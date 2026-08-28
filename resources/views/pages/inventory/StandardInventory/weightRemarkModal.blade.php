<div class="modal fade" id="weightRemarkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="weightRemarkForm" action="{{ route('pages.inventory.standardInventory.weightRemark') }}" method="POST">
                @csrf
                <input type="hidden" name="import_id" id="wrImportId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-comment"></i> Nhận xét kiểm soát khối lượng</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nhận xét ngoài giới hạn cho phép</label>
                        <textarea name="weight_deviation_remark" id="wrRemark" class="form-control" rows="4" placeholder="Nhập nhận xét..."></textarea>
                    </div>
                </div>
                <div class="modal-footer text-right">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu nhận xét</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.btn-weight-remark', function() {
            var id = $(this).data('id');
            var remark = $(this).data('remark');
            
            $('#wrImportId').val(id);
            $('#wrRemark').val(remark);
            
            $('#weightRemarkModal').modal('show');
        });
    });
</script>
