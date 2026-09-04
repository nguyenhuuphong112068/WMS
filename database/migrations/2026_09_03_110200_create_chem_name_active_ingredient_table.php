<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TÊN HOÁ CHẤT ↔ TÊN HOẠT CHẤT: đổi từ khoá ngoại đơn sang NHIỀU - NHIỀU.
 *
 * Một tên hoá chất (chem_names) thường là hỗn hợp nhiều chất. Để xét Bảng B của
 * Phụ lục IV NĐ 24/2026/NĐ-CP (điều kiện tiên quyết: hỗn hợp chứa ít nhất một hoạt
 * chất thuộc Bảng A) thì chem_names phải gắn được nhiều hoạt chất.
 *
 * up():   tạo bảng pivot, chuyển dữ liệu từ chem_names.active_ingredients_id sang,
 *         rồi bỏ cột khoá ngoại đơn.
 * down(): trả lại cột đơn = hoạt chất có id nhỏ nhất của mỗi tên hoá chất, xoá pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chem_name_active_ingredient')) {
            Schema::create('chem_name_active_ingredient', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chem_names_id');
                $table->unsignedBigInteger('active_ingredients_id');
                $table->timestamps();

                $table->unique(['chem_names_id', 'active_ingredients_id'], 'cnai_pair_unique');
                $table->index('chem_names_id', 'cnai_chem_names_id_index');
                $table->index('active_ingredients_id', 'cnai_active_ingredients_id_index');
            });
        }

        if (Schema::hasColumn('chem_names', 'active_ingredients_id')) {
            DB::table('chem_names')
                ->whereNotNull('active_ingredients_id')
                ->orderBy('id')
                ->each(function ($row) {
                    DB::table('chem_name_active_ingredient')->updateOrInsert(
                        ['chem_names_id' => $row->id, 'active_ingredients_id' => $row->active_ingredients_id],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                });

            Schema::table('chem_names', function (Blueprint $table) {
                $table->dropIndex('chem_names_active_ingredients_id_index');
                $table->dropColumn('active_ingredients_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('chem_names', 'active_ingredients_id')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->unsignedBigInteger('active_ingredients_id')->nullable()->after('name');
                $table->index('active_ingredients_id', 'chem_names_active_ingredients_id_index');
            });
        }

        if (Schema::hasTable('chem_name_active_ingredient')) {
            // Chuỗi trong DB::raw là hằng, không ghép từ dữ liệu người dùng.
            DB::table('chem_name_active_ingredient')
                ->select('chem_names_id', DB::raw('MIN(active_ingredients_id) as ai_id'))
                ->groupBy('chem_names_id')
                ->get()
                ->each(function ($row) {
                    DB::table('chem_names')
                        ->where('id', $row->chem_names_id)
                        ->update(['active_ingredients_id' => $row->ai_id]);
                });

            Schema::dropIfExists('chem_name_active_ingredient');
        }
    }
};
