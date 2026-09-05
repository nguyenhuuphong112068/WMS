<?php

namespace App\Http\Controllers\Pages\Import;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\AttachmentBackup;
use App\Support\Barcode128;
use App\Support\ChemicalCode;
use App\Support\DepartmentChemical;
use App\Support\UnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * NHẬP - NHẬP HOÁ CHẤT
 *
 * Ghi nhận từng lần nhập hoá chất vào kho của phòng ban đang chọn.
 *
 * MÃ XUẤT NHẬP (cột code) sinh tự động: "C" + shortName phòng ban + đuôi ngẫu nhiên,
 * ví dụ C-QC1-7KPMR9J4WD. Mã KHÔNG chứa số thứ tự và không gắn với danh mục hoá chất
 * nên khoá / xoá một phiếu nhập không để lại khoảng trống nhìn thấy được qua giao diện.
 * Công thức nằm ở App\Support\ChemicalCode.
 */
class ChemicalImportController extends Controller
{
    private const TABLE = 'chemical_imports';

    private const HISTORY_TABLE = 'chemical_import_histories';

    private const ATTACHMENT_TABLE = 'chemical_import_attachments';

    /** Thư mục lưu file đính kèm, dùng chung cho cả disk private lẫn bản sao lưu public/uploads/. */
    private const ATTACHMENT_FOLDER = 'chemical_imports';

    private const LABEL = 'phiếu nhập hoá chất';

    /** Số nhãn tối đa cho một lần in, chặn cả ở trang in lẫn ở đây. */
    private const LABEL_MAX_COPIES = 100;

    /** Sai số cho phép khi so số lượng với nhau (cột decimal 15,4). */
    private const EPSILON = 0.00005;

    /**
     * Các trường được theo dõi khi điều chỉnh: cột => tên hiển thị trong lịch sử.
     * Thêm cột mới vào form nhớ khai ở đây, nếu không lịch sử sẽ không ghi lại thay đổi đó.
     */
    private const FIELDS = [
        'category_id' => 'Hoá chất',
        'amount' => 'Số lượng',
        'invoice_number' => 'Số hoá đơn',
        'invoice_date' => 'Ngày hoá đơn',
        'expired_date' => 'Hạn sử dụng',
        'is_microbiological_chemicals' => 'Hoá chất vi sinh',
        'batch_no' => 'Số lô',
        'supplier_id' => 'Nhà cung cấp',
        'location_id' => 'Vị trí lưu trữ',
        'note' => 'Ghi chú',
    ];

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('chemical_categories', self::TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            // Đơn vị tính khai ở danh mục hoá chất CỦA PHÒNG, không còn ở danh mục chung
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, self::TABLE.'.category_id'))
            ->leftJoin('suppliers', self::TABLE.'.supplier_id', '=', 'suppliers.id')
            // Định khu của lô: locations giữ sẵn id 3 cấp trên nên join tiếp là ra đủ đường dẫn
            ->leftJoin('locations', self::TABLE.'.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            // Lô nhận từ phòng ban khác: truy ngược phiếu chuyển để biết nhận của phòng nào
            ->leftJoin('chemical_exports as source_export', self::TABLE.'.source_export_id', '=', 'source_export.id')
            ->leftJoin('deparments as from_dept', 'source_export.department_id', '=', 'from_dept.id')
            ->select(
                self::TABLE.'.*',
                'source_export.code as source_code',
                'source_export.exported_date as transferred_date',
                'from_dept.name as from_department_name',
                'from_dept.shortName as from_department_short',
                'chemical_categories.code as category_code',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'suppliers.name as supplier_name',
                'suppliers.address as supplier_address',
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

        $categories = $this->categoryOptions($departmentId, $datas->pluck('category_id')->all());

        $deptChemicals = DB::table('chemical_department_categories')
            ->where('department_id', $departmentId)
            ->get()
            ->keyBy('category_id');

        $categoryDefaults = $categories->mapWithKeys(function ($category) use ($deptChemicals) {
            $dc = $deptChemicals->get($category->id);
            $info = [
                'Tên: <strong>' . htmlspecialchars($category->chem_name ?: $category->code) . '</strong>',
                'Đơn vị phòng: <strong>' . htmlspecialchars($category->unit_short_name ?: 'Chưa thiết lập') . '</strong>'
            ];
            
            return [$category->id => [
                'location_id' => $dc->default_location_id ?? null,
                'info_html' => implode(' | ', $info),
            ]];
        })->toArray();

        $attachments = DB::table(self::ATTACHMENT_TABLE)
            ->whereIn('chemical_import_id', $datas->pluck('id'))
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('chemical_import_id');

        [$from, $to] = $this->reportRange($request);

        return view('pages.import.ChemicalImport.list', [
            'datas' => $datas,
            'categories' => $categories,
            'categoryDefaults' => $categoryDefaults,
            'attachments' => $attachments,
            // Nhóm NĐ 24/2026 suy tự động theo mã danh mục (thay cột classification đã bỏ)
            'classificationCodes' => \App\Support\ChemicalClassification::codesByCategory(),
            'classificationLabels' => \App\Support\ChemicalClassification::labels(),
            'suppliers' => $this->supplierOptions(),
            'locations' => $this->locationOptions($departmentId),
            'codePreviews' => [],
            'report' => $this->importReport($departmentId, $from, $to),
            'reportFrom' => $from,
            'reportTo' => $to,
            // Số lần điều chỉnh của từng phiếu, hiện thành badge ở góc nút Sửa thay vì một nút riêng
            'historyCounts' => $this->historyCounts($departmentId),
            // Lọc xong thì trang tải lại, quay về đúng tab báo cáo thay vì tab sổ
            'activeTab' => in_array($request->input('tab'), ['report'], true)
                ? $request->input('tab')
                : 'book',
        ]);
    }

    /**
     * IN NHÃN DÁN LÔ HOÁ CHẤT.
     *
     * Trang in độc lập đúng khổ nhãn khai ở config/chemical.php (mặc định 60x40mm), mở
     * tab mới, chọn số lượng nhãn rồi bấm In - chọn máy in nhãn Zebra là ra nhãn dán
     * thẳng lên chai/thùng. Số lượng nhãn nhân bản bằng JS ngay trên trang, không nạp
     * lại trang; lúc bấm In, trang gọi labelPrinted() để ghi audit log.
     *
     * Mã vạch là Code 128 sinh thẳng thành SVG (App\Support\Barcode128), nội dung đúng
     * bằng MÃ XUẤT NHẬP của lô, để màn hình Sử Dụng quét lại là ra đúng lô này.
     */
    public function label(Request $request)
    {
        $row = DB::table(self::TABLE)
            ->leftJoin('chemical_categories', self::TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $this->departmentId(), self::TABLE.'.category_id'))
            ->leftJoin('locations', self::TABLE.'.location_id', '=', 'locations.id')
            ->select(
                self::TABLE.'.*',
                'chemical_categories.code as category_code',
                'chemical_categories.safety_warning',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code'
            )
            ->where(self::TABLE.'.id', $request->id)
            // Chỉ in được nhãn lô của phòng ban đang chọn
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->first();

        if (! $row) {
            abort(404, 'Không tìm thấy phiếu nhập cần in nhãn.');
        }

        return view('pages.import.ChemicalImport.label', [
            'import' => $row,
            'label' => config('chemical.label'),
            'barcode' => Barcode128::svg($row->code),
            'maxCopies' => self::LABEL_MAX_COPIES,
        ]);
    }

    /**
     * GHI AUDIT LOG MỖI LẦN IN NHÃN HOÁ CHẤT.
     *
     * Trang in gọi vào đây ngay trước khi mở hộp thoại In, kể cả khi người dùng bấm
     * Ctrl+P thay vì nút In nhãn. Chỉ ghi nhật ký, không đụng vào dữ liệu phiếu nhập.
     *
     * Nhật ký lưu: in nhãn của hoá chất nào (tên + mã xuất nhập), bao nhiêu nhãn và
     * thời điểm in - thời điểm chính là audittriallog.created_at, ghi thêm vào phần mô
     * tả cho dễ đọc trên màn hình Audit Trail.
     */
    public function labelPrinted(Request $request)
    {
        $departmentId = $this->departmentId();
        $copies = max(1, min(self::LABEL_MAX_COPIES, (int) $request->input('copies', 1)));

        $row = DB::table(self::TABLE)
            ->leftJoin('chemical_categories', self::TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->select(self::TABLE.'.id', self::TABLE.'.code', 'chem_names.name as chem_name')
            ->where(self::TABLE.'.id', $request->id)
            // Chỉ ghi nhận in nhãn của lô thuộc phòng ban đang chọn
            ->where(self::TABLE.'.department_id', $departmentId)
            ->first();

        if (! $row) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy phiếu nhập cần in nhãn.'], 404);
        }

        $printedAt = now();

        AuditTrialController::log(
            'In nhãn',
            self::TABLE,
            $row->id,
            'NA',
            'In nhãn hoá chất: '.($row->chem_name ?: '(chưa có tên)')
                .' | Mã xuất nhập: '.$row->code
                .' | Số lượng nhãn: '.$copies
                .' | Thời điểm in: '.$printedAt->format('d/m/Y H:i:s')
        );

        return response()->json([
            'ok' => true,
            'copies' => $copies,
            'printedAt' => $printedAt->format('d/m/Y H:i:s'),
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
     * - Số lượng theo đơn vị PHÒNG đã khai cho hoá chất đó (chemical_department_categories.unit_id)
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
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, self::TABLE.'.category_id'))
            ->select(
                'chemical_categories.id as category_id',
                'chemical_categories.code as category_code',
                'chemical_categories.density',
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

        $uploadedFiles = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('public/'.self::ATTACHMENT_FOLDER);
                    AttachmentBackup::copy($path, self::ATTACHMENT_FOLDER);

                    $uploadedFiles[] = [
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getClientMimeType() ?: $file->getClientOriginalExtension(),
                    ];
                }
            }
        }

        // Sinh mã và ghi bản ghi trong cùng một transaction để hai người nhập cùng lúc không trùng mã
        $result = DB::transaction(function () use ($request, $departmentId, $uploadedFiles) {
            $code = $this->nextCode($departmentId);

            $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
                'code' => $code,
                'department_id' => $departmentId,
                // Ngày nhập là ngày bấm Lưu, người dùng không chọn được
                'imported_date' => now()->format('Y-m-d'),
                // Người nhập luôn là người đang đăng nhập, không nhận giá trị từ form
                'imported_by' => $this->actor(),
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($uploadedFiles as $f) {
                DB::table(self::ATTACHMENT_TABLE)->insert($f + [
                    'chemical_import_id' => $id,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Mốc đầu tiên của lịch sử: giá trị phiếu lúc mới tạo
            $this->writeHistory($id, 'Thêm mới', 'Tạo mới phiếu nhập, mã xuất nhập '.$code.'.');

            return ['id' => $id, 'code' => $code];
        });

        AuditTrialController::log('Thêm mới', self::TABLE, $result['id'], 'NA', 'Nhập hoá chất, mã xuất nhập: '.$result['code']);

        $redirect = redirect()->back()->with('success', 'Đã tạo '.self::LABEL.' mã '.$result['code'].'!');

        // Cảnh báo (không chặn) khi lô vừa nhập đẩy tồn trữ toàn công ty chạm/vượt ngưỡng
        // Phụ lục IV NĐ 24/2026/NĐ-CP - cả Bảng A (theo hoạt chất) và Bảng B (theo hỗn hợp).
        $warnings = array_filter([
            $this->thresholdWarning((int) $request->category_id),
            $this->thresholdWarningTableB((int) $request->category_id),
        ]);

        if ($warnings) {
            $redirect->with('warning', implode(' — ', $warnings));
        }

        return $redirect;
    }

    /**
     * Kiểm tra cảnh báo ngưỡng PL IV theo hoá chất + số lượng đang gõ trong modal Nhập / Điều
     * chỉnh phiếu nhập hoá chất, TRƯỚC KHI lưu - JS gọi mỗi khi đổi hoá chất hoặc gõ số
     * lượng (xem pages/import/shared/assets.blade.php). Chỉ hoạt chất Bảng A (nhóm 9) hoặc
     * hỗn hợp Bảng B (nhóm 10) mới có cảnh báo; hoá chất khác trả về mảng rỗng vì
     * ActiveIngredientThreshold/MixtureHazardThreshold::projectedForCategory() tự trả null.
     *
     * Modal Điều chỉnh gửi kèm original_category_id + original_amount (giá trị đang lưu của
     * phiếu trước khi sửa) để trừ phần đã tính vào tồn hiện tại - tránh cộng trùng số lượng
     * cũ của chính phiếu đang sửa vào tồn dự kiến.
     */
    public function checkThreshold(Request $request)
    {
        $categoryId = (int) $request->category_id;
        $amount = (float) $request->amount;

        if (! $categoryId || $amount <= 0) {
            return response()->json(['warnings' => []]);
        }

        $unitId = (int) DB::table('chemical_department_categories')
            ->where('department_id', $this->departmentId())
            ->where('category_id', $categoryId)
            ->value('unit_id');

        if (! $unitId) {
            return response()->json(['warnings' => []]);
        }

        $newRow = collect([(object) ['amount' => $amount, 'unit_id' => $unitId]]);

        $originalAmount = (int) $request->input('original_category_id') === $categoryId
            ? (float) $request->input('original_amount', 0)
            : 0.0;
        $originalRow = $originalAmount > 0
            ? collect([(object) ['amount' => $originalAmount, 'unit_id' => $unitId]])
            : collect();

        $companyId = \App\Support\CompanyContext::currentId();
        $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');
        $warnings = [];

        // ----- BẢNG A: theo hoạt chất (× % hàm lượng) -----
        $newA = \App\Support\ActiveIngredientThreshold::sumEstimateKg($categoryId, $newRow);
        $oldA = \App\Support\ActiveIngredientThreshold::sumEstimateKg($categoryId, $originalRow);
        $projA = \App\Support\ActiveIngredientThreshold::projectedForCategory(
            $categoryId, max($newA['kg'] - $oldA['kg'], 0.0), $companyId
        );

        if ($projA && ($projA->add_ratio >= 1.0 || $projA->projected_ratio >= \App\Support\ActiveIngredientThreshold::warnRatio())) {
            $warnings[] = [
                'level' => $projA->level,
                'message' => 'Hoá chất "'.$projA->ai_name.'" thuộc nhóm phải xây dựng Kế hoạch phòng ngừa, ứng phó '
                    .'sự cố hoá chất (Phụ lục IV NĐ 24/2026/NĐ-CP - Bảng A). Số lượng đang nhập ≈ '.$num($projA->add_kg)
                    .' kg hoạt chất'.($newA['unconvertible'] ? ' (chưa gồm phần chưa quy đổi được)' : '')
                    .'; cộng tồn hiện tại toàn công ty '.$num($projA->current_kg).' kg thì tổng ≈ '.$num($projA->projected_kg)
                    .' kg / ngưỡng '.$num($projA->threshold_kg).' kg ('.(int) round($projA->projected_ratio * 100).'%). '
                    .($projA->add_ratio >= 1.0 ? 'Riêng số lượng đang nhập đã vượt ngưỡng "tồn trữ lớn nhất tại một thời điểm". ' : '')
                    .($projA->level === \App\Support\ActiveIngredientThreshold::LEVEL_EXCEEDED
                        ? 'DỰ KIẾN VƯỢT NGƯỠNG nếu lưu phiếu này.'
                        : 'Dự kiến chạm ngưỡng cảnh báo.'),
            ];
        }

        // ----- BẢNG B: theo hỗn hợp (tồn thô, không × %) -----
        $newB = \App\Support\MixtureHazardThreshold::sumEstimateKg($categoryId, $newRow);
        $oldB = \App\Support\MixtureHazardThreshold::sumEstimateKg($categoryId, $originalRow);
        $projB = \App\Support\MixtureHazardThreshold::projectedForCategory(
            $categoryId, max($newB['kg'] - $oldB['kg'], 0.0), $companyId
        );

        if ($projB && ($projB->add_ratio >= 1.0 || $projB->projected_ratio >= \App\Support\MixtureHazardThreshold::warnRatio())) {
            $warnings[] = [
                'level' => $projB->level,
                'message' => 'Hỗn hợp "'.$projB->chem_name.'" thuộc nhóm nguy hại Bảng B (Phụ lục IV NĐ 24/2026/NĐ-CP). '
                    .'Số lượng đang nhập ≈ '.$num($projB->add_kg).' kg thô'
                    .($newB['unconvertible'] ? ' (chưa quy đổi được)' : '')
                    .'; cộng tồn hiện tại '.$num($projB->current_kg).' kg thì tổng ≈ '.$num($projB->projected_kg)
                    .' kg / ngưỡng thấp nhất '.$num($projB->threshold_kg).' kg (nhóm '.$projB->strictest_group.', '
                    .(int) round($projB->projected_ratio * 100).'%). '
                    .($projB->add_ratio >= 1.0 ? 'Riêng số lượng đang nhập đã vượt ngưỡng. ' : '')
                    .($projB->level === \App\Support\MixtureHazardThreshold::LEVEL_EXCEEDED
                        ? 'DỰ KIẾN VƯỢT NGƯỠNG nếu lưu phiếu này.'
                        : 'Dự kiến chạm ngưỡng cảnh báo.'),
            ];
        }

        return response()->json(['warnings' => $warnings]);
    }

    /**
     * Sau khi ghi phiếu nhập, đối chiếu tổng tồn trữ của hoạt chất đứng sau mã danh mục này
     * với ngưỡng Phụ lục IV. Phạm vi cộng tồn gói trong công ty của phòng ban đang chọn.
     * Trả về câu cảnh báo, hoặc null nếu còn trong ngưỡng.
     */
    private function thresholdWarning(int $categoryId): ?string
    {
        $eval = \App\Support\ActiveIngredientThreshold::forCategories(\App\Support\CompanyContext::currentId())[$categoryId] ?? null;

        // Cảnh báo lúc nhập bám theo TỒN HIỆN TẠI (sau khi nhập), không theo đỉnh quá khứ.
        if (! $eval || $eval->current_level === \App\Support\ActiveIngredientThreshold::LEVEL_OK) {
            return null;
        }

        $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');

        return 'Tổng tồn trữ toàn công ty của hoạt chất "'.$eval->ai_name.'" hiện '
            .$num($eval->total_kg).' kg / ngưỡng '.$num($eval->threshold_kg).' kg ('
            .(int) round($eval->ratio * 100).'%) theo Phụ lục IV Nghị định 24/2026/NĐ-CP. '
            .($eval->current_level === \App\Support\ActiveIngredientThreshold::LEVEL_EXCEEDED
                ? 'Đã VƯỢT ngưỡng.'
                : 'Sắp chạm ngưỡng.')
            .' Cơ sở tồn trữ vượt ngưỡng phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố hoá chất.';
    }

    /**
     * Như thresholdWarning() nhưng theo BẢNG B: tổng tồn thô của hỗn hợp trong phạm vi công
     * ty đang chọn (không nhân % hàm lượng) so với ngưỡng thấp nhất trong các nhóm đã tick.
     */
    private function thresholdWarningTableB(int $categoryId): ?string
    {
        $eval = \App\Support\MixtureHazardThreshold::forCategories(\App\Support\CompanyContext::currentId())[$categoryId] ?? null;

        // Cảnh báo lúc nhập bám theo TỒN HIỆN TẠI (sau khi nhập), không theo đỉnh quá khứ.
        if (! $eval || $eval->current_level === \App\Support\MixtureHazardThreshold::LEVEL_OK) {
            return null;
        }

        $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');

        return 'Tổng tồn trữ thô toàn công ty của hỗn hợp "'.$eval->chem_name.'" hiện '
            .$num($eval->total_kg).' kg / ngưỡng Bảng B '.$num($eval->min_threshold_kg).' kg ('
            .(int) round($eval->ratio * 100).'%, nhóm chặt nhất '.$eval->strictest_group.') theo Phụ lục IV '
            .'Nghị định 24/2026/NĐ-CP. '
            .($eval->current_level === \App\Support\MixtureHazardThreshold::LEVEL_EXCEEDED
                ? 'Đã VƯỢT ngưỡng.'
                : 'Sắp chạm ngưỡng.')
            .' Cơ sở tồn trữ vượt ngưỡng phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố hoá chất.';
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

        $importedDate = $current->imported_date ? \Carbon\Carbon::parse($current->imported_date)->format('Y-m-d') : null;

        $rules = $this->rules($departmentId, $importedDate) + [
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
        $hasNewFiles = $request->hasFile('attachments');

        if ($note === '' && ! $hasNewFiles) {
            return redirect()->back()->with('error', 'Không có thông tin nào thay đổi nên chưa ghi nhận điều chỉnh.');
        }

        // Mã xuất nhập là mã lô cố định của phiếu, không đổi kể cả khi đổi hoá chất.

        $reason = trim((string) $request->reason);

        DB::transaction(function () use ($current, $payload, $note, $reason, $request) {
            DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('public/'.self::ATTACHMENT_FOLDER);
                        AttachmentBackup::copy($path, self::ATTACHMENT_FOLDER);

                        DB::table(self::ATTACHMENT_TABLE)->insert([
                            'chemical_import_id' => $current->id,
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'file_size' => $file->getSize(),
                            'file_type' => $file->getClientMimeType() ?: $file->getClientOriginalExtension(),
                            'created_by' => $this->actor(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $this->writeHistory((int) $current->id, 'Điều chỉnh', $note ?: 'Cập nhật tài liệu đính kèm', $reason);
        });

        AuditTrialController::log(
            'Điều chỉnh',
            self::TABLE,
            $current->id,
            $current->code,
            $current->code.' | '.($note ?: 'Cập nhật tài liệu đính kèm').' | Lý do: '.$reason
        );

        return redirect()->back()->with('success', 'Đã ghi nhận điều chỉnh '.self::LABEL.' '.$current->code.'!');
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
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $this->departmentId(), self::HISTORY_TABLE.'.category_id'))
            ->leftJoin('suppliers', self::HISTORY_TABLE.'.supplier_id', '=', 'suppliers.id')
            ->leftJoin('locations', self::HISTORY_TABLE.'.location_id', '=', 'locations.id')
            ->select(
                self::HISTORY_TABLE.'.*',
                'chemical_categories.code as category_code',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'suppliers.name as supplier_name',
                'locations.code as location_code'
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
                    'Vị trí lưu trữ' => $row->location_code ?: '—',
                    'Hoá đơn' => $row->invoice_number ? $row->invoice_number.' ('.$date($row->invoice_date).')' : '—',
                    'Hoá chất vi sinh' => $row->is_microbiological_chemicals ? 'Có' : 'Không',
                    'Trạng thái' => $row->status_id == 1 ? 'Hiệu lực' : 'Đã khoá',
                    'Ghi chú' => $row->note ?: '—',
                ],
            ]),
        ]);
    }

    public function downloadAttachment($id)
    {
        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->join(self::TABLE, self::ATTACHMENT_TABLE.'.chemical_import_id', '=', self::TABLE.'.id')
            ->where(self::ATTACHMENT_TABLE.'.id', $id)
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->select(self::ATTACHMENT_TABLE.'.*')
            ->first();

        if (! $attachment) {
            abort(404, 'Không tìm thấy file đính kèm.');
        }

        if (! Storage::exists($attachment->file_path)) {
            abort(404, 'File không tồn tại trên hệ thống lưu trữ.');
        }

        return Storage::response($attachment->file_path, $attachment->file_name, [
            'Content-Disposition' => 'inline; filename="'.$attachment->file_name.'"',
        ]);
    }

    public function deleteAttachment(Request $request)
    {
        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->join(self::TABLE, self::ATTACHMENT_TABLE.'.chemical_import_id', '=', self::TABLE.'.id')
            ->where(self::ATTACHMENT_TABLE.'.id', $request->id)
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->select(self::ATTACHMENT_TABLE.'.*', self::TABLE.'.code as import_code')
            ->first();

        if (! $attachment) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy file.'], 404);
        }

        Storage::delete($attachment->file_path);
        AttachmentBackup::delete($attachment->file_path, self::ATTACHMENT_FOLDER);
        DB::table(self::ATTACHMENT_TABLE)->where('id', $attachment->id)->delete();

        AuditTrialController::log(
            'Xoá tài liệu',
            self::TABLE,
            $attachment->chemical_import_id,
            $attachment->import_code,
            'Xoá file đính kèm: '.$attachment->file_name
        );

        return response()->json(['success' => true]);
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
            'location_id' => DB::table('locations')->pluck('code', 'id')->all(),
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
     * Mã xuất nhập kế tiếp: "C" + shortName phòng ban + đuôi ngẫu nhiên.
     *
     * Không còn số thứ tự, không còn phụ thuộc danh mục hoá chất - xem
     * App\Support\ChemicalCode. Gọi trong transaction của lúc lưu.
     */
    private function nextCode(int $departmentId): string
    {
        return ChemicalCode::next($this->departmentShortName($departmentId));
    }

    /** shortName của phòng ban để ghép vào mã xuất nhập. */
    private function departmentShortName(int $departmentId): string
    {
        if ($departmentId === $this->departmentId()) {
            $short = session('user')['selected_department'] ?? null;

            if ($short) {
                return $short;
            }
        }

        return (string) (DB::table('deparments')->where('id', $departmentId)->value('shortName') ?: $departmentId);
    }

    /**
     * Số lần ĐIỀU CHỈNH của từng phiếu nhập: [import_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc lập phiếu chứ không phải một lần chỉnh sửa.
     * Badge trên nút Sửa chỉ hiện khi phiếu thật sự đã bị đổi ít nhất một lần.
     */
    private function historyCounts(int $departmentId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('import_id', DB::raw('COUNT(*) as times'))
            ->whereIn('import_id', function ($query) use ($departmentId) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $departmentId);
            })
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('import_id')
            ->pluck('times', 'import_id');
    }

    /**
     * Hoá chất được chọn để nhập: CHỈ những chất phòng đã khai ở tab "Hoá Chất Của Phòng".
     *
     * Chưa khai thì không nhập vào kho được - xem App\Support\DepartmentChemical.
     */
    private function categoryOptions(int $departmentId, array $keepIds = [])
    {
        return DepartmentChemical::importCategoryOptions($departmentId, $keepIds);
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
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where('locations.department_id', $departmentId)
            ->where('locations.status_id', 1)
            ->orderBy('warehouses.name', 'asc')
            ->orderBy('rooms.name', 'asc')
            ->orderBy('shelves.name', 'asc')
            ->orderBy('locations.code', 'asc')
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
        return \App\Support\Signer::actor();
    }

    /**
     * $importedDate: ngày nhập đã ghi của phiếu (lúc điều chỉnh) hoặc hôm nay (lúc tạo mới),
     * dùng làm mốc cho Hạn sử dụng vì form không còn ô Ngày nhập.
     */
    private function rules(int $departmentId, ?string $importedDate = null): array
    {
        return [
            // Chưa khai hoá chất ở tab "Hoá Chất Của Phòng" thì không được nhập vào kho:
            // exists:chemical_categories,id không thôi thì sửa request là nhập được chất của phòng khác
            'category_id' => [
                'required',
                Rule::exists('chemical_department_categories', 'category_id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'invoice_number' => ['nullable', 'max:100'],
            'invoice_date' => ['nullable', 'date'],
            'expired_date' => ['nullable', 'date', 'after_or_equal:'.($importedDate ?: now()->format('Y-m-d'))],
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
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'category_id' => (int) $request->category_id,
            'amount' => (float) $request->amount,
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
            'category_id.exists' => 'Hoá chất được chọn chưa được phòng khai ở tab "Hoá Chất Của Phòng" nên không nhập vào kho được.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'invoice_number.max' => 'Số hoá đơn tối đa 100 ký tự.',
            'invoice_date.date' => 'Ngày hoá đơn không hợp lệ.',
            'expired_date.date' => 'Hạn sử dụng không hợp lệ.',
            'expired_date.after_or_equal' => 'Hạn sử dụng phải từ ngày nhập trở đi.',
            'batch_no.max' => 'Số lô tối đa 100 ký tự.',
            'supplier_id.exists' => 'Nhà cung cấp được chọn không tồn tại.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
            'attachments.*.max' => 'Mỗi file đính kèm không được vượt quá 10MB.',
        ];
    }
}
