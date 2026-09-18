<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NGƯỜI KÝ ĐÍCH DANH CHO TỪNG BƯỚC CỦA QUY TRÌNH TRÌNH KÝ VẬT TƯ
 *
 * Ban đầu mỗi bước chỉ khai VAI TRÒ phê duyệt. Nhưng màn hình Đề Nghị Cấp Phát Vật Tư
 * chỉ ĐỌC LẠI quy trình chứ không cho người lập phiếu chọn lại người ký, nên vai trò
 * không đủ - phải biết đích danh ai ký bước nào mới ghi được material_request_signs và
 * mới gửi được thông báo "tới lượt bạn ký".
 *
 * Vai trò (role_id) vẫn giữ: nó là điều kiện lọc danh sách người được chọn ở bước đó,
 * và là thứ đọc lên cho người xem hiểu bước này do cấp nào duyệt.
 *
 *   role_id   : vai trò phê duyệt bước này
 *   user_id   : người ký đích danh, phải thuộc vai trò trên
 *   user_name : ảnh chụp "Họ Tên (userName)" lúc khai - đổi tên sau này vẫn đối chiếu được
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_request_sign_flow_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('material_request_sign_flow_steps', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('role_id');  // -> user_management.id
            }
            if (! Schema::hasColumn('material_request_sign_flow_steps', 'user_name')) {
                $table->string('user_name', 255)->nullable()->after('user_id');
            }
        });

        Schema::table('material_request_sign_flow_steps', function (Blueprint $table) {
            $table->index('user_id', 'material_request_sign_flow_steps_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('material_request_sign_flow_steps', function (Blueprint $table) {
            $table->dropIndex('material_request_sign_flow_steps_user_id_index');
            $table->dropColumn(['user_id', 'user_name']);
        });
    }
};
