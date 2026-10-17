<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TIÊU CHÍ "THEO CẤP PHÁT BỞI HÀNH CHÁNH" ĐỔI SANG CÓ / KHÔNG
 *
 * Lựa chọn cũ "Hành Chánh / Khác" đổi thành "Có / Không" cho đồng bộ với các tiêu chí
 * dạng có-không khác (hàng nguy hiểm, cần hiệu chuẩn):
 *   supply : admin -> yes, other -> no
 *
 * Quy đổi cả dữ liệu đã lưu ở danh mục vật tư công ty + lịch sử, và điều kiện phân loại
 * của luồng Trình Ký Đề Nghị CP Vật Tư (material_request_sign_flows.criteria).
 */
return new class extends Migration
{
    /** Bảng => cột JSON chứa phân loại vật tư. */
    private const TARGETS = [
        'material_categories' => 'classification',
        'material_category_histories' => 'classification',
        'material_request_sign_flows' => 'criteria',
    ];

    public function up(): void
    {
        $this->convert(['admin' => 'yes', 'other' => 'no']);
    }

    public function down(): void
    {
        $this->convert(['yes' => 'admin', 'no' => 'other']);
    }

    /** Chỉ đổi giá trị của khoá supply, giữ nguyên thứ tự các khoá còn lại. */
    private function convert(array $map): void
    {
        foreach (self::TARGETS as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->whereNotNull($column)
                ->orderBy('id')
                ->select('id', $column)
                ->chunk(200, function ($rows) use ($table, $column, $map) {
                    foreach ($rows as $row) {
                        $decoded = json_decode((string) $row->{$column}, true);
                        $value = is_array($decoded) ? ($decoded['supply'] ?? null) : null;

                        if (! is_string($value) || ! isset($map[$value])) {
                            continue;
                        }

                        $decoded['supply'] = $map[$value];

                        DB::table($table)->where('id', $row->id)->update([
                            $column => json_encode($decoded, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                });
        }
    }
};
