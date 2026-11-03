<?php

use App\Support\PermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Loại hàng nhóm quyền phụ trách (material,chemical,standard), chọn khi tạo / sửa nhóm quyền.
 * Dùng để xếp cột trên bảng Phân Quyền › Nhóm Quyền: 1 loại -> cột loại đó, 2-3 loại -> cột Chung.
 * Nhóm quyền đang có được điền theo cách xếp tự động (suy theo quyền đã tick + tên nhóm).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'kinds')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('kinds', 50)->nullable()->after('description');
            });
        }

        foreach (PermissionMatrix::build()['roles'] as $role) {
            DB::table('roles')->where('id', $role->id)->update(['kinds' => implode(',', $role->kinds)]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('kinds');
        });
    }
};
