<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: TỒN
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Inventory/...
| View       : resources/views/pages/inventory/...
| Tên route  : pages.inventory.<chứcNăng>.<action>
|
| Tồn được tính từ imports / exports / inventory_balancings nên không có
| store / update / deActive. Riêng "balancing" là nút Cân Đối trên bảng tồn kho,
| ghi thêm một dòng inventory_balancings để chỉnh số lượng nhập về đúng thực tế,
| và "internalExpiry" là nút Xác Định Hạn Dùng Nội Bộ, ghi imports.internal_expired_date.
|
| Riêng "materialStocktake" là tab KIỂM KÊ ĐỊNH KỲ nằm trong màn hình Tồn Kho Vật Tư
| (chu kỳ 1 tháng 1 lần) nên không có index - dữ liệu của tab do
| MaterialInventoryController::index() lấy qua MaterialStocktakeController::panel().
|
| "standardStability" là hai tab ĐÁNH GIÁ HẠN DÙNG CTC / KẾ HOẠCH ĐÁNH GIÁ của màn hình
| Tồn Kho Chất Chuẩn (dữ liệu do StandardInventoryController::index() lấy qua
| StandardStabilityController::tabData() / planData()): index chỉ chuyển sang tab, còn
| lại là trang chi tiết một phiếu + các hành động ghi. Phiếu không xoá cứng nên dùng
| "cancel" (chuyển "Huỷ") thay cho deActive; "resume" mở lại phiếu đã ngưng; mốc chưa có
| kết quả thì xoá được (deleteItem). Mọi thay đổi ghi standard_stability_assessment_histories.
*/

use App\Http\Controllers\Pages\Inventory\ChemicalInventoryController;
use App\Http\Controllers\Pages\Inventory\MaterialInventoryController;
use App\Http\Controllers\Pages\Inventory\MaterialStocktakeController;
use App\Http\Controllers\Pages\Inventory\StandardInventoryController;
use App\Http\Controllers\Pages\Inventory\StandardStabilityController;
use App\Http\Controllers\Pages\Inventory\ThresholdReconciliationController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/inventory')
    ->name('pages.inventory.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/chemicalInventory')->name('chemicalInventory.')->controller(ChemicalInventoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            // Dữ liệu JSON của các bảng tồn - trang chỉ trả khung, bảng nạp bằng AJAX
            Route::get('data', 'data')->name('data');
            Route::get('zones', 'zones')->name('zones');
            // Dữ liệu JSON cho modal Biểu Đồ Nhập - Xuất - Tồn của một hoá chất
            Route::get('chart', 'chart')->name('chart');
            // Dữ liệu JSON cho modal "Chi Tiết Con Số" (bấm badge trên bảng tồn)
            Route::get('movements', 'movements')->name('movements');
            Route::post('balancing', 'balancing')->name('balancing');
            Route::post('internalExpiry', 'internalExpiry')->name('internalExpiry');
            // Cửa sổ xem file đính kèm của phiếu nhập ngay trên màn hình Tồn Kho
            Route::get('download-attachment/{id}', 'downloadAttachment')->name('downloadAttachment');
            Route::post('toggle-attachment-status', 'toggleAttachmentStatus')->name('toggleAttachmentStatus');
            Route::post('upload-attachment', 'uploadAttachment')->name('uploadAttachment');
        });

        Route::prefix('/standardStability')->name('standardStability.')->controller(StandardStabilityController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('detail', 'detail')->name('detail');

            // Đầu phiếu
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('cancel', 'cancel')->name('cancel');
            Route::post('resume', 'resume')->name('resume');

            // Mốc đánh giá
            Route::post('storeItem', 'storeItem')->name('storeItem');
            Route::post('updateItem', 'updateItem')->name('updateItem');
            Route::post('deleteItem', 'deleteItem')->name('deleteItem');
            Route::post('assess', 'assess')->name('assess');
            Route::post('issueTestings', 'issueTestings')->name('issueTestings');
        });

        Route::prefix('/standardInventory')->name('standardInventory.')->controller(StandardInventoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            // Dữ liệu JSON cho modal "Chi Tiết Con Số" (bấm badge trên bảng tồn)
            Route::get('movements', 'movements')->name('movements');
            Route::post('balancing', 'balancing')->name('balancing');
            Route::post('internalExpiry', 'internalExpiry')->name('internalExpiry');
            Route::post('weightRemark', 'weightRemark')->name('weightRemark');
            Route::post('tareWeight', 'tareWeight')->name('tareWeight');
            // Cập nhật hạn dùng cho ống Retest / Check online, lưu vết ở standard_expiry_updates
            Route::post('expiryUpdate', 'expiryUpdate')->name('expiryUpdate');
            Route::get('expiry-attachment/{id}', 'downloadExpiryAttachment')->name('downloadExpiryAttachment');
            // Cửa sổ xem file đính kèm của phiếu nhập ngay trên màn hình Tồn Kho
            Route::get('download-attachment/{id}', 'downloadAttachment')->name('downloadAttachment');
            Route::post('toggle-attachment-status', 'toggleAttachmentStatus')->name('toggleAttachmentStatus');
            Route::post('upload-attachment', 'uploadAttachment')->name('uploadAttachment');
        });

        Route::prefix('/materialInventory')->name('materialInventory.')->controller(MaterialInventoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            // Dữ liệu JSON cho modal Biểu Đồ Nhập - Xuất - Tồn của một vật tư
            Route::get('chart', 'chart')->name('chart');
            // Dữ liệu JSON cho modal "Chi Tiết Con Số" (bấm badge trên bảng tồn)
            Route::get('movements', 'movements')->name('movements');
            Route::post('balancing', 'balancing')->name('balancing');
            // Cửa sổ xem file đính kèm của phiếu nhập ngay trên màn hình Tồn Kho
            Route::get('download-attachment/{id}', 'downloadAttachment')->name('downloadAttachment');
            Route::post('toggle-attachment-status', 'toggleAttachmentStatus')->name('toggleAttachmentStatus');
            Route::post('upload-attachment', 'uploadAttachment')->name('uploadAttachment');
        });

        // Tab "Đối chiếu ngưỡng PL IV" của Tồn Kho Hoá Chất - chỉ đọc, số liệu cộng trong phạm
        // vi các phòng ban thuộc công ty của phòng ban đang chọn. index chỉ chuyển sang tab,
        // pane trả HTML nội dung tab (nạp AJAX khi mở tab lần đầu).
        Route::prefix('/thresholdReconciliation')->name('thresholdReconciliation.')->controller(ThresholdReconciliationController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('pane', 'pane')->name('pane');
            // Dữ liệu JSON cho modal "Chi tiết đối chiếu Ngưỡng Tồn Trữ PL IV"
            Route::get('thresholdDetail', 'thresholdDetail')->name('thresholdDetail');
        });

        // Tab KIỂM KÊ ĐỊNH KỲ của màn hình Tồn Kho Vật Tư - chu kỳ 1 tháng 1 lần
        Route::prefix('/materialStocktake')->name('materialStocktake.')->controller(MaterialStocktakeController::class)->group(function () {
            Route::post('open', 'open')->name('open');
            Route::post('count', 'count')->name('count');
            Route::post('complete', 'complete')->name('complete');
            Route::post('deActive', 'deActive')->name('deActive');
            // Dữ liệu JSON cho modal xem lại một kỳ kiểm kê đã chốt
            Route::get('detail', 'detail')->name('detail');
        });
    });
