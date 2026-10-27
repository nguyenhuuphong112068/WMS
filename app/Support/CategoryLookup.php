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
     |  HOẠT CHẤT (ô chọn nhiều của modal Tên Hoá Chất)
     ========================================================== */

    /**
     * Hoạt chất đã duyệt + đang hoạt động, tìm theo tên / mã / số CAS / công thức.
     * Kèm name + g9 (thuộc nhóm 9) để bảng % khối lượng và khoá nhóm 10 đọc được
     * mà không cần option render sẵn.
     */
    public static function searchActiveIngredients(string $q, int $page): array
    {
        $query = DB::table('active_ingredients')
            ->select('id', 'code', 'name', 'cas_no')
            ->where('status_id', 1)
            ->where('app_status', 'approved');

        foreach (self::terms($q) as $term) {
            $query->where(function ($sub) use ($term) {
                foreach (['name', 'code', 'cas_no', 'chemical_formula'] as $column) {
                    $sub->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        [$rows, $more] = self::paginate($query->orderBy('name', 'asc')->orderBy('id', 'asc'), $page);

        $groups = ChemicalClassification::groupsForActiveIngredients($rows->pluck('id')->map(fn ($v) => (int) $v)->all());

        return self::response(
            $rows->map(fn ($row) => self::activeIngredientItem($row, $groups[(int) $row->id] ?? []))->all(),
            $more
        );
    }

    /** {id, text, name, g9} của một hoạt chất - cùng nhãn với ô chọn cũ ("Tên — CAS x — Nhóm 1, 9"). */
    public static function activeIngredientItem($row, array $groups): array
    {
        return [
            'id' => (int) $row->id,
            'text' => self::activeIngredientLabel($row, $groups),
            'name' => (string) $row->name,
            'g9' => in_array(9, $groups, true) ? 1 : 0,
        ];
    }

    public static function activeIngredientLabel($row, array $groups): string
    {
        return $row->name
            .(! empty($row->cas_no) ? ' — CAS '.$row->cas_no : '')
            .($groups ? ' — Nhóm '.implode(', ', $groups) : '');
    }

    /* ==========================================================
     |  DANH MỤC HOÁ CHẤT (mã C000xx)
     ========================================================== */

    /** Phạm vi tìm của ô chọn mã hoá chất */
    public const CHEMICAL_SCOPES = ['import', 'undeclared', 'approved'];

    /** Phạm vi ô chọn mã chất chuẩn - xem searchStandardCategories() */
    public const STANDARD_SCOPES = ['department', 'all'];

    /**
     * Tìm mã hoá chất theo mã / tên, theo phạm vi:
     *  - import     : hoá chất PHÒNG đã khai (modal Nhập Hoá Chất) - như DepartmentChemical::importCategoryOptions
     *  - undeclared : hoá chất đã duyệt mà phòng CHƯA khai (modal Khai Hoá Chất Cho Phòng)
     *  - approved   : mọi hoá chất đã duyệt (đề nghị chuyển liên phòng ban), kèm đơn vị phòng đang dùng
     */
    public static function searchChemicalCategories(string $scope, int $departmentId, string $q, int $page): array
    {
        $query = DB::table('chemical_categories')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_categories.id'))
            ->select('chemical_categories.id', 'chemical_categories.code', 'chem_names.name as chem_name', 'units.short_name as unit_short_name')
            ->where('chemical_categories.status_id', 1)
            ->where('chemical_categories.app_status', 'approved');

        $declared = fn ($sub) => $sub->select(DB::raw(1))
            ->from(DepartmentChemical::TABLE.' as lk_dc')
            ->whereColumn('lk_dc.category_id', 'chemical_categories.id')
            ->where('lk_dc.department_id', $departmentId);

        if ($scope === 'import') {
            $query->whereExists(fn ($sub) => $declared($sub)->where('lk_dc.status_id', 1));
        } elseif ($scope === 'undeclared') {
            $query->whereNotExists($declared);
        }

        foreach (self::terms($q) as $term) {
            $query->where(fn ($sub) => $sub->where('chemical_categories.code', 'like', '%'.$term.'%')
                ->orWhere('chem_names.name', 'like', '%'.$term.'%'));
        }

        [$rows, $more] = self::paginate($query->orderBy('chemical_categories.code', 'asc'), $page);

        return self::response($rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'text' => self::chemicalCategoryLabel($row, $scope === 'import'),
            'code' => (string) $row->code,
            'unit' => (string) ($row->unit_short_name ?? ''),
        ])->all(), $more);
    }

    /** "C00001 - Ethanol" (+ " (ml)" ở ô Nhập Hoá Chất) - cùng nhãn với option render sẵn trước đây. */
    public static function chemicalCategoryLabel($row, bool $withUnit = false): string
    {
        return $row->code.' - '.$row->chem_name
            .($withUnit && ! empty($row->unit_short_name) ? ' ('.$row->unit_short_name.')' : '');
    }

    /* ==========================================================
     |  MÃ CHẤT CHUẨN
     ========================================================== */

    /**
     * Tìm chất chuẩn đang hoạt động theo mã / tên / nhà sản xuất - ô Chất Chuẩn của đề nghị cấp
     * phát chuẩn ở Sử Dụng Chuẩn, kèm đơn vị phòng đang chọn và nhà sản xuất để phân biệt các chất
     * chuẩn trùng tên. Phạm vi:
     *  - department : chuẩn PHÒNG đã khai ở Chất Chuẩn Của Phòng (đề nghị cấp phát cho Tổ - kho
     *                 phòng chỉ có ống của chuẩn đã khai)
     *  - all        : mọi chất chuẩn (đề nghị liên phòng ban - A khai chuẩn trước khi nhận là được)
     */
    public static function searchStandardCategories(string $scope, int $departmentId, string $q, int $page): array
    {
        $query = DB::table('standard_categories')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_categories.id'))
            ->select(
                'standard_categories.id',
                'standard_categories.code',
                'standard_names.name as standard_name',
                'manufacturers.name as manufacturer_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where('standard_categories.status_id', 1);

        if ($scope === 'department') {
            $query->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from(DepartmentStandard::TABLE.' as lk_ds')
                ->whereColumn('lk_ds.category_id', 'standard_categories.id')
                ->where('lk_ds.department_id', $departmentId)
                ->where('lk_ds.status_id', 1));
        }

        foreach (self::terms($q) as $term) {
            $query->where(fn ($sub) => $sub->where('standard_categories.code', 'like', '%'.$term.'%')
                ->orWhere('standard_names.name', 'like', '%'.$term.'%')
                ->orWhere('manufacturers.name', 'like', '%'.$term.'%'));
        }

        [$rows, $more] = self::paginate(
            $query->orderBy('standard_names.name', 'asc')->orderBy('standard_categories.code', 'asc'),
            $page
        );

        return self::response($rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'text' => self::standardCategoryLabel($row),
            'code' => (string) $row->code,
            'unit' => (string) ($row->unit_short_name ?: ($row->unit_name ?? '')),
            'manufacturer' => (string) ($row->manufacturer_name ?? ''),
        ])->all(), $more);
    }

    /** "Ibuprofen (S00020)" - cùng nhãn với option render sẵn trước đây. */
    public static function standardCategoryLabel($row): string
    {
        return $row->standard_name.' ('.$row->code.')';
    }

    /* ==========================================================
     |  ĐỊNH KHU
     ========================================================== */

    /**
     * Tìm định khu đang hoạt động của phòng ban, chỉ ô khai đúng loại hàng hoặc chưa khai loại
     * (dùng chung) - cùng điều kiện với DepartmentMaterial/Chemical/Standard::locationOptions().
     */
    /**
     * $zone: lọc nhanh theo phân loại định khu (chip trong ô chọn) - null = mọi phân loại,
     * '' = chưa phân loại, còn lại là khoá của ZoneType::TYPES.
     */
    /**
     * $itemType 'all': không lọc loại hàng - đúng điều kiện ô Định khu của Nhập Hoá Chất / Chất Chuẩn.
     * $onlyZone: chỉ một phân loại cố định (modal Nhập: lô mới chỉ vào khu Biệt Trữ 'quarantine'),
     * khác $zone là chip lọc người dùng bấm trong ô chọn.
     */
    public static function searchLocations(string $itemType, int $departmentId, string $q, int $page, ?string $zone = null, ?string $onlyZone = null): array
    {
        $query = self::locationQuery()
            ->where('locations.department_id', $departmentId)
            ->where('locations.status_id', 1)
            ->when($itemType !== 'all', fn ($query) => $query->where(fn ($sub) => $sub->whereNull('locations.item_type')
                ->orWhere('locations.item_type', $itemType)))
            ->when($onlyZone !== null && isset(ZoneType::TYPES[$onlyZone]), fn ($query) => $query->where('locations.zone_type', $onlyZone));

        if ($zone === '') {
            $query->whereNull('locations.zone_type');
        } elseif ($zone !== null && isset(ZoneType::TYPES[$zone])) {
            $query->where('locations.zone_type', $zone);
        }

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
            $rows->map(fn ($row) => [
                'id' => (int) $row->id,
                'text' => self::locationLabel($row),
                'zone_type' => $row->zone_type,
            ])->all(),
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
     |  TÊN SẢN PHẨM
     ========================================================== */

    /**
     * Tên sản phẩm đang hoạt động - ô Mục Đích / Lý Do khi sử dụng hoá chất. id = tên vì cột
     * purpose lưu chữ (vẫn cho gõ mục đích tự do ngoài danh sách).
     */
    public static function searchProductNames(string $q, int $page): array
    {
        $query = DB::table('product_names')->select('name')->where('status_id', 1);

        foreach (self::terms($q) as $term) {
            $query->where('name', 'like', '%'.$term.'%');
        }

        [$rows, $more] = self::paginate($query->orderBy('name', 'asc'), $page);

        return self::response($rows->map(fn ($row) => ['id' => $row->name, 'text' => $row->name])->all(), $more);
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
