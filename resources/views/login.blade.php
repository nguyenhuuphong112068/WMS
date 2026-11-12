<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>Đăng nhập | CMS - Hệ Thống Quản Lý Vật Tư Tiêu Hao</title>

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">

    {{-- Bố cục: thanh trên / thẻ đăng nhập ở giữa / thanh dưới; nền xanh đậm, cảnh kho isometric hai bên + hiệu ứng 3D --}}
    <style>
        :root {
            --primary: #2E7BC4;
            --primary-dark: #1F5E9E;
            --primary-light: #5AA0DE;
            --primary-lighter: #9CC7EE;
            --primary-soft: #EAF3FC;
            --primary-rgb: 46, 123, 196;
            --accent: #17B8D4;
            --accent-rgb: 23, 184, 212;
            --primary-deep: #123E6B;
            --primary-deeper: #0B2C4F;
            --bg-neutral: #F5F9FD;
            --text-main: #1F2A3C;
            --text-muted: #6B7A90;
            --line: #E1E9F2;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(900px 520px at 100% 110%, rgba(var(--accent-rgb), 0.24), transparent 60%),
                radial-gradient(800px 480px at -10% -10%, rgba(var(--primary-rgb), 0.50), transparent 65%),
                linear-gradient(160deg, var(--primary-deep) 0%, var(--primary-deeper) 100%);
            background-attachment: fixed;
            overflow-x: hidden;
        }

        /* lưới chấm kiểu bản vẽ kỹ thuật */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.09) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.3));
            -webkit-mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.3));
            pointer-events: none;
        }

        /* quầng sáng sau thẻ đăng nhập */
        .stage::before {
            content: '';
            position: absolute;
            width: 620px;
            height: 620px;
            max-width: 100%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(var(--accent-rgb), 0.16), transparent 65%);
            pointer-events: none;
        }

        /* ============ THANH TRÊN ============ */
        .topbar {
            position: relative;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 40px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
        }

        .brand .logo-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.22);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand .logo-box img { width: 24px; filter: brightness(0) invert(1); }

        .brand-name { font-size: 1.15rem; font-weight: 800; letter-spacing: 2.5px; color: #fff; line-height: 1; }
        .brand-tag { font-size: 0.68rem; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255, 255, 255, 0.65); margin-top: 3px; }

        .btn-lookup {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-lookup i { color: var(--accent); }

        .btn-lookup:hover {
            color: #fff;
            border-color: rgba(var(--accent-rgb), 0.6);
            background: rgba(255, 255, 255, 0.14);
            transform: translateY(-1px);
        }

        /* ============ VÙNG GIỮA ============ */
        .stage {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px 40px;
        }

        .scene {
            position: absolute;
            bottom: 24px;
            z-index: 0;
            pointer-events: none;
            filter: drop-shadow(0 22px 34px rgba(0, 0, 0, 0.30));
            /* lệch theo chuột (parallax) - dùng thuộc tính translate để không đụng animation transform */
            translate: calc(var(--mx, 0) * -18px) calc(var(--my, 0) * -10px);
            transition: translate 0.5s ease-out;
            animation: sceneIn 1s 0.15s ease-out both;
        }

        .scene-left { left: 3%; width: min(32vw, 470px); }
        .scene-right { right: 4%; width: min(24vw, 340px); animation-delay: 0.35s; }

        /* ============ HIỆU ỨNG ĐỘNG 3D ============ */
        /* thẻ nghiêng nhẹ theo chuột */
        .card-3d {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            transform: perspective(1400px) rotateY(calc(var(--mx, 0) * 3deg)) rotateX(calc(var(--my, 0) * -3deg));
            transition: transform 0.5s ease-out;
        }

        /* khối hộp 3D trong suốt trôi trên nền */
        .cube-field {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            perspective: 900px;
            overflow: hidden;
        }

        .cube-wrap {
            position: absolute;
            width: var(--s);
            height: var(--s);
            translate: calc(var(--mx, 0) * var(--depth) * -26px) calc(var(--my, 0) * var(--depth) * -16px);
            transition: translate 0.6s ease-out;
            animation: cubeDrift calc(var(--dur) * 0.5) ease-in-out var(--delay) infinite;
        }

        .cube {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            animation: cubeSpin var(--dur) linear var(--delay) infinite;
        }

        .cube .f {
            position: absolute;
            inset: 0;
            border: 1px solid rgba(var(--accent-rgb), 0.45);
            background: linear-gradient(135deg, rgba(90, 160, 222, 0.20), rgba(var(--accent-rgb), 0.04));
            box-shadow: inset 0 0 12px rgba(var(--accent-rgb), 0.12);
        }

        .cube .f1 { transform: translateZ(calc(var(--s) / 2)); }
        .cube .f2 { transform: rotateY(180deg) translateZ(calc(var(--s) / 2)); }
        .cube .f3 { transform: rotateY(90deg) translateZ(calc(var(--s) / 2)); }
        .cube .f4 { transform: rotateY(-90deg) translateZ(calc(var(--s) / 2)); }
        .cube .f5 { transform: rotateX(90deg) translateZ(calc(var(--s) / 2)); }
        .cube .f6 { transform: rotateX(-90deg) translateZ(calc(var(--s) / 2)); }

        @keyframes cubeSpin {
            from { transform: rotateX(-24deg) rotateY(0deg) rotateZ(0deg); }
            to { transform: rotateX(336deg) rotateY(360deg) rotateZ(180deg); }
        }

        @keyframes cubeDrift {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-18px); }
        }

        /* thùng hàng lơ lửng + ghim vị trí trong cảnh kho */
        .iso-float { animation: isoFloat 3.6s ease-in-out infinite; }

        .iso-float-shadow {
            transform-box: fill-box;
            transform-origin: center;
            animation: isoShadow 3.6s ease-in-out infinite;
        }

        .iso-ping {
            transform-box: fill-box;
            transform-origin: center;
            animation: isoPing 2.4s ease-out infinite;
        }

        @keyframes isoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes isoShadow {
            0%, 100% { opacity: 0.9; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.84); }
        }

        @keyframes isoPing {
            0% { transform: scale(1); opacity: 0.9; }
            100% { transform: scale(3.4); opacity: 0; }
        }

        @keyframes sceneIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: none; }
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(26px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }

        @keyframes barFlow {
            to { background-position: -200% 0; }
        }

        .auth-card {
            position: relative;
            z-index: 2;
            width: 100%;
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12), 0 30px 70px rgba(4, 20, 40, 0.45);
            overflow: hidden;
            animation: cardIn 0.7s cubic-bezier(0.2, 0.8, 0.2, 1) both;
        }

        /* viền trên có vệt sáng chạy */
        .auth-card::before {
            content: '';
            display: block;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-deep), var(--primary), var(--accent), var(--primary), var(--primary-deep));
            background-size: 200% 100%;
            animation: barFlow 6s linear infinite;
        }

        .card-inner { padding: 30px 36px 32px; }

        .card-head { text-align: center; margin-bottom: 22px; }

        .card-head .mark {
            width: 56px;
            height: 56px;
            margin: 0 auto 14px;
            border-radius: 14px;
            background: linear-gradient(150deg, var(--primary) 0%, var(--primary-deep) 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 8px 18px rgba(var(--primary-rgb), 0.35);
        }

        .card-head .system {
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: var(--primary-deep);
        }

        /* thanh chọn Đăng nhập / Đổi mật khẩu */
        .seg {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 4px;
            margin-bottom: 24px;
            border-radius: 10px;
            background: var(--bg-neutral);
            border: 1px solid var(--line);
        }

        .seg button {
            border: none;
            background: transparent;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.2s ease;
        }

        .seg button:hover { color: var(--primary-dark); }

        .seg button.is-active {
            background: #fff;
            color: var(--primary-dark);
            box-shadow: 0 1px 3px rgba(16, 42, 77, 0.10), 0 2px 8px rgba(var(--primary-rgb), 0.10);
        }

        .card-inner h3 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 4px;
            color: var(--text-main);
        }

        .card-inner .subtitle {
            color: var(--text-muted);
            font-size: 0.86rem;
            margin-bottom: 22px;
        }

        .form-label {
            font-weight: 600;
            color: #3A4659;
            font-size: 0.83rem;
            margin-bottom: 6px;
        }

        .input-shell { position: relative; }

        .input-shell > i.field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #93A4BA;
            font-size: 1rem;
            transition: color 0.2s ease;
        }

        .form-control {
            height: 46px;
            border-radius: 8px;
            padding: 0 44px;
            border: 1px solid #D5E1EE;
            background: #FBFDFF;
            font-size: 0.93rem;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .form-control::placeholder { color: #A9B6C6; }

        .form-control:focus {
            background: #fff;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.12);
        }

        .input-shell:focus-within > i.field-icon { color: var(--primary); }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #A0AEC0;
            font-size: 1rem;
            transition: color 0.2s ease;
        }

        .toggle-password:hover { color: var(--primary); }

        .btn-login {
            width: 100%;
            height: 46px;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 6px 16px rgba(var(--primary-rgb), 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.18);
            transition: all 0.25s ease;
        }

        .btn-login:hover,
        .btn-login:focus {
            color: #fff;
            background: var(--primary-deep);
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(var(--primary-rgb), 0.34);
        }

        .alert {
            border-radius: 8px;
            border: none;
            font-size: 0.85rem;
            padding: 11px 14px;
            margin-bottom: 18px;
        }

        .alert-danger { background: #FDECEC; color: #B42318; border-left: 4px solid #DC2626; }
        .alert-warning { background: #FFF6E5; color: #B25E09; border-left: 4px solid #F59E0B; }

        .card-foot {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            background: var(--bg-neutral);
            border-top: 1px solid var(--line);
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .card-foot i { color: var(--primary); }

        /* ============ THANH DƯỚI ============ */
        .bottombar {
            position: relative;
            z-index: 3;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px 24px;
            padding: 14px 40px 18px;
            font-size: 0.76rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .bottombar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 40px;
            right: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .bottombar .badges { display: flex; flex-wrap: wrap; gap: 8px 18px; }
        .bottombar .badges span { display: inline-flex; align-items: center; gap: 6px; }
        .bottombar .badges i { color: var(--accent); }

        @media (max-width: 1199.98px) {
            .scene { display: none; }
        }

        @media (max-width: 767.98px) {
            .cube-field { display: none; }
            .card-3d { transform: none; }
        }

        /* người dùng bật "giảm chuyển động" trong hệ điều hành -> tắt hiệu ứng */
        @media (prefers-reduced-motion: reduce) {
            .cube, .cube-wrap, .scene, .auth-card, .auth-card::before,
            .iso-float, .iso-float-shadow, .iso-ping { animation: none !important; }
            .card-3d { transform: none; }
            .scene, .cube-wrap { translate: none; }
        }

        @media (max-width: 575.98px) {
            .topbar { padding: 14px 16px; }
            .brand-tag { display: none; }
            .btn-lookup span { display: none; }
            .card-inner { padding: 24px 20px 26px; }
            .bottombar { padding: 12px 16px 16px; justify-content: center; text-align: center; }
            .bottombar::before { left: 16px; right: 16px; }
        }
    </style>
</head>

<body>

    <!-- ============ THANH TRÊN ============ -->
    <header class="topbar">
        <div class="brand">
            <div class="logo-box"><img src="{{ asset('img/iconstella.svg') }}" alt="Logo"></div>
            <div>
                <div class="brand-name">CMS</div>
                <div class="brand-tag">Consumables Management System</div>
            </div>
        </div>
        <a href="{{ route('publicChemicalLookup') }}" class="btn-lookup" title="Tra cứu tồn kho hoá chất, chất chuẩn, vật tư, VPP (không cần đăng nhập)">
            <i class="bi bi-search"></i><span>Tra cứu tồn kho</span>
        </a>
    </header>

    <!-- ============ KHỐI HỘP 3D TRÔI TRÊN NỀN ============ -->
    <div class="cube-field" aria-hidden="true">
        {{-- [trái %, trên %, cạnh px, vòng xoay s, trễ s, độ sâu parallax] --}}
        @foreach ([
            [9, 20, 46, 26, -4, 1.2],
            [24, 9, 20, 18, -9, 0.6],
            [70, 12, 30, 22, -3, 0.8],
            [86, 30, 58, 32, -12, 1.4],
            [94, 17, 18, 16, -2, 0.5],
            [66, 70, 24, 24, -7, 0.9],
            [33, 86, 16, 20, -5, 0.5],
        ] as [$cl, $ct, $cs, $cd, $cdl, $cdp])
            <div class="cube-wrap"
                style="left: {{ $cl }}%; top: {{ $ct }}%; --s: {{ $cs }}px; --dur: {{ $cd }}s; --delay: {{ $cdl }}s; --depth: {{ $cdp }};">
                <div class="cube">
                    <span class="f f1"></span><span class="f f2"></span><span class="f f3"></span>
                    <span class="f f4"></span><span class="f f5"></span><span class="f f6"></span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- ============ VÙNG GIỮA ============ -->
    <main class="stage">

        {{-- Kệ kho 3 tầng --}}
        @include('login.isoScene', [
            'class' => 'scene scene-left',
            'boxes' => [
                [-34, -30, 0, 274, 128, 0, 'floor'],
                [0, 0, 0, 6, 6, 216, 'rack'],
                [0, 0, 8, 200, 56, 4, 'board'],
                [14, 10, 12, 48, 38, 44, 'blue'],
                [70, 14, 12, 40, 34, 32, 'pale'],
                [120, 10, 12, 64, 38, 50, 'pale'],
                [0, 0, 80, 200, 56, 4, 'board'],
                [16, 12, 84, 56, 36, 46, 'pale'],
                [80, 10, 84, 44, 40, 40, 'accent'],
                [134, 14, 84, 50, 34, 54, 'blue'],
                [0, 0, 152, 200, 56, 4, 'board'],
                [20, 10, 156, 40, 36, 36, 'blue'],
                [70, 12, 156, 60, 38, 48, 'pale'],
                [142, 14, 156, 40, 32, 30, 'pale'],
                [0, 0, 212, 200, 56, 4, 'board'],
                [194, 0, 0, 6, 6, 216, 'rack'],
                [0, 50, 0, 6, 6, 216, 'rack'],
                [194, 50, 0, 6, 6, 216, 'rack'],
            ],
            'markers' => [
                [100, 28, 252, 216],
            ],
        ])

        {{-- Pallet hàng chờ nhập kho --}}
        @include('login.isoScene', [
            'class' => 'scene scene-right',
            'boxes' => [
                [-24, -24, 0, 268, 140, 0, 'floor'],
                [0, 0, 0, 110, 90, 10, 'pallet'],
                [4, 4, 10, 50, 40, 40, 'blue'],
                [56, 4, 10, 50, 40, 40, 'pale'],
                [4, 46, 10, 50, 40, 40, 'pale'],
                [56, 46, 10, 50, 40, 40, 'blue'],
                [4, 4, 50, 50, 40, 36, 'pale'],
                [56, 4, 50, 50, 40, 36, 'blue'],
                [4, 46, 50, 50, 40, 36, 'accent'],
                [60, 8, 100, 42, 34, 28, 'pale', 'iso-float', 86],
                [140, 20, 0, 80, 70, 10, 'pallet'],
                [146, 26, 10, 68, 58, 50, 'blue'],
                [160, 40, 60, 40, 30, 26, 'accent'],
            ],
            'markers' => [
                [180, 55, 124, 86],
            ],
        ])

        <div class="card-3d">
        <div class="auth-card">
            <div class="card-inner">
                <div class="card-head">
                    <div class="mark"><i class="bi bi-boxes"></i></div>
                    <div class="system">Hệ Thống Quản Lý Vật Tư Tiêu Hao</div>
                </div>

                <div class="seg" role="tablist">
                    <button type="button" class="is-active" data-auth-tab="login" role="tab" aria-selected="true"
                        onclick="toggleForms(false)">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                    </button>
                    <button type="button" data-auth-tab="changePass" role="tab" aria-selected="false"
                        onclick="toggleForms(true)">
                        <i class="bi bi-key me-1"></i> Đổi mật khẩu
                    </button>
                </div>

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

                <!-- Form đăng nhập -->
                <form id="loginForm" action="{{ route('login') }}" method="POST">
                    @csrf
                    <h3>Xin chào!</h3>
                    <p class="subtitle">Nhập tài khoản và mật khẩu được cấp để tiếp tục.</p>

                    <div class="mb-3">
                        <label for="username" class="form-label">Tên tài khoản</label>
                        <div class="input-shell">
                            <i class="bi bi-person field-icon"></i>
                            <input type="text" id="username" name="username" class="form-control"
                                placeholder="Nhập username" required autofocus autocomplete="username" value="{{ old('username') }}">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="loginPassword" class="form-label">Mật khẩu</label>
                        <div class="input-shell">
                            <i class="bi bi-lock field-icon"></i>
                            <input type="password" id="loginPassword" name="passWord" class="form-control"
                                placeholder="••••••••" required autocomplete="current-password">
                            <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('loginPassword', this)"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login mt-4">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập hệ thống
                    </button>
                </form>

                <!-- Form đổi mật khẩu -->
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
                            <input type="password" id="oldPassword" name="oldPassword" class="form-control" placeholder="••••••••">
                            <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('oldPassword', this)"></i>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mật khẩu mới</label>
                        <div class="input-shell">
                            <i class="bi bi-key field-icon"></i>
                            <input type="password" id="newPassword" name="newPassword" class="form-control" placeholder="••••••••">
                            <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('newPassword', this)"></i>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu mới</label>
                        <div class="input-shell">
                            <i class="bi bi-key-fill field-icon"></i>
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" placeholder="••••••••">
                            <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login mt-2">
                        <i class="bi bi-check2-circle me-1"></i> Cập nhật mật khẩu
                    </button>
                </form>
            </div>

            <div class="card-foot">
                <i class="bi bi-shield-lock"></i>
                Phiên tự đăng xuất sau {{ \App\Support\LoginHistory::IDLE_MINUTES }} phút không thao tác
            </div>
        </div>
        </div>
    </main>

    <!-- ============ THANH DƯỚI ============ -->
    <footer class="bottombar">
        <span>CMS © {{ date('Y') }} · Hệ thống quản lý vật tư tiêu hao nội bộ</span>
        <div class="badges">
            <span><i class="bi bi-shield-check"></i> 21 CFR Part 11</span>
            <span><i class="bi bi-journal-text"></i> Audit Trail</span>
            <span><i class="bi bi-pen"></i> Chữ ký điện tử</span>
        </div>
    </footer>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
        function toggleForms(showChangePass) {
            document.getElementById('loginForm').style.display = showChangePass ? 'none' : 'block';
            document.getElementById('changePassForm').style.display = showChangePass ? 'block' : 'none';
            // Đồng bộ thanh chọn "Đăng nhập / Đổi mật khẩu"
            document.querySelectorAll('[data-auth-tab]').forEach(function (tab) {
                var active = (tab.getAttribute('data-auth-tab') === 'changePass') === showChangePass;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
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

        // Parallax 3D: ghi vị trí chuột (-1..1) vào biến CSS --mx/--my; bỏ qua trên màn hình cảm ứng / giảm chuyển động
        (function () {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !window.matchMedia('(hover: hover)').matches) {
                return;
            }
            var root = document.documentElement, frame = null, mx = 0, my = 0;
            window.addEventListener('mousemove', function (e) {
                mx = (e.clientX / window.innerWidth - 0.5) * 2;
                my = (e.clientY / window.innerHeight - 0.5) * 2;
                if (frame) return;
                frame = requestAnimationFrame(function () {
                    root.style.setProperty('--mx', mx.toFixed(3));
                    root.style.setProperty('--my', my.toFixed(3));
                    frame = null;
                });
            });
            document.addEventListener('mouseleave', function () {
                root.style.setProperty('--mx', 0);
                root.style.setProperty('--my', 0);
            });
        })();
    </script>
</body>

</html>
