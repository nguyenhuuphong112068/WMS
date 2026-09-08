<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỰ TRÙ - CÁC BƯỚC KÝ CỦA MỘT PHIẾU (quy trình ký duyệt động)
 *
 * Song song với material_request_signs của màn Sử Dụng Vật Tư. Người lập phiếu tự khai
 * SỐ BƯỚC KÝ và NGƯỜI KÝ đích danh từng bước; mỗi bước một dòng, thứ tự theo step_no.
 * Tối thiểu 2 bước, bước cuối bắt buộc là Ban Giám Đốc.
 *
 * Mỗi loại phiếu dự trù có bộ bảng riêng nên bảng bước ký cũng tách theo tiền tố
 * chemical_ / standard_ / material_.
 *
 * - user_id / user_name : người ký đích danh (phiếu khai mới)
 * - role_names          : fallback vai trò, chỉ dùng cho phiếu chuyển từ luồng 2 bước cũ
 * - status              : pending | signed | rejected
 * - active              : sửa phiếu = khai lại quy trình, các bước cũ bị bỏ hiệu lực (0)
 */
return new class extends Migration
{
    private const TYPES = ['chemical', 'standard', 'material'];

    public function up(): void
    {
        foreach (self::TYPES as $type) {
            $tableName = $type.'_estimate_signs';

            Schema::create($tableName, function (Blueprint $table) use ($type, $tableName) {
                $table->id();
                $table->unsignedBigInteger($type.'_estimate_id');
                $table->unsignedTinyInteger('step_no');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name')->nullable();
                $table->string('role_names')->nullable();
                $table->string('status', 20)->default('pending');
                $table->string('signed_by')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->string('reject_reason', 500)->nullable();
                $table->tinyInteger('active')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();

                $table->index($type.'_estimate_id', $tableName.'_parent_index');
                $table->index(['status', 'active'], $tableName.'_status_index');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TYPES as $type) {
            Schema::dropIfExists($type.'_estimate_signs');
        }
    }
};
