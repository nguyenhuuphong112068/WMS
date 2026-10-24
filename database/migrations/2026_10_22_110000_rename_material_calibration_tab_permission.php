<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tab "Hiệu Chuẩn" của Sử Dụng Vật Tư đổi tên thành "Xác Nhận Hiệu Chuẩn" - đổi theo tên quyền tab
 * (App\Support\PageTabs chỉ đặt display_name khi tạo quyền mới).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('Xác nhận hiệu chuẩn');
    }

    public function down(): void
    {
        $this->rename('Hiệu chuẩn trước cấp phát');
    }

    private function rename(string $label): void
    {
        DB::table('permissions')->where('name', 'export_material_tab_calibration')->update([
            'display_name' => 'Tab '.$label,
            'description' => DB::raw("CONCAT(SUBSTRING_INDEX(description, ' - tab ', 1), ' - tab \"".$label."\"')"),
            'updated_at' => now(),
        ]);
    }
};
