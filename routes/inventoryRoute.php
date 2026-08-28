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
*/

use App\Http\Controllers\Pages\Inventory\ChemicalInventoryController;
use App\Http\Controllers\Pages\Inventory\MaterialInventoryController;
use App\Http\Controllers\Pages\Inventory\StandardInventoryController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/inventory')
    ->name('pages.inventory.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/chemicalInventory')->name('chemicalInventory.')->controller(ChemicalInventoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('balancing', 'balancing')->name('balancing');
            Route::post('internalExpiry', 'internalExpiry')->name('internalExpiry');
        });

        Route::prefix('/standardInventory')->name('standardInventory.')->controller(StandardInventoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('balancing', 'balancing')->name('balancing');
            Route::post('internalExpiry', 'internalExpiry')->name('internalExpiry');
            Route::post('weightRemark', 'weightRemark')->name('weightRemark');
        });

        Route::prefix('/materialInventory')->name('materialInventory.')->controller(MaterialInventoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('balancing', 'balancing')->name('balancing');
        });
    });
