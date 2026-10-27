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
                {{-- Lập mới chỉ có phiếu Sử dụng - chất chuẩn hỏng đi quy trình Cách ly ở Tồn Kho Chất Chuẩn --}}
                <input type="hidden" name="type" value="export">

                <div class="modal-body">

                    {{-- BƯỚC 1: Tổ / phiếu đề nghị / KNV của ống bấm [Sử dụng] ở tab "Sử dụng chuẩn" - không đổi trong form --}}
                    @php $createGroup = old('group_id') ? $groups->firstWhere('id', (int) old('group_id')) : null; @endphp
                    <input type="hidden" name="group_id" id="create_group_id" value="{{ old('group_id') }}">
                    <div class="form-row bg-light pt-3 pb-1 px-3 rounded mb-3 border">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold text-primary"><i class="fas fa-users mr-1"></i> Tổ Sử Dụng</label>
                            <div class="font-weight-bold text-dark" id="create_group_name">{{ $createGroup->name ?? '—' }}</div>
                            @if ($bag->has('group_id'))
                                <span class="md-error">{{ $bag->first('group_id') }}</span>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold text-primary"><i class="fas fa-file-invoice mr-1"></i> Phiếu Đề Nghị</label>
                            <div class="font-weight-bold text-dark" id="create_request_code">—</div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold text-primary"><i class="fas fa-user mr-1"></i> KNV</label>
                            <div class="text-dark" id="create_analyst_name">—</div>
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
                            <span class="text-muted font-weight-500">Chưa chọn ống chuẩn. Bấm <b>[Sử dụng]</b> trên ống chuẩn ở tab <b>Sử dụng chuẩn</b>.</span>
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
                                <input type="text" inputmode="decimal" name="amount" id="create_amount" min="0.0001"
                                    class="form-control js-decimal {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                    value="{{ old('amount') }}" placeholder="Ví dụ: 2.5" required>
                                <div class="input-group-append">
                                    <span class="input-group-text exp-unit-name bg-light">---</span>
                                </div>
                            </div>
                            {{-- Badge cảnh báo tức thời: vượt số lượng cấp phát (cùng ĐVT) / làm tồn âm --}}
                            <div id="create_amount_warnings" class="d-none mt-1">
                                <span class="amt-warn d-none" id="create_warn_issued">
                                    <i class="fas fa-exclamation-triangle mr-1"></i><span class="amt-warn__text"></span>
                                </span>
                                <span class="amt-warn is-danger d-none" id="create_warn_negative">
                                    <i class="fas fa-arrow-down mr-1"></i><span class="amt-warn__text"></span>
                                </span>
                            </div>
                            @if ($bag->has('amount'))
                                <span class="md-error d-block">{{ $bag->first('amount') }}</span>
                            @endif
                        </div>

                        {{-- 1b. Khối Lượng Bì + Chuẩn - chỉ ống kiểm soát khối lượng, lần dùng đầu tiên --}}
                        <div class="form-group col-md-3 d-none" id="col_gross_weight">
                            <label class="required font-weight-bold text-primary">
                                <i class="fas fa-balance-scale mr-1"></i> Khối Lượng Bì + Chuẩn
                            </label>
                            <div class="input-group">
                                <input type="text" inputmode="decimal" name="gross_weight_before" id="create_gross_weight"
                                    class="form-control js-decimal {{ $bag->has('gross_weight_before') ? 'is-invalid' : '' }}"
                                    value="{{ old('gross_weight_before') }}" placeholder="Cân trước khi dùng">
                                <div class="input-group-append">
                                    <span class="input-group-text exp-weight-unit bg-light">---</span>
                                </div>
                            </div>
                            @if ($bag->has('gross_weight_before'))
                                <span class="md-error d-block">{{ $bag->first('gross_weight_before') }}</span>
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
    /* Badge cảnh báo dưới ô Số Lượng Xuất */
    #create_amount_warnings {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    #create_amount_warnings.d-none {
        display: none !important;
    }
    .amt-warn {
        display: inline-flex;
        align-items: center;
        align-self: flex-start;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 700;
        line-height: 1.5;
        color: #92400E;
        background: #FEF3C7;
        border: 1px solid #FCD34D;
        animation: amt-warn-in 0.2s ease-out;
    }
    .amt-warn.is-danger {
        color: #991B1B;
        background: #FEE2E2;
        border-color: #FCA5A5;
    }
    #create_amount.is-warning {
        border-color: #F59E0B;
        box-shadow: 0 0 0 0.15rem rgba(245, 158, 11, 0.2);
    }
    #create_amount.is-danger-soft {
        border-color: #EF4444;
        box-shadow: 0 0 0 0.15rem rgba(239, 68, 68, 0.2);
    }
    @keyframes amt-warn-in {
        from { opacity: 0; transform: translateY(-3px); }
        to { opacity: 1; transform: none; }
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
            if (data.group_id) {
                $('#create_group_id').val(data.group_id);
                $('#create_group_name').text(data.group_name || '—');
            }
            $('#create_request_code').text(data.request_code || '—');
            $('#create_analyst_name').text(data.analyst_name || '—');

            // Lưu thông tin số lượng để kiểm tra cảnh báo khi submit
            $('#create_import_id')
                .data('requested-amount', data.requested_amount !== undefined ? data.requested_amount : '')
                .data('remaining', data.remaining !== undefined ? data.remaining : 0)
                .data('issued-amount', (data.issued_amount !== undefined && data.issued_amount !== null) ? data.issued_amount : '')
                .data('issued-unit', data.issued_unit || data.unit || '')
                .data('unit', data.unit || '')
                .data('weight-controlled', data.weight_controlled == 1 ? 1 : 0)
                .data('first-use', data.is_first_use == 1 ? 1 : 0);

            toggleGrossWeight();
            
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
            // Số cân luôn là khối lượng: chuẩn ĐVT ml cân theo mg (App\Support\StandardWeight)
            $('#createModal .exp-weight-unit').text(data.weight_unit || data.unit || '');
            
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
            updateAmountWarnings();
        };

        function fmtNum(n) {
            return parseFloat(n.toFixed(4)).toString();
        }

        /*
        | Badge cảnh báo tức thời dưới ô Số Lượng Xuất (không chặn lưu - lúc Lưu vẫn hỏi xác nhận):
        |  - Vượt số lượng kho đã cấp: chỉ so khi ĐVT cấp phát trùng ĐVT xuất.
        |  - Làm tồn âm: số lượng xuất > tồn còn lại của ống.
        */
        function updateAmountWarnings() {
            var $imp = $('#create_import_id');
            var raw = String($('#create_amount').val() || '').replace(',', '.');
            var amount = parseFloat(raw);
            var unit = $imp.data('unit') || '';
            var issuedRaw = $imp.data('issued-amount');
            var issued = (issuedRaw !== '' && issuedRaw !== undefined && issuedRaw !== null) ? parseFloat(issuedRaw) : null;
            var issuedUnit = String($imp.data('issued-unit') || unit).trim().toLowerCase();
            var remainingRaw = $imp.data('remaining');
            var remaining = (remainingRaw !== '' && remainingRaw !== undefined && remainingRaw !== null) ? parseFloat(remainingRaw) : null;

            var overIssued = !isNaN(amount) && amount > 0 && issued !== null && !isNaN(issued)
                && issuedUnit === String(unit).trim().toLowerCase()
                && amount - issued > 0.00005;
            var negative = !isNaN(amount) && amount > 0 && remaining !== null && !isNaN(remaining)
                && amount - remaining > 0.00005;

            $('#create_warn_issued').toggleClass('d-none', !overIssued).find('.amt-warn__text')
                .text(overIssued ? 'Vượt số lượng cấp phát (' + fmtNum(issued) + ' ' + unit + ') thêm ' + fmtNum(amount - issued) + ' ' + unit : '');
            $('#create_warn_negative').toggleClass('d-none', !negative).find('.amt-warn__text')
                .text(negative ? 'Tồn âm sau xuất: -' + fmtNum(amount - remaining) + ' ' + unit + ' (tồn còn ' + fmtNum(remaining) + ' ' + unit + ')' : '');
            $('#create_amount_warnings').toggleClass('d-none', !(overIssued || negative));
            $('#create_amount')
                .toggleClass('is-danger-soft', negative)
                .toggleClass('is-warning', overIssued && !negative);
        }

        $('#create_amount').on('input change keyup', updateAmountWarnings);

        /*
        | Ống chuẩn có kiểm soát khối lượng, ở lần SỬ DỤNG đầu tiên phải cân và ghi lại
        | khối lượng "Bì + Chuẩn".
        */
        function toggleGrossWeight() {
            var $col = $('#col_gross_weight');
            var wasVisible = !$col.hasClass('d-none');
            var need = $('#create_import_id').data('weight-controlled') == 1
                && $('#create_import_id').data('first-use') == 1;

            $col.toggleClass('d-none', !need);
            $('#create_gross_weight').prop('required', need);

            // Chỉ xoá số đã nhập khi ô đang hiện rồi bị ẩn đi, tránh mất giá trị nhập lại sau lỗi
            if (!need && wasVisible) {
                $('#create_gross_weight').val('');
            }
        }

        toggleGrossWeight();

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

            var warnings = [];

            // Cảnh báo 1: Số lượng xuất khác số lượng đề nghị
            if (requestedAmount !== null && requestedAmount > 0 && Math.abs(enteredAmount - requestedAmount) > 0.00005) {
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

        // Nút [Sử dụng] trên từng ống ở tab "Sử dụng chuẩn": mở form với Tổ + ống đã chọn sẵn
        $(document).on('click', '.btn-use-issued-std', function() {
            var data = $(this).data('std') || {};
            var $form = $('#formStdCreate');

            // Xoá số liệu và lỗi của lần mở trước (kể cả lỗi kiểm tra do server trả về)
            $form.find('#create_amount, #create_gross_weight, #create_product_name, #create_batch_no, #create_testing, #create_request_item_id').val('');
            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            window.populateStandardDisplay(data);
            $('#createModal').modal('show');
        });

        function loadIssuedStandards(groupId, selectedImportId) {
            if (!selectedImportId) return;
            $.get('{{ route("pages.export.standardExport.getIssuedStandards") }}', { group_id: groupId }, function(res) {
                var standards = res.standards || [];
                var item = standards.find(s => s.import_id == selectedImportId);
                if (item) {
                    window.populateStandardDisplay({
                        import_id: item.import_id,
                        import_code: item.import_code,
                        req_item_id: item.id,
                        std_name: item.standard_name,
                        remaining: item.actual_remaining,
                        requested_amount: item.requested_amount || item.issued_amount || '',
                        issued_amount: item.issued_amount,
                        issued_unit: item.issued_unit || item.unit_short_name,
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
                        testing: item.test_criteria || '',
                        weight_controlled: item.weight_controlled,
                        is_first_use: item.is_first_use,
                        weight_unit: item.weight_unit,
                        group_id: item.group_id,
                        group_name: item.group_name,
                        request_code: item.request_code,
                        analyst_name: item.analyst_name
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
