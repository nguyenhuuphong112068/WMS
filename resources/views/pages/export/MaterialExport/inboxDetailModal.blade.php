{{-- Xem nhanh nội dung một đề nghị trong hộp ký duyệt liên phòng ban (chỉ đọc).
     Biến vào: $req, $items, $signs --}}
<div class="modal fade md-modal" id="inboxDetailModal_{{ $req->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 80vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list-ul mr-2"></i>Đề Nghị {{ $req->code }}
                    @if ($req->name) <span class="text-muted font-weight-normal">— {{ $req->name }}</span>@endif
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
                        <small class="text-muted">Người lập</small>
                        <div class="font-weight-bold">{{ $req->submitted_by ?: $req->created_by ?: '—' }}</div>
                        <div class="md-sub small text-muted">{{ $expDate($req->submitted_at) }}</div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Quy trình ký duyệt</small>
                        <div>
                            @foreach ($signs as $s)
                                <div class="md-sub">
                                    <b>{{ $s->step_no }}.</b>
                                    {{ $s->signer_full_name ?: ($s->user_name ?: ($s->role_names ?: '—')) }} —
                                    @if ($s->status === 'signed')
                                        <span class="text-success">Đã ký {{ $expDateTime($s->signed_at) }}</span>
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
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Ghi chú</small>
                        <div>{{ $req->note ?: '—' }}</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:45px">STT</th>
                                <th style="width:200px">Vật tư</th>
                                <th style="width:150px">Quy cách</th>
                                <th class="text-right" style="width:110px">SL đề nghị</th>
                                <th style="width:150px">Thiết bị liên quan</th>
                                <th>Mục đích</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $it)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $it->display_name ?: '—' }}</div>
                                        @if ($it->category_code)
                                            <div class="md-sub"><span class="md-tag">{{ $it->category_code }}</span></div>
                                        @endif
                                        @unless ($it->category_id) <span class="badge badge-secondary">Ngoài danh mục</span> @endunless
                                    </td>
                                    <td class="md-sub">{{ $it->technical_specification ?: '—' }}</td>
                                    <td class="text-right">{{ $expNum($it->requested_amount) }} {{ $it->requested_unit }}</td>
                                    <td class="md-sub">{{ $it->product_name ?: '—' }}</td>
                                    <td class="md-sub">{{ $it->purpose ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Đề nghị chưa có mục nào.</td></tr>
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
