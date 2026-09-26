{{--
| NHẬP - IN NHÃN DÁN LÔ HOÁ CHẤT
|
| Trang in độc lập, KHÔNG dùng layout.master để bản in không dính menu / topNAV.
| Khổ giấy đặt đúng bằng khổ nhãn khai ở config/chemical.php nên bấm In là ra thẳng
| nhãn trên máy in nhãn (Zebra ZD421), không phải căn lại trên khổ A4.
|
| Mọi kích thước bên trong tính theo mm để in ra đúng bằng nhãn thật, không phụ thuộc
| độ phân giải màn hình. Mã QR là SVG co giãn nên in ở 203dpi hay 300dpi đều sắc nét.
|
| Bố cục (chỉ tiếng Việt): dòng số biểu mẫu -> tên hoá chất -> dải cảnh báo an toàn -> phần
| dưới giống nhãn lô vật tư: trái là thông tin (ngày nhập, hạn dùng, người nhập, vị trí), góc
| dưới phải là MÃ QR có logo Stella ở giữa (App\Support\QrCode, mức sửa lỗi Q) và mã xuất nhập
| ngay dưới QR. Cạnh mã QR khai ở config('chemical.label.qr_size_mm').
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
    $lblSafetyWarnings = \App\Support\ChemicalCompatibility::labels();
    $lblWarningText = implode(' - ', array_map(fn ($code) => $lblSafetyWarnings[$code] ?? $code, $lblWarningCodes));
    // Nhãn cảnh báo theo Sơ đồ lưu trữ GHS dài hơn bộ mã cũ: nhiều cảnh báo thì thu nhỏ chữ cho khỏi tràn dải
    $lblWarningLength = mb_strlen($lblWarningText);
    $lblWarningSize = $lblWarningLength > 110 ? 1.7 : ($lblWarningLength > 75 ? 2.0 : ($lblWarningLength > 45 ? 2.3 : 2.6));

    // Tên hoá chất (chiếm trọn bề ngang nhãn) dài thì thu nhỏ chữ cho vừa thay vì tràn ra khỏi ô
    $lblName = $import->chem_name ?: '';
    $lblNameSize = mb_strlen($lblName) > 50 ? 2.4 : (mb_strlen($lblName) > 34 ? 2.9 : 3.5);

    // Mã xuất nhập nằm dưới QR, trong ô hẹp bằng bề rộng ô QR: giữ trên 1 dòng, mã dài thì thu nhỏ chữ
    $lblCodeLength = mb_strlen((string) $import->code);
    $lblCodeSize = $lblCodeLength > 17 ? 1.25 : ($lblCodeLength > 14 ? 1.45 : 1.7);

    // $qrSize = cạnh phần MÃ QR (mm), chưa tính vùng trắng. Bề rộng ảnh SVG phải cộng
    // thêm viền để phần mã in ra đúng $qrSize mm dù QR bao nhiêu module.
    $qrSize = $label['qr_size_mm'] ?? 12;
    $qrModules = $qr['modules'] ?? 0;
    $qrBorder = $qr['border'] ?? 0;
    $qrBoxMm = $qrModules ? round($qrSize * ($qrModules + 2 * $qrBorder) / $qrModules, 2) : $qrSize;
    $qrLogoMm = round($qrSize * 0.2, 2);     // logo Stella ~20% cạnh mã (an toàn với ECC Q)
    $qrCellMm = round($qrBoxMm + 2, 1);      // ô chứa QR: ảnh QR + lề 1mm mỗi bên (dòng mã nằm dưới)

    // Giá trị dài (người nhập) thì thu nhỏ chữ, cho xuống 2 dòng thay vì bị cắt
    $lblValSize = function ($value) {
        $n = mb_strlen(trim((string) $value));
        return $n > 30 ? 1.7 : ($n > 22 ? 1.9 : ($n > 16 ? 2.2 : 2.5));
    };
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

        /* Dòng đầu: số biểu mẫu - chữ nhỏ in nghiêng, liền khối với dòng tên (không kẻ ngang) */
        .form-no {
            font-size: 1.1mm;
            font-weight: 600;
            font-style: italic;
            border-bottom: 0;
        }

        .form-no .cell {
            flex: 1;
            text-align: right;
            padding: 0.4mm 1mm 0;
        }

        /* Dòng tên hoá chất */
        .name {
            flex: 1;
            align-items: center;
            text-align: center;
            font-size: {{ $lblNameSize }}mm;
            font-weight: 700;
            line-height: 1.15;
        }

        /* Dải cảnh báo an toàn */
        .warning {
            padding: 0.8mm 1mm;
            text-align: center;
            font-size: 2.6mm;
            font-weight: 700;
        }

        /* Tên của từng ô thông tin */
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

        /* ---------- Phần dưới: thông tin (trái) + QR (góc dưới phải) ---------- */
        .bottom {
            flex: 1;
            min-height: 0;
        }

        .info {
            flex: 1;
            display: flex;
            flex-direction: column;
            border-right: 0.25mm solid #000;
            min-width: 0;
        }

        .info .line {
            flex: 1;
            display: flex;
            border-bottom: 0.25mm solid #000;
            min-height: 0;
        }

        .info .line:last-child {
            border-bottom: 0;
        }

        .info .line .cell {
            padding: 0.3mm 1mm;
        }

        .info .line .cell:first-child {
            width: 15.5mm;
            flex: none;
            white-space: nowrap;
        }

        .info .line .cell:last-child {
            flex: 1;
            text-align: center;
            align-items: center;
        }

        .info .caption {
            font-size: 2mm;
            line-height: 1.12;
        }

        .info .value {
            line-height: 1.1;
            word-break: break-word;
        }

        .qr {
            width: {{ $qrCellMm }}mm;
            flex: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Ảnh QR + logo Stella chồng chính giữa */
        .qr-wrap {
            position: relative;
            width: {{ $qrBoxMm }}mm;
            height: {{ $qrBoxMm }}mm;
        }

        .qr-wrap svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .qr-logo-box {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            padding: 0.3mm;
            background: #fff;
        }

        .qr-logo-box svg {
            width: {{ $qrLogoMm }}mm;
            height: {{ round($qrLogoMm * 53 / 60, 2) }}mm;
            display: block;
        }

        /* Mã xuất nhập ngay dưới QR */
        .qr-code {
            width: 100%;
            text-align: center;
            font-size: {{ $lblCodeSize }}mm;
            font-weight: 700;
            margin-top: 0.4mm;
            white-space: nowrap;
            letter-spacing: -0.02mm;
            line-height: 1.05;
        }

        .qr-empty {
            text-align: center;
            font-size: 1.6mm;
            font-weight: 700;
            color: #B91C1C;
            padding: 0 0.5mm;
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

            <div class="row form-no">
                <div class="cell">{{ $label['form_no'] }}</div>
            </div>

            <div class="row" style="height: 8mm">
                <div class="cell name">{{ $lblName ?: '—' }}</div>
            </div>

            <div class="row">
                <div class="cell warning" style="flex: 1; font-size: {{ $lblWarningSize }}mm">{{ $lblWarningText }}</div>
            </div>

            <div class="row bottom">
                <div class="info">
                    <div class="line">
                        <div class="cell"><span class="caption">Ngày Nhập</span></div>
                        <div class="cell">
                            <span class="value">{{ $lblDate($import->imported_date) }}</span>
                        </div>
                    </div>
                    <div class="line">
                        <div class="cell"><span class="caption">Hạn Dùng NSX</span></div>
                        <div class="cell">
                            <span class="value">{{ $lblDate($import->expired_date) }}</span>
                        </div>
                    </div>
                    <div class="line">
                        <div class="cell"><span class="caption">Người Nhập</span></div>
                        <div class="cell">
                            <span class="value" style="font-size: {{ $lblValSize($import->imported_by) }}mm">{{ $import->imported_by }}</span>
                        </div>
                    </div>
                    <div class="line">
                        <div class="cell"><span class="caption">Vị Trí</span></div>
                        <div class="cell">
                            <span class="value" style="font-size: {{ $lblValSize($import->location_code) }}mm">{{ $import->location_code ?: '—' }}</span>
                        </div>
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
