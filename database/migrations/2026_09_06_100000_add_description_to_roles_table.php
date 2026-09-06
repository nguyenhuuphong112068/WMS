<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột diễn giải cho nhóm quyền, phục vụ nút "Quản Lý Nhóm Quyền"
 * trên màn NHÓM QUYỀN (tạo / sửa role ngay trên giao diện).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles') && !Schema::hasColumn('roles', 'description')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('description')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'description')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
