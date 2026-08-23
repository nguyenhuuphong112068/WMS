{{--
|--------------------------------------------------------------------------
| DỰ TRÙ - CSS + JS dùng chung
|--------------------------------------------------------------------------
| Dùng lại toàn bộ giao diện md-* của nhóm Dữ Liệu Gốc (bảng, modal, nút thao tác,
| SweetAlert, DataTables) rồi bổ sung phần riêng của nhóm Dự Trù:
| - Thanh theo dõi trình ký 2 bước hiển thị ngay trên danh sách
| - Ô chọn Select2 có tìm kiếm, đặt trong modal
| - Khối khai số lượng theo từng tháng (thêm / bớt dòng)
| - Modal xem nhật ký trình ký
|
| Quy ước để phần JS bên dưới hoạt động:
| - Ô chọn 1 giá trị           : class="est-select"
| - Chọn nguồn hoá chất        : radio name="source" (category | manual)
|                                khối tương ứng class="est-source-category" / "est-source-manual"
| - Khối số lượng theo tháng   : class="est-amounts" + <template class="est-amount-template">
| - Nút thêm / bớt dòng        : class="btn-est-amount-add" / "btn-est-amount-remove"
| - Sửa mặt hàng               : class="btn-est-item-edit" kèm data-row='{...}' -> #itemUpdateModal
| - Xem nhật ký trình ký       : class="btn-est-history" kèm data-url / data-title
| - Từ chối phiếu              : class="btn-est-reject" kèm data-id / data-code
| - Tiếp nhận / hoàn tất       : class="btn-est-reception" kèm data-id / data-code / data-action / data-mode
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

    .select2-container--open {
        z-index: 1061;
    }

    /* ---------- Ô chỉ đọc (mã phiếu sinh tự động) ---------- */
    .md-modal .est-readonly {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        font-weight: 700;
        letter-spacing: 1px;
        cursor: default;
    }

    .md-modal .est-readonly:focus {
        box-shadow: none;
        border-color: var(--primary-lighter);
    }

    .md-modal .form-group small.md-sub {
        display: block;
        margin-top: 4px;
        font-size: 0.76rem;
    }

    /* ---------- Mã phiếu / kỳ dự trù trên bảng ---------- */
    .est-code {
        font-weight: 700;
        color: var(--primary-dark);
        letter-spacing: 0.6px;
        white-space: nowrap;
    }

    .est-period {
        display: inline-block;
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
        border-radius: 8px;
        padding: 3px 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    /* ---------- Thanh theo dõi trình ký ---------- */
    .est-flow {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: nowrap;
    }

    .est-step {
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 3px 11px 3px 4px;
        border: 1px solid #e2e8f0;
        background: #fff;
        white-space: nowrap;
    }

    .est-step .no {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .est-step .txt {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
    }

    .est-step .txt b {
        font-size: 0.76rem;
        color: #475569;
        font-weight: 700;
    }

    .est-step .txt span {
        font-size: 0.68rem;
        color: #94a3b8;
    }

    /* bước đã ký */
    .est-step.is-done {
        border-color: #86EFAC;
        background: #F0FDF4;
    }

    .est-step.is-done .no {
        background: #16A34A;
        color: #fff;
    }

    .est-step.is-done .txt b {
        color: #15803D;
    }

    /* bước đang chờ ký */
    .est-step.is-current {
        border-color: #FCD34D;
        background: #FFFBEB;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
    }

    .est-step.is-current .no {
        background: #F59E0B;
        color: #fff;
    }

    .est-step.is-current .txt b {
        color: #B45309;
    }

    /* bước bị từ chối */
    .est-step.is-rejected {
        border-color: #FCA5A5;
        background: #FEF2F2;
    }

    .est-step.is-rejected .no {
        background: #DC2626;
        color: #fff;
    }

    .est-step.is-rejected .txt b {
        color: #B91C1C;
    }

    .est-flow .sep {
        color: #cbd5e1;
        font-size: 0.7rem;
    }

    /* ---------- Nhãn trạng thái tiếp nhận ---------- */
    .est-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 3px 11px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .est-badge.waiting {
        background: #FEF3C7;
        color: #B45309;
        border: 1px solid #FCD34D;
    }

    .est-badge.received {
        background: #E0F2FE;
        color: #0369A1;
        border: 1px solid #7DD3FC;
    }

    .est-badge.completed {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }

    .est-badge.none {
        background: #F1F5F9;
        color: #94A3B8;
        border: 1px solid #E2E8F0;
    }

    /* ---------- Ô chọn nguồn hoá chất (trong danh mục / tự nhập) ---------- */
    .est-switch {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        margin: 0 8px 0 0;
        padding: 9px 12px;
        border: 1px solid #dbe6f2;
        border-radius: var(--border-radius-md);
        background: #fff;
        font-weight: 400;
        cursor: pointer;
        transition: background-color var(--transition-fast);
    }

    .est-switch:hover,
    .est-switch.is-checked {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
    }

    .est-switch input {
        width: 17px;
        height: 17px;
        flex-shrink: 0;
        accent-color: var(--primary);
        cursor: pointer;
    }

    /* ---------- Khối khai số lượng theo tháng ---------- */
    .est-amounts {
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: var(--bg-neutral);
        padding: 12px 12px 4px;
    }

    .est-amount-row {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        margin-bottom: 8px;
    }

    .est-amount-row .col-amount {
        flex: 1 1 30%;
    }

    .est-amount-row .col-unit {
        flex: 1 1 30%;
    }

    .est-amount-row .col-period {
        flex: 1 1 30%;
    }

    .est-amount-row .col-remove {
        flex: 0 0 40px;
        padding-top: 1px;
    }

    .est-amount-row .btn-est-amount-remove {
        border-radius: var(--border-radius-md);
        transition: all var(--transition-fast);
    }

    .est-amount-row .btn-est-amount-remove:hover {
        transform: translateY(-1px);
    }

    .est-amount-head {
        display: flex;
        gap: 8px;
        margin-bottom: 6px;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .est-amount-head .col-amount,
    .est-amount-head .col-unit,
    .est-amount-head .col-period {
        flex: 1 1 30%;
    }

    .est-amount-head .col-remove {
        flex: 0 0 40px;
    }

    /* ---------- Chip số lượng theo tháng trên bảng chi tiết ---------- */
    .est-chip {
        display: inline-block;
        background: #fff;
        border: 1px solid var(--primary-lighter);
        border-radius: 999px;
        padding: 2px 11px;
        margin: 2px 4px 2px 0;
        font-size: 0.8rem;
        white-space: nowrap;
    }

    .est-chip b {
        color: var(--primary-dark);
    }

    .est-chip span {
        color: #64748b;
    }

    /* ---------- Nhãn hoá chất ngoài danh mục ---------- */
    .est-outside {
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

    /* ---------- Nhật ký trình ký ---------- */
    .est-history .row-item {
        border-left: 3px solid var(--primary-lighter);
        background: var(--bg-neutral);
        border-radius: var(--border-radius-md);
        padding: 10px 14px;
        margin-bottom: 10px;
    }

    .est-history .row-item .head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }

    .est-history .row-item .act {
        font-weight: 700;
        color: var(--primary-dark);
    }

    .est-history .row-item .who {
        color: var(--text-main);
        font-size: 0.85rem;
    }

    .est-history .row-item .when {
        color: #94a3b8;
        font-size: 0.8rem;
        margin-left: auto;
    }

    .est-history .row-item .note {
        color: #64748b;
        font-size: 0.85rem;
    }

    .est-history .is-empty {
        color: #94a3b8;
        font-size: 0.87rem;
        text-align: center;
        padding: 18px 0;
    }

    /* ---------- Trang chi tiết phiếu ---------- */
    .est-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .est-info .box {
        background: #fff;
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-lg);
        padding: 14px 16px;
        box-shadow: var(--shadow-sm);
    }

    .est-info .box label {
        display: block;
        margin: 0 0 4px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .est-info .box .val {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-main);
    }

    .est-reject-note {
        background: #FEF2F2;
        border: 1px solid #FCA5A5;
        border-radius: var(--border-radius-md);
        color: #B91C1C;
        padding: 10px 14px;
        font-size: 0.87rem;
        margin-bottom: 16px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Bật tìm kiếm cho các ô chọn trong modal ---------- */
        function initSelect($scope) {
            $scope.find('.est-select').each(function() {
                var $modal = $(this).closest('.md-modal');

                if ($(this).hasClass('select2-hidden-accessible')) return;

                $(this).select2({
                    theme: 'bootstrap4',
                    dropdownParent: $modal.length ? $modal : $(document.body),
                    width: '100%',
                    placeholder: '-- Chọn --',
                    language: {
                        noResults: function() {
                            return 'Không tìm thấy dữ liệu phù hợp';
                        }
                    }
                });
            });
        }

        initSelect($(document));

        /* ---------- Chọn nguồn hoá chất: trong danh mục hay tự nhập ---------- */
        function applySource($form) {
            var source = $form.find('[name="source"]:checked').val() || 'category';

            $form.find('.est-source-category').toggle(source === 'category');
            $form.find('.est-source-manual').toggle(source === 'manual');

            // Tô nền ô đang chọn cho dễ nhìn
            $form.find('[name="source"]').each(function() {
                $(this).closest('.est-switch').toggleClass('is-checked', this.checked);
            });
        }

        $(document).on('change', '[name="source"]', function() {
            applySource($(this).closest('form'));
        });

        $('form').each(function() {
            if ($(this).find('[name="source"]').length) applySource($(this));
        });

        /* ---------- Khối số lượng theo tháng: thêm / bớt dòng ---------- */

        // Đánh lại số thứ tự để tên ô luôn là amounts[0][...], amounts[1][...]
        function reindex($box) {
            $box.find('.est-amount-row').each(function(index) {
                $(this).find('[name]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/amounts\[\d*\]/, 'amounts[' + index + ']'));
                });
            });

            // Còn đúng một dòng thì không cho xoá nốt, phiếu phải có ít nhất một số lượng
            $box.find('.btn-est-amount-remove').prop('disabled', $box.find('.est-amount-row').length <= 1);
        }

        function addRow($box, data) {
            var html = $box.find('.est-amount-template').html();
            var $row = $(html);

            if (data) {
                $row.find('[data-field="amount"]').val(data.amount);
                $row.find('[data-field="unit_id"]').val(data.unit_id);
                $row.find('[data-field="for_month_year"]').val(data.for_month_year);
            }

            $box.find('.est-amount-list').append($row);
            reindex($box);

            return $row;
        }

        // Form còn trống thì mở sẵn các tháng mặc định (3 tháng liên tiếp từ tháng dự trù)
        function fillDefaults($box) {
            var periods = $box.data('default-periods') || [];

            $box.find('.est-amount-list').empty();

            if (periods.length) {
                periods.forEach(function(period) {
                    addRow($box, {
                        amount: '',
                        unit_id: '',
                        for_month_year: period
                    });
                });
            } else {
                addRow($box);
            }

            reindex($box);
        }

        $(document).on('click', '.btn-est-amount-add', function() {
            addRow($(this).closest('.est-amounts'));
        });

        $(document).on('click', '.btn-est-amount-remove', function() {
            var $box = $(this).closest('.est-amounts');

            if ($box.find('.est-amount-row').length <= 1) return;

            $(this).closest('.est-amount-row').remove();
            reindex($box);
        });

        $('.est-amounts').each(function() {
            // Chưa có dòng nào (form thêm mới) thì mở sẵn các tháng mặc định
            if (!$(this).find('.est-amount-row').length) fillDefaults($(this));
            reindex($(this));
        });

        /* ---------- Mở modal sửa mặt hàng dự trù ---------- */
        $(document).on('click', '.btn-est-item-edit', function() {
            var row = $(this).data('row') || {};
            var $modal = $('#itemUpdateModal');
            var $form = $modal.find('form');

            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            $form.find('[name="id"]').val(row.id);
            $form.find('[name="technical_information"]').val(row.technical_information || '');
            $form.find('[name="purpose"]').val(row.purpose || '');
            $form.find('[name="chem_name"]').val(row.chem_name || '');

            $form.find('[name="source"][value="' + (row.category_id ? 'category' : 'manual') + '"]')
                .prop('checked', true);

            $form.find('[name="category_id"]').val(row.category_id || '').trigger('change');

            applySource($form);

            // Dựng lại các dòng số lượng theo đúng dữ liệu đang lưu
            var $box = $form.find('.est-amounts');

            $box.find('.est-amount-list').empty();

            (row.amounts || []).forEach(function(item) {
                addRow($box, item);
            });

            if (!$box.find('.est-amount-row').length) addRow($box);

            reindex($box);

            $modal.modal('show');
        });

        /* ---------- Xoá trắng modal thêm mặt hàng mỗi lần mở ---------- */
        $(document).on('click', '.btn-est-item-create', function() {
            var $modal = $('#itemCreateModal');
            var $form = $modal.find('form');

            $form[0].reset();
            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('[name="category_id"]').val('').trigger('change');

            fillDefaults($form.find('.est-amounts'));

            applySource($form);
            $modal.modal('show');
        });

        /* ---------- Modal từ chối: nhận id + mã phiếu của dòng đang bấm ---------- */
        $(document).on('click', '.btn-est-reject', function() {
            var $modal = $('#rejectModal');

            $modal.find('[name="id"]').val($(this).data('id'));
            $modal.find('.est-reject-code').val($(this).data('code'));
            $modal.modal('show');
        });

        /* ---------- Modal tiếp nhận / hoàn tất của bộ phận Cung Ứng ---------- */
        $(document).on('click', '.btn-est-reception', function() {
            var $modal = $('#receptionModal');
            var mode = $(this).data('mode');

            $modal.find('form').attr('action', $(this).data('action'));
            $modal.find('[name="id"]').val($(this).data('id'));
            $modal.find('.est-reception-code').text($(this).data('code'));
            $modal.find('.est-reception-title').text(mode === 'complete' ? 'Hoàn Tất Giải Quyết' : 'Tiếp Nhận Dự Trù');
            $modal.find('.est-reception-desc').text(mode === 'complete' ?
                'Xác nhận bộ phận Cung Ứng đã giải quyết xong phiếu dự trù này.' :
                'Xác nhận bộ phận Cung Ứng tiếp nhận phiếu dự trù đã được phê duyệt.');
            $modal.find('.est-reception-submit').text(mode === 'complete' ? 'Hoàn tất' : 'Tiếp nhận');
            $modal.modal('show');
        });

        /* ---------- Modal theo dõi trình ký ---------- */
        $(document).on('click', '.btn-est-history', function() {
            var $modal = $('#historyModal');
            var $body = $modal.find('.est-history');

            $modal.find('.est-history-subtitle').text($(this).data('title') || '');
            $body.html('<div class="is-empty">Đang tải nhật ký trình ký...</div>');
            $modal.modal('show');

            $.get($(this).data('url'), function(res) {
                var rows = (res && res.rows) || [];

                if (!rows.length) {
                    $body.html('<div class="is-empty">Phiếu này chưa có bước trình ký nào.</div>');
                    return;
                }

                $body.html(rows.map(function(item) {
                    var flow = item.from_status && item.to_status ?
                        '<div class="note"><i class="fas fa-arrow-right-arrow-left mr-1"></i>' +
                        item.from_status + ' &rarr; ' + item.to_status + '</div>' : '';

                    return '<div class="row-item">' +
                        '<div class="head">' +
                        '<span class="act">' + item.action + (item.step ? ' - ' + item.step : '') + '</span>' +
                        '<span class="who"><i class="fas fa-user mr-1"></i>' + item.created_by + '</span>' +
                        '<span class="when">' + item.created_at + '</span>' +
                        '</div>' +
                        flow +
                        (item.note ? '<div class="note"><i class="fas fa-comment-dots mr-1"></i>' + item.note + '</div>' : '') +
                        '</div>';
                }).join(''));
            }).fail(function() {
                $body.html('<div class="is-empty">Không tải được nhật ký trình ký.</div>');
            });
        });
    });
</script>
