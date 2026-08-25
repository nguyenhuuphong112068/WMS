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
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
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
                            <label>Tên Sản Phẩm Kiểm Nghiệm</label>
                            <input type="text" list="updateProductList" name="product_name"
                                class="form-control" value="{{ old('product_name') }}"
                                placeholder="Ví dụ: Paracetamol 500mg...">
                            <datalist id="updateProductList">
                                @foreach ($productNames as $pn)
                                    <option value="{{ $pn->name }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Kiểm Nghiệm Viên Thực Hiện</label>
                            <select name="analyst_id" class="form-control">
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
                            <label>Số Lượng <span class="text-danger">*</span></label>
                            <input type="number" name="amount" step="0.0001" min="0.0001"
                                class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                value="{{ old('amount') }}" required>
                            @if ($bag->has('amount'))
                                <span class="md-error">{{ $bag->first('amount') }}</span>
                            @endif
                            <span class="exp-remaining"></span>
                        </div>

                        <div class="form-group col-md-4">
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

                        <div class="form-group col-md-4">
                            <label>Ngày Sử Dụng <span class="text-danger">*</span></label>
                            <input type="date" name="exported_date"
                                class="form-control {{ $bag->has('exported_date') ? 'is-invalid' : '' }}"
                                value="{{ old('exported_date') }}" required>
                            @if ($bag->has('exported_date'))
                                <span class="md-error">{{ $bag->first('exported_date') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
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
                        </div>

                        <div class="form-group col-md-6">
                            <label>Số PKN, OOS, BCSL...</label>
                            <input type="text" name="test_report_no" maxlength="100"
                                class="form-control {{ $bag->has('test_report_no') ? 'is-invalid' : '' }}"
                                value="{{ old('test_report_no') }}">
                            @if ($bag->has('test_report_no'))
                                <span class="md-error">{{ $bag->first('test_report_no') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mục Đích Sử Dụng</label>
                        <textarea name="purpose" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('purpose') ? 'is-invalid' : '' }}">{{ old('purpose') }}</textarea>
                        @if ($bag->has('purpose'))
                            <span class="md-error">{{ $bag->first('purpose') }}</span>
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
