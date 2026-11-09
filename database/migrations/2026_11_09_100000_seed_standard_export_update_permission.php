<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền ĐIỀU CHỈNH SỔ SỬ DỤNG CHẤT CHUẨN - nút Sửa ở tab Sổ sử dụng chất chuẩn (Sử Dụng Chất Chuẩn),
 * tách khỏi quyền Xuất Kho Chất Chuẩn (export_standard_issue) như Hoá Chất / Vật Tư. Nút Sửa từng
 * gắn với export_standard_issue rồi bị gỡ (07/09/2026), nay mở lại với quyền riêng.
 *
 * Đồng thời BỎ chặn "chỉ sửa phiếu trong ngày" ở Sổ sử dụng hoá chất: phiếu ngày cũ vẫn điều chỉnh
 * được, nên diễn giải quyền export_chemical_update viết lại cho đúng.
 *
 * Để không ai mất thao tác, quyền mới được cấp sẵn cho đúng các nhóm quyền (và quyền cấp riêng cho
 * user) đang có Xuất Kho Chất Chuẩn - bỏ tick ở màn Nhóm Quyền nếu không muốn cho điều chỉnh.
 */
return new class extends Migration
{
    private const NAME = 'export_standard_update';

    private const FROM = 'export_standard_issue';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(['name' => self::NAME], [
            'permission_group' => 4,
            'display_name' => 'Điều Chỉnh Sổ Sử Dụng Chất Chuẩn',
            'description' => implode("\n", [
                'Sửa (ống chuẩn, Tổ, số lượng, sản phẩm, số lô, chỉ tiêu) một dòng ở tab Sổ sử dụng chất chuẩn, kể cả phiếu ngày cũ. Mỗi lần sửa được lưu lịch sử điều chỉnh.',
                'Lưu ý: dòng sinh từ phiếu cách ly chỉ xử lý ở tab Cách ly - Loại bỏ - Huỷ; dòng cấp phát liên phòng ban không sửa được; loại phiếu không đổi được.',
            ]),
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        DB::table('permissions')->where('name', 'export_chemical_update')->update([
            'description' => implode("\n", [
                'Sửa (lô, số lượng, người kiểm tra, số PKN / OOS / BCSL, mục đích) hoặc khoá / mở khoá một dòng ở tab Sổ sử dụng hoá chất, kể cả phiếu ngày cũ. Khoá thì số lượng được cộng trả lại tồn, mở khoá thì trừ lại; mỗi lần sửa được lưu lịch sử điều chỉnh.',
                'Lưu ý: dòng sinh từ phiếu cách ly chỉ xử lý ở tab Cách ly - Loại bỏ - Huỷ; dòng chuyển cho phòng khác không sửa được.',
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
