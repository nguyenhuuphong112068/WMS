<?php
/*
 * Chèn ảnh đã chụp vào section của một tài liệu hướng dẫn (chỉ chèn ảnh chưa được chèn).
 *
 *   php insert.php <khoá tài liệu> [--dry]
 *
 *  - <id>.png      : ngay sau đoạn <p> mở đầu của section (không có thì đầu file);
 *  - <id>-form.png : ngay trên bảng "Các ô nhập" đầu tiên (@include shared.fields), kèm tiêu đề nếu có.
 * Ảnh khác (đặt tên tuỳ ý qua 'shots' trong toc.php) thì chèn tay vào đúng bước.
 */
require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$guide = $argv[1] ?? exit("Thiếu khoá tài liệu\n");
$dry = in_array('--dry', $argv, true);
$dir = resource_path("views/pages/userGuide/$guide/sections");

$shot = fn (string $file, string $caption) => "@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/$guide/$file', 'caption' => '"
    .str_replace("'", "\\'", $caption)."'])";

foreach (App\Support\UserGuideLinks::groups($guide) as $items) {
    foreach ($items as $item) {
        $path = "$dir/{$item['file']}.blade.php";
        if (! is_file($path)) {
            continue;
        }
        $source = file_get_contents($path);
        $before = $source;
        $label = $item['path'] ?? $item['title'];

        $page = "{$item['id']}.png";
        if (is_file(public_path("img/userGuide/$guide/$page")) && ! str_contains($source, "/$page'")) {
            $line = $shot($page, 'Màn hình '.$label);
            $end = strpos($source, '</p>');
            $source = $end !== false && $end < 1500
                ? substr_replace($source, "</p>\n$line", $end, strlen('</p>'))
                : "$line\n\n".$source;
        }

        $form = "{$item['id']}-form.png";
        $at = strpos($source, "@include('pages.userGuide.shared.fields'");
        if (is_file(public_path("img/userGuide/$guide/$form")) && ! str_contains($source, "/$form'") && $at !== false) {
            $source = substr_replace($source, $shot($form, 'Hộp thoại thêm mới - '.$item['title'])."\n\n", $at, 0);
        }

        if ($source !== $before) {
            echo ($dry ? '[dry] ' : '')."{$item['id']} -> sections/{$item['file']}.blade.php\n";
            if (! $dry) {
                file_put_contents($path, $source);
            }
        }
    }
}
