<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHÂN LOẠI VẬT TƯ THEO BẢNG PHÒNG TỔNG HỢP (13/11/2026)
 *
 * - Danh mục vật tư CÔNG TY còn 4 tiêu chí: Mức độ quan trọng, Hàng hoá nguy hiểm, Cần hiệu chuẩn
 *   (thêm lựa chọn "sau khi cấp phát"), Tiếp xúc (mới) - xem App\Support\MaterialClassification.
 * - "Theo Giá Trị" chuyển xuống danh mục vật tư của PHÒNG (mọi phòng): cột mới
 *   material_department_categories.price_level, điền sẵn theo giá trị cũ ở danh mục công ty rồi gỡ
 *   khoá price khỏi JSON công ty. Điều kiện trình ký {"price":...} giữ nguyên - MaterialSignFlow
 *   đọc giá trị từ dòng của phòng.
 * - Phân loại riêng của phòng (department_classification): QC + AD (Phát Triển Phân Tích) có 3 nhóm
 *   Spare part / Vật tư tiêu hao / Khác; Phân xưởng có Có tiếp xúc / Không tiếp xúc. Phòng nào chưa
 *   có thì thêm (phòng đã có giữ nguyên).
 */
return new class extends Migration
{
    private const QC_GROUPS = [
        'Spare part của thiết bị và dụng cụ thủy tinh',
        'Vật tư tiêu hao dùng để phân tích mẫu',
        'Khác (văn phòng phẩm, dụng cụ vệ sinh...)',
    ];

    private const PX_GROUPS = ['Có tiếp xúc', 'Không tiếp xúc'];

    public function up(): void
    {
        if (! Schema::hasColumn('material_department_categories', 'price_level')) {
            Schema::table('material_department_categories', function (Blueprint $table) {
                $table->string('price_level', 10)->nullable()->after('classification_id');
            });
        }

        // Giá trị cũ ở danh mục công ty -> mọi dòng khai của phòng, rồi gỡ khỏi JSON công ty
        DB::table('material_categories')
            ->where('classification', 'like', '%"price"%')
            ->orderBy('id')
            ->select('id', 'classification')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $decoded = json_decode((string) $row->classification, true);
                    if (! is_array($decoded) || ! array_key_exists('price', $decoded)) {
                        continue;
                    }

                    if (in_array($decoded['price'], ['low', 'high'], true)) {
                        DB::table('material_department_categories')
                            ->where('category_id', $row->id)
                            ->whereNull('price_level')
                            ->update(['price_level' => $decoded['price']]);
                    }

                    unset($decoded['price']);
                    DB::table('material_categories')->where('id', $row->id)->update([
                        'classification' => $decoded ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : null,
                    ]);
                }
            });

        $now = now();
        foreach (DB::table('deparments')->get(['id', 'shortName']) as $dept) {
            $short = mb_strtoupper((string) $dept->shortName);
            $groups = match (true) {
                str_starts_with($short, 'QC') || str_starts_with($short, 'KTCL') || $short === 'PTPT' => self::QC_GROUPS,
                str_starts_with($short, 'PX') => self::PX_GROUPS,
                default => [],
            };

            foreach ($groups as $name) {
                DB::table('department_classification')->insertOrIgnore([
                    'name' => $name,
                    'department_id' => $dept->id,
                    'status_id' => 1,
                    'created_by' => 'Hệ thống',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Trả giá trị về danh mục công ty: lấy giá trị phòng gặp đầu tiên (cao ưu tiên)
        $prices = DB::table('material_department_categories')
            ->whereNotNull('price_level')
            ->orderByRaw("price_level = 'high' desc")
            ->get(['category_id', 'price_level'])
            ->unique('category_id')
            ->pluck('price_level', 'category_id');

        foreach ($prices as $categoryId => $price) {
            $current = DB::table('material_categories')->where('id', $categoryId)->value('classification');
            $decoded = json_decode((string) $current, true);
            $decoded = ['price' => $price] + (is_array($decoded) ? $decoded : []);
            DB::table('material_categories')->where('id', $categoryId)->update([
                'classification' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
            ]);
        }

        if (Schema::hasColumn('material_department_categories', 'price_level')) {
            Schema::table('material_department_categories', function (Blueprint $table) {
                $table->dropColumn('price_level');
            });
        }
    }
};
