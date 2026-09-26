<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỊNH KHU - THỨ TỰ TRONG CẤP CHA (position)
 *
 * Màn "Cấu trúc kho" vẽ mỗi kệ thành lưới: khối = Cột, hàng = Tầng, ô = Vị trí.
 * Muốn vẽ và co giãn được lưới thì mỗi cột/tầng/vị trí phải biết mình đứng thứ mấy
 * trong cấp cha, nên thêm cột `position` cho ba bảng columns / tiers / locations.
 *
 * Dữ liệu cũ được điền sẵn: lấy phần số cuối của mã (01/01/01/02 -> 2) nếu không trùng
 * trong cùng cấp cha, còn lại xếp nối tiếp theo thứ tự mã. Bản ghi chưa gắn cấp cha
 * (vị trí chưa thuộc tầng nào...) để null - lưới bỏ qua, vẫn sửa được ở tab danh sách.
 */
return new class extends Migration
{
    private const LEVELS = [
        'columns' => 'shelf_id',
        'tiers' => 'column_id',
        'locations' => 'tier_id',
    ];

    public function up(): void
    {
        foreach (self::LEVELS as $table => $parent) {
            if (! Schema::hasColumn($table, 'position')) {
                Schema::table($table, function (Blueprint $blueprint) use ($parent) {
                    $blueprint->unsignedInteger('position')->nullable()->after($parent);
                });
            }

            $this->backfill($table, $parent);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::LEVELS) as $table) {
            if (Schema::hasColumn($table, 'position')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('position');
                });
            }
        }
    }

    private function backfill(string $table, string $parent): void
    {
        $rows = DB::table($table)
            ->whereNotNull($parent)
            ->whereNull('position')
            ->orderBy($parent)
            ->orderBy('code')
            ->get(['id', 'code', $parent]);

        foreach ($rows->groupBy($parent) as $parentId => $children) {
            $taken = DB::table($table)
                ->where($parent, $parentId)
                ->whereNotNull('position')
                ->pluck('position')
                ->map(fn ($p) => (int) $p)
                ->all();
            $taken = array_flip($taken);
            $pending = [];

            foreach ($children as $row) {
                $position = preg_match('/(\d+)\s*$/', (string) $row->code, $m) ? (int) $m[1] : 0;

                if ($position > 0 && ! isset($taken[$position])) {
                    $taken[$position] = true;
                    DB::table($table)->where('id', $row->id)->update(['position' => $position]);
                } else {
                    $pending[] = $row->id;
                }
            }

            $next = $taken ? max(array_keys($taken)) : 0;
            foreach ($pending as $id) {
                DB::table($table)->where('id', $id)->update(['position' => ++$next]);
            }
        }
    }
};
