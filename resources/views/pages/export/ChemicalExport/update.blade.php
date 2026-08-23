@php $bag = $errors->getBag('updateErrors'); @endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $expIcon }}"></i> Cập Nhật Phiếu Sử Dụng Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Phiếu Nhập <span class="text-danger">*</span></label>
                            <select name="import_id"
                                class="form-control exp-select {{ $bag->has('import_id') ? 'is-invalid' : '' }}"
                                data-imports="{{ json_encode($expImportMap) }}" data-over="{{ $expOverRatio }}"
                                required>
                                <option value="">-- Chọn phiếu nhập --</option>
                                {{-- Giữ cả phiếu hết hạn / hết tồn để phiếu xuất cũ còn chọn lại được đúng phiếu
                                     nhập của nó; Controller chỉ chặn khi ĐỔI sang một phiếu khác. --}}
                                @foreach ($imports as $import)
                                    <option value="{{ $import->id }}"
                                        {{ old('import_id') == $import->id ? 'selected' : '' }}>
                                        {{ $import->code }} - {{ $import->chem_name }}{{ $import->batch_no ? ' - Lô ' . $import->batch_no : '' }}
                                        (còn {{ $expNum($import->remaining) }} {{ $import->unit_short_name }}@if ($import->expired), ĐÃ HẾT HẠN @elseif($import->waiting_internal), CHƯA CÓ HẠN NỘI BỘ @elseif(!$import->selectable), ĐÃ HẾT TỒN @endif)
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('import_id'))
                                <span class="md-error">{{ $bag->first('import_id') }}</span>
                            @endif
                            <small class="md-sub">Đổi sang phiếu nhập khác thì mã xuất nhập đổi theo phiếu nhập mới, và
                                phiếu mới phải còn hạn dùng, còn tồn.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Mã Xuất Nhập</label>
                            <input type="text" class="form-control exp-readonly exp-code-view" readonly
                                data-placeholder="Chọn phiếu nhập" value="">
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

                        {{-- Chỉ hiện khi chọn Chuyển kho, JS bật/tắt qua class exp-transfer-only --}}
                        <div class="form-group col-md-4 exp-transfer-only" style="display: none">
                            <label>Phòng Ban Nhận <span class="text-danger">*</span></label>
                            <select name="to_department_id"
                                class="form-control exp-select {{ $bag->has('to_department_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn phòng ban nhận --</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"
                                        {{ old('to_department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }} ({{ $department->shortName }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('to_department_id'))
                                <span class="md-error">{{ $bag->first('to_department_id') }}</span>
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
                            <label>Người Sử Dụng</label>
                            <input type="text" name="exported_by" class="form-control exp-readonly" readonly
                                value="{{ old('exported_by') }}">
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
                        </div>
                    </div>

                    {{-- Căn cứ loại bỏ, chỉ hỏi khi chọn Huỷ bỏ; JS bật/tắt qua class exp-cancel-only --}}
                    <div class="form-group exp-cancel-only" style="display: none">
                        <label>Số PKN, OOS, BCSL...</label>
                        <input type="text" name="test_report_no" maxlength="100"
                            class="form-control {{ $bag->has('test_report_no') ? 'is-invalid' : '' }}"
                            value="{{ old('test_report_no') }}" placeholder="Ví dụ: PKN-2026-0145 / OOS-08">
                        @if ($bag->has('test_report_no'))
                            <span class="md-error">{{ $bag->first('test_report_no') }}</span>
                        @endif
                        <small class="md-sub">Căn cứ loại bỏ, in vào cột cùng tên ở mục 1 của Phiếu Theo Dõi Và
                            Quyết Định Huỷ.</small>
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
                            placeholder="Ví dụ: Ghi nhầm số lượng khi cân, đã cân lại theo phiếu cân ngày 20/08">{{ old('adjust_reason') }}</textarea>
                        @if ($bag->has('adjust_reason'))
                            <span class="md-error">{{ $bag->first('adjust_reason') }}</span>
                        @endif
                        <small class="md-sub">Không lưu trên phiếu, chỉ ghi vào lịch sử điều chỉnh để giải thích lần
                            sửa này.</small>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Mỗi lần sửa được lưu thành một dòng <b>Lịch sử điều chỉnh</b> của phiếu (kèm nội dung
                        <b>Trường: cũ → mới</b>) và ghi vào Audit Trail. Xem lại bằng badge số nhỏ ở góc trên bên
                        phải nút Sửa trên bảng.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu thay đổi
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
