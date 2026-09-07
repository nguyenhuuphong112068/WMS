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
    .me-flow-head .me-add-signer {
        margin-left: auto;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 6px;
        background: var(--primary, #2E7BC4);
        border: 1px solid var(--primary, #2E7BC4);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(46, 123, 196, 0.2);
    }
    .me-flow-head .me-add-signer:hover {
        background: var(--primary-dark, #1F5E9E);
        border-color: var(--primary-dark, #1F5E9E);
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(46, 123, 196, 0.25);
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
    .me-step-row .me-step-user {
        flex: 1 1 auto;
        min-width: 0;
        height: 38px !important;
        min-height: 38px !important;
        max-height: none !important;
        line-height: 1.5 !important;
        font-size: 0.875rem !important;
        padding: 6px 12px !important;
        color: #1e293b !important;
        background-color: #ffffff !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 6px !important;
        box-sizing: border-box !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        cursor: pointer;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .me-step-row .me-step-user:focus {
        border-color: var(--primary, #2E7BC4) !important;
        outline: 0 !important;
        box-shadow: 0 0 0 3px rgba(46, 123, 196, 0.15) !important;
    }
    .me-step-row .me-step-user option {
        padding: 6px 10px;
        font-size: 0.875rem;
        color: #1e293b;
    }
    .me-del-signer {
        flex: 0 0 auto;
        width: 34px;
        height: 34px;
        border: 1px solid #fee2e2 !important;
        background: #fff5f5 !important;
        color: #ef4444 !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        padding: 0;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .me-del-signer:hover {
        background: #fee2e2 !important;
        color: #b91c1c !important;
        border-color: #fca5a5 !important;
        transform: scale(1.05);
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
                <h5 class="modal-title"><i class="fas fa-file-signature mr-2"></i>Tạo Đề Nghị Cấp Phát Vật Tư</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'requestStore') }}" method="POST" id="reqCreateForm">
                @csrf
                <input type="hidden" name="action_type" id="reqCreateAction" value="send">
                <div class="modal-body">

                    {{-- Thông tin chung + hai nút thao tác nằm gọn trên một hàng --}}
                    <div class="me-req-toolbar mb-3">
                        <div class="me-field flex-grow-1" style="min-width: 280px;">
                            <label>Tiêu đề đề nghị</label>
                            <input type="text" name="name" maxlength="255" class="form-control" value="{{ old('name') }}" placeholder="VD: Đề nghị vật tư bảo trì tháng 9...">
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-info shadow-sm btn-open-me-picker" data-target-rows="#reqCreateModal .me-rows">
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
                        @include('pages.export.MaterialExport.signFlowFields', [
                            'flowSigners' => old('signers', []),
                        ])
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
                    <option value="{{ $c->id }}" data-unit="{{ $c->unit_short_name }}" data-spec="{{ $c->technical_specification }}">
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
        <td><textarea name="items[__i__][product_name]" maxlength="255" rows="1" class="form-control form-control-sm me-autosize" placeholder="Thiết bị liên quan..."></textarea></td>
        <td><textarea name="items[__i__][purpose]" maxlength="500" rows="1" class="form-control form-control-sm me-autosize" placeholder="Mục đích sử dụng..."></textarea></td>
        <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger me-del-row" title="Xoá dòng">&times;</button></td>
    </tr>
</template>

<template id="meSignerTemplate">
    <div class="me-step-row">
        <div class="me-step-badge">
            <span class="me-step-no"></span>
            <span class="me-step-tag">Bước <span class="me-step-idx"></span>:</span>
        </div>
        <select name="signers[]" class="form-control me-step-user" required>
            <option value="">-- Chọn người ký duyệt --</option>
            @foreach ($signerOptions as $person)
                @php
                    $label = ($person->fullName ?: $person->userName)
                        . ($person->role_name ? ' — ' . $person->role_name : '')
                        . ($person->department_short ? ' · ' . $person->department_short : '');
                @endphp
                <option value="{{ $person->id }}" title="{{ $label }}">
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <button type="button" class="btn me-del-signer" title="Xoá bước ký"><i class="fas fa-trash-alt"></i></button>
    </div>
</template>

<script>
    /**
     * Đánh lại số thứ tự bước ký + cập nhật dòng tóm tắt của một khối quy trình.
     * Gọi sau mọi lần thêm / xoá bước, và một lần lúc dựng trang cho các bước đã lưu.
     */
    window.meSyncSignFlow = function (box) {
        var $box = $(box);
        var $rows = $box.find('.me-step-row');

        $rows.each(function (i) {
            $(this).find('.me-step-no').text(i + 1);
            $(this).find('.me-step-idx').text(i + 1);
        });

        $box.find('.me-flow-count').text($rows.length ? $rows.length + ' bước ký' : 'Duyệt thẳng (0 bước)');
        if ($rows.length === 0) {
            $box.find('.me-flow-none').css('display', 'flex');
        } else {
            $box.find('.me-flow-none').hide();
        }
    };

    /**
     * Thêm một bước ký vào khối quy trình.
     * Người lập tự xếp thứ tự bước: bước nào đứng trước thì ký trước.
     */
    window.meAddSignerRow = function (box) {
        var $box = $(box);

        $box.find('.me-flow-steps').append(document.getElementById('meSignerTemplate').innerHTML);
        window.meSyncSignFlow($box);
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

        return $row;
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
        $(document).on('click', '.me-del-row', function () { $(this).closest('tr').remove(); });
        $(document).on('input', 'textarea.me-autosize', function () { window.meAutoSize($(this)); });
        $(document).on('change', '.me-cat', function () {
            var $o = $(this).find(':selected'), $tr = $(this).closest('tr');
            if ($o.val()) {
                $tr.find('.me-unit').val($o.data('unit') || '');
                $tr.find('.me-spec').val($o.data('spec') || '').prop('readonly', true);
                $tr.find('[name$="[material_name]"]').val('').prop('disabled', true);
            } else {
                $tr.find('[name$="[material_name]"]').prop('disabled', false);
                $tr.find('.me-spec').prop('readonly', false);
            }
            window.meAutoSize($tr);
        });
        $(document).on('click', '.me-btn-draft', function () { $('#reqCreateAction').val('draft'); });
        $(document).on('click', '.me-btn-send', function () { $('#reqCreateAction').val('send'); });

        // ---- Quy trình ký duyệt: thêm / xoá bước, số thứ tự tự đánh lại ----
        $(document).on('click', '.me-add-signer', function () {
            window.meAddSignerRow($(this).closest('.me-flow-box'));
        });
        $(document).on('click', '.me-del-signer', function () {
            var $box = $(this).closest('.me-flow-box');
            $(this).closest('.me-step-row').remove();
            window.meSyncSignFlow($box);
        });
        $('.me-flow-box').each(function () { window.meSyncSignFlow(this); });

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
