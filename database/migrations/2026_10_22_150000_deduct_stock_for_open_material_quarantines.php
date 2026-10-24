<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CÁCH LY = TRỪ TỒN NGAY
 *
 * Từ nay phiếu cách ly vật tư hỏng trừ tồn ngay lúc cách ly (material_exports type = cancel gắn
 * quarantine_id - MaterialQuarantineController::deductStock), không còn chỉ "giữ chỗ" qua
 * MaterialPicking. Phiếu đang "Cách ly - Chờ quyết định" lập trước đó chưa có dòng xuất: sinh bù
 * dòng xuất theo đúng ngày cách ly để tồn không bị cộng lại sau khi bỏ phần giữ chỗ.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('material_quarantines')
            ->leftJoin('material_imports', 'material_quarantines.import_id', '=', 'material_imports.id')
            ->select('material_quarantines.*', 'material_imports.code as import_code')
            ->where('material_quarantines.status_id', 1)
            ->where('material_quarantines.app_status', 'quarantined')
            ->whereNull('material_quarantines.export_id')
            ->get();

        foreach ($rows as $row) {
            $actor = $row->created_by ?: 'Hệ thống';

            $exportId = DB::table('material_exports')->insertGetId([
                'code' => $row->import_code,
                'import_id' => $row->import_id,
                'department_id' => $row->department_id,
                'quarantine_id' => $row->id,
                'amount' => $row->amount,
                'type' => 'cancel',
                'reason' => mb_substr('Cách ly (phiếu '.$row->code.'): '.$row->reason, 0, 500),
                'used_by' => $actor,
                'status_id' => 1,
                'created_by' => $actor,
                'created_at' => $row->created_at,
                'updated_at' => now(),
            ]);

            DB::table('material_quarantines')->where('id', $row->id)->update(['export_id' => $exportId]);

            DB::table('material_export_histories')->insert([
                'material_export_id' => $exportId,
                'action' => 'Thêm mới',
                'code' => $row->import_code,
                'import_id' => $row->import_id,
                'amount' => $row->amount,
                'type' => 'cancel',
                'reason' => mb_substr('Cách ly (phiếu '.$row->code.'): '.$row->reason, 0, 500),
                'used_by' => $actor,
                'status_id' => 1,
                'change_note' => 'Trừ tồn bù cho phiếu cách ly lập trước khi cách ly trừ tồn',
                'created_by' => 'Hệ thống',
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $exportIds = DB::table('material_quarantines')
            ->where('app_status', 'quarantined')
            ->whereNotNull('export_id')
            ->pluck('export_id');

        DB::table('material_quarantines')->whereIn('export_id', $exportIds)->update(['export_id' => null]);
        DB::table('material_export_histories')->whereIn('material_export_id', $exportIds)->delete();
        DB::table('material_exports')->whereIn('id', $exportIds)->delete();
    }
};
