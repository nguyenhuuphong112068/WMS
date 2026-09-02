{{--
|--------------------------------------------------------------------------
| ĐÁNH GIÁ HẠN DÙNG - CHẤT CHUẨN - CSS + JS dùng chung
|--------------------------------------------------------------------------
| Được @include vào dataTable (màn hình danh sách) và detail (màn hình chi tiết một
| phiếu) để hai trang cùng một giao diện, không lặp CSS ở hai nơi.
|
| Kéo theo pages.materData.shared.assets để có sẵn: khung .md-page / .md-card, bảng
| DataTables tiếng Việt, modal .md-modal, nút .btn-md-create / .btn-md-edit, form hỏi
| xác nhận .form-md-confirm và thông báo SweetAlert của session('success') / ('error').
|
| Quy ước riêng của màn hình này:
| - Ô chọn có tìm kiếm : class="ssa-select"
| - Nút mở modal ghi kết quả : class="btn-ssa-assess" kèm data-row='{...}'
| - Nút mở modal sửa mốc     : class="btn-md-edit" data-modal="#itemUpdateModal"
--}}

@include('pages.materData.shared.assets')

@once

    <style>
        /* ---------- Mã ống chuẩn ---------- */
        .ssa-code {
            font-weight: 700;
            color: var(--primary-dark);
            letter-spacing: 0.4px;
        }

        /* Mã nhóm chuẩn nằm giữa mã ống chuẩn (PRS, VKN, IMP...) */
        .ssa-group-tag {
            display: inline-block;
            background: #EDE9FE;
            color: #5B21B6;
            border: 1px solid #C4B5FD;
            border-radius: 999px;
            padding: 1px 9px;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        /* ---------- Trạng thái phiếu ---------- */
        .ssa-badge {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ssa-badge.initial {
            background: var(--primary-soft);
            color: var(--primary-dark);
            border: 1px solid var(--primary-lighter);
        }

        .ssa-badge.running {
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #FCD34D;
        }

        .ssa-badge.done {
            background: #DCFCE7;
            color: #166534;
            border: 1px solid #86EFAC;
        }

        .ssa-badge.cancelled {
            background: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
        }

        /* Ngưng đánh giá - đã làm được một phần rồi mới dừng, không đỏ như Huỷ */
        .ssa-badge.stopped {
            background: #FFEDD5;
            color: #9A3412;
            border: 1px solid #FDBA74;
        }

        /* ---------- Tình trạng một mốc đánh giá ---------- */
        .ssa-state {
            display: inline-block;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ssa-state-done {
            background: #DCFCE7;
            color: #166534;
            border: 1px solid #86EFAC;
        }

        .ssa-state-overdue {
            background: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
        }

        .ssa-state-due {
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #FCD34D;
        }

        .ssa-state-waiting {
            background: #F1F5F9;
            color: #475569;
            border: 1px solid #CBD5E1;
        }

        /* Mốc của phiếu đã ngưng - sẽ không được thực hiện nữa */
        .ssa-state-stopped {
            background: #FFEDD5;
            color: #9A3412;
            border: 1px solid #FDBA74;
        }

        /* Kết luận Đạt / Không Đạt của một mốc */
        .ssa-result-pass {
            color: #166534;
            font-weight: 700;
        }

        .ssa-result-fail {
            color: #B91C1C;
            font-weight: 700;
        }

        /* ---------- Mốc thời gian T0 / T3 / T6 ---------- */
        .ssa-timepoint {
            display: inline-block;
            min-width: 46px;
            text-align: center;
            background: var(--primary);
            color: #fff;
            border-radius: var(--border-radius-md);
            padding: 3px 10px;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        /* ---------- Chỉ tiêu thử nghiệm ---------- */
        .ssa-testing {
            display: inline-block;
            background: var(--primary-soft);
            color: var(--primary-dark);
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-md);
            padding: 1px 8px;
            margin: 1px 2px 1px 0;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Chỉ tiêu ĐÃ CẤP PHÁT CHUẨN - dấu tick nằm ngay trong nhãn chỉ tiêu */
        .ssa-testing.is-issued {
            background: #DCFCE7;
            color: #166534;
            border-color: #86EFAC;
        }

        .ssa-testing.is-issued::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 4px;
            font-size: 0.68rem;
        }

        /* ---------- Ngưng đánh giá ---------- */

        /* Khối hỏi "làm tiếp hay ngưng" trong modal ghi kết quả */
        .ssa-after {
            margin-top: 4px;
            padding: 12px 14px;
            border-radius: var(--border-radius-md);
        }

        .ssa-after-pass {
            background: var(--primary-soft);
            border: 1px solid var(--primary-lighter);
        }

        .ssa-after-fail {
            display: flex;
            gap: 10px;
            background: #FEF2F2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
            font-size: 0.88rem;
        }

        .ssa-after-fail i {
            margin-top: 2px;
            font-size: 1rem;
        }

        .ssa-after-title {
            margin-bottom: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .ssa-after-title i {
            margin-right: 5px;
        }

        .ssa-after-choice {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .ssa-radio {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 0;
            padding: 7px 11px;
            border: 1px solid transparent;
            border-radius: var(--border-radius-md);
            background: #fff;
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ssa-radio:hover {
            border-color: var(--primary-light);
        }

        .ssa-radio input {
            margin-top: 3px;
        }

        /* Lý do ngưng đánh giá hiển thị trên đầu trang chi tiết */
        .ssa-stop-banner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 16px;
            margin-bottom: 16px;
            border: 1px solid #FDBA74;
            border-radius: var(--border-radius-md);
            background: #FFF7ED;
            color: #9A3412;
            font-size: 0.88rem;
        }

        .ssa-stop-banner i {
            margin-top: 2px;
            font-size: 1.05rem;
        }

        .ssa-stop-banner .who {
            margin-top: 3px;
            font-size: 0.8rem;
            color: #B45309;
        }

        /* ---------- Modal cấp phát chuẩn theo chỉ tiêu ---------- */
        .ssa-issue-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 4px 0 8px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .ssa-issue-count {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary);
        }

        .ssa-issue-table thead th {
            background: var(--primary-soft);
            color: var(--primary-dark);
            border-color: var(--primary-lighter);
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ssa-issue-table td {
            vertical-align: middle;
        }

        .ssa-issue-name {
            font-weight: 600;
            color: var(--text-main);
        }

        .ssa-issue-check {
            width: 17px;
            height: 17px;
            cursor: pointer;
        }

        /* ---------- Tiến độ các mốc đã đánh giá ---------- */
        .ssa-progress {
            height: 6px;
            background: #E2E8F0;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 5px;
        }

        .ssa-progress span {
            display: block;
            height: 100%;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .ssa-progress.is-done span {
            background: #16A34A;
        }

        /* ---------- Khối thông tin đầu trang chi tiết ---------- */
        .ssa-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .ssa-info .box {
            background: #fff;
            border: 1px solid var(--primary-soft);
            border-radius: var(--border-radius-lg);
            padding: 14px 16px;
            box-shadow: var(--shadow-sm);
        }

        .ssa-info .box label {
            display: block;
            margin: 0 0 4px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .ssa-info .box .val {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-main);
        }

        /* ---------- Bộ lọc nhanh theo tình trạng ---------- */
        .ssa-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .ssa-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 999px;
            padding: 5px 14px;
            font-size: 0.83rem;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ssa-chip:hover {
            border-color: var(--primary-light);
            transform: translateY(-1px);
        }

        .ssa-chip.is-active {
            background: var(--primary-soft);
            border-color: var(--primary);
            color: var(--primary-dark);
        }

        .ssa-chip .count {
            background: var(--primary);
            color: #fff;
            border-radius: 999px;
            padding: 0 8px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        /*
        | KHỔ RỘNG CHO MODAL LẬP PHIẾU
        |
        | Bootstrap đi kèm dự án KHÔNG có sẵn .modal-xl, dùng class đó modal vẫn co về
        | 500px mặc định và bảng mốc đánh giá bị bóp ngang. Khai khổ rộng ở đây thay vì
        | viết style thẳng trên thẻ như mấy modal cũ.
        */
        .ssa-modal-wide {
            max-width: 1180px;
            width: 92vw;
        }

        @media (max-width: 991.98px) {
            .ssa-modal-wide {
                width: 96vw;
            }
        }

        /* ---------- Bảng mốc đánh giá khai ngay trên form lập phiếu ---------- */
        .ssa-items-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 4px 0 10px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .ssa-items-table thead th {
            background: var(--primary-soft);
            color: var(--primary-dark);
            border-color: var(--primary-lighter);
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ssa-items-table td {
            vertical-align: top;
        }

        .ssa-items-table .ssa-due-cell {
            font-weight: 600;
            color: var(--text-main);
            white-space: nowrap;
        }

        /* ---------- Nhật ký thay đổi của phiếu ---------- */
        .ssa-history-table thead th {
            background: var(--primary-soft);
            color: var(--primary-dark);
            border-color: var(--primary-lighter);
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ssa-history-table td {
            vertical-align: top;
        }

        .ssa-history-note {
            margin-top: 4px;
            color: var(--primary-dark);
            font-style: italic;
        }

        /* Ô chỉ đọc trong modal - cùng cách hiển thị với các màn hình Tồn khác */
        .ssa-readonly {
            background: #F8FAFC !important;
            color: #64748B;
            font-weight: 600;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ---------- Ô chọn có tìm kiếm ---------- */
            $('.ssa-select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) return;

                var $modal = $(this).closest('.md-modal');
                var multiple = this.multiple;

                $(this).select2({
                    theme: 'bootstrap4',
                    dropdownParent: $modal.length ? $modal : $(document.body),
                    width: '100%',
                    placeholder: multiple ? '-- Chọn chỉ tiêu --' : '-- Chọn --',
                    // Ô chọn nhiều thì giữ danh sách mở để tick liên tiếp nhiều chỉ tiêu
                    closeOnSelect: !multiple,
                    language: {
                        noResults: function() {
                            return 'Không tìm thấy dữ liệu phù hợp';
                        }
                    }
                });
            });

            /*
            | Lọc nhanh theo tình trạng: mỗi dòng của bảng mang sẵn data-state, bấm chip
            | nào thì đưa đúng giá trị đó vào ô tìm kiếm ẩn của DataTables.
            */
            $(document).on('click', '.ssa-chip', function() {
                var $chip = $(this);
                var $wrap = $chip.closest('.ssa-filters');
                var state = $chip.data('state');
                var selector = $wrap.data('table');

                if (!selector || !$.fn.dataTable.isDataTable(selector)) return;

                $wrap.find('.ssa-chip').removeClass('is-active');
                $chip.addClass('is-active');

                var table = $(selector).DataTable();

                // Bộ lọc cũ của bảng này được gỡ ra trước để hai chip không cộng dồn điều kiện
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                    return fn.ssaTable !== selector;
                });

                if (state && state !== 'all') {
                    var filter = function(settings, data, index) {
                        if (settings.nTable !== $(selector)[0]) return true;

                        return $(settings.aoData[index].nTr).data('state') === state;
                    };

                    filter.ssaTable = selector;

                    $.fn.dataTable.ext.search.push(filter);
                }

                table.draw();
            });
        });
    </script>

@endonce
