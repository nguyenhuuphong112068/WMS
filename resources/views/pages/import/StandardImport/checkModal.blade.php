{{--
| Modal XÁC NHẬN KIỂM TRA một ống chuẩn đang "Chờ kiểm tra".
|
| Mở bằng nút .btn-imp-check ở tab "Chờ kiểm tra" (dataTable.blade.php), dữ liệu dòng
| truyền qua data-row. Đây là bước duy nhất đưa ống ra khỏi khu Biệt Trữ: bổ sung thông
| tin lần nhập đầu còn thiếu + định khu vị trí lưu trữ thật, sau đó ống mới được cộng
| vào tồn kho và mới được đề nghị / sử dụng.
--}}

@php $bag = $errors->getBag('checkErrors'); @endphp

<div class="modal fade md-modal" id="checkModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 900px;">
        <div class="modal-content">
            <form method="POST" action="{{ route($impRoute . 'confirmCheck') }}">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-check mr-2"></i>Xác nhận kiểm tra ống chuẩn</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">

                    <div class="imp-receive-info">
                        <div>
                            <label>Mã ống chuẩn</label>
                            <div class="val sc-code">—</div>
                        </div>
                        <div>
                            <label>Chất chuẩn</label>
                            <div class="val sc-standard">—</div>
                        </div>
                        <div>
                            <label>Số lượng</label>
                            <div class="val sc-amount">—</div>
                        </div>
                        <div>
                            <label>Ngày nhập</label>
                            <div class="val sc-imported-date">—</div>
                        </div>
                        <div>
                            <label>Vị trí biệt trữ</label>
                            <div class="val sc-location">—</div>
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
                        <div class="form-group col-md-3">
                            <label>Số Lô</label>
                            <input type="text" name="batch_no" maxlength="100"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}">
                            @if ($bag->has('batch_no'))
                                <span class="md-error">{{ $bag->first('batch_no') }}</span>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label>Số COA</label>
                            <input type="text" name="coa_no" maxlength="100"
                                class="form-control {{ $bag->has('coa_no') ? 'is-invalid' : '' }}"
                                value="{{ old('coa_no') }}">
                            @if ($bag->has('coa_no'))
                                <span class="md-error">{{ $bag->first('coa_no') }}</span>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label>Hàm Lượng</label>
                            <input type="text" name="potency" maxlength="100"
                                class="form-control {{ $bag->has('potency') ? 'is-invalid' : '' }}"
                                value="{{ old('potency') }}">
                            @if ($bag->has('potency'))
                                <span class="md-error">{{ $bag->first('potency') }}</span>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label>Độ Ẩm</label>
                            <input type="text" name="moisture" maxlength="100"
                                class="form-control {{ $bag->has('moisture') ? 'is-invalid' : '' }}"
                                value="{{ old('moisture') }}">
                            @if ($bag->has('moisture'))
                                <span class="md-error">{{ $bag->first('moisture') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số Hoá Đơn</label>
                            <input type="text" name="invoice_number" maxlength="100"
                                class="form-control {{ $bag->has('invoice_number') ? 'is-invalid' : '' }}"
                                value="{{ old('invoice_number') }}">
                            @if ($bag->has('invoice_number'))
                                <span class="md-error">{{ $bag->first('invoice_number') }}</span>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Hạn Sử Dụng</label>
                            <input type="date" name="expired_date"
                                class="form-control {{ $bag->has('expired_date') ? 'is-invalid' : '' }}"
                                value="{{ old('expired_date') }}">
                            @if ($bag->has('expired_date'))
                                <span class="md-error">{{ $bag->first('expired_date') }}</span>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Nhà Cung Cấp</label>
                            <select name="supplier_id"
                                class="form-control imp-select {{ $bag->has('supplier_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn nhà cung cấp --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @if ($bag->has('supplier_id'))
                                <span class="md-error">{{ $bag->first('supplier_id') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label>Định khu vị trí lưu trữ thật <span class="text-danger">*</span></label>
                            <select name="location_id"
                                class="form-control imp-select {{ $bag->has('location_id') ? 'is-invalid' : '' }}"
                                required>
                                <option value="">-- Chọn vị trí lưu trữ --</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->warehouse_name ?: '—' }} /
                                        {{ $location->shelf_name ?: '—' }} /
                                        {{ $location->column_name ?: '—' }} /
                                        {{ $location->tier_name ?: '—' }} /
                                        {{ $location->code }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('location_id'))
                                <span class="md-error">{{ $bag->first('location_id') }}</span>
                            @endif
                        </div>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
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
            $form.find('[name="coa_no"]').val(row.coa_no || '');
            $form.find('[name="potency"]').val(row.potency || '');
            $form.find('[name="moisture"]').val(row.moisture || '');
            $form.find('[name="invoice_number"]').val(row.invoice_number || '');
            $form.find('[name="expired_date"]').val(dateValue(row.expired_date));
            // Select2 chỉ vẽ lại khi có sự kiện change, .val() thôi là chưa đủ
            $form.find('[name="supplier_id"]').val(row.supplier_id || '').trigger('change');
            $form.find('[name="location_id"]').val(row.location_id || '').trigger('change');

            $('#checkModal .sc-code').text(row.code || '—');
            $('#checkModal .sc-standard').text(row.standard_name || '—');
            $('#checkModal .sc-amount').text(row.amount_label || '—');
            $('#checkModal .sc-imported-date').text(row.imported_date_label || '—');
            $('#checkModal .sc-location').text(row.location_label || '—');

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


        @if ($bag->any())
            $(function() {
                $('#checkModal').modal('show');
            });
        @endif
    });
</script>
