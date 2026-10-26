<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - CHẤT CHUẨN
|--------------------------------------------------------------------------
| Module chất chuẩn trải trên Danh Mục, Nhập, Sử Dụng, Tồn Kho (gồm Đánh giá hạn dùng CTC) và Dự Trù.
| Mỗi mục là một file trong sections/, thường ứng với một tab (App\Support\PageTabs).
| params['tab'] chỉ truyền cho màn hình đọc ?tab= trên URL: Sử Dụng, Dự Trù, Tồn Kho (2 tab đánh giá hạn dùng).
*/

$approve = ['Có bước duyệt', 'is-warn'];
$sign = ['Ký bằng mật khẩu', 'is-warn'];
$byDept = ['Theo phòng ban đang chọn', ''];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Vòng Đời Ống Chuẩn', 'icon' => 'fas fa-compass'],
        ['id' => 'khai-niem-chung', 'file' => 'concepts', 'title' => 'Khái Niệm & Thao Tác Chung', 'icon' => 'fas fa-hand-pointer',
            'keywords' => 'ma ong chuan nhom chuan retest check online han noi bo mo ong xuat vuot'],
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Quyền Truy Cập', 'icon' => 'fas fa-user-shield'],
    ],
    'Danh Mục › Chất Chuẩn' => [
        ['id' => 'danh-muc-cong-ty', 'file' => 'companyCategory', 'title' => 'Danh Mục Chất Chuẩn Công Ty', 'icon' => 'fas fa-vial',
            'path' => 'Danh Mục › Chất Chuẩn › tab Danh mục chất chuẩn công ty',
            'route' => 'pages.category.standardCategory.list', 'pane' => 'tabCompany', 'perm' => 'category_standard_view', 'chips' => [$approve],
            'keywords' => 'phan nhom chuan prs imp ctc vkn han dung mac dinh'],
        ['id' => 'chuan-cua-phong', 'file' => 'departmentStandard', 'title' => 'Chất Chuẩn Của Phòng', 'icon' => 'fas fa-vials',
            'path' => 'Danh Mục › Chất Chuẩn › tab Chất chuẩn của phòng',
            'route' => 'pages.category.standardCategory.list', 'pane' => 'tabDepartment', 'perm' => 'category_standard_view', 'chips' => [$byDept],
            'keywords' => 'don vi tinh han dung sau khi mo ong nguong dinh khu'],
    ],
    'Nhập › Chất Chuẩn' => [
        ['id' => 'nhap-chuan', 'file' => 'importCheck', 'title' => 'Nhập Chất Chuẩn & Kiểm Tra', 'icon' => 'fas fa-dolly',
            'path' => 'Nhập › Chất Chuẩn › tab Nhập chất chuẩn',
            'route' => 'pages.import.standardImport.list', 'pane' => 'impPaneCheck', 'perm' => 'import_standard_view', 'chips' => [$byDept],
            'keywords' => 'ma ong chuan coa ham luong do am loai han dung kiem soat khoi luong chiet ong cho kiem tra biet tru dat khong dat'],
        ['id' => 'so-nhap', 'file' => 'importBook', 'title' => 'Sổ Nhập Chất Chuẩn', 'icon' => 'fas fa-book',
            'path' => 'Nhập › Chất Chuẩn › tab Sổ nhập chất chuẩn',
            'route' => 'pages.import.standardImport.list', 'pane' => 'impPaneBook', 'params' => ['tab' => 'book'], 'perm' => 'import_standard_view',
            'keywords' => 'in nhan dieu chinh khoa'],
    ],
    'Sử Dụng › Chất Chuẩn' => [
        ['id' => 'de-nghi-noi-bo', 'file' => 'request', 'title' => 'Đề Nghị & Cấp Phát Chuẩn Cho Tổ', 'icon' => 'fas fa-file-signature',
            'path' => 'Đề Nghị - Sử Dụng › Chất Chuẩn › tab Đề nghị cấp phát chuẩn nội bộ',
            'route' => 'pages.export.standardExport.list', 'pane' => 'expPaneRequest', 'params' => ['tab' => 'request'], 'perm' => 'export_standard_view',
            'keywords' => 'to de nghi thu kho cap phat tra lai chuan'],
        ['id' => 'so-su-dung', 'file' => 'use', 'title' => 'Ghi Sử Dụng & Sổ Sử Dụng', 'icon' => 'fas fa-book',
            'path' => 'Đề Nghị - Sử Dụng › Chất Chuẩn › tab Sổ sử dụng chất chuẩn',
            'route' => 'pages.export.standardExport.list', 'pane' => 'expPaneBook', 'params' => ['tab' => 'book'], 'perm' => 'export_standard_view',
            'keywords' => 'phieu su dung khoi luong bi chuan san pham chi tieu'],
        ['id' => 'lien-phong-ban', 'file' => 'transfer', 'title' => 'Đề Nghị Cấp Phát Liên Phòng Ban', 'icon' => 'fas fa-people-arrows',
            'path' => 'Đề Nghị - Sử Dụng › Chất Chuẩn › tab Đề nghị cấp phát chuẩn liên phòng ban',
            'route' => 'pages.export.standardExport.list', 'pane' => 'expPaneTransfer', 'params' => ['tab' => 'transfer'], 'perm' => 'export_standard_view',
            'keywords' => 'chuyen phong khac nhan tu choi nhan'],
        ['id' => 'cach-ly', 'file' => 'quarantine', 'title' => 'Cách Ly - Loại Bỏ - Huỷ', 'icon' => 'fas fa-biohazard',
            'path' => 'Đề Nghị - Sử Dụng › Chất Chuẩn › tab Cách ly - Loại bỏ - Huỷ',
            'route' => 'pages.export.standardExport.list', 'pane' => 'expPaneQuarantine', 'params' => ['tab' => 'quarantine'], 'perm' => 'export_standard_view', 'chips' => [$sign],
            'keywords' => 'ong hong het han loai bo cho huy tra ve kho cho quyet dinh oos'],
    ],
    'Tồn Kho › Chất Chuẩn' => [
        ['id' => 'ton-kho', 'file' => 'inventory', 'title' => 'Xem Tồn Kho & Cân Đối', 'icon' => 'fas fa-boxes',
            'path' => 'Tồn Kho › Chất Chuẩn › tab Theo mã ống chuẩn / Theo chất chuẩn / Theo định khu',
            'route' => 'pages.inventory.standardInventory.list', 'pane' => ['invPaneCode', 'invPaneChem', 'invPaneZone'], 'perm' => 'inventory_standard_view', 'chips' => [$byDept],
            'keywords' => 'ton dau ky cuoi ky am kho can doi'],
        ['id' => 'han-dung', 'file' => 'expiry', 'title' => 'Hạn Dùng: Retest, Check Online, Nội Bộ', 'icon' => 'fas fa-hourglass-half',
            'path' => 'Tồn Kho › Chất Chuẩn › tab Cận hạn dùng / Chưa có hạn nội bộ',
            'route' => 'pages.inventory.standardInventory.list', 'pane' => ['invPaneExpiring', 'invPaneInternal'], 'perm' => 'inventory_standard_view',
            'keywords' => 'cap nhat han retest check online mo ong han noi bo'],
        ['id' => 'khoi-luong', 'file' => 'weight', 'title' => 'Kiểm Soát Khối Lượng', 'icon' => 'fas fa-balance-scale',
            'path' => 'Tồn Kho › Chất Chuẩn › tab Kiểm soát khối lượng',
            'route' => 'pages.inventory.standardInventory.list', 'pane' => 'invPaneWeight', 'perm' => 'inventory_standard_view',
            'keywords' => 'khoi luong bi gioi han sai lech bot roi bot min set nhan xet'],
        ['id' => 'danh-gia', 'file' => 'stability', 'title' => 'Đánh Giá Hạn Dùng CTC', 'icon' => 'fas fa-clipboard-list',
            'path' => 'Tồn Kho › Chất Chuẩn › tab Đánh giá hạn dùng CTC',
            'route' => 'pages.inventory.standardInventory.list', 'pane' => 'invPaneStability', 'params' => ['tab' => 'stability'],
            'helpRoutes' => ['pages.inventory.standardStability.detail'], 'perm' => 'stability_standard_view',
            'keywords' => 'chuan thu cap ctc phieu danh gia moc t0 t3 t6 dat khong dat dung danh gia dod'],
        ['id' => 'ke-hoach', 'file' => 'plan', 'title' => 'Kế Hoạch Đánh Giá', 'icon' => 'fas fa-calendar-alt',
            'path' => 'Tồn Kho › Chất Chuẩn › tab Kế hoạch đánh giá',
            'route' => 'pages.inventory.standardInventory.list', 'pane' => 'invPanePlan', 'params' => ['tab' => 'stabilityPlan'], 'perm' => 'stability_plan_view',
            'keywords' => 'den han qua han sap den han'],
    ],
    'Dự Trù › Chất Chuẩn' => [
        ['id' => 'du-tru', 'file' => 'estimate', 'title' => 'Lập & Trình Ký Phiếu Dự Trù', 'icon' => 'fas fa-file-invoice',
            'path' => 'Dự Trù › Chất Chuẩn › tab Danh sách phiếu',
            'route' => 'pages.estimate.standardEstimate.list', 'pane' => 'list', 'params' => ['tab' => 'list'], 'perm' => 'estimate_standard_view', 'chips' => [$sign],
            'keywords' => 'quan ly bo phan ban giam doc'],
        ['id' => 'can-du-tru', 'file' => 'watchlist', 'title' => 'Danh Sách Chất Chuẩn Cần Dự Trù', 'icon' => 'fas fa-clipboard-list',
            'path' => 'Dự Trù › Chất Chuẩn › tab Danh sách chất chuẩn cần dự trù',
            'route' => 'pages.estimate.standardEstimate.list', 'pane' => 'watchlist', 'params' => ['tab' => 'watchlist'], 'perm' => 'estimate_standard_view',
            'keywords' => 'duoi nguong toi thieu'],
        ['id' => 'theo-doi-du-tru', 'file' => 'estimateFollowUp', 'title' => 'Theo Dõi & Tiếp Nhận Dự Trù', 'icon' => 'fas fa-truck-loading',
            'path' => 'Dự Trù › Chất Chuẩn › tab Theo dõi dự trù / Đơn dự trù đã được phê duyệt',
            'route' => 'pages.estimate.standardEstimate.list', 'pane' => ['tracking', 'approved'], 'params' => ['tab' => 'tracking'], 'perm' => 'estimate_standard_view',
            'keywords' => 'cung ung tiep nhan ngay hen huy muc'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi Thường Gặp', 'icon' => 'fas fa-question-circle'],
    ],
];
