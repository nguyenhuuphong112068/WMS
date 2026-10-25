<p>Danh mục <b>chất hoá học tinh khiết</b> (hoạt chất) kèm phân loại theo <b>Nghị định 24/2026/NĐ-CP</b>.
    Tên hoá chất (có thể là hỗn hợp) ở màn hình <a href="#hoa-chat">Hoá Chất</a> được ghép từ các hoạt chất này,
    nên cần khai hoạt chất trước.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên khoa học (IUPAC)', true, 'Tên danh pháp chuẩn. Không trùng.'],
    ['Tên chất', false, 'Tên thường gọi hoặc tên tiếng Anh.'],
    ['Mã số CAS', false, 'Ví dụ <span class="ug-key">75-05-8</span>. Nên nhập để tra cứu và đối chiếu chính xác.'],
    ['Công Thức Hoá Học', false, 'Dùng các nút trên ô nhập để gõ chỉ số dưới/trên (hoặc <span class="ug-key">Ctrl</span> + <span class="ug-key">↓</span> / <span class="ug-key">↑</span>). Nút <b>Tự động</b> hạ chỉ số các con số đứng sau ký hiệu nguyên tố, ví dụ H2SO4 → H₂SO₄.'],
    ['Thuộc Mục Gộp Của Nghị Định', false, 'Dùng khi nghị định khai theo <b>nhóm chất</b>, ví dụ "Thủy ngân và các hợp chất của thủy ngân". Chất cụ thể (HgCl₂...) chọn mục gộp tương ứng để tồn kho được <b>cộng chung</b> vào ngưỡng của mục gộp. Chất đó cũng tự nhận phân loại của mục gộp, không cần tick lại.'],
    ['Hệ Số Quy Đổi Về Mục Gộp', false, 'Chỉ hiện khi đã chọn mục gộp. Để <b>1</b> nếu tính trọn khối lượng hợp chất. Chỉ đổi khi cần quy về phần nguyên tố, ví dụ 0,738 nếu chỉ tính khối lượng Hg trong HgCl₂.'],
    ['Phân Loại NĐ 24/2026', false, 'Tick một hoặc nhiều nhóm <b>đơn chất</b>: 1, 3, 4, 5, 6, 7, 9. Các nhóm hỗn hợp (2, 8, 10) do hệ thống tự suy ở màn hình Hoá Chất.'],
    ['Ngưỡng Tồn Trữ (kg)', false, '<b>Bắt buộc khi tick nhóm 9</b> (Phụ lục IV Bảng A): khối lượng tồn trữ lớn nhất tại một thời điểm. Hệ thống cảnh báo khi tổng tồn của công ty vượt ngưỡng.'],
]])

<h3>Bảng tra các nhóm đơn chất</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 70px" class="text-center">Nhóm</th>
                <th>Nội dung</th>
            </tr>
        </thead>
        <tbody>
            @foreach (\App\Support\ChemicalClassification::SINGLE_SUBSTANCE_GROUPS as $g)
                <tr>
                    <td class="text-center">{{ $g }}</td>
                    <td style="font-weight: 400">{{ \App\Support\ChemicalClassification::GROUPS[$g] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div><b>Mã hoạt chất</b> do hệ thống tự sinh (bắt đầu bằng chữ <span class="ug-key">A</span>). Hoạt chất chỉ được
        dùng để cảnh báo và ghép vào hoá chất sau khi <b>Đã duyệt</b>.</div>
</div>
