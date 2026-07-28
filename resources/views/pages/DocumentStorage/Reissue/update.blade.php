<div class="modal fade" id="updateReissueModal" tabindex="-1" role="dialog"
    aria-labelledby="updateReissueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="updateReissueModalLabel">
                    <i class="fas fa-edit"></i> Sửa nội dung đề nghị cấp lại hồ sơ
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.reissue.update') }}" method="POST" class="form-single-submit">
                @csrf
                <input type="hidden" name="id" id="update_id">
                <div class="modal-body">
                    <div class="alert alert-light border small mb-3">
                        <i class="fas fa-info-circle text-info"></i>
                        Chỉ sửa được khi <b>QĐ/P.QĐ PXSX chưa ký duyệt</b>.
                    </div>

                    <div class="form-group">
                        <label>Ngày <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" id="update_request_date" class="form-control" required>
                        @if ($errors->updateErrors->has('request_date'))
                            <span class="d-block text-danger small">{{ $errors->updateErrors->first('request_date') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Tên sản phẩm BMR/ BPR <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" id="update_product_name" class="form-control" required
                            list="product_name_list">
                        @if ($errors->updateErrors->has('product_name'))
                            <span class="d-block text-danger small">{{ $errors->updateErrors->first('product_name') }}</span>
                        @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số quy trình</label>
                            <input type="text" name="process_no" id="update_process_no" class="form-control">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Ấn bản</label>
                            <input type="text" name="edition" id="update_edition" class="form-control">
                        </div>
                        <div class="form-group col-md-5">
                            <label>Số trang cần xin lại <span class="text-danger">*</span></label>
                            <input type="text" name="pages" id="update_pages" class="form-control" required>
                            @if ($errors->updateErrors->has('pages'))
                                <span class="d-block text-danger small">{{ $errors->updateErrors->first('pages') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lý do xin lại <span class="text-danger">*</span></label>
                        <textarea name="reason" id="update_reason" class="form-control" rows="2" required></textarea>
                        @if ($errors->updateErrors->has('reason'))
                            <span class="d-block text-danger small">{{ $errors->updateErrors->first('reason') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>CAPA</label>
                        <textarea name="capa" id="update_capa" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="note" id="update_note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
