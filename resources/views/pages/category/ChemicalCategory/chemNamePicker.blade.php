{{--
|--------------------------------------------------------------------------
| DANH MỤC - HOÁ CHẤT | Bảng chọn "Tên Hoá Chất" từ dữ liệu gốc
|--------------------------------------------------------------------------
| Mở từ nút ".cat-pick-chem" đặt trong modal Thêm / Cập nhật danh mục
| (nút mang data-target-form="#createModal" hoặc "#updateModal").
|
| Một danh mục chỉ mang MỘT tên hoá chất: bảng này cho tìm kiếm + lọc theo
| nhóm NĐ 24/2026 rồi bấm "Chọn" để đổ vào ô select[name="chem_names_id"]
| của form tương ứng. Sau khi chọn, ô xem nhanh [data-cls-preview] trong
| modal đó hiển thị các nhóm phân loại suy được của hoá chất.
|
| Danh sách hoá chất ($chemNames) và nhóm phân loại ($chemNameGroups) lấy
| từ ChemicalCategoryController::index(). Dùng lại toàn bộ CSS md-*/cat-*.
--}}

@php
    // Mã nhóm NĐ 24/2026 (N1..N10) theo từng tên hoá chất, để lọc bảng + vẽ chip.
    $cnpGroupCodes = [];
    foreach ($chemNameGroups as $chemId => $groups) {
        $cnpGroupCodes[$chemId] = array_map(fn ($g) => 'N' . $g, $groups);
    }
    $cnpClsList = \App\Support\ChemicalClassification::labels();
@endphp

<div class="modal fade md-modal" id="chemNamePickerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-flask mr-2"></i> Chọn Tên Hoá Chất Từ Dữ Liệu Gốc
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p class="md-hint">
                    <i class="fas fa-info-circle mr-1"></i>
                    Một danh mục chỉ mang <b>một</b> tên hoá chất. Tìm nhanh theo tên hoặc số CAS,
                    lọc theo nhóm NĐ 24/2026, rồi bấm <b>Chọn</b>.
                </p>

                <div class="cnp-filters">
                    <div class="cnp-field cnp-field-search">
                        <label><i class="fas fa-search mr-1 text-primary"></i> Tìm kiếm</label>
                        <input type="text" class="form-control" id="cnpSearch"
                            placeholder="Tên hoá chất, số CAS...">
                    </div>
                    <div class="cnp-field">
                        <label><i class="fas fa-filter mr-1 text-primary"></i> Nhóm NĐ 24/2026</label>
                        <select class="form-control" id="cnpGroup">
                            <option value="all">Tất cả</option>
                            <option value="none">Chưa phân loại</option>
                            @foreach ($cnpClsList as $code => $name)
                                <option value="{{ $code }}" title="{{ $name }}">{{ $code }} — {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="chemNamePickerTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 52px">STT</th>
                                <th>Tên Hoá Chất</th>
                                <th style="width: 150px">Số CAS</th>
                                <th style="width: 200px">Phân Loại NĐ 24/2026</th>
                                <th class="text-center" style="width: 80px">Chọn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chemNames as $cn)
                                @php
                                    $codes = $cnpGroupCodes[$cn->id] ?? [];
                                @endphp
                                <tr data-classification="{{ implode(',', $codes) }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="font-weight-bold">{{ $cn->name }}</span>
                                    </td>
                                    <td class="md-sub">{{ $cn->cas_no ?: '—' }}</td>
                                    <td data-order="{{ $codes ? (int) ltrim($codes[0], 'N') : 99 }}">
                                        @if ($codes)
                                            <div class="cat-chips">
                                                @foreach ($codes as $code)
                                                    <span
                                                        class="cat-chip {{ \App\Support\ChemicalClassification::toneOfCode($code) }}"
                                                        title="{{ $cnpClsList[$code] ?? $code }}">{{ $code }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="md-empty">Chưa phân loại</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary cnp-choose"
                                            data-id="{{ $cn->id }}"
                                            data-name="{{ $cn->name }}{{ $cn->cas_no ? ' (CAS: ' . $cn->cas_no . ')' : '' }}"
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
        #chemNamePickerModal .modal-dialog {
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

        #chemNamePickerTable tbody td {
            font-size: 0.86rem;
            vertical-align: middle;
        }

        #chemNamePickerModal .cnp-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        /* Ô xem nhanh nhóm phân loại trong modal Thêm / Cập nhật danh mục */
        .cat-cls-preview {
            border: 1px dashed var(--primary-lighter);
            border-radius: var(--border-radius-md, 8px);
            padding: 8px 10px;
            background: var(--primary-soft);
            min-height: 38px;
        }

        .cat-cls-preview .cat-chips {
            gap: 6px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var CNP_GROUP_CODES = @json($cnpGroupCodes);
            var CNP_CLS_LABELS = @json($cnpClsList);
            var CRITICAL = ['N9', 'N10'],
                BANNED = ['N4', 'N6'];

            var pickerTargetForm = null; // form (#createModal / #updateModal) đang chờ nhận hoá chất
            var cnpTable = null;
            var cnpGroupWant = 'all';

            /* ---------- Lọc theo nhóm NĐ 24/2026, chỉ áp cho bảng chọn ---------- */
            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                if (settings.nTable.id !== 'chemNamePickerTable') return true;
                if (cnpGroupWant === 'all') return true;

                var raw = ($(settings.aoData[index].nTr).attr('data-classification') || '').trim();
                var codes = raw ? raw.split(',') : [];

                return cnpGroupWant === 'none' ? codes.length === 0 : codes.indexOf(cnpGroupWant) !== -1;
            });

            function initCnpTable() {
                if (cnpTable) {
                    cnpTable.columns.adjust();
                    return;
                }

                cnpTable = $('#chemNamePickerTable').DataTable({
                    paging: true,
                    pageLength: 10,
                    lengthChange: false,
                    info: true,
                    autoWidth: false,
                    order: [
                        [1, 'asc']
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: [0, 4]
                    }],
                    dom: 'rt<"cnp-foot"ip>',
                    language: {
                        info: 'Hiện _START_–_END_ / _TOTAL_ hoá chất',
                        infoEmpty: 'Không có hoá chất',
                        infoFiltered: '(lọc từ _MAX_)',
                        zeroRecords: 'Không tìm thấy hoá chất phù hợp',
                        paginate: {
                            previous: 'Trước',
                            next: 'Sau'
                        }
                    }
                });

                $('#cnpSearch').on('keyup', function() {
                    cnpTable.search(this.value).draw();
                });

                $('#cnpGroup').on('change', function() {
                    cnpGroupWant = this.value;
                    cnpTable.draw();
                });
            }

            // Bảng nằm trong modal ẩn -> khởi tạo khi modal hiện ra để cột không bị tính sai
            $('#chemNamePickerModal').on('shown.bs.modal', function() {
                initCnpTable();
            });

            /* ---------- Mở bảng chọn từ nút trong modal Thêm / Cập nhật ---------- */
            $(document).on('click', '.cat-pick-chem', function() {
                pickerTargetForm = $($(this).data('target-form') || '#createModal');
                $('#chemNamePickerModal').modal('show');
            });

            /* ---------- Chọn một hoá chất ---------- */
            $(document).on('click', '.cnp-choose', function() {
                if (!pickerTargetForm || !pickerTargetForm.length) return;

                var id = String($(this).data('id'));
                var $sel = pickerTargetForm.find('select[name="chem_names_id"]');
                if (!$sel.length) return;

                if (!$sel.find('option[value="' + id + '"]').length) {
                    $sel.append(new Option($(this).data('name'), id, true, true));
                }
                $sel.val(id).trigger('change');

                $('#chemNamePickerModal').modal('hide');
                renderClsPreview(pickerTargetForm.closest('.md-modal'));
            });

            /* ---------- Ô xem nhanh nhóm phân loại trong modal Thêm / Cập nhật ---------- */
            function renderClsPreview($modal) {
                if (!$modal || !$modal.length) return;

                var $box = $modal.find('[data-cls-preview]');
                if (!$box.length) return;

                var id = String($modal.find('select[name="chem_names_id"]').val() || '');
                var codes = CNP_GROUP_CODES[id] || [];
                $box.empty();

                if (!id) {
                    $box.append($('<span class="md-empty">')
                        .text('Chọn tên hoá chất để xem nhóm phân loại NĐ 24/2026.'));
                    return;
                }

                if (!codes.length) {
                    $box.append($('<span class="md-empty">')
                        .text('Tên hoá chất này chưa thuộc nhóm nào của NĐ 24/2026.'));
                    return;
                }

                var $chips = $('<div class="cat-chips">');
                codes.forEach(function(code) {
                    var tone = CRITICAL.indexOf(code) !== -1 ? 'critical' :
                        (BANNED.indexOf(code) !== -1 ? 'banned' : '');
                    $chips.append($('<span class="cat-chip">').addClass(tone)
                        .attr('title', CNP_CLS_LABELS[code] || code).text(code));
                });
                $box.append($chips);
            }

            $(document).on('change', '.md-modal select[name="chem_names_id"]', function() {
                renderClsPreview($(this).closest('.md-modal'));
            });

            $(document).on('shown.bs.modal', '.md-modal', function() {
                if (this.id === 'chemNamePickerModal') return;
                renderClsPreview($(this));
            });

            /* ---------- Modal chọn xếp chồng lên modal Thêm / Cập nhật ---------- */
            $('#chemNamePickerModal').on('show.bs.modal', function() {
                $(this).css('z-index', 1075);
                setTimeout(function() {
                    $('.modal-backdrop').last().css('z-index', 1070);
                }, 0);
            });

            $('#chemNamePickerModal').on('hidden.bs.modal', function() {
                // Bootstrap gỡ .modal-open khi đóng modal con -> trả lại cho modal cha còn mở
                if ($('.modal:visible').length) $(document.body).addClass('modal-open');
            });
        });
    </script>
@endonce
