<?php

namespace App\Http\Controllers\Pages\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * TỒN - KIỂM KÊ KHO VẬT TƯ (chu kỳ 3 tháng 1 lần)
 *
 * Không phải một màn hình riêng trên leftNAV: toàn bộ phần này hiển thị trong tab
 * "Kiểm Kê Định Kỳ" của màn hình TỒN KHO VẬT TƯ. MaterialInventoryController::index()
 * gọi self::panel() để lấy dữ liệu cho tab đó, các nút bấm trên tab gửi về đây.
 *
 * CHU KỲ: 3 tháng 1 lần, kỳ kiểm kê là một QUÝ dương lịch (Q1 = 01-03, Q2 = 04-06,
 * Q3 = 07-09, Q4 = 10-12). Mỗi phòng ban mỗi quý mở ĐÚNG MỘT phiếu, kỳ là trọn quý
 * HIỆN TẠI do hệ thống tự xác định - người dùng không chọn quý, không chọn ngày (xem
 * quy tắc "ngày thao tác do hệ thống ghi").
 *
 * CÁCH TÍNH:
 *      Tồn sổ sách của một mã = material_imports.amount
 *                             + SUM(material_balancings.balancing_amount)
 *                             - SUM(material_exports.amount)
 *      Chênh lệch của một dòng = số đếm thực tế - tồn sổ sách lúc mở phiếu
 *
 * CHỐT PHIẾU: dòng nào lệch thì ghi thêm một dòng material_balancings đúng bằng phần
 * lệch để kéo tồn sổ sách về số thực đếm. Vẫn giữ nguyên hai chốt chặn của màn hình
 * Cân Đối: tổng cân đối của một mã không vượt 5% lượng nhập, và cân đối xong tồn không
 * được âm. Dòng vi phạm KHÔNG tự ghi cân đối, chỉ đánh dấu để xử lý riêng.
 */
class MaterialStocktakeController extends Controller
{
    private const EPSILON = 0.00005;

    /** Trùng với MaterialInventoryController::BALANCING_MAX_RATIO - hạn mức cân đối 5%. */
    private const BALANCING_MAX_RATIO = 0.05;

    /** Độ dài một chu kỳ kiểm kê, tính bằng tháng - 3 tháng = một quý. */
    private const CYCLE_MONTHS = 3;

    /** Số kỳ gần nhất đưa vào ô chọn "Theo dõi chu kỳ" (8 quý = 2 năm). */
    private const PERIOD_COUNT = 8;

    public const STATES = [
        'counting' => 'Đang kiểm kê',
        'completed' => 'Đã chốt',
    ];

    /* ==========================================================
     |  DỮ LIỆU CHO TAB "KIỂM KÊ ĐỊNH KỲ"
     ========================================================== */

    /**
     * Gói dữ liệu cho tab kiểm kê, gọi từ MaterialInventoryController::index().
     *
     * - current  : phiếu của QUÝ HIỆN TẠI (nếu đã mở), kể cả khi đã chốt
     * - items    : các dòng đếm của phiếu quý hiện tại
     * - periods  : 8 quý gần nhất kèm tình trạng, đổ vào ô chọn Theo Dõi Chu Kỳ
     * - history  : các phiếu đã mở, kèm số dòng / số dòng lệch
     */
    public static function panel(int $departmentId): array
    {
        $controller = new self;

        $start = now()->startOfQuarter();

        $current = DB::table('material_stocktakes')
            ->where('department_id', $departmentId)
            ->where('period_start', $start->format('Y-m-d'))
            ->where('status_id', 1)
            ->orderByDesc('id')
            ->first();

        $history = $controller->history($departmentId);
        $periods = $controller->periods($departmentId, $history);

        return [
            'current' => $current,
            'items' => $current ? $controller->items($current->id, $departmentId) : collect(),
            'progress' => $current ? $controller->progress($current->id) : null,
            'periods' => $periods,
            'history' => $history,
            'period' => $start->format('Y-m-d'),
            'periodLabel' => $controller->periodLabel($start),
            'periodRange' => $controller->periodRange($start),
            // Số kỳ đã kiểm kê / tổng số kỳ trong ô chọn, thay cho dải thẻ 12 tháng cũ
            'doneCount' => count(array_filter($periods, fn ($p) => $p['state'] === 'completed')),
            'missedCount' => count(array_filter($periods, fn ($p) => $p['state'] === 'missed')),
            'periodCount' => count($periods),
            'cycleMonths' => self::CYCLE_MONTHS,
            'canOpen' => $current === null,
            'balancingMaxPercent' => (int) round(self::BALANCING_MAX_RATIO * 100),
            'states' => self::STATES,
        ];
    }

    /**
     * Kết quả của một thao tác trên tab kiểm kê.
     *
     * Gọi bằng AJAX (nút trên tab) thì trả JSON kèm HTML của tab đã dựng lại, để trang
     * không phải tải lại - tải lại là màn hình Tồn Kho nhảy về tab đầu, mất chỗ đang làm.
     * Gọi kiểu thường (trình duyệt tắt JS) vẫn giữ nguyên redirect như các màn khác.
     */
    private function respond(Request $request, string $status, string $message)
    {
        if (! $request->ajax()) {
            return redirect()->back()->with($status, $message);
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'html' => view('pages.inventory.MaterialInventory.stocktakePanel', [
                'stocktake' => self::panel($this->departmentId()),
            ])->render(),
        ]);
    }

    /**
     * Lỗi nhập liệu của bảng đếm. Gửi ngầm thì gộp thành một câu cho SweetAlert2,
     * gọi kiểu thường thì vẫn đổ vào bag stocktakeErrors để view in ra như cũ.
     */
    private function respondValidation(Request $request, $validator)
    {
        if ($request->ajax()) {
            return $this->respond($request, 'error', implode(' ', $validator->errors()->all()));
        }

        return redirect()->back()->withErrors($validator, 'stocktakeErrors')->withInput();
    }

    /** "Quý 3/2026" - nhãn của một kỳ kiểm kê, dùng chung cho mọi chỗ hiển thị. */
    private function periodLabel($start): string
    {
        $start = \Carbon\Carbon::parse($start);

        return 'Quý '.$start->quarter.'/'.$start->year;
    }

    /** "01/07/2026 - 30/09/2026" - khoảng thời gian của một kỳ kiểm kê. */
    private function periodRange($start): string
    {
        $start = \Carbon\Carbon::parse($start);

        return $start->copy()->startOfQuarter()->format('d/m/Y')
            .' - '.$start->copy()->endOfQuarter()->format('d/m/Y');
    }

    /* ==========================================================
     |  HÀNH ĐỘNG
     ========================================================== */

    /**
     * MỞ PHIẾU KIỂM KÊ CỦA QUÝ HIỆN TẠI.
     *
     * Chốt danh sách các mã xuất nhập ĐANG CÓ SỐ DƯ (còn tồn hoặc đang âm kho) rồi ghi
     * lại tồn sổ sách của từng mã. Mã đã dùng hết sạch không đưa vào danh sách đếm.
     */
    public function open(Request $request)
    {
        $departmentId = $this->departmentId();
        $start = now()->startOfQuarter();
        $label = $this->periodLabel($start);

        $existed = DB::table('material_stocktakes')
            ->where('department_id', $departmentId)
            ->where('period_start', $start->format('Y-m-d'))
            ->where('status_id', 1)
            ->first();

        if ($existed) {
            return $this->respond($request, 'error', 'Kỳ kiểm kê '.$label.' đã có phiếu, mỗi quý chỉ mở một phiếu!');
        }

        $stock = $this->currentStock($departmentId)
            ->filter(fn ($row) => abs($row->gap) > self::EPSILON)
            ->values();

        if ($stock->isEmpty()) {
            return $this->respond($request, 'error', 'Kho vật tư đang không còn mã xuất nhập nào có số dư để kiểm kê!');
        }

        $actor = $this->actor();
        $now = now();

        $stocktakeId = DB::table('material_stocktakes')->insertGetId([
            'code' => $this->nextCode($departmentId, $start),
            'department_id' => $departmentId,
            'period_start' => $start->format('Y-m-d'),
            'from_date' => $start->format('Y-m-d'),
            'to_date' => $start->copy()->endOfQuarter()->format('Y-m-d'),
            'state' => 'counting',
            'note' => $request->filled('note') ? substr(trim($request->note), 0, 500) : null,
            'opened_by' => $actor,
            'opened_at' => $now,
            'status_id' => 1,
            'created_by' => $actor,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_stocktake_items')->insert(
            $stock->map(fn ($row) => [
                'stocktake_id' => $stocktakeId,
                'import_id' => (int) $row->id,
                'code' => $row->code,
                'category_id' => $row->category_id ? (int) $row->category_id : null,
                'location_id' => $row->location_id ? (int) $row->location_id : null,
                'department_id' => $departmentId,
                'system_amount' => $row->gap,
                'status_id' => 1,
                'created_by' => $actor,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );

        AuditTrialController::log(
            'Mở kiểm kê',
            'material_stocktakes',
            $stocktakeId,
            null,
            'Kỳ '.$label.' ('.$this->periodRange($start).') - '.$stock->count().' mã xuất nhập cần đếm'
        );

        return $this->respond($request, 'success',
            'Đã mở phiếu kiểm kê '.$label.' với '.$stock->count().' mã xuất nhập cần đếm.'
        );
    }

    /**
     * LƯU SỐ ĐẾM THỰC TẾ - cho phép đếm nhiều đợt, dòng nào bỏ trống thì để nguyên
     * là chưa đếm. Chỉ ghi được khi phiếu còn đang kiểm kê.
     */
    public function count(Request $request)
    {
        $departmentId = $this->departmentId();
        $stocktake = $this->openStocktake($request->stocktake_id, $departmentId);

        if (! $stocktake) {
            return $this->respond($request, 'error', 'Không tìm thấy phiếu kiểm kê đang mở!');
        }

        $validator = $this->countValidator($request, $stocktake->id);

        if ($validator->errors()->isNotEmpty()) {
            return $this->respondValidation($request, $validator);
        }

        $saved = $this->saveCounts($request, $stocktake->id);
        $progress = $this->progress($stocktake->id);

        return $this->respond($request, 'success',
            'Đã lưu số đếm của '.$saved.' dòng. Đã đếm '.$progress['counted'].'/'.$progress['total'].' dòng.'
        );
    }

    /** Kiểm tra ô số đếm gửi lên: bỏ trống thì bỏ qua, có nhập thì phải là số không âm. */
    private function countValidator(Request $request, int $stocktakeId)
    {
        $codes = DB::table('material_stocktake_items')
            ->where('stocktake_id', $stocktakeId)
            ->where('status_id', 1)
            ->pluck('code', 'id');

        $validator = Validator::make([], []);

        foreach ($this->countInput($request) as $itemId => $values) {
            if (! isset($codes[$itemId])) {
                continue;
            }

            $actual = $values['actual_amount'] ?? null;

            if ($actual === null || trim((string) $actual) === '') {
                continue;
            }

            if (! is_numeric($actual)) {
                $validator->errors()->add('items', 'Số đếm thực tế của mã '.$codes[$itemId].' phải là số.');
            } elseif ((float) $actual < 0) {
                $validator->errors()->add('items', 'Số đếm thực tế của mã '.$codes[$itemId].' không được âm.');
            }
        }

        return $validator;
    }

    /**
     * Ghi số đếm vào các dòng của phiếu, trả về số dòng đã đổi.
     *
     * Dùng chung cho nút "Lưu số đếm" và nút "Chốt kiểm kê" - hai nút cùng gửi một biểu
     * mẫu, nên bấm chốt thẳng cũng không mất số vừa gõ.
     */
    private function saveCounts(Request $request, int $stocktakeId): int
    {
        $items = DB::table('material_stocktake_items')
            ->where('stocktake_id', $stocktakeId)
            ->where('status_id', 1)
            ->get()
            ->keyBy('id');

        $actor = $this->actor();
        $now = now();
        $saved = 0;

        foreach ($this->countInput($request) as $itemId => $values) {
            $item = $items->get($itemId);

            if (! $item) {
                continue;
            }

            $actual = $values['actual_amount'] ?? null;
            $note = isset($values['note']) ? substr(trim((string) $values['note']), 0, 500) : null;

            // Bỏ trống lại ô đã đếm = xoá số đếm, dòng đó quay về "chưa đếm"
            if ($actual === null || trim((string) $actual) === '') {
                if ($item->actual_amount !== null) {
                    DB::table('material_stocktake_items')->where('id', $item->id)->update([
                        'actual_amount' => null,
                        'diff_amount' => null,
                        'note' => $note ?: null,
                        'counted_by' => null,
                        'counted_at' => null,
                        'updated_by' => $actor,
                        'updated_at' => $now,
                    ]);
                    $saved++;
                }

                continue;
            }

            $actual = round((float) $actual, 4);

            DB::table('material_stocktake_items')->where('id', $item->id)->update([
                'actual_amount' => $actual,
                'diff_amount' => round($actual - (float) $item->system_amount, 4),
                'note' => $note ?: null,
                'counted_by' => $actor,
                'counted_at' => $now,
                'updated_by' => $actor,
                'updated_at' => $now,
            ]);
            $saved++;
        }

        DB::table('material_stocktakes')->where('id', $stocktakeId)->update([
            'updated_by' => $actor,
            'updated_at' => $now,
        ]);

        return $saved;
    }

    /** [item_id => ['actual_amount' => ..., 'note' => ...]] gửi lên từ bảng đếm. */
    private function countInput(Request $request): array
    {
        $input = $request->input('items');

        if (! is_array($input)) {
            return [];
        }

        $out = [];

        foreach ($input as $itemId => $values) {
            if (is_array($values)) {
                $out[(int) $itemId] = $values;
            }
        }

        return $out;
    }

    /**
     * CHỐT PHIẾU KIỂM KÊ.
     *
     * Bắt buộc đếm đủ mọi dòng mới được chốt. Dòng nào lệch thì ghi thêm một dòng
     * material_balancings đúng bằng phần lệch; dòng vi phạm hạn mức 5% hoặc làm tồn âm
     * thì bỏ qua và ghi rõ lý do vào balancing_note để xử lý riêng.
     */
    public function complete(Request $request)
    {
        $departmentId = $this->departmentId();
        $stocktake = $this->openStocktake($request->stocktake_id, $departmentId);

        if (! $stocktake) {
            return $this->respond($request, 'error', 'Không tìm thấy phiếu kiểm kê đang mở!');
        }

        $validator = $this->countValidator($request, $stocktake->id);

        if ($validator->errors()->isNotEmpty()) {
            return $this->respondValidation($request, $validator);
        }

        // Nút Chốt gửi kèm cả bảng đếm nên lưu số vừa gõ trước, rồi mới xét đủ hay chưa
        $this->saveCounts($request, $stocktake->id);

        $progress = $this->progress($stocktake->id);

        if ($progress['counted'] < $progress['total']) {
            return $this->respond($request, 'error',
                'Còn '.($progress['total'] - $progress['counted']).' dòng chưa đếm, phải đếm đủ mới chốt được phiếu kiểm kê!'
            );
        }

        $items = DB::table('material_stocktake_items')
            ->where('stocktake_id', $stocktake->id)
            ->where('status_id', 1)
            ->orderBy('code', 'asc')
            ->get();

        $imports = DB::table('material_imports')
            ->select('id', 'code', 'amount')
            ->where('department_id', $departmentId)
            ->whereIn('id', $items->pluck('import_id')->all())
            ->get()
            ->keyBy('id');

        $balancedAll = $this->balancedByImport($departmentId);
        $stock = $this->currentStock($departmentId)->keyBy('id');

        $actor = $this->actor();
        $now = now();

        $balanced = 0;
        $skipped = 0;
        $matched = 0;

        foreach ($items as $item) {
            $diff = round((float) $item->actual_amount - (float) $item->system_amount, 4);

            if (abs($diff) <= self::EPSILON) {
                $matched++;

                continue;
            }

            $import = $imports->get($item->import_id);
            $reason = $this->balancingBlocker($import, $stock->get($item->import_id), (float) ($balancedAll[$item->import_id] ?? 0), $diff);

            if ($reason !== null) {
                DB::table('material_stocktake_items')->where('id', $item->id)->update([
                    'diff_amount' => $diff,
                    'balancing_skipped' => true,
                    'balancing_note' => $reason,
                    'updated_by' => $actor,
                    'updated_at' => $now,
                ]);
                $skipped++;

                continue;
            }

            $balancingId = DB::table('material_balancings')->insertGetId([
                'code' => $import->code,
                'import_id' => (int) $import->id,
                'department_id' => $departmentId,
                'balancing_amount' => $diff,
                'balancing_by' => $actor,
                'balancing_at' => $now,
                'status_id' => 1,
                'created_by' => $actor,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('material_stocktake_items')->where('id', $item->id)->update([
                'diff_amount' => $diff,
                'balancing_id' => $balancingId,
                'balancing_skipped' => false,
                'balancing_note' => 'Kiểm kê '.$stocktake->code.': cân đối '.($diff > 0 ? '+' : '').$this->number($diff).'.',
                'updated_by' => $actor,
                'updated_at' => $now,
            ]);

            AuditTrialController::log(
                'Cân đối',
                'material_balancings',
                $balancingId,
                'Tồn sổ sách: '.$this->number((float) $item->system_amount),
                'Kiểm kê '.$stocktake->code.' - thực tế '.$this->number((float) $item->actual_amount)
                .', cân đối '.($diff > 0 ? '+' : '').$this->number($diff)
            );

            $balanced++;
        }

        DB::table('material_stocktakes')->where('id', $stocktake->id)->update([
            'state' => 'completed',
            'note' => $request->filled('note') ? substr(trim($request->note), 0, 500) : $stocktake->note,
            'completed_by' => $actor,
            'completed_at' => $now,
            'updated_by' => $actor,
            'updated_at' => $now,
        ]);

        AuditTrialController::log(
            'Chốt kiểm kê',
            'material_stocktakes',
            $stocktake->id,
            'Đang kiểm kê - '.$progress['total'].' dòng',
            'Đã chốt - khớp '.$matched.', đã cân đối '.$balanced.', chờ xử lý '.$skipped
        );

        $message = 'Đã chốt phiếu kiểm kê '.$stocktake->code.': khớp '.$matched.' dòng, cân đối '.$balanced.' dòng.';

        // Lý do bỏ qua mỗi dòng mỗi khác (vượt hạn mức / cân đối xong tồn âm) nên ở đây
        // chỉ báo số lượng, lý do từng dòng nằm ở cột Chênh Lệch của bảng kiểm kê.
        if ($skipped > 0) {
            $message .= ' Còn '.$skipped.' dòng lệch chưa cân đối được, xem lý do ở cột Chênh Lệch rồi xử lý riêng.';
        }

        return $this->respond($request, 'success', $message);
    }

    /**
     * HUỶ PHIẾU ĐANG KIỂM KÊ - đảo status_id về 0, không xoá cứng. Huỷ xong tháng đó
     * mở lại được phiếu mới. Phiếu đã chốt không huỷ.
     */
    public function deActive(Request $request)
    {
        $departmentId = $this->departmentId();
        $stocktake = $this->openStocktake($request->stocktake_id, $departmentId);

        if (! $stocktake) {
            return $this->respond($request, 'error', 'Không tìm thấy phiếu kiểm kê đang mở!');
        }

        $actor = $this->actor();
        $now = now();

        DB::table('material_stocktakes')->where('id', $stocktake->id)->update([
            'status_id' => 0,
            'updated_by' => $actor,
            'updated_at' => $now,
        ]);

        DB::table('material_stocktake_items')->where('stocktake_id', $stocktake->id)->update([
            'status_id' => 0,
            'updated_by' => $actor,
            'updated_at' => $now,
        ]);

        AuditTrialController::log('Huỷ kiểm kê', 'material_stocktakes', $stocktake->id, 'Đang kiểm kê', 'Đã huỷ');

        return $this->respond($request, 'success', 'Đã huỷ phiếu kiểm kê '.$stocktake->code.'.');
    }

    /** Chi tiết một phiếu kiểm kê (trả JSON cho modal xem lại các kỳ đã chốt). */
    public function detail(Request $request)
    {
        $departmentId = $this->departmentId();

        $stocktake = DB::table('material_stocktakes')
            ->where('id', (int) $request->query('stocktake_id'))
            ->where('department_id', $departmentId)
            ->first();

        if (! $stocktake) {
            return response()->json(['message' => 'Không tìm thấy phiếu kiểm kê.'], 404);
        }

        $items = $this->items($stocktake->id, $departmentId);

        return response()->json([
            'code' => $stocktake->code,
            'period_label' => $this->periodLabel($stocktake->period_start),
            'period_range' => $this->periodRange($stocktake->period_start),
            'state' => $stocktake->state,
            'state_label' => self::STATES[$stocktake->state] ?? $stocktake->state,
            'opened_by' => $stocktake->opened_by,
            'opened_at' => $stocktake->opened_at ? \Carbon\Carbon::parse($stocktake->opened_at)->format('d/m/Y H:i') : null,
            'completed_by' => $stocktake->completed_by,
            'completed_at' => $stocktake->completed_at ? \Carbon\Carbon::parse($stocktake->completed_at)->format('d/m/Y H:i') : null,
            'note' => $stocktake->note,
            'items' => $items->map(fn ($row) => [
                'code' => $row->code,
                'material_name' => $row->material_name ?: '—',
                'sub' => trim(implode(' · ', array_filter([$row->manufacturer_short_name, $row->technical_specification]))),
                'location_code' => $row->location_code ?: '—',
                'unit' => $row->unit,
                'system_amount' => $this->number((float) $row->system_amount),
                'actual_amount' => $row->actual_amount === null ? '—' : $this->number((float) $row->actual_amount),
                'diff_amount' => $row->diff_amount === null ? '—' : ($row->diff_amount > 0 ? '+' : '').$this->number((float) $row->diff_amount),
                'diff_state' => $row->diff_state,
                'note' => $row->note ?: '',
                'balancing_note' => $row->balancing_note ?: '',
                'balancing_skipped' => (bool) $row->balancing_skipped,
                'counted_by' => $row->counted_by ?: '',
                'counted_at' => $row->counted_at ? \Carbon\Carbon::parse($row->counted_at)->format('d/m/Y H:i') : '',
            ])->values(),
        ]);
    }

    /* ==========================================================
     |  TRUY VẤN DÙNG CHUNG
     ========================================================== */

    /**
     * TỒN SỔ SÁCH HIỆN TẠI của từng mã xuất nhập vật tư trong phòng ban.
     *
     * Cùng công thức với MaterialInventoryController nhưng KHÔNG cắt kỳ: kiểm kê đối
     * chiếu với số dư ngay tại thời điểm đếm, không phải số dư cuối một kỳ báo cáo.
     */
    private function currentStock(int $departmentId)
    {
        $exported = DB::table('material_exports')
            ->select('import_id')
            ->selectRaw('SUM(amount) as out_amount')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->get()
            ->keyBy('import_id');

        $balanced = $this->balancedByImport($departmentId);

        return DB::table('material_imports')
            ->leftJoin('material_categories', 'material_imports.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_imports.category_id'))
            ->leftJoin('locations', 'material_imports.location_id', '=', 'locations.id')
            ->select(
                'material_imports.id',
                'material_imports.code',
                'material_imports.category_id',
                'material_imports.location_id',
                'material_imports.amount',
                'material_imports.expired_date',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code'
            )
            ->where('material_imports.department_id', $departmentId)
            ->where('material_imports.status_id', 1)
            ->orderBy('material_imports.code', 'asc')
            ->get()
            ->map(function ($row) use ($exported, $balanced) {
                $row->gap = round(
                    (float) $row->amount
                    + (float) ($balanced[$row->id] ?? 0)
                    - (float) ($exported[$row->id]->out_amount ?? 0),
                    4
                );

                return $row;
            });
    }

    /** [import_id => tổng số đã cân đối] của cả phòng ban, dùng để chặn hạn mức 5%. */
    private function balancedByImport(int $departmentId)
    {
        return DB::table('material_balancings')
            ->select('import_id')
            ->selectRaw('SUM(balancing_amount) as balanced')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->get()
            ->pluck('balanced', 'import_id')
            ->map(fn ($value) => (float) $value);
    }

    /**
     * Lý do KHÔNG ghi được cân đối cho một dòng lệch, null nghĩa là ghi được.
     * Giữ đúng hai chốt chặn của màn hình Cân Đối để hai nơi không lệch luật nhau.
     */
    private function balancingBlocker($import, $stock, float $balancedAll, float $diff): ?string
    {
        if (! $import) {
            return 'Không tìm thấy mã xuất nhập, không ghi được cân đối.';
        }

        $limit = abs((float) $import->amount) * self::BALANCING_MAX_RATIO;

        if (abs($balancedAll + $diff) > $limit + self::EPSILON) {
            return 'Lệch '.($diff > 0 ? '+' : '').$this->number($diff).' vượt hạn mức cân đối ±'
                .$this->number($limit).' ('.(int) round(self::BALANCING_MAX_RATIO * 100).'% lượng nhập, đã cân đối '
                .$this->number($balancedAll).'). Cần lập phiếu xử lý riêng.';
        }

        $gap = $stock ? (float) $stock->gap : 0;

        if ($gap + $diff < -self::EPSILON) {
            return 'Cân đối '.($diff > 0 ? '+' : '').$this->number($diff).' làm tồn sổ sách âm (tồn hiện tại '
                .$this->number($gap).'). Cần kiểm tra lại phiếu sử dụng.';
        }

        return null;
    }

    /** Các dòng đếm của một phiếu, đã join sẵn tên vật tư / đơn vị / vị trí để đổ ra bảng. */
    private function items(int $stocktakeId, int $departmentId)
    {
        return DB::table('material_stocktake_items')
            ->leftJoin('material_categories', 'material_stocktake_items.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_stocktake_items.category_id'))
            ->leftJoin('locations', 'material_stocktake_items.location_id', '=', 'locations.id')
            ->leftJoin('material_imports', 'material_stocktake_items.import_id', '=', 'material_imports.id')
            ->select(
                'material_stocktake_items.*',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code',
                'material_imports.expired_date'
            )
            ->where('material_stocktake_items.stocktake_id', $stocktakeId)
            ->where('material_stocktake_items.status_id', 1)
            ->orderBy('material_stocktake_items.code', 'asc')
            ->get()
            ->map(function ($row) {
                $row->unit = $row->unit_short_name ?: $row->unit_name;
                $row->diff_state = $this->diffState($row);

                return $row;
            });
    }

    /** waiting = chưa đếm, match = khớp sổ, over = thừa so với sổ, short = thiếu so với sổ. */
    private function diffState($row): string
    {
        if ($row->actual_amount === null) {
            return 'waiting';
        }

        $diff = (float) $row->diff_amount;

        if (abs($diff) <= self::EPSILON) {
            return 'match';
        }

        return $diff > 0 ? 'over' : 'short';
    }

    private function progress(int $stocktakeId): array
    {
        $rows = DB::table('material_stocktake_items')
            ->select('actual_amount', 'diff_amount', 'balancing_skipped')
            ->where('stocktake_id', $stocktakeId)
            ->where('status_id', 1)
            ->get();

        $counted = $rows->filter(fn ($row) => $row->actual_amount !== null);

        return [
            'total' => $rows->count(),
            'counted' => $counted->count(),
            'waiting' => $rows->count() - $counted->count(),
            'diff' => $counted->filter(fn ($row) => abs((float) $row->diff_amount) > self::EPSILON)->count(),
            'skipped' => $rows->filter(fn ($row) => (bool) $row->balancing_skipped)->count(),
            'percent' => $rows->count() > 0 ? (int) round($counted->count() / $rows->count() * 100) : 0,
        ];
    }

    /** Các phiếu kiểm kê của phòng ban, kèm số liệu tổng hợp để dựng bảng theo dõi. */
    private function history(int $departmentId)
    {
        $stats = DB::table('material_stocktake_items')
            ->select('stocktake_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN actual_amount IS NOT NULL THEN 1 ELSE 0 END) as counted')
            ->selectRaw('SUM(CASE WHEN actual_amount IS NOT NULL AND ABS(diff_amount) > ? THEN 1 ELSE 0 END) as diff', [self::EPSILON])
            ->selectRaw('SUM(CASE WHEN balancing_skipped = 1 THEN 1 ELSE 0 END) as skipped')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('stocktake_id')
            ->get()
            ->keyBy('stocktake_id');

        return DB::table('material_stocktakes')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderByDesc('period_start')
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) use ($stats) {
                $stat = $stats->get($row->id);

                $row->period_label = $this->periodLabel($row->period_start);
                $row->period_range = $this->periodRange($row->period_start);
                $row->state_label = self::STATES[$row->state] ?? $row->state;
                $row->total = (int) ($stat->total ?? 0);
                $row->counted = (int) ($stat->counted ?? 0);
                $row->diff = (int) ($stat->diff ?? 0);
                $row->skipped = (int) ($stat->skipped ?? 0);

                return $row;
            });
    }

    /**
     * 8 quý gần nhất kèm tình trạng kiểm kê - đổ vào ô chọn "Theo dõi chu kỳ" để thấy
     * ngay quý nào đã kiểm kê, quý nào bỏ sót so với chu kỳ 3 tháng 1 lần.
     *
     * Kỳ MỚI NHẤT đứng đầu danh sách vì đó là kỳ người dùng quan tâm trước.
     */
    private function periods(int $departmentId, $history): array
    {
        $byPeriod = $history->keyBy(fn ($row) => substr((string) $row->period_start, 0, 10));
        $thisPeriod = now()->startOfQuarter()->format('Y-m-d');
        $out = [];

        for ($i = 0; $i < self::PERIOD_COUNT; $i++) {
            $start = now()->startOfQuarter()->subMonthsNoOverflow($i * self::CYCLE_MONTHS)->startOfQuarter();
            $key = $start->format('Y-m-d');
            $row = $byPeriod->get($key);

            $out[] = [
                'key' => $key,
                'label' => $this->periodLabel($start),
                'range' => $this->periodRange($start),
                'is_current' => $key === $thisPeriod,
                'state' => $row ? $row->state : ($key === $thisPeriod ? 'pending' : 'missed'),
                'code' => $row->code ?? null,
                'id' => $row->id ?? null,
                'counted' => $row->counted ?? 0,
                'total' => $row->total ?? 0,
                'diff' => $row->diff ?? 0,
            ];
        }

        return $out;
    }

    /** Phiếu ĐANG KIỂM KÊ của phòng ban - mọi hành động ghi đều phải qua đây. */
    private function openStocktake($id, int $departmentId)
    {
        return DB::table('material_stocktakes')
            ->where('id', (int) $id)
            ->where('department_id', $departmentId)
            ->where('state', 'counting')
            ->where('status_id', 1)
            ->first();
    }

    /** KKVT-<viết tắt phòng>-YYYYQn, thêm hậu tố khi quý đó từng có phiếu bị huỷ. */
    private function nextCode(int $departmentId, $start): string
    {
        $shortName = DB::table('deparments')->where('id', $departmentId)->value('shortName');
        $prefix = 'KKVT-'.(preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $shortName)) ?: $departmentId)
            .'-'.$start->format('Y').'Q'.$start->quarter;

        $used = DB::table('material_stocktakes')
            ->where('department_id', $departmentId)
            ->where('period_start', $start->format('Y-m-d'))
            ->count();

        return $used > 0 ? $prefix.'-'.($used + 1) : $prefix;
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
}
