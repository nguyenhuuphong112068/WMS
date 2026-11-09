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
|       columns: [{ label, tiers: [{ label, first, level (0 = hàng dưới cùng), max, depth, texture,
|                   cells: [{ p, d, cls, title, key, color, box, dim, hit }] }] }]
|   });
|
| p = ô ngang (1 = trái), d = dãy sâu (1 = sát mép trước). box = vẽ thùng hàng nổi lên (cao boxHeight),
| không thì vẽ ô phẳng trên mặt tầng. Ô có key mang data-w3d-key để trang gắn sự kiện bấm.
| Kéo chuột để xoay, nhấp đúp về góc nghiêng; [data-w3d-view] đổi góc nhìn, [data-w3d-zoom] phóng to.
| Đặt stage.dataset.hl = columns|tiers|max|depth để làm sáng một chiều của kệ.
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
        background-size: var(--u) var(--u);
        background-position: var(--pad) var(--pad);
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
    .w3d-room-floor {
        background-color: rgba(255, 255, 255, 0.75);
        background-image:
            linear-gradient(90deg, rgba(var(--primary-rgb), 0.16) 1px, transparent 1px),
            linear-gradient(rgba(var(--primary-rgb), 0.16) 1px, transparent 1px);
        background-size: var(--m) var(--m);
        border: 2px solid var(--primary-light);
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
                '<div class="w3d-group" role="group" aria-label="Phóng to thu nhỏ">' +
                '<button type="button" data-w3d-zoom="-1" title="Thu nhỏ"><i class="fas fa-search-minus"></i></button>' +
                '<button type="button" data-w3d-zoom="1" title="Phóng to"><i class="fas fa-search-plus"></i></button>' +
                '<button type="button" data-w3d-full title="Mở to toàn màn hình (Esc để thu về)"><i class="fas fa-expand"></i></button>' +
                '</div>' +
                '</div>' +
                '<div class="w3d-scene"><div class="w3d-world"></div></div>' +
                '<div class="w3d-legend">' + (opts.legend || '') + '</div>' +
                '<div class="w3d-hint"><i class="fas fa-arrows-alt mr-1"></i>Kéo để xoay · Shift + kéo để dời</div>';
        }

        function cellMarkup(c, x, y, U, H) {
            var cls = (c.cls || '') + (c.d === 1 ? ' d1' : '') + (c.p === 1 ? ' p1' : '') +
                (c.dim ? ' is-dim' : '') + (c.hit ? ' is-hit' : '');
            var attrs = (c.key ? ' data-w3d-key="' + esc(c.key) + '"' : '') + (c.title ? ' title="' + esc(c.title) + '"' : '');
            var style = 'left:' + px(x) + ';top:' + px(y) + ';width:' + px(U) + ';height:' + px(U) +
                (c.color ? ';--c:' + esc(c.color) : '') + (c.style || '');

            if (!c.box || !H) {
                return '<span class="w3d-cell ' + cls + '" style="' + style + '"' + attrs + '></span>';
            }

            // Thùng hàng: mặt trên nâng lên H, 4 mặt đứng dựng từ mép ô lên (trục z của mặt tầng hướng lên trên).
            var s = px(U), h = px(H);
            return '<span class="w3d-box ' + cls + '" style="' + style + '"' + attrs + '>' +
                '<i class="w3d-f t" style="width:' + s + ';height:' + s + ';transform:translateZ(' + h + ')"></i>' +
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
            world.style.transform = 'translate(' + Math.round(v.panX) + 'px,' + Math.round(v.panY) + 'px) scale(' + (v.fit * v.zoom).toFixed(3) +
                ') rotateX(' + v.ax + 'deg) rotateY(' + v.ay + 'deg)';
            stage.querySelectorAll('[data-w3d-view]').forEach(function(btn) {
                btn.classList.toggle('active', btn.dataset.w3dView === v.preset);
            });
        }

        // Cỡ vừa khung theo góc nhìn hiện tại (xoay tay không đổi cỡ, chỉ tính lại khi dựng lại / đổi cỡ khung).
        function refit(stage) {
            var box = stage._w3dBox;
            var scene = stage.querySelector('.w3d-scene');
            if (!box || !scene || !scene.clientWidth) return;
            var v = viewOf(stage);
            var ax = Math.abs(v.ax) * Math.PI / 180, ay = Math.abs(v.ay) * Math.PI / 180;
            var projW = box.W * Math.cos(ay) + box.D * Math.sin(ay) + 120;
            var projH = box.H * Math.cos(ax) + (box.W * Math.sin(ay) + box.D * Math.cos(ay)) * Math.sin(ax) + 90;
            v.fit = Math.max(0.15, Math.min(scene.clientWidth / projW, (scene.clientHeight - 30) / projH, 1.6));
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
                    html += '<div class="w3d-label w3d-col-label" style="width:' + px(w) + ';transform:translate3d(' +
                        px(x) + ',' + px(top - 20) + ',' + px(front) + ')">' + esc(column.label) + '</div>';
                }

                column.tiers.forEach(function(tier) {
                    var y = floor - (tier.level || 0) * TH;
                    var cells = (tier.cells || []).map(function(c) {
                        return cellMarkup(c, PAD + (c.p - 1) * (U + G), D - PAD - c.d * U - (c.d - 1) * G, U, BH);
                    }).join('');

                    html += '<div class="w3d-board' + (tier.texture ? ' is-texture' : '') + (tier.first ? ' is-first' : '') + '"' +
                        ' style="width:' + px(w) + ';height:' + px(D) + ';transform:translate3d(' + px(x) + ',' + px(y) + ',' +
                        px(back) + ') rotateX(90deg)' + (tier.texture ? ';--u:' + px(U + G) + ';--pad:' + px(PAD) : '') + '"' +
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
            stage._w3dBox = { W: shelf.W, D: shelf.D, H: shelf.H };
            observe(stage);
        }

        /*
         | Cả một phòng: sàn (lưới 1 m), tường thấp, cửa ra vào và nhiều kệ đặt theo mét.
         |   room: { width, depth, ppm (px/m, mặc định 100), tierMetre (chiều cao 1 tầng, m),
         |           door: { wall: top|right|bottom|left, offset, width } | null,
         |           shelves: [{ key, label, x, y (tâm, m), width, depth (m), rotation, selected, clash, model }] }
         | Trục sơ đồ: x sang phải, y xuống dưới (= hướng về phía người xem ở 3D). rotation = hướng mặt trước
         | theo chiều kim đồng hồ trên sơ đồ: 0 xuống, 90 trái, 180 lên, 270 phải.
         */
        function renderRoom(stage, room) {
            var world = stage.querySelector('.w3d-world');
            if (!world) return;
            clearEmpty(stage);

            var PPM = room.ppm || 100;
            var RW = room.width * PPM, RD = room.depth * PPM;
            var WALL = 0.3 * PPM;
            var tallest = 0;
            var html = '';

            html += '<div class="w3d-room-floor" style="width:' + px(RW) + ';height:' + px(RD) + ';--m:' + px(PPM) +
                ';transform:translate3d(' + px(-RW / 2) + ',0px,' + px(-RD / 2) + ') rotateX(90deg)"></div>';

            // Tường thấp quanh phòng, chừa khoảng trống ở chỗ cửa.
            var door = room.door && room.door.wall ? room.door : null;
            function wall(side, from, to) {
                if (to - from <= 0.001) return '';
                if (side === 'top' || side === 'bottom') {
                    return plane('w3d-wall', from * PPM - RW / 2, -WALL, side === 'top' ? -RD / 2 : RD / 2, (to - from) * PPM, WALL);
                }
                return plane('w3d-wall', side === 'left' ? -RW / 2 : RW / 2, -WALL, to * PPM - RD / 2, (to - from) * PPM, WALL, 'rotateY(90deg)');
            }
            ['top', 'right', 'bottom', 'left'].forEach(function(side) {
                var length = side === 'top' || side === 'bottom' ? room.width : room.depth;
                if (door && door.wall === side) {
                    var a = Math.max(0, door.offset), b = Math.min(length, door.offset + (door.width || 1));
                    html += wall(side, 0, a) + wall(side, b, length);
                } else {
                    html += wall(side, 0, length);
                }
            });

            if (door) {
                var dw = (door.width || 1) * PPM, mark = 0.25 * PPM, dx, dz, dW, dD;
                if (door.wall === 'top' || door.wall === 'bottom') {
                    dx = door.offset * PPM - RW / 2; dW = dw; dD = mark;
                    dz = door.wall === 'top' ? -RD / 2 : RD / 2 - mark;
                } else {
                    dz = door.offset * PPM - RD / 2; dD = dw; dW = mark;
                    dx = door.wall === 'left' ? -RW / 2 : RW / 2 - mark;
                }
                html += '<div class="w3d-door" style="width:' + px(dW) + ';height:' + px(dD) + ';transform:translate3d(' +
                    px(dx) + ',-1px,' + px(dz) + ') rotateX(90deg)"><span>Cửa</span></div>';
            }

            (room.shelves || []).forEach(function(s) {
                var built = buildShelf(s.model || {}, true);
                var X = s.x * PPM - RW / 2, Z = s.y * PPM - RD / 2;
                var height = (built ? built.levels : 1) * (room.tierMetre || 0.4) * PPM;
                tallest = Math.max(tallest, height);

                if (built) {
                    var sx = s.width * PPM / built.W, sz = s.depth * PPM / built.D, sy = height / built.H;
                    html += '<div class="w3d-unit' + (s.selected ? ' is-selected' : '') + (s.clash ? ' is-clash' : '') + '"' +
                        (s.key ? ' data-w3d-shelf="' + esc(s.key) + '"' : '') + (s.label ? ' title="' + esc(s.label) + '"' : '') +
                        ' style="transform:translate3d(' + px(X) + ',0px,' + px(Z) + ') rotateY(' + (-(s.rotation || 0)) + 'deg) scale3d(' +
                        sx.toFixed(4) + ',' + sy.toFixed(4) + ',' + sz.toFixed(4) + ') translateY(' + px(-built.H / 2) + ')">' +
                        built.html + '</div>';
                } else {
                    // Kệ chưa có cột/tầng: chỉ vẽ khung chiếm chỗ trên sàn
                    html += '<div class="w3d-unit-empty' + (s.selected ? ' is-selected' : '') + '"' +
                        (s.key ? ' data-w3d-shelf="' + esc(s.key) + '"' : '') +
                        ' style="width:' + px(s.width * PPM) + ';height:' + px(s.depth * PPM) + ';transform:translate3d(' + px(X) + ',-1px,' + px(Z) +
                        ') rotateY(' + (-(s.rotation || 0)) + 'deg) translate3d(' + px(-s.width * PPM / 2) + ',0px,' + px(-s.depth * PPM / 2) +
                        ') rotateX(90deg)"></div>';
                }

                if (s.label) {
                    html += '<div class="w3d-label w3d-unit-label' + (s.selected ? ' is-selected' : '') + '" style="transform:translate3d(' +
                        px(X) + ',' + px(-height - 14) + ',' + px(Z) + ') translateX(-50%)">' + esc(s.label) + '</div>';
                }
            });

            world.innerHTML = html;
            stage._w3dBox = { W: RW, D: RD, H: Math.max(tallest, WALL) };
            if (!(room.shelves || []).length && room.empty) {
                stage.insertAdjacentHTML('beforeend', '<div class="w3d-empty">' + room.empty + '</div>');
            }
            observe(stage);
        }

        function setView(stage, preset) {
            var v = viewOf(stage);
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
            drag = {
                stage: stage, x: event.clientX, y: event.clientY, ax: v.ax, ay: v.ay,
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
            var stage = scene.closest('.w3d');
            viewOf(stage).zoom = 1;
            setView(stage, 'iso');
        });

        function zoomBy(stage, factor) {
            var v = viewOf(stage);
            v.zoom = Math.max(0.3, Math.min(6, v.zoom * factor));
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

        document.addEventListener('keydown', function(event) {
            if (event.key !== 'Escape' || document.querySelector('.modal.show, .swal2-container')) return;
            document.querySelectorAll('.w3d.is-full').forEach(function(stage) { setFull(stage, false); });
        });

        // Lăn chuột để phóng to / thu nhỏ khi đang mở to (khung thường thì vẫn lăn trang như bình thường).
        document.addEventListener('wheel', function(event) {
            var scene = event.target.closest && event.target.closest('.w3d.is-full .w3d-scene');
            if (!scene) return;
            event.preventDefault();
            zoomBy(scene.closest('.w3d'), event.deltaY < 0 ? 1.12 : 1 / 1.12);
        }, { passive: false });

        document.addEventListener('click', function(event) {
            if (!event.target.closest) return;
            var view = event.target.closest('[data-w3d-view]');
            if (view) {
                setView(view.closest('.w3d'), view.dataset.w3dView);
                return;
            }
            var zoom = event.target.closest('[data-w3d-zoom]');
            if (zoom) {
                zoomBy(zoom.closest('.w3d'), Number(zoom.dataset.w3dZoom) > 0 ? 1.2 : 1 / 1.2);
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
