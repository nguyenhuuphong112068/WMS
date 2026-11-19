<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CHIA SẺ VỊ TRÍ (Ô ĐỊNH KHU) CHO BỘ PHẬN KHÁC
 *
 * Kho chung do một phòng quản lý (phòng CHỦ KHO = locations.department_id, vd. QC) giao một số ô cho
 * bộ phận khác cất hàng (bộ phận MƯỢN = locations.shared_department_id, vd. Phân xưởng). Người dùng chốt
 * 17/11/2026:
 *  - Đơn vị chia sẻ là từng Ô (vị trí), khai ở Dữ Liệu Gốc → Định Khu → Cấu trúc kho (thanh chọn ô).
 *  - Mỗi ô giao cho đúng MỘT bộ phận; đã giao thì phòng chủ kho KHÔNG cất hàng vào ô đó nữa.
 *  - Tồn tính cho phòng nhập lô (imports.department_id) như cũ; phòng chủ kho XEM được hàng của bộ phận
 *    mượn ở các ô đã giao trên sơ đồ Theo định khu (Thẻ / 3D / Mặt bằng) - chỉ xem, có xét tương kỵ
 *    nếu là hoá chất. Bộ phận mượn thấy các ô được giao trong sơ đồ của mình (kèm tên kho của phòng chủ).
 *
 * Mọi chỗ lọc "ô của phòng đang chọn" (ô chọn định khu, validate vị trí khi nhập / định khu / đổi vị trí /
 * cách ly / nhận chuyển) dùng scope() / rule() ở đây thay cho where('department_id', ...).
 */
class LocationShare
{
    private const EPSILON = 0.0000001;

    /**
     * Bảng lô / xuất / cân đối + danh mục, tên, đơn vị theo loại lưu trữ (locations.item_type).
     * exports_status = false: bảng xuất không có cột status_id (standard_exports).
     */
    public const MODULES = [
        'chemical' => [
            'imports' => 'chemical_imports', 'exports' => 'chemical_exports', 'exports_status' => true,
            'balancings' => 'chemical_balancings', 'categories' => 'chemical_categories',
            'names' => 'chem_names', 'name_fk' => 'chem_names_id', 'unit_from' => 'department',
            'department_table' => 'chemical_department_categories', 'internal_expiry' => true, 'label' => 'Hoá chất',
        ],
        'standard' => [
            'imports' => 'standard_imports', 'exports' => 'standard_exports', 'exports_status' => false,
            'balancings' => 'standard_balancings', 'categories' => 'standard_categories',
            'names' => 'chem_names', 'name_fk' => 'chem_names_id', 'unit_from' => 'department',
            'department_table' => 'standard_department_categories', 'internal_expiry' => true, 'label' => 'Chất chuẩn',
        ],
        'material' => [
            'imports' => 'material_imports', 'exports' => 'material_exports', 'exports_status' => true,
            'balancings' => 'material_balancings', 'categories' => 'material_categories',
            'names' => 'material_names', 'name_fk' => 'material_names_id', 'unit_from' => 'department',
            'department_table' => 'material_department_categories', 'internal_expiry' => false, 'label' => 'Vật tư',
        ],
        'vpp' => [
            'imports' => 'vpp_imports', 'exports' => 'vpp_exports', 'exports_status' => true,
            'balancings' => 'vpp_balancings', 'categories' => 'vpp_categories',
            'names' => 'vpp_names', 'name_fk' => 'vpp_names_id', 'unit_from' => 'category',
            'department_table' => 'vpp_department_categories', 'internal_expiry' => false, 'label' => 'VPP',
        ],
    ];

    /* ==========================================================
     |  PHẠM VI Ô CỦA MỘT PHÒNG
     ========================================================== */

    /**
     * Ô phòng $departmentId được cất hàng: ô của phòng chưa giao cho ai + ô phòng khác giao cho phòng này.
     * $table = '' khi gọi trong Rule::exists (cột không kèm tên bảng).
     */
    public static function scope($query, int $departmentId, string $table = 'locations')
    {
        $p = $table === '' ? '' : $table.'.';

        return $query->where(fn ($sub) => $sub
            ->where(fn ($own) => $own->where($p.'department_id', $departmentId)->whereNull($p.'shared_department_id'))
            ->orWhere($p.'shared_department_id', $departmentId));
    }

    /** Rule::exists vị trí ĐANG HOẠT ĐỘNG phòng được cất hàng - thay cho exists(...)->where('department_id', ...). */
    public static function rule(int $departmentId)
    {
        return Rule::exists('locations', 'id')
            ->where(fn ($query) => self::scope($query, $departmentId, ''))
            ->where('status_id', 1);
    }

    /** Phòng được chọn để giao ô (đang hoạt động, trừ chính phòng chủ kho). */
    public static function departmentOptions(int $exceptId)
    {
        return DB::table('deparments')
            ->where('isActive', 1)
            ->where('is_general', 1)
            ->where('id', '!=', $exceptId)
            ->orderBy('name')
            ->get(['id', 'name', 'shortName']);
    }

    /** "Phân xưởng (PX)" - nhãn phòng ngắn gọn cho ô / tooltip. */
    public static function departmentLabels(array $ids, bool $short = false): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return $ids
            ? DB::table('deparments')->whereIn('id', $ids)->get(['id', 'name', 'shortName'])
                ->mapWithKeys(fn ($d) => [(int) $d->id => ($short && $d->shortName ? $d->shortName : DepartmentOptions::label($d))])->all()
            : [];
    }

    /* ==========================================================
     |  TỒN TẠI Ô (kiểm tra trước khi giao / thu hồi)
     ========================================================== */

    /**
     * Lô đang còn tồn ở các ô, mọi loại hàng: [locationId => [departmentId => số lô]].
     * Tính cả lô "Chờ kiểm tra" - hàng đó đã nằm trên kệ.
     */
    public static function onHandByLocation(array $locationIds): array
    {
        $out = [];

        foreach (array_keys(self::MODULES) as $module) {
            foreach (self::lotsAt($module, $locationIds, false) as $lot) {
                $out[$lot->location_id][$lot->department_id] = ($out[$lot->location_id][$lot->department_id] ?? 0) + 1;
            }
        }

        return $out;
    }

    /**
     * Lô CÒN TỒN của một loại hàng tại các ô: {id, code, department_id, category_id, location_id, remaining, ...}.
     * $detail = true kèm tên hàng / đơn vị / hạn dùng / nhóm GHS để vẽ lên sơ đồ.
     */
    private static function lotsAt(string $module, array $locationIds, bool $detail, bool $checkedOnly = false, ?callable $where = null)
    {
        $m = self::MODULES[$module];
        $locationIds = array_values(array_unique(array_filter(array_map('intval', $locationIds))));

        if (! $locationIds && ! $where) {
            return collect();
        }

        $i = $m['imports'];
        $query = DB::table($i)
            ->where($i.'.status_id', 1)
            ->whereNotNull($i.'.location_id')
            ->when($locationIds, fn ($q) => $q->whereIn($i.'.location_id', $locationIds))
            ->when($checkedOnly, fn ($q) => $q->where($i.'.is_checked', 1));

        // Không đưa closure vào when(): when() coi closure là điều kiện và gọi nó trước
        if ($where) {
            $where($query, $i);
        }

        $columns = [$i.'.id', $i.'.code', $i.'.department_id', $i.'.category_id', $i.'.location_id', $i.'.amount'];

        if ($detail) {
            $c = $m['categories'];
            $query->leftJoin($c, $i.'.category_id', '=', $c.'.id')
                ->leftJoin($m['names'], $c.'.'.$m['name_fk'], '=', $m['names'].'.id');

            if ($m['unit_from'] === 'category') {
                $query->leftJoin('units', $c.'.unit_id', '=', 'units.id');
            } else {
                $d = $m['department_table'];
                $query->leftJoin($d.' as ls_dc', function ($join) use ($i) {
                    $join->on('ls_dc.category_id', '=', $i.'.category_id')
                        ->on('ls_dc.department_id', '=', $i.'.department_id');
                })->leftJoin('units', 'ls_dc.unit_id', '=', 'units.id');
            }

            $columns = array_merge($columns, [
                $i.'.batch_no', $i.'.expired_date', $i.'.is_checked',
                $c.'.code as category_code', $m['names'].'.name as item_name',
                'units.short_name as unit_short_name', 'units.name as unit_name',
            ]);
            if ($m['internal_expiry']) {
                $columns[] = $i.'.internal_expired_date';
            }
            if ($module === 'chemical') {
                $columns[] = $c.'.safety_warning';
            }
        }

        $imports = $query->select($columns)->get();

        if ($imports->isEmpty()) {
            return collect();
        }

        $used = [];
        $balanced = [];

        foreach ($imports->pluck('id')->chunk(1000) as $chunk) {
            $used += DB::table($m['exports'])
                ->whereIn('import_id', $chunk->all())
                ->when($m['exports_status'], fn ($q) => $q->where('status_id', 1))
                ->groupBy('import_id')
                ->select('import_id', DB::raw('SUM(amount) as total'))
                ->pluck('total', 'import_id')
                ->all();
            $balanced += DB::table($m['balancings'])
                ->whereIn('import_id', $chunk->all())
                ->where('status_id', 1)
                ->groupBy('import_id')
                ->select('import_id', DB::raw('SUM(balancing_amount) as total'))
                ->pluck('total', 'import_id')
                ->all();
        }

        return $imports->map(function ($lot) use ($used, $balanced) {
            $lot->location_id = (int) $lot->location_id;
            $lot->department_id = (int) $lot->department_id;
            $lot->remaining = (float) $lot->amount + (float) ($balanced[$lot->id] ?? 0) - (float) ($used[$lot->id] ?? 0);

            return $lot;
        })->filter(fn ($lot) => $lot->remaining > self::EPSILON)->values();
    }

    /**
     * Ô đang được khai là Vị trí mặc định trong danh mục của các phòng (mọi loại hàng):
     * [locationId => [departmentId => số mặt hàng]].
     */
    public static function defaultsByLocation(array $locationIds): array
    {
        $out = [];
        $locationIds = array_values(array_unique(array_filter(array_map('intval', $locationIds))));

        if (! $locationIds) {
            return $out;
        }

        foreach (self::MODULES as $m) {
            $rows = DB::table($m['department_table'])
                ->whereIn('default_location_id', $locationIds)
                ->where('status_id', 1)
                ->groupBy('default_location_id', 'department_id')
                ->select('default_location_id', 'department_id', DB::raw('COUNT(*) as total'))
                ->get();

            foreach ($rows as $row) {
                $loc = (int) $row->default_location_id;
                $dept = (int) $row->department_id;
                $out[$loc][$dept] = ($out[$loc][$dept] ?? 0) + (int) $row->total;
            }
        }

        return $out;
    }

    /* ==========================================================
     |  SƠ ĐỒ THEO ĐỊNH KHU Ở TỒN KHO
     ========================================================== */

    /**
     * Bổ sung khung định khu ($zones = zoneOptions() của màn Tồn Kho) cho phần chia sẻ:
     *  - Phòng CHỦ KHO: ô đã giao mang shared_to (tên bộ phận mượn) + 'guests' = lô còn tồn của bộ phận mượn ở
     *    các ô đó (chỉ xem; hoá chất có 'sw' để đối chiếu tương kỵ cùng tầng).
     *  - Phòng MƯỢN: thêm các ô được giao (đúng loại hàng hoặc dùng chung) + kho/kệ/cột/tầng chứa chúng; tên
     *    kho kèm tên phòng chủ kho, ô mang owner.
     * $guests = false: không lấy hàng của bộ phận mượn (trang tra cứu công khai chỉ hiện hàng của phòng được tra).
     */
    public static function extendZones(array $zones, int $departmentId, string $module, bool $guests = true): array
    {
        $zones['guests'] = [];
        $locations = collect($zones['locations'] ?? []);

        // Ô của phòng mình đang giao cho bộ phận khác
        $shared = $locations->isEmpty() ? collect() : DB::table('locations')
            ->whereIn('id', $locations->pluck('id')->all())
            ->whereNotNull('shared_department_id')
            ->pluck('shared_department_id', 'id');

        if ($shared->isNotEmpty()) {
            $labels = self::departmentLabels($shared->values()->all());

            $locations = $locations->map(function ($loc) use ($shared, $labels) {
                if (isset($shared[$loc->id])) {
                    $loc->shared_to = $labels[(int) $shared[$loc->id]] ?? '—';
                }

                return $loc;
            });

            if ($guests) {
                $zones['guests'] = self::guestRows($module, $shared->keys()->all(), $departmentId);
            }
        }

        // Ô phòng khác giao cho phòng mình
        $borrowed = DB::table('locations')
            ->where('shared_department_id', $departmentId)
            ->where('department_id', '!=', $departmentId)
            ->where('status_id', 1)
            ->where(fn ($q) => $q->whereNull('item_type')->orWhere('item_type', $module))
            ->orderBy('code')
            ->get(['id', 'code', 'department_id', 'warehouse_id', 'shelf_id', 'column_id', 'tier_id', 'position', 'depth', 'item_type', 'zone_type', 'color']);

        if ($borrowed->isNotEmpty()) {
            $owners = self::departmentLabels($borrowed->pluck('department_id')->all(), true);
            $ownerOf = $borrowed->pluck('department_id', 'warehouse_id')->all();

            $locations = $locations->concat($borrowed->map(function ($loc) use ($owners) {
                $loc->badge = ZoneType::badge($loc->id, $loc->zone_type, $loc->color);
                $loc->owner = $owners[(int) $loc->department_id] ?? '—';
                unset($loc->department_id);

                return $loc;
            }));

            $have = fn (string $key) => collect($zones[$key] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
            $extra = function (string $table, string $key, array $columns) use ($borrowed, $have) {
                $ids = array_diff($borrowed->pluck($key)->filter()->unique()->map(fn ($id) => (int) $id)->all(), $have($table));

                return $ids ? DB::table($table)->whereIn('id', $ids)->orderBy('name')->get($columns) : collect();
            };

            $warehouses = $extra('warehouses', 'warehouse_id', ['id', 'code', 'name', 'layout_width', 'layout_depth', 'layout_polygon', 'layout_door_wall', 'layout_door_offset', 'layout_door'])
                ->map(function ($w) use ($ownerOf, $owners) {
                    $w->owner = $owners[(int) ($ownerOf[$w->id] ?? 0)] ?? '—';
                    $w->name = $w->name.' · kho '.$w->owner;

                    return $w;
                });

            $zones['warehouses'] = collect($zones['warehouses'] ?? [])->concat(RoomLayout::attach($warehouses))->values();
            $zones['shelves'] = collect($zones['shelves'] ?? [])->concat($extra('shelves', 'shelf_id', ['id', 'code', 'name', 'warehouse_id', 'tier_order', 'layout_x', 'layout_y', 'layout_width', 'layout_depth', 'layout_height', 'layout_rotation']))->values();
            $zones['columns'] = collect($zones['columns'] ?? [])->concat($extra('columns', 'column_id', ['id', 'code', 'name', 'warehouse_id', 'shelf_id', 'position']))->values();
            $zones['tiers'] = collect($zones['tiers'] ?? [])->concat($extra('tiers', 'tier_id', ['id', 'code', 'name', 'warehouse_id', 'shelf_id', 'column_id', 'position']))->values();
        }

        $zones['locations'] = $locations->values();

        return $zones;
    }

    /**
     * Lô CÒN TỒN của bộ phận mượn tại các ô phòng $ownerId đã giao - cùng tên trường với dòng tồn của sơ đồ
     * (inventory/shared/zoneGrid) để vẽ chung, thêm guest = tên bộ phận.
     */
    private static function guestRows(string $module, array $locationIds, int $ownerId): array
    {
        $lots = self::lotsAt($module, $locationIds, true, true,
            fn ($q, $table) => $q->where($table.'.department_id', '!=', $ownerId));

        if ($lots->isEmpty()) {
            return [];
        }

        $labels = self::departmentLabels($lots->pluck('department_id')->all());
        $today = now()->format('Y-m-d');

        return $lots->map(function ($lot) use ($labels, $today, $module) {
            $expired = ! empty($lot->internal_expired_date) ? $lot->internal_expired_date : $lot->expired_date;
            $expired = $expired ? substr((string) $expired, 0, 10) : null;
            $state = $expired && $expired < $today ? 'expired' : 'in';

            return [
                'id' => 'g'.$lot->id,
                'guest' => $labels[$lot->department_id] ?? '—',
                'code' => $lot->code,
                'category_id' => (int) $lot->category_id,
                'category_code' => $lot->category_code,
                'name' => $lot->item_name,
                'sub' => implode(' · ', array_filter([$lot->category_code, $lot->batch_no ? 'Lô '.$lot->batch_no : null])),
                'sw' => $module === 'chemical'
                    ? ChemicalCompatibility::comparableCodes(ChemicalCompatibility::decode($lot->safety_warning ?? null))
                    : [],
                'remaining' => round($lot->remaining, 6),
                'unit' => $lot->unit_short_name ?: $lot->unit_name,
                'expired' => $expired,
                'state' => $state,
                'state_label' => $state === 'expired' ? 'Hết hạn' : 'Còn hàng',
                'location_id' => $lot->location_id,
            ];
        })->values()->all();
    }
}
