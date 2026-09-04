{{--
|--------------------------------------------------------------------------
| TRA CỨU TỒN KHO HOÁ CHẤT - TRANG CÔNG KHAI (không cần đăng nhập)
|--------------------------------------------------------------------------
| Standalone, không dùng layout AdminLTE. Thư viện nạp offline qua asset().
| Người dùng chọn 1 phòng ban + gõ tên/mã hoá chất -> xem các VỊ TRÍ đang chứa
| hoá chất đó kèm số lượng tồn, trình bày dạng thẻ (card - grid).
| Dữ liệu do PublicChemicalLookupController::index() dựng sẵn.
--}}
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>Tra cứu tồn kho hoá chất | WMS</title>

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
            max-width: 1180px;
            margin: 0 auto;
        }

        /* ---------- Header ---------- */
        .clk-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
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
        .clk-login-link:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

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
        }
        .clk-field .form-control, .clk-field .form-select {
            border-radius: var(--border-radius-md);
            border: 1px solid #DDE7F2;
            background: #F8FBFF;
            padding: 11px 14px;
            font-size: .95rem;
        }
        .clk-field .form-control:focus, .clk-field .form-select:focus {
            border-color: var(--primary-light);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), .12);
            outline: none;
        }

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

        /* ---------- Thanh tổng quan ---------- */
        .clk-head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .clk-stats { display: flex; flex-wrap: wrap; gap: 10px; }
        .clk-stat {
            background: var(--primary-soft); border: 1px solid #DCE7F3; border-radius: var(--border-radius-md);
            padding: 8px 16px; min-width: 92px;
        }
        .clk-stat b { display: block; font-size: 1.3rem; font-weight: 700; color: var(--primary); line-height: 1.15; }
        .clk-stat span { font-size: .66rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #64748B; }

        .clk-search { position: relative; }
        .clk-search input { padding-left: 32px; min-width: 240px; border-radius: var(--border-radius-md); border: 1px solid #DDE7F2; padding-top: 7px; padding-bottom: 7px; font-size: .9rem; }
        .clk-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--primary-lighter); }

        .clk-scope {
            font-size: .82rem; color: var(--text-muted); margin-bottom: 12px;
        }
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

        /* ---------- Phòng ---------- */
        .clk-room { border-left: 3px solid var(--primary-lighter); padding-left: 14px; margin-bottom: 16px; }
        .clk-room-head { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px; color: var(--primary-dark); font-weight: 700; }
        .clk-room-head .tag { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 2px 10px; font-size: .7rem; font-weight: 700; }

        /* ---------- Kệ / Tủ ---------- */
        .clk-shelf { background: var(--bg-neutral); border: 1px solid #E6EEF7; border-radius: var(--border-radius-md); padding: 12px 14px; margin-bottom: 12px; }
        .clk-shelf-head { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px; font-size: .88rem; font-weight: 700; color: var(--text-main); }
        .clk-shelf-head .sub { margin-left: auto; font-size: .72rem; font-weight: 600; color: #64748B; }

        /* ---------- Ô vị trí ---------- */
        .clk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 10px; }
        .clk-cell {
            display: block; width: 100%; text-align: left; background: #fff; padding: 11px 13px;
            border: 1px solid #DCE7F3; border-top: 3px solid #16A34A; border-radius: 10px; transition: all .2s; cursor: pointer;
        }
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
        .clk-noresult { display: none; }

        .clk-footer { text-align: center; font-size: .74rem; color: #94A3B8; margin-top: 26px; }

        /* ---------- Modal chi tiết ---------- */
        .clk-modal-table th { background: var(--primary-soft); color: var(--primary); font-size: .78rem; }
        .clk-modal-table td { font-size: .84rem; }

        @media (max-width: 640px) {
            .clk-form { flex-direction: column; align-items: stretch; }
            .clk-btn { width: 100%; }
        }
    </style>
</head>

<body>
    <div class="clk-shell">

        <div class="clk-top">
            <div class="clk-brand">
                <div class="logo-box"><i class="bi bi-box-seam"></i></div>
                <div>
                    <h1>Tra cứu tồn kho hoá chất</h1>
                    <span>WMS – Hệ Thống Quản Lý Kho</span>
                </div>
            </div>
            <a href="{{ route('login') }}" class="clk-login-link">
                <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập hệ thống
            </a>
        </div>

        {{-- ---------- Bộ lọc tra cứu ---------- --}}
        <div class="clk-panel">
            <form method="GET" action="{{ route('publicChemicalLookup') }}" class="clk-form">
                <div class="clk-field dep">
                    <label for="department_id">Phòng ban <span style="color:#DC2626">*</span></label>
                    <select name="department_id" id="department_id" class="form-select" required>
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
                    <label for="q">Tên hoặc mã hoá chất</label>
                    <input type="text" name="q" id="q" class="form-control" value="{{ $keyword }}"
                        placeholder="Bỏ trống để xem toàn bộ hoá chất của phòng" maxlength="120">
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
                Chọn <b>phòng ban</b>, nhập tên (hoặc mã) hoá chất rồi bấm <b>Tra cứu</b> để xem hoá chất đó đang nằm ở
                <b>Kho / Phòng / Kệ-Tủ / Vị trí</b> nào và còn tồn bao nhiêu. Số liệu lấy trực tiếp từ hệ thống, cập nhật theo thời gian thực.
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

            @if (count($result['warehouses']) === 0 && count($result['unzoned']) === 0)
                <div class="clk-empty">
                    <i class="bi bi-inbox"></i>
                    Không tìm thấy hoá chất còn tồn khớp với điều kiện tra cứu ở phòng ban này.
                </div>
            @else
                <div class="clk-head">
                    <div class="clk-stats">
                        <div class="clk-stat"><b>{{ $t['warehouses'] }}</b><span>Kho</span></div>
                        <div class="clk-stat"><b>{{ $t['rooms'] }}</b><span>Phòng</span></div>
                        <div class="clk-stat"><b>{{ $t['shelves'] }}</b><span>Kệ / Tủ</span></div>
                        <div class="clk-stat"><b>{{ $t['locations'] }}</b><span>Vị trí có hàng</span></div>
                        <div class="clk-stat"><b>{{ $t['lots'] }}</b><span>Mã lô</span></div>
                        <div class="clk-stat"><b>{{ $t['chemicals'] }}</b><span>Hoá chất</span></div>
                    </div>
                    <div class="clk-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="clkFilter" class="form-control form-control-sm"
                            placeholder="Lọc nhanh trong kết quả: vị trí, kệ/tủ, tên hoá chất...">
                    </div>
                </div>

                {{-- Lô còn tồn nhưng chưa gán vị trí --}}
                @if (count($result['unzoned']) > 0)
                    <div class="clk-unzoned">
                        <div class="clk-unzoned-head">
                            <i class="bi bi-exclamation-triangle"></i> Chưa xếp vị trí
                            <span class="clk-pill is-alert">{{ count($result['unzoned']) }} hoá chất</span>
                        </div>
                        <div class="clk-grid">
                            @foreach ($result['unzoned'] as $uz)
                                <div class="clk-uz-card" data-search="{{ mb_strtolower($uz['name']) }}">
                                    <div class="nm">{{ $uz['name'] }}</div>
                                    <div class="qty">Tồn: <b>{{ $uz['remaining'] }} {{ $uz['unit'] }}</b> · {{ $uz['lots'] }} lô</div>
                                    <div class="ex">Hạn dùng gần nhất: {{ $uz['expiry'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @foreach ($result['warehouses'] as $wh)
                    @php
                        $whLots = 0; $whLoc = 0;
                        foreach ($wh['rooms'] as $rm) {
                            foreach ($rm['shelves'] as $sh) {
                                $whLoc += count($sh['locations']);
                                foreach ($sh['locations'] as $lc) { $whLots += $lc['stat']['lots']; }
                            }
                        }
                    @endphp
                    <section class="clk-wh">
                        <div class="clk-wh-head">
                            <i class="bi bi-chevron-down clk-caret"></i>
                            <span class="ic"><i class="bi bi-building"></i></span>
                            <div>
                                <div class="clk-wh-title">{{ $wh['name'] }}</div>
                                <div class="clk-wh-sub">{{ count($wh['rooms']) }} phòng · {{ $whLoc }} vị trí có hàng</div>
                            </div>
                            <div class="clk-wh-meta">
                                <span class="clk-pill">{{ $whLots }} lô</span>
                            </div>
                        </div>

                        <div class="clk-wh-body">
                            @foreach ($wh['rooms'] as $room)
                                <div class="clk-room">
                                    <div class="clk-room-head">
                                        <i class="bi bi-door-open"></i> {{ $room['name'] }}
                                        <span class="tag">{{ count($room['shelves']) }} kệ/tủ</span>
                                    </div>

                                    @foreach ($room['shelves'] as $shelf)
                                        <div class="clk-shelf">
                                            <div class="clk-shelf-head">
                                                <i class="bi bi-grid-3x3-gap" style="color: var(--primary-light)"></i>
                                                {{ $shelf['name'] }}
                                                <span class="sub">{{ count($shelf['locations']) }} vị trí có hàng</span>
                                            </div>

                                            <div class="clk-grid">
                                                @foreach ($shelf['locations'] as $loc)
                                                    @php
                                                        $cellSearch = mb_strtolower(
                                                            trim(
                                                                $loc['code'] . ' ' . $shelf['name'] . ' ' . $room['name'] . ' ' .
                                                                    implode(' ', array_column($loc['preview'], 'name')),
                                                            ),
                                                        );
                                                    @endphp
                                                    <button type="button" class="clk-cell" data-key="{{ $loc['key'] }}"
                                                        data-search="{{ $cellSearch }}">
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
                                                            @if ($loc['stat']['chemicals'] > count($loc['preview']))
                                                                <div><span class="nm">·
                                                                        +{{ $loc['stat']['chemicals'] - count($loc['preview']) }}
                                                                        hoá chất khác</span></div>
                                                            @endif
                                                        </div>
                                                        <div class="clk-cell-foot">
                                                            {{ $loc['stat']['chemicals'] }} hoá chất · {{ $loc['stat']['lots'] }} lô — bấm xem chi tiết
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div class="clk-empty clk-noresult">
                    <i class="bi bi-search"></i>
                    Không có vị trí nào khớp với từ khoá lọc.
                </div>
            @endif
        @endif

        <div class="clk-footer">
            WMS © {{ date('Y') }} – Tra cứu tồn kho hoá chất (chỉ đọc)
        </div>
    </div>

    {{-- ---------- Modal chi tiết một vị trí ---------- --}}
    <div class="modal fade" id="clkDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: var(--border-radius-lg);">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-geo-alt-fill me-1" style="color: var(--primary)"></i>
                        <span id="clkDName">Vị trí</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <small class="text-muted">Đường dẫn định khu</small>
                            <div class="fw-bold" id="clkDPath">—</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Mã lô</small>
                            <div class="fw-bold" id="clkDLots">0</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Hoá chất</small>
                            <div class="fw-bold" id="clkDChem">0</div>
                        </div>
                    </div>
                    <table class="table table-bordered table-sm clk-modal-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:46px">STT</th>
                                <th>Hoá chất</th>
                                <th class="text-end" style="width:140px">Tồn</th>
                                <th class="text-center" style="width:70px">Số lô</th>
                                <th class="text-center" style="width:110px">Hạn gần nhất</th>
                            </tr>
                        </thead>
                        <tbody id="clkDBody"></tbody>
                    </table>
                    <div class="clk-hint">
                        <i class="bi bi-info-circle me-1"></i>
                        Chỉ liệt kê hoá chất <b>còn tồn</b> tại vị trí này, tồn cộng dồn từ các lô.
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
    <script>
        $(function () {
            var clkIndex = @json($result ? $result['index'] : new \stdClass);

            // Lọc nhanh trong kết quả: ẩn ô + ẩn luôn kệ/phòng/kho rỗng
            function clkApply() {
                var q = ($('#clkFilter').val() || '').toString().trim().toLowerCase();

                $('.clk-cell').each(function () {
                    var ok = q === '' || ($(this).data('search') || '').toString().indexOf(q) >= 0;
                    $(this).css('display', ok ? '' : 'none');
                });
                $('.clk-uz-card').each(function () {
                    var ok = q === '' || ($(this).data('search') || '').toString().indexOf(q) >= 0;
                    $(this).css('display', ok ? '' : 'none');
                });
                $('.clk-shelf').each(function () {
                    $(this).css('display', $(this).find('.clk-cell:visible').length ? '' : 'none');
                });
                $('.clk-room').each(function () {
                    $(this).css('display', $(this).find('.clk-shelf:visible').length ? '' : 'none');
                });
                $('.clk-wh').each(function () {
                    $(this).css('display', $(this).find('.clk-room:visible').length ? '' : 'none');
                });

                var total = $('.clk-wh').length;
                var shown = $('.clk-wh:visible').length;
                $('.clk-noresult').css('display', total > 0 && shown === 0 ? 'block' : 'none');
            }
            $('#clkFilter').on('input', clkApply);

            // Thu gọn / mở từng kho
            $(document).on('click', '.clk-wh-head', function () {
                $(this).closest('.clk-wh').toggleClass('is-closed');
            });

            // Bấm một ô vị trí -> xem đủ hoá chất đang nằm ở đó
            var clkModal = new bootstrap.Modal(document.getElementById('clkDetailModal'));
            $(document).on('click', '.clk-cell[data-key]', function () {
                var d = clkIndex[$(this).data('key')];
                if (!d) return;

                $('#clkDName').text(d.code || '—');
                $('#clkDPath').text(d.path || '—');
                $('#clkDLots').text(d.lots);
                $('#clkDChem').text(d.chemicals);

                var $tb = $('#clkDBody').empty();
                if (!d.items || !d.items.length) {
                    $tb.append('<tr><td colspan="5" class="text-center text-muted">Vị trí này đang trống.</td></tr>');
                } else {
                    d.items.forEach(function (x, i) {
                        var $tr = $('<tr></tr>');
                        $tr.append($('<td class="text-center"></td>').text(i + 1));
                        var $nm = $('<td></td>').append($('<div class="fw-bold"></div>').text(x.name));
                        if (x.code) $nm.append($('<div class="small text-muted"></div>').text(x.code));
                        $tr.append($nm);
                        $tr.append($('<td class="text-end fw-bold"></td>').text(x.remaining + ' ' + (x.unit || '')));
                        $tr.append($('<td class="text-center"></td>').text(x.lots));
                        $tr.append($('<td class="text-center small"></td>').text(x.expiry));
                        $tb.append($tr);
                    });
                }
                clkModal.show();
            });
        });
    </script>
</body>

</html>
