<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standard_imports', function (Blueprint $table) {
            // Drop index if exists before altering
            try {
                $table->dropIndex('standard_imports_purpose_id_index');
            } catch (\Exception $e) {}
        });

        // Use raw SQL to alter column type safely
        DB::statement('ALTER TABLE standard_imports MODIFY purpose_id VARCHAR(500) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE standard_imports MODIFY purpose_id BIGINT UNSIGNED NULL');
    }
};

