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
    <link rel="stylesheet" href="{{ asset('css/tribute.css') }}">
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

        .msg-text {
            word-wrap: break-word;
            word-break: break-word;
            font-size: 1.35rem;
            /* Tăng 1.5 lần */
            line-height: 1.4;
        }

        /* --- CHAT CSS --- */
        .chat-sidebar {
            position: fixed;
            right: -320px;
            top: 0;
            width: 320px;
            height: 100%;
            background: #fff;
            box-shadow: -2px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1051;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .chat-sidebar.active {
            right: 0;
        }

        .chat-header {
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-list {
            flex: 1;
            overflow-y: auto;
        }

        .chat-tab-item {
            padding: 10px 15px;
            cursor: pointer;
            color: #666;
            border-bottom: 2px solid transparent;
        }

        .chat-tab-item.active {
            color: #28a745;
            border-bottom: 2px solid #28a745;
            font-weight: bold;
        }

        .chat-group-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f1f1;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background 0.2s;
        }

        .chat-group-item:hover {
            background: #f8f9fa;
        }

        .user-initials {
            width: 40px;
            height: 40px;
            background: var(--primary-navy);
            /* Màu Navy chủ đạo */
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
            font-size: 16px;
        }

        .chat-group-info {
            flex: 1;
            min-width: 0;
        }

        .chat-group-name {
            font-weight: 600;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #28a745;
            /* Màu tên người dùng */
        }

        .chat-group-last-msg {
            font-size: 12px;
            color: #777;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            display: inline-block;
            margin-left: 5px;
        }

        .chat-trigger {
            position: relative;
            width: 38px;
            height: 38px;
            background: transparent;
            border: 2px solid var(--accent-gold);
            color: var(--accent-gold);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.2s;
        }

        .chat-trigger:hover {
            transform: scale(1.1);
            border-color: #e5df18;
            color: #e5df18;
        }

        .chat-trigger .unread-badge-total {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 1px 5px;
            font-size: 10px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1001;
        }

        .online-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
            box-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
        }

        /* Floating Windows */
        .chat-window-container {
            position: fixed;
            bottom: 0;
            right: 60px;
            /* Offset from sidebar trigger if any */
            display: flex;
            flex-direction: row-reverse;
            align-items: flex-end;
            z-index: 1050;
            pointer-events: none;
        }

        .chat-window {
            width: 450px;
            /* Tăng chiều rộng để phù hợp chữ to */
            height: 550px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-radius: 8px 8px 0 0;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1050;
            margin-left: 10px;
            pointer-events: auto;
        }

        .chat-resizer {
            width: 15px;
            height: 15px;
            position: absolute;
            top: -2px;
            left: -2px;
            cursor: nw-resize;
            z-index: 10;
            background: transparent;
        }

        .chat-window.maximized {
            position: fixed;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            border-radius: 0;
            z-index: 2000;
        }

        .chat-window.maximized .chat-window-content,
        .chat-window.maximized .chat-window-footer {
            padding: 10px 5px;
        }

        .chat-window.maximized .msg-text {
            font-size: 16px;
        }

        .chat-window.maximized .chat-input {
            font-size: 16px;
            padding: 10px;
        }

        body.chat-maximized {
            overflow: hidden;
        }

        .chat-window-header {
            padding: 8px 12px;
            background: #28a745;
            /* Xanh lá */
            color: white;
            border-radius: 7px 7px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .chat-window-content {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            background: #ffffff;
            /* Trắng tinh khiết cho thoáng */
            display: flex;
            flex-direction: column;
        }

        .chat-window-footer {
            padding: 8px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 8px 12px;
            font-size: 1.35rem;
            /* Tăng 1.5 lần */
            border-radius: 20px;
            background: #f0f2f5;
        }

        .msg-item {
            margin-bottom: 8px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
        }

        .msg-row {
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 100%;
            width: 100%;
            /* Đảm bảo hàng chiếm hết không gian nếu cần */
        }

        .msg-bubble {
            padding: 8px 12px;
            border-radius: 12px;
            position: relative;
            z-index: 1;
            background: #f5f5f5;
            color: #333;
            border-left: 3px solid #e0e0e0;
            max-width: calc(100% - 120px);
            /* Giới hạn chiều rộng để chừa chỗ cho Toolbar */
            word-break: break-word;
            /* Chống tràn chữ quá dài */
        }

        .msg-item.me .msg-bubble {
            background: #e8f5e9;
            color: #1b5e20;
            border-left: 3px solid #81c784;
            border-radius: 12px 0 12px 12px;
        }

        .msg-item.other .msg-bubble {
            border-radius: 0 12px 12px 12px;
        }

        .msg-sender {
            font-size: 11px;
            color: #28a745;
            /* Màu tên người gửi trong chat */
            font-weight: bold;
            margin-bottom: 4px;
        }

        .msg-status {
            font-size: 10px;
            color: #999;
            margin-top: 4px;
            display: flex;
            justify-content: flex-start;
            gap: 10px;
        }

        .msg-item.me .msg-status {
            justify-content: flex-start;
        }

        /* Chat Upgrades */
        .recalled-msg {
            font-style: italic;
            color: #999;
        }

        .reply-wrapper {
            background: rgba(0, 0, 0, 0.05);
            border-left: 3px solid #007bff;
            padding: 5px 8px;
            margin-bottom: 5px;
            border-radius: 4px;
            font-size: 0.85em;
            cursor: pointer;
        }

        .reply-sender {
            font-weight: bold;
            color: #28a745;
            display: block;
        }

        .reply-content {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        .msg-item .msg-actions {
            display: flex;
            /* Luôn là flex để giữ layout ổn định */
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            gap: 8px;
            background: transparent;
            padding: 0;
            box-shadow: none;
            position: static;
            transform: none;
            flex-shrink: 0;
            white-space: nowrap;
            transition: opacity 0.2s, visibility 0.2s;
        }

        .msg-row:hover .msg-actions,
        .msg-item:hover .msg-actions {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
        }

        .msg-action-btn {
            font-size: 11px;
            color: #666;
            cursor: pointer;
        }

        .msg-action-btn:hover {
            color: #28a745;
        }

        #reply-preview-container {
            padding: 5px 10px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: none;
            justify-content: space-between;
            align-items: center;
        }



        @keyframes chat-blink {
            0% {
                transform: scale(1);
                box-shadow: 0 0 5px rgba(205, 199, 23, 0.2);
            }

            50% {
                transform: scale(1.1);
                box-shadow: 0 0 15px rgba(205, 199, 23, 0.6);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 5px rgba(205, 199, 23, 0.2);
            }
        }

        .chat-trigger.blinking {
            animation: chat-blink 1s infinite;
        }

        .emoji-picker {
            position: absolute;
            bottom: 50px;
            right: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            /* Tăng số cột */
            gap: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1060;
            max-height: 200px;
            /* Thêm giới hạn chiều cao */
            overflow-y: auto;
            /* Thêm thanh cuộn */
            width: 280px;
        }

        .emoji-item {
            cursor: pointer;
            font-size: 18px;
            padding: 2px;
            text-align: center;
        }

        .emoji-item:hover {
            background: #f0f0f0;
        }

        .reaction-container {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            /* Không cho phép bị co lại */
        }

        .reaction-list {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
        }

        .reaction-item {
            background: #f0f0f0;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 11px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 2px;
            border: 1px solid transparent;
        }

        .reaction-item.me {
            background: #e8f5e9;
            border-color: #81c784;
        }

        .reaction-item:hover {
            background: #e0e0e0;
        }

        .btn-reaction {
            position: relative;
        }

        .reaction-picker-mini {
            position: absolute;
            bottom: calc(100% + 5px);
            left: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 5px 10px;
            display: flex;
            /* Mặc định là flex để tránh thay đổi layout khi hiện */
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1070;
            white-space: nowrap;
            transition: opacity 0.2s;
        }

        .btn-reaction:hover .reaction-picker-mini {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
        }

        /* Thêm vùng đệm bằng pseudo-element để tránh mất hover khi di chuyển chuột */
        .reaction-picker-mini::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            height: 15px;
            background: transparent;
        }

        .msg-actions:hover .reaction-picker-mini {
            /* display: flex;  -- Sẽ điều khiển bằng click hoặc hover tùy ý, ở đây dùng hover cho nhanh */
        }

        .reaction-emoji-btn {
            cursor: pointer;
            font-size: 18px;
            transition: transform 0.1s;
        }

        .reaction-emoji-btn:hover {
            transform: scale(1.3);
        }

        .reaction-emoji-btn:active {
            transform: scale(0.9);
        }

        /* Hiệu ứng nhấp nháy khi vừa thả cảm xúc để người dùng biết đã thành công */
        .reaction-item.just-added {
            animation: pulse-green 0.5s;
        }

        @keyframes pulse-green {
            0% {
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4);
            }

            100% {
                box-shadow: 0 0 0 10px rgba(76, 175, 80, 0);
            }
        }

        /* --- CHAT SEARCH CSS --- */
        .chat-search-container {
            padding: 8px 12px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chat-search-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 13px;
            outline: none;
        }

        .chat-search-input:focus {
            border-color: #28a745;
        }

        .search-highlight {
            background-color: #ffeb3b;
            color: #000;
            padding: 0 2px;
            border-radius: 2px;
            font-weight: bold;
        }

        .search-highlight.current-match {
            background-color: #ff9800;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
        }

        .chat-search-stats {
            font-size: 11px;
            color: #666;
            white-space: nowrap;
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

        <!-- CHAT CENTER -->
        <div id="chat-overlay"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); z-index:1050;"
            onclick="toggleChat(false)"></div>
        <div id="chat-sidebar" class="chat-sidebar">
            <div class="chat-header">
                <h5 class="mb-0">TIN NHẮN</h5>
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-outline-primary me-2" onclick="showCreateGroupModal()">
                        <i class="fas fa-plus"></i> Nhóm
                    </button>
                    <button class="btn btn-sm btn-light" onclick="toggleChat(false)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-2 border-bottom">
                <input type="text" class="form-control form-control-sm" placeholder="Tìm kiếm đồng nghiệp..."
                    id="chatSearch" onkeyup="searchChat()">
            </div>
            <div class="notif-tabs" style="border-top:none;">
                <div class="notif-tab" id="tab-conversations" onclick="switchChatTab('conv')">Hội thoại</div>
                <div class="notif-tab active" id="tab-contacts" onclick="switchChatTab('contacts')">Danh bạ</div>
            </div>
            <div class="chat-list d-none" id="chatList">
                <!-- Data will be loaded here -->
            </div>
            <div class="chat-list" id="contactList">
                <!-- Users will be loaded here -->
            </div>
        </div>

        <div id="chat-window-container" class="chat-window-container"></div>


        <!-- Modal Tạo Nhóm Chat -->
        <div class="modal fade" id="createGroupModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tạo nhóm chat mới</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên nhóm</label>
                            <input type="text" id="newGroupName" class="form-control" placeholder="Nhập tên nhóm...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phòng ban</label>
                            <div class="d-flex gap-2">
                                <select id="deptFilter" class="form-control" onchange="filterUsersByDepartment()">
                                    <option value="all">Tất cả phòng ban</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-success text-nowrap"
                                    onclick="selectAllInDept()">Chọn tất cả</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nhóm người dùng (UserGroup)</label>
                            <div class="d-flex gap-2">
                                <select id="userGroupFilter" class="form-control" onchange="filterUsersByUserGroup()">
                                    <option value="all">Tất cả nhóm</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-info text-nowrap"
                                    onclick="selectAllInUserGroup()">Chọn tất cả</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Chọn thành viên</label>
                            <div id="userListForGroup"
                                style="max-height: 250px; overflow-y: auto; border: 1px solid #eee; padding: 10px; border-radius: 5px;">
                                <!-- User list will be loaded here -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="submitCreateGroup()">Tạo
                            nhóm</button>
                    </div>
                </div>
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

            // --- HỆ THỐNG CHAT MỚI ---
            let openChatGroups = []; // Danh sách các nhóm đang mở cửa sổ chat
            let chatGroupLastTimes = {}; // Lưu trữ thời gian tin nhắn cuối cùng của từng nhóm
            // task.md
            // - [x] Tối ưu giao diện toàn màn hình, căn lề sát trái <!-- id: 51 -->
            // - [x] Sửa lỗi 404 khi mở file đính kèm <!-- id: 52 -->
            // - [x] Tinh chỉnh màu sắc chat nhẹ nhàng, dễ nhìn <!-- id: 54 -->
            // - [x] Sửa lỗi tự động cuộn khi đang xem tin nhắn cũ <!-- id: 55 -->
            // - [x] Kiểm tra và tối ưu hóa hiệu năng <!-- id: 32 -->
            let currentUserId = {{ session('user')['userId'] ?? 'null' }};



            let blinkInterval = null;
            let originalTitle = document.title;

            function blinkTitle(msg) {
                if (blinkInterval) return;
                blinkInterval = setInterval(() => {
                    document.title = document.title === originalTitle ? msg : originalTitle;
                }, 1000);
            }
            $(window).on('focus click keydown', function() {
                if (blinkInterval) {
                    clearInterval(blinkInterval);
                    blinkInterval = null;
                    document.title = originalTitle;
                }
            });

            window.toggleChat = function(show) {
                if (show) {
                    $('#chat-sidebar').addClass('active');
                    $('#chat-overlay').fadeIn();
                    // loadChatGroups();
                    // loadContacts();
                    switchChatTab('contacts');
                } else {
                    $('#chat-sidebar').removeClass('active');
                    $('#chat-overlay').fadeOut();
                }
            };

            window.switchChatTab = function(tab) {
                $('.chat-sidebar .notif-tab').removeClass('active');
                if (tab === 'conv') {
                    $('#tab-conversations').addClass('active');
                    $('#chatList').removeClass('d-none');
                    $('#contactList').addClass('d-none');
                } else {
                    $('#tab-contacts').addClass('active');
                    $('#chatList').addClass('d-none');
                    $('#contactList').removeClass('d-none');
                }
            };

            // function loadChatGroups() {
            //     $.get("{{ route('chat.groups') }}", function(data) {
            //         let html = '';
            //         let totalUnread = 0;

            //         data.forEach(g => {
            //             // Ẩn hội thoại với AI Agent nếu không phải Admin
            //             let userGroup = "{{ session('user')['userGroup'] ?? '' }}";
            //             if (g.type == 0 && g.display_name === 'AI Agent Search PMS' && userGroup !==
            //                 'Admin') {
            //                 return;
            //             }

            //             let unreadHtml = g.unread_count > 0 ?
            //                 `<span class="unread-badge">${g.unread_count}</span>` : '';
            //             let onlineHtml = g.is_online ?
            //                 `<span class="online-dot" title="Online"></span>` : '';
            //             totalUnread += g.unread_count;

            //             html += `
        //                 <div class="chat-group-item" onclick="openChatWindow(${g.id}, '${g.display_name}', ${g.is_online || false})">
        //                     <div class="chat-group-info">
        //                         <div class="chat-group-name">
        //                             ${onlineHtml}
        //                             <b>${g.display_name}</b>
        //                             ${unreadHtml}
        //                         </div>
        //                         <div class="chat-group-last-msg">${g.last_message || 'Chưa có tin nhắn'}</div>
        //                     </div>
        //                 </div>
        //             `;

            //             // Tự động mở cửa sổ chat nếu có tin nhắn mới và không phải mình gửi
            //             if (g.last_time) {
            //                 if (chatGroupLastTimes[g.id] && g.last_time > chatGroupLastTimes[g.id]) {
            //                     if (g.last_sender_id != currentUserId) {

            //                         blinkTitle("Có tin nhắn mới...");
            //                         if (!openChatGroups.includes(g.id)) {
            //                             openChatWindow(g.id, g.display_name, g.is_online || false);
            //                         }
            //                     }
            //                 }
            //                 chatGroupLastTimes[g.id] = g.last_time;
            //             }
            //         });

            //         // The original code had a block here to update lastChatCheckTime, which is now handled by chatGroupLastTimes
            //         // if (data.length > 0) {
            //         //     let maxTime = data.reduce((max, obj) => obj.last_time > max ? obj.last_time : max, lastChatCheckTime);
            //         //     lastChatCheckTime = maxTime;
            //         // }

            //         if (totalUnread > 0) {
            //             $('#unread-total-badge').text(totalUnread).removeClass('d-none');
            //             $('.chat-trigger').addClass('blinking');
            //         } else {
            //             $('#unread-total-badge').addClass('d-none');
            //             $('.chat-trigger').removeClass('blinking');
            //         }

            //         $('#chatList').html(html ||
            //             '<div class="text-center p-3 text-muted">Chưa có hội thoại nào</div>');
            //     });
            // }

            // function loadContacts() {
            //     $.get("{{ route('chat.users') }}", function(data) {
            //         let html = '';
            //         data.forEach(u => {
            //             let isAI = u.id == 9999;

            //             // Ẩn AI Agent nếu không phải Admin
            //             let userGroup = "{{ session('user')['userGroup'] ?? '' }}";
            //             if (isAI && userGroup !== 'Admin') {
            //                 return;
            //             }

            //             let badgeHtml = isAI ?
            //                 `<span class="badge badge-primary ml-1" style="font-size: 10px; background: #007bff;">AI BOT</span>` :
            //                 '';
            //             let onlineHtml = (u.is_online || isAI) ?
            //                 `<span class="online-dot" title="Online"></span>` : '';
            //             html += `
        //                 <div class="chat-group-item contact-item ${isAI ? 'ai-agent-item' : ''}" onclick="startDirectChat(${u.id}, '${u.fullName}', ${u.is_online || isAI})">
        //                     <div class="chat-group-info">
        //                         <div class="chat-group-name">
        //                             ${onlineHtml}
        //                             <b>${u.fullName}</b> ${badgeHtml}
        //                         </div>
        //                         <div class="chat-group-last-msg">@${u.userName}</div>
        //                     </div>
        //                 </div>
        //             `;
            //         });
            //         $('#contactList').html(html);
            //     });
            // }

            window.startDirectChat = function(userId, fullName, isOnline) {
                $.post("{{ route('chat.getDirectChat', [], false) }}", {
                    _token: "{{ csrf_token() }}",
                    target_user_id: userId
                }, function(group) {
                    openChatWindow(group.id, fullName, isOnline);
                });
            };

            window.searchChat = function() {
                let val = $('#chatSearch').val().toLowerCase();
                $('.chat-group-item').each(function() {
                    let text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(val) > -1);
                });
            };

            window.openChatWindow = function(groupId, groupName, isOnline) {
                if (openChatGroups.includes(groupId)) return;
                if (openChatGroups.length >= 3) {
                    let oldest = openChatGroups.shift();
                    $(`#chat-window-${oldest}`).remove();
                }

                let onlineHtml = isOnline ?
                    `<span class="online-dot me-1" title="Online" style="border: 1px solid white;"></span>` : '';

                openChatGroups.push(groupId);
                let html = `
                    <div class="chat-window" id="chat-window-${groupId}">
                        <div class="chat-resizer" onmousedown="initChatResize(event, ${groupId})"></div>
                        <div class="chat-window-header" onclick="handleChatHeaderClick(${groupId})" ondblclick="toggleChatWindowMax(${groupId})">
                            <span class="chat-window-title">
                                ${onlineHtml}
                                <b>${groupName}</b>
                            </span>
                            <div class="chat-window-actions">
                                <i class="fas fa-search me-2" title="Tìm kiếm tin nhắn" onclick="event.stopPropagation(); toggleChatSearch(${groupId})"></i>
                                <i class="fas fa-minus me-2"></i>
                                <i class="fas fa-times" onclick="closeChatWindow(event, ${groupId})"></i>
                            </div>
                        </div>
                        <div class="chat-search-container d-none" id="chat-search-container-${groupId}">
                            <input type="text" class="chat-search-input" id="chat-search-input-${groupId}" placeholder="Tìm tin nhắn..." onkeyup="if(event.key === 'Enter') executeChatSearch(${groupId})">
                            <div class="chat-search-stats d-none" id="chat-search-stats-${groupId}">0/0</div>
                            <div class="d-flex gap-1">
                                <i class="fas fa-chevron-up text-muted cursor-pointer" onclick="navigateChatSearch(${groupId}, -1)"></i>
                                <i class="fas fa-chevron-down text-muted cursor-pointer" onclick="navigateChatSearch(${groupId}, 1)"></i>
                                <i class="fas fa-times text-muted cursor-pointer ms-1" onclick="toggleChatSearch(${groupId})"></i>
                            </div>
                        </div>
                        <div class="chat-window-content" id="chat-content-${groupId}">
                            <!-- Messages -->
                        </div>
                        <div id="reply-preview-${groupId}" class="reply-preview" style="display:none;">
                            <i class="fas fa-times close-reply" onclick="cancelReply(${groupId})"></i>
                            <div class="reply-to-name"></div>
                            <div class="reply-to-text"></div>
                        </div>
                        <div class="chat-window-footer">
                            <label class="mb-0 me-2" style="cursor:pointer">
                                <i class="fas fa-paperclip text-muted"></i>
                                <input type="file" style="display:none" onchange="uploadFile(this, ${groupId})">
                            </label>
                            <input type="text" class="chat-input" placeholder="Nhập tin nhắn..." 
                                onkeypress="if(event.key === 'Enter') sendChatMessage(${groupId}, this)"
                                onpaste="handleChatPaste(event, ${groupId})">
                            <i class="far fa-smile ms-2 text-muted" style="cursor:pointer" onclick="toggleEmojiPicker(${groupId})"></i>
                            <button class="btn btn-sm text-success p-1 ms-1" onclick="sendChatMessage(${groupId}, $(this).siblings('.chat-input')[0])">
                                <i class="fas fa-paper-plane" style="font-size: 1.2rem;"></i>
                            </button>
                        </div>
                    </div>
                `;
                $('#chat-window-container').append(html);
                loadChatMessages(groupId, true);
                markChatAsRead(groupId);
                toggleChat(false);
                initTribute(groupId); // Khởi tạo tag @

                // Thêm sự kiện cuộn để tải thêm tin nhắn
                $(`#chat-content-${groupId}`).on('scroll', function() {
                    if ($(this).scrollTop() === 0) {
                        loadMoreMessages(groupId);
                    }
                });
            };

            window.markChatAsRead = function(groupId) {
                $.post("{{ route('chat.markAsRead', [], false) }}", {
                    _token: "{{ csrf_token() }}",
                    group_id: groupId
                }, function() {
                    loadChatGroups();
                });
            };

            window.closeChatWindow = function(event, groupId) {
                event.stopPropagation();
                openChatGroups = openChatGroups.filter(id => id !== groupId);
                $(`#chat-window-${groupId}`).remove();
            };

            let chatClickTimer = null;
            window.handleChatHeaderClick = function(groupId) {
                if (chatClickTimer) {
                    clearTimeout(chatClickTimer);
                    chatClickTimer = null;
                    return;
                }
                chatClickTimer = setTimeout(() => {
                    toggleChatWindowMin(groupId);
                    chatClickTimer = null;
                }, 250); // Đợi 250ms để xem có click thứ 2 không
            };

            window.toggleChatWindowMin = function(groupId) {
                let win = $(`#chat-window-${groupId}`);
                if (win.height() > 50) {
                    win.data('old-height', win.height());
                    win.css('height', '40px');
                } else {
                    let oldH = win.data('old-height') || '400px';
                    win.css('height', oldH);
                }
            };

            window.toggleChatWindowMax = function(groupId) {
                if (chatClickTimer) {
                    clearTimeout(chatClickTimer);
                    chatClickTimer = null;
                }
                let win = $(`#chat-window-${groupId}`);
                let isMax = win.hasClass('maximized');

                // Đóng các cửa sổ khác nếu đang phóng to (tùy chọn, để đỡ rối)
                if (!isMax) {
                    $('.chat-window').not(win).removeClass('maximized');
                    $('body').addClass('chat-maximized');
                } else {
                    $('body').removeClass('chat-maximized');
                }

                win.toggleClass('maximized');

                // Cuộn xuống cuối sau khi phóng to
                setTimeout(() => {
                    let contentDiv = document.getElementById(`chat-content-${groupId}`);
                    if (contentDiv) contentDiv.scrollTop = contentDiv.scrollHeight;
                }, 350);
            };

            function loadChatMessages(groupId, forceScroll = false) {
                let url = "{{ route('chat.messages', ':groupId', false) }}".replace(':groupId', groupId);
                let contentDiv = document.getElementById(`chat-content-${groupId}`);
                let isAtBottom = contentDiv ? (contentDiv.scrollHeight - contentDiv.scrollTop <= contentDiv
                    .clientHeight + 50) : true;

                $.get(url, function(res) {
                    let container = $(`#chat-content-${groupId}`);
                    let currentUserId = {{ session('user')['userId'] ?? 'null' }};
                    let messages = res.messages;
                    let groupMembersReadStatus = res.group_members_read_status;

                    // Nếu là lần đầu load hoặc forceScroll, nạp lại toàn bộ
                    if (forceScroll || container.find('.msg-item').length === 0) {
                        let html = renderMessages(messages, currentUserId, groupMembersReadStatus);
                        container.html(html);
                        attachMessageActions(groupId); // Gắn sự kiện
                        if (forceScroll || isAtBottom) {
                            let div = document.getElementById(`chat-content-${groupId}`);
                            if (div) div.scrollTop = div.scrollHeight;
                        }
                    } else {
                        // Polling: Chỉ thêm tin nhắn mới nhất nếu chưa có
                        let lastId = container.find('.msg-item').last().data('id');
                        let newMessages = messages.filter(m => m.id > (lastId || 0));
                        if (newMessages.length > 0) {
                            container.append(renderMessages(newMessages, currentUserId,
                                groupMembersReadStatus));
                            attachMessageActions(groupId); // Gắn sự kiện cho tin mới
                            if (isAtBottom) {
                                let div = document.getElementById(`chat-content-${groupId}`);
                                if (div) div.scrollTop = div.scrollHeight;
                            }
                        }
                        // Cập nhật trạng thái "Đã xem" cho tin nhắn cũ hơn
                        updateMessagesStatus(groupId, groupMembersReadStatus);
                    }
                });
            }

            function loadMoreMessages(groupId) {
                let container = $(`#chat-content-${groupId}`);
                let firstMsg = container.find('.msg-item').first();
                let firstId = firstMsg.data('id');
                if (!firstId) return;

                // Tránh gọi liên tục
                if (container.data('loading')) return;
                container.data('loading', true);

                let url = "{{ route('chat.messages', ':groupId', false) }}".replace(':groupId', groupId);
                $.get(url, {
                    before_id: firstId
                }, function(res) {
                    container.data('loading', false);
                    let messages = res.messages;
                    if (messages.length === 0) return;

                    let currentUserId = {{ session('user')['userId'] ?? 'null' }};
                    let groupMembersReadStatus = res.group_members_read_status;

                    let oldScrollHeight = container[0].scrollHeight;
                    container.prepend(renderMessages(messages, currentUserId, groupMembersReadStatus));

                    // Giữ vị trí cuộn
                    container.scrollTop(container[0].scrollHeight - oldScrollHeight);
                });
            }

            function renderMessages(messages, currentUserId, groupMembersReadStatus) {
                let html = '';
                messages.forEach(m => {
                    let side = m.sender_id == currentUserId ? 'me' : 'other';
                    let isRecalled = m.is_recalled == 1;
                    let content = isRecalled ? '<span class="recalled-msg">Tin nhắn đã được thu hồi</span>' : (m
                        .message || '');

                    if (!isRecalled && m.file_path) {
                        let fPath = m.file_path.startsWith('http') ? m.file_path : (m.file_path.startsWith(
                            '/') ? m.file_path : '/storage/' + m.file_path);
                        if (m.file_type && m.file_type.startsWith('image/')) {
                            content +=
                                `<div class="mt-1"><img src="${fPath}" style="max-width:100%; border-radius:5px; cursor:pointer;" onclick="window.open('${fPath}', '_blank')"></div>`;
                        } else {
                            content +=
                                `<div class="mt-1"><a href="${fPath}" target="_blank" class="text-primary font-weight-bold"><i class="fas fa-file-download"></i> ${m.file_name || 'Tải xuống File'}</a></div>`;
                        }
                    }

                    // Render Reply Content
                    let replyHtml = '';
                    if (m.reply_to_id && m.reply_to_content) {
                        let rText = m.reply_to_content.is_recalled ? 'Tin nhắn đã bị thu hồi' : m
                            .reply_to_content.message;
                        replyHtml = `
                            <div class="reply-wrapper" data-reply-id="${m.reply_to_id}">
                                <span class="reply-sender">${m.reply_to_content.sender_name || 'Người dùng'}</span>
                                <span class="reply-content">${rText}</span>
                            </div>
                        `;
                    }

                    let statusHtml = '';
                    let timeHtml = `<span class="msg-time">${moment(m.created_at).format('HH:mm')}</span>`;
                    if (side === 'me') {
                        let seenUsers = groupMembersReadStatus ? groupMembersReadStatus.filter(u => u
                            .last_read_at && u
                            .last_read_at >= m.created_at) : [];
                        let isSeen = seenUsers.length > 0;
                        let seenInfo = isSeen ? 'Đã xem bởi: ' + seenUsers.map(u => u.fullName).join(', ') :
                            'Đã gửi';

                        statusHtml =
                            `<div class="msg-status" data-time="${m.created_at}" title="${isSeen ? seenInfo : ''}">
                                ${timeHtml} 
                                <span>${isRecalled ? '' : (isSeen ? 'Đã xem ('+seenUsers.length+')' : 'Đã gửi')}</span>
                            </div>`;
                    } else {
                        statusHtml = `<div class="msg-status">${timeHtml}</div>`;
                    }

                    // Render Reactions
                    let reactionsHtml = '<div class="reaction-list">';
                    if (m.reactions_summary) {
                        for (let emoji in m.reactions_summary) {
                            let r = m.reactions_summary[emoji];
                            let usersTitle = r.users.join(', ');
                            reactionsHtml += `
                                <div class="reaction-item ${r.me ? 'me' : ''}" title="${usersTitle}" onclick="toggleReaction(${m.id}, '${emoji}')">
                                    <span>${emoji}</span>
                                    <span class="reaction-count">${r.count}</span>
                                </div>
                            `;
                        }
                    }
                    reactionsHtml += '</div>';

                    // Actions (Reply, Recall, Reaction)
                    let actionsHtml = '';
                    if (!isRecalled) {
                        let safeText = (m.message || 'File').replace(/'/g, "&apos;").replace(/"/g, "&quot;")
                            .replace(/\n/g, " ");
                        let safeName = (m.sender_name || 'Người dùng').replace(/'/g, "&apos;").replace(/"/g,
                            "&quot;");
                        actionsHtml = `
                            <div class="msg-actions">
                                <div class="btn-reaction">
                                    <span class="msg-action-btn" title="Thả cảm xúc">
                                        <i class="far fa-smile"></i>
                                    </span>
                                    <div class="reaction-picker-mini">
                                        <span class="reaction-emoji-btn" onclick="toggleReaction(${m.id}, '👍')">👍</span>
                                        <span class="reaction-emoji-btn" onclick="toggleReaction(${m.id}, '❤️')">❤️</span>
                                        <span class="reaction-emoji-btn" onclick="toggleReaction(${m.id}, '😄')">😄</span>
                                        <span class="reaction-emoji-btn" onclick="toggleReaction(${m.id}, '😮')">😮</span>
                                        <span class="reaction-emoji-btn" onclick="toggleReaction(${m.id}, '😢')">😢</span>
                                        <span class="reaction-emoji-btn" onclick="toggleReaction(${m.id}, '🔥')">🔥</span>
                                    </div>
                                </div>
                                <span class="msg-action-btn btn-reply" title="Trả lời" 
                                    data-msg-id="${m.id}" data-name="${safeName}" data-text="${safeText}">
                                    <i class="fas fa-reply"></i>
                                </span>
                                ${side === 'me' ? `<span class="msg-action-btn btn-recall text-danger" title="Thu hồi, sau 30p sẽ không được thu hồi" data-msg-id="${m.id}">
                                                                        <i class="fas fa-undo"></i>
                                                                    </span>` : ''}
                            </div>
                        `;
                    }

                    html += `
                        <div class="msg-item ${side}" data-id="${m.id}" data-time="${m.created_at}" id="msg-${m.id}">
                            ${side === 'other' ? `<div class="msg-sender" style="margin-left: 0;">${m.sender_name}</div>` : ''}
                            <div class="msg-row">
                                <div class="msg-bubble">
                                    ${replyHtml}
                                    <div class="msg-text">${content}</div>
                                    ${statusHtml}
                                </div>
                                <div class="reaction-container">
                                    ${renderReactions(m.reactions_summary, m.id)}
                                </div>
                                ${actionsHtml}
                            </div>
                        </div>
                    `;
                });
                return html;
            }

            function renderReactions(summary, messageId) {
                if (!summary || Object.keys(summary).length === 0) return '';
                let html = '<div class="reaction-list">';
                for (let emoji in summary) {
                    let r = summary[emoji];
                    let usersTitle = r.users.join(', ');
                    html += `
                        <div class="reaction-item ${r.me ? 'me' : ''}" title="${usersTitle}" onclick="toggleReaction(${messageId}, '${emoji}')">
                            <span>${emoji}</span>
                            <span class="reaction-count">${r.count}</span>
                        </div>
                    `;
                }
                html += '</div>';
                return html;
            }

            function attachMessageActions(groupId) {
                let win = $(`#chat-window-${groupId}`);

                // Gắn sự kiện hover để quản lý trạng thái tương tác
                win.find('.msg-row').off('mouseenter mouseleave').on('mouseenter', function() {
                    isUserInteracting = true;
                }).on('mouseleave', function() {
                    isUserInteracting = false;
                });

                win.find('.btn-reply').off('click').on('click', function() {
                    let id = $(this).data('msg-id');
                    let name = $(this).data('name');
                    let text = $(this).data('text');
                    setReply(groupId, id, name, text);
                });
                win.find('.btn-recall').off('click').on('click', function() {
                    let id = $(this).data('msg-id');
                    recallMessage(groupId, id);
                });
                win.find('.reply-wrapper').off('click').on('click', function() {
                    let id = $(this).data('reply-id');
                    scrollToMessage(groupId, id);
                });
            }

            function updateMessagesStatus(groupId, groupMembersReadStatus) {
                $(`#chat-content-${groupId} .msg-item.me`).each(function() {
                    let msgId = $(this).data('id');
                    let msgTime = $(this).data('time');
                    let isRecalled = $(this).find('.recalled-msg').length > 0;
                    if (isRecalled) return;

                    let seenUsers = groupMembersReadStatus ? groupMembersReadStatus.filter(u => u
                        .last_read_at && u
                        .last_read_at >= msgTime) : [];
                    let isSeen = seenUsers.length > 0;

                    if (isSeen) {
                        let seenInfo = 'Đã xem bởi: ' + seenUsers.map(u => u.fullName).join(', ');
                        let statusSpan = $(this).find('.msg-status span:not(.msg-time)');
                        statusSpan.text('Đã xem (' + seenUsers.length + ')');
                        $(this).find('.msg-status').attr('title', seenInfo);
                    }
                });
            }

            window.toggleReaction = function(messageId, emoji) {
                $.post("{{ route('chat.reaction', [], false) }}", {
                    _token: "{{ csrf_token() }}",
                    message_id: messageId,
                    reaction: emoji
                }, function(res) {
                    if (res.success) {
                        // Cập nhật DOM ngay lập tức
                        let reactionContainer = $(`#msg-${messageId} .reaction-container`);
                        if (reactionContainer.length) {
                            reactionContainer.html(renderReactions(res.reactions_summary, messageId));
                        }
                    }
                }).fail(function(err) {
                    console.error("Lỗi khi thả cảm xúc:", err);
                });
            };

            let currentReplyTo = {}; // {groupId: messageId}

            window.setReply = function(groupId, messageId, senderName, messageText) {
                currentReplyTo[groupId] = messageId;
                let preview = $(`#reply-preview-${groupId}`);
                preview.find('.reply-to-name').text(senderName);
                preview.find('.reply-to-text').text(messageText);
                preview.show();
                $(`#chat-window-${groupId} .chat-input`).focus();
            };

            window.cancelReply = function(groupId) {
                delete currentReplyTo[groupId];
                $(`#reply-preview-${groupId}`).hide();
            };

            window.scrollToMessage = function(groupId, messageId) {
                let msgElement = $(`#msg-${messageId}`);
                if (msgElement.length) {
                    let contentDiv = $(`#chat-content-${groupId}`);
                    contentDiv.animate({
                        scrollTop: contentDiv.scrollTop() + msgElement.position().top - 50
                    }, 500);
                    msgElement.css('background', '#fff9c4');
                    setTimeout(() => msgElement.css('background', ''), 2000);
                }
            };

            window.recallMessage = function(groupId, messageId) {
                Swal.fire({
                    title: 'Thu hồi tin nhắn?',
                    text: 'Bạn không thể hoàn tác hành động này sau 30p.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ route('chat.recall', [], false) }}", {
                            _token: "{{ csrf_token() }}",
                            message_id: messageId
                        }).done(function() {
                            loadChatMessages(groupId, true);
                        }).fail(function(xhr) {
                            Swal.fire('Lỗi', xhr.responseJSON?.message ||
                                'Không thể thu hồi tin nhắn', 'error');
                        });
                    }
                });
            };

            window.sendChatMessage = function(groupId, input) {
                let msg = input.value;
                if (!msg.trim()) return;

                let replyId = currentReplyTo[groupId] || null;

                // Xóa input ngay lập tức
                input.value = '';
                cancelReply(groupId);

                $.post("{{ route('chat.send', [], false) }}", {
                    _token: "{{ csrf_token() }}",
                    group_id: groupId,
                    message: msg,
                    reply_to_id: replyId
                }).done(function() {
                    loadChatMessages(groupId, true);
                    loadChatGroups(); // Cập nhật danh sách trái
                }).fail(function() {
                    input.value = msg;
                    alert('Gửi tin nhắn thất bại, vui lòng thử lại.');
                });
            };

            window.uploadFile = function(input, groupId) {
                if (!input.files || !input.files[0]) return;
                performFileUpload(input.files[0], groupId, function() {
                    input.value = ''; // Reset input
                });
            };

            window.handleChatPaste = function(event, groupId) {
                let items = (event.clipboardData || event.originalEvent.clipboardData).items;
                for (let index in items) {
                    let item = items[index];
                    if (item.kind === 'file' && item.type.startsWith('image/')) {
                        let blob = item.getAsFile();
                        let fileName = "pasted_image_" + moment().format('YYYYMMDD_HHmmss') + ".png";
                        let file = new File([blob], fileName, {
                            type: item.type
                        });
                        performFileUpload(file, groupId);
                    }
                }
            };

            function performFileUpload(file, groupId, callback = null) {
                let formData = new FormData();
                formData.append('file', file);
                formData.append('group_id', groupId);
                formData.append('_token', "{{ csrf_token() }}");
                formData.append('message', '[Hình ảnh dán: ' + file.name + ']');

                $.ajax({
                    url: "{{ route('chat.send', [], false) }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (callback) callback();
                        loadChatMessages(groupId, true);
                        loadChatGroups();
                    },
                    error: function(xhr) {
                        alert('Lỗi tải lên hình ảnh: ' + (xhr.responseJSON?.message || 'Vui lòng thử lại'));
                    }
                });
            }

            window.initChatResize = function(e, groupId) {
                let win = document.getElementById(`chat-window-${groupId}`);
                if (!win || win.classList.contains('maximized')) return;

                let startX = e.clientX;
                let startY = e.clientY;
                let startWidth = win.offsetWidth;
                let startHeight = win.offsetHeight;

                function doResize(e) {
                    let newWidth = startWidth + (startX - e.clientX);
                    let newHeight = startHeight + (startY - e.clientY);

                    // Limits
                    if (newWidth >= 250 && newWidth <= 800) win.style.width = newWidth + 'px';

                    // Giới hạn chiều cao: Tối thiểu 100px, tối đa là chiều cao màn hình trừ đi 100px để không vượt quá header phần mềm
                    let maxHeight = window.innerHeight - 100;
                    if (newHeight >= 100 && newHeight <= maxHeight) win.style.height = newHeight + 'px';
                }

                function stopResize() {
                    window.removeEventListener('mousemove', doResize);
                    window.removeEventListener('mouseup', stopResize);
                }

                window.addEventListener('mousemove', doResize);
                window.addEventListener('mouseup', stopResize);
            };

            /* 
            // Polling cập nhật tin nhắn
            loadChatGroups(); // Gọi ngay lần đầu khi load trang
            setInterval(function() {
                openChatGroups.forEach(groupId => {
                    loadChatMessages(groupId);
                });
                loadChatGroups(); // Luôn gọi để cập nhật Badge chưa đọc và hiệu ứng nhấp nháy
            }, 10000);
            */

            // --- CÁC HÀM XỬ LÝ NHÓM & EMOJI ---
            let allUsersForGroup = [];

            window.showCreateGroupModal = function() {
                $.get("{{ route('chat.users') }}", function(users) {
                    allUsersForGroup = users;

                    // Xây dựng danh sách phòng ban
                    let depts = [...new Set(users.map(u => u.deparment).filter(d => d))].sort();
                    let deptHtml = '<option value="all">Tất cả phòng ban</option>';
                    depts.forEach(d => {
                        deptHtml += `<option value="${d}">${d}</option>`;
                    });
                    $('#deptFilter').html(deptHtml);

                    // Xây dựng danh sách Nhóm người dùng (UserGroup)
                    let groups = [...new Set(users.map(u => u.userGroup).filter(g => g))].sort();
                    let groupHtml = '<option value="all">Tất cả nhóm</option>';
                    groups.forEach(g => {
                        groupHtml += `<option value="${g}">${g}</option>`;
                    });
                    $('#userGroupFilter').html(groupHtml);

                    renderUserListForGroup(users);
                    $('#newGroupName').val('');
                    $('#createGroupModal').modal('show');
                });
            };

            window.renderUserListForGroup = function(users) {
                let html = '';
                users.forEach(u => {
                    html += `
                        <div class="form-check mb-1 user-check-item" data-dept="${u.deparment || ''}" data-group="${u.userGroup || ''}">
                            <input class="form-check-input" type="checkbox" value="${u.id}" id="user-${u.id}" name="group_members">
                            <label class="form-check-label" for="user-${u.id}" style="cursor:pointer">
                                ${u.fullName} (@${u.userName}) 
                                <small class="text-muted">-${u.deparment || ''}</small>
                                <small class="text-info font-italic">[${u.userGroup || ''}]</small>
                            </label>
                        </div>
                    `;
                });
                $('#userListForGroup').html(html ||
                    '<div class="text-center text-muted">Không có người dùng nào</div>');
            }

            window.filterUsersByDepartment = function() {
                let dept = $('#deptFilter').val();
                let ugroup = $('#userGroupFilter').val();

                $('.user-check-item').each(function() {
                    let matchDept = (dept === 'all' || $(this).data('dept') === dept);
                    let matchGroup = (ugroup === 'all' || $(this).data('group') === ugroup);
                    $(this).toggle(matchDept && matchGroup);
                });
            };

            window.filterUsersByUserGroup = function() {
                // Sử dụng cùng logic filter kết hợp
                filterUsersByDepartment();
            };

            window.selectAllInDept = function() {
                let dept = $('#deptFilter').val();
                let ugroup = $('#userGroupFilter').val();

                $('.user-check-item').each(function() {
                    let matchDept = (dept === 'all' || $(this).data('dept') === dept);
                    let matchGroup = (ugroup === 'all' || $(this).data('group') === ugroup);
                    if (matchDept && matchGroup) {
                        $(this).find('input').prop('checked', true);
                    }
                });
            };

            window.selectAllInUserGroup = function() {
                // Sử dụng cùng logic chọn tất cả dựa trên filter hiện tại
                selectAllInDept();
            };

            window.submitCreateGroup = function() {
                let name = $('#newGroupName').val();
                let members = [];
                $('input[name="group_members"]:checked').each(function() {
                    members.push($(this).val());
                });

                if (!name || members.length === 0) {
                    alert('Vui lòng nhập tên nhóm và chọn ít nhất 1 thành viên');
                    return;
                }

                $.post("{{ route('chat.createGroup') }}", {
                    _token: "{{ csrf_token() }}",
                    name: name,
                    member_ids: members
                }, function(res) {
                    $('#createGroupModal').modal('hide');
                    loadChatGroups();
                    openChatWindow(res.id, name);
                });
            };

            const commonEmojis = [
                '😀', '😁', '😂', '🤣', '😃', '😄', '😅', '😆', '😉', '😊', '😋', '😎', '😍', '😘', '🥰', '😗',
                '😙', '😚', '☺️', '🙂', '🤗', '🤩',
                '🤔', '🤨', '😐', '😑', '😶', '🙄', '😏', '😣', '😥', '😮', '🤐', '😯', '😪', '😫', '🥱', '😴',
                '😌', '😛', '😜', '😝', '🤤', '😒', '😓', '😔', '😕', '🙃', '🤑', '😲',
                '☹️', '🙁', '😖', '😞', '😟', '😤', '😢', '😭', '😦', '😧', '😨', '😩', '🤯', '😬', '😰', '😱',
                '🥵', '🥶', '😳', '🤪', '😵', '😡', '😠', '🤬', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '😇', '🥳',
                '🤫', '🤭', '🧐', '🤓', '😈', '👿', '👹', '👺', '💀', '👻', '👽', '🤖', '💩', '😺', '😸', '😹',
                '😻', '😼', '😽', '🙀', '😿', '😾',
                '👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕',
                '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏',
                '✍️', '💅', '🤳', '💪', '🦾', '🦵', '🦿', '🦶', '👂', '🦻', '👃', '🧠', '🦷', '🦴', '👀', '👁️',
                '👅', '👄', '💋', '🩸',
                '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖',
                '💘', '💝', '💟',
                '✨', '🔥', '⚡', '💥', '❄️', '🌈', '⭐', '🌟', '☁️', '☀️', '📌', '📍', '✅', '❌', '🚀', '🎯', '⌛', '⏰'
            ];

            window.toggleEmojiPicker = function(groupId) {
                let existing = $(`#emoji-picker-${groupId}`);
                if (existing.length) {
                    existing.remove();
                    isUserInteracting = false; // Mở lại cuộn nếu đóng picker
                    return;
                }

                isUserInteracting = true; // Khóa cuộn khi đang chọn emoji
                let html = `<div class="emoji-picker" id="emoji-picker-${groupId}">`;
                commonEmojis.forEach(e => {
                    html += `<span class="emoji-item" onclick="addEmoji(${groupId}, '${e}')">${e}</span>`;
                });
                html += `</div>`;
                $(`#chat-window-${groupId}`).append(html);

                // Đóng khi click ngoài
                $(document).one('mousedown', function(e) {
                    if (!$(e.target).closest('.emoji-picker, .btn-emoji').length) {
                        $(`#emoji-picker-${groupId}`).remove();
                        isUserInteracting = false;
                    }
                });
            };

            window.addEmoji = function(groupId, emoji) {
                let input = $(`#chat-window-${groupId} .chat-input`);
                input.val(input.val() + emoji);
                input.focus();
                $(`#emoji-picker-${groupId}`).remove();
                isUserInteracting = false; // Mở lại cuộn sau khi chọn xong
            };

            // --- TRIBUTE.JS (TAG @USER) ---
            window.initTribute = function(groupId) {
                $.get("{{ route('chat.users') }}", function(users) {
                    let tribute = new Tribute({
                        values: users.map(u => ({
                            key: u.fullName,
                            value: `@${u.fullName}[${u.id}]`
                        })),
                        selectTemplate: function(item) {
                            return item.original.value;
                        }
                    });
                    let input = document.querySelector(`#chat-window-${groupId} .chat-input`);
                    if (input) tribute.attach(input);
                });
            };

            // --- CHAT SEARCH LOGIC ---
            let chatSearchResults = {}; // {groupId: {indices: [], current: -1}}

            window.toggleChatSearch = function(groupId) {
                let container = $(`#chat-search-container-${groupId}`);
                let input = $(`#chat-search-input-${groupId}`);
                if (container.hasClass('d-none')) {
                    container.removeClass('d-none');
                    input.focus();
                } else {
                    container.addClass('d-none');
                    clearChatSearch(groupId);
                }
            };

            function clearChatSearch(groupId) {
                let content = $(`#chat-content-${groupId}`);
                content.find('.search-highlight').each(function() {
                    $(this).replaceWith($(this).text());
                });
                $(`#chat-search-stats-${groupId}`).addClass('d-none');
                chatSearchResults[groupId] = {
                    indices: [],
                    current: -1
                };
            }

            window.executeChatSearch = function(groupId) {
                let query = $(`#chat-search-input-${groupId}`).val().trim().toLowerCase();
                clearChatSearch(groupId);
                if (!query) return;

                let content = $(`#chat-content-${groupId}`);
                let matches = [];

                content.find('.msg-text').each(function() {
                    let text = $(this).text();
                    let lowerText = text.toLowerCase();
                    if (lowerText.includes(query)) {
                        // Use regex to replace all occurrences with case-insensitive support
                        let regex = new RegExp(`(${query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')})`,
                            'gi');
                        let newHtml = text.replace(regex, '<span class="search-highlight">$1</span>');
                        $(this).html(newHtml);
                    }
                });

                let allHighlights = content.find('.search-highlight');
                if (allHighlights.length > 0) {
                    chatSearchResults[groupId] = {
                        indices: allHighlights,
                        current: 0
                    };
                    $(`#chat-search-stats-${groupId}`).text(`1/${allHighlights.length}`).removeClass('d-none');
                    scrollToMatch(groupId, 0);
                } else {
                    $(`#chat-search-stats-${groupId}`).text('0/0').removeClass('d-none');
                }
            };

            window.navigateChatSearch = function(groupId, direction) {
                let results = chatSearchResults[groupId];
                if (!results || results.indices.length === 0) return;

                results.current += direction;
                if (results.current < 0) results.current = results.indices.length - 1;
                if (results.current >= results.indices.length) results.current = 0;

                $(`#chat-search-stats-${groupId}`).text(`${results.current + 1}/${results.indices.length}`);
                scrollToMatch(groupId, results.current);
            };

            function scrollToMatch(groupId, index) {
                let results = chatSearchResults[groupId];
                if (!results) return;

                let highlights = results.indices;
                highlights.removeClass('current-match');

                let target = $(highlights[index]);
                target.addClass('current-match');

                let contentDiv = $(`#chat-content-${groupId}`);
                let scrollTop = contentDiv.scrollTop();
                let targetTop = target.offset().top - contentDiv.offset().top + scrollTop;

                contentDiv.animate({
                    scrollTop: targetTop - (contentDiv.height() / 2)
                }, 300);
            }
        })();
    </script>
    <script src="{{ asset('js/tribute.min.js') }}"></script>
</body>

</html>
