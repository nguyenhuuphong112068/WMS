{{--
|--------------------------------------------------------------------------
| QUÉT MÃ VẠCH TÌM NHANH - dùng chung cho màn hình Nhập / Sử Dụng / Tồn Kho
|--------------------------------------------------------------------------
| Máy quét cầm tay gõ mã vào ô rồi tự bấm Enter như bàn phím, nên chỉ cần bắt phím
| Enter là dùng được cả quét lẫn gõ tay. Mã vạch trên nhãn lô là Code 128 của đúng
| cột "Mã Xuất Nhập" / "Mã Ống Chuẩn", nên chỉ cần lọc theo cột đó là ra lô cần tìm.
|
| Lọc chạy ngay ở trình duyệt trên toàn bộ dòng của bảng (kể cả dòng ở trang sau),
| không gọi Controller và không tải lại trang.
--}}

@php
    $scanTables = $scanTables ?? [['id' => 'mdTable', 'column' => 1, 'pane' => '', 'label' => 'Danh sách']];
    $scanTitle = $scanTitle ?? 'Quét mã vạch';
    $scanPlaceholder = $scanPlaceholder ?? 'Nhập / quét mã...';
    $scanHint =
        $scanHint ??
        'Quét xong bảng chỉ còn đúng lô vừa quét, các bộ lọc khác được bỏ để không che mất dòng cần tìm. Bấm Bỏ lọc để xem lại toàn bộ.';
@endphp

<div class="bcs-box scan-box" data-tables="{{ json_encode($scanTables) }}">
    <div class="bcs-row">
        <div class="bcs-label-inline">
            <i class="fas fa-barcode mr-1 text-primary"></i> <span>{{ $scanTitle }}</span>:
        </div>
        <div class="bcs-input-wrap">
            <input type="text" class="form-control bcs-input" autocomplete="off" spellcheck="false"
                placeholder="{{ $scanPlaceholder }}" title="{{ $scanHint }}">
        </div>
        <div class="bcs-actions-inline">
            <button type="button" class="btn btn-outline-primary btn-camera-scan" title="Quét bằng camera">
                <i class="fas fa-camera"></i>
            </button>
            <button type="button" class="btn btn-primary btn-bcs-find">
                <i class="fas fa-search mr-1"></i> Tìm
            </button>
            <button type="button" class="btn btn-secondary btn-bcs-clear" style="display: none">
                <i class="fas fa-undo mr-1"></i> Bỏ lọc
            </button>
        </div>
    </div>

    <div class="bcs-result"></div>
</div>

@include('pages.shared.cameraScan')

@once
    <style>
        /* ---------- Ô quét mã vạch siêu gọn ---------- */
        .bcs-box {
            padding: 5px 10px;
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            transition: box-shadow var(--transition-fast, 0.2s), border-color var(--transition-fast, 0.2s);
        }

        .bcs-box.is-filtering {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
            background: #eff6ff;
        }

        .bcs-row {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 8px;
        }

        .bcs-label-inline {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }

        .bcs-input-wrap {
            flex: 0 0 160px;
            width: 160px;
        }

        .bcs-input-wrap .bcs-input {
            width: 100%;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            font-weight: 600;
            letter-spacing: 0.5px;
            height: 31px;
            padding: 2px 8px;
            font-size: 0.84rem;
            background: #ffffff;
        }

        .bcs-input-wrap .bcs-input:focus {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }

        .bcs-actions-inline {
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .bcs-actions-inline .btn {
            padding: 2px 8px;
            font-size: 0.82rem;
            height: 31px;
            line-height: 1.5;
            display: inline-flex;
            align-items: center;
            border-radius: 4px;
        }

        .bcs-result {
            display: none;
            margin-top: 6px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.82rem;
            line-height: 1.4;
        }

        .bcs-result.is-shown {
            display: block;
        }

        .bcs-result.ok {
            background: #DCFCE7;
            border: 1px solid #86EFAC;
            color: #15803D;
        }

        .bcs-result.fail {
            background: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #B91C1C;
        }

        .bcs-result b {
            font-weight: 700;
        }

        /* ---------- Dòng vừa quét ra ---------- */
        table tr.bcs-hit>td {
            background: var(--primary-soft, #eff6ff) !important;
        }

        table tr.bcs-hit>td:first-child {
            box-shadow: inset 3px 0 0 var(--primary, #3b82f6);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /** Các bảng đã khai báo của một ô quét, chỉ giữ bảng thật sự là DataTables. */
            function bcsTables($box) {
                return ($box.data('tables') || []).filter(function(item) {
                    return item.id && $.fn.dataTable.isDataTable('#' + item.id);
                });
            }

            /** Chữ thuần của một ô trong bảng, bỏ hết thẻ HTML để so cho đúng. */
            function bcsText(html) {
                return $('<div>').html(html === null || html === undefined ? '' : html).text();
            }

            /** Số thứ tự các dòng của bảng có mã cần tìm, xét trên TOÀN BỘ dòng. */
            function bcsHits(dt, column, code) {
                var needle = code.toUpperCase();
                var hits = [];

                dt.rows().every(function() {
                    if (bcsText(dt.cell(this.index(), column).data()).toUpperCase().indexOf(needle) !== -1) {
                        hits.push(this.index());
                    }
                });

                return hits;
            }

            /** Bỏ các bộ lọc khác đang bật trên một bảng. */
            function bcsRelax(dt, id) {
                dt.search('');

                $('.cls-select[data-target="' + id + '"]').val('all').trigger('change');
                $('.sgr-select[data-target="' + id + '"]').val('all').trigger('change');

                if (id === 'mdTable') $('.inv-chip[data-state="all"]').not('.is-active').trigger('click');
                if (id === 'invZoneTable') $('.btn-inv-zone-reset').trigger('click');
            }

            /** Tên hoá chất / chất chuẩn của dòng, lấy ở cột ngay sau cột mã. */
            function bcsName(dt, rowIndex, column) {
                var $cell = $('<div>').html(dt.cell(rowIndex, column + 1).data() || '');

                return ($cell.find('.font-weight-bold').first().text() || $cell.text()).trim();
            }

            /** Trả bảng về như cũ: bỏ lọc theo mã và bỏ tô sáng. */
            function bcsReset($box) {
                ($box.data('applied') || []).forEach(function(item) {
                    if (!$.fn.dataTable.isDataTable('#' + item.id)) return;

                    var dt = $('#' + item.id).DataTable();

                    $(dt.rows().nodes()).removeClass('bcs-hit');
                    dt.column(item.column).search('').draw();
                });

                $box.data('applied', []);
                $box.removeClass('is-filtering');
                $box.find('.btn-bcs-clear').hide();
                $box.find('.bcs-result').removeClass('is-shown ok fail').empty();
            }

            /** Viết dòng kết quả, dựng bằng thao tác DOM để mã quét được luôn escape. */
            function bcsShow($box, kind, build) {
                var $result = $box.find('.bcs-result').removeClass('ok fail').addClass('is-shown ' + kind).empty();

                build($result);
            }

            /* ---------- Quét / gõ một mã rồi tìm trên các bảng đã khai báo ---------- */
            function bcsFind($box) {
                var $input = $box.find('.bcs-input');
                var code = ($input.val() || '').trim();

                bcsReset($box);

                if (!code) {
                    bcsShow($box, 'fail', function($result) {
                        $result.append($('<i>').addClass('fas fa-exclamation-circle mr-1'))
                            .append(document.createTextNode(
                                'Vui lòng quét mã vạch trên nhãn hoặc gõ mã rồi nhấn Enter.'));
                    });

                    return;
                }

                var found = [];

                bcsTables($box).forEach(function(item) {
                    var dt = $('#' + item.id).DataTable();
                    var hits = bcsHits(dt, item.column, code);

                    if (!hits.length) return;

                    bcsRelax(dt, item.id);

                    dt.column(item.column).search(code, false, false).draw();

                    $(dt.rows(hits).nodes()).addClass('bcs-hit');

                    found.push({
                        id: item.id,
                        column: item.column,
                        pane: item.pane,
                        label: item.label,
                        rows: hits.length,
                        name: bcsName(dt, hits[0], item.column)
                    });
                });

                if (!found.length) {
                    bcsShow($box, 'fail', function($result) {
                        $result.append($('<i>').addClass('fas fa-exclamation-circle mr-1'))
                            .append(document.createTextNode('Không tìm thấy mã '))
                            .append($('<b>').text(code))
                            .append(document.createTextNode(
                                ' trên màn hình này. Kiểm tra lại nhãn, hoặc lô này thuộc phòng ban khác.'
                            ));
                    });

                    $input.select();

                    return;
                }

                $box.data('applied', found);
                $box.addClass('is-filtering');
                $box.find('.btn-bcs-clear').show();

                var first = found[0];

                if (first.pane) $('[data-pane="' + first.pane + '"]').not('.is-active').trigger('click');

                bcsShow($box, 'ok', function($result) {
                    $result.append($('<i>').addClass('fas fa-check-circle mr-1'))
                        .append(document.createTextNode('Đã tìm thấy '))
                        .append($('<b>').text(code))
                        .append(document.createTextNode(first.name ? ' · ' + first.name : ''))
                        .append($('<br>'));

                    found.forEach(function(item, index) {
                        $result.append(document.createTextNode(
                            (index ? ' · ' : '') + item.rows + ' dòng ở tab "' + item.label + '"'));
                    });
                });

                var table = document.getElementById(first.id);

                if (table) table.scrollIntoView({ behavior: 'smooth', block: 'center' });

                $input.val('');
            }

            $(document).on('keydown', '.bcs-input', function(e) {
                if (e.key !== 'Enter') return;

                e.preventDefault();
                bcsFind($(this).closest('.bcs-box'));
            });

            $(document).on('click', '.btn-bcs-find', function() {
                bcsFind($(this).closest('.bcs-box'));
            });

            $(document).on('click', '.btn-bcs-clear', function() {
                var $box = $(this).closest('.bcs-box');

                bcsReset($box);
                $box.find('.bcs-input').val('').trigger('focus');
            });

            $(document).on('keypress', function(e) {
                var $input = $('.bcs-input').first();
                var tag = (e.target.tagName || '').toLowerCase();

                if (!$input.length || $('.modal.show').length) return;
                if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
                if (e.ctrlKey || e.altKey || e.metaKey) return;
                if (!/^[0-9A-Za-z\-_]$/.test(e.key)) return;

                e.preventDefault();
                $input.val($input.val() + e.key).trigger('focus');
            });

            $('.bcs-input').first().each(function() {
                this.focus({ preventScroll: true });
            });
        });
    </script>
@endonce
