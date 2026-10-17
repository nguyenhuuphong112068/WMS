<?php

use App\Support\DataMasterHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nhóm 11 "Hoá chất cấm theo Luật Đầu tư 2025, số 143/2025/QH15" chuyển từ màn Tên Hoạt
 * Chất (dòng active_ingredient_classifications appendix='LDT') sang tick tay ở Dữ Liệu Gốc
 * -> Hoá Chất (cột chem_names.is_banned), cùng khối "Phân loại khác" với nhóm 12 Hàng hoá
 * đặc biệt. App\Support\ChemicalClassification::groupsByChemName() đọc cột này.
 *
 * Chuyển dữ liệu giữ nguyên trạng thái đang hiển thị: trước đây chỉ tên hoá chất ĐƠN CHẤT
 * (đúng 1 hoạt chất thành phần đã duyệt, đang hoạt động) mới mang nhóm 11 - thừa hưởng từ
 * hoạt chất khai LDT, hoặc từ mục gộp cha (đã duyệt, đang hoạt động) khai LDT. Hỗn hợp
 * không mang nhóm 11 nên không tick.
 */
return new class extends Migration
{
    private const ACTOR = 'Hệ thống (nâng cấp dữ liệu)';

    public function up(): void
    {
        if (! Schema::hasTable('chem_names')) {
            return;
        }

        if (! Schema::hasColumn('chem_names', 'is_banned')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->tinyInteger('is_banned')->default(0)->after('is_special_goods');
            });
        }

        if (! Schema::hasTable('active_ingredient_classifications')) {
            return;
        }

        $ldtAiIds = DB::table('active_ingredient_classifications')
            ->where('appendix', 'LDT')
            ->pluck('active_ingredients_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        if (! $ldtAiIds) {
            return;
        }

        // Chất thành viên thừa hưởng nhóm 11 từ mục gộp cha khai LDT
        $memberIds = DB::table('active_ingredients as ai')
            ->join('active_ingredients as pai', 'pai.id', '=', 'ai.parent_id')
            ->whereIn('pai.id', $ldtAiIds)
            ->where('pai.status_id', 1)
            ->where('pai.app_status', 'approved')
            ->pluck('ai.id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $bannedAiIds = array_flip(array_merge($ldtAiIds, $memberIds));

        $chemNameIds = DB::table('chem_name_active_ingredient as p')
            ->join('active_ingredients as ai', 'ai.id', '=', 'p.active_ingredients_id')
            ->where('ai.status_id', 1)
            ->where('ai.app_status', 'approved')
            ->groupBy('p.chem_names_id')
            ->havingRaw('count(*) = 1')
            ->select('p.chem_names_id', DB::raw('min(p.active_ingredients_id) as ai_id'))
            ->get()
            ->filter(fn ($row) => isset($bannedAiIds[(int) $row->ai_id]))
            ->pluck('chem_names_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        DB::transaction(function () use ($chemNameIds, $ldtAiIds) {
            if ($chemNameIds) {
                DB::table('chem_names')->whereIn('id', $chemNameIds)->update(['is_banned' => 1]);

                foreach ($chemNameIds as $id) {
                    $this->history(
                        'chem_names',
                        $id,
                        'Phân loại khác: tick "Hoá chất cấm (Luật Đầu tư 2025)" - chuyển từ phân loại của hoạt chất thành phần sang khai trực tiếp trên tên hoá chất.'
                    );
                }
            }

            DB::table('active_ingredient_classifications')->where('appendix', 'LDT')->delete();

            foreach ($ldtAiIds as $id) {
                $this->history(
                    'active_ingredients',
                    $id,
                    'Phân loại: bỏ "Nhóm HC Cấm" (Luật Đầu tư 2025) - chuyển sang khai ở Dữ Liệu Gốc -> Hoá Chất, khối "Phân loại khác".'
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('chem_names') || ! Schema::hasColumn('chem_names', 'is_banned')) {
            return;
        }

        // Trả nhóm 11 về hoạt chất: tên hoá chất đơn chất đang tick -> dòng LDT cho hoạt chất đó
        if (Schema::hasTable('active_ingredient_classifications')) {
            $aiIds = DB::table('chem_names as c')
                ->join('chem_name_active_ingredient as p', 'p.chem_names_id', '=', 'c.id')
                ->where('c.is_banned', 1)
                ->groupBy('c.id')
                ->havingRaw('count(*) = 1')
                ->select(DB::raw('min(p.active_ingredients_id) as ai_id'))
                ->pluck('ai_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->all();

            foreach ($aiIds as $aiId) {
                $exists = DB::table('active_ingredient_classifications')
                    ->where('active_ingredients_id', $aiId)
                    ->where('appendix', 'LDT')
                    ->exists();

                if (! $exists) {
                    DB::table('active_ingredient_classifications')->insert([
                        'active_ingredients_id' => $aiId,
                        'appendix' => 'LDT',
                        'group_no' => null,
                        'table_ref' => null,
                        'note' => null,
                        'is_statutory' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Schema::table('chem_names', function (Blueprint $table) {
            $table->dropColumn('is_banned');
        });
    }

    private function history(string $table, int $recordId, string $note): void
    {
        if (! Schema::hasTable(DataMasterHistory::TABLE)) {
            return;
        }

        DB::table(DataMasterHistory::TABLE)->insert([
            'table_name' => $table,
            'record_id' => $recordId,
            'action' => 'Cập nhật',
            'snapshot' => json_encode([], JSON_UNESCAPED_UNICODE),
            'change_note' => $note,
            'change_reason' => 'Gộp "Hoá chất cấm" và "Hàng hoá đặc biệt" vào khối "Phân loại khác" của Dữ Liệu Gốc -> Hoá Chất.',
            'created_by' => self::ACTOR,
            'created_at' => now(),
        ]);
    }
};
