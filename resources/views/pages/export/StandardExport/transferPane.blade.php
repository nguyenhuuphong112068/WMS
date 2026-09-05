{{--
| SỬ DỤNG CHẤT CHUẨN - TAB ĐỀ NGHỊ CẤP PHÁT CHUẨN LIÊN PHÒNG BAN
|
| Khác tab nội bộ (chỉ RESERVE ống cho Tổ, tồn kho chưa đổi): đây là CHUYỂN TỒN THẬT
| giữa hai phòng ban (cùng hoặc khác công ty), theo mô hình 3 bước:
|
| 1. Phòng A (đang cần chuẩn) tạo đề nghị, chọn phòng B (đang giữ chuẩn) để gửi đến.
| 2. Phòng B thấy đề nghị ở mục "Cần cấp phát", chọn ống cụ thể + số lượng rồi bấm Cấp
|    phát: trừ tồn B ngay, item chuyển sang "Chờ nhận" - CHƯA tạo tồn cho A. B có thể Từ
|    chối riêng từng mục kèm lý do nếu không cấp được.
| 3. Phòng A thấy mục "Chờ nhận" ở bảng "Đề nghị phòng mình đã gửi đi", tự khai 4 thông
|    tin riêng của phòng mình (vị trí lưu/chỉ tiêu kiểm/kiểm soát khối lượng/chiết ống)
|    rồi bấm Nhận: lúc này mới thật sự tạo phiếu nhập mới cho A (bắt buộc A đã khai danh
|    mục chất chuẩn này trước). A cũng có thể Từ chối nhận để hoàn tồn lại cho B.
--}}

@php
    $stdReqStatus = $stdReqStatus ?? [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'canceled' => ['label' => 'Đã huỷ', 'class' => 'rejected'],
    ];
    $stdReqBadge = $stdReqBadge ?? fn($status) => $stdReqStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
@endphp

<div class="exp-pane {{ $activeTab === 'transfer' ? 'is-active' : '' }}" id="expPaneTransfer">

    <div class="md-toolbar">
        @perm('export_standard_request')
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#transferRequestModal">
                <i class="fas fa-plus mr-1"></i> Tạo đề nghị cấp phát liên phòng ban
            </button>
        @endperm
        <p class="hint">
            <i class="fas fa-info-circle mr-1"></i>
            Đề nghị và cấp phát chuẩn <b>giữa các phòng ban</b> (cùng hoặc khác công ty). Cấp phát trừ tồn phòng
            gửi ngay, phòng nhận tự khai thông tin riêng và xác nhận Nhận thì mới thật sự có tồn.
        </p>
    </div>

    <h6 class="font-weight-bold text-primary mt-3 mb-2"><i class="fas fa-paper-plane mr-1"></i> Đề nghị phòng mình đã gửi đi</h6>
    <div class="table-responsive mb-4">
        <table id="stdTransferSentTable" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="width: 190px">Mã Đề Nghị</th>
                    <th style="width: 170px">Gửi Đến Phòng</th>
                    <th style="width: 120px">Số Lượng Mục</th>
                    <th style="width: 140px">Trạng Thái</th>
                    <th style="width: 140px">Người Lập</th>
                    <th style="width: 120px">Ngày Lập</th>
                    <th style="width: 110px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transferSent as $req)
                    @php
                        $items = $transferItems[$req->id] ?? collect();
                        $awaitingReceiptCount = $items->where('status', 'issued')->count();
                    @endphp
                    <tr>
                        <td class="text-center align-middle">{{ $loop->iteration }}</td>
                        <td class="align-middle">
                            <span class="exp-code font-weight-bold">{{ $req->code }}</span>
                            @if ($req->note)
                                <div class="md-sub mt-1 text-muted" title="{{ $req->note }}">
                                    <i class="fas fa-comment-dots mr-1"></i>{{ Str::limit($req->note, 25) }}
                                </div>
                            @endif
                        </td>
                        <td class="align-middle">
                            <span class="font-weight-bold text-primary">{{ $req->partner_name ?: '—' }}</span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge badge-info px-2 py-1" style="font-size: 0.82rem;">
                                <i class="fas fa-vial mr-1"></i> {{ $items->count() }} chất chuẩn
                            </span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="exp-req-badge {{ $stdReqBadge($req->status)['class'] }}">
                                {{ $stdReqBadge($req->status)['label'] }}
                            </span>
                        </td>
                        <td class="align-middle">{{ $req->updated_by ?: $req->created_by ?: '—' }}</td>
                        <td class="text-center align-middle">{{ $expDate($req->created_at) }}</td>
                        <td class="text-center align-middle" style="white-space: nowrap;">
                            @if ($req->status === 'draft' && user_can('export_standard_request'))
                                <button type="button" class="btn btn-sm btn-warning px-2 py-1 shadow-sm mr-1"
                                    data-toggle="modal"
                                    data-target="#transferRequestEditModal_{{ $req->id }}"
                                    title="Chỉnh sửa đề nghị">
                                    <i class="fas fa-edit mr-1"></i> Sửa
                                </button>
                                <form action="{{ route('pages.export.standardExport.transferRequestDestroy') }}" method="POST" class="d-inline-block form-delete-req" onsubmit="return confirm('Bạn có chắc chắn muốn huỷ đề nghị này không? Thao tác này không thể phục hồi.');">
                                    @csrf
                                    <input type="hidden" name="transfer_request_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-danger px-2 py-1 shadow-sm" title="Huỷ/Xoá đề nghị">
                                        <i class="fas fa-trash-alt mr-1"></i> Huỷ
                                    </button>
                                </form>
                            @elseif ($awaitingReceiptCount > 0 && (user_can('export_standard_transfer_receive') || user_can('export_standard_transfer_return')))
                                <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm"
                                    data-toggle="modal"
                                    data-target="#transferDetailModal_{{ $req->id }}"
                                    title="Có mục đã được cấp phát, chờ nhận">
                                    <i class="fas fa-inbox mr-1"></i> Nhận ({{ $awaitingReceiptCount }})
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-info px-2 py-1 shadow-sm"
                                    data-toggle="modal"
                                    data-target="#transferDetailModal_{{ $req->id }}"
                                    title="Xem chi tiết phiếu đề nghị">
                                    <i class="fas fa-eye mr-1"></i> Xem
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Phòng mình chưa gửi đề nghị liên phòng ban nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h6 class="font-weight-bold text-success mb-2"><i class="fas fa-inbox mr-1"></i> Đề nghị phòng ban khác gửi đến - cần cấp phát</h6>
    <div class="table-responsive">
        <table id="stdTransferReceivedTable" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="width: 190px">Mã Đề Nghị</th>
                    <th style="width: 170px">Phòng Đề Nghị</th>
                    <th style="width: 120px">Số Lượng Mục</th>
                    <th style="width: 140px">Trạng Thái</th>
                    <th style="width: 140px">Người Lập</th>
                    <th style="width: 120px">Ngày Lập</th>
                    <th style="width: 110px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transferReceived as $req)
                    @php $items = $transferItems[$req->id] ?? collect(); @endphp
                    <tr>
                        <td class="text-center align-middle">{{ $loop->iteration }}</td>
                        <td class="align-middle">
                            <span class="exp-code font-weight-bold">{{ $req->code }}</span>
                            @if ($req->note)
                                <div class="md-sub mt-1 text-muted" title="{{ $req->note }}">
                                    <i class="fas fa-comment-dots mr-1"></i>{{ Str::limit($req->note, 25) }}
                                </div>
                            @endif
                        </td>
                        <td class="align-middle">
                            <span class="font-weight-bold text-primary">{{ $req->partner_name ?: '—' }}</span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge badge-info px-2 py-1" style="font-size: 0.82rem;">
                                <i class="fas fa-vial mr-1"></i> {{ $items->count() }} chất chuẩn
                            </span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="exp-req-badge {{ $stdReqBadge($req->status)['class'] }}">
                                {{ $stdReqBadge($req->status)['label'] }}
                            </span>
                        </td>
                        <td class="align-middle">{{ $req->updated_by ?: $req->created_by ?: '—' }}</td>
                        <td class="text-center align-middle">{{ $expDate($req->created_at) }}</td>
                        <td class="text-center align-middle" style="white-space: nowrap;">
                            @if (in_array($req->status, ['pending', 'partial']) && user_can('export_standard_issue'))
                                <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm mr-1"
                                    data-toggle="modal"
                                    data-target="#transferDetailModal_{{ $req->id }}"
                                    title="Cấp phát chuẩn cho phiếu này">
                                    <i class="fas fa-hand-holding-medical mr-1"></i> Cấp phát
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-info px-2 py-1 shadow-sm"
                                    data-toggle="modal"
                                    data-target="#transferDetailModal_{{ $req->id }}"
                                    title="Xem chi tiết phiếu đề nghị">
                                    <i class="fas fa-eye mr-1"></i> Xem
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Chưa có phòng ban nào gửi đề nghị đến phòng mình.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
