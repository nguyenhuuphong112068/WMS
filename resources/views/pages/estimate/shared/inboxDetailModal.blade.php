{{--
| DỰ TRÙ - Xem nhanh nội dung một phiếu trong hộp "Ký duyệt (mọi phòng ban)" (chỉ đọc).
| Biến vào: $req (kèm department_name / department_short / current_step), $items, $signs
--}}
@php
    $idmNum = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ','), '0'), '.');
    $idmDate = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y H:i') : '—';
@endphp

<div class="modal fade md-modal" id="estInboxDetail_{{ $req->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 80vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list-ul mr-2"></i>Phiếu {{ $req->code }}
                    <span class="md-badge pending ml-2">Chờ ký duyệt</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">

                <div class="row mb-3">
                    <div class="col-md-3">
                        <small class="text-muted">Phòng ban</small>
                        <div class="font-weight-bold">{{ $req->department_short ?: '—' }}</div>
                        <div class="md-sub small text-muted">{{ $req->department_name }}</div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Kỳ dự trù</small>
                        <div class="font-weight-bold">{{ str_pad($req->month, 2, '0', STR_PAD_LEFT) }}/{{ $req->year }}</div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Người lập</small>
                        <div class="font-weight-bold">{{ $req->submitted_by ?: $req->created_by ?: '—' }}</div>
                        <div class="md-sub small text-muted">{{ $idmDate($req->submitted_at) }}</div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Ghi chú</small>
                        <div>{{ $req->note ?: '—' }}</div>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Quy trình ký duyệt</small>
                    @foreach ($signs as $s)
                        @php $who = $s->signer_full_name ?: ($s->user_name ?: ($s->role_names ?: '—')); @endphp
                        <div class="md-sub">
                            <b>Bước {{ $s->step_no }}.</b> {{ $who }} —
                            @if ($s->status === 'signed')
                                <span class="text-success">Đã ký {{ $idmDate($s->signed_at) }}</span>
                            @elseif ($s->status === 'rejected')
                                <span class="text-danger">Từ chối: {{ $s->reject_reason }}</span>
                            @elseif ((int) $req->current_step === (int) $s->step_no)
                                <span class="text-primary font-weight-bold">Đang chờ ký</span>
                            @else
                                <span class="text-muted">Chưa tới lượt</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:45px">STT</th>
                                <th style="width:240px">Mặt hàng</th>
                                <th>Thông tin kỹ thuật</th>
                                <th style="width:260px">Số lượng dự trù</th>
                                <th>Mục đích sử dụng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $it)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $it->display_name ?: '—' }}</div>
                                        @unless ($it->category_id)
                                            <span class="est-outside">Ngoài danh mục</span>
                                        @endunless
                                    </td>
                                    <td class="md-sub">{{ $it->technical_information ?: '—' }}</td>
                                    <td>
                                        @forelse ($it->amounts as $amount)
                                            <span class="est-chip">
                                                <b>{{ $idmNum($amount->amount) }} {{ $amount->unit_short_name ?: $amount->unit_name }}</b>
                                                <span>&middot; {{ \Carbon\Carbon::parse($amount->for_month_year)->format('m/Y') }}</span>
                                            </span>
                                        @empty
                                            <span class="md-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td class="md-sub">{{ $it->purpose ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Phiếu chưa có mặt hàng nào.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
