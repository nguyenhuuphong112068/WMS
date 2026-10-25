<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - VẬT TƯ
|--------------------------------------------------------------------------
| Module vật tư trải trên 6 nhóm menu (Danh Mục, Nhập, Đề Nghị - Sử Dụng, Tồn Kho, Dự Trù,
| Thống Kê). Mỗi mục là một file trong sections/, thường ứng với một tab của màn hình
| (xem App\Support\PageTabs). params['tab'] chỉ truyền cho màn hình đọc ?tab= trên URL.
*/

$approve = ['Có bước duyệt', 'is-warn'];
$sign = ['Ký bằng mật khẩu', 'is-warn'];
$byDept = ['Theo phòng ban đang chọn', ''];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Vòng Đời Vật Tư', 'icon' => 'fas fa-compass'],
        ['id' => 'khai-niem-chung', 'file' => 'concepts', 'title' => 'Khái Niệm & Thao Tác Chung', 'icon' => 'fas fa-hand-pointer',
            'keywords' => 'ma xuat nhap qr ton kha dung fefo fifo mat khau ky dien tu tab'],
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Quyền Truy Cập', 'icon' => 'fas fa-user-shield'],
    ],
    'Danh Mục › Vật Tư' => [
        ['id' => 'danh-muc-cong-ty', 'file' => 'companyCategory', 'title' => 'Danh Mục Vật Tư Công Ty', 'icon' => 'fas fa-cubes',
            'path' => 'Danh Mục › Vật Tư › tab Danh mục vật tư công ty',
            'route' => 'pages.category.materialCategory.list', 'pane' => 'tabCompany', 'perm' => 'category_material_view', 'chips' => [$approve],
            'keywords' => 'phan loai bo phan mua hang part number thoi gian dat hang'],
        ['id' => 'vat-tu-cua-phong', 'file' => 'departmentMaterial', 'title' => 'Vật Tư Của Phòng', 'icon' => 'fas fa-boxes',
            'path' => 'Danh Mục › Vật Tư › tab Vật tư của phòng',
            'route' => 'pages.category.materialCategory.list', 'pane' => 'tabDepartment', 'perm' => 'category_material_view', 'chips' => [$byDept],
            'keywords' => 'don vi tinh nguong ton toi thieu toi da dinh khu'],
        ['id' => 'danh-sach-chu-ky', 'file' => 'periodicLists', 'title' => 'Danh Sách Theo Chu Kỳ', 'icon' => 'fas fa-sync-alt',
            'path' => 'Danh Mục › Vật Tư › 3 tab DS ... theo chu kỳ',
            'route' => 'pages.category.materialCategory.list', 'pane' => ['tabPeriodicInternal', 'tabPeriodicExternal', 'tabPeriodicEstimate'], 'perm' => 'category_material_view', 'chips' => [$byDept],
            'keywords' => 'dinh ky tu dong tao de nghi du tru hang thang cal'],
    ],
    'Nhập › Vật Tư' => [
        ['id' => 'nhap-vat-tu', 'file' => 'importBook', 'title' => 'Nhập Vật Tư & Sổ Nhập', 'icon' => 'fas fa-dolly',
            'path' => 'Nhập › Vật Tư › tab Sổ nhập vật tư',
            'route' => 'pages.import.materialImport.list', 'pane' => 'impPaneBook', 'perm' => 'import_material_view', 'chips' => [$byDept],
            'keywords' => 'phieu nhap ma xuat nhap in nhan qr han su dung hoa don'],
        ['id' => 'cho-kiem-tra', 'file' => 'importCheck', 'title' => 'Chờ Kiểm Tra', 'icon' => 'fas fa-clipboard-check',
            'path' => 'Nhập › Vật Tư › tab Chờ kiểm tra',
            'route' => 'pages.import.materialImport.list', 'pane' => 'impPaneCheck', 'perm' => 'import_material_view',
            'keywords' => 'biet tru dat khong dat tra hang'],
    ],
    'Đề Nghị - Sử Dụng › Vật Tư' => [
        ['id' => 'de-nghi-noi-bo', 'file' => 'internalRequest', 'title' => 'Đề Nghị Cấp Phát Nội Bộ', 'icon' => 'fas fa-file-signature',
            'path' => 'Đề Nghị - Sử Dụng › Vật Tư › 3 tab Đề nghị nội bộ',
            'route' => 'pages.export.materialExport.list', 'pane' => ['mePanePeriodic', 'mePaneRisk', 'mePaneRegular'], 'params' => ['tab' => 'regular'], 'perm' => 'export_material_view', 'chips' => [$sign],
            'keywords' => 'thuong quy danh gia rui ro dinh ky trinh ky tu choi'],
        ['id' => 'cho-cap-phat', 'file' => 'prepare', 'title' => 'Chờ Cấp Phát', 'icon' => 'fas fa-dolly-flatbed',
            'path' => 'Đề Nghị - Sử Dụng › Vật Tư › tab Chờ cấp phát',
            'route' => 'pages.export.materialExport.list', 'pane' => 'mePanePrepare', 'params' => ['tab' => 'prepare'], 'perm' => 'export_material_view',
            'keywords' => 'kho cap phat fefo fifo lo cap vuot'],
        ['id' => 'hieu-chuan', 'file' => 'calibration', 'title' => 'Hiệu Chuẩn Trước Khi Cấp', 'icon' => 'fas fa-ruler-combined',
            'path' => 'Đề Nghị - Sử Dụng › Vật Tư › tab Xác nhận hiệu chuẩn',
            'route' => 'pages.export.materialExport.list', 'pane' => 'mePaneCalibration', 'params' => ['tab' => 'calibration'], 'perm' => 'export_material_view', 'chips' => [$sign],
            'keywords' => 'qa seri no dat khong dat'],
        ['id' => 'de-nghi-vpp', 'file' => 'vpp', 'title' => 'Đề Nghị VPP Đến Hành Chánh', 'icon' => 'fas fa-paperclip',
            'path' => 'Đề Nghị - Sử Dụng › Vật Tư › tab Đề nghị liên phòng ban - VPP đến HC',
            'route' => 'pages.export.materialExport.list', 'pane' => 'mePaneVpp', 'params' => ['tab' => 'vpp'], 'perm' => 'export_material_view',
            'keywords' => 'van phong pham hanh chinh'],
        ['id' => 'lien-phong-ban', 'file' => 'transfer', 'title' => 'Đề Nghị Liên Phòng Ban', 'icon' => 'fas fa-exchange-alt',
            'path' => 'Đề Nghị - Sử Dụng › Vật Tư › tab Đề nghị liên phòng ban - Phòng ban khác',
            'route' => 'pages.export.materialExport.list', 'pane' => 'mePaneTransfer', 'params' => ['tab' => 'transfer'], 'perm' => 'export_material_view', 'chips' => [$sign],
            'keywords' => 'chuyen vat tu phong khac nhan hang tu choi nhan'],
        ['id' => 'so-cap-phat', 'file' => 'book', 'title' => 'Sổ Cấp Phát', 'icon' => 'fas fa-book',
            'path' => 'Đề Nghị - Sử Dụng › Vật Tư › tab Sổ cấp phát',
            'route' => 'pages.export.materialExport.list', 'pane' => 'mePaneBook', 'params' => ['tab' => 'book'], 'perm' => 'export_material_view'],
        ['id' => 'cach-ly', 'file' => 'quarantine', 'title' => 'Cách Ly - Loại Bỏ - Huỷ', 'icon' => 'fas fa-exclamation-triangle',
            'path' => 'Đề Nghị - Sử Dụng › Vật Tư › tab Cách ly - Loại bỏ - Huỷ',
            'route' => 'pages.export.materialExport.list', 'pane' => 'mePaneQuarantine', 'params' => ['tab' => 'quarantine'], 'perm' => 'export_material_view', 'chips' => [$sign],
            'keywords' => 'hong het han cho quyet dinh tra ve kho'],
        ['id' => 'cap-phat-tre', 'file' => 'late', 'title' => 'Ghi Nhận Cấp Phát Trễ', 'icon' => 'fas fa-hourglass-half',
            'path' => 'Đề Nghị - Sử Dụng › Vật Tư › tab Ghi nhận cấp phát trễ',
            'route' => 'pages.export.materialExport.list', 'pane' => 'mePaneLate', 'params' => ['tab' => 'late'], 'perm' => 'export_material_view',
            'keywords' => 'duyet tre thieu ton'],
    ],
    'Tồn Kho › Vật Tư' => [
        ['id' => 'ton-kho', 'file' => 'inventory', 'title' => 'Xem Tồn Kho & Cân Đối', 'icon' => 'fas fa-boxes',
            'path' => 'Tồn Kho › Vật Tư › tab Theo mã xuất nhập / Tồn theo mã vật tư / Tồn kho theo vị trí',
            'route' => 'pages.inventory.materialInventory.list', 'pane' => ['miPaneCode', 'miPaneMat', 'miPaneZone'], 'perm' => 'inventory_material_view', 'chips' => [$byDept],
            'keywords' => 'ton dau ky cuoi ky can doi bieu do vi tri'],
        ['id' => 'kha-dung-thang-toi', 'file' => 'nextMonth', 'title' => 'Khả Dụng Tháng Tới', 'icon' => 'fas fa-calendar-alt',
            'path' => 'Tồn Kho › Vật Tư › tab Khả dụng tháng tới',
            'route' => 'pages.inventory.materialInventory.list', 'pane' => 'miPaneNext', 'perm' => 'inventory_material_view',
            'keywords' => 'thieu du nhu cau chu ky'],
        ['id' => 'kiem-ke', 'file' => 'stocktake', 'title' => 'Kiểm Kê Định Kỳ', 'icon' => 'fas fa-tasks',
            'path' => 'Tồn Kho › Vật Tư › tab Kiểm kê định kỳ',
            'route' => 'pages.inventory.materialInventory.list', 'pane' => 'miPaneCheck', 'helpRoutes' => ['pages.inventory.materialStocktake.detail'], 'perm' => 'inventory_material_view',
            'keywords' => 'quy dem thuc te chenh lech chot phieu'],
    ],
    'Dự Trù › Vật Tư' => [
        ['id' => 'du-tru', 'file' => 'estimate', 'title' => 'Lập & Trình Ký Phiếu Dự Trù', 'icon' => 'fas fa-file-invoice',
            'path' => 'Dự Trù › Vật Tư › tab Danh sách phiếu',
            'route' => 'pages.estimate.materialEstimate.list', 'pane' => 'list', 'params' => ['tab' => 'list'], 'perm' => 'estimate_material_view', 'chips' => [$sign],
            'keywords' => 'quan ly bo phan ban giam doc thang du tru'],
        ['id' => 'can-du-tru', 'file' => 'watchlist', 'title' => 'Danh Sách Vật Tư Cần Dự Trù', 'icon' => 'fas fa-clipboard-list',
            'path' => 'Dự Trù › Vật Tư › tab Danh sách vật tư cần dự trù',
            'route' => 'pages.estimate.materialEstimate.list', 'pane' => 'watchlist', 'params' => ['tab' => 'watchlist'], 'perm' => 'estimate_material_view',
            'keywords' => 'duoi nguong ghi nho'],
        ['id' => 'theo-doi-du-tru', 'file' => 'estimateFollowUp', 'title' => 'Theo Dõi & Tiếp Nhận Dự Trù', 'icon' => 'fas fa-truck-loading',
            'path' => 'Dự Trù › Vật Tư › tab Theo dõi dự trù / Đơn dự trù đã được phê duyệt',
            'route' => 'pages.estimate.materialEstimate.list', 'pane' => ['tracking', 'approved'], 'params' => ['tab' => 'tracking'], 'perm' => 'estimate_material_view',
            'keywords' => 'cung ung tiep nhan ngay hen huy muc trao doi'],
    ],
    'Thống Kê › Vật Tư' => [
        ['id' => 'thong-ke', 'file' => 'statistic', 'title' => 'Thống Kê Cấp Phát', 'icon' => 'fas fa-chart-bar',
            'path' => 'Thống Kê › Vật Tư',
            'route' => 'pages.statistic.materialIssue.list', 'perm' => 'export_material_view',
            'keywords' => 'doi tuong thiet bi tieu thu'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi Thường Gặp', 'icon' => 'fas fa-question-circle'],
    ],
];
