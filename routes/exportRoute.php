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
use App\Http\Controllers\Pages\Export\MaterialExportController;
use App\Http\Controllers\Pages\Export\StandardExportController;
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
            // Tra lô theo mã xuất nhập khi quét mã vạch trên nhãn, trả JSON cho form xuất
            Route::get('lookup', 'lookup')->name('lookup');
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

        Route::prefix('/standardExport')->name('standardExport.')->controller(StandardExportController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            // Lịch sử điều chỉnh của một phiếu, trả JSON cho modal xem lịch sử
            Route::get('history', 'history')->name('history');
            // Tra ống theo mã ống chuẩn khi quét mã vạch trên nhãn, trả JSON cho form xuất
            Route::get('lookup', 'lookup')->name('lookup');
            // Đề nghị cấp phát chuẩn & Cấp phát chuẩn cho Tổ
            Route::post('requestStore', 'requestStore')->name('requestStore');
            Route::post('requestUpdate', 'requestUpdate')->name('requestUpdate');
            Route::post('requestSend', 'requestSend')->name('requestSend');
            Route::post('issueStore', 'issueStore')->name('issueStore');
            Route::post('issueDraftStore', 'issueDraftStore')->name('issueDraftStore');
            Route::post('requestReject', 'requestReject')->name('requestReject');
            Route::post('requestDestroy', 'requestDestroy')->name('requestDestroy');
            Route::get('getIssuedStandards', 'getIssuedStandards')->name('getIssuedStandards');
            Route::get('getCategoryInfo', 'getCategoryInfo')->name('getCategoryInfo');
        });

        /*
        | SỬ DỤNG VẬT TƯ - bắt buộc qua đề nghị được phê duyệt (Trưởng/Phó Phòng bắt
        | buộc, Ban Giám Đốc tuỳ chọn) rồi kho cấp phát. Cấp phát trừ tồn ngay; Tổ chỉ
        | chốt lại đã dùng bao nhiêu hoặc trả về kho (useStore).
        | Loại bỏ (type = cancel) hàng hỏng thì lập thẳng, không cần đề nghị.
        */
        Route::prefix('/materialExport')->name('materialExport.')->controller(MaterialExportController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::get('history', 'history')->name('history');
            Route::get('lookup', 'lookup')->name('lookup');
            Route::get('getCategoryInfo', 'getCategoryInfo')->name('getCategoryInfo');

            // Đề nghị cấp phát + trình ký
            Route::post('requestStore', 'requestStore')->name('requestStore');
            Route::post('requestUpdate', 'requestUpdate')->name('requestUpdate');
            Route::post('requestSubmit', 'requestSubmit')->name('requestSubmit');
            Route::post('requestSignManager', 'requestSignManager')->name('requestSignManager');
            Route::post('requestSignDirector', 'requestSignDirector')->name('requestSignDirector');
            Route::post('requestReject', 'requestReject')->name('requestReject');
            Route::post('requestDestroy', 'requestDestroy')->name('requestDestroy');

            // Cấp phát của kho
            Route::post('issueStore', 'issueStore')->name('issueStore');
            Route::post('issueReject', 'issueReject')->name('issueReject');

            // Tổ chốt dòng đã cấp phát: ghi nhận sử dụng hoặc trả về kho
            Route::post('useStore', 'useStore')->name('useStore');
        });
    });
