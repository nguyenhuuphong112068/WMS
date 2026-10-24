<style>
    .lh-page { padding: 24px 16px; }
    .lh-page .card { border: 0; border-radius: var(--border-radius-lg, 12px); box-shadow: var(--shadow-sm); }
    .lh-page .card-header { background: #fff; border-bottom: 1px solid var(--primary-soft); border-radius: var(--border-radius-lg, 12px) var(--border-radius-lg, 12px) 0 0; padding: 16px 20px; }
    .lh-page .card-title { color: var(--primary); font-weight: 700; }
    .lh-page .card-body { padding: 20px; }
    .lh-page .lh-sub { font-size: 12px; color: #6c757d; margin-top: 2px; }
    .lh-page .lh-mono { font-family: Consolas, "Courier New", monospace; }
    .lh-page .lh-scroll { max-height: 260px; overflow-y: auto; padding: 0; }
    .lh-page .info-box { min-height: 76px; border-radius: var(--border-radius-lg, 12px); box-shadow: var(--shadow-sm); transition: box-shadow .2s, transform .2s; }
    .lh-page .info-box:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
    .lh-page .info-box-icon { border-radius: var(--border-radius-md, 8px); }
    .lh-page .info-box-icon.bg-primary { background: var(--primary) !important; }
    .lh-page .info-box-number { font-size: 22px; color: var(--text-main); }
    .lh-page .table thead th { background: var(--primary-soft); color: var(--primary); border-color: var(--primary-lighter); white-space: nowrap; }
    .lh-page .table td { vertical-align: middle; }
    .lh-page .table-hover tbody tr { transition: background-color .2s; }
    .lh-page .table-hover tbody tr:hover { background: var(--primary-soft); }
    .lh-page tr.lh-current { background: rgba(var(--primary-rgb), 0.08); }
    .lh-page a.lh-user { color: var(--primary); }
    .lh-page a.lh-user:hover { color: var(--primary-dark); }
    .lh-page .form-control, .lh-page .btn { border-radius: var(--border-radius-md, 8px); }
    .lh-page .btn { transition: all .2s; }
    .lh-page .btn:hover { transform: translateY(-1px); }
    .lh-page .btn-primary { background: var(--primary); border-color: var(--primary); }
    .lh-page .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
    .lh-page .pagination .page-item.active .page-link { background: var(--primary); border-color: var(--primary); }
    .lh-page .pagination .page-link { color: var(--primary); }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () { document.body.style.overflowY = 'auto'; });
</script>
