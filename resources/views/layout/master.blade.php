<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>WMS - Hệ Thống Quản Lý Kho</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">

    @include('layout.css')
    <style>
        /* NOTIFICATION DRAWER CSS */
        #notification-drawer {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100%;
            background: #fff;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        #notification-drawer.open {
            right: 0;
        }

        #notification-drawer-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #notification-drawer-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .notif-tabs {
            display: flex;
            padding: 0 20px;
            border-bottom: 1px solid #eee;
        }

        .notif-tab {
            padding: 10px 15px;
            cursor: pointer;
            color: #666;
            border-bottom: 2px solid transparent;
        }

        .notif-tab.active {
            color: #28a745;
            border-bottom-color: #28a745;
            font-weight: bold;
        }

        #notification-drawer-items {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }

        .notif-date-group {
            padding: 10px 20px;
            background: #f8f9fa;
            font-size: 12px;
            font-weight: bold;
            color: #888;
            text-transform: uppercase;
        }

        .notif-item {
            padding: 15px 20px;
            display: flex;
            gap: 15px;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }

        .notif-item:hover {
            background: #f0f7f2;
        }

        .notif-item.unread {
            background: #f3fcf5;
        }

        .notif-content {
            flex: 1;
        }

        .notif-title {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .notif-title b {
            color: #333;
        }

        .notif-message {
            font-size: 13px;
            color: #666;
            border-left: 3px solid #ddd;
            padding-left: 10px;
            margin: 5px 0;
        }

        .notif-time {
            font-size: 11px;
            color: #999;
        }

        .unread-indicator {
            width: 10px;
            height: 10px;
            background: #007bff;
            border-radius: 50%;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        #notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 9998;
            display: none;
        }


        /* FLOATING BELL BUTTON - Now integrated into Header */
        #notif-bell-btn {
            width: 40px;
            height: 40px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
            position: relative;
            margin: 0 10px;
        }

        #notif-bell-btn:hover {
            transform: scale(1.1);
            background: #f8f9fa;
        }

        #notif-bell-btn .badge {
            position: absolute;
            top: -2px;
            right: -2px;
            padding: 3px 5px;
            font-size: 10px;
        }
    </style>

</head>

<body class="hold-transition sidebar-mini layout-navbar-fixed layout-fixed @yield('bodyClass')">

    <!-- General wrapper -->
    <div class="wrapper">

        @include('layout.topNAV')
        @include('layout.leftNAV')

        @yield('mainContent')

        <!-- NOTIFICATION CENTER -->
        <div id="notification-overlay"></div>
        <div id="notification-drawer">
            <div id="notification-drawer-header">
                <h3>Thông báo</h3>
                <button type="button" class="btn btn-sm btn-light" id="close-notif-drawer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="notif-tabs">
                <div class="notif-tab active" data-tab="all">Tất cả</div>
                <div class="notif-tab" data-tab="unread">Chưa đọc</div>
            </div>
            <div id="notification-drawer-items">
                <!-- Items will be loaded here -->
            </div>
            <div class="p-3 text-center border-top">
                <a href="#" style="color: #666; font-size: 13px;">Xem thêm thông báo cũ hơn</a>
            </div>
        </div>

        @yield('model')

        @yield('script')




    </div>
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    @include('layout.js')
    <!-- page script -->
    <!-- page script -->
    <script>
        $(function() {
            $("#example1").DataTable({
                "responsive": true,
                "autoWidth": false,
            });
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
            });
        });
    </script>

    {{--
    |--------------------------------------------------------------------------
    | Gom các nút phía trên bảng về một hàng - áp dụng cho MỌI màn hình
    |--------------------------------------------------------------------------
    | Trước đây phía trên mỗi bảng có nhiều hàng riêng: nút thêm mới, ghi chú,
    | bộ lọc, rồi hàng "Hiển thị N dòng / Tìm kiếm" do DataTables tự dựng. Dồn
    | hết về một thanh .md-tablebar đặt ngay trên bảng để lấy lại chiều cao cho
    | phần dữ liệu.
    |
    | Đặt ở layout để màn hình nào có bảng cũng được, không phải sửa từng file:
    | các màn hình mới (dùng .md-toolbar + .table-responsive) và cả các màn hình
    | cũ (nút Thêm mới đứng trơ ngay trước bảng) đều gom được.
    |
    | Kiểu dáng của .md-tablebar khai trong layout/css.blade.php.
    --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Hoãn một nhịp để đợi mọi bảng DataTables dựng xong
            setTimeout(function() {
                if (!$.fn.dataTable) return;

                $($.fn.dataTable.tables()).each(function() {
                    var $table = $(this);
                    var $wrapper = $table.closest('.dataTables_wrapper');

                    if (!$wrapper.length) return;

                    var $responsive = $wrapper.closest('.table-responsive');
                    var $anchor = $responsive.length ? $responsive : $wrapper;
                    var $container = $anchor.parent();

                    var $bar = $anchor.prev('.md-tablebar');
                    if (!$bar.length) {
                        $bar = $('<div class="md-tablebar"></div>').insertBefore($anchor);
                    }

                    // 1. Quét mã vạch
                    var $bcs = $anchor.prevAll('.bcs-box').first();
                    if (!$bcs.length) $bcs = $container.find('.bcs-box').not($bar.find('.bcs-box')).first();
                    if ($bcs.length && !$bcs.closest('.md-tablebar').length) {
                        $bar.append($bcs);
                    }

                    // 2. Nút thao tác trong .md-toolbar
                    var $toolbar = $anchor.prevAll('.md-toolbar').first();
                    if ($toolbar.length) {
                        $bar.append($toolbar.children('button, .btn'));
                    } else {
                        var $oldBtns = $($anchor.prevAll('button.btn, a.btn').not('.bcs-box *').get().reverse());
                        if ($oldBtns.length) $bar.append($oldBtns);
                    }

                    // 3. Bộ lọc phân loại / phân nhóm
                    var $cls = $anchor.prevAll('.cls-filter').first();
                    if (!$cls.length) $cls = $container.find('.cls-filter').not($bar.find('.cls-filter')).first();
                    if ($cls.length && !$cls.closest('.md-tablebar').length) {
                        $bar.append($cls);
                    }

                    var $sgr = $anchor.prevAll('.sgr-filter').first();
                    if (!$sgr.length) $sgr = $container.find('.sgr-filter').not($bar.find('.sgr-filter')).first();
                    if ($sgr.length && !$sgr.closest('.md-tablebar').length) {
                        $bar.append($sgr);
                    }

                    /*
                    | 3b. Phần tử do chính view khai báo muốn nằm chung thanh công cụ
                    |
                    | View chỉ cần gắn thêm class .md-tablebar-item cho khối muốn gom
                    | (bộ lọc riêng, dải thời gian, ghi chú...) là nó tự về hàng này,
                    | không phải thêm tên class mới vào file layout mỗi lần.
                    */
                    $($anchor.prevAll('.md-tablebar-item').get().reverse()).each(function() {
                        if (!$(this).closest('.md-tablebar').length) $bar.append(this);
                    });

                    if ($toolbar.length) {
                        $bar.append($toolbar.children('.hint').not('.inv-blocking'));
                        if (!$toolbar.children().length) $toolbar.remove();
                    }

                    // 4. Hiển thị & Tìm kiếm của DataTables
                    var $length = $wrapper.find('.dataTables_length');
                    var $filter = $wrapper.find('.dataTables_filter');
                    var $topRow = $length.closest('.row').add($filter.closest('.row'));

                    if ($length.length) $bar.append($length);
                    if ($filter.length) $bar.append($filter);

                    if ($topRow.length) $topRow.remove();

                    // Dọn dẹp các hàng row rỗng trước bảng
                    $anchor.prevAll('.row').each(function() {
                        if (!$.trim($(this).text())) $(this).remove();
                    });

                    if (!$bar.children().length) $bar.remove();
                });
            }, 50);
        });
    </script>

    {{--
    |--------------------------------------------------------------------------
    | Chiều rộng cột bảng dữ liệu - chia theo lượng dữ liệu của từng cột
    |--------------------------------------------------------------------------
    | Bảng nào cũng có cột chỉ chứa số / ngày / trạng thái / nút bấm nằm cạnh cột
    | chứa tên hàng dài. Trình duyệt không biết điều đó nên chia gần như đều nhau:
    | cột tên vật tư bị bóp xuống 5-6 dòng trong khi cột "Loại Bỏ" chỉ có một chữ
    | số vẫn chiếm cả trăm pixel.
    |
    | Đoạn dưới đo độ dài dữ liệu THẬT của từng cột - lấy toàn bộ dòng của bảng
    | qua API DataTables chứ không chỉ trang đang xem - rồi gắn class:
    |
    |   - Ô dài nhất <= FIT_MAX ký tự -> .md-col-fit  : cột co sát nội dung
    |   - Còn lại                     -> .md-col-flex : cột nhận phần rộng dư
    |
    | Kiểu dáng của hai class khai trong layout/css.blade.php.
    |
    | Đặt ở layout nên mọi màn hình có bảng đều được, kể cả bảng dựng sau bằng
    | AJAX (bắt qua sự kiện init.dt), không phải sửa từng file dataTable.blade.php.
    | Bảng nào muốn tự giữ chiều rộng riêng thì thêm thuộc tính data-no-fit vào
    | thẻ <table>.
    --}}
    <script>
        (function () {

            // Cột có ô dài nhất không quá bấy nhiêu ký tự thì coi là cột dữ liệu ngắn
            var FIT_MAX = 18;

            // Số dòng tối đa đem đo cho mỗi cột, để bảng vài nghìn dòng vẫn nhẹ
            var SAMPLE_MAX = 500;

            /*
            | Lấy phần chữ của một ô. Dữ liệu DataTables trả về có thể là chuỗi HTML
            | (bảng đọc từ DOM), là node, hoặc object dạng { display: ..., filter: ... }
            */
            function cellText(v) {
                if (v === null || v === undefined) return '';

                if (typeof v === 'object') {
                    if (v.nodeType) return $.trim($(v).text().replace(/\s+/g, ' '));
                    v = v.display !== undefined ? v.display : (v.filter !== undefined ? v.filter : '');
                }

                return $.trim(String(v)
                    .replace(/<[^>]*>/g, ' ')
                    .replace(/&nbsp;/gi, ' ')
                    .replace(/&[a-z]+;/gi, ' ')
                    .replace(/\s+/g, ' '));
            }

            /*
            | Ô chứa input / select / ảnh / thanh tiến độ thì bề rộng do chính phần tử
            | đó quyết định chứ không theo số ký tự, đo chữ sẽ ra gần bằng 0 và cột bị
            | ép sát lại. Những cột như vậy luôn xếp vào loại co giãn.
            | Input ẩn của các form Khoá / Duyệt không tính vì không chiếm chỗ.
            */
            var STRETCHY_TAG = /<(select|textarea|img|canvas|svg|progress)\b/i;
            var PROGRESS_BOX = /class="[^"]*\bprogress\b/i;
            var INPUT_TAG = /<input\b[^>]*>/gi;
            var HIDDEN_INPUT = /type\s*=\s*["']?hidden/i;

            function hasStretchy(v) {
                if (typeof v !== 'string') return false;
                if (STRETCHY_TAG.test(v) || PROGRESS_BOX.test(v)) return true;

                var tags = v.match(INPUT_TAG);
                if (!tags) return false;

                for (var i = 0; i < tags.length; i++) {
                    if (!HIDDEN_INPUT.test(tags[i])) return true;
                }

                return false;
            }

            function fitOneTable(table) {
                var $table = $(table);

                if ($table.is('[data-no-fit]')) return;
                if ($table.data('mdFitApplied')) return;

                // Tiêu đề nhiều tầng hoặc có ô gộp: để nguyên cho chắc
                var $headRows = $table.children('thead').children('tr');
                if ($headRows.length !== 1) return;

                var $heads = $headRows.children('th, td');
                if ($heads.length < 3) return;

                // Chỉ bỏ qua khi có ô gộp thật. DataTables tự gắn colspan="1" cho
                // MỌI ô tiêu đề nên không được kiểm tra bằng sự tồn tại của colspan.
                var merged = $heads.filter(function () {
                    return parseInt($(this).attr('colspan') || 1, 10) > 1;
                }).length;
                if (merged) return;

                var api;
                try {
                    api = new $.fn.dataTable.Api(table);
                } catch (e) {
                    return;
                }

                /*
                | Số cột của DataTables phải khớp đúng số ô tiêu đề trong DOM thì việc
                | ánh xạ cột <-> ô tiêu đề mới đúng.
                |
                | Không dùng columns(':visible'): selector đó thực chất chạy bộ lọc
                | :visible của jQuery trên các ô tiêu đề, nên bảng nằm trong tab đang
                | ẩn sẽ ra 0 cột và bị bỏ qua oan.
                */
                var idx = api.columns().indexes().toArray();
                if (idx.length !== $heads.length) return;

                var lens = [];
                var hasLong = false;

                for (var i = 0; i < idx.length; i++) {
                    var data = api.column(idx[i]).data().toArray();
                    var step = data.length > SAMPLE_MAX ? Math.ceil(data.length / SAMPLE_MAX) : 1;
                    var max = 0;

                    for (var r = 0; r < data.length; r += step) {
                        if (hasStretchy(data[r])) {
                            max = Infinity;
                            break;
                        }

                        var len = cellText(data[r]).length;
                        if (len > max) max = len;
                    }

                    lens.push(max);
                    if (max > FIT_MAX) hasLong = true;
                }

                // Cả bảng toàn cột ngắn: không có cột nào để nhận chỗ dư, để nguyên
                if (!hasLong) return;

                var fitCols = [];
                var flexCols = [];

                $heads.each(function (i) {
                    if (lens[i] > FIT_MAX) {
                        flexCols.push(idx[i]);
                        $(this).addClass('md-col-flex');
                        return;
                    }

                    fitCols.push(idx[i]);

                    /*
                    | Bỏ chiều rộng đặt cứng trong blade (style="width:90px"...):
                    | chính nó giữ cột số rộng hơn mức dữ liệu cần.
                    */
                    $(this).removeAttr('width').css('width', '').addClass('md-col-fit');
                });

                function paint() {
                    fitCols.forEach(function (c) {
                        api.column(c).nodes().to$().addClass('md-col-fit');
                    });
                    flexCols.forEach(function (c) {
                        api.column(c).nodes().to$().addClass('md-col-flex');
                    });
                }

                paint();

                // Sang trang / tìm kiếm / sắp xếp: gắn lại cho các dòng vừa dựng
                $table.on('draw.dt', paint);

                // Màn hình nào huỷ bảng rồi dựng lại thì đo lại từ đầu
                $table.one('destroy.dt', function () {
                    $table.off('draw.dt', paint).removeData('mdFitApplied');
                    $table.children('thead, tbody').children('tr').children('th, td')
                        .removeClass('md-col-fit md-col-flex');
                });

                $table.data('mdFitApplied', true);
            }

            window.mdFitColumns = function (root) {
                if (!$.fn.dataTable) return;

                $(root || document).find('table.dataTable').addBack('table.dataTable').each(function () {
                    // Một bảng lạ không được làm hỏng các bảng còn lại của trang
                    try {
                        fitOneTable(this);
                    } catch (e) {
                        if (window.console) console.warn('mdFitColumns:', e);
                    }
                });
            };

            var pending = null;

            function schedule() {
                clearTimeout(pending);
                pending = setTimeout(function () {
                    window.mdFitColumns();
                }, 60);
            }

            // Bảng dựng sau (tab nạp bằng AJAX, modal...) cũng được tính
            $(document).on('init.dt', schedule);
            $(document).ready(schedule);
        })();
    </script>

    {{-- Tự động Logout khi không sử dụng sau 1 tiếng --}}
    <script>
        (function() {
            // Lấy thời gian sống của session từ server (phút) chuyển sang mili giây
            // Mặc định là 60 phút nếu không lấy được
            const sessionLifetime = {{ config('session.lifetime') }} * 60 * 1000;
            let timeout;

            function resetTimer() {
                clearTimeout(timeout);
                timeout = setTimeout(logout, sessionLifetime);
            }

            function logout() {
                // Chuyển hướng người dùng về trang login kèm tham số báo hết hạn
                window.location.replace("{{ route('login') }}?timeout=true");
            }

            // Các sự kiện được coi là người dùng đang hoạt động
            const events = ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'];

            events.forEach(function(event) {
                window.addEventListener(event, resetTimer);
            });

            // Khởi tạo lần đầu
            resetTimer();

            // Xử lý lỗi AJAX toàn cục (419 - Page Expired)
            $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
                if (jqXHR.status === 419 || jqXHR.status === 401) {
                    window.location.href = "{{ route('login') }}";
                }
            });

            // --- HỆ THỐNG THÔNG BÁO MỚI ---
            function toggleDrawer(show) {
                if (show) {
                    $('#notification-drawer').addClass('open');
                    $('#notification-overlay').fadeIn();
                } else {
                    $('#notification-drawer').removeClass('open');
                    $('#notification-overlay').fadeOut();
                }
            }

            $('#notif-bell-btn, #close-notif-drawer, #notification-overlay').on('click', function() {
                toggleDrawer($('#notification-drawer').hasClass('open') ? false : true);
            });

            function loadNotifications() {
                $.get("{{ route('notifications.list') }}", function(data) {
                    let unreadCount = data.filter(n => n.is_read == 0).length;
                    if (unreadCount > 0) {
                        $('#notif-badge-navbar').text(unreadCount).show();
                    } else {
                        $('#notif-badge-navbar').hide();
                    }

                    let html = '';
                    if (data.length === 0) {
                        html = '<div class="text-center p-5 text-muted">Không có thông báo mới</div>';
                    } else {
                        // Nhóm theo ngày
                        let groups = {};
                        data.forEach(n => {
                            let dateLabel = moment(n.created_at).calendar(null, {
                                sameDay: '[Hôm nay]',
                                lastDay: '[Hôm qua]',
                                lastWeek: 'DD/MM/YYYY',
                                sameElse: 'DD/MM/YYYY'
                            });
                            if (!groups[dateLabel]) groups[dateLabel] = [];
                            groups[dateLabel].push(n);
                        });

                        for (let date in groups) {
                            html += `<div class="notif-date-group">${date}</div>`;
                            groups[date].forEach(n => {
                                let isUnread = n.is_read == 0 ? 'unread' : '';
                                html += `
                                    <div class="notif-item ${isUnread}" onclick="markNotificationRead(${n.id}, '${n.url}')">
                                        <div class="notif-content">
                                            <div class="notif-title"><b>${n.sender_name}</b> đã ${n.activity_type}</div>
                                            <div class="notif-message">${n.message}</div>
                                            <div class="notif-time">${moment(n.created_at).format('HH:mm DD/MM/YYYY')}</div>
                                        </div>
                                        ${n.is_read == 0 ? '<div class="unread-indicator"></div>' : ''}
                                    </div>
                                `;
                            });
                        }
                    }
                    $('#notification-drawer-items').html(html);
                    $('#notification-items').html(html); // Dự phòng cho các mẫu cũ
                });
            }

            window.markNotificationRead = function(id, targetUrl) {
                $.post("{{ route('notifications.markAsRead') }}", {
                    _token: "{{ csrf_token() }}",
                    notification_id: id
                }, function() {
                    loadNotifications();

                    // ĐIỀU HƯỚNG ĐỘNG TỪ DATABASE
                    if (targetUrl && targetUrl !== 'null' && targetUrl !== 'undefined') {
                        window.location.href = targetUrl;
                    }
                });
            };

            loadNotifications();
            setInterval(loadNotifications, 60000);
        })();
    </script>
</body>

</html>
