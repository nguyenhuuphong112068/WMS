<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * TƯƠNG KỴ KHI LƯU TRỮ HOÁ CHẤT - "SƠ ĐỒ LƯU TRỮ HOÁ CHẤT THEO HÌNH ĐỒ CẢNH BÁO" (GHS)
 *
 * Mỗi mã danh mục hoá chất khai các nhóm Cảnh Báo An Toàn (chemical_categories.safety_warning,
 * mã trong config('chemical.safety_warnings')). config('chemical.storage_incompatible') chép lại
 * các ô "x" của sơ đồ. Hai hoá chất TƯƠNG KỴ khi có ít nhất một cặp nhóm (một của chất này, một
 * của chất kia) rơi vào ô "x". Chất chưa khai cảnh báo thì không xét được.
 *
 * "Gần nhau" = cùng một cấp định khu khai ở config('chemical.incompatibility_scope') (mặc định
 * cùng Kệ/Tủ). Hoá chất đang ở một chỗ gồm:
 *   - dòng "Hoá Chất Của Phòng" đang hoạt động có định khu ở đó
 *     (chemical_department_categories.default_location_id)
 *   - lô đang còn tồn ở đó (chemical_imports.location_id; tồn = nhập + cân bằng - xuất > 0,
 *     tính cả lô "Chờ kiểm tra" vì hàng đã nằm trên kệ)
 * Cùng một mã danh mục thì bỏ qua - một chất để cạnh chính nó.
 *
 * Query Builder thuần, không Eloquent.
 */
class ChemicalCompatibility
{
    /** Cấp định khu => cột của bảng locations + nhãn hiển thị */
    public const SCOPES = [
        'location' => ['column' => 'id', 'label' => 'Vị trí'],
        'tier' => ['column' => 'tier_id', 'label' => 'Tầng'],
        'column' => ['column' => 'column_id', 'label' => 'Cột'],
        'shelf' => ['column' => 'shelf_id', 'label' => 'Kệ/Tủ'],
        'warehouse' => ['column' => 'warehouse_id', 'label' => 'Kho'],
    ];

    /** Tránh sai số cộng trừ số thực khi xét lô còn tồn */
    private const EPSILON = 0.0000001;

    /** Nhãn mọi mã cảnh báo, gồm cả mã cũ chỉ còn trong ảnh chụp lịch sử. */
    public static function labels(): array
    {
        return config('chemical.safety_warnings', []) + config('chemical.safety_warnings_legacy', []);
    }

    /** Nhãn ngắn (phần tiếng Việt trước dấu "/") để ghép câu cảnh báo cho gọn. */
    public static function shortLabel(string $code): string
    {
        $label = self::labels()[$code] ?? $code;

        return trim(explode('/', $label)[0]);
    }

    /** Ma trận đối xứng: [mã => [mã tương kỵ => true]] */
    public static function matrix(): array
    {
        static $matrix = null;

        if ($matrix !== null) {
            return $matrix;
        }

        $matrix = [];

        foreach (config('chemical.storage_incompatible', []) as $code => $others) {
            foreach ((array) $others as $other) {
                $matrix[$code][$other] = true;
                $matrix[$other][$code] = true;
            }
        }

        return $matrix;
    }

    public static function isIncompatible(string $a, string $b): bool
    {
        return isset(self::matrix()[$a][$b]);
    }

    /** Các cặp nhóm tương kỵ giữa hai hoá chất: [[mã của A, mã của B], ...] */
    public static function pairs(array $codesA, array $codesB): array
    {
        $pairs = [];

        foreach ($codesA as $a) {
            foreach ($codesB as $b) {
                if (self::isIncompatible($a, $b)) {
                    $pairs[] = [$a, $b];
                }
            }
        }

        return $pairs;
    }

    /** Mã nào của danh sách có tham gia xét tương kỵ (có dòng trong sơ đồ). */
    public static function comparableCodes(array $codes): array
    {
        return array_values(array_filter($codes, fn ($code) => isset(self::matrix()[$code])));
    }

    /** Chuỗi JSON safety_warning -> mảng mã */
    public static function decode($value): array
    {
        $decoded = $value ? json_decode($value, true) : null;

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    /** Cấp định khu đang dùng để xét "gần nhau": ['key', 'column', 'label'] */
    public static function scope(): array
    {
        $key = (string) config('chemical.incompatibility_scope', 'shelf');
        $key = isset(self::SCOPES[$key]) ? $key : 'shelf';

        return ['key' => $key] + self::SCOPES[$key];
    }

    /* ==========================================================
     |  ĐỐI CHIẾU MỘT HOÁ CHẤT VỚI ĐỊNH KHU
     ========================================================== */

    /**
     * Kiểm tra đặt hoá chất $categoryId vào định khu $locationId - dùng cho khung cảnh báo
     * ở modal và cho validate khi lưu.
     *
     * @return array{
     *   scope_label: string, area: ?string, warnings: array, comparable: bool,
     *   neighbours: int, conflicts: array
     * }
     *   conflicts: mỗi hoá chất tương kỵ một phần tử
     *   {category_id, code, chem_name, warnings, pairs: [[mã mình, mã họ]], places: [{location, source, lot_code}]}
     */
    public static function checkPlacement(int $categoryId, int $locationId): array
    {
        $scope = self::scope();
        $category = self::categories([$categoryId])[$categoryId] ?? null;
        $warnings = $category ? $category['warnings'] : [];
        $location = self::locationRows([$locationId])->first();

        $result = [
            'scope_label' => $scope['label'],
            'area' => $location ? self::areaLabel($location, $scope['key']) : null,
            'warnings' => $warnings,
            'comparable' => (bool) self::comparableCodes($warnings),
            'neighbours' => 0,
            'conflicts' => [],
        ];

        if (! $location) {
            return $result;
        }

        $neighbourIds = self::neighbourIds([$locationId])[$locationId] ?? [$locationId];
        $occupants = self::occupantsNear($neighbourIds, $categoryId);

        $result['neighbours'] = count($occupants);
        $result['conflicts'] = self::conflictsOf($warnings, $occupants);

        return $result;
    }

    /**
     * Gắn cờ tương kỵ cho các dòng kết quả tìm định khu (Select2): dòng tương kỵ có
     * disabled = true + warning = câu giải thích, để người dùng không chọn được.
     *
     * @param  array  $items  [{id, text}, ...]
     */
    public static function annotateLocationOptions(array $items, int $categoryId): array
    {
        $category = self::categories([$categoryId])[$categoryId] ?? null;

        if (! $category || ! self::comparableCodes($category['warnings']) || ! $items) {
            return $items;
        }

        $locationIds = array_map(fn ($item) => (int) $item['id'], $items);
        $neighbours = self::neighbourIds($locationIds);
        $allIds = array_values(array_unique(array_merge(...array_values($neighbours))));
        $occupantsByLocation = self::occupantsByLocation($allIds, $categoryId);

        foreach ($items as &$item) {
            $occupants = [];

            foreach ($neighbours[(int) $item['id']] ?? [(int) $item['id']] as $id) {
                foreach ($occupantsByLocation[$id] ?? [] as $occupant) {
                    $occupants[] = $occupant;
                }
            }

            $conflicts = self::conflictsOf($category['warnings'], self::groupOccupants($occupants));

            if ($conflicts) {
                $item['disabled'] = true;
                $item['warning'] = 'Tương kỵ: '.implode('; ', array_map(
                    fn ($conflict) => $conflict['code'].' '.$conflict['chem_name']
                        .' ('.self::pairsText($conflict['pairs']).')',
                    array_slice($conflicts, 0, 3)
                )).(count($conflicts) > 3 ? '; +'.(count($conflicts) - 3).' hoá chất khác' : '');
            }
        }

        return $items;
    }

    /** Câu báo lỗi khi lưu: "Không được đặt cùng Kệ/Tủ với ...". */
    public static function message(array $result): string
    {
        $names = array_map(
            fn ($conflict) => $conflict['code'].' - '.$conflict['chem_name'].' ('.self::pairsText($conflict['pairs']).')',
            $result['conflicts']
        );

        return 'Không được đặt hoá chất này cùng '.$result['scope_label']
            .($result['area'] ? ' "'.$result['area'].'"' : '')
            .' với hoá chất tương kỵ theo Sơ đồ lưu trữ GHS: '.implode('; ', $names)
            .'. Vui lòng chọn định khu khác.';
    }

    /** "Lỏng dễ cháy ✕ Oxy hoá, Ăn mòn nhóm axit ✕ Độc" */
    public static function pairsText(array $pairs): string
    {
        return implode(', ', array_map(
            fn ($pair) => self::shortLabel($pair[0]).' ✕ '.self::shortLabel($pair[1]),
            $pairs
        ));
    }

    /* ==========================================================
     |  DỮ LIỆU
     ========================================================== */

    /**
     * Định khu cùng phạm vi "gần nhau" của từng định khu: [locationId => [locationIds]].
     * Định khu chưa gắn cấp đang xét (cột null) thì chỉ gồm chính nó.
     */
    public static function neighbourIds(array $locationIds): array
    {
        $scope = self::scope();
        $column = $scope['column'];
        $locationIds = array_values(array_unique(array_filter(array_map('intval', $locationIds))));

        if (! $locationIds) {
            return [];
        }

        $rows = DB::table('locations')->whereIn('id', $locationIds)->select('id', $column.' as scope_value')->get();

        $values = $rows->pluck('scope_value')->filter()->unique()->values()->all();

        $groups = $values
            ? DB::table('locations')->whereIn($column, $values)->select('id', $column.' as scope_value')->get()
                ->groupBy('scope_value')
                ->map(fn ($group) => $group->pluck('id')->map(fn ($id) => (int) $id)->all())
            : collect();

        $out = [];

        foreach ($rows as $row) {
            $out[(int) $row->id] = $row->scope_value ? ($groups[$row->scope_value] ?? [(int) $row->id]) : [(int) $row->id];
        }

        return $out;
    }

    /** Hoá chất khác đang ở trong tập định khu, gộp theo mã danh mục. */
    private static function occupantsNear(array $locationIds, int $exceptCategoryId): array
    {
        $occupants = [];

        foreach (self::occupantsByLocation($locationIds, $exceptCategoryId) as $rows) {
            foreach ($rows as $row) {
                $occupants[] = $row;
            }
        }

        return self::groupOccupants($occupants);
    }

    /**
     * Hoá chất đang ở từng định khu: [locationId => [{category_id, location_id, source, lot_code}]].
     * source: 'declared' (khai định khu ở Hoá Chất Của Phòng) | 'stock' (lô đang còn tồn).
     */
    private static function occupantsByLocation(array $locationIds, int $exceptCategoryId): array
    {
        $locationIds = array_values(array_unique(array_filter(array_map('intval', $locationIds))));

        if (! $locationIds) {
            return [];
        }

        $out = [];

        $declared = DB::table(DepartmentChemical::TABLE)
            ->whereIn('default_location_id', $locationIds)
            ->where('status_id', 1)
            ->where('category_id', '<>', $exceptCategoryId)
            ->select('category_id', 'default_location_id as location_id')
            ->get();

        foreach ($declared as $row) {
            $out[(int) $row->location_id][] = (object) [
                'category_id' => (int) $row->category_id,
                'location_id' => (int) $row->location_id,
                'source' => 'declared',
                'lot_code' => null,
            ];
        }

        foreach (self::lotsInStock($locationIds, $exceptCategoryId) as $lot) {
            $out[$lot->location_id][] = (object) [
                'category_id' => $lot->category_id,
                'location_id' => $lot->location_id,
                'source' => 'stock',
                'lot_code' => $lot->code,
            ];
        }

        return $out;
    }

    /**
     * Lô hoá chất đang còn tồn tại các định khu. Cùng công thức App\Support\ChemicalStock::onHandByLot()
     * nhưng tính cả lô "Chờ kiểm tra" - hàng đó đã nằm trên kệ.
     */
    private static function lotsInStock(array $locationIds, int $exceptCategoryId): array
    {
        $imports = DB::table('chemical_imports')
            ->whereIn('location_id', $locationIds)
            ->where('status_id', 1)
            ->where('category_id', '<>', $exceptCategoryId)
            ->select('id', 'code', 'category_id', 'location_id', 'amount')
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
            $onHand = (float) $import->amount + (float) ($balanced[$import->id] ?? 0) - (float) ($used[$import->id] ?? 0);

            if ($onHand > self::EPSILON) {
                $out[] = (object) [
                    'code' => (string) $import->code,
                    'category_id' => (int) $import->category_id,
                    'location_id' => (int) $import->location_id,
                ];
            }
        }

        return $out;
    }

    /** Gộp danh sách "ai đang ở đâu" theo mã danh mục: [categoryId => [places...]] */
    private static function groupOccupants(array $occupants): array
    {
        $grouped = [];

        foreach ($occupants as $occupant) {
            $key = $occupant->source.'-'.$occupant->location_id.'-'.$occupant->lot_code;
            $grouped[$occupant->category_id][$key] = $occupant;
        }

        return array_map('array_values', $grouped);
    }

    /**
     * Đối chiếu nhóm cảnh báo của hoá chất đang xếp với các hoá chất xung quanh.
     *
     * @param  array  $grouped  [categoryId => [occupant...]] (kết quả groupOccupants)
     */
    private static function conflictsOf(array $warnings, array $grouped): array
    {
        if (! $warnings || ! $grouped) {
            return [];
        }

        $categories = self::categories(array_keys($grouped));
        $conflicts = [];
        $locationLabels = null;

        foreach ($grouped as $categoryId => $places) {
            $other = $categories[$categoryId] ?? null;
            $pairs = $other ? self::pairs($warnings, $other['warnings']) : [];

            if (! $pairs) {
                continue;
            }

            if ($locationLabels === null) {
                $allIds = [];

                foreach ($grouped as $rows) {
                    foreach ($rows as $row) {
                        $allIds[] = $row->location_id;
                    }
                }

                $locationLabels = self::locationRows($allIds)
                    ->mapWithKeys(fn ($row) => [(int) $row->id => CategoryLookup::locationLabel($row)])
                    ->all();
            }

            $conflicts[] = [
                'category_id' => (int) $categoryId,
                'code' => $other['code'],
                'chem_name' => $other['chem_name'],
                'warnings' => $other['warnings'],
                'pairs' => $pairs,
                'places' => array_map(fn ($place) => [
                    'location' => $locationLabels[$place->location_id] ?? '—',
                    'source' => $place->source,
                    'lot_code' => $place->lot_code,
                ], $places),
            ];
        }

        usort($conflicts, fn ($a, $b) => strcmp($a['code'], $b['code']));

        return $conflicts;
    }

    /** Mã danh mục + tên + nhóm cảnh báo: [id => {code, chem_name, warnings}] */
    private static function categories(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (! $ids) {
            return [];
        }

        return DB::table('chemical_categories')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->whereIn('chemical_categories.id', $ids)
            ->select('chemical_categories.id', 'chemical_categories.code', 'chemical_categories.safety_warning', 'chem_names.name as chem_name')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->id => [
                'code' => (string) $row->code,
                'chem_name' => (string) ($row->chem_name ?? ''),
                'warnings' => self::decode($row->safety_warning),
            ]])
            ->all();
    }

    private static function locationRows(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return $ids ? CategoryLookup::locationsByIds($ids) : collect();
    }

    /** Tên vùng đang xét, ví dụ "Kho 01 / Kệ/Tủ 01" khi xét theo kệ. */
    private static function areaLabel($location, string $scopeKey): string
    {
        $parts = [
            'warehouse' => $location->warehouse_name ?: '—',
            'shelf' => $location->shelf_name ?: '—',
            'column' => $location->column_name ?: '—',
            'tier' => $location->tier_name ?: '—',
            'location' => $location->code,
        ];

        $out = [];

        foreach ($parts as $key => $value) {
            $out[] = $value;

            if ($key === $scopeKey) {
                break;
            }
        }

        return implode(' / ', $out);
    }
}
