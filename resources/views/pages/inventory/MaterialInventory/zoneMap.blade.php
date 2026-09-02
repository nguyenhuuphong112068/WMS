{{--
|--------------------------------------------------------------------------
| TỒN - TỒN KHO VẬT TƯ THEO VỊ TRÍ (sơ đồ thẻ)
|--------------------------------------------------------------------------
| Vẽ đúng cây định khu Kho -> Phòng -> Kệ/Tủ -> Vị trí. Mỗi vị trí là một ô
| trên lưới, tô màu theo tình trạng hàng đang đứng ở đó:
|   xám (trống) - xanh (bình thường) - vàng (cần chú ý) - đỏ (hết hạn / âm kho)
| Bấm vào ô mở modal xem đủ các mã xuất nhập đang nằm tại vị trí đó.
|
| Dữ liệu do MaterialInventoryController::zoneMap() dựng sẵn, view chỉ vẽ.
--}}

<style>
    /* ---------- Thanh tổng quan + bộ lọc ---------- */
    .mz-head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .mz-stats { display: flex; flex-wrap: wrap; gap: 10px; }
    .mz-stat {
        background: var(--primary-soft); border: 1px solid #DCE7F3; border-radius: var(--border-radius-md);
        padding: 8px 16px; min-width: 92px;
    }
    .mz-stat b { display: block; font-size: 1.3rem; font-weight: 700; color: var(--primary); line-height: 1.15; }
    .mz-stat span { font-size: .68rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #64748B; }
    .mz-stat.is-alert { background: #FFF7ED; border-color: #FED7AA; }
    .mz-stat.is-alert b { color: #C2410C; }

    .mz-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .mz-search { position: relative; }
    .mz-search input { padding-left: 32px; min-width: 240px; border-radius: var(--border-radius-md); }
    .mz-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--primary-lighter); }

    .mz-hint {
        background: var(--primary-soft); border-left: 3px solid var(--primary-light);
        border-radius: var(--border-radius-md); padding: 9px 14px; margin-bottom: 12px;
        font-size: .8rem; color: var(--text-main);
    }
    .mz-filters { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
    .mz-filter {
        border: 1px solid #DCE7F3; background: #fff; border-radius: 999px; padding: 5px 14px;
        font-size: .78rem; font-weight: 600; color: var(--text-main); cursor: pointer; transition: all .2s;
    }
    .mz-filter:hover { border-color: var(--primary-light); }
    .mz-filter.is-active { background: var(--primary); border-color: var(--primary); color: #fff; }
    .mz-filter .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
    .mz-filter .dot.ok { background: #16A34A; }
    .mz-filter .dot.warn { background: #F59E0B; }
    .mz-filter .dot.danger { background: #DC2626; }
    .mz-filter .dot.empty { background: #CBD5E1; }

    /* ---------- Kho ---------- */
    .mz-wh {
        background: #fff; border: 1px solid #DCE7F3; border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 16px;
    }
    .mz-wh-head {
        display: flex; align-items: center; gap: 12px; padding: 13px 18px; cursor: pointer;
        background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff;
    }
    .mz-wh-head .ic {
        width: 34px; height: 34px; flex: none; border-radius: 10px; background: rgba(255, 255, 255, .18);
        display: flex; align-items: center; justify-content: center;
    }
    .mz-wh-title { font-weight: 700; letter-spacing: .4px; }
    .mz-wh-sub { font-size: .72rem; opacity: .88; }
    .mz-wh-meta { margin-left: auto; display: flex; align-items: center; gap: 6px; }
    .mz-pill { background: rgba(255, 255, 255, .2); border-radius: 999px; padding: 3px 11px; font-size: .72rem; font-weight: 700; white-space: nowrap; }
    .mz-pill.is-alert { background: #FEF3C7; color: #92400E; }
    .mz-caret { transition: transform .25s; }
    .mz-wh.is-closed .mz-caret { transform: rotate(-90deg); }
    .mz-wh.is-closed .mz-wh-body { display: none; }
    .mz-wh-body { padding: 16px 18px 4px; }

    /* ---------- Phòng ---------- */
    .mz-room { border-left: 3px solid var(--primary-lighter); padding-left: 14px; margin-bottom: 16px; }
    .mz-room-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; color: var(--primary-dark); font-weight: 700; }
    .mz-room-head .tag { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 2px 10px; font-size: .7rem; font-weight: 700; }

    /* ---------- Kệ / Tủ ---------- */
    .mz-shelf { background: var(--bg-neutral); border: 1px solid #E6EEF7; border-radius: var(--border-radius-md); padding: 12px 14px; margin-bottom: 12px; }
    .mz-shelf-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: .88rem; font-weight: 700; color: var(--text-main); }
    .mz-shelf-head .sub { margin-left: auto; font-size: .72rem; font-weight: 600; color: #64748B; }

    /* ---------- Ô vị trí ---------- */
    .mz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(196px, 1fr)); gap: 10px; }
    .mz-cell {
        display: block; width: 100%; text-align: left; background: #fff; padding: 10px 12px;
        border: 1px solid #DCE7F3; border-top: 3px solid #CBD5E1; border-radius: 10px; transition: all .2s;
    }
    .mz-cell:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
    .mz-cell.is-ok { border-top-color: #16A34A; }
    .mz-cell.is-warn { border-top-color: #F59E0B; background: #FFFCF5; }
    .mz-cell.is-danger { border-top-color: #DC2626; background: #FFF9F9; }
    .mz-cell.is-empty { border-style: dashed; border-top-style: solid; background: #FBFDFF; cursor: default; }
    .mz-cell.is-empty:hover { transform: none; box-shadow: none; border-color: #DCE7F3; }

    .mz-cell-top { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
    .mz-cell-code { font-weight: 700; color: var(--primary-dark); font-size: .84rem; }
    .mz-cell-lots {
        margin-left: auto; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 700; background: var(--primary-soft); color: var(--primary);
    }
    .mz-cell.is-warn .mz-cell-lots { background: #FEF3C7; color: #92400E; }
    .mz-cell.is-danger .mz-cell-lots { background: #FEE2E2; color: #991B1B; }
    .mz-cell.is-empty .mz-cell-lots { background: #EEF2F6; color: #94A3B8; }
    .mz-cell-name { font-size: .78rem; color: var(--text-main); font-weight: 600; margin-bottom: 6px; }
    .mz-cell-items { font-size: .73rem; color: #64748B; line-height: 1.45; min-height: 32px; }
    .mz-cell-items > div { display: flex; align-items: baseline; gap: 8px; }
    .mz-cell-items .nm { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mz-cell-items .qty { margin-left: auto; flex: none; font-weight: 700; color: var(--primary-dark); }
    .mz-cell-items .qty .u { font-weight: 600; color: #94A3B8; }
    .mz-cell.is-warn .mz-cell-items .qty { color: #B45309; }
    .mz-cell.is-danger .mz-cell-items .qty { color: #B91C1C; }
    .mz-cell-foot { margin-top: 6px; padding-top: 6px; border-top: 1px dashed #E2E8F0; font-size: .7rem; color: #94A3B8; font-weight: 600; }
    .mz-cell-foot .alert { color: #C2410C; }
    .mz-cell-empty-note { font-size: .73rem; color: #94A3B8; font-style: italic; }

    /* ---------- Khối chưa xếp vị trí + trạng thái rỗng ---------- */
    .mz-unzoned { border: 1px dashed #F59E0B; background: #FFFBEB; border-radius: var(--border-radius-lg); padding: 14px 18px; margin-bottom: 16px; }
    .mz-unzoned-head { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #B45309; margin-bottom: 8px; }
    .mz-empty { text-align: center; padding: 40px 20px; color: #94A3B8; }
    .mz-empty i { font-size: 2.2rem; color: var(--primary-lighter); margin-bottom: 10px; display: block; }
    .mz-noresult { display: none; }
</style>

@php
    $mzTotals = $zoneMap['totals'];
@endphp

<div class="mz-head">
    <div class="mz-stats">
        <div class="mz-stat"><b>{{ $mzTotals['warehouses'] }}</b><span>Kho</span></div>
        <div class="mz-stat"><b>{{ $mzTotals['rooms'] }}</b><span>Phòng</span></div>
        <div class="mz-stat"><b>{{ $mzTotals['shelves'] }}</b><span>Kệ / Tủ</span></div>
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

<p class="mz-hint">
    <i class="fas fa-info-circle mr-1"></i>
    Sơ đồ chỉ vẽ các ô có <b>Loại Lưu Trữ = Vật tư</b> khai ở <b>Dữ Liệu Gốc → Định Khu</b>, kèm các ô chưa khai loại
    (dùng chung). Ô của loại khác mà đang còn vật tư vẫn hiện ra để biết mà chuyển chỗ.
</p>

<div class="mz-filters">
    <button type="button" class="mz-filter is-active" data-state="">Tất cả vị trí</button>
    <button type="button" class="mz-filter" data-state="stocked"><span class="dot ok"></span>Đang có hàng</button>
    <button type="button" class="mz-filter" data-state="alert"><span class="dot warn"></span>Cần chú ý</button>
    <button type="button" class="mz-filter" data-state="empty"><span class="dot empty"></span>Còn trống</button>
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
                    {{ $wh['code'] ? $wh['code'] . ' · ' : '' }}{{ $wh['stat']['rooms'] }} phòng ·
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
            @foreach ($wh['rooms'] as $room)
                <div class="mz-room">
                    <div class="mz-room-head">
                        <i class="fas fa-door-open"></i> {{ $room['name'] }}
                        @if ($room['code'])
                            <span class="tag">{{ $room['code'] }}</span>
                        @endif
                        <span class="tag">{{ $room['stat']['shelves'] }} kệ/tủ</span>
                        <span class="tag">{{ $room['stat']['lots'] }} mã</span>
                    </div>

                    @foreach ($room['shelves'] as $shelf)
                        <div class="mz-shelf">
                            <div class="mz-shelf-head">
                                <i class="fas fa-th-large" style="color: var(--primary-light)"></i>
                                {{ $shelf['name'] }}
                                @if ($shelf['code'])
                                    <span class="md-tag">{{ $shelf['code'] }}</span>
                                @endif
                                <span class="sub">
                                    {{ $shelf['stat']['filled'] }}/{{ $shelf['stat']['locations'] }} vị trí có hàng ·
                                    {{ $shelf['stat']['lots'] }} mã
                                </span>
                            </div>

                            <div class="mz-grid">
                                @foreach ($shelf['locations'] as $loc)
                                    @php
                                        $mzSearch = mb_strtolower(
                                            trim(
                                                $loc['code'] .
                                                    ' ' .
                                                    $shelf['name'] .
                                                    ' ' .
                                                    implode(' ', array_column($loc['preview'], 'name')),
                                            ),
                                        );
                                    @endphp
                                    <button type="button" class="mz-cell is-{{ $loc['state'] }}"
                                        data-key="{{ $loc['key'] }}" data-state="{{ $loc['state'] }}"
                                        data-search="{{ $mzSearch }}"
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
                                                        +{{ $loc['stat']['materials'] - count($loc['preview']) }} vật tư
                                                        khác</span></div>
                                            @endif
                                        </div>
                                        <div class="mz-cell-foot">
                                            {{ $loc['stat']['materials'] }} vật tư
                                            @if ($loc['stat']['alerts'] > 0)
                                                · <span class="alert">{{ $loc['stat']['alerts'] }} cần chú ý</span>
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
    </section>
@empty
    <div class="mz-empty">
        <i class="fas fa-map-marked-alt"></i>
        Chưa khai báo định khu cho phòng ban này. Vào <b>Dữ Liệu Gốc → Định Khu</b> để tạo Kho / Phòng / Kệ-Tủ / Vị trí,
        sau đó xếp vị trí cho từng mã nhập.
    </div>
@endforelse

<div class="mz-empty mz-noresult">
    <i class="fas fa-search"></i>
    Không có vị trí nào khớp với điều kiện đang lọc.
</div>
