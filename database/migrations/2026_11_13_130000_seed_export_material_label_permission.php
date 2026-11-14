<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền IN NHÃN CẤP PHÁT VẬT TƯ - nút in nhãn ở tab Sổ Cấp Phát (Sử Dụng Vật Tư).
 *
 * Cấp sẵn cho Admin và mọi vai trò đang có quyền Xuất Kho Vật Tư (export_material_issue),
 * vì người cấp phát là người dán nhãn khi giao vật tư.
 */
return new class extends Migration
{
    private const NAME = 'export_material_label';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(['name' => self::NAME], [
            'permission_group' => 4,
            'display_name' => 'In Nhãn Cấp Phát Vật Tư',
            'description' => implode("\n", [
                'In nhãn cấp phát (tên vật tư, ngày cấp, hạn dùng, số lượng, người đề nghị, mục đích, QR mã xuất nhập) cho một dòng ở tab Sổ cấp phát. Mỗi lần in được ghi Audit Trail.',
                'Cũng in được nhãn Cách ly - Chờ quyết định và Loại bỏ - Chờ huỷ cho dòng sinh từ phiếu cách ly.',
                'Lưu ý: phiếu cách ly đã huỷ / trả về kho, dòng loại bỏ lập thẳng và cấp phát liên phòng ban không có nhãn.',
            ]),
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $permissionId = DB::table('permissions')->where('name', self::NAME)->value('id');

        $roleIds = DB::table('roles')->where('name', 'Admin')->pluck('id')
            ->merge(
                DB::table('role_permission')
                    ->join('permissions', 'role_permission.permission_id', '=', 'permissions.id')
                    ->where('permissions.name', 'export_material_issue')
                    ->pluck('role_permission.role_id')
            )
            ->unique();

        foreach ($roleIds as $roleId) {
            DB::table('role_permission')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ], []);
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('name', self::NAME)->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
