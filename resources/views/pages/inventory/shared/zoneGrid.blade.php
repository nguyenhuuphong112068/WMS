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
| $zgCompat (chỉ Hoá Chất) = App\Support\ChemicalCompatibility::clientConfig(): đối chiếu tương kỵ GHS giữa các lô
| CÒN TỒN nằm cùng tầng (rows[].sw = nhóm cảnh báo của lô) - ô có tương kỵ viền đỏ + dấu ⚠, chip lọc "Tương kỵ",
| hộp chi tiết liệt kê hoá chất tương kỵ; node.conflicts dùng chung cho 3D / Mặt bằng (zone3d).
|
| KHỐI "CHƯA XẾP VỊ TRÍ" thu gọn / mở được (nhớ theo trình duyệt), ô tìm kiếm lọc cả thẻ trong khối.
| $zgAssign (tuỳ chọn, null = chỉ xem) bật ĐỊNH KHU NHANH: mỗi thẻ có ô chọn + nút [Định khu], đầu khối
| có [Chọn tất cả] và [Định khu N mã đã chọn], mở modal #<zgId>Assign chọn một vị trí (Select2 AJAX)
| rồi POST {import_ids[], location_id} về $zgAssign['url']. Khai báo:
|   ['url' => route POST, 'lookup' => route tra cứu định khu (CategoryLookup), 'max' => số mã tối đa/lượt]
| Lưu xong gọi opts.onAssigned(res) để trang tự vá dữ liệu rồi dựng lại; không có thì tự vá rows và dựng lại.
--}}
@include('pages.inventory.shared.zoneMapStyles')
@include('pages.inventory.shared.zone3d')
{{-- nút camera ở ô tìm (thường đã nạp qua ô quét mã của tab Theo mã xuất nhập; @once nên không nạp lại) --}}
@include('pages.shared.cameraScan')

@php
    $zgItem = $zgItem ?? 'hoá chất';
    $zgCode = $zgCode ?? 'Mã xuất nhập';
    $zgAssign = ! empty($zgAssign) ? $zgAssign + ['token' => csrf_token(), 'max' => 200] : null;
    $zgCompat = $zgCompat ?? null;
    $zgZoneTypes = collect(\App\Support\ZoneType::TYPES)
        ->map(function ($label, $type) {
            $color = \App\Support\ZoneType::color($type);

            return ['bg' => $color, 'text' => \App\Support\ZoneType::textOn($color)];
        })
        ->all();
@endphp

<div class="mz-root" id="{{ $zgId }}" data-item="{{ $zgItem }}" data-code-label="{{ $zgCode }}"
    data-zone-types="{{ json_encode($zgZoneTypes) }}" @if ($zgAssign) data-assign="{{ json_encode($zgAssign) }}" @endif
    @if ($zgCompat) data-compat="{{ json_encode($zgCompat) }}" @endif>
    {{-- Một dòng: chip lọc bên trái, công cụ (Thẻ | 3D | Mặt bằng, tìm kiếm) bên phải - hẹp thì tự xuống dòng --}}
    <div class="mz-head">
        <div class="mz-filters">
            <button type="button" class="mz-filter is-active" data-state="">Tất cả vị trí</button>
            <button type="button" class="mz-filter" data-state="stocked"><span class="dot ok"></span>Đang có hàng</button>
            <button type="button" class="mz-filter" data-state="alert"><span class="dot warn"></span>Cần chú ý</button>
            <button type="button" class="mz-filter" data-state="empty"><span class="dot empty"></span>Còn trống</button>
            @if ($zgCompat)
                <button type="button" class="mz-filter mz-filter-compat is-zero" data-state="compat"
                    title="Vị trí có hoá chất nằm cùng {{ \Illuminate\Support\Str::lower($zgCompat['scope_label']) }} với hoá chất tương kỵ (Sơ đồ lưu trữ GHS)"><i
                        class="fas fa-exclamation-triangle"></i>Tương kỵ<b class="mz-compat-count">0</b></button>
            @endif

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

        <div class="mz-tools">
            <div class="mz-search scan-box">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm mz-search-input" autocomplete="off"
                    placeholder="Tìm vị trí, kệ/tủ, mã hoặc tên {{ $zgItem }}...">
                <button type="button" class="btn btn-outline-primary btn-camera-scan" title="Quét mã trên nhãn bằng camera">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mz-toggle-all">
                <i class="fas fa-compress mr-1"></i> Thu gọn tất cả
            </button>
        </div>
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

                <div class="mz-d-compat" hidden></div>

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

@if ($zgAssign)
    {{-- Định khu nhanh các mã đang "Chưa xếp vị trí" --}}
    <div class="modal fade md-modal" id="{{ $zgId }}Assign" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-map-marker-alt"></i> Định Khu Nhanh</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-6 col-md-3">
                            <small class="text-muted">{{ $zgCode }}</small>
                            <div class="font-weight-bold mz-as-count">0</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted">{{ \Illuminate\Support\Str::ucfirst($zgItem) }}</small>
                            <div class="font-weight-bold mz-as-items">0</div>
                        </div>
                    </div>

                    <div class="mz-as-list mb-3">
                        <table class="table table-bordered table-sm mb-0 md-no-sticky">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px">STT</th>
                                    <th style="width:160px">{{ $zgCode }}</th>
                                    <th>{{ \Illuminate\Support\Str::ucfirst($zgItem) }}</th>
                                    <th class="text-right" style="width:120px">Tồn</th>
                                    <th class="text-center" style="width:105px">Hạn Áp Dụng</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="form-group mb-0">
                        <label>Vị trí lưu trữ <span class="text-danger">*</span></label>
                        <select class="form-control mz-as-location" data-lookup-url="{{ $zgAssign['lookup'] }}" data-zone-filter>
                            <option value="">-- Chọn vị trí lưu trữ --</option>
                        </select>
                        <span class="md-error mz-as-error" hidden></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="button" class="btn btn-primary mz-as-submit">
                        <i class="fas fa-map-marker-alt mr-1"></i> Lưu định khu
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

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

            /**
             * Tương kỵ GHS giữa các lô CÒN TỒN nằm cùng phạm vi (compat.scope, đang dùng: cùng tầng). Gắn vào từng
             * node: conflicts = [{ownId, own, ownCode, ownPlaces, otherId, other, otherCode, places, pairs: [[thuộc tính
             * của own, thuộc tính của other]]}] - hoá chất own (có ở ô này) tương kỵ với hoá chất other đang nằm ở các vị
             * trí places trên cùng tầng. Trả số vị trí có tương kỵ.
             */
            function markConflicts(nodes, compat) {
                var matrix = {};
                Object.keys(compat.matrix || {}).forEach(function(a) {
                    matrix[a] = {};
                    (compat.matrix[a] || []).forEach(function(b) { matrix[a][b] = true; });
                });
                var label = function(code) { return (compat.labels || {})[code] || code; };
                var groups = {};

                nodes.forEach(function(n) {
                    n.conflicts = [];
                    n.items.forEach(function(r) {
                        if (!r.category_id || !r.sw || !r.sw.length) return;
                        var g = groups[n.scopeKey] = groups[n.scopeKey] || {};
                        var c = g[r.category_id] = g[r.category_id] ||
                            { id: r.category_id, name: r.name || '—', code: r.category_code || '', sw: r.sw, nodes: [], places: {} };
                        if (c.nodes.indexOf(n) < 0) c.nodes.push(n);
                        c.places[n.code] = true;
                    });
                });

                function pairsOf(a, b) {
                    var out = [];
                    a.sw.forEach(function(x) {
                        b.sw.forEach(function(y) {
                            if (matrix[x] && matrix[x][y]) out.push([label(x), label(y)]);
                        });
                    });
                    return out;
                }

                function flag(a, b, pairs) {
                    a.nodes.forEach(function(n) {
                        n.conflicts.push({
                            ownId: a.id, own: a.name, ownCode: a.code, ownPlaces: Object.keys(a.places).sort(),
                            otherId: b.id, other: b.name, otherCode: b.code, places: Object.keys(b.places).sort(), pairs: pairs
                        });
                    });
                }

                Object.keys(groups).forEach(function(key) {
                    var cats = values(groups[key]);
                    for (var i = 0; i < cats.length; i++) {
                        for (var j = i + 1; j < cats.length; j++) {
                            var ab = pairsOf(cats[i], cats[j]);
                            if (!ab.length) continue;
                            flag(cats[i], cats[j], ab);
                            flag(cats[j], cats[i], pairsOf(cats[j], cats[i]));
                        }
                    }
                });

                return nodes.filter(function(n) { return n.conflicts.length > 0; }).length;
            }

            /** "Tương kỵ cùng tầng: A ✕ B (vị trí); ..." - tooltip ngắn của một ô */
            function conflictTitle(n) {
                return 'Tương kỵ cùng tầng: ' + n.conflicts.map(function(c) {
                    return c.own + ' ✕ ' + c.other + ' (' + c.places.join(', ') + ')';
                }).join('; ');
            }
            window.wmsConflictTitle = conflictTitle;

            function uniq(list) {
                return list.filter(function(x, i) { return list.indexOf(x) === i; });
            }

            /**
             * Kết luận tương kỵ của một vị trí: mỗi CẶP hoá chất một dòng (A ✕ B và B ✕ A gộp làm một) -
             * "A (thuộc tính ...) tương kỵ với B (thuộc tính ...)" + chi tiết thuộc tính nào của A kỵ thuộc tính nào của B.
             */
            function conflictConclusion(n, scopeLabel) {
                var seen = {};
                var list = n.conflicts.filter(function(c) {
                    var key = [c.ownId, c.otherId].sort().join('-');
                    if (seen[key]) return false;
                    seen[key] = true;
                    return true;
                });
                var chips = function(attrs) {
                    return attrs.map(function(a) { return '<span class="attr">' + esc(a) + '</span>'; }).join(' ');
                };
                var who = function(name, code, places) {
                    return '<b>' + esc(name) + '</b>' + (code ? ' <span class="md-sub">' + esc(code) + '</span>' : '') +
                        ' <span class="md-sub">(ở ' + esc(places.join(', ')) + ')</span>';
                };

                var html = '<div class="mz-d-compat-head"><i class="fas fa-exclamation-triangle mr-1"></i>Kết luận: ' +
                    list.length + ' cặp hoá chất tương kỵ nằm cùng ' + esc(String(scopeLabel || 'tầng').toLowerCase()) +
                    ' (Sơ đồ lưu trữ GHS) - nên tách ra tầng khác</div><ol>';

                list.forEach(function(c) {
                    // Thuộc tính của hoá chất này -> các thuộc tính của hoá chất kia mà nó kỵ
                    var byOwn = {};
                    c.pairs.forEach(function(p) { (byOwn[p[0]] = byOwn[p[0]] || []).push(p[1]); });

                    html += '<li><div class="concl">' + who(c.own, c.ownCode, c.ownPlaces) + ' có thuộc tính ' +
                        chips(uniq(c.pairs.map(function(p) { return p[0]; }))) + ' <b class="vs">tương kỵ với</b> thuộc tính ' +
                        chips(uniq(c.pairs.map(function(p) { return p[1]; }))) + ' của ' + who(c.other, c.otherCode, c.places) + '</div>' +
                        '<div class="pairs">' + Object.keys(byOwn).map(function(attr) {
                            return esc(attr) + ' ✕ ' + esc(uniq(byOwn[attr]).join(', '));
                        }).join(' · ') + '</div></li>';
                });

                return html + '</ol>';
            }

            window.invZoneGrid = function(selector, opts) {
                var $root = $(selector);
                var $modal = $(selector + 'Modal');
                var zones = opts.zones || {};
                var rows = opts.rows || [];
                var item = $root.data('item') || 'hoá chất';
                var zoneTypes = $root.data('zone-types') || {};
                // Định khu nhanh: chỉ bật khi trang khai $zgAssign (có quyền)
                var assign = $root.data('assign') || null;
                // Đối chiếu tương kỵ (chỉ Tồn Kho Hoá Chất khai $zgCompat)
                var compat = $root.data('compat') || null;
                var $assign = $(selector + 'Assign');

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
                    // Vùng "gần nhau" để xét tương kỵ (vị trí chưa gán cấp đang xét thì chỉ xét chính nó)
                    var scopeId = compat ? (compat.scope === 'location' ? loc.id : loc[compat.scope + '_id']) : null;
                    node.scopeKey = scopeId ? compat.scope + scopeId : 'location' + loc.id;
                    node.search = [node.code, sn.name, cn.name, tn.name].concat(node.items.map(function(r) {
                        return r.name + ' ' + r.code;
                    })).join(' ').toLowerCase();

                    index[node.key] = node;
                    tn.locations.push(node);
                });

                var compatCount = compat ? markConflicts(values(index), compat) : 0;
                $root.find('.mz-compat-count').text(compatCount);
                $root.find('.mz-filter-compat').toggleClass('is-zero', compatCount === 0);

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
                // (Đã bỏ hàng thẻ thống kê đầu sơ đồ - nhường chỗ cho khung 3D / Mặt bằng; số vị trí có hàng,
                //  số mã từng kho / kệ vẫn hiện ở đầu khối kho và dòng tiêu đề khung 3D.)
                function cell(n) {
                    var zoned = n.badge.bg !== '#FFFFFF';
                    var more = n.cats.length - n.preview.length;
                    var clash = n.conflicts && n.conflicts.length > 0;
                    var html = '<button type="button" class="mz-cell is-' + n.state + (zoned ? ' is-zoned' : '') + (clash ? ' is-compat' : '') + '"' +
                        ' data-key="' + n.key + '" data-state="' + n.state + '" data-zone-type="' + esc(n.zoneType) + '"' +
                        (clash ? ' data-compat="1"' : '') +
                        ' data-search="' + esc(n.search) + '"' +
                        ' style="--chip: ' + esc(n.badge.bg) + '; --chip-text: ' + esc(n.badge.text) + '"' +
                        (n.state === 'empty' ? ' disabled' : '') + '>' +
                        '<div class="mz-cell-top"><span class="mz-cell-code">' + esc(n.code) + '</span>' +
                        (clash ? '<span class="mz-cell-compat" title="' + esc(conflictTitle(n)) + '"><i class="fas fa-exclamation"></i></span>' : '') +
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
                var codeLabel = String($root.data('code-label') || '').toLowerCase();
                var unzonedKey = 'wms.zoneGrid.unzonedClosed.' + ($root.attr('id') || '');
                var unzonedById = {};

                unzoned.forEach(function(r) { unzonedById[r.id] = r; });

                function unzonedClosed() {
                    try { return window.localStorage.getItem(unzonedKey) === '1'; } catch (e) { return false; }
                }

                function rememberUnzoned(closed) {
                    try { window.localStorage.setItem(unzonedKey, closed ? '1' : '0'); } catch (e) {}
                }

                /** Thẻ một mã chưa xếp vị trí; có quyền định khu thì kèm ô chọn + nút [Định khu] */
                function unzonedCard(r) {
                    return '<div class="mz-cell mz-ucard is-warn' + (assign ? ' is-pickable' : '') + '" data-id="' + esc(r.id) + '"' +
                        ' data-search="' + esc([r.code, r.name, r.sub].join(' ').toLowerCase()) + '">' +
                        '<div class="mz-cell-top">' +
                        (assign ? '<input type="checkbox" class="mz-ucard-pick" title="Chọn để định khu cùng lúc">' : '') +
                        '<span class="mz-cell-code">' + esc(r.code) + '</span></div>' +
                        '<div class="mz-ucard-row"><span class="mz-cell-name" title="' + esc(r.name || '') + '">' +
                        esc(r.name || '—') + '</span>' +
                        '<span class="mz-cell-lots">' + num(r.remaining) + ' ' + esc(r.unit) + '</span></div>' +
                        '<div class="mz-cell-foot mz-ucard-foot"><span>Hạn áp dụng ' + date(r.expired) + '</span>' +
                        (assign ? '<button type="button" class="mz-ucard-assign" title="Xếp mã này vào một vị trí lưu trữ">' +
                            '<i class="fas fa-map-marker-alt"></i> Định khu</button>' : '') +
                        '</div></div>';
                }

                // Mã còn tồn nhưng chưa xếp vị trí - để trên cùng cho dễ thấy mà bổ sung định khu
                if (unzoned.length) {
                    html += '<div class="mz-unzoned' + (unzonedClosed() ? ' is-closed' : '') + '">' +
                        '<div class="mz-unzoned-head is-toggle" title="Bấm để thu gọn / mở khối">' +
                        '<i class="fas fa-chevron-down mz-caret"></i><i class="fas fa-exclamation-triangle"></i>' +
                        ' Chưa xếp vị trí <span class="mz-pill is-alert mz-unzoned-count">' + unzoned.length + ' ' +
                        esc(codeLabel) + '</span>' +
                        (assign ? '<div class="mz-unzoned-tools">' +
                            '<button type="button" class="btn btn-sm btn-outline-primary mz-pick-all">' +
                            '<i class="far fa-check-square mr-1"></i> <span>Chọn tất cả</span></button>' +
                            '<button type="button" class="btn btn-sm btn-primary mz-assign-picked" disabled>' +
                            '<i class="fas fa-map-marker-alt mr-1"></i> Định khu <b class="mz-picked-count">0</b> mã đã chọn</button>' +
                            '</div>' : '') +
                        '</div><div class="mz-grid mz-unzoned-grid">' + unzoned.map(unzonedCard).join('') + '</div></div>';
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
                            (state === 'alert' && (st === 'warn' || st === 'danger')) ||
                            (state === 'compat' && $(this).attr('data-compat') === '1');
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

                    // Khối Chưa xếp vị trí: chỉ lọc theo từ khoá (mã, tên); đang tìm thì đếm "khớp/tổng"
                    var $cards = $root.find('.mz-ucard');
                    var cardsShown = 0;

                    if ($cards.length) {
                        $cards.each(function() {
                            this.style.display = q === '' || ($(this).attr('data-search') || '').indexOf(q) >= 0 ? '' : 'none';
                        });
                        cardsShown = shown($cards);
                        $root.find('.mz-unzoned-count').text((q ? cardsShown + '/' : '') + $cards.length + ' ' + codeLabel);
                        // Lọc Tương kỵ: mã chưa xếp vị trí không nằm trên tầng nào nên ẩn cả khối
                        if (state === 'compat') cardsShown = 0;
                        $root.find('.mz-unzoned').css('display', (q && !cardsShown) || state === 'compat' ? 'none' : '');
                        syncPicked();
                    }

                    var total = $root.find('.mz-wh').length;
                    $root.find('.mz-noresult').css('display',
                        (total > 0 || $cards.length) && shown($root.find('.mz-wh')) === 0 && !cardsShown ? 'block' : 'none');
                }

                /* ---------- Định khu nhanh: chọn thẻ, mở modal, lưu ---------- */
                function pickedIds() {
                    return $root.find('.mz-ucard.is-picked').map(function() { return $(this).attr('data-id'); }).get();
                }

                /** Đồng bộ nút đầu khối theo số thẻ đang chọn / đang hiện */
                function syncPicked() {
                    if (!assign) return;

                    var count = pickedIds().length;
                    var $visible = $root.find('.mz-ucard').filter(function() { return this.style.display !== 'none'; });
                    var allPicked = $visible.length > 0 && $visible.not('.is-picked').length === 0;

                    $root.find('.mz-picked-count').text(count);
                    $root.find('.mz-assign-picked').prop('disabled', count === 0);
                    $root.find('.mz-pick-all span').text(allPicked ? 'Bỏ chọn tất cả' : 'Chọn tất cả');
                }

                function setPicked($card, on) {
                    $card.toggleClass('is-picked', on).find('.mz-ucard-pick').prop('checked', on);
                }

                function openAssign(ids) {
                    if (!assign || !$assign.length) return;

                    var max = Number(assign.max) || 200;
                    if (ids.length > max) {
                        var text = 'Mỗi lần định khu tối đa ' + max + ' ' + codeLabel + ', vui lòng bỏ bớt mã đã chọn.';
                        return window.Swal ? Swal.fire({ icon: 'info', title: 'Chọn quá nhiều', text: text, confirmButtonColor: '#2E7BC4' }) :
                            window.alert(text);
                    }

                    var list = ids.map(function(id) { return unzonedById[id]; }).filter(Boolean);
                    var categories = {};
                    var $tb = $assign.find('tbody').empty();

                    list.forEach(function(r, i) {
                        categories[r.category_id ? 'c' + r.category_id : 'n' + r.name] = r.category_id || null;
                        $tb.append('<tr><td class="text-center">' + (i + 1) + '</td>' +
                            '<td class="font-weight-bold">' + esc(r.code) + '</td>' +
                            '<td><div class="font-weight-bold">' + esc(r.name || '—') + '</div>' +
                            (r.sub ? '<div class="md-sub small text-muted">' + esc(r.sub) + '</div>' : '') + '</td>' +
                            '<td class="text-right font-weight-bold">' + num(r.remaining) + ' ' + esc(r.unit) + '</td>' +
                            '<td class="text-center md-sub">' + date(r.expired) + '</td></tr>');
                    });

                    $assign.find('.mz-as-count').text(list.length);
                    $assign.find('.mz-as-items').text(Object.keys(categories).length);
                    $assign.find('.mz-as-error').prop('hidden', true).text('');
                    $assign.data('ids', list.map(function(r) { return r.id; }));
                    // Danh sách vị trí cảnh báo sẵn chỗ tương kỵ với bất kỳ hoá chất nào đang xếp
                    $assign.data('categoryIds', values(categories).filter(Boolean));

                    var $select = $assign.find('.mz-as-location');
                    $select.val(null).find('option[value!=""]').remove();
                    $select.trigger('change');

                    $assign.modal('show');
                }

                function saveAssign() {
                    var ids = $assign.data('ids') || [];
                    var locationId = $assign.find('.mz-as-location').val();
                    var $error = $assign.find('.mz-as-error');
                    var $btn = $assign.find('.mz-as-submit');

                    if (!locationId) {
                        $error.text('Vui lòng chọn vị trí lưu trữ.').prop('hidden', false);
                        return;
                    }

                    $error.prop('hidden', true).text('');
                    $btn.prop('disabled', true).find('i').attr('class', 'fas fa-circle-notch fa-spin mr-1');

                    $.ajax({
                        url: assign.url,
                        method: 'POST',
                        dataType: 'json',
                        data: { _token: assign.token, import_ids: ids, location_id: locationId }
                    }).done(function(res) {
                        $assign.modal('hide');

                        // Tương kỵ không chặn: vẫn lưu, báo cảnh báo để thủ kho cân nhắc chuyển chỗ
                        if (window.Swal && res.warning) {
                            Swal.fire({ icon: 'warning', title: 'Đã lưu - có tương kỵ', html: esc(res.message) + '<br><br>' +
                                esc(res.warning).split(' | ').join('<br>'), confirmButtonColor: '#2E7BC4' });
                        } else if (window.Swal) {
                            Swal.fire({ icon: 'success', title: 'Thành công!', text: res.message, timer: 1800, showConfirmButton: false });
                        }

                        if (typeof opts.onAssigned === 'function') {
                            opts.onAssigned(res);
                            return;
                        }

                        // Trang không tự xử lý: vá vị trí vào dòng tồn rồi dựng lại sơ đồ
                        (res.import_ids || []).forEach(function(id) {
                            rows.forEach(function(r) { if (String(r.id) === String(id)) $.extend(r, res.location); });
                        });
                        window.invZoneGrid(selector, opts);
                        $root.find('.mz-filter[data-state].is-active, .mz-zt-filter.is-active').trigger('click');
                    }).fail(function(xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message) ||
                            'Không lưu được định khu, vui lòng tải lại trang rồi thử lại.';
                        $error.text(message).prop('hidden', false);
                    }).always(function() {
                        $btn.prop('disabled', false).find('i').attr('class', 'fas fa-map-marker-alt mr-1');
                    });
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
                // Thu gọn / mở khối Chưa xếp vị trí (bấm nút trong đầu khối thì không tính)
                $root.on('click.mz', '.mz-unzoned-head', function(e) {
                    if ($(e.target).closest('.mz-unzoned-tools').length) return;

                    var $block = $(this).closest('.mz-unzoned').toggleClass('is-closed');
                    rememberUnzoned($block.hasClass('is-closed'));
                });
                $root.on('click.mz', '.mz-toggle-all', function() {
                    var closeAll = $root.find('.mz-wh, .mz-unzoned').not('.is-closed').length > 0;
                    $root.find('.mz-wh, .mz-unzoned').toggleClass('is-closed', closeAll);
                    if ($root.find('.mz-unzoned').length) rememberUnzoned(closeAll);
                    $(this).html(closeAll ?
                        '<i class="fas fa-expand mr-1"></i> Mở tất cả' :
                        '<i class="fas fa-compress mr-1"></i> Thu gọn tất cả');
                });

                if (assign) {
                    // Bấm vào thẻ (ngoài nút Định khu) = chọn / bỏ chọn
                    $root.on('click.mz', '.mz-ucard', function(e) {
                        if ($(e.target).closest('.mz-ucard-assign').length) return;

                        var $card = $(this);
                        var on = $(e.target).is('.mz-ucard-pick') ? e.target.checked : !$card.hasClass('is-picked');
                        setPicked($card, on);
                        syncPicked();
                    });
                    $root.on('click.mz', '.mz-ucard-assign', function() {
                        openAssign([$(this).closest('.mz-ucard').attr('data-id')]);
                    });
                    $root.on('click.mz', '.mz-pick-all', function() {
                        var $visible = $root.find('.mz-ucard').filter(function() { return this.style.display !== 'none'; });
                        var on = $visible.not('.is-picked').length > 0;
                        setPicked($visible, on);
                        syncPicked();
                    });
                    $root.on('click.mz', '.mz-assign-picked', function() {
                        openAssign(pickedIds());
                    });

                    $assign.off('.mz');
                    $assign.on('click.mz', '.mz-as-submit', saveAssign);
                    $assign.on('change.mz', '.mz-as-location', function() {
                        if ($(this).val()) $assign.find('.mz-as-error').prop('hidden', true).text('');
                    });
                    // Mở modal là mở luôn danh sách vị trí để gõ tìm ngay
                    $assign.on('shown.bs.modal.mz', function() {
                        var $select = $assign.find('.mz-as-location');

                        if (!$select.hasClass('select2-hidden-accessible')) {
                            $select.data('lookupParams', function() {
                                return { category_ids: $assign.data('categoryIds') || [] };
                            });
                            $select.select2({
                                theme: 'bootstrap4',
                                width: '100%',
                                dropdownParent: $assign,
                                placeholder: '-- Chọn vị trí lưu trữ --',
                                ajax: window.wmsLookupAjax($select),
                                // Vị trí tương kỵ: vẫn chọn được, hiện lý do cảnh báo bên dưới
                                templateResult: function(row) {
                                    if (!row.id || !row.warning) return window.wmsZoneTemplate(row);

                                    return $('<div class="mz-opt-warn">').append(
                                        $('<div>').append(window.wmsZoneTemplate(row)),
                                        $('<small>').text(row.warning)
                                    );
                                }
                            });
                        }

                        $select.select2('open');
                    });
                }

                // Bấm một ô vị trí -> xem đủ các mã đang nằm ở đó
                $root.on('click.mz', '.mz-cell[data-key]', function() {
                    var n = index[$(this).attr('data-key')];
                    if (!n) return;

                    $modal.find('.mz-d-name, .mz-d-code').text(n.code);
                    $modal.find('.mz-d-path').text(n.path);
                    $modal.find('.mz-d-lots').text(n.lots);
                    $modal.find('.mz-d-items').text(n.cats.length);

                    var clash = n.conflicts && n.conflicts.length > 0;
                    var $compat = $modal.find('.mz-d-compat').empty().prop('hidden', !clash);
                    if (clash) $compat.html(conflictConclusion(n, compat.scope_label));

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

                // Nút Thẻ | 3D: dựng kệ 3D từ cùng khung định khu và các ô vừa gom ở trên
                if (window.invZone3d) {
                    window.invZone3d.init($root[0], {
                        zones: { warehouses: warehouses, shelves: shelves, columns: columns, tiers: tiers, locations: locations },
                        nodes: index,
                        compat: compat
                    });
                }
            };
        })();
    </script>
@endonce
