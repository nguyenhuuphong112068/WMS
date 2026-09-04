<?php

/*
|--------------------------------------------------------------------------
| ROUTE CHUNG TOÀN HỆ THỐNG - ĐĂNG NHẬP, TRANG CHỦ, IMPORT
|--------------------------------------------------------------------------
| Các route không thuộc nhóm chức năng nào trên leftNAV.
*/

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\General\HomeController;
use App\Http\Controllers\General\PublicChemicalLookupController;
use App\Http\Controllers\General\PrintTestController;
use App\Http\Controllers\General\SwitchProductionController;
use App\Http\Controllers\UploadDataController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'showLogin']);

// Cho phép mở trực tiếp /login - cùng trả về form đăng nhập ở '/'
Route::get('/login', [LoginController::class, 'showLogin'])->name('login.form');

Route::post('/', [LoginController::class, 'login'])->name('login');

// Tra cứu tồn kho + vị trí hoá chất của một phòng ban - CÔNG KHAI, không cần đăng nhập
Route::get('/tra-cuu-hoa-chat', [PublicChemicalLookupController::class, 'index'])->name('publicChemicalLookup');

Route::post('/changePassword', [LoginController::class, 'changePassword'])->name('changePassword');

Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/home', [HomeController::class, 'showHomeForm'])->name('pages.home')->middleware(CheckLogin::class);

Route::get('/switch', [SwitchProductionController::class, 'switchProduction'])->name('switch')->middleware(CheckLogin::class);

// Import dữ liệu từ file Excel (dùng chung)
Route::get('/upload', [UploadDataController::class, 'index'])->name('upload.form_load');

Route::post('/import', [UploadDataController::class, 'import'])->name('upload.import');

Route::post('/import_permission', [UploadDataController::class, 'import_permission'])->name('upload.import_permission');

// Kiểm tra in nhãn qua Zebra Browser Print (trang chẩn đoán, mở trực tiếp trên máy trạm có máy in)
Route::get('/print-test', [PrintTestController::class, 'index'])->name('printTest')->middleware(CheckLogin::class);
