<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bỏ cột "Thiết bị liên quan" (product_name) khỏi các model đề nghị vật tư - không còn dùng.
 * Bảng đề nghị theo chu kỳ có thể đã đổi tên sang tiền tố material_ nên dò cả 2 tên.
 */
return new class extends Migration
{
    private function periodicItemTable(): ?string
    {
        return match (true) {
            Schema::hasTable('material_periodic_request_item') => 'material_periodic_request_item',
            Schema::hasTable('periodic_request_item') => 'periodic_request_item',
            default => null,
        };
    }

    public function up(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('material_request_items', 'product_name')) {
                $table->dropColumn('product_name');
            }
        });

        if ($periodicTable = $this->periodicItemTable()) {
            Schema::table($periodicTable, function (Blueprint $table) use ($periodicTable) {
                if (Schema::hasColumn($periodicTable, 'product_name')) {
                    $table->dropColumn('product_name');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            if (! Schema::hasColumn('material_request_items', 'product_name')) {
                $table->string('product_name', 255)->nullable()->after('requested_unit');
            }
        });

        if ($periodicTable = $this->periodicItemTable()) {
            Schema::table($periodicTable, function (Blueprint $table) use ($periodicTable) {
                if (! Schema::hasColumn($periodicTable, 'product_name')) {
                    $table->string('product_name', 255)->nullable()->after('category_id');
                }
            });
        }
    }
};
