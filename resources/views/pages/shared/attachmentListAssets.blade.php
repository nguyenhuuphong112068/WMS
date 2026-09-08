{{--
|--------------------------------------------------------------------------
| CSS + JS cho cửa sổ xem file đính kèm
|--------------------------------------------------------------------------
| Đi kèm pages/shared/attachmentList.blade.php (nút) và
| pages/shared/attachmentListModal.blade.php (modal #attachmentListModal).
| Nạp một lần trong import/shared/assets.blade.php và inventory/shared/assets.blade.php.
|
| Nút .att-open-modal đổ data-files (JSON) vào bảng trong modal; nút .att-modal-toggle
| gọi AJAX đổi trạng thái file rồi cập nhật ngay tại chỗ, không tải lại trang.
--}}
<style>
    .att-open-modal {
        white-space: nowrap;
    }

    /* Rộng gần hết màn hình, không phụ thuộc breakpoint của modal-xl để bảng 6 cột đủ chỗ */
    #attachmentListModal .modal-dialog {
        max-width: 1140px;
        width: calc(100% - 3.5rem);
    }

    #attachmentListModal .modal-body {
        padding: 20px 24px;
    }

    #attachmentListModal .att-modal-table th {
        white-space: nowrap;
    }

    #attachmentListModal .att-modal-table td {
        word-break: break-word;
    }

    #attachmentListModal .att-modal-head {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 32px;
        margin-bottom: 16px;
        padding: 12px 16px;
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
    }

    #attachmentListModal .att-modal-head label {
        display: block;
        margin: 0 0 2px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    #attachmentListModal .att-modal-head > div > div {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--primary-dark);
        word-break: break-word;
    }

    #attachmentListModal .att-modal-table thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: none;
        vertical-align: middle;
    }

    #attachmentListModal .att-modal-table td {
        vertical-align: middle;
        font-size: 0.85rem;
    }

    #attachmentListModal .att-file {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        color: var(--text-main);
    }

    #attachmentListModal .att-file:hover {
        color: var(--primary-dark);
        text-decoration: none;
    }

    #attachmentListModal .att-row-off .att-file {
        text-decoration: line-through;
        color: #94a3b8;
    }

    .att-status {
        display: inline-block;
        border-radius: 999px;
        padding: 1px 10px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .att-status-on {
        background: #DCFCE7;
        color: #15803D;
        border-color: #86EFAC;
    }

    .att-status-off {
        background: #E2E8F0;
        color: #475569;
        border-color: #CBD5E1;
    }

    #attachmentListModal .att-modal-toggle:disabled {
        opacity: 0.6;
        cursor: default;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ATT_CSRF = '{{ csrf_token() }}';
        var $attModal = $('#attachmentListModal');

        /** Chặn HTML lọt vào từ tên file / người đính kèm */
        function attEsc(value) {
            return $('<div>').text(value === null || value === undefined ? '' : value).html();
        }

        /** Dựng lại các dòng file trong modal từ mảng data-files */
        function attRenderRows(files, toggleUrl) {
            var $tbody = $attModal.find('.att-modal-table tbody').empty();

            if (!files || !files.length) {
                $tbody.html('<tr><td colspan="6" class="text-center text-muted">Chưa có file đính kèm.</td></tr>');
                return;
            }

            files.forEach(function(f, i) {
                var on = f.is_active == 1;

                var actions =
                    '<a class="btn btn-xs btn-outline-secondary" target="_blank" href="' + attEsc(f.url) +
                    '" title="Mở xem file"><i class="fas fa-external-link-alt"></i></a>';

                if (toggleUrl) {
                    actions += ' <button type="button" class="btn btn-xs btn-outline-primary att-modal-toggle" ' +
                        'data-id="' + attEsc(f.id) + '" title="Đổi trạng thái sử dụng của file">' +
                        '<i class="fas fa-right-left mr-1"></i>' + (on ? 'Ngưng dùng' : 'Dùng lại') + '</button>';
                }

                $tbody.append(
                    '<tr data-id="' + attEsc(f.id) + '" class="' + (on ? '' : 'att-row-off') + '">' +
                    '<td class="text-center">' + (i + 1) + '</td>' +
                    '<td><a class="att-file" target="_blank" href="' + attEsc(f.url) + '">' +
                    '<i class="fas fa-external-link-alt text-primary"></i>' + attEsc(f.file_name) + '</a></td>' +
                    '<td>' + attEsc(f.created_by) + '</td>' +
                    '<td class="text-center">' + attEsc(f.created_at) + '</td>' +
                    '<td class="text-center"><span class="att-status att-status-' + (on ? 'on' : 'off') + '">' +
                    (on ? 'Đang sử dụng' : 'Ngưng sử dụng') + '</span></td>' +
                    '<td class="text-center">' + actions + '</td>' +
                    '</tr>'
                );
            });
        }

        $(document).on('click', '.att-open-modal', function() {
            var $btn = $(this);
            var files = $btn.data('files') || [];
            var type = $btn.data('type-label') || '';
            var uploadUrl = $btn.data('upload-url') || '';

            $attModal.data('toggle-url', $btn.data('toggle-url') || '');
            $attModal.data('source-btn', $btn);
            $attModal.find('.att-modal-type').text(type ? '· ' + type : '');
            $attModal.find('.att-modal-code').text($btn.data('code') || '—');
            $attModal.find('.att-modal-name').text($btn.data('name') || '—');
            
            var $uploadSection = $attModal.find('.att-upload-section');
            if (uploadUrl) {
                $uploadSection.show();
                $attModal.find('.att-upload-form').data('url', uploadUrl)[0].reset();
            } else {
                $uploadSection.hide();
            }

            attRenderRows(files, $btn.data('toggle-url') || '');
            $attModal.modal('show');
        });

        $(document).on('submit', '.att-upload-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var uploadUrl = $form.data('url');
            var $btnSubmit = $form.find('button[type="submit"]');

            if (!uploadUrl || $btnSubmit.prop('disabled')) return;

            var formData = new FormData(this);
            formData.append('_token', ATT_CSRF);

            $btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải...');

            $.ajax({
                url: uploadUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res && res.success) {
                        var $sourceBtn = $attModal.data('source-btn');
                        if ($sourceBtn) {
                            var currentFiles = $sourceBtn.data('files') || [];
                            var newFiles = currentFiles.concat(res.files);
                            $sourceBtn.data('files', newFiles);
                            $sourceBtn.html('<i class="fas fa-paperclip"></i> (' + newFiles.length + ')');
                            attRenderRows(newFiles, $attModal.data('toggle-url'));
                        }
                        $form[0].reset();
                    } else {
                        alert((res && res.message) || 'Không tải lên được, vui lòng thử lại.');
                    }
                },
                error: function(xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) || 'Lỗi kết nối hoặc file quá lớn.');
                },
                complete: function() {
                    $btnSubmit.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i> Tải lên');
                }
            });
        });


        $(document).on('click', '.att-modal-toggle', function() {
            var $btn = $(this);
            var $tr = $btn.closest('tr');
            var toggleUrl = $attModal.data('toggle-url');

            if (!toggleUrl || $btn.prop('disabled')) return;

            $btn.prop('disabled', true);

            $.post(toggleUrl, {
                _token: ATT_CSRF,
                id: $btn.data('id')
            }).done(function(res) {
                if (!res || !res.success) {
                    alert((res && res.message) || 'Không đổi được trạng thái file.');
                    return;
                }

                var on = res.is_active == 1;

                $tr.toggleClass('att-row-off', !on);
                $tr.find('.att-status')
                    .attr('class', 'att-status att-status-' + (on ? 'on' : 'off'))
                    .text(on ? 'Đang sử dụng' : 'Ngưng sử dụng');
                $btn.html('<i class="fas fa-right-left mr-1"></i>' + (on ? 'Ngưng dùng' : 'Dùng lại'));
            }).fail(function(xhr) {
                alert((xhr.responseJSON && xhr.responseJSON.message) ||
                    'Không đổi được trạng thái file, vui lòng thử lại.');
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });
    });
</script>
