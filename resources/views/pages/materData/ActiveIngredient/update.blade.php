@php
    $bag = $errors->getBag('updateErrors');
    $oldGroups = array_map('intval', (array) old('groups', []));
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

                    <div class="form-group">
                        <label>Tên khoa học (danh pháp IUPAC) <span class="text-danger">*</span></label>
                        <input type="text" name="name" maxlength="255"
                            class="form-control {{ $bag->has('name') ? 'is-invalid' : '' }}" value="{{ old('name') }}"
                            required>
                        @if ($bag->has('name'))
                            <span class="md-error">{{ $bag->first('name') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Tên chất</label>
                        <input type="text" name="name_en" maxlength="255"
                            class="form-control {{ $bag->has('name_en') ? 'is-invalid' : '' }}"
                            value="{{ old('name_en') }}">
                        @if ($bag->has('name_en'))
                            <span class="md-error">{{ $bag->first('name_en') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Mã số CAS</label>
                        <input type="text" name="cas_no" maxlength="100"
                            class="form-control {{ $bag->has('cas_no') ? 'is-invalid' : '' }}"
                            value="{{ old('cas_no') }}">
                        @if ($bag->has('cas_no'))
                            <span class="md-error">{{ $bag->first('cas_no') }}</span>
                        @endif
                    </div>

                    @include('pages.materData.shared.formulaInput', ['bag' => $bag])

                    @include('pages.materData.ActiveIngredient.groupFields', [
                        'bag' => $bag,
                        'groupLabels' => $groupLabels,
                        'singleSubstanceGroups' => $singleSubstanceGroups,
                        'oldGroups' => $oldGroups,
                    ])

                    <div class="md-hint">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Sau khi sửa, bản ghi quay về trạng thái <b>Chờ duyệt</b> và cần được duyệt lại.
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
