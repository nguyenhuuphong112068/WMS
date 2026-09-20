{{--
| GỬI ĐỀ NGHỊ LIÊN PHÒNG BAN TỪ CÁC VẬT TƯ ĐÃ CHỌN Ở TAB "DANH SÁCH VẬT TƯ CẦN DỰ TRÙ"
|
| Chỉ mở được khi mọi vật tư đã chọn đều có BỘ PHẬN MUA HÀNG là Hành Chánh và nằm trong
| danh mục công ty (xem watchlistTable.blade.php). Phiếu ghi vào material_transfer_requests
| - cùng bộ bảng với tab "Đề nghị chuyển liên phòng ban" bên SỬ DỤNG VẬT TƯ, nơi phòng nhận
| cấp phát và phòng mình xác nhận nhận hàng.
--}}
@php
    $wlTrBag = $errors->getBag('watchlistTransferErrors');
    $wlTrDept = (int) old('to_department_id', $adminDepartmentId ?? 0);
@endphp

<div class="modal fade md-modal" id="watchlistTransferModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-share-square"></i> Đề Nghị Liên Phòng Ban</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($estRoute . 'watchlistTransferStore') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tiêu Đề Đề Nghị <span class="text-danger">*</span></label>
                            <input type="text" name="title" maxlength="255" required
                                class="form-control {{ $wlTrBag->has('title') ? 'is-invalid' : '' }}"
                                value="{{ old('title', 'Đề nghị cấp vật tư ' . now()->format('m/Y')) }}">
                            @if ($wlTrBag->has('title'))
                                <span class="md-error">{{ $wlTrBag->first('title') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Phòng Ban Nhận Đề Nghị <span class="text-danger">*</span></label>
                            <select name="to_department_id" required
                                class="form-control est-select {{ $wlTrBag->has('to_department_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn phòng ban --</option>
                                @foreach ($transferDepartments as $dept)
                                    <option value="{{ $dept->id }}" {{ $wlTrDept === (int) $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->shortName }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($wlTrBag->has('to_department_id'))
                                <span class="md-error">{{ $wlTrBag->first('to_department_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-2">
                            <label>Ngày Cần Dùng</label>
                            <input type="date" name="needed_date"
                                class="form-control {{ $wlTrBag->has('needed_date') ? 'is-invalid' : '' }}"
                                value="{{ old('needed_date') }}">
                            @if ($wlTrBag->has('needed_date'))
                                <span class="md-error">{{ $wlTrBag->first('needed_date') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-2">
                            <label>Ghi Chú</label>
                            <input type="text" name="note" maxlength="500"
                                class="form-control {{ $wlTrBag->has('note') ? 'is-invalid' : '' }}"
                                value="{{ old('note') }}">
                            @if ($wlTrBag->has('note'))
                                <span class="md-error">{{ $wlTrBag->first('note') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered md-table wl-items-table w-100" data-no-datatable>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 45px">STT</th>
                                    <th style="width: 260px">Vật Tư</th>
                                    <th>Quy Cách</th>
                                    <th style="width: 140px">Số Lượng Đề Nghị <span class="text-danger">*</span></th>
                                    <th class="text-center" style="width: 90px">ĐVT</th>
                                    <th style="width: 220px">Ghi Chú</th>
                                </tr>
                            </thead>
                            <tbody class="wl-items-body"></tbody>
                        </table>
                    </div>

                    <template class="wl-row-template">
                        <tr>
                            <td class="text-center wl-stt"></td>
                            <td>
                                <div class="font-weight-bold" data-field="display_name"></div>
                                <input type="hidden" name="items[__INDEX__][category_id]" data-field="category_id">
                                <input type="hidden" name="items[__INDEX__][requested_unit]" data-field="requested_unit">
                            </td>
                            <td class="md-sub" data-field="display_spec"></td>
                            <td>
                                <input type="number" step="0.0001" min="0.0001" required
                                    class="form-control form-control-sm" name="items[__INDEX__][requested_amount]"
                                    data-field="requested_amount">
                            </td>
                            <td class="text-center" data-field="display_unit"></td>
                            <td>
                                <input type="text" maxlength="500" class="form-control form-control-sm"
                                    name="items[__INDEX__][note]" data-field="note">
                            </td>
                        </tr>
                    </template>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#watchlistTransferModal').on('shown.bs.modal', function() {
            $(this).find('.wl-items-body tr').each(function(index) {
                $(this).find('.wl-stt').text(index + 1);
            });
        });
    });
</script>
