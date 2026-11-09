<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền ĐỊNH KHU NHANH HOÁ CHẤT - khối "Chưa xếp vị trí" ở tab Theo định khu (Tồn Kho Hoá Chất):
 * nút [Định khu] trên từng thẻ mã xuất nhập và nút định khu nhiều mã đã chọn cùng lúc.
 *
 * Định khu là sửa vị trí lưu trữ của phiếu nhập (chemical_imports.location_id, có ghi lịch sử phiếu)
 * nên quyền mới được cấp sẵn cho đúng các nhóm quyền (và quyền cấp riêng cho user) đang có Sửa Phiếu
 * Nhập Hoá Chất - bỏ tick ở màn Nhóm Quyền nếu không muốn cho định khu nhanh.
 */
return new class extends Migration
{
    private const NAME = 'inventory_chemical_location';

    private const FROM = 'import_chemical_update';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(['name' => self::NAME], [
            'permission_group' => 5,
            'display_name' => 'Định Khu Nhanh Hoá Chất',
            'description' => implode("\n", [
                'Xếp vị trí lưu trữ cho mã xuất nhập còn tồn mà chưa có định khu, ngay ở khối "Chưa xếp vị trí" của tab Theo định khu (Tồn Kho Hoá Chất): từng mã hoặc nhiều mã đã chọn vào cùng một vị trí. Mỗi mã được ghi lịch sử phiếu nhập và Audit Trail.',
                'Lưu ý: chỉ chọn được vị trí Dự Phòng / Sử Dụng / chưa phân loại; vị trí cùng kệ/tủ với hoá chất tương kỵ (Sơ đồ lưu trữ GHS) bị khoá. Mã đã có định khu muốn đổi chỗ thì Điều chỉnh phiếu nhập (ghi lý do).',
            ]),
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $newId = DB::table('permissions')->where('name', self::NAME)->value('id');
        $fromId = DB::table('permissions')->where('name', self::FROM)->value('id');

        if (! $newId || ! $fromId) {
            return;
        }

        foreach (DB::table('role_permission')->where('permission_id', $fromId)->pluck('role_id') as $roleId) {
            DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $newId], []);
        }

        foreach (DB::table('user_permission')->where('permission_id', $fromId)->get() as $row) {
            DB::table('user_permission')->updateOrInsert(
                ['user_id' => $row->user_id, 'permission_id' => $newId],
                ['is_denied' => $row->is_denied]
            );
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', self::NAME)->value('id');

        if ($id) {
            DB::table('user_permission')->where('permission_id', $id)->delete();
            DB::table('role_permission')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
