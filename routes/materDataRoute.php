<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: DỮ LIỆU GỐC (Master Data)
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/MaterData/...
| View       : resources/views/pages/materData/...
| Tên route  : pages.materData.<chứcNăng>.<action>
*/

use App\Http\Controllers\Pages\MaterData\ChemManufacturerController;
use App\Http\Controllers\Pages\MaterData\ChemNameController;
use App\Http\Controllers\Pages\MaterData\ChemSupplierController;
use App\Http\Controllers\Pages\MaterData\DepartmentController;
use App\Http\Controllers\Pages\MaterData\MaterialNameController;
use App\Http\Controllers\Pages\MaterData\PackagingSpecificationController;
use App\Http\Controllers\Pages\MaterData\StatusController;
use App\Http\Controllers\Pages\MaterData\StorageConditionController;
use App\Http\Controllers\Pages\MaterData\UnitController;
use App\Http\Controllers\Pages\MaterData\ZoneController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/materData')
    ->name('pages.materData.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/department')->name('department.')->controller(DepartmentController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        Route::prefix('/status')->name('status.')->controller(StatusController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        // Định Khu: Kho - Phòng - Kệ - Vị Trí gộp chung một màn hình, {type} là cấp đang thao tác
        Route::prefix('/zone')->name('zone.')
            ->controller(ZoneController::class)
            ->where(['type' => 'warehouse|room|shelf|location'])
            ->group(function () {
                Route::get('', 'index')->name('list');
                Route::post('{type}/store', 'store')->name('store');
                Route::post('{type}/update', 'update')->name('update');
                Route::post('{type}/deActive', 'deActive')->name('deActive');
                Route::post('{type}/destroy', 'destroy')->name('destroy');
            });

        Route::prefix('/chemName')->name('chemName.')->controller(ChemNameController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/materialName')->name('materialName.')->controller(MaterialNameController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/chemManufacturer')->name('chemManufacturer.')->controller(ChemManufacturerController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/chemSupplier')->name('chemSupplier.')->controller(ChemSupplierController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/packagingSpecification')->name('packagingSpecification.')->controller(PackagingSpecificationController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/unit')->name('unit.')->controller(UnitController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/storageCondition')->name('storageCondition.')->controller(StorageConditionController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });
    });
