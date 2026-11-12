<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG VPP CHUYỂN SANG CƠ CHẾ "SỬ DỤNG - GHI SỔ" NHƯ HOÁ CHẤT (12/11/2026)
 *
 * Bỏ đề nghị cấp phát nội bộ (3 loại) + trình ký + kho cấp phát + cấp phát trễ + hiệu chuẩn trước
 * khi cấp phát: người dùng bấm Sử dụng VPP, chọn lô rồi Lưu là trừ tồn ngay (không có Phiếu Tạm).
 *
 *   - vpp_exports.purpose: mục đích sử dụng ghi trên chính phiếu (trước đây nằm ở dòng đề nghị).
 *   - Quyền: thêm tab "Sử Dụng VPP" (export_vpp_tab_use, cấp cho ai đang xem được tab đề nghị nội bộ),
 *     export_vpp_issue thành quyền ghi phiếu sử dụng (cấp thêm cho ai đang có export_vpp_request), xoá
 *     các quyền chỉ phục vụ luồng đề nghị nội bộ / cấp phát / hiệu chuẩn / DS đề nghị nội bộ theo chu kỳ /
 *     Trình Ký Đề Nghị CP VPP.
 *   - Vai trò "Người Đề nghị VPP" đổi thành "Người Sử Dụng VPP".
 */
return new class extends Migration
{
    private const REMOVED = [
        'export_vpp_approve',
        'export_vpp_calibration_confirm',
        'export_vpp_tab_periodic',
        'export_vpp_tab_risk_assessment',
        'export_vpp_tab_regular',
        'export_vpp_tab_prepare',
        'export_vpp_tab_late',
        'export_vpp_tab_calibration',
        'category_vpp_tab_internal',
        'materData_vpp_view_vppSignFlow',
        'materData_vpp_manage_vppSignFlow',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('vpp_exports', 'purpose')) {
            Schema::table('vpp_exports', function (Blueprint $table) {
                $table->string('purpose', 500)->nullable()->after('amount');
            });
        }

        $now = now();
        $id = fn (string $name) => DB::table('permissions')->where('name', $name)->value('id');

        $view = DB::table('permissions')->where('name', 'export_vpp_view')->first();

        // ---- Tab "Sử Dụng VPP": ai đang xem được một tab đề nghị nội bộ / chờ cấp phát thì xem được tab mới ----
        DB::table('permissions')->updateOrInsert(['name' => 'export_vpp_tab_use'], [
            'permission_group' => $view->permission_group ?? null,
            'parent_id' => $view->id ?? null,
            'display_name' => 'Tab Sử Dụng VPP',
            'description' => "Xem tab Sử Dụng VPP: nơi bấm Sử dụng VPP để ghi phiếu (quét mã hoặc chọn lô từ tồn kho của phòng).\nLưu ý: ghi phiếu cần thêm quyền Sử Dụng VPP.",
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->grantLike($id('export_vpp_tab_use'), array_filter([
            $id('export_vpp_tab_regular'), $id('export_vpp_tab_periodic'),
            $id('export_vpp_tab_risk_assessment'), $id('export_vpp_tab_prepare'),
        ]));

        // ---- Ghi phiếu sử dụng: cấp thêm cho người đang lập đề nghị VPP ----
        $this->grantLike($id('export_vpp_issue'), array_filter([$id('export_vpp_request')]));

        $labels = [
            'export_vpp_view' => ['Xem Sử Dụng VPP', "Thấy mục Đề Nghị - Sử Dụng › VPP trên menu và mở màn hình.\nLưu ý: từng tab bên trong cần thêm quyền xem tab tương ứng."],
            'export_vpp_issue' => ['Sử Dụng VPP', "Ghi phiếu Sử dụng VPP ở tab Sử Dụng VPP (quét mã hoặc chọn lô từ tồn kho): lưu là trừ tồn ngay, không qua đề nghị / ký duyệt, không có Phiếu Tạm.\nLưu ý: số lượng không vượt tồn còn của lô + 5%; lô hết hạn không chọn được."],
            'export_vpp_request' => ['Tạo Đề Nghị VPP Liên Phòng Ban / Đến HC', "Lập, sửa và gửi đề nghị xin VPP từ phòng khác (tab Phòng ban khác) hoặc từ kho VPP Hành Chính (tab VPP đến HC); sửa và gửi lại phiếu đang Lưu tạm hoặc Bị từ chối.\nLưu ý: gồm cả gửi đề nghị từ danh sách VPP cần đề nghị Hành Chính. Phiếu gửi đi qua các bước ký theo Trình Ký Liên Phòng Ban VPP."],
            'export_vpp_issue_lot_adjust' => ['Điều Chỉnh Mã Xuất Nhập Khi Cấp Phát VPP Liên Phòng Ban', 'Khi cấp phát đề nghị VPP liên phòng ban / đến HC, được đổi hoặc thêm mã xuất nhập khác với lô hệ thống đề xuất (FEFO/FIFO).'],
            'export_vpp_issue_amount_adjust' => ['Điều Chỉnh Số Lượng Khi Cấp Phát VPP Liên Phòng Ban', 'Khi cấp phát đề nghị VPP liên phòng ban / đến HC, được sửa số lượng cấp khác số hệ thống đề xuất (cấp một phần / cấp vượt).'],
            'export_vpp_tab_book' => ['Tab Sổ sử dụng VPP', 'Xem tab Sổ sử dụng VPP: nhật ký mọi lần VPP rời kho của phòng (sử dụng, loại bỏ, chuyển cho phòng khác).'],
        ];
        foreach ($labels as $name => [$display, $description]) {
            DB::table('permissions')->where('name', $name)->update([
                'display_name' => $display,
                'description' => $description,
                'updated_at' => $now,
            ]);
        }

        // ---- Bỏ quyền của các chức năng đã gỡ ----
        $removedIds = DB::table('permissions')->whereIn('name', self::REMOVED)->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $removedIds)->delete();
        DB::table('user_permission')->whereIn('permission_id', $removedIds)->delete();
        DB::table('permissions')->whereIn('id', $removedIds)->delete();

        // ---- Vai trò "Người Đề nghị VPP" -> "Người Sử Dụng VPP" ----
        $role = DB::table('roles')->where('name', 'Người Đề nghị VPP')->first();
        if ($role) {
            DB::table('roles')->where('id', $role->id)->update([
                'name' => 'Người Sử Dụng VPP',
                'description' => 'Ghi phiếu sử dụng VPP (trừ tồn ngay) và lập đề nghị VPP liên phòng ban / đến Hành Chính',
                'updated_at' => $now,
            ]);
            foreach (['export_vpp_issue', 'export_vpp_tab_use', 'export_vpp_tab_book'] as $name) {
                $row = ['role_id' => $role->id, 'permission_id' => $id($name)];
                if ($row['permission_id'] && ! DB::table('role_permission')->where($row)->exists()) {
                    DB::table('role_permission')->insert($row);
                }
            }
        }
    }

    /** Cấp $targetId cho mọi nhóm quyền / user (quyền riêng) đang có một trong $sourceIds. */
    private function grantLike(?int $targetId, array $sourceIds): void
    {
        if (! $targetId || ! $sourceIds) {
            return;
        }

        foreach (DB::table('role_permission')->whereIn('permission_id', $sourceIds)->distinct()->pluck('role_id') as $roleId) {
            $row = ['role_id' => $roleId, 'permission_id' => $targetId];
            if (! DB::table('role_permission')->where($row)->exists()) {
                DB::table('role_permission')->insert($row);
            }
        }

        foreach (DB::table('user_permission')->whereIn('permission_id', $sourceIds)->where('is_denied', 0)->distinct()->pluck('user_id') as $userId) {
            if (! DB::table('user_permission')->where('user_id', $userId)->where('permission_id', $targetId)->exists()) {
                DB::table('user_permission')->insert(['user_id' => $userId, 'permission_id' => $targetId, 'is_denied' => 0]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vpp_exports', 'purpose')) {
            Schema::table('vpp_exports', fn (Blueprint $table) => $table->dropColumn('purpose'));
        }

        DB::table('roles')->where('name', 'Người Sử Dụng VPP')->update(['name' => 'Người Đề nghị VPP']);
    }
};
