<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền xem tab "Sử dụng chuẩn" (export_standard_tab_use) của Sử Dụng Chất Chuẩn - danh sách
 * ống chuẩn đã cấp cho các Tổ, nút [Sử dụng] trên từng ống thay cho nút "Sử dụng chất chuẩn" ở
 * tab Sổ sử dụng. PageTabs::seed() tạo quyền con của export_standard_view và cấp cho Admin + mọi
 * vai trò đang xem được trang.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageTabs::seed();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('name', 'export_standard_tab_use')->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
