{{--
| SỬ DỤNG - GIAO NHẬN PHẾ PHẨM VÀ THEO DÕI HUỶ
|
| Ghi mục 3 và mục 4 của biểu mẫu QA/F/058-07, chỉ mở được sau khi đợt đã có quyết
| định huỷ. Điền đủ "Ngày tiến hành huỷ" và "Người xác nhận" thì đợt chuyển sang
| trạng thái Đã huỷ xong.
--}}

@php $dspDoneBag = $errors->getBag('completeErrors'); @endphp

<div class="modal fade md-modal" id="disposalCompleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-truck-ramp-box"></i> Giao Nhận &amp; Theo Dõi Huỷ
                    <span class="dsp-done-code"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($dspRoute . 'complete') }}" method="POST">
                @csrf
                <input type="hidden" name="id">

                <div class="modal-body">

                    <h6 class="dsp-section">3. Giao nhận phế phẩm</h6>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tổng Khối Lượng Phế Phẩm Rắn (kg)</label>
                            <input type="number" name="solid_weight" step="0.0001" min="0"
                                class="form-control {{ $dspDoneBag->has('solid_weight') ? 'is-invalid' : '' }}"
                                value="{{ old('solid_weight') }}">
                            @if ($dspDoneBag->has('solid_weight'))
                                <span class="md-error">{{ $dspDoneBag->first('solid_weight') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label>Tổng Khối Lượng Phế Phẩm Lỏng (kg)</label>
                            <input type="number" name="liquid_weight" step="0.0001" min="0"
                                class="form-control {{ $dspDoneBag->has('liquid_weight') ? 'is-invalid' : '' }}"
                                value="{{ old('liquid_weight') }}">
                            @if ($dspDoneBag->has('liquid_weight'))
                                <span class="md-error">{{ $dspDoneBag->first('liquid_weight') }}</span>
                            @endif
                            <small class="md-sub dsp-suggest"></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Ngày Giao</label>
                            <input type="date" name="handover_date" class="form-control">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Người Giao</label>
                            <input type="text" name="handover_by" maxlength="255" class="form-control">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Ngày Nhận</label>
                            <input type="date" name="receive_date" class="form-control">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Người Nhận (Hành Chánh)</label>
                            <input type="text" name="receive_by" maxlength="255" class="form-control">
                        </div>
                    </div>

                    <h6 class="dsp-section">4. ĐBCL kiểm tra và theo dõi huỷ</h6>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Ngày Dán Nhãn</label>
                            <input type="date" name="label_date" class="form-control">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Người Thực Hiện</label>
                            <input type="text" name="label_by" maxlength="255" class="form-control">
                            <small class="md-sub">Kiểm tra, dán nhãn "Chấp nhận huỷ".</small>
                        </div>

                        <div class="form-group col-md-3">
                            <label>Ngày Tiến Hành Huỷ</label>
                            <input type="date" name="destroy_date" class="form-control">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Người Xác Nhận</label>
                            <input type="text" name="destroy_by" maxlength="255"
                                class="form-control {{ $dspDoneBag->has('destroy_by') ? 'is-invalid' : '' }}">
                            @if ($dspDoneBag->has('destroy_by'))
                                <span class="md-error">{{ $dspDoneBag->first('destroy_by') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Điền đủ <b>Ngày tiến hành huỷ</b> và <b>Người xác nhận</b> thì đợt chuyển sang
                        <b>Đã huỷ xong</b>. Các phần còn trống vẫn in ra được để ký tay trên bản giấy, quay lại đây
                        nhập sau lúc nào cũng được.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
