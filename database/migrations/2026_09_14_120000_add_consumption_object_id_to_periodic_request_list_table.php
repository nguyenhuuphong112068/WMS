<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Danh sách đề nghị vật tư NỘI BỘ theo chu kỳ gắn với một Đối tượng (dữ liệu gốc
 * consumption_objects - thiết bị sản xuất / kiểm nghiệm...). Danh sách liên phòng ban để NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodic_request_list', function (Blueprint $table) {
            if (! Schema::hasColumn('periodic_request_list', 'consumption_object_id')) {
                $table->unsignedBigInteger('consumption_object_id')->nullable()->after('type'); // -> consumption_objects.id
                $table->index('consumption_object_id', 'periodic_request_list_consumption_object_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('periodic_request_list', function (Blueprint $table) {
            if (Schema::hasColumn('periodic_request_list', 'consumption_object_id')) {
                $table->dropIndex('periodic_request_list_consumption_object_id_index');
                $table->dropColumn('consumption_object_id');
            }
        });
    }
};
