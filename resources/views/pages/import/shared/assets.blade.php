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
| - Bảng báo cáo           : id="impReportTable"
| - Nút chọn nhanh khoảng thời gian : trong .imp-quick, kèm data-from / data-to
| - Nút xem lịch sử điều chỉnh : class="btn-imp-history" kèm data-url / data-title
| - Modal lịch sử          : id="historyModal"
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
    .imp-code {
        font-weight: 700;
        color: var(--primary-dark);
        letter-spacing: 0.6px;
        white-space: nowrap;
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

    /* ---------- Bảng báo cáo ---------- */
    #impReportTable thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: none;
        vertical-align: middle;
    }

    #impReportTable tbody tr:hover {
        background: rgba(var(--primary-rgb), 0.04);
    }

    #impReportTable tfoot th {
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Bật tìm kiếm cho các ô chọn trong modal ---------- */
        $('.md-modal').each(function() {
            var $modal = $(this);

            $modal.find('.imp-select').select2({
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

        /* ---------- Bảng báo cáo nhập theo khoảng thời gian ---------- */
        $('#impReportTable').DataTable({
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
                emptyTable: 'Không có phiếu nhập nào trong khoảng thời gian đã chọn.',
                paginate: {
                    previous: 'Trước',
                    next: 'Sau'
                }
            }
        });

        /* ---------- Bảng hàng chờ nhận từ phòng ban khác ---------- */
        $('#impTransferTable').DataTable({
            autoWidth: false,
            responsive: true,
            pageLength: 25,
            order: [
                [5, 'desc']
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
                emptyTable: 'Không có hoá chất nào đang chờ nhận từ phòng ban khác.',
                paginate: {
                    previous: 'Trước',
                    next: 'Sau'
                }
            }
        });

        /* ---------- Mở modal Nhận hàng chuyển kho ---------- */
        $(document).on('click', '.btn-imp-receive', function() {
            var row = $(this).data('row') || {};
            var $form = $('#receiveModal').find('form');

            $form.find('[name="export_id"]').val(row.export_id);
            $form.find('.imp-rcv-from').text(row.from_department || '—');
            $form.find('.imp-rcv-code').text(row.source_code || '—');
            $form.find('.imp-rcv-chem').text((row.chem_name || '—') +
                (row.category_code ? ' (' + row.category_code + ')' : ''));
            $form.find('.imp-rcv-amount').text(row.amount || '—');
            $form.find('.imp-rcv-batch').text(row.batch_no || '—');
            $form.find('.imp-rcv-expired').text(row.expired_date || '—');
            $form.find('.imp-rcv-date').text(row.exported_date || '—');
            $form.find('.imp-rcv-by').text(row.exported_by || '—');

            // Định khu và ghi chú là của phòng nhận, không lấy theo lô bên phòng gửi
            $form.find('[name="location_id"]').val('').trigger('change');
            $form.find('[name="note"]').val('');
            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            $('#receiveModal').modal('show');
        });

        /* ---------- Mở modal Từ chối nhận ---------- */
        $(document).on('click', '.btn-imp-reject', function() {
            var $form = $('#rejectTransferModal').find('form');

            $form.find('[name="export_id"]').val($(this).data('id'));
            $form.find('[name="reject_reason"]').val('');
            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            $('#rejectTransferModal').find('.imp-reject-subtitle').text($(this).data('title') || '');
            $('#rejectTransferModal').modal('show');
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

            // Select2 chỉ vẽ lại khi có sự kiện change, .val() thôi là chưa đủ
            $form.find('.imp-select').each(function() {
                var field = $(this).attr('name');
                $(this).val(row[field] === undefined || row[field] === null ? '' : row[field]).trigger('change');
            });

            $form.find('.imp-switch input').each(function() {
                var checked = String(row[$(this).attr('name')]) === '1';
                $(this).prop('checked', checked).closest('.imp-switch').toggleClass('is-checked', checked);
            });

            // Lý do là của riêng từng lần điều chỉnh, không lấy lại của lần trước
            $form.find('[name="reason"]').val('').removeClass('is-invalid');
        });
    });
</script>
