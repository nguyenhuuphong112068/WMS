<?php

namespace App\Http\Controllers\Pages\Import;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\AttachmentBackup;
use App\Support\DepartmentMaterial;
use App\Support\ListRange;
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
 * MÃ LÔ VẬT TƯ sinh tự động: "M" + id phòng ban (2 chữ số) + đuôi ngẫu nhiên, ví dụ
 * M-07-7KPMR9J4WD. Mọi mã dài bằng nhau. Không còn số thứ tự nên xoá phiếu không lộ
 * khoảng trống trên giao diện. Công thức nằm ở App\Support\MaterialCode.
 *
 * Phiếu nhập chỉ khoá (deActive) chứ không xoá cứng, để mã lô không bị cấp lại.
 * Vật tư là hàng tiêu hao nên phiếu nhập gọn: không có nhóm chuẩn, nhà cung cấp,
 * hàm lượng / độ ẩm, hạn dùng nội bộ. Hạn sử dụng có thể để trống.
 *
 * Số lô, số hoá đơn, ngày ký hoá đơn, mục đích sử dụng đều là thuộc tính KHÔNG bắt
 * buộc của phiếu nhập (batch_no, invoice_number, invoice_date, purpose).
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
        'batch_no' => 'Số lô',
        'invoice_number' => 'Số hoá đơn',
        'invoice_date' => 'Ngày ký hoá đơn',
        'expired_date' => 'Hạn sử dụng',
        'location_id' => 'Vị trí lưu trữ',
        'purpose' => 'Mục đích sử dụng',
        'note' => 'Ghi chú',
    ];

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        // Sổ nhập chỉ lấy đúng một trang trong khoảng ngày đang lọc (mặc định 30 ngày
        // gần nhất), không nạp toàn bộ phiếu nhập của phòng như trước.
        $bookRange = ListRange::of($request, 'book_');
        $bookKeyword = ListRange::keyword($request, 'book_');

        $datas = DB::table(self::TABLE)
            ->leftJoin('material_categories', self::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, self::TABLE.'.category_id'))
            ->tap(fn ($query) => DepartmentMaterial::join($query, $departmentId, self::TABLE.'.category_id'))
            ->leftJoin('locations', self::TABLE.'.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->leftJoin('columns', 'locations.column_id', '=', 'columns.id')
            ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id')
            ->select(
                self::TABLE.'.*',
                'material_categories.code as category_code',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'material_categories.classification',
                DepartmentMaterial::minStockColumn(),
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code',
                'locations.zone_type as location_zone_type',
                'locations.color as location_color',
                'warehouses.name as warehouse_name',
                'shelves.name as shelf_name',
                'columns.name as column_name',
                'tiers.name as tier_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->tap(ListRange::dateFilter(self::TABLE.'.imported_date', $bookRange))
            ->tap(ListRange::search([
                self::TABLE.'.code',
                self::TABLE.'.batch_no',
                self::TABLE.'.invoice_number',
                'material_names.name',
                'material_categories.technical_specification',
                'locations.code',
                self::TABLE.'.note',
            ], $bookKeyword))
            ->orderBy(self::TABLE.'.imported_date', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->paginate(ListRange::perPage($request, 'book_'), ['*'], ListRange::pageName('book_'))
            ->withQueryString();

        session()->put(['title' => 'NHẬP - NHẬP VẬT TƯ']);

        $categories = DepartmentMaterial::importCategoryOptions($departmentId);

        // Tồn hiện tại + ngưỡng tối đa của phòng, để modal cảnh báo khi nhập quá nhiều
        $maxStock = \App\Support\MaxStockWarning::forMaterial($departmentId);

        $categoryDefaults = $categories->mapWithKeys(function ($category) use ($maxStock) {
            $info = [
                'Tên: <strong>'.htmlspecialchars($category->material_name ?: '—').'</strong>',
                'NSX: <strong>'.htmlspecialchars($category->manufacturer_short_name ?: $category->manufacturer_name ?: '—').'</strong>',
                'Đơn vị phòng: <strong>'.htmlspecialchars($category->unit_short_name ?: 'Chưa thiết lập').'</strong>',
            ];
            $classification = \App\Support\MaterialClassification::summary($category->classification);
            if ($classification !== '') {
                $info[] = 'Phân loại: <strong>'.htmlspecialchars($classification).'</strong>';
            }
            if ($category->technical_specification) {
                $info[] = 'Quy cách: <strong>'.htmlspecialchars($category->technical_specification).'</strong>';
            }
            if ($category->min_stock !== null) {
                $info[] = 'Ngưỡng tồn: <strong>'.$this->number((float) $category->min_stock).' '.htmlspecialchars($category->unit_short_name ?: '').'</strong>';
            }

            $stock = $maxStock[$category->id] ?? ['max_stock' => null, 'on_hand' => 0, 'unit' => $category->unit_short_name ?: ''];

            $info[] = 'Tồn hiện tại: <strong>'.$this->number((float) $stock['on_hand']).' '.htmlspecialchars($stock['unit']).'</strong>';

            if ($stock['max_stock'] !== null) {
                $info[] = 'Ngưỡng tối đa: <strong>'.$this->number((float) $stock['max_stock']).' '.htmlspecialchars($stock['unit']).'</strong>';
            }

            return [$category->id => [
                'unit_short_name' => $category->unit_short_name,
                'min_stock' => $category->min_stock,
                // Ba khoá dưới đây để JS dựng cảnh báo vượt ngưỡng tồn tối đa
                'max_stock' => $stock['max_stock'],
                'on_hand' => $stock['on_hand'],
                'unit' => $stock['unit'],
                // Định khu phòng đã khai ở tab "Vật Tư Của Phòng" - điền sẵn ô vị trí lưu trữ
                'location_id' => $category->default_location_id,
                'info_html' => implode(' | ', $info),
            ]];
        })->toArray();

        $attachments = DB::table(self::ATTACHMENT_TABLE)
            ->whereIn('material_import_id', $datas->pluck('id'))
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('material_import_id');

        $pendingRows = $this->pendingRows($departmentId);

        return view('pages.import.MaterialImport.list', [
            'datas' => $datas,
            'categories' => $categories,
            'categoryDefaults' => $categoryDefaults,
            'attachments' => $attachments,
            'locations' => DepartmentMaterial::locationOptions($departmentId),
            // Modal Nhập vật tư chỉ cho chọn vị trí Biệt Trữ/Chờ kiểm tra
            'quarantineLocations' => DepartmentMaterial::locationOptions($departmentId, true),
            'historyCounts' => $this->historyCounts($departmentId),
            'bookRange' => $bookRange,
            'bookKeyword' => $bookKeyword,
            'bookPerPage' => ListRange::perPage($request, 'book_'),
            // Tab "Chờ kiểm tra": lô vừa nhập, chưa cộng tồn, chờ bước Xác nhận kiểm tra
            'pendingRows' => $pendingRows,
            'pendingCount' => $pendingRows->count(),
        ]);
    }

    /**
     * Các lô đang CHỜ KIỂM TRA của phòng ban (is_checked = 0, còn hiệu lực).
     *
     * Không cắt theo khoảng ngày như sổ nhập: hàng chờ kiểm tra phải hiện hết cho tới
     * khi được xác nhận, dù nhập từ bao lâu trước.
     */
    private function pendingRows(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->leftJoin('material_categories', self::TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, self::TABLE.'.category_id'))
            ->leftJoin('locations', self::TABLE.'.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->select(
                self::TABLE.'.*',
                'material_categories.code as category_code',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                'manufacturers.name as manufacturer_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'locations.code as location_code',
                'locations.zone_type as location_zone_type',
                'locations.color as location_color',
                'warehouses.name as warehouse_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            // Chỉ lô CHƯA có kết luận; lô Không đạt đã trả hàng nên không chờ nữa
            ->where(self::TABLE.'.check_result', \App\Support\CheckStatus::PENDING)
            ->orderBy(self::TABLE.'.imported_date', 'asc')
            ->orderBy(self::TABLE.'.id', 'asc')
            ->get();
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

        DB::transaction(function () use ($request, $departmentId, $quantity, $uploadedFiles, &$createdCodes) {
            $payload = $this->payload($request);

            for ($i = 0; $i < $quantity; $i++) {
                $code = MaterialCode::next($departmentId);

                $id = DB::table(self::TABLE)->insertGetId($payload + [
                    'code' => $code,
                    'department_id' => $departmentId,
                    // Ngày nhập luôn là ngày thực hiện thao tác, người dùng không chỉnh được
                    'imported_date' => now()->format('Y-m-d'),
                    'imported_by' => $this->actor(),
                    'status_id' => 1,
                    // Nhập lần đầu luôn ở trạng thái "Chờ kiểm tra" - chỉ cộng vào tồn
                    // kho, được đề nghị/sử dụng sau khi xác nhận ở tab "Chờ kiểm tra"
                    'is_checked' => 0,
                    'checked_by' => null,
                    'checked_at' => null,
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

        $msg .= ' Lô đang ở trạng thái CHỜ KIỂM TRA, chưa cộng vào tồn kho - vào tab "Chờ kiểm tra" để xác nhận.';

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

        // Phiếu chưa kiểm tra thì định khu vẫn bị giới hạn trong khu Biệt Trữ/Chờ kiểm tra
        $rules = $this->rules($departmentId, false, (bool) $current->is_checked) + ['reason' => ['required', 'max:500']];
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

    /**
     * XÁC NHẬN KIỂM TRA một lô đang "Chờ kiểm tra" (check_result = 'pending').
     *
     * Hai kết quả, chọn ở modal Xác nhận kiểm tra - xem App\Support\CheckStatus:
     *
     *   ĐẠT (passed)      - bổ sung thông tin lần nhập đầu còn thiếu (số lô, hoá đơn,
     *                       hạn dùng, mục đích sử dụng) + ĐỊNH KHU LẠI vị trí lưu trữ
     *                       thật (bắt buộc), ghi is_checked = 1. Từ đây lô mới được
     *                       cộng vào tồn kho và mới được đề nghị / sử dụng.
     *
     *   KHÔNG ĐẠT (failed)- tương ứng TRẢ HÀNG: is_checked giữ 0 nên lô KHÔNG BAO GIỜ
     *                       vào tồn kho, không chọn để xuất được, cũng rời khỏi tab
     *                       Chờ kiểm tra. Bắt buộc ghi lý do, không hồi lại được.
     *
     * Cả hai trường hợp đều ghi người kiểm tra + thời điểm kiểm tra.
     */
    public function confirmCheck(Request $request)
    {
        $departmentId = $this->departmentId();

        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần xác nhận kiểm tra!');
        }

        if (\App\Support\CheckStatus::isFinal($current->check_result)) {
            return redirect()->back()->with(
                'error',
                'Mã xuất nhập '.$current->code.' đã kiểm tra trước đó rồi (tình trạng: '
                .\App\Support\CheckStatus::label($current->check_result).'), không kiểm tra lại được.'
            );
        }

        if (! $current->status_id) {
            return redirect()->back()->with('error', 'Mã xuất nhập '.$current->code.' đang bị khoá nên chưa xác nhận kiểm tra được.');
        }

        $passed = $request->input('check_result') === \App\Support\CheckStatus::PASSED;

        $validator = Validator::make(
            $request->all(),
            $this->confirmCheckRules($departmentId, $passed),
            $this->messages()
        );

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'checkErrors')->withInput();
        }

        $checkNote = $this->nullIfBlank($request->check_note);

        if ($passed) {
            // Đạt: nhận thông tin bổ sung + định khu vị trí lưu trữ thật
            $payload = [
                'batch_no' => $this->nullIfBlank($request->batch_no),
                'invoice_number' => $this->nullIfBlank($request->invoice_number),
                'invoice_date' => $this->nullIfBlank($request->invoice_date),
                'expired_date' => $this->nullIfBlank($request->expired_date),
                'location_id' => (int) $request->location_id,
                'purpose' => $this->nullIfBlank($request->purpose),
            ];
        } else {
            // Không đạt = trả hàng: giữ nguyên mọi thông tin của lô, chỉ ghi kết luận
            $payload = [];
        }

        // Phần thông tin được bổ sung / sửa lại ngay trong lúc kiểm tra, ghi vào lịch sử
        $changes = $payload ? $this->changeNote($current, $payload) : '';

        $note = $passed
            ? 'Kiểm tra ĐẠT, lô được nhập kho và cộng vào tồn.'
                .($changes !== '' ? ' Bổ sung: '.$changes : '')
            : 'Kiểm tra KHÔNG ĐẠT - trả hàng, lô không được nhập kho.';

        if ($checkNote !== null) {
            $note .= ' Kết luận: '.$checkNote;
        }

        DB::transaction(function () use ($current, $payload, $note, $passed, $checkNote) {
            DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
                // is_checked chỉ bật khi ĐẠT - lô không đạt không bao giờ vào tồn kho
                'is_checked' => $passed ? 1 : 0,
                'check_result' => $passed
                    ? \App\Support\CheckStatus::PASSED
                    : \App\Support\CheckStatus::FAILED,
                'check_note' => $checkNote,
                'checked_by' => $this->actor(),
                'checked_at' => now(),
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->writeHistory(
                (int) $current->id,
                $passed ? 'Kiểm tra Đạt' : 'Kiểm tra Không đạt',
                $note
            );
        });

        AuditTrialController::log(
            $passed ? 'Kiểm tra Đạt' : 'Kiểm tra Không đạt',
            self::TABLE,
            $current->id,
            $current->code,
            $note
        );

        return redirect()->back()->with(
            'success',
            $passed
                ? 'Mã xuất nhập '.$current->code.' đã KIỂM TRA ĐẠT! Lô đã được cộng vào tồn kho và sẵn sàng để đề nghị / sử dụng.'
                : 'Mã xuất nhập '.$current->code.' KHÔNG ĐẠT - lô được trả hàng và không nhập kho.'
        );
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
                    'Số lô' => $row->batch_no ?: '—',
                    'Hoá đơn' => $row->invoice_number ? $row->invoice_number.' ('.$date($row->invoice_date).')' : '—',
                    'Mục đích sử dụng' => $row->purpose ?: '—',
                    'Tình trạng' => \App\Support\CheckStatus::label($row->check_result)
                        .($row->checked_by ? ' - '.$row->checked_by : '')
                        .($row->checked_at ? ' ('.\Carbon\Carbon::parse($row->checked_at)->format('d/m/Y H:i').')' : '')
                        .($row->check_note ? ' | Kết luận: '.$row->check_note : ''),
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

    /**
     * ĐỔI TRẠNG THÁI FILE ĐÍNH KÈM - Đang sử dụng (1) <-> Ngưng sử dụng (0).
     *
     * File ngưng sử dụng vẫn mở xem được, chỉ hiển thị kèm nhãn trạng thái. Dùng chung
     * cho cả màn hình Tồn Kho Vật Tư (MaterialInventoryController gọi lại logic này).
     */
    public function toggleAttachmentStatus(Request $request)
    {
        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->join(self::TABLE, self::ATTACHMENT_TABLE.'.material_import_id', '=', self::TABLE.'.id')
            ->where(self::ATTACHMENT_TABLE.'.id', $request->id)
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->select(self::ATTACHMENT_TABLE.'.*', self::TABLE.'.code as import_code')
            ->first();

        if (! $attachment) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy file đính kèm.'], 404);
        }

        $wasActive = ! isset($attachment->is_active) || $attachment->is_active;
        $newActive = $wasActive ? 0 : 1;

        DB::table(self::ATTACHMENT_TABLE)->where('id', $attachment->id)->update([
            'is_active' => $newActive,
            'status_changed_by' => $this->actor(),
            'status_changed_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Đổi trạng thái tài liệu',
            self::TABLE,
            $attachment->material_import_id,
            $attachment->import_code,
            'File "'.$attachment->file_name.'": '
                .($wasActive ? 'Đang sử dụng' : 'Ngưng sử dụng').' -> '
                .($newActive ? 'Đang sử dụng' : 'Ngưng sử dụng')
        );

        return response()->json(['success' => true, 'is_active' => $newActive]);
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
            'batch_no' => $row->batch_no,
            'invoice_number' => $row->invoice_number,
            'invoice_date' => $row->invoice_date,
            'imported_date' => $row->imported_date,
            'imported_by' => $row->imported_by,
            'expired_date' => $row->expired_date,
            'location_id' => $row->location_id,
            'purpose' => $row->purpose,
            'note' => $row->note,
            'status_id' => $row->status_id,
            'is_checked' => $row->is_checked,
            'check_result' => $row->check_result,
            'check_note' => $row->check_note,
            'checked_by' => $row->checked_by,
            'checked_at' => $row->checked_at,
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
            // Bước Xác nhận kiểm tra chỉ gửi một phần cột (không có vật tư / số lượng),
            // cột không nằm trong payload nghĩa là không đụng tới - đừng báo đổi thành "—"
            if (! array_key_exists($field, $payload)) {
                continue;
            }

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

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    /**
     * $isChecked: trạng thái ĐANG LƯU của phiếu (false khi tạo mới - luôn "Chờ kiểm tra").
     * Phiếu CHƯA kiểm tra chỉ được định khu vào vị trí zone_type = 'quarantine' (Biệt
     * Trữ/Chờ kiểm tra); định khu vị trí thật chỉ thực hiện ở bước Xác nhận kiểm tra
     * (xem confirmCheckRules()).
     */
    private function rules(int $departmentId, bool $isCreate = true, bool $isChecked = false): array
    {
        $locationRule = Rule::exists('locations', 'id')->where('department_id', $departmentId)->where('status_id', 1);

        if (! $isChecked) {
            $locationRule->where('zone_type', 'quarantine');
        }

        $rules = [
            'category_id' => [
                'required',
                Rule::exists('material_department_categories', 'category_id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'batch_no' => ['nullable', 'max:100'],
            'invoice_number' => ['nullable', 'max:100'],
            'invoice_date' => ['nullable', 'date'],
            'expired_date' => ['nullable', 'date'],
            'location_id' => ['nullable', $locationRule],
            'purpose' => ['nullable', 'max:500'],
            'note' => ['nullable', 'max:500'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ];

        if ($isCreate) {
            $rules['quantity'] = ['nullable', 'integer', 'min:1', 'max:50'];
        }

        return $rules;
    }

    /**
     * Validate bước "Xác nhận kiểm tra".
     *
     * ĐẠT      : bắt buộc định khu vị trí lưu trữ thật (không còn giới hạn quarantine).
     * KHÔNG ĐẠT: hàng trả lại nên không cần định khu, nhưng BẮT BUỘC ghi lý do.
     */
    private function confirmCheckRules(int $departmentId, bool $passed): array
    {
        return [
            'check_result' => ['required', Rule::in(\App\Support\CheckStatus::RESULTS)],
            'location_id' => [
                $passed ? 'required' : 'nullable',
                Rule::exists('locations', 'id')->where('department_id', $departmentId)->where('status_id', 1),
            ],
            'check_note' => [$passed ? 'nullable' : 'required', 'max:500'],
            'batch_no' => ['nullable', 'max:100'],
            'invoice_number' => ['nullable', 'max:100'],
            'invoice_date' => ['nullable', 'date'],
            'expired_date' => ['nullable', 'date'],
            'purpose' => ['nullable', 'max:500'],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'category_id' => (int) $request->category_id,
            'amount' => (float) $request->amount,
            'batch_no' => $this->nullIfBlank($request->batch_no),
            'invoice_number' => $this->nullIfBlank($request->invoice_number),
            'invoice_date' => $this->nullIfBlank($request->invoice_date),
            'expired_date' => $this->nullIfBlank($request->expired_date),
            'location_id' => $request->location_id ? (int) $request->location_id : null,
            'purpose' => $this->nullIfBlank($request->purpose),
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
            'batch_no.max' => 'Số lô tối đa 100 ký tự.',
            'invoice_number.max' => 'Số hoá đơn tối đa 100 ký tự.',
            'invoice_date.date' => 'Ngày ký hoá đơn không hợp lệ.',
            'expired_date.date' => 'Hạn sử dụng không hợp lệ.',
            'check_result.required' => 'Vui lòng chọn kết quả kiểm tra: Đạt hoặc Không đạt.',
            'check_result.in' => 'Kết quả kiểm tra không hợp lệ.',
            'check_note.required' => 'Kiểm tra Không đạt thì bắt buộc ghi lý do / kết luận.',
            'check_note.max' => 'Kết luận kiểm tra tối đa 500 ký tự.',
            'location_id.required' => 'Vui lòng định khu vị trí lưu trữ thật trước khi xác nhận kiểm tra Đạt.',
            'location_id.exists' => 'Vị trí lưu trữ không hợp lệ: phải thuộc phòng ban đang chọn, và khi CHƯA kiểm tra thì chỉ chọn được vị trí Biệt Trữ/Chờ kiểm tra.',
            'purpose.max' => 'Mục đích sử dụng tối đa 500 ký tự.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
            'attachments.*.max' => 'Mỗi file đính kèm không được vượt quá 10MB.',
        ];
    }

    public function uploadAttachment(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'attachments' => 'required|array',
            'attachments.*' => 'file|max:20480',
        ]);

        $departmentId = $this->departmentId();
        $import = \Illuminate\Support\Facades\DB::table('material_imports')
            ->where('id', $request->id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $import) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy phiếu nhập.']);
        }

        $newFiles = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                    $fileType = $file->getClientMimeType() ?: $file->getClientOriginalExtension();
                    $path = $file->store('public/material_imports');
                    \App\Support\AttachmentBackup::copy($path, 'material_imports');

                    $attachmentId = \Illuminate\Support\Facades\DB::table('material_import_attachments')->insertGetId([
                        'material_import_id' => $import->id,
                        'file_name' => $originalName,
                        'file_path' => $path,
                        'file_size' => $fileSize,
                        'file_type' => $fileType,
                        'created_by' => $this->actor(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $newFiles[] = [
                        'id' => $attachmentId,
                        'file_name' => $originalName,
                        'created_by' => $this->actor(),
                        'created_at' => now()->format('d/m/Y H:i'),
                        'is_active' => 1,
                        'url' => route('pages.import.materialImport.downloadAttachment', ['id' => $attachmentId]),
                    ];
                }
            }
        }

        if (empty($newFiles)) {
            return response()->json(['success' => false, 'message' => 'Không có file nào được tải lên.']);
        }

        if (method_exists($this, 'writeHistory')) {
            $this->writeHistory((int) $import->id, 'Điều chỉnh', 'Đã đính kèm thêm ' . count($newFiles) . ' file.');
        }

        \App\Http\Controllers\Pages\AuditTrail\AuditTrialController::log(
            'Điều chỉnh',
            'material_imports',
            $import->id,
            $import->code,
            'Đính kèm thêm ' . count($newFiles) . ' file'
        );

        return response()->json(['success' => true, 'files' => $newFiles]);
    }
}
