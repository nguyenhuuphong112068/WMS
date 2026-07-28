<div class="modal fade" id="forwardRoutingModal" tabindex="-1" role="dialog"
    aria-labelledby="forwardRoutingModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="forwardRoutingModalLabel">
                    <i class="fas fa-share"></i> Bước 2 &mdash; Giao tài liệu cho người tiếp theo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.routing.forward') }}" method="POST">
                @csrf
                <input type="hidden" name="routing_id" id="forward_routing_id">
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <div><b>Hồ sơ:</b> <span id="forward_doc_label"></span></div>
                        <div><b>Đang giữ:</b> <span id="forward_current_holder" class="text-primary"></span></div>
                    </div>

                    <div class="form-group">
                        <label>Giao cho <span class="text-danger">*</span></label>
                        <select name="to_user_id" id="forward_to_user_id" class="form-control select2" required
                            style="width: 100%;" data-placeholder="Chọn người nhận tiếp theo">
                            <option value="">-- Chọn người nhận tiếp theo --</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">
                                    {{ $u->fullName ?: $u->userName }}{{ $u->deparment ? ' - ' . $u->deparment : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->forwardErrors->has('to_user_id'))
                            <span class="d-block text-danger small">{{ $errors->forwardErrors->first('to_user_id') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Ngày giao <span class="text-danger">*</span></label>
                        <input type="date" name="handover_date" class="form-control" required
                            value="{{ date('Y-m-d') }}">
                        @if ($errors->forwardErrors->has('handover_date'))
                            <span class="d-block text-danger small">{{ $errors->forwardErrors->first('handover_date') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Ghi chú / Lý do chuyển</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="Ví dụ: chuyển ký duyệt, chuyển xem xét..."></textarea>
                    </div>

                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Sau khi giao, chặng mới ở trạng thái <b>chờ xác nhận</b> cho đến khi người nhận bấm nút
                        <i class="fas fa-check"></i> xác nhận đã nhận.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-share"></i> Chuyển tiếp
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
