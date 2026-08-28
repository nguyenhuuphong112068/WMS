{{-- Modal xem lịch sử điều chỉnh phiếu sử dụng vật tư. Nội dung nạp bằng JS dùng chung
     (pages/export/shared/assets.blade.php -> renderHistory) từ route materialExport.history. --}}
<div class="modal fade md-modal" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clock-rotate-left"></i> Lịch Sử Điều Chỉnh
                    <small class="exp-history-subtitle md-sub ml-2"></small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="md-hint mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Mỗi dòng là một lần thay đổi phiếu, hiển thị nội dung đã đổi và giá trị của phiếu ngay sau lần đó.
                    Mới nhất nằm trên cùng. Bản ghi lịch sử không sửa và không xoá.
                </div>
                <div class="exp-history-body"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
