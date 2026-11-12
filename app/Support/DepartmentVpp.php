<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * CẤU HÌNH VPP THEO TỪNG PHÒNG BAN
 *
 * Danh mục VPP (vpp_categories) dùng chung toàn công ty vì nó mô tả bản chất của
 * VPP (kể cả ĐƠN VỊ TÍNH và nhóm dự trù - VppClassification). Cách dùng VPP thì riêng
 * từng phòng, nằm ở bảng vpp_department_categories: phân loại của phòng, ngưỡng tồn
 * tối thiểu / tối đa, định khu.
 *
 * Lớp này gom lại phần join để tab "VPP Của Phòng" (do VppCategoryController dựng)
 * và các thao tác thêm / sửa / khoá (do DepartmentVppController xử lý) đọc chung một
 * câu truy vấn, không lệch nhau. Vẫn là Query Builder thuần, không dùng Eloquent.
 *
 * Song song với App\Support\DepartmentChemical / DepartmentStandard. Lớp này cũng gom phần
 * join đơn vị tính để mọi màn hình hiện đơn vị đi qua một đường (joinUnit / joinUnitOn).
 * Khác Vật Tư / Hoá Chất / Chất Chuẩn: từ 12/11/2026 đơn vị VPP nằm ở vpp_categories.unit_id,
 * cả công ty dùng chung (không có quy đổi đơn vị giữa các phòng).
 */
class DepartmentVpp
{
    public const TABLE = 'vpp_department_categories';

    /** Bí danh của chính bảng này khi chỉ nối để lấy ngưỡng tồn / phân loại phòng - xem joinUnit(). */
    public const UNIT_ALIAS = 'dm_unit';

    /** Bí danh của vpp_categories khi joinUnit() nối để lấy đơn vị tính dùng chung. */
    public const UNIT_CATEGORY_ALIAS = 'dm_unit_cat';

    /**
     * DANH MỤC PHÒNG LUÔN ĐỦ BẰNG DANH MỤC CHUNG (12/11/2026): mỗi mã VPP công ty có đúng một
     * dòng ở MỌI phòng ban. Phòng không tự thêm / khoá dòng, chỉ sửa phần riêng (phân loại phòng,
     * ngưỡng tồn, định khu, ghi chú); mã bị khoá / chưa duyệt ở danh mục chung thì phòng cũng
     * không dùng được (các truy vấn lọc theo vpp_categories.status_id / app_status).
     *
     * Hàm này thêm các cặp (phòng, mã) còn thiếu - gọi sau khi thêm mã chung, thêm phòng ban
     * mới, và lúc mở trang Danh Mục VPP (phòng khi phòng được tạo bằng đường khác).
     * $categoryIds = null là mọi mã. Trả về số dòng vừa thêm.
     */
    public static function syncRows(?array $categoryIds = null, ?string $actor = null): int
    {
        $now = now();
        $actor = $actor ?? 'Hệ thống';

        $missing = DB::table('deparments')
            ->crossJoin('vpp_categories')
            ->leftJoin(self::TABLE.' as existing', function ($join) {
                $join->on('existing.department_id', '=', 'deparments.id')
                    ->on('existing.category_id', '=', 'vpp_categories.id');
            })
            ->whereNull('existing.id')
            ->when($categoryIds !== null, fn ($query) => $query->whereIn('vpp_categories.id', $categoryIds ?: [0]))
            ->select('deparments.id as department_id', 'vpp_categories.id as category_id')
            ->get();

        foreach ($missing->chunk(500) as $chunk) {
            DB::table(self::TABLE)->insertOrIgnore($chunk->map(fn ($row) => [
                'department_id' => (int) $row->department_id,
                'category_id' => (int) $row->category_id,
                'status_id' => 1,
                'created_by' => $actor,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all());
        }

        return $missing->count();
    }

    /**
     * Nối bảng cấu hình của ĐÚNG một phòng ban vào câu truy vấn đang có (để lấy min_stock,
     * default_location_id...). Điều kiện phòng ban đặt trong mệnh đề JOIN, không đặt ở WHERE:
     * đây là leftJoin, để ở WHERE sẽ loại mất VPP phòng chưa khai cấu hình.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $categoryColumn  Cột chứa category_id, ví dụ 'vpp_imports.category_id'
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
     * Nối ĐƠN VỊ TÍNH (của danh mục chung) + dòng khai của đúng một phòng ban (bí danh UNIT_ALIAS,
     * để đọc min_stock / classification_id...) vào câu truy vấn đang có.
     *
     * Không lọc status_id: phòng khoá dòng khai rồi thì phiếu cũ vẫn phải hiện đúng đơn vị.
     * Sau khi gọi, select 'units.short_name as unit_short_name' như bình thường.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $categoryColumn  Cột chứa category_id, ví dụ 'vpp_imports.category_id'
     */
    public static function joinUnit($query, int $departmentId, string $categoryColumn)
    {
        return $query
            ->leftJoin(self::TABLE.' as '.self::UNIT_ALIAS, function ($join) use ($departmentId, $categoryColumn) {
                $join->on(self::UNIT_ALIAS.'.category_id', '=', $categoryColumn)
                    ->where(self::UNIT_ALIAS.'.department_id', '=', $departmentId);
            })
            ->leftJoin('vpp_categories as '.self::UNIT_CATEGORY_ALIAS, self::UNIT_CATEGORY_ALIAS.'.id', '=', $categoryColumn)
            ->leftJoin('units', self::UNIT_CATEGORY_ALIAS.'.unit_id', '=', 'units.id');
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
            ->leftJoin('vpp_categories as '.self::UNIT_CATEGORY_ALIAS, self::UNIT_CATEGORY_ALIAS.'.id', '=', $categoryColumn)
            ->leftJoin('units', self::UNIT_CATEGORY_ALIAS.'.unit_id', '=', 'units.id');
    }

    /** Ngưỡng tồn tối thiểu của phòng, dùng trong select() sau khi đã gọi join(). */
    public static function minStockColumn(): string
    {
        return self::TABLE.'.min_stock';
    }

    /** Ngưỡng tồn tối đa của phòng, dùng trong select() sau khi đã gọi join(). */
    public static function maxStockColumn(): string
    {
        return self::TABLE.'.max_stock';
    }

    /**
     * VPP phòng ĐƯỢC PHÉP nhập / dùng: có dòng khai trong vpp_department_categories (còn hoạt
     * động) và danh mục chung đã duyệt. Kèm đơn vị tính của danh mục chung.
     *
     * $exclude là các category_id cần loại khỏi ô chọn (đã nhập rồi trong lần này...).
     */
    public static function importCategoryOptions(int $departmentId, array $exclude = [])
    {
        $query = DB::table(self::TABLE)
            ->join('vpp_categories', self::TABLE.'.category_id', '=', 'vpp_categories.id')
            ->leftJoin('material_names', 'vpp_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'vpp_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', 'vpp_categories.unit_id', '=', 'units.id')
            ->select(
                'vpp_categories.id',
                'vpp_categories.code',
                'vpp_categories.classification',
                // Phân loại của phòng - cùng với classification ở trên quyết định quy trình
                // trình ký của dòng đề nghị, xem App\Support\VppSignFlow
                self::TABLE.'.classification_id',
                'vpp_categories.purchasing_department',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                self::TABLE.'.min_stock',
                self::TABLE.'.max_stock',
                // Định khu phòng đã khai cho VPP này - màn hình Nhập điền sẵn vào ô vị trí
                self::TABLE.'.default_location_id',
                'vpp_categories.unit_id',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->where('vpp_categories.status_id', 1)
            ->where('vpp_categories.app_status', 'approved')
            ->orderBy('material_names.name', 'asc');

        $exclude = array_values(array_filter($exclude));
        if ($exclude) {
            $query->whereNotIn('vpp_categories.id', $exclude);
        }

        return $query->get();
    }

    /**
     * Định khu của đúng phòng ban đang chọn, kèm đường dẫn Kho / Kệ-Tủ / Cột / Tầng.
     *
     * Lọc theo locations.item_type để không xếp nhầm hàng vào ô của loại khác;
     * ô chưa khai loại được coi là dùng chung nên vẫn chọn được.
     *
     * $quarantineOnly = true: chỉ lấy vị trí zone_type = 'quarantine' (Biệt Trữ/Chờ
     * kiểm tra) - dùng cho modal Nhập VPP, vì lô mới nhập chỉ được định khu tạm vào
     * đây, chờ "Xác nhận kiểm tra" mới định khu lại vị trí thật.
     */
    public static function locationOptions(int $departmentId, bool $quarantineOnly = false)
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
            )
            ->where('locations.department_id', $departmentId)
            ->where('locations.status_id', 1)
            // Chỉ những ô khai loại VPP, cộng thêm ô chưa khai loại (dùng chung)
            ->where(fn ($query) => $query->whereNull('locations.item_type')
                ->orWhere('locations.item_type', 'vpp'))
            ->when($quarantineOnly, fn ($query) => $query->where('locations.zone_type', 'quarantine'))
            ->orderBy('warehouses.name', 'asc')
            ->orderBy('shelves.name', 'asc')
            ->orderBy('columns.name', 'asc')
            ->orderBy('tiers.name', 'asc')
            ->orderBy('locations.code', 'asc')
            ->get();
    }

    /**
     * Cấu hình VPP của ĐÚNG một phòng ban, kèm phần mô tả lấy từ danh mục chung.
     */
    public static function rowsOfDepartment(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->leftJoin('vpp_categories', self::TABLE.'.category_id', '=', 'vpp_categories.id')
            ->leftJoin('material_names', 'vpp_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'vpp_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', 'vpp_categories.unit_id', '=', 'units.id')
            // Phân loại riêng của phòng (dữ liệu gốc department_classification)
            ->leftJoin('department_classification', self::TABLE.'.classification_id', '=', 'department_classification.id')
            // Định khu phòng đã khai cho VPP, kèm đường dẫn Kho / Kệ-Tủ / Cột / Tầng
            ->leftJoin('locations', self::TABLE.'.default_location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->leftJoin('columns', 'locations.column_id', '=', 'columns.id')
            ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id')
            ->select(
                self::TABLE.'.*',
                'vpp_categories.code as category_code',
                'vpp_categories.classification as category_classification',
                // Tình trạng của mã ở danh mục chung - dòng phòng đi theo, không khoá riêng
                'vpp_categories.status_id as category_status_id',
                'vpp_categories.app_status as category_app_status',
                'vpp_categories.purchasing_department as category_purchasing_department',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'department_classification.name as classification_name',
                'locations.code as location_code',
                'locations.zone_type as location_zone_type',
                'warehouses.name as warehouse_name',
                'shelves.name as shelf_name',
                'columns.name as column_name',
                'tiers.name as tier_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy('material_names.name', 'asc')
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
     * Phân loại riêng của phòng cho ô chọn (dữ liệu gốc department_classification).
     *
     * Giữ lại những phân loại dòng đang sửa đã dùng dù chúng đã bị khoá, nếu không màn
     * hình cập nhật sẽ làm mất giá trị cũ.
     */
    public static function classificationOptions(int $departmentId, array $usedIds = [])
    {
        $usedIds = array_values(array_filter($usedIds));

        return DB::table('department_classification')
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

}
