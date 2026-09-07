<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HOẠT CHẤT "MỤC GỘP" - Phụ lục IV NĐ 24/2026/NĐ-CP.
 *
 * Nghị định có những dòng khai theo NHÓM chứ không theo một chất cụ thể:
 *   "Thủy ngân và các hợp chất của thủy ngân" (ngưỡng 1 kg), "Các hợp chất xyanua"
 *   (5.000 kg), "Beryli (dạng bột và các hợp chất)" (100 kg)...
 * Thực tế kho lại nhập chất cụ thể có số CAS riêng (HgCl₂, HgI₂...). Nếu khai chất cụ thể
 * thành một hoạt chất độc lập thì tồn của nó KHÔNG cộng vào ngưỡng của mục gộp -> đối
 * chiếu ngưỡng sai. Ba cột dưới đây nối chất cụ thể vào mục gộp:
 *
 *   parent_id     : hoạt chất này là THÀNH VIÊN của mục gộp nào (null = không thuộc mục gộp).
 *                   Tồn của nó được cộng vào ngưỡng của mục gộp thay vì đứng riêng.
 *   equiv_factor  : hệ số quy khối lượng thành viên -> khối lượng tính vào ngưỡng mục gộp.
 *                   Mặc định 1 = tính TRỌN khối lượng hợp chất (cách hiểu chặt, đang dùng).
 *                   Muốn tính theo kim loại/gốc nguyên tố thì đặt hệ số tương ứng
 *                   (ví dụ HgCl₂ -> 0,738 nếu chỉ tính phần Hg).
 *   is_collective : 1 = chính dòng này là MỤC GỘP của nghị định (đích để chọn ở ô
 *                   "Thuộc mục gộp"), không phải một chất cụ thể.
 *
 * Thành viên KHÔNG cần khai lại phân loại của mục gộp: App\Support\ChemicalClassification
 * cho thành viên thừa hưởng các nhóm của cha.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('active_ingredients')) {
            return;
        }

        Schema::table('active_ingredients', function (Blueprint $table) {
            if (! Schema::hasColumn('active_ingredients', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
                $table->index('parent_id', 'active_ingredients_parent_id_index');
            }

            if (! Schema::hasColumn('active_ingredients', 'equiv_factor')) {
                $table->decimal('equiv_factor', 9, 6)->default(1)->after('threshold_kg');
            }

            if (! Schema::hasColumn('active_ingredients', 'is_collective')) {
                $table->tinyInteger('is_collective')->default(0)->after('equiv_factor');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('active_ingredients')) {
            return;
        }

        Schema::table('active_ingredients', function (Blueprint $table) {
            if (Schema::hasColumn('active_ingredients', 'parent_id')) {
                $table->dropIndex('active_ingredients_parent_id_index');
                $table->dropColumn('parent_id');
            }

            foreach (['equiv_factor', 'is_collective'] as $column) {
                if (Schema::hasColumn('active_ingredients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
