<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ĐƯA "TÊN HOÁ CHẤT" (chem_names) TRỎ VỀ DỮ LIỆU GỐC "TÊN HOẠT CHẤT" (active_ingredients).
 *
 * Trước:  chem_names tự giữ active_ingredient_name (text), cas_no, chemical_formula, doc_no.
 * Sau:    chem_names chỉ còn name + active_ingredients_id -> active_ingredients.id. Tên hoạt
 *         chất, số CAS, công thức hoá học LUÔN lấy từ active_ingredients (join khi hiển thị),
 *         không lưu trùng ở chem_names nữa. Số tài liệu bỏ hẳn khỏi chem_names.
 *
 * Backfill trước khi xoá cột: khớp cas_no rồi tới active_ingredient_name với
 * active_ingredients (chỉ gắn khi trùng DUY NHẤT), để dữ liệu cũ không mất liên kết.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chem_names', 'active_ingredients_id')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->unsignedBigInteger('active_ingredients_id')->nullable()->after('name');
                $table->index('active_ingredients_id', 'chem_names_active_ingredients_id_index');
            });
        }

        if (Schema::hasTable('active_ingredients')) {
            $this->backfillByColumn('cas_no', 'cas_no');
            $this->backfillByColumn('active_ingredient_name', 'name');
        }

        Schema::table('chem_names', function (Blueprint $table) {
            foreach (['cas_no', 'chemical_formula', 'active_ingredient_name', 'doc_no'] as $column) {
                if (Schema::hasColumn('chem_names', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('chem_names', function (Blueprint $table) {
            if (! Schema::hasColumn('chem_names', 'active_ingredient_name')) {
                $table->string('active_ingredient_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('chem_names', 'cas_no')) {
                $table->string('cas_no', 100)->nullable()->after('active_ingredient_name');
            }
            if (! Schema::hasColumn('chem_names', 'doc_no')) {
                $table->string('doc_no', 100)->nullable()->after('cas_no');
            }
            if (! Schema::hasColumn('chem_names', 'chemical_formula')) {
                $table->string('chemical_formula', 255)->nullable()->after('doc_no');
            }
        });

        if (Schema::hasColumn('chem_names', 'active_ingredients_id')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->dropIndex('chem_names_active_ingredients_id_index');
                $table->dropColumn('active_ingredients_id');
            });
        }
    }

    /**
     * Gắn active_ingredients_id cho các dòng chem_names còn trống, bằng cách so
     * $chemColumn (trên chem_names) với $ingredientColumn (trên active_ingredients).
     * Chỉ gắn khi active_ingredients có đúng một dòng khớp (đã chuẩn hoá khoảng trắng,
     * không phân biệt hoa/thường).
     */
    private function backfillByColumn(string $chemColumn, string $ingredientColumn): void
    {
        if (! Schema::hasColumn('chem_names', $chemColumn)) {
            return;
        }

        $ingredients = DB::table('active_ingredients')
            ->select('id', $ingredientColumn . ' as key_value')
            ->whereNotNull($ingredientColumn)
            ->get()
            ->groupBy(fn ($row) => mb_strtolower(trim((string) $row->key_value)));

        DB::table('chem_names')
            ->whereNull('active_ingredients_id')
            ->whereNotNull($chemColumn)
            ->orderBy('id')
            ->each(function ($row) use ($chemColumn, $ingredients) {
                $key = mb_strtolower(trim((string) $row->$chemColumn));
                $matches = $ingredients->get($key);

                if ($matches && $matches->count() === 1) {
                    DB::table('chem_names')
                        ->where('id', $row->id)
                        ->update(['active_ingredients_id' => $matches->first()->id]);
                }
            });
    }
};
