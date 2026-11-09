{{--
    Vẽ cảnh kho dạng isometric từ danh sách khối hộp (dùng cho trang đăng nhập).
    $boxes: mỗi phần tử [x, y, z, rộng(x), sâu(y), cao(z), tông màu] - truyền theo thứ tự vẽ (xa -> gần, thấp -> cao);
    tông 'floor' cao 0 = tấm sàn mờ dưới cảnh.
    Phần tử thứ 8 (tuỳ chọn) = class hiệu ứng bọc riêng khối đó (vd 'iso-float'); phần tử thứ 9 = cao độ z của mặt
    bên dưới để vẽ bóng đổ (class 'iso-float-shadow').
    $markers (tuỳ chọn): ghim vị trí [x, y, z ghim, z chân] - chấm sáng + vòng lan (class 'iso-ping') + nét đứt xuống chân.
    $class: class của thẻ svg.
--}}
@php
    $tones = [
        'rack'   => ['#5E8DBD', '#3A6896', '#2B5580'],
        'board'  => ['#6F9DCB', '#3D6C9A', '#2E5A86'],
        'blue'   => ['#8CC0EF', '#4A92D4', '#2F72B0'],
        'pale'   => ['#D3E7F8', '#9DC4E8', '#76A6D4'],
        'accent' => ['#86E6F4', '#25C3DC', '#1597AE'],
        'pallet' => ['#5584B3', '#3A6894', '#2C557D'],
        'floor'  => ['rgba(255,255,255,0.05)', 'none', 'none'],
    ];
    $stroke = 'rgba(11,44,79,0.35)';
    $minX = INF; $minY = INF; $maxX = -INF; $maxY = -INF;
    $project = function ($x, $y, $z) use (&$minX, &$minY, &$maxX, &$maxY) {
        $sx = round(($x - $y) * 0.866, 1);
        $sy = round(($x + $y) * 0.5 - $z, 1);
        $minX = min($minX, $sx); $maxX = max($maxX, $sx);
        $minY = min($minY, $sy); $maxY = max($maxY, $sy);
        return $sx . ',' . $sy;
    };
    $svg = '';
    foreach ($boxes as $box) {
        [$x, $y, $z, $w, $d, $h, $tone] = $box;
        $anim = $box[7] ?? null;
        [$top, $left, $right] = $tones[$tone];
        if ($anim && isset($box[8])) {
            $sz = $box[8];
            $shadow = implode(' ', array_map(fn ($p) => $project(...$p), [[$x, $y, $sz], [$x + $w, $y, $sz], [$x + $w, $y + $d, $sz], [$x, $y + $d, $sz]]));
            $svg .= '<polygon class="iso-float-shadow" points="' . $shadow . '" fill="rgba(4,20,40,0.35)"/>';
        }
        if ($anim) {
            $svg .= '<g class="' . e($anim) . '">';
        }
        $faceTop = [[$x, $y, $z + $h], [$x + $w, $y, $z + $h], [$x + $w, $y + $d, $z + $h], [$x, $y + $d, $z + $h]];
        $faceLeft = [[$x, $y + $d, $z], [$x + $w, $y + $d, $z], [$x + $w, $y + $d, $z + $h], [$x, $y + $d, $z + $h]];
        $faceRight = [[$x + $w, $y, $z], [$x + $w, $y + $d, $z], [$x + $w, $y + $d, $z + $h], [$x + $w, $y, $z + $h]];
        foreach ([[$faceLeft, $left], [$faceRight, $right], [$faceTop, $top]] as [$face, $fill]) {
            $pts = implode(' ', array_map(fn ($p) => $project(...$p), $face));
            if ($fill === 'none') {
                continue;
            }
            $svg .= '<polygon points="' . $pts . '" fill="' . $fill . '" stroke="' . ($tone === 'floor' ? 'none' : $stroke) . '" stroke-width="0.8" stroke-linejoin="round"/>';
        }
        if ($anim) {
            $svg .= '</g>';
        }
    }
    foreach ($markers ?? [] as [$mx, $my, $mz, $mBase]) {
        [$px, $py] = explode(',', $project($mx, $my, $mz));
        [$bx, $by] = explode(',', $project($mx, $my, $mBase));
        $svg .= '<g class="iso-marker">'
            . '<line x1="' . $bx . '" y1="' . $by . '" x2="' . $px . '" y2="' . $py . '" stroke="#25C3DC" stroke-width="1.2" stroke-dasharray="3 3" opacity="0.7"/>'
            . '<circle class="iso-ping" cx="' . $px . '" cy="' . $py . '" r="5" fill="none" stroke="#25C3DC" stroke-width="1.5"/>'
            . '<circle cx="' . $px . '" cy="' . $py . '" r="4.5" fill="#86E6F4" stroke="#fff" stroke-width="1.2"/>'
            . '</g>';
    }
    $pad = 18;
    $viewBox = ($minX - $pad) . ' ' . ($minY - $pad) . ' ' . ($maxX - $minX + 2 * $pad) . ' ' . ($maxY - $minY + 2 * $pad);
@endphp
<svg class="{{ $class ?? '' }}" viewBox="{{ $viewBox }}" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    {!! $svg !!}
</svg>
