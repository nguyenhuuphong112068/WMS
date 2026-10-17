<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CẢNH BÁO AN TOÀN: TÁCH MÃ THEO "SƠ ĐỒ LƯU TRỮ HOÁ CHẤT THEO HÌNH ĐỒ CẢNH BÁO" (GHS)
 *
 * Sơ đồ xét tương kỵ theo nhóm chi tiết hơn bộ mã cũ:
 *   - "Dễ cháy" tách thành lỏng dễ cháy / rắn dễ cháy / gặp nước sinh khí dễ cháy / dễ tự bốc cháy
 *   - "Ăn mòn" tách thành nhóm axit / nhóm bazơ
 *
 * Mã cũ không biết thuộc nhánh nào nên đổi theo nhánh THẬN TRỌNG hơn:
 *   FLAMMABLE -> FLAMMABLE_LIQUID (dung môi dễ cháy - loại phổ biến nhất trong phòng thí nghiệm)
 *   CORROSIVE -> CORROSIVE_ACID   (axit tương kỵ với nhiều nhóm hơn bazơ)
 * Mã danh mục nào thực ra là bazơ / chất rắn thì sửa lại ở màn Danh Mục Hoá Chất.
 *
 * Mỗi dòng bị đổi được chụp một bản vào chemical_category_histories để còn vết thay đổi.
 * Ảnh chụp lịch sử cũ giữ nguyên mã cũ - config('chemical.safety_warnings_legacy') lo phần nhãn.
 */
return new class extends Migration
{
    private array $map = [
        'FLAMMABLE' => 'FLAMMABLE_LIQUID',
        'CORROSIVE' => 'CORROSIVE_ACID',
    ];

    public function up(): void
    {
        $rows = DB::table('chemical_categories')
            ->where(function ($query) {
                foreach (array_keys($this->map) as $code) {
                    $query->orWhere('safety_warning', 'like', '%"'.$code.'"%');
                }
            })
            ->get();

        foreach ($rows as $row) {
            $old = json_decode($row->safety_warning ?? '', true);

            if (! is_array($old)) {
                continue;
            }

            $new = array_values(array_unique(array_map(fn ($code) => $this->map[$code] ?? $code, $old)));

            if ($new === $old) {
                continue;
            }

            $newJson = json_encode($new, JSON_UNESCAPED_UNICODE);

            DB::table('chemical_categories')->where('id', $row->id)->update(['safety_warning' => $newJson]);

            DB::table('chemical_category_histories')->insert([
                'chemical_category_id' => $row->id,
                'action' => 'Cập nhật',
                'code' => $row->code,
                'type' => $row->type,
                'chem_names_id' => $row->chem_names_id,
                'manufacturers_id' => $row->manufacturers_id,
                'density' => $row->density,
                'ai_content_percent' => $row->ai_content_percent,
                'shelf_life_months' => $row->shelf_life_months,
                'storage_condition_id' => $row->storage_condition_id,
                'doc_no' => $row->doc_no,
                'classification' => null,
                'safety_warning' => $newJson,
                'lead_time_days' => $row->lead_time_days,
                'app_status' => $row->app_status,
                'status_id' => $row->status_id,
                'change_note' => 'Cảnh báo an toàn: '.implode(', ', $old).' -> '.implode(', ', $new),
                'change_reason' => 'Hệ thống tách mã cảnh báo theo Sơ đồ lưu trữ hoá chất theo hình đồ cảnh báo (GHS) để xét tương kỵ khi xếp định khu.',
                'created_by' => 'Hệ thống',
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $reverse = array_flip($this->map) + [
            'FLAMMABLE_SOLID' => 'FLAMMABLE',
            'WATER_REACTIVE' => 'FLAMMABLE',
            'PYROPHORIC' => 'FLAMMABLE',
            'CORROSIVE_BASE' => 'CORROSIVE',
        ];

        foreach (DB::table('chemical_categories')->whereNotNull('safety_warning')->get() as $row) {
            $codes = json_decode($row->safety_warning, true);

            if (! is_array($codes)) {
                continue;
            }

            $old = array_values(array_unique(array_map(fn ($code) => $reverse[$code] ?? $code, $codes)));

            if ($old !== $codes) {
                DB::table('chemical_categories')->where('id', $row->id)
                    ->update(['safety_warning' => json_encode($old, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }
};
