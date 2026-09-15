{{--
| CSS + JS của 2 tab "Danh sách vật tư đề nghị ... theo chu kỳ".
| Bọc trong @once nên include ở cả hai tab vẫn chỉ in ra một bản.
--}}
@once
<style>
    /* ---------- Cột "Đề Nghị Đã Tạo": bộ đếm số lần ---------- */
    .pr-count {
        display: inline-flex;
        align-items: baseline;
        gap: 4px;
        color: var(--primary-dark);
    }

    .pr-count b {
        font-size: 1.15rem;
        color: var(--primary);
    }

    /* ---------- Modal: rộng 80% màn hình, nằm sát cạnh trên ---------- */
    .pr-modal .pr-dialog {
        max-width: 80vw;
        margin-top: 20px;
    }

    @media (max-width: 767.98px) {
        .pr-modal .pr-dialog {
            max-width: none;
        }
    }

    /* ---------- Bảng nhập dòng vật tư trong modal ---------- */
    .pr-rows-wrap {
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-md);
        transition: border-color var(--transition-fast, 0.2s ease);
    }

    .pr-rows-wrap.is-invalid {
        border-color: #DC2626;
    }

    .pr-rows-table thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        vertical-align: middle;
        border-color: var(--primary-soft);
    }

    .pr-rows-table td {
        vertical-align: middle;
        border-color: var(--primary-soft);
    }

    /*
    | Cố định bề rộng cột theo <colgroup>: để trình duyệt tự chia thì tên vật tư dài trong
    | Select2 (không xuống dòng) nong cột Vật Tư ra và ép hẹp làm che mất chữ ở cột Đơn Vị.
    | Màn hình hẹp thì bảng cuộn ngang thay vì bóp cột.
    */
    .pr-rows-table {
        table-layout: fixed;
        min-width: 1300px;
    }

    .pr-rows-table .pr-spec,
    .pr-rows-table .pr-spec:focus {
        background: var(--primary-soft);
        border-color: var(--primary-soft);
        color: var(--primary-dark);
        box-shadow: none;
        cursor: default;
        text-overflow: ellipsis;
    }

    .pr-rows-table .select2-container {
        max-width: 100%;
    }

    .pr-rows-table .select2-selection__rendered {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /*
    | Chiều cao ô nhập tự co theo nội dung. CSS chung .md-modal .form-control đặt padding 9px
    | nhưng Bootstrap vẫn giữ height cố định (38px, bản -sm 31px) nên chữ trong ô chọn bị cắt
    | mất đầu / chân (Đơn vị, Chu kỳ, Ngày trong chu kỳ). Trả height về auto để ô cao theo chữ.
    */
    .pr-modal .form-control:not(textarea) {
        height: auto;
        line-height: 1.5;
    }

    .pr-modal .pr-rows-table .form-control-sm {
        min-height: 34px;
        padding: 5px 8px;
        font-size: 0.875rem;
    }

    .pr-modal .pr-rows-table select.pr-unit {
        min-width: 0;
        padding-right: 22px;
    }

    .pr-modal .pr-rows-table .select2-container--bootstrap4 .select2-selection {
        min-height: 34px;
    }

    /*
    | ---------- Hàng Tần suất / Ngày tạo / Ngày bắt đầu / Lần tạo kế tiếp ----------
    | Nhãn nhỏ, in hoa, màu phụ - gọn và đồng bộ giữa các ô thay vì nhãn to như label mặc định.
    */
    .pr-cycle-row {
        margin-bottom: 0.25rem;
    }

    .pr-cycle-row .form-group {
        margin-bottom: 0;
    }

    .pr-cycle-row > div > label {
        margin-bottom: 4px;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        color: #64748b;
    }

    .pr-cycle-row .form-control {
        font-size: 0.86rem;
    }

    .pr-cycle-row .input-group-text {
        font-size: 0.82rem;
        padding: 6px 10px;
    }

    /*
    | "Lần Tạo Kế Tiếp" không có col-md cố định - tự giãn lấp phần còn lại của hàng (Bootstrap
    | .form-row vốn là flex), kể cả khi cột "Ngày Cụ Thể" bên cạnh bị ẩn ở chế độ CAL.
    */
    .pr-next-col {
        flex: 1 1 180px;
        min-width: 180px;
        padding-right: 5px;
        padding-left: 5px;
    }

    /* Ô xem trước lần tạo đề nghị kế tiếp - cùng chiều cao với ô input/select cạnh bên */
    .pr-next-box {
        display: flex;
        align-items: center;
        min-height: 36px;
        padding: 6px 12px;
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
        border: 1px solid var(--primary-lighter);
    }

    .pr-next-box b {
        color: var(--primary-dark);
        font-size: 0.86rem;
        font-weight: 700;
    }

    /*
    | Ô chọn Đối Tượng + nút mở dữ liệu gốc chung 1 hàng dạng input-group. Select2 tự bọc ô
    | <select> gốc trong 1 span nên input-group CSS mặc định (bo góc, flex) không nhận diện
    | được - phải tự ép flex + bỏ bo góc phải để dính liền với nút.
    */
    .pr-object-group .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
    }

    .pr-object-group .select2-container--bootstrap4 .select2-selection {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    /* ---------- Vị trí + tần suất của đối tượng đang chọn ---------- */
    .pr-object-info {
        display: block;
        margin-top: 4px;
        font-size: 0.78rem;
        color: #64748b;
    }

    .pr-object-info b {
        color: var(--primary-dark);
        font-weight: 600;
    }

    /* ---------- Hạn lịch CAL đang theo (ngày tạo "Theo ngày đến hạn lịch CAL") ---------- */
    .pr-cal-info {
        display: block;
        margin-top: 4px;
        font-size: 0.78rem;
        color: #64748b;
    }

    .pr-cal-info b {
        color: var(--primary-dark);
        font-weight: 600;
    }

    .pr-cal-info.is-warning {
        color: #b45309;
    }

    /* ---------- Bảng chọn nhiều vật tư: nổi trên modal thêm / sửa ---------- */
    .pr-picker {
        z-index: 1060;
    }

    .pr-picker .pr-picker-dialog {
        max-width: 72vw;
        margin-top: 40px;
    }

    @media (max-width: 767.98px) {
        .pr-picker .pr-picker-dialog {
            max-width: none;
        }
    }

    .pr-picker-search-group {
        flex: 1 1 320px;
        max-width: 560px;
    }

    .pr-picker-wrap {
        max-height: 58vh;
        overflow-y: auto;
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-md);
    }

    .pr-picker-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        vertical-align: middle;
        border-color: var(--primary-soft);
    }

    .pr-picker-table td {
        vertical-align: middle;
        border-color: var(--primary-soft);
    }

    .pr-picker-row {
        cursor: pointer;
        transition: background-color var(--transition-fast, 0.2s ease);
    }

    .pr-picker-row.is-checked {
        background: var(--primary-soft);
    }

    .pr-picker-row.is-added {
        cursor: default;
        opacity: 0.6;
    }

    .pr-picker-added {
        display: none;
        margin-left: 6px;
        font-weight: 600;
    }

    .pr-picker-row.is-added .pr-picker-added {
        display: inline-block;
    }

    .pr-picker-check,
    .pr-picker-all,
    .pr-object-picker-check {
        width: 16px;
        height: 16px;
        accent-color: var(--primary);
        cursor: pointer;
    }

    .pr-picker-selected {
        color: var(--primary-dark);
    }

    .pr-picker-selected b {
        color: var(--primary);
    }
</style>

{{--
| Modal xem đầy đủ vật tư đề nghị của 1 danh sách - dùng chung cho cả 2 tab (nội bộ / liên phòng
| ban), JS đổ nội dung từ data-items của nút ".btn-pr-items" (đã có sẵn trong HTML, không cần AJAX).
--}}
<div class="modal fade md-modal pr-modal" id="periodicItemsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog pr-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list-ul"></i> Vật Tư Đề Nghị
                    <small class="pr-items-modal-subtitle md-sub ml-2"></small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                {{-- Cùng bố cục bảng với modal Thêm / Sửa - chỉ để xem, không có ô nhập --}}
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 pr-items-table">
                        <thead>
                            <tr>
                                <th>Vật Tư</th>
                                <th>Thông Tin Kỹ Thuật</th>
                                <th class="text-right" style="width: 120px">Số Lượng</th>
                                <th style="width: 110px">Đơn Vị</th>
                                <th>Mục Đích Sử Dụng</th>
                            </tr>
                        </thead>
                        <tbody class="pr-items-modal-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var prRowIndex = 0;

        var PR_WEEKDAYS = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
        var PR_MAX_DAY = @json(\App\Support\MaterialPeriodicRequest::CYCLE_MAX_DAY);
        var PR_LENGTH_LIMITS = @json(\App\Support\MaterialPeriodicRequest::CYCLE_LENGTH_LIMITS);
        /** Tần suất của đối tượng => {label, periodic, length} */
        var PR_FREQUENCIES = @json(\App\Support\MaterialPeriodicRequest::frequencyScheduleMap());
        /** Chu kỳ dài nhập số ngày thứ mấy thay vì ô chọn */
        var PR_INPUT_CYCLES = ['days', 'half_year', 'year'];

        /** Modal "Dữ Liệu Gốc - Đối Tượng": tìm qua AJAX thay vì nhúng cả danh mục vào trang */
        var PR_OBJECT_SEARCH_URL = '{{ route('pages.category.periodicRequest.objects') }}';
        /** Đối tượng đã tải qua AJAX, theo id - dùng dựng lại <option> khi bấm "Chọn đối tượng" */
        var PR_OBJECT_CACHE = {};
        /** Lịch Pending CAL của đối tượng + ngày tạo sẽ theo, cho tuỳ chọn "Theo ngày đến hạn lịch CAL" */
        var PR_CAL_SCHEDULE_URL = '{{ route('pages.category.periodicRequest.calSchedule') }}';
        /** Mặc định số ngày tạo đề nghị trước hạn CAL khi chưa từng khai (danh sách mới) */
        var PR_CAL_LEAD_DAYS_DEFAULT = {{ \App\Support\MaterialPeriodicRequest::CAL_LEAD_DAYS_DEFAULT }};

        /*
        | ---------- Modal "Chi Tiết Vật Tư Đề Nghị" ----------
        | Dữ liệu đã có sẵn trong data-items của nút bấm (đổ ra cùng lúc với bảng) nên chỉ cần đọc
        | lại và dựng HTML, không cần gọi thêm AJAX.
        */
        $(document).on('click', '.btn-pr-items', function() {
            var items = $(this).data('items') || [];
            var $body = $('#periodicItemsModal .pr-items-modal-body').empty();

            $('#periodicItemsModal .pr-items-modal-subtitle').text($(this).data('title') || '');

            if (!items.length) {
                $body.html('<tr><td colspan="5" class="text-center md-empty py-4">Danh sách chưa có vật tư nào.</td></tr>');
            }

            items.forEach(function(item) {
                var $row = $('<tr>');

                $row.append($('<td>').append(
                    $('<span>').addClass('md-tag mr-1').text(item.category_code || '—'),
                    document.createTextNode(item.material_name || '—')
                ));
                $row.append($('<td>').addClass('md-sub').text(item.technical_specification || '—'));
                // requested_amount đã được server làm gọn (bỏ số 0 thừa) từ trước, dùng luôn
                $row.append($('<td>').addClass('text-right font-weight-bold').text(item.requested_amount || '0'));
                $row.append($('<td>').text(item.requested_unit || '—'));
                $row.append($('<td>').addClass('md-sub').text(item.purpose || '—'));

                $body.append($row);
            });

            $('#periodicItemsModal').modal('show');
        });

        /** 10.5000 -> "10.5"; trống giữ trống */
        function prTrimNumber(value) {
            if (value === null || value === undefined || value === '') return '';
            var number = parseFloat(value);
            return isNaN(number) ? String(value) : String(number);
        }

        function prPad(value) {
            return String(value).padStart(2, '0');
        }

        function prIsoDate(date) {
            return date.getFullYear() + '-' + prPad(date.getMonth() + 1) + '-' + prPad(date.getDate());
        }

        function prHasLength(periodic) {
            return Object.prototype.hasOwnProperty.call(PR_LENGTH_LIMITS, periodic);
        }

        /* ---------- Tính ngày tạo kế tiếp - cùng thuật toán với MaterialPeriodicRequest::nextRunDate() ---------- */
        function prAddDays(date, days) {
            return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
        }

        function prParseDate(value) {
            var parts = String(value || '').split('-');
            return parts.length === 3 ? new Date(+parts[0], +parts[1] - 1, +parts[2]) : null;
        }

        function prPeriodStart(periodic, date, start, length) {
            var y = date.getFullYear();
            var m = date.getMonth();

            if (periodic === 'week') return prAddDays(date, -((date.getDay() + 6) % 7));
            if (periodic === 'bi_month') return new Date(y, Math.floor(m / 2) * 2, 1);
            if (periodic === 'quarter') return new Date(y, Math.floor(m / 3) * 3, 1);
            if (periodic === 'half_year') return new Date(y, m < 6 ? 0 : 6, 1);
            if (periodic === 'year') {
                return new Date(start.getFullYear() + Math.floor((y - start.getFullYear()) / length) * length, 0, 1);
            }
            if (periodic === 'days') {
                var diff = Math.round((date - start) / 86400000);
                return prAddDays(start, Math.floor(diff / length) * length);
            }
            return new Date(y, m, 1);
        }

        function prPeriodEnd(periodic, periodStart, length) {
            var y = periodStart.getFullYear();
            var m = periodStart.getMonth();

            if (periodic === 'week') return prAddDays(periodStart, 6);
            if (periodic === 'bi_month') return new Date(y, m + 2, 0);
            if (periodic === 'quarter') return new Date(y, m + 3, 0);
            if (periodic === 'half_year') return new Date(y, m + 6, 0);
            if (periodic === 'year') return new Date(y + length, 0, 0);
            if (periodic === 'days') return prAddDays(periodStart, length - 1);
            return new Date(y, m + 1, 0);
        }

        /** Lúc vừa khai / đổi lịch: tính cả hôm nay (hoặc chính ngày bắt đầu) */
        function prNextRunDate(periodic, cycleDay, length, startValue) {
            var start = prParseDate(startValue);
            if (!start || !periodic || !cycleDay || (prHasLength(periodic) && !length)) return null;

            var now = new Date();
            var from = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            if (from < start) from = start;

            var threshold = prAddDays(from, -1);
            var periodStart = prPeriodStart(periodic, from, start, length);

            for (var i = 0; i < 24; i++) {
                var end = prPeriodEnd(periodic, periodStart, length);
                // Chu kỳ nhiều năm: ngày thứ mấy tính trong năm đầu kỳ
                var dayEnd = periodic === 'year' ? new Date(periodStart.getFullYear(), 11, 31) : end;
                var candidate = prAddDays(periodStart, cycleDay - 1);

                if (candidate > dayEnd) candidate = dayEnd;
                if (candidate > threshold) return candidate;

                periodStart = prAddDays(end, 1);
            }

            return null;
        }

        function prSchedule($form) {
            var periodic = $form.find('.pr-periodic').val() || '';
            var useInput = PR_INPUT_CYCLES.indexOf(periodic) !== -1;

            return {
                periodic: periodic,
                cycle_length: prHasLength(periodic) ? (parseInt($form.find('.pr-cycle-length').val(), 10) || 0) : null,
                cycle_day: parseInt((useInput ? $form.find('.pr-cycle-day-input') : $form.find('.pr-cycle-day-select')).val(), 10) || 0,
                start_date: $form.find('.pr-start-date').val()
            };
        }

        function prMaxDay(schedule) {
            return schedule.periodic === 'days' ? schedule.cycle_length : (PR_MAX_DAY[schedule.periodic] || 0);
        }

        /** Đang chọn "Theo ngày đến hạn lịch CAL" (chỉ modal nội bộ có ô .pr-day-mode) */
        function prIsCalMode($form) {
            return $form.find('.pr-day-mode').val() === 'cal_due';
        }

        function prPreview($form) {
            var schedule = prSchedule($form);
            var origin = $form.data('origin');
            var date = null;

            // Theo hạn lịch CAL: ngày tạo do server tính từ Sch_DueDate (prLoadCal lưu vào data('cal'))
            if (prIsCalMode($form)) {
                var cal = $form.data('cal') || {};
                date = cal.next ? prParseDate(cal.next.run_date) : null;

                $form.find('.pr-next-preview').text(date
                    ? PR_WEEKDAYS[(date.getDay() + 6) % 7] + ', ' + prPad(date.getDate()) + '/' + prPad(date.getMonth() + 1) + '/' + date.getFullYear()
                    : 'Chờ lịch CAL');
                return;
            }

            // Sửa mà chưa đổi lịch thì server giữ nguyên ngày kế tiếp đang có
            if (origin && origin.next_run_date && origin.cycle_day_mode !== 'cal_due' && origin.periodic === schedule.periodic
                && parseInt(origin.cycle_day, 10) === schedule.cycle_day
                && (origin.cycle_length ? parseInt(origin.cycle_length, 10) : null) === schedule.cycle_length
                && origin.start_date === schedule.start_date) {
                date = prParseDate(origin.next_run_date);
            } else if (schedule.cycle_day >= 1 && schedule.cycle_day <= prMaxDay(schedule)) {
                date = prNextRunDate(schedule.periodic, schedule.cycle_day, schedule.cycle_length, schedule.start_date);
            }

            $form.find('.pr-next-preview').text(date
                ? PR_WEEKDAYS[(date.getDay() + 6) % 7] + ', ' + prPad(date.getDate()) + '/' + prPad(date.getMonth() + 1) + '/' + date.getFullYear()
                : '—');
        }

        /**
         * Hiện đúng ô theo chu kỳ đang chọn, giữ ngày cũ nếu còn hợp lệ.
         * Tuần / tháng / 2 tháng / quý: ô chọn ngày. Theo số ngày / nửa năm / theo năm: ô nhập số.
         * Theo số ngày / theo năm (liên phòng ban) hiện thêm ô độ dài chu kỳ.
         */
        function prApplyCycle($form, keepDay) {
            var periodic = $form.find('.pr-periodic').val() || '';
            var useInput = PR_INPUT_CYCLES.indexOf(periodic) !== -1;
            var hasLength = prHasLength(periodic);
            var $select = $form.find('.pr-cycle-day-select');
            var $input = $form.find('.pr-cycle-day-input');
            var $length = $form.find('.pr-cycle-length');
            var current = keepDay !== undefined && keepDay !== null && keepDay !== ''
                ? String(keepDay)
                : (useInput ? $input.val() : $select.val());

            $form.find('.pr-length-group').toggle(hasLength);
            $length.prop('disabled', !hasLength).prop('required', hasLength);

            if (hasLength) {
                $length.attr('max', PR_LENGTH_LIMITS[periodic]);
                $form.find('.pr-length-name').text(periodic === 'year' ? 'Năm' : 'Ngày');
                $form.find('.pr-length-unit').text(periodic === 'year' ? 'năm' : 'ngày');
            }

            // Theo hạn lịch CAL: không dùng ngày cố định - ẩn hẳn cột "Ngày Cụ Thể" (+ disabled cả hai ô cycle_day)
            var calMode = prIsCalMode($form);

            $form.find('.pr-day-col').toggle(!calMode);
            $select.toggle(!useInput && !calMode).prop('disabled', useInput || calMode);
            $form.find('.pr-cycle-day-input-group').toggle(useInput && !calMode);
            $input.prop('disabled', !useInput || calMode).prop('required', useInput && !calMode);

            // Số ngày tạo đề nghị trước hạn CAL - cột riêng, ngược lại "Ngày Cụ Thể" (chỉ dùng khi CAL)
            $form.find('.pr-lead-col').toggle(calMode);
            $form.find('.pr-cal-lead-days').prop('disabled', !calMode).prop('required', calMode);

            if (useInput) {
                var max = prMaxDay(prSchedule($form));
                $input.attr('max', max || '').val(current || '');
                $form.find('.pr-day-max').text('/ ' + (max || '—'));
            } else {
                var maxDay = PR_MAX_DAY[periodic] || 0;
                $select.empty();

                for (var day = 1; day <= maxDay; day++) {
                    var text = periodic === 'week' ? PR_WEEKDAYS[day - 1]
                        : periodic === 'month' ? 'Ngày ' + day
                        : periodic === 'bi_month' ? 'Ngày thứ ' + day + ' của kỳ 2 tháng'
                        : 'Ngày thứ ' + day + ' của quý';

                    $select.append($('<option>').val(day).text(text));
                }

                if (current && $select.find('option[value="' + current + '"]').length) {
                    $select.val(current);
                }
            }

            prPreview($form);
        }

        /* ---------- Danh sách nội bộ: tần suất đề nghị lấy theo tần suất của đối tượng ---------- */
        /** Điền periodic + cycle_length ẩn theo tần suất đang chọn rồi dựng lại ô ngày */
        function prApplyFrequency($form, keepDay) {
            var schedule = PR_FREQUENCIES[$form.find('.pr-frequency').val()] || null;

            $form.find('.pr-periodic').val(schedule ? schedule.periodic : '');
            $form.find('.pr-cycle-length').val(schedule && schedule.length ? schedule.length : '');
            prApplyCycle($form, keepDay);
        }

        /**
         * Dựng ô "Tần Suất Đề Nghị" từ tần suất của đối tượng đang chọn. Đối tượng chỉ có một
         * tần suất thì chọn sẵn; chưa khai tần suất (hoặc chưa chọn đối tượng, vì Đối Tượng
         * không bắt buộc) thì cho chọn mọi tần suất.
         * keepCode: tần suất đang dùng khi sửa - không còn trong đối tượng vẫn giữ lại để không mất.
         */
        function prFillFrequencies($form, keepCode, keepDay) {
            var $frequency = $form.find('.pr-frequency');
            if (!$frequency.length) return;

            var $option = $form.find('.pr-object option:selected');
            var current = keepCode !== undefined && keepCode !== null ? String(keepCode) : ($frequency.val() || '');
            var codes = $option.val()
                ? String($option.data('frequencies') || '').split(',')
                    .map(function(code) { return $.trim(code); })
                    .filter(function(code) { return PR_FREQUENCIES[code]; })
                : [];

            if (!codes.length) codes = Object.keys(PR_FREQUENCIES);

            $frequency.empty();
            if (codes.length > 1) $frequency.append($('<option>').val('').text('-- Chọn tần suất --'));

            codes.forEach(function(code) {
                $frequency.append($('<option>').val(code).text(PR_FREQUENCIES[code].label));
            });

            if (keepCode && PR_FREQUENCIES[current] && codes.indexOf(current) === -1) {
                $frequency.append($('<option>').val(current).text(PR_FREQUENCIES[current].label + ' (đang dùng)'));
            }

            if (current && $frequency.find('option[value="' + current + '"]').length) {
                $frequency.val(current);
            } else if (codes.length === 1) {
                $frequency.val(codes[0]);
            }

            prApplyFrequency($form, keepDay);
        }

        /** "Mã - Tên đối tượng - Tần suất" - chỉ gợi ý khi Tiêu Đề đang trống hoặc còn đúng gợi ý lần trước */
        function prSuggestedTitle($form) {
            var $option = $form.find('.pr-object option:selected');
            var name = $.trim($option.data('name') || '');
            if (!$option.val() || !name) return '';

            var code = $.trim($option.data('code') || '');
            var label = code ? (code + ' - ' + name) : name;

            var $frequency = $form.find('.pr-frequency');
            var freqText = $frequency.length && $frequency.val() ? $.trim($frequency.find('option:selected').text()) : '';

            return freqText ? (label + ' - ' + freqText) : label;
        }

        function prApplyTitleSuggestion($form) {
            var $title = $form.find('[name="title"]');
            if (!$title.length) return;

            var suggestion = prSuggestedTitle($form);
            var current = $title.val() || '';
            var previous = $form.data('prTitleAuto') || '';

            if (current === '' || current === previous) {
                // Tiêu đề trống hoặc còn nguyên gợi ý lần trước - thay hẳn bằng gợi ý mới
                $title.val(suggestion);
            } else if (previous && current.slice(-previous.length) === previous) {
                // Người dùng gõ thêm tiền tố riêng trước gợi ý (VD "Bảo trì " + tên đối tượng) -
                // đổi đối tượng / tần suất thì chỉ thay phần gợi ý, giữ nguyên tiền tố đã gõ
                $title.val(current.slice(0, current.length - previous.length) + suggestion);
            }
            // Còn lại: tiêu đề đã được sửa tay khác hẳn gợi ý - giữ nguyên, không tự đổi

            $form.data('prTitleAuto', suggestion);
        }

        /** Vị trí + tần suất (đồng bộ từ CAL) của đối tượng đang chọn */
        function prObjectInfo($form) {
            var $info = $form.find('.pr-object-info').empty();
            var $option = $form.find('.pr-object option:selected');

            if (!$info.length || !$option.val()) return;

            [['Vị trí', $option.data('location')], ['Tần suất', $option.data('frequency')]].forEach(function(part) {
                if (!part[1]) return;
                if ($info.children().length) $info.append(document.createTextNode(' · '));
                $info.append($('<span>').text(part[0] + ': ').append($('<b>').text(String(part[1]))));
            });
        }

        $(document).on('change', '.pr-modal .pr-object', function() {
            var $form = $(this).closest('form');

            prObjectInfo($form);
            prFillFrequencies($form);
            prApplyTitleSuggestion($form);
            prLoadCal($form);
        });

        $(document).on('change', '.pr-modal .pr-frequency', function() {
            var $form = $(this).closest('form');

            prApplyFrequency($form);
            prApplyTitleSuggestion($form);
            prLoadCal($form);
        });

        /* ---------- Ngày tạo theo hạn lịch CAL (chỉ danh sách nội bộ gắn đối tượng CAL) ---------- */
        var prCalRequestSeq = 0;

        /**
         * Hỏi server lịch Pending CAL của đối tượng + tần suất đang chọn (liên kết qua Inst_ID).
         * Có lịch thì bật tuỳ chọn "Theo ngày đến hạn lịch CAL" và hiện hạn đang theo; không có thì
         * khoá tuỳ chọn, đang chọn thì trả về "Ngày cố định". Server tính lại khi lưu - đây chỉ để hiển thị.
         * keepMode: cách chọn ngày muốn giữ (khi sửa / lưu lỗi) nếu lịch CAL còn dùng được.
         */
        function prLoadCal($form, keepMode) {
            var $mode = $form.find('.pr-day-mode');
            if (!$mode.length) return;

            var objectId = $form.find('select.pr-object').val() || '';
            var frequency = $form.find('.pr-frequency').val() || '';
            var wanted = keepMode !== undefined && keepMode !== null && keepMode !== '' ? String(keepMode) : $mode.val();
            var origin = $form.data('origin') || {};
            var seq = ++prCalRequestSeq;

            if (!objectId || !frequency) {
                prApplyCal($form, { available: false, message: objectId ? 'Chọn tần suất đề nghị trước.' : '' }, wanted);
                return;
            }

            $form.find('.pr-cal-info').removeClass('is-warning')
                .html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang đọc lịch CAL...');

            fetch(PR_CAL_SCHEDULE_URL + '?' + $.param({
                    object_id: objectId,
                    frequency: frequency,
                    start_date: $form.find('.pr-start-date').val() || '',
                    list_id: origin.id || '',
                    cal_lead_days: $form.find('.pr-cal-lead-days').val() || ''
                }), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(response) {
                    if (!response.ok) throw new Error('http');
                    return response.json();
                })
                .then(function(data) {
                    if (seq === prCalRequestSeq) prApplyCal($form, data, wanted);
                })
                .catch(function() {
                    if (seq === prCalRequestSeq) {
                        prApplyCal($form, { available: false, message: 'Không đọc được lịch từ phần mềm CAL, vui lòng thử lại sau.' }, wanted);
                    }
                });
        }

        /** Bật / khoá tuỳ chọn theo kết quả lịch CAL, hiện hạn đang theo hoặc lý do không dùng được */
        function prApplyCal($form, data, wanted) {
            var $mode = $form.find('.pr-day-mode');
            var $info = $form.find('.pr-cal-info').removeClass('is-warning').empty();
            var available = !!(data && data.available);

            $form.data('cal', available ? data : null);
            $mode.find('option[value="cal_due"]').prop('disabled', !available);
            $mode.val(available && wanted === 'cal_due' ? 'cal_due' : 'fixed');

            if (prIsCalMode($form)) {
                if (data.next) {
                    var due = prParseDate(data.next.due_date);
                    var dueText = prPad(due.getDate()) + '/' + prPad(due.getMonth() + 1) + '/' + due.getFullYear();

                    // Có tạo trước hạn (run_date != due_date) thì hiện cả 2 ngày, không thì chỉ hiện hạn CAL
                    if (data.next.run_date && data.next.run_date !== data.next.due_date) {
                        var run = prParseDate(data.next.run_date);

                        $info.append($('<span>').text('Tạo đề nghị: ').append($('<b>').text(
                            prPad(run.getDate()) + '/' + prPad(run.getMonth() + 1) + '/' + run.getFullYear()
                        )));
                        $info.append(document.createTextNode(' · Hạn CAL ' + dueText));
                    } else {
                        $info.append($('<span>').text('Hạn CAL: ').append($('<b>').text(dueText)));
                    }

                    $info.append(document.createTextNode(' · ' + data.next.inst_id + ' · SCH_ID ' + data.next.sch_id));
                } else if (data.message) {
                    $info.addClass('is-warning').text(data.message);
                }
            } else if (!available && data && data.message) {
                // Lý do tuỳ chọn "Theo ngày đến hạn lịch CAL" đang bị khoá, ngay dưới ô chọn
                $info.toggleClass('is-warning', wanted === 'cal_due').text(data.message);
            }

            prApplyCycle($form);
        }

        $(document).on('change', '.pr-modal .pr-day-mode', function() {
            var $form = $(this).closest('form');

            prApplyCal($form, $form.data('cal'), $(this).val());
        });

        // Đổi số ngày tạo trước hạn CAL thì đọc lại lịch để cập nhật ngày tạo đề nghị hiển thị
        $(document).on('input change', '.pr-modal .pr-cal-lead-days', function() {
            prLoadCal($(this).closest('form'));
        });

        /* ---------- Dòng vật tư ---------- */
        /** Chọn đơn vị cho dòng; đơn vị cũ không còn trong danh sách thì thêm tạm một option */
        function prSetUnit($row, unit) {
            var $unit = $row.find('.pr-unit');
            if (!unit) return;
            if (!$unit.find('option').filter(function() { return this.value === unit; }).length) {
                $unit.append($('<option>').val(unit).text(unit));
            }
            $unit.val(unit);
        }

        /** Thông tin kỹ thuật của vật tư đang chọn - chỉ hiển thị, có title để xem đủ khi bị cắt */
        function prSetSpec($row) {
            var spec = $row.find('.pr-cat option:selected').data('spec');
            spec = spec === undefined || spec === null ? '' : String(spec);

            $row.find('.pr-spec').val(spec).attr('title', spec);
        }

        function prAddRow($modal, data) {
            data = data || {};

            var template = $modal.find('template.pr-row-template')[0];
            var $row = $(document.importNode(template.content, true).querySelector('tr'));
            var index = prRowIndex++;

            $row.find('[data-name]').each(function() {
                this.name = 'items[' + index + '][' + $(this).data('name') + ']';
            });

            $modal.find('.pr-rows').append($row);

            var $category = $row.find('.pr-cat');
            if (data.category_id) $category.val(String(data.category_id));

            $row.find('[data-name="requested_amount"]').val(prTrimNumber(data.requested_amount));
            prSetSpec($row);
            $row.find('[data-name="purpose"]').val(data.purpose || '');
            prSetUnit($row, data.requested_unit || $category.find('option:selected').data('unit') || '');

            $category.select2({
                theme: 'bootstrap4',
                dropdownParent: $modal,
                width: '100%',
                placeholder: '-- Chọn vật tư --',
                language: {
                    noResults: function() {
                        return 'Không tìm thấy vật tư phù hợp';
                    }
                }
            });
        }

        function prFillRows($modal, items) {
            $modal.find('.pr-rows .pr-cat').each(function() {
                if ($(this).data('select2')) $(this).select2('destroy');
            });
            $modal.find('.pr-rows').empty();

            (items && items.length ? items : [{}]).forEach(function(item) {
                prAddRow($modal, item);
            });
        }

        function prClearErrors($modal) {
            $modal.find('.pr-errors').remove();
            $modal.find('.is-invalid').removeClass('is-invalid');
        }

        /* ---------- Thêm mới ---------- */
        $(document).on('click', '.btn-pr-create', function() {
            var $modal = $($(this).data('modal'));
            var $form = $modal.find('form');

            $form[0].reset();
            prClearErrors($modal);
            $form.removeData('origin');
            $form.removeData('prTitleAuto');

            $form.find('[name="title"]').val('');
            $form.find('select.pr-periodic').val('month');
            $form.find('.pr-cycle-length').val('');
            $form.find('.pr-start-date').val(prIsoDate(new Date()));
            $form.find('.pr-day-mode').val('fixed');
            $form.find('.pr-cal-lead-days').val(PR_CAL_LEAD_DAYS_DEFAULT);
            $form.removeData('cal');
            prApplyCycle($form, 1);
            $form.find('select[name="to_department_id"]').val('').trigger('change');
            $form.find('select[name="consumption_object_id"]').val('').trigger('change');

            prFillRows($modal, []);
            $modal.modal('show');
        });

        /* ---------- Cập nhật ---------- */
        $(document).on('click', '.btn-pr-edit', function() {
            var row = $(this).data('row') || {};
            var $modal = $($(this).data('modal'));
            var $form = $modal.find('form');

            prClearErrors($modal);
            $form.data('origin', row);

            $form.find('[name="id"]').val(row.id);
            $form.find('[name="title"]').val(row.title || '');
            $form.find('.pr-periodic').val(row.periodic || 'month');
            $form.find('.pr-cycle-length').val(row.cycle_length || '');
            $form.find('.pr-start-date').val(row.start_date || '');
            $form.find('[name="change_reason"]').val('');
            $form.find('.pr-day-mode').val('fixed');
            $form.find('.pr-cal-lead-days').val(row.cal_lead_days || PR_CAL_LEAD_DAYS_DEFAULT);
            $form.removeData('cal');
            prApplyCycle($form, row.cycle_day);
            $form.find('select[name="to_department_id"]').val(row.to_department_id ? String(row.to_department_id) : '').trigger('change');
            $form.find('select[name="consumption_object_id"]').val(row.consumption_object_id ? String(row.consumption_object_id) : '').trigger('change');
            prFillFrequencies($form, row.frequency, row.cycle_day);
            // Dựng tần suất xong mới đọc lịch CAL, giữ đúng cách chọn ngày đã lưu
            prLoadCal($form, row.cycle_day_mode);

            prFillRows($modal, row.items || []);
            $modal.modal('show');
        });

        /* ---------- Đổi lịch chu kỳ ---------- */
        $(document).on('change', '.pr-modal select.pr-periodic', function() {
            prApplyCycle($(this).closest('form'));
        });

        $(document).on('input change', '.pr-modal .pr-cycle-length', function() {
            prApplyCycle($(this).closest('form'));
        });

        $(document).on('input change', '.pr-modal .pr-cycle-day-select, .pr-modal .pr-cycle-day-input, .pr-modal .pr-start-date', function() {
            prPreview($(this).closest('form'));
        });

        // Đổi ngày bắt đầu thì lịch CAL đang theo có thể đổi (không tạo đề nghị trước ngày bắt đầu)
        $(document).on('change', '.pr-modal .pr-start-date', function() {
            var $form = $(this).closest('form');

            if ($form.find('.pr-day-mode').length && $form.find('select.pr-object').val()) prLoadCal($form);
        });

        /* ---------- Thêm / xoá dòng, chọn vật tư thì điền sẵn đơn vị của phòng ---------- */
        $(document).on('click', '.pr-add-row', function() {
            prAddRow($(this).closest('.pr-modal'));
        });

        $(document).on('click', '.pr-del-row', function() {
            var $modal = $(this).closest('.pr-modal');
            var $row = $(this).closest('tr');

            $row.find('.pr-cat').each(function() {
                if ($(this).data('select2')) $(this).select2('destroy');
            });
            $row.remove();

            if (!$modal.find('.pr-rows tr').length) prAddRow($modal);
        });

        $(document).on('change', '.pr-modal .pr-cat', function() {
            var $row = $(this).closest('tr');
            var unit = $(this).find('option:selected').data('unit');

            if (unit) prSetUnit($row, String(unit));
            prSetSpec($row);
        });

        /* ---------- Bảng chọn nhiều vật tư từ danh mục ---------- */
        function prPickerRefresh($picker) {
            var search = ($picker.find('.pr-picker-search').val() || '').toLowerCase().trim();
            var visible = 0;

            $picker.find('.pr-picker-row').each(function() {
                var match = !search || String($(this).data('search')).indexOf(search) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });

            var $selectable = $picker.find('.pr-picker-row:visible .pr-picker-check:not(:disabled)');

            $picker.find('.pr-picker-visible').text(visible + ' vật tư');
            $picker.find('.pr-picker-selected b').text($picker.find('.pr-picker-check:checked').length);
            $picker.find('.pr-picker-all').prop('checked', $selectable.length > 0 && $selectable.filter(':checked').length === $selectable.length);
        }

        $(document).on('click', '.pr-open-picker', function() {
            var $modal = $(this).closest('.pr-modal');
            var $picker = $($(this).data('picker'));
            var chosen = {};

            $modal.find('.pr-rows .pr-cat').each(function() {
                if (this.value) chosen[this.value] = true;
            });

            // Vật tư đã có trong danh sách thì khoá lại, không cho chọn trùng dòng
            $picker.data('target', $modal);
            $picker.find('.pr-picker-search').val('');
            $picker.find('.pr-picker-row').each(function() {
                var added = !!chosen[String($(this).data('id'))];

                $(this).toggleClass('is-added', added).removeClass('is-checked');
                $(this).find('.pr-picker-check').prop('checked', false).prop('disabled', added);
            });

            prPickerRefresh($picker);
            $picker.modal('show');
        });

        $(document).on('shown.bs.modal', '.pr-picker', function() {
            // Lớp phủ của bảng chọn phải che luôn modal thêm / sửa phía dưới
            $('.modal-backdrop').last().css('z-index', 1055);
            $(this).find('.pr-picker-search').trigger('focus');
        });

        $(document).on('hidden.bs.modal', '.pr-picker', function() {
            // Bootstrap gỡ modal-open khỏi body khi đóng modal trên cùng, modal bên dưới sẽ mất cuộn
            if ($('.modal.show').length) $('body').addClass('modal-open');
        });

        $(document).on('input', '.pr-picker-search', function() {
            prPickerRefresh($(this).closest('.pr-picker'));
        });

        $(document).on('change', '.pr-picker-check', function() {
            $(this).closest('.pr-picker-row').toggleClass('is-checked', this.checked);
            prPickerRefresh($(this).closest('.pr-picker'));
        });

        $(document).on('click', '.pr-picker-row', function(e) {
            if ($(e.target).is('input')) return;

            var $check = $(this).find('.pr-picker-check');
            if ($check.prop('disabled')) return;

            $check.prop('checked', !$check.prop('checked')).trigger('change');
        });

        $(document).on('change', '.pr-picker-all', function() {
            var $picker = $(this).closest('.pr-picker');
            var checked = this.checked;

            $picker.find('.pr-picker-row:visible .pr-picker-check:not(:disabled)').each(function() {
                $(this).prop('checked', checked).closest('.pr-picker-row').toggleClass('is-checked', checked);
            });

            prPickerRefresh($picker);
        });

        $(document).on('click', '.pr-picker-confirm', function() {
            var $picker = $(this).closest('.pr-picker');
            var $modal = $picker.data('target');
            var ids = $picker.find('.pr-picker-check:checked').map(function() {
                return this.value;
            }).get();

            if (!$modal || !ids.length) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Chưa chọn vật tư nào', text: 'Tick chọn ít nhất một vật tư để thêm vào danh sách.' });
                }
                return;
            }

            // Dùng lại các dòng còn trống (chưa chọn vật tư, chưa nhập số lượng) trước khi thêm dòng mới.
            // Dòng trống có sẵn thiết bị / mục đích vẫn giữ nguyên, chỉ điền vật tư vào.
            var emptyRows = $modal.find('.pr-rows tr').filter(function() {
                return !$(this).find('.pr-cat').val() && !$(this).find('[data-name="requested_amount"]').val();
            }).get();

            ids.forEach(function(id) {
                var row = emptyRows.shift();

                if (row) {
                    $(row).find('.pr-cat').val(id).trigger('change');
                } else {
                    prAddRow($modal, { category_id: id });
                }
            });

            $picker.modal('hide');
        });

        /* ---------- Bảng chọn 1 Đối Tượng từ dữ liệu gốc (chỉ danh sách nội bộ) ---------- */
        /**
         * Danh mục đối tượng có thể lên tới hàng nghìn dòng nên KHÔNG nhúng sẵn vào trang - mở modal /
         * gõ tìm / đổi bộ lọc thì gọi AJAX lấy trang đầu (50 dòng), cuộn tới cuối bảng (hoặc bấm dòng
         * "Tải thêm") thì lấy trang kế và nối vào, xem PeriodicRequestController::objects().
         * keepId (nếu có) luôn đứng đầu trang đầu dù không khớp bộ lọc, để giữ đúng lựa chọn đang dùng.
         * append = true: tải trang kế theo trạng thái đã lưu ở $picker.data('prObjectState').
         */
        function prObjectPickerLoad($picker, keepId, append) {
            var $tbody = $picker.find('.pr-object-picker-tbody');
            var state = $picker.data('prObjectState') || {};

            if (append) {
                if (state.loading || !state.more) return;
            } else {
                state = { keepId: keepId || '', offset: 0, more: false, count: 0, seq: (state.seq || 0) + 1 };
                $tbody.html('<tr><td colspan="7" class="text-center md-empty py-4">'
                    + '<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải...</td></tr>');
                $picker.find('.pr-picker-wrap').scrollTop(0);
            }

            state.loading = true;
            $picker.data('prObjectState', state);

            var seq = state.seq;
            var params = $.param({
                q: $picker.find('.pr-object-picker-search').val() || '',
                type: $picker.find('.pr-object-picker-type').val() || '',
                frequency: $picker.find('.pr-object-picker-frequency').val() || '',
                keep_id: state.keepId,
                offset: state.offset
            });

            $tbody.find('.pr-object-picker-more').html('<td colspan="7" class="text-center md-sub py-2">'
                + '<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải thêm...</td>');

            fetch(PR_OBJECT_SEARCH_URL + '?' + params, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(response) {
                    if (!response.ok) throw new Error('http');
                    return response.json();
                })
                .then(function(data) {
                    // Đã tìm / lọc lại trong lúc chờ - bỏ kết quả cũ
                    if (seq !== ($picker.data('prObjectState') || {}).seq) return;

                    state.loading = false;
                    state.offset = data.next_offset || 0;
                    state.more = !!data.more;

                    prRenderObjectPickerRows($picker, data.items || [], state, !!append);
                    $picker.find('.pr-object-picker-visible').text(
                        Number(data.total || 0).toLocaleString('vi-VN') + ' đối tượng'
                    );
                })
                .catch(function() {
                    if (seq !== ($picker.data('prObjectState') || {}).seq) return;

                    state.loading = false;

                    if (append) {
                        $tbody.find('.pr-object-picker-more').html('<td colspan="7" class="text-center md-sub py-2">'
                            + 'Không tải thêm được. Bấm để thử lại.</td>');
                        return;
                    }

                    $tbody.html('<tr><td colspan="7" class="text-center md-empty py-4">'
                        + 'Không tải được danh sách đối tượng. Vui lòng thử lại.</td></tr>');
                });
        }

        function prRenderObjectPickerRows($picker, items, state, append) {
            var $tbody = $picker.find('.pr-object-picker-tbody');
            var keepId = state.keepId;

            $tbody.find('.pr-object-picker-more').remove();

            if (!append) {
                $tbody.empty();
                state.count = 0;
            }

            if (!append && !items.length) {
                $tbody.html('<tr><td colspan="7" class="text-center md-empty py-4">Không tìm thấy đối tượng phù hợp.</td></tr>');
                $picker.find('.pr-picker-selected b').text(0);
                return;
            }

            items.forEach(function(object) {
                PR_OBJECT_CACHE[object.id] = object;

                // Trang sau có thể trả lại dòng đang chọn đã chèn ở trang đầu - không lặp
                if (append && $tbody.find('.pr-object-picker-row[data-id="' + object.id + '"]').length) return;

                state.count++;

                var isKept = keepId && String(object.id) === String(keepId);
                var $row = $('<tr>').addClass('pr-picker-row pr-object-picker-row').toggleClass('is-checked', !!isKept)
                    .attr('data-id', object.id);

                $row.append($('<td>').addClass('text-center').append(
                    $('<input>').attr({ type: 'radio', name: 'pr_object_picker_choice' })
                        .addClass('pr-object-picker-check').val(object.id).prop('checked', !!isKept)
                ));
                $row.append($('<td>').addClass('text-center md-sub').text(state.count));
                $row.append($('<td>').append($('<span>').addClass('md-tag').text(object.code)));

                var $nameCell = $('<td>').append($('<span>').addClass('font-weight-bold').text(object.name));
                if (object.status_id != 1) {
                    $nameCell.append($('<span>').addClass('badge badge-secondary ml-1').text('Đã khoá'));
                }
                $row.append($nameCell);

                $row.append($('<td>').addClass('md-sub').text(object.type_label || object.type));
                $row.append($('<td>').addClass('md-sub').text(object.location || '—'));
                $row.append($('<td>').addClass('md-sub').text(object.frequency_label || '—'));

                $tbody.append($row);
            });

            if (state.more) {
                $tbody.append($('<tr>').addClass('pr-object-picker-more').append(
                    $('<td>').attr('colspan', 7).addClass('text-center py-2')
                        .append($('<a href="#">').html('<i class="fas fa-angle-double-down mr-1"></i> Tải thêm đối tượng'))
                ));
            }

            $picker.find('.pr-picker-selected b').text($picker.find('.pr-object-picker-check:checked').length);
            prObjectPickerFill($picker);
        }

        /** Trang vừa tải chưa đủ cao để có thanh cuộn thì tải tiếp, tránh kẹt không cuộn được */
        function prObjectPickerFill($picker) {
            var wrap = $picker.find('.pr-picker-wrap').get(0);

            if (wrap && $picker.is(':visible') && wrap.scrollHeight <= wrap.clientHeight + 40) {
                prObjectPickerLoad($picker, null, true);
            }
        }

        // Sự kiện scroll không nổi bọt nên gắn thẳng vào vùng cuộn của từng modal chọn đối tượng
        $('.pr-object-picker .pr-picker-wrap').on('scroll', function() {
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 80) {
                prObjectPickerLoad($(this).closest('.pr-picker'), null, true);
            }
        });

        $(document).on('click', '.pr-object-picker-more', function(e) {
            e.preventDefault();
            prObjectPickerLoad($(this).closest('.pr-picker'), null, true);
        });

        $(document).on('click', '.pr-open-object-picker', function() {
            var $modal = $(this).closest('.pr-modal');
            var $picker = $($(this).data('picker'));
            var current = $modal.find('select.pr-object').val() || '';

            $picker.data('target', $modal);
            $picker.find('.pr-object-picker-search').val('');
            $picker.find('.pr-object-picker-type').val('');
            $picker.find('.pr-object-picker-frequency').val('');

            prObjectPickerLoad($picker, current);
            $picker.modal('show');
        });

        $(document).on('shown.bs.modal', '.pr-object-picker', function() {
            $('.modal-backdrop').last().css('z-index', 1055);
            $(this).find('.pr-object-picker-search').trigger('focus');
            prObjectPickerFill($(this));
        });

        var prObjectSearchTimer = null;

        $(document).on('input', '.pr-object-picker-search', function() {
            var $picker = $(this).closest('.pr-picker');

            clearTimeout(prObjectSearchTimer);
            prObjectSearchTimer = setTimeout(function() {
                prObjectPickerLoad($picker, $picker.find('.pr-object-picker-check:checked').val());
            }, 300);
        });

        $(document).on('change', '.pr-object-picker-type, .pr-object-picker-frequency', function() {
            var $picker = $(this).closest('.pr-picker');
            prObjectPickerLoad($picker, $picker.find('.pr-object-picker-check:checked').val());
        });

        $(document).on('change', '.pr-object-picker-check', function() {
            var $picker = $(this).closest('.pr-picker');

            $picker.find('.pr-object-picker-row').removeClass('is-checked');
            $(this).closest('.pr-object-picker-row').toggleClass('is-checked', this.checked);
            $picker.find('.pr-picker-selected b').text($picker.find('.pr-object-picker-check:checked').length);
        });

        $(document).on('click', '.pr-object-picker-row', function(e) {
            if ($(e.target).is('input')) return;
            $(this).find('.pr-object-picker-check').prop('checked', true).trigger('change');
        });

        $(document).on('click', '.pr-object-picker-confirm', function() {
            var $picker = $(this).closest('.pr-picker');
            var $modal = $picker.data('target');
            var id = $picker.find('.pr-object-picker-check:checked').val();

            if (!$modal || !id) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Chưa chọn đối tượng nào', text: 'Chọn một đối tượng trong danh sách để dùng cho danh sách đề nghị.' });
                }
                return;
            }

            // Đối tượng vừa chọn có thể chưa từng nằm trong ô chọn (nhúng rất ít dòng ban đầu) -
            // dựng / cập nhật <option> từ dữ liệu đã tải qua AJAX trước khi gán giá trị.
            var object = PR_OBJECT_CACHE[id];
            var $select = $modal.find('select.pr-object');
            var $option = $select.find('option[value="' + id + '"]');

            if (!$option.length) {
                $option = $('<option>').val(id);
                $select.append($option);
            }

            if (object) {
                $option.attr({
                    'data-code': object.code || '',
                    'data-name': object.name || '',
                    'data-location': object.location || '',
                    'data-frequency': object.frequency_label || '',
                    'data-frequencies': object.frequency || ''
                }).text(object.code + ' - ' + object.name + (object.status_id != 1 ? ' (đã khoá)' : ''));
            }

            $select.val(id).trigger('change');
            $picker.modal('hide');
        });

        /* ---------- Dựng sẵn ô ngày / tần suất cho mọi modal; lưu bị lỗi validate thì mở lại đúng modal ---------- */
        $('.pr-modal').each(function() {
            var $modal = $(this);
            var $form = $modal.find('form');
            var old = String($modal.data('has-errors')) === '1' ? ($modal.data('old') || {}) : null;

            prApplyCycle($form, old ? old.cycle_day : 1);
            prObjectInfo($form);
            prFillFrequencies($form, old ? old.frequency : undefined, old ? old.cycle_day : undefined);
            // Lưu bị lỗi validate lúc đang chọn "Theo ngày đến hạn lịch CAL": đọc lại lịch để giữ lựa chọn
            if (old && old.cycle_day_mode === 'cal_due') prLoadCal($form, 'cal_due');

            if (!old) return;

            var items = old.items || [];
            prFillRows($modal, Array.isArray(items) ? items : Object.values(items));
            $modal.modal('show');
        });
    });
</script>
@endonce
