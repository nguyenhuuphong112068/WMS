{{--
| SỬ DỤNG HOÁ CHẤT - TAB ĐỀ NGHỊ CHUYỂN HOÁ CHẤT LIÊN PHÒNG BAN
|
| Mô hình 3 bước:
| 1. Phòng A (đang thiếu) tạo đề nghị, chọn phòng B (đang giữ) để gửi đến.
| 2. Phòng B thấy đề nghị ở mục "Cần cấp phát", chọn phiếu nhập cụ thể + số lượng rồi
|    bấm Cấp phát: trừ tồn B ngay, item chuyển sang "Chờ nhận" - CHƯA tạo tồn cho A.
|    B có thể Từ chối riêng từng mục kèm lý do nếu không cấp được.
| 3. Phòng A thấy mục "Chờ nhận" ở bảng "Đề nghị phòng mình đã gửi đi", tự chọn vị trí
|    lưu của phòng mình rồi bấm Nhận: lúc này mới thật sự tạo phiếu nhập mới cho A (bắt
|    buộc A đã khai danh mục hoá chất này trước). A cũng có thể Từ chối nhận để hoàn tồn
|    lại cho B nếu phát hiện cấp sai.
|
| Mỗi card 2 tab con (giống Vật Tư - MaterialExport/transferLists):
| - Chờ xử lý   : luôn hiện đủ, không lọc ngày (gửi đi: chưa cấp xong / còn mục chờ nhận;
|                 gửi đến: Chờ cấp phát / Cấp một phần).
| - Đã hoàn thành: lọc theo ngày lập.
| Tab con đang mở giữ trên URL (tsub_sent / tsub_recv = done) để bấm Lọc / sang trang xong
| vẫn quay về đúng tab. CSS + JS tab con ở pages/export/shared/assets.
--}}

@php
    $expReqStatus = [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'canceled' => ['label' => 'Đã huỷ', 'class' => 'rejected'],
    ];
    $expReqBadge = fn($status) => $expReqStatus[$status] ?? ['label' => $status, 'class' => 'pending'];

    $ctCards = [
        [
            'side' => 'sent', 'class' => 'is-sent', 'icon' => 'fa-paper-plane',
            'title' => 'Đề nghị phòng mình đã gửi đi', 'key' => 'Sent',
            'subKey' => 'tsub_sent', 'donePrefix' => 'tsdone_',
            'pending' => $transferSent, 'done' => $transferSentDone,
            'doneRange' => $transferSentDoneRange, 'donePerPage' => $transferSentDonePerPage,
            'emptyPending' => 'Không có đề nghị gửi đi nào đang chờ xử lý.',
            'emptyDone' => 'Không có đề nghị đã hoàn thành trong khoảng đã lọc.',
            'partnerLabel' => 'Gửi Đến Phòng',
        ],
        [
            'side' => 'received', 'class' => 'is-received', 'icon' => 'fa-inbox',
            'title' => 'Đề nghị phòng ban khác gửi đến', 'key' => 'Received',
            'subKey' => 'tsub_recv', 'donePrefix' => 'tdone_',
            'pending' => $transferReceived, 'done' => $transferDone,
            'doneRange' => $transferDoneRange, 'donePerPage' => $transferDonePerPage,
            'emptyPending' => 'Không còn đề nghị nào cần cấp phát.',
            'emptyDone' => 'Không có đề nghị đã hoàn thành trong khoảng đã lọc.',
            'partnerLabel' => 'Phòng Đề Nghị',
        ],
    ];
@endphp

@tab('export_chemical', 'request')
<div class="exp-pane {{ $activeTab === 'request' ? 'is-active' : '' }}" id="expPaneRequest">

    <div class="md-toolbar">
        @perm('export_chemical_request')
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#transferRequestModal">
                <i class="fas fa-plus mr-1"></i> Tạo đề nghị chuyển liên phòng ban
            </button>
        @endperm
    </div>

    @foreach ($ctCards as $ctCard)
    @php
        $ctSide = $ctCard['side'];
        $ctSubDone = request($ctCard['subKey']) === 'done';
        $ctPaneId = 'chemTransfer' . $ctCard['key'];
    @endphp
    <div class="exp-req-card {{ $ctCard['class'] }}">
        <div class="exp-req-card-header">
            <i class="fas {{ $ctCard['icon'] }}"></i>
            <span class="exp-req-card-title">{{ $ctCard['title'] }}</span>
            <span class="exp-req-card-count" title="Chờ xử lý">{{ $ctCard['pending']->total() }}</span>
            <i class="fas fa-chevron-down exp-req-card-toggle"></i>
        </div>
        <div class="exp-req-card-body">

    <div class="mt-subhead">
        <ul class="nav nav-tabs mt-subtabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $ctSubDone ? '' : 'active' }}" data-toggle="tab" role="tab"
                    href="#{{ $ctPaneId }}Pending" data-sub-key="{{ $ctCard['subKey'] }}" data-sub-value="">
                    <i class="fas fa-hourglass-half mr-1"></i> Chờ xử lý
                    <span class="badge badge-warning">{{ $ctCard['pending']->total() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $ctSubDone ? 'active' : '' }}" data-toggle="tab" role="tab"
                    href="#{{ $ctPaneId }}Done" data-sub-key="{{ $ctCard['subKey'] }}" data-sub-value="done">
                    <i class="fas fa-check-double mr-1"></i> Đã hoàn thành
                    <span class="badge badge-success">{{ $ctCard['done']->total() }}</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="tab-content">
    @foreach (['pending' => 'Pending', 'done' => 'Done'] as $ctSub => $ctSubId)
        @php
            $ctList = $ctCard[$ctSub];
            $ctIsDone = $ctSub === 'done';
        @endphp
        <div class="tab-pane fade {{ $ctIsDone === $ctSubDone ? 'show active' : '' }}" id="{{ $ctPaneId }}{{ $ctSubId }}" role="tabpanel">

    @if ($ctIsDone)
        @include('pages.shared.rangeFilter', [
            'rfRoute' => $expRoute . 'list',
            'rfTab' => 'request',
            'rfPrefix' => $ctCard['donePrefix'],
            'rfRange' => $ctCard['doneRange'],
            'rfPerPage' => $ctCard['donePerPage'],
            'rfSearch' => false,
            'rfDateLabel' => 'Ngày lập',
            'rfInBar' => true,
            'rfHidden' => [$ctCard['subKey'] => 'done'],
        ])
    @endif

    <div class="table-responsive">
        <table id="{{ $ctPaneId }}{{ $ctIsDone ? 'Done' : '' }}Table" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="width: 130px">Mã Đề Nghị</th>
                    <th>Tiêu Đề</th>
                    <th style="width: 170px">{{ $ctCard['partnerLabel'] }}</th>
                    <th style="width: 120px">Số Lượng Mục</th>
                    <th style="width: 140px">Trạng Thái</th>
                    <th style="width: 140px">Người Lập</th>
                    <th style="width: 120px">Ngày Lập</th>
                    <th style="width: 120px">Ngày Mong Muốn</th>
                    <th style="width: 110px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ctList as $req)
                    @php
                        $items = $transferItems[$req->id] ?? collect();
                        $awaitingReceiptCount = $items->where('status', 'issued')->count();
                    @endphp
                    <tr>
                        <td class="text-center align-middle">{{ $ctList->firstItem() + $loop->index }}</td>
                        <td class="align-middle">
                            <span class="exp-code font-weight-bold">{{ $req->code }}</span>
                        </td>
                        <td class="align-middle">
                            <span class="font-weight-bold">{{ $req->title ?: '—' }}</span>
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
                                <i class="fas fa-flask mr-1"></i> {{ $items->count() }} hoá chất
                            </span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="exp-req-badge {{ $expReqBadge($req->status)['class'] }}">
                                {{ $expReqBadge($req->status)['label'] }}
                            </span>
                        </td>
                        <td class="align-middle">{{ $req->updated_by ?: $req->created_by ?: '—' }}</td>
                        <td class="text-center align-middle">{{ $expDate($req->created_at) }}</td>
                        <td class="text-center align-middle">{{ $req->needed_date ? $expDate($req->needed_date) : '—' }}</td>
                        <td class="text-center align-middle" style="white-space: nowrap;">
                            @if ($ctSide === 'sent' && $req->status === 'draft' && user_can('export_chemical_request'))
                                <button type="button" class="btn btn-sm btn-warning px-2 py-1 shadow-sm mr-1"
                                    data-toggle="modal"
                                    data-target="#transferRequestEditModal_{{ $req->id }}"
                                    title="Chỉnh sửa đề nghị">
                                    <i class="fas fa-edit mr-1"></i> Sửa
                                </button>
                                <form action="{{ route('pages.export.chemicalExport.transferRequestDestroy') }}" method="POST" class="d-inline-block form-delete-req" onsubmit="return confirm('Bạn có chắc chắn muốn huỷ đề nghị này không? Thao tác này không thể phục hồi.');">
                                    @csrf
                                    <input type="hidden" name="transfer_request_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-danger px-2 py-1 shadow-sm" title="Huỷ/Xoá đề nghị">
                                        <i class="fas fa-trash-alt mr-1"></i> Huỷ
                                    </button>
                                </form>
                            @elseif ($ctSide === 'sent' && $awaitingReceiptCount > 0 && (user_can('export_chemical_transfer_receive') || user_can('export_chemical_transfer_return')))
                                <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm"
                                    data-toggle="modal"
                                    data-target="#transferDetailModal_{{ $req->id }}"
                                    title="Có mục đã được cấp phát, chờ nhận">
                                    <i class="fas fa-inbox mr-1"></i> Nhận ({{ $awaitingReceiptCount }})
                                </button>
                            @elseif ($ctSide === 'received' && in_array($req->status, ['pending', 'partial']) && user_can('export_chemical_transfer'))
                                <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm mr-1"
                                    data-toggle="modal"
                                    data-target="#transferDetailModal_{{ $req->id }}"
                                    title="Cấp phát hoá chất cho phiếu này">
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
                        <td colspan="10" class="text-center text-muted py-4">{{ $ctIsDone ? $ctCard['emptyDone'] : $ctCard['emptyPending'] }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @php
        // Sang trang ở tab nào thì mở lại đúng tab đó
        $ctList->appends([$ctCard['subKey'] => $ctIsDone ? 'done' : null]);
    @endphp
    @include('pages.shared.paginator', [
        'pgItems' => $ctList,
        'pgTab' => 'request',
        'pgUnit' => 'đề nghị',
    ])

        </div>
    @endforeach
    </div>

        </div>
    </div>
    @endforeach
</div>
@endtab
