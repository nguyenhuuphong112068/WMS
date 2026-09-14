<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DANH MỤC - VẬT TƯ | DANH SÁCH VẬT TƯ ĐỀ NGHỊ THEO CHU KỲ
 *
 * Danh sách đề nghị cấp phát lập sẵn một lần. Cứ đến chu kỳ, hệ thống tự tạo một đề nghị
 * ở trạng thái Lưu tạm từ danh sách này (App\Support\MaterialPeriodicRequest), người đề
 * nghị chỉ việc điều chỉnh rồi trình ký / gửi đi thay vì nhập lại toàn bộ.
 *
 *   type = internal : sinh material_request_lists + material_request_items (đề nghị nội bộ)
 *   type = external : sinh material_transfer_requests + material_transfer_items
 *                     (đề nghị liên phòng ban, gửi tới to_department_id)
 *
 * Lịch: periodic (week | month | quarter | days) + cycle_day (ngày thứ mấy trong chu kỳ),
 * riêng days có thêm cycle_length (số ngày của một chu kỳ). start_date là ngày bắt đầu chu
 * kỳ đầu tiên - không tạo đề nghị trước ngày này, và là mốc đếm của chu kỳ days.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [2, 'category_material_periodic_manage', 'Quản Lý Đề Nghị Vật Tư Theo Chu Kỳ', 'Thêm / sửa / khoá danh sách vật tư đề nghị theo chu kỳ và tạo đề nghị ngay từ danh sách'],
    ];

    private const GRANT_TO_ROLES = ['Admin'];

    public function up(): void
    {
        if (! Schema::hasTable('periodic_request_list')) {
            Schema::create('periodic_request_list', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->string('periodic', 20)->default('month');           // Chu kỳ: week | month | quarter | days
                $table->unsignedSmallInteger('cycle_length')->nullable();   // Số ngày của một chu kỳ, chỉ periodic = days
                $table->unsignedSmallInteger('cycle_day')->default(1);      // Ngày thứ mấy trong chu kỳ (tuần 1-7 = Thứ 2-CN, tháng 1-31, quý 1-92, days 1-cycle_length)
                $table->date('start_date');                                 // Ngày bắt đầu chu kỳ đầu tiên
                $table->enum('type', ['internal', 'external'])->default('internal');
                $table->unsignedBigInteger('department_id');                // -> deparments.id (phòng đề nghị)
                $table->unsignedBigInteger('to_department_id')->nullable(); // -> deparments.id (phòng cấp phát, chỉ external)
                $table->date('next_run_date')->nullable();                  // Ngày sẽ tạo đề nghị kế tiếp (hệ thống tính)
                $table->timestamp('last_generated_at')->nullable();
                $table->string('last_request_code', 50)->nullable();        // Mã đề nghị tạo gần nhất
                $table->unsignedInteger('generated_count')->default(0);     // Số lần đã tạo đề nghị từ danh sách
                $table->tinyInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->unsignedBigInteger('created_user_id')->nullable();  // Người nhận thông báo khi đề nghị được tạo
                $table->string('updated_by')->nullable();
                $table->timestamps();

                $table->index(['department_id', 'type'], 'periodic_request_list_department_type_index');
                $table->index(['status_id', 'next_run_date'], 'periodic_request_list_due_index');
            });
        }

        if (! Schema::hasTable('periodic_request_item')) {
            Schema::create('periodic_request_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('periodic_request_list_id');     // -> periodic_request_list.id
                $table->unsignedBigInteger('category_id');                  // -> material_categories.id
                $table->string('purpose', 500)->nullable();
                $table->decimal('requested_amount', 15, 4)->default(0);
                $table->string('requested_unit', 50)->nullable();
                $table->tinyInteger('active')->default(1);                  // Sửa danh sách: bỏ hiệu lực dòng cũ, không xoá cứng
                $table->string('created_by')->nullable();
                $table->timestamps();

                $table->index('periodic_request_list_id', 'periodic_request_item_parent_index');
                $table->index('category_id', 'periodic_request_item_category_id_index');
            });
        }

        // Lịch sử: mỗi lần Thêm / Sửa / Khoá / Mở khoá / Tạo đề nghị chụp lại toàn bộ danh sách
        if (! Schema::hasTable('periodic_request_list_histories')) {
            Schema::create('periodic_request_list_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('periodic_request_list_id');     // -> periodic_request_list.id
                $table->string('action', 50);
                $table->text('change_note')->nullable();
                $table->string('change_reason', 500)->nullable();
                $table->longText('snapshot')->nullable();                   // JSON {nhãn: giá trị} ngay sau lần thay đổi
                $table->string('created_by')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('periodic_request_list_id', 'periodic_request_list_histories_parent_index');
            });
        }

        $now = now();

        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission[1]], [
                'permission_group' => $permission[0],
                'display_name' => $permission[2],
                'description' => $permission[3],
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('name', array_column(self::PERMISSIONS, 1))->pluck('id');
        $roleIds = DB::table('roles')->whereIn('name', self::GRANT_TO_ROLES)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', array_column(self::PERMISSIONS, 1))->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('periodic_request_list_histories');
        Schema::dropIfExists('periodic_request_item');
        Schema::dropIfExists('periodic_request_list');
    }
};
