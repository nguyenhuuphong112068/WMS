<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vai trò dùng cho phân quyền sổ xin cấp lại hồ sơ (Document Reissue):
     *  - Admin:                    toàn quyền mọi thao tác.
     *  - Production_Manager:       ghi sổ đề nghị (Bước 1) và ký duyệt đề nghị (Bước 2 - QĐ/P.QĐ PXSX).
     *  - QA_Manager:                cho phép cấp lại hồ sơ (Bước 3 - TP/PP. ĐBCL) và cấp lại hồ sơ (Bước 4).
     *  - Người đề nghị:             chỉ được ghi sổ đề nghị (Bước 1).
     *  - Người cho lại hồ sơ:       chỉ được cấp lại hồ sơ (Bước 4).
     */
    private const ROLES = [
        'Admin',
        'Production_Manager',
        'QA_Manager',
        'Người đề nghị',
        'Người cho lại hồ sơ',
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
