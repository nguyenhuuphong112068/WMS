---
name: wms-rules
description: Quy tắc bắt buộc khi code dự án WMS (Laravel Blade - Quản Lý Kho). Dùng skill này MỖI KHI tạo/sửa Controller, View Blade, Route, migration, đặt tên bảng CSDL, giao diện, màu sắc, hoặc khi tổ chức thư mục file trong dự án này. Bao gồm - chỉ dùng Query Builder trong Controller, quy tắc đặt tên bảng CSDL, bảng màu xanh dương nhạt, chuẩn giao diện chuyên nghiệp, và cấu trúc thư mục theo nhóm chức năng trên leftNAV.
---

# Quy Tắc Chung Dự Án WMS (Quản Lý Kho)

Stack: **Laravel 12 + Blade + AdminLTE + Bootstrap + jQuery/DataTables**. Không dùng React/Inertia cho các trang nghiệp vụ.

---

## 1. Chỉ dùng Query Builder trong Controller

**Bắt buộc:** mọi thao tác với DB trong file Controller phải dùng `Illuminate\Support\Facades\DB` (Query Builder).

- ✅ `DB::table('warehouses')->where(...)->get()`
- ❌ Không dùng Eloquent Model trong Controller (`Warehouse::find()`, `$model->save()`, `with()`, quan hệ `hasMany`...).
- ❌ Không dùng `DB::raw()` để ghép chuỗi từ input người dùng — luôn dùng binding (`where('col', $request->x)`).
- Join dữ liệu bằng `leftJoin` / `join` + `select` chỉ định rõ cột:

```php
$datas = DB::table('warehouses')
    ->leftJoin('deparments', 'warehouses.department_id', '=', 'deparments.id')
    ->select('warehouses.*', 'deparments.name as department_name')
    ->where('warehouses.department_id', session('user')['selected_department_id'])
    ->orderBy('warehouses.name', 'asc')
    ->get();
```

**Chuẩn 4 action cho một chức năng CRUD** (giữ đúng tên như các controller hiện có):

| Method | Việc làm |
|---|---|
| `index()` | `DB::table(...)->get()` + `session()->put(['title' => 'TÊN MÀN HÌNH'])` + `return view(...)` |
| `store(Request $request)` | `Validator::make(...)` → fail: `withErrors($validator, 'createErrors')->withInput()` → `DB::table()->insert([... 'created_by' => session('user')['fullName'], 'created_at' => now()])` |
| `update(Request $request)` | như trên với bag `updateErrors`, set `updated_by` / `updated_at` |
| `deActive(Request $request)` | đảo `status_id` (1 ↔ 0), không xoá cứng dữ liệu |

Luôn kết thúc bằng `redirect()->back()->with('success', '...')`. Ghi log nghiệp vụ quan trọng qua `AuditTrialController::log(...)`.

---

## 2. Bảng màu toàn dự án: Xanh dương nhạt

Biến CSS được khai báo tập trung tại [resources/views/layout/css.blade.php](resources/views/layout/css.blade.php). **Luôn dùng biến, không hardcode mã màu trong file view.**

| Biến | Mã màu | Dùng cho |
|---|---|---|
| `--primary` | `#2E7BC4` | Màu chủ đạo: nút chính, tiêu đề, menu active |
| `--primary-dark` | `#1F5E9E` | Trạng thái hover / nhấn |
| `--primary-light` | `#5AA0DE` | Gradient, viền nhấn |
| `--primary-lighter` | `#9CC7EE` | Icon phụ, đường kẻ |
| `--primary-soft` | `#EAF3FC` | Nền vùng nhấn, hàng bảng hover |
| `--accent` | `#17B8D4` | Điểm nhấn (chuông thông báo, chat) |
| `--bg-neutral` | `#F5F9FD` | Nền trang |
| `--text-main` | `#2D3748` | Chữ chính |

- `--primary-navy` và `--accent-gold` là **alias cũ**, đã trỏ về `--primary` / `--accent` để tương thích các view cũ. Code mới dùng tên mới.
- Cần màu trong suốt: dùng `rgba(var(--primary-rgb), 0.1)`.
- Màu trạng thái giữ chuẩn Bootstrap: success `#16A34A`, warning `#F59E0B`, danger `#DC2626`.

---

## 3. Giao diện phải đẹp và chuyên nghiệp

- **Bo góc**: `--border-radius-lg: 12px` cho card/modal, `--border-radius-md: 8px` cho input/button.
- **Đổ bóng**: mềm (`--shadow-sm`, `--shadow-md`), không dùng viền đen đậm.
- **Chuyển động**: mọi hover/focus có `transition` 0.2s–0.3s. Nút chính nhấc nhẹ `translateY(-1px)` khi hover.
- **Khoảng trắng**: card có padding tối thiểu 20px; không nhồi nhét nội dung sát viền.
- **Tiêu đề màn hình**: IN HOA, `font-weight: 700`, `letter-spacing: 1px`, màu `var(--primary)`.
- **Icon**: FontAwesome 6 (`fas fa-*`) trong khu vực AdminLTE, Bootstrap Icons (`bi bi-*`) ở trang login/standalone. Mỗi chức năng có 1 icon riêng, dùng nhất quán giữa leftNAV và tiêu đề trang.
- **Bảng dữ liệu**: DataTables, header nền `--primary-soft`, chữ `--primary`, hàng hover nền nhạt.
- **Form**: label rõ ràng, hiển thị lỗi validate ngay dưới field, nút hành động chính bên phải.
- **Thông báo**: `session('success')` / `session('error')` hiển thị bằng alert bo góc mềm hoặc SweetAlert2.
- **Responsive**: bố cục phải dùng grid/flex của Bootstrap, không vỡ trên màn hình nhỏ.
- Trang không có left menu (login) dùng nền gradient xanh dương nhạt + minh hoạ chủ đề kho hàng (kệ, pallet, thùng hàng).

---

## 4. Cấu trúc thư mục theo nhóm chức năng trên leftNAV

Hệ thống là **một khối thống nhất**, không chia phân hệ. Không có màn hình chọn phân hệ, không có `session('module')`, không có thư mục `Material/` / `Chemical/` / `General/` trong `Pages/`.

**Cấp 1 chính là nhóm chức năng trên leftNAV.** Mục cha trên menu = thư mục cấp 1, mục con = thư mục chức năng.

| Nhóm menu leftNAV | Thư mục Controller | Thư mục View | Tiền tố route |
|---|---|---|---|
| **Dữ Liệu Gốc** | `app/Http/Controllers/Pages/MaterData/` | `resources/views/pages/materData/` | `pages.materData.` |
| **Phân Quyền** | `app/Http/Controllers/Pages/User/` | `resources/views/pages/user/` | `pages.user.` |
| **Audit Trail** | `app/Http/Controllers/Pages/AuditTrail/` | `resources/views/pages/auditTrail/` | `pages.auditTrail.` |

```
app/Http/Controllers/Pages/
├── MaterData/                        ← menu "Dữ Liệu Gốc"
│   ├── DepartmentController.php
│   ├── StatusController.php
│   └── ZoneController.php
├── User/                             ← menu "Phân Quyền"
│   ├── UserController.php
│   ├── RoleController.php
│   ├── PermissionContoller.php
│   └── PositionController.php
└── AuditTrail/                       ← menu "Audit Trail"
    └── AuditTrialController.php

resources/views/pages/
├── materData/{Department,Status,Zone}/
├── user/{user,role,permission}/
├── auditTrail/
└── home.blade.php
```

> `app/Http/Controllers/General/` (HomeController, ChatController, NotificationController, SwitchProductionController) là tiện ích dùng chung toàn hệ thống, **không phải** màn hình trên leftNAV nên nằm ngoài `Pages/`.

Mỗi thư mục chức năng có 4 file view chuẩn:

```
Department/
├── list.blade.php       (màn hình chính, @extends layout.master)
├── dataTable.blade.php  (bảng dữ liệu, được @include vào list)
├── create.blade.php     (modal thêm mới)
└── update.blade.php     (modal cập nhật)
```

**Route** đặt trong `routes/<nhómChứcNăng>Route.php` (`materDataRoute.php`, `UserRoute.php`, `AuditTrialRoute.php`), khai báo qua `web.php`. Route không thuộc nhóm menu nào (đăng nhập, trang chủ, import) nằm ở `routes/appRoute.php`. **Tên route và URL đi đúng theo đường dẫn thư mục:**

```php
Route::prefix('/materData')->name('pages.materData.')
    ->middleware(CheckLogin::class)->group(function () {
        Route::prefix('/department')->name('department.')
            ->controller(DepartmentController::class)->group(function () {
                Route::get('', 'index')->name('list');
                Route::post('store', 'store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive', 'deActive')->name('deActive');
            });
    });
```

→ URL `/materData/department` — route `pages.materData.department.list` — view `pages.materData.Department.list`

**leftNAV trỏ thẳng tới tên route**, không có biến điều kiện, không ghép chuỗi tên route:

```blade
<a href="{{ route('pages.materData.department.list') }}"
   class="nav-link {{ request()->is('materData/department') ? 'active' : '' }}">Phòng Ban</a>
```

**Khi thêm chức năng mới:**

1. Xác định chức năng nằm ở nhóm nào trên leftNAV. Nhóm chưa có thì tạo nhóm mới (thư mục cấp 1 + file route + mục cha trên leftNAV).
2. Tạo thư mục Controller + View theo đúng 2 cấp `<nhóm chức năng>/<chức năng>`.
3. Khai báo route theo đúng cấu trúc trên, gắn `middleware(CheckLogin::class)`.
4. Thêm mục vào leftNAV kèm trạng thái `active` theo `request()->is(...)`.

Không đặt file lạc thư mục. Không tạo lại cấu trúc phân hệ (`material/`, `chemical/`, `general/`) dưới bất kỳ hình thức nào.

---

## 5. Quy tắc đặt tên bảng CSDL

**Không dùng tiền tố.** Tên bảng viết thẳng theo nội dung bảng: `chem_names`, `material_names`, `units`, `packaging_specifications`, `warehouses`, `statuses`... Không thêm `wms_`, `gen_`, `mat_`, `che_` hay bất kỳ tiền tố phân hệ / phân nhóm nào.

Không tách bảng theo loại hàng (vật tư / hoá chất) thành hai bảng song song khi nghiệp vụ giống nhau. Loại hàng là **dữ liệu**, không phải kiến trúc: dùng một cột phân loại (ví dụ `material_type_id`) trên cùng một bảng.

Quy ước đặt tên:

- Chữ thường, `snake_case`, danh từ **số nhiều**: `material_types` (không phải `MaterialType`, không phải `material_type`).
- Bảng trung gian (pivot): `<bảng1 số ít>_<bảng2 số ít>` — ví dụ `user_role`, `role_permission`.
- Khoá ngoại theo quy ước Laravel: `department_id`, `status_id`, `material_id`.
- Mọi bảng nghiệp vụ có đủ cột chuẩn: `id`, `status_id`, `created_by`, `created_at`, `updated_by`, `updated_at`.
- Tên file migration theo chuẩn Laravel: `2026_08_20_090000_create_material_names_table.php`.

```php
Schema::create('materials', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique();
    $table->string('name');
    $table->unsignedBigInteger('material_type_id')->nullable();
    $table->unsignedBigInteger('department_id')->nullable();
    $table->unsignedBigInteger('status_id')->default(1);
    $table->string('created_by')->nullable();
    $table->string('updated_by')->nullable();
    $table->timestamps();
});
```

```php
$datas = DB::table('materials')
    ->leftJoin('deparments', 'materials.department_id', '=', 'deparments.id')
    ->select('materials.*', 'deparments.name as department_name')
    ->get();
```

> **Lưu ý:** bảng phòng ban trong DB tên là `deparments` (thiếu chữ `t`, sai chính tả từ đầu dự án). Giữ nguyên khi viết query, đừng tự sửa thành `departments`.

---

## 6. Quy ước khác đang áp dụng

- Thông tin đăng nhập nằm trong `session('user')`: `userId`, `userName`, `fullName`, `userGroup`, `department`, `selected_department`, `selected_department_id`.
- Đăng nhập thành công vào thẳng `pages.home`. Không có bước chọn phân hệ, không dùng `session('module')`.
- Mọi route nghiệp vụ phải gắn `middleware(CheckLogin::class)`.
- Kiểm tra quyền bằng `user_has_any_role($userId, ['Admin'])` / `user_has_permission(...)` ở cả view và controller.
- Tiêu đề trang đặt bằng `session()->put(['title' => '...'])` trong controller, topNAV tự hiển thị.
- Ngôn ngữ hiển thị: Tiếng Việt có dấu. Ngày giờ lưu DB dạng `Y-m-d H:i:s`, hiển thị `d/m/Y`.
