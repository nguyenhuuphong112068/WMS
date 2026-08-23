@php
    $reqBag = $errors->getBag('requestErrors');
    $resBag = $errors->getBag('respondErrors');
@endphp

{{-- ---------- Gửi đề nghị chuyển hoá chất ---------- --}}
<div class="modal fade md-modal" id="requestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Đề Nghị Chuyển Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'requestStore') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="form-group">
                        <label>Phòng Ban Được Đề Nghị <span class="text-danger">*</span></label>
                        <select name="to_department_id"
                            class="form-control exp-select {{ $reqBag->has('to_department_id') ? 'is-invalid' : '' }}"
                            required>
                            <option value="">-- Chọn phòng ban đang có hoá chất --</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}"
                                    {{ old('to_department_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }} ({{ $department->shortName }})
                                </option>
                            @endforeach
                        </select>
                        @if ($reqBag->has('to_department_id'))
                            <span class="md-error">{{ $reqBag->first('to_department_id') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Hoá Chất Cần <span class="text-danger">*</span></label>
                        <select name="category_id"
                            class="form-control exp-select {{ $reqBag->has('category_id') ? 'is-invalid' : '' }}"
                            required>
                            <option value="">-- Chọn hoá chất --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->code }} - {{ $category->chem_name }}{{ $category->unit_short_name ? ' (' . $category->unit_short_name . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if ($reqBag->has('category_id'))
                            <span class="md-error">{{ $reqBag->first('category_id') }}</span>
                        @endif
                        <small class="md-sub">Đề nghị theo danh mục, chọn lô nào để chuyển là quyền của phòng giữ
                            hàng.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Số Lượng Cần <span class="text-danger">*</span></label>
                            <input type="number" name="amount" step="0.0001" min="0.0001"
                                class="form-control {{ $reqBag->has('amount') ? 'is-invalid' : '' }}"
                                value="{{ old('amount') }}" required>
                            @if ($reqBag->has('amount'))
                                <span class="md-error">{{ $reqBag->first('amount') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ngày Cần</label>
                            <input type="date" name="needed_date"
                                class="form-control {{ $reqBag->has('needed_date') ? 'is-invalid' : '' }}"
                                value="{{ old('needed_date') }}">
                            @if ($reqBag->has('needed_date'))
                                <span class="md-error">{{ $reqBag->first('needed_date') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lý Do / Mục Đích</label>
                        <textarea name="reason" rows="2" maxlength="500"
                            class="form-control {{ $reqBag->has('reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Hết hàng, cần gấp cho đợt kiểm nghiệm tuần này">{{ old('reason') }}</textarea>
                        @if ($reqBag->has('reason'))
                            <span class="md-error">{{ $reqBag->first('reason') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đề nghị không trừ cộng tồn kho. Phòng kia đồng ý rồi vẫn phải lập phiếu <b>Chuyển kho</b> thì
                        hàng mới đi.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ---------- Trả lời đề nghị: đồng ý / từ chối ---------- --}}
<div class="modal fade md-modal" id="respondModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-reply"></i>
                    <span class="exp-respond-heading">Trả Lời Đề Nghị</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'requestRespond') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <input type="hidden" name="app_status" value="{{ old('app_status') }}">

                <div class="modal-body">
                    <div class="md-hint mb-3">
                        <i class="fas fa-flask mr-1"></i>
                        <span class="exp-respond-subtitle"></span>
                    </div>

                    <div class="form-group">
                        <label>
                            <span class="exp-respond-label">Ghi Chú Trả Lời</span>
                            <span class="text-danger exp-respond-required" style="display: none">*</span>
                        </label>
                        <textarea name="response_note" rows="3" maxlength="500"
                            class="form-control {{ $resBag->has('response_note') ? 'is-invalid' : '' }}">{{ old('response_note') }}</textarea>
                        @if ($resBag->has('response_note'))
                            <span class="md-error">{{ $resBag->first('response_note') }}</span>
                        @endif
                        <small class="md-sub exp-respond-hint"></small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary exp-respond-submit">
                        <i class="fas fa-save mr-1"></i> Lưu trả lời
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($reqBag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#requestModal').modal('show');
        });
    </script>
@endif

@if ($resBag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#respondModal').modal('show');
        });
    </script>
@endif
