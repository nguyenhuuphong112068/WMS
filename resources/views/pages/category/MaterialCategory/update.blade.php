@php
    $bag = $errors->getBag('updateErrors');
    $oldClassification = $bag->any() ? (array) old('classification', []) : [];

    // Ảnh hiện tại của dòng đang sửa khi modal mở lại sau lỗi validate
    $mcuEditing = $bag->any() ? $datas->firstWhere('id', (int) old('id')) : null;
    $mcuCurrentImage = $mcuEditing && $mcuEditing->image_path
        ? route($mdRoute . 'image', ['id' => $mcuEditing->id, 'v' => substr(md5($mcuEditing->image_path), 0, 8)])
        : null;
@endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Cập Nhật {{ $mdTitle }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-7">

                            <div class="form-group">
                                <label>Mã Vật Tư</label>
                                <input type="text" name="code" class="form-control cat-readonly"
                                    value="{{ old('code') }}" readonly tabindex="-1">
                                <small class="md-sub">Mã đã cấp, không sửa được.</small>
                            </div>

                            <div class="form-group">
                                <label>Tên Vật Tư <span class="text-danger">*</span></label>
                                <select name="material_names_id"
                                    data-lookup-url="{{ route('pages.category.lookup.names', ['source' => 'material']) }}"
                                    class="form-control cat-select {{ $bag->has('material_names_id') ? 'is-invalid' : '' }}"
                                    required>
                                    <option value="">-- Chọn tên vật tư --</option>
                                    @foreach ($materialNames as $option)
                                        <option value="{{ $option->id }}"
                                            {{ old('material_names_id') == $option->id ? 'selected' : '' }}>
                                            {{ $option->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($bag->has('material_names_id'))
                                    <span class="md-error">{{ $bag->first('material_names_id') }}</span>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nhà Sản Xuất <span class="text-danger">*</span></label>
                                        <select name="manufacturers_id"
                                            class="form-control cat-select {{ $bag->has('manufacturers_id') ? 'is-invalid' : '' }}"
                                            required>
                                            <option value="">-- Chọn nhà sản xuất --</option>
                                            @foreach ($manufacturers as $option)
                                                <option value="{{ $option->id }}"
                                                    {{ old('manufacturers_id') == $option->id ? 'selected' : '' }}>
                                                    {{ $option->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($bag->has('manufacturers_id'))
                                            <span class="md-error">{{ $bag->first('manufacturers_id') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Thông Tin Kỹ Thuật</label>
                                        <input type="text" name="technical_specification" maxlength="100"
                                            class="form-control {{ $bag->has('technical_specification') ? 'is-invalid' : '' }}"
                                            value="{{ old('technical_specification') }}"
                                            placeholder="Nhập thông tin kỹ thuật">
                                        @if ($bag->has('technical_specification'))
                                            <span class="md-error">{{ $bag->first('technical_specification') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Part Number</label>
                                        <input type="text" name="part_number" maxlength="100"
                                            class="form-control {{ $bag->has('part_number') ? 'is-invalid' : '' }}"
                                            value="{{ old('part_number') }}"
                                            placeholder="Mã linh kiện của nhà sản xuất">
                                        @if ($bag->has('part_number'))
                                            <span class="md-error">{{ $bag->first('part_number') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="d-block">Hình Ảnh Vật Tư</label>
                                        {{-- Ảnh hiện tại đổ từ data-row.image_url khi bấm Sửa; remove_image = 1 khi gỡ ảnh cũ --}}
                                        <label class="mci-picker mci-lg mb-0 {{ $bag->has('image') ? 'is-invalid' : '' }}"
                                            id="mcuImagePicker">
                                            <input type="file" name="image" class="mci-input" accept="image/*">
                                            <input type="hidden" name="remove_image" class="mci-remove" value="0">
                                            <span class="mci-pick" title="Chọn hình ảnh vật tư (không bắt buộc)">
                                                <i class="fas fa-camera"></i> Chọn ảnh
                                            </span>
                                            <span class="mci-preview">
                                                <img src="" alt="Ảnh vật tư" title="Bấm để xem ảnh lớn">
                                                <button type="button" class="mci-clear" title="Gỡ ảnh">&times;</button>
                                            </span>
                                        </label>
                                        @if ($bag->has('image'))
                                            <span class="md-error">{{ $bag->first('image') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="md-hint">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                Sau khi sửa, bản ghi quay về trạng thái <b>Chờ duyệt</b> và cần được duyệt lại.
                                Nội dung thay đổi được lưu lại ở mục <b>Lịch sử thay đổi</b>.
                            </div>

                            <div class="form-group">
                                <label>Lý Do Điều Chỉnh <span class="text-danger">*</span></label>
                                <textarea name="change_reason" rows="2" maxlength="500" required
                                    class="form-control {{ $bag->has('change_reason') ? 'is-invalid' : '' }}"
                                    placeholder="Nêu rõ lý do sửa bản ghi này">{{ old('change_reason') }}</textarea>
                                @if ($bag->has('change_reason'))
                                    <span class="md-error">{{ $bag->first('change_reason') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-5">
                            @include('pages.category.MaterialCategory.classificationFields', [
                                'bag' => $bag,
                                'selected' => $oldClassification,
                                'purchasing' => $bag->any() ? old('purchasing_department') : null,
                            ])
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Đổ phân loại của dòng đang sửa vào nhóm radio ----------
           Phần JS dùng chung chỉ hiểu ô tick dạng name="<tên>[]"; phân loại ở đây là
           name="classification[<tiêu chí>]" và name="purchasing_department" nên phải tự
           đổ. Handler này gắn sau nên chạy sau phần dùng chung (phần đó vừa bỏ tick
           toàn bộ nhóm). */
        $(document).on('click', '.btn-md-edit:not([data-modal])', function() {
            var row = $(this).data('row') || {};
            var classification = row.classification || {};
            var $form = $('#updateModal').find('form');

            $form.find('.cat-check-input').each(function() {
                var name = $(this).attr('name');
                var key = (name.match(/^classification\[(.+)\]$/) || [])[1];
                var want = key ? (classification[key] || '') : (row.purchasing_department || '');
                var checked = want === this.value;

                $(this).prop('checked', checked).closest('.cat-check-item').toggleClass('is-checked', checked);
            });

            // Hình ảnh: hiện ảnh hiện tại (nếu có), bỏ file / cờ gỡ ảnh của lần sửa trước
            var $picker = $('#mcuImagePicker');
            $picker.find('.mci-input').val('');
            $picker.find('.mci-remove').val('0');
            $picker.data('hasCurrent', !!row.image_url).removeClass('is-invalid');
            mciShow($picker, row.image_url || '');
        });

        @if ($bag->any())
            // Mở lại sau lỗi validate (tải lại trang): dựng lại ảnh hiện tại + cờ gỡ ảnh đã chọn
            (function() {
                var $picker = $('#mcuImagePicker');
                var current = @json($mcuCurrentImage);
                var removed = @json(old('remove_image') === '1');

                $picker.data('hasCurrent', !!current);
                $picker.find('.mci-remove').val(removed ? '1' : '0');
                mciShow($picker, removed ? '' : (current || ''));
            })();
        @endif
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
