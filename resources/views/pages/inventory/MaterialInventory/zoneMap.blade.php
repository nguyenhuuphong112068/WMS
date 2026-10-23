{{--
|--------------------------------------------------------------------------
| TỒN - TỒN KHO VẬT TƯ THEO VỊ TRÍ (sơ đồ thẻ)
|--------------------------------------------------------------------------
| Vẽ đúng cây định khu Kho/Phòng -> Kệ/Tủ -> Cột -> Tầng -> Vị trí. Mỗi vị trí là một ô
| trên lưới, tô màu theo tình trạng hàng đang đứng ở đó:
|   xám (trống) - xanh (bình thường) - vàng (cần chú ý) - đỏ (hết hạn / âm kho)
| Bấm vào ô mở modal xem đủ các mã xuất nhập đang nằm tại vị trí đó.
|
| Dữ liệu do MaterialInventoryController::zoneMap() dựng sẵn, view chỉ vẽ.
--}}

@include('pages.inventory.shared.zoneMapStyles')

@php
    $mzTotals = $zoneMap['totals'];
@endphp

<div class="mz-head">
    <div class="mz-stats">
        <div class="mz-stat"><b>{{ $mzTotals['warehouses'] }}</b><span>Kho / Phòng</span></div>
        <div class="mz-stat"><b>{{ $mzTotals['shelves'] }}</b><span>Kệ / Tủ</span></div>
        <div class="mz-stat"><b>{{ $mzTotals['columns'] }}</b><span>Cột</span></div>
        <div class="mz-stat"><b>{{ $mzTotals['tiers'] }}</b><span>Tầng</span></div>
        <div class="mz-stat"><b>{{ $mzTotals['filled'] }}/{{ $mzTotals['locations'] }}</b><span>Vị trí có hàng</span></div>
        <div class="mz-stat"><b>{{ $mzTotals['lots'] }}</b><span>Mã xuất nhập</span></div>
        <div class="mz-stat"><b>{{ $mzTotals['materials'] }}</b><span>Vật tư</span></div>
        @if ($mzTotals['alerts'] > 0)
            <div class="mz-stat is-alert"><b>{{ $mzTotals['alerts'] }}</b><span>Cần chú ý</span></div>
        @endif
    </div>

    <div class="mz-tools">
        <div class="mz-search">
            <i class="fas fa-search"></i>
            <input type="text" id="mzSearch" class="form-control form-control-sm"
                placeholder="Tìm vị trí, kệ/tủ hoặc tên vật tư...">
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="mzToggleAll">
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
    <button type="button" class="mz-filter mz-zt-filter" data-zone-type=""><span class="dot empty"></span>Chưa phân loại</button>
</div>

{{-- Mã còn tồn nhưng chưa gán vị trí - để trên cùng cho dễ thấy mà bổ sung định khu --}}
@if (count($zoneMap['unzoned']) > 0)
    <div class="mz-unzoned">
        <div class="mz-unzoned-head">
            <i class="fas fa-exclamation-triangle"></i> Chưa xếp vị trí
            <span class="mz-pill is-alert">{{ count($zoneMap['unzoned']) }} mã xuất nhập</span>
        </div>
        <div class="mz-grid">
            @foreach ($zoneMap['unzoned'] as $item)
                <div class="mz-cell is-warn">
                    <div class="mz-cell-top">
                        <span class="mz-cell-code">{{ $item['code'] }}</span>
                        <span class="mz-cell-lots">{{ $item['remaining'] }} {{ $item['unit'] }}</span>
                    </div>
                    <div class="mz-cell-name">{{ $item['material_name'] }}</div>
                    <div class="mz-cell-foot">Hạn dùng {{ $item['expired_date'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@forelse ($zoneMap['warehouses'] as $wh)
    <section class="mz-wh">
        <div class="mz-wh-head">
            <i class="fas fa-chevron-down mz-caret"></i>
            <span class="ic"><i class="fas fa-warehouse"></i></span>
            <div>
                <div class="mz-wh-title">{{ $wh['name'] }}</div>
                <div class="mz-wh-sub">
                    {{ $wh['code'] ? $wh['code'] . ' · ' : '' }}{{ $wh['stat']['shelves'] }} kệ/tủ ·
                    {{ $wh['stat']['filled'] }}/{{ $wh['stat']['locations'] }} vị trí có hàng
                </div>
            </div>
            <div class="mz-wh-meta">
                <span class="mz-pill">{{ $wh['stat']['lots'] }} mã</span>
                <span class="mz-pill">{{ $wh['stat']['materials'] }} vật tư</span>
                @if ($wh['stat']['alerts'] > 0)
                    <span class="mz-pill is-alert">{{ $wh['stat']['alerts'] }} cần chú ý</span>
                @endif
            </div>
        </div>

        <div class="mz-wh-body">
            @foreach ($wh['shelves'] as $shelf)
                <div class="mz-shelf">
                    <div class="mz-shelf-head">
                        <i class="fas fa-layer-group"></i> {{ $shelf['name'] }}
                        @if ($shelf['code'])
                            <span class="tag">{{ $shelf['code'] }}</span>
                        @endif
                        <span class="tag">{{ $shelf['stat']['columns'] }} cột</span>
                        <span class="tag">{{ $shelf['stat']['lots'] }} mã</span>
                    </div>

                    @foreach ($shelf['columns'] as $column)
                        <div class="mz-column">
                            <div class="mz-column-head">
                                <i class="fas fa-grip-lines-vertical" style="color: var(--primary-light)"></i>
                                {{ $column['name'] }}
                                @if ($column['code'])
                                    <span class="md-tag">{{ $column['code'] }}</span>
                                @endif
                                <span class="sub">
                                    {{ $column['stat']['tiers'] }} tầng ·
                                    {{ $column['stat']['filled'] }}/{{ $column['stat']['locations'] }} vị trí có hàng ·
                                    {{ $column['stat']['lots'] }} mã
                                </span>
                            </div>

                            @foreach ($column['tiers'] as $tier)
                                <div class="mz-tier">
                                    <div class="mz-tier-head">
                                        <i class="fas fa-bars"></i> {{ $tier['name'] }}
                                        @if ($tier['code'])
                                            <span class="tag">{{ $tier['code'] }}</span>
                                        @endif
                                        <span class="sub">
                                            {{ $tier['stat']['filled'] }}/{{ $tier['stat']['locations'] }} vị trí có
                                            hàng · {{ $tier['stat']['lots'] }} mã
                                        </span>
                                    </div>

                                    <div class="mz-grid">
                                        @foreach ($tier['locations'] as $loc)
                                            @php
                                                $mzSearch = mb_strtolower(
                                                    trim(
                                                        $loc['code'] .
                                                            ' ' .
                                                            $shelf['name'] .
                                                            ' ' .
                                                            $column['name'] .
                                                            ' ' .
                                                            $tier['name'] .
                                                            ' ' .
                                                            implode(' ', array_column($loc['preview'], 'name')),
                                                    ),
                                                );
                                                $mzZoned = $loc['zoneBadge']['bg'] !== '#FFFFFF';
                                            @endphp
                                            <button type="button"
                                                class="mz-cell is-{{ $loc['state'] }} {{ $mzZoned ? 'is-zoned' : '' }}"
                                                data-key="{{ $loc['key'] }}" data-state="{{ $loc['state'] }}"
                                                data-zone-type="{{ $loc['zoneType'] }}"
                                                data-search="{{ $mzSearch }}"
                                                style="--chip: {{ $loc['zoneBadge']['bg'] }}; --chip-text: {{ $loc['zoneBadge']['text'] }}"
                                                {{ $loc['state'] === 'empty' ? 'disabled' : '' }}>
                                                <div class="mz-cell-top">
                                                    <span class="mz-cell-code">{{ $loc['code'] }}</span>
                                                    <span class="mz-cell-lots">{{ $loc['stat']['lots'] }}</span>
                                                </div>
                                                <div class="mz-cell-items">
                                                    @forelse ($loc['preview'] as $item)
                                                        <div>
                                                            <span class="nm">· {{ $item['name'] }}</span>
                                                            <span class="qty">{{ $item['amount'] }}
                                                                <span class="u">{{ $item['unit'] }}</span></span>
                                                        </div>
                                                    @empty
                                                        <span class="mz-cell-empty-note">Đang trống</span>
                                                    @endforelse
                                                    @if ($loc['stat']['materials'] > count($loc['preview']))
                                                        <div><span class="nm">·
                                                                +{{ $loc['stat']['materials'] - count($loc['preview']) }}
                                                                vật tư khác</span></div>
                                                    @endif
                                                </div>
                                                <div class="mz-cell-foot">
                                                    {{ $loc['stat']['materials'] }} vật tư
                                                    @if ($loc['stat']['alerts'] > 0)
                                                        · <span class="alert">{{ $loc['stat']['alerts'] }} cần
                                                            chú ý</span>
                                                    @endif
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>
@empty
    <div class="mz-empty">
        <i class="fas fa-map-marked-alt"></i>
        Chưa khai báo định khu cho phòng ban này. Vào <b>Dữ Liệu Gốc → Định Khu</b> để tạo Kho-Phòng / Kệ-Tủ / Cột / Tầng /
    Vị trí,
        sau đó xếp vị trí cho từng mã nhập.
    </div>
@endforelse

<div class="mz-empty mz-noresult">
    <i class="fas fa-search"></i>
    Không có vị trí nào khớp với điều kiện đang lọc.
</div>
