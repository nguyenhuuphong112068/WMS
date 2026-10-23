<?php

namespace App\Http\Middleware;

use App\Support\DeferredRows;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lượt gọi ?_deferred=1 của trang danh sách: trang đã render xong (các dòng nằm trong
 * bộ đệm của App\Support\DeferredRows), đổi phản hồi HTML thành JSON chỉ gồm các dòng.
 *
 * Phản hồi không phải trang HTML thành công (redirect về đăng nhập, file tải về, JSON
 * sẵn có...) thì giữ nguyên.
 */
class ServeDeferredRows
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || ! DeferredRows::requested() || ! $response->isSuccessful()) {
            return $response;
        }

        $type = (string) $response->headers->get('Content-Type', 'text/html');

        if (! str_contains($type, 'text/html')) {
            return $response;
        }

        return response()->json(['tables' => DeferredRows::payload()]);
    }
}
