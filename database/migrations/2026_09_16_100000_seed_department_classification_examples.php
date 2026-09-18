<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEED VÍ DỤ PHÂN LOẠI THEO PHÒNG (department_classification)
 *
 * Dữ liệu mẫu do Phòng Tổng Hợp đề xuất cho 3 phòng, mỗi phòng một cách chia nhóm riêng:
 *   - QC (KTCL-B1, KTCL-B2, KTCL-MN1) : chia theo loại vật tư (spare part / tiêu hao / khác)
 *   - PX (PXV1, PXV2, PXVH, PXDN, PXTN, PXV-NM1) : chia theo có tiếp xúc sản phẩm hay không
 *   - HC (HC-NM2)                                : chia theo tần suất dự trù
 *
 * updateOrInsert khoá theo (department_id, name) nên chạy lại nhiều lần không nhân đôi.
 */
return new class extends Migration
{
    private const QC_DEPARTMENTS = ['KTCL-B1', 'KTCL-B2', 'KTCL-MN1'];
    private const PX_DEPARTMENTS = ['PXV1', 'PXV2', 'PXVH', 'PXDN', 'PXTN', 'PXV-NM1'];
    private const HC_DEPARTMENTS = ['HC-NM2'];

    private const QC_NAMES = [
        'Spare part của thiết bị và dụng cụ thủy tinh',
        'Vật tư tiêu hao dùng để phân tích mẫu',
        'Khác (văn phòng phẩm, dụng cụ vệ sinh...)',
    ];

    private const PX_NAMES = [
        'Có tiếp xúc',
        'Không tiếp xúc',
    ];

    private const HC_NAMES = [
        'Dự trù Hàng tháng',
        'Dự trù Hàng quí',
        'Dự trù Số lượng nhiều',
        'Dự trù Số lượng ít',
    ];

    public function up(): void
    {
        $this->seed(self::QC_DEPARTMENTS, self::QC_NAMES);
        $this->seed(self::PX_DEPARTMENTS, self::PX_NAMES);
        $this->seed(self::HC_DEPARTMENTS, self::HC_NAMES);
    }

    public function down(): void
    {
        $this->unseed(self::QC_DEPARTMENTS, self::QC_NAMES);
        $this->unseed(self::PX_DEPARTMENTS, self::PX_NAMES);
        $this->unseed(self::HC_DEPARTMENTS, self::HC_NAMES);
    }

    private function seed(array $shortNames, array $names): void
    {
        $departmentIds = DB::table('deparments')->whereIn('shortName', $shortNames)->pluck('id');

        foreach ($departmentIds as $departmentId) {
            foreach ($names as $name) {
                $exists = DB::table('department_classification')
                    ->where('department_id', $departmentId)
                    ->where('name', $name)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('department_classification')->insert([
                    'name' => $name,
                    'department_id' => $departmentId,
                    'status_id' => 1,
                    'created_by' => 'Hệ thống',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /** Chỉ xoá đúng những dòng do migration này tạo, không đụng dòng người dùng đã sửa tên. */
    private function unseed(array $shortNames, array $names): void
    {
        $departmentIds = DB::table('deparments')->whereIn('shortName', $shortNames)->pluck('id');

        DB::table('department_classification')
            ->whereIn('department_id', $departmentIds)
            ->whereIn('name', $names)
            ->where('created_by', 'Hệ thống')
            ->delete();
    }
};
