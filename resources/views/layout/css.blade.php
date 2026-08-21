		
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

            body.modal-open {
                  padding: 0 !important;
                  overflow-y: scroll;
            }
      </style>
