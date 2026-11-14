{{--
| Hộp ký duyệt / từ chối đề nghị liên phòng ban Hoá Chất / Chất Chuẩn (trait TransferSigning).
| Nút mở: .btn-ts-sign trong modal chi tiết phiếu, data-mode (sign | reject), data-id, data-code, data-step.
| Biến vào: $tsSignUrl, $tsRejectUrl.
--}}
<div class="modal fade md-modal" id="tsSignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-signature"></i> <span class="ts-sign-title"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" data-sign-url="{{ $tsSignUrl }}" data-reject-url="{{ $tsRejectUrl }}">
                @csrf
                <input type="hidden" name="transfer_request_id">
                <div class="modal-body">
                    <div class="form-group ts-sign-note">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="500" class="form-control" placeholder="Ghi chú khi ký (không bắt buộc)"></textarea>
                    </div>
                    <div class="form-group ts-sign-reason">
                        <label>Lý Do Từ Chối <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500" class="form-control" placeholder="Nêu rõ lý do để phòng đề nghị sửa lại"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Mật Khẩu Xác Nhận <span class="text-danger">*</span></label>
                        <input type="password" name="sign_password" autocomplete="current-password" class="form-control"
                            placeholder="Nhập lại mật khẩu đăng nhập của bạn" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn ts-sign-submit"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Một hộp cho cả Ký và Từ chối: đổi action, tiêu đề, ô bắt buộc theo nút bấm
        $(document).on('click', '.btn-ts-sign', function() {
            var btn = this;
            var $parent = $(this).closest('.modal');

            // Nút nằm trong modal chi tiết phiếu: đóng modal đó rồi mới mở hộp ký (không chồng 2 modal)
            if ($parent.length && $parent.attr('id') !== 'tsSignModal') {
                $parent.one('hidden.bs.modal', function() { tsOpenSign(btn); }).modal('hide');
            } else {
                tsOpenSign(btn);
            }
        });

        function tsOpenSign(btn) {
            var reject = $(btn).data('mode') === 'reject';
            var $modal = $('#tsSignModal').appendTo('body');
            var $form = $modal.find('form');

            $form.attr('action', $form.data(reject ? 'reject-url' : 'sign-url'));
            $form.find('[name="transfer_request_id"]').val($(btn).data('id'));
            $form.find('textarea, [name="sign_password"]').val('');
            $form.find('.ts-sign-note').toggle(!reject);
            $form.find('.ts-sign-reason').toggle(reject).find('textarea').prop('required', reject);
            $modal.find('.ts-sign-title').text((reject ? 'Từ chối ' : 'Ký duyệt bước ' + $(btn).data('step') + ' - ') + $(btn).data('code'));
            $modal.find('.ts-sign-submit')
                .toggleClass('btn-success', !reject).toggleClass('btn-danger', reject)
                .html(reject ? '<i class="fas fa-times mr-1"></i> Từ chối' : '<i class="fas fa-signature mr-1"></i> Ký duyệt');
            $modal.modal('show');
        }

        // Link "chờ bạn ký" ở Trang chủ mở thẳng phiếu: ?open=transferDetailModal_<id> (HomeController::signUrl)
        var params = new URLSearchParams(window.location.search);
        var open = params.get('open') || '';

        if (/^transferDetailModal_\d+$/.test(open)) {
            params.delete('open');
            window.history.replaceState(null, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));

            if ($('#' + open).length) {
                $('#' + open).modal('show');
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Không mở được phiếu',
                    text: 'Phiếu không nằm trong danh sách đang hiện (đã được xử lý hoặc ngoài khoảng ngày lọc).'
                });
            }
        }
    });
</script>
