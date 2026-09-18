@php
    $bag = $errors->getBag('updateErrors');

    // Sửa dở bị lỗi validate thì đổ lại đúng những gì người dùng vừa chọn;
    // mở modal bình thường thì JS ở dataTable đổ dữ liệu của dòng đang sửa vào.
    $oldCriteria = $bag->any() ? (array) old('criteria', []) : [];
    $oldSteps = $bag->any() ? array_values((array) old('steps', [])) : [];
    $oldSigners = $bag->any() ? array_values((array) old('signers', [])) : [];
@endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Cập Nhật {{ $mdTitle }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-body">

                    @include('pages.materData.MaterialSignFlow.formFields', [
                        'bag' => $bag,
                        'selected' => $oldCriteria,
                        'selectedClass' => $bag->any() ? old('classification_id') : null,
                        'selectedSteps' => $oldSteps,
                        'selectedSigners' => $oldSigners,
                    ])

                    <div class="form-group">
                        <label>Lý Do Điều Chỉnh <span class="text-danger">*</span></label>
                        <textarea name="change_reason" rows="2" maxlength="500" required
                            class="form-control @error('change_reason', 'updateErrors') is-invalid @enderror"
                            placeholder="Nêu rõ lý do sửa quy trình này">{{ old('change_reason') }}</textarea>
                        @error('change_reason', 'updateErrors')
                            <span class="md-error">{{ $message }}</span>
                        @enderror
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

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
