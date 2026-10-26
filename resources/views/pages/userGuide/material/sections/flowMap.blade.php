{{--
| Sơ đồ chung: hàng ngang = đường đi của phiếu, cột dọc = trang thao tác, cột cuối = tồn kho thay đổi.
| Mỗi khối là một liên kết tới mục hướng dẫn chi tiết. Mũi tên vẽ bằng SVG ở cuối file:
|   .m-src[data-to=n]   nguồn -> khối phiếu thứ n của hàng (hoặc data-to=<sid> -> nguồn có data-sid)
|   .m-doc[data-n]      khối phiếu theo thứ tự, nối lần lượt
|   .m-doc[data-from=n] khối rẽ nhánh từ khối phiếu thứ n
|   .m-eff[data-from]   ô tồn kho, mặc định nối từ khối phiếu cuối; 'alt' = khối rẽ nhánh
| Mục khác dẫn về đây bằng @include('pages.userGuide.shared.flowMapLink', ['lane' => ..., ...]).
--}}
<p>Toàn bộ đường đi của phiếu vật tư trên một sơ đồ: <b>mỗi hàng ngang là một đường đi</b>, <b>mỗi cột dọc là một trang</b>
    trên menu, cột cuối cho biết <b>tồn kho thay đổi thế nào</b> khi phiếu đi hết đường. Bấm vào bất kỳ khối nào để mở hướng
    dẫn chi tiết của bước đó.</p>

<div class="ugfm-legend">
    <span class="ugfm-lg"><span class="m-src ugfm-mini"><b>Nguồn tạo phiếu</b></span> tự động hoặc người dùng bấm nút</span>
    <span class="ugfm-lg"><span class="m-doc ugfm-mini"><span class="s">Trạng thái phiếu</span></span> phiếu đang nằm ở đâu</span>
    <span class="ugfm-lg"><span class="m-doc done ugfm-mini"><span class="s">Kết thúc</span></span> phiếu đã xong</span>
    <span class="ugfm-lg"><span class="ugfm-act-mini">hành động</span> người dùng bấm để đi tiếp</span>
    <span class="ugfm-lg"><span class="m-eff up ugfm-mini">▲ tăng</span><span class="m-eff down ugfm-mini">▼ giảm</span> tồn kho</span>
</div>

<div class="ugfm">
    <div class="ugfm-bar">
        <div class="ugfm-keys">
            <button type="button" class="ugfm-key k1" data-lane="du-tru"><span class="bnum">1</span> Dự trù mua hàng</button>
            <button type="button" class="ugfm-key k2" data-lane="vpp"><span class="bnum">2</span> Đề nghị VPP đến HC</button>
            <button type="button" class="ugfm-key k2" data-lane="lien-phong-ban"><span class="bnum">2</span> Liên phòng ban</button>
            <button type="button" class="ugfm-key k3" data-lane="noi-bo"><span class="bnum">3</span> Đề nghị nội bộ</button>
            <button type="button" class="ugfm-key k4" data-lane="cach-ly"><span class="bnum">4</span> Cách ly - Loại bỏ - Huỷ</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary ugfm-full-btn" title="Xem sơ đồ toàn màn hình (Esc để thoát)">
            <i class="fas fa-expand mr-1"></i> <span>Toàn màn hình</span>
        </button>
    </div>

    <div class="m-wrap">
        <svg class="m-links" aria-hidden="true"></svg>
        <table class="matrix">
            <thead>
                <tr>
                    <th class="m-corner">Đường đi \ Trang</th>
                    <th class="m-col DM"><span class="page-ico"></span>Danh Mục › Vật Tư</th>
                    <th class="m-col TK"><span class="page-ico"></span>Tồn Kho › Vật Tư</th>
                    <th class="m-col SD"><span class="page-ico"></span>Đề Nghị - Sử Dụng › Vật Tư</th>
                    <th class="m-col DT"><span class="page-ico"></span>Dự Trù › Vật Tư</th>
                    <th class="m-col NH"><span class="page-ico"></span>Nhập › Vật Tư</th>
                    <th class="m-col KQ"><span class="page-ico"></span>Tồn kho thay đổi</th>
                </tr>
            </thead>
            <tbody>
                {{-- 1. Dự trù mua hàng --}}
                <tr class="m-row m1" data-lane="du-tru">
                    <th class="m-lane"><span class="bnum">1</span><span class="m-lane-t"><b>Dự trù mua hàng</b><span>bộ phận mua hàng mua về</span></span></th>
                    <td class="m-cell DM"><div class="m-flow">
                        <a class="m-src m-node" data-to="1" href="#danh-sach-chu-ky"><b>DS dự trù theo chu kỳ</b><span>tự lập phiếu Nháp</span></a>
                        <a class="m-src m-node" data-to="0" href="#vat-tu-cua-phong"><b>Vật tư dưới ngưỡng tối thiểu</b><span>tự động</span></a>
                        <a class="m-src m-node" data-to="0" href="#vat-tu-cua-phong"><b>Nút Đề nghị dự trù</b><span>người dùng</span></a>
                    </div></td>
                    <td class="m-cell TK"><div class="m-flow">
                        <a class="m-src m-node" data-to="0" href="#kha-dung-thang-toi"><b>Dự báo thiếu tồn tháng tới</b><span>tự động</span></a>
                    </div></td>
                    <td class="m-cell SD"><div class="m-flow">
                        <a class="m-src m-node" data-to="0" href="#de-nghi-noi-bo"><b>Nút Đề nghị dự trù trên dòng đề nghị</b><span>người dùng</span></a>
                    </div></td>
                    <td class="m-cell DT"><div class="m-flow">
                        <div class="m-act"><span>gom · Cung Ứng mua</span></div>
                        <a class="m-doc m-node" data-n="0" href="#can-du-tru"><span class="k">tab DS vật tư cần dự trù</span><span class="s">Chưa lập phiếu</span></a>
                        <div class="m-act"><span>lập phiếu</span></div>
                        <a class="m-doc m-node" data-n="1" href="#du-tru"><span class="k">Chi tiết phiếu</span><span class="s">Nháp</span></a>
                        <div class="m-act"><span>trình ký</span></div>
                        <a class="m-doc m-node" data-n="2" href="#du-tru"><span class="k">Chi tiết phiếu</span><span class="s">Chờ QLBP ký</span></a>
                        <div class="m-act"><span>ký bước 1</span></div>
                        <a class="m-doc m-node" data-n="3" href="#du-tru"><span class="k">Chi tiết phiếu</span><span class="s">Chờ BGĐ ký</span></a>
                        <div class="m-act"><span>ký bước 2</span></div>
                        <a class="m-doc m-node" data-n="4" href="#theo-doi-du-tru"><span class="k">Chi tiết phiếu</span><span class="s">Đã phê duyệt</span></a>
                        <div class="m-act"><span>mua hàng tiếp nhận</span></div>
                        <a class="m-doc m-node" data-n="5" href="#theo-doi-du-tru"><span class="k">tab Đơn dự trù đã được phê duyệt</span><span class="s">Đang giải quyết</span></a>
                    </div></td>
                    <td class="m-cell NH"><div class="m-flow">
                        <div class="m-act"><span>hàng về · lập phiếu nhập</span></div>
                        <a class="m-doc m-node" data-n="6" href="#cho-kiem-tra"><span class="k">tab Chờ kiểm tra</span><span class="s">Chờ kiểm tra</span></a>
                        <div class="m-act"><span>kiểm tra Đạt</span></div>
                        <a class="m-doc m-node" data-n="7" href="#nhap-vat-tu"><span class="k">tab Sổ nhập vật tư</span><span class="s">Lô vào tồn</span></a>
                        <div class="m-act"><span>xác nhận ở Dự Trù</span></div>
                        <a class="m-doc m-node done" data-n="8" href="#theo-doi-du-tru"><span class="k">tab Theo dõi dự trù</span><span class="s">Hoàn thành</span></a>
                    </div></td>
                    <td class="m-cell KQ"><div class="m-flow">
                        <div class="m-eff m-node up" data-from="7">▲ Tồn kho phòng tăng</div>
                    </div></td>
                </tr>

                {{-- 2a. Đề nghị VPP đến HC --}}
                <tr class="m-row m2" data-lane="vpp">
                    <th class="m-lane"><span class="bnum">2</span><span class="m-lane-t"><b>Đề nghị VPP đến HC</b><span>chuyển kho · Hành Chánh mua</span></span></th>
                    <td class="m-cell DM"><div class="m-flow">
                        <a class="m-src m-node" data-to="1" href="#danh-sach-chu-ky"><b>DS liên phòng ban theo chu kỳ</b><span>phòng cấp là HC</span></a>
                        <a class="m-src m-node" data-to="0" href="#vat-tu-cua-phong"><b>Dưới ngưỡng · nút Đề nghị dự trù</b><span>vật tư HC mua</span></a>
                    </div></td>
                    <td class="m-cell TK"><div class="m-flow">
                        <a class="m-src m-node" data-to="0" href="#kha-dung-thang-toi"><b>Dự báo thiếu tồn</b><span>vật tư HC mua</span></a>
                    </div></td>
                    <td class="m-cell SD"><div class="m-flow">
                        <div class="m-act"><span>gom · HC mua</span></div>
                        <a class="m-doc m-node" data-n="0" href="#de-nghi-vpp"><span class="k">DS vật tư cần đề nghị Hành Chính</span><span class="s">Chưa đề nghị</span></a>
                        <div class="m-act"><span>lập</span></div>
                        <a class="m-doc m-node" data-n="1" href="#de-nghi-vpp"><span class="k">tab VPP đến HC</span><span class="s">Lưu tạm</span></a>
                        <div class="m-act"><span>gửi</span></div>
                        <a class="m-doc m-node" data-n="2" href="#de-nghi-vpp"><span class="k">tab VPP đến HC</span><span class="s">Chờ ký</span></a>
                        <div class="m-act"><span>ký duyệt</span></div>
                        <a class="m-doc m-node" data-n="3" href="#cho-cap-phat"><span class="k">Hành Chánh · gửi đến</span><span class="s">Chờ cấp phát</span></a>
                        <div class="m-act"><span>HC xuất chuyển kho</span></div>
                        <a class="m-doc m-node" data-n="4" href="#de-nghi-vpp"><span class="k">tab VPP đến HC</span><span class="s">Chờ nhận</span></a>
                        <div class="m-act"><span>nhận · kiểm tra</span></div>
                        <a class="m-doc m-node done" data-n="5" href="#de-nghi-vpp"><span class="k">tab VPP đến HC</span><span class="s">Đã cấp đủ</span></a>
                    </div></td>
                    <td class="m-cell DT pass"></td>
                    <td class="m-cell NH"><div class="m-flow">
                        <div class="m-act"><span>tự tạo lô</span></div>
                        <a class="m-doc m-node" data-n="6" href="#nhap-vat-tu"><span class="k">Lô nhập chuyển kho</span><span class="s">Vào tồn phòng</span></a>
                    </div></td>
                    <td class="m-cell KQ"><div class="m-flow">
                        <div class="m-eff m-node down">▼ Kho VPP của HC giảm</div>
                        <div class="m-eff m-node up">▲ Phòng đề nghị tăng</div>
                    </div></td>
                </tr>

                {{-- 2b. Đề nghị liên phòng ban --}}
                <tr class="m-row m2" data-lane="lien-phong-ban">
                    <th class="m-lane"><span class="bnum">2</span><span class="m-lane-t"><b>Đề nghị liên phòng ban</b><span>chuyển kho · phòng A xin phòng B</span></span></th>
                    <td class="m-cell DM"><div class="m-flow">
                        <a class="m-src m-node" data-to="0" href="#danh-sach-chu-ky"><b>DS liên phòng ban theo chu kỳ</b><span>phòng cấp khác HC</span></a>
                    </div></td>
                    <td class="m-cell TK pass"></td>
                    <td class="m-cell SD"><div class="m-flow">
                        <a class="m-src m-node" data-to="0" href="#lien-phong-ban"><b>Bấm Tạo đề nghị</b><span>phòng A</span></a>
                        <div class="m-act"><span>lập</span></div>
                        <a class="m-doc m-node" data-n="0" href="#lien-phong-ban"><span class="k">tab Phòng ban khác</span><span class="s">Lưu tạm</span></a>
                        <div class="m-act"><span>gửi</span></div>
                        <a class="m-doc m-node" data-n="1" href="#lien-phong-ban"><span class="k">tab Phòng ban khác</span><span class="s">Chờ ký</span></a>
                        <div class="m-act"><span>ký duyệt</span></div>
                        <a class="m-doc m-node" data-n="2" href="#cho-cap-phat"><span class="k">Phòng B · gửi đến</span><span class="s">Chờ cấp phát</span></a>
                        <div class="m-act"><span>B xuất chuyển kho</span></div>
                        <a class="m-doc m-node" data-n="3" href="#lien-phong-ban"><span class="k">tab Phòng ban khác</span><span class="s">Chờ nhận</span></a>
                        <div class="m-act"><span>A nhận · kiểm tra</span></div>
                        <a class="m-doc m-node done" data-n="4" href="#lien-phong-ban"><span class="k">tab Phòng ban khác</span><span class="s">Đã cấp đủ</span></a>
                    </div></td>
                    <td class="m-cell DT pass"></td>
                    <td class="m-cell NH"><div class="m-flow">
                        <div class="m-act"><span>tự tạo lô</span></div>
                        <a class="m-doc m-node" data-n="5" href="#nhap-vat-tu"><span class="k">Lô nhập chuyển kho</span><span class="s">Vào tồn phòng A</span></a>
                    </div></td>
                    <td class="m-cell KQ"><div class="m-flow">
                        <div class="m-eff m-node down">▼ Tồn phòng B giảm</div>
                        <div class="m-eff m-node up">▲ Tồn phòng A tăng</div>
                    </div></td>
                </tr>

                {{-- 3. Đề nghị sử dụng nội bộ --}}
                <tr class="m-row m3" data-lane="noi-bo">
                    <th class="m-lane"><span class="bnum">3</span><span class="m-lane-t"><b>Đề nghị sử dụng nội bộ</b><span>định kỳ · ĐG rủi ro · thường quy</span></span></th>
                    <td class="m-cell DM"><div class="m-flow">
                        <a class="m-src m-node" data-to="pk" href="#danh-sach-chu-ky"><b>DS đề nghị nội bộ theo chu kỳ</b><span>tự động</span></a>
                    </div></td>
                    <td class="m-cell TK pass"></td>
                    <td class="m-cell SD"><div class="m-flow">
                        <div class="m-stack">
                            <a class="m-src m-node" data-to="0" data-sid="pk" href="#de-nghi-noi-bo"><b>Tab Theo định kỳ</b><span>hệ thống tự tạo</span></a>
                            <a class="m-src m-node" data-to="0" href="#de-nghi-noi-bo"><b>Tab Theo ĐG rủi ro</b><span>bấm Tạo đề nghị</span></a>
                            <a class="m-src m-node" data-to="0" href="#de-nghi-noi-bo"><b>Tab Thường quy</b><span>bấm Tạo đề nghị</span></a>
                        </div>
                        <div class="m-act"><span>lập</span></div>
                        <a class="m-doc m-node" data-n="0" href="#de-nghi-noi-bo"><span class="k">tab Đề nghị nội bộ</span><span class="s">Lưu tạm</span></a>
                        <div class="m-act"><span>trình ký</span></div>
                        <a class="m-doc m-node" data-n="1" href="#de-nghi-noi-bo"><span class="k">tab Đề nghị nội bộ</span><span class="s">Chờ ký duyệt</span></a>
                        <div class="m-act"><span>ký duyệt</span></div>
                        <a class="m-doc m-node" data-n="2" href="#de-nghi-noi-bo"><span class="k">tab Đề nghị nội bộ</span><span class="s">Đã duyệt</span></a>
                        <div class="m-act"><span>chuyển kho</span></div>
                        <a class="m-doc m-node" data-n="3" href="#cho-cap-phat"><span class="k">tab Chờ cấp phát</span><span class="s">Chờ cấp phát</span></a>
                        <div class="m-act"><span>kho cấp phát</span></div>
                        <a class="m-doc m-node done" data-n="4" href="#cho-cap-phat"><span class="k">tab Chờ cấp phát</span><span class="s">Đã cấp phát</span></a>
                        <div class="m-act"><span>ghi sổ</span></div>
                        <a class="m-doc m-node" data-n="5" href="#so-cap-phat"><span class="k">tab Sổ cấp phát</span><span class="s">Lượt cấp phát</span></a>
                    </div></td>
                    <td class="m-cell DT pass"></td>
                    <td class="m-cell NH pass"></td>
                    <td class="m-cell KQ"><div class="m-flow">
                        <div class="m-eff m-node down">▼ Tồn kho phòng giảm</div>
                    </div></td>
                </tr>

                {{-- 4. Cách ly - Loại bỏ - Huỷ --}}
                <tr class="m-row m4" data-lane="cach-ly">
                    <th class="m-lane"><span class="bnum">4</span><span class="m-lane-t"><b>Cách ly - Loại bỏ - Huỷ</b><span>hỏng · hết hạn · không đạt hiệu chuẩn</span></span></th>
                    <td class="m-cell DM pass"></td>
                    <td class="m-cell TK pass"></td>
                    <td class="m-cell SD"><div class="m-flow">
                        <div class="m-stack">
                            <a class="m-src m-node" data-to="0" href="#cach-ly"><b>Nhóm Vật tư sắp hết hạn dùng</b><span>lô còn tồn sát / quá hạn</span></a>
                            <a class="m-src m-node" data-to="0" href="#cach-ly"><b>Nút Cách ly vật tư hỏng</b><span>người dùng</span></a>
                            <a class="m-src m-node" data-to="0" href="#hieu-chuan"><b>Tab Xác nhận hiệu chuẩn · QA ký Không đạt</b><span>tự cách ly phần không đạt</span></a>
                        </div>
                        <div class="m-act"><span>lập phiếu · định khu Chờ QĐ</span></div>
                        <a class="m-doc m-node" data-n="0" href="#cach-ly"><span class="k">tab Cách ly - Loại bỏ - Huỷ</span><span class="s">Cách ly - Chờ quyết định</span></a>
                        <div class="m-branch">
                            <div class="m-line">
                                <div class="m-act"><span>QĐ loại bỏ · định khu Loại bỏ</span></div>
                                <a class="m-doc m-node" data-n="1" href="#cach-ly"><span class="k">tab Cách ly - Loại bỏ - Huỷ</span><span class="s">Đã loại bỏ - chờ huỷ</span></a>
                                <div class="m-act"><span>huỷ · ghi phương pháp</span></div>
                                <a class="m-doc m-node done" data-n="2" href="#cach-ly"><span class="k">tab Cách ly - Loại bỏ - Huỷ</span><span class="s">Đã huỷ</span></a>
                            </div>
                            <div class="m-line">
                                <div class="m-act"><span>QĐ trả về kho · chọn định khu</span></div>
                                <a class="m-doc m-node alt" data-from="0" href="#cach-ly"><span class="k">tab Cách ly - Loại bỏ - Huỷ</span><span class="s">Đã trả về kho</span></a>
                            </div>
                        </div>
                    </div></td>
                    <td class="m-cell DT pass"></td>
                    <td class="m-cell NH pass"></td>
                    <td class="m-cell KQ"><div class="m-flow">
                        <div class="m-eff m-node down" data-from="2">▼ Tồn kho phòng giảm<br><small>trừ ngay từ lúc cách ly</small></div>
                        <div class="m-eff m-node up" data-from="alt">▲ Hoàn lại tồn kho<br><small>hàng về định khu đã chọn</small></div>
                    </div></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<h3>Đọc sơ đồ theo từng đường đi</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 210px">Đường đi</th><th>Diễn giải</th><th style="width: 200px">Tồn kho</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="ugfm-no k1">1</span> Dự trù mua hàng</td>
                <td>Vật tư cần mua được gom vào <a href="#can-du-tru">Danh sách vật tư cần dự trù</a> từ 3 nguồn (dưới ngưỡng,
                    người dùng đề nghị, dự báo thiếu tháng tới), hoặc lập sẵn bằng <a href="#danh-sach-chu-ky">DS dự trù theo chu kỳ</a>.
                    Phiếu <a href="#du-tru">ký 2 bước</a> rồi chuyển tới bộ phận mua hàng
                    <a href="#theo-doi-du-tru">tiếp nhận</a>. Hàng về được <a href="#nhap-vat-tu">nhập kho</a>,
                    <a href="#cho-kiem-tra">kiểm tra Đạt</a> thì mới vào tồn; phòng đề nghị bấm <b>Hoàn thành</b> mục dự trù.</td>
                <td>Tăng khi lô kiểm tra Đạt.</td>
            </tr>
            <tr>
                <td><span class="ugfm-no k2">2</span> Đề nghị VPP đến HC</td>
                <td>Văn phòng phẩm do Hành Chánh mua không lập dự trù mà xin thẳng kho của Hành Chánh
                    (<a href="#de-nghi-vpp">Đề nghị VPP</a>). Hành Chánh xuất chuyển kho, phòng đề nghị nhận bằng cách
                    <b>xác nhận kiểm tra</b>; hệ thống tự tạo lô nhập chuyển kho.</td>
                <td>Kho HC giảm, phòng đề nghị tăng.</td>
            </tr>
            <tr>
                <td><span class="ugfm-no k2">2</span> Đề nghị liên phòng ban</td>
                <td>Giống VPP nhưng phòng cấp là một phòng bất kỳ (<a href="#lien-phong-ban">Đề nghị liên phòng ban</a>).
                    Dòng đề nghị hiện ở tab <a href="#cho-cap-phat">Chờ cấp phát</a> của phòng cấp.</td>
                <td>Phòng cấp giảm, phòng xin tăng.</td>
            </tr>
            <tr>
                <td><span class="ugfm-no k3">3</span> Đề nghị sử dụng nội bộ</td>
                <td>Lấy vật tư trong kho của chính phòng để dùng. Đề nghị từ 3 tab
                    (<a href="#de-nghi-noi-bo">định kỳ, ĐG rủi ro, thường quy</a>), ký duyệt xong chuyển kho
                    <a href="#cho-cap-phat">cấp phát</a> và tự ghi <a href="#so-cap-phat">Sổ cấp phát</a>. Vật tư cần hiệu
                    chuẩn phải qua <a href="#hieu-chuan">QA xác nhận Đạt</a> trước.</td>
                <td>Giảm ngay khi kho cấp phát.</td>
            </tr>
            <tr>
                <td><span class="ugfm-no k4">4</span> Cách ly - Loại bỏ - Huỷ</td>
                <td>Vật tư hỏng, hết hạn hoặc QA ký <b>Không đạt</b> hiệu chuẩn được <a href="#cach-ly">cách ly</a> ở định
                    khu Chờ quyết định, sau đó quyết định <b>Loại bỏ</b> (rồi Huỷ) hoặc <b>Trả về kho</b>.</td>
                <td>Giảm từ lúc cách ly; trả về kho thì hoàn lại.</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Bước <b>ký duyệt</b> của đề nghị nội bộ theo Dữ Liệu Gốc <i>Trình Ký Đề Nghị Cấp Phát Vật Tư</i>; của đề nghị VPP và
        liên phòng ban theo <i>Trình Ký Đề Nghị Liên Phòng Ban</i>. Không có quy định nào áp dụng thì phiếu được duyệt thẳng,
        bỏ qua khối <b>Chờ ký</b>. Sơ đồ rộng: kéo ngang trong khung hoặc bấm <b>Toàn màn hình</b>.</div>
</div>

<style>
    .ugfm {
        --b1: #2E7D32;
        --b2: #1E5FA6;
        --b3: #C25E0A;
        --b4: #B3261E;
        --up: #1D6E36;
        --down: #B3261E;
        --fm-line: var(--border-soft, #E1ECF7);
        --fm-tag: #3E4A56;
        --fm-pg: var(--bg-neutral, #F5F9FD);
        --fm-mono: Consolas, "Roboto Mono", ui-monospace, monospace;
        margin: 6px 0 16px;
    }

    .ugfm-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 12px;
        margin-bottom: 10px;
    }

    .ugfm-keys {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .ugfm-key {
        --bc: var(--b1);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px 3px 4px;
        border: 1.5px solid var(--bc);
        border-radius: 999px;
        background: color-mix(in srgb, var(--bc) 8%, #fff);
        color: var(--text-main);
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
    }

    .ugfm-key.is-on {
        background: var(--bc);
        color: #fff;
    }

    .ugfm-key.is-on .bnum {
        background: #fff;
        color: var(--bc);
    }

    .ugfm-full-btn {
        margin-left: auto;
    }

    .ugfm .k1, .ugfm-no.k1 { --bc: var(--b1); }
    .ugfm .k2, .ugfm-no.k2 { --bc: var(--b2); }
    .ugfm .k3, .ugfm-no.k3 { --bc: var(--b3); }
    .ugfm .k4, .ugfm-no.k4 { --bc: var(--b4); }

    .ugfm-no {
        display: inline-grid;
        place-items: center;
        width: 22px;
        height: 22px;
        margin-right: 6px;
        border-radius: 50%;
        background: var(--bc);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .ugfm .bnum {
        display: inline-grid;
        place-items: center;
        flex: none;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--bc, var(--rc));
        color: #fff;
        font-size: 0.75rem;
        font-weight: 800;
    }

    /* ---------- Chú giải ---------- */
    .ugfm-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 20px;
        align-items: center;
        margin: 4px 0 12px;
        font-size: 0.82rem;
        color: var(--text-muted);
        --rc: #5B6875;
    }

    .ugfm-lg {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .ugfm-legend .ugfm-mini {
        display: inline-block;
        max-width: none;
        min-width: 0;
        margin: 0;
        padding: 2px 8px;
    }

    .ugfm-act-mini {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-main);
        border-bottom: 2px solid #7D8A97;
        padding-bottom: 2px;
    }

    /* ---------- Ma trận ---------- */
    .ugfm .m-wrap {
        position: relative;
        z-index: 0;
        overflow-x: auto;
        border: 1px solid var(--fm-line);
        border-radius: 10px;
        background: #fff;
    }

    .ugfm .m-links {
        position: absolute;
        left: 0;
        top: 0;
        z-index: 1;
        pointer-events: none;
        overflow: visible;
    }

    .ugfm .matrix {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 2300px;
        width: 100%;
        font-size: 12px;
        margin: 0;
    }

    .ugfm .matrix th,
    .ugfm .matrix td {
        vertical-align: middle;
        border-right: 1px dashed var(--fm-line);
        border-bottom: 1px solid var(--fm-line);
    }

    .ugfm .matrix td {
        padding: 30px 16px;
    }

    .ugfm .matrix thead th {
        padding: 9px 10px;
        background: var(--fm-tag);
        color: #fff;
        font: 600 11.5px/1.3 var(--fm-mono);
        text-align: left;
    }

    .ugfm .page-ico {
        display: inline-block;
        width: 11px;
        height: 9px;
        margin-right: 6px;
        vertical-align: -1px;
        border: 1.5px solid currentColor;
        border-top-width: 3px;
        border-radius: 2px;
    }

    .ugfm .m-corner {
        width: 190px;
    }

    .ugfm .m-col.DM {
        width: 200px;
    }

    .ugfm .m-col.TK {
        width: 170px;
    }

    .ugfm .m-col.SD {
        width: 760px;
    }

    .ugfm .m-col.DT,
    .ugfm .m-col.NH {
        width: 420px;
    }

    .ugfm .m-col.KQ {
        width: 160px;
    }

    .ugfm .m-cell.DM,
    .ugfm .m-cell.SD,
    .ugfm .m-cell.NH {
        background: var(--fm-pg);
    }

    .ugfm .m-cell.TK,
    .ugfm .m-cell.DT,
    .ugfm .m-cell.KQ {
        background: #fff;
    }

    .ugfm .m-row { --rc: var(--b1); }
    .ugfm .m-row.m2 { --rc: var(--b2); }
    .ugfm .m-row.m3 { --rc: var(--b3); }
    .ugfm .m-row.m4 { --rc: var(--b4); }

    .ugfm .m-row {
        transition: opacity 0.2s;
    }

    .ugfm.has-focus .m-row:not(.is-focus) {
        opacity: 0.22;
    }

    .ugfm .m-lane {
        position: sticky;
        left: 0;
        z-index: 3;
        padding: 16px 12px;
        background: color-mix(in srgb, var(--rc) 14%, #fff);
        border-left: 6px solid var(--rc);
        text-align: left;
        cursor: pointer;
    }

    .ugfm .m-lane .bnum {
        margin-right: 8px;
        vertical-align: middle;
    }

    .ugfm .m-lane-t {
        display: inline-grid;
        vertical-align: middle;
        max-width: 140px;
    }

    .ugfm .m-lane-t b {
        font-size: 13px;
        color: var(--text-main);
    }

    .ugfm .m-lane-t span {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 400;
    }

    .ugfm .m-flow {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        row-gap: 44px;
    }

    .ugfm .m-cell.KQ .m-flow {
        flex-direction: column;
        align-items: stretch;
        row-gap: 22px;
    }

    .ugfm .m-row.m4 .m-cell.SD .m-flow {
        flex-wrap: nowrap;
    }

    .ugfm .m-node,
    .ugfm .m-act {
        position: relative;
        z-index: 2;
    }

    a.m-node,
    a.m-node:hover {
        color: inherit;
        text-decoration: none;
    }

    a.m-node {
        transition: box-shadow 0.15s, transform 0.15s;
    }

    a.m-node:hover,
    a.m-node:focus-visible {
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--rc) 30%, transparent);
        transform: translateY(-1px);
        outline: none;
    }

    .m-doc {
        display: grid;
        gap: 1px;
        min-width: 92px;
        max-width: 120px;
        padding: 5px 8px;
        border-radius: 7px;
        border: 1.5px solid var(--rc);
        background: color-mix(in srgb, var(--rc) 14%, #fff);
    }

    .m-doc .k {
        font-size: 10px;
        color: var(--text-muted);
        line-height: 1.2;
    }

    .m-doc .s {
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        color: var(--text-main);
    }

    .m-doc.done {
        background: var(--rc);
    }

    .m-doc.done .k,
    .m-doc.done .s {
        color: #fff;
    }

    .m-doc.alt {
        border-style: dashed;
    }

    .m-src {
        display: grid;
        max-width: 150px;
        padding: 4px 8px;
        margin: 9px 18px 9px 0;
        border: 1.5px dashed var(--rc);
        border-radius: 7px;
        background: #fff;
    }

    .m-src b {
        font-size: 11.5px;
        line-height: 1.25;
    }

    .m-src span {
        font-size: 10.5px;
        color: var(--text-muted);
        font-style: italic;
    }

    .ugfm .m-stack {
        display: grid;
        gap: 18px;
    }

    .ugfm .m-act {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 58px;
        margin: 0 8px;
        text-align: center;
    }

    .ugfm .m-act span {
        width: 100%;
        padding: 1px 2px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.15;
        color: var(--text-main);
    }

    .ugfm .m-cell.DM .m-act span,
    .ugfm .m-cell.SD .m-act span,
    .ugfm .m-cell.NH .m-act span {
        background: var(--fm-pg);
    }

    .ugfm .m-cell.TK .m-act span,
    .ugfm .m-cell.DT .m-act span {
        background: #fff;
    }

    .m-eff {
        padding: 6px 9px;
        margin: 2px 0;
        border-radius: 8px;
        color: #fff;
        font-size: 11.5px;
        font-weight: 700;
        line-height: 1.25;
    }

    .m-eff.up { background: var(--up, #1D6E36); }
    .m-eff.down { background: var(--down, #B3261E); }

    .m-eff small {
        font-size: 10.5px;
        font-weight: 500;
        opacity: 0.9;
    }

    .ugfm .m-branch {
        display: grid;
        row-gap: 44px;
    }

    .ugfm .m-line {
        display: flex;
        align-items: center;
    }

    /* ---------- Toàn màn hình ---------- */
    .ugfm.is-full {
        position: fixed;
        inset: 0;
        z-index: 1060;
        margin: 0;
        padding: 14px 16px;
        background: var(--bg-neutral, #F5F9FD);
        overflow: auto;
    }

    .ugfm.is-full .m-wrap {
        max-height: calc(100vh - 70px);
        overflow: auto;
    }

    body.ugfm-lock {
        overflow: hidden;
    }

    @media print {
        .ugfm-bar {
            display: none;
        }

        .ugfm .m-wrap {
            overflow: visible;
        }

        .ugfm .matrix {
            min-width: 0;
        }
    }
</style>

<script>
    /* Vẽ mũi tên nối từng khối của sơ đồ: nguồn -> khối đích, khối phiếu -> khối kế tiếp, khối cuối -> ô tồn kho.
       Đường đi né khối: thử đường cong thẳng trước; đụng khối khác thì bỏ.
       Cùng ô khác dòng: vòng qua khe trống giữa 2 dòng. Khác ô: lên kênh trống ở mép trên hàng, chạy ngang rồi thả xuống.
       Tính lại khi đổi cỡ, tải xong font, bật/tắt toàn màn hình. */
    (function() {
        var map = document.querySelector('.ugfm');
        if (!map) return;
        var wrap = map.querySelector('.m-wrap');
        var svg = wrap.querySelector('.m-links');
        var NS = 'http://www.w3.org/2000/svg';
        var base;

        function box(el) {
            var r = el.getBoundingClientRect();
            var l = r.left - base.left + wrap.scrollLeft,
                t = r.top - base.top + wrap.scrollTop;
            return { l: l, r: l + r.width, t: t, b: t + r.height, y: t + r.height / 2 };
        }

        function bez(a, b) {
            var dx = Math.max(18, (b.l - a.r) / 2);
            return [[a.r, a.y], [a.r + dx, a.y], [b.l - dx, b.y], [b.l - 2, b.y]];
        }

        function bezPoint(p, t) {
            var u = 1 - t;
            return [
                u * u * u * p[0][0] + 3 * u * u * t * p[1][0] + 3 * u * t * t * p[2][0] + t * t * t * p[3][0],
                u * u * u * p[0][1] + 3 * u * u * t * p[1][1] + 3 * u * t * t * p[2][1] + t * t * t * p[3][1]
            ];
        }

        function hits(points, obstacles) {
            return points.some(function(pt) {
                return obstacles.some(function(o) {
                    return pt[0] > o.l - 3 && pt[0] < o.r + 3 && pt[1] > o.t - 3 && pt[1] < o.b + 3;
                });
            });
        }

        function sampleLine(pts) {
            var out = [];
            for (var i = 0; i + 1 < pts.length; i++) {
                for (var k = 0; k <= 12; k++) {
                    out.push([pts[i][0] + (pts[i + 1][0] - pts[i][0]) * k / 12, pts[i][1] + (pts[i + 1][1] - pts[i][1]) * k / 12]);
                }
            }
            return out;
        }

        function poly(pts) {
            return 'M' + pts.map(function(p) { return p[0] + ',' + p[1]; }).join(' L');
        }

        function route(aEl, bEl, row, obstacles) {
            var a = box(aEl), b = box(bEl);

            if (b.l > a.r + 4) {
                var c = bez(a, b), samples = [];
                for (var t = 0.04; t < 0.97; t += 0.04) samples.push(bezPoint(c, t));
                if (!hits(samples, obstacles)) {
                    return 'M' + c[0] + ' C' + c[1] + ' ' + c[2] + ' ' + c[3];
                }
            }

            var cellA = aEl.closest('td'), cellB = bEl.closest('td');

            if (cellA === cellB) {
                var items = Array.prototype.slice.call(cellA.querySelectorAll('.m-node, .m-act span')).map(box);
                var above = items.filter(function(o) { return o.b <= b.t + 1; });
                var top = above.length ? Math.max.apply(null, above.map(function(o) { return o.b; })) : a.b;
                var gapY = (top + b.t) / 2;
                var xr = Math.min(box(cellA).r - 6, a.r + 10), xl = b.l - 14;
                return poly([[a.r, a.y], [xr, a.y], [xr, gapY], [xl, gapY], [xl, b.y], [b.l - 2, b.y]]);
            }

            var channel = box(row).t + 12;
            var exitX = box(cellA).r - 8;
            var dropX = b.l - 14;
            var pts = [[a.r, a.y], [exitX, a.y], [exitX, channel], [dropX, channel], [dropX, b.y], [b.l - 2, b.y]];
            if (hits(sampleLine([[dropX, channel], [dropX, b.y]]), obstacles)) {
                var cb = box(cellB);
                var flow = Array.prototype.slice.call(cellB.querySelectorAll('.m-node')).map(box);
                var up = flow.filter(function(o) { return o.b <= b.t + 1; });
                var gy = up.length ? (Math.max.apply(null, up.map(function(o) { return o.b; })) + b.t) / 2 : b.t - 10;
                pts = [[a.r, a.y], [exitX, a.y], [exitX, channel], [cb.l + 6, channel], [cb.l + 6, gy], [dropX, gy], [dropX, b.y], [b.l - 2, b.y]];
            }
            return poly(pts);
        }

        function draw() {
            if (!wrap.offsetParent && !map.classList.contains('is-full')) return;
            base = wrap.getBoundingClientRect();
            svg.setAttribute('width', wrap.scrollWidth);
            svg.setAttribute('height', wrap.scrollHeight);
            svg.innerHTML = '';
            var defs = document.createElementNS(NS, 'defs');
            svg.appendChild(defs);

            wrap.querySelectorAll('tr.m-row').forEach(function(row, ri) {
                var color = getComputedStyle(row).getPropertyValue('--rc').trim() || '#5B6875';
                var mid = 'ugfmArrow' + ri;
                var m = document.createElementNS(NS, 'marker');
                m.setAttribute('id', mid);
                m.setAttribute('viewBox', '0 0 10 10');
                m.setAttribute('refX', '9');
                m.setAttribute('refY', '5');
                m.setAttribute('markerWidth', '7');
                m.setAttribute('markerHeight', '7');
                m.setAttribute('orient', 'auto-start-reverse');
                var tri = document.createElementNS(NS, 'path');
                tri.setAttribute('d', 'M0,0 L10,5 L0,10 z');
                tri.setAttribute('fill', color);
                m.appendChild(tri);
                defs.appendChild(m);

                var cards = Array.prototype.slice.call(row.querySelectorAll('.m-node'));
                var docs = Array.prototype.slice.call(row.querySelectorAll('.m-doc[data-n]'));
                var alts = Array.prototype.slice.call(row.querySelectorAll('.m-doc[data-from]'));
                var edges = [];

                row.querySelectorAll('.m-src[data-to]').forEach(function(s) {
                    var to = s.getAttribute('data-to');
                    var target = /^\d+$/.test(to) ? docs[+to] : row.querySelector('.m-src[data-sid="' + to + '"]');
                    if (target && target !== s) edges.push([s, target]);
                });
                for (var i = 0; i + 1 < docs.length; i++) edges.push([docs[i], docs[i + 1]]);
                alts.forEach(function(d) {
                    var f = docs[+d.getAttribute('data-from')];
                    if (f) edges.push([f, d]);
                });
                if (docs.length) row.querySelectorAll('.m-eff').forEach(function(e) {
                    var from = e.getAttribute('data-from');
                    var src = from === null ? docs[docs.length - 1] : (from === 'alt' ? alts[0] : docs[+from]);
                    if (src) edges.push([src, e]);
                });

                edges.forEach(function(e) {
                    var obstacles = cards.filter(function(c) { return c !== e[0] && c !== e[1]; }).map(box);
                    var p = document.createElementNS(NS, 'path');
                    p.setAttribute('d', route(e[0], e[1], row, obstacles));
                    p.setAttribute('fill', 'none');
                    p.setAttribute('stroke', color);
                    p.setAttribute('stroke-width', '1.8');
                    p.setAttribute('stroke-linejoin', 'round');
                    p.setAttribute('marker-end', 'url(#' + mid + ')');
                    p.setAttribute('class', 'ugfm-edge');
                    p.dataset.lane = row.dataset.lane;
                    svg.appendChild(p);
                });
            });
            syncFocus();
        }

        /* Làm nổi một đường đi: bấm nút chú giải / đầu hàng, hoặc liên kết data-ug-lane từ mục khác */
        var focusLane = null;

        function syncFocus() {
            map.classList.toggle('has-focus', !!focusLane);
            map.querySelectorAll('tr.m-row').forEach(function(r) {
                r.classList.toggle('is-focus', r.dataset.lane === focusLane);
            });
            map.querySelectorAll('.ugfm-key').forEach(function(k) {
                k.classList.toggle('is-on', k.dataset.lane === focusLane);
            });
            svg.querySelectorAll('.ugfm-edge').forEach(function(p) {
                p.style.opacity = focusLane && p.dataset.lane !== focusLane ? '0.15' : '';
            });
        }

        function setFocus(lane) {
            focusLane = lane && lane !== focusLane ? lane : null;
            syncFocus();
        }

        map.querySelectorAll('.ugfm-key').forEach(function(k) {
            k.addEventListener('click', function() { setFocus(k.dataset.lane); });
        });
        map.querySelectorAll('.m-lane').forEach(function(th) {
            th.addEventListener('click', function() { setFocus(th.closest('tr').dataset.lane); });
        });
        document.addEventListener('click', function(e) {
            var link = e.target.closest('[data-ug-lane]');
            if (!link) return;
            focusLane = null;
            setFocus(link.getAttribute('data-ug-lane'));
            var row = map.querySelector('tr.m-row[data-lane="' + focusLane + '"]');
            if (row) setTimeout(function() {
                var first = row.querySelector('.m-doc');
                if (first) wrap.scrollLeft = Math.max(0, box(first).l - 260);
            }, 300);
        });

        /* Toàn màn hình */
        var fullBtn = map.querySelector('.ugfm-full-btn');

        function setFull(on) {
            map.classList.toggle('is-full', on);
            document.body.classList.toggle('ugfm-lock', on);
            fullBtn.querySelector('i').className = on ? 'fas fa-compress mr-1' : 'fas fa-expand mr-1';
            fullBtn.querySelector('span').textContent = on ? 'Thu nhỏ' : 'Toàn màn hình';
            draw();
        }

        fullBtn.addEventListener('click', function() { setFull(!map.classList.contains('is-full')); });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && map.classList.contains('is-full')) setFull(false);
        });

        var timer;

        function later() {
            clearTimeout(timer);
            timer = setTimeout(draw, 60);
        }

        draw();
        window.addEventListener('resize', later);
        window.addEventListener('load', draw);
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(draw);
        if (window.ResizeObserver) new ResizeObserver(later).observe(wrap.querySelector('table'));
        window.addEventListener('beforeprint', draw);
    })();
</script>
