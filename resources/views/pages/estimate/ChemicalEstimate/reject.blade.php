@php $bag = $errors->getBag('rejectErrors'); @endphp

{{--
| Modal từ chối một phiếu đang chờ ký. Người ký bắt buộc nhập lý do để phòng ban biết
| phải sửa gì trước khi trình ký lại. Id và mã phiếu do JS đổ vào khi bấm nút Từ chối.
--}}
<div class="modal fade md-modal" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ban"></i> Từ Chối Phiếu Dự Trù</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($estRoute . 'reject') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">

                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã Phiếu</label>
                        <input type="text" class="form-control est-readonly est-reject-code" readonly value="">
                    </div>

                    <div class="form-group">
                        <label>Lý Do Từ Chối <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" rows="3" maxlength="500"
                            class="form-control {{ $bag->has('reject_reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Số lượng dự trù tháng 10 quá cao so với định mức sử dụng" required>{{ old('reject_reason') }}</textarea>
                        @if ($bag->has('reject_reason'))
                            <span class="md-error">{{ $bag->first('reject_reason') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Phiếu bị từ chối quay về trạng thái <b>Bị từ chối</b>, phòng ban sửa lại rồi trình ký lại
                        từ bước 1. Lý do được lưu vào nhật ký trình ký.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban mr-1"></i> Từ chối
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#rejectModal').modal('show');
        });
    </script>
@endif
