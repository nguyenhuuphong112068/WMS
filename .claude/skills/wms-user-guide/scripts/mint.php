<?php
/*
 * Tạo một phiên đăng nhập (session database) cho Chrome headless chụp màn hình hướng dẫn,
 * KHÔNG qua form đăng nhập để không ghi Lịch Sử Đăng Nhập / Audit Trail.
 *
 *   php mint.php <userName|-> '<flash json>' [departmentId]
 *   -> {"name": "<cookie>", "value": "<giá trị cookie đã mã hoá>"}
 */
require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\CompanyContext;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$userName = $argv[1] ?? '-';
$flash = json_decode($argv[2] ?? '{}', true) ?: [];
$deptOverride = isset($argv[3]) && $argv[3] !== '' ? (int) $argv[3] : null;

$session = app('session')->driver();
$session->setId(Str::random(40));
$session->start();

if ($userName !== '-') {
    $user = DB::table('user_management')->where('userName', $userName)->first();
    $home = DB::table('deparments')->where('id', $user->deparment_id)->first();
    $selected = $deptOverride ? DB::table('deparments')->where('id', $deptOverride)->first() : $home;
    $companyId = CompanyContext::resolveForDepartment($selected->id ?? null);

    $session->put('user', [
        'userId' => $user->id,
        'userName' => $user->userName,
        'fullName' => $user->fullName,
        'userGroup' => DB::table('roles')->where('id', $user->role_id)->value('name'),
        'department' => $home->shortName ?? null,
        'department_id' => $home->id ?? null,
        'selected_department' => $selected->shortName ?? null,
        'selected_department_id' => $selected->id ?? null,
        'company_id' => $companyId,
        'company_name' => CompanyContext::name($companyId),
        'last_activity_ts' => time(),
    ]);
}

foreach ($flash as $key => $value) {
    $session->flash($key, $value);
}
$session->save();

$name = config('session.cookie');
$encrypter = app('encrypter');
echo json_encode([
    'name' => $name,
    'value' => $encrypter->encrypt(CookieValuePrefix::create($name, $encrypter->getKey()).$session->getId(), false),
]);
