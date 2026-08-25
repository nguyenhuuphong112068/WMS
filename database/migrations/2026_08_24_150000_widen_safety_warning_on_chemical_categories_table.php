<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cảnh báo an toàn đổi từ MỘT dòng chữ tự do sang chọn NHIỀU mã cảnh báo (kiểu GHS),
 * lưu chuỗi JSON các mã xuống cùng cột, ví dụ ["TOXIC","CORROSIVE"] - giống cách lưu
 * của cột classification. Danh sách mã đầy đủ khai báo tại config/chemical.php.
 *
 * Cột cũ dài tối đa 60 ký tự chỉ đủ một dòng chữ, không đủ cho mảng JSON nhiều mã nên
 * phải nới ra bằng đúng độ dài cột classification (255). Không có bản ghi thật nào
 * đang dùng safety_warning ở thời điểm đổi (tính năng vừa thêm trong ngày), nên không
 * cần chuyển đổi dữ liệu cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `chemical_categories` MODIFY `safety_warning` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `chemical_category_histories` MODIFY `safety_warning` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `chemical_categories` MODIFY `safety_warning` VARCHAR(60) NULL');
        DB::statement('ALTER TABLE `chemical_category_histories` MODIFY `safety_warning` VARCHAR(60) NULL');
    }
};
