<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tạo một công ty mặc định và gán toàn bộ phòng ban chưa có công ty vào đó.
 *
 * Giữ cho các phần mềm đang chạy một công ty không đổi hành vi: sau migration mọi phòng
 * ban vẫn nằm chung một công ty nên "Ngưỡng Tồn Trữ PL IV" cộng y như trước. Khi triển
 * khai thêm công ty mới, người dùng đổi tên công ty mặc định này và tạo thêm công ty
 * khác ở màn "Dữ Liệu Gốc › Công Ty".
 */
return new class extends Migration
{
    private const DEFAULT_CODE = 'CT00001';

    public function up(): void
    {
        $now = now();

        $companyId = DB::table('companies')->where('code', self::DEFAULT_CODE)->value('id');

        if (! $companyId) {
            $companyId = DB::table('companies')->insertGetId([
                'code' => self::DEFAULT_CODE,
                'name' => 'Công Ty Mặc Định',
                'short_name' => 'CTMD',
                'status_id' => 1,
                'created_by' => 'System',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('deparments')->whereNull('company_id')->update(['company_id' => $companyId]);
    }

    public function down(): void
    {
        $companyId = DB::table('companies')->where('code', self::DEFAULT_CODE)->value('id');

        if ($companyId) {
            DB::table('deparments')->where('company_id', $companyId)->update(['company_id' => null]);
            DB::table('companies')->where('id', $companyId)->delete();
        }
    }
};
