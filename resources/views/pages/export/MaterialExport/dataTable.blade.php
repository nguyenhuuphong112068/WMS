@include('pages.export.shared.assets')

@php
    // 3 tab đề nghị cấp phát: key = material_request_lists.type (cũng là tên tab)
    $reqTabMeta = [
        'periodic' => ['pane' => 'mePanePeriodic', 'icon' => 'fas fa-sync-alt', 'label' => 'Đề Nghị Theo Định Kỳ', 'short' => 'Theo Định Kỳ', 'prefix' => 'reqp_', 'create' => false, 'empty' => 'Chưa có đề nghị định kỳ nào.'],
        'risk_assessment' => ['pane' => 'mePaneRisk', 'icon' => 'fas fa-shield-alt', 'label' => 'Đề Nghị Theo ĐG Rủi Ro', 'short' => 'Theo ĐG Rủi Ro', 'prefix' => 'reqk_', 'create' => true, 'empty' => 'Chưa có đề nghị theo đánh giá rủi ro nào.'],
        'regular' => ['pane' => 'mePaneRegular', 'icon' => 'fas fa-file-signature', 'label' => 'Đề Nghị Thường Quy', 'short' => 'Thường Quy', 'prefix' => 'reqt_', 'create' => true, 'empty' => 'Chưa có đề nghị cấp phát vật tư nào.'],
    ];

    /*
    | Nhóm "Đề Nghị" trên thanh tab: 3 tab đề nghị cấp phát + tab đề nghị VPP (gửi kho VPP của
    | Hành Chính) + tab đề nghị chuyển liên phòng ban gom thành một dropdown. Mỗi mục vẫn là .exp-tab với pane riêng như trước nên ?tab= và
    | activeTab sau khi lưu không đổi.
    */
    $reqGroupTabs = collect($reqTabMeta)->map(fn($meta, $type) => [
        'key' => $type,
        'pane' => $meta['pane'],
        'icon' => $meta['icon'],
        'label' => $meta['short'],
        'count' => $reqTabs[$type]['list']->total(),
    ])->values()->push([
        'key' => 'vpp',
        'pane' => 'mePaneVpp',
        'icon' => 'fas fa-paperclip',
        'label' => 'Đề nghị VPP',
        // Phiếu chờ xử lý, Controller đếm - số vật tư cần đề nghị hiện ở tab con trong pane
        'count' => $vppBadgeCount,
    ])->push([
        'key' => 'transfer',
        'pane' => 'mePaneTransfer',
        'icon' => 'fas fa-people-arrows',
        'label' => 'Chuyển liên phòng ban',
        // Do Controller đếm trên toàn bộ dữ liệu, không chỉ trang đang xem
        'count' => $transferBadgeCount,
    ]);
    $reqGroupActive = $reqGroupTabs->firstWhere('key', $activeTab);
    $reqGroupCount = $reqGroupTabs->sum('count');
@endphp

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                <div class="exp-tabs">
                    <div class="exp-tab-group dropdown">
                        <button type="button" class="exp-tab-toggle dropdown-toggle {{ $reqGroupActive ? 'is-active' : '' }}"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-file-signature mr-1"></i> Đề Nghị
                            <span class="exp-tab-current">{{ $reqGroupActive ? '· ' . $reqGroupActive['label'] : '' }}</span>
                            @if ($reqGroupCount)
                                <span class="exp-tab-count">{{ $reqGroupCount }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu exp-tab-menu">
                            @foreach ($reqGroupTabs as $groupTab)
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
                    <button type="button" class="exp-tab {{ $activeTab === 'prepare' ? 'is-active' : '' }}" data-pane="mePanePrepare">
                        <i class="fas fa-dolly mr-1"></i> Cấp Phát
                        @if ($prepBadgeCount)
                            <span class="exp-tab-count">{{ $prepBadgeCount }}</span>
                        @endif
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}" data-pane="mePaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ Cấp Phát
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'quarantine' ? 'is-active' : '' }}" data-pane="mePaneQuarantine">
                        <i class="fas fa-biohazard mr-1"></i> Cách ly - Loại bỏ - Huỷ
                        @if ($quarantineBadge)
                            <span class="exp-tab-count">{{ $quarantineBadge }}</span>
                        @endif
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'late' ? 'is-active' : '' }}" data-pane="mePaneLate">
                        <i class="fas fa-hourglass-end mr-1"></i> Ghi nhận cấp phát trễ
                        @if ($lateRows->count())
                            <span class="exp-tab-count">{{ $lateRows->count() }}</span>
                        @endif
                    </button>
                </div>

                {{-- ============ SỔ SỬ DỤNG ============ --}}
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
                                    <th style="width:160px">Thiết Bị Liên Quan</th>
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
                                            @else
                                                <span class="badge badge-{{ $row->type === 'cancel' ? 'danger' : 'success' }}">
                                                    {{ \App\Http\Controllers\Pages\Export\MaterialExportController::TYPES[$row->type] ?? $row->type }}
                                                </span>
                                            @endif
                                            @unless ($row->status_id) <div><span class="badge badge-secondary mt-1">Đã khoá</span></div> @endunless
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $row->created_at }}">{{ $expDateTime($row->created_at) }}</td>
                                        <td class="md-sub">
                                            {{ $row->type === 'cancel' ? '—' : ($row->product_name ?: '—') }}
                                            @if ($row->test_report_no) <div><small>PKN: {{ $row->test_report_no }}</small></div> @endif
                                        </td>
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
                                                {{-- Loại bỏ theo quyết định ở tab "Cách ly - Loại Bỏ - Huỷ": không quay lại kho được --}}
                                                <span class="md-sub text-muted" title="Loại bỏ theo quyết định, không điều chỉnh được"><i class="fas fa-lock"></i></span>
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

                {{-- ============ ĐỀ NGHỊ CẤP PHÁT: ĐỊNH KỲ / THEO ĐÁNH GIÁ RỦI RO / THƯỜNG QUY ============ --}}
                @foreach ($reqTabMeta as $reqType => $meta)
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
                <div class="exp-pane {{ $activeTab === 'prepare' ? 'is-active' : '' }}" id="mePanePrepare">
                    @include('pages.export.MaterialExport.preparePane')
                </div>

                {{-- ============ CẤP PHÁT TRỄ HẠN ============ --}}
                <div class="exp-pane {{ $activeTab === 'late' ? 'is-active' : '' }}" id="mePaneLate">
                    @include('pages.export.MaterialExport.latePane')
                </div>

                {{-- ============ VẬT TƯ HỎNG: CÁCH LY -> LOẠI BỎ -> HUỶ ============ --}}
                <div class="exp-pane {{ $activeTab === 'quarantine' ? 'is-active' : '' }}" id="mePaneQuarantine">
                    @include('pages.export.MaterialExport.quarantinePane')
                </div>

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
