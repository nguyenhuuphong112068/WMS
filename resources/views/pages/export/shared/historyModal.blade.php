{{--
| Modal xem lịch sử điều chỉnh của một phiếu sử dụng hoá chất.
| Nội dung nạp bằng JS (xem pages/export/shared/assets.blade.php) từ route pages.export.chemicalExport.history.
--}}

<div class="modal fade md-modal" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clock-rotate-left"></i>
                    Lịch Sử Điều Chỉnh
                    <small class="exp-history-subtitle md-sub ml-2"></small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="md-hint mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Mỗi dòng là một lần thay đổi, hiển thị giá trị của phiếu ngay sau lần thay đổi đó và nội dung đã
                    đổi theo dạng <b>Trường: cũ → mới</b>. Mới nhất nằm trên cùng. Lịch sử chỉ ghi thêm, không sửa
                    và không xoá.
                </div>

                <div class="exp-history-body"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
