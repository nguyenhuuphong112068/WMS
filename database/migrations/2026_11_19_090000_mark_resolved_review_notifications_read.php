<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Đề nghị trang chủ đã xử lý (resolved_at) mà còn chưa đọc → đánh dấu đã đọc. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notification_recipients', 'resolved_at')) {
            return;
        }

        DB::table('notification_recipients')
            ->whereNotNull('resolved_at')
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => DB::raw('resolved_at')]);
    }

    public function down(): void
    {
    }
};
