{{--
| Modal ĐIỀU CHỈNH THỜI GIAN ĐẶT HÀNG của danh mục công ty (vật tư / hoá chất / chất chuẩn).
| Mở bằng nút .btn-cat-lead-time ở cột Thời Gian Đặt Hàng, chỉ hiện khi có quyền {permPrefix}lead_time.
| Gửi tới {prefix}updateLeadTime - xem App\Http\Controllers\Concerns\UpdatesCategoryLeadTime.
|
| Biến vào: $mdRoute (tiền tố route của màn hình).
--}}
@php
    $ltBag = $errors->getBag('leadTimeErrors');
@endphp

<div class="modal fade md-modal" id="leadTimeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shipping-fast"></i> Điều Chỉnh Thời Gian Đặt Hàng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'updateLeadTime') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <input type="hidden" name="code" value="{{ old('code') }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã</label>
                        <input type="text" class="form-control cat-readonly lt-code" value="{{ old('code') }}" readonly tabindex="-1">
                    </div>

                    <div class="form-group">
                        <label>Thời Gian Đặt Hàng</label>
                        <div class="input-group">
                            <input type="text" inputmode="numeric" name="lead_time_days"
                                class="form-control {{ $ltBag->has('lead_time_days') ? 'is-invalid' : '' }}"
                                value="{{ old('lead_time_days') }}" placeholder="Ví dụ: 30">
                            <div class="input-group-append">
                                <span class="input-group-text">ngày</span>
                            </div>
                        </div>
                        @if ($ltBag->has('lead_time_days'))
                            <span class="md-error">{{ $ltBag->first('lead_time_days') }}</span>
                        @endif
                        <small class="md-sub">Số ngày từ lúc đặt hàng tới lúc hàng về công ty. Để trống nếu chưa xác định.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label>Lý Do Điều Chỉnh <span class="text-danger">*</span></label>
                        <textarea name="change_reason" rows="2" maxlength="500" required
                            class="form-control {{ $ltBag->has('change_reason') ? 'is-invalid' : '' }}"
                            placeholder="Nêu rõ lý do đổi thời gian đặt hàng">{{ old('change_reason') }}</textarea>
                        @if ($ltBag->has('change_reason'))
                            <span class="md-error">{{ $ltBag->first('change_reason') }}</span>
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Nút nằm trong dòng bảng (dòng được nạp lại sau mỗi lần lưu) nên gắn qua document
        $(document).on('click', '.btn-cat-lead-time', function() {
            var $btn = $(this);
            var $form = $('#leadTimeModal').find('form');

            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.md-error').remove();
            $form.find('[name="id"]').val($btn.data('id'));
            $form.find('[name="code"], .lt-code').val($btn.data('code'));
            $form.find('[name="lead_time_days"]').val($btn.data('days') === '' ? '' : $btn.data('days'));
            $form.find('[name="change_reason"]').val('');

            $('#leadTimeModal').modal('show');
        });

        $('#leadTimeModal').on('shown.bs.modal', function() {
            $(this).find('[name="lead_time_days"]').trigger('focus').select();
        });
    });
</script>

@if ($ltBag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#leadTimeModal').modal('show');
        });
    </script>
@elseif (! empty($leadTimeOpen))
    {{-- Vào từ thẻ trang chủ "Mã mới chờ khai thời gian đặt hàng" (?lead_time=<id>) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('<button type="button" class="btn-cat-lead-time d-none">')
                .attr('data-id', @json($leadTimeOpen->id))
                .attr('data-code', @json($leadTimeOpen->code))
                .attr('data-days', @json($leadTimeOpen->lead_time_days ?? ''))
                .appendTo('body').trigger('click').remove();
        });
    </script>
@endif
