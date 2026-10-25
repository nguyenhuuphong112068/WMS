<?php

namespace App\Support;

use App\Http\Controllers\Pages\UserGuide\UserGuideController;

/**
 * Liên kết từ màn hình nghiệp vụ tới đúng mục Hướng Dẫn Sử Dụng (nút "?" trên topNAV).
 *
 * Nguồn duy nhất là mục lục của từng tài liệu: resources/views/pages/userGuide/<khoá>/toc.php.
 * Mỗi mục có thể khai:
 *  - route      : màn hình của mục (cũng là nút "Mở màn hình" trong hướng dẫn);
 *  - pane       : id vùng nội dung của tab trên màn hình đó (chuỗi hoặc mảng), để nút "?" chọn đúng
 *                 mục theo tab đang mở;
 *  - helpRoutes : các route khác (trang con, trang chi tiết) cũng dẫn về mục này, được ưu tiên trước.
 * Màn hình nào không khớp mục nào thì thử route "<cùng nhóm>.list", cuối cùng về trang danh sách hướng dẫn.
 */
class UserGuideLinks
{
    private static array $groups = [];

    private static ?array $map = null;

    /** Mục lục của một tài liệu: ['Tên nhóm' => [mục, ...]] */
    public static function groups(string $guide): array
    {
        return self::$groups[$guide] ??= require resource_path("views/pages/userGuide/{$guide}/toc.php");
    }

    /**
     * Các mục hướng dẫn ứng với một route, theo thứ tự ưu tiên:
     * [['url', 'title', 'panes' => [...], 'tab' => ?string], ...]. Rỗng khi không có mục nào.
     */
    public static function forRoute(?string $routeName): array
    {
        if (!$routeName) {
            return [];
        }

        $map = self::map();
        if (isset($map[$routeName])) {
            return $map[$routeName];
        }

        // Trang con (lịch sử, chi tiết...) dùng mục của màn hình chính cùng nhóm
        $sibling = preg_replace('/\.[^.]+$/', '.list', $routeName);

        return $map[$sibling] ?? [];
    }

    /** route => danh sách mục, dựng một lần mỗi request */
    private static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $primary = [];
        $byRoute = [];

        foreach (UserGuideController::GUIDES as $guide => $meta) {
            foreach (self::groups($guide) as $items) {
                foreach ($items as $item) {
                    $link = [
                        'url' => route('pages.userGuide.show', $guide) . '#' . $item['id'],
                        'title' => $meta['title'] . ' › ' . $item['title'],
                        'panes' => (array) ($item['pane'] ?? []),
                        'tab' => $item['params']['tab'] ?? null,
                    ];

                    foreach ($item['helpRoutes'] ?? [] as $route) {
                        $primary[$route][] = $link;
                    }
                    if (!empty($item['route'])) {
                        $byRoute[$item['route']][] = $link;
                    }
                }
            }
        }

        self::$map = $byRoute;
        foreach ($primary as $route => $links) {
            self::$map[$route] = array_merge($links, $byRoute[$route] ?? []);
        }

        return self::$map;
    }
}
