@include('pages.inventory.shared.assets')
@php $activeTab = \App\Support\PageTabs::active('inventory_chemical', $activeTab ?? 'code'); @endphp

@php
    $invDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
@endphp

<div class="content-wrapper">
    <div class="md-page inv-compact">

        <div class="card md-card">
            <div class="card-body">

                {{-- ============ KỲ BÁO CÁO ============ --}}
                <form method="GET" action="{{ route('pages.inventory.chemicalInventory.list') }}" class="inv-period"
                    id="invPeriodForm">
                    <div class="inv-period-title">
                        <i class="fas fa-calendar-week"></i> Kỳ báo cáo
                    </div>

                    <div class="inv-period-field">
                        <label for="invFromDate">Từ ngày</label>
                        <input type="date" id="invFromDate" name="from_date" class="form-control"
                            value="{{ $period['from'] }}">
                    </div>

                    <div class="inv-period-field">
                        <label for="invToDate">Đến ngày</label>
                        <input type="date" id="invToDate" name="to_date" class="form-control"
                            value="{{ $period['to'] }}">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm inv-period-apply">
                        <i class="fas fa-search mr-1"></i> Xem kỳ
                    </button>

                    {{-- Bấm mốc nhanh là điền sẵn hai ô ngày rồi gửi luôn, không phải chọn tay --}}
                    <div class="inv-period-quick">
                        @foreach ($invPeriodPresets as $preset)
                            <button type="button" class="inv-period-chip {{ $preset['active'] ? 'is-active' : '' }}"
                                data-from="{{ $preset['from'] }}" data-to="{{ $preset['to'] }}">
                                {{ $preset['label'] }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Lọc thuộc tính Hoá chất VS cho mọi tab; nằm trong form để đổi kỳ vẫn giữ lựa chọn --}}
                    <div class="inv-period-field">
                        <label for="invVsFilter">Hoá chất VS</label>
                        <select id="invVsFilter" name="vs"
                            class="form-control inv-vs-select {{ $vsFilter !== 'all' ? 'is-on' : '' }}"
                            title="Lọc theo thuộc tính Hoá chất vi sinh của phiếu nhập, áp dụng cho mọi tab">
                            <option value="all" @selected($vsFilter === 'all')>Tất cả</option>
                            <option value="yes" @selected($vsFilter === 'yes')>Hoá chất VS</option>
                            <option value="no" @selected($vsFilter === 'no')>Không phải VS</option>
                        </select>
                    </div>

                    {{-- Diễn giải kỳ để ở tooltip, không chiếm thêm một hàng --}}
                    <div class="inv-period-note"
                        title="{{ $period['is_current']
                            ? 'Kỳ đang chạy - Tồn Cuối Kỳ chính là tồn thực tế đang có trong kho.'
                            : 'Đang xem lại kỳ đã qua - mọi số liệu tính đến hết ngày ' . $invDate($period['to']) . '.' }} Chỉ hiện mã còn tồn cuối kỳ, có sử dụng hoặc có loại bỏ trong kỳ.">
                        <i class="fas {{ $period['is_current'] ? 'fa-circle-check' : 'fa-clock-rotate-left' }}"></i>
                        <span class="inv-period-days">{{ $period['days'] }} ngày</span>
                    </div>
                </form>

                <div class="inv-tabs">
                    @tab('inventory_chemical', 'code')
                    <button type="button" class="inv-tab {{ $activeTab === 'code' ? 'is-active' : '' }}" data-pane="invPaneCode">
                        <i class="fas fa-barcode mr-1"></i> Theo mã xuất nhập
                    </button>
                    @endtab
                    @tab('inventory_chemical', 'chemical')
                    <button type="button" class="inv-tab {{ $activeTab === 'chemical' ? 'is-active' : '' }}" data-pane="invPaneChem">
                        <i class="fas fa-flask mr-1"></i> Theo hoá chất
                    </button>
                    @endtab
                    @tab('inventory_chemical', 'zone')
                    <button type="button" class="inv-tab {{ $activeTab === 'zone' ? 'is-active' : '' }}" data-pane="invPaneZone">
                        <i class="fas fa-map-location-dot mr-1"></i> Theo định khu
                    </button>
                    @endtab
                    @tab('inventory_chemical', 'expiring')
                    <button type="button" class="inv-tab {{ $activeTab === 'expiring' ? 'is-active' : '' }}" data-pane="invPaneExpiring">
                        <i class="fas fa-calendar-xmark mr-1"></i> Cận Hạn Dùng
                        <span class="inv-tab-count" data-inv-count="expiring">…</span>
                    </button>
                    @endtab
                    @tab('inventory_chemical', 'internal')
                    <button type="button" class="inv-tab {{ $activeTab === 'internal' ? 'is-active' : '' }}" data-pane="invPaneInternal">
                        <i class="fas fa-hourglass-half mr-1"></i> Chưa có hạn nội bộ
                        <span class="inv-tab-count" data-inv-count="internal">…</span>
                    </button>
                    @endtab
                </div>

                {{-- ============ TỒN THEO TỪNG MÃ XUẤT NHẬP ============ --}}
                @tab('inventory_chemical', 'code')
                <div class="inv-pane {{ $activeTab === 'code' ? 'is-active' : '' }}" id="invPaneCode">

                    <div class="inv-filters md-tablebar-item">
                        <button type="button" class="inv-chip is-active" data-state="all">
                            <i class="fas fa-layer-group"></i> Tất cả
                            <span class="count">…</span>
                        </button>
                        @foreach ($states as $key => $label)
                            {{-- Cách tính từng trạng thái để ở tooltip, không chiếm thêm một hàng trên màn hình --}}
                            <button type="button" class="inv-chip" data-state="{{ $key }}"
                                title="{{ $invStateHints[$key] ?? $label }}">
                                <span class="inv-badge inv-badge-{{ $key }}">{{ $label }}</span>
                                <span class="count">…</span>
                            </button>
                        @endforeach
                    </div>

                    @include('pages.shared.barcodeSearch', [
                        'scanTitle' => 'Quét mã vạch',
                        'scanTables' => [
                            ['id' => 'mdTable', 'column' => 1, 'pane' => 'invPaneCode', 'label' => 'Theo mã xuất nhập'],
                            [
                                'id' => 'invExpiringTable',
                                'column' => 1,
                                'pane' => 'invPaneExpiring',
                                'label' => 'Cận Hạn Dùng',
                            ],
                            [
                                'id' => 'invInternalTable',
                                'column' => 1,
                                'pane' => 'invPaneInternal',
                                'label' => 'Chưa có hạn nội bộ',
                            ],
                        ],
                    ])

                    @include('pages.shared.classificationFilter', ['clsTarget' => 'mdTable'])

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100" data-no-datatable>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 135px">Mã Xuất Nhập</th>
                                    <th>Hoá Chất</th>
                                    <th style="width: 175px"
                                        title="Vị trí lưu trữ thực tế của lô hàng này (Kho / Phòng / Kệ/Tủ / Vị trí)">
                                        Vị Trí Lưu Trữ</th>
                                    <th class="text-center" style="width: 70px">Đơn Vị</th>
                                    <th class="text-right" style="width: 110px"
                                        title="Tồn của mã xuất nhập này trước ngày {{ $invDate($period['from']) }}">
                                        Tồn Đầu Kỳ</th>
                                    <th class="text-right" style="width: 110px"
                                        title="Số nhập kho trong kỳ {{ $invPeriodLabel }}">Nhập Trong Kỳ</th>
                                    <th class="text-right" style="width: 105px"
                                        title="Số đã cân đối trong kỳ {{ $invPeriodLabel }}">Cân Đối Trong Kỳ</th>
                                    <th class="text-right" style="width: 115px"
                                        title="Số đã sử dụng trong kỳ {{ $invPeriodLabel }}">Sử Dụng Trong Kỳ</th>
                                    <th class="text-right" style="width: 105px"
                                        title="Số đã huỷ bỏ trong kỳ {{ $invPeriodLabel }}">Huỷ Trong Kỳ</th>
                                    <th class="text-right" style="width: 150px"
                                        title="Tồn đầu kỳ + nhập + cân đối - sử dụng - huỷ, tính đến hết ngày {{ $invDate($period['to']) }}">
                                        Tồn Cuối Kỳ</th>
                                    <th class="text-right" style="width: 125px"
                                        title="Tổng tồn của các mã cùng hoá chất và cùng số lô">Tổng Tồn Theo Lô</th>
                                    <th class="text-right" style="width: 125px"
                                        title="Tổng tồn của các mã cùng hoá chất, gộp mọi số lô">Tổng Tồn Các Lô</th>
                                    <th class="text-center" style="width: 120px">Hạn Dùng</th>
                                    <th class="text-center" style="width: 125px">Hạn Nội Bộ</th>
                                    <th class="text-center" style="width: 115px">Trạng Thái</th>
                                    <th class="text-center" style="width: 60px" title="File hồ sơ đính kèm của phiếu nhập">
                                        <i class="fas fa-paperclip"></i></th>
                                    <th class="text-center" style="width: 110px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="inv-loading-row">
                                    <td colspan="18" class="text-center md-sub">
                                        <i class="fas fa-circle-notch fa-spin mr-1"></i> Đang tải dữ liệu tồn kho...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ TỒN CỘNG DỒN THEO HOÁ CHẤT ============ --}}
                @tab('inventory_chemical', 'chemical')
                <div class="inv-pane {{ $activeTab === 'chemical' ? 'is-active' : '' }}" id="invPaneChem">

                    @include('pages.shared.classificationFilter', ['clsTarget' => 'invSummaryTable'])

                    <div class="table-responsive">
                        <table id="invSummaryTable" class="table table-bordered table-hover w-100" data-inv-lazy>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th>Hoá Chất</th>
                                    <th class="text-center" style="width: 120px"
                                        title="Số mã xuất nhập còn tồn / tổng số mã xuất nhập trong kỳ">Số Mã Xuất Nhập</th>
                                    <th class="text-center" style="width: 70px">Đơn Vị</th>
                                    <th class="text-right" style="width: 110px">Tồn Đầu Kỳ</th>
                                    <th class="text-right" style="width: 110px">Nhập Trong Kỳ</th>
                                    <th class="text-right" style="width: 105px">Cân Đối Trong Kỳ</th>
                                    <th class="text-right" style="width: 110px">Sử Dụng Trong Kỳ</th>
                                    <th class="text-right" style="width: 100px">Huỷ Trong Kỳ</th>
                                    <th class="text-right" style="width: 130px">Tồn Cuối Kỳ</th>
                                    <th class="text-center" style="width: 120px">Hạn Gần Nhất</th>
                                    <th class="text-center inv-chart-th" style="width: 115px">Biểu Đồ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="inv-loading-row">
                                    <td colspan="12" class="text-center md-sub">
                                        <i class="fas fa-circle-notch fa-spin mr-1"></i> Đang tải dữ liệu tồn kho...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ TỒN THEO ĐỊNH KHU ============ --}}
                @tab('inventory_chemical', 'zone')
                <div class="inv-pane {{ $activeTab === 'zone' ? 'is-active' : '' }}" id="invPaneZone">

                    {{-- Sơ đồ thẻ Kho/Phòng -> Kệ/Tủ -> Cột -> Tầng -> Vị trí như Tồn Kho Vật Tư, dựng ở script --}}
                    @include('pages.inventory.shared.zoneGrid', [
                        'zgId' => 'invZoneMap',
                        'zgItem' => 'hoá chất',
                        'zgCode' => 'Mã xuất nhập',
                    ])
                </div>
                @endtab

                {{-- ============ CẬN HẠN DÙNG (HẠN ÁP DỤNG DƯỚI 6 THÁNG) ============ --}}
                @tab('inventory_chemical', 'expiring')
                <div class="inv-pane {{ $activeTab === 'expiring' ? 'is-active' : '' }}" id="invPaneExpiring">

                    <div class="inv-filters md-tablebar-item">
                        <button type="button" class="inv-chip is-active" data-expiry="all"
                            title="Còn tồn và hạn áp dụng dưới {{ $expiringSoonMonths }} tháng, kể cả đã quá hạn">
                            <i class="fas fa-layer-group"></i> Dưới {{ $expiringSoonMonths }} tháng
                            <span class="count">…</span>
                        </button>
                        @foreach ($expiringFilters as $key => $filter)
                            <button type="button" class="inv-chip" data-expiry="{{ $key }}"
                                title="Hạn áp dụng đến hết {{ \Carbon\Carbon::parse($filter['until'])->format('d/m/Y') }}, kể cả đã quá hạn">
                                <i class="fas fa-hourglass-end"></i> {{ $filter['label'] }}
                                <span class="count">…</span>
                            </button>
                        @endforeach
                    </div>

                    @include('pages.shared.classificationFilter', ['clsTarget' => 'invExpiringTable'])

                    <div class="table-responsive">
                        <table id="invExpiringTable" class="table table-bordered table-hover w-100" data-inv-lazy>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 135px">Mã Xuất Nhập</th>
                                    <th>Hoá Chất</th>
                                    <th class="text-right" style="width: 125px">Tồn Cuối Kỳ</th>
                                    <th class="text-right" style="width: 125px">Tổng Tồn Theo Lô</th>
                                    <th class="text-center" style="width: 120px">Hạn Dùng</th>
                                    <th class="text-center" style="width: 120px">Hạn Nội Bộ</th>
                                    <th class="text-center" style="width: 140px">Hạn Áp Dụng</th>
                                    <th class="text-center" style="width: 115px">Trạng Thái</th>
                                    <th class="text-center" style="width: 80px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="inv-loading-row">
                                    <td colspan="10" class="text-center md-sub">
                                        <i class="fas fa-circle-notch fa-spin mr-1"></i> Đang tải dữ liệu tồn kho...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ CHƯA XÁC ĐỊNH HẠN DÙNG NỘI BỘ ============ --}}
                @tab('inventory_chemical', 'internal')
                <div class="inv-pane {{ $activeTab === 'internal' ? 'is-active' : '' }}" id="invPaneInternal">

                    @include('pages.shared.classificationFilter', ['clsTarget' => 'invInternalTable'])

                    <div class="table-responsive">
                        <table id="invInternalTable" class="table table-bordered table-hover w-100" data-inv-lazy>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 135px">Mã Xuất Nhập</th>
                                    <th>Hoá Chất</th>
                                    <th class="text-right" style="width: 130px">Tồn Cuối Kỳ</th>
                                    <th class="text-center" style="width: 110px">Ngày Nhập</th>
                                    <th class="text-center" style="width: 120px">Hạn Dùng</th>
                                    <th class="text-center" style="width: 130px">Hạn Dùng Mặc Định</th>
                                    <th class="text-center" style="width: 150px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="inv-loading-row">
                                    <td colspan="8" class="text-center md-sub">
                                        <i class="fas fa-circle-notch fa-spin mr-1"></i> Đang tải dữ liệu tồn kho...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

            </div>
        </div>
    </div>
</div>

@include('pages.inventory.ChemicalInventory.script')

@once
    <style>
        /* ---------- Thu gọn phần bộ lọc phía trên bảng ---------- */
        .inv-compact .inv-period {
            align-items: center;
            gap: 8px 10px;
            padding: 6px 12px;
            margin-bottom: 8px;
        }

        .inv-compact .inv-period-field {
            flex-direction: row;
            align-items: center;
            gap: 6px;
        }

        .inv-compact .inv-period-field label {
            white-space: nowrap;
        }

        .inv-compact .inv-period-field .inv-vs-select {
            width: 130px;
            padding: 2px 8px;
            cursor: pointer;
        }

        .inv-compact .inv-period-field .inv-vs-select.is-on {
            border-color: var(--primary);
            background: var(--primary-soft);
            color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
        }

        .inv-compact .inv-period-field .form-control {
            width: 140px;
            height: 30px;
            padding: 2px 8px;
        }

        .inv-compact .inv-period-apply {
            height: 30px;
            padding: 0 12px;
        }

        .inv-compact .inv-period-quick {
            padding-left: 10px;
            gap: 4px;
        }

        .inv-compact .inv-period-chip {
            padding: 3px 10px;
        }

        .inv-compact .inv-period-note {
            flex: 0 0 auto;
            min-width: 0;
            margin-left: auto;
            cursor: help;
        }

        .inv-compact .inv-tabs {
            margin-bottom: 8px;
        }

        .inv-compact .inv-tab {
            padding: 6px 14px;
        }

        /* Chip trạng thái nằm đầu thanh công cụ chung (.md-tablebar) */
        .inv-compact .md-tablebar .inv-filters {
            order: -1;
            gap: 4px;
            margin: 0;
        }

        .inv-compact .md-tablebar .inv-chip {
            gap: 5px;
            padding: 3px 9px;
            font-size: 0.78rem;
        }

        .inv-compact .md-tablebar .inv-chip.is-active {
            box-shadow: none;
        }

        /*
        | Header bảng luôn hiện khi cuộn: bảng cuộn trong khung cao vừa màn hình,
        | tiêu đề cột dính trên cùng khung (dom 'inv-scroll' ở script.blade.php).
        */
        .inv-compact .table-responsive {
            overflow: visible;
        }

        .inv-compact .inv-scroll {
            max-height: calc(100vh - 150px);
            overflow: auto;
        }

        .inv-compact .inv-scroll table.dataTable {
            margin-top: 0 !important;
        }

        .inv-compact .inv-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: var(--primary-soft);
        }

        /* ---------- Hai bảng tồn theo mã / theo hoá chất: giao diện như Tồn Kho Vật Tư ---------- */
        #invPaneChem #invSummaryTable thead th {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-lighter);
        }

        #invPaneChem #invSummaryTable tbody tr:hover {
            background: var(--primary-soft);
        }

        .inv-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .inv-act-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            padding: 0;
            line-height: 1;
            border-radius: var(--border-radius-md);
            font-size: .85rem;
            border: 1px solid var(--primary);
            background: #fff;
            color: var(--primary);
            transition: all .2s;
        }

        .inv-act-btn:hover,
        .inv-act-btn:focus {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
            outline: none;
        }

        /* Mã âm kho: nút Cân đối tô đỏ để thấy cần xử lý */
        .inv-act-btn.is-danger {
            border-color: #DC2626;
            color: #DC2626;
        }

        .inv-act-btn.is-danger:hover,
        .inv-act-btn.is-danger:focus {
            background: #DC2626;
            color: #fff;
        }

        .inv-act-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            line-height: 1;
            border-radius: var(--border-radius-md);
            font-size: .78rem;
            font-weight: 700;
            border: 1px solid var(--primary-lighter);
            background: var(--primary-soft);
            color: var(--primary);
            transition: all .2s;
        }

        .inv-act-count:hover,
        .inv-act-count:focus {
            background: var(--primary-lighter);
            color: var(--primary-dark);
            border-color: var(--primary);
            transform: translateY(-1px);
            outline: none;
        }

        .inv-threshold-alert {
            border-radius: var(--border-radius-md);
            margin-bottom: 16px;
        }

        .inv-threshold-head {
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .inv-threshold-table th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-top: none;
        }

        .inv-threshold-table td {
            font-size: 0.86rem;
            vertical-align: middle;
        }

        .inv-threshold-dept {
            display: inline-block;
            background: rgba(var(--primary-rgb), 0.08);
            border: 1px solid var(--primary-lighter);
            border-radius: 6px;
            padding: 1px 7px;
            margin: 2px 4px 2px 0;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .inv-threshold-dept.is-current {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            font-weight: 700;
        }
    </style>
@endonce
