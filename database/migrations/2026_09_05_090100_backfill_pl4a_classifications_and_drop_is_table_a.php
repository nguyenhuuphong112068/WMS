<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GỘP "Thuộc Bảng A" (active_ingredients.is_table_a) VÀO PHÂN LOẠI MỚI.
 *
 * Trước: cột is_table_a = 1 nghĩa là hoạt chất thuộc Bảng A Phụ lục IV (nhóm 9 hình 1).
 * Sau:   thông tin đó nằm ở bảng con active_ingredient_classifications dưới dạng dòng
 *        (appendix = 'IV', group_no = null, table_ref = 'A'). Cột is_table_a bị bỏ hẳn;
 *        mọi nơi cần "hoạt chất PL IV bảng A" kiểm tra qua bảng con.
 *
 * threshold_kg vẫn nằm ở active_ingredients (mỗi hoạt chất chỉ có một ngưỡng PL IV bảng A).
 *
 * up():   với mỗi dòng is_table_a = 1 -> tạo dòng phân loại IV/-/A (idempotent), rồi drop cột.
 * down(): thêm lại cột is_table_a, set = 1 cho hoạt chất có dòng phân loại IV/-/A.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('active_ingredients') || ! Schema::hasTable('active_ingredient_classifications')) {
            return;
        }

        if (Schema::hasColumn('active_ingredients', 'is_table_a')) {
            $now = now();

            DB::table('active_ingredients')
                ->where('is_table_a', 1)
                ->orderBy('id')
                ->pluck('id')
                ->each(function ($aiId) use ($now) {
                    DB::table('active_ingredient_classifications')->updateOrInsert(
                        [
                            'active_ingredients_id' => $aiId,
                            'appendix' => 'IV',
                            'group_no' => null,
                            'table_ref' => 'A',
                        ],
                        [
                            'note' => 'Nghị định 24/2026/NĐ-CP - Phụ lục IV - Bảng A',
                            'is_statutory' => 1,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                });

            Schema::table('active_ingredients', function (Blueprint $table) {
                $table->dropColumn('is_table_a');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('active_ingredients')) {
            return;
        }

        if (! Schema::hasColumn('active_ingredients', 'is_table_a')) {
            Schema::table('active_ingredients', function (Blueprint $table) {
                $table->tinyInteger('is_table_a')->default(0)->after('chemical_formula');
            });
        }

        if (Schema::hasTable('active_ingredient_classifications')) {
            $ids = DB::table('active_ingredient_classifications')
                ->where('appendix', 'IV')
                ->where('table_ref', 'A')
                ->distinct()
                ->pluck('active_ingredients_id')
                ->all();

            if ($ids) {
                DB::table('active_ingredients')->whereIn('id', $ids)->update(['is_table_a' => 1]);
            }
        }
    }
};
