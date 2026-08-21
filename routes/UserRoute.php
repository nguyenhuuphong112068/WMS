<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: PHÂN QUYỀN
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/User/...
| View       : resources/views/pages/user/...
| Tên route  : pages.user.<chứcNăng>.<action>
*/

use App\Http\Controllers\Pages\User\PermissionContoller;
use App\Http\Controllers\Pages\User\RoleController;
use App\Http\Controllers\Pages\User\UserController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/user')
    ->name('pages.user.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/user')
            ->name('user.')
            ->controller(UserController::class)
            ->group(function () {
                Route::get('', 'index')->name('list');
                Route::post('store', 'store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive/{id}', 'deActive')->name('deActive');
            });

        Route::prefix('/permission')
            ->name('permission.')
            ->controller(PermissionContoller::class)
            ->group(function () {
                Route::get('', 'index')->name('list');
            });

        Route::prefix('/role')
            ->name('role.')
            ->controller(RoleController::class)
            ->group(function () {
                Route::get('', 'index')->name('list');
                Route::post('store_or_update', 'store_or_update')->name('store_or_update');
            });
    });
