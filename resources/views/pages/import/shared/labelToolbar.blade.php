{{--
| NHẬP - THANH CÔNG CỤ TRANG IN NHÃN (dùng chung cho nhãn vật tư và nhãn hoá chất)
|
| Gồm ô chọn SỐ LƯỢNG NHÃN, ô chọn MÁY IN ZEBRA và 2 cách in:
|
|  1. "In ra máy Zebra"  (mặc định) - render phần tử nhãn thành ảnh 1-bit bằng
|     html2canvas rồi gửi thẳng lệnh ^GFA tới máy in qua Zebra Browser Print. Không
|     mở hộp thoại in, không header/footer, khổ luôn đúng, tiếng Việt có dấu.
|
|  2. "In bằng trình duyệt" - cách cũ: window.print(). Dùng khi máy trạm chưa cài
|     Zebra Browser Print. Nhân bản nhãn ngay trên trang (#labelStack) để xem trước
|     đúng số lượng.
|
| MỖI LẦN IN ĐỀU GHI AUDIT LOG trước khi in (POST $logUrl). Ghi hỏng thì KHÔNG in,
| để nhật ký không bao giờ thiếu so với số nhãn thực in. Bấm Ctrl+P cũng ghi (bắt
| qua beforeprint).
|
| Biến truyền vào:
|   $importId    id phiếu nhập đang in nhãn
|   $logUrl      route ghi audit log in nhãn (POST)
|   $backUrl     route quay lại màn hình nhập
|   $maxCopies   số nhãn tối đa cho một lần in
|   $lblWidth    chiều rộng nhãn (mm)
|   $lblHeight   chiều cao nhãn (mm)
|   $dpi         (tuỳ chọn) độ phân giải đầu in Zebra, mặc định 203
|   $printerNote (tuỳ chọn) câu nhắc thêm về máy in
--}}

@php($lblDpi = $dpi ?? 203)
@php($lblMediaW = $mediaW ?? $lblWidth)
@php($lblMediaH = $mediaH ?? $lblHeight)

<script src="{{ asset('js/html2canvas.min.js') }}"></script>
<script src="{{ asset('js/zebra-print.js') }}"></script>

<style>
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

    .toolbar .go:hover:not(:disabled) {
        background: #1F5E9E;
        transform: translateY(-1px);
    }

    .toolbar .go:disabled {
        background: #9CC7EE;
        cursor: not-allowed;
        transform: none;
    }

    .toolbar .ghost {
        background: #EAF3FC;
        color: #1F5E9E;
    }

    .toolbar .ghost:hover:not(:disabled) {
        background: #D9E9F9;
    }

    .toolbar .ghost:disabled {
        opacity: .55;
        cursor: not-allowed;
    }

    .toolbar .back {
        background: #EAF3FC;
        color: #1F5E9E;
    }

    .toolbar .back:hover {
        background: #D9E9F9;
    }

    .toolbar .note {
        width: 100%;
        margin: 0;
        text-align: center;
        color: #64748B;
        font-size: 12px;
        font-weight: 400;
    }

    /* ---------- Ô chọn số lượng nhãn ---------- */
    .copies {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px 4px 14px;
        background: #EAF3FC;
        border-radius: 8px;
    }

    .copies label {
        font-size: 14px;
        font-weight: 600;
        color: #1F5E9E;
        white-space: nowrap;
    }

    .copies .step {
        width: 30px;
        height: 30px;
        padding: 0;
        border-radius: 8px;
        background: #fff;
        color: #1F5E9E;
        font-size: 17px;
        font-weight: 700;
        line-height: 1;
    }

    .copies .step:hover {
        background: #2E7BC4;
        color: #fff;
        transform: none;
    }

    .copies input {
        width: 68px;
        height: 30px;
        padding: 0 6px;
        border: 1px solid #9CC7EE;
        border-radius: 8px;
        background: #fff;
        color: #2D3748;
        font-size: 14px;
        font-weight: 700;
        text-align: center;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .copies input:focus {
        outline: 0;
        border-color: #2E7BC4;
        box-shadow: 0 0 0 3px rgba(46, 123, 196, .18);
    }

    /* ---------- Ô chọn máy in Zebra ---------- */
    .printer {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px 4px 14px;
        background: #EAF3FC;
        border-radius: 8px;
    }

    .printer label {
        font-size: 14px;
        font-weight: 600;
        color: #1F5E9E;
        white-space: nowrap;
    }

    .printer select {
        height: 30px;
        max-width: 260px;
        padding: 0 6px;
        border: 1px solid #9CC7EE;
        border-radius: 8px;
        background: #fff;
        color: #2D3748;
        font-size: 13px;
        font-weight: 600;
    }

    .printer select:focus {
        outline: 0;
        border-color: #2E7BC4;
        box-shadow: 0 0 0 3px rgba(46, 123, 196, .18);
    }

    .printer .rescan {
        width: 30px;
        height: 30px;
        padding: 0;
        border-radius: 8px;
        background: #fff;
        color: #1F5E9E;
        font-size: 14px;
        line-height: 1;
    }

    .printer .rescan:hover {
        background: #2E7BC4;
        color: #fff;
    }

    .printer .pstate {
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .printer .pstate.ok { color: #15803D; }
    .printer .pstate.bad { color: #B91C1C; }
    .printer .pstate.wait { color: #B45309; }

    /* ---------- Dòng báo kết quả ---------- */
    .toolbar .log-state {
        width: 100%;
        margin: 0;
        text-align: center;
        font-size: 12.5px;
        font-weight: 600;
    }

    .toolbar .log-state.ok { color: #16A34A; }
    .toolbar .log-state.fail { color: #DC2626; }

    /* ---------- Khi in bằng trình duyệt: giấu thanh công cụ, mỗi nhãn một trang ---------- */
    @media print {
        .toolbar {
            display: none !important;
        }

        #labelStack .label {
            page-break-after: always;
            break-after: page;
        }

        #labelStack .label:last-child {
            page-break-after: auto;
            break-after: auto;
        }
    }
</style>

<div class="toolbar">

    <div class="copies">
        <label for="copies">Số lượng nhãn</label>
        <button type="button" class="step" id="copiesMinus" title="Bớt 1 nhãn">&minus;</button>
        <input type="number" id="copies" name="copies" value="1" min="1" max="{{ $maxCopies }}" step="1"
            title="Số nhãn sẽ in, tối đa {{ $maxCopies }} nhãn một lần">
        <button type="button" class="step" id="copiesPlus" title="Thêm 1 nhãn">+</button>
    </div>

    <div class="printer">
        <label for="zebraPrinter">Máy in</label>
        <select id="zebraPrinter" title="Máy in Zebra qua Browser Print">
            <option value="">— đang dò —</option>
        </select>
        <button type="button" class="rescan" id="btnRescan" title="Dò lại máy in">&#8635;</button>
        <span class="pstate wait" id="printerState">…</span>
    </div>

    <button type="button" class="go" id="btnPrint">In ra máy Zebra</button>
    <button type="button" class="ghost" id="btnBrowserPrint" title="Mở hộp thoại in của trình duyệt">In bằng trình duyệt</button>
    <a class="back" href="{{ $backUrl }}">Quay lại</a>

    <p class="note">
        Khung nhãn {{ $lblWidth }}x{{ $lblHeight }}mm @ {{ $lblDpi }} dpi.
        @if ($lblMediaW != $lblWidth || $lblMediaH != $lblHeight)
            Khung canh giữa cuộn tem {{ $lblMediaW }}x{{ $lblMediaH }}mm.
        @endif
        {{ $printerNote ?? '' }}
        <b>In ra máy Zebra</b>: gửi thẳng, không cần chỉnh gì. <b>In bằng trình duyệt</b>: trong hộp thoại
        đặt khổ giấy <b>{{ $lblMediaW }} x {{ $lblMediaH }} mm</b>, lề <b>None</b>, bỏ tick
        <b>Headers and footers</b>. Mỗi lần in đều ghi vào <b>Audit Trail</b>.
    </p>

    <p class="log-state" id="logState"></p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var MAX = {{ (int) $maxCopies }};
        var LOG_URL = '{{ $logUrl }}';
        var IMPORT_ID = '{{ $importId }}';
        var TOKEN = '{{ csrf_token() }}';
        var LBL_W = {{ (float) $lblWidth }};
        var LBL_H = {{ (float) $lblHeight }};
        var MEDIA_W = {{ (float) $lblMediaW }};
        var MEDIA_H = {{ (float) $lblMediaH }};
        var DPI = {{ (int) $lblDpi }};
        var PRINTER_KEY = 'wms.zebra.printerUid';

        var stack = document.getElementById('labelStack');
        var input = document.getElementById('copies');
        var btnPrint = document.getElementById('btnPrint');
        var btnBrowserPrint = document.getElementById('btnBrowserPrint');
        var btnRescan = document.getElementById('btnRescan');
        var selPrinter = document.getElementById('zebraPrinter');
        var printerState = document.getElementById('printerState');
        var logState = document.getElementById('logState');

        // Bản gốc của nhãn (1 cái), giữ lại để nhân ra khi in trình duyệt và để rasterize khi in Zebra
        var master = stack.firstElementChild.cloneNode(true);

        var zebraDevices = [];
        var alreadyLogged = false;   // tránh ghi nhật ký 2 lần khi bấm nút rồi beforeprint lại chạy

        /* ---------------- Số lượng nhãn + xem trước ---------------- */

        function copies() {
            var n = parseInt(input.value, 10);
            if (isNaN(n) || n < 1) n = 1;
            return n > MAX ? MAX : n;
        }

        function render() {
            var n = copies();
            input.value = n;
            stack.innerHTML = '';
            for (var i = 0; i < n; i++) {
                stack.appendChild(master.cloneNode(true));
            }
        }

        function say(message, isOk) {
            logState.textContent = message;
            logState.className = 'log-state ' + (isOk ? 'ok' : 'fail');
        }

        input.addEventListener('input', function () {
            if (input.value !== '') render();
        });
        input.addEventListener('change', render);
        document.getElementById('copiesMinus').addEventListener('click', function () {
            input.value = copies() - 1;
            render();
        });
        document.getElementById('copiesPlus').addEventListener('click', function () {
            input.value = copies() + 1;
            render();
        });

        /* ---------------- Ghi audit log ---------------- */

        function logPrint(n, beacon) {
            var body = new FormData();
            body.append('_token', TOKEN);
            body.append('id', IMPORT_ID);
            body.append('copies', n);

            if (beacon && navigator.sendBeacon) {
                return Promise.resolve(navigator.sendBeacon(LOG_URL, body));
            }

            return fetch(LOG_URL, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) { return response.ok; });
        }

        /* ---------------- Máy in Zebra ---------------- */

        function setPrinterState(kind, text) {
            printerState.className = 'pstate ' + kind;
            printerState.textContent = text;
        }

        function fillPrinters(devices) {
            zebraDevices = devices || [];
            selPrinter.innerHTML = '';

            if (!zebraDevices.length) {
                selPrinter.innerHTML = '<option value="">— không thấy máy in —</option>';
                btnPrint.disabled = true;
                return;
            }

            var saved = null;
            try { saved = localStorage.getItem(PRINTER_KEY); } catch (e) {}

            zebraDevices.forEach(function (d, i) {
                var opt = document.createElement('option');
                opt.value = String(i);
                opt.textContent = ZebraPrint.deviceLabel(d);
                if (saved && d.uid === saved) opt.selected = true;
                selPrinter.appendChild(opt);
            });

            btnPrint.disabled = false;
        }

        function selectedDevice() {
            var i = parseInt(selPrinter.value, 10);
            return isNaN(i) ? null : zebraDevices[i];
        }

        selPrinter.addEventListener('change', function () {
            var d = selectedDevice();
            try { if (d) localStorage.setItem(PRINTER_KEY, d.uid); } catch (e) {}
        });

        function scanPrinters() {
            setPrinterState('wait', 'đang dò…');
            btnPrint.disabled = true;
            selPrinter.innerHTML = '<option value="">— đang dò —</option>';

            var saved = null;
            try { saved = localStorage.getItem(PRINTER_KEY); } catch (e) {}

            ZebraPrint.resolvePrinter(saved)
                .then(function (res) {
                    fillPrinters(res.devices);
                    setPrinterState('ok', res.devices.length + ' máy in');
                })
                .catch(function (e) {
                    fillPrinters([]);
                    setPrinterState('bad', 'không kết nối Browser Print');
                    say('Không thấy Zebra Browser Print trên máy này. Dùng nút "In bằng trình duyệt".', false);
                });
        }

        btnRescan.addEventListener('click', scanPrinters);

        /* ---------------- In ra máy Zebra (mặc định) ---------------- */

        btnPrint.addEventListener('click', function () {
            var device = selectedDevice();
            if (!device) {
                say('Chưa chọn được máy in Zebra. Bấm nút dò lại hoặc kiểm tra Browser Print.', false);
                return;
            }

            var n = copies();
            btnPrint.disabled = true;
            btnBrowserPrint.disabled = true;
            say('Đang ghi nhật ký in nhãn…', true);

            logPrint(n, false).then(function (ok) {
                if (!ok) {
                    btnPrint.disabled = false;
                    btnBrowserPrint.disabled = false;
                    say('Chưa ghi được nhật ký in nhãn nên chưa in. Vui lòng thử lại.', false);
                    return;
                }

                say('Đang dựng ảnh nhãn và gửi tới máy in…', true);

                return ZebraPrint.printElement({
                    element: master,
                    device: device,
                    widthMm: LBL_W,
                    heightMm: LBL_H,
                    mediaWidthMm: MEDIA_W,
                    mediaHeightMm: MEDIA_H,
                    dpi: DPI,
                    copies: n
                }).then(function () {
                    say('Đã gửi ' + n + ' nhãn tới "' + ZebraPrint.deviceLabel(device) + '" và ghi Audit Trail.', true);
                }).catch(function (e) {
                    say('Đã ghi nhật ký nhưng gửi máy in lỗi: ' + e.message + '. Thử "In bằng trình duyệt".', false);
                }).then(function () {
                    btnPrint.disabled = false;
                    btnBrowserPrint.disabled = false;
                });
            }).catch(function () {
                btnPrint.disabled = false;
                btnBrowserPrint.disabled = false;
                say('Chưa ghi được nhật ký in nhãn nên chưa in. Vui lòng thử lại.', false);
            });
        });

        /* ---------------- In bằng trình duyệt (dự phòng) ---------------- */

        btnBrowserPrint.addEventListener('click', function () {
            var n = copies();
            btnPrint.disabled = true;
            btnBrowserPrint.disabled = true;
            say('Đang ghi nhật ký in nhãn…', true);

            logPrint(n, false).then(function (ok) {
                btnPrint.disabled = false;
                btnBrowserPrint.disabled = false;

                if (!ok) {
                    say('Chưa ghi được nhật ký in nhãn nên chưa in. Vui lòng thử lại.', false);
                    return;
                }

                say('Đã ghi nhật ký in ' + n + ' nhãn vào Audit Trail.', true);
                alreadyLogged = true;
                window.print();
            }).catch(function () {
                btnPrint.disabled = false;
                btnBrowserPrint.disabled = false;
                say('Chưa ghi được nhật ký in nhãn nên chưa in. Vui lòng thử lại.', false);
            });
        });

        // In bằng Ctrl+P / menu trình duyệt: vẫn phải có nhật ký
        window.addEventListener('beforeprint', function () {
            if (alreadyLogged) return;
            alreadyLogged = true;
            logPrint(copies(), true);
            say('Đã ghi nhật ký in ' + copies() + ' nhãn vào Audit Trail.', true);
        });

        window.addEventListener('afterprint', function () {
            alreadyLogged = false;
        });

        render();
        scanPrinters();
    });
</script>
