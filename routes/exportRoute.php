<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: SỬ DỤNG
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Export/...
| View       : resources/views/pages/export/...
| Tên route  : pages.export.<chứcNăng>.<action>
*/

use App\Http\Controllers\Pages\Export\ChemicalExportController;
use App\Http\Controllers\Pages\Export\ChemicalQuarantineController;
use App\Http\Controllers\Pages\Export\MaterialCalibrationController;
use App\Http\Controllers\Pages\Export\MaterialExportController;
use App\Http\Controllers\Pages\Export\MaterialQuarantineController;
use App\Http\Controllers\Pages\Export\StandardExportController;
use App\Http\Controllers\Pages\Export\StandardQuarantineController;
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
            // Kiểm tra Số Lượng chạy ngầm khi gõ ở modal Sử Dụng Hoá Chất (tồn thật + 5%)
            Route::get('checkAmount', 'checkAmount')->name('checkAmount');
            // Đề nghị chuyển hoá chất LIÊN PHÒNG BAN: A đề nghị -> B cấp phát (trừ tồn B) -> A nhận (tạo tồn A)
            // Danh mục + tồn kho của phòng được đề nghị, trả JSON cho picker chọn nhiều hoá chất
            Route::get('transferDepartmentStock', 'transferDepartmentStock')->name('transferDepartmentStock');
            Route::post('transferRequestStore', 'transferRequestStore')->name('transferRequestStore');
            Route::post('transferRequestUpdate', 'transferRequestUpdate')->name('transferRequestUpdate');
            Route::post('transferRequestSend', 'transferRequestSend')->name('transferRequestSend');
            Route::post('transferRequestDestroy', 'transferRequestDestroy')->name('transferRequestDestroy');
            Route::post('transferIssueStore', 'transferIssueStore')->name('transferIssueStore');
            Route::post('transferRequestReject', 'transferRequestReject')->name('transferRequestReject');
            Route::post('transferReceiveStore', 'transferReceiveStore')->name('transferReceiveStore');
            Route::post('transferReceiveReject', 'transferReceiveReject')->name('transferReceiveReject');
            // Picker "Chọn Nhiều Từ Tồn Kho": xuất/lưu tạm nhiều phiếu nhập cùng lúc
            Route::post('storeBatch', 'storeBatch')->name('storeBatch');
            // Tab "Phiếu Tạm": dùng ngay hoặc xoá các đợt đã lưu tạm
            Route::post('draftFinalize', 'draftFinalize')->name('draftFinalize');
            Route::post('draftDeleteItem', 'draftDeleteItem')->name('draftDeleteItem');
            Route::post('draftDeleteBatch', 'draftDeleteBatch')->name('draftDeleteBatch');
        });

        /*
        | HOÁ CHẤT HỎNG - 3 bước: Cách ly chờ quyết định (bấm [Cách ly] ở Tồn Kho Hoá Chất, trừ tồn
        | ngay, trả về kho được) -> Loại bỏ - Chờ huỷ -> Huỷ. Các bước sau nằm trong tab "Cách ly -
        | Loại bỏ - Huỷ" của Sử Dụng Hoá Chất, dữ liệu do ChemicalExportController::index() nạp sẵn.
        */
        Route::prefix('/chemicalQuarantine')->name('chemicalQuarantine.')->controller(ChemicalQuarantineController::class)->group(function () {
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('decide', 'decide')->name('decide');
            Route::post('dispose', 'dispose')->name('dispose');
        });

        /*
        | CHẤT CHUẨN HỎNG - 3 bước như hoá chất: Cách ly chờ quyết định (bấm [Cách ly] ở Tồn Kho Chất
        | Chuẩn, trừ tồn ngay, trả về kho được) -> Loại bỏ - Chờ huỷ -> Huỷ. Các bước sau nằm trong tab
        | "Cách ly - Loại bỏ - Huỷ" của Sử Dụng Chất Chuẩn, dữ liệu do StandardExportController::index() nạp sẵn.
        */
        Route::prefix('/standardQuarantine')->name('standardQuarantine.')->controller(StandardQuarantineController::class)->group(function () {
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('decide', 'decide')->name('decide');
            Route::post('dispose', 'dispose')->name('dispose');
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
            // Đề nghị cấp phát chuẩn nội bộ & Cấp phát chuẩn cho Tổ
            Route::post('requestStore', 'requestStore')->name('requestStore');
            Route::post('requestUpdate', 'requestUpdate')->name('requestUpdate');
            Route::post('requestSend', 'requestSend')->name('requestSend');
            Route::post('issueStore', 'issueStore')->name('issueStore');
            Route::post('issueDraftStore', 'issueDraftStore')->name('issueDraftStore');
            Route::post('requestReject', 'requestReject')->name('requestReject');
            Route::post('requestUnuse', 'requestUnuse')->name('requestUnuse');
            Route::post('requestDestroy', 'requestDestroy')->name('requestDestroy');
            Route::get('getIssuedStandards', 'getIssuedStandards')->name('getIssuedStandards');
            Route::get('getCategoryInfo', 'getCategoryInfo')->name('getCategoryInfo');

            // Đề nghị cấp phát chuẩn LIÊN PHÒNG BAN: A đề nghị -> B cấp phát (trừ tồn B) -> A nhận (tạo tồn A)
            // Danh mục + tồn kho của phòng được đề nghị, trả JSON cho picker chọn nhiều chất chuẩn
            Route::get('transferDepartmentStock', 'transferDepartmentStock')->name('transferDepartmentStock');
            Route::post('transferRequestStore', 'transferRequestStore')->name('transferRequestStore');
            Route::post('transferRequestUpdate', 'transferRequestUpdate')->name('transferRequestUpdate');
            Route::post('transferRequestSend', 'transferRequestSend')->name('transferRequestSend');
            Route::post('transferRequestDestroy', 'transferRequestDestroy')->name('transferRequestDestroy');
            Route::post('transferIssueStore', 'transferIssueStore')->name('transferIssueStore');
            Route::post('transferRequestReject', 'transferRequestReject')->name('transferRequestReject');
            Route::post('transferReceiveStore', 'transferReceiveStore')->name('transferReceiveStore');
            Route::post('transferReceiveReject', 'transferReceiveReject')->name('transferReceiveReject');
        });

        /*
        | SỬ DỤNG VẬT TƯ - bắt buộc qua đề nghị được phê duyệt rồi kho cấp phát. Số bước
        | ký và người ký từng bước do người lập phiếu tự khai (0 bước = duyệt thẳng đến
        | người cấp phát). Cấp phát là xuất kho, trừ tồn
        | ngay và tính luôn là vật tư đã đem sử dụng - không có bước chốt lại.
        | Loại bỏ hàng hỏng đi riêng quy trình 3 bước ở MaterialQuarantineController.
        */
        Route::prefix('/materialExport')->name('materialExport.')->controller(MaterialExportController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::get('history', 'history')->name('history');
            Route::get('lookup', 'lookup')->name('lookup');
            Route::get('getCategoryInfo', 'getCategoryInfo')->name('getCategoryInfo');
            Route::get('requestStockMap', 'requestStockMap')->name('requestStockMap');
            // Ô chọn Đối Tượng ở cột Mục đích của dòng đề nghị (select2 AJAX)
            Route::get('requestObjects', 'requestObjects')->name('requestObjects');
            // Tab "Cấp phát vật tư" - phiếu soạn hàng A4 in theo đường đi trong kho
            Route::get('prepPrint', 'prepPrint')->name('prepPrint');
            // "Ghi nhớ vật tư cần dự trù" từ dòng đề nghị - hiện ở tab Dự Trù Vật Tư
            Route::post('watchlistRemember', 'watchlistRemember')->name('watchlistRemember');
            // Tab "Đề nghị VPP": lập đề nghị tới kho VPP (Hành Chính) từ danh sách vật tư cần đề nghị
            Route::post('vppWatchlistStore', 'vppWatchlistStore')->name('vppWatchlistStore');
            Route::post('watchlistDismiss', 'watchlistDismiss')->name('watchlistDismiss');

            // Đề nghị cấp phát + trình ký
            Route::post('requestStore', 'requestStore')->name('requestStore');
            Route::post('requestUpdate', 'requestUpdate')->name('requestUpdate');
            Route::post('requestSubmit', 'requestSubmit')->name('requestSubmit');
            Route::post('requestSign', 'requestSign')->name('requestSign');
            Route::post('requestReject', 'requestReject')->name('requestReject');
            Route::post('requestDestroy', 'requestDestroy')->name('requestDestroy');

            // Cấp phát của kho (xuất kho = đem sử dụng)
            Route::post('issueStore', 'issueStore')->name('issueStore');
            Route::post('issueReject', 'issueReject')->name('issueReject');

            // Đề nghị chuyển vật tư LIÊN PHÒNG BAN: A đề nghị -> B cấp phát (trừ tồn B) -> A nhận (tạo tồn A)
            // Danh mục + tồn kho của phòng được đề nghị, trả JSON cho picker chọn nhiều vật tư
            Route::get('transferDepartmentStock', 'transferDepartmentStock')->name('transferDepartmentStock');
            Route::post('transferRequestStore', 'transferRequestStore')->name('transferRequestStore');
            Route::post('transferRequestUpdate', 'transferRequestUpdate')->name('transferRequestUpdate');
            Route::post('transferRequestSend', 'transferRequestSend')->name('transferRequestSend');
            Route::post('transferRequestDestroy', 'transferRequestDestroy')->name('transferRequestDestroy');
            Route::post('transferIssueStore', 'transferIssueStore')->name('transferIssueStore');
            Route::post('transferRequestReject', 'transferRequestReject')->name('transferRequestReject');
            Route::post('transferReceiveStore', 'transferReceiveStore')->name('transferReceiveStore');
            Route::post('transferReceiveReject', 'transferReceiveReject')->name('transferReceiveReject');
            // Ký duyệt đề nghị liên phòng ban / VPP theo quy trình ở Dữ Liệu Gốc > Trình Ký Đề Nghị Liên Phòng Ban
            Route::post('transferSign', 'transferSign')->name('transferSign');
            Route::post('transferSignReject', 'transferSignReject')->name('transferSignReject');
        });

        /*
        | HIỆU CHUẨN TRƯỚC KHI CẤP PHÁT - vật tư "Cần Hiệu Chuẩn Trước Khi Dùng": kho đề nghị hiệu chuẩn
        | (giữ chỗ tồn) -> QA ký xác nhận Đạt / Không đạt -> phần đạt mới cấp phát được. Tab "Hiệu chuẩn"
        | của Sử Dụng Vật Tư, dữ liệu do MaterialExportController::index() nạp sẵn.
        */
        Route::prefix('/materialCalibration')->name('materialCalibration.')->controller(MaterialCalibrationController::class)->group(function () {
            Route::post('store', 'store')->name('store');
            Route::post('confirm', 'confirm')->name('confirm');
            Route::post('cancel', 'cancel')->name('cancel');
        });

        /*
        | LOẠI BỎ VẬT TƯ HỎNG - 3 bước: Cách ly chờ quyết định (trả về kho được) -> Loại bỏ
        | (trừ tồn, không quay lại kho) -> Huỷ. Nằm trong tab "Cách ly - Loại Bỏ - Huỷ" của Sử Dụng
        | Vật Tư, dữ liệu do MaterialExportController::index() nạp sẵn.
        */
        Route::prefix('/materialQuarantine')->name('materialQuarantine.')->controller(MaterialQuarantineController::class)->group(function () {
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('decide', 'decide')->name('decide');
            Route::post('dispose', 'dispose')->name('dispose');
        });
    });
