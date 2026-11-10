<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Support\DepartmentChemical;
use App\Support\DepartmentMaterial;
use App\Support\DepartmentStandard;
use App\Support\ZoneType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TRA CỨU TỒN KHO CÔNG KHAI (không cần đăng nhập) - HOÁ CHẤT / CHẤT CHUẨN / VẬT TƯ
 *
 * Mở từ nút "Tra cứu tồn kho" ở trang đăng nhập. Người dùng chọn MỘT phòng ban, gõ tên, mã
 * danh mục hoặc mã lô (quét được mã trên nhãn bằng camera); trang trả về các VỊ TRÍ (Kho / Phòng
 * / Kệ-Tủ / Vị trí) đang chứa hàng đó kèm SỐ LƯỢNG TỒN, xem được 3 kiểu như tab "Theo định khu"
 * của màn hình Tồn Kho: Thẻ, 3D từng kệ, Mặt bằng cả kho/phòng (pages.inventory.shared.zone3d).
 *
 * Màn hình CHỈ ĐỌC, dùng Query Builder. Tồn tính trực tiếp từ bảng nghiệp vụ, cùng công thức với
 * màn hình Tồn Kho của từng loại hàng:
 *
 *      tồn 1 lô = <loại>_imports.amount
 *               + SUM(<loại>_balancings.balancing_amount)   (status_id = 1)
 *               - SUM(<loại>_exports.amount)                 (sử dụng / huỷ / chuyển phòng ban)
 *
 * chỉ tính lô đã kiểm tra nhập (is_checked = 1), giữ lại lô CÒN TỒN ( > 0 ) tính đến hiện tại.
 *
 * Thẻ và số tổng chỉ gồm lô khớp từ khoá (lọc bằng SQL nên không phân biệt dấu / hoa thường);
 * sơ đồ 3D / Mặt bằng dựng ĐỦ kệ của phòng ban, vị trí có lô khớp được đánh dấu match để sáng lên.
 */
class PublicStockLookupController extends Controller
{
    /** Sai số cho phép khi so tồn với 0 (cột decimal 15,4). */
    private const EPSILON = 0.00005;

    /** Hạn dùng còn ít hơn số ngày này thì vị trí tô "cần chú ý". */
    private const WARN_DAYS = 30;

    /**
     * Cấu hình từng loại hàng. exportTypes: loại phiếu xuất trừ tồn (đúng như màn hình Tồn Kho);
     * exportStatus: phiếu xuất có cột status_id hay không; internal: có hạn dùng nội bộ.
     */
    private const KINDS = [
        'chemical' => [
            'route' => 'publicChemicalLookup',
            'tab' => 'Hoá chất',
            'title' => 'Tra cứu tồn kho hoá chất',
            'item' => 'hoá chất',
            'icon' => 'bi-droplet-half',
            'lot' => 'Mã xuất nhập',
            'imports' => 'chemical_imports',
            'exports' => 'chemical_exports',
            'balancings' => 'chemical_balancings',
            'categories' => 'chemical_categories',
            'names' => 'chem_names',
            'nameKey' => 'chem_names_id',
            'support' => DepartmentChemical::class,
            'exportTypes' => ['export', 'cancel'],
            'exportStatus' => true,
            'internal' => true,
        ],
        'standard' => [
            'route' => 'publicStandardLookup',
            'tab' => 'Chất chuẩn',
            'title' => 'Tra cứu tồn kho chất chuẩn',
            'item' => 'chất chuẩn',
            'icon' => 'bi-eyedropper',
            'lot' => 'Mã ống chuẩn',
            'imports' => 'standard_imports',
            'exports' => 'standard_exports',
            'balancings' => 'standard_balancings',
            'categories' => 'standard_categories',
            'names' => 'standard_names',
            'nameKey' => 'chem_names_id',
            'support' => DepartmentStandard::class,
            'exportTypes' => ['export', 'cancel', 'transfer_out'],
            'exportStatus' => false,
            'internal' => true,
        ],
        'material' => [
            'route' => 'publicMaterialLookup',
            'tab' => 'Vật tư',
            'title' => 'Tra cứu tồn kho vật tư',
            'item' => 'vật tư',
            'icon' => 'bi-tools',
            'lot' => 'Mã xuất nhập',
            'imports' => 'material_imports',
            'exports' => 'material_exports',
            'balancings' => 'material_balancings',
            'categories' => 'material_categories',
            'names' => 'material_names',
            'nameKey' => 'material_names_id',
            'support' => DepartmentMaterial::class,
            'exportTypes' => ['export', 'cancel', 'transfer_out'],
            'exportStatus' => true,
            'internal' => false,
        ],
    ];

    public function chemical(Request $request)
    {
        return $this->show($request, 'chemical');
    }

    public function standard(Request $request)
    {
        return $this->show($request, 'standard');
    }

    public function material(Request $request)
    {
        return $this->show($request, 'material');
    }

    private function show(Request $request, string $kind)
    {
        $cfg = self::KINDS[$kind];

        $departments = DB::table('deparments')
            ->leftJoin('companies', 'deparments.company_id', '=', 'companies.id')
            ->select('deparments.id', 'deparments.name', 'deparments.shortName', 'companies.short_name as company_short_name')
            ->where('deparments.isActive', 1)
            ->where('deparments.is_general', 1)
            ->orderBy('companies.short_name', 'asc')
            ->orderBy('deparments.name', 'asc')
            ->get();

        $departmentId = (int) $request->query('department_id', 0);
        $keyword = mb_substr(trim((string) $request->query('q', '')), 0, 120);
        $department = $departmentId ? $departments->firstWhere('id', $departmentId) : null;

        return view('public.stockLookup', [
            'kind' => $kind,
            'cfg' => $cfg,
            'kinds' => self::KINDS,
            'departments' => $departments,
            'departmentId' => $department ? $departmentId : 0,
            'department' => $department,
            'keyword' => $keyword,
            'result' => $department ? $this->lookup($cfg, $kind, $departmentId, $keyword) : null,
            // Đã bấm "Tra cứu" ít nhất một lần (để phân biệt với lần mở trang đầu tiên)
            'submitted' => $request->has('department_id'),
        ]);
    }

    /* ==========================================================
     |  TÍNH TỒN + DỰNG SƠ ĐỒ VỊ TRÍ
     ========================================================== */

    private function lookup(array $cfg, string $kind, int $departmentId, string $keyword): array
    {
        $imp = $cfg['imports'];
        $cat = $cfg['categories'];
        $nm = $cfg['names'];

        $query = DB::table($imp)
            ->leftJoin($cat, $imp.'.category_id', '=', $cat.'.id')
            ->leftJoin($nm, $cat.'.'.$cfg['nameKey'], '=', $nm.'.id')
            ->leftJoin('locations', $imp.'.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->leftJoin('columns', 'locations.column_id', '=', 'columns.id')
            ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id');

        // Đơn vị tính lấy theo khai báo của phòng ban đang chọn
        $query = $cfg['support']::joinUnit($query, $departmentId, $imp.'.category_id');

        $like = '%'.$keyword.'%';
        $rows = $query
            ->select(
                $imp.'.id',
                $imp.'.code',
                $imp.'.category_id',
                $imp.'.amount',
                $imp.'.expired_date',
                $imp.'.location_id',
                $nm.'.name as item_name',
                $cat.'.code as category_code',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code',
                'locations.zone_type as location_zone_type',
                'warehouses.name as warehouse_name',
                'shelves.name as shelf_name',
                'columns.name as column_name',
                'tiers.name as tier_name'
            )
            ->when($cfg['internal'], fn ($q) => $q->addSelect($imp.'.internal_expired_date'))
            // Lô khớp từ khoá: tên, mã danh mục hoặc mã lô (mã trên nhãn) - so bằng collation của CSDL
            ->selectRaw(
                $keyword === '' ? '1 as is_match'
                    : "CASE WHEN {$nm}.name LIKE ? OR {$cat}.code LIKE ? OR {$imp}.code LIKE ? THEN 1 ELSE 0 END as is_match",
                $keyword === '' ? [] : [$like, $like, $like]
            )
            ->where($imp.'.department_id', $departmentId)
            ->where($imp.'.status_id', 1)
            // Lô đang "Chờ kiểm tra" chưa được cộng vào tồn kho
            ->where($imp.'.is_checked', 1)
            ->get();

        $zones = $this->zones($kind, $departmentId);

        if ($rows->isEmpty()) {
            return $this->emptyResult($zones);
        }

        $ids = $rows->pluck('id')->all();

        $used = DB::table($cfg['exports'])
            ->select('import_id')
            ->selectRaw('SUM(amount) as amount')
            ->whereIn('import_id', $ids)
            ->whereIn('type', $cfg['exportTypes'])
            ->when($cfg['exportStatus'], fn ($q) => $q->where('status_id', 1))
            ->groupBy('import_id')
            ->pluck('amount', 'import_id');

        $balanced = DB::table($cfg['balancings'])
            ->select('import_id')
            ->selectRaw('SUM(balancing_amount) as amount')
            ->whereIn('import_id', $ids)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->pluck('amount', 'import_id');

        $today = now()->startOfDay();
        $stock = $rows->map(function ($row) use ($used, $balanced, $today) {
            $row->remaining = (float) $row->amount
                + (float) ($balanced[$row->id] ?? 0)
                - (float) ($used[$row->id] ?? 0);
            $row->unit = $row->unit_short_name ?: $row->unit_name;
            $row->is_match = (int) $row->is_match === 1;

            // Hạn dùng của lô: hạn nội bộ (sau khi mở) nếu có, không thì hạn nhà sản xuất
            $expiry = ($row->internal_expired_date ?? null) ?: $row->expired_date;
            $row->expiry = $expiry ? \Carbon\Carbon::parse($expiry)->startOfDay() : null;
            $row->state = ! $row->expiry ? 'ok'
                : ($row->expiry->lt($today) ? 'danger' : ($today->diffInDays($row->expiry) <= self::WARN_DAYS ? 'warn' : 'ok'));

            return $row;
        })->filter(fn ($row) => $row->remaining > self::EPSILON)->values();

        if ($stock->isEmpty()) {
            return $this->emptyResult($zones);
        }

        $result = $this->buildZoneMap($stock->filter(fn ($row) => $row->is_match)->values());
        $result['zones'] = $zones;
        $result['nodes'] = $this->nodes($stock);

        return $result;
    }

    /**
     * Định khu của phòng ban cho sơ đồ 3D / Mặt bằng: giống zoneOptions() của màn hình Tồn Kho - chỉ lấy ô
     * dành cho loại hàng này hoặc ô dùng chung (chưa khai loại).
     */
    private function zones(string $kind, int $departmentId): array
    {
        $of = fn (string $table, array $columns) => DB::table($table)
            ->select($columns)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->get();

        return [
            'warehouses' => \App\Support\RoomLayout::attach($of('warehouses', ['id', 'code', 'name', 'layout_width', 'layout_depth', 'layout_polygon', 'layout_door_wall', 'layout_door_offset', 'layout_door'])),
            'shelves' => $of('shelves', ['id', 'code', 'name', 'warehouse_id', 'tier_order', 'layout_x', 'layout_y', 'layout_width', 'layout_depth', 'layout_rotation']),
            'columns' => $of('columns', ['id', 'code', 'name', 'warehouse_id', 'shelf_id', 'position']),
            'tiers' => $of('tiers', ['id', 'code', 'name', 'warehouse_id', 'shelf_id', 'column_id', 'position']),
            'locations' => DB::table('locations')
                ->select(['id', 'code', 'warehouse_id', 'shelf_id', 'column_id', 'tier_id', 'position', 'depth', 'zone_type'])
                ->where('department_id', $departmentId)
                ->where('status_id', 1)
                ->where(fn ($q) => $q->whereNull('item_type')->orWhere('item_type', $kind))
                ->orderBy('code', 'asc')
                ->get(),
        ];
    }

    /**
     * Mọi vị trí đang có hàng của phòng ban (không lọc từ khoá) cho sơ đồ 3D và hộp chi tiết:
     * state = tình trạng xấu nhất trong các lô, match = có lô khớp từ khoá tra cứu.
     */
    private function nodes($stock): array
    {
        $rank = ['ok' => 0, 'warn' => 1, 'danger' => 2];
        $nodes = [];

        foreach ($stock->filter(fn ($row) => $row->location_id)->groupBy('location_id') as $locationId => $rows) {
            $first = $rows->first();
            $node = $this->locationNode('L'.$locationId, $first->location_code ?: '—', $this->pathOf($first), $rows);
            $state = $rows->reduce(fn ($worst, $row) => $rank[$row->state] > $rank[$worst] ? $row->state : $worst, 'ok');

            $nodes[$node['key']] = [
                'code' => $node['code'],
                'path' => $node['path'],
                'state' => $state,
                'zoneType' => $first->location_zone_type ?: '',
                'lots' => $node['stat']['lots'],
                'count' => $node['stat']['items'],
                'alerts' => $rows->filter(fn ($row) => $row->state !== 'ok')->count(),
                'match' => $rows->contains(fn ($row) => $row->is_match),
                'search' => $node['search'],
                'items' => $node['items'],
            ];
        }

        return $nodes;
    }

    private function pathOf($row): string
    {
        return ($row->warehouse_name ?: 'Chưa gán kho/phòng').' / '.($row->shelf_name ?: 'Chưa gán kệ/tủ').' / '
            .($row->column_name ?: 'Chưa gán cột').' / '.($row->tier_name ?: 'Chưa gán tầng');
    }

    /**
     * Gom các lô còn tồn KHỚP TỪ KHOÁ thành cây Kho/Phòng -> Kệ/Tủ -> Cột -> Tầng -> Vị trí để vẽ thẻ.
     * Chỉ dựng những nhánh có hàng khớp điều kiện tra cứu, ô trống không đưa vào.
     */
    private function buildZoneMap($stock): array
    {
        $located = $stock->filter(fn ($row) => $row->location_id);
        $unzonedRows = $stock->filter(fn ($row) => ! $row->location_id);
        $warehouses = [];

        foreach ($located->groupBy('location_id') as $locationId => $locRows) {
            $first = $locRows->first();
            $wName = $first->warehouse_name ?: 'Chưa gán kho/phòng';
            $sName = $first->shelf_name ?: 'Chưa gán kệ/tủ';
            $cName = $first->column_name ?: 'Chưa gán cột';
            $tName = $first->tier_name ?: 'Chưa gán tầng';

            $node = $this->locationNode('L'.$locationId, $first->location_code ?: '—', $this->pathOf($first), $locRows);

            $warehouses[$wName]['name'] ??= $wName;
            $branch = &$warehouses[$wName]['shelves'][$sName];
            $branch['name'] ??= $sName;
            $branch['columns'][$cName]['name'] ??= $cName;
            $branch['columns'][$cName]['tiers'][$tName]['name'] ??= $tName;
            $branch['columns'][$cName]['tiers'][$tName]['locations'][] = $node;
            unset($branch);
        }

        $tree = [];
        $totals = ['locations' => 0, 'lots' => 0, 'shelves' => 0, 'columns' => 0, 'tiers' => 0];
        $catSet = [];

        ksort($warehouses);
        foreach ($warehouses as $w) {
            ksort($w['shelves']);
            $wShelves = [];

            foreach ($w['shelves'] as $s) {
                $totals['shelves']++;
                ksort($s['columns']);
                $sColumns = [];

                foreach ($s['columns'] as $c) {
                    $totals['columns']++;
                    ksort($c['tiers']);
                    $cTiers = [];

                    foreach ($c['tiers'] as $t) {
                        $totals['tiers']++;
                        usort($t['locations'], fn ($a, $b) => strcmp((string) $a['code'], (string) $b['code']));

                        foreach ($t['locations'] as $loc) {
                            $totals['locations']++;
                            $totals['lots'] += $loc['stat']['lots'];
                            foreach ($loc['catIds'] as $cid) {
                                $catSet[$cid] = true;
                            }
                        }

                        $cTiers[] = $t;
                    }

                    $c['tiers'] = $cTiers;
                    $sColumns[] = $c;
                }

                $s['columns'] = $sColumns;
                $wShelves[] = $s;
            }

            $w['shelves'] = $wShelves;
            $tree[] = $w;
        }

        return [
            'warehouses' => $tree,
            'unzoned' => $this->unzonedNodes($unzonedRows),
            'totals' => $totals + [
                'warehouses' => count($tree),
                'items' => count($catSet),
                'unzoned' => $unzonedRows->count(),
            ],
        ];
    }

    /** Một ô vị trí: các mặt hàng đang nằm ở đó, cộng dồn tồn theo từng mặt hàng. */
    private function locationNode(string $key, string $code, string $path, $rows): array
    {
        $byItem = $this->groupByItem($rows);

        return [
            'key' => $key,
            'code' => $code,
            'path' => $path,
            'catIds' => $rows->pluck('category_id')->filter()->unique()->values()->all(),
            'stat' => ['lots' => $rows->count(), 'items' => count($byItem)],
            'preview' => array_map(fn ($x) => ['name' => $x['name'], 'amount' => $x['remaining'], 'unit' => $x['unit']], array_slice($byItem, 0, 4)),
            'items' => $byItem,
            // Chuỗi lọc nhanh (chữ thường): mã vị trí, đường dẫn, tên + mã danh mục + mã lô
            'search' => mb_strtolower($code.' '.$path.' '.implode(' ', array_map(fn ($x) => $x['name'].' '.$x['code'].' '.implode(' ', $x['lotCodes']), $byItem))),
        ];
    }

    /** Gom các lô theo mặt hàng (danh mục), cộng dồn tồn. */
    private function groupByItem($rows): array
    {
        return $rows
            ->groupBy(fn ($row) => $row->category_id ? 'c:'.$row->category_id : 'n:'.$row->item_name)
            ->map(function ($group) {
                $first = $group->first();
                $dates = $group->pluck('expiry')->filter()->sort()->values();

                return [
                    'name' => $first->item_name ?: '—',
                    'code' => $first->category_code,
                    'remaining' => $this->number((float) $group->sum('remaining')),
                    'sum' => (float) $group->sum('remaining'),
                    'unit' => $first->unit,
                    'lots' => $group->count(),
                    'lotCodes' => $group->pluck('code')->filter()->values()->all(),
                    'expiry' => $dates->isEmpty() ? '—' : $dates->first()->format('d/m/Y'),
                ];
            })
            ->sortByDesc('sum')
            ->map(function ($x) {
                unset($x['sum']);

                return $x;
            })
            ->values()
            ->all();
    }

    /** Lô còn tồn nhưng chưa xếp vị trí - gom theo mặt hàng để hiện thành một khối riêng. */
    private function unzonedNodes($rows): array
    {
        $items = $this->groupByItem($rows);
        usort($items, fn ($a, $b) => $b['lots'] <=> $a['lots']);

        return $items;
    }

    private function emptyResult(array $zones): array
    {
        return [
            'warehouses' => [],
            'unzoned' => [],
            'totals' => ['warehouses' => 0, 'shelves' => 0, 'columns' => 0, 'tiers' => 0, 'locations' => 0, 'lots' => 0, 'items' => 0, 'unzoned' => 0],
            'zones' => $zones,
            'nodes' => [],
        ];
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }

    /** Màu loại định khu (Dự Phòng, Sử Dụng...) cho chế độ tô màu "Loại định khu" của sơ đồ 3D. */
    public static function zoneTypes(): array
    {
        return collect(ZoneType::TYPES)
            ->map(fn ($label, $type) => ['bg' => ZoneType::color($type), 'label' => $label])
            ->all();
    }
}
