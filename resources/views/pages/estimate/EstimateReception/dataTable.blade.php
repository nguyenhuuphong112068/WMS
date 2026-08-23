@include('pages.estimate.shared.assets')

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    <p class="hint mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        @if ($canReceive)
                            Bấm <b>Tiếp nhận</b> để nhận phiếu về xử lý, xong việc thì bấm <b>Hoàn tất</b>.
                        @else
                            Bạn chỉ được xem. Chỉ tài khoản thuộc bộ phận Cung Ứng mới tiếp nhận được phiếu.
                        @endif
                    </p>
                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th style="width: 130px">Mã Phiếu</th>
                                <th style="width: 150px">Phòng Ban</th>
                                <th class="text-center" style="width: 100px">Kỳ Dự Trù</th>
                                <th class="text-center" style="width: 85px">Mặt Hàng</th>
                                <th style="width: 300px">Trình Ký</th>
                                <th class="text-center" style="width: 130px">Tình Trạng</th>
                                <th style="width: 150px">Cung Ứng Xử Lý</th>
                                <th class="text-center" style="width: 160px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @php $estReception = $row->reception_status ?: 'waiting'; @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="est-code">{{ $row->code }}</span>
                                        @if ($row->note)
                                            <div class="md-sub">
                                                <span class="md-note" title="{{ $row->note }}">{{ $row->note }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $row->department_short_name }}</div>
                                        <div class="md-sub">{{ $row->department_name }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="est-period">{{ str_pad($row->month, 2, '0', STR_PAD_LEFT) }}/{{ $row->year }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="md-tag">{{ $itemCounts[$row->id] ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @include('pages.estimate.shared.signFlow', ['row' => $row, 'signSteps' => $signSteps])
                                    </td>
                                    <td class="text-center">
                                        <span class="est-badge {{ $estReception }}">
                                            {{ $receptionStatuses[$estReception] ?? $estReception }}
                                        </span>
                                    </td>
                                    <td class="md-sub">
                                        @if ($row->received_by)
                                            <div><i class="fas fa-inbox mr-1"></i>{{ $row->received_by }}
                                                <small>{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('d/m/Y H:i') : '' }}</small>
                                            </div>
                                        @endif
                                        @if ($row->completed_by)
                                            <div><i class="fas fa-circle-check mr-1"></i>{{ $row->completed_by }}
                                                <small>{{ $row->completed_at ? \Carbon\Carbon::parse($row->completed_at)->format('d/m/Y H:i') : '' }}</small>
                                            </div>
                                        @endif
                                        @if (! $row->received_by && ! $row->completed_by)
                                            <span class="md-empty">—</span>
                                        @endif
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

                                            @if ($canReceive && $estReception === 'waiting')
                                                <button type="button" class="btn btn-sm btn-success btn-est-reception"
                                                    title="Tiếp nhận" data-mode="receive" data-id="{{ $row->id }}"
                                                    data-code="{{ $row->code }}"
                                                    data-action="{{ route($estRoute . 'receive') }}">
                                                    <i class="fas fa-inbox"></i>
                                                </button>
                                            @endif

                                            @if ($canReceive && $estReception === 'received')
                                                <button type="button" class="btn btn-sm btn-success btn-est-reception"
                                                    title="Hoàn tất giải quyết" data-mode="complete" data-id="{{ $row->id }}"
                                                    data-code="{{ $row->code }}"
                                                    data-action="{{ route($estRoute . 'complete') }}">
                                                    <i class="fas fa-flag-checkered"></i>
                                                </button>
                                            @endif
                                        </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Giữ nguyên thứ tự Controller đã sắp: chờ tiếp nhận nằm trên cùng
        $('#mdTable').DataTable().order([]).draw();
    });
</script>
