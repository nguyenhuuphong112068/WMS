@include('pages.export.shared.assets')
@php $activeTab = \App\Support\PageTabs::active('export_vpp', $activeTab ?? 'use'); @endphp

@php
    /*
    | Nhóm dropdown "Đề nghị liên phòng ban": đề nghị VPP gửi Hành Chính + đề nghị chuyển liên
    | phòng ban. Mỗi mục vẫn là .exp-tab với pane riêng nên ?tab= và activeTab sau khi lưu không đổi.
    */
    $reqGroups = collect([
        [
            'icon' => 'fas fa-people-arrows',
            'label' => 'Đề nghị liên phòng ban',
            'tabs' => collect([
                [
                    'key' => 'vpp',
                    'pane' => 'mePaneVpp',
                    'icon' => 'fas fa-paperclip',
                    'label' => 'VPP đến HC',
                    // Phiếu chờ xử lý, Controller đếm - số VPP cần đề nghị hiện ở tab con trong pane
                    'count' => $vppBadgeCount,
                ],
                [
                    'key' => 'transfer',
                    'pane' => 'mePaneTransfer',
                    'icon' => 'fas fa-people-arrows',
                    'label' => 'Phòng ban khác',
                    // Do Controller đếm trên toàn bộ dữ liệu, không chỉ trang đang xem
                    'count' => $transferBadgeCount,
                ],
            ]),
        ],
    ])
        // Quyền xem từng tab (App\Support\PageTabs): bỏ mục không được xem, nhóm rỗng thì bỏ cả nhóm
        ->map(function ($group) {
            $group['tabs'] = $group['tabs']
                ->filter(fn ($groupTab) => \App\Support\PageTabs::can('export_vpp', $groupTab['key']))
                ->values();

            return $group;
        })
        ->filter(fn ($group) => $group['tabs']->isNotEmpty())
        ->all();
@endphp

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                <div class="exp-tabs">
                    @tab('export_vpp', 'use')
                    <button type="button" class="exp-tab {{ $activeTab === 'use' ? 'is-active' : '' }}" data-pane="mePaneUse">
                        <i class="fas fa-paperclip mr-1"></i> Sử Dụng VPP
                        @if ($todayUses->count())
                            <span class="exp-tab-count" title="Số dòng sử dụng hôm nay">{{ $todayUses->count() }}</span>
                        @endif
                    </button>
                    @endtab
                    @tab('export_vpp', 'book')
                    <button type="button" class="exp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}" data-pane="mePaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ sử dụng VPP
                    </button>
                    @endtab
                    @foreach ($reqGroups as $group)
                        @php
                            $groupActive = $group['tabs']->firstWhere('key', $activeTab);
                            $groupCount = $group['tabs']->sum('count');
                        @endphp
                        <div class="exp-tab-group dropdown">
                            <button type="button" class="exp-tab-toggle dropdown-toggle {{ $groupActive ? 'is-active' : '' }}"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="{{ $group['icon'] }} mr-1"></i> {{ $group['label'] }}
                                <span class="exp-tab-current">{{ $groupActive ? '· ' . $groupActive['label'] : '' }}</span>
                                @if ($groupCount)
                                    <span class="exp-tab-count">{{ $groupCount }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu exp-tab-menu">
                                @foreach ($group['tabs'] as $groupTab)
                                    <button type="button" class="exp-tab {{ $activeTab === $groupTab['key'] ? 'is-active' : '' }}"
                                        data-pane="{{ $groupTab['pane'] }}" data-short="{{ $groupTab['label'] }}">
                                        <i class="{{ $groupTab['icon'] }}"></i> {{ $groupTab['label'] }}
                                        @if ($groupTab['count'])
                                            <span class="exp-tab-count">{{ $groupTab['count'] }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @tab('export_vpp', 'quarantine')
                    <button type="button" class="exp-tab {{ $activeTab === 'quarantine' ? 'is-active' : '' }}" data-pane="mePaneQuarantine">
                        <i class="fas fa-biohazard mr-1"></i> Cách ly - Loại bỏ - Huỷ
                        @if ($quarantineBadge)
                            <span class="exp-tab-count">{{ $quarantineBadge }}</span>
                        @endif
                    </button>
                    @endtab
                </div>

                {{-- ============ SỬ DỤNG VPP: nút ghi phiếu + các dòng đã dùng hôm nay ============ --}}
                @tab('export_vpp', 'use')
                <div class="exp-pane {{ $activeTab === 'use' ? 'is-active' : '' }}" id="mePaneUse">
                    <div class="md-toolbar">
                        @perm(\App\Http\Controllers\Pages\Export\VppExportController::ISSUE_PERMISSION)
                            <button type="button" class="btn btn-primary btn-vpp-use">
                                <i class="fas fa-plus mr-1"></i> Sử dụng VPP
                            </button>
                        @endperm
                    </div>

                    <div class="table-responsive">
                        <table id="meTodayTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:45px">STT</th>
                                    <th style="width:150px">Mã Xuất Nhập</th>
                                    <th>VPP</th>
                                    <th class="text-right" style="width:110px">Số Lượng</th>
                                    <th class="text-center" style="width:90px">Giờ Dùng</th>
                                    <th>Mục Đích</th>
                                    <th style="width:150px">Người Sử Dụng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($todayUses as $row)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="exp-code font-weight-bold">{{ $row->code }}</span>
                                            @if ($row->category_code)
                                                <div class="md-sub"><span class="md-tag">{{ $row->category_code }}</span></div>
                                            @endif
                                        </td>
                                        <td class="font-weight-bold">
                                            {{ $row->material_name ?: '—' }}
                                            @if (! $row->status_id) <span class="badge badge-secondary ml-1">Đã khoá</span> @endif
                                        </td>
                                        <td class="text-right">{{ $expNum($row->amount) }} <span class="md-sub">{{ $row->unit_short_name }}</span></td>
                                        <td class="text-center md-sub" data-order="{{ $row->created_at }}">{{ \Carbon\Carbon::parse($row->created_at)->format('H:i') }}</td>
                                        <td class="md-sub">{{ $row->purpose ?: '—' }}</td>
                                        <td class="md-sub">{{ $row->used_by ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ SỔ SỬ DỤNG ============ --}}
                @tab('export_vpp', 'book')
                <div class="exp-pane {{ $activeTab === 'book' ? 'is-active' : '' }}" id="mePaneBook">
                    @include('pages.shared.rangeFilter', [
                        'rfRoute' => $expRoute . 'list',
                        'rfTab' => 'book',
                        'rfPrefix' => 'book_',
                        'rfInBar' => true,
                        'rfRange' => $bookRange,
                        'rfPerPage' => $bookPerPage,
                        'rfKeyword' => $bookKeyword,
                        'rfDateLabel' => 'Ngày sử dụng',
                        'rfPlaceholder' => 'Mã xuất nhập, mã phiếu đề nghị, tên VPP, người sử dụng, mục đích...',
                    ])

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100" data-server-paged>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:45px">STT</th>
                                    <th style="width:150px">Mã Xuất Nhập</th>
                                    <th style="width:150px">Mã Phiếu Đề Nghị</th>
                                    <th>VPP</th>
                                    <th class="text-right" style="width:100px">Số Lượng</th>
                                    <th class="text-center" style="width:90px">Loại</th>
                                    <th class="text-center" style="width:120px">Thời Gian</th>
                                    <th>Mục Đích</th>
                                    <th style="width:130px">Người Sử Dụng</th>
                                    <th class="text-center" style="width:80px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Không có quyền điều chỉnh: vẫn hiện nút nhưng khoá lại (server cũng chặn ở update())
                                    $meCanUpdate = user_can(\App\Http\Controllers\Pages\Export\VppExportController::UPDATE_PERMISSION);
                                @endphp
                                @foreach ($exports as $row)
                                    <tr>
                                        <td class="text-center">{{ $exports->firstItem() + $loop->index }}</td>
                                        <td>
                                            <span class="exp-code font-weight-bold">{{ $row->code }}</span>
                                            @if ($row->category_code)
                                                <div class="md-sub"><span class="md-tag">{{ $row->category_code }}</span></div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row->request_code)
                                                <span class="exp-code font-weight-bold">{{ $row->request_code }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->material_name ?: '—' }}</div>
                                        </td>
                                        <td class="text-right">
                                            {{ $expNum($row->amount) }} <span class="md-sub">{{ $row->unit_short_name }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($row->type === 'transfer_out')
                                                {{-- Phiếu do tab "Đề nghị chuyển liên phòng ban" sinh ra: hàng sang phòng khác, không phải hàng đã dùng --}}
                                                <span class="badge badge-primary">Chuyển đi</span>
                                                {{-- Tình trạng nhận hàng của phòng đề nghị --}}
                                                @if ($row->transfer_item_status === 'received')
                                                    <div><span class="badge badge-success mt-1"
                                                        title="{{ $row->transfer_received_by }} - {{ $row->transfer_received_at ? \Carbon\Carbon::parse($row->transfer_received_at)->format('d/m/Y H:i') : '' }}">Đã nhận</span></div>
                                                @elseif ($row->transfer_item_status === 'returned')
                                                    <div><span class="badge badge-danger mt-1" title="{{ $row->transfer_return_note }}">Từ chối nhận</span></div>
                                                @elseif ($row->transfer_item_status === 'issued')
                                                    <div><span class="badge badge-warning mt-1">Bộ phận chưa nhận</span></div>
                                                @endif
                                            @elseif ($row->quarantine_status === 'quarantined')
                                                <span class="badge badge-warning">Cách ly - Chờ quyết định</span>
                                            @elseif ($row->quarantine_status === 'returned')
                                                <span class="badge badge-success">Cách ly - Đã trả về kho</span>
                                            @else
                                                <span class="badge badge-{{ $row->type === 'cancel' ? 'danger' : 'success' }}">
                                                    {{ \App\Http\Controllers\Pages\Export\VppExportController::TYPES[$row->type] ?? $row->type }}
                                                </span>
                                            @endif
                                            @if (! $row->status_id && $row->transfer_item_status !== 'returned' && $row->quarantine_status !== 'returned') <div><span class="badge badge-secondary mt-1">Đã khoá</span></div> @endif
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $row->created_at }}">{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '—' }}</td>
                                        <td class="md-sub">
                                            @if ($row->type === 'cancel')
                                                <span class="text-danger">{{ $row->reason ?: '—' }}</span>
                                            @elseif ($row->type === 'transfer_out')
                                                <span class="text-primary">Cấp phát liên phòng ban đến {{ $row->to_department_name ?: '—' }}</span>
                                            @else
                                                {{ $row->purpose ?: '—' }}
                                            @endif
                                        </td>
                                        <td class="md-sub">{{ $row->used_by ?: '—' }}</td>
                                        <td class="text-center">
                                            @if ($row->type === 'transfer_out')
                                                {{-- Phiếu cấp phát liên phòng ban chỉ đổi được qua thao tác Nhận / Từ chối nhận của phòng nhận --}}
                                                <span class="md-sub text-muted">—</span>
                                            @elseif ($row->quarantine_id)
                                                {{-- Sinh từ phiếu cách ly ở tab "Cách ly - Loại Bỏ - Huỷ": chỉ điều chỉnh qua phiếu cách ly --}}
                                                <span class="md-sub text-muted" title="Sinh từ phiếu cách ly, chỉ điều chỉnh ở tab Cách ly - Loại bỏ - Huỷ"><i class="fas fa-lock"></i></span>
                                            @else
                                            <div class="md-actions">
                                                <span class="exp-btn-wrap">
                                                    @if ($meCanUpdate)
                                                        <button type="button" class="btn btn-sm btn-warning btn-me-edit" title="Điều chỉnh"
                                                            data-row="{{ json_encode([
                                                                'id' => $row->id,
                                                                'code' => $row->code,
                                                                'request_code' => $row->request_code,
                                                                'amount' => $row->amount,
                                                                'type' => $row->type,
                                                                'type_label' => \App\Http\Controllers\Pages\Export\VppExportController::TYPES[$row->type] ?? $row->type,
                                                                'product_name' => $row->product_name,
                                                                'reason' => $row->reason,
                                                                'material_name' => $row->material_name,
                                                                'category_code' => $row->category_code,
                                                                'purpose' => $row->purpose,
                                                                'unit_short_name' => $row->unit_short_name,
                                                                'used_by' => $row->used_by,
                                                                'created_at' => $expDateTime($row->created_at),
                                                                'locked' => ! $row->status_id,
                                                            ]) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-warning" disabled
                                                            title="Cần quyền Điều Chỉnh Sổ Sử Dụng VPP">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    @endif
                                                    @php $c = (int) ($adjustCounts[$row->id] ?? 0); @endphp
                                                    @if ($c > 0)
                                                        <button type="button" class="exp-count-badge btn-exp-history"
                                                            data-url="{{ route($expRoute . 'history', ['id' => $row->id]) }}"
                                                            data-title="{{ $row->code }}">{{ $c }}</button>
                                                    @endif
                                                </span>
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('pages.shared.paginator', [
                        'pgItems' => $exports,
                        'pgTab' => 'book',
                        'pgUnit' => 'phiếu sử dụng',
                    ])
                </div>
                @endtab

                {{-- ============ VPP HỎNG: CÁCH LY -> LOẠI BỎ -> HUỶ ============ --}}
                @tab('export_vpp', 'quarantine')
                <div class="exp-pane {{ $activeTab === 'quarantine' ? 'is-active' : '' }}" id="mePaneQuarantine">
                    @include('pages.export.VppExport.quarantinePane')
                </div>
                @endtab

                {{-- ============ ĐỀ NGHỊ CHUYỂN LIÊN PHÒNG BAN ============ --}}
                @include('pages.export.VppExport.transferPane')
                @include('pages.export.VppExport.vppPane')


            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if ($.fn.DataTable.isDataTable('#mdTable')) $('#mdTable').DataTable().order([6, 'desc']).draw();
    });
</script>
