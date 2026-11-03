{{--
| Xem file khởi tạo dữ liệu đã đính kèm ngay trên web: danh sách sheet bên trái, bảng dữ liệu bên phải.
| Nội dung nạp bằng AJAX từ InitialDataController::preview() khi bấm nút .btn-idt-preview.
--}}
<style>
    .idt-preview-dialog {
        max-width: min(1400px, calc(100vw - 32px));
    }

    .idt-preview-sub {
        font-size: 0.85rem;
        font-weight: 400;
        color: #64748b;
        margin-top: 2px;
        word-break: break-all;
    }

    .idt-preview-layout {
        display: flex;
        gap: 16px;
        min-height: 420px;
    }

    .idt-preview-sheets {
        flex: 0 0 240px;
        max-height: 68vh;
        overflow-y: auto;
        border-right: 1px solid var(--primary-soft);
        padding-right: 10px;
    }

    .idt-preview-sheets .nav-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 7px 10px;
        border-radius: var(--border-radius-md);
        color: var(--text-main);
        font-size: 0.88rem;
        transition: all 0.2s;
    }

    .idt-preview-sheets .nav-link:hover {
        background: var(--primary-soft);
    }

    .idt-preview-sheets .nav-link.active {
        background: var(--primary);
        color: #fff;
    }

    .idt-preview-sheets .nav-link.is-empty {
        color: var(--primary-lighter);
    }

    .idt-preview-count {
        flex-shrink: 0;
        font-size: 0.72rem;
        font-weight: 700;
        border-radius: 20px;
        padding: 1px 7px;
        background: var(--bg-neutral);
        color: var(--primary-dark);
    }

    .idt-preview-count.has-new {
        background: rgba(22, 163, 74, 0.12);
        color: #16A34A;
    }

    .idt-preview-sheets .nav-link.active .idt-preview-count {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .idt-preview-main {
        flex: 1;
        min-width: 0;
    }

    .idt-preview-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }

    .idt-preview-title {
        font-weight: 700;
        color: var(--primary);
    }

    .idt-preview-table-wrap {
        overflow-x: auto;
    }

    .idt-preview-table-wrap table.dataTable td {
        white-space: pre-line;
        font-size: 0.86rem;
    }

    .idt-preview-table-wrap table.dataTable td:first-child {
        color: #64748b;
        font-weight: 600;
    }

    .idt-preview-table-wrap tr.is-prefill td {
        background: var(--bg-neutral);
        color: #94a3b8;
    }

    .idt-preview-state {
        padding: 60px 0;
        text-align: center;
        color: #64748b;
    }

    @media (max-width: 767.98px) {
        .idt-preview-layout {
            flex-direction: column;
        }

        .idt-preview-sheets {
            flex-basis: auto;
            max-height: 180px;
            border-right: 0;
            border-bottom: 1px solid var(--primary-soft);
            padding: 0 0 10px;
        }
    }
</style>

<div class="modal fade md-modal" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl idt-preview-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title"><i class="fas fa-eye"></i> Xem File Khởi Tạo Dữ Liệu</h5>
                    <div class="idt-preview-sub" id="idtPreviewFile"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="idt-preview-state" id="idtPreviewState"></div>
                <div class="idt-preview-layout" id="idtPreviewLayout" style="display: none">
                    <nav class="nav flex-column idt-preview-sheets" id="idtPreviewSheets"></nav>
                    <div class="idt-preview-main">
                        <div class="idt-preview-bar">
                            <span class="idt-preview-title" id="idtPreviewTitle"></span>
                            <div class="custom-control custom-switch" id="idtPreviewNewWrap">
                                <input type="checkbox" class="custom-control-input" id="idtPreviewOnlyNew">
                                <label class="custom-control-label" for="idtPreviewOnlyNew"
                                    title="Ẩn các dòng hệ thống điền sẵn (nền xám) - chỉ xem dòng phòng ban mới điền">Chỉ dòng mới điền</label>
                            </div>
                        </div>
                        <div class="idt-preview-table-wrap" id="idtPreviewTable"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <a href="#" class="btn btn-primary" id="idtPreviewDownload"><i class="fas fa-download mr-1"></i> Tải file</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $modal = $('#previewModal');
        var cache = {};
        var current = null;
        var request = 0;

        function state(html) {
            $('#idtPreviewLayout').hide();
            $('#idtPreviewState').html(html).show();
        }

        function newCount(sheet) {
            return sheet.rows.filter(function(row) {
                return row[0] >= sheet.firstNew;
            }).length;
        }

        function renderSheet(index) {
            var sheet = current.sheets[index];
            var onlyNew = $('#idtPreviewOnlyNew').prop('checked');
            var hasPrefill = sheet.firstNew > {{ \App\Support\InitialData\InitialDataSchema::FIRST_DATA_ROW }};
            var rows = onlyNew && hasPrefill ? sheet.rows.filter(function(row) {
                return row[0] >= sheet.firstNew;
            }) : sheet.rows;

            $('#idtPreviewSheets .nav-link').removeClass('active').eq(index).addClass('active');
            $('#idtPreviewTitle').text(sheet.title + (sheet.truncated ? ' (chỉ hiện ' + sheet.rows.length + ' dòng đầu, tải file để xem đủ)' : ''));
            $('#idtPreviewNewWrap').toggle(hasPrefill);

            var $wrap = $('#idtPreviewTable');
            var $old = $wrap.find('table');
            if ($old.length && $.fn.dataTable.isDataTable($old[0])) {
                $old.DataTable().destroy();
            }

            var $table = $('<table class="table table-bordered table-sm md-table" data-no-datatable style="width: 100%"></table>');
            $wrap.empty().append($table);
            $table.DataTable({
                data: rows,
                columns: [{
                    title: 'Dòng',
                    width: '50px'
                }].concat(sheet.columns.map(function(title) {
                    return {
                        title: title,
                        render: $.fn.dataTable.render.text()
                    };
                })),
                deferRender: true,
                autoWidth: false,
                pageLength: 25,
                order: [
                    [0, 'asc']
                ],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'Tất cả']
                ],
                search: {
                    smart: false
                },
                createdRow: function(tr, row) {
                    $(tr).toggleClass('is-prefill', row[0] < sheet.firstNew);
                },
                language: {
                    search: 'Tìm kiếm:',
                    lengthMenu: 'Hiển thị _MENU_ dòng',
                    info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
                    infoEmpty: 'Không có dữ liệu',
                    zeroRecords: 'Không tìm thấy dòng nào phù hợp',
                    emptyTable: onlyNew ? 'Sheet này không có dòng mới điền' : 'Sheet này chưa có dữ liệu',
                    paginate: {
                        previous: 'Trước',
                        next: 'Sau'
                    }
                }
            });
            // Gom Hiển thị / Tìm kiếm về một thanh như các bảng khác (layout/master)
            if (window.mdTablebar) window.mdTablebar($table[0]);
        }

        function render(data) {
            current = data;
            if (!data.sheets.length) {
                state('<i class="far fa-file-excel fa-2x mb-2 d-block"></i>File không có sheet dữ liệu nào để xem.');
                return;
            }

            var $nav = $('#idtPreviewSheets').empty();
            var first = -1;
            data.sheets.forEach(function(sheet, i) {
                var fresh = newCount(sheet);
                if (first < 0 && fresh) first = i;
                $('<a href="#" class="nav-link"></a>').attr('data-index', i)
                    .toggleClass('is-empty', !sheet.rows.length)
                    .attr('title', sheet.rows.length + ' dòng có dữ liệu, ' + fresh + ' dòng mới điền')
                    .append($('<span></span>').text(sheet.title))
                    .append($('<span class="idt-preview-count"></span>').toggleClass('has-new', fresh > 0)
                        .text(fresh ? '+' + fresh : sheet.rows.length))
                    .appendTo($nav);
            });

            $('#idtPreviewState').hide();
            $('#idtPreviewLayout').show();
            // Mở sẵn sheet đầu tiên có dòng mới điền
            renderSheet(Math.max(first, 0));
        }

        $(document).on('click', '.btn-idt-preview', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            var token = ++request;

            $('#idtPreviewFile').text($(this).data('file') || '');
            $('#idtPreviewDownload').attr('href', $(this).data('download'));
            $('#idtPreviewOnlyNew').prop('checked', false);
            state('<i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>Đang đọc file...');

            var shown = $.Deferred();
            $modal.one('shown.bs.modal', shown.resolve).modal('show');
            var load = cache[url] ? $.Deferred().resolve(cache[url]) : $.getJSON(url).done(function(data) {
                cache[url] = data;
            });

            // Dựng bảng sau khi modal đã hiện để DataTables đo đúng độ rộng
            $.when(load, shown).done(function(data) {
                if (token === request) render(cache[url]);
            }).fail(function(xhr) {
                if (token !== request) return;
                var message = xhr && xhr.responseJSON && xhr.responseJSON.message;
                state('<i class="fas fa-exclamation-triangle fa-2x mb-2 d-block text-danger"></i>' +
                    $('<span></span>').text(message || 'Không đọc được file, hãy tải về để mở bằng Excel.').html());
            });
        });

        $('#idtPreviewSheets').on('click', '.nav-link', function(e) {
            e.preventDefault();
            renderSheet($(this).data('index'));
        });

        $('#idtPreviewOnlyNew').on('change', function() {
            renderSheet($('#idtPreviewSheets .nav-link.active').data('index'));
        });
    });
</script>
