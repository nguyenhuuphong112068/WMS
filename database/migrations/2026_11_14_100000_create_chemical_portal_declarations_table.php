<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Khai báo Cổng thông tin quốc gia của Danh Mục Hoá Chất Công Ty - KHAI BÁO NHIỀU LẦN.
 *
 * Trước đây mỗi mã danh mục chỉ xác nhận được một lần (cờ national_portal_declared + mã hồ sơ
 * trên chemical_categories). Từ 14/11/2026 mỗi lần khai báo là một dòng ở bảng này, gồm
 * Mã hồ sơ khai báo + Code nhà sản xuất (mã hàng / catalogue number của NSX). Từng dòng đã
 * ghi thì không sửa / xoá được; các cột trên chemical_categories giữ giá trị của LẦN KHAI
 * BÁO MỚI NHẤT (cờ = đã có ít nhất một lần khai báo).
 *
 * Các mã đã xác nhận trước đó được chép sang làm lần khai báo đầu tiên (Code NSX để trống).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chemical_portal_declarations')) {
            Schema::create('chemical_portal_declarations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chemical_category_id')->index();
                $table->string('declaration_no', 100)->nullable();
                $table->string('manufacturer_code', 100)->nullable();
                $table->unsignedBigInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });

            if (Schema::hasColumn('chemical_categories', 'national_portal_declaration_no')) {
                $rows = DB::table('chemical_categories')
                    ->where('national_portal_declared', 1)
                    ->select('id', 'national_portal_declaration_no', 'national_portal_declared_by', 'national_portal_declared_at', 'updated_at')
                    ->get();

                foreach ($rows as $row) {
                    $at = $row->national_portal_declared_at ?: ($row->updated_at ?: now());

                    DB::table('chemical_portal_declarations')->insert([
                        'chemical_category_id' => $row->id,
                        // Các mã xác nhận trước 05/11/2026 chưa có mã hồ sơ
                        'declaration_no' => $row->national_portal_declaration_no,
                        'manufacturer_code' => null,
                        'status_id' => 1,
                        'created_by' => $row->national_portal_declared_by,
                        'created_at' => $at,
                        'updated_at' => $at,
                    ]);
                }
            }
        }

        foreach (['chemical_categories', 'chemical_category_histories'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'national_portal_manufacturer_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('national_portal_manufacturer_code', 100)->nullable()->after('national_portal_declaration_no');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['chemical_category_histories', 'chemical_categories'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'national_portal_manufacturer_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('national_portal_manufacturer_code');
                });
            }
        }

        Schema::dropIfExists('chemical_portal_declarations');
    }
};
