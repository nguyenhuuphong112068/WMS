@include('pages.estimate.shared.assets')

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    @perm('estimate_chemical_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Lập phiếu dự trù
                        </button>
                    @endperm
                    <p class="hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Phiếu chỉ sửa được khi còn <b>Nháp</b> hoặc <b>Bị từ chối</b>. Trình ký xong là khoá nội dung.
                    </p>
                </div>

                <ul class="nav nav-tabs mb-3" id="estimateTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="list-tab" data-toggle="tab" href="#list" role="tab">Danh sách phiếu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tracking-tab" data-toggle="tab" href="#tracking" role="tab">Theo dõi dự trù <span class="badge badge-info">{{ $trackedItems->count() }}</span></a>
                    </li>
                </ul>

                <div class="tab-content" id="estimateTabsContent">
                    <div class="tab-pane fade show active" id="list" role="tabpanel">
                        <div class="table-responsive">
                            <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th style="width: 150px">Bộ Phận</th>
                                <th style="width: 130px">Mã Phiếu</th>
                                <th class="text-center" style="width: 100px">Kỳ Dự Trù</th>
                                <th class="text-center" style="width: 85px">Mặt Hàng</th>
                                <th style="width: 130px">Trạng Thái</th>
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
                                            $displayStatus = $appStatuses[$row->app_status] ?? $row->app_status;
                                            $estStatusClass = match ($row->app_status) {
                                                'approved' => 'approved',
                                                'rejected' => 'rejected',
                                                'cancelled' => 'rejected',
                                                default => 'pending',
                                            };

                                            if ($row->app_status === 'approved') {
                                                if ($row->reception_status === 'completed') {
                                                    $displayStatus = 'Hoàn tất';
                                                    $estStatusClass = 'approved bg-success text-white border-success';
                                                } else {
                                                    $hasPromisedDate = \Illuminate\Support\Facades\DB::table('chemical_estimate_items')
                                                                         ->where('estimate_list_id', $row->id)
                                                                         ->whereNotNull('promised_date')
                                                                         ->exists();
                                                    if ($hasPromisedDate) {
                                                        $displayStatus = 'Cung ứng tiếp nhận và xử lý';
                                                        $estStatusClass = 'approved bg-info text-white border-info';
                                                    }
                                                }
                                            }
                                        @endphp
                                        <span class="md-badge {{ $estStatusClass }}">
                                            {{ $displayStatus }}
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
                                    <td>
                                        @include('pages.estimate.shared.signFlow', ['row' => $row, 'signSteps' => $signSteps])
                                    </td>
                                    <td class="md-sub">
                                        {{ $row->created_by ?: '—' }}
                                        <br><small>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '' }}</small>
                                    </td>
                                    <td>
                                        <div class="md-actions">
                                            <a href="{{ route($estRoute . 'detail', ['id' => $row->id]) }}"
                                                class="btn btn-sm btn-primary" title="Xem chi tiết mặt hàng">
                                                <i class="fas fa-list-ul"></i>
                                            </a>

                                            <button type="button" class="btn btn-sm btn-info btn-est-history"
                                                title="Theo dõi trình ký"
                                                data-url="{{ route($estRoute . 'history', ['id' => $row->id]) }}"
                                                data-title="Phiếu {{ $row->code }}">
                                                <i class="fas fa-route"></i>
                                            </button>

                                            @if ($estEditable && user_can('estimate_chemical_update'))
                                                <button type="button" class="btn btn-sm btn-warning btn-md-edit" title="Sửa"
                                                    data-row="{{ json_encode([
                                                        'id' => $row->id,
                                                        'month' => $row->month,
                                                        'year' => $row->year,
                                                        'note' => $row->note,
                                                    ]) }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form class="form-md-confirm d-inline" data-require-password="1" action="{{ route($estRoute . 'submit') }}"
                                                    method="POST" data-title="Trình ký phiếu {{ $row->code }}?"
                                                    data-text="Phiếu sẽ chuyển sang bước chờ Phó/Trưởng Phòng ký và không sửa được nữa cho tới khi bị từ chối.">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Trình ký">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($row->app_status === 'pending_manager' && $canSignManager && user_can('estimate_chemical_sign'))
                                                <form class="form-md-confirm d-inline" data-require-password="1" action="{{ route($estRoute . 'signManager') }}"
                                                    method="POST" data-title="Ký duyệt bước Phó/Trưởng Phòng?"
                                                    data-text="Phiếu {{ $row->code }} sẽ được chuyển tiếp lên Ban Giám Đốc ký.">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Ký duyệt (Phó/Trưởng Phòng)">
                                                        <i class="fas fa-signature"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($row->app_status === 'pending_director' && $canSignDirector && user_can('estimate_chemical_sign'))
                                                <form class="form-md-confirm d-inline" data-require-password="1" action="{{ route($estRoute . 'signDirector') }}"
                                                    method="POST" data-title="Ban Giám Đốc phê duyệt phiếu {{ $row->code }}?"
                                                    data-text="Sau khi phê duyệt, phiếu chuyển sang bộ phận Cung Ứng tiếp nhận giải quyết.">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Ký duyệt (Ban Giám Đốc)">
                                                        <i class="fas fa-stamp"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if (
                                                ($row->app_status === 'pending_manager' && $canSignManager) ||
                                                    ($row->app_status === 'pending_director' && $canSignDirector)) && user_can('estimate_chemical_sign')
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-est-reject"
                                                    title="Từ chối" data-id="{{ $row->id }}" data-code="{{ $row->code }}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif

                                            @if ($estEditable && user_can('estimate_chemical_delete'))
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
                    <div class="tab-pane fade" id="tracking" role="tabpanel">
                        @include('pages.estimate.shared.trackingTable', ['items' => $trackedItems])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bảng dùng chung sắp theo cột 1, riêng dự trù cần xem kỳ gần nhất trước
        $('#mdTable').DataTable().order([2, 'desc']).draw();
    });
</script>
