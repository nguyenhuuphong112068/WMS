{{--
|--------------------------------------------------------------------------
| TAB KẾ HOẠCH ĐÁNH GIÁ (Tồn Kho Chất Chuẩn) - CSS + JS riêng của tab
|--------------------------------------------------------------------------
| Kéo theo assets của Chuẩn Thứ Cấp để dùng lại nguyên bộ nhãn đã có: .ssa-code,
| .ssa-group-tag, .ssa-timepoint, .ssa-testing, .ssa-state-*, .ssa-badge,
| .ssa-filters / .ssa-chip - hai màn hình cùng một chức năng thì phải cùng một cách
| hiển thị, không vẽ lại bộ nhãn thứ hai.
|
| Phần thêm ở đây chỉ là những thứ màn hình kia không có:
| - .plan-filter : khối chọn khoảng xem (plan_from / plan_to) + số liệu của khoảng
| - .plan-months : dải thời gian theo tháng, bấm vào lọc bảng theo đúng tháng đó
|
| Bộ lọc và dải tháng nằm chung thanh .md-tablebar của layout để nhường chiều cao cho bảng.
--}}

@include('pages.inventory.StandardStability.assets')

@once

    <style>


        #planTable thead th {
            padding: 7px 8px;
        }

        #planTable tbody td {
            padding: 6px 8px;
        }



        /* Khối chọn khoảng xem của kế hoạch + dải số liệu của khoảng */
        .plan-filter {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 14px;
            margin-bottom: 12px;
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-md);
            background: var(--primary-soft);
        }

        #planTable .md-sub {
            font-size: 0.78rem;
        }

        .plan-field {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .plan-field label {
            margin: 0;
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .plan-field label i {
            margin-right: 3px;
            color: var(--primary);
        }

        .plan-field .form-control {
            width: 146px;
            height: 32px;
            padding: 3px 9px;
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-md);
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-main);
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .plan-field .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.14);
        }

        .plan-apply {
            height: 32px;
            padding: 0 14px;
            border-radius: var(--border-radius-md);
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .plan-apply:hover {
            transform: translateY(-1px);
        }

        .plan-presets {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
            padding-left: 10px;
            border-left: 1px solid var(--primary-lighter);
        }

        .plan-period-chip {
            padding: 4px 11px;
            border: 1px solid var(--primary-lighter);
            border-radius: 999px;
            background: #fff;
            color: var(--primary-dark);
            font-size: 0.76rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .plan-period-chip:hover {
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .plan-period-chip.is-active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 3px 10px rgba(var(--primary-rgb), 0.24);
        }



        /*
        | Dải số liệu của kỳ - thay cho 4 ô tổng quan cũ. Cùng chừng ấy con số nhưng
        | nằm trên một hàng nhãn nhỏ, không chiếm nguyên một hàng thẻ cao.
        */
        .plan-summary {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-left: auto;
        }

        .plan-stat {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 10px;
            border: 1px solid var(--primary-lighter);
            border-radius: 999px;
            background: #fff;
            font-size: 0.74rem;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
        }

        .plan-stat i {
            color: var(--primary);
        }

        .plan-stat b {
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        /* Còn việc phải làm trong kỳ - phải nhận ra ngay giữa dải nhãn */
        .plan-stat.is-todo {
            border-color: #FCA5A5;
            background: #FEF2F2;
            color: #B91C1C;
        }

        .plan-stat.is-todo i,
        .plan-stat.is-todo b {
            color: #DC2626;
        }



        /* ---------- Dải thời gian theo tháng ---------- */
        .plan-months {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
        }

        .plan-month {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border: 1px solid #E2E8F0;
            border-radius: 999px;
            background: #fff;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .plan-month:hover {
            border-color: var(--primary-light);
            transform: translateY(-1px);
        }

        .plan-month.is-active {
            border-color: var(--primary);
            background: var(--primary-soft);
        }

        .plan-month-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* Tháng hiện tại - luôn nhận ra được kể cả khi đang lọc tháng khác */
        .plan-month.is-current .plan-month-label {
            color: var(--primary);
        }

        .plan-month.is-current .plan-month-label::after {
            content: ' •';
            color: var(--accent);
        }

        .plan-month-total {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        /* Tháng không có mốc nào - làm nhạt để nhìn ra quãng trống của kế hoạch */
        .plan-month.is-empty .plan-month-total {
            color: #CBD5E1;
        }

        .plan-month-dots {
            display: inline-flex;
            gap: 3px;
        }

        .plan-month-dot {
            padding: 0 6px;
            border-radius: 999px;
            font-size: 0.66rem;
            font-weight: 700;
            line-height: 15px;
        }

        .plan-month-dot.overdue {
            background: #FEE2E2;
            color: #B91C1C;
        }

        .plan-month-dot.due {
            background: #FEF3C7;
            color: #92400E;
        }

        .plan-month-dot.done {
            background: #DCFCE7;
            color: #166534;
        }

        /* Ghi chú cách đọc bảng - thu về một nút tròn, rê chuột mới hiện đủ chữ */
        .plan-help {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border: 1px solid var(--primary-lighter);
            border-radius: 50%;
            background: #fff;
            color: var(--primary);
            font-size: 0.7rem;
            cursor: help;
        }

        /*
        | Bộ lọc tình trạng + dải tháng + ghi chú được layout kéo vào thanh .md-tablebar
        | chung với "Hiển thị N dòng" / "Tìm kiếm", nên bỏ hết khoảng cách rời rạc.
        */
        .md-tablebar .ssa-filters,
        .md-tablebar .plan-months {
            margin: 0;
            flex-wrap: wrap;
        }

        .md-tablebar .ssa-chip {
            padding: 3px 11px;
            font-size: 0.78rem;
        }





        /* Hạn dùng của ống chuẩn đến trước ngày phải đánh giá - mốc đó gần như bỏ đi */
        .plan-expired-warn {
            display: inline-block;
            margin-top: 3px;
            padding: 1px 8px;
            border-radius: 999px;
            background: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #B91C1C;
            font-size: 0.68rem;
            font-weight: 700;
            white-space: nowrap;
        }

    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* Bấm mốc nhanh là điền sẵn hai ô ngày rồi gửi luôn, không phải chọn tay */
            $(document).on('click', '.plan-period-chip', function() {
                var $form = $(this).closest('form');

                $form.find('[name="plan_from"]').val($(this).data('from'));
                $form.find('[name="plan_to"]').val($(this).data('to'));
                $form.trigger('submit');
            });

            /*
            | Lọc bảng theo tháng: mỗi dòng mang sẵn data-month, bấm tháng nào thì chỉ giữ
            | lại dòng của tháng đó. Bấm lại đúng tháng đang chọn là bỏ lọc.
            |
            | Bộ lọc này đánh dấu bằng planMonth để KHÔNG bị bộ lọc tình trạng (.ssa-chip)
            | gỡ mất - hai bộ lọc chạy song song, lọc tháng xong vẫn lọc tiếp tình trạng.
            */
            $(document).on('click', '.plan-month', function() {
                var $month = $(this);
                var selector = $month.closest('.plan-months').data('table');

                if (!selector || !$.fn.dataTable.isDataTable(selector)) return;

                var off = $month.hasClass('is-active');
                var key = $month.data('month');

                $month.closest('.plan-months').find('.plan-month').removeClass('is-active');

                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                    return fn.planMonth !== selector;
                });

                if (!off) {
                    $month.addClass('is-active');

                    var filter = function(settings, data, index) {
                        if (settings.nTable !== $(selector)[0]) return true;

                        return $(settings.aoData[index].nTr).data('month') === key;
                    };

                    filter.planMonth = selector;

                    $.fn.dataTable.ext.search.push(filter);
                }

                $(selector).DataTable().draw();
            });
        });
    </script>

@endonce
