<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DataMasterHistory;
use App\Support\MaterialSignFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DỮ LIỆU GỐC - TRÌNH KÝ ĐỀ NGHỊ CẤP PHÁT VẬT TƯ
 *
 * Phòng ban khai trước quy trình ký chuẩn của phiếu đề nghị cấp phát vật tư: ứng với một
 * TỔ HỢP điều kiện của vật tư thì phiếu phải đi qua những bước ký nào, mỗi bước do VAI TRÒ
 * nào phê duyệt.
 *
 * Điều kiện gồm hai vế, bỏ trống vế nào là "Tất cả":
 *   - Phân loại của danh mục vật tư CHUNG : material_categories.classification (nhiều tiêu chí)
 *   - Phân loại của danh mục vật tư PHÒNG : department_classification.id
 *
 * Màn hình luôn làm việc trên phòng ban đang chọn ở topNAV - department_id lấy từ session,
 * không cho chọn tay để tránh khai nhầm sang phòng khác.
 *
 * Không có bước duyệt. Khoá (status_id = 0) thay cho xoá cứng; sửa quy trình thì bỏ hiệu
 * lực các bước cũ (active = 0) rồi ghi bước mới, không xoá cứng.
 *
 * Cách các màn hình khác đọc quy trình đã khai: App\Support\MaterialSignFlow::resolve().
 */
class MaterialSignFlowController extends Controller
{
    use RequiresChangeReason;

    private const TABLE = 'material_request_sign_flows';

    private const STEP_TABLE = 'material_request_sign_flow_steps';

    /** Người được ký một bước - một bước giao cho nhiều người thì mỗi người một dòng. */
    private const STEP_USER_TABLE = 'material_request_sign_flow_step_users';

    private const CLASSIFICATION_TABLE = 'department_classification';

    private const LABEL = 'quy trình trình ký';

    /** Danh sách người ký chọn được - nạp một lần cho cả request, xem signerOptions(). */
    private $signerOptionsCache = null;

    public function index()
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin(self::CLASSIFICATION_TABLE, self::CLASSIFICATION_TABLE.'.id', '=', self::TABLE.'.classification_id')
            ->select(self::TABLE.'.*', self::CLASSIFICATION_TABLE.'.name as classification_name')
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy(self::TABLE.'.name', 'asc')
            ->get();

        // Bước ký của mọi quy trình trên trang, nạp một lần rồi gom theo quy trình
        $steps = MaterialSignFlow::stepsOfMany($datas->pluck('id'));

        session()->put(['title' => 'DỮ LIỆU GỐC - TRÌNH KÝ ĐỀ NGHỊ CP VẬT TƯ']);

        return view('pages.materData.MaterialSignFlow.list', [
            'datas' => $datas,
            'steps' => $steps,
            'classifications' => $this->classificationOptions(),
            'roles' => DB::table('roles')->orderBy('name', 'asc')->get(['id', 'name']),
            'signerOptions' => $this->signerOptions(),
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        $criteria = MaterialSignFlow::encode($request->input('criteria'));
        $classificationId = $this->classificationId($request);

        $this->checkSigners($validator, $request);
        $this->checkDuplicate($validator, $criteria, $classificationId);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $name = trim((string) $request->name);

        $id = DB::transaction(function () use ($request, $name, $criteria, $classificationId) {
            $id = DB::table(self::TABLE)->insertGetId([
                'name' => $name,
                'department_id' => $this->departmentId(),
                'criteria' => $criteria,
                'classification_id' => $classificationId,
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->insertSteps($id, $request);

            return $id;
        });

        DataMasterHistory::write(
            self::TABLE,
            $id,
            'Thêm mới',
            'Khai báo mới '.self::LABEL.': '.$name.'.',
            $this->snapshotOf($id)
        );

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm '.self::LABEL.': '.$name);

        return redirect()->back()->with('success', 'Đã thêm '.self::LABEL.' thành công!');
    }

    public function update(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        $validator = Validator::make(
            $request->all(),
            $this->rules($current->id) + $this->changeReasonRules(),
            $this->messages() + $this->changeReasonMessages()
        );

        $criteria = MaterialSignFlow::encode($request->input('criteria'));
        $classificationId = $this->classificationId($request);

        $this->checkSigners($validator, $request);
        $this->checkDuplicate($validator, $criteria, $classificationId, $current->id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $before = $this->descriptorOf($current, MaterialSignFlow::stepsOf((int) $current->id));

        $payload = [
            'name' => trim((string) $request->name),
            'criteria' => $criteria,
            'classification_id' => $classificationId,
        ];

        DB::transaction(function () use ($current, $payload, $request) {
            DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Sửa quy trình là khai lại toàn bộ bước ký: bỏ hiệu lực bước cũ rồi ghi bước mới
            $this->deactivateSteps((int) $current->id);
            $this->insertSteps((int) $current->id, $request);
        });

        $after = $this->descriptorOf($this->findOwn($current->id), MaterialSignFlow::stepsOf((int) $current->id));
        $note = $this->diffNote($before, $after);

        if ($note === '') {
            return redirect()->back()->with('error', 'Chưa có thông tin nào thay đổi nên không lưu.')->withInput();
        }

        DataMasterHistory::write(
            self::TABLE,
            (int) $current->id,
            'Cập nhật',
            $note,
            $this->snapshotOf((int) $current->id),
            $this->changeReason($request)
        );

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $before['Tên quy trình'], $after['Tên quy trình']);

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
    }

    public function deActive(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
        }

        if ($stop = $this->guardChangeReason($request)) {
            return $stop;
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::write(
            self::TABLE,
            (int) $current->id,
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            DataMasterHistory::statusNote($current->status_id, $newStatus),
            $this->snapshotOf((int) $current->id),
            $this->changeReason($request)
        );

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            self::TABLE,
            $current->id,
            'status_id: '.$current->status_id,
            'status_id: '.$newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' '.$current->name.'!'
        );
    }

    /**
     * Trả về lịch sử thay đổi của một dòng cho modal xem lịch sử.
     * Chỉ đọc được quy trình của phòng ban đang chọn, giống các thao tác còn lại.
     */
    public function history(Request $request)
    {
        $id = (int) DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->value('id');

        return response()->json([
            'rows' => $id ? DataMasterHistory::rows(self::TABLE, $id) : [],
        ]);
    }

    /* ==========================================================
     |  GHI BƯỚC KÝ
     ========================================================== */

    /**
     * Ghi các bước ký theo đúng thứ tự người dùng xếp trên form.
     *
     * Mỗi bước là một cặp cùng chỉ số: steps[i] = vai trò, signers[i][] = danh sách người
     * được ký bước đó. Người ký tách sang bảng con, mỗi người một dòng.
     */
    private function insertSteps(int $flowId, Request $request): void
    {
        $people = $this->signerOptions()->keyBy('id');

        foreach ($this->stepPairs($request) as $index => $step) {
            $stepId = DB::table(self::STEP_TABLE)->insertGetId([
                'flow_id' => $flowId,
                'step_no' => $index + 1,
                'role_id' => $step['role_id'],
                'active' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($step['user_ids'] as $userId) {
                $person = $people->get($userId);

                DB::table(self::STEP_USER_TABLE)->insert([
                    'step_id' => $stepId,
                    'user_id' => $userId,
                    'user_name' => $person ? MaterialSignFlow::personName($person) : null,
                    'active' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /** Bỏ hiệu lực toàn bộ bước ký (và người ký của bước) của một quy trình. */
    private function deactivateSteps(int $flowId): void
    {
        $stepIds = DB::table(self::STEP_TABLE)->where('flow_id', $flowId)->where('active', 1)->pluck('id');

        if ($stepIds->isEmpty()) {
            return;
        }

        DB::table(self::STEP_USER_TABLE)->whereIn('step_id', $stepIds)->update([
            'active' => 0,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DB::table(self::STEP_TABLE)->whereIn('id', $stepIds)->update([
            'active' => 0,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Các bước trên form dưới dạng [['role_id' => .., 'user_ids' => [..]], ...], giữ nguyên
     * thứ tự dòng. Bỏ dòng thiếu vai trò hoặc chưa chọn người ký nào - validate đã chặn
     * trước rồi, đây chỉ là lớp chặn cuối để không ghi bước rỗng vào CSDL.
     */
    private function stepPairs(Request $request): array
    {
        $roles = array_values((array) $request->input('steps', []));
        $signers = array_values((array) $request->input('signers', []));
        $pairs = [];

        foreach ($roles as $index => $roleId) {
            $roleId = (int) $roleId;
            $userIds = [];

            foreach ((array) ($signers[$index] ?? []) as $value) {
                $userId = (int) $value;

                if ($userId > 0 && ! in_array($userId, $userIds, true)) {
                    $userIds[] = $userId;
                }
            }

            if ($roleId > 0 && $userIds) {
                $pairs[] = ['role_id' => $roleId, 'user_ids' => $userIds];
            }
        }

        return $pairs;
    }

    /* ==========================================================
     |  VALIDATE
     ========================================================== */

    private function rules($ignoreId = null): array
    {
        $departmentId = $this->departmentId();

        return [
            'name' => [
                'required',
                'max:150',
                Rule::unique(self::TABLE, 'name')
                    ->where(fn ($query) => $query->where('department_id', $departmentId))
                    ->ignore($ignoreId),
            ],
            'classification_id' => [
                'nullable',
                Rule::exists(self::CLASSIFICATION_TABLE, 'id')
                    ->where(fn ($query) => $query->where('department_id', $departmentId)->where('status_id', 1)),
            ],
        ] + MaterialSignFlow::rules();
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên quy trình.',
            'name.max' => 'Tên quy trình tối đa 150 ký tự.',
            'name.unique' => 'Tên quy trình này đã tồn tại trong phòng ban.',
            'classification_id.exists' => 'Phân loại của phòng không hợp lệ.',
        ] + MaterialSignFlow::messages();
    }

    /**
     * Mỗi bước phải có vai trò + ít nhất một người ký, và MỌI người ký của bước phải THUỘC
     * vai trò của bước đó. Một người không được đứng ở hai bước khác nhau - nếu không họ
     * vừa duyệt bước trước vừa duyệt bước sau, mất ý nghĩa của quy trình nhiều cấp.
     *
     * Màn hình Đề Nghị Cấp Phát Vật Tư chỉ đọc lại quy trình này chứ không cho chọn lại
     * người ký, nên bước khai thiếu người hoặc gán nhầm người sẽ làm phiếu treo không ai ký.
     */
    private function checkSigners($validator, Request $request): void
    {
        $roles = array_values((array) $request->input('steps', []));
        $signers = array_values((array) $request->input('signers', []));
        $people = $this->signerOptions()->keyBy('id');
        $roleNames = DB::table('roles')->pluck('name', 'id');

        $validator->after(function ($v) use ($roles, $signers, $people, $roleNames) {
            if (count($signers) !== count($roles)) {
                $v->errors()->add('signers', 'Mỗi bước trình ký phải chọn đủ vai trò và người ký.');

                return;
            }

            $seen = [];

            foreach ($roles as $index => $roleId) {
                $stepUserIds = array_values(array_unique(array_map('intval', (array) ($signers[$index] ?? []))));

                if (! $stepUserIds) {
                    $v->errors()->add('signers.'.$index, 'Bước '.($index + 1).' chưa chọn người ký nào.');

                    continue;
                }

                foreach ($stepUserIds as $userId) {
                    $person = $people->get($userId);

                    if (! $person) {
                        $v->errors()->add(
                            'signers.'.$index,
                            'Bước '.($index + 1).': có người ký không thuộc công ty của bạn.'
                        );

                        continue;
                    }

                    if (! in_array((int) $roleId, $person->role_ids, true)) {
                        $v->errors()->add(
                            'signers.'.$index,
                            'Bước '.($index + 1).': '.($person->fullName ?: $person->userName)
                                .' không thuộc vai trò '.($roleNames[$roleId] ?? 'đã chọn').'.'
                        );
                    }

                    if (isset($seen[$userId])) {
                        $v->errors()->add(
                            'signers.'.$index,
                            'Bước '.($index + 1).': '.($person->fullName ?: $person->userName)
                                .' đã được giao ký ở bước '.$seen[$userId].'.'
                        );

                        continue;
                    }

                    $seen[$userId] = $index + 1;
                }
            }
        });
    }

    /**
     * Hai quy trình còn hiệu lực cùng tổ hợp điều kiện thì một vật tư sẽ khớp cả hai và
     * hệ thống không biết chọn cái nào - chặn ngay từ lúc khai.
     */
    private function checkDuplicate($validator, ?string $criteria, $classificationId, $ignoreId = null): void
    {
        $signature = MaterialSignFlow::signature($criteria, $classificationId);

        $clash = DB::table(self::TABLE)
            ->where('department_id', $this->departmentId())
            ->where('status_id', 1)
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->get(['id', 'name', 'criteria', 'classification_id'])
            ->first(fn ($row) => MaterialSignFlow::signature($row->criteria, $row->classification_id) === $signature);

        if ($clash) {
            $validator->after(fn ($v) => $v->errors()->add(
                'criteria',
                'Tổ hợp điều kiện này đã được khai ở quy trình "'.$clash->name.'".'
            ));
        }
    }

    /* ==========================================================
     |  LỊCH SỬ THAY ĐỔI
     ========================================================== */

    /** Nội dung đọc được của một quy trình - dùng chung cho ảnh chụp và mô tả thay đổi. */
    private function descriptorOf($row, $steps): array
    {
        return [
            'Tên quy trình' => (string) ($row->name ?? ''),
            'Điều kiện - Phân loại danh mục chung' => MaterialSignFlow::describe($row->criteria ?? null),
            'Điều kiện - Phân loại của phòng' => $this->classificationName($row->classification_id ?? null),
            'Các bước trình ký' => MaterialSignFlow::stepsText($steps) ?: '—',
        ];
    }

    /**
     * Ảnh chụp bản ghi cho lịch sử. Tự dựng thay vì dùng DataMasterHistory::snapshot()
     * vì các bước ký nằm ở bảng con, không đọc ra được từ một dòng của bảng chính.
     */
    private function snapshotOf(int $id): array
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return [];
        }

        return [
            'Người tạo' => $row->created_by ?: '—',
            'Ngày tạo' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '—',
        ] + $this->descriptorOf($row, MaterialSignFlow::stepsOf($id)) + [
            'Trạng thái sử dụng' => $row->status_id ? 'Hoạt động' : 'Đã khoá',
        ];
    }

    /** Mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới", ghép bằng dấu gạch đứng. */
    private function diffNote(array $before, array $after): string
    {
        $parts = [];

        foreach ($after as $label => $value) {
            if ((string) ($before[$label] ?? '') !== (string) $value) {
                $parts[] = $label.': '.($before[$label] ?: '—').' -> '.($value ?: '—');
            }
        }

        return implode(' | ', $parts);
    }

    /* ==========================================================
     |  TIỆN ÍCH
     ========================================================== */

    private function findOwn($id)
    {
        return DB::table(self::TABLE)
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    /** Người được chọn làm người ký - nạp một lần cho cả request. */
    private function signerOptions()
    {
        return $this->signerOptionsCache ??= MaterialSignFlow::signerOptions();
    }

    /** Phân loại của phòng đang chọn, dùng cho ô select trên modal. */
    private function classificationOptions()
    {
        return DB::table(self::CLASSIFICATION_TABLE)
            ->where('department_id', $this->departmentId())
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);
    }

    /** Id phân loại của phòng lấy từ form; để trống nghĩa là áp dụng cho mọi phân loại. */
    private function classificationId(Request $request)
    {
        $id = (int) $request->input('classification_id');

        return $id > 0 ? $id : null;
    }

    private function classificationName($id): string
    {
        if (! $id) {
            return MaterialSignFlow::ANY_LABEL;
        }

        return (string) DB::table(self::CLASSIFICATION_TABLE)->where('id', $id)->value('name') ?: 'NA';
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }
}
