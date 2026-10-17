{{--
| CSS dùng chung của modal "Thêm ... Dự Trù" dạng bảng (thêm nhiều mặt hàng một lần) ở cả
| ba màn Dự Trù Vật Tư / Hoá Chất / Chất Chuẩn. Tiền tố emi- = "estimate multi item".
|
| - Vật tư           : pages/estimate/MaterialEstimate/itemCreate.blade.php
| - Hoá chất, chuẩn  : pages/estimate/shared/batchItemCreate.blade.php
|
| Mọi quy tắc gói trong #itemCreateModal nên không ảnh hưởng modal khác trên trang.
--}}
@once
    <style>
        #itemCreateModal .modal-dialog {
            max-width: 98vw !important;
            width: 98vw;
            margin: 1.5vh auto;
        }
    
        #itemCreateModal .modal-body {
            padding: 16px 20px;
        }
    
        #itemCreateModal .emi-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }
    
        #itemCreateModal .emi-toolbar .btn {
            margin-right: 4px;
        }
    
        #itemCreateModal .emi-count {
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 0.85rem;
            background: var(--primary-soft);
            padding: 4px 12px;
            border-radius: 20px;
        }
    
        #itemCreateModal .emi-scroll {
            min-height: 320px;
            max-height: 72vh;
            overflow: auto;
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
        }
    
        #itemCreateModal .emi-table {
            width: 100%;
            min-width: 1250px;
            table-layout: fixed;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
    
        #itemCreateModal .emi-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
            vertical-align: middle;
            border-bottom: 1px solid var(--primary-lighter);
            padding: 10px 8px;
        }
    
        #itemCreateModal .emi-table td {
            vertical-align: top;
            padding: 8px;
            border-top: 1px solid #eef3f9;
        }
    
        #itemCreateModal .emi-table tbody tr {
            transition: background .2s;
        }
    
        #itemCreateModal .emi-table tbody tr:hover {
            background: rgba(var(--primary-rgb), 0.04);
        }
    
        #itemCreateModal .emi-table .form-control-sm {
            height: 34px;
            font-size: 0.85rem;
        }
    
        #itemCreateModal .emi-no {
            color: #64748b;
            font-weight: 600;
            padding-top: 15px !important;
        }
    
        /* Độ rộng cột: Vật Tư lấy phần còn lại */
        #itemCreateModal .emi-col-no {
            width: 44px;
        }
    
        #itemCreateModal .emi-col-text {
            width: 15%;
        }
    
        #itemCreateModal .emi-col-pn {
            width: 150px;
        }
    
        #itemCreateModal .emi-col-file { width: 110px; }
    
        #itemCreateModal .emi-file-btn {
            width: 100%;
            height: 34px;
            line-height: 24px;
            cursor: pointer;
            white-space: nowrap;
        }
    
        #itemCreateModal .emi-file-btn.has-files {
            background: var(--primary-soft);
            border-color: var(--primary-lighter);
            color: var(--primary);
            font-weight: 600;
        }
    
        #itemCreateModal .emi-file-names {
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 3px;
            word-break: break-all;
            line-height: 1.3;
        }
    
        #itemCreateModal .emi-col-date {
            width: 160px;
        }
    
        /* Cảnh báo không kịp thời gian đặt hàng */
        #itemCreateModal .emi-lead-warn {
            display: block;
            margin-top: 4px;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1.35;
            background: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
        }
    
        #itemCreateModal .emi-col-unit {
            width: 120px;
        }
    
        #itemCreateModal .emi-col-period {
            width: 150px;
        }
    
        #itemCreateModal .emi-col-act {
            width: 92px;
        }
    
        #itemCreateModal .emi-period {
            display: flex;
            align-items: center;
            gap: 4px;
        }
    
        #itemCreateModal .emi-period input {
            flex: 1;
            min-width: 0;
            font-size: 0.8rem;
            padding: 2px 6px;
            height: 30px;
        }
    
        #itemCreateModal .emi-period .btn {
            padding: 0 7px;
            line-height: 26px;
        }
    
        #itemCreateModal textarea.emi-auto {
            resize: vertical;
            min-height: 34px;
            height: 34px;
        }
    
        #itemCreateModal .emi-act .btn {
            padding: 4px 8px;
        }
    
        /* Ô Vật Tư: dòng danh mục hiện tên cố định, dòng ngoài danh mục là ô gõ tên */
        #itemCreateModal .emi-mat-label {
            min-height: 34px;
            padding: 4px 10px;
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-md);
            background: var(--primary-soft);
            line-height: 1.35;
        }
    
        #itemCreateModal .emi-mat-label.is-invalid {
            border-color: #DC2626;
        }
    
        #itemCreateModal .emi-mat-name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 0.86rem;
        }
    
        #itemCreateModal .emi-mat-meta {
            display: block;
            color: #64748b;
            font-size: 0.75rem;
        }
    
        #itemCreateModal .emi-manual-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }
    
        #itemCreateModal .emi-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 0.68rem;
            font-weight: 700;
            white-space: nowrap;
            vertical-align: middle;
        }
    
        #itemCreateModal .emi-badge-cat {
            background: #fff;
            color: var(--primary);
            border: 1px solid var(--primary-lighter);
            margin-right: 4px;
        }
    
        #itemCreateModal .emi-badge-manual {
            background: #FEF3C7;
            color: #92400E;
        }
    
        #itemCreateModal .js-max-stock-warn {
            font-size: 0.76rem;
            padding: 5px 8px;
        }
    
        /* ---------- Khung chọn từ danh mục phòng ---------- */
        #itemCreateModal .emi-picker {
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-md);
            box-shadow: var(--shadow-sm);
            margin-bottom: 12px;
            overflow: hidden;
        }
    
        #itemCreateModal .emi-picker-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: var(--primary-soft);
            border-bottom: 1px solid var(--primary-lighter);
        }
    
        #itemCreateModal .emi-picker-title {
            color: var(--primary);
            font-weight: 700;
            white-space: nowrap;
        }
    
        #itemCreateModal .emi-picker-search {
            max-width: 380px;
        }
    
        #itemCreateModal .emi-picker-low {
            font-size: 0.85rem;
            color: var(--text-main);
            white-space: nowrap;
            cursor: pointer;
        }
    
        #itemCreateModal .emi-picker-head .close {
            margin-left: auto;
        }
    
        #itemCreateModal .emi-picker-scroll {
            max-height: 34vh;
            overflow: auto;
        }
    
        #itemCreateModal .emi-picker-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #fff;
            color: var(--primary);
            font-size: 0.74rem;
            text-transform: uppercase;
            border-bottom: 1px solid var(--primary-lighter);
        }
    
        #itemCreateModal .emi-picker-table td {
            font-size: 0.85rem;
            vertical-align: middle;
        }
    
        #itemCreateModal .emi-pick-row {
            cursor: pointer;
        }
    
        #itemCreateModal .emi-pick-row.is-checked {
            background: rgba(var(--primary-rgb), 0.08);
        }
    
        #itemCreateModal .emi-pick-row.is-added {
            opacity: .55;
            cursor: default;
        }
    
        #itemCreateModal .emi-picker-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
            border-top: 1px solid #eef3f9;
            background: #fafcff;
        }
    
        #itemCreateModal .emi-pick-count {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary-dark);
        }
    
        #itemCreateModal .emi-empty td {
            text-align: center;
            color: #64748b;
            padding: 40px 10px !important;
            vertical-align: middle;
        }
    
        #itemCreateModal .emi-empty i {
            font-size: 1.6rem;
            color: var(--primary-lighter);
            display: block;
            margin-bottom: 8px;
        }
    
        #itemCreateModal .emi-errors {
            border-radius: var(--border-radius-md);
            font-size: 0.85rem;
            max-height: 120px;
            overflow: auto;
        }
    
        #itemCreateModal .emi-errors ul {
            margin: 0;
            padding-left: 18px;
        }

        /* ---------- Riêng hoá chất / chất chuẩn ---------- */
        #itemCreateModal .emi-col-group {
            width: 150px;
        }

        #itemCreateModal .emi-group-chips .badge {
            font-weight: 600;
            margin: 0 2px 2px 0;
        }

        /* Cảnh báo ngưỡng PL IV của từng dòng hoá chất: gọn một dòng + nút "Chi tiết" */
        #itemCreateModal .emi-threshold-line {
            margin-top: 4px;
            font-weight: 700;
            line-height: 1.7;
        }

        /* Mã đã vượt ngưỡng PL IV (tồn + đang dự trù): khoá, không chọn được */
        #itemCreateModal .emi-pick-row.is-blocked {
            background: #FEF2F2;
            cursor: not-allowed;
        }

        #itemCreateModal .emi-pick-row.is-blocked td {
            color: #991B1B;
        }

        #itemCreateModal .emi-blocked-badge {
            white-space: normal;
            text-align: left;
            line-height: 1.3;
        }
    </style>
@endonce
