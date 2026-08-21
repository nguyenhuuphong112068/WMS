<?php

/*
|--------------------------------------------------------------------------
| Khai báo route theo nhóm chức năng trên leftNAV
|--------------------------------------------------------------------------
| app          : đăng nhập, trang chủ, import (không thuộc nhóm menu nào)
| materData    : nhóm menu "Dữ Liệu Gốc"
| category     : nhóm menu "Danh Mục"
| User         : nhóm menu "Phân Quyền"
| AuditTrial   : nhóm menu "Audit Trail"
| Notification / Chat : tiện ích dùng chung trên topNAV
*/

require __DIR__.'/appRoute.php';
require __DIR__.'/materDataRoute.php';
require __DIR__.'/categoryRoute.php';
require __DIR__.'/UserRoute.php';
require __DIR__.'/AuditTrialRoute.php';
require __DIR__.'/NotificationRoute.php';
require __DIR__.'/ChatRoute.php';
