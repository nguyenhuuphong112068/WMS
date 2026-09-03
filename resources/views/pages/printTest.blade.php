{{--
| KIỂM TRA IN NHÃN QUA ZEBRA BROWSER PRINT
|
| Trang chẩn đoán độc lập (KHÔNG dùng layout.master). Mở trực tiếp /print-test trên
| máy trạm đã cài "Zebra Browser Print" và cắm máy in Zebra qua USB.
|
| Cách hoạt động:
|  1. Trang gọi HTTP tới dịch vụ Browser Print chạy nền ở http://localhost:9100
|     (hoặc https://127.0.0.1:9101 nếu WMS chạy HTTPS) - KHÔNG cần thư viện SDK,
|     chỉ dùng fetch() thuần nên không vi phạm quy tắc "không CDN".
|  2. GET /available   -> danh sách máy in đang kết nối
|     GET /default     -> máy in đặt mặc định trong Browser Print
|  3. POST /write { device, data } -> gửi chuỗi ZPL thẳng ra máy in.
|
| Nhãn mẫu để đúng khổ nhãn lô vật tư khai ở config/material.php (mặc định 50x30mm),
| in ở 203 dpi = 8 chấm/mm.
--}}

@php
    $lblW = (int) ($label['width_mm'] ?? 50);
    $lblH = (int) ($label['height_mm'] ?? 30);
    $dpmm = 8;                       // 203 dpi
    $pw = $lblW * $dpmm;             // bề rộng nhãn tính bằng chấm
    $ll = $lblH * $dpmm;             // chiều cao nhãn tính bằng chấm

    // Nhãn ZPL mẫu - dữ liệu giả, chỉ để thử đường in
    $sampleZpl = implode("\n", [
        '^XA',
        '^CI28',                     // nhận chuỗi UTF-8
        "^PW{$pw}",
        "^LL{$ll}",
        '^LH0,0',
        '^FO16,14^A0N,30,30^FDNHAN THU NGHIEM^FS',
        "^FO16,50^GB" . ($pw - 32) . ",0,2^FS",
        '^FO16,64^A0N,26,26^FDTen: Acid HCl 37%^FS',
        '^FO16,98^A0N,24,24^FDVi tri: A1-01^FS',
        '^FO16,128^A0N,24,24^FDNgay nhap: 03-Sep-26^FS',
        '^FO16,158^A0N,24,24^FDHan dung: 03-Sep-27^FS',
        '^FO16,192^A0N,22,22^FDTieng Viet: Hoa chat - Han dung^FS',
        "^FO" . ($pw - 132) . ",92^BQN,2,4^FDLA,VT-TEST-0001^FS",
        "^FO" . ($pw - 140) . "," . ($ll - 26) . "^A0N,20,20^FDVT-TEST-0001^FS",
        '^XZ',
    ]);
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kiểm tra in nhãn Zebra</title>
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 28px 18px 60px;
            background: #F5F9FD;
            color: #2D3748;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            line-height: 1.5;
        }

        .wrap { max-width: 860px; margin: 0 auto; }

        h1 {
            margin: 0 0 4px;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #2E7BC4;
            text-transform: uppercase;
        }

        .lead { margin: 0 0 22px; color: #64748B; font-size: 13.5px; }

        .card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 20px 22px;
            margin-bottom: 18px;
            box-shadow: 0 1px 3px rgba(16, 42, 67, .06);
        }

        .card h2 {
            margin: 0 0 14px;
            font-size: 14px;
            font-weight: 700;
            color: #1F5E9E;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .row + .row { margin-top: 12px; }

        label.fld { font-size: 13px; font-weight: 600; color: #1F5E9E; }

        select, input[type="number"], input[type="text"] {
            height: 38px;
            padding: 0 10px;
            border: 1px solid #9CC7EE;
            border-radius: 8px;
            background: #fff;
            color: #2D3748;
            font-size: 14px;
            font-family: inherit;
        }

        select { min-width: 280px; }
        input[type="number"] { width: 80px; text-align: center; font-weight: 700; }

        select:focus, input:focus, textarea:focus {
            outline: 0;
            border-color: #2E7BC4;
            box-shadow: 0 0 0 3px rgba(46, 123, 196, .16);
        }

        button {
            border: 0;
            border-radius: 8px;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all .2s ease;
        }

        .btn-primary { background: #2E7BC4; color: #fff; }
        .btn-primary:hover:not(:disabled) { background: #1F5E9E; transform: translateY(-1px); }
        .btn-ghost { background: #EAF3FC; color: #1F5E9E; }
        .btn-ghost:hover:not(:disabled) { background: #D9E9F9; }
        button:disabled { opacity: .55; cursor: not-allowed; transform: none; }

        textarea {
            width: 100%;
            min-height: 230px;
            margin-top: 4px;
            padding: 12px;
            border: 1px solid #9CC7EE;
            border-radius: 8px;
            background: #0F172A;
            color: #E2E8F0;
            font-family: "Consolas", "Courier New", monospace;
            font-size: 12.5px;
            line-height: 1.55;
            resize: vertical;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 700;
        }

        .pill .dot { width: 9px; height: 9px; border-radius: 50%; background: currentColor; }
        .pill.ok { background: #DCFCE7; color: #15803D; }
        .pill.bad { background: #FEE2E2; color: #B91C1C; }
        .pill.wait { background: #FEF3C7; color: #B45309; }

        .hint { margin: 10px 0 0; font-size: 12.5px; color: #64748B; }
        .hint code { background: #EAF3FC; color: #1F5E9E; padding: 1px 5px; border-radius: 4px; }

        .log {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            font-family: "Consolas", "Courier New", monospace;
            font-size: 12.5px;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 220px;
            overflow: auto;
        }

        .log .line { padding: 2px 0; }
        .log .line.err { color: #B91C1C; }
        .log .line.good { color: #15803D; }
        .log .line.muted { color: #94A3B8; }
    </style>
</head>

<body>
    <div class="wrap">
        <h1>Kiểm tra in nhãn Zebra</h1>
        <p class="lead">
            Trang chẩn đoán đường in qua <b>Zebra Browser Print</b>. Chạy trên máy trạm đã cắm máy in Zebra
            và cài Browser Print. Khổ nhãn thử: <b>{{ $lblW }} x {{ $lblH }} mm</b> ({{ $pw }} x {{ $ll }} chấm @ 203 dpi).
        </p>

        <div class="card">
            <h2>1. Kết nối dịch vụ Browser Print</h2>
            <div class="row">
                <label class="fld" for="baseUrl">Địa chỉ dịch vụ</label>
                <select id="baseUrl">
                    <option value="http://localhost:9100/">http://localhost:9100/ (WMS chạy HTTP)</option>
                    <option value="https://127.0.0.1:9101/">https://127.0.0.1:9101/ (WMS chạy HTTPS)</option>
                </select>
                <button type="button" class="btn-ghost" id="btnReload">Dò lại máy in</button>
                <span id="connState" class="pill wait"><span class="dot"></span> Đang dò…</span>
            </div>
            <div class="row">
                <label class="fld" for="device">Máy in</label>
                <select id="device"><option value="">— chưa dò được —</option></select>
            </div>
            <p class="hint">
                Nếu báo lỗi kết nối: mở tab mới vào <code id="checkLink">http://localhost:9100/available</code> —
                phải thấy JSON danh sách máy in. Với HTTPS, lần đầu vào <code>https://127.0.0.1:9101/available</code>
                bấm <b>Advanced → proceed</b> để tin chứng chỉ tự ký của Browser Print.
            </p>
        </div>

        <div class="card">
            <h2>2. Nội dung nhãn (ZPL)</h2>
            <textarea id="zpl" spellcheck="false">{{ $sampleZpl }}</textarea>
            <p class="hint">
                Sửa trực tiếp để thử. <code>^CI28</code> = nhận UTF-8. Font mặc định <code>A0</code> của máy in
                <b>không có sẵn dấu tiếng Việt</b> — dòng "Tieng Viet" để không dấu là cố ý, để so sánh. Muốn in
                tiếng Việt có dấu đúng như nhãn hiện tại thì phải nạp font TTF vào máy in hoặc gửi nhãn dạng ảnh
                (bước sau).
            </p>
        </div>

        <div class="card">
            <h2>3. Gửi lệnh in</h2>
            <div class="row">
                <label class="fld" for="copies">Số nhãn</label>
                <input type="number" id="copies" value="1" min="1" max="20" step="1">
                <button type="button" class="btn-primary" id="btnPrint" disabled>In nhãn thử</button>
                <button type="button" class="btn-ghost" id="btnStatus" disabled>Đọc trạng thái máy in</button>
            </div>
            <div class="log" id="log"><span class="line muted">Nhật ký thao tác sẽ hiện ở đây…</span></div>
        </div>
    </div>

    <script>
        (function () {
            'use strict';

            var $ = function (id) { return document.getElementById(id); };
            var elBase = $('baseUrl'), elDevice = $('device'), elState = $('connState'),
                elLog = $('log'), elZpl = $('zpl'), elCopies = $('copies'),
                btnPrint = $('btnPrint'), btnStatus = $('btnStatus'), btnReload = $('btnReload');

            var devices = [];   // danh sách máy in lấy từ /available
            var firstLog = true;

            function log(msg, kind) {
                if (firstLog) { elLog.innerHTML = ''; firstLog = false; }
                var line = document.createElement('div');
                line.className = 'line' + (kind ? ' ' + kind : '');
                var t = new Date().toLocaleTimeString('vi-VN');
                line.textContent = '[' + t + '] ' + msg;
                elLog.appendChild(line);
                elLog.scrollTop = elLog.scrollHeight;
            }

            function base() { return elBase.value; }

            function setState(kind, text) {
                elState.className = 'pill ' + kind;
                elState.innerHTML = '<span class="dot"></span> ' + text;
            }

            function setPrintEnabled(on) {
                btnPrint.disabled = !on;
                btnStatus.disabled = !on;
            }

            // Browser Print trả object dạng { "printer": [ {...} ], ... } hoặc mảng thẳng.
            function extractDevices(data) {
                if (Array.isArray(data)) return data;
                if (data && Array.isArray(data.printer)) return data.printer;
                var out = [];
                if (data && typeof data === 'object') {
                    Object.keys(data).forEach(function (k) {
                        if (Array.isArray(data[k])) out = out.concat(data[k]);
                    });
                }
                return out;
            }

            function deviceLabel(d) {
                var name = d.name || d.uid || 'Không tên';
                var extra = [d.connection, d.manufacturer].filter(Boolean).join(' · ');
                return extra ? name + '  (' + extra + ')' : name;
            }

            function fillDevices(list, defaultUid) {
                devices = list || [];
                elDevice.innerHTML = '';

                if (!devices.length) {
                    elDevice.innerHTML = '<option value="">— không tìm thấy máy in —</option>';
                    setPrintEnabled(false);
                    return;
                }

                devices.forEach(function (d, i) {
                    var opt = document.createElement('option');
                    opt.value = String(i);
                    opt.textContent = deviceLabel(d);
                    if (defaultUid && d.uid === defaultUid) opt.selected = true;
                    elDevice.appendChild(opt);
                });
                setPrintEnabled(true);
            }

            function selectedDevice() {
                var i = parseInt(elDevice.value, 10);
                return isNaN(i) ? null : devices[i];
            }

            function discover() {
                setState('wait', 'Đang dò…');
                setPrintEnabled(false);
                elDevice.innerHTML = '<option value="">— đang dò —</option>';

                var defaultUid = null;

                // Lấy máy in mặc định trước (không bắt buộc thành công), rồi lấy danh sách.
                fetch(base() + 'default?type=printer', { method: 'GET' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .catch(function () { return null; })
                    .then(function (def) {
                        if (def && def.uid) { defaultUid = def.uid; }
                        return fetch(base() + 'available', { method: 'GET' });
                    })
                    .then(function (r) {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(function (data) {
                        var list = extractDevices(data);
                        fillDevices(list, defaultUid);
                        if (list.length) {
                            setState('ok', 'Đã kết nối · ' + list.length + ' máy in');
                            log('Kết nối Browser Print OK. Tìm thấy ' + list.length + ' máy in.', 'good');
                            list.forEach(function (d) { log('  • ' + deviceLabel(d) + (d.uid === defaultUid ? '  [mặc định]' : '')); });
                        } else {
                            setState('bad', 'Không thấy máy in');
                            log('Browser Print chạy nhưng chưa thấy máy in nào. Kiểm tra cáp USB / nguồn máy in.', 'err');
                        }
                    })
                    .catch(function (e) {
                        setState('bad', 'Không kết nối được');
                        fillDevices([]);
                        log('Không gọi được ' + base() + 'available — ' + e.message, 'err');
                        log('Kiểm tra: Zebra Browser Print đã chạy chưa? Đúng cổng chưa? (xem gợi ý phía trên)', 'muted');
                    });
            }

            // POST /write { device, data } — dùng Content-Type text/plain để tránh CORS preflight,
            // Browser Print vẫn parse body thành JSON.
            function writeToPrinter(device, data) {
                return fetch(base() + 'write', {
                    method: 'POST',
                    headers: { 'Content-Type': 'text/plain;charset=UTF-8' },
                    body: JSON.stringify({ device: device, data: data })
                });
            }

            btnReload.addEventListener('click', discover);

            elBase.addEventListener('change', function () {
                var host = base().replace(/\/$/, '');
                $('checkLink').textContent = host + '/available';
                discover();
            });

            btnPrint.addEventListener('click', function () {
                var device = selectedDevice();
                if (!device) { log('Chưa chọn máy in.', 'err'); return; }

                var n = Math.max(1, Math.min(20, parseInt(elCopies.value, 10) || 1));
                var one = elZpl.value.trim();
                if (!one) { log('Nội dung ZPL đang trống.', 'err'); return; }

                // Nhân bản nhãn ra đúng số lượng: nối nhiều khối ^XA…^XZ.
                var payload = new Array(n).fill(one).join('\n');

                setPrintEnabled(false);
                log('Gửi ' + n + ' nhãn tới "' + deviceLabel(device) + '"…');

                writeToPrinter(device, payload)
                    .then(function (r) {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        log('Đã gửi xong. Kiểm tra nhãn ra ở máy in.', 'good');
                    })
                    .catch(function (e) {
                        log('Gửi thất bại — ' + e.message, 'err');
                    })
                    .then(function () { setPrintEnabled(true); });
            });

            btnStatus.addEventListener('click', function () {
                var device = selectedDevice();
                if (!device) { log('Chưa chọn máy in.', 'err'); return; }

                setPrintEnabled(false);
                log('Hỏi trạng thái máy in (~HQES)…');

                fetch(base() + 'write', {
                    method: 'POST',
                    headers: { 'Content-Type': 'text/plain;charset=UTF-8' },
                    body: JSON.stringify({ device: device, data: '~HQES' })
                })
                    .then(function () {
                        return fetch(base() + 'read', {
                            method: 'POST',
                            headers: { 'Content-Type': 'text/plain;charset=UTF-8' },
                            body: JSON.stringify({ device: device })
                        });
                    })
                    .then(function (r) { return r.ok ? r.text() : Promise.reject(new Error('HTTP ' + r.status)); })
                    .then(function (txt) {
                        log('Máy in trả về:', 'good');
                        log(txt || '(rỗng)');
                    })
                    .catch(function (e) { log('Không đọc được trạng thái — ' + e.message, 'err'); })
                    .then(function () { setPrintEnabled(true); });
            });

            // Dò ngay khi mở trang
            discover();
        })();
    </script>
</body>

</html>
