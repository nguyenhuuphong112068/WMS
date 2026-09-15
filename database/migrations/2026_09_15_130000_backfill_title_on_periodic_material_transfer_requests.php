<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Đề nghị liên phòng ban do danh sách theo chu kỳ tự sinh trước khi có cột title thì để trống
 * tiêu đề. Điền lại theo đúng dạng hiện tại "<tên danh sách> - kỳ dd/mm/yyyy": tên danh sách
 * lấy trong ghi chú cố định lúc sinh, ngày kỳ lấy theo ngày tạo phiếu.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('material_transfer_requests')
            ->whereNull('title')
            ->where('note', 'LIKE', 'Tạo tự động từ danh sách đề nghị theo chu kỳ "%')
            ->orderBy('id')
            ->get(['id', 'note', 'created_at'])
            ->each(function ($row) {
                if (! preg_match('/^Tạo tự động từ danh sách đề nghị theo chu kỳ "(.+)"\.?$/u', (string) $row->note, $m)) {
                    return;
                }

                DB::table('material_transfer_requests')->where('id', $row->id)->update([
                    'title' => Str::limit($m[1].' - kỳ '.date('d/m/Y', strtotime((string) $row->created_at)), 255, ''),
                ]);
            });
    }

    public function down(): void
    {
        // Chỉ điền dữ liệu, không có gì để hoàn tác
    }
};
