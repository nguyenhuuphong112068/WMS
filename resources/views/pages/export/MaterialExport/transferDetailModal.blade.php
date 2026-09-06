{{--
| Phiếu chi tiết của một đề nghị chuyển vật tư liên phòng ban. Một modal dùng cho cả hai
| vai, tự đổi cột và nút theo phòng đang đăng nhập:
|   - Mình là B (phòng được đề nghị): chọn mã xuất nhập của kho mình + số lượng -> Cấp phát.
|   - Mình là A (phòng đề nghị)     : chọn định khu của phòng mình -> Nhận / Từ chối nhận.
--}}
@php
    $mtStatus = $mtStatus ?? [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'canceled' => ['label' => 'Đã huỷ', 'class' => 'rejected'],
        'issued' => ['label' => 'Chờ nhận', 'class' => 'issued'],
        'received' => ['label' => 'Đã nhận', 'class' => 'accepted'],
        'returned' => ['label' => 'Đã từ chối nhận', 'class' => 'rejected'],
    ];
    $mtBadge = $mtBadge ?? fn($status) => $mtStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
    $mtAll = $transferSent->merge($transferReceived)->unique('id');
@endphp

<style>
    .mat-pick-display {
        border: 1px solid #ced4da;
        border-radius: var(--border-radius-md, 8px);
        padding: 7px 10px;
        min-height: 38px;
        background: #fff;
        font-size: 0.86rem;
    }
    .mat-pick-display.is-empty {
        border-style: dashed;
        background: var(--primary-soft, #eaf3fc);
    }
</style>

@foreach ($mtAll as $req)
    @php
        $items = $transferItems[$req->id] ?? collect();
        $iAmSource = (int) $req->to_department_id === (int) $currentDepartmentId;
    @endphp
    <div class="modal fade md-modal" id="matTransferDetailModal_{{ $req->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 96vw;" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                        <i class="fas fa-people-arrows mr-2"></i> Đề Nghị Liên Phòng Ban: {{ $req->code }}
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
                                    <b class="text-primary ml-1">{{ $req->partner_name ?: '—' }}</b>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Trạng thái:</span>
                                    <span class="exp-req-badge {{ $mtBadge($req->status)['class'] }} ml-1">{{ $mtBadge($req->status)['label'] }}</span>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Người lập:</span>
                                    <b class="ml-1">{{ $req->updated_by ?: $req->created_by ?: '—' }}</b>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Ngày lập:</span>
                                    <b class="ml-1">{{ $expDate($req->created_at) }}</b>
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
                                    <th style="min-width: 220px">Vật Tư</th>
                                    <th style="min-width: 90px" class="text-right">SL Đề Nghị</th>
                                    @if ($iAmSource)
                                        <th style="min-width: 240px">Mã Xuất Nhập (Kho Mình) <span class="text-danger">*</span></th>
                                        <th style="min-width: 110px" class="text-right">SL Cấp Phát <span class="text-danger">*</span></th>
                                    @else
                                        <th style="min-width: 220px">Mã Cấp / Định Khu Nhận</th>
                                        <th style="min-width: 110px" class="text-right">SL Cấp Phát</th>
                                    @endif
                                    <th style="min-width: 90px">ĐVT</th>
                                    <th style="min-width: 130px">Ghi Chú</th>
                                    <th style="min-width: 170px" class="text-center">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr class="mat-issue-row">
                                        <td class="text-center align-middle text-muted">{{ $loop->iteration }}</td>
                                        <td class="align-middle">
                                            <span class="font-weight-bold text-dark">{{ $item->material_name ?: '—' }}</span>
                                            <span class="badge badge-secondary ml-1">{{ $item->category_code }}</span>
                                            @if ($item->technical_specification)
                                                <div class="md-sub small text-muted">{{ $item->technical_specification }}</div>
                                            @endif
                                        </td>
                                        <td class="align-middle text-right font-weight-bold text-primary">
                                            {{ $expNum($item->requested_amount) }} {{ $item->requested_unit }}
                                        </td>

                                        @if ($iAmSource && $item->status === 'pending')
                                            {{-- B đang cấp phát: chỉ chọn mã xuất nhập của mình + số lượng. Định khu
                                                 là việc của phòng A, để A tự chọn ở bước Nhận. --}}
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center" style="gap: 5px;">
                                                    <div class="mat-pick-display flex-grow-1 is-empty">
                                                        <span class="text-muted"><i class="fas fa-boxes mr-1"></i> Chưa chọn mã xuất nhập</span>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-open-mat-picker"
                                                        data-category-id="{{ $item->category_id }}" title="Chọn từ tồn kho của phòng">
                                                        <i class="fas fa-list-ul"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="align-middle text-right">
                                                <input type="text" inputmode="decimal" min="0.0001" name="issued_amount"
                                                    class="form-control text-right font-weight-bold input-mat-issue-amount js-decimal"
                                                    value="{{ (float) $item->requested_amount }}"
                                                    style="height: 38px !important; min-height: 38px !important; font-size: 0.88rem;" required>
                                            </td>
                                            <td class="align-middle text-center">
                                                <select name="issued_unit" class="form-control select-mat-issue-unit text-center"
                                                    style="height: 38px !important; min-height: 38px !important; font-size: 0.88rem;">
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
                                                    {{-- Phòng mình (A) đang nhận: tự chọn định khu của phòng mình --}}
                                                    <select name="dest_location_id" class="form-control input-mat-dest-location mt-1"
                                                        style="height: 34px !important; min-height: 34px !important; font-size: 0.82rem;">
                                                        <option value="">-- Chọn định khu --</option>
                                                        @foreach ($transferOwnLocations as $loc)
                                                            <option value="{{ $loc->id }}">
                                                                {{ $loc->code }}@if ($loc->warehouse_name) ({{ $loc->warehouse_name }}/{{ $loc->shelf_name }}/{{ $loc->column_name }}/{{ $loc->tier_name }})@endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if (! in_array((int) $item->category_id, $declaredCategoryIds, true))
                                                        <small class="text-danger d-block mt-1">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i>Chưa khai vật tư này ở tab "Vật Tư Của Phòng"
                                                        </small>
                                                    @endif
                                                @elseif ($item->dest_location_code)
                                                    <span class="badge badge-light border mt-1 d-block" style="width: fit-content;">{{ $item->dest_location_code }}</span>
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
                                            @if ($iAmSource && $item->status === 'pending' && user_can('export_material_transfer'))
                                                <form action="{{ route('pages.export.materialExport.transferIssueStore') }}" method="POST" class="d-inline form-mat-transfer-issue">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <input type="hidden" name="import_id" class="hidden-mat-import-id" value="">
                                                    <input type="hidden" name="issued_amount" class="hidden-mat-issued-amount" value="">
                                                    <input type="hidden" name="issued_unit" class="hidden-mat-issued-unit" value="">
                                                    <button type="button" class="btn btn-xs btn-success btn-trigger-mat-transfer-issue px-2 py-1 shadow-sm" title="Xác nhận cấp phát ngay">
                                                        <i class="fas fa-check-circle mr-1"></i> Cấp phát
                                                    </button>
                                                </form>

                                                <form action="{{ route('pages.export.materialExport.transferRequestReject') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <button type="button" class="btn btn-xs btn-danger ml-1 px-2 py-1 shadow-sm btn-mat-transfer-reject" title="Từ chối cấp phát">
                                                        <i class="fas fa-ban mr-1"></i> Từ chối
                                                    </button>
                                                </form>
                                            @elseif (! $iAmSource && $item->status === 'issued' && (user_can('export_material_transfer_receive') || user_can('export_material_transfer_return')))
                                                @if (user_can('export_material_transfer_receive'))
                                                    <form action="{{ route('pages.export.materialExport.transferReceiveStore') }}" method="POST" class="d-inline form-mat-transfer-receive">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <input type="hidden" name="dest_location_id" class="hidden-mat-dest-location" value="">
                                                        <button type="button" class="btn btn-xs btn-success btn-trigger-mat-transfer-receive px-2 py-1 shadow-sm" title="Xác nhận đã nhận hàng">
                                                            <i class="fas fa-inbox mr-1"></i> Nhận
                                                        </button>
                                                    </form>
                                                @endif
                                                @if (user_can('export_material_transfer_return'))
                                                    <form action="{{ route('pages.export.materialExport.transferReceiveReject') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <button type="button" class="btn btn-xs btn-danger ml-1 px-2 py-1 shadow-sm btn-mat-transfer-return" title="Từ chối nhận, hoàn tồn cho phòng gửi">
                                                            <i class="fas fa-undo mr-1"></i> Từ chối nhận
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="exp-req-badge {{ $mtBadge($item->status)['class'] }}">{{ $mtBadge($item->status)['label'] }}</span>
                                                @if ($item->issued_by)
                                                    <small class="d-block text-muted">Cấp: {{ $item->issued_by }}</small>
                                                @endif
                                                @if ($item->issued_at)
                                                    <small class="d-block text-muted" style="font-size: 0.78rem;">{{ $expDateTime($item->issued_at) }}</small>
                                                @endif
                                                @if ($item->received_by)
                                                    <small class="d-block text-muted">Nhận: {{ $item->received_by }}</small>
                                                @endif
                                                @if ($item->received_at)
                                                    <small class="d-block text-muted" style="font-size: 0.78rem;">{{ $expDateTime($item->received_at) }}</small>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">Phiếu này không có vật tư nào.</td>
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
        var MatTransferToast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });

        /** Hỏi lý do rồi mới gửi form (từ chối cấp phát / từ chối nhận). */
        function askReasonThenSubmit($form, options) {
            Swal.fire({
                title: options.title,
                text: options.text || '',
                input: 'text',
                inputPlaceholder: options.placeholder,
                showCancelButton: true,
                confirmButtonText: options.confirmText,
                cancelButtonText: 'Huỷ',
                confirmButtonColor: '#dc3545',
                inputValidator: function(value) {
                    if (!value) {
                        return options.requiredMessage;
                    }
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    if ($form.find('input[name="' + options.field + '"]').length === 0) {
                        $form.append('<input type="hidden" name="' + options.field + '">');
                    }
                    $form.find('input[name="' + options.field + '"]').val(result.value);
                    $form.off('submit').submit();
                }
            });
        }

        $(document).on('click', '.btn-mat-transfer-reject', function(e) {
            e.preventDefault();
            askReasonThenSubmit($(this).closest('form'), {
                title: 'Từ chối cấp phát mục này?',
                placeholder: 'Nhập lý do từ chối...',
                confirmText: 'Từ chối',
                field: 'reject_note',
                requiredMessage: 'Vui lòng nhập lý do từ chối!'
            });
        });

        $(document).on('click', '.btn-mat-transfer-return', function(e) {
            e.preventDefault();
            askReasonThenSubmit($(this).closest('form'), {
                title: 'Từ chối nhận mục này?',
                text: 'Tồn kho sẽ được hoàn lại cho phòng gửi.',
                placeholder: 'Nhập lý do từ chối nhận...',
                confirmText: 'Từ chối nhận',
                field: 'return_note',
                requiredMessage: 'Vui lòng nhập lý do từ chối nhận!'
            });
        });

        /** Gửi form bằng AJAX rồi tải lại trang để bảng và tồn kho cùng cập nhật. */
        function submitTransferForm($btn, $form, idleHtml) {
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                beforeSend: function() {
                    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
                },
                success: function(res) {
                    if (res.success) {
                        MatTransferToast.fire({ icon: 'success', title: res.message });
                        setTimeout(function() { location.reload(); }, 600);
                    } else {
                        $btn.prop('disabled', false).html(idleHtml);
                        MatTransferToast.fire({ icon: 'error', title: res.message });
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html(idleHtml);
                    MatTransferToast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                }
            });
        }

        $(document).on('click', '.btn-trigger-mat-transfer-issue', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $row = $btn.closest('.mat-issue-row');
            var $form = $btn.closest('.form-mat-transfer-issue');

            var importId = $row.find('.mat-pick-display').data('import-id');
            var issuedAmount = $row.find('.input-mat-issue-amount').val();

            if (!importId) {
                MatTransferToast.fire({ icon: 'error', title: 'Vui lòng chọn Mã Xuất Nhập!' });
                return;
            }

            if (!issuedAmount || parseFloat(issuedAmount) <= 0) {
                MatTransferToast.fire({ icon: 'error', title: 'Vui lòng nhập SL Cấp Phát hợp lệ!' });
                return;
            }

            $form.find('.hidden-mat-import-id').val(importId);
            $form.find('.hidden-mat-issued-amount').val(issuedAmount);
            $form.find('.hidden-mat-issued-unit').val($row.find('.select-mat-issue-unit').val());

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
                    submitTransferForm($btn, $form, '<i class="fas fa-check-circle mr-1"></i> Cấp phát');
                }
            });
        });

        $(document).on('click', '.btn-trigger-mat-transfer-receive', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $row = $btn.closest('.mat-issue-row');
            var $form = $btn.closest('.form-mat-transfer-receive');

            $form.find('.hidden-mat-dest-location').val($row.find('.input-mat-dest-location').val());

            Swal.fire({
                title: 'Xác nhận đã nhận hàng?',
                text: 'Hệ thống sẽ tạo mã xuất nhập mới cho phòng mình theo đúng số lượng đã cấp phát.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Huỷ'
            }).then(function(result) {
                if (result.isConfirmed) {
                    submitTransferForm($btn, $form, '<i class="fas fa-inbox mr-1"></i> Nhận');
                }
            });
        });
    });
</script>
