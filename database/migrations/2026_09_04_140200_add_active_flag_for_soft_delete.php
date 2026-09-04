<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 21 CFR Part 11 §11.10(c) - không xoá cứng dữ liệu nghiệp vụ.
 *
 * Các bảng con của phiếu dự trù / đề nghị cấp phát trước đây bị DELETE khi người
 * dùng sửa lại phiếu nháp. Nay thêm cột active (1 = còn hiệu lực, 0 = đã bỏ) để
 * xoá mềm: thao tác "xoá" chỉ set active = 0 và ghi Audit Trail, dữ liệu vẫn còn.
 * Mọi truy vấn đọc danh sách đều lọc active = 1.
 */
return new class extends Migration
{
    private array $tables = [
        'chemical_estimate_items',
        'material_estimate_items',
        'standard_estimate_items',
        'chemical_estimate_item_amounts',
        'material_estimate_item_amounts',
        'standard_estimate_item_amounts',
        'material_request_items',
        'standard_request_items',
    ];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            if (Schema::hasTable($name) && ! Schema::hasColumn($name, 'active')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->boolean('active')->default(true)->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'active')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->dropColumn('active');
                });
            }
        }
    }
};
