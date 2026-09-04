<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SEED NHÓM NGUY HẠI BẢNG B - Phụ lục IV Nghị định 24/2026/NĐ-CP.
 *
 * 21 nhóm phân loại theo GHS chia làm 4 phần:
 *   I   - Nguy hại sức khỏe        (3 nhóm)
 *   II  - Nguy hại vật chất        (14 nhóm)
 *   III - Nguy hại cho môi trường  (2 nhóm)
 *   IV  - Nguy hại khác            (2 nhóm)
 *
 * updateOrInsert khoá theo (hazard_group, ordinal) nên chạy lại nhiều lần không nhân đôi.
 * Mã B00001.. chỉ sinh khi thêm mới; xoá chỉ đụng dòng luật định do hệ thống seed.
 */
return new class extends Migration
{
    /** [hazard_group, ordinal, name, threshold_kg, threshold_basis|null] */
    private const ROWS = [
        // I - Nguy hại sức khỏe
        ['I', 1, 'Độc cấp tính cấp 1, tất cả các đường phơi nhiễm', 5000, null],
        ['I', 2, "Độc cấp tính:\n- Cấp 2, tất cả các đường phơi nhiễm;\n- Cấp 3, đường hô hấp", 50000, null],
        ['I', 3, 'Độc tính đến cơ quan cụ thể - phơi nhiễm đơn', 50000, null],

        // II - Nguy hại vật chất
        ['II', 1, "Chất nổ:\n- Chất nổ không bền;\n- Chất nổ cấp 1.1, 1.2, 1.3, 1.5 hoặc 1.6.", 10000, null],
        ['II', 2, 'Chất nổ cấp 1.4', 50000, null],
        ['II', 3, 'Khí dễ cháy cấp 1, cấp 2', 10000, null],
        ['II', 4, 'Sol khí dễ cháy cấp 1 và cấp 2, có chứa khí dễ cháy cấp 1, cấp 2 hoặc chất lỏng dễ cháy cấp 1', 150000, 'net'],
        ['II', 5, 'Sol khí dễ cháy cấp 1 và cấp 2, không chứa khí dễ cháy cấp 1, cấp 2 và không chứa chất lỏng dễ cháy cấp 1', 5000000, 'net'],
        ['II', 6, 'Khí oxi hóa cấp 1', 50000, null],
        ['II', 7, "Chất lỏng dễ cháy:\n- Chất lỏng dễ cháy cấp 1, hoặc\n- Chất lỏng dễ cháy cấp 2 hoặc cấp 3 ở điều kiện nhiệt độ trên nhiệt độ sôi của chúng, hoặc\n- Các chất lỏng khác có nhiệt độ chớp cháy ≤60°C, ở điều kiện nhiệt độ trên nhiệt độ sôi của chúng.", 10000, null],
        ['II', 8, "Chất lỏng dễ cháy:\n- Chất lỏng dễ cháy cấp 2 hoặc cấp 3 ở điều kiện áp suất cao hoặc nhiệt độ cao có thể tạo ra nguy cơ lớn, hoặc\n- Các chất lỏng khác có nhiệt độ chớp cháy ≤60°C ở điều kiện áp suất cao hoặc nhiệt độ cao có thể tạo ra nguy cơ lớn.", 50000, null],
        ['II', 9, 'Chất lỏng dễ cháy cấp 2 hoặc cấp 3 không thuộc trường hợp quy định tại mục 7, mục 8 bảng này.', 5000000, null],
        ['II', 10, 'Chất và hỗn hợp tự phản ứng kiểu A hoặc kiểu B; peroxyt hữu cơ kiểu A hoặc kiểu B', 10000, null],
        ['II', 11, 'Chất và hỗn hợp tự phản ứng kiểu C, D, E, F; peroxyt hữu cơ kiểu C, D, E, F', 50000, null],
        ['II', 12, 'Chất lỏng tự cháy cấp 1; chất rắn tự cháy cấp 1', 50000, null],
        ['II', 13, 'Chất lỏng oxi hóa cấp 1, 2 hoặc 3; chất rắn oxi hóa cấp 1, 2 hoặc 3', 50000, null],
        ['II', 14, 'Chất hoặc hợp chất khi tiếp xúc với nước gây phát sinh khí dễ cháy cấp 1', 100000, null],

        // III - Nguy hại cho môi trường
        ['III', 1, 'Nguy hại cấp tính đến môi trường thủy sinh cấp 1', 100000, null],
        ['III', 2, 'Nguy hại mãn tính đến môi trường thủy sinh cấp 2', 200000, null],

        // IV - Nguy hại khác
        ['IV', 1, 'Chất hoặc hợp chất gây nguy hiểm EUH014', 100000, null],
        ['IV', 2, 'Chất hoặc hợp chất gây nguy hiểm EUH029', 50000, null],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('mixture_hazard_categories')) {
            return;
        }

        // Mã kế tiếp bắt đầu từ số lớn nhất đang có
        $maxNumber = (int) DB::table('mixture_hazard_categories')
            ->where('code', 'like', 'B%')
            ->get('code')
            ->map(fn ($r) => (int) substr($r->code, 1))
            ->max();

        foreach (self::ROWS as [$group, $ordinal, $name, $thresholdKg, $basis]) {
            $exists = DB::table('mixture_hazard_categories')
                ->where('hazard_group', $group)
                ->where('ordinal', $ordinal)
                ->first();

            $payload = [
                'hazard_group' => $group,
                'ordinal' => $ordinal,
                'name' => $name,
                'threshold_kg' => $thresholdKg,
                'threshold_basis' => $basis,
                'legal_ref' => 'Nghị định 24/2026/NĐ-CP - Phụ lục IV - Bảng B',
                'is_statutory' => 1,
                'app_status' => 'approved',
                'status_id' => 1,
                'updated_at' => now(),
            ];

            if ($exists) {
                DB::table('mixture_hazard_categories')->where('id', $exists->id)->update($payload);
                continue;
            }

            $payload['code'] = 'B' . str_pad((string) (++$maxNumber), 5, '0', STR_PAD_LEFT);
            $payload['created_by'] = 'Hệ thống';
            $payload['approved_by'] = 'Hệ thống';
            $payload['approved_at'] = now();
            $payload['created_at'] = now();

            DB::table('mixture_hazard_categories')->insert($payload);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('mixture_hazard_categories')) {
            return;
        }

        foreach (self::ROWS as [$group, $ordinal]) {
            DB::table('mixture_hazard_categories')
                ->where('hazard_group', $group)
                ->where('ordinal', $ordinal)
                ->where('is_statutory', 1)
                ->where('created_by', 'Hệ thống')
                ->delete();
        }
    }
};
