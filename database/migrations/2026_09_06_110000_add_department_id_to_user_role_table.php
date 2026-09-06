<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phân quyền theo phòng ban: mỗi dòng user_role gắn thêm 1 phòng ban.
 *   - department_id = NULL  -> role áp cho MỌI phòng ban (giữ nguyên hành vi dữ liệu cũ)
 *   - department_id = <id>  -> role chỉ có hiệu lực khi phòng ban đang chọn = <id>
 *
 * Một user có thể có cùng 1 role ở nhiều phòng ban (nhiều dòng), nên bỏ ràng buộc
 * unique(user_id, role_id) nếu trước đây có (bảng gốc chỉ có khoá chính id nên không cần).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_role') && !Schema::hasColumn('user_role', 'department_id')) {
            Schema::table('user_role', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('role_id');
                $table->index(['user_id', 'department_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_role', 'department_id')) {
            Schema::table('user_role', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'department_id']);
                $table->dropColumn('department_id');
            });
        }
    }
};
