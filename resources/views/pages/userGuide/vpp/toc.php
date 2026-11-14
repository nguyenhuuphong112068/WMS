<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - VPP
|--------------------------------------------------------------------------
| Module VPP trải trên 5 nhóm menu (Danh Mục, Nhập, Đề Nghị - Sử Dụng, Tồn Kho, Dự Trù). Mỗi mục là một file trong sections/, thường ứng với một tab của màn hình
| (xem App\Support\PageTabs). params['tab'] chỉ truyền cho màn hình đọc ?tab= trên URL.
*/

$approve = ['Có bước duyệt', 'is-warn'];
$sign = ['Ký bằng mật khẩu', 'is-warn'];
$byDept = ['Theo phòng ban đang chọn', ''];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Vòng Đời VPP', 'icon' => 'fas fa-compass'],
        ['id' => 'so-do-chung', 'file' => 'flowMap', 'title' => 'Sơ Đồ Đường Đi Phiếu VPP', 'icon' => 'fas fa-project-diagram',
            'keywords' => 'ban do bieu do luong quy trinh duong di trang thai phieu du tru de nghi lien phong ban vpp cach ly ton kho tang giam'],
        ['id' => 'khai-niem-chung', 'file' => 'concepts', 'title' => 'Khái Niệm & Thao Tác Chung', 'icon' => 'fas fa-hand-pointer',
            'keywords' => 'ma xuat nhap qr ton kha dung fefo fifo mat khau ky dien tu tab'],
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Quyền Truy Cập', 'icon' => 'fas fa-user-shield'],
    ],
    'Danh Mục › VPP' => [
        ['id' => 'danh-muc-cong-ty', 'file' => 'companyCategory', 'title' => 'Danh Mục VPP Công Ty', 'icon' => 'fas fa-cubes',
            'path' => 'Danh Mục › VPP › tab Danh mục VPP công ty',
            'route' => 'pages.category.vppCategory.list', 'pane' => 'tabCompany', 'perm' => 'category_vpp_view', 'chips' => [$approve],
            'keywords' => 'nhom du tru don vi tinh van phong pham'],
        ['id' => 'vat-tu-cua-phong', 'file' => 'departmentVpp', 'title' => 'VPP Của Phòng', 'icon' => 'fas fa-boxes',
            'path' => 'Danh Mục › VPP › tab VPP của phòng',
            'route' => 'pages.category.vppCategory.list', 'pane' => 'tabDepartment', 'perm' => 'category_vpp_view', 'chips' => [$byDept],
            // Tab không có nút Thêm mới: ảnh hộp thoại là hộp Sửa của dòng đầu
            'shots' => [
                ['name' => 'vat-tu-cua-phong', 'tab' => ['tabDepartment'], 'clip' => '.content-wrapper', 'maxHeight' => 640, 'pad' => 0],
                ['name' => 'vat-tu-cua-phong-form', 'tab' => ['tabDepartment'], 'js' => "__ug.click('#tabDepartment .btn-md-edit')", 'clip' => '.modal.show .modal-content', 'maxHeight' => 1400, 'pad' => 0],
            ],
            'keywords' => 'don vi tinh nguong ton toi thieu toi da dinh khu'],
        ['id' => 'danh-sach-chu-ky', 'file' => 'periodicLists', 'title' => 'Danh Sách Theo Chu Kỳ', 'icon' => 'fas fa-sync-alt',
            'path' => 'Danh Mục › VPP › 2 tab DS ... theo chu kỳ',
            'route' => 'pages.category.vppCategory.list', 'pane' => ['tabPeriodicExternal', 'tabPeriodicEstimate'], 'perm' => 'category_vpp_view', 'chips' => [$byDept],
            'keywords' => 'dinh ky tu dong tao de nghi du tru hang thang cal'],
    ],
    'Nhập › VPP' => [
        ['id' => 'cho-kiem-tra', 'file' => 'importCheck', 'title' => 'Tab Nhập VPP (Chờ Kiểm Tra)', 'icon' => 'fas fa-clipboard-check',
            'path' => 'Nhập › VPP › tab Nhập VPP',
            'route' => 'pages.import.vppImport.list', 'pane' => 'impPaneCheck', 'perm' => 'import_vpp_view',
            'keywords' => 'biet tru dat khong dat tra hang'],
        ['id' => 'nhap-vat-tu', 'file' => 'importBook', 'title' => 'Nhập VPP & Sổ Nhập', 'icon' => 'fas fa-dolly',
            'path' => 'Nhập › VPP › tab Sổ nhập VPP',
            'route' => 'pages.import.vppImport.list', 'pane' => 'impPaneBook', 'perm' => 'import_vpp_view', 'chips' => [$byDept],
            // Hộp thoại Nhập VPP mở từ tab Nhập VPP (tab Sổ nhập không có nút tạo)
            'shots' => [
                ['name' => 'nhap-vat-tu', 'clip' => '.content-wrapper', 'maxHeight' => 640, 'pad' => 0],
                ['name' => 'nhap-vat-tu-form', 'tab' => ['impPaneCheck'], 'js' => "document.querySelector('#impPaneCheck .btn-md-create').click()", 'clip' => '.modal.show .modal-content', 'maxHeight' => 1400, 'pad' => 0],
            ],
            'keywords' => 'phieu nhap ma xuat nhap in nhan qr han su dung hoa don'],
    ],
    'Đề Nghị - Sử Dụng › VPP' => [
        ['id' => 'su-dung-vpp', 'file' => 'useTab', 'title' => 'Sử Dụng VPP (Ghi Sổ)', 'icon' => 'fas fa-hand-holding-medical',
            'path' => 'Đề Nghị - Sử Dụng › VPP › tab Sử Dụng VPP',
            'route' => 'pages.export.vppExport.list', 'pane' => 'mePaneUse', 'params' => ['tab' => 'use'], 'perm' => 'export_vpp_view',
            'keywords' => 'su dung vpp ghi so quet ma chon tu ton kho tru ton muc dich nguoi su dung'],
        ['id' => 'de-nghi-vpp', 'file' => 'vpp', 'shotDept' => 20, 'title' => 'Đề Nghị VPP Đến Hành Chánh', 'icon' => 'fas fa-paperclip',
            'path' => 'Đề Nghị - Sử Dụng › VPP › tab Đề nghị liên phòng ban - VPP đến HC',
            'route' => 'pages.export.vppExport.list', 'pane' => 'mePaneVpp', 'params' => ['tab' => 'vpp'], 'perm' => 'export_vpp_view',
            'keywords' => 'van phong pham hanh chinh hanh chanh kho vpp'],
        ['id' => 'lien-phong-ban', 'file' => 'transfer', 'shotDept' => 20, 'title' => 'Đề Nghị Liên Phòng Ban', 'icon' => 'fas fa-exchange-alt',
            'path' => 'Đề Nghị - Sử Dụng › VPP › tab Đề nghị liên phòng ban - Phòng ban khác',
            'route' => 'pages.export.vppExport.list', 'pane' => 'mePaneTransfer', 'params' => ['tab' => 'transfer'], 'perm' => 'export_vpp_view', 'chips' => [$sign],
            'keywords' => 'chuyen vat tu phong khac nhan hang tu choi nhan'],
        ['id' => 'so-cap-phat', 'file' => 'book', 'title' => 'Sổ Sử Dụng VPP', 'icon' => 'fas fa-book',
            'path' => 'Đề Nghị - Sử Dụng › VPP › tab Sổ sử dụng VPP',
            'route' => 'pages.export.vppExport.list', 'pane' => 'mePaneBook', 'params' => ['tab' => 'book'], 'perm' => 'export_vpp_view',
            'keywords' => 'so cap phat dieu chinh nguoi su dung'],
        ['id' => 'cach-ly', 'file' => 'quarantine', 'title' => 'Cách Ly - Loại Bỏ - Huỷ', 'icon' => 'fas fa-exclamation-triangle',
            'path' => 'Đề Nghị - Sử Dụng › VPP › tab Cách ly - Loại bỏ - Huỷ',
            'route' => 'pages.export.vppExport.list', 'pane' => 'mePaneQuarantine', 'params' => ['tab' => 'quarantine'], 'perm' => 'export_vpp_view', 'chips' => [$sign],
            'keywords' => 'hong het han cho quyet dinh tra ve kho'],
    ],
    'Tồn Kho › VPP' => [
        ['id' => 'ton-kho', 'file' => 'inventory', 'title' => 'Xem Tồn Kho & Cân Đối', 'icon' => 'fas fa-boxes',
            'path' => 'Tồn Kho › VPP › tab Theo mã xuất nhập / Tồn theo mã VPP / Tồn kho theo vị trí',
            'route' => 'pages.inventory.vppInventory.list', 'pane' => ['miPaneCode', 'miPaneMat', 'miPaneZone', 'miPaneExpiring'], 'perm' => 'inventory_vpp_view', 'chips' => [$byDept],
            'keywords' => 'ton dau ky cuoi ky can doi bieu do vi tri'],
        ['id' => 'kha-dung-thang-toi', 'file' => 'nextMonth', 'title' => 'Khả Dụng Tháng Tới', 'icon' => 'fas fa-calendar-alt',
            'path' => 'Tồn Kho › VPP › tab Khả dụng tháng tới',
            'route' => 'pages.inventory.vppInventory.list', 'pane' => 'miPaneNext', 'perm' => 'inventory_vpp_view',
            'keywords' => 'thieu du nhu cau chu ky'],
        ['id' => 'kiem-ke', 'file' => 'stocktake', 'title' => 'Kiểm Kê Định Kỳ', 'icon' => 'fas fa-tasks',
            'path' => 'Tồn Kho › VPP › tab Kiểm kê định kỳ',
            'route' => 'pages.inventory.vppInventory.list', 'pane' => 'miPaneCheck', 'helpRoutes' => ['pages.inventory.vppStocktake.detail'], 'perm' => 'inventory_vpp_view',
            'keywords' => 'quy dem thuc te chenh lech chot phieu'],
    ],
    'Dự Trù › VPP' => [
        ['id' => 'du-tru', 'file' => 'estimate', 'title' => 'Lập & Trình Ký Phiếu Dự Trù', 'icon' => 'fas fa-file-invoice',
            'path' => 'Dự Trù › VPP › tab Danh sách phiếu',
            'route' => 'pages.estimate.vppEstimate.list', 'pane' => 'list', 'params' => ['tab' => 'list'], 'perm' => 'estimate_vpp_view', 'chips' => [$sign],
            'keywords' => 'quan ly bo phan ban giam doc thang du tru'],
        ['id' => 'can-du-tru', 'file' => 'watchlist', 'title' => 'Danh Sách VPP Cần Dự Trù', 'icon' => 'fas fa-clipboard-list',
            'path' => 'Dự Trù › VPP › tab Danh sách VPP cần dự trù',
            'route' => 'pages.estimate.vppEstimate.list', 'pane' => 'watchlist', 'params' => ['tab' => 'watchlist'], 'perm' => 'estimate_vpp_view',
            'keywords' => 'duoi nguong ghi nho'],
        ['id' => 'theo-doi-du-tru', 'file' => 'estimateFollowUp', 'title' => 'Theo Dõi & Tiếp Nhận Dự Trù', 'icon' => 'fas fa-truck-loading',
            'path' => 'Dự Trù › VPP › tab Theo dõi dự trù / Đơn dự trù đã được phê duyệt',
            'route' => 'pages.estimate.vppEstimate.list', 'pane' => ['tracking', 'approved'], 'params' => ['tab' => 'tracking'], 'perm' => 'estimate_vpp_view',
            'keywords' => 'cung ung tiep nhan ngay hen huy muc trao doi'],
    ],
    'Hỗ trợ' => [
        ['id' => 'tra-cuu', 'file' => 'publicLookup', 'title' => 'Tra Cứu Không Cần Đăng Nhập', 'icon' => 'fas fa-search-location',
            'keywords' => 'tim vi tri vat tu dang nhap mat bang 3d quet ma camera'],
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi Thường Gặp', 'icon' => 'fas fa-question-circle'],
    ],
];
