<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standard_imports', function (Blueprint $table) {
            $table->string('potency', 100)->nullable()->after('coa_no');               // Hàm lượng (ví dụ: 99.5%, 1000 ug/ml)
            $table->string('moisture', 100)->nullable()->after('potency');             // Độ ẩm (ví dụ: 0.3%)
            $table->string('expiry_type', 30)->default('defined')->after('expired_date'); // defined, undetermined, retest
            $table->smallInteger('retest_interval_months')->nullable()->after('expiry_type'); // Khoảng thời gian retest (tháng)
            $table->tinyInteger('weight_controlled')->default(0)->after('retest_interval_months'); // Chuẩn có cần kiểm soát khối lượng (1/0)
            $table->string('standard_form', 50)->nullable()->after('weight_controlled'); // Dạng chuẩn (Bột, Dung dịch, Viên, Khí, Khác)
            $table->tinyInteger('requires_aliquot')->default(0)->after('standard_form'); // Chuẩn cần chiết ống trước khi sử dụng (1/0)
        });

        Schema::table('standard_import_histories', function (Blueprint $table) {
            $table->string('potency', 100)->nullable()->after('coa_no');
            $table->string('moisture', 100)->nullable()->after('potency');
            $table->string('expiry_type', 30)->default('defined')->after('expired_date');
            $table->smallInteger('retest_interval_months')->nullable()->after('expiry_type');
            $table->tinyInteger('weight_controlled')->default(0)->after('retest_interval_months');
            $table->string('standard_form', 50)->nullable()->after('weight_controlled');
            $table->tinyInteger('requires_aliquot')->default(0)->after('standard_form');
        });
    }

    public function down(): void
    {
        Schema::table('standard_imports', function (Blueprint $table) {
            $table->dropColumn([
                'potency',
                'moisture',
                'expiry_type',
                'retest_interval_months',
                'weight_controlled',
                'standard_form',
                'requires_aliquot',
            ]);
        });

        Schema::table('standard_import_histories', function (Blueprint $table) {
            $table->dropColumn([
                'potency',
                'moisture',
                'expiry_type',
                'retest_interval_months',
                'weight_controlled',
                'standard_form',
                'requires_aliquot',
            ]);
        });
    }
};
