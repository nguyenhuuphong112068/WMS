<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền ĐỊNH KHU VẬT TƯ - tab Tồn kho theo vị trí (Tồn Kho Vật Tư): Định khu nhanh mã "Chưa xếp vị trí",
 * nút [Thêm vật tư] trong hộp chi tiết vị trí, Đổi vị trí (bắt lý do) và kéo thả - giống Tồn Kho Hoá Chất.
 *
 * Định khu là sửa vị trí lưu trữ của phiếu nhập (material_imports.location_id, có ghi lịch sử phiếu) nên quyền
 * được cấp sẵn cho đúng các nhóm quyền (và quyền cấp riêng cho user) đang có Sửa Phiếu Nhập Vật Tư.
 */
return new class extends Migration
{
    private const NAME = 'inventory_material_location';

    private const FROM = 'import_material_update';

    private const LIKE = 'inventory_chemical_location';

    public function up(): void
    {
        $now = now();
        $like = DB::table('permissions')->where('name', self::LIKE)->first();

        DB::table('permissions')->updateOrInsert(['name' => self::NAME], [
            'permission_group' => $like->permission_group ?? 5,
            'display_name' => 'Định Khu Vật Tư',
            'description' => implode("\n", [
                'Xếp vị trí lưu trữ cho mã xuất nhập vật tư còn tồn ở tab Tồn kho theo vị trí (Tồn Kho Vật Tư): Định khu mã "Chưa xếp vị trí", Thêm vật tư vào một ô, Đổi vị trí mã đã có định khu (bắt buộc lý do) và kéo thả giữa các ô. Mỗi mã được ghi lịch sử phiếu nhập và Audit Trail.',
                'Lưu ý: chỉ chọn được vị trí Dự Phòng / Sử Dụng / chưa phân loại; hàng ở khu Biệt Trữ / Cách Ly / Loại Bỏ xử lý theo luồng Cách ly.',
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
