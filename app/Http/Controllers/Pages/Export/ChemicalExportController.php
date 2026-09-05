<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\CategoryUnitConversion;
use App\Support\DepartmentChemical;
use App\Support\UnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * SỬ DỤNG - SỬ DỤNG HOÁ CHẤT
 *
 * Ghi nhận từng lần lấy hoá chất ra khỏi kho từ một phiếu nhập cụ thể:
 * sử dụng cho công việc (type = export) hoặc huỷ bỏ (type = cancel).
 *
 * Mã xuất nhập không sinh mới - lấy đúng mã của phiếu nhập được xuất ra.
 * Phiếu chỉ khoá (deActive) chứ không xoá cứng; phiếu đã khoá không trừ tồn.
 *
 * Hai quy tắc của nghiệp vụ xuất:
 * - Chỉ được CHỌN phiếu nhập còn hạn sử dụng và còn tồn > 0.
 * - Được XUẤT VƯỢT tồn tối đa 5% để bù sai số cân đong. Phần vượt làm tồn bị âm,
 *   xử lý bằng chức năng Cân Đối ở màn hình Tồn Kho Hoá Chất.
 */
class ChemicalExportController extends Controller
{
    private const TABLE = 'chemical_exports';

    private const HISTORY_TABLE = 'chemical_export_histories';

    private const TRANSFER_REQUEST_TABLE = 'chemical_transfer_requests';

    private const TRANSFER_ITEM_TABLE = 'chemical_transfer_items';

    private const LABEL = 'phiếu sử dụng hoá chất';

    /** Các trường được theo dõi thay đổi, dùng làm nhãn trong lịch sử điều chỉnh. */
    private const FIELDS = [
        'code' => 'Mã xuất nhập',
        'amount' => 'Số lượng',
        'type' => 'Loại phiếu',
        'purpose' => 'Mục đích sử dụng',
        'test_report_no' => 'Số phiếu KN, OOS, BCSL',
        'checked_by' => 'Người kiểm tra',
    ];

    /** Sai số cho phép khi so số lượng xuất với tồn (cột decimal 15,4). */
    private const EPSILON = 0.00005;

    /** Được xuất vượt tồn còn lại tối đa ngần này (5%). */
    private const OVER_ISSUE_RATIO = 0.05;

    public const TYPES = [
        'export' => 'Sử dụng',
        'cancel' => 'Loại bỏ',
    ];

    /**
     * Loại phiếu CẤP PHÁT LIÊN PHÒNG BAN - không nằm trong TYPES vì không được chọn ở
     * form Sử Dụng chung, chỉ tạo được qua transferIssueStore(). Xem khối "ĐỀ NGHỊ
     * CHUYỂN HOÁ CHẤT LIÊN PHÒNG BAN" bên dưới.
     */
    private const TYPE_TRANSFER_OUT = 'transfer_out';

    /**
     * Loại phiếu HUỶ BỎ - bước 1 của nghiệp vụ huỷ hoá chất.
     *
     * Lập phiếu là "loại bỏ": hoá chất bị đánh dấu bỏ và trừ tồn ngay, nhưng CHƯA huỷ.
     * Phiếu rơi vào tab "Hoá chất chờ huỷ" để gom lại xin quyết định huỷ một lần
     * (bước 2, xem ChemicalDisposalController).
     */
    private const TYPE_CANCEL = 'cancel';

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('chemical_imports', self::TABLE.'.import_id', '=', 'chemical_imports.id')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            // Đơn vị tính khai ở danh mục hoá chất CỦA PHÒNG, không còn ở danh mục chung
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_imports.category_id'))
            // Phòng ban nhận, chỉ có ở phiếu cấp phát liên phòng ban (type = transfer_out)
            ->leftJoin('deparments', self::TABLE.'.to_department_id', '=', 'deparments.id')
            ->select(
                self::TABLE.'.*',
                'chemical_categories.code as category_code',
                'chemical_imports.category_id as category_id',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'chemical_imports.amount as import_amount',
                'chemical_imports.batch_no',
                'chemical_imports.expired_date',
                'deparments.name as to_department_name',
                'deparments.shortName as to_department_short',
                // Phiếu loại bỏ đã gom vào đợt huỷ nào, để khoá nút Sửa / Khoá trên bảng
                'chemical_disposals.code as disposal_code',
                'chemical_disposals.app_status as disposal_status'
            )
            ->leftJoin('chemical_disposals', self::TABLE.'.disposal_id', '=', 'chemical_disposals.id')
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy(self::TABLE.'.exported_date', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        session()->put(['title' => 'SỬ DỤNG - SỬ DỤNG HOÁ CHẤT']);

        [$from, $to] = $this->reportRange($request);

        // Đề nghị chuyển hoá chất LIÊN PHÒNG BAN: đã gửi đi (mình là A) / cần cấp phát (mình là B)
        $transfer = $this->transferRequestsData($departmentId);

        // Vị trí lưu CỦA CHÍNH PHÒNG MÌNH, dùng khi mình là A bấm Nhận (bước 3) - khác B
        // chọn hộ vị trí như cơ chế cũ, giờ luôn là phòng đang đăng nhập tự chọn cho mình.
        $transferOwnLocations = DepartmentChemical::locationOptions($departmentId);

        // Hoá chất phòng mình đã khai ở tab "Hoá Chất Của Phòng" - dùng để cảnh báo ngay
        // trên bảng khi có mục đang "chờ nhận" mà phòng mình chưa khai, thay vì để A bấm
        // Nhận xong mới báo lỗi.
        $declaredCategoryIds = DB::table(DepartmentChemical::TABLE)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->pluck('category_id')
            ->all();

        return view('pages.export.ChemicalExport.list', [
            'datas' => $datas,
            'categories' => $this->categoryOptions($departmentId),
            // Nhóm NĐ 24/2026 suy tự động theo mã danh mục (thay cột classification đã bỏ)
            'classificationCodes' => \App\Support\ChemicalClassification::codesByCategory(),
            'classificationLabels' => \App\Support\ChemicalClassification::labels(),
            'transferSent' => $transfer['sent'],
            'transferReceived' => $transfer['received'],
            'transferItems' => $transfer['items'],
            'transferDepartments' => $this->departmentOptions($departmentId),
            'transferOwnLocations' => $transferOwnLocations,
            'declaredCategoryIds' => $declaredCategoryIds,
            'currentDepartmentId' => $departmentId,
            'imports' => $this->importOptions($departmentId),
            'checkers' => $this->checkerOptions($departmentId),
            'units' => DB::table('units')->where('status_id', 1)->orderBy('name')->get(),
            'types' => self::TYPES,
            'overIssuePercent' => (int) round(self::OVER_ISSUE_RATIO * 100),
            'adjustCounts' => $this->adjustCounts($departmentId),
            'report' => $this->usageReport($departmentId, $from, $to),
            'reportFrom' => $from,
            'reportTo' => $to,
            // Bước 2 của nghiệp vụ huỷ: hàng chờ huỷ và các đợt xin quyết định huỷ
            'waitingDisposal' => ChemicalDisposalController::waiting($departmentId),
            'disposals' => ChemicalDisposalController::batches($departmentId),
            'disposalStatuses' => ChemicalDisposalController::STATUSES,
            'disposalMethods' => ChemicalDisposalController::METHODS,
            'disposalExecutors' => ChemicalDisposalController::EXECUTORS,
            // Các đợt Lưu Tạm từ picker "Chọn Nhiều Từ Tồn Kho", chờ Dùng Ngay hoặc xoá
            'drafts' => $this->drafts($departmentId),
            // Lọc xong thì trang tải lại, quay về đúng tab thay vì tab sổ. Các action Phiếu
            // Tạm là POST + redirect()->back() (không đổi URL) nên tự flash activeTab qua
            // session, dùng làm phương án dự phòng khi không có ?tab= trên URL.
            'activeTab' => in_array($request->input('tab'), ['report', 'request', 'disposal', 'draft'], true)
                ? $request->input('tab')
                : (in_array(session('activeTab'), ['report', 'request', 'disposal', 'draft'], true)
                    ? session('activeTab')
                    : 'book'),
        ]);
    }

    /**
     * TRA LÔ THEO MÃ XUẤT NHẬP - quét mã vạch trên nhãn hoặc gõ tay mã.
     *
     * Trả về đúng lô của phòng ban đang đứng kèm cờ có xuất được hay không. Điều kiện
     * xuất được lấy nguyên từ importOptions() - cùng một nguồn với ô chọn phiếu nhập
     * trên form, để quét mã và chọn tay không bao giờ cho hai kết quả khác nhau.
     */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->code);

        if ($code === '') {
            return response()->json(['ok' => false, 'reason' => 'Vui lòng quét mã vạch trên nhãn hoặc nhập mã xuất nhập.']);
        }

        $import = $this->importOptions($this->departmentId())->firstWhere('code', $code);

        if (! $import) {
            return response()->json([
                'ok' => false,
                'reason' => 'Không tìm thấy mã xuất nhập "'.$code.'" trong kho của phòng ban này, '
                    .'hoặc phiếu nhập đã bị khoá.',
            ]);
        }

        return response()->json([
            'ok' => (bool) $import->selectable,
            'id' => $import->id,
            'code' => $import->code,
            'chem_name' => $import->chem_name ?: '—',
            'category_code' => $import->category_code ?: '—',
            'batch_no' => $import->batch_no ?: '—',
            'remaining' => $this->number($import->remaining).' '.($import->unit_short_name ?: ''),
            'expired_date' => $import->expired_date
                ? \Carbon\Carbon::parse($import->expired_date)->format('d/m/Y')
                : '—',
            'reason' => $import->selectable ? null : $this->notSelectableReason($import),
        ]);
    }

    /** Vì sao một lô không xuất được, viết đúng cách xử lý tiếp theo cho người dùng. */
    private function notSelectableReason($import): string
    {
        if ($import->expired) {
            return 'Lô '.$import->code.' đã hết hạn sử dụng ngày '
                .\Carbon\Carbon::parse($import->expired_date)->format('d/m/Y')
                .' nên không xuất ra sử dụng được.';
        }

        if ($import->waiting_internal) {
            return 'Lô '.$import->code.' chưa xác định hạn dùng nội bộ. Vào màn hình Tồn Kho Hoá Chất, '
                .'tab "Chưa Xác Định Hạn Nội Bộ" để xác định trước.';
        }

        return 'Lô '.$import->code.' đã hết tồn, vui lòng quét lô khác.';
    }

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $import = $this->findImport($request->import_id, $departmentId);

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());
        $this->checkImport($validator, $request, $import);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request, $import) + [
            'department_id' => $departmentId,
            // Ngày sử dụng là ngày bấm Lưu, người dùng không chọn được
            'exported_date' => now()->format('Y-m-d'),
            // Người sử dụng luôn là người đang đăng nhập, không nhận giá trị từ form
            'exported_by' => $this->actor(),
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logHistory($id, 'Thêm mới');

        AuditTrialController::log(
            'Thêm mới',
            self::TABLE,
            $id,
            'NA',
            self::TYPES[$request->type].' hoá chất, mã xuất nhập: '.$import->code.', số lượng: '.$request->amount
        );

        return redirect()->back()->with('success', 'Đã ghi nhận '.self::LABEL.' cho phiếu nhập '.$import->code.'!');
    }

    /**
     * SỬ DỤNG HOÁ CHẤT - chọn nhiều lô từ picker "Tồn Kho Của Phòng" rồi ghi cùng lúc.
     *
     * Loại Phiếu và Số PKN/OOS/BCSL là LỰA CHỌN CHUNG cho cả đợt (chọn trước khi mở
     * picker), chỉ Số Lượng / Người Kiểm Tra / Mục Đích là riêng từng dòng. Không còn
     * Chuyển kho ở luồng này - hàng chuyển kho có nghiệp vụ khác (chuyển tồn sang
     * phòng ban khác) sẽ làm lại theo hướng đề nghị + cấp phát liên phòng ban.
     *
     * mode = 'save' : chỉ áp dụng cho loại Sử dụng - CHƯA trừ kho, gom vào
     *                 chemical_export_drafts (tab Phiếu Tạm) để xử lý tiếp sau.
     * mode = 'use'  : ghi thẳng vào chemical_exports như store() từng phiếu một.
     *
     * Loại bỏ LUÔN trừ kho ngay bất kể mode: đây là trừ tồn thật ngay khi lập phiếu,
     * không phải "dự định lấy hàng" nên không có khái niệm lưu tạm.
     */
    public function storeBatch(Request $request)
    {
        $departmentId = $this->departmentId();

        // Không còn checkbox: mỗi khoá trong items[] là một dòng người dùng đã thêm
        // qua picker / quét mã, không cần lọc lại.
        $picked = [];
        foreach ((array) $request->input('items', []) as $importId => $row) {
            $picked[(int) $importId] = (array) $row;
        }

        if (! $picked) {
            return redirect()->back()->with('error', 'Vui lòng chọn ít nhất một hoá chất từ tồn kho của phòng.');
        }

        $type = $request->input('type') === 'cancel' ? 'cancel' : 'export';
        $testReportNo = $type === 'cancel' ? $this->nullIfBlank($request->input('test_report_no')) : null;
        $asDraft = $request->input('mode') === 'save' && $type === 'export';

        $batchCode = 'TAM-'.now()->format('ymdHis').'-'.strtoupper(Str::random(4));
        $savedCount = 0;
        $usedCount = 0;

        try {
            DB::transaction(function () use ($picked, $departmentId, $type, $testReportNo, $asDraft, $batchCode, &$savedCount, &$usedCount) {
                foreach ($picked as $importId => $row) {
                    $import = $this->findImport($importId, $departmentId);

                    if ($asDraft) {
                        if (! $import) {
                            $validator = Validator::make([], []);
                            $validator->errors()->add("items.$importId.import_id", 'Phiếu nhập không tồn tại hoặc đã bị khoá.');
                            throw new ValidationException($validator);
                        }

                        if (! is_numeric($row['amount'] ?? null) || (float) $row['amount'] <= 0) {
                            $validator = Validator::make([], []);
                            $validator->errors()->add("items.$importId.amount", 'Số lượng phải lớn hơn 0.');
                            throw new ValidationException($validator);
                        }

                        DB::table('chemical_export_drafts')->insert([
                            'batch_code' => $batchCode,
                            'department_id' => $departmentId,
                            'import_id' => $importId,
                            'amount' => (float) $row['amount'],
                            'purpose' => $this->nullIfBlank($row['purpose'] ?? null),
                            'checked_by' => $this->nullIfBlank($row['checked_by'] ?? null),
                            'created_by' => $this->actor(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $savedCount++;

                        continue;
                    }

                    $itemRequest = Request::create('/', 'POST', array_merge($row, [
                        'import_id' => $importId,
                        'type' => $type,
                        'test_report_no' => $testReportNo,
                    ]));

                    $validator = Validator::make($itemRequest->all(), $this->rules($departmentId), $this->messages());
                    $this->checkImport($validator, $itemRequest, $import);

                    if ($validator->fails()) {
                        throw new ValidationException($validator);
                    }

                    $id = DB::table(self::TABLE)->insertGetId($this->payload($itemRequest, $import) + [
                        'department_id' => $departmentId,
                        'exported_date' => now()->format('Y-m-d'),
                        'exported_by' => $this->actor(),
                        'status_id' => 1,
                        'created_by' => $this->actor(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->logHistory($id, 'Thêm mới');

                    AuditTrialController::log(
                        'Thêm mới',
                        self::TABLE,
                        $id,
                        'NA',
                        self::TYPES[$type].' hoá chất, mã xuất nhập: '.$import->code.', số lượng: '.$row['amount']
                    );

                    $usedCount++;
                }
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator, 'createErrors')->withInput();
        }

        $parts = [];
        if ($usedCount) {
            $parts[] = 'ghi nhận '.$usedCount.' dòng';
        }
        if ($savedCount) {
            $parts[] = 'lưu tạm '.$savedCount.' dòng';
        }

        $summary = 'Đã '.implode(', ', $parts).'!';

        if ($type === 'cancel' && $request->input('mode') === 'save') {
            $summary .= ' Loại bỏ luôn trừ kho ngay nên đã ghi thẳng vào sổ, không nằm ở Phiếu Tạm.';
        }

        // Có lưu tạm thì đưa người dùng sang tab Phiếu Tạm để thấy ngay đợt vừa tạo;
        // dùng ngay hết thì để mặc định về tab Sổ, thấy luôn phiếu vừa ghi.
        return redirect()->back()->with('success', $summary)
            ->with('activeTab', $savedCount ? 'draft' : 'book');
    }

    public function update(Request $request)
    {
        $departmentId = $this->departmentId();

        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        if ($blocked = $this->transferOutGuard($current, 'cập nhật')) {
            return $blocked;
        }

        if ($blocked = $this->disposalGuard($current, 'cập nhật')) {
            return $blocked;
        }

        $import = $this->findImport($request->import_id, $departmentId);

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());
        // Bỏ qua chính bản ghi đang sửa khi tính tồn, nếu không số lượng cũ bị trừ hai lần.
        // Giữ nguyên phiếu nhập cũ thì không xét lại điều kiện hạn dùng / còn tồn,
        // phiếu đã ghi rồi, chỉ khi ĐỔI sang phiếu khác mới coi là một lần chọn mới.
        $this->checkImport($validator, $request, $import, (int) $current->id, (int) $current->import_id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request, $import);

        // Dựng mô tả thay đổi TRƯỚC khi ghi đè, lúc này còn cả giá trị cũ lẫn mới
        $note = $this->changeNote($current, $payload, $request->adjust_reason);

        if ($note === '') {
            return redirect()->back()->with('error', 'Không có thông tin nào thay đổi nên chưa cập nhật '.self::LABEL.'.');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->logHistory($current->id, 'Cập nhật', $note);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, $note);

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
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

        if ($blocked = $this->transferOutGuard($current, 'khoá / mở khoá')) {
            return $blocked;
        }

        if ($blocked = $this->disposalGuard($current, 'khoá / mở khoá')) {
            return $blocked;
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        // Mở khoá lại thì số lượng cũ phải còn nằm trong hạn mức xuất của phiếu nhập
        if ($newStatus == 1) {
            $import = DB::table('chemical_imports')->where('id', $current->import_id)->first();
            $remaining = $import ? $this->remaining($import, (int) $current->id) : 0;

            if (! $import || (float) $current->amount > $this->maxIssuable($remaining, $import) + self::EPSILON) {
                return redirect()->back()->with(
                    'error',
                    'Không mở khoá được: phiếu nhập chỉ còn '.$this->number($remaining).' trong khi phiếu này cần '.$this->number((float) $current->amount).'.'
                );
            }
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $action = $newStatus == 1 ? 'Mở khoá' : 'Khoá';

        $this->logHistory(
            $current->id,
            $action,
            $action.' phiếu'.($request->filled('adjust_reason') ? '. Lý do: '.trim($request->adjust_reason) : '')
        );

        AuditTrialController::log(
            $action,
            self::TABLE,
            $current->id,
            'status_id: '.$current->status_id,
            'status_id: '.$newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' của phiếu nhập '.$current->code.'!'
        );
    }

    /**
     * Số lần ĐIỀU CHỈNH của từng phiếu: [export_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc lập phiếu chứ không phải một lần chỉnh sửa.
     * Badge trên nút Sửa chỉ hiện khi phiếu thật sự đã bị đổi ít nhất một lần.
     */
    private function adjustCounts(int $departmentId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('export_id', DB::raw('COUNT(*) as times'))
            ->whereIn('export_id', function ($query) use ($departmentId) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $departmentId);
            })
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('export_id')
            ->pluck('times', 'export_id');
    }

    /** Trả về lịch sử điều chỉnh của một phiếu sử dụng cho modal xem lịch sử. */
    public function history(Request $request)
    {
        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('chemical_imports', self::HISTORY_TABLE.'.import_id', '=', 'chemical_imports.id')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $this->departmentId(), 'chemical_imports.category_id'))
            ->select(
                self::HISTORY_TABLE.'.*',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            // Chỉ cho xem lịch sử của phiếu thuộc phòng ban đang chọn
            ->whereIn(self::HISTORY_TABLE.'.export_id', function ($query) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $this->departmentId());
            })
            ->where(self::HISTORY_TABLE.'.export_id', $request->id)
            ->orderBy(self::HISTORY_TABLE.'.id', 'desc')
            ->get();

        return response()->json([
            'rows' => $rows->map(function ($row) {
                $unit = $row->unit_short_name ?: $row->unit_name;

                return [
                    'action' => $row->action,
                    'change_note' => $row->change_note,
                    'created_by' => $row->created_by ?: 'NA',
                    'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                    'snapshot' => [
                        'Mã xuất nhập' => $row->code ?: '—',
                        'Hoá chất' => $row->chem_name ?: '—',
                        'Số lượng' => $row->amount !== null ? $this->number((float) $row->amount).' '.$unit : '—',
                        'Loại phiếu' => self::TYPES[$row->type] ?? ($row->type ?: '—'),
                        'Ngày sử dụng' => $row->exported_date ? \Carbon\Carbon::parse($row->exported_date)->format('d/m/Y') : '—',
                        'Người sử dụng' => $row->exported_by ?: '—',
                        'Người kiểm tra' => $row->checked_by ?: '—',
                        'Mục đích sử dụng' => $row->purpose ?: '—',
                        'Số phiếu KN, OOS, BCSL' => $row->test_report_no ?: '—',
                        'Trạng thái' => $row->status_id == 1 ? 'Hiệu lực' : 'Đã khoá',
                    ],
                ];
            })->values(),
        ]);
    }

    /**
     * Chụp lại giá trị của phiếu NGAY SAU khi thay đổi vào bảng lịch sử.
     *
     * Đọc lại từ DB thay vì dùng payload để ảnh chụp luôn khớp đúng những gì đã lưu.
     */
    private function logHistory($id, string $action, ?string $note = null): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'export_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'import_id' => $row->import_id,
            'amount' => $row->amount,
            'type' => $row->type,
            'exported_date' => $row->exported_date,
            'exported_by' => $row->exported_by,
            'purpose' => $row->purpose,
            'test_report_no' => $row->test_report_no,
            'checked_by' => $row->checked_by,
            'status_id' => $row->status_id,
            'change_note' => $note,
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    /**
     * Mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới".
     *
     * Trả về chuỗi rỗng khi không có gì đổi, để màn hình không ghi một dòng lịch sử trống.
     * Lý do người dùng nhập được đặt lên đầu chuỗi.
     */
    private function changeNote($current, array $payload, ?string $reason = null): string
    {
        $parts = [];

        foreach (self::FIELDS as $field => $title) {
            $old = $current->$field;
            $new = $payload[$field] ?? null;

            // amount lấy từ DB là chuỗi "10.5000" còn form gửi "10.5", phải so theo giá trị số
            if ($field === 'amount') {
                if (abs((float) $old - (float) $new) < self::EPSILON) {
                    continue;
                }

                $parts[] = $title.': '.$this->number((float) $old).' -> '.$this->number((float) $new);

                continue;
            }

            if ((string) $old === (string) $new) {
                continue;
            }

            if ($field === 'type') {
                $parts[] = $title.': '.(self::TYPES[$old] ?? '—').' -> '.(self::TYPES[$new] ?? '—');

                continue;
            }

            $parts[] = $title.': '.($old === null || $old === '' ? '—' : $old).' -> '.($new === null || $new === '' ? '—' : $new);
        }

        if (! $parts) {
            return '';
        }

        $reason = trim((string) $reason);

        return ($reason !== '' ? 'Lý do: '.$reason.' | ' : '').implode(' | ', $parts);
    }

    private function departmentName($id): string
    {
        return $id ? (DB::table('deparments')->where('id', $id)->value('name') ?: 'NA') : '—';
    }

    /**
     * Phiếu nhập của phòng ban đang chọn, còn hiệu lực.
     *
     * Kèm sẵn tồn còn lại / hạn mức xuất để form hiển thị mà không phải hỏi DB theo
     * từng phiếu. Cờ selectable = còn hạn dùng VÀ còn tồn > 0: modal Thêm mới chỉ
     * hiện phiếu selectable, modal Cập Nhật giữ cả phiếu không selectable để phiếu
     * xuất cũ còn chọn lại được đúng phiếu nhập của nó.
     */
    private function importOptions(int $departmentId)
    {
        $used = $this->sumByImport(self::TABLE, 'amount', $departmentId);
        $balanced = $this->sumByImport('chemical_balancings', 'balancing_amount', $departmentId);
        $today = now()->startOfDay();

        $query = DB::table('chemical_imports')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id');

        // Hạn dùng nội bộ và đơn vị tính lấy theo cấu hình của phòng ban đang chọn
        return DepartmentChemical::join($query, $departmentId, 'chemical_imports.category_id')
            ->leftJoin('units', DepartmentChemical::TABLE.'.unit_id', '=', 'units.id')
            // Định khu để hiện trên picker "Tồn Kho Của Phòng"
            ->leftJoin('locations', 'chemical_imports.location_id', '=', 'locations.id')
            ->select(
                'chemical_imports.id',
                'chemical_imports.code',
                'chemical_imports.category_id',
                'chemical_imports.amount',
                'chemical_imports.batch_no',
                'chemical_imports.expired_date',
                'chemical_imports.internal_expired_date',
                'chemical_imports.is_partial_lot',
                'chemical_categories.code as category_code',
                DepartmentChemical::shelfLifeColumn(),
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'locations.code as location_code'
            )
            ->where('chemical_imports.department_id', $departmentId)
            ->where('chemical_imports.status_id', 1)
            ->orderBy('chemical_imports.imported_date', 'desc')
            ->orderBy('chemical_imports.id', 'desc')
            ->get()
            ->map(function ($import) use ($used, $balanced, $today) {
                $import->used = (float) ($used[$import->id] ?? 0);
                $import->balanced = (float) ($balanced[$import->id] ?? 0);
                $import->remaining = max((float) $import->amount + $import->balanced - $import->used, 0);
                $import->max_amount = $this->maxIssuable($import->remaining, $import);

                $import->expired = $import->expired_date
                    && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt($today);

                // Hoá chất có hạn dùng mặc định mà chưa xác định hạn nội bộ thì chưa được dùng
                $import->waiting_internal = (int) ($import->shelf_life_months ?? 0) > 0
                    && ! $import->internal_expired_date;

                $import->selectable = ! $import->expired
                    && ! $import->waiting_internal
                    && $import->remaining > self::EPSILON;

                return $import;
            });
    }

    /**
     * Các đợt LƯU TẠM (chemical_export_drafts) của phòng ban đang chọn, gom theo
     * batch_code cho tab "Phiếu Tạm" - mỗi đợt hiện thành 1 nhóm dòng.
     */
    private function drafts(int $departmentId)
    {
        return DB::table('chemical_export_drafts')
            ->leftJoin('chemical_imports', 'chemical_export_drafts.import_id', '=', 'chemical_imports.id')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_imports.category_id'))
            ->select(
                'chemical_export_drafts.*',
                'chemical_imports.code as import_code',
                'chemical_imports.batch_no',
                'chemical_imports.expired_date',
                'chemical_categories.code as category_code',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where('chemical_export_drafts.department_id', $departmentId)
            ->orderBy('chemical_export_drafts.batch_code', 'desc')
            ->orderBy('chemical_export_drafts.id', 'asc')
            ->get()
            ->groupBy('batch_code');
    }

    /**
     * DÙNG NGAY một đợt Phiếu Tạm: kiểm tra lại hạn mức / tồn còn lại TẠI THỜI ĐIỂM
     * NÀY (có thể đã đổi từ lúc lưu tạm) rồi ghi thật vào chemical_exports, xoá dòng
     * tạm. Có dòng nào không hợp lệ thì không đổi gì, giữ nguyên đợt để người dùng
     * sửa (xoá dòng đó) rồi thử lại - không âm thầm bỏ qua dòng lỗi.
     */
    public function draftFinalize(Request $request)
    {
        $departmentId = $this->departmentId();

        $rows = DB::table('chemical_export_drafts')
            ->where('batch_code', $request->batch_code)
            ->where('department_id', $departmentId)
            ->get();

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'Không tìm thấy đợt Phiếu Tạm này, có thể đã được xử lý rồi.');
        }

        try {
            DB::transaction(function () use ($rows, $departmentId) {
                foreach ($rows as $row) {
                    $import = $this->findImport($row->import_id, $departmentId);

                    $itemRequest = Request::create('/', 'POST', [
                        'import_id' => $row->import_id,
                        'amount' => $row->amount,
                        'type' => 'export',
                        'purpose' => $row->purpose,
                        'checked_by' => $row->checked_by,
                    ]);

                    $validator = Validator::make($itemRequest->all(), $this->rules($departmentId), $this->messages());
                    $this->checkImport($validator, $itemRequest, $import);

                    if ($validator->fails()) {
                        throw new ValidationException($validator);
                    }

                    $id = DB::table(self::TABLE)->insertGetId($this->payload($itemRequest, $import) + [
                        'department_id' => $departmentId,
                        'exported_date' => now()->format('Y-m-d'),
                        'exported_by' => $this->actor(),
                        'status_id' => 1,
                        'created_by' => $this->actor(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->logHistory($id, 'Thêm mới');

                    AuditTrialController::log(
                        'Thêm mới',
                        self::TABLE,
                        $id,
                        'NA',
                        'Sử dụng hoá chất (từ Phiếu Tạm), mã xuất nhập: '.$import->code.', số lượng: '.$row->amount
                    );

                    DB::table('chemical_export_drafts')->where('id', $row->id)->delete();
                }
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator, 'draftErrors')
                ->with('draftErrorBatch', $request->batch_code)
                ->with('activeTab', 'draft');
        }

        return redirect()->back()->with(
            'success',
            'Đã ghi nhận '.self::LABEL.' cho '.$rows->count().' dòng từ Phiếu Tạm!'
        )->with('activeTab', 'draft');
    }

    /** Xoá một dòng khỏi Phiếu Tạm (chưa từng trừ kho nên xoá cứng, không cần khoá). */
    public function draftDeleteItem(Request $request)
    {
        $deleted = DB::table('chemical_export_drafts')
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->delete();

        return redirect()->back()->with(
            $deleted ? 'success' : 'error',
            $deleted ? 'Đã xoá dòng khỏi Phiếu Tạm.' : 'Không tìm thấy dòng cần xoá.'
        )->with('activeTab', 'draft');
    }

    /** Xoá cả một đợt Phiếu Tạm. */
    public function draftDeleteBatch(Request $request)
    {
        $deleted = DB::table('chemical_export_drafts')
            ->where('batch_code', $request->batch_code)
            ->where('department_id', $this->departmentId())
            ->delete();

        return redirect()->back()->with(
            $deleted ? 'success' : 'error',
            $deleted ? 'Đã xoá cả đợt Phiếu Tạm ('.$deleted.' dòng).' : 'Không tìm thấy đợt cần xoá.'
        )->with('activeTab', 'draft');
    }

    /**
     * Khoảng thời gian của báo cáo sử dụng.
     *
     * Mặc định từ đầu tháng hiện tại đến hôm nay. Người dùng nhập ngược ngày thì
     * đảo lại cho đúng thay vì trả về báo cáo rỗng.
     *
     * @return array{0: string, 1: string}
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
     * BÁO CÁO SỬ DỤNG HOÁ CHẤT THEO KHOẢNG THỜI GIAN.
     *
     * Cộng dồn các phiếu sử dụng còn hiệu lực trong khoảng ngày, gom theo
     * chemical_categories.code (mã danh mục hoá chất). Mỗi dòng có:
     * - Số lượng theo đơn vị phòng đã khai cho hoá chất đó (chemical_department_categories.unit_id)
     * - Số lượng QUY ĐỔI SANG KG qua App\Support\UnitConverter
     *
     * Đơn vị nhóm đếm (chai, thùng...) không quy đổi tự động được, và đổi thể tích
     * sang khối lượng thì cần tỉ trọng d (g/ml) của hoá chất - thiếu thì để trống
     * kèm lý do thay vì hiện số sai.
     */
    private function usageReport(int $departmentId, string $from, string $to)
    {
        $kgUnit = DB::table('units')->where('short_name', 'kg')->first();

        // Chuỗi trong DB::raw là hằng, không ghép từ dữ liệu người dùng
        $rows = DB::table(self::TABLE)
            ->join('chemical_imports', self::TABLE.'.import_id', '=', 'chemical_imports.id')
            ->join('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_imports.category_id'))
            ->select(
                'chemical_categories.id as category_id',
                'chemical_categories.code as category_code',
                'chemical_categories.density',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'units.unit_group',
                'units.factor_to_base',
                DB::raw('SUM(CASE WHEN '.self::TABLE.".type = 'export' THEN ".self::TABLE.'.amount ELSE 0 END) as used'),
                DB::raw('SUM(CASE WHEN '.self::TABLE.".type = 'cancel' THEN ".self::TABLE.'.amount ELSE 0 END) as cancelled'),
                DB::raw('SUM('.self::TABLE.'.amount) as total'),
                DB::raw('COUNT(*) as times'),
                DB::raw('COUNT(DISTINCT '.self::TABLE.'.import_id) as code_count'),
                DB::raw('MAX('.self::TABLE.'.exported_date) as last_exported_date')
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->whereBetween(self::TABLE.'.exported_date', [$from, $to])
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

            $row->used = (float) $row->used;
            $row->cancelled = (float) $row->cancelled;
            $row->total = (float) $row->total;
            $row->unit = $row->unit_short_name ?: $row->unit_name;

            $check = $kgUnit
                ? UnitConverter::check($unit, $kgUnit, $density)
                : ['ok' => false, 'reason' => 'Chưa có đơn vị "kg" trong Dữ Liệu Gốc nên không quy đổi được.'];

            $row->convertible = $check['ok'];
            $row->convert_note = $check['reason'];

            $convert = fn ($value) => $check['ok'] && $kgUnit
                ? UnitConverter::convert($value, $unit, $kgUnit, $density)
                : null;

            $row->used_kg = $convert($row->used);
            $row->cancelled_kg = $convert($row->cancelled);
            $row->total_kg = $convert($row->total);

            return $row;
        });
    }

    /**
     * Phiếu cấp phát liên phòng ban (type = transfer_out) không sửa / khoá được ở form
     * chung: dòng nhập mới bên phòng nhận đã được tạo ngay trong cùng transaction lúc
     * cấp phát (transferIssueStore), sửa tay ở đây sẽ làm lệch sổ của phòng nhận.
     *
     * @return \Illuminate\Http\RedirectResponse|null null nghĩa là được phép đi tiếp
     */
    private function transferOutGuard($current, string $action)
    {
        if ($current->type !== self::TYPE_TRANSFER_OUT) {
            return null;
        }

        return redirect()->back()->with(
            'error',
            'Phiếu cấp phát liên phòng ban '.$current->code.' không '.$action.' được ở đây. '
            .'Đây là phiếu do tính năng "Đề nghị chuyển hoá chất liên phòng ban" tạo ra, chỉ sửa qua thao tác Nhận / Từ chối nhận ở tab đó.'
        );
    }

    /**
     * Phiếu loại bỏ đã được gom vào một đợt huỷ thì khoá lại, không cho sửa nữa.
     *
     * Số lượng, số lô và căn cứ loại bỏ trên phiếu chính là nội dung đã đưa vào hồ sơ
     * xin quyết định huỷ. Sửa phiếu sau khi gom là hồ sơ một đằng, dữ liệu một nẻo.
     * Đợt còn đang gom thì gỡ phiếu ra khỏi đợt là sửa lại được.
     *
     * @return \Illuminate\Http\RedirectResponse|null null nghĩa là được phép đi tiếp
     */
    private function disposalGuard($current, string $action)
    {
        if (! $current->disposal_id) {
            return null;
        }

        $batch = DB::table('chemical_disposals')->where('id', $current->disposal_id)->first();

        return redirect()->back()->with(
            'error',
            'Phiếu loại bỏ '.$current->code.' đã được gom vào đợt huỷ '.($batch->code ?? '')
            .' ('.(ChemicalDisposalController::STATUSES[$batch->app_status ?? ''] ?? 'không rõ').') nên không '
            .$action.' được nữa. '
            .(($batch->app_status ?? '') === 'draft'
                ? 'Vào tab "Hoá chất chờ huỷ", gỡ phiếu khỏi đợt rồi sửa.'
                : 'Đợt đã trình duyệt, danh sách phiếu phải giữ nguyên như hồ sơ đã gửi.')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ĐỀ NGHỊ CHUYỂN HOÁ CHẤT LIÊN PHÒNG BAN
    |--------------------------------------------------------------------------
    | Mô hình 1 bước, giống hệt "Đề nghị cấp phát chuẩn liên phòng ban"
    | (StandardExportController::transferIssueStore): B chọn phiếu nhập của mình +
    | cấp phát trực tiếp là CHUYỂN TỒN THẬT ngay - trừ tồn phòng nguồn (B) bằng một
    | dòng chemical_exports type = 'transfer_out', cộng tồn phòng nhận (A) bằng một
    | dòng chemical_imports MỚI - không qua bước "đồng ý" hay "phòng nhận bấm Nhận"
    | riêng như cơ chế cũ.
    |
    | department_id của chemical_transfer_requests = phòng ĐỀ NGHỊ (A, cần hoá chất).
    | to_department_id                              = phòng ĐƯỢC ĐỀ NGHỊ (B, đang giữ hoá chất).
    */

    /**
     * PHÒNG A TẠO ĐỀ NGHỊ CHUYỂN HOÁ CHẤT LIÊN PHÒNG BAN
     */
    public function transferRequestStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'to_department_id' => ['required', 'exists:deparments,id', Rule::notIn([$departmentId])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:chemical_categories,id'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'to_department_id.required' => 'Vui lòng chọn phòng ban nguồn (đang giữ hoá chất).',
            'to_department_id.exists' => 'Phòng ban được chọn không tồn tại.',
            'to_department_id.not_in' => 'Không thể tạo đề nghị liên phòng ban gửi đến chính phòng mình.',
            'items.required' => 'Vui lòng thêm ít nhất một hoá chất đề nghị.',
            'items.min' => 'Vui lòng thêm ít nhất một hoá chất đề nghị.',
            'items.*.category_id.required' => 'Vui lòng chọn hoá chất.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'transferCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'request');
        }

        $actionType = $request->input('action_type', 'send');
        $isDraft = $actionType === 'draft';
        $status = $isDraft ? 'draft' : 'pending';
        $toDepartmentId = (int) $request->to_department_id;

        $code = $this->nextChemTransferCode($departmentId, $toDepartmentId);

        $listId = DB::table(self::TRANSFER_REQUEST_TABLE)->insertGetId([
            'code' => $code,
            'department_id' => $departmentId,
            'to_department_id' => $toDepartmentId,
            'status' => $status,
            'note' => $this->nullIfBlank($request->note),
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($request->items as $item) {
            DB::table(self::TRANSFER_ITEM_TABLE)->insert([
                'transfer_request_id' => $listId,
                'category_id' => (int) $item['category_id'],
                'requested_amount' => (float) $item['requested_amount'],
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'note' => $this->nullIfBlank($item['note'] ?? null),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $toDeptName = $this->departmentName($toDepartmentId);

        AuditTrialController::log(
            $isDraft ? 'Lưu tạm đề nghị chuyển hoá chất liên phòng ban' : 'Tạo đề nghị chuyển hoá chất liên phòng ban',
            self::TRANSFER_REQUEST_TABLE,
            $listId,
            'NA',
            ($isDraft ? 'Lưu tạm đề nghị ' : 'Tạo đề nghị chuyển liên phòng ban ').$code.' gửi đến '.$toDeptName.' ('.count($request->items).' mục)'
        );

        $msg = $isDraft
            ? 'Đã lưu tạm đề nghị chuyển hoá chất liên phòng ban '.$code.'! Bạn có thể gửi đề nghị khi sẵn sàng.'
            : 'Đã gửi đề nghị chuyển hoá chất liên phòng ban '.$code.' đến '.$toDeptName.' thành công!';

        return redirect()->route('pages.export.chemicalExport.list', ['tab' => 'request'])
            ->with('success', $msg);
    }

    /**
     * PHÒNG A CẬP NHẬT / ĐIỀU CHỈNH ĐỀ NGHỊ LIÊN PHÒNG BAN ĐÃ LƯU TẠM
     */
    public function transferRequestUpdate(Request $request)
    {
        $departmentId = $this->departmentId();
        $listId = (int) $request->transfer_request_id;

        $req = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $listId)
            ->where('department_id', $departmentId)
            ->first();

        if (! $req || $req->status !== 'draft') {
            return redirect()->back()->with('error', 'Chỉ có thể điều chỉnh phiếu đề nghị đang ở trạng thái Lưu tạm!');
        }

        $validator = Validator::make($request->all(), [
            'transfer_request_id' => ['required', 'exists:chemical_transfer_requests,id'],
            'to_department_id' => ['required', 'exists:deparments,id', Rule::notIn([$departmentId])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:chemical_categories,id'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'to_department_id.required' => 'Vui lòng chọn phòng ban nguồn (đang giữ hoá chất).',
            'to_department_id.not_in' => 'Không thể tạo đề nghị liên phòng ban gửi đến chính phòng mình.',
            'items.required' => 'Vui lòng thêm ít nhất một hoá chất đề nghị.',
            'items.min' => 'Vui lòng thêm ít nhất một hoá chất đề nghị.',
            'items.*.category_id.required' => 'Vui lòng chọn hoá chất.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'transferCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'request');
        }

        $actionType = $request->input('action_type', 'draft');
        $isDraft = $actionType === 'draft';
        $status = $isDraft ? 'draft' : 'pending';
        $toDepartmentId = (int) $request->to_department_id;

        DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $req->id)->update([
            'to_department_id' => $toDepartmentId,
            'status' => $status,
            'note' => $this->nullIfBlank($request->note),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        // Không xoá cứng: bỏ hiệu lực các mục cũ (active = 0) rồi thêm lại từ đầu.
        DB::table(self::TRANSFER_ITEM_TABLE)->where('transfer_request_id', $req->id)->update([
            'active' => 0,
            'updated_at' => now(),
        ]);

        foreach ($request->items as $item) {
            DB::table(self::TRANSFER_ITEM_TABLE)->insert([
                'transfer_request_id' => $req->id,
                'category_id' => (int) $item['category_id'],
                'requested_amount' => (float) $item['requested_amount'],
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'note' => $this->nullIfBlank($item['note'] ?? null),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $toDeptName = $this->departmentName($toDepartmentId);

        AuditTrialController::log(
            $isDraft ? 'Cập nhật đề nghị chuyển hoá chất liên phòng ban' : 'Gửi đề nghị chuyển hoá chất liên phòng ban sau cập nhật',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            'draft',
            ($isDraft ? 'Cập nhật đề nghị ' : 'Gửi đề nghị ').$req->code.' gửi đến '.$toDeptName.' ('.count($request->items).' mục)'
        );

        $msg = $isDraft
            ? 'Đã cập nhật lưu tạm đề nghị '.$req->code.' thành công!'
            : 'Đã cập nhật và gửi đề nghị '.$req->code.' thành công!';

        return redirect()->route('pages.export.chemicalExport.list', ['tab' => 'request'])
            ->with('success', $msg);
    }

    /**
     * GỬI ĐỀ NGHỊ LIÊN PHÒNG BAN ĐÃ LƯU TẠM
     */
    public function transferRequestSend(Request $request)
    {
        $listId = (int) $request->transfer_request_id;
        $req = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $listId)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $req || $req->status !== 'draft') {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị lưu tạm cần gửi!');
        }

        DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $req->id)->update([
            'status' => 'pending',
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DB::table(self::TRANSFER_ITEM_TABLE)->where('transfer_request_id', $req->id)->where('status', 'draft')->update([
            'status' => 'pending',
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Gửi đề nghị chuyển hoá chất liên phòng ban',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            'draft',
            'Gửi đề nghị chuyển liên phòng ban: '.$req->code
        );

        return redirect()->route('pages.export.chemicalExport.list', ['tab' => 'request'])
            ->with('success', 'Đã gửi đề nghị chuyển liên phòng ban mã '.$req->code.' thành công!');
    }

    /**
     * HUỶ ĐỀ NGHỊ LIÊN PHÒNG BAN ĐANG LƯU TẠM
     */
    public function transferRequestDestroy(Request $request)
    {
        $departmentId = $this->departmentId();

        $req = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $request->transfer_request_id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị này.')->with('activeTab', 'request');
        }

        if ($req->status !== 'draft') {
            return redirect()->back()->with('error', 'Chỉ có thể huỷ phiếu đang ở trạng thái Lưu tạm.')->with('activeTab', 'request');
        }

        DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $req->id)->update([
            'status' => 'canceled',
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Huỷ đề nghị chuyển hoá chất liên phòng ban',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            $req->code,
            'Đã huỷ đề nghị chuyển liên phòng ban đang lưu tạm'
        );

        return redirect()->back()->with('success', 'Đã huỷ phiếu đề nghị '.$req->code.' thành công!')->with('activeTab', 'request');
    }

    /**
     * PHÒNG B CẤP PHÁT CHO 1 MỤC ĐỀ NGHỊ LIÊN PHÒNG BAN (bước 2/3)
     *
     * Chỉ trừ tồn phiếu nhập nguồn tại B (chemical_exports, type = transfer_out) - CHƯA
     * tạo tồn cho A. Item chuyển sang status 'issued' (chờ nhận), is_partial_lot chốt
     * NGAY tại đây theo tình trạng lô nguồn lúc cấp phát (xem migration
     * add_receive_step_to_transfer_items) vì lô nguồn có thể phát sinh giao dịch khác
     * trong lúc A chưa nhận. Dòng chemical_imports thật cho A chỉ được tạo ở bước A bấm
     * Nhận (transferReceiveStore) - lúc đó mới chắc A đã khai danh mục + đơn vị tính.
     */
    public function transferIssueStore(Request $request)
    {
        $departmentId = $this->departmentId(); // B

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:chemical_transfer_items,id'],
            'import_id' => ['required', 'exists:chemical_imports,id'],
            'issued_amount' => ['required', 'numeric', 'min:0.0001'],
            'issued_unit' => ['nullable', 'string', 'max:50'],
        ], [
            'item_id.required' => 'Không tìm thấy mục đề nghị cần cấp phát.',
            'import_id.required' => 'Vui lòng chọn phiếu nhập trong kho để cấp phát.',
            'issued_amount.required' => 'Vui lòng nhập số lượng cấp phát.',
            'issued_amount.min' => 'Số lượng cấp phát phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }
            return redirect()->back()->withErrors($validator, 'transferIssueErrors')->withInput()->with('activeTab', 'request');
        }

        $item = DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $request->item_id)->where('active', 1)->where('status', 'pending')->first();
        $error = function (string $message) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return redirect()->back()->with('error', $message)->with('activeTab', 'request');
        };

        if (! $item) {
            return $error('Không tìm thấy mục đề nghị hoặc mục này đã được xử lý!');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $item->transfer_request_id)->first();
        if (! $transferReq || (int) $transferReq->to_department_id !== $departmentId) {
            return $error('Không tìm thấy phiếu đề nghị thuộc phòng ban này!');
        }

        $sourceImport = $this->findImport($request->import_id, $departmentId);
        if (! $sourceImport) {
            return $error('Không tìm thấy phiếu nhập trong kho phòng ban này!');
        }
        if ((int) $sourceImport->category_id !== (int) $item->category_id) {
            return $error('Phiếu nhập được chọn không đúng hoá chất của mục đề nghị!');
        }

        $isExpired = $sourceImport->expired_date && now()->startOfDay()->gt(\Carbon\Carbon::parse($sourceImport->expired_date));
        if ($isExpired) {
            return $error('Phiếu nhập '.$sourceImport->code.' đã hết hạn sử dụng, không được cấp phát!');
        }

        // Chuyển liên phòng ban KHÔNG được xuất vượt tồn (hàng chuyển đi thành tồn của
        // phòng nhận, cho vượt là tự sinh thêm hàng trong hệ thống)
        $remaining = $this->remaining($sourceImport);
        if ((float) $request->issued_amount > $remaining + self::EPSILON) {
            return $error('Phiếu nhập '.$sourceImport->code.' chỉ còn '.$this->number($remaining).', không đủ để cấp phát '.$this->number((float) $request->issued_amount).'.');
        }

        $fullTransfer = $this->isFullTransfer($sourceImport, $request->issued_amount);

        // Hoá chất có hạn dùng mặc định mà chưa xác định hạn nội bộ thì chưa được chuyển,
        // trừ khi chuyển NGUYÊN cả lô - lô chưa mở nên phòng nhận sẽ tự xác định.
        $waitingInternal = (int) ($sourceImport->shelf_life_months ?? 0) > 0
            && ! $sourceImport->internal_expired_date
            && ! $fullTransfer;
        if ($waitingInternal) {
            return $error('Phiếu nhập '.$sourceImport->code.' chưa xác định hạn dùng nội bộ, không được cấp phát lẻ. Chuyển nguyên cả lô thì không cần, phòng nhận sẽ tự xác định.');
        }

        $aDepartmentId = (int) $transferReq->department_id;
        $aDeptName = $this->departmentName($aDepartmentId);
        $issuedAt = now();

        $result = DB::transaction(function () use (
            $request, $item, $sourceImport, $departmentId, $aDepartmentId, $fullTransfer, $issuedAt
        ) {
            // Trừ tồn phòng nguồn (B) - tồn của A chờ đến khi A bấm Nhận mới được tạo
            $exportId = DB::table(self::TABLE)->insertGetId([
                'code' => $sourceImport->code,
                'import_id' => $sourceImport->id,
                'department_id' => $departmentId,
                'to_department_id' => $aDepartmentId,
                'transfer_item_id' => $item->id,
                'amount' => (float) $request->issued_amount,
                'type' => self::TYPE_TRANSFER_OUT,
                'exported_date' => $issuedAt->format('Y-m-d'),
                'exported_by' => $this->actor(),
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => $issuedAt,
                'updated_at' => $issuedAt,
            ]);

            DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $item->id)->update([
                'status' => 'issued',
                'import_id' => $sourceImport->id,
                'import_code' => $sourceImport->code,
                'issued_amount' => (float) $request->issued_amount,
                'issued_unit' => $this->nullIfBlank($request->issued_unit ?? $item->requested_unit),
                'issued_by' => $this->actor(),
                'issued_at' => $issuedAt,
                'is_partial_lot' => ! $fullTransfer,
                'updated_at' => $issuedAt,
            ]);

            $allItems = DB::table(self::TRANSFER_ITEM_TABLE)->where('transfer_request_id', $item->transfer_request_id)->where('active', 1)->get();

            DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $item->transfer_request_id)->update([
                'status' => $this->transferHeaderStatus($allItems),
                'updated_by' => $this->actor(),
                'updated_at' => $issuedAt,
            ]);

            return ['export_id' => $exportId];
        });

        AuditTrialController::log(
            'Cấp phát hoá chất liên phòng ban',
            self::TABLE,
            $result['export_id'],
            'NA',
            'Chuyển '.$sourceImport->code.' số lượng '.$request->issued_amount.' đến phòng '.$aDeptName.', chờ phòng nhận xác nhận.'
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cấp phát phiếu nhập '.$sourceImport->code.' thành công, chờ phòng '.$aDeptName.' xác nhận nhận hàng!',
                'data' => [
                    'issued_amount' => (float) $request->issued_amount,
                    'issued_unit' => $this->nullIfBlank($request->issued_unit ?? $item->requested_unit),
                    'issued_by' => $this->actor(),
                    'issued_at' => $issuedAt->format('d/m/Y H:i'),
                    'import_code' => $sourceImport->code,
                ],
            ]);
        }

        return redirect()->route('pages.export.chemicalExport.list', ['tab' => 'request'])
            ->with('success', 'Đã cấp phát phiếu nhập '.$sourceImport->code.' thành công, chờ phòng '.$aDeptName.' xác nhận nhận hàng!');
    }

    /**
     * PHÒNG A NHẬN HOÁ CHẤT ĐÃ ĐƯỢC CẤP PHÁT (bước 3/3)
     *
     * Đến đây mới thật sự tạo dòng chemical_imports cho A: bắt buộc A đã khai danh mục
     * hoá chất này ở tab "Hoá Chất Của Phòng" (mới có đơn vị tính để quy đổi qua
     * CategoryUnitConversion), rồi tự chọn vị trí lưu của phòng mình - khác cơ chế cũ
     * B chọn hộ vị trí cho A ngay lúc cấp phát.
     */
    public function transferReceiveStore(Request $request)
    {
        $departmentId = $this->departmentId(); // A

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:chemical_transfer_items,id'],
            'dest_location_id' => ['nullable'],
        ], [
            'item_id.required' => 'Không tìm thấy mục cần nhận.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }
            return redirect()->back()->withErrors($validator, 'transferReceiveErrors')->withInput()->with('activeTab', 'request');
        }

        $item = DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $request->item_id)->where('active', 1)->where('status', 'issued')->first();
        $error = function (string $message) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return redirect()->back()->with('error', $message)->with('activeTab', 'request');
        };

        if (! $item) {
            return $error('Không tìm thấy mục cần nhận hoặc mục này đã được xử lý!');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $item->transfer_request_id)->where('department_id', $departmentId)->first();
        if (! $transferReq) {
            return $error('Không tìm thấy phiếu đề nghị thuộc phòng ban này!');
        }

        $sourceImport = DB::table('chemical_imports')->where('id', $item->import_id)->first();
        if (! $sourceImport) {
            return $error('Không tìm thấy phiếu nhập nguồn của mục này!');
        }

        $bDepartmentId = (int) $transferReq->to_department_id;

        // Phòng A phải đã khai danh mục hoá chất này thì mới có đơn vị tính để nhận
        $aCategoryRow = DB::table(DepartmentChemical::TABLE)
            ->where('department_id', $departmentId)
            ->where('category_id', $item->category_id)
            ->where('status_id', 1)
            ->first();

        if (! $aCategoryRow) {
            return $error('Phòng bạn chưa khai hoá chất này ở tab "Hoá Chất Của Phòng" nên chưa nhận được. Vui lòng khai trước rồi quay lại nhận.');
        }

        $aUnitId = (int) $aCategoryRow->unit_id;
        if (! $aUnitId) {
            return $error('Phòng bạn chưa khai đơn vị tính cho hoá chất này ở tab "Hoá Chất Của Phòng" nên chưa có đơn vị để nhận hàng.');
        }

        $bUnitId = (int) DB::table(DepartmentChemical::TABLE)
            ->where('department_id', $bDepartmentId)
            ->where('category_id', $item->category_id)
            ->value('unit_id');

        $convertedAmount = CategoryUnitConversion::convert(
            CategoryUnitConversion::TYPE_CHEMICAL,
            (int) $item->category_id,
            (float) $item->issued_amount,
            $bUnitId ?: null,
            $aUnitId
        );

        if ($convertedAmount === null) {
            return $error(
                'Phòng bạn tính theo đơn vị khác với phòng '.$this->departmentName($bDepartmentId).' cho hoá chất này, '
                .'nhưng chưa có hệ số quy đổi giữa hai đơn vị. Vui lòng vào tab "Hoá Chất Của Phòng", sửa dòng hoá chất '
                .'này và khai mục Quy Đổi Đơn Vị.'
            );
        }

        if ($request->filled('dest_location_id')) {
            $locOk = DB::table('locations')->where('id', $request->dest_location_id)->where('department_id', $departmentId)->where('status_id', 1)->exists();
            if (! $locOk) {
                return $error('Vị trí lưu trữ được chọn không thuộc phòng ban bạn!');
            }
        }

        $exportRow = DB::table(self::TABLE)->where('transfer_item_id', $item->id)->where('type', self::TYPE_TRANSFER_OUT)->first();
        if (! $exportRow) {
            return $error('Không tìm thấy phiếu chuyển tương ứng!');
        }

        $bDeptName = $this->departmentName($bDepartmentId);
        $fullTransfer = ! (bool) $item->is_partial_lot;
        $receivedAt = now();

        $result = DB::transaction(function () use (
            $request, $item, $sourceImport, $exportRow, $departmentId, $bDeptName,
            $fullTransfer, $convertedAmount, $receivedAt
        ) {
            // Sinh mã mới cho A: giữ nguyên mã gốc kèm hậu tố -CK<số thứ tự> để truy vết
            $newCode = $this->nextChemImportTransferCode($sourceImport->code);

            $newImportId = DB::table('chemical_imports')->insertGetId([
                'code' => $newCode,
                'department_id' => $departmentId,
                'source_export_id' => $exportRow->id,
                'transfer_item_id' => $item->id,
                'is_partial_lot' => $item->is_partial_lot,
                'category_id' => $item->category_id,
                'amount' => $convertedAmount,
                'batch_no' => $sourceImport->batch_no,
                'expired_date' => $sourceImport->expired_date,
                'internal_expired_date' => $fullTransfer ? null : $sourceImport->internal_expired_date,
                'is_microbiological_chemicals' => $sourceImport->is_microbiological_chemicals,
                'supplier_id' => $sourceImport->supplier_id,
                'imported_date' => $receivedAt->format('Y-m-d'),
                'imported_by' => $this->actor(),
                'location_id' => $request->filled('dest_location_id') ? (int) $request->dest_location_id : null,
                'note' => 'Nhận chuyển liên phòng ban từ '.$bDeptName.', mã gốc '.$sourceImport->code.'.',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]);

            $historyNote = 'Nhận chuyển liên phòng ban từ '.$bDeptName.', mã gốc '.$sourceImport->code.' -> mã mới '.$newCode.'.';

            DB::table('chemical_import_histories')->insert([
                'import_id' => $newImportId,
                'action' => 'Nhận chuyển liên phòng ban',
                'code' => $newCode,
                'category_id' => $item->category_id,
                'amount' => $convertedAmount,
                'imported_date' => $receivedAt->format('Y-m-d'),
                'imported_by' => $this->actor(),
                'batch_no' => $sourceImport->batch_no,
                'expired_date' => $sourceImport->expired_date,
                'internal_expired_date' => $fullTransfer ? null : $sourceImport->internal_expired_date,
                'is_microbiological_chemicals' => $sourceImport->is_microbiological_chemicals,
                'supplier_id' => $sourceImport->supplier_id,
                'location_id' => $request->filled('dest_location_id') ? (int) $request->dest_location_id : null,
                'note' => 'Nhận chuyển liên phòng ban từ '.$bDeptName.', mã gốc '.$sourceImport->code.'.',
                'status_id' => 1,
                'change_note' => $historyNote,
                'created_by' => $this->actor(),
                'created_at' => $receivedAt,
            ]);

            DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $item->id)->update([
                'status' => 'received',
                'dest_location_id' => $request->filled('dest_location_id') ? (int) $request->dest_location_id : null,
                'new_import_id' => $newImportId,
                'received_by' => $this->actor(),
                'received_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]);

            $allItems = DB::table(self::TRANSFER_ITEM_TABLE)->where('transfer_request_id', $item->transfer_request_id)->where('active', 1)->get();

            DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $item->transfer_request_id)->update([
                'status' => $this->transferHeaderStatus($allItems),
                'updated_by' => $this->actor(),
                'updated_at' => $receivedAt,
            ]);

            return ['new_import_id' => $newImportId, 'new_code' => $newCode];
        });

        AuditTrialController::log(
            'Nhận chuyển liên phòng ban',
            'chemical_imports',
            $result['new_import_id'],
            'NA',
            'Nhận từ phòng '.$bDeptName.', mã gốc '.$sourceImport->code.' -> mã mới '.$result['new_code']
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã nhận hàng, mã phiếu nhập mới: '.$result['new_code'].'!',
            ]);
        }

        return redirect()->route('pages.export.chemicalExport.list', ['tab' => 'request'])
            ->with('success', 'Đã nhận hàng, mã phiếu nhập mới: '.$result['new_code'].'!');
    }

    /**
     * PHÒNG A TỪ CHỐI NHẬN 1 MỤC ĐÃ ĐƯỢC CẤP PHÁT
     *
     * Hoàn tồn cho B bằng cách khoá dòng chemical_exports type=transfer_out tương ứng
     * (status_id=0) - remaining() chỉ cộng export status_id=1 nên lô nguồn coi như chưa
     * từng bị trừ.
     */
    public function transferReceiveReject(Request $request)
    {
        $departmentId = $this->departmentId(); // A

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:chemical_transfer_items,id'],
            'return_note' => ['required', 'max:500'],
        ], [
            'return_note.required' => 'Vui lòng nhập lý do từ chối nhận.',
            'return_note.max' => 'Lý do từ chối nhận tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->with('activeTab', 'request');
        }

        $item = DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $request->item_id)->where('active', 1)->where('status', 'issued')->first();
        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy mục cần từ chối nhận!')->with('activeTab', 'request');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $item->transfer_request_id)->where('department_id', $departmentId)->first();
        if (! $transferReq) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị thuộc phòng ban này!')->with('activeTab', 'request');
        }

        $exportRow = DB::table(self::TABLE)->where('transfer_item_id', $item->id)->where('type', self::TYPE_TRANSFER_OUT)->first();
        if (! $exportRow) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu chuyển tương ứng!')->with('activeTab', 'request');
        }

        $returnedAt = now();

        DB::transaction(function () use ($item, $exportRow, $returnedAt, $request) {
            DB::table(self::TABLE)->where('id', $exportRow->id)->update([
                'status_id' => 0,
                'updated_at' => $returnedAt,
            ]);

            $this->logHistory($exportRow->id, 'Khoá', 'Phòng nhận từ chối nhận: '.trim($request->return_note).' - hoàn tồn kho phòng gửi.');

            DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $item->id)->update([
                'status' => 'returned',
                'return_note' => trim($request->return_note),
                'returned_by' => $this->actor(),
                'returned_at' => $returnedAt,
                'updated_at' => $returnedAt,
            ]);

            $allItems = DB::table(self::TRANSFER_ITEM_TABLE)->where('transfer_request_id', $item->transfer_request_id)->where('active', 1)->get();

            DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $item->transfer_request_id)->update([
                'status' => $this->transferHeaderStatus($allItems),
                'updated_by' => $this->actor(),
                'updated_at' => $returnedAt,
            ]);
        });

        AuditTrialController::log(
            'Từ chối nhận chuyển liên phòng ban',
            self::TRANSFER_ITEM_TABLE,
            $item->id,
            'issued',
            'Từ chối nhận, hoàn tồn phiếu '.$exportRow->code.': '.$request->return_note
        );

        return redirect()->route('pages.export.chemicalExport.list', ['tab' => 'request'])
            ->with('success', 'Đã từ chối nhận, tồn kho phòng gửi đã được hoàn lại.');
    }

    /**
     * Trạng thái tổng của phiếu đề nghị, suy từ trạng thái từng item con.
     *
     * completed chỉ tính khi TẤT CẢ item đã received - còn item nào issued (chờ A nhận)
     * thì dù B đã cấp phát hết, phiếu vẫn coi là partial.
     */
    private function transferHeaderStatus($items): string
    {
        $total = $items->count();
        $pendingCount = $items->where('status', 'pending')->count();
        $issuedCount = $items->where('status', 'issued')->count();
        $receivedCount = $items->where('status', 'received')->count();

        if ($pendingCount === $total) {
            return 'pending';
        }

        if ($pendingCount === 0 && $issuedCount === 0) {
            return $receivedCount > 0 ? 'completed' : 'rejected';
        }

        return 'partial';
    }

    /**
     * PHÒNG B TỪ CHỐI CẤP PHÁT 1 MỤC ĐỀ NGHỊ LIÊN PHÒNG BAN
     */
    public function transferRequestReject(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:chemical_transfer_items,id'],
            'reject_note' => ['required', 'max:500'],
        ], [
            'reject_note.required' => 'Vui lòng nhập lý do từ chối.',
            'reject_note.max' => 'Lý do từ chối tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->with('activeTab', 'request');
        }

        $item = DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $request->item_id)->where('active', 1)->where('status', 'pending')->first();
        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy mục đề nghị!')->with('activeTab', 'request');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $item->transfer_request_id)->where('to_department_id', $departmentId)->first();
        if (! $transferReq) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị thuộc phòng ban này!')->with('activeTab', 'request');
        }

        DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $item->id)->update([
            'status' => 'rejected',
            'reject_note' => trim((string) $request->reject_note),
            'updated_at' => now(),
        ]);

        $allItems = DB::table(self::TRANSFER_ITEM_TABLE)->where('transfer_request_id', $item->transfer_request_id)->where('active', 1)->get();

        DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $item->transfer_request_id)->update([
            'status' => $this->transferHeaderStatus($allItems),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Từ chối cấp phát liên phòng ban',
            self::TRANSFER_ITEM_TABLE,
            $item->id,
            'pending',
            'Từ chối cấp phát: '.$request->reject_note
        );

        return redirect()->route('pages.export.chemicalExport.list', ['tab' => 'request'])
            ->with('success', 'Đã từ chối mục đề nghị cấp phát liên phòng ban.');
    }

    /**
     * Đề nghị liên phòng ban PHÒNG MÌNH GỬI ĐI (mình là A) và GỬI ĐẾN PHÒNG MÌNH (mình là
     * B), kèm các mục con group theo transfer_request_id. Cùng hình dạng với
     * transferRequestsData() của StandardExportController.
     */
    private function transferRequestsData(int $departmentId): array
    {
        $base = fn () => DB::table(self::TRANSFER_REQUEST_TABLE)
            ->select(self::TRANSFER_REQUEST_TABLE.'.*')
            ->orderBy(self::TRANSFER_REQUEST_TABLE.'.created_at', 'desc');

        $sent = $base()
            ->leftJoin('deparments', self::TRANSFER_REQUEST_TABLE.'.to_department_id', '=', 'deparments.id')
            ->addSelect('deparments.name as partner_name', 'deparments.shortName as partner_short')
            ->where(self::TRANSFER_REQUEST_TABLE.'.department_id', $departmentId)
            ->get();

        $received = $base()
            ->leftJoin('deparments', self::TRANSFER_REQUEST_TABLE.'.department_id', '=', 'deparments.id')
            ->addSelect('deparments.name as partner_name', 'deparments.shortName as partner_short')
            ->where(self::TRANSFER_REQUEST_TABLE.'.to_department_id', $departmentId)
            ->get();

        $requestIds = $sent->pluck('id')->merge($received->pluck('id'))->unique();

        $items = DB::table(self::TRANSFER_ITEM_TABLE)
            ->leftJoin('chemical_categories', self::TRANSFER_ITEM_TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('locations', self::TRANSFER_ITEM_TABLE.'.dest_location_id', '=', 'locations.id')
            ->where(self::TRANSFER_ITEM_TABLE.'.active', 1)
            ->whereIn(self::TRANSFER_ITEM_TABLE.'.transfer_request_id', $requestIds)
            ->select(
                self::TRANSFER_ITEM_TABLE.'.*',
                'chemical_categories.code as category_code',
                'chem_names.name as chem_name',
                'locations.code as dest_location_code'
            )
            ->orderBy(self::TRANSFER_ITEM_TABLE.'.id')
            ->get()
            ->groupBy('transfer_request_id');

        return ['sent' => $sent, 'received' => $received, 'items' => $items];
    }

    /** Mã đề nghị liên phòng ban: LPB-<shortName A>-<shortName B>-ddMMyy-<số thứ tự trong ngày>. */
    private function nextChemTransferCode(int $fromDepartmentId, int $toDepartmentId): string
    {
        $fromShort = DB::table('deparments')->where('id', $fromDepartmentId)->value('shortName') ?: 'NA';
        $toShort = DB::table('deparments')->where('id', $toDepartmentId)->value('shortName') ?: 'NA';
        $prefix = 'LPB-'.$fromShort.'-'.$toShort.'-'.date('dmy').'-';

        $latestCode = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('code', 'LIKE', $prefix.'%')
            ->orderBy('id', 'desc')
            ->value('code');

        $seq = 1;
        if ($latestCode) {
            $parts = explode('-', $latestCode);
            $seq = (int) end($parts) + 1;
        }

        return $prefix.str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Mã kế tiếp cho lô nhận từ phòng khác: <mã gốc>-CK<số thứ tự>.
     *
     * Mã gốc là mã của phòng nhập ĐẦU TIÊN, tức phần đứng trước "-CK" nếu lô đang
     * chuyển vốn cũng là hàng chuyển kho. Nhờ vậy chuyển qua bao nhiêu phòng thì mã
     * vẫn quy về đúng một gốc, không nối chồng "-CK01-CK02".
     */
    private function nextChemImportTransferCode(string $sourceCode): string
    {
        $root = explode('-CK', $sourceCode, 2)[0];
        $prefix = $root.'-CK';

        $next = DB::table('chemical_imports')
            ->where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix)))
            ->max();

        return $prefix.str_pad((string) (($next ?? 0) + 1), 2, '0', STR_PAD_LEFT);
    }

    /** Danh mục hoá chất để chọn khi gửi đề nghị. */
    private function categoryOptions(int $departmentId)
    {
        return DB::table('chemical_categories')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            // Đơn vị hiện trên ô chọn là đơn vị PHÒNG ĐANG CHỌN đã khai cho hoá chất đó
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_categories.id'))
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

    /** Phòng ban nhận hàng chuyển kho: mọi phòng đang hoạt động, trừ phòng đang đứng. */
    private function departmentOptions(int $departmentId)
    {
        return DB::table('deparments')
            ->select('id', 'name', 'shortName')
            ->where('isActive', 1)
            ->where('id', '<>', $departmentId)
            ->orderBy('name', 'asc')
            ->get();
    }

    /** Người kiểm tra: user đang hoạt động của phòng ban đang chọn. */
    private function checkerOptions(int $departmentId)
    {
        // user_management.deparment_id là FK trỏ thẳng deparments.id
        return DB::table('user_management')
            ->select('user_management.userName', 'user_management.fullName')
            ->where('user_management.deparment_id', $departmentId)
            ->where('user_management.isActive', 1)
            ->orderBy('user_management.fullName', 'asc')
            ->get();
    }

    /** Tổng một cột số theo từng phiếu nhập trong phòng ban: [import_id => tổng]. */
    private function sumByImport(string $table, string $column, int $departmentId)
    {
        return DB::table($table)
            ->select('import_id', DB::raw('SUM(`'.$column.'`) as total'))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');
    }

    /**
     * Tồn còn lại của một phiếu nhập, có thể bỏ qua một phiếu xuất đang được sửa.
     *
     * Tồn = số lượng nhập + số đã cân đối - số đã xuất (kể cả phần huỷ bỏ).
     */
    private function remaining($import, ?int $ignoreExportId = null): float
    {
        $query = DB::table(self::TABLE)
            ->where('import_id', $import->id)
            ->where('status_id', 1);

        if ($ignoreExportId) {
            $query->where('id', '<>', $ignoreExportId);
        }

        $balanced = (float) DB::table('chemical_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('balancing_amount');

        return max((float) $import->amount + $balanced - (float) $query->sum('amount'), 0);
    }

    /**
     * Hạn mức được xuất: tồn còn lại cộng thêm phần vượt cho phép.
     *
     * LÔ LẺ (nhận một phần từ phòng ban khác) không được vượt: phòng gửi đã cân chia
     * sẵn nên số lượng là con số đã chốt, không còn hao hụt cân đong để trừ hao.
     */
    private function maxIssuable(float $remaining, $import = null): float
    {
        if ($import && ! empty($import->is_partial_lot)) {
            return $remaining;
        }

        return $remaining * (1 + self::OVER_ISSUE_RATIO);
    }

    /**
     * CHUYỂN NGUYÊN hay không: lô còn y nguyên như lúc nhập và chuyển đi trọn vẹn.
     *
     * Phải đủ cả ba điều kiện:
     * 1. Số lượng chuyển đúng bằng LƯỢNG NHẬP GỐC (imports.amount, KHÔNG cộng cân đối).
     * 2. Lô chưa cân đối lần nào - đã cân đối nghĩa là đã đụng vào, không còn nguyên.
     * 3. Lô chưa xuất ra lần nào - đã dùng / huỷ / chuyển bớt thì cũng không còn nguyên.
     *
     * Thiếu bất kỳ điều kiện nào thì là CHUYỂN LẺ.
     *
     * @param  int|null  $ignoreExportId  phiếu xuất đang sửa, không tính vào phần đã xuất
     */
    private function isFullTransfer($import, $amount, ?int $ignoreExportId = null): bool
    {
        if (abs((float) $amount - (float) $import->amount) > self::EPSILON) {
            return false;
        }

        $balanced = DB::table('chemical_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->exists();

        if ($balanced) {
            return false;
        }

        $used = DB::table(self::TABLE)
            ->where('import_id', $import->id)
            ->where('status_id', 1);

        if ($ignoreExportId) {
            $used->where('id', '<>', $ignoreExportId);
        }

        return ! $used->exists();
    }

    private function findImport($importId, int $departmentId)
    {
        // Kèm shelf_life_months (theo cấu hình phòng ban) để kiểm tra hạn dùng nội bộ khi xuất
        $query = DB::table('chemical_imports')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id');

        return DepartmentChemical::join($query, $departmentId, 'chemical_imports.category_id')
            ->select('chemical_imports.*', DepartmentChemical::shelfLifeColumn())
            ->where('chemical_imports.id', $importId)
            ->where('chemical_imports.department_id', $departmentId)
            ->where('chemical_imports.status_id', 1)
            ->first();
    }

    /**
     * Kiểm tra phiếu nhập được chọn và số lượng xuất.
     *
     * @param  int|null  $ignoreExportId  phiếu xuất đang sửa, không tính vào tồn
     * @param  int|null  $currentImportId  phiếu nhập bản ghi đang giữ; giữ nguyên phiếu này
     *                                     thì không xét lại điều kiện hạn dùng / còn tồn
     */
    private function checkImport($validator, Request $request, $import, ?int $ignoreExportId = null, ?int $currentImportId = null): void
    {
        $validator->after(function ($validator) use ($request, $import, $ignoreExportId, $currentImportId) {
            if (! $import) {
                $validator->errors()->add('import_id', 'Phiếu nhập được chọn không tồn tại hoặc đã bị khoá.');

                return;
            }

            $remaining = $this->remaining($import, $ignoreExportId);

            // Chỉ chặn khi người dùng CHỌN một phiếu nhập khác với phiếu bản ghi đang giữ
            if ((int) $import->id !== (int) $currentImportId) {
                if ($import->expired_date && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt(now()->startOfDay())) {
                    $validator->errors()->add(
                        'import_id',
                        'Phiếu nhập '.$import->code.' đã hết hạn sử dụng ngày '.\Carbon\Carbon::parse($import->expired_date)->format('d/m/Y').', không được xuất ra sử dụng.'
                    );

                    return;
                }

                if ($remaining <= self::EPSILON) {
                    $validator->errors()->add('import_id', 'Phiếu nhập '.$import->code.' đã hết tồn, vui lòng chọn phiếu khác.');

                    return;
                }

                // Hoá chất có khai báo hạn dùng mặc định thì phải xác định hạn dùng nội bộ trước khi dùng.
                if ((int) ($import->shelf_life_months ?? 0) > 0 && ! $import->internal_expired_date) {
                    $validator->errors()->add(
                        'import_id',
                        'Phiếu nhập '.$import->code.' chưa xác định hạn dùng nội bộ nên chưa được sử dụng. '
                        .'Vào màn hình Tồn Kho Hoá Chất, tab "Chưa Xác Định Hạn Nội Bộ" để xác định trước.'
                    );

                    return;
                }
            }

            if (! is_numeric($request->amount)) {
                return;
            }

            $limit = $this->maxIssuable($remaining, $import);

            if ((float) $request->amount > $limit + self::EPSILON) {
                // Lô lẻ không có phần vượt, thông báo phải nói đúng lý do
                $validator->errors()->add(
                    'amount',
                    empty($import->is_partial_lot)
                        ? 'Phiếu nhập '.$import->code.' còn '.$this->number($remaining).'. Được xuất vượt tối đa '
                            .(int) round(self::OVER_ISSUE_RATIO * 100).'%, tức không quá '.$this->number($limit).'.'
                        : 'Phiếu nhập '.$import->code.' chỉ còn '.$this->number($remaining)
                            .'. Đây là lô nhận lẻ từ phòng ban khác nên không được xuất vượt lượng đã nhận.'
                );
            }
        });
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

    private function rules(int $departmentId): array
    {
        return [
            'import_id' => ['required', 'exists:chemical_imports,id'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'type' => ['required', 'in:'.implode(',', array_keys(self::TYPES))],
            'purpose' => ['nullable', 'max:500'],
            // Căn cứ loại bỏ (Số Phiếu KN, OOS, BCSL), in vào hồ sơ xin quyết định huỷ
            'test_report_no' => ['nullable', 'max:100'],
            // Chỉ ghi vào lịch sử điều chỉnh, không lưu thành cột của exports
            'adjust_reason' => ['nullable', 'max:500'],
            // Người kiểm tra phải là user đang hoạt động của chính phòng ban này
            'checked_by' => ['nullable', Rule::in($this->checkerOptions($departmentId)->pluck('fullName'))],
        ];
    }

    private function payload(Request $request, $import): array
    {
        return [
            'code' => $import->code,
            'import_id' => (int) $import->id,
            'amount' => (float) $request->amount,
            'type' => $request->type,
            'purpose' => $this->nullIfBlank($request->purpose),
            // Số phiếu KN / OOS / BCSL là căn cứ loại bỏ, chỉ phiếu Huỷ bỏ mới dùng đến
            'test_report_no' => $request->type === self::TYPE_CANCEL
                ? $this->nullIfBlank($request->test_report_no)
                : null,
            'checked_by' => $this->nullIfBlank($request->checked_by),
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
            'import_id.required' => 'Vui lòng chọn phiếu nhập cần xuất hoá chất.',
            'import_id.exists' => 'Phiếu nhập được chọn không tồn tại.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'type.required' => 'Vui lòng chọn loại phiếu.',
            'type.in' => 'Loại phiếu không hợp lệ.',
            'purpose.max' => 'Mục đích sử dụng tối đa 500 ký tự.',
            'test_report_no.max' => 'Số phiếu KN, OOS, BCSL tối đa 100 ký tự.',
            'adjust_reason.max' => 'Lý do điều chỉnh tối đa 500 ký tự.',
            'checked_by.in' => 'Người kiểm tra phải là nhân viên đang hoạt động của phòng ban này.',
        ];
    }
}
