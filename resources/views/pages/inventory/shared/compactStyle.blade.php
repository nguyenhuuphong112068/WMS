{{--
| Giao diện gọn của bảng Tồn Kho (Hoá Chất, Chất Chuẩn): kỳ báo cáo một hàng, chip trạng thái
| trên thanh công cụ, tiêu đề cột dính khi cuộn (.inv-scroll), nút thao tác dạng icon.
| Dùng khi khung trang có class .inv-compact.
--}}
@once
    <style>
        /* ---------- Thu gọn phần bộ lọc phía trên bảng ---------- */
        .inv-compact .inv-period {
            align-items: center;
            gap: 8px 10px;
            padding: 6px 12px;
            margin-bottom: 8px;
        }

        .inv-compact .inv-period-field {
            flex-direction: row;
            align-items: center;
            gap: 6px;
        }

        .inv-compact .inv-period-field label {
            white-space: nowrap;
        }

        .inv-compact .inv-period-field .inv-vs-select {
            width: 130px;
            padding: 2px 8px;
            cursor: pointer;
        }

        .inv-compact .inv-period-field .inv-vs-select.is-on {
            border-color: var(--primary);
            background: var(--primary-soft);
            color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
        }

        .inv-compact .inv-period-field .form-control {
            width: 140px;
            height: 30px;
            padding: 2px 8px;
        }

        .inv-compact .inv-period-apply {
            height: 30px;
            padding: 0 12px;
        }

        .inv-compact .inv-period-quick {
            padding-left: 10px;
            gap: 4px;
        }

        .inv-compact .inv-period-chip {
            padding: 3px 10px;
        }

        .inv-compact .inv-period-note {
            flex: 0 0 auto;
            min-width: 0;
            margin-left: auto;
            cursor: help;
        }

        .inv-compact .inv-tabs {
            margin-bottom: 8px;
        }

        .inv-compact .inv-tab {
            padding: 6px 14px;
        }

        /* Chip trạng thái nằm đầu thanh công cụ chung (.md-tablebar) */
        .inv-compact .md-tablebar .inv-filters {
            order: -1;
            gap: 4px;
            margin: 0;
        }

        .inv-compact .md-tablebar .inv-chip {
            gap: 5px;
            padding: 3px 9px;
            font-size: 0.78rem;
        }

        .inv-compact .md-tablebar .inv-chip.is-active {
            box-shadow: none;
        }

        /*
        | Header bảng luôn hiện khi cuộn: bảng cuộn trong khung cao vừa màn hình,
        | tiêu đề cột dính trên cùng khung (dom 'inv-scroll' ở script.blade.php).
        */
        .inv-compact .table-responsive {
            overflow: visible;
        }

        .inv-compact .inv-scroll {
            max-height: calc(100vh - 150px);
            overflow: auto;
        }

        .inv-compact .inv-scroll table.dataTable {
            margin-top: 0 !important;
        }

        .inv-compact .inv-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: var(--primary-soft);
        }

        /* ---------- Hai bảng tồn theo mã / theo hoá chất: giao diện như Tồn Kho Vật Tư ---------- */
        #invPaneChem #invSummaryTable thead th {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-lighter);
        }

        #invPaneChem #invSummaryTable tbody tr:hover {
            background: var(--primary-soft);
        }

        .inv-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .inv-act-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            padding: 0;
            line-height: 1;
            border-radius: var(--border-radius-md);
            font-size: .85rem;
            border: 1px solid var(--primary);
            background: #fff;
            color: var(--primary);
            transition: all .2s;
        }

        .inv-act-btn:hover,
        .inv-act-btn:focus {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
            outline: none;
        }

        /* Mã âm kho: nút Cân đối tô đỏ để thấy cần xử lý */
        .inv-act-btn.is-danger {
            border-color: #DC2626;
            color: #DC2626;
        }

        .inv-act-btn.is-danger:hover,
        .inv-act-btn.is-danger:focus {
            background: #DC2626;
            color: #fff;
        }

        .inv-act-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            line-height: 1;
            border-radius: var(--border-radius-md);
            font-size: .78rem;
            font-weight: 700;
            border: 1px solid var(--primary-lighter);
            background: var(--primary-soft);
            color: var(--primary);
            transition: all .2s;
        }

        .inv-act-count:hover,
        .inv-act-count:focus {
            background: var(--primary-lighter);
            color: var(--primary-dark);
            border-color: var(--primary);
            transform: translateY(-1px);
            outline: none;
        }

        .inv-threshold-alert {
            border-radius: var(--border-radius-md);
            margin-bottom: 16px;
        }

        .inv-threshold-head {
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .inv-threshold-table th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-top: none;
        }

        .inv-threshold-table td {
            font-size: 0.86rem;
            vertical-align: middle;
        }

        .inv-threshold-dept {
            display: inline-block;
            background: rgba(var(--primary-rgb), 0.08);
            border: 1px solid var(--primary-lighter);
            border-radius: 6px;
            padding: 1px 7px;
            margin: 2px 4px 2px 0;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .inv-threshold-dept.is-current {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            font-weight: 700;
        }
    </style>
@endonce
