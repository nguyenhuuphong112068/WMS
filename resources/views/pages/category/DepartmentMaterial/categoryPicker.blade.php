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

@php
    use App\Support\MaterialClassification;

    $dmcManufacturers = collect($categories)
        ->map(fn($c) => $c->manufacturer_short_name ?: $c->manufacturer_name)
        ->filter()->unique()->sort()->values();
@endphp

<div class="modal fade md-modal" id="dmCategoryPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
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
                    theo mã, tên, nhà sản xuất hoặc thông tin kỹ thuật rồi bấm <b>Chọn</b>.
                </p>

                <div class="cnp-filters">
                    <div class="cnp-field cnp-field-search">
                        <label><i class="fas fa-search mr-1 text-primary"></i> Tìm kiếm</label>
                        <input type="text" class="form-control" id="dmcSearch"
                            placeholder="Mã, tên vật tư, nhà sản xuất, part number...">
                    </div>
                    <div class="cnp-field cnp-field-mfr">
                        <label><i class="fas fa-industry mr-1 text-primary"></i> Nhà sản xuất</label>
                        <select class="form-control" id="dmcManufacturer">
                            <option value="">Tất cả</option>
                            @foreach ($dmcManufacturers as $manufacturer)
                                <option value="{{ $manufacturer }}">{{ $manufacturer }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cnp-field cnp-field-class">
                        <label><i class="fas fa-filter mr-1 text-primary"></i> Phân loại</label>
                        <select class="form-control" id="dmcClassFilter" multiple data-placeholder="Tất cả">
                            <option value="none">Chưa phân loại</option>
                            @foreach (MaterialClassification::formGroups() as $group)
                                @php $filterKey = str_replace('classification.', '', $group['errorKey']); @endphp
                                <optgroup label="{{ $group['label'] }}">
                                    @foreach ($group['options'] as $value => $name)
                                        <option value="{{ $filterKey }}:{{ $value }}">{{ $name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="dmCategoryPickerTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 52px">STT</th>
                                <th style="width: 100px">Mã Vật Tư</th>
                                <th>Tên Vật Tư</th>
                                <th style="width: 150px">Nhà Sản Xuất</th>
                                <th style="width: 120px">Part Number</th>
                                <th>Thông Tin Kỹ Thuật</th>
                                <th style="width: 200px">Phân Loại</th>
                                <th style="width: 120px">Bộ Phận Mua Hàng</th>
                                <th class="text-center" style="width: 95px">Thời Gian Đặt Hàng</th>
                                <th class="text-center" style="width: 70px">Chọn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                @php
                                    $dmcClassKeys = collect(MaterialClassification::decode($category->classification))
                                        ->map(fn($value, $key) => $key . ':' . $value)->implode(' ');
                                    $dmcChips = MaterialClassification::chips($category->classification);
                                    $dmcManufacturer = $category->manufacturer_short_name ?: $category->manufacturer_name;
                                @endphp
                                <tr data-classification="{{ $dmcClassKeys }}"
                                    data-purchasing="{{ $category->purchasing_department ? 'purchasing_department:' . $category->purchasing_department : '' }}"
                                    data-manufacturer="{{ $dmcManufacturer }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="text-nowrap">{{ $category->code ?: '—' }}</td>
                                    <td class="font-weight-bold">{{ $category->material_name ?: '—' }}</td>
                                    <td class="md-sub">
                                        {{ $category->manufacturer_name ?: '—' }}
                                        @if ($category->manufacturer_short_name)
                                            <br><span class="md-tag">{{ $category->manufacturer_short_name }}</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $category->part_number ?: '—' }}</td>
                                    <td class="md-sub">{{ $category->technical_specification ?: '—' }}</td>
                                    <td>
                                        @if ($dmcChips)
                                            <div class="cat-chips">
                                                @foreach ($dmcChips as $chip)
                                                    <span class="cat-chip {{ $chip['class'] }}"
                                                        title="{{ $chip['label'] }}">{{ $chip['short'] }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="md-empty">Chưa phân loại</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">
                                        {{ MaterialClassification::purchasingLabel($category->purchasing_department) ?: '—' }}
                                    </td>
                                    <td class="text-center">
                                        @if ($category->lead_time_days !== null)
                                            <span class="font-weight-bold">{{ $category->lead_time_days }}</span>
                                            <span class="md-sub">ngày</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary dmc-choose"
                                            data-id="{{ $category->id }}"
                                            data-name="{{ $category->code ? '[' . $category->code . '] ' : '' }}{{ $category->material_name }}{{ $dmcManufacturer ? ' (' . $dmcManufacturer . ')' : '' }}"
                                            title="Chọn vật tư này">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center md-empty py-3">
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
            max-width: 90vw;
            width: 90vw;
        }

        #dmCategoryPickerModal .modal-content {
            height: 90vh;
        }

        .cnp-field-mfr {
            flex: 0 1 220px;
        }

        .cnp-field-class {
            flex: 1 1 360px;
        }

        .cnp-field-class .select2-container {
            width: 100% !important;
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

            $('#dmcClassFilter').select2({
                theme: 'bootstrap4',
                placeholder: 'Tất cả',
                closeOnSelect: false,
                allowClear: true,
                width: '100%',
                dropdownParent: $('#dmCategoryPickerModal')
            });

            /*
             * Lọc theo Nhà sản xuất + Phân loại (chọn nhiều): cùng tiêu chí HOẶC, khác tiêu chí VÀ;
             * "Chưa phân loại" cộng thêm các dòng chưa khai phân loại.
             */
            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                if (settings.nTable.id !== 'dmCategoryPickerTable') return true;

                var $tr = $(settings.aoData[index].nTr);
                var manufacturer = $('#dmcManufacturer').val();
                if (manufacturer && $tr.attr('data-manufacturer') !== manufacturer) return false;

                var want = $('#dmcClassFilter').val() || [];
                if (!want.length) return true;

                var classKeys = ($tr.attr('data-classification') || '').trim();
                var keys = ' ' + classKeys + ' ' + ($tr.attr('data-purchasing') || '') + ' ';
                var wantNone = false;
                var groups = {};

                want.forEach(function(value) {
                    if (value === 'none') {
                        wantNone = true;
                        return;
                    }
                    var criterion = value.split(':')[0];
                    (groups[criterion] = groups[criterion] || []).push(value);
                });

                if (wantNone && classKeys === '') return true;

                var criteria = Object.keys(groups);
                if (!criteria.length) return false;

                return criteria.every(function(criterion) {
                    return groups[criterion].some(function(value) {
                        return keys.indexOf(' ' + value + ' ') !== -1;
                    });
                });
            });

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

                $('#dmcManufacturer, #dmcClassFilter').off('change').on('change', function() {
                    dmcTable.draw();
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
