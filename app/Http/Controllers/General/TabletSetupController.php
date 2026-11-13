<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Support\Barcode128;
use App\Support\QrCode;
use Illuminate\Http\Request;

class TabletSetupController extends Controller
{
    /** Cờ Chrome cho phép một địa chỉ HTTP được coi là "an toàn" (mở được camera). */
    public const CHROME_FLAG = 'chrome://flags/#unsafely-treat-insecure-origin-as-secure';

    /** Mã mẫu in trên trang để quét thử camera. */
    public const TEST_QR = 'WMS-TEST-QR-0001';

    public const TEST_CODE128 = 'WMS-TEST-128-0001';

    /**
     * CÀI ĐẶT TABLET QUÉT MÃ BẰNG CAMERA - chỉ Admin (middleware role:Admin, mục leftNAV "Cài Đặt Tablet")
     *
     * Server WMS chạy HTTP trong mạng LAN, mà trình duyệt chỉ mở camera trên trang
     * HTTPS / localhost. Trang web KHÔNG tự đổi được cài đặt bảo mật của Chrome, nên
     * trang này làm trợ lý cài đặt: tự kiểm tra trạng thái, sao chép sẵn đúng chuỗi
     * cần dán vào cờ Chrome "Insecure origins treated as secure", có nút quét thử và
     * mã mẫu để thử. Kèm cấu hình chính sách Chrome để cài hàng loạt qua MDM (Knox).
     */
    public function index(Request $request)
    {
        $origins = $this->origins($request);
        $pageUrl = $request->url();

        return view('public.tabletSetup', [
            'origins' => $origins,
            'originsText' => implode(',', $origins),
            'flag' => self::CHROME_FLAG,
            'pageUrl' => $pageUrl,
            // Mở thẳng trang này bằng Chrome khi đang ở trình duyệt khác (Samsung Internet...)
            'chromeIntent' => 'intent://'.preg_replace('#^https?://#', '', $pageUrl)
                .'#Intent;scheme='.$request->getScheme().';package=com.android.chrome;end',
            'pageQr' => QrCode::svg($pageUrl, 'M', 2),
            'testQr' => QrCode::svg(self::TEST_QR, 'M', 2),
            'testQrText' => self::TEST_QR,
            'testBarcode' => Barcode128::svg(self::TEST_CODE128),
            'testBarcodeText' => self::TEST_CODE128,
            'policy' => json_encode([
                'OverrideSecurityRestrictionsOnInsecureOrigin' => $origins,
                'VideoCaptureAllowedUrls' => $origins,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }

    /**
     * Các địa chỉ HTTP cần khai báo: địa chỉ đang mở trang này + APP_URL (nếu khác và
     * không phải localhost - localhost vốn đã được coi là an toàn).
     *
     * @return array<int, string>
     */
    private function origins(Request $request): array
    {
        $list = [$request->getSchemeAndHttpHost()];

        $appUrl = (string) config('app.url');
        $parts = parse_url($appUrl);
        if (! empty($parts['host']) && ($parts['scheme'] ?? '') === 'http'
            && ! in_array($parts['host'], ['localhost', '127.0.0.1'], true)) {
            $list[] = 'http://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        }

        return array_values(array_unique(array_filter($list, fn ($o) => str_starts_with($o, 'http://'))))
            ?: [$request->getSchemeAndHttpHost()];
    }
}
