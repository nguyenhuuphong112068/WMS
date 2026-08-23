@php $bag = $errors->getBag('createErrors'); @endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $expIcon }}"></i> Sử Dụng Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Phiếu Nhập <span class="text-danger">*</span></label>
                            <select name="import_id"
                                class="form-control exp-select {{ $bag->has('import_id') ? 'is-invalid' : '' }}"
                                data-imports="{{ json_encode($expImportMap) }}" data-over="{{ $expOverRatio }}"
                                required>
                                <option value="">-- Chọn phiếu nhập --</option>
                                {{-- Chỉ phiếu còn hạn dùng, còn tồn > 0 và đã xác định hạn dùng nội bộ mới được xuất --}}
                                @foreach ($imports->where('selectable', true) as $import)
                                    <option value="{{ $import->id }}"
                                        {{ old('import_id') == $import->id ? 'selected' : '' }}>
                                        {{ $import->code }} - {{ $import->chem_name }}{{ $import->batch_no ? ' - Lô ' . $import->batch_no : '' }}
                                        (còn {{ $expNum($import->remaining) }} {{ $import->unit_short_name }}{{ $import->expired_date ? ', HSD ' . \Carbon\Carbon::parse($import->expired_date)->format('d/m/Y') : '' }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('import_id'))
                                <span class="md-error">{{ $bag->first('import_id') }}</span>
                            @endif
                            <small class="md-sub">
                                Chỉ hiện phiếu nhập còn hạn sử dụng, còn tồn > 0 và đã xác định hạn dùng nội bộ
                                ({{ $imports->where('selectable', true)->count() }}/{{ $imports->count() }} phiếu).
                                @if ($imports->where('waiting_internal', true)->count())
                                    Có {{ $imports->where('waiting_internal', true)->count() }} phiếu chưa xác định hạn
                                    dùng nội bộ nên chưa dùng được, xác định ở màn hình Tồn Kho Hoá Chất.
                                @endif
                            </small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Mã Xuất Nhập</label>
                            <input type="text" class="form-control exp-readonly exp-code-view" readonly
                                data-placeholder="Chọn phiếu nhập" value="Chọn phiếu nhập">
                            <small class="md-sub">Lấy đúng mã của phiếu nhập, không sinh mã mới.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số Lượng <span class="text-danger">*</span></label>
                            <input type="number" name="amount" step="0.0001" min="0.0001"
                                class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                value="{{ old('amount') }}" placeholder="Ví dụ: 2.5" required>
                            @if ($bag->has('amount'))
                                <span class="md-error">{{ $bag->first('amount') }}</span>
                            @endif
                            <span class="exp-remaining"></span>
                        </div>

                        <div class="form-group col-md-4">
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
                            <small class="md-sub">Phòng nhận phải vào màn hình Nhập Hoá Chất bấm Nhận thì hàng mới
                                vào kho bên đó.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Ngày Sử Dụng <span class="text-danger">*</span></label>
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
                            class="form-control {{ $bag->has('purpose') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Kiểm nghiệm mẫu nước đợt tháng 9 / Huỷ do quá hạn sử dụng">{{ old('purpose') }}</textarea>
                        @if ($bag->has('purpose'))
                            <span class="md-error">{{ $bag->first('purpose') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Được xuất vượt tồn còn lại tối đa <b>{{ $overIssuePercent }}%</b> để bù sai số cân đong; phần
                        vượt làm tồn bị âm, xử lý bằng nút <b>Cân Đối</b> ở màn hình Tồn Kho Hoá Chất. Chọn
                        <b>Huỷ bỏ</b> khi hoá chất hỏng hoặc quá hạn - phần này vẫn trừ tồn nhưng được thống kê riêng.
                    </div>

                    <div class="md-hint exp-cancel-only" style="display: none">
                        <i class="fas fa-dumpster-fire mr-1"></i>
                        Đây mới là <b>bước 1 - Loại bỏ</b>: hoá chất bị đánh dấu loại bỏ và trừ tồn ngay, nhưng
                        <b>chưa huỷ</b>. Phiếu sẽ nằm ở tab <b>Hoá chất chờ huỷ</b> để gom lại xin quyết định huỷ
                        một lần từ TP. ĐBCL và Ban Giám Đốc (bước 2).
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
