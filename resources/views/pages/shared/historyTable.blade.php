{{--
|--------------------------------------------------------------------------
| BẢNG LỊCH SỬ THAY ĐỔI DÙNG CHUNG (CSS + JS)
|--------------------------------------------------------------------------
| Mọi modal "Lịch Sử Thay Đổi / Lịch Sử Điều Chỉnh" dựng nội dung bằng
| window.wmsHistoryTable($body, data, $trigger):
|
| - Mỗi lần thay đổi là MỘT dòng: cột "Lần Thay Đổi" (hành động, người, thời điểm,
|   nội dung đã đổi) + cột "Lý Do Điều Chỉnh" + đúng các cột của bảng danh sách.
| - Ô khác với lần ngay trước đó được tô vàng.
|
| Hai cách cấp cột:
| 1. Server trả data.columns + rows[].cells (xem *ExportController::history):
|    cell = {text, sub, strong, badge, tone, inline, nowrap}.
| 2. Server chỉ trả rows[].snapshot dạng {Nhãn: Giá trị}: cột lấy theo tiêu đề bảng chứa
|    nút vừa bấm ($trigger), bỏ STT / Thao tác; nhãn ảnh chụp khớp tiêu đề theo chữ
|    (không phân biệt hoa thường). Cột không có trong ảnh chụp (mã, số đếm...) hiện giá trị
|    hiện tại của dòng, chữ nhạt. Nút không nằm trong bảng thì cột = các nhãn ảnh chụp.
|
| Lý do: rows[].change_reason, hoặc tiền tố "Lý do: ... | " của change_note.
| Khung modal thêm class "wms-history-dialog" vào .modal-dialog để rộng 90% màn hình.
--}}
@once
    <style>
        .modal-dialog.wms-history-dialog {
            max-width: 90vw;
            width: 90vw;
        }

        .wh-scroll {
            max-height: 66vh;
            overflow: auto;
        }

        .wh-table thead th {
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 700;
            white-space: nowrap;
            vertical-align: middle;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .wh-table td {
            vertical-align: top;
        }

        .wh-col-change {
            min-width: 190px;
            max-width: 240px;
        }

        .wh-col-reason {
            min-width: 150px;
            max-width: 240px;
            word-break: break-word;
        }

        .wh-row td.wh-col-change {
            border-left: 3px solid var(--primary);
        }

        .wh-row.create td.wh-col-change {
            border-left-color: #16A34A;
        }

        .wh-row.lock td.wh-col-change {
            border-left-color: #94A3B8;
        }

        .wh-row.approve td.wh-col-change {
            border-left-color: var(--accent);
        }

        .wh-row.reject td.wh-col-change {
            border-left-color: #DC2626;
        }

        .wh-row:hover td {
            background: rgba(var(--primary-rgb), 0.04);
        }

        .wh-table td.wh-changed {
            background: #FEF3C7;
        }

        .wh-table td.wh-current {
            color: #94a3b8 !important;
        }

        .wh-action {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 0.88rem;
        }

        .wh-meta {
            color: #94a3b8;
            font-size: 0.76rem;
        }

        .wh-note {
            background: var(--primary-soft);
            border-radius: 6px;
            padding: 5px 8px;
            margin-top: 6px;
            font-size: 0.8rem;
            color: var(--primary-dark);
            word-break: break-word;
        }

        .wh-empty {
            text-align: center;
            color: #94a3b8;
            padding: 30px 10px;
            font-size: 0.9rem;
        }
    </style>

    <script>
        (function() {
            var THEMES = {
                'Thêm mới': 'create',
                'Khoá': 'lock',
                'Mở khoá': 'lock',
                'Phê duyệt': 'approve',
                'Từ chối duyệt': 'reject',
                'Xoá': 'reject'
            };

            /* Tiêu đề bảng -> nhãn ảnh chụp khi chữ khác hẳn nhau (thứ tự = ưu tiên) */
            var ALIASES = {
                'trạng thái': ['trạng thái sử dụng', 'trạng thái duyệt'],
                'sử dụng': ['trạng thái sử dụng'],
                'duyệt': ['trạng thái duyệt'],
                'hạn dùng': ['hạn sử dụng']
            };

            var SKIP = /^(stt|#|thao tác|hành động)$/;

            function norm(text) {
                return String(text || '').normalize('NFC').replace(/\s+/g, ' ').trim().toLowerCase();
            }

            /* Bỏ khoảng trắng + dấu câu để "Tỉ Trọng d(g/ml)" khớp "Tỉ trọng d (g/ml)" */
            function compact(text) {
                return norm(text).replace(/[\s()\[\]\/.,:;\-–_%]/g, '');
            }

            /*
            | Ghép nhãn ảnh chụp cho từng cột, mỗi nhãn chỉ dùng một lần. Đi qua nhiều lượt từ
            | chặt tới lỏng để cột trùng chữ (vd "Mục Đích Sử Dụng") giữ nhãn trước cột khớp
            | lỏng (vd "Sử Dụng"): 1. data-history-key trên <th>, 2. trùng chữ, 3. bí danh,
            | 4. nhãn bắt đầu bằng tiêu đề / tiêu đề bắt đầu bằng nhãn, 5. nhãn kết thúc bằng
            | tiêu đề, 6. chuỗi này chứa chuỗi kia.
            */
            function matchKeys(columns, keys) {
                var used = {};
                var free = function(key) {
                    return key && !used[key];
                };
                var take = function(col, list) {
                    list = list.filter(free);
                    if (!list.length) return;
                    list.forEach(function(key) {
                        used[key] = true;
                    });
                    col.keys = list;
                };
                var byCompact = {};
                keys.forEach(function(key) {
                    byCompact[compact(key)] = byCompact[compact(key)] || key;
                });
                var fuzzy = function(test) {
                    return function(col) {
                        var h = compact(col.title);
                        var hit = keys.filter(free).find(function(key) {
                            return test(compact(key), h);
                        });
                        if (hit) take(col, [hit]);
                    };
                };

                var passes = [
                    function(col) {
                        if (col.historyKey) take(col, col.historyKey.split('|').map(function(k) {
                            return byCompact[compact(k)];
                        }));
                    },
                    function(col) {
                        take(col, [byCompact[compact(col.title)]]);
                    },
                    function(col) {
                        take(col, (ALIASES[norm(col.title)] || []).map(function(k) {
                            return byCompact[compact(k)];
                        }));
                    },
                    fuzzy(function(k, h) {
                        return (h.length >= 4 && k.indexOf(h) === 0) || (k.length >= 4 && h.indexOf(k) === 0);
                    }),
                    fuzzy(function(k, h) {
                        return h.length >= 4 && k.slice(-h.length) === h;
                    }),
                    fuzzy(function(k, h) {
                        return (k.length >= 5 && h.indexOf(k) !== -1) || (h.length >= 5 && k.indexOf(h) !== -1);
                    })
                ];

                passes.forEach(function(pass) {
                    columns.forEach(function(col) {
                        if (!col.keys.length) pass(col);
                    });
                });
            }

            function splitReason(row) {
                var note = row.change_note || '';
                var reason = row.change_reason || '';

                if (note.indexOf('Lý do: ') === 0) {
                    var cut = note.indexOf(' | ');
                    reason = reason || (cut < 0 ? note.slice(7) : note.slice(7, cut));
                    note = cut < 0 ? '' : note.slice(cut + 3);
                }

                return {
                    note: note,
                    reason: reason
                };
            }

            /* Dòng bảng chứa nút vừa bấm (dòng con Responsive thì lấy dòng cha) */
            function sourceRow($trigger) {
                if (!$trigger || !$trigger.length) return null;

                var $tr = $trigger.closest('tr');
                if (!$tr.length || $tr.closest('thead').length) return null;
                if ($tr.hasClass('child')) $tr = $tr.prev();

                var $table = $tr.closest('table');
                var $head = $table.children('thead').find('tr').last();

                return $head.length ? {
                    tr: $tr,
                    head: $head
                } : null;
            }

            /* Cột kiểu 2: theo tiêu đề bảng, ghép nhãn ảnh chụp tương ứng */
            function snapshotColumns(rows, $trigger) {
                var keys = [];
                rows.forEach(function(row) {
                    Object.keys(row.snapshot || {}).forEach(function(key) {
                        if (keys.indexOf(key) === -1) keys.push(key);
                    });
                });

                var src = sourceRow($trigger);
                if (!src) {
                    return keys.map(function(key) {
                        return {
                            title: key,
                            keys: [key]
                        };
                    });
                }

                var columns = [];
                src.head.children('th, td').each(function(index) {
                    var title = $(this).text().replace(/\s+/g, ' ').trim();
                    var n = norm(title);

                    if (!title || SKIP.test(n) || $(this).find('input[type=checkbox]').length) return;

                    columns.push({
                        title: title,
                        keys: [],
                        historyKey: $(this).data('history-key') || '',
                        index: index,
                        align: $(this).hasClass('text-right') ? 'right' : ($(this).hasClass('text-center') ? 'center' : '')
                    });
                });

                matchKeys(columns, keys);

                // Cột không có trong ảnh chụp: lấy chữ đang hiện ở dòng của bảng (giá trị hiện tại)
                var $cells = src.tr.children('td');
                columns.forEach(function(col) {
                    if (!col.keys.length) {
                        col.current = $cells.eq(col.index).text().replace(/\s+/g, ' ').trim() || '—';
                    }
                });

                return columns;
            }

            function snapshotCell(row, col) {
                if (!col.keys.length) {
                    return {
                        text: col.current,
                        current: true
                    };
                }

                var values = col.keys.map(function(key) {
                    return (row.snapshot || {})[key];
                }).filter(function(value) {
                    return value !== undefined && value !== null && value !== '';
                });

                return {
                    text: values.length ? String(values[0]) : '—',
                    sub: values.length > 1 ? values.slice(1).join(' · ') : null
                };
            }

            function cellTd(cell, col) {
                var $td = $('<td>').toggleClass('text-nowrap', !!cell.nowrap);
                var $main;

                if (col && col.align) $td.addClass('text-' + col.align);

                if (cell.badge) {
                    $main = $('<span>').addClass('badge badge-' + cell.badge).text(cell.text);
                } else {
                    $main = $('<span>').text(cell.text);
                    if (cell.strong) $main.addClass('font-weight-bold');
                    if (cell.tone) $main.addClass('text-' + cell.tone);
                }
                $td.append($main);

                if (cell.sub) {
                    if (cell.inline) {
                        $td.append(' ').append($('<span>').addClass('md-sub').text(cell.sub));
                    } else {
                        $('<div>').addClass('md-sub small text-muted').text(cell.sub).appendTo($td);
                    }
                }

                if (cell.current) {
                    $td.addClass('wh-current').attr('title', 'Giá trị hiện tại - mục này không lưu trong lịch sử');
                }

                return $td;
            }

            window.wmsHistoryTable = function($body, data, $trigger, emptyText) {
                var rows = (data && data.rows) || [];
                $body.empty();

                if (!rows.length) {
                    $body.html($('<div>').addClass('wh-empty').text(emptyText || 'Bản ghi này chưa có lần thay đổi nào được lưu.'));
                    return;
                }

                var serverMode = !!(data.columns && data.columns.length);
                var columns = serverMode ? data.columns : snapshotColumns(rows, $trigger);
                var cellsOf = function(row) {
                    return serverMode ? (row.cells || []) : columns.map(function(col) {
                        return snapshotCell(row, col);
                    });
                };

                var $table = $('<table>').addClass('table table-bordered wh-table md-no-sticky mb-0');
                var $head = $('<tr>')
                    .append($('<th>').addClass('wh-col-change').text('Lần Thay Đổi'))
                    .append($('<th>').addClass('wh-col-reason').text('Lý Do Điều Chỉnh'));

                columns.forEach(function(col) {
                    $('<th>').addClass(col.align ? 'text-' + col.align : '').text(col.title).appendTo($head);
                });
                $('<thead>').append($head).appendTo($table);

                var $tbody = $('<tbody>').appendTo($table);
                var allCells = rows.map(cellsOf);

                rows.forEach(function(row, index) {
                    var parts = splitReason(row);
                    // Mới nhất nằm trên cùng: lần ngay trước đó là dòng kế tiếp
                    var olderCells = allCells[index + 1];
                    var $tr = $('<tr>').addClass('wh-row ' + (THEMES[row.action] || ''));

                    var $change = $('<td>').addClass('wh-col-change')
                        .append($('<div>').addClass('wh-action').text(row.action))
                        .append($('<div>').addClass('wh-meta').text(row.created_by || ''))
                        .append($('<div>').addClass('wh-meta').text(row.created_at || ''));

                    if (parts.note) {
                        $('<div>').addClass('wh-note').text(parts.note).appendTo($change);
                    }

                    $tr.append($change)
                        .append($('<td>').addClass('wh-col-reason').text(parts.reason || '—'));

                    allCells[index].forEach(function(cell, i) {
                        var $td = cellTd(cell, columns[i]);

                        if (olderCells && !cell.current &&
                            JSON.stringify(cell) !== JSON.stringify(olderCells[i])) {
                            $td.addClass('wh-changed').attr('title', 'Đã đổi so với lần trước');
                        }

                        $tr.append($td);
                    });

                    $tbody.append($tr);
                });

                $('<div>').addClass('wh-scroll').append($table).appendTo($body);
            };
        })();
    </script>
@endonce
