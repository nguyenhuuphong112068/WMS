<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * VẬT TƯ CỦA PHÒNG HÀNH CHÍNH: KHAI 10 DANH MỤC VĂN PHÒNG PHẨM
 *
 * Khai 10 dòng VPP #01..#10 của danh mục công ty (xem 2026_10_20_090000_...seed_vpp) vào
 * material_department_categories cho phòng làm bộ phận mua hàng Hành Chánh
 * (deparments.purchasing_key = admin; nhiều phòng thì lấy phòng đầu tiên).
 * Phòng chưa có định khu nên để trống; phân loại phòng lấy theo tên trong bộ nhóm của phòng
 * (department_classification), không có thì để trống. Dòng đã khai thì bỏ qua.
 */
return new class extends Migration
{
    private const ACTOR = 'Hệ thống';

    private const NOTE = 'Văn phòng phẩm';

    /** Thông tin kỹ thuật => [đơn vị, tối thiểu, tối đa, phân loại phòng] */
    private const ITEMS = [
        'VPP #01' => ['Thùng', 5, 30, 'Dự trù Hàng tháng'],      // Giấy in A4
        'VPP #02' => ['Hộp', 10, 50, 'Dự trù Hàng tháng'],       // Bút bi
        'VPP #03' => ['Cái/Chiếc', 10, 60, 'Dự trù Hàng tháng'], // Bút lông dầu
        'VPP #04' => ['Cái/Chiếc', 10, 60, 'Dự trù Hàng tháng'], // Bút dạ quang
        'VPP #05' => ['Cái/Chiếc', 20, 100, 'Dự trù Hàng quí'],  // Bìa còng
        'VPP #06' => ['Hộp', 10, 50, 'Dự trù Hàng tháng'],       // Kẹp giấy
        'VPP #07' => ['Hộp', 10, 50, 'Dự trù Hàng tháng'],       // Ghim bấm
        'VPP #08' => ['Gói', 20, 100, 'Dự trù Hàng tháng'],      // Giấy note
        'VPP #09' => ['Cuộn', 20, 100, 'Dự trù Hàng tháng'],     // Băng keo
        'VPP #10' => ['Cái/Chiếc', 20, 100, 'Dự trù Hàng quí'],  // Sổ tay
    ];

    public function up(): void
    {
        $departmentId = DB::table('deparments')->where('purchasing_key', 'admin')->orderBy('id')->value('id');

        if (! $departmentId) {
            return;
        }

        $categories = DB::table('material_categories')
            ->whereIn('technical_specification', array_keys(self::ITEMS))
            ->pluck('id', 'technical_specification');

        $units = DB::table('units')->where('status_id', 1)->pluck('id', 'name');

        $classifications = DB::table('department_classification')
            ->where('department_id', $departmentId)->where('status_id', 1)
            ->pluck('id', 'name');

        $declared = DB::table('material_department_categories')
            ->where('department_id', $departmentId)->pluck('category_id')->flip();

        foreach (self::ITEMS as $tag => [$unit, $min, $max, $classification]) {
            $categoryId = $categories[$tag] ?? null;

            if (! $categoryId || isset($declared[$categoryId])) {
                continue;
            }

            DB::table('material_department_categories')->insert([
                'department_id' => $departmentId,
                'category_id' => $categoryId,
                'classification_id' => $classifications[$classification] ?? null,
                'unit_id' => $units[$unit] ?? $units['Cái/Chiếc'] ?? null,
                'default_location_id' => null,
                'min_stock' => $min,
                'max_stock' => $max,
                'note' => self::NOTE,
                'status_id' => 1,
                'created_by' => self::ACTOR,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $categoryIds = DB::table('material_categories')
            ->whereIn('technical_specification', array_keys(self::ITEMS))->pluck('id');

        DB::table('material_department_categories')
            ->whereIn('category_id', $categoryIds)
            ->where('created_by', self::ACTOR)->where('note', self::NOTE)
            ->delete();
    }
};
