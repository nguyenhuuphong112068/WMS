<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 18/11/2026 đổi thuật ngữ "dự trù" thành "mua hàng" trên toàn hệ thống. Code chỉ đổi chữ hiển thị
 * (route / bảng / quyền vẫn là estimate_*); ở CSDL đổi các bảng cấu hình hiển thị:
 * tên + diễn giải quyền, tên nhóm quyền ("Dự Trù" -> "Mua Hàng", ma trận quyền gom card theo tên
 * này - xem PermissionMatrix 'groups'), diễn giải vai trò.
 * Không đụng dữ liệu nghiệp vụ / lịch sử (audit trail, ghi chú thay đổi, tên nhóm phân loại phòng tự đặt).
 * down() chỉ trả lại tên nhóm quyền (đổi ngược toàn bộ chữ "mua hàng" sẽ sai chỗ vốn đã là "mua hàng").
 */
return new class extends Migration
{
    private const MAP = [
        'dự trù mua hàng' => 'mua hàng', 'Dự trù mua hàng' => 'Mua hàng', 'Dự Trù Mua Hàng' => 'Mua Hàng',
        'DỰ TRÙ MUA HÀNG' => 'MUA HÀNG',
        'mặt hàng dự trù' => 'mặt hàng cần mua', 'Mặt hàng dự trù' => 'Mặt hàng cần mua',
        'Mặt Hàng Dự Trù' => 'Mặt Hàng Cần Mua', 'lượng nên dự trù' => 'lượng nên mua',
        'dự trù' => 'mua hàng', 'Dự trù' => 'Mua hàng', 'Dự Trù' => 'Mua Hàng', 'DỰ TRÙ' => 'MUA HÀNG',
        'dự Trù' => 'mua Hàng',
    ];

    /** bảng => cột cần đổi */
    private const COLUMNS = [
        'permissions' => ['display_name', 'description'],
        'permission_groups' => ['name'],
        'roles' => ['description'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach (DB::table($table)->get(array_merge(['id'], $columns)) as $row) {
                $changes = [];
                foreach ($columns as $column) {
                    $value = $row->{$column};
                    if ($value !== null && $value !== '' && ($new = strtr($value, self::MAP)) !== $value) {
                        $changes[$column] = $new;
                    }
                }
                if ($changes) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('permission_groups')->where('name', 'Mua Hàng')->update(['name' => 'Dự Trù']);
    }
};
