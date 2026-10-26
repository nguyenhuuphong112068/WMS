{{--
| CSS + JS dùng chung cho mọi trang hướng dẫn sử dụng.
| - Mục lục bên trái dính theo khi cuộn, tự tô mục đang đọc (IntersectionObserver)
| - Ô lọc mục lục, gõ không dấu vẫn tìm được
| - Nút lên đầu trang; khi in thì ẩn mục lục và các nút
--}}
<style>
    .ug-page {
        padding: 20px;
        background: var(--bg-neutral);
        min-height: calc(100vh - 57px);
        color: var(--text-main);
    }

    /* ---------- Khối tiêu đề ---------- */
    .ug-hero {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 22px 26px;
        margin-bottom: 20px;
        border-radius: var(--border-radius-lg);
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: #fff;
        box-shadow: var(--shadow-md);
    }

    .ug-hero-icon {
        flex: 0 0 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        background: rgba(255, 255, 255, 0.18);
    }

    /* layout/css tô mọi h1 màu chủ đạo - trên nền gradient phải trả về chữ trắng */
    .ug-hero h1 {
        color: #fff;
        margin: 0 0 4px;
        font-size: 1.45rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .ug-hero p {
        margin: 0;
        opacity: 0.92;
    }

    .ug-hero-meta {
        margin-left: auto;
        text-align: right;
        font-size: 0.85rem;
        opacity: 0.9;
        white-space: nowrap;
    }

    .ug-hero-meta .btn {
        margin-top: 6px;
        color: var(--primary);
        background: #fff;
        border: 0;
    }

    /* ---------- Bố cục 2 cột ---------- */
    .ug-layout {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }

    .ug-toc {
        flex: 0 0 280px;
        position: sticky;
        top: 72px;
        max-height: calc(100vh - 90px);
        overflow-y: auto;
        background: var(--surface);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
        padding: 16px;
    }

    .ug-toc-search {
        margin-bottom: 12px;
        border-radius: var(--border-radius-md);
    }

    .ug-toc-group {
        margin-bottom: 10px;
    }

    .ug-toc-group-title {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: var(--text-muted);
        padding: 6px 8px 4px;
    }

    .ug-toc a {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 6px 8px;
        border-radius: var(--border-radius-md);
        color: var(--text-main);
        font-size: 0.9rem;
        transition: background var(--transition-fast), color var(--transition-fast);
    }

    .ug-toc a i {
        width: 16px;
        text-align: center;
        color: var(--primary-lighter);
        transition: color var(--transition-fast);
    }

    .ug-toc a:hover {
        background: var(--primary-soft);
        color: var(--primary-dark);
        text-decoration: none;
    }

    .ug-toc a.active {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 600;
    }

    .ug-toc a.active i,
    .ug-toc a:hover i {
        color: var(--primary);
    }

    .ug-toc-empty {
        padding: 8px;
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .ug-content {
        flex: 1 1 auto;
        min-width: 0;
    }

    /* ---------- Một mục hướng dẫn ---------- */
    .ug-section {
        background: var(--surface);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
        padding: 24px 26px;
        margin-bottom: 20px;
        scroll-margin-top: 72px;
    }

    .ug-section-head {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding-bottom: 14px;
        margin-bottom: 16px;
        border-bottom: 1px solid var(--border-soft);
    }

    .ug-section-icon {
        flex: 0 0 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: var(--primary);
        background: var(--primary-soft);
    }

    .ug-section-head h2 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary);
        letter-spacing: 0.5px;
    }

    .ug-section-path {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .ug-section-tools {
        margin-left: auto;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    .ug-section h3 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin: 20px 0 10px;
    }

    .ug-section h3:first-of-type {
        margin-top: 4px;
    }

    .ug-section p,
    .ug-section li {
        line-height: 1.65;
    }

    .ug-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 600;
        background: var(--primary-soft);
        color: var(--primary-dark);
    }

    .ug-chip.is-warn {
        background: rgba(var(--state-warning-rgb), 0.14);
        color: var(--state-warning-dark);
    }

    .ug-chip.is-danger {
        background: rgba(var(--state-danger-rgb), 0.1);
        color: var(--state-danger);
    }

    .ug-chip.is-success {
        background: rgba(var(--state-success-rgb), 0.12);
        color: var(--state-success);
    }

    .ug-open {
        border-radius: var(--border-radius-md);
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
    }

    .ug-open:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    /* ---------- Các bước thao tác ---------- */
    .ug-steps {
        counter-reset: ug-step;
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .ug-steps > li {
        counter-increment: ug-step;
        position: relative;
        padding: 2px 0 10px 40px;
    }

    .ug-steps > li::before {
        content: counter(ug-step);
        position: absolute;
        left: 0;
        top: 1px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: var(--primary);
        color: #fff;
        font-size: 0.8rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ug-steps > li + li::after {
        content: '';
        position: absolute;
        left: 12px;
        top: -10px;
        height: 12px;
        border-left: 2px solid var(--primary-lighter);
    }

    /* ---------- Bảng trường nhập ---------- */
    .ug-table {
        width: 100%;
        margin-bottom: 0;
        font-size: 0.9rem;
    }

    .ug-table th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        border-color: var(--border-soft) !important;
    }

    .ug-table td {
        vertical-align: top;
        border-color: var(--border-soft) !important;
    }

    .ug-table td:first-child {
        font-weight: 600;
        white-space: nowrap;
    }

    .ug-req {
        color: var(--state-danger);
        font-weight: 700;
    }

    /* ---------- Hộp lưu ý ---------- */
    .ug-callout {
        display: flex;
        gap: 12px;
        padding: 12px 16px;
        margin: 14px 0;
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
        border-left: 4px solid var(--primary);
        font-size: 0.92rem;
    }

    .ug-callout > i {
        margin-top: 3px;
        color: var(--primary);
    }

    .ug-callout.is-warn {
        background: rgba(var(--state-warning-rgb), 0.1);
        border-left-color: var(--state-warning);
    }

    .ug-callout.is-warn > i {
        color: var(--state-warning-dark);
    }

    .ug-callout.is-danger {
        background: rgba(var(--state-danger-rgb), 0.07);
        border-left-color: var(--state-danger);
    }

    .ug-callout.is-danger > i {
        color: var(--state-danger);
    }

    .ug-callout ul {
        margin: 4px 0 0;
        padding-left: 18px;
    }

    /* Nút minh hoạ: dùng đúng class Bootstrap của màn hình thật, chỉ tắt tương tác */
    .ug-ui {
        pointer-events: none;
        padding: 1px 7px;
        font-size: 0.78rem;
        line-height: 1.5;
        vertical-align: 1px;
    }

    .ug-key {
        display: inline-block;
        padding: 0 7px;
        border-radius: 5px;
        border: 1px solid var(--border-soft);
        background: var(--bg-neutral);
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--primary-dark);
    }

    /* ---------- Sơ đồ thứ tự khai báo ---------- */
    .ug-flow {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 12px;
    }

    .ug-flow-step {
        position: relative;
        padding: 14px;
        border-radius: var(--border-radius-md);
        border: 1px solid var(--border-soft);
        background: var(--bg-neutral);
    }

    .ug-flow-step b {
        display: block;
        color: var(--primary);
        margin-bottom: 4px;
    }

    .ug-flow-step .ug-flow-no {
        position: absolute;
        top: -10px;
        right: 12px;
        padding: 0 8px;
        border-radius: 999px;
        background: var(--primary);
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .ug-flow-step a {
        font-size: 0.86rem;
    }

    /* ---------- Liên kết từ một mục về sơ đồ đường đi chung (shared/flowMapLink) ---------- */
    .ug-maplink {
        --bc: var(--primary);
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px 8px;
        margin: 0 0 12px;
        padding: 4px 12px 4px 5px;
        border: 1px solid var(--primary-lighter);
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.84rem;
    }

    .ug-maplink:hover {
        color: var(--primary-dark);
        text-decoration: none;
        border-color: var(--primary);
    }

    .ug-maplink-no {
        display: inline-grid;
        place-items: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--bc);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .ug-maplink.k1 { --bc: #2E7D32; }
    .ug-maplink.k2 { --bc: #1E5FA6; }
    .ug-maplink.k3 { --bc: #C25E0A; }
    .ug-maplink.k4 { --bc: #B3261E; }

    /* ---------- Trang danh sách hướng dẫn ---------- */
    .ug-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .ug-card {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 22px;
        border-radius: var(--border-radius-lg);
        background: var(--surface);
        box-shadow: var(--shadow-sm);
        color: var(--text-main);
        transition: transform var(--transition-normal), box-shadow var(--transition-normal);
    }

    .ug-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
        color: var(--text-main);
        text-decoration: none;
    }

    .ug-card h2 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary);
    }

    .ug-card p {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .ug-card-foot {
        margin-top: auto;
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .ug-top {
        position: fixed;
        right: 24px;
        bottom: 24px;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 0;
        background: var(--primary);
        color: #fff;
        box-shadow: var(--shadow-md);
        opacity: 0;
        pointer-events: none;
        transition: opacity var(--transition-normal), transform var(--transition-fast);
        z-index: 1030;
    }

    .ug-top.show {
        opacity: 1;
        pointer-events: auto;
    }

    .ug-top:hover {
        transform: translateY(-2px);
        background: var(--primary-dark);
    }

    /* ---------- Bảng "vai trò được làm gì" ---------- */
    .ug-role-table td:first-child {
        width: 230px;
        white-space: normal;
    }

    .ug-role-desc {
        display: block;
        margin-top: 2px;
        font-size: 0.78rem;
        font-weight: 400;
        color: var(--text-muted);
    }

    .ug-role-line + .ug-role-line {
        margin-top: 6px;
    }

    .ug-role-group {
        font-weight: 700;
        color: var(--primary-dark);
    }

    .ug-role-tabs {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    /* ---------- Ma trận phân quyền chung ---------- */
    .ug-matrix-scroll {
        max-height: 75vh;
        overflow: auto;
        border: 1px solid var(--border-soft);
        border-radius: var(--border-radius-md);
    }

    .ug-matrix {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.82rem;
        min-width: 100%;
    }

    .ug-matrix th,
    .ug-matrix td {
        border-right: 1px solid var(--border-soft);
        border-bottom: 1px solid var(--border-soft);
        padding: 5px 6px;
    }

    .ug-matrix thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        vertical-align: bottom;
    }

    /* Tên nhóm quyền xoay dọc để 16+ cột vẫn vừa khung */
    .ug-matrix th.ug-mx-role {
        min-width: 34px;
        text-align: center;
    }

    .ug-matrix th.ug-mx-role > span {
        display: inline-block;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        max-height: 160px;
        line-height: 1.15;
        text-align: left;
    }

    .ug-matrix .ug-mx-name {
        position: sticky;
        left: 0;
        z-index: 1;
        min-width: 250px;
        max-width: 320px;
        background: #fff;
    }

    .ug-matrix thead th.ug-mx-name {
        z-index: 3;
        background: var(--primary-soft);
    }

    .ug-matrix .ug-mx-group td {
        background: var(--bg-neutral);
        color: var(--primary-dark);
        font-weight: 700;
    }

    .ug-matrix .ug-mx-group td.ug-mx-name {
        background: var(--bg-neutral);
    }

    .ug-matrix tbody tr:not(.ug-mx-group):hover td {
        background: var(--primary-soft);
    }

    .ug-matrix .ug-mx-child {
        color: var(--text-muted);
    }

    .ug-matrix .ug-mx-child i {
        color: var(--primary-lighter);
        margin-right: 4px;
    }

    .ug-matrix .ug-mx-cell {
        text-align: center;
        vertical-align: middle;
    }

    .ug-matrix .ug-mx-on {
        color: var(--state-success);
    }

    .ug-matrix .ug-mx-idle {
        color: var(--primary-lighter);
    }

    @media (max-width: 991.98px) {
        /* Một cột: phải stretch, để flex-start thì nội dung giãn theo bảng rộng nhất và tràn ngang */
        .ug-layout {
            flex-direction: column;
            align-items: stretch;
        }

        .ug-toc {
            position: static;
            flex-basis: auto;
            width: 100%;
            max-height: 320px;
        }

        .ug-hero {
            flex-wrap: wrap;
        }

        .ug-hero-meta {
            margin-left: 0;
            text-align: left;
        }
    }

    @media (max-width: 575.98px) {
        .ug-page {
            padding: 12px;
        }

        .ug-section {
            padding: 18px 16px;
        }

        .ug-hero {
            padding: 18px 16px;
        }

        .ug-hero h1 {
            font-size: 1.1rem;
        }

        .ug-table td:first-child {
            white-space: normal;
        }
    }

    @media print {
        .main-sidebar,
        .main-header,
        .ug-toc,
        .ug-top,
        .ug-open,
        .ug-hero-meta .btn {
            display: none !important;
        }

        .content-wrapper {
            margin-left: 0 !important;
        }

        .ug-section {
            box-shadow: none;
            border: 1px solid var(--border-soft);
            break-inside: avoid-page;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toc = document.querySelector('.ug-toc');
        var topBtn = document.querySelector('.ug-top');

        if (topBtn) {
            window.addEventListener('scroll', function() {
                topBtn.classList.toggle('show', window.scrollY > 400);
            });
            topBtn.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        if (!toc) return;

        var links = Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]'));

        /* Tô mục đang đọc trên mục lục */
        if ('IntersectionObserver' in window) {
            var byId = {};
            links.forEach(function(a) { byId[a.getAttribute('href').slice(1)] = a; });

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (!entry.isIntersecting) return;
                    links.forEach(function(a) { a.classList.remove('active'); });
                    var link = byId[entry.target.id];
                    if (link) {
                        link.classList.add('active');
                        if (window.innerWidth >= 992) link.scrollIntoView({ block: 'nearest' });
                    }
                });
            }, { rootMargin: '-80px 0px -65% 0px' });

            document.querySelectorAll('.ug-section[id]').forEach(function(s) { observer.observe(s); });
        }

        /* Lọc mục lục - bỏ dấu tiếng Việt để gõ "don vi" vẫn ra "Đơn Vị Tính" */
        function plain(text) {
            return (text || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd');
        }

        var search = toc.querySelector('.ug-toc-search');
        var empty = toc.querySelector('.ug-toc-empty');

        if (search) {
            search.addEventListener('input', function() {
                var q = plain(search.value.trim());
                var shown = 0;

                toc.querySelectorAll('.ug-toc-group').forEach(function(group) {
                    var any = false;
                    group.querySelectorAll('a').forEach(function(a) {
                        var hit = !q || plain(a.textContent + ' ' + (a.dataset.keywords || '')).indexOf(q) !== -1;
                        a.style.display = hit ? '' : 'none';
                        if (hit) { any = true; shown++; }
                    });
                    group.style.display = any ? '' : 'none';
                });

                if (empty) empty.hidden = shown > 0;
            });
        }
    });
</script>
