# Thay đổi chờ cập nhật Hướng Dẫn Sử Dụng

Từ 10/11/2026 người dùng TẠM NGƯNG việc sửa hướng dẫn sử dụng (section, toc, ảnh chụp) sau mỗi thay đổi giao diện.
Mọi thay đổi người dùng nhìn thấy ghi vào file này; khi có lệnh thì cập nhật hướng dẫn theo danh sách dưới đây
(sửa section + chụp lại ảnh + `php artisan wms:user-guide-check`), xong thì xoá các mục đã làm.

Mỗi mục: ngày · màn hình / tab · thay đổi · mục hướng dẫn bị ảnh hưởng (tài liệu#id) · ảnh cần chụp lại.

## Đang chờ

### 10/11/2026 - Kết luận tương kỵ trong hộp chi tiết vị trí (Tồn Kho Hoá Chất › tab Theo định khu)
- Khối đỏ trong hộp chi tiết một vị trí (bấm thẻ ở chế độ Thẻ / thùng hàng ở 3D, Mặt bằng) đổi thành **kết luận**:
  tiêu đề "Kết luận: N cặp hoá chất tương kỵ nằm cùng tầng (Sơ đồ lưu trữ GHS) - nên tách ra tầng khác"; mỗi cặp hoá chất
  một dòng (không lặp A ✕ B rồi B ✕ A): "<Hoá chất A> mã (ở vị trí) có thuộc tính [chip...] **tương kỵ với** thuộc tính
  [chip...] của <Hoá chất B> mã (ở vị trí)", dòng "Chi tiết: thuộc tính A ✕ các thuộc tính B · ...".
- Code: `resources/views/pages/inventory/shared/zoneGrid.blade.php` (conflictConclusion), CSS `zoneMapStyles.blade.php`.
- Hướng dẫn: `chemical#ton-kho` (section `chemical/sections/inventory.blade.php`, đoạn "Tương kỵ cùng tầng" trong ô tab
  Theo định khu: câu "hộp chi tiết vị trí có khối đỏ liệt kê..." → mô tả kết luận theo cặp + chip thuộc tính).
- Ảnh: nên thêm ảnh hộp chi tiết có kết luận tương kỵ (vd. `ton-kho-tuong-ky.png`, phòng QC2 (9), vị trí
  Tủ dự trữ rắn/01/01/A1 đang có Acetonitril + Lead (II) nitrate) - khai `extraShots` trong `chemical/toc.php`.

### 10/11/2026 - Chế độ Thẻ tab Theo định khu vẽ lại kiểu lưới Cấu trúc kho + Thêm hoá chất / Đổi vị trí
- Áp cho Tồn Kho Hoá Chất và Tồn Kho Chất Chuẩn (cùng `shared/zoneGrid`); Chất Chuẩn chỉ xem.
- Bỏ thẻ to từng vị trí. Mỗi Kệ/Tủ (xếp ngang cạnh nhau) → mỗi Cột/cánh một khối: hàng = Tầng (theo chiều đánh số tầng
  của kệ), mỗi tầng = lưới ô Ngang (A, B...) × Sâu (1 = sát cửa) như Dữ Liệu Gốc › Định Khu › Cấu trúc kho. Ô có hàng tô
  màu tình trạng (xanh = có hàng, cam = cần chú ý, đỏ = hết hạn/vượt), số trong ô = số mã; vạch đáy = màu phân loại định
  khu; ô tương kỵ viền đỏ + dấu "!"; rê chuột xem tooltip mã vị trí + hoá chất. Bộ lọc làm mờ ô không khớp, ẩn tầng/cột/kệ
  không còn ô khớp.
- Bấm ô (cả ô trống khi có quyền) → hộp chi tiết có nút **Thêm hoá chất** (hộp chọn nhiều mã Chưa xếp vị trí, ô tìm, cảnh
  báo tương kỵ cùng tầng từng mã) và cột chọn mã + nút **Đổi vị trí N mã đã chọn** → hộp Đổi Vị Trí (Vị trí mới + Lý do
  bắt buộc). Kéo thẻ Chưa xếp vị trí (hoặc các thẻ đang chọn) thả vào ô = định khu (hỏi lại, cảnh báo tương kỵ); kéo ô có
  hàng thả sang ô khác = mở hộp Đổi Vị Trí điền sẵn vị trí mới. Ô khu Biệt Trữ / Cách Ly Chờ Quyết Định / Loại Bỏ không
  thêm / đổi được.
- Server: `ChemicalInventoryController::moveLocation` (route `pages.inventory.chemicalInventory.moveLocation`), quyền
  `inventory_chemical_location`; lịch sử phiếu nhập + Audit Trail action "Đổi vị trí" (ghi vị trí cũ → mới + lý do).
- Hướng dẫn: `chemical#ton-kho` (đoạn tab Theo định khu: mô tả lưới mới, Thêm hoá chất, Đổi vị trí, kéo thả; câu "Muốn đổi
  chỗ ... Điều chỉnh phiếu nhập" nếu có), `standard#ton-kho...` (đoạn chế độ Thẻ của Tồn Kho Chất Chuẩn).
- Ảnh: chụp lại ảnh tab Theo định khu Hoá Chất (phòng QC2 (9)) + Chất Chuẩn; nên thêm ảnh hộp chi tiết có nút Thêm / Đổi
  vị trí và hộp Đổi Vị Trí.

### 10/11/2026 - Chọn nhiều ô bằng Shift + quét để đổi vị trí cùng lúc (Tồn Kho Hoá Chất › Theo định khu › Thẻ)
- Shift + giữ chuột kéo khung quét trên sơ đồ (hoặc Shift / Ctrl + bấm từng ô) chọn các ô có hàng (viền vàng); Esc bỏ chọn.
  Thanh nổi "Đã chọn N ô · M mã [Đổi vị trí] [Bỏ chọn]".
- Kéo một ô đã chọn thả vào ô đích → hộp Đổi Vị Trí có 2 kiểu: **Dời nguyên khối** (mỗi ô sang ô tương ứng theo cùng độ lệch
  cột / tầng / ngang / sâu, báo "không đủ chỗ" nếu ra ngoài kệ hoặc rơi vào khu cách ly) hoặc **Gộp vào một vị trí**. Nút
  [Đổi vị trí] trên thanh = gộp vào một vị trí chọn ở ô Vị trí mới. Bảng trong hộp có thêm cột Vị trí hiện tại / Vị trí mới.
- Server: `moveLocation` nhận thêm `location_ids[]` (song song import_ids), một transaction; phản hồi có `groups`.
- Hướng dẫn: `chemical#ton-kho` (thêm bước chọn nhiều ô + 2 kiểu dời). Ảnh: thanh vùng chọn + hộp Đổi Vị Trí kiểu Dời nguyên khối.
