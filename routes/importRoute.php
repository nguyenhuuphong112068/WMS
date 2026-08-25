<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: NHẬP
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Import/...
| View       : resources/views/pages/import/...
| Tên route  : pages.import.<chứcNăng>.<action>
*/

use App\Http\Controllers\Pages\Import\ChemicalImportController;
use App\Http\Controllers\Pages\Import\StandardImportController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/import')
    ->name('pages.import.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/chemicalImport')->name('chemicalImport.')->controller(ChemicalImportController::class)->group(function () {
            Route::get('', 'index')->name('list');
            // Nhận lô hoá chất do phòng ban khác chuyển sang, khai định khu của phòng mình
            Route::post('receive', 'receive')->name('receive');
            // Từ chối nhận: khoá phiếu chuyển, trả số lượng lại tồn của phòng gửi
            Route::post('rejectTransfer', 'rejectTransfer')->name('rejectTransfer');
            Route::post('store', 'store')->name('store');
            // Trang in nhãn dán lô hàng (mã vạch Code 128), mở tab mới rồi bấm In
            Route::get('label', 'label')->name('label');
            Route::get('history', 'history')->name('history');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        Route::prefix('/standardImport')->name('standardImport.')->controller(StandardImportController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            // Trang in nhãn dán ống chuẩn (mã vạch Code 128), mở tab mới rồi bấm In
            Route::get('label', 'label')->name('label');
            Route::get('history', 'history')->name('history');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::get('download-attachment/{id}', 'downloadAttachment')->name('downloadAttachment');
            Route::post('delete-attachment', 'deleteAttachment')->name('deleteAttachment');
        });
    });
