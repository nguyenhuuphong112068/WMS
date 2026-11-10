{{--
| ĐỊNH KHU - MẶT BẰNG KHO/PHÒNG
| Vẽ hình dạng phòng (đa giác: kéo chấm tròn ở góc, bấm dấu + giữa cạnh để thêm góc, nhấp đúp vào góc để xoá),
| đặt cửa ra vào (kéo dọc tường, kéo hai đầu để đổi bề rộng), rồi đặt các kệ/tủ đã xây vào phòng: kéo thả trên sơ đồ
| nhìn từ trên xuống, xoay hướng mặt trước, chỉnh kích thước; bên phải là cả phòng ở 3D (pages/shared/shelf3d).
| Phòng lưu dạng đa giác toạ độ mét (App\Support\RoomLayout), cửa = { cạnh, cách góc đầu cạnh, rộng }.
| Dữ liệu tải/lưu qua ZoneStructureController::layout() / saveLayout(). Mở từ màn Cấu trúc kho:
|   window.ZoneLayout.open(warehouse, { onDirty(bool), onOpenShelf(id) })
--}}
<div id="zs-layout" class="zl" hidden>
    <div class="zl-bar">
        <div class="zl-room">
            <div class="zl-field">
                <label>Chiều ngang phòng</label>
                <div class="zl-row">
                    <div class="zs-stepper zl-step" data-field="room.width" data-step="0.5">
                        <button type="button" data-delta="-1" title="Bớt 0,5 m"><i class="fas fa-minus"></i></button>
                        <input type="number" step="0.5" min="1" aria-label="Chiều ngang phòng (m)">
                        <button type="button" data-delta="1" title="Thêm 0,5 m"><i class="fas fa-plus"></i></button>
                    </div>
                    <small>m</small>
                </div>
            </div>
            <span class="zl-x">×</span>
            <div class="zl-field">
                <label>Chiều sâu phòng</label>
                <div class="zl-row">
                    <div class="zs-stepper zl-step" data-field="room.depth" data-step="0.5">
                        <button type="button" data-delta="-1" title="Bớt 0,5 m"><i class="fas fa-minus"></i></button>
                        <input type="number" step="0.5" min="1" aria-label="Chiều sâu phòng (m)">
                        <button type="button" data-delta="1" title="Thêm 0,5 m"><i class="fas fa-plus"></i></button>
                    </div>
                    <small>m</small>
                </div>
            </div>
            <div class="zl-field">
                <label>Hình dạng phòng</label>
                <div class="zl-row">
                    <button type="button" class="btn btn-sm btn-outline-secondary zl-rect"
                        title="Đưa phòng về hình chữ nhật theo khung bao hiện tại">
                        <i class="far fa-square mr-1"></i> Hình chữ nhật
                    </button>
                    <small class="zl-corners"></small>
                </div>
            </div>
            <div class="zl-field">
                <label>Cửa ra vào</label>
                <div class="zl-row">
                    <button type="button" class="btn btn-sm btn-outline-primary zl-door-add" title="Đặt cửa vào giữa bức tường dài nhất">
                        <i class="fas fa-door-open mr-1"></i> Thêm cửa
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger zl-door-del" title="Bỏ cửa ra vào">
                        <i class="fas fa-times mr-1"></i> Bỏ cửa
                    </button>
                </div>
            </div>
            <div class="zl-field zl-door-size">
                <label>Rộng cửa</label>
                <div class="zl-row">
                    <div class="zs-stepper zl-step" data-field="room.door_width" data-step="0.1">
                        <button type="button" data-delta="-1" title="Bớt 0,1 m"><i class="fas fa-minus"></i></button>
                        <input type="number" step="0.1" min="0.5" aria-label="Bề rộng cửa (m)">
                        <button type="button" data-delta="1" title="Thêm 0,1 m"><i class="fas fa-plus"></i></button>
                    </div>
                    <small>m</small>
                </div>
            </div>
        </div>

        <div class="zl-actions">
            <span class="zl-status"></span>
            <button type="button" class="btn btn-sm btn-outline-primary zl-auto" title="Đặt lần lượt các kệ/tủ chưa đặt vào chỗ trống đầu tiên">
                <i class="fas fa-magic mr-1"></i> Tự xếp kệ chưa đặt
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary zl-reset">
                <i class="fas fa-undo mr-1"></i> Hoàn tác
            </button>
            <button type="button" class="btn btn-sm btn-primary zl-save">
                <i class="fas fa-save mr-1"></i> Lưu mặt bằng
            </button>
        </div>
    </div>

    <div class="zl-body">
        <section class="zl-card">
            <div class="zl-card-head">
                <span><i class="fas fa-vector-square mr-1"></i> Sơ đồ mặt bằng <small>(nhìn từ trên xuống)</small>
                    <i class="fas fa-question-circle zl-help" title="Kéo chấm tròn ở góc để đổi hình dạng phòng · bấm dấu + giữa một cạnh để thêm góc · nhấp đúp vào một góc để xoá góc · kéo cửa dọc theo tường, kéo hai đầu cửa để đổi bề rộng"></i></span>
                <small class="zl-scale"></small>
            </div>
            <div class="zl-plan-wrap">
                <div class="zl-plan"></div>
            </div>
            <div class="zl-unplaced"></div>
        </section>

        <section class="zl-card zl-side">
            <div class="zl-selected"></div>
            <div class="w3d zl-3d" title="Kéo chuột để xoay · bấm một kệ để chọn · nhấp đúp mở cấu trúc kệ"></div>
        </section>
    </div>
</div>

<style>
    .zl-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 18px;
        align-items: flex-end;
        justify-content: space-between;
        padding: 14px 16px;
        margin-bottom: 16px;
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-lg);
        background: var(--bg-neutral);
    }

    .zl-room {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 14px;
        align-items: flex-end;
    }

    .zl-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
        position: relative;
        margin-right: 14px;
    }

    .zl-field label {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .zl-row {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .zl-row small {
        color: #64748b;
        font-weight: 600;
    }

    .zl-field .zs-stepper input {
        width: 70px;
        font-size: 1rem;
    }

    .zl-field select {
        min-width: 140px;
        border-radius: var(--border-radius-md);
    }

    .zl-x {
        font-weight: 700;
        color: var(--primary-lighter);
        padding: 0 4px 8px 10px;
    }

    .zl-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .zl-actions .btn {
        border-radius: var(--border-radius-md);
        transition: all 0.2s;
    }

    .zl-actions .btn-primary:hover {
        transform: translateY(-1px);
    }

    .zl-status {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--primary);
        background: #fff;
        border: 1px solid var(--primary-soft);
        border-radius: 999px;
        padding: 4px 12px;
    }

    .zl-status .bad {
        color: #B91C1C;
    }

    .zl-body {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
        gap: 16px;
    }

    .zl-card {
        border: 1px solid #e2e8f0;
        border-radius: var(--border-radius-lg);
        background: #fff;
        padding: 14px 16px;
        box-shadow: var(--shadow-sm);
        min-width: 0;
    }

    .zl-side {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .zl-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        font-weight: 700;
        color: var(--primary);
    }

    .zl-card-head small {
        font-weight: 500;
        color: #64748b;
    }

    /* ---------- sơ đồ 2D ---------- */
    .zl-plan-wrap {
        position: relative;
        overflow: auto;
        padding: 24px 12px 28px 34px;
        background: var(--bg-neutral);
        border-radius: var(--border-radius-md);
    }

    /* phòng vẽ bằng SVG (đa giác), khung .zl-plan chỉ là khung bao gốc (0,0) */
    .zl-plan {
        position: relative;
        user-select: none;
        touch-action: none;
    }

    .zl-svg {
        position: absolute;
        left: 0;
        top: 0;
        overflow: visible;
        pointer-events: none;
    }

    .zl-svg-floor {
        z-index: 0;
    }

    .zl-svg-ui {
        z-index: 5;
    }

    .zl-svg-ui .zl-v,
    .zl-svg-ui .zl-mid,
    .zl-svg-ui .zl-dh {
        pointer-events: all;
    }

    .zl-v {
        fill: #fff;
        stroke: var(--primary);
        stroke-width: 2.5;
        cursor: move;
        transition: fill 0.2s;
    }

    .zl-v:hover,
    .zl-v.is-active {
        fill: var(--accent);
        stroke: #0E7F93;
    }

    .zl-mid circle {
        fill: rgba(var(--primary-rgb), 0.12);
        stroke: var(--primary-light);
        stroke-width: 1.5;
        stroke-dasharray: 2 2;
    }

    .zl-mid text {
        font-size: 12px;
        font-weight: 700;
        fill: var(--primary);
        pointer-events: none;
    }

    .zl-mid {
        cursor: copy;
        opacity: 0.55;
        transition: opacity 0.2s;
    }

    .zl-mid:hover {
        opacity: 1;
    }

    .zl-door-line {
        stroke: #fff;
        stroke-width: 9;
    }

    .zl-door-mark {
        stroke: var(--accent);
        stroke-width: 7;
        stroke-dasharray: 5 3;
    }

    .zl-dh.is-move {
        stroke: transparent;
        stroke-width: 16;
        cursor: grab;
    }

    .zl-dh.is-end {
        fill: #fff;
        stroke: #0E7F93;
        stroke-width: 2;
        cursor: ew-resize;
    }

    .zl-door-text {
        font-size: 10px;
        font-weight: 700;
        fill: #0E7F93;
        paint-order: stroke;
        stroke: #fff;
        stroke-width: 3px;
        pointer-events: none;
    }

    .zl-help {
        color: var(--primary-lighter);
        margin-left: 4px;
        cursor: help;
    }

    .zl-corners {
        white-space: nowrap;
    }

    .zl-readonly .zl-svg-ui {
        display: none;
    }

    .zl-rule {
        position: absolute;
        font-size: 10px;
        font-weight: 700;
        color: #94a3b8;
        pointer-events: none;
        white-space: nowrap;
    }

    .zl-rule.is-top {
        top: -20px;
        transform: translateX(-50%);
    }

    .zl-rule.is-left {
        left: -30px;
        width: 24px;
        text-align: right;
        transform: translateY(-50%);
    }

    .zl-door {
        position: absolute;
        background: #fff;
        border: 2px dashed var(--accent);
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .zl-door span {
        font-size: 10px;
        font-weight: 700;
        color: #0E7F93;
        background: #fff;
        padding: 0 3px;
        white-space: nowrap;
    }

    .zl-shelf {
        position: absolute;
        z-index: 2;
        display: flex;
        border: 1.5px solid var(--primary);
        background: rgba(var(--primary-rgb), 0.12);
        border-radius: 2px;
        cursor: grab;
        transition: box-shadow 0.2s, background 0.2s;
    }

    .zl-shelf:hover {
        background: rgba(var(--primary-rgb), 0.2);
    }

    .zl-shelf.is-selected {
        z-index: 3;
        border-color: var(--accent);
        background: rgba(23, 184, 212, 0.2);
        box-shadow: 0 0 0 3px rgba(23, 184, 212, 0.3);
    }

    .zl-shelf.is-clash {
        border-color: #DC2626;
        background: rgba(220, 38, 38, 0.18);
    }

    .zl-shelf.is-dragging {
        cursor: grabbing;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
    }

    .zl-readonly .zl-shelf {
        cursor: pointer;
    }

    .zl-shelf-col {
        flex: 1 1 0;
        border-right: 1px dashed rgba(var(--primary-rgb), 0.45);
    }

    .zl-shelf-col:last-child {
        border-right: none;
    }

    /* mép trước của kệ: vạch đậm + mũi tên chỉ hướng lấy hàng */
    .zl-shelf-front {
        position: absolute;
        left: -1px;
        right: -1px;
        bottom: -1px;
        height: 4px;
        background: var(--primary-dark);
    }

    .zl-shelf-front::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 4px;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: var(--primary-dark);
    }

    .zl-shelf-label {
        position: absolute;
        left: 50%;
        top: 50%;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.15;
        color: var(--primary-dark);
        text-align: center;
        white-space: nowrap;
        pointer-events: none;
    }

    .zl-shelf-label small {
        display: block;
        font-size: 9px;
        font-weight: 600;
        color: #475569;
    }

    .zl-unplaced {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 8px;
        align-items: center;
        margin-top: 12px;
        font-size: 0.8rem;
        color: #475569;
    }

    .zl-unplaced:empty {
        display: none;
    }

    .zl-chip {
        border: 1px dashed var(--primary);
        background: var(--primary-soft);
        color: var(--primary-dark);
        border-radius: 999px;
        padding: 3px 12px;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: grab;
        transition: all 0.2s;
        touch-action: none;
    }

    .zl-chip:hover {
        background: var(--primary);
        color: #fff;
    }

    /* ---------- kệ đang chọn ---------- */
    .zl-selected {
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-md);
        background: var(--bg-neutral);
        padding: 12px 14px;
    }

    .zl-sel-title {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .zl-sel-title .tag {
        font-size: 0.72rem;
        font-weight: 600;
        color: #475569;
        background: #fff;
        border-radius: 999px;
        padding: 2px 10px;
    }

    .zl-sel-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 22px;
    }

    .zl-sel-grid .zl-field .zs-stepper {
        align-self: flex-start;
    }

    .zl-face {
        display: inline-flex;
        align-self: flex-start;
        border: 1px solid #cbd5e1;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        background: #fff;
    }

    .zl-face button {
        width: 38px;
        height: 34px;
        border: none;
        background: none;
        color: #64748b;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .zl-face button + button {
        border-left: 1px solid #e2e8f0;
    }

    .zl-face button:hover:not(:disabled) {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .zl-face button.active {
        background: var(--primary);
        color: #fff;
    }

    .zl-sel-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }

    .zl-sel-actions .btn {
        border-radius: var(--border-radius-md);
    }

    .zl-sel-empty {
        color: #64748b;
        font-size: 0.82rem;
    }

    .zl-3d.w3d {
        min-height: 400px;
        flex: 1 1 auto;
    }

    @media (max-width: 1200px) {
        .zl-body {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<script>
    // Chạy sau khi trang dựng xong: cần cấu hình #zs-config và bộ vẽ 3D chung (pages/shared/shelf3d).
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        var CFG = JSON.parse(document.getElementById('zs-config').textContent);
        var root = document.getElementById('zs-layout');
        if (!root) return;

        var ROTATION_ICONS = { 0: 'fa-arrow-down', 90: 'fa-arrow-left', 180: 'fa-arrow-up', 270: 'fa-arrow-right' };
        var ROTATION_LABELS = { 0: 'xuống', 90: 'sang trái', 180: 'lên', 270: 'sang phải' };
        var TIER_METRE = 0.4;
        var MAX_3D_CELLS = 6000;
        var EPS = 0.001;

        var el = {
            plan: root.querySelector('.zl-plan'),
            wrap: root.querySelector('.zl-plan-wrap'),
            unplaced: root.querySelector('.zl-unplaced'),
            selected: root.querySelector('.zl-selected'),
            stage: root.querySelector('.zl-3d'),
            status: root.querySelector('.zl-status'),
            scale: root.querySelector('.zl-scale'),
            doorAdd: root.querySelector('.zl-door-add'),
            doorDel: root.querySelector('.zl-door-del'),
            doorSize: root.querySelector('.zl-door-size'),
            rect: root.querySelector('.zl-rect'),
            corners: root.querySelector('.zl-corners'),
            save: root.querySelector('.zl-save'),
            reset: root.querySelector('.zl-reset'),
            auto: root.querySelector('.zl-auto')
        };

        var S = {
            warehouse: null, hooks: {}, room: null, shelves: [], selected: null,
            limits: { maxRoom: 200, minShelf: 0.2, maxShelf: 50, doorWidth: 1, doorMin: 0.5, doorMax: 4, maxPoints: 48 },
            scale: 40, drag: null, raf: 0
        };

        el.stage.innerHTML = WmsShelf3d.markup();

        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        // Làm tròn theo bước rồi cắt sai số dấu phẩy động (1.9000000000000001 -> 1.9).
        function round(n, step) {
            step = step || 0.01;
            return parseFloat((Math.round(n / step) * step).toFixed(4));
        }

        function fmt(n) {
            return (Math.round(n * 100) / 100).toLocaleString('vi-VN', { maximumFractionDigits: 2 });
        }

        function clamp(n, a, b) {
            return Math.min(Math.max(n, a), b);
        }

        function toast(icon, text) {
            Swal.fire({
                icon: icon, text: text,
                title: { success: 'Đã lưu', warning: 'Chưa làm được', error: 'Không thực hiện được' }[icon] || '',
                timer: icon === 'success' ? 2400 : undefined,
                showConfirmButton: icon !== 'success',
                confirmButtonColor: '#2E7BC4'
            });
        }

        function editable() {
            return !!CFG.canUpdate;
        }

        function dirty(on) {
            if (S.hooks.onDirty) S.hooks.onDirty(on);
        }

        function byId(id) {
            return S.shelves.find(function(s) { return String(s.id) === String(id); });
        }

        /* ---------- hình học ---------- */

        // Khung chiếm chỗ trên sơ đồ [trái, trên, phải, dưới]: kệ quay ngang (90/270) thì đổi ngang - sâu.
        function box(s, x, y, rot) {
            x = x == null ? s.x : x;
            y = y == null ? s.y : y;
            var turned = ((rot == null ? s.rotation : rot) % 180) === 90;
            var w = turned ? s.depth : s.width, d = turned ? s.width : s.depth;
            return [x - w / 2, y - d / 2, x + w / 2, y + d / 2];
        }

        function overlaps(a, b) {
            return a[0] < b[2] - EPS && b[0] < a[2] - EPS && a[1] < b[3] - EPS && b[1] < a[3] - EPS;
        }

        /* ---- đa giác phòng (toạ độ mét, chạy theo chiều kim đồng hồ khi nhìn từ trên) ---- */

        function bounds() {
            var p = S.room.polygon;
            var b = { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity };
            p.forEach(function(v) {
                b.minX = Math.min(b.minX, v[0]); b.minY = Math.min(b.minY, v[1]);
                b.maxX = Math.max(b.maxX, v[0]); b.maxY = Math.max(b.maxY, v[1]);
            });
            return b;
        }

        // Cập nhật kích thước khung bao (ô Chiều ngang / Chiều sâu phòng)
        function syncBox() {
            var b = bounds();
            S.room.width = round(b.maxX - b.minX);
            S.room.depth = round(b.maxY - b.minY);
        }

        function rectPolygon(w, d) {
            return [[0, 0], [w, 0], [w, d], [0, d]];
        }

        function isRect() {
            var p = S.room.polygon, b = bounds();
            if (p.length !== 4) return false;
            var want = [[b.minX, b.minY], [b.maxX, b.minY], [b.maxX, b.maxY], [b.minX, b.maxY]];
            return want.every(function(w, i) { return Math.abs(w[0] - p[i][0]) < EPS && Math.abs(w[1] - p[i][1]) < EPS; });
        }

        function edgeOf(i) {
            var p = S.room.polygon;
            var a = p[i], b = p[(i + 1) % p.length];
            return { a: a, b: b, len: Math.hypot(b[0] - a[0], b[1] - a[1]) };
        }

        function cross(a, b, c) {
            return (b[0] - a[0]) * (c[1] - a[1]) - (b[1] - a[1]) * (c[0] - a[0]);
        }

        function properCross(p1, p2, q1, q2) {
            var e = 1e-9, d1 = cross(q1, q2, p1), d2 = cross(q1, q2, p2), d3 = cross(p1, p2, q1), d4 = cross(p1, p2, q2);
            return ((d1 > e && d2 < -e) || (d1 < -e && d2 > e)) && ((d3 > e && d4 < -e) || (d3 < -e && d4 > e));
        }

        function onSeg(a, b, c) {
            return Math.abs(cross(a, b, c)) < 0.0005 && c[0] >= Math.min(a[0], b[0]) - 0.0005 && c[0] <= Math.max(a[0], b[0]) + 0.0005 &&
                c[1] >= Math.min(a[1], b[1]) - 0.0005 && c[1] <= Math.max(a[1], b[1]) + 0.0005;
        }

        function pointIn(pt) {
            var p = S.room.polygon, inside = false;
            for (var i = 0, j = p.length - 1; i < p.length; j = i++) {
                if (onSeg(p[i], p[j], pt)) return true;
                if ((p[i][1] > pt[1]) !== (p[j][1] > pt[1]) && pt[0] < (p[j][0] - p[i][0]) * (pt[1] - p[i][1]) / (p[j][1] - p[i][1]) + p[i][0]) inside = !inside;
            }
            return inside;
        }

        // Đa giác đơn: không trùng góc liên tiếp, hai cạnh không kề nhau không chạm nhau.
        function simple() {
            var p = S.room.polygon, n = p.length;
            for (var i = 0; i < n; i++) {
                var q = p[(i + 1) % n];
                if (Math.abs(p[i][0] - q[0]) < 0.005 && Math.abs(p[i][1] - q[1]) < 0.005) return false;
            }
            for (i = 0; i < n; i++) {
                for (var j = i + 1; j < n; j++) {
                    if (j === i + 1 || (i === 0 && j === n - 1)) continue;
                    var a1 = p[i], a2 = p[(i + 1) % n], b1 = p[j], b2 = p[(j + 1) % n];
                    if (properCross(a1, a2, b1, b2) || onSeg(a1, a2, b1) || onSeg(a1, a2, b2) || onSeg(b1, b2, a1) || onSeg(b1, b2, a2)) return false;
                }
            }
            return true;
        }

        function area() {
            var p = S.room.polygon, sum = 0;
            for (var i = 0; i < p.length; i++) {
                var q = p[(i + 1) % p.length];
                sum += p[i][0] * q[1] - q[0] * p[i][1];
            }
            return sum / 2;
        }

        // Khung [trái, trên, phải, dưới] nằm trọn trong phòng (chạm tường được) - cùng cách xét với RoomLayout::contains().
        function inside(b) {
            var p = S.room.polygon, n = p.length;
            var c = [[b[0], b[1]], [b[2], b[1]], [b[2], b[3]], [b[0], b[3]]];
            for (var i = 0; i < 4; i++) {
                var u = c[i], v = c[(i + 1) % 4];
                for (var k = 0; k <= 6; k++) {
                    if (!pointIn([u[0] + (v[0] - u[0]) * k / 6, u[1] + (v[1] - u[1]) * k / 6])) return false;
                }
                for (var j = 0; j < n; j++) {
                    if (properCross(u, v, p[j], p[(j + 1) % n])) return false;
                }
            }
            return !p.some(function(w) { return w[0] > b[0] + EPS && w[0] < b[2] - EPS && w[1] > b[1] + EPS && w[1] < b[3] - EPS; });
        }

        /* ---- cửa ra vào: nằm trên cạnh `edge`, cách góc đầu cạnh `offset` m, rộng `width` m ---- */

        function clampDoor() {
            var d = S.room.door;
            if (!d) return;
            if (d.edge >= S.room.polygon.length) d.edge = 0;
            var len = edgeOf(d.edge).len;
            d.width = round(clamp(d.width, Math.min(S.limits.doorMin, len), Math.min(S.limits.doorMax, len)));
            d.offset = round(clamp(d.offset, 0, Math.max(len - d.width, 0)));
        }

        // Điểm gần nhất trên tường: { edge, t (m tính từ góc đầu cạnh), dist }
        function nearestWall(pt) {
            var best = null;
            for (var i = 0; i < S.room.polygon.length; i++) {
                var e = edgeOf(i);
                if (e.len < EPS) continue;
                var ux = (e.b[0] - e.a[0]) / e.len, uy = (e.b[1] - e.a[1]) / e.len;
                var t = clamp((pt[0] - e.a[0]) * ux + (pt[1] - e.a[1]) * uy, 0, e.len);
                var dist = Math.hypot(e.a[0] + ux * t - pt[0], e.a[1] + uy * t - pt[1]);
                if (!best || dist < best.dist) best = { edge: i, t: t, dist: dist };
            }
            return best;
        }

        function doorCentre() {
            var d = S.room.door, e = edgeOf(d.edge), k = (d.offset + d.width / 2) / (e.len || 1);
            return [e.a[0] + (e.b[0] - e.a[0]) * k, e.a[1] + (e.b[1] - e.a[1]) * k];
        }

        // Đặt cửa sao cho tâm cửa ở gần điểm pt nhất (dùng khi kéo cửa, hoặc sau khi thêm / xoá góc phòng)
        function doorAt(pt) {
            var w = nearestWall(pt);
            if (!w) return;
            S.room.door.edge = w.edge;
            S.room.door.offset = w.t - S.room.door.width / 2;
            clampDoor();
        }

        // Giữ tâm kệ để khung chiếm chỗ nằm trọn trong phòng (kệ lớn hơn phòng thì đặt giữa).
        function clampCenter(s, x, y, rot) {
            var b = box(s, 0, 0, rot), bb = bounds();
            var hw = -b[0], hd = -b[1];
            return {
                x: hw * 2 > bb.maxX - bb.minX ? (bb.minX + bb.maxX) / 2 : clamp(x, bb.minX + hw, bb.maxX - hw),
                y: hd * 2 > bb.maxY - bb.minY ? (bb.minY + bb.maxY) / 2 : clamp(y, bb.minY + hd, bb.maxY - hd)
            };
        }

        // Kệ nào đang chồng lên kệ khác hoặc nằm ra ngoài phòng.
        function problems() {
            var placed = S.shelves.filter(function(s) { return s.placed; });
            var bad = {};
            placed.forEach(function(a, i) {
                var ba = box(a);
                if (!inside(ba)) bad[a.id] = 'out';
                placed.slice(i + 1).forEach(function(b) {
                    if (overlaps(ba, box(b))) {
                        bad[a.id] = bad[a.id] || 'clash';
                        bad[b.id] = bad[b.id] || 'clash';
                    }
                });
            });
            return bad;
        }

        // Kích thước gợi ý cho kệ chưa từng đặt: mỗi ô ngang 0,25 m, mỗi dãy sâu 0,3 m.
        function defaultSize(s) {
            var width = 0, depth = 1;
            (s.columns || []).forEach(function(c) {
                var widest = 1;
                c.tiers.forEach(function(t) {
                    widest = Math.max(widest, t.max);
                    depth = Math.max(depth, t.depth);
                });
                width += widest * 0.25 + 0.1;
            });
            return {
                width: clamp(round(Math.max(width, 0.6), 0.1), S.limits.minShelf, S.limits.maxShelf),
                depth: clamp(round(Math.max(depth * 0.3 + 0.1, 0.4), 0.1), S.limits.minShelf, S.limits.maxShelf)
            };
        }

        // Chỗ trống đầu tiên (quét từ góc trên-trái, bước 0,25 m) cho một kệ, null nếu phòng đã kín.
        function freeSpot(s) {
            var others = S.shelves.filter(function(o) { return o.placed && o !== s; }).map(function(o) { return box(o); });
            var b0 = box(s, 0, 0);
            var hw = -b0[0], hd = -b0[1];
            var bb = bounds();
            for (var y = bb.minY + hd; y <= bb.maxY - hd + EPS; y += 0.25) {
                for (var x = bb.minX + hw; x <= bb.maxX - hw + EPS; x += 0.25) {
                    var b = box(s, x, y);
                    if (inside(b) && !others.some(function(o) { return overlaps(b, o); })) return { x: round(x), y: round(y) };
                }
            }
            return null;
        }

        function place(s, at) {
            s.placed = true;
            s.x = at.x;
            s.y = at.y;
        }

        /* ---------- vẽ sơ đồ 2D ---------- */

        function niceStep(scale) {
            var steps = [0.5, 1, 2, 5, 10, 20, 50];
            return steps.find(function(st) { return st * scale >= 34; }) || 100;
        }

        function renderPlan() {
            var bb = bounds();
            var wrapW = Math.max(el.wrap.clientWidth - 52, 200);
            // Đang kéo góc / cửa thì giữ nguyên tỉ lệ, khỏi giật sơ đồ
            if (!S.drag || !S.drag.lockScale) S.scale = clamp(Math.min(wrapW / Math.max(bb.maxX, 1), 540 / Math.max(bb.maxY, 1)), 6, 140);
            var sc = S.scale;
            var W = Math.max(bb.maxX, 0.5) * sc, H = Math.max(bb.maxY, 0.5) * sc;
            var html = '';

            el.plan.style.width = W + 'px';
            el.plan.style.height = H + 'px';
            el.scale.textContent = 'Phòng ' + fmt(S.room.width) + ' × ' + fmt(S.room.depth) + ' m' +
                (isRect() ? '' : ' · ' + S.room.polygon.length + ' góc') + ' · ' + fmt(Math.abs(area())) + ' m² · mỗi ô lưới 1 m';
            root.classList.toggle('zl-readonly', !editable());

            var step = niceStep(sc);
            for (var m = 0; m <= bb.maxX + EPS; m += step) {
                html += '<span class="zl-rule is-top" style="left:' + (m * sc) + 'px">' + fmt(m) + '</span>';
            }
            for (m = 0; m <= bb.maxY + EPS; m += step) {
                html += '<span class="zl-rule is-left" style="top:' + (m * sc) + 'px">' + fmt(m) + '</span>';
            }

            var pts = S.room.polygon.map(function(v) { return (v[0] * sc).toFixed(1) + ',' + (v[1] * sc).toFixed(1); }).join(' ');
            var bad0 = !simple();
            html += '<svg class="zl-svg zl-svg-floor" width="' + W + '" height="' + H + '">' +
                '<defs><pattern id="zl-grid" width="' + sc + '" height="' + sc + '" patternUnits="userSpaceOnUse">' +
                '<path d="M ' + sc + ' 0 L 0 0 0 ' + sc + '" fill="none" stroke="rgba(46,123,196,0.13)" stroke-width="1"/></pattern></defs>' +
                '<polygon points="' + pts + '" fill="#fff"/>' +
                '<polygon points="' + pts + '" fill="url(#zl-grid)" stroke="' + (bad0 ? '#DC2626' : '#2E7BC4') + '" stroke-width="3" stroke-linejoin="round"/>' +
                '</svg>';

            // Lớp điều khiển: chấm góc, dấu + giữa cạnh, cửa (kéo / co giãn)
            var ui = '';
            var n = S.room.polygon.length;
            if (editable() && n < S.limits.maxPoints) {
                for (var i = 0; i < n; i++) {
                    var e = edgeOf(i);
                    if (e.len * sc < 34) continue;
                    var mx = (e.a[0] + e.b[0]) / 2 * sc, my = (e.a[1] + e.b[1]) / 2 * sc;
                    ui += '<g class="zl-mid" data-mid="' + i + '"><title>Thêm góc ở giữa cạnh này</title><circle cx="' + mx.toFixed(1) + '" cy="' + my.toFixed(1) +
                        '" r="8"/><text x="' + mx.toFixed(1) + '" y="' + (my + 4).toFixed(1) + '" text-anchor="middle">+</text></g>';
                }
            }
            if (S.room.door) {
                var d = S.room.door, de = edgeOf(d.edge);
                var ux = (de.b[0] - de.a[0]) / (de.len || 1), uy = (de.b[1] - de.a[1]) / (de.len || 1);
                var x1 = (de.a[0] + ux * d.offset) * sc, y1 = (de.a[1] + uy * d.offset) * sc;
                var x2 = (de.a[0] + ux * (d.offset + d.width)) * sc, y2 = (de.a[1] + uy * (d.offset + d.width)) * sc;
                var line = 'x1="' + x1.toFixed(1) + '" y1="' + y1.toFixed(1) + '" x2="' + x2.toFixed(1) + '" y2="' + y2.toFixed(1) + '"';
                // pháp tuyến hướng vào trong phòng (đa giác chạy theo chiều kim đồng hồ: bên phải chiều đi)
                var nx = -uy, ny = ux;
                ui += '<line class="zl-door-line" ' + line + '/><line class="zl-door-mark" ' + line + '/>' +
                    '<text class="zl-door-text" x="' + ((x1 + x2) / 2 + nx * 14).toFixed(1) + '" y="' + ((y1 + y2) / 2 + ny * 14 + 3).toFixed(1) +
                    '" text-anchor="middle">Cửa ' + fmt(d.width) + ' m</text>';
                if (editable()) {
                    ui += '<line class="zl-dh is-move" data-door="move" ' + line + '><title>Kéo để dời cửa dọc theo tường</title></line>' +
                        '<rect class="zl-dh is-end" data-door="a" x="' + (x1 - 5).toFixed(1) + '" y="' + (y1 - 5).toFixed(1) + '" width="10" height="10" rx="2"><title>Kéo để đổi bề rộng cửa</title></rect>' +
                        '<rect class="zl-dh is-end" data-door="b" x="' + (x2 - 5).toFixed(1) + '" y="' + (y2 - 5).toFixed(1) + '" width="10" height="10" rx="2"><title>Kéo để đổi bề rộng cửa</title></rect>';
                }
            }
            if (editable()) {
                S.room.polygon.forEach(function(v, i) {
                    ui += '<circle class="zl-v' + (S.drag && S.drag.vertex === i ? ' is-active' : '') + '" data-vertex="' + i + '" cx="' + (v[0] * sc).toFixed(1) +
                        '" cy="' + (v[1] * sc).toFixed(1) + '" r="7"><title>Góc ' + (i + 1) + ' (' + fmt(v[0]) + '; ' + fmt(v[1]) +
                        ' m) · kéo để đổi hình · nhấp đúp để xoá góc</title></circle>';
                });
            }

            var bad = problems();
            S.shelves.forEach(function(s) {
                if (!s.placed) return;
                var cols = (s.columns || []).map(function(c) {
                    var widest = c.tiers.reduce(function(mx, t) { return Math.max(mx, t.max); }, 1);
                    return '<span class="zl-shelf-col" style="flex-grow:' + widest + '"></span>';
                }).join('');
                html += '<div class="zl-shelf' + (S.selected === s.id ? ' is-selected' : '') + (bad[s.id] ? ' is-clash' : '') + '"' +
                    ' data-id="' + s.id + '" title="' + esc(s.code + ' - ' + s.name + ' · mặt trước hướng ' + ROTATION_LABELS[s.rotation]) + '"' +
                    ' style="left:' + ((s.x - s.width / 2) * sc) + 'px;top:' + ((s.y - s.depth / 2) * sc) + 'px;width:' + (s.width * sc) +
                    'px;height:' + (s.depth * sc) + 'px;transform:rotate(' + s.rotation + 'deg)">' + cols +
                    '<span class="zl-shelf-front"></span>' +
                    '<span class="zl-shelf-label" style="transform:translate(-50%,-50%) rotate(' + (-s.rotation) + 'deg)">' + esc(s.code) +
                    (s.total ? '<small>' + s.used + '/' + s.total + ' có hàng</small>' : '') + '</span></div>';
            });

            el.plan.innerHTML = html + '<svg class="zl-svg zl-svg-ui" width="' + W + '" height="' + H + '">' + ui + '</svg>';

            var waiting = S.shelves.filter(function(s) { return !s.placed; });
            el.unplaced.innerHTML = waiting.length
                ? '<span><i class="fas fa-box-open mr-1"></i>Kệ/tủ chưa đặt' + (editable() ? ' (kéo vào sơ đồ hoặc bấm để đặt vào chỗ trống)' : '') + ':</span>' +
                    waiting.map(function(s) {
                        return '<button type="button" class="zl-chip" data-id="' + s.id + '"' + (editable() ? '' : ' disabled') + '>' + esc(s.code) + '</button>';
                    }).join('')
                : '';

            var placed = S.shelves.length - waiting.length;
            var counts = { clash: 0, out: 0 };
            Object.keys(bad).forEach(function(id) { counts[bad[id]]++; });
            el.status.innerHTML = (bad0 ? '<span class="bad">Các cạnh phòng đang cắt nhau</span> · ' : '') +
                'Đã đặt ' + placed + '/' + S.shelves.length + ' kệ/tủ' +
                (counts.clash ? ' · <span class="bad">' + counts.clash + ' kệ chồng nhau</span>' : '') +
                (counts.out ? ' · <span class="bad">' + counts.out + ' kệ ra ngoài phòng</span>' : '');
            el.auto.disabled = !waiting.length;

            schedule3d();
        }

        /* ---------- khung chỉnh kệ đang chọn ---------- */

        function stepper(field, label, value, step, min, max) {
            var off = editable() ? '' : ' disabled';
            return '<div class="zl-field"><label>' + label + '</label>' +
                '<div class="zl-row"><div class="zs-stepper zl-step" data-field="' + field + '" data-step="' + step + '">' +
                '<button type="button" data-delta="-1"' + off + '><i class="fas fa-minus"></i></button>' +
                '<input type="number" step="' + step + '" min="' + min + '" max="' + max + '" value="' + round(value) + '"' + off + '>' +
                '<button type="button" data-delta="1"' + off + '><i class="fas fa-plus"></i></button></div><small>m</small></div></div>';
        }

        function renderSelected() {
            var s = byId(S.selected);
            if (!s) {
                el.selected.innerHTML = '<div class="zl-sel-empty"><i class="fas fa-hand-pointer mr-1"></i>' +
                    'Bấm vào một kệ/tủ trên sơ đồ hoặc mô hình 3D để chỉnh vị trí, kích thước và hướng mặt trước.' +
                    (editable() ? ' Phím mũi tên dời kệ 0,1 m (giữ Shift: 0,5 m), phím R xoay, Delete gỡ khỏi mặt bằng.' : '') + '</div>';
                return;
            }

            var b = box(s);
            var off = editable() ? '' : ' disabled';
            el.selected.innerHTML = '<div class="zl-sel-title"><i class="fas fa-layer-group"></i>' + esc(s.code) +
                '<span class="tag">' + esc(s.name) + '</span>' +
                '<span class="tag">' + (s.columns || []).length + ' cột · ' + s.used + '/' + s.total + ' vị trí có hàng</span></div>' +
                (s.placed
                    ? '<div class="zl-sel-grid">' +
                        stepper('shelf.width', 'Bề ngang kệ (mặt trước)', s.width, 0.1, S.limits.minShelf, S.limits.maxShelf) +
                        stepper('shelf.depth', 'Bề sâu kệ', s.depth, 0.1, S.limits.minShelf, S.limits.maxShelf) +
                        stepper('shelf.left', 'Cách mép trái', b[0], 0.1, 0, S.limits.maxRoom) +
                        stepper('shelf.top', 'Cách mép trên', b[1], 0.1, 0, S.limits.maxRoom) +
                        '</div>' +
                        '<div class="zl-field mt-2"><label>Mặt trước (phía lấy hàng) hướng</label><div class="zl-face">' +
                        [0, 90, 180, 270].map(function(rot) {
                            return '<button type="button" data-rot="' + rot + '" class="' + (s.rotation === rot ? 'active' : '') + '"' +
                                ' title="Hướng ' + ROTATION_LABELS[rot] + '"' + off + '><i class="fas ' + ROTATION_ICONS[rot] + '"></i></button>';
                        }).join('') + '</div></div>'
                    : '<div class="zl-sel-empty">Kệ/tủ này chưa đặt vào phòng.</div>') +
                '<div class="zl-sel-actions">' +
                (s.placed && editable() ? '<button type="button" class="btn btn-sm btn-outline-danger zl-unplace"><i class="fas fa-times mr-1"></i>Gỡ khỏi mặt bằng</button>' : '') +
                (!s.placed && editable() ? '<button type="button" class="btn btn-sm btn-outline-primary zl-place"><i class="fas fa-plus mr-1"></i>Đặt vào phòng</button>' : '') +
                '<button type="button" class="btn btn-sm btn-outline-secondary zl-open"><i class="fas fa-th mr-1"></i>Mở cấu trúc kệ</button>' +
                '</div>';
        }

        function renderAll() {
            syncBox();
            clampDoor();
            root.querySelector('[data-field="room.width"] input').value = round(S.room.width);
            root.querySelector('[data-field="room.depth"] input').value = round(S.room.depth);
            root.querySelector('[data-field="room.door_width"] input').value = S.room.door ? round(S.room.door.width) : '';
            el.doorSize.hidden = !S.room.door;
            el.doorAdd.hidden = !!S.room.door;
            el.doorDel.hidden = !S.room.door;
            el.rect.disabled = isRect();
            el.corners.textContent = isRect() ? '' : S.room.polygon.length + ' góc';
            root.querySelectorAll('.zl-bar input, .zl-bar select, .zl-bar .zl-step button, .zl-bar .zl-room .btn').forEach(function(node) {
                if (!editable()) node.disabled = true;
            });
            if (editable()) el.rect.disabled = isRect();
            el.save.hidden = el.reset.hidden = el.auto.hidden = !editable();
            renderPlan();
            renderSelected();
        }

        /* ---------- 3D cả phòng ---------- */

        function shelfModel(s, cells) {
            var levels = 1;
            (s.columns || []).forEach(function(c) {
                c.tiers.forEach(function(t) { levels = Math.max(levels, t.position); });
            });
            var topDown = s.tier_order === 'top_down';
            return {
                unit: 16, gap: 3, pad: 7, tierHeight: 46, boxHeight: 12, levels: levels,
                columns: (s.columns || []).map(function(c) {
                    return {
                        label: c.name,
                        tiers: c.tiers.map(function(t) {
                            var list = [];
                            if (cells) {
                                var busy = {};
                                (t.busy || []).forEach(function(k) { busy[k] = true; });
                                for (var d = 1; d <= t.depth; d++) {
                                    for (var p = 1; p <= t.max; p++) {
                                        var on = busy[p + ':' + d];
                                        list.push({ p: p, d: d, box: !!on, color: on ? '#2E7BC4' : null });
                                    }
                                }
                            }
                            return {
                                label: t.name, first: t.position === 1, max: t.max, depth: t.depth, texture: !cells,
                                level: topDown ? levels - t.position : t.position - 1, cells: list
                            };
                        })
                    };
                })
            };
        }

        function render3d() {
            var bad = problems();
            var placed = S.shelves.filter(function(s) { return s.placed; });
            var total = placed.reduce(function(sum, s) {
                return sum + (s.columns || []).reduce(function(a, c) {
                    return a + c.tiers.reduce(function(b, t) { return b + t.max * t.depth; }, 0);
                }, 0);
            }, 0);
            var cells = total <= MAX_3D_CELLS;

            WmsShelf3d.renderRoom(el.stage, {
                width: bounds().maxX, depth: bounds().maxY, polygon: S.room.polygon, tierMetre: TIER_METRE,
                door: S.room.door ? { edge: S.room.door.edge, offset: S.room.door.offset, width: S.room.door.width } : null,
                empty: 'Chưa đặt kệ/tủ nào vào phòng.',
                shelves: placed.map(function(s) {
                    return {
                        key: String(s.id), label: s.code, x: s.x, y: s.y, width: s.width, depth: s.depth, rotation: s.rotation,
                        selected: S.selected === s.id, clash: !!bad[s.id], model: shelfModel(s, cells)
                    };
                })
            });
        }

        function schedule3d() {
            if (S.raf) return;
            S.raf = requestAnimationFrame(function() {
                S.raf = 0;
                render3d();
            });
        }

        /* ---------- tải / lưu ---------- */

        function load() {
            el.plan.innerHTML = '';
            el.status.textContent = 'Đang tải mặt bằng...';
            return fetch(CFG.layout + '?warehouse_id=' + encodeURIComponent(S.warehouse.id), { headers: { Accept: 'application/json' } })
                .then(function(r) {
                    return r.json().then(function(data) {
                        if (!r.ok) throw new Error(data.message || 'Không tải được mặt bằng.');
                        return data;
                    });
                })
                .then(function(data) {
                    S.limits = data.limits || S.limits;
                    S.shelves = data.shelves.map(function(s) {
                        var size = s.layout ? { width: s.layout.width, depth: s.layout.depth } : defaultSize(s);
                        return {
                            id: Number(s.id), code: s.code, name: s.name, tier_order: s.tier_order, columns: s.columns,
                            total: s.total, used: s.used, placed: !!s.layout,
                            x: s.layout ? s.layout.x : 0, y: s.layout ? s.layout.y : 0,
                            width: size.width, depth: size.depth, rotation: s.layout ? s.layout.rotation : 0
                        };
                    });

                    var layout = data.warehouse.layout;
                    if (layout) {
                        S.room = {
                            polygon: (layout.polygon || rectPolygon(layout.width, layout.depth)).map(function(v) { return [Number(v[0]), Number(v[1])]; }),
                            door: layout.door ? { edge: Number(layout.door.edge), offset: Number(layout.door.offset), width: Number(layout.door.width) } : null
                        };
                    } else {
                        // Phòng chưa khai: gợi ý đủ rộng để xếp các kệ thành một hàng
                        var row = S.shelves.reduce(function(sum, s) { return sum + s.width + 0.5; }, 1);
                        S.room = { polygon: rectPolygon(clamp(Math.ceil(Math.max(row, 8)), 1, S.limits.maxRoom), 6), door: null };
                    }
                    S.selected = null;
                    renderAll();
                    dirty(false);
                })
                .catch(function(error) {
                    el.status.textContent = error.message;
                });
        }

        // Dời cả phòng + kệ về gốc (0,0) trước khi lưu: góc trên-trái của khung bao luôn là (0,0)
        function normalize() {
            var bb = bounds();
            if (Math.abs(bb.minX) < EPS && Math.abs(bb.minY) < EPS) return;
            S.room.polygon = S.room.polygon.map(function(v) { return [round(v[0] - bb.minX), round(v[1] - bb.minY)]; });
            S.shelves.forEach(function(s) {
                if (!s.placed) return;
                s.x = round(s.x - bb.minX);
                s.y = round(s.y - bb.minY);
            });
        }

        el.save.addEventListener('click', function() {
            if (!simple()) {
                toast('warning', 'Các cạnh của phòng đang cắt nhau hoặc có hai góc trùng nhau. Hãy kéo lại các góc phòng trước khi lưu.');
                return;
            }
            if (Math.abs(area()) < 1) {
                toast('warning', 'Phòng quá nhỏ (dưới 1 m²).');
                return;
            }
            normalize();
            renderAll();
            var bad = problems();
            var names = Object.keys(bad).map(function(id) { return byId(id).code; });
            if (names.length) {
                toast('warning', 'Kệ/tủ ' + names.join(', ') + ' đang chồng lên kệ khác hoặc nằm ra ngoài phòng. Hãy dời lại trước khi lưu.');
                return;
            }

            el.save.disabled = true;
            fetch(CFG.saveLayout, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CFG.csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    warehouse_id: S.warehouse.id,
                    room: {
                        polygon: S.room.polygon.map(function(v) { return [round(v[0]), round(v[1])]; }),
                        door: S.room.door ? { edge: S.room.door.edge, offset: round(S.room.door.offset), width: round(S.room.door.width) } : null
                    },
                    shelves: S.shelves.map(function(s) {
                        return { id: s.id, placed: s.placed, x: s.x, y: s.y, width: s.width, depth: s.depth, rotation: s.rotation };
                    })
                })
            })
                .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                .catch(function() { return { ok: false, data: { message: 'Không kết nối được máy chủ.' } }; })
                .then(function(result) {
                    el.save.disabled = false;
                    if (!result.ok) {
                        toast('error', result.data.message || 'Không lưu được mặt bằng.');
                        return;
                    }
                    window.zoneStructureStale = true;
                    toast('success', result.data.message);
                    load();
                });
        });

        el.reset.addEventListener('click', function() {
            if (window.confirm('Bỏ các thay đổi chưa lưu của mặt bằng?')) load();
        });

        el.auto.addEventListener('click', function() {
            var full = [];
            S.shelves.forEach(function(s) {
                if (s.placed) return;
                var spot = freeSpot(s);
                if (spot) place(s, spot);
                else full.push(s.code);
            });
            dirty(true);
            renderAll();
            if (full.length) toast('warning', 'Phòng không còn chỗ trống cho kệ/tủ ' + full.join(', ') + '. Hãy nới kích thước phòng hoặc dời các kệ khác.');
        });

        /* ---------- ô số (phòng + kệ đang chọn) ---------- */

        function setField(field, value) {
            var s = byId(S.selected);
            value = Number(value);
            if (!isFinite(value)) return;

            if (field === 'room.width' || field === 'room.depth') {
                // Co giãn cả hình dạng phòng theo chiều đó (phòng chữ nhật = đổi cạnh như trước)
                var bb = bounds(), axis = field === 'room.width' ? 0 : 1;
                var from = axis ? bb.maxY - bb.minY : bb.maxX - bb.minX, origin = axis ? bb.minY : bb.minX;
                var to = clamp(round(value, 0.1), 1, S.limits.maxRoom);
                if (from < EPS) return;
                var centre = S.room.door ? doorCentre() : null;
                S.room.polygon.forEach(function(v) { v[axis] = round(origin + (v[axis] - origin) * to / from); });
                if (centre) {
                    centre[axis] = origin + (centre[axis] - origin) * to / from;
                    doorAt(centre);
                }
            } else if (field === 'room.door_width') {
                if (!S.room.door) return;
                var mid = S.room.door.offset + S.room.door.width / 2;
                S.room.door.width = round(value, 0.1);
                S.room.door.offset = mid - S.room.door.width / 2;
                clampDoor();
            } else if (s && (field === 'shelf.width' || field === 'shelf.depth')) {
                s[field.slice(6)] = clamp(round(value), S.limits.minShelf, S.limits.maxShelf);
                var c = clampCenter(s, s.x, s.y);
                s.x = round(c.x);
                s.y = round(c.y);
            } else if (s && (field === 'shelf.left' || field === 'shelf.top')) {
                var b = box(s);
                var at = clampCenter(s, field === 'shelf.left' ? s.x + value - b[0] : s.x, field === 'shelf.top' ? s.y + value - b[1] : s.y);
                s.x = round(at.x);
                s.y = round(at.y);
            } else {
                return;
            }
            dirty(true);
            renderAll();
        }

        function currentOf(field) {
            var s = byId(S.selected);
            if (field === 'room.door_width') return S.room.door ? S.room.door.width : 0;
            if (field.indexOf('room.') === 0) return Number(S.room[field.slice(5)]) || 0;
            if (!s) return 0;
            var b = box(s);
            return { 'shelf.width': s.width, 'shelf.depth': s.depth, 'shelf.left': b[0], 'shelf.top': b[1] }[field];
        }

        root.addEventListener('click', function(event) {
            var stepBtn = event.target.closest('.zl-step [data-delta]');
            if (stepBtn) {
                var wrap = stepBtn.closest('.zl-step');
                var field = wrap.dataset.field;
                setField(field, currentOf(field) + Number(stepBtn.dataset.delta) * Number(wrap.dataset.step));
                return;
            }

            if (event.target.closest('.zl-rect') && editable()) {
                var bb = bounds();
                var c0 = S.room.door ? doorCentre() : null;
                S.room.polygon = [[bb.minX, bb.minY], [bb.maxX, bb.minY], [bb.maxX, bb.maxY], [bb.minX, bb.maxY]];
                if (c0) doorAt(c0);
                dirty(true);
                renderAll();
                return;
            }

            if (event.target.closest('.zl-door-add') && editable()) {
                var longest = 0;
                for (var i = 1; i < S.room.polygon.length; i++) if (edgeOf(i).len > edgeOf(longest).len) longest = i;
                var w0 = Math.min(S.limits.doorWidth, edgeOf(longest).len);
                S.room.door = { edge: longest, offset: (edgeOf(longest).len - w0) / 2, width: w0 };
                clampDoor();
                dirty(true);
                renderAll();
                return;
            }

            if (event.target.closest('.zl-door-del') && editable()) {
                S.room.door = null;
                dirty(true);
                renderAll();
                return;
            }

            var face = event.target.closest('.zl-face [data-rot]');
            if (face) {
                rotate(byId(S.selected), Number(face.dataset.rot));
                return;
            }

            if (event.target.closest('.zl-unplace')) {
                unplace(byId(S.selected));
                return;
            }

            if (event.target.closest('.zl-place')) {
                autoPlaceOne(byId(S.selected));
                return;
            }

            if (event.target.closest('.zl-open')) {
                if (S.hooks.onOpenShelf) S.hooks.onOpenShelf(S.selected);
                return;
            }

            // Bấm vào kệ trên mô hình 3D = chọn kệ đó
            var unit = event.target.closest('[data-w3d-shelf]');
            if (unit) {
                S.selected = Number(unit.getAttribute('data-w3d-shelf'));
                renderPlan();
                renderSelected();
            }
        });

        root.addEventListener('change', function(event) {
            var input = event.target.closest('.zl-step input');
            if (input) setField(input.closest('.zl-step').dataset.field, input.value);
        });

        root.addEventListener('dblclick', function(event) {
            var vertex = event.target.closest('[data-vertex]');
            if (vertex && editable()) {
                if (S.room.polygon.length <= 3) {
                    toast('warning', 'Phòng cần ít nhất 3 góc.');
                    return;
                }
                var c1 = S.room.door ? doorCentre() : null;
                S.room.polygon.splice(Number(vertex.getAttribute('data-vertex')), 1);
                if (c1) doorAt(c1);
                dirty(true);
                renderAll();
                return;
            }
            var target = event.target.closest('.zl-shelf, [data-w3d-shelf]');
            if (!target || !S.hooks.onOpenShelf) return;
            S.hooks.onOpenShelf(target.dataset.id || target.getAttribute('data-w3d-shelf'));
        });

        /* ---------- thao tác trên kệ ---------- */

        function rotate(s, rot) {
            if (!s || !s.placed || !editable()) return;
            s.rotation = ((rot % 360) + 360) % 360;
            var c = clampCenter(s, s.x, s.y);
            s.x = round(c.x);
            s.y = round(c.y);
            dirty(true);
            renderAll();
        }

        function unplace(s) {
            if (!s || !editable()) return;
            s.placed = false;
            dirty(true);
            renderAll();
        }

        function autoPlaceOne(s) {
            if (!s || !editable()) return;
            var spot = freeSpot(s);
            if (!spot) {
                toast('warning', 'Phòng không còn chỗ trống vừa kệ/tủ ' + s.code + '. Hãy nới kích thước phòng hoặc dời các kệ khác.');
                return;
            }
            place(s, spot);
            S.selected = s.id;
            dirty(true);
            renderAll();
        }

        // Kéo kệ trên sơ đồ (bước 0,05 m), hoặc kéo chip "kệ chưa đặt" vào sơ đồ.
        function pointAt(event) {
            var r = el.plan.getBoundingClientRect();
            var border = 0;
            return {
                x: (event.clientX - r.left - border) / S.scale,
                y: (event.clientY - r.top - border) / S.scale,
                inside: event.clientX >= r.left && event.clientX <= r.right && event.clientY >= r.top && event.clientY <= r.bottom
            };
        }

        // Kéo góc phòng (bước 0,05 m, hút thẳng hàng với góc bên cạnh), thêm góc từ dấu + giữa cạnh, kéo / co giãn cửa.
        root.addEventListener('pointerdown', function(event) {
            if (event.button !== 0 || !editable()) return;
            var vertex = event.target.closest('[data-vertex]');
            var mid = event.target.closest('[data-mid]');
            var door = event.target.closest('[data-door]');
            if (!vertex && !mid && !door) return;
            event.preventDefault();
            event.stopPropagation();

            if (mid) {
                var i = Number(mid.getAttribute('data-mid'));
                var e = edgeOf(i);
                var c2 = S.room.door ? doorCentre() : null;
                S.room.polygon.splice(i + 1, 0, [round((e.a[0] + e.b[0]) / 2, 0.05), round((e.a[1] + e.b[1]) / 2, 0.05)]);
                if (c2) doorAt(c2);
                S.drag = { vertex: i + 1, lockScale: true, moved: true };
                dirty(true);
                renderPlan();
                return;
            }
            if (vertex) {
                S.drag = { vertex: Number(vertex.getAttribute('data-vertex')), lockScale: true, moved: false };
                return;
            }
            var d = S.room.door;
            S.drag = { door: door.getAttribute('data-door'), lockScale: true, moved: false, end: d.offset + d.width };
        }, true);

        document.addEventListener('pointermove', function(event) {
            var drag = S.drag;
            if (!drag || (drag.vertex == null && !drag.door)) return;
            var p = pointAt(event);
            var pt = [Math.max(0, p.x), Math.max(0, p.y)];

            if (drag.vertex != null) {
                var poly = S.room.polygon, n = poly.length, k = drag.vertex;
                var x = clamp(round(pt[0], 0.05), 0, S.limits.maxRoom), y = clamp(round(pt[1], 0.05), 0, S.limits.maxRoom);
                // hút thẳng hàng với hai góc kề (vẽ tường vuông góc cho dễ)
                [poly[(k + n - 1) % n], poly[(k + 1) % n]].forEach(function(o) {
                    if (Math.abs(o[0] - x) < 0.12) x = o[0];
                    if (Math.abs(o[1] - y) < 0.12) y = o[1];
                });
                if (Math.abs(poly[k][0] - x) < EPS && Math.abs(poly[k][1] - y) < EPS) return;
                poly[k] = [x, y];
                clampDoor();
            } else {
                var dd = S.room.door;
                if (drag.door === 'move') {
                    var w = nearestWall(pt);
                    dd.edge = w.edge;
                    dd.offset = round(w.t - dd.width / 2, 0.05);
                } else {
                    var e2 = edgeOf(dd.edge);
                    var ux = (e2.b[0] - e2.a[0]) / (e2.len || 1), uy = (e2.b[1] - e2.a[1]) / (e2.len || 1);
                    var t = clamp(round((pt[0] - e2.a[0]) * ux + (pt[1] - e2.a[1]) * uy, 0.05), 0, e2.len);
                    if (drag.door === 'a') {
                        t = clamp(t, Math.max(0, drag.end - S.limits.doorMax), drag.end - S.limits.doorMin);
                        dd.offset = t;
                        dd.width = drag.end - t;
                    } else {
                        dd.width = clamp(t - dd.offset, S.limits.doorMin, Math.min(S.limits.doorMax, e2.len - dd.offset));
                    }
                }
                clampDoor();
            }
            drag.moved = true;
            dirty(true);
            syncBox();
            renderPlan();
        });

        document.addEventListener('pointerup', function() {
            var drag = S.drag;
            if (!drag || (drag.vertex == null && !drag.door)) return;
            S.drag = null;
            renderAll();
        }, true);

        root.addEventListener('pointerdown', function(event) {
            if (event.button !== 0) return;
            var node = event.target.closest('.zl-shelf, .zl-chip');
            if (!node) return;
            var s = byId(node.dataset.id);
            if (!s) return;

            S.selected = s.id;
            if (!editable()) {
                renderPlan();
                renderSelected();
                return;
            }
            event.preventDefault();
            var p = pointAt(event);
            S.drag = { shelf: s, chip: node.classList.contains('zl-chip'), dx: s.x - p.x, dy: s.y - p.y, moved: false };
            if (!S.drag.chip) {
                renderPlan();
                renderSelected();
                var again = el.plan.querySelector('.zl-shelf[data-id="' + s.id + '"]');
                if (again) again.classList.add('is-dragging');
            }
        });

        document.addEventListener('pointermove', function(event) {
            var drag = S.drag;
            if (!drag || !drag.shelf) return;
            var p = pointAt(event);
            var s = drag.shelf;

            if (drag.chip && !s.placed) {
                if (!p.inside) return;
                place(s, clampCenter(s, p.x, p.y));
                drag.dx = 0;
                drag.dy = 0;
                renderPlan();
                renderSelected();
            }

            var at = clampCenter(s, round(p.x + drag.dx, 0.05), round(p.y + drag.dy, 0.05));
            if (Math.abs(at.x - s.x) < EPS && Math.abs(at.y - s.y) < EPS) return;
            s.x = round(at.x);
            s.y = round(at.y);
            drag.moved = true;
            dirty(true);

            var node = el.plan.querySelector('.zl-shelf[data-id="' + s.id + '"]');
            if (node) {
                node.style.left = ((s.x - s.width / 2) * S.scale) + 'px';
                node.style.top = ((s.y - s.depth / 2) * S.scale) + 'px';
                node.classList.add('is-dragging');
            }
            var bad = problems();
            el.plan.querySelectorAll('.zl-shelf').forEach(function(n) { n.classList.toggle('is-clash', !!bad[n.dataset.id]); });
            schedule3d();
        });

        document.addEventListener('pointerup', function() {
            var drag = S.drag;
            if (!drag || !drag.shelf) return;
            S.drag = null;
            // Bấm chip mà không kéo vào sơ đồ = đặt vào chỗ trống đầu tiên
            if (drag.chip && !drag.shelf.placed) {
                autoPlaceOne(drag.shelf);
                return;
            }
            renderAll();
        });

        document.addEventListener('keydown', function(event) {
            if (root.hidden || !editable() || S.selected == null) return;
            if (event.target.closest && event.target.closest('input, select, textarea, .swal2-container')) return;
            var s = byId(S.selected);
            if (!s) return;

            var step = event.shiftKey ? 0.5 : 0.1;
            var moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] };
            if (moves[event.key] && s.placed) {
                event.preventDefault();
                var at = clampCenter(s, s.x + moves[event.key][0], s.y + moves[event.key][1]);
                s.x = round(at.x);
                s.y = round(at.y);
                dirty(true);
                renderAll();
            } else if (event.key === 'r' || event.key === 'R') {
                event.preventDefault();
                rotate(s, s.rotation + 90);
            } else if (event.key === 'Delete') {
                event.preventDefault();
                unplace(s);
            } else if (event.key === 'Escape') {
                S.selected = null;
                renderPlan();
                renderSelected();
            }
        });

        window.addEventListener('resize', function() {
            if (!root.hidden && S.room) renderPlan();
        });

        window.ZoneLayout = {
            open: function(warehouse, hooks) {
                S.warehouse = warehouse;
                S.hooks = hooks || {};
                S.drag = null;
                return load();
            }
        };
    });
</script>
