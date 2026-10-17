<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * TRÌNH KÝ ĐỀ NGHỊ CẤP PHÁT VẬT TƯ - VAI TRÒ + ĐIỀU KIỆN
 *
 * Nguồn sự thật duy nhất cho hai bảng material_request_sign_rules và
 * material_request_sign_rule_users (xem migration convert_material_sign_flows_to_role_rules).
 *
 * Mỗi phòng khai danh sách VAI TRÒ nhận trình ký, mỗi vai trò một dòng gồm:
 *   - level      : CẤP KÝ. Phiếu ký lần lượt từ cấp nhỏ tới cấp lớn.
 *   - conditions : khi nào vai trò này nhận trình ký. Bỏ trống = nhận mọi đề nghị.
 *   - người ký   : một hoặc nhiều người thuộc vai trò, chỉ cần MỘT người ký là xong bước.
 *
 * ĐIỀU KIỆN là danh sách NHÓM, lưu JSON [{"price":"high","importance":"high"},{"dangerous":"yes"}]:
 *   - trong một nhóm các tiêu chí nối bằng VÀ, các nhóm nối với nhau bằng HOẶC
 *   - giá trị ANY_VALUE ("Tất cả") = tiêu chí đó không giới hạn, mọi vật tư đều thoả
 *   - khoá là tiêu chí phân loại danh mục vật tư CHUNG (MaterialClassification::CRITERIA,
 *     material_categories.classification), riêng DEPT_KEY là phân loại danh mục vật tư
 *     của PHÒNG (material_department_categories.classification_id)
 *
 * Một đề nghị KÍCH HOẠT vai trò khi có ít nhất MỘT dòng vật tư thoả một nhóm (cả nhóm phải
 * đúng trên cùng một dòng). Các vai trò được kích hoạt xếp theo cấp ký thành dãy bước ký
 * của phiếu. Không vai trò nào được kích hoạt thì phiếu được duyệt thẳng khi trình ký.
 * Dòng tên tự nhập ngoài danh mục không có phân loại nên chỉ gặp các vai trò "mọi đề nghị".
 */
class MaterialSignFlow
{
    /** Khoá điều kiện ứng với phân loại danh mục vật tư của phòng. */
    public const DEPT_KEY = 'classification_id';

    /** Nhãn khi vai trò không khai điều kiện nào. */
    public const ALWAYS_LABEL = 'Mọi đề nghị';

    /** Giá trị "Tất cả" của một tiêu chí trong nhóm: tiêu chí đó nhận mọi giá trị. */
    public const ANY_VALUE = '*';

    public const ANY_LABEL = 'Tất cả';

    /** Chặn khai nhầm quá nhiều nhóm / cấp. */
    public const MAX_GROUPS = 10;

    public const MAX_LEVEL = 50;

    private const RULE_TABLE = 'material_request_sign_rules';

    private const USER_TABLE = 'material_request_sign_rule_users';

    /* ==========================================================
     |  TIÊU CHÍ DÙNG ĐƯỢC TRONG ĐIỀU KIỆN
     ========================================================== */

    /**
     * Các tiêu chí khai được điều kiện của một phòng: [khoá => ['label', 'options' => [giá trị => nhãn]]].
     * Gồm 5 tiêu chí của danh mục chung + phân loại của phòng (nếu phòng đã khai).
     */
    public static function criteriaOptions(int $departmentId): array
    {
        $options = [];

        foreach (MaterialClassification::CRITERIA as $key => $criterion) {
            $options[$key] = [
                'label' => $criterion['label'],
                'options' => array_map('strval', $criterion['options']),
                'short' => MaterialClassification::SHORT_LABELS[$key] ?? [],
            ];
        }

        $departmentClasses = DB::table('department_classification')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->pluck('name', 'id');

        if ($departmentClasses->isNotEmpty()) {
            $options[self::DEPT_KEY] = [
                'label' => 'Phân Loại Của Phòng',
                'options' => $departmentClasses->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name])->all(),
                'short' => [],
            ];
        }

        return $options;
    }

    /* ==========================================================
     |  ĐỌC / GHI ĐIỀU KIỆN
     ========================================================== */

    /**
     * Đọc cột JSON thành danh sách nhóm [[khoá => giá trị], ...]. Chỉ giữ cấu trúc hợp lệ,
     * không đối chiếu giá trị với danh sách lựa chọn (phân loại của phòng có thể đã khoá).
     */
    public static function decodeConditions($json): array
    {
        $decoded = is_array($json) ? $json : json_decode((string) $json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $groups = [];

        foreach ($decoded as $group) {
            if (! is_array($group)) {
                continue;
            }

            $clean = [];
            foreach ($group as $key => $value) {
                if (is_string($key) && (is_string($value) || is_int($value))) {
                    $clean[$key] = (string) $value;
                }
            }

            if ($clean) {
                $groups[] = $clean;
            }
        }

        return $groups;
    }

    /**
     * Đọc điều kiện người dùng dựng trên form: conditions[nhóm][dòng][key|value].
     *
     * Trả về [nhóm đã chuẩn hoá hoặc null nếu không có nhóm nào, danh sách lỗi]. Dòng chưa
     * chọn tiêu chí bị bỏ qua; nhóm rỗng bị bỏ; nhóm trùng nhau chỉ giữ một. Cùng một tiêu
     * chí chọn hai lần trong một nhóm là lỗi (VÀ hai giá trị khác nhau thì không vật tư nào
     * thoả). Khoá được sắp theo thứ tự tiêu chí để cùng nội dung luôn ra cùng một chuỗi JSON.
     */
    public static function parseConditions($input, array $criteriaOptions): array
    {
        $groups = [];
        $errors = [];
        $order = array_flip(array_keys($criteriaOptions));

        foreach (array_values(is_array($input) ? $input : []) as $index => $rows) {
            $group = [];

            foreach ((is_array($rows) ? $rows : []) as $row) {
                $key = (string) ($row['key'] ?? '');
                $value = (string) ($row['value'] ?? '');

                if ($key === '') {
                    continue;
                }

                if (! isset($criteriaOptions[$key])) {
                    $errors[] = 'Nhóm '.($index + 1).': tiêu chí không hợp lệ.';

                    continue;
                }

                if ($value !== self::ANY_VALUE && ! isset($criteriaOptions[$key]['options'][$value])) {
                    $errors[] = 'Nhóm '.($index + 1).': chưa chọn giá trị cho "'.$criteriaOptions[$key]['label'].'".';

                    continue;
                }

                if (isset($group[$key]) && $group[$key] !== $value) {
                    $errors[] = 'Nhóm '.($index + 1).': "'.$criteriaOptions[$key]['label']
                        .'" chọn hai giá trị khác nhau trong cùng một nhóm VÀ.';

                    continue;
                }

                $group[$key] = $value;
            }

            if ($group) {
                uksort($group, fn ($a, $b) => $order[$a] <=> $order[$b]);
                $groups[json_encode($group)] = $group;
            }
        }

        if (count($groups) > self::MAX_GROUPS) {
            $errors[] = 'Tối đa '.self::MAX_GROUPS.' nhóm điều kiện.';
        }

        return [$groups ? array_values($groups) : null, $errors];
    }

    /** Chuỗi JSON để lưu, NULL khi vai trò nhận mọi đề nghị. */
    public static function encodeConditions(?array $groups): ?string
    {
        return $groups ? json_encode(array_values($groups), JSON_UNESCAPED_UNICODE) : null;
    }

    /** Nhãn ngắn của một tiêu chí = giá trị, dùng cho chip trên bảng. */
    public static function chipOf(string $key, string $value, array $criteriaOptions): array
    {
        $criterion = $criteriaOptions[$key] ?? null;

        if ($value === self::ANY_VALUE) {
            $name = preg_replace('/^Theo\s+/u', '', (string) ($criterion['label'] ?? $key));

            return [
                'short' => $name.': '.self::ANY_LABEL,
                'label' => ($criterion['label'] ?? $key).': '.self::ANY_LABEL,
                'class' => 'any',
            ];
        }

        $valueLabel = $criterion['options'][$value] ?? ($key === self::DEPT_KEY ? 'Phân loại đã khoá' : $value);

        return [
            'short' => $criterion['short'][$value] ?? $valueLabel,
            'label' => ($criterion['label'] ?? $key).': '.$valueLabel,
            'class' => $key === self::DEPT_KEY ? 'dept' : (MaterialClassification::CHIP_CLASSES[$key][$value] ?? ''),
        ];
    }

    /** Các nhóm điều kiện dưới dạng chip: [[chip, chip], [chip]]. */
    public static function chipGroups($json, array $criteriaOptions): array
    {
        return array_map(
            fn ($group) => array_map(
                fn ($key) => self::chipOf($key, $group[$key], $criteriaOptions),
                array_keys($group)
            ),
            self::decodeConditions($json)
        );
    }

    /** Mô tả một dòng cho lịch sử thay đổi: "(Giá cao VÀ Rất quan trọng) HOẶC (Hàng nguy hiểm)". */
    public static function describeConditions($json, array $criteriaOptions): string
    {
        $groups = array_map(
            fn ($chips) => '('.implode(' VÀ ', array_column($chips, 'label')).')',
            self::chipGroups($json, $criteriaOptions)
        );

        return $groups ? implode(' HOẶC ', $groups) : self::ALWAYS_LABEL;
    }

    /* ==========================================================
     |  KHỚP ĐIỀU KIỆN
     ========================================================== */

    /**
     * Một dòng vật tư có thoả TRỌN một nhóm điều kiện không.
     *
     * @param  array  $item  ['criteria' => [tiêu chí => giá trị], 'classification_id' => ?int]
     */
    public static function groupMatches(array $group, array $item): bool
    {
        foreach ($group as $key => $value) {
            if ((string) $value === self::ANY_VALUE) {
                continue;
            }

            $actual = $key === self::DEPT_KEY
                ? (string) ((int) ($item['classification_id'] ?? 0) ?: '')
                : (string) ($item['criteria'][$key] ?? '');

            if ($actual === '' || $actual !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    /** Một dòng vật tư có kích hoạt vai trò (thoả ít nhất một nhóm) không. */
    public static function itemTriggers(array $groups, array $item): bool
    {
        foreach ($groups as $group) {
            if (self::groupMatches($group, $item)) {
                return true;
            }
        }

        return false;
    }

    /* ==========================================================
     |  TRA DÃY BƯỚC KÝ CỦA MỘT ĐỀ NGHỊ
     ========================================================== */

    /**
     * Các vai trò ĐANG HOẠT ĐỘNG của phòng, xếp theo cấp ký, kèm ->groups (điều kiện đã đọc),
     * ->role_name và ->signers. Vai trò chưa có người ký nào còn hiệu lực bị bỏ qua - không
     * ai ký được thì phiếu sẽ treo.
     */
    public static function activeRules(int $departmentId)
    {
        $rules = self::rulesQuery()
            ->where(self::RULE_TABLE.'.department_id', $departmentId)
            ->where(self::RULE_TABLE.'.status_id', 1)
            ->get();

        return self::attachSigners($rules)
            ->filter(fn ($rule) => $rule->signers->isNotEmpty())
            ->values();
    }

    /** Mọi vai trò (kể cả đã khoá) của phòng cho màn hình Dữ Liệu Gốc. */
    public static function allRules(int $departmentId)
    {
        return self::attachSigners(
            self::rulesQuery()->where(self::RULE_TABLE.'.department_id', $departmentId)->get()
        );
    }

    /**
     * Dữ liệu phân loại của các vật tư trong danh mục phòng: category_id => item
     * ['criteria' => [...], 'classification_id' => ?int].
     */
    public static function categoryItems(int $departmentId, ?array $categoryIds = null): array
    {
        $rows = DB::table('material_department_categories')
            ->join('material_categories', 'material_categories.id', '=', 'material_department_categories.category_id')
            ->where('material_department_categories.department_id', $departmentId)
            ->where('material_department_categories.status_id', 1)
            ->when($categoryIds !== null, fn ($query) => $query->whereIn('material_categories.id', $categoryIds))
            ->get([
                'material_categories.id',
                'material_categories.classification',
                'material_department_categories.classification_id',
            ]);

        $items = [];

        foreach ($rows as $row) {
            $items[(int) $row->id] = [
                'criteria' => MaterialClassification::decode($row->classification),
                'classification_id' => $row->classification_id ? (int) $row->classification_id : null,
            ];
        }

        return $items;
    }

    /**
     * DÃY BƯỚC KÝ của một phiếu có nhiều dòng vật tư.
     *
     * Vai trò nào không khai điều kiện, hoặc có ít nhất một dòng vật tư thoả một nhóm điều
     * kiện của nó, thì thành một bước ký. Bước xếp theo cấp ký, đánh số lại 1, 2, 3...
     * Rỗng = không cần ký duyệt, phiếu được duyệt thẳng.
     *
     * @param  array  $categoryIds  category_id của từng dòng; phần tử rỗng = dòng tên tự nhập
     * @return array{steps: \Illuminate\Support\Collection}
     */
    public static function resolveForItems(int $departmentId, array $categoryIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        $items = $ids ? self::categoryItems($departmentId, $ids) : [];

        $triggered = self::activeRules($departmentId)->filter(function ($rule) use ($items) {
            if (! $rule->groups) {
                return true;
            }

            foreach ($items as $item) {
                if (self::itemTriggers($rule->groups, $item)) {
                    return true;
                }
            }

            return false;
        });

        return ['steps' => self::asSteps($triggered)];
    }

    /**
     * Bảng tra cho JS dựng lại khối "Quy trình ký duyệt" trên modal đề nghị ngay khi người
     * lập đổi dòng vật tư, không phải gọi lại server:
     *   'rules'      : các vai trò đang hoạt động, theo cấp ký
     *   'always'     : id vai trò nhận mọi đề nghị
     *   'byCategory' : category_id => id các vai trò mà vật tư đó kích hoạt
     */
    public static function mapForDepartment(int $departmentId): array
    {
        $rules = self::activeRules($departmentId);
        $always = [];
        $byCategory = [];

        foreach ($rules as $rule) {
            if (! $rule->groups) {
                $always[] = (int) $rule->id;
            }
        }

        foreach (self::categoryItems($departmentId) as $categoryId => $item) {
            foreach ($rules as $rule) {
                if ($rule->groups && self::itemTriggers($rule->groups, $item)) {
                    $byCategory[$categoryId][] = (int) $rule->id;
                }
            }
        }

        return ['rules' => $rules, 'always' => $always, 'byCategory' => $byCategory];
    }

    /** Đổi danh sách vai trò được kích hoạt thành các bước ký đánh số 1, 2, 3... */
    private static function asSteps($rules)
    {
        return collect($rules)->values()->map(function ($rule, $index) {
            $step = clone $rule;
            $step->step_no = $index + 1;

            return $step;
        });
    }

    private static function rulesQuery()
    {
        return DB::table(self::RULE_TABLE)
            ->leftJoin('roles', 'roles.id', '=', self::RULE_TABLE.'.role_id')
            ->select(self::RULE_TABLE.'.*', 'roles.name as role_name')
            ->orderBy(self::RULE_TABLE.'.level', 'asc')
            ->orderBy(self::RULE_TABLE.'.id', 'asc');
    }

    /** Gắn ->groups và ->signers cho từng vai trò. */
    private static function attachSigners($rules)
    {
        $signers = self::signersOfRules($rules->pluck('id'));

        foreach ($rules as $rule) {
            $rule->groups = self::decodeConditions($rule->conditions);
            $rule->signers = $signers->get($rule->id, collect());
        }

        return $rules;
    }

    /** Người ký của một loạt vai trò: rule_id => danh sách người, kèm tên và phòng ban hiện tại. */
    public static function signersOfRules($ruleIds)
    {
        $ruleIds = collect($ruleIds)->filter()->values();

        if ($ruleIds->isEmpty()) {
            return collect();
        }

        return DB::table(self::USER_TABLE)
            ->leftJoin('user_management', 'user_management.id', '=', self::USER_TABLE.'.user_id')
            ->leftJoin('deparments', 'deparments.id', '=', 'user_management.deparment_id')
            ->select(
                self::USER_TABLE.'.rule_id',
                self::USER_TABLE.'.user_id',
                self::USER_TABLE.'.user_name',
                'user_management.fullName as signer_full_name',
                'user_management.isActive as signer_active',
                'deparments.shortName as signer_department_short'
            )
            ->whereIn(self::USER_TABLE.'.rule_id', $ruleIds)
            ->where(self::USER_TABLE.'.active', 1)
            ->orderBy(self::USER_TABLE.'.id', 'asc')
            ->get()
            ->groupBy('rule_id');
    }

    /* ==========================================================
     |  HIỂN THỊ NGƯỜI KÝ
     ========================================================== */

    /** Tên một người ký: ưu tiên tên hiện tại, chưa có thì dùng ảnh chụp lúc khai. */
    public static function signerLabel($signer): string
    {
        return (string) ($signer->signer_full_name ?: $signer->user_name ?: 'NA');
    }

    /** Tên mọi người được ký một bước, ghép bằng dấu " / " - ai ký trước cũng được. */
    public static function stepSignerNames($step): string
    {
        $names = collect($step->signers ?? [])->map(fn ($signer) => self::signerLabel($signer))->all();

        return $names ? implode(' / ', $names) : 'NA';
    }

    /* ==========================================================
     |  NGƯỜI ĐƯỢC CHỌN LÀM NGƯỜI KÝ
     ========================================================== */

    /**
     * Người có thể được chọn làm người ký: user đang hoạt động thuộc CÙNG CÔNG TY với
     * phòng ban đang chọn - để trình ký lên cấp trên ngoài phòng (Ban Giám Đốc...).
     *
     * Mỗi người kèm 'role_ids' = MỌI vai trò của họ (role chính + role gán qua user_role
     * có phạm vi NULL hoặc đúng phòng ban đang chọn), dùng để lọc danh sách người ký theo
     * vai trò. Cùng cách xác định vai trò với EstimateSignFlow::signerOptions().
     */
    public static function signerOptions()
    {
        $companyId = CompanyContext::currentId();
        $departmentIds = CompanyContext::departmentIds($companyId);
        $currentDeptId = (int) (session('user')['selected_department_id'] ?? 0);

        $people = DB::table('user_management')
            ->leftJoin('deparments', 'deparments.id', '=', 'user_management.deparment_id')
            ->leftJoin('roles', 'roles.id', '=', 'user_management.role_id')
            ->select(
                'user_management.id',
                'user_management.fullName',
                'user_management.userName',
                'user_management.role_id',
                'deparments.shortName as department_short',
                'roles.name as role_name'
            )
            ->where('user_management.isActive', 1)
            ->when($companyId, fn ($query) => $query->where(function ($sub) use ($companyId, $departmentIds) {
                if ($departmentIds) {
                    $sub->whereIn('user_management.deparment_id', $departmentIds);
                }

                // Tài khoản chưa gắn phòng ban thì mới xét tới cột công ty của chính họ
                $sub->orWhere(fn ($q) => $q->whereNull('user_management.deparment_id')
                    ->where('user_management.company_id', $companyId));
            }))
            ->orderBy('user_management.fullName', 'asc')
            ->get();

        $assigned = DB::table('user_role')
            ->whereIn('user_role.user_id', $people->pluck('id'))
            ->where(function ($query) use ($currentDeptId) {
                $query->whereNull('user_role.department_id');
                if ($currentDeptId) {
                    $query->orWhere('user_role.department_id', $currentDeptId);
                }
            })
            ->get(['user_role.user_id', 'user_role.role_id'])
            ->groupBy('user_id');

        foreach ($people as $person) {
            $ids = $assigned->get($person->id, collect())->pluck('role_id')->map(fn ($id) => (int) $id)->all();

            if ($person->role_id) {
                $ids[] = (int) $person->role_id;
            }

            $person->role_ids = array_values(array_unique(array_filter($ids)));
        }

        return $people;
    }

    /** "Họ Tên (userName)" - cùng dạng với App\Support\Signer::actor() để đối chiếu chữ ký. */
    public static function personName($person): string
    {
        $fullName = trim((string) ($person->fullName ?? ''));
        $userName = trim((string) ($person->userName ?? ''));

        if ($userName === '') {
            return $fullName ?: 'NA';
        }

        return $fullName !== '' ? $fullName.' ('.$userName.')' : $userName;
    }
}
