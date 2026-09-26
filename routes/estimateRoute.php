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
| khai mặt hàng + số lượng theo tháng, rồi trình ký theo quy trình động do người lập tự
| khai (tối thiểu 2 bước, bước cuối là Ban Giám Đốc). Duyệt xong phiếu tự đánh dấu đã
| tiếp nhận - không còn màn "Tiếp Nhận Dự Trù".
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

            // Cảnh báo ngưỡng PL IV tức thời khi chọn hoá chất / nhập số lượng trong modal
            Route::get('checkThreshold', 'checkThreshold')->name('checkThreshold');
            // Nút "Chi tiết" của cảnh báo: các lượng đóng góp (tồn theo lô, dự trù chưa hoàn thành, lần này)
            Route::get('thresholdDetail', 'thresholdDetail')->name('thresholdDetail');

            // Trình ký động: người lập khai số bước ký + người ký từng bước (tối thiểu 2,
            // bước cuối là Ban Giám Đốc). signStep ký bước đang chờ, dùng chung cho cả tab
            // "Ký duyệt (mọi phòng ban)" qua tham số scope=inbox.
            Route::post('submit', 'submit')->name('submit');
            Route::post('signStep', 'signStep')->name('signStep');
            Route::post('reject', 'reject')->name('reject');

            // Cập nhật ngày mong muốn giao khi đã duyệt
            Route::post('updateItemStatus', 'updateItemStatus')->name('updateItemStatus');
            // Huỷ mục cần xác nhận của cả phòng đề nghị và bộ phận mua hàng
            Route::post('purchaseCancel', 'purchaseCancel')->name('purchaseCancel');
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

            // Trình ký động: người lập khai số bước ký + người ký từng bước (tối thiểu 2,
            // bước cuối là Ban Giám Đốc). signStep ký bước đang chờ, dùng chung cho cả tab
            // "Ký duyệt (mọi phòng ban)" qua tham số scope=inbox.
            Route::post('submit', 'submit')->name('submit');
            Route::post('signStep', 'signStep')->name('signStep');
            Route::post('reject', 'reject')->name('reject');

            // Cập nhật ngày mong muốn giao khi đã duyệt
            Route::post('updateItemStatus', 'updateItemStatus')->name('updateItemStatus');
            // Huỷ mục cần xác nhận của cả phòng đề nghị và bộ phận mua hàng
            Route::post('purchaseCancel', 'purchaseCancel')->name('purchaseCancel');
            Route::post('updatePromisedDate', 'updatePromisedDate')->name('updatePromisedDate');
            Route::get('getPromisedDateHistory/{itemId}', 'getPromisedDateHistory')->name('getPromisedDateHistory');
            Route::post('storeItemChat', 'storeItemChat')->name('storeItemChat');
        });

        // Dự trù vật tư: cùng luồng trình ký 2 bước, dữ liệu ở bộ bảng material_estimates.
        Route::prefix('/materialEstimate')->name('materialEstimate.')->controller(MaterialEstimateController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('detail', 'detail')->name('detail');
            Route::get('history', 'history')->name('history');

            // Tab "Danh sách vật tư cần dự trù": dưới ngưỡng tối thiểu + ghi nhớ thủ công.
            // Chọn nhiều vật tư rồi đi tiếp một trong hai đường: lập phiếu dự trù, hoặc (vật
            // tư do Hành Chánh mua) gửi thẳng đề nghị liên phòng ban cho phòng Hành Chánh.
            Route::post('watchlistDismiss', 'watchlistDismiss')->name('watchlistDismiss');
            Route::post('watchlistEstimateStore', 'watchlistEstimateStore')->name('watchlistEstimateStore');
            Route::post('watchlistTransferStore', 'watchlistTransferStore')->name('watchlistTransferStore');

            // Đầu phiếu
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('destroy', 'destroy')->name('destroy');

            // Vật tư dự trù + số lượng theo tháng
            Route::post('storeItem', 'storeItem')->name('storeItem');
            Route::post('updateItem', 'updateItem')->name('updateItem');
            Route::post('deleteItem', 'deleteItem')->name('deleteItem');

            // File đính kèm của phiếu (xoá mềm)
            Route::post('uploadAttachment', 'uploadAttachment')->name('uploadAttachment');
            Route::get('downloadAttachment/{id}', 'downloadAttachment')->name('downloadAttachment');
            Route::post('deleteAttachment', 'deleteAttachment')->name('deleteAttachment');

            // Huỷ mục dự trù cần xác nhận của cả phòng đề nghị và bộ phận mua hàng
            Route::post('purchaseCancel', 'purchaseCancel')->name('purchaseCancel');

            // Trình ký động: người lập khai số bước ký + người ký từng bước (tối thiểu 2,
            // bước cuối là Ban Giám Đốc). signStep ký bước đang chờ, dùng chung cho cả tab
            // "Ký duyệt (mọi phòng ban)" qua tham số scope=inbox.
            Route::post('submit', 'submit')->name('submit');
            Route::post('signStep', 'signStep')->name('signStep');
            Route::post('reject', 'reject')->name('reject');

            // Cập nhật ngày mong muốn giao khi đã duyệt
            Route::post('updateItemStatus', 'updateItemStatus')->name('updateItemStatus');
            Route::post('updatePromisedDate', 'updatePromisedDate')->name('updatePromisedDate');
            Route::get('getPromisedDateHistory/{itemId}', 'getPromisedDateHistory')->name('getPromisedDateHistory');
            Route::post('storeItemChat', 'storeItemChat')->name('storeItemChat');
        });
    });
