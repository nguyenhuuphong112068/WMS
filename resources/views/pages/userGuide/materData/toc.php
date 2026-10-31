<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - DỮ LIỆU GỐC
|--------------------------------------------------------------------------
| Mỗi mục là một file trong sections/. Mục lục và đầu mục đọc chung danh sách này.
| route / perm khớp với leftNAV để nút "Mở màn hình" chỉ hiện khi người xem có quyền.
*/

$approve = ['Có bước duyệt', 'is-warn'];
$byDept = ['Theo phòng ban đang chọn', ''];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Thứ Tự Khai Báo', 'icon' => 'fas fa-compass'],
        ['id' => 'thao-tac-chung', 'file' => 'commonActions', 'title' => 'Thao Tác Chung', 'icon' => 'fas fa-hand-pointer',
            'keywords' => 'them sua duyet tu choi khoa mo khoa lich su ly do dieu chinh tim kiem'],
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Quyền Truy Cập', 'icon' => 'fas fa-user-shield'],
    ],
    'Tổ chức' => [
        ['id' => 'cong-ty', 'file' => 'company', 'title' => 'Công Ty', 'icon' => 'fas fa-city',
            'route' => 'pages.materData.company.list', 'perm' => 'materData_common_view'],
        ['id' => 'phong-ban', 'file' => 'department', 'title' => 'Phòng Ban', 'icon' => 'fas fa-building',
            'route' => 'pages.materData.department.list', 'perm' => 'materData_common_view'],
        ['id' => 'to', 'file' => 'group', 'title' => 'Tổ', 'icon' => 'fas fa-users',
            'route' => 'pages.materData.group.list', 'perm' => 'materData_common_view'],
    ],
    'Danh mục dùng chung' => [
        ['id' => 'don-vi-tinh', 'file' => 'unit', 'title' => 'Đơn Vị Tính', 'icon' => 'fas fa-balance-scale',
            'route' => 'pages.materData.unit.list', 'perm' => 'materData_common_view', 'chips' => [$approve],
            'keywords' => 'quy doi he so kg g ml lit'],
        ['id' => 'quy-cach-dong-goi', 'file' => 'packagingSpecification', 'title' => 'Quy Cách Đóng Gói', 'icon' => 'fas fa-box-open',
            'route' => 'pages.materData.packagingSpecification.list', 'perm' => 'materData_common_view', 'chips' => [$approve]],
        ['id' => 'dieu-kien-bao-quan', 'file' => 'storageCondition', 'title' => 'Điều Kiện Bảo Quản', 'icon' => 'fas fa-temperature-low',
            'route' => 'pages.materData.storageCondition.list', 'perm' => 'materData_common_view', 'chips' => [$approve]],
        ['id' => 'nha-san-xuat', 'file' => 'chemManufacturer', 'title' => 'Nhà Sản Xuất', 'icon' => 'fas fa-industry',
            'route' => 'pages.materData.chemManufacturer.list', 'perm' => 'materData_common_view', 'chips' => [$approve]],
        ['id' => 'nha-cung-cap', 'file' => 'chemSupplier', 'title' => 'Nhà Cung Cấp', 'icon' => 'fas fa-truck',
            'route' => 'pages.materData.chemSupplier.list', 'perm' => 'materData_common_view', 'chips' => [$approve],
            'keywords' => 'ma so thue dia chi'],
        ['id' => 'trang-thai', 'file' => 'status', 'title' => 'Trạng Thái', 'icon' => 'fas fa-toggle-on',
            'route' => 'pages.materData.status.list', 'perm' => 'materData_common_view'],
        ['id' => 'ten-san-pham', 'file' => 'productName', 'title' => 'Tên Sản Phẩm', 'icon' => 'fas fa-pills',
            'route' => 'pages.materData.productName.list', 'perm' => 'materData_common_view'],
    ],
    'Hoá chất' => [
        ['id' => 'hoat-chat', 'file' => 'activeIngredient', 'title' => 'Đơn Chất', 'icon' => 'fas fa-atom',
            'route' => 'pages.materData.activeIngredient.list', 'perm' => 'materData_chemical_view', 'chips' => [$approve],
            'keywords' => 'hoat chat don chat cas nghi dinh 24 phu luc nhom 9 nguong ton tru muc gop'],
        ['id' => 'nhom-nguy-hai-bang-b', 'file' => 'mixtureHazardCategory', 'title' => 'Nhóm Nguy Hại Bảng B', 'icon' => 'fas fa-exclamation-triangle',
            'route' => 'pages.materData.mixtureHazardCategory.list', 'perm' => 'materData_chemical_view', 'chips' => [$approve],
            'keywords' => 'ghs phu luc iv bang b nguong'],
        ['id' => 'hoa-chat', 'file' => 'chemName', 'title' => 'Hoá Chất', 'icon' => 'fas fa-flask',
            'route' => 'pages.materData.chemName.list', 'perm' => 'materData_chemical_view', 'chips' => [$approve],
            'keywords' => 'ten hoa chat hon hop thanh phan nhom 2 8 10 11 12 cam hang hoa dac biet'],
    ],
    'Chất chuẩn' => [
        ['id' => 'ten-chuan', 'file' => 'standardName', 'title' => 'Tên Chuẩn', 'icon' => 'fas fa-flask',
            'route' => 'pages.materData.standardName.list', 'perm' => 'materData_standard_view', 'chips' => [$approve]],
        ['id' => 'chi-tieu-kiem', 'file' => 'purpose', 'title' => 'Chỉ Tiêu Kiểm', 'icon' => 'fas fa-vial',
            'route' => 'pages.materData.purpose.list', 'perm' => 'materData_standard_view'],
    ],
    'Vật tư' => [
        ['id' => 'ten-vat-tu', 'file' => 'materialName', 'title' => 'Tên Vật Tư', 'icon' => 'fas fa-cubes',
            'route' => 'pages.materData.materialName.list', 'perm' => 'materData_material_view', 'chips' => [$approve]],
        ['id' => 'phan-loai', 'file' => 'departmentClassification', 'shotDept' => 4, 'title' => 'Phân Loại', 'icon' => 'fas fa-tags',
            'route' => 'pages.materData.departmentClassification.list', 'perm' => 'materData_material_view', 'chips' => [$byDept]],
        ['id' => 'trinh-ky-cap-phat', 'file' => 'materialSignFlow', 'shotDept' => 4, 'title' => 'Trình Ký Đề Nghị CP Vật Tư', 'icon' => 'fas fa-file-signature',
            'route' => 'pages.materData.materialSignFlow.list', 'perm' => 'materData_material_view', 'chips' => [$byDept],
            'keywords' => 'cap ky nguoi ky vai tro dieu kien'],
        ['id' => 'trinh-ky-lien-phong-ban', 'file' => 'transferSignFlow', 'title' => 'Trình Ký Đề Nghị Liên Phòng Ban', 'icon' => 'fas fa-route',
            'route' => 'pages.materData.transferSignFlow.list', 'perm' => 'materData_transferSign_view',
            'keywords' => 'vpp van phong pham hanh chanh buoc ky'],
    ],
    'Kho & đối tượng' => [
        ['id' => 'dinh-khu', 'file' => 'zone', 'title' => 'Định Khu', 'icon' => 'fas fa-sitemap',
            'route' => 'pages.materData.zone.list', 'perm' => 'materData_common_view', 'chips' => [$byDept],
            'keywords' => 'kho phong ke tu cot tang vi tri cau truc kho biet tru du phong'],
        ['id' => 'doi-tuong', 'file' => 'consumptionObject', 'title' => 'Đối Tượng', 'icon' => 'fas fa-cogs',
            'route' => 'pages.materData.consumptionObject.list', 'perm' => 'materData_common_view',
            'keywords' => 'thiet bi cal dong bo tan suat'],
    ],
    'Quản trị' => [
        ['id' => 'khoi-tao-du-lieu', 'file' => 'initialData', 'title' => 'Khởi Tạo Dữ Liệu', 'icon' => 'fas fa-file-import',
            'route' => 'pages.materData.initialData.list', 'perm' => 'Admin', 'chips' => [['Chỉ Admin', 'is-danger']],
            'keywords' => 'excel file mau nap ton dau ky'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi Thường Gặp', 'icon' => 'fas fa-question-circle'],
    ],
];
