@php
    $expReqStatus = $expReqStatus ?? [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'canceled' => ['label' => 'Đã huỷ', 'class' => 'rejected'],
        // Trạng thái riêng của TỪNG ITEM (khác trạng thái tổng của phiếu ở trên)
        'issued' => ['label' => 'Chờ nhận', 'class' => 'issued'],
        'received' => ['label' => 'Đã nhận', 'class' => 'accepted'],
        'returned' => ['label' => 'Đã từ chối nhận', 'class' => 'rejected'],
    ];
    $expReqBadge = $expReqBadge ?? fn($status) => $expReqStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
    $expNum = $expNum ?? fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $expDate = $expDate ?? fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    // 4 danh sách (gửi đi / gửi đến x Chờ xử lý / Đã hoàn thành) là LengthAwarePaginator chứ
    // không còn là Collection thường - phải lấy đúng danh sách bản ghi qua getCollection()
    // trước khi gộp, nếu không merge() sẽ đọc theo toArray() của paginator (mảng
    // 'current_page', 'data', 'total'...) chứ không phải danh sách phiếu.
    $transferAll = collect([$transferSent, $transferSentDone, $transferReceived, $transferDone])
        ->flatMap(fn ($list) => $list->getCollection())
        ->unique('id');
@endphp

@foreach ($transferAll as $req)
    @php
        $items = $transferItems[$req->id] ?? collect();
        $iAmSource = (int) $req->to_department_id === (int) $currentDepartmentId;
    @endphp
    <div class="modal fade md-modal" id="transferDetailModal_{{ $req->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 96vw;" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                        <i class="fas fa-people-arrows mr-2"></i> {{ $req->title ?: 'Đề Nghị Liên Phòng Ban' }}
                        <span class="text-muted font-weight-normal" style="font-size: 0.85rem;">({{ $req->code }})</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-3">
                    <div class="card mb-3 border-0" style="background-color: #f1f5f9 !important;">
                        <div class="card-body py-2 px-3">
                            <div class="row align-items-center" style="font-size: 0.9rem;">
                                <div class="col-md-3">
                                    <span class="text-muted">{{ $iAmSource ? 'Phòng đề nghị:' : 'Gửi đến phòng:' }}</span>
                                    <b class="text-primary ml-1 font-weight-bold">{{ $req->partner_name ?: '—' }}</b>
                                </div>
                                <div class="col-md-2">
                                    <span class="text-muted">Trạng thái:</span>
                                    <span class="exp-req-badge {{ $expReqBadge($req->status)['class'] }} ml-1">
                                        {{ $expReqBadge($req->status)['label'] }}
                                    </span>
                                </div>
                                <div class="col-md-2">
                                    <span class="text-muted">Người lập:</span>
                                    <b class="ml-1">{{ $req->updated_by ?: $req->created_by ?: '—' }}</b>
                                </div>
                                <div class="col-md-2">
                                    <span class="text-muted">Ngày lập:</span>
                                    <b class="ml-1">{{ $expDate($req->created_at) }}</b>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Ngày mong muốn:</span>
                                    <b class="ml-1">{{ $req->needed_date ? $expDate($req->needed_date) : '—' }}</b>
                                </div>
                            </div>
                            @if ($req->note)
                                <div class="mt-2 text-muted" style="font-size: 0.85rem;">
                                    <i class="fas fa-comment-dots mr-1 text-secondary"></i><b>Ghi chú:</b> {{ $req->note }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 w-100" style="font-size: 0.86rem;">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th style="width: 40px">STT</th>
                                    <th style="min-width: 200px">Hoá Chất</th>
                                    <th style="min-width: 90px" class="text-right">SL Đề Nghị</th>
                                    @if ($iAmSource)
                                        <th style="min-width: 220px">Phiếu Nhập (Kho Mình) <span class="text-danger">*</span></th>
                                        <th style="min-width: 100px" class="text-right">SL Cấp Phát <span class="text-danger">*</span></th>
                                        <th style="min-width: 90px">ĐVT</th>
                                    @else
                                        <th style="min-width: 200px">Mã Cấp / Định Khu Nhận</th>
                                        <th style="min-width: 100px" class="text-right">SL Cấp Phát</th>
                                        <th style="min-width: 90px">ĐVT</th>
                                    @endif
                                    <th style="min-width: 130px">Ghi Chú</th>
                                    <th style="min-width: 160px" class="text-center">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr class="issue-row">
                                        <td class="text-center align-middle text-muted">{{ $loop->iteration }}</td>
                                        <td class="align-middle">
                                            <span class="font-weight-bold text-dark">{{ $item->chem_name }}</span>
                                            <span class="badge badge-secondary ml-1">{{ $item->category_code }}</span>
                                            @if ($expIsSpecial($item->category_id))
                                                <span class="badge-special-control ml-1" title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">
                                                    <i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt
                                                </span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-right font-weight-bold text-primary">
                                            {{ $expNum($item->requested_amount) }} {{ $item->requested_unit }}
                                        </td>

                                        @if ($iAmSource && $item->status === 'pending')
                                            {{-- B đang cấp phát: chỉ chọn phiếu nhập của mình + số lượng. Vị trí lưu
                                                 là việc của phòng A, để A tự chọn ở bước Nhận, không chọn hộ ở đây. --}}
                                            <td class="align-middle">
                                                <div class="chem-pick-box d-flex align-items-center" style="gap: 5px;">
                                                    <div class="chem-pick-display flex-grow-1 is-empty">
                                                        <span class="text-muted"><i class="fas fa-flask mr-1"></i> Chưa chọn phiếu nhập</span>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-open-chem-picker"
                                                        data-category-id="{{ $item->category_id }}"
                                                        title="Chọn từ tồn kho của phòng">
                                                        <i class="fas fa-list-ul"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="align-middle text-right">
                                                <input type="text" inputmode="decimal" min="0.0001" name="issued_amount"
                                                    class="form-control text-right font-weight-bold input-issue-amount js-decimal"
                                                    value="{{ (float) $item->requested_amount }}"
                                                    style="height: 38px !important; min-height: 38px !important; font-size: 0.88rem;" required>
                                            </td>
                                            <td class="align-middle text-center">
                                                <select name="issued_unit" class="form-control select-issue-unit text-center" style="height: 38px !important; min-height: 38px !important; font-size: 0.88rem;">
                                                    <option value="">-- ĐVT --</option>
                                                    @foreach ($units as $u)
                                                        @php $uVal = $u->short_name ?: $u->name; @endphp
                                                        <option value="{{ $uVal }}" {{ $item->requested_unit == $uVal ? 'selected' : '' }}>{{ $uVal }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @else
                                            <td class="align-middle">
                                                @if ($item->import_code)
                                                    <span class="exp-code font-weight-bold">{{ $item->import_code }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif

                                                @if (! $iAmSource && $item->status === 'issued')
                                                    {{-- Phòng mình (A) đang nhận: định khu chọn ở modal Xác nhận kiểm tra --}}
                                                    @if (! in_array((int) $item->category_id, $declaredCategoryIds, true))
                                                        <small class="text-danger d-block mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Chưa khai danh mục này ở tab "Hoá Chất Của Phòng"</small>
                                                    @endif
                                                @elseif ($item->dest_location_code)
                                                    <div class="mt-1">{{ \App\Support\ZoneType::tag($item->dest_location_code, $item->dest_location_zone_type ?? null) }}</div>
                                                @endif
                                            </td>
                                            <td class="align-middle text-right">
                                                @if ($item->issued_amount !== null)
                                                    <span class="font-weight-bold text-success">{{ $expNum($item->issued_amount) }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-center">{{ $item->issued_unit ?: '—' }}</td>
                                        @endif

                                        <td class="align-middle text-muted" title="{{ $item->note }}">
                                            {{ $item->note ?: '—' }}
                                            @if ($item->status === 'rejected' && $item->reject_note)
                                                <div class="text-danger mt-1"><i class="fas fa-ban mr-1"></i>{{ $item->reject_note }}</div>
                                            @elseif ($item->status === 'returned' && $item->return_note)
                                                <div class="text-danger mt-1"><i class="fas fa-undo mr-1"></i>{{ $item->return_note }}</div>
                                            @endif
                                        </td>

                                        <td class="align-middle text-center" style="white-space: nowrap;">
                                            @if ($iAmSource && $item->status === 'pending' && user_can('export_chemical_transfer'))
                                                <form action="{{ route('pages.export.chemicalExport.transferIssueStore') }}" method="POST" class="d-inline form-chem-transfer-issue">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <input type="hidden" name="import_id" class="hidden-import-id" value="">
                                                    <input type="hidden" name="issued_amount" class="hidden-issued-amount" value="">
                                                    <input type="hidden" name="issued_unit" class="hidden-issued-unit" value="">
                                                    <button type="button" class="btn btn-xs btn-success btn-trigger-chem-transfer-issue px-2 py-1 shadow-sm" title="Xác nhận cấp phát ngay">
                                                        <i class="fas fa-check-circle mr-1"></i> Cấp phát
                                                    </button>
                                                </form>

                                                <form class="form-md-confirm d-inline"
                                                    action="{{ route('pages.export.chemicalExport.transferRequestReject') }}"
                                                    method="POST"
                                                    data-title="Từ chối cấp mục này?"
                                                    data-danger="1">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <button type="button" class="btn btn-xs btn-danger ml-1 px-2 py-1 shadow-sm btn-chem-transfer-reject" title="Từ chối cấp phát">
                                                        <i class="fas fa-ban mr-1"></i> Từ chối
                                                    </button>
                                                </form>
                                            @elseif (! $iAmSource && $item->status === 'issued' && (user_can('export_chemical_transfer_receive') || user_can('export_chemical_transfer_return')))
                                                @if (user_can('export_chemical_transfer_receive'))
                                                    <form action="{{ route('pages.export.chemicalExport.transferReceiveStore') }}" method="POST" class="d-inline form-chem-transfer-receive"
                                                        data-reject-url="{{ route('pages.export.chemicalExport.transferReceiveReject') }}">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <button type="button" class="btn btn-xs btn-success btn-trigger-chem-transfer-receive px-2 py-1 shadow-sm"
                                                            title="Xác nhận kiểm tra lô và nhận hàng"
                                                            data-row="{{ json_encode([
                                                                'code' => $item->import_code,
                                                                'chemical' => trim(($item->chem_name ?: '—') . ' (' . $item->category_code . ')'),
                                                                'amount' => $item->issued_amount !== null ? $expNum($item->issued_amount) . ' ' . $item->issued_unit : '—',
                                                                'from' => $req->partner_name,
                                                                'batch_no' => $item->source_batch_no ?: '—',
                                                                'invoice_number' => $item->source_invoice_number ?: '—',
                                                                'invoice_date' => $expDate($item->source_invoice_date),
                                                                'expired_date' => $expDate($item->source_expired_date),
                                                                // Chuyển nguyên lô thì phòng nhận tự xác định lại hạn dùng nội bộ
                                                                'internal_expired_date' => $item->is_partial_lot ? $expDate($item->source_internal_expired_date) : 'Phòng nhận tự xác định',
                                                            ]) }}">
                                                            <i class="fas fa-inbox mr-1"></i> Nhận
                                                        </button>
                                                    </form>
                                                @endif
                                                @if (user_can('export_chemical_transfer_return'))
                                                    <form class="form-md-confirm d-inline"
                                                        action="{{ route('pages.export.chemicalExport.transferReceiveReject') }}"
                                                        method="POST"
                                                        data-title="Từ chối nhận mục này?"
                                                        data-danger="1">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <button type="button" class="btn btn-xs btn-danger ml-1 px-2 py-1 shadow-sm btn-chem-transfer-return" title="Từ chối nhận, hoàn tồn cho phòng gửi">
                                                            <i class="fas fa-undo mr-1"></i> Từ chối nhận
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="exp-req-badge {{ $expReqBadge($item->status)['class'] }}">
                                                    {{ $expReqBadge($item->status)['label'] }}
                                                </span>
                                                @if ($item->issued_by)
                                                    <small class="d-block text-muted">Cấp: {{ $item->issued_by }}</small>
                                                @endif
                                                @if ($item->issued_at)
                                                    <small class="d-block text-muted" style="font-size: 0.78rem;">{{ \Carbon\Carbon::parse($item->issued_at)->format('d/m/Y H:i') }}</small>
                                                @endif
                                                @if ($item->received_by)
                                                    <small class="d-block text-muted">Nhận: {{ $item->received_by }}</small>
                                                @endif
                                                @if ($item->received_at)
                                                    <small class="d-block text-muted" style="font-size: 0.78rem;">{{ \Carbon\Carbon::parse($item->received_at)->format('d/m/Y H:i') }}</small>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">Phiếu này không có hoá chất nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ChemTransferToast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });

        /*
        | Cấp phát / Nhận / Từ chối xong trang phải tải lại (tồn kho + trạng thái dòng đổi), nhưng
        | MỞ LẠI đúng phiếu chi tiết vừa thao tác để người dùng xử lý tiếp các mục còn lại.
        | Nhớ id modal ở sessionStorage (chỉ tab trình duyệt này), đọc xong xoá ngay.
        */
        var CHEM_TRANSFER_REOPEN = 'chemTransferReopenModal';

        function chemTransferRemember($el) {
            var id = $el.closest('.modal[id^="transferDetailModal_"]').attr('id');
            try {
                if (id) sessionStorage.setItem(CHEM_TRANSFER_REOPEN, id);
            } catch (e) {}
        }

        function chemTransferReload($el) {
            chemTransferRemember($el);
            setTimeout(function() { location.reload(); }, 600);
        }

        (function() {
            var id = null;
            try {
                id = sessionStorage.getItem(CHEM_TRANSFER_REOPEN);
                sessionStorage.removeItem(CHEM_TRANSFER_REOPEN);
            } catch (e) {}

            if (id && document.getElementById(id)) {
                $('#' + id).modal('show');
            }
        })();

        // Yêu cầu lý do trước khi gửi form từ chối cấp phát
        $(document).on('click', '.btn-chem-transfer-reject', function(e) {
            e.preventDefault();
            var $form = $(this).closest('form');

            Swal.fire({
                title: 'Từ chối cấp phát mục này?',
                input: 'text',
                inputPlaceholder: 'Nhập lý do từ chối...',
                showCancelButton: true,
                confirmButtonText: 'Từ chối',
                cancelButtonText: 'Huỷ',
                confirmButtonColor: '#dc3545',
                inputValidator: function(value) {
                    if (!value) {
                        return 'Vui lòng nhập lý do từ chối!';
                    }
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    if ($form.find('input[name="reject_note"]').length === 0) {
                        $form.append('<input type="hidden" name="reject_note">');
                    }
                    $form.find('input[name="reject_note"]').val(result.value);
                    chemTransferRemember($form);
                    $form.off('submit').submit();
                }
            });
        });

        // Yêu cầu lý do trước khi gửi form từ chối nhận
        $(document).on('click', '.btn-chem-transfer-return', function(e) {
            e.preventDefault();
            var $form = $(this).closest('form');

            Swal.fire({
                title: 'Từ chối nhận mục này?',
                text: 'Tồn kho sẽ được hoàn lại cho phòng gửi.',
                input: 'text',
                inputPlaceholder: 'Nhập lý do từ chối nhận...',
                showCancelButton: true,
                confirmButtonText: 'Từ chối nhận',
                cancelButtonText: 'Huỷ',
                confirmButtonColor: '#dc3545',
                inputValidator: function(value) {
                    if (!value) {
                        return 'Vui lòng nhập lý do từ chối nhận!';
                    }
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    if ($form.find('input[name="return_note"]').length === 0) {
                        $form.append('<input type="hidden" name="return_note">');
                    }
                    $form.find('input[name="return_note"]').val(result.value);
                    chemTransferRemember($form);
                    $form.off('submit').submit();
                }
            });
        });

        $(document).on('click', '.btn-trigger-chem-transfer-issue', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('.issue-row');
            var $form = $btn.closest('.form-chem-transfer-issue');

            // Đọc thẳng thuộc tính, KHÔNG dùng .data(): .data() cache giá trị lần đọc đầu nên chọn
            // lại mã khác trong picker (chỉ đổi attr) vẫn gửi mã cũ lên server
            var importId = $row.find('.chem-pick-display').attr('data-import-id');
            var issuedAmount = $row.find('.input-issue-amount').val();
            var issuedUnit = $row.find('.select-issue-unit').val();

            if (!importId) {
                ChemTransferToast.fire({ icon: 'error', title: 'Vui lòng chọn Phiếu Nhập!' });
                return;
            }
            if (!issuedAmount || issuedAmount <= 0) {
                ChemTransferToast.fire({ icon: 'error', title: 'Vui lòng nhập SL Cấp Phát hợp lệ!' });
                return;
            }

            $form.find('.hidden-import-id').val(importId);
            $form.find('.hidden-issued-amount').val(issuedAmount);
            $form.find('.hidden-issued-unit').val(issuedUnit);

            Swal.fire({
                title: 'Xác nhận cấp phát liên phòng ban?',
                text: 'Tồn kho phòng mình sẽ bị trừ ngay, chờ phòng nhận xác nhận Nhận hàng.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Huỷ'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: $form.attr('action'),
                        type: 'POST',
                        data: $form.serialize(),
                        beforeSend: function() {
                            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
                        },
                        success: function(res) {
                            if (res.success) {
                                ChemTransferToast.fire({ icon: 'success', title: res.message });
                                chemTransferReload($btn);
                            } else {
                                $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Cấp phát');
                                ChemTransferToast.fire({ icon: 'error', title: res.message });
                            }
                        },
                        error: function() {
                            $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Cấp phát');
                            ChemTransferToast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                        }
                    });
                }
            });
        });

        /* ---------- NHẬN = XÁC NHẬN KIỂM TRA LÔ (modal transferReceiveCheckModal) ---------- */
        var $crc = $('#chemReceiveCheckModal');
        var crcForm = null; // form .form-chem-transfer-receive của mục đang nhận

        function crcSync() {
            var failed = $crc.find('[name="crc_check_result"]:checked').val() === 'failed';

            $crc.find('.imp-check-opt').removeClass('is-active');
            $crc.find('[name="crc_check_result"]:checked').closest('.imp-check-opt').addClass('is-active');
            $crc.find('.js-crc-passed-only').toggleClass('d-none', failed);
            $crc.find('.js-crc-hint-failed').toggleClass('d-none', !failed);
            $crc.find('.js-crc-note-star').toggleClass('d-none', !failed);
            $crc.find('.js-crc-submit')
                .toggleClass('btn-success', !failed)
                .toggleClass('btn-danger', failed)
                .html(failed
                    ? '<i class="fas fa-ban mr-1"></i> Ghi nhận Không đạt (từ chối nhận)'
                    : '<i class="fas fa-clipboard-check mr-1"></i> Xác nhận Kiểm tra Đạt &amp; Nhận');
        }

        $crc.on('change', '[name="crc_check_result"]', crcSync);

        // Bootstrap 4 gỡ modal-open khi đóng modal con - trả lại để phiếu chi tiết còn cuộn được
        $crc.on('hidden.bs.modal', function() {
            if ($('.modal.show').length) {
                $('body').addClass('modal-open');
            }
        });

        $(document).on('click', '.btn-trigger-chem-transfer-receive', function(e) {
            e.preventDefault();
            if (!$crc.length) return;

            var row = $(this).data('row') || {};
            crcForm = $(this).closest('.form-chem-transfer-receive');

            ['code', 'chemical', 'amount', 'from', 'batch_no', 'invoice_number', 'invoice_date', 'expired_date', 'internal_expired_date'].forEach(function(key) {
                $crc.find('.crc-' + key.replace(/_/g, '-')).text(row[key] || '—');
            });

            $crc.find('[name="crc_check_result"][value="passed"]').prop('checked', true);
            $crc.find('[name="check_note"]').val('');
            $crc.find('[name="dest_location_id"]').val('').trigger('change');
            crcSync();

            // Modal con nằm trên phiếu chi tiết: nền mờ của nó phải che phiếu chi tiết
            $crc.one('shown.bs.modal', function() {
                $('.modal-backdrop').last().css('z-index', 1055);
            });
            $crc.modal('show');
        });

        $crc.on('click', '.js-crc-submit', function() {
            if (!crcForm) return;

            var failed = $crc.find('[name="crc_check_result"]:checked').val() === 'failed';
            var note = $.trim($crc.find('[name="check_note"]').val());
            var data = {
                _token: crcForm.find('[name="_token"]').val(),
                item_id: crcForm.find('[name="item_id"]').val()
            };

            if (failed) {
                if (!note) {
                    ChemTransferToast.fire({ icon: 'error', title: 'Vui lòng nhập kết luận kiểm tra (lý do không đạt)!' });
                    $crc.find('[name="check_note"]').trigger('focus');
                    return;
                }
                data.return_note = 'Kiểm tra không đạt: ' + note;
            } else {
                data.dest_location_id = $crc.find('[name="dest_location_id"]').val();
                if (!data.dest_location_id) {
                    ChemTransferToast.fire({ icon: 'error', title: 'Vui lòng chọn định khu vị trí lưu trữ thật!' });
                    return;
                }
                data.check_note = note;
            }

            var $submit = $(this);
            var label = $submit.html();

            $.ajax({
                url: failed ? crcForm.data('reject-url') : crcForm.attr('action'),
                type: 'POST',
                data: data,
                beforeSend: function() {
                    $submit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
                },
                success: function(res) {
                    if (res.success) {
                        ChemTransferToast.fire({ icon: 'success', title: res.message });
                        // crcForm nằm trong phiếu chi tiết (modal Kiểm tra là modal con)
                        chemTransferReload(crcForm);
                    } else {
                        $submit.prop('disabled', false).html(label);
                        ChemTransferToast.fire({ icon: 'error', title: res.message });
                    }
                },
                error: function() {
                    $submit.prop('disabled', false).html(label);
                    ChemTransferToast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                }
            });
        });
    });
</script>
