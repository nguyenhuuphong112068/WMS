<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - PHÂN QUYỀN
|--------------------------------------------------------------------------
| Tài liệu chung cho toàn hệ thống. Các bảng vai trò và ma trận quyền đọc thẳng cấu hình
| Nhóm Quyền hiện tại (UserGuideController::permissionMatrix), nên tự khớp khi phân quyền đổi.
*/

$roleList = ['route' => 'pages.user.role.list', 'perm' => 'role_view', 'path' => 'Quản Trị › Phân Quyền › Nhóm Quyền'];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Cách Hệ Thống Phân Quyền', 'icon' => 'fas fa-sitemap',
            'keywords' => 'nhom quyen vai tro phong ban quyen cha quyen con admin'],
        ['id' => 'menu-trai', 'file' => 'menu', 'title' => 'Thanh Menu Bên Trái', 'icon' => 'fas fa-bars',
            'route' => 'pages.home',
            'keywords' => 'menu cot icon bang con ghim tim man hinh ctrl k doi phong ban chuyen bo phan'],
        ['id' => 'thiet-lap', 'file' => 'setup', 'title' => 'Thiết Lập Phân Quyền', 'icon' => 'fas fa-tools',
            'path' => 'Quản Trị › Phân Quyền › User / Nhóm Quyền',
            'route' => 'pages.user.user.list', 'perm' => 'user_view',
            'helpRoutes' => ['pages.user.role.list', 'pages.user.permission.list', 'pages.user.userPermission.list'],
            'keywords' => 'gan vai tro user pham vi phong ban cap quyen rieng cho phep tu choi tick'],
        ['id' => 'vai-tro', 'file' => 'roles', 'title' => 'Các Vai Trò Hiện Có', 'icon' => 'fas fa-users'] + $roleList,
    ],
    'Bảng Phân Quyền Chung' => [
        ['id' => 'bang-quyen-chung', 'file' => 'matrixCommon', 'title' => 'Quyền Chung', 'icon' => 'fas fa-layer-group',
            'keywords' => 'du lieu goc quan tri user audit trail lich su dang nhap'] + $roleList,
        ['id' => 'bang-vat-tu', 'file' => 'matrixMaterial', 'title' => 'Vật Tư', 'icon' => 'fas fa-cubes'] + $roleList,
        ['id' => 'bang-hoa-chat', 'file' => 'matrixChemical', 'title' => 'Hoá Chất', 'icon' => 'fas fa-flask'] + $roleList,
        ['id' => 'bang-chat-chuan', 'file' => 'matrixStandard', 'title' => 'Chất Chuẩn', 'icon' => 'fas fa-vial',
            'keywords' => 'danh gia han dung chuan thu cap'] + $roleList,
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi Thường Gặp', 'icon' => 'fas fa-question-circle'],
    ],
];
