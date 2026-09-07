<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cập nhật EngshortName (tiền tố mã ống chuẩn theo tiếng Anh) cho các phòng:
 *   - PTPT  → AD
 *   - QC-B1 → QC1
 *   - QC-B2 → QC2
 *   - QC-NM1 → QC
 *
 * Ghép theo shortName vì đó là khoá nhận dạng phòng ban trong hệ thống.
 * Mapping theo dữ liệu thực tế:
 *   PTPT     (Phát Triển Phân Tích)                → AD
 *   KTCL-B1  (Kiểm tra chất lượng - Toàn nhà 1)   → QC1
 *   KTCL-B2  (Kiểm tra chất lượng - Toàn nhà 2)   → QC2
 *   KTCL-MN1 (Kiểm Tra Chất Lượng - NM1)          → QC
 */
return new class extends Migration
{
    private array $map = [
        'PTPT'     => 'AD',
        'KTCL-B1'  => 'QC1',
        'KTCL-B2'  => 'QC2',
        'KTCL-MN1' => 'QC',
    ];

    public function up(): void
    {
        foreach ($this->map as $shortName => $engShort) {
            DB::table('deparments')
                ->where('shortName', $shortName)
                ->update(['EngshortName' => $engShort]);
        }
    }

    public function down(): void
    {
        // Xoá lại các giá trị vừa set (chỉ xoá nếu đúng giá trị mình đã đặt)
        foreach ($this->map as $shortName => $engShort) {
            DB::table('deparments')
                ->where('shortName', $shortName)
                ->where('EngshortName', $engShort)
                ->update(['EngshortName' => null]);
        }
    }
};
