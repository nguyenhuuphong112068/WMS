{{--
|--------------------------------------------------------------------------
| DỮ LIỆU GỐC - CSS + JS dùng chung cho các màn hình có phê duyệt
|--------------------------------------------------------------------------
| Được @include vào dataTable.blade.php của từng chức năng để 5 màn hình
| Tên Hoá Chất / Nhà Sản Xuất / Nhà Cung Cấp / Quy Cách Đóng Gói / Đơn Vị Tính
| có cùng một giao diện và cùng một cách xử lý, không lặp lại CSS ở mỗi file.
|
| Quy ước để phần JS bên dưới hoạt động:
| - Bảng dữ liệu           : id="mdTable"
| - Nút thêm mới           : class="btn-md-create"
| - Nút sửa                : class="btn-md-edit" kèm data-row='{"id":1,"name":"..."}'
| - Form cần hỏi xác nhận  : class="form-md-confirm" kèm data-title / data-text / data-danger
| - Modal                  : id="createModal" và id="updateModal"
|
| Trang có nhiều bảng (nhiều tab) thì:
| - Bảng thêm vào          : id riêng + class="md-table"
| - Nút thêm mới / sửa     : thêm data-modal="#idModalCủaBảngĐó"
|
| File này bọc trong @once nên có @include nhiều lần trên một trang cũng chỉ in ra một bản.
--}}

@once

<style>
    .md-page {
        padding: 24px 24px 40px;
    }

    /* ---------- Thanh công cụ ---------- */
    .md-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .md-toolbar .hint {
        color: #94a3b8;
        font-size: 0.83rem;
        margin: 0;
    }

    /* ---------- Bảng ---------- */
    .md-card {
        border: none;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
    }

    .md-card .card-body {
        padding: 20px;
    }

    #mdTable thead th,
    .md-table thead th {
        background: var(--primary-soft);
        color: var(--primary-dark);
        border-bottom: 2px solid var(--primary-lighter);
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        /*
        | Tiêu đề cột ĐƯỢC PHÉP xuống dòng.
        |
        | Trước đây để nowrap nên một cột chỉ chứa số 1-2 chữ số vẫn bị kéo rộng
        | bằng cả dòng chữ tiêu đề ("NHẬP TRONG KỲ", "TỔNG TỒN VẬT TƯ"...) và ăn
        | hết chỗ của cột tên hàng - tên vật tư phải xuống 5-6 dòng. Cho tiêu đề
        | xuống dòng thì bề rộng tối thiểu của cột chỉ còn bằng từ dài nhất.
        |
        | Phần rộng dư chia cho cột nào là do mdFitColumns ở cuối
        | layout/master.blade.php quyết định, theo lượng dữ liệu thật của từng cột.
        */
        white-space: normal;
        vertical-align: middle;
        line-height: 1.25;
    }

    #mdTable tbody tr:hover,
    .md-table tbody tr:hover {
        background: var(--primary-soft);
    }

    table.dataTable tbody td {
        vertical-align: middle;
    }

    .md-tag {
        display: inline-block;
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
        border-radius: 6px;
        padding: 2px 9px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .md-sub {
        color: #64748b;
        font-size: 0.86rem;
    }

    /* Công thức hoá học - có chỉ số trên / chỉ số dưới dạng Unicode */
    .md-formula {
        color: var(--text-main);
        font-size: 0.92rem;
        font-weight: 600;
        letter-spacing: 0.4px;
    }

    .md-empty {
        color: #cbd5e1;
    }

    .md-note {
        display: inline-block;
        max-width: 260px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }

    /* ---------- Trạng thái duyệt ---------- */
    .md-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 3px 11px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .md-badge.pending {
        background: #FEF3C7;
        color: #B45309;
        border: 1px solid #FCD34D;
    }

    .md-badge.approved {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }

    .md-badge.rejected {
        background: #FEE2E2;
        color: #B91C1C;
        border: 1px solid #FCA5A5;
    }

    /* ---------- Nút thao tác ---------- */
    .md-actions {
        display: flex;
        justify-content: center;
        gap: 6px;
    }

    .md-actions .btn {
        border-radius: var(--border-radius-md);
        padding: 4px 10px;
        transition: all var(--transition-fast);
    }

    .md-actions .btn:hover {
        transform: translateY(-1px);
    }

    /* ---------- Modal ---------- */
    .md-modal .modal-content {
        border: none;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .md-modal .modal-header {
        background: var(--primary-soft);
        border-bottom: 1px solid var(--primary-lighter);
        padding: 16px 22px;
    }

    .md-modal .modal-title {
        color: var(--primary-dark);
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .md-modal .modal-body {
        padding: 22px;
    }

    .md-modal .modal-footer {
        border-top: 1px solid var(--primary-soft);
        padding: 14px 22px;
    }

    .md-modal label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #475569;
        margin-bottom: 5px;
    }

    .md-modal .form-control {
        border-radius: var(--border-radius-md);
        border: 1px solid #dbe6f2;
        padding: 9px 12px;
    }

    .md-modal .form-control:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
    }

    .md-modal .form-control.is-invalid {
        border-color: #DC2626;
    }

    .md-modal .md-error {
        color: #DC2626;
        font-size: 0.8rem;
        margin-top: 5px;
        display: block;
    }

    .md-modal .md-hint {
        background: var(--primary-soft);
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md);
        padding: 9px 12px;
        font-size: 0.83rem;
        color: var(--primary-dark);
    }
</style>

<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    var mdFlash = {
        success: @json(session('success')),
        error: @json(session('error')),
        warning: @json(session('warning')),
    };

    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Mở modal Thêm mới ---------- */
        $(document).on('click', '.btn-md-create', function() {
            // Trang có nhiều bảng (nhiều tab) thì nút tự chỉ ra modal của mình qua data-modal
            var modal = $(this).data('modal') || '#createModal';
            var $form = $(modal).find('form');

            $form[0].reset();
            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');
            $(modal).modal('show');
        });

        /* ---------- Mở modal Cập nhật, đổ dữ liệu theo name của từng ô ---------- */
        $(document).on('click', '.btn-md-edit', function() {
            var row = $(this).data('row') || {};
            var modal = $(this).data('modal') || '#updateModal';
            var $form = $(modal).find('form');

            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            Object.keys(row).forEach(function(field) {
                $form.find('[name="' + field + '"]').val(row[field] === null ? '' : row[field]);
            });

            // Lý do điều chỉnh phải nhập lại mỗi lần sửa, không giữ nội dung của lần trước
            $form.find('[name="change_reason"]').val('');

            $(modal).modal('show');
        });

        /* ---------- Bảng dữ liệu ---------- */
        // Một trang có thể có nhiều bảng (nhiều tab): #mdTable là bảng mặc định,
        // bảng thêm vào chỉ cần gắn thêm class md-table là chạy cùng một cấu hình.
        //
        // Tách thành hàm dùng chung để màn hình nào nạp lại một vùng bảng bằng AJAX
        // (ví dụ tab Kiểm Kê Định Kỳ) gọi lại được: mdInitTables(vùng vừa thay).
        //
        // Bảng NHẬP LIỆU trong modal (dòng do JS thêm/bớt) gắn data-no-datatable để
        // đứng ngoài: DataTables không biết các dòng thêm bằng jQuery nên phần đếm dòng,
        // tìm kiếm và phân trang sẽ sai, sắp xếp lại còn làm mất dòng đang nhập dở.
        //
        // Bảng ĐÃ PHÂN TRANG Ở SERVER (màn hình Nhập / Sử Dụng) gắn data-server-paged:
        // Controller chỉ gửi về đúng một trang nên phải TẮT phân trang và sắp xếp mặc
        // định của DataTables - bật vào sẽ thành hai tầng phân trang chồng nhau, và
        // sắp xếp lại chỉ đúng trong trang đang xem nên làm người dùng hiểu sai thứ tự.
        // Ô Tìm kiếm của DataTables vẫn giữ để lọc nhanh trong trang; muốn tìm trên
        // toàn bộ dữ liệu thì dùng ô Tìm kiếm của bộ lọc (chạy ở server).
        window.mdInitTables = function(root) {
            $(root ? $(root) : $(document)).find('#mdTable, table.md-table')
                .not('[data-no-datatable]').each(function() {
                if ($.fn.dataTable.isDataTable(this)) return;

                var serverPaged = $(this).is('[data-server-paged]');

                /*
                | Nhiều màn hình dùng forelse/empty của Blade và chèn sẵn một dòng
                | <td colspan="N">Chưa có ...</td> khi danh sách rỗng. Dòng đó ít ô hơn
                | số cột của thead nên DataTables dựng dòng bị lỗi "_DT_CellIndex" và
                | chết cả trang. Gỡ dòng đó ra, lấy luôn lời nhắn của nó làm emptyTable
                | để DataTables tự hiển thị.
                */
                var emptyText = 'Chưa có dữ liệu, hãy bấm "Thêm mới" để khai báo.';

                $(this).children('tbody').children('tr').each(function() {
                    var $cells = $(this).children('td, th');

                    if ($cells.length === 1 && parseInt($cells.attr('colspan') || 1, 10) > 1) {
                        var text = $.trim($cells.text());
                        if (text) emptyText = text;
                        $(this).remove();
                    }
                });

                $(this).DataTable({
                    autoWidth: false,
                    responsive: true,
                    paging: !serverPaged,
                    info: !serverPaged,
                    lengthChange: !serverPaged,
                    pageLength: 25,
                    search: {
                        smart: false
                    },
                    // Bảng phân trang ở server đã được Controller sắp xếp sẵn (mới nhất
                    // lên trước), giữ nguyên thứ tự đó thay vì sắp lại theo cột 1
                    order: serverPaged ? [] : [
                        [1, 'asc']
                    ],
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, 'Tất cả']
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: -1
                    }],
                    language: {
                        search: 'Tìm kiếm:',
                        lengthMenu: 'Hiển thị _MENU_ dòng',
                        info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
                        infoEmpty: 'Không có dữ liệu',
                        zeroRecords: 'Không tìm thấy dòng nào phù hợp',
                        emptyTable: emptyText,
                        paginate: {
                            previous: 'Trước',
                            next: 'Sau'
                        }
                    }
                });
            });
        };

        window.mdInitTables();

        /* ---------- Hỏi xác nhận trước khi Khoá / Duyệt / Từ chối ---------- */
        /*
         | data-require-password="1": bắt buộc nhập lại mật khẩu đăng nhập ngay trong
         | hộp xác nhận. Đây là thành phần thứ 2 của chữ ký điện tử (21 CFR Part 11
         | §11.200) - dùng cho các bước Trình ký / Ký duyệt / Phê duyệt / Từ chối.
         | Mật khẩu được gắn vào form dưới tên "sign_password" rồi mới submit.
         |
         | data-require-reason="1": bắt buộc nhập lý do điều chỉnh - dùng cho Khoá / Mở khoá
         | dữ liệu gốc và danh mục. Lý do được gắn vào form dưới tên "change_reason".
         | Hai cờ này không dùng chung trên một nút (duyệt cần mật khẩu, khoá cần lý do).
        */
        /*
         | Gửi form KHÔNG phụ thuộc vị trí của nó trong DOM.
         |
         | Nút Ký / Duyệt / Khoá nằm trong ô của bảng DataTables (responsive: true).
         | DataTables có thể tách <td> ra khỏi tài liệu khi co bảng, lúc đó form.submit()
         | của node đã rời DOM bị trình duyệt bỏ qua LẶNG LẼ - không lỗi, không gửi đi.
         | Nên dựng lại một form ẩn trên <body> từ action + toàn bộ ô có name của form gốc
         | (đã gồm _token, request_list_id...) rồi mới submit.
        */
        function mdConfirmSubmit(form, extras) {
            var proxy = document.createElement('form');
            proxy.method = (form.getAttribute('method') || 'POST');
            proxy.action = form.getAttribute('action') || window.location.href;
            proxy.style.display = 'none';

            $(form).find('input[name], select[name], textarea[name]').each(function() {
                if ((this.type === 'checkbox' || this.type === 'radio') && !this.checked) return;

                var field = document.createElement('input');
                field.type = 'hidden';
                field.name = this.name;
                field.value = this.value;
                proxy.appendChild(field);
            });

            Object.keys(extras || {}).forEach(function(name) {
                var field = document.createElement('input');
                field.type = 'hidden';
                field.name = name;
                field.value = extras[name];
                proxy.appendChild(field);
            });

            document.body.appendChild(proxy);
            proxy.submit();
        }

        /*
         | Icon con mắt bật / tắt xem mật khẩu.
         |
         | KHÔNG bọc / di chuyển ô nhập: SweetAlert2 chỉ đọc được giá trị khi ô còn là
         | con TRỰC TIẾP của .swal2-popup (selector ".swal2-popup > .swal2-input").
         | Bọc vào <div> sẽ làm getInput() trả null -> preConfirm tưởng bỏ trống.
         | Nên chèn nút mắt cạnh ô (vẫn là con trực tiếp của popup) và canh tuyệt đối
         | theo đúng vị trí thực của ô nhập.
        */
        function mdAddPasswordEye() {
            var input = Swal.getInput();
            var popup = Swal.getPopup();
            if (!input || !popup || input.dataset.eyeReady) return;
            input.dataset.eyeReady = '1';

            popup.style.position = 'relative';
            input.style.paddingRight = '42px';

            var eye = document.createElement('button');
            eye.type = 'button';
            eye.tabIndex = -1;
            eye.title = 'Hiện / ẩn mật khẩu';
            eye.innerHTML = '<i class="fas fa-eye"></i>';
            eye.style.cssText = 'position:absolute;border:0;background:transparent;color:#64748b;' +
                'cursor:pointer;font-size:15px;line-height:1;padding:6px;z-index:5;';
            eye.addEventListener('click', function() {
                var toText = input.type === 'password';
                input.type = toText ? 'text' : 'password';
                eye.innerHTML = toText ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
                input.focus();
            });

            input.insertAdjacentElement('afterend', eye);

            var place = function() {
                eye.style.top = (input.offsetTop + (input.offsetHeight - eye.offsetHeight) / 2) + 'px';
                eye.style.left = (input.offsetLeft + input.offsetWidth - eye.offsetWidth - 6) + 'px';
            };
            place();
            setTimeout(place, 0);
        }

        $(document).on('submit', '.form-md-confirm', function(e) {
            e.preventDefault();
            var form = this;
            var needPassword = String($(form).data('require-password') || '') === '1';
            var needReason = String($(form).data('require-reason') || '') === '1';

            Swal.fire({
                title: $(form).data('title'),
                text: $(form).data('text'),
                icon: 'warning',
                showCancelButton: true,
                input: needPassword ? 'password' : (needReason ? 'textarea' : undefined),
                inputLabel: needPassword ? 'Nhập lại mật khẩu của bạn để ký xác nhận' :
                    (needReason ? 'Lý do điều chỉnh' : undefined),
                inputPlaceholder: needPassword ? 'Mật khẩu đăng nhập' :
                    (needReason ? 'Nêu rõ lý do khoá / mở khoá bản ghi này' : undefined),
                inputAttributes: needPassword ? {
                    autocomplete: 'current-password',
                    autocapitalize: 'off'
                } : (needReason ? {
                    maxlength: '500'
                } : undefined),
                didOpen: needPassword ? mdAddPasswordEye : undefined,
                confirmButtonColor: $(form).data('danger') ? '#DC2626' : '#2E7BC4',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Huỷ',
                preConfirm: needPassword ? function(value) {
                    if (!value) {
                        Swal.showValidationMessage('Vui lòng nhập mật khẩu để ký xác nhận');
                    }
                    return value;
                } : (needReason ? function(value) {
                    if (!value || !value.trim()) {
                        Swal.showValidationMessage('Vui lòng nhập lý do điều chỉnh');
                    }
                    return value;
                } : undefined)
            }).then(function(result) {
                if (!result.isConfirmed) return;

                var extras = {};

                if (needPassword) {
                    extras.sign_password = result.value || '';
                }

                if (needReason) {
                    extras.change_reason = result.value || '';
                }

                mdConfirmSubmit(form, extras);
            });
        });

        /* ---------- Hỏi xác nhận có nhập lý do huỷ ---------- */
        $(document).on('submit', '.form-md-confirm-cancel', function(e) {
            e.preventDefault();
            var form = this;

            Swal.fire({
                title: $(form).data('title'),
                text: $(form).data('text'),
                icon: 'warning',
                input: 'textarea',
                inputPlaceholder: 'Nhập lý do huỷ...',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Đóng',
                preConfirm: (reason) => {
                    if (!reason) {
                        Swal.showValidationMessage('Vui lòng nhập lý do huỷ');
                    }
                    return reason;
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    mdConfirmSubmit(form, {
                        cancel_reason: result.value || ''
                    });
                }
            });
        });

        /* ---------- Thông báo kết quả ---------- */
        function mdShowWarning() {
            Swal.fire({
                title: 'Lưu ý',
                text: mdFlash.warning,
                icon: 'warning',
                confirmButtonColor: '#F59E0B',
                confirmButtonText: 'Đã hiểu'
            });
        }

        if (mdFlash.success) {
            Swal.fire({
                title: 'Thành công!',
                text: mdFlash.success,
                icon: 'success',
                timer: 1800,
                showConfirmButton: false
            }).then(function() {
                // Cảnh báo (nếu có) hiện sau khi toast thành công tự đóng, cần bấm xác nhận
                if (mdFlash.warning) mdShowWarning();
            });
        } else if (mdFlash.warning) {
            mdShowWarning();
        }

        if (mdFlash.error) {
            Swal.fire({
                title: 'Không thực hiện được',
                text: mdFlash.error,
                icon: 'error',
                confirmButtonColor: '#2E7BC4',
                confirmButtonText: 'Đã hiểu'
            });
        }
    });
</script>

@endonce
