<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NGƯỜI XÁC NHẬN HIỆU CHUẨN VẬT TƯ DO NGƯỜI ĐỀ NGHỊ CHỌN (App\Support\MaterialCalibration).
 *
 * Nhiều user ở nhiều phòng cùng mang quyền export_material_calibration_confirm nên phiếu đề nghị
 * hiệu chuẩn (HCN) phải ghi rõ gửi cho ai: mỗi dòng = một người được chọn của một phiếu (theo mã
 * phiếu), department_id = phòng mà người đó xác nhận (chọn phòng trước, lọc người theo phòng).
 * Chỉ người được chọn, đang làm việc ở đúng phòng đó, mới ký xác nhận được. Phiếu chưa chọn người
 * (phiếu cũ / HC sau cấp phát chưa gửi) giữ quy tắc cũ: phòng QA cùng công ty.
 */
return new class extends Migration
{
    private const PERMISSION = 'export_material_calibration_confirm';

    public function up(): void
    {
        if (! Schema::hasTable('material_calibration_confirmers')) {
            Schema::create('material_calibration_confirmers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->index();
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedTinyInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });
        }

        DB::table('permissions')->where('name', self::PERMISSION)->update([
            'description' => 'Ký xác nhận Đạt / Không đạt cho từng đơn vị vật tư được đề nghị hiệu chuẩn, ở tab Xác nhận hiệu chuẩn của Sử Dụng Vật Tư (nhập lại mật khẩu). Phần Không đạt của phiếu hiệu chuẩn trước cấp phát tự chuyển sang Cách ly; chỉ phần Đạt mới cấp phát được.'
                ."\nLưu ý: người đề nghị chọn Phòng + Người xác nhận khi gửi phiếu - chỉ người được chọn, đang làm việc ở đúng phòng đó, mới ký được. Phiếu chưa chọn người xác nhận thì phòng QA cùng công ty ký.",
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('material_calibration_confirmers');
    }
};
