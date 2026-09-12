@php
    $bag = $errors->getBag('createErrors');

    // Trang có 2 tab nên nhiều form dùng chung kho old(): chỉ điền lại giá trị vừa nhập
    // khi chính form này báo lỗi.
    $oldClassification = $bag->any() ? (array) old('classification', []) : [];
@endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Thêm {{ $mdTitle }} Mới</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-7">

                            <div class="form-group">
                                <label>Mã Vật Tư</label>
                                <input type="text" class="form-control cat-readonly" value="{{ $nextCode }}" readonly
                                    tabindex="-1">
                                <small class="md-sub">Sinh tự động khi lưu, dạng M00001.</small>
                            </div>

                            <div class="form-group">
                                <label>Tên Vật Tư <span class="text-danger">*</span></label>
                                <select name="material_names_id"
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

                            <div class="md-hint">
                                <i class="fas fa-info-circle mr-1"></i>
                                Ô chọn chỉ hiển thị dữ liệu gốc <b>đã duyệt</b> và <b>đang hoạt động</b>.
                                Bản ghi mới ở trạng thái <b>Chờ duyệt</b>, cần được duyệt trước khi dùng.
                                Đơn vị tính khai ở tab <b>Vật Tư Của Phòng</b>.
                            </div>
                        </div>

                        <div class="col-lg-5">
                            @include('pages.category.MaterialCategory.classificationFields', [
                                'bag' => $bag,
                                'selected' => $oldClassification,
                                'purchasing' => $bag->any() ? old('purchasing_department') : null,
                                'leadTime' => $bag->any() ? old('lead_time_days') : null,
                            ])
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Chia 2 cột (thông tin bên trái, Phân Loại bên phải) nên cần khung rộng hơn modal-lg mặc định --}}
<style>
    @media (min-width: 992px) {

        #createModal .modal-dialog,
        #updateModal .modal-dialog {
            max-width: 1180px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /* ---------- Mở modal Thêm mới: đưa mọi tiêu chí phân loại về "Chưa xác định" ----------
           Phần JS dùng chung chỉ bỏ tick mọi ô, nhóm radio sẽ không còn ô nào được chọn. */
        $(document).on('click', '.btn-md-create:not([data-modal])', function() {
            $('#createModal').find('.cat-check-input[value=""]').prop('checked', true)
                .closest('.cat-check-item').addClass('is-checked');
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
