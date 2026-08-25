<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * CẤU HÌNH CHẤT CHUẨN THEO TỪNG PHÒNG BAN
 *
 * Danh mục chất chuẩn (standard_categories) dùng chung toàn công ty vì nó mô tả bản
 * chất của chất chuẩn. Cách dùng thì riêng từng phòng, nằm ở bảng department_standards.
 *
 * Quy tắc đọc ở mọi nơi trong hệ thống:
 *
 *      giá trị hiệu lực = department_standards.<cột> ?? standard_categories.<cột>
 *
 * Phòng chưa khai riêng thì tự động chạy theo mặc định chung, không có màn hình nào gãy.
 *
 * Lớp này chỉ gom lại phần join + COALESCE để 3 màn hình Nhập / Sử Dụng / Tồn không mỗi
 * nơi viết một kiểu rồi lệch nhau. Vẫn là Query Builder thuần, không dùng Eloquent.
 * Song song với App\Support\DepartmentChemical bên hoá chất.
 */
class DepartmentStandard
{
    public const TABLE = 'department_standards';

    /**
     * Nối bảng cấu hình của ĐÚNG một phòng ban vào câu truy vấn đang có.
     *
     * Điều kiện phòng ban đặt ngay trong mệnh đề JOIN chứ không đặt ở WHERE: đây là
     * leftJoin, để ở WHERE sẽ loại mất những chất chuẩn phòng chưa khai cấu hình.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $categoryColumn  Cột chứa category_id ở bảng đang truy vấn,
     *                                  ví dụ 'standard_imports.category_id'
     */
    public static function join($query, int $departmentId, string $categoryColumn)
    {
        return $query->leftJoin(self::TABLE, function ($join) use ($departmentId, $categoryColumn) {
            $join->on(self::TABLE.'.category_id', '=', $categoryColumn)
                ->where(self::TABLE.'.department_id', '=', $departmentId)
                ->where(self::TABLE.'.status_id', '=', 1);
        });
    }

    /**
     * Cột hạn dùng nội bộ HIỆU LỰC, dùng trong select() sau khi đã gọi join().
     *
     * Chuỗi trong DB::raw là hằng, không ghép từ dữ liệu người dùng.
     */
    public static function shelfLifeColumn()
    {
        return DB::raw(
            'COALESCE('.self::TABLE.'.shelf_life_months, standard_categories.shelf_life_months)'
            .' as shelf_life_months'
        );
    }

    /** Ngưỡng tồn tối thiểu của phòng (không có mặc định chung nên không cần COALESCE). */
    public static function minStockColumn()
    {
        return self::TABLE.'.min_stock';
    }

    /** Vị trí lưu trữ quy hoạch của phòng, dùng để điền sẵn khi nhập. */
    public static function defaultLocationColumn()
    {
        return self::TABLE.'.default_location_id as default_location_id';
    }

    /**
     * Các phòng ban đang dùng từng chất chuẩn: [category_id => [{id, name, shortName}]].
     *
     * Một câu truy vấn cho cả bảng danh mục, tránh hỏi lại theo từng dòng. Đây chính là
     * cột QC / QC1 / QC2 / AD trên danh mục chất chuẩn.
     */
    public static function departmentsByCategory()
    {
        return DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->select(
                self::TABLE.'.category_id',
                'deparments.id',
                'deparments.name',
                'deparments.shortName'
            )
            ->where(self::TABLE.'.status_id', 1)
            ->orderBy('deparments.shortName', 'asc')
            ->get()
            ->groupBy('category_id');
    }

    /**
     * Cấu hình chất chuẩn của ĐÚNG một phòng ban, kèm phần mô tả lấy từ danh mục chung.
     *
     * Đặt ở đây thay vì trong Controller vì tab "Chất Chuẩn Của Phòng" nằm chung trang
     * với tab "Danh Mục Chất Chuẩn Công Ty": màn hình do StandardCategoryController dựng,
     * còn các thao tác thêm / sửa / khoá vẫn do DepartmentStandardController xử lý.
     */
    public static function rowsOfDepartment(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->leftJoin('standard_categories', self::TABLE.'.category_id', '=', 'standard_categories.id')
            ->leftJoin('chem_names', 'standard_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', 'standard_categories.unit_id', '=', 'units.id')
            ->leftJoin('storage_conditions', self::TABLE.'.storage_condition_id', '=', 'storage_conditions.id')
            ->leftJoin('storage_conditions as category_storage', 'standard_categories.storage_condition_id', '=', 'category_storage.id')
            ->leftJoin('locations', self::TABLE.'.default_location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->select(
                self::TABLE.'.*',
                'standard_categories.code as category_code',
                'standard_categories.groups',
                'standard_categories.version as category_version',
                'standard_categories.cas_no',
                'standard_categories.doc_no as category_doc_no',
                'standard_categories.shelf_life_months as category_shelf_life_months',
                'chem_names.name as standard_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'storage_conditions.name as storage_condition_name',
                'category_storage.name as category_storage_condition_name',
                'locations.name as location_name',
                'locations.code as location_code',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy('standard_categories.code', 'asc')
            ->get();
    }

    /**
     * Chất chuẩn được phép khai: đã duyệt và còn hoạt động trong danh mục chung.
     *
     * $exclude là các category_id phòng đã khai rồi - loại khỏi ô chọn của modal
     * Thêm mới để không đụng ràng buộc unique(department_id, category_id).
     */
    public static function categoryOptions(array $exclude = [])
    {
        $query = DB::table('standard_categories')
            ->leftJoin('chem_names', 'standard_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'standard_categories.unit_id', '=', 'units.id')
            ->select(
                'standard_categories.id',
                'standard_categories.code',
                'standard_categories.version',
                'standard_categories.shelf_life_months',
                'chem_names.name as standard_name',
                'units.short_name as unit_short_name'
            )
            ->where('standard_categories.status_id', 1)
            ->where('standard_categories.app_status', 'approved')
            ->orderBy('standard_categories.code', 'asc');

        $exclude = array_values(array_filter($exclude));

        if ($exclude) {
            $query->whereNotIn('standard_categories.id', $exclude);
        }

        return $query->get();
    }

    /** Vị trí lưu trữ của đúng phòng ban đang chọn, kèm đường dẫn Kho / Phòng / Kệ. */
    public static function locationOptions(int $departmentId)
    {
        return DB::table('locations')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->select(
                'locations.id',
                'locations.code',
                'locations.name',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where('locations.department_id', $departmentId)
            ->where('locations.status_id', 1)
            ->orderBy('warehouses.name', 'asc')
            ->orderBy('rooms.name', 'asc')
            ->orderBy('shelves.name', 'asc')
            ->orderBy('locations.name', 'asc')
            ->get();
    }

    /** Điều kiện bảo quản còn hiệu lực, dùng cho ô chọn của phòng ban. */
    public static function storageConditionOptions()
    {
        return DB::table('storage_conditions')
            ->select('id', 'name')
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->get();
    }
}
