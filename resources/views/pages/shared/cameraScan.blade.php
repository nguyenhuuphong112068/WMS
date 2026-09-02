{{--
|--------------------------------------------------------------------------
| QUÉT MÃ VẠCH BẰNG CAMERA - dùng chung cho mọi ô quét mã vạch
|--------------------------------------------------------------------------
| Có 2 cách quét mã trên các màn hình Nhập / Sử Dụng / Tồn Kho:
| - Máy đọc mã rời: gõ mã ra như bàn phím rồi tự bấm Enter, ô quét nào cũng đã bắt
|   sẵn phím Enter nên không cần thêm gì (xem barcodeSearch.blade.php, export/shared/assets.blade.php).
| - Camera máy tính / tablet / điện thoại: dùng thư viện html5-qrcode (đã có sẵn ở
|   public/js) đọc trực tiếp qua webcam. Đọc được CẢ HAI kiểu nhãn đang in:
|   mã QR trên nhãn lô vật tư (QrCode::svg()) và mã vạch Code 128 trên nhãn lô
|   hoá chất / chất chuẩn (Barcode128::svg()).
|
| Cách gắn nút quét camera vào một ô quét mã:
|   1. Khối bọc ô input phải có thêm class "scan-box" và chỉ chứa ĐÚNG MỘT
|      <input type="text"> cần điền mã.
|   2. Thêm nút: <button type="button" class="btn ... btn-camera-scan"><i class="fas fa-camera"></i></button>
|
| Quét được là tự điền mã vào ô rồi bắn phím Enter thật lên ô đó - dùng lại đúng phím
| tắt mà ô quét đã bắt sẵn, module này không cần biết bước tiếp theo xử lý mã ra sao.
|
| Camera chỉ chạy được khi trang mở qua HTTPS hoặc mở từ chính máy (localhost) - đây
| là giới hạn bảo mật của trình duyệt, không phải lỗi của hệ thống. Nơi nào không đáp
| ứng được thì nút quét camera tự khoá lại, vẫn dùng máy đọc mã rời hoặc gõ tay mã.
--}}

@once
    <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>

    <div class="modal fade md-modal" id="cameraScanModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-camera"></i> Quét Mã Vạch Bằng Camera</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">
                    <div class="cam-frame">
                        <div id="cameraScanView"></div>
                        <div class="cam-overlay cam-status">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Đang mở camera...
                        </div>
                        <div class="cam-overlay cam-error" style="display: none"></div>
                    </div>
                    <small class="md-sub">
                        Đưa mã vạch trên nhãn vào giữa khung hình, cách camera khoảng 10-15cm và giữ yên tới khi
                        quét được. Không quét được thì dùng máy đọc mã rời hoặc gõ tay mã.
                    </small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary btn-cam-switch">
                        <i class="fas fa-sync-alt mr-1"></i> Đổi camera
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-camera-scan {
            white-space: nowrap;
        }

        .btn-camera-scan:disabled {
            cursor: not-allowed;
        }

        .cam-frame {
            position: relative;
            min-height: 260px;
            border-radius: var(--border-radius-md);
            overflow: hidden;
            background: #000;
        }

        .cam-frame #cameraScanView {
            width: 100%;
        }

        .cam-frame video {
            width: 100% !important;
            display: block;
        }

        .cam-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            text-align: center;
            font-size: 0.9rem;
            color: #fff;
            background: rgba(15, 23, 42, 0.6);
        }

        .cam-error {
            background: rgba(127, 29, 29, 0.88);
            font-weight: 600;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            var camEngine = null; // Một phiên html5-qrcode dùng chung cho mọi ô quét trên trang
            var camDevices = []; // Danh sách camera của thiết bị, chỉ lấy khi bấm "Đổi camera"
            var camDeviceIndex = 0;
            var $camTarget = null; // Ô <input> đang cần điền mã của lần quét hiện tại

            /** Camera chỉ chạy được trên HTTPS hoặc localhost - giới hạn bảo mật của trình duyệt. */
            function camSecure() {
                return location.protocol === 'https:' ||
                    ['localhost', '127.0.0.1'].indexOf(location.hostname) !== -1;
            }

            function camSupported() {
                return !!(window.Html5Qrcode && navigator.mediaDevices && navigator.mediaDevices.getUserMedia) &&
                    camSecure();
            }

            // Trình duyệt hoặc kết nối không hỗ trợ thì khoá nút lại luôn, khỏi bấm vào rồi mới báo lỗi
            if (!camSupported()) {
                $('.btn-camera-scan').prop('disabled', true).attr('title',
                    camSecure() ?
                    'Trình duyệt này không hỗ trợ quét bằng camera. Hãy dùng máy đọc mã rời hoặc gõ tay mã.' :
                    'Quét bằng camera cần trang chạy qua HTTPS hoặc mở từ chính máy này. Hãy dùng máy đọc mã rời hoặc gõ tay mã.'
                );
            }

            function camShowStatus(html) {
                $('#cameraScanModal .cam-error').hide();
                $('#cameraScanModal .cam-status').html(html).show();
            }

            function camShowError(message) {
                $('#cameraScanModal .cam-status').hide();
                $('#cameraScanModal .cam-error').text(message).show();
            }

            /** Vì sao không mở được camera, viết thành câu người dùng hiểu được. */
            function camErrorMessage(err) {
                var text = String((err && err.message) || err || '');

                if (/NotAllowedError|Permission denied/i.test(text)) {
                    return 'Trình duyệt đã chặn quyền camera. Vào phần cài đặt của trình duyệt, cấp quyền camera cho trang này rồi thử lại.';
                }

                if (/NotFoundError|no camera/i.test(text)) {
                    return 'Không tìm thấy camera nào trên thiết bị này.';
                }

                if (/NotReadableError/i.test(text)) {
                    return 'Camera đang được ứng dụng khác sử dụng. Hãy đóng ứng dụng đó rồi thử lại.';
                }

                return 'Không mở được camera. Hãy dùng máy đọc mã rời hoặc gõ tay mã.';
            }

            /** Dừng hẳn phiên quét đang chạy, để đèn camera tắt hẳn khi đóng modal / đổi camera. */
            function camStop() {
                if (!camEngine || !camEngine.isScanning) return Promise.resolve();

                return camEngine.stop().catch(function() {});
            }

            function camOnDecoded(text) {
                camStop();

                $('#cameraScanModal').modal('hide');

                if (!$camTarget || !$camTarget.length) return;

                // Điền mã rồi bắn phím Enter thật - ô quét nào cũng đã bắt sẵn phím này,
                // nên module này không cần biết bước tiếp theo xử lý mã vạch ra sao.
                $camTarget.val(text).trigger($.Event('keydown', {
                    key: 'Enter'
                }));
            }

            /** Bắt đầu quét bằng một camera cụ thể (deviceId) hoặc để trình duyệt tự chọn (facingMode). */
            function camStart(cameraIdOrConfig) {
                camShowStatus('<i class="fas fa-spinner fa-spin mr-1"></i> Đang mở camera...');

                // Đọc cả hai kiểu nhãn đang in: nhãn lô hoá chất / chất chuẩn là mã vạch
                // Code 128 (Barcode128::svg()), nhãn lô vật tư là mã QR (QrCode::svg()).
                camEngine = camEngine || new Html5Qrcode('cameraScanView', {
                    formatsToSupport: [
                        Html5QrcodeSupportedFormats.QR_CODE,
                        Html5QrcodeSupportedFormats.CODE_128
                    ],
                    verbose: false
                });

                camEngine.start(
                    cameraIdOrConfig, {
                        fps: 10,
                        qrbox: {
                            width: 280,
                            height: 140
                        }
                    },
                    function(text) {
                        camOnDecoded(text);
                    },
                    function() {
                        // Chưa quét được ở khung hình này - hàm này bị gọi liên tục, không cần xử lý gì
                    }
                ).then(function() {
                    $('#cameraScanModal .cam-status, #cameraScanModal .cam-error').hide();
                }).catch(function(err) {
                    camShowError(camErrorMessage(err));
                });
            }

            /** Mở modal và bắt đầu quét bằng camera sau (environment) - hợp cả điện thoại lẫn máy tính. */
            $(document).on('click', '.btn-camera-scan', function() {
                if ($(this).is(':disabled')) return;

                $camTarget = $(this).closest('.scan-box').find('input[type="text"]').first();

                if (!$camTarget.length) return;

                camDevices = [];
                camDeviceIndex = 0;

                $('#cameraScanModal').modal('show');
                camStart({
                    facingMode: 'environment'
                });
            });

            /**
             * Đổi sang camera khác. Chỉ liệt kê danh sách camera ở lần bấm đầu tiên - hỏi quyền
             * camera càng ít lần càng đỡ làm phiền, phiên start() lúc mở modal đã đủ để trình
             * duyệt cấp quyền cho những lần liệt kê sau.
             */
            $(document).on('click', '.btn-cam-switch', function() {
                camStop().then(function() {
                    if (camDevices.length > 1) {
                        camDeviceIndex = (camDeviceIndex + 1) % camDevices.length;
                        camStart(camDevices[camDeviceIndex].id);

                        return;
                    }

                    Html5Qrcode.getCameras().then(function(devices) {
                        camDevices = devices || [];

                        if (camDevices.length < 2) {
                            camStart({
                                facingMode: 'environment'
                            });

                            return;
                        }

                        camDeviceIndex = 1;
                        camStart(camDevices[camDeviceIndex].id);
                    }).catch(function(err) {
                        camShowError(camErrorMessage(err));
                    });
                });
            });

            $('#cameraScanModal').on('show.bs.modal', function() {
                var $otherModals = $('.modal.show').not('#cameraScanModal');
                if ($otherModals.length) {
                    $('#cameraScanModal').css('z-index', 1060);
                    setTimeout(function() {
                        $('.modal-backdrop').last().css('z-index', 1055);
                    }, 0);
                }
            });

            // Đóng modal bằng bất kỳ cách nào (Huỷ, X, bấm ra ngoài, quét xong) đều phải tắt hẳn camera
            $('#cameraScanModal').on('hidden.bs.modal', function() {
                camStop();
                $('#cameraScanModal').css('z-index', '');
                var $otherModals = $('.modal.show').not('#cameraScanModal');
                if ($otherModals.length) {
                    $('body').addClass('modal-open');
                    $('.modal-backdrop').css('z-index', 1040);
                }
            });
        });
    </script>
@endonce
