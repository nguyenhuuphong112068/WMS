<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: HƯỚNG DẪN SỬ DỤNG
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/UserGuide/...
| View       : resources/views/pages/userGuide/...
| Tên route  : pages.userGuide.<action>
| Mọi tài khoản đã đăng nhập đều xem được, không cần quyền riêng.
*/

use App\Http\Controllers\Pages\UserGuide\UserGuideController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/userGuide')
    ->name('pages.userGuide.')
    ->middleware(CheckLogin::class)
    ->controller(UserGuideController::class)
    ->group(function () {
        Route::get('', 'index')->name('list');
        Route::get('{guide}', 'show')->name('show');
    });
