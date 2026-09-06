@php
    $bag = $errors->getBag('expiryUpdateErrors');

    // Form lỗi validate thì modal mở lại, lấy sẵn dòng cũ để các ô chỉ đọc không bị trống
    $invExpRow = $bag->any() ? $datas->firstWhere('id', (int) old('import_id')) : null;

    $invExpTypeLabel = function ($type) {
        return match ($type) {
            'check online', 'undetermined', 'unlimited' => 'Chưa xác định (Check online)',
            'retest' => 'Cần retest định kỳ',
            'Specify', 'defined' => 'Hạn dùng xác định',
            'Requires_re-evaluation' => 'Cần xác định lại hạn dùng nội bộ',
            default => $type ?: '—',
        };
    };
@endphp

<div class="modal fade md-modal" id="expiryUpdateModal" tabindex="-1" role="dialog"
    data-updates="{{ json_encode($expiryUpdates) }}">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-check"></i> Cập Nhật Hạn Dùng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route('pages.inventory.standardInventory.expiryUpdate') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_id" value="{{ old('import_id') }}">
                {{-- Loại hạn dùng đang có của ống, để JS ẩn/hiện đúng lựa chọn "tiếp tục"; Controller vẫn đọc lại từ DB --}}
                <input type="hidden" name="current_expiry_type"
                    value="{{ old('current_expiry_type', $invExpRow->expiry_type ?? '') }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Mã Ống Chuẩn</label>
                            <input type="text" class="form-control inv-readonly inv-exp-code" readonly
                                value="{{ $invExpRow->code ?? '' }}">
                        </div>

                        <div class="form-group col-md-7">
                            <label>Chất Chuẩn</label>
                            <input type="text" class="form-control inv-readonly inv-exp-chem" readonly
                                value="{{ $invExpRow->standard_name ?? '' }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Loại Hạn Dùng Hiện Tại</label>
                            <input type="text" class="form-control inv-readonly inv-exp-current-type" readonly
                                value="{{ $invExpRow ? $invExpTypeLabel($invExpRow->expiry_type) : '' }}">
                        </div>

                        <div class="form-group col-md-6">
                            <label>Hạn Dùng Hiện Tại</label>
                            <input type="text" class="form-control inv-readonly inv-exp-current-date" readonly
                                value="{{ $invExpRow ? $invDate($invExpRow->expired_date) : '' }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Hướng Xử Lý <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap" style="gap: 18px;">
                            <div class="custom-control custom-radio inv-exp-res-retest"
                                @if ($invExpRow && $invExpRow->expiry_type !== 'retest') style="display:none" @endif>
                                <input type="radio" id="expResRetest" name="resolution" value="retest"
                                    class="custom-control-input inv-exp-res"
                                    {{ old('resolution') === 'retest' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="expResRetest">Tiếp tục Retest định kỳ</label>
                            </div>
                            <div class="custom-control custom-radio inv-exp-res-online"
                                @if ($invExpRow && $invExpRow->expiry_type === 'retest') style="display:none" @endif>
                                <input type="radio" id="expResOnline" name="resolution" value="check_online"
                                    class="custom-control-input inv-exp-res"
                                    {{ old('resolution') === 'check_online' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="expResOnline">Tiếp tục Check online</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="expResDefined" name="resolution" value="defined"
                                    class="custom-control-input inv-exp-res"
                                    {{ old('resolution') === 'defined' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="expResDefined">Hạn dùng đã xác định</label>
                            </div>
                        </div>
                        @if ($bag->has('resolution'))
                            <span class="md-error">{{ $bag->first('resolution') }}</span>
                        @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6 inv-exp-date-wrap">
                            <label class="inv-exp-date-label">Hạn Dùng Mới <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="date" name="expired_date"
                                    class="form-control inv-exp-date {{ $bag->has('expired_date') ? 'is-invalid' : '' }}"
                                    value="{{ old('expired_date') }}">
                                <div class="input-group-append inv-exp-suggest-wrap" style="display: none;">
                                    <button type="button" class="btn btn-outline-info inv-exp-suggest"
                                        title="Gợi ý = hôm nay + chu kỳ retest">
                                        <i class="fas fa-magic"></i> Gợi ý
                                    </button>
                                </div>
                            </div>
                            @if ($bag->has('expired_date'))
                                <span class="md-error">{{ $bag->first('expired_date') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-6 inv-exp-interval-wrap" style="display: none;">
                            <label>Chu Kỳ Retest (tháng)</label>
                            <input type="number" name="retest_interval_months" min="1" max="120" step="1"
                                class="form-control {{ $bag->has('retest_interval_months') ? 'is-invalid' : '' }}"
                                value="{{ old('retest_interval_months') }}" placeholder="Ví dụ: 12">
                            @if ($bag->has('retest_interval_months'))
                                <span class="md-error">{{ $bag->first('retest_interval_months') }}</span>
                            @endif
                            <small class="md-sub">Để trống thì giữ nguyên chu kỳ đang khai của ống.</small>
                        </div>
                    </div>

                    <div class="inv-exp-hint md-sub mb-2"></div>

                    {{-- Kết quả kiểm nghiệm lại - chỉ hiện khi tiếp tục Retest --}}
                    <div class="form-row inv-exp-retest-fields" style="display: none;">
                        <div class="form-group col-md-4">
                            <label>Hàm Lượng</label>
                            <input type="text" name="potency" maxlength="100"
                                class="form-control {{ $bag->has('potency') ? 'is-invalid' : '' }}"
                                value="{{ old('potency', $invExpRow->potency ?? '') }}"
                                placeholder="Ví dụ: 99.5%, 1000 µg/mL">
                            @if ($bag->has('potency'))
                                <span class="md-error">{{ $bag->first('potency') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Độ Ẩm (%)</label>
                            <input type="text" name="moisture" maxlength="100"
                                class="form-control {{ $bag->has('moisture') ? 'is-invalid' : '' }}"
                                value="{{ old('moisture', $invExpRow->moisture ?? '') }}"
                                placeholder="Ví dụ: 0.3%">
                            @if ($bag->has('moisture'))
                                <span class="md-error">{{ $bag->first('moisture') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Số Phiếu Kiểm Nghiệm (CoA)</label>
                            <input type="text" name="coa_no" maxlength="100"
                                class="form-control {{ $bag->has('coa_no') ? 'is-invalid' : '' }}"
                                value="{{ old('coa_no', $invExpRow->coa_no ?? '') }}">
                            @if ($bag->has('coa_no'))
                                <span class="md-error">{{ $bag->first('coa_no') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lý Do / Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: đã tra cứu trên trang NSX ngày ..., kết quả còn hạn đến ...">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Đính Kèm File (CoA, ảnh tra cứu...)</label>
                        <input type="file" name="attachments[]" multiple
                            class="form-control-file border p-1 rounded w-100">
                        <small class="md-sub">Không bắt buộc. Đính kèm nhiều file, tối đa 10MB/file.</small>
                    </div>

                    <hr>

                    <label class="font-weight-bold"><i class="fas fa-clock-rotate-left mr-1"></i> Lịch Sử Cập Nhật Hạn Dùng</label>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover w-100 inv-expiry-hist-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 130px">Thời Điểm</th>
                                    <th style="width: 140px">Người Cập Nhật</th>
                                    <th>Thay Đổi</th>
                                    <th style="width: 170px">Ghi Chú</th>
                                    <th class="text-center" style="width: 150px">File</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#expiryUpdateModal').modal('show');
        });
    </script>
@endif
