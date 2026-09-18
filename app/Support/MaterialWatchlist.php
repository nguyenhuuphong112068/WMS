<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * VẬT TƯ CẦN DỰ TRÙ - dùng cho tab "Danh sách vật tư cần dự trù" (Dự Trù Vật Tư).
 *
 * Gộp hai nguồn:
 *   1. TỰ ĐỘNG: vật tư của phòng đang tồn dưới ngưỡng tối thiểu đã khai
 *      (material_department_categories.min_stock) - tồn tính qua
 *      App\Support\MaxStockWarning::onHandMaterial(), đúng công thức dùng ở màn Tồn Kho.
 *   2. THỦ CÔNG: người dùng bấm nút "Đề nghị dự trù vật tư" (phải nhập lý do) - hoặc ngay
 *      trên dòng đề nghị của modal Đề Nghị Thường Quy / Theo ĐG Rủi Ro (SỬ DỤNG VẬT TƯ,
 *      không phụ thuộc đề nghị đó có được lưu hay không), hoặc ngay trên danh mục
 *      "Vật Tư Của Phòng" (DANH MỤC) - lưu ở bảng material_watchlist_items.
 *
 * Một vật tư trong danh mục vừa dưới ngưỡng vừa được ghi nhớ thủ công chỉ hiện MỘT dòng -
 * xem forTab().
 */
class MaterialWatchlist
{
    public const TABLE = 'material_watchlist_items';

    /** Vật tư trong danh mục của phòng đang tồn dưới ngưỡng tối thiểu đã khai. */
    public static function belowThreshold(int $departmentId)
    {
        $rows = DB::table(DepartmentMaterial::TABLE)
            ->join('material_categories', DepartmentMaterial::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', DepartmentMaterial::TABLE.'.unit_id', '=', 'units.id')
            ->select(
                'material_categories.id as category_id',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                DepartmentMaterial::TABLE.'.min_stock',
                'units.short_name as unit_short_name'
            )
            ->where(DepartmentMaterial::TABLE.'.department_id', $departmentId)
            ->where(DepartmentMaterial::TABLE.'.status_id', 1)
            ->where('material_categories.status_id', 1)
            ->where('material_categories.app_status', 'approved')
            ->whereNotNull(DepartmentMaterial::TABLE.'.min_stock')
            ->where(DepartmentMaterial::TABLE.'.min_stock', '>', 0)
            ->orderBy('material_names.name', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            return $rows;
        }

        $onHand = MaxStockWarning::onHandMaterial($departmentId);

        return $rows->map(function ($row) use ($onHand) {
            $row->on_hand = (float) ($onHand[(int) $row->category_id] ?? 0);

            return $row;
        })->filter(fn ($row) => $row->on_hand < (float) $row->min_stock)->values();
    }

    /** Các dòng được ghi nhớ thủ công còn hoạt động của phòng, kèm mô tả lấy từ danh mục. */
    public static function rememberedRows(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->leftJoin('material_categories', self::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, self::TABLE.'.category_id'))
            ->leftJoin(DepartmentMaterial::TABLE.' as dm_status', function ($join) use ($departmentId) {
                $join->on('dm_status.category_id', '=', self::TABLE.'.category_id')
                    ->where('dm_status.department_id', '=', $departmentId);
            })
            ->select(
                self::TABLE.'.*',
                'material_categories.technical_specification as category_technical_specification',
                'material_names.name as category_material_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->where(function ($q) {
                $q->whereNull('dm_status.status_id')
                    ->orWhere('dm_status.status_id', '!=', 0);
            })
            ->orderBy(self::TABLE.'.created_at', 'desc')
            ->get();
    }

    /** Danh sách gộp cho tab "Vật tư cần dự trù": dưới ngưỡng + ghi nhớ thủ công, không trùng dòng. */
    public static function forTab(int $departmentId)
    {
        $rows = [];

        foreach (self::belowThreshold($departmentId) as $t) {
            $rows['cat_'.$t->category_id] = (object) [
                'watchlist_id' => null,
                'category_id' => $t->category_id,
                'material_name' => $t->material_name,
                'technical_specification' => $t->technical_specification,
                'manufacturer_name' => $t->manufacturer_short_name,
                'unit' => $t->unit_short_name,
                'on_hand' => $t->on_hand,
                'min_stock' => $t->min_stock,
                'below_threshold' => true,
                'source_type' => null,
                'note' => null,
                'remembered_by' => null,
                'remembered_at' => null,
            ];
        }

        foreach (self::rememberedRows($departmentId) as $r) {
            $key = $r->category_id ? 'cat_'.$r->category_id : 'name_'.mb_strtolower(trim((string) $r->material_name));

            if (isset($rows[$key])) {
                $rows[$key]->watchlist_id = $r->id;
                $rows[$key]->source_type = $r->source_type;
                $rows[$key]->note = $r->note;
                $rows[$key]->remembered_by = $r->created_by;
                $rows[$key]->remembered_at = $r->created_at;

                continue;
            }

            $rows[$key] = (object) [
                'watchlist_id' => $r->id,
                'category_id' => $r->category_id,
                'material_name' => $r->category_id ? $r->category_material_name : $r->material_name,
                'technical_specification' => $r->category_id ? $r->category_technical_specification : $r->technical_specification,
                'manufacturer_name' => $r->manufacturer_short_name,
                'unit' => $r->unit_short_name,
                'on_hand' => null,
                'min_stock' => null,
                'below_threshold' => false,
                'source_type' => $r->source_type,
                'note' => $r->note,
                'remembered_by' => $r->created_by,
                'remembered_at' => $r->created_at,
            ];
        }

        return collect(array_values($rows))->sortBy('material_name')->values();
    }

    /**
     * Tạo một "Đề nghị dự trù vật tư" (nút trên dòng đề nghị của modal Thường Quy / Theo ĐG
     * Rủi Ro, hoặc trên tab "Vật Tư Của Phòng") - bắt buộc nêu lý do dự trù. Vật tư trong
     * danh mục khoá theo (department_id, category_id) - bấm lại chỉ cập nhật lại lý do /
     * nguồn, không tạo dòng trùng. Vật tư ngoài danh mục khoá theo tên (không phân biệt hoa
     * thường) vì không có category_id để ràng buộc duy nhất.
     */
    public static function remember(int $departmentId, ?int $categoryId, ?string $materialName, ?string $technicalSpecification, ?string $note, ?string $sourceType, string $actor): array
    {
        $materialName = trim((string) $materialName) ?: null;
        $technicalSpecification = trim((string) $technicalSpecification) ?: null;
        $note = trim((string) $note) ?: null;

        if (! $categoryId && ! $materialName) {
            return ['success' => false, 'message' => 'Vui lòng chọn vật tư trong danh mục hoặc nhập tên vật tư trước khi đề nghị dự trù!'];
        }

        if (! $note) {
            return ['success' => false, 'message' => 'Vui lòng nhập lý do dự trù.'];
        }

        if ($categoryId) {
            $isLocked = DB::table(DepartmentMaterial::TABLE)
                ->where('department_id', $departmentId)
                ->where('category_id', $categoryId)
                ->where('status_id', 0)
                ->exists();

            if ($isLocked) {
                return ['success' => false, 'message' => 'Vật tư của phòng đã bị khoá, không thể đề nghị dự trù!'];
            }
        }

        $query = DB::table(self::TABLE)->where('department_id', $departmentId);
        $query = $categoryId
            ? $query->where('category_id', $categoryId)
            : $query->whereNull('category_id')->whereRaw('LOWER(material_name) = ?', [mb_strtolower($materialName)]);

        $existing = $query->first();

        $payload = [
            'technical_specification' => $technicalSpecification,
            'note' => $note,
            'source_type' => $sourceType,
            'status_id' => 1,
            'updated_by' => $actor,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table(self::TABLE)->where('id', $existing->id)->update($payload);

            return ['success' => true, 'message' => 'Vật tư đã có trong danh sách cần dự trù, đã cập nhật lại lý do dự trù!'];
        }

        DB::table(self::TABLE)->insert($payload + [
            'department_id' => $departmentId,
            'category_id' => $categoryId,
            'material_name' => $categoryId ? null : $materialName,
            'created_by' => $actor,
            'created_at' => now(),
        ]);

        return ['success' => true, 'message' => 'Đã gửi đề nghị dự trù vật tư!'];
    }

    /** Bỏ ghi nhớ một dòng thủ công - vật tư vẫn hiện lại nếu đang tồn dưới ngưỡng tối thiểu. */
    public static function dismiss(int $id, int $departmentId, string $actor): bool
    {
        return (bool) DB::table(self::TABLE)
            ->where('id', $id)
            ->where('department_id', $departmentId)
            ->update(['status_id' => 0, 'updated_by' => $actor, 'updated_at' => now()]);
    }
}
