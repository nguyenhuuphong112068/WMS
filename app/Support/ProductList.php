<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * DANH SÁCH THEO SẢN PHẨM - "gói" Sản phẩm + Chỉ tiêu của một phòng ban, dùng cho Hoá chất và Chất chuẩn.
 *
 *   <loại>_product_lists       : department_id, product_name (chữ, từ product_names), purpose_id (Chỉ tiêu kiểm)
 *   <loại>_product_list_items  : list_id, category_id, amount (lượng gợi ý, đơn vị của phòng)
 *
 * Khai ở tab "Danh sách ... theo sản phẩm" của trang Danh Mục (ProductListController). Nơi dùng:
 *   - Hoá chất : modal Sử Dụng Hoá Chất, Cách 3 - gợi ý một lô FEFO/FIFO cho từng hoá chất
 *   - Chất chuẩn: modal Đề nghị cấp phát chuẩn nội bộ - thêm các dòng đề nghị theo gói
 */
class ProductList
{
    public const CHEMICAL = 'chemical';

    public const STANDARD = 'standard';

    /** Số mã tối đa của một gói. */
    public const MAX_ITEMS = 100;

    /**
     * table / item_table      : bảng gói + bảng con
     * category_table / name_table : danh mục công ty + bảng tên (cột chem_names_id ở cả hai loại)
     * department              : lớp khai của phòng (TABLE + joinUnit)
     * noun / title            : tên loại hàng trong câu chữ / trên nhãn, tiêu đề cột
     */
    public const TYPES = [
        self::CHEMICAL => [
            'table' => 'chemical_product_lists',
            'item_table' => 'chemical_product_list_items',
            'category_table' => 'chemical_categories',
            'name_table' => 'chem_names',
            'department' => DepartmentChemical::class,
            'permission' => 'category_chemical_product_manage',
            'noun' => 'hoá chất',
            'title' => 'Hoá Chất',
        ],
        self::STANDARD => [
            'table' => 'standard_product_lists',
            'item_table' => 'standard_product_list_items',
            'category_table' => 'standard_categories',
            'name_table' => 'standard_names',
            'department' => DepartmentStandard::class,
            'permission' => 'category_standard_product_manage',
            'noun' => 'chất chuẩn',
            'title' => 'Chất Chuẩn',
        ],
    ];

    public static function config(string $type): array
    {
        return self::TYPES[$type];
    }

    /**
     * Các gói của phòng, mỗi gói kèm ->items (category_id, amount, category_code, item_name,
     * manufacturer_name, unit_short_name - đơn vị của phòng).
     */
    public static function rowsOfDepartment(string $type, int $departmentId, bool $activeOnly = false)
    {
        $config = self::config($type);
        $table = $config['table'];
        $itemTable = $config['item_table'];
        $categoryTable = $config['category_table'];

        $lists = DB::table($table)
            ->leftJoin('purposes', $table.'.purpose_id', '=', 'purposes.id')
            ->select($table.'.*', 'purposes.name as purpose_name')
            ->where($table.'.department_id', $departmentId)
            ->when($activeOnly, fn ($query) => $query->where($table.'.status_id', 1))
            ->orderBy($table.'.product_name')
            ->orderBy('purposes.name')
            ->get();

        $items = DB::table($itemTable)
            ->leftJoin($categoryTable, $itemTable.'.category_id', '=', $categoryTable.'.id')
            ->leftJoin($config['name_table'], $categoryTable.'.chem_names_id', '=', $config['name_table'].'.id')
            ->leftJoin('manufacturers', $categoryTable.'.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => $config['department']::joinUnit($query, $departmentId, $itemTable.'.category_id'))
            ->select(
                $itemTable.'.list_id',
                $itemTable.'.category_id',
                $itemTable.'.amount',
                $categoryTable.'.code as category_code',
                $config['name_table'].'.name as item_name',
                DB::raw('COALESCE(manufacturers.short_name, manufacturers.name) as manufacturer_name'),
                'units.short_name as unit_short_name'
            )
            ->whereIn($itemTable.'.list_id', $lists->pluck('id'))
            ->orderBy($itemTable.'.sort_order')
            ->orderBy($itemTable.'.id')
            ->get()
            ->groupBy('list_id');

        return $lists->map(function ($list) use ($items) {
            $list->items = collect($items[$list->id] ?? [])->values();

            return $list;
        });
    }

    /**
     * Nguồn ô "Thêm ..." của modal khai gói: các mã ĐANG KHAI cho phòng (DepartmentChemical /
     * DepartmentStandard::rowsOfDepartment), đơn vị theo phòng.
     */
    public static function options(string $type, $departmentRows)
    {
        $nameField = $type === self::STANDARD ? 'standard_name' : 'chem_name';

        return collect($departmentRows)->where('status_id', 1)->map(function ($row) use ($type, $nameField) {
            $manufacturer = $type === self::STANDARD ? ($row->manufacturer_short_name ?: $row->manufacturer_name) : null;

            return [
                'id' => (string) $row->category_id,
                'text' => $row->category_code.' - '.$row->{$nameField}.($manufacturer ? ' ('.$manufacturer.')' : ''),
                'code' => $row->category_code,
                'name' => $row->{$nameField},
                'manufacturer' => $manufacturer,
                'unit' => $row->unit_short_name ?: $row->unit_name,
            ];
        })->values();
    }

    /** Nhãn hiển thị của gói: "Sản phẩm - Chỉ tiêu" (cũng là chữ điền vào ô Mục Đích của phiếu Sử Dụng HC). */
    public static function label($list): string
    {
        return trim($list->product_name.($list->purpose_name ? ' - '.$list->purpose_name : ''));
    }

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> "12.5", null giữ null. */
    public static function amount($value): ?string
    {
        return $value === null ? null : rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }
}
