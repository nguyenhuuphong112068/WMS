{{--
| NHẬP - IN NHÃN DÁN LÔ HOÁ CHẤT
|
| Trang in độc lập, KHÔNG dùng layout.master để bản in không dính menu / topNAV.
| Khổ giấy đặt đúng bằng khổ nhãn khai ở config/chemical.php nên bấm In là ra thẳng
| nhãn trên máy in nhãn (Zebra ZD421), không phải căn lại trên khổ A4.
|
| Mọi kích thước bên trong tính theo mm để in ra đúng bằng nhãn thật, không phụ thuộc
| độ phân giải màn hình. Mã vạch là SVG co giãn nên in ở 203dpi hay 300dpi đều sắc nét.
|
| Số lượng nhãn cần in chọn trên thanh công cụ (pages.import.shared.labelToolbar) và
| mỗi lần in được ghi vào audit log qua pages.import.chemicalImport.labelPrinted.
--}}

@php
    /** Ngày trên nhãn viết theo kiểu 19-Aug-26 cho khớp bản nhãn đang dùng. */
    $lblDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d-M-y') : '';

    $lblWidth = $label['width_mm'];
    $lblHeight = $label['height_mm'];

    // Cột safety_warning lưu mảng mã dạng JSON (giống classification), in ra thành chữ nối bằng " - "
    $lblWarningCodes = json_decode($import->safety_warning ?? '', true);
    $lblWarningCodes = is_array($lblWarningCodes) ? $lblWarningCodes : [];
    $lblSafetyWarnings = config('chemical.safety_warnings');
    $lblWarningText = implode(' - ', array_map(fn ($code) => $lblSafetyWarnings[$code] ?? $code, $lblWarningCodes));

    // Tên hoá chất dài thì thu nhỏ chữ lại cho vừa một dòng thay vì tràn ra khỏi ô
    $lblName = $import->chem_name ?: '';
    $lblNameSize = mb_strlen($lblName) > 34 ? 2.4 : (mb_strlen($lblName) > 24 ? 2.9 : 3.5);
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Nhãn lô hoá chất - {{ $import->code }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #E9EEF3;
            color: #000;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
        }

        /* ---------- Nhãn ---------- */
        .label {
            width: {{ $lblWidth }}mm;
            height: {{ $lblHeight }}mm;
            margin: 18px auto;
            background: #fff;
            border: 0.25mm solid #000;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .15);
        }

        .row {
            display: flex;
            border-bottom: 0.25mm solid #000;
        }

        .row:last-child {
            border-bottom: 0;
        }

        .cell {
            padding: 0.6mm 1mm;
            border-right: 0.25mm solid #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }

        .cell:last-child {
            border-right: 0;
        }

        /* Dòng đầu: số SOP và số biểu mẫu */
        .sop {
            font-size: 2.2mm;
            font-weight: 600;
        }

        .sop .cell {
            flex: 1;
        }

        /* Dòng tên hoá chất + mã xuất nhập */
        .name {
            flex: 1;
            align-items: center;
            text-align: center;
            font-size: {{ $lblNameSize }}mm;
            font-weight: 700;
            line-height: 1.15;
        }

        .code {
            width: 21mm;
            align-items: center;
            text-align: center;
            font-size: 3.1mm;
            font-weight: 700;
            word-break: break-all;
        }

        /* Dải cảnh báo an toàn */
        .warning {
            padding: 0.8mm 1mm;
            text-align: center;
            font-size: 2.6mm;
            font-weight: 700;
        }

        /* Nhãn tiếng Việt / tiếng Anh của từng ô */
        .caption {
            font-size: 1.9mm;
            font-weight: 600;
            line-height: 1.2;
        }

        .value {
            font-size: 2.5mm;
            font-weight: 700;
            line-height: 1.2;
        }

        .date-row .cell:nth-child(1),
        .date-row .cell:nth-child(3) {
            width: 16.5mm;
        }

        .date-row .cell:nth-child(2),
        .date-row .cell:nth-child(4) {
            flex: 1;
            text-align: center;
            align-items: center;
        }

        .who-row .cell:nth-child(1) {
            width: 16.5mm;
        }

        .who-row .cell:nth-child(2) {
            flex: 1;
            text-align: center;
            align-items: center;
        }

        .who-row .cell:nth-child(3) {
            width: 12mm;
            text-align: center;
            align-items: center;
        }

        /* Mã vạch: SVG kéo full bề ngang, chừa lề trắng hai bên do chính SVG lo */
        .barcode {
            flex: 1;
            min-height: 7mm;
            padding: 0.6mm 1mm;
            display: flex;
            align-items: stretch;
        }

        .barcode svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .barcode-empty {
            width: 100%;
            text-align: center;
            font-size: 2.2mm;
            font-weight: 700;
            color: #B91C1C;
        }

        /* ---------- Khi in: đúng khổ nhãn, không lề, không thanh công cụ ---------- */
        @media print {
            @page {
                size: {{ $lblWidth }}mm {{ $lblHeight }}mm;
                margin: 0;
            }

            html, body {
                width: {{ $lblWidth }}mm;
                height: {{ $lblHeight }}mm;
                background: #fff;
            }

            /* Khung nhãn phủ kín đúng khổ giấy (box-sizing: border-box nên viền nằm
               gọn trong khổ), không chừa lề để Chrome khỏi tràn sang tờ thứ 2 */
            .label {
                width: 100%;
                height: 100%;
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

    @include('pages.import.shared.labelToolbar', [
        'importId' => $import->id,
        'logUrl' => route('pages.import.chemicalImport.labelPrinted'),
        'backUrl' => route('pages.import.chemicalImport.list'),
        'maxCopies' => $maxCopies,
        'lblWidth' => $lblWidth,
        'lblHeight' => $lblHeight,
        'dpi' => $label['dpi'] ?? 203,
        'printerNote' => 'Chọn máy in nhãn Zebra ZD421.',
    ])

    {{-- Bọc để thanh công cụ nhân bản nhãn ra đúng số lượng người dùng chọn --}}
    <div id="labelStack">
        <div class="label">

            <div class="row sop">
                <div class="cell"><span>SOP: {{ $label['sop_no'] }}</span></div>
                <div class="cell" style="text-align: right">{{ $label['form_no'] }}</div>
            </div>

            <div class="row" style="height: 8mm">
                <div class="cell name">{{ $lblName ?: '—' }}</div>
                <div class="cell code">{{ $import->code }}</div>
            </div>

            <div class="row">
                <div class="cell warning" style="flex: 1">{{ $lblWarningText }}</div>
            </div>

            <div class="row date-row">
                <div class="cell">
                    <span class="caption">Ngày Nhập/<br>Date of Receipt</span>
                </div>
                <div class="cell">
                    <span class="value">{{ $lblDate($import->imported_date) }}</span>
                </div>
                <div class="cell">
                    <span class="caption">Hạn Dùng NSX/<br>Mf. Exp. Date</span>
                </div>
                <div class="cell">
                    <span class="value">{{ $lblDate($import->expired_date) }}</span>
                </div>
            </div>

            <div class="row who-row">
                <div class="cell">
                    <span class="caption">Người nhập/<br>Received By</span>
                </div>
                <div class="cell">
                    <span class="value">{{ $import->imported_by }}</span>
                </div>
                <div class="cell">
                    <span class="value">{{ $import->location_code }}</span>
                </div>
            </div>

            <div class="row barcode">
                @if ($barcode)
                    {!! $barcode !!}
                @else
                    <span class="barcode-empty">Mã "{{ $import->code }}" không tạo được mã vạch</span>
                @endif
            </div>
        </div>
    </div>

</body>

</html>
