<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * QUY ĐỔI ĐƠN VỊ GIỮA CÁC PHÒNG BAN CHO CÙNG MỘT MÃ
 *
 * Đơn vị tính là của riêng từng phòng (department_chemicals / department_standards), nên
 * cùng một mã hoá chất hoặc chất chuẩn có thể tồn tại nhiều đơn vị khác nhau trong hệ
 * thống. Lớp này trả lời đúng một câu hỏi: đổi X đơn vị của phòng A thành bao nhiêu đơn
 * vị của phòng B.
 *
 * Thứ tự tra cứu:
 * 1. Cùng một đơn vị            -> hệ số 1.
 * 2. Có khai trong bảng quy đổi -> lấy đúng hệ số đã khai (chiều ngược lấy 1/factor).
 * 3. Chưa khai                  -> nhờ App\Support\UnitConverter tính theo nhóm đơn vị và
 *                                  tỉ trọng. Đây là đường dành cho kg <-> g, L <-> ml.
 * 4. Không ra được              -> trả null, nơi gọi phải báo lỗi chứ không được đoán.
 *
 * Bảng quy đổi đứng TRƯỚC UnitConverter vì nó là khai báo tường minh của người dùng cho
 * đúng mã đó; quy cách đóng gói của từng mã (1 chai = 500 ml) không suy ra được bằng
 * công thức chung.
 */
class CategoryUnitConversion
{
    public const TABLE = 'category_unit_conversions';

    public const TYPE_CHEMICAL = 'chemical';

    public const TYPE_STANDARD = 'standard';

    /**
     * Các đơn vị mà CÁC PHÒNG KHÁC đã khai cho từng mã: [category_id => [đơn vị]].
     *
     * Dùng để dựng phần "Quy Đổi Đơn Vị" trong modal khai báo của phòng: phòng đang khai
     * chọn đơn vị nào lệch với danh sách này thì phải khai hệ số quy đổi.
     */
    public static function unitsInUseByCategory(string $type, int $exceptDepartmentId)
    {
        return DB::table(self::departmentTable($type).' as dept')
            ->leftJoin('units', 'dept.unit_id', '=', 'units.id')
            ->leftJoin('deparments', 'dept.department_id', '=', 'deparments.id')
            ->select(
                'dept.category_id',
                'dept.unit_id',
                'units.name as unit_name',
                'units.short_name as unit_short_name',
                'deparments.shortName as department_short',
                'deparments.name as department_name'
            )
            ->whereNotNull('dept.unit_id')
            ->where('dept.department_id', '<>', $exceptDepartmentId)
            ->orderBy('units.name', 'asc')
            ->get()
            ->groupBy('category_id')
            // Hai phòng khai cùng một đơn vị thì chỉ cần khai quy đổi một lần
            ->map(fn ($rows) => $rows->unique('unit_id')->values());
    }

    /** Hệ số quy đổi đã khai, gom theo mã: [category_id => ['<from>-<to>' => factor]]. */
    public static function declaredByCategory(string $type)
    {
        return DB::table(self::TABLE)
            ->where('category_type', $type)
            ->where('status_id', 1)
            ->get()
            ->groupBy('category_id')
            ->map(fn ($rows) => $rows->mapWithKeys(
                fn ($row) => [$row->from_unit_id.'-'.$row->to_unit_id => (float) $row->factor]
            ));
    }

    /**
     * Hệ số để đổi từ $fromUnitId sang $toUnitId cho đúng một mã.
     *
     * @return float|null null nghĩa là chưa đủ dữ liệu để đổi, KHÔNG phải hệ số 0
     */
    public static function factor(string $type, int $categoryId, ?int $fromUnitId, ?int $toUnitId): ?float
    {
        if (! $fromUnitId || ! $toUnitId) {
            return null;
        }

        if ($fromUnitId === $toUnitId) {
            return 1.0;
        }

        $rows = DB::table(self::TABLE)
            ->where('category_type', $type)
            ->where('category_id', $categoryId)
            ->where('status_id', 1)
            ->where(function ($query) use ($fromUnitId, $toUnitId) {
                $query->where(function ($sub) use ($fromUnitId, $toUnitId) {
                    $sub->where('from_unit_id', $fromUnitId)->where('to_unit_id', $toUnitId);
                })->orWhere(function ($sub) use ($fromUnitId, $toUnitId) {
                    $sub->where('from_unit_id', $toUnitId)->where('to_unit_id', $fromUnitId);
                });
            })
            ->get();

        foreach ($rows as $row) {
            $factor = (float) $row->factor;

            if ($factor <= 0) {
                continue;
            }

            return (int) $row->from_unit_id === $fromUnitId ? $factor : 1 / $factor;
        }

        return self::genericFactor($type, $categoryId, $fromUnitId, $toUnitId);
    }

    /** Đổi số lượng; null nghĩa là không đổi được, nơi gọi phải dừng lại và báo lỗi. */
    public static function convert(string $type, int $categoryId, float $quantity, ?int $fromUnitId, ?int $toUnitId): ?float
    {
        $factor = self::factor($type, $categoryId, $fromUnitId, $toUnitId);

        return $factor === null ? null : $quantity * $factor;
    }

    /**
     * Hệ số suy ra được bằng công thức chung (kg <-> g, L <-> ml khi có tỉ trọng).
     *
     * Tính trên đúng 1 đơn vị nên kết quả cũng chính là hệ số cần tìm.
     */
    private static function genericFactor(string $type, int $categoryId, int $fromUnitId, int $toUnitId): ?float
    {
        $units = DB::table('units')->whereIn('id', [$fromUnitId, $toUnitId])->get()->keyBy('id');

        $from = $units[$fromUnitId] ?? null;
        $to = $units[$toUnitId] ?? null;

        if (! $from || ! $to) {
            return null;
        }

        $density = DB::table(self::categoryTable($type))->where('id', $categoryId)->value('density');

        return UnitConverter::convert(1.0, $from, $to, $density === null ? null : (float) $density);
    }

    /** Ghi hệ số vừa khai; khai lại cặp đã có thì đè lên dòng đang có. */
    public static function save(string $type, int $categoryId, int $fromUnitId, int $toUnitId, float $factor, string $actor): void
    {
        $existing = DB::table(self::TABLE)
            ->where('category_type', $type)
            ->where('category_id', $categoryId)
            ->where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->first();

        if ($existing) {
            DB::table(self::TABLE)->where('id', $existing->id)->update([
                'factor' => $factor,
                'status_id' => 1,
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table(self::TABLE)->insert([
            'category_type' => $type,
            'category_id' => $categoryId,
            'from_unit_id' => $fromUnitId,
            'to_unit_id' => $toUnitId,
            'factor' => $factor,
            'status_id' => 1,
            'created_by' => $actor,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Đơn vị các phòng KHÁC đã khai cho đúng một mã, kèm tên phòng để hiện lên form. */
    public static function unitsInUse(string $type, int $categoryId, int $exceptDepartmentId)
    {
        return self::unitsInUseByCategory($type, $exceptDepartmentId)->get($categoryId, collect());
    }

    /**
     * Những đơn vị phòng khác đang dùng mà lần khai này CHƯA có hệ số quy đổi hợp lệ.
     *
     * Đã khai sẵn ở bảng quy đổi từ trước thì không bắt khai lại, nên chỉ những đơn vị
     * còn thiếu mới trả về đây.
     *
     * @param  array  $submitted  Hệ số người dùng vừa nhập: [<to_unit_id> => factor]
     */
    public static function missingFor(string $type, int $categoryId, int $departmentId, ?int $unitId, array $submitted)
    {
        if (! $unitId) {
            return collect();
        }

        return self::unitsInUse($type, $categoryId, $departmentId)
            ->filter(function ($row) use ($type, $categoryId, $unitId, $submitted) {
                if ((int) $row->unit_id === $unitId) {
                    return false;
                }

                $entered = $submitted[$row->unit_id] ?? null;

                if (is_numeric($entered) && (float) $entered > 0) {
                    return false;
                }

                // Cặp này đã khai từ lần trước thì dùng lại, không bắt nhập nữa
                return self::declared($type, $categoryId, $unitId, (int) $row->unit_id) === null;
            })
            ->values();
    }

    /**
     * Ghi các hệ số vừa khai theo chiều: 1 <đơn vị của phòng đang khai> = factor <đơn vị kia>.
     *
     * Bỏ qua ô để trống và ô không phải số dương - phần bắt buộc đã do missingFor() chặn.
     */
    public static function saveDeclarations(string $type, int $categoryId, int $departmentId, ?int $unitId, array $submitted, string $actor): void
    {
        if (! $unitId) {
            return;
        }

        foreach ($submitted as $toUnitId => $factor) {
            $toUnitId = (int) $toUnitId;

            if ($toUnitId <= 0 || $toUnitId === $unitId || ! is_numeric($factor) || (float) $factor <= 0) {
                continue;
            }

            self::save($type, $categoryId, $unitId, $toUnitId, (float) $factor, $actor);
        }
    }

    /** Hệ số ĐÃ KHAI TƯỜNG MINH của một cặp đơn vị, không đụng tới công thức chung. */
    private static function declared(string $type, int $categoryId, int $fromUnitId, int $toUnitId): ?float
    {
        $row = DB::table(self::TABLE)
            ->where('category_type', $type)
            ->where('category_id', $categoryId)
            ->where('status_id', 1)
            ->where(function ($query) use ($fromUnitId, $toUnitId) {
                $query->where(function ($sub) use ($fromUnitId, $toUnitId) {
                    $sub->where('from_unit_id', $fromUnitId)->where('to_unit_id', $toUnitId);
                })->orWhere(function ($sub) use ($fromUnitId, $toUnitId) {
                    $sub->where('from_unit_id', $toUnitId)->where('to_unit_id', $fromUnitId);
                });
            })
            ->first();

        if (! $row || (float) $row->factor <= 0) {
            return null;
        }

        return (int) $row->from_unit_id === $fromUnitId ? (float) $row->factor : 1 / (float) $row->factor;
    }

    private static function departmentTable(string $type): string
    {
        return $type === self::TYPE_STANDARD ? 'department_standards' : 'department_chemicals';
    }

    private static function categoryTable(string $type): string
    {
        return $type === self::TYPE_STANDARD ? 'standard_categories' : 'chemical_categories';
    }
}
