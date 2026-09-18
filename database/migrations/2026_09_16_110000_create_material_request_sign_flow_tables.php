<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - TRÌNH KÝ ĐỀ NGHỊ CẤP PHÁT VẬT TƯ
 *
 * Trước đây người lập phiếu đề nghị cấp phát vật tư tự khai số bước ký và chọn đích danh
 * người ký từng bước (material_request_signs). Cách đó linh hoạt nhưng mỗi người khai một
 * kiểu, cùng một loại vật tư mà phiếu này ký 1 bước phiếu kia ký 3 bước.
 *
 * Hai bảng dưới đây cho phòng ban khai TRƯỚC quy trình ký chuẩn: ứng với một TỔ HỢP
 * điều kiện của vật tư thì phải đi qua những bước ký nào, mỗi bước do VAI TRÒ nào duyệt.
 *
 *   material_request_sign_flows       : đầu quy trình - điều kiện áp dụng
 *   material_request_sign_flow_steps  : các bước ký của quy trình đó, theo step_no
 *
 * ĐIỀU KIỆN ÁP DỤNG gồm hai vế, khai vế nào thì vế đó phải khớp, bỏ trống là "Tất cả":
 *
 *   - criteria         : phân loại của DANH MỤC VẬT TƯ CHUNG (material_categories.classification)
 *                        lưu JSON {"price":"high","dangerous":"yes"} - chỉ chứa tiêu chí
 *                        được chọn, tiêu chí không khai thì không có mặt trong JSON.
 *   - classification_id: phân loại của DANH MỤC VẬT TƯ PHÒNG
 *                        (material_department_categories.classification_id -> department_classification.id)
 *
 * Một phòng không được khai hai quy trình còn hiệu lực có cùng tổ hợp điều kiện - nếu
 * không, một vật tư sẽ khớp hai quy trình khác nhau và hệ thống không biết chọn cái nào.
 * Xem App\Support\MaterialSignFlow::signature().
 *
 * Không có bước duyệt (app_status) - dữ liệu cấp phòng, giống department_classification.
 * Khoá (status_id = 0) thay cho xoá cứng; sửa quy trình thì bỏ hiệu lực bước cũ
 * (active = 0) rồi ghi bước mới, không xoá cứng để còn đối chiếu lịch sử.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_request_sign_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);                                  // Tên quy trình do phòng tự đặt
            $table->unsignedBigInteger('department_id');                  // -> deparments.id
            $table->json('criteria')->nullable();                         // Phân loại danh mục chung, NULL = mọi vật tư
            $table->unsignedBigInteger('classification_id')->nullable();  // -> department_classification.id, NULL = mọi phân loại
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Trong một phòng không được trùng tên quy trình
            $table->unique(['department_id', 'name'], 'material_request_sign_flows_dept_name_unique');
            $table->index('department_id', 'material_request_sign_flows_department_id_index');
            $table->index('classification_id', 'material_request_sign_flows_classification_id_index');
        });

        Schema::create('material_request_sign_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id');       // -> material_request_sign_flows.id
            $table->unsignedInteger('step_no');          // Thứ tự ký: 1, 2, 3...
            $table->unsignedBigInteger('role_id');       // -> roles.id, vai trò thực hiện phê duyệt bước này
            $table->tinyInteger('active')->default(1);   // Sửa quy trình: bỏ hiệu lực bước cũ, không xoá cứng
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index(['flow_id', 'step_no'], 'material_request_sign_flow_steps_parent_index');
            $table->index('role_id', 'material_request_sign_flow_steps_role_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_sign_flow_steps');
        Schema::dropIfExists('material_request_sign_flows');
    }
};
