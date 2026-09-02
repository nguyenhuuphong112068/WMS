{{-- Chi tiết một ô vị trí trên sơ đồ Tồn Kho Theo Vị Trí --}}
<div class="modal fade md-modal" id="zoneDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-map-pin"></i> <span class="mz-d-name">Vị trí</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Đường dẫn định khu</small>
                        <div class="font-weight-bold mz-d-path">—</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Mã vị trí</small>
                        <div class="font-weight-bold mz-d-code">—</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Mã xuất nhập</small>
                        <div class="font-weight-bold mz-d-lots">0</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Vật tư</small>
                        <div class="font-weight-bold mz-d-materials">0</div>
                    </div>
                </div>

                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px">STT</th>
                            <th style="width:140px">Mã Xuất Nhập</th>
                            <th>Vật Tư</th>
                            <th class="text-right" style="width:120px">Tồn</th>
                            <th class="text-center" style="width:105px">Hạn Dùng</th>
                            <th class="text-center" style="width:110px">Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div class="md-hint">
                    <i class="fas fa-info-circle mr-1"></i>
                    Chỉ liệt kê các mã <b>còn tồn</b> tại vị trí này tính đến hết kỳ đang xem.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
