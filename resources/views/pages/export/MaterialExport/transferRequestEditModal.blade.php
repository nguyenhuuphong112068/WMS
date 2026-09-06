{{--
| Modal ĐIỀU CHỈNH ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN đang lưu tạm.
| Chỉ dựng cho các phiếu phòng mình gửi đi và còn ở trạng thái draft.
|
| Dòng vật tư, nút thêm dòng và picker "Danh mục tồn của phòng" dùng chung với modal tạo
| (transferRequestModal + transferRowFields), nên hai form không lệch nhau.
--}}
@foreach ($transferSent->where('status', 'draft') as $req)
    @php $rows = ($transferItems[$req->id] ?? collect())->values(); @endphp
    <div class="modal fade md-modal" id="matTransferEditModal_{{ $req->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 85%; width: 85%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2 text-warning"></i> Điều Chỉnh Đề Nghị Liên Phòng Ban: {{ $req->code }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form action="{{ route('pages.export.materialExport.transferRequestUpdate') }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="transfer_request_id" value="{{ $req->id }}">
                    <input type="hidden" name="action_type" class="edit-mat-transfer-action-type" value="draft">

                    <div class="modal-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap: 8px;">
                            <div class="d-flex align-items-center">
                                <label class="font-weight-bold mb-0 mr-2 text-nowrap" style="font-size: 0.95rem;">
                                    <i class="fas fa-building mr-1 text-primary"></i> Gửi Đến Phòng Ban <span class="text-danger">*</span>:
                                </label>
                                <div style="min-width: 280px;">
                                    <select name="to_department_id" class="form-control font-weight-bold" style="height: 38px !important;" required>
                                        <option value="">-- Chọn phòng ban đang giữ vật tư --</option>
                                        @foreach ($transferDepartments as $dept)
                                            <option value="{{ $dept->id }}" {{ $req->to_department_id == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }} ({{ $dept->shortName }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex align-items-center" style="gap: 8px;">
                                <button type="button" class="btn btn-sm btn-outline-info shadow-sm btn-open-mat-stock-picker"
                                    data-target-rows="#matTransferEditRows_{{ $req->id }}">
                                    <i class="fas fa-boxes-stacked mr-1"></i> Danh mục vật tư phòng nguồn
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-add-edit-mat-transfer-row shadow-sm"
                                    data-rows="#matTransferEditRows_{{ $req->id }}">
                                    <i class="fas fa-plus mr-1"></i> Thêm vật tư
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-bordered mb-0 mat-transfer-table" style="font-size: 0.9rem;">
                                @include('pages.export.MaterialExport.transferRowsHead')
                                <tbody id="matTransferEditRows_{{ $req->id }}" data-next-idx="{{ max($rows->count(), 1) }}">
                                    @for ($idx = 0; $idx < max($rows->count(), 1); $idx++)
                                        @include('pages.export.MaterialExport.transferRowFields', [
                                            'idx' => $idx,
                                            'item' => $rows->get($idx),
                                            'onlyRow' => $rows->count() <= 1,
                                        ])
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        <div class="form-group mt-3 mb-0">
                            <label class="font-weight-bold" style="font-size: 0.9rem;">Ghi chú chung</label>
                            <textarea name="note" class="form-control" rows="2">{{ $req->note }}</textarea>
                        </div>
                    </div>

                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-outline-primary btn-submit-edit-mat-transfer" data-action="draft"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button>
                        <button type="submit" class="btn btn-primary btn-submit-edit-mat-transfer" data-action="send"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.btn-submit-edit-mat-transfer', function() {
            $(this).closest('form').find('.edit-mat-transfer-action-type').val($(this).data('action') || 'draft');
        });

        $(document).on('click', '.btn-add-edit-mat-transfer-row', function() {
            window.matAddTransferRow($(this).data('rows'));
        });
    });
</script>
