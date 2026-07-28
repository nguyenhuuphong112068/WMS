<div class="modal fade" id="qaReviewReissueModal" tabindex="-1" role="dialog"
    aria-labelledby="qaReviewReissueModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="qaReviewReissueModalLabel">
                    <i class="fas fa-user-check"></i> Bước 3 &mdash; Ý kiến TP/ PP. ĐBCL
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.reissue.qaReview') }}" method="POST" class="form-single-submit">
                @csrf
                <input type="hidden" name="id" id="qa_id">
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <div><b>Đề nghị:</b> <span id="qa_doc_label"></span></div>
                        <div><b>Số trang xin lại:</b> <span id="qa_pages_label" class="text-primary"></span></div>
                    </div>

                    <div class="form-group">
                        <label>Ý kiến <span class="text-danger">*</span></label>
                        <div class="d-flex" style="gap: 20px;">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="qa_decision_agree" name="qa_decision" value="agree"
                                    class="custom-control-input" required>
                                <label class="custom-control-label text-success font-weight-bold"
                                    for="qa_decision_agree">Đồng ý</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="qa_decision_disagree" name="qa_decision" value="disagree"
                                    class="custom-control-input" required>
                                <label class="custom-control-label text-danger font-weight-bold"
                                    for="qa_decision_disagree">Không đồng ý</label>
                            </div>
                        </div>
                        @if ($errors->qaErrors->has('qa_decision'))
                            <span class="d-block text-danger small">{{ $errors->qaErrors->first('qa_decision') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Nội dung ý kiến</label>
                        <textarea name="qa_opinion" class="form-control" rows="2"
                            placeholder="Ghi rõ ý kiến của TP/ PP. ĐBCL..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Người ký</label>
                        <input type="text" class="form-control"
                            value="{{ session('user')['fullName'] }} - {{ session('user')['selected_department'] }}"
                            disabled>
                        <small class="text-muted">
                            Người đang đăng nhập &mdash; ký tên với vai trò TP/ PP. ĐBCL.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Ngày ký</label>
                        <input type="text" class="form-control" data-default-now disabled
                            value="{{ date('d/m/Y') }}">
                        <small class="text-muted">
                            Hệ thống tự ghi ngày ký tại thời điểm bấm ký &mdash; không sửa được.
                        </small>
                    </div>

                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Nếu <b>đồng ý</b>, nhân sự xin hồ sơ mang sổ + các trang hồ sơ sai đến bộ phận ban hành để cấp lại.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-signature"></i> Ghi ý kiến &amp; ký tên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
