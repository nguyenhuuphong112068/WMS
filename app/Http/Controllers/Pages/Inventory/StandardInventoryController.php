<?php

namespace App\Http\Controllers\Pages\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentStandard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * TỒN - TỒN KHO CHẤT CHUẨN
 *
 * Tồn kho được TÍNH RA từ các bảng nghiệp vụ đã có, không lưu thành một bảng tồn
 * riêng (tránh số liệu lệch nhau):
 *
 *      Tồn của một mã ống chuẩn = standard_imports.amount
 *                           + SUM(standard_balancings.balancing_amount)
 *                           - SUM(standard_exports.amount)
 *
 * LỌC THEO KỲ: chỉ hiện mã ống chuẩn CÓ PHÁT SINH hoặc CÒN TỒN trong kỳ - còn tồn cuối
 * kỳ, hoặc có sử dụng, hoặc có loại bỏ (xem movedInPeriod). Ống đã hết sạch từ kỳ trước,
 * trong kỳ không động tới thì không hiện.
 *
 * KỲ BÁO CÁO: màn hình xét một khoảng "từ ngày - đến ngày" (mặc định là trọn tháng
 * hiện tại), tách công thức trên thành bốn chỉ số theo mốc thời gian:
 *
 *      Tồn đầu kỳ   = phát sinh TRƯỚC ngày bắt đầu kỳ
 *      Nhập trong kỳ = standard_imports.imported_date trong kỳ (+ cân đối ghi trong kỳ)
 *      Sử dụng / Huỷ trong kỳ = standard_exports.created_at trong kỳ, tách theo type
 *      Tồn cuối kỳ  = đầu kỳ + nhập - sử dụng - huỷ
 *
 * Ống nhập sau ngày cuối kỳ không hiện trên bảng vì trong kỳ đó chưa tồn tại. Mọi số
 * luỹ kế khác (trạng thái tồn, hạn dùng, kiểm soát khối lượng) cũng tính đến hết ngày
 * cuối kỳ để cả màn hình cùng nói về một thời điểm.
 *
 * Quy ước tính:
 * - Chỉ tính phiếu nhập còn hiệu lực (standard_imports.status_id = 1).
 * - Chỉ trừ phiếu sử dụng còn hiệu lực (standard_exports.status_id = 1), đúng như cách
 *   StandardExportController kiểm tra tồn khi ghi phiếu.
 * - Cả 'export' (sử dụng) và 'cancel' (huỷ bỏ) đều trừ tồn, nhưng tách thành hai cột
 *   để thấy phần hao hụt do huỷ.
 * - Số lượng theo đơn vị phòng đã khai cho chất chuẩn đó (standard_department_categories.unit_id).
 *
 * Màn hình chỉ đọc phần tồn, hai hành động ghi dữ liệu là:
 * - CÂN ĐỐI: phiếu sử dụng được xuất vượt tồn tối đa 5% nên tồn có thể âm ("Âm kho"),
 *   nút Cân Đối ghi thêm một dòng standard_balancings để đưa số lượng nhập về đúng thực tế.
 * - XÁC ĐỊNH HẠN DÙNG NỘI BỘ: hạn sau khi mở ống, ghi standard_imports.internal_expired_date.
 */
class StandardInventoryController extends Controller
{
    /** Hạn dùng còn dưới ngần này ngày là "Sắp hết hạn". */
    private const NEAR_EXPIRY_DAYS = 30;

    /** Hạn áp dụng rơi vào trong ngần này tháng thì vào tab "Sắp hết hạn". */
    private const EXPIRING_SOON_MONTHS = 6;

    /** Tồn còn dưới ngần này so với lượng nhập ban đầu là "Sắp hết". */
    private const LOW_STOCK_RATIO = 0.2;

    /** Sai số cho phép khi so tồn với 0 (cột decimal 15,4). */
    /** Loại lưu trữ của định khu mà màn hình này quan tâm - xem locations.item_type. */
    private const LOCATION_TYPE = 'standard';

    private const EPSILON = 0.00005;

    /**
     * Tổng số đã cân đối của một mã ống chuẩn không được vượt quá ngần này so với
     * SỐ LƯỢNG NHẬP ban đầu (5%), tính theo trị tuyệt đối và cộng dồn mọi lần cân đối
     * - chặn luỹ kế chứ không chặn từng lần, nếu không sẽ lách được bằng cách cân đối
     * nhiều lần nhỏ.
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

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $period = $this->period($request);

        $datas = $this->stockByCode($departmentId, $period['from'], $period['to']);

        session()->put(['title' => 'TỒN - TỒN KHO CHẤT CHUẨN']);

        return view('pages.inventory.StandardInventory.list', [
            'datas' => $datas,
            'summaries' => $this->stockByStandard($datas),
            'balancings' => $this->balancingHistory($departmentId),
            'zones' => $this->zoneOptions($departmentId),
            'states' => self::STATES,
            'groups' => config('standard.groups'),
            'period' => $period,
            'nearExpiryDays' => self::NEAR_EXPIRY_DAYS,
            'expiringSoonMonths' => self::EXPIRING_SOON_MONTHS,
            'lowStockPercent' => (int) round(self::LOW_STOCK_RATIO * 100),
            'balancingMaxPercent' => (int) round(self::BALANCING_MAX_RATIO * 100),
        ]);
    }

    /**
     * KỲ BÁO CÁO - khoảng "từ ngày - đến ngày" mà cả màn hình tồn đang xét.
     *
     * Mặc định là TRỌN THÁNG HIỆN TẠI (ngày 1 đến ngày cuối tháng). Ngày cuối tháng
     * còn ở tương lai thì cũng chưa có phát sinh nào, nên "Tồn cuối kỳ" vẫn đúng bằng
     * tồn thực tế đang có trong kho.
     *
     * Ngày gửi lên không đọc được thì bỏ qua và lấy mặc định; chọn ngược ngày
     * (từ > đến) thì đảo lại cho đúng thứ tự thay vì báo lỗi.
     */
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
            // Kỳ còn bao hôm nay: chưa có phát sinh nào sau đó nên tồn cuối kỳ = tồn hiện tại
            'is_current' => $to->gte($today),
        ];
    }

    /**
     * CÂN ĐỐI SỐ LƯỢNG NHẬP - ghi thêm một dòng standard_balancings.
     *
     * balancing_amount là SỐ ĐIỀU CHỈNH (dương = nhập thiếu nên cộng thêm, âm = nhập
     * dư nên trừ bớt), không phải số lượng nhập mới. Các lần cân đối cộng dồn; ghi sai
     * thì cân đối ngược lại chứ không sửa bản ghi cũ, để giữ vết cho Audit Trail.
     */
    public function balancing(Request $request)
    {
        $departmentId = $this->departmentId();

        $import = DB::table('standard_imports')
            ->where('id', $request->import_id)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->first();

        if (! $import) {
            return redirect()->back()->with('error', 'Không tìm thấy mã ống chuẩn cần cân đối!');
        }

        $validator = Validator::make($request->all(), [
            'import_id' => ['required', 'exists:standard_imports,id'],
            'balancing_amount' => ['required', 'numeric', 'not_in:0'],
            'balancing_at' => ['required', 'date'],
        ], [
            'import_id.required' => 'Vui lòng chọn mã ống chuẩn cần cân đối.',
            'import_id.exists' => 'Mã ống chuẩn cần cân đối không tồn tại.',
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

            // Chặn luỹ kế: tổng các lần cân đối của mã ống chuẩn không vượt 5% lượng nhập
            if (abs($balanced + $amount) > $limit + self::EPSILON) {
                $validator->errors()->add(
                    'balancing_amount',
                    'Chỉ được cân đối tối đa '.(int) round(self::BALANCING_MAX_RATIO * 100).'% số lượng nhập, tức ±'
                    .$this->number($limit).'. Mã ống chuẩn này đã cân đối '.$this->number($balanced)
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

        $id = DB::table('standard_balancings')->insertGetId([
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
            'standard_balancings',
            $id,
            'Tồn: '.$this->number($gap),
            'Cân đối '.($amount > 0 ? '+' : '').$this->number($amount).' -> tồn: '.$this->number($gap + $amount)
        );

        return redirect()->back()->with(
            'success',
            'Đã cân đối mã ống chuẩn '.$import->code.' ('.($amount > 0 ? '+' : '').$this->number($amount)
            .'), tồn còn lại '.$this->number($gap + $amount).'.'
        );
    }

    /**
     * XÁC ĐỊNH HẠN DÙNG NỘI BỘ - ghi standard_imports.internal_expired_date.
     *
     * Với chất chuẩn, hạn dùng nội bộ là hạn tính từ NGÀY MỞ ỐNG:
     * hạn nội bộ = ngày xác định + shelf_life_months (tháng).
     * Nếu kết quả vượt quá hạn của nhà sản xuất (standard_imports.expired_date) thì
     * lấy chính hạn nhà sản xuất - hạn nội bộ không bao giờ dài hơn hạn trên nhãn gốc.
     *
     * Chỉ áp dụng cho chất chuẩn có shelf_life_months > 0. Xác định lại được nhiều lần,
     * mỗi lần đều ghi Audit Trail kèm ngày xác định và giá trị cũ.
     */
    public function internalExpiry(Request $request)
    {
        $departmentId = $this->departmentId();

        // Hạn dùng nội bộ lấy theo cấu hình của PHÒNG BAN, thiếu thì theo mặc định danh mục
        $query = DB::table('standard_imports')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id');

        $import = DepartmentStandard::join($query, $departmentId, 'standard_imports.category_id')
            ->select(
                'standard_imports.id',
                'standard_imports.code',
                'standard_imports.expired_date',
                'standard_imports.internal_expired_date',
                DepartmentStandard::shelfLifeColumn()
            )
            ->where('standard_imports.id', $request->import_id)
            ->where('standard_imports.department_id', $departmentId)
            ->where('standard_imports.status_id', 1)
            ->first();

        if (! $import) {
            return redirect()->back()->with('error', 'Không tìm thấy mã ống chuẩn cần xác định hạn dùng nội bộ!');
        }

        $months = (int) ($import->shelf_life_months ?? 0);

        if ($months <= 0) {
            return redirect()->back()->with(
                'error',
                'Chất chuẩn của mã ống '.$import->code.' chưa khai báo hạn dùng mặc định trong Danh Mục nên không xác định được hạn dùng nội bộ!'
            );
        }

        $validator = Validator::make($request->all(), [
            'import_id' => ['required', 'exists:standard_imports,id'],
            'determined_date' => ['required', 'date'],
        ], [
            'import_id.required' => 'Vui lòng chọn mã ống chuẩn cần xác định.',
            'import_id.exists' => 'Mã ống chuẩn cần xác định không tồn tại.',
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

        DB::table('standard_imports')->where('id', $import->id)->update([
            'internal_expired_date' => $internal->format('Y-m-d'),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Xác định hạn dùng nội bộ',
            'standard_imports',
            $import->id,
            $import->internal_expired_date
                ? \Carbon\Carbon::parse($import->internal_expired_date)->format('d/m/Y')
                : 'Chưa xác định',
            'Ngày xác định '.$determined->format('d/m/Y').' + '.$months.' tháng -> '
                .$internal->format('d/m/Y').($capped ? ' (lấy theo hạn nhà sản xuất)' : '')
        );

        return redirect()->back()->with(
            'success',
            'Đã xác định hạn dùng nội bộ của mã ống chuẩn '.$import->code.' là '.$internal->format('d/m/Y')
                .($capped ? ', lấy theo hạn dùng của nhà sản xuất vì cộng đủ '.$months.' tháng sẽ vượt hạn.' : '.')
        );
    }

    /**
     * Ghi nhận xét khi kiểm soát khối lượng ngoài giới hạn
     */
    public function weightRemark(Request $request)
    {
        $departmentId = $this->departmentId();

        $import = DB::table('standard_imports')
            ->where('id', $request->import_id)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->first();

        if (! $import) {
            return redirect()->back()->with('error', 'Không tìm thấy mã ống chuẩn!');
        }

        $validator = Validator::make($request->all(), [
            'import_id' => ['required', 'exists:standard_imports,id'],
            'weight_deviation_remark' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('error', 'Lỗi nhập liệu.');
        }

        DB::table('standard_imports')->where('id', $import->id)->update([
            'weight_deviation_remark' => $request->weight_deviation_remark,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Nhận xét khối lượng',
            'standard_imports',
            $import->id,
            $import->weight_deviation_remark ?: 'Trống',
            $request->weight_deviation_remark ?: 'Trống'
        );

        return redirect()->back()->with(
            'success',
            'Đã lưu nhận xét kiểm soát khối lượng cho mã ống chuẩn '.$import->code
        );
    }

    /**
     * Tồn theo từng mã ống chuẩn của phòng ban đang chọn.
     *
     * Lấy phiếu nhập và số lượng đã xuất bằng hai câu truy vấn rồi ghép trong PHP:
     * gọn hơn một câu join có SUM kèm điều kiện, và số liệu khớp đúng cách
     * StandardExportController đang kiểm tra tồn.
     */
    private function stockByCode(int $departmentId, string $from, string $to)
    {
        $used = $this->usedByImport($departmentId, $from, $to);
        $balanced = $this->balancedByImport($departmentId, $from, $to);
        $today = now()->startOfDay();

        $query = DB::table('standard_imports')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            // Đơn vị tính khai ở danh mục chất chuẩn CỦA PHÒNG, không còn ở danh mục chung
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_imports.category_id'))
            ->leftJoin('suppliers', 'standard_imports.supplier_id', '=', 'suppliers.id')
            // Định khu THỰC TẾ của ống: locations giữ sẵn id của cả 3 cấp trên nên
            // chỉ cần standard_imports.location_id là dựng lại đủ Kho -> Phòng -> Kệ -> Vị trí
            ->leftJoin('locations', 'standard_imports.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id');

        // Hạn dùng nội bộ và ngưỡng tồn tối thiểu lấy theo cấu hình riêng của phòng ban
        return DepartmentStandard::join($query, $departmentId, 'standard_imports.category_id')
            ->select(
                'standard_imports.id',
                'standard_imports.code',
                'standard_imports.category_id',
                'standard_imports.group_code',
                'standard_imports.amount',
                'standard_imports.imported_date',
                'standard_imports.expired_date',
                'standard_imports.expiry_type',
                'standard_imports.internal_expired_date',
                'standard_imports.batch_no',
                'standard_imports.coa_no',
                'standard_imports.invoice_number',
                'standard_imports.weight_controlled',
                'standard_imports.weight_deviation_remark',
                'standard_imports.standard_form',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_categories.cas_no',
                'standard_categories.groups',
                DepartmentStandard::shelfLifeColumn(),
                DepartmentStandard::minStockColumn(),
                'standard_names.name as standard_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'suppliers.name as supplier_name',
                'standard_imports.location_id',
                'locations.code as location_code',
                'locations.warehouse_id',
                'locations.room_id',
                'locations.shelf_id',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where('standard_imports.department_id', $departmentId)
            ->where('standard_imports.status_id', 1)
            // Ống nhập sau ngày cuối kỳ thì trong kỳ này chưa tồn tại -> không đưa vào bảng
            ->whereDate('standard_imports.imported_date', '<=', $to)
            ->orderBy('standard_imports.code', 'asc')
            ->get()
            ->map(function ($row) use ($used, $balanced, $from, $to, $today) {
                $out = $used[$row->id] ?? null;
                $bal = $balanced[$row->id] ?? null;

                // Cột date của MySQL trả về chuỗi 'Y-m-d', so sánh chuỗi là đúng thứ tự ngày
                $importedDate = substr((string) $row->imported_date, 0, 10);

                /*
                | SỐ LUỸ KẾ TÍNH ĐẾN HẾT NGÀY CUỐI KỲ.
                |
                | Đây là mốc để xét trạng thái tồn, vạch tiến độ đã dùng và các tab phụ,
                | nhờ vậy khi chọn một kỳ trong quá khứ thì cả màn hình cùng nói về một
                | thời điểm chứ không lẫn số của hôm nay.
                */
                $row->imported = (float) $row->amount;
                $row->balanced = (float) ($bal->balanced_to ?? 0);
                $row->used = (float) ($out->used_to ?? 0);
                $row->cancelled = (float) ($out->cancelled_to ?? 0);

                $row->last_balancing_at = $bal->last_balancing_at ?? null;
                $row->balancing_times = (int) ($bal->times ?? 0);
                $row->last_exported_date = $out->last_exported_date ?? null;
                $row->export_times = (int) ($out->times ?? 0);
                $row->period_export_times = (int) ($out->times_in ?? 0);

                /*
                | BỐN CHỈ SỐ CỦA KỲ
                |
                | - Tồn đầu kỳ    : mọi phát sinh TRƯỚC ngày bắt đầu kỳ (nhập + cân đối - xuất).
                | - Nhập trong kỳ : phiếu nhập có ngày nhập nằm trong kỳ, cộng cả số cân đối
                |                   ghi trong kỳ vì cân đối là chỉnh lại chính lượng đã nhập.
                | - Sử dụng / Huỷ : phiếu sử dụng có ngày sử dụng nằm trong kỳ, tách theo type.
                | - Tồn cuối kỳ   : đầu kỳ + nhập - sử dụng - huỷ.
                |
                | Cộng lại đúng bằng phần luỹ kế đến hết kỳ ở trên, nên Tồn Cuối Kỳ luôn
                | khớp công thức tồn cũ (nhập + cân đối - đã dùng - đã huỷ).
                */
                $row->opening = ($importedDate < $from ? $row->imported : 0)
                    + (float) ($bal->balanced_before ?? 0)
                    - (float) ($out->used_before ?? 0)
                    - (float) ($out->cancelled_before ?? 0);

                $row->period_imported = $importedDate >= $from && $importedDate <= $to ? $row->imported : 0;
                $row->period_balanced = (float) ($bal->balanced_in ?? 0);
                $row->period_in = $row->period_imported + $row->period_balanced;
                $row->period_used = (float) ($out->used_in ?? 0);
                $row->period_cancelled = (float) ($out->cancelled_in ?? 0);

                // Ống mới nhập trong kỳ: đầu kỳ chưa có gì, dùng để ghi chú trên bảng
                $row->is_new_in_period = $row->period_imported > 0;

                // Lượng nhập thực tế sau khi cân đối, tính đến hết kỳ
                $row->effective = $row->imported + $row->balanced;

                /*
                | Hạn mức cân đối xét trên TOÀN BỘ lịch sử cân đối, không cắt theo kỳ:
                | tổng mọi lần cân đối không quá 5% lượng nhập ban đầu.
                */
                $row->balanced_all = (float) ($bal->balanced_all ?? 0);
                $row->balancing_limit = abs($row->imported) * self::BALANCING_MAX_RATIO;
                $row->balancing_min_input = -$row->balancing_limit - $row->balanced_all;
                $row->balancing_max_input = $row->balancing_limit - $row->balanced_all;

                // Phiếu sử dụng được xuất vượt tồn tối đa 5% nên chênh lệch có thể âm.
                // Giữ số âm ở $gap để nhận ra mã cần cân đối, còn $remaining không âm.
                $row->closing = $row->opening + $row->period_in - $row->period_used - $row->period_cancelled;
                $row->gap = $row->closing;
                $row->remaining = max($row->gap, 0);
                $row->used_percent = $row->effective > 0
                    ? (int) min(round(($row->used + $row->cancelled) / $row->effective * 100), 100)
                    : 0;

                $row->days_to_expiry = $row->expired_date
                    ? (int) $today->diffInDays(\Carbon\Carbon::parse($row->expired_date)->startOfDay(), false)
                    : null;

                // Ngưỡng tồn tối thiểu riêng của phòng, null = chưa khai, dùng tỉ lệ mặc định
                $row->min_stock = $row->min_stock !== null ? (float) $row->min_stock : null;

                // Chỉ chất chuẩn có khai báo hạn dùng mặc định mới xác định được hạn dùng nội bộ
                $row->shelf_life_months = (int) ($row->shelf_life_months ?? 0);
                $row->can_internal_expiry = $row->shelf_life_months > 0;

                $row->ghkl = 0;
                $row->deviation = 0;
                if ($row->weight_controlled) {
                    $quicach = $row->imported;
                    $unit = strtolower(trim($row->unit_short_name ?: $row->unit_name));
                    if ($unit === 'g' || $unit === 'ml') {
                        $quicach *= 1000;
                    }

                    if ($row->standard_form === 'Dạng Bột Rời') {
                        if ($quicach < 10) $row->ghkl = 50;
                        elseif ($quicach <= 100) $row->ghkl = 60;
                        else $row->ghkl = 70;
                    } elseif ($row->standard_form === 'Dạng Bột Mịn') {
                        if ($quicach < 10) $row->ghkl = 20;
                        elseif ($quicach <= 100) $row->ghkl = 30;
                        else $row->ghkl = 50;
                    } elseif ($row->standard_form === 'Dạng Sệt') {
                        if ($quicach < 10) $row->ghkl = 10;
                        elseif ($quicach <= 100) $row->ghkl = 20;
                        else $row->ghkl = 50;
                    }

                    $klThuc = $row->imported;
                    $soLuongXuat = $row->used;
                    if ($unit === 'g' || $unit === 'ml') {
                        $klThuc *= 1000;
                        $soLuongXuat *= 1000;
                    }
                    if ($soLuongXuat != 0) {
                        $row->deviation = round(abs(($klThuc - $soLuongXuat) / $soLuongXuat) * 100, 1);
                    }
                }

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
            ->filter(fn ($row) => $this->movedInPeriod($row))
            ->values()
            ->pipe(fn ($rows) => $this->withGroupTotals($rows));
    }

    /**
     * LỌC THEO KỲ - một mã ống chuẩn chỉ hiện trên màn hình tồn khi trong kỳ đang xem
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

    /**
     * Gắn thêm hai mức tồn cộng dồn cho mỗi mã ống chuẩn:
     *
     * - batch_remaining    : tổng tồn của các ống CÙNG chất chuẩn và CÙNG số lô.
     * - category_remaining : tổng tồn của các ống CÙNG chất chuẩn, không phân biệt lô.
     *
     * Cộng trong PHP trên chính danh sách vừa tính chứ không truy vấn thêm, để hai cột
     * này luôn khớp với cột Tồn Còn Lại của từng dòng.
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
     * Số lượng đã xuất của từng ống chuẩn, CẮT THEO KỲ:
     *
     *      [import_id => {used_before, cancelled_before,   -> trước ngày bắt đầu kỳ
     *                     used_in, cancelled_in, times_in, -> trong kỳ
     *                     used_to, cancelled_to,           -> luỹ kế đến hết kỳ
     *                     times, last_exported_date}]
     *
     * Gom cả ba mốc trong MỘT câu truy vấn bằng SUM(CASE WHEN ...) để không phải quét
     * bảng phiếu sử dụng ba lần. Ngày kỳ đưa vào bằng binding, không ghép chuỗi.
     *
     * Ngày sử dụng lấy theo standard_exports.created_at - bảng này KHÔNG còn cột
     * exported_date (đã bỏ ở migration 2026_08_26_143500), màn hình Sử Dụng cũng đang
     * hiển thị created_at làm ngày xuất. created_at là datetime nên mốc kỳ phải mở
     * rộng ra cả ngày: 00:00:00 của ngày đầu đến 23:59:59 của ngày cuối.
     */
    private function usedByImport(int $departmentId, string $from, string $to)
    {
        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';

        return DB::table('standard_exports')
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
            ->groupBy('import_id')
            ->get()
            ->keyBy('import_id');
    }

    /**
     * Số đã cân đối của từng ống chuẩn, CẮT THEO KỲ:
     * [import_id => {balanced_before, balanced_in, balanced_to, balanced_all, times, last_balancing_at}].
     *
     * balancing_at là datetime nên mốc kỳ phải mở rộng ra cả ngày: từ 00:00:00 của
     * ngày bắt đầu đến 23:59:59 của ngày kết thúc.
     *
     * balanced_all là tổng KHÔNG cắt theo kỳ - hạn mức cân đối 5% xét trên toàn bộ
     * lịch sử nên không được để kỳ đang xem làm sai hạn mức đó.
     */
    private function balancedByImport(int $departmentId, string $from, string $to)
    {
        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';

        return DB::table('standard_balancings')
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

    /** Lịch sử cân đối để hiện trong modal, gom theo mã ống chuẩn. */
    private function balancingHistory(int $departmentId)
    {
        return DB::table('standard_balancings')
            ->select('import_id', 'balancing_amount', 'balancing_by', 'balancing_at')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('balancing_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('import_id');
    }

    /** Tổng số đã cân đối của một ống chuẩn - tính lại từ DB lúc ghi. */
    private function balancedOf($import): float
    {
        return (float) DB::table('standard_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('balancing_amount');
    }

    /**
     * Chênh lệch tồn hiện tại của một ống chuẩn (có thể âm khi đã xuất vượt).
     *
     * Dùng lúc ghi cân đối - tính lại từ DB thay vì tin số trên form.
     */
    private function gapOf($import): float
    {
        $out = (float) DB::table('standard_exports')
            ->where('import_id', $import->id)
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
            'locations' => $this->locationOptions($departmentId),
        ];
    }

    /**
     * Chỉ lấy các ô đã khai loại lưu trữ là CHẤT CHUẨN. Ô chưa khai loại là "Dùng chung"
     * nên vẫn lấy - định khu cũ chưa phân loại không bị biến mất khỏi màn hình này.
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

    /** Cộng dồn tồn của các mã ống chuẩn về từng chất chuẩn trong danh mục. */
    private function stockByStandard($datas)
    {
        return $datas
            ->groupBy('category_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $inStock = $rows->filter(fn ($row) => $row->remaining > self::EPSILON);

                return (object) [
                    'category_code' => $first->category_code,
                    // Giữ lại để bộ lọc Phân nhóm chuẩn dùng được ở bảng cộng dồn
                    'groups' => $first->groups,
                    'standard_name' => $first->standard_name,
                    'version' => $first->category_version,
                    'cas_no' => $first->cas_no,
                    'unit' => $first->unit_short_name ?: $first->unit_name,
                    'imported' => (float) $rows->sum('imported'),
                    'balanced' => (float) $rows->sum('balanced'),
                    'used' => (float) $rows->sum('used'),
                    'cancelled' => (float) $rows->sum('cancelled'),
                    'remaining' => (float) $rows->sum('remaining'),
                    // Bốn chỉ số của kỳ, cộng thẳng từ các ống nên luôn khớp bảng theo mã ống
                    'opening' => (float) $rows->sum('opening'),
                    'period_in' => (float) $rows->sum('period_in'),
                    'period_used' => (float) $rows->sum('period_used'),
                    'period_cancelled' => (float) $rows->sum('period_cancelled'),
                    'closing' => (float) $rows->sum('closing'),
                    'code_count' => $rows->count(),
                    'in_stock_count' => $inStock->count(),
                    // Hạn dùng gần nhất trong các ống còn tồn - phần cần dùng trước
                    'nearest_expiry' => $inStock->whereNotNull('expired_date')->min('expired_date'),
                    'alert_count' => $rows->whereIn('state', ['low', 'near', 'expired', 'over'])->count(),
                ];
            })
            ->sortBy('standard_name')
            ->values();
    }

    /**
     * Trạng thái tồn của một mã ống chuẩn, xét theo thứ tự ưu tiên:
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
        | "Sắp hết" ưu tiên ngưỡng tồn tối thiểu do PHÒNG BAN khai trong standard_department_categories.
        | Phòng chưa khai thì tính theo tỉ lệ so với lượng nhập ban đầu.
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
        return \App\Support\Signer::actor();
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }
}
