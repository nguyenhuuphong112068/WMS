<?php

use App\Support\MaterDataPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền con theo từng màn hình Dữ Liệu Gốc (App\Support\MaterDataPermissions), dưới quyền nhóm
 * materData_<phạm vi>_<thao tác>. Nhóm quyền / user đang có quyền nhóm được cấp sẵn mọi quyền con nên không ai mất quyền.
 */
return new class extends Migration
{
    public function up(): void
    {
        MaterDataPermissions::seed();
    }

    public function down(): void
    {
        $names = [];
        foreach (MaterDataPermissions::SCREENS as $screen => [, , $actions]) {
            foreach ($actions as $action) {
                $names[] = MaterDataPermissions::name($screen, $action);
            }
        }

        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
