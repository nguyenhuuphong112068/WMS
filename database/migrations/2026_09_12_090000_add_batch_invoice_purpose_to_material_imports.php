<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THÊM THÔNG TIN NHẬP CHO VẬT TƯ: Số lô, Số hoá đơn, Ngày ký hoá đơn, Mục đích sử dụng.
 *
 * Cả 4 trường đều không bắt buộc (nullable), song song với chemical_imports
 * (batch_no/invoice_number/invoice_date) và material_exports (purpose).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_imports', function (Blueprint $table) {
            $table->string('batch_no', 100)->nullable()->after('amount');             // Số lô
            $table->string('invoice_number', 100)->nullable()->after('batch_no');     // Số hoá đơn
            $table->date('invoice_date')->nullable()->after('invoice_number');        // Ngày ký hoá đơn
            $table->string('purpose', 500)->nullable()->after('note');                // Mục đích sử dụng
        });

        Schema::table('material_import_histories', function (Blueprint $table) {
            $table->string('batch_no', 100)->nullable()->after('amount');
            $table->string('invoice_number', 100)->nullable()->after('batch_no');
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->string('purpose', 500)->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('material_imports', function (Blueprint $table) {
            $table->dropColumn(['batch_no', 'invoice_number', 'invoice_date', 'purpose']);
        });

        Schema::table('material_import_histories', function (Blueprint $table) {
            $table->dropColumn(['batch_no', 'invoice_number', 'invoice_date', 'purpose']);
        });
    }
};
