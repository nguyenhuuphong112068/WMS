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
|   ['url' => route POST, 'lookup' => route tra cứu định khu (CategoryLookup), 'max' => số mã tối đa/lượt,
|    'except' => loại định khu không nhận hàng (zone_type)]
| Mỗi thẻ vị trí (trừ loại 'except') có thêm nút [Đặt <hàng>] mở modal #<zgId>Place: chọn nhiều mã Chưa xếp vị trí
| (tìm kiếm, cảnh báo tương kỵ cùng tầng) để đặt vào chính vị trí đó - cùng url POST.
| Lưu xong gọi opts.onAssigned(res) để trang tự vá dữ liệu rồi dựng lại; không có thì tự vá rows và dựng lại.
--}}
@include('pages.inventory.shared.zoneMapStyles')
@include('pages.inventory.shared.zone3d')
{{-- nút camera ở ô tìm (thường đã nạp qua ô quét mã của tab Theo mã xuất nhập; @once nên không nạp lại) --}}
@include('pages.shared.cameraScan')

@php
    $zgItem = $zgItem ?? 'hoá chất';
    $zgCode = $zgCode ?? 'Mã xuất nhập';
    $zgAssign = ! empty($zgAssign) ? $zgAssign + ['token' => csrf_token(), 'max' => 200, 'except' => []] : null;
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

    @if (! empty($zgAssign['moveUrl']))
        {{-- Thanh vùng chọn: Shift + kéo quét (hoặc Shift + bấm) chọn nhiều ô có hàng để dời cùng lúc --}}
        <div class="mz-pickbar" hidden>
            <span><i class="fas fa-object-group mr-1"></i> Đã chọn <b class="mz-pk-cells">0</b> ô · <b class="mz-pk-lots">0</b> mã</span>
            <button type="button" class="btn btn-sm btn-primary mz-pk-move" title="Gộp tất cả mã đã chọn vào một vị trí - hoặc kéo một ô đã chọn thả vào ô đích để dời nguyên khối">
                <i class="fas fa-exchange-alt mr-1"></i> Đổi vị trí
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary mz-pk-clear" title="Bỏ chọn (Esc)">
                <i class="fas fa-times mr-1"></i> Bỏ chọn
            </button>
        </div>
    @endif
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
                            @if ($zgAssign)
                                <th class="text-center mz-d-pickcol" style="width:40px"><input type="checkbox" class="mz-d-all"
                                        title="Chọn tất cả"></th>
                            @endif
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
                @if ($zgAssign)
                    <div class="mz-d-actions mr-auto">
                        <button type="button" class="btn btn-outline-primary mz-d-add">
                            <i class="fas fa-plus mr-1"></i> Thêm {{ $zgItem }}
                        </button>
                        <button type="button" class="btn btn-outline-primary mz-d-move" disabled
                            title="Chọn mã trong bảng để chuyển sang vị trí khác">
                            <i class="fas fa-exchange-alt mr-1"></i> Đổi vị trí <b>0</b> mã đã chọn
                        </button>
                    </div>
                @endif
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

    {{-- Thêm các mã "Chưa xếp vị trí" vào MỘT vị trí - mở từ nút [Thêm ...] trong hộp chi tiết vị trí --}}
    <div class="modal fade md-modal" id="{{ $zgId }}Place" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm {{ $zgItem }} vào vị trí <span class="mz-pl-code"></span></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <small class="text-muted">Đường dẫn định khu</small>
                            <div class="font-weight-bold mz-pl-path">—</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted">{{ $zgCode }} đang có</small>
                            <div class="font-weight-bold mz-pl-lots">0</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted">Đã chọn</small>
                            <div class="font-weight-bold text-primary"><span class="mz-pl-count">0</span> mã</div>
                        </div>
                    </div>

                    <div class="mz-search mb-2">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control form-control-sm mz-pl-search" autocomplete="off"
                            placeholder="Tìm {{ \Illuminate\Support\Str::lower($zgCode) }} hoặc tên {{ $zgItem }} chưa xếp vị trí...">
                    </div>

                    <div class="mz-as-list mz-pl-list">
                        <table class="table table-bordered table-sm mb-0 md-no-sticky">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:40px"><input type="checkbox" class="mz-pl-all" title="Chọn tất cả mã đang hiện"></th>
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
                    <span class="md-error mz-pl-error" hidden></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="button" class="btn btn-primary mz-pl-submit" disabled>
                        <i class="fas fa-map-marker-alt mr-1"></i> Thêm vào vị trí
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if (! empty($zgAssign['moveUrl']))
        {{-- Đổi vị trí các mã đang nằm ở một ô (từ hộp chi tiết hoặc kéo ô thả sang ô khác) - bắt buộc lý do --}}
        <div class="modal fade md-modal" id="{{ $zgId }}Move" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg mz-mv-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> Đổi Vị Trí</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-2">
                            <div class="col-6 col-md-4">
                                <small class="text-muted">Vị trí hiện tại</small>
                                <div class="font-weight-bold mz-mv-from">—</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <small class="text-muted">{{ $zgCode }}</small>
                                <div class="font-weight-bold mz-mv-count">0</div>
                            </div>
                        </div>

                        {{-- Chọn nhiều ô (Shift + quét) rồi thả vào một ô: dời nguyên khối hoặc gộp vào ô đó --}}
                        <div class="mz-mv-modes mb-3" hidden>
                            <label class="mz-mv-mode">
                                <input type="radio" name="{{ $zgId }}MoveMode" value="block">
                                <span><b>Dời nguyên khối</b> <small class="mz-mv-mode-note"></small></span>
                            </label>
                            <label class="mz-mv-mode">
                                <input type="radio" name="{{ $zgId }}MoveMode" value="merge">
                                <span><b>Gộp vào một vị trí</b> <small class="mz-mv-merge-note"></small></span>
                            </label>
                        </div>

                        <div class="mz-as-list mz-mv-list mb-3">
                            <table class="table table-bordered table-sm mb-0 md-no-sticky">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:50px">STT</th>
                                        <th style="width:150px">{{ $zgCode }}</th>
                                        <th>{{ \Illuminate\Support\Str::ucfirst($zgItem) }}</th>
                                        <th class="text-right" style="width:110px">Tồn</th>
                                        <th style="width:150px">Vị trí hiện tại</th>
                                        <th style="width:150px">Vị trí mới</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="form-group mz-mv-target">
                            <label>Vị trí mới <span class="text-danger">*</span></label>
                            <select class="form-control mz-mv-location" data-lookup-url="{{ $zgAssign['lookup'] }}" data-zone-filter>
                                <option value="">-- Chọn vị trí lưu trữ --</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label>Lý do đổi vị trí <span class="text-danger">*</span></label>
                            <textarea class="form-control mz-mv-reason" rows="2" maxlength="255"
                                placeholder="{{ $zgCompat ? 'Ví dụ: tách hoá chất tương kỵ, sắp xếp lại tủ...' : 'Ví dụ: sắp xếp lại kệ/tủ...' }}"></textarea>
                            <span class="md-error mz-mv-error" hidden></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                        <button type="button" class="btn btn-primary mz-mv-submit">
                            <i class="fas fa-exchange-alt mr-1"></i> Lưu đổi vị trí
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
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

            /** Theo thứ tự đã khai ở Định Khu (position), mục chưa có thứ tự xếp sau theo tên */
            function byPosition(a, b) {
                var pa = Number(a.position) || 0, pb = Number(b.position) || 0;
                if (pa && pb && pa !== pb) return pa - pb;
                if (!pa !== !pb) return pa ? -1 : 1;
                return byText('name')(a, b);
            }

            /** Nhãn ô ngang đánh số từ 1 (giống Cấu trúc kho) */
            function letters(n) {
                return String(n);
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
                var $place = $(selector + 'Place');
                var $move = $(selector + 'Move');

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
                        { code: s.code, name: s.name || 'Chưa gán kệ/tủ', topDown: s.tier_order === 'top_down', children: {} };
                    var cn = sn.children[loc.column_id || 0] = sn.children[loc.column_id || 0] ||
                        { code: c.code, name: c.name || 'Chưa gán cột', position: c.position, children: {} };
                    var tn = cn.children[loc.tier_id || 0] = cn.children[loc.tier_id || 0] ||
                        { code: t.code, name: t.name || 'Chưa gán tầng', position: t.position, locations: [] };

                    var node = nodeOf(loc, byLocation[loc.id] || [], zoneTypes);
                    node.id = loc.id;
                    // Toạ độ ô trên lưới tầng: ngang (A, B...) × dãy sâu (1 = sát cửa) như Cấu trúc kho
                    node.p = Number(loc.position) > 0 ? Number(loc.position) : 0;
                    node.d = Math.max(1, Number(loc.depth) || 1);
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
                        s.list = values(s.children).sort(byPosition).map(function(c) {
                            var cAcc = blank();
                            c.list = values(c.children).sort(byPosition).map(function(t) {
                                var tAcc = blank();
                                t.locations.forEach(function(n) { add(tAcc, n); });
                                t.stat = tAcc;
                                // Ô khai lẻ chưa có toạ độ: xếp tiếp sau ô ngang xa nhất, theo mã
                                var next = t.locations.reduce(function(m, n) { return Math.max(m, n.p); }, 0);
                                t.locations.filter(function(n) { return !n.p; }).forEach(function(n) { n.p = ++next; });
                                t.width = t.locations.reduce(function(m, n) { return Math.max(m, n.p); }, 1);
                                t.depth = t.locations.reduce(function(m, n) { return Math.max(m, n.d); }, 1);
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
                /** Vị trí nhận thêm / đổi hàng được (có quyền, không phải khu Biệt trữ / Cách ly / Loại bỏ) */
                function canEdit(n) {
                    return !!assign && (assign.except || []).indexOf(n.zoneType) < 0;
                }

                /** Tooltip một ô: mã vị trí, phân loại, các hoá chất đang có */
                function slotTitle(n) {
                    var lines = [n.code + (n.zoneType ? ' · ' + ($root.find('.mz-zt-filter[data-zone-type="' + n.zoneType + '"]').text().trim()) : '')];
                    if (!n.items.length) lines.push('Đang trống');
                    n.preview.forEach(function(p) { lines.push('· ' + p.name + ': ' + num(p.remaining) + ' ' + p.unit); });
                    if (n.cats.length > n.preview.length) lines.push('· +' + (n.cats.length - n.preview.length) + ' ' + item + ' khác');
                    if (n.alerts > 0) lines.push(n.alerts + ' mã cần chú ý');
                    if (n.conflicts && n.conflicts.length) lines.push('⚠ ' + conflictTitle(n));

                    return lines.join('\n');
                }

                /** Một ô vị trí trên lưới tầng (kiểu Cấu trúc kho): màu theo tình trạng, số = số mã đang có */
                function slot(n) {
                    var clash = n.conflicts && n.conflicts.length > 0;
                    var zt = n.zoneType && zoneTypes[n.zoneType] ? zoneTypes[n.zoneType].bg : '';
                    var drag = canEdit(n) && n.lots > 0;

                    return '<span role="button" tabindex="0" class="mz-slot is-' + n.state + (zt ? ' has-zone' : '') +
                        (clash ? ' is-compat' : '') + (canEdit(n) ? ' is-drop' : '') + (drag && assign.moveUrl ? ' is-pickable' : '') + '"' +
                        ' data-key="' + n.key + '" data-state="' + n.state + '" data-zone-type="' + esc(n.zoneType) + '"' +
                        (clash ? ' data-compat="1"' : '') + (drag ? ' draggable="true"' : '') +
                        ' data-search="' + esc(n.search) + '"' +
                        ' style="grid-column:' + n.p + ';grid-row:' + n.row + (zt ? ';--zt:' + esc(zt) : '') + '"' +
                        ' title="' + esc(slotTitle(n)) + '">' + (n.lots || '') + '</span>';
                }

                /** Khối một tầng: thước số dãy bên trái, lưới ô, thước chữ ô ngang dọc mép trước */
                function tierBlock(t) {
                    var depthRule = '';
                    var frontRule = '';
                    for (var d = t.depth; d >= 1; d--) depthRule += '<span>' + d + '</span>';
                    for (var p = 1; p <= t.width; p++) frontRule += '<span>' + letters(p) + '</span>';
                    t.locations.forEach(function(n) { n.row = t.depth - n.d + 1; });

                    return '<div class="mz-tblock" style="--cols:' + t.width + ';--rows:' + t.depth + '">' +
                        '<div class="mz-depth-rule" title="Dãy tính từ cửa vào trong (1 = sát cửa)">' + depthRule + '</div>' +
                        '<div class="mz-slots">' + t.locations.map(slot).join('') + '</div>' +
                        '<div class="mz-front-rule" title="Ô ngang dọc mép trước tầng">' + frontRule + '</div></div>';
                }

                function codeTag(code) {
                    return code ? ' <span class="tag">' + esc(code) + '</span>' : '';
                }

                var shelfNo = 0;
                var gridAt = {};
                function gridKey(s, ci, ti, p, d) { return [s, ci, ti, p, d].join('|'); }

                var html = '';
                var codeLabel =String($root.data('code-label') || '').toLowerCase();
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
                        (assign ? ' draggable="true" title="Kéo thả vào một ô vị trí bên dưới để định khu"' : '') +
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
                        '</div></div><div class="mz-wh-body is-grid">';

                    w.list.forEach(function(s) {
                        // Toạ độ lưới của từng ô (kệ, cột, tầng, ngang, sâu) - dùng để Dời nguyên khối
                        var sk = ++shelfNo;
                        s.list.forEach(function(c, ci) {
                            c.list.forEach(function(t, ti) {
                                t.locations.forEach(function(n) {
                                    n.grid = { s: sk, ci: ci, ti: ti, p: n.p, d: n.d };
                                    gridAt[gridKey(sk, ci, ti, n.p, n.d)] = n;
                                });
                            });
                        });

                        html += '<div class="mz-shelf"><div class="mz-shelf-head"><i class="fas fa-layer-group"></i> ' +
                            esc(s.name) + codeTag(s.code) + '<span class="tag">' + s.list.length + ' cột</span>' +
                            '<span class="tag">' + s.stat.lots + ' mã</span></div><div class="mz-cols">';

                        // Mỗi Cột (cánh) một khối lưới như Cấu trúc kho: hàng = Tầng (Tầng 01 dưới cùng trừ khi kệ
                        // khai đánh số từ trên xuống), mỗi tầng = lưới ô Ngang × Sâu
                        s.list.forEach(function(c) {
                            html += '<div class="mz-col"><div class="mz-col-head">' +
                                '<div class="mz-col-title"><i class="fas fa-grip-lines-vertical"></i> ' + esc(c.name) +
                                (c.code ? ' <span class="md-tag">' + esc(c.code) + '</span>' : '') + '</div>' +
                                '<div class="mz-col-meta">' + c.list.length + ' tầng · ' + c.stat.filled + '/' + c.stat.locations +
                                ' vị trí có hàng · ' + c.stat.lots + ' mã</div></div><div class="mz-col-body">';

                            (s.topDown ? c.list : c.list.slice().reverse()).forEach(function(t) {
                                html += '<div class="mz-trow"><div class="mz-tlabel" title="' + esc(t.code || t.name) + '">' + esc(t.name) +
                                    (t.stat.lots ? '<small>' + t.stat.lots + ' mã</small>' : '') + '</div>' + tierBlock(t) + '</div>';
                            });

                            html += '</div></div>';
                        });

                        html += '</div></div>';
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

                    // Ô không khớp chỉ làm mờ (giữ nguyên hình lưới tầng); tầng / cột / kệ / kho không còn ô khớp thì ẩn
                    var filtering = state !== '' || zoneType !== '*' || q !== '';
                    $root.find('.mz-slot').each(function() {
                        var st = $(this).attr('data-state');
                        var okState = state === '' ||
                            (state === 'empty' && st === 'empty') ||
                            (state === 'stocked' && st !== 'empty') ||
                            (state === 'alert' && (st === 'warn' || st === 'danger')) ||
                            (state === 'compat' && $(this).attr('data-compat') === '1');
                        var okText = q === '' || ($(this).attr('data-search') || '').indexOf(q) >= 0;
                        var okZone = zoneType === '*' || ($(this).attr('data-zone-type') || '') === zoneType;
                        var ok = okState && okText && okZone;

                        $(this).toggleClass('is-dim', !ok).toggleClass('is-hit', ok && filtering);
                    });

                    ['.mz-trow', '.mz-col', '.mz-shelf', '.mz-wh'].forEach(function(sel) {
                        $root.find(sel).each(function() {
                            this.style.display = !filtering || $(this).find('.mz-slot:not(.is-dim)').length ? '' : 'none';
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

                /** Lưu định khu xong (khối Chưa xếp vị trí hoặc nút Đặt hoá chất trên thẻ vị trí): báo kết quả + dựng lại */
                function assigned(res) {
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
                    (res.groups || [res]).forEach(function(group) {
                        (group.import_ids || []).forEach(function(id) {
                            rows.forEach(function(r) { if (String(r.id) === String(id)) $.extend(r, group.location); });
                        });
                    });
                    window.invZoneGrid(selector, opts);
                    $root.find('.mz-filter[data-state].is-active, .mz-zt-filter.is-active').trigger('click');
                }

                /* ---------- Đặt hoá chất vào một vị trí: chọn trong các mã Chưa xếp vị trí ---------- */
                var compatPairs = {};
                if (compat) {
                    Object.keys(compat.matrix || {}).forEach(function(a) {
                        compatPairs[a] = {};
                        (compat.matrix[a] || []).forEach(function(b) { compatPairs[a][b] = true; });
                    });
                }

                /** Hoá chất đang nằm cùng phạm vi (tầng) với vị trí n mà mã r tương kỵ - chỉ cảnh báo, không chặn */
                function clashesAt(n, r) {
                    if (!compat || !r.sw || !r.sw.length) return [];

                    var names = {};
                    values(index).forEach(function(m) {
                        if (m.scopeKey !== n.scopeKey) return;
                        m.items.forEach(function(x) {
                            if (!x.sw || !x.sw.length || (r.category_id && x.category_id === r.category_id)) return;
                            var hit = r.sw.some(function(a) {
                                return x.sw.some(function(b) { return compatPairs[a] && compatPairs[a][b]; });
                            });
                            if (hit) names[x.name || '—'] = (names[x.name || '—'] || []).concat(m.code);
                        });
                    });

                    return Object.keys(names).map(function(name) { return name + ' (' + uniq(names[name]).join(', ') + ')'; });
                }

                function placePickedIds() {
                    return $place.find('.mz-pl-pick:checked').map(function() { return $(this).val(); }).get();
                }

                function syncPlace() {
                    var count = placePickedIds().length;
                    var $visible = $place.find('tbody tr').filter(function() { return this.style.display !== 'none'; });

                    $place.find('.mz-pl-count').text(count);
                    $place.find('.mz-pl-submit').prop('disabled', count === 0);
                    $place.find('.mz-pl-all').prop('checked', $visible.length > 0 && $visible.find('.mz-pl-pick:not(:checked)').length === 0);
                }

                function openPlace(n) {
                    if (!$place.length) return;

                    $place.data('node', n);
                    $place.find('.mz-pl-code').text(n.code);
                    $place.find('.mz-pl-path').text(n.path);
                    $place.find('.mz-pl-lots').text(n.lots);
                    $place.find('.mz-pl-search').val('');
                    $place.find('.mz-pl-error').prop('hidden', true).text('');

                    var $tb = $place.find('tbody').empty();
                    unzoned.forEach(function(r, i) {
                        var clashes = clashesAt(n, r);
                        $tb.append('<tr data-search="' + esc([r.code, r.name, r.sub].join(' ').toLowerCase()) + '"' +
                            (clashes.length ? ' class="is-clash"' : '') + '>' +
                            '<td class="text-center"><input type="checkbox" class="mz-pl-pick" value="' + esc(r.id) + '"></td>' +
                            '<td class="text-center">' + (i + 1) + '</td>' +
                            '<td class="font-weight-bold">' + esc(r.code) + '</td>' +
                            '<td><div class="font-weight-bold">' + esc(r.name || '—') + '</div>' +
                            (r.sub ? '<div class="md-sub small text-muted">' + esc(r.sub) + '</div>' : '') +
                            (clashes.length ? '<div class="mz-pl-clash"><i class="fas fa-exclamation-triangle mr-1"></i>Tương kỵ cùng tầng: ' +
                                esc(clashes.join('; ')) + '</div>' : '') + '</td>' +
                            '<td class="text-right font-weight-bold">' + num(r.remaining) + ' ' + esc(r.unit) + '</td>' +
                            '<td class="text-center md-sub">' + date(r.expired) + '</td></tr>');
                    });
                    syncPlace();

                    $place.modal('show');
                }

                function savePlace() {
                    var n = $place.data('node');
                    var ids = placePickedIds();
                    var max = Number(assign.max) || 200;
                    var $error = $place.find('.mz-pl-error');
                    var $btn = $place.find('.mz-pl-submit');

                    if (!n || !ids.length) {
                        $error.text('Vui lòng chọn ' + codeLabel + ' cần đặt vào vị trí này.').prop('hidden', false);
                        return;
                    }
                    if (ids.length > max) {
                        $error.text('Mỗi lần định khu tối đa ' + max + ' ' + codeLabel + ', vui lòng bỏ bớt mã đã chọn.').prop('hidden', false);
                        return;
                    }

                    $error.prop('hidden', true).text('');
                    $btn.prop('disabled', true).find('i').attr('class', 'fas fa-circle-notch fa-spin mr-1');

                    $.ajax({
                        url: assign.url,
                        method: 'POST',
                        dataType: 'json',
                        data: { _token: assign.token, import_ids: ids, location_id: String(n.key).replace(/^L/, '') }
                    }).done(function(res) {
                        $place.modal('hide');
                        assigned(res);
                    }).fail(function(xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message) ||
                            'Không lưu được định khu, vui lòng tải lại trang rồi thử lại.';
                        $error.text(message).prop('hidden', false);
                    }).always(function() {
                        $btn.prop('disabled', placePickedIds().length === 0).find('i').attr('class', 'fas fa-map-marker-alt mr-1');
                    });
                }

                /* ---------- Vùng chọn nhiều ô (Shift + quét) ---------- */
                var suppressClick = false;

                function pickedSlots() {
                    return $root.find('.mz-slot.is-picked').map(function() { return $(this).attr('data-key'); }).get();
                }

                function syncPickbar() {
                    var keys = pickedSlots();
                    var lots = keys.reduce(function(sum, k) { return sum + (index[k] ? index[k].lots : 0); }, 0);

                    $root.find('.mz-pickbar').prop('hidden', keys.length === 0);
                    $root.find('.mz-pk-cells').text(keys.length);
                    $root.find('.mz-pk-lots').text(lots);
                    $root.toggleClass('has-picked', keys.length > 0);
                }

                function clearPicked() {
                    $root.find('.mz-slot.is-picked').removeClass('is-picked');
                    syncPickbar();
                }

                /** Đóng modal đang mở rồi mới mở modal kế (Bootstrap 4 không chồng 2 modal) */
                function swapModal($from, next) {
                    $from.one('hidden.bs.modal', next).modal('hide');
                }

                /** Ô chọn vị trí đích (Select2 AJAX tra cứu định khu, chip lọc phân loại, cảnh báo tương kỵ) */
                function lookupSelect($select, $parent, categoryIds) {
                    if ($select.hasClass('select2-hidden-accessible')) return;

                    $select.data('lookupParams', function() { return { category_ids: categoryIds() }; });
                    $select.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: $parent,
                        placeholder: '-- Chọn vị trí lưu trữ --',
                        ajax: window.wmsLookupAjax($select),
                        templateResult: function(row) {
                            if (!row.id || !row.warning) return window.wmsZoneTemplate(row);

                            return $('<div class="mz-opt-warn">').append(
                                $('<div>').append(window.wmsZoneTemplate(row)),
                                $('<small>').text(row.warning)
                            );
                        }
                    });
                }

                function syncDetail() {
                    var count = $modal.find('.mz-d-pick:checked').length;
                    var total = $modal.find('.mz-d-pick').length;

                    $modal.find('.mz-d-move').prop('disabled', count === 0).find('b').text(count);
                    $modal.find('.mz-d-all').prop('checked', total > 0 && count === total);
                }

                /**
                 * Kế hoạch "Dời nguyên khối": mỗi ô nguồn dời theo cùng độ lệch (cột, tầng, ô ngang, dãy sâu) như từ ô
                 * neo (ô đang kéo) tới ô đích, trên kệ của ô đích. Trả {dest: {key: ô đích}} hoặc {error: lý do}.
                 */
                function blockPlan(sources, anchor, target) {
                    var a = anchor.grid, t = target.grid, dest = {}, bad = [];
                    if (!a || !t) return { error: 'vị trí chưa có toạ độ trên lưới' };

                    sources.forEach(function(n) {
                        var g = n.grid;
                        var to = g && gridAt[gridKey(t.s, t.ci + g.ci - a.ci, t.ti + g.ti - a.ti, t.p + g.p - a.p, t.d + g.d - a.d)];
                        if (!to) bad.push(n.code + ' → (ngoài kệ)');
                        else if (!canEdit(to)) bad.push(n.code + ' → ' + to.code + ' (khu không nhận hàng)');
                        else dest[n.key] = to;
                    });

                    return bad.length ? { error: 'không đủ chỗ: ' + bad.slice(0, 3).join('; ') + (bad.length > 3 ? '...' : '') } : { dest: dest };
                }

                /** Vẽ lại cột "Vị trí mới" + ẩn / hiện ô chọn vị trí theo kiểu dời đang chọn */
                function syncMoveMode() {
                    var st = $move.data('state');
                    if (!st) return;

                    var mode = $move.find('input[type="radio"]:checked').val() || 'merge';
                    var targetCode = $move.find('.mz-mv-location option:selected').text();
                    var hasTarget = !!$move.find('.mz-mv-location').val();

                    st.mode = mode;
                    $move.find('.mz-mv-target').prop('hidden', mode === 'block');
                    $move.find('tbody tr').each(function(i) {
                        var row = st.rows[i];
                        var to = mode === 'block' ? st.plan.dest[row.from.key] : null;
                        $(this).find('.mz-mv-to').html(mode === 'block' ? '<b>' + esc(to.code) + '</b>' :
                            (hasTarget ? '<b>' + esc(targetCode) + '</b>' : '<span class="text-muted">—</span>'));
                    });
                }

                /**
                 * Mở modal Đổi vị trí. sources = các ô nguồn (mỗi ô [{node, ids?}], ids bỏ trống = mọi mã trong ô);
                 * target = ô đích đã thả (kéo thả) hoặc null; anchor = ô đang kéo khi kéo cả vùng chọn (để dời nguyên khối).
                 */
                function openMove(sources, target, anchor) {
                    if (!$move.length || !assign.moveUrl) return;

                    var rows = [];
                    sources.forEach(function(src) {
                        var want = null;
                        if (src.ids) {
                            want = {};
                            src.ids.forEach(function(id) { want[String(id)] = true; });
                        }
                        src.node.items.forEach(function(r) {
                            if (!want || want[String(r.id)]) rows.push({ r: r, from: src.node });
                        });
                    });

                    var nodes = uniq(sources.map(function(s) { return s.node; }));
                    var plan = target && anchor && nodes.length > 1 ? blockPlan(nodes, anchor, target) : null;
                    // Gộp: mã đang nằm sẵn ở ô đích thì không cần đổi
                    var mergeRows = target ? rows.filter(function(x) { return x.from !== target; }) : rows;
                    if (!mergeRows.length && !(plan && plan.dest)) return;

                    var categories = {};
                    var $tb = $move.find('tbody').empty();
                    var st = { rows: plan && plan.dest ? rows : mergeRows, plan: plan, mode: 'merge' };

                    st.rows.forEach(function(x, i) {
                        var r = x.r;
                        categories[r.category_id ? 'c' + r.category_id : 'n' + r.name] = r.category_id || null;
                        $tb.append('<tr><td class="text-center">' + (i + 1) + '</td>' +
                            '<td class="font-weight-bold">' + esc(r.code) + '</td>' +
                            '<td><div class="font-weight-bold">' + esc(r.name || '—') + '</div>' +
                            (r.sub ? '<div class="md-sub small text-muted">' + esc(r.sub) + '</div>' : '') + '</td>' +
                            '<td class="text-right font-weight-bold">' + num(r.remaining) + ' ' + esc(r.unit) + '</td>' +
                            '<td>' + esc(x.from.code) + '</td><td class="mz-mv-to"></td></tr>');
                    });

                    $move.data('state', st);
                    $move.data('categoryIds', values(categories).filter(Boolean));
                    $move.find('.mz-mv-from').text(nodes.length === 1 ? nodes[0].code : nodes.length + ' ô vị trí');
                    $move.find('.mz-mv-count').text(st.rows.length);
                    $move.find('.mz-mv-reason').val('');
                    $move.find('.mz-mv-error').prop('hidden', true).text('');

                    // Nhiều ô + có ô đích: cho chọn Dời nguyên khối (mặc định nếu đủ chỗ) hoặc Gộp vào ô đích
                    var $modes = $move.find('.mz-mv-modes').prop('hidden', !plan);
                    if (plan) {
                        var ok = !!plan.dest;
                        $modes.find('input[value="block"]').prop('disabled', !ok).prop('checked', ok);
                        $modes.find('input[value="merge"]').prop('checked', !ok);
                        $modes.find('.mz-mv-mode-note').text(ok ? '- mỗi ô sang ô tương ứng, giữ nguyên bố cục' : '- ' + plan.error);
                        $modes.find('.mz-mv-merge-note').text('- mọi mã vào ' + target.code);
                        if (!ok) st.rows = mergeRows;
                    } else {
                        $modes.find('input').prop('checked', false);
                    }

                    var $select = $move.find('.mz-mv-location');
                    $select.val(null).find('option[value!=""]').remove();
                    if (target) {
                        $select.append($('<option>', { value: target.id, text: target.code, selected: true })
                            .attr('data-zone', target.zoneType || ''));
                    }
                    $select.trigger('change');
                    syncMoveMode();

                    $move.modal('show');
                }

                function saveMove() {
                    var st = $move.data('state') || { rows: [] };
                    var block = st.mode === 'block' && st.plan && st.plan.dest;
                    var locationId = $move.find('.mz-mv-location').val();
                    var reason = String($move.find('.mz-mv-reason').val() || '').trim();
                    var $error = $move.find('.mz-mv-error');
                    var $btn = $move.find('.mz-mv-submit');
                    var data = { _token: assign.token, import_ids: st.rows.map(function(x) { return x.r.id; }), reason: reason };

                    if (block) {
                        data.location_ids = st.rows.map(function(x) { return st.plan.dest[x.from.key].id; });
                    } else {
                        if (!locationId) return $error.text('Vui lòng chọn vị trí mới.').prop('hidden', false);
                        // Gộp: bỏ các mã đang nằm sẵn ở vị trí mới
                        var keep = st.rows.filter(function(x) { return String(x.from.id) !== String(locationId); });
                        if (!keep.length) return $error.text('Các mã đã nằm ở vị trí này.').prop('hidden', false);
                        data.import_ids = keep.map(function(x) { return x.r.id; });
                        data.location_id = locationId;
                    }
                    if (!reason) return $error.text('Vui lòng nhập lý do đổi vị trí.').prop('hidden', false);

                    $error.prop('hidden', true).text('');
                    $btn.prop('disabled', true).find('i').attr('class', 'fas fa-circle-notch fa-spin mr-1');

                    $.ajax({
                        url: assign.moveUrl,
                        method: 'POST',
                        dataType: 'json',
                        data: data
                    }).done(function(res) {
                        $move.modal('hide');
                        assigned(res);
                    }).fail(function(xhr) {
                        $error.text((xhr.responseJSON && xhr.responseJSON.message) ||
                            'Không đổi được vị trí, vui lòng tải lại trang rồi thử lại.').prop('hidden', false);
                    }).always(function() {
                        $btn.prop('disabled', false).find('i').attr('class', 'fas fa-exchange-alt mr-1');
                    });
                }

                /** Thả thẻ Chưa xếp vị trí (hoặc các thẻ đang chọn) vào một ô: hỏi lại rồi định khu luôn */
                function dropUnzoned(ids, target) {
                    var list = ids.map(function(id) { return unzonedById[id]; }).filter(Boolean);
                    if (!list.length) return;

                    var max = Number(assign.max) || 200;
                    var clashes = [];
                    list.forEach(function(r) {
                        clashesAt(target, r).forEach(function(c) { clashes.push(r.code + ' ✕ ' + c); });
                    });
                    var label = list.length === 1 ? codeLabel + ' ' + list[0].code : list.length + ' ' + codeLabel;
                    var send = function() {
                        $.ajax({
                            url: assign.url,
                            method: 'POST',
                            dataType: 'json',
                            data: { _token: assign.token, import_ids: list.map(function(r) { return r.id; }), location_id: target.id }
                        }).done(assigned).fail(function(xhr) {
                            var text = (xhr.responseJSON && xhr.responseJSON.message) || 'Không lưu được định khu, vui lòng tải lại trang rồi thử lại.';
                            return window.Swal ? Swal.fire({ icon: 'error', title: 'Không lưu được', text: text, confirmButtonColor: '#2E7BC4' }) :
                                window.alert(text);
                        });
                    };

                    if (list.length > max) {
                        var tooMany = 'Mỗi lần định khu tối đa ' + max + ' ' + codeLabel + ', vui lòng bỏ bớt mã đã chọn.';
                        return window.Swal ? Swal.fire({ icon: 'info', title: 'Chọn quá nhiều', text: tooMany, confirmButtonColor: '#2E7BC4' }) :
                            window.alert(tooMany);
                    }
                    if (!window.Swal) {
                        if (window.confirm('Định khu ' + label + ' vào ' + target.code + '?')) send();
                        return;
                    }

                    Swal.fire({
                        icon: clashes.length ? 'warning' : 'question',
                        title: 'Định khu vào ' + target.code + '?',
                        html: 'Xếp ' + esc(label) + ' vào vị trí <b>' + esc(target.code) + '</b>.' +
                            (clashes.length ? '<div class="text-left text-danger mt-2 small"><b>Tương kỵ cùng tầng:</b><br>' +
                                clashes.map(esc).join('<br>') + '</div>' : ''),
                        showCancelButton: true,
                        confirmButtonText: 'Định khu',
                        cancelButtonText: 'Huỷ',
                        confirmButtonColor: '#2E7BC4'
                    }).then(function(r) { if (r.isConfirmed || r.value) send(); });
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
                        assigned(res);
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

                    /* ----- Hộp chi tiết vị trí: [Thêm hoá chất] + chọn mã -> [Đổi vị trí] ----- */
                    $modal.off('.mz');
                    $modal.on('click.mz', '.mz-d-add', function() {
                        var n = $modal.data('node');
                        if (n) swapModal($modal, function() { openPlace(n); });
                    });
                    $modal.on('click.mz', '.mz-d-move', function() {
                        var n = $modal.data('node');
                        var ids = $modal.find('.mz-d-pick:checked').map(function() { return $(this).val(); }).get();
                        if (n && ids.length) swapModal($modal, function() { openMove([{ node: n, ids: ids }], null, null); });
                    });
                    $modal.on('change.mz', '.mz-d-pick', syncDetail);
                    $modal.on('change.mz', '.mz-d-all', function() {
                        $modal.find('.mz-d-pick').prop('checked', this.checked);
                        syncDetail();
                    });

                    /* ----- Modal Đổi vị trí ----- */
                    $move.off('.mz');
                    $move.on('click.mz', '.mz-mv-submit', saveMove);
                    $move.on('change.mz input.mz', '.mz-mv-location, .mz-mv-reason', function() {
                        $move.find('.mz-mv-error').prop('hidden', true).text('');
                    });
                    $move.on('change.mz', '.mz-mv-location, input[type="radio"]', syncMoveMode);
                    $move.on('shown.bs.modal.mz', function() {
                        var $select = $move.find('.mz-mv-location');
                        lookupSelect($select, $move, function() { return $move.data('categoryIds') || []; });
                        if (!$select.val()) $select.select2('open');
                        else $move.find('.mz-mv-reason').trigger('focus');
                    });

                    /* ----- Kéo thả: thẻ Chưa xếp vị trí -> ô (định khu); ô có hàng -> ô khác (đổi vị trí) ----- */
                    var dragging = null;
                    $root.on('dragstart.mz', '.mz-ucard[draggable], .mz-slot[draggable]', function(e) {
                        var $el = $(this);
                        if ($el.hasClass('mz-ucard')) {
                            var ids = $el.hasClass('is-picked') ? pickedIds() : [$el.attr('data-id')];
                            dragging = { kind: 'unzoned', ids: ids };
                        } else if ($el.hasClass('is-picked') && pickedSlots().length > 1) {
                            // Kéo một ô trong vùng chọn = kéo cả vùng (ô này làm neo để dời nguyên khối)
                            dragging = { kind: 'block', keys: pickedSlots(), key: $el.attr('data-key') };
                        } else {
                            dragging = { kind: 'move', key: $el.attr('data-key') };
                        }
                        $root.addClass('is-dragging');
                        try { e.originalEvent.dataTransfer.setData('text/plain', $el.attr('data-id') || $el.attr('data-key')); } catch (x) {}
                        e.originalEvent.dataTransfer.effectAllowed = 'move';
                    });
                    $root.on('dragend.mz', function() {
                        dragging = null;
                        $root.removeClass('is-dragging').find('.mz-slot.is-over').removeClass('is-over');
                    });
                    $root.on('dragover.mz', '.mz-slot.is-drop', function(e) {
                        if (!dragging || (dragging.kind === 'move' && dragging.key === $(this).attr('data-key'))) return;
                        e.preventDefault();
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                        $(this).addClass('is-over');
                    });
                    $root.on('dragleave.mz', '.mz-slot', function() { $(this).removeClass('is-over'); });
                    $root.on('drop.mz', '.mz-slot.is-drop', function(e) {
                        e.preventDefault();
                        var drag = dragging;
                        var target = index[$(this).attr('data-key')];
                        $root.trigger('dragend.mz');
                        if (!drag || !target) return;

                        if (drag.kind === 'move') {
                            var from = index[drag.key];
                            if (from && from !== target) openMove([{ node: from }], target, null);
                            return;
                        }
                        if (drag.kind === 'block') {
                            openMove(drag.keys.map(function(k) { return { node: index[k] }; }), target, index[drag.key]);
                            return;
                        }

                        dropUnzoned(drag.ids, target);
                    });

                    /* ----- Vùng chọn nhiều ô: Shift (Ctrl) + bấm, hoặc Shift + kéo quét; Esc bỏ chọn ----- */
                    if (assign.moveUrl) {
                        $root.on('click.mz', '.mz-pk-clear', clearPicked);
                        $root.on('click.mz', '.mz-pk-move', function() {
                            var keys = pickedSlots();
                            if (keys.length) openMove(keys.map(function(k) { return { node: index[k] }; }), null, null);
                        });
                        $(document).off('keydown.mzpick').on('keydown.mzpick', function(e) {
                            if (e.key === 'Escape' && pickedSlots().length && !$('.modal.show').length) clearPicked();
                        });

                        // Shift + giữ chuột kéo trên vùng sơ đồ = khung quét; thả ra chọn thêm mọi ô có hàng nằm trong khung
                        $root.on('mousedown.mz', '.mz-wh-body', function(e) {
                            if (!e.shiftKey || e.button !== 0) return;
                            e.preventDefault();

                            var x0 = e.clientX, y0 = e.clientY, moved = false;
                            var $box = $('<div class="mz-marquee">').appendTo(document.body);
                            var rect = function(ev) {
                                return {
                                    left: Math.min(x0, ev.clientX), top: Math.min(y0, ev.clientY),
                                    right: Math.max(x0, ev.clientX), bottom: Math.max(y0, ev.clientY)
                                };
                            };

                            $(document).on('mousemove.mzmarquee', function(ev) {
                                var r = rect(ev);
                                moved = moved || (r.right - r.left > 4 || r.bottom - r.top > 4);
                                $box.css({ left: r.left, top: r.top, width: r.right - r.left, height: r.bottom - r.top });
                            }).on('mouseup.mzmarquee', function(ev) {
                                $(document).off('.mzmarquee');
                                $box.remove();
                                if (!moved) return;   // Shift + bấm (không kéo) do click xử lý

                                var r = rect(ev);
                                $root.find('.mz-slot.is-pickable').each(function() {
                                    var b = this.getBoundingClientRect();
                                    if (b.right >= r.left && b.left <= r.right && b.bottom >= r.top && b.top <= r.bottom) {
                                        $(this).addClass('is-picked');
                                    }
                                });
                                // Chặn click sinh ra ngay sau khi thả chuột
                                suppressClick = true;
                                setTimeout(function() { suppressClick = false; }, 0);
                                syncPickbar();
                            });
                        });
                    }

                    $place.off('.mz');
                    $place.on('click.mz', '.mz-pl-submit', savePlace);
                    $place.on('change.mz', '.mz-pl-pick', syncPlace);
                    // Bấm cả dòng = chọn / bỏ chọn
                    $place.on('click.mz', 'tbody tr', function(e) {
                        if ($(e.target).is('.mz-pl-pick')) return;
                        var $cb = $(this).find('.mz-pl-pick');
                        $cb.prop('checked', !$cb.prop('checked'));
                        syncPlace();
                    });
                    $place.on('change.mz', '.mz-pl-all', function() {
                        var on = this.checked;
                        $place.find('tbody tr').filter(function() { return this.style.display !== 'none'; })
                            .find('.mz-pl-pick').prop('checked', on);
                        syncPlace();
                    });
                    $place.on('input.mz', '.mz-pl-search', function() {
                        var q = ($(this).val() || '').toString().trim().toLowerCase();
                        $place.find('tbody tr').each(function() {
                            this.style.display = q === '' || ($(this).attr('data-search') || '').indexOf(q) >= 0 ? '' : 'none';
                        });
                        syncPlace();
                    });
                    $place.on('shown.bs.modal.mz', function() {
                        $place.find('.mz-pl-search').trigger('focus');
                    });
                }

                // Ô vị trí là <span role="button">: Enter / Space cũng mở như bấm chuột
                $root.on('keydown.mz', '.mz-slot', function(e) {
                    if (e.key !== 'Enter' && e.key !== ' ') return;
                    e.preventDefault();
                    $(this).trigger('click');
                });

                // Bấm một ô vị trí -> xem đủ các mã đang nằm ở đó; có quyền thì Thêm hoá chất / Đổi vị trí ngay trong hộp.
                // Shift / Ctrl + bấm = chọn / bỏ chọn ô (để dời nhiều ô cùng lúc)
                $root.on('click.mz', '.mz-slot', function(e) {
                    if (suppressClick) return;
                    if ((e.shiftKey || e.ctrlKey || e.metaKey) && $(this).hasClass('is-pickable')) {
                        $(this).toggleClass('is-picked');
                        syncPickbar();
                        return;
                    }

                    var n = index[$(this).attr('data-key')];
                    if (!n) return;
                    // Ô trống mà không có quyền thêm thì không có gì để xem
                    if (!n.lots && !(assign && canEdit(n))) return;

                    var editable = !!assign && canEdit(n);
                    $modal.data('node', n);
                    $modal.find('.mz-d-name, .mz-d-code').text(n.code);
                    $modal.find('.mz-d-path').text(n.path);
                    $modal.find('.mz-d-lots').text(n.lots);
                    $modal.find('.mz-d-items').text(n.cats.length);

                    var clash = n.conflicts && n.conflicts.length > 0;
                    var $compat = $modal.find('.mz-d-compat').empty().prop('hidden', !clash);
                    if (clash) $compat.html(conflictConclusion(n, compat.scope_label));

                    var $tb = $modal.find('tbody').empty();
                    $modal.find('.mz-d-pickcol').toggle(editable);
                    $modal.find('.mz-d-all').prop('checked', false);
                    if (!n.items.length) {
                        $tb.append('<tr><td colspan="7" class="text-center text-muted font-italic">Vị trí đang trống</td></tr>');
                    }
                    n.items.forEach(function(r, i) {
                        $tb.append('<tr>' + (editable ? '<td class="text-center"><input type="checkbox" class="mz-d-pick" value="' +
                            esc(r.id) + '" title="Chọn để đổi vị trí"></td>' : '') + '<td class="text-center">' + (i + 1) + '</td>' +
                            '<td class="font-weight-bold">' + esc(r.code) + '</td>' +
                            '<td><div class="font-weight-bold">' + esc(r.name || '—') + '</div>' +
                            (r.sub ? '<div class="md-sub small text-muted">' + esc(r.sub) + '</div>' : '') + '</td>' +
                            '<td class="text-right font-weight-bold">' + num(r.remaining) + ' ' + esc(r.unit) + '</td>' +
                            '<td class="text-center md-sub">' + date(r.expired) + '</td>' +
                            '<td class="text-center"><span class="inv-badge inv-badge-' + esc(r.state) + '">' +
                            esc(r.state_label) + '</span></td></tr>');
                    });

                    // Nút ở chân hộp: chỉ hiện khi có quyền và vị trí không thuộc khu Biệt trữ / Cách ly / Loại bỏ
                    $modal.find('.mz-d-actions').toggle(editable);
                    $modal.find('.mz-d-add').prop('disabled', !unzoned.length)
                        .attr('title', unzoned.length ? 'Chọn ' + codeLabel + ' chưa xếp vị trí để thêm vào ' + n.code :
                            'Không còn ' + codeLabel + ' nào chưa xếp vị trí');
                    syncDetail();
                    $modal.modal('show');
                });

                syncPickbar();
                apply();

                // Nút Thẻ | 3D: dựng kệ 3D từ cùng khung định khu và các ô vừa gom ở trên
                if (window.invZone3d) {
                    window.invZone3d.init($root[0], {
                        zones: { warehouses: warehouses, shelves: shelves, columns: columns, tiers: tiers, locations: locations, foreign: zones.foreign || {} },
                        nodes: index,
                        compat: compat
                    });
                }
            };
        })();
    </script>
@endonce
