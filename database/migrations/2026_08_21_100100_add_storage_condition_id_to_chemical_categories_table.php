<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DANH MỤC HOÁ CHẤT - LIÊN KẾT ĐIỀU KIỆN BẢO QUẢN VỀ DỮ LIỆU GỐC
 *
 * Cột storage_condition trước đây nhập tay tự do, nay thay bằng storage_condition_id
 * trỏ tới bảng storage_conditions (Dữ Liệu Gốc), chọn qua danh sách đã khai báo sẵn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_categories', function (Blueprint $table) {
            $table->dropColumn('storage_condition');
            $table->unsignedBigInteger('storage_condition_id')->nullable()->after('shelf_life_months');
            $table->index('storage_condition_id', 'chemical_categories_storage_condition_id_index');
        });

        Schema::table('chemical_category_histories', function (Blueprint $table) {
            $table->dropColumn('storage_condition');
            $table->unsignedBigInteger('storage_condition_id')->nullable()->after('shelf_life_months');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_categories', function (Blueprint $table) {
            $table->dropIndex('chemical_categories_storage_condition_id_index');
            $table->dropColumn('storage_condition_id');
            $table->string('storage_condition', 255)->nullable()->after('shelf_life_months');
        });

        Schema::table('chemical_category_histories', function (Blueprint $table) {
            $table->dropColumn('storage_condition_id');
            $table->string('storage_condition', 255)->nullable()->after('shelf_life_months');
        });
    }
};
