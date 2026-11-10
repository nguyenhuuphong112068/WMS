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

### 10/11/2026 - Bỏ ô "Số Hồ Sơ" khỏi form Sửa Danh Mục Hoá Chất Công Ty
- Dữ Liệu Gốc/Danh Mục › Hoá Chất Công Ty: form Cập Nhật không còn ô Số Hồ Sơ (doc_no); bỏ khỏi validate, payload, message và
  dòng "Số tài liệu" trong chi tiết. Cột DB `doc_no` giữ nguyên (giá trị cũ không bị xoá khi sửa).
- Code: `ChemicalCategory/update.blade.php`, `ChemicalCategoryController.php`.
- Hướng dẫn: `chemical/sections/companyCategory.blade.php` đã xoá dòng 'Số Hồ Sơ' ở bảng trường (cần soát lại cùng đợt) +
  kiểm tra ảnh form Sửa có ô Số Hồ Sơ cần chụp lại.

### 10/11/2026 - Bỏ dòng mã hoạt chất (Axxxx) ở tab Tồn - Đối Chiếu Ngưỡng
- Tồn Kho Hoá Chất › tab Đối chiếu ngưỡng PL IV: cột Hoạt Chất không còn dòng mã A (vd. A00076) dưới tên + badge Nhóm 9;
  vẫn còn dòng "Gộp tồn của: ..." và cảnh báo lô chưa quy đổi.
- Code: `ThresholdReconciliation/pane.blade.php`.
- Hướng dẫn: `chemical#ton-kho` (đoạn tab Đối chiếu ngưỡng nếu nhắc mã A). Ảnh: chụp lại ảnh tab Đối chiếu ngưỡng.

### 10/11/2026 - Nhập Vật Tư: đổi tab + nút; modal Sản Phẩm - Chỉ Tiêu: cột NSX + chọn nhiều từ danh mục phòng
- Nhập › Nhập Vật Tư: tab "Chờ kiểm tra" đổi tên "Nhập Vật Tư" và đứng TRƯỚC tab "Sổ nhập vật tư"; nút [Nhập vật tư] chuyển từ tab Sổ nhập sang tab "Nhập Vật Tư".
- Danh Mục (HC + CC) › tab Danh sách theo sản phẩm, modal Thêm/Cập nhật Sản Phẩm - Chỉ Tiêu: bảng có cột Nhà Sản Xuất (ô chọn cũng tìm theo NSX);
  thêm nút "Danh mục phòng" mở hộp chọn nhiều hoá chất/chất chuẩn cùng lúc (tìm kiếm, tick, Thêm đã chọn).
- Code: `import/MaterialImport/dataTable.blade.php`, `category/shared/productListForm.blade.php`, `Support/ProductList.php`.
- Hướng dẫn: `import_material` (tên tab, vị trí nút), `chemical/standard#danh-sach-theo-san-pham`. Ảnh: chụp lại trang Nhập Vật Tư + modal Sản Phẩm - Chỉ Tiêu.

- Tồn › Tồn Kho Vật Tư, tab "Tồn kho theo vị trí": đổi sang sơ đồ lưới giống Tồn Kho Hoá Chất/Chất Chuẩn (dùng chung `inventory/shared/zoneGrid`: ô vị trí màu theo trạng thái, nút Thẻ | 3D | Mặt bằng, khối Chưa xếp vị trí thu gọn được, hộp chi tiết vị trí). Hàng cách ly/chờ huỷ hiện ở khu tương ứng (cách ly = vàng, chờ huỷ = đỏ).
- Code: `MaterialInventoryController` (zoneMap -> zoneRows), `MaterialInventory/dataTable.blade.php`, xoá `zoneMap.blade.php` + `zoneDetail.blade.php`.
- Hướng dẫn: `material` mục Tồn kho (pane miPaneZone). Ảnh: chụp lại tab Tồn kho theo vị trí.

### 10/11/2026 - Danh Mục Vật Tư: Tổ quản lý cho DS đề nghị nội bộ theo chu kỳ
- Modal Thêm/Cập nhật "Danh Sách Đề Nghị Nội Bộ Theo Chu Kỳ": thêm ô chọn bắt buộc "Tổ Quản Lý" (tổ đang hoạt động của phòng); đề nghị tự tạo mang tổ này (material_request_lists.group_id).
- Tab "DS đề nghị nội bộ theo chu kỳ": thêm cột Tổ Quản Lý + bộ lọc "Tổ quản lý" ở toolbar. Danh sách cũ chưa có tổ phải chọn tổ khi sửa.
- Code: migration `add_group_id_to_material_periodic_request_list_table`, `Support/MaterialPeriodicRequest.php`, `PeriodicRequestController`, `category/PeriodicRequest/{form,dataTable,assets}.blade.php`.
- Hướng dẫn: `material` mục Danh Mục › DS đề nghị nội bộ theo chu kỳ (bảng ô nhập, cột bảng, lỗi "Vui lòng chọn tổ quản lý"). Ảnh: chụp lại tab + modal.

### 10/11/2026 - Tồn Kho Vật Tư: thêm tab "Cận hạn dùng"
- Tab mới `expiring` (pane `miPaneExpiring`) giống Tồn Kho Hoá Chất/Chất Chuẩn: lô còn tồn có hạn dùng dưới 6 tháng (kể cả quá hạn), chip lọc ≤ 3 tháng / 1 tháng / 2 tuần / 1 tuần, lọc theo phân loại vật tư; quyền con `inventory_material_tab_expiring`.
- Code: `PageTabs`, migration `seed_material_inventory_expiring_tab`, `MaterialInventoryController` (expiringFilters), `MaterialInventory/{list,dataTable,classificationFilter}.blade.php`.
- Hướng dẫn: `material` mục Tồn kho (bảng "Ba tab xem tồn" -> bốn tab, pane thêm miPaneExpiring). Ảnh: chụp lại.
