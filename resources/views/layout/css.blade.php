		
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/fontawesome-free/css/all.min.css')}} ">
      <!-- DataTables -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
      <!-- Theme style -->
      <link rel="stylesheet" href="{{asset ('dataTable/dist/css/adminlte.min.css')}}">



      <link rel="stylesheet" href="{{asset ('dataTable/plugins/fontawesome-free/css/all.min.css')}}">
      <!-- Ionicons -->
      <link rel="stylesheet" href="{{asset ('css/ionicons.min.css')}}">
      <!-- daterange picker -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/daterangepicker/daterangepicker.css')}}">
      <!-- iCheck for checkboxes and radio inputs -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
      <!-- Bootstrap Color Picker -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css')}}">
      <!-- Tempusdominus Bbootstrap 4 -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">
      <!-- Select2 -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/select2/css/select2.min.css')}}">
      
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
      <!-- Bootstrap4 Duallistbox -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css')}}">
      <!-- Theme style -->
      <link rel="stylesheet" href="{{asset ('dataTable/dist/css/adminlte.min.css')}}">

      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      
      <!-- Fonts: Sans-serif system -->
      
      <style>
            :root {
                  /* ===== Bảng màu chủ đạo: XANH DƯƠNG NHẠT ===== */
                  --primary: #2E7BC4;
                  --primary-dark: #1F5E9E;
                  --primary-light: #5AA0DE;
                  --primary-lighter: #9CC7EE;
                  --primary-soft: #EAF3FC;
                  --primary-rgb: 46, 123, 196;
                  --accent: #17B8D4;

                  /* Alias tương thích các view cũ */
                  --primary-navy: var(--primary);
                  --accent-gold: var(--accent);

                  --bg-neutral: #F5F9FD;
                  --text-main: #2D3748;
                  --border-radius-lg: 12px;
                  --border-radius-md: 8px;
                  --shadow-sm: 0 2px 4px rgba(var(--primary-rgb), 0.06);
                  --shadow-md: 0 4px 12px rgba(var(--primary-rgb), 0.12);
                  --transition-fast: 0.2s ease;
                  --transition-normal: 0.3s ease;
            }

            body {
      font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                  background-color: var(--bg-neutral);
                  color: var(--text-main);
                  overflow-x: hidden;
            }

            h1, h2, h3, h4, .library-title, .module-header {
      font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                  text-transform: uppercase;
                  letter-spacing: 1px;
                  font-weight: 700;
                  color: var(--primary-navy);
            }

            .form-floating > label {
      font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                  font-size: 0.85rem;
                  color: #718096;
            }

            .card {
                  border: none;
                  border-radius: var(--border-radius-lg);
                  box-shadow: var(--shadow-sm);
                  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
            }

            .card:hover {
                  box-shadow: var(--shadow-md);
            }

            .btn-primary {
                  background-color: var(--primary);
                  border-color: var(--primary);
                  border-radius: var(--border-radius-md);
                  padding: 8px 16px;
                  font-weight: 500;
                  transition: all var(--transition-fast);
            }

            .btn-primary:hover,
            .btn-primary:focus,
            .btn-primary:active {
                  background-color: var(--primary-dark);
                  border-color: var(--primary-dark);
                  transform: translateY(-1px);
                  box-shadow: 0 4px 8px rgba(var(--primary-rgb), 0.25);
            }

            .btn-outline-primary {
                  color: var(--primary);
                  border-color: var(--primary);
                  border-radius: var(--border-radius-md);
            }

            .btn-outline-primary:hover {
                  background-color: var(--primary);
                  border-color: var(--primary);
                  color: #fff;
            }

            a {
                  color: var(--primary);
            }

            a:hover {
                  color: var(--primary-dark);
            }

            .form-control:focus,
            .form-select:focus,
            .custom-select:focus {
                  border-color: var(--primary-light);
                  box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15);
            }

            /* Bảng dữ liệu theo tông xanh dương nhạt */
            .table thead th {
                  background-color: var(--primary-soft);
                  color: var(--primary-dark);
                  border-bottom: 2px solid var(--primary-lighter) !important;
                  text-transform: uppercase;
                  font-size: 0.8rem;
                  letter-spacing: 0.5px;
            }

            .table tbody tr:hover {
                  background-color: rgba(var(--primary-rgb), 0.05);
            }

            .page-item.active .page-link {
                  background-color: var(--primary);
                  border-color: var(--primary);
            }

            .page-link {
                  color: var(--primary);
            }


            /* ----------------------------------------------------------------
               Thanh công cụ gộp ngay trên bảng
               ----------------------------------------------------------------
               Đoạn JS ở cuối layout/master.blade.php dồn nút thêm mới, bộ lọc,
               ô "Hiển thị N dòng" và ô "Tìm kiếm" của DataTables về CÙNG MỘT
               HÀNG, thay vì mỗi thứ chiếm một hàng riêng, để bảng dữ liệu có
               thêm chiều cao. Khai báo ở đây để mọi màn hình đều dùng được.
               ---------------------------------------------------------------- */
            .md-tablebar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px 12px;
                margin-bottom: 10px;
                padding: 6px 10px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
            }

            /* Bỏ margin rời rạc của các phần tử được kéo vào */
            .md-tablebar > * {
                margin-top: 0;
                margin-bottom: 0;
            }

            .md-tablebar .hint {
                margin: 0;
                color: #94a3b8;
                font-size: 0.82rem;
            }

            /* Quét mã vạch bên trong tablebar */
            .md-tablebar .bcs-box {
                padding: 0;
                margin: 0;
                border: none;
                background: transparent;
                box-shadow: none;
            }

            .md-tablebar .bcs-box .bcs-row {
                display: flex;
                align-items: center;
                flex-wrap: nowrap;
                gap: 6px;
            }

            .md-tablebar .bcs-box .bcs-label-inline {
                font-size: 0.82rem;
                font-weight: 700;
                color: #1e293b;
                white-space: nowrap;
            }

            .md-tablebar .bcs-box .bcs-input-wrap {
                flex: 0 0 135px;
                width: 135px;
            }

            .md-tablebar .bcs-box .bcs-input-wrap .bcs-input {
                height: 30px;
                padding: 2px 8px;
                font-size: 0.83rem;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                background: #fff;
            }

            .md-tablebar .bcs-box .btn {
                height: 30px;
                padding: 2px 8px;
                font-size: 0.82rem;
                display: inline-flex;
                align-items: center;
                border-radius: 4px;
            }

            /* Bộ lọc phân loại / phân nhóm chuẩn */
            .md-tablebar .cls-filter,
            .md-tablebar .sgr-filter {
                padding: 0;
                margin: 0;
                border: none;
                background: transparent;
                box-shadow: none;
                gap: 6px;
                display: flex;
                align-items: center;
                white-space: nowrap;
            }

            .md-tablebar .cls-filter .cls-hint,
            .md-tablebar .sgr-filter .sgr-hint {
                display: none;
            }

            .md-tablebar .cls-filter label,
            .md-tablebar .sgr-filter label {
                margin: 0;
                font-size: 0.82rem;
                font-weight: 700;
                color: #1e293b;
                white-space: nowrap;
            }

            .md-tablebar .cls-filter .cls-select,
            .md-tablebar .sgr-filter .sgr-select {
                min-width: 140px;
                max-width: 200px;
                flex: 0 1 180px;
                height: 30px;
                padding: 2px 8px;
                font-size: 0.83rem;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                background: #fff;
            }

            .md-tablebar .cls-filter.is-filtering .cls-select,
            .md-tablebar .sgr-filter.is-filtering .sgr-select {
                border-color: var(--primary);
                background: var(--primary-soft);
                font-weight: 600;
            }

            /* Nhóm bên phải: số dòng hiển thị + ô tìm kiếm */
            .md-tablebar .dataTables_length,
            .md-tablebar .dataTables_filter {
                margin: 0;
                padding: 0;
                text-align: left;
                white-space: nowrap;
            }

            .md-tablebar .dataTables_length {
                margin-left: auto;
            }

            .md-tablebar .dataTables_length label,
            .md-tablebar .dataTables_filter label {
                display: flex;
                align-items: center;
                gap: 6px;
                margin: 0;
                font-size: 0.82rem;
                font-weight: 600;
                color: #475569;
                white-space: nowrap;
            }

            .md-tablebar .dataTables_length select {
                width: auto;
                min-width: 60px;
                height: 30px;
                padding: 2px 6px;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                font-size: 0.83rem;
                background: #fff;
            }

            .md-tablebar .dataTables_filter input {
                min-width: 130px;
                max-width: 160px;
                height: 30px;
                margin: 0;
                padding: 2px 8px;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                font-size: 0.83rem;
                background: #fff;
            }

            .md-tablebar .dataTables_length select:focus,
            .md-tablebar .dataTables_filter input:focus {
                border-color: var(--primary-light);
                box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.12);
                outline: none;
            }

            .md-tablebar .btn {
                white-space: nowrap;
            }
                }

                .md-tablebar .cls-filter .cls-select,
                .md-tablebar .dataTables_filter input {
                    min-width: 0;
                    width: 100%;
                }
            }

            body.modal-open {
                  padding: 0 !important;
                  overflow-y: scroll;
            }
      </style>
