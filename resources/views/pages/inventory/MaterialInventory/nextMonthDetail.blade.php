{{--
| Chi tiết một mã ở tab "Khả dụng tháng tới": các con số của phép tính và danh sách
| đề nghị theo chu kỳ tạo ra nhu cầu. Dữ liệu đổ bằng JS trong nextMonth.blade.php.
--}}
<style>
    /* Bảng nguồn nhu cầu có 6 cột chữ dài - để mặc định modal-xl thì tên danh sách bị xuống dòng từng chữ */
    #mfDetailModal .modal-dialog { max-width: 85%; }
</style>

<div class="modal fade md-modal" id="mfDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tasks"></i> Nhu Cầu Theo Chu Kỳ - <span class="mf-d-month">—</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3"><small class="text-muted">Mã vật tư</small>
                        <div class="font-weight-bold mf-d-code">—</div>
                    </div>
                    <div class="col-md-9"><small class="text-muted">Vật tư</small>
                        <div class="font-weight-bold mf-d-name">—</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-2"><small class="text-muted">Tồn hiện hành</small>
                        <div class="font-weight-bold mf-d-stock">—</div>
                    </div>
                    <div class="col-md-2"><small class="text-muted">Đã đề nghị chưa cấp</small>
                        <div class="font-weight-bold mf-d-reserved">—</div>
                    </div>
                    <div class="col-md-2"><small class="text-muted">Chu kỳ chưa tạo ĐN</small>
                        <div class="font-weight-bold mf-d-pending">—</div>
                    </div>
                    <div class="col-md-2"><small class="text-muted">Khả dụng</small>
                        <div class="font-weight-bold mf-d-available">—</div>
                    </div>
                    <div class="col-md-2"><small class="text-muted">Nhu cầu</small>
                        <div class="font-weight-bold mf-d-demand">—</div>
                    </div>
                    <div class="col-md-2"><small class="text-muted">Thiếu / Dư</small>
                        <div class="font-weight-bold mf-d-gap">—</div>
                    </div>
                </div>

                <h6 class="font-weight-bold text-uppercase"><i class="fas fa-calendar-day mr-1"></i>
                    Nhu cầu <span class="mf-d-month">—</span></h6>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px">STT</th>
                            <th>Danh Sách Theo Chu Kỳ</th>
                            <th class="text-center" style="width:130px">Loại</th>
                            <th class="text-center" style="width:150px">Chu Kỳ</th>
                            <th class="text-center" style="width:170px">Số Lần × Số Lượng</th>
                            <th class="text-right" style="width:110px">Thành Lượng</th>
                        </tr>
                    </thead>
                    <tbody class="mf-d-demand-body"></tbody>
                </table>

                <h6 class="font-weight-bold text-uppercase mt-4"><i class="fas fa-hourglass-half mr-1"></i>
                    Chu kỳ chưa tạo đề nghị (<span class="mf-d-pre-range">—</span>)</h6>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px">STT</th>
                            <th>Danh Sách Theo Chu Kỳ</th>
                            <th class="text-center" style="width:130px">Loại</th>
                            <th class="text-center" style="width:150px">Chu Kỳ</th>
                            <th class="text-center" style="width:170px">Số Lần × Số Lượng</th>
                            <th class="text-right" style="width:110px">Thành Lượng</th>
                        </tr>
                    </thead>
                    <tbody class="mf-d-pending-body"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
