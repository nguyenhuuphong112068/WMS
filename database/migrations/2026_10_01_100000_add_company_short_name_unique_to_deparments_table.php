<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mã phòng (shortName) chỉ cần duy nhất TRONG MỘT CÔNG TY.
 *
 * Phần mềm chạy cho nhiều công ty, mỗi công ty có bộ phòng ban riêng nên hai công ty
 * khác nhau được phép trùng mã phòng (VD: cùng có "QA"). Khoá duy nhất vì vậy là cặp
 * (company_id, shortName), không phải riêng shortName.
 */
return new class extends Migration
{
    private const INDEX = 'deparments_company_id_short_name_unique';

    public function up(): void
    {
        if (! Schema::hasTable('deparments') || ! Schema::hasColumn('deparments', 'company_id')) {
            return;
        }

        if ($this->hasIndex(self::INDEX)) {
            return;
        }

        $duplicates = DB::table('deparments')
            ->select('company_id', 'shortName', DB::raw('COUNT(*) as total'))
            ->groupBy('company_id', 'shortName')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $list = $duplicates
                ->map(fn ($row) => 'company_id=' . $row->company_id . ' / ' . $row->shortName)
                ->implode('; ');

            throw new RuntimeException(
                'Không tạo được khoá duy nhất (company_id, shortName) vì đang có mã phòng trùng trong cùng công ty: ' . $list
            );
        }

        Schema::table('deparments', function (Blueprint $table) {
            $table->unique(['company_id', 'shortName'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('deparments') && $this->hasIndex(self::INDEX)) {
            Schema::table('deparments', function (Blueprint $table) {
                $table->dropUnique(self::INDEX);
            });
        }
    }

    private function hasIndex(string $name): bool
    {
        return collect(DB::select('SHOW INDEX FROM deparments'))
            ->contains(fn ($row) => $row->Key_name === $name);
    }
};
