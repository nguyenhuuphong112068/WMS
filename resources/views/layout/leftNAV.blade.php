<style>
    /* Modern Sidebar Styles */
    .main-sidebar {
        background-color: white !important;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.03) !important;
        border-right: 1px solid rgba(0, 0, 0, 0.05);
    }

    .sidebar {
        padding-top: 20px;
    }

    .nav-pills .nav-link {
        color: #64748b !important;
        margin: 4px 15px;
        border-radius: var(--border-radius-md);
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        padding: 10px 15px;
    }

    .nav-pills .nav-link i {
        font-size: 1.1rem;
        width: 25px;
        margin-right: 10px;
    }

    .nav-pills .nav-link:hover {
        background-color: rgba(0, 58, 79, 0.05) !important;
        color: var(--primary-navy) !important;
        transform: translateX(5px);
    }

    .nav-pills .nav-link.active {
        background-color: var(--primary-navy) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(0, 58, 79, 0.2);
    }

    .nav-pills .nav-link.active i {
        color: white !important;
    }

    .brand-link {
        border-bottom: 0 !important;
        padding: 15px 0;
    }

    .nav-header {
        padding: 15px 25px 5px !important;
        color: #94a3b8 !important;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
    }
</style>

<aside class="main-sidebar elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('pages.general.home') }}" class="brand-link text-center">
        <img src="{{ asset('img/iconstella.svg') }}" alt="Logo" style="width: 50px; height: auto;">
        {{-- Tên dài hơn "LMS-SYSTEM" cũ nên giảm cỡ chữ để xuống dòng gọn trong sidebar --}}
        <span class="brand-text fw-bold d-block mt-2 library-title"
            style="color: var(--primary-navy); font-size: 0.95rem; line-height: 1.3;">
            Sổ theo dõi<br>cấp lại hồ sơ
        </span>
    </a>

    <!-- Sidebar Menu -->
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column mt-5" data-widget="treeview" role="menu"
                data-accordion="false">

                {{-- <li class="nav-header">TỔNG QUAN</li>
                <li class="nav-item">
                    <a href="{{ route('pages.general.home') }}"
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
                                    class="nav-link"><i class="far fa-circle nav-icon text-info"></i>
                                    <p>Phòng Ban</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.status.list') }}"
                                    class="nav-link"><i class="far fa-circle nav-icon text-warning"></i>
                                    <p>Trạng Thái</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.materData.documentType.list') }}"
                                    class="nav-link"><i class="far fa-circle nav-icon text-success"></i>
                                    <p>Loại Tài Liệu</p>
                                </a></li>
                        </ul>
                    </li>

                    <!-- Droplist Menu Vị Trí Lưu Trữ -->
                    <li
                        class="nav-item has-treeview {{ str_contains(url()->current(), 'storageLocation') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'storageLocation') ? 'active' : '' }}">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>
                                Vị Trí Lưu Trữ
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('pages.storageLocation.warehouse.list') }}"
                                    class="nav-link"><i class="far fa-circle nav-icon text-primary"></i>
                                    <p>Kho</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.storageLocation.room.list') }}"
                                    class="nav-link"><i class="far fa-circle nav-icon text-info"></i>
                                    <p>Phòng</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.storageLocation.shelf.list') }}"
                                    class="nav-link"><i class="far fa-circle nav-icon text-warning"></i>
                                    <p>Kệ (Shelf)</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.storageLocation.location.list') }}"
                                    class="nav-link"><i class="far fa-circle nav-icon text-danger"></i>
                                    <p>Vị trí (Location)</p>
                                </a></li>
                        </ul>
                    </li>

                    <!-- Quản lý Tài liệu -->
                    <li class="nav-item">
                        <a href="{{ route('pages.documentStorage.document.list') }}"
                            class="nav-link {{ str_contains(url()->current(), 'documentStorage/document') ? 'active' : '' }}">
                            <i class="fas fa-file-contract"></i>
                            <p>Quản lý Tài liệu</p>
                        </a>
                    </li>

                    <!-- Luân chuyển Hồ sơ -->
                    <li class="nav-item">
                        <a href="{{ route('pages.documentStorage.routing.list') }}"
                            class="nav-link {{ str_contains(url()->current(), 'documentStorage/routing') ? 'active' : '' }}">
                            <i class="fas fa-route"></i>
                            <p>Luân chuyển Hồ sơ</p>
                        </a>
                    </li>
                @endif

                <!-- Xin cấp lại Hồ sơ -->
                <li class="nav-item">
                    <a href="{{ route('pages.documentStorage.reissue.list') }}"
                        class="nav-link {{ str_contains(url()->current(), 'documentStorage/reissue') ? 'active' : '' }}">
                        <i class="fas fa-redo-alt"></i>
                        <p>Xin cấp lại Hồ sơ</p>
                    </a>
                </li>

                <!-- User Policy -->
                @if (user_has_any_role(session('user')['userId'], ['Admin']))
                    <li class="nav-header">QUẢN TRỊ</li>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="fas fa-user-shield"></i>
                            <p>Phân Quyền <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('pages.User.user.list') }}" class="nav-link"><i
                                        class="far fa-circle nav-icon"></i>
                                    <p>User</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.User.role.list') }}" class="nav-link"><i
                                        class="far fa-circle nav-icon"></i>
                                    <p>Nhóm Quyền</p>
                                </a></li>
                            <li class="nav-item"><a href="{{ route('pages.User.permission.list') }}"
                                    class="nav-link"><i class="far fa-circle nav-icon"></i>
                                    <p>Quyền</p>
                                </a></li>
                        </ul>
                    </li>

                    <li class="nav-item mt-3">
                        <a href="{{ route('pages.AuditTrail.list') }}" class="nav-link">
                            <i class="fas fa-history"></i>
                            <p>Audit Trail</p>
                        </a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
