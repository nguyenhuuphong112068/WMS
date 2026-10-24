<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tab "Hạn dùng sắp hết" của Tồn Kho Hoá Chất đổi tên thành "Cận hạn dùng" - cập nhật
 * tên hiển thị của quyền xem tab (App\Support\PageTabs chỉ đặt tên lúc tạo quyền).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('Cận hạn dùng');
    }

    public function down(): void
    {
        $this->rename('Hạn dùng sắp hết');
    }

    private function rename(string $label): void
    {
        DB::table('permissions')->where('name', 'inventory_chemical_tab_expiring')->update([
            'display_name' => 'Tab '.$label,
            'description' => 'Xem Tồn Kho Hoá Chất - tab "'.$label.'"',
            'updated_at' => now(),
        ]);
    }
};
