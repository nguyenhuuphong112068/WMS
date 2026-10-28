<?php

namespace App\Http\Controllers\Concerns;

use App\Support\EstimateSigner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * DỰ TRÙ - QUY TRÌNH KÝ DUYỆT ĐỘNG (dùng chung 3 controller dự trù).
 *
 * Quy trình CỐ ĐỊNH 2 bước (bảng <loại>_estimate_signs), ký tuần tự theo step_no, nhập lại
 * mật khẩu khi ký (21 CFR Part 11):
 *   - Bước 1: Quản Lý Bộ Phận của phòng lập phiếu - ghi theo vai trò (user_id NULL), mọi người
 *     thuộc vai trò đó của phòng đều ký được (App\Support\EstimateSigner).
 *   - Bước 2: Ban Giám Đốc - người lập chọn đích danh trong modal Trình ký.
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
    /* ==========================================================
     |  QUY TRÌNH KÝ (modal Trình ký)
     ========================================================== */

    /** Quy trình ký cố định: bước 1 Quản Lý Bộ Phận, bước 2 Ban Giám Đốc - xem config/estimate.php. */
    protected function signFlowStepCount(): int
    {
        return 2;
    }

    /**
     * Validator của modal Trình ký. Người lập không chọn người ký nữa: cả 2 bước tự gán theo vai
     * trò + phạm vi phòng ban (App\Support\EstimateSigner), chỉ cần phòng có đủ người ở mỗi bước.
     */
    protected function signFlowValidator(Request $request, int $departmentId)
    {
        $validator = Validator::make([], []);

        $validator->after(function ($validator) use ($departmentId) {
            if ($flowError = $this->validateSignerFlow($departmentId)) {
                $validator->errors()->add('signers', $flowError);
            }
        });

        return $validator;
    }

    /**
     * Phòng lập phiếu có đủ người ký cho cả 2 bước không. Trả về câu lỗi, hoặc null nếu hợp lệ.
     */
    protected function validateSignerFlow(int $departmentId): ?string
    {
        if (EstimateSigner::managers($departmentId)->isEmpty()) {
            return 'Phòng ban chưa có tài khoản nào thuộc vai trò '.implode(' / ', EstimateSigner::managerRoles()).' để ký bước 1.';
        }

        if (EstimateSigner::bods($departmentId)->isEmpty()) {
            return 'Phòng ban chưa có tài khoản '.implode(' / ', EstimateSigner::bodRoles()).' nào được phân quyền (phạm vi phòng ban) để ký bước 2.';
        }

        return null;
    }

    /**
     * Ghi 2 bước ký, cả hai theo VAI TRÒ (user_id NULL): bước 1 Quản Lý Bộ Phận, bước 2 Ban Giám
     * Đốc - ai thuộc vai trò đó với phạm vi bao gồm phòng lập phiếu cũng ký được.
     */
    protected function insertSigns(int $listId, Request $request): void
    {
        $base = [
            self::ESTIMATE_FK => $listId,
            'user_id' => null,
            'user_name' => null,
            'status' => 'pending',
            'active' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table(self::SIGN_TABLE)->insert($base + ['step_no' => 1, 'role_names' => implode(', ', EstimateSigner::managerRoles())]);
        DB::table(self::SIGN_TABLE)->insert($base + ['step_no' => 2, 'role_names' => implode(', ', EstimateSigner::bodRoles())]);
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

        // Bước Quản Lý Bộ Phận / Ban Giám Đốc: phải có vai trò với phạm vi bao gồm ĐÚNG phòng lập phiếu
        if (EstimateSigner::isScopedStep($sign)) {
            $departmentId = (int) DB::table(self::TABLE)->where('id', $sign->{self::ESTIMATE_FK})->value('department_id');

            return EstimateSigner::canSign($userId, $sign, $departmentId);
        }

        // Phiếu chuyển từ luồng 2 bước cũ: chỉ có role_names
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
     |  TÌM PHIẾU ĐỂ KÝ / TỪ CHỐI
     |  Tab "Ký duyệt (mọi phòng ban)" đã bỏ: người phòng ban chung mở phiếu từ Trang chủ,
     |  hệ thống tự chuyển phòng đang chọn sang phòng của phiếu (HomeController::signUrl) nên
     |  ký / từ chối luôn trong phạm vi phòng đang chọn.
     ========================================================== */

    /** Tab cần mở lại sau khi ký / từ chối. */
    protected function approvalActiveTab(Request $request): string
    {
        return 'list';
    }

    /** Phiếu để ký / từ chối: thuộc phòng ban đang chọn. */
    protected function resolveEstimateForApproval(Request $request)
    {
        return $this->findOwn($request->id);
    }
}
