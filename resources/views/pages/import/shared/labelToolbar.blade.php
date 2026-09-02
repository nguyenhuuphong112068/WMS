{{--
| NHẬP - THANH CÔNG CỤ TRANG IN NHÃN (dùng chung cho nhãn vật tư và nhãn hoá chất)
|
| Gồm ô chọn SỐ LƯỢNG NHÃN cần in, nút In nhãn và đường dẫn quay lại. Số lượng nhãn
| nhân bản ngay trên trang bằng JS (nhân đôi phần tử .label đầu tiên trong #labelStack)
| nên xem trước được đúng số nhãn sẽ ra, không phải nạp lại trang.
|
| MỖI LẦN IN ĐỀU GHI AUDIT LOG: trước khi mở hộp thoại In, trang gọi POST tới $logUrl
| để lưu lại in nhãn của lô nào, bao nhiêu nhãn và lúc mấy giờ. Người dùng bấm Ctrl+P
| thay vì nút In nhãn cũng vẫn ghi (bắt qua sự kiện beforeprint). Ghi nhật ký hỏng thì
| KHÔNG in, để nhật ký không bao giờ thiếu so với số nhãn thực sự in ra.
|
| Biến truyền vào:
|   $importId    id phiếu nhập đang in nhãn
|   $logUrl      route ghi audit log in nhãn (POST)
|   $backUrl     route quay lại màn hình nhập
|   $maxCopies   số nhãn tối đa cho một lần in
|   $lblWidth    chiều rộng nhãn (mm) - dùng cho dòng hướng dẫn khổ giấy
|   $lblHeight   chiều cao nhãn (mm)
|   $printerNote (tuỳ chọn) câu nhắc thêm về máy in, ví dụ tên máy in nhãn
--}}

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

    .toolbar .go:hover {
        background: #1F5E9E;
        transform: translateY(-1px);
    }

    .toolbar .go:disabled {
        background: #9CC7EE;
        cursor: not-allowed;
        transform: none;
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

    /* ---------- Dòng báo kết quả ghi nhật ký in ---------- */
    .toolbar .log-state {
        width: 100%;
        margin: 0;
        text-align: center;
        font-size: 12.5px;
        font-weight: 600;
    }

    .toolbar .log-state.ok {
        color: #16A34A;
    }

    .toolbar .log-state.fail {
        color: #DC2626;
    }

    /* ---------- Khi in: giấu thanh công cụ, mỗi nhãn một trang ---------- */
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

    <button type="button" class="go" id="btnPrint">In nhãn</button>
    <a class="back" href="{{ $backUrl }}">Quay lại</a>

    <p class="note">
        Khổ nhãn {{ $lblWidth }}x{{ $lblHeight }}mm. {{ $printerNote ?? '' }}
        Trong hộp thoại In, đặt khổ giấy <b>{{ $lblWidth }} x {{ $lblHeight }} mm</b>, lề <b>None</b>
        và bỏ tick <b>Headers and footers</b> để nhãn ra đúng như trên màn hình.
        Mỗi lần in đều được ghi vào <b>Audit Trail</b> kèm số lượng nhãn và thời điểm in.
    </p>

    <p class="log-state" id="logState"></p>
</div>

<script>
    // Thanh công cụ nằm trước khối nhãn nên phải đợi trình duyệt dựng xong DOM,
    // chạy ngay lúc này thì chưa tìm thấy #labelStack.
    document.addEventListener('DOMContentLoaded', function () {
        var MAX = {{ (int) $maxCopies }};
        var LOG_URL = '{{ $logUrl }}';
        var IMPORT_ID = '{{ $importId }}';
        var TOKEN = '{{ csrf_token() }}';

        var stack = document.getElementById('labelStack');
        var input = document.getElementById('copies');
        var btnPrint = document.getElementById('btnPrint');
        var logState = document.getElementById('logState');

        // Bản gốc của nhãn, giữ lại để nhân ra đúng số lượng người dùng chọn
        var master = stack.firstElementChild.cloneNode(true);

        // Bấm nút In nhãn đã ghi nhật ký rồi thì beforeprint không ghi thêm lần nữa
        var alreadyLogged = false;

        /** Số nhãn hợp lệ đang chọn: số nguyên từ 1 tới MAX. */
        function copies() {
            var n = parseInt(input.value, 10);

            if (isNaN(n) || n < 1) {
                n = 1;
            }

            return n > MAX ? MAX : n;
        }

        /** Dựng lại phần xem trước cho đúng số nhãn đang chọn. */
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

        /**
         * Ghi audit log cho lần in này.
         *
         * beacon = true dùng khi in bằng Ctrl+P: beforeprint không chờ được fetch nên
         * gửi bằng sendBeacon cho kịp trước lúc hộp thoại In mở ra.
         */
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
            }).then(function (response) {
                return response.ok;
            });
        }

        input.addEventListener('input', function () {
            // Đang gõ dở (ô trống) thì để yên, gõ xong mới dựng lại
            if (input.value !== '') {
                render();
            }
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

        btnPrint.addEventListener('click', function () {
            var n = copies();

            btnPrint.disabled = true;
            say('Đang ghi nhật ký in nhãn...', true);

            logPrint(n, false).then(function (ok) {
                btnPrint.disabled = false;

                if (!ok) {
                    say('Chưa ghi được nhật ký in nhãn nên chưa in. Vui lòng thử lại.', false);

                    return;
                }

                say('Đã ghi nhật ký in ' + n + ' nhãn vào Audit Trail.', true);
                alreadyLogged = true;
                window.print();
            }).catch(function () {
                btnPrint.disabled = false;
                say('Chưa ghi được nhật ký in nhãn nên chưa in. Vui lòng thử lại.', false);
            });
        });

        // In bằng Ctrl+P / menu trình duyệt: vẫn phải có nhật ký
        window.addEventListener('beforeprint', function () {
            if (alreadyLogged) {
                return;
            }

            alreadyLogged = true;
            logPrint(copies(), true);
            say('Đã ghi nhật ký in ' + copies() + ' nhãn vào Audit Trail.', true);
        });

        // In xong, lần in kế tiếp lại phải ghi nhật ký mới
        window.addEventListener('afterprint', function () {
            alreadyLogged = false;
        });

        render();
    });
</script>
