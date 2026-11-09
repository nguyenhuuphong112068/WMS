<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - LỊCH SỬ ĐIỀU CHỈNH CHỤP ĐỦ CỘT NHƯ BẢNG SỔ
 *
 * Modal "Lịch Sử Điều Chỉnh" hiển thị mỗi lần thay đổi thành 1 dòng với đúng các cột của
 * bảng sổ, nên ảnh chụp lịch sử phải có các giá trị sửa được còn thiếu:
 * - material_export_histories.purpose: Mục đích (nằm ở dòng đề nghị, sửa được ở modal Điều chỉnh).
 * - standard_export_histories.group_id: Tổ (sửa được ở modal Sửa).
 *
 * Dữ liệu cũ: lấy giá trị hiện tại rồi lùi dần theo các lần đổi ghi trong change_note
 * ("Mục đích: cũ -> mới", "Tổ: cũ -> mới") để mỗi ảnh chụp mang đúng giá trị lúc đó.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('material_export_histories', 'purpose')) {
            Schema::table('material_export_histories', function (Blueprint $table) {
                $table->string('purpose', 500)->nullable()->after('reason');
            });
        }

        if (! Schema::hasColumn('standard_export_histories', 'group_id')) {
            Schema::table('standard_export_histories', function (Blueprint $table) {
                $table->unsignedBigInteger('group_id')->nullable()->after('reason');
            });
        }

        $this->backfillMaterialPurpose();
        $this->backfillStandardGroup();
    }

    public function down(): void
    {
        Schema::table('material_export_histories', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });

        Schema::table('standard_export_histories', function (Blueprint $table) {
            $table->dropColumn('group_id');
        });
    }

    private function backfillMaterialPurpose(): void
    {
        $current = DB::table('material_exports')
            ->leftJoin('material_request_items', 'material_exports.request_item_id', '=', 'material_request_items.id')
            ->pluck('material_request_items.purpose', 'material_exports.id');

        DB::table('material_export_histories')
            ->orderBy('material_export_id')->orderBy('id', 'desc')
            ->get(['id', 'material_export_id', 'change_note'])
            ->groupBy('material_export_id')
            ->each(function ($rows, $exportId) use ($current) {
                $value = $current[$exportId] ?? null;

                foreach ($rows as $row) {
                    DB::table('material_export_histories')->where('id', $row->id)->update(['purpose' => $value]);

                    $old = $this->oldValue($row->change_note, 'Mục đích');
                    if ($old !== false) {
                        $value = $old;
                    }
                }
            });
    }

    private function backfillStandardGroup(): void
    {
        $current = DB::table('standard_exports')->pluck('group_id', 'id');
        $groupIds = DB::table('groups')->pluck('id', 'name');

        DB::table('standard_export_histories')
            ->orderBy('standard_export_id')->orderBy('id', 'desc')
            ->get(['id', 'standard_export_id', 'change_note'])
            ->groupBy('standard_export_id')
            ->each(function ($rows, $exportId) use ($current, $groupIds) {
                $value = $current[$exportId] ?? null;

                foreach ($rows as $row) {
                    DB::table('standard_export_histories')->where('id', $row->id)->update(['group_id' => $value]);

                    $old = $this->oldValue($row->change_note, 'Tổ');
                    if ($old !== false) {
                        $value = $old === null ? null : ($groupIds[$old] ?? null);
                    }
                }
            });
    }

    /** Giá trị TRƯỚC khi đổi của một trường trong change_note; false = lần đó không đổi trường này. */
    private function oldValue(?string $note, string $title)
    {
        foreach (explode(' | ', (string) $note) as $part) {
            if (str_starts_with($part, $title.': ') && str_contains($part, ' -> ')) {
                $old = trim(explode(' -> ', substr($part, strlen($title) + 2), 2)[0]);

                return $old === '—' || $old === '' ? null : $old;
            }
        }

        return false;
    }
};
