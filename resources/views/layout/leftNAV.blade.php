<style>
    /* ==========================================================
       LEFT NAV - WMS: cột icon cố định + bảng con mở bên cạnh
       - .wms-rail  : cột icon 76px, luôn hiện (màn hình nhỏ thì ẩn, mở bằng nút ☰).
       - .wms-panel : bảng con 248px của mục đang chọn. Ghim thì bảng nằm cố định và
                      đẩy nội dung sang phải; bỏ ghim thì bảng nổi đè lên nội dung,
                      tự đóng khi bấm ra ngoài. Trạng thái ghim nhớ theo máy (localStorage).
       Không dùng .main-sidebar / pushmenu của AdminLTE nữa nên lề trái của
       .main-header / .content-wrapper do biến --wms-nav-offset quyết định.
       ========================================================== */
    :root {
        --wms-rail-w: 76px;
        --wms-panel-w: 248px;
    }

    body {
        --wms-nav-offset: var(--wms-rail-w);
    }

    body.wms-panel-docked {
        --wms-nav-offset: calc(var(--wms-rail-w) + var(--wms-panel-w));
    }

    body .content-wrapper,
    body .main-header,
    body .main-footer {
        margin-left: var(--wms-nav-offset) !important;
        transition: margin-left var(--transition-normal);
    }

    /* ---------- Cột icon ---------- */
    .wms-rail {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 1038;
        width: var(--wms-rail-w);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        padding: 10px 0;
        background: var(--surface);
        border-right: 1px solid var(--border-soft);
        box-shadow: 2px 0 10px rgba(var(--primary-rgb), 0.05);
        overflow-x: hidden;
        overflow-y: auto;
        scrollbar-width: none;
    }

    .wms-rail::-webkit-scrollbar {
        display: none;
    }

    .wms-rail-logo {
        display: grid;
        place-items: center;
        width: 52px;
        height: 44px;
        margin-bottom: 2px;
        flex-shrink: 0;
    }

    .wms-rail-logo img {
        width: 36px;
        height: auto;
    }

    .wms-rbtn {
        position: relative;
        width: 66px;
        flex-shrink: 0;
        display: grid;
        justify-items: center;
        gap: 3px;
        padding: 7px 2px 6px;
        border: 0;
        border-radius: 10px;
        background: none;
        color: var(--text-muted);
        font-size: 10.5px;
        font-weight: 600;
        line-height: 1.15;
        text-align: center;
        cursor: pointer;
        transition: background var(--transition-fast), color var(--transition-fast);
    }

    .wms-rbtn i {
        font-size: 17px;
        color: var(--primary-lighter);
        transition: color var(--transition-fast);
    }

    .wms-rbtn:hover {
        background: var(--primary-soft);
        color: var(--primary-dark);
        text-decoration: none;
    }

    .wms-rbtn:hover i {
        color: var(--primary);
    }

    .wms-rbtn:focus-visible,
    .wms-link:focus-visible,
    .wms-tile:focus-visible,
    .wms-icon-btn:focus-visible {
        outline: 2px solid var(--primary);
        outline-offset: 1px;
    }

    /* màn hình hiện tại thuộc mục này */
    .wms-rbtn.is-current {
        color: var(--primary);
    }

    .wms-rbtn.is-current i {
        color: var(--primary);
    }

    .wms-rbtn.is-current::before {
        content: "";
        position: absolute;
        left: -5px;
        top: 9px;
        bottom: 9px;
        width: 3px;
        border-radius: 0 3px 3px 0;
        background: var(--primary);
    }

    /* mục đang mở bảng con */
    .wms-rbtn.is-open {
        background: var(--primary-soft);
        color: var(--primary-dark);
    }

    .wms-rbtn.is-open i {
        color: var(--primary);
    }

    .wms-rail-sep {
        width: 36px;
        margin: 5px 0;
        border-top: 1px solid var(--border-soft);
        flex-shrink: 0;
    }

    .wms-rail-grow {
        flex: 1;
        min-height: 8px;
    }

    .wms-rbtn.wms-dept {
        margin-top: 4px;
        padding: 7px 3px;
        border: 1px solid var(--border-soft);
        background: var(--bg-neutral);
        color: var(--primary-dark);
        word-break: break-word;
    }

    .wms-rbtn.wms-dept[disabled] {
        cursor: default;
    }

    /* ---------- Bảng con ---------- */
    .wms-panel {
        position: fixed;
        top: 0;
        bottom: 0;
        left: var(--wms-rail-w);
        z-index: 1036;
        width: var(--wms-panel-w);
        display: flex;
        flex-direction: column;
        background: var(--surface);
        border-right: 1px solid var(--border-soft);
    }

    body:not(.wms-panel-docked) .wms-panel {
        z-index: 1039;
        box-shadow: var(--shadow-md), 8px 0 28px rgba(var(--primary-rgb), 0.12);
    }

    .wms-panel-head {
        display: flex;
        align-items: center;
        gap: 6px;
        height: calc(3.5rem + 1px);
        padding: 0 10px 0 18px;
        border-bottom: 1px solid var(--border-soft);
        flex-shrink: 0;
    }

    .wms-panel-title {
        flex: 1;
        min-width: 0;
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--primary-dark);
        letter-spacing: 0.3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .wms-icon-btn {
        width: 30px;
        height: 30px;
        display: grid;
        place-items: center;
        border: 0;
        border-radius: var(--border-radius-md);
        background: none;
        color: var(--text-muted);
        cursor: pointer;
        transition: background var(--transition-fast), color var(--transition-fast);
    }

    .wms-icon-btn:hover {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .wms-icon-btn.is-on {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .wms-panel-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 10px 10px 20px;
        scrollbar-width: thin;
        scrollbar-color: var(--primary-lighter) transparent;
    }

    .wms-grp {
        padding: 12px 10px 4px;
        color: var(--text-muted);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .wms-grp:first-child {
        padding-top: 2px;
    }

    .wms-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 7px 10px;
        margin: 1px 0;
        border-radius: var(--border-radius-md);
        color: var(--text-main);
        font-size: 0.88rem;
        font-weight: 500;
        transition: background var(--transition-fast), color var(--transition-fast);
    }

    .wms-link i {
        width: 18px;
        text-align: center;
        font-size: 0.85rem;
        color: var(--primary-lighter);
        flex-shrink: 0;
        transition: color var(--transition-fast);
    }

    .wms-link:hover {
        background: var(--primary-soft);
        color: var(--primary-dark);
        text-decoration: none;
    }

    .wms-link:hover i,
    .wms-link.active i {
        color: var(--primary);
    }

    .wms-link.active {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 600;
    }

    /* thẻ loại hàng (Vật Tư / Hoá Chất / Chất Chuẩn) */
    .wms-tiles {
        display: grid;
        gap: 8px;
    }

    .k-material { --c: var(--db-group-material); --c-rgb: var(--db-group-material-rgb); }
    .k-chemical { --c: var(--db-group-chemical); --c-rgb: var(--db-group-chemical-rgb); }
    .k-standard { --c: var(--db-group-standard); --c-rgb: var(--db-group-standard-rgb); }

    .wms-tile {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border: 1px solid var(--border-soft);
        border-radius: 10px;
        color: var(--text-main);
        transition: background var(--transition-fast), border-color var(--transition-fast), transform var(--transition-fast);
    }

    .wms-tile:hover {
        border-color: rgba(var(--c-rgb), 0.45);
        background: rgba(var(--c-rgb), 0.05);
        color: var(--text-main);
        text-decoration: none;
        transform: translateY(-1px);
    }

    .wms-tile-ic {
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        border-radius: var(--border-radius-md);
        background: rgba(var(--c-rgb), 0.12);
        color: var(--c);
        font-size: 16px;
    }

    .wms-tile b {
        display: block;
        font-size: 0.9rem;
        line-height: 1.3;
    }

    .wms-tile small {
        display: block;
        color: var(--text-muted);
        font-size: 0.74rem;
        line-height: 1.3;
    }

    .wms-tile.active {
        border-color: var(--c);
        background: rgba(var(--c-rgb), 0.08);
    }

    .wms-tile.active b {
        color: var(--c);
    }

    /* ---------- Tìm màn hình (Ctrl K) ---------- */
    .wms-cmd {
        position: fixed;
        inset: 0;
        z-index: 1060;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 12vh 16px 16px;
        background: rgba(15, 23, 42, 0.35);
    }

    .wms-cmd-box {
        width: 100%;
        max-width: 540px;
        overflow: hidden;
        background: var(--surface);
        border-radius: var(--border-radius-lg);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    }

    .wms-cmd-in {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-muted);
    }

    .wms-cmd-in input {
        flex: 1;
        border: 0;
        outline: none;
        background: none;
        font-size: 1rem;
        color: var(--text-main);
    }

    .wms-cmd-list {
        max-height: 50vh;
        overflow-y: auto;
        padding: 6px;
    }

    .wms-cmd-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: var(--border-radius-md);
        color: var(--text-main);
        font-size: 0.9rem;
    }

    .wms-cmd-row i {
        width: 18px;
        text-align: center;
        color: var(--primary-lighter);
    }

    .wms-cmd-row small {
        margin-left: auto;
        color: var(--text-muted);
        font-size: 0.75rem;
    }

    .wms-cmd-row:hover,
    .wms-cmd-row.is-on {
        background: var(--primary-soft);
        color: var(--primary-dark);
        text-decoration: none;
    }

    .wms-cmd-row.is-on i {
        color: var(--primary);
    }

    .wms-cmd-empty {
        padding: 20px;
        text-align: center;
        color: var(--text-muted);
    }

    .wms-cmd-foot {
        display: flex;
        gap: 16px;
        padding: 8px 16px;
        border-top: 1px solid var(--border-soft);
        color: var(--text-muted);
        font-size: 0.74rem;
    }

    .wms-cmd kbd {
        padding: 1px 6px;
        border: 1px solid var(--border-soft);
        border-bottom-width: 2px;
        border-radius: 5px;
        background: var(--bg-neutral);
        color: var(--text-muted);
        font-size: 0.7rem;
        box-shadow: none;
    }

    .wms-nav-backdrop {
        display: none;
    }

    /* ---------- Màn hình nhỏ: cột icon ẩn, mở bằng nút ☰ trên topNAV ---------- */
    @media (max-width: 991.98px) {

        body,
        body.wms-panel-docked {
            --wms-nav-offset: 0px;
        }

        .wms-rail,
        .wms-panel {
            transform: translateX(calc(-1 * (var(--wms-rail-w) + var(--wms-panel-w))));
            transition: transform var(--transition-normal);
        }

        body.wms-nav-mobile-open .wms-rail,
        body.wms-nav-mobile-open .wms-panel {
            transform: none;
        }

        body.wms-nav-mobile-open .wms-nav-backdrop {
            display: block;
            position: fixed;
            inset: 0;
            z-index: 1035;
            background: rgba(15, 23, 42, 0.3);
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .wms-rail,
        .wms-panel,
        body .content-wrapper,
        body .main-header {
            transition: none !important;
        }
    }
</style>

@php
    $currentDept = session('user')['selected_department'] ?? (session('user')['department'] ?? null);
    $currentCompanyId = session('user')['company_id'] ?? null;

    // Phòng ban user được phép làm việc (Admin -> tất cả; user thường -> phòng được gán role).
    // Phòng ban chung (is_general = 0, VD: BOD, Cung Ứng) chỉ để tạo user, không có kho riêng.
    $allowedDeptIds = user_allowed_department_ids(session('user')['userId'] ?? 0);
    $switchableDepts = DB::table('deparments')
        ->leftJoin('companies', 'companies.id', '=', 'deparments.company_id')
        ->select(
            'deparments.id',
            'deparments.shortName',
            'deparments.company_id',
            'companies.short_name as company_short_name',
            'companies.name as company_name',
        )
        ->where('deparments.isActive', 1)
        ->where('deparments.is_general', 1)
        ->when(
            $allowedDeptIds !== ['*'],
            fn($q) => $q->whereIn('deparments.id', $allowedDeptIds ?: [0]),
        )
        ->orderBy('companies.name', 'asc')
        ->orderBy('deparments.shortName', 'asc')
        ->get();

    // Gom theo công ty; chỉ có một công ty thì không hiện tiêu đề công ty.
    $deptsByCompany = $switchableDepts->groupBy('company_id');
    $companyLabels = [];
    foreach ($switchableDepts as $dept) {
        $companyLabels[$dept->company_id] =
            $dept->company_short_name ?: ($dept->company_name ?: 'Chưa gán công ty');
    }

    $isAdmin = user_has_any_role(session('user')['userId'] ?? 0, ['Admin']);

    // Nhóm và icon khớp mục lục trang Hướng Dẫn Sử Dụng - Dữ Liệu Gốc.
    // [đoạn route giữa pages.materData. và .list, tên, icon, quyền ('Admin' = chỉ role Admin)]
    $materDataGroups = [
        'Tổ chức' => [
            ['company', 'Công Ty', 'fas fa-city', 'materData_common_view'],
            ['department', 'Phòng Ban', 'fas fa-building', 'materData_common_view'],
            ['group', 'Tổ', 'fas fa-users', 'materData_common_view'],
        ],
        'Danh mục dùng chung' => [
            ['unit', 'Đơn Vị Tính', 'fas fa-balance-scale', 'materData_common_view'],
            ['packagingSpecification', 'Quy Cách Đóng Gói', 'fas fa-box-open', 'materData_common_view'],
            ['storageCondition', 'Điều Kiện Bảo Quản', 'fas fa-temperature-low', 'materData_common_view'],
            ['chemManufacturer', 'Nhà Sản Xuất', 'fas fa-industry', 'materData_common_view'],
            ['chemSupplier', 'Nhà Cung Cấp', 'fas fa-truck', 'materData_common_view'],
            ['status', 'Trạng Thái', 'fas fa-toggle-on', 'materData_common_view'],
            ['productName', 'Tên Sản Phẩm', 'fas fa-pills', 'materData_common_view'],
        ],
        'Hoá chất' => [
            ['activeIngredient', 'Hoạt Chất', 'fas fa-atom', 'materData_chemical_view'],
            ['mixtureHazardCategory', 'Nhóm Nguy Hại Bảng B', 'fas fa-exclamation-triangle', 'materData_chemical_view'],
            ['chemName', 'Hoá Chất', 'fas fa-flask', 'materData_chemical_view'],
        ],
        'Chất chuẩn' => [
            ['standardName', 'Tên Chuẩn', 'fas fa-flask', 'materData_standard_view'],
            ['purpose', 'Chỉ Tiêu Kiểm', 'fas fa-vial', 'materData_standard_view'],
        ],
        'Vật tư' => [
            ['materialName', 'Tên Vật Tư', 'fas fa-cubes', 'materData_material_view'],
            ['departmentClassification', 'Phân Loại', 'fas fa-tags', 'materData_material_view'],
            ['materialSignFlow', 'Trình Ký Đề Nghị CP Vật Tư', 'fas fa-file-signature', 'materData_material_view'],
            ['transferSignFlow', 'Trình Ký Đề Nghị Liên Phòng Ban', 'fas fa-route', 'materData_material_view'],
        ],
        'Kho & đối tượng' => [
            ['zone', 'Định Khu', 'fas fa-sitemap', 'materData_common_view'],
            ['consumptionObject', 'Đối Tượng', 'fas fa-cogs', 'materData_common_view'],
        ],
        'Quản trị' => [
            ['initialData', 'Khởi Tạo Dữ Liệu', 'fas fa-file-import', 'Admin'],
        ],
    ];
    $materDataItems = [];
    foreach ($materDataGroups as $groupName => $items) {
        foreach ($items as [$key, $label, $icon, $perm]) {
            if ($perm === 'Admin' ? !$isAdmin : !user_can($perm)) {
                continue;
            }
            $materDataItems[] = [
                'label' => $label,
                'url' => route('pages.materData.' . $key . '.list'),
                'icon' => $icon,
                'group' => $groupName,
                'active' => request()->is('materData/' . $key, 'materData/' . $key . '/*'),
            ];
        }
    }

    $kindIcons = ['material' => 'fas fa-cubes', 'chemical' => 'fas fa-flask', 'standard' => 'fas fa-vials'];
    $kindLabels = ['material' => 'Vật Tư', 'chemical' => 'Hoá Chất', 'standard' => 'Chất Chuẩn'];
    // Mục con theo loại hàng: [loại, quyền, url, đang mở]
    $kindItems = function (array $rows) use ($kindIcons, $kindLabels) {
        $out = [];
        foreach ($rows as [$kind, $perm, $url, $active]) {
            if (user_can($perm)) {
                $out[] = ['label' => $kindLabels[$kind], 'url' => $url, 'icon' => $kindIcons[$kind], 'kind' => $kind, 'active' => $active];
            }
        }
        return $out;
    };

    // Các mục trên cột icon. 'items' rỗng thì mục bị ẩn; mục không có 'items' là link thẳng.
    $navModules = [
        [
            'id' => 'home', 'label' => 'Trang Chủ', 'short' => 'Trang chủ', 'icon' => 'fas fa-home',
            'url' => route('pages.home'), 'active' => request()->is('home*') || request()->is('/'),
        ],
        [
            'id' => 'materData', 'label' => 'Dữ Liệu Gốc', 'short' => 'Dữ liệu gốc', 'icon' => 'fas fa-database',
            'items' => $materDataItems,
        ],
        [
            'id' => 'category', 'label' => 'Danh Mục', 'short' => 'Danh mục', 'icon' => 'fas fa-clipboard-list',
            'desc' => 'Danh mục công ty & của phòng',
            'items' => $kindItems([
                ['material', 'category_material_view', route('pages.category.materialCategory.list'), request()->is('category/materialCategory')],
                ['chemical', 'category_chemical_view', route('pages.category.chemicalCategory.list'), request()->is('category/chemicalCategory')],
                ['standard', 'category_standard_view', route('pages.category.standardCategory.list'), request()->is('category/standardCategory')],
            ]),
        ],
        [
            'id' => 'import', 'label' => 'Nhập', 'short' => 'Nhập', 'icon' => 'fas fa-dolly',
            'desc' => 'Sổ nhập kho, kiểm tra nhập',
            'items' => $kindItems([
                ['material', 'import_material_view', route('pages.import.materialImport.list'), request()->is('import/materialImport')],
                ['chemical', 'import_chemical_view', route('pages.import.chemicalImport.list'), request()->is('import/chemicalImport')],
                ['standard', 'import_standard_view', route('pages.import.standardImport.list'), request()->is('import/standardImport')],
            ]),
        ],
        [
            'id' => 'export', 'label' => 'Đề Nghị - Sử Dụng', 'short' => 'Sử dụng', 'icon' => 'fas fa-hand-holding-water',
            'desc' => 'Đề nghị, cấp phát, cách ly',
            'items' => $kindItems([
                ['material', 'export_material_view', route('pages.export.materialExport.list'), request()->is('export/materialExport')],
                ['chemical', 'export_chemical_view', route('pages.export.chemicalExport.list'), request()->is('export/chemicalExport')],
                ['standard', 'export_standard_view', route('pages.export.standardExport.list'), request()->is('export/standardExport')],
            ]),
        ],
        [
            'id' => 'inventory', 'label' => 'Tồn Kho', 'short' => 'Tồn kho', 'icon' => 'fas fa-boxes',
            'desc' => 'Tồn theo lô, vị trí',
            'items' => $kindItems([
                ['material', 'inventory_material_view', route('pages.inventory.materialInventory.list'), request()->is('inventory/materialInventory')],
                ['chemical', 'inventory_chemical_view', route('pages.inventory.chemicalInventory.list'), request()->is('inventory/chemicalInventory')],
                ['standard', 'inventory_standard_view', route('pages.inventory.standardInventory.list'), request()->is('inventory/standardInventory') || request()->is('inventory/standardStability*')],
            ]),
        ],
        [
            'id' => 'estimate', 'label' => 'Dự Trù', 'short' => 'Dự trù', 'icon' => 'fas fa-file-signature',
            'desc' => 'Phiếu dự trù, ký duyệt',
            'items' => $kindItems([
                ['material', 'estimate_material_view', route('pages.estimate.materialEstimate.list'), request()->is('estimate/materialEstimate*')],
                ['chemical', 'estimate_chemical_view', route('pages.estimate.chemicalEstimate.list'), request()->is('estimate/chemicalEstimate*')],
                ['standard', 'estimate_standard_view', route('pages.estimate.standardEstimate.list'), request()->is('estimate/standardEstimate*')],
            ]),
        ],
        [
            'id' => 'statistic', 'label' => 'Thống Kê', 'short' => 'Thống kê', 'icon' => 'fas fa-chart-bar',
            'desc' => 'Cấp phát theo đối tượng',
            'items' => $kindItems([
                ['material', 'export_material_view', route('pages.statistic.materialIssue.list'), request()->is('statistic/materialIssue')],
            ]),
        ],
        [
            'id' => 'user', 'label' => 'Phân Quyền', 'short' => 'Phân quyền', 'icon' => 'fas fa-user-shield', 'sep' => true,
            'items' => array_values(array_filter([
                user_can('user_view') ? ['label' => 'User', 'url' => route('pages.user.user.list'), 'icon' => 'fas fa-user', 'active' => request()->is('user/user')] : null,
                user_can('role_view') ? ['label' => 'Nhóm Quyền', 'url' => route('pages.user.role.list'), 'icon' => 'fas fa-users-cog', 'active' => request()->is('user/role')] : null,
                user_can('permission_view') ? ['label' => 'Quyền', 'url' => route('pages.user.permission.list'), 'icon' => 'fas fa-key', 'active' => request()->is('user/permission')] : null,
            ])),
        ],
        [
            'id' => 'loginHistory', 'label' => 'Lịch Sử Đăng Nhập', 'short' => 'Lịch sử', 'icon' => 'fas fa-history',
            'items' => array_values(array_filter([
                user_can('login_history_view') ? ['label' => 'Toàn Hệ Thống', 'url' => route('pages.loginHistory.list'), 'icon' => 'fas fa-globe', 'active' => request()->is('loginHistory')] : null,
                ['label' => 'Của Tôi', 'url' => route('pages.loginHistory.mine'), 'icon' => 'fas fa-user-clock', 'active' => request()->is('loginHistory/mine')],
                user_can('auditTrail_view') ? ['label' => 'Audit Trail', 'url' => route('pages.auditTrail.list'), 'icon' => 'fas fa-fingerprint', 'active' => request()->is('auditTrail')] : null,
            ])),
        ],
    ];
    if ($isAdmin) {
        $navModules[] = [
            'id' => 'database', 'label' => 'Cơ Sở Dữ Liệu', 'short' => 'CSDL', 'icon' => 'fas fa-server',
            'url' => route('pages.database.list'), 'active' => request()->is('database'),
        ];
    }
    // Hướng Dẫn Sử Dụng: mọi tài khoản đều xem được, đặt cuối cột
    $navModules[] = [
        'id' => 'userGuide', 'label' => 'Hướng Dẫn Sử Dụng', 'short' => 'Hướng dẫn', 'icon' => 'fas fa-book-open', 'bottom' => true,
        'items' => [
            ['label' => 'Tất Cả Hướng Dẫn', 'url' => route('pages.userGuide.list'), 'icon' => 'fas fa-th-large', 'active' => request()->is('userGuide')],
            ['label' => 'Phân Quyền', 'url' => route('pages.userGuide.show', 'permission'), 'icon' => 'fas fa-user-shield', 'active' => request()->is('userGuide/permission')],
            ['label' => 'Dữ Liệu Gốc', 'url' => route('pages.userGuide.show', 'materData'), 'icon' => 'fas fa-database', 'active' => request()->is('userGuide/materData')],
            ['label' => 'Vật Tư', 'url' => route('pages.userGuide.show', 'material'), 'icon' => 'fas fa-cubes', 'active' => request()->is('userGuide/material')],
            ['label' => 'Hoá Chất', 'url' => route('pages.userGuide.show', 'chemical'), 'icon' => 'fas fa-flask', 'active' => request()->is('userGuide/chemical')],
            ['label' => 'Chất Chuẩn', 'url' => route('pages.userGuide.show', 'standard'), 'icon' => 'fas fa-vials', 'active' => request()->is('userGuide/standard')],
        ],
    ];

    $navModules = array_values(array_filter($navModules, fn($m) => !array_key_exists('items', $m) || $m['items']));

    // Mục chứa màn hình hiện tại + danh sách cho ô tìm màn hình (Ctrl K)
    $navCurrent = '';
    $navSearch = [];
    foreach ($navModules as $i => $m) {
        if (!isset($m['items'])) {
            $navSearch[] = ['label' => $m['label'], 'path' => '', 'url' => $m['url'], 'icon' => $m['icon']];
            continue;
        }
        $navModules[$i]['active'] = collect($m['items'])->contains('active', true);
        if ($navModules[$i]['active']) {
            $navCurrent = $m['id'];
        }
        foreach ($m['items'] as $item) {
            $navSearch[] = [
                'label' => isset($item['kind']) ? $m['label'] . ' ' . $item['label'] : $item['label'],
                'path' => $m['label'],
                'url' => $item['url'],
                'icon' => $item['icon'],
            ];
        }
    }
@endphp

<nav class="wms-rail" id="wms-rail" aria-label="Menu chính" data-current="{{ $navCurrent }}">
    <a href="{{ route('pages.home') }}" class="wms-rail-logo" title="WMS - Quản Lý Kho">
        <img src="{{ asset('img/iconstella.svg') }}" alt="WMS">
    </a>

    <button type="button" class="wms-rbtn" data-wms-cmd title="Tìm màn hình (Ctrl K)">
        <i class="fas fa-search"></i>Tìm
    </button>
    <div class="wms-rail-sep"></div>

    @foreach ($navModules as $m)
        @if (!empty($m['bottom']))
            <div class="wms-rail-grow"></div>
        @elseif (!empty($m['sep']))
            <div class="wms-rail-sep"></div>
        @endif
        @if (isset($m['items']))
            <button type="button" class="wms-rbtn {{ $m['active'] ? 'is-current' : '' }}" data-wms-panel="{{ $m['id'] }}"
                title="{{ $m['label'] }}" aria-controls="wms-panel">
                <i class="{{ $m['icon'] }}"></i>{{ $m['short'] }}
            </button>
        @else
            <a href="{{ $m['url'] }}" class="wms-rbtn {{ $m['active'] ? 'is-current' : '' }}" title="{{ $m['label'] }}">
                <i class="{{ $m['icon'] }}"></i>{{ $m['short'] }}
            </a>
        @endif
    @endforeach

    {{-- Phòng ban đang làm việc: bấm để đổi (chỉ khi được làm việc ở nhiều phòng) --}}
    <button type="button" class="wms-rbtn wms-dept" @if ($switchableDepts->count() > 1) data-wms-panel="dept" @else disabled @endif
        title="{{ trim((session('user')['company_name'] ?? '') . ' - ' . ($currentDept ?? ''), ' -') }}">
        <i class="fas fa-building"></i>{{ $currentDept ?? 'Chưa phân bộ phận' }}
    </button>
</nav>

<aside class="wms-panel" id="wms-panel" hidden>
    <div class="wms-panel-head">
        <h6 class="wms-panel-title" id="wms-panel-title"></h6>
        <button type="button" class="wms-icon-btn" id="wms-panel-pin"><i class="fas fa-thumbtack"></i></button>
        <button type="button" class="wms-icon-btn" id="wms-panel-close" title="Đóng"><i class="fas fa-times"></i></button>
    </div>
    <div class="wms-panel-scroll">
        @foreach ($navModules as $m)
            @continue(!isset($m['items']))
            <div data-wms-body="{{ $m['id'] }}" data-title="{{ $m['label'] }}" hidden>
                @if (isset($m['items'][0]['kind']))
                    <div class="wms-tiles">
                        @foreach ($m['items'] as $item)
                            <a href="{{ $item['url'] }}" class="wms-tile k-{{ $item['kind'] }} {{ $item['active'] ? 'active' : '' }}">
                                <span class="wms-tile-ic"><i class="{{ $item['icon'] }}"></i></span>
                                <span><b>{{ $item['label'] }}</b><small>{{ $m['desc'] ?? '' }}</small></span>
                            </a>
                        @endforeach
                    </div>
                @else
                    @php $navGroup = null; @endphp
                    @foreach ($m['items'] as $item)
                        @if (($item['group'] ?? null) && $item['group'] !== $navGroup)
                            @php $navGroup = $item['group']; @endphp
                            <div class="wms-grp">{{ $navGroup }}</div>
                        @endif
                        <a href="{{ $item['url'] }}" class="wms-link {{ $item['active'] ? 'active' : '' }}">
                            <i class="{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endif
            </div>
        @endforeach

        @if ($switchableDepts->count() > 1)
            <div data-wms-body="dept" data-title="Đổi Phòng Ban" hidden>
                @foreach ($deptsByCompany as $companyId => $depts)
                    @if ($deptsByCompany->count() > 1)
                        <div class="wms-grp">{{ $companyLabels[$companyId] ?? 'Chưa gán công ty' }}</div>
                    @endif
                    @foreach ($depts as $dept)
                        <a href="{{ route('switch', ['selected_department' => $dept->shortName, 'selected_department_id' => $dept->id, 'redirect' => url()->current()]) }}"
                            class="wms-link {{ $currentDept == $dept->shortName ? 'active' : '' }}">
                            <i class="fas fa-building"></i><span>{{ $dept->shortName }}</span>
                        </a>
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>
</aside>

<div class="wms-nav-backdrop" id="wms-nav-backdrop"></div>

<div class="wms-cmd" id="wms-cmd" hidden>
    <div class="wms-cmd-box" role="dialog" aria-label="Tìm màn hình">
        <div class="wms-cmd-in">
            <i class="fas fa-search"></i>
            <input type="text" id="wms-cmd-input" placeholder="Gõ tên màn hình, ví dụ: tồn hoá chất" autocomplete="off">
            <kbd>Esc</kbd>
        </div>
        <div class="wms-cmd-list" id="wms-cmd-list"></div>
        <div class="wms-cmd-foot"><span><kbd>↑</kbd> <kbd>↓</kbd> chọn</span><span><kbd>Enter</kbd> mở</span></div>
    </div>
</div>

<script>
    (function() {
        var body = document.body;
        var rail = document.getElementById('wms-rail');
        var panel = document.getElementById('wms-panel');
        var titleEl = document.getElementById('wms-panel-title');
        var pinBtn = document.getElementById('wms-panel-pin');
        var closeBtn = document.getElementById('wms-panel-close');
        var backdrop = document.getElementById('wms-nav-backdrop');
        var current = rail.getAttribute('data-current');
        var PIN_KEY = 'wmsNavPinned';
        var mobileMq = window.matchMedia('(max-width: 991.98px)');
        var openId = null;
        var wasDocked = false;

        function readPinned() {
            try {
                return localStorage.getItem(PIN_KEY) !== '0';
            } catch (e) {
                return true;
            }
        }

        function savePinned(v) {
            try {
                localStorage.setItem(PIN_KEY, v ? '1' : '0');
            } catch (e) {}
        }

        var pinned = readPinned();

        // Nội dung đổi bề ngang -> căn lại cột các bảng DataTables đang hiện
        function adjustTables() {
            setTimeout(function() {
                if (window.jQuery && jQuery.fn.dataTable) {
                    jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                }
                window.dispatchEvent(new Event('resize'));
            }, 320);
        }

        function render() {
            var docked = !!openId && pinned && !mobileMq.matches;
            body.classList.toggle('wms-panel-docked', docked);
            panel.hidden = !openId;

            panel.querySelectorAll('[data-wms-body]').forEach(function(el) {
                var on = el.getAttribute('data-wms-body') === openId;
                el.hidden = !on;
                if (on) titleEl.textContent = el.getAttribute('data-title');
            });
            rail.querySelectorAll('[data-wms-panel]').forEach(function(el) {
                el.classList.toggle('is-open', el.getAttribute('data-wms-panel') === openId);
            });

            pinBtn.classList.toggle('is-on', pinned);
            pinBtn.title = pinned ? 'Bỏ ghim: bảng tự đóng sau khi chọn' : 'Ghim bảng để luôn mở';
            pinBtn.hidden = mobileMq.matches;
            closeBtn.hidden = docked;

            if (docked !== wasDocked) adjustTables();
            wasDocked = docked;
        }

        function open(id) {
            openId = id;
            render();
        }

        function close() {
            openId = null;
            render();
        }

        function togglePin() {
            pinned = !pinned;
            savePinned(pinned);
            if (!pinned) openId = null;
            else if (!openId) openId = current || null;
            render();
        }

        rail.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-wms-panel]');
            if (!btn) return;
            var id = btn.getAttribute('data-wms-panel');
            if (openId === id && !body.classList.contains('wms-panel-docked')) close();
            else open(id);
        });

        pinBtn.addEventListener('click', togglePin);
        closeBtn.addEventListener('click', close);

        // Bảng nổi: bấm ra ngoài thì đóng
        document.addEventListener('click', function(e) {
            if (!openId || body.classList.contains('wms-panel-docked')) return;
            if (panel.contains(e.target) || rail.contains(e.target) || e.target.closest('#wms-nav-toggle')) return;
            close();
            body.classList.remove('wms-nav-mobile-open');
        });

        backdrop.addEventListener('click', function() {
            body.classList.remove('wms-nav-mobile-open');
            close();
        });

        // Nút ☰ trên topNAV: màn hình lớn = ghim / bỏ ghim bảng con, màn hình nhỏ = mở / đóng menu
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#wms-nav-toggle')) return;
            e.preventDefault();
            if (mobileMq.matches) {
                body.classList.toggle('wms-nav-mobile-open');
                if (!body.classList.contains('wms-nav-mobile-open')) close();
            } else {
                togglePin();
            }
        });

        (mobileMq.addEventListener ? mobileMq.addEventListener.bind(mobileMq, 'change') : mobileMq.addListener.bind(mobileMq))(function() {
            body.classList.remove('wms-nav-mobile-open');
            render();
        });

        if (pinned && current && !mobileMq.matches) openId = current;
        wasDocked = !!openId && pinned && !mobileMq.matches;
        render();

        /* ---------- Tìm màn hình (Ctrl K) ---------- */
        var items = @json($navSearch);
        var cmd = document.getElementById('wms-cmd');
        var input = document.getElementById('wms-cmd-input');
        var list = document.getElementById('wms-cmd-list');
        var rows = [];
        var sel = 0;

        function norm(s) {
            return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'd').toLowerCase();
        }

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        items.forEach(function(it) {
            it.key = norm(it.label + ' ' + it.path);
        });

        function draw() {
            var terms = norm(input.value).split(/\s+/).filter(Boolean);
            rows = items.filter(function(it) {
                return terms.every(function(t) {
                    return it.key.indexOf(t) !== -1;
                });
            });
            if (sel >= rows.length) sel = 0;
            list.innerHTML = rows.length ? rows.map(function(it, i) {
                return '<a class="wms-cmd-row' + (i === sel ? ' is-on' : '') + '" href="' + esc(it.url) + '"><i class="' + esc(it.icon) + '"></i><span>' +
                    esc(it.label) + '</span><small>' + esc(it.path) + '</small></a>';
            }).join('') : '<div class="wms-cmd-empty">Không có màn hình nào khớp.</div>';
            var on = list.querySelector('.is-on');
            if (on) on.scrollIntoView({ block: 'nearest' });
        }

        function openCmd() {
            cmd.hidden = false;
            input.value = '';
            sel = 0;
            draw();
            input.focus();
        }

        function closeCmd() {
            cmd.hidden = true;
        }

        rail.querySelector('[data-wms-cmd]').addEventListener('click', openCmd);
        cmd.addEventListener('click', function(e) {
            if (e.target === cmd) closeCmd();
        });
        input.addEventListener('input', function() {
            sel = 0;
            draw();
        });

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                cmd.hidden ? openCmd() : closeCmd();
                return;
            }
            if (e.key === 'Escape') {
                if (!cmd.hidden) closeCmd();
                else if (openId && !body.classList.contains('wms-panel-docked')) close();
                return;
            }
            if (cmd.hidden) return;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!rows.length) return;
                sel = (sel + (e.key === 'ArrowDown' ? 1 : rows.length - 1)) % rows.length;
                draw();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (rows[sel]) window.location.href = rows[sel].url;
            }
        });
    })();
</script>
