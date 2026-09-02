@php $bag = $errors->getBag('requestRejectErrors'); @endphp

{{-- Modal từ chối một đề nghị cấp phát vật tư đang chờ ký. Id do JS đổ vào khi bấm Từ chối. --}}
<div class="modal fade md-modal" id="reqRejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ban"></i> Từ Chối Đề Nghị Cấp Phát Vật Tư</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route($expRoute . 'requestReject') }}" method="POST">
                @csrf
                <input type="hidden" name="request_list_id" value="{{ old('request_list_id') }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã Đề Nghị</label>
                        <input type="text" class="form-control exp-readonly req-reject-code" readonly value="">
                    </div>
                    <div class="form-group">
                        <label>Lý Do Từ Chối <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" rows="3" maxlength="500"
                            class="form-control {{ $bag->has('reject_reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Vật tư còn tồn đủ dùng, chưa cần cấp thêm" required>{{ old('reject_reason') }}</textarea>
                        @if ($bag->has('reject_reason')) <div class="md-error text-danger small">{{ $bag->first('reject_reason') }}</div> @endif
                    </div>
                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đề nghị bị từ chối quay về <b>Bị từ chối</b>, Tổ sửa lại rồi trình ký lại từ đầu.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-ban mr-1"></i> Từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $(document).on('click', '.btn-req-reject', function () {
            $('#reqRejectModal [name="request_list_id"]').val($(this).data('id'));
            $('#reqRejectModal .req-reject-code').val($(this).data('code'));
            $('#reqRejectModal').modal('show');
        });
    });
</script>

@if ($bag->any())
    <script>document.addEventListener('DOMContentLoaded', function () { $('#reqRejectModal').modal('show'); });</script>
@endif
