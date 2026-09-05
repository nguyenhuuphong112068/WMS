@php
    $stdReqStatus = $stdReqStatus ?? [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'issued' => ['label' => 'Đã cấp', 'class' => 'accepted'],
    ];
    $stdReqBadge = $stdReqBadge ?? fn($status) => $stdReqStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
    $expNum = $expNum ?? fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $expDate = $expDate ?? fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
@endphp

@foreach ($requests as $req)
    @php
        $items = $requestItems[$req->id] ?? collect();
    @endphp
    <div class="modal fade md-modal" id="requestDetailModal_{{ $req->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 95vw;" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                        <i class="fas fa-file-invoice mr-2"></i> Phiếu Đề Nghị Cấp Phát Chuẩn: {{ $req->code }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-3">
                    {{-- Summary info banner --}}
                    <div class="card mb-3 border-0" style="background-color: #f1f5f9 !important;">
                        <div class="card-body py-2 px-3">
                            <div class="row align-items-center" style="font-size: 0.9rem;">
                                <div class="col-md-3">
                                    <span class="text-muted">Tổ đề nghị:</span>
                                    <b class="text-primary ml-1 font-weight-bold">{{ $req->group_name ?: '—' }}</b>
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

                    {{-- Items table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 w-100" style="font-size: 0.88rem;">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th style="width: 40px">STT</th>
                                    <th style="min-width: 220px">Mã Ống Chuẩn <span class="text-danger">*</span></th>
                                    <th style="min-width: 180px">Chất Chuẩn</th>
                                    <th style="min-width: 100px">Qui Cách</th>
                                    <th style="min-width: 140px">Nhà Sản Xuất</th>
                                    <th style="min-width: 130px">Mục Đích</th>
                                    <th style="min-width: 90px" class="text-right">SL Đề Nghị</th>
                                    <th style="min-width: 110px" class="text-right">SL Cấp Phát <span class="text-danger">*</span></th>
                                    <th style="min-width: 100px" class="text-center">ĐVT</th>
                                    <th style="min-width: 100px" class="text-center">Trả Chuẩn</th>
                                    <th style="min-width: 130px">Tên Sản Phẩm</th>
                                    <th style="min-width: 140px">Chỉ Tiêu</th>
                                    <th style="min-width: 130px">KNV</th>
                                    <th style="min-width: 130px">Ghi Chú</th>
                                    <th style="min-width: 160px" class="text-center">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr class="issue-row">
                                        <td class="text-center align-middle text-muted">{{ $loop->iteration }}</td>
                                        
                                        {{-- Mã Ống Chuẩn --}}
                                        <td class="align-middle">
                                            @if ($item->status === 'pending')
                                                @php
                                                    $catImports = ($availableImports ?? collect())->where('category_id', $item->category_id);
                                                    $pickedImp = $item->import_id ? $catImports->firstWhere('id', $item->import_id) : null;
                                                @endphp
                                                {{-- Select ẩn giữ giá trị cho JS cấp phát / lưu nháp; người dùng chọn qua modal danh sách tồn --}}
                                                <select name="import_id" class="select-issue-import d-none">
                                                    <option value="">-- Chọn mã ống chuẩn --</option>
                                                    @forelse ($catImports as $imp)
                                                        @php
                                                            $disabled = !$imp->selectable;
                                                        @endphp
                                                        <option value="{{ $imp->id }}" {{ $item->import_id == $imp->id ? 'selected' : '' }}
                                                            {{ $disabled && $item->import_id != $imp->id ? 'disabled' : '' }}
                                                            data-remaining="{{ (float)$imp->remaining }}"
                                                            data-unit="{{ $imp->unit_short_name ?: '' }}">
                                                            {{ $imp->code }}
                                                        </option>
                                                    @empty
                                                        <option value="" disabled>Chưa có ống chuẩn còn tồn</option>
                                                    @endforelse
                                                </select>

                                                <div class="std-pick-box d-flex align-items-center" style="gap: 5px;">
                                                    <div class="std-pick-display flex-grow-1 {{ $pickedImp ? '' : 'is-empty' }}">
                                                        @if ($pickedImp)
                                                            <span class="exp-code font-weight-bold">{{ $pickedImp->code }}</span>
                                                            <small class="text-muted d-block">Lô: {{ $pickedImp->batch_no ?: '—' }} · Tồn: {{ $expNum($pickedImp->remaining) }} {{ $pickedImp->unit_short_name ?: '' }}</small>
                                                        @else
                                                            <span class="text-muted"><i class="fas fa-flask mr-1"></i> Chưa chọn ống chuẩn</span>
                                                        @endif
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-open-std-picker"
                                                        data-category-id="{{ $item->category_id }}"
                                                        data-standard-name="{{ $item->standard_name }}"
                                                        data-category-code="{{ $item->category_code }}"
                                                        title="Chọn từ danh sách tồn">
                                                        <i class="fas fa-list-ul"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="exp-code font-weight-bold">{{ $item->import_code }}</span>
                                                @if ($item->batch_no)
                                                    <small class="text-muted d-block">Lô: {{ $item->batch_no }}</small>
                                                @endif
                                                @if ($item->location_code)
                                                    <span class="std-location-tag mt-1">{{ $item->location_code }}</span>
                                                @endif
                                            @endif
                                        </td>

                                        {{-- Tên Chất Chuẩn --}}
                                        <td class="align-middle">
                                            <span class="font-weight-bold text-dark">{{ $item->standard_name }}</span>
                                            <span class="badge badge-secondary ml-1">{{ $item->category_code }}</span>
                                        </td>

                                        <td class="align-middle text-center">{{ $item->specification ?: '—' }}</td>
                                        <td class="align-middle">{{ $item->supplier_name ?: '—' }}</td>
                                        
                                        <td class="align-middle">
                                            @if ($item->purpose_name)
                                                <span class="text-dark">{{ $item->purpose_name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        {{-- SL Đề Nghị --}}
                                        <td class="align-middle text-right font-weight-bold text-primary">
                                            {{ $expNum($item->requested_amount) }}
                                        </td>

                                        {{-- SL Cấp Phát: Nhập tay trực tiếp --}}
                                        <td class="align-middle text-right">
                                            @if ($item->status === 'pending')
                                                <input type="number" step="0.0001" min="0.0001" name="issued_amount"
                                                    class="form-control text-right font-weight-bold input-issue-amount"
                                                    value="{{ (float)($item->issued_amount ?? $item->requested_amount) }}"
                                                    style="height: 38px !important; min-height: 38px !important; font-size: 0.88rem;" required>
                                            @else
                                                <span class="font-weight-bold text-success">{{ $expNum($item->issued_amount) }}</span>
                                            @endif
                                        </td>

                                        {{-- ĐVT: Select trực tiếp --}}
                                        <td class="align-middle text-center">
                                            @if ($item->status === 'pending')
                                                <select name="issued_unit" class="form-control select-issue-unit text-center" style="height: 38px !important; min-height: 38px !important; font-size: 0.88rem;">
                                                    <option value="">-- ĐVT --</option>
                                                    @foreach ($units as $u)
                                                        @php 
                                                            $uVal = $u->short_name ?: $u->name; 
                                                            $selectedUnit = $item->issued_unit ?: $item->requested_unit;
                                                        @endphp
                                                        <option value="{{ $uVal }}" {{ ($selectedUnit == $uVal) ? 'selected' : '' }}>
                                                            {{ $uVal }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="font-weight-500">{{ $item->issued_unit ?: ($item->requested_unit ?: '—') }}</span>
                                            @endif
                                        </td>

                                        {{-- Trả Chuẩn Về --}}
                                        <td class="align-middle text-center">
                                            @if ($item->status === 'pending')
                                                <div class="custom-control custom-checkbox d-inline-block">
                                                    <input type="checkbox" class="custom-control-input input-return-standard" id="chkReturn_{{ $item->id }}" value="1" {{ $item->return_standard ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="chkReturn_{{ $item->id }}"></label>
                                                </div>
                                            @else
                                                @if ($item->return_standard)
                                                    <span class="badge badge-warning" title="Trả lại chuẩn sau khi dùng">
                                                        <i class="fas fa-undo-alt mr-1"></i> Trả lại
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            @endif
                                        </td>

                                        <td class="align-middle">{{ $item->product_name ?: '—' }}</td>
                                        <td class="align-middle">{{ $item->test_criteria ?: '—' }}</td>
                                        <td class="align-middle">{{ $item->analyst_name ?: '—' }}</td>
                                        <td class="align-middle text-muted" title="{{ $item->note }}">{{ $item->note ?: '—' }}</td>

                                        {{-- Thao Tác --}}
                                        <td class="align-middle text-center" style="white-space: nowrap;">
                                            @if ($item->status === 'pending' && user_can('export_standard_issue'))
                                                <form action="{{ route('pages.export.standardExport.issueStore') }}" method="POST" class="d-inline form-direct-issue">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <input type="hidden" name="import_id" class="hidden-import-id" value="">
                                                    <input type="hidden" name="issued_amount" class="hidden-issued-amount" value="">
                                                    <input type="hidden" name="issued_unit" class="hidden-issued-unit" value="">
                                                    <input type="hidden" name="return_standard" class="hidden-return-standard" value="0">
                                                    <button type="button" class="btn btn-xs btn-success btn-trigger-direct-issue px-2 py-1 shadow-sm" title="Xác nhận cấp phát ngay">
                                                        <i class="fas fa-check-circle mr-1"></i> Cấp phát
                                                    </button>
                                                </form>

                                                <form class="form-md-confirm d-inline"
                                                    action="{{ route('pages.export.standardExport.requestReject') }}"
                                                    method="POST"
                                                    data-title="Từ chối cấp mục này?"
                                                    data-danger="1">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <button type="submit" class="btn btn-xs btn-danger ml-1 px-2 py-1 shadow-sm" title="Từ chối cấp phát">
                                                        <i class="fas fa-ban mr-1"></i> Từ chối
                                                    </button>
                                                </form>
                                            @else
                                                <span class="exp-req-badge {{ $stdReqBadge($item->status)['class'] }}">
                                                    {{ $stdReqBadge($item->status)['label'] }}
                                                </span>
                                                @if ($item->issued_by)
                                                    <small class="d-block text-muted">{{ $item->issued_by }}</small>
                                                @endif
                                                @if ($item->issued_at)
                                                    <small class="d-block text-muted" style="font-size: 0.78rem;">{{ \Carbon\Carbon::parse($item->issued_at)->format('d/m/Y H:i') }}</small>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center text-muted py-3">Phiếu này không có chất chuẩn nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    @if ($req->status === 'draft' && user_can('export_standard_request'))
                        <button type="button" class="btn btn-warning" data-dismiss="modal" data-toggle="modal" data-target="#requestEditModal_{{ $req->id }}">
                            <i class="fas fa-edit mr-1"></i> Chỉnh sửa
                        </button>
                        <form class="form-md-confirm d-inline"
                            action="{{ route('pages.export.standardExport.requestSend') }}"
                            method="POST"
                            data-title="Gửi phiếu đề nghị {{ $req->code }} sang kho để cấp phát?">
                            @csrf
                            <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị này
                            </button>
                        </form>
                    @elseif (in_array($req->status, ['pending', 'partial']) && user_can('export_standard_issue'))
                        <button type="button" class="btn btn-primary btn-save-draft-issue" data-list-id="{{ $req->id }}">
                            <i class="fas fa-save mr-1"></i> Lưu
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Handle change import to auto select unit
        $(document).on('change', '.select-issue-import', function() {
            let $row = $(this).closest('.issue-row');
            let unit = $(this).find('option:selected').data('unit');
            if (unit) {
                let $unitSelect = $row.find('.select-issue-unit');
                if ($unitSelect.find('option[value="' + unit + '"]').length > 0) {
                    $unitSelect.val(unit).trigger('change');
                }
            }
        });

        // Handle direct issue button click
        $(document).on('click', '.btn-trigger-direct-issue', function(e) {
            e.preventDefault();
            let $btn = $(this);
            let $row = $btn.closest('.issue-row');
            let $form = $btn.closest('.form-direct-issue');

            let importId = $row.find('.select-issue-import').val();
            let issuedAmount = $row.find('.input-issue-amount').val();
            let issuedUnit = $row.find('.select-issue-unit').val();
            let returnStandard = $row.find('.input-return-standard').is(':checked') ? 1 : 0;

            if (!importId) {
                Toast.fire({ icon: 'error', title: 'Vui lòng chọn Mã Ống Chuẩn!' });
                $row.find('.select-issue-import').focus();
                return;
            }
            if (!issuedAmount || issuedAmount <= 0) {
                Toast.fire({ icon: 'error', title: 'Vui lòng nhập SL Cấp Phát hợp lệ!' });
                $row.find('.input-issue-amount').focus();
                return;
            }
            if (!issuedUnit) {
                Toast.fire({ icon: 'error', title: 'Vui lòng chọn ĐVT!' });
                $row.find('.select-issue-unit').focus();
                return;
            }

            // Sync to hidden inputs
            $form.find('.hidden-import-id').val(importId);
            $form.find('.hidden-issued-amount').val(issuedAmount);
            $form.find('.hidden-issued-unit').val(issuedUnit);
            $form.find('.hidden-return-standard').val(returnStandard);

            // Thời điểm cấp phát do server tự ghi bằng now(), không gửi từ trình duyệt

            // Confirm
            Swal.fire({
                title: 'Xác nhận cấp phát?',
                text: "Dữ liệu sẽ được cập nhật ngay lập tức.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
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
                                Toast.fire({ icon: 'success', title: res.message });
                                
                                let data = res.data;
                                
                                // Format number
                                let numFmt = parseFloat(data.issued_amount).toString();
                                
                                // 1. Mã Ống Chuẩn
                                let codeHtml = '<span class="exp-code font-weight-bold">' + data.import_code + '</span>';
                                if (data.batch_no) codeHtml += '<small class="text-muted d-block">Lô: ' + data.batch_no + '</small>';
                                if (data.location) codeHtml += '<span class="std-location-tag mt-1">' + data.location + '</span>';
                                $row.find('td:nth-child(2)').html(codeHtml);

                                // 2. SL Cấp Phát (cột số 8)
                                $row.find('td:nth-child(8)').html('<span class="font-weight-bold text-success">' + numFmt + '</span>');
                                
                                // 3. ĐVT (cột số 9)
                                $row.find('td:nth-child(9)').html('<span class="font-weight-500">' + data.issued_unit + '</span>');

                                // 4. Trả Chuẩn (cột số 10)
                                let returnHtml = data.return_standard 
                                    ? '<span class="badge badge-warning" title="Trả lại chuẩn sau khi dùng"><i class="fas fa-undo-alt mr-1"></i> Trả lại</span>' 
                                    : '<span class="text-muted">—</span>';
                                $row.find('td:nth-child(10)').html(returnHtml);

                                // 5. Thao Tác (cột cuối)
                                let actionHtml = '<span class="exp-req-badge accepted">Đã cấp</span>' + 
                                                 '<small class="d-block text-muted">' + data.issued_by + '</small>' +
                                                 '<small class="d-block text-muted" style="font-size: 0.78rem;">' + data.issued_at + '</small>';
                                $row.find('td:last-child').html(actionHtml);

                            } else {
                                $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Cấp phát');
                                Toast.fire({ icon: 'error', title: res.message });
                            }
                        },
                        error: function() {
                            $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Cấp phát');
                            Toast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                        }
                    });
                }
            });
        });

        // Handle save draft issue (Lưu)
        $(document).on('click', '.btn-save-draft-issue', function(e) {
            e.preventDefault();
            let $btn = $(this);
            let listId = $btn.data('list-id');
            let $modal = $('#requestDetailModal_' + listId);
            
            let items = [];
            $modal.find('.issue-row').each(function() {
                let $row = $(this);
                // Only get pending items that have inputs
                if ($row.find('.select-issue-import').length > 0) {
                    items.push({
                        id: $row.find('.hidden-item-id').val() || $row.find('.form-direct-issue input[name="item_id"]').val(),
                        import_id: $row.find('.select-issue-import').val() || null,
                        issued_amount: $row.find('.input-issue-amount').val() || null,
                        issued_unit: $row.find('.select-issue-unit').val() || null,
                        return_standard: $row.find('.input-return-standard').is(':checked') ? 1 : 0
                    });
                }
            });

            if (items.length === 0) {
                Toast.fire({ icon: 'info', title: 'Không có mục nào để lưu!' });
                return;
            }

            $.ajax({
                url: '{{ route("pages.export.standardExport.issueDraftStore") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    request_list_id: listId,
                    items: items
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...');
                },
                success: function(res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu');
                    if (res.success) {
                        Toast.fire({ icon: 'success', title: res.message });
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    } else {
                        Toast.fire({ icon: 'error', title: res.message });
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu');
                    Toast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                }
            });
        });
    });
</script>

{{-- ============================================================================
     MODAL CHỌN ỐNG CHUẨN TỪ DANH SÁCH TỒN
     Thay ô dropdown "Mã Ống Chuẩn" bằng bảng tồn dễ nhìn, có tìm kiếm — tránh
     chọn nhầm khi một chất chuẩn có nhiều ống. Dùng chung cho mọi phiếu / mọi dòng.
     ============================================================================ --}}
@php
    $stdPickerData = ($availableImports ?? collect())
        ->groupBy('category_id')
        ->map(fn ($grp) => $grp->map(fn ($imp) => [
            'id' => $imp->id,
            'code' => $imp->code,
            'batch_no' => $imp->batch_no ?: '—',
            'remaining' => (float) $imp->remaining,
            'unit' => $imp->unit_short_name ?: '',
            'location' => $imp->location_code ?: '',
            'expired_date' => $imp->expired_date ? \Carbon\Carbon::parse($imp->expired_date)->format('d/m/Y') : '—',
            'expired' => (bool) $imp->expired,
            'selectable' => (bool) $imp->selectable,
            'reason' => $imp->waiting_internal ? 'Chưa XĐ hạn nội bộ'
                : ($imp->expired ? 'Đã hết hạn'
                : ($imp->remaining <= 0 ? 'Hết tồn' : '')),
        ])->values());
@endphp

<style>
    .std-pick-box { min-width: 190px; }
    .std-pick-display {
        padding: 6px 10px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md, 8px);
        background: var(--primary-soft);
        font-size: 0.85rem;
        line-height: 1.35;
    }
    .std-pick-display.is-empty { border-style: dashed; background: transparent; }
    #stdImportPickerModal .std-picker-row.is-current > td { background: var(--primary-soft); }
    #stdImportPickerModal table thead th {
        position: sticky;
        top: 0;
        background: var(--primary-soft);
        color: var(--primary);
        z-index: 1;
    }
</style>

<div class="modal fade" id="stdImportPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 60vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                    <i class="fas fa-flask mr-2"></i> Chọn Ống Chuẩn Cấp Phát
                    <span id="stdPickerCatLabel" class="text-muted font-weight-normal ml-1" style="font-size: 0.9rem;"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-3">
                <div class="input-group input-group-sm mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" id="stdPickerSearch" class="form-control" placeholder="Tìm mã ống, lô, vị trí...">
                </div>

                <div class="table-responsive border rounded" style="max-height: 55vh; overflow-y: auto;">
                    <table class="table table-bordered table-hover mb-0" style="font-size: 0.86rem;">
                        <thead class="text-center">
                            <tr>
                                <th style="width: 40px;">STT</th>
                                <th style="min-width: 140px;">Mã Ống Chuẩn</th>
                                <th>Lô</th>
                                <th class="text-right">Tồn</th>
                                <th>ĐVT</th>
                                <th>Vị Trí</th>
                                <th>Hạn Dùng</th>
                                <th>Trạng Thái</th>
                                <th style="width: 90px;">Chọn</th>
                            </tr>
                        </thead>
                        <tbody id="stdPickerBody"></tbody>
                    </table>
                </div>

                <p class="text-muted mt-2 mb-0" style="font-size: 0.8rem;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Chỉ ống còn hạn, còn tồn và đã xác định hạn dùng nội bộ mới cấp phát được.
                </p>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const stdPickerData = @json($stdPickerData);
        const $stdPicker = $('#stdImportPickerModal');
        const $stdPickerBody = $('#stdPickerBody');
        let $stdActiveRow = null;

        const stdNum = (v) => {
            v = parseFloat(v) || 0;
            return v.toLocaleString('en-US', { maximumFractionDigits: 4 });
        };

        function stdRenderPicker(catId, currentVal) {
            const list = stdPickerData[catId] || [];
            $stdPickerBody.empty();

            if (!list.length) {
                $stdPickerBody.append(
                    '<tr><td colspan="9" class="text-center text-muted py-4">Chưa có ống chuẩn còn tồn cho chất chuẩn này.</td></tr>'
                );
                return;
            }

            list.forEach(function (imp, i) {
                const isCurrent = String(imp.id) === String(currentVal || '');
                const statusHtml = imp.selectable
                    ? '<span class="badge badge-success">Cấp phát được</span>'
                    : '<span class="badge ' + (imp.reason.indexOf('Chưa') === 0 ? 'badge-warning' : 'badge-danger') + '">'
                        + (imp.reason || 'Không dùng được') + '</span>';

                let actionHtml;
                if (isCurrent) {
                    actionHtml = '<span class="badge badge-primary">Đang chọn</span>';
                } else if (imp.selectable) {
                    actionHtml = '<button type="button" class="btn btn-xs btn-primary btn-pick-std" data-id="' + imp.id + '">Chọn</button>';
                } else {
                    actionHtml = '<button type="button" class="btn btn-xs btn-outline-secondary" disabled>Chọn</button>';
                }

                $stdPickerBody.append(
                    '<tr class="std-picker-row' + (isCurrent ? ' is-current' : '') + '">'
                    + '<td class="text-center text-muted">' + (i + 1) + '</td>'
                    + '<td class="font-weight-bold">' + imp.code + '</td>'
                    + '<td>' + imp.batch_no + '</td>'
                    + '<td class="text-right font-weight-bold text-success">' + stdNum(imp.remaining) + '</td>'
                    + '<td class="text-center">' + (imp.unit || '—') + '</td>'
                    + '<td>' + (imp.location || '—') + '</td>'
                    + '<td class="text-center' + (imp.expired ? ' text-danger font-weight-bold' : '') + '">' + imp.expired_date + '</td>'
                    + '<td class="text-center">' + statusHtml + '</td>'
                    + '<td class="text-center">' + actionHtml + '</td>'
                    + '</tr>'
                );
            });
        }

        $(document).on('click', '.btn-open-std-picker', function () {
            const $btn = $(this);
            $stdActiveRow = $btn.closest('.issue-row');

            const name = $btn.data('standard-name') || '';
            const code = $btn.data('category-code') || '';
            $('#stdPickerCatLabel').text(name ? '— ' + name + (code ? ' (' + code + ')' : '') : '');
            $('#stdPickerSearch').val('');

            stdRenderPicker($btn.data('category-id'), $stdActiveRow.find('.select-issue-import').val());
            $stdPicker.modal('show');
        });

        $(document).on('click', '.btn-pick-std', function () {
            if (!$stdActiveRow) return;

            const impId = String($(this).data('id'));
            const $select = $stdActiveRow.find('.select-issue-import');

            if (!$select.find('option[value="' + impId + '"]').length) {
                $select.append('<option value="' + impId + '"></option>');
            }
            $select.val(impId).trigger('change');

            const catId = $stdActiveRow.find('.btn-open-std-picker').data('category-id');
            const imp = (stdPickerData[catId] || []).filter((x) => String(x.id) === impId)[0];
            if (imp) {
                $stdActiveRow.find('.std-pick-display').removeClass('is-empty').html(
                    '<span class="exp-code font-weight-bold">' + imp.code + '</span>'
                    + '<small class="text-muted d-block">Lô: ' + imp.batch_no + ' · Tồn: '
                    + stdNum(imp.remaining) + ' ' + (imp.unit || '') + '</small>'
                );
            }

            $stdPicker.modal('hide');
        });

        $('#stdPickerSearch').on('keyup', function () {
            const v = $(this).val().toLowerCase();
            $('#stdPickerBody tr.std-picker-row').each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1);
            });
        });

        // Modal chồng modal: nâng z-index để picker nổi trên phiếu chi tiết
        $stdPicker.on('show.bs.modal', function () {
            const z = 1060;
            $(this).css('z-index', z);
            setTimeout(function () {
                $('.modal-backdrop').not('.std-stacked-backdrop').last()
                    .css('z-index', z - 1).addClass('std-stacked-backdrop');
            }, 0);
        });
        $stdPicker.on('hidden.bs.modal', function () {
            if ($('.modal.show').length) {
                $('body').addClass('modal-open');
            }
            $('.std-stacked-backdrop').removeClass('std-stacked-backdrop');
        });
    });
</script>
