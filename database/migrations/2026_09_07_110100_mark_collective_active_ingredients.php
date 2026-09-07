<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ĐÁNH DẤU CÁC DÒNG "MỤC GỘP" TRONG DỮ LIỆU GỐC TÊN HOẠT CHẤT.
 *
 * Nghị định 24/2026/NĐ-CP có nhiều dòng khai theo NHÓM chất chứ không theo một chất cụ
 * thể ("Thủy ngân và các hợp chất của thủy ngân", "Các hợp chất xyanua", "Beryli (dạng bột
 * và các hợp chất)", "Chì và các hợp chất của chì"...). Các dòng này là ĐÍCH để gắn chất
 * cụ thể vào (active_ingredients.parent_id) nên cần cờ is_collective = 1 để màn "Tên Hoạt
 * Chất" đưa vào danh sách chọn "Thuộc mục gộp".
 *
 * Nhận diện theo mẫu tên - đủ để phủ toàn bộ dòng gộp của cả 4 phụ lục:
 *   "... và các hợp chất ...", "... các muối ...", "... muối của nó ...", "Hợp chất ...".
 *
 * CỐ Ý KHÔNG tự gán parent_id cho bất kỳ chất nào: một mục gộp thường kèm điều kiện pháp
 * lý ("dạng bột có thể phát tán trong không khí", "muối asenat" khác "muối asenit"...) nên
 * việc một chất cụ thể có thuộc mục gộp hay không phải do người dùng quyết định trên màn
 * Tên Hoạt Chất, không suy máy móc từ tên.
 */
return new class extends Migration
{
    /** Mẫu tên nhận diện dòng khai theo nhóm chất. */
    private const PATTERNS = [
        '%các hợp chất%',
        '%các muối%',
        '%muối của nó%',
        'Hợp chất %',
    ];

    public function up(): void
    {
        $this->setFlag(1);
    }

    public function down(): void
    {
        $this->setFlag(0);
    }

    private function setFlag(int $value): void
    {
        if (! Schema::hasTable('active_ingredients')
            || ! Schema::hasColumn('active_ingredients', 'is_collective')) {
            return;
        }

        DB::table('active_ingredients')
            ->where(function ($query) {
                foreach (self::PATTERNS as $pattern) {
                    $query->orWhere('name', 'like', $pattern);
                }
            })
            ->update(['is_collective' => $value]);
    }
};
