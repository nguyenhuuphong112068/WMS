{{--
| SỬ DỤNG - GHI QUYẾT ĐỊNH HUỶ (mục 2 của biểu mẫu QA/F/058-07)
|
| Một modal dùng cho cả hai câu trả lời, JS đổi tiêu đề và bật/tắt phần cần nhập:
| - Duyệt      : bắt buộc số quyết định + TP. ĐBCL + Ban Giám Đốc -> in được biểu mẫu.
| - Không duyệt: bắt buộc lý do; các phiếu được trả về hàng chờ huỷ để gom đợt khác.
--}}

@php $dspDecideBag = $errors->getBag('decideErrors'); @endphp

<div class="modal fade md-modal" id="disposalDecideModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-stamp"></i> <span class="dsp-decide-heading">Quyết Định Huỷ Bỏ</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($dspRoute . 'decide') }}" method="POST">
                @csrf
                <input type="hidden" name="id">
                <input type="hidden" name="app_status">

                <div class="modal-body">
                    <div class="dsp-picked-box">
                        <i class="fas fa-clipboard-check mr-1"></i>
                        Đợt huỷ <b class="dsp-decide-code"></b> — Trưởng phòng ĐBCL quyết định huỷ bỏ các hoá chất,
                        chất chuẩn theo danh mục đã tổng kết ở mục 1.
                    </div>

                    {{-- ---------- Phần chỉ dùng khi DUYỆT ---------- --}}
                    <div class="dsp-approve-only">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Quyết Định Số <span class="text-danger">*</span></label>
                                <input type="text" name="decision_no" maxlength="100"
                                    class="form-control {{ $dspDecideBag->has('decision_no') ? 'is-invalid' : '' }}"
                                    value="{{ old('decision_no') }}" placeholder="Ví dụ: 12/QĐ-ĐBCL">
                                @if ($dspDecideBag->has('decision_no'))
                                    <span class="md-error">{{ $dspDecideBag->first('decision_no') }}</span>
                                @endif
                            </div>

                            <div class="form-group col-md-6">
                                <label>Thời Gian Dự Kiến Thực Hiện</label>
                                <input type="text" name="planned_time" maxlength="255" class="form-control"
                                    value="{{ old('planned_time') }}" placeholder="Ví dụ: Tuần 2 tháng 09/2026">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Phương Pháp Huỷ</label>
                            <div class="exp-types">
                                @foreach ($disposalMethods as $value => $label)
                                    <label class="exp-type {{ old('method') === $value ? 'is-checked' : '' }}">
                                        <input type="radio" name="method" value="{{ $value }}"
                                            {{ old('method') === $value ? 'checked' : '' }}>
                                        <span>{{ $loop->iteration }}. {{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <small class="md-sub">Khoanh tròn trên biểu mẫu in ra. Để trống nếu quyết định sau.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-5">
                                <label>Thực Hiện Huỷ</label>
                                <select name="executor_type" class="form-control">
                                    <option value="">-- Chưa xác định --</option>
                                    @foreach ($disposalExecutors as $value => $label)
                                        <option value="{{ $value }}" {{ old('executor_type') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-7 dsp-executor-other" style="display: none">
                                <label>Tên Đơn Vị Thực Hiện</label>
                                <input type="text" name="executor_other" maxlength="255" class="form-control"
                                    value="{{ old('executor_other') }}" placeholder="Ghi tên đơn vị huỷ">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>TP. ĐBCL Quyết Định <span class="text-danger">*</span></label>
                                <input type="text" name="qa_approved_by" maxlength="255"
                                    class="form-control {{ $dspDecideBag->has('qa_approved_by') ? 'is-invalid' : '' }}"
                                    value="{{ old('qa_approved_by') }}" placeholder="Họ tên Trưởng phòng ĐBCL">
                                @if ($dspDecideBag->has('qa_approved_by'))
                                    <span class="md-error">{{ $dspDecideBag->first('qa_approved_by') }}</span>
                                @endif
                            </div>

                            <div class="form-group col-md-6">
                                <label>Ngày Quyết Định <span class="text-danger">*</span></label>
                                <input type="date" name="qa_approved_at"
                                    class="form-control {{ $dspDecideBag->has('qa_approved_at') ? 'is-invalid' : '' }}"
                                    value="{{ old('qa_approved_at', now()->format('Y-m-d')) }}">
                                @if ($dspDecideBag->has('qa_approved_at'))
                                    <span class="md-error">{{ $dspDecideBag->first('qa_approved_at') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Ban Giám Đốc Duyệt <span class="text-danger">*</span></label>
                                <input type="text" name="director_approved_by" maxlength="255"
                                    class="form-control {{ $dspDecideBag->has('director_approved_by') ? 'is-invalid' : '' }}"
                                    value="{{ old('director_approved_by') }}" placeholder="Họ tên người duyệt">
                                @if ($dspDecideBag->has('director_approved_by'))
                                    <span class="md-error">{{ $dspDecideBag->first('director_approved_by') }}</span>
                                @endif
                            </div>

                            <div class="form-group col-md-6">
                                <label>Ngày Duyệt <span class="text-danger">*</span></label>
                                <input type="date" name="director_approved_at"
                                    class="form-control {{ $dspDecideBag->has('director_approved_at') ? 'is-invalid' : '' }}"
                                    value="{{ old('director_approved_at', now()->format('Y-m-d')) }}">
                                @if ($dspDecideBag->has('director_approved_at'))
                                    <span class="md-error">{{ $dspDecideBag->first('director_approved_at') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="dsp-note-label">Ghi Chú Khác</label>
                        <textarea name="other_note" rows="2" maxlength="500" class="form-control"
                            placeholder="Ghi chú in vào dòng &quot;Ghi chú khác&quot; của biểu mẫu">{{ old('other_note') }}</textarea>
                    </div>

                    {{-- ---------- Phần chỉ dùng khi KHÔNG DUYỆT ---------- --}}
                    <div class="form-group dsp-reject-only" style="display: none">
                        <label>Lý Do Không Duyệt <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" rows="2" maxlength="500"
                            class="form-control {{ $dspDecideBag->has('reject_reason') ? 'is-invalid' : '' }}"
                            placeholder="Nêu rõ vì sao chưa huỷ được để phòng ban biết đường xử lý">{{ old('reject_reason') }}</textarea>
                        @if ($dspDecideBag->has('reject_reason'))
                            <span class="md-error">{{ $dspDecideBag->first('reject_reason') }}</span>
                        @endif
                        <small class="md-sub">Các phiếu trong đợt sẽ được trả về hàng chờ huỷ để gom lại đợt
                            khác.</small>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Có quyết định huỷ rồi thì bấm <b>In phiếu</b> để in
                        <b>{{ \App\Http\Controllers\Pages\Export\ChemicalDisposalController::FORM['form_no'] }} -
                            Phiếu Theo Dõi Và Quyết Định Huỷ</b>, sau đó ghi tiếp mục 3 (giao nhận phế phẩm) và mục 4
                        (ĐBCL theo dõi huỷ) ở nút <b>Giao nhận &amp; theo dõi huỷ</b>.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary dsp-decide-submit">
                        <i class="fas fa-save mr-1"></i> Lưu quyết định
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
