<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 18/11/2026 Danh mục VPP công ty không qua bước duyệt: thêm mới là dùng được ngay.
 * - Các mã đang "Chờ duyệt" chuyển sang "Đã duyệt" (cột app_status giữ lại vì các màn hình
 *   nghiệp vụ VPP vẫn lọc vpp_categories.app_status = 'approved').
 * - Xoá quyền category_vpp_approve cùng mọi gán quyền của vai trò / người dùng.
 * - Viết lại diễn giải quyền Thêm / Sửa / Tab Danh mục VPP công ty (bỏ câu chờ duyệt và các ô đã bỏ 12/11).
 * down() chỉ tạo lại quyền + diễn giải cũ, không gán lại cho vai trò và không đưa mã về chờ duyệt.
 */
return new class extends Migration
{
    private const NAME = 'category_vpp_approve';

    /** name => [diễn giải cũ, diễn giải mới] */
    private const DESCRIPTIONS = [
        'category_vpp_create' => [
            "Thêm mã VPP mới vào Danh mục VPP công ty: chọn tên VPP và nhà sản xuất, khai thông tin kỹ thuật, Part Number, 4 tiêu chí phân loại, bộ phận mua hàng, thời gian đặt hàng.
Lưu ý: mã M00001... tự sinh. Mã mới ở trạng thái Chờ duyệt, các phòng chỉ dùng được sau khi được duyệt.",
            "Thêm mã VPP mới vào Danh mục VPP công ty (nhiều dòng một lần): chọn tên VPP, nhà sản xuất, đơn vị tính, nhóm dự trù.
Lưu ý: mã P00001... tự sinh. Mã mới dùng được ngay, không qua bước duyệt.",
        ],
        'category_vpp_update' => [
            "Sửa thông tin mã VPP trong Danh mục VPP công ty (nút Sửa trên dòng). Mỗi lần sửa được lưu vào lịch sử thay đổi của mã.
Lưu ý: sửa xong mã quay về Chờ duyệt và phải được duyệt lại. Thời gian đặt hàng không sửa ở đây mà dùng quyền Điều Chỉnh Thời Gian Đặt Hàng.",
            "Sửa thông tin mã VPP trong Danh mục VPP công ty (nút Sửa trên dòng). Mỗi lần sửa được lưu vào lịch sử thay đổi của mã.
Lưu ý: phải ghi lý do thay đổi; mã vẫn dùng được ngay, không phải duyệt lại.",
        ],
        'category_vpp_tab_company' => [
            'Xem tab Danh mục VPP công ty: toàn bộ mã VPP dùng chung công ty với phân loại, bộ phận mua hàng, thời gian đặt hàng, trạng thái duyệt và lịch sử thay đổi.',
            'Xem tab Danh mục VPP công ty: toàn bộ mã VPP dùng chung công ty với nhóm dự trù, đơn vị tính, trạng thái sử dụng và lịch sử thay đổi.',
        ],
    ];

    public function up(): void
    {
        $this->swapDescriptions(0, 1);

        DB::table('vpp_categories')
            ->where(fn ($query) => $query->whereNull('app_status')->orWhere('app_status', '<>', 'approved'))
            ->update([
                'app_status' => 'approved',
                'approved_by' => DB::raw("COALESCE(approved_by, 'Hệ thống')"),
                'approved_at' => DB::raw('COALESCE(approved_at, NOW())'),
            ]);

        $ids = DB::table('permissions')->where('name', self::NAME)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        foreach (['role_permission', 'user_permission'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->whereIn('permission_id', $ids)->delete();
            }
        }

        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    private function swapDescriptions(int $from, int $to): void
    {
        foreach (self::DESCRIPTIONS as $name => $texts) {
            DB::table('permissions')->where('name', $name)->where('description', $texts[$from])
                ->update(['description' => $texts[$to]]);
        }
    }

    public function down(): void
    {
        $this->swapDescriptions(1, 0);

        if (DB::table('permissions')->where('name', self::NAME)->exists()) {
            return;
        }

        DB::table('permissions')->insert([
            'permission_group' => DB::table('permissions')->where('name', 'category_vpp_view')->value('permission_group'),
            'name' => self::NAME,
            'display_name' => 'Duyệt Danh Mục VPP Chung',
            'description' => 'Duyệt mã VPP đang Chờ duyệt trong Danh mục VPP công ty (nhập lại mật khẩu khi duyệt).',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
