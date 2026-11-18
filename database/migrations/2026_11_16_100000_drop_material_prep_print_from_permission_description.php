<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 16/11/2026 bỏ chức năng "In phiếu soạn hàng" ở tab Chờ Cấp Phát (Sử Dụng Vật Tư):
 * gỡ câu "in phiếu soạn hàng" khỏi diễn giải quyền export_material_issue.
 */
return new class extends Migration
{
    private const OLD = 'Sổ cấp phát; in phiếu soạn hàng. Gồm cả';

    private const NEW = 'Sổ cấp phát. Gồm cả';

    public function up(): void
    {
        $this->swap(self::OLD, self::NEW);
    }

    public function down(): void
    {
        $this->swap(self::NEW, self::OLD);
    }

    private function swap(string $from, string $to): void
    {
        $row = DB::table('permissions')->where('name', 'export_material_issue')->first();

        if ($row && str_contains((string) $row->description, $from)) {
            DB::table('permissions')->where('id', $row->id)->update([
                'description' => str_replace($from, $to, $row->description),
            ]);
        }
    }
};
