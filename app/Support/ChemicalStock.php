<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * TỒN KHO HOÁ CHẤT TÍNH RA (hệ thống không lưu bảng tồn).
 *
 * Gom lại phần dùng chung cho các bộ máy đối chiếu ngưỡng Phụ lục IV NĐ 24/2026/NĐ-CP
 * (Bảng A - App\Support\ActiveIngredientThreshold, Bảng B - App\Support\MixtureHazardThreshold)
 * để hai nơi không viết lệch công thức.
 *
 * Khớp App\Http\Controllers\Pages\Inventory\ChemicalInventoryController:
 *   tồn 1 mã xuất nhập = chemical_imports.amount
 *                      + SUM(chemical_balancings.balancing_amount)   -- status_id = 1
 *                      - SUM(chemical_exports.amount)                 -- status_id = 1
 *   (chỉ tính chemical_imports.status_id = 1)
 *
 * Query Builder thuần, không Eloquent.
 */
class ChemicalStock
{
    /**
     * Tồn theo (department_id, category_id): [ "deptId-categoryId" => tồn (đơn vị của phòng) ].
     *
     * @param  int|null  $departmentId  Chỉ một phòng ban.
     * @param  array<int>|null  $scopeDepartmentIds  Giới hạn trong tập phòng ban này (ví dụ các
     *                                               phòng thuộc một công ty). null = không giới hạn.
     *                                               Mảng rỗng = không có phòng nào -> không có tồn.
     */
    public static function onHandByDepartmentCategory(array $categoryIds, ?int $departmentId = null, ?array $scopeDepartmentIds = null): array
    {
        $out = [];

        foreach (self::onHandByLot($categoryIds, $departmentId, $scopeDepartmentIds) as $lot) {
            $key = $lot->department_id . '-' . $lot->category_id;
            $out[$key] = ($out[$key] ?? 0) + $lot->on_hand;
        }

        return $out;
    }

    /**
     * Tồn còn lại của TỪNG mã xuất nhập (mỗi phiếu chemical_imports đang hiệu lực một dòng),
     * để modal "xem chi tiết" cho thấy con số tồn được cộng từ những phiếu nào.
     *
     *   tồn 1 phiếu = imported + balanced - exported   (tất cả theo đơn vị của phòng)
     *
     * @param  int|null  $departmentId  Chỉ một phòng ban.
     * @param  array<int>|null  $scopeDepartmentIds  Giới hạn trong tập phòng ban này. null = không giới hạn.
     * @return array<int, object>  mỗi phần tử:
     *   {import_id, code, imported_date, category_id, department_id,
     *    imported, balanced, exported, on_hand}
     */
    public static function onHandByLot(array $categoryIds, ?int $departmentId = null, ?array $scopeDepartmentIds = null): array
    {
        $imports = DB::table('chemical_imports')
            ->whereIn('category_id', $categoryIds)
            ->where('status_id', 1)
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($scopeDepartmentIds !== null, fn ($q) => $q->whereIn('department_id', $scopeDepartmentIds))
            ->select('id', 'code', 'imported_date', 'category_id', 'department_id', 'amount')
            ->get();

        if ($imports->isEmpty()) {
            return [];
        }

        $importIds = $imports->pluck('id')->all();

        $used = DB::table('chemical_exports')
            ->whereIn('import_id', $importIds)
            ->where('status_id', 1)
            ->select('import_id', DB::raw('SUM(amount) as total'))
            ->groupBy('import_id')
            ->pluck('total', 'import_id');

        $balanced = DB::table('chemical_balancings')
            ->whereIn('import_id', $importIds)
            ->where('status_id', 1)
            ->select('import_id', DB::raw('SUM(balancing_amount) as total'))
            ->groupBy('import_id')
            ->pluck('total', 'import_id');

        $out = [];

        foreach ($imports as $import) {
            $imported = (float) $import->amount;
            $bal = (float) ($balanced[$import->id] ?? 0);
            $exp = (float) ($used[$import->id] ?? 0);

            $out[] = (object) [
                'import_id' => (int) $import->id,
                'code' => (string) $import->code,
                'imported_date' => substr((string) $import->imported_date, 0, 10),
                'category_id' => (int) $import->category_id,
                'department_id' => (int) $import->department_id,
                'imported' => $imported,
                'balanced' => $bal,
                'exported' => $exp,
                'on_hand' => $imported + $bal - $exp,
            ];
        }

        return $out;
    }

    /**
     * Chuỗi sự kiện làm thay đổi tồn của từng (phòng ban, mã danh mục), đã sắp theo ngày.
     *
     * Hệ thống không lưu bảng tồn nên "mức tồn cao nhất đã từng đạt" phải dựng lại bằng
     * cách cộng dồn các chứng từ theo trục thời gian:
     *   + chemical_imports.amount            tại imported_date
     *   + chemical_balancings.balancing_amount (có thể âm) tại ngày của balancing_at
     *   - chemical_exports.amount            tại exported_date
     * Chỉ tính chứng từ đang hiệu lực (status_id = 1) - chứng từ bị khoá về sau không còn
     * trong chuỗi, nên đỉnh tính lại theo trạng thái hiện hành. Độ phân giải theo NGÀY;
     * trong cùng một ngày thì cộng (nhập) trước, trừ (xuất) sau để ra đỉnh thận trọng.
     *
     * @param  int|null  $departmentId  Chỉ một phòng ban.
     * @param  array<int>|null  $scopeDepartmentIds  Giới hạn trong tập phòng ban này. null = không giới hạn.
     * @return array<int, array{date: string, type: string, ref: string, department_id: int, category_id: int, delta: float}>
     *         type: 'import' | 'balancing' | 'export' | 'cancel'; ref: mã chứng từ.
     */
    public static function movementEvents(array $categoryIds, ?int $departmentId = null, ?array $scopeDepartmentIds = null): array
    {
        $imports = DB::table('chemical_imports')
            ->whereIn('category_id', $categoryIds)
            ->where('status_id', 1)
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($scopeDepartmentIds !== null, fn ($q) => $q->whereIn('department_id', $scopeDepartmentIds))
            ->select('id', 'code', 'category_id', 'department_id', 'amount', 'imported_date')
            ->get();

        if ($imports->isEmpty()) {
            return [];
        }

        $importById = $imports->keyBy('id');
        $importIds = $imports->pluck('id')->all();

        $events = [];

        foreach ($imports as $import) {
            $events[] = [
                'date' => substr((string) $import->imported_date, 0, 10),
                'type' => 'import',
                'ref' => (string) $import->code,
                'department_id' => (int) $import->department_id,
                'category_id' => (int) $import->category_id,
                'delta' => (float) $import->amount,
            ];
        }

        $balancings = DB::table('chemical_balancings')
            ->whereIn('import_id', $importIds)
            ->where('status_id', 1)
            ->select('import_id', 'code', 'balancing_amount', 'balancing_at')
            ->get();

        foreach ($balancings as $balancing) {
            $import = $importById->get($balancing->import_id);

            if (! $import) {
                continue;
            }

            $events[] = [
                'date' => substr((string) $balancing->balancing_at, 0, 10),
                'type' => 'balancing',
                'ref' => (string) $balancing->code,
                'department_id' => (int) $import->department_id,
                'category_id' => (int) $import->category_id,
                'delta' => (float) $balancing->balancing_amount,
            ];
        }

        $exports = DB::table('chemical_exports')
            ->whereIn('import_id', $importIds)
            ->where('status_id', 1)
            ->select('import_id', 'code', 'amount', 'type', 'exported_date')
            ->get();

        foreach ($exports as $export) {
            $import = $importById->get($export->import_id);

            if (! $import) {
                continue;
            }

            $events[] = [
                'date' => substr((string) $export->exported_date, 0, 10),
                'type' => $export->type === 'cancel' ? 'cancel' : 'export',
                'ref' => (string) $export->code,
                'department_id' => (int) $import->department_id,
                'category_id' => (int) $import->category_id,
                'delta' => -(float) $export->amount,
            ];
        }

        // Sắp theo ngày; cùng ngày: delta dương (nhập / bù thêm) trước, delta âm (xuất) sau
        usort($events, function ($a, $b) {
            return strcmp($a['date'], $b['date'])
                ?: (($b['delta'] <=> 0) <=> ($a['delta'] <=> 0));
        });

        return $events;
    }

    /**
     * Đơn vị tính của từng (phòng ban, mã danh mục): [ "deptId-categoryId" => object đơn vị ].
     */
    public static function departmentUnits(array $categoryIds): array
    {
        return DB::table('chemical_department_categories as dc')
            ->join('units as u', 'dc.unit_id', '=', 'u.id')
            ->whereIn('dc.category_id', $categoryIds)
            ->where('dc.status_id', 1)
            ->select('dc.department_id', 'dc.category_id', 'u.unit_group', 'u.factor_to_base', 'u.short_name')
            ->get()
            ->keyBy(fn ($r) => $r->department_id . '-' . $r->category_id)
            ->all();
    }
}
