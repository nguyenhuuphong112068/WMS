<div class="modal fade md-modal" id="balancingHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clock-rotate-left"></i> Lịch Sử Cân Đối</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4"><small class="text-muted">Mã xuất nhập</small><div class="font-weight-bold mi-hist-code">—</div></div>
                    <div class="col-md-4"><small class="text-muted">Vật tư</small><div class="font-weight-bold mi-hist-name">—</div></div>
                    <div class="col-md-4">
                        <small class="text-muted">Nhập / Đã cân đối / Tồn</small>
                        <div class="font-weight-bold">
                            <span class="mi-hist-imported">—</span> / <span class="mi-hist-balanced">—</span> / <span class="mi-hist-gap">—</span>
                        </div>
                    </div>
                </div>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px">STT</th>
                            <th class="text-right" style="width:130px">Số Cân Đối</th>
                            <th>Người Cân Đối</th>
                            <th style="width:150px">Thời Điểm</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="md-hint"><i class="fas fa-info-circle mr-1"></i> Bản ghi cân đối không sửa, không xoá.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
