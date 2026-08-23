{{--
| SỬ DỤNG - IN PHIẾU THEO DÕI VÀ QUYẾT ĐỊNH HUỶ (biểu mẫu QA/F/058-07)
|
| Trang A4 độc lập, KHÔNG dùng layout.master để bản in không dính menu / topNAV.
| Người dùng bấm nút In rồi chọn máy in hoặc "Lưu thành PDF" của trình duyệt.
|
| Bố cục bám đúng bản giấy: đầu trang là khối SOP, mục 1 - 2 ở trang 1, mục 3 - 4 ở
| trang 2. Ô nào hệ thống chưa có dữ liệu thì in dòng chấm để ký / điền tay.
--}}

@php
    $dspFmt = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : null;
    $dspNum = fn($value) => $value === null ? null : rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Có dữ liệu thì in dữ liệu, chưa có thì in dòng chấm để điền tay. */
    $dspLine = fn($value, $dots = 30) => $value !== null && $value !== '' ? $value : str_repeat('.', $dots);

    $dspPeriod = str_pad($disposal->period_month, 2, '0', STR_PAD_LEFT) . '.' . $disposal->period_year;

    // Chừa tối thiểu 5 dòng cho bảng tổng kết, thiếu thì để trống cho ghi tay
    $dspBlankRows = max(0, 5 - $items->count());
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $form['form_no'] }} - Phiếu Theo Dõi Và Quyết Định Huỷ - {{ $disposal->code }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">

    <style>
        :root {
            --ink: #1a1a1a;
            --label: #14707F;
            --line: #333;
            --brand: #4CAF3E;
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
            position: relative;
        }

        /* ---------- Đầu trang: logo + khối SOP ---------- */
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

        .sop {
            font-size: 12px;
            text-align: right;
            color: var(--label);
        }

        .sop i {
            font-style: italic;
        }

        .sop b {
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

        .kinds {
            text-align: center;
            font-size: 13px;
            font-weight: 600;
        }

        .kinds div {
            line-height: 1.55;
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

        /* ---------- Bảng tổng kết phế phẩm ---------- */
        table.grid {
            width: 100%;
            border-collapse: collapse;
        }

        table.grid th,
        table.grid td {
            border: 1px solid var(--line);
            padding: 6px 8px;
            vertical-align: top;
        }

        table.grid th {
            font-weight: 600;
            text-align: center;
            font-size: 12.5px;
        }

        table.grid td.no,
        table.grid td.mid {
            text-align: center;
        }

        table.grid td.qty {
            text-align: right;
            white-space: nowrap;
        }

        table.grid tr.blank td {
            height: 30px;
        }

        .sub {
            font-size: 11.5px;
            color: #555;
        }

        /* ---------- Khối chữ ký ---------- */
        .signs {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding: 0 6mm;
        }

        .sign {
            min-width: 62mm;
        }

        .sign .role {
            margin-top: 16mm;
            font-weight: 600;
        }

        .sign .filled {
            margin-top: 16mm;
            font-weight: 600;
            text-align: center;
            border-top: 1px dotted #999;
            padding-top: 3px;
        }

        .line {
            margin: 5px 0;
        }

        .line .lbl {
            color: var(--label);
        }

        .dots {
            letter-spacing: 1px;
            color: #666;
        }

        .indent {
            padding-left: 8mm;
        }

        .box {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1px solid var(--ink);
            margin-right: 5px;
            text-align: center;
            line-height: 11px;
            font-size: 11px;
            vertical-align: -2px;
        }

        .picked {
            font-weight: 700;
        }

        .picked .box {
            background: #EAF3FC;
        }

        /* Phương pháp huỷ được chọn thì khoanh tròn đúng như hướng dẫn trên biểu mẫu */
        .circled {
            display: inline-block;
            border: 1.5px solid var(--ink);
            border-radius: 40px;
            padding: 1px 12px;
            font-weight: 700;
        }

        table.handover {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.handover td {
            border: 1px solid var(--line);
            padding: 8px 10px;
            width: 50%;
            vertical-align: top;
        }

        table.handover td .who {
            min-height: 22mm;
        }

        .foot {
            position: absolute;
            left: 14mm;
            right: 14mm;
            bottom: 8mm;
            text-align: right;
            font-size: 11.5px;
            color: #555;
        }

        /* ---------- Khi in ---------- */
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
                page-break-after: always;
            }

            .sheet:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        <button type="button" class="go" onclick="window.print()">In / Lưu thành PDF</button>
        <a class="back" href="{{ route('pages.export.chemicalExport.list', ['tab' => 'disposal']) }}">Quay lại</a>
        <span class="note">Hộp thoại in hiện ra, chọn máy in hoặc "Save as PDF" / "Microsoft Print to PDF".</span>
    </div>

    {{-- ==================== TRANG 1: MỤC 1 VÀ MỤC 2 ==================== --}}
    <div class="sheet">

        <div class="head">
            <div class="brand">
                <img src="{{ asset('img/iconstella.svg') }}" alt="Stella">
                <span>STELLA</span>
            </div>

            <div class="sop">
                <div>Số SOP đối chiếu/ <i>Ref.SOP No:</i> <b>{{ $form['sop_no'] }}</b></div>
                <div>Số biểu mẫu/ <i>Format No:</i> <b>{{ $form['form_no'] }}</b></div>
                <div>Ngày hiệu lực/ <i>Effective date:</i> <b>{{ $form['effective_date'] }}</b></div>
            </div>
        </div>

        <div class="title">Phiếu theo dõi và quyết định huỷ</div>

        <div class="kinds">
            <div><span class="box"></span> NGUYÊN LIỆU</div>
            <div><span class="box"></span> THÀNH PHẨM</div>
            <div class="picked"><span class="box">&#10003;</span> HOÁ CHẤT/ CHẤT CHUẨN</div>
        </div>

        <div class="meta">
            <div><span class="lbl">Tháng/ năm:</span> <b>{{ $dspPeriod }}</b></div>
            <div>
                <span class="lbl">Quyết định số:</span>
                <b>{{ $disposal->decision_no ?: str_repeat('.', 24) }}</b>
            </div>
        </div>

        <div class="meta">
            <div>
                <span class="lbl">Bộ Phận giao phế phẩm:</span>
                <b>{{ $department->shortName ?? ($department->name ?? '—') }}</b>
            </div>
            <div><span class="lbl">Số phiếu theo dõi:</span> <b>{{ $disposal->code }}</b></div>
        </div>

        <div class="section">1. TỔNG KẾT PHẾ PHẨM:</div>

        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 8%">STT</th>
                    <th style="width: 30%">Tên nguyên liệu, sản phẩm, hoá chất, chất chuẩn</th>
                    <th style="width: 14%">Số lô</th>
                    <th style="width: 16%">Số Phiếu KN, OOS, BCSL...</th>
                    <th style="width: 14%">Khối Lượng/ Số lượng</th>
                    <th>Lý do</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td>
                            {{ $item->chem_name ?: '—' }}
                            <div class="sub">{{ $item->category_code }} · {{ $item->code }}</div>
                        </td>
                        <td class="mid">{{ $item->batch_no ?: '' }}</td>
                        <td class="mid">{{ $item->test_report_no ?: '' }}</td>
                        <td class="qty">
                            {{ $dspNum($item->amount) }} {{ $item->unit }}
                            @if ($item->amount_kg !== null)
                                <div class="sub">≈ {{ $dspNum($item->amount_kg) }} kg</div>
                            @endif
                        </td>
                        <td>{{ $item->purpose ?: '' }}</td>
                    </tr>
                @endforeach

                @for ($i = 0; $i < $dspBlankRows; $i++)
                    <tr class="blank">
                        <td class="no">{{ $items->count() + $i + 1 }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="signs">
            <div class="sign">
                <div class="line"><span class="lbl">Ngày:</span> {{ $dspFmt($disposal->summarized_at) ?: str_repeat('.', 20) }}</div>
                <div>Người tổng kết</div>
                @if ($disposal->summarized_by)
                    <div class="filled">{{ $disposal->summarized_by }}</div>
                @else
                    <div class="role">&nbsp;</div>
                @endif
            </div>

            <div class="sign">
                <div class="line"><span class="lbl">Ngày kiểm tra:</span> {{ $dspFmt($disposal->checked_at) ?: str_repeat('.', 16) }}</div>
                <div>Nhân Viên Quản Lý Hoá Chất</div>
                @if ($disposal->chemical_staff)
                    <div class="filled">{{ $disposal->chemical_staff }}</div>
                @else
                    <div class="role">&nbsp;</div>
                @endif
            </div>
        </div>

        <div class="section">2. QUYẾT ĐỊNH HUỶ BỎ:</div>

        <div class="line indent">
            Trưởng phòng ĐBCL quyết định huỷ bỏ các nguyên liệu, sản phẩm, hoá chất, chất chuẩn theo danh mục như
            trên.
        </div>

        <div class="line indent">
            <span class="lbl">Ghi chú khác:</span>
            <span class="{{ $disposal->other_note ? '' : 'dots' }}">{{ $dspLine($disposal->other_note, 70) }}</span>
        </div>

        <div class="line indent">Khoanh tròn phương pháp huỷ được chọn:</div>

        @foreach ($methods as $key => $label)
            <div class="line indent">
                @if ($disposal->method === $key)
                    <span class="circled">{{ $loop->iteration }}. {{ $label }}</span>
                @else
                    {{ $loop->iteration }}. {{ $label }}
                @endif
            </div>
        @endforeach

        <div class="line indent">
            <span class="lbl">Thời gian dự kiến thực hiện:</span>
            <span class="{{ $disposal->planned_time ? '' : 'dots' }}">{{ $dspLine($disposal->planned_time, 55) }}</span>
        </div>

        <div class="line indent">
            <span class="lbl">Thực hiện huỷ:</span>
            <span class="{{ $disposal->executor_type === 'agency' ? 'picked' : '' }}" style="margin-left: 12px">
                <span class="box">{!! $disposal->executor_type === 'agency' ? '&#10003;' : '' !!}</span> Cơ quan huỷ
            </span>
            <span class="{{ $disposal->executor_type === 'other' ? 'picked' : '' }}" style="margin-left: 30mm">
                <span class="box">{!! $disposal->executor_type === 'other' ? '&#10003;' : '' !!}</span>
                <span class="{{ $disposal->executor_other ? '' : 'dots' }}">{{ $dspLine($disposal->executor_other, 26) }}</span>
            </span>
        </div>

        <div class="signs">
            <div class="sign">
                <div class="line">
                    <span class="lbl">Ngày:</span>
                    {{ $dspFmt($disposal->qa_approved_at) ?: str_repeat('.', 20) }}
                </div>
                <div>TP. ĐBCL</div>
                @if ($disposal->qa_approved_by)
                    <div class="filled">{{ $disposal->qa_approved_by }}</div>
                @else
                    <div class="role">&nbsp;</div>
                @endif
            </div>

            <div class="sign">
                <div class="line">
                    <span class="lbl">Duyệt:</span>
                    {{ $dspFmt($disposal->director_approved_at) ?: str_repeat('.', 20) }}
                </div>
                <div>Ban Giám Đốc</div>
                @if ($disposal->director_approved_by)
                    <div class="filled">{{ $disposal->director_approved_by }}</div>
                @else
                    <div class="role">&nbsp;</div>
                @endif
            </div>
        </div>

        <div class="foot">{{ $form['form_no'] }} · {{ $disposal->code }} · Trang 1/2</div>
    </div>

    {{-- ==================== TRANG 2: MỤC 3 VÀ MỤC 4 ==================== --}}
    <div class="sheet">

        <div class="section">3. GIAO NHẬN PHẾ PHẨM:</div>

        <div class="line indent">
            <span class="lbl">Tổng khối lượng phế phẩm:</span>
            rắn: <span class="{{ $disposal->solid_weight === null ? 'dots' : '' }}">{{ $dspNum($disposal->solid_weight) ?: str_repeat('.', 26) }}</span> kg
        </div>

        <div class="line" style="padding-left: 55mm">
            lỏng: <span class="{{ $disposal->liquid_weight === null ? 'dots' : '' }}">{{ $dspNum($disposal->liquid_weight) ?: str_repeat('.', 26) }}</span> kg
        </div>

        @if ($totalKg > 0)
            <div class="line indent sub">
                (Hệ thống quy đổi từ danh mục ở mục 1: {{ $dspNum($totalKg) }} kg
                @if ($notConvertible)
                    , còn {{ $notConvertible }} dòng đơn vị đếm hoặc thiếu tỉ trọng nên không quy đổi được
                @endif
                )
            </div>
        @endif

        <table class="handover">
            <tr>
                <td>
                    <span class="lbl">Ngày:</span>
                    {{ $dspFmt($disposal->handover_date) ?: str_repeat('.', 28) }}
                </td>
                <td>
                    <span class="lbl">Ngày:</span>
                    {{ $dspFmt($disposal->receive_date) ?: str_repeat('.', 28) }}
                </td>
            </tr>
            <tr>
                <td>
                    <div>Người giao:</div>
                    <div class="who">{{ $disposal->handover_by }}</div>
                </td>
                <td>
                    <div>Người nhận (Hành chánh):</div>
                    <div class="who">{{ $disposal->receive_by }}</div>
                </td>
            </tr>
        </table>

        <div class="section">4. ĐBCL KIỂM TRA VÀ THEO DÕI HUỶ:</div>

        <div class="line indent">1. Kiểm tra, dán nhãn "Chấp nhận huỷ":</div>
        <div class="line indent" style="padding-left: 14mm">
            <span class="lbl">Ngày:</span>
            {{ $dspFmt($disposal->label_date) ?: str_repeat('.', 26) }}
            <span class="lbl" style="margin-left: 10mm">Người thực hiện:</span>
            <span class="{{ $disposal->label_by ? '' : 'dots' }}">{{ $dspLine($disposal->label_by, 26) }}</span>
        </div>

        <div class="line indent">2. Tiến hành huỷ:</div>
        <div class="line indent" style="padding-left: 14mm">
            <span class="lbl">Ngày:</span>
            {{ $dspFmt($disposal->destroy_date) ?: str_repeat('.', 26) }}
            <span class="lbl" style="margin-left: 10mm">Người xác nhận:</span>
            <span class="{{ $disposal->destroy_by ? '' : 'dots' }}">{{ $dspLine($disposal->destroy_by, 26) }}</span>
        </div>

        <div class="foot">{{ $form['form_no'] }} · {{ $disposal->code }} · Trang 2/2</div>
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
