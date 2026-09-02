@php $bag = $errors->getBag('createErrors'); $ubag = $errors->getBag('updateErrors'); @endphp

{{-- ============ LẬP PHIẾU LOẠI BỎ HÀNG HỎNG / HẾT HẠN ============ --}}
<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $expIcon }} mr-2"></i>Loại bỏ vật tư hỏng / hết hạn</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <input type="hidden" name="type" value="cancel">

                    <div class="md-hint mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Phiếu này chỉ dùng để <b>loại bỏ</b> hàng hỏng / hết hạn. Vật tư đem <b>sử dụng</b> đã
                        được trừ kho ngay lúc kho cấp phát, Tổ chốt lại ở nút <b>"Sử Dụng Vật Tư"</b> trong
                        phiếu đề nghị.
                    </div>

                    {{-- Nguồn: chọn mã xuất nhập để loại bỏ --}}
                    <div class="form-group">
                        <label>Mã xuất nhập cần loại bỏ <span class="text-danger">*</span></label>
                        <select name="import_id" class="form-control exp-select {{ $bag->has('import_id') ? 'is-invalid' : '' }}">
                            <option value="">-- Chọn mã xuất nhập trong kho --</option>
                            @foreach ($availableImports as $i)
                                <option value="{{ $i->id }}" data-remaining="{{ $i->remaining }}" data-unit="{{ $i->unit_short_name }}"
                                    {{ old('import_id') == $i->id ? 'selected' : '' }} {{ $i->remaining <= 0 && $i->expired ? '' : '' }}>
                                    {{ $i->code }} — {{ $i->material_name }} (còn {{ $expNum($i->remaining) }} {{ $i->unit_short_name }}){{ $i->expired ? ' · HẾT HẠN' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('import_id')) <div class="md-error text-danger small">{{ $bag->first('import_id') }}</div> @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Số lượng <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0.0001" name="amount"
                                    class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}" value="{{ old('amount') }}" required>
                                <div class="input-group-append"><span class="input-group-text me-unit">—</span></div>
                            </div>
                            @if ($bag->has('amount')) <div class="md-error text-danger small">{{ $bag->first('amount') }}</div> @endif
                            <small class="text-muted me-remaining"></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lý do loại bỏ <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500" class="form-control {{ $bag->has('reason') ? 'is-invalid' : '' }}">{{ old('reason') }}</textarea>
                        @if ($bag->has('reason')) <div class="md-error text-danger small">{{ $bag->first('reason') }}</div> @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Ghi nhận</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ ĐIỀU CHỈNH PHIẾU ============ --}}
{{-- Các ô trong modal bám đúng theo cột của bảng "Sổ sử dụng vật tư": phần chỉ xem
     (mã xuất nhập, vật tư, tổ, loại, thời gian, người thực hiện) nằm ở khối tóm tắt,
     phần được phép sửa (số lượng, sản phẩm / lý do) nằm ở form bên dưới. --}}
<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Điều chỉnh phiếu xuất kho</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id">
                <div class="modal-body">

                    <div class="exp-summary mb-3">
                        <div class="cell">
                            <small>Mã xuất nhập</small>
                            <b class="exp-code me-up-code">—</b>
                        </div>
                        <div class="cell">
                            <small>Vật tư</small>
                            <b class="me-up-material">—</b>
                            <span class="md-sub me-up-spec"></span>
                        </div>
                        <div class="cell">
                            <small>Tổ</small>
                            <b class="me-up-group">—</b>
                        </div>
                        <div class="cell">
                            <small>Loại</small>
                            <b class="me-up-type">—</b>
                        </div>
                        <div class="cell">
                            <small>Thời gian</small>
                            <b class="me-up-time">—</b>
                        </div>
                        <div class="cell">
                            <small>Người thực hiện</small>
                            <b class="me-up-user">—</b>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Số lượng <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0.0001" name="amount"
                                    class="form-control {{ $ubag->has('amount') ? 'is-invalid' : '' }}" required>
                                <div class="input-group-append"><span class="input-group-text me-up-unit">—</span></div>
                            </div>
                            @if ($ubag->has('amount')) <div class="md-error text-danger small">{{ $ubag->first('amount') }}</div> @endif
                        </div>
                        <div class="form-group col-md-7 me-up-export">
                            <label>Thiết bị liên quan</label>
                            <input type="text" name="product_name" maxlength="255" class="form-control"
                                placeholder="Thiết bị đã dùng vật tư này...">
                        </div>
                    </div>

                    <div class="form-group me-up-export">
                        <label>Mục đích</label>
                        <textarea name="purpose" rows="2" maxlength="500" class="form-control"
                            placeholder="Mục đích sử dụng..."></textarea>
                        <small class="md-sub">Mục đích thuộc dòng đề nghị: sửa ở đây áp dụng cho mọi phiếu được cấp từ dòng đó.</small>
                    </div>
                    <div class="form-group me-up-cancel">
                        <label>Lý do loại bỏ</label>
                        <textarea name="reason" rows="2" maxlength="500" class="form-control"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Lý do điều chỉnh</label>
                        <textarea name="adjust_reason" rows="2" maxlength="500" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Ghi nhận điều chỉnh</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Chọn mã xuất nhập -> đơn vị + tồn còn lại
        $(document).on('change', '#createModal [name="import_id"]', function () {
            var $o = $(this).find(':selected');
            $('#createModal .me-unit').text($o.data('unit') || '—');
            $('#createModal .me-remaining').text($o.val() ? ('Tồn còn: ' + ($o.data('remaining') || 0)) : '');
        });

        // Đổ dữ liệu dòng của sổ sử dụng vào modal điều chỉnh. Phiếu sử dụng chỉ sinh
        // từ cấp phát nên form suy loại phiếu từ dữ liệu dòng.
        $(document).on('click', '.btn-me-edit', function () {
            var r = $(this).data('row') || {};
            var $m = $('#updateModal');
            var isCancel = r.type === 'cancel';

            $m.find('form')[0].reset();
            $m.find('[name="id"]').val(r.id);
            $m.find('[name="amount"]').val(r.amount);
            $m.find('[name="product_name"]').val(r.product_name || '');
            $m.find('[name="purpose"]').val(r.purpose || '');
            $m.find('[name="reason"]').val(r.reason || '');

            $m.find('.me-up-code').text(r.code || '—');
            $m.find('.me-up-material').text(r.material_name || '—');
            $m.find('.me-up-spec').text(r.technical_specification || '');
            $m.find('.me-up-group').text(r.group_name || '—');
            $m.find('.me-up-time').text(r.created_at || '—');
            $m.find('.me-up-user').text(r.used_by || '—');
            $m.find('.me-up-unit').text(r.unit_short_name || '—');
            $m.find('.me-up-type')
                .empty()
                .append($('<span>').addClass('badge badge-' + (isCancel ? 'danger' : 'success')).text(r.type_label || '—'))
                .append(r.locked ? $('<span>').addClass('badge badge-secondary ml-1').text('Đã khoá') : '');

            $m.find('.me-up-export').toggle(!isCancel);
            $m.find('.me-up-cancel').toggle(isCancel);
            $m.modal('show');
        });

        @if ($bag->any()) $(function () { $('#createModal').modal('show'); }); @endif

        {{-- Lỗi validate: mở lại đúng dòng vừa sửa để giữ nguyên phần thông tin chỉ xem --}}
        @if ($ubag->any())
            $(function () {
                var oldId = @json(old('id'));
                var $btn = $('.btn-me-edit').filter(function () {
                    return String(($(this).data('row') || {}).id) === String(oldId);
                }).first();

                if (!$btn.length) return $('#updateModal').modal('show');

                $btn.trigger('click');
                $('#updateModal [name="amount"]').val(@json(old('amount')));
                $('#updateModal [name="product_name"]').val(@json(old('product_name')));
                $('#updateModal [name="purpose"]').val(@json(old('purpose')));
                $('#updateModal [name="reason"]').val(@json(old('reason')));
                $('#updateModal [name="adjust_reason"]').val(@json(old('adjust_reason')));
            });
        @endif
    });
</script>
