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
