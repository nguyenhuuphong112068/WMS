{{--
|--------------------------------------------------------------------------
| MÔ HÌNH 3D MỘT KỆ/TỦ (CSS 3D thuần, không thư viện - máy chủ không có internet)
|--------------------------------------------------------------------------
| Dùng chung cho Dữ Liệu Gốc > Định Khu (khung Thông số kệ) và tab "Theo định khu" của Tồn Kho
| Vật Tư / Hoá Chất / Chất Chuẩn.
|
|   stage.innerHTML = WmsShelf3d.markup({ tools: '...', legend: '...' });
|   WmsShelf3d.render(stage, {
|       unit: 16, gap: 3, pad: 7, tierHeight: 46, boxHeight: 0,
|       levels: <số tầng cao nhất>,
|       columns: [{ label, code, tiers: [{ label, first, level (0 = hàng dưới cùng), max, depth, texture,
|                   cells: [{ p, d, cls, title, key, color, box, dim, hit, label, flag }] }] }]
|   });
|
| code của cột hiện dưới tên cột (trên nóc cột); label của ô (đoạn mã vị trí, vd. A1) in nằm trên mặt ô / nắp thùng.
| Hai nhãn này bỏ qua khi vẽ trong cả phòng (compact). flag = ô / thùng cần báo động (vd. tương kỵ hoá chất): viền đỏ
| + dấu "!" đỏ trên nắp thùng, vẽ cả trong cả phòng. Kệ trong phòng có clash = vách + nhãn kệ tô đỏ.
| p = ô ngang (1 = trái), d = dãy sâu (1 = sát mép trước). box = vẽ thùng hàng nổi lên (cao boxHeight),
| không thì vẽ ô phẳng trên mặt tầng. Ô có key mang data-w3d-key để trang gắn sự kiện bấm.
| Kéo chuột để xoay, nhấp đúp về góc nghiêng; [data-w3d-view] đổi góc nhìn, [data-w3d-zoom] phóng to;
| Shift + lăn chuột (khung mở to: lăn chuột) phóng to / thu nhỏ quanh con trỏ.
| Đặt stage.dataset.hl = columns|tiers|max|depth để làm sáng một chiều của kệ.
| Cả phòng (WmsShelf3d.renderRoom): sàn đa giác + tường + cửa trên một cạnh, nhãn kệ / thẻ chi tiết hàng luôn hướng về
| người xem (billboard) và giữ cỡ chữ khi phóng to thu nhỏ. Nút Xuyên tủ (chỉ cả phòng): mặt cắt tiến dần vào phòng, kệ đã
| đi qua ẩn hẳn, tỉ lệ phóng to / góc nhìn giữ nguyên.
| Tìm kiếm: thêm class is-seek vào stage để thùng / ô is-hit nhấp nháy; WmsShelf3d.focus(stage, { maxZoom })
| phóng to + dời khung tới các ô đó (có hiệu ứng bay tới), focus(stage, { reset: true }) bay về toàn cảnh.
| Mở to / thu về (nút hoặc Esc) phát sự kiện 'w3d:full' trên stage (detail.on).
--}}
@once
<style>
    .w3d {
        position: relative;
        min-height: 420px;
        border: 1px solid #e2e8f0;
        border-radius: var(--border-radius-lg, 12px);
        overflow: hidden;
        background: radial-gradient(ellipse at 50% 30%, #fff 0%, var(--primary-soft) 100%);
        box-shadow: var(--shadow-sm);
        user-select: none;
    }

    .w3d-tools {
        position: absolute;
        top: 10px;
        left: 10px;
        right: 10px;
        z-index: 2;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: space-between;
        pointer-events: none;
    }

    .w3d-tools > * {
        pointer-events: auto;
    }

    .w3d-group {
        display: inline-flex;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: var(--border-radius-md, 8px);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .w3d-group button {
        border: none;
        background: none;
        padding: 4px 10px;
        font-size: 0.74rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .w3d-group button + button {
        border-left: 1px solid #e2e8f0;
    }

    .w3d-group button:hover {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .w3d-group button.active {
        background: var(--primary);
        color: #fff;
    }

    .w3d-scene {
        position: absolute;
        inset: 0;
        perspective: 1600px;
        perspective-origin: 50% 40%;
        cursor: grab;
        touch-action: none;
    }

    .w3d.is-dragging .w3d-scene {
        cursor: grabbing;
    }

    .w3d-world {
        position: absolute;
        left: 50%;
        top: 55%;
        width: 0;
        height: 0;
        transform-style: preserve-3d;
        transition: transform 0.35s ease;
    }

    .w3d.is-dragging .w3d-world {
        transition: none;
    }

    .w3d-world > *,
    .w3d-unit > * {
        position: absolute;
        left: 0;
        top: 0;
        transform-origin: 0 0;
    }

    .w3d-floor {
        background: radial-gradient(ellipse at center, rgba(var(--primary-rgb), 0.2) 0%, rgba(var(--primary-rgb), 0) 70%);
    }

    .w3d-back {
        background: rgba(var(--primary-rgb), 0.04);
        border: 1px solid rgba(var(--primary-rgb), 0.12);
    }

    /* vách giữa các cột: mép trước + mép sau là 2 trụ kệ */
    .w3d-side {
        background: rgba(var(--primary-rgb), 0.07);
        border-left: 3px solid var(--primary-light);
        border-right: 3px solid var(--primary-lighter);
        transition: background 0.2s;
    }

    .w3d-cap {
        background: rgba(var(--primary-rgb), 0.05);
        border: 1px solid rgba(var(--primary-rgb), 0.25);
    }

    .w3d-board {
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid var(--primary-light);
        transform-style: preserve-3d;
        transition: background 0.2s;
    }

    .w3d-board.is-texture {
        background-image:
            linear-gradient(90deg, rgba(var(--primary-rgb), 0.35) 1px, transparent 1px),
            linear-gradient(rgba(var(--primary-rgb), 0.35) 1px, transparent 1px);
        background-size: var(--ux) var(--uy);
        background-position: var(--pad);
    }

    /* nẹp mép trước của tầng; Tầng 01 tô đậm */
    .w3d-lip {
        background: var(--primary-lighter);
    }

    .w3d-lip.is-first {
        background: var(--primary);
    }

    .w3d-cell {
        position: absolute;
        border-radius: 2px;
        border: 1px solid #A9B4C2;
        background: #EEF2F6;
    }

    .w3d-cell[style*="--c"] {
        background: var(--c);
        border-color: rgba(0, 0, 0, 0.25);
    }

    /* thùng hàng nổi trên mặt tầng: 5 mặt, mặt bên tối hơn mặt trên */
    .w3d-box {
        position: absolute;
        transform-style: preserve-3d;
    }

    .w3d-f {
        position: absolute;
        left: 0;
        top: 0;
        transform-origin: 0 0;
        background: var(--c, var(--primary));
        border: 1px solid rgba(0, 0, 0, 0.22);
        transition: opacity 0.2s;
    }

    .w3d-f.fr,
    .w3d-f.bk {
        box-shadow: inset 0 0 0 100px rgba(0, 0, 0, 0.12);
    }

    .w3d-f.l,
    .w3d-f.r {
        box-shadow: inset 0 0 0 100px rgba(0, 0, 0, 0.24);
    }

    [data-w3d-key] {
        cursor: pointer;
    }

    /* lọc: ô không khớp mờ đi, ô khớp phát sáng (opacity đặt trên từng mặt vì preserve-3d) */
    .w3d-cell.is-dim,
    .w3d-box.is-dim .w3d-f {
        opacity: 0.16;
    }

    .w3d-cell.is-hit,
    .w3d-box.is-hit .w3d-f.t {
        outline: 2px solid var(--accent);
        outline-offset: 1px;
    }

    .w3d-box.is-hit .w3d-f {
        border-color: var(--accent);
        border-width: 2px;
    }

    /* cờ báo động (tương kỵ hoá chất...): viền đỏ, dấu "!" đỏ ở góc nắp thùng */
    .w3d-cell.is-flag,
    .w3d-box.is-flag .w3d-f {
        border-color: #DC2626;
        border-width: 2px;
    }

    .w3d-flag {
        position: absolute;
        right: -4px;
        top: -4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #DC2626;
        color: #fff;
        font-size: 8px;
        font-style: normal;
        font-weight: 800;
        line-height: 10px;
        text-align: center;
        box-shadow: 0 0 0 1.5px #fff;
        pointer-events: none;
    }

    .w3d-unit.is-clash .w3d-lip {
        background: #DC2626;
    }

    .w3d-unit-label.is-clash {
        color: #fff;
        background: #DC2626;
        border-color: #B91C1C;
    }

    /* đang tìm kiếm (is-seek): thùng / ô khớp nhấp nháy + phát sáng để nhìn từ xa vẫn thấy */
    @keyframes w3d-seek-face {
        50% {
            background-color: #E0FBFF;
        }
    }

    @keyframes w3d-seek-glow {
        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(23, 184, 212, 0);
        }

        50% {
            box-shadow: 0 0 0 3px #fff, 0 0 16px 6px rgba(23, 184, 212, 0.9);
        }
    }

    @keyframes w3d-seek-label {
        50% {
            box-shadow: 0 0 0 5px rgba(23, 184, 212, 0.35);
        }
    }

    .w3d.is-seek .w3d-box.is-hit .w3d-f {
        animation: w3d-seek-face 1s ease-in-out infinite;
    }

    .w3d.is-seek .w3d-box.is-hit .w3d-f.t {
        animation: w3d-seek-face 1s ease-in-out infinite, w3d-seek-glow 1s ease-in-out infinite;
    }

    .w3d.is-seek .w3d-cell.is-hit {
        animation: w3d-seek-glow 1s ease-in-out infinite;
    }

    .w3d.is-seek .w3d-unit-label.is-selected {
        animation: w3d-seek-label 1s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .w3d.is-seek .w3d-box.is-hit .w3d-f,
        .w3d.is-seek .w3d-cell.is-hit,
        .w3d.is-seek .w3d-unit-label.is-selected {
            animation: none;
        }
    }

    .w3d-label {
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        color: var(--primary-dark);
        white-space: nowrap;
        text-align: center;
        pointer-events: none;
    }

    .w3d-col-label small {
        display: inline-block;
        margin-top: 3px;
        padding: 1px 6px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        color: var(--primary-dark);
        background: rgba(var(--primary-rgb), 0.12);
    }

    .w3d-col-label b {
        display: block;
    }

    /* mã vị trí in nằm trên mặt ô / nắp thùng (cỡ chữ theo cỡ ô, đặt qua --fs) */
    .w3d-cell-code {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--fs, 7px);
        font-style: normal;
        font-weight: 700;
        line-height: 1;
        letter-spacing: -0.2px;
        white-space: nowrap;
        color: var(--primary-dark);
        text-shadow: 0 0 2px rgba(255, 255, 255, 0.9);
        pointer-events: none;
    }

    .w3d-cell[style*="--c"] .w3d-cell-code,
    .w3d-f.t .w3d-cell-code {
        color: #0F172A;
    }

    .w3d-tier-label {
        color: #64748b;
    }

    .w3d-tier-label.is-first {
        color: var(--primary);
    }

    .w3d-hint {
        position: absolute;
        right: 12px;
        bottom: 8px;
        font-size: 0.72rem;
        color: #94a3b8;
        pointer-events: none;
    }

    .w3d-legend {
        position: absolute;
        left: 12px;
        bottom: 8px;
        right: 120px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px 12px;
        font-size: 0.72rem;
        color: #475569;
        pointer-events: none;
    }

    .w3d-legend i {
        display: inline-block;
        width: 11px;
        height: 11px;
        border-radius: 3px;
        margin-right: 4px;
        vertical-align: -1px;
        border: 1px solid rgba(0, 0, 0, 0.15);
    }

    .w3d-empty {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 0.85rem;
        pointer-events: none;
    }

    /* ---------- cả phòng: sàn, tường, cửa, từng kệ đặt theo mét ---------- */
    /* sàn là khung bao của phòng cắt theo đa giác (clip-path), đường viền do tường vẽ */
    .w3d-room-floor {
        background-color: rgba(255, 255, 255, 0.75);
        background-image:
            linear-gradient(90deg, rgba(var(--primary-rgb), 0.16) 1px, transparent 1px),
            linear-gradient(rgba(var(--primary-rgb), 0.16) 1px, transparent 1px);
        background-size: var(--m) var(--m);
    }

    /* nhãn billboard: xoay ngược góc nhìn của khung nên luôn quay mặt về người xem, bù tỉ lệ để chữ giữ cỡ khi zoom */
    .w3d-bb {
        transform-origin: 0 0;
        pointer-events: none;
    }

    .w3d-unit-label.w3d-bb {
        font-size: 12px;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.18);
    }

    /* thẻ chi tiết hàng hoá khớp tìm kiếm trên đầu kệ: tên, mã xuất nhập, tồn, vị trí */
    .w3d-card {
        width: 238px;
        padding: 7px 9px 8px;
        border-radius: 10px;
        background: #fff;
        border: 1.5px solid var(--accent);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.28);
        text-align: left;
        white-space: normal;
        line-height: 1.25;
        font-size: 11px;
        color: var(--text-main);
    }

    .w3d-card::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: -7px;
        width: 12px;
        height: 12px;
        margin-left: -6px;
        background: #fff;
        border-right: 1.5px solid var(--accent);
        border-bottom: 1.5px solid var(--accent);
        transform: rotate(45deg);
    }

    .w3d-card-head {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .w3d-card-head em {
        margin-left: auto;
        font-style: normal;
        font-size: 10px;
        font-weight: 700;
        color: #0E7F93;
        background: rgba(23, 184, 212, 0.14);
        border-radius: 999px;
        padding: 1px 7px;
    }

    .w3d-card-line {
        padding-top: 4px;
        margin-top: 4px;
        border-top: 1px dashed #dbe5f0;
    }

    .w3d-card-line:first-of-type {
        border-top: none;
        margin-top: 0;
        padding-top: 0;
    }

    .w3d-card-line b {
        display: block;
        font-size: 11.5px;
        color: var(--text-main);
    }

    .w3d-card-line span {
        display: block;
        color: #475569;
        font-weight: 600;
    }

    .w3d-card-line span i {
        font-style: normal;
        color: #94a3b8;
        font-weight: 500;
    }

    .w3d-card-more {
        margin-top: 5px;
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
    }

    .w3d-wall {
        background: rgba(var(--primary-rgb), 0.18);
        border-top: 3px solid var(--primary);
    }

    .w3d-door {
        background: rgba(23, 184, 212, 0.35);
        border: 1px dashed var(--accent);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .w3d-door span {
        font-size: 10px;
        font-weight: 700;
        color: #0E7F93;
    }

    .w3d-unit {
        transform-style: preserve-3d;
        cursor: pointer;
    }

    /* trong cả phòng kệ bị thu nhỏ, nhìn từ trên xuống: mặt tầng gần trong suốt để thấy thùng hàng ở tầng dưới */
    .w3d-unit .w3d-board {
        background: rgba(255, 255, 255, 0.28);
    }

    .w3d-unit .w3d-back {
        background: rgba(var(--primary-rgb), 0.02);
    }

    .w3d-unit-empty {
        background: rgba(var(--primary-rgb), 0.12);
        border: 2px dashed var(--primary-light);
        cursor: pointer;
    }

    /* kệ đang chọn / có vị trí khớp: chỉ viền sáng, vách gần trong suốt để thấy rõ thùng hàng bên trong */
    .w3d-unit.is-selected .w3d-side,
    .w3d-unit.is-selected .w3d-cap {
        background: rgba(23, 184, 212, 0.06);
        border-color: var(--accent);
    }

    .w3d-unit.is-selected .w3d-lip {
        background: var(--accent);
    }

    .w3d-unit-empty.is-selected {
        border-color: var(--accent);
        background: rgba(23, 184, 212, 0.22);
    }

    .w3d-unit.is-clash .w3d-side,
    .w3d-unit.is-clash .w3d-cap {
        background: rgba(220, 38, 38, 0.2);
        border-color: #DC2626;
    }

    .w3d-unit-label {
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid var(--primary-lighter);
    }

    /* ---------- xuyên tủ: đi bộ góc nhìn thứ nhất trong phòng (như game 3D) ---------- */
    .w3d-walk {
        display: none;
    }

    .w3d.is-room .w3d-walk {
        display: inline-flex;
    }

    /* chọn góc nhìn chỉ hiện khi đang đi bộ */
    .w3d-walk .w3d-fov {
        display: none;
    }

    .w3d.is-fp .w3d-walk .w3d-fov {
        display: inline-block;
    }

    .w3d.is-fp .w3d-scene {
        perspective-origin: 50% 50%;
        cursor: crosshair;
    }

    .w3d.is-fp .w3d-world {
        top: 50%;
        transition: none;
    }

    /* zoom không dùng khi đang đi bộ (góc nhìn cố định như mắt người) */
    .w3d.is-fp [data-w3d-zoom] {
        opacity: 0.4;
        pointer-events: none;
    }

    .w3d-cross {
        display: none;
        position: absolute;
        left: 50%;
        top: 50%;
        width: 22px;
        height: 22px;
        margin: -11px 0 0 -11px;
        pointer-events: none;
        z-index: 1;
    }

    .w3d-cross::before,
    .w3d-cross::after {
        content: '';
        position: absolute;
        background: var(--primary-dark);
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.85);
        border-radius: 1px;
    }

    .w3d-cross::before {
        left: 10px;
        top: 0;
        width: 2px;
        height: 22px;
    }

    .w3d-cross::after {
        left: 0;
        top: 10px;
        width: 22px;
        height: 2px;
    }

    .w3d.is-fp .w3d-cross {
        display: block;
    }

    .w3d-cross.is-target::before,
    .w3d-cross.is-target::after {
        background: var(--accent);
    }

    .w3d-hint .fp,
    .w3d.is-fp .w3d-hint .orbit {
        display: none;
    }

    .w3d.is-fp .w3d-hint .fp {
        display: inline;
    }

    .w3d.is-fp .w3d-hint {
        color: #475569;
        background: rgba(255, 255, 255, 0.85);
        border-radius: 6px;
        padding: 2px 8px;
    }

    .w3d-unit-label.is-selected {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    /* làm sáng một chiều của kệ */
    .w3d[data-hl="columns"] .w3d-side {
        background: rgba(var(--primary-rgb), 0.24);
        border-color: var(--primary);
    }

    .w3d[data-hl="columns"] .w3d-col-label,
    .w3d[data-hl="tiers"] .w3d-tier-label {
        color: var(--accent);
    }

    .w3d[data-hl="tiers"] .w3d-lip {
        background: var(--accent);
    }

    .w3d[data-hl="tiers"] .w3d-board {
        background: rgba(23, 184, 212, 0.16);
    }

    .w3d[data-hl="max"] .w3d-cell.d1,
    .w3d[data-hl="depth"] .w3d-cell.p1 {
        background: var(--accent) !important;
        border-color: #0E7F93 !important;
    }

    /* mở to: phủ gần kín màn hình, trên menu nhưng dưới hộp thoại (Bootstrap modal 1050, nền 1040) */
    .w3d.is-full {
        position: fixed !important;
        top: 16px;
        left: 16px;
        right: 16px;
        bottom: 16px;
        z-index: 1045;
        min-height: 0 !important;
        height: auto !important;
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.35);
    }

    body.w3d-full-open {
        overflow: hidden;
    }

    body.w3d-full-open::after {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 1044;
        background: rgba(15, 23, 42, 0.45);
    }

    @media (max-width: 768px) {
        .w3d {
            min-height: 340px;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        var PRESETS = {
            iso: { ax: -24, ay: -32 },
            front: { ax: -12, ay: 0 },
            top: { ax: -82, ay: 0 }
        };

        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function px(n) {
            return n.toFixed(1) + 'px';
        }

        // Một mặt phẳng trong không gian: đặt góc trên-trái tại (x, y, z) rồi xoay.
        function plane(cls, x, y, z, w, h, rotate) {
            return '<div class="' + cls + '" style="width:' + px(w) + ';height:' + px(h) +
                ';transform:translate3d(' + px(x) + ',' + px(y) + ',' + px(z) + ')' + (rotate ? ' ' + rotate : '') + '"></div>';
        }

        function viewOf(stage) {
            if (!stage._w3d) stage._w3d = { ax: PRESETS.iso.ax, ay: PRESETS.iso.ay, zoom: 1, fit: 1, preset: 'iso', panX: 0, panY: 0 };
            return stage._w3d;
        }

        function markup(opts) {
            opts = opts || {};
            return '<div class="w3d-tools">' +
                '<div class="w3d-group" role="group" aria-label="Góc nhìn">' +
                '<button type="button" data-w3d-view="iso"><i class="fas fa-cube mr-1"></i>Nghiêng</button>' +
                '<button type="button" data-w3d-view="front"><i class="fas fa-border-all mr-1"></i>Chính diện</button>' +
                '<button type="button" data-w3d-view="top"><i class="fas fa-map mr-1"></i>Từ trên</button>' +
                '</div>' + (opts.tools || '') +
                '<div class="w3d-group w3d-walk" role="group" aria-label="Xuyên tủ">' +
                '<button type="button" data-w3d-walk title="Đi bộ trong phòng như game 3D: W A S D di chuyển, chuột nhìn quanh, đi xuyên qua tủ">' +
                '<i class="fas fa-walking mr-1"></i>Xuyên tủ</button>' +
                [80, 100, 120].map(function(f) {
                    return '<button type="button" class="w3d-fov" data-w3d-fov="' + f + '" title="Góc nhìn ' + f + '° - số càng lớn càng thấy rộng (như camera góc rộng), không phóng to">' + f + '°</button>';
                }).join('') +
                '</div>' +
                '<div class="w3d-group" role="group" aria-label="Phóng to thu nhỏ">' +
                '<button type="button" data-w3d-zoom="-1" title="Thu nhỏ"><i class="fas fa-search-minus"></i></button>' +
                '<button type="button" data-w3d-zoom="1" title="Phóng to"><i class="fas fa-search-plus"></i></button>' +
                '<button type="button" data-w3d-full title="Mở to toàn màn hình (Esc để thu về)"><i class="fas fa-expand"></i></button>' +
                '</div>' +
                '</div>' +
                '<div class="w3d-scene"><div class="w3d-world"></div></div><div class="w3d-cross"></div>' +
                '<div class="w3d-legend">' + (opts.legend || '') + '</div>' +
                '<div class="w3d-hint"><span class="orbit"><i class="fas fa-arrows-alt mr-1"></i>Kéo để xoay · Shift + kéo để dời · Shift + lăn chuột để phóng to</span>' +
                '<span class="fp"><i class="fas fa-walking mr-1"></i>W A S D đi · Shift chạy · bấm vào khung rồi rê chuột để nhìn quanh (hoặc kéo) · lăn chuột tiến / lùi · Esc thả chuột</span></div>';
        }

        function cellMarkup(c, x, y, U, H, compact) {
            var cls = (c.cls || '') + (c.d === 1 ? ' d1' : '') + (c.p === 1 ? ' p1' : '') +
                (c.dim ? ' is-dim' : '') + (c.hit ? ' is-hit' : '') + (c.flag ? ' is-flag' : '');
            var attrs = (c.key ? ' data-w3d-key="' + esc(c.key) + '"' : '') + (c.title ? ' title="' + esc(c.title) + '"' : '');
            var style = 'left:' + px(x) + ';top:' + px(y) + ';width:' + px(U) + ';height:' + px(U) +
                (c.color ? ';--c:' + esc(c.color) : '') + (c.style || '');
            // Mã vị trí: chữ co theo độ dài để nằm gọn trong ô (tối đa ~45% cạnh ô)
            var code = '';
            if (c.label && !compact) {
                var len = String(c.label).length;
                var fs = Math.max(4, Math.min(U * 0.45, (U - 2) / Math.max(1, len) * 1.6));
                code = '<em class="w3d-cell-code" style="--fs:' + fs.toFixed(1) + 'px">' + esc(c.label) + '</em>';
            }
            if (c.flag) code += '<i class="w3d-flag">!</i>';

            if (!c.box || !H) {
                return '<span class="w3d-cell ' + cls + '" style="' + style + '"' + attrs + '>' + code + '</span>';
            }

            // Thùng hàng: mặt trên nâng lên H, 4 mặt đứng dựng từ mép ô lên (trục z của mặt tầng hướng lên trên).
            var s = px(U), h = px(H);
            return '<span class="w3d-box ' + cls + '" style="' + style + '"' + attrs + '>' +
                '<i class="w3d-f t" style="width:' + s + ';height:' + s + ';transform:translateZ(' + h + ')">' + code + '</i>' +
                '<i class="w3d-f fr" style="top:' + s + ';width:' + s + ';height:' + h + ';transform:rotateX(90deg)"></i>' +
                '<i class="w3d-f bk" style="width:' + s + ';height:' + h + ';transform:rotateX(90deg)"></i>' +
                '<i class="w3d-f l" style="width:' + h + ';height:' + s + ';transform:rotateY(-90deg)"></i>' +
                '<i class="w3d-f r" style="left:' + s + ';width:' + h + ';height:' + s + ';transform:rotateY(-90deg)"></i>' +
                '</span>';
        }

        function apply(stage) {
            var v = viewOf(stage);
            var world = stage.querySelector('.w3d-world');
            if (!world) return;
            var fp = !!(v.fp && stage._w3dRoom);
            stage.classList.toggle('is-fp', fp);
            var walkBtn = stage.querySelector('[data-w3d-walk]');
            if (walkBtn) walkBtn.classList.toggle('active', fp);
            if (fp) {
                applyFp(stage, v, world);
                return;
            }
            var scene = stage.querySelector('.w3d-scene');
            if (scene) scene.style.perspective = '';
            world.style.transform = 'translate(' + Math.round(v.panX) + 'px,' + Math.round(v.panY + (v.baseY || 0)) + 'px) scale(' + (v.fit * v.zoom).toFixed(3) +
                ') rotateX(' + v.ax + 'deg) rotateY(' + v.ay + 'deg)';
            // Nhãn billboard (.w3d-bb) đọc các biến này để xoay ngược lại góc nhìn và bù tỉ lệ, luôn hướng về người xem
            world.style.setProperty('--w3d-ax', v.ax + 'deg');
            world.style.setProperty('--w3d-ay', v.ay + 'deg');
            world.style.setProperty('--w3d-inv', Math.max(0.45, Math.min(7, 1 / (v.fit * v.zoom))).toFixed(3));
            (stage._w3dUnits || []).forEach(function(u) { u.labels.forEach(function(el) { el.style.visibility = ''; }); });
            stage.querySelectorAll('[data-w3d-view]').forEach(function(btn) {
                btn.classList.toggle('active', btn.dataset.w3dView === v.preset);
            });
        }

        /*
         | Xuyên tủ = đi bộ góc nhìn thứ nhất trong cả phòng (như game 3D bắn súng): người đứng ở cửa, mắt cao 1,6 m,
         | góc nhìn cố định (mặc định 100° như camera góc rộng, chọn 80 / 100 / 120°, máy nhớ; không phóng to); W A S D / phím mũi tên đi, Shift chạy, chuột nhìn quanh (bấm vào khung
         | để khoá chuột - Pointer Lock, Esc thả; hoặc kéo chuột), lăn chuột tiến / lùi. Đi xuyên qua tủ được (tủ đi qua
         | nằm sau lưng nên trình duyệt không vẽ), ra ngoài phòng tối đa 2 m. Khi khoá chuột, bấm = bấm vào thùng hàng ở
         | tâm ngắm. Camera CSS: perspective P, thế giới đặt translateZ(P) rotateX(pitch) rotateY(yaw) translate(-vị trí).
         */
        var FP_FOVS = [80, 100, 120], FP_FOV_KEY = 'wms.w3d.fov', fpFovMem = 100, FP_EYE = 1.6, FP_SPEED = 1.4, FP_RUN = 2.8, FP_TURN = 0.15;
        var fpStage = null, fpKeys = {}, fpLast = 0, fpRelay = false;

        function fpPerspective(stage) {
            var scene = stage.querySelector('.w3d-scene');
            var w = scene && scene.clientWidth || 1000;
            return Math.round(w / 2 / Math.tan(fpFov() / 2 * Math.PI / 180));
        }

        function fpFov() {
            var f = fpFovMem;
            try {
                f = Number(localStorage.getItem(FP_FOV_KEY)) || fpFovMem;
            } catch (e) { /* trình duyệt chặn localStorage: dùng mặc định */ }
            return FP_FOVS.indexOf(f) >= 0 ? f : 100;
        }

        // Đứng ngoài cửa 1,5 m nhìn vào phòng (thấy cả phòng rồi mới bước vào); phòng không có cửa thì đứng trước giữa mép trước.
        function fpStart(stage) {
            var v = viewOf(stage), room = stage._w3dRoom;
            v.cy = -FP_EYE * room.ppm;
            v.pitch = -12;
            if (room.door) {
                v.cx = room.door.x - room.door.nx * 1.5 * room.ppm;
                v.cz = room.door.z - room.door.nz * 1.5 * room.ppm;
                v.yaw = Math.atan2(room.door.nx, -room.door.nz) * 180 / Math.PI;
            } else {
                v.cx = (room.minX + room.maxX) / 2;
                v.cz = room.maxZ + 1.5 * room.ppm;
                v.yaw = 0;
            }
            v.fpSig = room.sig;
        }

        function applyFp(stage, v, world) {
            var P = fpPerspective(stage);
            stage.querySelector('.w3d-scene').style.perspective = P + 'px';
            world.style.transform = 'translateZ(' + P + 'px) rotateX(' + v.pitch.toFixed(2) + 'deg) rotateY(' + v.yaw.toFixed(2) + 'deg) translate3d(' +
                px(-v.cx) + ',' + px(-v.cy) + ',' + px(-v.cz) + ')';
            world.style.setProperty('--w3d-ax', v.pitch.toFixed(2) + 'deg');
            world.style.setProperty('--w3d-ay', v.yaw.toFixed(2) + 'deg');
            // Nhãn giữ cỡ chữ như ở góc 75° dù đổi góc nhìn (góc rộng làm mọi thứ nhỏ lại)
            world.style.setProperty('--w3d-inv', (0.4 * Math.tan(fpFov() / 2 * Math.PI / 180) / Math.tan(37.5 * Math.PI / 180)).toFixed(3));
            // Nhãn kệ khi đứng sát / đã đi qua thì ẩn, không phình to che tầm nhìn
            var near = 1.2 * stage._w3dRoom.ppm;
            (stage._w3dUnits || []).forEach(function(u) {
                var hide = Math.hypot(u.x - v.cx, u.z - v.cz) < near;
                u.labels.forEach(function(el) { el.style.visibility = hide ? 'hidden' : ''; });
            });
            stage.querySelectorAll('[data-w3d-view]').forEach(function(btn) { btn.classList.remove('active'); });
            var fov = fpFov();
            stage.querySelectorAll('[data-w3d-fov]').forEach(function(btn) { btn.classList.toggle('active', Number(btn.dataset.w3dFov) === fov); });
            fpTarget(stage);
        }

        function fpMove(stage, forward, right, dist) {
            var v = viewOf(stage), room = stage._w3dRoom;
            var a = v.yaw * Math.PI / 180;
            // Đi được trong phòng và ra ngoài tối đa 2 m (đứng ngoài nhìn vào)
            var m = -2 * room.ppm;
            v.cx = Math.max(room.minX + m, Math.min(room.maxX - m, v.cx + (Math.sin(a) * forward + Math.cos(a) * right) * dist));
            v.cz = Math.max(room.minZ + m, Math.min(room.maxZ - m, v.cz + (-Math.cos(a) * forward + Math.sin(a) * right) * dist));
        }

        function fpLook(stage, dx, dy) {
            var v = viewOf(stage);
            v.yaw = (v.yaw + dx * FP_TURN) % 360;
            v.pitch = Math.max(-80, Math.min(80, v.pitch - dy * FP_TURN));
            apply(stage);
        }

        // Thùng hàng / ô đang nằm ở tâm ngắm
        function fpPick(stage) {
            var r = stage.querySelector('.w3d-scene').getBoundingClientRect();
            var el = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
            return el && stage.contains(el) ? el.closest('[data-w3d-key]') : null;
        }

        function fpTarget(stage) {
            var cross = stage.querySelector('.w3d-cross');
            if (cross) cross.classList.toggle('is-target', !!fpPick(stage));
        }

        function setWalk(stage, on) {
            var v = viewOf(stage);
            v.fp = !!on && !!stage._w3dRoom;
            if (v.fp) {
                fpStart(stage);
                fpStage = stage;
            } else {
                fpKeys = {};
                if (document.pointerLockElement && stage.contains(document.pointerLockElement)) document.exitPointerLock();
            }
            apply(stage);
            if (!v.fp) refit(stage);
        }

        // Cỡ vừa khung theo góc nhìn hiện tại (xoay tay không đổi cỡ, chỉ tính lại khi dựng lại / đổi cỡ khung).
        function refit(stage) {
            var box = stage._w3dBox;
            var scene = stage.querySelector('.w3d-scene');
            if (!box || !scene || !scene.clientWidth) return;
            var v = viewOf(stage);
            var ax = Math.abs(v.ax) * Math.PI / 180, ay = Math.abs(v.ay) * Math.PI / 180;
            var W = box.W, D = box.D, H = box.H;
            var projW = W * Math.cos(ay) + D * Math.sin(ay) + 120;
            var projH = H * Math.cos(ax) + (W * Math.sin(ay) + D * Math.cos(ay)) * Math.sin(ax) + 90;
            // Có thẻ chi tiết trên đầu kệ: chừa phía trên khung (thẻ giữ cỡ cố định trên màn hình) và hạ cả cảnh xuống
            var top = box.cards ? Math.min(170, scene.clientHeight * 0.3) : 0;
            v.fit = Math.max(0.15, Math.min(scene.clientWidth / projW, (scene.clientHeight - 30 - top) / projH, 1.6));
            v.baseY = top / 2;
            apply(stage);
        }

        // Dựng HTML một kệ, gốc toạ độ ở tâm khối kệ (sàn kệ ở y = +H/2). compact = bỏ nhãn cột/tầng và
        // bóng sàn (dùng khi đặt nhiều kệ trong phòng). Trả về null khi kệ chưa có cột/tầng nào.
        function buildShelf(model, compact) {
            var U = model.unit || 16, G = model.gap || 3, PAD = model.pad || 7, TH = model.tierHeight || 46;
            var BH = model.boxHeight || 0;
            var cols = (model.columns || []).filter(function(column) { return column.tiers && column.tiers.length; });
            if (!cols.length) return null;

            var span = function(n) { return n * U + (n - 1) * G + 2 * PAD; };
            var maxDepth = 1;
            var levels = Math.max(1, model.levels || 1);
            var widths = cols.map(function(column) {
                var widest = 1;
                column.tiers.forEach(function(tier) {
                    widest = Math.max(widest, tier.max || 1);
                    maxDepth = Math.max(maxDepth, tier.depth || 1);
                    levels = Math.max(levels, (tier.level || 0) + 1);
                });
                return span(widest);
            });

            var W = widths.reduce(function(a, b) { return a + b; }, 0);
            var D = span(maxDepth);
            var H = levels * TH;
            var x0 = -W / 2, top = -H / 2, floor = H / 2, back = -D / 2, front = D / 2;
            var html = '';

            if (!compact) html += plane('w3d-floor', x0 - 30, floor + 1, back - 30, W + 60, D + 60, 'rotateX(90deg)');
            html += plane('w3d-back', x0, top, back, W, H);

            var x = x0;
            cols.forEach(function(column, ci) {
                var w = widths[ci];

                html += plane('w3d-side', x, top, front, D, H, 'rotateY(90deg)');
                html += plane('w3d-cap', x, top, back, w, D, 'rotateX(90deg)');
                if (!compact) {
                    // Tên cột + mã cột (chip) trên nóc cột, mép dưới nhãn cách nóc 8px
                    var name = column.label || '', code = column.code && column.code !== name ? column.code : '';
                    if (name || code) {
                        html += '<div class="w3d-label w3d-col-label" style="width:' + px(w) + ';transform:translate3d(' +
                            px(x) + ',' + px(top - 8) + ',' + px(front) + ') translateY(-100%)">' +
                            (name ? '<b>' + esc(name) + '</b>' : '') + (code ? '<small>' + esc(code) + '</small>' : '') + '</div>';
                    }
                }

                column.tiers.forEach(function(tier) {
                    var y = floor - (tier.level || 0) * TH;
                    // Tầng ít vị trí hơn tầng rộng / sâu nhất của cột: chia đều các ô trên cả mặt tầng (ô đầu, ô cuối sát
                    // mép như tầng đầy đủ; chỉ 1 ô thì nằm giữa) thay vì dồn về góc trái-trước.
                    var nx = Math.max(1, tier.max || 1), nd = Math.max(1, tier.depth || 1);
                    var availX = w - 2 * PAD - U, availD = D - 2 * PAD - U;
                    var sx = nx > 1 ? availX / (nx - 1) : 0, sd = nd > 1 ? availD / (nd - 1) : 0;
                    var cells = (tier.cells || []).map(function(c) {
                        var cx = nx > 1 ? PAD + (c.p - 1) * sx : PAD + availX / 2;
                        var cy = nd > 1 ? D - PAD - U - (c.d - 1) * sd : PAD + availD / 2;
                        // compact (cả phòng) bỏ mã ô cho đỡ rối, trừ khi trang bật Nhãn vị trí (model.codes)
                        return cellMarkup(c, cx, cy, U, BH, compact && !model.codes);
                    }).join('');
                    var texture = tier.texture ? ';--ux:' + px(nx > 1 ? sx : w) + ';--uy:' + px(nd > 1 ? sd : D) +
                        ';--pad:' + px(nx > 1 ? PAD : PAD + availX / 2) + ' ' + px(nd > 1 ? D - PAD - U - (nd - 1) * sd : PAD + availD / 2) : '';

                    html += '<div class="w3d-board' + (tier.texture ? ' is-texture' : '') + (tier.first ? ' is-first' : '') + '"' +
                        ' style="width:' + px(w) + ';height:' + px(D) + ';transform:translate3d(' + px(x) + ',' + px(y) + ',' +
                        px(back) + ') rotateX(90deg)' + texture + '"' +
                        (tier.title ? ' title="' + esc(tier.title) + '"' : '') + '>' + cells + '</div>';
                    html += plane('w3d-lip' + (tier.first ? ' is-first' : ''), x, y, front, w, 5);

                    if (!compact && ci === 0 && tier.label) {
                        html += '<div class="w3d-label w3d-tier-label' + (tier.first ? ' is-first' : '') +
                            '" style="transform:translate3d(' + px(x - 8) + ',' + px(y - 16) + ',' + px(front) + ') translateX(-100%)">' +
                            esc(tier.label) + '</div>';
                    }
                });

                x += w;
            });
            html += plane('w3d-side', x, top, front, D, H, 'rotateY(90deg)');

            return { html: html, W: W, D: D, H: H, levels: levels, TH: TH };
        }

        function observe(stage) {
            // Khung đang ẩn (tab chưa mở) thì đo được 0: chờ khung có kích thước rồi mới canh cỡ.
            if (!stage._w3dObserver && window.ResizeObserver) {
                stage._w3dObserver = new ResizeObserver(function() { refit(stage); });
                stage._w3dObserver.observe(stage.querySelector('.w3d-scene'));
            }
            refit(stage);
        }

        function clearEmpty(stage) {
            var old = stage.querySelector('.w3d-empty');
            if (old) old.remove();
        }

        function render(stage, model) {
            var world = stage.querySelector('.w3d-world');
            if (!world) return;
            clearEmpty(stage);

            var shelf = buildShelf(model, false);
            if (!shelf) {
                world.innerHTML = '';
                stage._w3dBox = null;
                if (model.empty) stage.insertAdjacentHTML('beforeend', '<div class="w3d-empty">' + model.empty + '</div>');
                return;
            }

            world.innerHTML = shelf.html;
            stage.classList.remove('is-room', 'is-fp');
            stage._w3dUnits = null;
            stage._w3dRoom = null;
            viewOf(stage).fp = false;
            stage._w3dBox = { W: shelf.W, D: shelf.D, H: shelf.H };
            observe(stage);
        }

        /*
         | Cả một phòng: sàn đa giác (lưới 1 m), tường thấp, cửa ra vào và nhiều kệ đặt theo mét.
         |   room: { polygon: [[x, y], ...] (m, chạy theo chiều kim đồng hồ nhìn từ trên; không có thì dùng width × depth),
         |           width, depth, ppm (px/m, mặc định 100), tierMetre (chiều cao 1 tầng, m),
         |           door: { edge, offset, width } (cạnh i đi từ góc i tới góc i+1, offset đo từ góc i)
         |                 hoặc kiểu cũ { wall: top|right|bottom|left, offset, width } | null,
         |           shelves: [{ key, label, x, y (tâm, m), width, depth (m), rotation, selected, clash, model,
         |                       card: { title, count, lines: [{ name, code, qty, loc }], more } }] }
         | Trục sơ đồ: x sang phải, y xuống dưới (= hướng về phía người xem ở 3D). rotation = hướng mặt trước
         | theo chiều kim đồng hồ trên sơ đồ: 0 xuống, 90 trái, 180 lên, 270 phải.
         */
        var LEGACY_WALLS = { top: 0, right: 1, bottom: 2, left: 3 };

        function roomShape(room) {
            var poly = room.polygon && room.polygon.length >= 3
                ? room.polygon
                : [[0, 0], [room.width, 0], [room.width, room.depth], [0, room.depth]];
            var door = null;

            if (room.door && room.door.edge != null) {
                door = { edge: Number(room.door.edge), offset: Number(room.door.offset) || 0, width: Number(room.door.width) || 1 };
            } else if (room.door && room.door.wall && LEGACY_WALLS[room.door.wall] != null && poly.length === 4) {
                var edge = LEGACY_WALLS[room.door.wall];
                var len = Math.hypot(poly[(edge + 1) % 4][0] - poly[edge][0], poly[(edge + 1) % 4][1] - poly[edge][1]);
                var width = Math.min(room.door.width || 1, len);
                var off = Math.max(0, Math.min(Number(room.door.offset) || 0, len - width));
                door = { edge: edge, offset: edge === 2 || edge === 3 ? Math.max(0, len - off - width) : off, width: width };
            }

            return { poly: poly, door: door };
        }

        // Chuỗi transform đặt nhãn billboard: neo ở giữa đáy nhãn tại (x, y, z), xoay ngược góc nhìn, bù tỉ lệ khung.
        function billboard(x, y, z, factor) {
            return 'translate3d(' + px(x) + ',' + px(y) + ',' + px(z) + ') rotateY(calc(var(--w3d-ay, 0deg) * -1)) rotateX(calc(var(--w3d-ax, 0deg) * -1)) ' +
                'scale(calc(var(--w3d-inv, 1) * ' + (factor || 1) + ')) translate(-50%, -100%)';
        }

        function cardMarkup(s, X, Y, Z) {
            var c = s.card;
            var html = '<div class="w3d-label w3d-bb w3d-card" style="transform:' + billboard(X, Y, Z, 0.85) + '">' +
                '<div class="w3d-card-head"><i class="fas fa-layer-group"></i>' + esc(c.title || s.label || '') +
                (c.count ? '<em>' + esc(c.count) + '</em>' : '') + '</div>';

            (c.lines || []).forEach(function(line) {
                html += '<div class="w3d-card-line"><b>' + esc(line.name || '—') + '</b>' +
                    '<span><i>Mã xuất nhập:</i> ' + esc(line.code || '—') + '</span>' +
                    '<span><i>Tồn:</i> ' + esc(line.qty || '—') + '</span>' +
                    '<span><i>Vị trí:</i> ' + esc(line.loc || '—') + '</span></div>';
            });

            if (c.more > 0) html += '<div class="w3d-card-more">+ ' + c.more + ' hàng hoá khớp khác trên kệ này</div>';

            return html + '</div>';
        }

        function renderRoom(stage, room) {
            var world = stage.querySelector('.w3d-world');
            if (!world) return;
            clearEmpty(stage);

            var shape = roomShape(room);
            var poly = shape.poly;
            var door = shape.door;
            var PPM = room.ppm || 100;
            var bw = 0, bd = 0;
            poly.forEach(function(p) { bw = Math.max(bw, p[0]); bd = Math.max(bd, p[1]); });
            var RW = bw * PPM, RD = bd * PPM;
            var WALL = 0.3 * PPM;
            var tallest = 0;
            var html = '';

            // Sàn: khung bao cắt theo đa giác
            var clip = poly.map(function(p) { return px(p[0] * PPM) + ' ' + px(p[1] * PPM); }).join(',');
            html += '<div class="w3d-room-floor" style="width:' + px(RW) + ';height:' + px(RD) + ';--m:' + px(PPM) +
                ';clip-path:polygon(' + clip + ');transform:translate3d(' + px(-RW / 2) + ',0px,' + px(-RD / 2) + ') rotateX(90deg)"></div>';

            // Mỗi cạnh một dải tường thấp (chừa khoảng trống ở chỗ cửa).
            var n = poly.length;
            for (var e = 0; e < n; e++) {
                var P = poly[e], Q = poly[(e + 1) % n];
                var ex = Q[0] - P[0], ey = Q[1] - P[1];
                var len = Math.hypot(ex, ey);
                if (len < 0.001) continue;
                var ux = ex / len, uy = ey / len;
                var angle = Math.atan2(uy, ux) * 180 / Math.PI;
                var px0 = P[0] * PPM - RW / 2, pz0 = P[1] * PPM - RD / 2;
                var spans = [[0, len]];

                if (door && door.edge === e) {
                    var a = Math.max(0, door.offset), b = Math.min(len, door.offset + door.width);
                    spans = [[0, a], [b, len]];
                }

                spans.forEach(function(span) {
                    if (span[1] - span[0] <= 0.001) return;
                    html += plane('w3d-wall', px0 + ux * span[0] * PPM, -WALL, pz0 + uy * span[0] * PPM, (span[1] - span[0]) * PPM, WALL,
                        'rotateY(' + (-angle).toFixed(3) + 'deg)');
                });

                if (door && door.edge === e) {
                    var c0 = door.offset;
                    html += '<div class="w3d-door" style="width:' + px(door.width * PPM) + ';height:' + px(0.25 * PPM) + ';transform:translate3d(' +
                        px(px0 + ux * c0 * PPM) + ',-1px,' + px(pz0 + uy * c0 * PPM) + ') rotateY(' + (-angle).toFixed(3) + 'deg) rotateX(90deg)"><span>Cửa</span></div>';
                }
            }

            var units = [];
            (room.shelves || []).forEach(function(s, si) {
                var built = buildShelf(s.model || {}, true);
                var X = s.x * PPM - RW / 2, Z = s.y * PPM - RD / 2;
                var height = (built ? built.levels : 1) * (room.tierMetre || 0.4) * PPM;
                tallest = Math.max(tallest, height);
                units.push({ x: X, z: Z });

                if (built) {
                    var sx = s.width * PPM / built.W, sz = s.depth * PPM / built.D, sy = height / built.H;
                    html += '<div data-w3d-i="' + si + '" class="w3d-unit' + (s.selected ? ' is-selected' : '') + (s.clash ? ' is-clash' : '') + '"' +
                        (s.key ? ' data-w3d-shelf="' + esc(s.key) + '"' : '') + (s.label ? ' title="' + esc(s.label) + '"' : '') +
                        ' style="transform:translate3d(' + px(X) + ',0px,' + px(Z) + ') rotateY(' + (-(s.rotation || 0)) + 'deg) scale3d(' +
                        sx.toFixed(4) + ',' + sy.toFixed(4) + ',' + sz.toFixed(4) + ') translateY(' + px(-built.H / 2) + ')">' +
                        built.html + '</div>';
                } else {
                    // Kệ chưa có cột/tầng: chỉ vẽ khung chiếm chỗ trên sàn
                    html += '<div data-w3d-i="' + si + '" class="w3d-unit-empty' + (s.selected ? ' is-selected' : '') + '"' +
                        (s.key ? ' data-w3d-shelf="' + esc(s.key) + '"' : '') +
                        ' style="width:' + px(s.width * PPM) + ';height:' + px(s.depth * PPM) + ';transform:translate3d(' + px(X) + ',-1px,' + px(Z) +
                        ') rotateY(' + (-(s.rotation || 0)) + 'deg) translate3d(' + px(-s.width * PPM / 2) + ',0px,' + px(-s.depth * PPM / 2) +
                        ') rotateX(90deg)"></div>';
                }

                if (s.card && s.card.lines && s.card.lines.length) {
                    html += cardMarkup(s, X, -height - 10, Z).replace('<div ', '<div data-w3d-i="' + si + '" ');
                } else if (s.label) {
                    html += '<div data-w3d-i="' + si + '" class="w3d-label w3d-unit-label w3d-bb' + (s.selected ? ' is-selected' : '') +
                        (s.clash ? ' is-clash' : '') + '" style="transform:' +
                        billboard(X, -height - 8, Z, 0.85) + '">' + esc(s.label) + '</div>';
                }
            });

            world.innerHTML = html;
            stage.classList.add('is-room');
            units.forEach(function(u, i) { u.labels = Array.prototype.slice.call(world.querySelectorAll('.w3d-bb[data-w3d-i="' + i + '"]')); });
            stage._w3dUnits = units;

            // Khung phòng cho chế độ đi bộ (Xuyên tủ): giới hạn đi lại + chỗ đứng ở cửa, hướng nhìn vào trong phòng
            var pts = poly.map(function(p) { return [p[0] * PPM - RW / 2, p[1] * PPM - RD / 2]; });
            var xs = pts.map(function(p) { return p[0]; }), zs = pts.map(function(p) { return p[1]; });
            var fpRoom = {
                ppm: PPM, sig: JSON.stringify(poly), door: null,
                minX: Math.min.apply(null, xs), maxX: Math.max.apply(null, xs), minZ: Math.min.apply(null, zs), maxZ: Math.max.apply(null, zs)
            };
            if (door) {
                var A = pts[door.edge], B = pts[(door.edge + 1) % n];
                var elen = Math.hypot(B[0] - A[0], B[1] - A[1]) || 1;
                var dux = (B[0] - A[0]) / elen, duz = (B[1] - A[1]) / elen;
                var at = (door.offset + door.width / 2) * PPM;
                var dx = A[0] + dux * at, dz = A[1] + duz * at;
                var mx = xs.reduce(function(s, x) { return s + x; }, 0) / xs.length;
                var mz = zs.reduce(function(s, z) { return s + z; }, 0) / zs.length;
                var nx = -duz, nz = dux;
                if (nx * (mx - dx) + nz * (mz - dz) < 0) {
                    nx = -nx;
                    nz = -nz;
                }
                fpRoom.door = { x: dx, z: dz, nx: nx, nz: nz };
            }
            stage._w3dRoom = fpRoom;
            var rv = viewOf(stage);
            if (rv.fp && rv.fpSig !== fpRoom.sig) fpStart(stage);
            stage._w3dBox = { W: RW, D: RD, H: Math.max(tallest, WALL), cards: html.indexOf('w3d-card') >= 0 };
            if (!(room.shelves || []).length && room.empty) {
                stage.insertAdjacentHTML('beforeend', '<div class="w3d-empty">' + room.empty + '</div>');
            }
            apply(stage);
            observe(stage);
        }

        function setView(stage, preset) {
            var v = viewOf(stage);
            if (v.fp) setWalk(stage, false);
            v.ax = PRESETS[preset].ax;
            v.ay = PRESETS[preset].ay;
            v.preset = preset;
            v.panX = 0;
            v.panY = 0;
            refit(stage);
        }

        /*
         | Bay tới các ô cần chỉ (mặc định thùng / ô đang is-hit): phóng to + dời khung cho chúng nằm giữa, giữ nguyên
         | góc xoay. Vị trí đo thẳng trên màn hình (getBoundingClientRect đã tính cả xoay 3D và phối cảnh) rồi chỉnh
         | vài lần cho khớp tâm, sau đó mới cho khung trượt từ chỗ cũ tới chỗ mới.
         |   opts: { selector, maxZoom (mặc định 4), share (phần khung nhìn mà các ô chiếm, 0.4), duration (ms),
         |           reset: true = bay về toàn cảnh }
         | Trả về số ô được chỉ (0 = không có ô nào, khung giữ nguyên).
         */
        function focus(stage, opts) {
            opts = opts || {};
            var world = stage.querySelector('.w3d-world');
            var scene = stage.querySelector('.w3d-scene');
            if (!world || !scene || !scene.clientWidth) return 0;

            var targets = opts.reset ? [] : Array.prototype.slice.call(stage.querySelectorAll(opts.selector || '.w3d-box.is-hit, .w3d-cell.is-hit'));
            if (!opts.reset && !targets.length) return 0;

            var v = viewOf(stage);
            var from = world.style.transform;
            world.style.transition = 'none';

            if (targets.length) {
                var sr = scene.getBoundingClientRect();
                var measure = function() {
                    apply(stage);
                    var l = Infinity, t = Infinity, r = -Infinity, b = -Infinity;
                    targets.forEach(function(el) {
                        var rc = el.getBoundingClientRect();
                        l = Math.min(l, rc.left);
                        t = Math.min(t, rc.top);
                        r = Math.max(r, rc.right);
                        b = Math.max(b, rc.bottom);
                    });
                    return { x: (l + r) / 2 - sr.left, y: (t + b) / 2 - sr.top, w: r - l, h: b - t };
                };
                var share = opts.share || 0.4;
                var u = measure();
                var k = Math.min(sr.width * share / Math.max(u.w, 1), sr.height * share / Math.max(u.h, 1));
                v.zoom = Math.max(1, Math.min(opts.maxZoom || 4, v.zoom * k));
                // Phối cảnh làm tâm lệch đi một chút sau mỗi lần dời: chỉnh 3 lần là khớp.
                for (var i = 0; i < 3; i++) {
                    u = measure();
                    v.panX += sr.width / 2 - u.x;
                    v.panY += sr.height / 2 - u.y;
                }
            } else {
                v.zoom = 1;
                v.panX = 0;
                v.panY = 0;
            }

            apply(stage);
            var to = world.style.transform;
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var duration = reduce ? 0 : (opts.duration || 900);

            world.style.transform = from;
            world.getBoundingClientRect(); // chốt vị trí cũ để trình duyệt chạy hiệu ứng từ đó
            world.style.transition = duration ? 'transform ' + duration + 'ms cubic-bezier(0.22, 0.8, 0.24, 1)' : 'none';
            world.style.transform = to;
            clearTimeout(stage._w3dFocusTimer);
            stage._w3dFocusTimer = setTimeout(function() { world.style.transition = ''; }, duration + 50);
            return targets.length;
        }

        /* ---------- xoay / dời bằng chuột, đổi góc nhìn, phóng to ---------- */
        var drag = null;
        var justDragged = false;

        // Kéo chuột trái = xoay; Shift + kéo hoặc kéo chuột phải = dời khung nhìn.
        document.addEventListener('pointerdown', function(event) {
            var scene = event.target.closest && event.target.closest('.w3d-scene');
            if (!scene || (event.button !== 0 && event.button !== 2)) return;
            var stage = scene.closest('.w3d');
            var v = viewOf(stage);
            if (v.fp) fpStage = stage;
            if (document.pointerLockElement === scene) return;
            drag = {
                stage: stage, x: event.clientX, y: event.clientY, ax: v.ax, ay: v.ay, lx: event.clientX, ly: event.clientY,
                panX: v.panX, panY: v.panY, pan: event.shiftKey || event.button === 2, moved: false
            };
        });

        document.addEventListener('pointermove', function(event) {
            if (!drag) return;
            var dx = event.clientX - drag.x, dy = event.clientY - drag.y;
            if (!drag.moved && Math.abs(dx) + Math.abs(dy) < 5) return;
            if (!drag.moved) {
                drag.moved = true;
                drag.stage.classList.add('is-dragging');
            }
            var v = viewOf(drag.stage);
            if (v.fp) {
                // Đi bộ: kéo chuột = quay đầu nhìn quanh (như rê chuột khi đã khoá)
                fpLook(drag.stage, event.clientX - drag.lx, event.clientY - drag.ly);
                drag.lx = event.clientX;
                drag.ly = event.clientY;
                return;
            }
            if (drag.pan) {
                v.panX = drag.panX + dx;
                v.panY = drag.panY + dy;
            } else {
                v.ay = Math.round(drag.ay + dx * 0.45);
                v.ax = Math.round(Math.max(-88, Math.min(12, drag.ax - dy * 0.35)));
                v.preset = null;
            }
            apply(drag.stage);
        });

        document.addEventListener('pointerup', function() {
            if (!drag) return;
            justDragged = drag.moved;
            drag.stage.classList.remove('is-dragging');
            drag = null;
            setTimeout(function() { justDragged = false; }, 0);
        });

        document.addEventListener('contextmenu', function(event) {
            if (event.target.closest && event.target.closest('.w3d-scene')) event.preventDefault();
        });

        // Thả chuột sau khi xoay thì không tính là bấm vào ô.
        document.addEventListener('click', function(event) {
            if (justDragged && event.target.closest && event.target.closest('.w3d-scene')) {
                event.stopImmediatePropagation();
                event.preventDefault();
            }
        }, true);

        document.addEventListener('dblclick', function(event) {
            var scene = event.target.closest && event.target.closest('.w3d-scene');
            if (!scene) return;
            // Nhấp đúp vào kệ trong cả phòng là lệnh của trang (mở 3D / cấu trúc kệ), không đặt lại góc nhìn
            if (event.target.closest('[data-w3d-shelf]')) return;
            var stage = scene.closest('.w3d');
            if (viewOf(stage).fp) return;
            viewOf(stage).zoom = 1;
            setView(stage, 'iso');
        });

        // Phóng to / thu nhỏ; có (clientX, clientY) thì giữ nguyên điểm dưới con trỏ (dời khung bù lại).
        function zoomBy(stage, factor, clientX, clientY) {
            var v = viewOf(stage);
            var old = v.zoom;
            v.zoom = Math.max(0.3, Math.min(8, v.zoom * factor));
            var scene = stage.querySelector('.w3d-scene');
            if (clientX != null && scene && old) {
                var r = scene.getBoundingClientRect();
                var k = v.zoom / old;
                // gốc của .w3d-world nằm ở 50% ngang, 55% dọc của cảnh
                var ox = r.left + r.width * 0.5 + v.panX, oy = r.top + r.height * 0.55 + v.panY + (v.baseY || 0);
                v.panX += (clientX - ox) * (1 - k);
                v.panY += (clientY - oy) * (1 - k);
            }
            apply(stage);
        }

        // Mở to: khung 3D phủ gần kín màn hình, nằm dưới hộp thoại Bootstrap để bấm thùng hàng vẫn xem được chi tiết.
        function setFull(stage, on) {
            stage.classList.toggle('is-full', on);
            document.body.classList.toggle('w3d-full-open', !!document.querySelector('.w3d.is-full'));
            var btn = stage.querySelector('[data-w3d-full]');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-' + (on ? 'compress' : 'expand') + '"></i>';
                btn.title = on ? 'Thu về (Esc)' : 'Mở to toàn màn hình (Esc để thu về)';
            }
            var v = viewOf(stage);
            v.panX = 0;
            v.panY = 0;
            refit(stage);
            // Báo cho trang (vd. đang tìm kiếm thì bay lại tới ô khớp sau khi khung đổi cỡ).
            stage.dispatchEvent(new CustomEvent('w3d:full', { detail: { on: on } }));
        }

        /* ---------- Xuyên tủ: đi bộ bằng bàn phím + chuột ---------- */
        var FP_KEYS = { w: 'f', arrowup: 'f', s: 'b', arrowdown: 'b', a: 'l', arrowleft: 'l', d: 'r', arrowright: 'r' };

        function fpActive() {
            if (!fpStage || !document.body.contains(fpStage) || !viewOf(fpStage).fp) return null;
            var scene = fpStage.querySelector('.w3d-scene');
            if (document.pointerLockElement === scene || fpStage.classList.contains('is-full') || fpStage.matches(':hover')) return fpStage;
            return null;
        }

        function fpLoop(now) {
            var stage = fpActive();
            var f = (fpKeys.f ? 1 : 0) - (fpKeys.b ? 1 : 0), r = (fpKeys.r ? 1 : 0) - (fpKeys.l ? 1 : 0);
            if (!stage || (!f && !r)) {
                fpLast = 0;
                return;
            }
            var dt = fpLast ? Math.min(0.1, (now - fpLast) / 1000) : 0.016;
            fpLast = now;
            var len = Math.hypot(f, r);
            fpMove(stage, f / len, r / len, (fpKeys.run ? FP_RUN : FP_SPEED) * stage._w3dRoom.ppm * dt);
            apply(stage);
            requestAnimationFrame(fpLoop);
        }

        document.addEventListener('keydown', function(event) {
            var key = (event.key || '').toLowerCase();
            if (key === 'shift') fpKeys.run = true;
            if (!FP_KEYS[key] || event.ctrlKey || event.altKey || event.metaKey) return;
            if (event.target.closest && event.target.closest('input, textarea, select, [contenteditable]')) return;
            if (!fpActive() || document.querySelector('.modal.show, .swal2-container')) return;
            event.preventDefault();
            var moving = fpKeys.f || fpKeys.b || fpKeys.l || fpKeys.r;
            fpKeys[FP_KEYS[key]] = true;
            if (!moving) requestAnimationFrame(fpLoop);
        });

        document.addEventListener('keyup', function(event) {
            var key = (event.key || '').toLowerCase();
            if (key === 'shift') fpKeys.run = false;
            if (FP_KEYS[key]) fpKeys[FP_KEYS[key]] = false;
        });

        window.addEventListener('blur', function() { fpKeys = {}; });

        // Đã khoá chuột: rê chuột = nhìn quanh
        document.addEventListener('mousemove', function(event) {
            var scene = document.pointerLockElement;
            if (!scene || !scene.classList || !scene.classList.contains('w3d-scene')) return;
            fpLook(scene.closest('.w3d'), event.movementX || 0, event.movementY || 0);
        });

        // Bấm vào khung khi đang đi bộ: chưa khoá chuột thì khoá (bấm trúng thùng hàng thì vẫn mở chi tiết như thường);
        // đã khoá thì bấm = bấm vào thùng hàng nằm ở tâm ngắm (thả chuột ra để xem hộp thoại chi tiết).
        document.addEventListener('click', function(event) {
            var scene = event.target.closest && event.target.closest('.w3d-scene');
            if (!scene || justDragged || fpRelay) return;
            var stage = scene.closest('.w3d');
            if (!viewOf(stage).fp) return;
            if (document.pointerLockElement === scene) {
                event.stopImmediatePropagation();
                var hit = fpPick(stage);
                if (hit) {
                    document.exitPointerLock();
                    fpRelay = true;
                    try {
                        hit.dispatchEvent(new MouseEvent('click', { bubbles: true }));
                    } finally {
                        fpRelay = false;
                    }
                }
                return;
            }
            if (event.target.closest('[data-w3d-key]')) return;
            if (scene.requestPointerLock) {
                try {
                    var req = scene.requestPointerLock();
                    if (req && req.catch) req.catch(function() {});
                } catch (e) { /* không khoá được chuột: vẫn kéo chuột để nhìn */ }
            }
        }, true);

        document.addEventListener('keydown', function(event) {
            if (event.key !== 'Escape' || document.pointerLockElement || document.querySelector('.modal.show, .swal2-container')) return;
            document.querySelectorAll('.w3d.is-full').forEach(function(stage) { setFull(stage, false); });
        });

        // Shift + lăn chuột: phóng to / thu nhỏ quanh con trỏ. Khung đang mở to thì lăn chuột không cần Shift
        // (khung thường lăn chuột không Shift vẫn cuộn trang như bình thường).
        // Shift + lăn trên Windows thường thành cuộn ngang (deltaX) nên đọc cả hai chiều.
        document.addEventListener('wheel', function(event) {
            var scene = event.target.closest && event.target.closest('.w3d-scene');
            if (!scene) return;
            var stage = scene.closest('.w3d');
            if (viewOf(stage).fp) {
                // Đi bộ: lăn chuột = bước tới / lùi lại 0,4 m (không phóng to)
                event.preventDefault();
                var step = event.deltaY || event.deltaX;
                if (!step) return;
                fpMove(stage, step < 0 ? 1 : -1, 0, 0.4 * stage._w3dRoom.ppm);
                apply(stage);
                return;
            }
            if (!event.shiftKey && !stage.classList.contains('is-full')) return;
            var delta = event.deltaY || event.deltaX;
            event.preventDefault();
            if (!delta) return;
            zoomBy(stage, delta < 0 ? 1.12 : 1 / 1.12, event.clientX, event.clientY);
        }, { passive: false });

        document.addEventListener('click', function(event) {
            if (!event.target.closest) return;
            var view = event.target.closest('[data-w3d-view]');
            if (view) {
                setView(view.closest('.w3d'), view.dataset.w3dView);
                return;
            }
            var zoom = event.target.closest('[data-w3d-zoom]');
            if (zoom && viewOf(zoom.closest('.w3d')).fp) return;
            if (zoom) {
                zoomBy(zoom.closest('.w3d'), Number(zoom.dataset.w3dZoom) > 0 ? 1.2 : 1 / 1.2);
                return;
            }
            var fovBtn = event.target.closest('[data-w3d-fov]');
            if (fovBtn) {
                fpFovMem = Number(fovBtn.dataset.w3dFov) || 100;
                try {
                    localStorage.setItem(FP_FOV_KEY, fovBtn.dataset.w3dFov);
                } catch (e) { /* không lưu được thì chỉ đổi ở lần xem này */ }
                document.querySelectorAll('.w3d.is-fp').forEach(apply);
                return;
            }
            var walk = event.target.closest('[data-w3d-walk]');
            if (walk) {
                var walkStage = walk.closest('.w3d');
                setWalk(walkStage, !viewOf(walkStage).fp);
                return;
            }
            var full = event.target.closest('[data-w3d-full]');
            if (full) {
                var stage = full.closest('.w3d');
                setFull(stage, !stage.classList.contains('is-full'));
            }
        });

        window.WmsShelf3d = { markup: markup, render: render, renderRoom: renderRoom, setView: setView, focus: focus };
    })();
</script>
@endonce
