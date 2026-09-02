<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * CẤU HÌNH HOÁ CHẤT THEO TỪNG PHÒNG BAN
 *
 * Danh mục hoá chất (chemical_categories) dùng chung toàn công ty vì nó mô tả bản chất
 * của chất. Cách dùng chất thì riêng từng phòng, nằm ở bảng chemical_department_categories.
 *
 * Quy tắc đọc ở mọi nơi trong hệ thống:
 *
 *      giá trị hiệu lực = chemical_department_categories.<cột> ?? chemical_categories.<cột>
 *
 * Phòng chưa khai riêng thì tự động chạy theo mặc định chung, không có màn hình nào gãy.
 *
 * Riêng ĐƠN VỊ TÍNH không theo quy tắc trên: nó chỉ nằm ở chemical_department_categories, danh mục
 * chung không còn cột unit_id. Mỗi phòng nhập / xuất hoá chất theo đơn vị của phòng mình,
 * nên mọi màn hình muốn hiện đơn vị đều phải đi qua joinUnit().
 *
 * Lớp này chỉ gom lại phần join + COALESCE để 3 màn hình Nhập / Sử Dụng / Tồn không mỗi
 * nơi viết một kiểu rồi lệch nhau. Vẫn là Query Builder thuần, không dùng Eloquent.
 */
class DepartmentChemical
{
    public const TABLE = 'chemical_department_categories';

    /** Bí danh của chính bảng này khi chỉ nối vào để lấy đơn vị tính - xem joinUnit(). */
    public const UNIT_ALIAS = 'dc_unit';

    /**
     * Nối bảng cấu hình của ĐÚNG một phòng ban vào câu truy vấn đang có.
     *
     * Điều kiện phòng ban đặt ngay trong mệnh đề JOIN chứ không đặt ở WHERE: đây là
     * leftJoin, để ở WHERE sẽ loại mất những hoá chất phòng chưa khai cấu hình.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $categoryColumn  Cột chứa category_id ở bảng đang truy vấn,
     *                                  ví dụ 'chemical_imports.category_id'
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
            'COALESCE('.self::TABLE.'.shelf_life_months, chemical_categories.shelf_life_months)'
            .' as shelf_life_months'
        );
    }

    /**
     * Nối ĐƠN VỊ TÍNH của đúng một phòng ban vào câu truy vấn đang có.
     *
     * Đơn vị nằm ở chemical_department_categories nên không lấy thẳng từ chemical_categories được
     * nữa. Dùng bí danh riêng để câu truy vấn nào đã gọi join() ở trên vẫn dùng được.
     *
     * Không lọc status_id: phòng khoá dòng khai rồi thì các phiếu cũ vẫn phải hiện đúng
     * đơn vị đã dùng. Sau khi gọi, select 'units.short_name as unit_short_name' như cũ.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $categoryColumn  Cột chứa category_id ở bảng đang truy vấn,
     *                                  ví dụ 'chemical_imports.category_id'
     */
    public static function joinUnit($query, int $departmentId, string $categoryColumn)
    {
        return $query
            ->leftJoin(self::TABLE.' as '.self::UNIT_ALIAS, function ($join) use ($departmentId, $categoryColumn) {
                $join->on(self::UNIT_ALIAS.'.category_id', '=', $categoryColumn)
                    ->where(self::UNIT_ALIAS.'.department_id', '=', $departmentId);
            })
            ->leftJoin('units', self::UNIT_ALIAS.'.unit_id', '=', 'units.id');
    }

    /**
     * Như joinUnit() nhưng phòng ban lấy theo MỘT CỘT của câu truy vấn, không phải một
     * số cố định.
     *
     * Dùng cho màn hình đọc dữ liệu của nhiều phòng cùng lúc (lô đang chuyển kho, đề
     * nghị của phòng khác, phiếu dự trù): số lượng đã ghi theo đơn vị của phòng nào thì
     * phải hiện đúng đơn vị của phòng đó.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $departmentColumn  Cột chứa department_id, ví dụ 'chemical_exports.department_id'
     * @param  string  $categoryColumn  Cột chứa category_id, ví dụ 'source.category_id'
     */
    public static function joinUnitOn($query, string $departmentColumn, string $categoryColumn)
    {
        return $query
            ->leftJoin(self::TABLE.' as '.self::UNIT_ALIAS, function ($join) use ($departmentColumn, $categoryColumn) {
                $join->on(self::UNIT_ALIAS.'.category_id', '=', $categoryColumn)
                    ->on(self::UNIT_ALIAS.'.department_id', '=', $departmentColumn);
            })
            ->leftJoin('units', self::UNIT_ALIAS.'.unit_id', '=', 'units.id');
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
     * Hạn dùng nội bộ hiệu lực của một cặp (phòng ban, hoá chất).
     *
     * Dùng khi chỉ cần đúng một dòng - các màn hình danh sách nên dùng join() để tránh
     * hỏi DB theo từng dòng.
     */
    public static function shelfLifeMonths(int $departmentId, int $categoryId): int
    {
        $own = DB::table(self::TABLE)
            ->where('department_id', $departmentId)
            ->where('category_id', $categoryId)
            ->where('status_id', 1)
            ->value('shelf_life_months');

        if ($own !== null) {
            return (int) $own;
        }

        return (int) DB::table('chemical_categories')
            ->where('id', $categoryId)
            ->value('shelf_life_months');
    }

    /**
     * Các phòng ban đang dùng từng hoá chất: [category_id => [{id, name, shortName}]].
     *
     * Một câu truy vấn cho cả bảng danh mục, tránh hỏi lại theo từng dòng.
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
     * Cấu hình hoá chất của ĐÚNG một phòng ban, kèm phần mô tả lấy từ danh mục chung.
     *
     * Đặt ở đây thay vì trong Controller vì tab "Hoá Chất Của Phòng" nằm chung trang với
     * tab "Danh Mục Hoá Chất Công Ty": màn hình do ChemicalCategoryController dựng, còn
     * các thao tác thêm / sửa / khoá vẫn do DepartmentChemicalController xử lý. Hai nơi
     * đọc chung một câu truy vấn để không lệch nhau.
     */
    public static function rowsOfDepartment(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->leftJoin('chemical_categories', self::TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', 'chemical_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', self::TABLE.'.unit_id', '=', 'units.id')
            ->leftJoin('storage_conditions', self::TABLE.'.storage_condition_id', '=', 'storage_conditions.id')
            ->leftJoin('storage_conditions as category_storage', 'chemical_categories.storage_condition_id', '=', 'category_storage.id')
            ->leftJoin('locations', self::TABLE.'.default_location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->select(
                self::TABLE.'.*',
                'chemical_categories.code as category_code',
                'chemical_categories.type as category_type',
                'chemical_categories.classification',
                'chemical_categories.density as category_density',
                'chemical_categories.doc_no as category_doc_no',
                'chemical_categories.shelf_life_months as category_shelf_life_months',
                'chem_names.name as chem_name',
                'chem_names.cas_no as cas_no',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'storage_conditions.name as storage_condition_name',
                'category_storage.name as category_storage_condition_name',
                'locations.code as location_code',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy('chemical_categories.code', 'asc')
            ->get();
    }

    /**
     * Hoá chất được phép khai: đã duyệt và còn hoạt động trong danh mục chung.
     *
     * $exclude là các category_id phòng đã khai rồi - loại khỏi ô chọn của modal
     * Thêm mới để không đụng ràng buộc unique(department_id, category_id).
     */
    public static function categoryOptions(array $exclude = [])
    {
        $query = DB::table('chemical_categories')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->select(
                'chemical_categories.id',
                'chemical_categories.code',
                'chemical_categories.shelf_life_months',
                'chem_names.name as chem_name'
            )
            ->where('chemical_categories.status_id', 1)
            ->where('chemical_categories.app_status', 'approved')
            ->orderBy('chemical_categories.code', 'asc');

        $exclude = array_values(array_filter($exclude));

        if ($exclude) {
            $query->whereNotIn('chemical_categories.id', $exclude);
        }

        return $query->get();
    }

    /**
     * Vị trí lưu trữ của đúng phòng ban đang chọn, kèm đường dẫn Kho / Phòng / Kệ.
     *
     * Lọc theo locations.item_type để không xếp nhầm hàng vào ô của loại khác;
     * ô chưa khai loại được coi là dùng chung nên vẫn chọn được.
     */
    public static function locationOptions(int $departmentId)
    {
        return DB::table('locations')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->select(
                'locations.id',
                'locations.code',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where('locations.department_id', $departmentId)
            ->where('locations.status_id', 1)
            // Chỉ những ô khai loại hoá chất, cộng thêm ô chưa khai loại (dùng chung)
            ->where(fn ($query) => $query->whereNull('locations.item_type')
                ->orWhere('locations.item_type', 'chemical'))
            ->orderBy('warehouses.name', 'asc')
            ->orderBy('rooms.name', 'asc')
            ->orderBy('shelves.name', 'asc')
            ->orderBy('locations.code', 'asc')
            ->get();
    }

    /**
     * Đơn vị tính cho ô chọn của phòng ban.
     *
     * Giữ lại những đơn vị phòng đang dùng dù chúng đã bị khoá / chưa duyệt, nếu không
     * màn hình cập nhật sẽ làm mất đơn vị cũ của dòng đang sửa.
     */
    public static function unitOptions(array $usedIds = [])
    {
        $usedIds = array_values(array_filter($usedIds));

        return DB::table('units')
            ->select('id', 'name', 'short_name')
            ->where(function ($query) use ($usedIds) {
                $query->where(function ($sub) {
                    $sub->where('status_id', 1)->where('app_status', 'approved');
                });

                if ($usedIds) {
                    $query->orWhereIn('id', $usedIds);
                }
            })
            ->orderBy('name', 'asc')
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

    /**
     * Hoá chất được phép NHẬP KHO của đúng một phòng ban.
     *
     * Có dòng ở chemical_department_categories = phòng đó được dùng hoá chất này. Phòng chưa khai ở
     * tab "Hoá Chất Của Phòng" thì không được nhập vào kho, nên ô chọn của màn hình Nhập
     * đi thẳng từ bảng này ra chứ không duyệt cả danh mục chung của công ty.
     *
     * Điều kiện bảo quản lấy theo quy tắc chung: của phòng trước, chưa khai thì theo danh mục.
     * Đơn vị tính chỉ có ở chemical_department_categories nên lấy thẳng từ dòng khai của phòng.
     *
     * $keepIds là các category_id đang nằm trên phiếu cũ của phòng: giữ lại để modal Điều
     * chỉnh không mất giá trị đang chọn khi danh mục chung đã bị khoá / thu hồi duyệt.
     * Dù thế nào cũng KHÔNG nới điều kiện "phòng đã khai".
     */
    public static function importCategoryOptions(int $departmentId, array $keepIds = [])
    {
        $keepIds = array_values(array_filter($keepIds));

        return DB::table(self::TABLE)
            ->join('chemical_categories', self::TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', 'chemical_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('storage_conditions', self::TABLE.'.storage_condition_id', '=', 'storage_conditions.id')
            ->leftJoin('storage_conditions as category_storage', 'chemical_categories.storage_condition_id', '=', 'category_storage.id')
            ->leftJoin('units', self::TABLE.'.unit_id', '=', 'units.id')
            ->select(
                'chemical_categories.id',
                'chemical_categories.code',
                'chemical_categories.classification',
                'chemical_categories.density',
                'chem_names.name as chem_name',
                'chem_names.cas_no as cas_no',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                // Chuỗi trong DB::raw là hằng, không ghép từ dữ liệu người dùng
                DB::raw('COALESCE(storage_conditions.name, category_storage.name) as storage_condition_name'),
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                self::TABLE.'.min_stock',
                self::TABLE.'.default_location_id'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->where(function ($query) use ($keepIds) {
                $query->where(function ($sub) {
                    $sub->where('chemical_categories.status_id', 1)
                        ->where('chemical_categories.app_status', 'approved');
                });

                if ($keepIds) {
                    $query->orWhereIn('chemical_categories.id', $keepIds);
                }
            })
            ->orderBy('chemical_categories.code', 'asc')
            ->get();
    }
}
