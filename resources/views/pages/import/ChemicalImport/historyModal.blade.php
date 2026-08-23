{{--
| Modal xem lịch sử điều chỉnh của một phiếu nhập.
| Nội dung nạp bằng JS (xem pages/import/shared/assets.blade.php) từ route pages.import.chemicalImport.history.
--}}

<div class="modal fade md-modal" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clock-rotate-left"></i>
                    Lịch Sử Điều Chỉnh
                    <small class="imp-history-subtitle md-sub ml-2"></small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="md-hint mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Mỗi dòng là một lần thay đổi phiếu nhập, hiển thị nội dung đã đổi, lý do và giá trị của
                    phiếu ngay sau lần đó. Mới nhất nằm trên cùng. Bản ghi lịch sử không sửa và không xoá.
                </div>

                <div class="imp-history-body"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
