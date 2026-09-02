<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * CẤU HÌNH VẬT TƯ THEO TỪNG PHÒNG BAN
 *
 * Danh mục vật tư (material_categories) dùng chung toàn công ty vì nó mô tả bản chất của
 * vật tư. Cách dùng vật tư thì riêng từng phòng, nằm ở bảng material_department_categories: phân
 * loại theo bộ nhóm của phòng, đơn vị tính, ngưỡng tồn tối thiểu.
 *
 * Lớp này gom lại phần join để tab "Vật Tư Của Phòng" (do MaterialCategoryController dựng)
 * và các thao tác thêm / sửa / khoá (do DepartmentMaterialController xử lý) đọc chung một
 * câu truy vấn, không lệch nhau. Vẫn là Query Builder thuần, không dùng Eloquent.
 *
 * Song song với App\Support\DepartmentChemical / DepartmentStandard. Từ khi có màn Nhập /
 * Sử Dụng / Tồn / Dự Trù vật tư, lớp này cũng gom phần join đơn vị tính của phòng để mọi
 * màn hình hiện đơn vị đi qua một đường (joinUnit / joinUnitOn) - đơn vị nằm ở
 * material_department_categories.unit_id, danh mục chung không còn cột đó.
 */
class DepartmentMaterial
{
    public const TABLE = 'material_department_categories';

    /** Bí danh của chính bảng này khi chỉ nối để lấy đơn vị / ngưỡng tồn - xem joinUnit(). */
    public const UNIT_ALIAS = 'dm_unit';

    /**
     * Nối bảng cấu hình của ĐÚNG một phòng ban vào câu truy vấn đang có (để lấy min_stock,
     * classification_id...). Điều kiện phòng ban đặt trong mệnh đề JOIN, không đặt ở WHERE:
     * đây là leftJoin, để ở WHERE sẽ loại mất vật tư phòng chưa khai cấu hình.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $categoryColumn  Cột chứa category_id, ví dụ 'material_imports.category_id'
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
     * Nối ĐƠN VỊ TÍNH của đúng một phòng ban vào câu truy vấn đang có.
     *
     * Không lọc status_id: phòng khoá dòng khai rồi thì phiếu cũ vẫn phải hiện đúng đơn vị
     * đã dùng. Sau khi gọi, select 'units.short_name as unit_short_name' như bình thường.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $categoryColumn  Cột chứa category_id, ví dụ 'material_imports.category_id'
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
     * Như joinUnit() nhưng phòng ban lấy theo MỘT CỘT của câu truy vấn, không phải một số
     * cố định. Dùng cho màn hình đọc dữ liệu của nhiều phòng cùng lúc.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $departmentColumn  Cột chứa department_id
     * @param  string  $categoryColumn    Cột chứa category_id
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

    /** Ngưỡng tồn tối thiểu của phòng, dùng trong select() sau khi đã gọi join(). */
    public static function minStockColumn(): string
    {
        return self::TABLE.'.min_stock';
    }

    /**
     * Vật tư phòng ĐƯỢC PHÉP nhập / dùng: có dòng khai trong material_department_categories (còn hoạt
     * động) và danh mục chung đã duyệt. Kèm đơn vị của phòng.
     *
     * $exclude là các category_id cần loại khỏi ô chọn (đã nhập rồi trong lần này...).
     */
    public static function importCategoryOptions(int $departmentId, array $exclude = [])
    {
        $query = DB::table(self::TABLE)
            ->join('material_categories', self::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('material_classifications', self::TABLE.'.classification_id', '=', 'material_classifications.id')
            ->leftJoin('units', self::TABLE.'.unit_id', '=', 'units.id')
            ->select(
                'material_categories.id',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'material_classifications.name as classification_name',
                self::TABLE.'.min_stock',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->where('material_categories.status_id', 1)
            ->where('material_categories.app_status', 'approved')
            ->orderBy('material_names.name', 'asc');

        $exclude = array_values(array_filter($exclude));
        if ($exclude) {
            $query->whereNotIn('material_categories.id', $exclude);
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
            // Chỉ những ô khai loại vật tư, cộng thêm ô chưa khai loại (dùng chung)
            ->where(fn ($query) => $query->whereNull('locations.item_type')
                ->orWhere('locations.item_type', 'material'))
            ->orderBy('warehouses.name', 'asc')
            ->orderBy('rooms.name', 'asc')
            ->orderBy('shelves.name', 'asc')
            ->orderBy('locations.code', 'asc')
            ->get();
    }

    /**
     * Cấu hình vật tư của ĐÚNG một phòng ban, kèm phần mô tả lấy từ danh mục chung.
     */
    public static function rowsOfDepartment(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->leftJoin('material_categories', self::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('material_classifications', self::TABLE.'.classification_id', '=', 'material_classifications.id')
            ->leftJoin('units', self::TABLE.'.unit_id', '=', 'units.id')
            ->select(
                self::TABLE.'.*',
                'material_categories.technical_specification as category_technical_specification',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'material_classifications.name as classification_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy('material_names.name', 'asc')
            ->get();
    }

    /**
     * Vật tư được phép khai: đã duyệt và còn hoạt động trong danh mục chung.
     *
     * $exclude là các category_id phòng đã khai rồi - loại khỏi ô chọn của modal Thêm mới
     * để không đụng ràng buộc unique(department_id, category_id).
     */
    public static function categoryOptions(array $exclude = [])
    {
        $query = DB::table('material_categories')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->select(
                'material_categories.id',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name'
            )
            ->where('material_categories.status_id', 1)
            ->where('material_categories.app_status', 'approved')
            ->orderBy('material_names.name', 'asc');

        $exclude = array_values(array_filter($exclude));

        if ($exclude) {
            $query->whereNotIn('material_categories.id', $exclude);
        }

        return $query->get();
    }

    /**
     * Bộ phân loại của đúng phòng ban đang chọn.
     *
     * Giữ lại những phân loại phòng đang gán dù đã bị khoá, nếu không màn hình cập nhật
     * sẽ làm mất phân loại cũ của dòng đang sửa.
     */
    public static function classificationOptions(int $departmentId, array $usedIds = [])
    {
        $usedIds = array_values(array_filter($usedIds));

        return DB::table('material_classifications')
            ->select('id', 'name')
            ->where('department_id', $departmentId)
            ->where(function ($query) use ($usedIds) {
                $query->where('status_id', 1);

                if ($usedIds) {
                    $query->orWhereIn('id', $usedIds);
                }
            })
            ->orderBy('name', 'asc')
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

    /**
     * Các phòng ban đang dùng từng vật tư: [category_id => [{id, name, shortName}]].
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
}
