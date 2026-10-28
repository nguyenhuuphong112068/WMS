<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - BẮT ĐẦU & BẢO MẬT
|--------------------------------------------------------------------------
| Tài liệu chung cho mọi người dùng: đăng nhập - mật khẩu - hết phiên (LoginController,
| UpdateUserActivity), Trang chủ (HomeController), chữ ký điện tử (VerifiesSignature) và
| các màn hình truy vết theo 21 CFR Part 11 (Lịch Sử Đăng Nhập, Audit Trail).
*/

return [
    'Đăng nhập' => [
        ['id' => 'dang-nhap', 'file' => 'login', 'title' => 'Đăng Nhập & Đăng Xuất', 'icon' => 'fas fa-sign-in-alt',
            'keywords' => 'dang nhap dang xuat tai khoan username mat khau sai bi khoa'],
        ['id' => 'doi-mat-khau', 'file' => 'changePassword', 'title' => 'Đổi Mật Khẩu', 'icon' => 'fas fa-key',
            'keywords' => 'doi mat khau lan dau het han 90 ngay do phuc tap chu hoa ky tu dac biet trung mat khau cu'],
        ['id' => 'khoa-het-phien', 'file' => 'lockTimeout', 'title' => 'Khoá Tài Khoản & Hết Phiên', 'icon' => 'fas fa-user-lock',
            'keywords' => 'khoa tai khoan 5 lan 15 phut tu dang xuat het phien khong thao tac vo hieu hoa'],
        ['id' => 'tra-cuu-cong-khai', 'file' => 'publicLookup', 'title' => 'Tra Cứu Hoá Chất Không Cần Đăng Nhập', 'icon' => 'fas fa-search',
            'path' => 'Trang đăng nhập › Tra cứu tồn kho hoá chất',
            'route' => 'publicChemicalLookup',
            'keywords' => 'tra cuu cong khai ton kho vi tri hoa chat khong dang nhap'],
    ],
    'Trang Chủ' => [
        ['id' => 'trang-chu', 'file' => 'home', 'title' => 'Trang Chủ', 'icon' => 'fas fa-home',
            'path' => 'Trang chủ', 'route' => 'pages.home',
            'keywords' => 'trang chu dashboard banner thanh tren cung thong bao chuong nut hoi'],
        ['id' => 'ma-tran', 'file' => 'homeMatrix', 'title' => 'Ma Trận Tổng Quan', 'icon' => 'fas fa-th',
            'path' => 'Trang chủ › Ma trận tổng quan',
            'keywords' => 'ma tran tong quan nghiep vu doi tuong vat tu hoa chat chat chuan dung chung nhan gui'],
        ['id' => 'the-de-nghi', 'file' => 'homeCards', 'title' => 'Đề Nghị Chờ Bạn Xem Xét', 'icon' => 'fas fa-file-signature',
            'path' => 'Trang chủ › Đề nghị chờ bạn xem xét',
            'keywords' => 'the de nghi cho ky duyet cho phong ban xu ly da cap cho ban tu chuyen phong'],
    ],
    'Bảo mật - 21 CFR Part 11' => [
        ['id' => 'cfr-part-11', 'file' => 'cfr', 'title' => 'Tuân Thủ 21 CFR Part 11', 'icon' => 'fas fa-shield-alt',
            'keywords' => 'cfr part 11 gmp ho so dien tu chu ky dien tu tuan thu truy vet'],
        ['id' => 'lich-su-dang-nhap', 'file' => 'loginHistory', 'title' => 'Lịch Sử Đăng Nhập', 'icon' => 'fas fa-history',
            'path' => 'Lịch Sử Đăng Nhập › Toàn Hệ Thống / Của Tôi',
            'route' => 'pages.loginHistory.list', 'perm' => 'login_history_view',
            'helpRoutes' => ['pages.loginHistory.mine'],
            'keywords' => 'lich su dang nhap phien dang truc tuyen ip trinh duyet cua toi xuat excel'],
        ['id' => 'audit-trail', 'file' => 'auditTrail', 'title' => 'Audit Trail', 'icon' => 'fas fa-fingerprint',
            'path' => 'Lịch Sử Đăng Nhập › Audit Trail',
            'route' => 'pages.auditTrail.list', 'perm' => 'auditTrail_view',
            'keywords' => 'audit trail nhat ky thao tac gia tri cu gia tri moi truy vet'],
        ['id' => 'quan-tri-tai-khoan', 'file' => 'accountAdmin', 'title' => 'Quản Trị Tài Khoản', 'icon' => 'fas fa-users-cog',
            'path' => 'Quản Trị › Phân Quyền › User',
            'keywords' => 'tao tai khoan reset mat khau tam vo hieu hoa mo khoa quan tri'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi Thường Gặp', 'icon' => 'fas fa-question-circle'],
    ],
];
