<div class="modal fade" id="pmSignReissueModal" tabindex="-1" role="dialog"
    aria-labelledby="pmSignReissueModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="pmSignReissueModalLabel">
                    <i class="fas fa-signature"></i> Bước 2 &mdash; QĐ/ P.QĐ PXSX ký duyệt
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.reissue.pmSign') }}" method="POST" class="form-single-submit">
                @csrf
                <input type="hidden" name="id" id="pm_id">
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <div><b>Đề nghị:</b> <span id="pm_doc_label"></span></div>
                    </div>

                    <div class="form-group">
                        <label>Người ký duyệt</label>
                        <input type="text" class="form-control"
                            value="{{ session('user')['fullName'] }} - {{ session('user')['selected_department'] }}"
                            disabled>
                        <small class="text-muted">
                            Người đang đăng nhập &mdash; ký tên với vai trò QĐ/ P.QĐ PXSX.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Ngày ký duyệt</label>
                        <input type="text" class="form-control" data-default-now disabled
                            value="{{ date('d/m/Y') }}">
                        <small class="text-muted">
                            Hệ thống tự ghi ngày ký tại thời điểm bấm ký &mdash; không sửa được.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="pm_note" class="form-control" rows="2"></textarea>
                    </div>

                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Sau khi ký, chuyển sổ và các trang hồ sơ xin lại đến <b>TP/ PP. ĐBCL</b> xem và cho ý kiến.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-signature"></i> Đồng ý &amp; ký tên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
