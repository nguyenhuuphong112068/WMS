<?php

namespace App\Support;

/**
 * MÃ VẠCH CODE 128
 *
 * Vẽ mã vạch thành ảnh SVG ngay trong trang in, không cần thư viện ngoài và không
 * cần máy in có font mã vạch riêng - máy in nào in được trang web là in được nhãn.
 *
 * Tự chọn giữa bộ mã B (chữ + số) và bộ mã C (nén 2 chữ số vào 1 ký hiệu). Mã xuất
 * nhập của hệ thống toàn chữ số nên phần lớn rơi vào bộ C, nhãn 60x40mm nhờ vậy đủ
 * chỗ cho vạch đủ dày để máy quét đọc được.
 */
class Barcode128
{
    /**
     * Độ rộng 6 phần tử (vạch/khoảng trắng xen kẽ, bắt đầu bằng vạch) của 107 ký hiệu
     * Code 128 theo chuẩn. Ký hiệu 106 (Stop) có 7 phần tử.
     */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    private const START_B = 104;

    private const START_C = 105;

    private const STOP = 106;

    /** Chuyển sang bộ mã B khi đang ở bộ mã C, và ngược lại. */
    private const CODE_B = 100;

    private const CODE_C = 99;

    /** Khoảng trắng bắt buộc hai đầu mã vạch, tính theo module. Thiếu là máy quét không bắt được. */
    private const QUIET_ZONE = 10;

    /**
     * Vẽ mã vạch thành thẻ <svg> tự co giãn theo ô chứa.
     *
     * SVG dùng viewBox theo đơn vị module nên chỉ cần đặt width/height bằng CSS là
     * vạch luôn sắc nét ở mọi độ phân giải máy in.
     *
     * @param  string  $data  Nội dung mã vạch, chỉ nhận ASCII in được (32-126)
     * @return string Thẻ SVG, hoặc chuỗi rỗng nếu nội dung không mã hoá được
     */
    public static function svg(string $data): string
    {
        $bars = self::bars($data);

        if (! $bars) {
            return '';
        }

        [$rects, $width] = $bars;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' 100" '
            .'preserveAspectRatio="none" shape-rendering="crispEdges">';

        foreach ($rects as $rect) {
            $svg .= '<rect x="'.$rect[0].'" y="0" width="'.$rect[1].'" height="100" fill="#000"/>';
        }

        return $svg.'</svg>';
    }

    /**
     * Danh sách vạch đen [vị trí bắt đầu, độ rộng] tính theo module, kèm tổng bề rộng.
     *
     * @return array{0: array<int, array{0: int, 1: int}>, 1: int}|null
     */
    private static function bars(string $data): ?array
    {
        $codes = self::values($data);

        if (! $codes) {
            return null;
        }

        // Ký hiệu kiểm tra: (ký hiệu bắt đầu + tổng vị_trí x giá_trị) chia lấy dư 103
        $checksum = $codes[0];

        foreach (array_slice($codes, 1) as $index => $code) {
            $checksum += ($index + 1) * $code;
        }

        $codes[] = $checksum % 103;
        $codes[] = self::STOP;

        $rects = [];
        $cursor = self::QUIET_ZONE;

        foreach ($codes as $code) {
            $widths = str_split(self::PATTERNS[$code]);

            foreach ($widths as $index => $width) {
                // Phần tử ở vị trí chẵn là vạch đen, vị trí lẻ là khoảng trắng
                if ($index % 2 === 0) {
                    $rects[] = [$cursor, (int) $width];
                }

                $cursor += (int) $width;
            }
        }

        return [$rects, $cursor + self::QUIET_ZONE];
    }

    /**
     * Nội dung cần in -> danh sách giá trị ký hiệu Code 128 (đã gồm ký hiệu bắt đầu).
     *
     * Nguyên tắc chuyển bộ mã: chuỗi chữ số dài từ 6 trở lên (hoặc từ 4 trở lên nếu
     * nằm cuối chuỗi) thì dồn sang bộ C cho ngắn; chuỗi lẻ thì in ký tự đầu bằng bộ B
     * để phần còn lại chia hết cho 2.
     *
     * @return array<int, int>|null null khi nội dung có ký tự nằm ngoài ASCII in được
     */
    private static function values(string $data): ?array
    {
        $length = strlen($data);

        if ($length === 0) {
            return null;
        }

        for ($i = 0; $i < $length; $i++) {
            $ord = ord($data[$i]);

            if ($ord < 32 || $ord > 126) {
                return null;
            }
        }

        $useC = self::digitRun($data, 0) >= 4;

        $codes = [$useC ? self::START_C : self::START_B];
        $mode = $useC ? 'C' : 'B';
        $position = 0;

        while ($position < $length) {
            $run = self::digitRun($data, $position);

            if ($mode === 'C') {
                if ($run < 2) {
                    $codes[] = self::CODE_B;
                    $mode = 'B';

                    continue;
                }

                // Bộ C nuốt từng cặp chữ số, chuỗi lẻ thì chừa lại ký tự cuối cho bộ B
                $pairs = intdiv($run, 2);

                for ($i = 0; $i < $pairs; $i++) {
                    $codes[] = (int) substr($data, $position, 2);
                    $position += 2;
                }

                if ($position < $length) {
                    $codes[] = self::CODE_B;
                    $mode = 'B';
                }

                continue;
            }

            if ($run >= 6 || ($run >= 4 && $position + $run === $length)) {
                if ($run % 2 === 1) {
                    $codes[] = ord($data[$position]) - 32;
                    $position++;
                }

                $codes[] = self::CODE_C;
                $mode = 'C';

                continue;
            }

            $codes[] = ord($data[$position]) - 32;
            $position++;
        }

        return $codes;
    }

    /** Số chữ số liên tiếp bắt đầu từ vị trí $position. */
    private static function digitRun(string $data, int $position): int
    {
        $run = 0;
        $length = strlen($data);

        while ($position + $run < $length && ctype_digit($data[$position + $run])) {
            $run++;
        }

        return $run;
    }
}
