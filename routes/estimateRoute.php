<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: DỰ TRÙ
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Estimate/...
| View       : resources/views/pages/estimate/...
| Tên route  : pages.estimate.<chứcNăng>.<action>
|
| chemicalEstimate : phòng ban lập phiếu dự trù hoá chất, khai mặt hàng + số lượng
|                    theo tháng, rồi trình ký 2 bước (Phó/Trưởng Phòng -> Ban Giám Đốc).
| estimateReception: bộ phận Cung Ứng tiếp nhận và giải quyết phiếu đã được phê duyệt.
*/

use App\Http\Controllers\Pages\Estimate\ChemicalEstimateController;
use App\Http\Controllers\Pages\Estimate\EstimateReceptionController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/estimate')
    ->name('pages.estimate.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/chemicalEstimate')->name('chemicalEstimate.')->controller(ChemicalEstimateController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('detail', 'detail')->name('detail');
            Route::get('history', 'history')->name('history');

            // Đầu phiếu
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');

            // Mặt hàng dự trù + số lượng theo tháng
            Route::post('storeItem', 'storeItem')->name('storeItem');
            Route::post('updateItem', 'updateItem')->name('updateItem');
            Route::post('deleteItem', 'deleteItem')->name('deleteItem');

            // Trình ký 2 bước
            Route::post('submit', 'submit')->name('submit');
            Route::post('signManager', 'signManager')->name('signManager');
            Route::post('signDirector', 'signDirector')->name('signDirector');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/estimateReception')->name('estimateReception.')->controller(EstimateReceptionController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('detail', 'detail')->name('detail');
            Route::get('history', 'history')->name('history');
            Route::post('receive', 'receive')->name('receive');
            Route::post('complete', 'complete')->name('complete');
        });
    });
