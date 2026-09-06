{{--
| Cửa sổ xem file đính kèm của một phiếu nhập - dùng chung cho màn hình Nhập và Tồn Kho.
| Nút .att-open-modal trên bảng mang data-code / data-name / data-type-label /
| data-toggle-url / data-files (JSON). JS ở pages/shared/attachmentListAssets.blade.php
| đổ dữ liệu vào bảng và xử lý nút đổi trạng thái.
--}}
<div class="modal fade md-modal" id="attachmentListModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paperclip mr-1"></i> File Đính Kèm
                    <small class="att-modal-type md-sub ml-2"></small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="att-modal-head">
                    <div>
                        <label>Mã</label>
                        <div class="att-modal-code">—</div>
                    </div>
                    <div>
                        <label>Tên</label>
                        <div class="att-modal-name">—</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover att-modal-table w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 45px">STT</th>
                                <th>Tên File</th>
                                <th style="width: 160px">Người Đính Kèm</th>
                                <th class="text-center" style="width: 140px">Ngày Đính Kèm</th>
                                <th class="text-center" style="width: 120px">Trạng Thái</th>
                                <th class="text-center" style="width: 165px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
