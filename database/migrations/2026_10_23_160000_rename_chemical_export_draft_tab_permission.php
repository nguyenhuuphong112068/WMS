<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tab "Phiếu tạm" của Sử Dụng Hoá Chất đổi tên thành "Sử Dụng Hoá Chất" (lên đầu, chứa nút lập
 * phiếu sử dụng) - cập nhật tên hiển thị của quyền xem tab (App\Support\PageTabs chỉ đặt tên lúc tạo quyền).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('Sử Dụng Hoá Chất');
    }

    public function down(): void
    {
        $this->rename('Phiếu tạm');
    }

    private function rename(string $label): void
    {
        DB::table('permissions')->where('name', 'export_chemical_tab_draft')->update([
            'display_name' => 'Tab '.$label,
            'description' => 'Xem Cấp Phát Hoá Chất - tab "'.$label.'"',
            'updated_at' => now(),
        ]);
    }
};
