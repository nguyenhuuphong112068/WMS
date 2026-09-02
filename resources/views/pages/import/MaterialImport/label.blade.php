{{--
| NHẬP - IN NHÃN DÁN LÔ VẬT TƯ
|
| Trang in độc lập, KHÔNG dùng layout.master. Khổ giấy đặt đúng bằng khổ nhãn khai ở
| config/material.php. Mã vạch dùng mã QR (App\Support\QrCode) - quét nhanh bằng điện
| thoại, không cần máy quét mã vạch 1 chiều.
|
| Số lượng nhãn cần in chọn trên thanh công cụ (pages.import.shared.labelToolbar) và
| mỗi lần in được ghi vào audit log qua pages.import.materialImport.labelPrinted.
--}}

@php
    $lblDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d-M-y') : '';
    $lblWidth = $label['width_mm'];
    $lblHeight = $label['height_mm'];
    $lblName = $import->material_name ?: '';
    $lblNameSize = mb_strlen($lblName) > 34 ? 2.1 : (mb_strlen($lblName) > 24 ? 2.5 : 3);
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Nhãn lô vật tư - {{ $import->code }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #E9EEF3;
            color: #000;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
        }

        .label {
            width: {{ $lblWidth }}mm;
            height: {{ $lblHeight }}mm;
            margin: 18px auto;
            background: #fff;
            border: 0.25mm solid #000;
            display: flex;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .15);
        }

        .label .body {
            flex: 1;
            display: flex;
            flex-direction: column;
            border-right: 0.25mm solid #000;
        }

        .row {
            display: flex;
            border-bottom: 0.25mm solid #000;
            padding: 0.5mm 0.9mm;
        }

        .row:last-child { border-bottom: 0; }

        .sop { font-size: 1.9mm; font-weight: 600; justify-content: space-between; }

        .name {
            flex: 1;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: {{ $lblNameSize }}mm;
            font-weight: 700;
            line-height: 1.15;
        }

        .code {
            display: none;
        }

        .caption { font-size: 1.7mm; font-weight: 600; line-height: 1.15; }
        .value { font-size: 2.2mm; font-weight: 700; line-height: 1.15; }

        .info-row { flex-direction: column; }
        .info-row .line { display: flex; justify-content: space-between; }

        .qr {
            width: {{ min($lblHeight, 26) }}mm;
            padding: 1mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr svg { width: 100%; height: auto; display: block; }
        
        .qr-code {
            text-align: center;
            font-size: 2.2mm;
            font-weight: 700;
            margin-top: 1mm;
            letter-spacing: 0.1mm;
            word-break: break-all;
            line-height: 1.1;
        }

        .qr-empty {
            width: 100%;
            text-align: center;
            font-size: 1.8mm;
            font-weight: 700;
            color: #B91C1C;
        }

        @media print {
            @page {
                size: {{ $lblWidth }}mm {{ $lblHeight }}mm;
                margin: 0;
            }

            body { background: #fff; }
            .label { margin: 0; box-shadow: none; }
        }
    </style>
</head>

<body>

    @include('pages.import.shared.labelToolbar', [
        'importId' => $import->id,
        'logUrl' => route('pages.import.materialImport.labelPrinted'),
        'backUrl' => route('pages.import.materialImport.list'),
        'maxCopies' => $maxCopies,
        'lblWidth' => $lblWidth,
        'lblHeight' => $lblHeight,
    ])

    {{-- Bọc để thanh công cụ nhân bản nhãn ra đúng số lượng người dùng chọn --}}
    <div id="labelStack">
        <div class="label">
            <div class="body">
                <div class="row name">{{ $lblName ?: '—' }}</div>

                <div class="row info-row">
                    <div class="line">
                        <span class="caption">Vị trí</span>
                        <span class="value">{{ $import->location_code ?: '-' }}</span>
                    </div>
                </div>

                <div class="row info-row">
                    <div class="line">
                        <span class="caption">Ngày nhập</span>
                        <span class="value">{{ $lblDate($import->imported_date) }}</span>
                    </div>
                    <div class="line">
                        <span class="caption">Hạn dùng</span>
                        <span class="value">{{ $lblDate($import->expired_date) ?: '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="qr">
                @if ($qr)
                    {!! $qr !!}
                @else
                    <span class="qr-empty">Mã "{{ $import->code }}" không tạo được QR</span>
                @endif
                <div class="qr-code">{{ $import->code }}</div>
            </div>
        </div>
    </div>

</body>

</html>
