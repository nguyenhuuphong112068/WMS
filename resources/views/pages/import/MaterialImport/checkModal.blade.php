{{--
| Modal XÁC NHẬN KIỂM TRA một lô vật tư đang "Chờ kiểm tra".
|
| Mở bằng nút .btn-imp-check ở tab "Chờ kiểm tra" (dataTable.blade.php), dữ liệu dòng
| truyền qua data-row. Đây là bước duy nhất đưa lô ra khỏi khu Biệt Trữ: bổ sung thông
| tin lần nhập đầu còn thiếu + định khu vị trí lưu trữ thật, sau đó lô mới được cộng
| vào tồn kho và mới được đề nghị / sử dụng.
--}}

@php $bag = $errors->getBag('checkErrors'); @endphp

<div class="modal fade md-modal" id="checkModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 860px;">
        <div class="modal-content">
            <form method="POST" action="{{ route($impRoute . 'confirmCheck') }}">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-check mr-2"></i>Xác nhận kiểm tra lô vật tư</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">

                    <div class="imp-receive-info">
                        <div>
                            <label>Mã xuất nhập</label>
                            <div class="val mc-code">—</div>
                        </div>
                        <div>
                            <label>Vật tư</label>
                            <div class="val mc-material">—</div>
                        </div>
                        <div>
                            <label>Số lượng</label>
                            <div class="val mc-amount">—</div>
                        </div>
                        <div>
                            <label>Ngày nhập</label>
                            <div class="val mc-imported-date">—</div>
                        </div>
                        <div>
                            <label>Vị trí biệt trữ</label>
                            <div class="val mc-location">—</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label>Kết quả kiểm tra <span class="text-danger">*</span></label>
                            <div class="imp-check-result">
                                @foreach (['passed' => 'Kiểm tra Đạt', 'failed' => 'Không đạt'] as $crValue => $crLabel)
                                    <label
                                        class="imp-check-opt is-{{ $crValue }} {{ old('check_result', 'passed') === $crValue ? 'is-active' : '' }}">
                                        <input type="radio" name="check_result" value="{{ $crValue }}"
                                            {{ old('check_result', 'passed') === $crValue ? 'checked' : '' }}>
                                        <i class="{{ \App\Support\CheckStatus::icon($crValue) }} mr-1"></i>{{ $crLabel }}
                                    </label>
                                @endforeach
                            </div>
                            @if ($bag->has('check_result'))
                                <div class="md-error text-danger small">{{ $bag->first('check_result') }}</div>
                            @endif
                            <small class="text-muted js-check-hint-failed d-none text-danger">
                                <i class="fas fa-triangle-exclamation mr-1"></i>
                                Không đạt = <b>trả hàng</b>: lô không bao giờ được nhập kho, không cộng tồn và
                                không kiểm tra lại được.
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Kết luận kiểm tra <span class="text-danger js-check-note-star d-none">*</span></label>
                        <textarea name="check_note" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('check_note') ? 'is-invalid' : '' }}"
                            placeholder="Kết luận / lý do (bắt buộc khi Không đạt)...">{{ old('check_note') }}</textarea>
                        @if ($bag->has('check_note'))
                            <div class="md-error text-danger small">{{ $bag->first('check_note') }}</div>
                        @endif
                    </div>

                    <div class="js-check-passed-only">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số lô</label>
                            <input type="text" name="batch_no" maxlength="100"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}">
                            @if ($bag->has('batch_no'))
                                <div class="md-error text-danger small">{{ $bag->first('batch_no') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Số hoá đơn</label>
                            <input type="text" name="invoice_number" maxlength="100"
                                class="form-control {{ $bag->has('invoice_number') ? 'is-invalid' : '' }}"
                                value="{{ old('invoice_number') }}">
                            @if ($bag->has('invoice_number'))
                                <div class="md-error text-danger small">{{ $bag->first('invoice_number') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày ký hoá đơn</label>
                            <input type="date" name="invoice_date"
                                class="form-control {{ $bag->has('invoice_date') ? 'is-invalid' : '' }}"
                                value="{{ old('invoice_date') }}">
                            @if ($bag->has('invoice_date'))
                                <div class="md-error text-danger small">{{ $bag->first('invoice_date') }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Hạn sử dụng</label>
                            <input type="date" name="expired_date"
                                class="form-control {{ $bag->has('expired_date') ? 'is-invalid' : '' }}"
                                value="{{ old('expired_date') }}">
                            @if ($bag->has('expired_date'))
                                <div class="md-error text-danger small">{{ $bag->first('expired_date') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-8">
                            <label>Định khu vị trí lưu trữ thật <span class="text-danger">*</span></label>
                            <select name="location_id"
                                class="form-control imp-select {{ $bag->has('location_id') ? 'is-invalid' : '' }}"
                                required>
                                <option value="">-- Chọn vị trí lưu trữ --</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}" data-zone="{{ $loc->zone_type ?? '' }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->code }} — {{ $loc->warehouse_name }} /
                                        {{ $loc->shelf_name }} / {{ $loc->column_name }} / {{ $loc->tier_name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('location_id'))
                                <div class="md-error text-danger small">{{ $bag->first('location_id') }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mục đích sử dụng</label>
                        <div class="d-flex align-items-start">
                            <button type="button" class="btn btn-outline-info mr-2 mi-open-purpose-objects" style="flex-shrink: 0;"
                                title="Chọn đối tượng để điền vào mục đích">
                                <i class="fas fa-crosshairs"></i>
                            </button>
                            <textarea name="purpose" rows="2" maxlength="500"
                                class="form-control {{ $bag->has('purpose') ? 'is-invalid' : '' }}"
                                placeholder="Mục đích sử dụng...">{{ old('purpose') }}</textarea>
                        </div>
                        @if ($bag->has('purpose'))
                            <div class="md-error text-danger small">{{ $bag->first('purpose') }}</div>
                        @endif
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success js-check-submit">
                        <i class="fas fa-clipboard-check mr-1"></i> Xác nhận Kiểm tra Đạt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /* Ngày dạng 'Y-m-d ...' của MySQL -> đúng dạng input type=date cần (Y-m-d) */
        function dateValue(value) {
            return String(value || '').substring(0, 10);
        }

        $(document).on('click', '.btn-imp-check', function() {
            var row = $(this).data('row') || {};
            var $form = $('#checkModal form');

            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            $form.find('[name="id"]').val(row.id || '');
            $form.find('[name="batch_no"]').val(row.batch_no || '');
            $form.find('[name="invoice_number"]').val(row.invoice_number || '');
            $form.find('[name="invoice_date"]').val(dateValue(row.invoice_date));
            $form.find('[name="expired_date"]').val(dateValue(row.expired_date));
            $form.find('[name="purpose"]').val(row.purpose || '');
            // Form kiểm tra không có ô vật tư - modal Đối Tượng lấy vật tư từ đây
            $form.data('categoryId', row.category_id || '');
            // Select2 chỉ vẽ lại khi có sự kiện change, .val() thôi là chưa đủ
            $form.find('[name="location_id"]').val(row.location_id || '').trigger('change');

            $('#checkModal .mc-code').text(row.code || '—');
            $('#checkModal .mc-material').text(row.material_name || '—');
            $('#checkModal .mc-amount').text(row.amount_label || '—');
            $('#checkModal .mc-imported-date').text(row.imported_date_label || '—');
            $('#checkModal .mc-location').text(row.location_label || '—');

            syncCheckResult();

            $('#checkModal').modal('show');
        });
        /* Đạt hay Không đạt quyết định: bắt buộc định khu hay bắt buộc kết luận */
        function syncCheckResult() {
            var $modal = $('#checkModal');
            var failed = $modal.find('[name="check_result"]:checked').val() === 'failed';

            $modal.find('.imp-check-opt').removeClass('is-active');
            $modal.find('[name="check_result"]:checked').closest('.imp-check-opt').addClass('is-active');

            // Hàng trả lại thì không định khu, cũng không cần bổ sung thông tin lô
            $modal.find('.js-check-passed-only').toggleClass('d-none', failed);
            $modal.find('[name="location_id"]').prop('required', ! failed);
            $modal.find('.js-check-hint-failed').toggleClass('d-none', ! failed);
            $modal.find('.js-check-note-star').toggleClass('d-none', ! failed);
            $modal.find('[name="check_note"]').prop('required', failed);

            $modal.find('.js-check-submit')
                .toggleClass('btn-success', ! failed)
                .toggleClass('btn-danger', failed)
                .html(failed
                    ? '<i class="fas fa-ban mr-1"></i> Ghi nhận Không đạt (trả hàng)'
                    : '<i class="fas fa-clipboard-check mr-1"></i> Xác nhận Kiểm tra Đạt');
        }

        $(document).on('change', '#checkModal [name="check_result"]', syncCheckResult);

        /*
        | GỬI NGẦM (AJAX) - không tải lại trang. confirmCheck() trả JSON:
        |   - lỗi validate (422): hiện ngay dưới từng ô, modal vẫn mở để sửa
        |   - thành công: gỡ dòng khỏi tab Chờ kiểm tra + giảm số đếm, thay dòng ở Sổ nhập
        |     bằng bản server vừa render (data.book_row) nếu dòng đó đang hiện ở trang này
        */
        function checkFieldError($form, name, message) {
            var $field = $form.find('[name="' + name + '"]');
            if (!$field.length) return false;

            $field.addClass('is-invalid');
            $('<div class="md-error text-danger small"></div>').text(message)
                .appendTo($field.first().closest('.form-group'));

            return true;
        }

        function checkAlert(message) {
            Swal.fire({
                title: 'Không thực hiện được',
                text: message || 'Có lỗi xảy ra, vui lòng thử lại.',
                icon: 'error',
                confirmButtonColor: '#2E7BC4',
                confirmButtonText: 'Đã hiểu'
            });
        }

        function applyCheckResult(data) {
            var id = String(data.id || '');

            // Tab Chờ kiểm tra: lô đã có kết quả thì rời khỏi danh sách
            var $pendingTable = $('#mdTableCheck');
            var $pendingRow = $pendingTable.find('tr[data-import-id="' + id + '"]');

            $pendingRow.fadeOut(250, function() {
                if ($.fn.dataTable.isDataTable($pendingTable)) {
                    var dt = $pendingTable.DataTable();

                    dt.row($pendingRow).remove().draw(false);
                    dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function(cell, i) {
                        cell.innerHTML = i + 1;
                    });
                } else {
                    $pendingRow.remove();
                }
            });

            var $count = $('.imp-tab[data-pane="impPaneCheck"] .imp-tab-count');
            var left = (parseInt($count.text(), 10) || 0) - 1;
            left > 0 ? $count.text(left) : $count.remove();

            // Sổ nhập: thay nội dung đúng dòng đó, giữ nguyên STT đang hiển thị
            var $bookTable = $('#mdTable');
            var $bookRow = $bookTable.find('tr[data-import-id="' + id + '"]');

            if ($bookRow.length && data.book_row) {
                var $fresh = $($.parseHTML($.trim(data.book_row))).filter('tr');

                $fresh.children('td').first().text($bookRow.children('td').first().text());
                $bookRow.html($fresh.html());

                if ($.fn.dataTable.isDataTable($bookTable)) {
                    $bookTable.DataTable().row($bookRow).invalidate('dom');
                }

                $bookRow.removeClass('imp-row-flash');
                void $bookRow[0].offsetWidth; // chạy lại hiệu ứng nếu dòng vừa nháy
                $bookRow.addClass('imp-row-flash');
            }
        }

        $(document).on('submit', '#checkModal form', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('.js-check-submit');
            if ($btn.prop('disabled')) return;

            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            var btnHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json'
            }).done(function(res) {
                if (!res || !res.success) {
                    checkAlert(res && res.message);
                    return;
                }

                $('#checkModal').modal('hide');
                applyCheckResult(res.data || {});

                Swal.fire({
                    title: 'Thành công!',
                    text: res.message,
                    icon: 'success',
                    timer: 1800,
                    showConfirmButton: false
                });
            }).fail(function(xhr) {
                var res = xhr.responseJSON || {};

                if (xhr.status === 422 && res.errors) {
                    var unplaced = [];

                    $.each(res.errors, function(name, messages) {
                        if (!checkFieldError($form, name, messages[0])) unplaced.push(messages[0]);
                    });

                    if (unplaced.length) checkAlert(unplaced.join('\n'));
                    $form.find('.is-invalid').first().trigger('focus');
                    return;
                }

                checkAlert(xhr.status === 419
                    ? 'Phiên làm việc đã hết hạn, vui lòng tải lại trang rồi thao tác lại.'
                    : res.message);
            }).always(function() {
                $btn.prop('disabled', false).html(btnHtml);
            });
        });


        @if ($bag->any())
            $(function() {
                $('#checkModal').modal('show');
            });
        @endif
    });
</script>
