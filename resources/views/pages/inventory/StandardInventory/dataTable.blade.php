@include('pages.inventory.shared.assets')
@php $activeTab = \App\Support\PageTabs::active('inventory_standard', $activeTab ?? 'code'); @endphp

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
                    @tab('inventory_standard', 'code')
                    <button type="button" class="inv-tab {{ $activeTab === 'code' ? 'is-active' : '' }}" data-pane="invPaneCode">
                        <i class="fas fa-barcode mr-1"></i> Theo mã ống chuẩn
                    </button>
                    @endtab
                    @tab('inventory_standard', 'standard')
                    <button type="button" class="inv-tab {{ $activeTab === 'standard' ? 'is-active' : '' }}" data-pane="invPaneChem">
                        <i class="fas fa-vial-circle-check mr-1"></i> Theo chất chuẩn
                    </button>
                    @endtab
                    @tab('inventory_standard', 'zone')
                    <button type="button" class="inv-tab {{ $activeTab === 'zone' ? 'is-active' : '' }}" data-pane="invPaneZone">
                        <i class="fas fa-map-location-dot mr-1"></i> Theo định khu
                    </button>
                    @endtab
                    @tab('inventory_standard', 'expiring')
                    <button type="button" class="inv-tab {{ $activeTab === 'expiring' ? 'is-active' : '' }}" data-pane="invPaneExpiring">
                        <i class="fas fa-calendar-xmark mr-1"></i> Hạn dùng dưới {{ $expiringSoonMonths }} tháng
                        <span class="inv-tab-count">{{ $invExpiringSoon->count() }}</span>
                    </button>
                    @endtab
                    @tab('inventory_standard', 'internal')
                    <button type="button" class="inv-tab {{ $activeTab === 'internal' ? 'is-active' : '' }}" data-pane="invPaneInternal">
                        <i class="fas fa-hourglass-half mr-1"></i> Chưa có hạn nội bộ
                        <span class="inv-tab-count">{{ $invWaitingInternal->count() }}</span>
                    </button>
                    @endtab
                    @tab('inventory_standard', 'weight')
                    <button type="button" class="inv-tab {{ $activeTab === 'weight' ? 'is-active' : '' }}" data-pane="invPaneWeight">
                        <i class="fas fa-balance-scale mr-1"></i> Kiểm soát Khối lượng
                        <span class="inv-tab-count">{{ $invWeightControlled->count() }}</span>
                    </button>
                    @endtab
                </div>

                {{-- ============ TỒN THEO TỪNG MÃ ỐNG CHUẨN ============ --}}
                @tab('inventory_standard', 'code')
                <div class="inv-pane {{ $activeTab === 'code' ? 'is-active' : '' }}" id="invPaneCode">

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
                        <table id="mdTable" class="table table-bordered table-hover w-100" data-deferred>
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
                                    <th class="text-center" style="width: 60px" title="File hồ sơ đính kèm của phiếu nhập">
                                        <i class="fas fa-paperclip"></i></th>
                                    <th class="text-center" style="width: 190px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @deferredRows('mdTable')
                                @foreach ($datas as $row)
                                    {{-- data-groups để bộ lọc Phân nhóm chuẩn nhận ra dòng này --}}
                                    <tr data-state="{{ $row->state }}" data-groups="{{ $invGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="inv-code">{{ $row->code }}</span>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                @if ($row->group_code)
                                                    <span class="sd-group-tag">{{ $invGroupName($row->group_code) }}</span>
                                                @endif
                                                @if ($row->batch_no)
                                                    <span class="{{ $row->group_code ? 'ml-1' : '' }}">Lô {{ $row->batch_no }}</span>
                                                @endif
                                                @if ($row->manufacturer_short_name)
                                                    <span class="ml-1">· {{ $row->manufacturer_short_name }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        {{-- Vị trí THỰC TẾ của ống, sắp xếp theo đường dẫn định khu --}}
                                        <td class="md-sub"
                                            data-order="{{ $row->location_code ? $row->warehouse_name . '/' . $row->shelf_name . '/' . $row->column_name . '/' . $row->tier_name . '/' . $row->location_code : 'zzz' }}">
                                            @if ($row->location_code)
                                                <div class="font-weight-bold">
                                                    {{ \App\Support\ZoneType::tag($row->location_code, $row->location_zone_type ?? null) }}
                                                </div>
                                            @else
                                                <span class="inv-zone-none">Chưa xếp vị trí</span>
                                            @endif
                                        </td>
                                        {{-- Tồn đầu kỳ: phát sinh trước ngày bắt đầu kỳ --}}
                                        <td class="text-right" data-order="{{ $row->opening }}">
                                            <button type="button" class="{{ $mvCls($row->opening) }}" data-scope="import"
                                                data-id="{{ $row->id }}" data-metric="opening">{{ $invNum($row->opening) }}</button>
                                            <div class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</div>
                                            @if ($row->is_new_in_period)
                                                <div class="md-sub"><span class="inv-period-tag">Mới nhập trong
                                                        kỳ</span></div>
                                            @endif
                                        </td>
                                        {{-- Nhập trong kỳ: phiếu nhập + số cân đối ghi trong kỳ --}}
                                        <td class="text-right" data-order="{{ $row->period_in }}">
                                            <button type="button" class="{{ $mvCls($row->period_in) }}" data-scope="import"
                                                data-id="{{ $row->id }}" data-metric="period_in">{{ $invNum($row->period_in) }}</button>
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
                                            <button type="button" class="{{ $mvCls($row->period_used, 'out') }}" data-scope="import"
                                                data-id="{{ $row->id }}" data-metric="period_used">{{ $invNum($row->period_used) }}</button>
                                            <div class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</div>
                                            @if ($row->period_export_times > 0)
                                                <div class="md-sub">{{ $row->period_export_times }} lần xuất</div>
                                            @endif
                                        </td>
                                        {{-- Huỷ trong kỳ: standard_exports type = 'cancel' --}}
                                        <td class="text-right" data-order="{{ $row->period_cancelled }}">
                                            <button type="button" class="{{ $mvCls($row->period_cancelled, 'out') }}" data-scope="import"
                                                data-id="{{ $row->id }}" data-metric="period_cancelled">{{ $invNum($row->period_cancelled) }}</button>
                                            <div class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</div>
                                            @if ($row->period_transferred > 0)
                                                <div class="md-sub">Chuyển LPB:
                                                    <button type="button" class="{{ $mvCls($row->period_transferred, 'out') }}" data-scope="import"
                                                        data-id="{{ $row->id }}" data-metric="period_transferred">{{ $invNum($row->period_transferred) }}</button>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-right" data-order="{{ $row->gap }}">
                                            {{-- Tồn âm (đã xuất vượt) hiện đúng số âm để thấy phần phải cân đối --}}
                                            <button type="button"
                                                class="{{ $mvCls($row->gap) }} {{ $row->state === 'over' ? 'is-out' : '' }}"
                                                data-scope="import" data-id="{{ $row->id }}" data-metric="closing">{{ $invNum($row->gap) }}</button>
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
                                            <button type="button" class="{{ $mvCls($row->batch_remaining, 'in') }}" data-scope="category"
                                                data-id="{{ $row->category_id }}" data-metric="stock_batch"
                                                data-batch="{{ $row->batch_no }}">{{ $invNum($row->batch_remaining) }}</button>
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
                                            <button type="button" class="{{ $mvCls($row->category_remaining, 'in') }}" data-scope="category"
                                                data-id="{{ $row->category_id }}" data-metric="stock_category">{{ $invNum($row->category_remaining) }}</button>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                            <div class="md-sub">
                                                {{ $row->category_batches }} lô · {{ $row->category_codes }} ống
                                            </div>
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                            @include('pages.inventory.StandardInventory.expiryBadge', ['row' => $row])
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
                                            @include('pages.shared.attachmentList', [
                                                'attachments' => $attachments->get($row->id) ?? collect(),
                                                'routePrefix' => 'pages.inventory.standardInventory.',
                                                'uploadPerm' => 'import_standard_attachment_upload',
                                                'parentId' => $row->id,
                                                'statusPerm' => 'import_standard_attachment_status',
                                                'code' => $row->code,
                                                'name' => $row->standard_name,
                                                'typeLabel' => 'Chất chuẩn',
                                            ])
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
                                @enddeferredRows
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ TỒN CỘNG DỒN THEO CHẤT CHUẨN ============ --}}
                @tab('inventory_standard', 'standard')
                <div class="inv-pane {{ $activeTab === 'standard' ? 'is-active' : '' }}" id="invPaneChem">

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'invSummaryTable'])

                    <div class="table-responsive">
                        <table id="invSummaryTable" class="table table-bordered table-hover w-100" data-deferred>
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
                                @deferredRows('invSummaryTable')
                                @foreach ($summaries as $sum)
                                    <tr data-groups="{{ $invGroups($sum->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $sum->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $sum->category_code ?: '—' }}</span>
                                                @if ($sum->cas_no)
                                                    <span class="ml-1">CAS: {{ $sum->cas_no }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $sum->code_count }}">
                                            {{ $sum->in_stock_count }}/{{ $sum->code_count }} còn tồn
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->opening }}">
                                            <button type="button" class="{{ $mvCls($sum->opening) }}" data-scope="category"
                                                data-id="{{ $sum->category_id }}" data-metric="opening">{{ $invNum($sum->opening) }}</button>
                                            <div class="md-sub">{{ $sum->unit }}</div>
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->period_in }}">
                                            <button type="button" class="{{ $mvCls($sum->period_in) }}" data-scope="category"
                                                data-id="{{ $sum->category_id }}" data-metric="period_in">{{ $invNum($sum->period_in) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->period_used }}">
                                            <button type="button" class="{{ $mvCls($sum->period_used, 'out') }}" data-scope="category"
                                                data-id="{{ $sum->category_id }}" data-metric="period_used">{{ $invNum($sum->period_used) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->period_cancelled }}">
                                            <button type="button" class="{{ $mvCls($sum->period_cancelled, 'out') }}" data-scope="category"
                                                data-id="{{ $sum->category_id }}" data-metric="period_cancelled">{{ $invNum($sum->period_cancelled) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $sum->closing }}">
                                            <button type="button" class="{{ $mvCls($sum->closing) }}" data-scope="category"
                                                data-id="{{ $sum->category_id }}" data-metric="closing">{{ $invNum($sum->closing) }}</button>
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
                                @enddeferredRows
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ TỒN THEO ĐỊNH KHU ============ --}}
                @tab('inventory_standard', 'zone')
                <div class="inv-pane {{ $activeTab === 'zone' ? 'is-active' : '' }}" id="invPaneZone">

                    {{-- Sơ đồ thẻ Kho/Phòng -> Kệ/Tủ -> Cột -> Tầng -> Vị trí như Tồn Kho Vật Tư --}}
                    @include('pages.inventory.shared.zoneGrid', [
                        'zgId' => 'invZoneMap',
                        'zgItem' => 'chất chuẩn',
                        'zgCode' => 'Ống chuẩn',
                    ])

                    @php
                        // Chỉ ống CÒN TỒN mới nằm trên sơ đồ, gọn đúng các trường thẻ + modal dùng
                        $invZoneRows = $datas
                            ->filter(fn($row) => $row->remaining > 0)
                            ->map(fn($row) => [
                                'code' => $row->code,
                                'category_id' => (int) $row->category_id,
                                'name' => $row->standard_name,
                                'sub' => implode(' · ', array_filter([
                                    $row->category_code,
                                    $row->batch_no ? 'Lô ' . $row->batch_no : null,
                                    $row->manufacturer_short_name,
                                ])),
                                'remaining' => (float) $row->remaining,
                                'unit' => $row->unit_short_name ?: $row->unit_name,
                                'expired' => $row->effective_expired_date,
                                'state' => $row->state,
                                'state_label' => $row->state_label,
                                'location_id' => $row->location_id ? (int) $row->location_id : null,
                                'location_code' => $row->location_code,
                                'location_zone_type' => $row->location_zone_type,
                                'warehouse_id' => $row->warehouse_id,
                                'shelf_id' => $row->shelf_id,
                                'column_id' => $row->column_id,
                                'tier_id' => $row->tier_id,
                                'warehouse_name' => $row->warehouse_name,
                                'shelf_name' => $row->shelf_name,
                                'column_name' => $row->column_name,
                                'tier_name' => $row->tier_name,
                            ])
                            ->values();
                    @endphp
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            window.invZoneGrid('#invZoneMap', {
                                zones: @json($zones),
                                rows: @json($invZoneRows)
                            });
                        });
                    </script>
                </div>
                @endtab

                {{-- ============ HẠN DÙNG DƯỚI 6 THÁNG ============ --}}
                @tab('inventory_standard', 'expiring')
                <div class="inv-pane {{ $activeTab === 'expiring' ? 'is-active' : '' }}" id="invPaneExpiring">

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'invExpiringTable'])

                    <div class="table-responsive">
                        <table id="invExpiringTable" class="table table-bordered table-hover w-100" data-deferred>
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
                                @deferredRows('invExpiringTable')
                                @foreach ($invExpiringSoon as $row)
                                    <tr data-groups="{{ $invGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="inv-code">{{ $row->code }}</span>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                            </div>
                                            @if ($row->batch_no)
                                                <div class="md-sub">Lô {{ $row->batch_no }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                @if ($row->group_code)
                                                    <span class="sd-group-tag">{{ $invGroupName($row->group_code) }}</span>
                                                @endif
                                                @if ($row->supplier_name)
                                                    <span class="{{ $row->group_code ? 'ml-1' : '' }}">· {{ $row->supplier_name }}</span>
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
                                            @include('pages.inventory.StandardInventory.expiryBadge', ['row' => $row, 'countdown' => false])
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
                                @enddeferredRows
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ CHƯA XÁC ĐỊNH HẠN DÙNG NỘI BỘ ============ --}}
                @tab('inventory_standard', 'internal')
                <div class="inv-pane {{ $activeTab === 'internal' ? 'is-active' : '' }}" id="invPaneInternal">

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'invInternalTable'])

                    <div class="table-responsive">
                        <table id="invInternalTable" class="table table-bordered table-hover w-100" data-deferred>
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
                                @deferredRows('invInternalTable')
                                @foreach ($invWaitingInternal as $row)
                                    <tr data-groups="{{ $invGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="inv-code">{{ $row->code }}</span>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                            </div>
                                            @if ($row->batch_no)
                                                <div class="md-sub">Lô {{ $row->batch_no }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            @if ($row->group_code)
                                                <div class="md-sub">
                                                    <span class="sd-group-tag">{{ $invGroupName($row->group_code) }}</span>
                                                </div>
                                            @endif
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
                                            @include('pages.inventory.StandardInventory.expiryBadge', ['row' => $row, 'countdown' => false])
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
                                @enddeferredRows
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ KIỂM SOÁT KHỐI LƯỢNG ============ --}}
                @tab('inventory_standard', 'weight')
                <div class="inv-pane {{ $activeTab === 'weight' ? 'is-active' : '' }}" id="invPaneWeight">

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'invWeightTable'])

                    <div class="table-responsive">
                        <table id="invWeightTable" class="table table-bordered table-hover w-100" data-deferred>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px">STT</th>
                                    <th style="width: 150px">Mã Ống Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th style="width: 130px">Dạng Chuẩn</th>
                                    <th class="text-right" style="width: 130px">Qui Cách (Thực)</th>
                                    <th class="text-right" style="width: 120px">Tổng Xuất</th>
                                    <th class="text-right" style="width: 150px">Khối Lượng Bì + Chuẩn Trước Khi Dùng</th>
                                    <th class="text-right" style="width: 160px">Khối Lượng Bì Sau Khi Sử Dụng</th>
                                    <th class="text-center" style="width: 110px">Độ Lệch (%)</th>
                                    <th class="text-center" style="width: 110px">Giới Hạn (%)</th>
                                    <th class="text-center" style="width: 100px">Kết Quả</th>
                                </tr>
                            </thead>
                            <tbody>
                                @deferredRows('invWeightTable')
                                @foreach ($invWeightControlled as $row)
                                    <tr data-groups="{{ $invGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="inv-code">{{ $row->code }}</span>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                            </div>
                                            @if ($row->batch_no)
                                                <div class="md-sub">Lô {{ $row->batch_no }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            @if ($row->group_code)
                                                <div class="md-sub">
                                                    <span class="sd-group-tag">{{ $invGroupName($row->group_code) }}</span>
                                                </div>
                                            @endif
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
                                        <td class="text-right" data-order="{{ $row->gross_weight_before ?? -1 }}">
                                            @if ($row->gross_weight_before !== null)
                                                <span class="inv-amount">{{ $invNum($row->gross_weight_before) }}</span>
                                                <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                            @else
                                                <span class="md-empty">Chưa cân</span>
                                            @endif
                                        </td>
                                        <td class="text-right" data-order="{{ $row->tare_weight_after ?? -1 }}">
                                            @if ($row->tare_weight_after !== null)
                                                <span class="inv-amount">{{ $invNum($row->tare_weight_after) }}</span>
                                                <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                            @else
                                                <span class="md-empty">Chưa cân</span>
                                            @endif
                                            @perm('inventory_standard_weight')
                                                <div class="mt-1">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary btn-tare-weight"
                                                        data-id="{{ $row->id }}"
                                                        data-code="{{ $row->code }}"
                                                        data-gross="{{ $row->gross_weight_before !== null ? $invNum($row->gross_weight_before) : '' }}"
                                                        data-tare="{{ $row->tare_weight_after !== null ? $invNum($row->tare_weight_after) : '' }}"
                                                        data-unit="{{ $row->unit_short_name ?: $row->unit_name }}"
                                                        title="{{ $row->gross_weight_before === null ? 'Chưa có Khối lượng Bì + Chuẩn, nhập ở lần sử dụng đầu tiên của ống' : 'Nhập khối lượng bì sau khi sử dụng' }}"
                                                        {{ $row->gross_weight_before === null ? 'disabled' : '' }}>
                                                        <i class="fas fa-balance-scale mr-1"></i> Nhập
                                                    </button>
                                                </div>
                                            @endperm
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
                                @enddeferredRows
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

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
            '#invExpiringTable': 'Không có ống chuẩn nào còn tồn mà sắp hết hạn.',
            '#invInternalTable': 'Tất cả chất chuẩn có khai báo hạn dùng mặc định đều đã xác định hạn dùng nội bộ.',
            '#invWeightTable': 'Không có ống chuẩn nào có khai báo kiểm soát khối lượng.',
        };

        // Bảng nạp sau (data-deferred) giữ "Đang tải..." tới khi dòng về rồi mới dùng câu này
        Object.keys(sdEmpty).forEach(function(selector) {
            mdDeferred.setEmpty(selector, sdEmpty[selector]);
        });
    });
</script>
