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
            'route' => 'pages.materData.company.list', 'perm' => md_perm('company', 'view')],
        ['id' => 'phong-ban', 'file' => 'department', 'title' => 'Phòng Ban', 'icon' => 'fas fa-building',
            'route' => 'pages.materData.department.list', 'perm' => md_perm('department', 'view')],
        ['id' => 'to', 'file' => 'group', 'title' => 'Tổ', 'icon' => 'fas fa-users',
            'route' => 'pages.materData.group.list', 'perm' => md_perm('group', 'view')],
    ],
    'Danh mục dùng chung' => [
        ['id' => 'don-vi-tinh', 'file' => 'unit', 'title' => 'Đơn Vị Tính', 'icon' => 'fas fa-balance-scale',
            'route' => 'pages.materData.unit.list', 'perm' => md_perm('unit', 'view'), 'chips' => [$approve],
            'keywords' => 'quy doi he so kg g ml lit'],
        ['id' => 'quy-cach-dong-goi', 'file' => 'packagingSpecification', 'title' => 'Quy Cách Đóng Gói', 'icon' => 'fas fa-box-open',
            'route' => 'pages.materData.packagingSpecification.list', 'perm' => md_perm('packagingSpecification', 'view'), 'chips' => [$approve]],
        ['id' => 'dieu-kien-bao-quan', 'file' => 'storageCondition', 'title' => 'Điều Kiện Bảo Quản', 'icon' => 'fas fa-temperature-low',
            'route' => 'pages.materData.storageCondition.list', 'perm' => md_perm('storageCondition', 'view'), 'chips' => [$approve]],
        ['id' => 'nha-san-xuat', 'file' => 'chemManufacturer', 'title' => 'Nhà Sản Xuất', 'icon' => 'fas fa-industry',
            'route' => 'pages.materData.chemManufacturer.list', 'perm' => md_perm('chemManufacturer', 'view'), 'chips' => [$approve]],
        ['id' => 'nha-cung-cap', 'file' => 'chemSupplier', 'title' => 'Nhà Cung Cấp', 'icon' => 'fas fa-truck',
            'route' => 'pages.materData.chemSupplier.list', 'perm' => md_perm('chemSupplier', 'view'), 'chips' => [$approve],
            'keywords' => 'ma so thue dia chi'],
        ['id' => 'ten-san-pham', 'file' => 'productName', 'title' => 'Tên Sản Phẩm', 'icon' => 'fas fa-pills',
            'route' => 'pages.materData.productName.list', 'perm' => md_perm('productName', 'view')],
    ],
    'Hoá chất' => [
        ['id' => 'hoat-chat', 'file' => 'activeIngredient', 'title' => 'Đơn Chất', 'icon' => 'fas fa-atom',
            'route' => 'pages.materData.activeIngredient.list', 'perm' => md_perm('activeIngredient', 'view'), 'chips' => [$approve],
            'keywords' => 'hoat chat don chat cas nghi dinh 24 phu luc nhom 9 nguong ton tru muc gop'],
        ['id' => 'hoa-chat', 'file' => 'chemName', 'title' => 'Hoá Chất', 'icon' => 'fas fa-flask',
            'route' => 'pages.materData.chemName.list', 'perm' => md_perm('chemName', 'view'), 'chips' => [$approve],
            'keywords' => 'ten hoa chat hon hop thanh phan nhom 2 8 10 11 12 cam hang hoa dac biet'],
    ],
    'Chất chuẩn' => [
        ['id' => 'ten-chuan', 'file' => 'standardName', 'title' => 'Tên Chuẩn', 'icon' => 'fas fa-flask',
            'route' => 'pages.materData.standardName.list', 'perm' => md_perm('standardName', 'view'), 'chips' => [$approve]],
        ['id' => 'chi-tieu-kiem', 'file' => 'purpose', 'title' => 'Chỉ Tiêu Kiểm', 'icon' => 'fas fa-vial',
            'route' => 'pages.materData.purpose.list', 'perm' => md_perm('purpose', 'view')],
    ],
    'Vật tư' => [
        ['id' => 'ten-vat-tu', 'file' => 'materialName', 'title' => 'Tên Vật Tư', 'icon' => 'fas fa-cubes',
            'route' => 'pages.materData.materialName.list', 'perm' => md_perm('materialName', 'view'), 'chips' => [$approve]],
        ['id' => 'phan-loai', 'file' => 'departmentClassification', 'shotDept' => 4, 'title' => 'Phân Loại', 'icon' => 'fas fa-tags',
            'route' => 'pages.materData.departmentClassification.list', 'perm' => md_perm('departmentClassification', 'view'), 'chips' => [$byDept]],
        ['id' => 'trinh-ky-cap-phat', 'file' => 'materialSignFlow', 'shotDept' => 4, 'title' => 'Trình Ký Đề Nghị CP Vật Tư', 'icon' => 'fas fa-file-signature',
            'route' => 'pages.materData.materialSignFlow.list', 'perm' => md_perm('materialSignFlow', 'view'), 'chips' => [$byDept],
            'keywords' => 'cap ky nguoi ky vai tro dieu kien'],
        ['id' => 'trinh-ky-lien-phong-ban', 'file' => 'transferSignFlow', 'title' => 'Trình Ký Đề Nghị Liên Phòng Ban', 'icon' => 'fas fa-route',
            'route' => 'pages.materData.transferSignFlow.list', 'perm' => 'materData_transferSign_view',
            'keywords' => 'vpp van phong pham hanh chanh buoc ky'],
    ],
    'Kho & đối tượng' => [
        ['id' => 'dinh-khu', 'file' => 'zone', 'title' => 'Định Khu', 'icon' => 'fas fa-sitemap',
            'route' => 'pages.materData.zone.list', 'perm' => md_perm('zone', 'view'), 'chips' => [$byDept],
            // Phòng có sẵn kho/kệ; ảnh thêm = khung Thông số kệ/tủ + mô hình 3D khi tạo kệ mới
            'shotDept' => 4,
            'extraShots' => [
                ['name' => 'dinh-khu-thong-so', 'clip' => '#zoneStructure', 'maxHeight' => 1150, 'pad' => 0, 'afterJs' => 1500,
                    'js' => "(async () => { const w = ms => new Promise(r => setTimeout(r, ms));"
                        ." try { localStorage.setItem('wms.zone.spec', JSON.stringify({columns: 3, tiers: 4, max: 5, depth: 2})); } catch (e) {}"
                        ." for (let i = 0; i < 20 && !document.querySelector('#zs-warehouses .zs-tile'); i++) await w(300);"
                        ." document.querySelector('#zs-warehouses .zs-tile').click(); await w(1500);"
                        ." document.querySelector('#zs-add').click(); await w(800); return true; })()"],
            ],
            'keywords' => 'kho phong ke tu cot tang vi tri cau truc kho biet tru du phong thong so 3d'],
        ['id' => 'doi-tuong', 'file' => 'consumptionObject', 'title' => 'Đối Tượng', 'icon' => 'fas fa-cogs',
            'route' => 'pages.materData.consumptionObject.list', 'perm' => md_perm('consumptionObject', 'view'),
            'keywords' => 'thiet bi cal dong bo tan suat'],
    ],
    'Quản trị' => [
        ['id' => 'khoi-tao-du-lieu', 'file' => 'initialData', 'title' => 'Khởi Tạo Dữ Liệu', 'icon' => 'fas fa-file-import',
            'route' => 'pages.materData.initialData.list', 'perm' => 'materData_initialData_upload',
            'keywords' => 'excel file mau nap ton dau ky dinh kem upload theo doi tien do',
            // Mở việc đầu tiên đã có file nộp (giai đoạn 2 - 4) kèm lịch sử nộp, để ảnh có nút Xem / Tải;
            // chưa có file nào thì mở tab Giai đoạn 3
            'shots' => [['name' => 'khoi-tao-du-lieu', 'clip' => '.content-wrapper', 'maxHeight' => 1100,
                'js' => "(() => { var \$b = $('.btn-idt-preview').filter(function () { return !$(this).closest('#idtStage1').length; }).first();"
                    ." if (!\$b.length) { $('.idt-stage-tabs a[href=\"#idtStage3\"]').tab('show'); return; }"
                    ." var \$g = \$b.closest('.idt-group'); $('.idt-stage-tabs a[href=\"#' + \$b.closest('.tab-pane').attr('id') + '\"]').tab('show');"
                    ." $('.idt-company-filter .btn').filter(function () { return $(this).data('company') === \$g.data('company'); }).click();"
                    ." $('#idtSearch').val(String(\$g.data('name')).split(' - ')[0]).trigger('input');"
                    ." \$b.closest('.idt-task').find('.btn-idt-history').click(); })()"]],
            // Hộp thoại Xem file: dùng file Giai đoạn 1 (tổ chức) để ảnh không chứa thông tin cá nhân người dùng
            'extraShots' => [['name' => 'khoi-tao-du-lieu-xem', 'url' => '/materData/initialData', 'clip' => '.modal.show .modal-content', 'pad' => 0, 'afterJs' => 2500,
                'js' => "$('.idt-stage-tabs a[href=\"#idtStage1\"]').tab('show'); $('#idtStage1 .btn-idt-preview').first().click()"]]],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi Thường Gặp', 'icon' => 'fas fa-question-circle'],
    ],
];
