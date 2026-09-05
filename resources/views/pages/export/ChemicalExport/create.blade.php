@php
    $bag = $errors->getBag('createErrors');
@endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 90vw;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $expIcon }}"></i> Sử Dụng Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'storeBatch') }}" method="POST" id="expCreateForm">
                @csrf
                <input type="hidden" name="mode" class="exp-mode-input" value="use">
                {{-- Kênh nhận kết quả quét mã: shared/assets.blade.php tự set giá trị field này khi
                     tra mã thành công, JS của picker.blade.php lắng nghe change để thêm dòng --}}
                <input type="hidden" name="import_id">

                <div class="modal-body">
                    @if ($bag->any())
                        <div class="alert alert-danger">
                            <b>Không lưu được, kiểm tra lại:</b>
                            <ul class="mb-0">
                                @foreach ($bag->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label>Loại Phiếu <span class="text-danger">*</span></label>
                            <div class="exp-types">
                                @foreach ($types as $value => $label)
                                    <label class="exp-type {{ old('type', 'export') == $value ? 'is-checked' : '' }}">
                                        <input type="radio" name="type" value="{{ $value }}"
                                            {{ old('type', 'export') == $value ? 'checked' : '' }}>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Căn cứ loại bỏ, chỉ hỏi khi chọn Loại bỏ; dùng chung cho cả đợt.
                             JS bật/tắt qua class exp-cancel-only (đã có sẵn ở shared/assets.blade.php) --}}
                        <div class="form-group col-md-5 exp-cancel-only" style="display: none">
                            <label>Số PKN, OOS, BCSL...</label>
                            <input type="text" name="test_report_no" maxlength="100" class="form-control"
                                placeholder="Ví dụ: PKN-2026-0145 / OOS-08">
                            <small class="md-sub">Căn cứ loại bỏ, áp dụng chung cho các hoá chất chọn bên dưới.</small>
                        </div>
                    </div>

                    {{-- Quét mã vạch trên nhãn lô (máy đọc mã rời, camera, hoặc gõ tay mã) để thêm nhanh 1 dòng --}}
                    <div class="exp-scan scan-box">
                        <label><i class="fas fa-barcode mr-1"></i> Quét Mã Xuất Nhập</label>
                        <div class="exp-scan-row">
                            <input type="text" class="form-control exp-scan-input" autocomplete="off"
                                data-url="{{ route($expRoute . 'lookup') }}"
                                placeholder="Đưa máy quét vào mã vạch trên nhãn, hoặc gõ mã rồi nhấn Enter">
                            <button type="button" class="btn btn-outline-primary btn-camera-scan" title="Quét bằng camera">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button type="button" class="btn btn-primary btn-exp-scan">
                                <i class="fas fa-search mr-1"></i> Tra mã
                            </button>
                        </div>
                        <div class="exp-scan-result"></div>
                    </div>

                    <div class="md-toolbar">
                        <button type="button" class="btn btn-primary btn-open-exp-picker">
                            <i class="fas fa-boxes-stacked mr-1"></i> Tồn Kho Của Phòng
                        </button>
                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            Chọn 1 hoặc nhiều hoá chất từ tồn kho của phòng, mỗi dòng tự nhập Số Lượng riêng.
                        </p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm md-table mb-0" id="expRowsTable" style="display: none">
                            <thead>
                                <tr>
                                    <th>Hoá Chất</th>
                                    <th style="width: 130px">Số Lượng <span class="text-danger">*</span></th>
                                    <th style="width: 180px">Người Kiểm Tra</th>
                                    <th>Mục Đích / Lý Do</th>
                                    <th style="width: 50px"></th>
                                </tr>
                            </thead>
                            <tbody id="expRowsBody"></tbody>
                        </table>
                    </div>
                    <div class="text-center md-empty py-4" id="expRowsEmpty">
                        Chưa chọn hoá chất nào. Bấm <b>Tồn Kho Của Phòng</b> hoặc quét mã ở trên để thêm.
                    </div>

                    <div class="md-hint mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ngày sử dụng luôn là ngày bấm Lưu, người sử dụng luôn là người đang đăng nhập
                        (<b>{{ session('user')['fullName'] }}</b>). Được xuất vượt tồn còn lại tối đa
                        <b>{{ $overIssuePercent }}%</b> để bù sai số cân đong; phần vượt làm tồn bị âm, xử lý bằng
                        nút <b>Cân Đối</b> ở màn hình Tồn Kho Hoá Chất.
                    </div>

                    <div class="md-hint exp-cancel-only" style="display: none">
                        <i class="fas fa-dumpster-fire mr-1"></i>
                        Đây mới là <b>bước 1 - Loại bỏ</b>: hoá chất bị đánh dấu loại bỏ và trừ tồn ngay, nhưng
                        <b>chưa huỷ</b>. Phiếu sẽ nằm ở tab <b>Hoá chất chờ huỷ</b> để gom lại xin quyết định huỷ
                        một lần từ TP. ĐBCL và Ban Giám Đốc (bước 2). Loại bỏ luôn trừ kho ngay, không lưu tạm được.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="button" class="btn btn-outline-primary exp-submit" data-mode="save" id="expBtnSaveDraft">
                        <i class="fas fa-clock mr-1"></i> Lưu Tạm
                    </button>
                    <button type="button" class="btn btn-primary exp-submit" data-mode="use">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal quét camera dùng chung - đặt ngoài #createModal để không lồng modal trong modal --}}
@include('pages.shared.cameraScan')

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $modal = $('#createModal');

        function esc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function trimNum(value) {
            return String(Number(value || 0).toFixed(4)).replace(/\.?0+$/, '');
        }

        function checkerOptionsHtml() {
            var html = '<option value="">-- Chọn người kiểm tra --</option>';

            (window.expCheckerOptions || []).forEach(function(checker) {
                html += '<option value="' + esc(checker.value) + '">' + esc(checker.label) + '</option>';
            });

            return html;
        }

        function syncRowsVisibility() {
            var hasRows = $('#expRowsBody tr').length > 0;
            $('#expRowsTable').toggle(hasRows);
            $('#expRowsEmpty').toggle(!hasRows);
        }

        // Thêm 1 dòng vào bảng - gọi từ picker.blade.php khi bấm "Thêm Vào Danh Sách"
        // hoặc khi quét/tra mã thành công. Đã có sẵn thì bỏ qua, không thêm trùng.
        window.expAddRow = function(importId) {
            importId = String(importId);

            if ($modal.find('#expRowsBody tr[data-import-id="' + importId + '"]').length) {
                return;
            }

            var data = (window.expImportData || {})[importId];

            if (!data) {
                return;
            }

            var $row = $(
                '<tr data-import-id="' + importId + '">' +
                '<td>' +
                '<div class="font-weight-bold">' + esc(data.chem_name || '—') + '</div>' +
                '<div class="md-sub"><span class="md-tag">' + esc(data.category_code || '—') + '</span>' +
                (data.batch_no ? ' Lô ' + esc(data.batch_no) : '') + '</div>' +
                '<div class="md-sub">' + esc(data.code) + ' · còn ' + trimNum(data.remaining) + ' ' + esc(data.unit || '') +
                (data.expired_date ? ' · HSD ' + esc(data.expired_date) : '') + '</div>' +
                '</td>' +
                '<td><input type="number" step="0.0001" min="0.0001" max="' + trimNum(data.max_issue) +
                '" name="items[' + importId + '][amount]" class="form-control form-control-sm" placeholder="Số lượng" required></td>' +
                '<td><select name="items[' + importId + '][checked_by]" class="form-control form-control-sm">' +
                checkerOptionsHtml() + '</select></td>' +
                '<td><input type="text" name="items[' + importId + '][purpose]" maxlength="500" class="form-control form-control-sm" placeholder="Mục đích / lý do"></td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger exp-row-remove"><i class="fas fa-times"></i></button></td>' +
                '</tr>'
            );

            $('#expRowsBody').append($row);
            syncRowsVisibility();
        };

        $(document).on('click', '#expRowsBody .exp-row-remove', function() {
            $(this).closest('tr').remove();
            syncRowsVisibility();
        });

        // Lưu Tạm chỉ áp dụng cho loại Sử dụng - Loại bỏ luôn trừ kho ngay
        function syncTypeUi() {
            var type = $modal.find('.exp-type input:checked').val();
            $('#expBtnSaveDraft').toggle(type === 'export');
        }

        $(document).on('change', '#createModal .exp-type input', syncTypeUi);

        // Mở modal Thêm mới thì dọn sạch bảng dòng + trả nút Lưu về mặc định "Sử dụng ngay"
        $(document).on('click', '.btn-md-create', function() {
            $('#expRowsBody').empty();
            syncRowsVisibility();
            $modal.find('.exp-mode-input').val('use');
            syncTypeUi();
        });

        syncTypeUi();
        syncRowsVisibility();

        $modal.find('.exp-submit').on('click', function() {
            if (!$('#expRowsBody tr').length) {
                alert('Vui lòng chọn ít nhất một hoá chất từ tồn kho của phòng.');
                return;
            }

            $modal.find('.exp-mode-input').val($(this).data('mode'));

            var form = document.getElementById('expCreateForm');

            if (form.reportValidity()) {
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    });
</script>
