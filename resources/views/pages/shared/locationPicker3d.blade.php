{{--
|--------------------------------------------------------------------------
| CHỌN ĐỊNH KHU TRÊN 3D - nút "Chọn trên 3D" cạnh ô chọn định khu
|--------------------------------------------------------------------------
| Gắn data-loc3d vào <select> định khu có data-lookup-url trỏ lookup/locations: nhãn của ô có thêm nút
| [Chọn trên 3D]. Bấm nút: hộp chọn hiện danh sách kệ/tủ (theo kho/phòng) bên trái, kệ dựng 3D bên phải
| (pages.shared.shelf3d) theo lưới Định Khu, lấy từ cùng URL kèm grid=1 (CategoryLookup::locationGrid -
| đúng điều kiện ô chọn: type / only_zone / except_zone).
|   - Ô chọn được: trống = xanh lá phẳng, đang có hàng = thùng cam (rê chuột xem số lô).
|   - Ô không thuộc điều kiện (phân loại khác, loại hàng khác...): xám mờ, không bấm được.
|   - Form có select category_id (hoá chất đang nhập): ô cùng tầng với hoá chất tương kỵ viền đỏ + "!" (chỉ cảnh báo).
| Bấm ô = chọn (tô xanh dương, hiện đường dẫn đầy đủ), [Chọn vị trí] điền vào select2 rồi đóng.
| Ô đang có giá trị thì mở thẳng kệ chứa ô đó.
--}}
@include('pages.shared.shelf3d')

@once
<style>
    .loc3d-btn {
        float: right;
        border: 1px solid var(--primary, #2563eb);
        background: #fff;
        color: var(--primary, #2563eb);
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 1px 8px;
        line-height: 1.5;
    }

    .loc3d-btn:hover { background: var(--primary-soft, #eff6ff); }

    .loc3d-overlay {
        position: fixed;
        inset: 0;
        z-index: 2050;
        background: rgba(15, 23, 42, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .loc3d-dialog {
        background: #fff;
        border-radius: 12px;
        width: min(1200px, 100%);
        max-height: calc(100vh - 32px);
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.3);
        overflow: hidden;
    }

    .loc3d-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        border-bottom: 1px solid #e2e8f0;
        background: var(--primary-soft, #eff6ff);
        font-weight: 600;
        color: var(--primary, #2563eb);
    }

    .loc3d-head button { border: none; background: none; font-size: 1.3rem; line-height: 1; color: #64748b; }

    .loc3d-body {
        display: flex;
        gap: 12px;
        padding: 12px;
        min-height: 0;
        flex: 1;
    }

    .loc3d-list {
        width: 230px;
        flex-shrink: 0;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 6px;
        font-size: 0.8rem;
    }

    .loc3d-wh { font-weight: 700; color: #475569; padding: 6px 6px 2px; font-size: 0.74rem; text-transform: uppercase; }

    .loc3d-shelf {
        display: flex;
        justify-content: space-between;
        width: 100%;
        text-align: left;
        border: none;
        background: none;
        border-radius: 6px;
        padding: 5px 8px;
        color: #334155;
    }

    .loc3d-shelf:hover { background: #f1f5f9; }
    .loc3d-shelf.is-active { background: var(--primary, #2563eb); color: #fff; }
    .loc3d-shelf b { font-weight: 600; opacity: 0.75; }

    .loc3d-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    /* Vách / nóc / mặt sau / nhãn của kệ không nhận chuột: xoay sang Trái / Phải / Sau vẫn bấm trúng ô phía sau vách */
    .loc3d-overlay .w3d-world * { pointer-events: none; }
    .loc3d-overlay .w3d-world [data-w3d-key],
    .loc3d-overlay .w3d-world [data-w3d-key] * { pointer-events: auto; }

    .loc3d-bar { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 8px; font-size: 0.76rem; }
    .loc3d-bar .lbl { font-weight: 600; color: #475569; margin-right: 2px; }
    .loc3d-bar button {
        border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 999px;
        padding: 1px 10px; font-size: 0.74rem; font-weight: 600;
    }
    .loc3d-bar button.is-active { background: var(--primary, #2563eb); border-color: var(--primary, #2563eb); color: #fff; }
    .loc3d-bar .sep { flex: 1; }
    .loc3d-main .w3d { height: min(62vh, 560px); }

    .loc3d-legend span { margin-right: 10px; white-space: nowrap; }
    .loc3d-legend i { display: inline-block; width: 11px; height: 11px; border-radius: 3px; margin-right: 4px; vertical-align: -1px; }

    .loc3d-foot {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-top: 1px solid #e2e8f0;
        flex-wrap: wrap;
    }

    .loc3d-picked { flex: 1; min-width: 200px; font-size: 0.82rem; color: #334155; }
    .loc3d-picked .warn { color: #DC2626; display: block; font-size: 0.76rem; }

    @media (max-width: 767px) {
        .loc3d-body { flex-direction: column; }
        .loc3d-list { width: 100%; max-height: 130px; }
        .loc3d-main .w3d { height: 50vh; min-height: 300px; }
    }
</style>

<script>
    // jQuery nạp ở cuối layout (sau phần modal của trang) - chạy khi trang đã nạp xong script
    document.addEventListener('DOMContentLoaded', function() {
        var COLORS = { free: '#86D19A', used: '#F59E0B', off: '#E2E8F0', picked: '#2563EB' };
        var state = null;

        function esc(v) {
            return String(v == null ? '' : v).replace(/[&<>"']/g, function(ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function byPos(a, b) {
            var pa = Number(a.position) || 0, pb = Number(b.position) || 0;
            if (pa && pb && pa !== pb) return pa - pb;
            if (!pa !== !pb) return pa ? -1 : 1;
            return String(a.code || a.name || '').localeCompare(String(b.code || b.name || ''), 'vi', { numeric: true });
        }

        // Ô AJAX trỏ lookup/locations: lưới lấy cùng điều kiện. Ô render sẵn option (name chứa location_id):
        // lưới lấy theo DEFAULT_URL, chỉ ô có trong option mới chọn được.
        var DEFAULT_URL = @json(route('pages.category.lookup.locations', ['type' => 'all']));
        var SELECTOR = 'select[data-loc3d], select[data-lookup-url*="/lookup/locations"], select[name*="location_id"]';

        function isAjax(select) {
            return String($(select).data('lookup-url') || '').indexOf('/lookup/locations') !== -1;
        }

        function attach(select) {
            if (select._loc3d || select.multiple || select.hasAttribute('data-no-loc3d')) return;
            select._loc3d = true;
            var label = $(select).closest('.form-group').find('label').first();
            var btn = $('<button type="button" class="loc3d-btn" title="Chọn vị trí trên mô hình 3D của kệ/tủ"><i class="fas fa-cube mr-1"></i>' +
                (label.length ? 'Chọn trên 3D' : '3D') + '</button>');
            btn.on('click', function() { if (!select.disabled) open(select); });
            if (label.length) {
                label.append(btn);
            } else {
                // Ô trong bảng / dòng thêm động: nút nhỏ ngay dưới ô chọn
                btn.css({ float: 'none', marginTop: '3px' });
                var anchor = $(select).next('.select2-container');
                (anchor.length ? anchor : $(select)).after(btn);
            }
        }

        var scanTimer = null;
        function scan() {
            document.querySelectorAll(SELECTOR).forEach(attach);
        }
        function scanSoon() {
            clearTimeout(scanTimer);
            scanTimer = setTimeout(scan, 150);
        }

        function gridUrl(select) {
            var url = isAjax(select) ? String($(select).data('lookup-url')) : DEFAULT_URL;
            var params = { grid: 1 };
            var cat = $(select).closest('form').find('[name="category_id"]').val();
            if (cat) params.category_id = cat;
            return url + (url.indexOf('?') >= 0 ? '&' : '?') + $.param(params);
        }

        function open(select) {
            close();
            var $ov = $('<div class="loc3d-overlay" role="dialog" aria-modal="true">' +
                '<div class="loc3d-dialog">' +
                '<div class="loc3d-head"><span><i class="fas fa-cube mr-2"></i>Chọn định khu trên 3D</span><button type="button" data-loc3d-close aria-label="Đóng">&times;</button></div>' +
                '<div class="loc3d-body"><aside class="loc3d-list"><div class="p-2 text-muted"><i class="fas fa-circle-notch fa-spin mr-1"></i>Đang tải...</div></aside>' +
                '<div class="loc3d-main"><div class="loc3d-bar"></div><div class="w3d" title="Kéo chuột để xoay · bấm vào ô để chọn">' +
                WmsShelf3d.markup({
                    tools: '<div class="w3d-group" role="group" aria-label="Mặt nhìn">' +
                        '<button type="button" data-w3d-view="left" title="Nhìn từ bên trái">Trái</button>' +
                        '<button type="button" data-w3d-view="right" title="Nhìn từ bên phải">Phải</button>' +
                        '<button type="button" data-w3d-view="back" title="Nhìn từ phía sau">Sau</button></div>',
                    legend: '<span class="loc3d-legend">' +
                        '<span><i style="background:' + COLORS.free + '"></i>Trống</span>' +
                        '<span><i style="background:' + COLORS.used + '"></i>Đang có hàng</span>' +
                        '<span><i style="background:' + COLORS.picked + '"></i>Đang chọn</span>' +
                        '<span><i style="background:#fff;border:2px solid #DC2626"></i>Tương kỵ cùng tầng</span>' +
                        '<span><i style="background:' + COLORS.off + '"></i>Không chọn được</span></span>'
                }) + '</div></div></div>' +
                '<div class="loc3d-foot"><div class="loc3d-picked text-muted">Bấm vào một ô trên kệ để chọn vị trí.</div>' +
                '<button type="button" class="btn btn-secondary btn-sm" data-loc3d-close>Huỷ</button>' +
                '<button type="button" class="btn btn-primary btn-sm" data-loc3d-ok disabled><i class="fas fa-check mr-1"></i>Chọn vị trí</button></div>' +
                '</div></div>');
            // Đặt trong modal Bootstrap đang mở (nếu có) để modal không kéo focus về và Esc không đóng modal ngoài
            var host = $(select).closest('.modal');
            (host.length ? host : $('body')).append($ov);
            $ov.attr('tabindex', '-1');
            $ov.on('keydown', function(e) {
                if (e.key !== 'Escape') return;
                e.stopPropagation();
                if (!state.stage.classList.contains('is-full')) close();
            });

            state = { select: select, $ov: $ov, stage: $ov.find('.w3d')[0], picked: Number($(select).val()) || null, shelf: null, data: null, tier: 0, boxes: false, only: null };
            if (!isAjax(select)) {
                state.only = {};
                $(select).find('option').each(function() { if (this.value) state.only[this.value] = true; });
            }

            $ov.on('click', '[data-loc3d-close]', close);
            $ov.on('mousedown', function(e) { if (e.target === this) close(); });
            $ov.on('click', '.loc3d-shelf', function() {
                state.shelf = Number(this.getAttribute('data-shelf'));
                state.tier = 0;
                renderList();
                renderShelf();
            });
            // Chỉ hiện một tầng (tầng trên không che tầng dưới) / bật thùng hàng nổi
            $ov.on('click', '[data-loc3d-tier]', function() {
                state.tier = Number(this.getAttribute('data-loc3d-tier')) || 0;
                renderShelf();
            });
            $ov.on('click', '[data-loc3d-boxes]', function() {
                state.boxes = !state.boxes;
                renderShelf();
            });
            $ov.on('click', '[data-w3d-key]', function() {
                state.picked = Number(String(this.getAttribute('data-w3d-key')).slice(1));
                renderShelf();
                renderPicked();
            });
            $ov.on('click', '[data-loc3d-ok]', confirm);
            $ov.trigger('focus');

            $.getJSON(gridUrl(select)).done(function(res) {
                if (!state || state.$ov !== $ov) return;
                prepare(res || {});
            }).fail(function() {
                $ov.find('.loc3d-list').html('<div class="p-2 text-danger">Không tải được dữ liệu định khu.</div>');
            });
        }

        function close() {
            if (!state) return;
            state.$ov.remove();
            state = null;
        }

        function prepare(res) {
            var shelves = {}, cols = {}, locs = {};
            (res.shelves || []).forEach(function(s) { shelves[s.id] = $.extend({ columns: {} }, s); });
            (res.columns || []).forEach(function(c) { if (shelves[c.shelf_id]) cols[c.id] = shelves[c.shelf_id].columns[c.id] = $.extend({ tiers: {} }, c); });
            (res.tiers || []).forEach(function(t) { if (cols[t.column_id]) cols[t.column_id].tiers[t.id] = $.extend({ locs: [] }, t); });
            (res.locations || []).forEach(function(l) {
                if (state.only && !state.only[l.id]) l.ok = false;
                locs[l.id] = l;
                var c = cols[l.column_id];
                var t = c && c.tiers[l.tier_id];
                if (t) t.locs.push(l);
                if (shelves[l.shelf_id]) {
                    shelves[l.shelf_id].ok = (shelves[l.shelf_id].ok || 0) + (l.ok ? 1 : 0);
                }
            });
            var wh = {};
            (res.warehouses || []).forEach(function(w) { wh[w.id] = w; });

            state.data = { shelves: shelves, locs: locs, wh: wh };
            var pickedLoc = state.picked && locs[state.picked];
            var list = Object.keys(shelves).map(function(id) { return shelves[id]; }).filter(function(s) { return s.ok; });
            state.shelf = pickedLoc ? pickedLoc.shelf_id : (list[0] ? list[0].id : null);
            if (pickedLoc && !pickedLoc.ok) state.picked = null;
            state.tier = 0;

            renderList();
            renderShelf();
            renderPicked();
        }

        function renderList() {
            var d = state.data;
            var groups = {};
            Object.keys(d.shelves).forEach(function(id) {
                var s = d.shelves[id];
                if (!s.ok) return;
                var key = s.warehouse_id || 0;
                (groups[key] = groups[key] || []).push(s);
            });
            var keys = Object.keys(groups).sort(function(a, b) {
                return String((d.wh[a] || {}).name || '').localeCompare(String((d.wh[b] || {}).name || ''), 'vi');
            });
            if (!keys.length) {
                state.$ov.find('.loc3d-list').html('<div class="p-2 text-muted">Chưa có kệ/tủ nào khai lưới vị trí phù hợp (Dữ Liệu Gốc → Định Khu).</div>');
                return;
            }
            var html = keys.map(function(k) {
                return '<div class="loc3d-wh">' + esc((d.wh[k] || {}).name || 'Chưa gán kho/phòng') + '</div>' +
                    groups[k].sort(byPos).map(function(s) {
                        return '<button type="button" class="loc3d-shelf' + (Number(s.id) === Number(state.shelf) ? ' is-active' : '') +
                            '" data-shelf="' + s.id + '"><span>' + esc(s.name || s.code) + '</span><b>' + s.ok + '</b></button>';
                    }).join('');
            }).join('');
            state.$ov.find('.loc3d-list').html(html);
        }

        function cellOf(l) {
            var title = l.code + (l.ok ? (l.lots ? ' · Đang có ' + l.lots + ' lô' : ' · Đang trống') : ' · Không chọn được ở ô này');
            if (l.warning) title += '\n⚠ ' + l.warning;
            var picked = l.id === state.picked;
            return {
                key: l.ok ? 'L' + l.id : null,
                title: title,
                label: String(l.code || '').split('/').pop(),
                color: picked ? COLORS.picked : (!l.ok ? COLORS.off : (l.lots ? COLORS.used : COLORS.free)),
                // Mặc định vẽ phẳng để thùng hàng phía trước không che ô phía sau
                box: state.boxes && l.ok && l.lots > 0,
                dim: !l.ok,
                hit: picked,
                flag: !!l.warning
            };
        }

        function renderShelf() {
            var s = state.data && state.data.shelves[state.shelf];
            if (!s) {
                WmsShelf3d.render(state.stage, { columns: [], empty: 'Chọn kệ/tủ ở danh sách bên trái.' });
                return;
            }
            var topDown = s.tier_order === 'top_down';
            var levels = 0;
            var columns = Object.keys(s.columns).map(function(k) { return s.columns[k]; }).sort(byPos).map(function(c) {
                var rank = 0;
                var tiers = Object.keys(c.tiers).map(function(k) { return c.tiers[k]; }).sort(byPos).map(function(t) {
                    rank = Number(t.position) > rank ? Number(t.position) : rank + 1;
                    var next = t.locs.reduce(function(m, l) { return Math.max(m, Number(l.position) || 0); }, 0);
                    var cells = t.locs.slice().sort(function(a, b) { return String(a.code).localeCompare(String(b.code), 'vi', { numeric: true }); })
                        .map(function(l) {
                            var cell = cellOf(l);
                            cell.p = Number(l.position) > 0 ? Number(l.position) : ++next;
                            cell.d = Math.max(1, Number(l.depth) || 1);
                            return cell;
                        });
                    return {
                        rank: rank,
                        label: t.name || t.code || '',
                        first: rank === 1,
                        title: (c.name || '') + ' · ' + (t.name || ''),
                        max: cells.reduce(function(m, x) { return Math.max(m, x.p); }, 1),
                        depth: cells.reduce(function(m, x) { return Math.max(m, x.d); }, 1),
                        cells: cells
                    };
                });
                levels = Math.max(levels, rank);
                return { label: c.name || c.code || '', code: c.code || '', tiers: tiers };
            });
            columns.forEach(function(c) {
                c.tiers.forEach(function(t) { t.level = topDown ? levels - t.rank : t.rank - 1; });
            });
            var allLevels = levels;
            // Chọn một tầng: chỉ dựng đúng tầng đó (đặt sát sàn), bỏ hẳn các tầng khác để xoay nhìn mọi phía
            if (state.tier) {
                columns = columns.map(function(c) {
                    var only = c.tiers.filter(function(t) { return t.rank === state.tier; });
                    only.forEach(function(t) { t.level = 0; t.first = true; });
                    return $.extend({}, c, { tiers: only });
                }).filter(function(c) { return c.tiers.length; });
                levels = 1;
            }
            var chips = '<span class="lbl"><i class="fas fa-layer-group mr-1"></i>Tầng</span>' +
                '<button type="button" data-loc3d-tier="0" class="' + (!state.tier ? 'is-active' : '') + '">Tất cả</button>';
            for (var i = 1; i <= allLevels; i++) {
                chips += '<button type="button" data-loc3d-tier="' + i + '" class="' + (state.tier === i ? 'is-active' : '') + '">' + (i < 10 ? '0' : '') + i + '</button>';
            }
            chips += '<span class="sep"></span><button type="button" data-loc3d-boxes class="' + (state.boxes ? 'is-active' : '') +
                '" title="Vẽ ô đang có hàng thành thùng nổi (có thể che ô phía sau)"><i class="fas fa-box mr-1"></i>Hiện thùng hàng</button>';
            state.$ov.find('.loc3d-bar').html(chips);
            WmsShelf3d.render(state.stage, { unit: 22, gap: 4, pad: 8, tierHeight: 54, boxHeight: 17, levels: levels, columns: columns });
        }

        function renderPicked() {
            var l = state.picked && state.data && state.data.locs[state.picked];
            var $p = state.$ov.find('.loc3d-picked');
            state.$ov.find('[data-loc3d-ok]').prop('disabled', !l);
            if (!l) {
                $p.addClass('text-muted').text('Bấm vào một ô trên kệ để chọn vị trí.');
                return;
            }
            $p.removeClass('text-muted').html('<i class="fas fa-map-marker-alt text-primary mr-1"></i><b>' + esc(l.text) + '</b>' +
                (l.lots ? ' <span class="text-muted">· đang có ' + l.lots + ' lô (để chung vị trí được)</span>' : '') +
                (l.warning ? '<span class="warn"><i class="fas fa-exclamation-triangle mr-1"></i>' + esc(l.warning) + '</span>' : ''));
        }

        function confirm() {
            var l = state.picked && state.data.locs[state.picked];
            if (!l) return;
            var $s = $(state.select);
            if (!$s.find('option[value="' + l.id + '"]').length) {
                $s.append($('<option>').val(l.id).text(l.text).attr('data-zone', l.zone_type || ''));
            }
            $s.val(String(l.id)).trigger('change');
            close();
        }

        $(scan);
        $(document).on('shown.bs.modal', scan);
        // Dòng thêm động (bảng nhiều dòng Danh Mục, Định khu nhanh...)
        if (window.MutationObserver) new MutationObserver(scanSoon).observe(document.body, { childList: true, subtree: true });
    });
</script>
@endonce
