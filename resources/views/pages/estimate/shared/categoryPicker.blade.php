{{--
| DỰ TRÙ - Bảng chọn "Hoá Chất Trong Danh Mục" từ Danh Mục Hoá Chất của phòng ban
|
| Mở từ nút ".est-pick-category" đặt trước ô select[name="category_id"] trong modal
| Thêm / Sửa mặt hàng dự trù (nút mang data-target-modal="#itemCreateModal" hoặc
| "#itemUpdateModal"). Bấm "Chọn" đổ giá trị vào ô select của modal đó rồi trigger
| change để Select2 cập nhật và cảnh báo ngưỡng PL IV tự chạy lại (xem assets.blade.php).
|
| Biến vào: $categories (từ categoryOptions()), $categoryLevels (category_id => 'ok'|'warn'|'exceeded').
--}}

@php
    $ecpLevelLabel = ['exceeded' => 'Vượt ngưỡng', 'warn' => 'Sắp chạm ngưỡng'];
    $ecpLevelBadge = ['exceeded' => 'badge-danger', 'warn' => 'badge-warning'];
@endphp

<div class="modal fade md-modal" id="estCategoryPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-th-list mr-2"></i> Chọn Hoá Chất Từ Danh Mục
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p class="md-hint">
                    <i class="fas fa-info-circle mr-1"></i>
                    Chỉ hiện hoá chất trong Danh Mục của phòng ban đang chọn, đã được duyệt và đang hoạt động.
                    Tìm nhanh theo mã hoặc tên rồi bấm <b>Chọn</b>.
                </p>

                <div class="ecp-filters">
                    <div class="ecp-field ecp-field-search">
                        <label><i class="fas fa-search mr-1 text-primary"></i> Tìm kiếm</label>
                        <input type="text" class="form-control" id="ecpSearch" placeholder="Mã hoặc tên hoá chất...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="estCategoryPickerTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 52px">STT</th>
                                <th style="width: 110px">Mã</th>
                                <th>Tên Hoá Chất</th>
                                <th style="width: 90px">Đơn Vị</th>
                                <th style="width: 150px">Cảnh Báo Ngưỡng</th>
                                <th class="text-center" style="width: 80px">Chọn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                @php $ecpLevel = $categoryLevels[$category->id] ?? null; @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><span class="md-tag">{{ $category->code }}</span></td>
                                    <td class="font-weight-bold">{{ $category->chem_name }}</td>
                                    <td>{{ $category->unit_short_name ?: '—' }}</td>
                                    <td>
                                        @if ($ecpLevel && isset($ecpLevelLabel[$ecpLevel]))
                                            <span class="badge {{ $ecpLevelBadge[$ecpLevel] }}">{{ $ecpLevelLabel[$ecpLevel] }}</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary ecp-choose"
                                            data-id="{{ $category->id }}"
                                            data-name="{{ $category->code }} - {{ $category->chem_name }}{{ $category->unit_short_name ? ' (' . $category->unit_short_name . ')' : '' }}"
                                            title="Chọn hoá chất này">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        #estCategoryPickerModal .modal-dialog {
            max-width: 900px;
        }

        .ecp-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 12px;
        }

        .ecp-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ecp-field-search {
            flex: 1 1 260px;
        }

        .ecp-field > label {
            margin: 0;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .ecp-field .form-control {
            height: 34px;
            font-size: 0.86rem;
        }

        #estCategoryPickerTable tbody td {
            font-size: 0.86rem;
            vertical-align: middle;
        }

        #estCategoryPickerModal .ecp-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-est-pick-category {
            margin-bottom: 8px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var pickerTargetModal = null; // #itemCreateModal / #itemUpdateModal đang chờ nhận hoá chất
            var ecpTable = null;

            function initEcpTable() {
                if (ecpTable) {
                    ecpTable.columns.adjust();
                    return;
                }

                ecpTable = $('#estCategoryPickerTable').DataTable({
                    paging: true,
                    pageLength: 10,
                    lengthChange: false,
                    info: true,
                    autoWidth: false,
                    order: [[1, 'asc']],
                    columnDefs: [{ orderable: false, targets: [0, 5] }],
                    dom: 'rt<"ecp-foot"ip>',
                    language: {
                        info: 'Hiện _START_–_END_ / _TOTAL_ hoá chất',
                        infoEmpty: 'Không có hoá chất',
                        infoFiltered: '(lọc từ _MAX_)',
                        zeroRecords: 'Không tìm thấy hoá chất phù hợp',
                        paginate: { previous: 'Trước', next: 'Sau' }
                    }
                });

                $('#ecpSearch').on('keyup', function() {
                    ecpTable.search(this.value).draw();
                });
            }

            // Bảng nằm trong modal ẩn -> khởi tạo khi modal hiện ra để cột không bị tính sai
            $('#estCategoryPickerModal').on('shown.bs.modal', function() {
                initEcpTable();
            });

            /* ---------- Mở bảng chọn từ nút trước ô select[name="category_id"] ---------- */
            $(document).on('click', '.est-pick-category', function() {
                pickerTargetModal = $($(this).data('target-modal') || '#itemCreateModal');
                $('#estCategoryPickerModal').modal('show');
            });

            /* ---------- Chọn một hoá chất ---------- */
            $(document).on('click', '.ecp-choose', function() {
                if (!pickerTargetModal || !pickerTargetModal.length) return;

                var id = String($(this).data('id'));
                var $sel = pickerTargetModal.find('select[name="category_id"]');
                if (!$sel.length) return;

                $sel.val(id).trigger('change');

                $('#estCategoryPickerModal').modal('hide');
            });

            /* ---------- Modal chọn xếp chồng lên modal Thêm / Sửa mặt hàng ---------- */
            $('#estCategoryPickerModal').on('show.bs.modal', function() {
                $(this).css('z-index', 1075);
                setTimeout(function() {
                    $('.modal-backdrop').last().css('z-index', 1070);
                }, 0);
            });

            $('#estCategoryPickerModal').on('hidden.bs.modal', function() {
                // Bootstrap gỡ .modal-open khi đóng modal con -> trả lại cho modal cha còn mở
                if ($('.modal:visible').length) $(document.body).addClass('modal-open');
            });
        });
    </script>
@endonce
