{{--
|--------------------------------------------------------------------------
| DANH MỤC - CSS + JS dùng chung
|--------------------------------------------------------------------------
| Dùng lại toàn bộ giao diện md-* của nhóm Dữ Liệu Gốc (bảng, modal, nút thao tác,
| SweetAlert, DataTables) rồi bổ sung phần riêng của nhóm Danh Mục:
| - Ô chọn Select2 có tìm kiếm, đặt trong modal
| - Thẻ mã phân loại hoá chất
| - Modal xem lịch sử thay đổi
|
| Quy ước để phần JS bên dưới hoạt động:
| - Ô chọn 1 giá trị       : class="cat-select"
| - Nhóm tick chọn nhiều   : class="cat-check-group", input class="cat-check-input", name="<tên>[]"
| - Nút xem lịch sử        : class="btn-cat-history" kèm data-url / data-title
| - Trang nhiều tab        : nút thêm/sửa kèm data-modal="#idModalCủaTabĐó"
|
| Bọc trong @once nên @include nhiều lần trên một trang vẫn chỉ in ra một bản.
--}}

@once

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

    /* ---------- Nhóm tick chọn Phân Loại (nhiều nhóm cùng lúc) ---------- */
    .cat-check-group {
        display: flex;
        flex-direction: column;
        gap: 1px;
        overflow-y: auto;
        border: 1px solid #dbe6f2;
        border-radius: var(--border-radius-md);
        padding: 6px;
        background: #fff;
    }

    .cat-check-group.is-invalid {
        border-color: #dc3545;
    }

    .cat-check-item {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        margin: 0;
        padding: 7px 8px;
        border-radius: 6px;
        font-weight: 400;
        cursor: pointer;
        transition: background-color var(--transition-fast);
    }

    .cat-check-item:hover {
        background: var(--primary-soft);
    }

    .cat-check-item.is-checked {
        background: var(--primary-soft);
    }

    .cat-check-input {
        width: 17px;
        height: 17px;
        margin-top: 2px;
        flex-shrink: 0;
        accent-color: var(--primary);
        cursor: pointer;
    }

    .cat-check-code {
        flex-shrink: 0;
        min-width: 42px;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .cat-check-name {
        color: var(--text-main);
        font-size: 0.85rem;
        line-height: 1.4;
    }

    /* ---------- Ô chỉ đọc (mã sinh tự động) ---------- */
    .md-modal .cat-readonly {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        font-weight: 700;
        letter-spacing: 1px;
        cursor: default;
    }

    .md-modal .cat-readonly:focus {
        box-shadow: none;
        border-color: var(--primary-lighter);
    }

    .md-modal .form-group small.md-sub {
        display: block;
        margin-top: 4px;
        font-size: 0.76rem;
    }

    /* ---------- Thẻ mã phân loại ---------- */
    .cat-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    .cat-chip {
        display: inline-block;
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
        border-radius: 999px;
        padding: 1px 9px;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        cursor: help;
    }

    .cat-chip.danger {
        background: #FEE2E2;
        color: #B91C1C;
        border-color: #FCA5A5;
    }

    /* Phòng ban đang dùng - tách màu khỏi chip phân loại để không đọc nhầm hai cột */
    .cat-chip.dept {
        background: #DCFCE7;
        color: #15803D;
        border-color: #86EFAC;
    }

    /* ---------- Badge số lần thay đổi, gắn ở góc trên bên phải nút Sửa ---------- */
    .cat-btn-wrap {
        position: relative;
        display: inline-block;
    }

    .cat-count-badge {
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

    .cat-count-badge:hover {
        background: var(--primary-dark);
        transform: scale(1.12);
    }

    .cat-count-badge:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.25);
    }

    /* ---------- Logo cảnh báo an toàn (kiểu GHS) ---------- */
    .safety-picto {
        flex-shrink: 0;
        display: block;
    }

    /* Ô tick "Cảnh Báo An Toàn": giống .cat-check-item nhưng có thêm logo trước mã */
    .cat-check-item.has-picto {
        align-items: center;
    }

    .cat-check-item.has-picto .safety-picto {
        margin-right: 2px;
    }

    /* Thẻ hiển thị ở bảng danh mục / lịch sử: logo + tên cảnh báo trên một dòng */
    .safety-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #FEE2E2;
        color: #B91C1C;
        border: 1px solid #FCA5A5;
        border-radius: 999px;
        padding: 1px 9px 1px 4px;
        font-size: 0.74rem;
        font-weight: 700;
        white-space: nowrap;
        cursor: help;
    }

    /* ---------- Modal lịch sử ---------- */
    .cat-history-body {
        max-height: 62vh;
        overflow-y: auto;
        padding-right: 4px;
    }

    .cat-history-item {
        border: 1px solid var(--primary-soft);
        border-left: 3px solid var(--primary);
        border-radius: var(--border-radius-md);
        padding: 12px 14px;
        margin-bottom: 12px;
        background: #fff;
        transition: box-shadow var(--transition-fast);
    }

    .cat-history-item:hover {
        box-shadow: var(--shadow-sm);
    }

    .cat-history-item.create {
        border-left-color: #16A34A;
    }

    .cat-history-item.lock {
        border-left-color: #94A3B8;
    }

    .cat-history-item.approve {
        border-left-color: #17B8D4;
    }

    .cat-history-item.reject {
        border-left-color: #DC2626;
    }

    .cat-history-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }

    .cat-history-action {
        font-weight: 700;
        color: var(--primary-dark);
        font-size: 0.9rem;
    }

    .cat-history-meta {
        color: #94a3b8;
        font-size: 0.78rem;
    }

    .cat-history-note {
        background: var(--primary-soft);
        border-radius: 6px;
        padding: 7px 10px;
        font-size: 0.83rem;
        color: var(--primary-dark);
        margin-bottom: 8px;
        word-break: break-word;
    }

    .cat-history-snapshot {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 4px 16px;
        font-size: 0.82rem;
        color: #64748b;
    }

    .cat-history-snapshot b {
        color: #475569;
        font-weight: 600;
    }

    .cat-history-empty {
        text-align: center;
        color: #94a3b8;
        padding: 30px 10px;
        font-size: 0.9rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Bật tìm kiếm cho các ô chọn trong modal ---------- */
        $('.md-modal').each(function() {
            var $modal = $(this);

            $modal.find('.cat-select').select2({
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

        /* ---------- Tô nền dòng đang tick trong nhóm Phân Loại ---------- */
        $(document).on('change', '.cat-check-input', function() {
            if (this.type === 'radio') {
                $(this).closest('.cat-check-group').find('.cat-check-item').removeClass('is-checked');
                if (this.checked) {
                    $(this).closest('.cat-check-item').addClass('is-checked');
                }
            } else {
                $(this).closest('.cat-check-item').toggleClass('is-checked', this.checked);
            }
        });

        /* ---------- Xoá trắng ô chọn khi mở modal Thêm mới ---------- */
        $(document).on('click', '.btn-md-create', function() {
            var $form = $($(this).data('modal') || '#createModal').find('form');

            $form.find('.cat-select').val('').trigger('change');
            $form.find('.cat-check-input').prop('checked', false)
                .closest('.cat-check-item').removeClass('is-checked');
        });

        /* ---------- Đổ dữ liệu vào ô chọn khi mở modal Cập nhật ---------- */
        $(document).on('click', '.btn-md-edit', function() {
            var row = $(this).data('row') || {};
            var $form = $($(this).data('modal') || '#updateModal').find('form');

            // Select2 chỉ vẽ lại khi có sự kiện change, .val() thôi là chưa đủ
            $form.find('.cat-select').each(function() {
                var field = $(this).attr('name');
                $(this).val(row[field] === undefined || row[field] === null ? '' : row[field]).trigger('change');
            });

            $form.find('.cat-check-input').each(function() {
                var field = $(this).attr('name').replace('[]', '');
                var selected = Array.isArray(row[field]) ? row[field] : [];
                var checked = selected.indexOf($(this).val()) !== -1;

                $(this).prop('checked', checked).closest('.cat-check-item').toggleClass('is-checked', checked);
            });
        });

        /* ---------- Xem lịch sử thay đổi ---------- */
        $(document).on('click', '.btn-cat-history', function() {
            var url = $(this).data('url');

            $('#historyModal').find('.cat-history-subtitle').text($(this).data('title') || '');
            $('#historyModal').find('.cat-history-body').html(
                '<div class="cat-history-empty"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải lịch sử...</div>'
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
                    $('#historyModal').find('.cat-history-body').html(
                        '<div class="cat-history-empty">Không tải được lịch sử thay đổi. Vui lòng thử lại.</div>'
                    );
                });
        });

        /* Dựng danh sách lịch sử bằng thao tác DOM để nội dung dữ liệu luôn được escape */
        function renderHistory(rows) {
            var $body = $('#historyModal').find('.cat-history-body').empty();

            if (!rows.length) {
                $body.html('<div class="cat-history-empty">Bản ghi này chưa có lần thay đổi nào được lưu.</div>');
                return;
            }

            var themes = {
                'Thêm mới': 'create',
                'Khoá': 'lock',
                'Mở khoá': 'lock',
                'Phê duyệt': 'approve',
                'Từ chối duyệt': 'reject'
            };

            rows.forEach(function(row) {
                var $item = $('<div>').addClass('cat-history-item ' + (themes[row.action] || ''));

                $('<div>').addClass('cat-history-head')
                    .append($('<span>').addClass('cat-history-action').text(row.action))
                    .append($('<span>').addClass('cat-history-meta').text(row.created_by + ' · ' + row.created_at))
                    .appendTo($item);

                if (row.change_note) {
                    $('<div>').addClass('cat-history-note').text(row.change_note).appendTo($item);
                }

                var $snapshot = $('<div>').addClass('cat-history-snapshot');

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
    });
</script>

@endonce
