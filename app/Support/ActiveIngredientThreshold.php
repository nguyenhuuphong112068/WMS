<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * ĐỐI CHIẾU TỒN TRỮ VỚI NGƯỠNG PHỤ LỤC IV NĐ 24/2026/NĐ-CP
 *
 * "Ngưỡng khối lượng hoá chất tồn trữ lớn nhất tại một thời điểm (kg)" khai ở dữ liệu gốc
 * active_ingredients.threshold_kg. Một hoạt chất có thể nằm trong nhiều mã danh mục hoá
 * chất (khác nhà sản xuất, khác nồng độ) nên phải cộng tồn của TẤT CẢ các mã đó, quy về
 * kg, rồi so với ngưỡng.
 *
 * PHẠM VI CỘNG TỒN: gói trong MỘT công ty. Phần mềm chạy cho nhiều công ty, mỗi công ty
 * có bộ phòng ban riêng (deparments.company_id); ngưỡng chỉ đối chiếu trên tồn của các
 * phòng ban thuộc cùng công ty. Truyền $companyId (thường là App\Support\CompanyContext::
 * currentId()) vào các hàm bên dưới; null = không giới hạn (CLI / seed).
 *
 * Quy tắc tính (khớp App\Http\Controllers\Pages\Inventory\ChemicalInventoryController):
 *   tồn 1 mã xuất nhập = chemical_imports.amount
 *                      + SUM(chemical_balancings.balancing_amount)   -- status_id = 1
 *                      - SUM(chemical_exports.amount)                 -- status_id = 1
 *   (chỉ tính chemical_imports.status_id = 1)
 *
 * Số lượng lưu theo đơn vị của phòng ban (chemical_department_categories.unit_id). Quy về
 * kg bằng App\Support\UnitConverter với tỉ trọng chemical_categories.density, rồi nhân
 * hàm lượng hoạt chất chemical_categories.ai_content_percent (mặc định 100%).
 *
 * Đơn vị đếm (chai/thùng…) hoặc thiếu tỉ trọng => KHÔNG quy đổi được, gom vào phần
 * "cần kiểm tra thủ công" chứ không bỏ qua âm thầm.
 *
 * Query Builder thuần, không Eloquent.
 */
class ActiveIngredientThreshold
{
    /** Mức đánh giá. */
    public const LEVEL_OK = 'ok';
    public const LEVEL_WARN = 'warn';        // Chạm ngưỡng cảnh báo (mặc định >= 80%)
    public const LEVEL_EXCEEDED = 'exceeded'; // Vượt ngưỡng (>= 100%)

    /** Tỉ lệ so với ngưỡng để bắt đầu cảnh báo vàng. */
    public static function warnRatio(): float
    {
        return (float) config('chemical.threshold_iv.warn_ratio', 0.8);
    }

    /**
     * Hoạt chất (đã duyệt, đang hoạt động) đang được ít nhất một mã danh mục hoá chất
     * tham chiếu, kèm các mã danh mục thuộc hoạt chất đó.
     *
     * @return array<int, object>  keyed by active_ingredient_id
     */
    public static function ingredients(): array
    {
        $rows = self::categoryRows();

        $out = [];

        foreach ($rows as $row) {
            if (! isset($out[$row->ai_id])) {
                $out[$row->ai_id] = (object) [
                    'ai_id' => (int) $row->ai_id,
                    'ai_code' => $row->ai_code,
                    'ai_name' => $row->ai_name,
                    'cas_no' => $row->cas_no,
                    'threshold_kg' => $row->threshold_kg === null ? null : (float) $row->threshold_kg,
                    'legal_ref' => $row->legal_ref,
                    'category_ids' => [],
                ];
            }

            $out[$row->ai_id]->category_ids[] = (int) $row->category_id;
        }

        return $out;
    }

    /**
     * Tồn trữ quy ra kg của từng hoạt chất.
     *
     * @param  int|null  $departmentId  Có giá trị = chỉ một phòng.
     * @param  int|null  $companyId     Có giá trị = chỉ cộng các phòng ban thuộc công ty này
     *                                  (phạm vi đối chiếu ngưỡng PL IV). null = toàn hệ thống.
     * @param  bool  $withDetail  true = kèm onhand_rows (chi tiết tồn hiện tại theo mã × phòng)
     *                            và timeline (diễn biến từng chứng từ tạo nên đỉnh) cho modal xem chi tiết.
     * @return array<int, object>  keyed by active_ingredient_id, mỗi phần tử:
     *   {ai_id, ai_code, ai_name, cas_no, threshold_kg, legal_ref,
     *    total_kg, peak_kg, peak_date,
     *    by_department: [ {department_id, department_name, kg} ],
     *    unconvertible: [ {category_code, chem_name, reason} ],
     *    onhand_rows: [ {category_code, chem_name, department_name, unit_short, on_hand_unit, on_hand_kg} ],
     *    timeline: [ {date, type, ref, category_code, department_name, delta_unit, unit_short, delta_kg, running_kg, is_peak} ]}
     *
     *  peak_kg  = mức tồn trữ quy ra kg CAO NHẤT đã từng đạt (dựng lại từ chứng từ, theo ngày).
     *  peak_date = ngày đạt đỉnh đó (Y-m-d) hoặc null nếu chưa có chứng từ quy đổi được.
     */
    public static function onHandByIngredient(?int $departmentId = null, ?int $companyId = null, bool $withDetail = false): array
    {
        // Một mã danh mục có thể gắn nhiều hoạt chất Bảng A -> gom theo category_id
        $rowsByCategory = collect(self::categoryRows())->groupBy('category_id');

        // Khởi tạo mọi hoạt chất được tham chiếu, kể cả khi chưa có tồn
        $result = [];
        foreach (self::ingredients() as $ing) {
            $result[$ing->ai_id] = (object) [
                'ai_id' => $ing->ai_id,
                'ai_code' => $ing->ai_code,
                'ai_name' => $ing->ai_name,
                'cas_no' => $ing->cas_no,
                'threshold_kg' => $ing->threshold_kg,
                'legal_ref' => $ing->legal_ref,
                'total_kg' => 0.0,
                'peak_kg' => 0.0,
                'peak_date' => null,
                'by_department' => [],
                'unconvertible' => [],
                'onhand_rows' => [],
                'timeline' => [],
            ];
        }

        if ($rowsByCategory->isEmpty()) {
            return $result;
        }

        $categoryIds = $rowsByCategory->keys()->all();

        // Giới hạn tồn trong các phòng ban của công ty đang xét (null = không giới hạn)
        $scopeDepartmentIds = CompanyContext::departmentIds($companyId);

        // Tồn hiện tại theo (phòng ban, mã danh mục) + chuỗi sự kiện để dựng lại đỉnh
        $onHand = ChemicalStock::onHandByDepartmentCategory($categoryIds, $departmentId, $scopeDepartmentIds);
        $events = ChemicalStock::movementEvents($categoryIds, $departmentId, $scopeDepartmentIds);

        if (empty($onHand) && empty($events)) {
            return $result;
        }

        $deptUnits = ChemicalStock::departmentUnits($categoryIds);
        $deptNames = DB::table('deparments')->pluck('name', 'id');
        $kgUnit = (object) ['unit_group' => 'mass', 'factor_to_base' => 1000.0];

        // Hệ số quy 1 đơn vị (của phòng) -> kg chất gốc cho từng (phòng ban, mã danh mục),
        // kèm lý do nếu không quy đổi được. % hàm lượng hoạt chất nhân sau.
        $keyFactor = [];
        $keyReason = [];

        $allKeys = array_unique(array_merge(
            array_keys($onHand),
            array_map(fn ($e) => $e['department_id'] . '-' . $e['category_id'], $events)
        ));

        foreach ($allKeys as $key) {
            [$deptId, $categoryId] = array_map('intval', explode('-', $key));
            $catRows = $rowsByCategory->get($categoryId);

            if (! $catRows || $catRows->isEmpty()) {
                $keyFactor[$key] = null;
                $keyReason[$key] = null;
                continue;
            }

            // density nằm ở chemical_categories nên giống nhau cho mọi hoạt chất của cùng mã
            $density = $catRows->first()->density !== null ? (float) $catRows->first()->density : null;
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

        // 1) Tồn hiện tại quy ra kg cho từng hoạt chất + phần chưa quy đổi được
        foreach ($onHand as $key => $amount) {
            [$deptId, $categoryId] = array_map('intval', explode('-', $key));
            $catRows = $rowsByCategory->get($categoryId);

            if (! $catRows || $catRows->isEmpty()) {
                continue;
            }

            $percent = $catRows->first()->ai_content_percent !== null
                ? (float) $catRows->first()->ai_content_percent
                : 100.0;
            $factor = $keyFactor[$key] ?? null;
            $reason = $keyReason[$key] ?? null;
            $deptName = $deptNames[$deptId] ?? ('#' . $deptId);

            foreach ($catRows as $cat) {
                $target = $result[$cat->ai_id];

                if ($reason !== null) {
                    $target->unconvertible[] = (object) [
                        'category_code' => $cat->category_code,
                        'chem_name' => $cat->chem_name,
                        'reason' => $reason,
                    ];
                    continue;
                }

                $kg = (float) $amount * $factor * $percent / 100;

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
        }

        // 1b) Chi tiết tồn hiện tại theo TỪNG mã xuất nhập (cho modal xem chi tiết)
        if ($withDetail) {
            foreach (ChemicalStock::onHandByLot($categoryIds, $departmentId, $scopeDepartmentIds) as $lot) {
                if (abs($lot->on_hand) < 1e-9) {
                    continue; // phiếu đã dùng hết -> không đóng góp vào tồn hiện tại
                }

                $key = $lot->department_id . '-' . $lot->category_id;
                $factor = $keyFactor[$key] ?? null;

                if ($factor === null) {
                    continue; // đã liệt kê ở phần "chưa quy đổi được"
                }

                $catRows = $rowsByCategory->get($lot->category_id);
                if (! $catRows || $catRows->isEmpty()) {
                    continue;
                }

                $percent = $catRows->first()->ai_content_percent !== null
                    ? (float) $catRows->first()->ai_content_percent
                    : 100.0;
                $unitShort = ($deptUnits[$key] ?? null)->short_name ?? '';
                $deptName = $deptNames[$lot->department_id] ?? ('#' . $lot->department_id);

                foreach ($catRows as $cat) {
                    $result[$cat->ai_id]->onhand_rows[] = (object) [
                        'ref' => $lot->code,
                        'date' => $lot->imported_date,
                        'category_code' => $cat->category_code,
                        'chem_name' => $cat->chem_name,
                        'department_name' => $deptName,
                        'unit_short' => $unitShort,
                        'imported' => $lot->imported,
                        'balanced' => $lot->balanced,
                        'exported' => $lot->exported,
                        'on_hand_unit' => $lot->on_hand,
                        'on_hand_kg' => $lot->on_hand * $factor * $percent / 100,
                    ];
                }
            }
        }

        // 2) Dựng lại đường tồn theo thời gian -> mức cao nhất đã từng đạt của từng hoạt chất
        $running = [];   // ai_id => tồn cộng dồn (kg)
        $peak = [];      // ai_id => [kg, 'Y-m-d', timelineIndex|null]

        foreach ($events as $event) {
            $key = $event['department_id'] . '-' . $event['category_id'];
            $factor = $keyFactor[$key] ?? null;

            if ($factor === null) {
                continue; // lô chưa quy đổi được - đã ghi ở phần unconvertible
            }

            $catRows = $rowsByCategory->get($event['category_id']);
            if (! $catRows || $catRows->isEmpty()) {
                continue;
            }

            $percent = $catRows->first()->ai_content_percent !== null
                ? (float) $catRows->first()->ai_content_percent
                : 100.0;
            $kgDelta = $event['delta'] * $factor * $percent / 100;
            $unitShort = ($deptUnits[$key] ?? null)->short_name ?? '';
            $deptName = $deptNames[$event['department_id']] ?? ('#' . $event['department_id']);

            foreach ($catRows as $cat) {
                $aiId = $cat->ai_id;
                $running[$aiId] = ($running[$aiId] ?? 0.0) + $kgDelta;

                if ($withDetail) {
                    $result[$aiId]->timeline[] = (object) [
                        'date' => $event['date'],
                        'type' => $event['type'],
                        'ref' => $event['ref'],
                        'category_code' => $cat->category_code,
                        'department_name' => $deptName,
                        'delta_unit' => $event['delta'],
                        'unit_short' => $unitShort,
                        'delta_kg' => $kgDelta,
                        'running_kg' => $running[$aiId],
                        'is_peak' => false,
                    ];
                }

                if (! isset($peak[$aiId]) || $running[$aiId] > $peak[$aiId][0]) {
                    $peak[$aiId] = [
                        $running[$aiId],
                        $event['date'],
                        $withDetail ? count($result[$aiId]->timeline) - 1 : null,
                    ];
                }
            }
        }

        // by_department: object map -> mảng tuần tự, sắp theo kg giảm dần; chốt đỉnh
        foreach ($result as $aiId => $row) {
            $row->by_department = collect($row->by_department)->sortByDesc('kg')->values()->all();

            // Đỉnh không thể nhỏ hơn tồn hiện tại và không âm
            $row->peak_kg = max($peak[$aiId][0] ?? 0.0, $row->total_kg, 0.0);
            $row->peak_date = $peak[$aiId][1] ?? null;

            // Đánh dấu đúng dòng chứng từ làm tồn chạm đỉnh (nếu đỉnh đến từ chuỗi sự kiện)
            $peakIndex = $peak[$aiId][2] ?? null;
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
     * Gắn tỉ lệ + mức đánh giá vào một dòng tồn đã có total_kg + peak_kg:
     *   - ratio / current_level : theo TỒN HIỆN TẠI (total_kg). Dùng cho cảnh báo lúc nhập
     *     hoá chất (chặn theo tồn sau khi nhập).
     *   - peak_ratio / level    : theo TỒN CAO NHẤT ĐÃ TỪNG ĐẠT (peak_kg) - đúng tinh thần
     *     "khối lượng tồn trữ lớn nhất tại một thời điểm" của Phụ lục IV. Đây là mức CHÍNH
     *     hiển thị ở cột Trạng Thái và đếm tóm tắt (đã vượt ngưỡng thì phải xây dựng Kế
     *     hoạch phòng ngừa dù nay đã xuất bớt).
     */
    public static function applyRatios(object $row, float $threshold): void
    {
        $row->ratio = $row->total_kg / $threshold;
        $row->current_level = self::classify($row->ratio);

        $row->peak_ratio = $row->peak_kg / $threshold;
        $row->level = self::classify($row->peak_ratio);
    }

    /**
     * Như onHandByIngredient() nhưng chỉ giữ hoạt chất CÓ ngưỡng, kèm tỉ lệ và mức đánh giá.
     *
     * @param  int|null  $companyId  Giới hạn phạm vi cộng tồn trong một công ty. null = toàn hệ thống.
     * @param  bool  $withDetail  true = kèm onhand_rows + timeline (xem onHandByIngredient()).
     * @return array<int, object>  thêm khoá: ratio, peak_ratio (float), level, current_level (LEVEL_*),
     *                             has_unconvertible (bool). level = theo đỉnh; current_level = theo tồn hiện tại.
     */
    public static function evaluate(?int $departmentId = null, ?int $companyId = null, bool $withDetail = false): array
    {
        $out = [];

        foreach (self::onHandByIngredient($departmentId, $companyId, $withDetail) as $aiId => $row) {
            if ($row->threshold_kg === null || $row->threshold_kg <= 0) {
                continue;
            }

            self::applyRatios($row, (float) $row->threshold_kg);
            $row->has_unconvertible = ! empty($row->unconvertible);

            $out[$aiId] = $row;
        }

        return $out;
    }

    /**
     * Đánh giá gắn theo từng mã danh mục hoá chất, để bảng Danh Mục Hoá Chất hiện cột
     * cảnh báo ngưỡng.
     *
     * @param  int|null  $companyId  Cộng tồn trong phạm vi công ty này. null = toàn hệ thống.
     * @return array<int, object>  keyed by chemical_categories.id
     */
    public static function forCategories(?int $companyId = null): array
    {
        $evaluations = self::evaluate(null, $companyId);
        $out = [];

        foreach (self::ingredients() as $ing) {
            if (! isset($evaluations[$ing->ai_id])) {
                continue;
            }

            foreach ($ing->category_ids as $categoryId) {
                $out[$categoryId] = $evaluations[$ing->ai_id];
            }
        }

        return $out;
    }

    /**
     * Chi tiết đối chiếu ngưỡng của MỘT mã danh mục hoá chất: tất cả hoạt chất Bảng A mà
     * mã đó tham chiếu, kèm onhand_rows + timeline để modal "xem chi tiết" dựng bảng.
     *
     * @param  int|null  $companyId  Phạm vi cộng tồn (công ty). null = toàn hệ thống.
     * @return array<int, object>  0..n hoạt chất, mỗi phần tử như evaluate() + onhand_rows + timeline.
     */
    public static function detailForCategory(int $categoryId, ?int $companyId = null): array
    {
        $evaluations = self::evaluate(null, $companyId, true);
        $out = [];

        foreach (self::ingredients() as $ing) {
            if (isset($evaluations[$ing->ai_id]) && in_array($categoryId, $ing->category_ids, true)) {
                $out[] = $evaluations[$ing->ai_id];
            }
        }

        return $out;
    }

    /**
     * Cộng các dòng số lượng (mỗi dòng {amount, unit_id}) của một mặt hàng dự trù, quy về
     * kg HOẠT CHẤT gốc (× % hàm lượng) theo tỉ trọng của mã danh mục.
     *
     * @param  iterable  $amountRows  các object/array có khoá amount + unit_id
     * @return array{kg: float, unconvertible: bool}  unconvertible = có dòng đơn vị đếm / thiếu tỉ trọng
     */
    public static function sumEstimateKg(int $categoryId, $amountRows): array
    {
        $cat = DB::table('chemical_categories')
            ->where('id', $categoryId)
            ->select('density', 'ai_content_percent')
            ->first();

        $rows = collect($amountRows);

        if (! $cat) {
            return ['kg' => 0.0, 'unconvertible' => $rows->isNotEmpty()];
        }

        $unitIds = $rows->map(fn ($r) => (int) ($r->unit_id ?? ($r['unit_id'] ?? 0)))->filter()->unique()->all();
        $units = $unitIds ? DB::table('units')->whereIn('id', $unitIds)->get()->keyBy('id') : collect();

        $density = $cat->density !== null ? (float) $cat->density : null;
        $percent = $cat->ai_content_percent !== null ? (float) $cat->ai_content_percent : 100.0;
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

            $totalKg += $base * $percent / 100;
        }

        return ['kg' => $totalKg, 'unconvertible' => $unconvertible];
    }

    /**
     * "Nếu dự trù thêm addKg (kg hoạt chất gốc) cho mã danh mục này thì tổng tồn trữ toàn
     * công ty của hoạt chất Bảng A đứng sau nó sẽ tới đâu so với ngưỡng PL IV."
     *
     * Trả null nếu mã danh mục không thuộc diện đối chiếu (không N9/N10/CAM, hoặc chưa gắn
     * hoạt chất Bảng A đã duyệt có ngưỡng).
     *
     * @return object|null {ai_name, ai_code, threshold_kg, current_kg, add_kg, projected_kg,
     *                       current_ratio, add_ratio, projected_ratio, level}
     */
    public static function projectedForCategory(int $categoryId, float $addKg, ?int $companyId = null): ?object
    {
        $eval = self::forCategories($companyId)[$categoryId] ?? null;

        if (! $eval || $eval->threshold_kg === null || $eval->threshold_kg <= 0) {
            return null;
        }

        $threshold = (float) $eval->threshold_kg;
        $addKg = max($addKg, 0.0);
        $projectedKg = $eval->total_kg + $addKg;

        return (object) [
            'ai_name' => $eval->ai_name,
            'ai_code' => $eval->ai_code,
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

    /* -------------------------------------------------------------------------
     |  Nội bộ
     | ------------------------------------------------------------------------- */

    /**
     * Mã danh mục hoá chất có gắn hoạt chất thuộc NHÓM 9 (Phụ lục IV Bảng A) đã duyệt +
     * đang hoạt động. Diện đối chiếu ngưỡng PL IV nay SUY tự động từ phân loại của hoạt
     * chất - không còn điều kiện tick N9/N10/CAM trên mã danh mục.
     *
     * chem_names gắn NHIỀU hoạt chất (bảng pivot chem_name_active_ingredient) nên một
     * mã danh mục có thể sinh ra nhiều dòng - mỗi hoạt chất nhóm 9 một dòng. Tồn của
     * mã đó được quy cho TỪNG hoạt chất nhóm 9 của hỗn hợp (nhân cùng ai_content_percent
     * của mã danh mục); hỗn hợp có 2+ chất nhóm 9 thì tính theo hướng thận trọng.
     *
     * @return array<int, object>
     */
    private static function categoryRows(): array
    {
        return DB::table('chemical_categories as cc')
            ->join('chem_names as cn', 'cc.chem_names_id', '=', 'cn.id')
            ->join('chem_name_active_ingredient as cnai', 'cnai.chem_names_id', '=', 'cn.id')
            ->join('active_ingredients as ai', 'cnai.active_ingredients_id', '=', 'ai.id')
            // Hoạt chất thuộc nhóm 9 = có dòng phân loại Phụ lục IV / bảng A
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('active_ingredient_classifications as aic')
                    ->whereColumn('aic.active_ingredients_id', 'ai.id')
                    ->where('aic.appendix', 'IV')
                    ->where('aic.table_ref', 'A');
            })
            ->where('ai.status_id', 1)
            ->where('ai.app_status', 'approved')
            ->select(
                'cc.id as category_id',
                'cc.code as category_code',
                'cc.density',
                'cc.ai_content_percent',
                'cn.name as chem_name',
                'ai.id as ai_id',
                'ai.code as ai_code',
                'ai.name as ai_name',
                'ai.cas_no',
                'ai.threshold_kg',
                'ai.legal_ref'
            )
            ->get()
            ->all();
    }
}
