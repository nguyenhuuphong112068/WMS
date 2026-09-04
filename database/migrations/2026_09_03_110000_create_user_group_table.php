<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Một user có thể ở nhiều tổ -> bảng trung gian user_group.
 * Chuyển dữ liệu group_id đơn hiện có (nếu có) sang bảng mới.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_group')) {
            Schema::create('user_group', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('group_id');
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('user_management', 'group_id')) {
            $rows = DB::table('user_management')
                ->whereNotNull('group_id')
                ->where('group_id', '>', 0)
                ->get(['id', 'group_id']);

            foreach ($rows as $row) {
                $exists = DB::table('user_group')
                    ->where('user_id', $row->id)
                    ->where('group_id', $row->group_id)
                    ->exists();

                if (!$exists) {
                    DB::table('user_group')->insert([
                        'user_id' => $row->id,
                        'group_id' => $row->group_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_group');
    }
};
