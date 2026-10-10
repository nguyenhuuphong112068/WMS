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
 * Phòng ban khai các VAI TRÒ nhận trình ký đề nghị cấp phát vật tư. Mỗi vai trò một dòng:
 *   - Cấp ký   : phiếu ký lần lượt từ cấp nhỏ tới cấp lớn
 *   - Người ký : một hoặc nhiều người thuộc vai trò, chỉ cần một người ký là xong bước
 *   - Điều kiện: các nhóm tiêu chí (VÀ trong nhóm, HOẶC giữa các nhóm). Đề nghị có ít nhất
 *                một vật tư thoả một nhóm thì vai trò nhận trình ký. Bỏ trống = mọi đề nghị.
 *
 * Màn hình luôn làm việc trên phòng ban đang chọn ở topNAV - department_id lấy từ session.
 * Khoá (status_id = 0) thay cho xoá cứng; sửa người ký thì bỏ hiệu lực dòng cũ (active = 0).
 *
 * Cách màn hình Đề Nghị Cấp Phát Vật Tư đọc lại: App\Support\MaterialSignFlow::resolveForItems().
 */
class MaterialSignFlowController extends Controller
{
    use RequiresChangeReason;

    private const TABLE = 'material_request_sign_rules';

    /** Người ký của một vai trò - mỗi người một dòng. */
    private const USER_TABLE = 'material_request_sign_rule_users';

    private const LABEL = 'vai trò trình ký';

    /** Danh sách người ký chọn được - nạp một lần cho cả request, xem signerOptions(). */
    private $signerOptionsCache = null;

    /** Tiêu chí điều kiện của phòng đang chọn - nạp một lần cho cả request. */
    private $criteriaCache = null;

    public function index()
    {
        session()->put(['title' => 'DỮ LIỆU GỐC - TRÌNH KÝ ĐỀ NGHỊ CP VẬT TƯ']);

        return view('pages.materData.MaterialSignFlow.list', [
            'datas' => MaterialSignFlow::allRules($this->departmentId()),
            'criteriaOptions' => $this->criteriaOptions(),
            'roles' => DB::table('roles')->orderBy('name', 'asc')->get(['id', 'name']),
            'signerOptions' => $this->signerOptions(),
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());
        $conditions = $this->checkConditions($validator, $request);
        $this->checkSigners($validator, $request);
        $this->checkLevel($validator, $request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::transaction(function () use ($request, $conditions) {
            $id = DB::table(self::TABLE)->insertGetId([
                'department_id' => $this->departmentId(),
                'role_id' => (int) $request->role_id,
                'level' => (int) $request->level,
                'conditions' => MaterialSignFlow::encodeConditions($conditions),
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->insertSigners($id, $request);

            return $id;
        });

        $roleName = $this->roleName($request->role_id);

        DataMasterHistory::write(self::TABLE, $id, 'Thêm mới', 'Khai báo mới '.self::LABEL.': '.$roleName.'.', $this->snapshotOf($id));

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm '.self::LABEL.': '.$roleName);

        return redirect()->back()->with('success', 'Đã thêm '.self::LABEL.' '.$roleName.'!');
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
        $conditions = $this->checkConditions($validator, $request);
        $this->checkSigners($validator, $request, $current->id);
        $this->checkLevel($validator, $request, $current->id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $before = $this->descriptorOf((int) $current->id);

        // So nội dung mới với nội dung đang lưu TRƯỚC khi ghi, không đổi gì thì không đụng CSDL
        $note = $this->diffNote($before, $this->describe(
            (int) $request->role_id,
            (int) $request->level,
            $this->signerIds($request),
            MaterialSignFlow::encodeConditions($conditions)
        ));

        if ($note === '') {
            return redirect()->back()->with('error', 'Chưa có thông tin nào thay đổi nên không lưu.')->withInput();
        }

        DB::transaction(function () use ($current, $request, $conditions) {
            DB::table(self::TABLE)->where('id', $current->id)->update([
                'role_id' => (int) $request->role_id,
                'level' => (int) $request->level,
                'conditions' => MaterialSignFlow::encodeConditions($conditions),
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Khai lại người ký: bỏ hiệu lực danh sách cũ rồi ghi danh sách mới
            DB::table(self::USER_TABLE)->where('rule_id', $current->id)->where('active', 1)->update([
                'active' => 0,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);
            $this->insertSigners((int) $current->id, $request);
        });

        DataMasterHistory::write(
            self::TABLE,
            (int) $current->id,
            'Cập nhật',
            $note,
            $this->snapshotOf((int) $current->id),
            $this->changeReason($request)
        );

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $before['Vai trò'], $note);

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

        // Mở khoá lại mà cấp ký đã bị vai trò khác dùng thì thứ tự ký sẽ mơ hồ
        if ($newStatus === 1 && $this->levelTaken((int) $current->level, (int) $current->id)) {
            return redirect()->back()->with(
                'error',
                'Cấp ký '.$current->level.' đã được vai trò khác dùng. Sửa cấp ký trước khi mở khoá.'
            );
        }

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
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' '.$this->roleName($current->role_id).'!'
        );
    }

    /**
     * Trả về lịch sử thay đổi của một dòng cho modal xem lịch sử.
     * Chỉ đọc được vai trò của phòng ban đang chọn, giống các thao tác còn lại.
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
     |  GHI NGƯỜI KÝ
     ========================================================== */

    private function insertSigners(int $ruleId, Request $request): void
    {
        $people = $this->signerOptions()->keyBy('id');

        foreach ($this->signerIds($request) as $userId) {
            $person = $people->get($userId);

            DB::table(self::USER_TABLE)->insert([
                'rule_id' => $ruleId,
                'user_id' => $userId,
                'user_name' => $person ? MaterialSignFlow::personName($person) : null,
                'active' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** id người ký trên form, bỏ ô trống và người trùng, giữ thứ tự chọn. */
    private function signerIds(Request $request): array
    {
        $ids = [];

        foreach ((array) $request->input('signers', []) as $value) {
            $id = (int) $value;
            if ($id > 0 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /* ==========================================================
     |  VALIDATE
     ========================================================== */

    private function rules($ignoreId = null): array
    {
        $departmentId = $this->departmentId();

        return [
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id'),
                // Mỗi vai trò chỉ khai một lần trong phòng (kể cả dòng đã khoá - mở khoá lại mà dùng)
                Rule::unique(self::TABLE, 'role_id')
                    ->where(fn ($query) => $query->where('department_id', $departmentId))
                    ->ignore($ignoreId),
            ],
            'level' => ['required', 'integer', 'min:1', 'max:'.MaterialSignFlow::MAX_LEVEL],
            'signers' => ['required', 'array', 'min:1'],
            'signers.*' => ['nullable', 'integer'],
            'conditions' => ['nullable', 'array'],
        ];
    }

    private function messages(): array
    {
        return [
            'role_id.required' => 'Vui lòng chọn vai trò nhận trình ký.',
            'role_id.exists' => 'Vai trò không hợp lệ.',
            'role_id.unique' => 'Vai trò này đã được khai trong phòng ban. Sửa dòng đã có thay vì thêm mới.',
            'level.required' => 'Vui lòng nhập cấp ký.',
            'level.integer' => 'Cấp ký phải là số nguyên.',
            'level.min' => 'Cấp ký nhỏ nhất là 1.',
            'level.max' => 'Cấp ký lớn nhất là '.MaterialSignFlow::MAX_LEVEL.'.',
            'signers.required' => 'Vui lòng chọn ít nhất 1 người ký.',
            'signers.min' => 'Vui lòng chọn ít nhất 1 người ký.',
        ];
    }

    /** Đọc + kiểm tra các nhóm điều kiện; trả về nhóm đã chuẩn hoá (null = mọi đề nghị). */
    private function checkConditions($validator, Request $request): ?array
    {
        [$groups, $errors] = MaterialSignFlow::parseConditions($request->input('conditions'), $this->criteriaOptions());

        if ($errors) {
            $validator->after(function ($v) use ($errors) {
                foreach (array_unique($errors) as $message) {
                    $v->errors()->add('conditions', $message);
                }
            });
        }

        return $groups;
    }

    /**
     * Người ký phải thuộc công ty và THUỘC vai trò đang khai. Một người không được đứng ở hai
     * vai trò còn hiệu lực của cùng phòng - nếu không họ ký hai bước liền trên cùng một phiếu,
     * mất ý nghĩa của ký nhiều cấp.
     */
    private function checkSigners($validator, Request $request, $ignoreId = null): void
    {
        $roleId = (int) $request->role_id;
        $userIds = $this->signerIds($request);
        $people = $this->signerOptions()->keyBy('id');
        $roleName = $this->roleName($roleId);

        $taken = DB::table(self::USER_TABLE)
            ->join(self::TABLE, self::TABLE.'.id', '=', self::USER_TABLE.'.rule_id')
            ->leftJoin('roles', 'roles.id', '=', self::TABLE.'.role_id')
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->where(self::TABLE.'.status_id', 1)
            ->where(self::USER_TABLE.'.active', 1)
            ->when($ignoreId, fn ($query) => $query->where(self::TABLE.'.id', '<>', $ignoreId))
            ->whereIn(self::USER_TABLE.'.user_id', $userIds ?: [0])
            ->pluck('roles.name', self::USER_TABLE.'.user_id');

        $validator->after(function ($v) use ($roleId, $userIds, $people, $roleName, $taken) {
            if (! $userIds) {
                $v->errors()->add('signers', 'Vui lòng chọn ít nhất 1 người ký.');

                return;
            }

            foreach ($userIds as $userId) {
                $person = $people->get($userId);

                if (! $person) {
                    $v->errors()->add('signers', 'Có người ký không thuộc công ty của bạn.');

                    continue;
                }

                $name = $person->fullName ?: $person->userName;

                if ($roleId && ! in_array($roleId, $person->role_ids, true)) {
                    $v->errors()->add('signers', $name.' không thuộc vai trò '.$roleName.'.');
                }

                if ($taken->has($userId)) {
                    $v->errors()->add('signers', $name.' đã là người ký của vai trò '.($taken[$userId] ?: 'khác').'.');
                }
            }
        });
    }

    /** Hai vai trò còn hiệu lực cùng một cấp ký thì không biết ai ký trước - chặn ngay lúc khai. */
    private function checkLevel($validator, Request $request, $ignoreId = null): void
    {
        $level = (int) $request->level;

        if ($level > 0 && ($clash = $this->levelTaken($level, $ignoreId))) {
            $validator->after(fn ($v) => $v->errors()->add(
                'level',
                'Cấp ký '.$level.' đã được vai trò '.($clash->role_name ?: 'khác').' dùng.'
            ));
        }
    }

    private function levelTaken(int $level, $ignoreId = null)
    {
        return DB::table(self::TABLE)
            ->leftJoin('roles', 'roles.id', '=', self::TABLE.'.role_id')
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->where(self::TABLE.'.status_id', 1)
            ->where(self::TABLE.'.level', $level)
            ->when($ignoreId, fn ($query) => $query->where(self::TABLE.'.id', '<>', $ignoreId))
            ->first(['roles.name as role_name']);
    }

    /* ==========================================================
     |  LỊCH SỬ THAY ĐỔI
     ========================================================== */

    /** Nội dung đọc được của một vai trò đang lưu - dùng cho ảnh chụp và mô tả thay đổi. */
    private function descriptorOf(int $id): array
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return [];
        }

        return $this->describe(
            (int) $row->role_id,
            (int) $row->level,
            MaterialSignFlow::signersOfRules([$id])->get($id, collect())->pluck('user_id')->map(fn ($v) => (int) $v)->all(),
            $row->conditions
        );
    }

    /** Mô tả một vai trò từ giá trị thô - dùng chung cho dữ liệu đang lưu và dữ liệu trên form. */
    private function describe(int $roleId, int $level, array $userIds, ?string $conditions): array
    {
        $people = $this->signerOptions()->keyBy('id');
        $names = DB::table('user_management')->whereIn('id', $userIds ?: [0])->pluck('fullName', 'id');

        return [
            'Vai trò' => $this->roleName($roleId),
            'Cấp ký' => (string) $level,
            'Người ký' => collect($userIds)
                ->map(fn ($id) => $names[$id] ?? ($people->get($id)->fullName ?? 'NA'))
                ->implode(' / ') ?: '—',
            'Điều kiện nhận trình ký' => MaterialSignFlow::describeConditions($conditions, $this->criteriaOptions()),
        ];
    }

    /** Ảnh chụp bản ghi cho lịch sử - người ký nằm ở bảng con nên tự dựng. */
    private function snapshotOf(int $id): array
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return [];
        }

        return [
            'Người tạo' => $row->created_by ?: '—',
            'Ngày tạo' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '—',
        ] + $this->descriptorOf($id) + [
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

    private function roleName($roleId): string
    {
        return (string) DB::table('roles')->where('id', (int) $roleId)->value('name') ?: 'NA';
    }

    /** Người được chọn làm người ký - nạp một lần cho cả request. */
    private function signerOptions()
    {
        return $this->signerOptionsCache ??= MaterialSignFlow::signerOptions();
    }

    private function criteriaOptions(): array
    {
        return $this->criteriaCache ??= MaterialSignFlow::criteriaOptions($this->departmentId());
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
