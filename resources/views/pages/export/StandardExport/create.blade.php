@php $bag = $errors->getBag('createErrors'); @endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 80vw; width: 80%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $expIcon }}"></i> Sử Dụng Chất Chuẩn</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'store') }}" method="POST" id="formStdCreate">
                @csrf
                <input type="hidden" name="request_item_id" id="create_request_item_id"
                    value="{{ old('request_item_id') }}">

                <div class="modal-body">

                    {{-- BƯỚC 1: Chọn Loại Phiếu, Tổ & Nút Chọn Chuẩn --}}
                    <div class="form-row bg-light pt-3 pb-2 px-3 rounded mb-3 border align-items-center">
                        <div class="form-group col-md-3">
                            <label class="required font-weight-bold text-primary">Loại Phiếu</label>
                            <div class="exp-types mt-1">
                                @foreach ($types as $value => $label)
                                    <label class="exp-type {{ old('type', 'export') == $value ? 'is-checked' : '' }}">
                                        <input type="radio" name="type" class="radio-exp-type"
                                            value="{{ $value }}"
                                            {{ old('type', 'export') == $value ? 'checked' : '' }}>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($bag->has('type'))
                                <span class="md-error">{{ $bag->first('type') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label class="required font-weight-bold text-primary"><i class="fas fa-users mr-1"></i> Tổ Sử Dụng</label>
                            <select name="group_id" id="create_group_id"
                                class="form-control font-weight-bold {{ $bag->has('group_id') ? 'is-invalid' : '' }}"
                                required>
                                <option value="">-- Chọn Tổ của bạn --</option>
                                @foreach ($groups as $g)
                                    <option value="{{ $g->id }}"
                                        {{ old('group_id') == $g->id ? 'selected' : '' }}>
                                        {{ $g->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('group_id'))
                                <span class="md-error">{{ $bag->first('group_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-5 pt-4" id="btn-group-pick-standard">
                            <!-- Nút hiển thị khi Loại = Sử dụng -->
                            <button type="button" class="btn btn-outline-primary shadow-sm font-weight-bold w-100"
                                id="btnOpenIssuedPicker" style="height: 38px;">
                                <i class="fas fa-list-check mr-1"></i> Chọn ống chuẩn từ <b>Danh sách đã cấp phát</b>
                            </button>

                            <!-- Nút hiển thị khi Loại = Loại bỏ -->
                            <button type="button" class="btn btn-outline-danger shadow-sm font-weight-bold w-100 d-none"
                                id="btnOpenInventoryPicker" data-toggle="modal"
                                data-target="#inventoryImportPickerModal" style="height: 38px;">
                                <i class="fas fa-boxes mr-1"></i> Chọn ống chuẩn từ <b>Tồn kho của phòng (để loại bỏ)</b>
                            </button>
                        </div>
                    </div>

                    {{-- Thông tin chuẩn đã chọn --}}
                    <div class="std-selected-card border rounded mb-3 bg-white shadow-sm position-relative overflow-hidden">
                        <input type="hidden" name="import_id" id="create_import_id" value="{{ old('import_id') }}" required>
                        @if ($bag->has('import_id'))
                            <span class="md-error d-block p-2 mb-0 border-bottom">{{ $bag->first('import_id') }}</span>
                        @endif

                        <div id="create_import_placeholder" class="p-4 bg-light text-center">
                            <i class="fas fa-flask text-muted fa-2x mb-2 d-block opacity-50"></i>
                            <span class="text-muted font-weight-500">Chưa chọn ống chuẩn. Vui lòng bấm nút <b>"Chọn ống chuẩn từ Danh sách đã cấp phát"</b> ở trên.</span>
                        </div>
                        
                        <div id="create_import_display" class="d-none p-3">
                            <!-- Header: Standard Name & Return Badge -->
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom bg-light mx-n3 mt-n3 px-3 py-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-primary mr-2 px-2 py-1 font-weight-bold"><i class="fas fa-flask mr-1"></i> THÔNG TIN CHUẨN</span>
                                    <span class="font-weight-bold text-dark text-uppercase" id="display_std_title" style="font-size: 1.05rem; letter-spacing: 0.5px;"></span>
                                </div>
                                <div id="badge_return_container">
                                    <span id="badge_return_standard" class="badge badge-warning text-dark border border-warning px-2 py-1 font-weight-bold d-none" title="Chất chuẩn này có yêu cầu trả về sau khi sử dụng">
                                        <i class="fas fa-undo-alt mr-1"></i> Có Yêu Cầu Trả Về
                                    </span>
                                </div>
                            </div>

                            <!-- 3 Columns Detail Grid -->
                            <div class="row">
                                <!-- Cột 1 -->
                                <div class="col-md-4 border-right">
                                    <div class="std-detail-item">
                                        <span class="std-label">Mã Ống:</span>
                                        <span class="std-value font-weight-bold text-primary" id="display_import_code"></span>
                                    </div>
                                    <div class="std-detail-item">
                                        <span class="std-label">Mã Chuẩn:</span>
                                        <span class="std-value font-weight-600" id="display_category_code"></span>
                                    </div>
                                    <div class="std-detail-item">
                                        <span class="std-label">Tên Chuẩn:</span>
                                        <span class="std-value font-weight-600 text-dark" id="display_std_name2"></span>
                                    </div>
                                    <div class="std-detail-item">
                                        <span class="std-label">Nguồn Gốc:</span>
                                        <span class="std-value" id="display_supplier"></span>
                                    </div>
                                </div>

                                <!-- Cột 2 -->
                                <div class="col-md-4 border-right">
                                    <div class="std-detail-item">
                                        <span class="std-label">Qui Cách:</span>
                                        <span class="std-value" id="display_spec"></span>
                                    </div>
                                    <div class="std-detail-item">
                                        <span class="std-label">Số Lô Chuẩn:</span>
                                        <span class="std-value font-weight-600" id="display_batch"></span>
                                    </div>
                                    <div class="std-detail-item">
                                        <span class="std-label">Hạn Sử Dụng:</span>
                                        <span class="std-value" id="display_expired_wrapper">
                                            <span id="display_expired" class="font-weight-600"></span>
                                            <span id="display_expired_check_online" class="badge badge-warning font-weight-bold px-2 py-1 d-none" title="Hạn dùng chưa xác định từ NSX. Tra cứu trực tuyến khi sử dụng.">
                                                <i class="fas fa-globe mr-1"></i> Check online
                                            </span>
                                        </span>
                                    </div>
                                    <div class="std-detail-item">
                                        <span class="std-label">Hàm Lượng:</span>
                                        <span class="std-value font-weight-600 text-dark" id="display_potency"></span>
                                    </div>
                                </div>

                                <!-- Cột 3 -->
                                <div class="col-md-4">
                                    <div class="std-detail-item">
                                        <span class="std-label">Ẩm:</span>
                                        <span class="std-value font-weight-600" id="display_moisture"></span>
                                    </div>
                                    <div class="std-detail-item">
                                        <span class="std-label">Khác:</span>
                                        <span class="std-value" id="display_other"></span>
                                    </div>
                                    <div class="std-detail-item">
                                        <span class="std-label">Hồ Sơ Chuẩn:</span>
                                        <span class="std-value" id="display_attachment_wrapper">
                                            <div class="dropdown d-inline-block" id="dropdown_attachments">
                                                <button class="btn btn-xs btn-outline-primary dropdown-toggle font-weight-bold shadow-sm" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Xem danh sách file đính kèm">
                                                    <i class="fas fa-paperclip"></i> (<span id="count_attachments">0</span>)
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right shadow-lg py-1 border-0" id="list_attachments" style="z-index: 1070; min-width: 220px;"></div>
                                            </div>
                                            <span id="no_attachment_text" class="text-muted d-none">—</span>
                                        </span>
                                    </div>
                                    <div class="std-detail-item">
                                        <span class="std-label">Tồn Kho:</span>
                                        <span class="std-value font-weight-bold text-success" style="font-size: 0.95rem;">
                                            <span class="exp-remaining"></span> <span class="exp-unit-name"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row" id="row_export_inputs">
                        {{-- 1. Số Lượng Xuất --}}
                        <div class="form-group col-md-3" id="col_export_amount">
                            <label class="required font-weight-bold">Số Lượng Xuất</label>
                            <div class="input-group">
                                <input type="number" name="amount" id="create_amount" step="0.0001" min="0.0001"
                                    class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                    value="{{ old('amount') }}" placeholder="Ví dụ: 2.5" required>
                                <div class="input-group-append">
                                    <span class="input-group-text exp-unit-name bg-light">---</span>
                                </div>
                            </div>
                            @if ($bag->has('amount'))
                                <span class="md-error d-block">{{ $bag->first('amount') }}</span>
                            @endif
                        </div>

                        {{-- 2. Tên Sản Phẩm --}}
                        <div class="form-group col-md-3 exp-usage-col">
                            <label>Tên Sản Phẩm</label>
                            <input type="text" list="createProductList" name="product_name" id="create_product_name"
                                class="form-control {{ $bag->has('product_name') ? 'is-invalid' : '' }}" value="{{ old('product_name') }}"
                                placeholder="Ví dụ: Paracetamol 500mg...">
                            <datalist id="createProductList">
                                @foreach ($productNames as $pn)
                                    <option value="{{ $pn->name ?? $pn }}">
                                @endforeach
                            </datalist>
                            @if ($bag->has('product_name'))
                                <span class="md-error">{{ $bag->first('product_name') }}</span>
                            @endif
                        </div>

                        {{-- 3. Số Lô --}}
                        <div class="form-group col-md-3 exp-usage-col">
                            <label>Số Lô</label>
                            <input type="text" name="batch_no" id="create_batch_no"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}" placeholder="Ví dụ: Lô SP 010226...">
                            @if ($bag->has('batch_no'))
                                <span class="md-error">{{ $bag->first('batch_no') }}</span>
                            @endif
                        </div>

                        {{-- 4. Chỉ Tiêu --}}
                        <div class="form-group col-md-3 exp-usage-col">
                            <label>Chỉ Tiêu</label>
                            <input type="text" name="testing" id="create_testing"
                                class="form-control {{ $bag->has('testing') ? 'is-invalid' : '' }}"
                                value="{{ old('testing') }}" placeholder="Ví dụ: Độ hoà tan, Định lượng...">
                            @if ($bag->has('testing'))
                                <span class="md-error">{{ $bag->first('testing') }}</span>
                            @endif
                        </div>

                        {{-- Lý do loại bỏ (khi Loại = Loại bỏ) --}}
                        <div class="form-group col-md-9 exp-cancel-col d-none" id="group_cancel_reason">
                            <label class="required font-weight-bold text-danger">Lý Do Loại Bỏ</label>
                            <input type="text" name="reason" id="create_reason" maxlength="500"
                                class="form-control {{ $bag->has('reason') ? 'is-invalid' : '' }}"
                                placeholder="Ví dụ: Hết hạn sử dụng, hỏng bao bì, tạp chất, do OOS, BCSL..." value="{{ old('reason') }}">
                            @if ($bag->has('reason'))
                                <span class="md-error">{{ $bag->first('reason') }}</span>
                            @endif
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .std-selected-card {
        border-color: #dbe6f2 !important;
    }
    .std-detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px dashed #e2e8f0;
        font-size: 0.88rem;
    }
    .std-detail-item:last-child {
        border-bottom: none;
    }
    .std-label {
        color: #64748b;
        font-weight: 500;
        min-width: 105px;
        flex-shrink: 0;
    }
    .std-value {
        color: #1e293b;
        text-align: right;
        word-break: break-word;
        font-weight: 500;
    }
    .font-weight-600 {
        font-weight: 600 !important;
    }
    .btn-xs {
        padding: 0.15rem 0.45rem;
        font-size: 0.8rem;
        line-height: 1.5;
        border-radius: 0.25rem;
    }
</style>

{{-- Modal quét camera dùng chung - đặt ngoài #createModal để không lồng modal trong modal --}}
@include('pages.shared.cameraScan')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var downloadBaseUrl = "{{ route('pages.import.standardImport.downloadAttachment', ['id' => '___ID___']) }}";

        function formatStdDate(str) {
            if (!str || str === '—') return '—';
            if (str.indexOf('-') > -1) {
                var parts = str.substring(0, 10).split('-');
                if (parts.length === 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
            }
            return str;
        }

        window.populateStandardDisplay = function(data) {
            $('#create_import_id').val(data.import_id);
            if (data.req_item_id) {
                $('#create_request_item_id').val(data.req_item_id);
            }

            // Lưu thông tin số lượng để kiểm tra cảnh báo khi submit
            $('#create_import_id')
                .data('requested-amount', data.requested_amount !== undefined ? data.requested_amount : '')
                .data('remaining', data.remaining !== undefined ? data.remaining : 0)
                .data('unit', data.unit || '');
            
            $('#display_std_title').text(data.std_name ? data.std_name.toUpperCase() : '—');
            $('#display_import_code').text(data.import_code || '—');
            $('#display_category_code').text(data.category_code || '—');
            $('#display_std_name2').text(data.std_name || '—');
            $('#display_supplier').text(data.supplier || '—');
            $('#display_spec').text(data.spec || '—');
            $('#display_batch').text(data.batch || '—');
            
            // Hạn sử dụng: check online hoặc ngày cố định
            if (data.expiry_type === 'check online' || data.expiry_type === 'undetermined' || data.expiry_type === 'unlimited') {
                $('#display_expired').addClass('d-none').text('');
                $('#display_expired_check_online').removeClass('d-none');
            } else {
                $('#display_expired').removeClass('d-none').text(formatStdDate(data.expired));
                $('#display_expired_check_online').addClass('d-none');
            }
            
            $('#display_potency').text(data.potency || '—');
            $('#display_moisture').text(data.moisture || '—');
            $('#display_other').text(data.other || '—');
            
            // Yêu cầu trả về
            if (data.return_standard == 1 || data.return_standard === true || data.return_standard === '1') {
                $('#badge_return_standard').removeClass('d-none');
            } else {
                $('#badge_return_standard').addClass('d-none');
            }

            // Hồ sơ chuẩn (Attachments dropdown)
            var attachments = data.attachments;
            if (typeof attachments === 'string') {
                try { attachments = JSON.parse(attachments); } catch(e) { attachments = []; }
            }
            if (!Array.isArray(attachments)) {
                attachments = [];
            }
            if (attachments.length === 0 && data.attachment_id) {
                attachments = [{ id: data.attachment_id, file_name: 'File đính kèm' }];
            }

            if (attachments.length > 0) {
                $('#count_attachments').text(attachments.length);
                var attHtml = '';
                attachments.forEach(function(att) {
                    var url = downloadBaseUrl.replace('___ID___', att.id);
                    attHtml += `<a class="dropdown-item small py-2 text-dark font-weight-500 border-bottom" href="${url}" target="_blank" title="Mở file trực tiếp">
                        <i class="fas fa-external-link-alt mr-2 text-primary"></i> ${att.file_name}
                    </a>`;
                });
                $('#list_attachments').html(attHtml);
                $('#dropdown_attachments').removeClass('d-none');
                $('#no_attachment_text').addClass('d-none');
            } else {
                $('#dropdown_attachments').addClass('d-none');
                $('#no_attachment_text').removeClass('d-none');
            }

            $('#createModal .exp-remaining').text(data.remaining !== undefined ? data.remaining : '—');
            $('#createModal .exp-unit-name').text(data.unit || '');
            
            $('#create_import_placeholder').addClass('d-none');
            $('#create_import_display').removeClass('d-none');

            if (data.product_name && !$('#create_product_name').val()) {
                $('#create_product_name').val(data.product_name);
            }
            if (data.testing && !$('#create_testing').val()) {
                $('#create_testing').val(data.testing);
            }

            // Reset trạng thái xác nhận Swal
            $('#createModal form').data('swal-confirmed', false);
        };

        // Toggle buttons and fields based on type
        $('.radio-exp-type').on('change', function() {
            let type = $(this).val();
            if (type === 'export') {
                $('#btnOpenIssuedPicker').removeClass('d-none');
                $('#btnOpenInventoryPicker').addClass('d-none');
                $('.exp-usage-col').removeClass('d-none');
                $('.exp-cancel-col').addClass('d-none');
            } else if (type === 'cancel') {
                $('#btnOpenIssuedPicker').addClass('d-none');
                $('#btnOpenInventoryPicker').removeClass('d-none');
                $('.exp-usage-col').addClass('d-none');
                $('.exp-cancel-col').removeClass('d-none');
            } else {
                $('#btnOpenIssuedPicker').addClass('d-none');
                $('#btnOpenInventoryPicker').removeClass('d-none');
                $('.exp-usage-col').removeClass('d-none');
                $('.exp-cancel-col').addClass('d-none');
            }
        });
        $('.radio-exp-type:checked').trigger('change');

        // Reset trạng thái confirmed khi thay đổi số lượng hoặc ống chuẩn
        $('#create_amount, #create_import_id').on('input change', function() {
            $('#createModal form').data('swal-confirmed', false);
        });

        // Xử lý cảnh báo Swal khi submit form
        $('#createModal form').on('submit', function(e) {
            var $form = $(this);
            if ($form.data('swal-confirmed')) {
                return true;
            }

            var enteredAmount = parseFloat($('#create_amount').val()) || 0;
            var rawReq = $('#create_import_id').data('requested-amount');
            var requestedAmount = (rawReq !== '' && rawReq !== undefined && rawReq !== null) ? parseFloat(rawReq) : null;
            var remaining = parseFloat($('#create_import_id').data('remaining')) || 0;
            var unit = $('#create_import_id').data('unit') || $form.find('.exp-unit-name').first().text() || '';
            var type = $form.find('.radio-exp-type:checked').val();

            var warnings = [];

            // Cảnh báo 1: Số lượng xuất khác số lượng đề nghị (chỉ áp dụng cho loại sử dụng)
            if (type === 'export' && requestedAmount !== null && requestedAmount > 0 && Math.abs(enteredAmount - requestedAmount) > 0.00005) {
                warnings.push(`Số lượng xuất (<b>${enteredAmount} ${unit}</b>) khác với số lượng đề nghị (<b>${requestedAmount} ${unit}</b>).`);
            }

            // Cảnh báo 2: Số lượng xuất làm tồn âm
            if (enteredAmount > remaining) {
                var diff = (enteredAmount - remaining).toFixed(4).replace(/\.?0+$/, '');
                warnings.push(`Số lượng xuất (<b>${enteredAmount} ${unit}</b>) vượt quá tồn còn lại (<b>${remaining} ${unit}</b>). Tồn kho sau xuất sẽ bị âm (<b>-${diff} ${unit}</b>).`);
            }

            if (warnings.length > 0) {
                e.preventDefault();

                var listHtml = '<ul class="text-left mb-3 pl-3" style="color: #b91c1c; font-size: 0.95rem;">' +
                    warnings.map(function(w) { return '<li class="mb-1">' + w + '</li>'; }).join('') +
                    '</ul>';

                Swal.fire({
                    title: 'Cảnh Báo Số Lượng!',
                    html: `
                        <div class="text-left">
                            <p class="text-muted mb-2 font-weight-bold">Phát hiện thông tin cần xác nhận:</p>
                            ${listHtml}
                            <div class="alert alert-warning mb-0 p-2 small">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Bạn có chắc chắn muốn tiếp tục lưu phiếu sử dụng này?
                            </div>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-check mr-1"></i> Tiếp tục lưu',
                    cancelButtonText: '<i class="fas fa-arrow-left mr-1"></i> Kiểm tra lại',
                    reverseButtons: true
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $form.data('swal-confirmed', true);
                        $form.submit();
                    }
                });

                return false;
            }
        });

        // Handle opening Issued Picker
        $('#btnOpenIssuedPicker').on('click', function() {
            var groupId = $('#create_group_id').val();
            if (!groupId) {
                alert('Vui lòng chọn Tổ Sử Dụng trước khi chọn chuẩn.');
                return;
            }

            var $tbody = $('#issuedStandardPickerTable tbody');
            $tbody.html(
                '<tr><td colspan="15" class="text-center"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải...</td></tr>'
                );
            $('#issuedStandardPickerModal').modal('show');

            $.get('{{ route('pages.export.standardExport.getIssuedStandards') }}', {
                group_id: groupId
            }, function(res) {
                var standards = res.standards || [];
                if (standards.length === 0) {
                    $tbody.html(
                        '<tr><td colspan="15" class="text-center text-danger">Tổ của bạn chưa có ống chuẩn nào được cấp phát, hoặc tất cả đã được lập phiếu sử dụng.</td></tr>'
                        );
                    return;
                }

                var html = '';
                standards.forEach(function(item, index) {
                    let returnBadge = (item.return_standard == 1 || item.return_standard === true) ?
                        '<span class="badge badge-warning text-dark border border-warning font-weight-bold" title="Trả lại chuẩn sau khi dùng"><i class="fas fa-undo-alt mr-1"></i> Có</span>' :
                        '<span class="text-muted">—</span>';

                    let criteriaHtml = '';
                    if (item.criteria_names) {
                        try {
                            let criteria = JSON.parse(item.criteria_names);
                            criteriaHtml = Array.isArray(criteria) ? criteria.join(', ') : criteria;
                        } catch (e) {
                            criteriaHtml = item.criteria_names;
                        }
                    }

                    let expiredDisplay = '—';
                    if (item.expiry_type === 'check online' || item.expiry_type === 'undetermined' || item.expiry_type === 'unlimited') {
                        expiredDisplay = '<span class="badge badge-warning font-weight-bold"><i class="fas fa-globe mr-1"></i> Check online</span>';
                    } else if (item.import_expired_date) {
                        expiredDisplay = formatStdDate(item.import_expired_date);
                    }

                    html += `<tr>
                        <td class="text-center">${index + 1}</td>
                        <td>
                            <div class="exp-code font-weight-bold">${item.import_code}</div>
                            ${item.batch_no ? '<small class="text-muted d-block">Lô: ' + item.batch_no + '</small>' : ''}
                            ${item.location_name ? '<span class="std-location-tag mt-1">' + item.location_name + '</span>' : ''}
                        </td>
                        <td><b>${item.standard_name}</b> <span class="badge badge-dark">v${item.category_version}</span></td>
                        <td>${item.specification || '—'}</td>
                        <td>${item.supplier_name || '—'}</td>
                        <td>${item.purpose_name || '—'}</td>
                        <td class="text-right">${item.requested_amount || '—'}</td>
                        <td class="text-right text-success font-weight-bold">
                            ${item.issued_amount || '—'}
                            <small class="d-block text-muted">Bởi: ${item.issued_by || ''}</small>
                            <small class="d-block text-muted" style="font-size:0.75rem">${item.issued_at ? item.issued_at.substring(0,10) : ''}</small>
                        </td>
                        <td class="text-center">${item.unit_short_name || '—'}</td>
                        <td class="text-center">${returnBadge}</td>
                        <td>${item.product_name || '—'}</td>
                        <td>${criteriaHtml || '—'}</td>
                        <td>${item.analyst_name || '—'}</td>
                        <td>${item.note || '—'}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-primary btn-select-issued-std" 
                                data-import-id="${item.import_id}"
                                data-import-code="${item.import_code}"
                                data-req-item-id="${item.id}"
                                data-product="${item.product_name || ''}"
                                data-testing="${criteriaHtml || ''}"
                                data-std-name="${item.standard_name}"
                                data-remaining="${item.actual_remaining}"
                                data-requested-amount="${item.requested_amount || item.issued_amount || ''}"
                                data-unit="${item.unit_short_name}"
                                data-category-code="${item.category_code || ''}"
                                data-supplier="${item.supplier_name || ''}"
                                data-spec="${item.specification || ''}"
                                data-batch="${item.batch_no || ''}"
                                data-potency="${item.potency || ''}"
                                data-moisture="${item.moisture || ''}"
                                data-other="${item.standard_form || ''}"
                                data-attachments='${JSON.stringify(item.attachments || [])}'
                                data-expiry-type="${item.expiry_type || ''}"
                                data-return-standard="${item.return_standard == 1 || item.return_standard === true ? 1 : 0}"
                                data-expired="${item.import_expired_date ? item.import_expired_date.substring(0,10) : ''}">
                                Chọn
                            </button>
                        </td>
                    </tr>`;
                });
                $tbody.html(html);
            }).fail(function() {
                $tbody.html(
                    '<tr><td colspan="15" class="text-center text-danger">Lỗi khi tải dữ liệu.</td></tr>'
                    );
            });
        });

        function loadIssuedStandards(groupId, selectedImportId) {
            if (!selectedImportId) return;
            $.get('{{ route("pages.export.standardExport.getIssuedStandards") }}', { group_id: groupId }, function(res) {
                var standards = res.standards || [];
                var item = standards.find(s => s.import_id == selectedImportId);
                if (item) {
                    let criteria = item.criteria_names || '';
                    if (criteria) {
                        try {
                            let parsed = JSON.parse(criteria);
                            criteria = Array.isArray(parsed) ? parsed.join(', ') : parsed;
                        } catch(e) {}
                    }
                    window.populateStandardDisplay({
                        import_id: item.import_id,
                        import_code: item.import_code,
                        req_item_id: item.id,
                        std_name: item.standard_name,
                        remaining: item.issued_amount,
                        requested_amount: item.requested_amount || item.issued_amount || '',
                        unit: item.unit_short_name,
                        category_code: item.category_code || '',
                        supplier: item.supplier_name || '—',
                        spec: item.specification || '—',
                        batch: item.batch_no || '—',
                        potency: item.potency || '—',
                        moisture: item.moisture || '—',
                        other: item.standard_form || '—',
                        attachments: item.attachments || [],
                        expiry_type: item.expiry_type || '',
                        expired: item.import_expired_date ? item.import_expired_date.substring(0,10) : '',
                        return_standard: item.return_standard,
                        product_name: item.product_name || '',
                        testing: criteria
                    });
                }
            });
        }

        @if (old('group_id'))
            loadIssuedStandards({{ old('group_id') }}, {{ old('import_id') ?? 'null' }});
        @endif
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
