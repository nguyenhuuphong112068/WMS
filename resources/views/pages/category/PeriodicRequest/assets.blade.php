{{--
| CSS + JS của 2 tab "Danh sách vật tư đề nghị ... theo chu kỳ".
| Bọc trong @once nên include ở cả hai tab vẫn chỉ in ra một bản.
--}}
@once
<style>
    /* ---------- Cột "Vật Tư Đề Nghị" trên bảng ---------- */
    .pr-items {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .pr-item {
        padding: 4px 0;
        border-bottom: 1px dashed var(--primary-soft);
        line-height: 1.45;
    }

    .pr-item:last-child {
        border-bottom: none;
    }

    .pr-item-name {
        font-weight: 600;
        color: var(--text-main);
        margin-left: 4px;
    }

    .pr-item-amount {
        margin-left: 6px;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .pr-item-purpose {
        max-width: 460px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: 0.78rem;
        color: #64748b;
    }

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

    .pr-rows-table .select2-container--bootstrap4 .select2-selection {
        min-height: 31px;
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

    /* ---------- Ô xem trước lần tạo đề nghị kế tiếp ---------- */
    .pr-next-box {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 38px;
        padding: 4px 12px;
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
        color: var(--primary-dark);
        line-height: 1.3;
    }

    .pr-next-label {
        font-size: 0.74rem;
        color: #64748b;
    }

    .pr-next-label i {
        color: var(--primary);
    }

    .pr-next-box b {
        color: var(--primary);
        font-size: 0.9rem;
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
    .pr-picker-all {
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var prRowIndex = 0;

        var PR_WEEKDAYS = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
        var PR_MAX_DAY = @json(\App\Support\MaterialPeriodicRequest::CYCLE_MAX_DAY);
        var PR_MAX_LENGTH = {{ \App\Support\MaterialPeriodicRequest::CYCLE_MAX_LENGTH }};

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

        /* ---------- Tính ngày tạo kế tiếp - cùng thuật toán với MaterialPeriodicRequest::nextRunDate() ---------- */
        function prAddDays(date, days) {
            return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
        }

        function prParseDate(value) {
            var parts = String(value || '').split('-');
            return parts.length === 3 ? new Date(+parts[0], +parts[1] - 1, +parts[2]) : null;
        }

        function prPeriodStart(periodic, date, start, length) {
            if (periodic === 'week') return prAddDays(date, -((date.getDay() + 6) % 7));
            if (periodic === 'quarter') return new Date(date.getFullYear(), Math.floor(date.getMonth() / 3) * 3, 1);
            if (periodic === 'days') {
                var diff = Math.round((date - start) / 86400000);
                return prAddDays(start, Math.floor(diff / length) * length);
            }
            return new Date(date.getFullYear(), date.getMonth(), 1);
        }

        function prPeriodEnd(periodic, periodStart, length) {
            if (periodic === 'week') return prAddDays(periodStart, 6);
            if (periodic === 'quarter') return new Date(periodStart.getFullYear(), periodStart.getMonth() + 3, 0);
            if (periodic === 'days') return prAddDays(periodStart, length - 1);
            return new Date(periodStart.getFullYear(), periodStart.getMonth() + 1, 0);
        }

        /** Lúc vừa khai / đổi lịch: tính cả hôm nay (hoặc chính ngày bắt đầu) */
        function prNextRunDate(periodic, cycleDay, length, startValue) {
            var start = prParseDate(startValue);
            if (!start || !cycleDay || (periodic === 'days' && !length)) return null;

            var now = new Date();
            var from = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            if (from < start) from = start;

            var threshold = prAddDays(from, -1);
            var periodStart = prPeriodStart(periodic, from, start, length);

            for (var i = 0; i < 24; i++) {
                var end = prPeriodEnd(periodic, periodStart, length);
                var candidate = prAddDays(periodStart, cycleDay - 1);

                if (candidate > end) candidate = end;
                if (candidate > threshold) return candidate;

                periodStart = prAddDays(end, 1);
            }

            return null;
        }

        function prSchedule($form) {
            var periodic = $form.find('.pr-periodic').val();
            var isDays = periodic === 'days';

            return {
                periodic: periodic,
                cycle_length: isDays ? (parseInt($form.find('.pr-cycle-length').val(), 10) || 0) : null,
                cycle_day: parseInt((isDays ? $form.find('.pr-cycle-day-input') : $form.find('.pr-cycle-day-select')).val(), 10) || 0,
                start_date: $form.find('.pr-start-date').val()
            };
        }

        function prPreview($form) {
            var schedule = prSchedule($form);
            var origin = $form.data('origin');
            var date;

            // Sửa mà chưa đổi lịch thì server giữ nguyên ngày kế tiếp đang có
            if (origin && origin.next_run_date && origin.periodic === schedule.periodic
                && parseInt(origin.cycle_day, 10) === schedule.cycle_day
                && (origin.cycle_length ? parseInt(origin.cycle_length, 10) : null) === schedule.cycle_length
                && origin.start_date === schedule.start_date) {
                date = prParseDate(origin.next_run_date);
            } else {
                var maxDay = schedule.periodic === 'days' ? schedule.cycle_length : PR_MAX_DAY[schedule.periodic];
                date = schedule.cycle_day >= 1 && schedule.cycle_day <= maxDay
                    ? prNextRunDate(schedule.periodic, schedule.cycle_day, schedule.cycle_length, schedule.start_date)
                    : null;
            }

            $form.find('.pr-next-preview').text(date
                ? PR_WEEKDAYS[(date.getDay() + 6) % 7] + ', ' + prPad(date.getDate()) + '/' + prPad(date.getMonth() + 1) + '/' + date.getFullYear()
                : '—');
        }

        /**
         * Hiện đúng ô theo chu kỳ đang chọn, giữ ngày cũ nếu còn hợp lệ.
         * Hằng tuần / tháng / quý: ô chọn ngày. Theo số ngày: ô nhập số ngày + ô nhập ngày thứ mấy.
         */
        function prApplyCycle($form, keepDay) {
            var periodic = $form.find('.pr-periodic').val();
            var isDays = periodic === 'days';
            var $select = $form.find('.pr-cycle-day-select');
            var $input = $form.find('.pr-cycle-day-input');
            var current = keepDay !== undefined && keepDay !== null && keepDay !== ''
                ? String(keepDay)
                : (isDays ? $input.val() : $select.val());

            $form.find('.pr-length-group').toggle(isDays);
            $form.find('.pr-cycle-length').prop('disabled', !isDays).prop('required', isDays);
            $select.toggle(!isDays).prop('disabled', isDays);
            $form.find('.pr-cycle-day-input-group').toggle(isDays);
            $input.prop('disabled', !isDays).prop('required', isDays);

            if (isDays) {
                var length = parseInt($form.find('.pr-cycle-length').val(), 10) || 0;
                $input.attr('max', length || PR_MAX_LENGTH).val(current || '');
                $form.find('.pr-day-max').text('/ ' + (length || '—'));
            } else {
                var max = PR_MAX_DAY[periodic] || 0;
                $select.empty();

                for (var day = 1; day <= max; day++) {
                    var text = periodic === 'week' ? PR_WEEKDAYS[day - 1]
                        : periodic === 'month' ? 'Ngày ' + day
                        : 'Ngày thứ ' + day + ' của quý';

                    $select.append($('<option>').val(day).text(text));
                }

                if (current && $select.find('option[value="' + current + '"]').length) {
                    $select.val(current);
                }
            }

            prPreview($form);
        }

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

        /** Vị trí + tần suất (đồng bộ từ CAL) của đối tượng đang chọn, để tham khảo khi đặt chu kỳ */
        function prObjectInfo($form) {
            var $info = $form.find('.pr-object-info').empty();
            var $option = $form.find('.pr-object option:selected');

            if (!$info.length || !$option.val()) return;

            [['Vị trí', $option.data('location')], ['Tần suất', $option.data('frequency')]].forEach(function(part, index) {
                if (!part[1]) return;
                if ($info.children().length) $info.append(document.createTextNode(' · '));
                $info.append($('<span>').text(part[0] + ': ').append($('<b>').text(String(part[1]))));
            });
        }

        $(document).on('change', '.pr-modal .pr-object', function() {
            prObjectInfo($(this).closest('form'));
        });

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
            $row.find('[data-name="product_name"]').val(data.product_name || '');
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

            $form.find('[name="title"]').val('');
            $form.find('.pr-periodic').val('month');
            $form.find('.pr-cycle-length').val('');
            $form.find('.pr-start-date').val(prIsoDate(new Date()));
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
            prApplyCycle($form, row.cycle_day);
            $form.find('select[name="to_department_id"]').val(row.to_department_id ? String(row.to_department_id) : '').trigger('change');
            $form.find('select[name="consumption_object_id"]').val(row.consumption_object_id ? String(row.consumption_object_id) : '').trigger('change');

            prFillRows($modal, row.items || []);
            $modal.modal('show');
        });

        /* ---------- Đổi lịch chu kỳ ---------- */
        $(document).on('change', '.pr-modal .pr-periodic', function() {
            prApplyCycle($(this).closest('form'));
        });

        $(document).on('input change', '.pr-modal .pr-cycle-length', function() {
            var $form = $(this).closest('form');
            var length = parseInt($(this).val(), 10) || 0;

            $form.find('.pr-cycle-day-input').attr('max', length || PR_MAX_LENGTH);
            $form.find('.pr-day-max').text('/ ' + (length || '—'));
            prPreview($form);
        });

        $(document).on('input change', '.pr-modal .pr-cycle-day-select, .pr-modal .pr-cycle-day-input, .pr-modal .pr-start-date', function() {
            prPreview($(this).closest('form'));
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

        /* ---------- Dựng sẵn ô ngày cho mọi modal; lưu bị lỗi validate thì mở lại đúng modal ---------- */
        $('.pr-modal').each(function() {
            var $modal = $(this);
            var old = String($modal.data('has-errors')) === '1' ? ($modal.data('old') || {}) : null;

            prApplyCycle($modal.find('form'), old ? old.cycle_day : 1);
            prObjectInfo($modal.find('form'));

            if (!old) return;

            var items = old.items || [];
            prFillRows($modal, Array.isArray(items) ? items : Object.values(items));
            $modal.modal('show');
        });
    });
</script>
@endonce
