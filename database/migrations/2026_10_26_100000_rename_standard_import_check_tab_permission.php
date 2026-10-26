<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tab "Chờ kiểm tra" của Nhập Chất Chuẩn đổi tên thành "Nhập chất chuẩn" (lên đầu, chứa nút lập
 * phiếu nhập) - cập nhật tên hiển thị của quyền xem tab (App\Support\PageTabs chỉ đặt tên lúc tạo quyền).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('Chờ kiểm tra', 'Nhập chất chuẩn');
    }

    public function down(): void
    {
        $this->rename('Nhập chất chuẩn', 'Chờ kiểm tra');
    }

    private function rename(string $from, string $to): void
    {
        $permission = DB::table('permissions')->where('name', 'import_standard_tab_check')->first();

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
