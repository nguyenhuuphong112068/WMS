<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_reissue_requests', function (Blueprint $table) {
            // Mỗi lần mở form sinh một token; unique index đảm bảo dù người dùng bấm
            // "Ghi sổ" nhiều lần (mạng chậm) cũng chỉ ghi được đúng một dòng.
            $table->string('submit_token', 64)->nullable()->unique()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_reissue_requests', function (Blueprint $table) {
            $table->dropUnique(['submit_token']);
            $table->dropColumn('submit_token');
        });
    }
};
