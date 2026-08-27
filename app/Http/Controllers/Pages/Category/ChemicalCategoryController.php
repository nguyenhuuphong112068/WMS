<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\CategoryUnitConversion;
use App\Support\DepartmentChemical;
use App\Support\UnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DANH MỤC - HOÁ CHẤT, TAB "DANH MỤC HOÁ CHẤT CÔNG TY"
 *
 * Màn hình này có 2 tab nằm chung một trang:
 * - Tab 1 "Danh Mục Hoá Chất Công Ty": bản chất của chất, dùng chung toàn công ty (chính là controller này).
 * - Tab 2 "Hoá Chất Của Phòng": cách dùng riêng của từng phòng, do DepartmentChemicalController xử lý.
 * Controller này dựng cả trang, nên index() lấy dữ liệu cho cả hai tab.
 *
 * Cột classification lưu danh sách mã nhóm phân loại dạng JSON, ví dụ ["PL2","N1"].
 * Danh sách mã đầy đủ khai báo tại config/chemical.php.
 *
 * ĐƠN VỊ TÍNH không khai ở đây: mỗi phòng nhập / xuất theo đơn vị của phòng mình nên
 * đơn vị nằm ở tab "Hoá Chất Của Phòng" (department_chemicals.unit_id).
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", sửa lại bản ghi đã duyệt sẽ đưa về "Chờ duyệt".
 * Mọi thay đổi đều được chụp lại ở bảng chemical_category_histories.
 */
class ChemicalCategoryController extends Controller
{
    private const TABLE = 'chemical_categories';
    private const HISTORY_TABLE = 'chemical_category_histories';
    private const LABEL = 'danh mục hoá chất công ty';

    /** Tiền tố mã danh mục sinh tự động: H00001, H00002... */
    private const CODE_PREFIX = 'H';
    private const CODE_LENGTH = 5;

    /** Các cột người dùng nhập, dùng chung cho so sánh lịch sử. Mã danh mục không sửa được nên không nằm ở đây. */
    private const FIELDS = [
        'type' => 'Loại',
        'chem_names_id' => 'Tên hoá chất',
        'manufacturers_id' => 'Nhà sản xuất',
        'density' => 'Tỉ trọng d (g/ml)',
        'shelf_life_months' => 'Hạn dùng mặc định (tháng)',
        'storage_condition_id' => 'Điều kiện bảo quản',
        'doc_no' => 'Số tài liệu',
        'classification' => 'Phân loại',
        'safety_warning' => 'Cảnh báo an toàn',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)
            ->leftJoin('chem_names', self::TABLE . '.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', self::TABLE . '.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('storage_conditions', self::TABLE . '.storage_condition_id', '=', 'storage_conditions.id')
            ->select(
                self::TABLE . '.*',
                'chem_names.name as chem_name',
                'chem_names.cas_no as cas_no',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'storage_conditions.name as storage_condition_name'
            )
            ->orderBy(self::TABLE . '.code', 'asc')
            ->get();

        session()->put(['title' => 'DANH MỤC - HOÁ CHẤT']);

        $departmentId = (int) (session('user')['selected_department_id'] ?? 0);

        // Tab 2 - Hoá Chất Của Phòng: cùng một trang nhưng thao tác thêm/sửa/khoá vẫn
        // gửi về DepartmentChemicalController, ở đây chỉ dựng dữ liệu để hiển thị.
        $dcDatas = DepartmentChemical::rowsOfDepartment($departmentId);

        return view('pages.category.ChemicalCategory.list', [
            'datas' => $datas,
            'chemNames' => $this->options('chem_names', $datas->pluck('chem_names_id')->all()),
            'manufacturers' => $this->options('manufacturers', $datas->pluck('manufacturers_id')->all()),
            // Danh mục không còn cột đơn vị; danh sách này chỉ để đổ vào modal Quy Đổi Đơn Vị
            'units' => $this->options('units', []),
            'storageConditions' => $this->options('storage_conditions', $datas->pluck('storage_condition_id')->all()),
            'classifications' => config('chemical.classifications'),
            'types' => config('chemical.types'),
            'safetyWarnings' => config('chemical.safety_warnings'),
            'nextCode' => $this->previewNextCode(),
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa thay vì một nút riêng
            'historyCounts' => $this->historyCounts(),
            /*
            | Danh mục dùng chung toàn công ty, nhưng mỗi phòng ban tự khai chất nào phòng
            | mình có dùng (bảng department_chemicals). Cột "Phòng Ban Đang Dùng" đọc từ đó.
            */
            'departmentsByCategory' => DepartmentChemical::departmentsByCategory(),

            // Dữ liệu của tab Hoá Chất Của Phòng, đặt tiền tố dc để không đụng biến của tab 1
            'dcDatas' => $dcDatas,
            'dcCategories' => DepartmentChemical::categoryOptions($dcDatas->pluck('category_id')->all()),
            'dcLocations' => DepartmentChemical::locationOptions($departmentId),
            'dcStorageConditions' => DepartmentChemical::storageConditionOptions(),
            'dcUnits' => DepartmentChemical::unitOptions($dcDatas->pluck('unit_id')->all()),
            /*
            | Đơn vị các phòng KHÁC đang dùng cho từng hoá chất. Phòng đang khai chọn đơn
            | vị lệch với danh sách này thì modal bắt khai thêm hệ số quy đổi, để lúc
            | chuyển kho hệ thống đổi được số lượng qua lại.
            */
            'dcUnitsInUse' => CategoryUnitConversion::unitsInUseByCategory(
                CategoryUnitConversion::TYPE_CHEMICAL,
                $departmentId
            ),
            'dcConversions' => CategoryUnitConversion::declaredByCategory(CategoryUnitConversion::TYPE_CHEMICAL),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        $this->checkDuplicate($validator, $request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        // Sinh mã và ghi bản ghi trong cùng một transaction để hai người thêm cùng lúc không trùng mã
        $id = DB::transaction(function () use ($request) {
            return DB::table(self::TABLE)->insertGetId($this->payload($request) + [
                'code' => $this->nextCode(),
                'app_status' => 'pending',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $code = DB::table(self::TABLE)->where('id', $id)->value('code');

        $this->writeHistory($id, 'Thêm mới', 'Khai báo mới ' . self::LABEL . ', mã ' . $code . '.');

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm ' . self::LABEL . ': ' . $code);

        return redirect()->back()->with('success', 'Đã thêm ' . self::LABEL . ' mã ' . $code . '! Bản ghi đang chờ duyệt.');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        $this->checkDuplicate($validator, $request, $current->id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = $this->changeNote($current, $payload);

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            // Sửa nội dung thì phải duyệt lại từ đầu
            'app_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->writeHistory($current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.');

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $note ?: 'Không đổi', $current->code);

        return redirect()->back()->with('success', 'Cập nhật ' . self::LABEL . ' thành công! Bản ghi chuyển về chờ duyệt.');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần thay đổi trạng thái!');
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;
        $action = $newStatus == 1 ? 'Mở khoá' : 'Khoá';

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->writeHistory($current->id, $action, 'Trạng thái sử dụng: ' . ($current->status_id == 1 ? 'Hoạt động' : 'Đã khoá') . ' -> ' . ($newStatus == 1 ? 'Hoạt động' : 'Đã khoá'));

        AuditTrialController::log($action, self::TABLE, $current->id, 'status_id: ' . $current->status_id, 'status_id: ' . $newStatus);

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . self::LABEL . ' ' . $current->code . '!'
        );
    }

    public function approve(Request $request)
    {
        return $this->setApproval($request, 'approved');
    }

    public function reject(Request $request)
    {
        return $this->setApproval($request, 'rejected');
    }

    /** Trả về lịch sử thay đổi của một dòng danh mục cho modal xem lịch sử. */
    public function history(Request $request)
    {
        $classifications = config('chemical.classifications');
        $safetyWarnings = config('chemical.safety_warnings');

        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('chem_names', self::HISTORY_TABLE . '.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', self::HISTORY_TABLE . '.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', self::HISTORY_TABLE . '.unit_id', '=', 'units.id')
            ->leftJoin('storage_conditions', self::HISTORY_TABLE . '.storage_condition_id', '=', 'storage_conditions.id')
            ->select(
                self::HISTORY_TABLE . '.*',
                'chem_names.name as chem_name',
                'manufacturers.name as manufacturer_name',
                'units.name as unit_name',
                'storage_conditions.name as storage_condition_name'
            )
            ->where(self::HISTORY_TABLE . '.chemical_category_id', $request->id)
            ->orderBy(self::HISTORY_TABLE . '.id', 'desc')
            ->get();

        return response()->json([
            'rows' => $rows->map(function ($row) use ($classifications, $safetyWarnings) {
                $codes = $this->decodeCodes($row->classification);
                $warningCodes = $this->decodeCodes($row->safety_warning);

                $snapshot = [
                    'Mã danh mục' => $row->code ?: '—',
                    'Loại' => $row->type ?: '—',
                    'Tên hoá chất' => $row->chem_name ?: '—',
                    'Nhà sản xuất' => $row->manufacturer_name ?: '—',
                    'Tỉ trọng d (g/ml)' => $this->formatDensity($row->density),
                    'Hạn dùng (tháng)' => $row->shelf_life_months ?: '—',
                    'Điều kiện bảo quản' => $row->storage_condition_name ?: '—',
                    'Số tài liệu' => $row->doc_no ?: '—',
                    'Phân loại' => $codes ? implode(', ', $codes) : '—',
                    'Cảnh báo an toàn' => $warningCodes
                        ? implode(', ', array_map(fn ($code) => $safetyWarnings[$code] ?? $code, $warningCodes))
                        : '—',
                ];

                // Đơn vị tính đã chuyển sang danh mục của phòng nên bản ghi mới không còn
                // ghi cột này. Ảnh chụp cũ vẫn hiện lại để không mất vết đã thay đổi.
                if ($row->unit_name) {
                    $snapshot['Đơn vị tính (trước khi chuyển về phòng)'] = $row->unit_name;
                }

                return [
                    'action' => $row->action,
                    'change_note' => $row->change_note,
                    'created_by' => $row->created_by ?: 'NA',
                    'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                    'snapshot' => $snapshot,
                ];
            })->values(),
        ]);
    }

    /**
     * Quy đổi số lượng giữa hai đơn vị cho một hoá chất trong danh mục.
     *
     * Dùng chung App\Support\UnitConverter với các màn nhập/xuất sau này để chỉ có
     * một chỗ duy nhất định nghĩa cách quy đổi.
     */
    public function convert(Request $request)
    {
        $row = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $row) {
            return response()->json(['ok' => false, 'reason' => 'Không tìm thấy danh mục hoá chất.']);
        }

        $from = DB::table('units')->where('id', $request->from)->first();
        $to = DB::table('units')->where('id', $request->to)->first();

        if (! $from || ! $to) {
            return response()->json(['ok' => false, 'reason' => 'Đơn vị tính không hợp lệ.']);
        }

        $quantity = (float) $request->quantity;

        if ($quantity <= 0) {
            return response()->json(['ok' => false, 'reason' => 'Số lượng phải lớn hơn 0.']);
        }

        $density = $row->density !== null ? (float) $row->density : null;
        $check = UnitConverter::check($from, $to, $density);

        if (! $check['ok']) {
            return response()->json(['ok' => false, 'reason' => $check['reason']]);
        }

        $result = UnitConverter::convert($quantity, $from, $to, $density);

        if ($result === null) {
            return response()->json(['ok' => false, 'reason' => 'Không quy đổi được giữa hai đơn vị này.']);
        }

        return response()->json([
            'ok' => true,
            'result' => round($result, 6),
            'text' => $this->trimNumber($quantity) . ' ' . $from->short_name
                . ' = ' . $this->trimNumber(round($result, 6)) . ' ' . $to->short_name,
            'note' => $check['reason'],
        ]);
    }

    /** Bỏ số 0 thừa khi hiển thị số lượng. */
    private function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }

    /** Ghi nhận kết quả duyệt: ai duyệt, duyệt lúc nào. */
    private function setApproval(Request $request, string $appStatus)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần duyệt!');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => $appStatus,
            'approved_by' => $this->actor(),
            'approved_at' => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $action = $appStatus === 'approved' ? 'Phê duyệt' : 'Từ chối duyệt';

        $this->writeHistory($current->id, $action, 'Trạng thái duyệt: ' . $current->app_status . ' -> ' . $appStatus);

        AuditTrialController::log($action, self::TABLE, $current->id, 'app_status: ' . $current->app_status, 'app_status: ' . $appStatus);

        return redirect()->back()->with(
            'success',
            ($appStatus === 'approved' ? 'Đã duyệt ' : 'Đã từ chối ') . self::LABEL . ' ' . $current->code . '!'
        );
    }

    /**
     * Chụp lại giá trị bản ghi ngay sau khi thay đổi vào bảng lịch sử.
     */
    private function writeHistory(int $id, string $action, ?string $note): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'chemical_category_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'type' => $row->type,
            'chem_names_id' => $row->chem_names_id,
            'manufacturers_id' => $row->manufacturers_id,
            'density' => $row->density,
            'shelf_life_months' => $row->shelf_life_months,
            'storage_condition_id' => $row->storage_condition_id,
            'doc_no' => $row->doc_no,
            'classification' => $row->classification,
            'safety_warning' => $row->safety_warning,
            'app_status' => $row->app_status,
            'status_id' => $row->status_id,
            'change_note' => $note,
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    /** Mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới". */
    private function changeNote($current, array $payload): string
    {
        $labels = $this->labelMaps();
        $parts = [];

        foreach (self::FIELDS as $field => $title) {
            $old = (string) $current->$field;
            $new = (string) $payload[$field];

            if ($old === $new) {
                continue;
            }

            // DB trả về 1.0400 còn form gửi lên 1.04, phải so sánh theo giá trị số
            if ($field === 'density') {
                if ((float) $old === (float) $new) {
                    continue;
                }

                $parts[] = $title . ': ' . $this->formatDensity($current->$field) . ' -> ' . $this->formatDensity($payload[$field]);
                continue;
            }

            if (in_array($field, ['classification', 'safety_warning'], true)) {
                $parts[] = $title . ': '
                    . (implode(', ', $this->decodeCodes($current->$field)) ?: '—') . ' -> '
                    . (implode(', ', $this->decodeCodes($payload[$field])) ?: '—');
                continue;
            }

            if (isset($labels[$field])) {
                $parts[] = $title . ': '
                    . ($labels[$field][$current->$field] ?? '—') . ' -> '
                    . ($labels[$field][$payload[$field]] ?? '—');
                continue;
            }

            $parts[] = $title . ': ' . ($old === '' ? '—' : $old) . ' -> ' . ($new === '' ? '—' : $new);
        }

        return implode(' | ', $parts);
    }

    /** Bảng tra id -> tên của các nguồn dữ liệu gốc, dùng để viết mô tả thay đổi. */
    private function labelMaps(): array
    {
        return [
            'chem_names_id' => DB::table('chem_names')->pluck('name', 'id')->all(),
            'manufacturers_id' => DB::table('manufacturers')->pluck('name', 'id')->all(),
            'storage_condition_id' => DB::table('storage_conditions')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Mã danh mục kế tiếp theo dạng H00001: lấy số lớn nhất đang có rồi cộng 1.
     *
     * Lưu ý: màn hình này chỉ khoá bản ghi (deActive) chứ không xoá, nên mã không bị dùng lại.
     * Nếu xoá cứng bản ghi có mã lớn nhất bằng tay dưới DB thì mã đó sẽ được cấp lại.
     */
    private function nextCode(): string
    {
        $numbers = DB::table(self::TABLE)
            ->where('code', 'like', self::CODE_PREFIX . '%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr($code, strlen(self::CODE_PREFIX)));

        $next = ($numbers->max() ?? 0) + 1;

        return self::CODE_PREFIX . str_pad((string) $next, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /** Mã dự kiến hiển thị trên form thêm mới (chỉ để xem, mã thật sinh lúc lưu). */
    private function previewNextCode(): string
    {
        return $this->nextCode();
    }

    /**
     * Không cho khai báo trùng cùng một tổ hợp Tên hoá chất + Loại + Nhà sản xuất.
     * Cột type cho phép rỗng nên phải so sánh riêng trường hợp NULL.
     */
    private function checkDuplicate($validator, Request $request, $ignoreId = null): void
    {
        $validator->after(function ($validator) use ($request, $ignoreId) {
            $type = trim((string) $request->type);

            $exists = DB::table(self::TABLE)
                ->where('chem_names_id', $request->chem_names_id)
                ->where('manufacturers_id', $request->manufacturers_id)
                ->when($type === '', fn ($query) => $query->whereNull('type'))
                ->when($type !== '', fn ($query) => $query->where('type', $type))
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists();

            if ($exists) {
                $validator->errors()->add('chem_names_id', 'Tổ hợp Tên hoá chất - Loại - Nhà sản xuất này đã có trong danh mục.');
            }
        });
    }

    /** Bỏ số 0 thừa ở cuối: 1.0400 -> 1.04 */
    private function formatDensity($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $text = (string) $value;

        return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
    }

    /**
     * Số lần thay đổi của từng dòng danh mục: [chemical_category_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc khai báo chứ không phải một lần sửa. Badge trên
     * nút Sửa chỉ hiện khi dòng danh mục thật sự đã bị đổi ít nhất một lần (sửa, khoá,
     * mở khoá, duyệt, từ chối...).
     */
    private function historyCounts()
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('chemical_category_id', DB::raw('COUNT(*) as times'))
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('chemical_category_id')
            ->pluck('times', 'chemical_category_id');
    }

    /** Chuỗi JSON mảng mã (classification hoặc safety_warning) -> mảng mã. */
    private function decodeCodes($value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Chỉ cho chọn dữ liệu gốc đã duyệt và đang hoạt động.
     * Riêng những id đang được danh mục sử dụng thì vẫn giữ lại trong ô chọn
     * để màn hình cập nhật không bị mất giá trị cũ.
     */
    private function options(string $table, array $usedIds)
    {
        $usedIds = array_values(array_filter($usedIds));

        return DB::table($table)
            ->where(function ($query) use ($usedIds) {
                $query->where(function ($sub) {
                    $sub->where('status_id', 1)->where('app_status', 'approved');
                });

                if ($usedIds) {
                    $query->orWhereIn('id', $usedIds);
                }
            })
            ->orderBy('name', 'asc')
            ->get();
    }

    private function actor(): string
    {
        return session('user')['fullName'] ?? 'NA';
    }

    /** Mã danh mục sinh tự động nên không nằm trong danh sách kiểm tra. */
    private function rules(): array
    {
        return [
            'type' => ['nullable', 'max:30'],
            'chem_names_id' => ['required', 'integer', 'exists:chem_names,id'],
            'manufacturers_id' => ['required', 'integer', 'exists:manufacturers,id'],
            'density' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
            'shelf_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'storage_condition_id' => ['nullable', 'integer', 'exists:storage_conditions,id'],
            'doc_no' => ['nullable', 'max:20'],
            'classification' => ['nullable', 'array'],
            'classification.*' => [Rule::in(array_keys(config('chemical.classifications')))],
            'safety_warning' => ['nullable', 'array'],
            'safety_warning.*' => [Rule::in(array_keys(config('chemical.safety_warnings')))],
        ];
    }

    private function payload(Request $request): array
    {
        $type = trim((string) $request->type);
        $docNo = trim((string) $request->doc_no);
        $density = trim((string) $request->density);
        $shelfLife = trim((string) $request->shelf_life_months);
        $storageConditionId = trim((string) $request->storage_condition_id);

        // Giữ đúng thứ tự khai báo trong config để chuỗi JSON luôn ổn định
        $selected = (array) $request->input('classification', []);
        $codes = array_values(array_intersect(array_keys(config('chemical.classifications')), $selected));

        $selectedWarnings = (array) $request->input('safety_warning', []);
        $warningCodes = array_values(array_intersect(array_keys(config('chemical.safety_warnings')), $selectedWarnings));

        return [
            'type' => $type === '' ? null : $type,
            'chem_names_id' => (int) $request->chem_names_id,
            'manufacturers_id' => (int) $request->manufacturers_id,
            'density' => $density === '' ? null : $density,
            'shelf_life_months' => $shelfLife === '' ? null : (int) $shelfLife,
            'storage_condition_id' => $storageConditionId === '' ? null : (int) $storageConditionId,
            'doc_no' => $docNo === '' ? null : $docNo,
            'classification' => $codes ? json_encode($codes, JSON_UNESCAPED_UNICODE) : null,
            'safety_warning' => $warningCodes ? json_encode($warningCodes, JSON_UNESCAPED_UNICODE) : null,
        ];
    }

    private function messages(): array
    {
        return [
            'type.max' => 'Loại tối đa 30 ký tự.',
            'chem_names_id.required' => 'Vui lòng chọn tên hoá chất.',
            'chem_names_id.exists' => 'Tên hoá chất không hợp lệ.',
            'manufacturers_id.required' => 'Vui lòng chọn nhà sản xuất.',
            'manufacturers_id.exists' => 'Nhà sản xuất không hợp lệ.',
            'density.numeric' => 'Tỉ trọng phải là số.',
            'density.gt' => 'Tỉ trọng phải lớn hơn 0.',
            'shelf_life_months.integer' => 'Hạn dùng phải là số tháng nguyên.',
            'shelf_life_months.min' => 'Hạn dùng tối thiểu 1 tháng.',
            'shelf_life_months.max' => 'Hạn dùng tối đa 1200 tháng (100 năm).',
            'storage_condition_id.exists' => 'Điều kiện bảo quản không hợp lệ.',
            'doc_no.max' => 'Số tài liệu tối đa 20 ký tự.',
            'classification.*.in' => 'Nhóm phân loại không hợp lệ.',
            'safety_warning.*.in' => 'Cảnh báo an toàn không hợp lệ.',
        ];
    }
}
