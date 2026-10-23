{{--
|--------------------------------------------------------------------------
| NHẬP - CSS + JS dùng chung
|--------------------------------------------------------------------------
| Dùng lại toàn bộ giao diện md-* của nhóm Dữ Liệu Gốc (bảng, modal, nút thao tác,
| SweetAlert, DataTables) rồi bổ sung phần riêng của nhóm Nhập:
| - Ô chọn Select2 có tìm kiếm, đặt trong modal
| - Ô mã xuất nhập chỉ đọc, tự đổi theo hoá chất đang chọn
| - Hai tab: Sổ nhập hoá chất và Báo cáo nhập hoá chất theo khoảng thời gian
|
| Quy ước để phần JS bên dưới hoạt động:
| - Ô chọn 1 giá trị       : class="imp-select"
| - Ô xem trước mã xuất nhập   : class="imp-code-preview" kèm data-codes='{"<category_id>":"<mã>"}'
| - Nút chuyển tab         : class="imp-tab" kèm data-pane="<id của pane>"
| - Vùng nội dung của tab  : class="imp-pane", tab đang mở thêm class="is-active"
| - Bảng báo cáo           : class="imp-report-table" kèm data-empty (ChemicalImport/reportTable)
| - Nút chọn nhanh khoảng thời gian : trong .imp-quick, kèm data-from / data-to
| - Nút xem lịch sử điều chỉnh : class="btn-imp-history" kèm data-url / data-title
| - Modal lịch sử          : id="historyModal"
| - Cảnh báo ngưỡng PL IV (Bảng A hoạt chất / Bảng B hỗn hợp), gõ tới đâu cảnh báo tới đó:
|   ô chọn hoá chất kèm data-threshold-url="{{ route(...checkThreshold) }}" + div
|   class="imp-threshold-alerts" trong cùng form; cần input [name="amount"] để lấy số lượng
--}}

@include('pages.materData.shared.assets')

<style>
    /* ---------- Ô chọn Select2 trong modal ---------- */
    .md-modal .select2-container--bootstrap4 .select2-selection {
        border-radius: var(--border-radius-md);
        border: 1px solid #dbe6f2;
        min-height: 40px;
    }

    .md-modal .select2-container--bootstrap4.select2-container--focus .select2-selection {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
    }

    /* Select2 dựng dropdown ở cuối modal, cần nổi trên lớp phủ của modal */
    .select2-container--open {
        z-index: 1061;
    }

    /* ---------- Ô chỉ đọc (mã xuất nhập sinh tự động) ---------- */
    .md-modal .imp-readonly {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        font-weight: 700;
        letter-spacing: 1px;
        cursor: default;
    }

    .md-modal .imp-readonly:focus {
        box-shadow: none;
        border-color: var(--primary-lighter);
    }

    .md-modal .form-group small.md-sub {
        display: block;
        margin-top: 4px;
        font-size: 0.76rem;
    }

    /* ---------- Modal có form bọc body + footer ----------
       Ở các modal Nhập, thẻ <form> bọc cả .modal-body lẫn .modal-footer nên
       cơ chế cuộn của .modal-dialog-scrollable không giữ được nút Lưu khi
       nội dung dài. Cho <form> thành cột co giãn để body tự cuộn, footer luôn hiện. */
    .md-modal .modal-dialog-scrollable .modal-content > form {
        display: flex;
        flex-direction: column;
        min-height: 0;
        max-height: 100%;
        overflow: hidden;
    }

    .md-modal .modal-dialog-scrollable .modal-content > form > .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    .md-modal .modal-dialog-scrollable .modal-content > form > .modal-footer {
        flex-shrink: 0;
    }

    /* ---------- Ô tick hoá chất vi sinh ---------- */
    .imp-switch {
        display: flex;
        align-items: center;
        gap: 9px;
        margin: 0;
        padding: 9px 12px;
        border: 1px solid #dbe6f2;
        border-radius: var(--border-radius-md);
        background: #fff;
        font-weight: 400;
        cursor: pointer;
        transition: background-color var(--transition-fast);
    }

    .imp-switch:hover,
    .imp-switch.is-checked {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
    }

    .imp-switch input {
        width: 17px;
        height: 17px;
        flex-shrink: 0;
        accent-color: var(--primary);
        cursor: pointer;
    }

    /* ---------- Thông tin phụ trên bảng ---------- */
    /* Nền tô theo màu của định khu đang giữ lô này (App\Support\ZoneType::badge());
       chưa định khu hoặc định khu chưa có màu thì --chip/--chip-text rơi về mặc định trắng. */
    .imp-code {
        display: inline-block;
        font-weight: 700;
        letter-spacing: 0.6px;
        white-space: nowrap;
        background: var(--chip, #fff);
        color: var(--chip-text, var(--primary-dark));
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 6px;
        padding: 2px 9px;
    }

    .imp-amount {
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
    }

    /* Hạn dùng đã qua / sắp tới */
    .imp-expired {
        color: #B91C1C;
        font-weight: 700;
    }

    .imp-expiring {
        color: #B45309;
        font-weight: 700;
    }

    /* ---------- Nhà cung cấp: tên trên, địa chỉ dưới ---------- */
    .imp-supplier {
        font-weight: 600;
        color: var(--text-main);
        line-height: 1.35;
    }

    .imp-supplier-address {
        color: #94a3b8;
        font-size: 0.78rem;
        line-height: 1.35;
        margin-top: 2px;
    }

    .imp-supplier-address i {
        font-size: 0.7rem;
        margin-right: 3px;
    }

    /* Lô chưa gán vị trí lưu trữ - tô cam để biết mà bổ sung */
    .imp-no-location {
        color: #B45309;
        font-weight: 600;
        font-style: italic;
    }

    /* ---------- Tab chuyển giữa Sổ nhập và Báo cáo ---------- */
    .imp-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--primary-soft);
    }

    .imp-tab {
        padding: 9px 18px;
        border: none;
        background: transparent;
        color: #64748b;
        font-weight: 600;
        font-size: 0.9rem;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .imp-tab:hover {
        color: var(--primary-dark);
    }

    /* Số lô đang chờ nhận, gắn cạnh tên tab */
    .imp-tab-count {
        display: inline-block;
        min-width: 20px;
        margin-left: 5px;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--accent);
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
        text-align: center;
    }

    /* Nhận nguyên cả lô / nhận lẻ một phần */
    .imp-lot-kind {
        display: inline-block;
        margin-top: 3px;
        border-radius: 999px;
        padding: 0 9px;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
        cursor: help;
    }

    .imp-lot-kind.full {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }

    .imp-lot-kind.partial {
        background: #FEF3C7;
        color: #B45309;
        border: 1px solid #FCD34D;
    }

    /* ---------- Chọn kết quả kiểm tra trong modal Xác nhận kiểm tra ---------- */
    .imp-check-result {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .imp-check-opt {
        display: inline-flex;
        align-items: center;
        margin: 0;
        padding: 9px 18px;
        border: 1px solid #dbe6f2;
        border-radius: var(--border-radius-md);
        background: #fff;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .imp-check-opt input {
        margin-right: 8px;
    }

    .imp-check-opt:hover {
        border-color: var(--primary-lighter);
    }

    .imp-check-opt.is-passed.is-active {
        border-color: #16A34A;
        background: #DCFCE7;
        color: #15803D;
    }

    .imp-check-opt.is-failed.is-active {
        border-color: #DC2626;
        background: #FEE2E2;
        color: #B91C1C;
    }

    /* ---------- Thông tin lô trong modal Nhận hàng ---------- */
    .imp-receive-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 12px 20px;
        margin-bottom: 18px;
        padding: 14px 18px;
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
    }

    .imp-receive-info label {
        display: block;
        margin: 0 0 2px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .imp-receive-info .val {
        font-weight: 700;
        color: var(--primary-dark);
        font-size: 0.9rem;
        word-break: break-word;
    }

    .imp-tab.is-active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .imp-pane {
        display: none;
    }

    .imp-pane.is-active {
        display: block;
    }

    /* ---------- Thanh chọn khoảng thời gian của báo cáo ---------- */
    .imp-range {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
        padding: 16px 18px;
        margin-bottom: 16px;
        border: 1px solid #dbe6f2;
        border-radius: var(--border-radius-lg);
        background: var(--primary-soft);
    }

    .imp-range .form-group {
        margin: 0;
    }

    .imp-range label {
        display: block;
        margin-bottom: 4px;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .imp-range input[type="date"] {
        min-width: 165px;
    }

    .imp-quick {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-left: auto;
    }

    .imp-quick button {
        padding: 6px 13px;
        border: 1px solid #dbe6f2;
        border-radius: 999px;
        background: #fff;
        color: #475569;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .imp-quick button:hover {
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        transform: translateY(-1px);
    }

    /* ---------- Card báo cáo: Hoá chất mua / Nhận chuyển liên phòng ban ---------- */
    .imp-report-card {
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 16px;
        background: #fff;
    }

    .imp-report-card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 16px;
        background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        color: #fff;
    }

    .imp-report-card.is-transfer .imp-report-card-header {
        background: linear-gradient(135deg, #15803D, #16A34A);
    }

    .imp-report-card-title {
        font-size: 0.92rem;
        font-weight: 700;
        letter-spacing: .3px;
    }

    .imp-report-card-count {
        min-width: 22px;
        padding: 1px 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .22);
        font-size: 0.78rem;
        font-weight: 700;
        text-align: center;
    }

    .imp-report-card-body {
        padding: 14px 16px 16px;
    }

    .imp-subtabs {
        margin-bottom: 12px;
    }

    .imp-subtabs .nav-link {
        font-weight: 700;
        font-size: 0.88rem;
        color: #64748b;
        transition: color 0.2s;
    }

    .imp-subtabs .nav-link.active {
        color: var(--primary);
    }

    /* ---------- Bảng báo cáo ---------- */
    .imp-report-table thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: none;
        vertical-align: middle;
    }

    .imp-report-table tbody tr:hover {
        background: rgba(var(--primary-rgb), 0.04);
    }

    .imp-report-table tfoot th {
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-weight: 700;
    }

    .imp-kg {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    /* Không quy đổi được (đơn vị đếm, thiếu tỉ trọng) */
    .imp-kg-na {
        color: #B45309;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: help;
        white-space: nowrap;
    }

    /* Nguyên nhân không quy đổi được, hiện ngay dưới chữ "Không quy đổi" */
    .kg-na-cause {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 3px;
        padding: 1px 8px;
        border-radius: 999px;
        background: #FFFBEB;
        border: 1px solid #FCD34D;
        color: #92400E;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
        cursor: help;
    }

    /* ---------- Lịch sử điều chỉnh ---------- */
    .imp-history-item {
        border: 1px solid #e2e8f0;
        border-left: 3px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        padding: 11px 14px;
        margin-bottom: 10px;
        background: #fff;
    }

    .imp-history-item.create {
        border-left-color: #16A34A;
    }

    .imp-history-item.lock {
        border-left-color: #94A3B8;
    }

    .imp-history-head {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 7px;
    }

    .imp-history-action {
        font-weight: 700;
        color: var(--primary-dark);
        font-size: 0.9rem;
    }

    .imp-history-meta {
        color: #94a3b8;
        font-size: 0.78rem;
    }

    .imp-history-note {
        background: var(--primary-soft);
        border-radius: 6px;
        padding: 7px 10px;
        font-size: 0.83rem;
        color: var(--primary-dark);
        margin-bottom: 7px;
        word-break: break-word;
    }

    /* Lý do do người dùng nhập - tách khỏi nội dung đã đổi cho dễ đọc */
    .imp-history-reason {
        border-left: 3px solid #FCD34D;
        background: #FEF9E7;
        border-radius: 6px;
        padding: 7px 10px;
        font-size: 0.83rem;
        color: #92400E;
        margin-bottom: 8px;
        word-break: break-word;
    }

    .imp-history-snapshot {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 4px 16px;
        font-size: 0.82rem;
        color: #64748b;
    }

    .imp-history-snapshot b {
        color: #475569;
        font-weight: 600;
    }

    .imp-history-empty {
        text-align: center;
        color: #94a3b8;
        padding: 30px 10px;
        font-size: 0.9rem;
    }

    .imp-flag {
        display: inline-block;
        background: #FEF3C7;
        color: #B45309;
        border: 1px solid #FCD34D;
        border-radius: 999px;
        padding: 1px 9px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    /* ---------- Badge số lần điều chỉnh, gắn ở góc trên bên phải nút Sửa ---------- */
    .imp-btn-wrap {
        position: relative;
        display: inline-block;
    }

    .imp-count-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border: 2px solid #fff;
        border-radius: 999px;
        background: var(--accent);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 16px;
        text-align: center;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
        transition: transform var(--transition-fast), background-color var(--transition-fast);
    }

    .imp-count-badge:hover {
        background: var(--primary-dark);
        transform: scale(1.12);
    }

    .imp-count-badge:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.25);
    }

    /* ---------- Cảnh báo ngưỡng tồn trữ PL IV (theo hoạt chất / hỗn hợp) ---------- */
    .imp-threshold-alert {
        border-radius: var(--border-radius-md);
        border: 1px solid;
        padding: 8px 12px;
        margin-top: 8px;
        font-size: 0.82rem;
        line-height: 1.45;
    }

    .imp-threshold-alert.level-warn {
        background: #FFFBEB;
        border-color: #FCD34D;
        color: #92400E;
    }

    .imp-threshold-alert.level-exceeded {
        background: #FEF2F2;
        border-color: #FCA5A5;
        color: #B91C1C;
    }

    /* ---------- Dòng vừa cập nhật ngầm (vd. sau khi Xác nhận kiểm tra) nháy nhẹ để dễ thấy ---------- */
    .imp-row-flash > td {
        animation: impRowFlash 2.4s ease-out;
    }

    @keyframes impRowFlash {
        from {
            background-color: rgba(var(--state-success-rgb), 0.22);
        }

        to {
            background-color: transparent;
        }
    }

    /*
    | ---------- Modal chọn danh mục khi nhập (vd. "Chọn Vật Tư") ----------
    | Mở chồng lên modal Nhập nên đẩy z-index cao hơn; bảng cuộn trong khung, tiêu đề dính.
    */
    .imp-pick {
        z-index: 1060;
    }

    /* Khung modal chiếm 80% màn hình; bảng giãn lấp phần còn lại của thân modal */
    .imp-pick .imp-pick-dialog {
        width: 80vw;
        max-width: 80vw;
    }

    .imp-pick .imp-pick-dialog .modal-content {
        height: 80vh;
    }

    .imp-pick .imp-pick-dialog .modal-body {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .imp-pick .imp-pick-dialog .modal-body > * {
        flex-shrink: 0;
    }

    .imp-pick .imp-pick-dialog .modal-body > .imp-pick-wrap {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
    }

    @media (max-width: 767.98px) {
        .imp-pick .imp-pick-dialog {
            width: auto;
            max-width: none;
        }

        .imp-pick .imp-pick-dialog .modal-content {
            height: auto;
        }

        .imp-pick .imp-pick-dialog .modal-body > .imp-pick-wrap {
            max-height: 56vh;
        }
    }

    .imp-pick .modal-body {
        padding: 18px 22px;
    }

    .imp-pick .imp-pick-total {
        font-size: 0.72rem;
        font-weight: 700;
    }

    .imp-pick-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }

    .imp-pick-search-group {
        flex: 1 1 300px;
        min-width: 0;
    }

    .imp-pick-search-group .input-group-text {
        background: var(--primary-soft);
        border: 1px solid #dbe6f2;
        border-right: 0;
        border-radius: var(--border-radius-md) 0 0 var(--border-radius-md);
        color: var(--primary);
    }

    .md-modal .imp-pick-search-group .imp-pick-search {
        height: 38px;
        border-radius: 0;
        border-left: 0;
        border-right: 0;
    }

    .imp-pick-search::-webkit-search-cancel-button {
        display: none;
    }

    .imp-pick-search-clear {
        background: #fff;
        border: 1px solid #dbe6f2;
        border-left: 0;
        border-radius: 0 var(--border-radius-md) var(--border-radius-md) 0;
        color: #94a3b8;
        transition: color var(--transition-fast);
    }

    .imp-pick-search-clear:hover {
        color: var(--state-danger);
    }

    .imp-pick-search-group:focus-within .input-group-text,
    .imp-pick-search-group:focus-within .imp-pick-search,
    .imp-pick-search-group:focus-within .imp-pick-search-clear {
        border-color: var(--primary-light);
    }

    .md-modal .imp-pick-search-group:focus-within .imp-pick-search {
        box-shadow: none;
    }

    .md-modal .imp-pick-filter-select {
        height: 38px;
        padding: 6px 28px 6px 10px;
        font-size: 0.85rem;
        text-overflow: ellipsis;
        transition: border-color var(--transition-fast), background-color var(--transition-fast);
    }

    .md-modal .imp-pick-quick {
        flex: 0 1 220px;
        width: auto;
        min-width: 180px;
    }

    .md-modal .imp-pick-filter-select.is-active {
        border-color: var(--primary);
        background-color: var(--primary-soft);
        color: var(--primary-dark);
        font-weight: 700;
    }

    .imp-pick-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        border-radius: var(--border-radius-md);
        font-size: 0.85rem;
        font-weight: 600;
        transition: transform var(--transition-fast), background-color var(--transition-fast), color var(--transition-fast);
    }

    .imp-pick-btn:not(:disabled):hover {
        transform: translateY(-1px);
    }

    .imp-pick-more-count {
        display: none;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 999px;
        background: var(--primary);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 18px;
        text-align: center;
    }

    .imp-pick-more:hover .imp-pick-more-count,
    .imp-pick-more[aria-expanded="true"] .imp-pick-more-count {
        background: #fff;
        color: var(--primary);
    }

    .imp-pick-more.has-active .imp-pick-more-count {
        display: inline-block;
    }

    .imp-pick-filters {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px 14px;
        margin-bottom: 12px;
        padding: 12px 14px;
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-lg);
        background: var(--bg-neutral);
    }

    @media (max-width: 991.98px) {
        .imp-pick-filters {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .imp-pick-filters {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    .imp-pick-filter {
        min-width: 0;
    }

    .md-modal .imp-pick-filter label {
        display: block;
        margin-bottom: 4px;
        overflow: hidden;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .md-modal .imp-pick-filter .imp-pick-filter-select {
        width: 100%;
        height: 36px;
    }

    .imp-pick-wrap {
        position: relative;
        max-height: 56vh;
        overflow: auto;
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-md);
    }

    .imp-pick-table {
        margin-bottom: 0;
        font-size: 0.86rem;
    }

    .imp-pick-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 10px;
        border-top: 0;
        border-bottom: 1px solid var(--primary-lighter);
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        white-space: nowrap;
        vertical-align: middle;
    }

    .imp-pick-table thead th[data-sort] {
        cursor: pointer;
        user-select: none;
        transition: background-color var(--transition-fast);
    }

    .imp-pick-table thead th[data-sort]:hover,
    .imp-pick-table thead th.is-sorted {
        background: rgba(var(--primary-rgb), 0.16);
    }

    .imp-pick-sort-icon {
        margin-left: 4px;
        font-size: 0.7rem;
        opacity: 0.45;
    }

    .imp-pick-table th.is-sorted .imp-pick-sort-icon {
        opacity: 1;
    }

    .imp-pick-table td {
        padding: 9px 10px;
        border-top: 1px solid var(--primary-soft);
        vertical-align: middle;
    }

    .imp-pick-row {
        transition: background-color var(--transition-fast);
    }

    .imp-pick-table tbody .imp-pick-row:hover {
        background: var(--primary-soft);
    }

    .imp-pick-row.is-current {
        background: rgba(var(--primary-rgb), 0.08);
        box-shadow: inset 3px 0 0 var(--primary);
    }

    .imp-pick-name {
        font-weight: 700;
        color: var(--text-main);
    }

    .imp-pick-current {
        display: none;
        margin-left: 6px;
        padding: 1px 8px;
        border-radius: 999px;
        background: var(--primary);
        color: #fff;
        font-size: 0.68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .imp-pick-row.is-current .imp-pick-current {
        display: inline-block;
    }

    .imp-pick-mfr-short {
        display: inline-block;
        margin-top: 3px;
        padding: 0 7px;
        border: 1px solid var(--border-soft);
        border-radius: 6px;
        background: var(--bg-neutral);
        color: var(--text-muted);
        font-size: 0.7rem;
        font-weight: 700;
    }

    .imp-pick-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    .imp-pick-chip {
        display: inline-block;
        padding: 1px 8px;
        border: 1px solid var(--primary-lighter);
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
        cursor: help;
    }

    /* Hàng nguy hiểm: đỏ - Cần hiệu chuẩn trước khi dùng: vàng (cùng quy ước chip ở Danh Mục) */
    .imp-pick-chip.critical {
        background: #FEE2E2;
        border-color: #FCA5A5;
        color: #B91C1C;
    }

    .imp-pick-chip.banned {
        background: #FEF3C7;
        border-color: #FCD34D;
        color: var(--state-warning-dark);
    }

    /* Cột Tồn: xanh còn hàng, vàng dưới ngưỡng tối thiểu, đỏ hết hàng, xanh trời vượt tối đa */
    .imp-pick-stock {
        display: inline-block;
        min-width: 58px;
        padding: 2px 10px;
        border: 1px solid;
        border-radius: 999px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        text-align: right;
        cursor: help;
    }

    .imp-pick-stock.is-ok {
        background: rgba(var(--state-success-rgb), 0.1);
        border-color: rgba(var(--state-success-rgb), 0.35);
        color: #15803D;
    }

    .imp-pick-stock.is-low {
        background: rgba(var(--state-warning-rgb), 0.12);
        border-color: rgba(var(--state-warning-rgb), 0.45);
        color: var(--state-warning-dark);
    }

    .imp-pick-stock.is-out {
        background: rgba(var(--state-danger-rgb), 0.08);
        border-color: rgba(var(--state-danger-rgb), 0.35);
        color: #B91C1C;
    }

    .imp-pick-stock.is-over {
        background: rgba(var(--primary-rgb), 0.1);
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
    }

    .imp-pick-stock-sub {
        margin-top: 3px;
        color: var(--text-muted);
        font-size: 0.7rem;
        white-space: nowrap;
    }

    .imp-pick-select {
        border-radius: var(--border-radius-md);
        font-weight: 600;
        padding: 4px 14px;
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
    }

    .imp-pick-select:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    .imp-pick-table .imp-pick-none td {
        padding: 30px 12px;
        color: var(--text-muted);
        text-align: center;
    }

    .imp-pick-none i {
        margin-right: 6px;
        color: var(--primary-lighter);
    }

    .imp-pick-visible {
        color: var(--text-muted);
        font-size: 0.85rem;
    }

    .imp-pick-visible b {
        color: var(--primary);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Bật tìm kiếm cho các ô chọn trong modal ---------- */
        $('.md-modal').each(function() {
            var $modal = $(this);

            $modal.find('.imp-select').each(function() {
                var $select = $(this);
                var options = {
                    theme: 'bootstrap4',
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: '-- Chọn --',
                    language: {
                        noResults: function() {
                            return 'Không tìm thấy dữ liệu phù hợp';
                        },
                        searching: function() {
                            return 'Đang tìm...';
                        },
                        loadingMore: function() {
                            return 'Đang tải thêm...';
                        },
                        errorLoading: function() {
                            return 'Không tải được dữ liệu, vui lòng thử lại';
                        }
                    }
                };

                // Ô danh sách lớn (định khu, hoá chất) gõ tìm qua AJAX thay vì nhúng hàng trăm option
                if ($select.data('lookup-url')) {
                    options.placeholder = $select.find('option[value=""]').first().text().trim() || options.placeholder;
                    // Option trống không nằm trong kết quả tìm: ô không bắt buộc phải xoá được lựa chọn
                    options.allowClear = !$select.prop('required');
                    options.ajax = wmsLookupAjax($select);
                }

                $select.select2(options);
            });
        });

        /* ---------- Tô nền ô tick hoá chất vi sinh ---------- */
        $(document).on('change', '.imp-switch input', function() {
            $(this).closest('.imp-switch').toggleClass('is-checked', this.checked);
        });

        /* ---------- Modal Lịch Sử Điều Chỉnh ---------- */
        $(document).on('click', '.btn-imp-history', function() {
            var url = $(this).data('url');
            var $modal = $('#historyModal');

            $modal.find('.imp-history-subtitle').text($(this).data('title') || '');
            $modal.find('.imp-history-body').html(
                '<div class="imp-history-empty"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải lịch sử...</div>'
            );
            $modal.modal('show');

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(response) {
                    if (!response.ok) throw new Error('http');

                    return response.json();
                })
                .then(function(data) {
                    renderImportHistory(data.rows || []);
                })
                .catch(function() {
                    $modal.find('.imp-history-body').html(
                        '<div class="imp-history-empty">Không tải được lịch sử điều chỉnh. Vui lòng thử lại.</div>'
                    );
                });
        });

        /* Dựng danh sách bằng thao tác DOM để nội dung dữ liệu luôn được escape */
        function renderImportHistory(rows) {
            var $body = $('#historyModal').find('.imp-history-body').empty();

            if (!rows.length) {
                $body.html('<div class="imp-history-empty">Phiếu này chưa có lần điều chỉnh nào được lưu.</div>');

                return;
            }

            var themes = {
                'Thêm mới': 'create',
                'Khoá': 'lock',
                'Mở khoá': 'lock'
            };

            rows.forEach(function(row) {
                var $item = $('<div>').addClass('imp-history-item ' + (themes[row.action] || ''));

                $('<div>').addClass('imp-history-head')
                    .append($('<span>').addClass('imp-history-action').text(row.action))
                    .append($('<span>').addClass('imp-history-meta').text(row.created_by + ' · ' + row.created_at))
                    .appendTo($item);

                if (row.change_note) {
                    $('<div>').addClass('imp-history-note').text(row.change_note).appendTo($item);
                }

                if (row.reason) {
                    $('<div>').addClass('imp-history-reason')
                        .append($('<b>').text('Lý do: '))
                        .append(document.createTextNode(row.reason))
                        .appendTo($item);
                }

                var $snapshot = $('<div>').addClass('imp-history-snapshot');

                Object.keys(row.snapshot || {}).forEach(function(field) {
                    $('<div>')
                        .append($('<b>').text(field + ': '))
                        .append(document.createTextNode(row.snapshot[field]))
                        .appendTo($snapshot);
                });

                $snapshot.appendTo($item);
                $item.appendTo($body);
            });
        }

        /* ---------- Bảng báo cáo nhập theo khoảng thời gian (mua + nhận chuyển) ---------- */
        $('.imp-report-table').each(function() {
            $(this).DataTable({
                autoWidth: false,
                responsive: true,
                pageLength: 25,
                order: [
                    [1, 'asc']
                ],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'Tất cả']
                ],
                language: {
                    search: 'Tìm kiếm:',
                    lengthMenu: 'Hiển thị _MENU_ dòng',
                    info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
                    infoEmpty: 'Không có dữ liệu',
                    zeroRecords: 'Không tìm thấy dòng nào phù hợp',
                    emptyTable: $(this).data('empty') || 'Không có phiếu nhập nào trong khoảng thời gian đã chọn.',
                    paginate: {
                        previous: 'Trước',
                        next: 'Sau'
                    }
                }
            });
        });

        /*
        | Tab con Nhận chuyển cùng công ty / khác công ty: nhớ vào ô ẩn rsub của form lọc + URL
        | để Xem báo cáo / tải lại vẫn mở đúng tab; căn lại cột bảng vừa hiện.
        */
        $(document).on('shown.bs.tab', '.imp-subtabs a', function() {
            var value = $(this).data('sub-value') || '';

            $('.imp-rsub').val(value);

            try {
                var url = new URL(window.location.href);
                value ? url.searchParams.set('rsub', value) : url.searchParams.delete('rsub');
                window.history.replaceState(null, '', url.toString());
            } catch (e) {}

            $($(this).attr('href')).find('table.dataTable').DataTable().columns.adjust();
        });

        /* ---------- Chuyển tab ---------- */
        $(document).on('click', '.imp-tab', function() {
            var target = $(this).data('pane');

            $('.imp-tab').removeClass('is-active');
            $(this).addClass('is-active');

            $('.imp-pane').removeClass('is-active');
            $('#' + target).addClass('is-active');

            // DataTables đo sai bề rộng cột khi bảng bị ẩn lúc khởi tạo
            $.fn.dataTable.tables({
                visible: true,
                api: true
            }).columns.adjust();
        });

        /* ---------- Nút chọn nhanh khoảng thời gian ---------- */
        $(document).on('click', '.imp-quick button', function() {
            var $form = $(this).closest('form');

            $form.find('[name="from"]').val($(this).data('from'));
            $form.find('[name="to"]').val($(this).data('to'));
            $form.trigger('submit');
        });

        /* ---------- Mã xuất nhập xem trước đổi theo hoá chất đang chọn ---------- */
        $(document).on('change', '.md-modal [name="category_id"]', function() {
            var $preview = $(this).closest('form').find('.imp-code-preview');

            if (!$preview.length) return;

            var codes = $preview.data('codes') || {};

            $preview.val(codes[$(this).val()] || $preview.data('placeholder') || '');
        });

        /* ---------- Xoá trắng ô chọn khi mở modal Thêm mới ---------- */
        $(document).on('click', '.btn-md-create', function() {
            var $form = $('#createModal').find('form');

            $form.find('.imp-select').val('').trigger('change');
            $form.find('.imp-switch input').prop('checked', false)
                .closest('.imp-switch').removeClass('is-checked');
        });

        /* ---------- Đổ dữ liệu vào ô chọn khi mở modal Cập nhật ---------- */
        $(document).on('click', '.btn-md-edit', function() {
            var row = $(this).data('row') || {};
            var $form = $('#updateModal').find('form');

            // Số lượng + hoá chất gốc của phiếu đang sửa, để trừ ra khi tính tồn dự kiến
            // (xem checkImpThreshold) - tránh cộng trùng số lượng cũ của chính phiếu này.
            // Đặt TRƯỚC khi trigger 'change' của .imp-select bên dưới để lần kiểm tra ngưỡng
            // đầu tiên đã có đủ dữ liệu, không phải gọi lại lần hai.
            $form.find('[name="original_category_id"]').val(row.category_id || '');
            $form.find('[name="original_amount"]').val(row.amount || '');

            // Select2 chỉ vẽ lại khi có sự kiện change, .val() thôi là chưa đủ. Ô tìm qua AJAX
            // chưa có option của giá trị đang dùng thì dựng từ nhãn row['<tên ô>_text'].
            $form.find('.imp-select').each(function() {
                var field = $(this).attr('name');
                var value = row[field] === undefined || row[field] === null ? '' : row[field];

                if ($(this).data('lookup-url')) {
                    wmsEnsureOption($(this), value, row[field + '_text'], { zone: row[field + '_zone'] });
                }
                $(this).val(value).trigger('change');
            });

            $form.find('.imp-switch input').each(function() {
                var checked = String(row[$(this).attr('name')]) === '1';
                $(this).prop('checked', checked).closest('.imp-switch').toggleClass('is-checked', checked);
            });

            // Lý do là của riêng từng lần điều chỉnh, không lấy lại của lần trước
            $form.find('[name="reason"]').val('').removeClass('is-invalid');
        });

        /* ---------- Cảnh báo ngưỡng PL IV khi chọn hoá chất / gõ số lượng ---------- */
        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, function(c) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[c];
            });
        }

        function renderImpThresholdAlerts($box, warnings) {
            if (!$box.length) return;

            if (!warnings || !warnings.length) {
                $box.empty();
                return;
            }

            $box.html(warnings.map(function(w) {
                return '<div class="imp-threshold-alert level-' + (w.level || 'warn') + '">' +
                    '<i class="fas fa-triangle-exclamation mr-1"></i>' + escapeHtml(w.message) + '</div>';
            }).join(''));
        }

        function checkImpThreshold($form) {
            var $select = $form.find('.imp-select[data-threshold-url]');
            var $box = $form.find('.imp-threshold-alerts');

            if (!$select.length || !$box.length) return;

            var categoryId = $select.val();
            var amount = parseFloat($form.find('[name="amount"]').val());

            // "Số lần nhập": modal Nhập tách 1 lần khai thành N lô cùng số lượng, nên
            // tồn dự kiến để soi ngưỡng phải là amount × số lần. Update không có ô này -> N = 1.
            var times = parseInt($form.find('[name="quantity"]').val(), 10);
            if (!times || times < 1) times = 1;
            if (times > 50) times = 50;

            if (!categoryId || !amount || amount <= 0) {
                renderImpThresholdAlerts($box, []);
                return;
            }

            $.get($select.data('threshold-url'), {
                category_id: categoryId,
                amount: amount * times,
                original_category_id: $form.find('[name="original_category_id"]').val() || '',
                original_amount: $form.find('[name="original_amount"]').val() || ''
            }).done(function(res) {
                renderImpThresholdAlerts($box, res && res.warnings);
            });
        }

        $(document).on('change', '.imp-select[data-threshold-url]', function() {
            checkImpThreshold($(this).closest('form'));
        });

        var impThresholdTimer = null;

        $(document).on('input', '.md-modal [name="amount"], .md-modal [name="quantity"]', function() {
            var $form = $(this).closest('form');

            clearTimeout(impThresholdTimer);
            impThresholdTimer = setTimeout(function() {
                checkImpThreshold($form);
            }, 400);
        });

        // Mở modal Thêm mới thì xoá cảnh báo cũ, không để sót từ lần mở trước
        $(document).on('click', '.btn-md-create', function() {
            $('#createModal').find('.imp-threshold-alerts').empty();
        });
    });
</script>

@include('pages.shared.attachmentListAssets')
