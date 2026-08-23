@php $bag = $errors->getBag('receptionErrors'); @endphp

{{--
| Modal dùng chung cho hai thao tác của bộ phận Cung Ứng: Tiếp nhận và Hoàn tất.
| Địa chỉ form, id phiếu và tiêu đề do JS đổ vào theo nút được bấm
| - xem pages/estimate/shared/assets.blade.php.
--}}
<div class="modal fade md-modal" id="receptionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-truck-ramp-box"></i>
                    <span class="est-reception-title">Tiếp Nhận Dự Trù</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route('pages.estimate.estimateReception.receive') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">

                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã Phiếu</label>
                        <input type="text" class="form-control est-readonly est-reception-code" readonly value="">
                    </div>

                    <div class="form-group">
                        <label>Người Xử Lý</label>
                        <input type="text" class="form-control est-readonly" readonly
                            value="{{ session('user')['fullName'] }}">
                        <small class="md-sub">Ghi theo người đang đăng nhập.</small>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú Cung Ứng</label>
                        <textarea name="reception_note" rows="3" maxlength="500"
                            class="form-control {{ $bag->has('reception_note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Đã đặt hàng nhà cung cấp, dự kiến về kho đầu tháng 10">{{ old('reception_note') }}</textarea>
                        @if ($bag->has('reception_note'))
                            <span class="md-error">{{ $bag->first('reception_note') }}</span>
                        @endif
                        <small class="md-sub">Để trống thì giữ nguyên ghi chú đang có. Phòng ban lập phiếu đọc được
                            ghi chú này.</small>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span class="est-reception-desc">Xác nhận bộ phận Cung Ứng tiếp nhận phiếu dự trù đã được phê
                            duyệt.</span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary est-reception-submit">Tiếp nhận</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#receptionModal').modal('show');
        });
    </script>
@endif
