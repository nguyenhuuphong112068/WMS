<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\ChemicalCode;
use App\Support\DepartmentStandard;
use App\Support\ListRange;
use App\Support\ZoneType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * SỬ DỤNG - CHẤT CHUẨN HỎNG (3 BƯỚC, cùng khuôn ChemicalQuarantineController)
 *
 * Cách ly bấm từ nút [Cách ly] trên từng ống ở Tồn Kho Chất Chuẩn (tab "Theo mã ống chuẩn" và
 * "Hạn dùng sắp hết"); các bước sau làm ở tab "Cách ly - Loại bỏ - Huỷ" của Sử Dụng Chất Chuẩn, dữ
 * liệu do StandardExportController::index() nạp qua paneData().
 *
 *   BƯỚC 1 - CÁCH LY  (quarantined): dời ống sang định khu "Chờ Quyết Định" -> TRỪ TỒN NGAY bằng
 *                     standard_exports type = cancel (quarantine_id trỏ về phiếu cách ly, xem
 *                     deductStock()). Quyết định "Trả về kho" (returned) thì đưa số lượng dòng xuất
 *                     đó về 0 - tồn quay lại. standard_exports không có cột status_id nên không khoá
 *                     dòng như hoá chất được; số lượng cũ vẫn nằm ở lịch sử dòng xuất.
 *   BƯỚC 2 - LOẠI BỎ  (removed)    : giữ nguyên dòng xuất, ống dời sang định khu "Loại Bỏ/Chờ Huỷ".
 *                     Không quay lại kho được.
 *   BƯỚC 3 - HUỶ      (destroyed)  : ghi nhận đã huỷ thực tế (phương pháp, ghi chú). Không động tồn.
 *
 * Quyết định (bước 2) và huỷ (bước 3) là thao tác ký xác nhận: nhập lại mật khẩu - 21 CFR Part 11.
 */
class StandardQuarantineController extends Controller
{
    use VerifiesSignature;

    public const TABLE = 'standard_quarantines';

    private const EXPORT_TABLE = 'standard_exports';

    private const EXPORT_HISTORY_TABLE = 'standard_export_histories';

    private const LABEL = 'phiếu cách ly chất chuẩn hỏng';

    /** Tiền tố tham số lọc / phân trang của tab. */
    public const PREFIX = 'qr_';

    /** Tiền tố mã phiếu cách ly: CLCC-<id phòng>-<đuôi ngẫu nhiên>. */
    private const CODE_KIND = 'CLCC';

    private const EPSILON = 0.00005;

    public const STATUSES = [
        'quarantined' => 'Cách ly - Chờ quyết định',
        'removed' => 'Loại bỏ - Chờ huỷ',
        'destroyed' => 'Đã huỷ',
        'returned' => 'Đã trả về kho',
    ];

    /** Mở tab không chỉ rõ nhóm thì vào danh sách đang cách ly - việc chờ quyết định. */
    public const DEFAULT_STATUS = 'quarantined';

    /** Phiếu CHƯA XONG - bộ lọc khoảng ngày luôn giữ lại dù đã ngoài khoảng lọc. */
    private const PENDING_STATUSES = ['quarantined', 'removed'];

    /** Định khu ống đang cách ly (bước 1) và ống đã loại bỏ chờ huỷ (bước 2) - App\Support\ZoneType. */
    public const PENDING_ZONE = 'pending';

    public const REJECTED_ZONE = 'rejected';

    /** Trả về kho: được đặt vào mọi định khu chất chuẩn của phòng, trừ khu Chờ Quyết Định / Loại Bỏ. */
    private const NOT_RETURN_ZONES = [self::PENDING_ZONE, self::REJECTED_ZONE];

    public const DECISIONS = [
        'remove' => 'Loại bỏ',
        'return' => 'Trả về kho',
    ];

    /** Tab của Tồn Kho Chất Chuẩn được quay lại sau khi cách ly. */
    private const INVENTORY_TABS = ['code', 'expiring'];

    /* ==========================================================
     |  DỮ LIỆU TAB
     ========================================================== */

    public static function paneData(int $departmentId, Request $request): array
    {
        $range = ListRange::of($request, self::PREFIX);
        $keyword = ListRange::keyword($request, self::PREFIX);
        $perPage = ListRange::perPage($request, self::PREFIX);
        $status = array_key_exists((string) $request->input('qstatus'), self::STATUSES)
            ? (string) $request->input('qstatus')
            : self::DEFAULT_STATUS;

        $rows = DB::table(self::TABLE)
            ->leftJoin('standard_imports', self::TABLE.'.import_id', '=', 'standard_imports.id')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('locations', 'standard_imports.location_id', '=', 'locations.id')
            ->leftJoin('locations as pending_locations', self::TABLE.'.pending_location_id', '=', 'pending_locations.id')
            ->leftJoin('locations as rejected_locations', self::TABLE.'.rejected_location_id', '=', 'rejected_locations.id')
            ->leftJoin('locations as returned_locations', self::TABLE.'.returned_location_id', '=', 'returned_locations.id')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_imports.category_id'))
            ->select(
                self::TABLE.'.*',
                'standard_imports.code as import_code',
                'standard_imports.batch_no',
                'standard_imports.group_code',
                'standard_imports.expired_date',
                'standard_imports.internal_expired_date',
                'standard_imports.category_id',
                'standard_categories.code as category_code',
                'standard_categories.groups',
                'standard_names.name as standard_name',
                'locations.code as location_code',
                'locations.zone_type as location_zone_type',
                'pending_locations.code as pending_location_code',
                'pending_locations.zone_type as pending_location_zone_type',
                'rejected_locations.code as rejected_location_code',
                'rejected_locations.zone_type as rejected_location_zone_type',
                'returned_locations.code as returned_location_code',
                'returned_locations.zone_type as returned_location_zone_type',
                'standard_imports.location_id as lot_location_id',
                'units.short_name as unit_short_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->where(self::TABLE.'.app_status', $status)
            ->tap(ListRange::dateFilterKeepPending(self::TABLE.'.created_at', $range, self::TABLE.'.app_status', self::PENDING_STATUSES))
            ->tap(ListRange::search([
                self::TABLE.'.code',
                'standard_imports.code',
                'standard_imports.batch_no',
                'standard_categories.code',
                'standard_names.name',
                self::TABLE.'.reason',
            ], $keyword))
            ->orderByDesc(self::TABLE.'.created_at')
            ->orderByDesc(self::TABLE.'.id')
            ->paginate($perPage, ['*'], ListRange::pageName(self::PREFIX))
            ->withQueryString();

        $counts = DB::table(self::TABLE)
            ->select('app_status', DB::raw('COUNT(*) as total'))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->whereIn('app_status', self::PENDING_STATUSES)
            ->groupBy('app_status')
            ->pluck('total', 'app_status');

        return [
            'rows' => $rows,
            'range' => $range,
            'keyword' => $keyword,
            'perPage' => $perPage,
            'status' => $status,
            'counts' => $counts,
            'badge' => (int) $counts->sum(),
            'pendingLocations' => self::zoneLocations($departmentId, self::PENDING_ZONE),
            'rejectedLocations' => self::zoneLocations($departmentId, self::REJECTED_ZONE),
            'returnLocations' => self::returnLocations($departmentId),
        ];
    }

    /* ==========================================================
     |  BƯỚC 1 - CÁCH LY (từ Tồn Kho Chất Chuẩn)
     ========================================================== */

    /**
     * Cách ly một hoặc nhiều ống (items[][import_id|amount], chung một lý do + định khu).
     *
     * Mỗi ống là MỘT phiếu cách ly riêng - quyết định loại bỏ / trả về kho và huỷ ở bước sau đi
     * theo từng phiếu. Cả đợt ghi trong một transaction: một ống sai thì không ghi ống nào.
     */
    public function store(Request $request)
    {
        if (! user_can('export_standard_quarantine')) {
            return redirect()->back()->with('error', 'Bạn không có quyền cách ly chất chuẩn hỏng.');
        }

        $departmentId = $this->departmentId();
        $returnTab = in_array($request->input('return_tab'), self::INVENTORY_TABS, true) ? $request->input('return_tab') : 'code';

        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.import_id' => ['required', 'integer'],
            'items.*.amount' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'string', 'max:500'],
            'pending_location_id' => ['required', 'integer'],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->storeFailed($validator, $returnTab);
        }

        $reason = trim((string) $request->reason);
        $locationId = (int) $request->pending_location_id;

        try {
            $created = DB::transaction(function () use ($request, $departmentId, $reason, $locationId) {
                // import_id => tổng số cách ly (cùng một ống chọn hai lần thì cộng dồn)
                $picked = [];

                foreach ($request->items as $item) {
                    $importId = (int) $item['import_id'];
                    $picked[$importId] = round(($picked[$importId] ?? 0) + (float) $item['amount'], 4);
                }

                $check = Validator::make([], []);
                $this->checkZoneLocation($check, 'pending_location_id', $locationId, self::PENDING_ZONE);

                $lots = [];

                foreach ($picked as $importId => $amount) {
                    // Khoá ống suốt lượt ghi: chặn cùng lúc người khác xuất / cách ly vượt tồn
                    $lot = self::lot($importId, $departmentId, true);

                    if (! $lot) {
                        $check->errors()->add('items', 'Không tìm thấy ống chuẩn đã chọn trong kho phòng ban này (hoặc ống còn chờ kiểm tra).');

                        continue;
                    }

                    if ($amount > $lot->remaining + self::EPSILON) {
                        $check->errors()->add(
                            'items',
                            'Ống chuẩn '.$lot->code.' chỉ còn tồn '.$this->number($lot->remaining).' '.$lot->unit_short_name
                            .', không cách ly vượt số này được.'
                        );

                        continue;
                    }

                    $lots[$importId] = $lot;
                }

                if ($check->errors()->any()) {
                    throw new \Illuminate\Validation\ValidationException($check);
                }

                $locationCode = (string) DB::table('locations')->where('id', $locationId)->value('code');
                $created = [];

                foreach ($lots as $importId => $lot) {
                    $code = $this->nextCode($departmentId);
                    $amount = $picked[$importId];

                    $id = DB::table(self::TABLE)->insertGetId([
                        'code' => $code,
                        'department_id' => $departmentId,
                        'import_id' => (int) $lot->id,
                        'amount' => $amount,
                        'reason' => $reason,
                        'pending_location_id' => $locationId,
                        'app_status' => 'quarantined',
                        'status_id' => 1,
                        'created_by' => $this->actor(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    self::deductStock($id, $this->actor());

                    AuditTrialController::log(
                        'Cách ly chất chuẩn hỏng',
                        self::TABLE,
                        $id,
                        'NA',
                        'Phiếu '.$code.', mã ống chuẩn: '.$lot->code.', số lượng: '.$this->number($amount)
                            .', định khu chờ quyết định: '.$locationCode.', lý do: '.$reason
                    );

                    $created[] = ['lot' => $lot, 'amount' => $amount, 'code' => $code];
                }

                return $created;
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->storeFailed($e->validator, $returnTab);
        }

        $message = count($created) === 1
            ? 'Đã cách ly '.$this->number($created[0]['amount']).' '.$created[0]['lot']->unit_short_name
                .' của ống chuẩn '.$created[0]['lot']->code.' (phiếu '.$created[0]['code'].').'
            : 'Đã cách ly '.count($created).' ống chuẩn: '.collect($created)
                ->map(fn ($row) => $row['lot']->code.' ('.$this->number($row['amount']).' '.$row['lot']->unit_short_name.' - phiếu '.$row['code'].')')
                ->implode(', ').'.';

        return redirect()->back()
            ->with('success', $message.' Đã trừ tồn; quyết định loại bỏ / trả về kho ở Sử Dụng Chất Chuẩn → tab Cách ly - Loại bỏ - Huỷ.')
            ->with('activeTab', $returnTab);
    }

    /** Sửa số lượng / lý do / định khu khi phiếu còn đang cách ly. */
    public function update(Request $request)
    {
        if (! user_can('export_standard_quarantine')) {
            return $this->fail('Bạn không có quyền sửa phiếu cách ly chất chuẩn.');
        }

        $current = $this->find($request->id);

        if (! $current || $current->app_status !== 'quarantined') {
            return $this->fail('Chỉ sửa được '.self::LABEL.' đang cách ly!');
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'string', 'max:500'],
            'pending_location_id' => ['required', 'integer'],
        ], $this->messages());

        $validator->after(function ($v) use ($request, $current) {
            $this->checkZoneLocation($v, 'pending_location_id', $request->pending_location_id, self::PENDING_ZONE);

            $lot = self::lot((int) $current->import_id, $this->departmentId());
            // Phần của chính phiếu này đã trừ khỏi tồn - cộng lại trước khi so
            $limit = ($lot ? $lot->remaining : 0) + ($current->export_id ? (float) $current->amount : 0);

            if (is_numeric($request->amount) && (float) $request->amount > $limit + self::EPSILON) {
                $v->errors()->add('amount', 'Ống chuẩn chỉ còn '.$this->number($limit).' có thể cách ly.');
            }
        });

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $amount = round((float) $request->amount, 4);
        $reason = trim((string) $request->reason);
        $locationId = (int) $request->pending_location_id;
        $changes = [];

        if (abs($amount - (float) $current->amount) > self::EPSILON) {
            $changes[] = 'Số lượng: '.$this->number((float) $current->amount).' -> '.$this->number($amount);
        }

        if ($reason !== (string) $current->reason) {
            $changes[] = 'Lý do: '.$current->reason.' -> '.$reason;
        }

        if ($locationId !== (int) $current->pending_location_id) {
            $codes = DB::table('locations')->whereIn('id', [$locationId, (int) $current->pending_location_id])->pluck('code', 'id');
            $changes[] = 'Định khu chờ quyết định: '.($codes[$current->pending_location_id] ?? '—').' -> '.($codes[$locationId] ?? '—');
        }

        if (! $changes) {
            return $this->fail('Không có thông tin nào thay đổi.');
        }

        DB::transaction(function () use ($current, $amount, $reason, $locationId) {
            DB::table(self::TABLE)->where('id', $current->id)->update([
                'amount' => $amount,
                'reason' => $reason,
                'pending_location_id' => $locationId,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Dòng xuất trừ tồn đi theo số lượng / lý do của phiếu cách ly
            if ($current->export_id) {
                DB::table(self::EXPORT_TABLE)->where('id', $current->export_id)->update([
                    'amount' => $amount,
                    'reason' => mb_substr('Cách ly (phiếu '.$current->code.'): '.$reason, 0, 500),
                    'updated_by' => $this->actor(),
                    'updated_at' => now(),
                ]);

                self::writeExportHistory((int) $current->export_id, 'Cập nhật', 'Sửa phiếu cách ly '.$current->code, $this->actor());
            } else {
                self::deductStock((int) $current->id, $this->actor());
            }
        });

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, implode('; ', $changes));

        return redirect()->back()->with('success', 'Đã cập nhật phiếu cách ly '.$current->code.'!')->with('activeTab', 'quarantine');
    }

    /* ==========================================================
     |  BƯỚC 2 - QUYẾT ĐỊNH: LOẠI BỎ / TRẢ VỀ KHO
     ========================================================== */

    public function decide(Request $request)
    {
        if (! user_can('export_standard_quarantine_decide')) {
            return $this->fail('Bạn không có quyền quyết định loại bỏ / trả về kho chất chuẩn cách ly.');
        }

        $current = $this->find($request->id);

        if (! $current || $current->app_status !== 'quarantined') {
            return $this->fail('Phiếu cách ly không còn ở trạng thái chờ quyết định!');
        }

        $validator = Validator::make($request->all(), [
            'decision' => ['required', 'in:'.implode(',', array_keys(self::DECISIONS))],
            'decision_note' => ['required', 'string', 'max:500'],
            'rejected_location_id' => ['required_if:decision,remove', 'nullable', 'integer'],
            'returned_location_id' => ['required_if:decision,return', 'nullable', 'integer'],
        ], [
            'returned_location_id.required_if' => 'Vui lòng chọn định khu đặt ống chuẩn trả về kho.',
            'returned_location_id.integer' => 'Định khu trả về kho không hợp lệ.',
            'rejected_location_id.required_if' => 'Vui lòng chọn định khu Loại Bỏ/Chờ Huỷ cho ống chuẩn bị loại bỏ.',
            'rejected_location_id.integer' => 'Định khu Loại Bỏ/Chờ Huỷ không hợp lệ.',
            'decision.required' => 'Vui lòng chọn quyết định.',
            'decision.in' => 'Quyết định không hợp lệ.',
            'decision_note.required' => 'Vui lòng nhập nội dung / căn cứ quyết định.',
            'decision_note.max' => 'Nội dung quyết định tối đa 500 ký tự.',
        ]);

        $validator->after(function ($v) use ($request, $current) {
            if ($request->decision === 'remove' && $request->filled('rejected_location_id')) {
                $this->checkZoneLocation($v, 'rejected_location_id', $request->rejected_location_id, self::REJECTED_ZONE);
            }

            if ($request->decision === 'return' && is_numeric($request->returned_location_id)) {
                $this->checkReturnLocation($v, $current, (int) $request->returned_location_id);
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'quarantineDecideErrors')
                ->withInput()
                ->with('activeTab', 'quarantine');
        }

        $label = self::DECISIONS[$request->decision];

        if ($denied = $this->guardSignature($request, self::TABLE, $current->id, 'Quyết định '.mb_strtolower($label).' chất chuẩn cách ly')) {
            return $denied->with('activeTab', 'quarantine');
        }

        $note = trim((string) $request->decision_note);
        $actor = $this->actor();
        $at = now();

        if ($request->decision === 'return') {
            $returnedLocationId = (int) $request->returned_location_id;
            $import = DB::table('standard_imports')->where('id', $current->import_id)->first();
            $oldLocationId = (int) ($import->location_id ?? 0);
            $codes = DB::table('locations')->whereIn('id', [$returnedLocationId, $oldLocationId])->pluck('code', 'id');

            $done = DB::transaction(function () use ($current, $import, $note, $actor, $at, $returnedLocationId, $oldLocationId) {
                $claimed = DB::table(self::TABLE)
                    ->where('id', $current->id)
                    ->where('app_status', 'quarantined')
                    ->update([
                        'app_status' => 'returned',
                        'decision_note' => $note,
                        'returned_location_id' => $returnedLocationId,
                        'decided_by' => $actor,
                        'decided_at' => $at,
                        'updated_by' => $actor,
                        'updated_at' => $at,
                    ]);

                if (! $claimed) {
                    return false;
                }

                // Hoàn tồn: standard_exports không có status_id -> đưa số lượng dòng xuất về 0,
                // số đã cách ly vẫn giữ trên phiếu cách ly và trong lịch sử dòng xuất
                if ($current->export_id) {
                    DB::table(self::EXPORT_TABLE)->where('id', $current->export_id)->update([
                        'amount' => 0,
                        'reason' => mb_substr('Trả về kho (phiếu cách ly '.$current->code.'): '.$note, 0, 500),
                        'updated_by' => $actor,
                        'updated_at' => $at,
                    ]);

                    self::writeExportHistory(
                        (int) $current->export_id,
                        'Trả về kho',
                        'Trả về kho theo quyết định phiếu cách ly '.$current->code.' - số lượng '
                            .rtrim(rtrim(number_format((float) $current->amount, 4, '.', ','), '0'), '.').' -> 0: '.$note,
                        $actor
                    );
                }

                // Cách ly cả ống (checkReturnLocation đã chặn một phần ống đổi vị trí): ống dời theo vị trí mới
                if ($import && $returnedLocationId !== $oldLocationId) {
                    DB::table('standard_imports')->where('id', $import->id)->update([
                        'location_id' => $returnedLocationId,
                        'updated_by' => $actor,
                        'updated_at' => $at,
                    ]);
                }

                return true;
            });

            if (! $done) {
                return $this->fail('Phiếu cách ly không còn ở trạng thái chờ quyết định!');
            }

            $moved = $returnedLocationId !== $oldLocationId;

            AuditTrialController::log(
                'Trả chất chuẩn cách ly về kho',
                self::TABLE,
                $current->id,
                'app_status: quarantined',
                'app_status: returned; định khu trả về: '.($codes[$returnedLocationId] ?? '—')
                    .($moved ? ' (vị trí ống: '.($codes[$oldLocationId] ?? '—').' -> '.($codes[$returnedLocationId] ?? '—').')' : '')
                    .'; '.$note
            );

            return redirect()->back()
                ->with('success', 'Đã trả '.$this->number((float) $current->amount).' chất chuẩn của phiếu '.$current->code
                    .' về kho tại '.($codes[$returnedLocationId] ?? '—').', ống dùng lại được.')
                ->with('activeTab', 'quarantine');
        }

        $rejectedLocationId = (int) $request->rejected_location_id;

        $done = DB::transaction(function () use ($current, $note, $actor, $at, $rejectedLocationId) {
            // Chốt trạng thái trước: hai người bấm quyết định cùng lúc thì chỉ một người thắng
            $claimed = DB::table(self::TABLE)
                ->where('id', $current->id)
                ->where('app_status', 'quarantined')
                ->update(['app_status' => 'removed', 'updated_at' => $at]);

            if (! $claimed) {
                return false;
            }

            $exportId = $current->export_id ? (int) $current->export_id : self::deductStock((int) $current->id, $actor);

            DB::table(self::EXPORT_TABLE)->where('id', $exportId)->update([
                'reason' => mb_substr('Loại bỏ (phiếu '.$current->code.'): '.$current->reason.' | Quyết định: '.$note, 0, 500),
                'updated_by' => $actor,
                'updated_at' => $at,
            ]);

            self::writeExportHistory($exportId, 'Loại bỏ', 'Quyết định loại bỏ phiếu cách ly '.$current->code, $actor);

            DB::table(self::TABLE)->where('id', $current->id)->update([
                'decision_note' => $note,
                'rejected_location_id' => $rejectedLocationId,
                'decided_by' => $actor,
                'decided_at' => $at,
                'export_id' => $exportId,
                'updated_by' => $actor,
                'updated_at' => $at,
            ]);

            return true;
        });

        if (! $done) {
            return $this->fail('Phiếu cách ly '.$current->code.' vừa được người khác quyết định, vui lòng tải lại trang.');
        }

        AuditTrialController::log(
            'Loại bỏ chất chuẩn hỏng',
            self::TABLE,
            $current->id,
            'app_status: quarantined',
            'app_status: removed; định khu loại bỏ: '.DB::table('locations')->where('id', $rejectedLocationId)->value('code').'; '.$note
        );

        return redirect()->back()
            ->with('success', 'Đã loại bỏ '.$this->number((float) $current->amount).' chất chuẩn của phiếu '.$current->code.' (không quay lại kho được). Chờ ghi nhận huỷ.')
            ->with('activeTab', 'quarantine');
    }

    /* ==========================================================
     |  BƯỚC 3 - HUỶ (một hoặc nhiều phiếu đã loại bỏ)
     ========================================================== */

    public function dispose(Request $request)
    {
        if (! user_can('export_standard_quarantine')) {
            return $this->fail('Bạn không có quyền ghi nhận huỷ chất chuẩn.');
        }

        $ids = collect((array) $request->input('ids'))->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $validator = Validator::make($request->all(), [
            'destroy_method' => ['required', 'string', 'max:255'],
            'destroy_note' => ['nullable', 'string', 'max:500'],
        ], [
            'destroy_method.required' => 'Vui lòng nhập phương pháp huỷ.',
            'destroy_method.max' => 'Phương pháp huỷ tối đa 255 ký tự.',
            'destroy_note.max' => 'Ghi chú tối đa 500 ký tự.',
        ]);

        if ($ids->isEmpty()) {
            $validator->after(fn ($v) => $v->errors()->add('ids', 'Vui lòng chọn ít nhất một phiếu đã loại bỏ để huỷ.'));
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'quarantineDisposeErrors')
                ->withInput()
                ->with('activeTab', 'quarantine');
        }

        $rows = DB::table(self::TABLE)
            ->whereIn('id', $ids)
            ->where('department_id', $this->departmentId())
            ->where('status_id', 1)
            ->where('app_status', 'removed')
            ->get();

        if ($rows->count() !== $ids->count()) {
            return $this->fail('Có phiếu không còn ở trạng thái "'.self::STATUSES['removed'].'", vui lòng tải lại trang.');
        }

        if ($denied = $this->guardSignature($request, self::TABLE, $ids->first(), 'Ghi nhận huỷ chất chuẩn')) {
            return $denied->with('activeTab', 'quarantine');
        }

        $actor = $this->actor();
        $at = now();
        $method = trim((string) $request->destroy_method);
        $note = $this->nullIfBlank($request->destroy_note);

        DB::table(self::TABLE)->whereIn('id', $rows->pluck('id'))->update([
            'app_status' => 'destroyed',
            'destroy_method' => $method,
            'destroy_note' => $note,
            'destroyed_by' => $actor,
            'destroyed_at' => $at,
            'updated_by' => $actor,
            'updated_at' => $at,
        ]);

        foreach ($rows as $row) {
            AuditTrialController::log('Huỷ chất chuẩn', self::TABLE, $row->id, 'app_status: removed', 'app_status: destroyed; phương pháp: '.$method.($note ? '; '.$note : ''));
        }

        return redirect()->back()
            ->with('success', 'Đã ghi nhận huỷ '.$rows->count().' phiếu: '.$rows->pluck('code')->implode(', ').'.')
            ->with('activeTab', 'quarantine');
    }

    /* ==========================================================
     |  HỖ TRỢ
     ========================================================== */

    /**
     * Một ống của phòng kèm tồn sổ sách hiện tại (nhập + cân đối - mọi phiếu xuất, kể cả phần
     * đang cách ly). Ống "Chờ kiểm tra" chưa vào tồn nên không cách ly được.
     * $lock = true: khoá dòng phiếu nhập (chỉ có tác dụng trong DB::transaction()).
     */
    public static function lot(int $importId, int $departmentId, bool $lock = false)
    {
        if ($lock) {
            DB::table('standard_imports')->where('id', $importId)->lockForUpdate()->first();
        }

        $lot = DB::table('standard_imports')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_imports.category_id'))
            ->select('standard_imports.id', 'standard_imports.code', 'standard_imports.amount', 'standard_imports.location_id', 'units.short_name as unit_short_name')
            ->where('standard_imports.id', $importId)
            ->where('standard_imports.department_id', $departmentId)
            ->where('standard_imports.status_id', 1)
            ->where('standard_imports.is_checked', 1)
            ->first();

        if ($lot) {
            $lot->remaining = self::remaining($lot);
        }

        return $lot;
    }

    /** Tồn sổ sách của một ống = nhập + cân đối - đã xuất (giống StandardExportController::remaining). */
    private static function remaining($import): float
    {
        $exported = (float) DB::table(self::EXPORT_TABLE)->where('import_id', $import->id)->sum('amount');
        $balanced = (float) DB::table('standard_balancings')->where('import_id', $import->id)->where('status_id', 1)->sum('balancing_amount');

        return max((float) $import->amount + $balanced - $exported, 0);
    }

    /**
     * Tồn HIỆN TẠI của mọi ống trong phòng: [import_id => tồn] - nút [Cách ly] ở Tồn Kho Chất Chuẩn
     * lấy số này (không cắt theo kỳ đang xem) vì cách ly trừ vào tồn hôm nay.
     */
    public static function remainingMap(int $departmentId): array
    {
        $exported = DB::table(self::EXPORT_TABLE)
            ->select('import_id', DB::raw('SUM(amount) as total'))
            ->where('department_id', $departmentId)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');

        $balanced = DB::table('standard_balancings')
            ->select('import_id', DB::raw('SUM(balancing_amount) as total'))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');

        return DB::table('standard_imports')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->where('is_checked', 1)
            ->pluck('amount', 'id')
            ->map(fn ($amount, $id) => max((float) $amount + (float) ($balanced[$id] ?? 0) - (float) ($exported[$id] ?? 0), 0))
            ->all();
    }

    /** Vị trí định khu chất chuẩn của phòng ban thuộc đúng một phân loại (pending / rejected), dùng cho ô chọn ở modal. */
    public static function zoneLocations(int $departmentId, string $zoneType)
    {
        return DepartmentStandard::locationOptions($departmentId)
            ->where('zone_type', $zoneType)
            ->values();
    }

    /** Định khu được chọn khi trả về kho: định khu chất chuẩn của phòng, trừ khu Chờ Quyết Định / Loại Bỏ. */
    public static function returnLocations(int $departmentId)
    {
        return DepartmentStandard::locationOptions($departmentId)
            ->reject(fn ($loc) => in_array($loc->zone_type, self::NOT_RETURN_ZONES, true))
            ->values();
    }

    /**
     * Vị trí trả về hợp lệ: thuộc returnLocations(), riêng vị trí cũ của ống luôn hợp lệ.
     * Ống chỉ có MỘT vị trí - phiếu cách ly một phần ống thì phần còn lại vẫn ở vị trí cũ,
     * nên chỉ trả về đúng vị trí đó; cách ly cả ống mới được đổi vị trí.
     */
    private function checkReturnLocation($validator, $current, int $locationId): void
    {
        $import = DB::table('standard_imports')->where('id', $current->import_id)->first();
        $oldLocationId = (int) ($import->location_id ?? 0);

        if ($locationId === $oldLocationId) {
            return;
        }

        if (! self::returnLocations($this->departmentId())->contains('id', $locationId)) {
            $validator->errors()->add('returned_location_id', 'Vị trí đã chọn không phải định khu chất chuẩn dùng được của phòng ban này.');

            return;
        }

        // Tồn hiện tại đã trừ phần đang cách ly: còn tồn tức là phần còn lại của ống vẫn nằm ở vị trí cũ
        $rest = $import ? self::remaining($import) - ($current->export_id ? 0 : (float) $current->amount) : 0;

        if ($import && $rest > self::EPSILON) {
            $validator->errors()->add(
                'returned_location_id',
                'Phiếu chỉ cách ly một phần ống '.$import->code.', phần còn lại vẫn ở vị trí '
                .(DB::table('locations')->where('id', $oldLocationId)->value('code') ?? '—').', chỉ trả về được đúng vị trí này.'
            );
        }
    }

    /** Vị trí được chọn phải là định khu chất chuẩn của phòng ban đang chọn và đúng phân loại yêu cầu. */
    private function checkZoneLocation($validator, string $field, $locationId, string $zoneType): void
    {
        if (! is_numeric($locationId)) {
            return;
        }

        if (! self::zoneLocations($this->departmentId(), $zoneType)->contains('id', (int) $locationId)) {
            $validator->errors()->add($field, 'Vị trí đã chọn không phải định khu "'.ZoneType::label($zoneType).'" của phòng ban này.');
        }
    }

    private function storeFailed($validator, string $returnTab)
    {
        return redirect()->back()
            ->withErrors($validator, 'quarantineErrors')
            ->withInput()
            ->with('activeTab', $returnTab);
    }

    private function find($id)
    {
        return DB::table(self::TABLE)
            ->where('id', (int) $id)
            ->where('department_id', $this->departmentId())
            ->where('status_id', 1)
            ->first();
    }

    private function fail(string $message)
    {
        return redirect()->back()->with('error', $message)->with('activeTab', 'quarantine');
    }

    /**
     * TRỪ TỒN phần cách ly của một phiếu: sinh standard_exports type = cancel gắn quarantine_id
     * và ghi export_id lên phiếu. Trả về id dòng xuất.
     */
    public static function deductStock(int $quarantineId, string $actor): int
    {
        $quarantine = DB::table(self::TABLE)->where('id', $quarantineId)->first();
        $importCode = DB::table('standard_imports')->where('id', $quarantine->import_id)->value('code');
        $at = now();

        $exportId = DB::table(self::EXPORT_TABLE)->insertGetId([
            'code' => $importCode,
            'import_id' => (int) $quarantine->import_id,
            'department_id' => (int) $quarantine->department_id,
            'quarantine_id' => (int) $quarantine->id,
            'amount' => (float) $quarantine->amount,
            'type' => 'cancel',
            'reason' => mb_substr('Cách ly (phiếu '.$quarantine->code.'): '.$quarantine->reason, 0, 500),
            'created_by' => $actor,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        DB::table(self::TABLE)->where('id', $quarantine->id)->update(['export_id' => $exportId]);

        self::writeExportHistory($exportId, 'Thêm mới', 'Cách ly chất chuẩn hỏng - phiếu '.$quarantine->code, $actor);

        return $exportId;
    }

    /** Ghi lịch sử dòng xuất của phiếu cách ly vào sổ sử dụng - cùng khuôn StandardExportController::logHistory. */
    private static function writeExportHistory(int $exportId, string $action, ?string $note, string $actor): void
    {
        $row = DB::table(self::EXPORT_TABLE)->where('id', $exportId)->first();

        DB::table(self::EXPORT_HISTORY_TABLE)->insert([
            'standard_export_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'import_id' => $row->import_id,
            'amount' => $row->amount,
            'type' => $row->type,
            'product_name' => $row->product_name,
            'batch_no' => $row->batch_no,
            'testing' => $row->testing,
            'reason' => $row->reason,
            'change_note' => $note,
            'created_by' => $actor,
            'created_at' => now(),
        ]);
    }

    /** CLCC-07-7KPMR9J4WD - cùng cách sinh đuôi ngẫu nhiên với phiếu cách ly hoá chất. */
    private function nextCode(int $departmentId): string
    {
        $prefix = self::CODE_KIND.ChemicalCode::SEP
            .str_pad((string) $departmentId, ChemicalCode::DEPT_LENGTH, '0', STR_PAD_LEFT).ChemicalCode::SEP;

        do {
            $code = $prefix.ChemicalCode::randomTail();
        } while (DB::table(self::TABLE)->where('code', $code)->exists());

        return $code;
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function messages(): array
    {
        return [
            'items.required' => 'Vui lòng chọn ít nhất một ống chuẩn cần cách ly.',
            'items.min' => 'Vui lòng chọn ít nhất một ống chuẩn cần cách ly.',
            'items.*.import_id.required' => 'Có dòng chưa chọn ống chuẩn.',
            'items.*.import_id.integer' => 'Ống chuẩn được chọn không hợp lệ.',
            'items.*.amount.required' => 'Vui lòng nhập số lượng cách ly.',
            'items.*.amount.numeric' => 'Số lượng cách ly phải là số.',
            'items.*.amount.min' => 'Số lượng cách ly phải lớn hơn 0.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'reason.required' => 'Vui lòng nhập lý do cách ly (hỏng / hết hạn...).',
            'reason.max' => 'Lý do tối đa 500 ký tự.',
            'pending_location_id.required' => 'Vui lòng chọn định khu Chờ Quyết Định để đặt ống chuẩn cách ly.',
            'pending_location_id.integer' => 'Định khu Chờ Quyết Định không hợp lệ.',
        ];
    }
}
