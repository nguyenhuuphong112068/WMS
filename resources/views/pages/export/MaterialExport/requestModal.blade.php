@php $bag = $errors->getBag('requestCreateErrors'); @endphp

{{-- Dùng chung cho form tạo và form sửa đề nghị cấp phát vật tư. --}}
<style>
    /* Hàng thông tin chung + nút thao tác: một hàng, tự xuống dòng khi màn hình hẹp */
    .me-req-toolbar {
        display: flex;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 10px 14px;
    }
    .me-req-toolbar .me-field {
        display: flex;
        flex-direction: column;
    }
    .me-req-toolbar .me-field > label {
        margin-bottom: 3px;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-main);
        white-space: nowrap;
    }
    .me-req-toolbar .form-control {
        height: 36px;
        font-size: 0.88rem;
    }
    .me-req-toolbar .me-check {
        display: flex;
        align-items: center;
        gap: 6px;
        height: 36px;
        padding: 0 12px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-main);
        background: var(--primary-soft);
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        white-space: nowrap;
        cursor: pointer;
    }
    .me-req-toolbar .btn {
        height: 36px;
        white-space: nowrap;
    }

    /* Khối khai quy trình ký duyệt: số bước và người ký từng bước */
    .me-flow-box {
        border: 1px solid #dbeafe;
        border-radius: 10px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 14px 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .me-flow-head {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 12px;
    }
    .me-flow-title-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .me-flow-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: #e0f2fe;
        color: var(--primary, #2E7BC4);
        font-size: 0.85rem;
    }
    .me-flow-label {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e3a8a;
        margin: 0;
        letter-spacing: -0.2px;
    }
    .me-flow-count {
        font-size: 0.76rem;
        font-weight: 600;
        color: #0369a1;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        border-radius: 999px;
        padding: 2px 10px;
        line-height: 1.4;
    }
    .me-flow-hint {
        font-size: 0.78rem;
        color: #64748b;
        margin-left: 4px;
    }
    .me-flow-steps {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .me-flow-steps:empty {
        display: none;
    }
    .me-step-row {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 3.5px solid var(--primary, #2E7BC4);
        border-radius: 8px;
        padding: 8px 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        transition: all 0.2s ease;
    }
    .me-step-row:hover {
        border-color: #cbd5e1;
        border-left-color: var(--primary-dark, #1F5E9E);
        box-shadow: 0 2px 6px rgba(46, 123, 196, 0.08);
    }
    .me-step-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
    }
    .me-step-no {
        width: 26px;
        height: 26px;
        line-height: 26px;
        text-align: center;
        border-radius: 50%;
        background: linear-gradient(135deg, #2E7BC4 0%, #1F5E9E 100%);
        color: #ffffff;
        font-size: 0.78rem;
        font-weight: 700;
        box-shadow: 0 1px 3px rgba(46, 123, 196, 0.25);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .me-step-tag {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        white-space: nowrap;
    }
    /* Dòng bước ký CHỈ ĐỌC: tên người ký nổi, vai trò + phòng ban ở dòng phụ */
    .me-step-who {
        display: flex;
        flex-direction: column;
        gap: 1px;
        min-width: 0;
        flex: 1 1 auto;
    }
    .me-step-name {
        font-size: 0.875rem;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.35;
    }
    .me-step-or {
        color: #94a3b8;
        font-weight: 400;
        font-style: italic;
        font-size: 0.78rem;
        margin: 0 5px;
    }
    .me-step-role {
        font-size: 0.76rem;
        color: #64748b;
        line-height: 1.3;
    }
    .me-step-warn {
        font-size: 0.74rem;
        font-weight: 600;
        color: #B45309;
        margin-top: 2px;
    }
    .me-flow-none {
        background: #ffffff;
        border: 1.5px dashed #cbd5e1;
        border-radius: 8px;
        padding: 12px 16px;
        color: #64748b;
        font-size: 0.82rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Bảng dòng đề nghị: ô nhập hiện đủ nội dung, cao tự giãn theo chữ */
    .me-req-table th {
        white-space: nowrap;
        vertical-align: middle;
        background-color: var(--primary-soft);
        color: var(--primary);
        font-size: 0.84rem;
        padding: 10px 8px !important;
    }
    .me-req-table td {
        vertical-align: top !important;
        padding: 6px !important;
    }
    .me-req-table .form-control {
        font-size: 0.875rem;
        line-height: 1.45;
        padding: 6px 8px;
        min-height: 36px;
        box-sizing: border-box;
    }
    .me-req-table select.form-control {
        height: 36px !important;
        min-height: 36px !important;
    }
    textarea.me-autosize {
        resize: none;
        overflow: hidden;
        word-break: break-word;
        box-sizing: border-box;
    }
    .me-req-table textarea.me-autosize {
        height: 36px;
    }
    .me-req-table select.me-cat {
        text-overflow: ellipsis;
    }
</style>

<div class="modal fade md-modal" id="reqCreateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 92vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-signature mr-2"></i>Tạo Đề Nghị Cấp Phát Vật Tư<span id="reqCreateTypeLabel">{{ old('type') === 'risk_assessment' ? ' - Theo ĐG Rủi Ro' : ' - Thường Quy' }}</span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'requestStore') }}" method="POST" id="reqCreateForm">
                @csrf
                <input type="hidden" name="action_type" id="reqCreateAction" value="send">
                {{-- Loại đề nghị: nút "Tạo đề nghị" của tab nào thì điền type của tab đó --}}
                <input type="hidden" name="type" id="reqCreateType" value="{{ old('type') === 'risk_assessment' ? 'risk_assessment' : 'regular' }}">
                <div class="modal-body">

                    {{-- Thông tin chung + hai nút thao tác nằm gọn trên một hàng --}}
                    <div class="me-req-toolbar mb-3">
                        <div class="me-field flex-grow-1" style="min-width: 280px;">
                            <label>Tiêu đề đề nghị</label>
                            <input type="text" name="name" maxlength="255" class="form-control" value="{{ old('name') }}" placeholder="VD: Đề nghị vật tư bảo trì tháng 9...">
                        </div>
                        <div class="me-field">
                            <label>Ngày mong muốn</label>
                            <input type="date" name="needed_date" class="form-control" value="{{ old('needed_date') }}">
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-info shadow-sm btn-open-me-picker" data-target-rows="#reqCreateModal .me-rows"
                            <i class="fas fa-boxes-stacked mr-1"></i>Danh mục tồn của phòng
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm me-add-row">
                            <i class="fas fa-plus mr-1"></i>Thêm vật tư
                        </button>
                    </div>

                    @if ($categories->isEmpty())
                        <div class="alert alert-warning py-2 px-3 small mb-2" style="border-radius: var(--border-radius-md);">
                            <i class="fas fa-triangle-exclamation mr-1"></i>
                            Phòng chưa khai vật tư nào trong <b>Danh Mục &rarr; Vật Tư Của Phòng</b> nên ô
                            <b>"Vật tư (danh mục)"</b> đang rỗng. Hãy khai danh mục trước, hoặc nhập tạm ở cột
                            <b>"Hoặc tên tự nhập"</b>.
                        </div>
                    @endif

                    <div class="table-responsive border rounded">
                        <table class="table table-bordered table-sm mb-0 me-req-table">
                            @include('pages.export.MaterialExport.requestRowsHead')
                            <tbody class="me-rows" data-next-idx="0"></tbody>
                        </table>
                    </div>
                    @foreach ($bag->keys() as $k)
                        @if (str_starts_with($k, 'items')) <div class="md-error text-danger small">{{ $bag->first($k) }}</div> @endif
                    @endforeach

                    <div class="mt-3">
                        @include('pages.export.MaterialExport.signFlowFields')
                    </div>
                    @foreach ($bag->keys() as $k)
                        @if (str_starts_with($k, 'signers')) <div class="md-error text-danger small">{{ $bag->first($k) }}</div> @endif
                    @endforeach

                    <div class="form-group mt-3 mb-0">
                        <label style="font-size: 0.82rem; font-weight: 600; color: var(--text-main);">Ghi chú chung</label>
                        <textarea name="note" maxlength="500" rows="2" class="form-control me-autosize" placeholder="Ghi chú cho cả đề nghị...">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-outline-primary me-btn-draft"><i class="fas fa-save mr-1"></i>Lưu tạm</button>
                    <button type="submit" class="btn btn-primary me-btn-send"><i class="fas fa-paper-plane mr-1"></i>Trình ký</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="meRowTemplate">
    <tr>
        <td>
            <select name="items[__i__][category_id]" class="form-control form-control-sm me-cat">
                <option value="">-- Ngoài danh mục --</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" data-unit="{{ $c->unit_short_name }}" data-spec="{{ $c->technical_specification }}"
                        data-stock="{{ $c->total_remaining ?? 0 }}" data-available="{{ $c->available_stock ?? ($c->total_remaining ?? 0) }}">
                        {{ $c->material_name }} — {{ $c->manufacturer_short_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td><textarea name="items[__i__][material_name]" maxlength="255" rows="1" class="form-control form-control-sm me-autosize" placeholder="Vật tư ngoài danh mục..."></textarea></td>
        <td><textarea name="items[__i__][technical_specification]" maxlength="255" rows="1" class="form-control form-control-sm me-autosize me-spec" placeholder="Thông tin kỹ thuật..."></textarea></td>
        <td><input type="text" inputmode="decimal" min="0.0001" name="items[__i__][requested_amount]" class="form-control form-control-sm text-right js-decimal" placeholder="0.0000" required></td>
        <td>
            <select name="items[__i__][requested_unit]" class="form-control form-control-sm me-unit">
                <option value="">--</option>
                @foreach ($units as $u)
                    <option value="{{ $u->short_name }}">{{ $u->short_name }}</option>
                @endforeach
            </select>
        </td>
        <td class="text-right">
            <span class="me-stock-view">—</span>
            <input type="hidden" class="me-stock-hidden" name="items[__i__][stock_at_request]" value="">
        </td>
        <td class="text-right"><span class="me-available">—</span></td>
        <td><textarea name="items[__i__][purpose]" maxlength="500" rows="1" class="form-control form-control-sm me-autosize" placeholder="Mục đích sử dụng..."></textarea></td>
        <td class="text-center">
            <button type="button" class="btn btn-xs btn-outline-warning me-remember-row mr-1" title="Đề nghị dự trù vật tư"><i class="fas fa-bookmark"></i></button>
            <button type="button" class="btn btn-xs btn-outline-danger me-del-row" title="Xoá dòng">&times;</button>
        </td>
    </tr>
</template>


<script>
    // Quy trình ký của từng vật tư trong danh mục của phòng, gửi từ Controller
    window.meSignFlowMap = @json($signFlowMap);

    /*
     | QUY TRÌNH KÝ DUYỆT - CHỈ ĐỌC, suy từ dữ liệu gốc "Trình Ký Đề Nghị CP Vật Tư".
     |
     | meSignFlowMap gửi từ Controller: byCategory[<category_id>] là quy trình của vật tư
     | đó, fallback là quy trình cho dòng tên tự nhập (chỉ khớp quy trình không khai điều
     | kiện nào). Mỗi dòng vật tư khớp một quy trình riêng; phiếu lấy quy trình NHIỀU BƯỚC
     | NHẤT. Còn dòng chưa khớp quy trình nào thì khoá nút Trình ký - phải khai dữ liệu gốc
     | trước, đúng như Controller chặn ở phía server.
     */
    window.meRenderSignFlow = function (box) {
        var $box = $(box);
        var map = window.meSignFlowMap || { byCategory: {}, fallback: null };
        var best = null;
        var missing = [];

        $box.closest('form').find('.me-rows').children('tr').each(function (i) {
            var catId = String($(this).find('.me-cat').val() || '');
            var flow = catId ? map.byCategory[catId] : map.fallback;

            if (!flow) {
                missing.push(i + 1);
                return;
            }

            if (!best || flow.steps.length > best.steps.length) best = flow;
        });

        var $steps = $box.find('.me-flow-steps').empty();

        (best ? best.steps : []).forEach(function (step) {
            var $row = $('<div class="me-step-row me-step-readonly"></div>');

            $row.append(
                $('<div class="me-step-badge"></div>')
                    .append($('<span class="me-step-no"></span>').text(step.no))
                    .append($('<span class="me-step-tag"></span>').text('Bước ' + step.no + ':'))
            );

            var $who = $('<div class="me-step-who"></div>');
            var $names = $('<span class="me-step-name"></span>');

            // Bước giao cho nhiều người: liệt kê hết, chỉ cần MỘT trong số họ ký là xong bước
            step.users.forEach(function (person, i) {
                if (i) $names.append($('<span class="me-step-or"></span>').text('hoặc'));
                $names.append($('<span></span>').text(person.name + (person.dept ? ' · ' + person.dept : '')));
            });

            $who.append($names);
            $who.append($('<span class="me-step-role"></span>').text(
                step.role + (step.users.length > 1 ? ' — chỉ cần 1 người ký' : '')
            ));

            var locked = step.users.filter(function (person) { return person.inactive; });

            if (locked.length) {
                $who.append($('<span class="me-step-warn"></span>').html(
                    '<i class="fas fa-triangle-exclamation mr-1"></i>Tài khoản đã bị khoá: '
                    + $('<div>').text(locked.map(function (p) { return p.name; }).join(', ')).html()
                ));
            }

            $row.append($who);
            $steps.append($row);
        });

        $box.find('.me-flow-count').text(best ? best.steps.length + ' bước ký · ' + best.name : 'Chưa xác định');

        // Dòng chưa khớp quy trình nào -> báo ngay và khoá Trình ký, vẫn cho Lưu tạm
        var $none = $box.find('.me-flow-none');

        if (missing.length) {
            $box.find('.me-flow-none-detail').text(
                'Dòng ' + missing.join(', ') + ' chưa khớp quy trình nào của phòng. '
                + 'Vào Dữ Liệu Gốc → Trình Ký Đề Nghị CP Vật Tư để khai quy trình cho phân loại tương ứng.'
            );
            $none.css('display', 'flex');
        } else {
            $none.hide();
        }

        $box.closest('form').find('.me-btn-send').prop('disabled', missing.length > 0);
    };

    /** Dựng lại khối quy trình ký của form chứa một phần tử bất kỳ. */
    window.meSyncSignFlow = function (el) {
        $(el).closest('form').find('[data-sign-flow]').each(function () {
            window.meRenderSignFlow(this);
        });
    };

    /**
     * Thêm một dòng vật tư vào bảng đề nghị (form tạo hoặc form sửa) và trả về dòng vừa thêm.
     * Chỉ số items[i] đếm riêng cho từng bảng, giữ ở data-next-idx nên không đụng nhau giữa các form.
     * Modal "Danh mục tồn của phòng" cũng gọi hàm này.
     */
    window.meAddRequestRow = function (tbody) {
        var $tbody = $(tbody);
        var next = parseInt($tbody.attr('data-next-idx') || 0, 10);

        $tbody.attr('data-next-idx', next + 1);
        $tbody.append(document.getElementById('meRowTemplate').innerHTML.replace(/__i__/g, next));

        var $row = $tbody.children('tr').last();
        window.meAutoSize($row);
        window.meSyncSignFlow($tbody);

        return $row;
    };

    /*
     | CỘT "TỒN KHẢ DỤNG" - tính động, KHÔNG lưu DB, khác cột "Tồn" (chụp tĩnh một lần lúc
     | thêm dòng vào ô ẩn .me-stock-hidden - xem handler .me-cat change bên dưới). Tồn khả
     | dụng = tồn - phần các đề nghị KHÁC đang chờ ký/đã duyệt nhưng kho chưa cấp đủ đã giữ
     | chỗ, nạp qua route requestStockMap và có thể làm mới bất cứ lúc nào bằng nút cạnh tiêu
     | đề cột hoặc mỗi khi mở lại modal.
     */
    window.meStockMap = {};

    window.meFmtStock = function (v) {
        if (v === null || v === undefined || v === '') return '—';
        var n = Number(v);
        if (isNaN(n)) return '—';
        return n.toLocaleString('vi-VN', { maximumFractionDigits: 4 });
    };

    window.meFetchStockMap = function (done) {
        $.getJSON('{{ route($expRoute . "requestStockMap") }}')
            .done(function (res) { window.meStockMap = (res && res.ok && res.stock) || {}; })
            .always(function () { if (typeof done === 'function') done(); });
    };

    /** Vẽ lại CHỈ cột "Tồn khả dụng" của mọi dòng trong một bảng, theo window.meStockMap hiện có. */
    window.meRefreshAvailable = function (tbody) {
        $(tbody).children('tr').each(function () {
            var catId = $(this).find('.me-cat').val();
            var $cell = $(this).find('.me-available');
            if (!catId) { $cell.text('—'); return; }
            var s = window.meStockMap[catId];
            $cell.text(s ? window.meFmtStock(s.available) : '—');
        });
    };

    /** Kéo chiều cao các ô textarea vừa đúng nội dung đang có (dòng mới, dòng đổ từ danh mục, dòng đã lưu). */
    window.meAutoSize = function (context) {
        $(context || document).find('textarea.me-autosize').addBack('textarea.me-autosize').each(function () {
            this.style.height = 'auto';
            var offset = this.offsetHeight - this.clientHeight;
            this.style.height = Math.max(this.scrollHeight + offset, 36) + 'px';
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        function addRow(tbodySel) { return window.meAddRequestRow(tbodySel); }
        $(document).on('click', '.me-add-row', function () { addRow('#reqCreateModal .me-rows'); });

        /*
         | ĐỀ NGHỊ DỰ TRÙ VẬT TƯ - nút trên từng dòng đề nghị, hỏi lý do dự trù rồi gửi AJAX
         | ngay (không phụ thuộc đề nghị có được lưu hay không). Hiện lại ở tab "Danh sách
         | vật tư cần dự trù" bên Dự Trù Vật Tư - xem App\Support\MaterialWatchlist.
         */
        window.meWatchlistToast = window.meWatchlistToast || Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });

        $(document).on('click', '.me-remember-row', function () {
            var $btn = $(this), $tr = $btn.closest('tr'), $form = $btn.closest('form');
            var categoryId = $tr.find('.me-cat').val();
            var materialName = $.trim($tr.find('[name$="[material_name]"]').val() || '');
            var spec = $tr.find('[name$="[technical_specification]"]').val() || '';
            var srcType = $form.attr('data-req-type') || $form.find('[name="type"]').val() || 'regular';

            if (!categoryId && !materialName) {
                window.meWatchlistToast.fire({ icon: 'warning', title: 'Chưa chọn vật tư cho dòng này!' });
                return;
            }

            Swal.fire({
                title: 'Đề nghị dự trù vật tư này?',
                input: 'textarea',
                inputLabel: 'Lý do dự trù',
                inputPlaceholder: 'Nêu rõ lý do cần dự trù vật tư này...',
                inputAttributes: { maxlength: '500' },
                showCancelButton: true,
                confirmButtonColor: '#2E7BC4',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Gửi đề nghị',
                cancelButtonText: 'Huỷ',
                preConfirm: function (value) {
                    if (!value || !value.trim()) {
                        Swal.showValidationMessage('Vui lòng nhập lý do dự trù');
                    }
                    return value;
                }
            }).then(function (result) {
                if (!result.isConfirmed) return;

                $btn.prop('disabled', true);

                $.post('{{ route($expRoute . "watchlistRemember") }}', {
                    _token: $form.find('input[name="_token"]').val(),
                    category_id: categoryId || '',
                    material_name: materialName,
                    technical_specification: spec,
                    note: result.value,
                    source_type: srcType
                }).done(function (res) {
                    window.meWatchlistToast.fire({ icon: res.success ? 'success' : 'error', title: res.message || 'Có lỗi xảy ra!' });
                    if (res.success) $btn.find('i').removeClass('fa-bookmark').addClass('fa-check');
                }).fail(function () {
                    window.meWatchlistToast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                }).always(function () {
                    $btn.prop('disabled', false);
                });
            });
        });
        $(document).on('click', '.me-del-row', function () {
            var $form = $(this).closest('form');
            $(this).closest('tr').remove();
            window.meSyncSignFlow($form);
        });
        $(document).on('input', 'textarea.me-autosize', function () { window.meAutoSize($(this)); });
        $(document).on('change', '.me-cat', function () {
            var $o = $(this).find(':selected'), $tr = $(this).closest('tr');
            if ($o.val()) {
                $tr.find('.me-unit').val($o.data('unit') || '');
                $tr.find('.me-spec').val($o.data('spec') || '').prop('readonly', true);
                $tr.find('[name$="[material_name]"]').val('').prop('disabled', true);

                /*
                 | "Tồn" chụp NGAY LÚC chọn danh mục cho dòng này - ưu tiên số vừa tải qua
                 | AJAX (window.meStockMap, mới hơn lúc mở trang), không có thì tạm dùng số
                 | lúc tải trang (data-stock/data-available trên option). Sau khi đã chụp,
                 | ô ẩn giữ nguyên giá trị này - chỉ đổi lại khi người dùng CHỌN LẠI danh mục
                 | khác cho đúng dòng đó, không tự cập nhật theo thời gian.
                 */
                var catId = String($o.val());
                var live = (window.meStockMap || {})[catId];
                var stock = live ? live.remaining : $o.data('stock');
                var avail = live ? live.available : $o.data('available');
                $tr.find('.me-stock-hidden').val(stock !== undefined && stock !== null ? stock : '');
                $tr.find('.me-stock-view').text(window.meFmtStock(stock));
                $tr.find('.me-available').text(window.meFmtStock(avail));
            } else {
                $tr.find('[name$="[material_name]"]').prop('disabled', false);
                $tr.find('.me-spec').prop('readonly', false);
                $tr.find('.me-stock-hidden').val('');
                $tr.find('.me-stock-view').text('—');
                $tr.find('.me-available').text('—');
            }
            window.meAutoSize($tr);
        });
        $(document).on('click', '.me-btn-draft', function () { $('#reqCreateAction').val('draft'); });
        $(document).on('click', '.me-btn-send', function () { $('#reqCreateAction').val('send'); });

        // ---- Quy trình ký duyệt: dựng lại mỗi khi danh sách vật tư đổi ----
        $(document).on('change', '.me-cat', function () { window.meSyncSignFlow(this); });
        $('[data-sign-flow]').each(function () { window.meRenderSignFlow(this); });

        // Cùng một modal cho tab Thường Quy và Theo ĐG Rủi Ro: nút bấm ở tab nào thì đề nghị thuộc loại đó
        $(document).on('click', '.btn-req-create', function () {
            $('#reqCreateType').val($(this).data('req-type'));
            $('#reqCreateTypeLabel').text($(this).data('req-label'));
        });

        // Textarea trong modal ẩn có scrollHeight = 0, phải đo lại lúc modal hiện ra
        $(document).on('shown.bs.modal', '#reqCreateModal, [id^="reqEditModal_"]', function () {
            if (this.id === 'reqCreateModal' && !$('#reqCreateModal .me-rows tr').length) {
                addRow('#reqCreateModal .me-rows');
            }
            window.meAutoSize($(this));

            // Mở modal thì làm mới ngay "Tồn khả dụng" của mọi dòng đang có trong đúng modal này
            var $rows = $(this).find('.me-rows, .me-edit-rows');
            window.meFetchStockMap(function () { window.meRefreshAvailable($rows); });
        });

        // Nút làm mới cạnh tiêu đề cột "Tồn khả dụng" - chỉ làm mới bảng chứa nút vừa bấm
        $(document).on('click', '.me-refresh-stock', function (e) {
            e.preventDefault();
            var $rows = $(this).closest('table').find('.me-rows, .me-edit-rows');
            window.meFetchStockMap(function () { window.meRefreshAvailable($rows); });
        });
        @if ($bag->any())
            $(function () {
                $('#reqCreateModal').modal('show');
                var old = @json(old('items', []));
                (old.length ? old : [{}]).forEach(function (row) {
                    addRow('#reqCreateModal .me-rows');
                    var $tr = $('#reqCreateModal .me-rows tr').last();
                    Object.keys(row).forEach(function (k) { $tr.find('[name$="[' + k + ']"]').val(row[k]); });
                    window.meAutoSize($tr);
                });
            });
        @endif
    });
</script>
