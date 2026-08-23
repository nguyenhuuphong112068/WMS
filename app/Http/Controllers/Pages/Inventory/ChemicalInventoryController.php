<?php

namespace App\Http\Controllers\Pages\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentChemical;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * TỒN - TỒN KHO HOÁ CHẤT
 *
 * Tồn kho được TÍNH RA từ các bảng nghiệp vụ đã có, không lưu thành một bảng tồn
 * riêng (tránh số liệu lệch nhau):
 *
 *      Tồn của một mã xuất nhập = imports.amount
 *                           + SUM(inventory_balancings.balancing_amount)
 *                           - SUM(exports.amount)
 *
 * Quy ước tính:
 * - Chỉ tính phiếu nhập còn hiệu lực (imports.status_id = 1). Phiếu nhập đã khoá
 *   coi như không nằm trong kho.
 * - Chỉ trừ phiếu sử dụng còn hiệu lực (exports.status_id = 1), đúng như cách
 *   ChemicalExportController kiểm tra tồn khi ghi phiếu.
 * - Cả 'export' (sử dụng) và 'cancel' (huỷ bỏ) đều trừ tồn, nhưng tách thành hai
 *   cột để thấy phần hao hụt do huỷ.
 * - Số lượng theo đơn vị gốc của danh mục hoá chất (chemical_categories.unit_id).
 *
 * Màn hình chỉ đọc phần tồn, riêng CÂN ĐỐI là hành động ghi dữ liệu: phiếu sử dụng
 * được xuất vượt tồn tối đa 5% nên tồn có thể âm ("Âm kho"), nút Cân Đối ghi thêm
 * một dòng inventory_balancings để đưa số lượng nhập về đúng thực tế.
 *
 * Dữ liệu trả về 2 mức:
 * - $datas     : tồn theo TỪNG MÃ XUẤT NHẬP (imports.code) - yêu cầu chính.
 * - $summaries : cộng dồn theo từng hoá chất trong danh mục, để xem tổng còn bao nhiêu.
 */
class ChemicalInventoryController extends Controller
{
    /** Hạn dùng còn dưới ngần này ngày là "Sắp hết hạn". */
    private const NEAR_EXPIRY_DAYS = 30;

    /** Hạn áp dụng rơi vào trong ngần này tháng thì vào tab "Hạn dùng dưới 6 tháng". */
    private const EXPIRING_SOON_MONTHS = 6;

    /** Tồn còn dưới ngần này so với lượng nhập ban đầu là "Sắp hết". */
    private const LOW_STOCK_RATIO = 0.2;

    /** Sai số cho phép khi so tồn với 0 (cột decimal 15,4). */
    private const EPSILON = 0.00005;

    /**
     * Tổng số đã cân đối của một mã xuất nhập không được vượt quá ngần này so với
     * SỐ LƯỢNG NHẬP ban đầu (5%), tính theo trị tuyệt đối và cộng dồn mọi lần
     * cân đối - chặn luỹ kế chứ không chặn từng lần, nếu không sẽ lách được
     * bằng cách cân đối nhiều lần nhỏ.
     */
    private const BALANCING_MAX_RATIO = 0.05;

    /** Tên hiển thị của từng trạng thái tồn, dùng chung cho bảng và bộ lọc. */
    public const STATES = [
        'in' => 'Còn hàng',
        'low' => 'Sắp hết',
        'near' => 'Sắp hết hạn',
        'expired' => 'Hết hạn',
        'out' => 'Hết hàng',
        'over' => 'Âm kho',
    ];

    public function index()
    {
        $departmentId = $this->departmentId();

        $datas = $this->stockByCode($departmentId);

        session()->put(['title' => 'TỒN - TỒN KHO HOÁ CHẤT']);

        return view('pages.inventory.ChemicalInventory.list', [
            'datas' => $datas,
            'summaries' => $this->stockByChemical($datas),
            'balancings' => $this->balancingHistory($departmentId),
            'zones' => $this->zoneOptions($departmentId),
            'states' => self::STATES,
            'nearExpiryDays' => self::NEAR_EXPIRY_DAYS,
            'expiringSoonMonths' => self::EXPIRING_SOON_MONTHS,
            'lowStockPercent' => (int) round(self::LOW_STOCK_RATIO * 100),
            'balancingMaxPercent' => (int) round(self::BALANCING_MAX_RATIO * 100),
        ]);
    }

    /**
     * CÂN ĐỐI SỐ LƯỢNG NHẬP - ghi thêm một dòng inventory_balancings.
     *
     * balancing_amount là SỐ ĐIỀU CHỈNH (dương = nhập thiếu nên cộng thêm, âm = nhập
     * dư nên trừ bớt), không phải số lượng nhập mới. Các lần cân đối cộng dồn; ghi sai
     * thì cân đối ngược lại chứ không sửa bản ghi cũ, để giữ vết cho Audit Trail.
     */
    public function balancing(Request $request)
    {
        $departmentId = $this->departmentId();

        $import = DB::table('imports')
            ->where('id', $request->import_id)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->first();

        if (! $import) {
            return redirect()->back()->with('error', 'Không tìm thấy mã xuất nhập cần cân đối!');
        }

        // Lô nhận lẻ từ phòng ban khác: phòng gửi đã cân chia sẵn nên số lượng là con số
        // đã chốt, không còn hao hụt cân đong để cân đối. Lệch thì phải từ chối nhận từ đầu.
        if ($import->is_partial_lot) {
            return redirect()->back()->with(
                'error',
                'Mã '.$import->code.' là lô nhận lẻ từ phòng ban khác nên không cân đối được. '
                .'Số lượng do phòng gửi cân chia, lệch thì phải làm việc lại với phòng gửi.'
            );
        }

        $validator = Validator::make($request->all(), [
            'import_id' => ['required', 'exists:imports,id'],
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

        // Cân đối là để đưa tồn về đúng thực tế, không được làm tồn âm thêm
        $gap = $this->gapOf($import);

        // Tổng đã cân đối trước đó và hạn mức 5% tính trên số lượng nhập ban đầu
        $balanced = $this->balancedOf($import);
        $limit = abs((float) $import->amount) * self::BALANCING_MAX_RATIO;

        $validator->after(function ($validator) use ($request, $gap, $balanced, $limit) {
            if (! is_numeric($request->balancing_amount)) {
                return;
            }

            $amount = (float) $request->balancing_amount;

            // not_in:0 chỉ chặn đúng chuỗi "0", còn "0.0" hay "-0" thì lọt
            if (abs($amount) < self::EPSILON) {
                $validator->errors()->add('balancing_amount', 'Số lượng cân đối phải khác 0.');

                return;
            }

            // Chặn luỹ kế: tổng các lần cân đối của mã xuất nhập không vượt 5% lượng nhập
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

        $id = DB::table('inventory_balancings')->insertGetId([
            'code' => $import->code,
            'import_id' => (int) $import->id,
            'department_id' => $departmentId,
            'balancing_amount' => $amount,
            'balancing_by' => $this->actor(),
            // Ô datetime-local gửi lên dạng "Y-m-dTH:i", đưa về đúng định dạng của MySQL
            'balancing_at' => \Carbon\Carbon::parse($request->balancing_at)->format('Y-m-d H:i:s'),
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Cân đối',
            'inventory_balancings',
            $id,
            'Tồn: '.$this->number($gap),
            'Cân đối '.($amount > 0 ? '+' : '').$this->number($amount).' -> tồn: '.$this->number($gap + $amount)
        );

        return redirect()->back()->with(
            'success',
            'Đã cân đối mã xuất nhập '.$import->code.' ('.($amount > 0 ? '+' : '').$this->number($amount).'), tồn còn lại '.$this->number($gap + $amount).'.'
        );
    }

    /**
     * XÁC ĐỊNH HẠN DÙNG NỘI BỘ - ghi imports.internal_expired_date.
     *
     * Hạn dùng nội bộ = ngày xác định + chemical_categories.shelf_life_months (tháng).
     * Nếu kết quả vượt quá hạn dùng của nhà sản xuất (imports.expired_date) thì lấy
     * chính imports.expired_date - hạn nội bộ không bao giờ dài hơn hạn ghi trên bao bì.
     *
     * Chỉ áp dụng cho hoá chất có shelf_life_months > 0. Xác định lại được nhiều lần,
     * mỗi lần đều ghi Audit Trail kèm ngày xác định và giá trị cũ.
     */
    public function internalExpiry(Request $request)
    {
        $departmentId = $this->departmentId();

        // Hạn dùng nội bộ lấy theo cấu hình của PHÒNG BAN, thiếu thì theo mặc định danh mục
        $query = DB::table('imports')
            ->leftJoin('chemical_categories', 'imports.category_id', '=', 'chemical_categories.id');

        $import = DepartmentChemical::join($query, $departmentId, 'imports.category_id')
            ->select(
                'imports.id',
                'imports.code',
                'imports.expired_date',
                'imports.internal_expired_date',
                DepartmentChemical::shelfLifeColumn()
            )
            ->where('imports.id', $request->import_id)
            ->where('imports.department_id', $departmentId)
            ->where('imports.status_id', 1)
            ->first();

        if (! $import) {
            return redirect()->back()->with('error', 'Không tìm thấy mã xuất nhập cần xác định hạn dùng nội bộ!');
        }

        $months = (int) ($import->shelf_life_months ?? 0);

        if ($months <= 0) {
            return redirect()->back()->with(
                'error',
                'Hoá chất của mã xuất nhập '.$import->code.' chưa khai báo hạn dùng mặc định trong Danh Mục nên không xác định được hạn dùng nội bộ!'
            );
        }

        $validator = Validator::make($request->all(), [
            'import_id' => ['required', 'exists:imports,id'],
            'determined_date' => ['required', 'date'],
        ], [
            'import_id.required' => 'Vui lòng chọn mã xuất nhập cần xác định.',
            'import_id.exists' => 'Mã xuất nhập cần xác định không tồn tại.',
            'determined_date.required' => 'Vui lòng chọn ngày xác định.',
            'determined_date.date' => 'Ngày xác định không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'internalExpiryErrors')->withInput();
        }

        $determined = \Carbon\Carbon::parse($request->determined_date)->startOfDay();
        $internal = $determined->copy()->addMonthsNoOverflow($months);
        $capped = false;

        // Hạn nội bộ không được vượt hạn của nhà sản xuất
        if ($import->expired_date) {
            $manufacturer = \Carbon\Carbon::parse($import->expired_date)->startOfDay();

            if ($internal->gt($manufacturer)) {
                $internal = $manufacturer;
                $capped = true;
            }
        }

        DB::table('imports')->where('id', $import->id)->update([
            'internal_expired_date' => $internal->format('Y-m-d'),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Xác định hạn dùng nội bộ',
            'imports',
            $import->id,
            $import->internal_expired_date
                ? \Carbon\Carbon::parse($import->internal_expired_date)->format('d/m/Y')
                : 'Chưa xác định',
            'Ngày xác định '.$determined->format('d/m/Y').' + '.$months.' tháng -> '
                .$internal->format('d/m/Y').($capped ? ' (lấy theo hạn nhà sản xuất)' : '')
        );

        return redirect()->back()->with(
            'success',
            'Đã xác định hạn dùng nội bộ của mã xuất nhập '.$import->code.' là '.$internal->format('d/m/Y')
                .($capped ? ', lấy theo hạn dùng của nhà sản xuất vì cộng đủ '.$months.' tháng sẽ vượt hạn.' : '.')
        );
    }

    /**
     * Tồn theo từng mã xuất nhập của phòng ban đang chọn.
     *
     * Lấy phiếu nhập và số lượng đã xuất bằng hai câu truy vấn rồi ghép trong PHP:
     * gọn hơn một câu join có SUM kèm điều kiện, và số liệu khớp đúng cách
     * ChemicalExportController đang kiểm tra tồn.
     */
    private function stockByCode(int $departmentId)
    {
        $used = $this->usedByImport($departmentId);
        $balanced = $this->balancedByImport($departmentId);
        $today = now()->startOfDay();

        $query = DB::table('imports')
            ->leftJoin('chemical_categories', 'imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'chemical_categories.unit_id', '=', 'units.id')
            ->leftJoin('suppliers', 'imports.supplier_id', '=', 'suppliers.id')
            // Định khu THỰC TẾ của lô: locations giữ sẵn id của cả 3 cấp trên nên
            // chỉ cần imports.location_id là dựng lại đủ Kho -> Phòng -> Kệ -> Vị trí
            ->leftJoin('locations', 'imports.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id');

        // Hạn dùng nội bộ và ngưỡng tồn tối thiểu lấy theo cấu hình riêng của phòng ban
        return DepartmentChemical::join($query, $departmentId, 'imports.category_id')
            ->select(
                'imports.id',
                'imports.code',
                'imports.category_id',
                'imports.amount',
                'imports.imported_date',
                'imports.expired_date',
                'imports.internal_expired_date',
                'imports.batch_no',
                'imports.invoice_number',
                'imports.is_microbiological_chemicals',
                'chemical_categories.code as category_code',
                'chemical_categories.classification',
                DepartmentChemical::shelfLifeColumn(),
                DepartmentChemical::minStockColumn(),
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'suppliers.name as supplier_name',
                'imports.location_id',
                'locations.code as location_code',
                'locations.name as location_name',
                'locations.warehouse_id',
                'locations.room_id',
                'locations.shelf_id',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where('imports.department_id', $departmentId)
            ->where('imports.status_id', 1)
            ->orderBy('imports.code', 'asc')
            ->get()
            ->map(function ($row) use ($used, $balanced, $today) {
                $out = $used[$row->id] ?? null;
                $bal = $balanced[$row->id] ?? null;

                $row->imported = (float) $row->amount;
                $row->balanced = (float) ($bal->balanced ?? 0);
                $row->balancing_times = (int) ($bal->times ?? 0);
                $row->last_balancing_at = $bal->last_balancing_at ?? null;
                $row->used = (float) ($out->used ?? 0);
                $row->cancelled = (float) ($out->cancelled ?? 0);
                $row->last_exported_date = $out->last_exported_date ?? null;
                $row->export_times = (int) ($out->times ?? 0);

                // Lượng nhập thực tế sau khi cân đối
                $row->effective = $row->imported + $row->balanced;

                // Hạn mức cân đối: tổng mọi lần cân đối không quá 5% lượng nhập ban đầu
                $row->balancing_limit = abs($row->imported) * self::BALANCING_MAX_RATIO;
                $row->balancing_min_input = -$row->balancing_limit - $row->balanced;
                $row->balancing_max_input = $row->balancing_limit - $row->balanced;

                // Phiếu sử dụng được xuất vượt tồn tối đa 5% nên chênh lệch có thể âm.
                // Giữ số âm ở $gap để nhận ra mã cần cân đối, còn $remaining không âm.
                $row->gap = $row->effective - $row->used - $row->cancelled;
                $row->remaining = max($row->gap, 0);
                $row->used_percent = $row->effective > 0
                    ? (int) min(round(($row->used + $row->cancelled) / $row->effective * 100), 100)
                    : 0;

                $row->days_to_expiry = $row->expired_date
                    ? (int) $today->diffInDays(\Carbon\Carbon::parse($row->expired_date)->startOfDay(), false)
                    : null;

                // Ngưỡng tồn tối thiểu riêng của phòng, null = chưa khai, dùng tỉ lệ mặc định
                $row->min_stock = $row->min_stock !== null ? (float) $row->min_stock : null;

                // Chỉ hoá chất có khai báo hạn dùng mặc định mới xác định được hạn dùng nội bộ
                $row->shelf_life_months = (int) ($row->shelf_life_months ?? 0);
                $row->can_internal_expiry = $row->shelf_life_months > 0;

                /*
                | Hạn ÁP DỤNG: hạn nội bộ nếu đã xác định, không thì hạn nhà sản xuất.
                | Hạn nội bộ luôn <= hạn nhà sản xuất (xem internalExpiry) nên đây là
                | ngày thực sự chặn việc sử dụng.
                */
                $row->effective_expired_date = $row->internal_expired_date ?: $row->expired_date;
                $row->days_to_effective_expiry = $row->effective_expired_date
                    ? (int) $today->diffInDays(\Carbon\Carbon::parse($row->effective_expired_date)->startOfDay(), false)
                    : null;

                // Còn tồn và hạn áp dụng rơi vào trong 6 tháng tới (kể cả đã quá hạn)
                $row->is_expiring_soon = $row->effective_expired_date
                    && $row->remaining > self::EPSILON
                    && \Carbon\Carbon::parse($row->effective_expired_date)->startOfDay()
                        ->lt($today->copy()->addMonthsNoOverflow(self::EXPIRING_SOON_MONTHS));

                $row->state = $this->stateOf($row);
                $row->state_label = self::STATES[$row->state];

                return $row;
            })
            ->pipe(fn ($rows) => $this->withGroupTotals($rows));
    }

    /**
     * Gắn thêm hai mức tồn cộng dồn cho mỗi mã xuất nhập:
     *
     * - batch_remaining    : tổng tồn của các mã CÙNG hoá chất (chemical_categories.code)
     *                        và CÙNG số lô (imports.batch_no) - "Tổng tồn theo lô".
     * - category_remaining : tổng tồn của các mã CÙNG hoá chất, không phân biệt lô
     *                        - "Tổng tồn các lô".
     *
     * Cộng trong PHP trên chính danh sách vừa tính chứ không truy vấn thêm, để hai cột
     * này luôn khớp với cột Tồn Còn Lại của từng dòng.
     *
     * Mã chưa khai số lô gom chung vào một nhóm "không có lô" - vẫn cộng được với nhau
     * và không lẫn sang lô có tên.
     */
    private function withGroupTotals($rows)
    {
        $byBatch = $rows->groupBy(fn ($row) => $row->category_id.'|'.($row->batch_no ?? ''))
            ->map(fn ($group) => [
                'remaining' => (float) $group->sum('remaining'),
                'codes' => $group->count(),
            ]);

        $byCategory = $rows->groupBy('category_id')
            ->map(fn ($group) => [
                'remaining' => (float) $group->sum('remaining'),
                'codes' => $group->count(),
                'batches' => $group->pluck('batch_no')->unique()->count(),
            ]);

        return $rows->map(function ($row) use ($byBatch, $byCategory) {
            $batch = $byBatch[$row->category_id.'|'.($row->batch_no ?? '')];
            $category = $byCategory[$row->category_id];

            $row->batch_remaining = $batch['remaining'];
            $row->batch_codes = $batch['codes'];

            $row->category_remaining = $category['remaining'];
            $row->category_codes = $category['codes'];
            $row->category_batches = $category['batches'];

            return $row;
        });
    }

    /**
     * Số lượng đã xuất của từng phiếu nhập: [import_id => {used, cancelled, times, last_exported_date}].
     *
     * Chuỗi trong DB::raw là hằng, không ghép từ dữ liệu người dùng.
     */
    private function usedByImport(int $departmentId)
    {
        return DB::table('exports')
            ->select(
                'import_id',
                DB::raw("SUM(CASE WHEN type = 'export' THEN amount ELSE 0 END) as used"),
                DB::raw("SUM(CASE WHEN type = 'cancel' THEN amount ELSE 0 END) as cancelled"),
                DB::raw('COUNT(*) as times'),
                DB::raw('MAX(exported_date) as last_exported_date')
            )
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->get()
            ->keyBy('import_id');
    }

    /** Số đã cân đối của từng phiếu nhập: [import_id => {balanced, times, last_balancing_at}]. */
    private function balancedByImport(int $departmentId)
    {
        return DB::table('inventory_balancings')
            ->select(
                'import_id',
                DB::raw('SUM(balancing_amount) as balanced'),
                DB::raw('COUNT(*) as times'),
                DB::raw('MAX(balancing_at) as last_balancing_at')
            )
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->get()
            ->keyBy('import_id');
    }

    /** Lịch sử cân đối để hiện trong modal, gom theo mã xuất nhập. */
    private function balancingHistory(int $departmentId)
    {
        return DB::table('inventory_balancings')
            ->select('import_id', 'balancing_amount', 'balancing_by', 'balancing_at')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('balancing_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('import_id');
    }

    /** Tổng số đã cân đối của một phiếu nhập - tính lại từ DB lúc ghi. */
    private function balancedOf($import): float
    {
        return (float) DB::table('inventory_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('balancing_amount');
    }

    /**
     * Chênh lệch tồn hiện tại của một phiếu nhập (có thể âm khi đã xuất vượt).
     *
     * Dùng lúc ghi cân đối - tính lại từ DB thay vì tin số trên form.
     */
    private function gapOf($import): float
    {
        $out = (float) DB::table('exports')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('amount');

        return (float) $import->amount + $this->balancedOf($import) - $out;
    }

    /**
     * Bốn cấp định khu của phòng ban, cho bộ lọc Kho -> Phòng -> Kệ -> Vị Trí.
     *
     * Mỗi cấp mang sẵn id của các cấp trên nên phần lọc dây chuyền làm được hoàn toàn
     * ở trình duyệt, không phải tải lại trang mỗi lần đổi lựa chọn.
     */
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

    /** Cộng dồn tồn của các mã xuất nhập về từng hoá chất trong danh mục. */
    private function stockByChemical($datas)
    {
        return $datas
            ->groupBy('category_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $inStock = $rows->filter(fn ($row) => $row->remaining > self::EPSILON);

                return (object) [
                    'category_code' => $first->category_code,
                    // Giữ lại để bộ lọc Phụ lục / Nhóm hoá chất dùng được ở bảng cộng dồn
                    'classification' => $first->classification,
                    'chem_name' => $first->chem_name,
                    'unit' => $first->unit_short_name ?: $first->unit_name,
                    'imported' => (float) $rows->sum('imported'),
                    'balanced' => (float) $rows->sum('balanced'),
                    'used' => (float) $rows->sum('used'),
                    'cancelled' => (float) $rows->sum('cancelled'),
                    'remaining' => (float) $rows->sum('remaining'),
                    'code_count' => $rows->count(),
                    'in_stock_count' => $inStock->count(),
                    // Hạn dùng gần nhất trong các phiếu còn tồn - phần cần dùng trước
                    'nearest_expiry' => $inStock->whereNotNull('expired_date')->min('expired_date'),
                    'alert_count' => $rows->whereIn('state', ['low', 'near', 'expired', 'over'])->count(),
                ];
            })
            ->sortBy('chem_name')
            ->values();
    }

    /**
     * Trạng thái tồn của một mã xuất nhập, xét theo thứ tự ưu tiên:
     * âm kho -> hết hàng -> hết hạn -> sắp hết hạn -> sắp hết -> còn hàng.
     */
    private function stateOf($row): string
    {
        // Đã xuất vượt lượng nhập (được phép tối đa 5%) - cần cân đối lại
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

        /*
        | "Sắp hết" ưu tiên ngưỡng tồn tối thiểu do PHÒNG BAN khai trong department_chemicals:
        | 20% của 1000 lít khác hẳn 20% của 100 ml. Phòng chưa khai thì giữ nguyên cách cũ
        | là tính theo tỉ lệ, nên hành vi không đổi cho tới khi có người khai ngưỡng.
        */
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
