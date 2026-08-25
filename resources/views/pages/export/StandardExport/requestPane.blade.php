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
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'issued' => ['label' => 'Đã cấp', 'class' => 'accepted'],
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
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#requestModal">
            <i class="fas fa-plus mr-1"></i> Tạo đề nghị cấp phát chuẩn
        </button>
        <p class="hint">
            <i class="fas fa-info-circle mr-1"></i>
            Chất chuẩn được quản lý nghiêm ngặt theo <b>Tổ</b>: Tổ lập đề nghị ➔ Quản lý kho cấp phát ➔ Nhân viên tổ chỉ được dùng chuẩn đã cấp.
        </p>
    </div>

    <div class="table-responsive">
        <table id="stdRequestTable" class="table table-bordered table-hover w-100 md-table exp-req-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px">STT</th>
                    <th style="width: 140px">Mã Đề Nghị</th>
                    <th style="width: 130px">Tổ Đề Nghị</th>
                    <th>Danh Sách Đề Nghị Của Tổ</th>
                    <th class="text-center" style="width: 115px">Trạng Thái</th>
                    <th style="width: 130px">Người Lập</th>
                    <th class="text-center" style="width: 100px">Ngày Lập</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $req)
                    @php
                        $items = $requestItems[$req->id] ?? collect();
                    @endphp
                    <tr>
                        <td class="text-center align-top">{{ $loop->iteration }}</td>
                        <td class="align-top">
                            <span class="exp-code">{{ $req->code }}</span>
                            @if ($req->note)
                                <div class="md-sub mt-1" title="{{ $req->note }}">
                                    <i class="fas fa-comment-dots mr-1"></i>{{ Str::limit($req->note, 30) }}
                                </div>
                            @endif
                        </td>
                        <td class="align-top">
                            <span class="font-weight-bold text-primary">{{ $req->group_name ?: '—' }}</span>
                        </td>
                        <td>
                            <table class="table table-sm table-bordered std-req-item-table mb-0 w-100">
                                <thead>
                                    <tr class="text-center">
                                        <th style="width: 16%">Chất Chuẩn</th>
                                        <th style="width: 8%">Qui Cách</th>
                                        <th style="width: 9%" class="text-right">Số Lượng ĐN</th>
                                        <th style="width: 9%" class="text-right">Số Lượng CP</th>
                                        <th style="width: 14%">Tên Sản Phẩm</th>
                                        <th style="width: 10%">Chỉ Tiêu</th>
                                        <th style="width: 11%">Kiểm Nghiệm Viên</th>
                                        <th style="width: 8%">Ghi Chú</th>
                                        <th style="width: 7%">Định Khu</th>
                                        <th style="width: 8%">Ống Chuẩn</th>
                                        <th style="width: 10%" class="text-center">Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>
                                                <span class="font-weight-bold">{{ $item->standard_name }}</span>
                                                <span class="md-tag ml-1">{{ $item->category_code }}</span>
                                            </td>
                                            <td class="text-center">
                                                {{ $item->specification ?: '—' }}
                                            </td>
                                            <td class="text-right">
                                                <span class="exp-amount font-weight-bold">{{ $expNum($item->requested_amount) }}</span>
                                                <small class="text-muted">{{ $item->requested_unit }}</small>
                                            </td>
                                            <td class="text-right">
                                                @if ($item->issued_amount !== null)
                                                    <span class="exp-amount text-success font-weight-bold">{{ $expNum($item->issued_amount) }}</span>
                                                    <small class="text-muted">{{ $item->issued_unit ?: $item->requested_unit }}</small>
                                                @else
                                                    <span class="md-empty">—</span>
                                                @endif
                                            </td>
                                            <td class="md-sub font-weight-bold text-dark">{{ $item->product_name ?: '—' }}</td>
                                            <td class="md-sub">{{ $item->test_criteria ?: '—' }}</td>
                                            <td class="md-sub">{{ $item->analyst_name ?: '—' }}</td>
                                            <td class="md-sub" title="{{ $item->note }}">{{ $item->note ?: '—' }}</td>
                                            <td class="text-center">
                                                @if ($item->location_code || $item->location_name)
                                                    <span class="std-location-tag">{{ $item->location_code ?: $item->location_name }}</span>
                                                @else
                                                    <span class="md-empty">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->import_code)
                                                    <span class="exp-code">{{ $item->import_code }}</span>
                                                    @if ($item->batch_no)
                                                        <small class="text-muted d-block">Lô {{ $item->batch_no }}</small>
                                                    @endif
                                                @else
                                                    <span class="md-empty">Chưa cấp</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($item->status === 'pending')
                                                    <button type="button" class="btn btn-xs btn-success btn-std-issue"
                                                        title="Cấp phát chuẩn cho mục này"
                                                        data-item="{{ json_encode([
                                                            'id' => $item->id,
                                                            'request_code' => $req->code,
                                                            'group_name' => $req->group_name,
                                                            'category_id' => $item->category_id,
                                                            'standard_name' => $item->standard_name,
                                                            'specification' => $item->specification,
                                                            'requested_amount' => (float) $item->requested_amount,
                                                            'requested_unit' => $item->requested_unit,
                                                            'product_name' => $item->product_name,
                                                            'test_criteria' => $item->test_criteria,
                                                            'analyst_name' => $item->analyst_name,
                                                            'note' => $item->note,
                                                        ]) }}">
                                                        <i class="fas fa-hand-holding-medical mr-1"></i> Cấp
                                                    </button>

                                                    <form class="form-md-confirm d-inline"
                                                        action="{{ route('pages.export.standardExport.requestReject') }}"
                                                        method="POST"
                                                        data-title="Từ chối cấp mục này?"
                                                        data-danger="1">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <button type="submit" class="btn btn-xs btn-secondary" title="Từ chối">
                                                            <i class="fas fa-xmark"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="exp-req-badge {{ $stdReqBadge($item->status)['class'] }}">
                                                        {{ $stdReqBadge($item->status)['label'] }}
                                                    </span>
                                                    @if ($item->issued_by)
                                                        <small class="d-block text-muted">{{ $item->issued_by }}</small>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                        <td class="text-center align-top">
                            <span class="exp-req-badge {{ $stdReqBadge($req->status)['class'] }}">
                                {{ $stdReqBadge($req->status)['label'] }}
                            </span>
                        </td>
                        <td class="md-sub align-top">{{ $req->created_by ?: '—' }}</td>
                        <td class="text-center md-sub align-top">{{ $expDate($req->created_at) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center md-sub py-4">Chưa có phiếu đề nghị cấp phát chuẩn nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
