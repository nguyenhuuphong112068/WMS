{{--
| Modal theo dõi trình ký của một phiếu dự trù.
| Nội dung nạp bằng JS (xem pages/estimate/shared/assets.blade.php) từ route <prefix>history.
--}}

<div class="modal fade md-modal" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-route"></i>
                    Theo Dõi Trình Ký
                    <small class="est-history-subtitle md-sub ml-2"></small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="md-hint mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Mỗi dòng là một bước đã đi qua của phiếu: trình ký, ký duyệt, từ chối, tiếp nhận.
                    Mới nhất nằm trên cùng.
                </div>

                <div class="est-history"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
