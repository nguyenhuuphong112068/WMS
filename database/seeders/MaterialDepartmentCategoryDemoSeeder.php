<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * VẬT TƯ CỦA PHÒNG MẪU (DUMMY) TỪ DANH MỤC VẬT TƯ CÔNG TY
 *
 * Khai cho một phòng ban mọi dòng danh mục vật tư công ty dummy (MATRIX #01..#96, xem
 * MaterialCategoryMatrixSeeder) vào material_department_categories, với:
 *   - Phân loại phòng : ngẫu nhiên trong bộ nhóm của phòng (department_classification)
 *   - Đơn vị tính     : ngẫu nhiên trong nhóm đơn vị đếm được (Cái, Bộ, Hộp...)
 *   - Ngưỡng tồn      : tối thiểu 1..20, tối đa = tối thiểu + 5..50 (luôn >= tối thiểu)
 *   - Định khu        : ngẫu nhiên trong các vị trí đang hoạt động của phòng
 *
 * Mặc định phòng Kỹ thuật bảo trì (id 4) - phòng đang có cấu trúc định khu. Đổi phòng bằng
 * biến môi trường SEED_DEPARTMENT_ID. Dùng seed ngẫu nhiên cố định nên chạy lại cho cùng
 * kết quả; vật tư phòng đã khai thì bỏ qua (khớp ràng buộc unique department + category).
 *
 * Chạy:  php artisan db:seed --class=MaterialDepartmentCategoryDemoSeeder
 */
class MaterialDepartmentCategoryDemoSeeder extends Seeder
{
    private const TABLE = 'material_department_categories';
    private const DEFAULT_DEPARTMENT_ID = 4;
    private const ACTOR = 'Seeder (MaterialDepartmentCategoryDemoSeeder)';

    /** Đơn vị đếm được, hợp với vật tư (tra theo tên ở bảng units). */
    private const UNIT_NAMES = ['Cái/Chiếc', 'Chiếc', 'Bộ', 'Hộp', 'Cuộn', 'Gói', 'Túi', 'Thùng', 'Đôi', 'Mét'];

    public function run(): void
    {
        $departmentId = (int) (env('SEED_DEPARTMENT_ID') ?: self::DEFAULT_DEPARTMENT_ID);

        $categoryIds = DB::table('material_categories')
            ->where('technical_specification', 'like', 'MATRIX #%')
            ->where('status_id', 1)
            ->orderBy('code')
            ->pluck('id')->all();

        $classificationIds = DB::table('department_classification')
            ->where('department_id', $departmentId)->where('status_id', 1)
            ->pluck('id')->all();

        $locationIds = DB::table('locations')
            ->where('department_id', $departmentId)->where('status_id', 1)
            ->where(fn ($q) => $q->whereNull('item_type')->orWhere('item_type', 'material'))
            ->pluck('id')->all();

        $unitIds = DB::table('units')->whereIn('name', self::UNIT_NAMES)->where('status_id', 1)->pluck('id')->all();

        if (! $categoryIds) {
            $this->command->error('Chưa có danh mục dummy - chạy MaterialCategoryMatrixSeeder trước.');

            return;
        }

        if (! $unitIds) {
            $this->command->error('Không tìm thấy đơn vị tính phù hợp.');

            return;
        }

        if (! $classificationIds || ! $locationIds) {
            $this->command->warn('Phòng '.$departmentId.' thiếu '.(! $classificationIds ? 'phân loại ' : '').(! $locationIds ? 'định khu' : '').' - các ô đó sẽ để trống.');
        }

        $declared = DB::table(self::TABLE)->where('department_id', $departmentId)->pluck('category_id')->flip();

        mt_srand(20261009 + $departmentId);

        $created = 0;

        foreach ($categoryIds as $categoryId) {
            // Rút số ngẫu nhiên cho mọi dòng kể cả dòng bỏ qua để kết quả không lệch khi chạy lại
            $min = mt_rand(1, 20);
            $row = [
                'department_id' => $departmentId,
                'category_id' => $categoryId,
                'classification_id' => $classificationIds ? $classificationIds[mt_rand(0, count($classificationIds) - 1)] : null,
                'unit_id' => $unitIds[mt_rand(0, count($unitIds) - 1)],
                'default_location_id' => $locationIds ? $locationIds[mt_rand(0, count($locationIds) - 1)] : null,
                'min_stock' => $min,
                'max_stock' => $min + mt_rand(5, 50),
                'note' => 'Dữ liệu mẫu',
                'status_id' => 1,
                'created_by' => self::ACTOR,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (isset($declared[$categoryId])) {
                continue;
            }

            DB::table(self::TABLE)->insert($row);
            $created++;
        }

        $this->command->info('Phòng '.$departmentId.': đã khai '.$created.' / '.count($categoryIds).' vật tư của phòng.');
    }
}
