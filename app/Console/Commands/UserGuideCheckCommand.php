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
 * Cảnh báo: màn hình có trên leftNAV nhưng nút "?" chưa dẫn tới mục hướng dẫn nào.
 */
class UserGuideCheckCommand extends Command
{
    protected $signature = 'wms:user-guide-check';

    protected $description = 'Kiểm tra mục lục, liên kết, tab và quyền của Hướng Dẫn Sử Dụng còn khớp với route / view / quyền hiện tại';

    private array $errors = [];

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

        if ($this->errors) {
            $this->error(count($this->errors) . ' lỗi trên ' . count($guides) . " tài liệu / $items mục.");

            return self::FAILURE;
        }

        $this->info('Hướng dẫn khớp với mã nguồn: ' . count($guides) . " tài liệu, $items mục, " . count($uncovered) . ' cảnh báo.');

        return self::SUCCESS;
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
