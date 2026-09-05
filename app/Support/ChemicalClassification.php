<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * PHÂN LOẠI HOÁ CHẤT THEO NGHỊ ĐỊNH 24/2026/NĐ-CP - 10 nhóm của "hình 1", cộng thêm
 * nhóm 11 "Hoá chất cấm theo Luật Đầu tư 2025, số 143/2025/QH15" (khai đơn chất, không
 * thuộc phạm vi NĐ 24/2026 nhưng gộp chung danh sách để khai/lọc/cảnh báo cùng một chỗ).
 *
 * Nguồn sự thật DUY NHẤT để suy 10 nhóm. Không còn cột chemical_categories.classification
 * và không còn active_ingredients.is_table_a - mọi phân loại suy tự động từ hai dữ liệu gốc:
 *
 *   - active_ingredient_classifications  (đơn chất: nhóm 1, 3, 4, 5, 6, 7, 9)
 *   - chem_names + chem_name_active_ingredient(.content_percent) + chem_name_mixture_hazard_category
 *     (hỗn hợp: nhóm 2 tick tay, nhóm 8 suy từ % thành phần, nhóm 10 theo điều kiện Bảng B)
 *
 * QUY TẮC HỖN HỢP (hình 1) - hỗn hợp = từ 2 hoạt chất thành phần trở lên, CHỈ mang nhóm
 * của hỗn hợp (2 / 8 / 10), KHÔNG thừa hưởng nhóm đơn chất (1, 3, 4, 5, 6, 7, 9) của các
 * thành phần (các nhóm đó thuộc về từng hoạt chất, đã hiển thị riêng ở cột thành phần):
 *   Nhóm 2 : hỗn hợp (>= 2 hoạt chất) có >= 1 thành phần nhóm 1 (PL II bảng A).
 *   Nhóm 8 : có thành phần nhóm 3/4/6/7 tỉ lệ > 1%, HOẶC thành phần nhóm 5 tỉ lệ > 5%.
 *   Nhóm 10: hỗn hợp (>= 2 hoạt chất) có >= 1 thành phần nhóm 9 (PL IV bảng A) VÀ tick
 *            >= 1 nhóm nguy hại mixture_hazard_categories (đã duyệt, đang hoạt động).
 * Tên hoá chất đơn chất (<= 1 thành phần) thì mang đúng nhóm của thành phần đó.
 *
 * Danh mục hoá chất (chemical_categories) trỏ về đúng một tên hoá chất -> nhóm của mã danh
 * mục = nhóm suy được của tên hoá chất đó.
 *
 * Query Builder thuần, không Eloquent.
 */
class ChemicalClassification
{
    /** Nhãn 10 nhóm (khớp cột "GHI CHÚ" của biểu mẫu NĐ 24/2026). */
    public const GROUPS = [
        1 => 'Hoá chất sản xuất, kinh doanh có điều kiện (Phụ lục II_nhóm 1)',
        2 => 'Hỗn hợp chất sản xuất, kinh doanh có điều kiện (Phụ lục II_nhóm 2)',
        3 => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 1_bảng A (Tiền chất công nghiệp))',
        4 => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 1_bảng B (Hoá chất cấm))',
        5 => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 2_bảng A (Tiền chất công nghiệp))',
        6 => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 2_bảng B (Hoá chất cấm))',
        7 => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 2_bảng C (Hoá chất thuộc các công ước quốc tế về hoá chất))',
        8 => 'Hỗn hợp chất cần kiểm soát đặc biệt (Phụ lục III)',
        9 => 'Hoá chất phải xây dựng kế hoạch phòng ngừa, ứng phó sự cố hoá chất (Phụ lục IV_Bảng A)',
        10 => 'Hoá chất phải xây dựng kế hoạch phòng ngừa, ứng phó sự cố hoá chất (Phụ lục IV_Bảng B)',
        11 => 'Hoá chất cấm theo Luật Đầu tư 2025, số 143/2025/QH15',
    ];

    /**
     * Màu badge/chip theo mức độ (quy tắc dùng chung toàn hệ thống):
     *   - ĐỎ  : nhóm 9, 10 - phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố (Phụ lục IV).
     *   - CAM : nhóm 4, 6  - "Hoá chất cấm" (Phụ lục III bảng B).
     *   - Xanh: các nhóm còn lại.
     */
    public const CRITICAL_GROUPS = [9, 10];
    public const BANNED_GROUPS = [4, 6, 11];

    /** @deprecated Giữ để tương thích - dùng badgeClass() / BANNED_GROUPS. */
    public const DANGER_GROUPS = [4, 6];

    /** Nhóm khai được ở màn "Tên Hoạt Chất" (đơn chất). */
    public const SINGLE_SUBSTANCE_GROUPS = [1, 3, 4, 5, 6, 7, 9, 11];

    /** Nhóm chỉ dành cho hỗn hợp, suy ở màn "Tên Hoá Chất". */
    public const MIXTURE_GROUPS = [2, 8, 10];

    /**
     * Ánh xạ bộ (appendix|group_no|table_ref) -> số nhóm. Khoá dùng '' cho giá trị null.
     *
     * Phụ lục I ("Danh mục hoá chất phải khai báo") KHÔNG nằm trong hình 1: dòng
     * active_ingredient_classifications có appendix='I' vẫn được lưu để giữ vết, nhưng
     * groupOf() trả null nên không sinh badge nhóm và syncGroups() ở màn Tên Hoạt Chất
     * không bao giờ đụng tới.
     */
    private const TRIPLE_TO_GROUP = [
        'II|1|'    => 1,
        'III|1|A'  => 3,
        'III|1|B'  => 4,
        'III|2|A'  => 5,
        'III|2|B'  => 6,
        'III|2|C'  => 7,
        'IV||A'    => 9,
        // Không thuộc Nghị định 24/2026 - "Hoá chất cấm" theo Luật Đầu tư 2025 (số
        // 143/2025/QH15). Dùng chung bảng active_ingredient_classifications, khoá appendix
        // riêng 'LDT' để không đụng các phụ lục II/III/IV ở trên.
        'LDT||'    => 11,
    ];

    /** Ngưỡng % để nhóm 8 kích hoạt theo nhóm của thành phần. */
    private const G8_PERCENT_BY_GROUP = [
        3 => 1.0,
        4 => 1.0,
        6 => 1.0,
        7 => 1.0,
        5 => 5.0,
    ];

    /* ---------------------------------------------------------------------
     |  Ánh xạ nhóm <-> bộ (phụ lục / nhóm / bảng)
     | --------------------------------------------------------------------- */

    /** (appendix, group_no, table_ref) -> số nhóm 1..10, hoặc null nếu không khớp. */
    public static function groupOf(string $appendix, $groupNo, $tableRef): ?int
    {
        $key = trim($appendix) . '|' . ($groupNo === null || $groupNo === '' ? '' : (int) $groupNo)
            . '|' . ($tableRef === null ? '' : trim((string) $tableRef));

        return self::TRIPLE_TO_GROUP[$key] ?? null;
    }

    /**
     * Số nhóm đơn chất -> [appendix, group_no|null, table_ref|null] để ghi active_ingredient_classifications.
     *
     * @return array{0:string,1:?int,2:?string}|null
     */
    public static function tripleOf(int $group): ?array
    {
        foreach (self::TRIPLE_TO_GROUP as $key => $mapped) {
            if ($mapped === $group) {
                [$appendix, $groupNo, $tableRef] = explode('|', $key);

                return [
                    $appendix,
                    $groupNo === '' ? null : (int) $groupNo,
                    $tableRef === '' ? null : $tableRef,
                ];
            }
        }

        return null;
    }

    public static function label(int $group): string
    {
        return self::GROUPS[$group] ?? ('Nhóm ' . $group);
    }

    /** Mã hiển thị ngắn: 1 -> 'N1'. */
    public static function code(int $group): string
    {
        return 'N' . $group;
    }

    /** Lớp badge Bootstrap cho một nhóm: đỏ (9,10) / cam (4,6) / xanh (còn lại). */
    public static function badgeClass(int $group): string
    {
        if (in_array($group, self::CRITICAL_GROUPS, true)) {
            return 'badge-danger';
        }

        if (in_array($group, self::BANNED_GROUPS, true)) {
            return 'badge-warning text-dark';
        }

        return 'badge-primary';
    }

    /** Hạng màu theo mã N: 'critical' | 'banned' | '' - cho chip không dùng lớp Bootstrap. */
    public static function toneOfCode(string $code): string
    {
        $group = (int) ltrim($code, 'Nn');

        if (in_array($group, self::CRITICAL_GROUPS, true)) {
            return 'critical';
        }

        if (in_array($group, self::BANNED_GROUPS, true)) {
            return 'banned';
        }

        return '';
    }

    /** ['N1' => nhãn, ... 'N10' => nhãn] cho bộ lọc / chip (giữ mã N-prefix như dữ liệu cũ). */
    public static function labels(): array
    {
        $out = [];
        foreach (self::GROUPS as $group => $label) {
            $out['N' . $group] = $label;
        }

        return $out;
    }

    /* ---------------------------------------------------------------------
     |  Nhóm của hoạt chất (đơn chất)
     | --------------------------------------------------------------------- */

    /** @return int[] số nhóm của một hoạt chất, tăng dần. */
    public static function groupsForActiveIngredient(int $aiId): array
    {
        return self::groupsForActiveIngredients([$aiId])[$aiId] ?? [];
    }

    /**
     * @param  int[]  $aiIds
     * @return array<int, int[]>  [active_ingredients_id => [số nhóm]]
     */
    public static function groupsForActiveIngredients(array $aiIds): array
    {
        $aiIds = array_values(array_unique(array_filter(array_map('intval', $aiIds))));

        if (! $aiIds) {
            return [];
        }

        $rows = DB::table('active_ingredient_classifications')
            ->whereIn('active_ingredients_id', $aiIds)
            ->get(['active_ingredients_id', 'appendix', 'group_no', 'table_ref']);

        $out = [];

        foreach ($rows as $row) {
            $group = self::groupOf($row->appendix, $row->group_no, $row->table_ref);

            if ($group === null) {
                continue;
            }

            $out[(int) $row->active_ingredients_id][$group] = true;
        }

        foreach ($out as $aiId => $groups) {
            $keys = array_keys($groups);
            sort($keys);
            $out[$aiId] = $keys;
        }

        return $out;
    }

    /* ---------------------------------------------------------------------
     |  Nhóm của tên hoá chất (hỗn hợp: chỉ 2/8/10; đơn chất: nhóm của thành phần)
     | --------------------------------------------------------------------- */

    /** @return int[] số nhóm suy được của một tên hoá chất, tăng dần. */
    public static function groupsForChemName(int $chemNameId): array
    {
        return self::groupsByChemName([$chemNameId])[$chemNameId] ?? [];
    }

    /**
     * @param  int[]|null  $chemNameIds  null = mọi tên hoá chất.
     * @return array<int, int[]>  [chem_names_id => [số nhóm]]
     */
    public static function groupsByChemName(?array $chemNameIds = null): array
    {
        $chemQuery = DB::table('chem_names')->select('id');

        if ($chemNameIds !== null) {
            $chemNameIds = array_values(array_unique(array_filter(array_map('intval', $chemNameIds))));

            if (! $chemNameIds) {
                return [];
            }

            $chemQuery->whereIn('id', $chemNameIds);
        }

        $chemNames = $chemQuery->get();

        if ($chemNames->isEmpty()) {
            return [];
        }

        $ids = $chemNames->pluck('id')->map(fn ($v) => (int) $v)->all();

        // Thành phần + % của từng hỗn hợp (chỉ hoạt chất đã duyệt, đang hoạt động)
        $components = DB::table('chem_name_active_ingredient as p')
            ->join('active_ingredients as ai', 'ai.id', '=', 'p.active_ingredients_id')
            ->whereIn('p.chem_names_id', $ids)
            ->where('ai.status_id', 1)
            ->where('ai.app_status', 'approved')
            ->get(['p.chem_names_id', 'p.active_ingredients_id', 'p.content_percent'])
            ->groupBy('chem_names_id');

        $aiGroups = self::groupsForActiveIngredients(
            $components->flatMap(fn ($rows) => $rows->pluck('active_ingredients_id'))->all()
        );

        // Tên hoá chất có tick >= 1 nhóm nguy hại Bảng B (đã duyệt, đang hoạt động)
        $withHazard = DB::table('chem_name_mixture_hazard_category as p')
            ->join('mixture_hazard_categories as h', 'h.id', '=', 'p.mixture_hazard_categories_id')
            ->whereIn('p.chem_names_id', $ids)
            ->where('h.status_id', 1)
            ->where('h.app_status', 'approved')
            ->distinct()
            ->pluck('p.chem_names_id')
            ->map(fn ($v) => (int) $v)
            ->flip();

        $out = [];

        foreach ($chemNames as $chem) {
            $chemId = (int) $chem->id;
            $rows = $components->get($chemId, collect());
            $groups = [];

            // HỖN HỢP = từ 2 hoạt chất thành phần trở lên.
            $isMixture = $rows->count() >= 2;
            $hasG1 = false;
            $hasG9 = false;

            foreach ($rows as $row) {
                $aiId = (int) $row->active_ingredients_id;
                $percent = $row->content_percent === null ? 0.0 : (float) $row->content_percent;
                $compGroups = $aiGroups[$aiId] ?? [];

                foreach ($compGroups as $g) {
                    if ($g === 1) {
                        $hasG1 = true;
                    }

                    if ($g === 9) {
                        $hasG9 = true;
                    }

                    if ($isMixture) {
                        // Hỗn hợp chỉ mang nhóm của HỖN HỢP (2 / 8 / 10) - KHÔNG thừa hưởng
                        // nhóm đơn chất của các thành phần (các nhóm đó đã hiển thị ở cột
                        // "Hoạt Chất Thành Phần"). Nhóm 8: thành phần nhóm 3/4/5/6/7 vượt ngưỡng %.
                        if (isset(self::G8_PERCENT_BY_GROUP[$g]) && $percent > self::G8_PERCENT_BY_GROUP[$g]) {
                            $groups[8] = true;
                        }
                    } else {
                        // Tên hoá chất đơn chất (<= 1 thành phần): mang đúng nhóm của thành phần đó.
                        $groups[$g] = true;
                    }
                }
            }

            // Nhóm 10 - điều kiện Bảng B: hỗn hợp >= 2 thành phần, có nhóm 9, có tick nguy hại
            if ($isMixture && $hasG9 && $withHazard->has($chemId)) {
                $groups[10] = true;
            }

            // Nhóm 2 - hỗn hợp >= 2 thành phần, có >= 1 thành phần thuộc nhóm 1
            if ($isMixture && $hasG1) {
                $groups[2] = true;
            }

            $keys = array_keys($groups);
            sort($keys);
            $out[$chemId] = $keys;
        }

        return $out;
    }

    /* ---------------------------------------------------------------------
     |  Nhóm của mã danh mục hoá chất
     | --------------------------------------------------------------------- */

    /**
     * [chemical_categories.id => [số nhóm]] - suy từ tên hoá chất mà mã danh mục trỏ tới.
     * Chỉ tính các mã danh mục đang hoạt động không cần thiết ở đây; caller tự lọc nếu muốn.
     */
    public static function groupsByCategory(): array
    {
        $cats = DB::table('chemical_categories')
            ->whereNotNull('chem_names_id')
            ->get(['id', 'chem_names_id']);

        if ($cats->isEmpty()) {
            return [];
        }

        $byChem = self::groupsByChemName(
            $cats->pluck('chem_names_id')->map(fn ($v) => (int) $v)->unique()->all()
        );

        $out = [];
        foreach ($cats as $cat) {
            $out[(int) $cat->id] = $byChem[(int) $cat->chem_names_id] ?? [];
        }

        return $out;
    }

    /**
     * [chemical_categories.id => ['N1','N9',...]] - dạng mã, dùng thay chỗ cũ đọc
     * json_decode(chemical_categories.classification).
     */
    public static function codesByCategory(): array
    {
        $out = [];
        foreach (self::groupsByCategory() as $categoryId => $groups) {
            $out[$categoryId] = array_map(fn ($g) => 'N' . $g, $groups);
        }

        return $out;
    }
}
