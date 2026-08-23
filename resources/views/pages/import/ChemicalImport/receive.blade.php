@php
    $bag = $errors->getBag('receiveErrors');

    // Form lỗi validate thì modal mở lại, lấy sẵn lô cũ để các ô chỉ đọc không bị trống
    $impOldTransfer = $bag->any() ? $pendingTransfers->firstWhere('id', (int) old('export_id')) : null;
@endphp

<div class="modal fade md-modal" id="receiveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-inbox"></i> Nhận Hoá Chất Từ Phòng Ban Khác</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($impRoute . 'receive') }}" method="POST">
                @csrf
                <input type="hidden" name="export_id" value="{{ old('export_id') }}">

                <div class="modal-body">

                    {{-- Thông tin bản chất của lô, chép nguyên từ phòng gửi, không cho sửa --}}
                    <div class="imp-receive-info">
                        <div class="box">
                            <label>Phòng Ban Gửi</label>
                            <div class="val imp-rcv-from">{{ $impOldTransfer->from_department_name ?? '' }}</div>
                        </div>
                        <div class="box">
                            <label>Mã Gốc</label>
                            <div class="val imp-rcv-code">{{ $impOldTransfer->source_code ?? '' }}</div>
                        </div>
                        <div class="box">
                            <label>Hoá Chất</label>
                            <div class="val imp-rcv-chem">{{ $impOldTransfer->chem_name ?? '' }}</div>
                        </div>
                        <div class="box">
                            <label>Số Lượng</label>
                            <div class="val imp-rcv-amount">
                                {{ $impOldTransfer ? $impNum($impOldTransfer->amount) . ' ' . ($impOldTransfer->unit_short_name ?: $impOldTransfer->unit_name) : '' }}
                            </div>
                        </div>
                        <div class="box">
                            <label>Số Lô</label>
                            <div class="val imp-rcv-batch">{{ $impOldTransfer->batch_no ?? '' }}</div>
                        </div>
                        <div class="box">
                            <label>Hạn Dùng</label>
                            <div class="val imp-rcv-expired">{{ $impOldTransfer ? $impDate($impOldTransfer->expired_date) : '' }}</div>
                        </div>
                        <div class="box">
                            <label>Ngày Chuyển</label>
                            <div class="val imp-rcv-date">{{ $impOldTransfer ? $impDate($impOldTransfer->exported_date) : '' }}</div>
                        </div>
                        <div class="box">
                            <label>Người Chuyển</label>
                            <div class="val imp-rcv-by">{{ $impOldTransfer->exported_by ?? '' }}</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Ngày Nhận <span class="text-danger">*</span></label>
                            <input type="date" name="imported_date"
                                class="form-control {{ $bag->has('imported_date') ? 'is-invalid' : '' }}"
                                value="{{ old('imported_date', now()->format('Y-m-d')) }}" required>
                            @if ($bag->has('imported_date'))
                                <span class="md-error">{{ $bag->first('imported_date') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-8">
                            <label>Định Khu</label>
                            <select name="location_id"
                                class="form-control imp-select {{ $bag->has('location_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chưa xác định định khu --</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->code ? $location->code . ' - ' : '' }}{{ $location->name }}{{ $location->warehouse_name ? ' (' . $location->warehouse_name . ($location->room_name ? ' / ' . $location->room_name : '') . ($location->shelf_name ? ' / ' . $location->shelf_name : '') . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('location_id'))
                                <span class="md-error">{{ $bag->first('location_id') }}</span>
                            @endif
                            <small class="md-sub">Để trống được, sau này vào phiếu nhập điều chỉnh lại định
                                khu.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Nhận đủ số lượng, bao bì nguyên vẹn">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Lô sẽ được cấp mã mới dạng <b>&lt;mã gốc&gt;-CK&lt;số thứ tự&gt;</b> để phân biệt với hàng mua
                        từ ngoài, và <b>không có số hoá đơn</b>. Hoá chất, số lượng, số lô, hạn dùng giữ nguyên theo
                        phòng gửi. Hạn dùng nội bộ để trống - xác định ở màn hình <b>Tồn Kho Hoá Chất</b> trước khi
                        đưa vào sử dụng.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-inbox mr-1"></i> Nhận hàng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#receiveModal').modal('show');
        });
    </script>
@endif
