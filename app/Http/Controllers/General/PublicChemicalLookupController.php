<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Support\DepartmentChemical;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TRA CỨU TỒN KHO HOÁ CHẤT CÔNG KHAI (không cần đăng nhập)
 *
 * Mở từ liên kết ngoài trang đăng nhập. Người dùng chọn MỘT phòng ban, gõ tên
 * (hoặc mã) hoá chất; hệ thống trả về các VỊ TRÍ (Kho / Phòng / Kệ-Tủ / Vị trí)
 * đang chứa hoá chất đó kèm SỐ LƯỢNG TỒN, trình bày dạng thẻ (card - grid) như
 * màn hình "Tồn Kho Vật Tư Theo Vị Trí".
 *
 * Màn hình CHỈ ĐỌC, dùng Query Builder. Tồn tính trực tiếp từ bảng nghiệp vụ,
 * đúng công thức của ChemicalInventoryController:
 *
 *      tồn 1 lô = chemical_imports.amount
 *               + SUM(chemical_balancings.balancing_amount)   (status_id = 1)
 *               - SUM(chemical_exports.amount)                 (status_id = 1)
 *
 * và chỉ giữ lại các lô CÒN TỒN ( > 0 ) tính đến hiện tại.
 */
class PublicChemicalLookupController extends Controller
{
    /** Sai số cho phép khi so tồn với 0 (cột decimal 15,4). */
    private const EPSILON = 0.00005;

    public function index(Request $request)
    {
        $departments = DB::table('deparments')
            ->leftJoin('companies', 'deparments.company_id', '=', 'companies.id')
            ->select(
                'deparments.id',
                'deparments.name',
                'deparments.shortName',
                'companies.short_name as company_short_name'
            )
            ->where('deparments.isActive', 1)
            ->where('deparments.is_general', 1)
            ->orderBy('companies.short_name', 'asc')
            ->orderBy('deparments.name', 'asc')
            ->get();

        $departmentId = (int) $request->query('department_id', 0);
        $keyword = trim((string) $request->query('q', ''));

        $department = $departmentId
            ? $departments->firstWhere('id', $departmentId)
            : null;

        $result = $department ? $this->lookup($departmentId, $keyword) : null;

        return view('public.chemicalLookup', [
            'departments' => $departments,
            'departmentId' => $department ? $departmentId : 0,
            'department' => $department,
            'keyword' => $keyword,
            'result' => $result,
            // Đã bấm "Tra cứu" ít nhất một lần (để phân biệt với lần mở trang đầu tiên)
            'submitted' => $request->has('department_id'),
        ]);
    }

    /* ==========================================================
     |  TÍNH TỒN + DỰNG SƠ ĐỒ VỊ TRÍ
     ========================================================== */

    private function lookup(int $departmentId, string $keyword): array
    {
        $query = DB::table('chemical_imports')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('locations', 'chemical_imports.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id');

        // Đơn vị tính lấy theo khai báo của phòng ban đang chọn
        $query = DepartmentChemical::joinUnit($query, $departmentId, 'chemical_imports.category_id');

        $rows = $query
            ->select(
                'chemical_imports.id',
                'chemical_imports.category_id',
                'chemical_imports.amount',
                'chemical_imports.batch_no',
                'chemical_imports.expired_date',
                'chemical_imports.internal_expired_date',
                'chemical_imports.location_id',
                'chem_names.name as chem_name',
                'chemical_categories.code as category_code',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code',
                'locations.warehouse_id',
                'locations.room_id',
                'locations.shelf_id',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where('chemical_imports.department_id', $departmentId)
            ->where('chemical_imports.status_id', 1)
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('chem_names.name', 'like', '%'.$keyword.'%')
                        ->orWhere('chemical_categories.code', 'like', '%'.$keyword.'%');
                });
            })
            ->get();

        if ($rows->isEmpty()) {
            return $this->emptyResult();
        }

        $ids = $rows->pluck('id')->all();

        $used = DB::table('chemical_exports')
            ->select('import_id')
            ->selectRaw('SUM(amount) as amount')
            ->whereIn('import_id', $ids)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->pluck('amount', 'import_id');

        $balanced = DB::table('chemical_balancings')
            ->select('import_id')
            ->selectRaw('SUM(balancing_amount) as amount')
            ->whereIn('import_id', $ids)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->pluck('amount', 'import_id');

        $stock = $rows->map(function ($row) use ($used, $balanced) {
            $row->remaining = (float) $row->amount
                + (float) ($balanced[$row->id] ?? 0)
                - (float) ($used[$row->id] ?? 0);
            $row->unit = $row->unit_short_name ?: $row->unit_name;

            return $row;
        })->filter(fn ($row) => $row->remaining > self::EPSILON)->values();

        if ($stock->isEmpty()) {
            return $this->emptyResult();
        }

        return $this->buildZoneMap($stock);
    }

    /**
     * Gom các lô còn tồn thành cây Kho -> Phòng -> Kệ/Tủ -> Vị trí để vẽ thẻ.
     * Chỉ dựng những nhánh CÓ HÀNG khớp điều kiện tra cứu, ô trống không đưa vào.
     */
    private function buildZoneMap($stock): array
    {
        $located = $stock->filter(fn ($row) => $row->location_id);
        $unzonedRows = $stock->filter(fn ($row) => ! $row->location_id);

        $index = [];
        $warehouses = [];

        foreach ($located->groupBy('location_id') as $locationId => $locRows) {
            $first = $locRows->first();
            $wName = $first->warehouse_name ?: 'Chưa gán kho';
            $rName = $first->room_name ?: 'Chưa gán phòng';
            $sName = $first->shelf_name ?: 'Chưa gán kệ/tủ';
            $path = $wName.' / '.$rName.' / '.$sName;

            $node = $this->locationNode('L'.$locationId, $first->location_code ?: '—', $path, $locRows);

            $warehouses[$wName]['name'] ??= $wName;
            $warehouses[$wName]['rooms'][$rName]['name'] ??= $rName;
            $warehouses[$wName]['rooms'][$rName]['shelves'][$sName]['name'] ??= $sName;
            $warehouses[$wName]['rooms'][$rName]['shelves'][$sName]['locations'][] = $node;

            $index[$node['key']] = [
                'code' => $node['code'],
                'path' => $path,
                'lots' => $node['stat']['lots'],
                'chemicals' => $node['stat']['chemicals'],
                'items' => $node['items'],
            ];
        }

        $tree = [];
        $totalLocations = 0;
        $totalLots = 0;
        $roomCount = 0;
        $shelfCount = 0;
        $catSet = [];

        ksort($warehouses);
        foreach ($warehouses as $w) {
            ksort($w['rooms']);
            $wRooms = [];

            foreach ($w['rooms'] as $r) {
                $roomCount++;
                ksort($r['shelves']);
                $rShelves = [];

                foreach ($r['shelves'] as $s) {
                    $shelfCount++;
                    usort($s['locations'], fn ($a, $b) => strcmp((string) $a['code'], (string) $b['code']));

                    foreach ($s['locations'] as $loc) {
                        $totalLocations++;
                        $totalLots += $loc['stat']['lots'];
                        foreach ($loc['catIds'] as $cid) {
                            $catSet[$cid] = true;
                        }
                    }

                    $rShelves[] = $s;
                }

                $r['shelves'] = $rShelves;
                $wRooms[] = $r;
            }

            $w['rooms'] = $wRooms;
            $tree[] = $w;
        }

        return [
            'warehouses' => $tree,
            'unzoned' => $this->unzonedNodes($unzonedRows),
            'totals' => [
                'warehouses' => count($tree),
                'rooms' => $roomCount,
                'shelves' => $shelfCount,
                'locations' => $totalLocations,
                'lots' => $totalLots,
                'chemicals' => count($catSet),
                'unzoned' => $unzonedRows->count(),
            ],
            'index' => $index,
        ];
    }

    /** Một ô vị trí: các hoá chất đang nằm ở đó, cộng dồn tồn theo từng hoá chất. */
    private function locationNode(string $key, string $code, string $path, $rows): array
    {
        $byChemical = $rows
            ->groupBy(fn ($row) => $row->category_id ? 'c:'.$row->category_id : 'n:'.$row->chem_name)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'name' => $first->chem_name ?: '—',
                    'code' => $first->category_code,
                    'remaining' => (float) $group->sum('remaining'),
                    'unit' => $first->unit,
                    'lots' => $group->count(),
                    'expiry' => $this->nearestExpiry($group),
                ];
            })
            ->sortByDesc('remaining')
            ->values();

        return [
            'key' => $key,
            'code' => $code,
            'path' => $path,
            'catIds' => $rows->pluck('category_id')->filter()->unique()->values()->all(),
            'stat' => [
                'lots' => $rows->count(),
                'chemicals' => $byChemical->count(),
            ],
            'preview' => $byChemical->take(4)->map(fn ($x) => [
                'name' => $x['name'],
                'amount' => $this->number($x['remaining']),
                'unit' => $x['unit'],
            ])->all(),
            'items' => $byChemical->map(fn ($x) => [
                'name' => $x['name'],
                'code' => $x['code'],
                'remaining' => $this->number($x['remaining']),
                'unit' => $x['unit'],
                'lots' => $x['lots'],
                'expiry' => $x['expiry'],
            ])->all(),
        ];
    }

    /** Lô còn tồn nhưng chưa xếp vị trí - gom theo hoá chất để hiện thành một khối riêng. */
    private function unzonedNodes($rows): array
    {
        return $rows
            ->groupBy(fn ($row) => $row->category_id ? 'c:'.$row->category_id : 'n:'.$row->chem_name)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'name' => $first->chem_name ?: '—',
                    'code' => $first->category_code,
                    'remaining' => $this->number((float) $group->sum('remaining')),
                    'unit' => $first->unit,
                    'lots' => $group->count(),
                    'expiry' => $this->nearestExpiry($group),
                ];
            })
            ->sortByDesc(fn ($x) => $x['lots'])
            ->values()
            ->all();
    }

    /** Hạn dùng gần nhất trong nhóm (hạn nội bộ nếu có, không thì hạn nhà sản xuất). */
    private function nearestExpiry($group): string
    {
        $dates = $group
            ->map(fn ($row) => $row->internal_expired_date ?: $row->expired_date)
            ->filter()
            ->map(fn ($d) => \Carbon\Carbon::parse($d))
            ->sort()
            ->values();

        return $dates->isEmpty() ? '—' : $dates->first()->format('d/m/Y');
    }

    private function emptyResult(): array
    {
        return [
            'warehouses' => [],
            'unzoned' => [],
            'totals' => [
                'warehouses' => 0,
                'rooms' => 0,
                'shelves' => 0,
                'locations' => 0,
                'lots' => 0,
                'chemicals' => 0,
                'unzoned' => 0,
            ],
            'index' => [],
        ];
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }
}
