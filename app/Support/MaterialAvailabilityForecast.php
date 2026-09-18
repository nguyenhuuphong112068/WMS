<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CẢNH BÁO LƯỢNG VẬT TƯ KHẢ DỤNG CHO THÁNG TIẾP THEO
 *
 * Trả lời đúng một câu hỏi: tồn đang có của phòng có đủ cho nhu cầu THEO CHU KỲ của tháng
 * tới không, và thiếu bao nhiêu - để người dùng kịp lập dự trù.
 *
 *      Khả dụng = Tồn hiện hành
 *               - Vật tư đã đề nghị nhưng kho chưa cấp phát
 *               - Vật tư chu kỳ CHƯA tạo đề nghị, còn phát sinh từ nay tới hết tháng này
 *
 *      Thiếu / Đủ = Khả dụng - Nhu cầu theo chu kỳ của THÁNG ĐANG XÉT
 *
 * PHẠM VI - chỉ tính phần nhu cầu ĂN VÀO TỒN CỦA PHÒNG ĐANG CHỌN:
 *
 *      - Danh sách chu kỳ NỘI BỘ của phòng (department_id = phòng đang chọn): tổ đề nghị,
 *        kho của chính phòng cấp phát nên trừ thẳng vào tồn.
 *      - Danh sách chu kỳ LIÊN PHÒNG BAN gửi TỚI phòng (to_department_id = phòng đang chọn):
 *        phòng khác đề nghị mình cấp, hàng cũng rời kho mình.
 *
 * Danh sách liên phòng ban do chính phòng lập để XIN phòng khác (department_id = phòng đang
 * chọn, type = external) KHÔNG tính vào nhu cầu: phần đó trừ vào tồn của phòng được đề nghị,
 * và phòng đó thấy nó ở màn hình này của họ.
 *
 * TỒN HIỆN HÀNH lấy đúng công thức tồn của màn Tồn Kho (App\Support\MaterialPicking::lots),
 * BỎ các lô đã hết hạn - lô hết hạn không cấp phát được nên không tính là khả dụng.
 *
 * ĐƠN VỊ: cộng thẳng số lượng như các màn đề nghị / tồn khác đang làm (không quy đổi). Dòng
 * chu kỳ khai đơn vị khác đơn vị của phòng được gắn cờ unit_mixed để màn hình cảnh báo.
 *
 * Query Builder thuần, không Eloquent.
 */
class MaterialAvailabilityForecast
{
    /** Sai số so sánh số thập phân - giống MaterialExportController / MaterialPicking. */
    public const EPSILON = 0.00005;

    /** Đề nghị nội bộ còn giữ chỗ tồn: đã duyệt nhưng kho chưa cấp đủ. */
    private const REQ_PENDING_ISSUE_STATUSES = ['waiting', 'partial'];

    /** Dòng đề nghị nội bộ còn giữ chỗ tồn. */
    private const REQ_PENDING_ITEM_STATUSES = ['pending', 'partial'];

    /**
     * Đề nghị nội bộ chưa cấp phát, TÍNH CẢ PHIẾU LƯU TẠM: chu kỳ tới hạn là hệ thống tạo
     * sẵn một phiếu Lưu tạm, người đề nghị chỉ chỉnh rồi trình ký. Bỏ phiếu Lưu tạm ra thì
     * phần nhu cầu đó rơi khỏi cả hai vế (không còn "chưa tạo đề nghị", cũng chưa "đã đề
     * nghị") và màn hình báo thừa hàng.
     */
    private const REQ_PENDING_APP_STATUSES = ['draft', 'pending_sign'];

    /** Đề nghị liên phòng ban gửi tới phòng mà phòng chưa cấp phát - cũng tính phiếu Lưu tạm. */
    private const TRANSFER_PENDING_STATUSES = ['draft', 'pending', 'partial'];

    /** Số tháng cho người dùng chọn xem trước. */
    private const MONTH_OPTIONS = 6;

    public const STATES = [
        'short' => 'Không đáp ứng',
        'tight' => 'Vừa đủ',
        'ok' => 'Đáp ứng',
    ];

    /**
     * @param  string|null  $month  Tháng đang xét dạng Y-m; bỏ trống = tháng tiếp theo.
     * @return array{month: array, pre: array, options: array, rows: \Illuminate\Support\Collection, summary: array, states: array}
     */
    public static function build(int $departmentId, ?string $month = null): array
    {
        $today = now()->startOfDay();
        $target = self::targetMonth($month, $today);

        // Khoảng "từ nay tới trước tháng đang xét": phần chu kỳ của tháng này chưa tạo đề nghị
        $preFrom = $today->copy();
        $preTo = $target->copy()->subDay();

        $lists = self::lists($departmentId);
        $items = self::items($lists->pluck('id')->all());

        $demand = self::demand($lists, $items, $target->format('Y-m-d'), $target->copy()->endOfMonth()->format('Y-m-d'));
        $pending = self::demand($lists, $items, $preFrom->format('Y-m-d'), $preTo->format('Y-m-d'));

        $stock = self::stock($departmentId);
        $reserved = self::reserved($departmentId);
        $categories = self::categories($departmentId, array_keys($demand));

        $rows = collect(array_keys($demand))
            ->map(function ($categoryId) use ($categories, $stock, $reserved, $demand, $pending) {
                $category = $categories[$categoryId] ?? null;

                if (! $category) {
                    return null;
                }

                $row = clone $category;
                $row->stock = (float) ($stock[$categoryId] ?? 0);
                $row->reserved = (float) ($reserved[$categoryId] ?? 0);
                $row->pending = (float) ($pending[$categoryId]['amount'] ?? 0);
                $row->demand = (float) $demand[$categoryId]['amount'];
                $row->sources = $demand[$categoryId]['sources'];
                $row->pending_sources = $pending[$categoryId]['sources'] ?? [];
                $row->min_stock = $row->min_stock !== null ? (float) $row->min_stock : null;

                $row->available = $row->stock - $row->reserved - $row->pending;
                $row->gap = $row->available - $row->demand;

                // Còn lại sau tháng tới vẫn phải đủ ngưỡng tồn tối thiểu của phòng
                $floor = $row->min_stock ?: 0;
                $row->state = match (true) {
                    $row->gap < -self::EPSILON => 'short',
                    $row->gap < $floor - self::EPSILON => 'tight',
                    default => 'ok',
                };
                $row->state_label = self::STATES[$row->state];

                // Lượng nên dự trù: bù đủ nhu cầu tháng tới và ngưỡng tồn tối thiểu
                $row->suggest = max($floor - $row->gap, 0);

                $row->unit_mixed = collect($row->sources)->contains(
                    fn ($source) => $source['unit'] && $row->unit_short_name && $source['unit'] !== $row->unit_short_name
                );

                return $row;
            })
            ->filter()
            ->sortBy([
                fn ($a, $b) => array_search($a->state, ['short', 'tight', 'ok']) <=> array_search($b->state, ['short', 'tight', 'ok']),
                fn ($a, $b) => strcmp((string) $a->material_name, (string) $b->material_name),
            ])
            ->values();

        return [
            'month' => [
                'value' => $target->format('Y-m'),
                'label' => 'Tháng '.$target->format('m/Y'),
                'from' => $target->format('Y-m-d'),
                'to' => $target->copy()->endOfMonth()->format('Y-m-d'),
            ],
            'pre' => [
                'from' => $preFrom->format('Y-m-d'),
                'to' => $preTo->format('Y-m-d'),
                'days' => $preTo->gte($preFrom) ? (int) $preFrom->diffInDays($preTo) + 1 : 0,
            ],
            'options' => self::monthOptions($today, $target),
            'rows' => $rows,
            'summary' => [
                'total' => $rows->count(),
                'short' => $rows->where('state', 'short')->count(),
                'tight' => $rows->where('state', 'tight')->count(),
                'ok' => $rows->where('state', 'ok')->count(),
                'lists' => $lists->count(),
            ],
            'states' => self::STATES,
        ];
    }

    /** Tháng đang xét: tham số Y-m hợp lệ, không lùi về quá khứ; mặc định là tháng tiếp theo. */
    private static function targetMonth(?string $month, Carbon $today): Carbon
    {
        $default = $today->copy()->startOfMonth()->addMonthNoOverflow();

        if (! is_string($month) || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $default;
        }

        try {
            $target = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        } catch (\Throwable $e) {
            return $default;
        }

        return $target->lt($today->copy()->startOfMonth()) ? $default : $target;
    }

    /** Tháng tiếp theo + MONTH_OPTIONS tháng kế tiếp cho ô chọn kỳ. */
    private static function monthOptions(Carbon $today, Carbon $target): array
    {
        $first = $today->copy()->startOfMonth()->addMonthNoOverflow();

        return collect(range(0, self::MONTH_OPTIONS - 1))
            ->map(function ($offset) use ($first, $target) {
                $month = $first->copy()->addMonthsNoOverflow($offset);

                return [
                    'value' => $month->format('Y-m'),
                    'label' => 'Tháng '.$month->format('m/Y'),
                    'active' => $month->format('Y-m') === $target->format('Y-m'),
                ];
            })
            ->all();
    }

    /**
     * Danh sách chu kỳ ăn vào tồn của phòng: nội bộ của phòng + liên phòng ban gửi tới phòng.
     * Chỉ danh sách còn hiệu lực (status_id = 1) mới còn tự tạo đề nghị.
     */
    private static function lists(int $departmentId)
    {
        $table = MaterialPeriodicRequest::LIST_TABLE;

        return DB::table($table)
            ->leftJoin('deparments as pr_dept', $table.'.department_id', '=', 'pr_dept.id')
            ->leftJoin('consumption_objects as pr_object', $table.'.consumption_object_id', '=', 'pr_object.id')
            ->select(
                $table.'.id',
                $table.'.title',
                $table.'.type',
                $table.'.periodic',
                $table.'.cycle_day',
                $table.'.cycle_day_mode',
                $table.'.cycle_length',
                $table.'.frequency',
                $table.'.start_date',
                $table.'.next_run_date',
                $table.'.status_id',
                $table.'.department_id',
                'pr_dept.name as department_name',
                'pr_dept.shortName as department_short',
                'pr_object.name as object_name'
            )
            ->where($table.'.status_id', 1)
            ->where(function ($query) use ($table, $departmentId) {
                $query->where(function ($sub) use ($table, $departmentId) {
                    $sub->where($table.'.type', MaterialPeriodicRequest::TYPE_INTERNAL)
                        ->where($table.'.department_id', $departmentId);
                })->orWhere(function ($sub) use ($table, $departmentId) {
                    $sub->where($table.'.type', MaterialPeriodicRequest::TYPE_EXTERNAL)
                        ->where($table.'.to_department_id', $departmentId);
                });
            })
            ->orderBy($table.'.title', 'asc')
            ->get();
    }

    /** Dòng vật tư còn hiệu lực của các danh sách, gom theo danh sách. */
    private static function items(array $listIds)
    {
        if (! $listIds) {
            return collect();
        }

        $table = MaterialPeriodicRequest::ITEM_TABLE;

        return DB::table($table)
            ->select($table.'.periodic_request_list_id', $table.'.category_id', $table.'.requested_amount', $table.'.requested_unit')
            ->whereIn($table.'.periodic_request_list_id', $listIds)
            ->where($table.'.active', 1)
            ->whereNotNull($table.'.category_id')
            ->get()
            ->groupBy('periodic_request_list_id');
    }

    /**
     * Nhu cầu theo chu kỳ phát sinh trong khoảng [$from, $to]: mỗi danh sách nhân số lần sẽ
     * tạo đề nghị với số lượng từng dòng.
     *
     * @return array<int, array{amount: float, sources: array}>
     */
    private static function demand($lists, $items, string $from, string $to): array
    {
        $demand = [];

        foreach ($lists as $list) {
            $rows = $items[$list->id] ?? null;

            if (! $rows || $rows->isEmpty()) {
                continue;
            }

            $runs = MaterialPeriodicRequest::runsBetween($list, $from, $to);

            if ($runs < 1) {
                continue;
            }

            foreach ($rows as $item) {
                $categoryId = (int) $item->category_id;
                $amount = (float) $item->requested_amount * $runs;

                if ($amount <= 0) {
                    continue;
                }

                $demand[$categoryId] ??= ['amount' => 0.0, 'sources' => []];
                $demand[$categoryId]['amount'] += $amount;
                $demand[$categoryId]['sources'][] = [
                    'list_id' => (int) $list->id,
                    'title' => $list->title,
                    'type' => MaterialPeriodicRequest::typeOf($list->type),
                    'department' => $list->department_short ?: $list->department_name,
                    'object' => $list->object_name,
                    'cycle' => MaterialPeriodicRequest::scheduleLabel($list->periodic, $list->cycle_length, $list->frequency),
                    'cal' => MaterialPeriodicRequest::dayModeOf($list->cycle_day_mode ?? null) === MaterialPeriodicRequest::DAY_MODE_CAL_DUE,
                    'runs' => $runs,
                    'per_run' => (float) $item->requested_amount,
                    'unit' => $item->requested_unit,
                    'amount' => $amount,
                ];
            }
        }

        return $demand;
    }

    /**
     * Tồn hiện hành của phòng theo từng danh mục - BỎ lô đã hết hạn.
     *
     * @return array<int, float>
     */
    private static function stock(int $departmentId): array
    {
        return MaterialPicking::lots($departmentId)
            ->reject(fn ($lot) => $lot->expired)
            ->groupBy('category_id')
            ->map(fn ($group) => (float) $group->sum('remaining'))
            ->all();
    }

    /**
     * Vật tư ĐÃ ĐỀ NGHỊ NHƯNG KHO CHƯA CẤP PHÁT, gộp hai đường ăn vào tồn của phòng:
     *
     *   - Đề nghị nội bộ còn Lưu tạm / đang chờ ký, hoặc đã duyệt mà kho chưa cấp đủ (phần
     *     còn thiếu của dòng).
     *   - Đề nghị liên phòng ban phòng khác lập gửi tới, phòng mình chưa cấp phát.
     *
     * @return array<int, float>
     */
    private static function reserved(int $departmentId): array
    {
        $internal = DB::table('material_request_items')
            ->join('material_request_lists', 'material_request_lists.id', '=', 'material_request_items.request_list_id')
            ->where('material_request_lists.department_id', $departmentId)
            ->whereIn('material_request_items.status', self::REQ_PENDING_ITEM_STATUSES)
            ->where('material_request_items.active', 1)
            ->whereNotNull('material_request_items.category_id')
            ->where(function ($query) {
                $query->whereIn('material_request_lists.app_status', self::REQ_PENDING_APP_STATUSES)
                    ->orWhere(function ($sub) {
                        $sub->where('material_request_lists.app_status', 'approved')
                            ->whereIn('material_request_lists.issue_status', self::REQ_PENDING_ISSUE_STATUSES);
                    });
            })
            ->groupBy('material_request_items.category_id')
            ->selectRaw('material_request_items.category_id as category_id, SUM(material_request_items.requested_amount - COALESCE(material_request_items.issued_amount, 0)) as reserved')
            ->pluck('reserved', 'category_id');

        $transfer = DB::table('material_transfer_items')
            ->join('material_transfer_requests', 'material_transfer_requests.id', '=', 'material_transfer_items.transfer_request_id')
            ->where('material_transfer_requests.to_department_id', $departmentId)
            ->whereIn('material_transfer_requests.status', self::TRANSFER_PENDING_STATUSES)
            ->where('material_transfer_items.status', 'pending')
            ->where('material_transfer_items.active', 1)
            ->groupBy('material_transfer_items.category_id')
            ->selectRaw('material_transfer_items.category_id as category_id, SUM(material_transfer_items.requested_amount) as reserved')
            ->pluck('reserved', 'category_id');

        $reserved = [];

        foreach ([$internal, $transfer] as $source) {
            foreach ($source as $categoryId => $amount) {
                $reserved[(int) $categoryId] = ($reserved[(int) $categoryId] ?? 0) + (float) $amount;
            }
        }

        return $reserved;
    }

    /**
     * Thông tin hiển thị của các danh mục đang xét, kèm đơn vị và ngưỡng tồn tối thiểu của phòng.
     *
     * @return array<int, object>
     */
    private static function categories(int $departmentId, array $categoryIds): array
    {
        $categoryIds = array_values(array_filter($categoryIds));

        if (! $categoryIds) {
            return [];
        }

        return DB::table('material_categories')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_categories.id'))
            ->tap(fn ($query) => DepartmentMaterial::join($query, $departmentId, 'material_categories.id'))
            ->select(
                'material_categories.id as category_id',
                'material_categories.code as category_code',
                'material_categories.technical_specification',
                'material_categories.purchasing_department',
                'material_categories.lead_time_days',
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                DepartmentMaterial::minStockColumn(),
                'units.short_name as unit_short_name'
            )
            ->whereIn('material_categories.id', $categoryIds)
            ->get()
            ->keyBy('category_id')
            ->all();
    }
}
