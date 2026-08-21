<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>Đăng nhập | WMS - Hệ Thống Quản Lý Kho</title>

    <!-- Bootstrap & Icons -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">

    <style>
        :root {
            --primary: #2E7BC4;
            --primary-dark: #1F5E9E;
            --primary-light: #5AA0DE;
            --primary-lighter: #9CC7EE;
            --primary-soft: #EAF3FC;
            --primary-rgb: 46, 123, 196;
            --accent: #17B8D4;
            --text-main: #2D3748;
            --text-muted: #718096;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-main);
            background: linear-gradient(135deg, #EAF3FC 0%, #D6E8F9 45%, #C3DDF5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        /* Hoa văn kệ kho mờ phía nền */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(var(--primary-rgb), 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--primary-rgb), 0.05) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .auth-shell {
            position: relative;
            width: 100%;
            max-width: 1040px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(var(--primary-rgb), 0.22);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
        }

        /* ============ PANEL THƯƠNG HIỆU ============ */
        .brand-panel {
            position: relative;
            padding: 48px 44px;
            background: linear-gradient(150deg, var(--primary-light) 0%, var(--primary) 55%, var(--primary-dark) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            top: -160px;
            right: -160px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            z-index: 2;
        }

        .brand-logo .logo-box {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo .logo-box img {
            width: 32px;
            filter: brightness(0) invert(1);
        }

        .brand-logo h1 {
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: 3px;
            margin: 0;
            line-height: 1;
        }

        .brand-logo span {
            font-size: 0.72rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: 0.85;
        }

        .brand-headline {
            position: relative;
            z-index: 2;
            margin: 32px 0 8px;
        }

        .brand-headline h2 {
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.35;
            margin: 0 0 10px;
        }

        .brand-headline p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin: 0;
            max-width: 380px;
        }

        .warehouse-art {
            position: relative;
            z-index: 2;
            width: 100%;
            margin: 18px 0;
        }

        .brand-features {
            position: relative;
            z-index: 2;
            display: grid;
            gap: 12px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }

        .feature-item i {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        /* ============ PANEL FORM ============ */
        .form-panel {
            padding: 52px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-panel .subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 28px;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.85rem;
            margin-bottom: 6px;
        }

        .input-shell {
            position: relative;
        }

        .input-shell > i.field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-lighter);
            font-size: 1.05rem;
            transition: color 0.2s ease;
        }

        .form-control {
            border-radius: 12px;
            padding: 13px 46px;
            border: 1px solid #DDE7F2;
            background: #F8FBFF;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.12);
        }

        .input-shell:focus-within > i.field-icon {
            color: var(--primary);
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #A0AEC0;
            font-size: 1.05rem;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 60%, var(--primary-dark) 100%);
            color: #fff;
            border-radius: 12px;
            padding: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border: none;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 6px 16px rgba(var(--primary-rgb), 0.28);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(var(--primary-rgb), 0.36);
            color: #fff;
        }

        .toggle-link {
            display: block;
            text-align: center;
            margin-top: 18px;
            color: var(--primary);
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 600;
        }

        .toggle-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: none;
            font-size: 0.87rem;
            padding: 12px 16px;
        }

        .alert-danger {
            background: #FDECEC;
            color: #B42318;
        }

        .alert-warning {
            background: #FFF6E5;
            color: #B25E09;
        }

        .form-footer {
            margin-top: 32px;
            padding-top: 18px;
            border-top: 1px solid #EDF2F7;
            text-align: center;
            font-size: 0.75rem;
            color: #A0AEC0;
        }

        @media (max-width: 900px) {
            .auth-shell {
                grid-template-columns: 1fr;
                max-width: 480px;
            }

            .brand-panel {
                padding: 32px 28px;
            }

            .warehouse-art,
            .brand-features {
                display: none;
            }

            .brand-headline {
                margin: 20px 0 0;
            }

            .form-panel {
                padding: 34px 28px;
            }
        }
    </style>
</head>

<body>

    <div class="auth-shell">

        <!-- ============ TRÁI: THƯƠNG HIỆU + MINH HOẠ KHO ============ -->
        <div class="brand-panel">
            <div class="brand-logo">
                <div class="logo-box">
                    <img src="{{ asset('img/iconstella.svg') }}" alt="Logo">
                </div>
                <div>
                    <h1>WMS</h1>
                    <span>Warehouse Management</span>
                </div>
            </div>

            <div class="brand-headline">
                <h2>Hệ Thống Quản Lý Kho</h2>
                <p>Kiểm soát vị trí lưu trữ, tồn kho và luồng xuất nhập vật tư – hoá chất trên một nền tảng duy nhất.</p>
            </div>

            <!-- Minh hoạ kệ kho + xe nâng -->
            <svg class="warehouse-art" viewBox="0 0 560 260" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Nền sàn -->
                <line x1="10" y1="228" x2="550" y2="228" stroke="rgba(255,255,255,0.45)" stroke-width="3"
                    stroke-linecap="round" />

                <!-- Kệ hàng 1 -->
                <g opacity="0.95">
                    <rect x="40" y="40" width="10" height="188" rx="4" fill="rgba(255,255,255,0.55)" />
                    <rect x="216" y="40" width="10" height="188" rx="4" fill="rgba(255,255,255,0.55)" />
                    <rect x="40" y="40" width="186" height="9" rx="4" fill="rgba(255,255,255,0.65)" />
                    <rect x="40" y="112" width="186" height="9" rx="4" fill="rgba(255,255,255,0.65)" />
                    <rect x="40" y="184" width="186" height="9" rx="4" fill="rgba(255,255,255,0.65)" />

                    <!-- Thùng hàng tầng trên -->
                    <rect x="62" y="66" width="52" height="46" rx="5" fill="rgba(255,255,255,0.9)" />
                    <rect x="62" y="82" width="52" height="5" fill="rgba(46,123,196,0.35)" />
                    <rect x="126" y="76" width="42" height="36" rx="5" fill="rgba(255,255,255,0.7)" />
                    <rect x="126" y="89" width="42" height="4" fill="rgba(46,123,196,0.3)" />

                    <!-- Thùng hàng tầng giữa -->
                    <rect x="56" y="140" width="46" height="44" rx="5" fill="rgba(255,255,255,0.75)" />
                    <rect x="56" y="155" width="46" height="4" fill="rgba(46,123,196,0.3)" />
                    <rect x="112" y="132" width="58" height="52" rx="5" fill="rgba(255,255,255,0.92)" />
                    <rect x="112" y="150" width="58" height="5" fill="rgba(46,123,196,0.35)" />
                    <rect x="180" y="150" width="34" height="34" rx="5" fill="rgba(255,255,255,0.6)" />
                </g>

                <!-- Kệ hàng 2 -->
                <g opacity="0.8">
                    <rect x="268" y="70" width="9" height="158" rx="4" fill="rgba(255,255,255,0.5)" />
                    <rect x="410" y="70" width="9" height="158" rx="4" fill="rgba(255,255,255,0.5)" />
                    <rect x="268" y="70" width="151" height="8" rx="4" fill="rgba(255,255,255,0.6)" />
                    <rect x="268" y="146" width="151" height="8" rx="4" fill="rgba(255,255,255,0.6)" />

                    <rect x="286" y="102" width="44" height="44" rx="5" fill="rgba(255,255,255,0.8)" />
                    <rect x="286" y="118" width="44" height="4" fill="rgba(46,123,196,0.3)" />
                    <rect x="342" y="112" width="38" height="34" rx="5" fill="rgba(255,255,255,0.62)" />
                    <rect x="290" y="182" width="50" height="42" rx="5" fill="rgba(255,255,255,0.7)" />
                    <rect x="290" y="197" width="50" height="4" fill="rgba(46,123,196,0.3)" />
                    <rect x="352" y="190" width="40" height="34" rx="5" fill="rgba(255,255,255,0.55)" />
                </g>

                <!-- Xe nâng -->
                <g opacity="0.95">
                    <rect x="470" y="150" width="52" height="52" rx="8" fill="rgba(255,255,255,0.9)" />
                    <rect x="478" y="128" width="8" height="74" rx="4" fill="rgba(255,255,255,0.75)" />
                    <rect x="452" y="196" width="30" height="7" rx="3" fill="rgba(255,255,255,0.85)" />
                    <rect x="444" y="164" width="30" height="32" rx="4" fill="rgba(255,255,255,0.65)" />
                    <rect x="500" y="132" width="26" height="20" rx="4" fill="rgba(255,255,255,0.6)" />
                    <circle cx="484" cy="212" r="13" fill="rgba(255,255,255,0.9)" />
                    <circle cx="484" cy="212" r="5" fill="rgba(46,123,196,0.45)" />
                    <circle cx="516" cy="212" r="10" fill="rgba(255,255,255,0.9)" />
                    <circle cx="516" cy="212" r="4" fill="rgba(46,123,196,0.45)" />
                </g>
            </svg>

            <div class="brand-features">
                <div class="feature-item">
                    <i class="bi bi-geo-alt"></i>
                    <span>Quản lý kho – phòng – kệ – vị trí lưu trữ</span>
                </div>
                <div class="feature-item">
                    <i class="bi bi-boxes"></i>
                    <span>Theo dõi tồn kho vật tư và hoá chất</span>
                </div>
                <div class="feature-item">
                    <i class="bi bi-shield-check"></i>
                    <span>Phân quyền chặt chẽ và truy vết Audit Trail</span>
                </div>
            </div>
        </div>

        <!-- ============ PHẢI: FORM ============ -->
        <div class="form-panel">

            @if (session('error'))
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
                </div>
            @endif

            @if (request('timeout'))
                <div class="alert alert-warning">
                    <i class="bi bi-clock-history me-1"></i>Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.
                </div>
            @endif

            @if ($errors->changePasswordErrors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->changePasswordErrors->all() as $message)
                        <div><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                    @endforeach
                </div>
            @endif

            <!-- ✅ Form đăng nhập -->
            <form id="loginForm" action="{{ route('login') }}" method="POST">
                @csrf
                <h3>Đăng Nhập</h3>
                <p class="subtitle">Sử dụng tài khoản nội bộ để truy cập hệ thống.</p>

                <div class="mb-3">
                    <label for="username" class="form-label">Tên tài khoản</label>
                    <div class="input-shell">
                        <i class="bi bi-person field-icon"></i>
                        <input type="text" id="username" name="username" class="form-control"
                            placeholder="Nhập username" required autofocus value="{{ old('username') }}">
                    </div>
                </div>

                <div class="mb-2">
                    <label for="loginPassword" class="form-label">Mật khẩu</label>
                    <div class="input-shell">
                        <i class="bi bi-lock field-icon"></i>
                        <input type="password" id="loginPassword" name="passWord" class="form-control"
                            placeholder="••••••••" required>
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('loginPassword', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-login mt-4">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập hệ thống
                </button>

                <a href="#" class="toggle-link" onclick="toggleForms(true); return false;">Đổi mật khẩu / Quên mật
                    khẩu?</a>
            </form>

            <!-- ✅ Form đổi mật khẩu -->
            <form id="changePassForm" action="{{ route('changePassword') }}" method="POST" style="display: none;">
                @csrf
                <h3>Đổi Mật Khẩu</h3>
                <p class="subtitle">Mật khẩu mới tối thiểu 6 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.</p>

                <div class="mb-3">
                    <label class="form-label">Tên tài khoản</label>
                    <div class="input-shell">
                        <i class="bi bi-person field-icon"></i>
                        <input type="text" name="username" class="form-control" placeholder="Xác nhận username">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mật khẩu hiện tại</label>
                    <div class="input-shell">
                        <i class="bi bi-lock field-icon"></i>
                        <input type="password" id="oldPassword" name="oldPassword" class="form-control"
                            placeholder="••••••••">
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('oldPassword', this)"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <div class="input-shell">
                        <i class="bi bi-key field-icon"></i>
                        <input type="password" id="newPassword" name="newPassword" class="form-control"
                            placeholder="••••••••">
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('newPassword', this)"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Xác nhận mật khẩu mới</label>
                    <div class="input-shell">
                        <i class="bi bi-key-fill field-icon"></i>
                        <input type="password" id="confirmPassword" name="confirmPassword" class="form-control"
                            placeholder="••••••••">
                        <i class="bi bi-eye-slash toggle-password"
                            onclick="togglePassword('confirmPassword', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-login mt-2">
                    <i class="bi bi-check2-circle me-1"></i> Cập nhật mật khẩu
                </button>

                <a href="#" class="toggle-link" onclick="toggleForms(false); return false;">Quay lại đăng nhập</a>
            </form>

            <div class="form-footer">
                WMS © {{ date('Y') }} – Hệ thống quản lý kho nội bộ
            </div>
        </div>
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

        // Giữ nguyên form đang thao tác sau khi server trả lỗi
        @if (session('activeForm') === 'changePass')
            toggleForms(true);
        @endif
    </script>
</body>

</html>
