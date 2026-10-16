<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * QUY TRÌNH KÝ ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN (gồm Đề nghị VPP).
 *
 * Khai ở Dữ Liệu Gốc > "Trình Ký Đề Nghị Liên Phòng Ban" (bảng material_transfer_sign_steps)
 * theo LOẠI đề nghị và CÔNG TY của phòng đề nghị. Mỗi bước = một vai trò + phạm vi phòng ban:
 *   - requester  : vai trò đó của PHÒNG ĐỀ NGHỊ        (VD: trưởng bộ phận đề nghị VPP)
 *   - receiver   : vai trò đó của PHÒNG ĐƯỢC ĐỀ NGHỊ   (VD: trưởng bộ phận giữ hàng duyệt cho chuyển)
 *   - department : vai trò đó của một phòng cố định   (VD: trưởng Hành Chính - NM1 duyệt VPP của NM1)
 * Người ký = mọi user có vai trò đó với phạm vi bao gồm phòng của bước (App\Support\EstimateSigner),
 * ai ký trước cũng được, nhập lại mật khẩu (21 CFR Part 11).
 *
 * Bấm Gửi: chụp quy trình của loại + công ty vào material_transfer_signs (đã giải phòng của
 * từng bước), phiếu chuyển 'pending_sign'. Ký xong bước cuối -> 'pending' (tới phòng được đề
 * nghị cấp phát). Không khai bước nào -> gửi thẳng như trước. Từ chối -> về 'draft' kèm lý do.
 */
class MaterialTransferSignFlow
{
    public const TABLE = 'material_transfer_sign_steps';

    public const SIGN_TABLE = 'material_transfer_signs';

    public const KIND_VPP = 'vpp';

    public const KIND_TRANSFER = 'transfer';

    public const KINDS = [
        self::KIND_VPP => 'Đề nghị VPP (gửi Hành Chính)',
        self::KIND_TRANSFER => 'Đề nghị liên phòng ban khác',
    ];

    public const SCOPE_REQUESTER = 'requester';

    public const SCOPE_RECEIVER = 'receiver';

    public const SCOPE_DEPARTMENT = 'department';

    public const SCOPES = [
        self::SCOPE_REQUESTER => 'Phòng đề nghị',
        self::SCOPE_RECEIVER => 'Phòng được đề nghị',
        self::SCOPE_DEPARTMENT => 'Phòng cố định',
    ];

    /** Trạng thái phiếu đang chờ ký. */
    public const STATUS_PENDING_SIGN = 'pending_sign';

    /** Loại đề nghị theo phòng nhận: gửi Hành Chính (kho VPP) là VPP, còn lại là liên phòng ban. */
    public static function kindFor(?int $toDepartmentId): string
    {
        return MaterialVpp::isVppDepartment($toDepartmentId) ? self::KIND_VPP : self::KIND_TRANSFER;
    }

    /** Các bước đang hiệu lực của một loại + công ty, theo step_no. */
    public static function steps(string $kind, ?int $companyId)
    {
        if (! $companyId) {
            return collect();
        }

        return DB::table(self::TABLE)
            ->join('roles', 'roles.id', '=', self::TABLE.'.role_id')
            ->where(self::TABLE.'.kind', $kind)
            ->where(self::TABLE.'.company_id', $companyId)
            ->where(self::TABLE.'.status_id', 1)
            ->orderBy(self::TABLE.'.step_no')
            ->get([self::TABLE.'.*', 'roles.name as role_name']);
    }

    /**
     * Quy trình áp cho một phiếu (đầu phiếu material_transfer_requests): mỗi bước kèm phòng đã
     * giải theo phạm vi. @return array<int, array{step_no:int, role_names:string, department_id:int}>
     */
    public static function resolve($request): array
    {
        $companyId = DB::table('deparments')->where('id', $request->department_id)->value('company_id');
        $steps = [];

        foreach (self::steps(self::kindFor((int) $request->to_department_id), $companyId ? (int) $companyId : null) as $index => $step) {
            $departmentId = match ($step->scope) {
                self::SCOPE_RECEIVER => (int) $request->to_department_id,
                self::SCOPE_DEPARTMENT => (int) $step->department_id,
                default => (int) $request->department_id,
            };

            $steps[] = [
                'step_no' => $index + 1,
                'role_names' => $step->role_name,
                'department_id' => $departmentId,
            ];
        }

        return $steps;
    }

    /** Bước nào chưa có ai ký được thì báo lỗi (để chặn Gửi). */
    public static function missingSignerError(array $steps): ?string
    {
        foreach ($steps as $step) {
            if (EstimateSigner::usersWithRoles([$step['role_names']], $step['department_id'])->isEmpty()) {
                $department = DB::table('deparments')->where('id', $step['department_id'])->value('name') ?: 'NA';

                return 'Bước ký '.$step['step_no'].' chưa có tài khoản '.$step['role_names'].' nào của phòng '.$department
                    .'. Hãy phân quyền ở màn Phân quyền hoặc sửa Dữ Liệu Gốc > Trình Ký Đề Nghị Liên Phòng Ban.';
            }
        }

        return null;
    }

    /** Người ký được một dòng material_transfer_signs. */
    public static function signersOf($sign)
    {
        return EstimateSigner::usersWithRoles(EstimateSigner::stepRoles($sign), (int) $sign->department_id);
    }

    public static function canSign(int $userId, $sign): bool
    {
        return $userId > 0 && $sign && self::signersOf($sign)->contains('id', $userId);
    }

    /** Các bước còn hiệu lực của nhiều phiếu: request_id => bước theo step_no (kèm tên phòng). */
    public static function signsOf(array $requestIds)
    {
        if (! $requestIds) {
            return collect();
        }

        return DB::table(self::SIGN_TABLE)
            ->leftJoin('deparments', 'deparments.id', '=', self::SIGN_TABLE.'.department_id')
            ->where(self::SIGN_TABLE.'.active', 1)
            ->whereIn(self::SIGN_TABLE.'.transfer_request_id', $requestIds)
            ->orderBy(self::SIGN_TABLE.'.step_no')
            ->get([self::SIGN_TABLE.'.*', 'deparments.shortName as department_short', 'deparments.name as department_name'])
            ->groupBy('transfer_request_id');
    }

    /* ==========================================================
     |  VÒNG ĐỜI KÝ: gửi -> ký từng bước -> tới phòng được đề nghị / bị từ chối
     ========================================================== */

    /**
     * Gửi một phiếu đang Lưu tạm. Có quy trình ký -> 'pending_sign' (dòng vẫn 'draft', phòng
     * nhận chưa cấp được); không có -> 'pending' như trước. Bước nào chưa có người ký thì giữ
     * Lưu tạm và trả lỗi.
     *
     * @return array{status: string, error: ?string, steps: int}
     */
    public static function start(int $requestId, string $actor): array
    {
        $request = DB::table(MaterialTransferRequest::TABLE)->where('id', $requestId)->first();
        $steps = $request ? self::resolve($request) : [];

        if (! $steps) {
            self::release($requestId, $actor);

            return ['status' => 'pending', 'error' => null, 'steps' => 0];
        }

        if ($error = self::missingSignerError($steps)) {
            DB::table(MaterialTransferRequest::TABLE)->where('id', $requestId)->update(['status' => 'draft', 'updated_at' => now()]);

            return ['status' => 'draft', 'error' => $error, 'steps' => count($steps)];
        }

        DB::transaction(function () use ($requestId, $steps, $actor) {
            DB::table(self::SIGN_TABLE)->where('transfer_request_id', $requestId)->update(['active' => 0, 'updated_at' => now()]);

            foreach ($steps as $step) {
                DB::table(self::SIGN_TABLE)->insert($step + [
                    'transfer_request_id' => $requestId,
                    'status' => 'pending',
                    'active' => 1,
                    'created_by' => $actor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table(MaterialTransferRequest::TABLE)->where('id', $requestId)->update([
                'status' => self::STATUS_PENDING_SIGN,
                'current_step' => 1,
                'sign_rejected_by' => null,
                'sign_rejected_at' => null,
                'sign_reject_reason' => null,
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);

            DB::table(MaterialTransferRequest::ITEM_TABLE)->where('transfer_request_id', $requestId)
                ->where('active', 1)->update(['status' => 'draft', 'updated_at' => now()]);
        });

        self::notifyStep($requestId, 1);

        return ['status' => self::STATUS_PENDING_SIGN, 'error' => null, 'steps' => count($steps)];
    }

    /**
     * Ký bước đang chờ (đã kiểm quyền + mật khẩu ở controller). Bước cuối -> phiếu tới phòng
     * được đề nghị ('pending'). @return bool phiếu đã ký xong toàn bộ
     */
    public static function sign($request, $sign, string $actor, ?string $note = null): bool
    {
        $total = (int) DB::table(self::SIGN_TABLE)->where('transfer_request_id', $request->id)->where('active', 1)->count();
        $isLast = (int) $sign->step_no >= $total;

        DB::transaction(function () use ($request, $sign, $actor, $note, $isLast) {
            DB::table(self::SIGN_TABLE)->where('id', $sign->id)->update([
                'status' => 'signed',
                'signed_by' => $actor,
                'signed_at' => now(),
                'note' => $note,
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);

            if ($isLast) {
                self::release((int) $request->id, $actor);
            } else {
                DB::table(MaterialTransferRequest::TABLE)->where('id', $request->id)->update([
                    'current_step' => (int) $sign->step_no + 1,
                    'updated_by' => $actor,
                    'updated_at' => now(),
                ]);
            }
        });

        if (! $isLast) {
            self::notifyStep((int) $request->id, (int) $sign->step_no + 1);
        }

        return $isLast;
    }

    /** Từ chối ở bước đang chờ: phiếu về Lưu tạm kèm lý do để phòng đề nghị sửa rồi gửi lại. */
    public static function reject($request, $sign, string $actor, string $reason): void
    {
        DB::transaction(function () use ($request, $sign, $actor, $reason) {
            DB::table(self::SIGN_TABLE)->where('id', $sign->id)->update([
                'status' => 'rejected',
                'signed_by' => $actor,
                'signed_at' => now(),
                'note' => $reason,
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);

            DB::table(MaterialTransferRequest::TABLE)->where('id', $request->id)->update([
                'status' => 'draft',
                'current_step' => null,
                'sign_rejected_by' => $actor,
                'sign_rejected_at' => now(),
                'sign_reject_reason' => $reason,
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);
        });
    }

    /** Phiếu (và các dòng Lưu tạm) chuyển sang 'pending' - phòng được đề nghị bắt đầu cấp phát. */
    private static function release(int $requestId, string $actor): void
    {
        DB::table(MaterialTransferRequest::TABLE)->where('id', $requestId)->update([
            'status' => 'pending',
            'current_step' => null,
            'updated_by' => $actor,
            'updated_at' => now(),
        ]);

        DB::table(MaterialTransferRequest::ITEM_TABLE)->where('transfer_request_id', $requestId)
            ->where('active', 1)->where('status', 'draft')->update(['status' => 'pending', 'updated_at' => now()]);
    }

    /** Báo những người ký được bước $stepNo của phiếu. */
    private static function notifyStep(int $requestId, int $stepNo): void
    {
        $request = DB::table(MaterialTransferRequest::TABLE)->where('id', $requestId)->first();
        $sign = DB::table(self::SIGN_TABLE)->where('transfer_request_id', $requestId)->where('active', 1)->where('step_no', $stepNo)->first();

        if (! $request || ! $sign) {
            return;
        }

        $userIds = self::signersOf($sign)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($userIds) {
            \App\Http\Controllers\General\NotificationController::sendNotification(
                'Đề nghị '.$request->code.' đang chờ bạn ký duyệt (bước '.$stepNo.').',
                'Trình ký',
                $requestId,
                $userIds,
                [],
                route('pages.export.materialExport.list', ['tab' => MaterialVpp::tabFor((int) $request->to_department_id)])
            );
        }
    }

    /** Câu báo sau khi gửi, theo kết quả start(). */
    public static function sendMessage(array $result, string $code, string $kindLabel, string $toDeptName): string
    {
        return match ($result['status']) {
            self::STATUS_PENDING_SIGN => 'Đã trình ký '.$kindLabel.' '.$code.' ('.$result['steps'].' bước duyệt). Ký xong phiếu mới tới '.$toDeptName.'.',
            'draft' => 'Chưa gửi được '.$kindLabel.' '.$code.' (phiếu giữ Lưu tạm): '.$result['error'],
            default => 'Đã gửi '.$kindLabel.' '.$code.' đến '.$toDeptName.' thành công!',
        };
    }

    /** Bước đang chờ ký của một phiếu (current_step). */
    public static function currentSign($request)
    {
        if (! $request || $request->status !== self::STATUS_PENDING_SIGN || ! $request->current_step) {
            return null;
        }

        return DB::table(self::SIGN_TABLE)
            ->where('transfer_request_id', $request->id)
            ->where('active', 1)
            ->where('step_no', (int) $request->current_step)
            ->first();
    }
}
