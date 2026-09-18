<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QUY ĐỔI PHÂN LOẠI VẬT TƯ VỀ BỘ TIÊU CHÍ MỚI (Phòng Tổng Hợp chốt)
 *
 * Theo giá trị và Theo mức độ quan trọng thu từ 3 mức về 2 mức Thấp / Cao:
 *   price      : medium -> high          (low, high giữ nguyên)
 *   importance : a -> high, b -> high, c -> low
 *
 * Dòng đang bỏ trống tiêu chí nào thì để nguyên, không tự điền - người khai sẽ bị bắt
 * chọn đủ 6 tiêu chí ở lần sửa kế tiếp.
 *
 * Ghi lại JSON theo đúng thứ tự khoá của App\Support\MaterialClassification::CRITERIA
 * (price, importance, dangerous, supply, qa_calibration). Nếu không, so sánh chuỗi cũ /
 * mới lúc lưu sẽ báo "đã thay đổi" trong khi nội dung y hệt.
 */
return new class extends Migration
{
    /** Bảng có cột classification cần quy đổi. */
    private const TABLES = ['material_categories', 'material_category_histories'];

    /** Thứ tự khoá khi ghi lại JSON. */
    private const ORDER = ['price', 'importance', 'dangerous', 'supply', 'qa_calibration'];

    public function up(): void
    {
        $this->convert([
            'price' => ['medium' => 'high'],
            'importance' => ['a' => 'high', 'b' => 'high', 'c' => 'low'],
        ]);
    }

    /**
     * Quy đổi ngược chỉ khôi phục được mức tương đương: bộ cũ có 3 mức, bộ mới có 2 nên
     * "medium" và "B - Quan trọng" không lấy lại được. Chấp nhận đưa về mức gần nhất.
     */
    public function down(): void
    {
        $this->convert([
            'importance' => ['high' => 'a', 'low' => 'c'],
        ]);
    }

    /** Đọc từng dòng, đổi giá trị theo bảng tra rồi ghi lại JSON đúng thứ tự khoá. */
    private function convert(array $maps): void
    {
        foreach (self::TABLES as $table) {
            DB::table($table)
                ->whereNotNull('classification')
                ->orderBy('id')
                ->select('id', 'classification')
                ->chunk(200, function ($rows) use ($table, $maps) {
                    foreach ($rows as $row) {
                        $decoded = json_decode((string) $row->classification, true);

                        if (! is_array($decoded)) {
                            continue;
                        }

                        $result = [];

                        foreach (self::ORDER as $key) {
                            $value = $decoded[$key] ?? null;

                            if (! is_string($value) || $value === '') {
                                continue;
                            }

                            $result[$key] = $maps[$key][$value] ?? $value;
                        }

                        DB::table($table)->where('id', $row->id)->update([
                            'classification' => $result ? json_encode($result, JSON_UNESCAPED_UNICODE) : null,
                        ]);
                    }
                });
        }
    }
};
