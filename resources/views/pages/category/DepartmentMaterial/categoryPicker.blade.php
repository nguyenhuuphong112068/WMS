{{--
|--------------------------------------------------------------------------
| DANH MỤC - VẬT TƯ | Bảng chọn "Vật Tư" từ Danh Mục Vật Tư Công Ty
|--------------------------------------------------------------------------
| Mở từ nút ".dmc-pick-category" đặt trong modal "Khai Vật Tư Cho Phòng"
| (nút mang data-target-form="#dmCreateModal").
|
| Ô select[name="category_id"] của modal đó đã có Select2 (tìm theo tên),
| nhưng chỉ hiện được tên vật tư nên khó phân biệt các dòng trùng tên khác
| nhà sản xuất. Bảng này cho xem đủ Tên / Nhà Sản Xuất / Thông Tin Kỹ Thuật,
| tìm nhanh rồi bấm "Chọn" để đổ vào select.
|
| Danh sách $categories lấy từ DepartmentMaterial::categoryOptions() - chỉ
| gồm vật tư đã duyệt trong danh mục chung và phòng CHƯA khai, khớp với
| chính danh sách đã có sẵn trong select. Dùng lại toàn bộ CSS md-*/cat-*
| và khung bảng chọn cnp-* của chemNamePicker.
--}}

<div class="modal fade md-modal" id="dmCategoryPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cubes mr-2"></i> Chọn Vật Tư Từ Danh Mục Công Ty
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p class="md-hint">
                    <i class="fas fa-info-circle mr-1"></i>
                    Chỉ hiện vật tư đã duyệt trong danh mục chung và <b>phòng chưa khai</b>. Tìm nhanh
                    theo tên, nhà sản xuất hoặc thông tin kỹ thuật rồi bấm <b>Chọn</b>.
                </p>

                <div class="cnp-filters">
                    <div class="cnp-field cnp-field-search">
                        <label><i class="fas fa-search mr-1 text-primary"></i> Tìm kiếm</label>
                        <input type="text" class="form-control" id="dmcSearch"
                            placeholder="Tên vật tư, nhà sản xuất...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="dmCategoryPickerTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 52px">STT</th>
                                <th>Tên Vật Tư</th>
                                <th style="width: 180px">Nhà Sản Xuất</th>
                                <th>Thông Tin Kỹ Thuật</th>
                                <th class="text-center" style="width: 80px">Chọn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="font-weight-bold">{{ $category->material_name ?: '—' }}</td>
                                    <td class="md-sub">
                                        {{ $category->manufacturer_short_name ?: $category->manufacturer_name ?: '—' }}
                                    </td>
                                    <td class="md-sub">{{ $category->technical_specification ?: '—' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary dmc-choose"
                                            data-id="{{ $category->id }}"
                                            data-name="{{ $category->material_name }}{{ $category->manufacturer_short_name ? ' (' . $category->manufacturer_short_name . ')' : ($category->manufacturer_name ? ' (' . $category->manufacturer_name . ')' : '') }}"
                                            title="Chọn vật tư này">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center md-empty py-3">
                                        Phòng đã khai hết vật tư trong danh mục chung.
                                    </td>
                                </tr>
                            @endforelse
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
        #dmCategoryPickerModal .modal-dialog {
            max-width: 900px;
        }

        .cnp-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 12px;
        }

        .cnp-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cnp-field-search {
            flex: 1 1 260px;
        }

        .cnp-field > label {
            margin: 0;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .cnp-field .form-control {
            height: 34px;
            font-size: 0.86rem;
        }

        #dmCategoryPickerTable tbody td {
            font-size: 0.86rem;
            vertical-align: middle;
        }

        #dmCategoryPickerModal .dmc-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var pickerTargetForm = null; // form (#dmCreateModal) đang chờ nhận vật tư
            var dmcTable = null;

            function initDmcTable() {
                if (dmcTable) {
                    dmcTable.columns.adjust();
                    return;
                }

                if (!$.fn.dataTable.isDataTable('#dmCategoryPickerTable')) {
                    dmcTable = $('#dmCategoryPickerTable').DataTable({
                        paging: true,
                        pageLength: 10,
                        lengthChange: false,
                        info: true,
                        autoWidth: false,
                        ordering: false,
                        dom: 'rt<"dmc-foot"ip>',
                        language: {
                            info: 'Hiện _START_–_END_ / _TOTAL_ vật tư',
                            infoEmpty: 'Không có vật tư',
                            infoFiltered: '(lọc từ _MAX_)',
                            zeroRecords: 'Không tìm thấy vật tư phù hợp',
                            paginate: {
                                previous: 'Trước',
                                next: 'Sau'
                            }
                        }
                    });
                }

                $('#dmcSearch').off('keyup').on('keyup', function() {
                    dmcTable.search(this.value).draw();
                });
            }

            // Bảng nằm trong modal ẩn -> khởi tạo khi modal hiện ra để cột không bị tính sai
            $('#dmCategoryPickerModal').on('shown.bs.modal', function() {
                initDmcTable();
            });

            /* ---------- Mở bảng chọn từ nút trong modal Khai Vật Tư Cho Phòng ---------- */
            $(document).on('click', '.dmc-pick-category', function() {
                pickerTargetForm = $($(this).data('target-form') || '#dmCreateModal');
                $('#dmCategoryPickerModal').modal('show');
            });

            /* ---------- Chọn một vật tư ---------- */
            $(document).on('click', '.dmc-choose', function() {
                if (!pickerTargetForm || !pickerTargetForm.length) return;

                var id = String($(this).data('id'));
                var $sel = pickerTargetForm.find('select[name="category_id"]');
                if (!$sel.length) return;

                if (!$sel.find('option[value="' + id + '"]').length) {
                    $sel.append(new Option($(this).data('name'), id, true, true));
                }
                $sel.val(id).trigger('change');

                $('#dmCategoryPickerModal').modal('hide');
            });

            /* ---------- Modal chọn xếp chồng lên modal Khai Vật Tư Cho Phòng ---------- */
            $('#dmCategoryPickerModal').on('show.bs.modal', function() {
                $(this).css('z-index', 1075);
                setTimeout(function() {
                    $('.modal-backdrop').last().css('z-index', 1070);
                }, 0);
            });

            $('#dmCategoryPickerModal').on('hidden.bs.modal', function() {
                // Bootstrap gỡ .modal-open khi đóng modal con -> trả lại cho modal cha còn mở
                if ($('.modal:visible').length) $(document.body).addClass('modal-open');
            });
        });
    </script>
@endonce
