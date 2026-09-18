@php
    $bag = $errors->getBag('createErrors');

    // Khai dở bị lỗi validate thì đổ lại đúng những gì người dùng vừa chọn
    $oldCriteria = $bag->any() ? (array) old('criteria', []) : [];
    $oldSteps = $bag->any() ? array_values((array) old('steps', [])) : [];
    $oldSigners = $bag->any() ? array_values((array) old('signers', [])) : [];
@endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Thêm {{ $mdTitle }} Mới</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    @include('pages.materData.MaterialSignFlow.formFields', [
                        'bag' => $bag,
                        'selected' => $oldCriteria,
                        'selectedClass' => $bag->any() ? old('classification_id') : null,
                        'selectedSteps' => $oldSteps,
                        'selectedSigners' => $oldSigners,
                    ])

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Điều kiện để <b>Tất cả</b> nghĩa là không xét tiêu chí đó. Vật tư khớp
                        nhiều quy trình thì áp dụng quy trình khai nhiều điều kiện nhất.
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

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
