{{--
|--------------------------------------------------------------------------
| SỬ DỤNG - CSS + JS dùng chung
|--------------------------------------------------------------------------
| Dùng lại toàn bộ giao diện md-* của nhóm Dữ Liệu Gốc (bảng, modal, nút thao tác,
| SweetAlert, DataTables) rồi bổ sung phần riêng của nhóm Sử Dụng:
| - Ô chọn Select2 có tìm kiếm, đặt trong modal
| - Ô mã xuất nhập chỉ đọc, lấy theo phiếu nhập đang chọn (không sinh mã mới)
| - Dòng nhắc tồn còn lại của phiếu nhập đang chọn
| - Ô chọn loại phiếu: Sử dụng / Huỷ bỏ
|
| Quy ước để phần JS bên dưới hoạt động:
| - Ô chọn 1 giá trị      : class="exp-select"
| - Ô hiện mã xuất nhập  : class="exp-code-view"
| - Dòng nhắc tồn         : class="exp-remaining"
| - Nguồn dữ liệu phiếu nhập: ô chọn [name="import_id"] kèm
|   data-imports='{"<import_id>":{"code":"...","remaining":0,"unit":"..."}}'
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

    /* ---------- Ô chỉ đọc (mã xuất nhập lấy từ phiếu nhập) ---------- */
    .md-modal .exp-readonly {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        font-weight: 700;
        letter-spacing: 1px;
        cursor: default;
    }

    .md-modal .exp-readonly:focus {
        box-shadow: none;
        border-color: var(--primary-lighter);
    }

    .md-modal .form-group small.md-sub {
        display: block;
        margin-top: 4px;
        font-size: 0.76rem;
    }

    /* ---------- Dòng nhắc tồn còn lại ---------- */
    .exp-remaining {
        display: block;
        margin-top: 4px;
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .exp-remaining.is-empty {
        color: #B91C1C;
    }

    /* ---------- Ô chọn loại phiếu ---------- */
    .exp-types {
        display: flex;
        gap: 8px;
    }

    .exp-type {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin: 0;
        padding: 9px 10px;
        border: 1px solid #dbe6f2;
        border-radius: var(--border-radius-md);
        background: #fff;
        font-weight: 500;
        cursor: pointer;
        transition: background-color var(--transition-fast), border-color var(--transition-fast);
    }

    .exp-type:hover {
        background: var(--primary-soft);
    }

    .exp-type.is-checked {
        background: var(--primary-soft);
        border-color: var(--primary);
        color: var(--primary-dark);
        font-weight: 700;
    }

    .exp-type input {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        accent-color: var(--primary);
        cursor: pointer;
    }

    /* ---------- Tab chuyển giữa Sổ sử dụng và Báo cáo ---------- */
    .exp-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--primary-soft);
    }

    .exp-tab {
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

    .exp-tab:hover {
        color: var(--primary-dark);
    }

    .exp-tab.is-active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .exp-pane {
        display: none;
    }

    .exp-pane.is-active {
        display: block;
    }

    /* Số đề nghị chờ trả lời, gắn cạnh tên tab */
    .exp-tab-count {
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

    /* ---------- Đề nghị chuyển hoá chất ---------- */
    .exp-req-title {
        margin: 0 0 12px;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .exp-req-title .md-sub {
        font-weight: 400;
    }

    .exp-req-table thead th {
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        white-space: nowrap;
    }

    .exp-req-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 2px 10px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .exp-req-badge.pending {
        background: #FEF3C7;
        color: #B45309;
        border: 1px solid #FCD34D;
    }

    .exp-req-badge.accepted {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }

    .exp-req-badge.rejected {
        background: #FEE2E2;
        color: #B91C1C;
        border: 1px solid #FCA5A5;
    }

    /* ---------- Thanh chọn khoảng thời gian của báo cáo ---------- */
    .exp-range {
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

    .exp-range .form-group {
        margin: 0;
    }

    .exp-range label {
        display: block;
        margin-bottom: 4px;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .exp-range input[type="date"] {
        min-width: 165px;
    }

    .exp-quick {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-left: auto;
    }

    .exp-quick button {
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

    .exp-quick button:hover {
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        transform: translateY(-1px);
    }

    /* ---------- Bảng báo cáo ---------- */
    #expReportTable thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: none;
        vertical-align: middle;
    }

    #expReportTable tbody tr:hover {
        background: rgba(var(--primary-rgb), 0.04);
    }

    #expReportTable tfoot th {
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-weight: 700;
    }

    .exp-kg {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    /* Không quy đổi được (đơn vị đếm, thiếu tỉ trọng) */
    .exp-kg-na {
        color: #B45309;
        font-weight: 700;
        cursor: help;
        border-bottom: 1px dashed #FCD34D;
    }

    /* ---------- Badge số lần điều chỉnh, gắn ở góc trên bên phải nút Sửa ---------- */
    .exp-btn-wrap {
        position: relative;
        display: inline-block;
    }

    .exp-count-badge {
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

    .exp-count-badge:hover {
        background: var(--primary-dark);
        transform: scale(1.12);
    }

    .exp-count-badge:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.25);
    }

    /* ---------- Modal lịch sử điều chỉnh ---------- */
    .exp-history-body {
        max-height: 62vh;
        overflow-y: auto;
        padding-right: 4px;
    }

    .exp-history-item {
        border: 1px solid var(--primary-soft);
        border-left: 3px solid var(--primary);
        border-radius: var(--border-radius-md);
        padding: 12px 14px;
        margin-bottom: 12px;
        background: #fff;
        transition: box-shadow var(--transition-fast);
    }

    .exp-history-item:hover {
        box-shadow: var(--shadow-sm);
    }

    .exp-history-item.create {
        border-left-color: #16A34A;
    }

    .exp-history-item.lock {
        border-left-color: #94A3B8;
    }

    .exp-history-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }

    .exp-history-action {
        font-weight: 700;
        color: var(--primary-dark);
        font-size: 0.9rem;
    }

    .exp-history-meta {
        color: #94a3b8;
        font-size: 0.78rem;
    }

    .exp-history-note {
        background: var(--primary-soft);
        border-radius: 6px;
        padding: 7px 10px;
        font-size: 0.83rem;
        color: var(--primary-dark);
        margin-bottom: 8px;
        word-break: break-word;
    }

    .exp-history-snapshot {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 4px 16px;
        font-size: 0.82rem;
        color: #64748b;
    }

    .exp-history-snapshot b {
        color: #475569;
        font-weight: 600;
    }

    .exp-history-empty {
        text-align: center;
        color: #94a3b8;
        padding: 30px 10px;
        font-size: 0.9rem;
    }

    /* ---------- Thông tin phụ trên bảng ---------- */
    .exp-code {
        font-weight: 700;
        color: var(--primary-dark);
        letter-spacing: 0.6px;
        white-space: nowrap;
    }

    .exp-amount {
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
    }

    .exp-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 1px 10px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .exp-badge-export {
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
    }

    .exp-badge-transfer {
        background: #EDE9FE;
        color: #6D28D9;
        border: 1px solid #C4B5FD;
    }

    /* Trạng thái nhận hàng của phiếu chuyển kho */
    .exp-received,
    .exp-pending {
        display: inline-block;
        margin-top: 3px;
        border-radius: 999px;
        padding: 0 9px;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
        cursor: help;
    }

    .exp-received {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }

    .exp-pending {
        background: #FEF3C7;
        color: #B45309;
        border: 1px solid #FCD34D;
    }

    .exp-badge-cancel {
        background: #FEE2E2;
        color: #B91C1C;
        border: 1px solid #FCA5A5;
    }

    /* ---------- Hoá chất chờ huỷ và các đợt xin quyết định huỷ ---------- */
    .dsp-waiting-table tbody tr.is-picked {
        background: var(--primary-soft);
    }

    .dsp-waiting-table input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: var(--primary);
        cursor: pointer;
    }

    .dsp-card {
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-lg);
        padding: 16px 18px;
        margin-bottom: 16px;
        background: #fff;
        box-shadow: var(--shadow-sm);
        transition: box-shadow var(--transition-fast);
    }

    .dsp-card:hover {
        box-shadow: var(--shadow-md);
    }

    .dsp-card.is-locked {
        opacity: 0.7;
        background: #FAFAFA;
    }

    .dsp-card-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .dsp-card-actions {
        display: flex;
        gap: 6px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .dsp-code {
        font-family: "Consolas", "Courier New", monospace;
        font-weight: 700;
        font-size: 0.98rem;
        color: var(--primary-dark);
    }

    .dsp-status {
        display: inline-block;
        margin-left: 8px;
        border-radius: 999px;
        padding: 1px 11px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .dsp-status.draft {
        background: #F1F5F9;
        color: #475569;
        border: 1px solid #CBD5E1;
    }

    .dsp-status.pending {
        background: #FEF3C7;
        color: #B45309;
        border: 1px solid #FCD34D;
    }

    .dsp-status.approved {
        background: #DBEAFE;
        color: #1D4ED8;
        border: 1px solid #93C5FD;
    }

    .dsp-status.rejected {
        background: #FEE2E2;
        color: #B91C1C;
        border: 1px solid #FCA5A5;
    }

    .dsp-status.done {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }

    .dsp-item-table {
        margin-bottom: 8px;
    }

    .dsp-item-table thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 0.78rem;
    }

    .dsp-card-foot {
        border-top: 1px dashed var(--primary-soft);
        padding-top: 8px;
    }

    .dsp-reject {
        background: #FEF2F2;
        border: 1px solid #FCA5A5;
        border-radius: var(--border-radius-md);
        color: #B91C1C;
        padding: 9px 12px;
        margin-bottom: 12px;
        font-size: 0.85rem;
    }

    .dsp-empty,
    .dsp-empty-items {
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md);
        padding: 18px;
        text-align: center;
        color: #64748b;
        font-size: 0.88rem;
    }

    .dsp-empty-items {
        padding: 12px;
        margin-bottom: 8px;
    }

    /* Hộp tóm tắt số phiếu đang chọn trong modal xin quyết định huỷ */
    .dsp-picked-box {
        background: var(--primary-soft);
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        padding: 11px 14px;
        margin-bottom: 16px;
        font-size: 0.88rem;
        color: var(--primary-dark);
    }

    .dsp-picked-list {
        max-height: 130px;
        overflow-y: auto;
    }

    .dsp-picked-list div {
        line-height: 1.6;
    }

    .dsp-section {
        margin: 4px 0 12px;
        padding-bottom: 6px;
        border-bottom: 1px solid var(--primary-soft);
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--primary-dark);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Bật tìm kiếm cho các ô chọn trong modal ---------- */
        $('.md-modal').each(function() {
            var $modal = $(this);

            $modal.find('.exp-select').select2({
                theme: 'bootstrap4',
                dropdownParent: $modal,
                width: '100%',
                placeholder: '-- Chọn --',
                language: {
                    noResults: function() {
                        return 'Không tìm thấy dữ liệu phù hợp';
                    }
                }
            });
        });

        /* ---------- Tô nền ô chọn loại phiếu ---------- */
        function paintTypes($form) {
            $form.find('.exp-type').each(function() {
                $(this).toggleClass('is-checked', $(this).find('input').prop('checked'));
            });
        }

        /** Ô "Phòng ban nhận" chỉ dùng cho loại Chuyển kho, loại khác thì ẩn và xoá trắng */
        function toggleTransfer($form) {
            var isTransfer = $form.find('.exp-type input:checked').val() === 'transfer';

            $form.find('.exp-transfer-only').toggle(isTransfer);

            var $dept = $form.find('[name="to_department_id"]');

            if (!isTransfer && $dept.val()) {
                $dept.val('').trigger('change');
            }
        }

        /** Ô "Số PKN, OOS, BCSL" chỉ dùng cho loại Huỷ bỏ - đó là căn cứ loại bỏ */
        function toggleCancel($form) {
            var isCancel = $form.find('.exp-type input:checked').val() === 'cancel';

            $form.find('.exp-cancel-only').toggle(isCancel);

            if (!isCancel) {
                $form.find('[name="test_report_no"]').val('');
            }
        }

        $(document).on('change', '.exp-type input', function() {
            var $form = $(this).closest('form');

            paintTypes($form);
            toggleTransfer($form);
            toggleCancel($form);

            // Chuyển kho không được vượt tồn, hạn mức trên ô số lượng phải tính lại
            $form.find('[name="import_id"]').each(function() {
                syncImport($(this));
            });
        });

        /* ---------- Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 ---------- */
        function trimNum(value) {
            return String(Number(value).toFixed(4)).replace(/\.?0+$/, '');
        }

        /* ---------- Mã xuất nhập + tồn còn lại đổi theo phiếu nhập đang chọn ---------- */
        function syncImport($select) {
            var $form = $select.closest('form');
            var imports = $select.data('imports') || {};
            var picked = imports[$select.val()];

            var $code = $form.find('.exp-code-view');
            var $remaining = $form.find('.exp-remaining');

            $code.val(picked ? picked.code : ($code.data('placeholder') || ''));

            if (!$remaining.length) return;

            if (!picked) {
                $remaining.text('').removeClass('is-empty');
                return;
            }

            // Cộng lại số lượng của chính phiếu đang sửa, phần đó chưa bị coi là đã dùng
            var extra = Number($remaining.data('self-import')) === Number($select.val()) ?
                Number($remaining.data('self-amount') || 0) : 0;
            var left = Number(picked.remaining || 0) + extra;

            // Hạn mức xuất = tồn còn lại + phần được phép vượt (mặc định 5%).
            // Riêng Chuyển kho không được vượt tồn, hàng chuyển đi thành tồn phòng nhận.
            var isTransfer = $form.find('.exp-type input:checked').val() === 'transfer';
            var over = isTransfer ? 0 : Number($select.data('over') || 0);
            var limit = left * (1 + over);

            $remaining
                .text('Tồn còn lại: ' + trimNum(left) + ' ' + (picked.unit || '') +
                    (over > 0 ? ' - xuất tối đa ' + trimNum(limit) :
                        (isTransfer ? ' - chuyển kho không được vượt tồn' : '')))
                .toggleClass('is-empty', left <= 0);

            // Chặn ngay trên form, Controller vẫn kiểm tra lại khi lưu
            $form.find('[name="amount"]').attr('max', limit > 0 ? trimNum(limit) : null);
        }

        $(document).on('change', '.md-modal [name="import_id"]', function() {
            syncImport($(this));
        });

        /* ---------- Xoá trắng ô chọn khi mở modal Thêm mới ---------- */
        $(document).on('click', '.btn-md-create', function() {
            var $form = $('#createModal').find('form');

            $form.find('.exp-select').val('').trigger('change');

            // Mặc định là phiếu Sử dụng, tránh giữ lại lựa chọn Huỷ bỏ của lần mở trước
            $form.find('.exp-type input').each(function(i) {
                $(this).prop('checked', i === 0);
            });
            paintTypes($form);
            toggleTransfer($form);
            toggleCancel($form);
        });

        /* ---------- Đổ dữ liệu vào ô chọn khi mở modal Cập nhật ---------- */
        $(document).on('click', '.btn-md-edit', function() {
            var row = $(this).data('row') || {};
            var $form = $('#updateModal').find('form');

            // Phần tồn phải bỏ qua chính phiếu đang sửa, giống cách Controller kiểm tra
            $form.find('.exp-remaining')
                .data('self-import', row.import_id)
                .data('self-amount', row.amount);

            $form.find('.exp-type input').each(function() {
                $(this).prop('checked', String(row.type) === $(this).val());
            });
            paintTypes($form);
            toggleTransfer($form);
            toggleCancel($form);

            // Căn cứ loại bỏ nằm ngoài nhóm .exp-select nên phải đổ tay
            $form.find('[name="test_report_no"]').val(row.test_report_no || '');

            // Select2 chỉ vẽ lại khi có sự kiện change, .val() thôi là chưa đủ
            $form.find('.exp-select').each(function() {
                var field = $(this).attr('name');
                $(this).val(row[field] === undefined || row[field] === null ? '' : row[field]).trigger('change');
            });

            // Lý do điều chỉnh chỉ dành cho lần sửa này, không lấy theo dữ liệu dòng
            $form.find('[name="adjust_reason"]').val('');
        });

        /* ---------- Bảng báo cáo sử dụng theo khoảng thời gian ---------- */
        $('#expReportTable').DataTable({
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
                emptyTable: 'Không có phiếu sử dụng nào trong khoảng thời gian đã chọn.',
                paginate: {
                    previous: 'Trước',
                    next: 'Sau'
                }
            }
        });

        /* ---------- Xem lịch sử điều chỉnh ---------- */
        $(document).on('click', '.btn-exp-history', function() {
            var url = $(this).data('url');

            $('#historyModal').find('.exp-history-subtitle').text($(this).data('title') || '');
            $('#historyModal').find('.exp-history-body').html(
                '<div class="exp-history-empty"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải lịch sử...</div>'
            );
            $('#historyModal').modal('show');

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
                    renderHistory(data.rows || []);
                })
                .catch(function() {
                    $('#historyModal').find('.exp-history-body').html(
                        '<div class="exp-history-empty">Không tải được lịch sử điều chỉnh. Vui lòng thử lại.</div>'
                    );
                });
        });

        /* Dựng danh sách lịch sử bằng thao tác DOM để nội dung dữ liệu luôn được escape */
        function renderHistory(rows) {
            var $body = $('#historyModal').find('.exp-history-body').empty();

            if (!rows.length) {
                $body.html('<div class="exp-history-empty">Phiếu này chưa có lần điều chỉnh nào được lưu.</div>');
                return;
            }

            var themes = {
                'Thêm mới': 'create',
                'Khoá': 'lock',
                'Mở khoá': 'lock'
            };

            rows.forEach(function(row) {
                var $item = $('<div>').addClass('exp-history-item ' + (themes[row.action] || ''));

                $('<div>').addClass('exp-history-head')
                    .append($('<span>').addClass('exp-history-action').text(row.action))
                    .append($('<span>').addClass('exp-history-meta').text(row.created_by + ' · ' + row.created_at))
                    .appendTo($item);

                if (row.change_note) {
                    $('<div>').addClass('exp-history-note').text(row.change_note).appendTo($item);
                }

                var $snapshot = $('<div>').addClass('exp-history-snapshot');

                Object.keys(row.snapshot || {}).forEach(function(field) {
                    $('<div>')
                        .append($('<b>').text(field + ': '))
                        .append(document.createTextNode(row.snapshot[field]))
                        .appendTo($snapshot);
                });

                $snapshot.appendTo($item);
                $body.append($item);
            });
        }

        /* ---------- Trả lời đề nghị chuyển hoá chất ---------- */
        $(document).on('click', '.btn-exp-respond', function() {
            var accepted = $(this).data('answer') === 'accepted';
            var $form = $('#respondModal').find('form');

            $form.find('[name="id"]').val($(this).data('id'));
            $form.find('[name="app_status"]').val($(this).data('answer'));
            $form.find('[name="response_note"]').val('');
            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            $('#respondModal').find('.exp-respond-heading')
                .text(accepted ? 'Đồng Ý Đề Nghị Chuyển' : 'Từ Chối Đề Nghị Chuyển');
            $('#respondModal').find('.exp-respond-subtitle').text($(this).data('title') || '');

            // Từ chối thì bắt buộc nói lý do, Controller cũng kiểm tra lại
            $form.find('.exp-respond-label').text(accepted ? 'Ghi Chú Trả Lời' : 'Lý Do Từ Chối');
            $form.find('.exp-respond-required').toggle(!accepted);
            $form.find('[name="response_note"]').prop('required', !accepted);
            $form.find('.exp-respond-hint').text(accepted ?
                'Đồng ý mới là trả lời, hàng chưa đi. Sau đó bấm "Lập phiếu" để lập phiếu Chuyển kho.' :
                'Nêu rõ vì sao không chuyển được để phòng kia biết đường xoay.');
            $form.find('.exp-respond-submit')
                .toggleClass('btn-primary', accepted)
                .toggleClass('btn-danger', !accepted);

            $('#respondModal').modal('show');
        });

        /* ---------- Lập phiếu Chuyển kho từ một đề nghị đã đồng ý ---------- */
        $(document).on('click', '.btn-exp-make-transfer', function() {
            var req = $(this).data('request') || {};
            var $form = $('#createModal').find('form');

            // Mở modal Thêm mới rồi điền sẵn phần lấy được từ đề nghị; mã lô cụ thể
            // vẫn do người dùng chọn vì đề nghị chỉ nói danh mục hoá chất
            $('.btn-md-create').first().trigger('click');

            $form.find('.exp-type input[value="transfer"]').prop('checked', true).trigger('change');
            $form.find('[name="to_department_id"]').val(req.to_department_id).trigger('change');
            $form.find('[name="amount"]').val(req.amount);
            $form.find('[name="purpose"]').val(req.purpose || '');

            // Gắn kèm id đề nghị để Controller nối phiếu chuyển với đề nghị
            $form.find('[name="request_id"]').remove();
            $('<input>').attr({
                type: 'hidden',
                name: 'request_id',
                value: req.request_id
            }).appendTo($form);
        });

        /* ---------- Chuyển tab ---------- */
        $(document).on('click', '.exp-tab', function() {
            var target = $(this).data('pane');

            $('.exp-tab').removeClass('is-active');
            $(this).addClass('is-active');

            $('.exp-pane').removeClass('is-active');
            $('#' + target).addClass('is-active');

            // DataTables đo sai bề rộng cột khi bảng bị ẩn lúc khởi tạo
            $.fn.dataTable.tables({
                visible: true,
                api: true
            }).columns.adjust();
        });

        /* ---------- Nút chọn nhanh khoảng thời gian ---------- */
        $(document).on('click', '.exp-quick button', function() {
            var $form = $(this).closest('form');

            $form.find('[name="from"]').val($(this).data('from'));
            $form.find('[name="to"]').val($(this).data('to'));
            $form.trigger('submit');
        });

        /* ---------- Trạng thái ban đầu khi form mở lại kèm lỗi validate ---------- */
        $('.md-modal form').each(function() {
            paintTypes($(this));
            toggleTransfer($(this));
            toggleCancel($(this));
            $(this).find('[name="import_id"]').each(function() {
                if ($(this).val()) syncImport($(this));
            });
        });

        /* ==========================================================
        | HOÁ CHẤT CHỜ HUỶ - BƯỚC 2 CỦA NGHIỆP VỤ HUỶ BỎ
        ========================================================== */

        /** Các phiếu loại bỏ đang được tích chọn ở bảng "Hoá chất chờ huỷ". */
        function dspPicked() {
            return $('.dsp-pick:checked');
        }

        /** Cập nhật số đếm trên nút và bật / tắt nút Xin quyết định huỷ. */
        function dspSync() {
            var count = dspPicked().length;

            $('.dsp-picked').text(count);
            $('#btnDspCreate').prop('disabled', count === 0);

            $('.dsp-pick').each(function() {
                $(this).closest('tr').toggleClass('is-picked', $(this).prop('checked'));
            });

            // Ô chọn tất cả chỉ tích khi đã chọn hết
            var all = $('.dsp-pick').length;
            $('#dspCheckAll').prop('checked', all > 0 && count === all);
        }

        $(document).on('change', '.dsp-pick', dspSync);

        $(document).on('change', '#dspCheckAll', function() {
            $('.dsp-pick').prop('checked', $(this).prop('checked'));
            dspSync();
        });

        dspSync();

        /* ---------- Mở modal lập đợt, mang theo các phiếu đang chọn ---------- */
        $(document).on('click', '#btnDspCreate', function() {
            var $picked = dspPicked();

            if (!$picked.length) return;

            var $inputs = $('#disposalModal').find('.dsp-picked-inputs').empty();
            var $list = $('#disposalModal').find('.dsp-picked-list').empty();

            $picked.each(function() {
                var $row = $(this).closest('tr');

                $('<input>').attr({
                    type: 'hidden',
                    name: 'export_ids[]',
                    value: $(this).val()
                }).appendTo($inputs);

                // Dựng bằng thao tác DOM để nội dung dữ liệu luôn được escape
                $('<div>').text(
                    $row.find('td').eq(2).text().trim() + ' · ' +
                    $row.find('td').eq(3).find('.font-weight-bold').text().trim() + ' · ' +
                    $row.find('td').eq(6).text().trim().replace(/\s+/g, ' ')
                ).appendTo($list);
            });

            $('#disposalModal').modal('show');
        });

        /* ---------- Thêm phiếu đang chọn vào một đợt còn đang gom ---------- */
        $(document).on('submit', '.form-dsp-add', function(e) {
            var $picked = dspPicked();
            var $form = $(this);

            if (!$picked.length) {
                e.preventDefault();

                Swal.fire({
                    icon: 'info',
                    title: 'Chưa chọn phiếu nào',
                    text: 'Tích chọn hoá chất ở bảng "Hoá chất chờ huỷ" rồi bấm Thêm phiếu đã chọn.',
                    confirmButtonText: 'Đã hiểu'
                });

                return;
            }

            $form.find('input[name="export_ids[]"]').remove();

            $picked.each(function() {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'export_ids[]',
                    value: $(this).val()
                }).appendTo($form);
            });
        });

        /* ---------- Sửa thông tin đợt đang gom ---------- */
        $(document).on('click', '.btn-dsp-edit', function() {
            var batch = $(this).data('batch') || {};
            var $form = $('#disposalEditModal').find('form');

            $('#disposalEditModal').find('.dsp-edit-code').text(batch.code || '');

            $form.find('[name="id"]').val(batch.id);
            $form.find('[name="period_month"]').val(batch.period_month);
            $form.find('[name="period_year"]').val(batch.period_year);
            $form.find('[name="summarized_at"]').val(batch.summarized_at || '');
            $form.find('[name="checked_at"]').val(batch.checked_at || '');

            $form.find('[name="summarized_by"]').val(batch.summarized_by || '').trigger('change');
            $form.find('[name="chemical_staff"]').val(batch.chemical_staff || '').trigger('change');

            $('#disposalEditModal').modal('show');
        });

        /* ---------- Ghi quyết định huỷ / không duyệt ---------- */
        $(document).on('click', '.btn-dsp-decide', function() {
            var approved = $(this).data('answer') === 'approved';
            var $modal = $('#disposalDecideModal');
            var $form = $modal.find('form');

            $form.find('[name="id"]').val($(this).data('id'));
            $form.find('[name="app_status"]').val($(this).data('answer'));

            $modal.find('.dsp-decide-code').text($(this).data('code') || '');
            $modal.find('.dsp-decide-heading')
                .text(approved ? 'Quyết Định Huỷ Bỏ' : 'Không Duyệt Đợt Huỷ');

            // Duyệt thì cần số quyết định và hai chữ ký, không duyệt thì chỉ cần lý do
            $modal.find('.dsp-approve-only').toggle(approved);
            $modal.find('.dsp-reject-only').toggle(!approved);
            $modal.find('.dsp-note-label').text(approved ? 'Ghi Chú Khác' : 'Ghi Chú');

            $form.find('.dsp-approve-only [name="decision_no"], .dsp-approve-only [name="qa_approved_by"], .dsp-approve-only [name="qa_approved_at"], .dsp-approve-only [name="director_approved_by"], .dsp-approve-only [name="director_approved_at"]')
                .prop('required', approved);
            $form.find('[name="reject_reason"]').prop('required', !approved);

            $form.find('.dsp-decide-submit')
                .toggleClass('btn-primary', approved)
                .toggleClass('btn-danger', !approved)
                .html(approved ?
                    '<i class="fas fa-save mr-1"></i> Lưu quyết định huỷ' :
                    '<i class="fas fa-xmark mr-1"></i> Ghi không duyệt');

            $modal.modal('show');
        });

        /** Chọn "Đơn vị khác" thì mới hỏi tên đơn vị thực hiện huỷ */
        function dspToggleExecutor() {
            var $select = $('#disposalDecideModal').find('[name="executor_type"]');

            $('#disposalDecideModal').find('.dsp-executor-other').toggle($select.val() === 'other');
        }

        $(document).on('change', '#disposalDecideModal [name="executor_type"]', dspToggleExecutor);
        dspToggleExecutor();

        /* ---------- Giao nhận phế phẩm và theo dõi huỷ ---------- */
        $(document).on('click', '.btn-dsp-complete', function() {
            var batch = $(this).data('batch') || {};
            var $modal = $('#disposalCompleteModal');
            var $form = $modal.find('form');

            $modal.find('.dsp-done-code').text(batch.code || '');

            ['id', 'solid_weight', 'liquid_weight', 'handover_date', 'handover_by',
                'receive_date', 'receive_by', 'label_date', 'label_by', 'destroy_date', 'destroy_by'
            ].forEach(function(field) {
                $form.find('[name="' + field + '"]').val(
                    batch[field] === undefined || batch[field] === null ? '' : batch[field]
                );
            });

            // Gợi ý theo phần quy đổi được ở mục 1, người dùng vẫn cân thực tế rồi sửa lại
            $modal.find('.dsp-suggest').text(
                batch.suggest_kg > 0 ?
                'Hệ thống quy đổi từ danh mục mục 1: ' + batch.suggest_kg + ' kg (rắn + lỏng).' :
                ''
            );

            $modal.modal('show');
        });
    });
</script>
