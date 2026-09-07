<div class="modal fade" id="tareWeightModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="tareWeightForm" action="{{ route('pages.inventory.standardInventory.tareWeight') }}" method="POST">
                @csrf
                <input type="hidden" name="import_id" id="twImportId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-balance-scale mr-1"></i> Khối lượng bì sau khi sử dụng</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã ống chuẩn</label>
                        <input type="text" class="form-control" id="twCode" readonly>
                    </div>
                    <div class="form-group">
                        <label>Khối lượng Bì + Chuẩn trước khi dùng</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="twGross" readonly>
                            <div class="input-group-append">
                                <span class="input-group-text bg-light" id="twUnitGross"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="required font-weight-bold text-primary">Khối lượng bì sau khi sử dụng</label>
                        <div class="input-group">
                            <input type="text" inputmode="decimal" name="tare_weight_after" id="twTare"
                                class="form-control js-decimal" placeholder="Cân bì sau khi dùng" required>
                            <div class="input-group-append">
                                <span class="input-group-text bg-light" id="twUnitTare"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-right">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu khối lượng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.btn-tare-weight', function() {
            var $btn = $(this);

            $('#twImportId').val($btn.data('id'));
            $('#twCode').val($btn.data('code'));
            $('#twGross').val($btn.data('gross'));
            $('#twTare').val($btn.data('tare'));
            $('#twUnitGross, #twUnitTare').text($btn.data('unit') || '');

            $('#tareWeightModal').modal('show');
        });
    });
</script>
