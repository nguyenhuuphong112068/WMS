{{--
|--------------------------------------------------------------------------
| TỒN - CSS + JS dùng chung
|--------------------------------------------------------------------------
| Dùng lại toàn bộ giao diện md-* của nhóm Dữ Liệu Gốc (bảng, card, modal,
| DataTables, SweetAlert) rồi bổ sung phần riêng của nhóm Tồn:
| - Thẻ trạng thái tồn (Còn hàng / Sắp hết / Sắp hết hạn / Hết hạn / Hết hàng / Âm kho)
| - Thanh tiến độ đã dùng của từng mã xuất nhập
| - Bộ lọc nhanh theo trạng thái tồn
| - Hai tab: tồn theo mã xuất nhập và tồn cộng dồn theo hoá chất
| - Modal Cân Đối số lượng nhập của một mã xuất nhập
|
| Quy ước để phần JS bên dưới hoạt động:
| - Bảng tồn theo mã xuất nhập : id="mdTable" (do assets của Dữ Liệu Gốc khởi tạo)
| - Bảng tồn theo hoá chất : id="invSummaryTable"
| - Mỗi dòng của bảng mã xuất nhập có data-state="in|low|near|expired|out|over"
| - Nút lọc                : class="inv-chip" kèm data-state="all" hoặc mã trạng thái
| - Nút cân đối            : class="btn-inv-balancing" kèm data-row='{...}'
| - Modal cân đối          : id="balancingModal"
| - Badge số lần cân đối   : class="btn-inv-history" kèm data-row='{...}', nằm ở góc
|                            trên bên phải nút cân đối, bấm vào mở modal lịch sử
| - Modal lịch sử cân đối  : id="balancingHistoryModal", dữ liệu lấy từ
|                            [data-balancings] '{"<import_id>":[{...}]}'
| - Nút hạn dùng nội bộ    : class="btn-inv-internal" kèm data-row='{...}'
| - Modal hạn dùng nội bộ  : id="internalExpiryModal"
| - Nút xem biểu đồ        : class="btn-inv-chart" kèm data-category="<category_id>"
|                            và data-chem="<tên hoá chất>", ở cột cuối bảng tồn theo
|                            hoá chất (chỉ có ở màn hình tồn hoá chất)
| - Modal biểu đồ          : id="chemChartModal" kèm [data-url] [data-from] [data-to],
|                            khung vẽ id="chemChartCanvas" - vẽ bằng Chart.js của layout
--}}

@include('pages.materData.shared.assets')

<style>
    /* ---------- Thẻ trạng thái tồn ---------- */
    .inv-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 1px 10px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .inv-badge-in {
        background: #DCFCE7;
        color: #15803D;
        border-color: #86EFAC;
    }

    .inv-badge-low {
        background: #FEF3C7;
        color: #B45309;
        border-color: #FCD34D;
    }

    .inv-badge-near {
        background: #FFEDD5;
        color: #C2410C;
        border-color: #FDBA74;
    }

    .inv-badge-expired {
        background: #FEE2E2;
        color: #B91C1C;
        border-color: #FCA5A5;
    }

    .inv-badge-out {
        background: #E2E8F0;
        color: #475569;
        border-color: #CBD5E1;
    }

    /* Đã xuất vượt lượng nhập - phải cân đối lại */
    .inv-badge-over {
        background: #FCE7F3;
        color: #9D174D;
        border-color: #F9A8D4;
    }

    /* ---------- Mã xuất nhập / số lượng ---------- */
    .inv-code {
        font-weight: 700;
        color: var(--primary-dark);
        letter-spacing: 0.6px;
        white-space: nowrap;
    }

    .inv-amount {
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
    }

    .inv-remaining {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .inv-remaining.is-zero {
        color: #94A3B8;
    }

    /* Tồn âm do xuất vượt */
    .inv-remaining.is-over {
        color: #9D174D;
    }

    /* Số phát sinh trong kỳ: nhập cộng vào xanh lá, sử dụng / huỷ trừ đi đỏ gạch */
    .inv-amount.is-in {
        color: #15803D;
    }

    .inv-amount.is-out {
        color: #B91C1C;
    }

    .inv-muted {
        color: #94A3B8;
    }

    /* ---------- Kỳ báo cáo ---------- */
    .inv-period {
        display: flex;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 12px;
        padding: 12px 16px;
        margin-bottom: 16px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
    }

    .inv-period-title {
        align-self: center;
        font-size: 0.86rem;
        font-weight: 700;
        color: var(--primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        white-space: nowrap;
    }

    .inv-period-title i {
        margin-right: 5px;
        color: var(--primary);
    }

    .inv-period-field {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .inv-period-field label {
        margin: 0;
        font-size: 0.74rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .inv-period-field .form-control {
        width: 160px;
        height: 34px;
        padding: 4px 10px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-main);
        background: #fff;
        transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    }

    .inv-period-field .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.14);
    }

    .inv-period-apply {
        height: 34px;
        padding: 0 16px;
        border-radius: var(--border-radius-md);
        font-weight: 600;
        white-space: nowrap;
        transition: all var(--transition-fast);
    }

    .inv-period-apply:hover {
        transform: translateY(-1px);
    }

    .inv-period-quick {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        padding-left: 12px;
        margin-left: 2px;
        border-left: 1px solid var(--primary-lighter);
    }

    .inv-period-chip {
        padding: 5px 12px;
        border: 1px solid var(--primary-lighter);
        border-radius: 999px;
        background: #fff;
        color: var(--primary-dark);
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: all var(--transition-fast);
    }

    .inv-period-chip:hover {
        border-color: var(--primary);
        background: #fff;
        transform: translateY(-1px);
    }

    .inv-period-chip.is-active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
        box-shadow: 0 3px 10px rgba(var(--primary-rgb), 0.24);
    }

    .inv-period-note {
        flex: 1 1 220px;
        align-self: center;
        min-width: 220px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
    }

    .inv-period-note i {
        margin-right: 4px;
        color: var(--primary);
    }

    .inv-period-rule {
        display: block;
        margin-top: 4px;
        font-weight: 600;
        color: #94a3b8;
    }

    .inv-period-days {
        display: inline-block;
        margin-left: 6px;
        padding: 1px 9px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--primary-lighter);
        color: var(--primary-dark);
        font-size: 0.72rem;
        font-weight: 700;
    }

    /* Cột số liệu của kỳ - gạch chân nhẹ để tách khỏi các cột luỹ kế bên cạnh */
    th.inv-th-period {
        box-shadow: inset 0 -3px 0 var(--primary-lighter);
    }

    /* Nhãn "Mới nhập trong kỳ" ở cột Tồn Đầu Kỳ */
    .inv-period-tag {
        display: inline-block;
        padding: 1px 8px;
        border-radius: 999px;
        background: #DCFCE7;
        border: 1px solid #86EFAC;
        color: #15803D;
        font-size: 0.68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    /* Tồn cộng dồn theo lô / theo hoá chất - nhạt hơn cột Tồn Còn Lại của chính dòng đó */
    .inv-group-total {
        font-weight: 700;
        white-space: nowrap;
        color: var(--primary);
    }

    .inv-group-total.is-zero {
        color: #94A3B8;
    }

    /* Số ngày còn lại tới hạn áp dụng, ở tab Hạn dùng dưới 6 tháng */
    .inv-countdown {
        display: inline-block;
        margin-top: 2px;
        font-size: 0.76rem;
        font-weight: 700;
        color: #64748b;
        white-space: nowrap;
    }

    .inv-countdown.is-near {
        color: #C2410C;
    }

    .inv-countdown.is-over {
        color: #B91C1C;
    }

    /* Số đã cân đối: dương cộng thêm, âm trừ bớt */
    .inv-balanced {
        font-weight: 700;
        white-space: nowrap;
    }

    .inv-balanced.is-plus {
        color: #15803D;
    }

    .inv-balanced.is-minus {
        color: #B91C1C;
    }

    /* ---------- Modal cân đối ---------- */
    .md-modal .inv-readonly {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        font-weight: 700;
        cursor: default;
    }

    .md-modal .inv-readonly:focus {
        box-shadow: none;
        border-color: var(--primary-lighter);
    }

    .inv-preview {
        display: block;
        margin-top: 4px;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .inv-preview.is-bad {
        color: #B91C1C;
    }

    /* ---------- Badge số lần cân đối, gắn ở góc trên bên phải nút Cân Đối ---------- */
    .inv-btn-wrap {
        position: relative;
        display: inline-block;
    }

    .inv-count-badge {
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

    .inv-count-badge:hover {
        background: var(--primary-dark);
        transform: scale(1.12);
    }

    .inv-count-badge:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.25);
    }

    /* ---------- Modal Lịch Sử Cân Đối ---------- */
    .inv-hist-head {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 26px;
        margin-bottom: 18px;
        padding: 14px 18px;
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
    }

    .inv-hist-head label {
        display: block;
        margin: 0 0 2px;
        color: #64748b;
        font-size: 0.74rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .inv-hist-head > div > div {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .inv-hist-table {
        margin-bottom: 18px;
    }

    .inv-hist-table thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: none;
        vertical-align: middle;
    }

    .inv-hist-table tbody tr:hover {
        background: rgba(var(--primary-rgb), 0.04);
    }

    .inv-hist-table .is-empty {
        padding: 18px;
        text-align: center;
        color: #94A3B8;
        font-size: 0.85rem;
    }

    /* ---------- Modal Biểu Đồ Nhập - Xuất - Tồn ---------- */
    .inv-chart-stats {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }

    @media (max-width: 991px) {
        .inv-chart-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 575px) {
        .inv-chart-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .inv-chart-stat {
        padding: 12px 14px;
        border: 1px solid var(--primary-soft);
        border-left: 4px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: #fff;
        box-shadow: var(--shadow-sm);
    }

    .inv-chart-stat label {
        display: block;
        margin: 0 0 4px;
        color: #64748B;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .inv-chart-stat b {
        font-size: 1.02rem;
        font-weight: 700;
        color: var(--text-main);
        word-break: break-word;
    }

    /* Màu viền trái trùng màu của chính đường / cột đó trên biểu đồ */
    .inv-chart-stat.is-in {
        border-left-color: var(--primary-light);
    }

    .inv-chart-stat.is-in b {
        color: var(--primary-dark);
    }

    .inv-chart-stat.is-balanced {
        border-left-color: var(--accent);
    }

    .inv-chart-stat.is-used {
        border-left-color: #F59E0B;
    }

    .inv-chart-stat.is-used b {
        color: #B45309;
    }

    .inv-chart-stat.is-cancelled {
        border-left-color: #DC2626;
    }

    .inv-chart-stat.is-cancelled b {
        color: #B91C1C;
    }

    .inv-chart-stat.is-closing {
        border-left-color: #16A34A;
    }

    .inv-chart-stat.is-closing b {
        color: #15803D;
    }

    /* Tồn cuối kỳ âm - cùng cách báo với trạng thái "Âm kho" trên bảng */
    .inv-chart-stat.is-closing.is-over {
        border-left-color: #DC2626;
    }

    .inv-chart-stat.is-closing.is-over b {
        color: #B91C1C;
    }

    .inv-chart-box {
        position: relative;
        height: 56vh;
        min-height: 320px;
        padding: 14px 8px 6px;
        margin-bottom: 16px;
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-lg);
        background: #fff;
    }

    /* Lớp phủ lúc đang tải / không có phát sinh / gọi hỏng */
    .inv-chart-state {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 20px;
        border-radius: var(--border-radius-lg);
        background: rgba(255, 255, 255, 0.94);
        color: #64748B;
        font-size: 0.9rem;
        text-align: center;
    }

    .inv-chart-state.is-on {
        display: flex;
    }

    .inv-chart-state i {
        font-size: 1.7rem;
        color: var(--primary-lighter);
    }

    .inv-chart-state.is-bad {
        color: #B91C1C;
    }

    .inv-chart-state.is-bad i {
        color: #DC2626;
    }

    /* ---------- Ô "Hiển thị" và ô "Tìm kiếm" gom về cùng một hàng ---------- */
    .inv-dt-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px 18px;
        margin-bottom: 14px;
    }

    .inv-dt-bar .dataTables_length,
    .inv-dt-bar .dataTables_filter {
        margin: 0;
        padding: 0;
        float: none;
        white-space: nowrap;
    }

    .inv-dt-bar .dataTables_filter {
        margin-left: auto;
        text-align: right;
    }

    .inv-dt-bar label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .inv-dt-bar select,
    .inv-dt-bar input {
        border: 1px solid #dbe6f2;
        border-radius: var(--border-radius-md);
        padding: 6px 10px;
        color: var(--text-main);
        transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    }

    .inv-dt-bar input {
        min-width: 230px;
    }

    .inv-dt-bar select:focus,
    .inv-dt-bar input:focus {
        outline: none;
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
    }

    /* ---------- Bộ chọn định khu Kho / Phòng / Kệ / Vị trí ---------- */
    .inv-zone-picker {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
        margin-bottom: 14px;
        padding: 16px 18px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--primary-soft);
    }

    .inv-zone-field {
        flex: 1 1 180px;
        min-width: 150px;
    }

    .inv-zone-field label {
        display: block;
        margin-bottom: 5px;
        color: var(--primary-dark);
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .inv-zone-field label i {
        margin-right: 5px;
        opacity: 0.75;
    }

    .inv-zone-picker select {
        border-radius: var(--border-radius-md);
        border: 1px solid var(--primary-lighter);
        background: #fff;
    }

    .inv-zone-picker select:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
    }

    /* Cấp không còn lựa chọn nào (ví dụ kho chưa chia phòng) thì làm mờ cho biết */
    .inv-zone-picker select:disabled {
        background: #F1F5F9;
        color: #94A3B8;
        cursor: not-allowed;
    }

    .btn-inv-zone-reset {
        border-radius: var(--border-radius-md);
        white-space: nowrap;
    }

    /* ---------- Dòng tóm tắt vị trí đang xem ---------- */
    .inv-zone-summary {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 20px;
        margin-bottom: 14px;
        font-size: 0.86rem;
    }

    .inv-zone-summary .path {
        font-weight: 700;
        color: var(--primary-dark);
    }

    .inv-zone-summary .stat {
        color: #64748b;
    }

    .inv-zone-summary .stat b {
        color: var(--text-main);
    }

    .inv-zone-none {
        color: #B45309;
        font-weight: 600;
        font-style: italic;
    }

    /* ---------- Thanh tiến độ đã dùng ---------- */
    .inv-bar {
        height: 6px;
        margin-top: 5px;
        border-radius: 999px;
        background: var(--primary-soft);
        overflow: hidden;
    }

    .inv-bar > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--primary-light), var(--primary));
        transition: width var(--transition-normal);
    }

    .inv-bar.is-low > span {
        background: linear-gradient(90deg, #FCD34D, #F59E0B);
    }

    .inv-bar.is-out > span {
        background: #CBD5E1;
    }

    /* ---------- Bộ lọc nhanh ---------- */
    .inv-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .inv-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 14px;
        border: 1px solid #dbe6f2;
        border-radius: 999px;
        background: #fff;
        color: #475569;
        font-size: 0.83rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .inv-chip:hover {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        transform: translateY(-1px);
    }

    .inv-chip.is-active {
        background: linear-gradient(135deg, var(--primary-light), var(--primary));
        border-color: var(--primary);
        color: #fff;
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.24);
    }

    .inv-chip .count {
        display: inline-block;
        min-width: 20px;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.72rem;
        font-weight: 700;
        text-align: center;
    }

    .inv-chip.is-active .count {
        background: rgba(255, 255, 255, 0.28);
        color: #fff;
    }

    /* ---------- Tab chuyển cách xem ---------- */
    .inv-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--primary-soft);
    }

    .inv-tab {
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

    .inv-tab:hover {
        color: var(--primary-dark);
    }

    .inv-tab.is-active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .inv-tab .inv-tab-count {
        display: inline-block;
        min-width: 20px;
        margin-left: 5px;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.72rem;
        font-weight: 700;
        text-align: center;
    }

    .inv-pane {
        display: none;
    }

    .inv-pane.is-active {
        display: block;
    }

    #invSummaryTable thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: none;
        vertical-align: middle;
    }

    #invSummaryTable tbody tr:hover {
        background: rgba(var(--primary-rgb), 0.04);
    }

    /* ---------- Hạn dùng nội bộ ---------- */
    .inv-internal {
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .inv-internal-none {
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

    /* Nhắc việc ở tab "Chưa có hạn nội bộ" - những phiếu này đang bị chặn sử dụng */
    .md-toolbar .hint.inv-blocking {
        background: #FEF3C7;
        border: 1px solid #FCD34D;
        border-radius: var(--border-radius-md);
        color: #B45309;
        padding: 9px 13px;
    }

    .md-toolbar .hint.inv-blocking b {
        color: #92400E;
    }

    /* Kết quả xem trước trong modal xác định hạn dùng nội bộ */
    .md-modal .inv-int-result {
        font-weight: 700;
        letter-spacing: 0.6px;
    }

    .md-modal .inv-int-note.is-capped {
        color: #B45309;
        font-weight: 600;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        var table = $('#mdTable').DataTable();

        // Bảng dùng chung nhắc bấm "Thêm mới", màn hình tồn không có nút đó
        table.settings()[0].oLanguage.sEmptyTable =
            'Chưa có phiếu nhập nào để tính tồn kho. Hãy tạo phiếu ở màn hình Nhập Hoá Chất.';

        /* ---------- Kỳ báo cáo: mốc chọn nhanh ---------- */
        // Điền sẵn hai ô ngày rồi gửi form luôn, người dùng không phải chọn tay từng ngày
        $(document).on('click', '.inv-period-chip', function() {
            var $form = $(this).closest('form');

            $form.find('[name="from_date"]').val($(this).data('from'));
            $form.find('[name="to_date"]').val($(this).data('to'));
            $form.trigger('submit');
        });

        /* ---------- Lọc nhanh theo trạng thái tồn ---------- */
        var invState = 'all';

        $.fn.dataTable.ext.search.push(function(settings, data, index) {
            if (settings.nTable.id !== 'mdTable' || invState === 'all') return true;

            return $(settings.aoData[index].nTr).data('state') === invState;
        });

        $(document).on('click', '.inv-chip', function() {
            invState = $(this).data('state');

            $('.inv-chip').removeClass('is-active');
            $(this).addClass('is-active');

            table.draw();
        });

        /* ---------- Bảng phụ: tồn theo hoá chất và lịch sử cân đối ---------- */
        // Trả về API của bảng để nơi gọi dùng lại (ví dụ bộ lọc định khu cần draw)
        function invTable(selector, order, emptyText, columnDefs) {
            return $(selector).DataTable({
                autoWidth: false,
                responsive: true,
                pageLength: 25,
                order: [order],
                columnDefs: columnDefs || [],
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
                    emptyTable: emptyText,
                    paginate: {
                        previous: 'Trước',
                        next: 'Sau'
                    }
                }
            });
        }

        // Cột cuối của bảng tồn hoá chất là nút Biểu Đồ - không sắp xếp, không tìm kiếm
        // được. Màn hình tồn chất chuẩn dùng chung id bảng nhưng không có cột đó, nên nhận
        // biết bằng chính ô tiêu đề .inv-chart-th thay vì đoán theo màn hình.
        invTable('#invSummaryTable', [1, 'asc'], 'Chưa có hoá chất nào trong kho.',
            $('#invSummaryTable thead .inv-chart-th').length
                ? [{ targets: -1, orderable: false, searchable: false }]
                : []);

        // Chưa có hạn nội bộ: phiếu nhập lâu nhất lên trước vì cần xác định gấp
        invTable('#invInternalTable', [4, 'asc'],
            'Tất cả hoá chất có khai báo hạn dùng mặc định đều đã xác định hạn dùng nội bộ.');

        // Sắp hết hạn: cột 7 = Hạn Áp Dụng, gần hết hạn nhất lên trước
        invTable('#invExpiringTable', [7, 'asc'],
            'Không có mã xuất nhập nào còn tồn mà sắp hết hạn.');

        // Kiểm soát khối lượng
        invTable('#invWeightTable', [6, 'desc'],
            'Không có đối tượng nào có khai báo kiểm soát khối lượng.');

        // Theo định khu: cột 1 = Vị Trí, gom các mã cùng chỗ đứng cạnh nhau
        var zoneTable = invTable('#invZoneTable', [1, 'asc'],
            'Chưa có mã xuất nhập nào ở định khu đang chọn.');

        /* ---------- Lọc tồn theo định khu Kho -> Phòng -> Kệ -> Vị trí ----------
        | Bốn ô chọn dây chuyền: chọn cấp trên thì cấp dưới chỉ còn các mục thuộc cấp đó.
        | Chọn tới cấp nào thì lọc tới cấp đó, nên chỉ chọn Kho là thấy cả kho.
        | Toàn bộ dữ liệu 4 cấp đã nằm sẵn trong [data-zones] nên không phải tải lại trang.
        */
        var $picker = $('.inv-zone-picker');

        if ($picker.length) {
            var zones = $picker.data('zones') || {};

            // Cấp dưới lọc theo cột nào của cấp trên
            var levels = [
                { name: 'warehouse', source: 'warehouses', parents: [] },
                { name: 'room', source: 'rooms', parents: ['warehouse'] },
                { name: 'shelf', source: 'shelves', parents: ['warehouse', 'room'] },
                { name: 'location', source: 'locations', parents: ['warehouse', 'room', 'shelf'] },
            ];

            var picked = { warehouse: '', room: '', shelf: '', location: '' };

            function $select(level) {
                return $picker.find('.inv-zone-select[data-level="' + level + '"]');
            }

            /** Đổ lại lựa chọn của một cấp theo các cấp trên đang chọn */
            function fillLevel(level) {
                var items = (zones[level.source] || []).filter(function(item) {
                    return level.parents.every(function(parent) {
                        return !picked[parent] || String(item[parent + '_id']) === String(picked[parent]);
                    });
                });

                var $el = $select(level.name);
                var keep = items.some(function(i) { return String(i.id) === String(picked[level.name]); });

                // Lựa chọn cũ không còn hợp lệ sau khi đổi cấp trên thì bỏ
                if (!keep) picked[level.name] = '';

                $el.find('option:gt(0)').remove();
                items.forEach(function(item) {
                    $el.append($('<option></option>')
                        .attr('value', item.id)
                        .text(item.name + (item.code ? ' (' + item.code + ')' : '')));
                });

                $el.val(picked[level.name] || '').prop('disabled', items.length === 0);
            }

            function fillAll() {
                levels.forEach(fillLevel);
            }

            /** Câu mô tả vị trí đang xem, lấy đúng nhãn của ô chọn sâu nhất đã chọn */
            function pathText() {
                var parts = [];

                levels.forEach(function(level) {
                    if (picked[level.name]) {
                        parts.push($select(level.name).find('option:selected').text());
                    }
                });

                return parts.length ? parts.join(' / ') : 'Toàn bộ định khu của phòng';
            }

            // Lọc dòng: cấp nào đang chọn thì dòng phải khớp đúng cấp đó
            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                if (settings.nTable.id !== 'invZoneTable') return true;

                var $row = $(settings.aoData[index].nTr);

                return levels.every(function(level) {
                    return !picked[level.name] ||
                        String($row.data(level.name) || '') === String(picked[level.name]);
                });
            });

            function applyZone() {
                zoneTable.draw();

                // Đếm trên đúng phần đang hiển thị sau khi lọc
                var rows = zoneTable.rows({ search: 'applied' }).nodes();
                var chems = {};

                $(rows).each(function() { chems[$(this).data('category')] = true; });

                $('.inv-zone-path').text(pathText());
                $('.inv-zone-codes').text(rows.length);
                $('.inv-zone-chems').text(Object.keys(chems).length);
            }

            $(document).on('change', '.inv-zone-select', function() {
                picked[$(this).data('level')] = $(this).val();

                fillAll();
                applyZone();
            });

            $(document).on('click', '.btn-inv-zone-reset', function() {
                picked = { warehouse: '', room: '', shelf: '', location: '' };

                fillAll();
                applyZone();
            });

            fillAll();
            applyZone();
        }



        /* ---------- Modal Cân Đối ---------- */
        var $balancing = $('#balancingModal');

        /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 */
        function trimNum(value) {
            return String(Number(value).toFixed(4)).replace(/\.?0+$/, '');
        }

        /** Tồn sau cân đối, hiện ngay dưới ô nhập để thấy trước kết quả */
        function previewBalancing() {
            var $form = $balancing.find('form');
            var gap = Number($form.find('[name="current_gap"]').val() || 0);
            var input = $form.find('[name="balancing_amount"]').val();
            var $preview = $form.find('.inv-preview');

            if (input === '' || isNaN(Number(input))) {
                $preview.text('').removeClass('is-bad');
                return;
            }

            var amount = Number(input);
            var after = gap + amount;
            var unit = $form.data('unit') || '';

            // Vượt hạn mức 5% thì báo ngay, Controller vẫn kiểm tra lại khi lưu
            var min = Number($form.data('min-input'));
            var max = Number($form.data('max-input'));

            if (!isNaN(min) && !isNaN(max) && (amount < min || amount > max)) {
                $preview
                    .text('Vượt hạn mức cân đối - chỉ được nhập từ ' + trimNum(min) + ' đến ' + trimNum(max))
                    .addClass('is-bad');
                return;
            }

            $preview
                .text('Tồn sau cân đối: ' + trimNum(after) + ' ' + unit)
                // Controller không cho cân đối xong tồn vẫn âm
                .toggleClass('is-bad', after < 0);
        }

        $(document).on('input', '#balancingModal [name="balancing_amount"]', previewBalancing);

        $(document).on('click', '.btn-inv-balancing', function() {
            var row = $(this).data('row') || {};
            var $form = $balancing.find('form');

            $form.data('unit', row.unit || '');
            $form.data('min-input', row.min_input);
            $form.data('max-input', row.max_input);
            $form.find('[name="import_id"]').val(row.import_id);
            $form.find('[name="current_gap"]').val(row.gap);
            $form.find('.inv-code-view').val(row.code || '');
            $form.find('.inv-chem-view').val(row.chem_name || '');
            $form.find('.inv-gap-view').val(trimNum(row.gap) + ' ' + (row.unit || ''));
            $form.find('.inv-imported-view').val(
                trimNum(row.imported) + (Number(row.balanced) ? ' (đã cân đối ' +
                    (Number(row.balanced) > 0 ? '+' : '') + trimNum(row.balanced) + ')' : ''));

            // Hạn mức cân đối còn lại của mã xuất nhập
            $form.find('.inv-limit-view').val('±' + trimNum(row.limit) + ' ' + (row.unit || ''));
            $form.find('.inv-limit-hint').text(
                'Lần này được nhập từ ' + trimNum(row.min_input) + ' đến ' + trimNum(row.max_input) + '.');

            $form.find('[name="balancing_amount"]')
                .attr('min', trimNum(row.min_input))
                .attr('max', trimNum(row.max_input))
                // Tồn đang âm thì gợi ý sẵn phần bù vừa đủ về 0
                .val(Number(row.gap) < 0 ? trimNum(-row.gap) : '');

            previewBalancing();
            $balancing.modal('show');
        });

        /* ---------- Modal Lịch Sử Cân Đối (mở từ badge trên nút Cân Đối) ---------- */
        var $history = $('#balancingHistoryModal');

        /** Chặn HTML lọt vào từ tên người cân đối */
        function esc(value) {
            return $('<div>').text(value === null || value === undefined ? '' : value).html();
        }

        $(document).on('click', '.btn-inv-history', function() {
            var row = $(this).data('row') || {};
            var histories = ($history.data('balancings') || {})[row.import_id] || [];
            var imported = Number(row.imported) || 0;
            var balanced = Number(row.balanced) || 0;

            $history.find('.inv-hist-code').text(row.code || '—');
            $history.find('.inv-hist-chem').text(
                (row.chem_name || '—') + (row.category_code ? ' (' + row.category_code + ')' : ''));
            $history.find('.inv-hist-imported').text(trimNum(imported) + ' ' + (row.unit || ''));
            $history.find('.inv-hist-gap').text(trimNum(row.gap) + ' ' + (row.unit || ''));
            $history.find('.inv-hist-balanced')
                .attr('class', 'inv-balanced inv-hist-balanced ' + (balanced >= 0 ? 'is-plus' : 'is-minus'))
                .text((balanced > 0 ? '+' : '') + trimNum(balanced) + ' ' + (row.unit || ''));

            var $body = $history.find('.inv-hist-table tbody');

            if (!histories.length) {
                // Badge chỉ hiện khi đã cân đối nên nhánh này gần như không xảy ra, vẫn giữ để bảng không trống trơn
                $body.html('<tr><td colspan="5" class="is-empty">Mã xuất nhập này chưa được cân đối lần nào.</td></tr>');
            } else {
                $body.html(histories.map(function(item, index) {
                    var amount = Number(item.balancing_amount);
                    var ratio = imported > 0 ? (Math.abs(amount) / imported * 100).toFixed(2) + '%' : '—';

                    return '<tr>' +
                        '<td class="text-center">' + (index + 1) + '</td>' +
                        '<td class="text-right"><span class="inv-balanced ' +
                        (amount > 0 ? 'is-plus' : 'is-minus') + '">' +
                        (amount > 0 ? '+' : '') + trimNum(amount) + '</span> ' +
                        '<span class="md-sub">' + esc(row.unit || '') + '</span></td>' +
                        '<td class="text-center md-sub">' + ratio + '</td>' +
                        '<td class="md-sub">' + (esc(item.balancing_by) || '—') + '</td>' +
                        '<td class="text-center md-sub">' + esc(item.balancing_at) + '</td>' +
                        '</tr>';
                }).join(''));
            }

            $history.modal('show');
        });

        /* ---------- Modal Xác Định Hạn Dùng Nội Bộ ---------- */
        var $internal = $('#internalExpiryModal');

        /** Ngày yyyy-mm-dd -> Date lúc 0h, tránh lệch múi giờ khi parse chuỗi thuần */
        function toDate(value) {
            if (!value) return null;

            var parts = String(value).substr(0, 10).split('-');

            if (parts.length !== 3) return null;

            return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        }

        function fmtDate(date) {
            if (!date) return '';

            var d = String(date.getDate()).padStart(2, '0');
            var m = String(date.getMonth() + 1).padStart(2, '0');

            return d + '/' + m + '/' + date.getFullYear();
        }

        /**
         * Cộng thêm số tháng, không tràn sang tháng sau khi ngày gốc không tồn tại
         * ở tháng đích (31/01 + 1 tháng = 28/02), khớp với addMonthsNoOverflow của Carbon.
         */
        function addMonths(date, months) {
            var target = new Date(date.getFullYear(), date.getMonth() + months, 1);
            var lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();

            target.setDate(Math.min(date.getDate(), lastDay));

            return target;
        }

        /** Xem trước hạn nội bộ ngay khi đổi ngày xác định; Controller vẫn tính lại từ DB */
        function previewInternal() {
            var $form = $internal.find('form');
            var months = Number($form.find('[name="shelf_life_months"]').val() || 0);
            var determined = toDate($form.find('[name="determined_date"]').val());
            var manufacturer = toDate($form.find('[name="manufacturer_expiry"]').val());
            var $result = $form.find('.inv-int-result');
            var $note = $form.find('.inv-int-note');

            if (!determined || months <= 0) {
                $result.val('');
                $note.text('').removeClass('is-capped');

                return;
            }

            var internal = addMonths(determined, months);
            var capped = manufacturer && internal > manufacturer;

            if (capped) internal = manufacturer;

            $result.val(fmtDate(internal));
            $note.text(capped ?
                    'Cộng đủ ' + months + ' tháng sẽ vượt hạn nhà sản xuất nên lấy theo hạn nhà sản xuất.' :
                    'Ngày xác định + ' + months + ' tháng.')
                .toggleClass('is-capped', !!capped);
        }

        $(document).on('change input', '#internalExpiryModal [name="determined_date"]', previewInternal);

        $(document).on('click', '.btn-inv-internal', function() {
            var row = $(this).data('row') || {};
            var $form = $internal.find('form');

            $form.find('[name="import_id"]').val(row.import_id);
            $form.find('[name="shelf_life_months"]').val(row.shelf_life_months);
            $form.find('[name="manufacturer_expiry"]').val(row.expired_date || '');
            $form.find('.inv-int-code').val(row.code || '');
            $form.find('.inv-int-chem').val(row.chem_name || '');
            $form.find('.inv-int-months').val((row.shelf_life_months || 0) + ' tháng');
            $form.find('.inv-int-manu').val(fmtDate(toDate(row.expired_date)) || '—');
            $form.find('.inv-int-current').val(fmtDate(toDate(row.internal_expired_date)) || '');

            previewInternal();
            $internal.modal('show');
        });

        /* ---------- Modal Biểu Đồ Nhập - Xuất - Tồn ----------
        | Dùng chung cho màn hình tồn hoá chất và tồn vật tư: modal nhận diện bằng
        | class .inv-chart-modal, mỗi màn hình tự khai [data-url] của mình. Màn hình
        | nào không có modal đó thì bỏ qua toàn bộ khối này.
        |
        | Mỗi lần mở là một lần gọi JSON theo category_id của dòng vừa bấm và ĐÚNG kỳ
        | báo cáo đang xem, nên không phải tải trước số liệu của mọi mã.
        */
        var $chart = $('.inv-chart-modal');

        if ($chart.length) {
            var $chartState = $chart.find('.inv-chart-state');
            var chartInstance = null;

            // Dữ liệu về trước khi modal mở xong thì chờ: Chart.js đo sai khung vẽ
            // khi khung còn đang trong hiệu ứng mở của Bootstrap
            var chartShown = false;
            var chartPending = null;

            /** Số kèm đơn vị cho hàng chỉ số phía trên biểu đồ */
            function chartAmount(value, unit, signed) {
                var number = Number(value) || 0;

                return (signed && number > 0 ? '+' : '') + trimNum(number) + (unit ? ' ' + unit : '');
            }

            /** Lớp phủ khung vẽ: đang tải / không có phát sinh / gọi hỏng */
            function chartState(text, isBad, isLoading) {
                var icon = isLoading ? 'fa-circle-notch fa-spin' : (isBad ? 'fa-exclamation-triangle' : 'fa-chart-bar');

                $chartState
                    .toggleClass('is-bad', !!isBad)
                    .addClass('is-on')
                    .html('<i class="fas ' + icon + '"></i><span>' + esc(text) + '</span>');
            }

            /** Cột phát sinh trong mốc + đường tồn cuối mốc + đường ngưỡng tồn tối thiểu */
            function chartDatasets(data) {
                var bars = [
                    { key: 'imported', label: 'Nhập', color: '#5AA0DE' },
                    { key: 'used', label: 'Sử dụng', color: '#F59E0B' },
                    // Hoá chất gọi là "Huỷ", vật tư gọi là "Loại bỏ" - lấy theo modal
                    { key: 'cancelled', label: $chart.data('cancel-label') || 'Huỷ', color: '#DC2626' },
                ];

                // Cân đối rất ít phát sinh, chỉ thêm cột khi kỳ này thực sự có cân đối
                if (Number(data.totals.balanced) !== 0) {
                    bars.splice(1, 0, { key: 'balanced', label: 'Cân đối', color: '#17B8D4' });
                }

                var datasets = bars.map(function(bar) {
                    return {
                        label: bar.label,
                        backgroundColor: bar.color,
                        borderColor: bar.color,
                        yAxisID: 'flow',
                        order: 3,
                        data: data.points.map(function(point) { return point[bar.key]; })
                    };
                });

                datasets.push({
                    type: 'line',
                    label: 'Tồn cuối mốc',
                    borderColor: '#16A34A',
                    backgroundColor: 'rgba(22, 163, 74, 0.10)',
                    borderWidth: 3,
                    pointRadius: 3,
                    pointBackgroundColor: '#16A34A',
                    lineTension: 0.25,
                    fill: true,
                    yAxisID: 'stock',
                    order: 1,
                    data: data.points.map(function(point) { return point.closing; })
                });

                // Ngưỡng tồn tối thiểu của phòng - đường đứt để thấy lúc nào tồn chạm đáy
                if (data.min_stock !== null && Number(data.min_stock) > 0) {
                    datasets.push({
                        type: 'line',
                        label: 'Ngưỡng tồn tối thiểu',
                        borderColor: '#94A3B8',
                        borderDash: [6, 4],
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false,
                        yAxisID: 'stock',
                        order: 2,
                        data: data.points.map(function() { return Number(data.min_stock); })
                    });
                }

                return datasets;
            }

            /**
             * Hai trục dọc: bên trái là phát sinh trong mốc (cột), bên phải là tồn cuối
             * mốc (đường). Tách trục vì tồn thường lớn hơn hẳn phát sinh từng mốc, để
             * chung một trục thì cột bị dẹp lép không đọc được.
             */
            function chartDraw(data) {
                var unit = data.unit || '';

                // Gỡ lớp phủ "đang tải" ngay lúc vẽ, không gỡ sớm hơn để khỏi loé khung trắng
                $chartState.removeClass('is-on is-bad');

                if (chartInstance) chartInstance.destroy();

                chartInstance = new Chart($chart.find('.inv-chart-canvas')[0].getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.points.map(function(point) { return point.label; }),
                        datasets: chartDatasets(data)
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, boxWidth: 10, padding: 16 }
                        },
                        hover: { mode: 'index', intersect: false },
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                // Nhãn trục chỉ ghi gọn "d/m", tooltip mới ghi đủ khoảng ngày của mốc
                                title: function(items) {
                                    return data.points[items[0].index].range;
                                },
                                label: function(item, content) {
                                    return content.datasets[item.datasetIndex].label + ': ' +
                                        trimNum(item.yLabel) + (unit ? ' ' + unit : '');
                                }
                            }
                        },
                        scales: {
                            xAxes: [{
                                gridLines: { display: false },
                                ticks: { autoSkip: true, maxTicksLimit: 24, maxRotation: 0 }
                            }],
                            yAxes: [
                                {
                                    id: 'flow',
                                    position: 'left',
                                    ticks: { beginAtZero: true },
                                    gridLines: { color: 'rgba(148, 163, 184, 0.18)' },
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Phát sinh trong mốc' + (unit ? ' (' + unit + ')' : '')
                                    }
                                },
                                {
                                    id: 'stock',
                                    position: 'right',
                                    ticks: { beginAtZero: true },
                                    gridLines: { display: false },
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Tồn cuối mốc' + (unit ? ' (' + unit + ')' : '')
                                    }
                                }
                            ]
                        }
                    }
                });
            }

            /** Hàng chỉ số phía trên biểu đồ - đúng các cột của bảng cộng dồn để đối chiếu */
            function chartFill(data) {
                var unit = data.unit || '';

                // Màn hình hoá chất trả về chem_name, màn hình vật tư trả về material_name
                $chart.find('.inv-chart-chem').text(data.chem_name || data.material_name || '—');
                $chart.find('.inv-chart-code').text(data.category_code || '—');

                // Ô quy cách / nhà sản xuất chỉ có ở modal vật tư, modal hoá chất bỏ qua
                $chart.find('.inv-chart-spec').text(
                    [data.manufacturer_short_name, data.technical_specification]
                        .filter(function(part) { return part; }).join(' · ') || '—');
                $chart.find('.inv-chart-period').text(data.period.label);
                $chart.find('.inv-chart-bucket').text(data.bucket_label + ' - ' + data.points.length + ' mốc');
                $chart.find('.inv-chart-unit').text(unit || '—');

                $chart.find('.inv-chart-opening').text(chartAmount(data.opening, unit));
                $chart.find('.inv-chart-imported').text(chartAmount(data.totals.imported, unit));
                $chart.find('.inv-chart-balanced').text(chartAmount(data.totals.balanced, unit, true));
                $chart.find('.inv-chart-used').text(chartAmount(data.totals.used, unit));
                $chart.find('.inv-chart-cancelled').text(chartAmount(data.totals.cancelled, unit));
                $chart.find('.inv-chart-closing').text(chartAmount(data.closing, unit));

                // Tồn cuối kỳ âm - báo cùng kiểu với trạng thái "Âm kho" của bảng
                $chart.find('.inv-chart-stat.is-closing').toggleClass('is-over', Number(data.closing) < 0);
            }

            $chart.on('shown.bs.modal', function() {
                chartShown = true;

                if (chartPending) {
                    var data = chartPending;

                    chartPending = null;
                    chartDraw(data);
                }
            });

            $chart.on('hidden.bs.modal', function() {
                chartShown = false;
                chartPending = null;

                if (chartInstance) {
                    chartInstance.destroy();
                    chartInstance = null;
                }
            });

            $(document).on('click', '.btn-inv-chart', function() {
                // Tên hoá chất có sẵn trên nút nên hiện ngay, không phải chờ số liệu về
                $chart.find('.inv-chart-chem').text($(this).data('chem') || '—');
                $chart.find('.inv-chart-code, .inv-chart-period, .inv-chart-bucket, .inv-chart-unit, .inv-chart-spec')
                    .text('—');
                $chart.find('.inv-chart-stats b').text('—');
                $chart.find('.inv-chart-stat.is-closing').removeClass('is-over');

                if (chartInstance) {
                    chartInstance.destroy();
                    chartInstance = null;
                }

                chartPending = null;
                chartState('Đang tải số liệu...', false, true);
                $chart.modal('show');

                $.getJSON($chart.data('url'), {
                    category_id: $(this).data('category'),
                    from_date: $chart.data('from'),
                    to_date: $chart.data('to')
                }).done(function(data) {
                    chartFill(data);

                    var moved = Number(data.totals.imported) + Number(data.totals.balanced) +
                        Number(data.totals.used) + Number(data.totals.cancelled);

                    // Không phát sinh gì mà tồn đầu kỳ cũng bằng 0 thì vẽ ra chỉ là đường thẳng 0
                    if (!moved && !Number(data.opening)) {
                        chartState('Hoá chất này không có phát sinh nhập - xuất nào trong kỳ đang xem.', false, false);

                        return;
                    }

                    if (chartShown) {
                        chartDraw(data);
                    } else {
                        chartPending = data;
                    }
                }).fail(function(xhr) {
                    chartState(
                        (xhr.responseJSON && xhr.responseJSON.message) ||
                        'Không tải được số liệu biểu đồ, vui lòng thử lại.',
                        true,
                        false
                    );
                });
            });
        }

        /* ---------- Chuyển tab ---------- */
        $(document).on('click', '.inv-tab', function() {
            var target = $(this).data('pane');

            $('.inv-tab').removeClass('is-active');
            $(this).addClass('is-active');

            $('.inv-pane').removeClass('is-active');
            $('#' + target).addClass('is-active');

            // DataTables đo sai bề rộng cột khi bảng bị ẩn lúc khởi tạo
            $.fn.dataTable.tables({
                visible: true,
                api: true
            }).columns.adjust();
        });
    });
</script>
