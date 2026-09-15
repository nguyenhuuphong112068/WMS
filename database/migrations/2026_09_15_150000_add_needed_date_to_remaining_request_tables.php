<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Ngày mong muốn" (needed_date) cho 4 loại đề nghị còn lại - cùng ngoại lệ cho phép
 * người lập tự nhập tay như material_request_lists (xem SKILL.md §8):
 *   - material_transfer_requests  : đề nghị chuyển vật tư liên phòng ban
 *   - standard_request_lists      : đề nghị cấp phát chuẩn nội bộ (cho Tổ)
 *   - standard_transfer_requests  : đề nghị cấp phát chuẩn liên phòng ban
 *   - chemical_transfer_requests  : đề nghị chuyển hoá chất liên phòng ban
 */
return new class extends Migration
{
    private array $tables = [
        'material_transfer_requests',
        'standard_request_lists',
        'standard_transfer_requests',
        'chemical_transfer_requests',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'needed_date')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->date('needed_date')->nullable()->after('note');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'needed_date')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('needed_date');
                });
            }
        }
    }
};
