<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bỏ giao diện Dữ Liệu Gốc "Trạng Thái" và "Nhóm Nguy Hại Bảng B": xoá quyền con của 2 màn.
 * Bảng statuses / mixture_hazard_categories GIỮ NGUYÊN - thêm / sửa trực tiếp trên DB.
 */
return new class extends Migration
{
    private const NAMES = [
        'materData_common_view_status',
        'materData_common_manage_status',
        'materData_chemical_view_mixtureHazardCategory',
        'materData_chemical_manage_mixtureHazardCategory',
        'materData_chemical_review_mixtureHazardCategory',
    ];

    public function up(): void
    {
        $ids = DB::table('permissions')->whereIn('name', self::NAMES)->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        // Không khôi phục: giao diện 2 màn đã bỏ.
    }
};
