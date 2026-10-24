<?php

use App\Support\MaterialCalibration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seri No từng đơn vị do NGƯỜI ĐỀ NGHỊ hiệu chuẩn nhập lúc gửi (MaterialCalibrationController::store),
 * QA chỉ chọn Đạt / Không đạt, không sửa Seri No. Bản ghi đơn vị sinh ngay lúc đề nghị nên `result`
 * để trống cho tới khi QA xác nhận.
 *
 * Phiếu đang chờ QA lập trước thay đổi này chưa có bản ghi đơn vị: sinh bù (không Seri No) để QA vẫn
 * xác nhận từng đơn vị được.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_calibration_units', function (Blueprint $table) {
            $table->string('result', 20)->nullable()->change();
        });

        $pending = DB::table('material_calibrations')
            ->where('status_id', 1)
            ->where('app_status', 'pending')
            ->whereNotIn('id', DB::table('material_calibration_units')->select('calibration_id'))
            ->get();

        foreach ($pending as $row) {
            $count = MaterialCalibration::unitCount((float) $row->amount);

            for ($i = 1; $i <= max($count, 1); $i++) {
                DB::table('material_calibration_units')->insert([
                    'calibration_id' => $row->id,
                    'code' => $row->code,
                    'import_id' => $row->import_id,
                    'unit_no' => $count ? $i : 0,
                    'amount' => $count ? 1 : $row->amount,
                    'serial_no' => null,
                    'result' => null,
                    'status_id' => 1,
                    'created_by' => $row->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('material_calibration_units')->whereNull('result')->delete();

        Schema::table('material_calibration_units', function (Blueprint $table) {
            $table->string('result', 20)->nullable(false)->change();
        });
    }
};
