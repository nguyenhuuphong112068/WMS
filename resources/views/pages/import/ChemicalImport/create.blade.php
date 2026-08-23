@php $bag = $errors->getBag('createErrors'); @endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $impIcon }}"></i> Nhập Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($impRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Hoá Chất <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-control imp-select {{ $bag->has('category_id') ? 'is-invalid' : '' }}" required>
                                <option value="">-- Chọn hoá chất --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->code }} - {{ $category->chem_name }}{{ $category->unit_short_name ? ' (' . $category->unit_short_name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('category_id'))
                                <span class="md-error">{{ $bag->first('category_id') }}</span>
                            @endif
                            <small class="md-sub">Chỉ hiện hoá chất trong Danh Mục đã được duyệt và đang hoạt động.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Mã Xuất Nhập</label>
                            <input type="text" class="form-control imp-readonly imp-code-preview" readonly
                                data-codes="{{ json_encode($codePreviews) }}"
                                data-placeholder="Chọn hoá chất"
                                value="{{ old('category_id') && isset($codePreviews[old('category_id')]) ? $codePreviews[old('category_id')] : 'Chọn hoá chất' }}">
                            <small class="md-sub">Sinh tự động: mã phòng ban + mã hoá chất + số thứ tự.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số Lượng <span class="text-danger">*</span></label>
                            <input type="number" name="amount" step="0.0001" min="0.0001"
                                class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                value="{{ old('amount') }}" placeholder="Ví dụ: 25.5" required>
                            @if ($bag->has('amount'))
                                <span class="md-error">{{ $bag->first('amount') }}</span>
                            @endif
                            <small class="md-sub">Theo đơn vị gốc của hoá chất trong Danh Mục.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Số Lô</label>
                            <input type="text" name="batch_no" maxlength="100"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}" placeholder="Ví dụ: LOT-2026-018">
                            @if ($bag->has('batch_no'))
                                <span class="md-error">{{ $bag->first('batch_no') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Nhà Cung Cấp</label>
                            <select name="supplier_id" class="form-control imp-select {{ $bag->has('supplier_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn nhà cung cấp --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('supplier_id'))
                                <span class="md-error">{{ $bag->first('supplier_id') }}</span>
                            @endif
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
                                        {{ $location->name }} ({{ $location->code }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('location_id'))
                                <span class="md-error">{{ $bag->first('location_id') }}</span>
                            @endif
                            <small class="md-sub">Dạng Kho / Phòng / Kệ / Vị trí. Để trống thì mã này hiện
                                "Chưa xếp vị trí" ở màn hình Tồn Kho.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Ngày Nhập <span class="text-danger">*</span></label>
                            <input type="date" name="imported_date"
                                class="form-control {{ $bag->has('imported_date') ? 'is-invalid' : '' }}"
                                value="{{ old('imported_date', now()->format('Y-m-d')) }}" required>
                            @if ($bag->has('imported_date'))
                                <span class="md-error">{{ $bag->first('imported_date') }}</span>
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
                            <label>Người Nhập</label>
                            <input type="text" class="form-control imp-readonly" readonly
                                value="{{ session('user')['fullName'] }}">
                            <small class="md-sub">Luôn là người đang đăng nhập, không sửa được.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số Hoá Đơn</label>
                            <input type="text" name="invoice_number" maxlength="100"
                                class="form-control {{ $bag->has('invoice_number') ? 'is-invalid' : '' }}"
                                value="{{ old('invoice_number') }}" placeholder="Ví dụ: HD-000125">
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

                        <div class="form-group col-md-4">
                            <label>Phân Loại</label>
                            <label class="imp-switch {{ old('is_microbiological_chemicals') ? 'is-checked' : '' }}">
                                <input type="checkbox" name="is_microbiological_chemicals" value="1"
                                    {{ old('is_microbiological_chemicals') ? 'checked' : '' }}>
                                <span>Hoá chất vi sinh</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Nhập bổ sung cho đợt kiểm nghiệm tháng 9">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Phiếu được ghi cho phòng ban <b>{{ session('user')['selected_department'] }}</b>. Mã xuất nhập chính
                        thức sinh lúc bấm Lưu, có thể khác mã xem trước nếu có người nhập cùng lúc.
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

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
