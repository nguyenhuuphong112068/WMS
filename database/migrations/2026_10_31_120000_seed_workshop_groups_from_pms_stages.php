<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tạo Tổ cho 5 phân xưởng NM2 theo công đoạn sản xuất của PMS (pms.room.stage_code ↔ pms.stages):
 * mỗi stage_code phân xưởng đang có phòng sản xuất = 1 tổ. Bỏ công đoạn 8 (Bảo Trì - Hiệu Chuẩn,
 * thuộc KTBT) và 9 (Kế Hoạch Khác); công đoạn 2 (Cân Nguyên Liệu Khác) gộp vào Cân Nguyên Liệu.
 * Ghi cứng danh sách để chạy được trên máy không có DB pms. Tổ đã có (cùng tên, cùng phòng) thì bỏ qua.
 */
return new class extends Migration
{
    private const CREATED_BY = 'Hệ thống (công đoạn PMS)';

    private const GROUPS = [
        'PXV1' => ['Cân Nguyên Liệu', 'Pha Chế', 'Trộn Hoàn Tất', 'Định Hình', 'Bao Phim', 'ĐGSC-ĐGTC'],
        'PXV2' => ['Cân Nguyên Liệu', 'Pha Chế', 'Trộn Hoàn Tất', 'Định Hình', 'Bao Phim', 'ĐGSC-ĐGTC'],
        'PXVH' => ['Cân Nguyên Liệu', 'Pha Chế', 'Trộn Hoàn Tất', 'Định Hình', 'Bao Phim', 'ĐGSC-ĐGTC'],
        'PXTN' => ['Cân Nguyên Liệu', 'Pha Chế', 'ĐGSC-ĐGTC'],
        'PXDN' => ['Cân Nguyên Liệu', 'Pha Chế', 'ĐGSC-ĐGTC'],
    ];

    public function up(): void
    {
        $now = now();
        $departments = DB::table('deparments')->whereIn('shortName', array_keys(self::GROUPS))->get(['id', 'shortName']);

        foreach ($departments as $dept) {
            foreach (self::GROUPS[$dept->shortName] as $name) {
                $exists = DB::table('groups')
                    ->where('department_id', $dept->id)
                    ->where('name', $name)
                    ->exists();

                if (! $exists) {
                    DB::table('groups')->insert([
                        'name' => $name,
                        'department_id' => $dept->id,
                        'status_id' => 1,
                        'created_by' => self::CREATED_BY,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('groups')->where('created_by', self::CREATED_BY)->delete();
    }
};
