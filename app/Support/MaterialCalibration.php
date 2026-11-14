<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * HIỆU CHUẨN TRƯỚC KHI CẤP PHÁT - vật tư phân loại "Cần Hiệu Chuẩn Trước Khi Dùng"
 * (MaterialClassification::needsQaCalibration).
 *
 * Cấp phát nội bộ (đề nghị cấp phát sử dụng) vật tư loại này không cấp ngay được:
 *
 *   1. Kho chọn mã xuất nhập + số lượng ở tab Chờ Cấp Phát -> "Đề nghị hiệu chuẩn" (pending).
 *      Phần đó bị GIỮ CHỖ như hàng cách ly - không ai cấp / chuyển / cách ly được nữa.
 *   2. QA (phòng deparments.EngshortName = 'QA', quyền export_material_calibration_confirm)
 *      xác nhận cả phiếu: Đạt (passed) hoặc Không đạt (failed -> hàng tự sang Cách ly).
 *   3. Chỉ phần ĐẠT mới được cấp, đúng mã xuất nhập đã hiệu chuẩn, cho đúng dòng đề nghị đó.
 *
 * Ai xác nhận: người đề nghị CHỌN Phòng + Người xác nhận (một hay nhiều người của phòng đó, có
 * quyền xác nhận) khi gửi phiếu - bảng CONFIRMER_TABLE; chỉ những người đó, đang làm việc ở đúng
 * phòng đó, mới ký được (canConfirmCode). Phiếu chưa chọn người (phiếu cũ, phiếu HC sau cấp phát
 * chưa gửi) theo quy tắc cũ: QA cùng công ty; công ty chưa có phòng QA thì mọi phòng QA đều nhận.
 *
 * Query Builder thuần - song song với App\Support\MaterialPicking.
 */
class MaterialCalibration
{
    public const TABLE = 'material_calibrations';

    public const CODE_KIND = 'HCN';

    /** Kết quả từng đơn vị (Seri No + Đạt / Không đạt) - xem MaterialCalibrationController::confirm(). */
    public const UNIT_TABLE = 'material_calibration_units';

    /** Số đơn vị tối đa xác nhận riêng từng cái trên một mã xuất nhập; nhiều hơn thì xác nhận cả dòng. */
    public const UNIT_LIMIT = 500;

    public const CONFIRM_PERMISSION = 'export_material_calibration_confirm';

    /** Người xác nhận do người đề nghị chọn (theo mã phiếu) - xem confirmerOptions(). */
    public const CONFIRMER_TABLE = 'material_calibration_confirmers';

    /** Hiệu chuẩn TRƯỚC cấp phát (giữ chỗ tồn, đạt mới cấp) - qa_calibration = yes. */
    public const TIMING_BEFORE = 'before';

    /**
     * Hiệu chuẩn SAU cấp phát - qa_calibration = after: cấp phát bình thường, mỗi lần cấp tự sinh
     * một phiếu (issued_amount = amount, không giữ chỗ) gửi QA xác nhận. Không đạt KHÔNG tự cách ly
     * (hàng đã rời kho) - chỉ ghi kết quả và báo phòng thu hồi.
     */
    public const TIMING_AFTER = 'after';

    /** Trạng thái còn giữ chỗ tồn: chờ QA, hoặc đạt mà chưa cấp hết. */
    public const HOLDING_STATUSES = ['pending', 'passed'];

    public const STATUSES = [
        'pending' => 'Chờ QA hiệu chuẩn',
        'passed' => 'Đạt - chờ cấp phát',
        'issued' => 'Đạt - đã cấp phát',
        'failed' => 'Không đạt',
        'canceled' => 'Đã huỷ',
    ];

    private const EPSILON = 0.00005;

    /** Phần còn giữ của một dòng: chờ QA giữ đủ số, đạt thì giữ phần chưa cấp. */
    private const HELD_AMOUNT = 'GREATEST(amount - issued_amount, 0)';

    /**
     * Số đơn vị QA xác nhận riêng (mỗi cái một Seri No) của một dòng: số lượng nguyên trong
     * 1..UNIT_LIMIT; số lẻ / quá nhiều thì 0 = xác nhận cả dòng.
     */
    public static function unitCount(float $amount): int
    {
        $rounded = (int) round($amount);

        return abs($amount - $rounded) < self::EPSILON && $rounded >= 1 && $rounded <= self::UNIT_LIMIT ? $rounded : 0;
    }

    /** Id danh mục (trong $categoryIds) cần hiệu chuẩn trước khi dùng. */
    public static function categoryIdsNeeding($categoryIds): array
    {
        $ids = collect($categoryIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('material_categories')
            ->whereIn('id', $ids)
            ->pluck('classification', 'id')
            ->filter(fn ($json) => MaterialClassification::needsQaCalibration($json))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function needs(?int $categoryId): bool
    {
        return $categoryId && self::categoryIdsNeeding([$categoryId]);
    }

    /** Danh mục phân loại "HC sau cấp phát" (MaterialClassification::needsQaCalibrationAfter). */
    public static function needsAfter(?int $categoryId): bool
    {
        return $categoryId && MaterialClassification::needsQaCalibrationAfter(
            DB::table('material_categories')->where('id', $categoryId)->value('classification')
        );
    }

    /**
     * Sinh phiếu hiệu chuẩn SAU cấp phát cho một lần cấp: một mã phiếu, mỗi lô một dòng (đã cấp đủ
     * nên issued_amount = amount, không giữ chỗ tồn), đơn vị chờ QA chọn Đạt / Không đạt (chưa có
     * Seri No - QA xác nhận theo số thứ tự đơn vị).
     *
     * @param  array  $lots  [['import_id' => int, 'export_id' => ?int, 'amount' => float], ...]
     */
    public static function createAfterIssue(int $departmentId, $req, $item, array $lots, ?string $unit, string $actor): string
    {
        $now = now();

        return DB::transaction(function () use ($departmentId, $req, $item, $lots, $unit, $actor, $now) {
            $code = self::nextCode($departmentId);

            foreach ($lots as $lot) {
                $amount = round((float) $lot['amount'], 4);
                $calibrationId = DB::table(self::TABLE)->insertGetId([
                    'code' => $code,
                    'timing' => self::TIMING_AFTER,
                    'department_id' => $departmentId,
                    'request_list_id' => $req->id,
                    'request_item_id' => $item->id,
                    'category_id' => $item->category_id,
                    'import_id' => (int) $lot['import_id'],
                    'export_id' => $lot['export_id'] ?? null,
                    'amount' => $amount,
                    'issued_amount' => $amount,
                    'unit' => $unit,
                    'app_status' => 'pending',
                    'request_note' => 'Tự gửi khi cấp phát (HC sau cấp phát)',
                    'requested_by' => $actor,
                    'requested_at' => $now,
                    'status_id' => 1,
                    'created_by' => $actor,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $count = self::unitCount($amount);

                for ($i = 0; $i < max($count, 1); $i++) {
                    DB::table(self::UNIT_TABLE)->insert([
                        'calibration_id' => $calibrationId,
                        'code' => $code,
                        'import_id' => (int) $lot['import_id'],
                        'unit_no' => $count ? $i + 1 : 0,
                        'amount' => $count ? 1 : $amount,
                        'serial_no' => null,
                        'result' => null,
                        'status_id' => 1,
                        'created_by' => $actor,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            return $code;
        });
    }

    /* ---------- Giữ chỗ tồn ---------- */

    /** @return \Illuminate\Support\Collection import_id => số đang giữ */
    public static function heldByImport(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->select('import_id', DB::raw('SUM('.self::HELD_AMOUNT.') as total'))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->where('timing', self::TIMING_BEFORE)
            ->whereIn('app_status', self::HOLDING_STATUSES)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');
    }

    public static function heldOf(int $importId): float
    {
        return (float) DB::table(self::TABLE)
            ->where('import_id', $importId)
            ->where('status_id', 1)
            ->where('timing', self::TIMING_BEFORE)
            ->whereIn('app_status', self::HOLDING_STATUSES)
            ->selectRaw('COALESCE(SUM('.self::HELD_AMOUNT.'), 0) as total')
            ->value('total');
    }

    /**
     * Phần ĐẠT còn chưa cấp của một dòng đề nghị, theo từng mã xuất nhập.
     *
     * @return array<int, float> import_id => số được cấp
     */
    public static function passedByImport(int $itemId): array
    {
        return DB::table(self::TABLE)
            ->select('import_id', DB::raw('SUM('.self::HELD_AMOUNT.') as total'))
            ->where('request_item_id', $itemId)
            ->where('status_id', 1)
            ->where('timing', self::TIMING_BEFORE)
            ->where('app_status', 'passed')
            ->groupBy('import_id')
            ->pluck('total', 'import_id')
            ->map(fn ($total) => round((float) $total, 4))
            ->filter(fn ($total) => $total > self::EPSILON)
            ->all();
    }

    /**
     * Ghi phần đã cấp vào các dòng ĐẠT của dòng đề nghị (dòng cũ trước), dòng dùng hết thì
     * chuyển 'issued' - nhả giữ chỗ đúng phần đã thành phiếu sử dụng.
     *
     * @param  array<int, float>  $issued  import_id => số vừa cấp
     */
    public static function consume(int $itemId, array $issued, string $actor): void
    {
        foreach ($issued as $importId => $amount) {
            $left = (float) $amount;

            $rows = DB::table(self::TABLE)
                ->where('request_item_id', $itemId)
                ->where('import_id', $importId)
                ->where('status_id', 1)
                ->where('timing', self::TIMING_BEFORE)
                ->where('app_status', 'passed')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                if ($left <= self::EPSILON) {
                    break;
                }

                $take = min($left, max((float) $row->amount - (float) $row->issued_amount, 0));
                $newIssued = round((float) $row->issued_amount + $take, 4);
                $left -= $take;

                DB::table(self::TABLE)->where('id', $row->id)->update([
                    'issued_amount' => $newIssued,
                    'app_status' => $newIssued + self::EPSILON >= (float) $row->amount ? 'issued' : 'passed',
                    'updated_by' => $actor,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Dòng đề nghị đã cấp đủ mà còn phần ĐẠT chưa dùng (gửi hiệu chuẩn dư): kết thúc các dòng đạt
     * (issued) để phần dư - đã hiệu chuẩn đạt - trả về tồn chung, không giữ chỗ mãi.
     */
    public static function releaseForItem(int $itemId, string $actor): int
    {
        return DB::table(self::TABLE)
            ->where('request_item_id', $itemId)
            ->where('status_id', 1)
            ->where('timing', self::TIMING_BEFORE)
            ->where('app_status', 'passed')
            ->update([
                'app_status' => 'issued',
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);
    }

    /** Kho từ chối cấp dòng đề nghị: huỷ mọi phiếu hiệu chuẩn còn giữ chỗ của dòng đó. */
    public static function cancelForItem(int $itemId, string $actor, string $note): int
    {
        return DB::table(self::TABLE)
            ->where('request_item_id', $itemId)
            ->where('status_id', 1)
            ->where('timing', self::TIMING_BEFORE)
            ->whereIn('app_status', self::HOLDING_STATUSES)
            ->update([
                'app_status' => 'canceled',
                'result_note' => $note,
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);
    }

    /* ---------- Trạng thái theo dòng đề nghị (màn Chờ Cấp Phát / phiếu chi tiết) ---------- */

    /**
     * item_id => [
     *   'pending'      => số đang chờ QA,
     *   'pending_code' => mã phiếu đang chờ,
     *   'passed'       => import_id => phần đạt chưa cấp,
     *   'passed_total' => tổng phần đạt chưa cấp,
     *   'passed_codes' => mã phiếu đạt,
     *   'failed'       => phiếu không đạt gần nhất (mã + ghi chú) hoặc null,
     * ]
     */
    public static function statesForItems($itemIds): array
    {
        $itemIds = collect($itemIds)->filter()->unique()->values();

        if ($itemIds->isEmpty()) {
            return [];
        }

        $rows = DB::table(self::TABLE)
            ->whereIn('request_item_id', $itemIds)
            ->where('status_id', 1)
            ->where('timing', self::TIMING_BEFORE)
            ->whereIn('app_status', ['pending', 'passed', 'failed'])
            ->orderBy('id')
            ->get();

        $states = [];

        foreach ($rows->groupBy('request_item_id') as $itemId => $itemRows) {
            $state = ['pending' => 0.0, 'pending_code' => null, 'passed' => [], 'passed_total' => 0.0, 'passed_codes' => [], 'failed' => null];

            foreach ($itemRows as $row) {
                $held = max((float) $row->amount - (float) $row->issued_amount, 0);

                if ($row->app_status === 'pending') {
                    $state['pending'] += $held;
                    $state['pending_code'] = $row->code;
                } elseif ($row->app_status === 'passed' && $held > self::EPSILON) {
                    $state['passed'][(int) $row->import_id] = round(($state['passed'][(int) $row->import_id] ?? 0) + $held, 4);
                    $state['passed_total'] += $held;
                    $state['passed_codes'][$row->code] = true;
                } elseif ($row->app_status === 'failed') {
                    $state['failed'] = ['code' => $row->code, 'note' => $row->result_note, 'at' => $row->result_at];
                }
            }

            $state['pending'] = round($state['pending'], 4);
            $state['passed_total'] = round($state['passed_total'], 4);
            $state['passed_codes'] = array_keys($state['passed_codes']);
            $states[(int) $itemId] = $state;
        }

        return $states;
    }

    /**
     * Dòng cấp phát dựng từ phần ĐẠT - cùng bộ khoá với MaterialPicking::line() để form cấp
     * phát dùng chung. Lô đang giữ cho chính dòng này nên 'available' = đúng phần đạt.
     *
     * $cap = số còn phải cấp: số cấp gợi ý rót lần lượt các lô đạt tới đủ $cap (gửi hiệu chuẩn dư
     * thì không gợi ý cấp hết phần đạt); người cấp phát vẫn sửa được tới tối đa phần đạt.
     */
    public static function planLines(array $passed, $lots, ?float $cap = null): array
    {
        $lots = collect($lots)->keyBy('id');
        $lines = [];
        $left = $cap;

        foreach ($passed as $importId => $amount) {
            $lot = $lots->get($importId);
            $suggested = $left === null ? (float) $amount : max(min((float) $amount, $left), 0);

            if ($left !== null) {
                $left -= $suggested;
            }

            $lines[] = [
                'import_id' => (int) $importId,
                'import_code' => $lot->code ?? '',
                'location_id' => isset($lot->location_id) ? (int) $lot->location_id : null,
                'location_code' => $lot->location_code ?? null,
                'location_zone_type' => $lot->location_zone_type ?? null,
                'expired_date' => $lot->expired_date ?? null,
                'expiry_level' => $lot->expiry_level ?? null,
                'suggested_amount' => round($suggested, 4),
                'unit' => $lot->unit_short_name ?? null,
                'available' => round((float) $amount, 4),
            ];
        }

        return $lines;
    }

    /* ---------- Phòng QA ---------- */

    public static function isQaDepartment(?int $departmentId): bool
    {
        return $departmentId && DB::table('deparments')
            ->where('id', $departmentId)
            ->where('EngshortName', 'QA')
            ->exists();
    }

    /* ---------- Người xác nhận do người đề nghị chọn ---------- */

    /**
     * Ứng viên xác nhận cho phiếu của phòng $departmentId, nhóm theo phòng (chọn phòng trước, lọc
     * người theo phòng): user đang hoạt động được gán vai trò có quyền CONFIRM_PERMISSION TẠI một
     * phòng (user_role.department_id) - không tính vai trò Admin (Admin có mọi quyền ở mọi phòng),
     * kiểm lại bằng user_permission_names() để tôn trọng quyền bị chặn riêng. Chỉ phòng cùng công
     * ty với phòng đề nghị (công ty không có ai thì lấy mọi phòng). Bỏ chính người đề nghị.
     *
     * Phòng QA xếp trước (là phòng mặc định), rồi theo tên phòng.
     *
     * @return array<int, array{id:int, name:string, is_qa:bool, users:array<int, array{id:int, name:string}>}>
     */
    public static function confirmerOptions(int $departmentId, ?int $excludeUserId = null): array
    {
        $permissionId = DB::table('permissions')->where('name', self::CONFIRM_PERMISSION)->value('id');

        if (! $permissionId) {
            return [];
        }

        $rows = DB::table('user_role')
            ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->join('user_management', 'user_management.id', '=', 'user_role.user_id')
            ->join('deparments', 'deparments.id', '=', 'user_role.department_id')
            ->where('role_permission.permission_id', $permissionId)
            ->where('roles.name', '<>', 'Admin')
            ->whereNotNull('user_role.department_id')
            ->where('user_management.isActive', 1)
            ->where('deparments.isActive', 1)
            ->when($excludeUserId, fn ($query) => $query->where('user_management.id', '<>', $excludeUserId))
            ->select(
                'user_role.user_id',
                'user_role.department_id',
                'user_management.fullName',
                'user_management.userName',
                'deparments.name as department_name',
                'deparments.EngshortName',
                'deparments.company_id'
            )
            ->distinct()
            ->get()
            ->filter(fn ($row) => isset(user_permission_names((int) $row->user_id, (int) $row->department_id)[self::CONFIRM_PERMISSION]));

        $companyId = CompanyContext::resolveForDepartment($departmentId);
        $sameCompany = $rows->filter(fn ($row) => (int) $row->company_id === (int) $companyId);
        $rows = $sameCompany->isNotEmpty() ? $sameCompany : $rows;

        return $rows->groupBy('department_id')
            ->map(function ($group, $id) {
                $first = $group->first();

                return [
                    'id' => (int) $id,
                    'name' => (string) $first->department_name,
                    'is_qa' => $first->EngshortName === 'QA',
                    'users' => $group->unique('user_id')
                        ->map(fn ($row) => ['id' => (int) $row->user_id, 'name' => self::userLabel($row->fullName, $row->userName)])
                        ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy(fn ($dept) => ($dept['is_qa'] ? '0' : '1').mb_strtolower($dept['name']))
            ->values()
            ->all();
    }

    /**
     * Kiểm phòng + danh sách người xác nhận người đề nghị gửi lên: phòng phải có trong ứng viên,
     * mọi người phải thuộc phòng đó. Trả về [department_id, [user_id...]] hoặc câu báo lỗi.
     *
     * @return array{0:int, 1:array<int>}|string
     */
    public static function validateConfirmers(int $departmentId, ?int $requesterId, $confirmDepartmentId, $userIds): array|string
    {
        $confirmDepartmentId = (int) $confirmDepartmentId;
        $userIds = array_values(array_unique(array_filter(array_map('intval', (array) $userIds))));

        if (! $confirmDepartmentId) {
            return 'Vui lòng chọn phòng của người xác nhận hiệu chuẩn.';
        }

        if (! $userIds) {
            return 'Vui lòng chọn ít nhất một người xác nhận hiệu chuẩn.';
        }

        $dept = collect(self::confirmerOptions($departmentId, $requesterId))->firstWhere('id', $confirmDepartmentId);

        if (! $dept) {
            return 'Phòng được chọn không có người xác nhận hiệu chuẩn vật tư.';
        }

        $allowed = array_column($dept['users'], 'id');

        foreach ($userIds as $userId) {
            if (! in_array($userId, $allowed, true)) {
                return $requesterId && $userId === $requesterId
                    ? 'Không được tự chọn mình xác nhận hiệu chuẩn phiếu của mình.'
                    : 'Người được chọn không có quyền xác nhận hiệu chuẩn vật tư ở phòng '.$dept['name'].'.';
            }
        }

        return [$confirmDepartmentId, $userIds];
    }

    /** Ghi (thay toàn bộ) người xác nhận của phiếu $code - người cũ chuyển status_id = 0. */
    public static function assignConfirmers(string $code, int $confirmDepartmentId, array $userIds, string $actor): void
    {
        $now = now();

        DB::table(self::CONFIRMER_TABLE)
            ->where('code', $code)
            ->where('status_id', 1)
            ->update(['status_id' => 0, 'updated_by' => $actor, 'updated_at' => $now]);

        foreach ($userIds as $userId) {
            DB::table(self::CONFIRMER_TABLE)->insert([
                'code' => $code,
                'department_id' => $confirmDepartmentId,
                'user_id' => $userId,
                'status_id' => 1,
                'created_by' => $actor,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Người xác nhận đang được chọn của các phiếu.
     *
     * @return array<string, array{department_id:int, department_name:string, users:array<int, array{id:int, name:string}>}>
     */
    public static function confirmersOf($codes): array
    {
        $codes = collect($codes)->filter()->unique()->values();

        if ($codes->isEmpty()) {
            return [];
        }

        return DB::table(self::CONFIRMER_TABLE.' as c')
            ->join('user_management as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('deparments as d', 'd.id', '=', 'c.department_id')
            ->whereIn('c.code', $codes)
            ->where('c.status_id', 1)
            ->select('c.code', 'c.department_id', 'c.user_id', 'u.fullName', 'u.userName', 'd.name as department_name')
            ->orderBy('u.fullName')
            ->get()
            ->groupBy('code')
            ->map(fn ($group) => [
                'department_id' => (int) $group->first()->department_id,
                'department_name' => (string) $group->first()->department_name,
                'users' => $group->map(fn ($row) => ['id' => (int) $row->user_id, 'name' => self::userLabel($row->fullName, $row->userName)])->values()->all(),
            ])
            ->all();
    }

    /** Mã phiếu đang chờ mà $userId được chọn xác nhận, theo phòng xác nhận: [code => department_id]. */
    public static function pendingCodesAssignedTo(int $userId): array
    {
        return DB::table(self::CONFIRMER_TABLE.' as c')
            ->join(self::TABLE.' as m', 'm.code', '=', 'c.code')
            ->where('c.user_id', $userId)
            ->where('c.status_id', 1)
            ->where('m.status_id', 1)
            ->where('m.app_status', 'pending')
            ->distinct()
            ->pluck('c.department_id', 'c.code')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Người đang đăng nhập ($userId, đang làm việc ở $departmentId) có ký xác nhận được phiếu không:
     * phiếu đã chọn người thì phải là một trong những người đó và đang ở đúng phòng xác nhận; phiếu
     * chưa chọn người thì theo quy tắc cũ - phòng QA phụ trách phòng đề nghị.
     */
    public static function canConfirmCode(string $code, int $sourceDepartmentId, int $userId, int $departmentId, ?array $confirmers = null): bool
    {
        $confirmers ??= self::confirmersOf([$code])[$code] ?? null;

        if ($confirmers) {
            return $confirmers['department_id'] === $departmentId
                && in_array($userId, array_column($confirmers['users'], 'id'), true);
        }

        return in_array($sourceDepartmentId, self::sourceDepartmentIds($departmentId), true);
    }

    private static function userLabel(?string $fullName, ?string $userName): string
    {
        $fullName = trim((string) $fullName);

        return $fullName !== '' ? $fullName.($userName ? ' ('.$userName.')' : '') : (string) $userName;
    }

    /** Phòng QA nhận đề nghị hiệu chuẩn của $departmentId: QA cùng công ty, không có thì mọi QA. */
    public static function qaDepartmentIdsFor(int $departmentId): array
    {
        $qaDepartments = DB::table('deparments')
            ->where('EngshortName', 'QA')
            ->where('isActive', 1)
            ->select('id', 'company_id')
            ->get();

        $companyId = CompanyContext::resolveForDepartment($departmentId);
        $sameCompany = $qaDepartments->where('company_id', $companyId);

        return ($sameCompany->isNotEmpty() ? $sameCompany : $qaDepartments)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Các phòng mà phòng QA $qaDepartmentId phụ trách hiệu chuẩn (ngược của qaDepartmentIdsFor). */
    public static function sourceDepartmentIds(int $qaDepartmentId): array
    {
        if (! self::isQaDepartment($qaDepartmentId)) {
            return [];
        }

        $companyId = CompanyContext::resolveForDepartment($qaDepartmentId);
        $companiesWithQa = DB::table('deparments')
            ->where('EngshortName', 'QA')
            ->where('isActive', 1)
            ->whereNotNull('company_id')
            ->distinct()
            ->pluck('company_id')
            ->all();

        return DB::table('deparments')
            ->where(function ($query) use ($companyId, $companiesWithQa) {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id')
                    ->orWhereNotIn('company_id', $companiesWithQa ?: [0]);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** HCN-07-7KPMR9J4WD - cùng cách sinh đuôi ngẫu nhiên với mã lô vật tư. */
    public static function nextCode(int $departmentId): string
    {
        $prefix = self::CODE_KIND.MaterialCode::SEP
            .str_pad((string) $departmentId, MaterialCode::DEPT_LENGTH, '0', STR_PAD_LEFT).MaterialCode::SEP;

        do {
            $code = $prefix.MaterialCode::randomTail();
        } while (DB::table(self::TABLE)->where('code', $code)->exists());

        return $code;
    }
}
