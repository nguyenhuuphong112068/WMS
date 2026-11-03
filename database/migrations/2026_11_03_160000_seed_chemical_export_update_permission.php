<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền ĐIỀU CHỈNH SỔ SỬ DỤNG HOÁ CHẤT - nút Sửa và Khoá / Mở khoá ở tab Sổ sử dụng hoá chất (Sử Dụng Hoá Chất),
 * tách khỏi quyền Xuất Kho Hoá Chất (export_chemical_issue) như Vật Tư (export_material_update).
 *
 * Phiếu sử dụng đã ghi là đã trừ tồn nên sửa / khoá phải có quyền riêng; ChemicalExportController::update() và
 * deActive() chặn lại ở server. Để không ai mất thao tác đang dùng, quyền mới được cấp sẵn cho đúng các nhóm quyền
 * (và quyền cấp riêng cho user) đang có Xuất Kho Hoá Chất - bỏ tick ở màn Nhóm Quyền nếu không muốn cho điều chỉnh.
 */
return new class extends Migration
{
    private const NAME = 'export_chemical_update';

    /** Quyền đang cho bấm Sửa / Khoá ở Sổ sử dụng hoá chất trước khi tách */
    private const FROM = 'export_chemical_issue';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(['name' => self::NAME], [
            'permission_group' => 4,
            'display_name' => 'Điều Chỉnh Sổ Sử Dụng Hoá Chất',
            'description' => implode("\n", [
                'Sửa (lô, số lượng, người kiểm tra, số PKN / OOS / BCSL, mục đích) hoặc khoá / mở khoá một dòng ở tab Sổ sử dụng hoá chất. Khoá thì số lượng được cộng trả lại tồn, mở khoá thì trừ lại; mỗi lần sửa được lưu lịch sử điều chỉnh.',
                'Lưu ý: nút Sửa chỉ mở cho phiếu sử dụng trong ngày (qua ngày là chốt sổ); dòng sinh từ phiếu cách ly chỉ xử lý ở tab Cách ly - Loại bỏ - Huỷ; dòng chuyển cho phòng khác không sửa được.',
            ]),
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        DB::table('permissions')->where('name', self::FROM)->update([
            'description' => implode("\n", [
                'Ghi phiếu Sử dụng hoá chất ở tab Sử Dụng Hoá Chất (quét mã, chọn từ tồn kho hoặc theo gói sản phẩm - chỉ tiêu): lưu là trừ tồn ngay, không cần ký duyệt.',
                'Lưu ý: dùng hoá chất cấm bắt buộc chọn Người Kiểm Tra là người có vai trò Quản Lý Kho Hoá Chất của phòng.',
                'Lưu ý: sửa hoặc khoá dòng đã ghi ở Sổ sử dụng hoá chất cần quyền Điều Chỉnh Sổ Sử Dụng Hoá Chất.',
            ]),
            'updated_at' => $now,
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
