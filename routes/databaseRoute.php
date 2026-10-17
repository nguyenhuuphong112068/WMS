<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: CƠ SỞ DỮ LIỆU (khu Quản Trị - chỉ Admin)
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Database/...
| View       : resources/views/pages/database/...
| Tên route  : pages.database.<action>
*/

use App\Http\Controllers\Pages\Database\DatabaseController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/database')
    ->name('pages.database.')
    ->middleware([CheckLogin::class, 'role:Admin'])
    ->controller(DatabaseController::class)
    ->group(function () {
        Route::get('', 'index')->name('list');
        Route::get('signature', 'signature')->name('signature');
        Route::get('rows', 'rows')->name('rows');
    });
