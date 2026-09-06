<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DỮ LIỆU ĐỊNH KHU MẪU (DUMMY) CHO TẤT CẢ CÁC PHÒNG
 *
 * Với mỗi phòng ban trong bảng `deparments`, seeder tạo đủ 5 cấp định khu:
 *
 *   Kho/Phòng (warehouses)  1 kho / phòng ban
 *     └─ Kệ/Tủ (shelves)         3 kệ / kho
 *          └─ Cột (columns)          2 cột / kệ
 *               └─ Tầng (tiers)           3 tầng / cột
 *                    └─ Vị trí (locations)    2 vị trí / tầng
 *
 *   => mỗi phòng ban: 1 kho, 3 kệ, 6 cột, 18 tầng, 36 vị trí.
 *
 * Mã (code) có tiền tố "D{department_id}-" nên luôn duy nhất toàn hệ thống.
 * Dùng updateOrInsert theo `code` -> chạy lại nhiều lần vẫn an toàn.
 *
 * Chạy:  php artisan db:seed --class=ZoneDemoSeeder
 */
class ZoneDemoSeeder extends Seeder
{
    /** Kệ / tủ trong mỗi kho. */
    private const SHELVES = [
        ['A', 'Kệ A'],
        ['B', 'Kệ B'],
        ['C', 'Kệ C'],
    ];

    /** Số cột trên mỗi kệ. */
    private const COLUMNS_PER_SHELF = 2;

    /** Số tầng trên mỗi cột. */
    private const TIERS_PER_COLUMN = 3;

    /** Số vị trí trên mỗi tầng. */
    private const LOCATIONS_PER_TIER = 2;

    public function run(): void
    {
        $now = now();

        $departments = DB::table('deparments')->orderBy('id')->get();

        if ($departments->isEmpty()) {
            $this->command->warn('ZoneDemoSeeder: chưa có phòng ban nào trong bảng deparments, bỏ qua.');

            return;
        }

        $countWh = $countShelf = $countColumn = $countTier = $countLoc = 0;

        foreach ($departments as $dep) {
            $depId = $dep->id;
            $short = $this->slug($dep->shortName ?: $dep->name ?: ('P' . $depId));
            $prefix = 'D' . $depId . '-';

            // ---------- Kho / Phòng ----------
            $whCode = $prefix . 'KHO-' . $short;
            DB::table('warehouses')->updateOrInsert(['code' => $whCode], [
                'name' => 'Kho ' . ($dep->name ?: $short),
                'department_id' => $depId,
                'status_id' => 1,
                'created_by' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
            $warehouseId = DB::table('warehouses')->where('code', $whCode)->value('id');
            $countWh++;

            $base = ['department_id' => $depId, 'warehouse_id' => $warehouseId, 'status_id' => 1,
                'created_by' => null, 'updated_at' => $now, 'created_at' => $now];

            foreach (self::SHELVES as [$sKey, $sName]) {
                // ---------- Kệ / Tủ ----------
                $shelfCode = $prefix . 'KE-' . $short . '-' . $sKey;
                DB::table('shelves')->updateOrInsert(['code' => $shelfCode], ['name' => $sName] + $base);
                $shelfId = DB::table('shelves')->where('code', $shelfCode)->value('id');
                $countShelf++;

                for ($c = 1; $c <= self::COLUMNS_PER_SHELF; $c++) {
                    // ---------- Cột ----------
                    $columnCode = $shelfCode . '-C' . $this->pad($c);
                    DB::table('columns')->updateOrInsert(['code' => $columnCode], [
                        'name' => 'Cột ' . $this->pad($c),
                        'shelf_id' => $shelfId,
                    ] + $base);
                    $columnId = DB::table('columns')->where('code', $columnCode)->value('id');
                    $countColumn++;

                    for ($t = 1; $t <= self::TIERS_PER_COLUMN; $t++) {
                        // ---------- Tầng ----------
                        $tierCode = $columnCode . '-T' . $this->pad($t);
                        DB::table('tiers')->updateOrInsert(['code' => $tierCode], [
                            'name' => 'Tầng ' . $this->pad($t),
                            'shelf_id' => $shelfId,
                            'column_id' => $columnId,
                        ] + $base);
                        $tierId = DB::table('tiers')->where('code', $tierCode)->value('id');
                        $countTier++;

                        for ($i = 1; $i <= self::LOCATIONS_PER_TIER; $i++) {
                            // ---------- Vị trí ----------
                            $locCode = $tierCode . '-' . $this->pad($i);
                            DB::table('locations')->updateOrInsert(['code' => $locCode], [
                                'shelf_id' => $shelfId,
                                'column_id' => $columnId,
                                'tier_id' => $tierId,
                            ] + $base);
                            $countLoc++;
                        }
                    }
                }
            }
        }

        $this->command->info(sprintf(
            'ZoneDemoSeeder: %d phòng ban -> %d kho, %d kệ, %d cột, %d tầng, %d vị trí.',
            $departments->count(),
            $countWh,
            $countShelf,
            $countColumn,
            $countTier,
            $countLoc
        ));
    }

    private function pad(int $number): string
    {
        return str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    /** Rút gọn chuỗi thành mã ngắn không dấu, chỉ chữ HOA/số/gạch. */
    private function slug(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $ascii = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $ascii));

        return $ascii !== '' ? $ascii : 'P';
    }
}
