{{--
|--------------------------------------------------------------------------
| TỒN THEO ĐỊNH KHU - SƠ ĐỒ THẺ (card - grid) DỰNG Ở TRÌNH DUYỆT
|--------------------------------------------------------------------------
| Cùng giao diện với tab "Tồn kho theo vị trí" của Vật Tư (MaterialInventory/zoneMap)
| nhưng dựng bằng JS vì Hoá Chất nạp số liệu qua JSON. Gọi:
|
|   window.invZoneGrid('#idKhung', { zones: {...5 cấp...}, rows: [...] })
|
| zones: warehouses/shelves/columns/tiers/locations như zoneOptions() của controller
|        (vị trí mang sẵn badge {bg, text} = App\Support\ZoneType::badge()).
| rows : mỗi mã xuất nhập {code, category_id, name, sub, remaining, unit, expired,
|        state, state_label, location_id, location_code, location_zone_type,
|        warehouse_id, shelf_id, column_id, tier_id, *_name}. Chỉ mã CÒN TỒN được đếm.
|
| Tham số: $zgId (id khung), $zgItem (tên loại hàng, chữ thường), $zgCode (nhãn cột mã).
--}}
@include('pages.inventory.shared.zoneMapStyles')

@php
    $zgItem = $zgItem ?? 'hoá chất';
    $zgCode = $zgCode ?? 'Mã xuất nhập';
    $zgZoneTypes = collect(\App\Support\ZoneType::TYPES)
        ->map(function ($label, $type) {
            $color = \App\Support\ZoneType::color($type);

            return ['bg' => $color, 'text' => \App\Support\ZoneType::textOn($color)];
        })
        ->all();
@endphp

<div class="mz-root" id="{{ $zgId }}" data-item="{{ $zgItem }}" data-code-label="{{ $zgCode }}"
    data-zone-types="{{ json_encode($zgZoneTypes) }}">
    <div class="mz-head">
        <div class="mz-stats"></div>

        <div class="mz-tools">
            <div class="mz-search">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm mz-search-input"
                    placeholder="Tìm vị trí, kệ/tủ, mã hoặc tên {{ $zgItem }}...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mz-toggle-all">
                <i class="fas fa-compress mr-1"></i> Thu gọn tất cả
            </button>
        </div>
    </div>

    <div class="mz-filters">
        <button type="button" class="mz-filter is-active" data-state="">Tất cả vị trí</button>
        <button type="button" class="mz-filter" data-state="stocked"><span class="dot ok"></span>Đang có hàng</button>
        <button type="button" class="mz-filter" data-state="alert"><span class="dot warn"></span>Cần chú ý</button>
        <button type="button" class="mz-filter" data-state="empty"><span class="dot empty"></span>Còn trống</button>

        <span class="mz-filter-sep"></span>
        <span class="mz-filter-label">Loại định khu</span>
        <button type="button" class="mz-filter mz-zt-filter is-active" data-zone-type="*">Tất cả</button>
        @foreach (\App\Support\ZoneType::TYPES as $ztKey => $ztLabel)
            <button type="button" class="mz-filter mz-zt-filter" data-zone-type="{{ $ztKey }}"
                style="--zt: {{ \App\Support\ZoneType::color($ztKey) }}"><span class="dot zt"></span>{{ $ztLabel }}</button>
        @endforeach
        <button type="button" class="mz-filter mz-zt-filter" data-zone-type=""><span class="dot empty"></span>Chưa phân
            loại</button>
    </div>

    <div class="mz-body">
        <div class="mz-empty">
            <i class="fas fa-circle-notch fa-spin"></i>
            Đang tải sơ đồ định khu...
        </div>
    </div>

    <div class="mz-empty mz-noresult">
        <i class="fas fa-search"></i>
        Không có vị trí nào khớp với điều kiện đang lọc.
    </div>
</div>

{{-- Chi tiết một ô vị trí --}}
<div class="modal fade md-modal" id="{{ $zgId }}Modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-map-pin"></i> <span class="mz-d-name">Định khu</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Đường dẫn định khu</small>
                        <div class="font-weight-bold mz-d-path">—</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Mã định khu</small>
                        <div class="font-weight-bold mz-d-code">—</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">{{ $zgCode }}</small>
                        <div class="font-weight-bold mz-d-lots">0</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">{{ \Illuminate\Support\Str::ucfirst($zgItem) }}</small>
                        <div class="font-weight-bold mz-d-items">0</div>
                    </div>
                </div>

                <table class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px">STT</th>
                            <th style="width:160px">{{ $zgCode }}</th>
                            <th>{{ \Illuminate\Support\Str::ucfirst($zgItem) }}</th>
                            <th class="text-right" style="width:120px">Tồn</th>
                            <th class="text-center" style="width:105px">Hạn Áp Dụng</th>
                            <th class="text-center" style="width:110px">Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        (function() {
            var EPSILON = 0.000001;
            var ALERT_STATES = ['low', 'near', 'expired', 'over'];

            function esc(value) {
                return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function(c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            /** 1234.5000 -> 1,234.5 */
            function num(value) {
                var n = Number(value) || 0;
                var parts = Math.abs(n).toFixed(4).split('.');
                var whole = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                var dec = parts[1].replace(/0+$/, '');

                return (n < 0 && (whole !== '0' || dec) ? '-' : '') + whole + (dec ? '.' + dec : '');
            }

            /** 'Y-m-d' -> d/m/Y */
            function date(value) {
                if (!value) return '—';

                var p = String(value).substr(0, 10).split('-');

                return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : String(value);
            }

            function byText(key) {
                return function(a, b) {
                    return String(a[key] || '').localeCompare(String(b[key] || ''), 'vi', { numeric: true });
                };
            }

            function values(obj) {
                return Object.keys(obj).map(function(k) { return obj[k]; });
            }

            /** Gom mã của một ô: đang có gì, cảnh báo gì, tô màu theo tình trạng như zoneNode() của Vật Tư */
            function nodeOf(loc, rows, zoneTypes) {
                var inStock = rows.filter(function(r) { return Number(r.remaining) > EPSILON; })
                    .sort(function(a, b) { return Number(b.remaining) - Number(a.remaining); });
                var cats = {};
                var alerts = 0;
                var danger = false;

                inStock.forEach(function(r) {
                    cats[r.category_id ? 'c' + r.category_id : 'n' + r.name] = true;
                    if (ALERT_STATES.indexOf(r.state) >= 0) alerts++;
                    if (r.state === 'expired' || r.state === 'over') danger = true;
                });

                // Xem trước 3 mặt hàng nhiều nhất, cộng dồn các mã cùng hàng
                var preview = {};
                inStock.forEach(function(r) {
                    var key = r.category_id ? 'c' + r.category_id : 'n' + r.name;
                    preview[key] = preview[key] || { name: r.name || '—', remaining: 0, unit: r.unit || '' };
                    preview[key].remaining += Number(r.remaining) || 0;
                });

                var zoneType = loc.zone_type && zoneTypes[loc.zone_type] ? loc.zone_type : '';
                var badge = loc.badge || (zoneType ? zoneTypes[zoneType] : null) || { bg: '#FFFFFF', text: '' };

                return {
                    key: 'L' + loc.id,
                    code: loc.code || '—',
                    state: danger ? 'danger' : (alerts > 0 ? 'warn' : (inStock.length ? 'ok' : 'empty')),
                    zoneType: zoneType,
                    badge: badge,
                    lots: inStock.length,
                    alerts: alerts,
                    cats: Object.keys(cats),
                    preview: values(preview).sort(function(a, b) { return b.remaining - a.remaining; }).slice(0, 3),
                    items: inStock
                };
            }

            function blank() {
                return { locations: 0, filled: 0, lots: 0, alerts: 0, cats: {} };
            }

            function add(acc, node) {
                acc.locations += node.stat ? node.stat.locations : 1;
                acc.filled += node.stat ? node.stat.filled : (node.lots ? 1 : 0);
                acc.lots += node.stat ? node.stat.lots : node.lots;
                acc.alerts += node.stat ? node.stat.alerts : node.alerts;
                (node.stat ? Object.keys(node.stat.cats) : node.cats).forEach(function(c) { acc.cats[c] = true; });

                return acc;
            }

            function itemCount(stat) {
                return Object.keys(stat.cats).length;
            }

            window.invZoneGrid = function(selector, opts) {
                var $root = $(selector);
                var $modal = $(selector + 'Modal');
                var zones = opts.zones || {};
                var rows = opts.rows || [];
                var item = $root.data('item') || 'hoá chất';
                var zoneTypes = $root.data('zone-types') || {};

                var find = function(list) {
                    var map = {};
                    (list || []).forEach(function(x) { map[x.id] = x; });

                    return map;
                };
                var warehouses = find(zones.warehouses);
                var shelves = find(zones.shelves);
                var columns = find(zones.columns);
                var tiers = find(zones.tiers);
                var locations = find(zones.locations);

                // Mã còn hàng theo vị trí; vị trí đã ngừng hoạt động mà còn hàng thì vá lại từ dòng tồn
                var byLocation = {};
                rows.forEach(function(r) {
                    if (!r.location_id) return;

                    (byLocation[r.location_id] = byLocation[r.location_id] || []).push(r);

                    if (!locations[r.location_id]) {
                        locations[r.location_id] = {
                            id: r.location_id, code: r.location_code, zone_type: r.location_zone_type,
                            warehouse_id: r.warehouse_id, shelf_id: r.shelf_id, column_id: r.column_id, tier_id: r.tier_id
                        };
                    }
                    if (r.warehouse_id && !warehouses[r.warehouse_id]) warehouses[r.warehouse_id] = { name: r.warehouse_name };
                    if (r.shelf_id && !shelves[r.shelf_id]) shelves[r.shelf_id] = { name: r.shelf_name };
                    if (r.column_id && !columns[r.column_id]) columns[r.column_id] = { name: r.column_name };
                    if (r.tier_id && !tiers[r.tier_id]) tiers[r.tier_id] = { name: r.tier_name };
                });

                // Cây Kho/Phòng -> Kệ/Tủ -> Cột -> Tầng -> Vị trí (khoá 0 = chưa gán cấp đó)
                var tree = {};
                var index = {};

                values(locations).sort(byText('code')).forEach(function(loc) {
                    var w = warehouses[loc.warehouse_id] || {};
                    var s = shelves[loc.shelf_id] || {};
                    var c = columns[loc.column_id] || {};
                    var t = tiers[loc.tier_id] || {};

                    var wn = tree[loc.warehouse_id || 0] = tree[loc.warehouse_id || 0] ||
                        { code: w.code, name: w.name || 'Chưa gán kho/phòng', children: {} };
                    var sn = wn.children[loc.shelf_id || 0] = wn.children[loc.shelf_id || 0] ||
                        { code: s.code, name: s.name || 'Chưa gán kệ/tủ', children: {} };
                    var cn = sn.children[loc.column_id || 0] = sn.children[loc.column_id || 0] ||
                        { code: c.code, name: c.name || 'Chưa gán cột', children: {} };
                    var tn = cn.children[loc.tier_id || 0] = cn.children[loc.tier_id || 0] ||
                        { code: t.code, name: t.name || 'Chưa gán tầng', locations: [] };

                    var node = nodeOf(loc, byLocation[loc.id] || [], zoneTypes);
                    node.path = [wn.name, sn.name, cn.name, tn.name].join(' / ');
                    node.search = [node.code, sn.name, cn.name, tn.name].concat(node.items.map(function(r) {
                        return r.name + ' ' + r.code;
                    })).join(' ').toLowerCase();

                    index[node.key] = node;
                    tn.locations.push(node);
                });

                // Cộng dồn ngược từ vị trí lên tầng -> cột -> kệ/tủ -> kho
                var totals = blank();
                totals.shelves = 0;
                totals.columns = 0;
                totals.tiers = 0;

                var outW = values(tree).sort(byText('name')).map(function(w) {
                    var wAcc = blank();
                    w.list = values(w.children).sort(byText('name')).map(function(s) {
                        var sAcc = blank();
                        s.list = values(s.children).sort(byText('name')).map(function(c) {
                            var cAcc = blank();
                            c.list = values(c.children).sort(byText('name')).map(function(t) {
                                var tAcc = blank();
                                t.locations.forEach(function(n) { add(tAcc, n); });
                                t.stat = tAcc;
                                add(cAcc, t);

                                return t;
                            });
                            c.stat = cAcc;
                            add(sAcc, c);
                            totals.tiers += c.list.length;

                            return c;
                        });
                        s.stat = sAcc;
                        add(wAcc, s);
                        totals.columns += s.list.length;

                        return s;
                    });
                    w.stat = wAcc;
                    add(totals, w);
                    totals.shelves += w.list.length;

                    return w;
                });

                var unzoned = rows.filter(function(r) { return !r.location_id && Number(r.remaining) > EPSILON; })
                    .sort(function(a, b) { return Number(b.remaining) - Number(a.remaining); });

                /* ---------- Vẽ ---------- */
                function stat(value, label, cls) {
                    return '<div class="mz-stat ' + (cls || '') + '"><b>' + value + '</b><span>' + label + '</span></div>';
                }

                var allCats = {};
                rows.forEach(function(r) {
                    if (Number(r.remaining) > EPSILON) allCats[r.category_id ? 'c' + r.category_id : 'n' + r.name] = true;
                });

                $root.find('.mz-stats').html(
                    stat(outW.length, 'Kho / Phòng') + stat(totals.shelves, 'Kệ / Tủ') + stat(totals.columns, 'Cột') +
                    stat(totals.tiers, 'Tầng') + stat(totals.filled + '/' + totals.locations, 'Vị trí có hàng') +
                    stat(totals.lots + unzoned.length, esc($root.data('code-label'))) +
                    stat(Object.keys(allCats).length, esc(item)) +
                    (totals.alerts > 0 ? stat(totals.alerts, 'Cần chú ý', 'is-alert') : '')
                );

                function cell(n) {
                    var zoned = n.badge.bg !== '#FFFFFF';
                    var more = n.cats.length - n.preview.length;
                    var html = '<button type="button" class="mz-cell is-' + n.state + (zoned ? ' is-zoned' : '') + '"' +
                        ' data-key="' + n.key + '" data-state="' + n.state + '" data-zone-type="' + esc(n.zoneType) + '"' +
                        ' data-search="' + esc(n.search) + '"' +
                        ' style="--chip: ' + esc(n.badge.bg) + '; --chip-text: ' + esc(n.badge.text) + '"' +
                        (n.state === 'empty' ? ' disabled' : '') + '>' +
                        '<div class="mz-cell-top"><span class="mz-cell-code">' + esc(n.code) + '</span>' +
                        '<span class="mz-cell-lots">' + n.lots + '</span></div><div class="mz-cell-items">';

                    if (!n.preview.length) html += '<span class="mz-cell-empty-note">Đang trống</span>';
                    n.preview.forEach(function(p) {
                        html += '<div><span class="nm" title="' + esc(p.name) + '">· ' + esc(p.name) + '</span>' +
                            '<span class="qty">' + num(p.remaining) + ' <span class="u">' + esc(p.unit) + '</span></span></div>';
                    });
                    if (more > 0) html += '<div><span class="nm">· +' + more + ' ' + esc(item) + ' khác</span></div>';

                    return html + '</div><div class="mz-cell-foot">' + n.cats.length + ' ' + esc(item) +
                        (n.alerts > 0 ? ' · <span class="alert">' + n.alerts + ' cần chú ý</span>' : '') + '</div></button>';
                }

                function codeTag(code) {
                    return code ? ' <span class="tag">' + esc(code) + '</span>' : '';
                }

                var html = '';

                // Mã còn tồn nhưng chưa xếp vị trí - để trên cùng cho dễ thấy mà bổ sung định khu
                if (unzoned.length) {
                    html += '<div class="mz-unzoned"><div class="mz-unzoned-head"><i class="fas fa-exclamation-triangle"></i>' +
                        ' Chưa xếp vị trí <span class="mz-pill is-alert">' + unzoned.length + ' ' +
                        esc(String($root.data('code-label')).toLowerCase()) + '</span></div><div class="mz-grid">';
                    unzoned.forEach(function(r) {
                        html += '<div class="mz-cell is-warn"><div class="mz-cell-top"><span class="mz-cell-code">' +
                            esc(r.code) + '</span><span class="mz-cell-lots">' + num(r.remaining) + ' ' + esc(r.unit) +
                            '</span></div><div class="mz-cell-name">' + esc(r.name || '—') + '</div>' +
                            '<div class="mz-cell-foot">Hạn áp dụng ' + date(r.expired) + '</div></div>';
                    });
                    html += '</div></div>';
                }

                outW.forEach(function(w) {
                    html += '<section class="mz-wh"><div class="mz-wh-head"><i class="fas fa-chevron-down mz-caret"></i>' +
                        '<span class="ic"><i class="fas fa-warehouse"></i></span><div>' +
                        '<div class="mz-wh-title">' + esc(w.name) + '</div><div class="mz-wh-sub">' +
                        (w.code ? esc(w.code) + ' · ' : '') + w.list.length + ' kệ/tủ · ' + w.stat.filled + '/' +
                        w.stat.locations + ' vị trí có hàng</div></div><div class="mz-wh-meta">' +
                        '<span class="mz-pill">' + w.stat.lots + ' mã</span>' +
                        '<span class="mz-pill">' + itemCount(w.stat) + ' ' + esc(item) + '</span>' +
                        (w.stat.alerts > 0 ? '<span class="mz-pill is-alert">' + w.stat.alerts + ' cần chú ý</span>' : '') +
                        '</div></div><div class="mz-wh-body">';

                    w.list.forEach(function(s) {
                        html += '<div class="mz-shelf"><div class="mz-shelf-head"><i class="fas fa-layer-group"></i> ' +
                            esc(s.name) + codeTag(s.code) + '<span class="tag">' + s.list.length + ' cột</span>' +
                            '<span class="tag">' + s.stat.lots + ' mã</span></div>';

                        s.list.forEach(function(c) {
                            html += '<div class="mz-column"><div class="mz-column-head">' +
                                '<i class="fas fa-grip-lines-vertical" style="color: var(--primary-light)"></i> ' +
                                esc(c.name) + (c.code ? ' <span class="md-tag">' + esc(c.code) + '</span>' : '') +
                                '<span class="sub">' + c.list.length + ' tầng · ' + c.stat.filled + '/' + c.stat.locations +
                                ' vị trí có hàng · ' + c.stat.lots + ' mã</span></div>';

                            c.list.forEach(function(t) {
                                html += '<div class="mz-tier"><div class="mz-tier-head"><i class="fas fa-bars"></i> ' +
                                    esc(t.name) + codeTag(t.code) + '<span class="sub">' + t.stat.filled + '/' +
                                    t.stat.locations + ' vị trí có hàng · ' + t.stat.lots + ' mã</span></div>' +
                                    '<div class="mz-grid">' + t.locations.map(cell).join('') + '</div></div>';
                            });

                            html += '</div>';
                        });

                        html += '</div>';
                    });

                    html += '</div></section>';
                });

                if (!outW.length && !unzoned.length) {
                    html = '<div class="mz-empty"><i class="fas fa-map-marked-alt"></i>Chưa khai báo định khu cho phòng ban này.' +
                        ' Vào <b>Dữ Liệu Gốc → Định Khu</b> để tạo Kho-Phòng / Kệ-Tủ / Cột / Tầng / Vị trí.</div>';
                }

                $root.find('.mz-body').html(html);

                /* ---------- Lọc: tình trạng + loại định khu + từ khoá, ẩn luôn nhánh không còn ô ---------- */
                var state = '';
                var zoneType = '*';

                function shown($els) {
                    return $els.filter(function() { return this.style.display !== 'none'; }).length;
                }

                function apply() {
                    var q = ($root.find('.mz-search-input').val() || '').toString().trim().toLowerCase();

                    $root.find('.mz-cell[data-state]').each(function() {
                        var st = $(this).attr('data-state');
                        var okState = state === '' ||
                            (state === 'empty' && st === 'empty') ||
                            (state === 'stocked' && st !== 'empty') ||
                            (state === 'alert' && (st === 'warn' || st === 'danger'));
                        var okText = q === '' || ($(this).attr('data-search') || '').indexOf(q) >= 0;
                        var okZone = zoneType === '*' || ($(this).attr('data-zone-type') || '') === zoneType;

                        this.style.display = okState && okText && okZone ? '' : 'none';
                    });

                    ['.mz-tier|.mz-cell[data-state]', '.mz-column|.mz-tier', '.mz-shelf|.mz-column', '.mz-wh|.mz-shelf']
                    .forEach(function(pair) {
                        var p = pair.split('|');
                        $root.find(p[0]).each(function() {
                            this.style.display = shown($(this).find(p[1])) ? '' : 'none';
                        });
                    });

                    var total = $root.find('.mz-wh').length;
                    $root.find('.mz-noresult').css('display', total > 0 && shown($root.find('.mz-wh')) === 0 ? 'block' : 'none');
                }

                $root.off('.mz');
                $root.on('click.mz', '.mz-filter[data-state]', function() {
                    $root.find('.mz-filter[data-state]').removeClass('is-active');
                    $(this).addClass('is-active');
                    state = $(this).attr('data-state') || '';
                    apply();
                });
                $root.on('click.mz', '.mz-zt-filter', function() {
                    $root.find('.mz-zt-filter').removeClass('is-active');
                    $(this).addClass('is-active');
                    zoneType = $(this).attr('data-zone-type');
                    apply();
                });
                $root.on('input.mz', '.mz-search-input', apply);

                // Thu gọn / mở từng kho
                $root.on('click.mz', '.mz-wh-head', function() {
                    $(this).closest('.mz-wh').toggleClass('is-closed');
                });
                $root.on('click.mz', '.mz-toggle-all', function() {
                    var closeAll = $root.find('.mz-wh').not('.is-closed').length > 0;
                    $root.find('.mz-wh').toggleClass('is-closed', closeAll);
                    $(this).html(closeAll ?
                        '<i class="fas fa-expand mr-1"></i> Mở tất cả' :
                        '<i class="fas fa-compress mr-1"></i> Thu gọn tất cả');
                });

                // Bấm một ô vị trí -> xem đủ các mã đang nằm ở đó
                $root.on('click.mz', '.mz-cell[data-key]', function() {
                    var n = index[$(this).attr('data-key')];
                    if (!n) return;

                    $modal.find('.mz-d-name, .mz-d-code').text(n.code);
                    $modal.find('.mz-d-path').text(n.path);
                    $modal.find('.mz-d-lots').text(n.lots);
                    $modal.find('.mz-d-items').text(n.cats.length);

                    var $tb = $modal.find('tbody').empty();
                    n.items.forEach(function(r, i) {
                        $tb.append('<tr><td class="text-center">' + (i + 1) + '</td>' +
                            '<td class="font-weight-bold">' + esc(r.code) + '</td>' +
                            '<td><div class="font-weight-bold">' + esc(r.name || '—') + '</div>' +
                            (r.sub ? '<div class="md-sub small text-muted">' + esc(r.sub) + '</div>' : '') + '</td>' +
                            '<td class="text-right font-weight-bold">' + num(r.remaining) + ' ' + esc(r.unit) + '</td>' +
                            '<td class="text-center md-sub">' + date(r.expired) + '</td>' +
                            '<td class="text-center"><span class="inv-badge inv-badge-' + esc(r.state) + '">' +
                            esc(r.state_label) + '</span></td></tr>');
                    });
                    $modal.modal('show');
                });

                apply();
            };
        })();
    </script>
@endonce
