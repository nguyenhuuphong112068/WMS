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
    });
