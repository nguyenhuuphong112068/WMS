<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DataMasterHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DỮ LIỆU GỐC - ĐỐI TƯỢNG
 *
 * Đối tượng tiêu thụ vật tư - dùng để khảo sát lượng vật tư mà một đối tượng tiêu thụ. Đối tượng
 * không nhất thiết là thiết bị; loại đối tượng khai báo trong TYPES.
 *
 * Nguồn dữ liệu (source):
 * - cal    : đồng bộ từ phần mềm CAL - DB Bảo trì của PMS (cal1 = khối B1, cal2 = khối B2;
 *            config/database.php, biến DB_CAL1_* / DB_CAL2_* trong .env). Loại + mã là khoá nhận diện
 *            nên người dùng không sửa được; tên / vị trí / tần suất lần đồng bộ sau ghi lại theo CAL.
 * - manual : người dùng tự thêm trên màn hình.
 *
 * Liên kết WMS <-> CAL (chỉ có ở source = cal):
 * - cal_connection + cal_table_suffix : DB và cặp bảng Inst_Master_{x} / Schedule_Master_{x}
 * - cal_record_id                     : Inst_Master_{x}.ID của thiết bị lớn - khoá liên kết chính
 * - cal_inst_id                       : Inst_Master_{x}.Inst_id của thiết bị lớn
 * - cal_synced_at                     : lần đồng bộ gần nhất còn thấy đối tượng trên CAL
 *
 * Tạo đề nghị cấp phát theo lịch CAL (về sau): trên kết nối cal_connection, lấy lịch
 * Schedule_Master_{cal_table_suffix} có Sch_Result_Status = 'Pending' và Inst_ID thuộc
 * (cal_inst_id + Inst_id các thiết bị con có Parent_Equip_id = cal_inst_id). Mỗi (Inst_ID, Sch_Type)
 * có tối đa một lịch Pending; SCH_ID là IDENTITY nên (cal_connection, cal_table_suffix, SCH_ID)
 * dùng làm khoá chống tạo đề nghị trùng.
 *
 * Tần suất lưu mã gốc của CAL (Monthly, Quaterly...) để đồng bộ khớp 1-1, nhiều tần suất ghép
 * bằng dấu phẩy theo thứ tự FREQUENCIES.
 */
class ConsumptionObjectController extends Controller
{
    use RequiresChangeReason;

    private const TABLE = 'consumption_objects';
    private const LABEL = 'đối tượng';

    public const SOURCE_CAL = 'cal';
    public const SOURCE_MANUAL = 'manual';

    /** Nguồn dữ liệu => nhãn hiển thị. */
    public const SOURCES = [
        self::SOURCE_CAL => 'Đồng bộ CAL',
        self::SOURCE_MANUAL => 'Người dùng thêm',
    ];

    /**
     * Loại đối tượng => nhãn + hậu tố bảng bên CAL (Inst_Master_x / Schedule_Master_x).
     * Loại không lấy từ CAL thì bỏ 'cal_suffix' - khai báo tay trên màn hình.
     */
    public const TYPES = [
        'production_equipment' => ['label' => 'Thiết bị sản xuất', 'cal_suffix' => 2],
        'testing_equipment' => ['label' => 'Thiết bị kiểm nghiệm', 'cal_suffix' => 4],
    ];

    /** Kết nối CAL => khối. Mã có ở cả hai khối thì lấy theo khối đứng trước. */
    public const CAL_CONNECTIONS = ['cal1' => 'B1', 'cal2' => 'B2'];
    private const CAL_ACTIVE = 'Active';
    private const CAL_PENDING = 'Pending';

    /** Tần suất: mã gốc CAL (Sch_Type / Inst_sch_type) => nhãn hiển thị, xếp từ ngắn đến dài. */
    public const FREQUENCIES = [
        'Daily' => 'Hằng ngày',
        'Alternate Day' => '2 ngày/lần',
        'Weekly' => 'Hằng tuần',
        'Fortnightly' => '2 tuần/lần',
        'Monthly' => 'Hằng tháng',
        'Bi Monthly' => '2 tháng/lần',
        'Quaterly' => 'Hằng quý',
        'Half Yearly' => '6 tháng/lần',
        'Yearly' => 'Hằng năm',
        'Two Yearly' => '2 năm/lần',
        'Three Yearly' => '3 năm/lần',
        'Five Yearly' => '5 năm/lần',
        'Seven Yearly' => '7 năm/lần',
    ];

    /** Các cột hiện trong ảnh chụp và mô tả thay đổi của lịch sử. */
    private const FIELDS = [
        'source' => 'Nguồn dữ liệu',
        'type' => 'Loại đối tượng',
        'code' => 'Mã đối tượng',
        'name' => 'Tên đối tượng',
        'location' => 'Vị trí',
        'frequency' => 'Tần suất',
        'cal_inst_id' => 'Mã thiết bị bên CAL',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)
            ->orderBy('type', 'asc')
            ->orderBy('code', 'asc')
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - ĐỐI TƯỢNG']);

        return view('pages.materData.ConsumptionObject.list', [
            'datas' => $datas,
            'types' => self::typeLabels(),
            'sources' => self::SOURCES,
            'calBlocks' => self::CAL_CONNECTIONS,
            'frequencies' => self::FREQUENCIES,
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules($request), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $payload = $this->payload($request);

        $id = DB::table(self::TABLE)->insertGetId($payload + [
            'source' => self::SOURCE_MANUAL,
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            self::TABLE,
            $id,
            'Thêm mới',
            'Khai báo mới ' . self::LABEL . ': ' . $payload['code'] . ' - ' . $payload['name'] . '.',
            self::FIELDS,
            $this->maps($payload['frequency'])
        );

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm ' . self::LABEL . ': ' . $payload['code']);

        return redirect()->back()->with('success', 'Đã thêm ' . self::LABEL . ' thành công!');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        // Đối tượng đồng bộ từ CAL: loại + mã là khoá nhận diện, luôn giữ nguyên giá trị đang có
        if ($current->source === self::SOURCE_CAL) {
            $request->merge(['type' => $current->type, 'code' => $current->code]);
        }

        $validator = Validator::make(
            $request->all(),
            $this->rules($request, $current->id) + $this->changeReasonRules(),
            $this->messages() + $this->changeReasonMessages()
        );

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $maps = $this->maps($current->frequency, $payload['frequency']);
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload, $maps);

        if ($note === '') {
            return redirect()->back()->with('error', 'Chưa có thông tin nào thay đổi nên không lưu.')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note, self::FIELDS, $maps, $this->changeReason($request));

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $note, $payload['code']);

        return redirect()->back()->with('success', 'Cập nhật ' . self::LABEL . ' thành công!');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần thay đổi trạng thái!');
        }

        if ($stop = $this->guardChangeReason($request)) {
            return $stop;
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            self::TABLE,
            $current->id,
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            DataMasterHistory::statusNote($current->status_id, $newStatus),
            self::FIELDS,
            $this->maps($current->frequency),
            $this->changeReason($request)
        );

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            self::TABLE,
            $current->id,
            'status_id: ' . $current->status_id,
            'status_id: ' . $newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . self::LABEL . ' ' . $current->code . '!'
        );
    }

    /** Trả về lịch sử thay đổi của một dòng cho modal xem lịch sử. */
    public function history(Request $request)
    {
        return response()->json([
            'rows' => DataMasterHistory::rows(self::TABLE, (int) $request->id),
        ]);
    }

    /**
     * Đồng bộ đối tượng thẳng từ phần mềm CAL, lần lượt từng loại có 'cal_suffix' và từng khối (cal1, cal2):
     *
     * - Chỉ lấy instrument đang Active (Inst_Status).
     * - Thiết bị lớn = dòng có Parent_Equip_id rỗng hoặc trùng chính Inst_id => một đối tượng.
     * - Tần suất = các loại lịch (Sch_Type) đang có lịch Pending của thiết bị lớn và toàn bộ thiết bị
     *   con (PMS gom thiết bị con cùng thiết bị lớn thành một khối khi sắp lịch). Chưa có lịch Pending
     *   nào thì lấy tần suất khai ở Inst_Master (Inst_sch_type).
     * - Nhận diện đối tượng đã có theo liên kết (cal_connection, cal_table_suffix, cal_record_id);
     *   chưa có liên kết thì theo (loại, mã) - dòng người dùng thêm trùng mã được nhận làm dòng CAL.
     * - Mã chưa có: thêm mới. Đã có: chỉ cập nhật khi khác; giá trị rỗng không ghi đè.
     * - Dòng không đổi gì vẫn được ghi cal_synced_at để biết còn thấy trên CAL.
     * - Kết nối nào lỗi thì bỏ qua, dữ liệu đã có của phần đó giữ nguyên.
     */
    public function sync()
    {
        $groups = [];
        $errors = [];
        $readCount = 0;

        foreach (self::TYPES as $type => $meta) {
            if (empty($meta['cal_suffix'])) {
                continue;
            }

            $suffix = (int) $meta['cal_suffix'];

            foreach (self::CAL_CONNECTIONS as $connection => $block) {
                try {
                    $instruments = DB::connection($connection)->table('Inst_Master_' . $suffix)
                        ->select(
                            'ID as record_id',
                            'Inst_id as inst_id',
                            'Inst_Name as inst_name',
                            'Parent_Equip_id as parent_id',
                            'Inst_Installed_Location as location',
                            'Inst_sch_type as sch_type'
                        )
                        ->where('Inst_Status', self::CAL_ACTIVE)
                        ->orderBy('ID', 'asc')
                        ->get();

                    // Gộp ngay trên SQL Server - bảng lịch giữ toàn bộ lịch sử (hàng chục nghìn dòng)
                    $pending = DB::connection($connection)->table('Schedule_Master_' . $suffix)
                        ->selectRaw('LTRIM(RTRIM(Inst_ID)) as inst_id')
                        ->selectRaw('LTRIM(RTRIM(Sch_Type)) as sch_type')
                        ->where('Sch_Result_Status', self::CAL_PENDING)
                        ->groupByRaw('LTRIM(RTRIM(Inst_ID)), LTRIM(RTRIM(Sch_Type))')
                        ->get();
                } catch (\Throwable $e) {
                    $errors[] = $connection . ' - ' . $meta['label'] . ': ' . $this->shortError($e);
                    continue;
                }

                $readCount += $instruments->count();

                // Loại lịch đang còn áp dụng của từng instrument: [INST_ID] => [Sch_Type, ...]
                $scheduled = [];

                foreach ($pending as $schedule) {
                    $scheduled[mb_strtoupper((string) $schedule->inst_id)][] = $schedule->sch_type;
                }

                $local = [];

                foreach ($instruments as $row) {
                    $instId = trim((string) $row->inst_id);

                    if ($instId === '') {
                        continue;
                    }

                    $parent = trim((string) $row->parent_id) ?: $instId;
                    $key = $type . '|' . mb_strtoupper($parent);
                    $local[$key] ??= [
                        'type' => $type, 'connection' => $connection, 'block' => $block, 'suffix' => $suffix,
                        'record_id' => null, 'code' => $parent, 'name' => '', 'location' => '',
                        'frequencies' => [], 'fallback' => [],
                    ];

                    if (strcasecmp($parent, $instId) === 0 && $local[$key]['record_id'] === null) {
                        $local[$key]['record_id'] = (int) $row->record_id;
                        $local[$key]['code'] = $instId;
                        $local[$key]['name'] = trim((string) $row->inst_name);
                        $local[$key]['location'] = trim((string) $row->location);
                    }

                    foreach ($scheduled[mb_strtoupper($instId)] ?? [] as $schType) {
                        if ($frequency = $this->normalizeFrequency($schType)) {
                            $local[$key]['frequencies'][$frequency] = true;
                        }
                    }

                    if ($frequency = $this->normalizeFrequency($row->sch_type)) {
                        $local[$key]['fallback'][$frequency] = true;
                    }
                }

                foreach ($local as $key => $group) {
                    // Thiết bị lớn không có dòng riêng (hoặc đã In-Active) thì không có ID / tên - bỏ qua
                    if (isset($groups[$key]) || $group['record_id'] === null || $group['name'] === '') {
                        continue;
                    }

                    if (! $group['frequencies']) {
                        $group['frequencies'] = $group['fallback'];
                    }

                    $groups[$key] = $group;
                }
            }
        }

        $readNote = 'Đọc ' . number_format($readCount) . ' instrument từ CAL.';
        $errorNote = $errors ? ' Lỗi kết nối: ' . implode(' | ', $errors) : '';

        if (empty($groups)) {
            return redirect()->back()->with('error', 'Không có đối tượng nào để đồng bộ. ' . $readNote . $errorNote);
        }

        $now = now();
        $byLink = [];
        $byCode = [];

        foreach (DB::table(self::TABLE)->get() as $row) {
            if ($row->source === self::SOURCE_CAL && $row->cal_record_id !== null) {
                $byLink[$this->linkKey($row->cal_connection, $row->cal_table_suffix, $row->cal_record_id)] = $row;
            }

            $byCode[$row->type . '|' . mb_strtoupper($row->code)] = $row;
        }

        $inserted = 0;
        $updated = 0;
        $linked = 0;
        $keptCodes = [];
        $untouched = [];

        foreach ($groups as $key => $group) {
            $link = [
                'source' => self::SOURCE_CAL,
                'cal_connection' => $group['connection'],
                'cal_table_suffix' => $group['suffix'],
                'cal_record_id' => $group['record_id'],
                'cal_inst_id' => mb_substr($group['code'], 0, 50),
            ];
            $data = [
                'code' => mb_substr($group['code'], 0, 50),
                'name' => mb_substr($group['name'], 0, 255),
                'location' => $group['location'] !== '' ? mb_substr($group['location'], 0, 255) : null,
                'frequency' => $this->joinFrequencies(array_keys($group['frequencies'])),
            ];
            $reason = 'Đồng bộ từ phần mềm CAL (khối ' . $group['block'] . ')';

            $current = $byLink[$this->linkKey($group['connection'], $group['suffix'], $group['record_id'])] ?? null;

            if (! $current) {
                $sameCode = $byCode[$key] ?? null;

                // Mã đã gắn với một thiết bị CAL khác (VD lần này khối B1 lỗi, khối B2 có cùng mã) - không gắn lại
                if ($sameCode && $sameCode->source === self::SOURCE_CAL) {
                    continue;
                }

                // null => thêm mới; dòng người dùng thêm trùng (loại, mã) => nhận làm dòng CAL
                $current = $sameCode;
            }

            if (! $current) {
                $id = DB::table(self::TABLE)->insertGetId(['type' => $group['type']] + $data + $link + [
                    'cal_synced_at' => $now,
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', $reason . '.', self::FIELDS, $this->maps($data['frequency']));
                $inserted++;

                continue;
            }

            // CAL đổi Inst_id của thiết bị (cùng ID) sang mã đã có đối tượng khác cùng loại dùng - giữ mã cũ
            if (strcasecmp($current->code, $data['code']) !== 0) {
                $holder = $byCode[$current->type . '|' . mb_strtoupper($data['code'])] ?? null;

                if ($holder && $holder->id !== $current->id) {
                    $keptCodes[] = $current->code;
                    $data['code'] = $current->code;
                }
            }

            // Không để giá trị rỗng bên CAL xoá dữ liệu đang có
            $payload = array_filter($data, fn ($value) => $value !== null) + $link;
            $maps = $this->maps($current->frequency, $payload['frequency'] ?? null);
            $note = DataMasterHistory::note(self::FIELDS, $current, $payload, $maps);
            $linkChanged = collect($link)->contains(fn ($value, $column) => (string) ($current->$column ?? '') !== (string) $value);

            if ($note === '' && ! $linkChanged) {
                $untouched[] = $current->id;

                continue;
            }

            DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
                'cal_synced_at' => $now,
                'updated_by' => $this->actor(),
                'updated_at' => $now,
            ]);

            $wasManual = $current->source !== self::SOURCE_CAL;

            if ($note !== '') {
                DataMasterHistory::record(self::TABLE, $current->id, $wasManual ? 'Liên kết CAL' : 'Đồng bộ', $note, self::FIELDS, $maps, $reason);
            }

            $wasManual ? $linked++ : $updated++;
        }

        // Dòng không đổi gì: chỉ ghi nhận vẫn còn thấy trên CAL
        foreach (array_chunk($untouched, 500) as $ids) {
            DB::table(self::TABLE)->whereIn('id', $ids)->update(['cal_synced_at' => $now]);
        }

        $message = $readNote . ' Đối tượng: thêm mới ' . $inserted . ', cập nhật ' . $updated
            . ($linked ? ', liên kết CAL ' . $linked . ' đối tượng người dùng thêm' : '') . '.'
            . ($keptCodes ? ' Giữ mã cũ vì mã mới trên CAL đã có đối tượng khác dùng: ' . implode(', ', array_slice($keptCodes, 0, 10)) . '.' : '');

        AuditTrialController::log('Đồng bộ', self::TABLE, 0, 'NA', $message);

        if ($errors) {
            return redirect()->back()->with('error', $message . $errorNote);
        }

        return redirect()->back()->with('success', $message);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    /** Loại đối tượng => nhãn, dùng cho select / bộ lọc / lịch sử. */
    public static function typeLabels(): array
    {
        return array_map(fn ($meta) => $meta['label'], self::TYPES);
    }

    /** Khoá liên kết một thiết bị lớn bên CAL: "cal1|2|1234". */
    private function linkKey(?string $connection, $suffix, $recordId): string
    {
        return $connection . '|' . (int) $suffix . '|' . (int) $recordId;
    }

    /** Đưa cách viết lệch của CAL về mã chuẩn: "Half-Yearly" => "Half Yearly", "quaterly" => "Quaterly". */
    private function normalizeFrequency(?string $value): ?string
    {
        $value = mb_strtolower(trim(preg_replace('/[\s\-_]+/', ' ', (string) $value)));

        foreach (array_keys(self::FREQUENCIES) as $code) {
            if (mb_strtolower($code) === $value) {
                return $code;
            }
        }

        return null;
    }

    /** Ghép các mã tần suất theo đúng thứ tự FREQUENCIES để so sánh thay đổi ổn định. */
    private function joinFrequencies(array $codes): ?string
    {
        $codes = array_values(array_intersect(array_keys(self::FREQUENCIES), $codes));

        return $codes ? implode(',', $codes) : null;
    }

    /** Nhãn đọc được của một chuỗi tần suất đã lưu: "Monthly,Quaterly" => "Hằng tháng, Hằng quý". */
    public static function frequencyLabel(?string $value): string
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($code) => self::FREQUENCIES[trim($code)] ?? null)
            ->filter()
            ->implode(', ');
    }

    /** Bảng nhãn cho DataMasterHistory để lịch sử hiện tiếng Việt thay vì mã lưu trong DB. */
    private function maps(?string ...$frequencies): array
    {
        $map = [];

        foreach ($frequencies as $value) {
            if ($value !== null && $value !== '') {
                $map[$value] = self::frequencyLabel($value);
            }
        }

        return ['source' => self::SOURCES, 'type' => self::typeLabels(), 'frequency' => $map];
    }

    /** Lỗi kết nối rút gọn - bỏ phần câu SQL dài mà Laravel nối vào. */
    private function shortError(\Throwable $e): string
    {
        $message = preg_replace('/\s*\(Connection:.*$/s', '', $e->getMessage());

        return mb_substr(trim($message), 0, 200);
    }

    private function rules(Request $request, $ignoreId = null): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'code' => [
                'required',
                'max:50',
                Rule::unique(self::TABLE, 'code')->where('type', (string) $request->type)->ignore($ignoreId),
            ],
            'name' => ['required', 'max:255'],
            'location' => ['nullable', 'max:255'],
            'frequency' => ['required', 'array', 'min:1'],
            'frequency.*' => [Rule::in(array_keys(self::FREQUENCIES))],
        ];
    }

    private function payload(Request $request): array
    {
        $location = trim((string) $request->location);

        return [
            'type' => (string) $request->type,
            'code' => trim((string) $request->code),
            'name' => trim((string) $request->name),
            'location' => $location !== '' ? $location : null,
            'frequency' => $this->joinFrequencies((array) $request->frequency),
        ];
    }

    private function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại đối tượng.',
            'type.in' => 'Loại đối tượng không hợp lệ.',
            'code.required' => 'Vui lòng nhập mã đối tượng.',
            'code.max' => 'Mã đối tượng tối đa 50 ký tự.',
            'code.unique' => 'Mã đối tượng này đã tồn tại trong loại đối tượng đã chọn.',
            'name.required' => 'Vui lòng nhập tên đối tượng.',
            'name.max' => 'Tên đối tượng tối đa 255 ký tự.',
            'location.max' => 'Vị trí tối đa 255 ký tự.',
            'frequency.required' => 'Vui lòng chọn ít nhất một tần suất.',
            'frequency.min' => 'Vui lòng chọn ít nhất một tần suất.',
            'frequency.*.in' => 'Tần suất không hợp lệ.',
        ];
    }
}
