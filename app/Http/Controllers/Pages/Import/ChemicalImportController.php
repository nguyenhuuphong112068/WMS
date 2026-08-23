<?php

namespace App\Http\Controllers\Pages\Import;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\UnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * NHẬP - NHẬP HOÁ CHẤT
 *
 * Ghi nhận từng lần nhập hoá chất vào kho của phòng ban đang chọn.
 * Phiếu nhập chỉ khoá (deActive) chứ không xoá cứng, để mã xuất nhập không bị cấp lại.
 */
class ChemicalImportController extends Controller
{
    private const TABLE = 'imports';

    private const HISTORY_TABLE = 'import_histories';

    private const LABEL = 'phiếu nhập hoá chất';

    /** Số thứ tự trong mã xuất nhập: 8 chữ số, bắt đầu từ 00000001. */
    private const SEQ_LENGTH = 8;

    /**
     * Hậu tố cho lô NHẬN TỪ PHÒNG BAN KHÁC.
     *
     * Hàng chuyển kho không nhập từ ngoài vào nên mã phải phân biệt được với mã nhập
     * thường, đồng thời giữ nguyên mã của phòng nhập ĐẦU TIÊN để truy ngược nguồn gốc:
     *
     *      61600000001            <- phòng nhập đầu (mua ngoài)
     *      61600000001-CK01       <- chuyển lần 1
     *      61600000001-CK02       <- chuyển lần 2 (kể cả chuyển tiếp từ 61600000001-CK01)
     *
     * Số thứ tự đếm theo MÃ GỐC trên toàn hệ thống, không đếm theo phòng, vì
     * imports.code là duy nhất.
     */
    private const TRANSFER_MARK = '-CK';

    /** Số thứ tự sau -CK: 2 chữ số, vượt 99 thì tự dài ra (CK100). */
    private const TRANSFER_SEQ_LENGTH = 2;

    /** Sai số cho phép khi so số lượng với nhau (cột decimal 15,4). */
    private const EPSILON = 0.00005;

    /**
     * Các trường được theo dõi khi điều chỉnh: cột => tên hiển thị trong lịch sử.
     * Thêm cột mới vào form nhớ khai ở đây, nếu không lịch sử sẽ không ghi lại thay đổi đó.
     */
    private const FIELDS = [
        'category_id' => 'Hoá chất',
        'amount' => 'Số lượng',
        'imported_date' => 'Ngày nhập',
        'invoice_number' => 'Số hoá đơn',
        'invoice_date' => 'Ngày hoá đơn',
        'expired_date' => 'Hạn sử dụng',
        'is_microbiological_chemicals' => 'Hoá chất vi sinh',
        'batch_no' => 'Số lô',
        'supplier_id' => 'Nhà cung cấp',
        'location_id' => 'Vị trí lưu trữ',
        'note' => 'Ghi chú',
    ];

    /**
     * NHẬN HOÁ CHẤT TỪ PHÒNG BAN KHÁC.
     *
     * Phòng gửi lập phiếu chuyển ở màn hình Sử Dụng Hoá Chất (exports.type = 'transfer'),
     * hàng nằm ở trạng thái chờ nhận. Phòng nhận vào đây bấm Nhận và khai các thông tin
     * riêng của kho mình - chủ yếu là ĐỊNH KHU (có thể để trống) - lúc đó mới sinh một
     * dòng imports mới và cộng vào tồn.
     *
     * Thông tin bản chất của lô (hoá chất, số lô, hạn dùng nhà sản xuất, hoá chất vi sinh,
     * nhà cung cấp gốc) chép nguyên từ lô của phòng gửi, không cho sửa lúc nhận - sửa thì
     * hai phòng nói về hai thứ khác nhau. Số hoá đơn / ngày hoá đơn để trống vì không mua
     * từ ngoài. Hạn dùng nội bộ để trống, phòng nhận tự xác định ở màn hình Tồn Kho.
     */
    public function receive(Request $request)
    {
        $departmentId = $this->departmentId();

        $transfer = $this->pendingTransfer($request->export_id, $departmentId);

        if (! $transfer) {
            return redirect()->back()->with(
                'error',
                'Không tìm thấy lô hoá chất chờ nhận này, có thể phòng gửi đã khoá phiếu chuyển hoặc lô đã được nhận rồi!'
            );
        }

        $validator = Validator::make($request->all(), [
            'export_id' => ['required'],
            'imported_date' => ['required', 'date'],
            // Định khu để trống được, nhưng đã chọn thì phải là định khu của chính phòng này
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'note' => ['nullable', 'max:500'],
        ], [
            'imported_date.required' => 'Vui lòng chọn ngày nhận hàng.',
            'imported_date.date' => 'Ngày nhận hàng không hợp lệ.',
            'location_id.exists' => 'Định khu được chọn không thuộc phòng ban này hoặc đã ngừng sử dụng.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'receiveErrors')->withInput();
        }

        // Sinh mã và ghi cả hai bảng trong một transaction: nhận hai lần cùng lúc
        // thì không được ra hai mã, cũng không được có lô mà phiếu chuyển vẫn "chờ nhận"
        $result = DB::transaction(function () use ($request, $transfer, $departmentId) {
            $code = $this->nextTransferCode($transfer->source_code);

            $id = DB::table(self::TABLE)->insertGetId([
                'code' => $code,
                'department_id' => $departmentId,
                'source_export_id' => $transfer->id,
                // Nhận lẻ (ít hơn cả lô) thì số lượng đã chốt: không xuất vượt, không cân đối
                'is_partial_lot' => $transfer->is_partial,
                // Chép nguyên bản chất của lô từ phòng gửi
                'category_id' => $transfer->category_id,
                'amount' => $transfer->amount,
                'batch_no' => $transfer->batch_no,
                'expired_date' => $transfer->expired_date,
                // Nhận lẻ thì lô nguồn đã mở nên đã có hạn dùng nội bộ -> kế thừa, khỏi xác định lại.
                // Nhận nguyên thì lô chưa mở, để trống cho phòng nhận tự xác định.
                'internal_expired_date' => $transfer->is_partial ? $transfer->source_internal_expired_date : null,
                'is_microbiological_chemicals' => $transfer->is_microbiological_chemicals,
                'supplier_id' => $transfer->supplier_id,
                // Thông tin riêng của phòng nhận
                'imported_date' => $request->imported_date,
                'imported_by' => $this->actor(),
                'location_id' => $request->location_id ? (int) $request->location_id : null,
                'note' => $this->nullIfBlank($request->note),
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('exports')->where('id', $transfer->id)->update([
                'received_import_id' => $id,
                'received_at' => now(),
                'received_by' => $this->actor(),
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->writeHistory(
                $id,
                'Nhận chuyển kho',
                'Nhận từ phòng ban '.$transfer->from_department_name.', mã gốc '.$transfer->source_code.' -> mã mới '.$code.'.'
            );

            return ['id' => $id, 'code' => $code];
        });

        AuditTrialController::log(
            'Nhận chuyển kho',
            self::TABLE,
            $result['id'],
            'Phiếu chuyển: '.$transfer->source_code.' từ '.$transfer->from_department_name,
            'Mã mới: '.$result['code']
        );

        return redirect()->back()->with(
            'success',
            'Đã nhận hoá chất từ phòng ban '.$transfer->from_department_name.', mã lô tại kho phòng mình là '.$result['code'].'!'
        );
    }

    /**
     * TỪ CHỐI NHẬN lô hoá chất phòng ban khác chuyển sang.
     *
     * Dùng khi hàng về không đúng: thiếu số lượng, sai hoá chất, bao bì hỏng...
     * Từ chối sẽ khoá phiếu chuyển nên số lượng được trả lại tồn của phòng gửi ngay.
     * Phiếu đã từ chối không mở lại được - phòng gửi phải lập phiếu chuyển mới.
     *
     * Chỉ từ chối được lô CHƯA NHẬN. Nhận rồi thì phiếu khoá hoàn toàn, muốn trả hàng
     * phải lập phiếu chuyển ngược lại cho phòng kia.
     */
    public function rejectTransfer(Request $request)
    {
        $departmentId = $this->departmentId();

        $transfer = $this->pendingTransfer($request->export_id, $departmentId);

        if (! $transfer) {
            return redirect()->back()->with(
                'error',
                'Không tìm thấy lô hoá chất chờ nhận này, có thể lô đã được nhận hoặc đã bị từ chối rồi!'
            );
        }

        $validator = Validator::make($request->all(), [
            'export_id' => ['required'],
            'reject_reason' => ['required', 'max:500'],
        ], [
            'reject_reason.required' => 'Vui lòng nhập lý do từ chối nhận.',
            'reject_reason.max' => 'Lý do từ chối tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'rejectErrors')->withInput();
        }

        $reason = trim($request->reject_reason);

        DB::transaction(function () use ($transfer, $reason) {
            DB::table('exports')->where('id', $transfer->id)->update([
                // Khoá phiếu chuyển -> số lượng quay lại tồn của phòng gửi
                'status_id' => 0,
                'rejected_at' => now(),
                'rejected_by' => $this->actor(),
                'reject_reason' => $reason,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Ghi thẳng vào lịch sử của phiếu chuyển bên màn hình Sử Dụng, để phòng gửi
            // mở badge lịch sử là thấy ngay vì sao hàng bị trả về
            $export = DB::table('exports')->where('id', $transfer->id)->first();

            DB::table('export_histories')->insert([
                'export_id' => $export->id,
                'action' => 'Từ chối nhận',
                'code' => $export->code,
                'import_id' => $export->import_id,
                'amount' => $export->amount,
                'type' => $export->type,
                'to_department_id' => $export->to_department_id,
                'exported_date' => $export->exported_date,
                'exported_by' => $export->exported_by,
                'purpose' => $export->purpose,
                'checked_by' => $export->checked_by,
                'status_id' => $export->status_id,
                'change_note' => 'Phòng nhận từ chối nhận hàng. Lý do: '.$reason
                    .' | Số lượng đã được trả lại tồn của phòng gửi.',
                'created_by' => $this->actor(),
                'created_at' => now(),
            ]);
        });

        AuditTrialController::log(
            'Từ chối nhận',
            'exports',
            $transfer->id,
            'Chờ nhận từ '.$transfer->from_department_name,
            'Từ chối. Lý do: '.$reason
        );

        return redirect()->back()->with(
            'success',
            'Đã từ chối nhận lô '.$transfer->source_code.' từ phòng ban '.$transfer->from_department_name
            .'. Số lượng đã được trả lại tồn của phòng gửi.'
        );
    }

    /**
     * Một lô đang chờ phòng ban hiện tại nhận, hoặc null nếu không có / đã nhận rồi.
     *
     * Điều kiện: phiếu chuyển còn hiệu lực, đúng phòng nhận, và chưa sinh lô nào
     * (received_import_id còn trống).
     */
    private function pendingTransfer($exportId, int $departmentId)
    {
        return $this->markTransferKind(
            $this->pendingTransferQuery($departmentId)->where('exports.id', $exportId)->first()
        );
    }

    /** Danh sách lô đang chờ phòng ban hiện tại nhận. */
    private function pendingTransfers(int $departmentId)
    {
        return $this->pendingTransferQuery($departmentId)
            ->orderBy('exports.exported_date', 'desc')
            ->orderBy('exports.id', 'desc')
            ->get()
            ->map(fn ($row) => $this->markTransferKind($row));
    }

    private function pendingTransferQuery(int $departmentId)
    {
        return DB::table('exports')
            ->join('imports as source', 'exports.import_id', '=', 'source.id')
            ->leftJoin('chemical_categories', 'source.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'chemical_categories.unit_id', '=', 'units.id')
            ->leftJoin('deparments', 'exports.department_id', '=', 'deparments.id')
            ->select(
                'exports.id',
                'exports.amount',
                'exports.exported_date',
                'exports.exported_by',
                'exports.purpose',
                'exports.checked_by',
                'source.code as source_code',
                'source.category_id',
                'source.amount as source_amount',
                'source.batch_no',
                'source.expired_date',
                'source.internal_expired_date as source_internal_expired_date',
                'source.is_microbiological_chemicals',
                'source.supplier_id',
                'chemical_categories.code as category_code',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'deparments.name as from_department_name',
                'deparments.shortName as from_department_short'
            )
            // Lô nguồn đã cân đối lần nào chưa - đã cân đối thì không còn "nguyên"
            ->selectSub(
                DB::table('inventory_balancings')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('inventory_balancings.import_id', 'source.id')
                    ->where('inventory_balancings.status_id', 1),
                'source_balancing_times'
            )
            // Lô nguồn đã xuất ra lần nào khác chưa (không tính chính phiếu chuyển này)
            ->selectSub(
                DB::table('exports as other')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('other.import_id', 'source.id')
                    ->whereColumn('other.id', '<>', 'exports.id')
                    ->where('other.status_id', 1),
                'source_other_exports'
            )
            ->where('exports.type', 'transfer')
            ->where('exports.status_id', 1)
            ->where('exports.to_department_id', $departmentId)
            ->whereNull('exports.received_import_id')
            ->whereNull('exports.rejected_at');
    }

    /**
     * Gắn thêm cờ nhận nguyên / nhận lẻ cho một dòng hàng chờ nhận.
     *
     * NHẬN NGUYÊN : lô còn y nguyên như lúc phòng gửi nhập vào và chuyển đi trọn vẹn.
     *               Phải đủ ba điều kiện: lượng chuyển đúng bằng LƯỢNG NHẬP GỐC
     *               (imports.amount, KHÔNG cộng cân đối), lô chưa cân đối lần nào, và
     *               lô chưa xuất ra lần nào khác. Lô chưa bị mở nên vẫn còn hao hụt cân
     *               đong -> phòng nhận được xuất vượt 5% và được cân đối, nhưng phải tự
     *               xác định hạn dùng nội bộ.
     * NHẬN LẺ     : thiếu bất kỳ điều kiện nào ở trên. Phòng gửi đã đụng vào lô rồi nên
     *               số lượng là con số đã chốt, kế thừa luôn hạn dùng nội bộ của lô nguồn.
     */
    private function markTransferKind($row)
    {
        if (! $row) {
            return $row;
        }

        $row->full_lot_amount = (float) $row->source_amount;

        $row->is_partial = abs((float) $row->amount - (float) $row->source_amount) > self::EPSILON
            || (int) $row->source_balancing_times > 0
            || (int) $row->source_other_exports > 0;

        return $row;
    }

    /**
     * Mã kế tiếp cho lô nhận từ phòng khác: <mã gốc>-CK<số thứ tự>.
     *
     * Mã gốc là mã của phòng nhập ĐẦU TIÊN, tức phần đứng trước "-CK" nếu lô đang
     * chuyển vốn cũng là hàng chuyển kho. Nhờ vậy chuyển qua bao nhiêu phòng thì mã
     * vẫn quy về đúng một gốc, không nối chồng "-CK01-CK02".
     */
    private function nextTransferCode(string $sourceCode): string
    {
        $root = explode(self::TRANSFER_MARK, $sourceCode, 2)[0];
        $prefix = $root.self::TRANSFER_MARK;

        $next = DB::table(self::TABLE)
            ->where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix)))
            ->max();

        return $prefix.str_pad((string) (($next ?? 0) + 1), self::TRANSFER_SEQ_LENGTH, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('chemical_categories', self::TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'chemical_categories.unit_id', '=', 'units.id')
            ->leftJoin('suppliers', self::TABLE.'.supplier_id', '=', 'suppliers.id')
            // Định khu của lô: locations giữ sẵn id 3 cấp trên nên join tiếp là ra đủ đường dẫn
            ->leftJoin('locations', self::TABLE.'.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            // Lô nhận từ phòng ban khác: truy ngược phiếu chuyển để biết nhận của phòng nào
            ->leftJoin('exports as source_export', self::TABLE.'.source_export_id', '=', 'source_export.id')
            ->leftJoin('deparments as from_dept', 'source_export.department_id', '=', 'from_dept.id')
            ->select(
                self::TABLE.'.*',
                'source_export.code as source_code',
                'source_export.exported_date as transferred_date',
                'from_dept.name as from_department_name',
                'from_dept.shortName as from_department_short',
                'chemical_categories.code as category_code',
                'chemical_categories.classification',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'suppliers.name as supplier_name',
                'suppliers.address as supplier_address',
                'locations.name as location_name',
                'locations.code as location_code',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy(self::TABLE.'.imported_date', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        session()->put(['title' => 'NHẬP - NHẬP HOÁ CHẤT']);

        $categories = $this->categoryOptions();

        [$from, $to] = $this->reportRange($request);

        return view('pages.import.ChemicalImport.list', [
            'datas' => $datas,
            'categories' => $categories,
            'suppliers' => $this->supplierOptions(),
            'locations' => $this->locationOptions($departmentId),
            'codePreviews' => $this->codePreviews($departmentId, $categories),
            'report' => $this->importReport($departmentId, $from, $to),
            'reportFrom' => $from,
            'reportTo' => $to,
            // Hàng phòng ban khác chuyển sang, đang chờ phòng mình nhận
            'pendingTransfers' => $this->pendingTransfers($departmentId),
            // Lọc xong thì trang tải lại, quay về đúng tab báo cáo thay vì tab sổ
            'activeTab' => in_array($request->input('tab'), ['report', 'transfer'], true)
                ? $request->input('tab')
                : 'book',
        ]);
    }

    /**
     * Khoảng thời gian của báo cáo, mặc định từ đầu tháng đến hôm nay.
     * Nhập ngược (từ > đến) thì tự đảo lại cho đúng thứ tự.
     */
    private function reportRange(Request $request): array
    {
        $parse = function ($value, $fallback) {
            try {
                return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : $fallback;
            } catch (\Exception $e) {
                return $fallback;
            }
        };

        $from = $parse($request->input('from'), now()->startOfMonth()->format('Y-m-d'));
        $to = $parse($request->input('to'), now()->format('Y-m-d'));

        return $from <= $to ? [$from, $to] : [$to, $from];
    }

    /**
     * BÁO CÁO NHẬP HOÁ CHẤT THEO KHOẢNG THỜI GIAN.
     *
     * Cộng dồn các phiếu nhập còn hiệu lực trong khoảng ngày, gom theo
     * chemical_categories.code (mã danh mục hoá chất). Mỗi dòng có:
     * - Số lượng theo ĐƠN VỊ GỐC của danh mục (chemical_categories.unit_id)
     * - Số lượng QUY ĐỔI SANG KG qua App\Support\UnitConverter
     *
     * Đơn vị nhóm đếm (chai, thùng...) không quy đổi tự động được, và đổi thể tích
     * sang khối lượng thì cần tỉ trọng d (g/ml) của hoá chất - thiếu thì để trống
     * kèm lý do thay vì hiện số sai.
     */
    private function importReport(int $departmentId, string $from, string $to)
    {
        $kgUnit = DB::table('units')->where('short_name', 'kg')->first();

        // Chuỗi trong DB::raw là hằng, không ghép từ dữ liệu người dùng
        $rows = DB::table(self::TABLE)
            ->join('chemical_categories', self::TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'chemical_categories.unit_id', '=', 'units.id')
            ->select(
                'chemical_categories.id as category_id',
                'chemical_categories.code as category_code',
                'chemical_categories.density',
                'chemical_categories.classification',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'units.unit_group',
                'units.factor_to_base',
                DB::raw('SUM('.self::TABLE.'.amount) as total'),
                DB::raw('COUNT(*) as times'),
                DB::raw('COUNT(DISTINCT '.self::TABLE.'.supplier_id) as supplier_count'),
                DB::raw('MIN('.self::TABLE.'.imported_date) as first_imported_date'),
                DB::raw('MAX('.self::TABLE.'.imported_date) as last_imported_date')
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->whereBetween(self::TABLE.'.imported_date', [$from, $to])
            // Gom đủ mọi cột không phải hàm tổng, tránh lỗi ONLY_FULL_GROUP_BY của MySQL
            ->groupBy(
                'chemical_categories.id',
                'chemical_categories.code',
                'chemical_categories.density',
                'chemical_categories.classification',
                'chem_names.name',
                'units.short_name',
                'units.name',
                'units.unit_group',
                'units.factor_to_base'
            )
            ->orderBy('chemical_categories.code', 'asc')
            ->get();

        return $rows->map(function ($row) use ($kgUnit) {
            $unit = (object) [
                'unit_group' => $row->unit_group,
                'factor_to_base' => $row->factor_to_base,
            ];
            $density = $row->density !== null ? (float) $row->density : null;

            $row->total = (float) $row->total;
            $row->unit = $row->unit_short_name ?: $row->unit_name;

            $check = $kgUnit
                ? UnitConverter::check($unit, $kgUnit, $density)
                : ['ok' => false, 'reason' => 'Chưa có đơn vị "kg" trong Dữ Liệu Gốc nên không quy đổi được.'];

            $row->convertible = $check['ok'];
            $row->convert_note = $check['reason'];

            $row->total_kg = $check['ok'] && $kgUnit
                ? UnitConverter::convert($row->total, $unit, $kgUnit, $density)
                : null;

            return $row;
        });
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules($this->departmentId()), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $departmentId = $this->departmentId();

        // Sinh mã và ghi bản ghi trong cùng một transaction để hai người nhập cùng lúc không trùng mã
        $result = DB::transaction(function () use ($request, $departmentId) {
            $code = $this->nextCode($departmentId, (int) $request->category_id);

            $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
                'code' => $code,
                'department_id' => $departmentId,
                // Người nhập luôn là người đang đăng nhập, không nhận giá trị từ form
                'imported_by' => $this->actor(),
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Mốc đầu tiên của lịch sử: giá trị phiếu lúc mới tạo
            $this->writeHistory($id, 'Thêm mới', 'Tạo mới phiếu nhập, mã xuất nhập '.$code.'.');

            return ['id' => $id, 'code' => $code];
        });

        AuditTrialController::log('Thêm mới', self::TABLE, $result['id'], 'NA', 'Nhập hoá chất, mã xuất nhập: '.$result['code']);

        return redirect()->back()->with('success', 'Đã tạo '.self::LABEL.' mã '.$result['code'].'!');
    }

    /**
     * ĐIỀU CHỈNH PHIẾU NHẬP - sửa thông tin nhập và ghi lại một dòng lịch sử.
     *
     * Mỗi lần điều chỉnh bắt buộc nhập LÝ DO, và ghi vào import_histories một dòng gồm:
     * ảnh chụp phiếu sau khi sửa, nội dung đã đổi ("Trường: cũ -> mới") và lý do.
     * Không sửa lại dòng lịch sử cũ - điều chỉnh sai thì điều chỉnh tiếp lần nữa.
     *
     * Không đổi gì mà vẫn bấm Lưu thì không ghi lịch sử, tránh rác.
     */
    public function update(Request $request)
    {
        $departmentId = $this->departmentId();

        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần điều chỉnh!');
        }

        $rules = $this->rules($departmentId) + [
            'reason' => ['required', 'max:500'],
        ];

        $messages = $this->messages() + [
            'reason.required' => 'Vui lòng nhập lý do điều chỉnh.',
            'reason.max' => 'Lý do điều chỉnh tối đa 500 ký tự.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = $this->changeNote($current, $payload);

        if ($note === '') {
            return redirect()->back()->with('error', 'Không có thông tin nào thay đổi nên chưa ghi nhận điều chỉnh.');
        }

        // Đổi hoá chất thì mã xuất nhập phải sinh lại vì mã gắn với danh mục hoá chất
        if ((int) $payload['category_id'] !== (int) $current->category_id) {
            $payload['code'] = $this->nextCode((int) $current->department_id, (int) $payload['category_id']);
            $note .= ' | Mã xuất nhập: '.$current->code.' -> '.$payload['code'];
        }

        $reason = trim((string) $request->reason);

        DB::transaction(function () use ($current, $payload, $note, $reason) {
            DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->writeHistory((int) $current->id, 'Điều chỉnh', $note, $reason);
        });

        AuditTrialController::log(
            'Điều chỉnh',
            self::TABLE,
            $current->id,
            $current->code,
            ($payload['code'] ?? $current->code).' | '.$note.' | Lý do: '.$reason
        );

        return redirect()->back()->with('success', 'Đã ghi nhận điều chỉnh '.self::LABEL.' '.($payload['code'] ?? $current->code).'!');
    }

    /** Lịch sử điều chỉnh của một phiếu nhập, trả JSON cho modal trên bảng. */
    public function history(Request $request)
    {
        // Chỉ cho xem lịch sử phiếu của phòng ban đang chọn
        $import = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $import) {
            return response()->json(['rows' => []]);
        }

        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('chemical_categories', self::HISTORY_TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'chemical_categories.unit_id', '=', 'units.id')
            ->leftJoin('suppliers', self::HISTORY_TABLE.'.supplier_id', '=', 'suppliers.id')
            ->leftJoin('locations', self::HISTORY_TABLE.'.location_id', '=', 'locations.id')
            ->select(
                self::HISTORY_TABLE.'.*',
                'chemical_categories.code as category_code',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'suppliers.name as supplier_name',
                'locations.name as location_name'
            )
            ->where(self::HISTORY_TABLE.'.import_id', $import->id)
            ->orderBy(self::HISTORY_TABLE.'.id', 'desc')
            ->get();

        $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

        return response()->json([
            'rows' => $rows->map(fn ($row) => [
                'action' => $row->action,
                'change_note' => $row->change_note,
                'reason' => $row->reason,
                'created_by' => $row->created_by ?: 'NA',
                'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                'snapshot' => [
                    'Mã xuất nhập' => $row->code ?: '—',
                    'Hoá chất' => trim(($row->category_code ?: '').' '.($row->chem_name ?: '')) ?: '—',
                    'Số lượng' => $this->number((float) $row->amount).' '.($row->unit_short_name ?: $row->unit_name ?: ''),
                    'Số lô' => $row->batch_no ?: '—',
                    'Ngày nhập' => $date($row->imported_date),
                    'Hạn sử dụng' => $date($row->expired_date),
                    'Hạn nội bộ' => $date($row->internal_expired_date),
                    'Nhà cung cấp' => $row->supplier_name ?: '—',
                    'Vị trí lưu trữ' => $row->location_name ?: '—',
                    'Hoá đơn' => $row->invoice_number ? $row->invoice_number.' ('.$date($row->invoice_date).')' : '—',
                    'Hoá chất vi sinh' => $row->is_microbiological_chemicals ? 'Có' : 'Không',
                    'Trạng thái' => $row->status_id == 1 ? 'Hiệu lực' : 'Đã khoá',
                    'Ghi chú' => $row->note ?: '—',
                ],
            ]),
        ]);
    }

    /**
     * Ghi một dòng lịch sử, chụp lại giá trị phiếu NGAY SAU khi thay đổi.
     * Gọi sau khi đã ghi xong bảng imports.
     */
    private function writeHistory(int $id, string $action, ?string $note, ?string $reason = null): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'import_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'category_id' => $row->category_id,
            'amount' => $row->amount,
            'imported_date' => $row->imported_date,
            'imported_by' => $row->imported_by,
            'invoice_number' => $row->invoice_number,
            'invoice_date' => $row->invoice_date,
            'expired_date' => $row->expired_date,
            'internal_expired_date' => $row->internal_expired_date,
            'is_microbiological_chemicals' => $row->is_microbiological_chemicals,
            'batch_no' => $row->batch_no,
            'supplier_id' => $row->supplier_id,
            'location_id' => $row->location_id,
            'note' => $row->note,
            'status_id' => $row->status_id,
            'change_note' => $note,
            'reason' => $reason,
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    /**
     * Mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới".
     * Trả về chuỗi rỗng nghĩa là không có gì thay đổi.
     */
    private function changeNote($current, array $payload): string
    {
        $labels = $this->labelMaps();
        $parts = [];

        foreach (self::FIELDS as $field => $title) {
            $old = $current->$field;
            $new = $payload[$field];

            // Số lượng: DB trả 10.0000 còn form gửi 10, phải so theo giá trị số
            if ($field === 'amount') {
                if (abs((float) $old - (float) $new) < 0.00005) {
                    continue;
                }

                $parts[] = $title.': '.$this->number((float) $old).' -> '.$this->number((float) $new);

                continue;
            }

            if ($field === 'is_microbiological_chemicals') {
                if ((bool) $old === (bool) $new) {
                    continue;
                }

                $parts[] = $title.': '.($old ? 'Có' : 'Không').' -> '.($new ? 'Có' : 'Không');

                continue;
            }

            if ((string) $old === (string) $new) {
                continue;
            }

            // Cột khoá ngoại thì hiện tên cho dễ đọc thay vì id
            if (isset($labels[$field])) {
                $parts[] = $title.': '.($labels[$field][$old] ?? '—').' -> '.($labels[$field][$new] ?? '—');

                continue;
            }

            $parts[] = $title.': '.($old === null || $old === '' ? '—' : $old).' -> '.($new === null || $new === '' ? '—' : $new);
        }

        return implode(' | ', $parts);
    }

    /** Bảng tra id -> tên của các cột khoá ngoại, dùng khi mô tả nội dung đã đổi. */
    private function labelMaps(): array
    {
        return [
            'category_id' => DB::table('chemical_categories')
                ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
                ->select('chemical_categories.id', 'chemical_categories.code', 'chem_names.name as chem_name')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->id => trim($row->code.' '.($row->chem_name ?? ''))])
                ->all(),
            'supplier_id' => DB::table('suppliers')->pluck('name', 'id')->all(),
            'location_id' => DB::table('locations')->pluck('name', 'id')->all(),
        ];
    }

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 */
    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;
        $action = $newStatus == 1 ? 'Mở khoá' : 'Khoá';

        DB::transaction(function () use ($current, $newStatus, $action) {
            DB::table(self::TABLE)->where('id', $current->id)->update([
                'status_id' => $newStatus,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->writeHistory(
                (int) $current->id,
                $action,
                'Trạng thái: '.($current->status_id == 1 ? 'Hiệu lực' : 'Đã khoá').' -> '.($newStatus == 1 ? 'Hiệu lực' : 'Đã khoá')
            );
        });

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            self::TABLE,
            $current->id,
            'status_id: '.$current->status_id,
            'status_id: '.$newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' '.$current->code.'!'
        );
    }

    /**
     * Mã xuất nhập kế tiếp: department_id + category_id + số thứ tự 8 chữ số.
     *
     * Số thứ tự đếm riêng cho từng cặp (phòng ban, danh mục hoá chất), bắt đầu từ 00000001.
     * Lấy số lớn nhất đang có của cặp đó rồi cộng 1 - phiếu chỉ khoá chứ không xoá nên mã
     * không bị dùng lại.
     */
    private function nextCode(int $departmentId, int $categoryId): string
    {
        $prefix = $departmentId.$categoryId;

        $next = DB::table(self::TABLE)
            ->where('department_id', $departmentId)
            ->where('category_id', $categoryId)
            ->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix)))
            ->max();

        return $prefix.str_pad((string) (($next ?? 0) + 1), self::SEQ_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Mã dự kiến của từng hoá chất để hiện trước trên form thêm mới: [category_id => mã].
     *
     * Chỉ để xem, mã thật vẫn sinh lúc lưu trong transaction. Gom một truy vấn cho cả
     * phòng ban rồi tính trong PHP để không phải hỏi DB theo từng hoá chất.
     */
    private function codePreviews(int $departmentId, $categories): array
    {
        $used = DB::table(self::TABLE)
            ->select('category_id', 'code')
            ->where('department_id', $departmentId)
            ->get()
            ->groupBy('category_id');

        $previews = [];

        foreach ($categories as $category) {
            $prefix = $departmentId.$category->id;

            $next = ($used[$category->id] ?? collect())
                ->map(fn ($row) => (int) substr((string) $row->code, strlen($prefix)))
                ->max();

            $previews[$category->id] = $prefix.str_pad((string) (($next ?? 0) + 1), self::SEQ_LENGTH, '0', STR_PAD_LEFT);
        }

        return $previews;
    }

    /** Danh mục hoá chất đã duyệt và đang hoạt động mới được chọn để nhập. */
    private function categoryOptions()
    {
        return DB::table('chemical_categories')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'chemical_categories.unit_id', '=', 'units.id')
            ->select(
                'chemical_categories.id',
                'chemical_categories.code',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name'
            )
            ->where('chemical_categories.status_id', 1)
            ->where('chemical_categories.app_status', 'approved')
            ->orderBy('chemical_categories.code', 'asc')
            ->get();
    }

    /**
     * Vị trí lưu trữ được phép chọn: cấp sâu nhất của định khu (locations), kèm
     * đường dẫn Kho / Phòng / Kệ để người nhập biết chính xác đang chọn chỗ nào.
     *
     * Chỉ lưu locations.id ở imports - ba cấp trên lấy lại được từ chính bảng này.
     */
    private function locationOptions(int $departmentId)
    {
        return DB::table('locations')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->select(
                'locations.id',
                'locations.code',
                'locations.name',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where('locations.department_id', $departmentId)
            ->where('locations.status_id', 1)
            ->orderBy('warehouses.name', 'asc')
            ->orderBy('rooms.name', 'asc')
            ->orderBy('shelves.name', 'asc')
            ->orderBy('locations.name', 'asc')
            ->get();
    }

    private function supplierOptions()
    {
        return DB::table('suppliers')
            ->select('id', 'name')
            ->where('status_id', 1)
            ->where('app_status', 'approved')
            ->orderBy('name', 'asc')
            ->get();
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return session('user')['fullName'] ?? 'NA';
    }

    private function rules(int $departmentId): array
    {
        return [
            'category_id' => ['required', 'exists:chemical_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'imported_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'max:100'],
            'invoice_date' => ['nullable', 'date'],
            'expired_date' => ['nullable', 'date', 'after_or_equal:imported_date'],
            'batch_no' => ['nullable', 'max:100'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            // Chỉ nhận vị trí thuộc ĐÚNG phòng ban đang chọn và còn hiệu lực:
            // exists:locations,id không thôi thì sửa request là gán được vị trí của phòng khác
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'note' => ['nullable', 'max:500'],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'category_id' => (int) $request->category_id,
            'amount' => (float) $request->amount,
            'imported_date' => $request->imported_date,
            'invoice_number' => $this->nullIfBlank($request->invoice_number),
            'invoice_date' => $this->nullIfBlank($request->invoice_date),
            'expired_date' => $this->nullIfBlank($request->expired_date),
            'is_microbiological_chemicals' => $request->boolean('is_microbiological_chemicals'),
            'batch_no' => $this->nullIfBlank($request->batch_no),
            'supplier_id' => $request->supplier_id ? (int) $request->supplier_id : null,
            'location_id' => $request->location_id ? (int) $request->location_id : null,
            'note' => $this->nullIfBlank($request->note),
        ];
    }

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn hoá chất cần nhập.',
            'category_id.exists' => 'Hoá chất được chọn không tồn tại trong danh mục.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'imported_date.required' => 'Vui lòng chọn ngày nhập.',
            'imported_date.date' => 'Ngày nhập không hợp lệ.',
            'invoice_number.max' => 'Số hoá đơn tối đa 100 ký tự.',
            'invoice_date.date' => 'Ngày hoá đơn không hợp lệ.',
            'expired_date.date' => 'Hạn sử dụng không hợp lệ.',
            'expired_date.after_or_equal' => 'Hạn sử dụng phải từ ngày nhập trở đi.',
            'batch_no.max' => 'Số lô tối đa 100 ký tự.',
            'supplier_id.exists' => 'Nhà cung cấp được chọn không tồn tại.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }
}
