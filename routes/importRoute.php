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
use App\Http\Controllers\Pages\Import\MaterialImportController;
use App\Http\Controllers\Pages\Import\StandardImportController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/import')
    ->name('pages.import.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/chemicalImport')->name('chemicalImport.')->controller(ChemicalImportController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            // Cảnh báo ngưỡng PL IV tức thời khi chọn hoá chất / gõ số lượng trong modal Nhập/Điều chỉnh
            Route::get('checkThreshold', 'checkThreshold')->name('checkThreshold');
            // Trang in nhãn dán lô hàng (mã vạch Code 128), mở tab mới rồi bấm In
            Route::get('label', 'label')->name('label');
            // Trang in nhãn báo về khi bấm In: ghi audit log in nhãn của lô nào, mấy cái, lúc nào
            Route::post('labelPrinted', 'labelPrinted')->name('labelPrinted');
            Route::get('history', 'history')->name('history');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::get('download-attachment/{id}', 'downloadAttachment')->name('downloadAttachment');
            Route::post('delete-attachment', 'deleteAttachment')->name('deleteAttachment');
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

        Route::prefix('/materialImport')->name('materialImport.')->controller(MaterialImportController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            // Trang in nhãn dán lô vật tư (mã QR), mở tab mới rồi bấm In
            Route::get('label', 'label')->name('label');
            // Trang in nhãn báo về khi bấm In: ghi audit log in nhãn của lô nào, mấy cái, lúc nào
            Route::post('labelPrinted', 'labelPrinted')->name('labelPrinted');
            Route::get('history', 'history')->name('history');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::get('download-attachment/{id}', 'downloadAttachment')->name('downloadAttachment');
            Route::post('delete-attachment', 'deleteAttachment')->name('deleteAttachment');
        });
    });
