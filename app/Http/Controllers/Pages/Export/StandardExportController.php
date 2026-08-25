<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentStandard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * SỬ DỤNG - SỬ DỤNG CHẤT CHUẨN
 *
 * Ghi nhận từng lần lấy chất chuẩn ra khỏi kho từ một ống chuẩn cụ thể:
 * sử dụng cho phép thử (type = export) hoặc huỷ bỏ (type = cancel).
 *
 * Mã ống chuẩn không sinh mới - lấy đúng mã của phiếu nhập được xuất ra.
 * Phiếu chỉ khoá (deActive) chứ không xoá cứng; phiếu đã khoá không trừ tồn.
 *
 * Ba quy tắc của nghiệp vụ xuất chất chuẩn:
 * - Chỉ được CHỌN ống còn hạn sử dụng và còn tồn > 0.
 * - Chất chuẩn có khai hạn dùng mặc định thì phải xác định HẠN DÙNG NỘI BỘ (hạn sau
 *   khi mở ống) ở màn hình Tồn Kho trước khi dùng.
 * - Được XUẤT VƯỢT tồn tối đa 5% để bù sai số cân đong. Phần vượt làm tồn bị âm,
 *   xử lý bằng chức năng Cân Đối ở màn hình Tồn Kho Chất Chuẩn.
 */
class StandardExportController extends Controller
{
    private const TABLE = 'standard_exports';

    private const HISTORY_TABLE = 'standard_export_histories';

    private const LABEL = 'phiếu sử dụng chất chuẩn';

    /** Các trường được theo dõi thay đổi, dùng làm nhãn trong lịch sử điều chỉnh. */
    private const FIELDS = [
        'code' => 'Mã ống chuẩn',
        'amount' => 'Số lượng',
        'type' => 'Loại phiếu',
        'exported_date' => 'Ngày sử dụng',
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
        'cancel' => 'Huỷ bỏ',
    ];

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('standard_imports', self::TABLE.'.import_id', '=', 'standard_imports.id')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('chem_names', 'standard_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'standard_categories.unit_id', '=', 'units.id')
            ->leftJoin('groups', self::TABLE.'.group_id', '=', 'groups.id')
            ->leftJoin('analysts', self::TABLE.'.analyst_id', '=', 'analysts.id')
            ->select(
                self::TABLE.'.*',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_categories.groups',
                'chem_names.name as standard_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'standard_imports.amount as import_amount',
                'standard_imports.batch_no',
                'standard_imports.expired_date',
                'standard_imports.group_code',
                'groups.name as group_name',
                'analysts.name as analyst_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy(self::TABLE.'.exported_date', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        // Danh sách Đề nghị cấp phát chuẩn của các Tổ
        $requests = DB::table('request_lists')
            ->leftJoin('groups', 'request_lists.group_id', '=', 'groups.id')
            ->select(
                'request_lists.*',
                'groups.name as group_name'
            )
            ->where('request_lists.department_id', $departmentId)
            ->orderBy('request_lists.created_at', 'desc')
            ->get();

        $requestItems = DB::table('request_items')
            ->leftJoin('request_lists', 'request_items.request_list_id', '=', 'request_lists.id')
            ->leftJoin('standard_categories', 'request_items.category_id', '=', 'standard_categories.id')
            ->leftJoin('chem_names', 'standard_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('standard_imports', 'request_items.import_id', '=', 'standard_imports.id')
            ->leftJoin('locations', 'standard_imports.location_id', '=', 'locations.id')
            ->leftJoin('analysts', 'request_items.analyst_id', '=', 'analysts.id')
            ->select(
                'request_items.*',
                'chem_names.name as standard_name',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_imports.batch_no',
                'standard_imports.expired_date as import_expired_date',
                'locations.code as location_code',
                'locations.name as location_name',
                'analysts.name as analyst_name'
            )
            ->where('request_lists.department_id', $departmentId)
            ->get()
            ->groupBy('request_list_id');

        $groups = DB::table('groups')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $productNames = DB::table('product_names')
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $analysts = DB::table('analysts')
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $packagingSpecs = DB::table('packaging_specifications')
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $standardCategories = DB::table('standard_categories')
            ->leftJoin('chem_names', 'standard_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'standard_categories.unit_id', '=', 'units.id')
            ->select(
                'standard_categories.id',
                'standard_categories.code',
                'standard_categories.version',
                'chem_names.name as standard_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where('standard_categories.status_id', 1)
            ->orderBy('chem_names.name')
            ->get();

        $availableImports = $this->importOptions($departmentId);

        session()->put(['title' => 'SỬ DỤNG - SỬ DỤNG CHẤT CHUẨN']);

        [$from, $to] = $this->reportRange($request);

        $activeTab = $request->input('tab');
        if (!in_array($activeTab, ['book', 'request', 'report'])) {
            $activeTab = 'book';
        }

        return view('pages.export.StandardExport.list', [
            'datas' => $datas,
            'requests' => $requests,
            'requestItems' => $requestItems,
            'groups' => $groups,
            'productNames' => $productNames,
            'analysts' => $analysts,
            'packagingSpecs' => $packagingSpecs,
            'standardCategories' => $standardCategories,
            'availableImports' => $availableImports,
            'imports' => $availableImports,
            'checkers' => $this->checkerOptions($departmentId),
            'types' => self::TYPES,
            'standardGroups' => config('standard.groups'),
            'overIssuePercent' => (int) round(self::OVER_ISSUE_RATIO * 100),
            'adjustCounts' => $this->adjustCounts($departmentId),
            'report' => $this->usageReport($departmentId, $from, $to),
            'reportFrom' => $from,
            'reportTo' => $to,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * TRA ỐNG THEO MÃ ỐNG CHUẨN - quét mã vạch trên nhãn hoặc gõ tay mã.
     *
     * Trả về đúng ống của phòng ban đang đứng kèm cờ có xuất được hay không. Điều kiện
     * xuất được lấy nguyên từ importOptions() - cùng một nguồn với ô chọn phiếu nhập
     * trên form, để quét mã và chọn tay không bao giờ cho hai kết quả khác nhau.
     */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->code);

        if ($code === '') {
            return response()->json(['ok' => false, 'reason' => 'Vui lòng quét mã vạch trên nhãn hoặc nhập mã ống chuẩn.']);
        }

        $import = $this->importOptions($this->departmentId())->firstWhere('code', $code);

        if (! $import) {
            return response()->json([
                'ok' => false,
                'reason' => 'Không tìm thấy mã ống chuẩn "'.$code.'" trong kho của phòng ban này, '
                    .'hoặc phiếu nhập đã bị khoá.',
            ]);
        }

        return response()->json([
            'ok' => (bool) $import->selectable,
            'id' => $import->id,
            'code' => $import->code,
            // Khoá chem_name giữ đúng tên mà pages/export/shared/assets.blade.php đang đọc,
            // để phần quét mã dùng chung một đoạn JS với màn Sử Dụng Hoá Chất.
            'chem_name' => $import->standard_name ?: '—',
            'category_code' => $import->category_code ?: '—',
            'batch_no' => $import->batch_no ?: '—',
            'remaining' => $this->number($import->remaining).' '.($import->unit_short_name ?: ''),
            'expired_date' => $import->expired_date
                ? \Carbon\Carbon::parse($import->expired_date)->format('d/m/Y')
                : '—',
            'reason' => $import->selectable ? null : $this->notSelectableReason($import),
        ]);
    }

    /** Vì sao một ống không xuất được, viết đúng cách xử lý tiếp theo cho người dùng. */
    private function notSelectableReason($import): string
    {
        if ($import->expired) {
            return 'Ống chuẩn '.$import->code.' đã hết hạn sử dụng ngày '
                .\Carbon\Carbon::parse($import->expired_date)->format('d/m/Y')
                .' nên không xuất ra sử dụng được.';
        }

        if ($import->waiting_internal) {
            return 'Ống chuẩn '.$import->code.' chưa xác định hạn dùng nội bộ. Vào màn hình Tồn Kho Chất Chuẩn, '
                .'tab "Chưa Xác Định Hạn Nội Bộ" để xác định trước.';
        }

        return 'Ống chuẩn '.$import->code.' đã hết tồn, vui lòng quét ống khác.';
    }

    /**
     * LẤY DANH SÁCH CHUẨN ĐÃ ĐƯỢC CẤP PHÁT CHO MỘT TỔ (AJAX)
     *
     * Nhân viên thuộc tổ chỉ được nhìn thấy và sử dụng chuẩn đã được cấp phát cho tổ mình.
     */
    public function getIssuedStandards(Request $request)
    {
        $groupId = (int) $request->group_id;
        $departmentId = $this->departmentId();

        if (!$groupId) {
            return response()->json(['standards' => []]);
        }

        // Các request_items đã được cấp phát cho tổ này
        $issuedItems = DB::table('request_items')
            ->join('request_lists', 'request_items.request_list_id', '=', 'request_lists.id')
            ->leftJoin('standard_imports', 'request_items.import_id', '=', 'standard_imports.id')
            ->leftJoin('locations', 'standard_imports.location_id', '=', 'locations.id')
            ->leftJoin('standard_categories', 'request_items.category_id', '=', 'standard_categories.id')
            ->leftJoin('chem_names', 'standard_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'standard_categories.unit_id', '=', 'units.id')
            ->leftJoin('analysts', 'request_items.analyst_id', '=', 'analysts.id')
            ->select(
                'request_items.*',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'chem_names.name as standard_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'standard_imports.batch_no',
                'standard_imports.expired_date as import_expired_date',
                'locations.code as location_code',
                'locations.name as location_name',
                'analysts.name as analyst_name'
            )
            ->where('request_lists.department_id', $departmentId)
            ->where('request_lists.group_id', $groupId)
            ->where('request_items.status', 'issued')
            ->whereNotNull('request_items.import_id')
            ->orderBy('request_items.issued_at', 'desc')
            ->get();

        return response()->json(['standards' => $issuedItems]);
    }

    /**
     * TỔ TẠO ĐỀ NGHỊ CẤP PHÁT CHUẨN
     */
    public function requestStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'group_id' => ['required', 'exists:groups,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:standard_categories,id'],
            'items.*.specification' => ['nullable', 'string', 'max:100'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.test_criteria' => ['nullable', 'string', 'max:255'],
            'items.*.analyst_id' => ['nullable', 'exists:analysts,id'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'group_id.required' => 'Vui lòng chọn Tổ đề nghị.',
            'group_id.exists' => 'Tổ được chọn không tồn tại.',
            'items.required' => 'Vui lòng thêm ít nhất một chất chuẩn đề nghị.',
            'items.min' => 'Vui lòng thêm ít nhất một chất chuẩn đề nghị.',
            'items.*.category_id.required' => 'Vui lòng chọn chất chuẩn.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'requestCreateErrors')
                ->withInput()
                ->with('activeTab', 'request');
        }

        $code = 'YC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $listId = DB::table('request_lists')->insertGetId([
            'code' => $code,
            'department_id' => $departmentId,
            'group_id' => (int) $request->group_id,
            'status' => 'pending',
            'note' => $this->nullIfBlank($request->note),
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($request->items as $item) {
            DB::table('request_items')->insert([
                'request_list_id' => $listId,
                'category_id' => (int) $item['category_id'],
                'specification' => $this->nullIfBlank($item['specification'] ?? null),
                'requested_amount' => (float) $item['requested_amount'],
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'product_name' => $this->nullIfBlank($item['product_name'] ?? null),
                'test_criteria' => $this->nullIfBlank($item['test_criteria'] ?? null),
                'analyst_id' => !empty($item['analyst_id']) ? (int) $item['analyst_id'] : null,
                'status' => 'pending',
                'note' => $this->nullIfBlank($item['note'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $groupName = DB::table('groups')->where('id', $request->group_id)->value('name') ?: 'Tổ';

        AuditTrialController::log(
            'Tạo đề nghị cấp phát chuẩn',
            'request_lists',
            $listId,
            'NA',
            'Phiếu đề nghị ' . $code . ' cho ' . $groupName . ' (' . count($request->items) . ' mục)'
        );

        return redirect()->route('pages.export.standardExport.list', ['tab' => 'request'])
            ->with('success', 'Đã tạo phiếu đề nghị cấp phát chuẩn ' . $code . ' thành công!');
    }

    /**
     * THỦ KHO / QUẢN LÝ KHO CẤP PHÁT CHUẨN CHO TỔ
     */
    public function issueStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:request_items,id'],
            'import_id' => ['required', 'exists:standard_imports,id'],
            'issued_amount' => ['required', 'numeric', 'min:0.0001'],
            'issued_unit' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'item_id.required' => 'Không tìm thấy mục đề nghị cần cấp phát.',
            'import_id.required' => 'Vui lòng chọn ống chuẩn trong kho để cấp phát.',
            'issued_amount.required' => 'Vui lòng nhập số lượng cấp phát.',
            'issued_amount.min' => 'Số lượng cấp phát phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'issueErrors')
                ->withInput()
                ->with('activeTab', 'request');
        }

        $item = DB::table('request_items')->where('id', $request->item_id)->first();
        if (!$item) {
            return redirect()->back()->with('error', 'Không tìm thấy mục đề nghị!');
        }

        $import = DB::table('standard_imports')->where('id', $request->import_id)->where('department_id', $departmentId)->first();
        if (!$import) {
            return redirect()->back()->with('error', 'Không tìm thấy ống chuẩn trong kho phòng ban này!');
        }

        DB::table('request_items')->where('id', $item->id)->update([
            'import_id' => (int) $import->id,
            'import_code' => $import->code,
            'issued_amount' => (float) $request->issued_amount,
            'issued_unit' => $this->nullIfBlank($request->issued_unit ?? $item->requested_unit),
            'issued_by' => $this->actor(),
            'issued_at' => now(),
            'status' => 'issued',
            'note' => $this->nullIfBlank($request->note ?? $item->note),
            'updated_at' => now(),
        ]);

        // Cập nhật trạng thái phiếu đề nghị tổng (completed nếu đã cấp hết, partial nếu cấp 1 phần)
        $allItems = DB::table('request_items')->where('request_list_id', $item->request_list_id)->get();
        $pendingCount = $allItems->where('status', 'pending')->count();
        $issuedCount = $allItems->where('status', 'issued')->count();

        $newListStatus = $pendingCount === 0 ? 'completed' : ($issuedCount > 0 ? 'partial' : 'pending');

        DB::table('request_lists')->where('id', $item->request_list_id)->update([
            'status' => $newListStatus,
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Cấp phát chuẩn',
            'request_items',
            $item->id,
            'pending',
            'Cấp ống ' . $import->code . ' số lượng ' . $request->issued_amount
        );

        return redirect()->route('pages.export.standardExport.list', ['tab' => 'request'])
            ->with('success', 'Đã cấp phát ống chuẩn ' . $import->code . ' thành công!');
    }

    /**
     * TỪ CHỐI CẤP PHÁT MỤC ĐỀ NGHỊ
     */
    public function requestReject(Request $request)
    {
        $item = DB::table('request_items')->where('id', $request->item_id)->first();
        if (!$item) {
            return redirect()->back()->with('error', 'Không tìm thấy mục đề nghị!');
        }

        DB::table('request_items')->where('id', $item->id)->update([
            'status' => 'rejected',
            'note' => $this->nullIfBlank($request->note),
            'updated_at' => now(),
        ]);

        $allItems = DB::table('request_items')->where('request_list_id', $item->request_list_id)->get();
        $pendingCount = $allItems->where('status', 'pending')->count();
        $issuedCount = $allItems->where('status', 'issued')->count();

        $newListStatus = $pendingCount === 0 ? ($issuedCount > 0 ? 'completed' : 'rejected') : 'partial';

        DB::table('request_lists')->where('id', $item->request_list_id)->update([
            'status' => $newListStatus,
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Từ chối cấp phát',
            'request_items',
            $item->id,
            $item->status,
            'Từ chối cấp phát' . ($request->filled('note') ? ': ' . $request->note : '')
        );

        return redirect()->route('pages.export.standardExport.list', ['tab' => 'request'])
            ->with('success', 'Đã từ chối mục đề nghị cấp phát.');
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
            self::TYPES[$request->type].' chất chuẩn, mã ống chuẩn: '.$import->code.', số lượng: '.$request->amount
        );

        return redirect()->back()->with('success', 'Đã ghi nhận '.self::LABEL.' cho ống chuẩn '.$import->code.'!');
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

        $import = $this->findImport($request->import_id, $departmentId);

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());
        // Bỏ qua chính bản ghi đang sửa khi tính tồn, nếu không số lượng cũ bị trừ hai lần.
        // Giữ nguyên ống cũ thì không xét lại điều kiện hạn dùng / còn tồn, phiếu đã ghi rồi,
        // chỉ khi ĐỔI sang ống khác mới coi là một lần chọn mới.
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

        $newStatus = $current->status_id == 1 ? 0 : 1;

        // Mở khoá lại thì số lượng cũ phải còn nằm trong hạn mức xuất của ống chuẩn
        if ($newStatus == 1) {
            $import = DB::table('standard_imports')->where('id', $current->import_id)->first();
            $remaining = $import ? $this->remaining($import, (int) $current->id) : 0;

            if (! $import || (float) $current->amount > $this->maxIssuable($remaining) + self::EPSILON) {
                return redirect()->back()->with(
                    'error',
                    'Không mở khoá được: ống chuẩn chỉ còn '.$this->number($remaining)
                    .' trong khi phiếu này cần '.$this->number((float) $current->amount).'.'
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
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' của ống chuẩn '.$current->code.'!'
        );
    }

    /**
     * Số lần ĐIỀU CHỈNH của từng phiếu: [standard_export_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc lập phiếu chứ không phải một lần chỉnh sửa.
     */
    private function adjustCounts(int $departmentId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('standard_export_id', DB::raw('COUNT(*) as times'))
            ->whereIn('standard_export_id', function ($query) use ($departmentId) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $departmentId);
            })
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('standard_export_id')
            ->pluck('times', 'standard_export_id');
    }

    /** Trả về lịch sử điều chỉnh của một phiếu sử dụng cho modal xem lịch sử. */
    public function history(Request $request)
    {
        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('standard_imports', self::HISTORY_TABLE.'.import_id', '=', 'standard_imports.id')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('chem_names', 'standard_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'standard_categories.unit_id', '=', 'units.id')
            ->select(
                self::HISTORY_TABLE.'.*',
                'chem_names.name as standard_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            // Chỉ cho xem lịch sử của phiếu thuộc phòng ban đang chọn
            ->whereIn(self::HISTORY_TABLE.'.standard_export_id', function ($query) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $this->departmentId());
            })
            ->where(self::HISTORY_TABLE.'.standard_export_id', $request->id)
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
                        'Mã ống chuẩn' => $row->code ?: '—',
                        'Chất chuẩn' => $row->standard_name ?: '—',
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
            'standard_export_id' => $row->id,
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

            if ($field === 'exported_date') {
                $parts[] = $title.': '.$this->historyDate($old).' -> '.$this->historyDate($new);

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

    private function historyDate($value): string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    }

    /**
     * Ống chuẩn của phòng ban đang chọn, còn hiệu lực.
     *
     * Kèm sẵn tồn còn lại / hạn mức xuất để form hiển thị mà không phải hỏi DB theo
     * từng phiếu. Cờ selectable = còn hạn dùng VÀ còn tồn > 0 VÀ đã có hạn nội bộ khi
     * cần: modal Thêm mới chỉ hiện ống selectable, modal Cập Nhật giữ cả ống không
     * selectable để phiếu xuất cũ còn chọn lại được đúng ống của nó.
     */
    private function importOptions(int $departmentId)
    {
        $used = $this->sumByImport(self::TABLE, 'amount', $departmentId);
        $balanced = $this->sumByImport('standard_balancings', 'balancing_amount', $departmentId);
        $today = now()->startOfDay();

        $query = DB::table('standard_imports')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('chem_names', 'standard_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'standard_categories.unit_id', '=', 'units.id');

        // Hạn dùng nội bộ lấy theo cấu hình của phòng ban đang chọn
        return DepartmentStandard::join($query, $departmentId, 'standard_imports.category_id')
            ->select(
                'standard_imports.id',
                'standard_imports.code',
                'standard_imports.amount',
                'standard_imports.batch_no',
                'standard_imports.expired_date',
                'standard_imports.internal_expired_date',
                'standard_imports.group_code',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                DepartmentStandard::shelfLifeColumn(),
                'chem_names.name as standard_name',
                'units.short_name as unit_short_name'
            )
            ->where('standard_imports.department_id', $departmentId)
            ->where('standard_imports.status_id', 1)
            ->orderBy('standard_imports.imported_date', 'desc')
            ->orderBy('standard_imports.id', 'desc')
            ->get()
            ->map(function ($import) use ($used, $balanced, $today) {
                $import->used = (float) ($used[$import->id] ?? 0);
                $import->balanced = (float) ($balanced[$import->id] ?? 0);
                $import->remaining = max((float) $import->amount + $import->balanced - $import->used, 0);
                $import->max_amount = $this->maxIssuable($import->remaining);

                $import->expired = $import->expired_date
                    && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt($today);

                // Chất chuẩn có hạn dùng mặc định mà chưa xác định hạn nội bộ thì chưa được dùng
                $import->waiting_internal = (int) ($import->shelf_life_months ?? 0) > 0
                    && ! $import->internal_expired_date;

                $import->selectable = ! $import->expired
                    && ! $import->waiting_internal
                    && $import->remaining > self::EPSILON;

                return $import;
            });
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
     * BÁO CÁO SỬ DỤNG CHẤT CHUẨN THEO KHOẢNG THỜI GIAN.
     *
     * Cộng dồn các phiếu sử dụng còn hiệu lực trong khoảng ngày, gom theo mã danh mục
     * chất chuẩn, tách riêng phần đã dùng và phần đã huỷ.
     */
    private function usageReport(int $departmentId, string $from, string $to)
    {
        // Chuỗi trong DB::raw là hằng, không ghép từ dữ liệu người dùng
        return DB::table(self::TABLE)
            ->join('standard_imports', self::TABLE.'.import_id', '=', 'standard_imports.id')
            ->join('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('chem_names', 'standard_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('units', 'standard_categories.unit_id', '=', 'units.id')
            ->select(
                'standard_categories.id as category_id',
                'standard_categories.code as category_code',
                'standard_categories.version',
                'standard_categories.groups',
                'chem_names.name as standard_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
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
                'standard_categories.id',
                'standard_categories.code',
                'standard_categories.version',
                'standard_categories.groups',
                'chem_names.name',
                'units.short_name',
                'units.name'
            )
            ->orderBy('standard_categories.code', 'asc')
            ->get()
            ->map(function ($row) {
                $row->used = (float) $row->used;
                $row->cancelled = (float) $row->cancelled;
                $row->total = (float) $row->total;
                $row->unit = $row->unit_short_name ?: $row->unit_name;

                return $row;
            });
    }

    /** Người kiểm tra: user đang hoạt động của phòng ban đang chọn. */
    private function checkerOptions(int $departmentId)
    {
        // user_management.deparment lưu theo deparments.shortName chứ không phải id
        return DB::table('user_management')
            ->join('deparments', 'user_management.deparment', '=', 'deparments.shortName')
            ->select('user_management.userName', 'user_management.fullName')
            ->where('deparments.id', $departmentId)
            ->where('user_management.isActive', 1)
            ->where('user_management.isLocked', 0)
            ->orderBy('user_management.fullName', 'asc')
            ->get();
    }

    /** Tổng một cột số theo từng ống chuẩn trong phòng ban: [import_id => tổng]. */
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
     * Tồn còn lại của một ống chuẩn, có thể bỏ qua một phiếu xuất đang được sửa.
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

        $balanced = (float) DB::table('standard_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('balancing_amount');

        return max((float) $import->amount + $balanced - (float) $query->sum('amount'), 0);
    }

    /** Hạn mức được xuất: tồn còn lại cộng thêm phần vượt cho phép. */
    private function maxIssuable(float $remaining): float
    {
        return $remaining * (1 + self::OVER_ISSUE_RATIO);
    }

    private function findImport($importId, int $departmentId)
    {
        // Kèm shelf_life_months (theo cấu hình phòng ban) để kiểm tra hạn dùng nội bộ khi xuất
        $query = DB::table('standard_imports')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id');

        return DepartmentStandard::join($query, $departmentId, 'standard_imports.category_id')
            ->select('standard_imports.*', DepartmentStandard::shelfLifeColumn())
            ->where('standard_imports.id', $importId)
            ->where('standard_imports.department_id', $departmentId)
            ->where('standard_imports.status_id', 1)
            ->first();
    }

    /**
     * Kiểm tra ống chuẩn được chọn và số lượng xuất.
     *
     * @param  int|null  $ignoreExportId  phiếu xuất đang sửa, không tính vào tồn
     * @param  int|null  $currentImportId  ống bản ghi đang giữ; giữ nguyên ống này thì
     *                                     không xét lại điều kiện hạn dùng / còn tồn
     */
    private function checkImport($validator, Request $request, $import, ?int $ignoreExportId = null, ?int $currentImportId = null): void
    {
        $validator->after(function ($validator) use ($request, $import, $ignoreExportId, $currentImportId) {
            if (! $import) {
                $validator->errors()->add('import_id', 'Ống chuẩn được chọn không tồn tại hoặc đã bị khoá.');

                return;
            }

            $remaining = $this->remaining($import, $ignoreExportId);

            // Chỉ chặn khi người dùng CHỌN một ống khác với ống bản ghi đang giữ
            if ((int) $import->id !== (int) $currentImportId) {
                if ($import->expired_date && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt(now()->startOfDay())) {
                    $validator->errors()->add(
                        'import_id',
                        'Ống chuẩn '.$import->code.' đã hết hạn sử dụng ngày '
                        .\Carbon\Carbon::parse($import->expired_date)->format('d/m/Y').', không được xuất ra sử dụng.'
                    );

                    return;
                }

                if ($remaining <= self::EPSILON) {
                    $validator->errors()->add('import_id', 'Ống chuẩn '.$import->code.' đã hết tồn, vui lòng chọn ống khác.');

                    return;
                }

                // Chất chuẩn có khai báo hạn dùng mặc định thì phải xác định hạn dùng nội bộ
                // (hạn sau khi mở ống) trước khi dùng.
                if ((int) ($import->shelf_life_months ?? 0) > 0 && ! $import->internal_expired_date) {
                    $validator->errors()->add(
                        'import_id',
                        'Ống chuẩn '.$import->code.' chưa xác định hạn dùng nội bộ nên chưa được sử dụng. '
                        .'Vào màn hình Tồn Kho Chất Chuẩn, tab "Chưa Xác Định Hạn Nội Bộ" để xác định trước.'
                    );

                    return;
                }
            }

            if (! is_numeric($request->amount)) {
                return;
            }

            $limit = $this->maxIssuable($remaining);

            if ((float) $request->amount > $limit + self::EPSILON) {
                $validator->errors()->add(
                    'amount',
                    'Ống chuẩn '.$import->code.' còn '.$this->number($remaining).'. Được xuất vượt tối đa '
                    .(int) round(self::OVER_ISSUE_RATIO * 100).'%, tức không quá '.$this->number($limit).'.'
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
        return session('user')['fullName'] ?? 'NA';
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }

    private function rules(int $departmentId): array
    {
        return [
            'group_id' => ['required', 'exists:groups,id'],
            'import_id' => ['required', 'exists:standard_imports,id'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'type' => ['required', 'in:'.implode(',', array_keys(self::TYPES))],
            'exported_date' => ['required', 'date'],
            'product_name' => ['nullable', 'max:255'],
            'analyst_id' => ['nullable', 'exists:analysts,id'],
            'request_item_id' => ['nullable', 'exists:request_items,id'],
            'purpose' => ['nullable', 'max:500'],
            // Số phiếu kiểm nghiệm đã dùng chất chuẩn này, hoặc căn cứ loại bỏ (OOS, BCSL)
            'test_report_no' => ['nullable', 'max:100'],
            // Chỉ ghi vào lịch sử điều chỉnh, không lưu thành cột của standard_exports
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
            'group_id' => (int) $request->group_id,
            'amount' => (float) $request->amount,
            'type' => $request->type,
            'exported_date' => $request->exported_date,
            'product_name' => $this->nullIfBlank($request->product_name),
            'analyst_id' => $request->filled('analyst_id') ? (int) $request->analyst_id : null,
            'request_item_id' => $request->filled('request_item_id') ? (int) $request->request_item_id : null,
            'purpose' => $this->nullIfBlank($request->purpose),
            'test_report_no' => $this->nullIfBlank($request->test_report_no),
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
            'group_id.required' => 'Vui lòng chọn Tổ sử dụng.',
            'group_id.exists' => 'Tổ được chọn không tồn tại.',
            'import_id.required' => 'Vui lòng chọn ống chuẩn đã được cấp phát.',
            'import_id.exists' => 'Ống chuẩn được chọn không tồn tại.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'type.required' => 'Vui lòng chọn loại phiếu.',
            'type.in' => 'Loại phiếu không hợp lệ.',
            'exported_date.required' => 'Vui lòng chọn ngày sử dụng.',
            'exported_date.date' => 'Ngày sử dụng không hợp lệ.',
            'purpose.max' => 'Mục đích sử dụng tối đa 500 ký tự.',
            'test_report_no.max' => 'Số phiếu KN, OOS, BCSL tối đa 100 ký tự.',
            'adjust_reason.max' => 'Lý do điều chỉnh tối đa 500 ký tự.',
            'checked_by.in' => 'Người kiểm tra phải là nhân viên đang hoạt động của phòng ban này.',
        ];
    }
}
