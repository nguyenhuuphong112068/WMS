{{-- Chốt một dòng đề nghị đã được kho cấp phát (kho đã trừ tồn): ghi nhận sử dụng hoặc trả về kho. --}}
<style>
    .me-use-choices { display: flex; gap: 12px; flex-wrap: wrap; }
    .me-use-choice {
        flex: 1 1 220px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin: 0;
        padding: 12px 14px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: #fff;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .me-use-choice:hover { border-color: var(--primary-light); box-shadow: var(--shadow-sm); }
    .me-use-choice.is-active { background: var(--primary-soft); border-color: var(--primary); }
    .me-use-choice .title { font-weight: 700; color: var(--text-main); font-size: 0.92rem; }
    .me-use-choice .desc { font-size: 0.78rem; color: #6B7280; line-height: 1.4; }
    .me-use-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 28px;
        padding: 12px 14px;
        background: var(--primary-soft);
        border-radius: var(--border-radius-md);
    }
    .me-use-summary .cell small { display: block; color: #6B7280; font-size: 0.75rem; }
    .me-use-summary .cell b { color: var(--text-main); font-size: 0.92rem; }
</style>

<div class="modal fade md-modal" id="meUseModal" tabindex="-1" role="dialog" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-hand-holding-medical mr-2"></i>Sử Dụng Vật Tư</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="{{ route($expRoute . 'useStore') }}" method="POST" id="meUseForm">
                @csrf
                <input type="hidden" name="item_id" class="me-use-item-id">
                <input type="hidden" name="action" class="me-use-action" value="use">

                <div class="modal-body">
                    <div class="me-use-summary mb-3">
                        <div class="cell"><small>Vật tư</small><b class="me-use-material">—</b></div>
                        <div class="cell"><small>Mã xuất nhập</small><b class="me-use-code">—</b></div>
                        <div class="cell"><small>Đã cấp phát (đã trừ kho)</small><b class="me-use-issued">—</b></div>
                        <div class="cell"><small>Đề nghị</small><b class="me-use-request">—</b></div>
                    </div>

                    <div class="me-use-choices mb-3">
                        <label class="me-use-choice is-active">
                            <input type="radio" name="use_mode" value="use" checked class="mt-1">
                            <span>
                                <span class="title">Ghi nhận sử dụng</span>
                                <span class="desc">Nhập số thực dùng. Phần chưa dùng tự cộng lại kho.</span>
                            </span>
                        </label>
                        <label class="me-use-choice">
                            <input type="radio" name="use_mode" value="return" class="mt-1">
                            <span>
                                <span class="title">Trả về kho</span>
                                <span class="desc">Nhập số trả lại. Trả hết thì phiếu sử dụng bị huỷ, kho hoàn đủ.</span>
                            </span>
                        </label>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label class="me-use-amount-label">Số lượng thực dùng <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0" name="amount" class="form-control me-use-amount" required>
                                <div class="input-group-append"><span class="input-group-text me-use-unit">—</span></div>
                            </div>
                            <small class="text-muted me-use-hint"></small>
                        </div>
                        <div class="form-group col-md-7 me-use-only">
                            <label>Thiết bị liên quan</label>
                            <input type="text" name="product_name" maxlength="255" class="form-control me-use-product" placeholder="Thiết bị đã dùng vật tư này...">
                        </div>
                    </div>

                    <div class="form-group me-use-only">
                        <label>Số phiếu kiểm nghiệm</label>
                        <input type="text" name="test_report_no" maxlength="100" class="form-control">
                    </div>

                    <div class="form-group mb-0">
                        <label class="me-use-reason-label">Ghi chú</label>
                        <textarea name="reason" rows="2" maxlength="500" class="form-control" placeholder="Ghi chú nhật ký sử dụng..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary me-use-submit"><i class="fas fa-save mr-1"></i>Ghi nhận sử dụng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var $modal = $('#meUseModal');

        function applyMode() {
            var mode = $modal.find('[name="use_mode"]:checked').val() || 'use';
            var issued = parseFloat($modal.find('.me-use-amount').attr('data-issued')) || 0;
            var unit = $modal.find('.me-use-unit').text();
            var isReturn = mode === 'return';

            $modal.find('.me-use-action').val(mode);
            $modal.find('.me-use-choice').removeClass('is-active')
                .has('[name="use_mode"]:checked').addClass('is-active');
            $modal.find('.me-use-only').toggle(!isReturn);

            $modal.find('.me-use-amount-label').html(
                (isReturn ? 'Số lượng trả về kho' : 'Số lượng thực dùng') + ' <span class="text-danger">*</span>'
            );
            $modal.find('.me-use-reason-label').text(isReturn ? 'Lý do trả về kho' : 'Ghi chú');
            $modal.find('[name="reason"]').attr('placeholder', isReturn ? 'Vì sao không dùng hết...' : 'Ghi chú nhật ký sử dụng...');
            $modal.find('.me-use-submit').html(
                isReturn
                    ? '<i class="fas fa-rotate-left mr-1"></i>Trả về kho'
                    : '<i class="fas fa-save mr-1"></i>Ghi nhận sử dụng'
            );
            $modal.find('.me-use-hint').text('Tối đa ' + issued + ' ' + unit + (isReturn ? ' (trả hết = huỷ phiếu sử dụng)' : ''));
            $modal.find('.me-use-amount').attr('max', issued).val(issued);
        }

        $(document).on('change', '#meUseModal [name="use_mode"]', applyMode);

        // Mở từ nút "Sử Dụng Vật Tư" ở phiếu chi tiết đề nghị
        $(document).on('click', '.btn-me-use', function () {
            var d = $(this).data() || {};

            $modal.find('.me-use-item-id').val(d.itemId);
            $modal.find('.me-use-material').text(d.material || '—');
            $modal.find('.me-use-code').text(d.code || '—');
            $modal.find('.me-use-issued').text((d.amount || 0) + ' ' + (d.unit || ''));
            $modal.find('.me-use-request').text(d.request || '—');
            $modal.find('.me-use-unit').text(d.unit || '');
            $modal.find('.me-use-product').val(d.product || '');
            $modal.find('[name="test_report_no"], [name="reason"]').val('');
            $modal.find('.me-use-amount').attr('data-issued', d.amount || 0);
            $modal.find('[name="use_mode"][value="use"]').prop('checked', true);

            applyMode();
            $modal.modal('show');
        });
    });
</script>
