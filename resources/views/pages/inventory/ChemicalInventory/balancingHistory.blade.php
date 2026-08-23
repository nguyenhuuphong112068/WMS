{{--
|--------------------------------------------------------------------------
| TỒN - LỊCH SỬ CÂN ĐỐI CỦA MỘT MÃ XUẤT NHẬP
|--------------------------------------------------------------------------
| Mở bằng badge nhỏ ở góc trên bên phải nút "Cân đối" trên bảng tồn kho.
| Badge chỉ hiện khi mã xuất nhập đã được cân đối ít nhất một lần, nội dung
| badge là số lần cân đối của chính mã đó.
|
| Toàn bộ phần thân bảng do JS đổ vào từ data-balancings của modal này
| ([import_id => danh sách lần cân đối]), không truy vấn lại theo từng lần mở.
--}}

<div class="modal fade md-modal" id="balancingHistoryModal" tabindex="-1" role="dialog"
    data-balancings="{{ json_encode($invBalancingMap) }}">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clock-rotate-left"></i> Lịch Sử Cân Đối</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">

                <div class="inv-hist-head">
                    <div>
                        <label>Mã Xuất Nhập</label>
                        <div class="inv-code inv-hist-code">—</div>
                    </div>
                    <div>
                        <label>Hoá Chất</label>
                        <div class="inv-hist-chem">—</div>
                    </div>
                    <div>
                        <label>Số Lượng Nhập</label>
                        <div class="inv-amount inv-hist-imported">—</div>
                    </div>
                    <div>
                        <label>Tổng Đã Cân Đối</label>
                        <div class="inv-hist-balanced">—</div>
                    </div>
                    <div>
                        <label>Tồn Hiện Tại</label>
                        <div class="inv-amount inv-hist-gap">—</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover w-100 inv-hist-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">STT</th>
                                <th class="text-right" style="width: 140px">Số Cân Đối</th>
                                <th class="text-center" style="width: 100px">Tỉ Lệ</th>
                                <th>Người Cân Đối</th>
                                <th class="text-center" style="width: 150px">Thời Điểm</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="md-hint">
                    <i class="fas fa-info-circle mr-1"></i>
                    Bản ghi cân đối <b>không sửa và không xoá</b> - ghi sai thì cân đối ngược lại. Tổng các lần cân
                    đối của một mã xuất nhập vẫn bị chặn trong <b>±{{ $balancingMaxPercent }}%</b> số lượng nhập.
                    Mọi lần cân đối đều lưu trong Audit Trail.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
