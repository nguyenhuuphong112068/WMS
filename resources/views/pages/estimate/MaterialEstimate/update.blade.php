@php $bag = $errors->getBag('updateErrors'); @endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Cập Nhật Phiếu Dự Trù Vật Tư</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($estRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tháng Dự Trù <span class="text-danger">*</span></label>
                            <select name="month" class="form-control {{ $bag->has('month') ? 'is-invalid' : '' }}"
                                required>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ (int) old('month') === $m ? 'selected' : '' }}>
                                        Tháng {{ $m }}
                                    </option>
                                @endfor
                            </select>
                            @if ($bag->has('month'))
                                <span class="md-error">{{ $bag->first('month') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label>Năm <span class="text-danger">*</span></label>
                            <input type="number" name="year" min="2020" max="2100"
                                class="form-control {{ $bag->has('year') ? 'is-invalid' : '' }}"
                                value="{{ old('year') }}" required>
                            @if ($bag->has('year'))
                                <span class="md-error">{{ $bag->first('year') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đổi tháng/năm thì <b>mã phiếu được sinh lại</b> theo kỳ mới. Chỉ sửa được khi phiếu còn Nháp
                        hoặc Bị từ chối.
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
            $('#updateModal').modal('show');
        });
    </script>
@endif
