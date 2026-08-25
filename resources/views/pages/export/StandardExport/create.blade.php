@php $bag = $errors->getBag('createErrors'); @endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $expIcon }}"></i> Sử Dụng Chất Chuẩn</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'store') }}" method="POST" id="formStdCreate">
                @csrf
                <input type="hidden" name="request_item_id" id="create_request_item_id" value="{{ old('request_item_id') }}">

                <div class="modal-body">

                    {{-- Chọn Tổ sử dụng - Chuẩn phải được cấp phát cho Tổ mới được sử dụng --}}
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label class="required font-weight-bold text-primary"><i class="fas fa-users mr-1"></i> Tổ Sử Dụng</label>
                            <select name="group_id" id="create_group_id" class="form-control font-weight-bold {{ $bag->has('group_id') ? 'is-invalid' : '' }}" required>
                                <option value="">-- Chọn Tổ của bạn để tải danh sách chuẩn đã cấp phát --</option>
                                @foreach ($groups as $g)
                                    <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>
                                        {{ $g->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('group_id'))
                                <span class="md-error">{{ $bag->first('group_id') }}</span>
                            @endif
                            <small class="md-sub">Nhân viên chỉ được sử dụng các chất chuẩn đã được <b>Quản lý kho cấp phát</b> cho Tổ của mình.</small>
                        </div>
                    </div>

                    {{-- Quét mã vạch trên nhãn ống chuẩn (máy đọc mã rời, camera, hoặc gõ tay mã) để chọn nhanh ống --}}
                    <div class="exp-scan scan-box">
                        <label><i class="fas fa-barcode mr-1"></i> Quét Mã Ống Chuẩn</label>
                        <div class="exp-scan-row">
                            <input type="text" class="form-control exp-scan-input" autocomplete="off"
                                data-url="{{ route($expRoute . 'lookup') }}"
                                placeholder="Đưa máy quét vào mã vạch trên nhãn ống, hoặc gõ mã rồi nhấn Enter">
                            <button type="button" class="btn btn-outline-primary btn-camera-scan"
                                title="Quét bằng camera">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button type="button" class="btn btn-primary btn-exp-scan">
                                <i class="fas fa-search mr-1"></i> Tra mã
                            </button>
                        </div>
                        <div class="exp-scan-result"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="required font-weight-bold">Ống Chuẩn Đã Cấp Phát</label>
                            <select name="import_id" id="create_import_id"
                                class="form-control exp-select {{ $bag->has('import_id') ? 'is-invalid' : '' }}"
                                data-imports="{{ json_encode($expImportMap) }}" data-over="{{ $expOverRatio }}"
                                required>
                                <option value="">-- Vui lòng chọn Tổ trước --</option>
                                @foreach ($imports->where('selectable', true) as $import)
                                    <option value="{{ $import->id }}" class="opt-all-import"
                                        {{ old('import_id') == $import->id ? 'selected' : '' }}>
                                        {{ $import->code }} - {{ $import->standard_name }} (v{{ $import->category_version }}){{ $import->batch_no ? ' - Lô ' . $import->batch_no : '' }}
                                        (còn {{ $expNum($import->remaining) }} {{ $import->unit_short_name }}{{ $import->expired_date ? ', HSD ' . \Carbon\Carbon::parse($import->expired_date)->format('d/m/Y') : '' }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('import_id'))
                                <span class="md-error">{{ $bag->first('import_id') }}</span>
                            @endif
                            <small class="md-sub" id="create_import_hint">
                                Danh sách các ống chuẩn đã cấp phát và còn khả dụng cho tổ được chọn.
                            </small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Mã Ống Chuẩn</label>
                            <input type="text" class="form-control exp-readonly exp-code-view" readonly
                                data-placeholder="Chọn ống chuẩn" value="Chọn ống chuẩn">
                            <small class="md-sub">Lấy đúng mã của ống chuẩn, không sinh mã mới.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tên Sản Phẩm Kiểm Nghiệm</label>
                            <input type="text" list="createProductList" name="product_name" id="create_product_name"
                                class="form-control" value="{{ old('product_name') }}"
                                placeholder="Ví dụ: Paracetamol 500mg...">
                            <datalist id="createProductList">
                                @foreach ($productNames as $pn)
                                    <option value="{{ $pn->name }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Kiểm Nghiệm Viên Thực Hiện</label>
                            <select name="analyst_id" id="create_analyst_id" class="form-control">
                                <option value="">-- Chọn kiểm nghiệm viên --</option>
                                @foreach ($analysts as $an)
                                    <option value="{{ $an->id }}" {{ old('analyst_id') == $an->id ? 'selected' : '' }}>
                                        {{ $an->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="required font-weight-bold">Số Lượng Sử Dụng</label>
                            <input type="number" name="amount" step="0.0001" min="0.0001"
                                class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                value="{{ old('amount') }}" placeholder="Ví dụ: 2.5" required>
                            @if ($bag->has('amount'))
                                <span class="md-error">{{ $bag->first('amount') }}</span>
                            @endif
                            <span class="exp-remaining"></span>
                        </div>

                        <div class="form-group col-md-4">
                            <label class="required font-weight-bold">Loại Phiếu</label>
                            <div class="exp-types">
                                @foreach ($types as $value => $label)
                                    <label class="exp-type {{ old('type', 'export') == $value ? 'is-checked' : '' }}">
                                        <input type="radio" name="type" value="{{ $value }}"
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
                            <label class="required font-weight-bold">Ngày Sử Dụng</label>
                            <input type="date" name="exported_date"
                                class="form-control {{ $bag->has('exported_date') ? 'is-invalid' : '' }}"
                                value="{{ old('exported_date', now()->format('Y-m-d')) }}" required>
                            @if ($bag->has('exported_date'))
                                <span class="md-error">{{ $bag->first('exported_date') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Người Sử Dụng</label>
                            <input type="text" class="form-control exp-readonly" readonly
                                value="{{ session('user')['fullName'] }}">
                            <small class="md-sub">Ghi theo người đang đăng nhập.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Người Kiểm Tra</label>
                            <select name="checked_by"
                                class="form-control exp-select {{ $bag->has('checked_by') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn người kiểm tra --</option>
                                @foreach ($checkers as $checker)
                                    <option value="{{ $checker->fullName }}"
                                        {{ old('checked_by') == $checker->fullName ? 'selected' : '' }}>
                                        {{ $checker->fullName }} ({{ $checker->userName }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('checked_by'))
                                <span class="md-error">{{ $bag->first('checked_by') }}</span>
                            @endif
                            <small class="md-sub">Nhân viên đang hoạt động của phòng ban
                                <b>{{ session('user')['selected_department'] }}</b>.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Số PKN, OOS, BCSL...</label>
                        <input type="text" name="test_report_no" maxlength="100"
                            class="form-control {{ $bag->has('test_report_no') ? 'is-invalid' : '' }}"
                            value="{{ old('test_report_no') }}" placeholder="Ví dụ: PKN-2026-0145 / OOS-08">
                        @if ($bag->has('test_report_no'))
                            <span class="md-error">{{ $bag->first('test_report_no') }}</span>
                        @endif
                        <small class="md-sub">Phiếu <b>Sử dụng</b>: số phiếu kiểm nghiệm đã dùng ống chuẩn này -
                            đây là căn cứ truy ngược khi thẩm tra kết quả. Phiếu <b>Huỷ bỏ</b>: căn cứ loại bỏ
                            (OOS, BCSL).</small>
                    </div>

                    <div class="form-group">
                        <label>Mục Đích Sử Dụng</label>
                        <textarea name="purpose" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('purpose') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Dựng đường chuẩn định lượng Vitamin C / Huỷ do quá hạn sử dụng">{{ old('purpose') }}</textarea>
                        @if ($bag->has('purpose'))
                            <span class="md-error">{{ $bag->first('purpose') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Được xuất vượt tồn còn lại tối đa <b>{{ $overIssuePercent }}%</b> để bù sai số cân đong; phần
                        vượt làm tồn bị âm, xử lý bằng nút <b>Cân Đối</b> ở màn hình Tồn Kho Chất Chuẩn. Chọn
                        <b>Huỷ bỏ</b> khi ống chuẩn hỏng hoặc quá hạn - phần này vẫn trừ tồn nhưng được thống kê
                        riêng.
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

{{-- Modal quét camera dùng chung - đặt ngoài #createModal để không lồng modal trong modal --}}
@include('pages.shared.cameraScan')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var issuedStandardsCache = {};

        function loadIssuedStandards(groupId, selectedImportId) {
            var $select = $('#create_import_id');
            if (!groupId) {
                $select.html('<option value="">-- Vui lòng chọn Tổ trước --</option>');
                $('#create_import_hint').text('Chọn Tổ để tải danh sách chuẩn đã được cấp phát.');
                return;
            }

            $('#create_import_hint').html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải chuẩn đã cấp phát cho tổ...');

            $.get('{{ route("pages.export.standardExport.getIssuedStandards") }}', { group_id: groupId }, function(res) {
                var standards = res.standards || [];
                issuedStandardsCache[groupId] = standards;

                if (standards.length === 0) {
                    $select.html('<option value="">-- Tổ này chưa có chuẩn nào được cấp phát --</option>');
                    $('#create_import_hint').html('<span class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Tổ này chưa có chất chuẩn nào được cấp phát. Hãy sang tab "Đề nghị cấp phát chuẩn" để lập đề nghị.</span>');
                    return;
                }

                var html = '<option value="">-- Chọn ống chuẩn đã được cấp phát --</option>';
                standards.forEach(function(item) {
                    var label = item.import_code + ' - ' + item.standard_name + ' (v' + (item.category_version || 1) + ')' +
                                (item.batch_no ? ' - Lô ' + item.batch_no : '') +
                                ' [Đã cấp: ' + (item.issued_amount || '') + ' ' + (item.unit_short_name || '') + ']';
                    var isSel = (selectedImportId && selectedImportId == item.import_id) ? 'selected' : '';
                    html += '<option value="' + item.import_id + '" data-request-item="' + item.id + '" data-product="' + (item.product_name || '') + '" data-analyst="' + (item.analyst_id || '') + '" ' + isSel + '>' + label + '</option>';
                });

                $select.html(html);
                $('#create_import_hint').html('Tổ này có <b>' + standards.length + '</b> chất chuẩn đã được cấp phát.');

                if (selectedImportId) {
                    $select.trigger('change');
                }
            }).fail(function() {
                $('#create_import_hint').text('Không thể tải danh sách chuẩn của tổ.');
            });
        }

        $('#create_group_id').change(function() {
            var gId = $(this).val();
            loadIssuedStandards(gId);
        });

        $('#create_import_id').change(function() {
            var $opt = $(this).find('option:selected');
            var reqItemId = $opt.data('request-item') || '';
            var product = $opt.data('product') || '';
            var analystId = $opt.data('analyst') || '';

            $('#create_request_item_id').val(reqItemId);
            if (product && !$('#create_product_name').val()) {
                $('#create_product_name').val(product);
            }
            if (analystId && !$('#create_analyst_id').val()) {
                $('#create_analyst_id').val(analystId);
            }
        });

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
