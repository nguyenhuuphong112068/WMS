<!-- Modal Reset Mật Khẩu -->
<div class="modal fade" id="ResetPassModal" tabindex="-1" role="dialog" aria-labelledby="ResetPassModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">

        <form action="{{ route('pages.user.user.resetPassword') }}" method="POST">
            @csrf
            <input type="hidden" name="id" value="">

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="ResetPassModalLabel" style="color: #CDC717">
                        Reset Mật Khẩu
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Đặt mật khẩu tạm cho tài khoản
                        <strong id="resetPassUserName">—</strong>.
                        Người dùng sẽ bị buộc đổi mật khẩu ở lần đăng nhập kế tiếp.
                    </div>

                    {{-- Mật khẩu tạm --}}
                    <div class="form-group">
                        <label for="resetNewPassword">Mật Khẩu Tạm</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="newPassword" id="resetNewPassword"
                                value="{{ old('newPassword') }}"
                                placeholder="Tối thiểu 6 ký tự: chữ hoa, chữ thường, số, ký tự đặc biệt">
                            <div class="input-group-append">
                                <span class="input-group-text toggle-password" style="cursor: pointer;"
                                    data-target="#resetNewPassword">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        @error('newPassword', 'resetPasswordErrors')
                            <div class="alert alert-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Xác nhận --}}
                    <div class="form-group">
                        <label for="resetConfirmPassword">Xác Nhận Mật Khẩu Tạm</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirmPassword"
                                id="resetConfirmPassword" placeholder="Nhập lại mật khẩu tạm">
                            <div class="input-group-append">
                                <span class="input-group-text toggle-password" style="cursor: pointer;"
                                    data-target="#resetConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        @error('confirmPassword', 'resetPasswordErrors')
                            <div class="alert alert-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Reset Mật Khẩu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Mở lại modal nếu có lỗi validation --}}
@if ($errors->resetPasswordErrors->any())
    <script>
        $(document).ready(function() {
            var modal = $('#ResetPassModal');
            modal.find('input[name="id"]').val('{{ old('id') }}');
            modal.modal('show');
        });
    </script>
@endif
