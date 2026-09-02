@php $bag = $errors->getBag('updateErrors'); @endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $impIcon }}"></i> Điều Chỉnh Phiếu Nhập Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($impRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Hoá Chất <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-control imp-select {{ $bag->has('category_id') ? 'is-invalid' : '' }}" required>
                                <option value="">-- Chọn hoá chất phòng đang dùng --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->code }} - {{ $category->chem_name }}{{ $category->unit_short_name ? ' (' . $category->unit_short_name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('category_id'))
                                <span class="md-error">{{ $bag->first('category_id') }}</span>
                            @endif
                            <small class="md-sub">Chỉ đổi được sang hoá chất phòng đã khai ở tab <b>Hoá Chất Của Phòng</b>. Đổi sang hoá chất khác thì mã xuất nhập sẽ được cấp lại theo hoá chất mới.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Số Lượng <span class="text-danger">*</span></label>
                            <input type="number" name="amount" step="0.0001" min="0.0001"
                                class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                value="{{ old('amount') }}" required>
                            @if ($bag->has('amount'))
                                <span class="md-error">{{ $bag->first('amount') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số Lô</label>
                            <input type="text" name="batch_no" maxlength="100"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}">
                            @if ($bag->has('batch_no'))
                                <span class="md-error">{{ $bag->first('batch_no') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Nhà Cung Cấp</label>
                            <select name="supplier_id" class="form-control imp-select {{ $bag->has('supplier_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn nhà cung cấp --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @if ($bag->has('supplier_id'))
                                <span class="md-error">{{ $bag->first('supplier_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Người Nhập</label>
                            <input type="text" name="imported_by" class="form-control imp-readonly" readonly
                                value="{{ old('imported_by') }}">
                            <small class="md-sub">Giữ nguyên người đã nhập phiếu, không sửa được.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        {{-- Vị trí lưu trữ: chọn cấp sâu nhất, ba cấp Kho/Phòng/Kệ suy ra từ đó --}}
                        <div class="form-group col-md-12">
                            <label>Vị Trí Lưu Trữ</label>
                            <select name="location_id"
                                class="form-control imp-select {{ $bag->has('location_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chưa xếp vị trí --</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->warehouse_name ?: '—' }} /
                                        {{ $location->room_name ?: '—' }} /
                                        {{ $location->shelf_name ?: '—' }} /
                                        {{ $location->code }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('location_id'))
                                <span class="md-error">{{ $bag->first('location_id') }}</span>
                            @endif
                            <small class="md-sub">Chuyển hàng sang chỗ khác thì đổi tại đây, màn hình Tồn Kho cập
                                nhật theo.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Ngày Nhập</label>
                            <input type="text" class="form-control imp-readonly imp-up-imported-date" readonly>
                            <small class="md-sub">Ngày ghi nhận lúc lập phiếu, không sửa được.</small>
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
                            <label>Phân Loại</label>
                            <label class="imp-switch {{ old('is_microbiological_chemicals') ? 'is-checked' : '' }}">
                                <input type="checkbox" name="is_microbiological_chemicals" value="1"
                                    {{ old('is_microbiological_chemicals') ? 'checked' : '' }}>
                                <span>Hoá chất vi sinh</span>
                            </label>
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
                            <label>Ngày Hoá Đơn</label>
                            <input type="date" name="invoice_date"
                                class="form-control {{ $bag->has('invoice_date') ? 'is-invalid' : '' }}"
                                value="{{ old('invoice_date') }}">
                            @if ($bag->has('invoice_date'))
                                <span class="md-error">{{ $bag->first('invoice_date') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="form-group imp-reason">
                        <label>Lý Do Điều Chỉnh <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Ghi sai số lô theo phiếu giao hàng, chỉnh lại cho khớp chứng từ.">{{ old('reason') }}</textarea>
                        @if ($bag->has('reason'))
                            <span class="md-error">{{ $bag->first('reason') }}</span>
                        @endif
                        <small class="md-sub">Bắt buộc nhập, được lưu vào lịch sử điều chỉnh của phiếu.</small>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Mỗi lần điều chỉnh đều lưu một dòng <b>lịch sử</b> gồm nội dung đã đổi, lý do, người thực
                        hiện và thời điểm - xem bằng nút <i class="fas fa-clock-rotate-left"></i> trên bảng. Bản ghi
                        lịch sử không sửa và không xoá; ghi sai thì điều chỉnh lại lần nữa.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu điều chỉnh
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Ngày nhập chỉ để xem (d/m/Y), không có name nên không gửi lên server
    $(document).on('click', '.btn-md-edit', function() {
        var row = $(this).data('row') || {};
        var parts = String(row.imported_date || '').substring(0, 10).split('-');
        $('#updateModal .imp-up-imported-date')
            .val(parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : '—');
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
