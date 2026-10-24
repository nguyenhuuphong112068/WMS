<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tạo 4 tổ chuẩn cho 3 phòng KTCL (QC) của 2 nhà máy:
 * QC-B1, QC-B2 (NM2) và KTCL - NM1. Tổ đã có (cùng tên, cùng phòng) thì bỏ qua.
 */
return new class extends Migration
{
    private const GROUPS = [
        'Tổ Nguyên Liệu',
        'Tổ Vi Sinh',
        'Tổ Nguyên liệu - Hóa chất - Chất chuẩn',
        'Tổ Hành chánh - Tổng hợp',
    ];

    private function departmentIds(): array
    {
        return DB::table('deparments')
            ->whereIn('shortName', ['QC-B1', 'QC-B2'])
            ->orWhere(function ($q) {
                $q->where('shortName', 'KTCL')->where('name', 'like', '%NM1%');
            })
            ->pluck('id')
            ->all();
    }

    public function up(): void
    {
        $now = now();

        foreach ($this->departmentIds() as $deptId) {
            foreach (self::GROUPS as $name) {
                $exists = DB::table('groups')
                    ->where('department_id', $deptId)
                    ->where('name', $name)
                    ->exists();

                if (! $exists) {
                    DB::table('groups')->insert([
                        'name' => $name,
                        'department_id' => $deptId,
                        'status_id' => 1,
                        'created_by' => 'Hệ thống',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('groups')
            ->whereIn('department_id', $this->departmentIds())
            ->whereIn('name', self::GROUPS)
            ->delete();
    }
};
