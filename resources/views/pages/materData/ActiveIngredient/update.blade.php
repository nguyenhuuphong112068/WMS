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

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="aiIsTableAUpd" name="is_table_a"
                                value="1" data-table-a-toggle {{ old('is_table_a') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="aiIsTableAUpd">
                                Thuộc <b>Bảng A</b> Phụ lục IV Nghị định 24/2026/NĐ-CP (chất có tên, có ngưỡng tồn trữ)
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Tick chọn nếu hoạt chất thuộc Bảng A Phụ lục IV Nghị định số 24/2026/NĐ-CP (bắt buộc khai báo ngưỡng tồn trữ).
                        </small>
                    </div>

                    <div class="form-group" data-table-a-only style="{{ old('is_table_a') ? '' : 'display: none;' }}">
                        <label>Ngưỡng Khối Lượng Tồn Trữ Lớn Nhất Tại Một Thời Điểm (kg) <span class="text-danger">*</span></label>
                        <input type="number" name="threshold_kg" step="0.001" min="0.001"
                            class="form-control {{ $bag->has('threshold_kg') ? 'is-invalid' : '' }}"
                            value="{{ old('threshold_kg') }}" placeholder="Ví dụ: 50000" {{ old('is_table_a') ? 'required' : '' }}>
                        @if ($bag->has('threshold_kg'))
                            <span class="md-error">{{ $bag->first('threshold_kg') }}</span>
                        @endif
                        <small class="text-muted d-block mt-1">
                            Bắt buộc nhập ngưỡng tồn trữ đối với hoạt chất thuộc Bảng A (theo Phụ lục IV Nghị định 24/2026/NĐ-CP).
                        </small>
                    </div>

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
