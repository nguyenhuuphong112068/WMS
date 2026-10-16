<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentMaterial;
use App\Support\ListRange;
use App\Support\MaterialCode;
use App\Support\MaterialPicking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * SỬ DỤNG - LOẠI BỎ VẬT TƯ HỎNG (3 BƯỚC)
 *
 * Nằm trong tab "Cách ly - Loại Bỏ - Huỷ" của Sử Dụng Vật Tư nên không có action index riêng; dữ
 * liệu do MaterialExportController::index() nạp qua paneData().
 *
 *   BƯỚC 1 - CÁCH LY  (quarantined): phát hiện hàng hỏng thì cách ly chờ quyết định. Hàng
 *                     KHÔNG trừ tồn sổ sách (vẫn nằm trong kho) nhưng bị giữ chỗ qua
 *                     App\Support\MaterialPicking - mọi màn cấp phát / chuyển đi không lấy
 *                     được. Quyết định "Trả về kho" (returned) thì nhả ra dùng lại.
 *   BƯỚC 2 - LOẠI BỎ  (removed)    : quyết định loại bỏ -> sinh material_exports type =
 *                     cancel (quarantine_id trỏ về phiếu cách ly), TRỪ TỒN THẬT. Phiếu này
 *                     bị khoá sửa / khoá ở Sổ sử dụng: không có đường quay lại kho.
 *   BƯỚC 3 - HUỶ      (destroyed)  : ghi nhận đã huỷ thực tế (phương pháp, ghi chú). Không
 *                     động tồn - tồn đã trừ từ bước 2.
 *
 * Quyết định (bước 2) và huỷ (bước 3) là thao tác ký xác nhận: nhập lại mật khẩu -
 * 21 CFR Part 11.
 */
class MaterialQuarantineController extends Controller
{
    use VerifiesSignature;

    private const TABLE = MaterialPicking::QUARANTINE_TABLE;

    private const EXPORT_TABLE = 'material_exports';

    private const EXPORT_HISTORY_TABLE = 'material_export_histories';

    private const LABEL = 'phiếu cách ly vật tư hỏng';

    /** Tiền tố tham số lọc / phân trang của tab. */
    public const PREFIX = 'qr_';

    /** Tiền tố mã phiếu cách ly: CL-<id phòng>-<đuôi ngẫu nhiên>. */
    private const CODE_KIND = 'CL';

    private const EPSILON = 0.00005;

    public const STATUSES = [
        'quarantined' => 'Đang cách ly',
        'removed' => 'Đã loại bỏ - chờ huỷ',
        'destroyed' => 'Đã huỷ',
        'returned' => 'Đã trả về kho',
    ];

    /**
     * Nhóm "Vật tư sắp hết hạn dùng" (qstatus=expiring) của tab: không phải trạng thái phiếu mà là
     * danh sách LÔ còn tồn có hạn dùng <= ngưỡng sát hạn (config material.near_expiry_days.critical),
     * kể cả lô đã hết hạn - nguồn để chọn đem cách ly.
     */
    public const EXPIRING = 'expiring';

    /** Mở tab không chỉ rõ nhóm thì vào danh sách đang cách ly - việc chờ quyết định. */
    public const DEFAULT_STATUS = 'quarantined';

    /** Phiếu CHƯA XONG - bộ lọc khoảng ngày luôn giữ lại dù đã ngoài khoảng lọc. */
    private const PENDING_STATUSES = ['quarantined', 'removed'];

    public const DECISIONS = [
        'remove' => 'Loại bỏ',
        'return' => 'Trả về kho',
    ];

    /* ==========================================================
     |  DỮ LIỆU TAB
     ========================================================== */

    public static function paneData(int $departmentId, Request $request): array
    {
        $range = ListRange::of($request, self::PREFIX);
        $keyword = ListRange::keyword($request, self::PREFIX);
        $perPage = ListRange::perPage($request, self::PREFIX);
        $status = in_array((string) $request->input('qstatus'), array_merge([self::EXPIRING], array_keys(self::STATUSES)), true)
            ? (string) $request->input('qstatus')
            : self::DEFAULT_STATUS;

        $rows = DB::table(self::TABLE)
            ->leftJoin('material_imports', self::TABLE.'.import_id', '=', 'material_imports.id')
            ->leftJoin('material_categories', 'material_imports.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('locations', 'material_imports.location_id', '=', 'locations.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_imports.category_id'))
            ->select(
                self::TABLE.'.*',
                'material_imports.code as import_code',
                'material_imports.expired_date',
                'material_categories.code as category_code',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'locations.code as location_code',
                'units.short_name as unit_short_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->when($status !== self::EXPIRING, fn ($query) => $query->where(self::TABLE.'.app_status', $status))
            // Nhóm "sắp hết hạn" hiện danh sách lô, không hiện phiếu cách ly
            ->when($status === self::EXPIRING, fn ($query) => $query->whereIn(self::TABLE.'.id', []))
            ->tap(ListRange::dateFilterKeepPending(self::TABLE.'.created_at', $range, self::TABLE.'.app_status', self::PENDING_STATUSES))
            ->tap(ListRange::search([
                self::TABLE.'.code',
                'material_imports.code',
                'material_categories.code',
                'material_names.name',
                self::TABLE.'.reason',
            ], $keyword))
            // Việc còn phải làm lên trước: đang cách ly -> chờ huỷ -> đã xong
            ->orderByRaw("FIELD(".self::TABLE.".app_status, 'quarantined', 'removed', 'destroyed', 'returned')")
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

        // Lô cách ly được: còn tồn sổ sách chưa bị cách ly (kể cả lô hết hạn - hàng hết hạn
        // cũng là hàng phải loại bỏ; kể cả phần đang hứa cho đợt lấy hàng).
        $lots = MaterialPicking::lots($departmentId)
            ->map(function ($lot) {
                $lot->quarantinable = max($lot->remaining - $lot->quarantined, 0);

                return $lot;
            })
            ->filter(fn ($lot) => $lot->quarantinable > self::EPSILON)
            ->values();

        // Mã vật tư của từng lô - bảng "Danh mục tồn kho" ở modal cách ly tìm được theo mã vật tư
        $categoryCodes = DB::table('material_categories')
            ->whereIn('id', $lots->pluck('category_id')->filter()->unique()->values())
            ->pluck('code', 'id');

        foreach ($lots as $lot) {
            $lot->category_code = $categoryCodes[$lot->category_id] ?? null;
        }

        // Nhóm "Vật tư sắp hết hạn dùng": lô còn cách ly được, hạn dùng <= ngưỡng sát hạn (kể cả đã hết hạn)
        $expiringDays = (int) config('material.near_expiry_days.critical', 30);
        $expiring = $lots
            ->filter(fn ($lot) => $lot->days_to_expiry !== null && $lot->days_to_expiry <= $expiringDays)
            ->sortBy('days_to_expiry')
            ->values();

        return [
            'rows' => $rows,
            'range' => $range,
            'keyword' => $keyword,
            'perPage' => $perPage,
            'status' => $status,
            'counts' => $counts,
            'badge' => (int) $counts->sum(),
            'lots' => $lots,
            'expiring' => $expiring,
            'expiringDays' => $expiringDays,
        ];
    }

    /* ==========================================================
     |  BƯỚC 1 - CÁCH LY
     ========================================================== */

    /**
     * Cách ly NHIỀU mã xuất nhập một lần (items[][import_id|amount], chung một lý do).
     *
     * Mỗi mã vẫn là MỘT phiếu cách ly riêng - quyết định loại bỏ / trả về kho và huỷ ở bước
     * sau đi theo từng phiếu. Cả đợt ghi trong một transaction: một mã sai thì không ghi mã nào.
     */
    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.import_id' => ['required', 'integer'],
            'items.*.amount' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'string', 'max:500'],
        ], $this->messages());

        // import_id => ['lot' => lô, 'amount' => tổng số cách ly]
        $picked = [];

        $validator->after(function ($v) use ($request, $departmentId, &$picked) {
            if (! is_array($request->items)) {
                return;
            }

            $lots = MaterialPicking::lots($departmentId)->keyBy('id');

            foreach ($request->items as $item) {
                $importId = (int) ($item['import_id'] ?? 0);

                // Dòng thiếu mã / số lượng đã có lỗi từ rules ở trên
                if ($importId <= 0 || ! is_numeric($item['amount'] ?? null)) {
                    continue;
                }

                $lot = $lots->get($importId);

                if (! $lot) {
                    $v->errors()->add('items', 'Không tìm thấy mã xuất nhập đã chọn trong kho phòng ban này.');

                    continue;
                }

                // Cùng một mã chọn hai lần thì cộng dồn rồi mới so với phần còn cách ly được
                $picked[$importId] = [
                    'lot' => $lot,
                    'amount' => round(($picked[$importId]['amount'] ?? 0) + (float) $item['amount'], 4),
                ];
            }

            foreach ($picked as $row) {
                $limit = max($row['lot']->remaining - $row['lot']->quarantined, 0);

                if ($row['amount'] > $limit + self::EPSILON) {
                    $v->errors()->add(
                        'items',
                        'Mã xuất nhập '.$row['lot']->code.' chỉ còn '.$this->number($limit).' '.$row['lot']->unit_short_name
                        .' chưa cách ly, không cách ly vượt số này được.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'quarantineErrors')
                ->withInput()
                ->with('activeTab', 'quarantine');
        }

        $reason = trim((string) $request->reason);

        $created = DB::transaction(function () use ($picked, $departmentId, $reason) {
            $created = [];

            foreach ($picked as $row) {
                $code = $this->nextCode($departmentId);

                $id = DB::table(self::TABLE)->insertGetId([
                    'code' => $code,
                    'department_id' => $departmentId,
                    'import_id' => (int) $row['lot']->id,
                    'amount' => $row['amount'],
                    'reason' => $reason,
                    'app_status' => 'quarantined',
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                AuditTrialController::log(
                    'Cách ly vật tư hỏng',
                    self::TABLE,
                    $id,
                    'NA',
                    'Phiếu '.$code.', mã xuất nhập: '.$row['lot']->code.', số lượng: '.$this->number($row['amount']).', lý do: '.$reason
                );

                $created[] = $row + ['code' => $code];
            }

            return $created;
        });

        $message = count($created) === 1
            ? 'Đã cách ly '.$this->number($created[0]['amount']).' '.$created[0]['lot']->unit_short_name
                .' của mã xuất nhập '.$created[0]['lot']->code.' (phiếu '.$created[0]['code'].').'
            : 'Đã cách ly '.count($created).' mã xuất nhập: '.collect($created)
                ->map(fn ($row) => $row['lot']->code.' ('.$this->number($row['amount']).' '.$row['lot']->unit_short_name.' - phiếu '.$row['code'].')')
                ->implode(', ').'.';

        return redirect()->back()
            ->with('success', $message.' Hàng không còn cấp phát được cho tới khi có quyết định.')
            ->with('activeTab', 'quarantine');
    }

    /** Sửa số lượng / lý do khi phiếu còn đang cách ly. */
    public function update(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current || $current->app_status !== 'quarantined') {
            return $this->fail('Chỉ sửa được '.self::LABEL.' đang cách ly!');
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'string', 'max:500'],
        ], $this->messages());

        $validator->after(function ($v) use ($request, $current) {
            $lot = MaterialPicking::lots($this->departmentId())->firstWhere('id', (int) $current->import_id);
            $limit = $lot ? max($lot->remaining - MaterialPicking::quarantinedOf((int) $lot->id, (int) $current->id), 0) : 0;

            if (is_numeric($request->amount) && (float) $request->amount > $limit + self::EPSILON) {
                $v->errors()->add('amount', 'Mã xuất nhập chỉ còn '.$this->number($limit).' có thể cách ly.');
            }
        });

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $amount = round((float) $request->amount, 4);
        $reason = trim((string) $request->reason);
        $changes = [];

        if (abs($amount - (float) $current->amount) > self::EPSILON) {
            $changes[] = 'Số lượng: '.$this->number((float) $current->amount).' -> '.$this->number($amount);
        }

        if ($reason !== (string) $current->reason) {
            $changes[] = 'Lý do: '.$current->reason.' -> '.$reason;
        }

        if (! $changes) {
            return $this->fail('Không có thông tin nào thay đổi.');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'amount' => $amount,
            'reason' => $reason,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, implode('; ', $changes));

        return redirect()->back()->with('success', 'Đã cập nhật phiếu cách ly '.$current->code.'!')->with('activeTab', 'quarantine');
    }

    /* ==========================================================
     |  BƯỚC 2 - QUYẾT ĐỊNH: LOẠI BỎ / TRẢ VỀ KHO
     ========================================================== */

    public function decide(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current || $current->app_status !== 'quarantined') {
            return $this->fail('Phiếu cách ly không còn ở trạng thái chờ quyết định!');
        }

        $validator = Validator::make($request->all(), [
            'decision' => ['required', 'in:'.implode(',', array_keys(self::DECISIONS))],
            'decision_note' => ['required', 'string', 'max:500'],
        ], [
            'decision.required' => 'Vui lòng chọn quyết định.',
            'decision.in' => 'Quyết định không hợp lệ.',
            'decision_note.required' => 'Vui lòng nhập nội dung / căn cứ quyết định.',
            'decision_note.max' => 'Nội dung quyết định tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'quarantineDecideErrors')
                ->withInput()
                ->with('activeTab', 'quarantine');
        }

        $label = self::DECISIONS[$request->decision];

        if ($denied = $this->guardSignature($request, self::TABLE, $current->id, 'Quyết định '.mb_strtolower($label).' vật tư cách ly')) {
            return $denied->with('activeTab', 'quarantine');
        }

        $note = trim((string) $request->decision_note);
        $actor = $this->actor();
        $at = now();

        if ($request->decision === 'return') {
            DB::table(self::TABLE)->where('id', $current->id)->update([
                'app_status' => 'returned',
                'decision_note' => $note,
                'decided_by' => $actor,
                'decided_at' => $at,
                'updated_by' => $actor,
                'updated_at' => $at,
            ]);

            AuditTrialController::log('Trả vật tư cách ly về kho', self::TABLE, $current->id, 'app_status: quarantined', 'app_status: returned; '.$note);

            return redirect()->back()
                ->with('success', 'Đã trả '.$this->number((float) $current->amount).' vật tư của phiếu '.$current->code.' về kho, hàng dùng lại được.')
                ->with('activeTab', 'quarantine');
        }

        // Loại bỏ: trừ tồn thật. Tồn có thể đã giảm sau khi cách ly (cân đối kiểm kê...)
        $import = DB::table('material_imports')->where('id', $current->import_id)->first();
        $remaining = $import ? $this->remaining($import) : 0;

        if ((float) $current->amount > $remaining + self::EPSILON) {
            return $this->fail(
                'Mã xuất nhập '.($import->code ?? '').' chỉ còn tồn '.$this->number($remaining)
                .', không đủ để loại bỏ '.$this->number((float) $current->amount).'. Hãy sửa lại số lượng cách ly trước.'
            );
        }

        $done = DB::transaction(function () use ($current, $import, $note, $actor, $at) {
            // Chốt trạng thái trước: hai người bấm quyết định cùng lúc thì chỉ một người thắng
            $claimed = DB::table(self::TABLE)
                ->where('id', $current->id)
                ->where('app_status', 'quarantined')
                ->update(['app_status' => 'removed', 'updated_at' => $at]);

            if (! $claimed) {
                return false;
            }

            $exportId = DB::table(self::EXPORT_TABLE)->insertGetId([
                'code' => $import->code,
                'import_id' => (int) $import->id,
                'department_id' => (int) $current->department_id,
                'quarantine_id' => (int) $current->id,
                'amount' => (float) $current->amount,
                'type' => 'cancel',
                'reason' => mb_substr($current->reason.' | Quyết định: '.$note, 0, 500),
                'used_by' => $actor,
                'status_id' => 1,
                'created_by' => $actor,
                'created_at' => $at,
                'updated_at' => $at,
            ]);

            $this->logExportHistory($exportId, 'Thêm mới', 'Loại bỏ từ phiếu cách ly '.$current->code);

            DB::table(self::TABLE)->where('id', $current->id)->update([
                'decision_note' => $note,
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

        AuditTrialController::log('Loại bỏ vật tư hỏng', self::TABLE, $current->id, 'app_status: quarantined', 'app_status: removed; '.$note);

        return redirect()->back()
            ->with('success', 'Đã loại bỏ '.$this->number((float) $current->amount).' vật tư của phiếu '.$current->code.' (đã trừ tồn, không quay lại kho được). Chờ ghi nhận huỷ.')
            ->with('activeTab', 'quarantine');
    }

    /* ==========================================================
     |  BƯỚC 3 - HUỶ (một hoặc nhiều phiếu đã loại bỏ)
     ========================================================== */

    public function dispose(Request $request)
    {
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
            return $this->fail('Có phiếu không còn ở trạng thái "Đã loại bỏ - chờ huỷ", vui lòng tải lại trang.');
        }

        if ($denied = $this->guardSignature($request, self::TABLE, $ids->first(), 'Ghi nhận huỷ vật tư')) {
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
            AuditTrialController::log('Huỷ vật tư', self::TABLE, $row->id, 'app_status: removed', 'app_status: destroyed; phương pháp: '.$method.($note ? '; '.$note : ''));
        }

        return redirect()->back()
            ->with('success', 'Đã ghi nhận huỷ '.$rows->count().' phiếu: '.$rows->pluck('code')->implode(', ').'.')
            ->with('activeTab', 'quarantine');
    }

    /* ==========================================================
     |  HỖ TRỢ
     ========================================================== */

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

    /** Tồn sổ sách của một lô = nhập + cân đối - đã xuất (giống MaterialExportController::remaining). */
    private function remaining($import): float
    {
        $exported = (float) DB::table(self::EXPORT_TABLE)->where('import_id', $import->id)->where('status_id', 1)->sum('amount');
        $balanced = (float) DB::table('material_balancings')->where('import_id', $import->id)->where('status_id', 1)->sum('balancing_amount');

        return max((float) $import->amount + $balanced - $exported, 0);
    }

    /** Ghi lịch sử phiếu loại bỏ vào sổ sử dụng - cùng khuôn MaterialExportController::logHistory. */
    private function logExportHistory(int $exportId, string $action, ?string $note = null): void
    {
        $row = DB::table(self::EXPORT_TABLE)->where('id', $exportId)->first();

        DB::table(self::EXPORT_HISTORY_TABLE)->insert([
            'material_export_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'import_id' => $row->import_id,
            'amount' => $row->amount,
            'type' => $row->type,
            'product_name' => $row->product_name,
            'test_report_no' => $row->test_report_no,
            'reason' => $row->reason,
            'used_by' => $row->used_by,
            'status_id' => $row->status_id,
            'change_note' => $note,
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    /** CL-07-7KPMR9J4WD - cùng cách sinh đuôi ngẫu nhiên với mã lô vật tư. */
    private function nextCode(int $departmentId): string
    {
        $prefix = self::CODE_KIND.MaterialCode::SEP
            .str_pad((string) $departmentId, MaterialCode::DEPT_LENGTH, '0', STR_PAD_LEFT).MaterialCode::SEP;

        do {
            $code = $prefix.MaterialCode::randomTail();
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
            'import_id.required' => 'Vui lòng chọn mã xuất nhập cần cách ly.',
            'items.required' => 'Vui lòng chọn ít nhất một mã xuất nhập cần cách ly.',
            'items.min' => 'Vui lòng chọn ít nhất một mã xuất nhập cần cách ly.',
            'items.*.import_id.required' => 'Có dòng chưa chọn mã xuất nhập.',
            'items.*.import_id.integer' => 'Mã xuất nhập được chọn không hợp lệ.',
            'items.*.amount.required' => 'Vui lòng nhập số lượng cách ly cho mọi mã đã chọn.',
            'items.*.amount.numeric' => 'Số lượng cách ly phải là số.',
            'items.*.amount.min' => 'Số lượng cách ly phải lớn hơn 0.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'reason.required' => 'Vui lòng nhập lý do cách ly (hỏng / hết hạn...).',
            'reason.max' => 'Lý do tối đa 500 ký tự.',
        ];
    }
}
