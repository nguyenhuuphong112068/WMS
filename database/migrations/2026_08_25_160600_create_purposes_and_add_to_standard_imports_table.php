<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purposes')) {
            Schema::create('purposes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255)->unique();
                $table->tinyInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('standard_imports', function (Blueprint $table) {
            if (!Schema::hasColumn('standard_imports', 'purpose_id')) {
                $table->unsignedBigInteger('purpose_id')->nullable()->after('supplier_id');
                $table->index('purpose_id', 'standard_imports_purpose_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('standard_imports', function (Blueprint $table) {
            if (Schema::hasColumn('standard_imports', 'purpose_id')) {
                $table->dropIndex('standard_imports_purpose_id_index');
                $table->dropColumn('purpose_id');
            }
        });

        Schema::dropIfExists('purposes');
    }
};
