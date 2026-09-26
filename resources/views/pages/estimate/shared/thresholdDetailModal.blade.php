{{--
| DỰ TRÙ HOÁ CHẤT - Modal "Chi tiết" của cảnh báo ngưỡng tồn trữ PL IV NĐ 24/2026
|
| Cho người dùng thấy cảnh báo đến từ đâu: các lượng ĐÓNG GÓP vào con số đối chiếu của một mã
| danh mục (theo từng chủ ngưỡng - hoạt chất Bảng A / hỗn hợp Bảng B):
|   - Tồn hiện tại toàn công ty, chi tiết theo lô (+ lô chờ kiểm tra chưa được cộng)
|   - Từng mặt hàng dự trù chưa hoàn thành (Nháp / Chờ ký / Bị từ chối / Đã duyệt chờ giao)
|   - Lượng đang khai (dòng trên form, hoặc chính mặt hàng đang xem)
| Dữ liệu lấy qua AJAX từ ChemicalEstimateController::thresholdDetail().
|
| Mở bằng nút class="btn-est-thr-detail" mang data-params = {category_id, item_id?, amounts?}
| (JS gắn bằng $btn.data('params', {...}) hoặc thuộc tính data-params JSON). Modal tự xếp
| chồng lên modal đang mở (Thêm / Sửa mặt hàng, bảng chọn danh mục).
|
| Biến vào: $list (phiếu đang mở - đánh dấu các dòng dự trù thuộc chính phiếu này).
--}}

@once
    <div class="modal fade md-modal" id="estThrDetailModal" tabindex="-1" role="dialog" aria-hidden="true"
        data-url="{{ route('pages.estimate.chemicalEstimate.thresholdDetail') }}"
        data-list-id="{{ $list->id ?? '' }}">
        <div class="modal-dialog etd-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title"><i class="fas fa-balance-scale mr-2"></i>Chi Tiết Đối Chiếu Ngưỡng PL IV</h5>
                        <div class="etd-subtitle md-sub"></div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="etd-body"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        #estThrDetailModal {
            /* 3 thành phần của thanh xếp chồng - thứ tự đã kiểm tra phân biệt được với người mù màu */
            --etd-current: var(--primary);
            --etd-pending: #EB6834;
            --etd-add: #1BAF7A;
            --etd-limit: #DC2626;
            --etd-muted: #64748b;
        }

        .etd-dialog {
            max-width: 1100px;
        }

        @media (max-width: 1200px) {
            .etd-dialog {
                max-width: 95vw;
            }
        }

        .etd-loading,
        .etd-empty {
            color: #94a3b8;
            padding: 8px 0;
        }

        .etd-card {
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-lg);
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        .etd-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .etd-card-head h6 {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 2px;
        }

        .etd-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            border: 1px solid;
            white-space: nowrap;
        }

        .etd-status.level-ok {
            background: #F0FDF4;
            border-color: #86EFAC;
            color: #166534;
        }

        .etd-status.level-warn {
            background: #FFFBEB;
            border-color: #FCD34D;
            color: #92400E;
        }

        .etd-status.level-exceeded,
        .etd-status.is-blocked {
            background: #FEF2F2;
            border-color: #FCA5A5;
            color: #B91C1C;
        }

        /* ---------- Ô số liệu (kiêm chú giải của thanh) ---------- */
        .etd-figures {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 14px 0 10px;
        }

        .etd-fig {
            flex: 1 1 170px;
            background: var(--bg-neutral);
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            padding: 8px 12px;
        }

        .etd-fig .lbl {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: var(--etd-muted);
        }

        .etd-fig .val {
            display: block;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .etd-fig .sub {
            display: block;
            font-size: 0.72rem;
            color: var(--etd-muted);
        }

        .etd-fig.is-total {
            background: #fff;
            border-color: var(--primary-lighter);
        }

        .etd-swatch {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 3px;
            flex: 0 0 10px;
        }

        .etd-swatch.is-current,
        .etd-seg.is-current {
            background: var(--etd-current);
        }

        .etd-swatch.is-pending,
        .etd-seg.is-pending {
            background: var(--etd-pending);
        }

        .etd-swatch.is-add,
        .etd-seg.is-add {
            background: var(--etd-add);
        }

        /* ---------- Thanh xếp chồng tồn / đang dự trù / lần này so với vạch ngưỡng ---------- */
        .etd-bar {
            position: relative;
            margin: 26px 0 6px;
        }

        .etd-track {
            position: relative;
            height: 16px;
            border-radius: 4px;
            background: var(--primary-soft);
        }

        .etd-fill {
            display: flex;
            gap: 2px;
            height: 100%;
        }

        .etd-seg {
            position: relative;
            height: 100%;
            min-width: 3px;
            transition: opacity var(--transition-fast);
        }

        .etd-fill .etd-seg:last-child {
            border-radius: 0 4px 4px 0;
        }

        .etd-seg:hover {
            opacity: 0.85;
        }

        .etd-seg[data-tip]:hover::after {
            content: attr(data-tip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: var(--text-main);
            color: #fff;
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
            z-index: 3;
            pointer-events: none;
        }

        .etd-limit {
            position: absolute;
            top: -6px;
            bottom: -6px;
            width: 2px;
            margin-left: -1px;
            background: var(--etd-limit);
            z-index: 2;
        }

        .etd-limit-label {
            position: absolute;
            bottom: calc(100% + 2px);
            transform: translateX(-50%);
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-main);
            white-space: nowrap;
        }

        .etd-formula {
            font-size: 0.8rem;
            color: var(--etd-muted);
            margin-top: 6px;
        }

        .etd-formula b {
            color: var(--text-main);
        }

        /* ---------- Bảng chi tiết từng phần ---------- */
        .etd-section {
            margin-top: 16px;
        }

        .etd-section>.hd {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: var(--primary);
            border-left: 3px solid var(--primary);
            padding-left: 8px;
            margin-bottom: 8px;
        }

        .etd-section table {
            width: 100%;
            font-size: 0.82rem;
            margin-bottom: 0;
        }

        .etd-section table th {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 600;
            white-space: nowrap;
        }

        .etd-section table td,
        .etd-section table th {
            padding: 5px 8px;
            border: 1px solid #dbe6f2;
            vertical-align: top;
        }

        .etd-section td.num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .etd-section tr.is-current-list td {
            background: rgba(var(--primary-rgb), 0.06);
        }

        .etd-section tr.is-unconv td {
            background: #FEF2F2;
        }

        .etd-tag {
            display: inline-block;
            margin-left: 4px;
            padding: 0 6px;
            border-radius: 8px;
            font-size: 0.68rem;
            font-weight: 700;
            background: var(--primary-soft);
            color: var(--primary-dark);
            border: 1px solid var(--primary-lighter);
            white-space: nowrap;
        }

        .etd-note {
            font-size: 0.78rem;
            color: var(--etd-muted);
            margin-top: 6px;
        }

        .etd-note.is-warn {
            color: #B45309;
        }

        /* Nút "Chi tiết" gắn cạnh cảnh báo / badge ngưỡng */
        .btn-est-thr-detail {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 0 7px;
            margin-left: 6px;
            border: 1px solid currentColor;
            border-radius: 10px;
            background: #fff;
            color: inherit;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1.6;
            cursor: pointer;
            vertical-align: middle;
            transition: all var(--transition-fast);
        }

        .btn-est-thr-detail:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            transform: translateY(-1px);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var $modal = $('#estThrDetailModal');
            var url = $modal.data('url');
            var listId = $modal.data('list-id') || '';
            var seq = 0;

            $modal.appendTo('body');

            function el(tag, cls, text) {
                var $e = $('<' + tag + '>');
                if (cls) $e.addClass(cls);
                if (text !== undefined && text !== null) $e.text(text);
                return $e;
            }

            function table(headers, rows, numCols) {
                var $t = el('table', 'table table-bordered table-sm');
                var $h = el('tr');
                headers.forEach(function(h) {
                    $h.append(el('th', null, h));
                });
                $t.append(el('thead').append($h));

                var $b = el('tbody');
                rows.forEach(function(r) {
                    var $tr = el('tr', r.cls || null);
                    r.cells.forEach(function(c, i) {
                        var $td = el('td', (numCols || []).indexOf(i) !== -1 ? 'num' : null);
                        c instanceof $ ? $td.append(c) : $td.text(c == null ? '' : c);
                        $tr.append($td);
                    });
                    $b.append($tr);
                });

                return $t.append($b);
            }

            function section(icon, title, $content) {
                return el('div', 'etd-section')
                    .append(el('div', 'hd').append('<i class="' + icon + '"></i>').append(el('span', null, title)))
                    .append($content);
            }

            function figure(swatch, label, value, sub, cls) {
                var $lbl = el('span', 'lbl');
                if (swatch) $lbl.append(el('span', 'etd-swatch is-' + swatch));
                $lbl.append(el('span', null, label));

                return el('div', 'etd-fig' + (cls ? ' ' + cls : ''))
                    .append($lbl)
                    .append(el('span', 'val', value))
                    .append(sub ? el('span', 'sub', sub) : null);
            }

            function statusBadge(card) {
                var cls = card.blocked ? 'is-blocked' : 'level-' + card.level;
                var icon = card.blocked ? 'fa-ban' : (card.level === 'ok' ? 'fa-check-circle' :
                    'fa-exclamation-triangle');
                var pct = card.has_add ? card.projected_percent : card.base_percent;

                return el('span', 'etd-status ' + cls)
                    .append('<i class="fas ' + icon + '"></i>')
                    .append(el('span', null, card.level_label + ' · ' + pct + '%'));
            }

            /* Thanh xếp chồng: đoạn rỗng không vẽ; vạch ngưỡng đặt trên cùng thang đo */
            function bar(card) {
                var parts = [
                    ['current', card.bar.current, 'Tồn hiện tại: ' + card.current_kg + ' kg'],
                    ['pending', card.bar.pending, 'Đang dự trù chưa hoàn thành: ' + card.pending_kg + ' kg']
                ];
                if (card.has_add) parts.push(['add', card.bar.add, card.add_label + ': ' + card.add_kg + ' kg']);

                var $fill = el('div', 'etd-fill');
                parts.forEach(function(p) {
                    if (p[1] > 0) {
                        $fill.append(el('span', 'etd-seg is-' + p[0]).css('width', p[1] + '%').attr('data-tip', p[2]));
                    }
                });

                var $limit = el('span', 'etd-limit').css('left', card.bar.threshold + '%')
                    .append(el('span', 'etd-limit-label', 'Ngưỡng ' + card.threshold_kg + ' kg'));

                return el('div', 'etd-bar')
                    .attr('role', 'img')
                    .attr('aria-label', 'Tồn ' + card.current_kg + ' kg, đang dự trù ' + card.pending_kg + ' kg' +
                        (card.has_add ? ', ' + card.add_label.toLowerCase() + ' ' + card.add_kg + ' kg' : '') +
                        ' so với ngưỡng ' + card.threshold_kg + ' kg')
                    .append(el('div', 'etd-track').append($fill).append($limit));
            }

            function renderCard(card) {
                var $card = el('div', 'etd-card');

                $card.append(el('div', 'etd-card-head')
                    .append(el('div')
                        .append(el('h6', null, (card.table === 'A' ? 'Nhóm 9 (Bảng A) — ' : 'Nhóm 10 (Bảng B) — ') +
                            card.title))
                        .append(el('div', 'md-sub', [card.subtitle, 'ngưỡng ' + card.threshold_kg + ' ' + card
                            .kg_label
                        ].filter(Boolean).join(' · '))))
                    .append(statusBadge(card)));

                /* ----- Ô số liệu: cũng là chú giải màu của thanh ----- */
                var $figs = el('div', 'etd-figures')
                    .append(figure('current', 'Tồn hiện tại', card.current_kg + ' kg',
                        card.onhand_rows.length + ' lô còn tồn'))
                    .append(figure('pending', 'Đang dự trù chưa hoàn thành', card.pending_kg + ' kg',
                        card.pending_rows.length + ' mặt hàng'));
                if (card.has_add) {
                    $figs.append(figure('add', card.add_label, card.add_kg + ' kg',
                        card.add_rows.length + ' dòng số lượng'));
                }
                var totalKg = card.has_add ? card.projected_kg : card.base_kg;
                var totalPct = card.has_add ? card.projected_percent : card.base_percent;
                $figs.append(figure(null, card.has_add ? 'Tổng dự kiến' : 'Tồn + đang dự trù', totalKg + ' kg',
                    totalPct + '% ngưỡng ' + card.threshold_kg + ' kg', 'is-total'));
                $card.append($figs);

                $card.append(bar(card));
                $card.append(el('div', 'etd-formula')
                    .append(el('span', null, 'Tổng = tồn ' + card.current_kg + ' + đang dự trù ' + card.pending_kg +
                        (card.has_add ? ' + ' + card.add_label.toLowerCase() + ' ' + card.add_kg : '') + ' = '))
                    .append(el('b', null, totalKg + ' ' + card.kg_label))
                    .append(el('span', null, ' / ngưỡng ' + card.threshold_kg + ' kg.')));

                /* ----- Tồn hiện tại theo lô ----- */
                var $onhand = el('div');
                if (card.onhand_rows.length) {
                    $onhand.append(table(
                        ['Mã xuất nhập', 'Ngày nhập', 'Mã danh mục', 'Phòng ban', 'Tồn còn lại', 'Quy ra (kg)'],
                        card.onhand_rows.map(function(o) {
                            return {
                                cells: [o.ref, o.date, o.category_code + (o.member_name ? ' · ' + o.member_name : ''),
                                    o.department_name, o.on_hand, o.on_hand_kg
                                ]
                            };
                        }), [4, 5]));
                } else {
                    $onhand.append(el('div', 'etd-empty', 'Không có lô nào còn tồn.'));
                }
                if (card.unconvertible.length) {
                    $onhand.append(el('div', 'etd-note is-warn', 'Chưa quy đổi được ra kg (không cộng vào tồn):'));
                    $onhand.append(table(['Mã danh mục', 'Hoá chất', 'Lý do'], card.unconvertible.map(function(u) {
                        return {
                            cls: 'is-unconv',
                            cells: [u.category_code, u.chem_name, u.reason]
                        };
                    })));
                }
                if (card.quarantine.length) {
                    $onhand.append(el('div', 'etd-note is-warn',
                        'Lô đang chờ kiểm tra - chưa cộng vào tồn đối chiếu ngưỡng (được cộng sau khi xác nhận kiểm tra):'
                    ));
                    $onhand.append(table(['Mã xuất nhập', 'Ngày nhập', 'Mã danh mục', 'Phòng ban', 'Số lượng nhập'],
                        card.quarantine.map(function(q) {
                            return {
                                cells: [q.code, q.date, q.category_code, q.department_name, q.amount]
                            };
                        }), [4]));
                }
                $card.append(section('fas fa-warehouse', 'Tồn hiện tại — ' + card.current_kg + ' kg', $onhand));

                /* ----- Dự trù chưa hoàn thành ----- */
                var $pending = el('div');
                if (card.pending_rows.length) {
                    $pending.append(table(
                        ['Phiếu', 'Trạng thái', 'Phòng ban', 'Mã danh mục', 'Số lượng', 'Quy ra (kg)'],
                        card.pending_rows.map(function(p) {
                            var $code = el('span', null, p.list_code);
                            if (p.is_current_list) $code = $code.add(el('span', 'etd-tag', 'Phiếu đang mở'));
                            return {
                                cls: p.unconvertible ? 'is-unconv' : (p.is_current_list ? 'is-current-list' : null),
                                cells: [$('<span>').append($code), p.status_label, p.department_name, p.category_code,
                                    p.amounts, p.unconvertible ? p.kg + ' (thiếu quy đổi)' : p.kg
                                ]
                            };
                        }), [5]));
                    $pending.append(el('div', 'etd-note',
                        'Gồm các mặt hàng chưa huỷ, chưa xác nhận giao của phiếu Nháp / Chờ ký / Bị từ chối / Đã duyệt (chờ giao) trong công ty.'
                    ));
                    if (card.pending_rows.some(function(p) {
                            return p.unconvertible;
                        })) {
                        $pending.append(el('div', 'etd-note is-warn',
                            'Dòng tô đỏ có số lượng chưa quy ra kg được (thiếu hệ số quy đổi / tỉ trọng) nên chưa cộng đủ - nên sửa mặt hàng để khai hệ số.'
                        ));
                    }
                } else {
                    $pending.append(el('div', 'etd-empty', 'Không có mặt hàng dự trù nào đang chờ hoàn thành.'));
                }
                $card.append(section('fas fa-clipboard-list', 'Đang dự trù chưa hoàn thành — ' + card.pending_kg +
                    ' kg', $pending));

                /* ----- Lượng đang khai ----- */
                if (card.has_add) {
                    var $add = el('div');
                    if (card.add_rows.length) {
                        $add.append(table(['Số lượng'], card.add_rows.map(function(r) {
                            return {
                                cells: [r]
                            };
                        })));
                    } else {
                        $add.append(el('div', 'etd-empty', 'Chưa nhập số lượng.'));
                    }
                    if (card.add_unconvertible) {
                        $add.append(el('div', 'etd-note is-warn',
                            'Có dòng chưa quy ra kg được (thiếu hệ số quy đổi / tỉ trọng) nên chưa cộng.'));
                    }
                    $card.append(section('fas fa-plus-circle', card.add_label + ' — ' + card.add_kg + ' kg', $add));
                }

                return $card;
            }

            function render(data) {
                var $body = $modal.find('.etd-body').empty();

                $modal.find('.etd-subtitle').text('Mã ' + data.category_code + ' · ' + data.chem_name +
                    ' · cảnh báo vàng từ ' + data.warn_percent + '% ngưỡng');

                if (!data.cards.length) {
                    $body.append(el('div', 'etd-empty', 'Mã danh mục này không thuộc diện đối chiếu ngưỡng PL IV.'));
                    return;
                }

                data.cards.forEach(function(card) {
                    card.add_label = data.add_label || '';
                    $body.append(renderCard(card));
                });
            }

            $(document).on('click', '.btn-est-thr-detail', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var params = $.extend({}, $(this).data('params') || {}, {
                    list_id: listId
                });
                var mySeq = ++seq;

                $modal.find('.etd-subtitle').text('');
                $modal.find('.etd-body').html(
                    '<div class="etd-loading"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu...</div>');
                $modal.modal('show');

                $.get(url, params)
                    .done(function(data) {
                        if (mySeq !== seq) return;
                        if (!data || !data.ok) {
                            $modal.find('.etd-body').html('').append(el('div', 'etd-empty', (data && data.reason) ||
                                'Không tải được dữ liệu chi tiết.'));
                            return;
                        }
                        render(data);
                    })
                    .fail(function() {
                        if (mySeq !== seq) return;
                        $modal.find('.etd-body').html('').append(el('div', 'etd-empty',
                            'Không tải được dữ liệu chi tiết. Vui lòng thử lại.'));
                    });
            });

            /* ---------- Xếp chồng lên modal đang mở (Thêm / Sửa mặt hàng, bảng chọn danh mục) ---------- */
            $modal.on('show.bs.modal', function() {
                var depth = $('.modal.show').not(this).length;
                var z = 1080 + depth * 20;

                $(this).css('z-index', z);
                setTimeout(function() {
                    $('.modal-backdrop').last().css('z-index', z - 5);
                }, 0);
            });

            $modal.on('hidden.bs.modal', function() {
                // Bootstrap gỡ .modal-open khi đóng modal con -> trả lại cho modal cha còn mở
                if ($('.modal.show').length) $(document.body).addClass('modal-open');
            });
        });
    </script>
@endonce
