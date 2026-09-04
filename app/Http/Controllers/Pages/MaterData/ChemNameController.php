<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
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
 * chất (bảng pivot chem_name_active_ingredient); TÊN HOẠT CHẤT / SỐ CAS / CÔNG THỨC
 * luôn lấy từ dữ liệu gốc "Tên Hoạt Chất" (active_ingredients).
 *
 * BẢNG B - Phụ lục IV NĐ 24/2026/NĐ-CP: nếu hỗn hợp có ít nhất một hoạt chất thuộc
 * Bảng A (active_ingredients.is_table_a = 1) và được tick một hay nhiều nhóm nguy hại
 * (bảng pivot chem_name_mixture_hazard_category) thì hỗn hợp bị xét theo Bảng B.
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

    /** Cột người dùng nhập trực tiếp trên bảng chem_names (hoạt chất / nhóm Bảng B ghi lịch sử riêng). */
    private const FIELDS = [
        'name' => 'Tên hoá chất',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)->orderBy(self::TABLE . '.name', 'asc')->get();

        $aiByChem = $this->activeIngredientsByChemName();
        $hazardByChem = $this->hazardCategoriesByChemName();

        foreach ($datas as $row) {
            $ais = $aiByChem->get($row->id, collect());
            $hazards = $hazardByChem->get($row->id, collect());

            $row->active_ingredients = $ais->values()->all();
            $row->hazard_categories = $hazards->values()->all();
            $row->active_ingredient_ids = $ais->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
            $row->hazard_category_ids = $hazards->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
            $row->has_table_a = $ais->contains(fn ($ai) => (int) $ai->is_table_a === 1);
            // Bảng B chỉ xét cho HỖN HỢP: từ 2 hoạt chất trở lên, trong đó có ít nhất một hoạt chất Bảng A
            $row->is_mixture = $ais->count() >= 2;
            $row->is_table_b = $row->is_mixture && $row->has_table_a && $hazards->isNotEmpty();
            $strictest = $hazards->sortBy('threshold_kg')->first();
            $row->min_hazard_threshold_kg = $strictest ? (float) $strictest->threshold_kg : null;
            $row->strictest_hazard_code = $strictest ? ($strictest->hazard_group . '.' . $strictest->ordinal) : null;
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

        $id = DB::transaction(function () use ($request, $aiIds, $hazardIds) {
            $newId = DB::table(self::TABLE)->insertGetId([
                'name' => trim((string) $request->name),
                'app_status' => 'pending',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncPivot(self::AI_PIVOT, 'active_ingredients_id', $newId, $aiIds);
            $this->syncPivot(self::HAZARD_PIVOT, 'mixture_hazard_categories_id', $newId, $hazardIds);

            return $newId;
        });

        $note = 'Khai báo mới ' . self::LABEL . ': ' . $request->name . '.';
        if ($aiIds) {
            $note .= ' Hoạt chất: ' . $this->aiLabels($aiIds) . '.';
        }
        if ($hazardIds) {
            $note .= ' Phân loại Bảng B: ' . $this->hazardLabels($hazardIds) . '.';
        }

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', $note, self::FIELDS);

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

        $oldAiIds = $this->pivotIds(self::AI_PIVOT, 'active_ingredients_id', $current->id);
        $oldHazardIds = $this->pivotIds(self::HAZARD_PIVOT, 'mixture_hazard_categories_id', $current->id);

        $payload = ['name' => trim((string) $request->name)];

        $noteParts = [];
        if ($nameNote = DataMasterHistory::note(self::FIELDS, $current, $payload)) {
            $noteParts[] = $nameNote;
        }
        if ($this->normalized($oldAiIds) !== $this->normalized($aiIds)) {
            $noteParts[] = 'Hoạt chất: ' . ($this->aiLabels($oldAiIds) ?: '—') . ' → ' . ($this->aiLabels($aiIds) ?: '—') . '.';
        }
        if ($this->normalized($oldHazardIds) !== $this->normalized($hazardIds)) {
            $noteParts[] = 'Phân loại Bảng B: ' . ($this->hazardLabels($oldHazardIds) ?: '—') . ' → ' . ($this->hazardLabels($hazardIds) ?: '—') . '.';
        }

        DB::transaction(function () use ($current, $payload, $aiIds, $hazardIds) {
            DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
                // Sửa nội dung thì phải duyệt lại từ đầu
                'app_status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->syncPivot(self::AI_PIVOT, 'active_ingredients_id', $current->id, $aiIds);
            $this->syncPivot(self::HAZARD_PIVOT, 'mixture_hazard_categories_id', $current->id, $hazardIds);
        });

        DataMasterHistory::record(
            self::TABLE,
            $current->id,
            'Cập nhật',
            $noteParts ? implode(' ', $noteParts) : 'Lưu lại nhưng nội dung không đổi.',
            self::FIELDS
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
            self::FIELDS
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
            self::FIELDS
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

    /* -------------------------------------------------------------------------
     |  Hoạt chất / nhóm nguy hại của từng tên hoá chất
     | ------------------------------------------------------------------------- */

    /** [chem_names_id => Collection<{id, code, name, cas_no, chemical_formula, is_table_a, threshold_kg}>] */
    private function activeIngredientsByChemName()
    {
        return DB::table(self::AI_PIVOT . ' as p')
            ->join('active_ingredients as ai', 'p.active_ingredients_id', '=', 'ai.id')
            ->select(
                'p.chem_names_id',
                'ai.id',
                'ai.code',
                'ai.name',
                'ai.cas_no',
                'ai.chemical_formula',
                'ai.is_table_a',
                'ai.threshold_kg'
            )
            ->orderBy('ai.name', 'asc')
            ->get()
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

        return DB::table('active_ingredients')
            ->select('id', 'code', 'name', 'cas_no', 'chemical_formula', 'is_table_a', 'threshold_kg')
            ->where(function ($query) use ($usedIds) {
                $query->where(fn ($sub) => $sub->where('status_id', 1)->where('app_status', 'approved'));

                if ($usedIds) {
                    $query->orWhereIn('id', $usedIds);
                }
            })
            ->orderBy('name', 'asc')
            ->get();
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

    /** Đồng bộ danh sách id của một bảng pivot: thêm cái mới, xoá cái bỏ chọn. */
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

    private function aiLabels(array $ids): string
    {
        if (! $ids) {
            return '';
        }

        return DB::table('active_ingredients')
            ->whereIn('id', $ids)
            ->orderBy('name', 'asc')
            ->pluck('name')
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
     * Điều kiện tiên quyết Bảng B: đã tick nhóm nguy hại thì hỗn hợp phải có ít nhất
     * một hoạt chất thuộc Bảng A.
     */
    private function checkTableBPrerequisite($validator, Request $request): void
    {
        $hazardIds = $this->cleanIds($request->input('hazard_category_ids', []));

        if (! $hazardIds) {
            return;
        }

        $aiIds = $this->cleanIds($request->input('active_ingredients_ids', []));

        $hasTableA = $aiIds && DB::table('active_ingredients')
            ->whereIn('id', $aiIds)
            ->where('is_table_a', 1)
            ->exists();

        // Bảng B chỉ áp cho hỗn hợp: >= 2 hoạt chất, trong đó >= 1 thuộc Bảng A
        if (count($aiIds) < 2 || ! $hasTableA) {
            $validator->errors()->add(
                'hazard_category_ids',
                'Chỉ phân loại Bảng B cho hỗn hợp: cần chọn ít nhất 2 hoạt chất, trong đó có ít nhất một hoạt chất thuộc Bảng A.'
            );
        }
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name' => ['required', 'max:255', Rule::unique(self::TABLE, 'name')->ignore($ignoreId)],
            'active_ingredients_ids' => ['nullable', 'array'],
            'active_ingredients_ids.*' => ['integer', 'exists:active_ingredients,id'],
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
            'active_ingredients_ids.array' => 'Danh sách hoạt chất không hợp lệ.',
            'active_ingredients_ids.*.integer' => 'Hoạt chất không hợp lệ.',
            'active_ingredients_ids.*.exists' => 'Có hoạt chất không hợp lệ hoặc chưa được duyệt.',
            'hazard_category_ids.array' => 'Danh sách nhóm nguy hại không hợp lệ.',
            'hazard_category_ids.*.integer' => 'Nhóm nguy hại Bảng B không hợp lệ.',
            'hazard_category_ids.*.exists' => 'Có nhóm nguy hại Bảng B không hợp lệ hoặc chưa được duyệt.',
        ];
    }
}
