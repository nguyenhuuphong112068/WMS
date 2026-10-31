{{--
| NHẬP - IN NHÃN DÁN LÔ VẬT TƯ
|
| Trang in độc lập, KHÔNG dùng layout.master. Khung nhãn khai ở config/material.php,
| in vào giữa cuộn tem thực (media_*_mm) nên chừa lề trắng đều ~1mm.
|
| Bố cục kẻ ô 3 tầng:
|   1. TÊN VẬT TƯ (chữ to, canh giữa) - chia đều chiều cao còn lại với ô Mục đích, ô nào
|      nhiều chữ hơn thì được cao hơn
|   2. Lưới 2x2 (vị trí, ngày nhập, người nhập, hạn dùng - tiêu đề nhỏ nằm trên giá trị
|      nên không tốn cột tiêu đề) + MÃ QR có logo Stella bên phải
|   3. Mục đích sử dụng chạy hết bề ngang nhãn
| Ngày in dạng dd/mm/yyyy.
|
| Nội dung KHÔNG được cắt bằng "…": giá trị dài thì xuống dòng, vẫn không vừa ô thì
| script cuối trang tự thu nhỏ chữ (chạy trước khi thanh công cụ nhân bản nhãn).
|
| QR (App\Support\QrCode) mức sửa lỗi Q để chịu được logo đè giữa. Số lượng nhãn chọn
| trên thanh công cụ (pages.import.shared.labelToolbar), mỗi lần in ghi audit log qua
| pages.import.materialImport.labelPrinted.
--}}

@php
    $lblDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '';
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

    // Cỡ chữ khởi điểm theo độ dài; còn tràn ô thì script fit cuối trang thu nhỏ tiếp
    $lblValSize = function ($value) {
        $n = mb_strlen(trim((string) $value));
        return $n > 40 ? 1.6 : ($n > 24 ? 1.8 : ($n > 16 ? 2.0 : 2.3));
    };

    $lblPurpose = trim((string) ($import->purpose ?? ''));

    // Lưới 2x2 cạnh QR, đọc theo hàng: Vị trí | Ngày nhập, Người nhập | Hạn dùng
    $lblCells = [
        'Vị Trí' => $import->location_code ?: '—',
        'Ngày Nhập' => $lblDate($import->imported_date) ?: '—',
        'Người Nhập' => $import->imported_by ?: '—',
        'Hạn Dùng' => $lblDate($import->expired_date) ?: '—',
    ];

    // Biểu tượng Stella (viewBox 60x53): dùng ở góc trên trái ô tên và giữa mã QR
    $lblLogoPath = 'm116.5 2718.38-23.354 11.12v5.06a8.664 8.664 0 0 0 8.573 8.75h16.125a1.175 1.175 0 0 1 0 2.35h-16.125a8.664 8.664 0 0 0 -8.573 8.75v11.29a1.153 1.153 0 1 1 -2.305 0v-11.29a8.664 8.664 0 0 0 -8.572-8.75h-16.114a1.175 1.175 0 0 1 0-2.35h16.114a8.664 8.664 0 0 0 8.572-8.75v-5.06l-23.341-11.12a3.861 3.861 0 0 0 -5.5 3.56v29.21a5.167 5.167 0 0 0 2.52 4.45l24.963 14.7a4.944 4.944 0 0 0 5.038 0l24.963-14.7a5.167 5.167 0 0 0 2.519-4.45v-29.21a3.861 3.861 0 0 0 -5.503-3.56z';
    $lblLogoMm = 1.68;  // bề rộng biểu tượng ở logo góc ô tên (chữ STELLA cao tương ứng)
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Nhãn lô vật tư - {{ $import->code }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">

    <style>
        /* Font chữ STELLA ở logo - nạp từ public, server không có internet */
        @font-face {
            font-family: "Arimo";
            src: url("{{ asset('fonts/Arimo/Arimo-Bold.ttf') }}") format("truetype");
            font-weight: 700;
            font-style: normal;
        }

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

        /* Ô tên và ô Mục đích chia đều phần còn lại (flex 1 1 0). Không đặt overflow nên
           min-height tự = chiều cao nội dung: ô nào nhiều chữ hơn nửa phần được chia thì
           cao lên, ô kia nhường lại. Cả hai cùng tràn thì script fit thu nhỏ chữ ô lớn hơn. */
        .name,
        .purpose {
            flex: 1 1 0;
        }

        /* ---------- Trên: tên vật tư ---------- */
        /* Lề trên chừa dải logo, tên canh giữa phần dưới - giữ trọn bề ngang cho tên */
        .name {
            position: relative;
            padding: {{ round($lblLogoMm * 53 / 60, 2) + 1.2 }}mm 1mm 0.8mm;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: {{ $lblNameSize }}mm;
            font-weight: 700;
            line-height: 1.15;
            word-break: break-word;
            border-bottom: 0.25mm solid #000;
        }

        /* Logo góc trên trái: biểu tượng + chữ STELLA (đen - máy in nhãn in 1 màu) */
        .name-brand {
            position: absolute;
            top: 0.6mm;
            left: 0.8mm;
            display: flex;
            align-items: center;
            gap: 0.5mm;
        }

        .name-brand svg {
            width: {{ $lblLogoMm }}mm;
            height: {{ round($lblLogoMm * 53 / 60, 2) }}mm;
            display: block;
        }

        .name-brand span {
            font-family: "Arimo", Arial, sans-serif;
            font-size: {{ round($lblLogoMm * 53 / 60 * 1.05, 2) }}mm;
            font-weight: 700;
            letter-spacing: 0.08mm;
            line-height: 1;
        }

        /* ---------- Giữa: lưới 2x2 (trái) + QR (phải), cao bằng ô QR ---------- */
        .mid {
            display: flex;
            flex: 0 0 {{ $qrCellMm }}mm;
            border-bottom: 0.25mm solid #000;
        }

        .grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            border-right: 0.25mm solid #000;
            min-width: 0;
        }

        /* Ô lưới: tiêu đề nhỏ góc trên trái, giá trị canh giữa phần còn lại */
        .grid .cell {
            display: flex;
            flex-direction: column;
            padding: 0.3mm 0.8mm;
            overflow: hidden;
            min-width: 0;
            min-height: 0;
        }

        .grid .cell:nth-child(odd) { border-right: 0.25mm solid #000; }
        .grid .cell:nth-child(-n+2) { border-bottom: 0.25mm solid #000; }

        .grid .cell .value {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        /* Mã vị trí, ngày: giữ 1 dòng, dài thì script fit thu nhỏ chữ */
        .grid .cell .value.nowrap { white-space: nowrap; }

        /* ---------- Dưới: mục đích sử dụng, hết bề ngang ---------- */
        .purpose {
            display: flex;
            flex-direction: column;
            padding: 0.3mm 0.8mm;
        }

        /* Như ô lưới: tiêu đề góc trên trái, giá trị canh giữa phần còn lại */
        .purpose .value {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .caption { font-size: 1.5mm; font-weight: 600; line-height: 1.1; white-space: nowrap; }

        .value {
            font-weight: 700;
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
            <div class="name">
                <span class="name-brand">
                    <svg viewBox="0 0 60 53" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#000" fill-rule="evenodd" transform="translate(-62 -2718)" d="{{ $lblLogoPath }}" />
                    </svg>
                    <span>STELLA</span>
                </span>
                <span>{{ $lblName ?: '—' }}</span>
            </div>

            <div class="mid">
                <div class="grid">
                    @foreach ($lblCells as $lblCaption => $lblValue)
                        <div class="cell" data-fit>
                            <span class="caption">{{ $lblCaption }}</span>
                            <span class="value {{ $lblCaption === 'Người Nhập' ? '' : 'nowrap' }}" style="font-size: {{ $lblValSize($lblValue) }}mm">{{ $lblValue }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="qr">
                    @if (!empty($qr['svg']))
                        <div class="qr-wrap">
                            {!! $qr['svg'] !!}
                            <span class="qr-logo-box">
                                <svg viewBox="0 0 60 53" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="#000" fill-rule="evenodd" transform="translate(-62 -2718)" d="{{ $lblLogoPath }}" />
                                </svg>
                            </span>
                        </div>
                    @else
                        <span class="qr-empty">Mã "{{ $import->code }}" không tạo được QR</span>
                    @endif
                    <div class="qr-code">{{ $import->code }}</div>
                </div>
            </div>

            <div class="purpose">
                <span class="caption">Mục Đích</span>
                <span class="value" style="font-size: {{ $lblValSize($lblPurpose ?: '—') }}mm">{{ $lblPurpose ?: '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Chạy ngay khi dựng xong nhãn gốc (trước DOMContentLoaded) để thanh công cụ nhân
         bản / chụp in Zebra đúng cỡ chữ đã thu nhỏ (tối thiểu ~1mm):
         - ô lưới [data-fit] còn tràn thì giảm dần cỡ chữ đến khi vừa;
         - tên + mục đích cùng tràn khỏi nhãn thì giảm chữ của ô đang cao hơn. --}}
    <script>
        (function () {
            var MIN_PX = 3.8;   // ~1mm
            var shrink = function (text) {
                var size = parseFloat(getComputedStyle(text).fontSize);
                if (size <= MIN_PX) return false;
                text.style.fontSize = (size - 0.3) + 'px';
                return true;
            };

            document.querySelectorAll('#labelStack [data-fit]').forEach(function (box) {
                var text = box.querySelector('.value') || box;
                while ((box.scrollHeight > box.clientHeight + 0.5 || box.scrollWidth > box.clientWidth + 0.5) && shrink(text)) {}
            });

            var label = document.querySelector('#labelStack .label');
            var name = label.querySelector('.name');
            var purpose = label.querySelector('.purpose');
            var purposeText = purpose.querySelector('.value');

            while (label.scrollHeight > label.clientHeight + 0.5) {
                var first = name.offsetHeight >= purpose.offsetHeight ? [name, purposeText] : [purposeText, name];
                if (!shrink(first[0]) && !shrink(first[1])) break;
            }
        })();
    </script>

</body>

</html>
