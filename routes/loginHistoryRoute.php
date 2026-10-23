<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: LỊCH SỬ ĐĂNG NHẬP
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/LoginHistory/...
| View       : resources/views/pages/loginHistory/...
| Tên route  : pages.loginHistory.<action>
| Toàn hệ thống cần quyền login_history_view (kiểm tra trong controller); "Của tôi" ai cũng xem được.
*/

use App\Http\Controllers\Pages\LoginHistory\LoginHistoryController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/loginHistory')
    ->name('pages.loginHistory.')
    ->middleware(CheckLogin::class)
    ->controller(LoginHistoryController::class)
    ->group(function () {
        Route::get('', 'index')->name('list');
        Route::get('mine', 'mine')->name('mine');
    });
