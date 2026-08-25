<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CẢNH BÁO AN TOÀN của hoá chất, in ra dải giữa của nhãn dán lô hàng.
 *
 * Khai một lần ở Danh Mục Hoá Chất (ví dụ "Độc/Toxic", "Ăn mòn/Corrosive") rồi mọi
 * lô nhập của hoá chất đó đều in ra đúng dòng cảnh báo này. Gợi ý sẵn có khai báo
 * tại config/chemical.php, nhưng người dùng vẫn gõ được nội dung khác.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_categories', function (Blueprint $table) {
            $table->string('safety_warning', 60)->nullable()->after('classification');
        });

        Schema::table('chemical_category_histories', function (Blueprint $table) {
            $table->string('safety_warning', 60)->nullable()->after('classification');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_categories', function (Blueprint $table) {
            $table->dropColumn('safety_warning');
        });

        Schema::table('chemical_category_histories', function (Blueprint $table) {
            $table->dropColumn('safety_warning');
        });
    }
};
