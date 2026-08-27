{{-- Modal chọn chuẩn đã cấp phát (dùng cho Loại phiếu = Sử dụng) --}}
<div class="modal fade" id="issuedStandardPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 95vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
                    <i class="fas fa-list-check mr-2"></i> Chọn Ống Chuẩn Đã Cấp Phát Cho Tổ
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-3 bg-light">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" id="searchIssuedStandard" class="form-control" placeholder="Tìm tên chất chuẩn, mã ống, lô...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive bg-white rounded shadow-sm border">
                    <table class="table table-hover table-bordered mb-0" id="issuedStandardPickerTable" style="font-size: 0.88rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 50px">STT</th>
                                <th>Mã Ống Chuẩn</th>
                                <th>Chất Chuẩn</th>
                                <th>Qui Cách</th>
                                <th>Nhà Sản Xuất</th>
                                <th>Mục Đích</th>
                                <th>SL Đề Nghị</th>
                                <th>SL Đã Cấp</th>
                                <th>ĐVT</th>
                                <th>Trả Chuẩn</th>
                                <th>Sản Phẩm</th>
                                <th>Chỉ Tiêu</th>
                                <th>KNV</th>
                                <th>Ghi Chú</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Nội dung được load qua AJAX -->
                            <tr><td colspan="15" class="text-center text-muted py-4">Vui lòng chọn Tổ Sử Dụng trước.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#searchIssuedStandard').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#issuedStandardPickerTable tbody tr').filter(function() {
            if ($(this).find('td').length > 1) { // Không filter dòng trống
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            }
        });
    });

    $(document).on('click', '.btn-select-issued-std', function() {
        let $btn = $(this);
        let attachments = $btn.data('attachments') || [];
        if (typeof attachments === 'string') {
            try { attachments = JSON.parse(attachments); } catch(e) { attachments = []; }
        }

        if (typeof window.populateStandardDisplay === 'function') {
            window.populateStandardDisplay({
                import_id: $btn.data('import-id'),
                import_code: $btn.data('import-code'),
                req_item_id: $btn.data('req-item-id'),
                std_name: $btn.data('std-name'),
                remaining: $btn.data('remaining'),
                requested_amount: $btn.data('requested-amount'),
                unit: $btn.data('unit'),
                category_code: $btn.data('category-code') || '',
                supplier: $btn.data('supplier') || '—',
                spec: $btn.data('spec') || '—',
                batch: $btn.data('batch') || '—',
                potency: $btn.data('potency'),
                moisture: $btn.data('moisture'),
                other: $btn.data('other'),
                attachments: attachments,
                expiry_type: $btn.data('expiry-type') || '',
                expired: $btn.data('expired'),
                return_standard: $btn.data('return-standard'),
                product_name: $btn.data('product') || '',
                testing: $btn.data('testing') || ''
            });
        }

        $('#issuedStandardPickerModal').modal('hide');
    });
});
</script>
