{{--
| SỬ DỤNG - PHIẾU SOẠN VẬT TƯ CẤP PHÁT
|
| Trang A4 độc lập, KHÔNG dùng layout.master để bản in không dính menu / topNAV.
| Mỗi dòng là MỘT LÔ ở MỘT vị trí, xếp theo đường đi trong kho (kho -> kệ -> cột -> tầng)
| để nhân viên đi một vòng là lấy đủ. Cột cuối để tích tay khi đã lấy xong.
|
| Dữ liệu vào: $prepLines, $prepGroups, $department - xem MaterialExportController::prepPrint().
--}}

@php
    $prpNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $prpDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '';

    $prpShort = collect($prepGroups)->filter(fn($group) => (float) $group['shortage'] > 0.00005);

    $prpTypeLabel = ['periodic' => 'Định kỳ', 'risk_assessment' => 'ĐG rủi ro', 'regular' => 'Thường quy'];
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu soạn vật tư cấp phát - {{ $department->shortName ?? ($department->name ?? '') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">

    <style>
        :root {
            --ink: #1a1a1a;
            --label: #14707F;
            --line: #333;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #E9EEF3;
            color: var(--ink);
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }

        /* ---------- Thanh công cụ, chỉ hiện trên màn hình ---------- */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background: #fff;
            border-bottom: 1px solid #d7dee6;
        }

        .toolbar button,
        .toolbar a {
            border: 0;
            border-radius: 8px;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s ease;
        }

        .toolbar .go {
            background: #2E7BC4;
            color: #fff;
        }

        .toolbar .go:hover {
            background: #1F5E9E;
            transform: translateY(-1px);
        }

        .toolbar .back {
            background: #EAF3FC;
            color: #1F5E9E;
        }

        .toolbar .note {
            color: #64748B;
            font-size: 12px;
            font-weight: 400;
        }

        /* ---------- Trang A4 ---------- */
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 16px auto;
            padding: 14mm 14mm 12mm;
            background: #fff;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .12);
        }

        .head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand img {
            width: 42px;
            height: 42px;
        }

        .brand span {
            font-size: 30px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #1F3864;
        }

        .stamp {
            font-size: 12px;
            text-align: right;
            color: var(--label);
        }

        .stamp b {
            color: var(--ink);
            font-weight: 600;
        }

        .title {
            margin: 10px 0 2px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            color: var(--label);
        }

        .meta b {
            color: var(--ink);
        }

        .section {
            margin: 14px 0 6px;
            font-weight: 700;
            font-size: 14px;
        }

        table.grid {
            width: 100%;
            border-collapse: collapse;
        }

        table.grid th,
        table.grid td {
            border: 1px solid var(--line);
            padding: 5px 7px;
            vertical-align: top;
            font-size: 12px;
        }

        table.grid th {
            font-weight: 600;
            text-align: center;
            background: #EAF3FC;
        }

        table.grid td.no,
        table.grid td.mid {
            text-align: center;
        }

        table.grid td.qty {
            text-align: right;
            white-space: nowrap;
            font-weight: 700;
        }

        .loc {
            font-weight: 700;
            white-space: nowrap;
        }

        .code {
            font-weight: 700;
            letter-spacing: .4px;
            white-space: nowrap;
        }

        .sub {
            color: #64748B;
            font-size: 11px;
        }

        .tick {
            width: 14px;
            height: 14px;
            border: 1px solid var(--line);
            display: inline-block;
        }

        .warn {
            color: #B91C1C;
            font-weight: 700;
        }

        /* ---------- Ký tên ---------- */
        .signs {
            display: flex;
            justify-content: space-around;
            margin-top: 18px;
            text-align: center;
        }

        .signs div {
            width: 46%;
        }

        .signs .role {
            font-weight: 700;
        }

        .signs .hint {
            font-style: italic;
            font-size: 11.5px;
            color: #64748B;
        }

        .signs .space {
            height: 22mm;
        }

        .foot {
            margin-top: 10px;
            text-align: center;
            font-size: 11px;
            color: #64748B;
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                margin: 0;
                box-shadow: none;
            }

            table.grid th {
                background: #EAF3FC !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table.grid tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        <button type="button" class="go" onclick="window.print()">In / Lưu thành PDF</button>
        <a class="back" href="{{ route('pages.export.materialExport.list', ['tab' => 'prepare']) }}">Quay lại</a>
        <span class="note">Hộp thoại in hiện ra, chọn máy in hoặc "Save as PDF" / "Microsoft Print to PDF".</span>
    </div>

    <div class="sheet">

        <div class="head">
            <div class="brand">
                <img src="{{ asset('img/iconstella.svg') }}" alt="Stella">
                <span>STELLA</span>
            </div>

            <div class="stamp">
                <div>Phòng ban: <b>{{ $department->name ?? '—' }}</b></div>
                <div>Ngày in: <b>{{ now()->format('d/m/Y H:i') }}</b></div>
                <div>Người in: <b>{{ session('user')['fullName'] ?? '' }}</b></div>
            </div>
        </div>

        <div class="title">Phiếu soạn vật tư cấp phát</div>

        <div class="meta">
            <div>Số phiếu đề nghị: <b>{{ count($prepRequests) }}</b></div>
            <div>Số vật tư cần soạn: <b>{{ count($prepGroups) }}</b></div>
            <div>Số lượt lấy hàng: <b>{{ count($prepLines) }}</b></div>
            <div>Vật tư thiếu hàng: <b class="{{ $prpShort->count() ? 'warn' : '' }}">{{ $prpShort->count() }}</b></div>
        </div>

        <div class="section">1. Danh sách lấy hàng (theo đường đi trong kho)</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:8mm">TT</th>
                    <th style="width:24mm">Vị trí</th>
                    <th style="width:28mm">Mã xuất nhập</th>
                    <th>Vật tư</th>
                    <th style="width:20mm">Hạn dùng</th>
                    <th style="width:22mm">SL lấy</th>
                    <th style="width:32mm">Đề nghị</th>
                    <th style="width:12mm">Đã lấy</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prepLines as $line)
                    <tr>
                        <td class="no">{{ $line['sequence'] }}</td>
                        <td class="loc">{{ $line['location_code'] ?: '—' }}</td>
                        <td class="code">{{ $line['import_code'] }}</td>
                        <td>
                            {{ $line['material_name'] }}
                            @if ($line['category_code'])
                                <span class="sub">({{ $line['category_code'] }})</span>
                            @endif
                            @if ($line['specification'])
                                <div class="sub">{{ $line['specification'] }}</div>
                            @endif
                        </td>
                        <td class="mid">{{ $prpDate($line['expired_date']) ?: '—' }}</td>
                        <td class="qty">{{ $prpNum($line['suggested_amount']) }} {{ $line['unit'] }}</td>
                        <td class="sub">
                            @foreach ($line['requests'] as $req)
                                <div>{{ $req['code'] }}: <b>{{ $prpNum($req['left']) }} {{ $req['unit'] }}</b></div>
                            @endforeach
                        </td>
                        <td class="mid"><span class="tick"></span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="mid">Không có vật tư nào chờ cấp phát.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section">2. Chi tiết đề nghị cấp phát</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:8mm">TT</th>
                    <th style="width:30mm">Mã đề nghị</th>
                    <th style="width:20mm">Loại</th>
                    <th style="width:20mm">Ngày mong muốn</th>
                    <th style="width:26mm">Người lập</th>
                    <th>Vật tư cần cấp</th>
                    <th style="width:22mm">SL cần</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prepRequests as $request)
                    @php $prpInfo = $request['info']; @endphp
                    @foreach ($request['items'] as $item)
                        <tr>
                            @if ($loop->first)
                                <td class="no" rowspan="{{ count($request['items']) }}">{{ $loop->parent->iteration }}</td>
                                <td rowspan="{{ count($request['items']) }}">
                                    <span class="code">{{ $prpInfo['request_code'] }}</span>
                                    @if ($prpInfo['request_name'])
                                        <div class="sub">{{ $prpInfo['request_name'] }}</div>
                                    @endif
                                    @if ($prpInfo['request_note'])
                                        <div class="sub">{{ $prpInfo['request_note'] }}</div>
                                    @endif
                                </td>
                                <td class="mid" rowspan="{{ count($request['items']) }}">
                                    {{ $prpTypeLabel[$prpInfo['request_type']] ?? $prpInfo['request_type'] }}
                                </td>
                                <td class="mid" rowspan="{{ count($request['items']) }}">
                                    {{ $prpDate($prpInfo['needed_date']) ?: '—' }}
                                </td>
                                <td rowspan="{{ count($request['items']) }}">
                                    {{ $prpInfo['created_by'] ?: '—' }}
                                    <div class="sub">Lập {{ $prpDate($prpInfo['created_at']) }}</div>
                                </td>
                            @endif
                            <td>
                                {{ $item['material_name'] }}
                                @if ($item['category_code'])
                                    <span class="sub">({{ $item['category_code'] }})</span>
                                @endif
                                @if ($item['purpose'])
                                    <div class="sub">Mục đích: {{ $item['purpose'] }}</div>
                                @endif
                                @if ((float) $item['issued'] > 0.00005)
                                    <div class="sub">Đã cấp {{ $prpNum($item['issued']) }}/{{ $prpNum($item['requested']) }} {{ $item['display_unit'] }}</div>
                                @endif
                            </td>
                            <td class="qty">{{ $prpNum($item['left']) }} {{ $item['display_unit'] }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="7" class="mid">Không có đề nghị nào chờ cấp phát.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($prpShort->count())
            <div class="section">3. Vật tư kho không đủ hàng</div>
            <table class="grid">
                <thead>
                    <tr>
                        <th style="width:8mm">TT</th>
                        <th>Vật tư</th>
                        <th style="width:26mm">Cần soạn</th>
                        <th style="width:26mm">Tồn khả dụng</th>
                        <th style="width:26mm">Còn thiếu</th>
                        <th style="width:34mm">Đề nghị</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prpShort as $group)
                        <tr>
                            <td class="no">{{ $loop->iteration }}</td>
                            <td>
                                {{ $group['name'] }}
                                @if ($group['category_code'])
                                    <span class="sub">({{ $group['category_code'] }})</span>
                                @endif
                            </td>
                            <td class="qty">{{ $prpNum($group['needed']) }} {{ $group['unit'] }}</td>
                            <td class="qty">{{ $prpNum($group['available']) }} {{ $group['unit'] }}</td>
                            <td class="qty warn">{{ $prpNum($group['shortage']) }} {{ $group['unit'] }}</td>
                            <td class="sub">
                                {{ collect($group['demands'])->pluck('request_code')->unique()->implode(', ') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="signs">
            <div>
                <div class="role">Người soạn hàng</div>
                <div class="hint">(Ký, ghi rõ họ tên)</div>
                <div class="space"></div>
            </div>
            <div>
                <div class="role">Người kiểm tra</div>
                <div class="hint">(Ký, ghi rõ họ tên)</div>
                <div class="space"></div>
            </div>
        </div>

        <div class="foot">Phiếu soạn vật tư cấp phát · {{ $department->name ?? '' }} · in lúc {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <script>
        // Mở trang là bật luôn hộp thoại in, người dùng chọn "Lưu thành PDF" nếu cần bản PDF
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
</body>

</html>
