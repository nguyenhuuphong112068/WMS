{{--
|--------------------------------------------------------------------------
| CÀI ĐẶT TABLET QUÉT MÃ BẰNG CAMERA - chỉ Admin (route tabletSetup, mục leftNAV "Cài Đặt Tablet")
|--------------------------------------------------------------------------
| Server chạy HTTP nên Chrome khoá camera. Trang web không tự đổi được cài đặt bảo mật
| của trình duyệt -> trang này: kiểm tra trạng thái (trình duyệt / kết nối an toàn /
| camera / quyền), sao chép sẵn địa chỉ cờ Chrome + địa chỉ server cần dán, nút quét thử
| (pages.shared.cameraScan) và mã mẫu QR + Code 128, cấu hình chính sách cho MDM.
| Dữ liệu do App\Http\Controllers\General\TabletSetupController dựng sẵn.
--}}
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>Cài Đặt Tablet Quét Mã | CMS</title>

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('dataTable/plugins/fontawesome-free/css/all.min.css') }}">

    <style>
        :root {
            --primary: #2E7BC4;
            --primary-dark: #1F5E9E;
            --primary-light: #5AA0DE;
            --primary-lighter: #9CC7EE;
            --primary-soft: #EAF3FC;
            --primary-rgb: 46, 123, 196;
            --accent: #17B8D4;
            --success: #16A34A;
            --warning: #F59E0B;
            --danger: #DC2626;
            --text-main: #2D3748;
            --text-muted: #718096;
            --border-radius-lg: 12px;
            --border-radius-md: 8px;
            --shadow-sm: 0 1px 3px rgba(var(--primary-rgb), .12);
            --shadow-md: 0 6px 18px rgba(var(--primary-rgb), .16);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-main);
            background: linear-gradient(135deg, #EAF3FC 0%, #D6E8F9 45%, #C3DDF5 100%);
            padding: 28px 24px 60px;
        }

        .ts-shell { max-width: 1180px; margin: 0 auto; }

        .ts-top { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-bottom: 22px; }
        .ts-title { font-size: 1.35rem; font-weight: 700; letter-spacing: 1px; color: var(--primary); text-transform: uppercase; margin: 0; }
        .ts-title i { margin-right: 8px; }
        .ts-back {
            margin-left: auto; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
            border-radius: 999px; background: #fff; border: 1px solid #DCE7F3; color: var(--primary);
            font-weight: 600; font-size: .88rem; text-decoration: none; transition: all .2s ease;
        }
        .ts-back:hover { color: var(--primary-dark); box-shadow: var(--shadow-sm); transform: translateY(-1px); text-decoration: none; }

        .ts-card {
            background: #fff; border: 1px solid #DCE7F3; border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm); padding: 22px; margin-bottom: 20px;
        }
        .ts-card h2 { font-size: 1rem; font-weight: 700; color: var(--primary); margin: 0 0 14px; letter-spacing: .3px; }
        .ts-card h2 i { margin-right: 6px; }

        /* ---------- Trạng thái ---------- */
        .ts-status { list-style: none; margin: 0; padding: 0; }
        .ts-status li { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px dashed #E2E8F0; }
        .ts-status li:last-child { border-bottom: 0; }
        .ts-status .ic { font-size: 1.15rem; line-height: 1.2; width: 22px; text-align: center; flex: 0 0 22px; }
        .ts-status .lb { font-weight: 600; }
        .ts-status .ds { font-size: .82rem; color: var(--text-muted); }
        .st-ok .ic { color: var(--success); }
        .st-warn .ic { color: var(--warning); }
        .st-bad .ic { color: var(--danger); }
        .st-wait .ic { color: var(--primary-lighter); }

        .ts-banner { border-radius: var(--border-radius-md); padding: 12px 14px; font-weight: 600; margin-bottom: 14px; display: none; }
        .ts-banner.ok { display: block; background: rgba(22, 163, 74, .1); color: var(--success); }
        .ts-banner.bad { display: block; background: rgba(220, 38, 38, .08); color: var(--danger); }

        /* ---------- Các bước ---------- */
        .ts-step { display: flex; gap: 14px; padding: 14px 0; border-bottom: 1px solid #EDF2F7; }
        .ts-step:last-child { border-bottom: 0; padding-bottom: 0; }
        .ts-step:first-of-type { padding-top: 0; }
        .ts-num {
            flex: 0 0 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 700;
        }
        .ts-step-body { flex: 1; min-width: 0; }
        .ts-step-body .hd { font-weight: 700; margin-bottom: 4px; }
        .ts-step-body p { margin: 0 0 8px; font-size: .9rem; }
        .ts-step-body p:last-child { margin-bottom: 0; }

        .ts-copy { display: flex; gap: 8px; align-items: stretch; margin: 6px 0 8px; }
        .ts-copy code {
            flex: 1; min-width: 0; display: flex; align-items: center; padding: 9px 12px; font-size: .9rem;
            background: var(--primary-soft); color: var(--primary-dark); border-radius: var(--border-radius-md);
            word-break: break-all; user-select: all;
        }
        .ts-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 16px;
            border: 0; border-radius: var(--border-radius-md); background: var(--primary); color: #fff;
            font-weight: 600; font-size: .88rem; white-space: nowrap; cursor: pointer; transition: all .2s ease;
            text-decoration: none;
        }
        .ts-btn:hover { background: var(--primary-dark); color: #fff; transform: translateY(-1px); text-decoration: none; }
        .ts-btn.copied { background: var(--success); }
        .ts-btn-outline { background: #fff; color: var(--primary); border: 1px solid var(--primary-lighter); }
        .ts-btn-outline:hover { background: var(--primary-soft); color: var(--primary-dark); }
        .ts-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; }

        .ts-kbd { display: inline-block; padding: 1px 7px; border-radius: 5px; background: #EDF2F7; border: 1px solid #CBD5E0; font-size: .82rem; font-weight: 600; }

        /* ---------- Quét thử ---------- */
        .ts-test { display: flex; gap: 8px; }
        .ts-test input { flex: 1; min-width: 0; border: 1px solid #CBD5E0; border-radius: var(--border-radius-md); padding: 9px 12px; font-size: .95rem; transition: border-color .2s ease; }
        .ts-test input:focus { outline: 0; border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(var(--primary-rgb), .15); }
        .ts-result { margin-top: 12px; padding: 10px 12px; border-radius: var(--border-radius-md); font-size: .9rem; display: none; }
        .ts-result.ok { display: block; background: rgba(22, 163, 74, .1); color: var(--success); font-weight: 600; }
        .ts-result.info { display: block; background: var(--primary-soft); color: var(--primary-dark); }

        .ts-samples { display: flex; flex-wrap: wrap; gap: 18px; justify-content: space-around; }
        .ts-sample { text-align: center; }
        .ts-sample .qr { width: 150px; height: 150px; margin: 0 auto; }
        .ts-sample .qr svg { width: 100%; height: 100%; }
        .ts-sample .bc { height: 70px; width: 240px; margin: 40px auto 40px; }
        .ts-sample .bc svg { width: 100%; height: 100%; }
        .ts-sample .cap { font-size: .78rem; color: var(--text-muted); margin-top: 4px; }
        .ts-sample .cap b { color: var(--text-main); }

        .ts-page-qr { display: flex; gap: 16px; align-items: center; }
        .ts-page-qr .qr { flex: 0 0 120px; width: 120px; height: 120px; }
        .ts-page-qr .qr svg { width: 100%; height: 100%; }
        .ts-page-qr .tx { font-size: .88rem; }

        pre.ts-policy { background: #1E293B; color: #E2E8F0; border-radius: var(--border-radius-md); padding: 12px 14px; font-size: .82rem; margin: 8px 0; white-space: pre-wrap; word-break: break-all; }

        details.ts-more summary { cursor: pointer; font-weight: 700; color: var(--primary); list-style: none; }
        details.ts-more summary::-webkit-details-marker { display: none; }
        details.ts-more summary i { transition: transform .2s ease; margin-right: 4px; }
        details.ts-more[open] summary i { transform: rotate(90deg); }
        details.ts-more .body { margin-top: 12px; font-size: .9rem; }

        #cameraScanModal .modal-content { border-radius: var(--border-radius-lg); }
        #cameraScanModal .md-sub { display: block; margin-top: 8px; color: var(--text-muted); }

        @media (max-width: 640px) {
            body { padding: 18px 14px 40px; }
            .ts-copy, .ts-test { flex-direction: column; }
            .ts-page-qr { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>

<body>
    <div class="ts-shell">
        <div class="ts-top">
            <h1 class="ts-title"><i class="bi bi-tablet-landscape"></i>Cài Đặt Tablet Quét Mã</h1>
            <a href="{{ route('pages.home') }}" class="ts-back"><i class="bi bi-house"></i> Trang chủ</a>
        </div>

        <div class="row">
            {{-- ================= CỘT TRÁI: TRẠNG THÁI + QUÉT THỬ ================= --}}
            <div class="col-lg-5">
                <div class="ts-card">
                    <h2><i class="bi bi-clipboard-check"></i>Trạng Thái Thiết Bị Này</h2>
                    <div class="ts-banner" id="tsBanner"></div>
                    <ul class="ts-status">
                        <li id="stBrowser" class="st-wait">
                            <span class="ic"><i class="bi bi-circle"></i></span>
                            <div><div class="lb">Trình duyệt</div><div class="ds">Đang kiểm tra...</div></div>
                        </li>
                        <li id="stSecure" class="st-wait">
                            <span class="ic"><i class="bi bi-circle"></i></span>
                            <div><div class="lb">Khai báo địa chỉ "an toàn"</div><div class="ds">Đang kiểm tra...</div></div>
                        </li>
                        <li id="stCamera" class="st-wait">
                            <span class="ic"><i class="bi bi-circle"></i></span>
                            <div><div class="lb">Camera</div><div class="ds">Đang kiểm tra...</div></div>
                        </li>
                        <li id="stPerm" class="st-wait">
                            <span class="ic"><i class="bi bi-circle"></i></span>
                            <div><div class="lb">Quyền dùng camera</div><div class="ds">Đang kiểm tra...</div></div>
                        </li>
                    </ul>
                    <div class="mt-3">
                        <button type="button" class="ts-btn ts-btn-outline" id="tsRecheck"><i class="bi bi-arrow-clockwise"></i> Kiểm tra lại</button>
                    </div>
                </div>

                <div class="ts-card">
                    <h2><i class="bi bi-upc-scan"></i>Quét Thử</h2>
                    <div class="ts-test scan-box">
                        <input type="text" id="tsTestInput" placeholder="Mã quét được sẽ hiện ở đây" autocomplete="off">
                        <button type="button" class="ts-btn btn-camera-scan"><i class="fas fa-camera"></i> Quét bằng camera</button>
                    </div>
                    <div class="ts-result" id="tsTestResult"></div>
                </div>

                <div class="ts-card">
                    <h2><i class="bi bi-qr-code"></i>Mã Mẫu Để Thử</h2>
                    <div class="ts-samples">
                        <div class="ts-sample">
                            <div class="qr">{!! $testQr !!}</div>
                            <div class="cap">QR (nhãn VT / HC / VPP)<br><b>{{ $testQrText }}</b></div>
                        </div>
                        <div class="ts-sample">
                            <div class="bc">{!! $testBarcode !!}</div>
                            <div class="cap">Code 128 (nhãn chất chuẩn)<br><b>{{ $testBarcodeText }}</b></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= CỘT PHẢI: CÁC BƯỚC CÀI ĐẶT ================= --}}
            <div class="col-lg-7">
                <div class="ts-card">
                    <h2><i class="bi bi-list-ol"></i>Các Bước Cài Đặt (Chrome trên Android)</h2>

                    <div class="ts-step">
                        <div class="ts-num">1</div>
                        <div class="ts-step-body">
                            <div class="hd">Mở trang này bằng Google Chrome</div>
                            <p>Camera chỉ mở được khi dùng Chrome. Samsung Internet không có mục cài đặt này.</p>
                            <p id="tsOpenChromeWrap" style="display: none">
                                <a class="ts-btn" href="{{ $chromeIntent }}"><i class="bi bi-browser-chrome"></i> Mở trang này bằng Chrome</a>
                            </p>
                            <div class="ts-page-qr">
                                <div class="qr">{!! $pageQr !!}</div>
                                <div class="tx">Quét mã này bằng camera của tablet để mở nhanh trang cài đặt.<br><b>{{ $pageUrl }}</b></div>
                            </div>
                        </div>
                    </div>

                    <div class="ts-step">
                        <div class="ts-num">2</div>
                        <div class="ts-step-body">
                            <div class="hd">Mở trang cài đặt thử nghiệm của Chrome</div>
                            <p>Bấm <b>Sao chép</b>, chạm vào thanh địa chỉ của Chrome, dán rồi bấm <span class="ts-kbd">Enter</span>.</p>
                            <div class="ts-copy">
                                <code id="tsFlag">{{ $flag }}</code>
                                <button type="button" class="ts-btn" data-copy="#tsFlag"><i class="bi bi-clipboard"></i> Sao chép</button>
                            </div>
                        </div>
                    </div>

                    <div class="ts-step">
                        <div class="ts-num">3</div>
                        <div class="ts-step-body">
                            <div class="hd">Dán địa chỉ server WMS và bật</div>
                            <p>Ở mục <b>Insecure origins treated as secure</b> (đang tô vàng), dán địa chỉ dưới đây vào ô nhập, rồi đổi <span class="ts-kbd">Disabled</span> thành <span class="ts-kbd">Enabled</span>.</p>
                            <div class="ts-copy">
                                <code id="tsOrigins">{{ $originsText }}</code>
                                <button type="button" class="ts-btn" data-copy="#tsOrigins"><i class="bi bi-clipboard"></i> Sao chép</button>
                            </div>
                            <p>Nếu ở nơi khác mở WMS bằng địa chỉ khác (tên máy, IP khác), thêm địa chỉ đó vào cùng ô, cách nhau bằng dấu phẩy.</p>
                        </div>
                    </div>

                    <div class="ts-step">
                        <div class="ts-num">4</div>
                        <div class="ts-step-body">
                            <div class="hd">Khởi động lại Chrome</div>
                            <p>Bấm nút <span class="ts-kbd">Relaunch</span> ở cuối màn hình. Chrome tự mở lại các tab đang dùng.</p>
                        </div>
                    </div>

                    <div class="ts-step">
                        <div class="ts-num">5</div>
                        <div class="ts-step-body">
                            <div class="hd">Quay lại trang này và quét thử</div>
                            <p>Bốn dòng trạng thái chuyển xanh là xong. Bấm <b>Quét bằng camera</b>, chọn <span class="ts-kbd">Cho phép</span> khi Chrome hỏi quyền camera, rồi quét một mã mẫu.</p>
                        </div>
                    </div>
                </div>

                <div class="ts-card">
                    <details class="ts-more">
                        <summary><i class="bi bi-chevron-right"></i>Cài hàng loạt nhiều tablet (Samsung Knox / MDM)</summary>
                        <div class="body">
                            <p>Trong phần mềm quản lý thiết bị, mở cấu hình ứng dụng (Managed configuration) của <b>Google Chrome</b> và khai 2 chính sách sau. Tablet nhận cấu hình là dùng được camera ngay, không hỏi quyền, không cần làm bước 2-4.</p>
                            <pre class="ts-policy" id="tsPolicy">{{ $policy }}</pre>
                            <button type="button" class="ts-btn ts-btn-outline" data-copy="#tsPolicy"><i class="bi bi-clipboard"></i> Sao chép cấu hình</button>
                        </div>
                    </details>
                </div>

                <div class="ts-card">
                    <details class="ts-more">
                        <summary><i class="bi bi-chevron-right"></i>Máy tính Windows (Chrome / Edge)</summary>
                        <div class="body">
                            <p>Làm như các bước 2-4. Với Edge, dùng địa chỉ <code>edge://flags/#unsafely-treat-insecure-origin-as-secure</code>. Nếu cài qua Group Policy, khai cùng 2 chính sách ở trên cho Chrome hoặc Edge.</p>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('dataTable/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    @include('pages.shared.cameraScan')
    <script>
        $(function () {
            var ua = navigator.userAgent || '';
            var isAndroid = /Android/i.test(ua);
            var isSamsung = /SamsungBrowser/i.test(ua);
            var isEdge = /Edg(A|iOS)?\//.test(ua);
            var isChrome = /Chrome\//.test(ua) && !isSamsung && !isEdge && !/OPR\/|YaBrowser|CocCoc/i.test(ua);

            function setStatus(id, state, text) {
                var icons = { ok: 'bi-check-circle-fill', warn: 'bi-exclamation-triangle-fill', bad: 'bi-x-circle-fill', wait: 'bi-circle' };
                $('#' + id).attr('class', 'st-' + state)
                    .find('.ic').html('<i class="bi ' + icons[state] + '"></i>').end()
                    .find('.ds').text(text);
            }

            function check() {
                // 1. Trình duyệt
                if (isChrome) setStatus('stBrowser', 'ok', 'Google Chrome');
                else if (isEdge) setStatus('stBrowser', 'ok', 'Microsoft Edge (dùng edge://flags thay cho chrome://flags)');
                else if (isSamsung) setStatus('stBrowser', 'bad', 'Đang dùng Samsung Internet - hãy mở trang này bằng Chrome (bước 1).');
                else setStatus('stBrowser', 'warn', 'Không phải Chrome - nên dùng Google Chrome.');

                $('#tsOpenChromeWrap').toggle(isAndroid && !isChrome);

                // 2. Kết nối an toàn: đúng khi trang HTTPS / localhost / địa chỉ đã khai báo ở cờ Chrome
                var secure = window.isSecureContext === true;
                setStatus('stSecure', secure ? 'ok' : 'bad', secure
                    ? 'Địa chỉ ' + location.origin + ' đã được coi là an toàn.'
                    : 'Chưa khai báo - làm các bước 2, 3, 4.');

                // 3. Camera + 4. Quyền
                var api = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
                if (!secure) {
                    setStatus('stCamera', 'wait', 'Chờ khai báo địa chỉ an toàn.');
                    setStatus('stPerm', 'wait', 'Chờ khai báo địa chỉ an toàn.');
                    banner(false);
                    return;
                }
                if (!api) {
                    setStatus('stCamera', 'bad', 'Trình duyệt không hỗ trợ camera.');
                    setStatus('stPerm', 'wait', '-');
                    banner(false);
                    return;
                }

                var camCheck = navigator.mediaDevices.enumerateDevices().then(function (devs) {
                    var n = devs.filter(function (d) { return d.kind === 'videoinput'; }).length;
                    setStatus('stCamera', n ? 'ok' : 'bad', n ? 'Tìm thấy ' + n + ' camera.' : 'Không tìm thấy camera nào.');
                    return n > 0;
                }).catch(function () {
                    setStatus('stCamera', 'warn', 'Không đọc được danh sách camera.');
                    return true;
                });

                var permCheck = (navigator.permissions && navigator.permissions.query
                    ? navigator.permissions.query({ name: 'camera' }).then(function (p) {
                        var map = {
                            granted: ['ok', 'Đã cho phép.'],
                            prompt: ['warn', 'Chưa cấp - bấm Quét bằng camera rồi chọn Cho phép.'],
                            denied: ['bad', 'Đã bị chặn - chạm biểu tượng bên trái thanh địa chỉ > Quyền > Camera > Cho phép.']
                        };
                        var s = map[p.state] || ['warn', p.state];
                        setStatus('stPerm', s[0], s[1]);
                        p.onchange = check;
                        return p.state !== 'denied';
                    })
                    : Promise.reject()
                ).catch(function () {
                    setStatus('stPerm', 'warn', 'Sẽ hỏi khi bấm Quét bằng camera.');
                    return true;
                });

                Promise.all([camCheck, permCheck]).then(function (r) { banner(r[0] && r[1]); });
            }

            function banner(ok) {
                $('#tsBanner').attr('class', 'ts-banner ' + (ok ? 'ok' : 'bad')).html(ok
                    ? '<i class="bi bi-check2-circle mr-1"></i> Thiết bị đã sẵn sàng quét mã bằng camera.'
                    : '<i class="bi bi-exclamation-octagon mr-1"></i> Thiết bị chưa quét được bằng camera - làm theo các bước bên phải.');
            }

            check();
            $('#tsRecheck').on('click', check);

            // Sao chép: trang HTTP không có navigator.clipboard -> dùng cách chọn chữ + execCommand
            function copyText(text) {
                if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
                return new Promise(function (resolve, reject) {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    ta.setSelectionRange(0, text.length);
                    var ok = false;
                    try { ok = document.execCommand('copy'); } catch (e) {}
                    document.body.removeChild(ta);
                    ok ? resolve() : reject();
                });
            }

            $('[data-copy]').on('click', function () {
                var $btn = $(this);
                var html = $btn.data('html') || $btn.html();
                $btn.data('html', html);
                var text = $($btn.data('copy')).text().trim();
                copyText(text).then(function () {
                    $btn.addClass('copied').html('<i class="bi bi-check2"></i> Đã sao chép');
                }).catch(function () {
                    $btn.html('<i class="bi bi-hand-index"></i> Giữ tay lên chữ để sao chép');
                });
                setTimeout(function () { $btn.removeClass('copied').html(html); }, 2500);
            });

            // Quét thử: cameraScan điền mã rồi bắn phím Enter
            var samples = @json([$testQrText, $testBarcodeText]);
            $('#tsTestInput').on('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                var v = $(this).val().trim();
                if (!v) return;
                var known = samples.indexOf(v) !== -1;
                $('#tsTestResult').attr('class', 'ts-result ' + (known ? 'ok' : 'info')).html(known
                    ? '<i class="bi bi-check-circle-fill mr-1"></i> Quét thành công mã mẫu: ' + $('<span>').text(v).html()
                    : '<i class="bi bi-info-circle mr-1"></i> Đã đọc được mã: ' + $('<span>').text(v).html());
                check();
            });
        });
    </script>
</body>

</html>
