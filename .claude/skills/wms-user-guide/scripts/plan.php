<?php
/*
 * Dựng kế hoạch chụp màn hình cho một tài liệu hướng dẫn từ toc.php.
 *
 *   php plan.php <khoá tài liệu> [id1,id2...]  > plan.json
 *
 * Mặc định mỗi mục có 'route' được chụp:
 *   - <id>.png      : màn hình (mở đúng tab theo 'pane' / 'params'), phần đầu vùng nội dung;
 *   - <id>-form.png : hộp thoại "Thêm..." của tab đó, chỉ khi section có bảng "Các ô nhập".
 * Mục trong toc.php có thể khai 'shots' => false (không chụp) hoặc 'shots' => [[...], ...] để thay
 * kế hoạch mặc định (khoá: name, url, js, clip, highlight, maxHeight, width, user, dept, flash);
 * 'shotDept' => <id phòng ban> để chụp ở phòng có dữ liệu cho màn hình đó;
 * 'extraShots' => [[...], ...] thêm ảnh từng bước (cùng khoá như 'shots'), giữ nguyên ảnh mặc định.
 */
require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** Phòng ban đang chọn khi chụp - phòng có nhiều dữ liệu nhất của từng module. */
const GUIDE_DEPARTMENT = ['materData' => 20, 'material' => 4, 'chemical' => 20, 'standard' => 20, 'general' => 20, 'permission' => 20];

$guide = $argv[1] ?? exit("Thiếu khoá tài liệu\n");
$only = isset($argv[2]) ? explode(',', $argv[2]) : null;
$dir = resource_path("views/pages/userGuide/$guide/sections");
$plan = [];

foreach (App\Support\UserGuideLinks::groups($guide) as $items) {
    foreach ($items as $item) {
        if (empty($item['route']) || ($item['shots'] ?? null) === false || ($only && ! in_array($item['id'], $only, true))) {
            continue;
        }
        $base = [
            'url' => parse_url(route($item['route'], $item['params'] ?? []), PHP_URL_PATH).(($q = parse_url(route($item['route'], $item['params'] ?? []), PHP_URL_QUERY)) ? "?$q" : ''),
            'user' => 'Admin',
            'dept' => $item['shotDept'] ?? GUIDE_DEPARTMENT[$guide] ?? null,
            'width' => 1600,
            'tab' => array_values((array) ($item['pane'] ?? [])),
        ];
        $shots = $item['shots'] ?? null;
        if ($shots === null) {
            $shots = [['name' => $item['id'], 'clip' => '.content-wrapper', 'maxHeight' => 640, 'pad' => 0]];
            if (str_contains((string) @file_get_contents("$dir/{$item['file']}.blade.php"), 'shared.fields')) {
                $shots[] = ['name' => $item['id'].'-form', 'js' => '__ug.clickCreate()', 'clip' => '.modal.show .modal-content', 'maxHeight' => 1400, 'pad' => 0];
            }
        }
        // 'extraShots': ảnh từng bước (hộp thoại mở từ nút trên dòng...) cộng thêm vào kế hoạch mặc định
        $shots = array_merge($shots, $item['extraShots'] ?? []);
        foreach ($shots as $shot) {
            $shot['name'] = $shot['name'] ?? $item['id'];
            $plan[] = $shot + $base;
        }
    }
}

echo json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
