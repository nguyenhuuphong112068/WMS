<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: SỬ DỤNG
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Export/...
| View       : resources/views/pages/export/...
| Tên route  : pages.export.<chứcNăng>.<action>
*/

use App\Http\Controllers\Pages\Export\ChemicalDisposalController;
use App\Http\Controllers\Pages\Export\ChemicalExportController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/export')
    ->name('pages.export.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/chemicalExport')->name('chemicalExport.')->controller(ChemicalExportController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            // Lịch sử điều chỉnh của một phiếu, trả JSON cho modal xem lịch sử
            Route::get('history', 'history')->name('history');
            // Đề nghị chuyển hoá chất: nguồn thông tin trước khi lập phiếu chuyển
            Route::post('requestStore', 'requestStore')->name('requestStore');
            Route::post('requestRespond', 'requestRespond')->name('requestRespond');
        });

        /*
        | HUỶ HOÁ CHẤT - bước 2 của nghiệp vụ huỷ bỏ.
        | Màn hình nằm trong tab "Hoá chất chờ huỷ" của Sử Dụng Hoá Chất nên không có
        | action index riêng; dữ liệu do ChemicalExportController::index() nạp sẵn.
        */
        Route::prefix('/chemicalDisposal')->name('chemicalDisposal.')->controller(ChemicalDisposalController::class)->group(function () {
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('addItems', 'addItems')->name('addItems');
            Route::post('removeItem', 'removeItem')->name('removeItem');
            Route::post('submit', 'submit')->name('submit');
            Route::post('decide', 'decide')->name('decide');
            Route::post('complete', 'complete')->name('complete');
            Route::post('deActive', 'deActive')->name('deActive');
            // Trang in biểu mẫu QA/F/058-07, mở tab mới rồi Ctrl+P -> Lưu thành PDF
            Route::get('print', 'print')->name('print');
        });
    });
