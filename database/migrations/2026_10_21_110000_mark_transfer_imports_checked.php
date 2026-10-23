<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lô nhận chuyển liên phòng ban: bước Nhận chính là bước Kiểm tra. Các lô đã nhận trước đây
 * ghi is_checked = 1 (đã cộng tồn) nhưng quên ghi check_result nên vẫn là 'pending' và lọt vào
 * tab "Chờ kiểm tra" của màn Nhập - chuyển sang 'passed' cho khớp với is_checked.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['material_imports', 'chemical_imports', 'standard_imports'] as $table) {
            DB::table($table)
                ->whereNotNull('transfer_item_id')
                ->where('is_checked', 1)
                ->where('check_result', 'pending')
                ->update(['check_result' => 'passed']);
        }
    }

    public function down(): void
    {
        // Không cần hoàn tác: 'pending' khi is_checked = 1 là dữ liệu sai
    }
};
