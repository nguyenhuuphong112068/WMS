{{--
| Ô chọn người xác nhận hiệu chuẩn vật tư: chọn Phòng trước, danh sách Người (có quyền Xác Nhận
| Hiệu Chuẩn Vật Tư tại phòng đó) lọc theo phòng, chọn một hay nhiều người. Dùng ở modal Đề nghị
| HC và modal Đổi người xác nhận (calibrationModals). JS dựng danh sách người: calPickRender().
| Biến vào: $calPickOptions - MaterialCalibration::confirmerOptions().
--}}
<div class="form-group cal-picker">
    <label>Người xác nhận hiệu chuẩn <span class="text-danger">*</span></label>
    @if (count($calPickOptions))
        <div class="row">
            <div class="col-md-5 mb-2 mb-md-0">
                <select name="confirm_department_id" class="form-control cal-pick-dept" required title="Phòng của người xác nhận">
                    @foreach ($calPickOptions as $dept)
                        <option value="{{ $dept['id'] }}">{{ $dept['name'] }} ({{ count($dept['users']) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7">
                <div class="cal-pick-users"></div>
            </div>
        </div>
    @else
        <div class="text-danger small cal-pick-empty">
            <i class="fas fa-exclamation-circle mr-1"></i>Chưa có người nào được gán quyền Xác Nhận Hiệu Chuẩn Vật Tư tại một phòng (Phân Quyền → Người dùng) nên chưa gửi được.
        </div>
    @endif
</div>
