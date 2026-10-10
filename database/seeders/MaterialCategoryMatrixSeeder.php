<?php

namespace Database\Seeders;

use App\Support\MaterialClassification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DANH MỤC VẬT TƯ CÔNG TY MẪU (DUMMY) - ĐỦ MA TRẬN PHÂN LOẠI
 *
 * Sinh một dòng material_categories cho MỖI tổ hợp của 6 tiêu chí phân loại:
 *
 *   Theo giá trị (2) × Mức độ quan trọng (2) × Hàng hoá nguy hiểm (2)
 *   × Bộ phận mua hàng (3) × Cấp phát bởi Hành Chánh (2) × Cần hiệu chuẩn (2) = 96 dòng
 *
 * Bộ tiêu chí đọc thẳng từ App\Support\MaterialClassification nên đổi tiêu chí ở đó thì
 * seeder tự sinh theo. Tên vật tư lấy lần lượt từ dữ liệu gốc đã duyệt / đang hoạt động,
 * nhà sản xuất xoay vòng. Bản ghi tạo ở trạng thái ĐÃ DUYỆT để dùng ngay cho các màn khác.
 *
 * Thông tin kỹ thuật ghi "MATRIX #nn" để nhận diện dòng dummy. Chạy lại nhiều lần an toàn:
 * tổ hợp nào đã có (theo Thông tin kỹ thuật) thì bỏ qua.
 *
 * Chạy:  php artisan db:seed --class=MaterialCategoryMatrixSeeder
 */
class MaterialCategoryMatrixSeeder extends Seeder
{
    private const TABLE = 'material_categories';
    private const HISTORY_TABLE = 'material_category_histories';
    private const TAG = 'MATRIX #';
    private const ACTOR = 'Seeder (MaterialCategoryMatrixSeeder)';

    /** Thời gian đặt hàng xoay vòng, tối thiểu 7 ngày. */
    private const LEAD_TIMES = [7, 14, 30, 60, 90];

    public function run(): void
    {
        $combos = $this->combinations();

        $nameIds = DB::table('material_names')
            ->where('status_id', 1)->where('app_status', 'approved')
            ->orderBy('id')->limit(count($combos) * 2)->pluck('id')->all();

        $manufacturerIds = DB::table('manufacturers')
            ->where('status_id', 1)->where('app_status', 'approved')
            ->orderBy('id')->pluck('id')->all();

        if (count($nameIds) < count($combos) || ! $manufacturerIds) {
            $this->command->error('Thiếu dữ liệu gốc: cần ít nhất '.count($combos).' tên vật tư và 1 nhà sản xuất đã duyệt.');

            return;
        }

        // Cặp Tên + NSX đã có thì không dùng lại (checkDuplicate của màn hình chặn trùng)
        $usedPairs = DB::table(self::TABLE)->get(['material_names_id', 'manufacturers_id'])
            ->map(fn ($r) => $r->material_names_id.'-'.$r->manufacturers_id)->flip();

        $nextNumber = (int) DB::table(self::TABLE)->where('code', 'like', 'M%')->pluck('code')
            ->map(fn ($code) => (int) substr($code, 1))->max() + 1;

        $created = 0;
        $nameCursor = 0;

        foreach ($combos as $i => $combo) {
            $tag = self::TAG.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);

            if (DB::table(self::TABLE)->where('technical_specification', $tag)->exists()) {
                continue;
            }

            // Lấy tên vật tư kế tiếp chưa trùng cặp với nhà sản xuất của dòng này
            $manufacturerId = $manufacturerIds[$i % count($manufacturerIds)];
            do {
                $nameId = $nameIds[$nameCursor++] ?? null;
            } while ($nameId && isset($usedPairs[$nameId.'-'.$manufacturerId]));

            if (! $nameId) {
                $this->command->error('Hết tên vật tư chưa dùng tại tổ hợp '.$tag.'.');
                break;
            }

            $usedPairs[$nameId.'-'.$manufacturerId] = true;

            $row = [
                'code' => 'M'.str_pad((string) $nextNumber++, 5, '0', STR_PAD_LEFT),
                'material_names_id' => $nameId,
                'manufacturers_id' => $manufacturerId,
                'technical_specification' => $tag,
                'classification' => MaterialClassification::encode($combo['classification']),
                'purchasing_department' => $combo['purchasing_department'],
                'lead_time_days' => self::LEAD_TIMES[$i % count(self::LEAD_TIMES)],
                'app_status' => 'approved',
                'status_id' => 1,
                'created_by' => self::ACTOR,
                'approved_by' => self::ACTOR,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::transaction(function () use ($row) {
                $id = DB::table(self::TABLE)->insertGetId($row);

                DB::table(self::HISTORY_TABLE)->insert([
                    'material_category_id' => $id,
                    'code' => $row['code'],
                    'action' => 'Thêm mới',
                    'material_names_id' => $row['material_names_id'],
                    'manufacturers_id' => $row['manufacturers_id'],
                    'technical_specification' => $row['technical_specification'],
                    'classification' => $row['classification'],
                    'purchasing_department' => $row['purchasing_department'],
                    'lead_time_days' => $row['lead_time_days'],
                    'app_status' => $row['app_status'],
                    'status_id' => $row['status_id'],
                    'change_note' => 'Dữ liệu mẫu ma trận phân loại, mã '.$row['code'].'.',
                    'created_by' => self::ACTOR,
                    'created_at' => now(),
                ]);
            });

            $created++;
        }

        $this->command->info('Đã tạo '.$created.' / '.count($combos).' dòng danh mục vật tư mẫu.');
    }

    /**
     * Tích Descartes của mọi tiêu chí theo đúng thứ tự trên form
     * (Bộ Phận Mua Hàng đứng sau Hàng Hoá Nguy Hiểm, xem MaterialClassification::formGroups()).
     */
    private function combinations(): array
    {
        $dimensions = [];

        foreach (MaterialClassification::CRITERIA as $key => $criterion) {
            $dimensions[$key] = array_keys($criterion['options']);

            if ($key === 'dangerous') {
                $dimensions['_purchasing'] = array_keys(MaterialClassification::PURCHASING_DEPARTMENTS);
            }
        }

        $combos = [[]];

        foreach ($dimensions as $key => $values) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ($values as $value) {
                    $next[] = $combo + [$key => $value];
                }
            }
            $combos = $next;
        }

        return array_map(function ($combo) {
            $purchasing = $combo['_purchasing'];
            unset($combo['_purchasing']);

            return ['classification' => $combo, 'purchasing_department' => $purchasing];
        }, $combos);
    }
}
