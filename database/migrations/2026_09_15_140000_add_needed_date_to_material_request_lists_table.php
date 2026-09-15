<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Ngày mong muốn" (needed_date) của đề nghị cấp phát vật tư - ngoại lệ cho phép người
 * lập tự nhập tay (xem SKILL.md §8), khác ngày lập phiếu do hệ thống tự ghi. Danh sách
 * tự sinh theo chu kỳ (MaterialPeriodicRequest) để trống, người lập tự điền khi mở phiếu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('material_request_lists', 'needed_date')) {
            Schema::table('material_request_lists', function (Blueprint $table) {
                $table->date('needed_date')->nullable()->after('note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('material_request_lists', 'needed_date')) {
            Schema::table('material_request_lists', function (Blueprint $table) {
                $table->dropColumn('needed_date');
            });
        }
    }
};
