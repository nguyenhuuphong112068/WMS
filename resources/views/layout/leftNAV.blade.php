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
        color: #94a3b8 !important;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
    }

    /* ---------- Menu cấp 1 ---------- */
    .nav-sidebar > .nav-item > .nav-link {
        position: relative;
        color: #475569 !important;
        margin: 3px 12px;
        padding: 10px 14px;
        border-radius: var(--border-radius-md);
        display: flex;
        align-items: center;
        font-weight: 500;
        transition: all var(--transition-fast);
    }

    /* chỉ icon đứng đầu, không đụng tới mũi tên .right */
    .nav-sidebar .nav-link > i {
        font-size: 1.05rem;
        width: 24px;
        margin-right: 10px;
        text-align: center;
        flex-shrink: 0;
    }

    .nav-sidebar .nav-link > p {
        flex: 1;
        min-width: 0;
    }

    /* mũi tên xổ menu - căn giữa theo chiều cao mục menu */
    .nav-sidebar .nav-link > p > .right {
        top: 13px;
        right: 14px;
        font-size: 0.8rem;
        opacity: 0.6;
    }

    .nav-sidebar > .nav-item > .nav-link:hover {
        background-color: var(--primary-soft) !important;
        color: var(--primary-dark) !important;
        transform: translateX(4px);
    }

    /* mục cha đang mở: nền nhạt, không dùng gradient để menu con dễ đọc */
    .nav-sidebar > .nav-item.menu-open > .nav-link {
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

    /* ---------- Menu cấp 2 (nav-treeview) ---------- */
    .nav-sidebar .nav-treeview {
        margin: 2px 12px 6px;
        padding-left: 14px;
        border-left: 2px solid var(--primary-soft);
    }

    .nav-sidebar .nav-treeview > .nav-item > .nav-link {
        color: #64748b !important;
        padding: 7px 12px;
        margin: 2px 0;
        border-radius: var(--border-radius-md);
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        transition: all var(--transition-fast);
    }

    .nav-sidebar .nav-treeview > .nav-item > .nav-link > .nav-icon {
        font-size: 0.6rem;
        width: 18px;
        margin-right: 6px;
    }

    .nav-sidebar .nav-treeview > .nav-item > .nav-link:hover {
        background-color: var(--primary-soft) !important;
        color: var(--primary-dark) !important;
        transform: translateX(3px);
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
                @if (user_has_any_role(session('user')['userId'], ['Admin']))
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="fas fa-building"></i>
                            <p>
                                {{ session('user')['selected_department'] }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @php
                                $departments = DB::table('deparments')->get();
                            @endphp
                            @foreach ($departments as $dept)
                                <li class="nav-item">
                                    <a href="{{ route('switch', ['selected_department' => $dept->shortName, 'redirect' => url()->current()]) }}"
                                        class="nav-link">
                                        <i
                                            class="far fa-circle nav-icon {{ session('user')['selected_department'] == $dept->shortName ? 'text-danger' : '' }}"></i>
                                        <p>{{ $dept->shortName }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
                @if (user_has_any_role(session('user')['userId'], ['Admin']))
                    <!-- Droplist Menu Dữ Liệu Gốc  -->
                    <li
                        class="nav-item has-treeview {{ str_contains(url()->current(), 'materData') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'materData') ? 'active' : '' }}">
                            <i class="fas fa-database"></i>
                            <p>
                                Dữ Liệu Gốc
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('pages.materData.department.list') }}"
                                    class="nav-link {{ request()->is('materData/department') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-info"></i>
                                    <p>Phòng Ban</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.group.list') }}"
                                    class="nav-link {{ request()->is('materData/group') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-success"></i>
                                    <p>Tổ</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.productName.list') }}"
                                    class="nav-link {{ request()->is('materData/productName') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-danger"></i>
                                    <p>Tên Sản Phẩm</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.analyst.list') }}"
                                    class="nav-link {{ request()->is('materData/analyst') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-primary"></i>
                                    <p>Kiểm Nghiệm Viên</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.purpose.list') }}"
                                    class="nav-link {{ request()->is('materData/purpose') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-info"></i>
                                    <p>Mục Đích Sử Dụng</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.status.list') }}"
                                    class="nav-link {{ request()->is('materData/status') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-warning"></i>
                                    <p>Trạng Thái</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.zone.list') }}"
                                    class="nav-link {{ request()->is('materData/zone*') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-primary"></i>
                                    <p>Định Khu</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.chemName.list') }}"
                                    class="nav-link {{ request()->is('materData/chemName') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-success"></i>
                                    <p>Tên Hoá Chất</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.standardName.list') }}"
                                    class="nav-link {{ request()->is('materData/standardName') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-warning"></i>
                                    <p>Tên Chuẩn</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.materialName.list') }}"
                                    class="nav-link {{ request()->is('materData/materialName') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-primary"></i>
                                    <p>Tên Vật Tư</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.chemManufacturer.list') }}"
                                    class="nav-link {{ request()->is('materData/chemManufacturer') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-secondary"></i>
                                    <p>Nhà Sản Xuất</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.chemSupplier.list') }}"
                                    class="nav-link {{ request()->is('materData/chemSupplier') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-danger"></i>
                                    <p>Nhà Cung Cấp</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.packagingSpecification.list') }}"
                                    class="nav-link {{ request()->is('materData/packagingSpecification') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-info"></i>
                                    <p>Quy Cách Đóng Gói</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.unit.list') }}"
                                    class="nav-link {{ request()->is('materData/unit') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-warning"></i>
                                    <p>Đơn Vị Tính</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.storageCondition.list') }}"
                                    class="nav-link {{ request()->is('materData/storageCondition') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-dark"></i>
                                    <p>Điều Kiện Bảo Quản</p>
                                </a></li>

                        </ul>
                    </li>

                    <!-- Droplist Menu Danh Mục  -->
                    <li
                        class="nav-item has-treeview {{ str_contains(url()->current(), 'category') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'category') ? 'active' : '' }}">
                            <i class="fas fa-clipboard-list"></i>
                            <p>
                                Danh Mục
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('pages.category.materialCategory.list') }}"
                                    class="nav-link {{ request()->is('category/materialCategory') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-primary"></i>
                                    <p>Danh Mục Vật Tư</p>
                                </a></li>
                            {{-- Một trang 2 tab: Danh Mục Hoá Chất Công Ty + Hoá Chất Của Phòng --}}
                            <li class="nav-item"><a href="{{ route('pages.category.chemicalCategory.list') }}"
                                    class="nav-link {{ request()->is('category/chemicalCategory') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-success"></i>
                                    <p>Danh Mục Hoá Chất</p>
                                </a></li>
                            {{-- Một trang 2 tab: Danh Mục Chất Chuẩn Công Ty + Chất Chuẩn Của Phòng --}}
                            <li class="nav-item"><a href="{{ route('pages.category.standardCategory.list') }}"
                                    class="nav-link {{ request()->is('category/standardCategory') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-warning"></i>
                                    <p>Danh Mục Chất Chuẩn</p>
                                </a></li>
                        </ul>
                    </li>


                    <!-- Droplist Menu Nhập  -->
                    <li
                        class="nav-item has-treeview {{ request()->is('import/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('import/*') ? 'active' : '' }}">
                            <i class="fas fa-dolly"></i>
                            <p>
                                Nhập
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('pages.import.chemicalImport.list') }}"
                                    class="nav-link {{ request()->is('import/chemicalImport') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-success"></i>
                                    <p>Nhập Hoá Chất</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.import.standardImport.list') }}"
                                    class="nav-link {{ request()->is('import/standardImport') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-warning"></i>
                                    <p>Nhập Chất Chuẩn</p>
                                </a></li>
                        </ul>
                    </li>

                    <!-- Droplist Menu Sử Dụng  -->
                    <li
                        class="nav-item has-treeview {{ request()->is('export/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('export/*') ? 'active' : '' }}">
                            <i class="fas fa-hand-holding-water"></i>
                            <p>
                                Sử Dụng
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('pages.export.chemicalExport.list') }}"
                                    class="nav-link {{ request()->is('export/chemicalExport') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-success"></i>
                                    <p>Sử Dụng Hoá Chất</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.export.standardExport.list') }}"
                                    class="nav-link {{ request()->is('export/standardExport') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-warning"></i>
                                    <p>Sử Dụng Chất Chuẩn</p>
                                </a></li>
                        </ul>
                    </li>


                    <!-- Droplist Menu Tồn  -->
                    <li
                        class="nav-item has-treeview {{ request()->is('inventory/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('inventory/*') ? 'active' : '' }}">
                            <i class="fas fa-boxes"></i>
                            <p>
                                Tồn
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('pages.inventory.chemicalInventory.list') }}"
                                    class="nav-link {{ request()->is('inventory/chemicalInventory') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-success"></i>
                                    <p>Tồn Kho Hoá Chất</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.inventory.standardInventory.list') }}"
                                    class="nav-link {{ request()->is('inventory/standardInventory') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-warning"></i>
                                    <p>Tồn Kho Chất Chuẩn</p>
                                </a></li>
                        </ul>
                    </li>


                    <!-- Droplist Menu Dự Trù  -->
                    <li
                        class="nav-item has-treeview {{ request()->is('estimate/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('estimate/*') ? 'active' : '' }}">
                            <i class="fas fa-file-signature"></i>
                            <p>
                                Dự Trù
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('pages.estimate.chemicalEstimate.list') }}"
                                    class="nav-link {{ request()->is('estimate/chemicalEstimate*') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-success"></i>
                                    <p>Dự Trù Hoá Chất</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.estimate.standardEstimate.list') }}"
                                    class="nav-link {{ request()->is('estimate/standardEstimate*') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-warning"></i>
                                    <p>Dự Trù Chất Chuẩn</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.estimate.estimateReception.list') }}"
                                    class="nav-link {{ request()->is('estimate/estimateReception*') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon text-warning"></i>
                                    <p>Tiếp Nhận Dự Trù</p>
                                </a></li>
                        </ul>
                    </li>


                    <!-- User Policy -->
                    <li class="nav-header">QUẢN TRỊ</li>
                    <li class="nav-item has-treeview {{ request()->is('user/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('user/*') ? 'active' : '' }}">
                            <i class="fas fa-user-shield"></i>
                            <p>Phân Quyền <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('pages.user.user.list') }}"
                                    class="nav-link {{ request()->is('user/user') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon"></i>
                                    <p>User</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.user.role.list') }}"
                                    class="nav-link {{ request()->is('user/role') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon"></i>
                                    <p>Nhóm Quyền</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.user.permission.list') }}"
                                    class="nav-link {{ request()->is('user/permission') ? 'active' : '' }}"><i
                                        class="far fa-circle nav-icon"></i>
                                    <p>Quyền</p>
                                </a></li>
                        </ul>
                    </li>

                    <li class="nav-item mt-3">
                        <a href="{{ route('pages.auditTrail.list') }}"
                            class="nav-link {{ request()->is('auditTrail') ? 'active' : '' }}">
                            <i class="fas fa-history"></i>
                            <p>Audit Trail</p>
                        </a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
