<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: AUDIT TRAIL
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/AuditTrail/...
| View       : resources/views/pages/auditTrail/...
| Tên route  : pages.auditTrail.<action>
*/

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::get('/auditTrail', [AuditTrialController::class, 'index'])
    ->name('pages.auditTrail.list')
    ->middleware(CheckLogin::class);
