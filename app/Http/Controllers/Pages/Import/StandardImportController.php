<?php

namespace App\Http\Controllers\Pages\Import;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\AttachmentBackup;
use App\Support\Barcode128;
use App\Support\DepartmentStandard;
use App\Support\StandardCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * NHẬP - NHẬP CHẤT CHUẨN
 *
 * Ghi nhận từng ống chuẩn nhập vào kho của phòng ban đang chọn.
 *
 * MÃ ỐNG CHUẨN sinh tự động theo quy tắc riêng của chất chuẩn:
 *
 *      deparments.shortName + mã nhóm chuẩn + yy + mm + số thứ tự (4 chữ số)
 *      QC1              +   VKN         + 26 + 01 + 0036   ->  QC1VKN26010036
 *
 * Một chất chuẩn được xếp vào 1 nhóm (lấy từ Danh Mục Chất Chuẩn).
 * Công thức sinh mã nằm ở App\Support\StandardCode.
 *
 * Phiếu nhập chỉ khoá (deActive) chứ không xoá cứng, để mã ống chuẩn không bị cấp lại.
 */
class StandardImportController extends Controller
{
    private const TABLE = 'standard_imports';

    private const HISTORY_TABLE = 'standard_import_histories';

    private const ATTACHMENT_TABLE = 'standard_import_attachments';

    /** Thư mục lưu file đính kèm, dùng chung cho cả disk private lẫn bản sao lưu public/uploads/. */
    private const ATTACHMENT_FOLDER = 'standard_imports';

    private const LABEL = 'phiếu nhập chất chuẩn';

    /**
     * Các trường được theo dõi khi điều chỉnh: cột => tên hiển thị trong lịch sử.
     */
    private const FIELDS = [
        'category_id' => 'Chất chuẩn',
        'group_code' => 'Nhóm chuẩn',
        'amount' => 'Số lượng',
        'invoice_number' => 'Số hoá đơn',
        'expired_date' => 'Hạn sử dụng',
        'expiry_type' => 'Loại hạn dùng',
        'retest_interval_months' => 'Khoảng thời gian retest (tháng)',
        'batch_no' => 'Số lô',
        'coa_no' => 'Số phiếu kiểm nghiệm gốc',
        'potency' => 'Hàm lượng',
        'moisture' => 'Độ ẩm',
        'weight_controlled' => 'Kiểm soát khối lượng',
        'standard_form' => 'Dạng chuẩn',
        'requires_aliquot' => 'chiết ống trước khi dùng',
        'supplier_id' => 'Nhà cung cấp',
        'location_id' => 'Vị trí lưu trữ',
        'purpose_id' => 'Chỉ tiêu kiểm',
        'note' => 'Ghi chú',
    ];

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('standard_categories', self::TABLE . '.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            // Đơn vị tính khai ở danh mục chất chuẩn CỦA PHÒNG, không còn ở danh mục chung
            ->tap(fn($query) => DepartmentStandard::joinUnit($query, $departmentId, self::TABLE . '.category_id'))
            ->leftJoin('suppliers', self::TABLE . '.supplier_id', '=', 'suppliers.id')
            // Định khu của ống: locations giữ sẵn id 3 cấp trên nên join tiếp là ra đủ đường dẫn
            ->leftJoin('locations', self::TABLE . '.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->select(
                self::TABLE . '.*',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_categories.cas_no',
                'standard_categories.groups',
                'standard_names.name as standard_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'suppliers.name as supplier_name',
                'suppliers.address as supplier_address',
                'locations.code as location_code',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where(self::TABLE . '.department_id', $departmentId)
            ->orderBy(self::TABLE . '.imported_date', 'desc')
            ->orderBy(self::TABLE . '.id', 'desc')
            ->get();

        session()->put(['title' => 'NHẬP - NHẬP CHẤT CHUẨN']);



        $categories = $this->categoryOptions($departmentId, $datas->pluck('category_id')->all());

        $deptStandards = DB::table('standard_department_categories')
            ->where('department_id', $departmentId)
            ->get()
            ->keyBy('category_id');

        $categoryDefaults = $categories->mapWithKeys(function ($category) use ($deptStandards) {
            $ds = $deptStandards->get($category->id);
            $shelfLife = $ds->shelf_life_months ?? $category->shelf_life_months ?? null;
            $info = [
                'Tên: <strong>' . htmlspecialchars($category->standard_name ?: $category->code) . '</strong>',
                'NSX: <strong>' . htmlspecialchars($category->manufacturer_short_name ?: '—') . '</strong>',
                'Version: <strong>v' . htmlspecialchars($category->version) . '</strong>',
                'Đơn vị phòng: <strong>' . htmlspecialchars($category->unit_short_name ?: 'Chưa thiết lập') . '</strong>'
            ];
            if ($shelfLife) {
                $info[] = 'Hạn dùng: <strong>' . htmlspecialchars($shelfLife) . ' tháng</strong>';
            }

            return [$category->id => [
                'location_id' => $ds->default_location_id ?? null,
                'shelf_life_months' => $shelfLife,
                'group_key' => $category->default_group_key,
                'group_code' => $category->default_group_code,
                'info_html' => implode(' | ', $info),
                'unit_short_name' => $category->unit_short_name,
            ]];
        })->toArray();

        $attachments = DB::table(self::ATTACHMENT_TABLE)
            ->whereIn('standard_import_id', $datas->pluck('id'))
            ->get()
            ->groupBy('standard_import_id');

        $purposes = DB::table('purposes')
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->get();

        return view('pages.import.StandardImport.list', [
            'datas' => $datas,
            'categories' => $categories,
            'categoryDefaults' => $categoryDefaults,
            'attachments' => $attachments,
            'suppliers' => $this->supplierOptions(),
            'locations' => $this->locationOptions($departmentId),
            'purposes' => $purposes,
            'groups' => config('standard.groups'),
            'codePreviews' => StandardCode::previews($departmentId, $this->departmentShortName(), now()->format('Y-m-d')),
            'historyCounts' => $this->historyCounts($departmentId),
            'activeTab' => 'book',
        ]);
    }

    /**
     * IN NHÃN DÁN ỐNG CHUẨN.
     */
    public function label(Request $request)
    {
        $row = DB::table(self::TABLE)
            ->leftJoin('standard_categories', self::TABLE . '.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn($query) => DepartmentStandard::joinUnit($query, $this->departmentId(), self::TABLE . '.category_id'))
            ->leftJoin('locations', self::TABLE . '.location_id', '=', 'locations.id')
            ->select(
                self::TABLE . '.*',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_categories.cas_no',
                'standard_names.name as standard_name',
                'manufacturers.short_name as manufacturer_short_name',
                'manufacturers.name as manufacturer_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code'
            )
            ->where(self::TABLE . '.id', $request->id)
            ->where(self::TABLE . '.department_id', $this->departmentId())
            ->first();

        if (! $row) {
            abort(404, 'Không tìm thấy phiếu nhập chất chuẩn cần in nhãn.');
        }

        return view('pages.import.StandardImport.label', [
            'import' => $row,
            'label' => config('standard.label'),
            'groupLabel' => StandardCode::groupLabel($this->groupKeyOf($row->group_code)),
            'barcode' => Barcode128::svg($row->code),
        ]);
    }

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), $this->rules($departmentId, true), $this->messages());

        $this->checkGroup($validator, $request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $shortName = $this->departmentShortName();
        $quantity = max(1, min(50, (int) $request->input('quantity', 1)));

        // Upload files once if any
        $uploadedFiles = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                    $fileType = $file->getClientMimeType() ?: $file->getClientOriginalExtension();
                    $path = $file->store('public/'.self::ATTACHMENT_FOLDER);
                    AttachmentBackup::copy($path, self::ATTACHMENT_FOLDER);

                    $uploadedFiles[] = [
                        'file_name' => $originalName,
                        'file_path' => $path,
                        'file_size' => $fileSize,
                        'file_type' => $fileType,
                    ];
                }
            }
        }

        $createdCodes = [];

        DB::transaction(function () use ($request, $departmentId, $shortName, $quantity, $uploadedFiles, &$createdCodes) {
            $payload = $this->payload($request);
            // Ngày nhập là ngày bấm Lưu, người dùng không chọn được
            $importedDate = now()->format('Y-m-d');

            for ($i = 0; $i < $quantity; $i++) {
                $code = StandardCode::next($departmentId, $shortName, $payload['group_code'], $importedDate);

                $id = DB::table(self::TABLE)->insertGetId($payload + $code + [
                    'department_id' => $departmentId,
                    'imported_date' => $importedDate,
                    'imported_by' => $this->actor(),
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Lưu các file đính kèm cho từng ống
                foreach ($uploadedFiles as $f) {
                    DB::table(self::ATTACHMENT_TABLE)->insert([
                        'standard_import_id' => $id,
                        'file_name' => $f['file_name'],
                        'file_path' => $f['file_path'],
                        'file_size' => $f['file_size'],
                        'file_type' => $f['file_type'],
                        'created_by' => $this->actor(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->writeHistory($id, 'Thêm mới', 'Tạo mới phiếu nhập, mã ống chuẩn ' . $code['code'] . '.');

                $createdCodes[] = $code['code'];

                AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Nhập chất chuẩn, mã ống chuẩn: ' . $code['code']);
            }
        });

        $msg = count($createdCodes) === 1
            ? 'Đã tạo ' . self::LABEL . ' mã ống chuẩn ' . $createdCodes[0] . '!'
            : 'Đã tạo thành công ' . count($createdCodes) . ' ống chuẩn: ' . implode(', ', $createdCodes) . '!';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * ĐIỀU CHỈNH PHIẾU NHẬP - sửa thông tin nhập và ghi lại một dòng lịch sử.
     */
    public function update(Request $request)
    {
        $departmentId = $this->departmentId();

        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần điều chỉnh!');
        }

        $rules = $this->rules($departmentId, false) + [
            'reason' => ['required', 'max:500'],
        ];

        $messages = $this->messages() + [
            'reason.required' => 'Vui lòng nhập lý do điều chỉnh.',
            'reason.max' => 'Lý do điều chỉnh tối đa 500 ký tự.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $this->checkGroup($validator, $request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = $this->changeNote($current, $payload);

        $hasNewFiles = $request->hasFile('attachments');

        if ($note === '' && ! $hasNewFiles) {
            return redirect()->back()->with('error', 'Không có thông tin nào thay đổi nên chưa ghi nhận điều chỉnh.');
        }

        $reason = trim((string) $request->reason);

        DB::transaction(function () use ($current, $payload, $note, $reason, $request) {
            DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Xử lý upload thêm file nếu có
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $fileSize = $file->getSize();
                        $fileType = $file->getClientMimeType() ?: $file->getClientOriginalExtension();
                        $path = $file->store('public/'.self::ATTACHMENT_FOLDER);
                        AttachmentBackup::copy($path, self::ATTACHMENT_FOLDER);

                        DB::table(self::ATTACHMENT_TABLE)->insert([
                            'standard_import_id' => $current->id,
                            'file_name' => $originalName,
                            'file_path' => $path,
                            'file_size' => $fileSize,
                            'file_type' => $fileType,
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
            ($note ?: 'Cập nhật tài liệu đính kèm') . ' | Lý do: ' . $reason
        );

        return redirect()->back()->with('success', 'Đã ghi nhận điều chỉnh ' . self::LABEL . ' ' . $current->code . '!');
    }

    /** Lịch sử điều chỉnh của một phiếu nhập, trả JSON cho modal trên bảng. */
    public function history(Request $request)
    {
        $import = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $import) {
            return response()->json(['rows' => []]);
        }

        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('standard_categories', self::HISTORY_TABLE . '.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->tap(fn($query) => DepartmentStandard::joinUnit($query, $this->departmentId(), self::HISTORY_TABLE . '.category_id'))
            ->leftJoin('suppliers', self::HISTORY_TABLE . '.supplier_id', '=', 'suppliers.id')
            ->leftJoin('locations', self::HISTORY_TABLE . '.location_id', '=', 'locations.id')
            ->select(
                self::HISTORY_TABLE . '.*',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_names.name as standard_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'suppliers.name as supplier_name',
                'locations.code as location_code'
            )
            ->where(self::HISTORY_TABLE . '.standard_import_id', $import->id)
            ->orderBy(self::HISTORY_TABLE . '.id', 'desc')
            ->get();

        $date = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

        return response()->json([
            'rows' => $rows->map(fn($row) => [
                'action' => $row->action,
                'change_note' => $row->change_note,
                'reason' => $row->reason,
                'created_by' => $row->created_by ?: 'NA',
                'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                'snapshot' => [
                    'Mã ống chuẩn' => $row->code ?: '—',
                    'Chất chuẩn' => trim(($row->category_code ?: '') . ' ' . ($row->standard_name ?: '')) ?: '—',
                    'Version' => $row->category_version !== null ? (string) $row->category_version : '—',
                    'Nhóm chuẩn' => StandardCode::groupLabel($this->groupKeyOf($row->group_code)),
                    'Số lượng' => $this->number((float) $row->amount) . ' ' . ($row->unit_short_name ?: $row->unit_name ?: ''),
                    'Số lô' => $row->batch_no ?: '—',
                    'Số phiếu KN gốc' => $row->coa_no ?: '—',
                    'Hàm lượng' => $row->potency ?: '—',
                    'Độ ẩm' => $row->moisture ?: '—',
                    'Dạng chuẩn' => $row->standard_form ?: '—',
                    'Kiểm soát khối lượng' => $row->weight_controlled ? 'Có' : 'Không',
                    'Chiết ống trước khi dùng' => $row->requires_aliquot ? 'Có' : 'Không',
                    'Ngày nhập' => $date($row->imported_date),
                    'Loại hạn dùng' => match ($row->expiry_type) {
                        'check online', 'undetermined', 'unlimited' => 'Chưa xác định (Check online)',
                        'retest' => 'Cần retest định kỳ',
                        'Specify', 'defined' => 'Hạn dùng xác định',
                        'Requires_re-evaluation' => 'Cần xác định lại hạn dùng nội bộ',
                        default => $row->expiry_type ?: '—'
                    },
                    'Hạn sử dụng' => $date($row->expired_date),
                    'Chu kỳ retest' => $row->retest_interval_months ? $row->retest_interval_months . ' tháng' : '—',
                    'Nhà cung cấp' => $row->supplier_name ?: '—',
                    'Chỉ tiêu kiểm' => $this->purposeNames($row->purpose_id) ?: '—',
                    'Vị trí lưu trữ' => $row->location_code ?: '—',
                    'Hoá đơn' => $row->invoice_number ?: '—',
                    'Trạng thái' => $row->status_id == 1 ? 'Hiệu lực' : 'Đã khoá',
                    'Ghi chú' => $row->note ?: '—',
                ],
            ]),
        ]);
    }

    public function downloadAttachment($id)
    {
        $departmentId = $this->departmentId();

        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->join(self::TABLE, self::ATTACHMENT_TABLE . '.standard_import_id', '=', self::TABLE . '.id')
            ->where(self::ATTACHMENT_TABLE . '.id', $id)
            ->where(self::TABLE . '.department_id', $departmentId)
            ->select(self::ATTACHMENT_TABLE . '.*')
            ->first();

        if (! $attachment) {
            abort(404, 'Không tìm thấy file đính kèm.');
        }

        if (! Storage::exists($attachment->file_path)) {
            abort(404, 'File không tồn tại trên hệ thống lưu trữ.');
        }

        return Storage::response($attachment->file_path, $attachment->file_name, [
            'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"',
        ]);
    }

    public function deleteAttachment(Request $request)
    {
        $departmentId = $this->departmentId();

        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->join(self::TABLE, self::ATTACHMENT_TABLE . '.standard_import_id', '=', self::TABLE . '.id')
            ->where(self::ATTACHMENT_TABLE . '.id', $request->id)
            ->where(self::TABLE . '.department_id', $departmentId)
            ->select(self::ATTACHMENT_TABLE . '.*', self::TABLE . '.code as import_code')
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
            $attachment->standard_import_id,
            $attachment->import_code,
            'Xoá file đính kèm: ' . $attachment->file_name
        );

        return response()->json(['success' => true]);
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần thay đổi trạng thái!');
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
                'Trạng thái: ' . ($current->status_id == 1 ? 'Hiệu lực' : 'Đã khoá')
                    . ' -> ' . ($newStatus == 1 ? 'Hiệu lực' : 'Đã khoá')
            );
        });

        AuditTrialController::log(
            $action,
            self::TABLE,
            $current->id,
            'status_id: ' . $current->status_id,
            'status_id: ' . $newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . self::LABEL . ' ' . $current->code . '!'
        );
    }



    private function writeHistory(int $id, string $action, ?string $note, ?string $reason = null): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'standard_import_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'category_id' => $row->category_id,
            'group_code' => $row->group_code,
            'amount' => $row->amount,
            'imported_date' => $row->imported_date,
            'imported_by' => $row->imported_by,
            'invoice_number' => $row->invoice_number,
            'invoice_date' => $row->invoice_date ?? null,
            'expired_date' => $row->expired_date,
            'expiry_type' => $row->expiry_type ?? 'defined',
            'retest_interval_months' => $row->retest_interval_months ?? null,
            'internal_expired_date' => $row->internal_expired_date,
            'batch_no' => $row->batch_no,
            'coa_no' => $row->coa_no,
            'potency' => $row->potency ?? null,
            'moisture' => $row->moisture ?? null,
            'weight_controlled' => $row->weight_controlled ?? 0,
            'standard_form' => $row->standard_form ?? null,
            'requires_aliquot' => $row->requires_aliquot ?? 0,
            'supplier_id' => $row->supplier_id,
            'purpose_id' => $row->purpose_id ?? null,
            'location_id' => $row->location_id,
            'note' => $row->note,
            'status_id' => $row->status_id,
            'change_note' => $note,
            'reason' => $reason,
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    private function changeNote($current, array $payload): string
    {
        $labels = $this->labelMaps();
        $parts = [];

        foreach (self::FIELDS as $field => $title) {
            $old = $current->$field ?? null;
            $new = $payload[$field] ?? null;

            if ($field === 'amount') {
                if (abs((float) $old - (float) $new) < 0.00005) {
                    continue;
                }

                $parts[] = $title . ': ' . $this->number((float) $old) . ' -> ' . $this->number((float) $new);

                continue;
            }

            if ($field === 'weight_controlled' || $field === 'requires_aliquot') {
                $oldBool = (int) $old;
                $newBool = (int) $new;
                if ($oldBool !== $newBool) {
                    $parts[] = $title . ': ' . ($oldBool ? 'Có' : 'Không') . ' -> ' . ($newBool ? 'Có' : 'Không');
                }
                continue;
            }

            if ($field === 'purpose_id') {
                $oldNames = $this->purposeNames($old);
                $newNames = $this->purposeNames($new);
                if ($oldNames !== $newNames) {
                    $parts[] = $title . ': ' . ($oldNames ?: '—') . ' -> ' . ($newNames ?: '—');
                }
                continue;
            }

            if ((string) $old === (string) $new) {
                continue;
            }

            if ($field === 'group_code') {
                $parts[] = $title . ': ' . StandardCode::groupLabel($this->groupKeyOf($old))
                    . ' -> ' . StandardCode::groupLabel($this->groupKeyOf($new));

                continue;
            }

            if (isset($labels[$field])) {
                $parts[] = $title . ': ' . ($labels[$field][$old] ?? '—') . ' -> ' . ($labels[$field][$new] ?? '—');

                continue;
            }

            $parts[] = $title . ': ' . ($old === null || $old === '' ? '—' : $old) . ' -> ' . ($new === null || $new === '' ? '—' : $new);
        }

        return implode(' | ', $parts);
    }

    private function purposeNames($value): string
    {
        if (! $value) {
            return '';
        }

        $ids = is_array($value) ? $value : json_decode((string) $value, true);
        if (! is_array($ids)) {
            $ids = is_numeric($value) ? [(int) $value] : [];
        }

        if (empty($ids)) {
            return '';
        }

        return DB::table('purposes')->whereIn('id', $ids)->pluck('name')->implode(', ');
    }

    private function labelMaps(): array
    {
        return [
            'category_id' => DB::table('standard_categories')
                ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
                ->select('standard_categories.id', 'standard_categories.code', 'standard_names.name as standard_name')
                ->get()
                ->mapWithKeys(fn($row) => [$row->id => trim($row->code . ' ' . ($row->standard_name ?? ''))])
                ->all(),
            'supplier_id' => DB::table('suppliers')->pluck('name', 'id')->all(),
            'location_id' => DB::table('locations')->pluck('code', 'id')->all(),
        ];
    }

    private function groupKeyOf(?string $groupCode): ?string
    {
        if (! $groupCode) {
            return null;
        }

        foreach (config('standard.groups') as $key => $group) {
            if ($group['code'] === $groupCode) {
                return $key;
            }
        }

        return null;
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }

    private function historyCounts(int $departmentId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('standard_import_id', DB::raw('COUNT(*) as times'))
            ->whereIn('standard_import_id', function ($query) use ($departmentId) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $departmentId);
            })
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('standard_import_id')
            ->pluck('times', 'standard_import_id');
    }

    /**
     * Chất chuẩn được chọn để nhập: CHỈ những chất phòng đã khai ở tab "Chất Chuẩn Của Phòng".
     *
     * Chưa khai thì không nhập vào kho được - xem App\Support\DepartmentStandard.
     * Nhóm chuẩn giải mã ngay ở đây để ô chọn điền sẵn nhóm mặc định của từng chất.
     */
    private function categoryOptions(int $departmentId, array $usedIds = [])
    {
        return DepartmentStandard::importCategoryOptions($departmentId, $usedIds)
            ->map(function ($row) {
                $row->group_keys = StandardCode::decodeGroups($row->groups);
                $row->default_group_key = $row->group_keys[0] ?? '';
                $row->default_group_code = $row->default_group_key ? StandardCode::groupCode($row->default_group_key) : '';

                return $row;
            });
    }

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

    private function checkGroup($validator, Request $request): void
    {
        $validator->after(function ($validator) use ($request) {
            $cat = DB::table('standard_categories')->where('id', $request->category_id)->first();
            if (! $cat) {
                return;
            }

            $groups = StandardCode::decodeGroups($cat->groups);

            if (! $groups) {
                $validator->errors()->add(
                    'category_id',
                    'Chất chuẩn được chọn chưa khai phân nhóm chuẩn trong Danh Mục nên chưa sinh được mã ống chuẩn.'
                );

                return;
            }

            $groupKey = $request->group_key ?: ($groups[0] ?? null);

            if (! in_array($groupKey, $groups, true)) {
                $validator->errors()->add(
                    'group_key',
                    'Nhóm chuẩn không nằm trong các nhóm đã khai cho chất chuẩn này.'
                );
            }
        });
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function departmentShortName(): string
    {
        return (string) (
            session('user')['selected_department']
            ?? DB::table('deparments')->where('id', $this->departmentId())->value('shortName')
            ?? ''
        );
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    private function rules(int $departmentId, bool $isCreate = true): array
    {
        $rules = [
            // Chưa khai chất chuẩn ở tab "Chất Chuẩn Của Phòng" thì không được nhập vào kho:
            // exists:standard_categories,id không thôi thì sửa request là nhập được chất của phòng khác
            'category_id' => [
                'required',
                Rule::exists('standard_department_categories', 'category_id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'invoice_number' => ['nullable', 'max:100'],
            'expiry_type' => ['required', Rule::in(['check online', 'retest', 'Specify', 'Requires_re-evaluation', 'defined', 'undetermined', 'unlimited'])],
            'expired_date' => [
                Rule::requiredIf(fn() => !in_array(request('expiry_type'), ['check online', 'undetermined', 'unlimited'])),
                'nullable',
                'date',
            ],
            'retest_interval_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'batch_no' => ['nullable', 'max:100'],
            'coa_no' => ['nullable', 'max:100'],
            'potency' => ['nullable', 'max:100'],
            'moisture' => ['nullable', 'max:100'],
            'standard_form' => ['nullable', Rule::in(['Dạng Bột Rời', 'Dạng Bột Mịn', 'Dạng Sệt'])],
            'weight_controlled' => ['nullable', 'boolean'],
            'requires_aliquot' => ['nullable', 'boolean'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purpose_id' => ['nullable'],
            'purpose_id.*' => ['exists:purposes,id'],
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'note' => ['nullable', 'max:500'],
            'attachments.*' => ['nullable', 'file', 'max:10240'], // 10MB max per file
        ];

        if ($isCreate) {
            $rules['quantity'] = ['nullable', 'integer', 'min:1', 'max:50'];
        }

        return $rules;
    }

    private function payload(Request $request): array
    {
        $cat = DB::table('standard_categories')->where('id', $request->category_id)->first();
        $groups = $cat ? StandardCode::decodeGroups($cat->groups) : [];
        $groupKey = $request->group_key ?: ($groups[0] ?? null);

        $expiryType = $request->input('expiry_type', 'Specify');
        if (in_array($expiryType, ['undetermined', 'unlimited', 'check_online'])) {
            $expiryType = 'check online';
        } elseif (in_array($expiryType, ['defined', 'specify'])) {
            $expiryType = 'Specify';
        } elseif (in_array($expiryType, ['requires_re-evaluation', 're_evaluation'])) {
            $expiryType = 'Requires_re-evaluation';
        }

        $expiredDate = null;
        $retestInterval = null;

        if ($expiryType === 'check online') {
            $expiredDate = null;
        } elseif ($expiryType === 'retest') {
            $expiredDate = $this->nullIfBlank($request->expired_date);
            $retestInterval = $request->retest_interval_months ? (int) $request->retest_interval_months : null;
        } else {
            // 'Specify' hoặc 'Requires_re-evaluation'
            $expiredDate = $this->nullIfBlank($request->expired_date);
        }

        $purposeInput = $request->input('purpose_id');
        $purposeJson = null;
        if (is_array($purposeInput)) {
            $filtered = array_values(array_filter(array_map('intval', $purposeInput)));
            $purposeJson = !empty($filtered) ? json_encode($filtered) : null;
        } elseif (!empty($purposeInput)) {
            $purposeJson = is_numeric($purposeInput) ? json_encode([(int) $purposeInput]) : $purposeInput;
        }

        return [
            'category_id' => (int) $request->category_id,
            'group_code' => StandardCode::groupCode($groupKey),
            'amount' => (float) $request->amount,
            'invoice_number' => $this->nullIfBlank($request->invoice_number),
            'expiry_type' => $expiryType,
            'expired_date' => $expiredDate,
            'retest_interval_months' => $retestInterval,
            'batch_no' => $this->nullIfBlank($request->batch_no),
            'coa_no' => $this->nullIfBlank($request->coa_no),
            'potency' => $this->nullIfBlank($request->potency),
            'moisture' => $this->nullIfBlank($request->moisture),
            'weight_controlled' => $request->has('weight_controlled') && $request->weight_controlled ? 1 : 0,
            'standard_form' => ($request->has('weight_controlled') && $request->weight_controlled) ? $this->nullIfBlank($request->standard_form) : null,
            'requires_aliquot' => $request->has('requires_aliquot') && $request->requires_aliquot ? 1 : 0,
            'supplier_id' => $request->supplier_id ? (int) $request->supplier_id : null,
            'purpose_id' => $purposeJson,
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
            'category_id.required' => 'Vui lòng chọn chất chuẩn cần nhập.',
            'category_id.exists' => 'Chất chuẩn được chọn chưa được phòng khai ở tab "Chất Chuẩn Của Phòng" nên không nhập vào kho được.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'quantity.integer' => 'Số ống cần nhập phải là số nguyên.',
            'quantity.min' => 'Số ống cần nhập tối thiểu là 1.',
            'quantity.max' => 'Số ống cần nhập tối đa là 50 ống trong một lần.',
            'invoice_number.max' => 'Số hoá đơn tối đa 100 ký tự.',
            'expired_date.date' => 'Hạn sử dụng không hợp lệ.',
            'retest_interval_months.integer' => 'Khoảng thời gian retest phải là số nguyên (tháng).',
            'retest_interval_months.min' => 'Khoảng thời gian retest tối thiểu 1 tháng.',
            'batch_no.max' => 'Số lô tối đa 100 ký tự.',
            'coa_no.max' => 'Số phiếu kiểm nghiệm gốc tối đa 100 ký tự.',
            'potency.max' => 'Hàm lượng tối đa 100 ký tự.',
            'moisture.max' => 'Độ ẩm tối đa 100 ký tự.',
            'supplier_id.exists' => 'Nhà cung cấp được chọn không tồn tại.',
            'location_id.exists' => 'Vị trí lưu trữ không thuộc phòng ban đang chọn.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
            'attachments.*.max' => 'Mỗi file đính kèm không được vượt quá 10MB.',
        ];
    }
}
