<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ngày hẹn đáp ứng tách theo từng tháng dự trù: mục có số lượng của m tháng thì có m ngày hẹn.
 * Cột promised_date ở bảng mục vẫn giữ, luôn bằng ngày hẹn sớm nhất của các tháng
 * (tab Theo dõi dự trù lọc / sắp xếp theo cột này). Dữ liệu cũ: chép ngày hẹn của mục xuống mọi tháng.
 */
return new class extends Migration
{
    private const TABLES = [
        'material_estimate_item_amounts' => ['material_estimate_items', 'material_estimate_item_id'],
        'chemical_estimate_item_amounts' => ['chemical_estimate_items', 'estimate_item_id'],
        'standard_estimate_item_amounts' => ['standard_estimate_items', 'standard_estimate_item_id'],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $amountTable => [$itemTable, $fk]) {
            if (! Schema::hasColumn($amountTable, 'promised_date')) {
                Schema::table($amountTable, function (Blueprint $table) {
                    $table->date('promised_date')->nullable()->after('for_month_year');
                });
            }

            DB::table($itemTable)->whereNotNull('promised_date')->orderBy('id')
                ->each(function ($item) use ($amountTable, $fk) {
                    DB::table($amountTable)->where($fk, $item->id)->whereNull('promised_date')
                        ->update(['promised_date' => $item->promised_date]);
                });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $amountTable) {
            if (Schema::hasColumn($amountTable, 'promised_date')) {
                Schema::table($amountTable, fn (Blueprint $table) => $table->dropColumn('promised_date'));
            }
        }
    }
};
