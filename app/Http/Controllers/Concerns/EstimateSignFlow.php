<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\General\NotificationController;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DỰ TRÙ - QUY TRÌNH KÝ DUYỆT ĐỘNG (dùng chung 3 controller dự trù).
 *
 * Song song với cơ chế của MaterialExportController: người lập phiếu tự khai số bước ký và
 * người ký đích danh từng bước (bảng <loại>_estimate_signs), ký tuần tự theo step_no, mỗi
 * bước đúng người được chỉ định (nhập lại mật khẩu - 21 CFR Part 11). Tối thiểu 2 bước, bước
 * cuối bắt buộc thuộc vai trò config('estimate.bod_roles').
 *
 * Class dùng trait phải khai các hằng:
 *   - self::TABLE        : bảng đầu phiếu (vd 'chemical_estimates')
 *   - self::SIGN_TABLE   : bảng bước ký    (vd 'chemical_estimate_signs')
 *   - self::ESTIMATE_FK  : khoá ngoại      (vd 'chemical_estimate_id')
 *   - self::LABEL        : nhãn tiếng Việt (vd 'phiếu dự trù hoá chất')
 * và có sẵn: departmentId(), actor(), findOwn($id), static itemsOf($id).
 */
trait EstimateSignFlow
{
    /** Danh sách người ký chọn được - nạp một lần cho cả request. */
    private $signerOptionsCache = null;

    /* ==========================================================
     |  KHAI QUY TRÌNH KÝ (modal Lập / Sửa phiếu)
     ========================================================== */

    /**
     * Người có thể được chọn làm người ký: user đang hoạt động thuộc CÙNG CÔNG TY với phòng
     * ban đang chọn (để trình ký lên cấp trên ngoài phòng - Ban Giám Đốc...).
     *
     * Mỗi người kèm 'role_names' = MỌI vai trò của họ (role chính + role gán qua user_role
     * có phạm vi NULL hoặc đúng phòng ban đang chọn) để lọc người ký BƯỚC CUỐI theo
     * config('estimate.bod_roles') mà không phụ thuộc vai trò nào được đặt làm chính.
     */
    protected function signerOptions()
    {
        if ($this->signerOptionsCache !== null) {
            return $this->signerOptionsCache;
        }

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
                'deparments.shortName as department_short',
                'roles.name as role_name'
            )
            ->where('user_management.isActive', 1)
            ->when($companyId, fn ($query) => $query->where(function ($sub) use ($companyId, $departmentIds) {
                if ($departmentIds) {
                    $sub->whereIn('user_management.deparment_id', $departmentIds);
                }
                $sub->orWhere(fn ($q) => $q->whereNull('user_management.deparment_id')
                    ->where('user_management.company_id', $companyId));
            }))
            ->orderBy('user_management.fullName', 'asc')
            ->get();

        $assignedRoles = DB::table('user_role')
            ->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->whereIn('user_role.user_id', $people->pluck('id'))
            ->where(function ($q) use ($currentDeptId) {
                $q->whereNull('user_role.department_id');
                if ($currentDeptId) {
                    $q->orWhere('user_role.department_id', $currentDeptId);
                }
            })
            ->get(['user_role.user_id', 'roles.name'])
            ->groupBy('user_id');

        foreach ($people as $person) {
            $names = $assignedRoles->get($person->id, collect())->pluck('name')->all();
            if ($person->role_name) {
                $names[] = $person->role_name;
            }
            $person->role_names = array_values(array_unique(array_filter($names)));
        }

        return $this->signerOptionsCache = $people;
    }

    /** Id người ký trên form, giữ nguyên thứ tự và bỏ các ô để trống / trùng. */
    protected function signerIds(Request $request): array
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

    /**
     * Kiểm tra quy trình ký hợp lệ: tối thiểu 2 bước, bước cuối là Ban Giám Đốc.
     * Trả về câu lỗi, hoặc null nếu hợp lệ.
     */
    protected function validateSignerFlow(array $signerIds): ?string
    {
        if (count($signerIds) < 2) {
            return 'Quy trình ký duyệt phải có tối thiểu 2 bước.';
        }

        $lastId = end($signerIds);
        $bodRoles = config('estimate.bod_roles');

        if (! user_has_any_role($lastId, $bodRoles)) {
            return 'Người ký ở bước cuối phải thuộc vai trò '.implode(' / ', $bodRoles).'.';
        }

        return null;
    }

    /** Ghi các bước ký của một phiếu theo đúng thứ tự người dùng xếp trên form. */
    protected function insertSigns(int $listId, Request $request): void
    {
        $people = $this->signerOptions()->keyBy('id');

        foreach ($this->signerIds($request) as $index => $userId) {
            $person = $people->get($userId);

            DB::table(self::SIGN_TABLE)->insert([
                self::ESTIMATE_FK => $listId,
                'step_no' => $index + 1,
                'user_id' => $userId,
                'user_name' => $person ? $this->personName($person) : null,
                'status' => 'pending',
                'active' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** Sửa phiếu là khai lại quy trình: không xoá cứng, chỉ bỏ hiệu lực các bước cũ. */
    protected function deactivateSigns(int $listId): void
    {
        DB::table(self::SIGN_TABLE)->where(self::ESTIMATE_FK, $listId)->update([
            'active' => 0,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);
    }

    /** Số bước ký còn hiệu lực của một phiếu. */
    protected function activeStepCount(int $listId): int
    {
        return (int) DB::table(self::SIGN_TABLE)
            ->where(self::ESTIMATE_FK, $listId)
            ->where('active', 1)
            ->count();
    }

    /* ==========================================================
     |  ĐỌC BƯỚC KÝ
     ========================================================== */

    /** Các bước ký còn hiệu lực của một loạt phiếu: list_id => bước theo step_no. */
    protected function signRows($listIds)
    {
        $listIds = collect($listIds)->filter()->values();

        if ($listIds->isEmpty()) {
            return collect();
        }

        return DB::table(self::SIGN_TABLE)
            ->leftJoin('user_management', 'user_management.id', '=', self::SIGN_TABLE.'.user_id')
            ->leftJoin('deparments', 'deparments.id', '=', 'user_management.deparment_id')
            ->select(
                self::SIGN_TABLE.'.*',
                'user_management.fullName as signer_full_name',
                'deparments.shortName as signer_department_short'
            )
            ->where(self::SIGN_TABLE.'.active', 1)
            ->whereIn(self::SIGN_TABLE.'.'.self::ESTIMATE_FK, $listIds)
            ->orderBy(self::SIGN_TABLE.'.step_no', 'asc')
            ->get()
            ->groupBy(self::ESTIMATE_FK);
    }

    /** Dòng bước ký đang chờ của một phiếu, đọc thẳng từ DB. */
    protected function currentSign($row)
    {
        if (! $row || $row->app_status !== 'pending_sign' || ! $row->current_step) {
            return null;
        }

        return DB::table(self::SIGN_TABLE)
            ->where(self::ESTIMATE_FK, $row->id)
            ->where('active', 1)
            ->where('step_no', (int) $row->current_step)
            ->first();
    }

    /** Bước đang chờ ký, lấy từ bộ bước đã nạp sẵn ở index() để khỏi truy vấn lại từng phiếu. */
    protected function pendingSign($row, $signs)
    {
        if ($row->app_status !== 'pending_sign' || ! $row->current_step) {
            return null;
        }

        return collect($signs)->firstWhere('step_no', (int) $row->current_step);
    }

    /**
     * Người đang đăng nhập có ký được bước này không.
     * Phiếu mới chỉ định đích danh user_id; phiếu chuyển từ luồng 2 bước cũ chỉ có role_names.
     */
    protected function canSignRow($sign): bool
    {
        if (! $sign) {
            return false;
        }

        $userId = (int) (session('user')['userId'] ?? 0);

        if ($sign->user_id) {
            return (int) $sign->user_id === $userId;
        }

        $roles = array_values(array_filter(array_map('trim', explode(',', (string) $sign->role_names))));

        return $roles ? user_has_any_role($userId, $roles) : false;
    }

    /** "Họ Tên (userName)" - cùng dạng với App\Support\Signer::actor() để đối chiếu chữ ký. */
    protected function personName($person): string
    {
        $fullName = trim((string) ($person->fullName ?? ''));
        $userName = trim((string) ($person->userName ?? ''));

        if ($userName === '') {
            return $fullName ?: 'NA';
        }

        return $fullName !== '' ? $fullName.' ('.$userName.')' : $userName;
    }

    /* ==========================================================
     |  THÔNG BÁO TIẾN TRÌNH KÝ
     ========================================================== */

    /**
     * Báo cho người ký ở bước $stepNo của phiếu rằng "tới lượt bạn ký". Gọi khi trình ký
     * (bước 1) và sau mỗi lần ký xong (bước kế tiếp) để đi LẦN LƯỢT qua từng người trong
     * quy trình. Bước không gắn user_id (luồng cũ ký theo vai trò) thì bỏ qua; người gửi
     * tự bị NotificationController loại khỏi danh sách nhận.
     */
    protected function notifyPendingSigner(int $listId, string $code, int $stepNo): void
    {
        $rows = DB::table(self::SIGN_TABLE)
            ->where(self::ESTIMATE_FK, $listId)
            ->where('active', 1)
            ->get(['step_no', 'user_id']);

        $target = $rows->firstWhere('step_no', $stepNo);

        if (! $target || ! $target->user_id) {
            return;
        }

        NotificationController::sendNotification(
            'Phiếu '.$code.' đang chờ bạn ký duyệt (bước '.$stepNo.'/'.$rows->count().').',
            'Trình ký',
            $listId,
            [(int) $target->user_id],
            [],
            route(self::EST_ROUTE.'list')
        );
    }

    /* ==========================================================
     |  HỘP KÝ DUYỆT LIÊN PHÒNG BAN (tab "Ký duyệt (mọi phòng ban)")
     ========================================================== */

    /** Phòng ban NHÀ của người đang đăng nhập (user_management.deparment_id). */
    protected function homeDepartmentId(): int
    {
        return (int) DB::table('user_management')
            ->where('id', session('user')['userId'] ?? 0)
            ->value('deparment_id');
    }

    /** Được dùng hộp ký duyệt liên phòng ban không: có quyền is_BOD và phòng nhà là phòng chung. */
    protected function canUseApprovalInbox(): bool
    {
        if (! user_can('is_BOD')) {
            return false;
        }

        $homeDeptId = $this->homeDepartmentId();

        return $homeDeptId
            ? (int) DB::table('deparments')->where('id', $homeDeptId)->value('is_general') === 0
            : false;
    }

    /** Tab cần mở lại sau khi ký / từ chối: 'inbox' khi thao tác từ hộp ký duyệt, còn lại 'list'. */
    protected function approvalActiveTab(Request $request): string
    {
        return $request->input('scope') === 'inbox' && $this->canUseApprovalInbox() ? 'inbox' : 'list';
    }

    /**
     * Tìm phiếu để ký / từ chối. Bình thường lấy theo phòng ban đang chọn; khi thao tác từ hộp
     * ký duyệt liên phòng ban (scope = inbox) thì lấy theo CÔNG TY đang làm việc -
     * currentSign() + canSignRow() vẫn chặn đúng người ký nên không lỏng quyền.
     */
    protected function resolveEstimateForApproval(Request $request)
    {
        if ($request->input('scope') === 'inbox' && $this->canUseApprovalInbox()) {
            $companyDeptIds = CompanyContext::departmentIds(CompanyContext::currentId());

            return DB::table(self::TABLE)
                ->where('id', $request->id)
                ->when($companyDeptIds !== null, fn ($query) => $query->whereIn('department_id', $companyDeptIds))
                ->first();
        }

        return $this->findOwn($request->id);
    }

    /**
     * Dữ liệu tab "Ký duyệt (mọi phòng ban)": các phiếu dự trù pending_sign của mọi phòng
     * trong cùng công ty mà BƯỚC ĐANG CHỜ được giao đích danh cho người đang đăng nhập.
     */
    protected function approvalInboxData(): array
    {
        $empty = ['show' => false, 'requests' => collect(), 'items' => collect(), 'signs' => collect(), 'badge' => 0];

        if (! $this->canUseApprovalInbox()) {
            return $empty;
        }

        $myUserId = (int) (session('user')['userId'] ?? 0);
        $companyDeptIds = CompanyContext::departmentIds(CompanyContext::currentId());

        $requests = DB::table(self::TABLE)
            ->join(self::SIGN_TABLE, function ($join) {
                $join->on(self::SIGN_TABLE.'.'.self::ESTIMATE_FK, '=', self::TABLE.'.id')
                    ->on(self::SIGN_TABLE.'.step_no', '=', self::TABLE.'.current_step')
                    ->where(self::SIGN_TABLE.'.active', 1);
            })
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->where(self::TABLE.'.app_status', 'pending_sign')
            ->where(self::SIGN_TABLE.'.user_id', $myUserId)
            ->where(self::SIGN_TABLE.'.status', 'pending')
            ->when($companyDeptIds !== null, fn ($query) => $query->whereIn(self::TABLE.'.department_id', $companyDeptIds))
            ->select(
                self::TABLE.'.*',
                'deparments.name as department_name',
                'deparments.shortName as department_short',
                self::SIGN_TABLE.'.step_no as my_step_no'
            )
            ->orderBy(self::TABLE.'.submitted_at', 'asc')
            ->orderBy(self::TABLE.'.id', 'asc')
            ->get();

        $ids = $requests->pluck('id');

        $items = $ids->mapWithKeys(fn ($id) => [$id => self::itemsOf($id)]);

        return [
            'show' => true,
            'requests' => $requests,
            'items' => $items,
            'signs' => $this->signRows($ids),
            'badge' => $requests->count(),
        ];
    }
}
