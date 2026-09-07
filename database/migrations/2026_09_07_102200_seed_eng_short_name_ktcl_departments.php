<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cập nhật EngshortName cho 3 phòng KTC còn lại (PTPT đã set ở migration trước):
 *   KTCL-B1  (Kiểm tra chất lượng - Toàn nhà 1) → QC1
 *   KTCL-B2  (Kiểm tra chất lượng - Toàn nhà 2) → QC2
 *   KTCL-MN1 (Kiểm Tra Chất Lượng - NM1)        → QC
 */
return new class extends Migration
{
    private array $map = [
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
        foreach ($this->map as $shortName => $engShort) {
            DB::table('deparments')
                ->where('shortName', $shortName)
                ->where('EngshortName', $engShort)
                ->update(['EngshortName' => null]);
        }
    }
};
