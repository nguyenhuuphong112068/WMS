<?php

namespace App\Console\Commands;

use App\Http\Controllers\Pages\UserGuide\UserGuideController;
use App\Support\UserGuideLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * KIỂM TRA HƯỚNG DẪN SỬ DỤNG CÒN KHỚP VỚI MÃ NGUỒN
 *
 * Chạy sau mỗi lần sửa chức năng (quy tắc 11 của skill wms-rules):
 *
 *   php artisan wms:user-guide-check
 *
 * Lỗi (thoát mã 1):
 *  - mục trong toc.php trỏ tới file section, route, helpRoutes, quyền không còn tồn tại;
 *  - 'pane' (id vùng nội dung của tab) không còn trong view nào;
 *  - liên kết #neo trong section trỏ tới mục không có (cùng tài liệu hoặc tài liệu khác);
 *  - trùng id mục trong một tài liệu.
 *  - ảnh chụp màn hình (shared/shot) không còn file.
 * Cảnh báo: màn hình có trên leftNAV nhưng nút "?" chưa dẫn tới mục hướng dẫn nào; mục có màn hình
 * nhưng chưa có ảnh chụp; controller / view của màn hình sửa sau lần chụp ảnh (phải chụp lại).
 */
class UserGuideCheckCommand extends Command
{
    protected $signature = 'wms:user-guide-check';

    protected $description = 'Kiểm tra mục lục, liên kết, tab và quyền của Hướng Dẫn Sử Dụng còn khớp với route / view / quyền hiện tại';

    private array $errors = [];

    private array $shotWarnings = [];

    public function handle(): int
    {
        $guides = array_keys(UserGuideController::GUIDES);
        $permissions = DB::table('permissions')->pluck('name')->flip()->all();
        $viewSource = $this->businessViewSource();

        // id các mục + id phần tử trong section, theo từng tài liệu (để kiểm #neo)
        $anchors = [];
        foreach ($guides as $guide) {
            $anchors[$guide] = [];
            foreach (UserGuideLinks::groups($guide) as $items) {
                foreach ($items as $item) {
                    if (isset($anchors[$guide][$item['id']])) {
                        $this->addError("$guide: trùng id mục '{$item['id']}'");
                    }
                    $anchors[$guide][$item['id']] = true;
                }
            }
            foreach (File::glob($this->dir($guide) . '/sections/*.blade.php') as $file) {
                preg_match_all('/\bid="([^"{]+)"/', File::get($file), $m);
                $anchors[$guide] += array_fill_keys($m[1], true);
            }
        }

        $items = 0;
        foreach ($guides as $guide) {
            foreach (['index.blade.php', 'toc.php'] as $file) {
                if (!File::exists($this->dir($guide) . '/' . $file)) {
                    $this->addError("$guide: thiếu $file");
                }
            }

            foreach (UserGuideLinks::groups($guide) as $groupItems) {
                foreach ($groupItems as $item) {
                    $items++;
                    $where = "$guide#{$item['id']}";
                    $section = $this->dir($guide) . "/sections/{$item['file']}.blade.php";

                    if (!File::exists($section)) {
                        $this->addError("$where: thiếu file sections/{$item['file']}.blade.php");
                    }
                    foreach (array_merge((array) ($item['route'] ?? []), $item['helpRoutes'] ?? []) as $route) {
                        if (!Route::has($route)) {
                            $this->addError("$where: route '$route' không còn tồn tại");
                        }
                    }
                    if (!empty($item['perm']) && $item['perm'] !== 'Admin' && !isset($permissions[$item['perm']])) {
                        $this->addError("$where: quyền '{$item['perm']}' không có trong bảng permissions");
                    }
                    foreach ((array) ($item['pane'] ?? []) as $pane) {
                        if (!preg_match('/id="' . preg_quote($pane, '/') . '"|\'pane\' => \'' . preg_quote($pane, '/') . '\'/', $viewSource)) {
                            $this->addError("$where: không còn tab có id '$pane' trên màn hình");
                        }
                    }
                    if (File::exists($section)) {
                        $this->checkAnchors($where, $guide, File::get($section), $anchors);
                        $this->checkShots($where, $guide, $item, File::get($section));
                    }
                }
            }
        }

        // Màn hình trên menu trái chưa có mục hướng dẫn: chỉ cảnh báo
        preg_match_all("/route\('(pages\.[A-Za-z.]+)'\)/", File::get(resource_path('views/layout/leftNAV.blade.php')), $m);
        $uncovered = array_filter(
            array_unique($m[1]),
            fn ($route) => !str_starts_with($route, 'pages.userGuide.') && !UserGuideLinks::forRoute($route)
        );

        foreach ($this->errors as $error) {
            $this->error('  ✗ ' . $error);
        }
        foreach ($uncovered as $route) {
            $this->warn("  ! Màn hình '$route' trên menu chưa có mục hướng dẫn (nút \"?\" mở danh sách hướng dẫn)");
        }
        foreach ($this->shotWarnings as $warning) {
            $this->warn('  ! ' . $warning);
        }

        if ($this->errors) {
            $this->error(count($this->errors) . ' lỗi trên ' . count($guides) . " tài liệu / $items mục.");

            return self::FAILURE;
        }

        $this->info('Hướng dẫn khớp với mã nguồn: ' . count($guides) . " tài liệu, $items mục, " . (count($uncovered) + count($this->shotWarnings)) . ' cảnh báo.');

        return self::SUCCESS;
    }

    /**
     * Ảnh chụp màn hình (shared/shot): ảnh phải tồn tại; mục có màn hình mà chưa có ảnh, hoặc view /
     * controller của màn hình được sửa SAU lần chụp ảnh thì cảnh báo để chụp lại
     * (.claude/skills/wms-user-guide/scripts: php plan.php <tài liệu> <id> rồi node shoot.mjs).
     */
    private function checkShots(string $where, string $guide, array $item, string $source): void
    {
        preg_match_all("/shared\.shot',\s*\['src'\s*=>\s*'([^']+)'/", $source, $m);
        $images = [];
        foreach ($m[1] as $src) {
            if (!File::exists(public_path($src))) {
                $this->addError("$where: thiếu ảnh chụp public/$src");
            } else {
                $images[] = public_path($src);
            }
        }

        if (empty($item['route']) || ($item['shots'] ?? null) === false || in_array($guide, ['permission'], true)) {
            return;
        }
        if (!$images) {
            $this->shotWarnings[] = "$where: màn hình chưa có ảnh chụp minh hoạ";

            return;
        }

        $shotAt = min(array_map('filemtime', $images));
        $changed = array_filter($this->screenSources($item['route']), fn ($file) => filemtime($file) > $shotAt);
        if ($changed) {
            $this->shotWarnings[] = "$where: giao diện đã sửa sau khi chụp ảnh (" . implode(', ', array_map(
                fn ($file) => str_replace([base_path() . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $file),
                array_slice(array_values($changed), 0, 2)
            )) . ') - chụp lại ảnh';
        }
    }

    /** Controller + các view mà action của route trả về (view('pages.x.y.list') -> thư mục pages/x/y). */
    private function screenSources(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);
        $controller = $route ? ltrim(explode('@', (string) $route->getActionName())[0], '\\') : null;
        if (!$controller || !class_exists($controller)) {
            return [];
        }

        $file = (new \ReflectionClass($controller))->getFileName();
        $files = [$file];
        preg_match_all("/view\(\s*'([\w.]+)'/", File::get($file), $m);
        foreach (array_unique($m[1]) as $view) {
            $dir = resource_path('views/' . str_replace('.', '/', preg_replace('/\.[^.]+$/', '', $view)));
            if (str_starts_with($view, 'pages.') && File::isDirectory($dir)) {
                $files = array_merge($files, File::glob($dir . '/*.blade.php'));
            }
        }

        return array_unique($files);
    }

    /** href="#neo" (cùng tài liệu) và route('pages.userGuide.show', 'khoá') }}#neo (tài liệu khác) */
    private function checkAnchors(string $where, string $guide, string $source, array $anchors): void
    {
        preg_match_all('/href="#([^"{]+)"/', $source, $local);
        foreach ($local[1] as $anchor) {
            if (!isset($anchors[$guide][$anchor])) {
                $this->addError("$where: liên kết #$anchor không trỏ tới mục nào");
            }
        }

        preg_match_all("/route\('pages\.userGuide\.show',\s*'([^']+)'\)\s*\}\}#([\w-]+)/", $source, $cross, PREG_SET_ORDER);
        foreach ($cross as [, $target, $anchor]) {
            if (!isset($anchors[$target][$anchor])) {
                $this->addError("$where: liên kết sang $target#$anchor không trỏ tới mục nào");
            }
        }
    }

    /** Toàn bộ view nghiệp vụ (trừ hướng dẫn) gộp lại để tra id tab */
    private function businessViewSource(): string
    {
        $source = '';
        foreach (File::allFiles(resource_path('views/pages')) as $file) {
            if (!str_contains($file->getPathname(), 'userGuide')) {
                $source .= $file->getContents();
            }
        }

        return $source;
    }

    private function dir(string $guide): string
    {
        return resource_path("views/pages/userGuide/{$guide}");
    }

    private function addError(string $message): void
    {
        $this->errors[] = $message;
    }
}
