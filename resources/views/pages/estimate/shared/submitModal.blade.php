@php $sbag = $errors->getBag('submitErrors'); @endphp

{{--
| DỰ TRÙ - MODAL TRÌNH KÝ
|--------------------------------------------------------------------------
| Mở khi bấm nút "Trình ký" ở danh sách. Người lập khai QUY TRÌNH KÝ DUYỆT ngay tại đây
| (số bước + người ký từng bước, tối thiểu 2, bước cuối là Ban Giám Đốc) rồi nhập lại mật
| khẩu để ký xác nhận. Id + mã phiếu + bước ký đã khai trước đó do JS đổ vào khi bấm nút
| (xem pages/estimate/shared/assets.blade.php).
--}}
<div class="modal fade md-modal" id="submitModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane"></i> Trình Ký Phiếu Dự Trù
                    <span class="est-code est-submit-code"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($estRoute . 'submit') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <input type="hidden" name="code" value="{{ old('code') }}">

                <div class="modal-body">
                    @include('pages.estimate.shared.signFlowFields', ['flowSigners' => old('signers', []), 'flowErrorBag' => 'submitErrors'])

                    <div class="form-group">
                        <label>Ghi Chú Trình Ký</label>
                        <textarea name="note" rows="2" maxlength="500"
                            class="form-control {{ $sbag->has('note') ? 'is-invalid' : '' }}"
                            placeholder="Ghi chú kèm theo khi trình ký (không bắt buộc)">{{ old('note') }}</textarea>
                        @if ($sbag->has('note'))
                            <span class="md-error">{{ $sbag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="form-group mb-0">
                        <label>Mật Khẩu Xác Nhận <span class="text-danger">*</span></label>
                        <input type="password" name="sign_password" autocomplete="current-password"
                            class="form-control {{ $sbag->has('sign_password') ? 'is-invalid' : '' }}"
                            placeholder="Nhập lại mật khẩu đăng nhập của bạn" required>
                        @if ($sbag->has('sign_password'))
                            <span class="md-error">{{ $sbag->first('sign_password') }}</span>
                        @endif
                        <span class="md-hint d-block mt-1">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Nhập lại mật khẩu để ký xác nhận thao tác trình ký (chữ ký điện tử).
                        </span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane mr-1"></i> Trình ký
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($sbag->any() || session('signatureError') === 'Trình ký')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#submitModal .est-submit-code').text(@json(old('code')));
            $('#submitModal').modal('show');
        });
    </script>
@endif
