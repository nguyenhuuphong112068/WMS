{{--
| Modal xem lịch sử điều chỉnh của một phiếu nhập chất chuẩn.
| Nội dung nạp bằng JS (xem pages/import/shared/assets.blade.php) từ route
| pages.import.standardImport.history.
--}}

<div class="modal fade md-modal" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered wms-history-dialog" role="document">
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
                <div class="imp-history-body"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
