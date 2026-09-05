<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nạp dữ liệu gốc Tên Sản Phẩm từ hệ thống PMS (database `pms`, bảng `product_name`)
 * sang `product_names` của WMS - chạy một lần duy nhất. Môi trường không có sẵn DB pms
 * (production, máy dev khác...) thì bỏ qua, không lỗi migrate.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::select("SHOW DATABASES LIKE 'pms'")) {
            return;
        }

        $existing = DB::table('product_names')->pluck('name')
            ->map(fn ($n) => mb_strtolower(trim($n)))
            ->all();

        $rows = DB::table('pms.product_name')->select('name', 'active')->orderBy('id')->get();

        foreach ($rows as $row) {
            $name = trim((string) $row->name);

            if ($name === '' || in_array(mb_strtolower($name), $existing)) {
                continue;
            }

            DB::table('product_names')->insert([
                'name' => $name,
                'status_id' => $row->active ? 1 : 0,
                'created_by' => 'Hệ thống (đồng bộ từ PMS)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $existing[] = mb_strtolower($name);
        }
    }

    public function down(): void
    {
        // Không gỡ: tên sản phẩm sau khi nạp có thể đã được dùng ở nơi khác trong hệ thống.
    }
};
