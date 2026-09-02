@php
    $bag = $errors->getBag('assessErrors');

    // Form lỗi validate thì modal mở lại, lấy sẵn mốc cũ để các ô chỉ đọc không bị trống
    $ssaAssessItem = $bag->any() ? $items->firstWhere('id', (int) old('id')) : null;

    // Số mốc chưa thực hiện còn lại của phiếu, không kể mốc đang ghi kết quả
    $ssaAssessRemaining = $ssaAssessItem
        ? $items->where('status', 'Ban Đầu')->where('id', '!=', $ssaAssessItem->id)->count()
        : 0;
@endphp

<div class="modal fade md-modal" id="assessModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check"></i> Ghi Kết Quả Đánh Giá</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($ssaRoute . 'assess') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Mốc Đánh Giá</label>
                            <input type="text" class="form-control ssa-readonly ssa-as-name" readonly
                                value="{{ $ssaAssessItem ? $ssaAssessItem->name . ' (T' . $ssaAssessItem->timepoint . ')' : '' }}">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Ngày Đến Hạn</label>
                            <input type="text" class="form-control ssa-readonly ssa-as-due" readonly
                                value="{{ $ssaAssessItem && $ssaAssessItem->due_date ? \Carbon\Carbon::parse($ssaAssessItem->due_date)->format('d/m/Y') : '' }}">
                        </div>

                        <div class="form-group col-md-4">
                            <label>Ngày Thực Hiện <span class="text-danger">*</span></label>
                            <input type="date" name="done_at"
                                class="form-control {{ $bag->has('done_at') ? 'is-invalid' : '' }}"
                                value="{{ old('done_at', now()->format('Y-m-d')) }}" required>
                            @if ($bag->has('done_at'))
                                <span class="md-error">{{ $bag->first('done_at') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Chỉ Tiêu Kiểm Của Mốc Này</label>
                        <input type="text" class="form-control ssa-readonly ssa-as-testings" readonly
                            value="{{ $ssaAssessItem ? implode(', ', $ssaAssessItem->testing_names) : '' }}">
                    </div>

                    <div class="form-group">
                        <label>Kết Luận <span class="text-danger">*</span></label>
                        <select name="status" class="form-control {{ $bag->has('status') ? 'is-invalid' : '' }}"
                            required>
                            <option value="">-- Chọn kết luận --</option>
                            @foreach ($itemResults as $result)
                                <option value="{{ $result }}" {{ old('status') === $result ? 'selected' : '' }}>
                                    {{ $result }}
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('status'))
                            <span class="md-error">{{ $bag->first('status') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Kết Quả Đánh Giá <span class="text-danger">*</span></label>
                        <textarea name="result" rows="3" maxlength="255"
                            class="form-control {{ $bag->has('result') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Hàm lượng 99.2%, đạt tiêu chuẩn; cảm quan không đổi">{{ old('result') }}</textarea>
                        @if ($bag->has('result'))
                            <span class="md-error">{{ $bag->first('result') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="255"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    {{--
                    | SAU MỐC NÀY LÀM GÌ TIẾP - chỉ hỏi khi phiếu còn mốc chưa thực hiện.
                    |
                    | Không Đạt : chất chuẩn đã hỏng, phiếu dừng luôn, không hỏi gì thêm.
                    | Đạt       : chọn đánh giá tiếp hoặc ngưng, ngưng thì phải nêu lý do.
                    |
                    | Khối này do JS bật / tắt theo kết luận đang chọn và theo số mốc còn lại
                    | của đúng mốc vừa bấm (data-row.remaining).
                    --}}
                    <div class="ssa-after ssa-after-fail" style="display: none">
                        <i class="fas fa-triangle-exclamation"></i>
                        <div>
                            Kết luận <b>Không Đạt</b> nghĩa là chất chuẩn không còn dùng được. Lưu xong,
                            phiếu chuyển sang <b>Dừng Đánh Giá</b> và
                            <b><span class="ssa-as-remaining">các</span> mốc còn lại sẽ không thực hiện</b>.
                            Cần đánh giá tiếp thì mở lại phiếu bằng nút <b>Đánh giá tiếp</b>.
                        </div>
                    </div>

                    <div class="ssa-after ssa-after-pass" style="display: none">
                        <div class="ssa-after-title">
                            <i class="fas fa-circle-question"></i>
                            Mốc này <b>Đạt</b>, phiếu còn <b class="ssa-as-remaining">0</b> mốc chưa thực hiện -
                            làm tiếp hay ngưng?
                        </div>

                        <div class="ssa-after-choice">
                            <label class="ssa-radio">
                                <input type="radio" name="after_pass" value="{{ $afterContinue }}"
                                    {{ old('after_pass', $afterContinue) === $afterContinue ? 'checked' : '' }}>
                                <span><b>Đánh giá tiếp</b> các mốc còn lại theo kế hoạch</span>
                            </label>

                            <label class="ssa-radio">
                                <input type="radio" name="after_pass" value="{{ $afterStop }}"
                                    {{ old('after_pass') === $afterStop ? 'checked' : '' }}>
                                <span><b>Ngưng đánh giá</b> tại đây vì một lý do khác</span>
                            </label>
                        </div>

                        @if ($bag->has('after_pass'))
                            <span class="md-error">{{ $bag->first('after_pass') }}</span>
                        @endif

                        <div class="form-group mb-0 mt-2 ssa-stop-reason"
                            style="display: {{ old('after_pass') === $afterStop ? 'block' : 'none' }}">
                            <label>Lý Do Ngưng Đánh Giá <span class="text-danger">*</span></label>
                            <textarea name="stop_reason" rows="2" maxlength="255"
                                class="form-control {{ $bag->has('stop_reason') ? 'is-invalid' : '' }}"
                                placeholder="Ví dụ: đã dùng hết ống chuẩn; chuyển sang lô chuẩn mới; ngừng sản xuất sản phẩm dùng chuẩn này">{{ old('stop_reason') }}</textarea>
                            @if ($bag->has('stop_reason'))
                                <span class="md-error">{{ $bag->first('stop_reason') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ghi kết quả xong, trạng thái phiếu tự chuyển: còn mốc chưa làm là
                        <b>Đang Đánh Giá</b>, xong hết các mốc là <b>Hoàn Thành</b>. Ghi lại lần nữa sẽ đè lên kết
                        quả cũ, mọi lần ghi đều lưu trong <b>Lịch sử thay đổi</b> của phiếu và Audit Trail.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Lưu kết quả
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /* Mở modal ghi kết quả: đổ dữ liệu của đúng mốc vừa bấm */
        $(document).on('click', '.btn-ssa-assess', function() {
            var row = $(this).data('row') || {};
            var $form = $('#assessModal').find('form');

            $form.find('.md-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');

            $form.find('[name="id"]').val(row.id);
            $form.find('[name="done_at"]').val(row.done_at || '');
            $form.find('[name="result"]').val(row.result || '');
            // Mốc chưa đánh giá đang mang trạng thái "Ban Đầu", không phải một kết luận
            $form.find('[name="status"]').val(row.status === 'Đạt' || row.status === 'Không Đạt' ? row.status : '');
            $form.find('[name="note"]').val(row.note || '');

            $form.find('.ssa-as-name').val((row.name || '') + ' (T' + row.timepoint + ')');
            $form.find('.ssa-as-due').val(row.due_date || '');
            $form.find('.ssa-as-testings').val(row.testings || 'Chưa chọn chỉ tiêu');

            /*
            | Số mốc CÒN LẠI chưa thực hiện của phiếu (không kể mốc đang ghi). Bằng 0 thì
            | đây là mốc cuối, không còn gì để ngưng nên không hỏi tiếp hay ngưng.
            */
            $form.data('remaining', parseInt(row.remaining, 10) || 0);
            $form.find('.ssa-as-remaining').text(row.remaining || 0);

            // Mặc định quay về "Đánh giá tiếp" mỗi lần mở, tránh giữ lựa chọn của mốc trước
            $form.find('[name="after_pass"][value="{{ $afterContinue }}"]').prop('checked', true);
            $form.find('[name="stop_reason"]').val('');

            $form.find('[name="status"]').trigger('change');

            $('#assessModal').modal('show');
        });

        /* Đổi kết luận thì hiện đúng khối cần hỏi: Không Đạt cảnh báo, Đạt hỏi tiếp hay ngưng */
        $(document).on('change', '#assessModal [name="status"]', function() {
            var $form = $(this).closest('form');
            var status = $(this).val();
            var remaining = $form.data('remaining') || 0;

            $form.find('.ssa-after-fail').toggle(status === '{{ $itemFailed }}' && remaining > 0);
            $form.find('.ssa-after-pass').toggle(status === '{{ $itemPassed }}' && remaining > 0);
        });

        /* Chọn "Ngưng đánh giá" thì mở ô lý do - lý do là bắt buộc, Controller chặn lại lần nữa */
        $(document).on('change', '#assessModal [name="after_pass"]', function() {
            var $form = $(this).closest('form');
            var stop = $form.find('[name="after_pass"]:checked').val() === '{{ $afterStop }}';

            $form.find('.ssa-stop-reason').toggle(stop);

            if (stop) $form.find('[name="stop_reason"]').focus();
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mở lại sau lỗi validate: dựng lại số mốc còn lại rồi hiện đúng khối đang cần sửa
            var $form = $('#assessModal').find('form');

            $form.data('remaining', {{ $ssaAssessRemaining }});
            $form.find('.ssa-as-remaining').text('{{ $ssaAssessRemaining }}');
            $form.find('[name="status"]').trigger('change');
            $form.find('[name="after_pass"]').trigger('change');

            $('#assessModal').modal('show');
        });
    </script>
@endif
