<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tổ quản lý của danh sách đề nghị nội bộ theo chu kỳ -> groups.id; đề nghị tự tạo mang tổ này. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('material_periodic_request_list', 'group_id')) {
            Schema::table('material_periodic_request_list', function (Blueprint $table) {
                $table->unsignedBigInteger('group_id')->nullable()->after('department_id');
                $table->index('group_id', 'material_periodic_request_list_group_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('material_periodic_request_list', 'group_id')) {
            Schema::table('material_periodic_request_list', function (Blueprint $table) {
                $table->dropIndex('material_periodic_request_list_group_id_index');
                $table->dropColumn('group_id');
            });
        }
    }
};
