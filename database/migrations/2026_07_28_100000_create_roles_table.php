<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vai trò nền của hệ thống:
     *  - Admin: toàn quyền mọi thao tác.
     *
     * Các vai trò nghiệp vụ WMS khác được thêm trực tiếp qua trang Nhóm Quyền.
     */
    private const ROLES = [
        'Admin',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        foreach (self::ROLES as $name) {
            DB::table('roles')->updateOrInsert(['name' => $name], [
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('name', self::ROLES)->delete();
    }
};
