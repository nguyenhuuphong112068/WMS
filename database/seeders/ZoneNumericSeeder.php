<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SEED LẠI ĐỊNH KHU CỦA MỘT PHÒNG BAN THEO MÃ SỐ KK/TT/CC/TT/VV
 *
 * Mã của mỗi cấp là phần đầu của mã cấp dưới, tất cả đều là số hai chữ số:
 *
 *   Kho/Phòng   01
 *     └─ Kệ/Tủ      01/01
 *          └─ Cột       01/01/01
 *               └─ Tầng      01/01/01/01
 *                    └─ Vị trí     01/01/01/01/01
 *
 * Nhìn mã vị trí là đọc ra ngay đường đi: kho 01, kệ 01, cột 01, tầng 01, ô 01.
 *
 * Seeder XOÁ HẲN định khu cũ của phòng ban rồi tạo lại từ đầu, nên chỉ chạy khi
 * chưa có phiếu nào xếp hàng vào các vị trí đó - nếu còn, seeder dừng và báo rõ
 * bảng nào đang giữ, không xoá gì cả.
 *
 * Đổi phòng ban: sửa hằng số DEPARTMENT bên dưới, hoặc đặt biến môi trường ZONE_DEPT.
 *
 * Chạy:  php artisan db:seed --class=ZoneNumericSeeder
 */
class ZoneNumericSeeder extends Seeder
{
    /** shortName của phòng ban được seed lại. */
    private const DEPARTMENT = 'KTBT-NM2';

    /** Quy mô từng cấp. */
    private const WAREHOUSES = 2;

    private const SHELVES_PER_WAREHOUSE = 5;

    private const COLUMNS_PER_SHELF = 5;

    private const TIERS_PER_COLUMN = 5;

    private const LOCATIONS_PER_TIER = 5;

    /** Người tạo ghi trên dữ liệu mẫu, để phân biệt với dữ liệu người dùng tự khai. */
    private const ACTOR = 'Dữ liệu dummy (seed)';

    /** Các cột đang trỏ tới locations.id - phải rỗng thì mới được xoá định khu cũ. */
    private const LOCATION_REFERENCES = [
        ['chemical_department_categories', 'default_location_id'],
        ['chemical_import_histories', 'location_id'],
        ['chemical_imports', 'location_id'],
        ['chemical_transfer_items', 'dest_location_id'],
        ['material_department_categories', 'default_location_id'],
        ['material_import_histories', 'location_id'],
        ['material_imports', 'location_id'],
        ['material_pick_lines', 'location_id'],
        ['material_stocktake_items', 'location_id'],
        ['standard_department_categories', 'default_location_id'],
        ['standard_import_histories', 'location_id'],
        ['standard_imports', 'location_id'],
        ['standard_transfer_items', 'dest_location_id'],
    ];

    /** Năm bảng định khu, xếp từ cấp dưới lên trên để xoá không làm mồ côi cấp con. */
    private const TABLES = ['locations', 'tiers', 'columns', 'shelves', 'warehouses'];

    public function run(): void
    {
        $shortName = env('ZONE_DEPT', self::DEPARTMENT);

        $department = DB::table('deparments')->where('shortName', $shortName)->first();

        if (! $department) {
            $this->command->error('ZoneNumericSeeder: không tìm thấy phòng ban "'.$shortName.'".');

            return;
        }

        if (! $this->canWipe((int) $department->id)) {
            return;
        }

        $this->wipe((int) $department->id);
        $counts = $this->build((int) $department->id);

        $this->command->info(sprintf(
            'ZoneNumericSeeder: %s -> %d kho, %d kệ, %d cột, %d tầng, %d vị trí (mã KK/TT/CC/TT/VV).',
            $shortName,
            $counts['warehouses'],
            $counts['shelves'],
            $counts['columns'],
            $counts['tiers'],
            $counts['locations']
        ));
    }

    /** Còn phiếu nào đang xếp hàng vào định khu cũ thì không được xoá. */
    private function canWipe(int $departmentId): bool
    {
        $locationIds = DB::table('locations')->where('department_id', $departmentId)->pluck('id')->all();

        if (! $locationIds) {
            return true;
        }

        foreach (self::LOCATION_REFERENCES as [$table, $column]) {
            $used = DB::table($table)->whereIn($column, $locationIds)->count();

            if ($used > 0) {
                $this->command->error(
                    'ZoneNumericSeeder: dừng lại, còn '.$used.' dòng ở '.$table.'.'.$column
                    .' đang trỏ tới vị trí cũ. Chuyển hoặc xoá các dòng này trước khi seed lại.'
                );

                return false;
            }
        }

        return true;
    }

    /** Xoá định khu cũ của phòng ban, kèm lịch sử thay đổi của chính các bản ghi đó. */
    private function wipe(int $departmentId): void
    {
        foreach (self::TABLES as $table) {
            $ids = DB::table($table)->where('department_id', $departmentId)->pluck('id')->all();

            if (! $ids) {
                continue;
            }

            DB::table('datamaster_histories')->where('table_name', $table)->whereIn('record_id', $ids)->delete();
            DB::table($table)->whereIn('id', $ids)->delete();
        }
    }

    /** Dựng lại đủ năm cấp, cấp dưới lấy mã cấp trên làm tiền tố. */
    private function build(int $departmentId): array
    {
        $now = now();
        $base = [
            'department_id' => $departmentId,
            'status_id' => 1,
            'created_by' => self::ACTOR,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $counts = ['warehouses' => 0, 'shelves' => 0, 'columns' => 0, 'tiers' => 0, 'locations' => 0];
        $locations = [];

        for ($w = 1; $w <= self::WAREHOUSES; $w++) {
            $warehouseCode = $this->pad($w);
            $warehouseId = DB::table('warehouses')->insertGetId([
                'code' => $warehouseCode,
                'name' => 'Kho '.$this->pad($w),
            ] + $base);
            $counts['warehouses']++;

            for ($s = 1; $s <= self::SHELVES_PER_WAREHOUSE; $s++) {
                $shelfCode = $warehouseCode.'/'.$this->pad($s);
                $shelfId = DB::table('shelves')->insertGetId([
                    'code' => $shelfCode,
                    'name' => 'Kệ/Tủ '.$this->pad($s),
                    'warehouse_id' => $warehouseId,
                ] + $base);
                $counts['shelves']++;

                for ($c = 1; $c <= self::COLUMNS_PER_SHELF; $c++) {
                    $columnCode = $shelfCode.'/'.$this->pad($c);
                    $columnId = DB::table('columns')->insertGetId([
                        'code' => $columnCode,
                        'name' => 'Cột '.$this->pad($c),
                        'warehouse_id' => $warehouseId,
                        'shelf_id' => $shelfId,
                    ] + $base);
                    $counts['columns']++;

                    for ($t = 1; $t <= self::TIERS_PER_COLUMN; $t++) {
                        $tierCode = $columnCode.'/'.$this->pad($t);
                        $tierId = DB::table('tiers')->insertGetId([
                            'code' => $tierCode,
                            'name' => 'Tầng '.$this->pad($t),
                            'warehouse_id' => $warehouseId,
                            'shelf_id' => $shelfId,
                            'column_id' => $columnId,
                        ] + $base);
                        $counts['tiers']++;

                        for ($v = 1; $v <= self::LOCATIONS_PER_TIER; $v++) {
                            // Vị trí chỉ có mã, và để trống loại lưu trữ = dùng chung cho cả ba màn hình Tồn Kho
                            $locations[] = [
                                'code' => $tierCode.'/'.$this->pad($v),
                                'warehouse_id' => $warehouseId,
                                'shelf_id' => $shelfId,
                                'column_id' => $columnId,
                                'tier_id' => $tierId,
                            ] + $base;
                            $counts['locations']++;
                        }
                    }
                }
            }
        }

        // Vị trí nhiều nhất nên chèn theo lô cho nhanh, mỗi lô 500 dòng
        foreach (array_chunk($locations, 500) as $chunk) {
            DB::table('locations')->insert($chunk);
        }

        return $counts;
    }

    private function pad(int $number): string
    {
        return str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }
}
