@include('pages.export.shared.assets')

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                <div class="exp-tabs">
                    <button type="button" class="exp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}" data-pane="mePaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ sử dụng vật tư
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'request' ? 'is-active' : '' }}" data-pane="mePaneRequest">
                        <i class="fas fa-file-signature mr-1"></i> Đề nghị cấp phát vật tư
                        @if ($requestLists->total())
                            <span class="exp-tab-count">{{ $requestLists->total() }}</span>
                        @endif
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'transfer' ? 'is-active' : '' }}" data-pane="mePaneTransfer">
                        <i class="fas fa-people-arrows mr-1"></i> Đề nghị chuyển liên phòng ban
                        {{-- Do Controller đếm trên toàn bộ dữ liệu, không chỉ trang đang xem --}}
                        @if ($transferBadgeCount)
                            <span class="exp-tab-count">{{ $transferBadgeCount }}</span>
                        @endif
                    </button>
                    @if ($showApprovalInbox)
                        <button type="button" class="exp-tab {{ $activeTab === 'inbox' ? 'is-active' : '' }}" data-pane="mePaneInbox">
                            <i class="fas fa-inbox mr-1"></i> Ký duyệt (mọi phòng ban)
                            @if ($inboxBadgeCount)
                                <span class="exp-tab-count">{{ $inboxBadgeCount }}</span>
                            @endif
                        </button>
                    @endif
                </div>

                {{-- ============ SỔ SỬ DỤNG ============ --}}
                <div class="exp-pane {{ $activeTab === 'book' ? 'is-active' : '' }}" id="mePaneBook">
                    <div class="md-toolbar">
                        @perm('export_material_issue')
                            <button type="button" class="btn btn-danger btn-md-create">
                                <i class="fas fa-trash-alt mr-1"></i> Loại bỏ vật tư hỏng / hết hạn
                            </button>
                        @endperm
                    </div>

                    @include('pages.shared.rangeFilter', [
                        'rfRoute' => $expRoute . 'list',
                        'rfTab' => 'book',
                        'rfPrefix' => 'book_',
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
                                            @else
                                            <div class="md-actions">
                                                <span class="exp-btn-wrap">
                                                    @perm('export_material_issue')
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
                                                    @endperm
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

                {{-- ============ ĐỀ NGHỊ CẤP PHÁT ============ --}}
                <div class="exp-pane {{ $activeTab === 'request' ? 'is-active' : '' }}" id="mePaneRequest">
                    @include('pages.export.MaterialExport.requestPane')
                </div>

                {{-- ============ ĐỀ NGHỊ CHUYỂN LIÊN PHÒNG BAN ============ --}}
                @include('pages.export.MaterialExport.transferPane')

                {{-- ============ KÝ DUYỆT (MỌI PHÒNG BAN) ============ --}}
                @if ($showApprovalInbox)
                    <div class="exp-pane {{ $activeTab === 'inbox' ? 'is-active' : '' }}" id="mePaneInbox">
                        @include('pages.export.MaterialExport.inboxPane')
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if ($.fn.DataTable.isDataTable('#mdTable')) $('#mdTable').DataTable().order([5, 'desc']).draw();

        // Điền form loại bỏ / điều chỉnh
        $(document).on('click', '.btn-md-create', function () {
            $('#createModal form')[0].reset();
            $('#createModal').modal('show');
        });
    });
</script>
