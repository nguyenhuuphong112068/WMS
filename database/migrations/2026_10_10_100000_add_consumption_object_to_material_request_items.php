<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỐI TƯỢNG SỬ DỤNG CỦA TỪNG DÒNG ĐỀ NGHỊ CẤP PHÁT VẬT TƯ
 *
 * Người lập đề nghị khai vật tư của dòng này cấp cho Đối Tượng nào (dữ liệu gốc
 * consumption_objects: thiết bị, máy móc...). Là nguồn dữ liệu cho trang Thống Kê Cấp Phát
 * Vật Tư: trong một khoảng thời gian một đối tượng đã tiêu thụ những vật tư nào, bao nhiêu,
 * cho mục đích gì. Bỏ trống = chưa gắn đối tượng.
 *
 * Phiếu cũ sinh tự động từ danh sách đề nghị theo chu kỳ đã biết đối tượng (gắn ở danh
 * sách) nên điền bù luôn cho các dòng của phiếu đó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            if (! Schema::hasColumn('material_request_items', 'consumption_object_id')) {
                $table->unsignedBigInteger('consumption_object_id')->nullable()->after('purpose'); // -> consumption_objects.id
                $table->index('consumption_object_id', 'material_request_items_consumption_object_id_index');
            }
        });

        $this->backfillPeriodic();
    }

    /**
     * Phiếu sinh tự động không lưu id danh sách gốc, chỉ ghi chú dạng
     * 'Tạo tự động từ danh sách đề nghị theo chu kỳ "<tiêu đề>" ...'. Đọc lại tiêu đề đó và
     * chỉ điền khi khớp ĐÚNG MỘT danh sách nội bộ cùng phòng có gắn đối tượng.
     */
    private function backfillPeriodic(): void
    {
        if (! Schema::hasTable('material_periodic_request_list')) {
            return;
        }

        $requests = DB::table('material_request_lists')
            ->where('type', 'periodic')
            ->where('note', 'like', 'Tạo tự động từ danh sách đề nghị theo chu kỳ "%')
            ->get(['id', 'department_id', 'note']);

        foreach ($requests as $req) {
            if (! preg_match('/theo chu kỳ "(.+?)"/u', (string) $req->note, $m)) {
                continue;
            }

            $objectIds = DB::table('material_periodic_request_list')
                ->where('department_id', $req->department_id)
                ->where('type', 'internal')
                ->where('title', $m[1])
                ->whereNotNull('consumption_object_id')
                ->pluck('consumption_object_id')
                ->unique();

            if ($objectIds->count() === 1) {
                DB::table('material_request_items')
                    ->where('request_list_id', $req->id)
                    ->whereNull('consumption_object_id')
                    ->update(['consumption_object_id' => $objectIds->first()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('material_request_items', 'consumption_object_id')) {
                $table->dropIndex('material_request_items_consumption_object_id_index');
                $table->dropColumn('consumption_object_id');
            }
        });
    }
};
