<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỊNH KHU: ĐỔI TỪ 4 CẤP SANG 5 CẤP
 *
 *   Cũ:  Kho -> Phòng  -> Kệ/Tủ -> Vị Trí
 *   Mới: Kho -> Kệ/Tủ -> Cột    -> Tầng  -> Vị Trí
 *
 * - Bỏ hẳn cấp Phòng: drop bảng `rooms` và cột `room_id` của shelves / locations.
 * - Thêm hai cấp mới `columns` (Cột) và `tiers` (Tầng) nằm giữa Kệ/Tủ và Vị Trí.
 * - Vị trí đang có sẵn được gom vào "Cột 01 / Tầng 01" của chính kệ nó đang đứng,
 *   để sau khi đổi cấu trúc không ô nào rơi ra ngoài cây định khu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('columns')) {
            Schema::create('columns', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 255);
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->unsignedBigInteger('shelf_id')->nullable();
                $table->unsignedBigInteger('status_id')->nullable();
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tiers')) {
            Schema::create('tiers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 255);
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->unsignedBigInteger('shelf_id')->nullable();
                $table->unsignedBigInteger('column_id')->nullable();
                $table->unsignedBigInteger('status_id')->nullable();
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('locations', 'column_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->unsignedBigInteger('column_id')->nullable()->after('shelf_id');
                $table->unsignedBigInteger('tier_id')->nullable()->after('column_id');
            });
        }

        $this->fillDefaultColumnAndTier();

        foreach (['shelves', 'locations'] as $table) {
            if (Schema::hasColumn($table, 'room_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('room_id');
                });
            }
        }

        Schema::dropIfExists('rooms');
    }

    public function down(): void
    {
        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 255);
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->unsignedBigInteger('status_id')->nullable();
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });
        }

        foreach (['shelves', 'locations'] as $table) {
            if (! Schema::hasColumn($table, 'room_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('room_id')->nullable()->after('warehouse_id');
                });
            }
        }

        if (Schema::hasColumn('locations', 'column_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn(['column_id', 'tier_id']);
            });
        }

        Schema::dropIfExists('tiers');
        Schema::dropIfExists('columns');
    }

    /**
     * Mỗi kệ/tủ đang có vị trí được cấp sẵn một Cột 01 và một Tầng 01, rồi kéo toàn bộ
     * vị trí của kệ đó về hai cấp mới. Chạy lại nhiều lần vẫn an toàn nhờ dò theo `code`.
     */
    private function fillDefaultColumnAndTier(): void
    {
        $now = now();

        $shelves = DB::table('shelves')->orderBy('id')->get();

        foreach ($shelves as $shelf) {
            $hasLocation = DB::table('locations')->where('shelf_id', $shelf->id)->exists();

            if (! $hasLocation) {
                continue;
            }

            $columnCode = $this->code($shelf->code, 'C01');
            $columnId = $this->ensure('columns', $columnCode, [
                'name' => 'Cột 01',
                'department_id' => $shelf->department_id,
                'warehouse_id' => $shelf->warehouse_id,
                'shelf_id' => $shelf->id,
                'status_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $tierCode = $this->code($columnCode, 'T01');
            $tierId = $this->ensure('tiers', $tierCode, [
                'name' => 'Tầng 01',
                'department_id' => $shelf->department_id,
                'warehouse_id' => $shelf->warehouse_id,
                'shelf_id' => $shelf->id,
                'column_id' => $columnId,
                'status_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('locations')
                ->where('shelf_id', $shelf->id)
                ->whereNull('column_id')
                ->update(['column_id' => $columnId, 'tier_id' => $tierId]);
        }
    }

    /** Ghép hậu tố vào mã cấp trên, cắt bớt phần đầu nếu vượt quá 50 ký tự của cột code. */
    private function code(string $parent, string $suffix): string
    {
        $code = $parent.'-'.$suffix;

        return mb_strlen($code) <= 50 ? $code : mb_substr($parent, 0, 50 - mb_strlen($suffix) - 1).'-'.$suffix;
    }

    /** Tạo bản ghi nếu mã chưa có, trả về id để cấp dưới trỏ tới. */
    private function ensure(string $table, string $code, array $values): int
    {
        $id = DB::table($table)->where('code', $code)->value('id');

        if ($id) {
            return (int) $id;
        }

        return (int) DB::table($table)->insertGetId(['code' => $code] + $values);
    }
};
