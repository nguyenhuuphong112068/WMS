{{--
|--------------------------------------------------------------------------
| NHẬP VẬT TƯ - MODAL CHỌN ĐỐI TƯỢNG CHO Ô "MỤC ĐÍCH SỬ DỤNG"
|--------------------------------------------------------------------------
| Mở từ nút .mi-open-purpose-objects cạnh ô Mục đích sử dụng của modal Nhập vật tư, Sửa
| phiếu nhập và Xác nhận kiểm tra. Chọn 1 hoặc nhiều đối tượng (dữ liệu gốc
| consumption_objects) rồi "Điền vào mục đích" -> nối "Tên (Mã)" của các đối tượng vào ô,
| cách nhau bởi "; ", bỏ qua đối tượng đã có trong ô.
|
| Danh mục đối tượng có hàng nghìn dòng nên tải AJAX theo trang 50 dòng
| (MaterialImportController::purposeObjects), cuộn tới cuối bảng thì tải trang kế. Lựa chọn
| giữ lại khi gõ tìm / đổi bộ lọc. Mặc định chỉ hiện đối tượng đã gắn với vật tư đang chọn
| (danh sách đề nghị nội bộ theo chu kỳ / dòng đề nghị cấp phát của phòng), chưa có thì hiện tất cả.
--}}
@php
    $poTypes = \App\Http\Controllers\Pages\MaterData\ConsumptionObjectController::typeLabels();
@endphp

<div class="modal fade md-modal imp-pick" id="purposeObjectModal" tabindex="-1" role="dialog" aria-hidden="true"
    data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered imp-pick-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-crosshairs"></i> Đối Tượng
                    <span class="md-tag imp-pick-total po-total">0 đối tượng</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="imp-pick-toolbar">
                    <div class="input-group imp-pick-search-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="search" class="form-control imp-pick-search po-search"
                            placeholder="Tìm theo mã, tên, vị trí đối tượng...">
                    </div>

                    <select class="form-control imp-pick-filter-select imp-pick-quick po-scope">
                        <option value="linked">Đã gắn với vật tư này</option>
                        <option value="all">Tất cả đối tượng</option>
                    </select>

                    <select class="form-control imp-pick-filter-select imp-pick-quick po-type">
                        <option value="">-- Tất cả loại --</option>
                        @foreach ($poTypes as $poTypeKey => $poTypeLabel)
                            <option value="{{ $poTypeKey }}">{{ $poTypeLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="imp-pick-wrap po-wrap">
                    <table class="table table-hover table-sm imp-pick-table" data-no-datatable>
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 42px">
                                    <input type="checkbox" class="po-check-all" title="Chọn tất cả dòng đang thấy">
                                </th>
                                <th class="text-center" style="width: 52px">STT</th>
                                <th style="width: 130px">Mã Đối Tượng</th>
                                <th style="min-width: 220px">Tên Đối Tượng</th>
                                <th style="min-width: 150px">Loại</th>
                                <th style="min-width: 140px">Vị Trí</th>
                                <th style="min-width: 130px">Tần Suất</th>
                            </tr>
                        </thead>
                        <tbody class="po-tbody"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-between align-items-center">
                <span class="po-selected"><i class="fas fa-check-circle mr-1"></i> Đã chọn: <b>0</b> đối tượng</span>
                <div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary po-confirm" disabled>
                        <i class="fas fa-check mr-1"></i> Điền vào mục đích
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .po-row { cursor: pointer; }
    .po-row.is-checked td { background: var(--primary-soft); }
    .po-linked { font-size: 0.68rem; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var PO_URL = @json(route('pages.import.materialImport.purposeObjects'));
        var $po = $('#purposeObjectModal');
        var $tbody = $po.find('.po-tbody');
        var state = { seq: 0 };
        var selected = {};       // id -> {code, name} - giữ qua các lần tìm / lọc
        var $target = null;      // ô textarea Mục đích đang điền
        var searchTimer = null;

        function poLabel(object) {
            var name = String(object.name || '').trim();
            var code = String(object.code || '').trim();
            return code ? name + ' (' + code + ')' : name;
        }

        function poSyncSelected() {
            var n = Object.keys(selected).length;
            $po.find('.po-selected b').text(n);
            $po.find('.po-confirm').prop('disabled', !n);

            var $checks = $tbody.find('.po-check');
            $po.find('.po-check-all').prop('checked', $checks.length && $checks.filter(':checked').length === $checks.length);
        }

        function poLoad(append) {
            if (append) {
                if (state.loading || !state.more) return;
            } else {
                state = { seq: state.seq + 1, offset: 0, more: false, count: 0 };
                $tbody.html('<tr><td colspan="7" class="text-center md-empty py-4">'
                    + '<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải...</td></tr>');
                $po.find('.po-wrap').scrollTop(0);
            }

            state.loading = true;
            var seq = state.seq;

            $.getJSON(PO_URL, {
                q: $po.find('.po-search').val() || '',
                scope: $po.find('.po-scope').val(),
                type: $po.find('.po-type').val() || '',
                category_id: $po.data('categoryId') || '',
                offset: state.offset
            }).done(function(data) {
                if (seq !== state.seq) return;

                state.loading = false;
                state.offset = data.next_offset || 0;
                state.more = !!data.more;

                poRender(data.items || [], append);
                $po.find('.po-total').text(Number(data.total || 0).toLocaleString('vi-VN') + ' đối tượng');
            }).fail(function() {
                if (seq !== state.seq) return;

                state.loading = false;
                $tbody.find('.po-more').remove();
                if (!append) {
                    $tbody.html('<tr><td colspan="7" class="text-center md-empty py-4">'
                        + 'Không tải được danh sách đối tượng. Vui lòng thử lại.</td></tr>');
                }
            });
        }

        function poRender(items, append) {
            $tbody.find('.po-more').remove();
            if (!append) $tbody.empty();

            if (!append && !items.length) {
                var linked = $po.find('.po-scope').val() === 'linked';
                $tbody.html('<tr><td colspan="7" class="text-center md-empty py-4">'
                    + (linked ? 'Vật tư này chưa gắn đối tượng nào. Chọn "Tất cả đối tượng" để tìm.'
                        : 'Không tìm thấy đối tượng phù hợp.') + '</td></tr>');
                poSyncSelected();
                return;
            }

            items.forEach(function(object) {
                state.count++;
                var isChecked = !!selected[object.id];

                var $row = $('<tr>').addClass('po-row').toggleClass('is-checked', isChecked)
                    .attr('data-id', object.id).data('object', object);

                $row.append($('<td>').addClass('text-center').append(
                    $('<input>').attr('type', 'checkbox').addClass('po-check').prop('checked', isChecked)
                ));
                $row.append($('<td>').addClass('text-center md-sub').text(state.count));
                $row.append($('<td>').append($('<span>').addClass('md-tag').text(object.code || '—')));

                var $name = $('<td>').append($('<span>').addClass('font-weight-bold').text(object.name));
                if (object.linked) {
                    $name.append($('<span>').addClass('badge badge-info ml-1 po-linked').text('Đã gắn với vật tư'));
                }
                $row.append($name);

                $row.append($('<td>').addClass('md-sub').text(object.type_label || '—'));
                $row.append($('<td>').addClass('md-sub').text(object.location || '—'));
                $row.append($('<td>').addClass('md-sub').text(object.frequency_label || '—'));

                $tbody.append($row);
            });

            if (state.more) {
                $tbody.append($('<tr>').addClass('po-more').append(
                    $('<td>').attr('colspan', 7).addClass('text-center py-2')
                        .append($('<a href="#">').html('<i class="fas fa-angle-double-down mr-1"></i> Tải thêm đối tượng'))
                ));
            }

            poSyncSelected();
        }

        function poToggle($row, checked) {
            var object = $row.data('object');
            if (!object) return;

            $row.toggleClass('is-checked', checked).find('.po-check').prop('checked', checked);
            if (checked) {
                selected[object.id] = { code: object.code, name: object.name };
            } else {
                delete selected[object.id];
            }
        }

        // Nút mở modal: lấy vật tư của form (ô category_id, hoặc data đã gán khi mở modal Kiểm tra)
        $(document).on('click', '.mi-open-purpose-objects', function() {
            var $form = $(this).closest('form');
            $target = $form.find('textarea[name="purpose"]');

            var categoryId = $form.find('[name="category_id"]').val() || $form.data('categoryId') || '';
            $po.data('categoryId', categoryId);

            selected = {};
            $po.find('.po-search').val('');
            $po.find('.po-type').val('');
            $po.find('.po-scope').val(categoryId ? 'linked' : 'all');

            poLoad(false);
            $po.modal('show');
        });

        $po.on('shown.bs.modal', function() {
            // Lớp phủ phải che luôn modal nhập / sửa / kiểm tra phía dưới
            $('.modal-backdrop').last().css('z-index', 1055);
            $po.find('.po-search').trigger('focus');
        });

        $po.on('hidden.bs.modal', function() {
            if ($('.modal.show').length) $('body').addClass('modal-open');
        });

        // Sự kiện scroll không nổi bọt nên gắn thẳng vào vùng cuộn
        $po.find('.po-wrap').on('scroll', function() {
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 80) poLoad(true);
        });

        $po.on('click', '.po-more', function(e) {
            e.preventDefault();
            poLoad(true);
        });

        $po.on('input', '.po-search', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() { poLoad(false); }, 300);
        });

        $po.on('change', '.po-scope, .po-type', function() {
            poLoad(false);
        });

        $po.on('click', '.po-row', function(e) {
            var $row = $(this);
            var checked = $(e.target).is('.po-check') ? e.target.checked : !$row.hasClass('is-checked');
            poToggle($row, checked);
            poSyncSelected();
        });

        $po.on('change', '.po-check-all', function() {
            var checked = this.checked;
            $tbody.find('.po-row').each(function() { poToggle($(this), checked); });
            poSyncSelected();
        });

        $po.on('click', '.po-confirm', function() {
            if (!$target || !$target.length) return;

            var current = String($target.val() || '').trim();
            var parts = current ? [current] : [];

            Object.keys(selected).forEach(function(id) {
                var label = poLabel(selected[id]);
                if (label && current.indexOf(label) === -1) parts.push(label);
            });

            var text = parts.join('; ');
            var max = parseInt($target.attr('maxlength'), 10) || 500;

            if (text.length > max) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Mục đích quá dài',
                    text: 'Ô Mục đích sử dụng tối đa ' + max + ' ký tự. Vui lòng bớt đối tượng đã chọn.'
                });
                return;
            }

            $target.val(text).trigger('input').trigger('change');
            $po.modal('hide');
        });
    });
</script>
