<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm tiêu đề ngắn cho đề nghị chuyển liên phòng ban (hoá chất/chất chuẩn/vật tư), vì mã
 * đề nghị vừa được rút gọn (bỏ shortName 2 phòng ban) nên không còn tự mô tả nội dung.
 */
return new class extends Migration
{
    private array $tables = [
        'chemical_transfer_requests',
        'standard_transfer_requests',
        'material_transfer_requests',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'title')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('title', 255)->nullable()->after('code');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'title')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('title');
                });
            }
        }
    }
};
