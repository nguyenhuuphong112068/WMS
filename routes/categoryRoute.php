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
use App\Http\Controllers\Pages\Category\MaterialCategoryController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/category')
    ->name('pages.category.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/materialCategory')->name('materialCategory.')->controller(MaterialCategoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        // Trang 2 tab: "Danh Mục Hoá Chất Công Ty" + "Hoá Chất Của Phòng"
        Route::prefix('/chemicalCategory')->name('chemicalCategory.')->controller(ChemicalCategoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
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
    });
