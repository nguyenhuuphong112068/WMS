<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Từ nay CẤP PHÁT vật tư là trừ tồn ngay: mỗi dòng đề nghị được cấp phát có sẵn một phiếu
 * sử dụng (material_exports, type = export) gắn với nó.
 *
 * Dữ liệu cũ có những dòng đã cấp phát theo luật trước - Tổ chưa lập phiếu sử dụng nên kho
 * chưa bị trừ. Bù cho chúng một phiếu sử dụng để tồn kho và màn hình khớp luật mới; dòng
 * nào đã có phiếu rồi thì bỏ qua (chạy lại không nhân đôi).
 */
return new class extends Migration
{
    public function up(): void
    {
        $items = DB::table('material_request_items')
            ->join('material_request_lists', 'material_request_items.request_list_id', '=', 'material_request_lists.id')
            ->leftJoin('material_imports', 'material_request_items.import_id', '=', 'material_imports.id')
            ->select(
                'material_request_items.*',
                'material_request_lists.code as request_code',
                'material_request_lists.group_id',
                'material_request_lists.department_id',
                'material_imports.code as import_code'
            )
            ->where('material_request_items.status', 'issued')
            ->whereNotNull('material_request_items.import_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('material_exports')
                    ->whereColumn('material_exports.request_item_id', 'material_request_items.id')
                    ->where('material_exports.type', 'export');
            })
            ->get();

        foreach ($items as $item) {
            $exportId = DB::table('material_exports')->insertGetId([
                'code' => $item->import_code,
                'import_id' => $item->import_id,
                'department_id' => $item->department_id,
                'group_id' => $item->group_id,
                'request_item_id' => $item->id,
                'amount' => $item->issued_amount,
                'type' => 'export',
                'product_name' => $item->product_name,
                'used_by' => $item->issued_by,
                'status_id' => 1,
                'created_by' => $item->issued_by,
                'created_at' => $item->issued_at ?: now(),
                'updated_at' => now(),
            ]);

            DB::table('material_export_histories')->insert([
                'material_export_id' => $exportId,
                'action' => 'Cấp phát',
                'code' => $item->import_code,
                'import_id' => $item->import_id,
                'amount' => $item->issued_amount,
                'type' => 'export',
                'product_name' => $item->product_name,
                'used_by' => $item->issued_by,
                'status_id' => 1,
                'change_note' => 'Bù phiếu cấp phát cho đề nghị '.$item->request_code.' khi chuyển sang luật trừ tồn ngay lúc cấp phát',
                'created_by' => 'Hệ thống',
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Không gỡ: phiếu bù đã là dữ liệu tồn kho thật, xoá đi sẽ sai tồn.
    }
};
