<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - ĐỀ NGHỊ CHUYỂN HOÁ CHẤT
 *
 * Nguồn thông tin TRƯỚC khi chuyển: phòng đang thiếu hoá chất gửi đề nghị sang phòng
 * ban đang có, phòng kia xem rồi đồng ý (lập phiếu chuyển) hoặc từ chối kèm lý do.
 *
 * Đề nghị KHÔNG động vào tồn kho. Tồn chỉ đổi khi phiếu chuyển (exports.type =
 * 'transfer') được lập và phòng nhận bấm Nhận.
 *
 * department_id    : phòng ĐỀ NGHỊ - phòng đang cần hoá chất.
 * to_department_id : phòng ĐƯỢC ĐỀ NGHỊ - phòng đang giữ hoá chất, sẽ là phòng gửi.
 * category_id      : hoá chất cần, theo danh mục chứ không theo mã lô cụ thể -
 *                    chọn lô nào là quyền của phòng giữ hàng.
 * amount           : số lượng đề nghị, theo đơn vị gốc của danh mục.
 * app_status       : pending = chờ trả lời, accepted = đã đồng ý, rejected = từ chối.
 * export_id        : -> exports.id của phiếu chuyển được lập từ đề nghị này, để đối
 *                    chiếu đề nghị với phiếu chuyển thật.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chemical_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');            // Phòng đề nghị (cần hàng)
            $table->unsignedBigInteger('to_department_id');         // Phòng được đề nghị (có hàng)
            $table->unsignedBigInteger('category_id');              // -> chemical_categories.id
            $table->decimal('amount', 15, 4);                       // Số lượng đề nghị
            $table->date('needed_date')->nullable();                // Ngày cần dùng
            $table->string('reason', 500)->nullable();              // Lý do / mục đích cần
            $table->string('app_status', 20)->default('pending');   // pending | accepted | rejected
            $table->string('response_note', 500)->nullable();       // Trả lời của phòng được đề nghị
            $table->string('responded_by', 255)->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->unsignedBigInteger('export_id')->nullable();    // -> exports.id phiếu chuyển đã lập
            $table->string('requested_by', 255)->nullable();
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'chemical_transfer_requests_department_id_index');
            $table->index('to_department_id', 'chemical_transfer_requests_to_department_id_index');
            $table->index('export_id', 'chemical_transfer_requests_export_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemical_transfer_requests');
    }
};
