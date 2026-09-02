{{--
| SỬ DỤNG CHẤT CHUẨN - TAB ĐỀ NGHỊ CẤP PHÁT CHUẨN
|
| Quy trình nghiêm ngặt:
| 1. Tổ tạo phiếu đề nghị cấp phát (chọn chuẩn, qui cách, SL, sản phẩm, chỉ tiêu, KNV, ghi chú)
| 2. Thủ kho / Quản lý kho cấp phát ống chuẩn cụ thể (mã ống, định khu, SL cấp, ĐVT)
| 3. Nhân viên thuộc tổ chỉ được sử dụng các chuẩn đã được cấp phát cho tổ mình.
--}}

@php
    $stdReqStatus = [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'issued' => ['label' => 'Đã cấp', 'class' => 'accepted'],
        'canceled' => ['label' => 'Đã huỷ', 'class' => 'rejected'],
    ];
    $stdReqBadge = fn($status) => $stdReqStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
@endphp

<style>
    .std-req-item-table {
        background: #f8fafc;
        border-radius: 6px;
        font-size: 0.84rem;
    }
    .std-req-item-table th {
        background: #e2e8f0;
        color: #1e293b;
        font-size: 0.78rem;
        padding: 6px 8px !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .std-req-item-table td {
        padding: 6px 8px !important;
        vertical-align: middle !important;
    }
    .std-location-tag {
        display: inline-block;
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        border-radius: 4px;
        padding: 1px 6px;
        font-weight: 700;
        font-size: 0.75rem;
    }
</style>

<div class="exp-pane {{ $activeTab === 'request' ? 'is-active' : '' }}" id="expPaneRequest">

    <div class="md-toolbar">
        @perm('export_standard_request')
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#requestModal">
                <i class="fas fa-plus mr-1"></i> Tạo đề nghị cấp phát chuẩn
            </button>
        @endperm
        <p class="hint">
            <i class="fas fa-info-circle mr-1"></i>
            Chất chuẩn được quản lý nghiêm ngặt theo <b>Tổ</b>: Tổ lập đề nghị ➔ Quản lý kho cấp phát ➔ Nhân viên tổ chỉ được dùng chuẩn đã cấp.
        </p>
    </div>

    <div class="table-responsive">
        <table id="stdRequestTable" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="width: 160px">Mã Đề Nghị</th>
                    <th style="width: 160px">Tổ Đề Nghị</th>
                    <th style="width: 130px">Số Lượng Mục</th>
                    <th style="width: 140px">Trạng Thái</th>
                    <th style="width: 140px">Người Lập</th>
                    <th style="width: 120px">Ngày Lập</th>
                    <th style="width: 110px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $req)
                    @php
                        $items = $requestItems[$req->id] ?? collect();
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
                            <span class="font-weight-bold text-primary">{{ $req->group_name ?: '—' }}</span>
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
                        <td class="align-middle">{{ $req->created_by ?: '—' }}</td>
                        <td class="text-center align-middle">{{ $expDate($req->created_at) }}</td>
                        <td class="text-center align-middle" style="white-space: nowrap;">
                            @if ($req->status === 'draft' && user_can('export_standard_request'))
                                <button type="button" class="btn btn-sm btn-warning px-2 py-1 shadow-sm mr-1"
                                    data-toggle="modal"
                                    data-target="#requestEditModal_{{ $req->id }}"
                                    title="Chỉnh sửa đề nghị">
                                    <i class="fas fa-edit mr-1"></i> Sửa
                                </button>
                                <form action="{{ route('pages.export.standardExport.requestDestroy') }}" method="POST" class="d-inline-block form-delete-req" onsubmit="return confirm('Bạn có chắc chắn muốn huỷ đề nghị này không? Thao tác này không thể phục hồi.');">
                                    @csrf
                                    <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-danger px-2 py-1 shadow-sm mr-1" title="Huỷ/Xoá đề nghị">
                                        <i class="fas fa-trash-alt mr-1"></i> Huỷ
                                    </button>
                                </form>
                            @elseif (in_array($req->status, ['pending', 'partial']) && user_can('export_standard_issue'))
                                <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm mr-1"
                                    data-toggle="modal"
                                    data-target="#requestDetailModal_{{ $req->id }}"
                                    title="Cấp phát chuẩn cho phiếu này">
                                    <i class="fas fa-hand-holding-medical mr-1"></i> Cấp phát
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-info px-2 py-1 shadow-sm"
                                data-toggle="modal"
                                data-target="#requestDetailModal_{{ $req->id }}"
                                title="Xem chi tiết phiếu đề nghị">
                                <i class="fas fa-eye mr-1"></i> Xem
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Chưa có phiếu đề nghị cấp phát chuẩn nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
