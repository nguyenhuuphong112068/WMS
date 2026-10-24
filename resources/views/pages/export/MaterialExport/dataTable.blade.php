@include('pages.export.shared.assets')
@php $activeTab = \App\Support\PageTabs::active('export_material', $activeTab ?? 'regular'); @endphp

@php
    // 3 tab đề nghị cấp phát: key = material_request_lists.type (cũng là tên tab)
    $reqTabMeta = [
        'periodic' => ['pane' => 'mePanePeriodic', 'icon' => 'fas fa-sync-alt', 'label' => 'Đề Nghị Theo Định Kỳ', 'short' => 'Theo Định Kỳ', 'prefix' => 'reqp_', 'create' => false, 'empty' => 'Chưa có đề nghị định kỳ nào.'],
        'risk_assessment' => ['pane' => 'mePaneRisk', 'icon' => 'fas fa-shield-alt', 'label' => 'Đề Nghị Theo ĐG Rủi Ro', 'short' => 'Theo ĐG Rủi Ro', 'prefix' => 'reqk_', 'create' => true, 'empty' => 'Chưa có đề nghị theo đánh giá rủi ro nào.'],
        'regular' => ['pane' => 'mePaneRegular', 'icon' => 'fas fa-file-signature', 'label' => 'Đề Nghị Thường Quy', 'short' => 'Thường Quy', 'prefix' => 'reqt_', 'create' => true, 'empty' => 'Chưa có đề nghị cấp phát vật tư nào.'],
    ];

    /*
    | Thanh tab có 2 nhóm dropdown: "Đề Nghị" (3 loại đề nghị cấp phát nội bộ) và "Liên Phòng Ban"
    | (đề nghị VPP gửi Hành Chính + đề nghị chuyển liên phòng ban). Mỗi mục vẫn là .exp-tab với pane
    | riêng nên ?tab= và activeTab sau khi lưu không đổi.
    */
    $reqGroups = [
        [
            'icon' => 'fas fa-file-signature',
            'label' => 'Đề Nghị Nội Bộ',
            'tabs' => collect($reqTabMeta)->map(fn($meta, $type) => [
                'key' => $type,
                'pane' => $meta['pane'],
                'icon' => $meta['icon'],
                'label' => $meta['short'],
                'count' => $reqTabs[$type]['list']->total(),
            ])->values(),
        ],
        [
            'icon' => 'fas fa-people-arrows',
            'label' => 'Đề nghị liên phòng ban',
            'tabs' => collect([
                [
                    'key' => 'vpp',
                    'pane' => 'mePaneVpp',
                    'icon' => 'fas fa-paperclip',
                    'label' => 'VPP đến HC',
                    // Phiếu chờ xử lý, Controller đếm - số vật tư cần đề nghị hiện ở tab con trong pane
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
    ];

    // Quyền xem từng tab (App\Support\PageTabs): bỏ mục không được xem, nhóm rỗng thì bỏ cả nhóm
    $reqGroups = collect($reqGroups)
        ->map(function ($group) {
            $group['tabs'] = collect($group['tabs'])
                ->filter(fn ($groupTab) => \App\Support\PageTabs::can('export_material', $groupTab['key']))
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
                    @tab('export_material', 'prepare')
                    <button type="button" class="exp-tab {{ $activeTab === 'prepare' ? 'is-active' : '' }}" data-pane="mePanePrepare">
                        <i class="fas fa-dolly mr-1"></i> Chờ Cấp Phát
                        @if ($prepBadgeCount)
                            <span class="exp-tab-count">{{ $prepBadgeCount }}</span>
                        @endif
                    </button>
                    @endtab
                    @tab('export_material', 'calibration')
                    <button type="button" class="exp-tab {{ $activeTab === 'calibration' ? 'is-active' : '' }}" data-pane="mePaneCalibration">
                        <i class="fas fa-ruler-combined mr-1"></i> Xác Nhận Hiệu Chuẩn
                        @if ($calibrationBadge)
                            <span class="exp-tab-count">{{ $calibrationBadge }}</span>
                        @endif
                    </button>
                    @endtab
                    @tab('export_material', 'book')
                    <button type="button" class="exp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}" data-pane="mePaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ Cấp Phát
                    </button>
                    @endtab
                    @tab('export_material', 'quarantine')
                    <button type="button" class="exp-tab {{ $activeTab === 'quarantine' ? 'is-active' : '' }}" data-pane="mePaneQuarantine">
                        <i class="fas fa-biohazard mr-1"></i> Cách ly - Loại bỏ - Huỷ
                        @if ($quarantineBadge)
                            <span class="exp-tab-count">{{ $quarantineBadge }}</span>
                        @endif
                    </button>
                    @endtab
                    @tab('export_material', 'late')
                    <button type="button" class="exp-tab {{ $activeTab === 'late' ? 'is-active' : '' }}" data-pane="mePaneLate">
                        <i class="fas fa-hourglass-end mr-1"></i> Ghi nhận cấp phát trễ
                        @if ($lateRows->count())
                            <span class="exp-tab-count">{{ $lateRows->count() }}</span>
                        @endif
                    </button>
                    @endtab
                </div>

                {{-- ============ SỔ SỬ DỤNG ============ --}}
                @tab('export_material', 'book')
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
                        'rfPlaceholder' => 'Mã xuất nhập, mã phiếu đề nghị, tên vật tư, người dùng, mục đích...',
                    ])

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100" data-server-paged>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:45px">STT</th>
                                    <th style="width:150px">Mã Xuất Nhập</th>
                                    <th style="width:150px">Mã Phiếu Đề Nghị</th>
                                    <th>Vật Tư</th>
                                    <th class="text-right" style="width:100px">Số Lượng</th>
                                    <th class="text-center" style="width:90px">Loại</th>
                                    <th class="text-center" style="width:120px">Thời Gian</th>
                                    <th>Mục Đích</th>
                                    <th style="width:130px">Người Thực Hiện</th>
                                    <th class="text-center" style="width:80px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Không có quyền điều chỉnh: vẫn hiện nút nhưng khoá lại (server cũng chặn ở update())
                                    $meCanUpdate = user_can(\App\Http\Controllers\Pages\Export\MaterialExportController::UPDATE_PERMISSION);
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
                                            <div class="md-sub small text-muted">{{ $row->technical_specification }}</div>
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
                                                    {{ \App\Http\Controllers\Pages\Export\MaterialExportController::TYPES[$row->type] ?? $row->type }}
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
                                                                'type_label' => \App\Http\Controllers\Pages\Export\MaterialExportController::TYPES[$row->type] ?? $row->type,
                                                                'product_name' => $row->product_name,
                                                                'reason' => $row->reason,
                                                                'material_name' => $row->material_name,
                                                                'category_code' => $row->category_code,
                                                                'technical_specification' => $row->technical_specification,
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
                                                            title="Cần quyền Điều Chỉnh Sổ Sử Dụng Vật Tư">
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

                {{-- ============ ĐỀ NGHỊ CẤP PHÁT: ĐỊNH KỲ / THEO ĐÁNH GIÁ RỦI RO / THƯỜNG QUY ============ --}}
                @foreach ($reqTabMeta as $reqType => $meta)
                    @continue(! \App\Support\PageTabs::can('export_material', $reqType))
                    <div class="exp-pane {{ $activeTab === $reqType ? 'is-active' : '' }}" id="{{ $meta['pane'] }}">
                        @include('pages.export.MaterialExport.requestPane', [
                            'reqType' => $reqType,
                            'reqPrefix' => $meta['prefix'],
                            'reqList' => $reqTabs[$reqType]['list'],
                            'reqRange' => $reqTabs[$reqType]['range'],
                            'reqPerPage' => $reqTabs[$reqType]['perPage'],
                            'reqUnissued' => $reqTabs[$reqType]['unissued'],
                            'reqUnissuedCount' => $reqTabs[$reqType]['unissuedCount'],
                            'reqShowCreate' => $meta['create'],
                            'reqEmptyText' => $meta['empty'],
                        ])
                    </div>
                @endforeach

                {{-- ============ SOẠN VẬT TƯ CẤP PHÁT ============ --}}
                @tab('export_material', 'prepare')
                <div class="exp-pane {{ $activeTab === 'prepare' ? 'is-active' : '' }}" id="mePanePrepare">
                    @include('pages.export.MaterialExport.preparePane')
                </div>
                @endtab

                {{-- ============ HIỆU CHUẨN TRƯỚC KHI CẤP PHÁT ============ --}}
                @tab('export_material', 'calibration')
                <div class="exp-pane {{ $activeTab === 'calibration' ? 'is-active' : '' }}" id="mePaneCalibration">
                    @include('pages.export.MaterialExport.calibrationPane')
                </div>
                @endtab

                {{-- ============ CẤP PHÁT TRỄ HẠN ============ --}}
                @tab('export_material', 'late')
                <div class="exp-pane {{ $activeTab === 'late' ? 'is-active' : '' }}" id="mePaneLate">
                    @include('pages.export.MaterialExport.latePane')
                </div>
                @endtab

                {{-- ============ VẬT TƯ HỎNG: CÁCH LY -> LOẠI BỎ -> HUỶ ============ --}}
                @tab('export_material', 'quarantine')
                <div class="exp-pane {{ $activeTab === 'quarantine' ? 'is-active' : '' }}" id="mePaneQuarantine">
                    @include('pages.export.MaterialExport.quarantinePane')
                </div>
                @endtab

                {{-- ============ ĐỀ NGHỊ CHUYỂN LIÊN PHÒNG BAN ============ --}}
                @include('pages.export.MaterialExport.transferPane')
                @include('pages.export.MaterialExport.vppPane')


            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if ($.fn.DataTable.isDataTable('#mdTable')) $('#mdTable').DataTable().order([5, 'desc']).draw();
    });
</script>
