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
| Danh sách hoá chất KHÔNG nhúng vào trang: mở bảng lần đầu mới nạp JSON từ
| CategoryLookupController::chemicalNames(). $chemNameGroups chỉ mang mã nhóm
| (N1..N10) của các tên danh mục đang dùng, cho ô xem nhanh ở modal Cập nhật.
| Dùng lại toàn bộ CSS md-*/cat-*.
--}}

@php
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
                    <table id="chemNamePickerTable" class="table table-bordered table-hover w-100"
                        data-url="{{ route('pages.category.lookup.chemicalNames') }}">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 52px">STT</th>
                                <th>Tên Hoá Chất</th>
                                <th style="width: 150px">Số CAS</th>
                                <th style="width: 200px">Phân Loại NĐ 24/2026</th>
                                <th class="text-center" style="width: 80px">Chọn</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
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
            // Mã nhóm theo tên hoá chất, bổ sung dần từ kết quả tìm ở ô chọn và dữ liệu bảng chọn
            var CNP_GROUP_CODES = @json((object) $chemNameGroups);
            var CNP_CLS_LABELS = @json($cnpClsList);
            var CRITICAL = ['N9', 'N10'],
                BANNED = ['N4', 'N6'];

            var pickerTargetForm = null; // form (#createModal / #updateModal) đang chờ nhận hoá chất
            var cnpTable = null;
            var cnpLoading = false;
            var cnpGroupWant = 'all';

            function cnpEscape(value) {
                return String(value === null || value === undefined ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function cnpCodes(row) {
                return (row.codes || []).map(function(item) {
                    return item.code;
                });
            }

            function cnpMessage(html) {
                $('#chemNamePickerTable tbody').html(
                    '<tr><td colspan="5" class="text-center md-sub py-4">' + html + '</td></tr>');
            }

            /* ---------- Lọc theo nhóm NĐ 24/2026, chỉ áp cho bảng chọn ---------- */
            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                if (settings.nTable.id !== 'chemNamePickerTable') return true;
                if (cnpGroupWant === 'all') return true;

                var codes = cnpCodes(settings.aoData[index]._aData || {});

                return cnpGroupWant === 'none' ? codes.length === 0 : codes.indexOf(cnpGroupWant) !== -1;
            });

            function buildCnpTable(rows) {
                rows.forEach(function(row) {
                    CNP_GROUP_CODES[row.id] = cnpCodes(row);
                });

                $('#chemNamePickerTable tbody').empty();

                cnpTable = $('#chemNamePickerTable').DataTable({
                    data: rows,
                    deferRender: true,
                    paging: true,
                    pageLength: 10,
                    lengthChange: false,
                    info: true,
                    autoWidth: false,
                    search: {
                        search: $('#cnpSearch').val() || ''
                    },
                    order: [
                        [1, 'asc']
                    ],
                    columns: [{
                            data: null,
                            className: 'text-center',
                            orderable: false,
                            render: function(value, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        {
                            data: 'name',
                            render: function(value, type) {
                                return type === 'display' ?
                                    '<span class="font-weight-bold">' + cnpEscape(value) + '</span>' : value;
                            }
                        },
                        {
                            data: 'cas_no',
                            className: 'md-sub',
                            render: function(value, type) {
                                return type === 'display' ? (value ? cnpEscape(value) : '—') : value;
                            }
                        },
                        {
                            data: 'codes',
                            render: function(codes, type, row) {
                                codes = codes || [];

                                if (type === 'sort' || type === 'type') {
                                    return codes.length ? parseInt(codes[0].code.replace(/\D/g, ''), 10) : 99;
                                }

                                if (type !== 'display') {
                                    return cnpCodes(row).join(' ');
                                }

                                if (!codes.length) {
                                    return '<span class="md-empty">Chưa phân loại</span>';
                                }

                                var html = '<div class="cat-chips">' + codes.map(function(item) {
                                    return '<span class="cat-chip ' + cnpEscape(item.tone) + '" title="' +
                                        cnpEscape(CNP_CLS_LABELS[item.code] || item.code) + '">' +
                                        cnpEscape(item.code) + '</span>';
                                }).join('') + '</div>';

                                if (row.special) {
                                    html += '<div class="mt-1"><span class="badge-special-control" ' +
                                        'title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">' +
                                        '<i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt</span></div>';
                                }

                                return html;
                            }
                        },
                        {
                            data: 'id',
                            className: 'text-center',
                            orderable: false,
                            searchable: false,
                            render: function(id) {
                                return '<button type="button" class="btn btn-sm btn-primary cnp-choose" data-id="' +
                                    cnpEscape(id) + '" title="Chọn hoá chất này"><i class="fas fa-check"></i></button>';
                            }
                        }
                    ],
                    dom: 'rt<"cnp-foot"ip>',
                    language: {
                        info: 'Hiện _START_–_END_ / _TOTAL_ hoá chất',
                        infoEmpty: 'Không có hoá chất',
                        infoFiltered: '(lọc từ _MAX_)',
                        zeroRecords: 'Không tìm thấy hoá chất phù hợp',
                        emptyTable: 'Chưa có tên hoá chất nào đã duyệt',
                        paginate: {
                            previous: 'Trước',
                            next: 'Sau'
                        }
                    }
                });
            }

            /* Nạp danh sách hoá chất khi mở bảng lần đầu, những lần sau chỉ tính lại bề rộng cột */
            function initCnpTable() {
                if (cnpTable) {
                    cnpTable.columns.adjust();
                    return;
                }

                if (cnpLoading) return;
                cnpLoading = true;

                cnpMessage('<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải danh sách hoá chất...');

                fetch($('#chemNamePickerTable').data('url'), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(response) {
                        if (!response.ok) throw new Error('http');
                        return response.json();
                    })
                    .then(function(json) {
                        buildCnpTable(json.rows || []);
                    })
                    .catch(function() {
                        cnpMessage('Không tải được danh sách hoá chất. Đóng bảng rồi mở lại để thử lại.');
                    })
                    .finally(function() {
                        cnpLoading = false;
                    });
            }

            $('#cnpSearch').on('keyup', function() {
                if (cnpTable) cnpTable.search(this.value).draw();
            });

            $('#cnpGroup').on('change', function() {
                cnpGroupWant = this.value;
                if (cnpTable) cnpTable.draw();
            });

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
                if (!pickerTargetForm || !pickerTargetForm.length || !cnpTable) return;

                var row = cnpTable.row($(this).closest('tr')).data() || {};
                var id = String(row.id || $(this).data('id'));
                var $sel = pickerTargetForm.find('select[name="chem_names_id"]');
                if (!$sel.length) return;

                if (!$sel.find('option').filter(function() {
                        return this.value === id;
                    }).length) {
                    $sel.append(new Option(row.text || id, id, true, true));
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
                // Chọn qua ô tìm (AJAX): nhóm phân loại đi kèm kết quả tìm của Select2
                var picked = $(this).data('select2') ? ($(this).select2('data') || [])[0] : null;
                if (picked && picked.id && Array.isArray(picked.groups)) {
                    CNP_GROUP_CODES[picked.id] = picked.groups;
                }

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
