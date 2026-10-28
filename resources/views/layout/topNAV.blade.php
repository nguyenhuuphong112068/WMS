<nav class="main-header navbar navbar-expand navbar-white navbar-light shadow-sm px-3"
    style="border-bottom: 1px solid rgba(var(--primary-rgb), 0.12);">
    <!-- Left navbar links -->
    <ul class="navbar-nav align-items-center">
        <li class="nav-item">
            <a class="nav-link" id="wms-nav-toggle" href="#" role="button" title="Ghim / bỏ ghim menu"
                style="color: var(--primary);">
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
        {{-- Nút "?": mở mục hướng dẫn của màn hình hiện tại (App\Support\UserGuideLinks), chọn theo tab đang mở --}}
        @php
            $ugRouteName = request()->route()?->getName() ?? '';
            $ugHelp = \App\Support\UserGuideLinks::forRoute($ugRouteName);
        @endphp
        @unless (str_starts_with($ugRouteName, 'pages.userGuide.'))
            <li class="nav-item d-flex align-items-center mr-2">
                <a id="ug-help-btn" class="ug-help-btn" target="_blank" rel="noopener"
                    href="{{ $ugHelp[0]['url'] ?? route('pages.userGuide.list') }}"
                    title="{{ $ugHelp ? 'Hướng dẫn: ' . $ugHelp[0]['title'] : 'Hướng dẫn sử dụng' }}"
                    data-links='@json($ugHelp)'>
                    <i class="fas fa-question"></i>
                </a>
            </li>
        @endunless
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

<style>
    .ug-help-btn {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1.5px solid var(--primary);
        border-radius: 50%;
        color: var(--primary);
        font-size: 16px;
        transition: var(--transition-fast);
    }

    .ug-help-btn:hover {
        background: var(--primary);
        color: #fff;
        transform: translateY(-1px);
    }
</style>

<script>
    // Màn hình nhiều tab: chọn mục hướng dẫn của tab đang hiện (id vùng nội dung khai 'pane' trong toc.php),
    // sau đó theo ?tab= trên URL, cuối cùng là mục đầu tiên
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('ug-help-btn');
        if (!btn) return;

        var links = [];
        try {
            links = JSON.parse(btn.getAttribute('data-links') || '[]');
        } catch (e) {}
        if (links.length < 2) return;

        function pick() {
            for (var i = 0; i < links.length; i++) {
                for (var j = 0; j < links[i].panes.length; j++) {
                    var pane = document.getElementById(links[i].panes[j]);
                    if (pane && pane.offsetParent !== null) return links[i];
                }
            }
            var tab = new URLSearchParams(window.location.search).get('tab');
            for (var k = 0; tab && k < links.length; k++) {
                if (links[k].tab === tab) return links[k];
            }
            return links[0];
        }

        function sync() {
            var link = pick();
            btn.href = link.url;
            btn.title = 'Hướng dẫn: ' + link.title;
        }

        // Cập nhật cả khi rê chuột để tooltip đúng tab đang mở
        btn.addEventListener('mouseenter', sync);
        btn.addEventListener('focus', sync);
        btn.addEventListener('click', sync);
    });
</script>
