<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - PHÂN QUYỀN
|--------------------------------------------------------------------------
| Tài liệu chung cho toàn hệ thống. Các bảng vai trò và ma trận quyền đọc thẳng cấu hình
| Nhóm Quyền hiện tại (UserGuideController::permissionMatrix), nên tự khớp khi phân quyền đổi.
| Bảng phân quyền chung chia theo module leftNAV (PermissionMatrix::MODULES), dùng chung file sections/matrixModule.
*/

use App\Support\PermissionMatrix;

$roleList = ['route' => 'pages.user.role.list', 'perm' => 'role_view', 'path' => 'Quản Trị › Phân Quyền › Nhóm Quyền'];

$matrixKeywords = [
    'materData' => 'du lieu goc ten vat tu hoa chat chat chuan trinh ky khoi tao',
    'category' => 'danh muc cong ty phong ban duyet',
    'import' => 'nhap kho kiem tra in nhan',
    'export' => 'de nghi cap phat su dung cach ly lien phong ban vpp',
    'inventory' => 'ton kho kiem ke danh gia han dung chuan thu cap',
    'estimate' => 'du tru ky duyet mua hang',
    'admin' => 'quan tri user nhom quyen audit trail lich su dang nhap',
];

$matrixItems = [];
foreach (PermissionMatrix::MODULES as $moduleKey => $module) {
    if ($moduleKey === 'other') {
        continue;
    }
    $matrixItems[] = ['id' => 'bang-' . $moduleKey, 'file' => 'matrixModule', 'module' => $moduleKey,
        'title' => $module['label'], 'icon' => 'fas ' . $module['icon'],
        'path' => $roleList['path'] . ' › khối ' . $module['label'], 'shots' => false,
        'keywords' => $matrixKeywords[$moduleKey] ?? ''] + $roleList;
}

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Cách Hệ Thống Phân Quyền', 'icon' => 'fas fa-sitemap',
            'keywords' => 'nhom quyen vai tro phong ban quyen cha quyen con admin'],
        ['id' => 'menu-trai', 'file' => 'menu', 'title' => 'Thanh Menu Bên Trái', 'icon' => 'fas fa-bars',
            'route' => 'pages.home',
            // Mở màn Nhóm Quyền để bảng con (Phân Quyền) đang hiện cạnh cột icon
            'shots' => [['name' => 'menu-trai', 'url' => '/user/role', 'clip' => '.wms-rail, .wms-panel', 'maxHeight' => 760, 'pad' => 0]],
            'keywords' => 'menu cot icon bang con ghim tim man hinh ctrl k doi phong ban chuyen bo phan'],
        ['id' => 'thiet-lap', 'file' => 'setup', 'title' => 'Thiết Lập Phân Quyền', 'icon' => 'fas fa-tools',
            'path' => 'Quản Trị › Phân Quyền › User / Nhóm Quyền',
            'route' => 'pages.user.user.list', 'perm' => 'user_view',
            'helpRoutes' => ['pages.user.role.list', 'pages.user.permission.list', 'pages.user.userPermission.list'],
            // Ảnh từng bước; ảnh lọc để cuối vì bộ lọc được trình duyệt nhớ cho các lần mở sau
            'shots' => [
                // Hộp thoại Quản Lý Nhóm Quyền đang sửa một nhóm có sẵn để thấy ô Loại Phụ Trách đã tick
                ['name' => 'thiet-lap-tao-nhom', 'url' => '/user/role', 'clip' => '#roleForm', 'pad' => 0, 'afterJs' => 800,
                    'js' => "(async () => { __ug.click('[data-target=\"#manageRoleModal\"]'); await new Promise(r => setTimeout(r, 800)); __ug.click('.btn-edit-role'); return true; })()"],
                // Khung nhìn cao để trang không bị cuộn (cuộn thì thanh tiêu đề che mất thanh công cụ)
                ['name' => 'thiet-lap-nhom-quyen', 'url' => '/user/role', 'height' => 3600, 'maxHeight' => 760, 'pad' => 0,
                    'clip' => '.perm-toolbar-card, .perm-model-card[data-model="materData"]'],
                ['name' => 'thiet-lap-loc', 'url' => '/user/role', 'height' => 3600, 'maxHeight' => 640, 'pad' => 0,
                    'clip' => '.perm-toolbar-card, .perm-model-card[data-model="materData"]',
                    'js' => "__ug.click('.perm-kind-filter [data-kind=\"chemical\"]'); __ug.click('#btnRoleFilter')",
                    'highlight' => ['.perm-kind-filter', '.perm-role-filter']],
                ['name' => 'thiet-lap-user', 'url' => '/user/user', 'js' => "__ug.click('.btn-edit')", 'afterJs' => 2000,
                    'clip' => '#UpdateModal .modal-content', 'maxHeight' => 1400, 'pad' => 0],
                ['name' => 'thiet-lap-quyen-rieng', 'url' => '/user/user', 'js' => "__ug.click('.btn-user-permission')", 'afterJs' => 2500,
                    'clip' => '#UserPermissionModal .modal-content', 'maxHeight' => 640, 'pad' => 0],
            ],
            'keywords' => 'gan vai tro user pham vi phong ban cap quyen rieng cho phep tu choi tick'],
    ],
    // Một mục cho mỗi module trên menu trái, cùng thứ tự với các khối trên màn hình Nhóm Quyền
    'Bảng Phân Quyền Chung' => $matrixItems,
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi Thường Gặp', 'icon' => 'fas fa-question-circle'],
    ],
];
