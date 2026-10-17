<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * DỰ TRÙ HOÁ CHẤT NHÓM 9 / 10 ĐỐI CHIẾU NGƯỠNG TỒN TRỮ PHỤ LỤC IV NĐ 24/2026
 *
 * Bổ sung cho App\Support\ActiveIngredientThreshold (Bảng A - nhóm 9) và
 * App\Support\MixtureHazardThreshold (Bảng B - nhóm 10) phần "đang dự trù":
 *
 *   tổng đối chiếu = tồn HIỆN TẠI toàn công ty
 *                  + lượng các mặt hàng dự trù CHƯA HOÀN THÀNH (xem pendingRows())
 *
 * Tổng này đã chạm 100% ngưỡng thì mã danh mục bị CHẶN, không được dự trù thêm (khung chọn
 * danh mục ở modal Thêm mặt hàng + kiểm tra lại ở server) - xem categoryStatus().
 *
 * QUY RA KG (rawKg()): dòng số lượng dự trù khai theo đơn vị bất kỳ. Với đơn vị khác đơn vị
 * danh mục của phòng, người dùng khai hệ số chemical_estimate_item_amounts.conversion_factor
 * (1 đơn vị dự trù = bao nhiêu đơn vị danh mục), rồi đơn vị danh mục quy ra kg theo đúng cách
 * tính tồn (tỉ trọng, "Khối lượng quy đổi" pack_weight_kg của đơn vị đếm). Không có hệ số thì
 * thử quy đổi thẳng đơn vị dự trù sang kg (khối lượng / thể tích + tỉ trọng).
 *
 * Query Builder thuần, không Eloquent.
 */
class ChemicalEstimateThreshold
{
    /** Phiếu ở các trạng thái này không còn tính là "đang dự trù". */
    private const CLOSED_APP_STATUSES = ['cancelled'];

    private static array $densityCache = [];

    private static array $deptUnitCache = [];

    private static ?array $unitCache = null;

    private static ?array $criticalCache = null;

    /* -------------------------------------------------------------------------
     |  Quy lượng dự trù ra kg
     | ------------------------------------------------------------------------- */

    /**
     * Cộng các dòng số lượng {amount, unit_id, conversion_factor} của MỘT mặt hàng dự trù,
     * quy về kg chất THÔ (chưa nhân % hàm lượng).
     *
     * @param  int|null  $departmentId  Phòng lập phiếu - để biết đơn vị danh mục của phòng.
     * @return array{kg: float, unconvertible: bool}
     */
    public static function rawKg(int $categoryId, $amountRows, ?int $departmentId = null): array
    {
        $rows = collect($amountRows);
        $density = self::density($categoryId);

        if ($density === false) {
            return ['kg' => 0.0, 'unconvertible' => $rows->isNotEmpty()];
        }

        $deptUnit = $departmentId ? self::deptUnit($departmentId, $categoryId) : null;
        $kgPerDeptUnit = $deptUnit ? self::kgPerDeptUnit($deptUnit, $density) : null;
        $kgUnit = (object) ['unit_group' => 'mass', 'factor_to_base' => 1000.0];

        $totalKg = 0.0;
        $unconvertible = false;

        foreach ($rows as $row) {
            $qty = (float) self::field($row, 'amount');

            if ($qty <= 0) {
                continue;
            }

            $unitId = (int) self::field($row, 'unit_id');
            $factor = (float) self::field($row, 'conversion_factor');
            $kg = null;

            if ($deptUnit && $kgPerDeptUnit !== null) {
                if ($unitId === (int) $deptUnit->unit_id) {
                    $kg = $qty * $kgPerDeptUnit;
                } elseif ($factor > 0) {
                    $kg = $qty * $factor * $kgPerDeptUnit;
                }
            }

            // Không qua đơn vị danh mục được thì quy thẳng đơn vị dự trù sang kg
            if ($kg === null) {
                $unit = self::units()[$unitId] ?? null;

                if ($unit && $unit->unit_group !== 'count'
                    && ! ($unit->unit_group === 'volume' && ($density === null || $density <= 0))) {
                    $kg = UnitConverter::convert($qty, $unit, $kgUnit, $density);
                }
            }

            if ($kg === null) {
                $unconvertible = true;

                continue;
            }

            $totalKg += $kg;
        }

        return ['kg' => $totalKg, 'unconvertible' => $unconvertible];
    }

    /* -------------------------------------------------------------------------
     |  Lượng đang dự trù chưa hoàn thành
     | ------------------------------------------------------------------------- */

    /**
     * Mặt hàng dự trù hoá chất CHƯA HOÀN THÀNH trong phạm vi một công ty, của các mã danh mục
     * cần đối chiếu: mặt hàng còn hiệu lực (active = 1), chưa huỷ (status_id = 1), chưa xác
     * nhận giao (fulfilled_date NULL), thuộc phiếu chưa khoá và chưa huỷ - kể cả phiếu Nháp /
     * Chờ ký / Bị từ chối (đang soạn lại) / Đã duyệt chờ mua.
     *
     * @return array<int, object>  {item_id, category_id, department_id, list_id, list_code, app_status,
     *                             amounts, a_kg, b_kg, unconvertible}
     *                             a_kg = kg hoạt chất (Bảng A, đã nhân %), b_kg = kg thô (Bảng B),
     *                             amounts = các dòng số lượng (để modal "Chi tiết" in lại)
     */
    public static function pendingRows(?int $companyId, array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        if (! $categoryIds) {
            return [];
        }

        $scopeDepartmentIds = CompanyContext::departmentIds($companyId);

        $amounts = DB::table('chemical_estimate_items as i')
            ->join('chemical_estimates as e', 'i.estimate_list_id', '=', 'e.id')
            ->join('chemical_estimate_item_amounts as a', 'a.estimate_item_id', '=', 'i.id')
            ->whereIn('i.category_id', $categoryIds)
            ->where('i.active', 1)
            ->where('i.status_id', 1)
            ->whereNull('i.fulfilled_date')
            ->where('a.active', 1)
            ->where('e.status_id', 1)
            ->whereNotIn('e.app_status', self::CLOSED_APP_STATUSES)
            ->when($scopeDepartmentIds !== null, fn ($query) => $query->whereIn('e.department_id', $scopeDepartmentIds))
            ->select(
                'i.id as item_id', 'i.category_id', 'e.department_id', 'e.id as list_id', 'e.code as list_code',
                'e.app_status', 'a.amount', 'a.unit_id', 'a.conversion_factor', 'a.for_month_year'
            )
            ->orderBy('e.id')
            ->orderBy('a.for_month_year')
            ->get()
            ->groupBy('item_id');

        $out = [];

        foreach ($amounts as $itemId => $rows) {
            $first = $rows->first();
            $categoryId = (int) $first->category_id;
            $departmentId = (int) $first->department_id;

            $sumA = ActiveIngredientThreshold::sumEstimateKg($categoryId, $rows, $departmentId);
            $sumB = MixtureHazardThreshold::sumEstimateKg($categoryId, $rows, $departmentId);

            $out[] = (object) [
                'item_id' => (int) $itemId,
                'category_id' => $categoryId,
                'department_id' => $departmentId,
                'list_id' => (int) $first->list_id,
                'list_code' => $first->list_code,
                'app_status' => $first->app_status,
                'amounts' => $rows->values()->all(),
                'a_kg' => $sumA['kg'],
                'b_kg' => $sumB['kg'],
                'unconvertible' => $sumA['unconvertible'] || $sumB['unconvertible'],
            ];
        }

        return $out;
    }

    /**
     * Gom pendingRows() theo mã danh mục, bỏ riêng một mặt hàng (khi đang sửa / đang xem
     * chính mặt hàng đó thì không tự cộng mình hai lần).
     *
     * @return array{A: array<int, float>, B: array<int, float>}
     */
    public static function pendingByCategory(array $pendingRows, ?int $excludeItemId = null): array
    {
        $out = ['A' => [], 'B' => []];

        foreach ($pendingRows as $row) {
            if ($excludeItemId && $row->item_id === $excludeItemId) {
                continue;
            }

            $out['A'][$row->category_id] = ($out['A'][$row->category_id] ?? 0.0) + $row->a_kg;
            $out['B'][$row->category_id] = ($out['B'][$row->category_id] ?? 0.0) + $row->b_kg;
        }

        return $out;
    }

    /* -------------------------------------------------------------------------
     |  Trạng thái ngưỡng theo mã danh mục
     | ------------------------------------------------------------------------- */

    /**
     * Tổng tồn hiện tại + đang dự trù so với ngưỡng, theo từng mã danh mục có đối chiếu.
     * Một mã đứng sau nhiều chủ ngưỡng thì giữ chủ ngưỡng có tỉ lệ cao nhất.
     *
     * @return array<int, object>  category_id => {table, name, basis, threshold_kg, current_kg,
     *                              pending_kg, total_kg, ratio, level, blocked, message}
     */
    public static function categoryStatus(?int $companyId): array
    {
        $ingredients = ActiveIngredientThreshold::ingredients();
        $evalA = ActiveIngredientThreshold::evaluate(null, $companyId);
        $evalB = MixtureHazardThreshold::evaluate(null, $companyId);

        $categoryIds = [];
        foreach ($ingredients as $ing) {
            if (isset($evalA[$ing->ai_id])) {
                array_push($categoryIds, ...$ing->category_ids);
            }
        }
        foreach ($evalB as $row) {
            array_push($categoryIds, ...$row->category_ids);
        }

        $pending = self::pendingByCategory(self::pendingRows($companyId, $categoryIds));
        $out = [];

        foreach ($ingredients as $ing) {
            $eval = $evalA[$ing->ai_id] ?? null;

            if (! $eval) {
                continue;
            }

            self::keep($out, $ing->category_ids, self::status(
                'A', $eval->ai_name, 'Bảng A', (float) $eval->threshold_kg, (float) $eval->total_kg,
                self::sumFor($pending['A'], $ing->category_ids)
            ));
        }

        foreach ($evalB as $eval) {
            self::keep($out, $eval->category_ids, self::status(
                'B', $eval->chem_name, 'Bảng B' . ($eval->strictest_group ? ' - nhóm ' . $eval->strictest_group : ''),
                (float) $eval->min_threshold_kg, (float) $eval->total_kg,
                self::sumFor($pending['B'], $eval->category_ids)
            ));
        }

        return $out;
    }

    /**
     * Mã danh mục thuộc nhóm 9 hoặc 10 (App\Support\ChemicalClassification::CRITICAL_GROUPS) -
     * dự trù bằng đơn vị khác đơn vị danh mục thì bắt khai hệ số quy đổi.
     *
     * @return int[]
     */
    public static function criticalCategoryIds(): array
    {
        if (self::$criticalCache !== null) {
            return self::$criticalCache;
        }

        $out = [];

        foreach (ChemicalClassification::groupsByCategory() as $categoryId => $groups) {
            if (array_intersect($groups, ChemicalClassification::CRITICAL_GROUPS)) {
                $out[] = (int) $categoryId;
            }
        }

        return self::$criticalCache = $out;
    }

    /** Đơn vị danh mục của phòng cho một mã: {unit_id, short_name, unit_group, factor_to_base, pack_weight_kg} */
    public static function deptUnit(int $departmentId, int $categoryId): ?object
    {
        $key = $departmentId . '-' . $categoryId;

        if (! array_key_exists($key, self::$deptUnitCache)) {
            self::$deptUnitCache[$key] = DB::table('chemical_department_categories as dc')
                ->join('units as u', 'dc.unit_id', '=', 'u.id')
                ->where('dc.department_id', $departmentId)
                ->where('dc.category_id', $categoryId)
                ->where('dc.status_id', 1)
                ->select('dc.unit_id', 'dc.pack_weight_kg', 'u.short_name', 'u.name', 'u.unit_group', 'u.factor_to_base')
                ->first();
        }

        return self::$deptUnitCache[$key];
    }

    /* -------------------------------------------------------------------------
     |  Chi tiết các lượng đóng góp (nút "Chi tiết" của cảnh báo ngưỡng)
     | ------------------------------------------------------------------------- */

    /**
     * Các lượng đóng góp vào đối chiếu ngưỡng PL IV của MỘT mã danh mục, mỗi chủ ngưỡng đứng
     * sau mã (hoạt chất Bảng A / hỗn hợp Bảng B) một phần tử:
     *   current    : tồn hiện tại toàn công ty, chi tiết theo lô (eval->onhand_rows) + phần
     *                chưa quy đổi được (eval->unconvertible)
     *   pending    : từng mặt hàng dự trù CHƯA HOÀN THÀNH của MỌI mã danh mục cùng chủ ngưỡng
     *   add        : lượng đang khai ($addRows - dòng trên form, hoặc chính mặt hàng đang xem)
     *   quarantine : lô "Chờ kiểm tra" - KHÔNG cộng vào tồn, liệt kê để người dùng đối chiếu
     *
     * @param  iterable  $addRows  dòng {amount, unit_id, conversion_factor, for_month_year?}
     * @param  int|null  $departmentId  phòng khai $addRows (quy đổi qua đơn vị danh mục của phòng)
     * @param  int|null  $excludeItemId  mặt hàng đang sửa / đang xem - không tính lại vào pending
     * @return array<int, object>
     */
    public static function breakdown(int $categoryId, ?int $companyId, $addRows = [], ?int $departmentId = null, ?int $excludeItemId = null): array
    {
        $owners = [];
        $ingredients = null;

        foreach (ActiveIngredientThreshold::detailForCategory($categoryId, $companyId) as $eval) {
            $ingredients ??= ActiveIngredientThreshold::ingredients();

            $owners[] = (object) [
                'table' => 'A',
                'eval' => $eval,
                'threshold_kg' => (float) $eval->threshold_kg,
                'category_ids' => $ingredients[$eval->ai_id]->category_ids ?? [$categoryId],
            ];
        }

        if ($evalB = MixtureHazardThreshold::detailForCategory($categoryId, $companyId)) {
            $owners[] = (object) [
                'table' => 'B',
                'eval' => $evalB,
                'threshold_kg' => (float) $evalB->min_threshold_kg,
                'category_ids' => $evalB->category_ids,
            ];
        }

        if (! $owners) {
            return [];
        }

        $allCategoryIds = array_values(array_unique(array_merge(...array_map(fn ($o) => $o->category_ids, $owners))));

        $pendingRows = array_values(array_filter(
            self::pendingRows($companyId, $allCategoryIds),
            fn ($row) => ! $excludeItemId || $row->item_id !== $excludeItemId
        ));
        $quarantine = self::quarantineLots($allCategoryIds, $companyId);

        $addA = ActiveIngredientThreshold::sumEstimateKg($categoryId, $addRows, $departmentId);
        $addB = MixtureHazardThreshold::sumEstimateKg($categoryId, $addRows, $departmentId);

        foreach ($owners as $owner) {
            $isA = $owner->table === 'A';
            $inOwner = fn ($row) => in_array((int) $row->category_id, $owner->category_ids, true);

            $owner->pending = array_values(array_filter($pendingRows, $inOwner));
            $owner->pending_kg = array_sum(array_map(fn ($row) => $isA ? $row->a_kg : $row->b_kg, $owner->pending));
            $owner->quarantine = array_values(array_filter($quarantine, $inOwner));

            $owner->current_kg = (float) $owner->eval->total_kg;
            $owner->add_kg = $isA ? $addA['kg'] : $addB['kg'];
            $owner->add_unconvertible = $isA ? $addA['unconvertible'] : $addB['unconvertible'];

            $threshold = $owner->threshold_kg;
            $owner->base_kg = $owner->current_kg + $owner->pending_kg;
            $owner->projected_kg = $owner->base_kg + $owner->add_kg;
            $owner->base_ratio = $threshold > 0 ? $owner->base_kg / $threshold : 0.0;
            $owner->projected_ratio = $threshold > 0 ? $owner->projected_kg / $threshold : 0.0;
            $owner->level = ActiveIngredientThreshold::classify($owner->projected_ratio);
            // Cùng luật với categoryStatus(): tồn + đang dự trù đã chạm ngưỡng thì chặn dự trù thêm
            $owner->blocked = $owner->base_ratio >= 1.0;
        }

        return $owners;
    }

    /**
     * Lô hoá chất đang "Chờ kiểm tra" (chemical_imports.is_checked = 0) của các mã danh mục
     * trong phạm vi công ty - App\Support\ChemicalStock chưa cộng các lô này vào tồn nên chúng
     * không đóng góp vào ngưỡng; chỉ liệt kê cho người dùng đối chiếu.
     *
     * @return array<int, object>  {code, imported_date, category_id, category_code, department_name, amount, unit_short}
     */
    private static function quarantineLots(array $categoryIds, ?int $companyId): array
    {
        if (! $categoryIds) {
            return [];
        }

        $scopeDepartmentIds = CompanyContext::departmentIds($companyId);

        return DB::table('chemical_imports as ci')
            ->join('chemical_categories as cc', 'ci.category_id', '=', 'cc.id')
            ->leftJoin('deparments as d', 'ci.department_id', '=', 'd.id')
            ->leftJoin('chemical_department_categories as dc', function ($join) {
                $join->on('dc.department_id', '=', 'ci.department_id')
                    ->on('dc.category_id', '=', 'ci.category_id')
                    ->where('dc.status_id', 1);
            })
            ->leftJoin('units as u', 'dc.unit_id', '=', 'u.id')
            ->whereIn('ci.category_id', $categoryIds)
            ->where('ci.status_id', 1)
            ->where('ci.is_checked', 0)
            ->when($scopeDepartmentIds !== null, fn ($query) => $query->whereIn('ci.department_id', $scopeDepartmentIds))
            ->select(
                'ci.code', 'ci.imported_date', 'ci.category_id', 'ci.amount',
                'cc.code as category_code', 'd.name as department_name', 'u.short_name as unit_short'
            )
            ->orderBy('ci.imported_date')
            ->get()
            ->all();
    }

    /* -------------------------------------------------------------------------
     |  Nội bộ
     | ------------------------------------------------------------------------- */

    private static function status(string $table, string $name, string $basis, float $threshold, float $currentKg, float $pendingKg): object
    {
        $totalKg = $currentKg + $pendingKg;
        $ratio = $threshold > 0 ? $totalKg / $threshold : 0.0;
        $num = fn ($v) => rtrim(rtrim(number_format($v, 3, '.', ','), '0'), '.');

        return (object) [
            'table' => $table,
            'name' => $name,
            'basis' => $basis,
            'threshold_kg' => $threshold,
            'current_kg' => $currentKg,
            'pending_kg' => $pendingKg,
            'total_kg' => $totalKg,
            'ratio' => $ratio,
            'level' => ActiveIngredientThreshold::classify($ratio),
            'blocked' => $ratio >= 1.0,
            'message' => '"' . $name . '" (' . $basis . ' PL IV): tồn hiện tại ' . $num($currentKg) . ' kg'
                . ' + đang dự trù chưa hoàn thành ' . $num($pendingKg) . ' kg = ' . $num($totalKg) . ' kg'
                . ' / ngưỡng ' . $num($threshold) . ' kg (' . (int) round($ratio * 100) . '%).',
        ];
    }

    private static function keep(array &$out, array $categoryIds, object $status): void
    {
        foreach ($categoryIds as $categoryId) {
            if (! isset($out[$categoryId]) || $status->ratio > $out[$categoryId]->ratio) {
                $out[$categoryId] = $status;
            }
        }
    }

    private static function sumFor(array $byCategory, array $categoryIds): float
    {
        $sum = 0.0;

        foreach (array_unique($categoryIds) as $categoryId) {
            $sum += $byCategory[$categoryId] ?? 0.0;
        }

        return $sum;
    }

    /** kg chất trong 1 đơn vị danh mục của phòng; null = chưa quy đổi được. */
    private static function kgPerDeptUnit(object $deptUnit, ?float $density): ?float
    {
        if ($deptUnit->unit_group === 'count') {
            $packKg = $deptUnit->pack_weight_kg !== null ? (float) $deptUnit->pack_weight_kg : 0.0;

            return $packKg > 0 ? $packKg : null;
        }

        if ($deptUnit->unit_group === 'volume' && ($density === null || $density <= 0)) {
            return null;
        }

        return UnitConverter::convert(1.0, $deptUnit, (object) ['unit_group' => 'mass', 'factor_to_base' => 1000.0], $density);
    }

    /** Tỉ trọng của mã danh mục; false = không có mã danh mục. */
    private static function density(int $categoryId)
    {
        if (! array_key_exists($categoryId, self::$densityCache)) {
            $cat = DB::table('chemical_categories')->where('id', $categoryId)->select('density')->first();
            self::$densityCache[$categoryId] = $cat ? ($cat->density !== null ? (float) $cat->density : null) : false;
        }

        return self::$densityCache[$categoryId];
    }

    private static function units(): array
    {
        return self::$unitCache ??= DB::table('units')->get()->keyBy('id')->all();
    }

    /** Đọc một khoá từ dòng số lượng dạng object hoặc mảng. */
    private static function field($row, string $key)
    {
        return is_array($row) ? ($row[$key] ?? null) : ($row->{$key} ?? null);
    }
}
