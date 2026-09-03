{{--
| NHẬP - IN NHÃN DÁN LÔ VẬT TƯ
|
| Trang in độc lập, KHÔNG dùng layout.master. Khung nhãn khai ở config/material.php,
| in vào giữa cuộn tem thực (media_*_mm) nên chừa lề trắng đều ~1mm.
|
| Bố cục: nửa trên là TÊN VẬT TƯ (chữ to, canh giữa); nửa dưới chia đôi - trái là
| thông tin (vị trí, người nhập, ngày nhập, hạn dùng), phải là MÃ QR ở góc dưới bên
| phải (chiếm ~1/4 nhãn), giữa QR có logo Stella.
|
| QR (App\Support\QrCode) mức sửa lỗi Q để chịu được logo đè giữa. Số lượng nhãn chọn
| trên thanh công cụ (pages.import.shared.labelToolbar), mỗi lần in ghi audit log qua
| pages.import.materialImport.labelPrinted.
--}}

@php
    $lblDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d-M-y') : '';
    $lblWidth = $label['width_mm'];
    $lblHeight = $label['height_mm'];
    $mediaW = $label['media_width_mm'] ?? $lblWidth;
    $mediaH = $label['media_height_mm'] ?? $lblHeight;

    // $qrSize = cạnh phần MÃ QR (mm), chưa tính vùng trắng. Bề rộng ảnh SVG phải cộng
    // thêm viền để phần mã in ra đúng $qrSize mm dù QR bao nhiêu module.
    $qrSize = $label['qr_size_mm'] ?? 16;
    $qrModules = $qr['modules'] ?? 0;
    $qrBorder = $qr['border'] ?? 0;
    $qrBoxMm = $qrModules ? round($qrSize * ($qrModules + 2 * $qrBorder) / $qrModules, 2) : $qrSize;
    $qrLogoMm = round($qrSize * 0.2, 2);     // logo Stella ~20% cạnh mã (an toàn với ECC Q)

    // Ô chứa QR ở góc dưới phải: đủ rộng cho ảnh QR + dòng mã đọc được bên dưới
    $qrCellMm = (int) ceil($qrBoxMm + 3);

    $lblName = $import->material_name ?: '';
    $lblNameSize = mb_strlen($lblName) > 40 ? 2.8 : (mb_strlen($lblName) > 26 ? 3.4 : 4.2);
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
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .15);
        }

        /* ---------- Nửa trên: tên vật tư ---------- */
        .name {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1mm;
            font-size: {{ $lblNameSize }}mm;
            font-weight: 700;
            line-height: 1.15;
            border-bottom: 0.25mm solid #000;
        }

        /* ---------- Nửa dưới: thông tin (trái) + QR (góc dưới phải) ---------- */
        .bottom {
            display: flex;
            height: {{ $qrCellMm }}mm;
        }

        .info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.4mm;
            padding: 0.5mm 1.2mm;
            border-right: 0.25mm solid #000;
            min-width: 0;
        }

        .info .line {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 1mm;
        }

        .caption { font-size: 1.7mm; font-weight: 600; line-height: 1.1; white-space: nowrap; }

        .value {
            font-size: 2.2mm;
            font-weight: 700;
            line-height: 1.1;
            text-align: right;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .qr {
            width: {{ $qrCellMm }}mm;
            flex: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.4mm;
        }

        /* Ảnh QR + logo Stella chồng chính giữa */
        .qr-wrap {
            position: relative;
            width: {{ $qrBoxMm }}mm;
            height: {{ $qrBoxMm }}mm;
        }

        .qr-wrap svg { width: 100%; height: 100%; display: block; }

        .qr-logo-box {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            padding: 0.35mm;
            background: #fff;
        }

        .qr-logo-box svg {
            width: {{ $qrLogoMm }}mm;
            height: {{ round($qrLogoMm * 53 / 60, 2) }}mm;
            display: block;
        }

        .qr-code {
            text-align: center;
            font-size: 1.5mm;
            font-weight: 700;
            margin-top: 0.3mm;
            word-break: break-all;
            line-height: 1;
        }

        .qr-empty {
            text-align: center;
            font-size: 1.6mm;
            font-weight: 700;
            color: #B91C1C;
        }

        @media print {
            @page {
                size: {{ $mediaW }}mm {{ $mediaH }}mm;
                margin: 0;
            }

            /* Giấy = khổ cuộn tem; khung canh giữa -> lề trắng đều (tem - khung) / 2 */
            html, body {
                width: {{ $mediaW }}mm;
                height: {{ $mediaH }}mm;
                background: #fff;
            }

            .label {
                margin: {{ ($mediaH - $lblHeight) / 2 }}mm {{ ($mediaW - $lblWidth) / 2 }}mm;
                box-shadow: none;
            }
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
        'mediaW' => $mediaW,
        'mediaH' => $mediaH,
        'dpi' => $label['dpi'] ?? 203,
    ])

    {{-- Bọc để thanh công cụ nhân bản nhãn ra đúng số lượng người dùng chọn --}}
    <div id="labelStack">
        <div class="label">
            <div class="name">{{ $lblName ?: '—' }}</div>

            <div class="bottom">
                <div class="info">
                    <div class="line">
                        <span class="caption">Vị trí</span>
                        <span class="value">{{ $import->location_code ?: '-' }}</span>
                    </div>
                    <div class="line">
                        <span class="caption">Người nhập</span>
                        <span class="value">{{ $import->imported_by ?: '—' }}</span>
                    </div>
                    <div class="line">
                        <span class="caption">Ngày nhập</span>
                        <span class="value">{{ $lblDate($import->imported_date) }}</span>
                    </div>
                    <div class="line">
                        <span class="caption">Hạn dùng</span>
                        <span class="value">{{ $lblDate($import->expired_date) ?: '—' }}</span>
                    </div>
                </div>

                <div class="qr">
                    @if (!empty($qr['svg']))
                        <div class="qr-wrap">
                            {!! $qr['svg'] !!}
                            <span class="qr-logo-box">
                                <svg viewBox="0 0 60 53" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="#000" fill-rule="evenodd" transform="translate(-62 -2718)"
                                        d="m116.5 2718.38-23.354 11.12v5.06a8.664 8.664 0 0 0 8.573 8.75h16.125a1.175 1.175 0 0 1 0 2.35h-16.125a8.664 8.664 0 0 0 -8.573 8.75v11.29a1.153 1.153 0 1 1 -2.305 0v-11.29a8.664 8.664 0 0 0 -8.572-8.75h-16.114a1.175 1.175 0 0 1 0-2.35h16.114a8.664 8.664 0 0 0 8.572-8.75v-5.06l-23.341-11.12a3.861 3.861 0 0 0 -5.5 3.56v29.21a5.167 5.167 0 0 0 2.52 4.45l24.963 14.7a4.944 4.944 0 0 0 5.038 0l24.963-14.7a5.167 5.167 0 0 0 2.519-4.45v-29.21a3.861 3.861 0 0 0 -5.503-3.56z" />
                                </svg>
                            </span>
                        </div>
                    @else
                        <span class="qr-empty">Mã "{{ $import->code }}" không tạo được QR</span>
                    @endif
                    <div class="qr-code">{{ $import->code }}</div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
