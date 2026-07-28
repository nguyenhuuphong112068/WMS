<div class="modal fade" id="issueReissueModal" tabindex="-1" role="dialog"
    aria-labelledby="issueReissueModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="issueReissueModalLabel">
                    <i class="fas fa-print"></i> Bước 4 &mdash; Cấp lại hồ sơ &amp; ký tên
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.reissue.issue') }}" method="POST" class="form-single-submit">
                @csrf
                <input type="hidden" name="id" id="issue_id">
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <div><b>Đề nghị:</b> <span id="issue_doc_label"></span></div>
                        <div><b>Số trang đã duyệt:</b> <span id="issue_requested_pages" class="text-primary"></span></div>
                    </div>

                    <div class="form-group">
                        <label>Các trang đã cấp lại <span class="text-danger">*</span></label>
                        <input type="text" name="issued_pages" id="issued_pages" class="form-control" required>
                        <small class="text-muted">Chỉ cấp lại các trang đã được TP/ PP. ĐBCL duyệt.</small>
                        @if ($errors->issueErrors->has('issued_pages'))
                            <span class="d-block text-danger small">{{ $errors->issueErrors->first('issued_pages') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="wrong_pages_voided"
                                name="wrong_pages_voided" value="1" required>
                            <label class="custom-control-label" for="wrong_pages_voided">
                                Đã <b>gạch bỏ các trang hồ sơ sai</b> để tránh nhầm lẫn
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Người cấp hồ sơ</label>
                        <input type="text" class="form-control"
                            value="{{ session('user')['fullName'] }} - {{ session('user')['selected_department'] }}"
                            disabled>
                        <small class="text-muted">
                            Người đang đăng nhập &mdash; ký tên vào sổ với vai trò nhân sự cấp lại hồ sơ.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Ngày cấp lại</label>
                        <input type="text" class="form-control" data-default-now disabled
                            value="{{ date('d/m/Y') }}">
                        <small class="text-muted">
                            Hệ thống tự ghi ngày cấp lại tại thời điểm ký &mdash; không sửa được.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="issue_note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Xác nhận cấp lại &amp; ký tên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
