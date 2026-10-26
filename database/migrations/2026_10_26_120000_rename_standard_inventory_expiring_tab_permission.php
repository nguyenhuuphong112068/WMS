<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tab "Hạn dùng sắp hết" của Tồn Kho Chất Chuẩn dựng lại giống Tồn Kho Hoá Chất và đổi tên
 * thành "Cận hạn dùng" - cập nhật tên hiển thị của quyền xem tab (App\Support\PageTabs chỉ
 * đặt tên lúc tạo quyền).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('Hạn dùng sắp hết', 'Cận hạn dùng');
    }

    public function down(): void
    {
        $this->rename('Cận hạn dùng', 'Hạn dùng sắp hết');
    }

    private function rename(string $from, string $to): void
    {
        $permission = DB::table('permissions')->where('name', 'inventory_standard_tab_expiring')->first();

        if (! $permission) {
            return;
        }

        DB::table('permissions')->where('id', $permission->id)->update([
            'display_name' => 'Tab '.$to,
            'description' => str_replace('"'.$from.'"', '"'.$to.'"', (string) $permission->description),
            'updated_at' => now(),
        ]);
    }
};
