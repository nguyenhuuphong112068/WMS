<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bỏ tab "Bộ phận mua hàng" ở 3 màn Dự Trù: nút Đồng ý huỷ / Không đồng ý của bộ phận mua hàng
 * chuyển vào từng mục trên trang chi tiết phiếu (mở từ tab "Đơn dự trù đã được phê duyệt"),
 * nên xoá các quyền xem tab này. Quyền estimate_<loại>_purchasing (Tiếp Nhận Xử Lý) giữ nguyên.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'estimate_material_tab_purchasing',
        'estimate_chemical_tab_purchasing',
        'estimate_standard_tab_purchasing',
    ];

    public function up(): void
    {
        $ids = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        $now = now();
        $labels = ['material' => 'Vật Tư', 'chemical' => 'Hoá Chất', 'standard' => 'Chất Chuẩn'];

        foreach ($labels as $kind => $label) {
            $parent = DB::table('permissions')->where('name', "estimate_{$kind}_view")->first();

            if (! $parent) {
                continue;
            }

            $id = DB::table('permissions')->insertGetId([
                'permission_group' => $parent->permission_group,
                'parent_id' => $parent->id,
                'name' => "estimate_{$kind}_tab_purchasing",
                'display_name' => 'Tab Bộ phận mua hàng',
                'description' => "Dự Trù {$label} - tab \"Bộ phận mua hàng\"",
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (DB::table('role_permission')->where('permission_id', $parent->id)->pluck('role_id') as $roleId) {
                DB::table('role_permission')->insert(['role_id' => $roleId, 'permission_id' => $id]);
            }
        }
    }
};
