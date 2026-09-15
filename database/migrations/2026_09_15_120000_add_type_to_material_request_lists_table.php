<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chia đề nghị cấp phát vật tư thành 3 loại, mỗi loại một tab riêng trên màn Sử Dụng Vật Tư:
 *   periodic        : hệ thống tự sinh từ "danh sách vật tư đề nghị theo chu kỳ" của phòng
 *   risk_assessment : theo Đánh Giá Rủi Ro (chưa có nghiệp vụ tạo tự động, để sẵn cột)
 *   regular         : đề nghị tự lập như trước giờ (mặc định)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_request_lists', function (Blueprint $table) {
            $table->string('type', 30)->default('regular')->after('note');
            $table->index('type', 'material_request_lists_type_index');
        });

        // Các đề nghị hệ thống đã tự sinh trước đây (ghi chú có tiền tố cố định) -> đánh dấu lại là periodic
        DB::table('material_request_lists')
            ->where('note', 'LIKE', 'Tạo tự động từ danh sách đề nghị theo chu kỳ%')
            ->update(['type' => 'periodic']);
    }

    public function down(): void
    {
        Schema::table('material_request_lists', function (Blueprint $table) {
            $table->dropIndex('material_request_lists_type_index');
            $table->dropColumn('type');
        });
    }
};
