<?php

use App\Support\MaterialPeriodicRequest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Tạo đề nghị cấp phát vật tư từ các danh sách theo chu kỳ đã tới hạn (toàn hệ thống).
| Máy chủ cần chạy `php artisan schedule:run` mỗi phút (Task Scheduler / cron). Không chạy
| scheduler thì trang Danh Mục Vật Tư / Sử Dụng Vật Tư vẫn tự bù khi có người mở.
*/
Artisan::command('material:periodic-requests', function () {
    $created = MaterialPeriodicRequest::generateDue();

    foreach ($created as $result) {
        $this->line('Đã tạo đề nghị '.$result['code'].' từ danh sách #'.$result['list_id']);
    }

    $this->info('Hoàn tất: '.count($created).' đề nghị được tạo.');
})->purpose('Tạo đề nghị vật tư từ danh sách theo chu kỳ đã tới hạn');

Schedule::command('material:periodic-requests')->dailyAt('00:05')->withoutOverlapping();
