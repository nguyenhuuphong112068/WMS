<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỔI TÊN 3 BẢNG DANH MỤC THEO PHÒNG BAN
 *
 *      department_chemicals  ->  chemical_department_categories
 *      department_materials  ->  material_department_categories
 *      department_standards  ->  standard_department_categories
 *
 * Ba bảng này giữ CÁCH DÙNG của từng phòng ban đối với một dòng danh mục chung
 * (chemical_categories / material_categories / standard_categories). Tên cũ đọc như
 * "hoá chất của phòng ban", dễ nhầm là một bảng danh mục thứ hai; tên mới đặt theo
 * đúng nội dung là danh mục theo phòng ban của từng loại hàng, và đứng ngay cạnh
 * bảng danh mục chung tương ứng khi liệt kê bảng trong CSDL.
 *
 * Chỉ đổi tên bảng và tên index - cột, dữ liệu và quan hệ giữ nguyên.
 */
return new class extends Migration
{
    /**
     * Tên cũ => tên mới. Index của bảng cũng đổi tiền tố theo đúng cặp này.
     */
    private const TABLES = [
        'department_chemicals' => 'chemical_department_categories',
        'department_materials' => 'material_department_categories',
        'department_standards' => 'standard_department_categories',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $from => $to) {
            $this->rename($from, $to);
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES, true) as $from => $to) {
            $this->rename($to, $from);
        }
    }

    /**
     * Đổi tên bảng, rồi đổi luôn tiền tố của các index còn mang tên bảng cũ để tên
     * index không lạc so với tên bảng.
     *
     * Hai bước tách rời nhau và đều tự kiểm tra trạng thái hiện tại, nên chạy lại
     * trên CSDL đã đổi tên bảng dở dang vẫn ra đúng kết quả.
     */
    private function rename(string $from, string $to): void
    {
        if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
            Schema::rename($from, $to);
        }

        $this->renameIndexes($from, $to);
    }

    /**
     * MariaDB 10.4 chưa có ALTER TABLE ... RENAME INDEX, nên đổi tên index bằng cách
     * xoá rồi tạo lại y nguyên cột và tính duy nhất của nó.
     */
    private function renameIndexes(string $from, string $to): void
    {
        if (! Schema::hasTable($to)) {
            return;
        }

        foreach (Schema::getIndexes($to) as $index) {
            $name = (string) $index['name'];

            if (! str_starts_with($name, $from.'_')) {
                continue;
            }

            $newName = $to.substr($name, strlen($from));
            $columns = $index['columns'];
            $unique = (bool) $index['unique'];

            Schema::table($to, function (Blueprint $table) use ($name, $newName, $columns, $unique) {
                $table->dropIndex($name);

                if ($unique) {
                    $table->unique($columns, $newName);
                } else {
                    $table->index($columns, $newName);
                }
            });
        }
    }
};
