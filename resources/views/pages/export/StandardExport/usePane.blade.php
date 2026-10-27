{{--
| SỬ DỤNG CHẤT CHUẨN - TAB "SỬ DỤNG CHUẨN"
|
| Ống chuẩn kho đã cấp phát cho các Tổ (đề nghị cấp phát chuẩn nội bộ) mà chưa lập phiếu sử
| dụng - StandardExportController::issuedStandards(). Mỗi dòng một nút [Sử dụng] mở form Sử
| Dụng Chất Chuẩn (create.blade.php) điền sẵn Tổ + ống chuẩn; lập phiếu xong ống rời danh sách.
| Link thẻ "Chuẩn đã cấp cho bạn" ở Trang chủ: ?tab=use&use_item=<id dòng đề nghị> - cuộn tới và tô sáng ống đó.
--}}

@php
    $useMe = (int) (session('user')['userId'] ?? 0);
    $useGroups = $issuedStandards->pluck('group_name', 'group_id')->filter()->sort();
@endphp

<style>
    .use-std-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }

    .use-std-filter .form-control {
        height: 38px;
    }

    #useStdTable tr.is-target > td {
        background: rgba(var(--state-success-rgb), 0.12);
        transition: background 0.3s;
    }

    .use-std-me {
        display: inline-block;
        margin-left: 4px;
        padding: 0 6px;
        border-radius: 999px;
        background: rgba(var(--state-success-rgb), 0.12);
        color: var(--state-success);
        font-size: 0.72rem;
        font-weight: 700;
    }
</style>

@tab('export_standard', 'use')
<div class="exp-pane {{ $activeTab === 'use' ? 'is-active' : '' }}" id="expPaneUse">

    <div class="use-std-filter">
        <select id="useStdGroup" class="form-control" style="max-width: 240px;">
            <option value="">-- Tất cả tổ --</option>
            @foreach ($useGroups as $groupId => $groupName)
                <option value="{{ $groupId }}">{{ $groupName }}</option>
            @endforeach
        </select>
        <div class="input-group" style="max-width: 460px;">
            <div class="input-group-prepend">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
            </div>
            <input type="text" id="useStdSearch" class="form-control" placeholder="Tìm tên chất chuẩn, mã ống, lô, KNV...">
        </div>
    </div>

    <div class="table-responsive">
        <table id="useStdTable" class="table table-bordered table-hover w-100 exp-req-table">
            <thead>
                <tr class="text-center">
                    <th style="width: 50px">STT</th>
                    <th style="min-width: 160px">Mã Ống Chuẩn</th>
                    <th style="min-width: 190px">Chất Chuẩn</th>
                    <th style="min-width: 130px">Tổ / Phiếu</th>
                    <th style="min-width: 80px">Qui Cách</th>
                    <th style="min-width: 130px">Nhà Sản Xuất</th>
                    <th style="min-width: 110px">Mục Đích</th>
                    <th style="min-width: 80px">SL Đề Nghị</th>
                    <th style="min-width: 150px">SL Đã Cấp</th>
                    <th style="min-width: 60px">ĐVT</th>
                    <th style="min-width: 80px">Trả Chuẩn</th>
                    <th style="min-width: 130px">Sản Phẩm</th>
                    <th style="min-width: 110px">Chỉ Tiêu</th>
                    <th style="min-width: 150px">KNV</th>
                    <th style="min-width: 100px">Ghi Chú</th>
                    <th style="min-width: 190px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issuedStandards as $item)
                    @php
                        // Dữ liệu đổ vào form Sử Dụng (window.populateStandardDisplay ở create.blade.php)
                        $usePayload = [
                            'import_id' => $item->import_id,
                            'import_code' => $item->import_code,
                            'req_item_id' => $item->id,
                            'std_name' => $item->standard_name,
                            'remaining' => (float) $item->actual_remaining,
                            'requested_amount' => (float) ($item->requested_amount ?: $item->issued_amount),
                            // Số lượng kho đã cấp - badge cảnh báo khi số lượng xuất vượt (chỉ so khi cùng ĐVT)
                            'issued_amount' => $item->issued_amount !== null ? (float) $item->issued_amount : null,
                            'issued_unit' => $item->issued_unit ?: $item->unit_short_name,
                            'unit' => $item->unit_short_name,
                            'category_code' => $item->category_code,
                            'supplier' => $item->supplier_name ?: '—',
                            'spec' => $item->specification ?: '—',
                            'batch' => $item->batch_no ?: '—',
                            'potency' => $item->potency,
                            'moisture' => $item->moisture,
                            'other' => $item->standard_form,
                            'attachments' => $item->attachments,
                            'expiry_type' => $item->expiry_type,
                            'expired' => $item->import_expired_date ? substr($item->import_expired_date, 0, 10) : '',
                            'return_standard' => (int) $item->return_standard,
                            'product_name' => $item->product_name,
                            'testing' => $item->test_criteria,
                            'weight_controlled' => (int) $item->weight_controlled,
                            'is_first_use' => $item->is_first_use ? 1 : 0,
                            'weight_unit' => $item->weight_unit ?: $item->unit_short_name,
                            'group_id' => $item->group_id,
                            'group_name' => $item->group_name,
                            'request_code' => $item->request_code,
                            'analyst_name' => $item->analyst_name,
                        ];
                    @endphp
                    <tr class="use-std-row" data-group="{{ $item->group_id }}" data-item-id="{{ $item->id }}">
                        <td class="text-center align-middle use-std-no">{{ $loop->iteration }}</td>
                        <td class="align-middle">
                            <span class="exp-code font-weight-bold">{{ $item->import_code }}</span>
                            @if ($item->batch_no)
                                <small class="text-muted d-block">Lô: {{ $item->batch_no }}</small>
                            @endif
                            @if ($item->location_code)
                                <div class="mt-1">{{ \App\Support\ZoneType::tag($item->location_code, $item->location_zone_type) }}</div>
                            @endif
                        </td>
                        <td class="align-middle">
                            <span class="font-weight-bold text-dark">{{ $item->standard_name ?: '—' }}</span>
                            @if ($item->category_code)
                                <span class="badge badge-secondary ml-1">{{ $item->category_code }}</span>
                            @endif
                        </td>
                        <td class="align-middle">
                            <span class="font-weight-bold text-primary">{{ $item->group_name ?: '—' }}</span>
                            <small class="text-muted d-block">{{ $item->request_code }}</small>
                        </td>
                        <td class="align-middle text-center">{{ $item->specification ?: '—' }}</td>
                        <td class="align-middle">{{ $item->supplier_name ?: '—' }}</td>
                        <td class="align-middle">{{ $item->purpose_name ?: '—' }}</td>
                        <td class="align-middle text-right">{{ $item->requested_amount !== null ? $expNum($item->requested_amount) : '—' }}</td>
                        <td class="align-middle text-right">
                            <span class="font-weight-bold text-success">{{ $expNum($item->issued_amount) }}</span>
                            <small class="d-block text-muted">Bởi: {{ $item->issued_by ?: '—' }}</small>
                            <small class="d-block text-muted" style="font-size: 0.75rem;">{{ $expDateTime($item->issued_at) }}</small>
                        </td>
                        <td class="align-middle text-center">{{ $item->issued_unit ?: ($item->unit_short_name ?: '—') }}</td>
                        <td class="align-middle text-center">
                            @if ($item->return_standard)
                                <span class="badge badge-warning" title="Trả lại chuẩn sau khi dùng"><i class="fas fa-undo-alt mr-1"></i> Có</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="align-middle">{{ $item->product_name ?: '—' }}</td>
                        <td class="align-middle">{{ $item->test_criteria ?: '—' }}</td>
                        <td class="align-middle">
                            {{ $item->analyst_name ?: '—' }}
                            @if ($useMe && (int) $item->analyst_id === $useMe)
                                <span class="use-std-me">Bạn</span>
                            @endif
                        </td>
                        <td class="align-middle text-muted">{{ $item->note ?: '—' }}</td>
                        <td class="align-middle text-center" style="white-space: nowrap;">
                            @perm('export_standard_issue')
                                <button type="button" class="btn btn-sm btn-primary btn-use-issued-std px-2 py-1 shadow-sm"
                                    data-std="{{ json_encode($usePayload) }}" title="Lập phiếu sử dụng ống chuẩn này">
                                    <i class="fas fa-vial mr-1"></i> Sử dụng
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-unuse-issued-std px-2 py-1 shadow-sm ml-1"
                                    data-item-id="{{ $item->id }}"
                                    data-code="{{ $item->import_code }}"
                                    data-name="{{ $item->standard_name }}"
                                    title="Xác nhận không sử dụng chuẩn đã cấp phát">
                                    <i class="fas fa-ban mr-1"></i> Không sử dụng
                                </button>
                            @endperm
                        </td>
                    </tr>
                @empty
                    <tr class="use-std-empty">
                        <td colspan="16" class="text-center text-muted py-4">Không có ống chuẩn nào đã cấp phát đang chờ sử dụng.</td>
                    </tr>
                @endforelse
                @if ($issuedStandards->isNotEmpty())
                    <tr class="use-std-none d-none">
                        <td colspan="16" class="text-center text-muted py-4">Không có ống chuẩn nào khớp bộ lọc.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endtab

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var Toast = (window.Swal && Swal.mixin) ? Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        }) : null;

        // Lọc theo Tổ + ô tìm kiếm, đánh lại số thứ tự các dòng còn hiện
        function filterUseStd() {
            var group = String($('#useStdGroup').val() || '');
            var keyword = ($('#useStdSearch').val() || '').toLowerCase();
            var shown = 0;

            $('#useStdTable tbody tr.use-std-row').each(function() {
                var $row = $(this);
                var hit = (!group || String($row.data('group')) === group)
                    && (!keyword || $row.text().toLowerCase().indexOf(keyword) > -1);

                $row.toggle(hit);
                if (hit) {
                    $row.find('.use-std-no').text(++shown);
                }
            });

            $('#useStdTable tbody tr.use-std-none').toggleClass('d-none', shown > 0 || $('#useStdTable tbody tr.use-std-row').length === 0);
        }

        $('#useStdGroup').on('change', filterUseStd);
        $('#useStdSearch').on('keyup', filterUseStd);

        // Xử lý nút "Không sử dụng" ống chuẩn đã cấp phát
        $(document).on('click', '.btn-unuse-issued-std', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('tr.use-std-row');
            var itemId = $btn.data('item-id');
            var code = $btn.data('code') || '';
            var name = $btn.data('name') || '';

            if (!window.Swal) {
                if (confirm('Xác nhận không sử dụng ống chuẩn ' + code + '?')) {
                    executeUnuse('');
                }
                return;
            }

            Swal.fire({
                title: 'Không sử dụng ống chuẩn?',
                html: 'Bạn có chắc chắn xác nhận <b>không sử dụng</b> ống chuẩn <b>' + (code || 'này') + '</b>'
                    + (name ? ' (' + name + ')' : '') + ' đã được cấp phát không?<br>'
                    + '<small class="text-muted">Ống chuẩn sẽ rời khỏi danh sách chờ sử dụng và cập nhật trạng thái phiếu đề nghị.</small>',
                icon: 'warning',
                input: 'textarea',
                inputPlaceholder: 'Nhập lý do không sử dụng (không bắt buộc)...',
                inputAttributes: {
                    maxlength: 500,
                    rows: 3
                },
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-ban mr-1"></i> Xác nhận',
                cancelButtonText: 'Đóng'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                executeUnuse(result.value || '');
            });

            function executeUnuse(note) {
                $.ajax({
                    url: '{{ route("pages.export.standardExport.requestUnuse") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        item_id: itemId,
                        note: note
                    },
                    beforeSend: function() {
                        $row.find('button').prop('disabled', true);
                    },
                    success: function(res) {
                        if (!res.success) {
                            $row.find('button').prop('disabled', false);
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Không thể thực hiện',
                                    text: res.message || 'Có lỗi xảy ra, vui lòng thử lại!'
                                });
                            } else {
                                alert(res.message || 'Có lỗi xảy ra!');
                            }
                            return;
                        }

                        if (Toast) {
                            Toast.fire({
                                icon: 'success',
                                title: res.message || 'Đã xác nhận không sử dụng chuẩn!'
                            });
                        } else if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: res.message || 'Đã xác nhận không sử dụng chuẩn!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }

                        // Xoá dòng khỏi bảng, đánh lại STT và cập nhật badge count
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            filterUseStd();

                            // Cập nhật số đếm trên tab "Sử dụng chuẩn"
                            var remainingRows = $('#useStdTable tbody tr.use-std-row').length;
                            var $tabBtn = $('button.exp-tab[data-pane="expPaneUse"]');
                            var $badge = $tabBtn.find('.exp-tab-count');
                            if (remainingRows > 0) {
                                if ($badge.length) {
                                    $badge.text(remainingRows);
                                } else {
                                    $tabBtn.append('<span class="exp-tab-count">' + remainingRows + '</span>');
                                }
                            } else {
                                $badge.remove();
                                if ($('#useStdTable tbody tr.use-std-empty').length === 0) {
                                    $('#useStdTable tbody').append(
                                        '<tr class="use-std-empty"><td colspan="16" class="text-center text-muted py-4">Không có ống chuẩn nào đã cấp phát đang chờ sử dụng.</td></tr>'
                                    );
                                }
                            }
                        });
                    },
                    error: function(xhr) {
                        $row.find('button').prop('disabled', false);
                        var errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Có lỗi xảy ra khi gửi yêu cầu!';
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: errMsg
                            });
                        } else {
                            alert(errMsg);
                        }
                    }
                });
            }
        });

        // Mở từ Trang chủ (?use_item=): cuộn tới và tô sáng đúng ống, rồi bỏ tham số khỏi URL
        var params = new URLSearchParams(window.location.search);
        var target = params.get('use_item');

        if (target) {
            params.delete('use_item');
            window.history.replaceState(null, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));

            var $target = $('#useStdTable tr.use-std-row[data-item-id="' + target + '"]');

            if ($target.length) {
                $target.addClass('is-target');
                setTimeout(function() {
                    $target[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            } else if (window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Không thấy ống chuẩn',
                    text: 'Ống chuẩn này đã được lập phiếu sử dụng hoặc không thuộc phòng ban đang chọn.'
                });
            }
        }
    });
</script>
