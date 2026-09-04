<?php

namespace App\Http\Controllers\Pages\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentMaterial;
use App\Support\InventoryChart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * TỒN - TỒN KHO VẬT TƯ
 *
 * Tồn được TÍNH RA từ các bảng nghiệp vụ, không lưu bảng tồn riêng:
 *
 *      Tồn của một mã xuất nhập = material_imports.amount
 *                        + SUM(material_balancings.balancing_amount)   (status_id = 1)
 *                        - SUM(material_exports.amount where type='export')
 *                        - SUM(material_exports.amount where type='cancel')
 *
 * LỌC THEO KỲ: chỉ hiện mã xuất nhập CÓ PHÁT SINH hoặc CÒN TỒN trong kỳ - còn tồn cuối
 * kỳ, hoặc có sử dụng, hoặc có loại bỏ (xem movedInPeriod). Mã đã hết sạch từ kỳ trước,
 * trong kỳ không động tới thì không hiện.
 *
 * KỲ BÁO CÁO: xét khoảng "từ ngày - đến ngày" (mặc định trọn tháng hiện tại), tách thành
 * Tồn đầu kỳ / Nhập trong kỳ / Sử dụng - Loại bỏ trong kỳ / Tồn cuối kỳ. Song song với
 * StandardInventoryController nhưng gọn: vật tư không có nhóm chuẩn, không kiểm soát khối
 * lượng, không hạn dùng nội bộ nên chỉ còn hai hành động: xem tồn và Cân Đối.
 *
 * Riêng tab KIỂM KÊ ĐỊNH KỲ (chu kỳ 1 tháng 1 lần) do MaterialStocktakeController lo,
 * index() chỉ lấy dữ liệu qua MaterialStocktakeController::panel().
 */
class MaterialInventoryController extends Controller
{
    private const NEAR_EXPIRY_DAYS = 30;

    private const EXPIRING_SOON_MONTHS = 6;

    private const LOW_STOCK_RATIO = 0.2;

    private const EPSILON = 0.00005;

    private const BALANCING_MAX_RATIO = 0.05;

    /** Loại lưu trữ của định khu mà màn hình này quan tâm - xem locations.item_type. */
    private const LOCATION_TYPE = 'material';

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
        $zones = $this->zoneOptions($departmentId);

        session()->put(['title' => 'TỒN - TỒN KHO VẬT TƯ']);

        return view('pages.inventory.MaterialInventory.list', [
            'datas' => $datas,
            'summaries' => $this->stockByMaterial($datas),
            'balancings' => $this->balancingHistory($departmentId),
            'zones' => $zones,
            'zoneMap' => $this->zoneMap($datas, $zones),
            // Tab KIỂM KÊ ĐỊNH KỲ - chu kỳ 1 tháng 1 lần, xem MaterialStocktakeController
            'stocktake' => MaterialStocktakeController::panel($departmentId),
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
            return redirect()->back()->with('error', 'Không tìm thấy mã xuất nhập vật tư cần cân đối!');
        }

        $validator = Validator::make($request->all(), [
            'import_id' => ['required', 'exists:material_imports,id'],
            'balancing_amount' => ['required', 'numeric', 'not_in:0'],
            'balancing_at' => ['required', 'date'],
        ], [
            'import_id.required' => 'Vui lòng chọn mã xuất nhập cần cân đối.',
            'import_id.exists' => 'Mã xuất nhập cần cân đối không tồn tại.',
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
                    .$this->number($limit).'. Mã xuất nhập này đã cân đối '.$this->number($balanced)
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
            'Đã cân đối mã xuất nhập '.$import->code.' ('.($amount > 0 ? '+' : '').$this->number($amount)
            .'), tồn còn lại '.$this->number($gap + $amount).'.'
        );
    }

    /**
     * BIỂU ĐỒ NHẬP - XUẤT - TỒN CỦA MỘT VẬT TƯ (trả JSON cho modal).
     *
     * Mở bằng nút biểu đồ trên bảng "Tồn Kho Theo Tên". Kỳ báo cáo đọc lại đúng $period
     * của màn hình (from_date / to_date trên thanh Kỳ) nên số liệu trong biểu đồ luôn
     * khớp với con số đang hiện trên bảng.
     *
     * Cách chia mốc và cộng dồn tồn nằm ở App\Support\InventoryChart, dùng chung với
     * màn hình Tồn Kho Hoá Chất. Ở đây chỉ lấy đúng 3 nguồn phát sinh của vật tư.
     */
    public function chart(Request $request)
    {
        $departmentId = $this->departmentId();
        $period = $this->period($request);

        $query = DB::table('material_categories')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_categories.id'))
            ->tap(fn ($query) => DepartmentMaterial::join($query, $departmentId, 'material_categories.id'));

        // Đơn vị tính và ngưỡng tồn tối thiểu lấy theo khai báo của phòng ban đang chọn
        $category = $query
            ->select(
                'material_categories.id',
                'material_categories.code',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                DepartmentMaterial::minStockColumn(),
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where('material_categories.id', (int) $request->query('category_id'))
            ->first();

        if (! $category) {
            return response()->json(['message' => 'Không tìm thấy vật tư cần xem biểu đồ.'], 404);
        }

        $series = InventoryChart::series(
            $period,
            $this->chartImports($departmentId, $category->id, $period['to']),
            $this->chartExports($departmentId, $category->id, $period['to']),
            $this->chartBalancings($departmentId, $category->id, $period['to'])
        );

        return response()->json($series + [
            'category_code' => $category->code,
            'material_name' => $category->material_name,
            'technical_specification' => $category->technical_specification,
            'manufacturer_short_name' => $category->manufacturer_short_name,
            'unit' => $category->unit_short_name ?: $category->unit_name,
            'period' => $period + [
                'label' => \Carbon\Carbon::parse($period['from'])->format('d/m/Y')
                    .' - '.\Carbon\Carbon::parse($period['to'])->format('d/m/Y'),
            ],
            // Ngưỡng tồn tối thiểu của phòng, vẽ thành đường kẻ đứt để thấy lúc nào chạm đáy
            'min_stock' => $category->min_stock === null ? null : (float) $category->min_stock,
        ]);
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
            ->filter(fn ($row) => $this->movedInPeriod($row))
            ->values()
            ->pipe(fn ($rows) => $this->withGroupTotals($rows));
    }

    /**
     * LỌC THEO KỲ - một mã xuất nhập chỉ hiện trên màn hình tồn khi trong kỳ đang xem
     * có ít nhất một trong ba dấu hiệu:
     *
     *      - còn tồn cuối kỳ  (closing khác 0)
     *      - có sử dụng       (period_used > 0)
     *      - có loại bỏ       (period_cancelled > 0)
     *
     * Mã đã dùng hết từ những kỳ trước, trong kỳ này không nhập - không xuất - không loại bỏ
     * (mọi cột đều bằng 0) thì không hiện ra nữa. Mã nhập mới trong kỳ luôn thoả điều kiện
     * "còn tồn cuối kỳ" hoặc "có sử dụng" nên vẫn hiện bình thường. Riêng mã ÂM KHO
     * (closing < 0) vẫn giữ lại để sai lệch số liệu không bị giấu đi.
     */
    private function movedInPeriod($row): bool
    {
        return abs($row->closing) > self::EPSILON
            || $row->period_used > self::EPSILON
            || $row->period_cancelled > self::EPSILON;
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
            'locations' => $this->locationOptions($departmentId),
        ];
    }

    /**
     * Chỉ lấy các ô đã khai loại lưu trữ là VẬT TƯ. Ô chưa khai loại là "Dùng chung"
     * nên vẫn lấy - định khu cũ chưa phân loại không bị biến mất khỏi sơ đồ.
     */
    private function locationOptions(int $departmentId)
    {
        return DB::table('locations')
            ->select(['id', 'code', 'warehouse_id', 'room_id', 'shelf_id', 'item_type'])
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->where(fn ($query) => $query->whereNull('item_type')->orWhere('item_type', self::LOCATION_TYPE))
            ->orderBy('code', 'asc')
            ->get();
    }

    private function stockByMaterial($datas)
    {
        return $datas
            ->groupBy('category_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $inStock = $rows->filter(fn ($row) => $row->remaining > self::EPSILON);

                return (object) [
                    // Khoá của dòng cộng dồn, nút Biểu Đồ gửi lên chart() theo id này
                    'category_id' => (int) $first->category_id,
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

    /* ==========================================================
     |  SƠ ĐỒ TỒN THEO VỊ TRÍ
     ========================================================== */

    /**
     * Dựng sơ đồ Kho -> Phòng -> Kệ/Tủ -> Vị trí để vẽ dạng thẻ (card - grid).
     *
     * Khung lấy từ DANH MỤC ĐỊNH KHU nên ô đang trống vẫn hiện ra - người dùng nhìn được
     * chỗ nào còn xếp được hàng. Định khu đã ngừng hoạt động mà vẫn còn tồn thì lấy tên
     * ngay trên dòng tồn để không rơi khỏi sơ đồ. Mã chưa xếp vị trí gom thành khối riêng.
     *
     * Chỉ đếm các mã CÒN TỒN (remaining > 0): sơ đồ trả lời "chỗ này đang có gì", mã đã
     * dùng hết không còn nằm ở đó nữa.
     */
    private function zoneMap($datas, array $zones): array
    {
        $warehouses = $zones['warehouses']->keyBy('id');
        $rooms = $zones['rooms']->keyBy('id');
        $shelves = $zones['shelves']->keyBy('id');
        $locations = $zones['locations']->keyBy('id');

        $rowsByLocation = $datas->filter(fn ($row) => $row->location_id)->groupBy('location_id');

        // Định khu đã ngừng hoạt động nhưng còn hàng đứng ở đó -> vá lại từ chính dòng tồn
        foreach ($rowsByLocation as $locationId => $rows) {
            $first = $rows->first();

            if (! $locations->has($locationId)) {
                $locations[$locationId] = (object) [
                    'id' => (int) $locationId,
                    'code' => $first->location_code,
                    'warehouse_id' => $first->warehouse_id,
                    'room_id' => $first->room_id,
                    'shelf_id' => $first->shelf_id,
                ];
            }
            if ($first->warehouse_id && ! $warehouses->has($first->warehouse_id)) {
                $warehouses[$first->warehouse_id] = (object) ['id' => $first->warehouse_id, 'code' => null, 'name' => $first->warehouse_name];
            }
            if ($first->room_id && ! $rooms->has($first->room_id)) {
                $rooms[$first->room_id] = (object) ['id' => $first->room_id, 'code' => null, 'name' => $first->room_name];
            }
            if ($first->shelf_id && ! $shelves->has($first->shelf_id)) {
                $shelves[$first->shelf_id] = (object) ['id' => $first->shelf_id, 'code' => null, 'name' => $first->shelf_name];
            }
        }

        // Gom vị trí vào đúng nhánh Kho -> Phòng -> Kệ/Tủ (khoá 0 = chưa gán cấp đó)
        $tree = [];
        $index = [];

        foreach ($locations->sortBy('code') as $loc) {
            $wKey = (int) ($loc->warehouse_id ?: 0);
            $rKey = (int) ($loc->room_id ?: 0);
            $sKey = (int) ($loc->shelf_id ?: 0);

            $w = $warehouses->get($wKey);
            $r = $rooms->get($rKey);
            $s = $shelves->get($sKey);

            $tree[$wKey] ??= [
                'id' => $wKey ?: null,
                'code' => $w->code ?? null,
                'name' => $w->name ?? 'Chưa gán kho',
                'rooms' => [],
            ];
            $tree[$wKey]['rooms'][$rKey] ??= [
                'id' => $rKey ?: null,
                'code' => $r->code ?? null,
                'name' => $r->name ?? 'Chưa gán phòng',
                'shelves' => [],
            ];
            $tree[$wKey]['rooms'][$rKey]['shelves'][$sKey] ??= [
                'id' => $sKey ?: null,
                'code' => $s->code ?? null,
                'name' => $s->name ?? 'Chưa gán kệ/tủ',
                'locations' => [],
            ];

            $node = $this->zoneNode($loc, $rowsByLocation->get($loc->id, collect()));
            $node['path'] = $tree[$wKey]['name'].' / '.$tree[$wKey]['rooms'][$rKey]['name']
                .' / '.$tree[$wKey]['rooms'][$rKey]['shelves'][$sKey]['name'];

            $index[$node['key']] = [
                'code' => $node['code'],
                'path' => $node['path'],
                'lots' => $node['stat']['lots'],
                'materials' => $node['stat']['materials'],
                'alerts' => $node['stat']['alerts'],
                'items' => $node['items'],
            ];

            $tree[$wKey]['rooms'][$rKey]['shelves'][$sKey]['locations'][] = $node;
        }

        // Cộng dồn ngược từ vị trí lên kệ/tủ -> phòng -> kho
        $blank = ['locations' => 0, 'filled' => 0, 'lots' => 0, 'alerts' => 0, 'categories' => []];
        $add = function (array $acc, array $stat): array {
            $acc['locations'] += $stat['locations'];
            $acc['filled'] += $stat['filled'];
            $acc['lots'] += $stat['lots'];
            $acc['alerts'] += $stat['alerts'];
            $acc['categories'] = array_merge($acc['categories'], $stat['categories']);

            return $acc;
        };
        $close = function (array $acc): array {
            $acc['materials'] = count(array_unique($acc['categories']));
            unset($acc['categories']);

            return $acc;
        };

        $tops = $blank + ['rooms' => 0, 'shelves' => 0];
        $outWarehouses = [];

        foreach (collect($tree)->sortBy('name') as $w) {
            $wAcc = $blank;
            $wRooms = [];

            foreach (collect($w['rooms'])->sortBy('name') as $r) {
                $rAcc = $blank;
                $rShelves = [];

                foreach (collect($r['shelves'])->sortBy('name') as $s) {
                    $sAcc = $blank;

                    foreach ($s['locations'] as $loc) {
                        $sAcc = $add($sAcc, $loc['stat']);
                    }

                    $rAcc = $add($rAcc, $sAcc);
                    $s['stat'] = $close($sAcc);
                    $s['locations'] = array_map(function ($loc) {
                        unset($loc['stat']['categories']);

                        return $loc;
                    }, $s['locations']);
                    $rShelves[] = $s;
                }

                $wAcc = $add($wAcc, $rAcc);
                $r['shelves'] = $rShelves;
                $r['stat'] = $close($rAcc) + ['shelves' => count($rShelves)];
                $tops['shelves'] += count($rShelves);
                $wRooms[] = $r;
            }

            $tops = $add($tops, $wAcc);
            $tops['rooms'] += count($wRooms);
            $w['rooms'] = $wRooms;
            $w['stat'] = $close($wAcc) + ['rooms' => count($wRooms)];
            $outWarehouses[] = $w;
        }

        // Mã còn tồn nhưng chưa xếp vị trí - phải nhìn thấy để còn bổ sung định khu
        $unzoned = $datas
            ->filter(fn ($row) => ! $row->location_id && $row->remaining > self::EPSILON)
            ->sortByDesc('remaining')
            ->values();

        $totals = $close($tops) + [
            'warehouses' => count($outWarehouses),
            'unzoned' => $unzoned->count(),
        ];

        return [
            'warehouses' => $outWarehouses,
            'unzoned' => $this->zoneItems($unzoned),
            'totals' => $totals,
            'index' => $index,
        ];
    }

    /** Một ô vị trí trên sơ đồ: đang chứa mã nào, cảnh báo gì, trạng thái để tô màu. */
    private function zoneNode($loc, $rows): array
    {
        $inStock = $rows->filter(fn ($row) => $row->remaining > self::EPSILON)->sortByDesc('remaining')->values();
        $categories = $inStock->pluck('category_id')->unique()->values()->all();
        $alerts = $inStock->whereIn('state', ['low', 'near', 'expired', 'over'])->count();

        if ($inStock->whereIn('state', ['expired', 'over'])->count() > 0) {
            $state = 'danger';
        } elseif ($alerts > 0) {
            $state = 'warn';
        } elseif ($inStock->count() > 0) {
            $state = 'ok';
        } else {
            $state = 'empty';
        }

        return [
            'key' => 'L'.$loc->id,
            'id' => (int) $loc->id,
            'code' => $loc->code,
            'path' => '',
            'state' => $state,
            'stat' => [
                'locations' => 1,
                'filled' => $inStock->count() > 0 ? 1 : 0,
                'lots' => $inStock->count(),
                'alerts' => $alerts,
                'materials' => count($categories),
                'categories' => $categories,
            ],
            'preview' => $this->zonePreview($inStock),
            'items' => $this->zoneItems($inStock),
        ];
    }

    /**
     * Vài vật tư tiêu biểu đang nằm ở một ô vị trí, kèm SỐ LƯỢNG TỒN để nhìn thẻ là biết
     * chỗ đó đang giữ bao nhiêu, khỏi phải mở modal xem chi tiết.
     *
     * Một vật tư có thể nằm ở nhiều mã xuất nhập trong cùng một ô nên phải cộng dồn theo
     * vật tư; vật tư chưa gắn danh mục thì gom theo tên để không dồn nhầm vào một cục.
     */
    private function zonePreview($inStock): array
    {
        return $inStock
            ->groupBy(fn ($row) => $row->category_id ? 'c'.$row->category_id : 'n'.$row->material_name)
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'name' => $first->material_name ?: '—',
                    'remaining' => (float) $rows->sum('remaining'),
                    'unit' => $first->unit_short_name ?: $first->unit_name,
                ];
            })
            ->sortByDesc('remaining')
            ->take(3)
            ->map(fn ($item) => [
                'name' => $item['name'],
                'amount' => $this->number($item['remaining']),
                'unit' => $item['unit'],
            ])
            ->values()
            ->all();
    }

    /** Danh sách mã xuất nhập của một ô vị trí, đã format sẵn để đổ thẳng ra modal. */
    private function zoneItems($rows): array
    {
        return $rows->map(fn ($row) => [
            'code' => $row->code,
            'material_name' => $row->material_name ?: '—',
            'sub' => trim(implode(' · ', array_filter([
                $row->manufacturer_short_name,
                $row->technical_specification,
                $row->classification_name,
            ]))),
            'remaining' => $this->number($row->remaining),
            'unit' => $row->unit_short_name ?: $row->unit_name,
            'expired_date' => $row->expired_date ? \Carbon\Carbon::parse($row->expired_date)->format('d/m/Y') : '—',
            'state' => $row->state,
            'state_label' => $row->state_label,
        ])->values()->all();
    }

    /** Các lần NHẬP của một vật tư, tính đến hết ngày cuối kỳ (kể cả trước kỳ, để tính tồn đầu kỳ). */
    private function chartImports(int $departmentId, int $categoryId, string $to)
    {
        return DB::table('material_imports')
            ->select('imported_date as at', 'amount')
            ->where('department_id', $departmentId)
            ->where('category_id', $categoryId)
            ->where('status_id', 1)
            ->whereDate('imported_date', '<=', $to)
            ->get();
    }

    /**
     * Các lần SỬ DỤNG / LOẠI BỎ của một vật tư, tính đến hết ngày cuối kỳ.
     *
     * material_exports KHÔNG có cột ngày xuất riêng, mốc thời gian là created_at - đúng
     * cột mà usedByImport() đang dùng để cắt kỳ, nên hai nơi không lệch nhau.
     *
     * Phiếu xuất chỉ trỏ tới mã xuất nhập nên phải join ngược về material_imports mới
     * biết nó thuộc vật tư nào; phiếu nhập đã khoá thì phần đã xuất của nó cũng không
     * tính, đúng như cách stockByCode() bỏ qua các phiếu nhập status_id = 0.
     */
    private function chartExports(int $departmentId, int $categoryId, string $to)
    {
        return DB::table('material_exports')
            ->join('material_imports', 'material_exports.import_id', '=', 'material_imports.id')
            ->select(
                'material_exports.created_at as at',
                'material_exports.amount',
                'material_exports.type'
            )
            ->where('material_exports.department_id', $departmentId)
            ->where('material_exports.status_id', 1)
            ->where('material_imports.category_id', $categoryId)
            ->where('material_imports.status_id', 1)
            ->where('material_exports.created_at', '<=', $to.' 23:59:59')
            ->get();
    }

    /** Các lần CÂN ĐỐI của một vật tư, tính đến hết ngày cuối kỳ. */
    private function chartBalancings(int $departmentId, int $categoryId, string $to)
    {
        return DB::table('material_balancings')
            ->join('material_imports', 'material_balancings.import_id', '=', 'material_imports.id')
            ->select(
                'material_balancings.balancing_at as at',
                'material_balancings.balancing_amount as amount'
            )
            ->where('material_balancings.department_id', $departmentId)
            ->where('material_balancings.status_id', 1)
            ->where('material_imports.category_id', $categoryId)
            ->where('material_imports.status_id', 1)
            ->where('material_balancings.balancing_at', '<=', $to.' 23:59:59')
            ->get();
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }
}
