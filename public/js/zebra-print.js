/*
 |------------------------------------------------------------------------------
 | ZebraPrint - in nhãn thẳng ra máy in Zebra qua Zebra Browser Print
 |------------------------------------------------------------------------------
 | Không dùng Zebra BrowserPrint SDK (tránh phụ thuộc file ngoài): chỉ gọi HTTP
 | tới dịch vụ Browser Print chạy nền trên máy trạm.
 |
 |   GET  <base>available            -> danh sách máy in
 |   GET  <base>default?type=printer -> máy in mặc định
 |   POST <base>write  {device,data} -> gửi chuỗi ZPL ra máy in
 |   POST <base>read   {device}      -> đọc phản hồi từ máy in
 |
 | <base> = http://localhost:9100/  khi trang chạy HTTP
 |          https://127.0.0.1:9101/ khi trang chạy HTTPS (chứng chỉ tự ký, lần đầu
 |          phải mở thẳng URL đó bấm "proceed" để trình duyệt tin tưởng).
 |
 | Nhãn tiếng Việt có dấu: render phần tử HTML của nhãn ra ảnh 1-bit bằng
 | html2canvas rồi nhúng vào lệnh ^GFA - không cần nạp font vào máy in.
 |
 | Phụ thuộc: html2canvas (public/js/html2canvas.min.js) phải nạp trước file này.
 */
(function (global) {
    'use strict';

    var HTTP_BASE = 'http://localhost:9100/';
    var HTTPS_BASE = 'https://127.0.0.1:9101/';

    /** Địa chỉ dịch vụ Browser Print theo giao thức trang đang chạy. */
    function resolveBase() {
        return global.location && global.location.protocol === 'https:' ? HTTPS_BASE : HTTP_BASE;
    }

    function jsonHeaders() {
        // text/plain = "simple request", không kích hoạt CORS preflight; Browser
        // Print vẫn tự parse body thành JSON.
        return { 'Content-Type': 'text/plain;charset=UTF-8' };
    }

    /** Browser Print trả { "printer": [ {...} ] } hoặc mảng thẳng - gộp hết về 1 mảng. */
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

    /** Danh sách máy in Zebra đang kết nối. */
    function listDevices() {
        return fetch(resolveBase() + 'available', { method: 'GET' })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(extractDevices);
    }

    /** Máy in mặc định đặt trong Browser Print (có thể không có). */
    function defaultDevice() {
        return fetch(resolveBase() + 'default?type=printer', { method: 'GET' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { return d && d.uid ? d : null; })
            .catch(function () { return null; });
    }

    /**
     * Chọn máy in để in: ưu tiên uid chỉ định -> máy in mặc định -> máy đầu danh sách.
     * Trả về { device, devices } hoặc reject nếu không có máy nào / không kết nối được.
     */
    function resolvePrinter(preferredUid) {
        return Promise.all([listDevices(), defaultDevice()]).then(function (res) {
            var devices = res[0] || [];
            var def = res[1];

            if (!devices.length) throw new Error('Không tìm thấy máy in Zebra nào.');

            var pick = null;
            if (preferredUid) {
                pick = devices.filter(function (d) { return d.uid === preferredUid; })[0] || null;
            }
            if (!pick && def) {
                pick = devices.filter(function (d) { return d.uid === def.uid; })[0] || def;
            }
            if (!pick) pick = devices[0];

            return { device: pick, devices: devices };
        });
    }

    /** Gửi chuỗi bất kỳ (ZPL) ra máy in. */
    function sendRaw(device, data) {
        return fetch(resolveBase() + 'write', {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ device: device, data: data })
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return true;
        });
    }

    /** Ghi ~HQES rồi đọc phản hồi trạng thái máy in (chuỗi thô). */
    function readStatus(device) {
        return sendRaw(device, '~HQES')
            .then(function () {
                return fetch(resolveBase() + 'read', {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify({ device: device })
                });
            })
            .then(function (r) { return r.ok ? r.text() : Promise.reject(new Error('HTTP ' + r.status)); });
    }

    /**
     * Ảnh canvas -> mảnh lệnh đồ hoạ ^GFA (đen trắng 1 bit).
     * threshold: điểm ảnh có độ sáng < ngưỡng (và không trong suốt) coi là chấm đen.
     */
    function canvasToGfa(canvas, threshold) {
        threshold = threshold == null ? 180 : threshold;

        var w = canvas.width;
        var h = canvas.height;
        var ctx = canvas.getContext('2d');
        var px = ctx.getImageData(0, 0, w, h).data;

        var bytesPerRow = Math.ceil(w / 8);
        var total = bytesPerRow * h;
        var hex = '';
        var HEXCHARS = '0123456789ABCDEF';

        for (var y = 0; y < h; y++) {
            for (var b = 0; b < bytesPerRow; b++) {
                var byte = 0;

                for (var bit = 0; bit < 8; bit++) {
                    var x = b * 8 + bit;
                    if (x >= w) break;

                    var i = (y * w + x) * 4;
                    var alpha = px[i + 3];
                    var lum = 0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2];

                    if (alpha > 128 && lum < threshold) {
                        byte |= (0x80 >> bit);
                    }
                }

                hex += HEXCHARS[(byte >> 4) & 0xF] + HEXCHARS[byte & 0xF];
            }
        }

        return {
            zpl: '^GFA,' + total + ',' + total + ',' + bytesPerRow + ',' + hex,
            widthDots: bytesPerRow * 8,
            heightDots: h
        };
    }

    /** mm -> số chấm in theo dpi. */
    function mmToDots(mm, dpi) {
        return Math.round((mm * dpi) / 25.4);
    }

    /**
     * Render 1 phần tử HTML nhãn -> chuỗi ZPL hoàn chỉnh (^XA...^XZ).
     * opts: {
     *   widthMm, heightMm,            kích thước KHUNG nhãn (nội dung)
     *   mediaWidthMm, mediaHeightMm,  kích thước CUỘN nhãn thực (mặc định = khung);
     *                                 lớn hơn khung thì khung được canh giữa tem
     *   dpi=203, copies=1, threshold=180
     * }
     */
    function elementToZpl(element, opts) {
        opts = opts || {};
        var dpi = opts.dpi || 203;
        var copies = Math.max(1, parseInt(opts.copies, 10) || 1);
        var scale = dpi / 96;                       // 1 CSS px = 1/96 inch
        var mediaWMm = opts.mediaWidthMm || opts.widthMm;
        var mediaHMm = opts.mediaHeightMm || opts.heightMm;

        if (!global.html2canvas) {
            return Promise.reject(new Error('Thiếu thư viện html2canvas.'));
        }

        // html2canvas cần phần tử nằm trong DOM và hiển thị. Dựng bản sao ngoài
        // màn hình ở đúng kích thước mm thật để rasterize.
        var host = document.createElement('div');
        host.style.cssText = 'position:fixed;left:-10000px;top:0;margin:0;padding:0;background:#fff;z-index:-1;';

        var clone = element.cloneNode(true);
        clone.style.margin = '0';
        clone.style.boxShadow = 'none';
        if (opts.widthMm) clone.style.width = opts.widthMm + 'mm';
        if (opts.heightMm) clone.style.height = opts.heightMm + 'mm';

        host.appendChild(clone);
        document.body.appendChild(host);

        return global.html2canvas(clone, {
            scale: scale,
            backgroundColor: '#ffffff',
            logging: false,
            useCORS: true
        }).then(function (canvas) {
            document.body.removeChild(host);

            var g = canvasToGfa(canvas, opts.threshold);

            // Canh khung nhãn vào giữa cuộn tem: nếu tem to hơn ảnh thì chừa lề đều.
            var mediaWDots = mediaWMm ? Math.max(g.widthDots, mmToDots(mediaWMm, dpi)) : g.widthDots;
            var mediaHDots = mediaHMm ? Math.max(g.heightDots, mmToDots(mediaHMm, dpi)) : g.heightDots;
            var offsetX = Math.max(0, Math.round((mediaWDots - g.widthDots) / 2));
            var offsetY = Math.max(0, Math.round((mediaHDots - g.heightDots) / 2));

            return [
                '^XA',
                '^CI28',
                '^PW' + mediaWDots,
                '^LL' + mediaHDots,
                '^LH0,0',
                '^FO' + offsetX + ',' + offsetY + g.zpl + '^FS',
                '^PQ' + copies + ',0,0,Y',
                '^XZ'
            ].join('\n');
        }).catch(function (e) {
            if (host.parentNode) document.body.removeChild(host);
            throw e;
        });
    }

    /**
     * Render nhãn rồi in luôn ra máy Zebra.
     * opts: { element, device, widthMm, heightMm, dpi, copies, threshold }
     */
    function printElement(opts) {
        opts = opts || {};
        if (!opts.element) return Promise.reject(new Error('Chưa có phần tử nhãn để in.'));
        if (!opts.device) return Promise.reject(new Error('Chưa chọn máy in.'));

        return elementToZpl(opts.element, opts).then(function (zpl) {
            return sendRaw(opts.device, zpl);
        });
    }

    global.ZebraPrint = {
        resolveBase: resolveBase,
        listDevices: listDevices,
        defaultDevice: defaultDevice,
        resolvePrinter: resolvePrinter,
        sendRaw: sendRaw,
        readStatus: readStatus,
        elementToZpl: elementToZpl,
        printElement: printElement,
        deviceLabel: function (d) {
            if (!d) return '(không rõ)';
            var name = d.name || d.uid || 'Không tên';
            var extra = [d.connection, d.manufacturer].filter(Boolean).join(' · ');
            return extra ? name + ' (' + extra + ')' : name;
        }
    };
})(window);
