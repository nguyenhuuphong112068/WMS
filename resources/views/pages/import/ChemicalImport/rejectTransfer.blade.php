@php $bag = $errors->getBag('rejectErrors'); @endphp

<div class="modal fade md-modal" id="rejectTransferModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-xmark-circle"></i> Từ Chối Nhận Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($impRoute . 'rejectTransfer') }}" method="POST">
                @csrf
                <input type="hidden" name="export_id" value="{{ old('export_id') }}">

                <div class="modal-body">
                    <div class="md-hint mb-3">
                        <i class="fas fa-flask mr-1"></i>
                        <span class="imp-reject-subtitle"></span>
                    </div>

                    <div class="form-group">
                        <label>Lý Do Từ Chối <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" rows="3" maxlength="500"
                            class="form-control {{ $bag->has('reject_reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Cân lại chỉ được 1.8 so với 2 trên phiếu / Bao bì rách / Sai hoá chất"
                            required>{{ old('reject_reason') }}</textarea>
                        @if ($bag->has('reject_reason'))
                            <span class="md-error">{{ $bag->first('reject_reason') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-triangle-exclamation mr-1"></i>
                        Từ chối sẽ <b>khoá phiếu chuyển</b> và trả số lượng lại tồn của phòng gửi ngay. Phiếu đã từ
                        chối <b>không mở lại được</b> - phòng gửi phải lập phiếu chuyển mới. Lý do sẽ hiện trong lịch
                        sử phiếu chuyển bên phòng gửi.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-xmark mr-1"></i> Từ chối nhận
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#rejectTransferModal').modal('show');
        });
    </script>
@endif
