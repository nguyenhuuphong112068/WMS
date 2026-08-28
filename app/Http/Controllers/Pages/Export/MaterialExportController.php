<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * SỬ DỤNG - SỬ DỤNG VẬT TƯ
 *
 * Khác chất chuẩn: vật tư BẮT BUỘC phải qua ĐỀ NGHỊ được phê duyệt trước khi lấy ra dùng.
 *
 *   1. Tổ lập ĐỀ NGHỊ (material_request_lists + items) -> Trình ký.
 *   2. Trưởng/Phó Phòng ký (BẮT BUỘC). Nếu phiếu đánh dấu "cần Ban Giám Đốc" thì ký xong
 *      chuyển tiếp Ban Giám Đốc ký (TUỲ CHỌN). Ký đủ -> approved, issue_status = waiting.
 *   3. Kho CẤP PHÁT từng dòng: chỉ định mã lô, số lượng -> material_request_items.status = issued.
 *   4. Tổ SỬ DỤNG dòng đã cấp phát -> sinh một bản ghi material_exports (trừ tồn).
 *
 *   LOẠI BỎ (type = cancel) hàng hỏng / hết hạn không phải "sử dụng" nên lập thẳng trên
 *   material_exports, không cần đề nghị; bắt buộc nhập lý do và không được vượt tồn quá 5%.
 *
 * Trạng thái tồn dùng công thức: nhập + cân đối - đã xuất (kể cả loại bỏ).
 */
class MaterialExportController extends Controller
{
    private const TABLE = 'material_exports';

    private const HISTORY_TABLE = 'material_export_histories';

    private const REQ_LIST = 'material_request_lists';

    private const REQ_ITEM = 'material_request_items';

    private const LABEL = 'phiếu sử dụng vật tư';

    private const EPSILON = 0.00005;

    private const OVER_ISSUE_RATIO = 0.05;

    public const TYPES = ['export' => 'Sử dụng', 'cancel' => 'Loại bỏ'];

    /** Trường theo dõi khi điều chỉnh phiếu sử dụng: cột => tên hiển thị. */
    private const FIELDS = [
        'amount' => 'Số lượng',
        'type' => 'Loại phiếu',
        'product_name' => 'Tên sản phẩm',
        'test_report_no' => 'Số phiếu kiểm nghiệm',
        'reason' => 'Lý do loại bỏ',
    ];

    /* ==========================================================
     |  MÀN HÌNH CHÍNH
     ========================================================== */

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $exports = DB::table(self::TABLE)
            ->leftJoin('material_imports', self::TABLE.'.import_id', '=', 'material_imports.id')
            ->leftJoin('material_categories', 'material_imports.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('groups', self::TABLE.'.group_id', '=', 'groups.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_imports.category_id'))
            ->select(
                self::TABLE.'.*',
                'material_names.name as material_name',
                'material_categories.technical_specification',
                'groups.name as group_name',
                'units.short_name as unit_short_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy(self::TABLE.'.created_at', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        $requestLists = DB::table(self::REQ_LIST)
            ->leftJoin('groups', self::REQ_LIST.'.group_id', '=', 'groups.id')
            ->select(self::REQ_LIST.'.*', 'groups.name as group_name')
            ->where(self::REQ_LIST.'.department_id', $departmentId)
            ->orderBy(self::REQ_LIST.'.id', 'desc')
            ->get()
            ->map(function ($req) {
                $req->pending_step = $this->requestPendingStep($req->app_status);
                $req->can_sign = $req->pending_step ? $this->canSignRequest($req->pending_step) : false;

                return $req;
            });

        $requestItems = DB::table(self::REQ_ITEM)
            ->leftJoin('material_categories', self::REQ_ITEM.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('material_imports', self::REQ_ITEM.'.import_id', '=', 'material_imports.id')
            ->select(
                self::REQ_ITEM.'.*',
                'material_names.name as category_material_name',
                'material_imports.code as issued_import_code'
            )
            ->whereIn(self::REQ_ITEM.'.request_list_id', $requestLists->pluck('id'))
            ->orderBy(self::REQ_ITEM.'.id', 'asc')
            ->get()
            ->map(function ($item) {
                $item->display_name = $item->category_id ? $item->category_material_name : $item->material_name;

                return $item;
            })
            ->groupBy('request_list_id');

        // Dòng đề nghị đã được lập phiếu sử dụng (còn hiệu lực) - để ẩn nút "Lập phiếu sử dụng"
        $usedRequestItemIds = DB::table(self::TABLE)
            ->where('department_id', $departmentId)
            ->where('type', 'export')
            ->where('status_id', 1)
            ->whereNotNull('request_item_id')
            ->pluck('request_item_id')
            ->all();

        session()->put(['title' => 'SỬ DỤNG - SỬ DỤNG VẬT TƯ']);

        return view('pages.export.MaterialExport.list', [
            'exports' => $exports,
            'requestLists' => $requestLists,
            'requestItems' => $requestItems,
            'groups' => $this->groupOptions($departmentId),
            'categories' => DepartmentMaterial::importCategoryOptions($departmentId),
            'units' => $this->unitOptions(),
            'availableImports' => $this->importOptions($departmentId),
            'adjustCounts' => $this->adjustCounts($departmentId),
            'reqAppStatuses' => config('material.request_app_statuses'),
            'reqSignSteps' => config('material.request_sign_steps'),
            'reqIssueStatuses' => config('material.request_issue_statuses'),
            'reqItemStatuses' => config('material.request_item_statuses'),
            'canSignManager' => $this->canSignRequest('manager'),
            'canSignDirector' => $this->canSignRequest('director'),
            'usedRequestItemIds' => $usedRequestItemIds,
            'overIssuePercent' => (int) round(self::OVER_ISSUE_RATIO * 100),
            'activeTab' => $request->query('tab') === 'request' ? 'request' : 'book',
        ]);
    }

    /* ==========================================================
     |  ĐỀ NGHỊ CẤP PHÁT
     ========================================================== */

    public function requestStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), $this->requestRules(), $this->requestMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'requestCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'request');
        }

        $isDraft = $request->input('action_type', 'send') === 'draft';

        $deptStr = str_pad((string) $departmentId, 2, '0', STR_PAD_LEFT);
        $groupStr = str_pad((string) $request->group_id, 2, '0', STR_PAD_LEFT);
        $prefix = $deptStr.$groupStr.date('dmy').'_';

        $latest = DB::table(self::REQ_LIST)->where('code', 'LIKE', $prefix.'%')->orderBy('id', 'desc')->value('code');
        $seq = 1;
        if ($latest) {
            $parts = explode('_', $latest);
            $seq = (int) end($parts) + 1;
        }
        $code = $prefix.str_pad((string) $seq, 2, '0', STR_PAD_LEFT);

        $listId = DB::transaction(function () use ($request, $departmentId, $code, $isDraft) {
            $listId = DB::table(self::REQ_LIST)->insertGetId([
                'code' => $code,
                'department_id' => $departmentId,
                'group_id' => (int) $request->group_id,
                'note' => $this->nullIfBlank($request->note),
                'app_status' => $isDraft ? 'draft' : 'pending_manager',
                'needs_director' => $request->boolean('needs_director'),
                'submitted_by' => $isDraft ? null : $this->actor(),
                'submitted_at' => $isDraft ? null : now(),
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->insertRequestItems($listId, $request);

            return $listId;
        });

        AuditTrialController::log(
            $isDraft ? 'Lưu tạm đề nghị cấp phát vật tư' : 'Trình ký đề nghị cấp phát vật tư',
            self::REQ_LIST,
            $listId,
            'NA',
            'Đề nghị '.$code.' ('.count($request->items).' mục)'
        );

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'request'])->with(
            'success',
            $isDraft ? 'Đã lưu tạm đề nghị '.$code.'!' : 'Đã trình ký đề nghị '.$code.' lên Trưởng/Phó Phòng!'
        );
    }

    public function requestUpdate(Request $request)
    {
        $departmentId = $this->departmentId();

        $req = DB::table(self::REQ_LIST)
            ->where('id', $request->request_list_id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $req || ! in_array($req->app_status, ['draft', 'rejected'])) {
            return redirect()->back()->with('error', 'Chỉ sửa được đề nghị đang ở trạng thái Nháp hoặc Bị từ chối!')->with('activeTab', 'request');
        }

        $validator = Validator::make($request->all(), $this->requestRules() + [
            'request_list_id' => ['required', 'exists:'.self::REQ_LIST.',id'],
        ], $this->requestMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'requestCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'request');
        }

        $isDraft = $request->input('action_type', 'draft') === 'draft';

        DB::transaction(function () use ($request, $req, $isDraft) {
            DB::table(self::REQ_LIST)->where('id', $req->id)->update([
                'group_id' => (int) $request->group_id,
                'note' => $this->nullIfBlank($request->note),
                'needs_director' => $request->boolean('needs_director'),
                'app_status' => $isDraft ? 'draft' : 'pending_manager',
                'submitted_by' => $isDraft ? $req->submitted_by : $this->actor(),
                'submitted_at' => $isDraft ? $req->submitted_at : now(),
                'manager_signed_by' => null, 'manager_signed_at' => null,
                'director_signed_by' => null, 'director_signed_at' => null,
                'rejected_by' => null, 'rejected_at' => null, 'reject_step' => null, 'reject_reason' => null,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            DB::table(self::REQ_ITEM)->where('request_list_id', $req->id)->delete();
            $this->insertRequestItems($req->id, $request);
        });

        AuditTrialController::log('Cập nhật đề nghị cấp phát vật tư', self::REQ_LIST, $req->id, $req->code, $isDraft ? 'Lưu tạm' : 'Trình ký lại');

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'request'])->with(
            'success',
            $isDraft ? 'Đã lưu đề nghị '.$req->code.'!' : 'Đã trình ký lại đề nghị '.$req->code.'!'
        );
    }

    public function requestSubmit(Request $request)
    {
        $req = $this->findRequest($request->request_list_id);

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cần trình ký!')->with('activeTab', 'request');
        }

        if (! in_array($req->app_status, ['draft', 'rejected'])) {
            return redirect()->back()->with('error', 'Đề nghị '.$req->code.' không ở trạng thái sửa được nên không trình ký lại!')->with('activeTab', 'request');
        }

        if (! DB::table(self::REQ_ITEM)->where('request_list_id', $req->id)->exists()) {
            return redirect()->back()->with('error', 'Đề nghị '.$req->code.' chưa có mục nào, chưa trình ký được!')->with('activeTab', 'request');
        }

        DB::table(self::REQ_LIST)->where('id', $req->id)->update([
            'app_status' => 'pending_manager',
            'submitted_by' => $this->actor(),
            'submitted_at' => now(),
            'manager_signed_by' => null, 'manager_signed_at' => null,
            'director_signed_by' => null, 'director_signed_at' => null,
            'rejected_by' => null, 'rejected_at' => null, 'reject_step' => null, 'reject_reason' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Trình ký đề nghị cấp phát vật tư', self::REQ_LIST, $req->id, $req->code, 'app_status: pending_manager');

        return redirect()->back()->with('success', 'Đã trình ký đề nghị '.$req->code.' lên Trưởng/Phó Phòng!')->with('activeTab', 'request');
    }

    public function requestSignManager(Request $request)
    {
        return $this->requestSign($request, 'manager');
    }

    public function requestSignDirector(Request $request)
    {
        return $this->requestSign($request, 'director');
    }

    public function requestReject(Request $request)
    {
        $req = $this->findRequest($request->request_list_id);

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cần từ chối!')->with('activeTab', 'request');
        }

        $step = $this->requestPendingStep($req->app_status);

        if (! $step) {
            return redirect()->back()->with('error', 'Đề nghị '.$req->code.' không ở bước chờ ký nên không từ chối được!')->with('activeTab', 'request');
        }

        if (! $this->canSignRequest($step)) {
            return redirect()->back()->with('error', 'Bạn không có quyền ký bước "'.config('material.request_sign_steps')[$step]['label'].'"!')->with('activeTab', 'request');
        }

        $validator = Validator::make($request->all(), [
            'reject_reason' => ['required', 'max:500'],
        ], [
            'reject_reason.required' => 'Vui lòng nhập lý do từ chối.',
            'reject_reason.max' => 'Lý do từ chối tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'requestRejectErrors')->withInput()->with('activeTab', 'request');
        }

        DB::table(self::REQ_LIST)->where('id', $req->id)->update([
            'app_status' => 'rejected',
            'rejected_by' => $this->actor(),
            'rejected_at' => now(),
            'reject_step' => $step,
            'reject_reason' => $request->reject_reason,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Từ chối đề nghị cấp phát vật tư', self::REQ_LIST, $req->id, 'app_status: '.$req->app_status, 'app_status: rejected');

        return redirect()->back()->with('success', 'Đã từ chối đề nghị '.$req->code.'. Tổ cần sửa lại rồi trình ký lại.')->with('activeTab', 'request');
    }

    public function requestDestroy(Request $request)
    {
        $req = $this->findRequest($request->request_list_id);

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị này.')->with('activeTab', 'request');
        }

        if (! in_array($req->app_status, ['draft', 'rejected'])) {
            return redirect()->back()->with('error', 'Chỉ huỷ được đề nghị đang Nháp hoặc Bị từ chối.')->with('activeTab', 'request');
        }

        DB::table(self::REQ_LIST)->where('id', $req->id)->update(['app_status' => 'canceled', 'updated_at' => now()]);

        AuditTrialController::log('Huỷ đề nghị cấp phát vật tư', self::REQ_LIST, $req->id, $req->code, 'Đã huỷ');

        return redirect()->back()->with('success', 'Đã huỷ đề nghị '.$req->code.'.')->with('activeTab', 'request');
    }

    /** Ghi nhận một bước ký của đề nghị: kiểm tra đúng bước, đúng quyền rồi chuyển trạng thái. */
    private function requestSign(Request $request, string $step)
    {
        $config = config('material.request_sign_steps')[$step];
        $req = $this->findRequest($request->request_list_id);

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cần ký duyệt!')->with('activeTab', 'request');
        }

        if ($req->app_status !== $config['from']) {
            return redirect()->back()->with('error', 'Đề nghị '.$req->code.' không ở bước "'.$config['label'].'"!')->with('activeTab', 'request');
        }

        if (! $this->canSignRequest($step)) {
            return redirect()->back()->with('error', 'Bạn không có quyền ký bước "'.$config['label'].'"!')->with('activeTab', 'request');
        }

        // Trưởng/Phó Phòng ký xong: sang Ban Giám Đốc nếu phiếu cần, ngược lại duyệt luôn
        $to = ($step === 'manager' && $req->needs_director) ? 'pending_director' : 'approved';

        $payload = [
            'app_status' => $to,
            $config['signed_by'] => $this->actor(),
            $config['signed_at'] => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ];

        if ($to === 'approved') {
            $payload['issue_status'] = 'waiting';
        }

        DB::table(self::REQ_LIST)->where('id', $req->id)->update($payload);

        AuditTrialController::log('Ký duyệt đề nghị cấp phát vật tư', self::REQ_LIST, $req->id, 'app_status: '.$req->app_status, 'app_status: '.$to);

        return redirect()->back()->with(
            'success',
            $to === 'approved'
                ? 'Đã phê duyệt đề nghị '.$req->code.'! Kho có thể cấp phát.'
                : 'Đã ký bước '.$config['label'].' cho đề nghị '.$req->code.'! Chuyển lên Ban Giám Đốc.'
        )->with('activeTab', 'request');
    }

    /* ==========================================================
     |  CẤP PHÁT CỦA KHO
     ========================================================== */

    public function issueStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::REQ_ITEM.',id'],
            'import_id' => ['required', 'exists:material_imports,id'],
            'issued_amount' => ['required', 'numeric', 'min:0.0001'],
            'issued_unit' => ['nullable', 'string', 'max:50'],
            'issued_at' => ['nullable', 'date'],
        ], [
            'item_id.required' => 'Không tìm thấy mục đề nghị cần cấp phát.',
            'import_id.required' => 'Vui lòng chọn mã lô trong kho để cấp phát.',
            'issued_amount.required' => 'Vui lòng nhập số lượng cấp phát.',
            'issued_amount.min' => 'Số lượng cấp phát phải lớn hơn 0.',
        ]);

        $fail = function ($message) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }

            return redirect()->back()->with('error', $message)->with('activeTab', 'request');
        };

        if ($validator->fails()) {
            return $fail($validator->errors()->first());
        }

        $item = DB::table(self::REQ_ITEM)->where('id', $request->item_id)->first();
        $req = $item ? DB::table(self::REQ_LIST)->where('id', $item->request_list_id)->where('department_id', $departmentId)->first() : null;

        if (! $item || ! $req) {
            return $fail('Không tìm thấy mục đề nghị của phòng ban này!');
        }

        if ($req->app_status !== 'approved') {
            return $fail('Đề nghị '.$req->code.' chưa được phê duyệt nên chưa cấp phát được!');
        }

        if ($item->status === 'issued') {
            return $fail('Mục này đã được cấp phát rồi!');
        }

        $import = DB::table('material_imports')->where('id', $request->import_id)->where('department_id', $departmentId)->where('status_id', 1)->first();

        if (! $import) {
            return $fail('Không tìm thấy mã lô trong kho phòng ban này!');
        }

        if ($import->expired_date && now()->startOfDay()->gt(\Carbon\Carbon::parse($import->expired_date))) {
            return $fail('Mã lô '.$import->code.' đã hết hạn sử dụng, không được cấp phát!');
        }

        $issuedAt = $request->filled('issued_at') ? \Carbon\Carbon::parse($request->issued_at) : now();

        DB::table(self::REQ_ITEM)->where('id', $item->id)->update([
            'import_id' => (int) $import->id,
            'import_code' => $import->code,
            'issued_amount' => (float) $request->issued_amount,
            'issued_unit' => $this->nullIfBlank($request->issued_unit ?: $item->requested_unit),
            'issued_by' => $this->actor(),
            'issued_at' => $issuedAt,
            'status' => 'issued',
            'updated_at' => now(),
        ]);

        $this->refreshIssueStatus($item->request_list_id);

        AuditTrialController::log('Cấp phát vật tư', self::REQ_ITEM, $item->id, 'pending', 'Cấp lô '.$import->code.' số lượng '.$request->issued_amount);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cấp phát mã lô '.$import->code.'!',
                'data' => [
                    'issued_amount' => (float) $request->issued_amount,
                    'issued_unit' => $this->nullIfBlank($request->issued_unit ?: $item->requested_unit),
                    'issued_by' => $this->actor(),
                    'issued_at' => $issuedAt->format('d/m/Y H:i'),
                    'import_code' => $import->code,
                ],
            ]);
        }

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'request'])
            ->with('success', 'Đã cấp phát mã lô '.$import->code.'!');
    }

    public function issueReject(Request $request)
    {
        $departmentId = $this->departmentId();

        $item = DB::table(self::REQ_ITEM)->where('id', $request->item_id)->first();
        $req = $item ? DB::table(self::REQ_LIST)->where('id', $item->request_list_id)->where('department_id', $departmentId)->first() : null;

        if (! $item || ! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy mục đề nghị!')->with('activeTab', 'request');
        }

        DB::table(self::REQ_ITEM)->where('id', $item->id)->update([
            'status' => 'rejected',
            'note' => $this->nullIfBlank($request->note ?: $request->cancel_reason ?: $item->note),
            'updated_at' => now(),
        ]);

        $this->refreshIssueStatus($item->request_list_id);

        AuditTrialController::log('Từ chối cấp phát vật tư', self::REQ_ITEM, $item->id, $item->status, 'rejected');

        return redirect()->back()->with('success', 'Đã từ chối cấp phát mục đề nghị.')->with('activeTab', 'request');
    }

    /* ==========================================================
     |  PHIẾU SỬ DỤNG / LOẠI BỎ (trừ tồn)
     ========================================================== */

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'type' => ['required', 'in:'.implode(',', array_keys(self::TYPES))],
            'request_item_id' => ['nullable', 'exists:'.self::REQ_ITEM.',id'],
            'import_id' => ['nullable', 'exists:material_imports,id'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'product_name' => ['nullable', 'max:255'],
            'test_report_no' => ['nullable', 'max:100'],
            'reason' => ['nullable', 'max:500'],
            'adjust_reason' => ['nullable', 'max:500'],
        ], $this->messages());

        $type = $request->input('type');
        $import = null;
        $item = null;
        $groupId = null;

        // Chạy trong after() để lỗi tự thêm không bị passes() xoá khi gọi fails()
        $validator->after(function ($v) use ($request, $departmentId, $type, &$import, &$item, &$groupId) {
            [$import, $item, $groupId] = $this->resolveUseTarget($v, $request, $departmentId, $type);
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId([
            'code' => $import->code,
            'import_id' => (int) $import->id,
            'department_id' => $departmentId,
            'group_id' => $groupId,
            'request_item_id' => $item?->id,
            'amount' => (float) $request->amount,
            'type' => $type,
            'product_name' => $this->nullIfBlank($request->product_name),
            'test_report_no' => $this->nullIfBlank($request->test_report_no),
            'reason' => $this->nullIfBlank($request->reason),
            'used_by' => $this->actor(),
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
            self::TYPES[$type].' vật tư, mã lô: '.$import->code.', số lượng: '.$request->amount
        );

        return redirect()->back()->with('success', 'Đã ghi nhận '.self::LABEL.' cho mã lô '.$import->code.'!');
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

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'product_name' => ['nullable', 'max:255'],
            'test_report_no' => ['nullable', 'max:100'],
            'reason' => ['nullable', 'max:500'],
            'adjust_reason' => ['nullable', 'max:500'],
        ], $this->messages());

        $import = DB::table('material_imports')->where('id', $current->import_id)->first();

        $validator->after(function ($validator) use ($request, $import, $current) {
            if ($import && is_numeric($request->amount)) {
                $limit = $this->remaining($import, (int) $current->id) * (1 + self::OVER_ISSUE_RATIO);
                if ((float) $request->amount > $limit + self::EPSILON) {
                    $validator->errors()->add('amount', 'Mã lô '.$import->code.' chỉ còn cho phép xuất tối đa '.$this->number($limit).'.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = [
            'amount' => (float) $request->amount,
            'type' => $current->type,
            'product_name' => $this->nullIfBlank($request->product_name),
            'test_report_no' => $this->nullIfBlank($request->test_report_no),
            'reason' => $this->nullIfBlank($request->reason),
        ];

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
        $action = $newStatus == 1 ? 'Mở khoá' : 'Khoá';

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->logHistory($current->id, $action, 'Trạng thái: '.($current->status_id == 1 ? 'Hiệu lực' : 'Đã khoá').' -> '.($newStatus == 1 ? 'Hiệu lực' : 'Đã khoá'));

        AuditTrialController::log($action, self::TABLE, $current->id, 'status_id: '.$current->status_id, 'status_id: '.$newStatus);

        return redirect()->back()->with('success', ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' '.$current->code.'!');
    }

    public function history(Request $request)
    {
        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('material_imports', self::HISTORY_TABLE.'.import_id', '=', 'material_imports.id')
            ->leftJoin('material_categories', 'material_imports.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $this->departmentId(), 'material_imports.category_id'))
            ->select(
                self::HISTORY_TABLE.'.*',
                'material_names.name as material_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->whereIn(self::HISTORY_TABLE.'.material_export_id', function ($query) {
                $query->select('id')->from(self::TABLE)->where('department_id', $this->departmentId());
            })
            ->where(self::HISTORY_TABLE.'.material_export_id', $request->id)
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
                        'Mã lô' => $row->code ?: '—',
                        'Vật tư' => $row->material_name ?: '—',
                        'Số lượng' => $row->amount !== null ? $this->number((float) $row->amount).' '.$unit : '—',
                        'Loại phiếu' => self::TYPES[$row->type] ?? ($row->type ?: '—'),
                        'Tên sản phẩm' => $row->product_name ?: '—',
                        'Số phiếu kiểm nghiệm' => $row->test_report_no ?: '—',
                        'Lý do loại bỏ' => $row->reason ?: '—',
                        'Trạng thái' => $row->status_id == 1 ? 'Hiệu lực' : 'Đã khoá',
                    ],
                ];
            })->values(),
        ]);
    }

    /* ==========================================================
     |  AJAX HỖ TRỢ FORM
     ========================================================== */

    /** Tra mã lô khi quét mã QR trên nhãn, trả JSON cho form. */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->query('code'));
        $import = $this->importOptions($this->departmentId())->firstWhere('code', $code);

        if (! $import) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy mã lô "'.$code.'" trong kho phòng ban.']);
        }

        return response()->json([
            'ok' => $import->selectable,
            'id' => $import->id,
            'code' => $import->code,
            'material_name' => $import->material_name,
            'remaining' => $import->remaining,
            'unit' => $import->unit_short_name,
            'expired_date' => $import->expired_date,
            'message' => $import->selectable ? '' : ($import->expired ? 'Mã lô đã hết hạn.' : 'Mã lô đã hết tồn.'),
        ]);
    }

    /** category_id -> đơn vị / quy cách mặc định, để điền sẵn dòng đề nghị. */
    public function getCategoryInfo(Request $request)
    {
        $departmentId = $this->departmentId();

        $row = DepartmentMaterial::importCategoryOptions($departmentId)->firstWhere('id', (int) $request->query('category_id'));

        if (! $row) {
            return response()->json(['ok' => false]);
        }

        return response()->json([
            'ok' => true,
            'unit' => $row->unit_short_name,
            'technical_specification' => $row->technical_specification,
        ]);
    }

    /** Các dòng đề nghị đã CẤP PHÁT cho một Tổ nhưng CHƯA lập phiếu sử dụng. */
    public function getIssuedItems(Request $request)
    {
        $departmentId = $this->departmentId();
        $groupId = (int) $request->query('group_id');

        $rows = DB::table(self::REQ_ITEM)
            ->join(self::REQ_LIST, self::REQ_ITEM.'.request_list_id', '=', self::REQ_LIST.'.id')
            ->leftJoin('material_categories', self::REQ_ITEM.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('material_imports', self::REQ_ITEM.'.import_id', '=', 'material_imports.id')
            ->select(
                self::REQ_ITEM.'.id',
                self::REQ_ITEM.'.category_id',
                self::REQ_ITEM.'.material_name',
                self::REQ_ITEM.'.import_id',
                self::REQ_ITEM.'.import_code',
                self::REQ_ITEM.'.requested_amount',
                self::REQ_ITEM.'.issued_amount',
                self::REQ_ITEM.'.issued_unit',
                self::REQ_ITEM.'.product_name',
                self::REQ_ITEM.'.purpose',
                self::REQ_LIST.'.code as request_code',
                self::REQ_LIST.'.group_id',
                'material_names.name as category_material_name'
            )
            ->where(self::REQ_LIST.'.department_id', $departmentId)
            ->where(self::REQ_LIST.'.group_id', $groupId)
            ->where(self::REQ_ITEM.'.status', 'issued')
            ->whereNotNull(self::REQ_ITEM.'.import_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from(self::TABLE)
                    ->whereColumn(self::TABLE.'.request_item_id', self::REQ_ITEM.'.id')
                    ->where(self::TABLE.'.type', 'export')
                    ->where(self::TABLE.'.status_id', 1);
            })
            ->orderBy(self::REQ_ITEM.'.id', 'desc')
            ->get()
            ->map(function ($row) {
                $import = DB::table('material_imports')->where('id', $row->import_id)->first();
                $balanced = (float) DB::table('material_balancings')->where('import_id', $row->import_id)->where('status_id', 1)->sum('balancing_amount');
                $used = (float) DB::table(self::TABLE)->where('import_id', $row->import_id)->where('status_id', 1)->sum('amount');
                $row->display_name = $row->category_id ? $row->category_material_name : $row->material_name;
                $row->actual_remaining = $import ? max((float) $import->amount + $balanced - $used, 0) : 0;

                return $row;
            });

        return response()->json(['rows' => $rows]);
    }

    /* ==========================================================
     |  HÀM DÙNG CHUNG
     ========================================================== */

    private function insertRequestItems(int $listId, Request $request): void
    {
        foreach ((array) $request->items as $item) {
            $categoryId = ! empty($item['category_id']) ? (int) $item['category_id'] : null;

            DB::table(self::REQ_ITEM)->insert([
                'request_list_id' => $listId,
                'category_id' => $categoryId,
                'material_name' => $categoryId ? null : $this->nullIfBlank($item['material_name'] ?? null),
                'technical_specification' => $this->nullIfBlank($item['technical_specification'] ?? null),
                'requested_amount' => (float) ($item['requested_amount'] ?? 0),
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'product_name' => $this->nullIfBlank($item['product_name'] ?? null),
                'purpose' => $this->nullIfBlank($item['purpose'] ?? null),
                'note' => $this->nullIfBlank($item['note'] ?? null),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** Xác định mã lô + dòng đề nghị + Tổ cho một phiếu sử dụng / loại bỏ. */
    private function resolveUseTarget($validator, Request $request, int $departmentId, string $type): array
    {
        $import = null;
        $item = null;
        $groupId = $request->filled('group_id') ? (int) $request->group_id : null;

        if ($type === 'export') {
            if (! $request->filled('request_item_id')) {
                $validator->errors()->add('request_item_id', 'Vật tư bắt buộc phải lấy từ một dòng đề nghị đã được cấp phát.');

                return [null, null, null];
            }

            $item = DB::table(self::REQ_ITEM)->where('id', $request->request_item_id)->first();
            $req = $item ? DB::table(self::REQ_LIST)->where('id', $item->request_list_id)->where('department_id', $departmentId)->first() : null;

            if (! $item || ! $req) {
                $validator->errors()->add('request_item_id', 'Không tìm thấy dòng đề nghị của phòng ban này.');

                return [null, null, null];
            }

            if ($item->status !== 'issued' || ! $item->import_id) {
                $validator->errors()->add('request_item_id', 'Dòng đề nghị này chưa được kho cấp phát mã lô.');

                return [null, null, null];
            }

            $alreadyUsed = DB::table(self::TABLE)
                ->where('request_item_id', $item->id)
                ->where('type', 'export')
                ->where('status_id', 1)
                ->exists();

            if ($alreadyUsed) {
                $validator->errors()->add('request_item_id', 'Dòng đề nghị này đã được lập phiếu sử dụng.');

                return [null, null, null];
            }

            $import = DB::table('material_imports')->where('id', $item->import_id)->where('department_id', $departmentId)->first();
            $groupId = $req->group_id;
        } else {
            if (! $request->filled('import_id')) {
                $validator->errors()->add('import_id', 'Vui lòng chọn mã lô cần loại bỏ.');

                return [null, null, null];
            }

            $import = DB::table('material_imports')->where('id', $request->import_id)->where('department_id', $departmentId)->where('status_id', 1)->first();

            if (! trim((string) $request->reason)) {
                $validator->errors()->add('reason', 'Vui lòng nhập lý do loại bỏ.');
            }
        }

        if (! $import) {
            $validator->errors()->add('import_id', 'Không tìm thấy mã lô trong kho phòng ban này.');

            return [null, null, null];
        }

        if (is_numeric($request->amount)) {
            $limit = $this->remaining($import) * (1 + self::OVER_ISSUE_RATIO);
            if ((float) $request->amount > $limit + self::EPSILON) {
                $validator->errors()->add(
                    'amount',
                    'Mã lô '.$import->code.' còn '.$this->number($this->remaining($import)).'. Được xuất vượt tối đa '
                    .(int) round(self::OVER_ISSUE_RATIO * 100).'%, tức không quá '.$this->number($limit).'.'
                );
            }
        }

        return [$import, $item, $groupId];
    }

    /** Cập nhật issue_status của đề nghị theo trạng thái các dòng. */
    private function refreshIssueStatus(int $listId): void
    {
        $items = DB::table(self::REQ_ITEM)->where('request_list_id', $listId)->get();
        $pending = $items->where('status', 'pending')->count();
        $issued = $items->where('status', 'issued')->count();

        $status = $pending === 0 ? 'completed' : ($issued > 0 ? 'partial' : 'waiting');

        DB::table(self::REQ_LIST)->where('id', $listId)->update(['issue_status' => $status, 'updated_at' => now()]);
    }

    private function logHistory($id, string $action, ?string $note = null): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'material_export_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'import_id' => $row->import_id,
            'amount' => $row->amount,
            'type' => $row->type,
            'product_name' => $row->product_name,
            'test_report_no' => $row->test_report_no,
            'reason' => $row->reason,
            'used_by' => $row->used_by,
            'status_id' => $row->status_id,
            'change_note' => $note,
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    private function changeNote($current, array $payload, ?string $reason = null): string
    {
        $parts = [];

        foreach (self::FIELDS as $field => $title) {
            $old = $current->$field;
            $new = $payload[$field] ?? null;

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

    /** Mã lô của phòng ban đang chọn, còn hiệu lực, kèm tồn còn lại. */
    private function importOptions(int $departmentId)
    {
        $used = $this->sumByImport(self::TABLE, 'amount', $departmentId);
        $balanced = $this->sumByImport('material_balancings', 'balancing_amount', $departmentId);
        $today = now()->startOfDay();

        return DB::table('material_imports')
            ->leftJoin('material_categories', 'material_imports.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('locations', 'material_imports.location_id', '=', 'locations.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_imports.category_id'))
            ->select(
                'material_imports.id',
                'material_imports.code',
                'material_imports.category_id',
                'material_imports.amount',
                'material_imports.expired_date',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'units.short_name as unit_short_name',
                'locations.code as location_code'
            )
            ->where('material_imports.department_id', $departmentId)
            ->where('material_imports.status_id', 1)
            ->orderBy('material_imports.imported_date', 'desc')
            ->orderBy('material_imports.id', 'desc')
            ->get()
            ->map(function ($import) use ($used, $balanced, $today) {
                $import->used = (float) ($used[$import->id] ?? 0);
                $import->balanced = (float) ($balanced[$import->id] ?? 0);
                $import->remaining = max((float) $import->amount + $import->balanced - $import->used, 0);
                $import->max_amount = $import->remaining * (1 + self::OVER_ISSUE_RATIO);
                $import->expired = $import->expired_date
                    && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt($today);
                $import->selectable = ! $import->expired && $import->remaining > self::EPSILON;

                return $import;
            });
    }

    private function sumByImport(string $table, string $column, int $departmentId)
    {
        return DB::table($table)
            ->select('import_id', DB::raw('SUM(`'.$column.'`) as total'))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');
    }

    private function remaining($import, ?int $ignoreExportId = null): float
    {
        $query = DB::table(self::TABLE)->where('import_id', $import->id)->where('status_id', 1);

        if ($ignoreExportId) {
            $query->where('id', '<>', $ignoreExportId);
        }

        $balanced = (float) DB::table('material_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('balancing_amount');

        return max((float) $import->amount + $balanced - (float) $query->sum('amount'), 0);
    }

    private function adjustCounts(int $departmentId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('material_export_id', DB::raw('COUNT(*) as times'))
            ->whereIn('material_export_id', function ($query) use ($departmentId) {
                $query->select('id')->from(self::TABLE)->where('department_id', $departmentId);
            })
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('material_export_id')
            ->pluck('times', 'material_export_id');
    }

    private function groupOptions(int $departmentId)
    {
        return DB::table('groups')
            ->select('id', 'name')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->get();
    }

    private function unitOptions()
    {
        return DB::table('units')
            ->select('id', 'name', 'short_name')
            ->where('status_id', 1)
            ->where('app_status', 'approved')
            ->orderBy('name', 'asc')
            ->get();
    }

    private function findRequest($id)
    {
        return DB::table(self::REQ_LIST)
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    private function requestPendingStep(?string $appStatus): ?string
    {
        foreach (config('material.request_sign_steps') as $step => $config) {
            if ($config['from'] === $appStatus) {
                return $step;
            }
        }

        return null;
    }

    private function canSignRequest(string $step): bool
    {
        return user_has_any_role(session('user')['userId'] ?? 0, config('material.request_sign_steps')[$step]['roles']);
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

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function requestRules(): array
    {
        return [
            'group_id' => ['required', 'exists:groups,id'],
            'needs_director' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['nullable'],
            'items.*.material_name' => ['nullable', 'string', 'max:255'],
            'items.*.technical_specification' => ['nullable', 'string', 'max:255'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.purpose' => ['nullable', 'string', 'max:500'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function requestMessages(): array
    {
        return [
            'group_id.required' => 'Vui lòng chọn Tổ đề nghị.',
            'group_id.exists' => 'Tổ được chọn không tồn tại.',
            'items.required' => 'Vui lòng thêm ít nhất một vật tư đề nghị.',
            'items.min' => 'Vui lòng thêm ít nhất một vật tư đề nghị.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ];
    }

    private function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại phiếu.',
            'type.in' => 'Loại phiếu không hợp lệ.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'adjust_reason.max' => 'Lý do điều chỉnh tối đa 500 ký tự.',
        ];
    }
}
