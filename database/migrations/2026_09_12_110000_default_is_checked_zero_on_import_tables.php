<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MẶC ĐỊNH is_checked = 0 (CHỜ KIỂM TRA) CHO CẢ 3 BẢNG NHẬP.
 *
 * Một consumable (vật tư / hoá chất / chất chuẩn) khi NHẬP LẦN ĐẦU luôn ở trạng thái
 * chưa kiểm tra, phải hiện trong tab "Chờ kiểm tra" rồi mới được cộng vào tồn kho.
 * Đặt mặc định ngay ở CSDL để mọi đường ghi dữ liệu đều theo đúng quy tắc này, không
 * phụ thuộc controller có nhớ set hay không.
 *
 * Ngoại lệ DUY NHẤT là dòng nhập sinh ra khi NHẬN CHUYỂN LIÊN PHÒNG BAN: hàng đã được
 * kiểm tra ở lô gốc bên phòng gửi nên các chỗ đó ghi thẳng is_checked = 1 (xem
 * ChemicalExportController / MaterialExportController / StandardExportController).
 *
 * Migration trước (2026_09_12_100000) để mặc định 1 nhằm giữ dữ liệu cũ trong tồn kho;
 * dữ liệu cũ đã được set 1 ở đó rồi nên đổi mặc định lúc này không đụng tới chúng.
 */
return new class extends Migration
{
    private const TABLES = ['material_imports', 'chemical_imports', 'standard_imports'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'is_checked')) {
                DB::statement('ALTER TABLE `'.$table.'` MODIFY `is_checked` TINYINT NOT NULL DEFAULT 0');
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'is_checked')) {
                DB::statement('ALTER TABLE `'.$table.'` MODIFY `is_checked` TINYINT NOT NULL DEFAULT 1');
            }
        }
    }
};
