<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentMaterial;
use App\Support\MaterialPicking;
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
 *   3. Kho CẤP PHÁT từng dòng: chỉ định mã xuất nhập, số lượng. Hàng rời kho ngay lúc này
 *      nên cấp phát TRỪ TỒN TRỰC TIẾP - sinh luôn một bản ghi material_exports (type =
 *      export) gắn với dòng đề nghị, và material_request_items.status = issued.
 *   4. Tổ chốt lại dòng đã cấp bằng "SỬ DỤNG VẬT TƯ" (useStore):
 *        - Ghi nhận sử dụng: sửa phiếu sử dụng về đúng số THỰC DÙNG, phần dư tự về kho
 *          -> status = used.
 *        - Trả về kho: trả lại số chưa dùng; trả hết thì huỷ phiếu sử dụng (status_id = 0)
 *          nên kho hoàn đủ -> status = returned.
 *
 *   LOẠI BỎ (type = cancel) hàng hỏng / hết hạn không phải "sử dụng" nên lập thẳng trên
 *   material_exports, không cần đề nghị; bắt buộc nhập lý do và không được vượt tồn quá 5%.
 *
 * Trạng thái tồn dùng công thức: nhập + cân đối - đã xuất (kể cả loại bỏ). Vì cấp phát đã
 * sinh sẵn phiếu sử dụng, không có chỗ nào lập phiếu sử dụng thủ công nữa - tránh trừ hai lần.
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
        'product_name' => 'Thiết bị liên quan',
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
            ->leftJoin(self::REQ_ITEM, self::TABLE.'.request_item_id', '=', self::REQ_ITEM.'.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_imports.category_id'))
            ->select(
                self::TABLE.'.*',
                'material_names.name as material_name',
                'material_categories.technical_specification',
                'groups.name as group_name',
                'units.short_name as unit_short_name',
                self::REQ_ITEM.'.purpose'
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
            ->where(self::REQ_ITEM.'.active', 1)
            ->whereIn(self::REQ_ITEM.'.request_list_id', $requestLists->pluck('id'))
            ->orderBy(self::REQ_ITEM.'.id', 'asc')
            ->get()
            ->map(function ($item) {
                $item->display_name = $item->category_id ? $item->category_material_name : $item->material_name;

                return $item;
            })
            ->groupBy('request_list_id');

        // Danh mục vật tư của phòng kèm tồn kho - đổ vào ô chọn dòng đề nghị và bảng "Danh mục tồn của phòng".
        $availableImports = $this->importOptions($departmentId);
        $categories = DepartmentMaterial::importCategoryOptions($departmentId);

        $stockByCategory = $availableImports->groupBy('category_id')->map(fn ($group) => [
            'total_remaining' => (float) $group->sum('remaining'),
            'total_lots' => (int) $group->where('remaining', '>', self::EPSILON)->count(),
        ]);

        $departmentMaterialInventory = $categories->map(function ($cat) use ($stockByCategory) {
            $cat->total_remaining = $stockByCategory[$cat->id]['total_remaining'] ?? 0.0;
            $cat->total_lots = $stockByCategory[$cat->id]['total_lots'] ?? 0;

            return $cat;
        });

        /*
        | Một dòng đề nghị có thể được cấp từ NHIỀU mã xuất nhập: mỗi lô là một phiếu sử
        | dụng riêng trong material_exports (cùng request_item_id). Nạp sẵn để phiếu chi
        | tiết liệt kê đủ các lô đã cấp, và dựng trước kế hoạch chia lô cho dòng còn chờ.
        */
        $flatItems = $requestItems->flatten();
        $issuedLots = $this->issuedLots($flatItems->pluck('id'));
        $lotsByCategory = $availableImports->groupBy('category_id');
        $issuePlans = $this->issuePlans($requestLists, $requestItems, $lotsByCategory);

        session()->put(['title' => 'SỬ DỤNG - SỬ DỤNG VẬT TƯ']);

        return view('pages.export.MaterialExport.list', [
            'exports' => $exports,
            'requestLists' => $requestLists,
            'requestItems' => $requestItems,
            'groups' => $this->groupOptions($departmentId),
            'categories' => $categories,
            'units' => $this->unitOptions(),
            'availableImports' => $availableImports,
            'lotsByCategory' => $lotsByCategory,
            'issuedLots' => $issuedLots,
            'issuePlans' => $issuePlans,
            'departmentMaterialInventory' => $departmentMaterialInventory,
            'adjustCounts' => $this->adjustCounts($departmentId),
            'reqAppStatuses' => config('material.request_app_statuses'),
            'reqSignSteps' => config('material.request_sign_steps'),
            'reqIssueStatuses' => config('material.request_issue_statuses'),
            'reqItemStatuses' => config('material.request_item_statuses'),
            'canSignManager' => $this->canSignRequest('manager'),
            'canSignDirector' => $this->canSignRequest('director'),
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
                'name' => $this->nullIfBlank($request->name),
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
                'name' => $this->nullIfBlank($request->name),
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

            // Không xoá cứng: bỏ hiệu lực các mục cũ (active = 0), dữ liệu vẫn lưu lại.
            DB::table(self::REQ_ITEM)->where('request_list_id', $req->id)->update([
                'active' => 0,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);
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

        if (! DB::table(self::REQ_ITEM)->where('request_list_id', $req->id)->where('active', 1)->exists()) {
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
     |
     |  Một dòng đề nghị được cấp từ MỘT HOẶC NHIỀU mã xuất nhập: lô hạn gần nhất không
     |  đủ thì lấy tiếp lô kế tiếp theo đúng thứ tự nên xuất. Mỗi lô sinh một phiếu sử
     |  dụng riêng (material_exports) để tồn của từng lô trừ đúng phần của nó; dòng đề
     |  nghị chỉ giữ phần tổng hợp: lô đầu tiên + tổng số lượng đã cấp.
     ========================================================== */

    public function issueStore(Request $request)
    {
        $departmentId = $this->departmentId();

        // Dạng cũ (một lô mỗi lần cấp) vẫn nhận được: quy về đúng mảng lots của dạng mới.
        if (! $request->has('lots') && $request->filled('import_id')) {
            $request->merge(['lots' => [['import_id' => $request->import_id, 'amount' => $request->issued_amount]]]);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::REQ_ITEM.',id'],
            'lots' => ['required', 'array', 'min:1'],
            'lots.*.import_id' => ['nullable', 'integer'],
            'lots.*.amount' => ['nullable', 'numeric', 'min:0'],
            'issued_unit' => ['nullable', 'string', 'max:50'],
        ], [
            'item_id.required' => 'Không tìm thấy mục đề nghị cần cấp phát.',
            'lots.required' => 'Vui lòng chọn mã xuất nhập trong kho để cấp phát.',
            'lots.*.import_id.integer' => 'Mã xuất nhập được chọn không hợp lệ.',
            'lots.*.amount.numeric' => 'Số lượng cấp phát phải là số.',
            'lots.*.amount.min' => 'Số lượng cấp phát không được âm.',
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

        /*
        | Cấp thêm được khi dòng CÒN NỢ HÀNG: chưa cấp, mới cấp một phần, hoặc dòng cũ đã
        | ghi 'issued' nhưng số đã cấp vẫn nhỏ hơn số đề nghị. Dòng đã chốt sử dụng / trả
        | về kho / bị từ chối thì đã ra khỏi luồng cấp phát.
        */
        if (! in_array($item->status, ['pending', 'partial', 'issued'], true)) {
            return $fail(match ($item->status) {
                'used' => 'Mục này đã chốt nhật ký sử dụng nên không cấp thêm được!',
                'returned' => 'Mục này đã trả về kho nên không cấp thêm được!',
                'rejected' => 'Mục này đã bị từ chối cấp phát!',
                default => 'Mục này không còn ở trạng thái cấp phát được!',
            });
        }

        if ((float) $item->requested_amount - (float) $item->issued_amount <= self::EPSILON) {
            return $fail('Mục này đã cấp phát đủ số đề nghị rồi!');
        }

        /*
        | Gom dòng cấp phát: bỏ dòng trống, cộng dồn nếu người dùng chọn trùng một mã xuất
        | nhập ở hai dòng - có cộng dồn thì mới chặn đúng tồn của lô đó.
        */
        $wanted = [];

        foreach ((array) $request->input('lots', []) as $lot) {
            $importId = (int) ($lot['import_id'] ?? 0);
            $amount = (float) ($lot['amount'] ?? 0);

            if ($importId <= 0 || $amount <= self::EPSILON) {
                continue;
            }

            $wanted[$importId] = ($wanted[$importId] ?? 0) + $amount;
        }

        if (! $wanted) {
            return $fail('Vui lòng chọn mã xuất nhập và nhập số lượng cấp phát lớn hơn 0!');
        }

        $imports = DB::table('material_imports')
            ->whereIn('id', array_keys($wanted))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->get()
            ->keyBy('id');

        $lines = [];

        foreach ($wanted as $importId => $amount) {
            $import = $imports->get($importId);

            if (! $import) {
                return $fail('Không tìm thấy mã xuất nhập trong kho phòng ban này!');
            }

            if ($import->expired_date && now()->startOfDay()->gt(\Carbon\Carbon::parse($import->expired_date))) {
                return $fail('Mã xuất nhập '.$import->code.' đã hết hạn sử dụng, không được cấp phát!');
            }

            /*
            | Cấp phát trừ tồn ngay nên phải chặn cấp quá số còn lại ngay tại đây. Mốc chặn
            | là tồn CÒN HỨA ĐƯỢC, không phải tồn sổ sách: phần đã hứa cho một đợt lấy hàng
            | còn treo vẫn nằm trong kho nhưng không được đem cấp lẻ lần nữa.
            */
            $available = $this->available($import);
            $limit = $available * (1 + self::OVER_ISSUE_RATIO);

            if ($amount > $limit + self::EPSILON) {
                $held = $this->remaining($import) - $available;

                return $fail(
                    'Mã xuất nhập '.$import->code.' còn hứa được '.$this->number($available)
                    .($held > self::EPSILON ? ' (đang giữ '.$this->number($held).' cho đợt lấy hàng)' : '')
                    .'. Được cấp vượt tối đa '.(int) round(self::OVER_ISSUE_RATIO * 100).'%, tức không quá '.$this->number($limit).'.'
                );
            }

            $lines[] = ['import' => $import, 'amount' => round($amount, 4)];
        }

        // Thời điểm cấp phát luôn là lúc bấm Cấp Phát, không nhận giá trị từ form
        $issuedAt = now();
        $issuedUnit = $this->nullIfBlank($request->issued_unit ?: $item->requested_unit);
        $addedAmount = round(array_sum(array_column($lines, 'amount')), 4);
        $first = $lines[0]['import'];
        $codes = implode(', ', array_map(fn ($line) => $line['import']->code, $lines));

        /*
        | Cấp phát cộng dồn qua nhiều lần: kho thiếu hàng thì cấp trước phần có, dòng đề nghị
        | nằm ở PARTIAL và vẫn cấp thêm được. Đủ số đề nghị mới chuyển sang ISSUED.
        */
        $issuedBefore = (float) $item->issued_amount;
        $issuedAmount = round($issuedBefore + $addedAmount, 4);
        $requestedAmount = (float) $item->requested_amount;
        $shortAfter = round(max($requestedAmount - $issuedAmount, 0), 4);
        $newStatus = $shortAfter > self::EPSILON ? 'partial' : 'issued';

        DB::transaction(function () use ($item, $req, $lines, $first, $issuedAmount, $issuedUnit, $issuedAt, $departmentId, $newStatus) {
            DB::table(self::REQ_ITEM)->where('id', $item->id)->update([
                // Dòng đề nghị chỉ giữ phần tổng hợp; chi tiết từng lô nằm ở material_exports.
                // Lô đầu tiên giữ nguyên qua các lần cấp thêm để còn tra đúng lần cấp đầu.
                'import_id' => (int) ($item->import_id ?: $first->id),
                'import_code' => $item->import_code ?: $first->code,
                'issued_amount' => $issuedAmount,
                'issued_unit' => $issuedUnit,
                'issued_by' => $this->actor(),
                'issued_at' => $issuedAt,
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

            // Cấp phát là hàng đã rời kho: mỗi lô một phiếu sử dụng để trừ tồn ngay.
            // Tổ chốt lại sau bằng "Sử Dụng Vật Tư" (ghi số thực dùng) hoặc trả về kho.
            foreach ($lines as $line) {
                $exportId = DB::table(self::TABLE)->insertGetId([
                    'code' => $line['import']->code,
                    'import_id' => (int) $line['import']->id,
                    'department_id' => $departmentId,
                    'group_id' => $req->group_id,
                    'request_item_id' => $item->id,
                    'amount' => $line['amount'],
                    'type' => 'export',
                    'product_name' => $item->product_name,
                    'used_by' => $this->actor(),
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => $issuedAt,
                    'updated_at' => $issuedAt,
                ]);

                $this->logHistory($exportId, 'Cấp phát', 'Kho cấp phát cho đề nghị '.$req->code.', trừ tồn ngay '.$this->number($line['amount']).' '.($issuedUnit ?: ''));
            }
        });

        $this->refreshIssueStatus($item->request_list_id);

        AuditTrialController::log(
            'Cấp phát vật tư',
            self::REQ_ITEM,
            $item->id,
            $item->status,
            $newStatus.': cấp thêm '.count($lines).' mã xuất nhập ('.$codes.') '.$this->number($addedAmount)
                .', luỹ kế '.$this->number($issuedAmount).'/'.$this->number($requestedAmount).' (đã trừ tồn)'
        );

        $message = count($lines) > 1
            ? 'Đã cấp phát '.$this->number($addedAmount).' '.($issuedUnit ?: '').' từ '.count($lines).' mã xuất nhập: '.$codes.'!'
            : 'Đã cấp phát '.$this->number($addedAmount).' '.($issuedUnit ?: '').' từ mã xuất nhập '.$first->code.'!';

        if ($newStatus === 'partial') {
            $message .= ' Mục này mới cấp '.$this->number($issuedAmount).'/'.$this->number($requestedAmount)
                .' '.($issuedUnit ?: '').', còn thiếu '.$this->number($shortAfter).' - cấp thêm khi có hàng về.';
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'status' => $newStatus,
                    'issued_amount' => $issuedAmount,
                    'short_amount' => $shortAfter,
                    'issued_unit' => $issuedUnit,
                    'issued_by' => $this->actor(),
                    'issued_at' => $issuedAt->format('d/m/Y H:i'),
                    'import_code' => $codes,
                ],
            ]);
        }

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'request'])
            ->with('success', $message);
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
     |  SỬ DỤNG VẬT TƯ ĐÃ CẤP PHÁT
     |
     |  Kho cấp phát là đã trừ tồn, nên ở đây Tổ chỉ chốt lại phiếu sử dụng đã có:
     |    - Ghi nhận sử dụng: nhập số THỰC DÙNG, phần chưa dùng tự cộng lại kho.
     |    - Trả về kho: nhập số TRẢ LẠI, trả hết thì phiếu sử dụng bị huỷ, kho hoàn đủ.
     |  Hai việc quy về một phép tính nên dùng chung một action.
     ========================================================== */

    public function useStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::REQ_ITEM.',id'],
            'action' => ['required', 'in:use,return'],
            'amount' => ['required', 'numeric', 'min:0'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'test_report_no' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'item_id.required' => 'Không tìm thấy mục đề nghị cần ghi nhận.',
            'action.required' => 'Vui lòng chọn Sử dụng hoặc Trả về kho.',
            'amount.required' => 'Vui lòng nhập số lượng.',
        ]);

        $back = fn (string $key, string $message) => redirect()->back()
            ->with($key, $message)
            ->with('activeTab', 'request');

        if ($validator->fails()) {
            return $back('error', $validator->errors()->first());
        }

        $item = DB::table(self::REQ_ITEM)->where('id', $request->item_id)->first();
        $req = $item ? DB::table(self::REQ_LIST)->where('id', $item->request_list_id)->where('department_id', $departmentId)->first() : null;

        if (! $item || ! $req) {
            return $back('error', 'Không tìm thấy mục đề nghị của phòng ban này!');
        }

        // Cấp một phần cũng chốt được: Tổ dùng luôn phần đã nhận, không chờ đủ số đề nghị.
        if (! in_array($item->status, ['issued', 'partial'], true)) {
            return $back('error', 'Mục này chưa được cấp phát, hoặc đã chốt sử dụng / trả về kho rồi!');
        }

        /*
        | Một dòng có thể đã được cấp từ nhiều lô nên có nhiều phiếu sử dụng. Xếp theo thứ
        | tự đã cấp (cũng là thứ tự nên xuất): phần THỰC DÙNG tính vào các lô đầu, phần trả
        | lại kho cắt ngược từ lô cuối - lô hạn gần nhất coi như đã dùng trước.
        */
        $exports = DB::table(self::TABLE)
            ->where('request_item_id', $item->id)
            ->where('type', 'export')
            ->where('status_id', 1)
            ->orderBy('id', 'asc')
            ->get();

        if ($exports->isEmpty()) {
            return $back('error', 'Không tìm thấy phiếu sử dụng của mục đề nghị này!');
        }

        $issued = (float) ($item->issued_amount ?: $exports->sum('amount'));
        $amount = (float) $request->amount;
        $isReturn = $request->input('action') === 'return';

        if ($amount > $issued + self::EPSILON) {
            return $back('error', 'Số lượng không được vượt quá số đã cấp phát ('.$this->number($issued).' '.$item->issued_unit.').');
        }

        // Quy cả hai hành động về "số thực tính là đã dùng"
        $usedAmount = $isReturn ? $issued - $amount : $amount;
        $returnedAmount = $issued - $usedAmount;
        $unit = $item->issued_unit ?: $item->requested_unit;

        if (! $isReturn && $usedAmount <= self::EPSILON) {
            return $back('error', 'Số lượng sử dụng phải lớn hơn 0. Không dùng gì thì chọn "Trả về kho".');
        }

        if ($isReturn && $amount <= self::EPSILON) {
            return $back('error', 'Số lượng trả về kho phải lớn hơn 0.');
        }

        $fullyReturned = $usedAmount <= self::EPSILON;

        DB::transaction(function () use ($item, $exports, $request, $usedAmount, $unit, $fullyReturned) {
            $left = $usedAmount;

            foreach ($exports as $export) {
                $issuedOfLot = (float) $export->amount;
                $usedOfLot = min($left, $issuedOfLot);
                $left = max($left - $usedOfLot, 0);

                $returnedOfLot = $issuedOfLot - $usedOfLot;
                $lotReturned = $usedOfLot <= self::EPSILON;

                $note = $lotReturned
                    ? 'Trả toàn bộ '.$this->number($issuedOfLot).' '.$unit.' của mã '.$export->code.' về kho'
                    : 'Ghi nhận sử dụng '.$this->number($usedOfLot).' '.$unit.' của mã '.$export->code
                        .($returnedOfLot > self::EPSILON ? ', trả lại kho '.$this->number($returnedOfLot).' '.$unit : '');

                DB::table(self::TABLE)->where('id', $export->id)->update([
                    // Phiếu bị trả hết thì huỷ (status_id = 0) và giữ nguyên số để còn tra cứu
                    'amount' => $lotReturned ? $issuedOfLot : $usedOfLot,
                    'product_name' => $this->nullIfBlank($request->product_name ?: $export->product_name),
                    'test_report_no' => $this->nullIfBlank($request->test_report_no ?: $export->test_report_no),
                    'reason' => $this->nullIfBlank($request->reason ?: $export->reason),
                    'status_id' => $lotReturned ? 0 : 1,
                    'used_by' => $this->actor(),
                    'updated_by' => $this->actor(),
                    'updated_at' => now(),
                ]);

                $this->logHistory($export->id, $lotReturned ? 'Trả về kho' : 'Ghi nhận sử dụng', $note.($request->reason ? ' | Lý do: '.$request->reason : ''));
            }

            DB::table(self::REQ_ITEM)->where('id', $item->id)->update([
                'status' => $fullyReturned ? 'returned' : 'used',
                'product_name' => $this->nullIfBlank($request->product_name ?: $item->product_name),
                'updated_at' => now(),
            ]);
        });

        AuditTrialController::log(
            $fullyReturned ? 'Trả vật tư về kho' : 'Ghi nhận sử dụng vật tư',
            self::REQ_ITEM,
            $item->id,
            $item->status,
            ($fullyReturned ? 'returned' : 'used').', dùng '.$this->number($usedAmount).' / cấp '.$this->number($issued)
        );

        return $back(
            'success',
            $fullyReturned
                ? 'Đã trả '.$this->number($returnedAmount).' '.$unit.' về kho cho đề nghị '.$req->code.'!'
                : 'Đã ghi nhận sử dụng '.$this->number($usedAmount).' '.$unit
                    .($returnedAmount > self::EPSILON ? ' (trả lại kho '.$this->number($returnedAmount).' '.$unit.')' : '').'!'
        );
    }

    /* ==========================================================
     |  PHIẾU LOẠI BỎ (trừ tồn) - hàng hỏng / hết hạn, không qua đề nghị
     ========================================================== */

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        // Chỉ còn loại bỏ hàng hỏng / hết hạn: phiếu sử dụng nay do kho sinh lúc cấp phát.
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'in:cancel'],
            'import_id' => ['nullable', 'exists:material_imports,id'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['nullable', 'max:500'],
            'adjust_reason' => ['nullable', 'max:500'],
        ], $this->messages());

        $type = 'cancel';
        $import = null;
        $item = null;
        $groupId = null;

        // Chạy trong after() để lỗi tự thêm không bị passes() xoá khi gọi fails()
        $validator->after(function ($v) use ($request, $departmentId, &$import, &$item, &$groupId) {
            [$import, $item, $groupId] = $this->resolveUseTarget($v, $request, $departmentId);
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
            self::TYPES[$type].' vật tư, mã xuất nhập: '.$import->code.', số lượng: '.$request->amount
        );

        return redirect()->back()->with('success', 'Đã ghi nhận '.self::LABEL.' cho mã xuất nhập '.$import->code.'!');
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
            'purpose' => ['nullable', 'max:500'],
            'reason' => ['nullable', 'max:500'],
            'adjust_reason' => ['nullable', 'max:500'],
        ], $this->messages());

        $import = DB::table('material_imports')->where('id', $current->import_id)->first();

        $validator->after(function ($validator) use ($request, $import, $current) {
            if ($import && is_numeric($request->amount)) {
                $limit = $this->remaining($import, (int) $current->id) * (1 + self::OVER_ISSUE_RATIO);
                if ((float) $request->amount > $limit + self::EPSILON) {
                    $validator->errors()->add('amount', 'Mã xuất nhập '.$import->code.' chỉ còn cho phép xuất tối đa '.$this->number($limit).'.');
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
            'test_report_no' => $current->test_report_no,
            'reason' => $this->nullIfBlank($request->reason),
        ];

        /*
        | Mục đích nằm ở DÒNG ĐỀ NGHỊ chứ không ở phiếu sử dụng. Một dòng đề nghị có thể
        | được cấp từ nhiều mã xuất nhập nên sửa ở đây là sửa chung cho mọi phiếu sinh ra
        | từ dòng đó. Phiếu loại bỏ không có đề nghị nên không có mục đích để sửa.
        */
        $item = $current->request_item_id
            ? DB::table(self::REQ_ITEM)->where('id', $current->request_item_id)->first()
            : null;

        $purpose = $this->nullIfBlank($request->purpose);
        $extra = [];

        if ($item && (string) $item->purpose !== (string) $purpose) {
            $extra[] = 'Mục đích: '.($item->purpose ?: '—').' -> '.($purpose ?: '—');
        }

        $note = $this->changeNote($current, $payload, $request->adjust_reason, $extra);

        if ($note === '') {
            return redirect()->back()->with('error', 'Không có thông tin nào thay đổi nên chưa cập nhật '.self::LABEL.'.');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        if ($item && $extra) {
            DB::table(self::REQ_ITEM)->where('id', $item->id)->update([
                'purpose' => $purpose,
                'updated_at' => now(),
            ]);
        }

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
                        'Mã xuất nhập' => $row->code ?: '—',
                        'Vật tư' => $row->material_name ?: '—',
                        'Số lượng' => $row->amount !== null ? $this->number((float) $row->amount).' '.$unit : '—',
                        'Loại phiếu' => self::TYPES[$row->type] ?? ($row->type ?: '—'),
                        'Thiết bị liên quan' => $row->product_name ?: '—',
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

    /** Tra mã xuất nhập khi quét mã QR trên nhãn, trả JSON cho form. */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->query('code'));
        $import = $this->importOptions($this->departmentId())->firstWhere('code', $code);

        if (! $import) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy mã xuất nhập "'.$code.'" trong kho phòng ban.']);
        }

        return response()->json([
            'ok' => $import->selectable,
            'id' => $import->id,
            'code' => $import->code,
            'material_name' => $import->material_name,
            'remaining' => $import->remaining,
            'unit' => $import->unit_short_name,
            'expired_date' => $import->expired_date,
            'message' => $import->selectable ? '' : ($import->expired ? 'Mã xuất nhập đã hết hạn.' : 'Mã xuất nhập đã hết tồn.'),
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

    /**
     * Xác định mã xuất nhập cho một phiếu LOẠI BỎ và chặn xuất vượt tồn.
     * Phiếu sử dụng không đi qua đây: kho sinh sẵn lúc cấp phát (issueStore), Tổ chỉ chốt
     * lại bằng useStore().
     */
    private function resolveUseTarget($validator, Request $request, int $departmentId): array
    {
        $groupId = $request->filled('group_id') ? (int) $request->group_id : null;

        if (! $request->filled('import_id')) {
            $validator->errors()->add('import_id', 'Vui lòng chọn mã xuất nhập cần loại bỏ.');

            return [null, null, null];
        }

        $import = DB::table('material_imports')->where('id', $request->import_id)->where('department_id', $departmentId)->where('status_id', 1)->first();

        if (! trim((string) $request->reason)) {
            $validator->errors()->add('reason', 'Vui lòng nhập lý do loại bỏ.');
        }

        if (! $import) {
            $validator->errors()->add('import_id', 'Không tìm thấy mã xuất nhập trong kho phòng ban này.');

            return [null, null, null];
        }

        if (is_numeric($request->amount)) {
            $limit = $this->remaining($import) * (1 + self::OVER_ISSUE_RATIO);
            if ((float) $request->amount > $limit + self::EPSILON) {
                $validator->errors()->add(
                    'amount',
                    'Mã xuất nhập '.$import->code.' còn '.$this->number($this->remaining($import)).'. Được xuất vượt tối đa '
                    .(int) round(self::OVER_ISSUE_RATIO * 100).'%, tức không quá '.$this->number($limit).'.'
                );
            }
        }

        return [$import, null, $groupId];
    }

    /** Cập nhật issue_status của đề nghị theo trạng thái các dòng. */
    private function refreshIssueStatus(int $listId): void
    {
        $items = DB::table(self::REQ_ITEM)->where('request_list_id', $listId)->where('active', 1)->get();

        // Dòng mới cấp một phần vẫn còn nợ hàng nên phiếu chưa thể coi là cấp xong.
        $open = $items->whereIn('status', ['pending', 'partial'])->count();
        $issued = $items->whereIn('status', ['partial', 'issued', 'used', 'returned'])->count();

        $status = $open === 0 ? 'completed' : ($issued > 0 ? 'partial' : 'waiting');

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

    /** $extra: các thay đổi không nằm trên bảng phiếu sử dụng, ví dụ mục đích của dòng đề nghị. */
    private function changeNote($current, array $payload, ?string $reason = null, array $extra = []): string
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

        $parts = array_merge($parts, $extra);

        if (! $parts) {
            return '';
        }

        $reason = trim((string) $reason);

        return ($reason !== '' ? 'Lý do: '.$reason.' | ' : '').implode(' | ', $parts);
    }

    /**
     * Các lô ĐÃ CẤP của từng dòng đề nghị: request_item_id => danh sách phiếu sử dụng.
     *
     * Một dòng cấp từ nhiều mã xuất nhập thì có bấy nhiêu phiếu sử dụng cùng trỏ về nó.
     * Lấy cả phiếu đã huỷ (status_id = 0) để phiếu chi tiết còn nói được "lô này đã trả
     * về kho", nhưng KHÔNG lấy phiếu loại bỏ hàng hỏng (type = cancel).
     */
    private function issuedLots($itemIds)
    {
        $itemIds = collect($itemIds)->filter()->values();

        if ($itemIds->isEmpty()) {
            return collect();
        }

        return DB::table(self::TABLE)
            ->leftJoin('material_imports', self::TABLE.'.import_id', '=', 'material_imports.id')
            ->select(
                self::TABLE.'.id',
                self::TABLE.'.request_item_id',
                self::TABLE.'.import_id',
                self::TABLE.'.code',
                self::TABLE.'.amount',
                self::TABLE.'.status_id',
                self::TABLE.'.created_at',
                'material_imports.expired_date',
                'material_imports.imported_date'
            )
            ->whereIn(self::TABLE.'.request_item_id', $itemIds)
            ->where(self::TABLE.'.type', 'export')
            ->orderBy(self::TABLE.'.id', 'asc')
            ->get()
            ->groupBy('request_item_id');
    }

    /**
     * Kế hoạch chia lô gợi ý cho từng dòng CÒN CHỜ CẤP của các đề nghị đã duyệt.
     *
     * Đề nghị 20 cái mà lô hạn gần nhất chỉ còn 12 thì kế hoạch là 12 của lô đó + 8 của
     * lô kế tiếp, đúng thứ tự nên xuất. Dùng lại bộ lô đã nạp ở index() nên không phát
     * sinh thêm truy vấn cho mỗi dòng.
     *
     * @return \Illuminate\Support\Collection request_item_id => ['lines' => [...], 'shortage' => float]
     */
    private function issuePlans($requestLists, $requestItems, $lotsByCategory)
    {
        $plans = collect();

        foreach ($requestLists->where('app_status', 'approved') as $req) {
            foreach ($requestItems->get($req->id, collect()) as $item) {
                if (! in_array($item->status, ['pending', 'partial', 'issued'], true) || ! $item->category_id) {
                    continue;
                }

                // Dòng đã cấp một phần chỉ cần chia lô cho phần CÒN THIẾU
                $left = round((float) $item->requested_amount - (float) $item->issued_amount, 4);

                if ($left <= self::EPSILON) {
                    continue;
                }

                $plans[$item->id] = MaterialPicking::planFrom(
                    $lotsByCategory->get($item->category_id, collect()),
                    $left
                );
            }
        }

        return $plans;
    }

    /**
     * Mã xuất nhập của phòng ban đang chọn, còn hiệu lực, kèm tồn còn lại.
     *
     * Thứ tự do App\Support\MaterialPicking quyết định: lô NÊN XUẤT TRƯỚC đứng đầu danh
     * sách (hạn gần nhất trước; vật tư không hạn dùng tự sắp theo ngày nhập). Trước đây
     * ô chọn sắp theo ngày nhập giảm dần - lô MỚI NHẤT nằm trên cùng, ngược nguyên tắc
     * xuất kho.
     *
     * `remaining` vẫn là tồn sổ sách để hiện cho người dùng; `available` mới là phần còn
     * hứa được, đã trừ hàng đang giữ cho các đợt lấy hàng còn treo.
     */
    private function importOptions(int $departmentId)
    {
        return MaterialPicking::lots($departmentId)->map(function ($import) {
            // Giữ tên cột cũ cho các view đang dùng
            $import->used = $import->exported;
            $import->max_amount = $import->available * (1 + self::OVER_ISSUE_RATIO);
            $import->selectable = $import->suggestable;

            return $import;
        });
    }

    /**
     * Tồn CÒN HỨA ĐƯỢC của một lô = tồn sổ sách - phần đang giữ cho các đợt lấy hàng.
     *
     * Dùng khi CẤP PHÁT (hứa hàng cho một Tổ). Việc LOẠI BỎ hàng hỏng vẫn đi theo
     * remaining() - phát hiện lô hỏng thì phải ghi nhận được ngay, kể cả khi lô đó đã
     * hứa cho một đợt; bước xuất đợt sẽ kiểm lại tồn trước khi trừ.
     */
    private function available($import, ?int $ignoreExportId = null): float
    {
        return max($this->remaining($import, $ignoreExportId) - MaterialPicking::heldOf((int) $import->id), 0);
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
        return \App\Support\Signer::actor();
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
            'name' => ['nullable', 'string', 'max:255'],
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
