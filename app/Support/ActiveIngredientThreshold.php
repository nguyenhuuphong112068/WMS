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
 * hàm lượng hoạt chất: ưu tiên chemical_categories.ai_content_percent (khai tay ở mã danh
 * mục, ví dụ lô có kết quả COA khác nhãn); nếu mã danh mục không khai thì lấy % thành phần
 * (chem_name_active_ingredient.content_percent, khai trên màn Tên Hoá Chất); không có gì
 * thì mặc định 100%. CHỈ áp dụng cho hoá chất là hoạt chất ĐƠN (chính nó là hoạt chất Bảng
 * A, có thể pha loãng) - xem categoryRows(). Hỗn hợp nhiều hoạt chất (ví dụ hỗn hợp chứa
 * một hoạt chất Bảng A bên trong) KHÔNG cộng vào đây; hỗn hợp đó đứng riêng ở Nhóm 10
 * (Bảng B, tồn thô - App\Support\MixtureHazardThreshold), nhập kho hỗn hợp không tách %
 * hoạt chất thành phần để cộng thêm vào ngưỡng Bảng A.
 *
 * MỤC GỘP (active_ingredients.parent_id - xem migration 2026_09_07_110000):
 * Nghị định có dòng khai theo NHÓM chất ("Thủy ngân và các hợp chất của thủy ngân" - 1 kg,
 * "Các hợp chất xyanua" - 5.000 kg...) trong khi kho nhập chất cụ thể có số CAS riêng
 * (HgCl₂, HgI₂...). Chất cụ thể khai thành một hoạt chất riêng rồi trỏ parent_id về mục
 * gộp; tồn của nó KHÔNG đứng riêng mà cộng vào ngưỡng của mục gộp ("chủ ngưỡng"). Với phần
 * cộng gộp này:
 *   - Tính trên KHỐI LƯỢNG HỢP CHẤT THÔ - KHÔNG nhân % hàm lượng (ngưỡng 1 kg của mục gộp
 *     là 1 kg hợp chất thuỷ ngân, không phải 1 kg Hg nguyên tố). Đây là cách hiểu chặt hơn,
 *     giống cách Bảng B cộng tồn thô ở App\Support\MixtureHazardThreshold.
 *   - Nhân thêm active_ingredients.equiv_factor của chất thành viên (mặc định 1). Chỉ đổi
 *     hệ số này khi muốn quy về phần nguyên tố (ví dụ 0,738 cho Hg trong HgCl₂).
 * Chất vừa có ngưỡng RIÊNG vừa thuộc mục gộp thì được cộng ở CẢ HAI chỗ (đúng nghị định:
 * nó vừa bị liệt kê đích danh vừa nằm trong nhóm).
 *
 * Đơn vị đếm (chai/thùng…): quy ra kg theo "Khối lượng quy đổi" phòng khai ở tab Hoá Chất
 * Của Phòng (chemical_department_categories.pack_weight_kg). Chưa khai con số này, hoặc đơn
 * vị thể tích mà thiếu tỉ trọng => KHÔNG quy đổi được, gom vào phần "cần kiểm tra thủ công"
 * chứ không bỏ qua âm thầm.
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
     * Hoạt chất CHỊU NGƯỠNG (đã duyệt, đang hoạt động) đang được ít nhất một mã danh mục
     * hoá chất tham chiếu, kèm các mã danh mục thuộc hoạt chất đó. Với mục gộp, đây là
     * dòng mục gộp - các chất thành viên đóng góp vào nó (xem members).
     *
     * @return array<int, object>  keyed by active_ingredient_id của CHỦ NGƯỠNG
     */
    public static function ingredients(): array
    {
        $rows = self::categoryRows();

        $out = [];

        foreach ($rows as $row) {
            $ownerId = (int) $row->owner_ai_id;

            if (! isset($out[$ownerId])) {
                $out[$ownerId] = (object) [
                    'ai_id' => $ownerId,
                    'ai_code' => $row->owner_ai_code,
                    'ai_name' => $row->owner_ai_name,
                    'cas_no' => $row->owner_cas_no,
                    'threshold_kg' => $row->owner_threshold_kg === null ? null : (float) $row->owner_threshold_kg,
                    'legal_ref' => $row->owner_legal_ref,
                    'category_ids' => [],
                    // Tên các hoạt chất thành viên cộng vào mục gộp này (rỗng nếu không phải mục gộp)
                    'members' => [],
                ];
            }

            $out[$ownerId]->category_ids[] = (int) $row->category_id;

            if ($row->is_rolled_up) {
                $out[$ownerId]->members[(int) $row->ai_id] = $row->member_ai_name;
            }
        }

        foreach ($out as $ing) {
            $ing->category_ids = array_values(array_unique($ing->category_ids));
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
     * @return array<int, object>  keyed by active_ingredient_id của CHỦ NGƯỠNG (mục gộp nếu
     *   hoạt chất là thành viên của mục gộp), mỗi phần tử:
     *   {ai_id, ai_code, ai_name, cas_no, threshold_kg, legal_ref, members,
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
                // Hoạt chất thành viên cộng vào mục gộp này (rỗng = hoạt chất đứng riêng)
                'members' => array_values($ing->members),
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
                // Đơn vị đếm / bao bì: quy ra kg theo "Khối lượng quy đổi" phòng khai cho
                // mã này (chemical_department_categories.pack_weight_kg = kg hoá chất trong
                // 1 đơn vị đếm). Chưa khai thì vẫn gom vào phần "chưa quy đổi được".
                $packKg = isset($unit->pack_weight_kg) && $unit->pack_weight_kg !== null
                    ? (float) $unit->pack_weight_kg
                    : null;

                if ($packKg !== null && $packKg > 0) {
                    $factor = $packKg;
                } else {
                    $reason = 'Đơn vị đếm (' . $unit->short_name . ') - khai "Khối lượng quy đổi" ở tab Hoá Chất Của Phòng để quy ra kg';
                }
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

            $factor = $keyFactor[$key] ?? null;
            $reason = $keyReason[$key] ?? null;
            $deptName = $deptNames[$deptId] ?? ('#' . $deptId);

            foreach ($catRows as $cat) {
                $target = $result[$cat->owner_ai_id];

                if ($reason !== null) {
                    $target->unconvertible[] = (object) [
                        'category_code' => $cat->category_code,
                        'chem_name' => $cat->chem_name,
                        'reason' => $reason,
                    ];
                    continue;
                }

                $kg = (float) $amount * $factor * self::kgMultiplier($cat);

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

                $unitShort = ($deptUnits[$key] ?? null)->short_name ?? '';
                $deptName = $deptNames[$lot->department_id] ?? ('#' . $lot->department_id);

                foreach ($catRows as $cat) {
                    $result[$cat->owner_ai_id]->onhand_rows[] = (object) [
                        'ref' => $lot->code,
                        'date' => $lot->imported_date,
                        'category_code' => $cat->category_code,
                        'chem_name' => $cat->chem_name,
                        // Hoạt chất thành viên đóng góp (chỉ có khi cộng vào mục gộp)
                        'member_name' => $cat->member_ai_name,
                        'department_name' => $deptName,
                        'unit_short' => $unitShort,
                        'imported' => $lot->imported,
                        'balanced' => $lot->balanced,
                        'exported' => $lot->exported,
                        'on_hand_unit' => $lot->on_hand,
                        'on_hand_kg' => $lot->on_hand * $factor * self::kgMultiplier($cat),
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

            $unitShort = ($deptUnits[$key] ?? null)->short_name ?? '';
            $deptName = $deptNames[$event['department_id']] ?? ('#' . $event['department_id']);

            foreach ($catRows as $cat) {
                $kgDelta = $event['delta'] * $factor * self::kgMultiplier($cat);
                $aiId = $cat->owner_ai_id;
                $running[$aiId] = ($running[$aiId] ?? 0.0) + $kgDelta;

                if ($withDetail) {
                    $result[$aiId]->timeline[] = (object) [
                        'date' => $event['date'],
                        'type' => $event['type'],
                        'ref' => $event['ref'],
                        'category_code' => $cat->category_code,
                        'member_name' => $cat->member_ai_name,
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
     * Một mã danh mục có thể đứng sau nhiều chủ ngưỡng (nhiều hoạt chất Bảng A, hoặc vừa
     * ngưỡng riêng vừa mục gộp) - giữ chủ ngưỡng CĂNG NHẤT (tỉ lệ đỉnh cao nhất) để cột
     * cảnh báo không bị dòng nhẹ hơn ghi đè.
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

            $eval = $evaluations[$ing->ai_id];

            foreach ($ing->category_ids as $categoryId) {
                if (! isset($out[$categoryId]) || $eval->peak_ratio > $out[$categoryId]->peak_ratio) {
                    $out[$categoryId] = $eval;
                }
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
     * Hoá chất là thành viên của một MỤC GỘP thì lấy khối lượng hợp chất thô (không nhân %),
     * chỉ nhân equiv_factor - đúng cách cộng của onHandByIngredient().
     *
     * @param  iterable  $amountRows  các object/array có khoá amount + unit_id
     * @return array{kg: float, unconvertible: bool}  unconvertible = có dòng đơn vị đếm / thiếu tỉ trọng
     */
    public static function sumEstimateKg(int $categoryId, $amountRows): array
    {
        $cat = DB::table('chemical_categories')
            ->where('id', $categoryId)
            ->select('chem_names_id', 'density', 'ai_content_percent')
            ->first();

        $rows = collect($amountRows);

        if (! $cat) {
            return ['kg' => 0.0, 'unconvertible' => $rows->isNotEmpty()];
        }

        $unitIds = $rows->map(fn ($r) => (int) ($r->unit_id ?? ($r['unit_id'] ?? 0)))->filter()->unique()->all();
        $units = $unitIds ? DB::table('units')->whereIn('id', $unitIds)->get()->keyBy('id') : collect();

        $density = $cat->density !== null ? (float) $cat->density : null;
        $multiplier = self::estimateMultiplier((int) $cat->chem_names_id, $cat->ai_content_percent);
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

            $totalKg += $base * $multiplier;
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
     * Mã danh mục hoá chất mà CHÍNH tên hoá chất của nó là hoạt chất đơn (không phải hỗn
     * hợp) thuộc NHÓM 9 (Phụ lục IV Bảng A), hoạt chất đã duyệt + đang hoạt động.
     *
     * Chỉ nhận chem_names có ĐÚNG 1 dòng trong bảng pivot chem_name_active_ingredient (tức
     * bản thân tên hoá chất CHÍNH LÀ hoạt chất đó, có thể pha loãng - % lấy theo
     * cnai.content_percent hoặc override cc.ai_content_percent - nhưng không lẫn hoạt chất
     * nào khác). Hoá chất là HỖN HỢP nhiều hoạt chất khác nhau (như "Hộn chất ABC" gồm
     * Acetone + Acrolein) bị loại khỏi đây dù có chứa một hoạt chất nhóm 9 bên trong: hỗn
     * hợp đó đã đứng riêng ở Nhóm 10 (Bảng B, tồn thô - MixtureHazardThreshold), nhập kho
     * hỗn hợp không tách ra cộng thêm vào tồn hoạt chất nhóm 9 của từng thành phần.
     *
     * @return array<int, object>
     */
    private static function categoryRows(): array
    {
        $ivAIds = self::appendixIvAIds();

        if (! $ivAIds) {
            return [];
        }

        $rows = DB::table('chemical_categories as cc')
            ->join('chem_names as cn', 'cc.chem_names_id', '=', 'cn.id')
            ->join('chem_name_active_ingredient as cnai', 'cnai.chem_names_id', '=', 'cn.id')
            ->join('active_ingredients as ai', 'cnai.active_ingredients_id', '=', 'ai.id')
            // Mục gộp mà hoạt chất này là thành viên (nếu có) - phải đã duyệt, đang hoạt động
            ->leftJoin('active_ingredients as pai', function ($join) {
                $join->on('pai.id', '=', 'ai.parent_id')
                    ->where('pai.status_id', 1)
                    ->where('pai.app_status', 'approved');
            })
            // Thuộc nhóm 9 khi CHÍNH NÓ có dòng phân loại Phụ lục IV / bảng A, hoặc nó là
            // thành viên của một mục gộp thuộc Phụ lục IV / bảng A.
            ->where(function ($query) use ($ivAIds) {
                $query->whereIn('ai.id', $ivAIds)->orWhereIn('pai.id', $ivAIds);
            })
            ->where('ai.status_id', 1)
            ->where('ai.app_status', 'approved')
            // Tên hoá chất phải là hoạt chất đơn - không phải hỗn hợp nhiều thành phần
            ->whereRaw('(select count(*) from chem_name_active_ingredient as x where x.chem_names_id = cn.id) = 1')
            ->select(
                'cc.id as category_id',
                'cc.code as category_code',
                'cc.density',
                'cc.ai_content_percent',
                'cnai.content_percent as cnai_percent',
                'cn.name as chem_name',
                'ai.id as ai_id',
                'ai.code as ai_code',
                'ai.name as ai_name',
                'ai.cas_no',
                'ai.threshold_kg',
                'ai.legal_ref',
                'ai.equiv_factor',
                'pai.id as parent_ai_id',
                'pai.code as parent_ai_code',
                'pai.name as parent_ai_name',
                'pai.cas_no as parent_cas_no',
                'pai.threshold_kg as parent_threshold_kg',
                'pai.legal_ref as parent_legal_ref'
            )
            ->get();

        // Chốt "chủ ngưỡng" của từng dòng. Một hoạt chất vừa có ngưỡng riêng vừa thuộc mục
        // gộp thì sinh HAI dòng - tồn của nó được cộng ở cả hai chỗ.
        $out = [];

        foreach ($rows as $row) {
            if (in_array((int) $row->ai_id, $ivAIds, true)) {
                $out[] = self::ownerRow($row, false);
            }

            if ($row->parent_ai_id !== null && in_array((int) $row->parent_ai_id, $ivAIds, true)) {
                $out[] = self::ownerRow($row, true);
            }
        }

        return $out;
    }

    /** id các hoạt chất có dòng phân loại Phụ lục IV / Bảng A (nhóm 9). */
    private static function appendixIvAIds(): array
    {
        return DB::table('active_ingredient_classifications')
            ->where('appendix', 'IV')
            ->where('table_ref', 'A')
            ->pluck('active_ingredients_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Nhân bản một dòng của categoryRows() và gắn thông tin "chủ ngưỡng":
     *   $rollUp = false -> chính hoạt chất đó chịu ngưỡng (tính có nhân % hàm lượng).
     *   $rollUp = true  -> cộng vào mục gộp cha (tính trên khối lượng hợp chất thô, không
     *                      nhân %, có nhân equiv_factor của chất thành viên).
     */
    private static function ownerRow(object $row, bool $rollUp): object
    {
        $out = clone $row;

        $out->is_rolled_up = $rollUp;
        $out->owner_ai_id = $rollUp ? (int) $row->parent_ai_id : (int) $row->ai_id;
        $out->owner_ai_code = $rollUp ? $row->parent_ai_code : $row->ai_code;
        $out->owner_ai_name = $rollUp ? $row->parent_ai_name : $row->ai_name;
        $out->owner_cas_no = $rollUp ? $row->parent_cas_no : $row->cas_no;
        $out->owner_threshold_kg = $rollUp ? $row->parent_threshold_kg : $row->threshold_kg;
        $out->owner_legal_ref = $rollUp ? $row->parent_legal_ref : $row->legal_ref;
        // Tên hoạt chất thành viên đóng góp vào mục gộp (null khi không phải dòng cộng gộp)
        $out->member_ai_name = $rollUp ? $row->ai_name : null;
        $out->member_ai_code = $rollUp ? $row->ai_code : null;

        return $out;
    }

    /**
     * % hàm lượng dùng để quy tồn của một mã danh mục (hoạt chất ĐƠN - xem categoryRows())
     * ra kg hoạt chất gốc: ưu tiên chemical_categories.ai_content_percent (khai tay ở mã
     * danh mục - override khi lô có kết quả COA khác nhãn); không khai thì lấy %
     * chem_name_active_ingredient.content_percent (độ tinh khiết/nồng độ khai trên màn Tên
     * Hoá Chất); không có gì thì mặc định 100% (coi như hoạt chất nguyên chất).
     */
    private static function resolvePercent(object $cat): float
    {
        if ($cat->ai_content_percent !== null) {
            return (float) $cat->ai_content_percent;
        }

        if ($cat->cnai_percent !== null) {
            return (float) $cat->cnai_percent;
        }

        return 100.0;
    }

    /**
     * Hệ số nhân từ "kg chất như nhập kho" ra "kg tính vào ngưỡng của chủ ngưỡng".
     *
     *   - Dòng cộng vào MỤC GỘP: lấy khối lượng hợp chất THÔ (không nhân % hàm lượng - ngưỡng
     *     của mục gộp là ngưỡng của hợp chất, không phải của nguyên tố), chỉ nhân
     *     equiv_factor của chất thành viên (mặc định 1).
     *   - Dòng hoạt chất đứng riêng: giữ nguyên cách cũ - nhân % hàm lượng (resolvePercent).
     */
    private static function kgMultiplier(object $cat): float
    {
        if (! empty($cat->is_rolled_up)) {
            return self::equivFactor($cat->equiv_factor ?? null);
        }

        return self::resolvePercent($cat) / 100;
    }

    /** equiv_factor hợp lệ (> 0); dữ liệu trống/hỏng thì coi như 1. */
    private static function equivFactor($value): float
    {
        $factor = $value === null ? 1.0 : (float) $value;

        return $factor > 0 ? $factor : 1.0;
    }

    /**
     * Dùng cho sumEstimateKg(): hệ số nhân từ kg chất như dự trù ra kg tính vào ngưỡng.
     *
     * Chỉ có ý nghĩa khi tên hoá chất là hoạt chất ĐƠN (đúng 1 dòng pivot, cùng điều kiện
     * với categoryRows()); hoá chất là hỗn hợp nhiều thành phần thì không thuộc diện Bảng A
     * nên projectedForCategory() bỏ qua kg trả về ở đây.
     *
     *   - Hoạt chất là THÀNH VIÊN của mục gộp: khối lượng hợp chất thô × equiv_factor,
     *     không nhân % (kể cả khi mã danh mục có khai ai_content_percent).
     *   - Hoạt chất đứng riêng: ai_content_percent (khai tay ở mã danh mục) nếu có, không
     *     thì content_percent lớn nhất của pivot; không có gì -> 100%.
     */
    private static function estimateMultiplier(int $chemNamesId, $categoryPercent): float
    {
        $ivAIds = self::appendixIvAIds();

        $row = $ivAIds
            ? DB::table('chem_name_active_ingredient as cnai')
                ->join('active_ingredients as ai', 'cnai.active_ingredients_id', '=', 'ai.id')
                ->leftJoin('active_ingredients as pai', function ($join) {
                    $join->on('pai.id', '=', 'ai.parent_id')
                        ->where('pai.status_id', 1)
                        ->where('pai.app_status', 'approved');
                })
                ->where('cnai.chem_names_id', $chemNamesId)
                ->where(function ($query) use ($ivAIds) {
                    $query->whereIn('ai.id', $ivAIds)->orWhereIn('pai.id', $ivAIds);
                })
                ->where('ai.status_id', 1)
                ->where('ai.app_status', 'approved')
                ->whereRaw('(select count(*) from chem_name_active_ingredient as x where x.chem_names_id = cnai.chem_names_id) = 1')
                // Chất thuộc mục gộp (parent_ai_id khác null) lên trước, rồi tới % cao nhất
                ->orderByRaw('pai.id is null')
                ->orderByDesc('cnai.content_percent')
                ->select('cnai.content_percent', 'ai.equiv_factor', 'pai.id as parent_ai_id')
                ->first()
            : null;

        if ($row && $row->parent_ai_id !== null && in_array((int) $row->parent_ai_id, $ivAIds, true)) {
            return self::equivFactor($row->equiv_factor);
        }

        if ($categoryPercent !== null) {
            return (float) $categoryPercent / 100;
        }

        if ($row && $row->content_percent !== null) {
            return (float) $row->content_percent / 100;
        }

        return 1.0;
    }
}
