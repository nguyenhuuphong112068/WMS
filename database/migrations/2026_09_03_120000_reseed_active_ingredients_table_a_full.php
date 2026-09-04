<?php

use Illuminate\Database\Migrations\Migration;

/**
 * NẠP LẠI TOÀN BỘ BẢNG A Phụ lục IV NĐ 24/2026/NĐ-CP (271 hoạt chất).
 * ---------------------------------------------------------------------------
 * Bản seed đầu (2026_09_03_100000_seed_active_ingredients_table_a) đã chạy khi mới
 * có ~26 chất. File này chạy lại đúng logic up() của bản seed đó sau khi bản seed
 * được cập nhật đủ 271 dòng của Bảng A - updateOrInsert theo 'name' nên không tạo
 * bản trùng, đồng thời dọn các dòng luật định cũ không còn trong danh mục chuẩn.
 *
 * Muốn nạp lại lần nữa (khi Bảng A thay đổi): sửa mảng ROWS trong file seed gốc rồi
 * "php artisan migrate:rollback --step=1" + "php artisan migrate" file này.
 */
return new class extends Migration
{
    private function seeder(): object
    {
        return require __DIR__ . '/2026_09_03_100000_seed_active_ingredients_table_a.php';
    }

    public function up(): void
    {
        $this->seeder()->up();
    }

    public function down(): void
    {
        $this->seeder()->down();
    }
};
