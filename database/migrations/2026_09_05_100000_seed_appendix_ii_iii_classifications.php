<?php

use App\Support\ChemicalFormula;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SEED TOÀN BỘ 4 PHỤ LỤC - Nghị định 24/2026/NĐ-CP.
 * ---------------------------------------------------------------------------
 * Nạp dữ liệu gốc "Tên Hoạt Chất" + phân loại theo quy tắc "hình 1" từ file
 *   database/data/nd24_2026_appendices.csv
 * (bóc từ bảng tính 4 phụ lục của Nghị định 24/2026/NĐ-CP - đã lọc bỏ các dòng
 *  mô tả / mảnh câu không phải hoá chất, chuẩn hoá số CAS, gộp biến thể đồng phân
 *  cùng tên trong một phụ lục).
 *
 * Cột CSV: name_vi, name_en, cas, formula, appendix, group_no, table_ref, threshold_kg, loai
 *
 * Ánh xạ (appendix, group_no, table_ref) -> nhóm hình 1 (App\Support\ChemicalClassification::groupOf):
 *   (II , 1, - ) => nhóm 1      (III, 1, A) => nhóm 3      (III, 1, B) => nhóm 4
 *   (III, 2, A ) => nhóm 5      (III, 2, B) => nhóm 6      (III, 2, C) => nhóm 7
 *   (IV , - , A) => nhóm 9
 *   Phụ lục I ("Danh mục hoá chất phải khai báo") KHÔNG thuộc nhóm nào của hình 1 -
 *   vẫn lưu dòng phân loại (appendix='I') để giữ vết "thuộc Phụ lục I", groupOf() trả null
 *   nên không hiện badge nhóm. Nhóm 2/8/10 (hỗn hợp) suy ở màn "Tên Hoá Chất", không seed ở đây.
 *
 * HOÁ CHẤT TRÙNG NHIỀU PHỤ LỤC: khớp theo số CAS trước (rồi tới tên) với dòng đang
 * có - kể cả 271 chất Phụ lục IV đã seed ở 2026_09_03_120000 - nên một chất nằm ở
 * nhiều phụ lục chỉ tạo MỘT active_ingredients và nhiều dòng active_ingredient_classifications.
 *
 * up():   idempotent - updateOrInsert theo CAS/tên, chỉ bổ khuyết name_en/công thức/
 *         ngưỡng khi dòng cũ còn trống, updateOrInsert dòng phân loại.
 * down(): gỡ dòng phân loại phụ lục I/II/III do file này thêm; xoá active_ingredients
 *         chỉ khi do file này tạo (legal_ref = MARK), không còn phân loại, chưa gắn
 *         tên hoá chất, threshold_kg trống. Dòng Phụ lục IV để nguyên (thuộc 2026_09_03_*).
 *
 * Bổ sung / sửa dữ liệu: sửa file CSV rồi
 *   php artisan migrate:rollback --step=1 && php artisan migrate
 */
return new class extends Migration
{
    private const DATA_FILE = 'data/nd24_2026_appendices.csv';
    private const MARK = 'Nghị định 24/2026/NĐ-CP';

    public function up(): void
    {
        if (! Schema::hasTable('active_ingredients') || ! Schema::hasTable('active_ingredient_classifications')) {
            return;
        }

        $rows = $this->readCsv();

        if (! $rows) {
            return;
        }

        $now = now();

        $maxNumber = DB::table('active_ingredients')
            ->where('code', 'like', 'A%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr($code, 1))
            ->max() ?? 0;

        foreach ($rows as $r) {
            $nameVi = trim($r['name_vi'] ?? '');

            if ($nameVi === '') {
                continue;
            }

            $nameEn = trim($r['name_en'] ?? '') ?: null;
            $cas = trim($r['cas'] ?? '') ?: null;
            $formula = trim($r['formula'] ?? '');
            $formulaStored = $formula === '' ? null : ChemicalFormula::toSubscript($formula);
            $appendix = trim($r['appendix'] ?? '');
            $groupNo = ($g = trim($r['group_no'] ?? '')) === '' ? null : (int) $g;
            $tableRef = trim($r['table_ref'] ?? '') ?: null;
            $threshold = ($t = trim($r['threshold_kg'] ?? '')) === '' ? null : (float) $t;
            $loai = trim($r['loai'] ?? '');

            if ($appendix === '') {
                continue;
            }

            // 1) Khớp hoạt chất đang có: số CAS trước, rồi tới tên.
            $existing = null;
            if ($cas) {
                $existing = DB::table('active_ingredients')->where('cas_no', $cas)->first();
            }
            if (! $existing) {
                $existing = DB::table('active_ingredients')->where('name', $nameVi)->first();
            }

            if ($existing) {
                $aiId = $existing->id;

                $patch = [];
                if (! $existing->name_en && $nameEn) {
                    $patch['name_en'] = $nameEn;
                }
                if (! $existing->chemical_formula && $formulaStored) {
                    $patch['chemical_formula'] = $formulaStored;
                }
                if ($existing->threshold_kg === null && $threshold !== null) {
                    $patch['threshold_kg'] = $threshold;
                }
                if ($patch) {
                    $patch['updated_at'] = $now;
                    DB::table('active_ingredients')->where('id', $aiId)->update($patch);
                }
            } else {
                $aiId = DB::table('active_ingredients')->insertGetId([
                    'code' => 'A' . str_pad((string) (++$maxNumber), 5, '0', STR_PAD_LEFT),
                    'name' => $nameVi,
                    'name_en' => $nameEn,
                    'cas_no' => $cas,
                    'chemical_formula' => $formulaStored,
                    'threshold_kg' => $threshold,
                    'legal_ref' => self::MARK,
                    'is_statutory' => 1,
                    'note' => self::MARK . ' - Phụ lục ' . $appendix,
                    'app_status' => 'approved',
                    'status_id' => 1,
                    'approved_by' => 'Hệ thống',
                    'approved_at' => $now,
                    'created_by' => 'Hệ thống',
                    'updated_by' => 'Hệ thống',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 2) Dòng phân loại (một hoạt chất có thể nhiều dòng - nhiều phụ lục)
            $clsNote = self::MARK . ' · Phụ lục ' . $appendix
                . ($groupNo !== null ? ' nhóm ' . $groupNo : '')
                . ($tableRef ? ' bảng ' . $tableRef : '')
                . ($loai !== '' ? ' · Loại: ' . $loai : '');

            DB::table('active_ingredient_classifications')->updateOrInsert(
                [
                    'active_ingredients_id' => $aiId,
                    'appendix' => $appendix,
                    'group_no' => $groupNo,
                    'table_ref' => $tableRef,
                ],
                [
                    'note' => $clsNote,
                    'is_statutory' => 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('active_ingredient_classifications')) {
            return;
        }

        // Xoá mọi dòng phân loại Phụ lục I/II/III do file này seed - nhận diện theo note
        // (dòng người dùng tự tick ở màn Tên Hoạt Chất có note = null). Không đụng Phụ lục IV
        // (thuộc backfill 2026_09_05_090100). Lọc theo note nên KHÔNG phụ thuộc CSV hiện tại,
        // an toàn cả khi bộ (phụ lục/nhóm/bảng) đã đổi so với lần seed trước.
        DB::table('active_ingredient_classifications')
            ->whereIn('appendix', ['I', 'II', 'III'])
            ->where('note', 'like', self::MARK . ' · Phụ lục%')
            ->delete();

        // Dọn active_ingredients chỉ do file này tạo và giờ không còn dấu vết nào.
        $orphans = DB::table('active_ingredients as ai')
            ->where('ai.created_by', 'Hệ thống')
            ->where('ai.legal_ref', self::MARK)
            ->whereNull('ai.threshold_kg')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('active_ingredient_classifications as c')
                ->whereColumn('c.active_ingredients_id', 'ai.id'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('chem_name_active_ingredient as p')
                ->whereColumn('p.active_ingredients_id', 'ai.id'))
            ->pluck('ai.id');

        if ($orphans->isNotEmpty()) {
            DB::table('active_ingredients')->whereIn('id', $orphans)->delete();
        }
    }

    /** Đọc CSV dữ liệu phụ lục thành mảng kết hợp theo tên cột. */
    private function readCsv(): array
    {
        $path = database_path(self::DATA_FILE);

        if (! is_file($path) || ! ($fh = fopen($path, 'r'))) {
            return [];
        }

        $header = fgetcsv($fh);
        if ($header === false) {
            fclose($fh);

            return [];
        }
        $header = array_map('trim', $header);

        $rows = [];
        while (($line = fgetcsv($fh)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }
            $rows[] = array_combine($header, array_pad(array_slice($line, 0, count($header)), count($header), ''));
        }
        fclose($fh);

        return $rows;
    }
};
