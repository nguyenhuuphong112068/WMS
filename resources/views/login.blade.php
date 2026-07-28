<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>Đăng nhập | Sổ theo dõi cấp lại hồ sơ</title>

    <!-- Bootstrap & Fonts -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">

    <style>
        :root {
            --primary-navy: #003A4F;
            --accent-gold: #CDC717;
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        body {
            background: linear-gradient(rgba(0, 58, 79, 0.8), rgba(0, 58, 79, 0.8)), 
                        url('{{ asset('img/Stella_Icon_Main.jpg') }}') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .library-branding h2 {
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            font-size: 2rem;
            margin-top: 15px;
            letter-spacing: -0.5px;
        }

        .form-label {
            font-weight: 500;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-navy);
            box-shadow: 0 0 0 3px rgba(0, 58, 79, 0.1);
        }

        .btn-login {
            background-color: var(--primary-navy);
            color: white;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-login:hover {
            background-color: #002D3D;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 58, 79, 0.3);
            color: white;
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
        }

        .toggle-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-navy);
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
        }

        .toggle-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-card shadow-lg text-center">
        <div class="library-branding mb-5">
            <img src="{{ asset('img/iconstella.svg') }}" alt="Logo" style="max-width: 60px;">
            <h2>Sổ theo dõi cấp lại hồ sơ</h2>
            <p class="text-muted small">Quản lý đề nghị cấp lại trang hồ sơ BMR/ BPR</p>
        </div>

        @if (session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif

        <!-- ✅ Form đăng nhập -->
        <form id="loginForm" action="{{ route('login') }}" method="POST" class="text-start">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label">Tên tài khoản</label>
                <input type="text" name="username" class="form-control" placeholder="Nhập username" required autofocus value="{{ old('username') }}">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <div class="password-wrapper">
                    <input type="password" id="loginPassword" name="passWord" class="form-control" placeholder="••••••••" required>
                    <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('loginPassword', this)"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-login w-100">
                Đăng nhập hệ thống
            </button>

            <a href="#" class="toggle-link" onclick="toggleForms(true)">Bạn quên mật khẩu?</a>
        </form>

        <!-- ✅ Form đổi mật khẩu (Ẩn mặc định) -->
        <form id="changePassForm" action="{{ route('changePassword') }}" method="POST" style="display: none;" class="text-start">
            @csrf
            <h5 class="mb-4 fw-bold">Thiết lập mật khẩu mới</h5>
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Xác nhận username" required>
            </div>
            <div class="mb-3 password-wrapper">
                <input type="password" id="oldPassword" name="oldPassword" class="form-control" placeholder="Mật khẩu hiện tại" required>
            </div>
            <div class="mb-3">
                <input type="password" id="newPassword" name="newPassword" class="form-control" placeholder="Mật khẩu mới" required>
            </div>
            <button type="submit" class="btn btn-login w-100">Cập nhật ngay</button>
            <a href="#" class="toggle-link" onclick="toggleForms(false)">Quay lại đăng nhập</a>
        </form>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
        function toggleForms(showChangePass) {
            document.getElementById('loginForm').style.display = showChangePass ? 'none' : 'block';
            document.getElementById('changePassForm').style.display = showChangePass ? 'block' : 'none';
        }

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("bi-eye-slash", "bi-eye");
            } else {
                input.type = "password";
                icon.classList.replace("bi-eye", "bi-eye-slash");
            }
        }
    </script>
</body>

</html>
