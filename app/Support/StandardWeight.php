<?php

namespace App\Support;

/**
 * KIỂM SOÁT KHỐI LƯỢNG (KSKL) ỐNG CHUẨN - một chỗ duy nhất định nghĩa công thức.
 *
 * Theo đúng query Q_ChuanKSKL của phần mềm cũ SMS 2.0:
 *
 *      KL thực   = (KL Bì + Chuẩn trước khi dùng) - (KL Bì sau khi dùng)
 *      Độ lệch % = |KL thực - Tổng đã xuất| / Tổng đã xuất x 100
 *
 * - Chỉ tính khi ĐÃ CÂN BÌ SAU KHI DÙNG: ống đang dùng dở chưa có KL thực, so lượng
 *   nhập với lượng đã xuất là sai (ống 1.000 mg mới dùng 5 mg sẽ ra 19.900%).
 * - Cân luôn ra KHỐI LƯỢNG: chất chuẩn có ĐVT khối lượng (mg, g...) thì số cân ghi
 *   theo đúng ĐVT đó; ĐVT thể tích (ml...) thì số cân ghi theo mg và quy ra thể tích
 *   bằng tỉ trọng d (g/ml) của danh mục: KL thực (ml) = chênh lệch (mg) / 1000 / d.
 * - Giới hạn % theo dạng chuẩn và quy cách (lượng nhập quy ra mg), bảng giữ nguyên
 *   như trước.
 */
class StandardWeight
{
    /** Đơn vị ghi số cân của ống: ĐVT khối lượng thì chính nó, còn lại cân theo mg. */
    public static function weightUnit(?string $unitGroup, ?string $unitShort): string
    {
        return $unitGroup === 'mass' && $unitShort ? $unitShort : 'mg';
    }

    /**
     * KL thực đã lấy ra khỏi ống, theo ĐVT của phòng. null khi chưa tính được.
     *
     * @return array{0: ?float, 1: string} [KL thực, trạng thái]
     *         trạng thái: ok | no_gross | no_tare | no_density | not_weighable
     */
    public static function actual($gross, $tare, ?string $unitGroup, $unitFactor, $density): array
    {
        if ($gross === null) {
            return [null, 'no_gross'];
        }
        if ($tare === null) {
            return [null, 'no_tare'];
        }

        $diff = (float) $gross - (float) $tare;

        if ($unitGroup === 'mass') {
            return [$diff, 'ok'];
        }

        if ($unitGroup === 'volume') {
            if (! $density || (float) $density <= 0) {
                return [null, 'no_density'];
            }

            // mg -> g -> ml (đơn vị gốc nhóm thể tích) -> ĐVT của phòng
            $factor = (float) $unitFactor > 0 ? (float) $unitFactor : 1.0;

            return [$diff / 1000 / (float) $density / $factor, 'ok'];
        }

        return [null, 'not_weighable'];
    }

    /** Độ lệch % giữa KL thực và tổng đã xuất (cùng ĐVT), null khi chưa tính được. */
    public static function deviation(?float $actual, float $used): ?float
    {
        if ($actual === null || $used <= 0) {
            return null;
        }

        return round(abs(($actual - $used) / $used) * 100, 1);
    }

    /** Giới hạn độ lệch % theo dạng chuẩn và quy cách ống (lượng nhập quy ra mg). */
    public static function limit(?string $form, float $imported, ?string $unitGroup, $unitFactor): int
    {
        // factor_to_base: nhóm khối lượng gốc là g, nhóm thể tích gốc là ml (coi 1 ml ~ 1 g)
        $quicach = in_array($unitGroup, ['mass', 'volume'], true)
            ? $imported * ((float) $unitFactor > 0 ? (float) $unitFactor : 1.0) * 1000
            : $imported;

        return match ($form) {
            'Dạng Bột Rời' => $quicach < 10 ? 50 : ($quicach <= 100 ? 60 : 70),
            'Dạng Bột Mịn' => $quicach < 10 ? 20 : ($quicach <= 100 ? 30 : 50),
            'Dạng Sệt' => $quicach < 10 ? 10 : ($quicach <= 100 ? 20 : 50),
            default => 0,
        };
    }

    /** Lời giải thích ngắn cho ô Kết quả khi chưa tính được độ lệch. */
    public static function statusLabel(string $status, float $used): string
    {
        if ($used <= 0) {
            return 'Chưa sử dụng';
        }

        return match ($status) {
            'no_gross' => 'Chưa cân Bì + Chuẩn',
            'no_tare' => 'Đang sử dụng - chưa cân bì sau dùng',
            'no_density' => 'Thiếu tỉ trọng d',
            'not_weighable' => 'ĐVT không quy được khối lượng',
            default => 'Chưa tính',
        };
    }
}
