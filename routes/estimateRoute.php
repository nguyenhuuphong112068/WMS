<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: DỰ TRÙ
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Estimate/...
| View       : resources/views/pages/estimate/...
| Tên route  : pages.estimate.<chứcNăng>.<action>
|
| chemicalEstimate / standardEstimate / materialEstimate: phòng ban lập phiếu dự trù,
| khai mặt hàng + số lượng theo tháng, rồi trình ký 2 bước (Phó/Trưởng Phòng -> Ban
| Giám Đốc). Duyệt xong phiếu tự đánh dấu đã tiếp nhận - không còn màn "Tiếp Nhận Dự Trù".
*/

use App\Http\Controllers\Pages\Estimate\ChemicalEstimateController;
use App\Http\Controllers\Pages\Estimate\MaterialEstimateController;
use App\Http\Controllers\Pages\Estimate\StandardEstimateController;
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
            Route::post('destroy', 'destroy')->name('destroy');

            // Mặt hàng dự trù + số lượng theo tháng
            Route::post('storeItem', 'storeItem')->name('storeItem');
            Route::post('updateItem', 'updateItem')->name('updateItem');
            Route::post('deleteItem', 'deleteItem')->name('deleteItem');

            // Trình ký 2 bước
            Route::post('submit', 'submit')->name('submit');
            Route::post('signManager', 'signManager')->name('signManager');
            Route::post('signDirector', 'signDirector')->name('signDirector');
            Route::post('reject', 'reject')->name('reject');

            // Cập nhật ngày mong muốn giao khi đã duyệt
            Route::post('updateItemStatus', 'updateItemStatus')->name('updateItemStatus');
            Route::post('updatePromisedDate', 'updatePromisedDate')->name('updatePromisedDate');
            Route::get('getPromisedDateHistory/{itemId}', 'getPromisedDateHistory')->name('getPromisedDateHistory');
            Route::post('storeItemChat', 'storeItemChat')->name('storeItemChat');
        });

        // Dự trù chất chuẩn: cùng luồng trình ký 2 bước với dự trù hoá chất,
        // dữ liệu nằm ở bộ bảng standard_estimates riêng.
        Route::prefix('/standardEstimate')->name('standardEstimate.')->controller(StandardEstimateController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('detail', 'detail')->name('detail');
            Route::get('history', 'history')->name('history');

            // Đầu phiếu
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('destroy', 'destroy')->name('destroy');

            // Chất chuẩn dự trù + số lượng theo tháng
            Route::post('storeItem', 'storeItem')->name('storeItem');
            Route::post('updateItem', 'updateItem')->name('updateItem');
            Route::post('deleteItem', 'deleteItem')->name('deleteItem');

            // Trình ký 2 bước
            Route::post('submit', 'submit')->name('submit');
            Route::post('signManager', 'signManager')->name('signManager');
            Route::post('signDirector', 'signDirector')->name('signDirector');
            Route::post('reject', 'reject')->name('reject');

            // Cập nhật ngày mong muốn giao khi đã duyệt
            Route::post('updateItemStatus', 'updateItemStatus')->name('updateItemStatus');
            Route::post('updatePromisedDate', 'updatePromisedDate')->name('updatePromisedDate');
            Route::get('getPromisedDateHistory/{itemId}', 'getPromisedDateHistory')->name('getPromisedDateHistory');
            Route::post('storeItemChat', 'storeItemChat')->name('storeItemChat');
        });

        // Dự trù vật tư: cùng luồng trình ký 2 bước, dữ liệu ở bộ bảng material_estimates.
        Route::prefix('/materialEstimate')->name('materialEstimate.')->controller(MaterialEstimateController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('detail', 'detail')->name('detail');
            Route::get('history', 'history')->name('history');

            // Đầu phiếu
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('destroy', 'destroy')->name('destroy');

            // Vật tư dự trù + số lượng theo tháng
            Route::post('storeItem', 'storeItem')->name('storeItem');
            Route::post('updateItem', 'updateItem')->name('updateItem');
            Route::post('deleteItem', 'deleteItem')->name('deleteItem');

            // Trình ký 2 bước
            Route::post('submit', 'submit')->name('submit');
            Route::post('signManager', 'signManager')->name('signManager');
            Route::post('signDirector', 'signDirector')->name('signDirector');
            Route::post('reject', 'reject')->name('reject');

            // Cập nhật ngày mong muốn giao khi đã duyệt
            Route::post('updateItemStatus', 'updateItemStatus')->name('updateItemStatus');
            Route::post('updatePromisedDate', 'updatePromisedDate')->name('updatePromisedDate');
            Route::get('getPromisedDateHistory/{itemId}', 'getPromisedDateHistory')->name('getPromisedDateHistory');
            Route::post('storeItemChat', 'storeItemChat')->name('storeItemChat');
        });
    });
