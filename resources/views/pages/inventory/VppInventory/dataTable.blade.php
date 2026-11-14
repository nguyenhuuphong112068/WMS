@include('pages.inventory.shared.assets')
@php $activeTab = \App\Support\PageTabs::active('inventory_vpp', $activeTab ?? 'code'); @endphp

<style>
    .mi-tabs { display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap; }
    .mi-tab {
        border: 1px solid var(--primary-soft); background: #fff; color: var(--text-main);
        border-radius: var(--border-radius-md); padding: 8px 16px; font-weight: 600; cursor: pointer;
        transition: all .2s;
    }
    .mi-tab.is-active { background: linear-gradient(135deg, var(--primary-light), var(--primary)); color: #fff; }
    .mi-pane { display: none; }
    .mi-pane.is-active { display: block; }
    .mi-chip {
        display: inline-block; border: 1px solid var(--primary-soft); background: #fff; border-radius: 999px;
        padding: 4px 12px; margin: 0 4px 6px 0; font-size: .8rem; font-weight: 600; cursor: pointer;
    }
    .mi-chip.is-active { background: var(--primary); color: #fff; border-color: var(--primary); }
    .mi-badge { display: inline-block; border-radius: 999px; padding: 2px 10px; font-size: .74rem; font-weight: 700; }
    .mi-badge.in { background: #DCFCE7; color: #166534; }
    .mi-badge.low { background: #FEF9C3; color: #854D0E; }
    .mi-badge.near { background: #FFEDD5; color: #9A3412; }
    .mi-badge.expired { background: #FEE2E2; color: #991B1B; }
    .mi-badge.out { background: #E2E8F0; color: #475569; }
    .mi-badge.over { background: #FCE7F3; color: #9D174D; }
    .mi-badge.quarantine { background: #FFEDD5; color: #C2410C; }
    .mi-badge.rejected { background: #FEE2E2; color: #B91C1C; }

    /* ---------- Cột THAO TÁC ---------- */
    .mi-actions { display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .mi-act-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; padding: 0; line-height: 1;
        border-radius: var(--border-radius-md); font-size: .85rem;
        border: 1px solid var(--primary); background: #fff; color: var(--primary);
        transition: all .2s;
    }
    .mi-act-btn:hover, .mi-act-btn:focus {
        background: var(--primary); color: #fff; border-color: var(--primary-dark);
        transform: translateY(-1px); box-shadow: var(--shadow-sm); outline: none;
    }
    .mi-act-count {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 32px; height: 32px; padding: 0 8px; line-height: 1;
        border-radius: var(--border-radius-md); font-size: .78rem; font-weight: 700;
        border: 1px solid var(--primary-lighter); background: var(--primary-soft); color: var(--primary);
        transition: all .2s;
    }
    .mi-act-count:hover, .mi-act-count:focus {
        background: var(--primary-lighter); color: var(--primary-dark);
        border-color: var(--primary); transform: translateY(-1px); outline: none;
    }
</style>

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                {{-- ============ KỲ BÁO CÁO ============ --}}
                <form method="GET" action="{{ route('pages.inventory.vppInventory.list') }}" class="inv-period"
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

                <div class="mi-tabs">
                    @tab('inventory_vpp', 'code')
                    <button type="button" class="mi-tab {{ $activeTab === 'code' ? 'is-active' : '' }}" data-pane="miPaneCode"><i class="fas fa-boxes mr-1"></i>Theo mã xuất nhập</button>
                    @endtab
                    @tab('inventory_vpp', 'material')
                    <button type="button" class="mi-tab {{ $activeTab === 'material' ? 'is-active' : '' }}" data-pane="miPaneMat"><i class="fas fa-layer-group mr-1"></i>Tồn Theo mã VPP</button>
                    @endtab
                    @tab('inventory_vpp', 'zone')
                    <button type="button" class="mi-tab {{ $activeTab === 'zone' ? 'is-active' : '' }}" data-pane="miPaneZone"><i class="fas fa-map-marked-alt mr-1"></i>Tồn kho theo vị trí</button>
                    @endtab
                    @tab('inventory_vpp', 'expiring')
                    <button type="button" class="mi-tab {{ $activeTab === 'expiring' ? 'is-active' : '' }}" data-pane="miPaneExpiring">
                        <i class="fas fa-calendar-xmark mr-1"></i>Cận hạn dùng
                        <span class="badge badge-light ml-1">{{ $invExpiringSoon->count() }}</span>
                    </button>
                    @endtab
                    @tab('inventory_vpp', 'next')
                    <button type="button" class="mi-tab {{ $activeTab === 'next' ? 'is-active' : '' }}" data-pane="miPaneNext">
                        <i class="fas fa-calendar-check mr-1"></i>Khả dụng {{ $forecast['month']['label'] }}
                        @if ($forecast['summary']['short'] > 0)
                            <span class="badge badge-danger ml-1">{{ $forecast['summary']['short'] }} mã không đáp ứng</span>
                        @elseif ($forecast['summary']['tight'] > 0)
                            <span class="badge badge-warning ml-1">{{ $forecast['summary']['tight'] }} mã vừa đủ</span>
                        @endif
                    </button>
                    @endtab
                    @tab('inventory_vpp', 'check')
                    <button type="button" class="mi-tab {{ $activeTab === 'check' ? 'is-active' : '' }}" data-pane="miPaneCheck">
                        <i class="fas fa-clipboard-check mr-1"></i>Kiểm kê định kỳ
                        @if ($stocktake['canOpen'])
                            <span class="badge badge-warning ml-1">Chưa kiểm kê {{ $stocktake['periodLabel'] }}</span>
                        @elseif ($stocktake['current']->state === 'counting')
                            <span class="badge badge-light ml-1">{{ $stocktake['progress']['counted'] }}/{{ $stocktake['progress']['total'] }}</span>
                        @endif
                    </button>
                    @endtab
                </div>

                @include('pages.inventory.VppInventory.classificationFilter')

                {{-- ============ THEO MÃ XUẤT NHẬP ============ --}}
                @tab('inventory_vpp', 'code')
                <div class="mi-pane {{ $activeTab === 'code' ? 'is-active' : '' }}" id="miPaneCode">

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100" data-deferred>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:45px">STT</th>
                                    <th style="width:150px">Mã Xuất Nhập</th>
                                    <th>VPP</th>
                                    <th style="width:120px">Nhà Sản Xuất</th>
                                    <th style="width:150px">Vị Trí</th>
                                    <th class="text-center" style="width:70px">Đơn Vị</th>
                                    <th class="text-right" style="width:90px">Tồn Đầu Kỳ</th>
                                    <th class="text-right" style="width:90px">Nhập Trong Kỳ</th>
                                    <th class="text-right" style="width:90px">Sử Dụng</th>
                                    <th class="text-right" style="width:80px">Cách Ly/Huỷ</th>
                                    <th class="text-right" style="width:95px">Tồn Cuối Kỳ</th>
                                    <th class="text-right" style="width:100px">Tổng Tồn VPP</th>
                                    <th class="text-center" style="width:95px">Hạn Dùng</th>
                                    <th class="text-center" style="width:95px">Trạng Thái</th>
                                    <th class="text-center" style="width:55px" title="File hồ sơ đính kèm của phiếu nhập">
                                        <i class="fas fa-paperclip"></i></th>
                                    <th class="text-center" style="width:110px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @deferredRows('mdTable')
                                @foreach ($datas as $row)
                                    <tr data-classification="{{ \App\Support\VppClassification::filterKeys($row->classification, $row->purchasing_department) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td data-order="{{ $row->code }}">
                                            <span class="inv-code font-weight-bold">{{ $row->code }}</span>
                                            <div class="md-sub"><span class="md-tag">{{ $row->category_code ?: '—' }}</span></div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->material_name ?: '—' }}</div>
                                        </td>
                                        <td class="md-sub" title="{{ $row->manufacturer_name }}">
                                            {{ $row->manufacturer_short_name ?: '—' }}
                                        </td>
                                        <td class="md-sub">
                                            @if ($row->location_code)
                                                {{ \App\Support\ZoneType::tag($row->location_code, $row->location_zone_type ?? null) }}
                                            @else <span class="text-muted">—</span> @endif
                                        </td>
                                        <td class="text-center md-sub">{{ $row->unit_short_name ?: ($row->unit_name ?: '—') }}</td>
                                        <td class="text-right" data-order="{{ $row->opening }}">
                                            <button type="button" class="{{ $mvCls($row->opening) }}" data-scope="import"
                                                data-id="{{ $row->id }}" data-metric="opening">{{ $invNum($row->opening) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->period_in }}">
                                            <button type="button" class="{{ $mvCls($row->period_in) }}" data-scope="import"
                                                data-id="{{ $row->id }}" data-metric="period_in">{{ $invNum($row->period_in) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->period_used }}">
                                            <button type="button" class="{{ $mvCls($row->period_used, 'use') }}" data-scope="import"
                                                data-id="{{ $row->id }}" data-metric="period_used">{{ $invNum($row->period_used) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->period_cancelled }}">
                                            <button type="button" class="{{ $mvCls($row->period_cancelled, 'out') }}" data-scope="import"
                                                data-id="{{ $row->id }}" data-metric="period_cancelled">{{ $invNum($row->period_cancelled) }}</button>
                                        </td>
                                        <td class="text-right font-weight-bold {{ $row->gap < 0 ? 'text-danger' : '' }}" data-order="{{ $row->gap }}">
                                            <button type="button" class="{{ $mvCls($row->gap) }}" data-scope="import"
                                                data-id="{{ $row->id }}" data-metric="closing">{{ $invNum($row->gap) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->category_remaining }}">
                                            <button type="button" class="{{ $mvCls($row->category_remaining, 'in') }}" data-scope="category"
                                                data-id="{{ $row->category_id }}" data-metric="stock_category">{{ $invNum($row->category_remaining) }}</button>
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $row->expired_date ?: '9999-12-31' }}">{{ $invDate($row->expired_date) }}</td>
                                        <td class="text-center"><span class="mi-badge {{ $row->state }}">{{ $row->state_label }}</span></td>
                                        <td class="text-center">
                                            @include('pages.shared.attachmentList', [
                                                'attachments' => $attachments->get($row->id) ?? collect(),
                                                'routePrefix' => 'pages.inventory.vppInventory.',
                                                'uploadPerm' => 'import_vpp_attachment_upload',
                                                'parentId' => $row->id,
                                                'statusPerm' => 'import_vpp_attachment_status',
                                                'code' => $row->code,
                                                'name' => $row->material_name,
                                                'typeLabel' => 'VPP',
                                            ])
                                        </td>
                                        <td class="text-center">
                                            <div class="mi-actions">
                                            @perm('inventory_vpp_balancing')
                                                <button type="button" class="mi-act-btn btn-mi-balancing" title="Cân đối"
                                                    data-row="{{ json_encode([
                                                        'id' => $row->id, 'code' => $row->code, 'material_name' => $row->material_name,
                                                        'unit' => $row->unit_short_name ?: $row->unit_name,
                                                        'imported' => $row->imported, 'gap' => $row->gap,
                                                    ]) }}">
                                                    <i class="fas fa-balance-scale"></i>
                                                </button>
                                            @endperm
                                            @if (($row->balancing_times ?? 0) > 0)
                                                <button type="button" class="mi-act-count btn-mi-history" title="Xem lịch sử cân đối"
                                                    data-id="{{ $row->id }}" data-code="{{ $row->code }}" data-name="{{ $row->material_name }}"
                                                    data-imported="{{ $invNum($row->imported) }}" data-balanced="{{ $invNum($row->balanced) }}"
                                                    data-gap="{{ $invNum($row->gap) }}">{{ $row->balancing_times }}</button>
                                            @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                @enddeferredRows
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ THEO VPP ============ --}}
                @tab('inventory_vpp', 'material')
                <div class="mi-pane {{ $activeTab === 'material' ? 'is-active' : '' }}" id="miPaneMat">
                    <div class="table-responsive">
                        <table id="miSummaryTable" class="table table-bordered table-hover w-100 md-table" data-deferred>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:45px">STT</th>
                                    <th style="width:130px">Mã VPP</th>
                                    <th>VPP</th>
                                    <th style="width:120px">Nhà Sản Xuất</th>
                                    <th class="text-center" style="width:90px">Số Lô</th>
                                    <th class="text-center" style="width:70px">Đơn Vị</th>
                                    <th class="text-right" style="width:100px">Tồn Đầu Kỳ</th>
                                    <th class="text-right" style="width:100px">Nhập Trong Kỳ</th>
                                    <th class="text-right" style="width:100px">Sử Dụng</th>
                                    <th class="text-right" style="width:90px">Cách Ly/Huỷ</th>
                                    <th class="text-right" style="width:110px">Tồn Cuối Kỳ</th>
                                    <th class="text-center" style="width:100px">Hạn Gần Nhất</th>
                                    <th class="text-center inv-chart-th" style="width:115px">Biểu Đồ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @deferredRows('miSummaryTable')
                                @foreach ($summaries as $s)
                                    <tr data-classification="{{ $s->classification_keys }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td><span class="md-tag">{{ $s->category_code ?: '—' }}</span></td>
                                        <td>
                                            <div class="font-weight-bold">{{ $s->material_name ?: '—' }}</div>
                                        </td>
                                        <td class="md-sub" title="{{ $s->manufacturer_name }}">
                                            {{ $s->manufacturer_short_name ?: '—' }}
                                        </td>
                                        <td class="text-center">{{ $s->in_stock_count }}/{{ $s->code_count }}</td>
                                        <td class="text-center md-sub">{{ $s->unit ?: '—' }}</td>
                                        <td class="text-right" data-order="{{ $s->opening }}">
                                            <button type="button" class="{{ $mvCls($s->opening) }}" data-scope="category"
                                                data-id="{{ $s->category_id }}" data-metric="opening">{{ $invNum($s->opening) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $s->period_in }}">
                                            <button type="button" class="{{ $mvCls($s->period_in) }}" data-scope="category"
                                                data-id="{{ $s->category_id }}" data-metric="period_in">{{ $invNum($s->period_in) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $s->period_used }}">
                                            <button type="button" class="{{ $mvCls($s->period_used, 'use') }}" data-scope="category"
                                                data-id="{{ $s->category_id }}" data-metric="period_used">{{ $invNum($s->period_used) }}</button>
                                        </td>
                                        <td class="text-right" data-order="{{ $s->period_cancelled }}">
                                            <button type="button" class="{{ $mvCls($s->period_cancelled, 'out') }}" data-scope="category"
                                                data-id="{{ $s->category_id }}" data-metric="period_cancelled">{{ $invNum($s->period_cancelled) }}</button>
                                        </td>
                                        <td class="text-right font-weight-bold" data-order="{{ $s->closing }}">
                                            <button type="button" class="{{ $mvCls($s->closing) }}" data-scope="category"
                                                data-id="{{ $s->category_id }}" data-metric="closing">{{ $invNum($s->closing) }}</button>
                                        </td>
                                        <td class="text-center md-sub">{{ $invDate($s->nearest_expiry) }}</td>
                                        {{-- Mở biểu đồ nhập - xuất - tồn của đúng VPP này, theo kỳ đang xem --}}
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-inv-chart"
                                                data-category="{{ $s->category_id }}"
                                                data-chem="{{ $s->material_name }}"
                                                title="Xem biểu đồ nhập - xuất - tồn của VPP này">
                                                <i class="fas fa-chart-bar mr-1"></i> Biểu đồ
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                @enddeferredRows
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ THEO VỊ TRÍ (SƠ ĐỒ THẺ) ============ --}}
                @tab('inventory_vpp', 'zone')
                <div class="mi-pane {{ $activeTab === 'zone' ? 'is-active' : '' }}" id="miPaneZone">
                    {{-- Sơ đồ thẻ Kho/Phòng -> Kệ/Tủ -> Cột -> Tầng -> Vị trí, cùng bộ vẽ với Tồn Kho Hoá Chất --}}
                    @include('pages.inventory.shared.zoneGrid', [
                        'zgId' => 'invZoneMap',
                        'zgItem' => 'VPP',
                        'zgCode' => 'Mã xuất nhập',
                        // Định khu nhanh + Thêm VPP vào ô + Đổi vị trí - VppInventoryController::assignLocation / moveLocation
                        'zgAssign' => user_can('inventory_vpp_location') ? [
                            'url' => route('pages.inventory.vppInventory.assignLocation'),
                            'moveUrl' => route('pages.inventory.vppInventory.moveLocation'),
                            'lookup' => route('pages.category.lookup.locations', [
                                'type' => 'vpp',
                                'except_zone' => implode(',', \App\Http\Controllers\Pages\Inventory\VppInventoryController::ASSIGN_EXCEPT_ZONES),
                            ]),
                            'max' => \App\Http\Controllers\Pages\Inventory\VppInventoryController::ASSIGN_MAX,
                            'except' => \App\Http\Controllers\Pages\Inventory\VppInventoryController::ASSIGN_EXCEPT_ZONES,
                        ] : null,
                    ])
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            window.invZoneGrid('#invZoneMap', {
                                zones: @json($zones),
                                rows: @json($zoneRows)
                            });
                        });
                    </script>
                </div>
                @endtab

                {{-- ============ CẬN HẠN DÙNG (HẠN DƯỚI 6 THÁNG) ============ --}}
                @tab('inventory_vpp', 'expiring')
                <div class="mi-pane {{ $activeTab === 'expiring' ? 'is-active' : '' }}" id="miPaneExpiring">

                    <div class="mb-2">
                        <span class="mi-chip is-active" data-expiry="all"
                            title="Còn tồn và hạn dùng dưới {{ $expiringSoonMonths }} tháng, kể cả đã quá hạn">
                            <i class="fas fa-layer-group"></i> Dưới {{ $expiringSoonMonths }} tháng
                            ({{ $invExpiringSoon->count() }})
                        </span>
                        @foreach ($expiringFilters as $key => $filter)
                            <span class="mi-chip" data-expiry="{{ $key }}" data-until="{{ $filter['until'] }}"
                                title="Hạn dùng đến hết {{ $invDate($filter['until']) }}, kể cả đã quá hạn">
                                <i class="fas fa-hourglass-end"></i> {{ $filter['label'] }}
                                ({{ $invExpiringCounts[$key] }})
                            </span>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table id="miExpiringTable" class="table table-bordered table-hover w-100" data-deferred>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:45px">STT</th>
                                    <th style="width:150px">Mã Xuất Nhập</th>
                                    <th>VPP</th>
                                    <th style="width:120px">Nhà Sản Xuất</th>
                                    <th style="width:150px">Vị Trí</th>
                                    <th class="text-center" style="width:70px">Đơn Vị</th>
                                    <th class="text-right" style="width:95px">Tồn Cuối Kỳ</th>
                                    <th class="text-right" style="width:100px">Tổng Tồn VPP</th>
                                    <th class="text-center" style="width:130px">Hạn Dùng</th>
                                    <th class="text-center" style="width:95px">Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @deferredRows('miExpiringTable')
                                @foreach ($invExpiringSoon as $row)
                                    <tr data-classification="{{ \App\Support\VppClassification::filterKeys($row->classification, $row->purchasing_department) }}"
                                        data-effective="{{ substr((string) $row->expired_date, 0, 10) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td data-order="{{ $row->code }}">
                                            <span class="inv-code font-weight-bold">{{ $row->code }}</span>
                                            <div class="md-sub"><span class="md-tag">{{ $row->category_code ?: '—' }}</span></div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->material_name ?: '—' }}</div>
                                        </td>
                                        <td class="md-sub" title="{{ $row->manufacturer_name }}">
                                            {{ $row->manufacturer_short_name ?: '—' }}
                                        </td>
                                        <td class="md-sub">
                                            @if ($row->location_code)
                                                {{ \App\Support\ZoneType::tag($row->location_code, $row->location_zone_type ?? null) }}
                                            @else <span class="text-muted">—</span> @endif
                                        </td>
                                        <td class="text-center md-sub">{{ $row->unit_short_name ?: ($row->unit_name ?: '—') }}</td>
                                        <td class="text-right font-weight-bold" data-order="{{ $row->gap }}">{{ $invNum($row->gap) }}</td>
                                        <td class="text-right" data-order="{{ $row->category_remaining }}">{{ $invNum($row->category_remaining) }}</td>
                                        <td class="text-center" data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                            <div class="font-weight-bold">{{ $invDate($row->expired_date) }}</div>
                                            @if ($row->days_to_expiry < 0)
                                                <span class="inv-countdown is-over">Quá {{ abs($row->days_to_expiry) }} ngày</span>
                                            @else
                                                <span class="inv-countdown {{ $row->days_to_expiry <= $nearExpiryDays ? 'is-near' : '' }}">Còn {{ $row->days_to_expiry }} ngày</span>
                                            @endif
                                        </td>
                                        <td class="text-center"><span class="mi-badge {{ $row->state }}">{{ $row->state_label }}</span></td>
                                    </tr>
                                @endforeach
                                @enddeferredRows
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ KHẢ DỤNG THÁNG TỚI ============ --}}
                @tab('inventory_vpp', 'next')
                <div class="mi-pane {{ $activeTab === 'next' ? 'is-active' : '' }}" id="miPaneNext">
                    @include('pages.inventory.VppInventory.nextMonth')
                </div>
                @endtab

                {{-- ============ KIỂM KÊ ĐỊNH KỲ (1 THÁNG 1 LẦN) ============ --}}
                @tab('inventory_vpp', 'check')
                <div class="mi-pane {{ $activeTab === 'check' ? 'is-active' : '' }}" id="miPaneCheck">
                    @include('pages.inventory.VppInventory.stocktake')
                </div>
                @endtab

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tabs
        $('.mi-tab').on('click', function () {
            $('.mi-tab').removeClass('is-active');
            $(this).addClass('is-active');
            $('.mi-pane').removeClass('is-active');
            $('#' + $(this).data('pane')).addClass('is-active');
            // Badge phân loại chỉ áp cho các tab dạng bảng tồn
            $('#miClassBar').toggle(['miPaneCode', 'miPaneMat', 'miPaneExpiring', 'miPaneNext'].indexOf($(this).data('pane')) !== -1);
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });

        // Modal cân đối
        $(document).on('click', '.btn-mi-balancing', function () {
            var r = $(this).data('row') || {};
            var $f = $('#balancingModal form');
            $f.find('[name="import_id"]').val(r.id);
            $f.find('[name="current_gap"]').val(r.gap);
            $f.find('.mi-code-view').val(r.code);
            $f.find('.mi-mat-view').val(r.material_name);
            $f.find('.mi-imported-view').val(r.imported);
            $f.find('.mi-gap-view').val(r.gap + ' ' + (r.unit || ''));
            $f.find('[name="balancing_amount"]').val('');
            $('#balancingModal').modal('show');
        });

        // Modal lịch sử cân đối
        var miBalMap = @json($invBalancingMap);
        $(document).on('click', '.btn-mi-history', function () {
            var id = $(this).data('id');
            var rows = miBalMap[id] || [];
            $('#balancingHistoryModal .mi-hist-code').text($(this).data('code'));
            $('#balancingHistoryModal .mi-hist-name').text($(this).data('name'));
            $('#balancingHistoryModal .mi-hist-imported').text($(this).data('imported'));
            $('#balancingHistoryModal .mi-hist-balanced').text($(this).data('balanced'));
            $('#balancingHistoryModal .mi-hist-gap').text($(this).data('gap'));
            var $tb = $('#balancingHistoryModal tbody').empty();
            if (!rows.length) {
                $tb.append('<tr><td colspan="4" class="text-center text-muted">Chưa có lần cân đối nào.</td></tr>');
            } else {
                rows.forEach(function (x, i) {
                    var $tr = $('<tr></tr>');
                    $tr.append($('<td class="text-center"></td>').text(i + 1));
                    $tr.append($('<td class="text-right font-weight-bold"></td>').text((x.balancing_amount > 0 ? '+' : '') + x.balancing_amount));
                    $tr.append($('<td></td>').text(x.balancing_by || 'NA'));
                    $tr.append($('<td></td>').text(x.balancing_at));
                    $tb.append($tr);
                });
            }
            $('#balancingHistoryModal').modal('show');
        });

        if ($.fn.DataTable.isDataTable('#mdTable')) $('#mdTable').DataTable().order([1, 'asc']).draw();

        // Tab Cận hạn dùng: chip lọc theo mốc hạn dùng
        var miExpiryUntil = null;
        $.fn.dataTable.ext.search.push(function (settings, data, index, rowData) {
            if (settings.nTable.id !== 'miExpiringTable' || !miExpiryUntil) return true;
            var effective = mdDeferred.rowAttr(rowData, settings.aoData[index].nTr, 'effective');
            return String(effective || '9999-12-31') <= miExpiryUntil;
        });
        $(document).on('click', '.mi-chip[data-expiry]', function () {
            miExpiryUntil = $(this).data('until') || null;
            $('.mi-chip[data-expiry]').removeClass('is-active');
            $(this).addClass('is-active');
            if ($.fn.DataTable.isDataTable('#miExpiringTable')) $('#miExpiringTable').DataTable().draw();
        });
        mdDeferred.setEmpty('#miExpiringTable', 'Không có mã xuất nhập nào còn tồn mà sắp hết hạn.');

        // Đổi tháng ở tab "Khả dụng tháng tới" nạp lại trang - mở lại đúng tab đó
        if (location.search.indexOf('tab=forecast') >= 0) {
            $('.mi-tab[data-pane="miPaneNext"]').trigger('click');
        }
    });
</script>
