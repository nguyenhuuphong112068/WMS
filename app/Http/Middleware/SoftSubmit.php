<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * GỬI FORM NGẦM (không tải lại trang) - phía server của layout/softSubmit.
 *
 * Các thao tác Sửa / Khoá / Duyệt / Từ chối ở Dữ Liệu Gốc và Danh Mục vẫn đi qua
 * đúng action cũ của Controller, kết thúc bằng redirect()->back()->with(...). Khi
 * form được gửi bằng JS kèm header X-WMS-Soft, middleware đổi redirect đó thành
 * JSON để trang ở lại nguyên chỗ (bộ lọc, trang đang xem, vị trí cuộn):
 *
 *   {soft: true, success, error, warning, errors: {bag: {field: [câu lỗi]}}}
 *
 * Flash của lượt này đã chuyển vào JSON (hoặc không còn ý nghĩa vì trang không tải lại)
 * thì xoá khỏi session để lần tải trang sau không hiện lại.
 *
 * Redirect sang TRANG KHÁC (hết phiên -> đăng nhập, tạo phiếu -> trang chi tiết...)
 * trả {soft: true, redirect: url} và GIỮ flash để trang đích hiện thông báo như cũ.
 */
class SoftSubmit
{
    public const HEADER = 'X-WMS-Soft';

    /** URL trang đang mở (JS gửi kèm, dự phòng khi trình duyệt không gửi Referer). */
    public const PAGE_HEADER = 'X-WMS-Page';

    /** Khoá flash mà các Controller dùng để báo kết quả. */
    private const MESSAGES = ['success', 'error', 'warning'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->headers->get(self::HEADER) !== '1') {
            return $next($request);
        }

        // redirect()->back() đọc Referer: trình duyệt chặn Referer thì lấy URL trang JS gửi kèm
        $page = (string) ($request->headers->get(self::PAGE_HEADER) ?: $request->headers->get('referer'));

        if ($page !== '' && ! $request->headers->has('referer')) {
            $request->headers->set('referer', $page);
        }

        $response = $next($request);

        if (! $response instanceof RedirectResponse) {
            return $response;
        }

        $target = $response->getTargetUrl();

        if (! $this->samePage($target, $page)) {
            return response()->json(['soft' => true, 'redirect' => $target]);
        }

        $session = $request->session();
        $payload = ['soft' => true, 'errors' => []];

        foreach (self::MESSAGES as $key) {
            $payload[$key] = $session->get($key);
        }

        $errors = $session->get('errors');

        if ($errors instanceof ViewErrorBag) {
            foreach ($errors->getBags() as $name => $bag) {
                if ($bag->isNotEmpty()) {
                    $payload['errors'][$name] = $bag->toArray();
                }
            }
        }

        // Bỏ MỌI flash của lượt này (kể cả activeTab / formTab / _old_input): trang không tải
        // lại nên không ai đọc, để lại thì lần mở trang sau sẽ tự mở lại modal, đổi tab...
        $flashed = (array) $session->get('_flash.new', []);
        $session->forget(array_unique(array_merge($flashed, self::MESSAGES, ['errors', '_old_input'])));
        $session->put('_flash.new', []);

        return response()->json($payload);
    }

    /** Redirect quay về đúng trang đang mở (so đường dẫn, bỏ qua query / #). */
    private function samePage(string $target, string $referer): bool
    {
        if ($referer === '') {
            return false;
        }

        $path = fn (string $url) => rtrim((string) parse_url($url, PHP_URL_PATH), '/');

        return $path($target) === $path($referer);
    }
}
