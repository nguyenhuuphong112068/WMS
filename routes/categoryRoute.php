<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: DANH MỤC (Category)
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/Category/...
| View       : resources/views/pages/category/...
| Tên route  : pages.category.<chứcNăng>.<action>
*/

use App\Http\Controllers\Pages\Category\ChemicalCategoryController;
use App\Http\Controllers\Pages\Category\DepartmentChemicalController;
use App\Http\Controllers\Pages\Category\DepartmentMaterialController;
use App\Http\Controllers\Pages\Category\DepartmentStandardController;
use App\Http\Controllers\Pages\Category\MaterialCategoryController;
use App\Http\Controllers\Pages\Category\StandardCategoryController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/category')
    ->name('pages.category.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        // Trang 2 tab: "Danh Mục Vật Tư Công Ty" + "Vật Tư Của Phòng"
        Route::prefix('/materialCategory')->name('materialCategory.')->controller(MaterialCategoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
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
        });

        // Trang 2 tab: "Danh Mục Hoá Chất Công Ty" + "Hoá Chất Của Phòng"
        Route::prefix('/chemicalCategory')->name('chemicalCategory.')->controller(ChemicalCategoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            // JSON cho modal "xem chi tiết" cột Ngưỡng Tồn Trữ PL IV: các dòng dữ liệu
            // (tồn hiện tại theo mã × phòng, diễn biến chứng từ tạo nên đỉnh)
            Route::get('thresholdDetail', 'thresholdDetail')->name('thresholdDetail');
            Route::get('convert', 'convert')->name('convert');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
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
            Route::post('reject', 'reject')->name('reject');
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
