<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bỏ "Tổ đề nghị" khỏi phiếu đề nghị cấp phát vật tư.
 *
 * Form tạo / sửa đề nghị không còn hỏi Tổ, mã đề nghị cũng bỏ đoạn 2 số của Tổ, nên
 * material_request_lists.group_id chuyển sang nullable và code mới ghi null. Cột vẫn
 * giữ lại để không mất dữ liệu Tổ của các đề nghị cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('material_request_lists', 'group_id')) {
            return;
        }

        Schema::table('material_request_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('material_request_lists', 'group_id')) {
            return;
        }

        Schema::table('material_request_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable(false)->change();
        });
    }
};
