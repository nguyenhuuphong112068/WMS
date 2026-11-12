<?php

use App\Support\DepartmentVpp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * VPP CỦA PHÒNG LUÔN ĐỦ BẰNG DANH MỤC VPP CÔNG TY (12/11/2026)
 *
 * Phòng không còn tự thêm / khoá dòng VPP của phòng: mỗi mã VPP công ty có đúng một dòng ở mọi
 * phòng ban (DepartmentVpp::syncRows). Migration này mở khoá các dòng phòng đã khoá trước đây và
 * bù dòng còn thiếu cho mọi phòng ban.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('vpp_department_categories')->where('status_id', '<>', 1)->update([
            'status_id' => 1,
            'updated_by' => 'Hệ thống',
            'updated_at' => now(),
        ]);

        DepartmentVpp::syncRows(null, 'Hệ thống');
    }

    public function down(): void
    {
        // Không xoá dòng đã bù: phòng có thể đã khai ngưỡng tồn / định khu trên đó.
    }
};
