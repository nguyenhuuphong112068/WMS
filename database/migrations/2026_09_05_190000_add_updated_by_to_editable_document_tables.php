<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Các bảng đầu phiếu dưới đây được sửa nội dung / đổi trạng thái nhiều lần sau khi tạo
 * nhưng thiếu cột updated_by nên không ghi được ai là người sửa cuối cùng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('standard_transfer_requests') && ! Schema::hasColumn('standard_transfer_requests', 'updated_by')) {
            Schema::table('standard_transfer_requests', function (Blueprint $table) {
                $table->string('updated_by')->nullable()->after('created_by');
            });
        }

        if (Schema::hasTable('standard_request_lists') && ! Schema::hasColumn('standard_request_lists', 'updated_by')) {
            Schema::table('standard_request_lists', function (Blueprint $table) {
                $table->string('updated_by')->nullable()->after('created_by');
            });
        }

        if (Schema::hasTable('standard_stability_assessment_list')) {
            Schema::table('standard_stability_assessment_list', function (Blueprint $table) {
                if (! Schema::hasColumn('standard_stability_assessment_list', 'updated_by')) {
                    $table->string('updated_by')->nullable()->after('created_by');
                }
                if (! Schema::hasColumn('standard_stability_assessment_list', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('updated_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('standard_transfer_requests') && Schema::hasColumn('standard_transfer_requests', 'updated_by')) {
            Schema::table('standard_transfer_requests', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }

        if (Schema::hasTable('standard_request_lists') && Schema::hasColumn('standard_request_lists', 'updated_by')) {
            Schema::table('standard_request_lists', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }

        if (Schema::hasTable('standard_stability_assessment_list')) {
            Schema::table('standard_stability_assessment_list', function (Blueprint $table) {
                if (Schema::hasColumn('standard_stability_assessment_list', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
                if (Schema::hasColumn('standard_stability_assessment_list', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
            });
        }
    }
};
