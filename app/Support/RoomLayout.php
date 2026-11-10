<?php

namespace App\Support;

/**
 * MẶT BẰNG KHO/PHÒNG ĐA GIÁC + CỬA
 *
 * Một phòng là đa giác đơn (không tự cắt) toạ độ mét, các góc chạy theo chiều kim đồng hồ khi nhìn từ trên xuống
 * (x sang phải, y xuống dưới). Phòng chữ nhật cũ (layout_width × layout_depth, chưa có layout_polygon) được coi là
 * đa giác 4 góc [0,0] [w,0] [w,d] [0,d] - cạnh 0 = tường trên, 1 = phải, 2 = dưới, 3 = trái.
 *
 * Cửa nằm trên một cạnh: cạnh i đi từ góc i tới góc i+1, offset tính từ góc i.
 * Mọi màn hình đọc mặt bằng (Định Khu, Tồn Kho VT/HC/CC, Tra cứu công khai) dùng attach() để nhận cùng một dạng.
 */
class RoomLayout
{
    public const MAX_POINTS = 48;

    public const DOOR_MIN = 0.5;

    public const DOOR_MAX = 4.0;

    public const DOOR_DEFAULT = 1.0;

    /** Cạnh của phòng chữ nhật cũ ứng với tên tường cũ. */
    private const LEGACY_WALLS = ['top' => 0, 'right' => 1, 'bottom' => 2, 'left' => 3];

    public static function rectangle(float $width, float $depth): array
    {
        return [[0.0, 0.0], [$width, 0.0], [$width, $depth], [0.0, $depth]];
    }

    /** Chuỗi JSON gọn của đa giác (không khoảng trắng) để lưu cột text. */
    public static function encode(array $polygon): string
    {
        return json_encode(array_map(fn ($p) => [round((float) $p[0], 2), round((float) $p[1], 2)], $polygon));
    }

    /** @return array<int, array{0: float, 1: float}>|null */
    public static function decodePolygon($value): ?array
    {
        $list = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($list) || count($list) < 3 || count($list) > self::MAX_POINTS) {
            return null;
        }

        $out = [];
        foreach ($list as $point) {
            if (! is_array($point) || count($point) < 2 || ! is_numeric($point[0]) || ! is_numeric($point[1])) {
                return null;
            }
            $out[] = [(float) $point[0], (float) $point[1]];
        }

        return $out;
    }

    /** Dạng chuẩn của mặt bằng một kho/phòng (dòng warehouses), null nếu chưa khai. */
    public static function payload($row): ?array
    {
        $polygon = self::decodePolygon($row->layout_polygon ?? null);

        if (! $polygon) {
            $w = (float) ($row->layout_width ?? 0);
            $d = (float) ($row->layout_depth ?? 0);

            if ($w <= 0 || $d <= 0) {
                return null;
            }

            $polygon = self::rectangle($w, $d);
        }

        [$width, $depth] = self::bounds($polygon);

        return [
            'polygon' => $polygon,
            'door' => self::door($row, $polygon),
            'width' => $width,
            'depth' => $depth,
            'is_rectangle' => self::isRectangle($polygon),
        ];
    }

    /** Gắn `layout_room` (payload) vào từng dòng kho/phòng - dùng cho zoneOptions() của các màn hình. */
    public static function attach($rows)
    {
        return collect($rows)->map(function ($row) {
            $row->layout_room = self::payload($row);

            return $row;
        });
    }

    /** Nhãn module của loại lưu trữ ở bảng locations.item_type. */
    private const TYPE_LABELS = ['chemical' => 'Hoá chất', 'standard' => 'Chất chuẩn', 'material' => 'Vật tư'];

    /**
     * Kệ/tủ chỉ có ô dành cho module KHÁC (không có ô dùng chung hay ô của loại $ownType): màn Tồn Kho không có
     * hàng ở đây nhưng vẫn vẽ mờ xám trên Mặt bằng / 3D cho khớp Định Khu.
     *
     * @return array<int, string> shelf_id => nhãn module (vd. "Vật tư")
     */
    public static function foreign(int $departmentId, string $ownType): array
    {
        $rows = \Illuminate\Support\Facades\DB::table('locations')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->whereNotNull('shelf_id')
            ->groupBy('shelf_id', 'item_type')
            ->select('shelf_id', 'item_type')
            ->get()
            ->groupBy('shelf_id');

        $out = [];

        foreach ($rows as $shelfId => $types) {
            $types = $types->pluck('item_type')->all();

            if (in_array(null, $types, true) || in_array($ownType, $types, true)) {
                continue;
            }

            $out[$shelfId] = implode(' / ', array_map(fn ($t) => self::TYPE_LABELS[$t] ?? $t, $types));
        }

        return $out;
    }

    public static function door($row, array $polygon): ?array
    {
        $data = is_string($row->layout_door ?? null) ? json_decode($row->layout_door, true) : null;
        $n = count($polygon);

        if (is_array($data) && isset($data['edge'], $data['offset'], $data['width'])) {
            $edge = (int) $data['edge'];

            if ($edge >= 0 && $edge < $n) {
                $len = self::edgeLength($polygon, $edge);
                $width = max(self::DOOR_MIN, min((float) $data['width'], self::DOOR_MAX, $len));
                $offset = max(0.0, min((float) $data['offset'], $len - $width));

                return ['edge' => $edge, 'offset' => round($offset, 2), 'width' => round($width, 2)];
            }

            return null;
        }

        // Cửa kiểu cũ: tên tường + cách góc tường (đo từ trái / từ trên), rộng 1 m - chỉ có ở phòng chữ nhật.
        $wall = $row->layout_door_wall ?? null;

        if ($wall && isset(self::LEGACY_WALLS[$wall]) && $n === 4) {
            $edge = self::LEGACY_WALLS[$wall];
            $len = self::edgeLength($polygon, $edge);
            $width = min(self::DOOR_DEFAULT, $len);
            $offset = max(0.0, min((float) ($row->layout_door_offset ?? 0), $len - $width));

            // Cạnh dưới và cạnh trái đi ngược chiều đo cũ (từ trái / từ trên) nên đổi đầu mốc.
            if (in_array($wall, ['bottom', 'left'], true)) {
                $offset = max(0.0, $len - $offset - $width);
            }

            return ['edge' => $edge, 'offset' => round($offset, 2), 'width' => round($width, 2)];
        }

        return null;
    }

    /** @return array{0: float, 1: float} chiều ngang / sâu của khung bao (gốc 0,0) */
    public static function bounds(array $polygon): array
    {
        return [
            round(max(array_map(fn ($p) => $p[0], $polygon)), 2),
            round(max(array_map(fn ($p) => $p[1], $polygon)), 2),
        ];
    }

    public static function edgeLength(array $polygon, int $edge): float
    {
        $a = $polygon[$edge];
        $b = $polygon[($edge + 1) % count($polygon)];

        return hypot($b[0] - $a[0], $b[1] - $a[1]);
    }

    /** Diện tích có dấu (dương = chạy theo chiều kim đồng hồ khi nhìn từ trên xuống). */
    public static function signedArea(array $polygon): float
    {
        $sum = 0.0;
        $n = count($polygon);

        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $sum += $polygon[$i][0] * $polygon[$j][1] - $polygon[$j][0] * $polygon[$i][1];
        }

        return $sum / 2;
    }

    public static function isRectangle(array $polygon): bool
    {
        if (count($polygon) !== 4) {
            return false;
        }

        [$w, $d] = self::bounds($polygon);

        return self::encode($polygon) === self::encode(self::rectangle($w, $d));
    }

    /** Đa giác đơn: không trùng góc liên tiếp và hai cạnh không kề nhau không cắt nhau. */
    public static function isSimple(array $polygon): bool
    {
        $n = count($polygon);

        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;

            if (abs($polygon[$i][0] - $polygon[$j][0]) < 0.005 && abs($polygon[$i][1] - $polygon[$j][1]) < 0.005) {
                return false;
            }
        }

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($j === $i + 1 || ($i === 0 && $j === $n - 1)) {
                    continue;
                }

                if (self::segmentsTouch($polygon[$i], $polygon[($i + 1) % $n], $polygon[$j], $polygon[($j + 1) % $n])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Khung chữ nhật [trái, trên, phải, dưới] nằm trọn trong đa giác (được chạm tường): 4 góc và các điểm dọc 4 cạnh
     * đều trong đa giác, không có cạnh phòng nào cắt xuyên qua khung, không góc phòng nào nằm lọt trong khung.
     */
    public static function contains(array $polygon, array $box): bool
    {
        [$l, $t, $r, $b] = $box;
        $corners = [[$l, $t], [$r, $t], [$r, $b], [$l, $b]];

        for ($i = 0; $i < 4; $i++) {
            $p = $corners[$i];
            $q = $corners[($i + 1) % 4];

            for ($k = 0; $k <= 6; $k++) {
                $s = $k / 6;

                if (! self::pointIn($polygon, [$p[0] + ($q[0] - $p[0]) * $s, $p[1] + ($q[1] - $p[1]) * $s])) {
                    return false;
                }
            }

            $n = count($polygon);
            for ($j = 0; $j < $n; $j++) {
                if (self::properCross($p, $q, $polygon[$j], $polygon[($j + 1) % $n])) {
                    return false;
                }
            }
        }

        foreach ($polygon as $v) {
            if ($v[0] > $l + 0.001 && $v[0] < $r - 0.001 && $v[1] > $t + 0.001 && $v[1] < $b - 0.001) {
                return false;
            }
        }

        return true;
    }

    /** Điểm trong đa giác hoặc nằm trên cạnh. */
    public static function pointIn(array $polygon, array $p): bool
    {
        $n = count($polygon);
        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $a = $polygon[$i];
            $b = $polygon[$j];

            if (abs(self::cross($a, $b, $p)) < 0.0005 && $p[0] >= min($a[0], $b[0]) - 0.0005 && $p[0] <= max($a[0], $b[0]) + 0.0005
                && $p[1] >= min($a[1], $b[1]) - 0.0005 && $p[1] <= max($a[1], $b[1]) + 0.0005) {
                return true;
            }

            if (($a[1] > $p[1]) !== ($b[1] > $p[1]) && $p[0] < ($b[0] - $a[0]) * ($p[1] - $a[1]) / ($b[1] - $a[1]) + $a[0]) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private static function cross(array $a, array $b, array $c): float
    {
        return ($b[0] - $a[0]) * ($c[1] - $a[1]) - ($b[1] - $a[1]) * ($c[0] - $a[0]);
    }

    private static function properCross(array $p1, array $p2, array $q1, array $q2): bool
    {
        $e = 1e-9;
        $d1 = self::cross($q1, $q2, $p1);
        $d2 = self::cross($q1, $q2, $p2);
        $d3 = self::cross($p1, $p2, $q1);
        $d4 = self::cross($p1, $p2, $q2);

        return (($d1 > $e && $d2 < -$e) || ($d1 < -$e && $d2 > $e)) && (($d3 > $e && $d4 < -$e) || ($d3 < -$e && $d4 > $e));
    }

    /** Hai đoạn có điểm chung (kể cả chạm đầu mút / nằm chồng lên nhau). */
    private static function segmentsTouch(array $p1, array $p2, array $q1, array $q2): bool
    {
        if (self::properCross($p1, $p2, $q1, $q2)) {
            return true;
        }

        $on = fn ($a, $b, $c) => abs(self::cross($a, $b, $c)) < 0.0005
            && $c[0] >= min($a[0], $b[0]) - 0.0005 && $c[0] <= max($a[0], $b[0]) + 0.0005
            && $c[1] >= min($a[1], $b[1]) - 0.0005 && $c[1] <= max($a[1], $b[1]) + 0.0005;

        return $on($p1, $p2, $q1) || $on($p1, $p2, $q2) || $on($q1, $q2, $p1) || $on($q1, $q2, $p2);
    }
}
