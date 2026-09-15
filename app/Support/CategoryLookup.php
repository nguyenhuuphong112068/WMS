<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * TRA CỨU DỮ LIỆU GỐC CHO Ô CHỌN SELECT2 DẠNG AJAX - NHÓM DANH MỤC (VẬT TƯ / HOÁ CHẤT / CHẤT CHUẨN)
 *
 * Trước đây trang nhúng cả danh mục vào <option> (3.500+ tên vật tư, 1.250 định khu... nhân 2 cho
 * modal Thêm + Sửa) làm trang nặng vài MB và Select2 đơ mỗi lần mở. Giờ ô chọn chỉ mang giá trị
 * đang chọn; gõ tìm thì gọi CategoryLookupController, mỗi lần PAGE_SIZE dòng. Nút Sửa gửi kèm nhãn
 * của giá trị đang dùng (<field>_text) để modal Sửa hiện đúng mà không phải nhúng danh mục.
 *
 * Kết quả trả đúng định dạng Select2: {results: [{id, text, ...}], pagination: {more}}.
 */
class CategoryLookup
{
    public const PAGE_SIZE = 30;

    /** Nguồn tên dữ liệu gốc theo loại hàng => bảng */
    public const NAME_TABLES = [
        'material' => 'material_names',
        'chemical' => 'chem_names',
        'standard' => 'standard_names',
    ];

    /** Loại hàng của định khu (locations.item_type) */
    public const ITEM_TYPES = ['material', 'chemical', 'standard'];

    /* ==========================================================
     |  TÊN DỮ LIỆU GỐC
     ========================================================== */

    /** Tìm tên đã duyệt + đang hoạt động theo tên (hoá chất / chất chuẩn tìm cả số CAS). */
    public static function searchNames(string $source, string $q, int $page): array
    {
        $table = self::NAME_TABLES[$source];

        $query = self::nameQuery($source)
            ->where($table.'.status_id', 1)
            ->where($table.'.app_status', 'approved');

        // Mỗi từ gõ vào phải khớp ít nhất một cột, gõ "bạc skf" vẫn ra "Bạc đạn SKF"
        foreach (self::terms($q) as $term) {
            $query->where(function ($sub) use ($source, $table, $term) {
                $sub->where($table.'.name', 'like', '%'.$term.'%');

                if ($source === 'standard') {
                    $sub->orWhere('standard_names.cas_no', 'like', '%'.$term.'%');
                }

                if ($source === 'chemical') {
                    $sub->orWhereExists(function ($exists) use ($term) {
                        $exists->from('chem_name_active_ingredient as lk_cnai')
                            ->join('active_ingredients as lk_ai', 'lk_ai.id', '=', 'lk_cnai.active_ingredients_id')
                            ->whereColumn('lk_cnai.chem_names_id', 'chem_names.id')
                            ->where('lk_ai.cas_no', 'like', '%'.$term.'%');
                    });
                }
            });
        }

        [$rows, $more] = self::paginate(
            $query->orderBy($table.'.name', 'asc')->orderBy($table.'.id', 'asc'),
            $page
        );

        $groups = $source === 'chemical' ? self::chemicalGroupCodes($rows->pluck('id')->all()) : [];

        $items = $rows->map(function ($row) use ($source, $groups) {
            $item = ['id' => (int) $row->id, 'text' => self::nameLabel($row)];

            if ($source !== 'material') {
                $item['cas_no'] = (string) ($row->cas_no ?? '');
            }

            if ($source === 'chemical') {
                $item['groups'] = $groups[$row->id] ?? [];
            }

            return $item;
        })->all();

        return self::response($items, $more);
    }

    /**
     * Các tên theo id, không lọc trạng thái - dựng sẵn option cho giá trị vừa gửi bị lỗi validate
     * (old()) để modal mở lại vẫn giữ lựa chọn.
     */
    public static function namesByIds(string $source, array $ids)
    {
        $ids = self::ids($ids);
        $table = self::NAME_TABLES[$source];

        return $ids
            ? self::nameQuery($source)->whereIn($table.'.id', $ids)->orderBy($table.'.name', 'asc')->get()
            : collect();
    }

    /** Nhãn hiển thị của một tên: "Tên (CAS: 64-17-5)", vật tư không có CAS thì chỉ tên. */
    public static function nameLabel($row): string
    {
        return ($row->name ?? '').(! empty($row->cas_no) ? ' (CAS: '.$row->cas_no.')' : '');
    }

    /**
     * Mã nhóm NĐ 24/2026 (N1..N10) của từng tên hoá chất: [chem_names_id => ['N9', ...]].
     */
    public static function chemicalGroupCodes(array $chemNameIds): array
    {
        $ids = self::ids($chemNameIds);

        if (! $ids) {
            return [];
        }

        return array_map(
            fn ($groups) => array_map(fn ($group) => 'N'.$group, $groups),
            ChemicalClassification::groupsByChemName($ids)
        );
    }

    /**
     * Dữ liệu cho bảng "Chọn Tên Hoá Chất Từ Dữ Liệu Gốc": tên đã duyệt + đang hoạt động, cộng các
     * tên danh mục đang mang (kể cả đã khoá). Nạp qua AJAX khi mở bảng lần đầu, không nhúng vào trang.
     */
    public static function chemicalPickerRows(): array
    {
        $keepIds = self::ids(DB::table('chemical_categories')->pluck('chem_names_id')->all());

        $rows = self::nameQuery('chemical')
            ->where(function ($query) use ($keepIds) {
                $query->where(fn ($sub) => $sub->where('chem_names.status_id', 1)->where('chem_names.app_status', 'approved'));

                if ($keepIds) {
                    $query->orWhereIn('chem_names.id', $keepIds);
                }
            })
            ->orderBy('chem_names.name', 'asc')
            ->get();

        $groups = self::chemicalGroupCodes($rows->pluck('id')->all());

        return $rows->map(function ($row) use ($groups) {
            $codes = $groups[$row->id] ?? [];

            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'cas_no' => (string) ($row->cas_no ?? ''),
                'text' => self::nameLabel($row),
                'codes' => array_map(fn ($code) => [
                    'code' => $code,
                    'tone' => ChemicalClassification::toneOfCode($code),
                ], $codes),
                'special' => ChemicalClassification::isSpecialControl($codes),
            ];
        })->all();
    }

    private static function nameQuery(string $source)
    {
        $table = self::NAME_TABLES[$source];
        $query = DB::table($table)->select($table.'.id', $table.'.name', $table.'.status_id');

        return match ($source) {
            // chem_names gắn nhiều hoạt chất, số CAS gộp từ bảng pivot
            'chemical' => $query->selectSub(DepartmentChemical::casNoSubquery('chem_names.id'), 'cas_no'),
            'standard' => $query->addSelect('standard_names.cas_no'),
            default => $query,
        };
    }

    /* ==========================================================
     |  ĐỊNH KHU
     ========================================================== */

    /**
     * Tìm định khu đang hoạt động của phòng ban, chỉ ô khai đúng loại hàng hoặc chưa khai loại
     * (dùng chung) - cùng điều kiện với DepartmentMaterial/Chemical/Standard::locationOptions().
     */
    public static function searchLocations(string $itemType, int $departmentId, string $q, int $page): array
    {
        $query = self::locationQuery()
            ->where('locations.department_id', $departmentId)
            ->where('locations.status_id', 1)
            ->where(fn ($query) => $query->whereNull('locations.item_type')
                ->orWhere('locations.item_type', $itemType));

        // Gõ "kho 01 tầng 02": mỗi từ khớp một phần bất kỳ của đường dẫn định khu
        foreach (self::terms($q) as $term) {
            $query->where(function ($sub) use ($term) {
                foreach (['locations.code', 'warehouses.name', 'shelves.name', 'columns.name', 'tiers.name'] as $column) {
                    $sub->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        [$rows, $more] = self::paginate(self::orderLocations($query), $page);

        return self::response(
            $rows->map(fn ($row) => ['id' => (int) $row->id, 'text' => self::locationLabel($row)])->all(),
            $more
        );
    }

    /** Định khu theo id, không lọc trạng thái / phòng - dựng sẵn option cho giá trị old(). */
    public static function locationsByIds(array $ids)
    {
        $ids = self::ids($ids);

        return $ids
            ? self::orderLocations(self::locationQuery()->whereIn('locations.id', $ids))->get()
            : collect();
    }

    /**
     * "Kho 01 / Kệ 01 / Cột 01 / Tầng 01 / 01/01/01/01/01". $codeField = 'location_code' khi đọc từ
     * dòng đã join sẵn định khu (rowsOfDepartment). Không có định khu thì null.
     */
    public static function locationLabel($row, string $codeField = 'code'): ?string
    {
        $code = $row->{$codeField} ?? null;

        if (! $code) {
            return null;
        }

        return implode(' / ', [
            $row->warehouse_name ?: '—',
            $row->shelf_name ?: '—',
            $row->column_name ?: '—',
            $row->tier_name ?: '—',
            $code,
        ]);
    }

    private static function locationQuery()
    {
        return DB::table('locations')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->leftJoin('columns', 'locations.column_id', '=', 'columns.id')
            ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id')
            ->select(
                'locations.id',
                'locations.code',
                'locations.zone_type',
                'warehouses.name as warehouse_name',
                'shelves.name as shelf_name',
                'columns.name as column_name',
                'tiers.name as tier_name'
            );
    }

    private static function orderLocations($query)
    {
        return $query->orderBy('warehouses.name', 'asc')
            ->orderBy('shelves.name', 'asc')
            ->orderBy('columns.name', 'asc')
            ->orderBy('tiers.name', 'asc')
            ->orderBy('locations.code', 'asc');
    }

    /* ==========================================================
     |  TIỆN ÍCH
     ========================================================== */

    public static function emptyResponse(): array
    {
        return self::response([], false);
    }

    private static function response(array $items, bool $more): array
    {
        return ['results' => $items, 'pagination' => ['more' => $more]];
    }

    /** Lấy dư 1 dòng để biết còn trang sau mà không phải COUNT cả bảng. */
    private static function paginate($query, int $page): array
    {
        $page = max(1, $page);

        $rows = $query->offset(($page - 1) * self::PAGE_SIZE)->limit(self::PAGE_SIZE + 1)->get();

        return [$rows->take(self::PAGE_SIZE)->values(), $rows->count() > self::PAGE_SIZE];
    }

    /** Tách chuỗi tìm thành tối đa 5 từ, bỏ khoảng trắng thừa. */
    private static function terms(string $q): array
    {
        $q = trim(mb_substr($q, 0, 100));

        if ($q === '') {
            return [];
        }

        return array_slice(array_values(array_filter(preg_split('/\s+/u', $q) ?: [], 'strlen')), 0, 5);
    }

    private static function ids(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
