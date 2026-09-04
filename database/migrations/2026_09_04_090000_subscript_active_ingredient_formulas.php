<?php

use App\Support\ChemicalFormula;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Chuẩn hoá active_ingredients.chemical_formula sang dạng chỉ số dưới Unicode (H₂SO₄).
 *
 * Bản seed Bảng A NĐ 24/2026 nạp công thức ở dạng ASCII phẳng ("C3H4O"). Module dữ liệu
 * gốc quy ước lưu công thức bằng ký tự Unicode để bảng dữ liệu hiển thị đúng chỉ số mà
 * không cần thẻ HTML. Migration này hạ chỉ số cho toàn bộ dòng đang có.
 *
 * ChemicalFormula::toSubscript() chuẩn hoá đầu vào về ASCII trước khi hạ chỉ số nên
 * chạy lại (hoặc chạy trên dòng đã đúng định dạng) không làm hỏng dữ liệu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('active_ingredients')) {
            return;
        }

        DB::table('active_ingredients')
            ->whereNotNull('chemical_formula')
            ->where('chemical_formula', '<>', '')
            ->orderBy('id')
            ->each(function ($row) {
                $subscripted = ChemicalFormula::toSubscript($row->chemical_formula);

                if ($subscripted !== $row->chemical_formula) {
                    DB::table('active_ingredients')
                        ->where('id', $row->id)
                        ->update(['chemical_formula' => $subscripted]);
                }
            });
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('active_ingredients')) {
            return;
        }

        DB::table('active_ingredients')
            ->whereNotNull('chemical_formula')
            ->where('chemical_formula', '<>', '')
            ->orderBy('id')
            ->each(function ($row) {
                $plain = ChemicalFormula::toPlain($row->chemical_formula);

                if ($plain !== $row->chemical_formula) {
                    DB::table('active_ingredients')
                        ->where('id', $row->id)
                        ->update(['chemical_formula' => $plain]);
                }
            });
    }
};
