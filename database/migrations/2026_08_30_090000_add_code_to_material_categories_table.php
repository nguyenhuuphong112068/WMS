<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Danh mục Vật Tư trước giờ không có mã, chỉ nhận diện bằng tổ hợp Tên vật tư + NSX.
 * Bổ sung cột `code` sinh tự động dạng M00001, M00002... - cùng kiểu với `code` của
 * chemical_categories (H.....) và standard_categories (S.....), để 3 danh mục công ty
 * đồng nhất cách đánh mã.
 *
 * Bản ghi cũ đã có (khai trước khi có cột này) được cấp mã bù theo thứ tự `id` tăng dần.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('id');
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('material_category_id');
        });

        $no = 0;

        foreach (DB::table('material_categories')->orderBy('id')->pluck('id') as $id) {
            $no++;

            DB::table('material_categories')
                ->where('id', $id)
                ->update(['code' => 'M'.str_pad((string) $no, 5, '0', STR_PAD_LEFT)]);
        }

        DB::statement('ALTER TABLE `material_categories` MODIFY `code` VARCHAR(50) NOT NULL');

        Schema::table('material_categories', function (Blueprint $table) {
            $table->unique('code', 'material_categories_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropUnique('material_categories_code_unique');
            $table->dropColumn('code');
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
