<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BỎ BƯỚC PHÂN LOẠI THỦ CÔNG Ở "DANH MỤC HOÁ CHẤT".
 *
 * Trước: chemical_categories.classification lưu JSON các mã (N1..N10 / PL1..PL4 / CAM)
 *        do người dùng tự tick khi khai danh mục.
 * Sau:   phân loại 10 nhóm theo Nghị định 24/2026/NĐ-CP được SUY tự động từ dữ liệu gốc
 *        "Tên Hoạt Chất" + "Tên Hoá Chất" (App\Support\ChemicalClassification). Cột này
 *        bị bỏ hẳn.
 *
 * chemical_category_histories.classification GIỮ LẠI để không mất ảnh chụp lịch sử cũ,
 * chỉ ngừng ghi giá trị mới (ChemicalCategoryController::writeHistory() không ghi nữa).
 *
 * down(): thêm lại cột (rỗng - dữ liệu cũ không khôi phục được).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chemical_categories') && Schema::hasColumn('chemical_categories', 'classification')) {
            Schema::table('chemical_categories', function (Blueprint $table) {
                $table->dropColumn('classification');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chemical_categories') && ! Schema::hasColumn('chemical_categories', 'classification')) {
            Schema::table('chemical_categories', function (Blueprint $table) {
                $table->text('classification')->nullable()->after('doc_no');
            });

            // Khôi phục thô từ ảnh chụp lịch sử mới nhất của từng mã (nếu còn).
            if (Schema::hasTable('chemical_category_histories')) {
                DB::table('chemical_categories')->orderBy('id')->pluck('id')->each(function ($id) {
                    $last = DB::table('chemical_category_histories')
                        ->where('chemical_category_id', $id)
                        ->whereNotNull('classification')
                        ->orderByDesc('id')
                        ->value('classification');

                    if ($last !== null) {
                        DB::table('chemical_categories')->where('id', $id)->update(['classification' => $last]);
                    }
                });
            }
        }
    }
};
