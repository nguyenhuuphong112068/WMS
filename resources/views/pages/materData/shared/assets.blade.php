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
--}}

<style>
    .md-page {
        padding: 24px 24px 40px;
    }

    /* ---------- Đầu trang ---------- */
    .md-hero {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        border-radius: var(--border-radius-lg);
        color: #fff;
        padding: 22px 26px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-md);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .md-hero h1 {
        color: #fff;
        font-size: 1.4rem;
        margin: 0 0 6px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .md-hero p {
        margin: 0;
        font-size: 0.88rem;
        opacity: 0.9;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Thẻ đếm số lượng theo trạng thái duyệt */
    .md-stats {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .md-stats .stat {
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
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

    #mdTable thead th {
        background: var(--primary-soft);
        color: var(--primary-dark);
        border-bottom: 2px solid var(--primary-lighter);
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        white-space: nowrap;
    }

    #mdTable tbody tr:hover {
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
    };

    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Mở modal Thêm mới ---------- */
        $(document).on('click', '.btn-md-create', function() {
            var $form = $('#createModal').find('form');

            $form[0].reset();
            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');
            $('#createModal').modal('show');
        });

        /* ---------- Mở modal Cập nhật, đổ dữ liệu theo name của từng ô ---------- */
        $(document).on('click', '.btn-md-edit', function() {
            var row = $(this).data('row') || {};
            var $form = $('#updateModal').find('form');

            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            Object.keys(row).forEach(function(field) {
                $form.find('[name="' + field + '"]').val(row[field] === null ? '' : row[field]);
            });

            $('#updateModal').modal('show');
        });

        /* ---------- Bảng dữ liệu ---------- */
        $('#mdTable').DataTable({
            autoWidth: false,
            responsive: true,
            pageLength: 25,
            order: [
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
                emptyTable: 'Chưa có dữ liệu, hãy bấm "Thêm mới" để khai báo.',
                paginate: {
                    previous: 'Trước',
                    next: 'Sau'
                }
            }
        });

        /* ---------- Hỏi xác nhận trước khi Khoá / Duyệt / Từ chối ---------- */
        $(document).on('submit', '.form-md-confirm', function(e) {
            e.preventDefault();
            var form = this;

            Swal.fire({
                title: $(form).data('title'),
                text: $(form).data('text'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: $(form).data('danger') ? '#DC2626' : '#2E7BC4',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Huỷ'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });

        /* ---------- Thông báo kết quả ---------- */
        if (mdFlash.success) {
            Swal.fire({
                title: 'Thành công!',
                text: mdFlash.success,
                icon: 'success',
                timer: 1800,
                showConfirmButton: false
            });
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
