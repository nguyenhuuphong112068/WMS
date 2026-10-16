<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BỎ BỘ PHẬN MUA HÀNG "IT"
 *
 * Bộ phận mua hàng của danh mục vật tư công ty chỉ còn Cung Ứng / Hành Chánh (xem
 * App\Support\MaterialClassification::PURCHASING_DEPARTMENTS). Dòng đang khai IT chuyển về
 * Cung Ứng - cũng là bộ phận hệ thống mặc định khi không rõ ai mua (MaterialPurchasing::DEFAULT_KEY).
 *
 * Mỗi dòng đổi được ghi một dòng lịch sử "Cập nhật" để còn vết; trạng thái duyệt giữ nguyên
 * vì đây là thay đổi bộ tiêu chí của hệ thống, không phải người dùng sửa nội dung vật tư.
 * Lịch sử cũ giữ nguyên khoá "it", hiển thị nhãn "IT (đã bỏ)".
 */
return new class extends Migration
{
    private const REASON = 'Bỏ bộ phận mua hàng IT - chuyển về Cung Ứng';

    private const ACTOR = 'Hệ thống';

    public function up(): void
    {
        $rows = DB::table('material_categories')->where('purchasing_department', 'it')->orderBy('id')->get();

        foreach ($rows as $row) {
            DB::transaction(function () use ($row) {
                DB::table('material_categories')->where('id', $row->id)->update([
                    'purchasing_department' => 'supply',
                    'updated_by' => self::ACTOR,
                    'updated_at' => now(),
                ]);

                DB::table('material_category_histories')->insert([
                    'material_category_id' => $row->id,
                    'action' => 'Cập nhật',
                    'code' => $row->code,
                    'material_names_id' => $row->material_names_id,
                    'manufacturers_id' => $row->manufacturers_id,
                    'part_number' => $row->part_number ?? null,
                    'technical_specification' => $row->technical_specification,
                    'classification' => $row->classification,
                    'purchasing_department' => 'supply',
                    'lead_time_days' => $row->lead_time_days,
                    'app_status' => $row->app_status,
                    'status_id' => $row->status_id,
                    'change_note' => 'Bộ phận mua hàng: IT -> Cung Ứng',
                    'change_reason' => self::REASON,
                    'created_by' => self::ACTOR,
                    'created_at' => now(),
                ]);
            });
        }
    }

    /** Trả lại IT cho đúng những dòng migration này đã đổi (nhận ra qua dòng lịch sử đã ghi). */
    public function down(): void
    {
        $histories = DB::table('material_category_histories')->where('change_reason', self::REASON)->get();

        foreach ($histories as $history) {
            DB::table('material_categories')
                ->where('id', $history->material_category_id)
                ->where('purchasing_department', 'supply')
                ->update(['purchasing_department' => 'it', 'updated_at' => now()]);
        }

        DB::table('material_category_histories')->where('change_reason', self::REASON)->delete();
    }
};
