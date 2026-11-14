<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MỖI NHÀ MÁY TỰ CÓ BỘ PHẬN MUA HÀNG (14/11/2026).
 *
 * NM1 và NM2 đã tách thành 2 bản WMS riêng (DB wms = NM2, wms_nm1 = NM1). Trước đây NM1 không có bộ
 * phận mua hàng, dự trù / đề nghị VPP gửi về Cung Ứng - NM2 và Hành Chính - NM2; nay hai DB không
 * còn thấy nhau nên mỗi DB phải tự có phòng mang deparments.purchasing_key (App\Support\MaterialPurchasing):
 *   - supply (Cung Ứng): DB chưa có phòng nào -> tạo "Cung Ứng - NM1" cho công ty của DB.
 *   - admin  (Hành Chính, mua + giữ kho VPP - App\Support\VppStore): DB chưa có -> gắn cho phòng
 *     Hành Chính sẵn có.
 * Chạy trên CẢ HAI DB: DB wms đã có đủ 2 khoá nên không đổi gì.
 */
return new class extends Migration
{
    private const SUPPLY_NAME = 'Cung Ứng - NM1';

    public function up(): void
    {
        $companyIds = DB::table('companies')->pluck('id');

        // Chỉ xử lý DB đã tách một công ty - DB gộp nhiều công ty thì không đoán được phòng của ai
        if ($companyIds->count() !== 1) {
            return;
        }

        $companyId = (int) $companyIds->first();
        $now = now();

        $hasKey = fn (string $key) => DB::table('deparments')->where('isActive', 1)->where('purchasing_key', $key)->exists();

        if (! $hasKey('supply')) {
            $existing = DB::table('deparments')->where('name', self::SUPPLY_NAME)->first();

            if ($existing) {
                DB::table('deparments')->where('id', $existing->id)->update(['purchasing_key' => 'supply', 'isActive' => 1, 'updated_at' => $now]);
            } else {
                DB::table('deparments')->insert([
                    'company_id' => $companyId,
                    'name' => self::SUPPLY_NAME,
                    'shortName' => 'CƯ',
                    'EngshortName' => 'PROC',
                    'prepareBy' => 'Hệ thống',
                    'isActive' => 1,
                    'is_general' => 1,
                    'purchasing_key' => 'supply',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! $hasKey('admin')) {
            $adminId = DB::table('deparments')
                ->where('isActive', 1)
                ->whereNull('purchasing_key')
                ->where('name', 'like', 'Hành Chính%')
                ->orderBy('id')
                ->value('id');

            if ($adminId) {
                DB::table('deparments')->where('id', $adminId)->update(['purchasing_key' => 'admin', 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Không gỡ: phòng Cung Ứng có thể đã phát sinh dự trù / tồn kho.
    }
};
