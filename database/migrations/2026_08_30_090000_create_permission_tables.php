<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bộ bảng phân quyền của hệ thống:
     *  - permission_groups : nhóm quyền theo menu leftNAV (Dữ Liệu Gốc, Nhập, Sử Dụng...)
     *  - permissions       : từng quyền chi tiết, thuộc 1 nhóm quyền
     *  - role_permission   : quyền được gán cho nhóm người dùng (roles)
     *  - user_permission   : quyền cấp riêng cho 1 user, ghi đè kết quả từ roles
     */
    public function up(): void
    {
        if (!Schema::hasTable('permission_groups')) {
            Schema::create('permission_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('permission_group'); // -> permission_groups.id
                $table->string('name')->unique();                 // ví dụ: import_material_create
                $table->string('display_name')->nullable();       // tên hiển thị: "Thêm Phiếu Nhập Vật Tư"
                $table->string('description')->nullable();
                $table->timestamps();

                $table->index('permission_group');
            });
        }

        if (!Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');

                $table->primary(['role_id', 'permission_id']); // tránh trùng dữ liệu
            });
        }

        if (!Schema::hasTable('user_permission')) {
            Schema::create('user_permission', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('permission_id');
                $table->boolean('is_denied')->default(false); // true = chặn quyền dù nhóm quyền có

                $table->primary(['user_id', 'permission_id']); // tránh trùng dữ liệu
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('permission_groups');
    }
};
