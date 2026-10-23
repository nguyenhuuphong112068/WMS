{{--
|--------------------------------------------------------------------------
| DÒNG BẢNG NẠP SAU - phía trình duyệt của App\Support\DeferredRows
|--------------------------------------------------------------------------
| Bảng gắn data-deferred (có id) chỉ có thead lúc tải trang. Sau DOMContentLoaded,
| JS gọi lại đúng URL hiện tại kèm ?_deferred=1 (giữ nguyên bộ lọc trên URL), nhận
| {tables: {id: [dòng]}} rồi đổ vào DataTables với deferRender: chỉ dựng <tr> của
| trang đang xem, thuộc tính <tr>/<td> (class, data-*) được đặt lại khi dựng dòng.
|
| Bảng do mdInitTables khởi tạo thì tự chạy. Màn hình tự khởi tạo DataTables cho
| bảng data-deferred phải gộp cấu hình qua mdDeferred.extend(table, options).
|
| Sự kiện 'md:rows-loaded' (trên <table>) báo dòng đã về - JS của màn hình cần đọc
| toàn bộ dòng (đếm, dựng bộ lọc) chạy lại ở đây thay vì lúc DOMContentLoaded.
|
| Bộ lọc $.fn.dataTable.ext.search đọc thuộc tính <tr> phải dùng
| mdDeferred.rowAttr(rowData, tr, 'state') vì dòng chưa hiện chưa có <tr>.
--}}
@once
    <script>
        // jQuery của layout nạp ở cuối trang nên khởi tạo trong DOMContentLoaded. File này được
        // nạp trước JS của màn hình nên handler chạy trước - mdDeferred có sẵn khi
        // mdInitTables / màn hình khởi tạo bảng.
        document.addEventListener('DOMContentLoaded', function() {
            var $ = jQuery;
            var LOADING = 'Đang tải dữ liệu...';
            var FAILED = 'Không tải được dữ liệu, vui lòng tải lại trang.';
            var pending = {};
            var lazy = {};
            var started = false;

            var ENTITIES = { '&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&#39;': "'", '&nbsp;': ' ' };

            /** Chữ thuần của một ô - bỏ thẻ, giải mã các ký tự đặc biệt hay gặp */
            function text(html) {
                return String(html === null || html === undefined ? '' : html)
                    .replace(/<[^>]*>/g, ' ')
                    .replace(/&(amp|lt|gt|quot|#39|nbsp);/g, function(m) { return ENTITIES[m]; })
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            /** Số cột lá của thead, tính cả colspan / rowspan của tiêu đề nhiều tầng */
            function columnCount(table) {
                var grid = [];
                var max = 0;

                $(table).children('thead').children('tr').each(function(r) {
                    var c = 0;

                    grid[r] = grid[r] || [];

                    $(this).children('th, td').each(function() {
                        while (grid[r][c]) c++;

                        var cs = parseInt(this.getAttribute('colspan') || 1, 10);
                        var rs = parseInt(this.getAttribute('rowspan') || 1, 10);

                        for (var i = 0; i < rs; i++) {
                            grid[r + i] = grid[r + i] || [];
                            for (var j = 0; j < cs; j++) grid[r + i][c + j] = 1;
                        }

                        c += cs;
                    });

                    max = Math.max(max, grid[r].length);
                });

                return max;
            }

            // display -> HTML gốc; sort/type -> data-order; filter -> data-search hoặc chữ thuần
            function render(data, type, row, meta) {
                var cell = (row.c || [])[meta.col];

                if (!cell) return '';
                if (type === 'sort' || type === 'type') return cell.o !== undefined ? cell.o : cell.h;
                if (type === 'filter') return cell.s !== undefined ? cell.s : text(cell.h);

                return cell.h;
            }

            function setAttrs(node, attrs) {
                if (!attrs) return;

                $.each(attrs, function(name, value) {
                    if (name === 'class') {
                        $(node).addClass(value);
                    } else {
                        node.setAttribute(name, value);
                    }
                });
            }

            function createdRow(tr, row) {
                setAttrs(tr, row.a);

                $(tr).children('td, th').each(function(i) {
                    var cell = (row.c || [])[i];
                    if (cell) setAttrs(this, cell.a);
                });
            }

            /** Đọc thuộc tính data-* của một dòng: dòng nạp sau đọc từ JSON, dòng render sẵn đọc <tr> */
            function rowAttr(rowData, tr, name) {
                if (rowData && !Array.isArray(rowData) && rowData.c) {
                    return rowData.a ? rowData.a['data-' + name] : undefined;
                }

                return tr ? tr.getAttribute('data-' + name) : undefined;
            }

            function isDeferredApi(api) {
                var col = api.settings()[0].aoColumns[0];

                return !!col && col.mData === 'c.0.h';
            }

            /** Đổ dòng vào bảng đã khởi tạo; dòng colspan "Chưa có ..." thành lời nhắn bảng rỗng */
            function fill(api, rows, failed) {
                var table = api.table().node();

                if (!isDeferredApi(api)) {
                    if (window.console) console.warn('Bảng #' + table.id + ' có data-deferred nhưng không khởi tạo qua mdDeferred.extend()');
                    return;
                }

                var empty = null;
                var data = [];

                (rows || []).forEach(function(row) {
                    var cells = row.c || [];

                    if (cells.length === 1 && cells[0].a && parseInt(cells[0].a.colspan || 1, 10) > 1) {
                        empty = text(cells[0].h) || empty;
                        return;
                    }

                    data.push(row);
                });

                api.settings()[0].oLanguage.sEmptyTable = failed ? FAILED :
                    (empty || $(table).data('md-empty') || 'Không có dữ liệu.');

                api.clear().rows.add(data).draw();

                $(table).attr('data-deferred-loaded', '1').trigger('md:rows-loaded', [api]);

                if (window.clsRefreshCounts) window.clsRefreshCounts(table.id);
            }

            /** URL lấy dòng: đúng URL đang xem (giữ bộ lọc) + _deferred=1, chỉ xin các bảng cần */
            function rowsUrl(ids) {
                var url = new URL(window.location.href);

                url.searchParams.set('_deferred', '1');
                url.searchParams.set('_tables', ids.join(','));
                url.hash = '';

                return url.toString();
            }

            function request(ids) {
                return $.ajax({ url: rowsUrl(ids), dataType: 'json', cache: false });
            }

            function start() {
                // Bảng "lazy" (bảng chọn trong modal...) chỉ lấy khi màn hình gọi mdDeferred.load()
                var $tables = $('table[data-deferred][id]').not('[data-deferred="lazy"]');

                if (started || !$tables.length) return;

                started = true;

                function deliver(tables, failed) {
                    $tables.each(function() {
                        var rows = tables[this.id] || [];

                        if ($.fn.dataTable.isDataTable(this)) {
                            fill($(this).DataTable(), rows, failed);
                        } else {
                            pending[this.id] = { rows: rows, failed: failed };
                        }
                    });
                }

                request($tables.map(function() { return this.id; }).get())
                    .done(function(res) { deliver((res && res.tables) || {}, false); })
                    .fail(function() { deliver({}, true); });
            }

            // Bảng khởi tạo muộn (tab mở sau, modal mở sau) nhận dòng ngay lúc khởi tạo xong
            $(document).on('init.dt', function(e, settings) {
                var id = settings.nTable.id;

                if (!pending[id]) return;

                var item = pending[id];
                delete pending[id];

                fill(new $.fn.dataTable.Api(settings), item.rows, item.failed);
            });

            window.mdDeferred = {
                /** Bảng này nạp dòng sau? */
                is: function(table) {
                    return $(table).is('[data-deferred]');
                },

                /** Gộp cấu hình dữ liệu (cột, deferRender, createdRow) vào options DataTables của màn hình */
                extend: function(table, options) {
                    var opts = $.extend(true, {}, options || {});
                    var own = opts.columns || [];
                    var pageCreatedRow = opts.createdRow;
                    var columns = [];

                    $(table).data('md-empty', (opts.language && opts.language.emptyTable) || null);

                    for (var i = 0, n = columnCount(table); i < n; i++) {
                        columns.push($.extend({}, own[i] || {}, {
                            data: 'c.' + i + '.h',
                            defaultContent: '',
                            render: render
                        }));
                    }

                    opts.columns = columns;
                    opts.data = [];
                    opts.deferRender = true;
                    opts.language = $.extend({}, opts.language, { emptyTable: LOADING });
                    opts.createdRow = function(tr, row) {
                        createdRow(tr, row);
                        if (pageCreatedRow) pageCreatedRow.apply(this, arguments);
                    };

                    return opts;
                },

                rowAttr: rowAttr,
                text: text,

                /**
                 * Đổi câu "bảng rỗng" của một bảng đã khởi tạo. Bảng nạp sau còn đang tải thì
                 * giữ "Đang tải dữ liệu...", câu mới được dùng khi dòng về mà không có dòng nào.
                 */
                setEmpty: function(table, message) {
                    if (!$.fn.dataTable.isDataTable(table)) return;

                    $(table).data('md-empty', message);

                    if (mdDeferred.loaded(table)) {
                        var api = $(table).DataTable();

                        api.settings()[0].oLanguage.sEmptyTable = message;
                        api.draw(false);
                    }
                },

                /**
                 * Lấy dòng của một bảng "lazy" (gọi một lần, các lần sau dùng lại kết quả).
                 * Trả jQuery promise -> mảng dòng.
                 */
                load: function(id) {
                    if (!lazy[id]) {
                        lazy[id] = request([id]).then(function(res) {
                            return ((res && res.tables) || {})[id] || [];
                        });
                        // Lỗi mạng thì cho gọi lại lần sau
                        lazy[id].fail(function() { delete lazy[id]; });
                    }

                    return lazy[id];
                },

                /**
                 * Bảng thường data-deferred="lazy" (bảng chọn trong modal): lần gọi đầu hiện dòng
                 * "Đang tải...", lấy dòng, chèn vào tbody rồi gọi done(); các lần sau gọi done() ngay.
                 */
                fillLazy: function(table, done, loadingText) {
                    var $table = $(table);

                    if (!$table.length || mdDeferred.loaded($table)) return done();

                    var $body = $table.children('tbody');
                    var cols = columnCount($table[0]) || 1;

                    $body.html('<tr><td colspan="' + cols + '" class="text-center md-sub py-3">' +
                        '<i class="fas fa-circle-notch fa-spin mr-1"></i> ' + (loadingText || LOADING) + '</td></tr>');

                    mdDeferred.load($table.attr('id')).done(function(rows) {
                        $body.html(mdDeferred.html(rows));
                        $table.attr('data-deferred-loaded', '1');
                        done();
                    }).fail(function() {
                        $body.html('<tr><td colspan="' + cols + '" class="text-center text-danger py-3">' + FAILED + '</td></tr>');
                    });
                },

                /** Dựng lại chuỗi <tr> từ dòng JSON - cho bảng thường (không phải DataTables) */
                html: function(rows) {
                    function attrs(a) {
                        return a ? Object.keys(a).map(function(name) {
                            return ' ' + name + '="' + String(a[name]).replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '"';
                        }).join('') : '';
                    }

                    return (rows || []).map(function(row) {
                        return '<tr' + attrs(row.a) + '>' + (row.c || []).map(function(cell) {
                            var a = $.extend({}, cell.a);

                            if (cell.o !== undefined) a['data-order'] = cell.o;
                            if (cell.s !== undefined) a['data-search'] = cell.s;

                            return '<td' + attrs(a) + '>' + cell.h + '</td>';
                        }).join('') + '</tr>';
                    }).join('');
                },

                /** Dòng đã về chưa (để JS màn hình biết chạy ngay hay chờ md:rows-loaded) */
                loaded: function(table) {
                    return !$(table).is('[data-deferred]') || $(table).attr('data-deferred-loaded') === '1';
                }
            };

            // Chờ mọi handler DOMContentLoaded khác khởi tạo xong bảng rồi mới gọi lấy dòng
            setTimeout(start, 0);
        });
    </script>
@endonce
