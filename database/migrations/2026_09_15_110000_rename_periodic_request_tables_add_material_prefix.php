<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm tiền tố material_ vào 3 bảng đề nghị theo chu kỳ để phân biệt rõ ràng
 * với các bảng periodic_request của module khác trong tương lai.
 *
 * - periodic_request_list           → material_periodic_request_list
 * - periodic_request_item           → material_periodic_request_item
 * - periodic_request_list_histories → material_periodic_request_list_histories
 */
return new class extends Migration
{
    private const RENAMES = [
        'periodic_request_list'           => 'material_periodic_request_list',
        'periodic_request_item'           => 'material_periodic_request_item',
        'periodic_request_list_histories' => 'material_periodic_request_list_histories',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $old => $new) {
            if (Schema::hasTable($old) && ! Schema::hasTable($new)) {
                Schema::rename($old, $new);
            }
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $old => $new) {
            if (Schema::hasTable($new) && ! Schema::hasTable($old)) {
                Schema::rename($new, $old);
            }
        }
    }
};
