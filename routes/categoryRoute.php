<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: DANH MỤC (Category)
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Category/...
| View       : resources/views/pages/category/...
| Tên route  : pages.category.<chứcNăng>.<action>
*/

use App\Http\Controllers\Pages\Category\CategoryLookupController;
use App\Http\Controllers\Pages\Category\ChemicalCategoryController;
use App\Http\Controllers\Pages\Category\DepartmentChemicalController;
use App\Http\Controllers\Pages\Category\DepartmentMaterialController;
use App\Http\Controllers\Pages\Category\DepartmentStandardController;
use App\Http\Controllers\Pages\Category\MaterialCategoryController;
use App\Http\Controllers\Pages\Category\PeriodicRequestController;
use App\Http\Controllers\Pages\Category\StandardCategoryController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/category')
    ->name('pages.category.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        // Tra cứu dữ liệu gốc qua AJAX cho ô chọn Select2 của 3 trang Danh Mục (tên vật tư / hoá chất /
        // chất chuẩn, định khu) - không nhúng cả danh mục vào trang, xem App\Support\CategoryLookup.
        Route::prefix('/lookup')->name('lookup.')->controller(CategoryLookupController::class)->group(function () {
            Route::get('names', 'names')->name('names');
            Route::get('locations', 'locations')->name('locations');
            Route::get('chemicalNames', 'chemicalNames')->name('chemicalNames');
            // Tương kỵ khi đặt hoá chất vào định khu (Sơ đồ lưu trữ GHS) - modal Khai Hoá Chất Cho Phòng
            Route::get('chemicalCompatibility', 'chemicalCompatibility')->name('chemicalCompatibility');
        });

        // Trang 2 tab: "Danh Mục Vật Tư Công Ty" + "Vật Tư Của Phòng"
        Route::prefix('/materialCategory')->name('materialCategory.')->controller(MaterialCategoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
        });

        // Cấu hình riêng của từng phòng ban cho vật tư dùng chung - không có bước duyệt,
        // phòng nào tự khai của phòng đó.
        //
        // Không có route hiển thị: nội dung nằm ở tab "Vật Tư Của Phòng" của trang
        // /category/materialCategory, các thao tác bên dưới gọi từ chính tab đó.
        Route::prefix('/departmentMaterial')->name('departmentMaterial.')->controller(DepartmentMaterialController::class)->group(function () {
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            // "Đề nghị dự trù vật tư" từ tab Vật Tư Của Phòng - hiện ở tab Dự Trù Vật Tư
            Route::post('watchlistRemember', 'watchlistRemember')->name('watchlistRemember');
        });

        // Danh sách vật tư đề nghị theo chu kỳ (nội bộ / liên phòng ban) - đến chu kỳ tự tạo
        // đề nghị Lưu tạm. Không có route hiển thị: nằm ở 2 tab của /category/materialCategory.
        Route::prefix('/periodicRequest')->name('periodicRequest.')->controller(PeriodicRequestController::class)->group(function () {
            Route::get('history', 'history')->name('history');
            // Tìm đối tượng (dữ liệu gốc) qua AJAX cho modal "Dữ Liệu Gốc - Đối Tượng" - không
            // nhúng cả danh mục vào trang, xem MaterialPeriodicRequest::objectSearch()
            Route::get('objects', 'objects')->name('objects');
            // Lịch Pending bên CAL của đối tượng + ngày tạo sẽ theo - cho tuỳ chọn "Theo ngày đến hạn
            // lịch CAL" ở modal thêm / sửa danh sách nội bộ, xem MaterialPeriodicRequest::calSchedulePreview()
            Route::get('calSchedule', 'calSchedule')->name('calSchedule');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('generateNow', 'generateNow')->name('generateNow');
        });

        // Trang 2 tab: "Danh Mục Hoá Chất Công Ty" + "Hoá Chất Của Phòng"
        Route::prefix('/chemicalCategory')->name('chemicalCategory.')->controller(ChemicalCategoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            // JSON cho modal "xem chi tiết" cột Ngưỡng Tồn Trữ PL IV: các dòng dữ liệu
            // (tồn hiện tại theo mã × phòng, diễn biến chứng từ tạo nên đỉnh)
            Route::get('thresholdDetail', 'thresholdDetail')->name('thresholdDetail');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            // Xác nhận "Đã khai báo trên Cổng thông tin quốc gia" - chỉ một lần, không gỡ lại được
            Route::post('declarePortal', 'declarePortal')->name('declarePortal');
        });

        // Cấu hình riêng của từng phòng ban cho hoá chất dùng chung - không có bước duyệt,
        // phòng nào tự khai của phòng đó.
        //
        // Không có route hiển thị: nội dung nằm ở tab "Hoá Chất Của Phòng" của trang
        // /category/chemicalCategory, các thao tác bên dưới gọi từ chính tab đó.
        Route::prefix('/departmentChemical')->name('departmentChemical.')->controller(DepartmentChemicalController::class)->group(function () {
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        // Trang 2 tab: "Danh Mục Chất Chuẩn Công Ty" + "Chất Chuẩn Của Phòng"
        Route::prefix('/standardCategory')->name('standardCategory.')->controller(StandardCategoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
        });

        // Cấu hình riêng của từng phòng ban cho chất chuẩn dùng chung - không có bước duyệt.
        //
        // Không có route hiển thị: nội dung nằm ở tab "Chất Chuẩn Của Phòng" của trang
        // /category/standardCategory, các thao tác bên dưới gọi từ chính tab đó.
        Route::prefix('/departmentStandard')->name('departmentStandard.')->controller(DepartmentStandardController::class)->group(function () {
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
    });
