{{--
| NHẬP - IN NHÃN DÁN ỐNG CHUẨN
|
| Trang in độc lập, KHÔNG dùng layout.master để bản in không dính menu / topNAV.
| Khổ giấy đặt đúng bằng khổ nhãn khai ở config/standard.php nên bấm In là ra thẳng
| nhãn trên máy in nhãn (Zebra ZD421), không phải căn lại trên khổ A4.
|
| Nhãn ống chuẩn nhỏ hơn nhãn chai hoá chất và không có dải cảnh báo an toàn; đổi lại
| in thêm VERSION và SỐ LÔ vì hai thông tin đó quyết định giá trị chuẩn dùng để tính.
|
| Mọi kích thước bên trong tính theo mm để in ra đúng bằng nhãn thật, không phụ thuộc
| độ phân giải màn hình. Mã vạch là SVG co giãn nên in ở 203dpi hay 300dpi đều sắc nét.
--}}

@php
    /** Ngày trên nhãn viết theo kiểu 19-Aug-26 cho khớp bản nhãn đang dùng. */
    $lblDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d-M-y') : '';

    $lblWidth = $label['width_mm'];
    $lblHeight = $label['height_mm'];

    // Tên chất chuẩn dài thì thu nhỏ chữ lại cho vừa một dòng thay vì tràn ra khỏi ô
    $lblName = $import->standard_name ?: '';
    $lblNameSize = mb_strlen($lblName) > 34 ? 2.1 : (mb_strlen($lblName) > 24 ? 2.5 : 3);
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Nhãn ống chuẩn - {{ $import->code }}</title>
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

        /* ---------- Thanh công cụ, chỉ hiện trên màn hình ---------- */
        .toolbar {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            padding: 14px;
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
            width: 100%;
            text-align: center;
            color: #64748B;
            font-size: 12px;
            font-weight: 400;
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
            padding: 0.5mm 0.9mm;
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
            font-size: 1.9mm;
            font-weight: 600;
        }

        .sop .cell {
            flex: 1;
        }

        /* Dòng tên chất chuẩn */
        .name {
            flex: 1;
            align-items: center;
            text-align: center;
            font-size: {{ $lblNameSize }}mm;
            font-weight: 700;
            line-height: 1.15;
        }

        /* Mã ống chuẩn - dòng riêng vì mã dài 13-14 ký tự */
        .code {
            flex: 1;
            align-items: center;
            text-align: center;
            font-size: 3mm;
            font-weight: 700;
            letter-spacing: 0.2mm;
            word-break: break-all;
        }

        /* Nhãn tiếng Việt / tiếng Anh của từng ô */
        .caption {
            font-size: 1.7mm;
            font-weight: 600;
            line-height: 1.15;
        }

        .value {
            font-size: 2.2mm;
            font-weight: 700;
            line-height: 1.15;
        }

        .info-row .cell {
            flex: 1;
            text-align: center;
            align-items: center;
        }

        .date-row .cell:nth-child(1),
        .date-row .cell:nth-child(3) {
            width: 13mm;
        }

        .date-row .cell:nth-child(2),
        .date-row .cell:nth-child(4) {
            flex: 1;
            text-align: center;
            align-items: center;
        }

        /* Mã vạch: SVG kéo full bề ngang, chừa lề trắng hai bên do chính SVG lo */
        .barcode {
            flex: 1;
            min-height: 6mm;
            padding: 0.5mm 0.9mm;
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
            font-size: 2mm;
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

            .toolbar {
                display: none;
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

    <div class="toolbar">
        <button type="button" class="go" onclick="window.print()">In nhãn</button>
        <a class="back" href="{{ route('pages.import.standardImport.list') }}">Quay lại</a>
        <p class="note">
            Khổ nhãn {{ $lblWidth }}x{{ $lblHeight }}mm. Trong hộp thoại In, chọn máy in nhãn
            <b>Zebra ZD421</b>, đặt khổ giấy <b>{{ $lblWidth }} x {{ $lblHeight }} mm</b>, lề <b>None</b>
            và bỏ tick <b>Headers and footers</b> để nhãn ra đúng như trên màn hình.
        </p>
    </div>

    <div class="label">

        <div class="row sop">
            <div class="cell"><span>SOP: {{ $label['sop_no'] }}</span></div>
            <div class="cell" style="text-align: right">{{ $label['form_no'] }}</div>
        </div>

        <div class="row" style="height: 6mm">
            <div class="cell name">{{ $lblName ?: '—' }}</div>
        </div>

        <div class="row" style="height: 5mm">
            <div class="cell code">{{ $import->code }}</div>
        </div>

        <div class="row info-row">
            <div class="cell">
                <span class="caption">Version</span>
                <span class="value">v{{ $import->category_version }}</span>
            </div>
            <div class="cell">
                <span class="caption">Số Lô/Lot</span>
                <span class="value">{{ $import->batch_no ?: '—' }}</span>
            </div>
            <div class="cell">
                <span class="caption">Vị trí/Loc.</span>
                <span class="value">{{ $import->location_code ?: '—' }}</span>
            </div>
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

        <div class="row barcode">
            @if ($barcode)
                {!! $barcode !!}
            @else
                <span class="barcode-empty">Mã "{{ $import->code }}" không tạo được mã vạch</span>
            @endif
        </div>
    </div>

</body>

</html>
