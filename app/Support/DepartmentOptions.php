<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * GOM PHÒNG BAN THEO CÔNG TY CHO Ô CHỌN BỘ PHẬN
 *
 * Hai công ty có phòng trùng tên (Hành Chính, QA...), nên mọi <select> chọn bộ phận
 * đều hiển thị dạng <optgroup> theo công ty. Bên gọi chỉ cần truyền danh sách phòng
 * (có id), tên công ty được tra lại theo deparments.company_id nên controller không
 * phải join thêm bảng companies.
 *
 * Dùng qua partial layout/departmentOptions.blade.php.
 *
 * Query Builder thuần, không Eloquent.
 */
class DepartmentOptions
{
    public const NO_COMPANY = 'Chưa gán công ty';

    /**
     * @param  iterable  $departments  object/array có id (name, shortName nếu có)
     * @return array<int, array{company: string, company_id: ?int, departments: array}>
     */
    public static function groups(iterable $departments): array
    {
        $departments = collect($departments)->map(fn ($d) => (object) $d)->values();

        if ($departments->isEmpty()) {
            return [];
        }

        $companies = DB::table('deparments')
            ->leftJoin('companies', 'deparments.company_id', '=', 'companies.id')
            ->whereIn('deparments.id', $departments->pluck('id')->filter()->all())
            ->select('deparments.id', 'deparments.company_id', 'companies.name as company_name')
            ->get()
            ->keyBy('id');

        $groups = [];
        foreach ($departments as $dept) {
            $company = $companies->get($dept->id);
            $companyId = $company->company_id ?? null;
            $key = $companyId ?: 0;

            $groups[$key] ??= [
                'company' => ($company->company_name ?? null) ?: self::NO_COMPANY,
                'company_id' => $companyId ? (int) $companyId : null,
                'departments' => [],
            ];
            $groups[$key]['departments'][] = $dept;
        }

        // Công ty theo thứ tự id (công ty mẹ trước chi nhánh), phòng chưa gán công ty xếp cuối
        uksort($groups, fn ($a, $b) => ($a === 0) <=> ($b === 0) ?: $a <=> $b);

        return array_values($groups);
    }

    /** Nhãn chuẩn của 1 phòng trong ô chọn: "Tên phòng (Mã)". */
    public static function label(object $dept): string
    {
        $name = $dept->name ?? '';
        $short = $dept->shortName ?? '';

        if ($name === '') {
            return $short;
        }

        return $short !== '' && $short !== $name ? "{$name} ({$short})" : $name;
    }
}
