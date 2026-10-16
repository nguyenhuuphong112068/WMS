{{--
| HAI BẢNG ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN - "phòng mình đã gửi đi" và "gửi đến - cần
| cấp phát". Dùng chung cho tab "Chuyển liên phòng ban" (transferPane) và tab "Đề nghị VPP"
| (vppPane) - cùng bộ bảng material_transfer_requests, chỉ khác phòng nhận đề nghị.
|
| Biến vào: $mtTab (tên tab cho bộ lọc / phân trang), $mtPrefix (tiền tố bộ lọc: t | v),
| $mtTableId, $mtSent / $mtReceived (paginator), $mtSentRange / $mtSentPerPage,
| $mtReceivedRange / $mtReceivedPerPage, $mtReceivedTitle, $mtEmptySent, $mtEmptyReceived,
| $mtShowReceived (false thì bỏ hẳn bảng gửi đến - phòng không bao giờ nhận loại đề nghị này),
| $mtCreate (tuỳ chọn ['target' => '#modal', 'label' => ...]: nút tạo đề nghị đặt cùng hàng bộ
| lọc của bảng "đã gửi đi" thay vì chiếm một hàng riêng phía trên).
| Biến đặt tiền tố $mt... để không đè $expReqBadge của tab đề nghị nội bộ.
--}}
@php
    $mtStatus = [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending_sign' => ['label' => 'Chờ ký', 'class' => 'warning'],
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
    // Bước ký của phiếu (Dữ Liệu Gốc > Trình Ký Đề Nghị Liên Phòng Ban) - App\Support\MaterialTransferSignFlow
    $mtSignsOf = fn($req) => ($transferSigns ?? collect())->get($req->id, collect());
    $mtToSign = $mtToSign ?? collect();
@endphp

@once
    <style>
        /* Bộ lọc + nút tạo đề nghị chung một hàng */
        .mt-filterbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px 12px;
            margin-bottom: 10px;
        }

        .mt-filterbar .rgf-box {
            flex: 1 1 auto;
            margin-bottom: 0;
        }

        .mt-filterbar .mt-create-btn {
            height: 34px;
            white-space: nowrap;
        }
    </style>
@endonce
    @if ($mtToSign->count())
    <div class="exp-req-card is-sign">
        <div class="exp-req-card-header">
            <i class="fas fa-file-signature"></i>
            <span class="exp-req-card-title">Đề nghị chờ bạn ký duyệt</span>
            <span class="exp-req-card-count">{{ $mtToSign->count() }}</span>
            <i class="fas fa-chevron-down exp-req-card-toggle"></i>
        </div>
        <div class="exp-req-card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover w-100 exp-req-table" data-no-datatable>
                    <thead>
                        <tr class="text-center">
                            <th style="width: 50px">STT</th>
                            <th style="width: 130px">Mã Đề Nghị</th>
                            <th>Tiêu Đề</th>
                            <th style="width: 170px">Phòng Đề Nghị</th>
                            <th style="width: 170px">Gửi Đến Phòng</th>
                            <th style="width: 120px">Số Lượng Mục</th>
                            <th style="width: 220px">Trình Ký</th>
                            <th style="width: 120px">Ngày Lập</th>
                            <th style="width: 190px">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mtToSign as $req)
                            <tr>
                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle"><span class="exp-code font-weight-bold">{{ $req->code }}</span></td>
                                <td class="align-middle font-weight-bold">{{ $req->title ?: '—' }}</td>
                                <td class="align-middle">{{ $req->requester_name ?: '—' }}</td>
                                <td class="align-middle">{{ $req->receiver_name ?: '—' }}</td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-info px-2 py-1" style="font-size: 0.82rem;">
                                        <i class="fas fa-boxes mr-1"></i> {{ ($transferItems[$req->id] ?? collect())->count() }} vật tư
                                    </span>
                                </td>
                                <td class="align-middle">
                                    @include('pages.export.MaterialExport.transferSignChips', ['mtSigns' => $mtSignsOf($req), 'mtReq' => $req])
                                </td>
                                <td class="text-center align-middle">{{ $expDate($req->created_at) }}</td>
                                <td class="text-center align-middle" style="white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-info px-2 py-1 shadow-sm"
                                        data-toggle="modal" data-target="#matTransferDetailModal_{{ $req->id }}" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm btn-mt-sign"
                                        data-mode="sign" data-id="{{ $req->id }}" data-code="{{ $req->code }}" data-step="{{ $req->sign_step_no }}">
                                        <i class="fas fa-signature mr-1"></i> Ký
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 btn-mt-sign"
                                        data-mode="reject" data-id="{{ $req->id }}" data-code="{{ $req->code }}" data-step="{{ $req->sign_step_no }}">
                                        <i class="fas fa-times mr-1"></i> Từ chối
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="exp-req-card is-sent">
        <div class="exp-req-card-header">
            <i class="fas fa-paper-plane"></i>
            <span class="exp-req-card-title">Đề nghị phòng mình đã gửi đi</span>
            <span class="exp-req-card-count">{{ $mtSent->total() }}</span>
            <i class="fas fa-chevron-down exp-req-card-toggle"></i>
        </div>
        <div class="exp-req-card-body">

    <div class="mt-filterbar">
        @include('pages.shared.rangeFilter', [
            'rfRoute' => $expRoute . 'list',
            'rfTab' => $mtTab,
            'rfPrefix' => $mtPrefix . 'sent_',
            'rfRange' => $mtSentRange,
            'rfPerPage' => $mtSentPerPage,
            'rfSearch' => false,
            'rfDateLabel' => 'Ngày lập',
            'rfInBar' => true,
        ])

        @if (! empty($mtCreate))
            @perm('export_material_request')
                <button type="button" class="btn btn-primary btn-sm mt-create-btn" data-toggle="modal" data-target="{{ $mtCreate['target'] }}">
                    <i class="fas fa-plus mr-1"></i> {{ $mtCreate['label'] }}
                </button>
            @endperm
        @endif
    </div>

    <div class="table-responsive">
        <table id="{{ $mtTableId }}SentTable" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="width: 130px">Mã Đề Nghị</th>
                    <th>Tiêu Đề</th>
                    <th style="width: 170px">Gửi Đến Phòng</th>
                    <th style="width: 120px">Số Lượng Mục</th>
                    <th style="width: 140px">Trạng Thái</th>
                    <th style="width: 140px">Người Lập</th>
                    <th style="width: 120px">Ngày Lập</th>
                    <th style="width: 120px">Ngày Mong Muốn</th>
                    <th style="width: 130px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mtSent as $req)
                    @php
                        $items = $transferItems[$req->id] ?? collect();
                        $awaitingReceiptCount = $items->where('status', 'issued')->count();
                    @endphp
                    <tr>
                        <td class="text-center align-middle">{{ $mtSent->firstItem() + $loop->index }}</td>
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
                                <i class="fas fa-boxes mr-1"></i> {{ $items->count() }} vật tư
                            </span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="exp-req-badge {{ $mtBadge($req->status)['class'] }}">{{ $mtBadge($req->status)['label'] }}</span>
                            @include('pages.export.MaterialExport.transferSignChips', ['mtSigns' => $mtSignsOf($req), 'mtReq' => $req])
                        </td>
                        <td class="align-middle">{{ $req->updated_by ?: $req->created_by ?: '—' }}</td>
                        <td class="text-center align-middle">{{ $expDate($req->created_at) }}</td>
                        <td class="text-center align-middle">{{ $req->needed_date ? $expDate($req->needed_date) : '—' }}</td>
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
                        <td colspan="10" class="text-center text-muted py-4">{{ $mtEmptySent }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('pages.shared.paginator', [
        'pgItems' => $mtSent,
        'pgTab' => $mtTab,
        'pgUnit' => 'đề nghị',
    ])

        </div>
    </div>

    @if ($mtShowReceived ?? true)
    <div class="exp-req-card is-received">
        <div class="exp-req-card-header">
            <i class="fas fa-inbox"></i>
            <span class="exp-req-card-title">{{ $mtReceivedTitle }}</span>
            <span class="exp-req-card-count">{{ $mtReceived->total() }}</span>
            <i class="fas fa-chevron-down exp-req-card-toggle"></i>
        </div>
        <div class="exp-req-card-body">

    @include('pages.shared.rangeFilter', [
        'rfRoute' => $expRoute . 'list',
        'rfTab' => $mtTab,
        'rfPrefix' => $mtPrefix . 'recv_',
        'rfRange' => $mtReceivedRange,
        'rfPerPage' => $mtReceivedPerPage,
        'rfSearch' => false,
        'rfDateLabel' => 'Ngày lập',
        'rfInBar' => true,
    ])

    <div class="table-responsive">
        <table id="{{ $mtTableId }}ReceivedTable" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="width: 130px">Mã Đề Nghị</th>
                    <th>Tiêu Đề</th>
                    <th style="width: 170px">Phòng Đề Nghị</th>
                    <th style="width: 120px">Số Lượng Mục</th>
                    <th style="width: 140px">Trạng Thái</th>
                    <th style="width: 140px">Người Lập</th>
                    <th style="width: 120px">Ngày Lập</th>
                    <th style="width: 120px">Ngày Mong Muốn</th>
                    <th style="width: 130px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mtReceived as $req)
                    @php $items = $transferItems[$req->id] ?? collect(); @endphp
                    <tr>
                        <td class="text-center align-middle">{{ $mtReceived->firstItem() + $loop->index }}</td>
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
                                <i class="fas fa-boxes mr-1"></i> {{ $items->count() }} vật tư
                            </span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="exp-req-badge {{ $mtBadge($req->status)['class'] }}">{{ $mtBadge($req->status)['label'] }}</span>
                            @include('pages.export.MaterialExport.transferSignChips', ['mtSigns' => $mtSignsOf($req), 'mtReq' => $req])
                        </td>
                        <td class="align-middle">{{ $req->updated_by ?: $req->created_by ?: '—' }}</td>
                        <td class="text-center align-middle">{{ $expDate($req->created_at) }}</td>
                        <td class="text-center align-middle">{{ $req->needed_date ? $expDate($req->needed_date) : '—' }}</td>
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
                        <td colspan="10" class="text-center text-muted py-4">{{ $mtEmptyReceived }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('pages.shared.paginator', [
        'pgItems' => $mtReceived,
        'pgTab' => $mtTab,
        'pgUnit' => 'đề nghị',
    ])

        </div>
    </div>
    @endif


@once
    <div class="modal fade md-modal" id="mtSignModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-signature"></i> <span class="mt-sign-title"></span></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" data-sign-url="{{ route('pages.export.materialExport.transferSign') }}"
                    data-reject-url="{{ route('pages.export.materialExport.transferSignReject') }}">
                    @csrf
                    <input type="hidden" name="transfer_request_id">
                    <div class="modal-body">
                        <div class="form-group mt-sign-note">
                            <label>Ghi Chú</label>
                            <textarea name="note" rows="2" maxlength="500" class="form-control" placeholder="Ghi chú khi ký (không bắt buộc)"></textarea>
                        </div>
                        <div class="form-group mt-sign-reason">
                            <label>Lý Do Từ Chối <span class="text-danger">*</span></label>
                            <textarea name="reason" rows="2" maxlength="500" class="form-control" placeholder="Nêu rõ lý do để phòng đề nghị sửa lại"></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label>Mật Khẩu Xác Nhận <span class="text-danger">*</span></label>
                            <input type="password" name="sign_password" autocomplete="current-password" class="form-control"
                                placeholder="Nhập lại mật khẩu đăng nhập của bạn" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn mt-sign-submit"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Một modal cho cả Ký và Từ chối: đổi action, tiêu đề, ô bắt buộc theo nút bấm
            $(document).on('click', '.btn-mt-sign', function() {
                var reject = $(this).data('mode') === 'reject';
                var $modal = $('#mtSignModal');
                var $form = $modal.find('form');

                $form.attr('action', $form.data(reject ? 'reject-url' : 'sign-url'));
                $form.find('[name="transfer_request_id"]').val($(this).data('id'));
                $form.find('textarea, [name="sign_password"]').val('');
                $form.find('.mt-sign-note').toggle(!reject);
                $form.find('.mt-sign-reason').toggle(reject).find('textarea').prop('required', reject);
                $modal.find('.mt-sign-title').text((reject ? 'Từ chối ' : 'Ký duyệt bước ' + $(this).data('step') + ' - ') + $(this).data('code'));
                $modal.find('.mt-sign-submit')
                    .toggleClass('btn-success', !reject).toggleClass('btn-danger', reject)
                    .html(reject ? '<i class="fas fa-times mr-1"></i> Từ chối' : '<i class="fas fa-signature mr-1"></i> Ký duyệt');
                $modal.modal('show');
            });
        });
    </script>
@endonce
