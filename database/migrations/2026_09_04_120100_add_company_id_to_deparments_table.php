<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gắn mỗi phòng ban vào một công ty.
 *
 * Bảng phòng ban trong DB tên là "deparments" (thiếu chữ t, sai chính tả từ đầu dự án) -
 * giữ nguyên. Cột company_id nullable để phiên bản một công ty vẫn chạy khi chưa gán;
 * migration seed đi kèm sẽ điền công ty mặc định cho toàn bộ phòng ban đang có.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deparments') && ! Schema::hasColumn('deparments', 'company_id')) {
            Schema::table('deparments', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deparments') && Schema::hasColumn('deparments', 'company_id')) {
            Schema::table('deparments', function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }
    }
};
