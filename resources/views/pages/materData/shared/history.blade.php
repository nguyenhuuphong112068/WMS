{{--
|--------------------------------------------------------------------------
| DỮ LIỆU GỐC - MODAL XEM LỊCH SỬ THAY ĐỔI (CSS + khung modal + JS)
|--------------------------------------------------------------------------
| Dùng chung cho mọi màn hình trong nhóm "Dữ Liệu Gốc". Nội dung lịch sử được
| nạp bằng JS từ route <prefix>history của từng chức năng, dữ liệu lấy từ bảng
| datamaster_histories (xem App\Support\DataMasterHistory).
|
| Cách gắn vào một màn hình:
| 1. list.blade.php  : @include('pages.materData.shared.history') trong @section('model')
| 2. dataTable       : bọc nút Sửa trong <span class="md-btn-wrap"> rồi @include
|                      'pages.materData.shared.historyBadge' kèm $count / $url / $title.
|                      Màn hình dùng shared.rowActions thì chỉ cần truyền 'historyCount'.
|
| File tự chứa CSS + JS và bọc trong @once nên màn hình cũ (Phòng Ban, Trạng Thái)
| chưa dùng bộ md-* vẫn hiển thị đúng, và @include nhiều lần cũng chỉ in ra một bản.
--}}

@once

    <style>
        /* ---------- Badge số lần thay đổi, gắn ở góc trên bên phải nút Sửa ---------- */
        .md-btn-wrap {
            position: relative;
            display: inline-block;
        }

        .md-count-badge {
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

        .md-count-badge:hover {
            background: var(--primary-dark);
            transform: scale(1.12);
        }

        .md-count-badge:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.25);
        }

        /* ---------- Khung modal ---------- */
        #mdHistoryModal .modal-content {
            border: none;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        #mdHistoryModal .modal-header {
            background: var(--primary-soft);
            border-bottom: 1px solid var(--primary-lighter);
            padding: 16px 22px;
        }

        #mdHistoryModal .modal-title {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        #mdHistoryModal .modal-body {
            padding: 22px;
        }

        #mdHistoryModal .modal-footer {
            border-top: 1px solid var(--primary-soft);
            padding: 14px 22px;
        }

        #mdHistoryModal .md-history-hint {
            background: var(--primary-soft);
            border: 1px dashed var(--primary-lighter);
            border-radius: var(--border-radius-md);
            padding: 9px 12px;
            font-size: 0.83rem;
            color: var(--primary-dark);
        }

        .md-history-subtitle {
            color: #64748b;
            font-size: 0.86rem;
            font-weight: 400;
        }

        /* ---------- Danh sách lịch sử ---------- */
        .md-history-body {
            max-height: 62vh;
            overflow-y: auto;
            padding-right: 4px;
        }

        .md-history-item {
            border: 1px solid var(--primary-soft);
            border-left: 3px solid var(--primary);
            border-radius: var(--border-radius-md);
            padding: 12px 14px;
            margin-bottom: 12px;
            background: #fff;
            transition: box-shadow var(--transition-fast);
        }

        .md-history-item:hover {
            box-shadow: var(--shadow-sm);
        }

        .md-history-item.create {
            border-left-color: #16A34A;
        }

        .md-history-item.lock {
            border-left-color: #94A3B8;
        }

        .md-history-item.approve {
            border-left-color: #17B8D4;
        }

        .md-history-item.reject {
            border-left-color: #DC2626;
        }

        .md-history-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }

        .md-history-action {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 0.9rem;
        }

        .md-history-meta {
            color: #94a3b8;
            font-size: 0.78rem;
        }

        .md-history-note {
            background: var(--primary-soft);
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 0.83rem;
            color: var(--primary-dark);
            margin-bottom: 8px;
            word-break: break-word;
        }

        .md-history-snapshot {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 4px 16px;
            font-size: 0.82rem;
            color: #64748b;
        }

        .md-history-snapshot b {
            color: #475569;
            font-weight: 600;
        }

        .md-history-empty {
            text-align: center;
            color: #94a3b8;
            padding: 30px 10px;
            font-size: 0.9rem;
        }
    </style>

    <div class="modal fade" id="mdHistoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history"></i>
                        Lịch Sử Thay Đổi
                        <small class="md-history-subtitle ml-2"></small>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">


                    <div class="md-history-body"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ---------- Bấm badge để xem lịch sử thay đổi ---------- */
            $(document).on('click', '.btn-md-history', function() {
                var url = $(this).data('url');
                var $modal = $('#mdHistoryModal');

                $modal.find('.md-history-subtitle').text($(this).data('title') || '');
                $modal.find('.md-history-body').html(
                    '<div class="md-history-empty"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải lịch sử...</div>'
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
                        renderHistory(data.rows || []);
                    })
                    .catch(function() {
                        $modal.find('.md-history-body').html(
                            '<div class="md-history-empty">Không tải được lịch sử thay đổi. Vui lòng thử lại.</div>'
                        );
                    });
            });

            /* Dựng danh sách bằng thao tác DOM để nội dung dữ liệu luôn được escape */
            function renderHistory(rows) {
                var $body = $('#mdHistoryModal').find('.md-history-body').empty();

                if (!rows.length) {
                    $body.html(
                        '<div class="md-history-empty">Bản ghi này chưa có lần thay đổi nào được lưu.</div>');
                    return;
                }

                var themes = {
                    'Thêm mới': 'create',
                    'Khoá': 'lock',
                    'Mở khoá': 'lock',
                    'Phê duyệt': 'approve',
                    'Từ chối duyệt': 'reject',
                    'Xoá': 'reject'
                };

                rows.forEach(function(row) {
                    var $item = $('<div>').addClass('md-history-item ' + (themes[row.action] || ''));

                    $('<div>').addClass('md-history-head')
                        .append($('<span>').addClass('md-history-action').text(row.action))
                        .append($('<span>').addClass('md-history-meta').text(row.created_by + ' · ' + row
                            .created_at))
                        .appendTo($item);

                    if (row.change_note) {
                        $('<div>').addClass('md-history-note').text(row.change_note).appendTo($item);
                    }

                    var $snapshot = $('<div>').addClass('md-history-snapshot');

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
