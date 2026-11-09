{{--
|--------------------------------------------------------------------------
| TỒN THEO ĐỊNH KHU - SƠ ĐỒ 3D TỪNG KỆ/TỦ
|--------------------------------------------------------------------------
| Thêm nút "Thẻ | 3D | Mặt bằng" vào sơ đồ thẻ của tab Theo định khu (Tồn Kho Vật Tư / Hoá Chất / Chất Chuẩn).
| Chế độ 3D: chọn kệ/tủ ở danh sách bên trái, kệ dựng 3D bên phải theo đúng lưới Định Khu
| (cột → tầng → ô Ngang × Sâu). Vị trí đang có hàng là thùng hàng nổi lên, tô màu theo tình trạng
| (hoặc loại định khu); vị trí trống là ô phẳng. Bộ lọc tình trạng / loại định khu / ô tìm kiếm có
| sẵn của sơ đồ thẻ làm mờ các vị trí không khớp. Bấm thùng hàng = bấm thẻ vị trí (mở chi tiết).
| Có từ khoá tìm (gõ, máy quét cầm tay hoặc nút camera cạnh ô tìm) thì thùng khớp nhấp nháy và khung 3D bay tới
| chỗ đó (WmsShelf3d.focus). Khung 3D / Mặt bằng cao tới gần đáy cửa sổ (fitHeight).
|
|   window.invZone3d.init(rootEl, { zones, nodes, item })
|
| Tuỳ chọn thêm (trang tra cứu công khai public/stockLookup): open(key) tự mở chi tiết khi bấm thùng hàng,
| zoneLabels {type: nhãn}, modeKey (khoá localStorage nhớ kiểu xem), defaultMode. Thuộc tính data-z3d-keyword
| trên rootEl = từ khoá đã lọc ở máy chủ: vị trí có node.match = true sáng lên / nhấp nháy như khi tìm kiếm.
|
| zones: warehouses/shelves/columns/tiers/locations (mảng hoặc map theo id) - cột position/depth/
|        tier_order lấy từ zoneOptions() của controller.
| nodes: { 'L<id vị trí>': { code, state, zoneType, badge {bg}, lots, alerts, search, preview|items } }
--}}
@include('pages.shared.shelf3d')

@once
<style>
    .mz-view {
        display: inline-flex;
        border: 1px solid #dbe5f0;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        background: #fff;
    }

    .mz-view button {
        border: none;
        background: none;
        padding: 4px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .mz-view button + button {
        border-left: 1px solid #dbe5f0;
    }

    .mz-view button:hover {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .mz-view button.is-active {
        background: var(--primary);
        color: #fff;
    }

    .mz-3d {
        display: none;
        grid-template-columns: 260px minmax(0, 1fr);
        gap: 14px;
        margin-top: 4px;
    }

    .mz-mode-3d .mz-3d {
        display: grid;
    }

    .mz-mode-3d .mz-body,
    .mz-mode-3d > .mz-wh,
    .mz-mode-3d > .mz-unzoned,
    .mz-mode-3d .mz-noresult,
    .mz-mode-3d .mz-toggle-all,
    .mz-mode-3d #mzToggleAll {
        display: none !important;
    }

    /* chiều cao do JS tính (fitHeight): khung 3D kéo tới gần đáy cửa sổ, danh sách bên trái cao bằng */
    .mz-3d-list {
        max-height: var(--mz3d-list, 560px);
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: var(--border-radius-lg);
        background: #fff;
        padding: 8px;
    }

    .mz-3d-wh {
        font-size: 0.74rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 8px 6px 4px;
    }

    .mz-3d-wh i {
        color: var(--primary-light);
        margin-right: 4px;
    }

    .mz-3d-shelf {
        display: block;
        width: 100%;
        text-align: left;
        border: 1px solid transparent;
        border-radius: var(--border-radius-md);
        background: none;
        padding: 7px 10px;
        margin-bottom: 2px;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s, opacity 0.2s;
    }

    .mz-3d-shelf:hover {
        background: var(--primary-soft);
    }

    .mz-3d-shelf.is-active {
        background: var(--primary-soft);
        border-color: var(--primary);
    }

    .mz-3d-shelf.is-miss {
        opacity: 0.45;
    }

    .mz-3d-shelf .nm {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .mz-3d-shelf .nm .hit {
        margin-left: auto;
        background: var(--accent);
        color: #fff;
        border-radius: 999px;
        padding: 0 7px;
        font-size: 0.7rem;
    }

    .mz-3d-shelf .sub {
        display: block;
        font-size: 0.72rem;
        color: #64748b;
        margin-top: 1px;
    }

    .mz-3d-shelf .sub .alert {
        color: #B45309;
        font-weight: 700;
    }

    .mz-3d-bar {
        height: 4px;
        border-radius: 999px;
        background: #EEF2F6;
        margin-top: 5px;
        overflow: hidden;
    }

    .mz-3d-bar span {
        display: block;
        height: 100%;
        background: #16A34A;
    }

    .mz-3d-main {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }

    .mz-3d-title {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
        font-weight: 700;
        color: var(--primary);
    }

    .mz-3d-title .tag {
        font-size: 0.74rem;
        font-weight: 600;
        color: #475569;
        background: var(--primary-soft);
        border-radius: 999px;
        padding: 2px 10px;
    }

    .mz-3d-title .tag.is-alert {
        background: #FEF3C7;
        color: #92400E;
    }

    .mz-3d-title .mz-3d-unzoned {
        border: 1px solid #FCD34D;
        cursor: pointer;
        transition: all 0.2s;
    }

    .mz-3d-title .mz-3d-unzoned:hover {
        background: #FDE68A;
        transform: translateY(-1px);
    }

    .mz-3d-title .mz-3d-unzoned i {
        font-size: 0.68rem;
        margin-left: 3px;
    }

    .mz-3d-main .w3d {
        height: var(--mz3d-h, 560px);
    }

    .mz-3d-none {
        padding: 30px 12px;
        text-align: center;
        color: #94a3b8;
        font-size: 0.84rem;
    }

    /* ---------- chế độ Mặt bằng: danh sách kho/phòng + sơ đồ thu nhỏ ---------- */
    .mz-3d-room {
        display: block;
        width: 100%;
        text-align: left;
        border: 1px solid transparent;
        border-radius: var(--border-radius-md);
        background: none;
        padding: 7px 10px;
        margin-bottom: 2px;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s, opacity 0.2s;
    }

    .mz-3d-room:hover,
    .mz-3d-room.is-active {
        background: var(--primary-soft);
    }

    .mz-3d-room.is-active {
        border-color: var(--primary);
    }

    .mz-3d-room.is-miss {
        opacity: 0.45;
    }

    .mz-3d-room .nm {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .mz-3d-room .nm .hit {
        margin-left: auto;
        background: var(--accent);
        color: #fff;
        border-radius: 999px;
        padding: 0 7px;
        font-size: 0.7rem;
    }

    .mz-3d-room .sub {
        display: block;
        font-size: 0.72rem;
        color: #64748b;
        margin-top: 1px;
    }

    .mz-plan-box {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #e2e8f0;
    }

    .mz-plan-head {
        font-size: 0.74rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 6px;
    }

    .mz-plan {
        position: relative;
        margin: 4px auto 6px;
        background-color: #fff;
        background-image:
            linear-gradient(90deg, rgba(var(--primary-rgb), 0.1) 1px, transparent 1px),
            linear-gradient(rgba(var(--primary-rgb), 0.1) 1px, transparent 1px);
        border: 2px solid var(--primary);
        box-sizing: content-box;
    }

    .mz-plan-door {
        position: absolute;
        background: #fff;
        border: 2px dashed var(--accent);
    }

    .mz-plan-shelf {
        position: absolute;
        border: 1px solid var(--primary);
        background: rgba(var(--primary-rgb), 0.1);
        border-bottom-width: 3px;
        cursor: pointer;
        padding: 0;
        transition: background 0.2s, box-shadow 0.2s;
    }

    .mz-plan-shelf:hover {
        box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.35);
    }

    .mz-plan-shelf.is-stocked {
        background: rgba(22, 163, 74, 0.18);
    }

    .mz-plan-shelf.is-alert {
        background: rgba(245, 158, 11, 0.25);
    }

    .mz-plan-shelf.is-hit {
        background: rgba(23, 184, 212, 0.35);
        border-color: var(--accent);
        box-shadow: 0 0 0 2px rgba(23, 184, 212, 0.35);
    }

    .mz-plan-shelf.is-miss {
        opacity: 0.4;
    }

    /* đang tìm kiếm: kệ có vị trí khớp nhấp nháy trên sơ đồ thu nhỏ */
    @keyframes mz-plan-seek {
        50% {
            background: rgba(23, 184, 212, 0.75);
            box-shadow: 0 0 0 3px #fff, 0 0 10px 4px rgba(23, 184, 212, 0.8);
        }
    }

    .mz-plan.is-seek .mz-plan-shelf.is-hit {
        animation: mz-plan-seek 1s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .mz-plan.is-seek .mz-plan-shelf.is-hit {
            animation: none;
        }
    }

    .mz-plan-shelf span {
        position: absolute;
        left: 50%;
        top: 50%;
        font-size: 9px;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
        pointer-events: none;
    }

    .mz-plan-note {
        font-size: 0.72rem;
        color: #64748b;
    }

    .mz-plan-note.is-warn {
        color: #B45309;
    }

    @media (max-width: 992px) {
        .mz-3d {
            grid-template-columns: minmax(0, 1fr);
        }

        .mz-3d-list {
            max-height: 220px;
        }

        .mz-3d-main .w3d {
            min-height: 380px;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        var STATE_COLORS = { ok: '#16A34A', warn: '#F59E0B', danger: '#DC2626' };
        var STATE_LABELS = { ok: 'Đang có hàng', warn: 'Cần chú ý', danger: 'Hết hạn / âm kho' };
        var NONE_COLOR = '#94A3B8';
        var MODE_KEY = 'wms.inv.zoneView';
        var COLOR_KEY = 'wms.inv.zone3dColor';

        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function read(key, fallback) {
            try { return localStorage.getItem(key) || fallback; } catch (err) { return fallback; }
        }

        function write(key, value) {
            try { localStorage.setItem(key, value); } catch (err) { /* bỏ qua */ }
        }

        // Màu nhạt của một mã hex (ô trống theo loại định khu).
        function soften(hex) {
            var n = parseInt(String(hex).replace('#', ''), 16);
            if (isNaN(n)) return '#EEF2F6';
            return 'rgba(' + ((n >> 16) & 255) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',0.25)';
        }

        function byCode(a, b) {
            return String(a.code || a.name || '').localeCompare(String(b.code || b.name || ''), 'vi', { numeric: true });
        }

        // Theo thứ tự đã khai ở Định Khu (position), mục chưa có thứ tự xếp sau theo mã.
        function byPosition(a, b) {
            var pa = Number(a.position) || 0, pb = Number(b.position) || 0;
            if (pa && pb && pa !== pb) return pa - pb;
            if (!pa !== !pb) return pa ? -1 : 1;
            return byCode(a, b);
        }

        function asMap(list) {
            var map = {};
            if (Array.isArray(list)) {
                list.forEach(function(x) { if (x && x.id != null) map[x.id] = x; });
            } else {
                Object.keys(list || {}).forEach(function(id) { map[id] = $.extend({ id: Number(id) }, list[id]); });
            }
            return map;
        }

        function values(map) {
            return Object.keys(map).map(function(k) { return map[k]; });
        }

        /* ---------- dựng cây kệ → cột → tầng → ô có toạ độ ---------- */
        function buildShelves(zones) {
            var warehouses = asMap(zones.warehouses);
            var shelves = asMap(zones.shelves);
            var columns = asMap(zones.columns);
            var tiers = asMap(zones.tiers);
            var locations = values(asMap(zones.locations));
            var out = {};

            locations.forEach(function(loc) {
                if (!loc.shelf_id || !loc.column_id || !loc.tier_id) return;
                var s = out[loc.shelf_id] = out[loc.shelf_id] || $.extend({ columns: {} }, shelves[loc.shelf_id] || { id: loc.shelf_id });
                s.warehouseKey = String(s.warehouse_id || loc.warehouse_id || 0);
                s.warehouse = warehouses[s.warehouseKey] || { name: 'Chưa gán kho/phòng' };
                var c = s.columns[loc.column_id] = s.columns[loc.column_id] || $.extend({ tiers: {} }, columns[loc.column_id] || { id: loc.column_id });
                var t = c.tiers[loc.tier_id] = c.tiers[loc.tier_id] || $.extend({ locations: [] }, tiers[loc.tier_id] || { id: loc.tier_id });
                t.locations.push(loc);
            });

            // Kệ có vị trí của loại hàng này thì dựng đủ mọi cột/tầng của kệ: tầng chỉ dành cho loại
            // hàng khác vẫn hiện thành mặt kệ trống, để kệ 3D đúng chiều cao và thứ tự như ngoài thực tế.
            values(out).forEach(function(s) {
                values(columns).forEach(function(c) {
                    if (String(c.shelf_id) === String(s.id) && !s.columns[c.id]) s.columns[c.id] = $.extend({ tiers: {} }, c);
                });
                values(s.columns).forEach(function(c) {
                    values(tiers).forEach(function(t) {
                        if (String(t.column_id) === String(c.id) && !c.tiers[t.id]) c.tiers[t.id] = $.extend({ locations: [] }, t);
                    });
                });
            });

            return values(out).map(function(s) {
                var levels = 0;
                s.columnList = values(s.columns).sort(byPosition).map(function(c) {
                    var rank = 0;
                    c.tierList = values(c.tiers).sort(byPosition).map(function(t) {
                        // Hàng của tầng theo thứ tự thật trong cột: tầng chỉ chứa loại hàng khác không có ở đây
                        // nhưng vẫn giữ chỗ, để các tầng còn lại không bị tụt xuống.
                        rank = Number(t.position) > rank ? Number(t.position) : rank + 1;
                        t.rank = rank;
                        // Ô chưa có toạ độ (khai lẻ trước khi có lưới) xếp tiếp sau ô xa nhất theo mã.
                        var placed = t.locations.filter(function(l) { return Number(l.position) > 0; });
                        var next = placed.reduce(function(m, l) { return Math.max(m, Number(l.position)); }, 0);
                        t.locations.filter(function(l) { return !(Number(l.position) > 0); }).sort(byCode).forEach(function(l) {
                            l._p = ++next;
                        });
                        t.cells = t.locations.map(function(l) {
                            return { loc: l, p: Number(l.position) > 0 ? Number(l.position) : l._p, d: Math.max(1, Number(l.depth) || 1) };
                        });
                        t.max = t.cells.reduce(function(m, c) { return Math.max(m, c.p); }, 1);
                        t.depth = t.cells.reduce(function(m, c) { return Math.max(m, c.d); }, 1);
                        return t;
                    });
                    levels = Math.max(levels, rank);
                    return c;
                });
                s.levels = levels;
                return s;
            }).sort(function(a, b) {
                return String(a.warehouse.name).localeCompare(String(b.warehouse.name), 'vi') || byCode(a, b);
            });
        }

        /* ---------- bộ lọc: đọc thẳng trạng thái nút / ô tìm của sơ đồ thẻ ---------- */
        function filterOf(root) {
            var st = root.querySelector('.mz-filter[data-state].is-active');
            var zt = root.querySelector('.mz-zt-filter.is-active');
            var input = root.querySelector('.mz-search input');
            var f = {
                state: st ? (st.getAttribute('data-state') || '') : '',
                zone: zt ? zt.getAttribute('data-zone-type') : '*',
                q: input ? String(input.value || '').trim().toLowerCase() : '',
                // Từ khoá đã lọc ở máy chủ (trang tra cứu công khai): vị trí khớp mang node.match = true
                k: String(root.getAttribute('data-z3d-keyword') || '')
            };
            f.active = f.state !== '' || f.zone !== '*' || f.q !== '' || f.k !== '';
            return f;
        }

        function matches(f, node) {
            var st = node ? node.state : 'empty';
            var okState = f.state === '' || (f.state === 'empty' && st === 'empty') ||
                (f.state === 'stocked' && st !== 'empty') || (f.state === 'alert' && (st === 'warn' || st === 'danger'));
            var okZone = f.zone === '*' || ((node && node.zoneType) || '') === f.zone;
            var okText = f.q === '' || (!!node && String(node.search || '').indexOf(f.q) >= 0);
            var okKey = f.k === '' || (!!node && !!node.match);
            return okState && okZone && okText && okKey;
        }

        function names(node) {
            return (node.preview || node.items || []).slice(0, 3).map(function(x) {
                return x.name || x.material_name || x.code;
            }).filter(Boolean);
        }

        function statOf(ctx, shelf, f) {
            var st = { locations: 0, filled: 0, lots: 0, alerts: 0, hits: 0 };
            shelf.columnList.forEach(function(c) {
                c.tierList.forEach(function(t) {
                    t.cells.forEach(function(cell) {
                        var node = ctx.nodes['L' + cell.loc.id];
                        st.locations++;
                        if (node && node.state !== 'empty') st.filled++;
                        st.lots += node ? Number(node.lots) || 0 : 0;
                        st.alerts += node ? Number(node.alerts) || 0 : 0;
                        if (f.active && matches(f, node)) st.hits++;
                    });
                });
            });
            return st;
        }

        /* ---------- vẽ ---------- */
        function legend(ctx) {
            if (ctx.color === 'zone') {
                return Object.keys(ctx.zoneTypes).map(function(type) {
                    return '<span><i style="background:' + esc(ctx.zoneTypes[type].bg) + '"></i>' + esc(ctx.zoneLabels[type] || type) + '</span>';
                }).join('') + '<span><i style="background:' + NONE_COLOR + '"></i>Chưa phân loại</span>';
            }
            return Object.keys(STATE_LABELS).map(function(state) {
                return '<span><i style="background:' + STATE_COLORS[state] + '"></i>' + STATE_LABELS[state] + '</span>';
            }).join('') + '<span><i style="background:#EEF2F6"></i>Trống</span>';
        }

        function cellOf(ctx, cell, f) {
            var node = ctx.nodes['L' + cell.loc.id];
            var state = node ? node.state : 'empty';
            var zoneColor = node && node.zoneType && ctx.zoneTypes[node.zoneType] ? ctx.zoneTypes[node.zoneType].bg : null;
            var stocked = state !== 'empty';
            var color;

            if (ctx.color === 'zone') color = stocked ? (zoneColor || NONE_COLOR) : (zoneColor ? soften(zoneColor) : null);
            else color = stocked ? STATE_COLORS[state] : null;

            var list = node ? names(node) : [];
            var title = (cell.loc.code || '') + ' · ' + (stocked
                ? (node.lots || 0) + ' mã' + (list.length ? ': ' + list.join(', ') : '') + (node.alerts ? ' · ' + node.alerts + ' cần chú ý' : '')
                : 'Đang trống');
            var hit = f.active && matches(f, node);

            return {
                p: cell.p, d: cell.d, key: stocked ? 'L' + cell.loc.id : null, title: title,
                color: color, box: stocked, dim: f.active && !hit, hit: hit
            };
        }

        // Mô hình 3D của một kệ. room = đang vẽ trong cả phòng: kệ bị thu nhỏ nên chỉ vẽ ô có hàng, thùng cao
        // hơn, thùng khớp tìm kiếm tô màu nổi bật để nhìn từ xa vẫn thấy.
        var HIT_COLOR = '#17B8D4';

        function shelfModel(ctx, shelf, f, room) {
            var topDown = shelf.tier_order === 'top_down';
            return {
                unit: 22, gap: 4, pad: 8, tierHeight: 54, boxHeight: room ? 34 : 17,
                levels: shelf.levels,
                columns: shelf.columnList.map(function(c) {
                    return {
                        label: c.name || c.code || '',
                        tiers: c.tierList.map(function(t) {
                            return {
                                label: t.name || t.code || '',
                                first: t.rank === 1,
                                level: topDown ? shelf.levels - t.rank : t.rank - 1,
                                max: t.max,
                                depth: t.depth,
                                title: (c.name || '') + ' · ' + (t.name || ''),
                                cells: t.cells.map(function(cell) { return cellOf(ctx, cell, f); })
                                    .filter(function(cell) { return !room || cell.box; })
                                    .map(function(cell) {
                                        if (room && cell.hit && cell.box) cell.color = HIT_COLOR;
                                        return cell;
                                    })
                            };
                        })
                    };
                })
            };
        }

        function unzonedTag(ctx) {
            return ctx.unzoned ? '<button type="button" class="tag is-alert mz-3d-unzoned" title="Chuyển sang chế độ Thẻ, mở khối Chưa xếp vị trí">' +
                ctx.unzoned + ' mã chưa xếp vị trí <i class="fas fa-arrow-right"></i></button>' : '';
        }

        // Khung 3D / Mặt bằng cao tới gần đáy cửa sổ (khi trang ở đầu), tối thiểu 560px (380px trên màn hình hẹp);
        // danh sách bên trái cao bằng khung. Gọi sau khi đã viết dòng tiêu đề (tiêu đề xuống dòng thì khung lùi xuống).
        function fitHeight(ctx) {
            if (ctx.mode === 'cards' || !ctx.panel.getClientRects().length) return;
            var title = ctx.panel.querySelector('.mz-3d-title');
            var list = ctx.panel.querySelector('.mz-3d-list');
            var scrollY = window.pageYOffset || 0;
            var stageTop = title.getBoundingClientRect().bottom + 8 + scrollY;
            var height = Math.max(window.innerWidth <= 992 ? 380 : 560, Math.round(window.innerHeight - stageTop - 16));
            var listHeight = Math.max(220, Math.round(stageTop + height - (list.getBoundingClientRect().top + scrollY)));

            if (ctx.panel.style.getPropertyValue('--mz3d-h') !== height + 'px') ctx.panel.style.setProperty('--mz3d-h', height + 'px');
            if (ctx.panel.style.getPropertyValue('--mz3d-list') !== listHeight + 'px') ctx.panel.style.setProperty('--mz3d-list', listHeight + 'px');
        }

        // Phóng to tối đa khi bay tới ô khớp: cả phòng thì kệ đã bị thu nhỏ nên cho phóng to hơn.
        function seekZoom(ctx) {
            return ctx.mode === 'room' ? 3 : 2.5;
        }

        // Tìm kiếm (gõ hoặc quét mã): ô khớp nhấp nháy và khung bay tới chỗ đó. Chỉ bay lại khi đổi từ khoá hoặc đổi
        // kệ / kho đang xem, để bộ lọc tình trạng hay nút đổi màu không kéo khung nhìn đi; xoá từ khoá thì bay về toàn cảnh.
        function seek(ctx) {
            var f = filterOf(ctx.root);
            var words = f.q + f.k;
            var key = ctx.mode + '|' + (ctx.mode === 'room' ? ctx.room : ctx.current) + '|' + f.q + '|' + f.k;
            ctx.stage.classList.toggle('is-seek', words !== '');
            if (key === ctx.seekKey || !ctx.stage.getClientRects().length) return;
            ctx.seekKey = key;

            // Chế độ Mặt bằng: chỉ nhấp nháy kệ khớp, KHÔNG zoom lại gần (giữ nguyên toàn cảnh kho/phòng)
            if (ctx.mode !== 'room' && words !== '' && WmsShelf3d.focus(ctx.stage, { maxZoom: seekZoom(ctx) })) {
                ctx.zoomed = true;
            } else if (ctx.zoomed) {
                WmsShelf3d.focus(ctx.stage, { reset: true });
                ctx.zoomed = false;
            }
        }

        function renderStage(ctx) {
            var f = filterOf(ctx.root);
            var shelf = ctx.shelves.find(function(s) { return String(s.id) === String(ctx.current); });
            var title = ctx.panel.querySelector('.mz-3d-title');

            if (!shelf) {
                title.innerHTML = '';
                fitHeight(ctx);
                WmsShelf3d.render(ctx.stage, { columns: [], empty: 'Chưa có kệ/tủ nào được khai lưới vị trí ở Dữ Liệu Gốc → Định Khu.' });
                return;
            }

            var st = statOf(ctx, shelf, f);
            title.innerHTML = '<i class="fas fa-layer-group"></i> ' + esc(shelf.name || shelf.code || 'Kệ/Tủ') +
                (shelf.code ? '<span class="tag">' + esc(shelf.code) + '</span>' : '') +
                '<span class="tag">' + shelf.columnList.length + ' cột</span>' +
                '<span class="tag">' + st.filled + '/' + st.locations + ' vị trí có hàng · ' + st.lots + ' mã</span>' +
                (st.alerts ? '<span class="tag is-alert">' + st.alerts + ' cần chú ý</span>' : '') +
                (f.active ? '<span class="tag">' + st.hits + ' vị trí khớp bộ lọc</span>' : '') +
                unzonedTag(ctx);

            fitHeight(ctx);
            WmsShelf3d.render(ctx.stage, shelfModel(ctx, shelf, f, false));
            ctx.stage.querySelector('.w3d-legend').innerHTML = legend(ctx);
        }

        /* ---------- chế độ Mặt bằng: cả kho/phòng theo mặt bằng đã khai ở Định Khu ---------- */

        // Kích thước gợi ý cho kệ chưa đặt trên mặt bằng (giống Định Khu): 0,25 m mỗi ô ngang, 0,3 m mỗi dãy sâu.
        function defaultSize(shelf) {
            var width = 0, depth = 1;
            shelf.columnList.forEach(function(c) {
                var widest = 1;
                c.tierList.forEach(function(t) {
                    widest = Math.max(widest, t.max);
                    depth = Math.max(depth, t.depth);
                });
                width += widest * 0.25 + 0.1;
            });
            return { width: Math.max(Math.round(width * 10) / 10, 0.6), depth: Math.max(Math.round((depth * 0.3 + 0.1) * 10) / 10, 0.4) };
        }

        // Gom kệ theo kho/phòng. Kho đã khai mặt bằng: kệ đặt đúng vị trí, kệ chưa đặt liệt kê riêng. Kho chưa khai:
        // xếp tạm các kệ thành một hàng để vẫn xem / tìm được trên 3D.
        function buildRooms(ctx) {
            var rooms = {};
            ctx.shelves.forEach(function(shelf) {
                var key = shelf.warehouseKey || '0';
                var room = rooms[key] = rooms[key] || { key: key, wh: shelf.warehouse, shelves: [] };
                room.shelves.push(shelf);
            });

            return values(rooms).map(function(room) {
                var wh = room.wh;
                var width = Number(wh.layout_width) || 0, depth = Number(wh.layout_depth) || 0;
                room.items = [];
                room.unplaced = [];

                if (width > 0 && depth > 0) {
                    room.width = width;
                    room.depth = depth;
                    room.door = wh.layout_door_wall ? { wall: wh.layout_door_wall, offset: Number(wh.layout_door_offset) || 0, width: 1 } : null;
                    room.shelves.forEach(function(shelf) {
                        if (shelf.layout_x == null || shelf.layout_x === '') {
                            room.unplaced.push(shelf);
                            return;
                        }
                        room.items.push({
                            shelf: shelf, x: Number(shelf.layout_x), y: Number(shelf.layout_y),
                            width: Number(shelf.layout_width) || defaultSize(shelf).width,
                            depth: Number(shelf.layout_depth) || defaultSize(shelf).depth,
                            rotation: Number(shelf.layout_rotation) || 0
                        });
                    });
                } else {
                    room.virtual = true;
                    var x = 0.5, deepest = 0.4;
                    room.shelves.forEach(function(shelf) {
                        var size = defaultSize(shelf);
                        room.items.push({ shelf: shelf, x: x + size.width / 2, y: 0.5 + size.depth / 2, width: size.width, depth: size.depth, rotation: 0 });
                        x += size.width + 0.6;
                        deepest = Math.max(deepest, size.depth);
                    });
                    room.width = Math.max(Math.ceil(x - 0.1), 4);
                    room.depth = Math.max(Math.ceil(deepest + 2), 3);
                    room.door = null;
                }
                return room;
            }).sort(function(a, b) {
                return String(a.wh.name || '').localeCompare(String(b.wh.name || ''), 'vi');
            });
        }

        function roomStat(ctx, room, f) {
            var acc = { locations: 0, filled: 0, lots: 0, alerts: 0, hits: 0, byShelf: {} };
            room.shelves.forEach(function(shelf) {
                var st = statOf(ctx, shelf, f);
                acc.byShelf[shelf.id] = st;
                ['locations', 'filled', 'lots', 'alerts', 'hits'].forEach(function(k) { acc[k] += st[k]; });
            });
            return acc;
        }

        function currentRoom(ctx) {
            return ctx.rooms.find(function(room) { return room.key === String(ctx.room); }) || ctx.rooms[0] || null;
        }

        function renderRoomStage(ctx) {
            var f = filterOf(ctx.root);
            var room = currentRoom(ctx);
            var title = ctx.panel.querySelector('.mz-3d-title');

            if (!room) {
                title.innerHTML = '';
                fitHeight(ctx);
                WmsShelf3d.render(ctx.stage, { columns: [], empty: 'Chưa có kệ/tủ nào được khai lưới vị trí ở Dữ Liệu Gốc → Định Khu.' });
                return;
            }

            var st = roomStat(ctx, room, f);
            title.innerHTML = '<i class="fas fa-vector-square"></i> ' + esc(room.wh.name || 'Kho/Phòng') +
                (room.wh.code ? '<span class="tag">' + esc(room.wh.code) + '</span>' : '') +
                '<span class="tag">' + (room.virtual ? 'Chưa khai mặt bằng' : 'Phòng ' + room.width + ' × ' + room.depth + ' m') + '</span>' +
                '<span class="tag">' + room.items.length + ' kệ/tủ</span>' +
                '<span class="tag">' + st.filled + '/' + st.locations + ' vị trí có hàng · ' + st.lots + ' mã</span>' +
                (st.alerts ? '<span class="tag is-alert">' + st.alerts + ' cần chú ý</span>' : '') +
                (f.active ? '<span class="tag">' + st.hits + ' vị trí khớp bộ lọc</span>' : '') +
                unzonedTag(ctx);

            fitHeight(ctx);
            WmsShelf3d.renderRoom(ctx.stage, {
                width: room.width, depth: room.depth, door: room.door, tierMetre: 0.4,
                empty: 'Kho/phòng này chưa đặt kệ/tủ nào trên mặt bằng.',
                shelves: room.items.map(function(item) {
                    var hits = st.byShelf[item.shelf.id].hits;
                    return {
                        key: String(item.shelf.id),
                        label: (item.shelf.code || item.shelf.name || '') + (f.active && hits ? ' · ' + hits + ' khớp' : ''),
                        x: item.x, y: item.y, width: item.width, depth: item.depth, rotation: item.rotation,
                        selected: f.active && hits > 0,
                        model: shelfModel(ctx, item.shelf, f, true)
                    };
                })
            });
            ctx.stage.querySelector('.w3d-legend').innerHTML = legend(ctx) +
                (f.active ? '<span><i style="background:' + HIT_COLOR + '"></i>Khớp bộ lọc / tìm kiếm</span>' : '');
        }

        // Sơ đồ mặt bằng thu nhỏ: kệ khớp bộ lọc sáng lên, bấm vào kệ để xem 3D riêng kệ đó.
        function planMarkup(ctx, room, st, f, width) {
            var sc = Math.min(width / room.width, 200 / room.depth);
            var html = '<div class="mz-plan' + (f.q || f.k ? ' is-seek' : '') + '" style="width:' + (room.width * sc) + 'px;height:' + (room.depth * sc) +
                'px;background-size:' + sc + 'px ' + sc + 'px">';

            if (room.door) {
                var dw = room.door.width * sc, off = room.door.offset * sc, t = 6, pos;
                if (room.door.wall === 'top') pos = 'left:' + off + 'px;top:-4px;width:' + dw + 'px;height:' + t + 'px';
                if (room.door.wall === 'bottom') pos = 'left:' + off + 'px;bottom:-4px;width:' + dw + 'px;height:' + t + 'px';
                if (room.door.wall === 'left') pos = 'top:' + off + 'px;left:-4px;height:' + dw + 'px;width:' + t + 'px';
                if (room.door.wall === 'right') pos = 'top:' + off + 'px;right:-4px;height:' + dw + 'px;width:' + t + 'px';
                html += '<span class="mz-plan-door" style="' + pos + '" title="Cửa ra vào"></span>';
            }

            room.items.forEach(function(item) {
                var ss = st.byShelf[item.shelf.id];
                var cls = f.active ? (ss.hits ? ' is-hit' : ' is-miss') : (ss.alerts ? ' is-alert' : (ss.filled ? ' is-stocked' : ''));
                html += '<button type="button" class="mz-plan-shelf' + cls + '" data-plan-shelf="' + esc(item.shelf.id) + '"' +
                    ' title="' + esc((item.shelf.code || '') + ' - ' + (item.shelf.name || '') + ' · ' + ss.filled + '/' + ss.locations +
                    ' vị trí có hàng' + (f.active ? ' · ' + ss.hits + ' khớp' : '') + ' · bấm để xem 3D kệ này') + '"' +
                    ' style="left:' + ((item.x - item.width / 2) * sc) + 'px;top:' + ((item.y - item.depth / 2) * sc) + 'px;width:' +
                    (item.width * sc) + 'px;height:' + (item.depth * sc) + 'px;transform:rotate(' + item.rotation + 'deg)">' +
                    '<span style="transform:translate(-50%,-50%) rotate(' + (-item.rotation) + 'deg)">' + esc(item.shelf.code || '') + '</span></button>';
            });

            return html + '</div>';
        }

        function renderRoomList(ctx) {
            var f = filterOf(ctx.root);
            var list = ctx.panel.querySelector('.mz-3d-list');

            if (!ctx.rooms.length) {
                list.innerHTML = '<div class="mz-3d-none">Chưa có kho/phòng nào có vị trí.</div>';
                return;
            }

            var stats = {};
            ctx.rooms.forEach(function(room) { stats[room.key] = roomStat(ctx, room, f); });

            // Lọc mà kho đang xem không còn vị trí khớp thì nhảy sang kho đầu tiên có vị trí khớp.
            var room = currentRoom(ctx);
            if (f.active && room && !stats[room.key].hits) {
                var first = ctx.rooms.find(function(r) { return stats[r.key].hits > 0; });
                if (first) ctx.room = first.key;
            }
            room = currentRoom(ctx);
            ctx.room = room.key;

            var html = ctx.rooms.map(function(r) {
                var st = stats[r.key];
                return '<button type="button" class="mz-3d-room' + (r === room ? ' is-active' : '') + (f.active && !st.hits ? ' is-miss' : '') +
                    '" data-z3d-room="' + esc(r.key) + '">' +
                    '<span class="nm"><i class="fas fa-warehouse" style="color: var(--primary-light)"></i>' + esc(r.wh.name || 'Kho/Phòng') +
                    (f.active && st.hits ? '<span class="hit">' + st.hits + '</span>' : '') + '</span>' +
                    '<span class="sub">' + (r.wh.code ? esc(r.wh.code) + ' · ' : '') + r.shelves.length + ' kệ/tủ · ' +
                    st.filled + '/' + st.locations + ' vị trí có hàng</span>' +
                    '</button>';
            }).join('');

            var width = Math.max(list.clientWidth - 24, 180);
            html += '<div class="mz-plan-box"><div class="mz-plan-head"><i class="fas fa-vector-square mr-1"></i>Mặt bằng ' + esc(room.wh.code || room.wh.name || '') + '</div>' +
                planMarkup(ctx, room, stats[room.key], f, width) +
                (room.virtual ? '<div class="mz-plan-note is-warn">Chưa khai mặt bằng ở Dữ Liệu Gốc → Định Khu: đang xếp tạm các kệ thành một hàng.</div>' : '') +
                (room.unplaced.length ? '<div class="mz-plan-note is-warn">Chưa đặt trên mặt bằng: ' +
                    room.unplaced.map(function(shelf) { return esc(shelf.code || shelf.name); }).join(', ') + '</div>' : '') +
                '</div>';
            list.innerHTML = html;
        }

        function renderMain(ctx) {
            if (ctx.mode === 'room') renderRoomStage(ctx);
            else renderStage(ctx);
        }

        function renderList(ctx) {
            var f = filterOf(ctx.root);
            var list = ctx.panel.querySelector('.mz-3d-list');
            var lastWh = null;
            var html = '';

            if (!ctx.shelves.length) {
                list.innerHTML = '<div class="mz-3d-none">Chưa có kệ/tủ nào có vị trí.</div>';
                return;
            }

            // Lọc mà kệ đang xem không còn vị trí khớp thì nhảy sang kệ đầu tiên có vị trí khớp.
            var stats = {};
            ctx.shelves.forEach(function(s) { stats[s.id] = statOf(ctx, s, f); });
            if (f.active && ctx.current != null && !stats[ctx.current].hits) {
                var first = ctx.shelves.find(function(s) { return stats[s.id].hits > 0; });
                if (first) ctx.current = first.id;
            }

            ctx.shelves.forEach(function(s) {
                var st = stats[s.id];
                if (s.warehouse.name !== lastWh) {
                    lastWh = s.warehouse.name;
                    html += '<div class="mz-3d-wh"><i class="fas fa-warehouse"></i>' + esc(lastWh) + '</div>';
                }
                var pct = st.locations ? Math.round(st.filled / st.locations * 100) : 0;
                html += '<button type="button" class="mz-3d-shelf' + (String(s.id) === String(ctx.current) ? ' is-active' : '') +
                    (f.active && !st.hits ? ' is-miss' : '') + '" data-shelf="' + esc(s.id) + '">' +
                    '<span class="nm"><i class="fas fa-layer-group" style="color: var(--primary-light)"></i>' +
                    esc(s.name || s.code || 'Kệ/Tủ') + (f.active && st.hits ? '<span class="hit">' + st.hits + '</span>' : '') + '</span>' +
                    '<span class="sub">' + (s.code ? esc(s.code) + ' · ' : '') + st.filled + '/' + st.locations + ' vị trí có hàng' +
                    (st.alerts ? ' · <span class="alert">' + st.alerts + ' cần chú ý</span>' : '') + '</span>' +
                    '<span class="mz-3d-bar"><span style="width:' + pct + '%"></span></span>' +
                    '</button>';
            });
            list.innerHTML = html;
        }

        function refresh(ctx) {
            if (ctx.mode === '3d') {
                renderList(ctx);
                renderStage(ctx);
                seek(ctx);
            } else if (ctx.mode === 'room') {
                renderRoomList(ctx);
                renderRoomStage(ctx);
                seek(ctx);
            }
        }

        function setMode(ctx, mode) {
            ctx.mode = mode === '3d' || mode === 'room' ? mode : 'cards';
            write(ctx.modeKey, ctx.mode);
            ctx.root.classList.toggle('mz-mode-3d', ctx.mode !== 'cards');
            ctx.root.classList.toggle('mz-mode-room', ctx.mode === 'room');
            ctx.root.querySelectorAll('[data-mz-view]').forEach(function(btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-mz-view') === ctx.mode);
            });
            refresh(ctx);
        }

        function init(root, opts) {
            if (!root || !window.WmsShelf3d) return;
            var $root = $(root);
            $root.off('.z3d');

            // Nút Thẻ | 3D đứng đầu nhóm công cụ; khung 3D nằm ngay dưới hàng bộ lọc.
            var tools = root.querySelector('.mz-tools');
            if (tools && !tools.querySelector('.mz-view')) {
                tools.insertAdjacentHTML('afterbegin', '<div class="mz-view" role="group" aria-label="Kiểu sơ đồ">' +
                    '<button type="button" data-mz-view="cards"><i class="fas fa-th-large mr-1"></i>Thẻ</button>' +
                    '<button type="button" data-mz-view="3d" title="Xem 3D từng kệ/tủ"><i class="fas fa-cube mr-1"></i>3D</button>' +
                    '<button type="button" data-mz-view="room" title="Xem cả kho/phòng theo mặt bằng"><i class="fas fa-vector-square mr-1"></i>Mặt bằng</button></div>');
            } else if (tools && !tools.querySelector('[data-mz-view="room"]')) {
                tools.querySelector('.mz-view').insertAdjacentHTML('beforeend',
                    '<button type="button" data-mz-view="room" title="Xem cả kho/phòng theo mặt bằng"><i class="fas fa-vector-square mr-1"></i>Mặt bằng</button>');
            }
            var panel = root.querySelector('.mz-3d');
            if (!panel) {
                // Chip lọc giờ nằm trong .mz-head (chung dòng với công cụ) - khung 3D đặt ngay dưới dòng đó
                var filters = root.querySelector('.mz-head') || root.querySelector('.mz-filters');
                var holder = document.createElement('div');
                holder.className = 'mz-3d';
                holder.innerHTML = '<aside class="mz-3d-list"></aside>' +
                    '<div class="mz-3d-main"><div class="mz-3d-title"></div>' +
                    '<div class="w3d" title="Kéo chuột để xoay · bấm thùng hàng để xem chi tiết">' +
                    WmsShelf3d.markup({
                        tools: '<div class="w3d-group" role="group" aria-label="Tô màu theo">' +
                            '<button type="button" data-z3d-color="state">Tình trạng</button>' +
                            '<button type="button" data-z3d-color="zone">Loại định khu</button></div>'
                    }) + '</div></div>';
                if (filters) filters.insertAdjacentElement('afterend', holder);
                else root.appendChild(holder);
                panel = holder;
            }

            var zoneTypes = $root.data('zone-types') || {};
            var zoneLabels = $.extend({}, opts.zoneLabels || {});
            root.querySelectorAll('.mz-zt-filter[data-zone-type]').forEach(function(btn) {
                var type = btn.getAttribute('data-zone-type');
                if (type && type !== '*') {
                    zoneLabels[type] = btn.textContent.trim();
                    if (!zoneTypes[type]) zoneTypes[type] = { bg: getComputedStyle(btn).getPropertyValue('--zt').trim() || NONE_COLOR };
                }
            });

            var ctx = {
                root: root,
                panel: panel,
                stage: panel.querySelector('.w3d'),
                nodes: opts.nodes || {},
                zoneTypes: zoneTypes,
                zoneLabels: zoneLabels,
                shelves: buildShelves(opts.zones || {}),
                color: read(COLOR_KEY, 'state') === 'zone' ? 'zone' : 'state',
                mode: 'cards',
                // Trang khác (tra cứu công khai) nhớ kiểu xem riêng, không lẫn với màn hình Tồn Kho
                modeKey: opts.modeKey || MODE_KEY,
                current: null,
                // Mã còn tồn chưa xếp vị trí (khối riêng ở chế độ Thẻ) - nhắc ở tiêu đề khung 3D
                unzoned: root.querySelectorAll('.mz-unzoned .mz-cell').length
            };

            // Mặc định mở kệ đầu tiên đang có hàng, và kho/phòng chứa kệ đó.
            var firstStocked = ctx.shelves.find(function(s) { return statOf(ctx, s, { active: false }).filled > 0; });
            ctx.current = (firstStocked || ctx.shelves[0] || {}).id;
            if (ctx.current === undefined) ctx.current = null;
            ctx.rooms = buildRooms(ctx);
            ctx.room = firstStocked ? firstStocked.warehouseKey : (ctx.rooms[0] ? ctx.rooms[0].key : null);

            panel.querySelectorAll('[data-z3d-color]').forEach(function(btn) {
                btn.classList.toggle('active', btn.getAttribute('data-z3d-color') === ctx.color);
            });

            $root.on('click.z3d', '[data-mz-view]', function() {
                setMode(ctx, this.getAttribute('data-mz-view'));
            });
            // Nhãn "N mã chưa xếp vị trí" -> về chế độ Thẻ, mở khối đó (bấm đầu khối để zoneGrid nhớ trạng thái mở)
            $root.on('click.z3d', '.mz-3d-unzoned', function() {
                setMode(ctx, 'cards');
                $root.find('.mz-unzoned.is-closed .mz-unzoned-head').trigger('click');
                var block = root.querySelector('.mz-unzoned');
                if (block) block.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            $root.on('click.z3d', '.mz-3d-shelf', function() {
                ctx.current = this.getAttribute('data-shelf');
                refresh(ctx);
            });
            $root.on('click.z3d', '[data-z3d-color]', function() {
                ctx.color = this.getAttribute('data-z3d-color') === 'zone' ? 'zone' : 'state';
                write(COLOR_KEY, ctx.color);
                panel.querySelectorAll('[data-z3d-color]').forEach(function(btn) {
                    btn.classList.toggle('active', btn.getAttribute('data-z3d-color') === ctx.color);
                });
                renderMain(ctx);
            });
            // Chế độ Mặt bằng: chọn kho/phòng; bấm kệ trên sơ đồ thu nhỏ hoặc trên 3D (ngoài thùng hàng) = xem 3D riêng kệ đó.
            $root.on('click.z3d', '[data-z3d-room]', function() {
                ctx.room = this.getAttribute('data-z3d-room');
                refresh(ctx);
            });
            $root.on('click.z3d', '[data-plan-shelf]', function() {
                ctx.current = this.getAttribute('data-plan-shelf');
                setMode(ctx, '3d');
            });
            $root.on('click.z3d', '[data-w3d-shelf]', function(event) {
                if ($(event.target).closest('[data-w3d-key]').length) return;
                ctx.current = this.getAttribute('data-w3d-shelf');
                setMode(ctx, '3d');
            });
            // Bộ lọc của sơ đồ thẻ đổi thì vẽ lại sau khi trang đã cập nhật trạng thái nút.
            $root.on('click.z3d', '.mz-filter', function() { setTimeout(function() { refresh(ctx); }, 0); });
            $root.on('input.z3d', '.mz-search input', function() {
                clearTimeout(ctx.timer);
                ctx.timer = setTimeout(function() { refresh(ctx); }, 200);
            });
            // Máy quét cầm tay / nút camera (pages.shared.cameraScan) điền mã rồi bấm Enter: lọc ngay, bôi đen mã để lần
            // quét sau ghi đè lên.
            $root.on('keydown.z3d', '.mz-search input', function(event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                $(this).trigger('input');
                this.select();
            });
            // Mở to / thu về khung 3D (bộ vẽ đặt lại khung nhìn) khi đang tìm: bay lại tới các vị trí khớp.
            if (ctx.stage._z3dFull) ctx.stage.removeEventListener('w3d:full', ctx.stage._z3dFull);
            ctx.stage._z3dFull = function() {
                if (ctx.zoomed) WmsShelf3d.focus(ctx.stage, { maxZoom: seekZoom(ctx) });
            };
            ctx.stage.addEventListener('w3d:full', ctx.stage._z3dFull);
            // Đổi cỡ cửa sổ / tab vừa hiện ra thì tính lại chiều cao khung 3D.
            if (root._z3dResize) window.removeEventListener('resize', root._z3dResize);
            root._z3dResize = function() {
                clearTimeout(ctx.resizeTimer);
                ctx.resizeTimer = setTimeout(function() { fitHeight(ctx); }, 150);
            };
            window.addEventListener('resize', root._z3dResize);
            if (window.ResizeObserver) {
                if (root._z3dObserver) root._z3dObserver.disconnect();
                root._z3dObserver = new ResizeObserver(function() {
                    window.requestAnimationFrame(function() { fitHeight(ctx); });
                });
                root._z3dObserver.observe(panel);
            }
            // Bấm thùng hàng = bấm thẻ vị trí tương ứng (mở hộp chi tiết của trang), hoặc gọi opts.open(key) nếu trang tự mở.
            $root.on('click.z3d', '[data-w3d-key]', function() {
                var key = this.getAttribute('data-w3d-key');
                if (typeof opts.open === 'function') {
                    opts.open(key);
                    return;
                }
                var card = root.querySelector('.mz-cell[data-key="' + key + '"]');
                if (card) $(card).trigger('click');
            });

            setMode(ctx, read(ctx.modeKey, opts.defaultMode || 'cards'));
        }

        window.invZone3d = { init: init };
    })();
</script>
@endonce
