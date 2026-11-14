<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KÝ DUYỆT ĐỀ NGHỊ HOÁ CHẤT / CHẤT CHUẨN LIÊN PHÒNG BAN (14/11/2026).
 *
 * Cùng mô hình Vật Tư / VPP (App\Support\TransferSignFlow): bấm Gửi chụp 2 bước ký vào
 * <loại>_transfer_signs (quản lý bộ phận phòng đề nghị -> quản lý bộ phận phòng cấp phát), phiếu
 * 'pending_sign' + current_step; từ chối thì về Lưu tạm kèm sign_rejected_*.
 */
return new class extends Migration
{
    private const KINDS = ['chemical', 'standard'];

    public function up(): void
    {
        foreach (self::KINDS as $kind) {
            $signs = $kind.'_transfer_signs';
            $requests = $kind.'_transfer_requests';

            if (! Schema::hasTable($signs)) {
                Schema::create($signs, function (Blueprint $table) use ($signs) {
                    $table->id();
                    $table->unsignedBigInteger('transfer_request_id');          // -> <loại>_transfer_requests.id
                    $table->unsignedTinyInteger('step_no');
                    $table->string('role_names', 255)->nullable();              // Vai trò được ký (Quản Lý Bộ Phân)
                    $table->unsignedBigInteger('department_id')->nullable();    // Phòng của bước: phòng đề nghị / phòng cấp phát
                    $table->string('status', 20)->default('pending');           // pending | signed | rejected
                    $table->string('signed_by', 255)->nullable();
                    $table->timestamp('signed_at')->nullable();
                    $table->string('note', 500)->nullable();
                    $table->tinyInteger('active')->default(1);                  // Gửi lại: bỏ hiệu lực bộ bước cũ
                    $table->string('created_by')->nullable();
                    $table->string('updated_by')->nullable();
                    $table->timestamps();

                    $table->index(['transfer_request_id', 'step_no'], $signs.'_request_step_index');
                    $table->index('department_id', $signs.'_department_id_index');
                });
            }

            Schema::table($requests, function (Blueprint $table) use ($requests) {
                if (! Schema::hasColumn($requests, 'current_step')) {
                    $table->unsignedTinyInteger('current_step')->nullable()->after('status');
                }
                if (! Schema::hasColumn($requests, 'sign_rejected_by')) {
                    $table->string('sign_rejected_by', 255)->nullable()->after('current_step');
                }
                if (! Schema::hasColumn($requests, 'sign_rejected_at')) {
                    $table->timestamp('sign_rejected_at')->nullable()->after('sign_rejected_by');
                }
                if (! Schema::hasColumn($requests, 'sign_reject_reason')) {
                    $table->string('sign_reject_reason', 500)->nullable()->after('sign_rejected_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::KINDS as $kind) {
            $requests = $kind.'_transfer_requests';

            Schema::table($requests, function (Blueprint $table) use ($requests) {
                foreach (['sign_reject_reason', 'sign_rejected_at', 'sign_rejected_by', 'current_step'] as $column) {
                    if (Schema::hasColumn($requests, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });

            Schema::dropIfExists($kind.'_transfer_signs');
        }
    }
};
