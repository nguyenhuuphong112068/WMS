{{--
| SỬ DỤNG VẬT TƯ - TAB ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN
|
| Mô hình 3 bước (giống Sử Dụng Hoá Chất):
| 1. Phòng A (đang thiếu) tạo đề nghị, chọn phòng B (đang giữ) để gửi đến.
| 2. Phòng B thấy đề nghị ở bảng "cần cấp phát", chọn mã xuất nhập cụ thể + số lượng rồi
|    bấm Cấp phát: trừ tồn B ngay, mục chuyển sang "Chờ nhận" - CHƯA tạo tồn cho A.
|    B có thể Từ chối riêng từng mục kèm lý do nếu không cấp được.
| 3. Phòng A thấy mục "Chờ nhận" ở bảng "đã gửi đi", tự chọn định khu của phòng mình rồi
|    bấm Nhận: lúc này mới thật sự tạo mã xuất nhập mới cho A (bắt buộc A đã khai vật tư
|    này trước). A cũng có thể Từ chối nhận để hoàn tồn lại cho B.
|
| Biến đặt tiền tố $mt... để không đè $expReqBadge của tab đề nghị nội bộ.
--}}

@php
    $mtStatus = [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'canceled' => ['label' => 'Đã huỷ', 'class' => 'rejected'],
        // Trạng thái riêng của TỪNG MỤC (khác trạng thái tổng của phiếu ở trên)
        'issued' => ['label' => 'Chờ nhận', 'class' => 'issued'],
        'received' => ['label' => 'Đã nhận', 'class' => 'accepted'],
        'returned' => ['label' => 'Đã từ chối nhận', 'class' => 'rejected'],
    ];
    $mtBadge = fn($status) => $mtStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
@endphp

<div class="exp-pane {{ $activeTab === 'transfer' ? 'is-active' : '' }}" id="mePaneTransfer">

    @perm('export_material_request')
        <div class="md-toolbar">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#matTransferRequestModal">
                <i class="fas fa-plus mr-1"></i> Tạo đề nghị chuyển liên phòng ban
            </button>
        </div>
    @endperm

    <h6 class="font-weight-bold text-primary mt-3 mb-2">
        <i class="fas fa-paper-plane mr-1"></i> Đề nghị phòng mình đã gửi đi
    </h6>
    <div class="table-responsive mb-4">
        <table id="matTransferSentTable" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="width: 190px">Mã Đề Nghị</th>
                    <th style="width: 170px">Gửi Đến Phòng</th>
                    <th style="width: 120px">Số Lượng Mục</th>
                    <th style="width: 140px">Trạng Thái</th>
                    <th style="width: 140px">Người Lập</th>
                    <th style="width: 120px">Ngày Lập</th>
                    <th style="width: 130px">Thao Tác</th>
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
                                <i class="fas fa-boxes mr-1"></i> {{ $items->count() }} vật tư
                            </span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="exp-req-badge {{ $mtBadge($req->status)['class'] }}">{{ $mtBadge($req->status)['label'] }}</span>
                        </td>
                        <td class="align-middle">{{ $req->updated_by ?: $req->created_by ?: '—' }}</td>
                        <td class="text-center align-middle">{{ $expDate($req->created_at) }}</td>
                        <td class="text-center align-middle" style="white-space: nowrap;">
                            @if ($req->status === 'draft' && user_can('export_material_request'))
                                <button type="button" class="btn btn-sm btn-warning px-2 py-1 shadow-sm mr-1"
                                    data-toggle="modal" data-target="#matTransferEditModal_{{ $req->id }}"
                                    title="Chỉnh sửa đề nghị">
                                    <i class="fas fa-edit mr-1"></i> Sửa
                                </button>
                                <form action="{{ route('pages.export.materialExport.transferRequestSend') }}" method="POST" class="d-inline-block">
                                    @csrf
                                    <input type="hidden" name="transfer_request_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-primary px-2 py-1 shadow-sm mr-1" title="Gửi đề nghị này đi">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                                <form class="form-md-confirm d-inline-block"
                                    action="{{ route('pages.export.materialExport.transferRequestDestroy') }}" method="POST"
                                    data-title="Huỷ đề nghị {{ $req->code }}?"
                                    data-text="Phiếu đề nghị đang lưu tạm sẽ bị huỷ."
                                    data-danger="1">
                                    @csrf
                                    <input type="hidden" name="transfer_request_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-danger px-2 py-1 shadow-sm" title="Huỷ đề nghị">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            @elseif ($awaitingReceiptCount > 0 && (user_can('export_material_transfer_receive') || user_can('export_material_transfer_return')))
                                <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm"
                                    data-toggle="modal" data-target="#matTransferDetailModal_{{ $req->id }}"
                                    title="Có mục đã được cấp phát, chờ nhận">
                                    <i class="fas fa-inbox mr-1"></i> Nhận ({{ $awaitingReceiptCount }})
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-info px-2 py-1 shadow-sm"
                                    data-toggle="modal" data-target="#matTransferDetailModal_{{ $req->id }}"
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

    <h6 class="font-weight-bold text-success mb-2">
        <i class="fas fa-inbox mr-1"></i> Đề nghị phòng ban khác gửi đến - cần cấp phát
    </h6>
    <div class="table-responsive">
        <table id="matTransferReceivedTable" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="width: 190px">Mã Đề Nghị</th>
                    <th style="width: 170px">Phòng Đề Nghị</th>
                    <th style="width: 120px">Số Lượng Mục</th>
                    <th style="width: 140px">Trạng Thái</th>
                    <th style="width: 140px">Người Lập</th>
                    <th style="width: 120px">Ngày Lập</th>
                    <th style="width: 130px">Thao Tác</th>
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
                                <i class="fas fa-boxes mr-1"></i> {{ $items->count() }} vật tư
                            </span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="exp-req-badge {{ $mtBadge($req->status)['class'] }}">{{ $mtBadge($req->status)['label'] }}</span>
                        </td>
                        <td class="align-middle">{{ $req->updated_by ?: $req->created_by ?: '—' }}</td>
                        <td class="text-center align-middle">{{ $expDate($req->created_at) }}</td>
                        <td class="text-center align-middle" style="white-space: nowrap;">
                            @if (in_array($req->status, ['pending', 'partial']) && user_can('export_material_transfer'))
                                <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm"
                                    data-toggle="modal" data-target="#matTransferDetailModal_{{ $req->id }}"
                                    title="Cấp phát vật tư cho phiếu này">
                                    <i class="fas fa-hand-holding-medical mr-1"></i> Cấp phát
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-info px-2 py-1 shadow-sm"
                                    data-toggle="modal" data-target="#matTransferDetailModal_{{ $req->id }}"
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
