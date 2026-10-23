<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Controller;
use App\Http\Controllers\General\NotificationController;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\ListRange;
use App\Support\MaterialCalibration;
use App\Support\MaterialCode;
use App\Support\MaterialPicking;
use App\Support\Signer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * SỬ DỤNG - ĐỀ NGHỊ HIỆU CHUẨN TRƯỚC KHI CẤP PHÁT
 *
 * Vật tư phân loại "Cần Hiệu Chuẩn Trước Khi Dùng" không cấp phát nội bộ ngay được
 * (MaterialExportController::issueStore() chặn). Quy trình - xem App\Support\MaterialCalibration:
 *
 *   1. store()   : kho chọn mã xuất nhập + số lượng của dòng đề nghị ở tab Chờ Cấp Phát, nhập Seri
 *                  No từng đơn vị rồi gửi QA hiệu chuẩn. Phần đó bị giữ chỗ tồn.
 *   2. confirm() : QA (phòng EngshortName = 'QA', quyền export_material_calibration_confirm) ký
 *                  xác nhận Đạt / Không đạt TỪNG ĐƠN VỊ của đúng số lượng + Seri No người đề nghị
 *                  đã nhập (QA không sửa số lượng / Seri No) - đạt một phần được. Không đạt -> hàng tự sang Cách ly (bước 1
 *                  quy trình loại bỏ vật tư hỏng) để phòng quyết định loại bỏ / trả về kho.
 *   3. Phần ĐẠT mới cấp được, đúng mã xuất nhập đã hiệu chuẩn, ở tab Chờ Cấp Phát như thường.
 *
 * cancel(): phòng đề nghị huỷ phiếu còn chờ QA - nhả giữ chỗ.
 *
 * Không có index riêng: tab "Hiệu chuẩn" của Sử Dụng Vật Tư, dữ liệu qua paneData().
 */
class MaterialCalibrationController extends Controller
{
    use VerifiesSignature;

    private const TABLE = MaterialCalibration::TABLE;

    private const REQ_LIST = 'material_request_lists';

    private const REQ_ITEM = 'material_request_items';

    private const QUARANTINE_TABLE = MaterialPicking::QUARANTINE_TABLE;

    /** Tiền tố tham số lọc của tab. */
    public const PREFIX = 'cal_';

    private const EPSILON = 0.00005;

    public const RESULTS = [
        'passed' => 'Đạt',
        'failed' => 'Không đạt',
    ];

    /* ==========================================================
     |  DỮ LIỆU TAB
     ========================================================== */

    /**
     * Phiếu hiệu chuẩn hiện ở tab: của chính phòng, cộng phiếu các phòng gửi tới nếu phòng đang
     * chọn là phòng QA và người dùng có quyền xác nhận. Phiếu chờ QA luôn hiện dù ngoài khoảng lọc.
     * Mỗi phần tử là MỘT PHIẾU (gom các dòng mã xuất nhập cùng code).
     */
    public static function paneData(int $departmentId, Request $request): array
    {
        $range = ListRange::of($request, self::PREFIX);
        $canConfirm = user_can(MaterialCalibration::CONFIRM_PERMISSION);
        $incomingIds = $canConfirm ? MaterialCalibration::sourceDepartmentIds($departmentId) : [];
        $departmentIds = array_values(array_unique(array_merge([$departmentId], $incomingIds)));

        $lines = DB::table(self::TABLE)
            ->leftJoin('material_imports', self::TABLE.'.import_id', '=', 'material_imports.id')
            ->leftJoin('material_categories', self::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin(self::REQ_LIST, self::TABLE.'.request_list_id', '=', self::REQ_LIST.'.id')
            ->leftJoin(self::REQ_ITEM, self::TABLE.'.request_item_id', '=', self::REQ_ITEM.'.id')
            ->leftJoin('consumption_objects', self::REQ_ITEM.'.consumption_object_id', '=', 'consumption_objects.id')
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->leftJoin(self::QUARANTINE_TABLE, self::TABLE.'.quarantine_id', '=', self::QUARANTINE_TABLE.'.id')
            ->select(
                self::TABLE.'.*',
                'material_imports.code as import_code',
                'material_imports.expired_date',
                'material_categories.code as category_code',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                self::REQ_LIST.'.code as request_code',
                self::REQ_LIST.'.type as request_type',
                self::REQ_ITEM.'.purpose',
                self::REQ_ITEM.'.requested_amount as item_requested_amount',
                self::REQ_ITEM.'.issued_amount as item_issued_amount',
                'consumption_objects.code as object_code',
                'consumption_objects.name as object_name',
                'deparments.name as department_name',
                self::QUARANTINE_TABLE.'.code as quarantine_code'
            )
            ->whereIn(self::TABLE.'.department_id', $departmentIds)
            ->where(self::TABLE.'.status_id', 1)
            // Chờ xác nhận: luôn hiện hết; đã xác nhận / huỷ: theo khoảng ngày đề nghị
            ->tap(ListRange::dateFilterKeepPending(self::TABLE.'.requested_at', $range, self::TABLE.'.app_status', ['pending']))
            ->orderByDesc(self::TABLE.'.requested_at')
            ->orderBy(self::TABLE.'.id')
            ->get();

        // Seri No + kết quả từng đơn vị (QA nhập lúc xác nhận), theo dòng kết quả
        $units = DB::table(MaterialCalibration::UNIT_TABLE)
            ->whereIn('calibration_id', $lines->pluck('id'))
            ->where('status_id', 1)
            ->orderBy('unit_no')
            ->get()
            ->groupBy('calibration_id');

        $lines->each(function ($line) use ($units) {
            $line->units = $units->get($line->id, collect())->values();
        });

        $rows = $lines->groupBy('code')->map(function ($group) use ($departmentId, $incomingIds) {
            $first = $group->first();
            // Trạng thái phiếu: còn dòng chờ QA thì là chờ; còn phần đạt chưa cấp thì là đạt - chờ cấp
            $statuses = $group->pluck('app_status')->unique();
            $status = collect(['pending', 'passed', 'failed', 'issued', 'canceled'])->first(fn ($s) => $statuses->contains($s));

            return (object) [
                'code' => $first->code,
                'department_id' => (int) $first->department_id,
                'department_name' => $first->department_name,
                'own' => (int) $first->department_id === $departmentId,
                'incoming' => in_array((int) $first->department_id, $incomingIds, true),
                'request_code' => $first->request_code,
                'request_type' => $first->request_type,
                'material_name' => $first->material_name,
                'category_code' => $first->category_code,
                'technical_specification' => $first->technical_specification,
                'object' => collect([$first->object_code, $first->object_name])->filter()->implode(' - '),
                'purpose' => $first->purpose,
                'unit' => $first->unit,
                'amount' => round((float) $group->sum('amount'), 4),
                'issued_amount' => round((float) $group->sum('issued_amount'), 4),
                'lines' => $group->values(),
                'status' => $status,
                'request_note' => $first->request_note,
                'requested_by' => $first->requested_by,
                'requested_at' => $first->requested_at,
                // Số lượng của dòng đề nghị cấp phát - QA đối chiếu khi xác nhận
                'item_requested_amount' => round((float) $first->item_requested_amount, 4),
                // Phần còn phải cấp của dòng đề nghị - so với số gửi hiệu chuẩn (được gửi dư dự phòng)
                'item_left' => round(max((float) $first->item_requested_amount - (float) $first->item_issued_amount, 0), 4),
                // Kết quả theo số lượng: phần đạt (passed + đã cấp) và phần không đạt
                'passed_amount' => round((float) $group->whereIn('app_status', ['passed', 'issued'])->sum('amount'), 4),
                'failed_amount' => round((float) $group->where('app_status', 'failed')->sum('amount'), 4),
                'result_note' => $first->result_note,
                'result_by' => $first->result_by,
                'result_at' => $first->result_at,
                'quarantine_codes' => $group->pluck('quarantine_code')->filter()->unique()->values()->all(),
            ];
        })->values();

        // Số trên tab: việc đang chờ của người xem - QA thấy phiếu các phòng gửi tới, kho thấy phiếu của phòng
        $badge = $rows->where('status', 'pending')
            ->filter(fn ($row) => $canConfirm && $row->incoming ? true : $row->own)
            ->count();

        $sub = $request->query('cal_sub') === 'done' ? 'done' : 'pending';

        return [
            // Tab con "Chờ xác nhận" / "Đã xác nhận" - cả hai mới nhất lên trước
            'pending' => $rows->where('status', 'pending')->values(),
            'done' => $rows->where('status', '<>', 'pending')->values(),
            'sub' => $sub,
            'range' => $range,
            'badge' => $badge,
            'canConfirm' => $canConfirm && $incomingIds,
        ];
    }

    /* ==========================================================
     |  BƯỚC 1 - KHO ĐỀ NGHỊ HIỆU CHUẨN
     ========================================================== */

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        if (! user_can('export_material_issue')) {
            return $this->prepBack($request, 'error', 'Bạn không có quyền cấp phát vật tư nên không đề nghị hiệu chuẩn được!');
        }

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'integer'],
            'lots' => ['required', 'array', 'min:1'],
            'lots.*.import_id' => ['nullable', 'integer'],
            'lots.*.amount' => ['nullable', 'numeric', 'min:0'],
            'request_note' => ['nullable', 'string', 'max:500'],
            'serials' => ['nullable', 'array'],
            'serials.*' => ['nullable', 'array'],
            'serials.*.*' => ['nullable', 'string', 'max:100'],
        ], [
            'serials.*.*.max' => 'Seri No tối đa 100 ký tự.',
            'lots.required' => 'Vui lòng chọn mã xuất nhập cần gửi hiệu chuẩn.',
            'lots.*.amount.numeric' => 'Số lượng phải là số.',
        ]);

        if ($validator->fails()) {
            return $this->prepBack($request, 'error', $validator->errors()->first());
        }

        $item = DB::table(self::REQ_ITEM)->where('id', $request->item_id)->where('active', 1)->first();
        $req = $item ? DB::table(self::REQ_LIST)->where('id', $item->request_list_id)->where('department_id', $departmentId)->first() : null;

        if (! $item || ! $req) {
            return $this->prepBack($request, 'error', 'Không tìm thấy mục đề nghị của phòng ban này!');
        }

        if ($req->app_status !== 'approved' || ! in_array($item->status, ['pending', 'partial', 'issued'], true)) {
            return $this->prepBack($request, 'error', 'Mục đề nghị '.$req->code.' không còn ở trạng thái chờ cấp phát!');
        }

        if (! MaterialCalibration::needs($item->category_id ? (int) $item->category_id : null)) {
            return $this->prepBack($request, 'error', 'Vật tư này không thuộc loại cần hiệu chuẩn trước khi dùng - cấp phát trực tiếp.');
        }

        $state = MaterialCalibration::statesForItems([$item->id])[$item->id] ?? null;

        if ($state && $state['pending'] > self::EPSILON) {
            return $this->prepBack($request, 'error', 'Mục này đang có phiếu '.$state['pending_code'].' chờ QA hiệu chuẩn.');
        }

        $left = round((float) $item->requested_amount - (float) $item->issued_amount, 4);
        $toRequest = round($left - ($state['passed_total'] ?? 0), 4);

        if ($toRequest <= self::EPSILON) {
            return $this->prepBack($request, 'error', 'Phần còn phải cấp của mục này đã được hiệu chuẩn đạt - cấp phát ngay ở tab Chờ Cấp Phát.');
        }

        $wanted = [];
        foreach ((array) $request->input('lots', []) as $lot) {
            $importId = (int) ($lot['import_id'] ?? 0);
            $amount = (float) ($lot['amount'] ?? 0);

            if ($importId > 0 && $amount > self::EPSILON) {
                $wanted[$importId] = round(($wanted[$importId] ?? 0) + $amount, 4);
            }
        }

        if (! $wanted) {
            return $this->prepBack($request, 'error', 'Vui lòng chọn mã xuất nhập và nhập số lượng gửi hiệu chuẩn lớn hơn 0!');
        }

        // Được gửi NHIỀU HƠN số còn phải cấp (dự phòng đơn vị không đạt) - chỉ giới hạn theo tồn
        // còn hứa được của từng lô ở dưới; phần đạt dư trả về kho khi dòng đề nghị cấp đủ.

        // Lô của đúng vật tư, của phòng, đã kiểm tra, còn hạn và còn hứa được (đã trừ phần đang giữ)
        $lots = MaterialPicking::lots($departmentId, (int) $item->category_id)->keyBy('id');

        foreach ($wanted as $importId => $amount) {
            $lot = $lots->get($importId);

            if (! $lot) {
                return $this->prepBack($request, 'error', 'Mã xuất nhập được chọn không thuộc vật tư này trong kho phòng ban!');
            }

            if ($lot->expired) {
                return $this->prepBack($request, 'error', 'Mã xuất nhập '.$lot->code.' đã hết hạn sử dụng!');
            }

            if ($amount > $lot->available + self::EPSILON) {
                return $this->prepBack($request, 'error', 'Mã xuất nhập '.$lot->code.' chỉ còn hứa được '.$this->number($lot->available).'.');
            }
        }

        /*
        | Seri No từng đơn vị do người đề nghị nhập (QA không sửa): số lượng nguyên thì đủ một Seri
        | No cho mỗi đơn vị, bắt buộc, không trùng trong phiếu; số lượng lẻ thì một Seri No (nếu có)
        | cho cả dòng - xem MaterialCalibration::unitCount().
        */
        $units = [];
        $seen = [];

        foreach ($wanted as $importId => $amount) {
            $count = MaterialCalibration::unitCount($amount);
            $serials = array_values(array_map(
                fn ($serial) => $this->nullIfBlank($serial),
                (array) $request->input('serials.'.$importId, [])
            ));

            for ($i = 0; $i < max($count, 1); $i++) {
                $serial = $serials[$i] ?? null;

                if ($count && ! $serial) {
                    return $this->prepBack($request, 'error', 'Vui lòng nhập đủ '.$count.' Seri No cho mã xuất nhập '.$lots->get($importId)->code.'.');
                }

                if ($serial !== null) {
                    $key = mb_strtolower($serial);

                    if (isset($seen[$key])) {
                        return $this->prepBack($request, 'error', 'Seri No "'.$serial.'" bị nhập trùng trong phiếu.');
                    }

                    $seen[$key] = true;
                }

                $units[$importId][] = [
                    'unit_no' => $count ? $i + 1 : 0,
                    'amount' => $count ? 1 : $amount,
                    'serial_no' => $serial,
                ];
            }
        }

        $actor = $this->actor();
        $now = now();
        $unit = $item->issued_unit ?: $item->requested_unit;
        $note = $this->nullIfBlank($request->request_note);

        $code = DB::transaction(function () use ($wanted, $units, $departmentId, $req, $item, $unit, $note, $actor, $now) {
            $code = MaterialCalibration::nextCode($departmentId);

            foreach ($wanted as $importId => $amount) {
                $calibrationId = DB::table(self::TABLE)->insertGetId([
                    'code' => $code,
                    'department_id' => $departmentId,
                    'request_list_id' => $req->id,
                    'request_item_id' => $item->id,
                    'category_id' => $item->category_id,
                    'import_id' => $importId,
                    'amount' => $amount,
                    'issued_amount' => 0,
                    'unit' => $unit,
                    'app_status' => 'pending',
                    'request_note' => $note,
                    'requested_by' => $actor,
                    'requested_at' => $now,
                    'status_id' => 1,
                    'created_by' => $actor,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Từng đơn vị + Seri No, kết quả để trống chờ QA
                foreach ($units[$importId] as $unitRow) {
                    DB::table(MaterialCalibration::UNIT_TABLE)->insert($unitRow + [
                        'calibration_id' => $calibrationId,
                        'code' => $code,
                        'import_id' => $importId,
                        'result' => null,
                        'status_id' => 1,
                        'created_by' => $actor,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            return $code;
        });

        $codes = $lots->only(array_keys($wanted))->pluck('code')->implode(', ');
        $total = array_sum($wanted);

        AuditTrialController::log(
            'Đề nghị hiệu chuẩn vật tư',
            self::TABLE,
            $item->id,
            'NA',
            'Phiếu '.$code.' cho đề nghị '.$req->code.': '.$this->number($total).' '.($unit ?: '').' từ mã xuất nhập '.$codes
        );

        $this->notifyQa($departmentId, $code, $req->code, $lots->first()->material_name ?? '', $total, $unit, $codes);

        return $this->prepBack($request, 'success', 'Đã gửi QA hiệu chuẩn '.$this->number($total).' '.($unit ?: '')
            .' (phiếu '.$code.'). Phần này được giữ chỗ, QA xác nhận đạt mới cấp phát được.');
    }

    /* ==========================================================
     |  BƯỚC 2 - QA XÁC NHẬN KẾT QUẢ
     ========================================================== */

    /**
     * QA xác nhận Đạt / Không đạt TỪNG ĐƠN VỊ của phiếu: số lượng và Seri No do người đề nghị đã
     * nhập lúc gửi (material_calibration_units), QA không sửa được - chỉ gửi kết quả theo id đơn vị:
     *
     *   units[<id đơn vị>] = passed | failed
     *
     * Kết quả mỗi dòng: đạt hết -> passed; không đạt hết -> failed + Cách ly; đạt một phần -> dòng
     * giữ phần đạt, phần không đạt tách thành dòng failed cùng mã phiếu + Cách ly. Có đơn vị không
     * đạt thì bắt buộc ghi lý do.
     */
    public function confirm(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string'],
            'units' => ['required', 'array', 'min:1'],
            'units.*' => ['required', 'in:'.implode(',', array_keys(self::RESULTS))],
            'result_note' => ['nullable', 'string', 'max:500'],
        ], [
            'units.required' => 'Vui lòng chọn Đạt / Không đạt cho từng đơn vị.',
            'units.*.required' => 'Vui lòng chọn Đạt / Không đạt cho từng đơn vị.',
        ]);

        if ($validator->fails()) {
            return $this->tabBack('error', $validator->errors()->first());
        }

        if (! user_can(MaterialCalibration::CONFIRM_PERMISSION)) {
            return $this->tabBack('error', 'Bạn không có quyền xác nhận hiệu chuẩn vật tư!');
        }

        $rows = DB::table(self::TABLE)
            ->leftJoin('material_imports', self::TABLE.'.import_id', '=', 'material_imports.id')
            ->select(self::TABLE.'.*', 'material_imports.code as import_code')
            ->where(self::TABLE.'.code', $request->code)
            ->where(self::TABLE.'.status_id', 1)
            ->where(self::TABLE.'.app_status', 'pending')
            ->orderBy(self::TABLE.'.id')
            ->get();

        if ($rows->isEmpty()) {
            return $this->tabBack('error', 'Phiếu '.$request->code.' không còn chờ QA hiệu chuẩn, vui lòng tải lại trang.');
        }

        $sourceDepartmentId = (int) $rows->first()->department_id;

        if (! in_array($sourceDepartmentId, MaterialCalibration::sourceDepartmentIds($departmentId), true)) {
            return $this->tabBack('error', 'Phòng QA đang chọn không phụ trách hiệu chuẩn cho phòng đề nghị phiếu này.');
        }

        // Đơn vị do người đề nghị khai - mọi đơn vị phải có kết quả
        $unitsByRow = DB::table(MaterialCalibration::UNIT_TABLE)
            ->whereIn('calibration_id', $rows->pluck('id'))
            ->where('status_id', 1)
            ->orderBy('unit_no')
            ->get()
            ->groupBy('calibration_id');

        $input = (array) $request->input('units', []);
        $results = [];

        foreach ($rows as $row) {
            $units = $unitsByRow->get($row->id, collect());

            if ($units->isEmpty() || $units->contains(fn ($u) => ! isset($input[$u->id]))) {
                return $this->tabBack('error', 'Vui lòng chọn Đạt / Không đạt cho từng đơn vị (tải lại trang nếu phiếu đã thay đổi).');
            }

            $passed = round((float) $units->filter(fn ($u) => $input[$u->id] === 'passed')->sum('amount'), 4);
            $passed = min($passed, (float) $row->amount);

            $results[$row->id] = [
                'units' => $units,
                'passed' => $passed,
                'failed' => round(max((float) $row->amount - $passed, 0), 4),
            ];
        }

        $totalPassed = round(array_sum(array_column($results, 'passed')), 4);
        $totalFailed = round(array_sum(array_column($results, 'failed')), 4);
        $note = $this->nullIfBlank($request->result_note);
        $unit = $rows->first()->unit ?: '';

        if ($totalFailed > self::EPSILON && ! $note) {
            return $this->tabBack('error', 'Có '.$this->number($totalFailed).' '.$unit.' không đạt - vui lòng ghi rõ lý do không đạt.');
        }

        $action = 'Xác nhận hiệu chuẩn: đạt '.$this->number($totalPassed).', không đạt '.$this->number($totalFailed);

        if ($denied = $this->guardSignature($request, self::TABLE, $rows->first()->id, $action)) {
            return $denied->with('activeTab', 'calibration');
        }

        $actor = $this->actor();
        $now = now();
        $code = (string) $request->code;

        DB::transaction(function () use ($rows, $results, $input, $actor, $now, $note, $code) {
            foreach ($rows as $row) {
                $passed = $results[$row->id]['passed'];
                $failed = $results[$row->id]['failed'];
                $result = ['result_note' => $note, 'result_by' => $actor, 'result_at' => $now, 'updated_by' => $actor, 'updated_at' => $now];

                // Phần không đạt: sang Cách ly chờ quyết định loại bỏ / trả về kho
                $quarantineId = $failed > self::EPSILON
                    ? DB::table(self::QUARANTINE_TABLE)->insertGetId([
                        'code' => $this->nextQuarantineCode((int) $row->department_id),
                        'department_id' => $row->department_id,
                        'import_id' => $row->import_id,
                        'amount' => $failed,
                        'reason' => mb_substr('Không đạt hiệu chuẩn (phiếu '.$code.'): '.$note, 0, 500),
                        'app_status' => MaterialPicking::QUARANTINE_HOLDING_STATUS,
                        'status_id' => 1,
                        'created_by' => $actor,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    : null;

                // Hàng không đạt dời sang khu Chờ Quyết Định: trừ tồn ngay như phiếu cách ly lập tay
                if ($quarantineId) {
                    MaterialQuarantineController::deductStock((int) $quarantineId, $actor);
                }

                $failedId = $row->id;

                if ($passed <= self::EPSILON) {
                    DB::table(self::TABLE)->where('id', $row->id)->update($result + ['app_status' => 'failed', 'quarantine_id' => $quarantineId]);
                } else {
                    // Dòng giữ phần đạt; đạt một phần thì tách phần không đạt thành dòng riêng cùng mã phiếu
                    DB::table(self::TABLE)->where('id', $row->id)->update($result + ['app_status' => 'passed', 'amount' => $passed]);

                    if ($quarantineId) {
                        $failedRow = (array) $row;
                        unset($failedRow['id'], $failedRow['import_code']);

                        $failedId = DB::table(self::TABLE)->insertGetId(array_merge($failedRow, $result, [
                            'amount' => $failed,
                            'app_status' => 'failed',
                            'quarantine_id' => $quarantineId,
                            'created_by' => $actor,
                            'created_at' => $now,
                        ]));
                    }
                }

                // Kết quả từng đơn vị; đơn vị không đạt chuyển sang đúng dòng failed
                foreach ($results[$row->id]['units'] as $unitRow) {
                    $isPassed = $input[$unitRow->id] === 'passed';

                    DB::table(MaterialCalibration::UNIT_TABLE)->where('id', $unitRow->id)->update([
                        'result' => $input[$unitRow->id],
                        'calibration_id' => $isPassed ? $row->id : $failedId,
                        'result_by' => $actor,
                        'result_at' => $now,
                        'updated_by' => $actor,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        AuditTrialController::log(
            'Xác nhận hiệu chuẩn vật tư',
            self::TABLE,
            $rows->first()->request_item_id,
            'pending',
            'Phiếu '.$code.': '.$rows->map(function ($row) use ($results, $input) {
                $detail = $results[$row->id]['units']
                    ->map(fn ($u) => ($u->serial_no ?: '#'.$u->unit_no).' '.($input[$u->id] === 'passed' ? 'đạt' : 'không đạt'))
                    ->implode(', ');

                return $row->import_code.' ['.$detail.']';
            })->implode('; ').($note ? ', ghi chú: '.$note : '')
        );

        $reqCode = DB::table(self::REQ_LIST)->where('id', $rows->first()->request_list_id)->value('code');
        $summary = 'đạt '.$this->number($totalPassed).' '.$unit.($totalFailed > self::EPSILON ? ', không đạt '.$this->number($totalFailed).' '.$unit : '');

        $this->notifyDepartment(
            $sourceDepartmentId,
            'QA xác nhận hiệu chuẩn phiếu '.$code.' cho đề nghị '.$reqCode.': '.$summary.'.'
                .($totalPassed > self::EPSILON ? ' Kho cấp phát được phần đạt.' : '')
                .($totalFailed > self::EPSILON ? ' Phần không đạt đã chuyển sang Cách ly ('.$note.').' : ''),
            $totalPassed > self::EPSILON ? 'prepare' : 'quarantine'
        );

        return $this->tabBack('success', 'Đã ký xác nhận hiệu chuẩn phiếu '.$code.': '.$summary
            .($totalFailed > self::EPSILON ? ' - phần không đạt đã chuyển sang Cách ly của phòng đề nghị.' : '.'));
    }

    /* ==========================================================
     |  HUỶ PHIẾU CHỜ QA
     ========================================================== */

    public function cancel(Request $request)
    {
        $departmentId = $this->departmentId();

        if (! user_can('export_material_issue')) {
            return $this->tabBack('error', 'Bạn không có quyền huỷ đề nghị hiệu chuẩn!');
        }

        $affected = DB::table(self::TABLE)
            ->where('code', (string) $request->code)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->where('app_status', 'pending')
            ->update([
                'app_status' => 'canceled',
                'result_note' => $this->nullIfBlank($request->cancel_reason) ?? 'Phòng đề nghị huỷ',
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

        if (! $affected) {
            return $this->tabBack('error', 'Phiếu không còn chờ QA hiệu chuẩn, vui lòng tải lại trang.');
        }

        AuditTrialController::log('Huỷ đề nghị hiệu chuẩn vật tư', self::TABLE, 0, 'pending', 'canceled: phiếu '.$request->code);

        return $this->tabBack('success', 'Đã huỷ phiếu '.$request->code.', hàng giữ chỗ đã được nhả về kho.');
    }

    /* ==========================================================
     |  HỖ TRỢ
     ========================================================== */

    /** Báo cho người có quyền xác nhận ở các phòng QA phụ trách phòng đề nghị. */
    private function notifyQa(int $departmentId, string $code, string $reqCode, string $material, float $amount, ?string $unit, string $lotCodes): void
    {
        $userIds = [];

        foreach (MaterialCalibration::qaDepartmentIdsFor($departmentId) as $qaDepartmentId) {
            $userIds = array_merge($userIds, users_with_permission(MaterialCalibration::CONFIRM_PERMISSION, $qaDepartmentId));
        }

        $userIds = array_values(array_unique($userIds));

        if (! $userIds) {
            return;
        }

        NotificationController::sendNotification(
            'Phòng '.$this->departmentName($departmentId).' đề nghị hiệu chuẩn '.$material.': '.$this->number($amount).' '.($unit ?: '')
                .' (mã xuất nhập '.$lotCodes.', phiếu '.$code.') trước khi cấp phát cho đề nghị '.$reqCode.'.',
            'Đề nghị hiệu chuẩn vật tư',
            null,
            $userIds,
            [],
            route('pages.export.materialExport.list', ['tab' => 'calibration'])
        );
    }

    /** Báo kết quả cho người cấp phát của phòng đề nghị. */
    private function notifyDepartment(int $departmentId, string $message, string $tab): void
    {
        $userIds = users_with_permission('export_material_issue', $departmentId);

        if (! $userIds) {
            return;
        }

        NotificationController::sendNotification(
            $message,
            'Kết quả hiệu chuẩn vật tư',
            null,
            $userIds,
            [],
            route('pages.export.materialExport.list', ['tab' => $tab])
        );
    }

    /** Quay về tab Chờ Cấp Phát giữ nguyên bộ lọc ngày mong muốn. */
    private function prepBack(Request $request, string $type, string $message)
    {
        return redirect()->route('pages.export.materialExport.list', ['tab' => 'prepare'] + array_filter([
            'prep_from' => $request->input('prep_from'),
            'prep_to' => $request->input('prep_to'),
        ]))->with($type, $message);
    }

    private function tabBack(string $type, string $message)
    {
        return redirect()->back()->with($type, $message)->with('activeTab', 'calibration');
    }

    /** CL-07-... - cùng mẫu mã phiếu cách ly của MaterialQuarantineController. */
    private function nextQuarantineCode(int $departmentId): string
    {
        $prefix = 'CL'.MaterialCode::SEP
            .str_pad((string) $departmentId, MaterialCode::DEPT_LENGTH, '0', STR_PAD_LEFT).MaterialCode::SEP;

        do {
            $code = $prefix.MaterialCode::randomTail();
        } while (DB::table(self::QUARANTINE_TABLE)->where('code', $code)->exists());

        return $code;
    }

    private function departmentName($id): string
    {
        return (string) (DB::table('deparments')->where('id', $id)->value('name') ?: '—');
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return Signer::actor();
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }

    private function nullIfBlank($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' || $value === null ? null : (string) $value;
    }
}
