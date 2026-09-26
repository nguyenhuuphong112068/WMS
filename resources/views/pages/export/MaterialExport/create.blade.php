@php $ubag = $errors->getBag('updateErrors'); @endphp

{{-- ============ ĐIỀU CHỈNH PHIẾU ============ --}}
{{-- Các ô trong modal bám đúng theo cột của bảng "Sổ sử dụng vật tư": phần chỉ xem
     (mã xuất nhập, vật tư, loại, thời gian, người thực hiện) nằm ở khối tóm tắt,
     phần được phép sửa (số lượng, sản phẩm / lý do) nằm ở form bên dưới. --}}
<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Điều chỉnh phiếu xuất kho</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id">
                <div class="modal-body">

                    <div class="exp-summary mb-3">
                        <div class="cell">
                            <small>Mã xuất nhập</small>
                            <b class="exp-code me-up-code">—</b>
                        </div>
                        <div class="cell me-up-req-cell">
                            <small>Mã đề nghị</small>
                            <b class="exp-code me-up-req">—</b>
                        </div>
                        <div class="cell">
                            <small>Vật tư</small>
                            <b class="me-up-material">—</b>
                            <span class="md-sub me-up-catcode"></span>
                            <span class="md-sub me-up-spec"></span>
                        </div>
                        <div class="cell">
                            <small>Loại</small>
                            <b class="me-up-type">—</b>
                        </div>
                        <div class="cell">
                            <small>Thời gian</small>
                            <b class="me-up-time">—</b>
                        </div>
                        <div class="cell">
                            <small>Người thực hiện</small>
                            <b class="me-up-user">—</b>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Số lượng <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" inputmode="decimal" min="0.0001" name="amount"
                                    class="form-control js-decimal {{ $ubag->has('amount') ? 'is-invalid' : '' }}" required>
                                <div class="input-group-append"><span class="input-group-text me-up-unit">—</span></div>
                            </div>
                            @if ($ubag->has('amount')) <div class="md-error text-danger small">{{ $ubag->first('amount') }}</div> @endif
                        </div>
                        <div class="form-group col-md-7 me-up-export">
                            <label>Thiết bị liên quan</label>
                            <input type="text" name="product_name" maxlength="255" class="form-control"
                                placeholder="Thiết bị đã dùng vật tư này...">
                        </div>
                    </div>

                    <div class="form-group me-up-export">
                        <label>Mục đích</label>
                        <textarea name="purpose" rows="2" maxlength="500" class="form-control"
                            placeholder="Mục đích sử dụng..."></textarea>
                        <small class="md-sub">Mục đích thuộc dòng đề nghị: sửa ở đây áp dụng cho mọi phiếu được cấp từ dòng đó.</small>
                    </div>
                    <div class="form-group me-up-cancel">
                        <label>Lý do loại bỏ</label>
                        <textarea name="reason" rows="2" maxlength="500" class="form-control"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Lý do điều chỉnh</label>
                        <textarea name="adjust_reason" rows="2" maxlength="500" data-require-fill class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Ghi nhận điều chỉnh</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Đổ dữ liệu dòng của sổ sử dụng vào modal điều chỉnh. Phiếu sử dụng chỉ sinh
        // từ cấp phát nên form suy loại phiếu từ dữ liệu dòng.
        $(document).on('click', '.btn-me-edit', function () {
            var r = $(this).data('row') || {};
            var $m = $('#updateModal');
            var isCancel = r.type === 'cancel';

            $m.find('form')[0].reset();
            $m.find('[name="id"]').val(r.id);
            $m.find('[name="amount"]').val(r.amount);
            $m.find('[name="product_name"]').val(r.product_name || '');
            $m.find('[name="purpose"]').val(r.purpose || '');
            $m.find('[name="reason"]').val(r.reason || '');

            $m.find('.me-up-code').text(r.code || '—');
            $m.find('.me-up-req').text(r.request_code || '—');
            $m.find('.me-up-req-cell').toggle(!isCancel && !!r.request_code);
            $m.find('.me-up-material').text(r.material_name || '—');
            $m.find('.me-up-catcode').text(r.category_code || '');
            $m.find('.me-up-spec').text(r.technical_specification || '');
            $m.find('.me-up-time').text(r.created_at || '—');
            $m.find('.me-up-user').text(r.used_by || '—');
            $m.find('.me-up-unit').text(r.unit_short_name || '—');
            $m.find('.me-up-type')
                .empty()
                .append($('<span>').addClass('badge badge-' + (isCancel ? 'danger' : 'success')).text(r.type_label || '—'))
                .append(r.locked ? $('<span>').addClass('badge badge-secondary ml-1').text('Đã khoá') : '');

            $m.find('.me-up-export').toggle(!isCancel);
            $m.find('.me-up-cancel').toggle(isCancel);
            $m.modal('show');
        });

        {{-- Lỗi validate: mở lại đúng dòng vừa sửa để giữ nguyên phần thông tin chỉ xem --}}
        @if ($ubag->any())
            $(function () {
                var oldId = @json(old('id'));
                var $btn = $('.btn-me-edit').filter(function () {
                    return String(($(this).data('row') || {}).id) === String(oldId);
                }).first();

                if (!$btn.length) return $('#updateModal').modal('show');

                $btn.trigger('click');
                $('#updateModal [name="amount"]').val(@json(old('amount')));
                $('#updateModal [name="product_name"]').val(@json(old('product_name')));
                $('#updateModal [name="purpose"]').val(@json(old('purpose')));
                $('#updateModal [name="reason"]').val(@json(old('reason')));
                $('#updateModal [name="adjust_reason"]').val(@json(old('adjust_reason')));
            });
        @endif
    });
</script>
