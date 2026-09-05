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
    $transferAll = $transferSent->merge($transferReceived)->unique('id');
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
                                    <b class="text-primary ml-1 font-weight-bold">{{ $req->partner_name ?: '—' }}</b>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Trạng thái:</span>
                                    <span class="exp-req-badge {{ $expReqBadge($req->status)['class'] }} ml-1">
                                        {{ $expReqBadge($req->status)['label'] }}
                                    </span>
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
                                    <th style="min-width: 200px">Hoá Chất</th>
                                    <th style="min-width: 90px" class="text-right">SL Đề Nghị</th>
                                    @if ($iAmSource)
                                        <th style="min-width: 220px">Phiếu Nhập (Kho Mình) <span class="text-danger">*</span></th>
                                        <th style="min-width: 100px" class="text-right">SL Cấp Phát <span class="text-danger">*</span></th>
                                        <th style="min-width: 90px">ĐVT</th>
                                    @else
                                        <th style="min-width: 200px">Mã Cấp / Vị Trí Nhận</th>
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
                                                <input type="number" step="0.0001" min="0.0001" name="issued_amount"
                                                    class="form-control text-right font-weight-bold input-issue-amount"
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
                                                    {{-- Phòng mình (A) đang nhận: tự chọn vị trí lưu của phòng mình --}}
                                                    <select name="dest_location_id" class="form-control input-dest-location-receive mt-1" style="height: 34px !important; min-height: 34px !important; font-size: 0.82rem;">
                                                        <option value="">-- Chọn vị trí lưu --</option>
                                                        @foreach ($transferOwnLocations as $loc)
                                                            <option value="{{ $loc->id }}">{{ $loc->code }}@if ($loc->warehouse_name) ({{ $loc->warehouse_name }}/{{ $loc->room_name }}/{{ $loc->shelf_name }})@endif</option>
                                                        @endforeach
                                                    </select>
                                                    @if (! in_array((int) $item->category_id, $declaredCategoryIds, true))
                                                        <small class="text-danger d-block mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Chưa khai danh mục này ở tab "Hoá Chất Của Phòng"</small>
                                                    @endif
                                                @elseif ($item->dest_location_code)
                                                    <span class="std-location-tag mt-1 d-block" style="width: fit-content;">{{ $item->dest_location_code }}</span>
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
                                                    <form action="{{ route('pages.export.chemicalExport.transferReceiveStore') }}" method="POST" class="d-inline form-chem-transfer-receive">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <input type="hidden" name="dest_location_id" class="hidden-dest-location-receive" value="">
                                                        <button type="button" class="btn btn-xs btn-success btn-trigger-chem-transfer-receive px-2 py-1 shadow-sm" title="Xác nhận đã nhận hàng">
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
                    $form.off('submit').submit();
                }
            });
        });

        $(document).on('click', '.btn-trigger-chem-transfer-issue', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('.issue-row');
            var $form = $btn.closest('.form-chem-transfer-issue');

            var importId = $row.find('.chem-pick-display').data('import-id');
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
                                setTimeout(function() { location.reload(); }, 600);
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

        $(document).on('click', '.btn-trigger-chem-transfer-receive', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('.issue-row');
            var $form = $btn.closest('.form-chem-transfer-receive');

            var destLocation = $row.find('.input-dest-location-receive').val();
            $form.find('.hidden-dest-location-receive').val(destLocation);

            Swal.fire({
                title: 'Xác nhận đã nhận hàng?',
                text: 'Hệ thống sẽ tạo phiếu nhập mới cho phòng mình theo đúng số lượng đã cấp phát.',
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
                                setTimeout(function() { location.reload(); }, 600);
                            } else {
                                $btn.prop('disabled', false).html('<i class="fas fa-inbox mr-1"></i> Nhận');
                                ChemTransferToast.fire({ icon: 'error', title: res.message });
                            }
                        },
                        error: function() {
                            $btn.prop('disabled', false).html('<i class="fas fa-inbox mr-1"></i> Nhận');
                            ChemTransferToast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                        }
                    });
                }
            });
        });
    });
</script>
