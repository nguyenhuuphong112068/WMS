<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TRANG CHỦ - BẢNG TỔNG HỢP
 *
 * Màn hình đầu tiên sau khi đăng nhập, gom ba thứ người dùng cần biết ngay thay vì
 * phải mở lần lượt từng màn hình:
 *
 *  1. MỤC CẦN DUYỆT   - phiếu đang nằm ở một bước ký / chờ trả lời. Phiếu nào rơi
 *                       đúng vai trò của người đang đăng nhập thì gắn thêm "Chờ bạn ký".
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
        $approvals = $this->approvals($departmentId);
        $assessmentAlerts = $this->assessmentAlerts($departmentId);

        session()->put(['title' => 'TRANG CHỦ']);

        return view('pages.home', [
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
    | MỤC CẦN DUYỆT
    |--------------------------------------------------------------------------
    */

    /**
     * Gom mọi phiếu đang chờ một chữ ký hoặc một câu trả lời về cùng một danh sách.
     *
     * Mỗi dòng có 'waiting_me' = phiếu đang dừng đúng ở vai trò của người đang đăng
     * nhập, để trang chủ tách được "việc của tôi" khỏi "việc của phòng".
     */
    private function approvals(int $departmentId): array
    {
        $rows = array_merge(
            $this->estimateApprovals($departmentId),
            $this->materialRequestApprovals($departmentId),
            $this->standardRequestApprovals($departmentId),
            $this->transferRequestApprovals($departmentId),
            $this->disposalApprovals($departmentId),
            $this->categoryApprovals()
        );

        // Việc của mình lên trước, sau đó phiếu chờ lâu nhất lên trước
        usort($rows, function ($a, $b) {
            if ($a['waiting_me'] !== $b['waiting_me']) {
                return $a['waiting_me'] ? -1 : 1;
            }

            return strcmp((string) $a['since'], (string) $b['since']);
        });

        return $rows;
    }

    /** Dự trù hoá chất / chất chuẩn / vật tư đang nằm ở một bước trình ký. */
    private function estimateApprovals(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        $steps = config('estimate.sign_steps');
        $labels = config('estimate.app_statuses');

        $tables = [
            'chemical' => ['chemical_estimates', 'Dự trù hoá chất', 'pages.estimate.chemicalEstimate.detail'],
            'standard' => ['standard_estimates', 'Dự trù chất chuẩn', 'pages.estimate.standardEstimate.detail'],
            'material' => ['material_estimates', 'Dự trù vật tư', 'pages.estimate.materialEstimate.detail'],
        ];

        $rows = [];

        foreach ($tables as [$table, $label, $route]) {
            $records = DB::table($table)
                ->select('id', 'code', 'month', 'year', 'app_status', 'submitted_at', 'created_at')
                ->where('department_id', $departmentId)
                ->where('status_id', 1)
                ->whereIn('app_status', ['pending_manager', 'pending_director'])
                ->orderBy('submitted_at')
                ->get();

            foreach ($records as $record) {
                $step = $record->app_status === 'pending_manager' ? 'manager' : 'director';

                $rows[] = [
                    'group' => 'Trình ký',
                    'icon' => 'fas fa-file-signature',
                    'label' => $label,
                    'code' => $record->code,
                    'title' => 'Kỳ '.str_pad((string) $record->month, 2, '0', STR_PAD_LEFT).'/'.$record->year,
                    'status_label' => $labels[$record->app_status] ?? $record->app_status,
                    'since' => $record->submitted_at ?: $record->created_at,
                    'url' => route($route, ['id' => $record->id]),
                    'waiting_me' => $this->canSign($steps[$step]['roles']),
                ];
            }
        }

        return $rows;
    }

    /** Đề nghị cấp phát vật tư đang chờ Trưởng/Phó Phòng hoặc Ban Giám Đốc ký. */
    private function materialRequestApprovals(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        $steps = config('estimate.sign_steps');
        $labels = config('estimate.app_statuses');

        $records = DB::table('material_request_lists as r')
            ->leftJoin('groups as g', 'r.group_id', '=', 'g.id')
            ->select('r.id', 'r.code', 'r.app_status', 'r.submitted_at', 'r.created_at', 'g.name as group_name')
            ->where('r.department_id', $departmentId)
            ->where('r.status_id', 1)
            ->whereIn('r.app_status', ['pending_manager', 'pending_director'])
            ->orderBy('r.submitted_at')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $step = $record->app_status === 'pending_manager' ? 'manager' : 'director';

            $rows[] = [
                'group' => 'Trình ký',
                'icon' => 'fas fa-clipboard-list',
                'label' => 'Đề nghị cấp phát vật tư',
                'code' => $record->code,
                'title' => $record->group_name ? 'Tổ '.$record->group_name : 'Chưa rõ tổ đề nghị',
                'status_label' => $labels[$record->app_status] ?? $record->app_status,
                'since' => $record->submitted_at ?: $record->created_at,
                'url' => route('pages.export.materialExport.list'),
                'waiting_me' => $this->canSign($steps[$step]['roles']),
            ];
        }

        return $rows;
    }

    /** Đề nghị cấp phát chuẩn của các Tổ, kho chưa cấp phát. */
    private function standardRequestApprovals(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        $records = DB::table('standard_request_lists as r')
            ->leftJoin('groups as g', 'r.group_id', '=', 'g.id')
            ->select('r.id', 'r.code', 'r.created_at', 'g.name as group_name')
            ->where('r.department_id', $departmentId)
            ->where('r.status', 'pending')
            ->orderBy('r.created_at')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $rows[] = [
                'group' => 'Chờ xử lý',
                'icon' => 'fas fa-vial',
                'label' => 'Đề nghị cấp phát chuẩn',
                'code' => $record->code,
                'title' => $record->group_name ? 'Tổ '.$record->group_name : 'Chưa rõ tổ đề nghị',
                'status_label' => 'Chờ kho cấp phát',
                'since' => $record->created_at,
                'url' => route('pages.export.standardExport.list'),
                'waiting_me' => false,
            ];
        }

        return $rows;
    }

    /** Đề nghị chuyển hoá chất mà phòng ban đang chọn là bên GIỮ HÀNG, phải trả lời. */
    private function transferRequestApprovals(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        $records = DB::table('chemical_transfer_requests as t')
            ->leftJoin('deparments as d', 't.department_id', '=', 'd.id')
            ->leftJoin('chemical_categories as c', 't.category_id', '=', 'c.id')
            ->leftJoin('chem_names as n', 'c.chem_names_id', '=', 'n.id')
            ->select('t.id', 't.amount', 't.needed_date', 't.created_at', 'd.name as from_department', 'n.name as chem_name')
            ->where('t.to_department_id', $departmentId)
            ->where('t.status_id', 1)
            ->where('t.app_status', 'pending')
            ->orderBy('t.created_at')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $rows[] = [
                'group' => 'Chờ xử lý',
                'icon' => 'fas fa-exchange-alt',
                'label' => 'Đề nghị chuyển hoá chất',
                'code' => $record->chem_name ?: 'Hoá chất',
                'title' => 'Từ '.($record->from_department ?: 'phòng ban khác')
                    .($record->needed_date ? ' - cần ngày '.\Carbon\Carbon::parse($record->needed_date)->format('d/m/Y') : ''),
                'status_label' => 'Chờ phòng bạn trả lời',
                'since' => $record->created_at,
                'url' => route('pages.export.chemicalExport.list'),
                'waiting_me' => true,
            ];
        }

        return $rows;
    }

    /** Đợt huỷ hoá chất đã trình, đang chờ TP. ĐBCL và Ban Giám Đốc quyết định. */
    private function disposalApprovals(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        $records = DB::table('chemical_disposals')
            ->select('id', 'code', 'period_month', 'period_year', 'submitted_at', 'created_at')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->where('app_status', 'pending')
            ->orderBy('submitted_at')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $rows[] = [
                'group' => 'Trình ký',
                'icon' => 'fas fa-trash-alt',
                'label' => 'Đợt huỷ hoá chất',
                'code' => $record->code,
                'title' => 'Đợt '.str_pad((string) $record->period_month, 2, '0', STR_PAD_LEFT).'/'.$record->period_year,
                'status_label' => 'Chờ quyết định huỷ',
                'since' => $record->submitted_at ?: $record->created_at,
                'url' => route('pages.export.chemicalExport.list'),
                'waiting_me' => $this->canSign(['Ban Giám Đốc', 'TP. ĐBCL', 'ĐBCL', 'QA']),
            ];
        }

        return $rows;
    }

    /**
     * Danh mục hoá chất / chuẩn / vật tư mới khai, chờ duyệt.
     *
     * Danh mục dùng chung toàn công ty nên KHÔNG lọc theo phòng ban.
     */
    private function categoryApprovals(): array
    {
        $tables = [
            ['chemical_categories', 'chem_names', 'chem_names_id', true, 'Danh mục hoá chất', 'fas fa-flask', 'pages.category.chemicalCategory.list'],
            ['standard_categories', 'chem_names', 'chem_names_id', true, 'Danh mục chất chuẩn', 'fas fa-vial', 'pages.category.standardCategory.list'],
            ['material_categories', 'material_names', 'material_names_id', false, 'Danh mục vật tư', 'fas fa-boxes', 'pages.category.materialCategory.list'],
        ];

        $rows = [];

        foreach ($tables as [$table, $names, $nameKey, $hasCode, $label, $icon, $route]) {
            $query = DB::table($table.' as c')
                ->leftJoin($names.' as n', 'c.'.$nameKey, '=', 'n.id')
                ->select('c.id', 'c.created_at', 'c.created_by', 'n.name as item_name')
                ->where('c.status_id', 1)
                ->where('c.app_status', 'pending')
                ->orderBy('c.created_at');

            if ($hasCode) {
                $query->addSelect('c.code');
            }

            foreach ($query->get() as $record) {
                $rows[] = [
                    'group' => 'Chờ duyệt',
                    'icon' => $icon,
                    'label' => $label,
                    'code' => $hasCode ? $record->code : ($record->item_name ?: 'Danh mục mới'),
                    'title' => $hasCode ? ($record->item_name ?: 'Chưa có tên') : 'Người khai: '.($record->created_by ?: 'NA'),
                    'status_label' => 'Chờ duyệt danh mục',
                    'since' => $record->created_at,
                    'url' => route($route),
                    'waiting_me' => $this->canSign(['Trưởng Phòng', 'Phó Phòng', 'Phó Trưởng Phòng']),
                ];
            }
        }

        return $rows;
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

    private function canSign(array $roles): bool
    {
        return user_has_any_role($this->userId(), $roles);
    }
}
