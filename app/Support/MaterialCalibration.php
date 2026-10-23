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
 * QA phụ trách phòng nào: QA cùng công ty; công ty chưa có phòng QA thì mọi phòng QA đều
 * nhận (giống thông báo cũ khi cấp phát, để không lọt việc).
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

    /* ---------- Giữ chỗ tồn ---------- */

    /** @return \Illuminate\Support\Collection import_id => số đang giữ */
    public static function heldByImport(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->select('import_id', DB::raw('SUM('.self::HELD_AMOUNT.') as total'))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->whereIn('app_status', self::HOLDING_STATUSES)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');
    }

    public static function heldOf(int $importId): float
    {
        return (float) DB::table(self::TABLE)
            ->where('import_id', $importId)
            ->where('status_id', 1)
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
