@php $bag = $errors->getBag('updateErrors'); @endphp

{{--
| Modal cập nhật một phiếu sử dụng chất chuẩn.
|
| Ô chọn ống chuẩn giữ CẢ ống không còn xuất được (hết hạn, hết tồn) để phiếu cũ vẫn
| chọn lại được đúng ống của nó; Controller chỉ xét lại điều kiện khi người dùng ĐỔI
| sang ống khác.
|
| Dữ liệu do JS đổ vào khi bấm nút Sửa (xem pages/export/shared/assets.blade.php).
--}}
<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 80vw; width: 80%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Cập Nhật Phiếu Sử Dụng Chất Chuẩn</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label class="required font-weight-bold"><i class="fas fa-users mr-1"></i> Tổ Sử Dụng</label>
                            <select name="group_id" class="form-control {{ $bag->has('group_id') ? 'is-invalid' : '' }}" required>
                                <option value="">-- Chọn Tổ --</option>
                                @foreach ($groups as $g)
                                    <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>
                                        {{ $g->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('group_id'))
                                <span class="md-error">{{ $bag->first('group_id') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Ống Chuẩn <span class="text-danger">*</span></label>
                            <select name="import_id"
                                class="form-control exp-select {{ $bag->has('import_id') ? 'is-invalid' : '' }}"
                                data-imports="{{ json_encode($expImportMap) }}" data-over="{{ $expOverRatio }}"
                                required>
                                <option value="">-- Chọn ống chuẩn --</option>
                                @foreach ($imports as $import)
                                    <option value="{{ $import->id }}"
                                        {{ old('import_id') == $import->id ? 'selected' : '' }}>
                                        {{ $import->code }} - {{ $import->standard_name }} (v{{ $import->category_version }}){{ $import->batch_no ? ' - Lô ' . $import->batch_no : '' }}
                                        (còn {{ $expNum($import->remaining) }} {{ $import->unit_short_name }}){{ $import->selectable ? '' : ' - không còn xuất được' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('import_id'))
                                <span class="md-error">{{ $bag->first('import_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Mã Ống Chuẩn</label>
                            <input type="text" class="form-control exp-readonly exp-code-view" readonly
                                data-placeholder="Chọn ống chuẩn" value="">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Số Lượng <span class="text-danger">*</span></label>
                            <input type="number" name="amount" step="0.0001" min="0.0001"
                                class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                value="{{ old('amount') }}" required>
                            @if ($bag->has('amount'))
                                <span class="md-error">{{ $bag->first('amount') }}</span>
                            @endif
                            <span class="exp-remaining"></span>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Loại Phiếu <span class="text-danger">*</span></label>
                            <div class="exp-types">
                                @foreach ($types as $value => $label)
                                    <label class="exp-type {{ old('type') == $value ? 'is-checked' : '' }}">
                                        <input type="radio" name="type" value="{{ $value }}"
                                            {{ old('type') == $value ? 'checked' : '' }}>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($bag->has('type'))
                                <span class="md-error">{{ $bag->first('type') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row exp-export-only">
                        <div class="form-group col-md-4">
                            <label>Tên Sản Phẩm</label>
                            <input type="text" list="updateProductList" name="product_name"
                                class="form-control {{ $bag->has('product_name') ? 'is-invalid' : '' }}" value="{{ old('product_name') }}"
                                placeholder="Ví dụ: Paracetamol 500mg...">
                            <datalist id="updateProductList">
                                @foreach ($productNames as $pn)
                                    <option value="{{ $pn->name ?? $pn }}">
                                @endforeach
                            </datalist>
                            @if ($bag->has('product_name'))
                                <span class="md-error">{{ $bag->first('product_name') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Số Lô</label>
                            <input type="text" name="batch_no"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}" placeholder="Ví dụ: Lô SP 010226...">
                            @if ($bag->has('batch_no'))
                                <span class="md-error">{{ $bag->first('batch_no') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Chỉ Tiêu</label>
                            <input type="text" name="testing"
                                class="form-control {{ $bag->has('testing') ? 'is-invalid' : '' }}"
                                value="{{ old('testing') }}" placeholder="Ví dụ: Độ hoà tan, Định lượng...">
                            @if ($bag->has('testing'))
                                <span class="md-error">{{ $bag->first('testing') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Lý do loại bỏ (khi Loại = Loại bỏ) --}}
                    <div class="form-group exp-cancel-only d-none">
                        <label class="required font-weight-bold text-danger">Lý Do Loại Bỏ</label>
                        <textarea name="reason" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Hết hạn sử dụng, hỏng bao bì, tạp chất, do OOS, BCSL...">{{ old('reason') }}</textarea>
                        @if ($bag->has('reason'))
                            <span class="md-error">{{ $bag->first('reason') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Lý Do Điều Chỉnh</label>
                        <textarea name="adjust_reason" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('adjust_reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Ghi nhầm số lượng, chỉnh lại theo sổ cân">{{ old('adjust_reason') }}</textarea>
                        @if ($bag->has('adjust_reason'))
                            <span class="md-error">{{ $bag->first('adjust_reason') }}</span>
                        @endif
                        <small class="md-sub">Lý do được ghi lên đầu dòng lịch sử điều chỉnh của phiếu.</small>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Không đổi gì mà vẫn bấm Lưu thì hệ thống không ghi lịch sử, tránh rác. Mỗi lần đổi đều
                        chụp lại giá trị phiếu ngay sau khi sửa, xem ở badge số nhỏ góc trên bên phải nút Sửa.
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
            $('#updateModal').modal('show');
        });
    </script>
@endif
