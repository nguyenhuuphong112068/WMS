<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * ĐỐI CHIẾU TỒN TRỮ HỖN HỢP VỚI NGƯỠNG BẢNG B - Phụ lục IV NĐ 24/2026/NĐ-CP.
 *
 * Một tên hoá chất (chem_names) bị xét theo Bảng B khi:
 *   - là HỖN HỢP: gắn TỪ 2 hoạt chất trở lên, VÀ
 *   - trong đó có ÍT NHẤT một hoạt chất thuộc Bảng A (active_ingredients.is_table_a = 1, đã duyệt), VÀ
 *   - được tick ÍT NHẤT một nhóm nguy hại Bảng B (mixture_hazard_categories, đã duyệt).
 *
 * Đối chiếu: TỔNG tồn trữ quy ra kg của hỗn hợp, cộng trên các phòng ban thuộc MỘT công
 * ty (deparments.company_id - truyền $companyId, thường là App\Support\CompanyContext::
 * currentId()), KHÔNG nhân % hàm lượng hoạt chất, so với NGƯỠNG THẤP NHẤT trong các nhóm
 * đã tick (nhóm chặt nhất). Cảnh báo vàng >= 80% ngưỡng, đỏ >= 100% - giống Bảng A.
 *
 * Công thức tồn + quy đổi kg dùng chung App\Support\ChemicalStock + App\Support\UnitConverter.
 * Query Builder thuần, không Eloquent.
 */
class MixtureHazardThreshold
{
    public const LEVEL_OK = 'ok';
    public const LEVEL_WARN = 'warn';
    public const LEVEL_EXCEEDED = 'exceeded';

    public static function warnRatio(): float
    {
        return (float) config('chemical.threshold_iv.warn_ratio', 0.8);
    }

    /**
     * Mã nhóm phân loại danh mục hoá chất bắt buộc phải có thì mới đối chiếu ngưỡng PL IV
     * (N9 = Bảng A, N10 = Bảng B, CAM = hoá chất cấm). Khai ở config/chemical.php.
     *
     * @return array<int, string>
     */
    public static function classificationCodes(): array
    {
        return (array) config('chemical.threshold_iv.classification_codes', ['N9', 'N10', 'CAM']);
    }

    /**
     * Hỗn hợp thuộc diện Bảng B, kèm ngưỡng thấp nhất và các mã danh mục hoá chất của nó.
     *
     * @return array<int, object>  keyed by chem_names_id
     */
    public static function chemNames(): array
    {
        // Hỗn hợp: tên hoá chất gắn từ 2 hoạt chất trở lên
        $mixtureIds = DB::table('chem_name_active_ingredient')
            ->select('chem_names_id')
            ->groupBy('chem_names_id')
            ->havingRaw('COUNT(*) >= 2')
            ->pluck('chem_names_id')
            ->all();

        if (! $mixtureIds) {
            return [];
        }

        $withTableA = DB::table('chem_name_active_ingredient as p')
            ->join('active_ingredients as ai', 'ai.id', '=', 'p.active_ingredients_id')
            ->whereIn('p.chem_names_id', $mixtureIds)
            ->where('ai.is_table_a', 1)
            ->where('ai.status_id', 1)
            ->where('ai.app_status', 'approved')
            ->distinct()
            ->pluck('p.chem_names_id')
            ->all();

        if (! $withTableA) {
            return [];
        }

        $hazardRows = DB::table('chem_name_mixture_hazard_category as p')
            ->join('mixture_hazard_categories as h', 'h.id', '=', 'p.mixture_hazard_categories_id')
            ->whereIn('p.chem_names_id', $withTableA)
            ->where('h.status_id', 1)
            ->where('h.app_status', 'approved')
            ->select('p.chem_names_id', 'h.id', 'h.code', 'h.hazard_group', 'h.ordinal', 'h.name', 'h.threshold_kg', 'h.threshold_basis')
            ->get()
            ->groupBy('chem_names_id');

        if ($hazardRows->isEmpty()) {
            return [];
        }

        $chemNameIds = $hazardRows->keys()->all();
        $names = DB::table('chem_names')->whereIn('id', $chemNameIds)->pluck('name', 'id');

        $categoriesByChem = DB::table('chemical_categories')
            ->whereIn('chem_names_id', $chemNameIds)
            // Chỉ mã danh mục đã phân loại N9 / N10 / CAM mới thuộc diện đối chiếu PL IV
            ->where(function ($query) {
                foreach (self::classificationCodes() as $code) {
                    $query->orWhere('classification', 'like', '%"' . $code . '"%');
                }
            })
            ->select('id', 'code', 'chem_names_id', 'density')
            ->get()
            ->groupBy('chem_names_id');

        $out = [];

        foreach ($hazardRows as $chemId => $rows) {
            $chemId = (int) $chemId;
            $minThreshold = $rows->min('threshold_kg');
            $strictest = $rows->firstWhere('threshold_kg', $minThreshold);
            $cats = $categoriesByChem->get($chemId) ?? collect();

            $out[$chemId] = (object) [
                'chem_names_id' => $chemId,
                'chem_name' => $names[$chemId] ?? ('#' . $chemId),
                'min_threshold_kg' => $minThreshold === null ? null : (float) $minThreshold,
                'strictest_group' => $strictest ? ($strictest->hazard_group . '.' . $strictest->ordinal) : null,
                'hazard_labels' => $rows->map(fn ($r) => $r->hazard_group . '.' . $r->ordinal)->values()->all(),
                'categories' => $cats->map(fn ($c) => (object) [
                    'id' => (int) $c->id,
                    'code' => $c->code,
                    'density' => $c->density,
                ])->values()->all(),
                'category_ids' => $cats->pluck('id')->map(fn ($v) => (int) $v)->all(),
            ];
        }

        return $out;
    }

    /**
     * Tổng tồn trữ quy ra kg (KHÔNG nhân % hàm lượng) của từng hỗn hợp Bảng B.
     *
     * @param  int|null  $departmentId  Có giá trị = chỉ một phòng.
     * @param  int|null  $companyId     Có giá trị = chỉ cộng các phòng ban thuộc công ty này
     *                                  (phạm vi đối chiếu ngưỡng PL IV). null = toàn hệ thống.
     * @param  bool  $withDetail  true = kèm onhand_rows + timeline cho modal xem chi tiết.
     * @return array<int, object>  keyed by chem_names_id
     */
    public static function onHandByChemName(?int $departmentId = null, ?int $companyId = null, bool $withDetail = false): array
    {
        $chemNames = self::chemNames();

        $result = [];
        foreach ($chemNames as $id => $c) {
            $result[$id] = (object) [
                'chem_names_id' => $c->chem_names_id,
                'chem_name' => $c->chem_name,
                'min_threshold_kg' => $c->min_threshold_kg,
                'strictest_group' => $c->strictest_group,
                'hazard_labels' => $c->hazard_labels,
                'category_ids' => $c->category_ids,
                'total_kg' => 0.0,
                'peak_kg' => 0.0,
                'peak_date' => null,
                'by_department' => [],
                'unconvertible' => [],
                'onhand_rows' => [],
                'timeline' => [],
            ];
        }

        if (! $chemNames) {
            return $result;
        }

        $catToChem = [];
        foreach ($chemNames as $c) {
            foreach ($c->categories as $cat) {
                $catToChem[$cat->id] = (object) [
                    'chem_names_id' => $c->chem_names_id,
                    'code' => $cat->code,
                    'density' => $cat->density,
                ];
            }
        }

        $categoryIds = array_keys($catToChem);
        if (! $categoryIds) {
            return $result;
        }

        // Giới hạn tồn trong các phòng ban của công ty đang xét (null = không giới hạn)
        $scopeDepartmentIds = CompanyContext::departmentIds($companyId);

        $onHand = ChemicalStock::onHandByDepartmentCategory($categoryIds, $departmentId, $scopeDepartmentIds);
        $events = ChemicalStock::movementEvents($categoryIds, $departmentId, $scopeDepartmentIds);

        if (empty($onHand) && empty($events)) {
            return $result;
        }

        $deptUnits = ChemicalStock::departmentUnits($categoryIds);
        $deptNames = DB::table('deparments')->pluck('name', 'id');
        $kgUnit = (object) ['unit_group' => 'mass', 'factor_to_base' => 1000.0];

        // Hệ số quy 1 đơn vị (của phòng) -> kg cho từng (phòng ban, mã danh mục) + lý do
        // nếu không quy đổi được. Bảng B tính tồn THÔ nên không nhân % hàm lượng.
        $keyFactor = [];
        $keyReason = [];

        $allKeys = array_unique(array_merge(
            array_keys($onHand),
            array_map(fn ($e) => $e['department_id'] . '-' . $e['category_id'], $events)
        ));

        foreach ($allKeys as $key) {
            [$deptId, $categoryId] = array_map('intval', explode('-', $key));
            $cat = $catToChem[$categoryId] ?? null;

            if (! $cat) {
                $keyFactor[$key] = null;
                $keyReason[$key] = null;
                continue;
            }

            $density = $cat->density !== null ? (float) $cat->density : null;
            $unit = $deptUnits[$key] ?? null;
            $reason = null;
            $factor = null;

            if (! $unit) {
                $reason = 'Phòng "' . ($deptNames[$deptId] ?? ('#' . $deptId)) . '" chưa khai đơn vị tính cho mã này';
            } elseif ($unit->unit_group === 'count') {
                $reason = 'Đơn vị đếm (' . $unit->short_name . ') - cần khai quy cách đóng gói để ra khối lượng';
            } elseif ($unit->unit_group === 'volume' && ($density === null || $density <= 0)) {
                $reason = 'Đơn vị thể tích (' . $unit->short_name . ') nhưng mã danh mục chưa khai tỉ trọng';
            } else {
                $factor = UnitConverter::convert(1.0, $unit, $kgUnit, $density);

                if ($factor === null) {
                    $reason = 'Không quy đổi được đơn vị "' . $unit->short_name . '" sang kg';
                }
            }

            $keyFactor[$key] = $factor;
            $keyReason[$key] = $reason;
        }

        // 1) Tồn hiện tại quy ra kg + phần chưa quy đổi được
        foreach ($onHand as $key => $amount) {
            [$deptId, $categoryId] = array_map('intval', explode('-', $key));

            $cat = $catToChem[$categoryId] ?? null;
            if (! $cat) {
                continue;
            }

            $target = $result[$cat->chem_names_id] ?? null;
            if (! $target) {
                continue;
            }

            $reason = $keyReason[$key] ?? null;
            $deptName = $deptNames[$deptId] ?? ('#' . $deptId);

            if ($reason !== null) {
                $target->unconvertible[] = (object) [
                    'category_code' => $cat->code,
                    'reason' => $reason,
                ];
                continue;
            }

            $kg = (float) $amount * $keyFactor[$key];

            $target->total_kg += $kg;

            if (! isset($target->by_department[$deptId])) {
                $target->by_department[$deptId] = (object) [
                    'department_id' => $deptId,
                    'department_name' => $deptName,
                    'kg' => 0.0,
                ];
            }

            $target->by_department[$deptId]->kg += $kg;
        }

        // 1b) Chi tiết tồn hiện tại theo TỪNG mã xuất nhập (cho modal xem chi tiết)
        if ($withDetail) {
            foreach (ChemicalStock::onHandByLot($categoryIds, $departmentId, $scopeDepartmentIds) as $lot) {
                if (abs($lot->on_hand) < 1e-9) {
                    continue;
                }

                $key = $lot->department_id . '-' . $lot->category_id;
                $factor = $keyFactor[$key] ?? null;
                $cat = $catToChem[$lot->category_id] ?? null;

                if ($factor === null || ! $cat) {
                    continue;
                }

                $target = $result[$cat->chem_names_id] ?? null;
                if (! $target) {
                    continue;
                }

                $target->onhand_rows[] = (object) [
                    'ref' => $lot->code,
                    'date' => $lot->imported_date,
                    'category_code' => $cat->code,
                    'chem_name' => '',
                    'department_name' => $deptNames[$lot->department_id] ?? ('#' . $lot->department_id),
                    'unit_short' => ($deptUnits[$key] ?? null)->short_name ?? '',
                    'imported' => $lot->imported,
                    'balanced' => $lot->balanced,
                    'exported' => $lot->exported,
                    'on_hand_unit' => $lot->on_hand,
                    'on_hand_kg' => $lot->on_hand * $factor,
                ];
            }
        }

        // 2) Dựng lại đường tồn theo thời gian -> mức cao nhất đã từng đạt của từng hỗn hợp
        $running = [];   // chem_names_id => tồn cộng dồn (kg)
        $peak = [];      // chem_names_id => [kg, 'Y-m-d', timelineIndex|null]

        foreach ($events as $event) {
            $key = $event['department_id'] . '-' . $event['category_id'];
            $factor = $keyFactor[$key] ?? null;

            if ($factor === null) {
                continue;
            }

            $cat = $catToChem[$event['category_id']] ?? null;
            if (! $cat) {
                continue;
            }

            $chemId = $cat->chem_names_id;
            $running[$chemId] = ($running[$chemId] ?? 0.0) + $event['delta'] * $factor;

            if ($withDetail) {
                $result[$chemId]->timeline[] = (object) [
                    'date' => $event['date'],
                    'type' => $event['type'],
                    'ref' => $event['ref'],
                    'category_code' => $cat->code,
                    'department_name' => $deptNames[$event['department_id']] ?? ('#' . $event['department_id']),
                    'delta_unit' => $event['delta'],
                    'unit_short' => ($deptUnits[$key] ?? null)->short_name ?? '',
                    'delta_kg' => $event['delta'] * $factor,
                    'running_kg' => $running[$chemId],
                    'is_peak' => false,
                ];
            }

            if (! isset($peak[$chemId]) || $running[$chemId] > $peak[$chemId][0]) {
                $peak[$chemId] = [
                    $running[$chemId],
                    $event['date'],
                    $withDetail ? count($result[$chemId]->timeline) - 1 : null,
                ];
            }
        }

        foreach ($result as $chemId => $row) {
            $row->by_department = collect($row->by_department)->sortByDesc('kg')->values()->all();

            // Đỉnh không thể nhỏ hơn tồn hiện tại và không âm
            $row->peak_kg = max($peak[$chemId][0] ?? 0.0, $row->total_kg, 0.0);
            $row->peak_date = $peak[$chemId][1] ?? null;

            $peakIndex = $peak[$chemId][2] ?? null;
            if ($peakIndex !== null && isset($row->timeline[$peakIndex])) {
                $row->timeline[$peakIndex]->is_peak = true;
            }
        }

        return $result;
    }

    /**
     * Phân loại một tỉ lệ tồn/ngưỡng thành mức đánh giá.
     */
    public static function classify(float $ratio): string
    {
        return $ratio >= 1.0
            ? self::LEVEL_EXCEEDED
            : ($ratio >= self::warnRatio() ? self::LEVEL_WARN : self::LEVEL_OK);
    }

    /**
     * Gắn tỉ lệ + mức đánh giá vào một dòng tồn:
     *   - ratio / current_level : theo TỒN THÔ HIỆN TẠI (total_kg).
     *   - peak_ratio / level    : theo TỒN THÔ CAO NHẤT ĐÃ TỪNG ĐẠT (peak_kg) - mức CHÍNH
     *     hiển thị ở cột Trạng Thái, đúng tinh thần "lớn nhất tại một thời điểm" của PL IV.
     */
    public static function applyRatios(object $row, float $threshold): void
    {
        $row->ratio = $row->total_kg / $threshold;
        $row->current_level = self::classify($row->ratio);

        $row->peak_ratio = $row->peak_kg / $threshold;
        $row->level = self::classify($row->peak_ratio);
    }

    /**
     * Như onHandByChemName() nhưng chỉ giữ hỗn hợp CÓ ngưỡng, kèm tỉ lệ và mức đánh giá.
     *
     * @param  int|null  $companyId  Giới hạn phạm vi cộng tồn trong một công ty. null = toàn hệ thống.
     * @param  bool  $withDetail  true = kèm onhand_rows + timeline (xem onHandByChemName()).
     * @return array<int, object>  thêm khoá: ratio, peak_ratio, level, current_level, has_unconvertible.
     *                             level = theo đỉnh; current_level = theo tồn hiện tại.
     */
    public static function evaluate(?int $departmentId = null, ?int $companyId = null, bool $withDetail = false): array
    {
        $out = [];

        foreach (self::onHandByChemName($departmentId, $companyId, $withDetail) as $id => $row) {
            if ($row->min_threshold_kg === null || $row->min_threshold_kg <= 0) {
                continue;
            }

            self::applyRatios($row, (float) $row->min_threshold_kg);
            $row->has_unconvertible = ! empty($row->unconvertible);

            $out[$id] = $row;
        }

        return $out;
    }

    /**
     * Đánh giá gắn theo từng mã danh mục hoá chất, cho bảng Danh Mục Hoá Chất.
     *
     * @param  int|null  $companyId  Cộng tồn trong phạm vi công ty này. null = toàn hệ thống.
     * @return array<int, object>  keyed by chemical_categories.id
     */
    public static function forCategories(?int $companyId = null): array
    {
        $out = [];

        foreach (self::evaluate(null, $companyId) as $row) {
            foreach ($row->category_ids as $categoryId) {
                $out[$categoryId] = $row;
            }
        }

        return $out;
    }

    /**
     * Chi tiết đối chiếu Bảng B của MỘT mã danh mục hoá chất (hỗn hợp chứa mã đó),
     * kèm onhand_rows + timeline cho modal "xem chi tiết".
     *
     * @param  int|null  $companyId  Phạm vi cộng tồn (công ty). null = toàn hệ thống.
     * @return object|null
     */
    public static function detailForCategory(int $categoryId, ?int $companyId = null): ?object
    {
        foreach (self::evaluate(null, $companyId, true) as $row) {
            if (in_array($categoryId, $row->category_ids, true)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Cộng các dòng số lượng ({amount, unit_id}) của một mặt hàng dự trù, quy về kg THÔ
     * (KHÔNG nhân % hàm lượng) theo tỉ trọng của mã danh mục.
     *
     * @return array{kg: float, unconvertible: bool}
     */
    public static function sumEstimateKg(int $categoryId, $amountRows): array
    {
        $cat = DB::table('chemical_categories')
            ->where('id', $categoryId)
            ->select('density')
            ->first();

        $rows = collect($amountRows);

        if (! $cat) {
            return ['kg' => 0.0, 'unconvertible' => $rows->isNotEmpty()];
        }

        $unitIds = $rows->map(fn ($r) => (int) ($r->unit_id ?? ($r['unit_id'] ?? 0)))->filter()->unique()->all();
        $units = $unitIds ? DB::table('units')->whereIn('id', $unitIds)->get()->keyBy('id') : collect();

        $density = $cat->density !== null ? (float) $cat->density : null;
        $kgUnit = (object) ['unit_group' => 'mass', 'factor_to_base' => 1000.0];

        $totalKg = 0.0;
        $unconvertible = false;

        foreach ($rows as $row) {
            $qty = (float) ($row->amount ?? ($row['amount'] ?? 0));
            $unitId = (int) ($row->unit_id ?? ($row['unit_id'] ?? 0));
            $unit = $unitId ? ($units[$unitId] ?? null) : null;

            if ($qty <= 0) {
                continue;
            }

            if (! $unit || $unit->unit_group === 'count'
                || ($unit->unit_group === 'volume' && ($density === null || $density <= 0))) {
                $unconvertible = true;

                continue;
            }

            $base = UnitConverter::convert($qty, $unit, $kgUnit, $density);

            if ($base === null) {
                $unconvertible = true;

                continue;
            }

            $totalKg += $base;
        }

        return ['kg' => $totalKg, 'unconvertible' => $unconvertible];
    }

    /**
     * "Nếu dự trù thêm addKg (kg thô) cho mã danh mục này thì tổng tồn trữ thô toàn công ty
     * của hỗn hợp Bảng B sẽ tới đâu so với ngưỡng thấp nhất."
     *
     * Trả null nếu mã danh mục không thuộc diện Bảng B (không N9/N10/CAM, hoặc hỗn hợp chưa
     * đủ điều kiện / chưa có ngưỡng).
     *
     * @return object|null {chem_name, strictest_group, threshold_kg, current_kg, add_kg,
     *                       projected_kg, current_ratio, add_ratio, projected_ratio, level}
     */
    public static function projectedForCategory(int $categoryId, float $addKg, ?int $companyId = null): ?object
    {
        $eval = self::forCategories($companyId)[$categoryId] ?? null;

        if (! $eval || $eval->min_threshold_kg === null || $eval->min_threshold_kg <= 0) {
            return null;
        }

        $threshold = (float) $eval->min_threshold_kg;
        $addKg = max($addKg, 0.0);
        $projectedKg = $eval->total_kg + $addKg;

        return (object) [
            'chem_name' => $eval->chem_name,
            'strictest_group' => $eval->strictest_group,
            'threshold_kg' => $threshold,
            'current_kg' => $eval->total_kg,
            'add_kg' => $addKg,
            'projected_kg' => $projectedKg,
            'current_ratio' => $eval->ratio,
            'add_ratio' => $addKg / $threshold,
            'projected_ratio' => $projectedKg / $threshold,
            'level' => self::classify($projectedKg / $threshold),
        ];
    }
}
