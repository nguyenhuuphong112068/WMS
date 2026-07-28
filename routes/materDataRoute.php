<?php
use App\Http\Controllers\Pages\MaterData\DosageController;
use App\Http\Controllers\Pages\MaterData\InstrumentController;
use App\Http\Controllers\Pages\MaterData\MarketController;
use App\Http\Controllers\Pages\MaterData\ProductNameController;
use App\Http\Controllers\Pages\StorageLocation\RoomController;
use App\Http\Controllers\Pages\MaterData\SourceMaterialController;
use App\Http\Controllers\Pages\MaterData\SpecificationController;
use App\Http\Controllers\Pages\MaterData\UnitController;
use App\Http\Controllers\Pages\MaterData\OffDaysController;
use App\Http\Controllers\Pages\MaterData\StageGroupController;
use App\Http\Controllers\Pages\MaterData\DepartmentController;
use App\Http\Controllers\Pages\MaterData\StatusController;
use App\Http\Controllers\Pages\MaterData\DocumentTypeController;
use App\Http\Controllers\Pages\StorageLocation\WarehouseController;
use App\Http\Controllers\Pages\StorageLocation\ShelfController;
use App\Http\Controllers\Pages\StorageLocation\LocationController;
use App\Http\Controllers\Pages\DocumentStorage\DocumentController;
use App\Http\Controllers\Pages\DocumentStorage\DocumentRoutingController;
use App\Http\Controllers\Pages\DocumentStorage\DocumentReissueController;
use App\Http\Controllers\UploadDataController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::get('/upload', [UploadDataController::class, 'index'])->name('upload.form_load');
Route::POST('/import', [UploadDataController::class, 'import'])->name('upload.import');
Route::POST('/import_permission', [UploadDataController::class, 'import_permission'])->name('upload.import_permission');

// 1. Dữ Liệu Gốc (Master Data)
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
        Route::prefix('/documentType')->name('documentType.')->controller(DocumentTypeController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        
        // Other Master Data
        Route::prefix('/productName')->name('productName.')->controller(ProductNameController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
        });
        Route::prefix('/Dosage')->name('Dosage.')->controller(DosageController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
        });
        Route::prefix('/Unit')->name('Unit.')->controller(UnitController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
        });
    });

// 2. Vị Trí Lưu Trữ (Storage Location)
Route::prefix('/storageLocation')
    ->name('pages.storageLocation.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::prefix('/warehouse')->name('warehouse.')->controller(WarehouseController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/room')->name('room.')->controller(RoomController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/shelf')->name('shelf.')->controller(ShelfController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/location')->name('location.')->controller(LocationController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
    });

// 3. Quản Lý Tài Liệu (Document Storage)
Route::prefix('/documentStorage')
    ->name('pages.documentStorage.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::prefix('/document')->name('document.')->controller(DocumentController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

        // Theo dõi đường đi của hồ sơ (Luân chuyển)
        Route::prefix('/routing')->name('routing.')->controller(DocumentRoutingController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');                   // Bước 1: Văn thư khởi tạo
            Route::post('forward', 'forward')->name('forward');             // Bước 2: Chuyển tiếp
            Route::post('confirmReceive', 'confirmReceive')->name('confirmReceive');
            Route::post('finish', 'finish')->name('finish');                // Bước 3: Kết thúc & lưu trữ
            Route::post('cancel', 'cancel')->name('cancel');
        });

        // Sổ xin cấp lại hồ sơ (BMR/BPR)
        Route::prefix('/reissue')->name('reissue.')->controller(DocumentReissueController::class)->group(function () {
            Route::get('', 'index')->name('list');

            // Bước 1: Người xin ghi sổ -> Admin, Production_Manager, Người đề nghị
            Route::post('store', 'store')->name('store')
                ->middleware('role:Admin,Production_Manager,Người đề nghị');
            Route::post('update', 'update')->name('update')
                ->middleware('role:Admin,Production_Manager,Người đề nghị');
            Route::post('cancel', 'cancel')->name('cancel')
                ->middleware('role:Admin,Production_Manager,Người đề nghị');

            // Bước 2: QĐ/P.QĐ PXSX ký duyệt -> Admin, Production_Manager
            Route::post('pmSign', 'pmSign')->name('pmSign')
                ->middleware('role:Admin,Production_Manager');

            // Bước 3: Ý kiến TP/PP. ĐBCL (cho phép cấp lại hồ sơ) -> Admin, QA_Manager
            Route::post('qaReview', 'qaReview')->name('qaReview')
                ->middleware('role:Admin,QA_Manager');

            // Bước 4: Cấp lại hồ sơ & ký tên -> Admin, QA_Manager, Người cho lại hồ sơ
            Route::post('issue', 'issue')->name('issue')
                ->middleware('role:Admin,QA_Manager,Người cho lại hồ sơ');
        });
    });