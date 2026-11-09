{{--
|--------------------------------------------------------------------------
| TRA CỨU TỒN KHO - TRANG CÔNG KHAI (không cần đăng nhập): HOÁ CHẤT / CHẤT CHUẨN / VẬT TƯ
|--------------------------------------------------------------------------
| Standalone, không dùng layout AdminLTE. Thư viện nạp offline qua asset() (CSS Bootstrap 4 + JS
| Bootstrap 5 như trang đăng nhập). Người dùng chọn 1 phòng ban + gõ (hoặc quét bằng camera) tên / mã /
| mã lô -> xem các VỊ TRÍ đang chứa hàng đó kèm số lượng tồn, theo 3 kiểu như tab "Theo định khu" của
| Tồn Kho: Thẻ (dựng ở đây), 3D từng kệ và Mặt bằng cả kho/phòng (pages.inventory.shared.zone3d).
| Dữ liệu do PublicStockLookupController dựng sẵn ($kind = chemical | standard | material).
--}}
@php
    $zoneTypes = \App\Http\Controllers\General\PublicStockLookupController::zoneTypes();
    $hasZones = $result && count($result['zones']['locations']) > 0;
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>{{ $cfg['title'] }} | CMS</title>

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">
    {{-- icon của sơ đồ 3D dùng chung (pages.shared.shelf3d) --}}
    <link rel="stylesheet" href="{{ asset('dataTable/plugins/fontawesome-free/css/all.min.css') }}">

    <style>
        :root {
            --primary: #2E7BC4;
            --primary-dark: #1F5E9E;
            --primary-light: #5AA0DE;
            --primary-lighter: #9CC7EE;
            --primary-soft: #EAF3FC;
            --primary-rgb: 46, 123, 196;
            --accent: #17B8D4;
            --bg-neutral: #F5F9FD;
            --text-main: #2D3748;
            --text-muted: #718096;
            --border-radius-lg: 12px;
            --border-radius-md: 8px;
            --shadow-sm: 0 1px 3px rgba(var(--primary-rgb), .12);
            --shadow-md: 0 6px 18px rgba(var(--primary-rgb), .16);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-main);
            background: linear-gradient(135deg, #EAF3FC 0%, #D6E8F9 45%, #C3DDF5 100%);
            padding: 28px 16px 60px;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(var(--primary-rgb), .05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--primary-rgb), .05) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .clk-shell {
            position: relative;
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
        }

        /* ---------- Header ---------- */
        .clk-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }

        .clk-brand { display: flex; align-items: center; gap: 12px; }

        .clk-brand .logo-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: linear-gradient(150deg, var(--primary-light), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .clk-brand .logo-box i { color: #fff; font-size: 1.35rem; }
        .clk-brand h1 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--primary);
            text-transform: uppercase;
        }
        .clk-brand span { font-size: .74rem; color: var(--text-muted); letter-spacing: .5px; }

        .clk-login-link {
            margin-left: auto;
            font-size: .84rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            border: 1px solid var(--primary-lighter);
            border-radius: 999px;
            padding: 7px 16px;
            transition: all .2s;
        }
        .clk-login-link:hover { background: var(--primary); color: #fff; border-color: var(--primary); text-decoration: none; }

        /* ---------- Chọn loại hàng ---------- */
        .clk-kinds { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .clk-kind {
            display: inline-flex; align-items: center; gap: 7px; padding: 8px 18px; border-radius: 999px;
            border: 1px solid #DCE7F3; background: #fff; color: var(--text-main); font-weight: 700; font-size: .9rem;
            text-decoration: none; transition: all .2s;
        }
        .clk-kind i { color: var(--primary-light); }
        .clk-kind:hover { border-color: var(--primary-light); color: var(--primary); text-decoration: none; transform: translateY(-1px); }
        .clk-kind.is-active { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: var(--shadow-md); }
        .clk-kind.is-active i { color: #fff; }

        /* ---------- Panel ---------- */
        .clk-panel {
            background: #fff;
            border: 1px solid #DCE7F3;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 22px 24px;
            margin-bottom: 18px;
        }

        .clk-form { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; }
        .clk-field { display: flex; flex-direction: column; gap: 5px; }
        .clk-field.dep { flex: 1 1 320px; }
        .clk-field.kw { flex: 1 1 320px; }
        .clk-field label {
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0;
        }
        .clk-field .form-control {
            height: auto;
            border-radius: var(--border-radius-md);
            border: 1px solid #DDE7F2;
            background: #F8FBFF;
            padding: 11px 14px;
            font-size: .95rem;
        }
        .clk-field .form-control:focus {
            border-color: var(--primary-light);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), .12);
            outline: none;
        }
        .clk-kw-row { display: flex; gap: 6px; }
        .clk-kw-row .form-control { flex: 1 1 auto; min-width: 0; }
        .clk-cam {
            flex: none; width: 48px; border-radius: var(--border-radius-md); border: 1px solid var(--primary-lighter);
            background: #fff; color: var(--primary); font-size: 1.1rem; transition: all .2s;
        }
        .clk-cam:hover:not(:disabled) { background: var(--primary-soft); }
        .clk-cam:disabled { opacity: .45; cursor: not-allowed; }

        .clk-btn {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 60%, var(--primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: var(--border-radius-md);
            padding: 11px 24px;
            font-weight: 700;
            letter-spacing: .5px;
            box-shadow: 0 6px 16px rgba(var(--primary-rgb), .28);
            transition: all .25s;
            white-space: nowrap;
        }
        .clk-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(var(--primary-rgb), .36); }

        .clk-hint {
            background: var(--primary-soft);
            border-left: 3px solid var(--primary-light);
            border-radius: var(--border-radius-md);
            padding: 12px 16px;
            font-size: .86rem;
            color: var(--text-main);
        }
        .clk-warn {
            background: #FFF6E5;
            border-left: 3px solid #F59E0B;
            border-radius: var(--border-radius-md);
            padding: 12px 16px;
            font-size: .88rem;
            color: #B25E09;
        }

        /* ---------- Thanh tổng quan + công cụ (zone3d chèn nút Thẻ | 3D | Mặt bằng vào .mz-tools) ---------- */
        .mz-head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .clk-stats { display: flex; flex-wrap: wrap; gap: 10px; }
        .clk-stat {
            background: #fff; border: 1px solid #DCE7F3; border-radius: var(--border-radius-md);
            padding: 8px 16px; min-width: 92px;
        }
        .clk-stat b { display: block; font-size: 1.3rem; font-weight: 700; color: var(--primary); line-height: 1.15; }
        .clk-stat span { font-size: .66rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #64748B; }
        .mz-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }

        .mz-search { position: relative; }
        .mz-search input { padding-left: 32px; min-width: 260px; border-radius: var(--border-radius-md); border: 1px solid #DDE7F2; font-size: .9rem; }
        .mz-search > i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--primary-lighter); }

        .clk-scope { font-size: .82rem; color: var(--text-muted); margin-bottom: 12px; }
        .clk-scope b { color: var(--primary-dark); }

        /* ---------- Kho ---------- */
        .clk-wh {
            background: #fff; border: 1px solid #DCE7F3; border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 16px;
        }
        .clk-wh-head {
            display: flex; align-items: center; gap: 12px; padding: 13px 18px; cursor: pointer;
            background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff;
        }
        .clk-wh-head .ic {
            width: 34px; height: 34px; flex: none; border-radius: 10px; background: rgba(255, 255, 255, .18);
            display: flex; align-items: center; justify-content: center;
        }
        .clk-wh-title { font-weight: 700; letter-spacing: .4px; }
        .clk-wh-sub { font-size: .72rem; opacity: .88; }
        .clk-wh-meta { margin-left: auto; display: flex; align-items: center; gap: 6px; }
        .clk-pill { background: rgba(255, 255, 255, .2); border-radius: 999px; padding: 3px 11px; font-size: .72rem; font-weight: 700; white-space: nowrap; }
        .clk-pill.is-alert { background: #FEF3C7; color: #92400E; }
        .clk-caret { transition: transform .25s; }
        .clk-wh.is-closed .clk-caret { transform: rotate(-90deg); }
        .clk-wh.is-closed .clk-wh-body { display: none; }
        .clk-wh-body { padding: 16px 18px 4px; }

        /* ---------- Kệ / Tủ ---------- */
        .clk-shelf { border-left: 3px solid var(--primary-lighter); padding-left: 14px; margin-bottom: 16px; }
        .clk-shelf-head { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px; color: var(--primary-dark); font-weight: 700; }
        .clk-shelf-head .tag { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 2px 10px; font-size: .7rem; font-weight: 700; }

        /* ---------- Cột ---------- */
        .clk-column { background: var(--bg-neutral); border: 1px solid #E6EEF7; border-radius: var(--border-radius-md); padding: 12px 14px; margin-bottom: 12px; }
        .clk-column-head { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px; font-size: .88rem; font-weight: 700; color: var(--text-main); }
        .clk-column-head .sub { margin-left: auto; font-size: .72rem; font-weight: 600; color: #64748B; }

        /* ---------- Tầng ---------- */
        .clk-tier { margin-bottom: 12px; }
        .clk-tier:last-child { margin-bottom: 0; }
        .clk-tier-head {
            display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 8px;
            font-size: .76rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: .4px;
        }
        .clk-tier-head .sub { margin-left: auto; font-size: .7rem; font-weight: 600; color: #94A3B8; text-transform: none; letter-spacing: 0; }

        /* ---------- Ô vị trí ---------- */
        .clk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 10px; }
        .clk-cell {
            display: block; width: 100%; text-align: left; background: #fff; padding: 11px 13px;
            border: 1px solid #DCE7F3; border-top: 3px solid #16A34A; border-radius: 10px; transition: all .2s; cursor: pointer;
        }
        .clk-cell.is-warn { border-top-color: #F59E0B; }
        .clk-cell.is-danger { border-top-color: #DC2626; }
        .clk-cell:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }

        .clk-cell-top { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; }
        .clk-cell-code { font-weight: 700; color: var(--primary-dark); font-size: .86rem; }
        .clk-cell-lots {
            margin-left: auto; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .72rem; font-weight: 700; background: var(--primary-soft); color: var(--primary);
        }
        .clk-cell-path { font-size: .68rem; color: #94A3B8; margin-bottom: 7px; }
        .clk-cell-items { font-size: .74rem; color: #64748B; line-height: 1.5; }
        .clk-cell-items > div { display: flex; align-items: baseline; gap: 8px; }
        .clk-cell-items .nm { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .clk-cell-items .qty { margin-left: auto; flex: none; font-weight: 700; color: var(--primary-dark); }
        .clk-cell-items .qty .u { font-weight: 600; color: #94A3B8; }
        .clk-cell-foot { margin-top: 7px; padding-top: 6px; border-top: 1px dashed #E2E8F0; font-size: .7rem; color: #94A3B8; font-weight: 600; }

        /* ---------- Khối chưa xếp vị trí + rỗng ---------- */
        .clk-unzoned { border: 1px dashed #F59E0B; background: #FFFBEB; border-radius: var(--border-radius-lg); padding: 14px 18px; margin-bottom: 16px; }
        .clk-unzoned-head { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #B45309; margin-bottom: 10px; }
        .clk-uz-card { background: #fff; border: 1px solid #FDE8C7; border-radius: 10px; padding: 10px 12px; }
        .clk-uz-card .nm { font-weight: 700; color: var(--primary-dark); font-size: .84rem; }
        .clk-uz-card .qty { font-size: .8rem; color: var(--text-main); margin-top: 3px; }
        .clk-uz-card .qty b { color: var(--primary-dark); }
        .clk-uz-card .ex { font-size: .7rem; color: #94A3B8; margin-top: 4px; }

        .clk-empty { text-align: center; padding: 44px 20px; color: #94A3B8; background: #fff; border: 1px solid #DCE7F3; border-radius: var(--border-radius-lg); }
        .clk-empty i { font-size: 2.2rem; color: var(--primary-lighter); margin-bottom: 10px; display: block; }
        .mz-noresult { display: none; }

        .clk-footer { text-align: center; font-size: .74rem; color: #94A3B8; margin-top: 26px; }

        /* ---------- Modal chi tiết ---------- */
        .clk-modal-table th { background: var(--primary-soft); color: var(--primary); font-size: .78rem; }
        .clk-modal-table td { font-size: .84rem; }
        .clk-modal-table .lots { font-size: .72rem; color: #64748B; word-break: break-all; }
        #clkDetailModal .modal-content, #cameraScanModal .modal-content { border-radius: var(--border-radius-lg); }
        #cameraScanModal .md-sub { display: block; margin-top: 8px; color: var(--text-muted); }

        @media (max-width: 640px) {
            .clk-form { flex-direction: column; align-items: stretch; }
            .clk-btn { width: 100%; }
            .mz-search input { min-width: 0; width: 100%; }
            .mz-tools, .mz-search { width: 100%; }
        }
    </style>
</head>

<body>
    <div class="clk-shell">

        <div class="clk-top">
            <div class="clk-brand">
                <div class="logo-box"><i class="bi bi-box-seam"></i></div>
                <div>
                    <h1>{{ $cfg['title'] }}</h1>
                    <span>CMS – Hệ Thống Quản Lý Vật Tư Tiêu Hao</span>
                </div>
            </div>
            <a href="{{ route('login') }}" class="clk-login-link">
                <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập hệ thống
            </a>
        </div>

        {{-- ---------- Loại hàng: giữ nguyên phòng ban + từ khoá khi chuyển ---------- --}}
        <nav class="clk-kinds" aria-label="Loại hàng tra cứu">
            @foreach ($kinds as $key => $k)
                <a href="{{ route($k['route'], array_filter(['department_id' => $departmentId ?: null, 'q' => $keyword !== '' ? $keyword : null])) }}"
                    class="clk-kind {{ $key === $kind ? 'is-active' : '' }}">
                    <i class="bi {{ $k['icon'] }}"></i> {{ $k['tab'] }}
                </a>
            @endforeach
        </nav>

        {{-- ---------- Bộ lọc tra cứu ---------- --}}
        <div class="clk-panel">
            <form method="GET" action="{{ route($cfg['route']) }}" class="clk-form" id="clkForm">
                <div class="clk-field dep">
                    <label for="department_id">Phòng ban <span style="color:#DC2626">*</span></label>
                    <select name="department_id" id="department_id" class="form-control" required>
                        <option value="">— Chọn phòng ban —</option>
                        @php $currentCompany = false; @endphp
                        @foreach ($departments as $dep)
                            @if ($dep->company_short_name !== $currentCompany)
                                @if ($currentCompany !== false)</optgroup>@endif
                                @php $currentCompany = $dep->company_short_name; @endphp
                                <optgroup label="{{ $dep->company_short_name ?: 'Chưa gán công ty' }}">
                            @endif
                            <option value="{{ $dep->id }}" {{ $departmentId === $dep->id ? 'selected' : '' }}>
                                {{ $dep->name }}{{ $dep->shortName ? ' (' . $dep->shortName . ')' : '' }}
                            </option>
                        @endforeach
                        @if ($currentCompany !== false)</optgroup>@endif
                    </select>
                </div>

                <div class="clk-field kw">
                    <label for="q">Tên, mã hoặc {{ mb_strtolower($cfg['lot']) }}</label>
                    {{-- scan-box + .btn-camera-scan: pages.shared.cameraScan điền mã quét được rồi bấm Enter --}}
                    <div class="clk-kw-row scan-box">
                        <input type="text" name="q" id="q" class="form-control" value="{{ $keyword }}" autocomplete="off"
                            placeholder="Bỏ trống để xem toàn bộ {{ $cfg['item'] }} của phòng" maxlength="120">
                        <button type="button" class="clk-cam btn-camera-scan" title="Quét mã trên nhãn bằng camera">
                            <i class="bi bi-camera"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="clk-btn">
                    <i class="bi bi-search me-1"></i> Tra cứu
                </button>
            </form>
        </div>

        {{-- ---------- Kết quả ---------- --}}
        @if (!$submitted)
            <div class="clk-hint">
                <i class="bi bi-info-circle me-1"></i>
                Chọn <b>phòng ban</b>, nhập (hoặc quét mã trên nhãn bằng nút <i class="bi bi-camera"></i>) tên, mã {{ $cfg['item'] }}
                hay {{ mb_strtolower($cfg['lot']) }} rồi bấm <b>Tra cứu</b> để xem hàng đang nằm ở <b>Kho / Phòng / Kệ-Tủ / Vị trí</b>
                nào và còn tồn bao nhiêu. Kết quả xem được dạng <b>Thẻ</b>, <b>3D</b> từng kệ/tủ hoặc <b>Mặt bằng</b> cả kho/phòng -
                vị trí khớp nhấp nháy để dễ tìm. Số liệu lấy trực tiếp từ hệ thống, cập nhật theo thời gian thực.
            </div>
        @elseif (!$department)
            <div class="clk-warn">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Vui lòng chọn một phòng ban hợp lệ để tra cứu.
            </div>
        @else
            @php $t = $result['totals']; @endphp

            <div class="clk-scope">
                Kết quả cho phòng <b>{{ $department->name }}</b>@if ($department->company_short_name) · {{ $department->company_short_name }}@endif
                @if ($keyword !== '') · từ khoá "<b>{{ $keyword }}</b>"@endif
            </div>

            <div class="mz-root" id="clkZone" data-zone-types="{{ json_encode(array_map(fn ($z) => ['bg' => $z['bg']], $zoneTypes)) }}"
                data-z3d-keyword="{{ mb_strtolower($keyword) }}">
                <div class="mz-head">
                    <div class="clk-stats">
                        <div class="clk-stat"><b>{{ $t['warehouses'] }}</b><span>Kho / Phòng</span></div>
                        <div class="clk-stat"><b>{{ $t['shelves'] }}</b><span>Kệ / Tủ</span></div>
                        <div class="clk-stat"><b>{{ $t['locations'] }}</b><span>Vị trí có hàng</span></div>
                        <div class="clk-stat"><b>{{ $t['lots'] }}</b><span>Mã lô</span></div>
                        <div class="clk-stat"><b>{{ $t['items'] }}</b><span>{{ $cfg['tab'] }}</span></div>
                        @if ($t['unzoned'] > 0)
                            <div class="clk-stat"><b>{{ $t['unzoned'] }}</b><span>Lô chưa xếp vị trí</span></div>
                        @endif
                    </div>
                    <div class="mz-tools">
                        <div class="mz-search">
                            <i class="bi bi-search"></i>
                            <input type="text" id="clkFilter" class="form-control form-control-sm" autocomplete="off"
                                placeholder="Lọc nhanh: vị trí, kệ/tủ, tên, mã...">
                        </div>
                    </div>
                </div>

                {{-- Lô còn tồn nhưng chưa gán vị trí (ẩn ở chế độ 3D / Mặt bằng) --}}
                @if (count($result['unzoned']) > 0)
                    <div class="clk-unzoned mz-unzoned">
                        <div class="clk-unzoned-head">
                            <i class="bi bi-exclamation-triangle"></i> Chưa xếp vị trí
                            <span class="clk-pill is-alert">{{ count($result['unzoned']) }} {{ $cfg['item'] }}</span>
                        </div>
                        <div class="clk-grid">
                            @foreach ($result['unzoned'] as $uz)
                                <div class="clk-uz-card mz-cell"
                                    data-search="{{ mb_strtolower($uz['name'] . ' ' . $uz['code'] . ' ' . implode(' ', $uz['lotCodes'])) }}">
                                    <div class="nm">{{ $uz['name'] }}</div>
                                    <div class="qty">Tồn: <b>{{ $uz['remaining'] }} {{ $uz['unit'] }}</b> · {{ $uz['lots'] }} lô</div>
                                    <div class="ex">Hạn dùng gần nhất: {{ $uz['expiry'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mz-body">
                    @if (count($result['warehouses']) === 0 && count($result['unzoned']) === 0)
                        <div class="clk-empty">
                            <i class="bi bi-inbox"></i>
                            Không tìm thấy {{ $cfg['item'] }} còn tồn khớp với điều kiện tra cứu ở phòng ban này.
                        </div>
                    @endif

                    @foreach ($result['warehouses'] as $wh)
                        @php
                            $whLots = 0; $whLoc = 0;
                            foreach ($wh['shelves'] as $sh) {
                                foreach ($sh['columns'] as $cl) {
                                    foreach ($cl['tiers'] as $tr) {
                                        $whLoc += count($tr['locations']);
                                        foreach ($tr['locations'] as $lc) { $whLots += $lc['stat']['lots']; }
                                    }
                                }
                            }
                        @endphp
                        <section class="clk-wh">
                            <div class="clk-wh-head">
                                <i class="bi bi-chevron-down clk-caret"></i>
                                <span class="ic"><i class="bi bi-building"></i></span>
                                <div>
                                    <div class="clk-wh-title">{{ $wh['name'] }}</div>
                                    <div class="clk-wh-sub">{{ count($wh['shelves']) }} kệ/tủ · {{ $whLoc }} vị trí có hàng</div>
                                </div>
                                <div class="clk-wh-meta">
                                    <span class="clk-pill">{{ $whLots }} lô</span>
                                </div>
                            </div>

                            <div class="clk-wh-body">
                                @foreach ($wh['shelves'] as $shelf)
                                    <div class="clk-shelf">
                                        <div class="clk-shelf-head">
                                            <i class="bi bi-stack"></i> {{ $shelf['name'] }}
                                            <span class="tag">{{ count($shelf['columns']) }} cột</span>
                                        </div>

                                        @foreach ($shelf['columns'] as $column)
                                            <div class="clk-column">
                                                <div class="clk-column-head">
                                                    <i class="bi bi-grid-3x3-gap" style="color: var(--primary-light)"></i>
                                                    {{ $column['name'] }}
                                                    <span class="sub">{{ count($column['tiers']) }} tầng</span>
                                                </div>

                                                @foreach ($column['tiers'] as $tier)
                                                    <div class="clk-tier">
                                                        <div class="clk-tier-head">
                                                            <i class="bi bi-list"></i> {{ $tier['name'] }}
                                                            <span class="sub">{{ count($tier['locations']) }} vị trí có hàng</span>
                                                        </div>

                                                        <div class="clk-grid">
                                                            @foreach ($tier['locations'] as $loc)
                                                                @php $node = $result['nodes'][$loc['key']] ?? null; @endphp
                                                                <button type="button"
                                                                    class="clk-cell {{ $node && $node['state'] !== 'ok' ? 'is-' . $node['state'] : '' }}"
                                                                    data-key="{{ $loc['key'] }}" data-search="{{ $loc['search'] }}">
                                                                    <div class="clk-cell-top">
                                                                        <span class="clk-cell-code">{{ $loc['code'] }}</span>
                                                                        <span class="clk-cell-lots">{{ $loc['stat']['lots'] }}</span>
                                                                    </div>
                                                                    <div class="clk-cell-path">{{ $loc['path'] }}</div>
                                                                    <div class="clk-cell-items">
                                                                        @foreach ($loc['preview'] as $item)
                                                                            <div>
                                                                                <span class="nm">· {{ $item['name'] }}</span>
                                                                                <span class="qty">{{ $item['amount'] }}
                                                                                    <span class="u">{{ $item['unit'] }}</span></span>
                                                                            </div>
                                                                        @endforeach
                                                                        @if ($loc['stat']['items'] > count($loc['preview']))
                                                                            <div><span class="nm">·
                                                                                    +{{ $loc['stat']['items'] - count($loc['preview']) }}
                                                                                    {{ $cfg['item'] }} khác</span></div>
                                                                        @endif
                                                                    </div>
                                                                    <div class="clk-cell-foot">
                                                                        {{ $loc['stat']['items'] }} {{ $cfg['item'] }} · {{ $loc['stat']['lots'] }} lô — bấm xem chi tiết
                                                                    </div>
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <div class="clk-empty mz-noresult">
                    <i class="bi bi-search"></i>
                    Không có vị trí nào khớp với từ khoá lọc.
                </div>
            </div>
        @endif

        <div class="clk-footer">
            CMS © {{ date('Y') }} – {{ $cfg['title'] }} (chỉ đọc)
        </div>
    </div>

    {{-- ---------- Modal chi tiết một vị trí ---------- --}}
    <div class="modal fade" id="clkDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-geo-alt-fill me-1" style="color: var(--primary)"></i>
                        <span id="clkDName">Vị trí</span></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Đóng"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <small class="text-muted">Đường dẫn định khu</small>
                            <div class="font-weight-bold" id="clkDPath">—</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Mã lô</small>
                            <div class="font-weight-bold" id="clkDLots">0</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">{{ $cfg['tab'] }}</small>
                            <div class="font-weight-bold" id="clkDCount">0</div>
                        </div>
                    </div>
                    <table class="table table-bordered table-sm clk-modal-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:46px">STT</th>
                                <th>{{ $cfg['tab'] }}</th>
                                <th class="text-right" style="width:140px">Tồn</th>
                                <th class="text-center" style="width:70px">Số lô</th>
                                <th class="text-center" style="width:110px">Hạn gần nhất</th>
                            </tr>
                        </thead>
                        <tbody id="clkDBody"></tbody>
                    </table>
                    <div class="clk-hint">
                        <i class="bi bi-info-circle me-1"></i>
                        Chỉ liệt kê {{ $cfg['item'] }} <b>còn tồn</b> tại vị trí này, tồn cộng dồn từ các lô.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('dataTable/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    @include('pages.shared.cameraScan')
    @include('pages.inventory.shared.zone3d')
    <script>
        $(function () {
            var nodes = @json($result ? (object) $result['nodes'] : new \stdClass);
            var lotLabel = @json($cfg['lot']);

            // Quét mã bằng camera (pages.shared.cameraScan) điền vào ô rồi bắn phím Enter giả -> gửi form tra cứu
            $('#q').on('keydown', function (e) {
                if (e.key === 'Enter' && !e.originalEvent && $('#department_id').val()) $('#clkForm').trigger('submit');
            });

            // Lọc nhanh trong kết quả: ẩn ô + ẩn luôn kệ/phòng/kho rỗng (3D / Mặt bằng đọc cùng ô này)
            function clkApply() {
                var q = ($('#clkFilter').val() || '').toString().trim().toLowerCase();

                $('.clk-cell, .clk-uz-card').each(function () {
                    var ok = q === '' || ($(this).attr('data-search') || '').indexOf(q) >= 0;
                    this.style.display = ok ? '' : 'none';
                });
                function shown($els) { return $els.filter(function () { return this.style.display !== 'none'; }).length; }
                $('.clk-tier').each(function () { this.style.display = shown($(this).find('.clk-cell')) ? '' : 'none'; });
                $('.clk-column').each(function () { this.style.display = shown($(this).find('.clk-tier')) ? '' : 'none'; });
                $('.clk-shelf').each(function () { this.style.display = shown($(this).find('.clk-column')) ? '' : 'none'; });
                $('.clk-wh').each(function () { this.style.display = shown($(this).find('.clk-shelf')) ? '' : 'none'; });
                $('.clk-unzoned').each(function () { this.style.display = shown($(this).find('.clk-uz-card')) ? '' : 'none'; });

                var total = $('.clk-wh, .clk-unzoned').length;
                $('.mz-noresult').css('display', total > 0 && shown($('.clk-wh, .clk-unzoned')) === 0 ? 'block' : 'none');
            }
            $('#clkFilter').on('input', clkApply);

            // Thu gọn / mở từng kho
            $(document).on('click', '.clk-wh-head', function () {
                $(this).closest('.clk-wh').toggleClass('is-closed');
            });

            // Một vị trí -> xem đủ mặt hàng đang nằm ở đó (bấm thẻ hoặc thùng hàng trên 3D)
            var clkModal = new bootstrap.Modal(document.getElementById('clkDetailModal'));
            function openLocation(key) {
                var d = nodes[key];
                if (!d) return;

                $('#clkDName').text(d.code || '—');
                $('#clkDPath').text(d.path || '—');
                $('#clkDLots').text(d.lots);
                $('#clkDCount').text(d.count);

                var $tb = $('#clkDBody').empty();
                (d.items || []).forEach(function (x, i) {
                    var $tr = $('<tr></tr>');
                    $tr.append($('<td class="text-center"></td>').text(i + 1));
                    var $nm = $('<td></td>').append($('<div class="font-weight-bold"></div>').text(x.name));
                    if (x.code) $nm.append($('<div class="small text-muted"></div>').text(x.code));
                    if (x.lotCodes && x.lotCodes.length) $nm.append($('<div class="lots"></div>').text(lotLabel + ': ' + x.lotCodes.join(', ')));
                    $tr.append($nm);
                    $tr.append($('<td class="text-right font-weight-bold"></td>').text(x.remaining + ' ' + (x.unit || '')));
                    $tr.append($('<td class="text-center"></td>').text(x.lots));
                    $tr.append($('<td class="text-center small"></td>').text(x.expiry));
                    $tb.append($tr);
                });
                clkModal.show();
            }
            $(document).on('click', '.clk-cell[data-key]', function () { openLocation($(this).attr('data-key')); });

            // Thẻ | 3D | Mặt bằng như tab "Theo định khu" của Tồn Kho. Từ khoá tra cứu (data-z3d-keyword) làm
            // các vị trí khớp sáng lên + nhấp nháy, khung 3D tự phóng tới đó.
            // Phòng ban chưa khai định khu nào thì chỉ có kiểu Thẻ (không hiện nút 3D / Mặt bằng).
            var root = document.getElementById('clkZone');
            if (root && window.invZone3d && @json($hasZones)) {
                window.invZone3d.init(root, {
                    zones: @json($result ? $result['zones'] : new \stdClass),
                    nodes: nodes,
                    open: openLocation,
                    zoneLabels: @json(array_map(fn ($z) => $z['label'], $zoneTypes)),
                    modeKey: 'wms.lookup.zoneView',
                    defaultMode: @json($hasZones ? 'room' : 'cards')
                });
            }
        });
    </script>
</body>

</html>
