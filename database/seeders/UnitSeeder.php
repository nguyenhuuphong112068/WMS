<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DỮ LIỆU GỐC - BỘ ĐƠN VỊ TÍNH CHUẨN
 *
 * Tạo sẵn các đơn vị thường dùng kèm thông tin quy đổi (nhóm + hệ số về đơn vị gốc),
 * để chức năng quy đổi ở App\Support\UnitConverter chạy được ngay.
 *
 * Chạy lại nhiều lần vẫn an toàn:
 * - Đơn vị đã có (theo ký hiệu) : chỉ cập nhật nhóm và hệ số, GIỮ NGUYÊN tên và trạng thái duyệt
 *   mà người dùng đã chỉnh.
 * - Đơn vị chưa có              : thêm mới ở trạng thái đã duyệt để dùng được luôn.
 * - Tên bị bảng khác chiếm chỗ  : bỏ qua và báo lại, không ghi đè dữ liệu người dùng.
 *
 * Chạy riêng:  php artisan db:seed --class=UnitSeeder
 */
class UnitSeeder extends Seeder
{
    private const TABLE = 'units';

    /**
     * [ký hiệu, tên đầy đủ, nhóm, hệ số quy về đơn vị gốc của nhóm]
     *
     * Đơn vị gốc: mass = g, volume = ml, count = 1 đơn vị đếm.
     */
    private const UNITS = [
        // ---------- Khối lượng (gốc: g) ----------
        ['µg', 'Vi-crô-gam', 'mass', 0.000001],
        ['mg', 'Mi-li-gam', 'mass', 0.001],
        ['g', 'Gam', 'mass', 1],
        ['kg', 'Ki-lô-gam', 'mass', 1000],
        ['tấn', 'Tấn', 'mass', 1000000],

        // ---------- Thể tích (gốc: ml) ----------
        ['µl', 'Vi-crô-lít', 'volume', 0.001],
        ['ml', 'Mi-li-lít', 'volume', 1],
        ['cc', 'Xăng-ti-mét khối', 'volume', 1],
        ['L', 'Lít', 'volume', 1000],
        ['m3', 'Mét khối', 'volume', 1000000],

        // ---------- Đếm / Bao bì (không tự quy đổi sang kg hay lít) ----------
        ['cái', 'Cái', 'count', 1],
        ['chiếc', 'Chiếc', 'count', 1],
        ['bộ', 'Bộ', 'count', 1],
        ['đôi', 'Đôi', 'count', 1],
        ['viên', 'Viên', 'count', 1],
        ['chai', 'Chai', 'count', 1],
        ['lọ', 'Lọ', 'count', 1],
        ['ống', 'Ống', 'count', 1],
        ['can', 'Can', 'count', 1],
        ['phuy', 'Phuy', 'count', 1],
        ['xô', 'Xô', 'count', 1],
        ['hộp', 'Hộp', 'count', 1],
        ['thùng', 'Thùng', 'count', 1],
        ['bao', 'Bao', 'count', 1],
        ['túi', 'Túi', 'count', 1],
        ['gói', 'Gói', 'count', 1],
        ['vỉ', 'Vỉ', 'count', 1],
        ['kiện', 'Kiện', 'count', 1],
        ['cuộn', 'Cuộn', 'count', 1],
        ['tờ', 'Tờ', 'count', 1],
        ['m', 'Mét', 'count', 1],
    ];

    public function run(): void
    {
        $added = 0;
        $updated = 0;
        $skipped = [];

        foreach (self::UNITS as [$shortName, $name, $group, $factor]) {
            $current = DB::table(self::TABLE)->where('short_name', $shortName)->first();

            // Đã có: chỉ bổ sung thông tin quy đổi, không đụng tới tên và trạng thái duyệt
            if ($current) {
                DB::table(self::TABLE)->where('id', $current->id)->update([
                    'unit_group' => $group,
                    'factor_to_base' => $factor,
                    'updated_by' => 'Hệ thống',
                    'updated_at' => now(),
                ]);

                $updated++;
                continue;
            }

            // Tên đã bị một đơn vị khác dùng: bỏ qua để không phá dữ liệu người dùng
            if (DB::table(self::TABLE)->where('name', $name)->exists()) {
                $skipped[] = $shortName . ' (tên "' . $name . '" đã được dùng)';
                continue;
            }

            DB::table(self::TABLE)->insert([
                'short_name' => $shortName,
                'name' => $name,
                'unit_group' => $group,
                'factor_to_base' => $factor,
                'app_status' => 'approved',
                'status_id' => 1,
                'created_by' => 'Hệ thống',
                'approved_by' => 'Hệ thống',
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $added++;
        }

        $this->command->info("Đơn vị tính: thêm mới {$added}, cập nhật quy đổi {$updated}, bỏ qua " . count($skipped) . '.');

        foreach ($skipped as $line) {
            $this->command->warn('  Bỏ qua: ' . $line);
        }
    }
}
