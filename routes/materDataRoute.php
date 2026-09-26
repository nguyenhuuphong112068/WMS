<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: DỮ LIỆU GỐC (Master Data)
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/MaterData/...
| View       : resources/views/pages/materData/...
| Tên route  : pages.materData.<chứcNăng>.<action>
*/

use App\Http\Controllers\Pages\MaterData\ActiveIngredientController;
use App\Http\Controllers\Pages\MaterData\ChemManufacturerController;
use App\Http\Controllers\Pages\MaterData\ChemNameController;
use App\Http\Controllers\Pages\MaterData\ChemSupplierController;
use App\Http\Controllers\Pages\MaterData\CompanyController;
use App\Http\Controllers\Pages\MaterData\ConsumptionObjectController;
use App\Http\Controllers\Pages\MaterData\DepartmentClassificationController;
use App\Http\Controllers\Pages\MaterData\DepartmentController;
use App\Http\Controllers\Pages\MaterData\GroupController;
use App\Http\Controllers\Pages\MaterData\MaterialNameController;
use App\Http\Controllers\Pages\MaterData\MaterialSignFlowController;
use App\Http\Controllers\Pages\MaterData\MixtureHazardCategoryController;
use App\Http\Controllers\Pages\MaterData\PackagingSpecificationController;
use App\Http\Controllers\Pages\MaterData\ProductNameController;
use App\Http\Controllers\Pages\MaterData\PurposeController;
use App\Http\Controllers\Pages\MaterData\StatusController;
use App\Http\Controllers\Pages\MaterData\StorageConditionController;
use App\Http\Controllers\Pages\MaterData\UnitController;
use App\Http\Controllers\Pages\MaterData\ZoneController;
use App\Http\Controllers\Pages\MaterData\ZoneStructureController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/materData')
    ->name('pages.materData.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/purpose')->name('purpose.')->controller(PurposeController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        // Công Ty: phần mềm chạy cho nhiều công ty, mỗi công ty có bộ phòng ban riêng.
        // Phạm vi đối chiếu "Ngưỡng Tồn Trữ PL IV" gói trong từng công ty.
        Route::prefix('/company')->name('company.')->controller(CompanyController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        Route::prefix('/department')->name('department.')->controller(DepartmentController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        Route::prefix('/group')->name('group.')->controller(GroupController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        Route::prefix('/productName')->name('productName.')->controller(ProductNameController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        Route::prefix('/status')->name('status.')->controller(StatusController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        // Định Khu - Cấu trúc kho: khai Kệ/Tủ -> Cột -> Tầng -> Vị Trí trên một lưới (API cho màn Định Khu)
        Route::prefix('/zone/structure')->name('zone.structure.')
            ->controller(ZoneStructureController::class)
            ->group(function () {
                Route::get('warehouses', 'warehouses')->name('warehouses');
                Route::post('warehouse', 'saveWarehouse')->name('saveWarehouse');
                Route::get('shelves', 'shelves')->name('shelves');
                Route::get('detail', 'detail')->name('detail');
                Route::get('checkCode', 'checkCode')->name('checkCode');
                Route::post('apply', 'apply')->name('apply');
            });

        // Định Khu: Kho/Phòng - Kệ/Tủ - Cột - Tầng - Vị Trí gộp chung một màn hình, {type} là cấp đang thao tác
        Route::prefix('/zone')->name('zone.')
            ->controller(ZoneController::class)
            ->where(['type' => 'warehouse|shelf|column|tier|location'])
            ->group(function () {
                Route::get('', 'index')->name('list');
                Route::get('{type}/history', 'history')->name('history');
                Route::post('{type}/store', 'store')->name('store');
                Route::post('{type}/update', 'update')->name('update');
                Route::post('{type}/deActive', 'deActive')->name('deActive');
                Route::post('{type}/destroy', 'destroy')->name('destroy');
            });

        Route::prefix('/chemName')->name('chemName.')->controller(ChemNameController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        // Tên Hoạt Chất - danh mục hoạt chất phải xây dựng Kế hoạch phòng ngừa, ứng phó
        // sự cố hoá chất (Phụ lục IV NĐ 24/2026/NĐ-CP), kèm ngưỡng khối lượng tồn trữ.
        Route::prefix('/activeIngredient')->name('activeIngredient.')->controller(ActiveIngredientController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        // Nhóm Nguy Hại Bảng B - phân loại GHS + ngưỡng tồn trữ cho hỗn hợp
        // (Phụ lục IV NĐ 24/2026/NĐ-CP - Bảng B).
        Route::prefix('/mixtureHazardCategory')->name('mixtureHazardCategory.')->controller(MixtureHazardCategoryController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/standardName')->name('standardName.')->controller(\App\Http\Controllers\Pages\MaterData\StandardNameController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/materialName')->name('materialName.')->controller(MaterialNameController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        // Phân loại của phòng: mỗi phòng ban tự khai bộ nhóm của phòng mình, dùng khi
        // khai "Vật Tư Của Phòng". Màn hình làm việc trên phòng ban đang chọn.
        Route::prefix('/departmentClassification')->name('departmentClassification.')->controller(DepartmentClassificationController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        // Trình ký đề nghị cấp phát vật tư: phòng khai trước quy trình ký chuẩn ứng với
        // từng tổ hợp phân loại (danh mục chung + danh mục của phòng), mỗi bước một vai trò.
        Route::prefix('/materialSignFlow')->name('materialSignFlow.')->controller(MaterialSignFlowController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        Route::prefix('/chemManufacturer')->name('chemManufacturer.')->controller(ChemManufacturerController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/chemSupplier')->name('chemSupplier.')->controller(ChemSupplierController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/packagingSpecification')->name('packagingSpecification.')->controller(PackagingSpecificationController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        Route::prefix('/unit')->name('unit.')->controller(UnitController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        // Đối Tượng: đối tượng tiêu thụ vật tư; loại thiết bị đồng bộ từ phần mềm CAL (cal1/cal2)
        Route::prefix('/consumptionObject')->name('consumptionObject.')->controller(ConsumptionObjectController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('sync', 'sync')->name('sync');
        });

        Route::prefix('/storageCondition')->name('storageCondition.')->controller(StorageConditionController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('history', 'history')->name('history');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });
    });
