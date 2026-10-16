<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BỎ TIÊU CHÍ PHÂN LOẠI "THEO CẤP PHÁT BỞI HÀNH CHÁNH" (khoá supply)
 *
 * Vật tư Hành Chánh cấp phát chính là vật tư có Bộ Phận Mua Hàng = Hành Chánh
 * (material_categories.purchasing_department = admin), nên tiêu chí riêng này bỏ hẳn -
 * xem App\Support\MaterialClassification::isAdminPurchased().
 *
 * Gỡ khoá supply khỏi JSON phân loại đang dùng của danh mục và điều kiện trình ký (nếu có),
 * giữ nguyên thứ tự các khoá còn lại để so sánh cũ / mới lúc lưu không báo thay đổi giả.
 * Bảng lịch sử material_category_histories GIỮ NGUYÊN để còn đối chiếu - describe() vẫn đọc
 * ra nhãn "Theo Cấp Phát Bởi Hành Chánh (đã bỏ)".
 *
 * down(): không khôi phục được giá trị cũ từng dòng (đã bỏ), suy lại theo bộ phận mua hàng:
 * Hành Chánh -> yes, còn lại -> no.
 */
return new class extends Migration
{
    /** Bảng => cột JSON chứa phân loại vật tư. */
    private const TARGETS = [
        'material_categories' => 'classification',
        'material_request_sign_flows' => 'criteria',
    ];

    public function up(): void
    {
        foreach (self::TARGETS as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->whereNotNull($column)
                ->where($column, 'like', '%"supply"%')
                ->orderBy('id')
                ->select('id', $column)
                ->chunk(200, function ($rows) use ($table, $column) {
                    foreach ($rows as $row) {
                        $decoded = json_decode((string) $row->{$column}, true);

                        if (! is_array($decoded) || ! array_key_exists('supply', $decoded)) {
                            continue;
                        }

                        unset($decoded['supply']);

                        DB::table($table)->where('id', $row->id)->update([
                            $column => $decoded ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : null,
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        DB::table('material_categories')
            ->whereNotNull('classification')
            ->orderBy('id')
            ->select('id', 'classification', 'purchasing_department')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $decoded = json_decode((string) $row->classification, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    // Đặt lại supply trước qa_calibration như thứ tự cũ của CRITERIA
                    $result = [];
                    foreach ($decoded as $key => $value) {
                        if ($key === 'qa_calibration') {
                            $result['supply'] = $row->purchasing_department === 'admin' ? 'yes' : 'no';
                        }
                        $result[$key] = $value;
                    }
                    $result['supply'] ??= $row->purchasing_department === 'admin' ? 'yes' : 'no';

                    DB::table('material_categories')->where('id', $row->id)->update([
                        'classification' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });
    }
};
