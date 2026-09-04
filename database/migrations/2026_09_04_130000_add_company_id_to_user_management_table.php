<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gắn mỗi người dùng vào một công ty.
 *
 * Cột company_id nullable để phiên bản một công ty vẫn chạy khi chưa gán.
 * Backfill: user_management.deparment lưu shortName của phòng ban, nên suy company_id
 * qua deparments.shortName -> deparments.company_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_management') && ! Schema::hasColumn('user_management', 'company_id')) {
            Schema::table('user_management', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }

        if (Schema::hasColumn('user_management', 'company_id') && Schema::hasColumn('deparments', 'company_id')) {
            DB::table('user_management')
                ->whereNull('company_id')
                ->whereNotNull('deparment')
                ->orderBy('id')
                ->each(function ($user) {
                    $companyId = DB::table('deparments')
                        ->where('shortName', $user->deparment)
                        ->value('company_id');

                    if ($companyId) {
                        DB::table('user_management')
                            ->where('id', $user->id)
                            ->update(['company_id' => $companyId]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_management') && Schema::hasColumn('user_management', 'company_id')) {
            Schema::table('user_management', function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }
    }
};
