<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * NGƯỠNG TỒN TỐI ĐA - DỮ LIỆU ĐỂ CẢNH BÁO LÚC NHẬP VÀ LÚC DỰ TRÙ
 *
 * Ngưỡng tối đa khai ở tab "<Loại hàng> Của Phòng" (cột max_stock của
 * material / chemical / standard_department_categories). Màn Nhập và màn Dự Trù chỉ
 * CẢNH BÁO khi tồn hiện tại cộng thêm lần này vượt ngưỡng - không chặn lưu.
 *
 * Lớp này trả về đúng ba thứ màn hình cần cho từng mã danh mục:
 *   max_stock : ngưỡng phòng đã khai (null = phòng không đặt trần tồn)
 *   on_hand   : tồn hiện tại của phòng, theo đơn vị của phòng
 *   unit      : nhãn đơn vị để viết câu cảnh báo
 *   unit_id   : id đơn vị của phòng - màn Dự Trù cho khai số lượng theo đơn vị khác, chỉ
 *               cộng được những dòng khai đúng đơn vị này
 *
 * Công thức tồn giống hệt các màn Tồn Kho (hệ thống không lưu bảng tồn):
 *
 *     tồn = SUM(imports.amount)               -- status_id = 1
 *         + SUM(balancings.balancing_amount)  -- status_id = 1
 *         - SUM(exports.amount)
 *
 * Cả ba loại hàng dùng chung một công thức nên gom vào một hàm, khác nhau đúng tên bảng.
 * Query Builder thuần, không Eloquent.
 */
class MaxStockWarning
{
    /** Cấu hình từng loại hàng: bảng danh mục phòng + bộ ba bảng nhập / cân đối / xuất. */
    private const SOURCES = [
        'material' => [
            'department_table' => 'material_department_categories',
            'imports' => 'material_imports',
            'balancings' => 'material_balancings',
            'exports' => 'material_exports',
        ],
        'chemical' => [
            'department_table' => 'chemical_department_categories',
            'imports' => 'chemical_imports',
            'balancings' => 'chemical_balancings',
            'exports' => 'chemical_exports',
        ],
        'standard' => [
            'department_table' => 'standard_department_categories',
            'imports' => 'standard_imports',
            'balancings' => 'standard_balancings',
            'exports' => 'standard_exports',
        ],
    ];

    public static function forMaterial(int $departmentId): array
    {
        return self::build('material', $departmentId);
    }

    public static function forChemical(int $departmentId): array
    {
        return self::build('chemical', $departmentId);
    }

    public static function forStandard(int $departmentId): array
    {
        return self::build('standard', $departmentId);
    }

    /**
     * [category_id => ['max_stock' => float|null, 'on_hand' => float, 'unit' => string]]
     * cho mọi mã danh mục phòng đang khai (kể cả mã chưa đặt ngưỡng - màn hình vẫn cần
     * con số tồn để viết câu cảnh báo khi phòng khai ngưỡng sau này).
     */
    private static function build(string $type, int $departmentId): array
    {
        $source = self::SOURCES[$type];

        $rows = DB::table($source['department_table'].' as dept')
            ->leftJoin('units', 'dept.unit_id', '=', 'units.id')
            ->select(
                'dept.category_id',
                'dept.max_stock',
                'dept.unit_id',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where('dept.department_id', $departmentId)
            ->where('dept.status_id', 1)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $onHand = self::onHand($source, $departmentId);

        $out = [];

        foreach ($rows as $row) {
            $out[(int) $row->category_id] = [
                'max_stock' => $row->max_stock === null ? null : (float) $row->max_stock,
                'on_hand' => (float) ($onHand[(int) $row->category_id] ?? 0),
                'unit' => $row->unit_short_name ?: ($row->unit_name ?: ''),
                'unit_id' => $row->unit_id === null ? null : (int) $row->unit_id,
            ];
        }

        return $out;
    }

    /** Tồn hiện tại của phòng theo từng mã danh mục: [category_id => tồn]. */
    private static function onHand(array $source, int $departmentId): array
    {
        $imports = DB::table($source['imports'])
            ->select('id', 'category_id', 'amount')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->get();

        if ($imports->isEmpty()) {
            return [];
        }

        $importIds = $imports->pluck('id')->all();

        $balanced = DB::table($source['balancings'])
            ->select('import_id', DB::raw('SUM(balancing_amount) as total'))
            ->whereIn('import_id', $importIds)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');

        $exported = DB::table($source['exports'])
            ->select('import_id', DB::raw('SUM(amount) as total'))
            ->whereIn('import_id', $importIds)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');

        $out = [];

        foreach ($imports as $import) {
            $remaining = (float) $import->amount
                + (float) ($balanced[$import->id] ?? 0)
                - (float) ($exported[$import->id] ?? 0);

            $categoryId = (int) $import->category_id;
            $out[$categoryId] = ($out[$categoryId] ?? 0) + max($remaining, 0);
        }

        return $out;
    }
}
