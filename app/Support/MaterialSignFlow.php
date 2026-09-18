<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * QUY TRÌNH TRÌNH KÝ ĐỀ NGHỊ CẤP PHÁT VẬT TƯ
 *
 * Nguồn sự thật duy nhất cho hai bảng material_request_sign_flows và
 * material_request_sign_flow_steps (xem migration create_material_request_sign_flow_tables).
 *
 * Một quy trình = ĐIỀU KIỆN ÁP DỤNG + DANH SÁCH BƯỚC KÝ.
 *
 * Điều kiện áp dụng có hai vế, mỗi vế để trống nghĩa là "Tất cả":
 *   - criteria          : phân loại của danh mục vật tư CHUNG (material_categories.classification),
 *                         dùng lại đúng bộ tiêu chí của App\Support\MaterialClassification
 *                         để hai màn hình không lệch nhau.
 *   - classification_id : phân loại của danh mục vật tư PHÒNG (department_classification.id).
 *
 * Một vật tư có thể khớp nhiều quy trình (ví dụ quy trình mặc định không điều kiện và
 * quy trình riêng cho vật tư giá cao). Quy trình nào khai NHIỀU điều kiện hơn thì cụ thể
 * hơn nên được ưu tiên - xem specificity() và resolve().
 */
class MaterialSignFlow
{
    /** Giá trị của ô "Tất cả" trên form - tiêu chí không tham gia điều kiện lọc. */
    public const ANY = '';

    /** Nhãn hiển thị khi một vế điều kiện không khai. */
    public const ANY_LABEL = 'Tất cả';

    /** Số bước ký tối đa của một quy trình - chặn khai nhầm hàng chục bước. */
    public const MAX_STEPS = 10;

    /* ==========================================================
     |  ĐIỀU KIỆN THEO PHÂN LOẠI DANH MỤC CHUNG (cột criteria)
     ========================================================== */

    /** Đọc cột JSON thành mảng [tiêu chí => giá trị], bỏ hết khoá / giá trị lạ. */
    public static function decode(?string $json): array
    {
        $decoded = json_decode((string) $json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $result = [];

        foreach (MaterialClassification::CRITERIA as $key => $criterion) {
            $value = $decoded[$key] ?? null;

            if (is_string($value) && isset($criterion['options'][$value])) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Gom lựa chọn từ form thành chuỗi JSON để lưu. Tiêu chí chọn "Tất cả" (chuỗi rỗng)
     * bị loại hẳn khỏi JSON - còn mặt trong JSON nghĩa là có tham gia điều kiện lọc.
     *
     * Luôn đi theo thứ tự khai báo của CRITERIA để cùng một nội dung luôn ra cùng một
     * chuỗi, nhờ vậy so sánh cũ / mới lúc ghi lịch sử không báo thay đổi giả và
     * signature() của hai quy trình trùng điều kiện luôn bằng nhau.
     */
    public static function encode($input): ?string
    {
        $input = is_array($input) ? $input : [];
        $result = [];

        foreach (MaterialClassification::CRITERIA as $key => $criterion) {
            $value = $input[$key] ?? null;

            if (is_string($value) && isset($criterion['options'][$value])) {
                $result[$key] = $value;
            }
        }

        return $result ? json_encode($result, JSON_UNESCAPED_UNICODE) : null;
    }

    /**
     * Các nhóm radio để dựng form khai điều kiện: mỗi tiêu chí thêm ô "Tất cả" đứng đầu.
     * Khác với MaterialClassification::formGroups() - bên đó bắt buộc chọn một giá trị,
     * bên này được phép bỏ qua tiêu chí.
     */
    public static function formGroups(array $selected = []): array
    {
        $groups = [];

        foreach (MaterialClassification::CRITERIA as $key => $criterion) {
            $groups[] = [
                'label' => $criterion['label'],
                'icon' => $criterion['icon'],
                'name' => 'criteria['.$key.']',
                'errorKey' => 'criteria.'.$key,
                'options' => [self::ANY => self::ANY_LABEL] + $criterion['options'],
                'value' => (string) ($selected[$key] ?? self::ANY),
            ];
        }

        return $groups;
    }

    /** Danh sách chip để hiện trên bảng: [['short', 'label', 'class'], ...]. */
    public static function chips(?string $json): array
    {
        $chips = [];

        foreach (self::decode($json) as $key => $value) {
            $chips[] = [
                'short' => MaterialClassification::SHORT_LABELS[$key][$value]
                    ?? MaterialClassification::CRITERIA[$key]['options'][$value],
                'label' => MaterialClassification::CRITERIA[$key]['label'].': '
                    .MaterialClassification::CRITERIA[$key]['options'][$value],
                'class' => MaterialClassification::CHIP_CLASSES[$key][$value] ?? '',
            ];
        }

        return $chips;
    }

    /** Mô tả đầy đủ dùng cho lịch sử thay đổi và Audit Trail. */
    public static function describe(?string $json): string
    {
        $parts = [];

        foreach (self::decode($json) as $key => $value) {
            $parts[] = MaterialClassification::CRITERIA[$key]['label'].': '
                .MaterialClassification::CRITERIA[$key]['options'][$value];
        }

        return $parts ? implode('; ', $parts) : self::ANY_LABEL;
    }

    /* ==========================================================
     |  KIỂM TRA TRÙNG ĐIỀU KIỆN
     ========================================================== */

    /**
     * Chuỗi nhận dạng của một tổ hợp điều kiện. Hai quy trình cùng chuỗi này là cùng
     * điều kiện áp dụng, không được tồn tại song song trong một phòng ban.
     */
    public static function signature(?string $criteriaJson, $classificationId): string
    {
        return (string) self::encode(self::decode($criteriaJson))
            .'|'.((int) $classificationId ?: 0);
    }

    /** Số điều kiện đã khai - quy trình càng nhiều điều kiện thì càng cụ thể. */
    public static function specificity(?string $criteriaJson, $classificationId): int
    {
        return count(self::decode($criteriaJson)) + ((int) $classificationId ? 1 : 0);
    }

    /* ==========================================================
     |  VALIDATE FORM KHAI BÁO
     ========================================================== */

    /**
     * Quy tắc validate phần điều kiện + các bước ký.
     * Tên quy trình và phân loại của phòng do Controller tự khai vì còn ràng buộc theo
     * phòng ban đang chọn.
     *
     * Mỗi bước là một cặp cùng chỉ số: steps[i] = vai trò phê duyệt, signers[i][] = DANH
     * SÁCH người được ký bước đó (chỉ cần một trong số họ ký là xong bước).
     */
    public static function rules(): array
    {
        $rules = [
            'criteria' => ['nullable', 'array'],
            'steps' => ['required', 'array', 'min:1', 'max:'.self::MAX_STEPS],
            'steps.*' => ['required', 'integer', Rule::exists('roles', 'id')],
            'signers' => ['required', 'array', 'min:1', 'max:'.self::MAX_STEPS],
            'signers.*' => ['required', 'array', 'min:1'],
            'signers.*.*' => ['required', 'integer', Rule::exists('user_management', 'id')],
        ];

        foreach (MaterialClassification::CRITERIA as $key => $criterion) {
            $rules['criteria.'.$key] = ['nullable', Rule::in(array_keys($criterion['options']))];
        }

        return $rules;
    }

    /** Thông báo lỗi tiếng Việt cho các quy tắc trên. */
    public static function messages(): array
    {
        $messages = [
            'steps.required' => 'Quy trình phải có ít nhất 1 bước trình ký.',
            'steps.min' => 'Quy trình phải có ít nhất 1 bước trình ký.',
            'steps.max' => 'Quy trình tối đa '.self::MAX_STEPS.' bước trình ký.',
            'steps.*.required' => 'Vui lòng chọn vai trò phê duyệt cho từng bước.',
            'steps.*.exists' => 'Vai trò phê duyệt không hợp lệ.',
            'signers.required' => 'Vui lòng chọn người ký cho từng bước.',
            'signers.*.required' => 'Mỗi bước phải có ít nhất 1 người ký.',
            'signers.*.min' => 'Mỗi bước phải có ít nhất 1 người ký.',
            'signers.*.*.required' => 'Vui lòng chọn người ký cho từng bước.',
            'signers.*.*.exists' => 'Người ký được chọn không hợp lệ.',
        ];

        foreach (MaterialClassification::CRITERIA as $key => $criterion) {
            $messages['criteria.'.$key.'.in'] = 'Điều kiện "'.$criterion['label'].'" không hợp lệ.';
        }

        return $messages;
    }

    /* ==========================================================
     |  TRA QUY TRÌNH ÁP DỤNG CHO MỘT VẬT TƯ
     ========================================================== */

    /**
     * Điều kiện của quy trình có khớp với vật tư không.
     *
     * Chỉ soi các tiêu chí quy trình CÓ khai; tiêu chí bỏ trống ("Tất cả") thì vật tư
     * khai gì cũng khớp. Vế phân loại của phòng cũng vậy.
     */
    public static function matches($flow, ?string $categoryCriteriaJson, $categoryClassificationId): bool
    {
        if ($flow->classification_id && (int) $flow->classification_id !== (int) $categoryClassificationId) {
            return false;
        }

        $category = self::decode($categoryCriteriaJson);

        foreach (self::decode($flow->criteria) as $key => $value) {
            if (($category[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Quy trình trình ký áp dụng cho một vật tư của phòng, kèm các bước ký đã sắp thứ tự.
     *
     * Đây là điểm vào cho màn hình Đề Nghị Cấp Phát Vật Tư: truyền phân loại của danh mục
     * chung (material_categories.classification) và phân loại của phòng
     * (material_department_categories.classification_id) để biết phiếu phải đi qua những
     * vai trò nào. Không quy trình nào khớp thì trả về null - phiếu giữ cách khai tay cũ.
     *
     * Nhiều quy trình cùng khớp thì lấy quy trình CỤ THỂ NHẤT (khai nhiều điều kiện nhất);
     * cùng độ cụ thể thì lấy quy trình khai trước để kết quả ổn định.
     *
     * @return null|object{id:int, name:string, criteria:?string, classification_id:?int, steps:\Illuminate\Support\Collection}
     */
    public static function resolve(int $departmentId, ?string $categoryCriteriaJson, $categoryClassificationId)
    {
        $flows = DB::table('material_request_sign_flows')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('id', 'asc')
            ->get();

        $matched = $flows
            ->filter(fn ($flow) => self::matches($flow, $categoryCriteriaJson, $categoryClassificationId))
            ->sortByDesc(fn ($flow) => self::specificity($flow->criteria, $flow->classification_id))
            ->first();

        if (! $matched) {
            return null;
        }

        $matched->steps = self::stepsOf((int) $matched->id);

        return $matched;
    }

    /**
     * QUY TRÌNH KÝ CỦA TỪNG VẬT TƯ TRONG MỘT PHÒNG - nạp một lần cho cả màn hình.
     *
     * Màn hình Đề Nghị Cấp Phát Vật Tư phải biết ngay quy trình của mọi vật tư trong danh
     * mục của phòng (để JS dựng lại khối "Quy trình ký duyệt" khi người lập đổi dòng vật
     * tư mà không phải gọi lại server), nên tra hàng loạt thay vì resolve() từng dòng.
     *
     * Trả về:
     *   'byCategory' : material_categories.id => ['flow' => đầu quy trình, 'steps' => bước ký]
     *   'fallback'   : quy trình cho dòng KHÔNG thuộc danh mục (tên tự nhập) - chỉ khớp
     *                  được quy trình không khai điều kiện nào; null nếu phòng chưa khai.
     *
     * Vật tư không khớp quy trình nào thì không có mặt trong 'byCategory'.
     */
    public static function mapForDepartment(int $departmentId): array
    {
        $flows = DB::table('material_request_sign_flows')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('id', 'asc')
            ->get();

        $stepsByFlow = self::stepsOfMany($flows->pluck('id'));

        // Quy trình chỉ tính là dùng được khi thật sự có bước ký còn hiệu lực
        $usable = $flows->filter(fn ($flow) => ($stepsByFlow[$flow->id] ?? collect())->isNotEmpty())->values();

        $pick = function (?string $criteriaJson, $classificationId) use ($usable, $stepsByFlow) {
            $flow = $usable
                ->filter(fn ($f) => self::matches($f, $criteriaJson, $classificationId))
                ->sortByDesc(fn ($f) => self::specificity($f->criteria, $f->classification_id))
                ->first();

            return $flow ? ['flow' => $flow, 'steps' => $stepsByFlow[$flow->id]] : null;
        };

        $categories = DB::table('material_department_categories')
            ->join('material_categories', 'material_categories.id', '=', 'material_department_categories.category_id')
            ->where('material_department_categories.department_id', $departmentId)
            ->where('material_department_categories.status_id', 1)
            ->get([
                'material_categories.id',
                'material_categories.classification',
                'material_department_categories.classification_id',
            ]);

        $byCategory = [];

        foreach ($categories as $category) {
            if ($matched = $pick($category->classification, $category->classification_id)) {
                $byCategory[(int) $category->id] = $matched;
            }
        }

        return [
            'byCategory' => $byCategory,
            'fallback' => $pick(null, null),
        ];
    }

    /**
     * Quy trình áp dụng cho CẢ MỘT PHIẾU có nhiều dòng vật tư.
     *
     * Mỗi dòng khớp một quy trình riêng, phiếu lấy quy trình CHẶT NHẤT - nhiều bước ký
     * nhất - để vật tư nào cần duyệt kỹ thì cả phiếu duyệt kỹ. Dòng nào không khớp quy
     * trình nào thì trả về trong 'missing' để Controller chặn không cho trình ký.
     *
     * @param  array  $categoryIds  category_id của từng dòng; phần tử rỗng = dòng tên tự nhập
     * @return array{steps: \Illuminate\Support\Collection, flow: ?object, missing: bool}
     */
    public static function resolveForItems(int $departmentId, array $categoryIds): array
    {
        $map = self::mapForDepartment($departmentId);
        $best = null;
        $missing = false;

        foreach ($categoryIds as $categoryId) {
            $matched = (int) $categoryId > 0
                ? ($map['byCategory'][(int) $categoryId] ?? null)
                : $map['fallback'];

            if (! $matched) {
                $missing = true;

                continue;
            }

            if (! $best || $matched['steps']->count() > $best['steps']->count()) {
                $best = $matched;
            }
        }

        return [
            'steps' => $best ? $best['steps'] : collect(),
            'flow' => $best ? $best['flow'] : null,
            'missing' => $missing,
        ];
    }

    /** Các bước ký còn hiệu lực của một quy trình, kèm vai trò + người ký, theo thứ tự ký. */
    public static function stepsOf(int $flowId)
    {
        return self::stepsOfMany([$flowId])->get($flowId, collect());
    }

    /**
     * Bước ký của NHIỀU quy trình cùng lúc: flow_id => các bước.
     * Dùng cho màn hình liệt kê nhiều quy trình để khỏi truy vấn lại từng dòng.
     *
     * Mỗi bước kèm ->signers: danh sách NGƯỜI ĐƯỢC KÝ bước đó. Một bước giao cho nhiều
     * người thì chỉ cần MỘT trong số họ ký là xong bước.
     */
    public static function stepsOfMany($flowIds)
    {
        $flowIds = collect($flowIds)->filter()->values();

        if ($flowIds->isEmpty()) {
            return collect();
        }

        $steps = DB::table('material_request_sign_flow_steps')
            ->leftJoin('roles', 'roles.id', '=', 'material_request_sign_flow_steps.role_id')
            ->select(
                'material_request_sign_flow_steps.id',
                'material_request_sign_flow_steps.flow_id',
                'material_request_sign_flow_steps.step_no',
                'material_request_sign_flow_steps.role_id',
                'roles.name as role_name'
            )
            ->whereIn('material_request_sign_flow_steps.flow_id', $flowIds)
            ->where('material_request_sign_flow_steps.active', 1)
            ->orderBy('material_request_sign_flow_steps.step_no', 'asc')
            ->get();

        $signers = self::signersOfSteps($steps->pluck('id'));

        foreach ($steps as $step) {
            $step->signers = $signers->get($step->id, collect());
        }

        return $steps->groupBy('flow_id');
    }

    /** Người ký của một loạt bước: step_id => danh sách người, kèm tên và phòng ban hiện tại. */
    public static function signersOfSteps($stepIds)
    {
        $stepIds = collect($stepIds)->filter()->values();

        if ($stepIds->isEmpty()) {
            return collect();
        }

        return DB::table('material_request_sign_flow_step_users')
            ->leftJoin('user_management', 'user_management.id', '=', 'material_request_sign_flow_step_users.user_id')
            ->leftJoin('deparments', 'deparments.id', '=', 'user_management.deparment_id')
            ->select(
                'material_request_sign_flow_step_users.step_id',
                'material_request_sign_flow_step_users.user_id',
                'material_request_sign_flow_step_users.user_name',
                'user_management.fullName as signer_full_name',
                'user_management.isActive as signer_active',
                'deparments.shortName as signer_department_short'
            )
            ->whereIn('material_request_sign_flow_step_users.step_id', $stepIds)
            ->where('material_request_sign_flow_step_users.active', 1)
            ->orderBy('material_request_sign_flow_step_users.id', 'asc')
            ->get()
            ->groupBy('step_id');
    }

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

    /** Một dòng gọn cho lịch sử thay đổi: "1. Trưởng Phòng - A / B -> 2. ...". */
    public static function stepsText($steps): string
    {
        $parts = collect($steps)
            ->map(fn ($step) => $step->step_no.'. '.($step->role_name ?: 'NA').' - '.self::stepSignerNames($step))
            ->all();

        return $parts ? implode(' -> ', $parts) : '';
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
     * vai trò của từng bước. Cùng cách xác định vai trò với EstimateSignFlow::signerOptions().
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
