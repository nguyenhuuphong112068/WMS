{{--
| Modal xem lịch sử thay đổi của một dòng danh mục.
| Nội dung được nạp bằng JS (xem pages/category/shared/assets.blade.php) từ route <prefix>history.
--}}

<div class="modal fade md-modal" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history"></i>
                    Lịch Sử Thay Đổi
                    <small class="cat-history-subtitle md-sub ml-2"></small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="md-hint mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Mỗi dòng là một lần thay đổi, hiển thị giá trị của bản ghi ngay sau lần thay đổi đó.
                    Mới nhất nằm trên cùng.
                </div>

                <div class="cat-history-body"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
