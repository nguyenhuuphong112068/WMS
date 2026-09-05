@php
    $stdReqStatus = $stdReqStatus ?? [
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
    $stdReqBadge = $stdReqBadge ?? fn($status) => $stdReqStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
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
                                    <span class="exp-req-badge {{ $stdReqBadge($req->status)['class'] }} ml-1">
                                        {{ $stdReqBadge($req->status)['label'] }}
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
                                    <th style="min-width: 200px">Chất Chuẩn</th>
                                    <th style="min-width: 90px" class="text-right">SL Đề Nghị</th>
                                    @if ($iAmSource)
                                        <th style="min-width: 210px">Mã Ống Chuẩn (Kho Mình) <span class="text-danger">*</span></th>
                                        <th style="min-width: 100px" class="text-right">SL Cấp Phát <span class="text-danger">*</span></th>
                                        <th style="min-width: 90px">ĐVT</th>
                                    @else
                                        <th style="min-width: 230px">Mã Ống Cấp / Thông Tin Nhận</th>
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
                                            <span class="font-weight-bold text-dark">{{ $item->standard_name }}</span>
                                            <span class="badge badge-secondary ml-1">{{ $item->category_code }}</span>
                                        </td>
                                        <td class="align-middle text-right font-weight-bold text-primary">
                                            {{ $expNum($item->requested_amount) }} {{ $item->requested_unit }}
                                        </td>

                                        @if ($iAmSource && $item->status === 'pending')
                                            {{-- B đang cấp phát: chỉ chọn ống của mình + số lượng. 4 thông tin riêng
                                                 của phòng A (vị trí lưu/chỉ tiêu kiểm/kiểm soát KL/chiết ống) là
                                                 việc của A, để A tự khai ở bước Nhận, không khai hộ ở đây. --}}
                                            <td class="align-middle">
                                                <select name="import_id" class="select-issue-import d-none">
                                                    <option value="">-- Chọn mã ống chuẩn --</option>
                                                    @foreach (($availableImports ?? collect())->where('category_id', $item->category_id) as $imp)
                                                        <option value="{{ $imp->id }}" {{ !$imp->selectable ? 'disabled' : '' }}
                                                            data-remaining="{{ (float) $imp->remaining }}"
                                                            data-unit="{{ $imp->unit_short_name ?: '' }}">
                                                            {{ $imp->code }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="std-pick-box d-flex align-items-center" style="gap: 5px;">
                                                    <div class="std-pick-display flex-grow-1 is-empty">
                                                        <span class="text-muted"><i class="fas fa-flask mr-1"></i> Chưa chọn ống chuẩn</span>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-open-std-picker"
                                                        data-category-id="{{ $item->category_id }}"
                                                        data-standard-name="{{ $item->standard_name }}"
                                                        data-category-code="{{ $item->category_code }}"
                                                        title="Chọn từ danh sách tồn">
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
                                                    {{-- Phòng mình (A) đang nhận: tự khai 4 thông tin riêng của phòng mình --}}
                                                    <div class="mt-1">
                                                        <select name="dest_location_id" class="form-control input-dest-location-receive mb-1" style="height: 32px !important; min-height: 32px !important; font-size: 0.78rem;">
                                                            <option value="">-- Vị trí lưu --</option>
                                                            @foreach ($transferOwnLocations as $loc)
                                                                <option value="{{ $loc->id }}">{{ $loc->code }}@if ($loc->warehouse_name) ({{ $loc->warehouse_name }}/{{ $loc->room_name }}/{{ $loc->shelf_name }})@endif</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="dest_purpose_id" class="form-control input-dest-purpose-receive mb-1" style="height: 32px !important; min-height: 32px !important; font-size: 0.78rem;">
                                                            <option value="">-- Chỉ tiêu kiểm --</option>
                                                            @foreach ($purposes as $purpose)
                                                                <option value="{{ $purpose->id }}">{{ $purpose->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="custom-control custom-checkbox mb-1">
                                                            <input type="checkbox" class="custom-control-input input-dest-weight-controlled-receive" id="destWcR_{{ $item->id }}" value="1">
                                                            <label class="custom-control-label" for="destWcR_{{ $item->id }}" style="font-size: 0.78rem;">Kiểm soát KL</label>
                                                        </div>
                                                        <select name="dest_standard_form" class="form-control select-dest-standard-form-receive d-none mb-1" style="height: 30px !important; min-height: 30px !important; font-size: 0.76rem;">
                                                            <option value="">-- Dạng chuẩn --</option>
                                                            <option value="Dạng Bột Rời">Dạng Bột Rời</option>
                                                            <option value="Dạng Bột Mịn">Dạng Bột Mịn</option>
                                                            <option value="Dạng Sệt">Dạng Sệt</option>
                                                        </select>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input input-dest-requires-aliquot-receive" id="destAliquotR_{{ $item->id }}" value="1">
                                                            <label class="custom-control-label" for="destAliquotR_{{ $item->id }}" style="font-size: 0.78rem;">Chiết ống</label>
                                                        </div>
                                                    </div>
                                                    @if (! in_array((int) $item->category_id, $declaredCategoryIds, true))
                                                        <small class="text-danger d-block mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Chưa khai danh mục này ở tab "Chất Chuẩn Của Phòng"</small>
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
                                            @if ($iAmSource && $item->status === 'pending' && user_can('export_standard_issue'))
                                                <form action="{{ route('pages.export.standardExport.transferIssueStore') }}" method="POST" class="d-inline form-transfer-issue">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <input type="hidden" name="import_id" class="hidden-import-id" value="">
                                                    <input type="hidden" name="issued_amount" class="hidden-issued-amount" value="">
                                                    <input type="hidden" name="issued_unit" class="hidden-issued-unit" value="">
                                                    <button type="button" class="btn btn-xs btn-success btn-trigger-transfer-issue px-2 py-1 shadow-sm" title="Xác nhận cấp phát ngay">
                                                        <i class="fas fa-check-circle mr-1"></i> Cấp phát
                                                    </button>
                                                </form>

                                                <form class="form-md-confirm d-inline"
                                                    action="{{ route('pages.export.standardExport.transferRequestReject') }}"
                                                    method="POST"
                                                    data-title="Từ chối cấp mục này?"
                                                    data-danger="1">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <button type="button" class="btn btn-xs btn-danger ml-1 px-2 py-1 shadow-sm btn-transfer-reject" title="Từ chối cấp phát">
                                                        <i class="fas fa-ban mr-1"></i> Từ chối
                                                    </button>
                                                </form>
                                            @elseif (! $iAmSource && $item->status === 'issued' && (user_can('export_standard_transfer_receive') || user_can('export_standard_transfer_return')))
                                                @if (user_can('export_standard_transfer_receive'))
                                                    <form action="{{ route('pages.export.standardExport.transferReceiveStore') }}" method="POST" class="d-inline form-transfer-receive">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <input type="hidden" name="dest_location_id" class="hidden-dest-location-receive" value="">
                                                        <input type="hidden" name="dest_purpose_id" class="hidden-dest-purpose-receive" value="">
                                                        <input type="hidden" name="dest_weight_controlled" class="hidden-dest-weight-controlled-receive" value="0">
                                                        <input type="hidden" name="dest_standard_form" class="hidden-dest-standard-form-receive" value="">
                                                        <input type="hidden" name="dest_requires_aliquot" class="hidden-dest-requires-aliquot-receive" value="0">
                                                        <button type="button" class="btn btn-xs btn-success btn-trigger-transfer-receive px-2 py-1 shadow-sm" title="Xác nhận đã nhận hàng">
                                                            <i class="fas fa-inbox mr-1"></i> Nhận
                                                        </button>
                                                    </form>
                                                @endif
                                                @if (user_can('export_standard_transfer_return'))
                                                    <form class="form-md-confirm d-inline"
                                                        action="{{ route('pages.export.standardExport.transferReceiveReject') }}"
                                                        method="POST"
                                                        data-title="Từ chối nhận mục này?"
                                                        data-danger="1">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <button type="button" class="btn btn-xs btn-danger ml-1 px-2 py-1 shadow-sm btn-transfer-return" title="Từ chối nhận, hoàn tồn cho phòng gửi">
                                                            <i class="fas fa-undo mr-1"></i> Từ chối nhận
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="exp-req-badge {{ $stdReqBadge($item->status)['class'] }}">
                                                    {{ $stdReqBadge($item->status)['label'] }}
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
                                        <td colspan="8" class="text-center text-muted py-3">Phiếu này không có chất chuẩn nào.</td>
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
        var TransferToast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });

        // Ẩn/hiện chọn Dạng chuẩn theo checkbox Kiểm soát khối lượng (cả cấp phát lẫn nhận)
        $(document).on('change', '.input-dest-weight-controlled, .input-dest-weight-controlled-receive', function() {
            $(this).closest('td, div').find('.select-dest-standard-form, .select-dest-standard-form-receive').toggleClass('d-none', !this.checked);
        });

        // Yêu cầu lý do trước khi gửi form từ chối cấp phát
        $(document).on('click', '.btn-transfer-reject', function(e) {
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
        $(document).on('click', '.btn-transfer-return', function(e) {
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

        $(document).on('click', '.btn-trigger-transfer-issue', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('.issue-row');
            var $form = $btn.closest('.form-transfer-issue');

            var importId = $row.find('.select-issue-import').val();
            var issuedAmount = $row.find('.input-issue-amount').val();
            var issuedUnit = $row.find('.select-issue-unit').val();

            if (!importId) {
                TransferToast.fire({ icon: 'error', title: 'Vui lòng chọn Mã Ống Chuẩn!' });
                return;
            }
            if (!issuedAmount || issuedAmount <= 0) {
                TransferToast.fire({ icon: 'error', title: 'Vui lòng nhập SL Cấp Phát hợp lệ!' });
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
                                TransferToast.fire({ icon: 'success', title: res.message });
                                setTimeout(function() { location.reload(); }, 600);
                            } else {
                                $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Cấp phát');
                                TransferToast.fire({ icon: 'error', title: res.message });
                            }
                        },
                        error: function() {
                            $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Cấp phát');
                            TransferToast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.btn-trigger-transfer-receive', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('.issue-row');
            var $form = $btn.closest('.form-transfer-receive');

            var destLocation = $row.find('.input-dest-location-receive').val();
            var destPurpose = $row.find('.input-dest-purpose-receive').val();
            var destWeightControlled = $row.find('.input-dest-weight-controlled-receive').is(':checked') ? 1 : 0;
            var destStandardForm = $row.find('.select-dest-standard-form-receive').val();
            var destRequiresAliquot = $row.find('.input-dest-requires-aliquot-receive').is(':checked') ? 1 : 0;

            $form.find('.hidden-dest-location-receive').val(destLocation);
            $form.find('.hidden-dest-purpose-receive').val(destPurpose);
            $form.find('.hidden-dest-weight-controlled-receive').val(destWeightControlled);
            $form.find('.hidden-dest-standard-form-receive').val(destStandardForm);
            $form.find('.hidden-dest-requires-aliquot-receive').val(destRequiresAliquot);

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
                                TransferToast.fire({ icon: 'success', title: res.message });
                                setTimeout(function() { location.reload(); }, 600);
                            } else {
                                $btn.prop('disabled', false).html('<i class="fas fa-inbox mr-1"></i> Nhận');
                                TransferToast.fire({ icon: 'error', title: res.message });
                            }
                        },
                        error: function() {
                            $btn.prop('disabled', false).html('<i class="fas fa-inbox mr-1"></i> Nhận');
                            TransferToast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                        }
                    });
                }
            });
        });
    });
</script>
