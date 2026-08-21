@php $bag = $errors->getBag('updateErrors'); @endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
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
                        <label>Tên Hoá Chất <span class="text-danger">*</span></label>
                        <input type="text" name="name" maxlength="255"
                            class="form-control {{ $bag->has('name') ? 'is-invalid' : '' }}"
                            value="{{ old('name') }}" required>
                        @if ($bag->has('name'))
                            <span class="md-error">{{ $bag->first('name') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Tên Hoạt Chất</label>
                        <input type="text" name="active_ingredient_name" maxlength="255"
                            class="form-control {{ $bag->has('active_ingredient_name') ? 'is-invalid' : '' }}"
                            value="{{ old('active_ingredient_name') }}">
                        @if ($bag->has('active_ingredient_name'))
                            <span class="md-error">{{ $bag->first('active_ingredient_name') }}</span>
                        @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Số CAS</label>
                            <input type="text" name="cas_no" maxlength="100"
                                class="form-control {{ $bag->has('cas_no') ? 'is-invalid' : '' }}"
                                value="{{ old('cas_no') }}">
                            @if ($bag->has('cas_no'))
                                <span class="md-error">{{ $bag->first('cas_no') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label>Số Tài Liệu</label>
                            <input type="text" name="doc_no" maxlength="100"
                                class="form-control {{ $bag->has('doc_no') ? 'is-invalid' : '' }}"
                                value="{{ old('doc_no') }}">
                            @if ($bag->has('doc_no'))
                                <span class="md-error">{{ $bag->first('doc_no') }}</span>
                            @endif
                        </div>
                    </div>

                    @include('pages.materData.shared.formulaInput', ['bag' => $bag])

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
