<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * VẬT TƯ CỦA PHÒNG QC-B1: KHAI NỐT CÁC DANH MỤC VĂN PHÒNG PHẨM CÒN THIẾU
 *
 * Phòng Kiểm tra chất lượng - B1 (shortName QC-B1) đã tự khai Băng keo (VPP #09); khai thêm
 * các dòng VPP #01..#10 còn lại. Ngưỡng theo dòng phòng đã khai tay (tối thiểu 10, tối đa 200),
 * đơn vị theo loại hàng; định khu lấy lần lượt các vị trí Sử Dụng cho vật tư của phòng chưa làm
 * định khu mặc định cho dòng nào. Dòng đã khai thì bỏ qua.
 */
return new class extends Migration
{
    private const ACTOR = 'Hệ thống';

    private const NOTE = 'Văn phòng phẩm';

    private const DEPARTMENT_SHORT_NAME = 'QC-B1';

    /** Thông tin kỹ thuật => đơn vị */
    private const ITEMS = [
        'VPP #01' => 'Thùng',     // Giấy in A4
        'VPP #02' => 'Hộp',       // Bút bi
        'VPP #03' => 'Cái/Chiếc', // Bút lông dầu
        'VPP #04' => 'Cái/Chiếc', // Bút dạ quang
        'VPP #05' => 'Cái/Chiếc', // Bìa còng
        'VPP #06' => 'Hộp',       // Kẹp giấy
        'VPP #07' => 'Hộp',       // Ghim bấm
        'VPP #08' => 'Gói',       // Giấy note
        'VPP #09' => 'Cuộn',      // Băng keo
        'VPP #10' => 'Cái/Chiếc', // Sổ tay
    ];

    private const MIN_STOCK = 10;

    private const MAX_STOCK = 200;

    public function up(): void
    {
        $departmentId = DB::table('deparments')->where('shortName', self::DEPARTMENT_SHORT_NAME)->value('id');

        if (! $departmentId) {
            return;
        }

        $categories = DB::table('material_categories')
            ->whereIn('technical_specification', array_keys(self::ITEMS))
            ->pluck('id', 'technical_specification');

        $units = DB::table('units')->where('status_id', 1)->pluck('id', 'name');

        $declared = DB::table('material_department_categories')
            ->where('department_id', $departmentId)->pluck('category_id')->flip();

        $freeLocations = DB::table('locations')
            ->where('department_id', $departmentId)->where('status_id', 1)
            ->where('zone_type', 'in_use')
            ->where(fn ($q) => $q->whereNull('item_type')->orWhere('item_type', 'material'))
            ->whereNotIn('id', DB::table('material_department_categories')
                ->where('department_id', $departmentId)->whereNotNull('default_location_id')
                ->select('default_location_id'))
            ->orderBy('id')->pluck('id')->all();

        foreach (self::ITEMS as $tag => $unit) {
            $categoryId = $categories[$tag] ?? null;

            if (! $categoryId || isset($declared[$categoryId])) {
                continue;
            }

            DB::table('material_department_categories')->insert([
                'department_id' => $departmentId,
                'category_id' => $categoryId,
                'classification_id' => null,
                'unit_id' => $units[$unit] ?? $units['Cái/Chiếc'] ?? null,
                'default_location_id' => array_shift($freeLocations),
                'min_stock' => self::MIN_STOCK,
                'max_stock' => self::MAX_STOCK,
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
        $departmentId = DB::table('deparments')->where('shortName', self::DEPARTMENT_SHORT_NAME)->value('id');

        $categoryIds = DB::table('material_categories')
            ->whereIn('technical_specification', array_keys(self::ITEMS))->pluck('id');

        DB::table('material_department_categories')
            ->where('department_id', $departmentId)
            ->whereIn('category_id', $categoryIds)
            ->where('created_by', self::ACTOR)->where('note', self::NOTE)
            ->delete();
    }
};
