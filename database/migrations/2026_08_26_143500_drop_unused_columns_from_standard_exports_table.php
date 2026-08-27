<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('standard_exports')) {
            Schema::table('standard_exports', function (Blueprint $table) {
                $columns = [
                    'exported_date',
                    'exported_by',
                    'purpose',
                    'test_criteria',
                    'test_report_no',
                    'analyst_id',
                    'checked_by',
                    'status_id',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('standard_exports', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('standard_export_histories')) {
            Schema::table('standard_export_histories', function (Blueprint $table) {
                $columns = [
                    'exported_date',
                    'exported_by',
                    'purpose',
                    'test_criteria',
                    'test_report_no',
                    'analyst_id',
                    'checked_by',
                    'status_id',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('standard_export_histories', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('standard_exports')) {
            Schema::table('standard_exports', function (Blueprint $table) {
                $table->date('exported_date')->nullable();
                $table->string('exported_by', 255)->nullable();
                $table->string('purpose', 500)->nullable();
                $table->string('test_criteria', 255)->nullable();
                $table->string('test_report_no', 100)->nullable();
                $table->unsignedBigInteger('analyst_id')->nullable();
                $table->string('checked_by', 255)->nullable();
                $table->tinyInteger('status_id')->default(1);
            });
        }

        if (Schema::hasTable('standard_export_histories')) {
            Schema::table('standard_export_histories', function (Blueprint $table) {
                $table->date('exported_date')->nullable();
                $table->string('exported_by', 255)->nullable();
                $table->string('purpose', 500)->nullable();
                $table->string('test_criteria', 255)->nullable();
                $table->string('test_report_no', 100)->nullable();
                $table->string('checked_by', 255)->nullable();
                $table->tinyInteger('status_id')->default(1);
            });
        }
    }
};
