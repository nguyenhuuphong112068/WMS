<?php

namespace App\Http\Controllers\Pages\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * TỒN - TỒN KHO VẬT TƯ
 *
 * Tồn được TÍNH RA từ các bảng nghiệp vụ, không lưu bảng tồn riêng:
 *
 *      Tồn của một mã lô = material_imports.amount
 *                        + SUM(material_balancings.balancing_amount)   (status_id = 1)
 *                        - SUM(material_exports.amount where type='export')
 *                        - SUM(material_exports.amount where type='cancel')
 *
 * KỲ BÁO CÁO: xét khoảng "từ ngày - đến ngày" (mặc định trọn tháng hiện tại), tách thành
 * Tồn đầu kỳ / Nhập trong kỳ / Sử dụng - Loại bỏ trong kỳ / Tồn cuối kỳ. Song song với
 * StandardInventoryController nhưng gọn: vật tư không có nhóm chuẩn, không kiểm soát khối
 * lượng, không hạn dùng nội bộ nên chỉ còn hai hành động: xem tồn và Cân Đối.
 */
class MaterialInventoryController extends Controller
{
    private const NEAR_EXPIRY_DAYS = 30;

    private const EXPIRING_SOON_MONTHS = 6;

    private const LOW_STOCK_RATIO = 0.2;

    private const EPSILON = 0.00005;

    private const BALANCING_MAX_RATIO = 0.05;

    public const STATES = [
        'in' => 'Còn hàng',
        'low' => 'Sắp hết',
        'near' => 'Sắp hết hạn',
        'expired' => 'Hết hạn',
        'out' => 'Hết hàng',
        'over' => 'Âm kho',
    ];

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();
        $period = $this->period($request);

        $datas = $this->stockByCode($departmentId, $period['from'], $period['to']);

        session()->put(['title' => 'TỒN - TỒN KHO VẬT TƯ']);

        return view('pages.inventory.MaterialInventory.list', [
            'datas' => $datas,
            'summaries' => $this->stockByMaterial($datas),
            'balancings' => $this->balancingHistory($departmentId),
            'zones' => $this->zoneOptions($departmentId),
            'states' => self::STATES,
            'period' => $period,
            'nearExpiryDays' => self::NEAR_EXPIRY_DAYS,
            'expiringSoonMonths' => self::EXPIRING_SOON_MONTHS,
            'lowStockPercent' => (int) round(self::LOW_STOCK_RATIO * 100),
            'balancingMaxPercent' => (int) round(self::BALANCING_MAX_RATIO * 100),
        ]);
    }

    private function period(Request $request): array
    {
        $parse = function ($value) {
            if (! is_string($value) || trim($value) === '') {
                return null;
            }
            try {
                return \Carbon\Carbon::parse($value)->startOfDay();
            } catch (\Throwable $e) {
                return null;
            }
        };

        $today = now()->startOfDay();
        $from = $parse($request->query('from_date')) ?: $today->copy()->startOfMonth();
        $to = $parse($request->query('to_date')) ?: $today->copy()->endOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'days' => (int) $from->diffInDays($to) + 1,
            'is_current' => $to->gte($today),
        ];
    }

    /**
     * CÂN ĐỐI SỐ LƯỢNG NHẬP - ghi thêm một dòng material_balancings.
     *
     * balancing_amount là SỐ ĐIỀU CHỈNH (+/-), không phải số lượng nhập mới. Chặn luỹ kế:
     * tổng mọi lần cân đối không vượt 5% lượng nhập ban đầu, và cân đối xong tồn không âm.
     */
    public function balancing(Request $request)
    {
        $departmentId = $this->departmentId();

        $import = DB::table('material_imports')
            ->where('id', $request->import_id)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->first();

        if (! $import) {
            return redirect()->back()->with('error', 'Không tìm thấy mã lô vật tư cần cân đối!');
        }

        $validator = Validator::make($request->all(), [
            'import_id' => ['required', 'exists:material_imports,id'],
            'balancing_amount' => ['required', 'numeric', 'not_in:0'],
            'balancing_at' => ['required', 'date'],
        ], [
            'import_id.required' => 'Vui lòng chọn mã lô cần cân đối.',
            'import_id.exists' => 'Mã lô cần cân đối không tồn tại.',
            'balancing_amount.required' => 'Vui lòng nhập số lượng cân đối.',
            'balancing_amount.numeric' => 'Số lượng cân đối phải là số.',
            'balancing_amount.not_in' => 'Số lượng cân đối phải khác 0.',
            'balancing_at.required' => 'Vui lòng chọn thời điểm cân đối.',
            'balancing_at.date' => 'Thời điểm cân đối không hợp lệ.',
        ]);

        $gap = $this->gapOf($import);
        $balanced = $this->balancedOf($import);
        $limit = abs((float) $import->amount) * self::BALANCING_MAX_RATIO;

        $validator->after(function ($validator) use ($request, $gap, $balanced, $limit) {
            if (! is_numeric($request->balancing_amount)) {
                return;
            }

            $amount = (float) $request->balancing_amount;

            if (abs($amount) < self::EPSILON) {
                $validator->errors()->add('balancing_amount', 'Số lượng cân đối phải khác 0.');

                return;
            }

            if (abs($balanced + $amount) > $limit + self::EPSILON) {
                $validator->errors()->add(
                    'balancing_amount',
                    'Chỉ được cân đối tối đa '.(int) round(self::BALANCING_MAX_RATIO * 100).'% số lượng nhập, tức ±'
                    .$this->number($limit).'. Mã lô này đã cân đối '.$this->number($balanced)
                    .', lần này chỉ được nhập trong khoảng từ '.$this->number(-$limit - $balanced)
                    .' đến '.$this->number($limit - $balanced).'.'
                );

                return;
            }

            if ($gap + $amount < -self::EPSILON) {
                $validator->errors()->add(
                    'balancing_amount',
                    'Tồn hiện tại là '.$this->number($gap).', cân đối xong tồn không được âm. Vui lòng nhập từ '
                    .$this->number(-$gap).' trở lên.'
                );
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'balancingErrors')->withInput();
        }

        $amount = (float) $request->balancing_amount;

        $id = DB::table('material_balancings')->insertGetId([
            'code' => $import->code,
            'import_id' => (int) $import->id,
            'department_id' => $departmentId,
            'balancing_amount' => $amount,
            'balancing_by' => $this->actor(),
            'balancing_at' => \Carbon\Carbon::parse($request->balancing_at)->format('Y-m-d H:i:s'),
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Cân đối',
            'material_balancings',
            $id,
            'Tồn: '.$this->number($gap),
            'Cân đối '.($amount > 0 ? '+' : '').$this->number($amount).' -> tồn: '.$this->number($gap + $amount)
        );

        return redirect()->back()->with(
            'success',
            'Đã cân đối mã lô '.$import->code.' ('.($amount > 0 ? '+' : '').$this->number($amount)
            .'), tồn còn lại '.$this->number($gap + $amount).'.'
        );
    }

    /* ==========================================================
     |  TÍNH TỒN
     ========================================================== */

    private function stockByCode(int $departmentId, string $from, string $to)
    {
        $used = $this->usedByImport($departmentId, $from, $to);
        $balanced = $this->balancedByImport($departmentId, $from, $to);
        $today = now()->startOfDay();

        $query = DB::table('material_imports')
            ->leftJoin('material_categories', 'material_imports.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_imports.category_id'))
            ->tap(fn ($query) => DepartmentMaterial::join($query, $departmentId, 'material_imports.category_id'))
            ->leftJoin('material_classifications', DepartmentMaterial::TABLE.'.classification_id', '=', 'material_classifications.id')
            ->leftJoin('locations', 'material_imports.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id');

        return $query
            ->select(
                'material_imports.id',
                'material_imports.code',
                'material_imports.category_id',
                'material_imports.amount',
                'material_imports.imported_date',
                'material_imports.expired_date',
                'material_categories.technical_specification',
                DepartmentMaterial::minStockColumn(),
                'material_classifications.name as classification_name',
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'material_imports.location_id',
                'locations.code as location_code',
                'locations.name as location_name',
                'locations.warehouse_id',
                'locations.room_id',
                'locations.shelf_id',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where('material_imports.department_id', $departmentId)
            ->where('material_imports.status_id', 1)
            ->whereDate('material_imports.imported_date', '<=', $to)
            ->orderBy('material_imports.code', 'asc')
            ->get()
            ->map(function ($row) use ($used, $balanced, $from, $to, $today) {
                $out = $used[$row->id] ?? null;
                $bal = $balanced[$row->id] ?? null;
                $importedDate = substr((string) $row->imported_date, 0, 10);

                $row->imported = (float) $row->amount;
                $row->balanced = (float) ($bal->balanced_to ?? 0);
                $row->used = (float) ($out->used_to ?? 0);
                $row->cancelled = (float) ($out->cancelled_to ?? 0);

                $row->last_balancing_at = $bal->last_balancing_at ?? null;
                $row->balancing_times = (int) ($bal->times ?? 0);
                $row->last_exported_date = $out->last_exported_date ?? null;
                $row->export_times = (int) ($out->times ?? 0);
                $row->period_export_times = (int) ($out->times_in ?? 0);

                $row->opening = ($importedDate < $from ? $row->imported : 0)
                    + (float) ($bal->balanced_before ?? 0)
                    - (float) ($out->used_before ?? 0)
                    - (float) ($out->cancelled_before ?? 0);

                $row->period_imported = $importedDate >= $from && $importedDate <= $to ? $row->imported : 0;
                $row->period_balanced = (float) ($bal->balanced_in ?? 0);
                $row->period_in = $row->period_imported + $row->period_balanced;
                $row->period_used = (float) ($out->used_in ?? 0);
                $row->period_cancelled = (float) ($out->cancelled_in ?? 0);
                $row->is_new_in_period = $row->period_imported > 0;

                $row->effective = $row->imported + $row->balanced;

                $row->balanced_all = (float) ($bal->balanced_all ?? 0);
                $row->balancing_limit = abs($row->imported) * self::BALANCING_MAX_RATIO;
                $row->balancing_min_input = -$row->balancing_limit - $row->balanced_all;
                $row->balancing_max_input = $row->balancing_limit - $row->balanced_all;

                $row->closing = $row->opening + $row->period_in - $row->period_used - $row->period_cancelled;
                $row->gap = $row->closing;
                $row->remaining = max($row->gap, 0);
                $row->used_percent = $row->effective > 0
                    ? (int) min(round(($row->used + $row->cancelled) / $row->effective * 100), 100)
                    : 0;

                $row->days_to_expiry = $row->expired_date
                    ? (int) $today->diffInDays(\Carbon\Carbon::parse($row->expired_date)->startOfDay(), false)
                    : null;

                $row->min_stock = $row->min_stock !== null ? (float) $row->min_stock : null;

                $row->effective_expired_date = $row->expired_date;
                $row->days_to_effective_expiry = $row->days_to_expiry;
                $row->is_expiring_soon = $row->expired_date
                    && $row->remaining > self::EPSILON
                    && \Carbon\Carbon::parse($row->expired_date)->startOfDay()
                        ->lt($today->copy()->addMonthsNoOverflow(self::EXPIRING_SOON_MONTHS));

                $row->state = $this->stateOf($row);
                $row->state_label = self::STATES[$row->state];

                return $row;
            })
            ->pipe(fn ($rows) => $this->withGroupTotals($rows));
    }

    /** Tổng tồn của các lô CÙNG vật tư (danh mục), cộng trong PHP để luôn khớp từng dòng. */
    private function withGroupTotals($rows)
    {
        $byCategory = $rows->groupBy('category_id')
            ->map(fn ($group) => [
                'remaining' => (float) $group->sum('remaining'),
                'codes' => $group->count(),
            ]);

        return $rows->map(function ($row) use ($byCategory) {
            $category = $byCategory[$row->category_id];
            $row->category_remaining = $category['remaining'];
            $row->category_codes = $category['codes'];

            return $row;
        });
    }

    private function usedByImport(int $departmentId, string $from, string $to)
    {
        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';

        return DB::table('material_exports')
            ->select('import_id')
            ->selectRaw("SUM(CASE WHEN type = 'export' AND created_at < ? THEN amount ELSE 0 END) as used_before", [$start])
            ->selectRaw("SUM(CASE WHEN type = 'cancel' AND created_at < ? THEN amount ELSE 0 END) as cancelled_before", [$start])
            ->selectRaw("SUM(CASE WHEN type = 'export' AND created_at BETWEEN ? AND ? THEN amount ELSE 0 END) as used_in", [$start, $end])
            ->selectRaw("SUM(CASE WHEN type = 'cancel' AND created_at BETWEEN ? AND ? THEN amount ELSE 0 END) as cancelled_in", [$start, $end])
            ->selectRaw("SUM(CASE WHEN type = 'export' AND created_at <= ? THEN amount ELSE 0 END) as used_to", [$end])
            ->selectRaw("SUM(CASE WHEN type = 'cancel' AND created_at <= ? THEN amount ELSE 0 END) as cancelled_to", [$end])
            ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as times_in', [$start, $end])
            ->selectRaw('SUM(CASE WHEN created_at <= ? THEN 1 ELSE 0 END) as times', [$end])
            ->selectRaw('MAX(CASE WHEN created_at <= ? THEN created_at END) as last_exported_date', [$end])
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->get()
            ->keyBy('import_id');
    }

    private function balancedByImport(int $departmentId, string $from, string $to)
    {
        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';

        return DB::table('material_balancings')
            ->select('import_id')
            ->selectRaw('SUM(CASE WHEN balancing_at < ? THEN balancing_amount ELSE 0 END) as balanced_before', [$start])
            ->selectRaw('SUM(CASE WHEN balancing_at BETWEEN ? AND ? THEN balancing_amount ELSE 0 END) as balanced_in', [$start, $end])
            ->selectRaw('SUM(CASE WHEN balancing_at <= ? THEN balancing_amount ELSE 0 END) as balanced_to', [$end])
            ->selectRaw('SUM(balancing_amount) as balanced_all')
            ->selectRaw('SUM(CASE WHEN balancing_at <= ? THEN 1 ELSE 0 END) as times', [$end])
            ->selectRaw('MAX(balancing_at) as last_balancing_at')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->get()
            ->keyBy('import_id');
    }

    private function balancingHistory(int $departmentId)
    {
        return DB::table('material_balancings')
            ->select('import_id', 'balancing_amount', 'balancing_by', 'balancing_at')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('balancing_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('import_id');
    }

    private function balancedOf($import): float
    {
        return (float) DB::table('material_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('balancing_amount');
    }

    private function gapOf($import): float
    {
        $out = (float) DB::table('material_exports')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('amount');

        return (float) $import->amount + $this->balancedOf($import) - $out;
    }

    private function zoneOptions(int $departmentId): array
    {
        $of = fn (string $table, array $columns) => DB::table($table)
            ->select($columns)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->get();

        return [
            'warehouses' => $of('warehouses', ['id', 'code', 'name']),
            'rooms' => $of('rooms', ['id', 'code', 'name', 'warehouse_id']),
            'shelves' => $of('shelves', ['id', 'code', 'name', 'warehouse_id', 'room_id']),
            'locations' => $of('locations', ['id', 'code', 'name', 'warehouse_id', 'room_id', 'shelf_id']),
        ];
    }

    private function stockByMaterial($datas)
    {
        return $datas
            ->groupBy('category_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $inStock = $rows->filter(fn ($row) => $row->remaining > self::EPSILON);

                return (object) [
                    'material_name' => $first->material_name,
                    'manufacturer_short_name' => $first->manufacturer_short_name,
                    'technical_specification' => $first->technical_specification,
                    'classification_name' => $first->classification_name,
                    'unit' => $first->unit_short_name ?: $first->unit_name,
                    'imported' => (float) $rows->sum('imported'),
                    'balanced' => (float) $rows->sum('balanced'),
                    'used' => (float) $rows->sum('used'),
                    'cancelled' => (float) $rows->sum('cancelled'),
                    'remaining' => (float) $rows->sum('remaining'),
                    'opening' => (float) $rows->sum('opening'),
                    'period_in' => (float) $rows->sum('period_in'),
                    'period_used' => (float) $rows->sum('period_used'),
                    'period_cancelled' => (float) $rows->sum('period_cancelled'),
                    'closing' => (float) $rows->sum('closing'),
                    'code_count' => $rows->count(),
                    'in_stock_count' => $inStock->count(),
                    'nearest_expiry' => $inStock->whereNotNull('expired_date')->min('expired_date'),
                    'alert_count' => $rows->whereIn('state', ['low', 'near', 'expired', 'over'])->count(),
                ];
            })
            ->sortBy('material_name')
            ->values();
    }

    private function stateOf($row): string
    {
        if ($row->gap < -self::EPSILON) {
            return 'over';
        }

        if ($row->remaining <= self::EPSILON) {
            return 'out';
        }

        if ($row->days_to_expiry !== null) {
            if ($row->days_to_expiry < 0) {
                return 'expired';
            }
            if ($row->days_to_expiry <= self::NEAR_EXPIRY_DAYS) {
                return 'near';
            }
        }

        if ($row->min_stock !== null) {
            return $row->remaining <= $row->min_stock ? 'low' : 'in';
        }

        if ($row->effective > 0 && $row->remaining <= $row->effective * self::LOW_STOCK_RATIO) {
            return 'low';
        }

        return 'in';
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return session('user')['fullName'] ?? 'NA';
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }
}
