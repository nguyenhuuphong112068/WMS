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
        |   @perm('materData_create')  ... @endperm      -> có quyền này
        |   @permAny(['a', 'b'])       ... @endpermAny   -> có ít nhất một trong các quyền
        |
        | Muốn làm ngược lại (hiện khi KHÔNG có quyền) thì dùng @unlessperm / @endunlessperm.
        */
        Blade::if('perm', fn(string $name) => user_can($name));
        Blade::if('permAny', fn(array $names) => user_can_any($names));
    }
}
