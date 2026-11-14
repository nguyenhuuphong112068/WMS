<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * QUY TRÌNH KÝ ĐỀ NGHỊ LIÊN PHÒNG BAN - dùng chung cho Vật Tư, VPP, Hoá Chất, Chất Chuẩn.
 *
 * Từ 14/11/2026 (mỗi nhà máy một bản WMS riêng) quy trình CỐ ĐỊNH 2 bước, nằm giữa bước Gửi và
 * bước Cấp phát:
 *   1. Quản Lý Bộ Phận của PHÒNG ĐỀ NGHỊ        (department_id của phiếu)
 *   2. Quản Lý Bộ Phận của PHÒNG CẤP PHÁT        (to_department_id - phòng được đề nghị)
 * Người ký = mọi user có vai trò config('estimate.department_manager_roles') với phạm vi bao gồm
 * phòng của bước (App\Support\EstimateSigner), ai ký trước cũng được, ký LẦN LƯỢT, nhập lại mật
 * khẩu (21 CFR Part 11). Màn Dữ Liệu Gốc "Trình Ký Đề Nghị Liên Phòng Ban" (VT / VPP) không còn
 * được đọc - đã ẩn khỏi menu.
 *
 * Bấm Gửi: chụp 2 bước vào bảng <loại>_transfer_signs, phiếu chuyển 'pending_sign' (dòng giữ
 * 'draft'). Ký xong bước 2 -> 'pending' (phòng cấp phát bắt đầu cấp). Từ chối -> về 'draft' kèm
 * lý do để phòng đề nghị sửa rồi gửi lại.
 *
 * Lớp con khai 3 bảng: REQUEST_TABLE, ITEM_TABLE, SIGN_TABLE.
 */
abstract class TransferSignFlow
{
    public const REQUEST_TABLE = '';

    public const ITEM_TABLE = '';

    public const SIGN_TABLE = '';

    /** Trạng thái phiếu đang chờ ký. */
    public const STATUS_PENDING_SIGN = 'pending_sign';

    /** Nhãn 2 bước - hiện ở hộp thoại / hướng dẫn. */
    public const STEP_LABELS = [
        1 => 'Quản lý bộ phận phòng đề nghị',
        2 => 'Quản lý bộ phận phòng cấp phát',
    ];

    /**
     * Quy trình áp cho một phiếu (đầu phiếu <loại>_transfer_requests): 2 bước đã giải phòng.
     *
     * @return array<int, array{step_no:int, role_names:string, department_id:int}>
     */
    public static function resolve($request): array
    {
        $roles = implode(', ', EstimateSigner::managerRoles());

        return [
            ['step_no' => 1, 'role_names' => $roles, 'department_id' => (int) $request->department_id],
            ['step_no' => 2, 'role_names' => $roles, 'department_id' => (int) $request->to_department_id],
        ];
    }

    /** Bước nào chưa có ai ký được thì báo lỗi (để chặn Gửi). */
    public static function missingSignerError(array $steps): ?string
    {
        foreach ($steps as $step) {
            if (EstimateSigner::usersWithRoles(EstimateSigner::stepRoles((object) $step), $step['department_id'])->isEmpty()) {
                $department = DB::table('deparments')->where('id', $step['department_id'])->value('name') ?: 'NA';

                return 'Bước ký '.$step['step_no'].' ('.(static::STEP_LABELS[$step['step_no']] ?? '').') chưa có tài khoản '
                    .$step['role_names'].' nào của phòng '.$department.'. Hãy phân quyền ở màn Phân quyền.';
            }
        }

        return null;
    }

    /** Người ký được một dòng <loại>_transfer_signs. */
    public static function signersOf($sign)
    {
        return EstimateSigner::usersWithRoles(EstimateSigner::stepRoles($sign), (int) $sign->department_id);
    }

    public static function canSign(int $userId, $sign): bool
    {
        return $userId > 0 && $sign && static::signersOf($sign)->contains('id', $userId);
    }

    /** Các bước còn hiệu lực của nhiều phiếu: request_id => bước theo step_no (kèm tên phòng). */
    public static function signsOf(array $requestIds)
    {
        if (! $requestIds) {
            return collect();
        }

        return DB::table(static::SIGN_TABLE)
            ->leftJoin('deparments', 'deparments.id', '=', static::SIGN_TABLE.'.department_id')
            ->where(static::SIGN_TABLE.'.active', 1)
            ->whereIn(static::SIGN_TABLE.'.transfer_request_id', $requestIds)
            ->orderBy(static::SIGN_TABLE.'.step_no')
            ->get([static::SIGN_TABLE.'.*', 'deparments.shortName as department_short', 'deparments.name as department_name'])
            ->groupBy('transfer_request_id');
    }

    /**
     * Trong các phiếu, những phiếu người đăng nhập ký được ở bước đang chờ.
     *
     * @return array<int, int> request_id => step_no
     */
    public static function signableOf($requests, int $userId): array
    {
        $pending = collect($requests)->filter(fn ($req) => $req->status === static::STATUS_PENDING_SIGN && $req->current_step);

        if ($pending->isEmpty() || $userId <= 0) {
            return [];
        }

        $signs = static::signsOf($pending->pluck('id')->unique()->values()->all());
        $result = [];

        foreach ($pending as $req) {
            $sign = $signs->get($req->id, collect())->firstWhere('step_no', (int) $req->current_step);

            if ($sign && $sign->status === 'pending' && static::canSign($userId, $sign)) {
                $result[(int) $req->id] = (int) $sign->step_no;
            }
        }

        return $result;
    }

    /* ==========================================================
     |  VÒNG ĐỜI KÝ: gửi -> ký từng bước -> tới phòng cấp phát / bị từ chối
     ========================================================== */

    /**
     * Gửi một phiếu đang Lưu tạm -> 'pending_sign' (dòng vẫn 'draft', phòng cấp phát chưa cấp
     * được). Bước nào chưa có người ký thì giữ Lưu tạm và trả lỗi.
     *
     * @return array{status: string, error: ?string, steps: int}
     */
    public static function start(int $requestId, string $actor): array
    {
        $request = DB::table(static::REQUEST_TABLE)->where('id', $requestId)->first();
        $steps = $request ? static::resolve($request) : [];

        if (! $steps) {
            static::release($requestId, $actor);

            return ['status' => 'pending', 'error' => null, 'steps' => 0];
        }

        if ($error = static::missingSignerError($steps)) {
            DB::table(static::REQUEST_TABLE)->where('id', $requestId)->update(['status' => 'draft', 'updated_at' => now()]);
            DB::table(static::ITEM_TABLE)->where('transfer_request_id', $requestId)
                ->where('active', 1)->whereIn('status', ['draft', 'pending'])->update(['status' => 'draft', 'updated_at' => now()]);

            return ['status' => 'draft', 'error' => $error, 'steps' => count($steps)];
        }

        DB::transaction(function () use ($requestId, $steps, $actor) {
            DB::table(static::SIGN_TABLE)->where('transfer_request_id', $requestId)->update(['active' => 0, 'updated_at' => now()]);

            foreach ($steps as $step) {
                DB::table(static::SIGN_TABLE)->insert($step + [
                    'transfer_request_id' => $requestId,
                    'status' => 'pending',
                    'active' => 1,
                    'created_by' => $actor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table(static::REQUEST_TABLE)->where('id', $requestId)->update([
                'status' => static::STATUS_PENDING_SIGN,
                'current_step' => 1,
                'sign_rejected_by' => null,
                'sign_rejected_at' => null,
                'sign_reject_reason' => null,
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);

            DB::table(static::ITEM_TABLE)->where('transfer_request_id', $requestId)
                ->where('active', 1)->update(['status' => 'draft', 'updated_at' => now()]);
        });

        return ['status' => static::STATUS_PENDING_SIGN, 'error' => null, 'steps' => count($steps)];
    }

    /**
     * Ký bước đang chờ (đã kiểm quyền + mật khẩu ở controller). Bước cuối -> phiếu tới phòng
     * cấp phát ('pending'). @return bool phiếu đã ký xong toàn bộ
     *
     * $rejectItems (item_id => lý do): duyệt MỘT PHẦN - các mục này chuyển 'rejected' ngay (lý
     * do ghi kèm bước ký vào reject_note), không đi tiếp. Controller đã chặn từ chối hết.
     */
    public static function sign($request, $sign, string $actor, ?string $note = null, array $rejectItems = []): bool
    {
        $total = (int) DB::table(static::SIGN_TABLE)->where('transfer_request_id', $request->id)->where('active', 1)->count();
        $isLast = (int) $sign->step_no >= $total;

        DB::transaction(function () use ($request, $sign, $actor, $note, $isLast, $rejectItems) {
            foreach ($rejectItems as $itemId => $reason) {
                DB::table(static::ITEM_TABLE)
                    ->where('id', $itemId)
                    ->where('transfer_request_id', $request->id)
                    ->where('active', 1)
                    ->where('status', 'draft')
                    ->update([
                        'status' => 'rejected',
                        'reject_note' => 'Từ chối khi ký bước '.$sign->step_no.' ('.$actor.'): '.$reason,
                        'updated_at' => now(),
                    ]);
            }

            DB::table(static::SIGN_TABLE)->where('id', $sign->id)->update([
                'status' => 'signed',
                'signed_by' => $actor,
                'signed_at' => now(),
                'note' => $note,
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);

            if ($isLast) {
                static::release((int) $request->id, $actor);
            } else {
                DB::table(static::REQUEST_TABLE)->where('id', $request->id)->update([
                    'current_step' => (int) $sign->step_no + 1,
                    'updated_by' => $actor,
                    'updated_at' => now(),
                ]);
            }
        });

        return $isLast;
    }

    /** Từ chối ở bước đang chờ: phiếu về Lưu tạm kèm lý do để phòng đề nghị sửa rồi gửi lại. */
    public static function reject($request, $sign, string $actor, string $reason): void
    {
        DB::transaction(function () use ($request, $sign, $actor, $reason) {
            DB::table(static::SIGN_TABLE)->where('id', $sign->id)->update([
                'status' => 'rejected',
                'signed_by' => $actor,
                'signed_at' => now(),
                'note' => $reason,
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);

            DB::table(static::REQUEST_TABLE)->where('id', $request->id)->update([
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

    /** Phiếu (và các dòng Lưu tạm) chuyển sang 'pending' - phòng cấp phát bắt đầu cấp phát. */
    protected static function release(int $requestId, string $actor): void
    {
        DB::table(static::REQUEST_TABLE)->where('id', $requestId)->update([
            'status' => 'pending',
            'current_step' => null,
            'updated_by' => $actor,
            'updated_at' => now(),
        ]);

        DB::table(static::ITEM_TABLE)->where('transfer_request_id', $requestId)
            ->where('active', 1)->where('status', 'draft')->update(['status' => 'pending', 'updated_at' => now()]);
    }

    /** Câu báo sau khi gửi, theo kết quả start(). */
    public static function sendMessage(array $result, string $code, string $kindLabel, string $toDeptName): string
    {
        return match ($result['status']) {
            static::STATUS_PENDING_SIGN => 'Đã trình ký '.$kindLabel.' '.$code.' ('.$result['steps'].' bước duyệt: quản lý bộ phận phòng đề nghị → quản lý bộ phận '.$toDeptName.'). Ký xong phiếu mới tới '.$toDeptName.' để cấp phát.',
            'draft' => 'Chưa gửi được '.$kindLabel.' '.$code.' (phiếu giữ Lưu tạm): '.$result['error'],
            default => 'Đã gửi '.$kindLabel.' '.$code.' đến '.$toDeptName.' thành công!',
        };
    }

    /** Bước đang chờ ký của một phiếu (current_step). */
    public static function currentSign($request)
    {
        if (! $request || $request->status !== static::STATUS_PENDING_SIGN || ! $request->current_step) {
            return null;
        }

        return DB::table(static::SIGN_TABLE)
            ->where('transfer_request_id', $request->id)
            ->where('active', 1)
            ->where('step_no', (int) $request->current_step)
            ->first();
    }
}
