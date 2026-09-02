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
        min-height: 34px;
    }
    textarea.me-autosize {
        resize: none;
        overflow: hidden;
        word-break: break-word;
    }
    .me-req-table textarea.me-autosize {
        height: 34px;
    }
    .me-req-table select.me-cat {
        text-overflow: ellipsis;
    }
</style>

<div class="modal fade md-modal" id="reqCreateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 92vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-signature mr-2"></i>Tạo Đề Nghị Cấp Phát Vật Tư</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'requestStore') }}" method="POST" id="reqCreateForm">
                @csrf
                <input type="hidden" name="action_type" id="reqCreateAction" value="send">
                <div class="modal-body">

                    {{-- Thông tin chung + hai nút thao tác nằm gọn trên một hàng --}}
                    <div class="me-req-toolbar mb-3">
                        <div class="me-field">
                            <label>Tổ đề nghị <span class="text-danger">*</span></label>
                            <select name="group_id" class="form-control {{ $bag->has('group_id') ? 'is-invalid' : '' }}" style="min-width: 190px;" required>
                                <option value="">-- Chọn Tổ --</option>
                                @foreach ($groups as $g)
                                    <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="me-field flex-grow-1" style="min-width: 280px;">
                            <label>Tiêu đề đề nghị</label>
                            <input type="text" name="name" maxlength="255" class="form-control" value="{{ old('name') }}" placeholder="VD: Đề nghị vật tư bảo trì tháng 9...">
                        </div>

                        <label class="me-check mb-0">
                            <input type="checkbox" name="needs_director" value="1" {{ old('needs_director') ? 'checked' : '' }}>
                            Cần Ban Giám Đốc phê duyệt
                        </label>

                        <button type="button" class="btn btn-sm btn-outline-info shadow-sm btn-open-me-picker" data-target-rows="#reqCreateModal .me-rows">
                            <i class="fas fa-boxes-stacked mr-1"></i>Danh mục tồn của phòng
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm me-add-row">
                            <i class="fas fa-plus mr-1"></i>Thêm vật tư
                        </button>
                    </div>

                    @if ($bag->has('group_id')) <div class="md-error text-danger small mb-2">{{ $bag->first('group_id') }}</div> @endif

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
                    <option value="{{ $c->id }}" data-unit="{{ $c->unit_short_name }}" data-spec="{{ $c->technical_specification }}">
                        {{ $c->material_name }} — {{ $c->manufacturer_short_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td><textarea name="items[__i__][material_name]" maxlength="255" rows="1" class="form-control form-control-sm me-autosize" placeholder="Vật tư ngoài danh mục..."></textarea></td>
        <td><textarea name="items[__i__][technical_specification]" maxlength="255" rows="1" class="form-control form-control-sm me-autosize me-spec" placeholder="Quy cách..."></textarea></td>
        <td><input type="number" step="0.0001" min="0.0001" name="items[__i__][requested_amount]" class="form-control form-control-sm text-right" placeholder="0.0000" required></td>
        <td>
            <select name="items[__i__][requested_unit]" class="form-control form-control-sm me-unit">
                <option value="">--</option>
                @foreach ($units as $u)
                    <option value="{{ $u->short_name }}">{{ $u->short_name }}</option>
                @endforeach
            </select>
        </td>
        <td><textarea name="items[__i__][product_name]" maxlength="255" rows="1" class="form-control form-control-sm me-autosize" placeholder="Thiết bị liên quan..."></textarea></td>
        <td><textarea name="items[__i__][purpose]" maxlength="500" rows="1" class="form-control form-control-sm me-autosize" placeholder="Mục đích sử dụng..."></textarea></td>
        <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger me-del-row" title="Xoá dòng">&times;</button></td>
    </tr>
</template>

<script>
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

        return $row;
    };

    /** Kéo chiều cao các ô textarea vừa đúng nội dung đang có (dòng mới, dòng đổ từ danh mục, dòng đã lưu). */
    window.meAutoSize = function (context) {
        $(context || document).find('textarea.me-autosize').addBack('textarea.me-autosize').each(function () {
            this.style.height = 'auto';
            this.style.height = Math.max(this.scrollHeight, 34) + 'px';
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        function addRow(tbodySel) { return window.meAddRequestRow(tbodySel); }
        $(document).on('click', '.me-add-row', function () { addRow('#reqCreateModal .me-rows'); });
        $(document).on('click', '.me-del-row', function () { $(this).closest('tr').remove(); });
        $(document).on('input', 'textarea.me-autosize', function () { window.meAutoSize($(this)); });
        $(document).on('change', '.me-cat', function () {
            var $o = $(this).find(':selected'), $tr = $(this).closest('tr');
            if ($o.val()) {
                $tr.find('.me-unit').val($o.data('unit') || '');
                if (!$tr.find('.me-spec').val()) $tr.find('.me-spec').val($o.data('spec') || '');
                $tr.find('[name$="[material_name]"]').val('').prop('disabled', true);
            } else {
                $tr.find('[name$="[material_name]"]').prop('disabled', false);
            }
            window.meAutoSize($tr);
        });
        $(document).on('click', '.me-btn-draft', function () { $('#reqCreateAction').val('draft'); });
        $(document).on('click', '.me-btn-send', function () { $('#reqCreateAction').val('send'); });
        // Textarea trong modal ẩn có scrollHeight = 0, phải đo lại lúc modal hiện ra
        $(document).on('shown.bs.modal', '#reqCreateModal, [id^="reqEditModal_"]', function () {
            if (this.id === 'reqCreateModal' && !$('#reqCreateModal .me-rows tr').length) {
                addRow('#reqCreateModal .me-rows');
            }
            window.meAutoSize($(this));
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
