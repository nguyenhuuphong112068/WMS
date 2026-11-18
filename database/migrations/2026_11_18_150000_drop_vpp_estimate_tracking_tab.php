<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 18/11/2026 Mua hàng VPP bỏ tab "Theo dõi mua hàng" và bỏ Ngày mong muốn / Ngày hẹn đáp ứng trong
 * phiếu. Xoá quyền estimate_vpp_tab_tracking; quyền Trao đổi (estimate_vpp_chat) chuyển sang làm quyền
 * con của Xem Mua Hàng VPP (nút Trao đổi vẫn ở chi tiết phiếu); sửa mô tả các quyền còn nhắc ngày hẹn.
 * down() tạo lại quyền tab (không gán lại) và trả mô tả cũ.
 */
return new class extends Migration
{
    private const TAB = 'estimate_vpp_tab_tracking';

    private const CHAT = 'estimate_vpp_chat';

    private const VIEW = 'estimate_vpp_view';

    /** name => [mô tả cũ, mô tả mới] */
    private const DESCRIPTIONS = [
        'estimate_vpp_chat' => [
            'Gửi tin nhắn trao đổi giữa phòng đề nghị và bộ phận mua hàng ở cột Trao đổi của từng VPP trong tab Theo dõi mua hàng.',
            'Gửi tin nhắn trao đổi ở nút Trao đổi của từng VPP trong chi tiết phiếu mua hàng VPP đã duyệt.',
        ],
        'estimate_vpp_purchasing' => [
            "Tiếp nhận xử lý mua hàng VPP của phòng Hành Chánh: xem tab Đơn mua hàng đã được phê duyệt, bấm Tiếp nhận phiếu, cập nhật Ngày hẹn đáp ứng cho từng tháng của từng VPP.\nLưu ý: chỉ có tác dụng khi đang chọn phòng Hành Chánh ở thanh trên cùng. Phòng Hành Chánh vừa lập vừa tiếp nhận nên huỷ mặt hàng chỉ cần một lần xác nhận. Phiếu chờ tiếp nhận hiện ở Trang chủ.",
            "Tiếp nhận xử lý mua hàng VPP của phòng Hành Chánh: xem tab Đơn mua hàng đã được phê duyệt, bấm Tiếp nhận phiếu.\nLưu ý: chỉ có tác dụng khi đang chọn phòng Hành Chánh ở thanh trên cùng. Phòng Hành Chánh vừa lập vừa tiếp nhận nên huỷ mặt hàng chỉ cần một lần xác nhận. Phiếu chờ tiếp nhận hiện ở Trang chủ.",
        ],
        'estimate_vpp_tab_approved' => [
            "Xem tab Đơn mua hàng đã được phê duyệt: phiếu mua hàng VPP đã duyệt của phòng Hành Chánh chờ tiếp nhận và hẹn ngày đáp ứng.\nLưu ý: tab chỉ hiện khi đang chọn phòng Hành Chánh và có thêm quyền Tiếp Nhận Xử Lý Mua Hàng VPP.",
            "Xem tab Đơn mua hàng đã được phê duyệt: phiếu mua hàng VPP đã duyệt của phòng Hành Chánh chờ tiếp nhận.\nLưu ý: tab chỉ hiện khi đang chọn phòng Hành Chánh và có thêm quyền Tiếp Nhận Xử Lý Mua Hàng VPP.",
        ],
        'estimate_vpp_tracking' => [
            "Phía phòng đề nghị theo dõi mặt hàng cần mua đã duyệt: đánh dấu Hoàn thành khi đã nhận đủ hàng, đề nghị Huỷ mặt hàng không cần nữa, hoặc hoàn tác.\nLưu ý: huỷ một mặt hàng cần cả bộ phận mua hàng đồng ý.",
            "Ở chi tiết phiếu mua hàng VPP đã duyệt: đánh dấu Hoàn thành khi đã nhận đủ hàng, Huỷ mặt hàng không cần nữa, hoặc hoàn tác.\nLưu ý: phòng Hành Chánh vừa lập vừa tiếp nhận nên huỷ mặt hàng chỉ cần một lần xác nhận.",
        ],
    ];

    private function swapDescriptions(int $from, int $to): void
    {
        foreach (self::DESCRIPTIONS as $name => $texts) {
            DB::table('permissions')->where('name', $name)->where('description', $texts[$from])
                ->update(['description' => $texts[$to]]);
        }
    }

    public function up(): void
    {
        $this->swapDescriptions(0, 1);

        $viewId = DB::table('permissions')->where('name', self::VIEW)->value('id');
        DB::table('permissions')->where('name', self::CHAT)->update(['parent_id' => $viewId]);

        $tabId = DB::table('permissions')->where('name', self::TAB)->value('id');

        if (! $tabId) {
            return;
        }

        foreach (['role_permission', 'user_permission'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('permission_id', $tabId)->delete();
            }
        }

        DB::table('permissions')->where('id', $tabId)->delete();
    }

    public function down(): void
    {
        $this->swapDescriptions(1, 0);

        $parent = DB::table('permissions')->where('name', self::VIEW)->first();

        if (! DB::table('permissions')->where('name', self::TAB)->exists()) {
            DB::table('permissions')->insert([
                'permission_group' => $parent->permission_group ?? null,
                'parent_id' => $parent->id ?? null,
                'name' => self::TAB,
                'display_name' => 'Tab Theo Dõi Mua Hàng',
                'description' => 'Xem tab Theo dõi mua hàng: từng mặt hàng đã duyệt của phòng với ngày mong muốn, ngày hẹn đáp ứng, trạng thái và cột Trao đổi.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tabId = DB::table('permissions')->where('name', self::TAB)->value('id');
        DB::table('permissions')->where('name', self::CHAT)->update(['parent_id' => $tabId]);
    }
};
