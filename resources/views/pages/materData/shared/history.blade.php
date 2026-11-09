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
    @include('pages.shared.historyTable')

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

        /* ---------- Bảng lịch sử: pages.shared.historyTable ---------- */
        .md-history-empty {
            text-align: center;
            color: #94a3b8;
            padding: 30px 10px;
            font-size: 0.9rem;
        }
    </style>

    <div class="modal fade" id="mdHistoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered wms-history-dialog" role="document">
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
                var $trigger = $(this);
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
                        wmsHistoryTable($modal.find('.md-history-body'), data, $trigger, 'Bản ghi này chưa có lần thay đổi nào được lưu.');
                    })
                    .catch(function() {
                        $modal.find('.md-history-body').html(
                            '<div class="md-history-empty">Không tải được lịch sử thay đổi. Vui lòng thử lại.</div>'
                        );
                    });
            });

        });
    </script>

@endonce
