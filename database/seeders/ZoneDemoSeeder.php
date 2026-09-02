<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DỮ LIỆU ĐỊNH KHU MẪU (DUMMY) CHO TẤT CẢ CÁC PHÒNG
 *
 * Với mỗi phòng ban trong bảng `deparments`, seeder tạo đủ 4 cấp định khu:
 *
 *   Kho (warehouses)  1 kho / phòng
 *     └─ Phòng (rooms)      4 phòng / kho
 *          └─ Kệ/Tủ (shelves)   3 kệ / phòng
 *               └─ Vị trí (locations)  4 vị trí / kệ
 *
 *   => mỗi phòng ban: 1 kho, 4 phòng, 12 kệ, 48 vị trí.
 *
 * Mã (code) có tiền tố "D{department_id}-" nên luôn duy nhất toàn hệ thống.
 * Dùng updateOrInsert theo `code` -> chạy lại nhiều lần vẫn an toàn.
 *
 * Chạy:  php artisan db:seed --class=ZoneDemoSeeder
 */
class ZoneDemoSeeder extends Seeder
{
    /** Tên các phòng kho điển hình trong nhà máy dược. */
    private const ROOMS = [
        ['NL', 'Phòng Nguyên Liệu'],
        ['BB', 'Phòng Bao Bì'],
        ['TP', 'Phòng Thành Phẩm'],
        ['BT', 'Phòng Biệt Trữ'],
    ];

    /** Kệ/tủ trong mỗi phòng. */
    private const SHELVES = [
        ['A', 'Kệ A'],
        ['B', 'Kệ B'],
        ['C', 'Kệ C'],
    ];

    /** Số vị trí trên mỗi kệ. */
    private const LOCATIONS_PER_SHELF = 4;

    public function run(): void
    {
        $now = now();

        $departments = DB::table('deparments')->orderBy('id')->get();

        if ($departments->isEmpty()) {
            $this->command->warn('ZoneDemoSeeder: chưa có phòng ban nào trong bảng deparments, bỏ qua.');

            return;
        }

        $countWh = $countRoom = $countShelf = $countLoc = 0;

        foreach ($departments as $dep) {
            $depId = $dep->id;
            $short = $this->slug($dep->shortName ?: $dep->name ?: ('P' . $depId));
            $prefix = 'D' . $depId . '-';

            // ---------- Kho ----------
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

            foreach (self::ROOMS as [$rKey, $rName]) {
                // ---------- Phòng ----------
                $roomCode = $prefix . 'P-' . $short . '-' . $rKey;
                DB::table('rooms')->updateOrInsert(['code' => $roomCode], [
                    'name' => $rName,
                    'department_id' => $depId,
                    'warehouse_id' => $warehouseId,
                    'status_id' => 1,
                    'created_by' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
                $roomId = DB::table('rooms')->where('code', $roomCode)->value('id');
                $countRoom++;

                foreach (self::SHELVES as [$sKey, $sName]) {
                    // ---------- Kệ / Tủ ----------
                    $shelfCode = $prefix . 'KE-' . $short . '-' . $rKey . $sKey;
                    DB::table('shelves')->updateOrInsert(['code' => $shelfCode], [
                        'name' => $sName . ' - ' . $rName,
                        'department_id' => $depId,
                        'warehouse_id' => $warehouseId,
                        'room_id' => $roomId,
                        'status_id' => 1,
                        'created_by' => null,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]);
                    $shelfId = DB::table('shelves')->where('code', $shelfCode)->value('id');
                    $countShelf++;

                    for ($i = 1; $i <= self::LOCATIONS_PER_SHELF; $i++) {
                        // ---------- Vị trí ----------
                        $locCode = $shelfCode . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                        DB::table('locations')->updateOrInsert(['code' => $locCode], [
                            'department_id' => $depId,
                            'warehouse_id' => $warehouseId,
                            'room_id' => $roomId,
                            'shelf_id' => $shelfId,
                            'status_id' => 1,
                            'created_by' => null,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]);
                        $countLoc++;
                    }
                }
            }
        }

        $this->command->info(sprintf(
            'ZoneDemoSeeder: %d phòng ban -> %d kho, %d phòng, %d kệ, %d vị trí.',
            $departments->count(),
            $countWh,
            $countRoom,
            $countShelf,
            $countLoc
        ));
    }

    /** Rút gọn chuỗi thành mã ngắn không dấu, chỉ chữ HOA/số/gạch. */
    private function slug(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $ascii = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $ascii));

        return $ascii !== '' ? $ascii : 'P';
    }
}
