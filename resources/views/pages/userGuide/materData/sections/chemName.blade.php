<p>Tên gọi của hoá chất đúng như trên nhãn hoặc tên thương mại, ví dụ <i>Acetonitrile HPLC</i>. Một tên hoá chất
    có thể là <b>đơn chất</b> (1 hoạt chất) hoặc <b>hỗn hợp</b> (từ 2 hoạt chất trở lên). Danh mục hoá chất công ty và
    phiếu nhập, xuất đều chọn từ danh sách này.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/hoa-chat.png', 'caption' => 'Màn hình Hoá Chất'])

<h3>Các bước khai báo</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Thêm mới</span>, nhập
        <b>Tên Hoá Chất</b> (không trùng).</li>
    <li>Ở ô <b>Hoạt Chất Thành Phần</b>, chọn một hoặc nhiều hoạt chất. Có thể tìm theo tên, số CAS hoặc công thức.
        Chỉ hoạt chất <a href="#hoat-chat">Đã duyệt</a> mới hiện ở đây.</li>
    <li>Với hỗn hợp, nhập <b>Tỉ Lệ % Khối Lượng</b> của từng thành phần (0 - 100). Tỉ lệ được dùng để suy nhóm 8.</li>
    <li>Nếu hỗn hợp thuộc Phụ lục IV Bảng B, tick các <a href="#nhom-nguy-hai-bang-b">nhóm nguy hại</a> tương ứng.</li>
    <li>Tick <b>Phân Loại Khác</b> nếu có: <i>Hoá chất cấm theo Luật Đầu tư 2025</i> (nhóm 11), <i>Hàng hoá đặc biệt</i> (nhóm 12).</li>
    <li>Bấm <b>Lưu</b> và chờ người có quyền duyệt.</li>
</ol>

<h3>Hệ thống tự phân loại hỗn hợp</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 80px" class="text-center">Nhóm</th>
                <th>Điều kiện hệ thống tự gán</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">2</td>
                <td style="font-weight: 400">Hỗn hợp có ít nhất 1 thành phần thuộc <b>nhóm 1</b> (Phụ lục II).</td>
            </tr>
            <tr>
                <td class="text-center">8</td>
                <td style="font-weight: 400">Có thành phần nhóm 3, 4, 6 hoặc 7 với tỉ lệ <b>&gt; 1%</b>, hoặc thành phần nhóm 5 với tỉ
                    lệ <b>&gt; 5%</b> (Phụ lục III).</td>
            </tr>
            <tr>
                <td class="text-center">10</td>
                <td style="font-weight: 400">Hỗn hợp có ít nhất 1 thành phần thuộc <b>nhóm 9</b> và đã tick ít nhất 1 nhóm nguy
                    hại Bảng B (Phụ lục IV Bảng B).</td>
            </tr>
        </tbody>
    </table>
</div>
<p>Các nhóm đơn chất (1, 3, 4, 5, 6, 7, 9) thuộc từng hoạt chất và hiện ở cột <b>Hoạt Chất Thành Phần</b>.</p>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Chỉ tick được nhóm nguy hại Bảng B khi hoá chất là <b>hỗn hợp</b> (ít nhất 2 hoạt chất) và có
        <b>ít nhất một thành phần thuộc nhóm 9</b>. Nếu không, hệ thống báo lỗi và không lưu.</div>
</div>
