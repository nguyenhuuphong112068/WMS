{{--
| HÌNH ẢNH VẬT TƯ (không bắt buộc) - dùng chung cho bảng Danh Mục Vật Tư Công Ty, modal Thêm mới
| (cột Hình Ảnh của từng dòng) và modal Cập nhật. Tiền tố mci- = "material category image".
|
| - Ô chọn ảnh: .mci-picker chứa <input type="file" class="mci-input"> (ẩn) + .mci-preview. Chọn ảnh
|   thì hiện ảnh thu nhỏ ngay (chưa tải lên), nút × bỏ ảnh vừa chọn.
| - Modal Cập nhật thêm ô ẩn remove_image: bấm "Gỡ ảnh" khi đang có ảnh cũ thì gửi 1 - controller
|   bỏ ảnh khỏi mã vật tư (file cũ vẫn giữ cho lịch sử).
| - Ảnh trên bảng (.mci-thumb) bấm vào mở hộp xem ảnh lớn #mciViewer.
--}}
<style>
    .mci-picker {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .mci-picker .mci-input {
        display: none;
    }

    .mci-pick {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        margin: 0;
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md, 8px);
        background: var(--bg-neutral);
        color: var(--primary);
        cursor: pointer;
        transition: all 0.2s;
    }

    .mci-pick:hover {
        border-color: var(--primary);
        background: var(--primary-soft);
    }

    .mci-picker.is-invalid .mci-pick,
    .mci-picker.is-invalid .mci-preview img {
        border-color: #DC2626;
    }

    .mci-picker.has-image .mci-pick {
        display: none;
    }

    .mci-preview {
        position: relative;
        display: none;
    }

    .mci-picker.has-image .mci-preview {
        display: inline-block;
    }

    .mci-preview img,
    .mci-thumb img {
        width: 38px;
        height: 38px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--primary-lighter);
        background: #fff;
        cursor: zoom-in;
    }

    .mci-clear {
        position: absolute;
        top: -7px;
        right: -7px;
        width: 18px;
        height: 18px;
        padding: 0;
        border: none;
        border-radius: 50%;
        background: #DC2626;
        color: #fff;
        font-size: 11px;
        line-height: 18px;
        text-align: center;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
    }

    /* Modal Cập nhật: khung ảnh lớn hơn + nút chữ */
    .mci-picker.mci-lg .mci-pick,
    .mci-picker.mci-lg .mci-preview img {
        width: 96px;
        height: 96px;
    }

    .mci-picker.mci-lg .mci-pick {
        flex-direction: column;
        gap: 4px;
        font-size: 0.75rem;
    }

    .mci-picker.mci-lg .mci-pick i {
        font-size: 1.3rem;
    }

    /* Ảnh nhỏ cạnh tên vật tư trên bảng danh sách */
    .mci-name {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mci-thumb {
        flex: 0 0 auto;
        padding: 0;
        border: none;
        background: none;
    }

    #mciViewer {
        z-index: 1065;
    }

    #mciViewer .modal-body {
        text-align: center;
        background: var(--bg-neutral);
    }

    #mciViewer img {
        max-width: 100%;
        max-height: 75vh;
        border-radius: var(--border-radius-md, 8px);
    }
</style>

<div class="modal fade md-modal" id="mciViewer" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-image"></i> <span class="mci-viewer-title">Hình Ảnh Vật Tư</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <img src="" alt="Hình ảnh vật tư">
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /* Hiện ảnh trong khung xem trước của .mci-picker (src rỗng = về trạng thái chưa có ảnh) */
        window.mciShow = function($picker, src) {
            var old = $picker.data('objectUrl');
            if (old) {
                URL.revokeObjectURL(old);
                $picker.removeData('objectUrl');
            }
            $picker.find('.mci-preview img').attr('src', src || '');
            $picker.toggleClass('has-image', !!src);
        };

        function mciWarn(title, text) {
            window.Swal ? Swal.fire(title, text, 'warning') : alert(text);
        }

        $(document).on('change', '.mci-input', function() {
            var $picker = $(this).closest('.mci-picker');
            var file = this.files && this.files[0];

            if (!file) return;

            if (!/^image\//.test(file.type)) {
                this.value = '';
                mciWarn('Không phải file ảnh', 'Vui lòng chọn file ảnh (jpg, png, gif, webp, bmp).');
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                this.value = '';
                mciWarn('Ảnh quá lớn', 'Hình ảnh tối đa 10 MB.');
                return;
            }

            var url = URL.createObjectURL(file);
            mciShow($picker, url);
            $picker.data('objectUrl', url);
            $picker.find('.mci-remove').val('0');
        });

        /* Nút ×: bỏ ảnh vừa chọn; modal Cập nhật đang hiện ảnh cũ thì đánh dấu gỡ ảnh */
        $(document).on('click', '.mci-clear', function(event) {
            event.preventDefault();
            event.stopPropagation();

            var $picker = $(this).closest('.mci-picker');
            $picker.find('.mci-input').val('');
            mciShow($picker, '');
            $picker.find('.mci-remove').val($picker.data('hasCurrent') ? '1' : '0');
        });

        /* Bấm ảnh (bảng hoặc khung xem trước) mở hộp xem ảnh lớn */
        $(document).on('click', '.mci-thumb, .mci-preview img', function(event) {
            event.preventDefault();
            var src = $(this).is('img') ? $(this).attr('src') : $(this).find('img').attr('src');
            if (!src) return;

            $('#mciViewer img').attr('src', src);
            $('#mciViewer .mci-viewer-title').text($(this).data('title') || 'Hình Ảnh Vật Tư');
            $('#mciViewer').modal('show');
        });

        /* Mở chồng lên modal Thêm mới / Cập nhật: nâng lớp phủ, đóng xong giữ cuộn của modal bên dưới */
        $('#mciViewer').on('show.bs.modal', function() {
            setTimeout(function() {
                $('.modal-backdrop').last().css('z-index', 1062);
            }, 0);
        }).on('hidden.bs.modal', function() {
            if ($('.modal.show').length) $('body').addClass('modal-open');
        });
    });
</script>
