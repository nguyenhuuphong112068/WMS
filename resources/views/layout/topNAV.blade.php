<nav class="main-header navbar navbar-expand navbar-white navbar-light shadow-sm px-3"
    style="border-bottom: 1px solid rgba(var(--primary-rgb), 0.12);">
    <!-- Left navbar links -->
    <ul class="navbar-nav align-items-center">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" style="color: var(--primary);">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <!-- Title Center -->
    <div class="mx-auto text-center">
        <h4 class="library-title mb-0" style="color: var(--primary-dark); letter-spacing: 1px;">
            {{ session('title', 'WMS - QUẢN LÝ KHO') }}
        </h4>
    </div>

    <!-- Right User Info + Notification + Logout -->
    <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item d-flex align-items-center me-3">
            <div id="notif-bell-btn"
                style="border: 1.5px solid var(--accent); border-radius: 50%; width: 38px; height: 38px; display: flex; justify-content: center; align-items: center; cursor: pointer; transition: var(--transition-fast);">
                <i class="far fa-bell" style="font-size: 18px; color: var(--accent);"></i>
                <span class="badge badge-warning" id="notif-badge-navbar"
                    style="display:none; position: absolute; top: -2px; right: -2px; border-radius: 50%;">0</span>
            </div>
        </li>

        <li class="nav-item d-none d-md-flex flex-column align-items-end me-4" style="line-height: 1.2;">
            <span class="fw-bold"
                style="color: var(--primary-dark); font-size: 0.95rem;">{{ session('user')['fullName'] }}</span>
            <span class="text-muted"
                style="font-size: 0.75rem; text-transform: uppercase;">{{ session('user')['userGroup'] }}</span>
        </li>

        <li class="nav-item">
            <a href="{{ route('logout') }}" class="nav-link"
                style="font-size: 1.2rem; color: #dc3545; transition: var(--transition-fast);">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </li>
    </ul>
</nav>
