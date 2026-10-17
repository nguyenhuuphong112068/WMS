<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Support\CompanyContext;
use App\Support\EstimateSigner;
use App\Support\MaterialPurchasing;
use App\Support\MaterialTransferSignFlow;
use App\Support\MaterialVpp;
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

        session()->put(['title' => 'TRANG CHỦ']);

        return view('pages.home', [
            'reviewSections' => $this->reviewSections($approvals),
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

    /** Hai khối của bảng đề nghị chờ xem xét, theo thứ tự hiển thị. */
    private const REVIEW_SECTIONS = [
        'sign' => ['label' => 'Chờ bạn ký duyệt', 'icon' => 'fas fa-signature'],
        'dept' => ['label' => 'Chờ phòng ban xử lý', 'icon' => 'fas fa-people-carry'],
    ];

    /** Loại đề nghị, theo thứ tự hiển thị trong từng khối. */
    private const REVIEW_TYPES = [
        'estimate_chemical' => ['section' => 'sign', 'label' => 'Dự trù hoá chất', 'icon' => 'fas fa-flask'],
        'estimate_standard' => ['section' => 'sign', 'label' => 'Dự trù chất chuẩn', 'icon' => 'fas fa-vial'],
        'estimate_material' => ['section' => 'sign', 'label' => 'Dự trù vật tư', 'icon' => 'fas fa-boxes'],
        'material_request' => ['section' => 'sign', 'label' => 'Đề nghị cấp phát vật tư', 'icon' => 'fas fa-clipboard-list'],
        'transfer_sign' => ['section' => 'sign', 'label' => 'Đề nghị liên phòng ban / VPP', 'icon' => 'fas fa-people-arrows'],
        'chemical_disposal' => ['section' => 'sign', 'label' => 'Đợt huỷ hoá chất', 'icon' => 'fas fa-trash-alt'],
        'material_quarantine' => ['section' => 'sign', 'label' => 'Vật tư hỏng chờ quyết định', 'icon' => 'fas fa-gavel'],
        'category' => ['section' => 'sign', 'label' => 'Danh mục chờ duyệt', 'icon' => 'fas fa-clipboard-check'],
        'master_data' => ['section' => 'sign', 'label' => 'Dữ liệu gốc chờ duyệt', 'icon' => 'fas fa-database'],
        'transfer_issue' => ['section' => 'dept', 'label' => 'Đề nghị liên phòng ban chờ cấp phát', 'icon' => 'fas fa-exchange-alt'],
        'transfer_receipt' => ['section' => 'dept', 'label' => 'Hàng liên phòng ban chờ nhận', 'icon' => 'fas fa-inbox'],
        'issue_pending' => ['section' => 'dept', 'label' => 'Đề nghị cấp phát chờ kho cấp', 'icon' => 'fas fa-dolly'],
        'estimate_cancel' => ['section' => 'dept', 'label' => 'Đề nghị huỷ mục dự trù', 'icon' => 'fas fa-ban'],
    ];

    /**
     * Ba loại dự trù dùng chung luồng ký động (trait EstimateSignFlow), chỉ khác bộ bảng.
     * item_fk: cột trên bảng mục trỏ về phiếu - dùng cho đề nghị huỷ mục dự trù.
     * purchasing_col: cột bộ phận mua hàng trên bảng danh mục, null = luôn Cung Ứng.
     */
    private const ESTIMATE_KINDS = [
        'chemical' => [
            'type' => 'estimate_chemical',
            'label' => 'Dự trù hoá chất',
            'table' => 'chemical_estimates',
            'sign_table' => 'chemical_estimate_signs',
            'sign_fk' => 'chemical_estimate_id',
            'item_table' => 'chemical_estimate_items',
            'item_fk' => 'estimate_list_id',
            'category_table' => 'chemical_categories',
            'purchasing_col' => null,
            'permission' => 'estimate_chemical_sign',
            'route' => 'pages.estimate.chemicalEstimate.',
        ],
        'standard' => [
            'type' => 'estimate_standard',
            'label' => 'Dự trù chất chuẩn',
            'table' => 'standard_estimates',
            'sign_table' => 'standard_estimate_signs',
            'sign_fk' => 'standard_estimate_id',
            'item_table' => 'standard_estimate_items',
            'item_fk' => 'standard_estimate_id',
            'category_table' => 'standard_categories',
            'purchasing_col' => null,
            'permission' => 'estimate_standard_sign',
            'route' => 'pages.estimate.standardEstimate.',
        ],
        'material' => [
            'type' => 'estimate_material',
            'label' => 'Dự trù vật tư',
            'table' => 'material_estimates',
            'sign_table' => 'material_estimate_signs',
            'sign_fk' => 'material_estimate_id',
            'item_table' => 'material_estimate_items',
            'item_fk' => 'material_estimate_id',
            'category_table' => 'material_categories',
            'purchasing_col' => 'purchasing_department',
            'permission' => 'estimate_material_sign',
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
            $this->disposalReviews(),
            $this->quarantineReviews(),
            $this->categoryReviews(),
            $this->masterDataReviews(),
            $this->transferIssueReviews(),
            $this->transferReceiptReviews(),
            $this->issuePendingReviews(),
            $this->estimateCancelReviews()
        );

        usort($rows, fn ($a, $b) => strcmp((string) $a['since'], (string) $b['since']));

        return $rows;
    }

    /**
     * Thống kê theo loại đề nghị, chia hai khối 'sign' / 'dept' cho trang chủ.
     * Loại không có đề nghị nào thì không hiện; khối rỗng cũng bỏ.
     */
    private function reviewSections(array $rows): array
    {
        $byType = [];

        foreach ($rows as $row) {
            $byType[$row['type']][] = $row;
        }

        $sections = [];

        foreach (self::REVIEW_SECTIONS as $sectionKey => $section) {
            $types = [];

            foreach (self::REVIEW_TYPES as $typeKey => $type) {
                if ($type['section'] !== $sectionKey || empty($byType[$typeKey])) {
                    continue;
                }

                $items = $byType[$typeKey];

                $types[] = $type + [
                    'key' => $typeKey,
                    'count' => count($items),
                    // "Chờ bạn ký duyệt" liệt kê ĐỦ từng phiếu (đã bỏ tab "Ký duyệt (mọi phòng ban)",
                    // trang chủ là nơi duy nhất gom phiếu chờ ký của mọi phòng); khối khác xem trước
                    'items' => $sectionKey === 'sign' ? $items : array_slice($items, 0, self::REVIEW_PREVIEW),
                    // Bấm vào thẻ: mở màn hình của đề nghị chờ lâu nhất
                    'url' => $items[0]['url'],
                ];
            }

            if ($types) {
                $sections[] = $section + [
                    'key' => $sectionKey,
                    'total' => array_sum(array_column($types, 'count')),
                    'types' => $types,
                ];
            }
        }

        return $sections;
    }

    /** Một dòng đề nghị, đủ khoá cho cả bảng thống kê lẫn khối "Mục cần duyệt" đang tạm ẩn. */
    private function reviewRow(string $type, ?string $code, ?string $title, ?string $meta, $since, string $url): array
    {
        $info = self::REVIEW_TYPES[$type];

        return [
            'type' => $type,
            'code' => $code ?: 'NA',
            'title' => $title ?: '',
            'meta' => $meta ?: '',
            'since' => $since,
            'url' => $url,
            // Khoá cũ của khối "Mục cần duyệt" (đang @if (false) trong view)
            'group' => self::REVIEW_SECTIONS[$info['section']]['label'],
            'icon' => $info['icon'],
            'label' => $info['label'],
            'status_label' => $meta ?: $info['label'],
            'waiting_me' => $info['section'] === 'sign',
        ];
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
                    $kind['type'],
                    $record->code,
                    'Kỳ '.str_pad((string) $record->month, 2, '0', STR_PAD_LEFT).'/'.$record->year,
                    $this->joinMeta([$this->stepLabel($record), $record->department_short]),
                    $record->submitted_at ?: $record->created_at,
                    $this->signUrl((int) $record->department_id, $kind['route'].'list', 'list')
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
                $record->code,
                $record->name,
                $this->joinMeta([$this->stepLabel($record), $record->department_short]),
                $record->submitted_at ?: $record->created_at,
                $this->signUrl((int) $record->department_id, 'pages.export.materialExport.list', $record->type ?: 'regular')
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
                'r.id', 'r.code', 'r.title', 'r.to_department_id', 'r.updated_at', 'r.created_at',
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

            $rows[] = $this->reviewRow(
                'transfer_sign',
                $record->code,
                $record->title,
                $this->joinMeta(['Bước '.$record->step_no, $record->from_short.' → '.$record->to_short]),
                $record->updated_at ?: $record->created_at,
                $this->signUrl((int) $record->sign_department_id, 'pages.export.materialExport.list', MaterialVpp::tabFor((int) $record->to_department_id))
            );
        }

        return $rows;
    }

    /** Đợt huỷ hoá chất của phòng đã trình, chờ người có quyền ghi quyết định huỷ. */
    private function disposalReviews(): array
    {
        $departmentId = $this->departmentId();

        if ($departmentId <= 0 || ! user_can('export_chemical_disposal_decide')) {
            return [];
        }

        $records = DB::table('chemical_disposals')
            ->select('id', 'code', 'period_month', 'period_year', 'submitted_at', 'created_at')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->where('app_status', 'pending')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $rows[] = $this->reviewRow(
                'chemical_disposal',
                $record->code,
                'Đợt '.str_pad((string) $record->period_month, 2, '0', STR_PAD_LEFT).'/'.$record->period_year,
                'Chờ quyết định huỷ',
                $record->submitted_at ?: $record->created_at,
                route('pages.export.chemicalExport.list', ['tab' => 'disposal'])
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
                $record->code,
                $record->material_name,
                $record->import_code,
                $record->created_at,
                route('pages.export.materialExport.list', ['tab' => 'quarantine', 'qstatus' => 'quarantined'])
            );
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
            ['chemical_categories', 'chem_names', 'chem_names_id', 'category_chemical_approve', 'Danh mục hoá chất', 'pages.category.chemicalCategory.list'],
            ['standard_categories', 'standard_names', 'chem_names_id', 'category_standard_approve', 'Danh mục chất chuẩn', 'pages.category.standardCategory.list'],
            ['material_categories', 'material_names', 'material_names_id', 'category_material_approve', 'Danh mục vật tư', 'pages.category.materialCategory.list'],
        ];

        $rows = [];

        foreach ($tables as [$table, $names, $nameKey, $permission, $label, $route]) {
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
                    $record->code,
                    $record->item_name,
                    $label,
                    $record->updated_at ?: $record->created_at,
                    route($route)
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
                'label' => 'Hoá chất',
                'requests' => 'chemical_transfer_requests',
                'items' => 'chemical_transfer_items',
                'issue_permission' => 'export_chemical_transfer',
                'receive_permissions' => ['export_chemical_transfer_receive', 'export_chemical_transfer_return'],
                'url' => route('pages.export.chemicalExport.list', ['tab' => 'request']),
            ],
            [
                'label' => 'Chất chuẩn',
                'requests' => 'standard_transfer_requests',
                'items' => 'standard_transfer_items',
                'issue_permission' => 'export_standard_issue',
                'receive_permissions' => ['export_standard_transfer_receive', 'export_standard_transfer_return'],
                'url' => route('pages.export.standardExport.list', ['tab' => 'transfer']),
            ],
            [
                'label' => 'Vật tư',
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

            foreach ($records as $record) {
                $rows[] = $this->reviewRow(
                    'transfer_issue',
                    $record->code,
                    $record->title,
                    $this->joinMeta([$kind['label'], $record->from_department ? 'từ '.$record->from_department : null]),
                    $record->created_at,
                    isset($kind['vpp_url']) && MaterialVpp::isVppDepartment($departmentId) ? $kind['vpp_url'] : $kind['url']
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
                $rows[] = $this->reviewRow(
                    'transfer_receipt',
                    $record->code,
                    $record->title,
                    $this->joinMeta([$kind['label'], $record->item_count.' mục chờ nhận']),
                    $record->first_issued_at,
                    isset($kind['vpp_url']) && MaterialVpp::isVppDepartment((int) $record->to_department_id) ? $kind['vpp_url'] : $kind['url']
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
                    $record->code,
                    $record->name,
                    $this->joinMeta(['Vật tư', $record->issue_status === 'partial' ? 'Đã cấp một phần' : null]),
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
                    $record->code,
                    $record->group_name ? 'Tổ '.$record->group_name : null,
                    $this->joinMeta(['Chất chuẩn', $record->status === 'partial' ? 'Đã cấp một phần' : null]),
                    $record->created_at,
                    route('pages.export.standardExport.list', ['tab' => 'request'])
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
                    $record->code,
                    $kind['label'],
                    $record->item_count.' mục - bộ phận mua hàng đề nghị huỷ',
                    $record->since,
                    route($kind['route'].'detail', ['id' => $record->id])
                );
            }

            // Bên bộ phận mua hàng: loại hàng không khai bộ phận mua thì chỉ Cung Ứng phụ trách
            $isDefaultPurchaser = in_array(MaterialPurchasing::DEFAULT_KEY, $purchasingKeys, true);

            if (! $purchasingKeys || (! $kind['purchasing_col'] && ! $isDefaultPurchaser)) {
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
                    $record->code,
                    $kind['label'],
                    $this->joinMeta([$record->item_count.' mục - phòng đề nghị xin huỷ', $record->department_short]),
                    $record->since,
                    route($kind['route'].'list', ['tab' => 'purchasing'])
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
     * phòng đang chọn trên leftNAV sang phòng của phiếu rồi mở thẳng tab của màn hình - màn hình
     * nghiệp vụ chỉ ký / từ chối được phiếu của phòng đang chọn (đã bỏ tab "Ký duyệt (mọi phòng ban)").
     */
    private function signUrl(int $recordDepartmentId, string $routeName, string $ownTab): string
    {
        if ($recordDepartmentId > 0
            && $recordDepartmentId !== $this->departmentId()
            && user_can_access_department($this->userId(), $recordDepartmentId)) {
            return route('switch', [
                'selected_department_id' => $recordDepartmentId,
                'redirect' => route($routeName, ['tab' => $ownTab], false),
            ]);
        }

        return route($routeName, ['tab' => $ownTab]);
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
