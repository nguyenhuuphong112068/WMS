<?php

namespace App\Support\InitialData;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\CategoryUnitConversion;
use App\Support\ChemicalClassification;
use App\Support\ChemicalCode;
use App\Support\CheckStatus;
use App\Support\MaterialClassification;
use App\Support\MaterialCode;
use App\Support\StandardCode;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

/**
 * NẠP FILE EXCEL KHỞI TẠO DỮ LIỆU VÀO DB
 *
 * Toàn bộ file chạy trong MỘT transaction theo đúng thứ tự sheet của InitialDataSchema:
 *   - Có lỗi ở bất kỳ dòng nào -> rollback hết, trả danh sách lỗi (sheet, dòng, cột, lý do).
 *   - Chế độ KIỂM TRA ($commit = false) chạy y hệt rồi rollback, nên "kiểm tra đạt" thì
 *     "nhập thật" cũng đạt - không có hai bộ luật song song để lệch nhau.
 *
 * Dữ liệu đã có sẵn trong DB (trùng khoá tự nhiên: tên, mã...) được DÙNG LẠI, không ghi đè,
 * nên nạp lại cùng một file không sinh bản ghi trùng. Riêng tồn đầu kỳ thì mỗi phòng ban chỉ
 * được nạp một lần (đánh dấu bằng InitialDataSchema::OPENING_NOTE ở ghi chú phiếu nhập).
 *
 * Dữ liệu gốc / danh mục nạp vào ở trạng thái ĐÃ DUYỆT vì file đã được người có thẩm quyền
 * rà soát trước khi nạp; lô tồn đầu kỳ ghi nhận KIỂM TRA ĐẠT và cộng ngay vào tồn.
 */
class InitialDataImporter
{
    private const ACTION_CREATE = 'Thêm mới';

    private const HISTORY_NOTE = 'Khởi tạo dữ liệu ban đầu từ file Excel.';

    /** Số lỗi tối đa trả về - file sai hàng loạt thì vài trăm dòng đầu là đủ để sửa. */
    private const MAX_ERRORS = 500;

    private array $errors = [];

    private array $warnings = [];

    private array $stats = [];

    /** Bộ nhớ đệm tra cứu: [bảng.cột => [khoá chuẩn hoá => id hoặc bản ghi]]. */
    private array $cache = [];

    private array $columnCache = [];

    /** File sai cấu trúc (thiếu sheet, lệch cột): không nạp gì cả. */
    private bool $broken = false;

    private string $sheetKey = '';

    private string $now;

    private string $today;

    /**
     * @param  string  $actor  "Họ tên (userName)" ghi vào created_by / lịch sử
     * @param  string  $actorUserName  userName ghi vào Audit Trail
     * @param  string|null  $password  mật khẩu khởi tạo chung cho tài khoản mới
     */
    public function __construct(
        private string $actor,
        private string $actorUserName,
        private ?string $password = null,
    ) {
        $this->now = now()->format('Y-m-d H:i:s');
        $this->today = now()->format('Y-m-d');
    }

    /**
     * @return array{committed: bool, errors: array, warnings: array, stats: array}
     */
    public function run(string $path, bool $commit = false, string $fileName = ''): array
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $data = $this->read($path);

        // Sai cấu trúc file thì dừng ngay; lỗi từng dòng thì vẫn chạy tiếp để báo đủ một lần
        if ($data === null) {
            return $this->result(false);
        }

        DB::beginTransaction();

        try {
            foreach (InitialDataSchema::sheets() as $key => $sheet) {
                $this->sheetKey = $key;
                $this->stats[$key] = ['title' => $sheet['title'], 'rows' => count($data[$key]), 'inserted' => 0, 'existing' => 0];

                $valid = array_values(array_filter($data[$key], fn ($row) => empty($row['_invalid'])));
                $this->{'import'.str_replace('_', '', ucwords($key, '_'))}($valid);

                if ($key === 'conversions') {
                    $this->checkUnitConversions();
                }
            }
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error(null, null, 'Lỗi hệ thống khi nạp dữ liệu: '.$this->shortMessage($e));

            return $this->result(false);
        }

        if ($this->errors || ! $commit) {
            DB::rollBack();

            return $this->result(false);
        }

        $this->audit($fileName);
        DB::commit();

        return $this->result(true);
    }

    private function result(bool $committed): array
    {
        return [
            'committed' => $committed,
            'errors' => array_slice($this->errors, 0, self::MAX_ERRORS),
            'error_total' => count($this->errors),
            'warnings' => array_slice($this->warnings, 0, self::MAX_ERRORS),
            'stats' => array_values($this->stats),
        ];
    }

    // ================================================================== ĐỌC FILE

    /** Đọc file + kiểm tra đúng mẫu + chuẩn hoá từng ô theo kiểu cột. Sai cấu trúc file -> null. */
    private function read(string $path): ?array
    {
        $titles = array_map(fn ($s) => $s['title'], InitialDataSchema::sheets());

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(array_merge(array_values($titles), [InitialDataSchema::LIST_SHEET]));
            $book = $reader->load($path);
        } catch (Throwable $e) {
            $this->error(null, null, 'Không đọc được file Excel: '.$this->shortMessage($e));

            return null;
        }

        $version = $book->getSheetByName(InitialDataSchema::LIST_SHEET)?->getCell('A2')->getValue();
        if ($version !== InitialDataSchema::VERSION) {
            $this->error(null, null, 'File không đúng mẫu khởi tạo dữ liệu (phiên bản '.InitialDataSchema::VERSION.'). Vui lòng tải file mẫu mới nhất trên hệ thống và điền lại.');

            return null;
        }

        $data = [];
        foreach (InitialDataSchema::sheets() as $key => $sheet) {
            $this->sheetKey = $key;
            $data[$key] = $this->readSheet($book, $sheet);
        }

        $book->disconnectWorksheets();

        return $this->broken ? null : $data;
    }

    private function readSheet(Spreadsheet $book, array $sheet): array
    {
        $ws = $book->getSheetByName($sheet['title']);

        if (! $ws) {
            $this->error(null, null, 'Thiếu sheet "'.$sheet['title'].'". Không được đổi tên hoặc xoá sheet.');
            $this->broken = true;

            return [];
        }

        foreach ($sheet['columns'] as $i => $col) {
            $header = trim((string) $ws->getCell(InitialDataSchema::letter($i).InitialDataSchema::HEADER_ROW)->getValue());
            if ($header !== InitialDataSchema::headerLabel($col)) {
                $this->error(InitialDataSchema::HEADER_ROW, null, 'Cột '.InitialDataSchema::letter($i).' phải là "'.InitialDataSchema::headerLabel($col).'" (đang là "'.$header.'"). Không được chèn / xoá / đổi thứ tự cột.');
                $this->broken = true;

                return [];
            }
        }

        $rows = [];
        $lastRow = $ws->getHighestDataRow();

        for ($r = InitialDataSchema::FIRST_DATA_ROW; $r <= $lastRow; $r++) {
            $raw = [];
            foreach ($sheet['columns'] as $i => $col) {
                if ($col['type'] === 'calc') {
                    continue;
                }
                $value = $ws->getCell(InitialDataSchema::letter($i).$r)->getValue();
                $raw[$col['key']] = $value instanceof RichText ? $value->getPlainText() : $value;
            }

            $isEmpty = ! array_filter($raw, fn ($v) => $v !== null && trim((string) $v) !== '');
            if ($isEmpty) {
                continue;
            }

            $rows[] = $this->normalizeRow($sheet, $raw, $r);
        }

        return $rows;
    }

    /** Chuẩn hoá một dòng theo khai báo cột; lỗi kiểu dữ liệu đánh dấu _invalid để bỏ qua dòng. */
    private function normalizeRow(array $sheet, array $raw, int $r): array
    {
        $row = ['_row' => $r];
        $lists = InitialDataSchema::lists();

        foreach ($sheet['columns'] as $col) {
            if ($col['type'] === 'calc') {
                continue;
            }

            $value = $raw[$col['key']];
            $text = $this->cellText($value);
            $label = $col['label'];

            if ($text === '') {
                if (! empty($col['req'])) {
                    $row['_invalid'] = $this->error($r, $label, 'Bắt buộc nhập.');
                }
                $row[$col['key']] = $col['type'] === 'multi' ? [] : null;

                continue;
            }

            switch ($col['type']) {
                case 'int':
                case 'num':
                    $number = $this->toNumber($value);
                    if ($number === null || ($col['type'] === 'int' && floor($number) != $number)) {
                        $row['_invalid'] = $this->error($r, $label, '"'.$text.'" không phải '.($col['type'] === 'int' ? 'số nguyên' : 'số').'.');
                        break;
                    }
                    if ((isset($col['gt']) && $number <= $col['gt'])
                        || (isset($col['min']) && $number < $col['min'])
                        || (isset($col['maxValue']) && $number > $col['maxValue'])) {
                        $row['_invalid'] = $this->error($r, $label, 'Giá trị '.$text.' nằm ngoài giới hạn cho phép'.$this->rangeText($col).'.');
                        break;
                    }
                    $row[$col['key']] = $col['type'] === 'int' ? (int) $number : $number;
                    break;

                case 'date':
                    $date = $this->toDate($value);
                    if ($date === null) {
                        $row['_invalid'] = $this->error($r, $label, '"'.$text.'" không phải ngày hợp lệ (dd/mm/yyyy).');
                        break;
                    }
                    $row[$col['key']] = $date;
                    break;

                case 'list':
                    $mapped = $this->matchList($lists[$col['list']], $text);
                    if ($mapped === null && empty($col['free'])) {
                        $row['_invalid'] = $this->error($r, $label, '"'.$text.'" không có trong danh sách chọn.');
                        break;
                    }
                    $row[$col['key']] = $mapped ?? $text;
                    if (! empty($col['free']) && isset($col['max']) && mb_strlen($text) > $col['max']) {
                        $row['_invalid'] = $this->error($r, $label, 'Tối đa '.$col['max'].' ký tự.');
                    }
                    break;

                case 'multi':
                    $values = [];
                    foreach (preg_split('/[,;]+/u', $text) as $part) {
                        $part = trim($part);
                        if ($part === '') {
                            continue;
                        }
                        $mapped = $this->matchList($lists[$col['list']], $part);
                        if ($mapped === null) {
                            $row['_invalid'] = $this->error($r, $label, '"'.$part.'" không phải mã hợp lệ.');
                            continue;
                        }
                        $values[$mapped] = $mapped;
                    }
                    $row[$col['key']] = array_values($values);
                    break;

                case 'color':
                    if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $text)) {
                        $row['_invalid'] = $this->error($r, $label, '"'.$text.'" không phải mã màu dạng #RRGGBB.');
                        break;
                    }
                    $row[$col['key']] = strtoupper($text);
                    break;

                case 'ref':
                    // Ô chọn danh mục hiển thị "mã | tên": chỉ lấy phần mã
                    $row[$col['key']] = in_array($col['ref'], ['R_DM_VT', 'R_DM_HC', 'R_DM_CC'], true)
                        ? trim(explode(trim(InitialDataSchema::REF_SEPARATOR), $text)[0])
                        : $text;
                    break;

                default:
                    if (isset($col['max']) && mb_strlen($text) > $col['max']) {
                        $row['_invalid'] = $this->error($r, $label, 'Tối đa '.$col['max'].' ký tự (đang có '.mb_strlen($text).').');
                        break;
                    }
                    if (isset($col['min_len']) && mb_strlen($text) < $col['min_len']) {
                        $row['_invalid'] = $this->error($r, $label, 'Tối thiểu '.$col['min_len'].' ký tự.');
                        break;
                    }
                    if (isset($col['pattern']) && ! preg_match($col['pattern'], $text)) {
                        $row['_invalid'] = $this->error($r, $label, '"'.$text.'" không đúng mẫu mã ('.$col['hint'].').');
                        break;
                    }
                    $row[$col['key']] = $text;
            }
        }

        return $row;
    }

    private function cellText($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) == $value && abs($value) < 1e15) {
            return (string) (int) $value;
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function toNumber($value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = str_replace(' ', '', trim((string) $value));
        // "1.234,5" (kiểu Việt) -> 1234.5 ; "1,5" -> 1.5
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $text)) {
            $text = str_replace(['.', ','], ['', '.'], $text);
        } elseif (preg_match('/^-?\d+,\d+$/', $text)) {
            $text = str_replace(',', '.', $text);
        } else {
            $text = str_replace(',', '', $text);
        }

        return is_numeric($text) ? (float) $text : null;
    }

    private function toDate($value): ?string
    {
        try {
            if (is_int($value) || is_float($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            }

            $text = trim((string) $value);
            foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y'] as $format) {
                $date = \DateTime::createFromFormat('!'.$format, $text);
                if ($date && $date->format($format) === $text) {
                    return $date->format('Y-m-d');
                }
            }
        } catch (Throwable) {
        }

        return null;
    }

    /** So khớp nhãn danh sách: nhận nhãn đầy đủ, giá trị lưu DB hoặc phần mã trước " - ". */
    private function matchList(array $items, string $text): string|int|null
    {
        $needle = mb_strtolower($text);

        foreach ($items as $label => $value) {
            $label = (string) $label;
            if (mb_strtolower($label) === $needle
                || mb_strtolower((string) $value) === $needle
                || mb_strtolower(explode(' - ', $label)[0]) === $needle) {
                return $value;
            }
        }

        return null;
    }

    private function rangeText(array $col): string
    {
        $parts = [];
        if (isset($col['gt'])) {
            $parts[] = '> '.$col['gt'];
        }
        if (isset($col['min'])) {
            $parts[] = '≥ '.$col['min'];
        }
        if (isset($col['maxValue'])) {
            $parts[] = '≤ '.$col['maxValue'];
        }

        return $parts ? ' ('.implode(', ', $parts).')' : '';
    }

    // ================================================================== TỔ CHỨC

    private function importCompanies(array $rows): void
    {
        $this->eachUnique($rows, ['short_name'], function ($row) {
            if ($this->lookup('companies', 'short_name', $row['short_name'])) {
                return $this->existing();
            }
            if ($this->lookup('companies', 'name', $row['name'])) {
                return $this->error($row['_row'], 'Tên công ty', 'Tên "'.$row['name'].'" đã được công ty khác dùng.');
            }

            $code = $this->nextCode('companies', 'CT', 5);
            $id = $this->insertMaster('companies', [
                'code' => $code,
                'short_name' => $row['short_name'],
                'name' => $row['name'],
            ], ['Mã công ty' => $code, 'Tên viết tắt' => $row['short_name'], 'Tên công ty' => $row['name']]);

            $this->remember('companies', 'short_name', $row['short_name'], $id);
            $this->remember('companies', 'name', $row['name'], $id);
        });
    }

    private function importDepartments(array $rows): void
    {
        $this->eachUnique($rows, ['short_name'], function ($row) {
            if ($this->lookup('deparments', 'shortName', $row['short_name'])) {
                return $this->existing();
            }
            if ($this->lookup('deparments', 'name', $row['name'])) {
                return $this->error($row['_row'], 'Tên phòng ban', 'Tên "'.$row['name'].'" đã được phòng ban khác dùng.');
            }
            $companyId = $this->requireRef($row, 'companies', 'short_name', 'company', 'Công ty', 'sheet 01');
            if (! $companyId) {
                return;
            }

            $id = DB::table('deparments')->insertGetId([
                'company_id' => $companyId,
                'name' => $row['name'],
                'shortName' => $row['short_name'],
                'EngshortName' => $row['eng_short_name'],
                // "Phòng ban chung" = không tham gia nghiệp vụ kho -> is_general = 0
                'is_general' => (int) ($row['is_common'] ?? 0) === 1 ? 0 : 1,
                'isActive' => 1,
                'prepareBy' => $this->actor,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->masterHistory('deparments', $id, [
                'Tên viết tắt' => $row['short_name'],
                'Tên phòng ban' => $row['name'],
                'Công ty' => $row['company'],
                'Phòng ban chung' => (int) ($row['is_common'] ?? 0) === 1 ? 'Có' : 'Không',
            ]);
            $this->inserted();

            $this->remember('deparments', 'shortName', $row['short_name'], $id);
            $this->remember('deparments', 'name', $row['name'], $id);
        });
    }

    private function importGroups(array $rows): void
    {
        $this->eachUnique($rows, ['department', 'name'], function ($row) {
            $deptId = $this->department($row);
            if (! $deptId) {
                return;
            }
            if ($this->scopedId('groups', $deptId, $row['name'])) {
                return $this->existing();
            }

            $id = $this->insertMaster('groups', ['department_id' => $deptId, 'name' => $row['name']], [
                'Phòng ban' => $row['department'], 'Tên tổ' => $row['name'],
            ]);
            $this->rememberScoped('groups', $deptId, $row['name'], $id);
        });
    }

    private function importRoles(array $rows): void
    {
        $this->eachUnique($rows, ['name'], function ($row) {
            if ($this->lookup('roles', 'name', $row['name'])) {
                return $this->existing();
            }

            $id = DB::table('roles')->insertGetId([
                'name' => $row['name'],
                'description' => $row['description'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->inserted();
            $this->remember('roles', 'name', $row['name'], $id);
        });
    }

    private function importUsers(array $rows): void
    {
        $passwordChecked = false;

        $this->eachUnique($rows, ['user_name'], function ($row) use (&$passwordChecked) {
            if ($this->lookup('user_management', 'userName', $row['user_name'])) {
                return $this->existing();
            }

            if (! $passwordChecked) {
                $passwordChecked = true;
                if (! self::validPassword($this->password)) {
                    $this->error(null, null, 'Sheet người dùng có tài khoản mới: cần nhập "Mật khẩu khởi tạo" (tối thiểu 6 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt).');
                }
            }
            if (! self::validPassword($this->password)) {
                return;
            }

            $deptId = $this->department($row);
            $roleIds = [];
            foreach (['role_1' => 'Vai trò chính', 'role_2' => 'Vai trò 2', 'role_3' => 'Vai trò 3'] as $key => $label) {
                if ($row[$key] === null) {
                    continue;
                }
                $roleId = $this->requireRef($row, 'roles', 'name', $key, $label, 'sheet 04');
                if ($roleId) {
                    $roleIds[$roleId] = $roleId;
                }
            }
            $groupId = null;
            if ($deptId && $row['group'] !== null) {
                $groupId = $this->scopedId('groups', $deptId, $row['group']);
                if (! $groupId) {
                    $this->error($row['_row'], 'Tổ / nhóm', 'Tổ "'.$row['group'].'" không thuộc phòng ban '.$row['department'].' (sheet 03).');
                }
            }
            if (! $deptId || ! $roleIds || ($row['group'] !== null && ! $groupId)) {
                return;
            }

            $hash = Hash::make($this->password);
            $primaryRoleId = array_values($roleIds)[0];

            $id = DB::table('user_management')->insertGetId([
                'userName' => $row['user_name'],
                'passWord' => $hash,
                'fullName' => $row['full_name'],
                'role_id' => $primaryRoleId,
                'deparment_id' => $deptId,
                'group_id' => $groupId,
                'company_id' => DB::table('deparments')->where('id', $deptId)->value('company_id'),
                'mail' => $row['mail'],
                'changePWdate' => today()->addDays(90),
                // Mật khẩu do quản trị đặt: buộc đổi ở lần đăng nhập đầu tiên
                'must_change_password' => 1,
                'prepareBy' => $this->actor,
                'created_at' => $this->now,
            ]);

            DB::table('password_histories')->insert([
                'user_id' => $id,
                'password_hash' => $hash,
                'created_by' => $this->actor,
                'created_at' => $this->now,
            ]);

            $adminId = $this->lookup('roles', 'name', 'Admin');
            foreach ($roleIds as $roleId) {
                // Vai trò gán theo đúng phòng ban của người dùng; riêng Admin áp mọi phòng
                DB::table('user_role')->insert([
                    'user_id' => $id,
                    'role_id' => $roleId,
                    'department_id' => $roleId === $adminId ? null : $deptId,
                ]);
            }

            if ($groupId) {
                DB::table('user_group')->insert([
                    'user_id' => $id, 'group_id' => $groupId, 'created_at' => $this->now, 'updated_at' => $this->now,
                ]);
            }

            $this->inserted();
            $this->remember('user_management', 'userName', $row['user_name'], $id);
        });
    }

    public static function validPassword(?string $password): bool
    {
        return $password !== null
            && mb_strlen($password) >= 6
            && preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/', $password) === 1;
    }

    // ================================================================== DỮ LIỆU GỐC DÙNG CHUNG

    private function importUnits(array $rows): void
    {
        $this->eachUnique($rows, ['short_name'], function ($row) {
            if ($this->lookup('units', 'short_name', $row['short_name'])) {
                return $this->existing();
            }
            if ($this->lookup('units', 'name', $row['name'])) {
                return $this->error($row['_row'], 'Tên đơn vị', 'Tên "'.$row['name'].'" đã được đơn vị khác dùng.');
            }

            $id = $this->insertMaster('units', [
                'short_name' => $row['short_name'],
                'name' => $row['name'],
                'unit_group' => $row['unit_group'],
                'factor_to_base' => $row['factor_to_base'],
            ], [
                'Ký hiệu' => $row['short_name'], 'Tên đơn vị tính' => $row['name'],
                'Nhóm đơn vị' => $row['unit_group'], 'Hệ số quy về đơn vị gốc' => $row['factor_to_base'],
            ]);
            $this->remember('units', 'short_name', $row['short_name'], $id);
            $this->remember('units', 'name', $row['name'], $id);
        });
    }

    private function importPackaging(array $rows): void
    {
        $this->simpleNames($rows, 'packaging_specifications', 'Quy cách đóng gói');
    }

    private function importStorage(array $rows): void
    {
        $this->simpleNames($rows, 'storage_conditions', 'Điều kiện bảo quản', fn ($row) => ['note' => $row['note']]);
    }

    private function importManufacturers(array $rows): void
    {
        $this->eachUnique($rows, ['short_name'], function ($row) {
            if ($this->lookup('manufacturers', 'short_name', $row['short_name'])) {
                return $this->existing();
            }
            if ($this->lookup('manufacturers', 'name', $row['name'])) {
                return $this->error($row['_row'], 'Tên nhà sản xuất', 'Tên "'.$row['name'].'" đã được nhà sản xuất khác dùng.');
            }

            $id = $this->insertMaster('manufacturers', ['short_name' => $row['short_name'], 'name' => $row['name']], [
                'Tên viết tắt' => $row['short_name'], 'Tên nhà sản xuất' => $row['name'],
            ]);
            $this->remember('manufacturers', 'short_name', $row['short_name'], $id);
            $this->remember('manufacturers', 'name', $row['name'], $id);
        });
    }

    private function importSuppliers(array $rows): void
    {
        $this->simpleNames($rows, 'suppliers', 'Tên nhà cung cấp', fn ($row) => [
            'address' => $row['address'], 'tax_no' => $row['tax_no'], 'note' => $row['note'],
        ]);
    }

    private function importPurposes(array $rows): void
    {
        $this->simpleNames($rows, 'purposes', 'Chỉ tiêu kiểm');
    }

    private function importProducts(array $rows): void
    {
        $this->simpleNames($rows, 'product_names', 'Tên sản phẩm');
    }

    private function importConsumption(array $rows): void
    {
        $this->eachUnique($rows, ['type', 'code', 'frequency'], function ($row) {
            $exists = DB::table('consumption_objects')
                ->where('type', $row['type'])->where('code', $row['code'])->where('frequency', $row['frequency'])
                ->exists();
            if ($exists) {
                return $this->existing();
            }

            $this->insertMaster('consumption_objects', [
                'source' => 'manual',
                'type' => $row['type'],
                'code' => $row['code'],
                'name' => $row['name'],
                'location' => $row['location'],
                'frequency' => $row['frequency'],
            ], ['Loại' => $row['type'], 'Mã' => $row['code'], 'Tên' => $row['name'], 'Tần suất' => $row['frequency']]);
        });
    }

    private function importDeptClass(array $rows): void
    {
        $this->eachUnique($rows, ['department', 'name'], function ($row) {
            $deptId = $this->department($row);
            if (! $deptId) {
                return;
            }
            if ($this->scopedId('department_classification', $deptId, $row['name'])) {
                return $this->existing();
            }

            $id = $this->insertMaster('department_classification', ['department_id' => $deptId, 'name' => $row['name']], [
                'Phòng ban' => $row['department'], 'Tên phân loại' => $row['name'],
            ]);
            $this->rememberScoped('department_classification', $deptId, $row['name'], $id);
        });
    }

    // ================================================================== TÊN HÀNG

    private function importMaterialNames(array $rows): void
    {
        $this->simpleNames($rows, 'material_names', 'Tên vật tư');
    }

    private function importChemNames(array $rows): void
    {
        $this->simpleNames($rows, 'chem_names', 'Tên hoá chất');
    }

    private function importStandardNames(array $rows): void
    {
        $this->simpleNames($rows, 'standard_names', 'Tên chất chuẩn', fn ($row) => [
            'active_ingredient_name' => $row['active_ingredient_name'], 'cas_no' => $row['cas_no'],
        ]);
    }

    /**
     * Thành phần hoá chất: gom theo tên hoá chất rồi kiểm tra điều kiện Bảng B trên CẢ bộ
     * thành phần (đã có trong DB + khai thêm trong file).
     */
    private function importChemComp(array $rows): void
    {
        $byChem = [];

        foreach ($rows as $row) {
            $chemId = $this->requireRef($row, 'chem_names', 'name', 'chem_name', 'Tên hoá chất', 'sheet 16');
            if (! $chemId) {
                continue;
            }

            $hasAi = $row['ai_code'] !== null;
            $hasHazard = $row['hazard_code'] !== null;

            if ($hasAi === $hasHazard) {
                $this->error($row['_row'], null, 'Mỗi dòng khai đúng MỘT trong hai: Mã hoạt chất (Bảng A) hoặc Mã nhóm nguy hại (Bảng B).');
                continue;
            }
            if ($hasHazard && $row['content_percent'] !== null) {
                $this->error($row['_row'], 'Hàm lượng (% KL)', 'Hàm lượng chỉ khai cho dòng hoạt chất.');
                continue;
            }

            if ($hasAi) {
                $aiId = $this->approvedCode('active_ingredients', $row['ai_code']);
                if (! $aiId) {
                    $this->error($row['_row'], 'Mã hoạt chất (Bảng A)', 'Không có hoạt chất mã "'.$row['ai_code'].'" (tra sheet TC.Hoạt chất).');
                    continue;
                }
                if (isset($byChem[$chemId]['ai'][$aiId])) {
                    $this->error($row['_row'], 'Mã hoạt chất (Bảng A)', 'Hoạt chất '.$row['ai_code'].' bị khai trùng cho cùng hoá chất.');
                    continue;
                }
                $byChem[$chemId]['ai'][$aiId] = ['percent' => $row['content_percent'], 'row' => $row['_row']];
            } else {
                $hazardId = $this->approvedCode('mixture_hazard_categories', $row['hazard_code']);
                if (! $hazardId) {
                    $this->error($row['_row'], 'Mã nhóm nguy hại (Bảng B)', 'Không có nhóm nguy hại mã "'.$row['hazard_code'].'" (tra sheet TC.Nhóm nguy hại).');
                    continue;
                }
                $byChem[$chemId]['hazard'][$hazardId] = $row['_row'];
            }
            $byChem[$chemId]['name'] = $row['chem_name'];
        }

        foreach ($byChem as $chemId => $parts) {
            $newAi = $parts['ai'] ?? [];
            $newHazard = $parts['hazard'] ?? [];

            $existingAi = DB::table('chem_name_active_ingredient')->where('chem_names_id', $chemId)->pluck('active_ingredients_id')->all();
            $existingHazard = DB::table('chem_name_mixture_hazard_category')->where('chem_names_id', $chemId)->pluck('mixture_hazard_categories_id')->all();

            if ($newHazard) {
                $allAi = array_unique(array_merge($existingAi, array_keys($newAi)));
                $hasGroup9 = false;
                foreach (ChemicalClassification::groupsForActiveIngredients($allAi) as $groups) {
                    $hasGroup9 = $hasGroup9 || in_array(9, $groups, true);
                }
                if (count($allAi) < 2 || ! $hasGroup9) {
                    $this->error(reset($newHazard), 'Mã nhóm nguy hại (Bảng B)', 'Hoá chất "'.$parts['name'].'": chỉ phân loại Bảng B cho hỗn hợp có ít nhất 2 hoạt chất, trong đó có thành phần thuộc nhóm 9 (Bảng A).');
                    continue;
                }
            }

            foreach ($newAi as $aiId => $info) {
                if (in_array($aiId, $existingAi)) {
                    $this->existing();
                    continue;
                }
                DB::table('chem_name_active_ingredient')->insert([
                    'chem_names_id' => $chemId, 'active_ingredients_id' => $aiId, 'content_percent' => $info['percent'],
                    'created_at' => $this->now, 'updated_at' => $this->now,
                ]);
                $this->inserted();
            }

            foreach ($newHazard as $hazardId => $r) {
                if (in_array($hazardId, $existingHazard)) {
                    $this->existing();
                    continue;
                }
                DB::table('chem_name_mixture_hazard_category')->insert([
                    'chem_names_id' => $chemId, 'mixture_hazard_categories_id' => $hazardId,
                    'created_at' => $this->now, 'updated_at' => $this->now,
                ]);
                $this->inserted();
            }
        }
    }

    // ================================================================== DANH MỤC CÔNG TY

    private function importMaterialCats(array $rows): void
    {
        $this->eachUnique($rows, ['code'], function ($row) {
            if ($this->lookup('material_categories', 'code', $row['code'])) {
                return $this->existing();
            }
            $nameId = $this->requireRef($row, 'material_names', 'name', 'material_name', 'Tên vật tư', 'sheet 15');
            $makerId = $this->requireRef($row, 'manufacturers', 'short_name', 'manufacturer', 'Nhà sản xuất', 'sheet 09');
            if (! $nameId || ! $makerId) {
                return;
            }

            $sameCode = DB::table('material_categories')->where('material_names_id', $nameId)->where('manufacturers_id', $makerId)->value('code');
            if ($sameCode) {
                return $this->error($row['_row'], 'Tên vật tư', 'Tổ hợp Vật tư - Nhà sản xuất này đã có trong danh mục với mã '.$sameCode.'.');
            }

            $classification = [];
            foreach (array_keys(MaterialClassification::CRITERIA) as $criterion) {
                $classification[$criterion] = $row['cls_'.$criterion];
            }

            $id = DB::table('material_categories')->insertGetId([
                'code' => $row['code'],
                'material_names_id' => $nameId,
                'manufacturers_id' => $makerId,
                'technical_specification' => $row['technical_specification'],
                'classification' => MaterialClassification::encode($classification),
                'purchasing_department' => $row['purchasing_department'],
                'lead_time_days' => $row['lead_time_days'],
            ] + $this->approvedStamp());

            $this->categoryHistory('material_category_histories', 'material_category_id', 'material_categories', $id);
            $this->inserted();
            $this->remember('material_categories', 'code', $row['code'], $id);
        });
    }

    private function importChemicalCats(array $rows): void
    {
        $this->eachUnique($rows, ['code'], function ($row) {
            if ($this->lookup('chemical_categories', 'code', $row['code'])) {
                return $this->existing();
            }
            $nameId = $this->requireRef($row, 'chem_names', 'name', 'chem_name', 'Tên hoá chất', 'sheet 16');
            $makerId = $this->requireRef($row, 'manufacturers', 'short_name', 'manufacturer', 'Nhà sản xuất', 'sheet 09');
            $storageId = $this->optionalRef($row, 'storage_conditions', 'name', 'storage_condition', 'Điều kiện bảo quản', 'sheet 08');
            if (! $nameId || ! $makerId || $storageId === false) {
                return;
            }

            $sameCode = DB::table('chemical_categories')
                ->where('chem_names_id', $nameId)->where('manufacturers_id', $makerId)
                ->when($row['type'] === null, fn ($q) => $q->whereNull('type'), fn ($q) => $q->where('type', $row['type']))
                ->value('code');
            if ($sameCode) {
                return $this->error($row['_row'], 'Tên hoá chất', 'Tổ hợp Tên hoá chất - Loại - Nhà sản xuất này đã có trong danh mục với mã '.$sameCode.'.');
            }

            $id = DB::table('chemical_categories')->insertGetId([
                'code' => $row['code'],
                'type' => $row['type'],
                'chem_names_id' => $nameId,
                'manufacturers_id' => $makerId,
                'density' => $row['density'],
                'ai_content_percent' => $row['ai_content_percent'],
                'shelf_life_months' => $row['shelf_life_months'],
                'storage_condition_id' => $storageId,
                'doc_no' => $row['doc_no'],
                'safety_warning' => $row['safety_warning'] ? json_encode($row['safety_warning'], JSON_UNESCAPED_UNICODE) : null,
                'lead_time_days' => $row['lead_time_days'],
            ] + $this->approvedStamp());

            $this->categoryHistory('chemical_category_histories', 'chemical_category_id', 'chemical_categories', $id);
            $this->inserted();
            $this->remember('chemical_categories', 'code', $row['code'], $id);
        });
    }

    private function importStandardCats(array $rows): void
    {
        $this->eachUnique($rows, ['code'], function ($row) {
            if ($this->lookup('standard_categories', 'code', $row['code'])) {
                return $this->existing();
            }
            $nameId = $this->requireRef($row, 'standard_names', 'name', 'standard_name', 'Tên chất chuẩn', 'sheet 18');
            $makerId = $this->requireRef($row, 'manufacturers', 'short_name', 'manufacturer', 'Nguồn gốc / NSX', 'sheet 09');
            $storageId = $this->optionalRef($row, 'storage_conditions', 'name', 'storage_condition', 'Điều kiện bảo quản', 'sheet 08');
            if (! $nameId || ! $makerId || $storageId === false) {
                return;
            }

            // Giữ đúng thứ tự khai báo trong config để chuỗi JSON luôn ổn định
            $groups = array_values(array_intersect(array_keys(config('standard.groups')), $row['groups']));
            $maxVersion = DB::table('standard_categories')->where('chem_names_id', $nameId)->where('manufacturers_id', $makerId)->max('version');

            $id = DB::table('standard_categories')->insertGetId([
                'code' => $row['code'],
                'chem_names_id' => $nameId,
                'cas_no' => $row['cas_no'],
                'manufacturers_id' => $makerId,
                'density' => $row['density'],
                'storage_condition_id' => $storageId,
                'version' => $maxVersion === null ? 0 : $maxVersion + 1,
                'groups' => json_encode($groups),
                'shelf_life_months' => $row['shelf_life_months'],
                'doc_no' => $row['doc_no'],
                'note' => $row['note'],
                'lead_time_days' => $row['lead_time_days'],
            ] + $this->approvedStamp());

            $this->categoryHistory('standard_category_histories', 'standard_category_id', 'standard_categories', $id);
            $this->inserted();
            $this->remember('standard_categories', 'code', $row['code'], $id);
        });
    }

    // ================================================================== ĐỊNH KHU

    private const ZONE_TABLES = ['warehouse' => 'warehouses', 'shelf' => 'shelves', 'column' => 'columns', 'tier' => 'tiers', 'location' => 'locations'];

    private const ZONE_LABELS = ['warehouse' => 'Mã kho', 'shelf' => 'Mã kệ / tủ', 'column' => 'Mã cột', 'tier' => 'Mã tầng'];

    private function importWarehouses(array $rows): void
    {
        $this->importZone($rows, 'warehouse');
    }

    private function importShelves(array $rows): void
    {
        $this->importZone($rows, 'shelf');
    }

    private function importColumns(array $rows): void
    {
        $this->importZone($rows, 'column');
    }

    private function importTiers(array $rows): void
    {
        $this->importZone($rows, 'tier');
    }

    private function importLocations(array $rows): void
    {
        $this->importZone($rows, 'location');
    }

    /**
     * Một cấp định khu. Cấp cha khai ở cấp gần nhất là đủ: các cấp trên suy ra từ bản ghi cha;
     * khai thêm cấp trên mà lệch với cấp suy ra thì báo lỗi. Mọi cấp cha phải cùng phòng ban.
     */
    private function importZone(array $rows, string $level): void
    {
        $table = self::ZONE_TABLES[$level];
        $parentLevels = array_slice(array_keys(self::ZONE_LABELS), 0, array_search($level, array_keys(self::ZONE_TABLES)));

        $this->eachUnique($rows, ['code'], function ($row) use ($table, $level, $parentLevels) {
            $deptId = $this->department($row);
            if (! $deptId) {
                return;
            }

            $current = $this->zone($table, $row['code']);
            if ($current) {
                return (int) $current->department_id === $deptId
                    ? $this->existing()
                    : $this->error($row['_row'], 'Mã', 'Mã "'.$row['code'].'" đã được phòng ban khác dùng.');
            }

            // Lần từ cấp cha sâu nhất được khai lên trên
            $parents = array_fill_keys(array_map(fn ($l) => $l.'_id', $parentLevels), null);
            foreach (array_reverse($parentLevels) as $parentLevel) {
                $code = $row[$parentLevel] ?? null;
                if ($code === null) {
                    continue;
                }

                $parent = $this->zone(self::ZONE_TABLES[$parentLevel], $code);
                if (! $parent) {
                    return $this->error($row['_row'], self::ZONE_LABELS[$parentLevel], 'Không có "'.$code.'" ở sheet định khu tương ứng.');
                }
                if ((int) $parent->department_id !== $deptId) {
                    return $this->error($row['_row'], self::ZONE_LABELS[$parentLevel], '"'.$code.'" thuộc phòng ban khác.');
                }

                $derived = $parents[$parentLevel.'_id'];
                if ($derived !== null && $derived !== (int) $parent->id) {
                    return $this->error($row['_row'], self::ZONE_LABELS[$parentLevel], '"'.$code.'" không khớp với cấp con đã khai (cấp con thuộc '.self::ZONE_LABELS[$parentLevel].' khác).');
                }
                $parents[$parentLevel.'_id'] = (int) $parent->id;

                foreach (['warehouse_id', 'shelf_id', 'column_id', 'tier_id'] as $column) {
                    if (array_key_exists($column, $parents) && $parents[$column] === null && ! empty($parent->$column)) {
                        $parents[$column] = (int) $parent->$column;
                    }
                }
            }

            $data = ['code' => $row['code'], 'department_id' => $deptId] + $parents + [
                'zone_type' => $row['zone_type'] ?? null,
                'color' => $row['color'] ?? null,
            ];
            if ($level !== 'location') {
                $data['name'] = $row['name'];
            } else {
                $data['item_type'] = $row['item_type'];
            }

            $id = DB::table($table)->insertGetId($data + [
                'status_id' => 1,
                'created_by' => $this->actor,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->masterHistory($table, $id, ['Phòng ban' => $row['department'], 'Mã' => $row['code']] + ($level !== 'location' ? ['Tên' => $row['name']] : []));
            $this->inserted();

            $this->cache['zone.'.$table][mb_strtolower($row['code'])] = (object) (['id' => $id] + $data);
        });
    }

    private function zone(string $table, string $code): ?object
    {
        $key = mb_strtolower($code);

        if (! array_key_exists($key, $this->cache['zone.'.$table] ?? [])) {
            $this->cache['zone.'.$table][$key] = DB::table($table)->where('code', $code)->first();
        }

        return $this->cache['zone.'.$table][$key];
    }

    // ================================================================== HÀNG CỦA PHÒNG

    private function importDeptMaterials(array $rows): void
    {
        $this->importDepartmentItems($rows, CategoryUnitConversion::TYPE_MATERIAL);
    }

    private function importDeptChemicals(array $rows): void
    {
        $this->importDepartmentItems($rows, CategoryUnitConversion::TYPE_CHEMICAL);
    }

    private function importDeptStandards(array $rows): void
    {
        $this->importDepartmentItems($rows, CategoryUnitConversion::TYPE_STANDARD);
    }

    private function importDepartmentItems(array $rows, string $type): void
    {
        [$categoryTable, $deptTable] = $this->typeTables($type);

        $this->eachUnique($rows, ['department', 'category'], function ($row) use ($type, $categoryTable, $deptTable) {
            $deptId = $this->department($row);
            $categoryId = $this->requireRef($row, $categoryTable, 'code', 'category', 'Danh mục', 'sheet '.$this->categorySheetNo($type));
            $unitId = $this->requireRef($row, 'units', 'short_name', 'unit', 'Đơn vị tính của phòng', 'sheet 06');
            if (! $deptId || ! $categoryId || ! $unitId) {
                return;
            }

            if (DB::table($deptTable)->where('department_id', $deptId)->where('category_id', $categoryId)->exists()) {
                return $this->existing();
            }

            if ($row['min_stock'] !== null && $row['max_stock'] !== null && $row['max_stock'] < $row['min_stock']) {
                return $this->error($row['_row'], 'Tồn tối đa', 'Tồn tối đa phải lớn hơn hoặc bằng tồn tối thiểu.');
            }

            $locationId = null;
            if ($row['default_location'] !== null) {
                $locationId = $this->usableLocation($row, $row['default_location'], $deptId, $type, 'Vị trí mặc định');
                if (! $locationId) {
                    return;
                }
            }

            $data = [
                'department_id' => $deptId,
                'category_id' => $categoryId,
                'unit_id' => $unitId,
                'min_stock' => $row['min_stock'],
                'max_stock' => $row['max_stock'],
                'default_location_id' => $locationId,
                'note' => $row['note'],
            ];

            if ($type === CategoryUnitConversion::TYPE_MATERIAL && $row['classification'] !== null) {
                $data['classification_id'] = $this->scopedId('department_classification', $deptId, $row['classification']);
                if (! $data['classification_id']) {
                    return $this->error($row['_row'], 'Phân loại của phòng', 'Phân loại "'.$row['classification'].'" không thuộc phòng ban '.$row['department'].' (sheet 14).');
                }
            }

            if ($type === CategoryUnitConversion::TYPE_CHEMICAL) {
                $storageId = $this->optionalRef($row, 'storage_conditions', 'name', 'storage_condition', 'Điều kiện bảo quản', 'sheet 08');
                if ($storageId === false) {
                    return;
                }
                $isCount = DB::table('units')->where('id', $unitId)->value('unit_group') === 'count';
                $data += [
                    // Khối lượng / 1 đơn vị chỉ có nghĩa với đơn vị đếm / bao bì
                    'pack_weight_kg' => $isCount ? $row['pack_weight_kg'] : null,
                    'shelf_life_months' => $row['shelf_life_months'],
                    'storage_condition_id' => $storageId,
                ];
            }

            if ($type === CategoryUnitConversion::TYPE_STANDARD) {
                $data['shelf_life_months'] = $row['shelf_life_months'];
            }

            DB::table($deptTable)->insert($data + [
                'status_id' => 1,
                'created_by' => $this->actor,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->inserted();
            $this->cache['deptRow.'.$type][$deptId.'-'.$categoryId] = $row['_row'];
        });
    }

    private function importConversions(array $rows): void
    {
        $this->eachUnique($rows, ['category_type', 'category_code', 'from_unit', 'to_unit'], function ($row) {
            [$categoryTable] = $this->typeTables($row['category_type']);
            $code = trim(explode(trim(InitialDataSchema::REF_SEPARATOR), $row['category_code'])[0]);
            $categoryId = $this->lookup($categoryTable, 'code', $code);
            if (! $categoryId) {
                return $this->error($row['_row'], 'Mã DM', 'Không có mã danh mục "'.$code.'" thuộc loại hàng đã chọn.');
            }
            $fromId = $this->requireRef($row, 'units', 'short_name', 'from_unit', 'Từ đơn vị', 'sheet 06');
            $toId = $this->requireRef($row, 'units', 'short_name', 'to_unit', 'Sang đơn vị', 'sheet 06');
            if (! $fromId || ! $toId) {
                return;
            }
            if ($fromId === $toId) {
                return $this->error($row['_row'], 'Sang đơn vị', 'Hai đơn vị giống nhau, không cần quy đổi.');
            }

            $exists = DB::table('category_unit_conversions')
                ->where('category_type', $row['category_type'])->where('category_id', $categoryId)
                ->where(fn ($q) => $q->where(fn ($s) => $s->where('from_unit_id', $fromId)->where('to_unit_id', $toId))
                    ->orWhere(fn ($s) => $s->where('from_unit_id', $toId)->where('to_unit_id', $fromId)))
                ->exists();
            if ($exists) {
                return $this->existing();
            }

            CategoryUnitConversion::save($row['category_type'], $categoryId, $fromId, $toId, (float) $row['factor'], $this->actor);
            if ($row['note'] !== null) {
                DB::table('category_unit_conversions')
                    ->where('category_type', $row['category_type'])->where('category_id', $categoryId)
                    ->where('from_unit_id', $fromId)->where('to_unit_id', $toId)
                    ->update(['note' => $row['note']]);
            }
            $this->inserted();
        });
    }

    /**
     * Cùng một mã hàng mà các phòng dùng đơn vị khác nhau thì phải đổi qua lại được
     * (cùng nhóm đơn vị, qua tỉ trọng, hoặc đã khai ở sheet 30) - không thì điều chuyển /
     * dự trù giữa các phòng sẽ không tính được số lượng.
     */
    private function checkUnitConversions(): void
    {
        $sheets = InitialDataSchema::sheets();

        foreach ([CategoryUnitConversion::TYPE_MATERIAL => 'dept_materials', CategoryUnitConversion::TYPE_CHEMICAL => 'dept_chemicals', CategoryUnitConversion::TYPE_STANDARD => 'dept_standards'] as $type => $sheetKey) {
            [$categoryTable, $deptTable] = $this->typeTables($type);
            $newRows = $this->cache['deptRow.'.$type] ?? [];
            if (! $newRows) {
                continue;
            }

            $categoryIds = array_unique(array_map(fn ($k) => (int) explode('-', $k)[1], array_keys($newRows)));

            $units = DB::table($deptTable)
                ->join('units', $deptTable.'.unit_id', '=', 'units.id')
                ->join($categoryTable, $deptTable.'.category_id', '=', $categoryTable.'.id')
                ->whereIn($deptTable.'.category_id', $categoryIds)
                ->select($deptTable.'.category_id', $deptTable.'.department_id', $deptTable.'.unit_id', 'units.short_name', $categoryTable.'.code')
                ->get()
                ->groupBy('category_id');

            foreach ($units as $categoryId => $list) {
                $distinct = $list->unique('unit_id')->values();
                for ($i = 0; $i < count($distinct); $i++) {
                    for ($j = $i + 1; $j < count($distinct); $j++) {
                        $a = $distinct[$i];
                        $b = $distinct[$j];
                        if (CategoryUnitConversion::factor($type, (int) $categoryId, (int) $a->unit_id, (int) $b->unit_id) !== null) {
                            continue;
                        }

                        $rowNo = $newRows[$b->department_id.'-'.$categoryId] ?? $newRows[$a->department_id.'-'.$categoryId] ?? null;
                        $this->errors[] = [
                            'sheet' => $sheets[$sheetKey]['title'],
                            'row' => $rowNo,
                            'column' => 'Đơn vị tính của phòng',
                            'message' => 'Mã '.$a->code.' đang dùng 2 đơn vị "'.$a->short_name.'" và "'.$b->short_name.'" ở các phòng khác nhau nhưng chưa quy đổi được - khai hệ số ở sheet 30.',
                        ];
                    }
                }
            }
        }
    }

    // ================================================================== TỒN ĐẦU KỲ

    private function importStockMaterials(array $rows): void
    {
        $this->importStock($rows, CategoryUnitConversion::TYPE_MATERIAL);
    }

    private function importStockChemicals(array $rows): void
    {
        $this->importStock($rows, CategoryUnitConversion::TYPE_CHEMICAL);
    }

    private function importStockStandards(array $rows): void
    {
        $this->importStock($rows, CategoryUnitConversion::TYPE_STANDARD);
    }

    private function importStock(array $rows, string $type): void
    {
        [$categoryTable, $deptTable, $sheetNo] = $this->typeTables($type);
        [$importTable, $historyTable, $historyKey] = match ($type) {
            CategoryUnitConversion::TYPE_MATERIAL => ['material_imports', 'material_import_histories', 'material_import_id'],
            CategoryUnitConversion::TYPE_CHEMICAL => ['chemical_imports', 'chemical_import_histories', 'import_id'],
            default => ['standard_imports', 'standard_import_histories', 'standard_import_id'],
        };

        // Phòng nào đã có tồn đầu kỳ (từ lần nạp trước) thì chặn, tránh cộng trùng tồn
        $alreadyLoaded = DB::table($importTable)->where('note', 'like', InitialDataSchema::OPENING_NOTE.'%')->distinct()->pluck('department_id')->map(fn ($id) => (int) $id)->all();
        $blocked = [];

        foreach ($rows as $row) {
            $this->guard($row, function () use ($row, $type, $categoryTable, $deptTable, $sheetNo, $importTable, $historyTable, $historyKey, $alreadyLoaded, &$blocked) {
                $deptId = $this->department($row);
                $categoryId = $this->requireRef($row, $categoryTable, 'code', 'category', 'Danh mục', 'sheet '.$this->categorySheetNo($type));
                if (! $deptId || ! $categoryId) {
                    return;
                }

                if (in_array($deptId, $alreadyLoaded, true)) {
                    if (! isset($blocked[$deptId])) {
                        $blocked[$deptId] = true;
                        $this->error($row['_row'], 'Phòng ban', 'Phòng '.$row['department'].' đã nạp tồn đầu kỳ trước đó, không nạp lại để tránh cộng trùng tồn.');
                    }

                    return;
                }

                $declared = DB::table($deptTable)->where('department_id', $deptId)->where('category_id', $categoryId)->where('status_id', 1)->exists();
                if (! $declared) {
                    return $this->error($row['_row'], 'Danh mục', 'Mã '.$row['category'].' chưa được khai cho phòng '.$row['department'].' ở '.$sheetNo.'.');
                }

                $locationId = $this->usableLocation($row, $row['location'], $deptId, $type, 'Vị trí lưu');
                if (! $locationId) {
                    return;
                }

                $common = [
                    'department_id' => $deptId,
                    'category_id' => $categoryId,
                    'amount' => $row['amount'],
                    'batch_no' => $row['batch_no'],
                    'invoice_number' => $row['invoice_number'],
                    'expired_date' => $row['expired_date'],
                    'location_id' => $locationId,
                    'note' => trim(InitialDataSchema::OPENING_NOTE.' '.($row['note'] ?? '')),
                    // Ngày nhập là ngày nạp file, không nhận từ file
                    'imported_date' => $this->today,
                    'imported_by' => $this->actor,
                    'status_id' => 1,
                    // Tồn đầu kỳ là hàng đang có thật trong kho: ghi nhận kiểm tra ĐẠT, cộng tồn ngay
                    'is_checked' => 1,
                    'check_result' => CheckStatus::PASSED,
                    'checked_by' => $this->actor,
                    'checked_at' => $this->now,
                    'check_note' => 'Tồn đầu kỳ khi khởi tạo hệ thống.',
                    'created_by' => $this->actor,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];

                if ($row['expired_date'] !== null && $row['expired_date'] < $this->today) {
                    $this->warning($row['_row'], 'Hạn sử dụng', 'Mã '.$row['category'].' đã hết hạn ('.Carbon::parse($row['expired_date'])->format('d/m/Y').'), vẫn nạp để xử lý huỷ trên hệ thống.');
                }

                $extra = match ($type) {
                    CategoryUnitConversion::TYPE_MATERIAL => $this->materialStockFields($row),
                    CategoryUnitConversion::TYPE_CHEMICAL => $this->chemicalStockFields($row),
                    default => $this->standardStockFields($row, $categoryId),
                };
                if ($extra === null) {
                    return;
                }

                $packs = (int) ($row['packs'] ?? 1);
                $shortName = DB::table('deparments')->where('id', $deptId)->value('shortName');

                for ($i = 0; $i < $packs; $i++) {
                    $code = match ($type) {
                        CategoryUnitConversion::TYPE_MATERIAL => ['code' => MaterialCode::next($deptId)],
                        CategoryUnitConversion::TYPE_CHEMICAL => ['code' => ChemicalCode::next($deptId)],
                        default => StandardCode::next($deptId, (string) $shortName, $extra['group_code'], $this->today),
                    };

                    $id = DB::table($importTable)->insertGetId($common + $extra + $code);
                    $this->importHistory($historyTable, $historyKey, $importTable, $id, 'Nạp tồn đầu kỳ khi khởi tạo hệ thống, mã '.$code['code'].'.');
                    $this->inserted();
                }
            });
        }
    }

    private function materialStockFields(array $row): array
    {
        return [
            'invoice_date' => $row['invoice_date'],
            'purpose' => $row['purpose'],
        ];
    }

    private function chemicalStockFields(array $row): ?array
    {
        $supplierId = $this->optionalRef($row, 'suppliers', 'name', 'supplier', 'Nhà cung cấp', 'sheet 10');
        if ($supplierId === false) {
            return null;
        }

        return [
            'invoice_date' => $row['invoice_date'],
            'internal_expired_date' => $row['internal_expired_date'],
            'is_microbiological_chemicals' => (int) ($row['is_microbiological_chemicals'] ?? 0),
            'supplier_id' => $supplierId,
        ];
    }

    private function standardStockFields(array $row, int $categoryId): ?array
    {
        $categoryGroups = StandardCode::decodeGroups(DB::table('standard_categories')->where('id', $categoryId)->value('groups'));
        $groupKey = $row['group'] ?? ($categoryGroups[0] ?? null);

        if (! $groupKey || ! in_array($groupKey, $categoryGroups, true)) {
            $this->error($row['_row'], 'Nhóm chuẩn', $row['group']
                ? 'Mã '.$row['category'].' không thuộc nhóm '.$row['group'].' (nhóm của DM: '.implode(', ', $categoryGroups).').'
                : 'Mã '.$row['category'].' chưa có phân nhóm chuẩn ở sheet 21.');

            return null;
        }

        if ($row['expiry_type'] !== 'check online' && $row['expired_date'] === null) {
            $this->error($row['_row'], 'Hạn sử dụng', 'Bắt buộc nhập hạn sử dụng (trừ kiểu "Chưa xác định (Check online)").');

            return null;
        }

        $supplierId = $this->optionalRef($row, 'suppliers', 'name', 'supplier', 'Nhà cung cấp', 'sheet 10');
        if ($supplierId === false) {
            return null;
        }

        $purposeIds = [];
        foreach (preg_split('/[;,]+/u', (string) $row['purposes']) as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $purposeId = $this->lookup('purposes', 'name', $name);
            if (! $purposeId) {
                $this->error($row['_row'], 'Mục đích sử dụng', 'Không có mục đích "'.$name.'" ở sheet 11.');

                return null;
            }
            $purposeIds[$purposeId] = $purposeId;
        }

        $weightControlled = (int) ($row['weight_controlled'] ?? 0);

        return [
            'group_code' => StandardCode::groupCode($groupKey),
            'expiry_type' => $row['expiry_type'],
            'expired_date' => $row['expiry_type'] === 'check online' ? null : $row['expired_date'],
            'retest_interval_months' => $row['expiry_type'] === 'retest' ? $row['retest_interval_months'] : null,
            'internal_expired_date' => $row['internal_expired_date'],
            'coa_no' => $row['coa_no'],
            'potency' => $row['potency'],
            'moisture' => $row['moisture'],
            'weight_controlled' => $weightControlled,
            'standard_form' => $weightControlled ? $row['standard_form'] : null,
            'requires_aliquot' => (int) ($row['requires_aliquot'] ?? 0),
            'supplier_id' => $supplierId,
            'purpose_id' => $purposeIds ? json_encode(array_values($purposeIds)) : null,
        ];
    }

    /** Vị trí thuộc đúng phòng, đang hoạt động, loại lưu trữ trống hoặc đúng loại hàng. */
    private function usableLocation(array $row, string $code, int $deptId, string $type, string $label): ?int
    {
        $location = $this->zone('locations', $code);

        if (! $location || (int) $location->department_id !== $deptId) {
            $this->error($row['_row'], $label, 'Không có vị trí "'.$code.'" của phòng '.$row['department'].' (sheet 26).');

            return null;
        }
        if ((int) ($location->status_id ?? 1) !== 1) {
            $this->error($row['_row'], $label, 'Vị trí "'.$code.'" đang bị khoá.');

            return null;
        }
        if (! empty($location->item_type) && $location->item_type !== $type) {
            $this->error($row['_row'], $label, 'Vị trí "'.$code.'" chỉ dành cho loại hàng khác.');

            return null;
        }

        return (int) $location->id;
    }

    // ================================================================== TIỆN ÍCH

    /** [bảng danh mục, bảng hàng của phòng, nhãn sheet hàng của phòng] theo loại hàng. */
    private function typeTables(string $type): array
    {
        return match ($type) {
            CategoryUnitConversion::TYPE_MATERIAL => ['material_categories', 'material_department_categories', 'sheet 27'],
            CategoryUnitConversion::TYPE_CHEMICAL => ['chemical_categories', 'chemical_department_categories', 'sheet 28'],
            default => ['standard_categories', 'standard_department_categories', 'sheet 29'],
        };
    }

    private function categorySheetNo(string $type): string
    {
        return match ($type) {
            CategoryUnitConversion::TYPE_MATERIAL => '19',
            CategoryUnitConversion::TYPE_CHEMICAL => '20',
            default => '21',
        };
    }

    /**
     * Duyệt các dòng, báo trùng khoá trong cùng sheet, mỗi dòng chạy trong guard() để lỗi
     * ràng buộc DB của một dòng không làm dừng cả file.
     */
    private function eachUnique(array $rows, array $keys, callable $handler): void
    {
        $seen = [];

        foreach ($rows as $row) {
            $key = mb_strtolower(implode('|', array_map(fn ($k) => (string) $row[$k], $keys)));

            if (isset($seen[$key])) {
                $this->error($row['_row'], null, 'Trùng với dòng '.$seen[$key].' trong cùng sheet.');

                continue;
            }
            $seen[$key] = $row['_row'];

            $this->guard($row, fn () => $handler($row));
        }
    }

    private function guard(array $row, callable $callback): void
    {
        try {
            $callback();
        } catch (QueryException $e) {
            $this->error($row['_row'], null, str_contains($e->getMessage(), 'Duplicate entry')
                ? 'Trùng với dữ liệu đã có trong hệ thống (có thể khác nhau chỉ ở dấu / hoa thường).'
                : 'Lỗi khi ghi dữ liệu: '.$this->shortMessage($e));
        }
    }

    /** Sheet chỉ có cột tên (+ vài cột phụ): tên trùng trong DB thì dùng lại. */
    private function simpleNames(array $rows, string $table, string $label, ?callable $extra = null): void
    {
        $this->eachUnique($rows, ['name'], function ($row) use ($table, $label, $extra) {
            if ($this->lookup($table, 'name', $row['name'])) {
                return $this->existing();
            }

            $more = $extra ? $extra($row) : [];
            $id = $this->insertMaster($table, ['name' => $row['name']] + $more, [$label => $row['name']]);
            $this->remember($table, 'name', $row['name'], $id);
        });
    }

    /** Thêm một dòng dữ liệu gốc + cột chuẩn + lịch sử "Thêm mới". */
    private function insertMaster(string $table, array $data, array $snapshot): int
    {
        $data += [
            'status_id' => 1,
            'created_by' => $this->actor,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ];

        if ($this->hasColumn($table, 'app_status')) {
            $data += ['app_status' => 'approved', 'approved_by' => $this->actor, 'approved_at' => $this->now];
        }

        $id = DB::table($table)->insertGetId($data);
        $this->masterHistory($table, $id, $snapshot, $this->hasColumn($table, 'app_status'));
        $this->inserted();

        return $id;
    }

    private function approvedStamp(): array
    {
        return [
            'app_status' => 'approved',
            'approved_by' => $this->actor,
            'approved_at' => $this->now,
            'status_id' => 1,
            'created_by' => $this->actor,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ];
    }

    private function masterHistory(string $table, int $id, array $snapshot, bool $approved = false): void
    {
        $snapshot = ['Người tạo' => $this->actor, 'Ngày tạo' => Carbon::parse($this->now)->format('d/m/Y H:i')]
            + array_map(fn ($v) => $v === null || $v === '' ? '—' : (string) $v, $snapshot)
            + ($approved ? ['Trạng thái duyệt' => 'Đã duyệt'] : [])
            + ['Trạng thái sử dụng' => 'Đang sử dụng'];

        DB::table('datamaster_histories')->insert([
            'table_name' => $table,
            'record_id' => $id,
            'action' => self::ACTION_CREATE,
            'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'change_note' => self::HISTORY_NOTE,
            'created_by' => $this->actor,
            'created_at' => $this->now,
        ]);
    }

    /** Lịch sử danh mục: chép các cột trùng tên giữa bảng danh mục và bảng lịch sử. */
    private function categoryHistory(string $historyTable, string $foreignKey, string $table, int $id): void
    {
        $this->copyToHistory($historyTable, $foreignKey, $table, $id, self::HISTORY_NOTE);
    }

    private function importHistory(string $historyTable, string $foreignKey, string $table, int $id, string $note): void
    {
        $this->copyToHistory($historyTable, $foreignKey, $table, $id, $note);
    }

    private function copyToHistory(string $historyTable, string $foreignKey, string $table, int $id, string $note): void
    {
        $row = (array) DB::table($table)->where('id', $id)->first();
        $columns = array_diff($this->columns($historyTable), ['id', $foreignKey, 'action', 'change_note', 'change_reason', 'reason', 'created_by', 'created_at', 'updated_at', 'updated_by']);

        $data = array_intersect_key($row, array_flip($columns));
        DB::table($historyTable)->insert($data + [
            $foreignKey => $id,
            'action' => self::ACTION_CREATE,
            'change_note' => $note,
            'created_by' => $this->actor,
            'created_at' => $this->now,
        ]);
    }

    private function columns(string $table): array
    {
        return $this->columnCache[$table] ??= Schema::getColumnListing($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    /** id theo khoá tự nhiên; DB so sánh theo collation (không phân biệt hoa thường). */
    private function lookup(string $table, string $column, ?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = $table.'.'.$column;
        $needle = mb_strtolower($value);

        if (! array_key_exists($needle, $this->cache[$key] ?? [])) {
            $id = DB::table($table)->where($column, $value)->value('id');
            $this->cache[$key][$needle] = $id ? (int) $id : null;
        }

        return $this->cache[$key][$needle];
    }

    private function remember(string $table, string $column, string $value, int $id): void
    {
        $this->cache[$table.'.'.$column][mb_strtolower($value)] = $id;
    }

    /** id của dòng (phòng ban, tên) - tổ, phân loại của phòng. */
    private function scopedId(string $table, int $deptId, string $name): ?int
    {
        $key = 'scoped.'.$table;
        $needle = $deptId.'|'.mb_strtolower($name);

        if (! array_key_exists($needle, $this->cache[$key] ?? [])) {
            $id = DB::table($table)->where('department_id', $deptId)->where('name', $name)->where('status_id', 1)->value('id');
            $this->cache[$key][$needle] = $id ? (int) $id : null;
        }

        return $this->cache[$key][$needle];
    }

    private function rememberScoped(string $table, int $deptId, string $name, int $id): void
    {
        $this->cache['scoped.'.$table][$deptId.'|'.mb_strtolower($name)] = $id;
    }

    private function approvedCode(string $table, string $code): ?int
    {
        $id = DB::table($table)->where('code', $code)->where('status_id', 1)->where('app_status', 'approved')->value('id');

        return $id ? (int) $id : null;
    }

    private function department(array $row): ?int
    {
        return $this->requireRef($row, 'deparments', 'shortName', 'department', 'Phòng ban', 'sheet 02');
    }

    /** id bắt buộc phải tìm thấy; không thấy thì báo lỗi và trả null. */
    private function requireRef(array $row, string $table, string $column, string $field, string $label, string $where): ?int
    {
        if ($row[$field] === null) {
            return null;
        }

        $id = $this->lookup($table, $column, $row[$field]);
        if (! $id) {
            $this->error($row['_row'], $label, 'Không tìm thấy "'.$row[$field].'" ('.$where.').');
        }

        return $id;
    }

    /** Ô không bắt buộc: trống -> null, có mà không tìm thấy -> false (đã báo lỗi). */
    private function optionalRef(array $row, string $table, string $column, string $field, string $label, string $where): int|false|null
    {
        if ($row[$field] === null) {
            return null;
        }

        return $this->requireRef($row, $table, $column, $field, $label, $where) ?? false;
    }

    private function nextCode(string $table, string $prefix, int $length): string
    {
        $max = DB::table($table)->where('code', 'like', $prefix.'%')->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($max + 1), $length, '0', STR_PAD_LEFT);
    }

    private function inserted(): void
    {
        $this->stats[$this->sheetKey]['inserted']++;
    }

    private function existing(): void
    {
        $this->stats[$this->sheetKey]['existing']++;
    }

    /** Ghi một lỗi; trả true để gán thẳng vào cờ _invalid. */
    private function error(?int $row, ?string $column, string $message): bool
    {
        $sheets = InitialDataSchema::sheets();

        $this->errors[] = [
            'sheet' => $this->sheetKey && isset($sheets[$this->sheetKey]) ? $sheets[$this->sheetKey]['title'] : '',
            'row' => $row,
            'column' => $column,
            'message' => $message,
        ];

        return true;
    }

    private function warning(?int $row, ?string $column, string $message): void
    {
        $this->warnings[] = [
            'sheet' => InitialDataSchema::sheets()[$this->sheetKey]['title'] ?? '',
            'row' => $row,
            'column' => $column,
            'message' => $message,
        ];
    }

    private function shortMessage(Throwable $e): string
    {
        return mb_substr(trim(preg_replace('/\s*\(Connection:.*$/s', '', $e->getMessage())), 0, 300);
    }

    /** Một dòng Audit Trail cho mỗi sheet có dữ liệu mới + một dòng tổng. */
    private function audit(string $fileName): void
    {
        $sheets = InitialDataSchema::sheets();
        $total = 0;

        foreach ($this->stats as $key => $stat) {
            if (! $stat['inserted']) {
                continue;
            }
            $total += $stat['inserted'];
            AuditTrialController::log(
                'Khởi tạo dữ liệu',
                $sheets[$key]['table'],
                'NA',
                'NA',
                'Nạp '.$stat['title'].' từ file "'.$fileName.'": thêm '.$stat['inserted'].', đã có sẵn '.$stat['existing'].'.',
                $this->actorUserName
            );
        }

        AuditTrialController::log('Khởi tạo dữ liệu', 'NA', 'NA', 'NA', 'Nạp file khởi tạo dữ liệu "'.$fileName.'": tổng cộng thêm mới '.$total.' bản ghi.', $this->actorUserName);
    }
}
