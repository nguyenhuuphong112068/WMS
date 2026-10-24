<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 10 VẬT TƯ MẪU MUA QUA CUNG ỨNG (DỰ TRÙ CUNG ỨNG)
 *
 * Tạo 10 danh mục vật tư công ty đã duyệt, bộ phận mua hàng = Cung Ứng (supply), rồi khai
 * luôn cho một phòng ban (vật tư của phòng: đơn vị, ngưỡng tồn, định khu).
 *
 * Mặc định phòng Đảm Bảo Chất Lượng - Nhà Máy 1 (id 12). Đổi phòng bằng biến môi trường
 * SEED_DEPARTMENT_ID. Tên vật tư / danh mục đã có thì dùng lại, vật tư phòng đã khai thì bỏ qua.
 *
 * Chạy:  php artisan db:seed --class=MaterialSupplyDemoSeeder
 */
class MaterialSupplyDemoSeeder extends Seeder
{
    private const DEFAULT_DEPARTMENT_ID = 12;
    private const ACTOR = 'Hệ thống';
    private const NOTE = 'Vật tư cung ứng';

    /** [tên, short_name đơn vị, NSX id, giá, quan trọng, nguy hiểm, hiệu chuẩn, ngày đặt hàng, min, max] */
    private const ITEMS = [
        ['Găng tay nitrile không bột size M', 'hộp', 1, 'low', 'high', 'no', 'no', 14, 10, 50],
        ['Khẩu trang y tế 4 lớp', 'hộp', 6, 'low', 'high', 'no', 'no', 14, 10, 60],
        ['Đầu côn (tip) 1000 µL', 'gói', 7, 'low', 'high', 'no', 'no', 21, 5, 30],
        ['Ống ly tâm nhựa 50 mL', 'gói', 1, 'low', 'low', 'no', 'no', 21, 5, 30],
        ['Màng lọc PTFE 0.45 µm 47 mm', 'hộp', 6, 'high', 'high', 'no', 'no', 30, 2, 10],
        ['Pipet Pasteur nhựa 3 mL', 'gói', 7, 'low', 'low', 'no', 'no', 21, 5, 20],
        ['Bình định mức thuỷ tinh 100 mL class A', 'cái', 1, 'high', 'high', 'no', 'yes', 45, 5, 20],
        ['Nhiệt kế điện tử -50~300°C', 'cái', 6, 'high', 'high', 'no', 'yes', 45, 2, 6],
        ['Giấy lọc định lượng số 42', 'hộp', 7, 'low', 'low', 'no', 'no', 30, 2, 10],
        ['Vial HPLC 2 mL nắp vặn', 'hộp', 1, 'high', 'high', 'no', 'no', 30, 3, 15],
    ];

    public function run(): void
    {
        $departmentId = (int) (env('SEED_DEPARTMENT_ID') ?: self::DEFAULT_DEPARTMENT_ID);

        $units = DB::table('units')->where('status_id', 1)->pluck('id', 'short_name');

        $locationIds = DB::table('locations')
            ->where('department_id', $departmentId)->where('status_id', 1)
            ->where(fn ($q) => $q->whereNull('item_type')->orWhere('item_type', 'material'))
            ->orderBy('code')
            ->pluck('id')->all();

        $nextCode = (int) substr((string) DB::table('material_categories')->where('code', 'like', 'M%')->max('code'), 1);

        $createdCategories = 0;
        $createdDepartment = 0;

        foreach (self::ITEMS as $i => [$name, $unit, $makerId, $price, $importance, $dangerous, $calibration, $leadTime, $min, $max]) {
            $nameId = DB::table('material_names')->where('name', $name)->value('id');
            if (! $nameId) {
                $nameId = DB::table('material_names')->insertGetId([
                    'name' => $name,
                    'app_status' => 'approved',
                    'status_id' => 1,
                    'created_by' => self::ACTOR,
                    'approved_by' => self::ACTOR,
                    'approved_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $categoryId = DB::table('material_categories')
                ->where('material_names_id', $nameId)->where('manufacturers_id', $makerId)
                ->value('id');

            if (! $categoryId) {
                $nextCode++;
                $categoryId = DB::table('material_categories')->insertGetId([
                    'code' => 'M'.str_pad((string) $nextCode, 5, '0', STR_PAD_LEFT),
                    'material_names_id' => $nameId,
                    'manufacturers_id' => $makerId,
                    'technical_specification' => 'CU #'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'classification' => json_encode([
                        'price' => $price,
                        'importance' => $importance,
                        'dangerous' => $dangerous,
                        'qa_calibration' => $calibration,
                    ]),
                    'purchasing_department' => 'supply',
                    'lead_time_days' => $leadTime,
                    'app_status' => 'approved',
                    'status_id' => 1,
                    'created_by' => self::ACTOR,
                    'approved_by' => self::ACTOR,
                    'approved_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $createdCategories++;
            }

            $declared = DB::table('material_department_categories')
                ->where('department_id', $departmentId)->where('category_id', $categoryId)->exists();

            if ($declared) {
                continue;
            }

            DB::table('material_department_categories')->insert([
                'department_id' => $departmentId,
                'category_id' => $categoryId,
                'classification_id' => null,
                'unit_id' => $units[$unit] ?? null,
                // Một nửa số vật tư được định khu sẵn, nửa còn lại để "Chưa định khu" cho bộ lọc
                'default_location_id' => ($locationIds && $i % 2 === 0) ? $locationIds[$i % count($locationIds)] : null,
                'min_stock' => $min,
                'max_stock' => $max,
                'note' => self::NOTE,
                'status_id' => 1,
                'created_by' => self::ACTOR,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $createdDepartment++;
        }

        $this->command->info('Đã tạo '.$createdCategories.' danh mục công ty, khai '.$createdDepartment.' vật tư cho phòng '.$departmentId.'.');
    }
}
