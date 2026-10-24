{{--
| HAI CARD ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN - "phòng mình gửi đi" và "phòng khác gửi
| đến", mỗi card 2 tab con Chờ xử lý / Đã hoàn thành. Dùng chung cho tab "Chuyển liên phòng ban" (transferPane) và tab "Đề nghị VPP"
| (vppPane) - cùng bộ bảng material_transfer_requests, chỉ khác phòng nhận đề nghị.
|
| Biến vào: $mtTab (tên tab cho bộ lọc / phân trang), $mtPrefix (tiền tố bộ lọc: t | v),
| $mtTableId, $mtToSign,
| $mtSent / $mtSentDone (paginator gửi đi: chờ xử lý / đã hoàn thành), $mtSentDoneRange / $mtSentDonePerPage,
| $mtReceived / $mtDone (paginator gửi đến: cần cấp phát / đã xử lý xong), $mtDoneRange / $mtDonePerPage,
| $mtReceivedTitle, $mtEmptySent, $mtEmptySentDone, $mtEmptyReceived, $mtEmptyDone,
| $mtShowReceived (false thì bỏ hẳn card gửi đến - phòng không bao giờ nhận loại đề nghị này),
| $mtCreate (tuỳ chọn ['target' => '#modal', 'label' => ...]: nút tạo đề nghị đặt cùng hàng tab con
| của card "gửi đi").
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

{{-- CSS + JS tab con Chờ xử lý / Đã hoàn thành (.mt-subhead, .mt-subtabs) ở pages/export/shared/assets --}}
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
                                    {{-- Ký / Từ chối nằm trong modal chi tiết (transferDetailModal) để xem đủ tồn trước khi quyết --}}
                                    <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm"
                                        data-toggle="modal" data-target="#matTransferDetailModal_{{ $req->id }}" title="Xem chi tiết và ký duyệt">
                                        <i class="fas fa-file-signature mr-1"></i> Xem &amp; ký
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

    @php
        /*
        | Hai card: "phòng mình gửi đi" và "phòng khác gửi đến". Mỗi card 2 tab con:
        | - Chờ xử lý   : luôn hiện đủ, không lọc ngày (gửi đi: chưa cấp xong / còn mục chờ nhận;
        |                 gửi đến: Chờ cấp phát / Cấp một phần).
        | - Đã hoàn thành: lọc theo ngày lập.
        | Tab con đang mở giữ trên URL bằng tham số <prefix>sub_sent / <prefix>sub_recv = done
        | để bấm Lọc / sang trang xong vẫn quay về đúng tab.
        */
        $mtCards = [
            [
                'side' => 'sent', 'class' => 'is-sent', 'icon' => 'fa-paper-plane',
                'title' => 'Đề nghị phòng mình gửi đi', 'key' => 'Sent',
                'subKey' => $mtPrefix . 'sub_sent', 'donePrefix' => $mtPrefix . 'sdone_',
                'pending' => $mtSent, 'done' => $mtSentDone,
                'doneRange' => $mtSentDoneRange, 'donePerPage' => $mtSentDonePerPage,
                'emptyPending' => $mtEmptySent, 'emptyDone' => $mtEmptySentDone,
                'partnerLabel' => 'Gửi Đến Phòng', 'show' => true,
            ],
            [
                'side' => 'received', 'class' => 'is-received', 'icon' => 'fa-inbox',
                'title' => $mtReceivedTitle, 'key' => 'Received',
                'subKey' => $mtPrefix . 'sub_recv', 'donePrefix' => $mtPrefix . 'done_',
                'pending' => $mtReceived, 'done' => $mtDone,
                'doneRange' => $mtDoneRange, 'donePerPage' => $mtDonePerPage,
                'emptyPending' => $mtEmptyReceived, 'emptyDone' => $mtEmptyDone,
                'partnerLabel' => 'Phòng Đề Nghị',
                // false thì bỏ hẳn card gửi đến - phòng không bao giờ nhận loại đề nghị này
                'show' => $mtShowReceived ?? true,
            ],
        ];
    @endphp

    @foreach ($mtCards as $mtCard)
    @continue(! $mtCard['show'])
    @php
        $mtSide = $mtCard['side'];
        $mtSubDone = request($mtCard['subKey']) === 'done';
        $mtPaneId = $mtTableId . $mtCard['key'];
    @endphp
    <div class="exp-req-card {{ $mtCard['class'] }}">
        <div class="exp-req-card-header">
            <i class="fas {{ $mtCard['icon'] }}"></i>
            <span class="exp-req-card-title">{{ $mtCard['title'] }}</span>
            <span class="exp-req-card-count" title="Chờ xử lý">{{ $mtCard['pending']->total() }}</span>
            <i class="fas fa-chevron-down exp-req-card-toggle"></i>
        </div>
        <div class="exp-req-card-body">

    <div class="mt-subhead">
        <ul class="nav nav-tabs mt-subtabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $mtSubDone ? '' : 'active' }}" data-toggle="tab" role="tab"
                    href="#{{ $mtPaneId }}Pending" data-sub-key="{{ $mtCard['subKey'] }}" data-sub-value="">
                    <i class="fas fa-hourglass-half mr-1"></i> Chờ xử lý
                    <span class="badge badge-warning">{{ $mtCard['pending']->total() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $mtSubDone ? 'active' : '' }}" data-toggle="tab" role="tab"
                    href="#{{ $mtPaneId }}Done" data-sub-key="{{ $mtCard['subKey'] }}" data-sub-value="done">
                    <i class="fas fa-check-double mr-1"></i> Đã hoàn thành
                    <span class="badge badge-success">{{ $mtCard['done']->total() }}</span>
                </a>
            </li>
        </ul>

        @if ($mtSide === 'sent' && ! empty($mtCreate))
            @perm('export_material_request')
                <button type="button" class="btn btn-primary btn-sm mt-create-btn" data-toggle="modal" data-target="{{ $mtCreate['target'] }}">
                    <i class="fas fa-plus mr-1"></i> {{ $mtCreate['label'] }}
                </button>
            @endperm
        @endif
    </div>

    <div class="tab-content">
    @foreach (['pending' => 'Pending', 'done' => 'Done'] as $mtSub => $mtSubId)
        @php
            $mtList = $mtCard[$mtSub];
            $mtIsDone = $mtSub === 'done';
        @endphp
        <div class="tab-pane fade {{ $mtIsDone === $mtSubDone ? 'show active' : '' }}" id="{{ $mtPaneId }}{{ $mtSubId }}" role="tabpanel">

    @if ($mtIsDone)
        @include('pages.shared.rangeFilter', [
            'rfRoute' => $expRoute . 'list',
            'rfTab' => $mtTab,
            'rfPrefix' => $mtCard['donePrefix'],
            'rfRange' => $mtCard['doneRange'],
            'rfPerPage' => $mtCard['donePerPage'],
            'rfSearch' => false,
            'rfDateLabel' => 'Ngày lập',
            'rfInBar' => true,
            'rfHidden' => [$mtCard['subKey'] => 'done'],
        ])
    @endif

    <div class="table-responsive">
        <table id="{{ $mtPaneId }}{{ $mtIsDone ? 'Done' : '' }}Table" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="width: 130px">Mã Đề Nghị</th>
                    <th>Tiêu Đề</th>
                    <th style="width: 170px">{{ $mtCard['partnerLabel'] }}</th>
                    <th style="width: 120px">Số Lượng Mục</th>
                    <th style="width: 140px">Trạng Thái</th>
                    <th style="width: 140px">Người Lập</th>
                    <th style="width: 120px">Ngày Lập</th>
                    <th style="width: 120px">Ngày Mong Muốn</th>
                    <th style="width: 130px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mtList as $req)
                    @php
                        $items = $transferItems[$req->id] ?? collect();
                        $awaitingReceiptCount = $items->where('status', 'issued')->count();
                    @endphp
                    <tr>
                        <td class="text-center align-middle">{{ $mtList->firstItem() + $loop->index }}</td>
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
                            @if ($mtSide === 'sent' && $req->status === 'draft' && user_can('export_material_request'))
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
                            @elseif ($mtSide === 'sent' && $awaitingReceiptCount > 0 && (user_can('export_material_transfer_receive') || user_can('export_material_transfer_return')))
                                <button type="button" class="btn btn-sm btn-success px-2 py-1 shadow-sm"
                                    data-toggle="modal" data-target="#matTransferDetailModal_{{ $req->id }}"
                                    title="Có mục đã được cấp phát, chờ nhận">
                                    <i class="fas fa-inbox mr-1"></i> Nhận ({{ $awaitingReceiptCount }})
                                </button>
                            @elseif ($mtSide === 'received' && in_array($req->status, ['pending', 'partial']) && user_can('export_material_transfer'))
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
                        <td colspan="10" class="text-center text-muted py-4">{{ $mtIsDone ? $mtCard['emptyDone'] : $mtCard['emptyPending'] }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @php
        // Sang trang ở tab nào thì mở lại đúng tab đó
        $mtList->appends([$mtCard['subKey'] => $mtIsDone ? 'done' : null]);
    @endphp
    @include('pages.shared.paginator', [
        'pgItems' => $mtList,
        'pgTab' => $mtTab,
        'pgUnit' => 'đề nghị',
    ])

        </div>
    @endforeach
    </div>

        </div>
    </div>
    @endforeach



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
                        {{-- Duyệt một phần: các mục bỏ tick "Duyệt" ở modal chi tiết phiếu, gửi kèm reject_items[] + reject_reasons[id] --}}
                        <div class="alert alert-warning py-2 mt-sign-partial" style="display: none; font-size: 0.88rem;"></div>
                        <div class="mt-sign-hidden"></div>
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
                var btn = this;
                var $parent = $(this).closest('.modal');
                var rejects = [];

                // Đồng ý: gom các mục bỏ tick "Duyệt" ở modal chi tiết phiếu
                if ($(btn).data('mode') !== 'reject') {
                    var $checks = $parent.find('.mt-approve-item');
                    var missing = false;

                    $checks.not(':checked').each(function () {
                        var $tr = $(this).closest('tr');
                        var reason = $.trim($tr.find('.mt-approve-reason').val());

                        if (!reason) {
                            missing = true;
                            $tr.find('.mt-approve-reason').addClass('is-invalid');
                        }

                        rejects.push({ id: this.value, reason: reason, name: $.trim($tr.find('td').eq(1).find('.font-weight-bold').first().text()) });
                    });

                    if (missing) {
                        Swal.fire({ icon: 'warning', title: 'Thiếu lý do từ chối', text: 'Vui lòng nhập lý do cho từng vật tư bỏ tick "Duyệt".' });
                        return;
                    }

                    if ($checks.length && rejects.length === $checks.length) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Chưa duyệt vật tư nào',
                            text: 'Muốn từ chối toàn bộ phiếu, hãy dùng nút "Từ chối" để trả phiếu về cho phòng đề nghị.'
                        });
                        return;
                    }
                }

                $(btn).data('rejects', rejects);

                // Nút nằm trong modal chi tiết phiếu: đóng modal đó rồi mới mở modal ký (không chồng 2 modal)
                if ($parent.length && $parent.attr('id') !== 'mtSignModal') {
                    $parent.one('hidden.bs.modal', function () { mtOpenSign(btn); }).modal('hide');
                } else {
                    mtOpenSign(btn);
                }
            });

            $(document).on('input', '.mt-approve-reason', function () {
                $(this).removeClass('is-invalid');
            });

            function mtOpenSign(btn) {
                var reject = $(btn).data('mode') === 'reject';
                // Modal chỉ render một lần, nằm trong tab Liên phòng ban: đang ở tab VPP thì tab đó ẩn,
                // modal nằm trong khối display:none sẽ không hiện -> đưa ra <body> trước khi mở
                var $modal = $('#mtSignModal').appendTo('body');
                var $form = $modal.find('form');

                $form.attr('action', $form.data(reject ? 'reject-url' : 'sign-url'));
                $form.find('[name="transfer_request_id"]').val($(btn).data('id'));
                $form.find('textarea, [name="sign_password"]').val('');
                $form.find('.mt-sign-note').toggle(!reject);
                $form.find('.mt-sign-reason').toggle(reject).find('textarea').prop('required', reject);
                // Mục bị từ chối (duyệt một phần) gom từ modal chi tiết -> input ẩn + dòng tóm tắt
                var rejects = reject ? [] : ($(btn).data('rejects') || []);
                var $hidden = $form.find('.mt-sign-hidden').empty();

                rejects.forEach(function (r) {
                    $hidden.append($('<input type="hidden" name="reject_items[]">').val(r.id));
                    $hidden.append($('<input type="hidden">').attr('name', 'reject_reasons[' + r.id + ']').val(r.reason));
                });

                $form.find('.mt-sign-partial').toggle(rejects.length > 0).html(rejects.length
                    ? '<i class="fas fa-exclamation-triangle mr-1"></i> Duyệt một phần - từ chối ' + rejects.length + ' vật tư: '
                        + rejects.map(function (r) { return $('<span>').text(r.name + ' (' + r.reason + ')').html(); }).join('; ')
                    : '');
                $modal.find('.mt-sign-title').text((reject ? 'Từ chối ' : 'Ký duyệt bước ' + $(btn).data('step') + ' - ') + $(btn).data('code'));
                $modal.find('.mt-sign-submit')
                    .toggleClass('btn-success', !reject).toggleClass('btn-danger', reject)
                    .html(reject ? '<i class="fas fa-times mr-1"></i> Từ chối' : '<i class="fas fa-signature mr-1"></i> Ký duyệt');
                $modal.modal('show');
            }

        });
    </script>
@endonce
