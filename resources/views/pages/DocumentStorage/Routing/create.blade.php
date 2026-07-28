<div class="modal fade" id="createRoutingModal" tabindex="-1" role="dialog"
    aria-labelledby="createRoutingModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="createRoutingModalLabel">
                    <i class="fas fa-file-signature"></i> Bước 1 &mdash; Tiếp nhận &amp; khởi tạo hồ sơ
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.routing.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Bộ Phận/Phòng Ban</label>
                        <input type="text" class="form-control"
                            value="{{ session('user')['selected_department'] }}" disabled>
                    </div>

                    <div class="form-group">
                        <label>Mã hồ sơ</label>
                        <input type="text" name="code" class="form-control"
                            value="{{ old('code') }}" placeholder="Bỏ trống để hệ thống tự sinh mã (HS-YYYYMM-xxxx)">
                        <small class="text-muted">Không bắt buộc &mdash; để trống hệ thống sẽ tự cấp mã.</small>
                        @if ($errors->createErrors->has('code'))
                            <span class="d-block text-danger small">{{ $errors->createErrors->first('code') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Tên tài liệu <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                            value="{{ old('name') }}" placeholder="Nhập tên tài liệu">
                        @if ($errors->createErrors->has('name'))
                            <span class="d-block text-danger small">{{ $errors->createErrors->first('name') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Người tiếp nhận</label>
                        <input type="text" class="form-control"
                            value="{{ session('user')['fullName'] }} - {{ session('user')['selected_department'] }}"
                            disabled>
                        <small class="text-muted">
                            Người đang đăng nhập &mdash; là người tiếp nhận hồ sơ từ bộ phận khác.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Ngày nhận <span class="text-danger">*</span></label>
                        <input type="date" name="received_date" class="form-control" required
                            value="{{ old('received_date', date('Y-m-d')) }}">
                        @if ($errors->createErrors->has('received_date'))
                            <span class="d-block text-danger small">{{ $errors->createErrors->first('received_date') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="Nội dung / mục đích luân chuyển...">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Khởi tạo hồ sơ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#createRoutingModal').modal('show');
        });
    </script>
@endif
