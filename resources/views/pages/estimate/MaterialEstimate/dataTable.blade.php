@include('pages.estimate.shared.assets')

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    @if ($tabAllowed['list'] && user_can('estimate_material_create'))
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Lập phiếu dự trù
                        </button>
                    @endif
                </div>

                <ul class="nav nav-tabs mb-3" id="estimateTabs" role="tablist">
                    @if ($tabAllowed['list'])
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'list' ? 'active' : '' }}" id="list-tab" data-toggle="tab" href="#list" role="tab">Danh sách phiếu</a>
                        </li>
                    @endif
                    @if ($tabAllowed['tracking'])
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'tracking' ? 'active' : '' }}" id="tracking-tab" data-toggle="tab" href="#tracking" role="tab">Theo dõi dự trù <span class="badge badge-info">{{ $trackedItems->count() }}</span></a>
                        </li>
                    @endif
                    @if ($tabAllowed['watchlist'])
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'watchlist' ? 'active' : '' }}" id="watchlist-tab" data-toggle="tab" href="#watchlist" role="tab">
                                Danh sách vật tư cần dự trù <span class="badge badge-warning">{{ $watchlistItems->filter(fn ($item) => ! $item->in_progress)->count() }}</span>
                            </a>
                        </li>
                    @endif
                    @if (($approvedLists ?? null) !== null)
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'approved' ? 'active' : '' }}" id="approved-tab" data-toggle="tab" href="#approved" role="tab">
                                Đơn dự trù đã được phê duyệt <span class="badge badge-success">{{ $approvedLists->count() }}</span>
                                @if ($approvedWaiting = $approvedLists->where('reception_status', 'waiting')->count())<span class="badge badge-warning" title="Phiếu vừa duyệt chờ tiếp nhận">{{ $approvedWaiting }}</span>@endif
                                @if ($approvedPendingCancel = $approvedLists->sum('pending_cancel_count'))<span class="badge badge-danger" title="Mục phòng đề nghị xin huỷ, chờ bộ phận mua hàng xác nhận">{{ $approvedPendingCancel }}</span>@endif
                            </a>
                        </li>
                    @endif
                </ul>

                <div class="tab-content" id="estimateTabsContent">
                    @if ($tabAllowed['list'])
                    <div class="tab-pane fade {{ $activeTab === 'list' ? 'show active' : '' }}" id="list" role="tabpanel">
                        <div class="table-responsive">
                            <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th style="width: 150px">Bộ Phận</th>
                                <th style="width: 140px">Mã Phiếu</th>
                                <th class="text-center" style="width: 100px">Kỳ Dự Trù</th>
                                <th class="text-center" style="width: 95px">Vật Tư</th>
                                <th style="width: 130px">Trạng Thái</th>
                                <th class="text-center" style="width: 150px">Tình Trạng</th>
                                <th style="width: 330px">Theo Dõi Trình Ký</th>
                                <th style="width: 130px">Người Lập</th>
                                <th class="text-center" style="width: 190px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @php
                                    $estEditable = in_array($row->app_status, ['draft', 'rejected']) && $row->status_id == 1;
                                    $estReception = $row->reception_status;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="est-code">{{ $row->department_short_name }}</span>
                                    </td>
                                    <td>
                                        <span class="est-code">{{ $row->code }}</span>
                                        @if ($row->note)
                                            <div class="md-sub">
                                                <span class="md-note" title="{{ $row->note }}">{{ $row->note }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="est-period">{{ str_pad($row->month, 2, '0', STR_PAD_LEFT) }}/{{ $row->year }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="md-tag">{{ $itemCounts[$row->id] ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $estStatusClass = match ($row->app_status) {
                                                'approved' => 'approved',
                                                'rejected' => 'rejected',
                                                'cancelled' => 'rejected',
                                                default => 'pending',
                                            };
                                        @endphp
                                        <span class="md-badge {{ $estStatusClass }}">
                                            {{ $appStatuses[$row->app_status] ?? $row->app_status }}
                                        </span>
                                        @if ($row->app_status === 'rejected' && $row->reject_reason)
                                            <div class="md-sub">
                                                <span class="md-note" title="{{ $row->reject_reason }}">
                                                    {{ $row->reject_reason }}
                                                </span>
                                            </div>
                                        @endif
                                        @if ($row->app_status === 'cancelled' && $row->cancel_reason)
                                            <div class="md-sub text-danger mt-1">
                                                <i class="fas fa-times-circle mr-1"></i>
                                                <span class="md-note" title="{{ $row->cancel_reason }}">
                                                    {{ $row->cancel_reason }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    @php $estApOrder = $row->approved_status ? ['waiting' => 0, 'progress' => 1, 'completed' => 2, 'cancelled' => 3][$row->approved_status['key']] : 9; @endphp
                                    <td class="text-center" data-order="{{ $estApOrder }}">
                                        @include('pages.estimate.shared.approvedStatusBadge', ['apStatus' => $row->approved_status])
                                    </td>
                                    <td>
                                        @include('pages.estimate.shared.signFlow', ['row' => $row, 'signs' => $signRows->get($row->id, collect())])
                                    </td>
                                    <td class="md-sub">
                                        {{ $row->updated_by ?: $row->created_by ?: '—' }}
                                        <br><small>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '' }}</small>
                                    </td>
                                    <td>
                                        <div class="md-actions">
                                            <a href="{{ route($estRoute . 'detail', ['id' => $row->id]) }}"
                                                class="btn btn-sm {{ $row->app_status === 'pending_sign' && $row->can_sign ? 'btn-success' : 'btn-primary' }}" title="{{ $row->app_status === 'pending_sign' && $row->can_sign ? 'Xem chi tiết và ký duyệt' : 'Xem chi tiết vật tư dự trù' }}">
                                                <i class="fas fa-list-ul"></i>
                                            </a>

                                            <button type="button" class="btn btn-sm btn-info btn-est-history"
                                                title="Theo dõi trình ký"
                                                data-url="{{ route($estRoute . 'history', ['id' => $row->id]) }}"
                                                data-title="Phiếu {{ $row->code }}">
                                                <i class="fas fa-route"></i>
                                            </button>

                                            @if ($estEditable && user_can('estimate_material_update'))
                                                <button type="button" class="btn btn-sm btn-warning btn-md-edit" title="Sửa"
                                                    data-row="{{ json_encode([
                                                        'id' => $row->id,
                                                        'month' => $row->month,
                                                        'year' => $row->year,
                                                        'note' => $row->note,
                                                    ]) }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <button type="button" class="btn btn-sm btn-success btn-est-submit" title="Trình ký"
                                                    data-id="{{ $row->id }}" data-code="{{ $row->code }}">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            @endif


                                            @if ($estEditable && user_can('estimate_material_delete'))
                                                <form class="form-md-confirm-cancel d-inline" action="{{ route($estRoute . 'destroy') }}"
                                                    method="POST"
                                                    data-title="Huỷ phiếu dự trù {{ $row->code }}?"
                                                    data-text="Hành động này sẽ huỷ phiếu. Bạn cần nhập lý do huỷ."
                                                    data-danger="1">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Huỷ phiếu">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                        </div>
                    </div>
                    @endif
                    @if ($tabAllowed['tracking'])
                        <div class="tab-pane fade {{ $activeTab === 'tracking' ? 'show active' : '' }}" id="tracking" role="tabpanel">
                            @include('pages.estimate.shared.trackingTable', ['items' => $trackedItems])
                        </div>
                    @endif
                    @if ($tabAllowed['watchlist'])
                        <div class="tab-pane fade {{ $activeTab === 'watchlist' ? 'show active' : '' }}" id="watchlist" role="tabpanel">
                            @include('pages.estimate.MaterialEstimate.watchlistTable', ['items' => $watchlistItems])
                        </div>
                    @endif
                    @if (($approvedLists ?? null) !== null)
                        <div class="tab-pane fade {{ $activeTab === 'approved' ? 'show active' : '' }}" id="approved" role="tabpanel">
                            @include('pages.estimate.shared.approvedListsPane', [
                                'lists' => $approvedLists,
                                'signRows' => $approvedSignRows,
                                'itemCounts' => $approvedItemCounts,
                                'itemLabel' => 'Vật Tư',
                            ])
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bảng dùng chung sắp theo cột 1, riêng dự trù cần xem kỳ gần nhất trước
        if ($('#mdTable').length) {
            $('#mdTable').DataTable().order([2, 'desc']).draw();
        }
    });
</script>
