<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Đổi 3 cột định danh trên user_management từ chuỗi sang khoá ngoại (id):
 *   deparment (shortName)  -> deparment_id  (FK deparments.id)
 *   userGroup (tên role)   -> role_id       (FK roles.id)   — role chính, đa role vẫn ở user_role
 *   groupName (bỏ hẳn)     -> group_id       (FK groups.id) — tổ chính, đa tổ vẫn ở user_group
 *
 * group_id đã được tạo sẵn ở migration 2026_08_28_154640, chỉ backfill lại giá trị chính.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_management', function (Blueprint $table) {
            if (!Schema::hasColumn('user_management', 'deparment_id')) {
                $table->unsignedBigInteger('deparment_id')->nullable()->after('fullName');
            }
            if (!Schema::hasColumn('user_management', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('deparment_id');
            }
            if (!Schema::hasColumn('user_management', 'group_id')) {
                $table->unsignedBigInteger('group_id')->nullable()->after('role_id');
            }
        });

        // ---- Backfill deparment_id từ deparment (shortName) ----
        if (Schema::hasColumn('user_management', 'deparment')) {
            DB::table('user_management')->whereNotNull('deparment')->orderBy('id')
                ->select('id', 'deparment')->get()
                ->each(function ($user) {
                    $deptId = DB::table('deparments')->where('shortName', $user->deparment)->value('id');
                    if ($deptId) {
                        DB::table('user_management')->where('id', $user->id)->update(['deparment_id' => $deptId]);
                    }
                });
        }

        // ---- Backfill role_id từ userGroup (tên role) ----
        if (Schema::hasColumn('user_management', 'userGroup')) {
            DB::table('user_management')->whereNotNull('userGroup')->orderBy('id')
                ->select('id', 'userGroup')->get()
                ->each(function ($user) {
                    $roleId = DB::table('roles')->where('name', $user->userGroup)->value('id');
                    if ($roleId) {
                        DB::table('user_management')->where('id', $user->id)->update(['role_id' => $roleId]);
                    }
                });
        }

        // ---- Backfill group_id (tổ chính) từ bảng trung gian user_group ----
        DB::table('user_management')->whereNull('group_id')->orderBy('id')
            ->pluck('id')
            ->each(function ($userId) {
                $groupId = DB::table('user_group')->where('user_id', $userId)->orderBy('id')->value('group_id');
                if ($groupId) {
                    DB::table('user_management')->where('id', $userId)->update(['group_id' => $groupId]);
                }
            });

        // ---- Bỏ các cột chuỗi cũ ----
        Schema::table('user_management', function (Blueprint $table) {
            foreach (['deparment', 'userGroup', 'groupName'] as $col) {
                if (Schema::hasColumn('user_management', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_management', function (Blueprint $table) {
            if (!Schema::hasColumn('user_management', 'deparment')) {
                $table->string('deparment')->nullable()->after('fullName');
            }
            if (!Schema::hasColumn('user_management', 'userGroup')) {
                $table->string('userGroup')->nullable()->after('deparment');
            }
            if (!Schema::hasColumn('user_management', 'groupName')) {
                $table->string('groupName')->nullable()->after('userGroup');
            }
        });

        DB::table('user_management')->orderBy('id')
            ->select('id', 'deparment_id', 'role_id')->get()
            ->each(function ($user) {
                DB::table('user_management')->where('id', $user->id)->update([
                    'deparment' => $user->deparment_id
                        ? DB::table('deparments')->where('id', $user->deparment_id)->value('shortName')
                        : null,
                    'userGroup' => $user->role_id
                        ? DB::table('roles')->where('id', $user->role_id)->value('name')
                        : null,
                ]);
            });

        Schema::table('user_management', function (Blueprint $table) {
            $table->dropColumn(['deparment_id', 'role_id']);
            // group_id giữ nguyên vì được tạo ở migration trước.
        });
    }
};
