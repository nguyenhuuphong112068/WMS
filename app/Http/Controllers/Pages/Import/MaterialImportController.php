<?php

namespace App\Http\Controllers\Pages\Import;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\AttachmentBackup;
use App\Support\DepartmentMaterial;
use App\Support\MaterialCode;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * NHẬP - NHẬP VẬT TƯ
 *
 * Ghi nhận từng lô vật tư nhập vào kho của phòng ban đang chọn.
 *
 * MÃ LÔ VẬT TƯ sinh tự động: "M" + shortName phòng ban + đuôi ngẫu nhiên, ví dụ
 * M-QC1-7KPMR9J4WD. Không còn số thứ tự nên xoá phiếu không lộ khoảng trống trên
 * giao diện. Công thức nằm ở App\Support\MaterialCode.
 *
 * Phiếu nhập chỉ khoá (deActive) chứ không xoá cứng, để mã lô không bị cấp lại.
 * Vật tư là hàng tiêu hao nên phiếu nhập gọn: không có nhóm chuẩn, số lô, nhà cung cấp,
 * số hoá đơn, hàm lượng / độ ẩm, hạn dùng nội bộ. Hạn sử dụng có thể để trống.
 *
 * NGÀY NHẬP luôn là ngày bấm Lưu (now()), không có ô cho người dùng chọn và cũng không
 * sửa được khi điều chỉnh phiếu. Chỉ Hạn sử dụng mới do người dùng nhập.
 */
class MaterialImportController extends Controller
{
    private const TABLE = 'material_imports';

    private const HISTORY_TABLE = 'material_import_histories';

    private const ATTACHMENT_TABLE = 'material_import_attachments';

    /** Thư mục lưu file đính kèm, dùng chung cho cả disk private lẫn bản sao lưu public/uploads/. */
    private const ATTACHMENT_FOLDER = 'material_imports';

    private const LABEL = 'phiếu nhập vật tư';

    /** Số nhãn tối đa cho một lần in, chặn cả ở trang in lẫn ở đây. */
    private const LABEL_MAX_COPIES = 100;

    /** Các trường được theo dõi khi điều chỉnh: cột => tên hiển thị trong lịch sử. */
    private const FIELDS = [
        'category_id' => 'Vật tư',
        'amount' => 'Số lượng',
        'expired_date' => 'Hạn sử dụng',
        'location_id' => 'Vị trí lưu trữ',
        'note' => 'Ghi chú',
    ];

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('material_categories', self::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, self::TABLE.'.category_id'))
            ->tap(fn ($query) => DepartmentMaterial::join($query, $departmentId, self::TABLE.'.category_id'))
            ->leftJoin('material_classifications', DepartmentMaterial::TABLE.'.classification_id', '=', 'material_classifications.id')
            ->leftJoin('locations', self::TABLE.'.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->select(
                self::TABLE.'.*',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'material_classifications.name as classification_name',
                DepartmentMaterial::minStockColumn(),
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code',
                'warehouses.name as warehouse_name',
                'rooms.name as room_name',
                'shelves.name as shelf_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy(self::TABLE.'.imported_date', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        session()->put(['title' => 'NHẬP - NHẬP VẬT TƯ']);

        $categories = DepartmentMaterial::importCategoryOptions($departmentId);

        $categoryDefaults = $categories->mapWithKeys(function ($category) {
            $info = [
                'Tên: <strong>'.htmlspecialchars($category->material_name ?: '—').'</strong>',
                'NSX: <strong>'.htmlspecialchars($category->manufacturer_short_name ?: $category->manufacturer_name ?: '—').'</strong>',
                'Đơn vị phòng: <strong>'.htmlspecialchars($category->unit_short_name ?: 'Chưa thiết lập').'</strong>',
            ];
            if ($category->classification_name) {
                $info[] = 'Phân loại: <strong>'.htmlspecialchars($category->classification_name).'</strong>';
            }
            if ($category->technical_specification) {
                $info[] = 'Quy cách: <strong>'.htmlspecialchars($category->technical_specification).'</strong>';
            }
            if ($category->min_stock !== null) {
                $info[] = 'Ngưỡng tồn: <strong>'.$this->number((float) $category->min_stock).' '.htmlspecialchars($category->unit_short_name ?: '').'</strong>';
            }

            return [$category->id => [
                'unit_short_name' => $category->unit_short_name,
                'min_stock' => $category->min_stock,
                'info_html' => implode(' | ', $info),
            ]];
        })->toArray();

        $attachments = DB::table(self::ATTACHMENT_TABLE)
            ->whereIn('material_import_id', $datas->pluck('id'))
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('material_import_id');

        return view('pages.import.MaterialImport.list', [
            'datas' => $datas,
            'categories' => $categories,
            'categoryDefaults' => $categoryDefaults,
            'attachments' => $attachments,
            'locations' => DepartmentMaterial::locationOptions($departmentId),
            'historyCounts' => $this->historyCounts($departmentId),
        ]);
    }

    /**
     * Trang in nhãn dán lô vật tư (mã QR). Mở tab mới, chọn số lượng nhãn rồi bấm In.
     *
     * Số lượng nhãn chọn ngay trên trang in (nhân bản nhãn bằng JS) nên không nạp lại
     * trang; lúc bấm In, trang gọi labelPrinted() để ghi audit log.
     */
    public function label(Request $request)
    {
        $departmentId = $this->departmentId();

        $row = DB::table(self::TABLE)
            ->leftJoin('material_categories', self::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, self::TABLE.'.category_id'))
            ->leftJoin('locations', self::TABLE.'.location_id', '=', 'locations.id')
            ->select(
                self::TABLE.'.*',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'units.short_name as unit_short_name',
                'locations.code as location_code'
            )
            ->where(self::TABLE.'.id', $request->id)
            ->where(self::TABLE.'.department_id', $departmentId)
            ->first();

        if (! $row) {
            abort(404, 'Không tìm thấy phiếu nhập vật tư.');
        }

        return view('pages.import.MaterialImport.label', [
            'import' => $row,
            'label' => config('material.label'),
            // ECC Q (25%): chịu được logo Stella đè giữa mã. border 1 module: vùng
            // trắng tối thiểu để QR gọn trong góc 1/4 nhãn.
            'qr' => QrCode::render($row->code, 'Q', 1),
            'maxCopies' => self::LABEL_MAX_COPIES,
        ]);
    }

    /**
     * GHI AUDIT LOG MỖI LẦN IN NHÃN VẬT TƯ.
     *
     * Trang in gọi vào đây ngay trước khi mở hộp thoại In, kể cả khi người dùng bấm
     * Ctrl+P thay vì nút In nhãn. Chỉ ghi nhật ký, không đụng vào dữ liệu phiếu nhập.
     *
     * Nhật ký lưu: in nhãn của vật tư nào (tên + mã xuất nhập), bao nhiêu nhãn và thời
     * điểm in - thời điểm chính là audittriallog.created_at, ghi thêm vào phần mô tả
     * cho dễ đọc trên màn hình Audit Trail.
     */
    public function labelPrinted(Request $request)
    {
        $departmentId = $this->departmentId();
        $copies = max(1, min(self::LABEL_MAX_COPIES, (int) $request->input('copies', 1)));

        $row = DB::table(self::TABLE)
            ->leftJoin('material_categories', self::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->select(self::TABLE.'.id', self::TABLE.'.code', 'material_names.name as material_name')
            ->where(self::TABLE.'.id', $request->id)
            // Chỉ ghi nhận in nhãn của lô thuộc phòng ban đang chọn
            ->where(self::TABLE.'.department_id', $departmentId)
            ->first();

        if (! $row) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy phiếu nhập vật tư cần in nhãn.'], 404);
        }

        $printedAt = now();

        AuditTrialController::log(
            'In nhãn',
            self::TABLE,
            $row->id,
            'NA',
            'In nhãn vật tư: '.($row->material_name ?: '(chưa có tên)')
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

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), $this->rules($departmentId, true), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $shortName = $this->departmentShortName();
        $quantity = max(1, min(50, (int) $request->input('quantity', 1)));

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

        $createdCodes = [];

        DB::transaction(function () use ($request, $departmentId, $shortName, $quantity, $uploadedFiles, &$createdCodes) {
            $payload = $this->payload($request);

            for ($i = 0; $i < $quantity; $i++) {
                $code = MaterialCode::next($shortName);

                $id = DB::table(self::TABLE)->insertGetId($payload + [
                    'code' => $code,
                    'department_id' => $departmentId,
                    // Ngày nhập luôn là ngày thực hiện thao tác, người dùng không chỉnh được
                    'imported_date' => now()->format('Y-m-d'),
                    'imported_by' => $this->actor(),
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($uploadedFiles as $f) {
                    DB::table(self::ATTACHMENT_TABLE)->insert($f + [
                        'material_import_id' => $id,
                        'created_by' => $this->actor(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->writeHistory($id, 'Thêm mới', 'Tạo mới phiếu nhập, mã xuất nhập '.$code.'.');
                $createdCodes[] = $code;

                AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Nhập vật tư, mã xuất nhập: '.$code);
            }
        });

        $msg = count($createdCodes) === 1
            ? 'Đã tạo '.self::LABEL.' mã xuất nhập '.$createdCodes[0].'!'
            : 'Đã tạo thành công '.count($createdCodes).' lô vật tư: '.implode(', ', $createdCodes).'!';

        return redirect()->back()->with('success', $msg);
    }

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

        $rules = $this->rules($departmentId, false) + ['reason' => ['required', 'max:500']];
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
                            'material_import_id' => $current->id,
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
            ($note ?: 'Cập nhật tài liệu đính kèm').' | Lý do: '.$reason
        );

        return redirect()->back()->with('success', 'Đã ghi nhận điều chỉnh '.self::LABEL.' '.$current->code.'!');
    }

    /** Lịch sử điều chỉnh của một phiếu nhập, trả JSON cho modal trên bảng. */
    public function history(Request $request)
    {
        $departmentId = $this->departmentId();

        $import = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $import) {
            return response()->json(['rows' => []]);
        }

        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('material_categories', self::HISTORY_TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, self::HISTORY_TABLE.'.category_id'))
            ->leftJoin('locations', self::HISTORY_TABLE.'.location_id', '=', 'locations.id')
            ->select(
                self::HISTORY_TABLE.'.*',
                'material_names.name as material_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code'
            )
            ->where(self::HISTORY_TABLE.'.material_import_id', $import->id)
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
                    'Vật tư' => $row->material_name ?: '—',
                    'Số lượng' => $this->number((float) $row->amount).' '.($row->unit_short_name ?: $row->unit_name ?: ''),
                    'Ngày nhập' => $date($row->imported_date),
                    'Hạn sử dụng' => $date($row->expired_date),
                    'Vị trí lưu trữ' => $row->location_code ?: '—',
                    'Trạng thái' => $row->status_id == 1 ? 'Hiệu lực' : 'Đã khoá',
                    'Ghi chú' => $row->note ?: '—',
                ],
            ]),
        ]);
    }

    public function downloadAttachment($id)
    {
        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->join(self::TABLE, self::ATTACHMENT_TABLE.'.material_import_id', '=', self::TABLE.'.id')
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
            ->join(self::TABLE, self::ATTACHMENT_TABLE.'.material_import_id', '=', self::TABLE.'.id')
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
            $attachment->material_import_id,
            $attachment->import_code,
            'Xoá file đính kèm: '.$attachment->file_name
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
                'Trạng thái: '.($current->status_id == 1 ? 'Hiệu lực' : 'Đã khoá')
                .' -> '.($newStatus == 1 ? 'Hiệu lực' : 'Đã khoá')
            );
        });

        AuditTrialController::log($action, self::TABLE, $current->id, 'status_id: '.$current->status_id, 'status_id: '.$newStatus);

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' '.$current->code.'!'
        );
    }

    /* ==========================================================
     |  HÀM DÙNG CHUNG
     ========================================================== */

    private function writeHistory(int $id, string $action, ?string $note, ?string $reason = null): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'material_import_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'category_id' => $row->category_id,
            'amount' => $row->amount,
            'imported_date' => $row->imported_date,
            'imported_by' => $row->imported_by,
            'expired_date' => $row->expired_date,
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
                $parts[] = $title.': '.$this->number((float) $old).' -> '.$this->number((float) $new);

                continue;
            }

            if ((string) $old === (string) $new) {
                continue;
            }

            if (isset($labels[$field])) {
                $parts[] = $title.': '.($labels[$field][$old] ?? '—').' -> '.($labels[$field][$new] ?? '—');

                continue;
            }

            $parts[] = $title.': '.($old === null || $old === '' ? '—' : $old).' -> '.($new === null || $new === '' ? '—' : $new);
        }

        return implode(' | ', $parts);
    }

    private function labelMaps(): array
    {
        return [
            'category_id' => DB::table('material_categories')
                ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
                ->pluck('material_names.name', 'material_categories.id')
                ->all(),
            'location_id' => DB::table('locations')->pluck('code', 'id')->all(),
        ];
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }

    private function historyCounts(int $departmentId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('material_import_id', DB::raw('COUNT(*) as times'))
            ->whereIn('material_import_id', function ($query) use ($departmentId) {
                $query->select('id')->from(self::TABLE)->where('department_id', $departmentId);
            })
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('material_import_id')
            ->pluck('times', 'material_import_id');
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
            'category_id' => [
                'required',
                Rule::exists('material_department_categories', 'category_id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'expired_date' => ['nullable', 'date'],
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')->where('department_id', $departmentId)->where('status_id', 1),
            ],
            'note' => ['nullable', 'max:500'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ];

        if ($isCreate) {
            $rules['quantity'] = ['nullable', 'integer', 'min:1', 'max:50'];
        }

        return $rules;
    }

    private function payload(Request $request): array
    {
        return [
            'category_id' => (int) $request->category_id,
            'amount' => (float) $request->amount,
            'expired_date' => $this->nullIfBlank($request->expired_date),
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
            'category_id.required' => 'Vui lòng chọn vật tư cần nhập.',
            'category_id.exists' => 'Vật tư được chọn chưa được phòng khai ở tab "Vật Tư Của Phòng" nên không nhập vào kho được.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'quantity.integer' => 'Số lô cần nhập phải là số nguyên.',
            'quantity.min' => 'Số lô cần nhập tối thiểu là 1.',
            'quantity.max' => 'Số lô cần nhập tối đa là 50 trong một lần.',
            'expired_date.date' => 'Hạn sử dụng không hợp lệ.',
            'location_id.exists' => 'Vị trí lưu trữ không thuộc phòng ban đang chọn.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
            'attachments.*.max' => 'Mỗi file đính kèm không được vượt quá 10MB.',
        ];
    }
}
