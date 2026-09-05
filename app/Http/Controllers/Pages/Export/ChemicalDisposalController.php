<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentChemical;
use App\Support\UnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * SỬ DỤNG - HUỶ HOÁ CHẤT (BƯỚC 2)
 *
 * Huỷ hoá chất đi hai bước, tách hẳn nhau:
 *
 * BƯỚC 1 - LOẠI BỎ : làm ở màn hình Sử Dụng Hoá Chất, lập phiếu loại "Huỷ bỏ"
 *                    (exports.type = 'cancel'). Hoá chất bị trừ tồn ngay và rơi vào
 *                    tab "Hoá chất chờ huỷ" chờ được gom.
 * BƯỚC 2 - HUỶ    : chính là controller này. Chọn nhiều phiếu loại bỏ đang chờ, gom
 *                    thành MỘT đợt huỷ để xin quyết định huỷ một lần từ TP. ĐBCL và
 *                    Ban Giám Đốc. Có quyết định rồi thì in được biểu mẫu QA/F/058-07
 *                    "Phiếu theo dõi và quyết định huỷ".
 *
 * Đợt huỷ KHÔNG động vào tồn kho - tồn đã trừ từ bước 1. Đây thuần tuý là hồ sơ chất
 * lượng: gom phế phẩm, xin duyệt, giao nhận, theo dõi huỷ.
 */
class ChemicalDisposalController extends Controller
{
    private const TABLE = 'chemical_disposals';

    private const EXPORT_TABLE = 'chemical_exports';

    private const LABEL = 'đợt huỷ hoá chất';

    /** Chỉ phiếu loại bỏ mới được gom vào đợt huỷ. */
    private const TYPE_CANCEL = 'cancel';

    /** Vòng đời của một đợt huỷ, xem chú thích ở migration create_disposals_table. */
    public const STATUSES = [
        'draft' => 'Đang gom phiếu',
        'pending' => 'Chờ quyết định',
        'approved' => 'Đã có quyết định',
        'rejected' => 'Không được duyệt',
        'done' => 'Đã huỷ xong',
    ];

    /** Phương pháp huỷ, đúng hai lựa chọn khoanh tròn trên biểu mẫu. */
    public const METHODS = [
        'burn' => 'Đốt',
        'dissolve' => 'Hoà tan trong nước và xả vào hệ thống xử lý nước thải',
    ];

    /** Bên thực hiện huỷ, đúng hai ô đánh dấu trên biểu mẫu. */
    public const EXECUTORS = [
        'agency' => 'Cơ quan huỷ',
        'other' => 'Đơn vị khác',
    ];

    /** Thông tin cố định in trên đầu biểu mẫu QA/F/058-07. */
    public const FORM = [
        'sop_no' => 'QA-SOP-027',
        'form_no' => 'QA/F/058-07',
        'effective_date' => '01/03/2022',
    ];

    /**
     * HÀNG CHỜ HUỶ - phiếu loại bỏ chưa được gom vào đợt nào.
     *
     * Chỉ lấy phiếu còn hiệu lực: phiếu đã khoá không còn trừ tồn nên cũng không có
     * phế phẩm nào để xin huỷ.
     */
    public static function waiting(int $departmentId)
    {
        return DB::table(self::EXPORT_TABLE)
            ->leftJoin('chemical_imports', self::EXPORT_TABLE.'.import_id', '=', 'chemical_imports.id')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            // Đơn vị tính khai ở danh mục hoá chất CỦA PHÒNG, không còn ở danh mục chung
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_imports.category_id'))
            ->select(
                self::EXPORT_TABLE.'.id',
                self::EXPORT_TABLE.'.code',
                self::EXPORT_TABLE.'.amount',
                self::EXPORT_TABLE.'.exported_date',
                self::EXPORT_TABLE.'.exported_by',
                self::EXPORT_TABLE.'.purpose',
                self::EXPORT_TABLE.'.test_report_no',
                self::EXPORT_TABLE.'.checked_by',
                'chemical_categories.code as category_code',
                'chemical_imports.category_id as category_id',
                'chem_names.name as chem_name',
                'chemical_imports.batch_no',
                'chemical_imports.expired_date',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where(self::EXPORT_TABLE.'.department_id', $departmentId)
            ->where(self::EXPORT_TABLE.'.type', self::TYPE_CANCEL)
            ->where(self::EXPORT_TABLE.'.status_id', 1)
            ->whereNull(self::EXPORT_TABLE.'.disposal_id')
            ->orderBy(self::EXPORT_TABLE.'.exported_date', 'asc')
            ->orderBy(self::EXPORT_TABLE.'.id', 'asc')
            ->get();
    }

    /**
     * Các đợt huỷ của phòng ban, kèm danh sách phiếu của từng đợt.
     *
     * Lấy toàn bộ phiếu của các đợt trong MỘT truy vấn rồi gắn vào đợt, tránh chạy
     * một truy vấn cho mỗi đợt khi màn hình có nhiều đợt.
     */
    public static function batches(int $departmentId)
    {
        $rows = DB::table(self::TABLE)
            ->where('department_id', $departmentId)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->get();

        if ($rows->isEmpty()) {
            return $rows;
        }

        $items = self::itemsOf($rows->pluck('id')->all())->groupBy('disposal_id');

        return $rows->map(function ($row) use ($items) {
            $row->items = $items->get($row->id, collect());
            $row->item_count = $row->items->count();
            $row->total_kg = $row->items->whereNotNull('amount_kg')->sum('amount_kg');
            $row->not_convertible = $row->items->whereNull('amount_kg')->count();
            $row->printable = in_array($row->app_status, ['approved', 'done'], true);
            $row->editable = $row->app_status === 'draft' && $row->status_id == 1;

            return $row;
        });
    }

    /**
     * Phiếu loại bỏ thuộc các đợt huỷ đã cho, kèm số lượng quy đổi sang kg.
     *
     * Quy đổi để cộng ra "Tổng khối lượng phế phẩm" ở mục 3 của biểu mẫu. Đơn vị nhóm
     * đếm (chai, thùng...) hoặc thiếu tỉ trọng d thì để trống thay vì hiện số sai.
     */
    private static function itemsOf(array $disposalIds)
    {
        $kgUnit = DB::table('units')->where('short_name', 'kg')->first();

        return DB::table(self::EXPORT_TABLE)
            ->leftJoin('chemical_imports', self::EXPORT_TABLE.'.import_id', '=', 'chemical_imports.id')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            // Đợt huỷ có thể gom phiếu do nhiều phòng lập, nên lấy đơn vị theo ĐÚNG phòng
            // đã ghi số lượng ở từng dòng, không phải một phòng cố định.
            ->tap(fn ($query) => DepartmentChemical::joinUnitOn(
                $query,
                self::EXPORT_TABLE.'.department_id',
                'chemical_imports.category_id'
            ))
            ->select(
                self::EXPORT_TABLE.'.id',
                self::EXPORT_TABLE.'.disposal_id',
                self::EXPORT_TABLE.'.code',
                self::EXPORT_TABLE.'.amount',
                self::EXPORT_TABLE.'.exported_date',
                self::EXPORT_TABLE.'.exported_by',
                self::EXPORT_TABLE.'.purpose',
                self::EXPORT_TABLE.'.test_report_no',
                self::EXPORT_TABLE.'.status_id',
                'chemical_categories.code as category_code',
                'chemical_categories.density',
                'chem_names.name as chem_name',
                'chemical_imports.batch_no',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'units.unit_group',
                'units.factor_to_base'
            )
            ->whereIn(self::EXPORT_TABLE.'.disposal_id', $disposalIds)
            ->orderBy(self::EXPORT_TABLE.'.id', 'asc')
            ->get()
            ->map(function ($row) use ($kgUnit) {
                $row->unit = $row->unit_short_name ?: $row->unit_name;

                $unit = (object) [
                    'unit_group' => $row->unit_group,
                    'factor_to_base' => $row->factor_to_base,
                ];
                $density = $row->density !== null ? (float) $row->density : null;

                $check = $kgUnit
                    ? UnitConverter::check($unit, $kgUnit, $density)
                    : ['ok' => false, 'reason' => 'Chưa có đơn vị "kg" trong Dữ Liệu Gốc nên không quy đổi được.'];

                $row->amount_kg = $check['ok']
                    ? UnitConverter::convert((float) $row->amount, $unit, $kgUnit, $density)
                    : null;
                $row->convert_note = $check['reason'];

                return $row;
            });
    }

    /**
     * BƯỚC 2 - Gom các phiếu loại bỏ đang chờ thành một đợt huỷ mới.
     *
     * Chỉ nhận phiếu của chính phòng ban đang đứng, đúng loại huỷ bỏ, còn hiệu lực và
     * chưa thuộc đợt nào. Người dùng gửi id lạ thì phiếu đó bị loại chứ không âm thầm
     * gom nhầm hàng của phòng khác.
     */
    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'export_ids' => ['required', 'array', 'min:1'],
            'export_ids.*' => ['integer'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'summarized_by' => ['nullable', 'max:255'],
            'summarized_at' => ['nullable', 'date'],
            'chemical_staff' => ['nullable', 'max:255'],
            'checked_at' => ['nullable', 'date'],
        ], [
            'export_ids.required' => 'Vui lòng chọn ít nhất một hoá chất chờ huỷ.',
            'export_ids.min' => 'Vui lòng chọn ít nhất một hoá chất chờ huỷ.',
            'period_month.required' => 'Vui lòng chọn tháng của đợt huỷ.',
            'period_month.min' => 'Tháng không hợp lệ.',
            'period_month.max' => 'Tháng không hợp lệ.',
            'period_year.required' => 'Vui lòng chọn năm của đợt huỷ.',
            'summarized_by.max' => 'Người tổng kết tối đa 255 ký tự.',
            'chemical_staff.max' => 'Nhân viên quản lý hoá chất tối đa 255 ký tự.',
        ]);

        // Lọc lại theo DB, không tin danh sách id gửi lên
        $ids = $this->pickWaiting($request->input('export_ids', []), $departmentId);

        $validator->after(function ($validator) use ($ids, $request) {
            if ($request->filled('export_ids') && ! $ids) {
                $validator->errors()->add(
                    'export_ids',
                    'Các phiếu được chọn không còn ở hàng chờ huỷ (đã gom vào đợt khác hoặc đã bị khoá). Vui lòng tải lại trang.'
                );
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'disposalErrors')->withInput();
        }

        $month = (int) $request->period_month;
        $year = (int) $request->period_year;
        $code = $this->nextCode($year, $month);

        DB::beginTransaction();

        try {
            $id = DB::table(self::TABLE)->insertGetId([
                'code' => $code,
                'department_id' => $departmentId,
                'period_month' => $month,
                'period_year' => $year,
                'summarized_by' => $this->nullIfBlank($request->summarized_by) ?: $this->actor(),
                'summarized_at' => $this->nullIfBlank($request->summarized_at) ?: now()->format('Y-m-d'),
                'chemical_staff' => $this->nullIfBlank($request->chemical_staff),
                'checked_at' => $this->nullIfBlank($request->checked_at),
                'app_status' => 'draft',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table(self::EXPORT_TABLE)->whereIn('id', $ids)->update([
                'disposal_id' => $id,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Không lập được '.self::LABEL.'. Vui lòng thử lại.');
        }

        AuditTrialController::log(
            'Thêm mới',
            self::TABLE,
            $id,
            'NA',
            'Lập '.self::LABEL.' '.$code.' gồm '.count($ids).' phiếu loại bỏ'
        );

        return redirect()->back()->with(
            'success',
            'Đã lập '.self::LABEL.' '.$code.' gồm '.count($ids).' phiếu. Bấm "Trình duyệt" để gửi TP. ĐBCL và Ban Giám Đốc.'
        );
    }

    /** Sửa phần đầu và mục 1 của đợt huỷ, chỉ khi đợt còn đang gom phiếu. */
    public function update(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        if ($blocked = $this->draftGuard($current, 'cập nhật')) {
            return $blocked;
        }

        $validator = Validator::make($request->all(), [
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'summarized_by' => ['nullable', 'max:255'],
            'summarized_at' => ['nullable', 'date'],
            'chemical_staff' => ['nullable', 'max:255'],
            'checked_at' => ['nullable', 'date'],
        ], [
            'period_month.required' => 'Vui lòng chọn tháng của đợt huỷ.',
            'period_year.required' => 'Vui lòng chọn năm của đợt huỷ.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'disposalUpdateErrors')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'period_month' => (int) $request->period_month,
            'period_year' => (int) $request->period_year,
            'summarized_by' => $this->nullIfBlank($request->summarized_by),
            'summarized_at' => $this->nullIfBlank($request->summarized_at),
            'chemical_staff' => $this->nullIfBlank($request->chemical_staff),
            'checked_at' => $this->nullIfBlank($request->checked_at),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, 'Sửa thông tin tổng kết phế phẩm');

        return redirect()->back()->with('success', 'Đã cập nhật '.self::LABEL.' '.$current->code.'!');
    }

    /** Thêm phiếu loại bỏ đang chờ vào một đợt còn đang gom. */
    public function addItems(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thêm phiếu!');
        }

        if ($blocked = $this->draftGuard($current, 'thêm phiếu vào')) {
            return $blocked;
        }

        $ids = $this->pickWaiting($request->input('export_ids', []), $current->department_id);

        if (! $ids) {
            return redirect()->back()->with('error', 'Không có phiếu nào hợp lệ để thêm vào '.self::LABEL.'.');
        }

        DB::table(self::EXPORT_TABLE)->whereIn('id', $ids)->update([
            'disposal_id' => $current->id,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, 'Thêm '.count($ids).' phiếu loại bỏ vào đợt');

        return redirect()->back()->with('success', 'Đã thêm '.count($ids).' phiếu vào '.self::LABEL.' '.$current->code.'!');
    }

    /**
     * Gỡ một phiếu loại bỏ khỏi đợt, phiếu quay lại hàng chờ huỷ.
     *
     * Chỉ gỡ được khi đợt còn đang gom; đã trình duyệt thì danh sách phải đứng yên
     * đúng như hồ sơ đã gửi đi.
     */
    public function removeItem(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.'!');
        }

        if ($blocked = $this->draftGuard($current, 'gỡ phiếu khỏi')) {
            return $blocked;
        }

        $export = DB::table(self::EXPORT_TABLE)
            ->where('id', $request->export_id)
            ->where('disposal_id', $current->id)
            ->first();

        if (! $export) {
            return redirect()->back()->with('error', 'Phiếu cần gỡ không thuộc '.self::LABEL.' này!');
        }

        DB::table(self::EXPORT_TABLE)->where('id', $export->id)->update([
            'disposal_id' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, 'Gỡ phiếu loại bỏ '.$export->code.' khỏi đợt');

        return redirect()->back()->with('success', 'Đã trả phiếu '.$export->code.' về hàng chờ huỷ!');
    }

    /**
     * Trình đợt huỷ lên TP. ĐBCL và Ban Giám Đốc.
     *
     * Từ lúc này danh sách phiếu bị đóng lại: hồ sơ đã gửi đi thì không thêm bớt được
     * nữa, muốn đổi phải để bên duyệt trả về (Không duyệt) rồi gom đợt mới.
     */
    public function submit(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần trình duyệt!');
        }

        if ($blocked = $this->draftGuard($current, 'trình duyệt')) {
            return $blocked;
        }

        $count = DB::table(self::EXPORT_TABLE)->where('disposal_id', $current->id)->count();

        if ($count < 1) {
            return redirect()->back()->with('error', 'Đợt huỷ '.$current->code.' chưa có phiếu nào nên chưa trình duyệt được.');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => 'pending',
            'submitted_by' => $this->actor(),
            'submitted_at' => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Trình duyệt', self::TABLE, $current->id, 'draft', 'pending - '.$count.' phiếu loại bỏ');

        return redirect()->back()->with(
            'success',
            'Đã trình '.self::LABEL.' '.$current->code.' ('.$count.' phiếu) lên TP. ĐBCL và Ban Giám Đốc.'
        );
    }

    /**
     * Ghi quyết định huỷ của TP. ĐBCL và Ban Giám Đốc.
     *
     * Duyệt -> approved, in được biểu mẫu. Không duyệt -> rejected và THẢ các phiếu về
     * hàng chờ huỷ để gom lại đợt khác, vì phế phẩm vẫn còn đó chưa huỷ được.
     */
    public function decide(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần ghi quyết định!');
        }

        if ($current->app_status !== 'pending' || $current->status_id != 1) {
            return redirect()->back()->with(
                'error',
                'Chỉ ghi quyết định cho đợt huỷ đang ở trạng thái "'.self::STATUSES['pending'].'". '
                .'Đợt '.$current->code.' đang là "'.(self::STATUSES[$current->app_status] ?? $current->app_status).'".'
            );
        }

        $approved = $request->app_status === 'approved';

        $validator = Validator::make($request->all(), [
            'app_status' => ['required', 'in:approved,rejected'],
            // Duyệt thì bắt buộc có số quyết định và đủ hai người ký, đó là căn cứ để in
            'decision_no' => [$approved ? 'required' : 'nullable', 'max:100'],
            'qa_approved_by' => [$approved ? 'required' : 'nullable', 'max:255'],
            'qa_approved_at' => [$approved ? 'required' : 'nullable', 'date'],
            'director_approved_by' => [$approved ? 'required' : 'nullable', 'max:255'],
            'director_approved_at' => [$approved ? 'required' : 'nullable', 'date'],
            'method' => ['nullable', 'in:'.implode(',', array_keys(self::METHODS))],
            'planned_time' => ['nullable', 'max:255'],
            'executor_type' => ['nullable', 'in:'.implode(',', array_keys(self::EXECUTORS))],
            'executor_other' => ['nullable', 'max:255'],
            'other_note' => ['nullable', 'max:500'],
            'reject_reason' => [$approved ? 'nullable' : 'required', 'max:500'],
        ], [
            'app_status.required' => 'Vui lòng chọn duyệt hoặc không duyệt.',
            'app_status.in' => 'Lựa chọn quyết định không hợp lệ.',
            'decision_no.required' => 'Vui lòng nhập số quyết định huỷ.',
            'qa_approved_by.required' => 'Vui lòng nhập TP. ĐBCL đã quyết định huỷ.',
            'qa_approved_at.required' => 'Vui lòng chọn ngày TP. ĐBCL quyết định.',
            'director_approved_by.required' => 'Vui lòng nhập người duyệt của Ban Giám Đốc.',
            'director_approved_at.required' => 'Vui lòng chọn ngày Ban Giám Đốc duyệt.',
            'reject_reason.required' => 'Không duyệt thì phải ghi lý do để phòng ban biết đường xử lý.',
            'method.in' => 'Phương pháp huỷ không hợp lệ.',
            'executor_type.in' => 'Bên thực hiện huỷ không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'decideErrors')->withInput();
        }

        DB::beginTransaction();

        try {
            DB::table(self::TABLE)->where('id', $current->id)->update([
                'app_status' => $approved ? 'approved' : 'rejected',
                'decision_no' => $approved ? $this->nullIfBlank($request->decision_no) : null,
                'other_note' => $this->nullIfBlank($request->other_note),
                'method' => $approved ? $this->nullIfBlank($request->method) : null,
                'planned_time' => $approved ? $this->nullIfBlank($request->planned_time) : null,
                'executor_type' => $approved ? $this->nullIfBlank($request->executor_type) : null,
                'executor_other' => $approved && $request->executor_type === 'other'
                    ? $this->nullIfBlank($request->executor_other)
                    : null,
                'qa_approved_by' => $approved ? $this->nullIfBlank($request->qa_approved_by) : null,
                'qa_approved_at' => $approved ? $this->nullIfBlank($request->qa_approved_at) : null,
                'director_approved_by' => $approved ? $this->nullIfBlank($request->director_approved_by) : null,
                'director_approved_at' => $approved ? $this->nullIfBlank($request->director_approved_at) : null,
                'reject_reason' => $approved ? null : $this->nullIfBlank($request->reject_reason),
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Không duyệt thì phế phẩm vẫn còn, thả phiếu về hàng chờ để gom đợt khác
            if (! $approved) {
                DB::table(self::EXPORT_TABLE)->where('disposal_id', $current->id)->update([
                    'disposal_id' => null,
                    'updated_by' => $this->actor(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Không ghi được quyết định huỷ. Vui lòng thử lại.');
        }

        AuditTrialController::log(
            $approved ? 'Duyệt huỷ' : 'Không duyệt huỷ',
            self::TABLE,
            $current->id,
            'pending',
            $approved
                ? 'approved - quyết định số '.$request->decision_no
                : 'rejected - '.$request->reject_reason
        );

        return redirect()->back()->with(
            'success',
            $approved
                ? 'Đã ghi quyết định huỷ cho đợt '.$current->code.'. Bấm nút In để in Phiếu Theo Dõi Và Quyết Định Huỷ.'
                : 'Đã ghi không duyệt đợt '.$current->code.'. Các phiếu đã được trả về hàng chờ huỷ.'
        );
    }

    /**
     * Hoàn tất đợt huỷ: ghi mục 3 (giao nhận phế phẩm) và mục 4 (ĐBCL theo dõi huỷ).
     *
     * Chỉ làm được sau khi đã có quyết định huỷ - chưa duyệt mà đã huỷ là làm sai quy trình.
     */
    public function complete(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần hoàn tất!');
        }

        if (! in_array($current->app_status, ['approved', 'done'], true) || $current->status_id != 1) {
            return redirect()->back()->with(
                'error',
                'Chỉ ghi giao nhận và theo dõi huỷ sau khi đợt '.$current->code.' đã có quyết định huỷ.'
            );
        }

        $validator = Validator::make($request->all(), [
            'solid_weight' => ['nullable', 'numeric', 'min:0'],
            'liquid_weight' => ['nullable', 'numeric', 'min:0'],
            'handover_date' => ['nullable', 'date'],
            'handover_by' => ['nullable', 'max:255'],
            'receive_date' => ['nullable', 'date'],
            'receive_by' => ['nullable', 'max:255'],
            'label_date' => ['nullable', 'date'],
            'label_by' => ['nullable', 'max:255'],
            // Đánh dấu đã huỷ xong thì phải nói rõ huỷ ngày nào, ai xác nhận
            'destroy_date' => ['nullable', 'date'],
            'destroy_by' => ['nullable', 'max:255'],
        ], [
            'solid_weight.numeric' => 'Khối lượng phế phẩm rắn phải là số.',
            'liquid_weight.numeric' => 'Khối lượng phế phẩm lỏng phải là số.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('destroy_date') && ! $request->filled('destroy_by')) {
                $validator->errors()->add('destroy_by', 'Đã tiến hành huỷ thì phải ghi người xác nhận.');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'completeErrors')->withInput();
        }

        // Đủ ngày huỷ và người xác nhận mới coi là huỷ xong
        $done = $request->filled('destroy_date') && $request->filled('destroy_by');

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'solid_weight' => $request->filled('solid_weight') ? (float) $request->solid_weight : null,
            'liquid_weight' => $request->filled('liquid_weight') ? (float) $request->liquid_weight : null,
            'handover_date' => $this->nullIfBlank($request->handover_date),
            'handover_by' => $this->nullIfBlank($request->handover_by),
            'receive_date' => $this->nullIfBlank($request->receive_date),
            'receive_by' => $this->nullIfBlank($request->receive_by),
            'label_date' => $this->nullIfBlank($request->label_date),
            'label_by' => $this->nullIfBlank($request->label_by),
            'destroy_date' => $this->nullIfBlank($request->destroy_date),
            'destroy_by' => $this->nullIfBlank($request->destroy_by),
            'app_status' => $done ? 'done' : 'approved',
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            $done ? 'Huỷ xong' : 'Cập nhật',
            self::TABLE,
            $current->id,
            $current->app_status,
            $done ? 'done - huỷ ngày '.$request->destroy_date : 'Ghi giao nhận phế phẩm'
        );

        return redirect()->back()->with(
            'success',
            $done
                ? 'Đã ghi nhận huỷ xong đợt '.$current->code.'.'
                : 'Đã lưu thông tin giao nhận phế phẩm của đợt '.$current->code.'.'
        );
    }

    /**
     * Khoá / mở khoá một đợt huỷ.
     *
     * Chỉ khoá được đợt CHƯA trình duyệt hoặc đã bị trả về - đợt đã có quyết định huỷ
     * là hồ sơ chất lượng đã ký, không được xoá bỏ dấu vết. Khoá thì thả các phiếu về
     * hàng chờ để gom lại đợt khác.
     */
    public function deActive(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
        }

        if (! in_array($current->app_status, ['draft', 'rejected'], true)) {
            return redirect()->back()->with(
                'error',
                'Đợt huỷ '.$current->code.' đã trình duyệt nên không khoá được. '
                .'Đợt đã trình là hồ sơ chất lượng, chỉ bên duyệt mới trả về được.'
            );
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        DB::beginTransaction();

        try {
            DB::table(self::TABLE)->where('id', $current->id)->update([
                'status_id' => $newStatus,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            if ($newStatus == 0) {
                DB::table(self::EXPORT_TABLE)->where('disposal_id', $current->id)->update([
                    'disposal_id' => null,
                    'updated_by' => $this->actor(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Không đổi được trạng thái '.self::LABEL.'. Vui lòng thử lại.');
        }

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            self::TABLE,
            $current->id,
            'status_id: '.$current->status_id,
            'status_id: '.$newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' '.$current->code
            .($newStatus == 0 ? '. Các phiếu đã trả về hàng chờ huỷ.' : '.')
        );
    }

    /**
     * IN PHIẾU THEO DÕI VÀ QUYẾT ĐỊNH HUỶ (biểu mẫu QA/F/058-07).
     *
     * Chỉ in được đợt đã có quyết định huỷ. Trang in là một trang A4 độc lập, người
     * dùng bấm In của trình duyệt rồi chọn "Lưu thành PDF" để có bản PDF.
     */
    public function print(Request $request)
    {
        $current = $this->find($request->id);

        if (! $current) {
            abort(404, 'Không tìm thấy đợt huỷ hoá chất.');
        }

        if (! in_array($current->app_status, ['approved', 'done'], true)) {
            abort(403, 'Đợt huỷ '.$current->code.' chưa có quyết định huỷ nên chưa in được biểu mẫu.');
        }

        $items = self::itemsOf([$current->id]);

        $department = DB::table('deparments')->where('id', $current->department_id)->first();

        return view('pages.export.ChemicalDisposal.print', [
            'disposal' => $current,
            'items' => $items,
            'department' => $department,
            'form' => self::FORM,
            'methods' => self::METHODS,
            'executors' => self::EXECUTORS,
            'totalKg' => $items->whereNotNull('amount_kg')->sum('amount_kg'),
            'notConvertible' => $items->whereNull('amount_kg')->count(),
        ]);
    }

    /**
     * Lọc danh sách id gửi lên, chỉ giữ phiếu THẬT SỰ đang chờ huỷ của phòng ban.
     *
     * @return array<int, int>
     */
    private function pickWaiting($ids, int $departmentId): array
    {
        $ids = collect(is_array($ids) ? $ids : [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->all();

        if (! $ids) {
            return [];
        }

        return DB::table(self::EXPORT_TABLE)
            ->whereIn('id', $ids)
            ->where('department_id', $departmentId)
            ->where('type', self::TYPE_CANCEL)
            ->where('status_id', 1)
            ->whereNull('disposal_id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Đợt huỷ của chính phòng ban đang đứng. */
    private function find($id)
    {
        return DB::table(self::TABLE)
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    /**
     * Chặn mọi thao tác sửa danh sách phiếu khi đợt đã rời trạng thái "đang gom".
     *
     * @return \Illuminate\Http\RedirectResponse|null null nghĩa là được phép đi tiếp
     */
    private function draftGuard($current, string $action)
    {
        if ($current->app_status === 'draft' && $current->status_id == 1) {
            return null;
        }

        return redirect()->back()->with(
            'error',
            'Không '.$action.' được đợt huỷ '.$current->code.': đợt đang ở trạng thái "'
            .(self::STATUSES[$current->app_status] ?? $current->app_status).'"'
            .($current->status_id == 1 ? '' : ' và đã bị khoá').'. '
            .'Chỉ đợt đang gom phiếu mới sửa được.'
        );
    }

    /**
     * Số phiếu theo dõi của đợt: HUY-YYYYMM-NN, đánh số lại từ 01 mỗi tháng.
     *
     * Lấy số lớn nhất đang có của tháng đó rồi cộng 1, không dựa vào số lượng bản ghi
     * để đợt bị khoá cũng không làm trùng số.
     */
    private function nextCode(int $year, int $month): string
    {
        $prefix = 'HUY-'.$year.str_pad((string) $month, 2, '0', STR_PAD_LEFT).'-';

        $next = DB::table(self::TABLE)
            ->where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix)))
            ->max();

        return $prefix.str_pad((string) (($next ?? 0) + 1), 2, '0', STR_PAD_LEFT);
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
