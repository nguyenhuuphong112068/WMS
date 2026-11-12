<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mã danh mục VPP công ty dùng tiền tố "P" (VppCategoryController::CODE_PREFIX) để không trùng dạng
 * với mã danh mục Vật Tư "M". Các mã chuyển từ Vật Tư sang (migration 2026_11_11_100100) đổi M -> P,
 * giữ nguyên phần số (M00097 -> P00097). Mã lô (vpp_imports.code) KHÔNG đổi vì đã in trên nhãn QR.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vpp_categories')) {
            return;
        }

        DB::table('vpp_categories')->where('code', 'like', 'M%')->update(['code' => DB::raw("CONCAT('P', SUBSTRING(code, 2))")]);
    }

    public function down(): void
    {
        DB::table('vpp_categories')->where('code', 'like', 'P%')->update(['code' => DB::raw("CONCAT('M', SUBSTRING(code, 2))")]);
    }
};
