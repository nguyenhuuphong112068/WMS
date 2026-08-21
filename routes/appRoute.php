<?php

/*
|--------------------------------------------------------------------------
| ROUTE CHUNG TOÀN HỆ THỐNG - ĐĂNG NHẬP, TRANG CHỦ, IMPORT
|--------------------------------------------------------------------------
| Các route không thuộc nhóm chức năng nào trên leftNAV.
*/

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\General\HomeController;
use App\Http\Controllers\General\SwitchProductionController;
use App\Http\Controllers\UploadDataController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'showLogin']);

Route::post('/', [LoginController::class, 'login'])->name('login');

Route::post('/changePassword', [LoginController::class, 'changePassword'])->name('changePassword');

Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/home', [HomeController::class, 'showHomeForm'])->name('pages.home')->middleware(CheckLogin::class);

Route::get('/switch', [SwitchProductionController::class, 'switchProduction'])->name('switch')->middleware(CheckLogin::class);

// Import dữ liệu từ file Excel (dùng chung)
Route::get('/upload', [UploadDataController::class, 'index'])->name('upload.form_load');

Route::post('/import', [UploadDataController::class, 'import'])->name('upload.import');

Route::post('/import_permission', [UploadDataController::class, 'import_permission'])->name('upload.import_permission');
