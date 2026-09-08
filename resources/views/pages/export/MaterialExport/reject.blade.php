@php $bag = $errors->getBag('requestRejectErrors'); @endphp

{{-- Modal từ chối một đề nghị cấp phát vật tư đang chờ ký. Id do JS đổ vào khi bấm Từ chối. --}}
<div class="modal fade md-modal" id="reqRejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ban"></i> Từ Chối Đề Nghị Cấp Phát Vật Tư</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route($expRoute . 'requestReject') }}" method="POST">
                @csrf
                <input type="hidden" name="request_list_id" value="{{ old('request_list_id') }}">
                {{-- 'inbox' khi từ chối từ tab "Ký duyệt (mọi phòng ban)" - Controller tìm phiếu theo công ty --}}
                <input type="hidden" name="scope" value="{{ old('scope') }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã Đề Nghị</label>
                        <input type="text" class="form-control exp-readonly req-reject-code" readonly value="">
                    </div>
                    <div class="form-group">
                        <label>Lý Do Từ Chối <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" rows="3" maxlength="500"
                            class="form-control {{ $bag->has('reject_reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Vật tư còn tồn đủ dùng, chưa cần cấp thêm" required>{{ old('reject_reason') }}</textarea>
                        @if ($bag->has('reject_reason')) <div class="md-error text-danger small">{{ $bag->first('reject_reason') }}</div> @endif
                    </div>
                    {{-- Thành phần thứ 2 của chữ ký điện tử - 21 CFR Part 11 §11.200 --}}
                    <div class="form-group mb-0">
                        <label>Mật Khẩu Xác Nhận <span class="text-danger">*</span></label>
                        <input type="password" name="sign_password" autocomplete="current-password"
                            class="form-control {{ $bag->has('sign_password') ? 'is-invalid' : '' }}"
                            placeholder="Nhập lại mật khẩu đăng nhập để ký xác nhận" required>
                        @if ($bag->has('sign_password')) <div class="md-error text-danger small">{{ $bag->first('sign_password') }}</div> @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-ban mr-1"></i> Từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $(document).on('click', '.btn-req-reject', function () {
            $('#reqRejectModal [name="request_list_id"]').val($(this).data('id'));
            $('#reqRejectModal [name="scope"]').val($(this).data('scope') || '');
            $('#reqRejectModal .req-reject-code').val($(this).data('code'));
            $('#reqRejectModal').modal('show');
        });
    });
</script>

{{-- Mở lại modal khi lý do không hợp lệ, hoặc khi mật khẩu ký xác nhận sai (guardSignature) --}}
@if ($bag->any() || session('signatureError') === 'Từ chối đề nghị cấp phát vật tư')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var oldId = @json(old('request_list_id'));

            // Bấm lại đúng nút Từ chối của phiếu vừa thao tác để lấy lại mã đề nghị
            var $btn = $('.btn-req-reject').filter(function () {
                return String($(this).data('id')) === String(oldId);
            }).first();

            if ($btn.length) $btn.trigger('click');
            else $('#reqRejectModal').modal('show');

            $('#reqRejectModal [name="reject_reason"]').val(@json(old('reject_reason')));
        });
    </script>
@endif
