@include('pages.inventory.shared.assets')

@php
    /*
    | Ống chuẩn chưa xác định hạn dùng nội bộ: chất chuẩn có khai báo hạn dùng mặc định
    | (standard_categories.shelf_life_months > 0) nhưng standard_imports.internal_expired_date
    | còn trống. Những ống này CHƯA ĐƯỢC SỬ DỤNG - màn hình Sử Dụng Chất Chuẩn không cho chọn.
    */
    $invWaitingInternal = $datas->filter(fn($row) => $row->can_internal_expiry && !$row->internal_expired_date)->values();

    /*
    | Sắp hết hạn: còn tồn và hạn ÁP DỤNG (hạn nội bộ nếu có, không thì hạn nhà sản xuất)
    | rơi vào trong 6 tháng tới - kể cả các mã đã quá hạn, đó là phần gấp nhất.
    */
    $invExpiringSoon = $datas->filter(fn($row) => $row->is_expiring_soon)
        ->sortBy('effective_expired_date')
        ->values();

    /*
    | Kiểm soát khối lượng
    */
    $invWeightControlled = $datas->filter(fn($row) => $row->weight_controlled)->values();
@endphp

<style>
    /* Mã nhóm chuẩn của ống - phần nằm giữa mã ống chuẩn */
    .sd-group-tag {
        display: inline-block;
        background: #EDE9FE;
        color: #5B21B6;
        border: 1px solid #C4B5FD;
        border-radius: 999px;
        padding: 1px 9px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }
</style>

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                {{-- ============ KỲ BÁO CÁO ============ --}}
                <form method="GET" action="{{ route('pages.inventory.standardInventory.list') }}" class="inv-period"
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

                    <div class="inv-period-note">
                        @if ($period['is_current'])
                            <i class="fas fa-circle-check"></i> Kỳ đang chạy - Tồn Cuối Kỳ chính là tồn thực tế
                            đang có trong kho.
                        @else
                            <i class="fas fa-clock-rotate-left"></i> Đang xem lại kỳ đã qua - mọi số liệu tính đến
                            hết ngày {{ $invDate($period['to']) }}.
                        @endif
                        <span class="inv-period-days">{{ $period['days'] }} ngày</span>
                        <span class="inv-period-rule"><i class="fas fa-filter"></i>Chỉ hiện mã còn tồn cuối kỳ,
                            có sử dụng hoặc có loại bỏ trong kỳ.</span>
                    </div>
                </form>

                <div class="inv-tabs">
                    <button type="button" class="inv-tab is-active" data-pane="invPaneCode">
                        <i class="fas fa-barcode mr-1"></i> Theo mã ống chuẩn
                    </button>
                    <button type="button" class="inv-tab" data-pane="invPaneChem">
                        <i class="fas fa-vial-circle-check mr-1"></i> Theo chất chuẩn
                    </button>
                    <button type="button" class="inv-tab" data-pane="invPaneZone">
                        <i class="fas fa-map-location-dot mr-1"></i> Theo định khu
                    </button>
                    <button type="button" class="inv-tab" data-pane="invPaneExpiring">
                        <i class="fas fa-calendar-xmark mr-1"></i> Hạn dùng dưới {{ $expiringSoonMonths }} tháng
                        <span class="inv-tab-count">{{ $invExpiringSoon->count() }}</span>
                    </button>
                    <button type="button" class="inv-tab" data-pane="invPaneInternal">
                        <i class="fas fa-hourglass-half mr-1"></i> Chưa có hạn nội bộ
                        <span class="inv-tab-count">{{ $invWaitingInternal->count() }}</span>
                    </button>
                    <button type="button" class="inv-tab" data-pane="invPaneWeight">
                        <i class="fas fa-balance-scale mr-1"></i> Kiểm soát Khối lượng
                        <span class="inv-tab-count">{{ $invWeightControlled->count() }}</span>
                    </button>
                </div>

                {{-- ============ TỒN THEO TỪNG MÃ ỐNG CHUẨN ============ --}}
                <div class="inv-pane is-active" id="invPaneCode">

                    <div class="inv-filters">
                        <button type="button" class="inv-chip is-active" data-state="all">
                            <i class="fas fa-layer-group"></i> Tất cả
                            <span class="count">{{ $datas->count() }}</span>
                        </button>
                        @foreach ($states as $key => $label)
                            {{-- Cách tính từng trạng thái để ở tooltip, không chiếm thêm một hàng trên màn hình --}}
                            <button type="button" class="inv-chip" data-state="{{ $key }}"
                                title="{{ $invStateHints[$key] ?? $label }}">
                                <span class="inv-badge inv-badge-{{ $key }}">{{ $label }}</span>
                                <span class="count">{{ $invStateCounts[$key] }}</span>
                            </button>
                        @endforeach
                    </div>

                    @include('pages.shared.barcodeSearch', [
                        'scanTitle' => 'Quét mã vạch',
                        'scanTables' => [
                            ['id' => 'mdTable', 'column' => 1, 'pane' => 'invPaneCode', 'label' => 'Theo mã ống chuẩn'],
                            ['id' => 'invZoneTable', 'column' => 2, 'pane' => 'invPaneZone', 'label' => 'Theo định khu'],
                            [
                                'id' => 'invExpiringTable',
                                'column' => 1,
                                'pane' => 'invPaneExpiring',
                                'label' => 'Hạn dùng dưới ' . $expiringSoonMonths . ' tháng',
                            ],
                            [
                                'id' => 'invInternalTable',
                                'column' => 1,
                                'pane' => 'invPaneInternal',
                                'label' => 'Chưa có hạn nội bộ',
                            ],
                            [
                                'id' => 'invWeightTable',
                                'column' => 1,
                                'pane' => 'invPaneWeight',
                                'label' => 'Kiểm soát Khối lượng',
                            ],
                        ],
                    ])

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'mdTable'])

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 165px">Mã Ống Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th style="width: 175px"
                                        title="Vị trí lưu trữ thực tế của ống chuẩn này (Kho / Phòng / Kệ/Tủ / Vị trí)">
                                        Vị Trí Lưu Trữ</th>
                                    <th class="text-right inv-th-period" style="width: 110px"
                                        title="Tồn của ống chuẩn này trước ngày {{ $invDate($period['from']) }}">
                                        Tồn Đầu Kỳ</th>
                                    <th class="text-right inv-th-period" style="width: 115px"
                                        title="Số nhập kho trong kỳ {{ $invPeriodLabel }}, đã gồm cả số cân đối ghi trong kỳ">
                                        Nhập Trong Kỳ</th>
                                    <th class="text-right inv-th-period" style="width: 115px"
                                        title="Số đã sử dụng trong kỳ {{ $invPeriodLabel }}">Sử Dụng Trong Kỳ</th>
                                    <th class="text-right inv-th-period" style="width: 105px"
                                        title="Số đã huỷ bỏ trong kỳ {{ $invPeriodLabel }}">Huỷ Trong Kỳ</th>
                                    <th class="text-right inv-th-period" style="width: 150px"
                                        title="Tồn đầu kỳ + nhập trong kỳ - sử dụng - huỷ, tính đến hết ngày {{ $invDate($period['to']) }}">
                                        Tồn Cuối Kỳ</th>
                                    <th class="text-right" style="width: 125px"
                                        title="Tổng tồn của các ống cùng chất chuẩn và cùng số lô">Tổng Tồn Theo Lô</th>
                                    <th class="text-right" style="width: 125px"
                                        title="Tổng tồn của các ống cùng chất chuẩn, gộp mọi số lô">Tổng Tồn Các Lô</th>
                                    <th class="text-center" style="width: 120px">Hạn Dùng</th>
                                    <th class="text-center" style="width: 125px">Hạn Nội Bộ</th>
                                    <th class="text-center" style="width: 115px">Trạng Thái</th>
                                    <th class="text-center" style="width: 190px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($datas as $row)
                                    {{-- data-groups để bộ lọc Phân nhóm chuẩn nhận ra dòng này --}}
                                    <tr data-state="{{ $row->state }}" data-groups="{{ $invGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="inv-code">{{ $row->code }}</span>
                                            <div class="md-sub">
                                                <span class="sd-group-tag">{{ $invGroupName($row->group_code) }}</span>
                                            </div>
                                            <div class="md-sub">Nhập {{ $invDate($row->imported_date) }}</div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                                <span class="sgr-version ml-1">v{{ $row->category_version }}</span>
                                                @if ($row->batch_no)
                                                    <span class="ml-1">Lô {{ $row->batch_no }}</span>
                                                @endif
                                                @if ($row->manufacturer_short_name)
                                                    <span class="ml-1">· {{ $row->manufacturer_short_name }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        {{-- Vị trí THỰC TẾ của ống, sắp xếp theo đường dẫn định khu --}}
                                        <td class="md-sub"
                                            data-order="{{ $row->location_code ? $row->warehouse_name . '/' . $row->room_name . '/' . $row->shelf_name . '/' . $row->location_code : 'zzz' }}">
                                            @if ($row->location_code)
                                                <div class="font-weight-bold">
                                                    <span class="md-tag">{{ $row->location_code }}</span>
                                                </div>
                                                <div>{{ $row->warehouse_name ?: '—' }} / {{ $row->room_name ?: '—' }} /
                                                    {{ $row->shelf_name ?: '—' }}</div>
                                            @else
                                                <span class="inv-zone-none">Chưa xếp vị trí</span>
                                            @endif
                                        </td>
                                        {{-- Tồn đầu kỳ: phát sinh trước ngày bắt đầu kỳ --}}
                                        <td class="text-right" data-order="{{ $row->opening }}">
                                            <span
                                                class="inv-amount {{ abs($row->opening) > 0 ? '' : 'inv-muted' }}">{{ $invNum($row->opening) }}</span>
                                            <div class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</div>
                                            @if ($row->is_new_in_period)
                                                <div class="md-sub"><span class="inv-period-tag">Mới nhập trong
                                                        kỳ</span></div>
                                            @endif
                                        </td>
                                        {{-- Nhập trong kỳ: phiếu nhập + số cân đối ghi trong kỳ --}}
                                        <td class="text-right" data-order="{{ $row->period_in }}">
                                            <span
                                                class="inv-amount {{ $row->period_in != 0 ? 'is-in' : 'inv-muted' }}">{{ $invNum($row->period_in) }}</span>
                                            <div class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</div>
                                            @if ($row->period_imported > 0)
                                                <div class="md-sub">Nhập {{ $invDate($row->imported_date) }}</div>
                                            @endif
                                            @if ($row->period_balanced != 0)
                                                <div class="md-sub">Cân đối
                                                    {{ $row->period_balanced > 0 ? '+' : '' }}{{ $invNum($row->period_balanced) }}
                                                </div>
                                            @endif
                                        </td>
                                        {{-- Sử dụng trong kỳ: standard_exports type = 'export' --}}
                                        <td class="text-right" data-order="{{ $row->period_used }}">
                                            <span class="inv-amount {{ $row->period_used > 0 ? 'is-out' : 'inv-muted' }}">
                                                {{ $invNum($row->period_used) }}
                                            </span>
                                            <div class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</div>
                                            @if ($row->period_export_times > 0)
                                                <div class="md-sub">{{ $row->period_export_times }} lần xuất</div>
                                            @endif
                                        </td>
                                        {{-- Huỷ trong kỳ: standard_exports type = 'cancel' --}}
                                        <td class="text-right" data-order="{{ $row->period_cancelled }}">
                                            <span
                                                class="inv-amount {{ $row->period_cancelled > 0 ? 'is-out' : 'inv-muted' }}">{{ $invNum($row->period_cancelled) }}</span>
                                            <div class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</div>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->gap }}">
                                            {{-- Tồn âm (đã xuất vượt) hiện đúng số âm để thấy phần phải cân đối --}}
                                            <span
                                                class="inv-remaining {{ $row->state === 'over' ? 'is-over' : ($row->remaining > 0 ? '' : 'is-zero') }}">{{ $invNum($row->gap) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                            <div
                                                class="inv-bar {{ $row->state === 'out' ? 'is-out' : ($row->state === 'low' ? 'is-low' : '') }}">
                                                <span style="width: {{ $row->used_percent }}%"></span>
                                            </div>
                                            {{-- Luỹ kế từ lúc nhập đến hết kỳ, không chỉ riêng phần phát sinh trong kỳ --}}
                                            <div class="md-sub">Đã dùng {{ $row->used_percent }}% (luỹ kế
                                                {{ $invNum($row->used + $row->cancelled) }})</div>
                                        </td>
                                        {{-- Cùng chất chuẩn + cùng số lô --}}
                                        <td class="text-right" data-order="{{ $row->batch_remaining }}">
                                            <span
                                                class="inv-group-total {{ $row->batch_remaining > 0 ? '' : 'is-zero' }}">{{ $invNum($row->batch_remaining) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                            <div class="md-sub">
                                                @if ($row->batch_no)
                                                    Lô {{ $row->batch_no }}
                                                @else
                                                    Chưa có số lô
                                                @endif
                                                · {{ $row->batch_codes }} ống
                                            </div>
                                        </td>
                                        {{-- Cùng chất chuẩn, gộp mọi số lô --}}
                                        <td class="text-right" data-order="{{ $row->category_remaining }}">
                                            <span
                                                class="inv-group-total {{ $row->category_remaining > 0 ? '' : 'is-zero' }}">{{ $invNum($row->category_remaining) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                            <div class="md-sub">
                                                {{ $row->category_batches }} lô · {{ $row->category_codes }} ống
                                            </div>
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                            @if ($invIsCheckOnline($row))
                                                <span class="badge badge-warning"
                                                    title="Hạn dùng chưa xác định từ NSX. Tra cứu trực tuyến khi sử dụng.">
                                                    <i class="fas fa-globe"></i> Check online
                                                </span>
                                            @else
                                                {{ $invDate($row->expired_date) }}
                                                @if ($row->days_to_expiry !== null && $row->remaining > 0)
                                                    <div>
                                                        @if ($row->days_to_expiry < 0)
                                                            <span class="text-danger font-weight-bold">Quá
                                                                {{ abs($row->days_to_expiry) }} ngày</span>
                                                        @else
                                                            Còn {{ $row->days_to_expiry }} ngày
                                                        @endif
                                                    </div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->internal_expired_date ?: '9999-12-31' }}">
                                            @if ($row->internal_expired_date)
                                                <span class="inv-internal">{{ $invDate($row->internal_expired_date) }}</span>
                                            @elseif ($row->can_internal_expiry)
                                                <span class="inv-internal-none">Chưa xác định</span>
                                            @else
                                                <span class="md-empty"
                                                    title="Chất chuẩn chưa khai báo hạn dùng mặc định trong Danh Mục">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="inv-badge inv-badge-{{ $row->state }}">{{ $row->state_label }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($row->can_internal_expiry)
                                                @perm('inventory_standard_internalExpiry')
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary btn-inv-internal mb-1"
                                                        title="Xác định hạn dùng nội bộ cho ống chuẩn này"
                                                        data-row="{{ json_encode([
                                                            'import_id' => $row->id,
                                                            'code' => $row->code,
                                                            'chem_name' => $row->standard_name,
                                                            'shelf_life_months' => $row->shelf_life_months,
                                                            'expired_date' => $row->expired_date,
                                                            'internal_expired_date' => $row->internal_expired_date,
                                                        ]) }}">
                                                        <i class="fas fa-hourglass-half mr-1"></i> Hạn nội bộ
                                                    </button>
                                                    @endperm
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ TỒN CỘNG DỒN THEO CHẤT CHUẨN ============ --}}
                <div class="inv-pane" id="invPaneChem">

                    <div class="md-toolbar">
                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            Cộng tồn của tất cả ống chuẩn về từng chất chuẩn trong danh mục. Nên dùng trước ống có hạn
                            gần nhất.
                        </p>
                    </div>

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'invSummaryTable'])

                    <div class="table-responsive">
                        <table id="invSummaryTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th>Chất Chuẩn</th>
                                    <th class="text-center" style="width: 120px">Ống Chuẩn</th>
                                    <th class="text-right inv-th-period" style="width: 110px">Tồn Đầu Kỳ</th>
                                    <th class="text-right inv-th-period" style="width: 110px">Nhập Trong Kỳ</th>
                                    <th class="text-right inv-th-period" style="width: 115px">Sử Dụng Trong Kỳ</th>
                                    <th class="text-right inv-th-period" style="width: 105px">Huỷ Trong Kỳ</th>
                                    <th class="text-right inv-th-period" style="width: 130px">Tồn Cuối Kỳ</th>
                                    <th class="text-center" style="width: 120px">Hạn Gần Nhất</th>
                                    <th class="text-center" style="width: 110px">Cần Chú Ý</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summaries as $sum)
                                    <tr data-groups="{{ $invGroups($sum->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $sum->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $sum->category_code ?: '—' }}</span>
                                                <span class="sgr-version ml-1">v{{ $sum->version }}</span>
                                                @if ($sum->cas_no)
                                                    <span class="ml-1">CAS: {{ $sum->cas_no }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $sum->code_count }}">
                                            {{ $sum->in_stock_count }}/{{ $sum->code_count }} còn tồn
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->opening }}">
                                            <span
                                                class="inv-amount {{ abs($sum->opening) > 0 ? '' : 'inv-muted' }}">{{ $invNum($sum->opening) }}</span>
                                            <div class="md-sub">{{ $sum->unit }}</div>
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->period_in }}">
                                            <span
                                                class="inv-amount {{ $sum->period_in != 0 ? 'is-in' : 'inv-muted' }}">{{ $invNum($sum->period_in) }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->period_used }}">
                                            <span
                                                class="inv-amount {{ $sum->period_used > 0 ? 'is-out' : 'inv-muted' }}">{{ $invNum($sum->period_used) }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->period_cancelled }}">
                                            <span
                                                class="inv-amount {{ $sum->period_cancelled > 0 ? 'is-out' : 'inv-muted' }}">{{ $invNum($sum->period_cancelled) }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->closing }}">
                                            <span
                                                class="inv-remaining {{ $sum->closing > 0 ? '' : ($sum->closing < 0 ? 'is-over' : 'is-zero') }}">{{ $invNum($sum->closing) }}</span>
                                            <span class="md-sub">{{ $sum->unit }}</span>
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $sum->nearest_expiry ?: '9999-12-31' }}">
                                            {{ $invDate($sum->nearest_expiry) }}
                                        </td>
                                        <td class="text-center" data-order="{{ $sum->alert_count }}">
                                            @if ($sum->alert_count > 0)
                                                <span class="inv-badge inv-badge-near">{{ $sum->alert_count }} ống</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ TỒN THEO ĐỊNH KHU ============ --}}
                <div class="inv-pane" id="invPaneZone">

                    <div class="md-toolbar">
                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            Chọn dần <b>Kho → Phòng → Kệ/Tủ → Vị trí</b> để xem chất chuẩn đang chứa ở đó. Chọn tới cấp
                            nào thì lọc tới cấp đó. Vị trí lấy theo <b>chỗ để thực tế</b> của từng ống, không phải vị
                            trí quy hoạch.
                        </p>
                    </div>

                    {{-- 4 ô chọn dây chuyền: chọn cấp trên thì cấp dưới tự lọc lại --}}
                    <div class="inv-zone-picker" data-zones="{{ json_encode($zones) }}">
                        <div class="inv-zone-field">
                            <label><i class="fas fa-warehouse"></i> Kho</label>
                            <select class="form-control inv-zone-select" data-level="warehouse">
                                <option value="">Tất cả kho</option>
                            </select>
                        </div>
                        <div class="inv-zone-field">
                            <label><i class="fas fa-door-open"></i> Phòng</label>
                            <select class="form-control inv-zone-select" data-level="room">
                                <option value="">Tất cả phòng</option>
                            </select>
                        </div>
                        <div class="inv-zone-field">
                            <label><i class="fas fa-layer-group"></i> Kệ/Tủ</label>
                            <select class="form-control inv-zone-select" data-level="shelf">
                                <option value="">Tất cả kệ/tủ</option>
                            </select>
                        </div>
                        <div class="inv-zone-field">
                            <label><i class="fas fa-map-pin"></i> Vị trí</label>
                            <select class="form-control inv-zone-select" data-level="location">
                                <option value="">Tất cả vị trí</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-inv-zone-reset">
                            <i class="fas fa-rotate-left mr-1"></i> Bỏ lọc
                        </button>
                    </div>

                    <div class="inv-zone-summary">
                        <span class="path"><i class="fas fa-location-dot mr-1"></i><span
                                class="inv-zone-path">Toàn bộ định khu của phòng</span></span>
                        <span class="stat"><i class="fas fa-barcode"></i> <b class="inv-zone-codes">0</b> ống
                            chuẩn</span>
                        <span class="stat"><i class="fas fa-vial-circle-check"></i> <b class="inv-zone-chems">0</b> chất
                            chuẩn</span>
                    </div>

                    <div class="table-responsive">
                        <table id="invZoneTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 55px">STT</th>
                                    <th style="width: 200px">Vị Trí</th>
                                    <th style="width: 165px">Mã Ống Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th class="text-right" style="width: 120px">Tồn Cuối Kỳ</th>
                                    <th class="text-center" style="width: 115px">Hạn Áp Dụng</th>
                                    <th class="text-center" style="width: 110px">Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($datas as $row)
                                    {{-- 4 cấp định khu để JS lọc; ống chưa xếp vị trí thì cả 4 đều rỗng --}}
                                    <tr data-groups="{{ $invGroups($row->groups) }}"
                                        data-warehouse="{{ $row->warehouse_id }}" data-room="{{ $row->room_id }}"
                                        data-shelf="{{ $row->shelf_id }}" data-location="{{ $row->location_id }}"
                                        data-category="{{ $row->category_id }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="md-sub">
                                            @if ($row->location_code)
                                                <div class="font-weight-bold">
                                                    <span class="md-tag">{{ $row->location_code }}</span>
                                                </div>
                                                <div>{{ $row->warehouse_name ?: '—' }} / {{ $row->room_name ?: '—' }} /
                                                    {{ $row->shelf_name ?: '—' }}</div>
                                            @else
                                                <span class="inv-zone-none">Chưa xếp vị trí</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="inv-code">{{ $row->code }}</span>
                                            @if ($row->batch_no)
                                                <div class="md-sub">Lô {{ $row->batch_no }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                                <span class="sgr-version ml-1">v{{ $row->category_version }}</span>
                                            </div>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->gap }}">
                                            <span
                                                class="inv-remaining {{ $row->state === 'over' ? 'is-over' : ($row->remaining > 0 ? '' : 'is-zero') }}">{{ $invNum($row->gap) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->effective_expired_date ?: '9999-12-31' }}">
                                            {{ $invDate($row->effective_expired_date) }}
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="inv-badge inv-badge-{{ $row->state }}">{{ $row->state_label }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ HẠN DÙNG DƯỚI 6 THÁNG ============ --}}
                <div class="inv-pane" id="invPaneExpiring">

                    <div class="md-toolbar">
                        <p class="hint">
                            <i class="fas fa-triangle-exclamation mr-1"></i>
                            Các ống chuẩn <b>còn tồn</b> và hết hạn trong vòng <b>{{ $expiringSoonMonths }}
                                tháng</b> tới, gần hết hạn nhất xếp trước. Hạn xét ở đây là <b>hạn áp dụng</b>: lấy
                            hạn dùng nội bộ nếu đã xác định, chưa xác định thì lấy hạn nhà sản xuất.
                        </p>
                    </div>

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'invExpiringTable'])

                    <div class="table-responsive">
                        <table id="invExpiringTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 165px">Mã Ống Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th class="text-right" style="width: 125px">Tồn Cuối Kỳ</th>
                                    <th class="text-right" style="width: 125px">Tổng Tồn Theo Lô</th>
                                    <th class="text-center" style="width: 120px">Hạn Nhà Sản Xuất</th>
                                    <th class="text-center" style="width: 120px">Hạn Nội Bộ</th>
                                    <th class="text-center" style="width: 140px">Hạn Áp Dụng</th>
                                    <th class="text-center" style="width: 115px">Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invExpiringSoon as $row)
                                    <tr data-groups="{{ $invGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="inv-code">{{ $row->code }}</span>
                                            @if ($row->batch_no)
                                                <div class="md-sub">Lô {{ $row->batch_no }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                                <span class="sgr-version ml-1">v{{ $row->category_version }}</span>
                                                @if ($row->supplier_name)
                                                    <span class="ml-1">· {{ $row->supplier_name }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->remaining }}">
                                            <span class="inv-remaining">{{ $invNum($row->remaining) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->batch_remaining }}">
                                            <span class="inv-group-total">{{ $invNum($row->batch_remaining) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                            <div class="md-sub">{{ $row->batch_codes }} ống</div>
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                            @if ($invIsCheckOnline($row))
                                                <span class="badge badge-warning"
                                                    title="Hạn dùng chưa xác định từ NSX. Tra cứu trực tuyến khi sử dụng.">
                                                    <i class="fas fa-globe"></i> Check online
                                                </span>
                                            @else
                                                {{ $invDate($row->expired_date) }}
                                            @endif
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->internal_expired_date ?: '9999-12-31' }}">
                                            @if ($row->internal_expired_date)
                                                {{ $invDate($row->internal_expired_date) }}
                                            @else
                                                <span class="md-empty">Chưa xác định</span>
                                            @endif
                                        </td>
                                        <td class="text-center"
                                            data-order="{{ $row->effective_expired_date ?: '9999-12-31' }}">
                                            <div class="font-weight-bold">{{ $invDate($row->effective_expired_date) }}
                                            </div>
                                            @if ($row->days_to_effective_expiry < 0)
                                                <span class="inv-countdown is-over">Quá
                                                    {{ abs($row->days_to_effective_expiry) }} ngày</span>
                                            @else
                                                <span
                                                    class="inv-countdown {{ $row->days_to_effective_expiry <= $nearExpiryDays ? 'is-near' : '' }}">Còn
                                                    {{ $row->days_to_effective_expiry }} ngày</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="inv-badge inv-badge-{{ $row->state }}">{{ $row->state_label }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ CHƯA XÁC ĐỊNH HẠN DÙNG NỘI BỘ ============ --}}
                <div class="inv-pane" id="invPaneInternal">

                    <div class="md-toolbar">
                        <p class="hint inv-blocking">
                            <i class="fas fa-ban mr-1"></i>
                            Các ống chuẩn dưới đây <b>chưa được sử dụng</b>: chất chuẩn có khai báo hạn dùng mặc định
                            trong Danh Mục nhưng chưa xác định hạn dùng nội bộ (hạn tính từ ngày mở ống). Màn hình Sử
                            Dụng Chất Chuẩn không cho chọn những ống này cho tới khi bấm <b>Xác định</b>.
                        </p>
                    </div>

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'invInternalTable'])

                    <div class="table-responsive">
                        <table id="invInternalTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 165px">Mã Ống Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th class="text-right" style="width: 130px">Tồn Cuối Kỳ</th>
                                    <th class="text-center" style="width: 110px">Ngày Nhập</th>
                                    <th class="text-center" style="width: 120px">Hạn Nhà Sản Xuất</th>
                                    <th class="text-center" style="width: 130px">Hạn Dùng Mặc Định</th>
                                    <th class="text-center" style="width: 150px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invWaitingInternal as $row)
                                    <tr data-groups="{{ $invGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="inv-code">{{ $row->code }}</span>
                                            @if ($row->batch_no)
                                                <div class="md-sub">Lô {{ $row->batch_no }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                                <span class="sgr-version ml-1">v{{ $row->category_version }}</span>
                                            </div>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->gap }}">
                                            <span
                                                class="inv-remaining {{ $row->remaining > 0 ? '' : 'is-zero' }}">{{ $invNum($row->gap) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->imported_date ?: '9999-12-31' }}">
                                            {{ $invDate($row->imported_date) }}
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                            @if ($invIsCheckOnline($row))
                                                <span class="badge badge-warning"
                                                    title="Hạn dùng chưa xác định từ NSX. Tra cứu trực tuyến khi sử dụng.">
                                                    <i class="fas fa-globe"></i> Check online
                                                </span>
                                            @else
                                                {{ $invDate($row->expired_date) }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="md-tag">{{ $row->shelf_life_months }} tháng</span>
                                        </td>
                                        <td class="text-center">
                                            @perm('inventory_standard_internalExpiry')
                                                <button type="button" class="btn btn-sm btn-primary btn-inv-internal"
                                                    title="Xác định hạn dùng nội bộ cho ống chuẩn này"
                                                    data-row="{{ json_encode([
                                                        'import_id' => $row->id,
                                                        'code' => $row->code,
                                                        'chem_name' => $row->standard_name,
                                                        'shelf_life_months' => $row->shelf_life_months,
                                                        'expired_date' => $row->expired_date,
                                                        'internal_expired_date' => $row->internal_expired_date,
                                                    ]) }}">
                                                    <i class="fas fa-hourglass-half mr-1"></i> Xác định
                                                </button>
                                            @endperm
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ KIỂM SOÁT KHỐI LƯỢNG ============ --}}
                <div class="inv-pane" id="invPaneWeight">
                    <div class="md-toolbar">
                        <p class="hint">
                            <i class="fas fa-balance-scale mr-1"></i>
                            Các ống chuẩn có khai báo <b>Kiểm soát khối lượng</b>. Độ lệch = |Khối lượng thực - Tổng lượng xuất| / Tổng lượng xuất * 100%. Nếu đơn vị là ml hoặc g sẽ được quy đổi sang mg.
                        </p>
                    </div>

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'invWeightTable'])

                    <div class="table-responsive">
                        <table id="invWeightTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px">STT</th>
                                    <th style="width: 150px">Mã Ống Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th style="width: 130px">Dạng Chuẩn</th>
                                    <th class="text-right" style="width: 130px">Qui Cách (Thực)</th>
                                    <th class="text-right" style="width: 120px">Tổng Xuất</th>
                                    <th class="text-center" style="width: 110px">Độ Lệch (%)</th>
                                    <th class="text-center" style="width: 110px">Giới Hạn (%)</th>
                                    <th class="text-center" style="width: 100px">Kết Quả</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invWeightControlled as $row)
                                    <tr data-groups="{{ $invGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="inv-code">{{ $row->code }}</span>
                                            @if ($row->batch_no)
                                                <div class="md-sub">Lô {{ $row->batch_no }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                                <span class="sgr-version ml-1">v{{ $row->category_version }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            {{ $row->standard_form ?: '—' }}
                                        </td>
                                        <td class="text-right" data-order="{{ $row->imported }}">
                                            <span class="inv-amount">{{ $invNum($row->imported) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->used }}">
                                            <span class="inv-amount">{{ $invNum($row->used) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="text-center font-weight-bold {{ $row->used > 0 && $row->deviation > $row->ghkl ? 'text-danger' : 'text-success' }}" data-order="{{ $row->deviation }}">
                                            {{ $row->used > 0 ? $row->deviation . '%' : '—' }}
                                        </td>
                                        <td class="text-center" data-order="{{ $row->ghkl }}">
                                            <span class="md-tag">{{ $row->ghkl }}%</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($row->used == 0)
                                                <span class="md-empty">Chưa tính</span>
                                            @elseif ($row->deviation > $row->ghkl)
                                                <span class="badge badge-danger">Ngoài giới hạn</span>
                                                @perm('inventory_standard_weight')
                                                    <button type="button" class="btn btn-sm btn-outline-primary ml-1 btn-weight-remark"
                                                        data-id="{{ $row->id }}"
                                                        data-remark="{{ $row->weight_deviation_remark ?? '' }}"
                                                        title="Nhận xét">
                                                        <i class="fas fa-comment"></i>
                                                    </button>
                                                @endperm
                                                @if($row->weight_deviation_remark)
                                                    <div class="md-sub mt-1 text-danger text-left font-italic">{{ $row->weight_deviation_remark }}</div>
                                                @endif
                                            @else
                                                <span class="badge badge-success">Không ngoài giới hạn</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bảng dùng chung sắp theo cột 1 tăng dần, tồn kho cần xem ống sắp hết hạn trước
        // (cột 11 = Hạn Dùng)
        $('#mdTable').DataTable().order([11, 'asc']).draw();

        /*
        | JS dùng chung của nhóm Tồn viết câu "bảng rỗng" theo hoá chất. Màn này là chất
        | chuẩn nên nói lại cho đúng màn hình người dùng cần vào.
        */
        var sdEmpty = {
            '#mdTable': 'Chưa có ống chuẩn nào để tính tồn kho. Hãy nhập ở màn hình Nhập Chất Chuẩn.',
            '#invSummaryTable': 'Chưa có chất chuẩn nào trong kho.',
            '#invZoneTable': 'Chưa có ống chuẩn nào ở định khu đang chọn.',
            '#invExpiringTable': 'Không có ống chuẩn nào còn tồn mà sắp hết hạn.',
            '#invInternalTable': 'Tất cả chất chuẩn có khai báo hạn dùng mặc định đều đã xác định hạn dùng nội bộ.',
            '#invWeightTable': 'Không có ống chuẩn nào có khai báo kiểm soát khối lượng.',
        };

        Object.keys(sdEmpty).forEach(function(selector) {
            if (!$.fn.dataTable.isDataTable(selector)) return;

            var table = $(selector).DataTable();

            table.settings()[0].oLanguage.sEmptyTable = sdEmpty[selector];
            table.draw(false);
        });
    });
</script>
