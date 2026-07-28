<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sổ XIN CẤP LẠI HỒ SƠ (BMR/BPR)
        Schema::create('document_reissue_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable()->unique();
            $table->foreignId('department_id')->nullable()->constrained('deparments')->nullOnDelete();

            // Nội dung người xin tự trình bày, ghi vào sổ
            $table->date('request_date');                       // Ngày
            $table->string('product_name', 255);                // Tên sản phẩm BMR/BPR
            $table->string('process_no', 100)->nullable();      // Số quy trình
            $table->string('edition', 50)->nullable();          // Ấn bản
            $table->string('pages', 255);                       // Số trang cần xin lại
            $table->text('reason');                             // Lý do xin lại
            $table->text('capa')->nullable();                   // CAPA

            // pending_pm | pending_qa | pending_issue | completed | rejected | cancelled
            $table->string('status', 20)->default('pending_pm');

            // Ngày / Người đề nghị
            $table->unsignedBigInteger('requester_user_id')->nullable();
            $table->string('requester_name', 150)->nullable();
            $table->date('requested_date')->nullable();

            // Ngày / QĐ - P.QĐ PXSX ký duyệt
            $table->unsignedBigInteger('pm_user_id')->nullable();
            $table->string('pm_name', 150)->nullable();
            $table->date('pm_signed_date')->nullable();
            $table->text('pm_note')->nullable();

            // Ý kiến TP / PP. ĐBCL: agree | disagree
            $table->unsignedBigInteger('qa_user_id')->nullable();
            $table->string('qa_name', 150)->nullable();
            $table->date('qa_signed_date')->nullable();
            $table->string('qa_decision', 20)->nullable();
            $table->text('qa_opinion')->nullable();

            // Ngày / Người cấp hồ sơ ký tên (bộ phận ban hành)
            $table->unsignedBigInteger('issuer_user_id')->nullable();
            $table->string('issuer_name', 150)->nullable();
            $table->date('issued_date')->nullable();
            $table->string('issued_pages', 255)->nullable();    // số trang thực tế đã cấp lại
            $table->boolean('wrong_pages_voided')->default(false); // đã gạch bỏ trang sai
            $table->text('issue_note')->nullable();

            $table->text('note')->nullable();
            $table->string('created_by', 150)->nullable();
            $table->string('updated_by', 150)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('request_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_reissue_requests');
    }
};
