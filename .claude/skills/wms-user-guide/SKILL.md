---
name: wms-user-guide
description: Đọc mã nguồn các màn hình của dự án WMS rồi viết trang Hướng Dẫn Sử Dụng tích hợp ngay trong ứng dụng (menu "Hướng Dẫn Sử Dụng" trên leftNAV, route /userGuide/{nhóm}). Dùng khi người dùng muốn viết / bổ sung / cập nhật hướng dẫn sử dụng, tài liệu thao tác, help, manual cho một nhóm menu (Dữ Liệu Gốc, Danh Mục, Nhập, Sử Dụng, Tồn, Dự Trù, Thống Kê, Phân Quyền...) hoặc cho một màn hình cụ thể, hoặc khi một màn hình vừa đổi nghiệp vụ và hướng dẫn cần sửa theo (bắt buộc theo quy tắc 11 của wms-rules: mọi thay đổi chức năng người dùng thấy đều phải cập nhật hướng dẫn và chạy php artisan wms:user-guide-check). Gồm cấu trúc file, cách đọc controller/view để lấy đúng nghiệp vụ, khuôn nội dung từng mục, cách kiểm tra render và chụp màn hình offline.
---

# Viết Hướng Dẫn Sử Dụng tích hợp trong WMS

Luôn tuân theo skill `wms-rules` (Query Builder, cấm CDN, bảng màu, cấu trúc thư mục, giao tiếp tiếng Việt).

## 1. Kiến trúc đã có

| Thành phần | Đường dẫn |
|---|---|
| Route | `routes/userGuideRoute.php` → `pages.userGuide.list` (`/userGuide`), `pages.userGuide.show` (`/userGuide/{guide}`) |
| Controller | `app/Http/Controllers/Pages/UserGuide/UserGuideController.php`, hằng `GUIDES` = danh sách tài liệu |
| Trang danh sách | `resources/views/pages/userGuide/list.blade.php` (thẻ từng tài liệu) |
| CSS/JS dùng chung | `resources/views/pages/userGuide/shared/assets.blade.php` (mục lục sticky, scrollspy, lọc không dấu, nút lên đầu, in) |
| Đầu mục | `shared/sectionHead.blade.php`: icon, tên, đường menu, chip, nút "Mở màn hình" (tự ẩn khi thiếu quyền) |
| Bảng ô nhập | `shared/fields.blade.php`: `[['Tên ô', bắt buộc, 'giải thích HTML'], ...]` |
| Khung trang | `shared/page.blade.php`: hero + mục lục + vòng lặp mục. Index chỉ khai `$ugGroups` rồi `@include('pages.userGuide.shared.page')` (tuỳ chọn `['ugPathPrefix' => 'Dữ Liệu Gốc › ']`) |
| Một tài liệu | `resources/views/pages/userGuide/<khoá>/toc.php` (mục lục, `return [...]`) + `index.blade.php` (chỉ include khung) + `sections/<màn hình>.blade.php` |
| Nút "?" trên topNAV | `layout/topNAV.blade.php` + `App\Support\UserGuideLinks`: đọc mọi `toc.php`, dẫn route hiện tại tới mục có cùng `route` (hoặc `helpRoutes`), chọn theo tab đang hiện (`pane`), rồi `?tab=`; không khớp thì thử `<nhóm>.list`, cuối cùng mở danh sách hướng dẫn |
| Lệnh kiểm tra | `php artisan wms:user-guide-check` (`app/Console/Commands/UserGuideCheckCommand.php`) |
| Menu | leftNAV, nhóm "TRỢ GIÚP › Hướng Dẫn Sử Dụng" ở cuối menu |

Mẫu tham chiếu đầy đủ:
- `pages/userGuide/materData/`: tài liệu theo **một nhóm menu** (26 mục, mỗi mục một màn hình).
- `pages/userGuide/material/`: tài liệu theo **một module** trải trên nhiều nhóm menu (Danh Mục, Nhập, Sử Dụng, Tồn, Dự Trù, Thống Kê › Vật Tư; 24 mục, mỗi mục thường là một tab). Mỗi mục khai `path` riêng (ví dụ `'Nhập › Vật Tư › tab Chờ kiểm tra'`). Hoá chất và Chất chuẩn nên viết theo đúng khuôn này.

Nội dung lưu bằng Blade trong git, KHÔNG lưu CSDL. **Ngoại lệ duy nhất là bảng phân quyền**: đọc thẳng cấu hình Nhóm Quyền
qua `App\Support\PermissionMatrix`, cũng là class mà màn hình Phân Quyền › Nhóm Quyền đang dùng, để luôn khớp khi phân quyền đổi.
KHÔNG viết tay danh sách vai trò hay quyền vào Blade.

| Bảng phân quyền | Cách dùng |
|---|---|
| Mục "Quyền Truy Cập" của một tài liệu | `@include('pages.userGuide.shared.roleTable')`. Bảng có 2 cột **Vai trò \| Được làm gì**, gom theo nhóm chức năng, quyền tab tách sang dòng "Xem tab". `show()` truyền `$ugRoleSummary = PermissionMatrix::roleSummary(<khoá>)`. Khoá mới phải thêm vào danh sách `in_array` trong `show()`; phạm vi lấy theo `modelOf()` (material/chemical/standard/common) hoặc `materData`. |
| Tài liệu `permission` (Phân Quyền) | Ma trận vai trò × quyền chỉ để xem: `@include('pages.userGuide.shared.permissionMatrix', ['modelKey' => 'chemical'])`. Dữ liệu (`roles`, `tree`, `assigned`, `parentOf`, `roleCounts`) lấy từ `UserGuideController::permissionMatrix()`. |

Bảng chỉ tính quyền **có hiệu lực**: Admin có toàn quyền; quyền con phải đi kèm quyền cha (`PermissionMatrix::isEffective`), khớp
với `user_permission_names()`. Ô đã tick nhưng thiếu quyền cha hiện dấu tick nhạt.

## 2. Thêm một tài liệu mới (ví dụ nhóm "Danh Mục")

1. Thêm một dòng vào `UserGuideController::GUIDES`. Khoá trùng tiền tố route của nhóm menu (`category`, `import`, `export`...). Khai `title`, `icon` (cùng icon với mục cha trên leftNAV), `summary`, `updated_at` (ngày hôm nay, `Y-m-d`).
2. Chép `chemical/index.blade.php` và `chemical/toc.php` sang thư mục `<khoá>/`, sửa mảng `return [...]` trong `toc.php` (controller nạp qua `UserGuideLinks::groups()` thành `$ugGroups`):
   `['id' => 'neo-khong-dau', 'file' => 'tenFile', 'title' => 'Tên Màn Hình', 'icon' => '...', 'route' => 'pages.<nhóm>.<chứcNăng>.list', 'perm' => '<quyền xem như leftNAV>', 'chips' => [...], 'keywords' => 'tu khoa khong dau']`
   - `perm`: lấy đúng chuỗi trong `@perm(...)` của leftNAV; màn hình chỉ Admin thì ghi `'Admin'`.
   - `params`: tham số route cho nút "Mở màn hình", ví dụ `['tab' => 'prepare']`. CHỈ truyền khi controller/view thật sự đọc `request()->query('tab')`; hiện chỉ Sử Dụng và Dự Trù đọc, còn Danh Mục / Nhập / Tồn chọn tab qua session, nên mở trang chính.
   - `path` hiện "Dữ Liệu Gốc › ..." đang viết cứng trong index của materData, nhớ đổi tên nhóm.
   - `pane`: id vùng nội dung của tab trên màn hình (`id="impPaneCheck"`, `href="#tabCompany"`, `'pane' => 'mePaneVpp'`...), chuỗi hoặc mảng nếu mục gom nhiều tab. Bắt buộc với màn hình nhiều tab để nút "?" mở đúng mục theo tab đang xem.
   - `helpRoutes`: route trang con / trang khác cũng dẫn về mục này (ví dụ `pages.inventory.materialStocktake.detail`, `pages.user.role.list`), được ưu tiên trước `route`.
   - Luôn có nhóm "Bắt đầu" (Tổng quan + thứ tự thao tác, Thao tác chung, Quyền) và cuối cùng là "Lỗi Thường Gặp".
   - Mục Quyền: 1 câu dẫn + `@include('pages.userGuide.shared.roleTable')` + callout cho các bước ký xét theo **tên vai trò** (người ký dự trù, người kiểm tra hoá chất cấm...), vì phần này không suy ra được từ bảng quyền.
3. Tạo từng file `sections/<file>.blade.php` (chỉ phần thân, không lặp đầu mục).
4. Thêm mục con vào leftNAV dưới "Hướng Dẫn Sử Dụng": `route('pages.userGuide.show', '<khoá>')`, active theo `request()->is('userGuide/<khoá>')`.

## 3. Đọc mã để lấy đúng nghiệp vụ (bắt buộc, không đoán)

Với mỗi màn hình, đọc theo thứ tự:

1. `routes/<nhóm>Route.php`: các action có thật (có `approve`/`reject` → có bước duyệt; `sync`, `destroy`, `template`...).
2. Docblock đầu Controller: mô tả nghiệp vụ gốc, thường đủ để viết đoạn mở đầu.
3. `FIELDS`, `rules()`, `messages()`: ô nào bắt buộc, giới hạn, điều kiện trùng (trùng toàn bảng hay trong phòng/công ty), câu báo lỗi thật để đưa vào "Lỗi Thường Gặp".
4. Hằng số và `App\Support\*` mà controller gọi: danh sách lựa chọn (loại, tần suất, nhóm...). Có thể in thẳng từ hằng trong Blade (ví dụ `ChemicalClassification::GROUPS`) để hướng dẫn tự khớp khi mã đổi.
5. View `create.blade.php` / `formFields.blade.php`: **nhãn ô đúng như trên màn hình**, `<small>` gợi ý, `title` của nút.
6. `session('user')['selected_department_id']` trong controller → màn hình "Theo phòng ban đang chọn".
7. Trang nhiều tab: danh sách tab và quyền từng tab nằm ở `App\Support\PageTabs::PAGES` (quyền `<trang>_tab_<khoá>`). Tên quyền hiển thị đọc từ bảng `permissions.display_name`. Thao tác cần ký bằng mật khẩu: tìm `guardSignature(` trong controller (trait `VerifiesSignature`).
8. Khi docblock, comment và config mâu thuẫn nhau (đã gặp: chu kỳ kiểm kê "1 tháng" và "quý"; lô Chờ kiểm tra "cộng tồn" và "chưa cộng tồn"; config/material.php mô tả người lập tự khai người ký nhưng code lấy từ dữ liệu gốc), **tin vào code đang chạy** (`startOfQuarter()`, cột `is_checked`, hàm thật được gọi).
9. Nhãn nút và màu nút: grep đúng view (`<i class="fas ..."></i> Nhãn` + class `btn-*` ngay trước nó), không đoán. Nút minh hoạ trong hướng dẫn phải cùng class với nút thật.
10. Câu khẳng định về luồng xử lý (khi nào chép quy trình ký, có ghi DB không...) phải truy tới chỗ dùng thật, ví dụ controller nghiệp vụ gọi Support. Không suy đoán.

Lệnh nhanh:

```bash
f=app/Http/Controllers/Pages/<Nhóm>/<X>Controller.php
awk '/^\/\*\*/,/^class/' $f; awk '/FIELDS = \[/,/\];/' $f; awk '/function rules/,/^    }/' $f
grep -o "<label[^>]*>[^<]*\|<small[^>]*>[^<]*\|placeholder=\"[^\"]*\"" resources/views/pages/<nhóm>/<X>/create.blade.php | sed 's/<[^>]*>//'
```

## 4. Khuôn nội dung một mục

```blade
<p>1-2 câu: màn hình dùng để làm gì, dữ liệu được chọn lại ở đâu.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Nhãn ô đúng như màn hình', true, 'Cách nhập, giới hạn, phạm vi không trùng. Có thể dùng <b>, <a href="#neo">, <span class="ug-key">'],
]])

<h3>Các bước ...</h3>          {{-- chỉ khi thao tác nhiều bước --}}
<ol class="ug-steps"><li>...</li></ol>

<div class="ug-callout"> | is-warn | is-danger>
    <i class="fas fa-info-circle | fa-exclamation-triangle | fa-exclamation-circle | fa-lightbulb"></i>
    <div>Lưu ý quan trọng</div>
</div>
```

- Nút minh hoạ: dùng đúng class Bootstrap của màn hình thật cộng thêm `ug-ui`, ví dụ `<span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span>`.
- Phím / mã: `<span class="ug-key">Ctrl</span>`; chip: `<span class="ug-chip is-warn">Chờ duyệt</span>`.
- Liên kết chéo giữa các mục bằng `href="#id"`.
- Văn phong: tiếng Việt có dấu, câu ngắn, nói với người dùng cuối (thủ kho, QC, trưởng phòng), không nhắc tên bảng CSDL, route hay class.

## 5. Bẫy đã gặp

- Quyền tính **theo phòng ban đang chọn**: khi render thử, session phải có `selected_department_id`; tài khoản thường có thể thấy 0 nút "Mở màn hình" ở một module là đúng nếu phòng của họ không có quyền module đó.
- Ngay giao diện thật cũng có khoảng 30 icon tên FA6 không hiện được; khi minh hoạ nút, lấy icon từ view thật rồi đổi sang tên FA5 tương đương nếu kiểm tra thấy thiếu.
- **FontAwesome là bản 5.13**, không có tên FA6: dùng `fa-exclamation-triangle` (không phải `fa-triangle-exclamation`), `fa-question-circle`, `fa-exclamation-circle`, `fa-external-link-alt`. Kiểm tra: `grep -c "fa-<tên>:" public/dataTable/plugins/fontawesome-free/css/all.min.css`.
- `layout/css.blade.php` tô mọi `h1-h4` IN HOA, màu `--primary`: trên nền màu phải đặt lại `color: #fff` bằng selector cụ thể hơn.
- Bố cục flex một cột ở màn hình hẹp phải `align-items: stretch`, nếu không nội dung giãn theo bảng rộng nhất và tràn ngang.
- Quy tắc 4 của `wms-rules` cấm đoạn diễn giải trên màn hình danh sách; giải thích dài thì đưa vào hướng dẫn, không nhúng vào list/dataTable.

## 6. Kiểm tra trước khi báo xong

1. `php artisan wms:user-guide-check` phải 0 lỗi; `php -l` cho controller, `toc.php`.
2. Render không cần đăng nhập web: bootstrap app, `session()->put('user', [...])` của một tài khoản Admin và một tài khoản thường, gọi `(new UserGuideController)->show('<khoá>')->render()`. Đếm `class="ug-section"` (bằng số mục) và `ug-open"` (số nút mở, ít hơn với tài khoản thiếu quyền). Khoá không có trong `GUIDES` phải trả 404.
3. Chụp màn hình offline: `php -S 127.0.0.1:8099 -t public` (chạy nền), lưu HTML đã render vào `public/__ug_preview.html` sau khi thay `http://localhost/` thành `http://127.0.0.1:8099/`, rồi chạy Chrome headless `--screenshot --window-size=1440,2400`.
   - Mở kèm `#neo` sẽ ra ảnh trắng. Muốn xem một mục giữa trang thì chèn `<style>.ug-section:not(#id){display:none}</style>`.
   - Trên Windows, Chrome headless không thu khung nhìn dưới khoảng 500px. Muốn kiểm tra tràn ngang thì đo `scrollWidth` bằng `clientWidth` qua `--dump-dom` thay vì nhìn ảnh 414px.
   - **Xoá các file `public/__ug_*`** và tắt server sau khi xong.
4. `grep -rIn "cdn\.\|googleapis\|jsdelivr\|unpkg\|cloudflare" resources/views/pages/userGuide` phải rỗng.
5. Cập nhật `updated_at` của tài liệu trong `GUIDES`.
