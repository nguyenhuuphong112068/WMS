<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - HOÁ CHẤT
|--------------------------------------------------------------------------
| Module hoá chất trải trên Danh Mục, Nhập, Sử Dụng, Tồn Kho (kèm tab Đối chiếu ngưỡng PL IV) và
| Dự Trù. Mỗi mục là một file trong sections/, thường ứng với một tab (App\Support\PageTabs).
| params['tab'] chỉ truyền cho màn hình đọc ?tab= trên URL: Nhập, Sử Dụng, Dự Trù, Tồn Kho.
*/

$approve = ['Có bước duyệt', 'is-warn'];
$sign = ['Ký bằng mật khẩu', 'is-warn'];
$byDept = ['Theo phòng ban đang chọn', ''];
$byCompany = ['Toàn công ty', ''];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Vòng Đời Hoá Chất', 'icon' => 'fas fa-compass'],
        ['id' => 'khai-niem-chung', 'file' => 'concepts', 'title' => 'Khái Niệm & Thao Tác Chung', 'icon' => 'fas fa-hand-pointer',
            'keywords' => 'ma xuat nhap han dung noi bo nghi dinh 24 tuong ky xuat vuot am kho quy doi kg'],
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Quyền Truy Cập', 'icon' => 'fas fa-user-shield'],
    ],
    'Danh Mục › Hoá Chất' => [
        ['id' => 'danh-muc-cong-ty', 'file' => 'companyCategory', 'title' => 'Danh Mục Hoá Chất Công Ty', 'icon' => 'fas fa-flask',
            'path' => 'Danh Mục › Hoá Chất › tab Danh mục hoá chất công ty',
            'route' => 'pages.category.chemicalCategory.list', 'pane' => 'tabCompany', 'perm' => 'category_chemical_view', 'chips' => [$approve],
            'keywords' => 'ti trong canh bao an toan cong thong tin quoc gia phan loai'],
        ['id' => 'hoa-chat-cua-phong', 'file' => 'departmentChemical', 'title' => 'Hoá Chất Của Phòng', 'icon' => 'fas fa-vials',
            'path' => 'Danh Mục › Hoá Chất › tab Hoá chất của phòng',
            'route' => 'pages.category.chemicalCategory.list', 'pane' => 'tabDepartment', 'perm' => 'category_chemical_view', 'chips' => [$byDept],
            'keywords' => 'don vi tinh khoi luong quy doi han dung noi bo dinh khu tuong ky'],
    ],
    'Nhập › Hoá Chất' => [
        ['id' => 'nhap-hoa-chat', 'file' => 'importCheck', 'title' => 'Nhập Hoá Chất & Kiểm Tra', 'icon' => 'fas fa-dolly',
            'path' => 'Nhập › Hoá Chất › tab Nhập hoá chất',
            'route' => 'pages.import.chemicalImport.list', 'pane' => 'impPaneCheck', 'perm' => 'import_chemical_view', 'chips' => [$byDept],
            'keywords' => 'phieu nhap cho kiem tra biet tru dat khong dat nha cung cap'],
        ['id' => 'so-nhap', 'file' => 'importBook', 'title' => 'Sổ Nhập Hoá Chất', 'icon' => 'fas fa-book',
            'path' => 'Nhập › Hoá Chất › tab Sổ nhập hoá chất',
            'route' => 'pages.import.chemicalImport.list', 'pane' => 'impPaneBook', 'params' => ['tab' => 'book'], 'perm' => 'import_chemical_view',
            'keywords' => 'in nhan qr dieu chinh khoa'],
        ['id' => 'bao-cao-nhap', 'file' => 'importReport', 'title' => 'Báo Cáo Nhập Hoá Chất', 'icon' => 'fas fa-chart-bar',
            'path' => 'Nhập › Hoá Chất › tab Báo cáo nhập hoá chất',
            'route' => 'pages.import.chemicalImport.list', 'pane' => 'impPaneReport', 'params' => ['tab' => 'report'], 'perm' => 'import_chemical_view',
            'keywords' => 'tong nhap quy doi kg'],
    ],
    'Sử Dụng › Hoá Chất' => [
        ['id' => 'su-dung', 'file' => 'use', 'title' => 'Sử Dụng Hoá Chất & Phiếu Tạm', 'icon' => 'fas fa-vials',
            'path' => 'Đề Nghị - Sử Dụng › Hoá Chất › tab Sử Dụng Hoá Chất',
            'route' => 'pages.export.chemicalExport.list', 'pane' => 'expPaneDraft', 'perm' => 'export_chemical_view', 'chips' => [$byDept],
            'keywords' => 'quet ma luu tam xuat vuot 5% nguoi kiem tra'],
        ['id' => 'so-su-dung', 'file' => 'book', 'title' => 'Sổ Sử Dụng Hoá Chất', 'icon' => 'fas fa-book',
            'path' => 'Đề Nghị - Sử Dụng › Hoá Chất › tab Sổ sử dụng hoá chất',
            'route' => 'pages.export.chemicalExport.list', 'pane' => 'expPaneBook', 'params' => ['tab' => 'book'], 'perm' => 'export_chemical_view',
            'keywords' => 'so phieu kn oos bcsl dieu chinh'],
        ['id' => 'hoa-chat-cam', 'file' => 'banned', 'title' => 'Sổ Hoá Chất Cấm', 'icon' => 'fas fa-ban',
            'path' => 'Đề Nghị - Sử Dụng › Hoá Chất › tab Hoá chất cấm',
            'route' => 'pages.export.chemicalExport.list', 'pane' => 'expPaneBanned', 'params' => ['tab' => 'banned'], 'perm' => 'export_chemical_view',
            'keywords' => 'luat dau tu 2025 nhom 11 quan ly kho'],
        ['id' => 'lien-phong-ban', 'file' => 'transfer', 'title' => 'Đề Nghị Chuyển Liên Phòng Ban', 'icon' => 'fas fa-people-arrows',
            'path' => 'Đề Nghị - Sử Dụng › Hoá Chất › tab Đề nghị chuyển liên phòng ban',
            'route' => 'pages.export.chemicalExport.list', 'pane' => 'expPaneRequest', 'params' => ['tab' => 'request'], 'perm' => 'export_chemical_view',
            'keywords' => 'cap phat nhan tu choi nhan phong khac'],
        ['id' => 'cach-ly', 'file' => 'quarantine', 'title' => 'Cách Ly - Loại Bỏ - Huỷ', 'icon' => 'fas fa-biohazard',
            'path' => 'Đề Nghị - Sử Dụng › Hoá Chất › tab Cách ly - Loại bỏ - Huỷ',
            'route' => 'pages.export.chemicalExport.list', 'pane' => 'expPaneQuarantine', 'params' => ['tab' => 'quarantine'], 'perm' => 'export_chemical_view', 'chips' => [$sign],
            'keywords' => 'hong het han cho quyet dinh'],
        ['id' => 'bao-cao-su-dung', 'file' => 'report', 'title' => 'Báo Cáo Sử Dụng', 'icon' => 'fas fa-chart-bar',
            'path' => 'Đề Nghị - Sử Dụng › Hoá Chất › tab Báo cáo theo khoảng thời gian',
            'route' => 'pages.export.chemicalExport.list', 'pane' => 'expPaneReport', 'params' => ['tab' => 'report'], 'perm' => 'export_chemical_view'],
    ],
    'Tồn Kho › Hoá Chất' => [
        ['id' => 'ton-kho', 'file' => 'inventory', 'title' => 'Xem Tồn Kho & Cân Đối', 'icon' => 'fas fa-boxes',
            'path' => 'Tồn Kho › Hoá Chất › tab Theo mã xuất nhập / Theo hoá chất / Theo định khu',
            'route' => 'pages.inventory.chemicalInventory.list', 'pane' => ['invPaneCode', 'invPaneChem', 'invPaneZone'], 'perm' => 'inventory_chemical_view', 'chips' => [$byDept],
            'keywords' => 'ton dau ky cuoi ky am kho sap het can doi bieu do'],
        ['id' => 'han-dung', 'file' => 'expiry', 'title' => 'Cận Hạn & Hạn Dùng Nội Bộ', 'icon' => 'fas fa-hourglass-half',
            'path' => 'Tồn Kho › Hoá Chất › tab Cận hạn dùng / Chưa có hạn nội bộ',
            'route' => 'pages.inventory.chemicalInventory.list', 'pane' => ['invPaneExpiring', 'invPaneInternal'], 'perm' => 'inventory_chemical_view',
            'keywords' => 'mo nap han ap dung het han'],
        ['id' => 'nguong-pl-iv', 'file' => 'threshold', 'title' => 'Ngưỡng Tồn Trữ PL IV', 'icon' => 'fas fa-exclamation-triangle',
            'path' => 'Tồn Kho › Hoá Chất › tab Đối chiếu ngưỡng PL IV',
            'route' => 'pages.inventory.chemicalInventory.list', 'pane' => 'invPaneThreshold', 'params' => ['tab' => 'threshold'], 'perm' => 'inventory_chemical_view', 'chips' => [$byCompany],
            'keywords' => 'phu luc iv bang a bang b nhom 9 nhom 10 ke hoach phong ngua ung pho su co'],
    ],
    'Dự Trù › Hoá Chất' => [
        ['id' => 'du-tru', 'file' => 'estimate', 'title' => 'Lập & Trình Ký Phiếu Dự Trù', 'icon' => 'fas fa-file-invoice',
            'path' => 'Dự Trù › Hoá Chất › tab Danh sách phiếu',
            'route' => 'pages.estimate.chemicalEstimate.list', 'pane' => 'list', 'params' => ['tab' => 'list'], 'perm' => 'estimate_chemical_view', 'chips' => [$sign],
            'keywords' => 'quan ly bo phan ban giam doc nguong'],
        ['id' => 'can-du-tru', 'file' => 'watchlist', 'title' => 'Danh Sách Hoá Chất Cần Dự Trù', 'icon' => 'fas fa-clipboard-list',
            'path' => 'Dự Trù › Hoá Chất › tab Danh sách hoá chất cần dự trù',
            'route' => 'pages.estimate.chemicalEstimate.list', 'pane' => 'watchlist', 'params' => ['tab' => 'watchlist'], 'perm' => 'estimate_chemical_view',
            'keywords' => 'duoi nguong toi thieu'],
        ['id' => 'theo-doi-du-tru', 'file' => 'estimateFollowUp', 'title' => 'Theo Dõi & Tiếp Nhận Dự Trù', 'icon' => 'fas fa-truck-loading',
            'path' => 'Dự Trù › Hoá Chất › tab Theo dõi dự trù / Đơn dự trù đã được phê duyệt',
            'route' => 'pages.estimate.chemicalEstimate.list', 'pane' => ['tracking', 'approved'], 'params' => ['tab' => 'tracking'], 'perm' => 'estimate_chemical_view',
            'keywords' => 'cung ung tiep nhan ngay hen huy muc trao doi'],
    ],
    'Hỗ trợ' => [
        ['id' => 'tra-cuu', 'file' => 'publicLookup', 'title' => 'Tra Cứu Không Cần Đăng Nhập', 'icon' => 'fas fa-search-location',
            'keywords' => 'tim vi tri hoa chat dang nhap'],
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi Thường Gặp', 'icon' => 'fas fa-question-circle'],
    ],
];
