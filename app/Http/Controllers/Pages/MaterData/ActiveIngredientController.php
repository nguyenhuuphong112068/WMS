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
 * DỮ LIỆU GỐC - TÊN HOẠT CHẤT (Nghị định 24/2026/NĐ-CP)
 *
 * Mỗi hoạt chất được phân loại theo quy tắc "hình 1" của NĐ 24/2026. Ở màn này khai được
 * các nhóm ĐƠN CHẤT: 1, 3, 4, 5, 6, 7, 9 (App\Support\ChemicalClassification::
 * SINGLE_SUBSTANCE_GROUPS). Một hoạt chất có thể thuộc NHIỀU nhóm cùng lúc nên phân loại
 * lưu ở bảng con active_ingredient_classifications (mỗi dòng một bộ phụ lục / nhóm / bảng).
 *
 * Nhóm 9 = "Phụ lục IV Bảng A" - bắt buộc kèm "Ngưỡng khối lượng hoá chất tồn trữ lớn nhất
 * tại một thời điểm (kg)" (cột threshold_kg). Các nhóm hỗn hợp (2, 8, 10) suy ở màn "Tên
 * Hoá Chất", không khai ở đây.
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", chỉ dùng để cảnh báo sau khi phê duyệt.
 * Sửa lại một bản ghi đã duyệt sẽ đưa về "Chờ duyệt" để duyệt lại.
 */
class ActiveIngredientController extends Controller
{
    private const TABLE = 'active_ingredients';
    private const GROUP_TABLE = 'active_ingredient_classifications';
    private const LABEL = 'tên hoạt chất';

    /** Tiền tố mã sinh tự động: A00001, A00002... */
    private const CODE_PREFIX = 'A';
    private const CODE_LENGTH = 5;

    /** Các cột người dùng nhập - dùng chung cho ảnh chụp và mô tả thay đổi của lịch sử. */
    private const FIELDS = [
        'name' => 'Tên hoạt chất',
        'name_en' => 'Tên tiếng Anh',
        'cas_no' => 'Số CAS',
        'chemical_formula' => 'Công thức hoá học',
        'threshold_kg' => 'Ngưỡng tồn trữ (kg)',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)->orderBy('name', 'asc')->get();

        $ids = $datas->pluck('id')->all();
        $groupsByAi = ChemicalClassification::groupsForActiveIngredients($ids);

        // Các dòng phân loại thô (phụ lục / nhóm / bảng) để hiện 3 cột riêng + bộ lọc.
        $clsByAi = DB::table(self::GROUP_TABLE)
            ->whereIn('active_ingredients_id', $ids ?: [0])
            ->orderBy('appendix')
            ->orderByRaw('group_no is null, group_no')
            ->orderByRaw('table_ref is null, table_ref')
            ->get(['active_ingredients_id', 'appendix', 'group_no', 'table_ref'])
            ->groupBy('active_ingredients_id');

        foreach ($datas as $row) {
            $row->groups = $groupsByAi[$row->id] ?? [];
            $row->classifications = ($clsByAi[$row->id] ?? collect())
                ->map(fn ($c) => [
                    'appendix' => $c->appendix,
                    'group_no' => $c->group_no === null ? null : (int) $c->group_no,
                    'table_ref' => $c->table_ref,
                ])
                ->values()
                ->all();
        }

        session()->put(['title' => 'DỮ LIỆU GỐC - TÊN HOẠT CHẤT']);

        return view('pages.materData.ActiveIngredient.list', [
            'datas' => $datas,
            'groupLabels' => ChemicalClassification::GROUPS,
            'singleSubstanceGroups' => ChemicalClassification::SINGLE_SUBSTANCE_GROUPS,
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(null, $request), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $groups = $this->groupsFromRequest($request);

        // Sinh mã trong transaction để hai người thêm cùng lúc không trùng mã
        $id = DB::transaction(function () use ($request, $groups) {
            $newId = DB::table(self::TABLE)->insertGetId($this->payload($request, $groups) + [
                'code' => $this->nextCode(),
                'is_statutory' => 0,
                'app_status' => 'pending',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncGroups($newId, $groups, 0);

            return $newId;
        });

        $note = 'Khai báo mới ' . self::LABEL . ': ' . $request->name . '.';
        if ($groups) {
            $note .= ' Phân loại NĐ 24/2026: ' . $this->groupLabels($groups) . '.';
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

        $validator = Validator::make($request->all(), $this->rules($current->id, $request), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $groups = $this->groupsFromRequest($request);
        $oldGroups = $this->groupsOf($current->id);

        $payload = $this->payload($request, $groups);
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload);

        if ($this->normalized($oldGroups) !== $this->normalized($groups)) {
            $note = trim($note . ' | Phân loại NĐ 24/2026: '
                . ($this->groupLabels($oldGroups) ?: '—') . ' -> '
                . ($this->groupLabels($groups) ?: '—'), ' |');
        }

        // Nhắc rõ khi sửa dữ liệu lấy từ nghị định
        if ($current->is_statutory) {
            $note = trim('Sửa dữ liệu luật định. ' . $note);
        }

        DB::transaction(function () use ($current, $payload, $groups) {
            DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
                // Sửa nội dung thì phải duyệt lại từ đầu
                'app_status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->syncGroups($current->id, $groups, (int) $current->is_statutory);
        });

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.', self::FIELDS);

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

    /* -------------------------------------------------------------------------
     |  Phân loại NĐ 24/2026 (bảng con active_ingredient_classifications)
     | ------------------------------------------------------------------------- */

    /** Danh sách số nhóm đơn chất hợp lệ gửi lên từ form. */
    private function groupsFromRequest(Request $request): array
    {
        $raw = array_map('intval', (array) $request->input('groups', []));

        return $this->normalized(array_intersect($raw, ChemicalClassification::SINGLE_SUBSTANCE_GROUPS));
    }

    /** Số nhóm đơn chất đang gắn cho một hoạt chất. */
    private function groupsOf(int $aiId): array
    {
        $rows = DB::table(self::GROUP_TABLE)
            ->where('active_ingredients_id', $aiId)
            ->get(['appendix', 'group_no', 'table_ref']);

        $groups = [];
        foreach ($rows as $row) {
            $group = ChemicalClassification::groupOf($row->appendix, $row->group_no, $row->table_ref);
            if ($group !== null) {
                $groups[] = $group;
            }
        }

        return $this->normalized($groups);
    }

    /**
     * Đồng bộ các dòng phân loại đơn chất: thêm nhóm mới tick, xoá nhóm bỏ chọn.
     * Chỉ đụng các nhóm đơn chất; các dòng phụ lục / bảng khác (nếu có) không bị ảnh hưởng.
     */
    private function syncGroups(int $aiId, array $groups, int $isStatutory): void
    {
        $groups = $this->normalized($groups);
        $existing = $this->groupsOf($aiId);

        foreach (array_diff($existing, $groups) as $group) {
            [$appendix, $groupNo, $tableRef] = ChemicalClassification::tripleOf($group);

            DB::table(self::GROUP_TABLE)
                ->where('active_ingredients_id', $aiId)
                ->where('appendix', $appendix)
                ->when($groupNo === null, fn ($q) => $q->whereNull('group_no'), fn ($q) => $q->where('group_no', $groupNo))
                ->when($tableRef === null, fn ($q) => $q->whereNull('table_ref'), fn ($q) => $q->where('table_ref', $tableRef))
                ->delete();
        }

        foreach (array_diff($groups, $existing) as $group) {
            [$appendix, $groupNo, $tableRef] = ChemicalClassification::tripleOf($group);

            DB::table(self::GROUP_TABLE)->insert([
                'active_ingredients_id' => $aiId,
                'appendix' => $appendix,
                'group_no' => $groupNo,
                'table_ref' => $tableRef,
                'note' => null,
                'is_statutory' => $isStatutory,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** "Nhóm 3, Nhóm 9" - mô tả ngắn cho lịch sử. */
    private function groupLabels(array $groups): string
    {
        $groups = $this->normalized($groups);

        return implode(', ', array_map(fn ($g) => 'Nhóm ' . $g, $groups));
    }

    /** Bỏ trùng, bỏ giá trị không hợp lệ, sắp tăng dần. */
    private function normalized(array $groups): array
    {
        $groups = array_values(array_unique(array_filter(
            array_map('intval', $groups),
            fn ($v) => $v > 0
        )));
        sort($groups);

        return $groups;
    }

    /**
     * Mã kế tiếp theo dạng A00001: lấy số lớn nhất đang có rồi cộng 1.
     * Màn hình chỉ khoá bản ghi chứ không xoá nên mã không bị dùng lại.
     */
    private function nextCode(): string
    {
        $numbers = DB::table(self::TABLE)
            ->where('code', 'like', self::CODE_PREFIX . '%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr($code, strlen(self::CODE_PREFIX)));

        $next = ($numbers->max() ?? 0) + 1;

        return self::CODE_PREFIX . str_pad((string) $next, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    private function rules($ignoreId = null, ?Request $request = null): array
    {
        $request = $request ?? request();
        $isGroup9 = in_array(9, array_map('intval', (array) $request->input('groups', [])), true);

        return [
            'name' => ['required', 'max:255', Rule::unique(self::TABLE, 'name')->ignore($ignoreId)],
            'name_en' => ['nullable', 'max:255'],
            'cas_no' => ['nullable', 'max:100'],
            'chemical_formula' => ['nullable', 'max:255'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', Rule::in(ChemicalClassification::SINGLE_SUBSTANCE_GROUPS)],
            'threshold_kg' => $isGroup9
                ? ['required', 'numeric', 'gt:0', 'max:999999999']
                : ['nullable', 'numeric', 'gt:0', 'max:999999999'],
        ];
    }

    private function payload(Request $request, array $groups): array
    {
        $isGroup9 = in_array(9, $groups, true);

        return [
            'name' => trim((string) $request->name),
            'name_en' => $this->nullable($request->name_en),
            'cas_no' => $this->nullable($request->cas_no),
            'chemical_formula' => $this->nullable($request->chemical_formula),
            // Ngưỡng chỉ có nghĩa với hoạt chất thuộc nhóm 9 (Phụ lục IV Bảng A)
            'threshold_kg' => $isGroup9 && trim((string) $request->threshold_kg) !== '' ? $request->threshold_kg : null,
        ];
    }

    private function nullable($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên hoạt chất.',
            'name.max' => 'Tên hoạt chất tối đa 255 ký tự.',
            'name.unique' => 'Tên hoạt chất này đã tồn tại.',
            'name_en.max' => 'Tên chất tối đa 255 ký tự.',
            'cas_no.max' => 'Số CAS tối đa 100 ký tự.',
            'chemical_formula.max' => 'Công thức hoá học tối đa 255 ký tự.',
            'groups.*.in' => 'Nhóm phân loại không hợp lệ (chỉ khai nhóm đơn chất tại màn này).',
            'threshold_kg.required' => 'Hoạt chất thuộc nhóm 9 (Phụ lục IV Bảng A) bắt buộc phải có ngưỡng tồn trữ, không được để trống.',
            'threshold_kg.numeric' => 'Ngưỡng tồn trữ phải là số.',
            'threshold_kg.gt' => 'Ngưỡng tồn trữ phải lớn hơn 0.',
            'threshold_kg.max' => 'Ngưỡng tồn trữ quá lớn.',
        ];
    }
}
