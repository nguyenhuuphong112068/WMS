{{--
| BẢNG NHẬP NHIỀU DÒNG trong modal Thêm mới của Danh Mục (tiền tố cbt- = "category batch table").
| Dùng cho: Thêm Danh Mục Vật Tư Công Ty (MaterialCategory/create) và Khai Vật Tư Cho Phòng
| (DepartmentMaterial/create) - mỗi lần lưu được nhiều mã.
|
| Quy ước markup:
| - Modal gắn class "cbt-modal", bảng gắn class "cbt-table" + data-no-datatable.
| - Mỗi dòng là MỘT <tbody class="cbt-item"> (dòng phụ - ví dụ khối quy đổi đơn vị - nằm chung
|   tbody), dòng trống cuối bảng là <tbody class="cbt-empty">.
| - Tên ô viết dạng items[<số dòng>][<trường>]; cbtReindex() đánh lại số dòng sau mỗi lần thêm /
|   bỏ dòng, kể cả thuộc tính data-name-prefix (khối quy đổi đơn vị tự dựng tên ô theo đó).
| - Hàng "Điền nhanh" ở đầu bảng: ô gắn data-fill="<trường>" (vd classification.price) áp giá trị
|   cho cùng trường của mọi dòng - mỗi modal tự xử lý vì cách đặt giá trị khác nhau.
--}}
@once
    <style>
        .cbt-modal .modal-dialog {
            max-width: 98vw;
            width: 98vw;
            margin: 1.5vh auto;
        }

        .cbt-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .cbt-toolbar-left {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .cbt-count {
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 0.85rem;
            background: var(--primary-soft);
            padding: 4px 12px;
            border-radius: 20px;
        }

        .cbt-errors ul {
            margin: 0;
            padding-left: 18px;
            max-height: 140px;
            overflow-y: auto;
        }

        .cbt-scroll {
            min-height: 260px;
            max-height: 62vh;
            overflow: auto;
            border: 1px solid rgba(var(--primary-rgb), 0.18);
            border-radius: var(--border-radius-md);
        }

        .cbt-table {
            width: 100%;
            table-layout: fixed;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .cbt-table thead {
            position: sticky;
            top: 0;
            z-index: 3;
        }

        .cbt-table thead th {
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            vertical-align: middle;
            border-bottom: 1px solid var(--primary-lighter);
            padding: 9px 8px;
        }

        .cbt-table thead tr.cbt-fill th {
            background: #fff;
            color: var(--primary-dark);
            text-transform: none;
            letter-spacing: 0;
            font-weight: 600;
            padding: 6px 8px;
            box-shadow: inset 0 -2px 0 var(--primary-soft);
        }

        .cbt-fill-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
        }

        .cbt-fill-label i {
            color: var(--primary-light);
        }

        .cbt-table td {
            vertical-align: top;
            padding: 7px 8px;
            border-top: 1px solid rgba(var(--primary-rgb), 0.08);
        }

        .cbt-table tbody.cbt-item {
            transition: background 0.2s ease;
        }

        .cbt-table tbody.cbt-item:hover {
            background: rgba(var(--primary-rgb), 0.04);
        }

        .cbt-table tbody.cbt-item.has-error {
            background: rgba(220, 38, 38, 0.04);
        }

        .cbt-table tbody.cbt-item.is-flash {
            background: rgba(var(--primary-rgb), 0.14);
        }

        .cbt-table .form-control {
            height: 34px;
            font-size: 0.85rem;
            padding: 4px 8px;
        }

        .cbt-modal .cbt-table .select2-container--bootstrap4 .select2-selection,
        .cbt-modal .cbt-toolbar .select2-container--bootstrap4 .select2-selection {
            min-height: 34px;
            font-size: 0.85rem;
        }

        .cbt-table .cbt-no {
            color: #64748b;
            font-weight: 600;
            padding-top: 14px;
        }

        .cbt-table .cbt-code {
            display: inline-block;
            margin-top: 7px;
        }

        .cbt-mat-name {
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.3;
            word-break: break-word;
        }

        .cbt-mat-meta {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 2px;
            word-break: break-word;
        }

        .cbt-actions {
            white-space: nowrap;
            text-align: center;
        }

        .cbt-actions .btn {
            width: 30px;
            height: 30px;
            padding: 0;
            margin-top: 2px;
        }

        .cbt-table tbody.cbt-empty td {
            text-align: center;
            color: #94a3b8;
            padding: 40px 10px;
            border-top: none;
        }

        .cbt-table tbody.cbt-empty i {
            display: block;
            font-size: 1.6rem;
            color: var(--primary-lighter);
            margin-bottom: 8px;
        }

        /* Dòng phụ (khối quy đổi đơn vị): ẩn khối thì dòng cao 0 */
        .cbt-table tr.cbt-sub td {
            padding: 0 8px;
            border-top: none;
        }

        .cbt-table .cuc-box.cbt-cuc {
            margin: 0 0 8px;
            padding: 8px 12px;
        }

        .cbt-table .cbt-cuc .cuc-row {
            padding: 4px 0;
        }

        .cbt-table .cbt-cuc .cuc-row input {
            height: 30px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /* Đánh lại số thứ tự + tên ô items[i][...] của mọi dòng, cập nhật số đếm và dòng trống */
            window.cbtReindex = function($table) {
                var $items = $table.children('tbody.cbt-item');

                $items.each(function(i) {
                    var prefix = 'items[' + i + ']';

                    $(this).find('.cbt-no').text(i + 1);
                    $(this).find('[name^="items["]').each(function() {
                        this.name = this.name.replace(/^items\[[^\]]*\]/, prefix);
                    });
                    $(this).find('[data-name-prefix]').attr('data-name-prefix', prefix);
                });

                $table.children('tbody.cbt-empty').toggle($items.length === 0);
                $table.closest('.modal').find('.cbt-count').text($items.length + ' dòng');
                $table.trigger('cbt:reindexed', [$items.length]);
            };

            $(document).on('click', '.btn-cbt-remove', function() {
                var $table = $(this).closest('table.cbt-table');

                $(this).closest('tbody.cbt-item').remove();
                cbtReindex($table);
            });

            /* Nhấn Enter trong ô chữ của bảng không gửi form (dễ lưu nhầm khi đang nhập dở) */
            $(document).on('keydown', '.cbt-table input', function(event) {
                if (event.key === 'Enter') event.preventDefault();
            });

            function isEmpty(value) {
                return value === null || value === undefined || value === '' || (Array.isArray(value) && !value.length);
            }

            /**
             * Bộ điều khiển chung của một modal bảng nhiều dòng.
             *
             * opts.modal      : selector modal (#createModal...)
             * opts.template   : selector <template> chứa một <tbody class="cbt-item">
             * opts.local      : { selector: [{id, text, search?}] } - ô Select2 tìm trên mảng có sẵn
             * opts.initRow    : function($root) - khởi tạo riêng của trang cho dòng mới / vùng đầu bảng
             * opts.blankStart : true = mở modal Thêm mới là có sẵn một dòng trống
             *
             * Markup: ô có data-fill="<trường>" được hàng Điền nhanh (.cbt-fill) áp giá trị và được nút
             * nhân bản (.btn-cbt-copy) chép; ô chỉ chép khi nhân bản gắn data-copy="<trường>". Ô cần mở
             * sẵn khi thêm / nhân bản dòng gắn class cbt-focus. Modal có data-next-code (M00118) thì cột
             * .cbt-code hiện mã dự kiến theo thứ tự dòng.
             */
            window.cbtBatch = function(opts) {
                var $modal = $(opts.modal);
                var $table = $modal.find('.cbt-table');

                function initSelects($root) {
                    $.each(opts.local || {}, function(selector, items) {
                        $root.find(selector).data('localLookup', items);
                    });
                    $root.find('select.cbt-select').each(function() {
                        if (!$(this).hasClass('select2-hidden-accessible')) catSelect2($(this), $modal);
                    });
                    if (opts.initRow) opts.initRow($root);
                }

                /* Đặt giá trị của ô $from (ô Điền nhanh / ô cùng trường ở dòng nguồn) vào ô data-fill="key" của dòng */
                function setField($item, key, $from) {
                    var $field = $item.find('[data-fill="' + key + '"]');
                    if (!$field.length) return;

                    // Ô tìm qua AJAX / mảng chỉ có option của giá trị đang chọn: tạo option trước khi đặt
                    if ($field.is('select.cbt-select') && $from.is('select')) {
                        wmsSelectedItems($from).forEach(function(picked) {
                            wmsEnsureOption($field, picked.id, picked.text, key === 'default_location_id' ? {
                                zone: picked.zone_type !== undefined ? (picked.zone_type || '') : (picked.zone || '')
                            } : null);
                        });
                    }

                    $field.val($from.val()).trigger('change');
                }

                function applyFills($item) {
                    $modal.find('.cbt-fill [data-fill]').each(function() {
                        if (!isEmpty($(this).val())) setField($item, $(this).data('fill'), $(this));
                    });
                }

                /* Thêm một dòng (sau $after, mặc định cuối bảng); $copyFrom: chép giá trị của dòng đó */
                function newRow($after, $copyFrom) {
                    var $item = $($(opts.template).html().trim());

                    $after ? $after.after($item) : $table.children('tbody.cbt-empty').before($item);
                    cbtReindex($table);
                    initSelects($item);

                    if ($copyFrom) {
                        $copyFrom.find('[data-fill]').each(function() {
                            if (!isEmpty($(this).val())) setField($item, $(this).data('fill'), $(this));
                        });
                        $copyFrom.find('[data-copy]').each(function() {
                            $item.find('[data-copy="' + $(this).data('copy') + '"]').val($(this).val());
                        });
                    } else {
                        applyFills($item);
                    }

                    return $item;
                }

                function focusRow($item) {
                    var $focus = $item.find('.cbt-focus').first();
                    if ($focus.hasClass('select2-hidden-accessible')) $focus.select2('open');
                    else $focus.trigger('focus');
                }

                /* Mã dự kiến theo thứ tự dòng */
                var codeMatch = /^(\D*)(\d+)$/.exec(String($modal.data('next-code') || ''));
                $table.on('cbt:reindexed', function() {
                    if (!codeMatch) return;

                    var start = parseInt(codeMatch[2], 10);
                    $table.children('tbody.cbt-item').each(function(i) {
                        $(this).find('.cbt-code').text(codeMatch[1] + String(start + i).padStart(codeMatch[2].length, '0'));
                    });
                });

                $modal.on('click', '.btn-cbt-add', function() {
                    var $item = newRow();
                    var scroll = $modal.find('.cbt-scroll')[0];
                    scroll.scrollTop = scroll.scrollHeight;
                    focusRow($item);
                });

                $modal.on('click', '.btn-cbt-copy', function() {
                    var $source = $(this).closest('tbody.cbt-item');
                    focusRow(newRow($source, $source));
                });

                /* Điền nhanh: áp giá trị vừa chọn cho mọi dòng đang có */
                $modal.on('change', '.cbt-fill [data-fill]', function() {
                    var $fill = $(this);
                    if (isEmpty($fill.val())) return;

                    $table.children('tbody.cbt-item').each(function() {
                        setField($(this), $fill.data('fill'), $fill);
                    });
                });

                /* Mở modal Thêm mới: bắt đầu lại từ đầu (bảng trống / một dòng trống) */
                $(document).on('click', '.btn-md-create', function() {
                    if (($(this).data('modal') || '#createModal') !== opts.modal) return;

                    $table.children('tbody.cbt-item').remove();
                    $modal.find('.cbt-errors').hide();
                    $modal.find('.cbt-fill [data-fill]').each(function() {
                        $(this).val($(this).prop('multiple') ? [] : '').trigger('change.select2');
                    });
                    if (opts.blankStart) newRow();
                    cbtReindex($table);
                });

                initSelects($modal.find('thead, tbody.cbt-item'));
                if (opts.blankStart && !$table.children('tbody.cbt-item').length) newRow();
                cbtReindex($table);

                return {
                    $modal: $modal,
                    $table: $table,
                    newRow: newRow,
                    setField: setField
                };
            };
        });
    </script>
@endonce
