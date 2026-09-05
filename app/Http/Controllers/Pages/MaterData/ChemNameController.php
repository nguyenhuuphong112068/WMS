<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\ChemicalClassification;
use App\Support\DataMasterHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DỮ LIỆU GỐC - TÊN HOÁ CHẤT
 *
 * chem_names giữ tên gọi (thường là tên thương mại / tên trong phòng thí nghiệm) của
 * một hoá chất - có thể là HỖN HỢP nhiều chất. Một tên hoá chất gắn được NHIỀU hoạt
 * chất (bảng pivot chem_name_active_ingredient, kèm % khối lượng từng thành phần);
 * TÊN HOẠT CHẤT / SỐ CAS / CÔNG THỨC luôn lấy từ dữ liệu gốc "Tên Hoạt Chất".
 *
 * PHÂN LOẠI HỖN HỢP theo Nghị định 24/2026/NĐ-CP (hình 1):
 *   - Nhóm 2  : tick tay ô "Hỗn hợp SX-KD có điều kiện (Phụ lục II nhóm 2)".
 *   - Nhóm 8  : tự suy - có thành phần nhóm 3/4/6/7 tỉ lệ > 1%, hoặc nhóm 5 tỉ lệ > 5%.
 *   - Nhóm 10 : hỗn hợp (>= 2 hoạt chất) có >= 1 thành phần nhóm 9 (PL IV bảng A) VÀ
 *               tick >= 1 nhóm nguy hại Bảng B (chem_name_mixture_hazard_category).
 * Hỗn hợp (>= 2 thành phần) CHỈ mang nhóm 2 / 8 / 10; các nhóm đơn chất (1, 3, 4, 5, 6,
 * 7, 9) là của từng hoạt chất thành phần, hiển thị ở cột "Hoạt Chất Thành Phần".
 * App\Support\ChemicalClassification lo toàn bộ việc suy nhóm.
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", chỉ dùng được sau khi phê duyệt.
 * Sửa lại một bản ghi đã duyệt sẽ đưa về "Chờ duyệt" để duyệt lại.
 */
class ChemNameController extends Controller
{
    private const TABLE = 'chem_names';
    private const LABEL = 'tên hoá chất';
    private const AI_PIVOT = 'chem_name_active_ingredient';
    private const HAZARD_PIVOT = 'chem_name_mixture_hazard_category';

    /** Cột người dùng nhập trực tiếp trên bảng chem_names. */
    private const FIELDS = [
        'name' => 'Tên hoá chất',
        'is_conditional_mixture' => 'Hỗn hợp SX-KD có điều kiện (nhóm 2)',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)->orderBy(self::TABLE . '.name', 'asc')->get();

        $aiByChem = $this->activeIngredientsByChemName();
        $hazardByChem = $this->hazardCategoriesByChemName();
        $groupsByChem = ChemicalClassification::groupsByChemName($datas->pluck('id')->map(fn ($v) => (int) $v)->all());

        // Bộ (phụ lục / nhóm / bảng) thô, gộp từ các hoạt chất thành phần - cho 3 cột + bộ lọc
        $aiIdsAll = $aiByChem->flatMap(fn ($c) => $c->pluck('id'))->map(fn ($v) => (int) $v)->unique()->values()->all();
        $clsByAi = DB::table('active_ingredient_classifications')
            ->whereIn('active_ingredients_id', $aiIdsAll ?: [0])
            ->get(['active_ingredients_id', 'appendix', 'group_no', 'table_ref'])
            ->groupBy('active_ingredients_id');

        foreach ($datas as $row) {
            $ais = $aiByChem->get($row->id, collect());
            $hazards = $hazardByChem->get($row->id, collect());

            $row->active_ingredients = $ais->values()->all();
            $row->hazard_categories = $hazards->values()->all();
            $row->active_ingredient_ids = $ais->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
            $row->hazard_category_ids = $hazards->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
            $row->content_percents = $ais->mapWithKeys(fn ($ai) => [(int) $ai->id => $ai->content_percent])->all();
            $row->is_conditional_mixture = (int) $row->is_conditional_mixture;
            // "có thành phần thuộc nhóm 9 (PL IV bảng A)"
            $row->has_table_a = $ais->contains(fn ($ai) => in_array(9, $ai->groups, true));
            // Bảng B chỉ xét cho HỖN HỢP: từ 2 hoạt chất trở lên, trong đó có ít nhất một thành phần nhóm 9
            $row->is_mixture = $ais->count() >= 2;
            $row->is_table_b = $row->is_mixture && $row->has_table_a && $hazards->isNotEmpty();
            $strictest = $hazards->sortBy('threshold_kg')->first();
            $row->min_hazard_threshold_kg = $strictest ? (float) $strictest->threshold_kg : null;
            $row->strictest_hazard_code = $strictest ? ($strictest->hazard_group . '.' . $strictest->ordinal) : null;
            // 10 nhóm suy được (đơn chất thừa hưởng + 2 / 8 / 10)
            $row->derived_groups = $groupsByChem[(int) $row->id] ?? [];

            // Bộ phụ lục / nhóm / bảng thô, gộp từ hoạt chất thành phần (bỏ trùng)
            $row->classifications = $ais
                ->flatMap(fn ($ai) => ($clsByAi[(int) $ai->id] ?? collect()))
                ->map(fn ($c) => [
                    'appendix' => $c->appendix,
                    'group_no' => $c->group_no === null ? null : (int) $c->group_no,
                    'table_ref' => $c->table_ref,
                ])
                ->unique(fn ($t) => $t['appendix'] . '|' . $t['group_no'] . '|' . $t['table_ref'])
                ->values()
                ->all();
        }

        session()->put(['title' => 'DỮ LIỆU GỐC - TÊN HOÁ CHẤT']);

        return view('pages.materData.ChemName.list', [
            'datas' => $datas,
            'activeIngredients' => $this->activeIngredientOptions(
                $aiByChem->flatMap(fn ($c) => $c->pluck('id'))->unique()->values()->all()
            ),
            'hazardCategories' => $this->hazardCategoryOptions(
                $hazardByChem->flatMap(fn ($c) => $c->pluck('id'))->unique()->values()->all()
            ),
            'hazardGroups' => MixtureHazardCategoryController::GROUPS,
            'groupLabels' => ChemicalClassification::GROUPS,
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());
        $validator->after(fn ($v) => $this->checkTableBPrerequisite($v, $request));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $aiIds = $this->cleanIds($request->input('active_ingredients_ids', []));
        $hazardIds = $this->cleanIds($request->input('hazard_category_ids', []));
        $percents = $this->cleanPercents($request->input('content_percent', []));
        $isConditional = $request->boolean('is_conditional_mixture') ? 1 : 0;

        $id = DB::transaction(function () use ($request, $aiIds, $hazardIds, $percents, $isConditional) {
            $newId = DB::table(self::TABLE)->insertGetId([
                'name' => trim((string) $request->name),
                'is_conditional_mixture' => $isConditional,
                'app_status' => 'pending',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncAiPivot($newId, $aiIds, $percents);
            $this->syncPivot(self::HAZARD_PIVOT, 'mixture_hazard_categories_id', $newId, $hazardIds);

            return $newId;
        });

        $note = 'Khai báo mới ' . self::LABEL . ': ' . $request->name . '.';
        if ($aiIds) {
            $note .= ' Hoạt chất: ' . $this->aiLabels($aiIds, $percents) . '.';
        }
        if ($hazardIds) {
            $note .= ' Phân loại nhóm nguy hại (nhóm 10): ' . $this->hazardLabels($hazardIds) . '.';
        }
        if ($isConditional) {
            $note .= ' Đánh dấu Hỗn hợp SX-KD có điều kiện (nhóm 2).';
        }

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', $note, self::FIELDS, $this->maps());

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm ' . self::LABEL . ': ' . $request->name);

        return redirect()->back()->with('success', 'Đã thêm ' . self::LABEL . ' thành công! Bản ghi đang chờ duyệt.');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        $validator = Validator::make($request->all(), $this->rules($current->id), $this->messages());
        $validator->after(fn ($v) => $this->checkTableBPrerequisite($v, $request));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $aiIds = $this->cleanIds($request->input('active_ingredients_ids', []));
        $hazardIds = $this->cleanIds($request->input('hazard_category_ids', []));
        $percents = $this->cleanPercents($request->input('content_percent', []));
        $isConditional = $request->boolean('is_conditional_mixture') ? 1 : 0;

        $oldAiIds = $this->pivotIds(self::AI_PIVOT, 'active_ingredients_id', $current->id);
        $oldHazardIds = $this->pivotIds(self::HAZARD_PIVOT, 'mixture_hazard_categories_id', $current->id);
        $oldPercents = $this->currentPercents($current->id);

        $payload = [
            'name' => trim((string) $request->name),
            'is_conditional_mixture' => $isConditional,
        ];

        $noteParts = [];
        if ($nameNote = DataMasterHistory::note(self::FIELDS, $current, $payload, $this->maps())) {
            $noteParts[] = $nameNote;
        }
        if ($this->normalized($oldAiIds) !== $this->normalized($aiIds) || $this->percentsChanged($oldPercents, $percents, $aiIds)) {
            $noteParts[] = 'Hoạt chất: ' . ($this->aiLabels($oldAiIds, $oldPercents) ?: '—') . ' → ' . ($this->aiLabels($aiIds, $percents) ?: '—') . '.';
        }
        if ($this->normalized($oldHazardIds) !== $this->normalized($hazardIds)) {
            $noteParts[] = 'Phân loại nhóm nguy hại (nhóm 10): ' . ($this->hazardLabels($oldHazardIds) ?: '—') . ' → ' . ($this->hazardLabels($hazardIds) ?: '—') . '.';
        }

        DB::transaction(function () use ($current, $payload, $aiIds, $hazardIds, $percents) {
            DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
                // Sửa nội dung thì phải duyệt lại từ đầu
                'app_status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->syncAiPivot($current->id, $aiIds, $percents);
            $this->syncPivot(self::HAZARD_PIVOT, 'mixture_hazard_categories_id', $current->id, $hazardIds);
        });

        DataMasterHistory::record(
            self::TABLE,
            $current->id,
            'Cập nhật',
            $noteParts ? implode(' ', $noteParts) : 'Lưu lại nhưng nội dung không đổi.',
            self::FIELDS,
            $this->maps()
        );

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->name, $request->name);

        return redirect()->back()->with('success', 'Cập nhật ' . self::LABEL . ' thành công! Bản ghi chuyển về chờ duyệt.');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần thay đổi trạng thái!');
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            self::TABLE,
            $current->id,
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            DataMasterHistory::statusNote($current->status_id, $newStatus),
            self::FIELDS,
            $this->maps()
        );

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            self::TABLE,
            $current->id,
            'status_id: ' . $current->status_id,
            'status_id: ' . $newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . self::LABEL . ' ' . $current->name . '!'
        );
    }

    public function approve(Request $request)
    {
        return $this->setApproval($request, 'approved');
    }

    public function reject(Request $request)
    {
        return $this->setApproval($request, 'rejected');
    }

    /** Trả về lịch sử thay đổi của một dòng cho modal xem lịch sử. */
    public function history(Request $request)
    {
        return response()->json([
            'rows' => DataMasterHistory::rows(self::TABLE, (int) $request->id),
        ]);
    }

    /** Ghi nhận kết quả duyệt: ai duyệt, duyệt lúc nào. */
    private function setApproval(Request $request, string $appStatus)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần duyệt!');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => $appStatus,
            'approved_by' => $this->actor(),
            'approved_at' => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            self::TABLE,
            $current->id,
            $appStatus === 'approved' ? 'Phê duyệt' : 'Từ chối duyệt',
            DataMasterHistory::approvalNote($current->app_status, $appStatus),
            self::FIELDS,
            $this->maps()
        );

        AuditTrialController::log(
            $appStatus === 'approved' ? 'Phê duyệt' : 'Từ chối duyệt',
            self::TABLE,
            $current->id,
            'app_status: ' . $current->app_status,
            'app_status: ' . $appStatus
        );

        return redirect()->back()->with(
            'success',
            ($appStatus === 'approved' ? 'Đã duyệt ' : 'Đã từ chối ') . self::LABEL . ' ' . $current->name . '!'
        );
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    /** Bảng tra giá trị đọc được cho lịch sử thay đổi. */
    private function maps(): array
    {
        return [
            'is_conditional_mixture' => [0 => 'Không', 1 => 'Có'],
        ];
    }

    /* -------------------------------------------------------------------------
     |  Hoạt chất / nhóm nguy hại của từng tên hoá chất
     | ------------------------------------------------------------------------- */

    /** [chem_names_id => Collection<{id, code, name, cas_no, chemical_formula, threshold_kg, content_percent, groups}>] */
    private function activeIngredientsByChemName()
    {
        $rows = DB::table(self::AI_PIVOT . ' as p')
            ->join('active_ingredients as ai', 'p.active_ingredients_id', '=', 'ai.id')
            ->select(
                'p.chem_names_id',
                'p.content_percent',
                'ai.id',
                'ai.code',
                'ai.name',
                'ai.cas_no',
                'ai.chemical_formula',
                'ai.threshold_kg'
            )
            ->orderBy('ai.name', 'asc')
            ->get();

        $groupsByAi = ChemicalClassification::groupsForActiveIngredients($rows->pluck('id')->all());

        return $rows
            ->map(function ($row) use ($groupsByAi) {
                $row->groups = $groupsByAi[(int) $row->id] ?? [];

                return $row;
            })
            ->groupBy('chem_names_id');
    }

    /** [chem_names_id => Collection<{id, code, hazard_group, ordinal, name, threshold_kg, threshold_basis}>] */
    private function hazardCategoriesByChemName()
    {
        return DB::table(self::HAZARD_PIVOT . ' as p')
            ->join('mixture_hazard_categories as h', 'p.mixture_hazard_categories_id', '=', 'h.id')
            ->select(
                'p.chem_names_id',
                'h.id',
                'h.code',
                'h.hazard_group',
                'h.ordinal',
                'h.name',
                'h.threshold_kg',
                'h.threshold_basis'
            )
            ->orderByRaw("FIELD(h.hazard_group, 'I', 'II', 'III', 'IV')")
            ->orderBy('h.ordinal', 'asc')
            ->get()
            ->groupBy('chem_names_id');
    }

    /**
     * Hoạt chất được phép gắn: đã duyệt + đang hoạt động. Giữ lại id đang được dùng để
     * màn hình cập nhật không mất giá trị cũ khi hoạt chất đó bị khoá / thu hồi duyệt.
     */
    private function activeIngredientOptions(array $usedIds = [])
    {
        $usedIds = array_values(array_filter($usedIds));

        $rows = DB::table('active_ingredients')
            ->select('id', 'code', 'name', 'cas_no', 'chemical_formula')
            ->where(function ($query) use ($usedIds) {
                $query->where(fn ($sub) => $sub->where('status_id', 1)->where('app_status', 'approved'));

                if ($usedIds) {
                    $query->orWhereIn('id', $usedIds);
                }
            })
            ->orderBy('name', 'asc')
            ->get();

        $groupsByAi = ChemicalClassification::groupsForActiveIngredients($rows->pluck('id')->all());

        return $rows->map(function ($row) use ($groupsByAi) {
            $row->groups = $groupsByAi[(int) $row->id] ?? [];

            return $row;
        });
    }

    /** Nhóm nguy hại Bảng B được phép tick: đã duyệt + đang hoạt động, cộng các id đang dùng. */
    private function hazardCategoryOptions(array $usedIds = [])
    {
        $usedIds = array_values(array_filter($usedIds));

        return DB::table('mixture_hazard_categories')
            ->select('id', 'code', 'hazard_group', 'ordinal', 'name', 'threshold_kg', 'threshold_basis')
            ->where(function ($query) use ($usedIds) {
                $query->where(fn ($sub) => $sub->where('status_id', 1)->where('app_status', 'approved'));

                if ($usedIds) {
                    $query->orWhereIn('id', $usedIds);
                }
            })
            ->orderByRaw("FIELD(hazard_group, 'I', 'II', 'III', 'IV')")
            ->orderBy('ordinal', 'asc')
            ->get();
    }

    /* -------------------------------------------------------------------------
     |  Pivot + validate + nhãn lịch sử
     | ------------------------------------------------------------------------- */

    /** Id đang gắn của một tên hoá chất trong một bảng pivot. */
    private function pivotIds(string $table, string $refCol, int $ownerId): array
    {
        return DB::table($table)
            ->where('chem_names_id', $ownerId)
            ->pluck($refCol)
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** % khối lượng hiện tại theo từng hoạt chất của một tên hoá chất. */
    private function currentPercents(int $ownerId): array
    {
        return DB::table(self::AI_PIVOT)
            ->where('chem_names_id', $ownerId)
            ->pluck('content_percent', 'active_ingredients_id')
            ->map(fn ($v) => $v === null ? null : (float) $v)
            ->all();
    }

    /** Đồng bộ pivot hoạt chất + % khối lượng: thêm cái mới, xoá cái bỏ chọn, cập nhật %. */
    private function syncAiPivot(int $ownerId, array $refIds, array $percents): void
    {
        $refIds = $this->normalized($refIds);
        $existing = $this->normalized($this->pivotIds(self::AI_PIVOT, 'active_ingredients_id', $ownerId));

        $toRemove = array_diff($existing, $refIds);
        $toAdd = array_diff($refIds, $existing);

        if ($toRemove) {
            DB::table(self::AI_PIVOT)->where('chem_names_id', $ownerId)->whereIn('active_ingredients_id', $toRemove)->delete();
        }

        foreach ($toAdd as $refId) {
            DB::table(self::AI_PIVOT)->insert([
                'chem_names_id' => $ownerId,
                'active_ingredients_id' => $refId,
                'content_percent' => $percents[$refId] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Cập nhật % cho các dòng vẫn còn (không thêm/xoá)
        foreach (array_intersect($refIds, $existing) as $refId) {
            DB::table(self::AI_PIVOT)
                ->where('chem_names_id', $ownerId)
                ->where('active_ingredients_id', $refId)
                ->update(['content_percent' => $percents[$refId] ?? null, 'updated_at' => now()]);
        }
    }

    /** Đồng bộ danh sách id của một bảng pivot đơn giản (không kèm dữ liệu phụ). */
    private function syncPivot(string $table, string $refCol, int $ownerId, array $refIds): void
    {
        $refIds = $this->normalized($refIds);
        $existing = $this->normalized($this->pivotIds($table, $refCol, $ownerId));

        $toRemove = array_diff($existing, $refIds);
        $toAdd = array_diff($refIds, $existing);

        if ($toRemove) {
            DB::table($table)->where('chem_names_id', $ownerId)->whereIn($refCol, $toRemove)->delete();
        }

        foreach ($toAdd as $refId) {
            DB::table($table)->insert([
                'chem_names_id' => $ownerId,
                $refCol => $refId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function cleanIds($value): array
    {
        return $this->normalized(array_map('intval', (array) $value));
    }

    /** [active_ingredients_id => % (float|null)] - bỏ giá trị rỗng / âm / quá 100. */
    private function cleanPercents($value): array
    {
        $out = [];

        foreach ((array) $value as $aiId => $percent) {
            $aiId = (int) $aiId;
            $percent = trim((string) $percent);

            if ($aiId <= 0 || $percent === '') {
                continue;
            }

            if (! is_numeric($percent)) {
                continue;
            }

            $percent = (float) $percent;

            if ($percent < 0 || $percent > 100) {
                continue;
            }

            $out[$aiId] = $percent;
        }

        return $out;
    }

    /** Bỏ trùng, bỏ giá trị không hợp lệ, sắp tăng dần - để so sánh cũ/mới ổn định. */
    private function normalized(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn ($v) => $v > 0
        )));
        sort($ids);

        return $ids;
    }

    /** % của hai lần lưu có khác nhau không (chỉ xét các hoạt chất đang gắn). */
    private function percentsChanged(array $old, array $new, array $aiIds): bool
    {
        foreach ($this->normalized($aiIds) as $aiId) {
            $o = $old[$aiId] ?? null;
            $n = $new[$aiId] ?? null;

            if (($o === null) !== ($n === null)) {
                return true;
            }

            if ($o !== null && abs((float) $o - (float) $n) > 1e-9) {
                return true;
            }
        }

        return false;
    }

    private function aiLabels(array $ids, array $percents = []): string
    {
        if (! $ids) {
            return '';
        }

        return DB::table('active_ingredients')
            ->whereIn('id', $ids)
            ->orderBy('name', 'asc')
            ->get(['id', 'name'])
            ->map(function ($row) use ($percents) {
                $percent = $percents[(int) $row->id] ?? null;

                return $percent !== null
                    ? $row->name . ' (' . rtrim(rtrim(number_format((float) $percent, 4, '.', ''), '0'), '.') . '%)'
                    : $row->name;
            })
            ->implode(', ');
    }

    private function hazardLabels(array $ids): string
    {
        if (! $ids) {
            return '';
        }

        return DB::table('mixture_hazard_categories')
            ->whereIn('id', $ids)
            ->orderByRaw("FIELD(hazard_group, 'I', 'II', 'III', 'IV')")
            ->orderBy('ordinal', 'asc')
            ->get()
            ->map(fn ($r) => $r->hazard_group . '.' . $r->ordinal)
            ->implode(', ');
    }

    /**
     * Điều kiện tiên quyết nhóm 10 (Bảng B): đã tick nhóm nguy hại thì hỗn hợp phải là
     * HỖN HỢP (>= 2 hoạt chất) và có ít nhất một thành phần thuộc nhóm 9 (Phụ lục IV bảng A).
     */
    private function checkTableBPrerequisite($validator, Request $request): void
    {
        $hazardIds = $this->cleanIds($request->input('hazard_category_ids', []));

        if (! $hazardIds) {
            return;
        }

        $aiIds = $this->cleanIds($request->input('active_ingredients_ids', []));

        $hasGroup9 = false;
        if ($aiIds) {
            $groupsByAi = ChemicalClassification::groupsForActiveIngredients($aiIds);
            foreach ($groupsByAi as $groups) {
                if (in_array(9, $groups, true)) {
                    $hasGroup9 = true;
                    break;
                }
            }
        }

        if (count($aiIds) < 2 || ! $hasGroup9) {
            $validator->errors()->add(
                'hazard_category_ids',
                'Chỉ phân loại nhóm 10 (Bảng B) cho hỗn hợp: cần chọn ít nhất 2 hoạt chất, trong đó có ít nhất một thành phần thuộc nhóm 9 (Phụ lục IV bảng A).'
            );
        }
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name' => ['required', 'max:255', Rule::unique(self::TABLE, 'name')->ignore($ignoreId)],
            'is_conditional_mixture' => ['nullable', 'boolean'],
            'active_ingredients_ids' => ['nullable', 'array'],
            'active_ingredients_ids.*' => ['integer', 'exists:active_ingredients,id'],
            'content_percent' => ['nullable', 'array'],
            'content_percent.*' => ['nullable', 'numeric', 'between:0,100'],
            'hazard_category_ids' => ['nullable', 'array'],
            'hazard_category_ids.*' => ['integer', 'exists:mixture_hazard_categories,id'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên hoá chất.',
            'name.max' => 'Tên hoá chất tối đa 255 ký tự.',
            'name.unique' => 'Tên hoá chất này đã tồn tại.',
            'is_conditional_mixture.boolean' => 'Giá trị "Hỗn hợp SX-KD có điều kiện" không hợp lệ.',
            'active_ingredients_ids.array' => 'Danh sách hoạt chất không hợp lệ.',
            'active_ingredients_ids.*.integer' => 'Hoạt chất không hợp lệ.',
            'active_ingredients_ids.*.exists' => 'Có hoạt chất không hợp lệ hoặc chưa được duyệt.',
            'content_percent.*.numeric' => 'Tỉ lệ % khối lượng phải là số.',
            'content_percent.*.between' => 'Tỉ lệ % khối lượng phải trong khoảng 0 - 100.',
            'hazard_category_ids.array' => 'Danh sách nhóm nguy hại không hợp lệ.',
            'hazard_category_ids.*.integer' => 'Nhóm nguy hại Bảng B không hợp lệ.',
            'hazard_category_ids.*.exists' => 'Có nhóm nguy hại Bảng B không hợp lệ hoặc chưa được duyệt.',
        ];
    }
}
