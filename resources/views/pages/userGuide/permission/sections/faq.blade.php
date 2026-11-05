<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 38%">Hiện tượng</th>
                <th>Nguyên nhân và cách xử lý</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Không thấy menu hoặc nút cần dùng</td>
                <td style="font-weight: 400">Vai trò của bạn ở phòng ban đang chọn chưa có quyền đó. Kiểm tra lại phòng ban đang chọn
                    hoặc nhờ quản trị viên cấp quyền.</td>
            </tr>
            <tr>
                <td>Phòng này có nút, phòng khác thì không</td>
                <td style="font-weight: 400">Vai trò chỉ được gán cho một số phòng ban. Xem <a href="#thiet-lap">Phạm vi phòng ban của
                    từng quyền</a>.</td>
            </tr>
            <tr>
                <td>"Phòng ban ... ngoài phạm vi làm việc của bạn - chỉ được xem, không thao tác được."</td>
                <td style="font-weight: 400">Phòng đang chọn có nhãn <b>Chỉ xem</b>. Chuyển về phòng ban của bạn ở ô cuối cột icon, hoặc
                    nhờ quản trị viên gán vai trò cho phòng đó nếu cần thao tác. Xem
                    <a href="#tong-quan">Phạm vi làm việc và phòng ban chỉ xem</a>.</td>
            </tr>
            <tr>
                <td>Đã tick quyền xem tab mà vẫn không thấy tab</td>
                <td style="font-weight: 400">Thiếu quyền xem màn hình (quyền cha). Ô hiện dấu tick nhạt trong
                    <a href="#bang-materData">bảng phân quyền chung</a>.</td>
            </tr>
            <tr>
                <td>Vừa tạo nhóm quyền mà không thấy cột của nhóm trên bảng</td>
                <td style="font-weight: 400">Bảng đang lọc: nút loại (Vật Tư / Hoá Chất / Chất Chuẩn) khác loại phụ trách của nhóm,
                    hoặc nút <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-users"></i> nhóm quyền</span>
                    đang chọn một số nhóm. Bấm <b>Tất cả</b> và chọn <b>Chọn tất cả</b> trong danh sách nhóm quyền.</td>
            </tr>
            <tr>
                <td>Nhóm quyền nằm sai cột loại (Chung / Vật Tư / Hoá Chất / Chất Chuẩn)</td>
                <td style="font-weight: 400">Sửa ô <b>Loại Phụ Trách</b> của nhóm trong
                    <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-users-cog"></i> Quản Lý Nhóm Quyền</span>:
                    tick 1 loại thì nằm ở loại đó, tick 2-3 loại thì nằm ở Chung.</td>
            </tr>
            <tr>
                <td>"Vui lòng chọn ít nhất một loại phụ trách (Vật Tư / Hoá Chất / Chất Chuẩn)."</td>
                <td style="font-weight: 400">Tick ít nhất một ô Loại Phụ Trách trước khi bấm Lưu.</td>
            </tr>
            <tr>
                <td>Thấy dấu tick trên bảng Nhóm Quyền nhưng bấm không đổi được</td>
                <td style="font-weight: 400">Tài khoản chỉ có quyền <i>Xem Nhóm Quyền</i>, thiếu quyền <i>Phân Quyền Cho Nhóm</i>.</td>
            </tr>
            <tr>
                <td>Nhóm quyền có quyền nhưng một người vẫn không thấy</td>
                <td style="font-weight: 400">Người đó bị <b>Từ chối</b> riêng quyền này. Xem nút
                    <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-user-shield"></i></span> ở màn hình User.</td>
            </tr>
            <tr>
                <td>Không có tên trong danh sách người ký dự trù</td>
                <td style="font-weight: 400">Cần vai trò <b>Quản Lý Bộ Phân</b> (bước 1) hoặc <b>Ban Giám Đốc</b> (bước 2) với phạm vi
                    gồm đúng phòng lập phiếu.</td>
            </tr>
            <tr>
                <td>"Không thể gỡ quyền của nhóm Admin"</td>
                <td style="font-weight: 400">Nhóm Admin luôn giữ toàn quyền.</td>
            </tr>
            <tr>
                <td>"Nhóm quyền ... đang được gán cho người dùng, không thể xoá."</td>
                <td style="font-weight: 400">Gỡ nhóm này khỏi mọi người dùng ở màn hình User trước khi xoá.</td>
            </tr>
        </tbody>
    </table>
</div>
