<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * HOÁ CHẤT / CHẤT CHUẨN CẦN DỰ TRÙ - tab "Danh sách hoá chất cần dự trù" (Dự Trù Hoá Chất) và
 * "Danh sách chất chuẩn cần dự trù" (Dự Trù Chất Chuẩn), cùng cách làm với tab "Danh sách vật
 * tư cần dự trù" của Dự Trù Vật Tư (App\Support\MaterialWatchlist) nhưng chỉ một nguồn:
 *
 *   Mã trong danh mục của phòng đang tồn DƯỚI NGƯỠNG TỐI THIỂU đã khai (<loại>_department_
 *   categories.min_stock) - tồn tính qua App\Support\MaxStockWarning, đúng công thức màn Tồn Kho.
 *
 * Mã đã nằm trong một phiếu dự trù chưa xong (mục còn hiệu lực, chưa giao, phiếu chưa huỷ - kể
 * cả Nháp / Chờ ký / Bị từ chối) thì gắn mã phiếu và khoá chọn lại - xem inProgress().
 */
class EstimateWatchlist
{
    /** Cấu hình từng loại hàng: bảng danh mục phòng / danh mục công ty / tên / phiếu dự trù. */
    private const KINDS = [
        'chemical' => [
            'department_table' => 'chemical_department_categories',
            'category_table' => 'chemical_categories',
            'name_table' => 'chem_names',
            'name_fk' => 'chem_names_id',
            'list_table' => 'chemical_estimates',
            'item_table' => 'chemical_estimate_items',
            'item_fk' => 'estimate_list_id',
        ],
        'standard' => [
            'department_table' => 'standard_department_categories',
            'category_table' => 'standard_categories',
            'name_table' => 'standard_names',
            'name_fk' => 'chem_names_id',
            'list_table' => 'standard_estimates',
            'item_table' => 'standard_estimate_items',
            'item_fk' => 'standard_estimate_id',
        ],
    ];

    /**
     * Danh sách cho tab: mỗi dòng là một mã danh mục của phòng đang dưới ngưỡng tối thiểu, kèm
     * tồn hiện tại, ngưỡng, đơn vị của phòng và các phiếu dự trù chưa xong đang chứa mã đó.
     */
    public static function forTab(string $kind, int $departmentId)
    {
        $cfg = self::KINDS[$kind];
        $dept = $cfg['department_table'];
        $cat = $cfg['category_table'];

        $query = DB::table($dept)
            ->join($cat, $dept.'.category_id', '=', $cat.'.id')
            ->leftJoin($cfg['name_table'], $cat.'.'.$cfg['name_fk'], '=', $cfg['name_table'].'.id')
            ->leftJoin('manufacturers', $cat.'.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', $dept.'.unit_id', '=', 'units.id')
            ->select(
                $cat.'.id as category_id',
                $cat.'.code',
                $cat.'.lead_time_days',
                $cfg['name_table'].'.name as name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                $dept.'.min_stock',
                $dept.'.max_stock',
                $dept.'.unit_id',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where($dept.'.department_id', $departmentId)
            ->where($dept.'.status_id', 1)
            ->where($cat.'.status_id', 1)
            ->where($cat.'.app_status', 'approved')
            ->whereNotNull($dept.'.min_stock')
            ->where($dept.'.min_stock', '>', 0)
            ->orderBy($cfg['name_table'].'.name', 'asc');

        if ($kind === 'chemical') {
            $query->addSelect($cat.'.type')
                ->selectSub(DepartmentChemical::casNoSubquery($cat.'.chem_names_id'), 'cas_no');
        } else {
            $query->addSelect($cat.'.version', $cat.'.groups', $cat.'.cas_no');
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return $rows;
        }

        $onHand = MaxStockWarning::onHandOf($kind, $departmentId);
        $progress = self::inProgress($kind, $departmentId);

        return $rows
            ->map(function ($row) use ($onHand, $progress) {
                $row->on_hand = (float) ($onHand[(int) $row->category_id] ?? 0);
                $row->unit = $row->unit_short_name ?: $row->unit_name;
                $row->in_progress = array_values($progress[(int) $row->category_id] ?? []);

                return $row;
            })
            ->filter(fn ($row) => $row->on_hand < (float) $row->min_stock)
            ->values();
    }

    /**
     * Dòng nào trong $items (form lập phiếu từ tab) đã có phiếu dự trù chưa xong thì báo lỗi -
     * chặn ở server phòng khi trang đang mở từ trước (JS đã khoá ô chọn).
     *
     * @return array<string, string> [khoá lỗi items.i.category_id => thông báo]
     */
    public static function inProgressErrors(string $kind, int $departmentId, array $items, string $itemLabel): array
    {
        $progress = self::inProgress($kind, $departmentId);
        $errors = [];

        foreach ($items as $index => $item) {
            $refs = $progress[(int) ($item['category_id'] ?? 0)] ?? [];

            if ($refs) {
                $ref = reset($refs);
                $errors['items.'.$index.'.category_id'] = ucfirst($itemLabel).' dòng '.($index + 1).' đã có trong phiếu dự trù '
                    .$ref['code'].' ('.$ref['status'].'), không lập lại được.';
            }
        }

        return $errors;
    }

    /**
     * Mã danh mục ĐANG ĐƯỢC DỰ TRÙ của phòng: dòng phiếu dự trù còn hiệu lực, chưa huỷ
     * (status_id = 1), chưa xác nhận giao (fulfilled_date NULL), thuộc phiếu chưa huỷ.
     *
     * @return array<int, array<string, array>> [category_id => [khoá => {id, code, status}]]
     */
    public static function inProgress(string $kind, int $departmentId): array
    {
        $cfg = self::KINDS[$kind];
        $appStatuses = config('estimate.app_statuses');
        $out = [];

        $rows = DB::table($cfg['item_table'].' as i')
            ->join($cfg['list_table'].' as e', 'e.id', '=', 'i.'.$cfg['item_fk'])
            ->where('e.department_id', $departmentId)
            ->where('e.status_id', 1)
            ->where('e.app_status', '!=', 'cancelled')
            ->where('i.active', 1)
            ->where('i.status_id', 1)
            ->whereNull('i.fulfilled_date')
            ->whereNotNull('i.category_id')
            ->orderBy('e.id', 'desc')
            ->get(['i.category_id', 'e.id', 'e.code', 'e.app_status']);

        foreach ($rows as $row) {
            $out[(int) $row->category_id]['e'.$row->id] ??= [
                'id' => (int) $row->id,
                'code' => $row->code,
                'status' => $appStatuses[$row->app_status] ?? $row->app_status,
            ];
        }

        return $out;
    }
}
