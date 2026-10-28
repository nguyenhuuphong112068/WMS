<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Support\CompanyContext;
use App\Support\MaterialCalibration;
use App\Support\EstimateSigner;
use App\Support\MaterialPurchasing;
use App\Support\MaterialTransferSignFlow;
use App\Support\MaterialVpp;
use App\Support\ReviewHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TRANG CHỦ - BẢNG TỔNG HỢP
 *
 * Màn hình đầu tiên sau khi đăng nhập, gom ba thứ người dùng cần biết ngay thay vì
 * phải mở lần lượt từng màn hình:
 *
 *  1. ĐỀ NGHỊ CHỜ XEM XÉT - thống kê theo loại các đề nghị đang chờ chính người đăng
 *                       nhập ký duyệt, và đề nghị chờ phòng ban đang chọn xử lý.
 *  2. NHẮC NHỞ TỒN KHO - lô quá hạn / sắp hết hạn, và hoá chất - chuẩn - vật tư có
 *                       tổng tồn rơi xuống dưới ngưỡng phòng ban đã khai.
 *  3. THÔNG BÁO       - thông báo gửi cho người dùng và hoạt động gần đây của hệ thống.
 *
 * TỒN KHO tính đúng công thức của màn hình Tồn:
 *
 *      Tồn của một mã = imports.amount
 *                     + SUM(*_balancings.balancing_amount)
 *                     - SUM(*_exports.amount)      (cả 'export' lẫn 'cancel')
 *
 * chỉ lấy phiếu còn hiệu lực (status_id = 1) và tính đến hôm nay - trang chủ luôn nói
 * về hiện tại nên không có kỳ báo cáo như màn hình Tồn.
 *
 * Ba loại hàng (hoá chất / chất chuẩn / vật tư) dùng chung một bộ bảng nghiệp vụ, chỉ
 * khác tiền tố tên bảng, nên khai báo một lần tại self::KINDS rồi chạy chung một hàm
 * thay vì viết ba lần.
 *
 * Toàn bộ số liệu bó theo PHÒNG BAN ĐANG CHỌN (session('user')['selected_department_id']),
 * trừ danh mục chờ duyệt vì danh mục dùng chung toàn công ty.
 */
class HomeController extends Controller
{
    /** Hạn dùng còn dưới ngần này ngày là "Sắp hết hạn" - lấy đúng ngưỡng của màn hình Tồn. */
    private const NEAR_EXPIRY_DAYS = 30;

    /**
     * Mốc đánh giá hạn dùng đến hạn trong ngần này ngày là việc phải chuẩn bị ngay.
     *
     * Ngắn hơn nhiều ngưỡng 30 ngày của hạn dùng: đánh giá là việc phải bố trí người và
     * chỉ tiêu kiểm nên chỉ nhắc trong tầm một tuần, nhắc sớm quá thì lần nào vào trang
     * chủ cũng thấy, thành ra không ai để ý nữa.
     */
    private const ASSESS_DUE_DAYS = 7;

    /** Chưa khai ngưỡng riêng thì tồn còn dưới ngần này so với lượng nhập là "Sắp hết". */
    private const LOW_STOCK_RATIO = 0.2;

    /** Sai số cho phép khi so tồn với 0 (cột decimal 15,4). */
    private const EPSILON = 0.00005;

    /** Số dòng tối đa hiển thị trên mỗi khối, phần còn lại xem ở màn hình gốc. */
    private const LIMIT = 12;

    /**
     * Bộ bảng nghiệp vụ của từng loại hàng.
     *
     * - names / name_key   : bảng tên hàng và cột khoá ngoại trên bảng danh mục.
     * - internal_expiry    : danh mục có hạn dùng nội bộ hay không (vật tư thì không).
     * - category_code      : bảng danh mục có cột mã hay không (material_categories thì không).
     * - exports_status     : bảng phiếu sử dụng có cột status_id để lọc hay không.
     */
    private const KINDS = [
        'chemical' => [
            'label' => 'Hoá chất',
            'icon' => 'fas fa-flask',
            'imports' => 'chemical_imports',
            'exports' => 'chemical_exports',
            'balancings' => 'chemical_balancings',
            'categories' => 'chemical_categories',
            'departments' => 'chemical_department_categories',
            'names' => 'chem_names',
            'name_key' => 'chem_names_id',
            'internal_expiry' => true,
            'category_code' => true,
            'route' => 'pages.inventory.chemicalInventory.list',
        ],
        'standard' => [
            'label' => 'Chất chuẩn',
            'icon' => 'fas fa-vial',
            'imports' => 'standard_imports',
            'exports' => 'standard_exports',
            // standard_exports không còn cột status_id (bỏ ở migration 2026_08_26_143500),
            // mọi phiếu đã ghi đều trừ tồn - giống StandardInventoryController đang tính
            'exports_status' => false,
            'balancings' => 'standard_balancings',
            'categories' => 'standard_categories',
            'departments' => 'standard_department_categories',
            'names' => 'chem_names',
            'name_key' => 'chem_names_id',
            'internal_expiry' => true,
            'category_code' => true,
            'route' => 'pages.inventory.standardInventory.list',
        ],
        'material' => [
            'label' => 'Vật tư',
            'icon' => 'fas fa-boxes',
            'imports' => 'material_imports',
            'exports' => 'material_exports',
            'balancings' => 'material_balancings',
            'categories' => 'material_categories',
            'departments' => 'material_department_categories',
            'names' => 'material_names',
            'name_key' => 'material_names_id',
            'internal_expiry' => false,
            'category_code' => false,
            'route' => 'pages.inventory.materialInventory.list',
        ],
    ];

    public function showHomeForm()
    {
        $departmentId = $this->departmentId();

        $lots = $this->lots($departmentId);
        $expiryAlerts = $this->expiryAlerts($lots);
        $lowStockAlerts = $this->lowStockAlerts($lots);
        $approvals = $this->reviewRows();
        $assessmentAlerts = $this->assessmentAlerts($departmentId);

        // Thanh Thông báo là lịch sử của chính các đề nghị đang hiện ở đây
        try {
            ReviewHistory::sync($approvals);
        } catch (\Throwable $e) {
            report($e);
        }

        session()->put(['title' => 'TRANG CHỦ']);

        return view('pages.home', [
            'reviewGroups' => $this->reviewGroups($approvals),
            'reviewMatrix' => $this->reviewMatrix($approvals),
            'reviewGroupInfo' => self::REVIEW_GROUPS,
            'reviewObjects' => self::REVIEW_OBJECTS,
            'reviewActors' => self::REVIEW_ACTORS,
            'reviewTotal' => count($approvals),
            // reviewRows() đã xếp chờ lâu nhất lên đầu
            'reviewOldest' => $approvals[0] ?? null,
            'companyName' => CompanyContext::currentName(),
            'approvals' => array_slice($approvals, 0, self::LIMIT),
            'approvalTotal' => count($approvals),
            'waitingMeTotal' => count(array_filter($approvals, fn ($row) => $row['waiting_me'])),
            'expiryAlerts' => array_slice($expiryAlerts, 0, self::LIMIT),
            'expiryTotal' => count($expiryAlerts),
            'expiredTotal' => count(array_filter($expiryAlerts, fn ($row) => $row['level'] === 'expired')),
            'lowStockAlerts' => array_slice($lowStockAlerts, 0, self::LIMIT),
            'lowStockTotal' => count($lowStockAlerts),
            'assessmentAlerts' => array_slice($assessmentAlerts, 0, self::LIMIT),
            'assessmentTotal' => count($assessmentAlerts),
            'assessOverdueTotal' => count(array_filter($assessmentAlerts, fn ($row) => $row['level'] === 'overdue')),
            'assessDueDays' => self::ASSESS_DUE_DAYS,
            'notifications' => $this->notifications(),
            'unreadTotal' => $this->unreadTotal(),
            'nearExpiryDays' => self::NEAR_EXPIRY_DAYS,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TỒN KHO
    |--------------------------------------------------------------------------
    */

    /**
     * Tồn hiện tại của TỪNG LÔ, gộp cả ba loại hàng về một danh sách phẳng.
     *
     * Lấy phiếu nhập, số đã xuất và số đã cân đối bằng ba câu truy vấn cho mỗi loại rồi
     * ghép trong PHP - đúng cách các màn hình Tồn đang làm, số liệu vì thế khớp nhau.
     */
    private function lots(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        $today = now()->startOfDay();
        $lots = [];

        foreach (self::KINDS as $kind => $tables) {
            $used = $this->sumByImport($tables['exports'], 'amount', $departmentId, $tables['exports_status'] ?? true);
            $balanced = $this->sumByImport($tables['balancings'], 'balancing_amount', $departmentId);

            $query = DB::table($tables['imports'].' as i')
                ->leftJoin($tables['categories'].' as c', 'i.category_id', '=', 'c.id')
                ->leftJoin($tables['names'].' as n', 'c.'.$tables['name_key'], '=', 'n.id')
                // Cấu hình riêng của phòng: điều kiện đặt trong JOIN chứ không ở WHERE,
                // để lô của hoá chất phòng chưa khai cấu hình vẫn còn trên danh sách
                ->leftJoin($tables['departments'].' as dp', function ($join) use ($departmentId) {
                    $join->on('dp.category_id', '=', 'i.category_id')
                        ->where('dp.department_id', '=', $departmentId);
                })
                ->leftJoin('units as u', 'dp.unit_id', '=', 'u.id')
                ->select(
                    'i.id',
                    'i.code',
                    'i.category_id',
                    'i.amount',
                    'i.expired_date',
                    'n.name as item_name',
                    'dp.min_stock',
                    'u.short_name as unit'
                )
                ->where('i.department_id', $departmentId)
                ->where('i.status_id', 1);

            if ($tables['internal_expiry']) {
                $query->addSelect('i.internal_expired_date');
            }

            if ($tables['category_code']) {
                $query->addSelect('c.code as category_code');
            }

            foreach ($query->orderBy('i.code')->get() as $row) {
                $remaining = (float) $row->amount
                    + (float) ($balanced[$row->id] ?? 0)
                    - (float) ($used[$row->id] ?? 0);

                // Hạn ÁP DỤNG: hạn nội bộ nếu đã xác định, không thì hạn nhà sản xuất.
                // Hạn nội bộ luôn <= hạn nhà sản xuất nên đây là ngày thực sự chặn sử dụng.
                $expiry = ($tables['internal_expiry'] ? $row->internal_expired_date : null) ?: $row->expired_date;

                $lots[] = [
                    'kind' => $kind,
                    'kind_label' => $tables['label'],
                    'icon' => $tables['icon'],
                    'route' => $tables['route'],
                    'id' => (int) $row->id,
                    'code' => $row->code,
                    'category_id' => (int) $row->category_id,
                    'category_code' => $tables['category_code'] ? $row->category_code : null,
                    'item_name' => $row->item_name ?: 'Chưa có tên',
                    'imported' => (float) $row->amount,
                    'remaining' => max($remaining, 0),
                    'unit' => $row->unit ?: '',
                    'min_stock' => $row->min_stock !== null ? (float) $row->min_stock : null,
                    'expiry' => $expiry ? substr((string) $expiry, 0, 10) : null,
                    'days_to_expiry' => $expiry
                        ? (int) $today->diffInDays(\Carbon\Carbon::parse($expiry)->startOfDay(), false)
                        : null,
                ];
            }
        }

        return $lots;
    }

    /**
     * Tổng của một cột trên bảng con, gom theo import_id.
     *
     * Dùng chung cho *_exports (số đã lấy ra, cả sử dụng lẫn huỷ đều trừ tồn) và
     * *_balancings (số điều chỉnh +/-). Tên bảng và tên cột là hằng khai trong lớp
     * này, không ghép từ dữ liệu người dùng.
     *
     * $hasStatus = false cho bảng không có cột status_id (standard_exports) - khi đó
     * mọi phiếu đã ghi đều được tính.
     *
     * @return array<int,float>
     */
    private function sumByImport(string $table, string $column, int $departmentId, bool $hasStatus = true): array
    {
        $query = DB::table($table)
            ->select('import_id', DB::raw('SUM('.$column.') as total'))
            ->where('department_id', $departmentId);

        if ($hasStatus) {
            $query->where('status_id', 1);
        }

        return $query->groupBy('import_id')
            ->pluck('total', 'import_id')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    /**
     * NHẮC HẠN DÙNG - theo từng lô còn tồn.
     *
     * Lô đã dùng hết thì hạn dùng không còn ý nghĩa nên bỏ qua. Quá hạn xếp trước, sau
     * đó tới lô còn ít ngày nhất.
     */
    private function expiryAlerts(array $lots): array
    {
        $alerts = [];

        foreach ($lots as $lot) {
            if ($lot['remaining'] <= self::EPSILON || $lot['days_to_expiry'] === null) {
                continue;
            }

            if ($lot['days_to_expiry'] < 0) {
                $lot['level'] = 'expired';
                $lot['level_label'] = 'Đã quá hạn '.abs($lot['days_to_expiry']).' ngày';
            } elseif ($lot['days_to_expiry'] <= self::NEAR_EXPIRY_DAYS) {
                $lot['level'] = 'near';
                $lot['level_label'] = $lot['days_to_expiry'] === 0
                    ? 'Hết hạn hôm nay'
                    : 'Còn '.$lot['days_to_expiry'].' ngày';
            } else {
                continue;
            }

            $alerts[] = $lot;
        }

        usort($alerts, fn ($a, $b) => $a['days_to_expiry'] <=> $b['days_to_expiry']);

        return $alerts;
    }

    /**
     * NHẮC TỒN THẤP - theo từng hoá chất / chuẩn / vật tư trong danh mục.
     *
     * Ngưỡng khai ở department_*.min_stock là ngưỡng của CẢ MẶT HÀNG chứ không của một
     * lô, nên phải cộng tồn các lô lại rồi mới so. Mặt hàng phòng chưa khai ngưỡng thì
     * so tạm với 20% tổng lượng đã nhập, giống cách màn hình Tồn đánh dấu "Sắp hết".
     */
    private function lowStockAlerts(array $lots): array
    {
        $totals = [];

        foreach ($lots as $lot) {
            $key = $lot['kind'].'-'.$lot['category_id'];

            if (! isset($totals[$key])) {
                $totals[$key] = [
                    'kind_label' => $lot['kind_label'],
                    'icon' => $lot['icon'],
                    'route' => $lot['route'],
                    'code' => $lot['category_code'],
                    'item_name' => $lot['item_name'],
                    'unit' => $lot['unit'],
                    'min_stock' => $lot['min_stock'],
                    'remaining' => 0.0,
                    'imported' => 0.0,
                    'lots' => 0,
                ];
            }

            $totals[$key]['remaining'] += $lot['remaining'];
            $totals[$key]['imported'] += $lot['imported'];
            $totals[$key]['lots']++;
        }

        $alerts = [];

        foreach ($totals as $row) {
            $threshold = $row['min_stock'] !== null
                ? $row['min_stock']
                : $row['imported'] * self::LOW_STOCK_RATIO;

            if ($threshold <= 0 || $row['remaining'] > $threshold + self::EPSILON) {
                continue;
            }

            $row['threshold'] = $threshold;
            $row['has_min_stock'] = $row['min_stock'] !== null;
            $row['level'] = $row['remaining'] <= self::EPSILON ? 'out' : 'low';
            $row['level_label'] = $row['level'] === 'out' ? 'Hết hàng' : 'Sắp hết';
            $row['percent'] = $threshold > 0
                ? (int) min(round($row['remaining'] / $threshold * 100), 100)
                : 0;

            $alerts[] = $row;
        }

        usort($alerts, fn ($a, $b) => $a['percent'] <=> $b['percent']);

        return $alerts;
    }

    /*
    |--------------------------------------------------------------------------
    | ĐỀ NGHỊ CHỜ XEM XÉT
    |--------------------------------------------------------------------------
    |
    | Gom mọi đề nghị đang dừng chờ người đang đăng nhập về một chỗ, chia hai khối:
    |
    |  - 'sign' : chờ CHÍNH BẠN ký / duyệt - bước ký đang chờ chỉ đích danh mình, hoặc
    |             mình có quyền duyệt / quyết định của loại đề nghị đó.
    |  - 'dept' : chờ PHÒNG BAN ĐANG CHỌN xử lý - cấp phát, nhận hàng, xác nhận huỷ - chỉ
    |             đếm khi người đang đăng nhập có quyền làm bước đó.
    |
    | Điều kiện "chờ mình" đọc đúng như nút Ký / Duyệt của từng màn hình để số trên trang
    | chủ khớp với số người dùng thấy khi bấm vào.
    */

    /** Số đề nghị hiện sẵn trên mỗi thẻ, phần còn lại mở màn hình gốc để xem. */
    private const REVIEW_PREVIEW = 5;

    /** Ai phải làm: chờ chính người đăng nhập ký / duyệt, hoặc chờ phòng ban đang chọn xử lý. */
    public const REVIEW_ACTORS = [
        'sign' => ['label' => 'Chờ bạn ký duyệt', 'short' => 'Bạn ký duyệt', 'icon' => 'fas fa-signature'],
        'dept' => ['label' => 'Chờ phòng ban xử lý', 'short' => 'Phòng xử lý', 'icon' => 'fas fa-people-carry'],
        // Báo cho riêng người đăng nhập (vd kiểm nghiệm viên được kho cấp chuẩn), không phải ký / duyệt
        'me' => ['label' => 'Đã cấp cho bạn', 'short' => 'Cấp cho bạn', 'icon' => 'fas fa-user-check'],
    ];

    /**
     * Đối tượng của đề nghị - mỗi đối tượng một màu cố định (biến --db-group-<color> ở layout/css.blade.php).
     * 'dc' = dữ liệu dùng chung không thuộc riêng loại hàng nào (đơn vị tính, nhà sản xuất...).
     */
    public const REVIEW_OBJECTS = [
        'vt' => ['label' => 'Vật tư', 'icon' => 'fas fa-boxes', 'color' => 'material'],
        'hc' => ['label' => 'Hoá chất', 'icon' => 'fas fa-flask', 'color' => 'chemical'],
        'cc' => ['label' => 'Chất chuẩn', 'icon' => 'fas fa-vial', 'color' => 'standard'],
        'dc' => ['label' => 'Dùng chung', 'icon' => 'fas fa-database', 'color' => 'master'],
    ];

    /**
     * Nhóm nghiệp vụ = hàng của ma trận tổng quan và nhóm card, theo thứ tự hiển thị.
     * direction: đề nghị đi giữa hai phòng - ô ma trận tách ← nhận / → gửi.
     */
    public const REVIEW_GROUPS = [
        'estimate' => ['label' => 'Dự trù', 'icon' => 'fas fa-file-invoice', 'direction' => false],
        'internal' => ['label' => 'Đề nghị nội bộ', 'icon' => 'fas fa-clipboard-list', 'direction' => false],
        'transfer' => ['label' => 'Liên phòng ban', 'icon' => 'fas fa-exchange-alt', 'direction' => true],
        'vpp' => ['label' => 'Văn phòng phẩm', 'icon' => 'fas fa-paperclip', 'direction' => true],
        'quality' => ['label' => 'KS chất lượng / hàng hỏng', 'icon' => 'fas fa-shield-alt', 'direction' => false],
        'data' => ['label' => 'Phê duyệt dữ liệu', 'icon' => 'fas fa-check-double', 'direction' => false],
    ];

    /** Loại đề nghị: thuộc nhóm nghiệp vụ nào, ai phải làm. Card = một loại × một đối tượng. */
    public const REVIEW_TYPES = [
        'estimate_sign' => ['group' => 'estimate', 'actor' => 'sign', 'label' => 'Ký dự trù'],
        'estimate_reception' => ['group' => 'estimate', 'actor' => 'dept', 'label' => 'Dự trù mới chờ tiếp nhận'],
        'estimate_cancel' => ['group' => 'estimate', 'actor' => 'dept', 'label' => 'Đề nghị huỷ mục dự trù'],
        'material_request' => ['group' => 'internal', 'actor' => 'sign', 'label' => 'Ký đề nghị cấp phát'],
        'issue_pending' => ['group' => 'internal', 'actor' => 'dept', 'label' => 'Chờ kho cấp'],
        'issued_to_me' => ['group' => 'internal', 'actor' => 'me', 'label' => 'Chuẩn đã cấp cho bạn'],
        'transfer_sign' => ['group' => 'transfer', 'actor' => 'sign', 'label' => 'Ký đề nghị liên phòng ban'],
        'transfer_issue' => ['group' => 'transfer', 'actor' => 'dept', 'label' => 'Chờ phòng bạn cấp phát'],
        'transfer_receipt' => ['group' => 'transfer', 'actor' => 'dept', 'label' => 'Hàng đã cấp, chờ bạn nhận'],
        'vpp_sign' => ['group' => 'vpp', 'actor' => 'sign', 'label' => 'Ký đề nghị VPP'],
        'vpp_issue' => ['group' => 'vpp', 'actor' => 'dept', 'label' => 'Chờ phòng bạn cấp VPP'],
        'vpp_receipt' => ['group' => 'vpp', 'actor' => 'dept', 'label' => 'VPP đã cấp, chờ bạn nhận'],
        'material_calibration' => ['group' => 'quality', 'actor' => 'sign', 'label' => 'Chờ QA hiệu chuẩn'],
        'material_quarantine' => ['group' => 'quality', 'actor' => 'sign', 'label' => 'Vật tư hỏng chờ quyết định'],
        'chemical_quarantine' => ['group' => 'quality', 'actor' => 'sign', 'label' => 'Hoá chất hỏng chờ quyết định'],
        'standard_quarantine' => ['group' => 'quality', 'actor' => 'sign', 'label' => 'Chất chuẩn hỏng chờ quyết định'],
        'category' => ['group' => 'data', 'actor' => 'sign', 'label' => 'Danh mục chờ duyệt'],
        'category_lead_time' => ['group' => 'data', 'actor' => 'sign', 'label' => 'Mã mới chờ khai thời gian đặt hàng'],
        'master_data' => ['group' => 'data', 'actor' => 'sign', 'label' => 'Dữ liệu gốc chờ duyệt'],
    ];

    /** Đối tượng theo phạm vi quyền materData_<phạm vi>_approve của dữ liệu gốc. */
    private const MASTER_DATA_OBJECTS = ['chemical' => 'hc', 'standard' => 'cc', 'material' => 'vt', 'common' => 'dc'];

    /**
     * Ba loại dự trù dùng chung luồng ký động (trait EstimateSignFlow), chỉ khác bộ bảng.
     * item_fk: cột trên bảng mục trỏ về phiếu - dùng cho đề nghị huỷ mục dự trù.
     * purchasing_col: cột bộ phận mua hàng trên bảng danh mục, null = luôn Cung Ứng.
     */
    private const ESTIMATE_KINDS = [
        'chemical' => [
            'object' => 'hc',
            'label' => 'Dự trù hoá chất',
            'table' => 'chemical_estimates',
            'sign_table' => 'chemical_estimate_signs',
            'sign_fk' => 'chemical_estimate_id',
            'item_table' => 'chemical_estimate_items',
            'item_fk' => 'estimate_list_id',
            'category_table' => 'chemical_categories',
            'purchasing_col' => null,
            'permission' => 'estimate_chemical_sign',
            'purchasing_permission' => 'estimate_chemical_purchasing',
            'route' => 'pages.estimate.chemicalEstimate.',
        ],
        'standard' => [
            'object' => 'cc',
            'label' => 'Dự trù chất chuẩn',
            'table' => 'standard_estimates',
            'sign_table' => 'standard_estimate_signs',
            'sign_fk' => 'standard_estimate_id',
            'item_table' => 'standard_estimate_items',
            'item_fk' => 'standard_estimate_id',
            'category_table' => 'standard_categories',
            'purchasing_col' => null,
            'permission' => 'estimate_standard_sign',
            'purchasing_permission' => 'estimate_standard_purchasing',
            'route' => 'pages.estimate.standardEstimate.',
        ],
        'material' => [
            'object' => 'vt',
            'label' => 'Dự trù vật tư',
            'table' => 'material_estimates',
            'sign_table' => 'material_estimate_signs',
            'sign_fk' => 'material_estimate_id',
            'item_table' => 'material_estimate_items',
            'item_fk' => 'material_estimate_id',
            'category_table' => 'material_categories',
            'purchasing_col' => 'purchasing_department',
            'permission' => 'estimate_material_sign',
            'purchasing_permission' => 'estimate_material_purchasing',
            'route' => 'pages.estimate.materialEstimate.',
        ],
    ];

    /** Tiền tố bộ lọc riêng của từng tab loại đề nghị cấp phát vật tư (MaterialExportController::REQ_TYPE_TABS). */
    private const MATERIAL_REQUEST_TAB_PREFIX = [
        'periodic' => 'reqp_',
        'risk_assessment' => 'reqk_',
        'regular' => 'reqt_',
    ];

    /** Id các phòng ban cùng công ty đang làm việc - nạp một lần cho cả request. */
    private ?array $companyDepartmentIdsCache = null;

    private bool $companyDepartmentIdsLoaded = false;

    /** Đề nghị đang chờ người đăng nhập - thanh Thông báo đồng bộ lịch sử từ đây (ReviewHistory). */
    public function pendingReviews(): array
    {
        return $this->reviewRows();
    }

    /**
     * Mọi đề nghị đang chờ người đang đăng nhập, gộp phẳng một danh sách.
     *
     * Mỗi dòng: type, code, title, meta, since, url. Phiếu chờ lâu nhất lên trước.
     */
    private function reviewRows(): array
    {
        $rows = array_merge(
            $this->estimateSignReviews(),
            $this->materialRequestSignReviews(),
            $this->transferSignReviews(),
            $this->quarantineReviews(),
            $this->chemicalQuarantineReviews(),
            $this->standardQuarantineReviews(),
            $this->calibrationReviews(),
            $this->categoryReviews(),
            $this->leadTimeReviews(),
            $this->masterDataReviews(),
            $this->transferIssueReviews(),
            $this->transferReceiptReviews(),
            $this->issuePendingReviews(),
            $this->standardIssuedToMeReviews(),
            $this->estimateCancelReviews(),
            $this->estimateReceptionReviews()
        );

        usort($rows, fn ($a, $b) => strcmp((string) $a['since'], (string) $b['since']));

        return $rows;
    }

    /**
     * Card đề nghị = một loại × một đối tượng, gom theo nhóm nghiệp vụ (hàng của ma trận).
     * Card không có đề nghị nào thì không hiện; nhóm rỗng cũng bỏ.
     */
    private function reviewGroups(array $rows): array
    {
        $cards = [];

        foreach ($rows as $row) {
            $key = $row['type'].'-'.$row['object'];

            if (! isset($cards[$key])) {
                $type = self::REVIEW_TYPES[$row['type']];
                $cards[$key] = [
                    'key' => $key,
                    'type' => $row['type'],
                    'group' => $type['group'],
                    'actor' => $type['actor'],
                    'object' => $row['object'],
                    'label' => $type['label'],
                    'items' => [],
                    'in' => 0,
                    'out' => 0,
                ];
            }

            $cards[$key]['items'][] = $row;
            $cards[$key]['in'] += $row['direction'] === 'in' ? 1 : 0;
            $cards[$key]['out'] += $row['direction'] === 'out' ? 1 : 0;
        }

        $typeOrder = array_flip(array_keys(self::REVIEW_TYPES));
        $objectOrder = array_flip(array_keys(self::REVIEW_OBJECTS));

        uasort($cards, fn ($a, $b) => [$typeOrder[$a['type']], $objectOrder[$a['object']]]
            <=> [$typeOrder[$b['type']], $objectOrder[$b['object']]]);

        $groups = [];

        foreach (self::REVIEW_GROUPS as $groupKey => $group) {
            $groupCards = [];

            foreach ($cards as $card) {
                if ($card['group'] !== $groupKey) {
                    continue;
                }

                $items = $card['items'];
                $card['count'] = count($items);
                // "Chờ bạn ký duyệt" / "Đã cấp cho bạn" liệt kê ĐỦ từng dòng (trang chủ là nơi duy nhất
                // gom việc của riêng mình ở mọi phòng); việc của phòng chỉ xem trước
                $card['items'] = in_array($card['actor'], ['sign', 'me'], true) ? $items : array_slice($items, 0, self::REVIEW_PREVIEW);
                // Bấm vào thẻ: mở màn hình của đề nghị chờ lâu nhất
                $card['url'] = $items[0]['url'];
                $groupCards[] = $card;
            }

            if ($groupCards) {
                $groups[] = $group + [
                    'key' => $groupKey,
                    'total' => array_sum(array_column($groupCards, 'count')),
                    'cards' => $groupCards,
                ];
            }
        }

        return $groups;
    }

    /**
     * Ma trận tổng quan: [nhóm nghiệp vụ][đối tượng][ai phải làm] => số đề nghị, kèm số theo
     * hướng ← nhận / → gửi. Tách theo ai phải làm để nút gạt ở view cộng lại được.
     */
    private function reviewMatrix(array $rows): array
    {
        $cells = [];

        foreach (array_keys(self::REVIEW_GROUPS) as $group) {
            foreach (array_keys(self::REVIEW_OBJECTS) as $object) {
                foreach (array_keys(self::REVIEW_ACTORS) as $actor) {
                    $cells[$group][$object][$actor] = ['count' => 0, 'in' => 0, 'out' => 0];
                }
            }
        }

        foreach ($rows as $row) {
            $cell = &$cells[$row['group']][$row['object']][$row['actor']];
            $cell['count']++;
            $cell['in'] += $row['direction'] === 'in' ? 1 : 0;
            $cell['out'] += $row['direction'] === 'out' ? 1 : 0;
            unset($cell);
        }

        return $cells;
    }

    /**
     * Một dòng đề nghị. $direction cho đề nghị đi giữa hai phòng: 'in' = phòng khác đề nghị
     * phòng mình (← Từ $partner), 'out' = phòng mình đề nghị phòng khác (→ Đến $partner).
     */
    private function reviewRow(string $type, string $object, ?string $code, ?string $title, ?string $meta, $since, string $url, ?string $direction = null, ?string $partner = null): array
    {
        $info = self::REVIEW_TYPES[$type];

        $row = [
            'type' => $type,
            'object' => $object,
            'group' => $info['group'],
            'actor' => $info['actor'],
            'code' => $code ?: 'NA',
            'title' => $title ?: '',
            'meta' => $meta ?: '',
            'since' => $since,
            'url' => $url,
            'direction' => $direction,
            'partner' => $partner,
            // Khoá cũ của khối "Mục cần duyệt" (đang @if (false) trong view)
            'icon' => self::REVIEW_OBJECTS[$object]['icon'],
            'label' => $info['label'],
            'status_label' => $meta ?: $info['label'],
            'waiting_me' => $info['actor'] === 'sign',
        ];

        // Khoá của dòng tương ứng trong lịch sử thông báo - bấm dòng trên thẻ thì đánh dấu đã đọc
        $row['key'] = ReviewHistory::key($row);

        return $row;
    }

    /* ---------- Chờ bạn ký duyệt ---------- */

    /**
     * Dự trù hoá chất / chất chuẩn / vật tư đang chờ mình ký: bước chỉ đích danh mình, hoặc bước
     * Quản Lý Bộ Phận của phòng mình quản lý.
     *
     * Phạm vi: người phòng thường - công ty của phòng đang chọn; người phòng ban chung (Ban Giám
     * Đốc...) - mọi phòng được gán vai trò, mỗi phiếu xét quyền tại đúng phòng của phiếu
     * (signDepartmentIds + canReviewIn). Bước của phiếu luồng cũ chỉ có role_names thì xét theo vai trò.
     */
    private function estimateSignReviews(): array
    {
        $userId = $this->userId();
        $companyDeptIds = $this->signDepartmentIds();
        $rows = [];

        foreach (self::ESTIMATE_KINDS as $kind) {
            // Người phòng chung ký thay mọi phòng: bước ký đã giao đích danh là đủ thẩm quyền,
            // không xét quyền theo phòng đang chọn trên leftNAV
            if (! $this->isGeneralUser() && ! user_can($kind['permission'])) {
                continue;
            }

            $records = DB::table($kind['table'].' as e')
                ->join($kind['sign_table'].' as s', function ($join) use ($kind) {
                    $join->on('s.'.$kind['sign_fk'], '=', 'e.id')
                        ->on('s.step_no', '=', 'e.current_step')
                        ->where('s.active', 1);
                })
                ->leftJoin('deparments as d', 'e.department_id', '=', 'd.id')
                ->select(
                    'e.id', 'e.code', 'e.month', 'e.year', 'e.department_id', 'e.current_step', 'e.sign_step_count',
                    'e.submitted_at', 'e.created_at',
                    's.user_id as signer_id', 's.role_names',
                    'd.shortName as department_short'
                )
                ->where('e.app_status', 'pending_sign')
                ->where('e.status_id', 1)
                ->where(function ($query) use ($userId) {
                    $query->where('s.user_id', $userId)
                        ->orWhere(fn ($sub) => $sub->whereNull('s.user_id')->whereNotNull('s.role_names'));
                })
                ->when($companyDeptIds !== null, fn ($query) => $query->whereIn('e.department_id', $companyDeptIds))
                ->get();

            foreach ($records as $record) {
                if (! $this->canReviewIn((int) $record->department_id, $kind['permission'])) {
                    continue;
                }

                if (! $record->signer_id) {
                    // Bước Quản Lý Bộ Phận / Ban Giám Đốc: vai trò có phạm vi bao gồm đúng phòng lập
                    // phiếu; phiếu luồng cũ xét theo vai trò
                    $mine = EstimateSigner::isScopedStep($record)
                        ? EstimateSigner::canSign($userId, $record, (int) $record->department_id)
                        : $this->hasAnyRole($record->role_names);

                    if (! $mine) {
                        continue;
                    }
                }

                $rows[] = $this->reviewRow(
                    'estimate_sign',
                    $kind['object'],
                    $record->code,
                    'Kỳ '.str_pad((string) $record->month, 2, '0', STR_PAD_LEFT).'/'.$record->year,
                    $this->joinMeta([$this->stepLabel($record), $record->department_short]),
                    $record->submitted_at ?: $record->created_at,
                    $this->signUrl((int) $record->department_id, $kind['route'].'detail', ['id' => $record->id])
                );
            }
        }

        return $rows;
    }

    /**
     * Đề nghị cấp phát vật tư đang chờ ký.
     *
     * Đọc đúng như MaterialExportController::canSignRow(): bước giao cho danh sách người ký
     * (material_request_sign_candidates) thì mình phải có tên trong đó; không có danh sách
     * thì xét user_id đích danh, phiếu cũ nữa thì xét theo vai trò. Nút Ký còn cần quyền
     * export_material_approve.
     */
    private function materialRequestSignReviews(): array
    {
        if (! $this->isGeneralUser() && ! user_can('export_material_approve')) {
            return [];
        }

        $userId = $this->userId();
        $companyDeptIds = $this->signDepartmentIds();

        $candidateCount = DB::table('material_request_sign_candidates as cc')
            ->selectRaw('COUNT(*)')
            ->whereColumn('cc.sign_id', 's.id')
            ->where('cc.active', 1);

        $records = DB::table('material_request_lists as r')
            ->join('material_request_signs as s', function ($join) {
                $join->on('s.request_list_id', '=', 'r.id')
                    ->on('s.step_no', '=', 'r.current_step')
                    ->where('s.active', 1);
            })
            ->leftJoin('material_request_sign_candidates as mc', function ($join) use ($userId) {
                $join->on('mc.sign_id', '=', 's.id')
                    ->where('mc.active', 1)
                    ->where('mc.user_id', $userId);
            })
            ->leftJoin('deparments as d', 'r.department_id', '=', 'd.id')
            ->select(
                'r.id', 'r.code', 'r.name', 'r.type', 'r.department_id', 'r.current_step', 'r.sign_step_count',
                'r.submitted_at', 'r.created_at',
                's.user_id as signer_id', 's.role_names', 'mc.id as my_candidate_id',
                'd.shortName as department_short'
            )
            ->selectSub($candidateCount, 'candidate_count')
            ->where('r.app_status', 'pending_sign')
            ->where('r.status_id', 1)
            ->where(function ($query) use ($userId) {
                $query->whereNotNull('mc.id')
                    ->orWhere('s.user_id', $userId)
                    ->orWhere(fn ($sub) => $sub->whereNull('s.user_id')->whereNotNull('s.role_names'));
            })
            ->when($companyDeptIds !== null, fn ($query) => $query->whereIn('r.department_id', $companyDeptIds))
            ->get()
            ->unique('id');

        $rows = [];

        foreach ($records as $record) {
            if (! $this->canReviewIn((int) $record->department_id, 'export_material_approve')) {
                continue;
            }

            $mine = (int) $record->candidate_count > 0
                ? (bool) $record->my_candidate_id
                : ($record->signer_id
                    ? (int) $record->signer_id === $userId
                    : $this->hasAnyRole($record->role_names));

            if (! $mine) {
                continue;
            }

            $rows[] = $this->reviewRow(
                'material_request',
                'vt',
                $record->code,
                $record->name,
                $this->joinMeta([$this->stepLabel($record), $record->department_short]),
                $record->submitted_at ?: $record->created_at,
                $this->signUrl((int) $record->department_id, 'pages.export.materialExport.list', ['tab' => $record->type ?: 'regular', 'open' => 'reqDetailModal_'.$record->id])
            );
        }

        return $rows;
    }

    /**
     * Đề nghị chuyển vật tư liên phòng ban / VPP đang chờ mình ký (App\Support\MaterialTransferSignFlow):
     * vai trò của bước với phạm vi bao gồm phòng của bước. Không giới hạn công ty - người ký có
     * thể ở phòng đề nghị, phòng được đề nghị hoặc một phòng cố định (VD Hành Chính - NM1).
     * Link chuyển phòng đang chọn sang PHÒNG CỦA BƯỚC KÝ, nơi tab hiện khối "Đề nghị chờ bạn ký".
     */
    private function transferSignReviews(): array
    {
        $userId = $this->userId();
        $signs = MaterialTransferSignFlow::SIGN_TABLE;

        $records = DB::table('material_transfer_requests as r')
            ->join($signs.' as s', function ($join) {
                $join->on('s.transfer_request_id', '=', 'r.id')
                    ->on('s.step_no', '=', 'r.current_step')
                    ->where('s.active', 1)
                    ->where('s.status', 'pending');
            })
            ->leftJoin('deparments as d', 'd.id', '=', 'r.department_id')
            ->leftJoin('deparments as t', 't.id', '=', 'r.to_department_id')
            ->where('r.status', MaterialTransferSignFlow::STATUS_PENDING_SIGN)
            ->select(
                'r.id', 'r.code', 'r.title', 'r.department_id', 'r.to_department_id', 'r.updated_at', 'r.created_at',
                's.step_no', 's.role_names', 's.department_id as sign_department_id',
                'd.shortName as from_short', 't.shortName as to_short'
            )
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $sign = (object) ['role_names' => $record->role_names, 'department_id' => $record->sign_department_id];

            if (! MaterialTransferSignFlow::canSign($userId, $sign)) {
                continue;
            }

            // Hướng nhìn từ phòng của bước ký: bước ở phòng đề nghị là "gửi đi", còn lại là "nhận về"
            $outgoing = (int) $record->sign_department_id === (int) $record->department_id;

            $rows[] = $this->reviewRow(
                MaterialVpp::isVppDepartment((int) $record->to_department_id) ? 'vpp_sign' : 'transfer_sign',
                'vt',
                $record->code,
                $record->title,
                'Bước '.$record->step_no,
                $record->updated_at ?: $record->created_at,
                $this->signUrl((int) $record->sign_department_id, 'pages.export.materialExport.list', ['tab' => MaterialVpp::tabFor((int) $record->to_department_id), 'open' => 'matTransferDetailModal_'.$record->id]),
                $outgoing ? 'out' : 'in',
                $outgoing ? $record->to_short : $record->from_short
            );
        }

        return $rows;
    }

    /** Phiếu cách ly vật tư hỏng của phòng chưa có quyết định Loại bỏ / Trả về kho. */
    private function quarantineReviews(): array
    {
        $departmentId = $this->departmentId();

        if ($departmentId <= 0 || ! user_can('export_material_quarantine_decide')) {
            return [];
        }

        $records = DB::table('material_quarantines as q')
            ->leftJoin('material_imports as i', 'q.import_id', '=', 'i.id')
            ->leftJoin('material_categories as c', 'i.category_id', '=', 'c.id')
            ->leftJoin('material_names as n', 'c.material_names_id', '=', 'n.id')
            ->select('q.id', 'q.code', 'q.created_at', 'i.code as import_code', 'n.name as material_name')
            ->where('q.department_id', $departmentId)
            ->where('q.status_id', 1)
            ->where('q.app_status', 'quarantined')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $rows[] = $this->reviewRow(
                'material_quarantine',
                'vt',
                $record->code,
                $record->material_name,
                $record->import_code,
                $record->created_at,
                route('pages.export.materialExport.list', ['tab' => 'quarantine', 'qstatus' => 'quarantined'])
            );
        }

        return $rows;
    }

    /** Phiếu cách ly hoá chất hỏng của phòng chưa có quyết định Loại bỏ / Trả về kho. */
    private function chemicalQuarantineReviews(): array
    {
        $departmentId = $this->departmentId();

        if ($departmentId <= 0 || ! user_can('export_chemical_quarantine_decide')) {
            return [];
        }

        $records = DB::table('chemical_quarantines as q')
            ->leftJoin('chemical_imports as i', 'q.import_id', '=', 'i.id')
            ->leftJoin('chemical_categories as c', 'i.category_id', '=', 'c.id')
            ->leftJoin('chem_names as n', 'c.chem_names_id', '=', 'n.id')
            ->select('q.id', 'q.code', 'q.created_at', 'i.code as import_code', 'n.name as chem_name')
            ->where('q.department_id', $departmentId)
            ->where('q.status_id', 1)
            ->where('q.app_status', 'quarantined')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $rows[] = $this->reviewRow(
                'chemical_quarantine',
                'hc',
                $record->code,
                $record->chem_name,
                $record->import_code,
                $record->created_at,
                route('pages.export.chemicalExport.list', ['tab' => 'quarantine', 'qstatus' => 'quarantined'])
            );
        }

        return $rows;
    }

    /** Phiếu cách ly chất chuẩn hỏng của phòng chưa có quyết định Loại bỏ / Trả về kho. */
    private function standardQuarantineReviews(): array
    {
        $departmentId = $this->departmentId();

        if ($departmentId <= 0 || ! user_can('export_standard_quarantine_decide')) {
            return [];
        }

        $records = DB::table('standard_quarantines as q')
            ->leftJoin('standard_imports as i', 'q.import_id', '=', 'i.id')
            ->leftJoin('standard_categories as c', 'i.category_id', '=', 'c.id')
            ->leftJoin('standard_names as n', 'c.chem_names_id', '=', 'n.id')
            ->select('q.id', 'q.code', 'q.created_at', 'i.code as import_code', 'n.name as standard_name')
            ->where('q.department_id', $departmentId)
            ->where('q.status_id', 1)
            ->where('q.app_status', 'quarantined')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $rows[] = $this->reviewRow(
                'standard_quarantine',
                'cc',
                $record->code,
                $record->standard_name,
                $record->import_code,
                $record->created_at,
                route('pages.export.standardExport.list', ['tab' => 'quarantine', 'qstatus' => 'quarantined'])
            );
        }

        return $rows;
    }

    /**
     * Phiếu đề nghị hiệu chuẩn vật tư chờ QA xác nhận (App\Support\MaterialCalibration): mọi phòng QA
     * người dùng vào được và có quyền xác nhận ở đó; bấm phiếu tự chuyển sang phòng QA.
     */
    private function calibrationReviews(): array
    {
        $userId = $this->userId();
        $qaDepartmentIds = DB::table('deparments')->where('EngshortName', 'QA')->where('isActive', 1)->pluck('id');
        $rows = [];
        $seen = [];

        foreach ($qaDepartmentIds as $qaDepartmentId) {
            $qaDepartmentId = (int) $qaDepartmentId;

            if (! user_can_access_department($userId, $qaDepartmentId)
                || ! isset(user_permission_names($userId, $qaDepartmentId)[MaterialCalibration::CONFIRM_PERMISSION])) {
                continue;
            }

            $records = DB::table(MaterialCalibration::TABLE.' as c')
                ->leftJoin('material_categories as mc', 'c.category_id', '=', 'mc.id')
                ->leftJoin('material_names as n', 'mc.material_names_id', '=', 'n.id')
                ->leftJoin('deparments as d', 'c.department_id', '=', 'd.id')
                ->select('c.code', DB::raw('MIN(c.requested_at) as requested_at'), DB::raw('MAX(n.name) as material_name'), DB::raw('MAX(d.name) as department_name'))
                ->whereIn('c.department_id', MaterialCalibration::sourceDepartmentIds($qaDepartmentId))
                ->where('c.status_id', 1)
                ->where('c.app_status', 'pending')
                ->groupBy('c.code')
                ->get();

            foreach ($records as $record) {
                if (isset($seen[$record->code])) {
                    continue;
                }

                $seen[$record->code] = true;
                $rows[] = $this->reviewRow(
                    'material_calibration',
                    'vt',
                    $record->code,
                    $record->material_name,
                    $record->department_name,
                    $record->requested_at,
                    $this->signUrl($qaDepartmentId, 'pages.export.materialExport.list', ['tab' => 'calibration'])
                );
            }
        }

        return $rows;
    }

    /**
     * Danh mục hoá chất / chất chuẩn / vật tư mới khai hoặc vừa sửa, chờ duyệt.
     * Danh mục dùng chung toàn công ty nên KHÔNG lọc theo phòng ban.
     */
    private function categoryReviews(): array
    {
        $tables = [
            ['chemical_categories', 'chem_names', 'chem_names_id', 'category_chemical_approve', 'hc', 'pages.category.chemicalCategory.list'],
            ['standard_categories', 'standard_names', 'chem_names_id', 'category_standard_approve', 'cc', 'pages.category.standardCategory.list'],
            ['material_categories', 'material_names', 'material_names_id', 'category_material_approve', 'vt', 'pages.category.materialCategory.list'],
        ];

        $rows = [];

        foreach ($tables as [$table, $names, $nameKey, $permission, $object, $route]) {
            if (! user_can($permission)) {
                continue;
            }

            $records = DB::table($table.' as c')
                ->leftJoin($names.' as n', 'c.'.$nameKey, '=', 'n.id')
                ->select('c.id', 'c.code', 'c.created_at', 'c.updated_at', 'n.name as item_name')
                ->where('c.status_id', 1)
                ->where('c.app_status', 'pending')
                ->get();

            foreach ($records as $record) {
                $rows[] = $this->reviewRow(
                    'category',
                    $object,
                    $record->code,
                    $record->item_name,
                    null,
                    $record->updated_at ?: $record->created_at,
                    route($route, ['app_status' => 'pending'])
                );
            }
        }

        return $rows;
    }

    /**
     * Mã danh mục công ty mới tạo, chờ người có quyền category_*_lead_time khai / xác nhận thời
     * gian đặt hàng (cờ lead_time_pending - UpdatesCategoryLeadTime). Không lọc theo phòng ban
     * như danh mục chờ duyệt; bấm dòng mở màn hình danh mục với hộp thoại điều chỉnh mở sẵn.
     */
    private function leadTimeReviews(): array
    {
        $tables = [
            ['chemical_categories', 'chem_names', 'chem_names_id', 'category_chemical_lead_time', 'hc', 'pages.category.chemicalCategory.list'],
            ['standard_categories', 'standard_names', 'chem_names_id', 'category_standard_lead_time', 'cc', 'pages.category.standardCategory.list'],
            ['material_categories', 'material_names', 'material_names_id', 'category_material_lead_time', 'vt', 'pages.category.materialCategory.list'],
        ];

        $rows = [];

        foreach ($tables as [$table, $names, $nameKey, $permission, $object, $route]) {
            if (! user_can($permission)) {
                continue;
            }

            $records = DB::table($table.' as c')
                ->leftJoin($names.' as n', 'c.'.$nameKey, '=', 'n.id')
                ->select('c.id', 'c.code', 'c.created_by', 'c.created_at', 'n.name as item_name')
                ->where('c.status_id', 1)
                ->where('c.lead_time_pending', 1)
                ->get();

            foreach ($records as $record) {
                $rows[] = $this->reviewRow(
                    'category_lead_time',
                    $object,
                    $record->code,
                    $record->item_name,
                    $record->created_by ? 'Tạo bởi '.$record->created_by : null,
                    $record->created_at,
                    route($route, ['lead_time' => $record->id])
                );
            }
        }

        return $rows;
    }

    /**
     * Dữ liệu gốc chờ duyệt - đọc theo đúng quyền materData_<phạm vi>_approve mà nút
     * Duyệt ở resources/views/pages/materData/shared/rowActions.blade.php đang kiểm tra.
     */
    private function masterDataReviews(): array
    {
        // [bảng, phạm vi quyền, route, nhãn, cột mã (nếu có), cột tên]
        $tables = [
            ['chem_names', 'chemical', 'chemName', 'Tên hoá chất', null, 'name'],
            ['active_ingredients', 'chemical', 'activeIngredient', 'Hoạt chất', 'code', 'name'],
            ['mixture_hazard_categories', 'chemical', 'mixtureHazardCategory', 'Nhóm nguy hại hỗn hợp', 'code', 'name'],
            ['standard_names', 'standard', 'standardName', 'Tên chất chuẩn', null, 'name'],
            ['material_names', 'material', 'materialName', 'Tên vật tư', null, 'name'],
            ['manufacturers', 'common', 'chemManufacturer', 'Nhà sản xuất', 'short_name', 'name'],
            ['suppliers', 'common', 'chemSupplier', 'Nhà cung cấp', null, 'name'],
            ['packaging_specifications', 'common', 'packagingSpecification', 'Quy cách đóng gói', null, 'name'],
            ['units', 'common', 'unit', 'Đơn vị tính', 'short_name', 'name'],
            ['storage_conditions', 'common', 'storageCondition', 'Điều kiện bảo quản', null, 'name'],
        ];

        $rows = [];

        foreach ($tables as [$table, $scope, $route, $label, $codeColumn, $nameColumn]) {
            if (! user_can('materData_'.$scope.'_approve')) {
                continue;
            }

            $columns = array_values(array_filter(['id', $codeColumn, $nameColumn, 'created_at', 'updated_at']));

            $records = DB::table($table)
                ->select($columns)
                ->where('status_id', 1)
                ->where('app_status', 'pending')
                ->get();

            foreach ($records as $record) {
                $name = (string) $record->{$nameColumn};

                $rows[] = $this->reviewRow(
                    'master_data',
                    self::MASTER_DATA_OBJECTS[$scope],
                    $codeColumn ? ($record->{$codeColumn} ?: $name) : $name,
                    $codeColumn ? $name : null,
                    $label,
                    $record->updated_at ?: $record->created_at,
                    route('pages.materData.'.$route.'.list')
                );
            }
        }

        return $rows;
    }

    /* ---------- Chờ phòng ban xử lý ---------- */

    /**
     * Ba bộ bảng đề nghị chuyển liên phòng ban (hoá chất / chất chuẩn / vật tư): bảng,
     * quyền cấp phát, quyền nhận / trả lại và tab màn hình Sử Dụng tương ứng.
     */
    private function transferKinds(): array
    {
        return [
            [
                'object' => 'hc',
                'requests' => 'chemical_transfer_requests',
                'items' => 'chemical_transfer_items',
                'issue_permission' => 'export_chemical_transfer',
                'receive_permissions' => ['export_chemical_transfer_receive', 'export_chemical_transfer_return'],
                'url' => route('pages.export.chemicalExport.list', ['tab' => 'request']),
            ],
            [
                'object' => 'cc',
                'requests' => 'standard_transfer_requests',
                'items' => 'standard_transfer_items',
                'issue_permission' => 'export_standard_issue',
                'receive_permissions' => ['export_standard_transfer_receive', 'export_standard_transfer_return'],
                'url' => route('pages.export.standardExport.list', ['tab' => 'transfer']),
            ],
            [
                'object' => 'vt',
                'requests' => 'material_transfer_requests',
                'items' => 'material_transfer_items',
                'issue_permission' => 'export_material_transfer',
                'receive_permissions' => ['export_material_transfer_receive', 'export_material_transfer_return'],
                'url' => route('pages.export.materialExport.list', ['tab' => 'transfer']),
                // Đề nghị gửi tới phòng Hành Chính nằm ở tab "Đề nghị VPP" (App\Support\MaterialVpp)
                'vpp_url' => route('pages.export.materialExport.list', ['tab' => MaterialVpp::TAB]),
            ],
        ];
    }

    /** Đề nghị chuyển liên phòng ban gửi TỚI phòng đang chọn (bên giữ hàng), chưa cấp phát xong. */
    private function transferIssueReviews(): array
    {
        $departmentId = $this->departmentId();

        if ($departmentId <= 0) {
            return [];
        }

        $rows = [];

        foreach ($this->transferKinds() as $kind) {
            if (! user_can($kind['issue_permission'])) {
                continue;
            }

            $records = DB::table($kind['requests'].' as t')
                ->leftJoin('deparments as d', 't.department_id', '=', 'd.id')
                ->select('t.id', 't.code', 't.title', 't.created_at', 'd.shortName as from_department')
                ->where('t.to_department_id', $departmentId)
                ->whereIn('t.status', ['pending', 'partial'])
                ->get();

            // Vật tư gửi tới phòng Hành Chính là đề nghị VPP
            $isVpp = isset($kind['vpp_url']) && MaterialVpp::isVppDepartment($departmentId);

            foreach ($records as $record) {
                $rows[] = $this->reviewRow(
                    $isVpp ? 'vpp_issue' : 'transfer_issue',
                    $kind['object'],
                    $record->code,
                    $record->title,
                    null,
                    $record->created_at,
                    $isVpp ? $kind['vpp_url'] : $kind['url'],
                    'in',
                    $record->from_department
                );
            }
        }

        return $rows;
    }

    /** Đề nghị của phòng đang chọn đã được bên kia cấp phát, đang chờ phòng bấm Nhận. */
    private function transferReceiptReviews(): array
    {
        $departmentId = $this->departmentId();

        if ($departmentId <= 0) {
            return [];
        }

        $rows = [];

        foreach ($this->transferKinds() as $kind) {
            if (! user_can_any($kind['receive_permissions'])) {
                continue;
            }

            $records = DB::table($kind['items'].' as i')
                ->join($kind['requests'].' as t', 'i.transfer_request_id', '=', 't.id')
                ->leftJoin('deparments as d', 't.to_department_id', '=', 'd.id')
                ->select(
                    't.id', 't.code', 't.title', 't.to_department_id', 'd.shortName as to_department',
                    DB::raw('MIN(i.issued_at) as first_issued_at'),
                    DB::raw('COUNT(*) as item_count')
                )
                ->where('t.department_id', $departmentId)
                ->where('i.active', 1)
                ->where('i.status', 'issued')
                ->groupBy('t.id', 't.code', 't.title', 't.to_department_id', 'd.shortName')
                ->get();

            foreach ($records as $record) {
                $isVpp = isset($kind['vpp_url']) && MaterialVpp::isVppDepartment((int) $record->to_department_id);

                $rows[] = $this->reviewRow(
                    $isVpp ? 'vpp_receipt' : 'transfer_receipt',
                    $kind['object'],
                    $record->code,
                    $record->title,
                    $record->item_count.' mục chờ nhận',
                    $record->first_issued_at,
                    $isVpp ? $kind['vpp_url'] : $kind['url'],
                    'out',
                    $record->to_department
                );
            }
        }

        return $rows;
    }

    /** Đề nghị cấp phát của phòng đã đủ chữ ký (vật tư) / đã gửi (chất chuẩn), kho chưa cấp đủ. */
    private function issuePendingReviews(): array
    {
        $departmentId = $this->departmentId();

        if ($departmentId <= 0) {
            return [];
        }

        $rows = [];

        if (user_can('export_material_issue')) {
            $records = DB::table('material_request_lists')
                ->select('id', 'code', 'name', 'type', 'issue_status', 'submitted_at', 'created_at', 'updated_at')
                ->where('department_id', $departmentId)
                ->where('status_id', 1)
                ->where('app_status', 'approved')
                ->whereIn('issue_status', ['waiting', 'partial'])
                ->get();

            foreach ($records as $record) {
                $type = array_key_exists((string) $record->type, self::MATERIAL_REQUEST_TAB_PREFIX) ? $record->type : 'regular';

                $rows[] = $this->reviewRow(
                    'issue_pending',
                    'vt',
                    $record->code,
                    $record->name,
                    $record->issue_status === 'partial' ? 'Đã cấp một phần' : null,
                    $record->updated_at ?: $record->created_at,
                    route('pages.export.materialExport.list', [
                        'tab' => $type,
                        self::MATERIAL_REQUEST_TAB_PREFIX[$type].'unissued' => 1,
                    ])
                );
            }
        }

        if (user_can('export_standard_issue')) {
            $records = DB::table('standard_request_lists as r')
                ->leftJoin('groups as g', 'r.group_id', '=', 'g.id')
                ->select('r.id', 'r.code', 'r.status', 'r.created_at', 'g.name as group_name')
                ->where('r.department_id', $departmentId)
                ->whereIn('r.status', ['pending', 'partial'])
                ->get();

            foreach ($records as $record) {
                $rows[] = $this->reviewRow(
                    'issue_pending',
                    'cc',
                    $record->code,
                    $record->group_name ? 'Tổ '.$record->group_name : null,
                    $record->status === 'partial' ? 'Đã cấp một phần' : null,
                    $record->created_at,
                    route('pages.export.standardExport.list', ['tab' => 'request'])
                );
            }
        }

        return $rows;
    }

    /**
     * Ống chuẩn kho đã cấp phát cho người đang đăng nhập - là KNV của dòng đề nghị - mà chưa
     * lập phiếu sử dụng. Cùng điều kiện danh sách "chuẩn đã cấp cho tổ" ở form Sử Dụng
     * (StandardExportController::getIssuedStandards): lập phiếu sử dụng từ ống đó là tự hết.
     *
     * Không lọc phòng đang chọn: báo đích danh KNV, phiếu ở phòng khác thì link đổi phòng
     * (signUrl) rồi mở tab "Sử dụng chuẩn" và tô sáng đúng ống (?use_item=<id dòng đề nghị>).
     */
    private function standardIssuedToMeReviews(): array
    {
        $userId = $this->userId();

        if ($userId <= 0) {
            return [];
        }

        $records = DB::table('standard_request_items as i')
            ->join('standard_request_lists as r', 'i.request_list_id', '=', 'r.id')
            ->leftJoin('standard_categories as c', 'i.category_id', '=', 'c.id')
            ->leftJoin('standard_names as n', 'c.chem_names_id', '=', 'n.id')
            ->select(
                'i.id', 'i.import_code', 'i.issued_amount', 'i.issued_unit', 'i.issued_at', 'i.return_standard',
                'r.code as list_code', 'r.department_id',
                'c.code as category_code', 'n.name as standard_name'
            )
            ->where('i.analyst_id', $userId)
            ->where('i.active', 1)
            ->where('i.status', 'issued')
            ->whereNotNull('i.import_id')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')
                ->from('standard_exports as e')
                ->whereColumn('e.request_item_id', 'i.id')
                ->where('e.type', 'export'))
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $amount = rtrim(rtrim(number_format((float) $record->issued_amount, 4, '.', ','), '0'), '.');

            $rows[] = $this->reviewRow(
                'issued_to_me',
                'cc',
                $record->import_code,
                trim($record->standard_name.($record->category_code ? ' ('.$record->category_code.')' : '')),
                $this->joinMeta([
                    'SL '.trim($amount.' '.$record->issued_unit),
                    'Phiếu '.$record->list_code,
                    $record->return_standard ? 'Trả lại chuẩn sau khi dùng' : null,
                ]),
                $record->issued_at,
                $this->signUrl((int) $record->department_id, 'pages.export.standardExport.list', [
                    'tab' => 'use',
                    'use_item' => $record->id,
                ])
            );
        }

        return $rows;
    }

    /**
     * Phiếu dự trù vừa được Ban Giám Đốc duyệt, chờ bộ phận mua hàng (phòng đang chọn) bấm
     * Tiếp nhận - EstimateItemCancel::receive() lưu người + ngày tiếp nhận.
     */
    private function estimateReceptionReviews(): array
    {
        $departmentId = $this->departmentId();

        if ($departmentId <= 0) {
            return [];
        }

        // Bộ phận mua hàng tiếp nhận phiếu của MỌI công ty; loại hàng không khai bộ phận mua thì chỉ Cung Ứng
        $purchasingKeys = MaterialPurchasing::keysOfDepartment($departmentId);
        $isDefaultPurchaser = in_array(MaterialPurchasing::DEFAULT_KEY, $purchasingKeys, true);
        $rows = [];

        foreach (self::ESTIMATE_KINDS as $kind) {
            if (! $purchasingKeys || (! $kind['purchasing_col'] && ! $isDefaultPurchaser)
                || ! user_can($kind['purchasing_permission'])) {
                continue;
            }

            $lists = DB::table($kind['table'].' as e')
                ->leftJoin('deparments as d', 'e.department_id', '=', 'd.id')
                ->select('e.id', 'e.code', 'e.month', 'e.year', 'e.updated_at', 'd.shortName as department_short')
                ->where('e.app_status', 'approved')
                ->where('e.reception_status', 'waiting')
                // Vật tư: chỉ phiếu có mục do phòng đang chọn phụ trách mua
                ->when($kind['purchasing_col'], function ($query) use ($kind, $purchasingKeys, $isDefaultPurchaser) {
                    $query->whereExists(function ($sub) use ($kind, $purchasingKeys, $isDefaultPurchaser) {
                        $sub->select(DB::raw(1))
                            ->from($kind['item_table'].' as i')
                            ->leftJoin($kind['category_table'].' as c', 'i.category_id', '=', 'c.id')
                            ->whereColumn('i.'.$kind['item_fk'], 'e.id')
                            ->where('i.active', 1)
                            ->where(function ($q) use ($kind, $purchasingKeys, $isDefaultPurchaser) {
                                $q->whereIn('c.'.$kind['purchasing_col'], $purchasingKeys);

                                if ($isDefaultPurchaser) {
                                    $q->orWhereNull('c.'.$kind['purchasing_col']);
                                }
                            });
                    });
                })
                ->get();

            foreach ($lists as $record) {
                $rows[] = $this->reviewRow(
                    'estimate_reception',
                    $kind['object'],
                    $record->code,
                    $kind['label'],
                    $this->joinMeta(['Kỳ '.str_pad($record->month, 2, '0', STR_PAD_LEFT).'/'.$record->year, $record->department_short]),
                    // Mốc chờ: lúc Ban Giám Đốc ký bước cuối (phiếu cập nhật lần cuối)
                    $record->updated_at,
                    route($kind['route'].'detail', ['id' => $record->id, 'from' => 'approved'])
                );
            }
        }

        return $rows;
    }

    /**
     * Mục dự trù đang chờ phòng đang chọn xác nhận huỷ (trait EstimateItemCancel):
     *  - bên PHÒNG ĐỀ NGHỊ: bộ phận mua hàng đã đề nghị huỷ, phòng chưa xác nhận;
     *  - bên BỘ PHẬN MUA HÀNG: phòng đề nghị đã xin huỷ, phòng đang chọn phụ trách mua.
     * Gộp theo phiếu dự trù để mỗi phiếu là một đề nghị.
     */
    private function estimateCancelReviews(): array
    {
        $departmentId = $this->departmentId();

        if ($departmentId <= 0) {
            return [];
        }

        // Bộ phận mua hàng tiếp nhận phiếu của MỌI công ty (NM1 không có bộ phận mua hàng)
        $purchasingKeys = MaterialPurchasing::keysOfDepartment($departmentId);
        $rows = [];

        foreach (self::ESTIMATE_KINDS as $kind) {
            $base = fn () => DB::table($kind['item_table'].' as i')
                ->join($kind['table'].' as e', 'i.'.$kind['item_fk'], '=', 'e.id')
                ->where('e.app_status', 'approved')
                ->where('i.active', 1)
                ->where('i.status_id', 1)
                ->whereNull('i.fulfilled_date');

            // Bên phòng đề nghị
            $requested = $base()
                ->select('e.id', 'e.code', DB::raw('MIN(i.cancel_purchasing_at) as since'), DB::raw('COUNT(*) as item_count'))
                ->where('e.department_id', $departmentId)
                ->whereNotNull('i.cancel_purchasing_at')
                ->whereNull('i.cancel_requester_at')
                ->groupBy('e.id', 'e.code')
                ->get();

            foreach ($requested as $record) {
                $rows[] = $this->reviewRow(
                    'estimate_cancel',
                    $kind['object'],
                    $record->code,
                    $kind['label'],
                    $record->item_count.' mục - bộ phận mua hàng đề nghị huỷ',
                    $record->since,
                    route($kind['route'].'detail', ['id' => $record->id])
                );
            }

            // Bên bộ phận mua hàng: loại hàng không khai bộ phận mua thì chỉ Cung Ứng phụ trách
            $isDefaultPurchaser = in_array(MaterialPurchasing::DEFAULT_KEY, $purchasingKeys, true);

            if (! $purchasingKeys || (! $kind['purchasing_col'] && ! $isDefaultPurchaser)
                || ! user_can($kind['purchasing_permission'])) {
                continue;
            }

            $purchasing = $base()
                ->leftJoin('deparments as d', 'e.department_id', '=', 'd.id')
                ->leftJoin($kind['category_table'].' as c', 'i.category_id', '=', 'c.id')
                ->select(
                    'e.id', 'e.code', 'd.shortName as department_short',
                    DB::raw('MIN(i.cancel_requester_at) as since'), DB::raw('COUNT(*) as item_count')
                )
                ->whereNotNull('i.cancel_requester_at')
                ->whereNull('i.cancel_purchasing_at')
                ->when($kind['purchasing_col'], function ($query) use ($kind, $purchasingKeys, $isDefaultPurchaser) {
                    $query->where(function ($sub) use ($kind, $purchasingKeys, $isDefaultPurchaser) {
                        $sub->whereIn('c.'.$kind['purchasing_col'], $purchasingKeys);

                        if ($isDefaultPurchaser) {
                            $sub->orWhereNull('c.'.$kind['purchasing_col']);
                        }
                    });
                })
                ->groupBy('e.id', 'e.code', 'd.shortName')
                ->get();

            foreach ($purchasing as $record) {
                $rows[] = $this->reviewRow(
                    'estimate_cancel',
                    $kind['object'],
                    $record->code,
                    $kind['label'],
                    $this->joinMeta([$record->item_count.' mục - phòng đề nghị xin huỷ', $record->department_short]),
                    $record->since,
                    // Mở chi tiết phiếu ở chế độ bộ phận mua hàng: nút Đồng ý huỷ / Không đồng ý nằm trên từng mục
                    route($kind['route'].'detail', ['id' => $record->id, 'from' => 'approved'])
                );
            }
        }

        return $rows;
    }

    /* ---------- Tiện ích của phần đề nghị ---------- */

    /** "Bước 2/3" của phiếu đang chờ ký. */
    private function stepLabel($record): string
    {
        return 'Bước '.$record->current_step.($record->sign_step_count ? '/'.$record->sign_step_count : '');
    }

    /** Nối các mẩu mô tả ngắn, bỏ mẩu rỗng. */
    private function joinMeta(array $parts): string
    {
        return implode(' · ', array_filter($parts, fn ($part) => $part !== null && $part !== ''));
    }

    /**
     * Link mở phiếu chờ ký. Phiếu của phòng khác phòng đang chọn thì đi qua route 'switch' để đổi
     * phòng đang chọn trên leftNAV sang phòng của phiếu rồi mở thẳng màn hình - màn hình nghiệp vụ
     * chỉ ký / từ chối được phiếu của phòng đang chọn (đã bỏ tab "Ký duyệt (mọi phòng ban)").
     *
     * Mở THẲNG phiếu: dự trù đi tới trang chi tiết (['id' => ...]); đề nghị vật tư thêm
     * 'open' => id modal chi tiết, màn Sử Dụng Vật Tư tự bật modal đó (MaterialExport/list) -
     * nút Đồng ý / Từ chối nằm trong modal / trang chi tiết.
     */
    private function signUrl(int $recordDepartmentId, string $routeName, array $params): string
    {
        if ($recordDepartmentId > 0
            && $recordDepartmentId !== $this->departmentId()
            && user_can_access_department($this->userId(), $recordDepartmentId)) {
            return route('switch', [
                'selected_department_id' => $recordDepartmentId,
                'redirect' => route($routeName, $params, false),
            ]);
        }

        return route($routeName, $params);
    }

    /** Id phòng ban cùng công ty đang làm việc; null = không giới hạn (phiên chưa có công ty). */
    private function companyDepartmentIds(): ?array
    {
        if (! $this->companyDepartmentIdsLoaded) {
            $this->companyDepartmentIdsCache = CompanyContext::departmentIds(CompanyContext::currentId());
            $this->companyDepartmentIdsLoaded = true;
        }

        return $this->companyDepartmentIdsCache;
    }

    private ?bool $generalUserCache = null;

    /**
     * Người đăng nhập thuộc PHÒNG BAN CHUNG (phòng nhà có deparments.is_general = 0: Ban Giám
     * Đốc, Cung Ứng...) - phòng không có kho riêng, bị ẩn khỏi droplist Chuyển Bộ Phận, ký
     * duyệt cho mọi phòng trong công ty.
     */
    private function isGeneralUser(): bool
    {
        if ($this->generalUserCache === null) {
            $homeDeptId = DB::table('user_management')->where('id', $this->userId())->value('deparment_id');

            $this->generalUserCache = $homeDeptId
                && (int) DB::table('deparments')->where('id', $homeDeptId)->value('is_general') === 0;
        }

        return $this->generalUserCache;
    }

    /**
     * Phạm vi phòng ban của mục "Chờ bạn ký duyệt", không phụ thuộc phòng đang chọn với người
     * phòng chung: là các phòng họ được gán vai trò (Phạm vi phòng ban của từng quyền - xem
     * user_allowed_department_ids; null = mọi phòng). Người phòng thường: công ty của phòng đang chọn.
     */
    private function signDepartmentIds(): ?array
    {
        if (! $this->isGeneralUser()) {
            return $this->companyDepartmentIds();
        }

        $allowed = user_allowed_department_ids($this->userId());

        return $allowed === ['*'] ? null : array_map('intval', $allowed);
    }

    /**
     * Người phòng chung có được xem / ký phiếu của phòng $departmentId không: phải có quyền
     * $permission TẠI ĐÚNG phòng đó (vai trò gán cho phòng đó, hoặc gán cho mọi phòng).
     * Người phòng thường đã xét quyền theo phòng đang chọn ở đầu hàm nên luôn true ở đây.
     */
    private function canReviewIn(int $departmentId, string $permission): bool
    {
        if (! $this->isGeneralUser()) {
            return true;
        }

        return user_can_access_department($this->userId(), $departmentId)
            && isset(user_permission_names($this->userId(), $departmentId)[$permission]);
    }

    /** Vai trò ghi dạng chuỗi "A, B" trên bước ký của phiếu luồng cũ. */
    private function hasAnyRole(?string $roleNames): bool
    {
        $roles = array_values(array_filter(array_map('trim', explode(',', (string) $roleNames))));

        return $roles ? user_has_any_role($this->userId(), $roles) : false;
    }

    /*
    |--------------------------------------------------------------------------
    | NHẮC ĐÁNH GIÁ HẠN DÙNG
    |--------------------------------------------------------------------------
    */

    /**
     * NHẮC ĐÁNH GIÁ - mốc đánh giá hạn dùng sắp phải làm của phòng ban đang chọn.
     *
     * Lấy mốc CHƯA CÓ KẾT QUẢ thuộc phiếu chưa bị Huỷ, có ngày đến hạn từ hôm nay đến
     * hết ASSESS_DUE_DAYS ngày nữa. Mốc đã quá hạn mà vẫn chưa làm cũng gom vào đây -
     * nợ cũ còn gấp hơn việc sắp tới, bỏ ra ngoài thì không ai thấy nữa - và được xếp
     * lên đầu, đánh dấu riêng bằng level 'overdue'.
     *
     * Nghiệp vụ đánh giá hạn dùng nằm ở bảng standard_stability_assessment_* do màn
     * hình Chuẩn Thứ Cấp dựng nên; máy chủ chưa chạy migration đó thì bỏ qua khối này
     * thay vì để trang chủ vỡ.
     */
    private function assessmentAlerts(int $departmentId): array
    {
        if ($departmentId <= 0 || ! $this->hasAssessmentTables()) {
            return [];
        }

        $today = now()->startOfDay();
        $limit = $today->copy()->addDays(self::ASSESS_DUE_DAYS)->format('Y-m-d');

        // standard_categories.chem_names_id trỏ sang standard_names (tên cột giữ từ đầu dự án)
        $rows = DB::table('standard_stability_assessment_item as it')
            ->join('standard_stability_assessment_list as li', 'it.standard_stability_assessment_list_id', '=', 'li.id')
            ->join('standard_imports as i', 'li.import_id', '=', 'i.id')
            ->leftJoin('standard_categories as c', 'i.category_id', '=', 'c.id')
            ->leftJoin('standard_names as n', 'c.chem_names_id', '=', 'n.id')
            ->select(
                'it.id',
                'it.name',
                'it.timepoint',
                'it.due_date',
                'li.id as list_id',
                'i.code as import_code',
                'i.batch_no',
                'c.code as category_code',
                'n.name as item_name'
            )
            ->where('i.department_id', $departmentId)
            // Phiếu huỷ hoặc đã ngưng đánh giá thì mốc còn lại không thực hiện nữa
            ->whereNotIn('li.status', ['Huỷ', 'Dừng Đánh Giá'])
            ->where('it.status', 'Ban Đầu')
            ->whereNotNull('it.due_date')
            ->whereDate('it.due_date', '<=', $limit)
            ->orderBy('it.due_date', 'asc')
            ->orderBy('i.code', 'asc')
            ->get();

        $alerts = [];

        foreach ($rows as $row) {
            $days = (int) $today->diffInDays(\Carbon\Carbon::parse($row->due_date)->startOfDay(), false);

            $alerts[] = [
                'icon' => 'fas fa-clipboard-check',
                'list_id' => $row->list_id,
                'code' => $row->import_code,
                'batch_no' => $row->batch_no,
                'category_code' => $row->category_code,
                'item_name' => $row->item_name ?: 'Chưa có tên chất chuẩn',
                'point' => 'T'.$row->timepoint,
                'point_name' => $row->name,
                'due_date' => $row->due_date,
                'days_to_due' => $days,
                'level' => $days < 0 ? 'overdue' : 'due',
                'level_label' => match (true) {
                    $days < 0 => 'Quá hạn '.abs($days).' ngày',
                    $days === 0 => 'Đến hạn hôm nay',
                    default => 'Còn '.$days.' ngày',
                },
            ];
        }

        return $alerts;
    }

    private function hasAssessmentTables(): bool
    {
        return Schema::hasTable('standard_stability_assessment_item')
            && Schema::hasTable('standard_stability_assessment_list');
    }

    /*
    |--------------------------------------------------------------------------
    | THÔNG BÁO
    |--------------------------------------------------------------------------
    */

    /**
     * Thông báo gửi cho người đang đăng nhập.
     *
     * Hai bảng notifications / notification_recipients do NotificationController quản
     * lý và có thể chưa được tạo trên máy chủ đang chạy, nên kiểm tra trước rồi mới
     * truy vấn - trang chủ không được vỡ chỉ vì phần thông báo chưa dựng xong.
     */
    private function notifications()
    {
        if (! $this->hasNotificationTables()) {
            return collect();
        }

        return DB::table('notification_recipients as nr')
            ->join('notifications as n', 'nr.notification_id', '=', 'n.id')
            ->leftJoin('user_management as u', 'n.sender_id', '=', 'u.id')
            ->select('n.id', 'n.message', 'n.activity_type', 'n.url', 'n.created_at', 'nr.is_read', 'u.fullName as sender_name')
            ->where('nr.user_id', $this->userId())
            ->orderByDesc('n.created_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /** Số thông báo chưa đọc, dùng cho thẻ tổng quan. */
    private function unreadTotal(): int
    {
        if (! $this->hasNotificationTables()) {
            return 0;
        }

        return (int) DB::table('notification_recipients')
            ->where('user_id', $this->userId())
            ->where('is_read', 0)
            ->count();
    }

    private function hasNotificationTables(): bool
    {
        return Schema::hasTable('notifications') && Schema::hasTable('notification_recipients');
    }

    /** Hoạt động gần đây của toàn hệ thống, đọc thẳng từ Audit Trail. */
    private function activities()
    {
        return DB::table('audittriallog')
            ->select('id', 'userName', 'action', 'table_Audit', 'record_Id_AuditTrial', 'created_at')
            ->orderByDesc('created_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | TIỆN ÍCH
    |--------------------------------------------------------------------------
    */

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function userId(): int
    {
        return (int) (session('user')['userId'] ?? 0);
    }
}
