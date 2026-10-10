<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: THỐNG KÊ
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Statistic/...
| View       : resources/views/pages/statistic/...
| Tên route  : pages.statistic.<chứcNăng>.<action>
|
| Các màn hình chỉ đọc, tổng hợp số liệu đã phát sinh ở nhóm Sử Dụng.
*/

use App\Http\Controllers\Pages\Statistic\MaterialIssueStatisticController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/statistic')
    ->name('pages.statistic.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        // Thống kê cấp phát vật tư theo đối tượng + khoảng thời gian
        Route::prefix('/materialIssue')->name('materialIssue.')->controller(MaterialIssueStatisticController::class)->group(function () {
            Route::get('', 'index')->name('list');
            // Ô chọn Đối Tượng ở bộ lọc (select2 AJAX)
            Route::get('objects', 'objects')->name('objects');
        });
    });
