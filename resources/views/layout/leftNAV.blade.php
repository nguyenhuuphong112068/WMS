<style>
    /* ==========================================================
       LEFT SIDEBAR - WMS
       LƯU Ý QUAN TRỌNG:
       Với <body class="layout-navbar-fixed">, AdminLTE đặt
       .brand-link thành position:fixed (thoát khỏi luồng) và chỉ
       đẩy .sidebar xuống đúng 3.5rem + 1px (57px).
       Brand của dự án cao hơn mức đó (logo + 2 dòng chữ) nên phải
       khai báo chiều cao rõ ràng và đẩy .sidebar xuống đúng bằng
       chiều cao đó, nếu không logo sẽ đè lên menu.
       ========================================================== */
    :root {
        --sidebar-brand-h: 118px;
        /* chiều cao brand khi menu thu gọn = chiều cao topNAV */
        --sidebar-brand-h-sm: calc(3.5rem + 1px);
    }

    .main-sidebar {
        background-color: #fff !important;
        box-shadow: 4px 0 14px rgba(var(--primary-rgb), 0.06) !important;
        border-right: 1px solid var(--primary-soft);
    }

    /* ---------- Brand / Logo ---------- */
    .brand-link {
        height: var(--sidebar-brand-h);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 12px 8px !important;
        background: #fff !important;
        border-bottom: 1px solid var(--primary-soft) !important;
        transition: height var(--transition-normal), width .3s ease-in-out;
    }

    .brand-link .brand-logo {
        width: 44px;
        height: auto;
        transition: width var(--transition-normal);
    }

    .brand-link .brand-text {
        margin: 0;
        color: var(--primary-dark);
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: 0.5px;
        text-align: center;
        white-space: nowrap;
    }

    /* ---------- Vùng cuộn của menu ---------- */
    .main-sidebar .sidebar {
        margin-top: var(--sidebar-brand-h) !important;
        height: calc(100vh - var(--sidebar-brand-h)) !important;
        padding: 14px 0 24px;
        overflow-x: hidden;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--primary-lighter) transparent;
    }

    .main-sidebar .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .main-sidebar .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .main-sidebar .sidebar::-webkit-scrollbar-thumb {
        background: var(--primary-lighter);
        border-radius: 3px;
    }

    /* ---------- Tiêu đề nhóm menu ---------- */
    .nav-sidebar .nav-header {
        padding: 16px 24px 6px !important;
        color: #475569 !important;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
    }

    /* ---------- Menu cấp 1 ---------- */
    .nav-sidebar>.nav-item>.nav-link {
        position: relative;
        color: var(--text-main) !important;
        margin: 3px 12px;
        padding: 10px 14px;
        border-radius: var(--border-radius-md);
        display: flex;
        align-items: center;
        font-weight: 600;
        transition: all var(--transition-fast);
    }

    /* chỉ icon đứng đầu, không đụng tới mũi tên .right */
    .nav-sidebar .nav-link>i {
        font-size: 1.05rem;
        width: 24px;
        margin-right: 10px;
        text-align: center;
        flex-shrink: 0;
    }

    /* icon menu cấp 1 theo màu chủ đạo để dễ phân biệt (không đụng bullet menu con) */
    .nav-sidebar>.nav-item>.nav-link>i {
        color: var(--primary);
    }

    .nav-sidebar>.nav-item.menu-open>.nav-link>i,
    .nav-sidebar>.nav-item>.nav-link:hover>i {
        color: var(--primary-dark);
    }

    .nav-sidebar .nav-link>p {
        flex: 1;
        min-width: 0;
    }

    /* mũi tên xổ menu - căn giữa theo chiều cao mục menu */
    .nav-sidebar .nav-link>p>.right {
        top: 13px;
        right: 14px;
        font-size: 0.8rem;
        opacity: 0.85;
    }

    .nav-sidebar>.nav-item>.nav-link:hover {
        background-color: var(--primary-soft) !important;
        color: var(--primary-dark) !important;
        transform: translateX(4px);
    }

    /* mục cha đang mở: nền nhạt, không dùng gradient để menu con dễ đọc */
    .nav-sidebar>.nav-item.menu-open>.nav-link {
        background-color: var(--primary-soft) !important;
        color: var(--primary-dark) !important;
    }

    /* mục đang ở màn hình hiện tại */
    .nav-sidebar .nav-link.active {
        background: linear-gradient(135deg, var(--primary-light), var(--primary)) !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.28);
    }

    .nav-sidebar .nav-link.active i {
        color: #fff !important;
    }

    /* ---------- Menu cấp 2 (nav-treeview) - cùng kiểu mục lục trang Hướng Dẫn Sử Dụng ---------- */
    .nav-sidebar .nav-treeview {
        margin: 4px 12px 8px;
        padding: 6px 0;
    }

    /* tiêu đề nhóm nhỏ bên trong menu con */
    .nav-sidebar .nav-treeview>.nav-header {
        padding: 10px 10px 4px !important;
        color: var(--text-muted) !important;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        white-space: normal;
    }

    .nav-sidebar .nav-treeview>.nav-header:first-child {
        padding-top: 2px !important;
    }

    .nav-sidebar .nav-treeview>.nav-item>.nav-link {
        color: var(--text-main) !important;
        padding: 6px 10px;
        margin: 1px 0;
        border-radius: var(--border-radius-md);
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        transition: background var(--transition-fast), color var(--transition-fast);
    }

    .nav-sidebar .nav-treeview>.nav-item>.nav-link>.nav-icon {
        font-size: 0.88rem;
        width: 18px;
        margin-right: 9px;
        color: var(--primary-lighter);
        transition: color var(--transition-fast);
    }

    .nav-sidebar .nav-treeview>.nav-item>.nav-link:hover {
        background-color: var(--primary-soft) !important;
        color: var(--primary-dark) !important;
    }

    /* mục đang mở: nền nhạt + chữ màu chủ đạo thay cho gradient của menu cấp 1 */
    .nav-sidebar .nav-treeview .nav-link.active {
        background: var(--primary-soft) !important;
        color: var(--primary) !important;
        font-weight: 600;
        box-shadow: none;
    }

    .nav-sidebar .nav-treeview>.nav-item>.nav-link:hover>.nav-icon,
    .nav-sidebar .nav-treeview .nav-link.active>.nav-icon {
        color: var(--primary) !important;
    }

    /* ---------- Menu cấp 3 (chọn công ty rồi tới phòng ban) ---------- */
    .nav-sidebar .nav-treeview .nav-treeview {
        margin: 2px 0 4px 8px;
        padding: 0 0 0 10px;
        border-left: 2px solid var(--primary-soft);
    }

    .nav-sidebar .nav-treeview>.nav-item.has-treeview>.nav-link {
        font-weight: 600;
        color: var(--primary-dark) !important;
    }

    .nav-sidebar .nav-treeview>.nav-item.has-treeview>.nav-link>.nav-icon {
        font-size: 0.85rem;
    }

    .nav-sidebar .nav-treeview .nav-link>p>.right {
        top: 10px;
        right: 10px;
        font-size: 0.72rem;
    }

    /* ---------- Trạng thái thu gọn (sidebar-mini) ---------- */
    .sidebar-collapse .main-sidebar:not(:hover) .sidebar {
        margin-top: var(--sidebar-brand-h-sm) !important;
        height: calc(100vh - var(--sidebar-brand-h-sm)) !important;
    }

    .sidebar-collapse .main-sidebar:not(:hover) .brand-link {
        padding: 6px 0 !important;
    }

    .sidebar-collapse .main-sidebar:not(:hover) .brand-logo {
        width: 34px;
    }

    .sidebar-collapse .main-sidebar:not(:hover) .brand-text {
        display: none;
    }

    /* khi rê chuột vào menu thu gọn, AdminLTE mở rộng lại -> trả brand về chiều cao đủ */
    .sidebar-collapse .main-sidebar:hover .brand-link {
        height: var(--sidebar-brand-h) !important;
    }
</style>

<aside class="main-sidebar elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('pages.home') }}" class="brand-link">
        <img src="{{ asset('img/iconstella.svg') }}" alt="Logo" class="brand-logo">
        <span class="brand-text library-title">WMS<br>Quản Lý Kho</span>
    </a>

    <!-- Sidebar Menu -->
    <div class="sidebar">

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">

                {{-- <li class="nav-header">TỔNG QUAN</li>
                <li class="nav-item">
                    <a href="{{ route('pages.home') }}"
                        class="nav-link {{ request()->is('home*') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i>
                        <p>Dashboard</p>
                    </a>
                </li> --}}

                <!-- Droplist Menu Chuyển Bộ Phận  -->
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

                    // Gom theo công ty: chọn công ty trước rồi mới chọn phòng ban.
                    // Chỉ có một công ty thì giữ danh sách phẳng cho đỡ một lần bấm.
                    $deptsByCompany = $switchableDepts->groupBy('company_id');
                    $companyLabels = [];
                    foreach ($switchableDepts as $dept) {
                        $companyLabels[$dept->company_id] =
                            $dept->company_short_name ?: ($dept->company_name ?: 'Chưa gán công ty');
                    }
                @endphp
                @if ($switchableDepts->count() > 1)
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link"
                            title="{{ trim((session('user')['company_name'] ?? '') . ' - ' . ($currentDept ?? ''), ' -') }}">
                            <i class="fas fa-building"></i>
                            <p>
                                {{ $currentDept ?? 'Chưa chọn bộ phận' }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if ($deptsByCompany->count() > 1)
                                @foreach ($deptsByCompany as $companyId => $depts)
                                    @php $isCurrentCompany = (string) $companyId === (string) $currentCompanyId; @endphp
                                    <li class="nav-item has-treeview {{ $isCurrentCompany ? 'menu-open' : '' }}">
                                        <a href="#" class="nav-link">
                                            <i class="fas fa-city nav-icon"></i>
                                            <p>
                                                {{ $companyLabels[$companyId] ?? 'Chưa gán công ty' }}
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @foreach ($depts as $dept)
                                                <li class="nav-item">
                                                    <a href="{{ route('switch', ['selected_department' => $dept->shortName, 'selected_department_id' => $dept->id, 'redirect' => url()->current()]) }}"
                                                        class="nav-link {{ $currentDept == $dept->shortName ? 'active' : '' }}">
                                                        <i class="fas fa-building nav-icon"></i>
                                                        <p>{{ $dept->shortName }}</p>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            @else
                                @foreach ($switchableDepts as $dept)
                                    <li class="nav-item">
                                        <a href="{{ route('switch', ['selected_department' => $dept->shortName, 'selected_department_id' => $dept->id, 'redirect' => url()->current()]) }}"
                                            class="nav-link {{ $currentDept == $dept->shortName ? 'active' : '' }}">
                                            <i class="fas fa-building nav-icon"></i>
                                            <p>{{ $dept->shortName }}</p>
                                        </a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <span class="nav-link" style="color: #c2c7d0; cursor: default;">
                            <i class="fas fa-building nav-icon"></i>
                            <p>{{ $currentDept ?? 'Chưa phân bộ phận' }}</p>
                        </span>
                    </li>
                @endif

                <!-- Droplist Menu Dữ Liệu Gốc  -->
                @permAny([
                    'materData_material_view',
                    'materData_chemical_view',
                    'materData_standard_view',
                    'materData_common_view'
                ])
                    <li class="nav-item has-treeview {{ str_contains(url()->current(), 'materData') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'materData') ? 'active' : '' }}">
                            <i class="fas fa-database"></i>
                            <p>
                                Dữ Liệu Gốc
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @php
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
                            $isAdmin = user_has_any_role(session('user')['userId'] ?? 0, ['Admin']);
                            foreach ($materDataGroups as $groupName => $items) {
                                $materDataGroups[$groupName] = array_filter(
                                    $items,
                                    fn($item) => $item[3] === 'Admin' ? $isAdmin : user_can($item[3]),
                                );
                            }
                            $materDataGroups = array_filter($materDataGroups);
                        @endphp
                        <ul class="nav nav-treeview">
                            @foreach ($materDataGroups as $groupName => $items)
                                <li class="nav-header">{{ $groupName }}</li>
                                @foreach ($items as [$key, $label, $icon])
                                    <li class="nav-item"><a href="{{ route('pages.materData.' . $key . '.list') }}"
                                            class="nav-link {{ request()->is('materData/' . $key, 'materData/' . $key . '/*') ? 'active' : '' }}"><i
                                                class="{{ $icon }} nav-icon"></i>
                                            <p>{{ $label }}</p>
                                        </a></li>
                                @endforeach
                            @endforeach
                        </ul>
                    </li>
                @endpermAny

                <!-- Droplist Menu Danh Mục  -->
                @permAny(['category_material_view', 'category_chemical_view', 'category_standard_view'])
                    <li class="nav-item has-treeview {{ str_contains(url()->current(), 'category') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'category') ? 'active' : '' }}">
                            <i class="fas fa-clipboard-list"></i>
                            <p>
                                Danh Mục
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            {{-- Một trang 2 tab: Danh Mục Vật Tư Công Ty + Vật Tư Của Phòng --}}
                            @perm('category_material_view')
                                <li class="nav-item"><a href="{{ route('pages.category.materialCategory.list') }}"
                                        class="nav-link {{ request()->is('category/materialCategory') ? 'active' : '' }}"><i
                                            class="fas fa-cubes nav-icon"></i>
                                        <p>Vật Tư</p>
                                    </a></li>
                            @endperm
                            {{-- Một trang 2 tab: Danh Mục Hoá Chất Công Ty + Hoá Chất Của Phòng --}}
                            @perm('category_chemical_view')
                                <li class="nav-item"><a href="{{ route('pages.category.chemicalCategory.list') }}"
                                        class="nav-link {{ request()->is('category/chemicalCategory') ? 'active' : '' }}"><i
                                            class="fas fa-flask nav-icon"></i>
                                        <p>Hoá Chất</p>
                                    </a></li>
                            @endperm
                            {{-- Một trang 2 tab: Danh Mục Chất Chuẩn Công Ty + Chất Chuẩn Của Phòng --}}
                            @perm('category_standard_view')
                                <li class="nav-item"><a href="{{ route('pages.category.standardCategory.list') }}"
                                        class="nav-link {{ request()->is('category/standardCategory') ? 'active' : '' }}"><i
                                            class="fas fa-vials nav-icon"></i>
                                        <p>Chất Chuẩn</p>
                                    </a></li>
                            @endperm
                        </ul>
                    </li>
                @endpermAny


                <!-- Droplist Menu Nhập  -->
                @permAny(['import_material_view', 'import_chemical_view', 'import_standard_view'])
                    <li class="nav-item has-treeview {{ request()->is('import/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('import/*') ? 'active' : '' }}">
                            <i class="fas fa-dolly"></i>
                            <p>
                                Nhập
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @perm('import_material_view')
                                <li class="nav-item"><a href="{{ route('pages.import.materialImport.list') }}"
                                        class="nav-link {{ request()->is('import/materialImport') ? 'active' : '' }}"><i
                                            class="fas fa-cubes nav-icon"></i>
                                        <p>Vật Tư</p>
                                    </a></li>
                            @endperm
                            @perm('import_chemical_view')
                                <li class="nav-item"><a href="{{ route('pages.import.chemicalImport.list') }}"
                                        class="nav-link {{ request()->is('import/chemicalImport') ? 'active' : '' }}"><i
                                            class="fas fa-flask nav-icon"></i>
                                        <p>Hoá Chất</p>
                                    </a></li>
                            @endperm
                            @perm('import_standard_view')
                                <li class="nav-item"><a href="{{ route('pages.import.standardImport.list') }}"
                                        class="nav-link {{ request()->is('import/standardImport') ? 'active' : '' }}"><i
                                            class="fas fa-vials nav-icon"></i>
                                        <p>Chất Chuẩn</p>
                                    </a></li>
                            @endperm
                        </ul>
                    </li>
                @endpermAny

                <!-- Droplist Menu Sử Dụng  -->
                @permAny(['export_material_view', 'export_chemical_view', 'export_standard_view'])
                    <li class="nav-item has-treeview {{ request()->is('export/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('export/*') ? 'active' : '' }}">
                            <i class="fas fa-hand-holding-water"></i>
                            <p>
                                Đề Nghị - Sử Dụng
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @perm('export_material_view')
                                <li class="nav-item"><a href="{{ route('pages.export.materialExport.list') }}"
                                        class="nav-link {{ request()->is('export/materialExport') ? 'active' : '' }}"><i
                                            class="fas fa-cubes nav-icon"></i>
                                        <p>Vật Tư</p>
                                    </a></li>
                            @endperm
                            @perm('export_chemical_view')
                                <li class="nav-item"><a href="{{ route('pages.export.chemicalExport.list') }}"
                                        class="nav-link {{ request()->is('export/chemicalExport') ? 'active' : '' }}"><i
                                            class="fas fa-flask nav-icon"></i>
                                        <p>Hoá Chất</p>
                                    </a></li>
                            @endperm
                            @perm('export_standard_view')
                                <li class="nav-item"><a href="{{ route('pages.export.standardExport.list') }}"
                                        class="nav-link {{ request()->is('export/standardExport') ? 'active' : '' }}"><i
                                            class="fas fa-vials nav-icon"></i>
                                        <p>Chất Chuẩn</p>
                                    </a></li>
                            @endperm

                        </ul>
                    </li>
                @endpermAny


                <!-- Droplist Menu Tồn  -->
                @permAny(['inventory_material_view', 'inventory_chemical_view', 'inventory_standard_view'])
                    <li class="nav-item has-treeview {{ request()->is('inventory/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('inventory/*') ? 'active' : '' }}">
                            <i class="fas fa-boxes"></i>
                            <p>
                                Tồn Kho
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @perm('inventory_material_view')
                                <li class="nav-item"><a href="{{ route('pages.inventory.materialInventory.list') }}"
                                        class="nav-link {{ request()->is('inventory/materialInventory') ? 'active' : '' }}"><i
                                            class="fas fa-cubes nav-icon"></i>
                                        <p>Vật Tư</p>
                                    </a></li>
                            @endperm
                            @perm('inventory_chemical_view')
                                <li class="nav-item"><a href="{{ route('pages.inventory.chemicalInventory.list') }}"
                                        class="nav-link {{ request()->is('inventory/chemicalInventory') ? 'active' : '' }}"><i
                                            class="fas fa-flask nav-icon"></i>
                                        <p>Hoá Chất</p>
                                    </a></li>
                            @endperm
                            @perm('inventory_standard_view')
                                <li class="nav-item"><a href="{{ route('pages.inventory.standardInventory.list') }}"
                                        class="nav-link {{ request()->is('inventory/standardInventory') ? 'active' : '' }}"><i
                                            class="fas fa-vials nav-icon"></i>
                                        <p>Chất Chuẩn</p>
                                    </a></li>
                            @endperm
                            @perm('inventory_chemical_view')
                                <li class="nav-item"><a href="{{ route('pages.inventory.thresholdReconciliation.list') }}"
                                        class="nav-link {{ request()->is('inventory/thresholdReconciliation') ? 'active' : '' }}"><i
                                            class="fas fa-tachometer-alt nav-icon"></i>
                                        <p>Ngưỡng Tồn Trữ PL IV</p>
                                    </a></li>
                            @endperm
                        </ul>
                    </li>
                @endpermAny


                <!-- Droplist Menu Dự Trù  -->
                @permAny(['estimate_material_view', 'estimate_chemical_view', 'estimate_standard_view'])
                    <li class="nav-item has-treeview {{ request()->is('estimate/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('estimate/*') ? 'active' : '' }}">
                            <i class="fas fa-file-signature"></i>
                            <p>
                                Dự Trù
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @perm('estimate_material_view')
                                <li class="nav-item"><a href="{{ route('pages.estimate.materialEstimate.list') }}"
                                        class="nav-link {{ request()->is('estimate/materialEstimate*') ? 'active' : '' }}"><i
                                            class="fas fa-cubes nav-icon"></i>
                                        <p>Vật Tư</p>
                                    </a></li>
                            @endperm
                            @perm('estimate_chemical_view')
                                <li class="nav-item"><a href="{{ route('pages.estimate.chemicalEstimate.list') }}"
                                        class="nav-link {{ request()->is('estimate/chemicalEstimate*') ? 'active' : '' }}"><i
                                            class="fas fa-flask nav-icon"></i>
                                        <p>Hoá Chất</p>
                                    </a></li>
                            @endperm
                            @perm('estimate_standard_view')
                                <li class="nav-item"><a href="{{ route('pages.estimate.standardEstimate.list') }}"
                                        class="nav-link {{ request()->is('estimate/standardEstimate*') ? 'active' : '' }}"><i
                                            class="fas fa-vials nav-icon"></i>
                                        <p>Chất Chuẩn</p>
                                    </a></li>
                            @endperm

                        </ul>
                    </li>
                @endpermAny

                <!-- Droplist Menu Thống Kê  -->
                @permAny(['export_material_view'])
                    <li class="nav-item has-treeview {{ request()->is('statistic/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('statistic/*') ? 'active' : '' }}">
                            <i class="fas fa-chart-bar"></i>
                            <p>
                                Thống Kê
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @perm('export_material_view')
                                <li class="nav-item"><a href="{{ route('pages.statistic.materialIssue.list') }}"
                                        class="nav-link {{ request()->is('statistic/materialIssue') ? 'active' : '' }}"><i
                                            class="fas fa-cubes nav-icon"></i>
                                        <p>Vật Tư</p>
                                    </a></li>
                            @endperm
                        </ul>
                    </li>
                @endpermAny

                <!-- Droplist Menu Đánh Giá Hạn Dùng  -->
                @permAny(['stability_standard_view', 'stability_plan_view'])
                    <li class="nav-item has-treeview {{ request()->is('stabilityAssessment/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('stabilityAssessment/*') ? 'active' : '' }}">
                            <i class="fas fa-clipboard-list"></i>
                            <p>
                                Đánh Giá Hạn Dùng
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @perm('stability_standard_view')
                                <li class="nav-item"><a
                                        href="{{ route('pages.stabilityAssessment.standardStability.list') }}"
                                        class="nav-link {{ request()->is('stabilityAssessment/standardStability*') ? 'active' : '' }}"><i
                                            class="fas fa-vial nav-icon"></i>
                                        <p>Chuẩn Thứ Cấp</p>
                                    </a></li>
                            @endperm
                            @perm('stability_plan_view')
                                <li class="nav-item"><a href="{{ route('pages.stabilityAssessment.assessmentPlan.list') }}"
                                        class="nav-link {{ request()->is('stabilityAssessment/assessmentPlan*') ? 'active' : '' }}"><i
                                            class="fas fa-calendar-check nav-icon"></i>
                                        <p>Kế Hoạch Đánh Giá</p>
                                    </a></li>
                            @endperm
                        </ul>
                    </li>
                @endpermAny

                <!-- User Policy -->
                <li class="nav-header">QUẢN TRỊ</li>
                @permAny(['user_view', 'role_view', 'permission_view'])
                    <li class="nav-item has-treeview {{ request()->is('user/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('user/*') ? 'active' : '' }}">
                            <i class="fas fa-user-shield"></i>
                            <p>Phân Quyền <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @perm('user_view')
                                <li class="nav-item"><a href="{{ route('pages.user.user.list') }}"
                                        class="nav-link {{ request()->is('user/user') ? 'active' : '' }}"><i
                                            class="fas fa-user nav-icon"></i>
                                        <p>User</p>
                                    </a></li>
                            @endperm
                            @perm('role_view')
                                <li class="nav-item"><a href="{{ route('pages.user.role.list') }}"
                                        class="nav-link {{ request()->is('user/role') ? 'active' : '' }}"><i
                                            class="fas fa-users-cog nav-icon"></i>
                                        <p>Nhóm Quyền</p>
                                    </a></li>
                            @endperm
                            @perm('permission_view')
                                <li class="nav-item"><a href="{{ route('pages.user.permission.list') }}"
                                        class="nav-link {{ request()->is('user/permission') ? 'active' : '' }}"><i
                                            class="fas fa-key nav-icon"></i>
                                        <p>Quyền</p>
                                    </a></li>
                            @endperm
                        </ul>
                    </li>
                @endpermAny

                @php $lhOpen = request()->is('loginHistory', 'loginHistory/*', 'auditTrail'); @endphp
                <li class="nav-item has-treeview {{ $lhOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $lhOpen ? 'active' : '' }}">
                        <i class="fas fa-history"></i>
                        <p>Lịch Sử Đăng Nhập <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @perm('login_history_view')
                            <li class="nav-item"><a href="{{ route('pages.loginHistory.list') }}"
                                    class="nav-link {{ request()->is('loginHistory') ? 'active' : '' }}"><i
                                        class="fas fa-globe nav-icon"></i>
                                    <p>Toàn Hệ Thống</p>
                                </a></li>
                        @endperm
                        <li class="nav-item"><a href="{{ route('pages.loginHistory.mine') }}"
                                class="nav-link {{ request()->is('loginHistory/mine') ? 'active' : '' }}"><i
                                    class="fas fa-user-clock nav-icon"></i>
                                <p>Của Tôi</p>
                            </a></li>
                        @perm('auditTrail_view')
                            <li class="nav-item"><a href="{{ route('pages.auditTrail.list') }}"
                                    class="nav-link {{ request()->is('auditTrail') ? 'active' : '' }}"><i
                                        class="fas fa-fingerprint nav-icon"></i>
                                    <p>Audit Trail</p>
                                </a></li>
                        @endperm
                    </ul>
                </li>

                @if (user_has_any_role(session('user')['userId'] ?? 0, ['Admin']))
                    <li class="nav-item">
                        <a href="{{ route('pages.database.list') }}"
                            class="nav-link {{ request()->is('database') ? 'active' : '' }}">
                            <i class="fas fa-database"></i>
                            <p>Cơ Sở Dữ Liệu</p>
                        </a>
                    </li>
                @endif

                <!-- Hướng Dẫn Sử Dụng: mọi tài khoản đều xem được -->
                <li class="nav-header">TRỢ GIÚP</li>
                <li class="nav-item has-treeview {{ request()->is('userGuide', 'userGuide/*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->is('userGuide', 'userGuide/*') ? 'active' : '' }}">
                        <i class="fas fa-book-open"></i>
                        <p>Hướng Dẫn Sử Dụng <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="{{ route('pages.userGuide.list') }}"
                                class="nav-link {{ request()->is('userGuide') ? 'active' : '' }}"><i
                                    class="fas fa-th-large nav-icon"></i>
                                <p>Tất Cả Hướng Dẫn</p>
                            </a></li>
                        <li class="nav-item"><a href="{{ route('pages.userGuide.show', 'permission') }}"
                                class="nav-link {{ request()->is('userGuide/permission') ? 'active' : '' }}"><i
                                    class="fas fa-user-shield nav-icon"></i>
                                <p>Phân Quyền</p>
                            </a></li>
                        <li class="nav-item"><a href="{{ route('pages.userGuide.show', 'materData') }}"
                                class="nav-link {{ request()->is('userGuide/materData') ? 'active' : '' }}"><i
                                    class="fas fa-database nav-icon"></i>
                                <p>Dữ Liệu Gốc</p>
                            </a></li>
                        <li class="nav-item"><a href="{{ route('pages.userGuide.show', 'material') }}"
                                class="nav-link {{ request()->is('userGuide/material') ? 'active' : '' }}"><i
                                    class="fas fa-cubes nav-icon"></i>
                                <p>Vật Tư</p>
                            </a></li>
                        <li class="nav-item"><a href="{{ route('pages.userGuide.show', 'chemical') }}"
                                class="nav-link {{ request()->is('userGuide/chemical') ? 'active' : '' }}"><i
                                    class="far fa-circle nav-icon text-danger"></i>
                                <p>Hoá Chất</p>
                            </a></li>
                        <li class="nav-item"><a href="{{ route('pages.userGuide.show', 'standard') }}"
                                class="nav-link {{ request()->is('userGuide/standard') ? 'active' : '' }}"><i
                                    class="far fa-circle nav-icon text-warning"></i>
                                <p>Chất Chuẩn</p>
                            </a></li>
                    </ul>
                </li>

            </ul>
        </nav>
    </div>
</aside>
