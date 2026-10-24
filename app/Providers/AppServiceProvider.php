<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        | Directive kiểm tra quyền dùng trong Blade, bọc quanh các nút thao tác:
        |
        |   @perm('materData_common_create')  ... @endperm   -> có quyền này
        |   @permAny(['a', 'b'])       ... @endpermAny   -> có ít nhất một trong các quyền
        |
        | Muốn làm ngược lại (hiện khi KHÔNG có quyền) thì dùng @unlessperm / @endunlessperm.
        */
        Blade::if('perm', fn(string $name) => user_can($name));
        Blade::if('permAny', fn(array $names) => user_can_any($names));
        // Quyền xem từng tab của trang nhiều tab - App\Support\PageTabs
        Blade::if('tab', fn(string $page, string $tab) => \App\Support\PageTabs::can($page, $tab));

        /*
        | Dòng bảng nạp sau bằng AJAX - App\Support\DeferredRows. Bọc vòng lặp <tr> trong tbody
        | của bảng gắn data-deferred:
        |
        |   <tbody>@deferredRows('mdTable') @foreach (...) <tr>...</tr> @endforeach @enddeferredRows</tbody>
        |
        | Lần tải trang vùng này không render; lượt ?_deferred=1 render vào bộ đệm để trả JSON.
        */
        Blade::directive('deferredRows', fn($expression) => "<?php if (\\App\\Support\\DeferredRows::start({$expression})): ?>");
        Blade::directive('enddeferredRows', fn() => '<?php \\App\\Support\\DeferredRows::end(); endif; ?>');
    }
}
