{{-- Xem lại một kỳ kiểm kê đã có, dữ liệu nạp bằng AJAX từ route materialStocktake.detail --}}

<div class="modal fade md-modal" id="stocktakeDetailModal" tabindex="-1" role="dialog"
    data-url="{{ route('pages.inventory.materialStocktake.detail') }}">
    {{-- Bootstrap 4.1.3 chưa có .modal-xl nên phải tự đặt bề rộng, giống modal biểu đồ --}}
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 94vw;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check"></i> Chi Tiết Kỳ Kiểm Kê</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3"><small class="text-muted">Mã phiếu</small><div class="font-weight-bold st-d-code">—</div></div>
                    <div class="col-md-3"><small class="text-muted">Kỳ / Trạng thái</small><div class="font-weight-bold st-d-period">—</div></div>
                    <div class="col-md-3"><small class="text-muted">Mở phiếu</small><div class="font-weight-bold st-d-opened">—</div></div>
                    <div class="col-md-3"><small class="text-muted">Chốt phiếu</small><div class="font-weight-bold st-d-completed">—</div></div>
                </div>
                <div class="mb-3"><small class="text-muted">Ghi chú</small><div class="st-d-note">—</div></div>

                <div class="table-responsive" style="max-height: 55vh; overflow: auto;">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:45px">STT</th>
                                <th style="width:140px">Mã Xuất Nhập</th>
                                <th>Vật Tư</th>
                                <th style="width:100px">Vị Trí</th>
                                <th class="text-right" style="width:110px">Tồn Sổ Sách</th>
                                <th class="text-right" style="width:105px">Thực Tế</th>
                                <th class="text-right" style="width:105px">Chênh Lệch</th>
                                <th style="width:220px">Ghi Chú</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="md-hint">
                    <i class="fas fa-info-circle mr-1"></i>
                    Phiếu kiểm kê đã chốt không sửa, không xoá. Phần chênh lệch đã cân đối được lưu sang
                    <b>Lịch Sử Cân Đối</b> của từng mã xuất nhập và ghi Audit Trail.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
