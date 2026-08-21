<?php

namespace App\Support;

/**
 * QUY ĐỔI ĐƠN VỊ TÍNH
 *
 * Nguyên tắc: tồn kho luôn lưu theo ĐƠN VỊ GỐC của mặt hàng (cột unit_id trong danh mục).
 * Phòng ban nhập/xuất bằng đơn vị nào cũng được, nhưng phải quy đổi về đơn vị gốc trước
 * khi ghi sổ, nếu không sẽ không cộng được tồn.
 *
 * Cách quy đổi:
 * - Cùng nhóm (kg -> g)      : nhân/chia factor_to_base, không cần thông tin gì thêm.
 * - Khác nhóm (kg <-> L)     : cần tỉ trọng d (g/ml) của hoá chất.
 *                              khối lượng (g) = thể tích (ml) x d
 * - Nhóm count (thùng, chai) : phụ thuộc quy cách đóng gói của từng mặt hàng nên
 *                              KHÔNG quy đổi tự động, phải khai báo quy cách riêng.
 *
 * $unit truyền vào là object/array có 2 khoá: unit_group và factor_to_base
 * (lấy thẳng từ DB::table('units')).
 */
class UnitConverter
{
    /**
     * Quy đổi $quantity từ $fromUnit sang $toUnit.
     *
     * @param  float       $quantity  Số lượng cần đổi
     * @param  object|array $fromUnit  Đơn vị nguồn
     * @param  object|array $toUnit    Đơn vị đích
     * @param  float|null  $density   Tỉ trọng d (g/ml), chỉ cần khi đổi chéo khối lượng <-> thể tích
     * @return float|null             Số lượng sau quy đổi, null nếu không đổi được
     */
    public static function convert(float $quantity, $fromUnit, $toUnit, ?float $density = null): ?float
    {
        $fromGroup = self::group($fromUnit);
        $toGroup = self::group($toUnit);
        $fromFactor = self::factor($fromUnit);
        $toFactor = self::factor($toUnit);

        if ($fromFactor <= 0 || $toFactor <= 0) {
            return null;
        }

        // Đưa về đơn vị gốc của nhóm nguồn: g nếu là khối lượng, ml nếu là thể tích
        $base = $quantity * $fromFactor;

        if ($fromGroup !== $toGroup) {
            $base = self::crossGroup($base, $fromGroup, $toGroup, $density);

            if ($base === null) {
                return null;
            }
        }

        return $base / $toFactor;
    }

    /**
     * Đổi giá trị đã quy về đơn vị gốc từ nhóm này sang nhóm khác.
     * Chỉ mass <-> volume đổi được, và bắt buộc có tỉ trọng.
     */
    private static function crossGroup(float $base, string $fromGroup, string $toGroup, ?float $density): ?float
    {
        if ($density === null || $density <= 0) {
            return null;
        }

        // ml -> g : nhân tỉ trọng
        if ($fromGroup === 'volume' && $toGroup === 'mass') {
            return $base * $density;
        }

        // g -> ml : chia tỉ trọng
        if ($fromGroup === 'mass' && $toGroup === 'volume') {
            return $base / $density;
        }

        // Dính tới nhóm count (thùng, chai, bao) thì không có cách đổi tự động
        return null;
    }

    /**
     * Có đổi được giữa hai đơn vị này không, và nếu không thì vì sao.
     * Dùng để hiện thông báo cho người dùng thay vì im lặng trả về null.
     *
     * @return array{ok: bool, reason: string}
     */
    public static function check($fromUnit, $toUnit, ?float $density = null): array
    {
        $fromGroup = self::group($fromUnit);
        $toGroup = self::group($toUnit);

        if ($fromGroup === $toGroup) {
            if ($fromGroup === 'count') {
                return ['ok' => true, 'reason' => 'Cùng nhóm đếm, chỉ đổi được khi hai đơn vị cùng quy cách.'];
            }

            return ['ok' => true, 'reason' => ''];
        }

        if ($fromGroup === 'count' || $toGroup === 'count') {
            return [
                'ok' => false,
                'reason' => 'Không tự quy đổi được giữa đơn vị đếm/bao bì và đơn vị khối lượng hoặc thể tích. Cần khai báo quy cách đóng gói cho mặt hàng.',
            ];
        }

        if ($density === null || $density <= 0) {
            return [
                'ok' => false,
                'reason' => 'Đổi giữa khối lượng và thể tích cần tỉ trọng d (g/ml). Hãy khai báo tỉ trọng cho hoá chất này.',
            ];
        }

        return ['ok' => true, 'reason' => ''];
    }

    /** Nhãn tiếng Việt của nhóm đơn vị. */
    public static function groupLabel(?string $group): string
    {
        return config('unit.groups.' . $group . '.label', $group ?: '—');
    }

    private static function group($unit): string
    {
        $value = is_array($unit) ? ($unit['unit_group'] ?? null) : ($unit->unit_group ?? null);

        return $value ?: 'count';
    }

    private static function factor($unit): float
    {
        $value = is_array($unit) ? ($unit['factor_to_base'] ?? null) : ($unit->factor_to_base ?? null);

        return (float) ($value ?: 0);
    }
}
