<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - ĐỐI TƯỢNG
 *
 * Đối tượng tiêu thụ vật tư (không nhất thiết là thiết bị) - dùng khảo sát lượng vật tư tiêu thụ.
 * Mỗi dòng là một (đối tượng, tần suất): cùng một mã có nhiều tần suất thì là nhiều dòng riêng.
 *
 * - source    : nguồn dữ liệu - cal = đồng bộ từ phần mềm CAL, manual = người dùng tự thêm
 * - type      : loại đối tượng, khai báo ở ConsumptionObjectController::TYPES
 *               (production_equipment = Thiết bị sản xuất, utility_equipment = Thiết bị tiện ích, testing_equipment = Thiết bị kiểm nghiệm...)
 * - code      : Mã đối tượng
 * - name      : Tên đối tượng
 * - location  : Vị trí
 * - frequency : Tần suất - một mã gốc CAL (Monthly, Quaterly...) = Schedule_Master_x.Sch_Type
 *
 * Liên kết WMS <-> CAL (chỉ có giá trị khi source = cal):
 * - cal_connection   : kết nối CAL - cal1 (khối B1) / cal2 (khối B2)
 * - cal_table_suffix : x trong Inst_Master_x / Schedule_Master_x
 * - cal_record_id    : Inst_Master_x.ID (IDENTITY) của thiết bị lớn
 * - cal_inst_id      : Inst_Master_x.Inst_id của thiết bị lớn = Parent_Equip_id của thiết bị con
 *                      = Schedule_Master_x.Inst_ID
 * - cal_synced_at    : lần đồng bộ gần nhất còn thấy (thiết bị, tần suất) này trên CAL
 * Lịch Pending của một dòng: Schedule_Master_x có Inst_ID thuộc thiết bị lớn/con và Sch_Type = frequency.
 *
 * Cột chuẩn theo quy tắc dự án: id, status_id, created_by, updated_by, timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consumption_objects')) {
            Schema::create('consumption_objects', function (Blueprint $table) {
                $table->id();
                $table->string('source', 10)->default('manual');
                $table->string('type', 30);
                $table->string('code', 50);
                $table->string('name');
                $table->string('location')->nullable();
                $table->string('frequency', 30);

                $table->string('cal_connection', 10)->nullable();
                $table->unsignedTinyInteger('cal_table_suffix')->nullable();
                $table->unsignedBigInteger('cal_record_id')->nullable();
                $table->string('cal_inst_id', 50)->nullable();
                $table->dateTime('cal_synced_at')->nullable();

                $table->tinyInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();

                // Một mã trong một loại có thể có nhiều tần suất - mỗi tần suất một dòng
                $table->unique(['type', 'code', 'frequency']);
                // Một (thiết bị lớn CAL, tần suất) chỉ gắn với một dòng (MySQL cho phép nhiều NULL - dòng manual)
                $table->unique(['cal_connection', 'cal_table_suffix', 'cal_record_id', 'frequency'], 'consumption_objects_cal_link_unique');
                $table->index(['cal_connection', 'cal_table_suffix', 'cal_inst_id'], 'consumption_objects_cal_inst_index');
                $table->index('source');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consumption_objects');
    }
};
