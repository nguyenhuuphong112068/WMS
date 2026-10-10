<style>
    .db-page {
        padding: 24px 24px 40px;
        color: var(--text-main);
    }

    .db-mono {
        font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
    }

    /* ---------- Đầu trang ---------- */
    .db-hero {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        border-radius: var(--border-radius-lg);
        color: var(--surface);
        padding: 20px 24px;
        margin-bottom: 16px;
        box-shadow: var(--shadow-md);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .db-hero h1 {
        color: var(--surface);
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0 0 6px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .db-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 16px;
        font-size: 0.82rem;
        opacity: 0.95;
    }

    .db-hero-meta i {
        margin-right: 4px;
        opacity: 0.8;
    }

    .db-live i {
        font-size: 0.55rem;
        vertical-align: middle;
        animation: db-pulse 2s ease-in-out infinite;
    }

    @keyframes db-pulse {
        50% { opacity: 0.25; }
    }

    .db-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: stretch;
    }

    .db-stat {
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: var(--border-radius-md);
        padding: 8px 14px;
        min-width: 92px;
        text-align: center;
    }

    .db-stat b {
        display: block;
        font-size: 1.15rem;
        line-height: 1.2;
    }

    .db-stat span {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.9;
    }

    .db-hero .btn-refresh {
        background: var(--surface);
        color: var(--primary);
        border: 0;
        font-weight: 600;
        border-radius: var(--border-radius-md);
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
    }

    .db-hero .btn-refresh:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    .db-changed {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(var(--primary-rgb), 0.08);
        border: 1px solid var(--primary-lighter);
        color: var(--primary-dark);
        border-radius: var(--border-radius-md);
        padding: 10px 14px;
        margin-bottom: 14px;
        font-weight: 600;
    }

    .db-changed[hidden] {
        display: none;
    }

    .db-changed .btn {
        margin-left: auto;
    }

    /* ---------- Tab ---------- */
    .db-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
        border-bottom: 2px solid var(--border-soft);
    }

    .db-tab {
        border: 0;
        background: transparent;
        color: var(--text-muted);
        font-weight: 600;
        padding: 10px 16px;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
        transition: color var(--transition-fast), background var(--transition-fast), border-color var(--transition-fast);
    }

    .db-tab:hover {
        color: var(--primary);
        background: var(--primary-soft);
    }

    .db-tab.is-active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .db-tab i {
        margin-right: 6px;
    }

    .db-count {
        display: inline-block;
        min-width: 22px;
        padding: 1px 7px;
        margin-left: 6px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 0.72rem;
        font-weight: 700;
    }

    .db-count.is-danger {
        background: var(--state-danger);
        color: var(--surface);
    }

    .db-count.is-warning {
        background: var(--state-warning);
        color: var(--surface);
    }

    .db-pane[hidden] {
        display: none !important;
    }

    .db-card {
        background: var(--surface);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-soft);
    }

    /* ---------- Bảng: danh sách + chi tiết ---------- */
    .db-split {
        display: grid;
        grid-template-columns: 300px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }

    .db-side {
        position: sticky;
        top: 70px;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 90px);
        overflow: hidden;
    }

    .db-side-tools {
        padding: 14px;
        border-bottom: 1px solid var(--border-soft);
        display: grid;
        gap: 8px;
    }

    .db-side-tools .form-control {
        border-radius: var(--border-radius-md);
        font-size: 0.86rem;
    }

    .db-table-list {
        overflow-y: auto;
        padding: 6px 0 12px;
    }

    .db-list-group {
        padding: 10px 16px 4px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .db-list-item {
        display: block;
        width: 100%;
        border: 0;
        background: transparent;
        text-align: left;
        padding: 6px 16px 6px 14px;
        border-left: 3px solid transparent;
        transition: background var(--transition-fast), border-color var(--transition-fast);
        cursor: pointer;
    }

    .db-list-item:hover {
        background: var(--primary-soft);
    }

    .db-list-item.is-active {
        background: var(--primary-soft);
        border-left-color: var(--primary);
    }

    .db-list-item .name {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-main);
        word-break: break-all;
    }

    .db-list-item .meta {
        font-size: 0.72rem;
        color: var(--text-muted);
    }

    .db-list-item .hit {
        font-size: 0.72rem;
        color: var(--primary-dark);
        word-break: break-all;
    }

    .db-list-item mark {
        padding: 0;
        background: rgba(var(--primary-rgb), 0.18);
        color: inherit;
        border-radius: 2px;
    }

    .db-empty {
        padding: 30px 16px;
        text-align: center;
        color: var(--text-muted);
        font-size: 0.86rem;
    }

    .db-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex: 0 0 auto;
    }

    .db-g-material { --g: var(--db-group-material); }
    .db-g-chemical { --g: var(--db-group-chemical); }
    .db-g-standard { --g: var(--db-group-standard); }
    .db-g-master { --g: var(--db-group-master); }
    .db-g-user { --g: var(--db-group-user); }
    .db-g-utility { --g: var(--db-group-utility); }
    .db-g-system { --g: var(--db-group-system); }
    .db-g-other { --g: var(--db-group-other); }

    .db-dot[class*="db-g-"] { background: var(--g); }

    .db-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        background: var(--primary-soft);
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .db-badge[class*="db-g-"] {
        background: var(--surface);
        border: 1px solid var(--g);
        color: var(--g);
    }

    .db-badge.is-ok { background: rgba(var(--state-success-rgb), 0.12); color: var(--state-success); }
    .db-badge.is-warn { background: rgba(var(--state-warning-rgb), 0.15); color: var(--state-warning-dark); }
    .db-badge.is-danger { background: rgba(var(--state-danger-rgb), 0.1); color: var(--state-danger); }
    .db-badge.is-muted { background: var(--bg-neutral); color: var(--text-muted); }

    .db-detail {
        padding: 22px 24px;
        min-height: 420px;
    }

    .db-detail-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 14px;
        margin-bottom: 18px;
    }

    .db-detail-head h2 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary);
        text-transform: none;
        letter-spacing: 0;
        word-break: break-all;
    }

    .db-detail-head .actions {
        margin-left: auto;
        display: flex;
        gap: 8px;
    }

    .db-btn {
        border-radius: var(--border-radius-md);
        font-weight: 600;
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
    }

    .db-btn:hover {
        transform: translateY(-1px);
    }

    .db-facts {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        margin-bottom: 22px;
    }

    .db-fact {
        background: var(--bg-neutral);
        border: 1px solid var(--border-soft);
        border-radius: var(--border-radius-md);
        padding: 10px 12px;
        min-width: 0;
    }

    .db-fact span {
        display: block;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        margin-bottom: 2px;
    }

    .db-fact b {
        font-size: 0.95rem;
        color: var(--text-main);
        word-break: break-word;
    }

    .db-section {
        margin-bottom: 26px;
    }

    .db-section > h3 {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .db-section > h3 .db-count {
        margin-left: 0;
    }

    .db-grid {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.83rem;
        border: 1px solid var(--border-soft);
        border-radius: var(--border-radius-md);
        overflow: hidden;
    }

    .db-grid th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 8px 10px;
        white-space: nowrap;
    }

    .db-grid td {
        padding: 7px 10px;
        border-top: 1px solid var(--border-soft);
        vertical-align: top;
    }

    .db-grid tbody tr {
        transition: background var(--transition-fast);
    }

    .db-grid tbody tr:hover {
        background: var(--primary-soft);
    }

    .db-grid tr.is-flash {
        animation: db-flash 1.6s ease;
    }

    @keyframes db-flash {
        0%, 40% { background: rgba(var(--primary-rgb), 0.18); }
    }

    .db-grid .col-name {
        font-weight: 600;
        white-space: nowrap;
    }

    .db-grid .col-type {
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .db-grid .num {
        text-align: right;
        color: var(--text-muted);
        width: 36px;
    }

    .db-detail .muted {
        color: var(--text-muted);
    }

    .db-detail .nowrap {
        white-space: nowrap;
    }

    .db-colnote {
        font-weight: 400;
        font-family: inherit;
        color: var(--text-muted);
        font-size: 0.75rem;
    }

    .db-keys {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .db-key {
        font-size: 0.65rem;
        font-weight: 700;
        padding: 1px 5px;
        border-radius: 4px;
        background: var(--bg-neutral);
        color: var(--text-muted);
        border: 1px solid var(--border-soft);
    }

    .db-key.pk { background: rgba(var(--state-warning-rgb), 0.16); color: var(--state-warning-dark); border-color: transparent; }
    .db-key.uq { background: rgba(var(--primary-rgb), 0.12); color: var(--primary-dark); border-color: transparent; }

    .db-links {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    .db-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 1px 8px;
        border-radius: 999px;
        border: 1px solid var(--primary-lighter);
        color: var(--primary-dark);
        background: var(--surface);
        font-size: 0.75rem;
        cursor: pointer;
        word-break: break-all;
        transition: background var(--transition-fast), border-color var(--transition-fast);
    }

    .db-link:hover {
        background: var(--primary-soft);
        border-color: var(--primary);
        text-decoration: none;
    }

    .db-link.src-name {
        border-style: dashed;
        color: var(--text-muted);
    }

    .db-link.src-model {
        border-color: var(--accent);
    }

    .db-flag {
        margin-left: 4px;
        font-size: 0.75rem;
    }

    .db-flag.is-warn { color: var(--state-warning); }
    .db-flag.is-danger { color: var(--state-danger); }

    .db-rel-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
    }

    .db-rel-row .arrow {
        color: var(--primary-lighter);
    }

    .db-refs {
        margin-top: 4px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px 10px;
        font-size: 0.74rem;
    }

    .db-ref {
        color: var(--text-muted);
        white-space: nowrap;
    }

    a.db-ref:hover {
        color: var(--primary);
    }

    .db-more {
        border: 0;
        background: none;
        padding: 0;
        color: var(--primary);
        font-size: 0.74rem;
        font-weight: 600;
        cursor: pointer;
    }

    .db-files {
        display: grid;
        gap: 6px;
    }

    .db-file {
        border: 1px solid var(--border-soft);
        border-radius: var(--border-radius-md);
        padding: 8px 12px;
        font-size: 0.8rem;
    }

    .db-file-head {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 10px;
        align-items: center;
        font-weight: 600;
        word-break: break-all;
    }

    .db-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 380px;
        color: var(--text-muted);
        gap: 10px;
    }

    .db-placeholder i {
        font-size: 2.6rem;
        color: var(--primary-lighter);
    }

    /* ---------- Sơ đồ ---------- */
    .db-erd {
        overflow: hidden;
    }

    .db-erd-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 14px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
    }

    .db-seg {
        display: inline-flex;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        overflow: hidden;
    }

    .db-seg button {
        border: 0;
        background: var(--surface);
        color: var(--primary-dark);
        font-size: 0.82rem;
        font-weight: 600;
        padding: 6px 12px;
        transition: background var(--transition-fast), color var(--transition-fast);
    }

    .db-seg button + button {
        border-left: 1px solid var(--primary-lighter);
    }

    .db-seg button.is-active {
        background: var(--primary);
        color: var(--surface);
    }

    .db-erd-bar .db-select {
        min-width: 260px;
    }

    .db-check {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin: 0;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-main);
        cursor: pointer;
        user-select: none;
    }

    .db-zoom {
        margin-left: auto;
        display: inline-flex;
        gap: 4px;
    }

    .db-zoom button {
        width: 32px;
        height: 32px;
        border-radius: var(--border-radius-md);
        border: 1px solid var(--primary-lighter);
        background: var(--surface);
        color: var(--primary);
        transition: background var(--transition-fast);
    }

    .db-zoom button:hover {
        background: var(--primary-soft);
    }

    .db-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 16px;
        padding: 8px 16px;
        font-size: 0.75rem;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border-soft);
        background: var(--bg-neutral);
    }

    .db-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .db-legend svg {
        overflow: visible;
    }

    .db-stage {
        position: relative;
        height: calc(100vh - 200px);
        min-height: 520px;
        background-color: var(--bg-neutral);
        background-image: radial-gradient(rgba(var(--primary-rgb), 0.12) 1px, transparent 1px);
        background-size: 22px 22px;
        overflow: hidden;
    }

    .db-stage svg.erd {
        width: 100%;
        height: 100%;
        cursor: grab;
        display: block;
    }

    .db-stage svg.erd.is-panning {
        cursor: grabbing;
    }

    .erd-card rect.body {
        fill: var(--surface);
        stroke: var(--border-soft);
        stroke-width: 1;
        filter: drop-shadow(0 2px 4px rgba(var(--primary-rgb), 0.1));
    }

    .erd-card .head {
        fill: var(--g);
    }

    .erd-card rect.zebra {
        fill: var(--bg-neutral);
    }

    .erd-card .info circle {
        fill: var(--surface);
    }

    .erd-card .info text.info-i {
        fill: var(--surface);
        font-size: 11px;
        font-weight: 700;
        font-style: italic;
        font-family: Georgia, serif;
    }

    .erd-card.is-focus rect.body {
        stroke: var(--primary);
        stroke-width: 2;
    }

    .erd-card text {
        font-size: 12px;
        fill: var(--text-main);
    }

    .erd-card text.title {
        fill: var(--surface);
        font-weight: 700;
        font-size: 12.5px;
        cursor: pointer;
    }

    .erd-card text.ctype {
        fill: var(--text-muted);
        font-size: 11px;
    }

    .erd-card text.key {
        font-size: 9px;
        font-weight: 700;
        fill: var(--state-warning-dark);
    }

    .erd-card rect.row-hl {
        fill: transparent;
    }

    .erd-card .info {
        cursor: pointer;
        fill: var(--surface);
        opacity: 0.85;
    }

    .erd-card .info:hover {
        opacity: 1;
    }

    .erd-edge {
        fill: none;
        stroke-width: 1.6;
        transition: opacity var(--transition-fast), stroke-width var(--transition-fast);
    }

    .erd-edge.src-fk { stroke: var(--primary-dark); stroke-width: 2.2; }
    .erd-edge.src-join { stroke: var(--primary); }
    .erd-edge.src-model { stroke: var(--accent); }
    .erd-edge.src-name { stroke: var(--text-muted); stroke-dasharray: 5 4; }
    .erd-edge.card-nn { stroke: var(--state-warning); stroke-dasharray: 2 4; }
    .erd-edge.is-cross { opacity: 0.35; }

    .erd-hit {
        fill: none;
        stroke: transparent;
        stroke-width: 12;
        cursor: pointer;
    }

    svg.erd.is-hover .erd-edge,
    svg.erd.is-hover .erd-card {
        opacity: 0.18;
    }

    svg.erd.is-hover .erd-edge.is-on,
    svg.erd.is-hover .erd-card.is-on {
        opacity: 1;
    }

    svg.erd.is-hover .erd-edge.is-on {
        stroke-width: 2.6;
    }

    .db-tip {
        position: absolute;
        z-index: 5;
        pointer-events: none;
        max-width: 420px;
        background: var(--text-main);
        color: var(--surface);
        border-radius: var(--border-radius-md);
        padding: 8px 12px;
        font-size: 0.78rem;
        box-shadow: var(--shadow-md);
    }

    .db-tip[hidden] {
        display: none;
    }

    .db-tip .t {
        font-weight: 700;
        margin-bottom: 2px;
        word-break: break-all;
    }

    .db-tip .r {
        opacity: 0.8;
        word-break: break-all;
    }

    /* Toàn cảnh: bảng xếp theo nhóm */
    .db-board {
        position: absolute;
        inset: 0;
        overflow: auto;
        padding: 18px;
    }

    .db-board-inner {
        position: relative;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
        align-items: start;
    }

    .db-board svg.links {
        position: absolute;
        left: 0;
        top: 0;
        pointer-events: none;
        overflow: visible;
        z-index: 2;
    }

    .db-board svg.links path {
        fill: none;
        stroke: var(--primary);
        stroke-width: 1.5;
        opacity: 0.75;
    }

    .db-board svg.links path.src-name {
        stroke: var(--text-muted);
        stroke-dasharray: 5 4;
    }

    .db-board svg.links path.out {
        stroke: var(--accent);
    }

    .db-cluster {
        background: var(--surface);
        border: 1px solid var(--border-soft);
        border-top: 4px solid var(--g);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
        padding: 12px 14px 14px;
    }

    .db-cluster-head {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        font-size: 0.86rem;
        margin-bottom: 10px;
        color: var(--text-main);
    }

    .db-cluster-head .db-count {
        margin-left: auto;
    }

    .db-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .db-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--border-soft);
        background: var(--surface);
        border-radius: 6px;
        padding: 3px 8px;
        font-size: 0.76rem;
        cursor: pointer;
        transition: border-color var(--transition-fast), background var(--transition-fast), opacity var(--transition-fast), transform var(--transition-fast);
        position: relative;
        z-index: 3;
    }

    .db-chip:hover {
        border-color: var(--g);
        transform: translateY(-1px);
    }

    .db-chip small {
        color: var(--text-muted);
        font-weight: 700;
    }

    .db-board.is-hover .db-chip {
        opacity: 0.3;
    }

    .db-board.is-hover .db-chip.is-on {
        opacity: 1;
        border-color: var(--g);
        background: var(--bg-neutral);
    }

    .db-board.is-hover .db-chip.is-self {
        opacity: 1;
        background: var(--g);
        color: var(--surface);
        border-color: var(--g);
    }

    .db-board.is-hover .db-chip.is-self small {
        color: var(--surface);
    }

    /* ---------- Danh sách quan hệ / migration ---------- */
    .db-list-card {
        padding: 18px 20px;
    }

    .db-list-card table.dataTable thead th {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .db-list-card table.dataTable td {
        vertical-align: top;
    }

    .db-list-card .db-refs {
        display: grid;
        gap: 2px;
        font-size: 0.82rem;
    }

    .db-goto {
        color: var(--primary-dark);
        font-weight: 600;
        cursor: pointer;
    }

    .db-goto:hover {
        color: var(--primary);
        text-decoration: underline;
    }

    .db-ops {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    .db-op {
        font-size: 0.72rem;
        padding: 1px 7px;
        border-radius: 999px;
        border: 1px solid var(--border-soft);
        cursor: pointer;
        white-space: nowrap;
    }

    .db-op.op-create { color: var(--state-success); border-color: rgba(var(--state-success-rgb), 0.35); }
    .db-op.op-alter { color: var(--primary); border-color: var(--primary-lighter); }
    .db-op.op-rename { color: var(--state-warning-dark); border-color: rgba(var(--state-warning-rgb), 0.45); }
    .db-op.op-data { color: var(--text-muted); border-style: dashed; }
    .db-op.op-drop { color: var(--state-danger); border-color: rgba(var(--state-danger-rgb), 0.35); }

    .db-filter {
        min-width: 200px;
    }

    /* ---------- Kiểm tra ---------- */
    .db-issues {
        display: grid;
        gap: 12px;
    }

    .db-issue {
        background: var(--surface);
        border: 1px solid var(--border-soft);
        border-left: 4px solid var(--lvl);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
    }

    .db-issue.lvl-danger { --lvl: var(--state-danger); }
    .db-issue.lvl-warning { --lvl: var(--state-warning); }
    .db-issue.lvl-info { --lvl: var(--primary-lighter); }

    .db-issue summary {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 18px;
        cursor: pointer;
        font-weight: 700;
        list-style: none;
    }

    .db-issue summary::-webkit-details-marker {
        display: none;
    }

    .db-issue summary .lvl-icon {
        color: var(--lvl);
        width: 18px;
        text-align: center;
    }

    .db-issue summary .chev {
        margin-left: auto;
        color: var(--text-muted);
        transition: transform var(--transition-fast);
    }

    .db-issue[open] summary .chev {
        transform: rotate(180deg);
    }

    .db-issue summary .db-count {
        background: var(--bg-neutral);
        color: var(--text-main);
    }

    .db-issue-body {
        padding: 0 18px 14px 46px;
        display: grid;
        gap: 6px;
        max-height: 420px;
        overflow-y: auto;
    }

    .db-issue-item {
        font-size: 0.82rem;
        padding: 6px 0;
        border-top: 1px dashed var(--border-soft);
    }

    .db-issue-item:first-child {
        border-top: 0;
    }

    .db-ok-state {
        text-align: center;
        padding: 50px 20px;
        color: var(--state-success);
        font-weight: 600;
    }

    .db-ok-state i {
        font-size: 2.4rem;
        display: block;
        margin-bottom: 10px;
    }

    @media (max-width: 991px) {
        .db-split {
            grid-template-columns: 1fr;
        }

        .db-side {
            position: static;
            max-height: 360px;
        }

    }

    @media (max-width: 575px) {
        .db-page {
            padding: 16px 12px 30px;
        }

        .db-detail {
            padding: 16px;
        }

        .db-erd-bar .db-select {
            min-width: 0;
            width: 100%;
        }
    }
</style>
