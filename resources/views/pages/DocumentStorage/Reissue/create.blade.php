<datalist id="product_name_list">
    @foreach ($products as $p)
        <option value="{{ $p->name }}">{{ $p->code }}</option>
    @endforeach
</datalist>

<div class="modal fade" id="createReissueModal" tabindex="-1" role="dialog"
    aria-labelledby="createReissueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="createReissueModalLabel">
                    <i class="fas fa-file-signature"></i> Bước 1 &mdash; Ghi sổ xin cấp lại hồ sơ
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.reissue.store') }}" method="POST" class="form-single-submit">
                @csrf
                {{-- Token sinh mới mỗi lần tải trang: bấm "Ghi sổ" nhiều lần cũng chỉ ghi một dòng --}}
                <input type="hidden" name="submit_token" value="{{ (string) Illuminate\Support\Str::uuid() }}">
                <div class="modal-body">
                    <div class="alert alert-light border small mb-3">
                        <i class="fas fa-info-circle text-info"></i>
                        Người xin tự trình bày, ghi thông tin vào sổ <b>sau khi QĐ/P.QĐ PXSX đã đồng ý</b>.
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Ngày <span class="text-danger">*</span></label>
                            <input type="date" name="request_date" class="form-control" required
                                data-default-now @if (old('request_date')) data-touched="1" @endif
                                value="{{ old('request_date', date('Y-m-d')) }}">
                            @if ($errors->createErrors->has('request_date'))
                                <span class="d-block text-danger small">{{ $errors->createErrors->first('request_date') }}</span>
                            @endif
                        </div>
                        <div class="form-group col-md-8">
                            <label>Bộ Phận/Phòng Ban</label>
                            <input type="text" class="form-control"
                                value="{{ session('user')['selected_department'] }}" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tên sản phẩm BMR/ BPR <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" class="form-control" required
                            list="product_name_list" value="{{ old('product_name') }}"
                            placeholder="Chọn từ danh mục hoặc gõ tên sản phẩm">
                        @if ($errors->createErrors->has('product_name'))
                            <span class="d-block text-danger small">{{ $errors->createErrors->first('product_name') }}</span>
                        @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số quy trình</label>
                            <input type="text" name="process_no" class="form-control"
                                value="{{ old('process_no') }}" placeholder="VD: QT-SX-001">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Ấn bản</label>
                            <input type="text" name="edition" class="form-control"
                                value="{{ old('edition') }}" placeholder="VD: 02">
                        </div>
                        <div class="form-group col-md-5">
                            <label>Số trang cần xin lại <span class="text-danger">*</span></label>
                            <input type="text" name="pages" class="form-control" required
                                value="{{ old('pages') }}" placeholder="VD: 3, 5-7">
                            @if ($errors->createErrors->has('pages'))
                                <span class="d-block text-danger small">{{ $errors->createErrors->first('pages') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lý do xin lại <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="2" required
                            placeholder="Trình bày lý do cần cấp lại trang hồ sơ...">{{ old('reason') }}</textarea>
                        @if ($errors->createErrors->has('reason'))
                            <span class="d-block text-danger small">{{ $errors->createErrors->first('reason') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>CAPA</label>
                        <textarea name="capa" class="form-control" rows="2"
                            placeholder="Hành động khắc phục / phòng ngừa (nếu có)...">{{ old('capa') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Người đề nghị</label>
                        <input type="text" class="form-control"
                            value="{{ session('user')['fullName'] }} - {{ session('user')['selected_department'] }}"
                            disabled>
                        <small class="text-muted">Người đang đăng nhập &mdash; ký tên vào cột "Người đề nghị".</small>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Ghi sổ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->createErrors->any())
    <script>
        // jQuery được nạp ở cuối trang (layout.js) nên phải đợi DOMContentLoaded.
        document.addEventListener('DOMContentLoaded', function() {
            $('#createReissueModal').modal('show');
        });
    </script>
@endif
