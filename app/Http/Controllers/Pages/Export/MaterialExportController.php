<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Controller;
use App\Http\Controllers\General\NotificationController;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\CategoryUnitConversion;
use App\Support\CompanyContext;
use App\Support\DepartmentMaterial;
use App\Support\ListRange;
use App\Support\MaterialCode;
use App\Support\MaterialPeriodicRequest;
use App\Support\MaterialPicking;
use App\Support\MaterialSignFlow;
use App\Support\MaterialWatchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * SỬ DỤNG - SỬ DỤNG VẬT TƯ
 *
 * Khác chất chuẩn: vật tư BẮT BUỘC phải qua ĐỀ NGHỊ được phê duyệt trước khi lấy ra dùng.
 *
 *   1. Tổ lập ĐỀ NGHỊ (material_request_lists + items) -> Trình ký. QUY TRÌNH KÝ DUYỆT
 *      KHÔNG do người lập khai nữa mà suy thẳng từ dữ liệu gốc "Trình Ký Đề Nghị CP Vật
 *      Tư": mỗi dòng vật tư khớp một quy trình theo phân loại của danh mục chung + phân
 *      loại của phòng, phiếu lấy quy trình CHẶT NHẤT (nhiều bước nhất) trong các dòng rồi
 *      chép các bước sang material_request_signs. Dòng nào chưa khớp quy trình nào thì
 *      chặn không cho trình ký - xem App\Support\MaterialSignFlow.
 *   2. Ký lần lượt theo step_no, mỗi bước đúng người được chỉ định ký (nhập lại mật khẩu -
 *      21 CFR Part 11). Ký hết bước cuối -> approved, issue_status = waiting. Mỗi lần
 *      chuyển bước đều gửi thông báo cho người phải ký tiếp; từ chối thì báo người lập.
 *   3. Kho CẤP PHÁT từng dòng: chỉ định mã xuất nhập, số lượng. Cấp phát là XUẤT KHO,
 *      tương đương vật tư đã đem sử dụng - TRỪ TỒN TRỰC TIẾP, sinh luôn một bản ghi
 *      material_exports (type = export) gắn với dòng đề nghị. Cấp đủ số đề nghị thì
 *      material_request_items.status = issued; kho thiếu hàng thì cấp được bao nhiêu hay
 *      bấy nhiêu (status = partial) và cấp thêm cho tới khi đủ. Không có bước chốt lại.
 *
 *   LOẠI BỎ (type = cancel) hàng hỏng / hết hạn không phải "sử dụng" nên lập thẳng trên
 *   material_exports, không cần đề nghị; bắt buộc nhập lý do và không được vượt tồn quá 5%.
 *
 *   CẤP PHÁT LIÊN PHÒNG BAN (type = transfer_out) là hàng chuyển sang phòng khác chứ không
 *   phải hàng đã dùng - xem khối "ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN" bên dưới.
 *
 * Trạng thái tồn dùng công thức: nhập + cân đối - đã xuất (kể cả loại bỏ và chuyển đi). Vì
 * cấp phát đã sinh sẵn phiếu sử dụng, không có chỗ nào lập phiếu sử dụng thủ công nữa -
 * tránh trừ hai lần.
 */
class MaterialExportController extends Controller
{
    use VerifiesSignature;

    private const TABLE = 'material_exports';

    private const HISTORY_TABLE = 'material_export_histories';

    private const REQ_LIST = 'material_request_lists';

    private const REQ_ITEM = 'material_request_items';

    /** Các bước ký của một đề nghị - mỗi bước một dòng, thứ tự theo step_no. */
    private const REQ_SIGN = 'material_request_signs';

    /** Người được ký một bước - một bước giao cho nhiều người thì mỗi người một dòng. */
    private const REQ_CANDIDATE = 'material_request_sign_candidates';

    private const TRANSFER_REQUEST_TABLE = 'material_transfer_requests';

    private const TRANSFER_ITEM_TABLE = 'material_transfer_items';

    /**
     * Trạng thái đề nghị chuyển liên phòng ban còn DỞ DANG - bộ lọc khoảng ngày luôn
     * giữ lại các đề nghị này dù đã ngoài khoảng lọc.
     */
    private const TRANSFER_PENDING_STATUSES = ['draft', 'pending', 'partial'];

    /** Đề nghị cấp phát còn DỞ DANG: chưa ký duyệt xong. */
    private const REQ_PENDING_APP_STATUSES = ['draft', 'pending_sign'];

    /** Đề nghị cấp phát đã duyệt nhưng kho chưa cấp đủ. */
    private const REQ_PENDING_ISSUE_STATUSES = ['waiting', 'partial'];

    /**
     * 3 loại đề nghị cấp phát, mỗi loại một tab riêng: material_request_lists.type =>
     * ['tab' => tên tab dùng cho activeTab/route, 'param' => tiền tố tham số lọc riêng].
     *   periodic        : hệ thống tự sinh từ danh sách vật tư đề nghị theo chu kỳ của phòng
     *   risk_assessment : theo Đánh Giá Rủi Ro (chưa có nghiệp vụ tạo tự động)
     *   regular         : đề nghị tự lập như trước giờ - tab duy nhất có nút "Tạo đề nghị"
     */
    private const REQ_TYPE_TABS = [
        'periodic' => 'reqp_',
        'risk_assessment' => 'reqk_',
        'regular' => 'reqt_',
    ];

    private const LABEL = 'phiếu sử dụng vật tư';

    private const EPSILON = 0.00005;

    private const OVER_ISSUE_RATIO = 0.05;

    public const TYPES = ['export' => 'Sử dụng', 'cancel' => 'Loại bỏ'];

    /**
     * Loại phiếu CẤP PHÁT LIÊN PHÒNG BAN - không nằm trong TYPES vì không chọn được ở form
     * Sử Dụng chung, chỉ sinh ra qua transferIssueStore(). Trừ tồn phòng gửi như mọi phiếu
     * xuất khác, nhưng hàng không mất đi mà thành tồn của phòng nhận.
     */
    private const TYPE_TRANSFER_OUT = 'transfer_out';

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

        // Danh sách đề nghị theo chu kỳ đã tới hạn thì tạo đề nghị Lưu tạm ngay, để phiếu
        // hiện ra trong tab Đề nghị dù máy chủ không chạy scheduler.
        MaterialPeriodicRequest::generateDue($departmentId);

        // Sổ sử dụng chỉ lấy đúng một trang trong khoảng ngày đang lọc (mặc định 30 ngày
        // gần nhất), không nạp toàn bộ phiếu sử dụng của phòng như trước.
        $bookRange = ListRange::of($request, 'book_');
        $bookKeyword = ListRange::keyword($request, 'book_');
        $bookPerPage = ListRange::perPage($request, 'book_');

        $exports = DB::table(self::TABLE)
            ->leftJoin('material_imports', self::TABLE.'.import_id', '=', 'material_imports.id')
            ->leftJoin('material_categories', 'material_imports.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin(self::REQ_ITEM, self::TABLE.'.request_item_id', '=', self::REQ_ITEM.'.id')
            ->leftJoin(self::REQ_LIST, self::REQ_ITEM.'.request_list_id', '=', self::REQ_LIST.'.id')
            ->leftJoin(self::TRANSFER_ITEM_TABLE, self::TABLE.'.transfer_item_id', '=', self::TRANSFER_ITEM_TABLE.'.id')
            ->leftJoin(self::TRANSFER_REQUEST_TABLE, self::TRANSFER_ITEM_TABLE.'.transfer_request_id', '=', self::TRANSFER_REQUEST_TABLE.'.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_imports.category_id'))
            // Phòng ban nhận, chỉ có ở phiếu cấp phát liên phòng ban (type = transfer_out)
            ->leftJoin('deparments', self::TABLE.'.to_department_id', '=', 'deparments.id')
            ->select(
                self::TABLE.'.*',
                'material_names.name as material_name',
                'material_categories.code as category_code',
                'material_categories.technical_specification',
                'units.short_name as unit_short_name',
                'deparments.name as to_department_name',
                'deparments.shortName as to_department_short',
                self::REQ_ITEM.'.purpose',
                DB::raw('COALESCE('.self::REQ_LIST.'.code, '.self::TRANSFER_REQUEST_TABLE.'.code) as request_code')
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->tap(ListRange::dateFilter(self::TABLE.'.created_at', $bookRange))
            ->tap(ListRange::search([
                self::TABLE.'.code',
                self::REQ_LIST.'.code',
                self::TRANSFER_REQUEST_TABLE.'.code',
                'material_imports.code',
                'material_categories.code',
                'material_names.name',
                'material_categories.technical_specification',
                self::TABLE.'.used_by',
                self::REQ_ITEM.'.purpose',
            ], $bookKeyword))
            ->orderBy(self::TABLE.'.created_at', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->paginate($bookPerPage, ['*'], ListRange::pageName('book_'))
            ->withQueryString();

        // Đề nghị cấp phát: chia 3 loại (periodic / risk_assessment / regular), mỗi loại một
        // tab với bộ lọc + phân trang RIÊNG (tiền tố xem self::REQ_TYPE_TABS), nhưng vẫn lọc
        // theo ngày lập và LUÔN giữ các đề nghị còn dở dang dù đã ngoài khoảng lọc.
        $reqTabs = [];
        foreach (self::REQ_TYPE_TABS as $reqType => $reqPrefix) {
            $reqTabs[$reqType] = $this->requestListsOfType($departmentId, $request, $reqType, $reqPrefix);
        }

        // Gộp lại để tính chung: bước ký, dòng đề nghị, kế hoạch chia lô - vẫn chỉ những
        // đề nghị đang HIỂN THỊ trên 3 trang hiện tại, không nạp toàn bộ dữ liệu của phòng.
        $requestLists = collect($reqTabs)->reduce(
            fn ($carry, $tab) => $carry->concat($tab['list']->items()),
            collect()
        );

        // Bước ký của các đề nghị đang hiện: request_list_id => danh sách bước theo step_no
        $requestSigns = $this->signRows($requestLists->pluck('id'));

        foreach ($reqTabs as $reqType => $reqTab) {
            $reqTab['list']->through(function ($req) use ($requestSigns) {
                $pending = $this->pendingSign($req, $requestSigns->get($req->id, collect()));

                $req->pending_sign = $pending;
                $req->can_sign = $pending ? $this->canSignRow($pending) : false;

                return $req;
            });
        }

        $requestItems = DB::table(self::REQ_ITEM)
            ->leftJoin('material_categories', self::REQ_ITEM.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('material_imports', self::REQ_ITEM.'.import_id', '=', 'material_imports.id')
            ->select(
                self::REQ_ITEM.'.*',
                'material_names.name as category_material_name',
                'material_categories.code as category_code',
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
            'total_remaining' => (float) $group->where('suggestable', true)->sum('remaining'),
            'total_lots' => (int) $group->where('suggestable', true)->count(),
        ]);

        // Tồn khả dụng (thời gian thực) của từng danh mục = tồn - phần các đề nghị KHÁC đang
        // chờ ký / đã duyệt nhưng kho chưa cấp đủ đang giữ chỗ - xem cột "Tồn khả dụng".
        $liveStockByCategory = $this->categoryStockMap($departmentId, $availableImports);

        $departmentMaterialInventory = $categories->map(function ($cat) use ($stockByCategory, $liveStockByCategory) {
            $cat->total_remaining = $stockByCategory[$cat->id]['total_remaining'] ?? 0.0;
            $cat->total_lots = $stockByCategory[$cat->id]['total_lots'] ?? 0;
            $cat->available_stock = $liveStockByCategory[$cat->id]['available'] ?? $cat->total_remaining;

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

        /*
        | Tab "Soạn vật tư cấp phát": gom phần CÒN PHẢI CẤP của mọi đề nghị đã duyệt theo
        | từng vật tư (không theo phiếu) để kho soạn hàng một lượt trước khi bấm cấp phát.
        | Đọc thẳng từ DB chứ không dựa vào 3 trang đề nghị đang hiện - phiếu nằm ở trang
        | sau vẫn phải được soạn.
        */
        $prep = $this->preparationData($departmentId, $lotsByCategory);

        session()->put(['title' => 'SỬ DỤNG - SỬ DỤNG VẬT TƯ']);

        // Đề nghị chuyển vật tư LIÊN PHÒNG BAN: đã gửi đi (mình là A) / cần cấp phát (mình là B)
        $transfer = $this->transferRequestsData($departmentId, $request);

        // Vật tư phòng mình đã khai ở tab "Vật Tư Của Phòng" - dùng để cảnh báo ngay trên
        // phiếu khi có mục đang "chờ nhận" mà phòng mình chưa khai (chưa có đơn vị tính),
        // thay vì để bấm Nhận xong mới báo lỗi.
        $declaredCategoryIds = DB::table(DepartmentMaterial::TABLE)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->pluck('category_id')
            ->all();

        // Hộp ký duyệt liên phòng ban: gom các đề nghị của mọi phòng đang chờ CHÍNH
        // người đang đăng nhập ký (chỉ bật cho phòng ban chung + có quyền is_BOD).
        $inbox = $this->approvalInboxData();

        /*
        | Tab nào đang mở: ?tab= trên URL là chính; các action liên phòng ban dùng
        | redirect()->back() (không đổi URL) nên tự flash activeTab qua session. 3 tab đề
        | nghị cấp phát dùng đúng tên type (periodic / risk_assessment / regular).
        */
        $tabs = array_merge(['book', 'prepare', 'transfer', 'inbox'], array_keys(self::REQ_TYPE_TABS));
        // Link trong thông báo cũ còn trỏ tab 'request' (trước khi tách 3 tab)
        $queryTab = $request->query('tab') === 'request' ? 'regular' : $request->query('tab');
        $activeTab = in_array($queryTab, $tabs, true)
            ? $queryTab
            : (in_array(session('activeTab'), $tabs, true) ? session('activeTab') : 'book');

        return view('pages.export.MaterialExport.list', [
            'exports' => $exports,
            'requestLists' => $requestLists,
            'reqTabs' => $reqTabs,
            'requestItems' => $requestItems,
            'categories' => $categories,
            'units' => $this->unitOptions(),
            'availableImports' => $availableImports,
            'lotsByCategory' => $lotsByCategory,
            'issuedLots' => $issuedLots,
            'issuePlans' => $issuePlans,
            // ---- Tab "Soạn vật tư cấp phát" ----
            'prepGroups' => $prep['groups'],
            'prepBadgeCount' => $prep['count'],
            'prepShortageCount' => $prep['shortageCount'],
            'departmentMaterialInventory' => $departmentMaterialInventory,
            'adjustCounts' => $this->adjustCounts($departmentId),
            'reqAppStatuses' => config('material.request_app_statuses'),
            'reqSignStatuses' => config('material.request_sign_statuses'),
            'reqIssueStatuses' => config('material.request_issue_statuses'),
            'reqItemStatuses' => config('material.request_item_statuses'),
            'requestSigns' => $requestSigns,
            // Quy trình ký của từng vật tư - JS dựng lại khối "Quy trình ký duyệt" theo dòng đang chọn
            'signFlowMap' => $this->signFlowMapForView($departmentId),
            'overIssuePercent' => (int) round(self::OVER_ISSUE_RATIO * 100),
            // ---- Tab "Đề nghị chuyển liên phòng ban" ----
            'transferSent' => $transfer['sent'],
            'transferReceived' => $transfer['received'],
            'transferItems' => $transfer['items'],
            'transferBadgeCount' => $transfer['badgeCount'],
            'transferSentRange' => $transfer['sentRange'],
            'transferSentPerPage' => $transfer['sentPerPage'],
            'transferReceivedRange' => $transfer['receivedRange'],
            'transferReceivedPerPage' => $transfer['receivedPerPage'],
            'bookRange' => $bookRange,
            'bookKeyword' => $bookKeyword,
            'bookPerPage' => $bookPerPage,
            'transferCategories' => $this->transferCategoryOptions($departmentId),
            'transferDepartments' => $this->departmentOptions($departmentId),
            'transferOwnLocations' => DepartmentMaterial::locationOptions($departmentId),
            'declaredCategoryIds' => $declaredCategoryIds,
            'currentDepartmentId' => $departmentId,
            'activeTab' => $activeTab,
            // ---- Tab "Ký duyệt (mọi phòng ban)" - hộp ký duyệt liên phòng ban ----
            'showApprovalInbox' => $inbox['show'],
            'inboxRequests' => $inbox['requests'],
            'inboxItems' => $inbox['items'],
            'inboxSigns' => $inbox['signs'],
            'inboxBadgeCount' => $inbox['badge'],
        ]);
    }

    /* ==========================================================
     |  ĐỀ NGHỊ CẤP PHÁT
     ========================================================== */

    public function requestStore(Request $request)
    {
        $departmentId = $this->departmentId();

        // Lập tay chỉ có Thường Quy / Theo ĐG Rủi Ro; 'periodic' chỉ do MaterialPeriodicRequest tự sinh
        $type = $request->input('type') === 'risk_assessment' ? 'risk_assessment' : 'regular';

        $validator = Validator::make($request->all(), $this->requestRules(), $this->requestMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'requestCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', $type);
        }

        $isDraft = $request->input('action_type', 'send') === 'draft';

        /*
        | Quy trình ký lấy thẳng từ dữ liệu gốc "Trình Ký Đề Nghị CP Vật Tư" theo phân loại
        | của các dòng vật tư. Lưu tạm thì cho qua (phiếu nháp chưa cần ký), nhưng TRÌNH KÝ
        | mà còn dòng chưa khớp quy trình nào là chặn - phải khai dữ liệu gốc trước.
        */
        $signFlow = $this->requestSignFlow($request, $departmentId);

        if (! $isDraft && $signFlow['missing']) {
            return redirect()->back()
                ->with('error', $this->signFlowMissingMessage())
                ->withInput()
                ->with('activeTab', $type);
        }

        $deptStr = str_pad((string) $departmentId, 2, '0', STR_PAD_LEFT);
        $prefix = $deptStr.date('dmy').'_';

        $latest = DB::table(self::REQ_LIST)->where('code', 'LIKE', $prefix.'%')->orderBy('id', 'desc')->value('code');
        $seq = 1;
        if ($latest) {
            $parts = explode('_', $latest);
            $seq = (int) end($parts) + 1;
        }
        $code = $prefix.str_pad((string) $seq, 2, '0', STR_PAD_LEFT);

        $signSteps = $signFlow['steps'];
        $stepCount = $signSteps->count();
        $flow = $isDraft
            ? ['app_status' => 'draft', 'current_step' => null, 'issue_status' => null]
            : $this->submitPayload($stepCount);

        $listId = DB::transaction(function () use ($request, $departmentId, $code, $isDraft, $flow, $stepCount, $type, $signSteps) {
            $listId = DB::table(self::REQ_LIST)->insertGetId($flow + [
                'code' => $code,
                'department_id' => $departmentId,
                'type' => $type,
                'name' => $this->nullIfBlank($request->name),
                'note' => $this->nullIfBlank($request->note),
                'needed_date' => $this->nullIfBlank($request->needed_date),
                'sign_step_count' => $stepCount,
                'submitted_by' => $isDraft ? null : $this->actor(),
                'submitted_at' => $isDraft ? null : now(),
                'created_by' => $this->actor(),
                'created_user_id' => (int) (session('user')['userId'] ?? 0) ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->insertRequestItems($listId, $request);
            $this->insertRequestSigns($listId, $signSteps);

            return $listId;
        });

        AuditTrialController::log(
            $isDraft ? 'Lưu tạm đề nghị cấp phát vật tư' : 'Trình ký đề nghị cấp phát vật tư',
            self::REQ_LIST,
            $listId,
            'NA',
            'Đề nghị '.$code.' ('.count($request->items).' mục, '.$stepCount.' bước ký)'
        );

        if (! $isDraft) {
            $this->notifySubmitted($this->findRequest($listId), $stepCount);
        }

        return redirect()->route('pages.export.materialExport.list', ['tab' => $type])->with(
            'success',
            $isDraft ? 'Đã lưu tạm đề nghị '.$code.'!' : $this->submitMessage($code, $stepCount)
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
            return redirect()->back()->with('error', 'Chỉ sửa được đề nghị đang ở trạng thái Nháp hoặc Bị từ chối!')->with('activeTab', $req->type ?? 'regular');
        }

        $validator = Validator::make($request->all(), $this->requestRules() + [
            'request_list_id' => ['required', 'exists:'.self::REQ_LIST.',id'],
        ], $this->requestMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'requestCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', $req->type);
        }

        $isDraft = $request->input('action_type', 'draft') === 'draft';

        // Sửa dòng vật tư có thể đổi luôn quy trình ký, nên tính lại từ dữ liệu gốc
        $signFlow = $this->requestSignFlow($request, $departmentId);

        if (! $isDraft && $signFlow['missing']) {
            return redirect()->back()
                ->with('error', $this->signFlowMissingMessage())
                ->withInput()
                ->with('activeTab', $req->type);
        }

        $signSteps = $signFlow['steps'];
        $stepCount = $signSteps->count();
        $flow = $isDraft
            ? ['app_status' => 'draft', 'current_step' => null, 'issue_status' => null]
            : $this->submitPayload($stepCount);

        DB::transaction(function () use ($request, $req, $isDraft, $flow, $stepCount, $signSteps) {
            DB::table(self::REQ_LIST)->where('id', $req->id)->update($flow + [
                'name' => $this->nullIfBlank($request->name),
                'note' => $this->nullIfBlank($request->note),
                'needed_date' => $this->nullIfBlank($request->needed_date),
                'sign_step_count' => $stepCount,
                'submitted_by' => $isDraft ? $req->submitted_by : $this->actor(),
                'submitted_at' => $isDraft ? $req->submitted_at : now(),
                'rejected_by' => null, 'rejected_at' => null, 'reject_step' => null, 'reject_reason' => null,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Không xoá cứng: bỏ hiệu lực các mục cũ (active = 0), dữ liệu vẫn lưu lại.
            DB::table(self::REQ_ITEM)->where('request_list_id', $req->id)->update([
                'active' => 0,
                'updated_at' => now(),
            ]);
            $this->insertRequestItems($req->id, $request);

            // Quy trình ký dựng lại từ đầu: các bước cũ (kể cả bước đã bị từ chối) hết hiệu lực
            $this->deactivateSigns($req->id);
            $this->insertRequestSigns($req->id, $signSteps);
        });

        AuditTrialController::log(
            'Cập nhật đề nghị cấp phát vật tư',
            self::REQ_LIST,
            $req->id,
            $req->code,
            ($isDraft ? 'Lưu tạm' : 'Trình ký lại').', '.$stepCount.' bước ký'
        );

        if (! $isDraft) {
            $this->notifySubmitted($this->findRequest($req->id), $stepCount);
        }

        return redirect()->route('pages.export.materialExport.list', ['tab' => $req->type])->with(
            'success',
            $isDraft ? 'Đã lưu đề nghị '.$req->code.'!' : $this->submitMessage($req->code, $stepCount, true)
        );
    }

    public function requestSubmit(Request $request)
    {
        $req = $this->findRequest($request->request_list_id);

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cần trình ký!')->with('activeTab', 'regular');
        }

        if (! in_array($req->app_status, ['draft', 'rejected'])) {
            return redirect()->back()->with('error', 'Đề nghị '.$req->code.' không ở trạng thái sửa được nên không trình ký lại!')->with('activeTab', $req->type);
        }

        if (! DB::table(self::REQ_ITEM)->where('request_list_id', $req->id)->where('active', 1)->exists()) {
            return redirect()->back()->with('error', 'Đề nghị '.$req->code.' chưa có mục nào, chưa trình ký được!')->with('activeTab', $req->type);
        }

        /*
        | Dựng lại quy trình ký từ dữ liệu gốc ngay lúc trình ký, không dùng lại các bước đã
        | ghi lúc lưu tạm: giữa hai lần đó phòng có thể đã sửa quy trình hoặc đổi người ký.
        */
        $signFlow = $this->requestSignFlowOf($req);

        if ($signFlow['missing']) {
            return redirect()->back()
                ->with('error', $this->signFlowMissingMessage())
                ->with('activeTab', $req->type);
        }

        $signSteps = $signFlow['steps'];
        $stepCount = $signSteps->count();
        $flow = $this->submitPayload($stepCount);

        DB::transaction(function () use ($req, $flow, $stepCount, $signSteps) {
            DB::table(self::REQ_LIST)->where('id', $req->id)->update($flow + [
                'sign_step_count' => $stepCount,
                'submitted_by' => $this->actor(),
                'submitted_at' => now(),
                'rejected_by' => null, 'rejected_at' => null, 'reject_step' => null, 'reject_reason' => null,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->deactivateSigns($req->id);
            $this->insertRequestSigns($req->id, $signSteps);
        });

        AuditTrialController::log(
            'Trình ký đề nghị cấp phát vật tư',
            self::REQ_LIST,
            $req->id,
            $req->code,
            'app_status: '.$flow['app_status'].', '.$stepCount.' bước ký'
        );

        $this->notifySubmitted($this->findRequest($req->id), $stepCount);

        return redirect()->back()
            ->with('success', $this->submitMessage($req->code, $stepCount))
            ->with('activeTab', $req->type);
    }

    /**
     * KÝ MỘT BƯỚC của đề nghị.
     *
     * Chỉ đúng người được chỉ định ở bước đang chờ mới ký được, và phải nhập lại mật khẩu
     * (21 CFR Part 11 §11.200). Ký xong bước cuối thì phiếu được duyệt, kho cấp phát được.
     */
    public function requestSign(Request $request)
    {
        $req = $this->resolveRequestForApproval($request);
        $tab = $this->approvalActiveTab($request, $req->type ?? null);

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cần ký duyệt!')->with('activeTab', $tab);
        }

        $sign = $this->currentSign($req);

        if (! $sign) {
            return redirect()->back()->with('error', 'Đề nghị '.$req->code.' không ở bước chờ ký nên không ký được!')->with('activeTab', $tab);
        }

        if (! $this->canSignRow($sign)) {
            return redirect()->back()
                ->with('error', 'Bước '.$sign->step_no.' của đề nghị '.$req->code.' do '.($sign->user_name ?: 'người khác').' ký, bạn không ký thay được!')
                ->with('activeTab', $tab);
        }

        if ($guard = $this->guardSignature($request, self::REQ_LIST, $req->id, 'Ký duyệt đề nghị cấp phát vật tư')) {
            return $guard->with('activeTab', $tab);
        }

        $stepCount = (int) $req->sign_step_count;
        $stepNo = (int) $sign->step_no;
        $isLast = $stepNo >= $stepCount;
        $signedAt = now();

        DB::transaction(function () use ($req, $sign, $stepNo, $isLast, $signedAt) {
            DB::table(self::REQ_SIGN)->where('id', $sign->id)->update([
                'status' => 'signed',
                'signed_by' => $this->actor(),
                // Bước giao cho nhiều người: ghi rõ AI trong danh sách đã ký
                'signed_user_id' => (int) (session('user')['userId'] ?? 0) ?: null,
                'signed_at' => $signedAt,
                'updated_by' => $this->actor(),
                'updated_at' => $signedAt,
            ]);

            DB::table(self::REQ_LIST)->where('id', $req->id)->update([
                'app_status' => $isLast ? 'approved' : 'pending_sign',
                'current_step' => $isLast ? null : $stepNo + 1,
                'issue_status' => $isLast ? 'waiting' : $req->issue_status,
                'updated_by' => $this->actor(),
                'updated_at' => $signedAt,
            ]);
        });

        AuditTrialController::log(
            'Ký duyệt đề nghị cấp phát vật tư',
            self::REQ_LIST,
            $req->id,
            'Bước '.$stepNo.'/'.$stepCount.' - app_status: '.$req->app_status,
            'app_status: '.($isLast ? 'approved' : 'pending_sign')
        );

        $fresh = DB::table(self::REQ_LIST)->where('id', $req->id)->first();

        if ($isLast) {
            $this->notifyIssuers($fresh);
            $this->notifyCreator($fresh, 'Đề nghị cấp phát vật tư '.$req->code.' đã được ký duyệt đủ '.$stepCount.' bước, chờ kho cấp phát.', 'Đã duyệt');
        } else {
            $this->notifyStep($fresh, $stepNo + 1, $stepCount);
        }

        return redirect()->back()->with(
            'success',
            $isLast
                ? 'Đã ký bước '.$stepNo.'/'.$stepCount.' - đề nghị '.$req->code.' được phê duyệt, kho có thể cấp phát.'
                : 'Đã ký bước '.$stepNo.'/'.$stepCount.' cho đề nghị '.$req->code.'! Đã chuyển tới người ký bước '.($stepNo + 1).'.'
        )->with('activeTab', $tab);
    }

    /** TỪ CHỐI tại bước đang chờ ký: phiếu quay về "Bị từ chối", Tổ sửa rồi trình ký lại. */
    public function requestReject(Request $request)
    {
        $req = $this->resolveRequestForApproval($request);
        $tab = $this->approvalActiveTab($request, $req->type ?? null);

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cần từ chối!')->with('activeTab', $tab);
        }

        $sign = $this->currentSign($req);

        if (! $sign) {
            return redirect()->back()->with('error', 'Đề nghị '.$req->code.' không ở bước chờ ký nên không từ chối được!')->with('activeTab', $tab);
        }

        if (! $this->canSignRow($sign)) {
            return redirect()->back()
                ->with('error', 'Bước '.$sign->step_no.' của đề nghị '.$req->code.' do '.($sign->user_name ?: 'người khác').' ký, bạn không từ chối thay được!')
                ->with('activeTab', $tab);
        }

        $validator = Validator::make($request->all(), [
            'reject_reason' => ['required', 'max:500'],
        ], [
            'reject_reason.required' => 'Vui lòng nhập lý do từ chối.',
            'reject_reason.max' => 'Lý do từ chối tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'requestRejectErrors')->withInput()->with('activeTab', $tab);
        }

        if ($guard = $this->guardSignature($request, self::REQ_LIST, $req->id, 'Từ chối đề nghị cấp phát vật tư')) {
            return $guard->with('activeTab', $tab);
        }

        $rejectedAt = now();

        DB::transaction(function () use ($req, $sign, $request, $rejectedAt) {
            DB::table(self::REQ_SIGN)->where('id', $sign->id)->update([
                'status' => 'rejected',
                'reject_reason' => $request->reject_reason,
                'updated_by' => $this->actor(),
                'updated_at' => $rejectedAt,
            ]);

            DB::table(self::REQ_LIST)->where('id', $req->id)->update([
                'app_status' => 'rejected',
                'current_step' => null,
                'rejected_by' => $this->actor(),
                'rejected_at' => $rejectedAt,
                'reject_step' => (string) $sign->step_no,
                'reject_reason' => $request->reject_reason,
                'updated_by' => $this->actor(),
                'updated_at' => $rejectedAt,
            ]);
        });

        AuditTrialController::log(
            'Từ chối đề nghị cấp phát vật tư',
            self::REQ_LIST,
            $req->id,
            'Bước '.$sign->step_no.' - app_status: '.$req->app_status,
            'app_status: rejected - '.$request->reject_reason
        );

        $this->notifyCreator(
            $req,
            'Đề nghị cấp phát vật tư '.$req->code.' bị trả về ở bước '.$sign->step_no.': '.$request->reject_reason,
            'Bị từ chối'
        );

        return redirect()->back()->with('success', 'Đã từ chối đề nghị '.$req->code.'. Tổ cần sửa lại rồi trình ký lại.')->with('activeTab', $tab);
    }

    public function requestDestroy(Request $request)
    {
        $req = $this->findRequest($request->request_list_id);

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị này.')->with('activeTab', 'regular');
        }

        if (! in_array($req->app_status, ['draft', 'rejected'])) {
            return redirect()->back()->with('error', 'Chỉ huỷ được đề nghị đang Nháp hoặc Bị từ chối.')->with('activeTab', $req->type);
        }

        DB::table(self::REQ_LIST)->where('id', $req->id)->update(['app_status' => 'canceled', 'updated_at' => now()]);

        AuditTrialController::log('Huỷ đề nghị cấp phát vật tư', self::REQ_LIST, $req->id, $req->code, 'Đã huỷ');

        return redirect()->back()->with('success', 'Đã huỷ đề nghị '.$req->code.'.')->with('activeTab', $req->type);
    }

    /* ==========================================================
     |  QUY TRÌNH KÝ DUYỆT TỰ CHỌN
     |
     |  Người lập phiếu khai bao nhiêu bước ký thì bảng material_request_signs có bấy
     |  nhiêu dòng còn hiệu lực, mỗi dòng gắn đúng một người ký. Không khai bước nào thì
     |  trình ký là duyệt luôn - phiếu đi thẳng đến người cấp phát.
     ========================================================== */

    /** Cột trạng thái khi phiếu được TRÌNH KÝ. Không có bước ký nào thì duyệt thẳng. */
    private function submitPayload(int $stepCount): array
    {
        return $stepCount > 0
            ? ['app_status' => 'pending_sign', 'current_step' => 1, 'issue_status' => null]
            : ['app_status' => 'approved', 'current_step' => null, 'issue_status' => 'waiting'];
    }

    private function submitMessage(string $code, int $stepCount, bool $again = false): string
    {
        if ($stepCount === 0) {
            return 'Đề nghị '.$code.' không cần ký duyệt nên đã được duyệt ngay, kho có thể cấp phát.';
        }

        return 'Đã trình ký'.($again ? ' lại' : '').' đề nghị '.$code.' qua '.$stepCount.' bước duyệt!';
    }

    /**
     * Ghi QUY TRÌNH KÝ của một phiếu: chép các bước của quy trình đã khai ở dữ liệu gốc
     * sang phiếu, theo đúng thứ tự ký.
     *
     * Một bước giao cho NHIỀU người thì mỗi người một dòng trong REQ_CANDIDATE - chỉ cần
     * MỘT trong số họ ký là xong bước. Phải chụp lại danh sách trên phiếu chứ không đọc
     * thẳng dữ liệu gốc: quy trình gốc đổi người sau này thì phiếu đang ký dở vẫn giữ đúng
     * danh sách lúc trình ký (21 CFR Part 11).
     */
    private function insertRequestSigns(int $listId, $steps): void
    {
        foreach (collect($steps)->values() as $index => $step) {
            $signers = collect($step->signers ?? []);

            $signId = DB::table(self::REQ_SIGN)->insertGetId([
                'request_list_id' => $listId,
                'step_no' => $index + 1,
                // Người ký đọc ở REQ_CANDIDATE; cột user_id chỉ còn dùng cho phiếu cũ
                'user_id' => null,
                'user_name' => MaterialSignFlow::stepSignerNames($step),
                // Giữ cả vai trò của bước: quy trình gốc đổi người ký sau này thì phiếu cũ
                // vẫn đọc được bước này do cấp nào duyệt
                'role_names' => $step->role_name,
                'status' => 'pending',
                'active' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($signers as $signer) {
                DB::table(self::REQ_CANDIDATE)->insert([
                    'sign_id' => $signId,
                    'user_id' => $signer->user_id,
                    'user_name' => $signer->user_name,
                    'active' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Gắn danh sách NGƯỜI ĐƯỢC KÝ vào các dòng bước ký đã đọc ra.
     *
     * Mỗi dòng nhận thêm ->candidates (id + tên) và ->signer_full_name (tên ghép để hiện
     * trên bảng). Phiếu cũ chỉ có user_id đích danh thì không có dòng candidate nào, lúc
     * đó giữ nguyên cách đọc cũ.
     */
    private function attachCandidates($signs)
    {
        $signs = collect($signs);

        if ($signs->isEmpty()) {
            return $signs;
        }

        $candidates = DB::table(self::REQ_CANDIDATE)
            ->leftJoin('user_management', 'user_management.id', '=', self::REQ_CANDIDATE.'.user_id')
            ->select(
                self::REQ_CANDIDATE.'.sign_id',
                self::REQ_CANDIDATE.'.user_id',
                self::REQ_CANDIDATE.'.user_name',
                'user_management.fullName as full_name'
            )
            ->whereIn(self::REQ_CANDIDATE.'.sign_id', $signs->pluck('id'))
            ->where(self::REQ_CANDIDATE.'.active', 1)
            ->orderBy(self::REQ_CANDIDATE.'.id', 'asc')
            ->get()
            ->groupBy('sign_id');

        foreach ($signs as $sign) {
            $rows = $candidates->get($sign->id, collect());

            $sign->candidates = $rows;
            $sign->candidate_ids = $rows->pluck('user_id')->map(fn ($id) => (int) $id)->all();

            if ($rows->isNotEmpty()) {
                $sign->signer_full_name = $rows
                    ->map(fn ($row) => $row->full_name ?: $row->user_name ?: 'NA')
                    ->implode(' / ');
            }
        }

        return $signs;
    }

    /**
     * QUY TRÌNH KÝ của một phiếu, suy từ dữ liệu gốc "Trình Ký Đề Nghị CP Vật Tư".
     *
     * Mỗi dòng vật tư khớp một quy trình theo phân loại của nó; phiếu lấy quy trình nhiều
     * bước nhất. Người lập phiếu không khai và không sửa được quy trình này.
     *
     * @return array{steps: \Illuminate\Support\Collection, flow: ?object, missing: bool}
     */
    private function requestSignFlow(Request $request, int $departmentId): array
    {
        $categoryIds = collect((array) $request->input('items', []))
            ->map(fn ($item) => (int) ($item['category_id'] ?? 0))
            ->all();

        return MaterialSignFlow::resolveForItems($departmentId, $categoryIds);
    }

    /** Quy trình ký của một phiếu ĐÃ LƯU - đọc các dòng còn hiệu lực ngay trong CSDL. */
    private function requestSignFlowOf($req): array
    {
        $categoryIds = DB::table(self::REQ_ITEM)
            ->where('request_list_id', $req->id)
            ->where('active', 1)
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return MaterialSignFlow::resolveForItems((int) $req->department_id, $categoryIds);
    }

    /**
     * Quy trình ký của từng vật tư, rút gọn thành dữ liệu JSON cho JS.
     *
     * Khối "Quy trình ký duyệt" trên modal là CHỈ ĐỌC và phải đổi theo dòng vật tư người
     * lập đang chọn, nên gửi sẵn cả bảng tra xuống trình duyệt thay vì gọi lại server mỗi
     * lần đổi dòng.
     */
    private function signFlowMapForView(int $departmentId): array
    {
        $map = MaterialSignFlow::mapForDepartment($departmentId);

        $shrink = fn ($matched) => $matched === null ? null : [
            'name' => $matched['flow']->name,
            'steps' => $matched['steps']->map(fn ($step) => [
                'no' => (int) $step->step_no,
                'role' => $step->role_name ?: 'NA',
                // Một bước giao cho nhiều người: chỉ cần MỘT trong số họ ký là xong bước
                'users' => collect($step->signers)->map(fn ($signer) => [
                    'name' => MaterialSignFlow::signerLabel($signer),
                    'dept' => $signer->signer_department_short ?: '',
                    // Người ký đã nghỉ / bị khoá tài khoản thì phải khai lại quy trình gốc
                    'inactive' => ! $signer->signer_active,
                ])->values()->all(),
            ])->values()->all(),
        ];

        return [
            'byCategory' => collect($map['byCategory'])->map($shrink)->all(),
            'fallback' => $shrink($map['fallback']),
        ];
    }

    /**
     * Câu báo lỗi khi phiếu có dòng vật tư chưa khớp quy trình ký nào.
     * Phải khai dữ liệu gốc trước mới trình ký được - không cho khai tay bù vào.
     */
    private function signFlowMissingMessage(): string
    {
        return 'Có dòng vật tư chưa khớp quy trình trình ký nào của phòng nên chưa trình ký được. '
            .'Vào Dữ Liệu Gốc → Trình Ký Đề Nghị CP Vật Tư để khai quy trình cho phân loại tương ứng '
            .'(vật tư tự nhập ngoài danh mục cần một quy trình khai điều kiện "Tất cả").';
    }

    /** Sửa phiếu là khai lại quy trình: không xoá cứng, chỉ bỏ hiệu lực các bước cũ. */
    private function deactivateSigns(int $listId): void
    {
        $signIds = DB::table(self::REQ_SIGN)->where('request_list_id', $listId)->pluck('id');

        if ($signIds->isNotEmpty()) {
            DB::table(self::REQ_CANDIDATE)->whereIn('sign_id', $signIds)->update([
                'active' => 0,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);
        }

        DB::table(self::REQ_SIGN)->where('request_list_id', $listId)->update([
            'active' => 0,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Một trang đề nghị cấp phát ĐÚNG MỘT LOẠI (periodic / risk_assessment / regular), lọc
     * theo ngày lập nhưng LUÔN giữ các đề nghị còn dở dang (chưa ký duyệt xong hoặc kho
     * chưa cấp đủ) dù đã ngoài khoảng lọc - cùng tiêu chí với tab "Đề nghị" trước khi tách
     * thành 3 tab, chỉ khác là tự lọc thêm theo type và mỗi loại có bộ lọc + phân trang
     * riêng (tiền tố $prefix, xem self::REQ_TYPE_TABS).
     */
    private function requestListsOfType(int $departmentId, Request $request, string $type, string $prefix): array
    {
        $range = ListRange::of($request, $prefix);
        $perPage = ListRange::perPage($request, $prefix);
        $unissued = $request->boolean($prefix.'unissued');

        $unissuedCount = DB::table(self::REQ_LIST)
            ->where('department_id', $departmentId)
            ->where('type', $type)
            ->where('app_status', 'approved')
            ->whereIn('issue_status', self::REQ_PENDING_ISSUE_STATUSES)
            ->count();

        $query = DB::table(self::REQ_LIST)
            ->select(self::REQ_LIST.'.*')
            ->where(self::REQ_LIST.'.department_id', $departmentId)
            ->where(self::REQ_LIST.'.type', $type);

        if ($unissued) {
            $query->where(self::REQ_LIST.'.app_status', 'approved')
                ->whereIn(self::REQ_LIST.'.issue_status', self::REQ_PENDING_ISSUE_STATUSES);
        } else {
            $query->tap(ListRange::dateFilterKeep(
                self::REQ_LIST.'.created_at',
                $range,
                fn ($q) => $q
                    ->orWhereIn(self::REQ_LIST.'.app_status', self::REQ_PENDING_APP_STATUSES)
                    ->orWhereIn(self::REQ_LIST.'.issue_status', self::REQ_PENDING_ISSUE_STATUSES)
            ));
        }

        $list = $query->orderBy(self::REQ_LIST.'.id', 'desc')
            ->paginate($perPage, ['*'], ListRange::pageName($prefix))
            ->withQueryString();

        return [
            'list' => $list,
            'range' => $range,
            'perPage' => $perPage,
            'unissued' => $unissued,
            'unissuedCount' => $unissuedCount,
        ];
    }

    /** Các bước ký còn hiệu lực của một loạt phiếu: request_list_id => bước theo step_no. */
    private function signRows($listIds)
    {
        $listIds = collect($listIds)->filter()->values();

        if ($listIds->isEmpty()) {
            return collect();
        }

        return DB::table(self::REQ_SIGN)
            ->leftJoin('user_management', 'user_management.id', '=', self::REQ_SIGN.'.user_id')
            ->leftJoin('deparments', 'deparments.id', '=', 'user_management.deparment_id')
            ->select(
                self::REQ_SIGN.'.*',
                'user_management.fullName as signer_full_name',
                'deparments.shortName as signer_department_short'
            )
            ->where(self::REQ_SIGN.'.active', 1)
            ->whereIn(self::REQ_SIGN.'.request_list_id', $listIds)
            ->orderBy(self::REQ_SIGN.'.step_no', 'asc')
            ->get()
            ->pipe(fn ($rows) => $this->attachCandidates($rows))
            ->groupBy('request_list_id');
    }

    /** Dòng bước ký đang chờ của một phiếu, đọc thẳng từ DB. */
    private function currentSign($req)
    {
        if ($req->app_status !== 'pending_sign' || ! $req->current_step) {
            return null;
        }

        $sign = DB::table(self::REQ_SIGN)
            ->where('request_list_id', $req->id)
            ->where('active', 1)
            ->where('step_no', (int) $req->current_step)
            ->first();

        return $sign ? $this->attachCandidates([$sign])->first() : null;
    }

    /** Bước đang chờ ký, lấy từ bộ bước đã nạp sẵn ở index() để khỏi truy vấn lại từng phiếu. */
    private function pendingSign($req, $signs)
    {
        if ($req->app_status !== 'pending_sign' || ! $req->current_step) {
            return null;
        }

        return collect($signs)->firstWhere('step_no', (int) $req->current_step);
    }

    /**
     * Người đang đăng nhập có ký được bước này không.
     *
     * Phiếu mới giao bước cho MỘT HOẶC NHIỀU người (REQ_CANDIDATE) - ai trong danh sách
     * đó ký cũng được, và chỉ cần một người ký là xong bước. Phiếu chỉ định đích danh một
     * người (user_id) và phiếu cũ khai theo chức danh (role_names) vẫn đọc như trước.
     */
    private function canSignRow($sign): bool
    {
        $userId = (int) (session('user')['userId'] ?? 0);
        $candidateIds = $sign->candidate_ids ?? null;

        if ($candidateIds) {
            return in_array($userId, $candidateIds, true);
        }

        if ($sign->user_id) {
            return (int) $sign->user_id === $userId;
        }

        $roles = array_values(array_filter(array_map('trim', explode(',', (string) $sign->role_names))));

        return $roles ? user_has_any_role($userId, $roles) : false;
    }

    /* ==========================================================
     |  HỘP KÝ DUYỆT LIÊN PHÒNG BAN (tab "Ký duyệt (mọi phòng ban)")
     |
     |  Người ký thuộc phòng ban CHUNG (deparments.is_general = 0: Ban Giám Đốc, Cung
     |  Ứng...) không có kho riêng, phải chuyển bộ phận từng lần mới thấy phiếu chờ ký.
     |  Tab này gom mọi đề nghị của các phòng trong cùng công ty đang chờ CHÍNH người
     |  đang đăng nhập ký. Ký / từ chối vẫn đi qua requestSign / requestReject, chỉ khác
     |  cách tìm phiếu: theo công ty thay vì theo phòng ban đang chọn.
     ========================================================== */

    /** Phòng ban NHÀ của người đang đăng nhập (user_management.deparment_id). */
    private function homeDepartmentId(): int
    {
        return (int) DB::table('user_management')
            ->where('id', session('user')['userId'] ?? 0)
            ->value('deparment_id');
    }

    /** Được dùng hộp ký duyệt liên phòng ban không: có quyền is_BOD và phòng nhà là phòng chung. */
    private function canUseApprovalInbox(): bool
    {
        if (! user_can('is_BOD')) {
            return false;
        }

        $homeDeptId = $this->homeDepartmentId();

        return $homeDeptId
            ? (int) DB::table('deparments')->where('id', $homeDeptId)->value('is_general') === 0
            : false;
    }

    /**
     * Tab cần mở lại sau khi ký / từ chối: 'inbox' khi thao tác từ hộp ký duyệt, còn lại
     * đúng tab loại của đề nghị ($type = material_request_lists.type). $type chỉ có sau
     * khi resolveRequestForApproval() tìm ra phiếu nên gọi hàm này SAU, truyền vào nếu có.
     */
    private function approvalActiveTab(Request $request, ?string $type = null): string
    {
        if ($request->input('scope') === 'inbox' && $this->canUseApprovalInbox()) {
            return 'inbox';
        }

        return $type ?? 'regular';
    }

    /**
     * Tìm đề nghị để ký / từ chối.
     *
     * Bình thường lấy theo phòng ban đang chọn. Khi thao tác từ hộp ký duyệt liên phòng
     * ban (scope = inbox) thì lấy theo CÔNG TY đang làm việc - currentSign() + canSignRow()
     * vẫn chặn đúng người ký nên không lỏng quyền.
     */
    private function resolveRequestForApproval(Request $request)
    {
        if ($request->input('scope') === 'inbox' && $this->canUseApprovalInbox()) {
            $companyDeptIds = CompanyContext::departmentIds(CompanyContext::currentId());

            return DB::table(self::REQ_LIST)
                ->where('id', $request->request_list_id)
                ->when($companyDeptIds !== null, fn ($query) => $query->whereIn('department_id', $companyDeptIds))
                ->first();
        }

        return $this->findRequest($request->request_list_id);
    }

    /**
     * Dữ liệu tab "Ký duyệt (mọi phòng ban)": các đề nghị pending_sign của mọi phòng
     * trong cùng công ty mà BƯỚC ĐANG CHỜ được giao đích danh cho người đang đăng nhập.
     */
    private function approvalInboxData(): array
    {
        $empty = ['show' => false, 'requests' => collect(), 'items' => collect(), 'signs' => collect(), 'badge' => 0];

        if (! $this->canUseApprovalInbox()) {
            return $empty;
        }

        $myUserId = (int) (session('user')['userId'] ?? 0);
        $companyDeptIds = CompanyContext::departmentIds(CompanyContext::currentId());

        $requests = DB::table(self::REQ_LIST)
            ->join(self::REQ_SIGN, function ($join) {
                $join->on(self::REQ_SIGN.'.request_list_id', '=', self::REQ_LIST.'.id')
                    ->on(self::REQ_SIGN.'.step_no', '=', self::REQ_LIST.'.current_step')
                    ->where(self::REQ_SIGN.'.active', 1);
            })
            ->leftJoin('deparments', self::REQ_LIST.'.department_id', '=', 'deparments.id')
            ->where(self::REQ_LIST.'.app_status', 'pending_sign')
            ->join(self::REQ_CANDIDATE, function ($join) use ($myUserId) {
                $join->on(self::REQ_CANDIDATE.'.sign_id', '=', self::REQ_SIGN.'.id')
                    ->where(self::REQ_CANDIDATE.'.active', 1)
                    ->where(self::REQ_CANDIDATE.'.user_id', $myUserId);
            })
            ->where(self::REQ_SIGN.'.status', 'pending')
            ->when($companyDeptIds !== null, fn ($query) => $query->whereIn(self::REQ_LIST.'.department_id', $companyDeptIds))
            ->select(
                self::REQ_LIST.'.*',
                'deparments.name as department_name',
                'deparments.shortName as department_short',
                self::REQ_SIGN.'.step_no as my_step_no'
            )
            ->orderBy(self::REQ_LIST.'.submitted_at', 'asc')
            ->orderBy(self::REQ_LIST.'.id', 'asc')
            ->get();

        $ids = $requests->pluck('id');

        $items = DB::table(self::REQ_ITEM)
            ->leftJoin('material_categories', self::REQ_ITEM.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->select(
                self::REQ_ITEM.'.*',
                'material_names.name as category_material_name',
                'material_categories.code as category_code'
            )
            ->where(self::REQ_ITEM.'.active', 1)
            ->whereIn(self::REQ_ITEM.'.request_list_id', $ids)
            ->orderBy(self::REQ_ITEM.'.id', 'asc')
            ->get()
            ->map(function ($item) {
                $item->display_name = $item->category_id ? $item->category_material_name : $item->material_name;

                return $item;
            })
            ->groupBy('request_list_id');

        return [
            'show' => true,
            'requests' => $requests,
            'items' => $items,
            'signs' => $this->signRows($ids),
            'badge' => $requests->count(),
        ];
    }

    /* ---------- Thông báo cho người phải xử lý tiếp ---------- */

    /** Sau khi trình ký: báo người ký bước 1, hoặc báo thẳng người cấp phát khi 0 bước. */
    private function notifySubmitted($req, int $stepCount): void
    {
        if (! $req) {
            return;
        }

        if ($stepCount > 0) {
            $this->notifyStep($req, 1, $stepCount);

            return;
        }

        $this->notifyIssuers($req);
    }

    /** Báo cho người ký của một bước rằng đã tới lượt mình. */
    private function notifyStep($req, int $stepNo, int $stepCount): void
    {
        $sign = DB::table(self::REQ_SIGN)
            ->where('request_list_id', $req->id)
            ->where('active', 1)
            ->where('step_no', $stepNo)
            ->first();

        if (! $sign) {
            return;
        }

        // Bước giao cho nhiều người thì báo cho tất cả - ai ký trước cũng được
        $userIds = DB::table(self::REQ_CANDIDATE)
            ->where('sign_id', $sign->id)
            ->where('active', 1)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Phiếu cũ khai theo role chứ không đích danh ai thì không có người để báo
        if (! $userIds && $sign->user_id) {
            $userIds = [(int) $sign->user_id];
        }

        if (! $userIds) {
            return;
        }

        $this->notify(
            'Đề nghị cấp phát vật tư '.$req->code.' đang chờ bạn ký duyệt (bước '.$stepNo.'/'.$stepCount.').',
            'Chờ ký duyệt',
            $req,
            $userIds
        );
    }

    /** Báo cho người cấp phát của phòng rằng có phiếu đã duyệt, chờ xuất kho. */
    private function notifyIssuers($req): void
    {
        $this->notify(
            'Đề nghị cấp phát vật tư '.$req->code.' đã được duyệt, chờ kho cấp phát.',
            'Chờ cấp phát',
            $req,
            users_with_permission('export_material_issue', (int) $req->department_id)
        );
    }

    private function notifyCreator($req, string $message, string $activityType): void
    {
        $this->notify($message, $activityType, $req, [(int) ($req->created_user_id ?? 0)]);
    }

    /**
     * Gửi thông báo vào chuông, kèm đường dẫn mở đúng tab loại của đề nghị.
     * NotificationController tự bỏ người gửi ra khỏi danh sách nhận.
     */
    private function notify(string $message, string $activityType, $req, array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter($userIds)));

        if (! $userIds) {
            return;
        }

        NotificationController::sendNotification(
            $message,
            $activityType,
            (int) $req->id,
            $userIds,
            [],
            route('pages.export.materialExport.list', ['tab' => $req->type ?? 'regular'])
        );
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

        $fail = function ($message) use ($request, &$req) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }

            return redirect()->back()->with('error', $message)->with('activeTab', $req->type ?? 'regular');
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

            // Cấp phát là hàng đã rời kho (xuất kho = đem sử dụng): mỗi lô một phiếu sử
            // dụng để trừ tồn ngay, không có bước chốt lại.
            foreach ($lines as $line) {
                $exportId = DB::table(self::TABLE)->insertGetId([
                    'code' => $line['import']->code,
                    'import_id' => (int) $line['import']->id,
                    'department_id' => $departmentId,
                    'request_item_id' => $item->id,
                    'amount' => $line['amount'],
                    'type' => 'export',
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

        return redirect()->route('pages.export.materialExport.list', ['tab' => $req->type])
            ->with('success', $message);
    }

    public function issueReject(Request $request)
    {
        $departmentId = $this->departmentId();

        $item = DB::table(self::REQ_ITEM)->where('id', $request->item_id)->first();
        $req = $item ? DB::table(self::REQ_LIST)->where('id', $item->request_list_id)->where('department_id', $departmentId)->first() : null;

        if (! $item || ! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy mục đề nghị!')->with('activeTab', 'regular');
        }

        DB::table(self::REQ_ITEM)->where('id', $item->id)->update([
            'status' => 'rejected',
            'note' => $this->nullIfBlank($request->note ?: $request->cancel_reason ?: $item->note),
            'updated_at' => now(),
        ]);

        $this->refreshIssueStatus($item->request_list_id);

        AuditTrialController::log('Từ chối cấp phát vật tư', self::REQ_ITEM, $item->id, $item->status, 'rejected');

        return redirect()->back()->with('success', 'Đã từ chối cấp phát mục đề nghị.')->with('activeTab', $req->type);
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

        // Chạy trong after() để lỗi tự thêm không bị passes() xoá khi gọi fails()
        $validator->after(function ($v) use ($request, $departmentId, &$import, &$item) {
            [$import, $item] = $this->resolveUseTarget($v, $request, $departmentId);
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId([
            'code' => $import->code,
            'import_id' => (int) $import->id,
            'department_id' => $departmentId,
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

        if ($guard = $this->transferOutGuard($current, 'sửa')) {
            return $guard;
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

        if ($guard = $this->transferOutGuard($current, 'khoá / mở khoá')) {
            return $guard;
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

    /**
     * category_id -> {remaining, available} của TOÀN BỘ danh mục vật tư trong phòng, dùng để
     * dựng lại 2 cột "Tồn" / "Tồn khả dụng" ở bảng dòng đề nghị cấp phát mỗi khi mở modal
     * tạo/sửa - xem categoryStockMap().
     */
    public function requestStockMap(Request $request)
    {
        return response()->json([
            'ok' => true,
            'stock' => (object) $this->categoryStockMap($this->departmentId())->all(),
        ]);
    }

    /**
     * "Đề nghị dự trù vật tư" bấm ngay trên một dòng đề nghị của modal Thường Quy / Theo ĐG
     * Rủi Ro - không phụ thuộc đề nghị đó có được lưu hay không, bắt buộc nêu lý do dự trù.
     * Hiện lại ở tab "Danh sách vật tư cần dự trù" bên Dự Trù Vật Tư - xem
     * App\Support\MaterialWatchlist.
     */
    public function watchlistRemember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['nullable', 'integer', 'exists:material_categories,id'],
            'material_name' => ['nullable', 'max:255'],
            'technical_specification' => ['nullable', 'max:255'],
            'note' => ['required', 'max:500'],
            'source_type' => ['nullable', 'in:regular,risk_assessment'],
        ], [
            'note.required' => 'Vui lòng nhập lý do dự trù.',
            'note.max' => 'Lý do dự trù tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $result = MaterialWatchlist::remember(
            $this->departmentId(),
            $request->category_id ? (int) $request->category_id : null,
            $request->material_name,
            $request->technical_specification,
            $request->note,
            $request->source_type,
            $this->actor()
        );

        return response()->json($result);
    }

    /**
     * Tồn hiện tại và "tồn khả dụng" của từng danh mục vật tư trong phòng.
     *
     * "Tồn khả dụng" = tồn - phần các đề nghị KHÁC (đang chờ ký, hoặc đã duyệt nhưng kho
     * chưa cấp đủ) đã giữ chỗ, tính động mỗi lần gọi - KHÁC với cột "Tồn" lưu tĩnh trên
     * từng dòng đề nghị (material_request_items.stock_at_request, chụp một lần lúc thêm
     * dòng, không tính lại). Đề nghị đang sửa (draft/rejected) chưa từng giữ chỗ nên không
     * cần loại trừ khỏi phần đang giữ chỗ.
     */
    private function categoryStockMap(int $departmentId, ?\Illuminate\Support\Collection $availableImports = null): \Illuminate\Support\Collection
    {
        $availableImports ??= $this->importOptions($departmentId);

        $remaining = $availableImports->where('suggestable', true)
            ->groupBy('category_id')
            ->map(fn ($group) => (float) $group->sum('remaining'));

        $reserved = DB::table(self::REQ_ITEM)
            ->join(self::REQ_LIST, self::REQ_LIST.'.id', '=', self::REQ_ITEM.'.request_list_id')
            ->where(self::REQ_LIST.'.department_id', $departmentId)
            ->whereIn(self::REQ_ITEM.'.status', ['pending', 'partial'])
            ->where(self::REQ_ITEM.'.active', 1)
            ->whereNotNull(self::REQ_ITEM.'.category_id')
            ->where(function ($q) {
                $q->where(self::REQ_LIST.'.app_status', 'pending_sign')
                    ->orWhere(function ($q2) {
                        $q2->where(self::REQ_LIST.'.app_status', 'approved')
                            ->whereIn(self::REQ_LIST.'.issue_status', self::REQ_PENDING_ISSUE_STATUSES);
                    });
            })
            ->groupBy(self::REQ_ITEM.'.category_id')
            ->selectRaw(self::REQ_ITEM.'.category_id as category_id, SUM('.self::REQ_ITEM.'.requested_amount - COALESCE('.self::REQ_ITEM.'.issued_amount, 0)) as reserved')
            ->pluck('reserved', 'category_id');

        return $remaining->keys()->merge($reserved->keys())->unique()->mapWithKeys(function ($categoryId) use ($remaining, $reserved) {
            $rem = (float) ($remaining[$categoryId] ?? 0);
            $res = (float) ($reserved[$categoryId] ?? 0);

            return [$categoryId => ['remaining' => $rem, 'available' => $rem - $res]];
        });
    }


    /* ==========================================================
     |  ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN
     |
     |  Mô hình 3 bước, giống hệt "Đề nghị chuyển hoá chất liên phòng ban"
     |  (ChemicalExportController): A đề nghị -> B cấp phát (trừ tồn B ngay bằng một phiếu
     |  material_exports type = transfer_out, mục sang "Chờ nhận") -> A bấm Nhận thì mới
     |  tạo dòng material_imports cho A. A từ chối nhận thì khoá phiếu transfer_out là tồn
     |  của B tự hoàn lại.
     |
     |  material_transfer_requests.department_id    = phòng ĐỀ NGHỊ (A, cần vật tư).
     |  material_transfer_requests.to_department_id = phòng ĐƯỢC ĐỀ NGHỊ (B, đang giữ vật tư).
     ========================================================== */

    /**
     * Phiếu cấp phát liên phòng ban không được sửa / khoá ở sổ sử dụng.
     *
     * Số lượng trên phiếu đã thành tồn của phòng nhận (hoặc đang chờ phòng nhận xác nhận),
     * sửa ở đây là lệch giữa hai phòng. Muốn thu hồi thì phòng nhận Từ chối nhận.
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
            .'Đây là phiếu do tính năng "Đề nghị chuyển vật tư liên phòng ban" tạo ra, '
            .'chỉ thay đổi được qua thao tác Nhận / Từ chối nhận của phòng nhận.'
        );
    }

    /**
     * PHÒNG A TẠO ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN (bước 1/3)
     */
    public function transferRequestStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), $this->transferRules($departmentId), $this->transferMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'transferCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'transfer');
        }

        $isDraft = $request->input('action_type', 'send') === 'draft';
        $status = $isDraft ? 'draft' : 'pending';
        $toDepartmentId = (int) $request->to_department_id;
        $code = $this->nextMaterialTransferCode($departmentId, $toDepartmentId);

        $listId = DB::transaction(function () use ($request, $departmentId, $toDepartmentId, $code, $status) {
            $listId = DB::table(self::TRANSFER_REQUEST_TABLE)->insertGetId([
                'code' => $code,
                'title' => $this->nullIfBlank($request->title),
                'department_id' => $departmentId,
                'to_department_id' => $toDepartmentId,
                'status' => $status,
                'note' => $this->nullIfBlank($request->note),
                'needed_date' => $this->nullIfBlank($request->needed_date),
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->insertTransferItems($listId, $request, $status);

            return $listId;
        });

        $toDeptName = $this->departmentName($toDepartmentId);

        AuditTrialController::log(
            $isDraft ? 'Lưu tạm đề nghị chuyển vật tư liên phòng ban' : 'Tạo đề nghị chuyển vật tư liên phòng ban',
            self::TRANSFER_REQUEST_TABLE,
            $listId,
            'NA',
            ($isDraft ? 'Lưu tạm đề nghị ' : 'Tạo đề nghị chuyển liên phòng ban ').$code
                .' gửi đến '.$toDeptName.' ('.count((array) $request->items).' mục)'
        );

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'transfer'])->with(
            'success',
            $isDraft
                ? 'Đã lưu tạm đề nghị chuyển vật tư liên phòng ban '.$code.'! Bạn có thể gửi đề nghị khi sẵn sàng.'
                : 'Đã gửi đề nghị chuyển vật tư liên phòng ban '.$code.' đến '.$toDeptName.' thành công!'
        );
    }

    /**
     * PHÒNG A ĐIỀU CHỈNH ĐỀ NGHỊ LIÊN PHÒNG BAN ĐANG LƯU TẠM
     */
    public function transferRequestUpdate(Request $request)
    {
        $departmentId = $this->departmentId();

        $req = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $request->transfer_request_id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $req || $req->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Chỉ có thể điều chỉnh phiếu đề nghị đang ở trạng thái Lưu tạm!')
                ->with('activeTab', 'transfer');
        }

        $validator = Validator::make($request->all(), $this->transferRules($departmentId), $this->transferMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'transferCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'transfer');
        }

        $isDraft = $request->input('action_type', 'draft') === 'draft';
        $status = $isDraft ? 'draft' : 'pending';
        $toDepartmentId = (int) $request->to_department_id;

        DB::transaction(function () use ($request, $req, $toDepartmentId, $status) {
            DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $req->id)->update([
                'title' => $this->nullIfBlank($request->title),
                'to_department_id' => $toDepartmentId,
                'status' => $status,
                'note' => $this->nullIfBlank($request->note),
                'needed_date' => $this->nullIfBlank($request->needed_date),
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Không xoá cứng: bỏ hiệu lực các mục cũ (active = 0) rồi thêm lại từ đầu
            DB::table(self::TRANSFER_ITEM_TABLE)->where('transfer_request_id', $req->id)->update([
                'active' => 0,
                'updated_at' => now(),
            ]);

            $this->insertTransferItems($req->id, $request, $status);
        });

        $toDeptName = $this->departmentName($toDepartmentId);

        AuditTrialController::log(
            $isDraft ? 'Cập nhật đề nghị chuyển vật tư liên phòng ban' : 'Gửi đề nghị chuyển vật tư liên phòng ban sau cập nhật',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            'draft',
            ($isDraft ? 'Cập nhật đề nghị ' : 'Gửi đề nghị ').$req->code.' gửi đến '.$toDeptName
                .' ('.count((array) $request->items).' mục)'
        );

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'transfer'])->with(
            'success',
            $isDraft
                ? 'Đã cập nhật lưu tạm đề nghị '.$req->code.' thành công!'
                : 'Đã cập nhật và gửi đề nghị '.$req->code.' thành công!'
        );
    }

    /**
     * GỬI ĐỀ NGHỊ LIÊN PHÒNG BAN ĐÃ LƯU TẠM
     */
    public function transferRequestSend(Request $request)
    {
        $req = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $request->transfer_request_id)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $req || $req->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Không tìm thấy phiếu đề nghị lưu tạm cần gửi!')
                ->with('activeTab', 'transfer');
        }

        DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $req->id)->update([
            'status' => 'pending',
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DB::table(self::TRANSFER_ITEM_TABLE)
            ->where('transfer_request_id', $req->id)
            ->where('status', 'draft')
            ->update(['status' => 'pending', 'updated_at' => now()]);

        AuditTrialController::log(
            'Gửi đề nghị chuyển vật tư liên phòng ban',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            'draft',
            'Gửi đề nghị chuyển liên phòng ban: '.$req->code
        );

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'transfer'])
            ->with('success', 'Đã gửi đề nghị chuyển liên phòng ban mã '.$req->code.' thành công!');
    }

    /**
     * HUỶ ĐỀ NGHỊ LIÊN PHÒNG BAN ĐANG LƯU TẠM
     */
    public function transferRequestDestroy(Request $request)
    {
        $req = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $request->transfer_request_id)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị này.')->with('activeTab', 'transfer');
        }

        if ($req->status !== 'draft') {
            return redirect()->back()->with('error', 'Chỉ có thể huỷ phiếu đang ở trạng thái Lưu tạm.')->with('activeTab', 'transfer');
        }

        DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $req->id)->update([
            'status' => 'canceled',
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Huỷ đề nghị chuyển vật tư liên phòng ban',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            $req->code,
            'Đã huỷ đề nghị chuyển liên phòng ban đang lưu tạm'
        );

        return redirect()->back()->with('success', 'Đã huỷ phiếu đề nghị '.$req->code.' thành công!')->with('activeTab', 'transfer');
    }

    /**
     * DANH MỤC VẬT TƯ + TỒN KHO CỦA PHÒNG ĐƯỢC ĐỀ NGHỊ, trả JSON cho picker chọn nhiều.
     *
     * Phòng A lập đề nghị cần nhìn thấy phòng B (phòng sẽ cấp phát) đang có những vật tư
     * gì và còn bao nhiêu, nên bảng chọn phải đọc kho của B chứ không phải kho của mình.
     * Vì phòng B chỉ được chọn ngay trên form nên dữ liệu nạp bằng AJAX, không dựng sẵn
     * tồn của mọi phòng vào trang.
     */
    public function transferDepartmentStock(Request $request)
    {
        $departmentId = (int) $request->query('department_id');

        if ($departmentId <= 0 || $departmentId === $this->departmentId()) {
            return response()->json(['ok' => false, 'message' => 'Vui lòng chọn phòng ban đang giữ vật tư trước khi mở danh mục.']);
        }

        $department = DB::table('deparments')->where('id', $departmentId)->where('isActive', 1)->first();

        if (! $department) {
            return response()->json(['ok' => false, 'message' => 'Phòng ban được chọn không tồn tại hoặc đã ngừng hoạt động.']);
        }

        // Tồn của từng lô trong kho phòng B, gom theo danh mục vật tư
        $lots = MaterialPicking::lots($departmentId)->groupBy('category_id');

        $rows = DepartmentMaterial::importCategoryOptions($departmentId)->map(function ($category) use ($lots) {
            $group = $lots->get($category->id, collect());

            return [
                'id' => (int) $category->id,
                'code' => $category->code,
                'material_name' => $category->material_name,
                'technical_specification' => $category->technical_specification,
                'manufacturer_name' => $category->manufacturer_name ?: $category->manufacturer_short_name,
                'classification_name' => \App\Support\MaterialClassification::summary($category->classification),
                'unit' => $category->unit_short_name ?: $category->unit_name,
                'remaining' => (float) $group->sum('remaining'),
                'lots' => (int) $group->where('remaining', '>', self::EPSILON)->count(),
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'department_name' => $department->name,
            'department_short' => $department->shortName,
            'rows' => $rows,
        ]);
    }

    /**
     * PHÒNG B CẤP PHÁT CHO 1 MỤC ĐỀ NGHỊ LIÊN PHÒNG BAN (bước 2/3)
     *
     * Chỉ trừ tồn mã xuất nhập nguồn tại B bằng một dòng material_exports
     * type = transfer_out - CHƯA tạo tồn cho A. Mục chuyển sang status 'issued' (chờ
     * nhận); dòng material_imports thật cho A chỉ sinh ra ở bước A bấm Nhận, lúc đó mới
     * chắc A đã khai vật tư này và có đơn vị tính để quy đổi.
     */
    public function transferIssueStore(Request $request)
    {
        $departmentId = $this->departmentId(); // B

        $error = function (string $message) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }

            return redirect()->back()->with('error', $message)->with('activeTab', 'transfer');
        };

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::TRANSFER_ITEM_TABLE.',id'],
            'import_id' => ['required', 'exists:material_imports,id'],
            'issued_amount' => ['required', 'numeric', 'min:0.0001'],
            'issued_unit' => ['nullable', 'string', 'max:50'],
        ], [
            'item_id.required' => 'Không tìm thấy mục đề nghị cần cấp phát.',
            'import_id.required' => 'Vui lòng chọn mã xuất nhập trong kho để cấp phát.',
            'issued_amount.required' => 'Vui lòng nhập số lượng cấp phát.',
            'issued_amount.min' => 'Số lượng cấp phát phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            return $error($validator->errors()->first());
        }

        $item = DB::table(self::TRANSFER_ITEM_TABLE)
            ->where('id', $request->item_id)
            ->where('active', 1)
            ->where('status', 'pending')
            ->first();

        if (! $item) {
            return $error('Không tìm thấy mục đề nghị hoặc mục này đã được xử lý!');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $item->transfer_request_id)->first();

        if (! $transferReq || (int) $transferReq->to_department_id !== $departmentId) {
            return $error('Không tìm thấy phiếu đề nghị thuộc phòng ban này!');
        }

        $sourceImport = DB::table('material_imports')
            ->where('id', $request->import_id)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->first();

        if (! $sourceImport) {
            return $error('Không tìm thấy mã xuất nhập trong kho phòng ban này!');
        }

        if ((int) $sourceImport->category_id !== (int) $item->category_id) {
            return $error('Mã xuất nhập được chọn không đúng vật tư của mục đề nghị!');
        }

        if ($sourceImport->expired_date && now()->startOfDay()->gt(\Carbon\Carbon::parse($sourceImport->expired_date))) {
            return $error('Mã xuất nhập '.$sourceImport->code.' đã hết hạn sử dụng, không được cấp phát!');
        }

        /*
        | Chuyển liên phòng ban KHÔNG được xuất vượt tồn: hàng chuyển đi thành tồn của
        | phòng nhận, cho vượt là tự sinh thêm hàng trong hệ thống. Mốc chặn là tồn CÒN
        | HỨA ĐƯỢC - phần đang giữ cho một đợt lấy hàng còn treo không đem chuyển được.
        */
        $available = $this->available($sourceImport);
        $issuedAmount = round((float) $request->issued_amount, 4);

        if ($issuedAmount > $available + self::EPSILON) {
            $held = $this->remaining($sourceImport) - $available;

            return $error(
                'Mã xuất nhập '.$sourceImport->code.' chỉ còn hứa được '.$this->number($available)
                .($held > self::EPSILON ? ' (đang giữ '.$this->number($held).' cho đợt lấy hàng)' : '')
                .', không đủ để cấp phát '.$this->number($issuedAmount).'.'
            );
        }

        $aDepartmentId = (int) $transferReq->department_id;
        $aDeptName = $this->departmentName($aDepartmentId);
        $issuedAt = now();
        $issuedUnit = $this->nullIfBlank($request->issued_unit ?: $item->requested_unit);

        $exportId = DB::transaction(function () use (
            $item, $sourceImport, $departmentId, $aDepartmentId, $issuedAmount, $issuedUnit, $issuedAt
        ) {
            // Trừ tồn phòng nguồn (B) - tồn của A chờ đến khi A bấm Nhận mới được tạo
            $exportId = DB::table(self::TABLE)->insertGetId([
                'code' => $sourceImport->code,
                'import_id' => (int) $sourceImport->id,
                'department_id' => $departmentId,
                'to_department_id' => $aDepartmentId,
                'transfer_item_id' => $item->id,
                'amount' => $issuedAmount,
                'type' => self::TYPE_TRANSFER_OUT,
                'used_by' => $this->actor(),
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => $issuedAt,
                'updated_at' => $issuedAt,
            ]);

            $this->logHistory(
                $exportId,
                'Cấp phát',
                'Cấp phát liên phòng ban: chuyển '.$this->number($issuedAmount).' '.($issuedUnit ?: '')
                .' sang phòng '.$this->departmentName($aDepartmentId).', chờ phòng nhận xác nhận.'
            );

            DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $item->id)->update([
                'status' => 'issued',
                'import_id' => (int) $sourceImport->id,
                'import_code' => $sourceImport->code,
                'issued_amount' => $issuedAmount,
                'issued_unit' => $issuedUnit,
                'issued_by' => $this->actor(),
                'issued_at' => $issuedAt,
                'updated_at' => $issuedAt,
            ]);

            $this->refreshTransferStatus((int) $item->transfer_request_id, $issuedAt);

            return $exportId;
        });

        AuditTrialController::log(
            'Cấp phát vật tư liên phòng ban',
            self::TABLE,
            $exportId,
            'NA',
            'Chuyển '.$sourceImport->code.' số lượng '.$this->number($issuedAmount).' đến phòng '.$aDeptName.', chờ phòng nhận xác nhận.'
        );

        $message = 'Đã cấp phát mã xuất nhập '.$sourceImport->code.' thành công, chờ phòng '.$aDeptName.' xác nhận nhận hàng!';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'issued_amount' => $issuedAmount,
                    'issued_unit' => $issuedUnit,
                    'issued_by' => $this->actor(),
                    'issued_at' => $issuedAt->format('d/m/Y H:i'),
                    'import_code' => $sourceImport->code,
                ],
            ]);
        }

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'transfer'])->with('success', $message);
    }

    /**
     * PHÒNG B TỪ CHỐI CẤP PHÁT 1 MỤC ĐỀ NGHỊ LIÊN PHÒNG BAN
     */
    public function transferRequestReject(Request $request)
    {
        $departmentId = $this->departmentId(); // B

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::TRANSFER_ITEM_TABLE.',id'],
            'reject_note' => ['required', 'max:500'],
        ], [
            'reject_note.required' => 'Vui lòng nhập lý do từ chối.',
            'reject_note.max' => 'Lý do từ chối tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->with('activeTab', 'transfer');
        }

        $item = DB::table(self::TRANSFER_ITEM_TABLE)
            ->where('id', $request->item_id)
            ->where('active', 1)
            ->where('status', 'pending')
            ->first();

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy mục đề nghị!')->with('activeTab', 'transfer');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $item->transfer_request_id)
            ->where('to_department_id', $departmentId)
            ->first();

        if (! $transferReq) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị thuộc phòng ban này!')->with('activeTab', 'transfer');
        }

        DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $item->id)->update([
            'status' => 'rejected',
            'reject_note' => trim((string) $request->reject_note),
            'updated_at' => now(),
        ]);

        $this->refreshTransferStatus((int) $item->transfer_request_id, now());

        AuditTrialController::log(
            'Từ chối cấp phát vật tư liên phòng ban',
            self::TRANSFER_ITEM_TABLE,
            $item->id,
            'pending',
            'Từ chối cấp phát: '.$request->reject_note
        );

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'transfer'])
            ->with('success', 'Đã từ chối mục đề nghị cấp phát liên phòng ban.');
    }

    /**
     * PHÒNG A NHẬN VẬT TƯ ĐÃ ĐƯỢC CẤP PHÁT (bước 3/3)
     *
     * Đến đây mới thật sự tạo dòng material_imports cho A: bắt buộc A đã khai vật tư này
     * ở tab "Vật Tư Của Phòng" (mới có đơn vị tính để quy đổi qua CategoryUnitConversion),
     * rồi tự chọn định khu của phòng mình. Mã lô mới sinh theo chuẩn mã vật tư của phòng A
     * (App\Support\MaterialCode), mã nguồn được ghi lại ở ghi chú để còn truy vết.
     */
    public function transferReceiveStore(Request $request)
    {
        $departmentId = $this->departmentId(); // A

        $error = function (string $message) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }

            return redirect()->back()->with('error', $message)->with('activeTab', 'transfer');
        };

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::TRANSFER_ITEM_TABLE.',id'],
            'dest_location_id' => ['nullable'],
        ], [
            'item_id.required' => 'Không tìm thấy mục cần nhận.',
        ]);

        if ($validator->fails()) {
            return $error($validator->errors()->first());
        }

        $item = DB::table(self::TRANSFER_ITEM_TABLE)
            ->where('id', $request->item_id)
            ->where('active', 1)
            ->where('status', 'issued')
            ->first();

        if (! $item) {
            return $error('Không tìm thấy mục cần nhận hoặc mục này đã được xử lý!');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $item->transfer_request_id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $transferReq) {
            return $error('Không tìm thấy phiếu đề nghị thuộc phòng ban này!');
        }

        $sourceImport = DB::table('material_imports')->where('id', $item->import_id)->first();

        if (! $sourceImport) {
            return $error('Không tìm thấy mã xuất nhập nguồn của mục này!');
        }

        $bDepartmentId = (int) $transferReq->to_department_id;
        $bDeptName = $this->departmentName($bDepartmentId);

        // Phòng A phải đã khai vật tư này thì mới có đơn vị tính để nhận
        $aCategoryRow = DB::table(DepartmentMaterial::TABLE)
            ->where('department_id', $departmentId)
            ->where('category_id', $item->category_id)
            ->where('status_id', 1)
            ->first();

        if (! $aCategoryRow) {
            return $error('Phòng bạn chưa khai vật tư này ở tab "Vật Tư Của Phòng" nên chưa nhận được. Vui lòng khai trước rồi quay lại nhận.');
        }

        $aUnitId = (int) $aCategoryRow->unit_id;

        if (! $aUnitId) {
            return $error('Phòng bạn chưa khai đơn vị tính cho vật tư này ở tab "Vật Tư Của Phòng" nên chưa có đơn vị để nhận hàng.');
        }

        $bUnitId = (int) DB::table(DepartmentMaterial::TABLE)
            ->where('department_id', $bDepartmentId)
            ->where('category_id', $item->category_id)
            ->value('unit_id');

        $convertedAmount = CategoryUnitConversion::convert(
            CategoryUnitConversion::TYPE_MATERIAL,
            (int) $item->category_id,
            (float) $item->issued_amount,
            $bUnitId ?: null,
            $aUnitId
        );

        if ($convertedAmount === null) {
            return $error(
                'Phòng bạn tính theo đơn vị khác với phòng '.$bDeptName.' cho vật tư này, nhưng chưa có hệ số '
                .'quy đổi giữa hai đơn vị. Vui lòng vào tab "Vật Tư Của Phòng", sửa dòng vật tư này và khai mục Quy Đổi Đơn Vị.'
            );
        }

        $destLocationId = $request->filled('dest_location_id') ? (int) $request->dest_location_id : null;

        if ($destLocationId) {
            $locOk = DB::table('locations')
                ->where('id', $destLocationId)
                ->where('department_id', $departmentId)
                ->where('status_id', 1)
                ->exists();

            if (! $locOk) {
                return $error('Định khu được chọn không thuộc phòng ban bạn!');
            }
        }

        $exportRow = DB::table(self::TABLE)
            ->where('transfer_item_id', $item->id)
            ->where('type', self::TYPE_TRANSFER_OUT)
            ->first();

        if (! $exportRow) {
            return $error('Không tìm thấy phiếu chuyển tương ứng!');
        }

        $receivedAt = now();
        $note = 'Nhận chuyển liên phòng ban từ '.$bDeptName.', mã nguồn '.$sourceImport->code.'.';

        $result = DB::transaction(function () use (
            $item, $sourceImport, $exportRow, $departmentId, $destLocationId, $convertedAmount, $receivedAt, $note
        ) {
            $newCode = MaterialCode::next($departmentId);

            $newImportId = DB::table('material_imports')->insertGetId([
                'code' => $newCode,
                'department_id' => $departmentId,
                'category_id' => (int) $item->category_id,
                'source_export_id' => $exportRow->id,
                'transfer_item_id' => $item->id,
                'amount' => $convertedAmount,
                // Ngày nhập là thời điểm bấm Nhận, hạn dùng giữ nguyên của lô nguồn
                'imported_date' => $receivedAt->format('Y-m-d'),
                'imported_by' => $this->actor(),
                'expired_date' => $sourceImport->expired_date,
                'location_id' => $destLocationId,
                'note' => $note,
                'status_id' => 1,
                // Hàng nhận chuyển liên phòng ban đã được kiểm tra ở lô gốc bên phòng
                // gửi nên vào tồn ngay, không phải qua lại bước Chờ kiểm tra
                'is_checked' => 1,
                'checked_by' => $this->actor(),
                'checked_at' => $receivedAt,
                'created_by' => $this->actor(),
                'created_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]);

            DB::table('material_import_histories')->insert([
                'material_import_id' => $newImportId,
                'action' => 'Thêm mới',
                'code' => $newCode,
                'category_id' => (int) $item->category_id,
                'amount' => $convertedAmount,
                'imported_date' => $receivedAt->format('Y-m-d'),
                'imported_by' => $this->actor(),
                'expired_date' => $sourceImport->expired_date,
                'location_id' => $destLocationId,
                'note' => $note,
                'status_id' => 1,
                'change_note' => $note.' Mã mới của phòng: '.$newCode.'.',
                'created_by' => $this->actor(),
                'created_at' => $receivedAt,
            ]);

            DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $item->id)->update([
                'status' => 'received',
                'dest_location_id' => $destLocationId,
                'new_import_id' => $newImportId,
                'received_by' => $this->actor(),
                'received_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]);

            $this->refreshTransferStatus((int) $item->transfer_request_id, $receivedAt);

            return ['new_import_id' => $newImportId, 'new_code' => $newCode];
        });

        AuditTrialController::log(
            'Nhận chuyển vật tư liên phòng ban',
            'material_imports',
            $result['new_import_id'],
            'NA',
            'Nhận từ phòng '.$bDeptName.', mã nguồn '.$sourceImport->code.' -> mã mới '.$result['new_code']
        );

        $message = 'Đã nhận hàng, mã xuất nhập mới: '.$result['new_code'].'!';

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'transfer'])->with('success', $message);
    }

    /**
     * PHÒNG A TỪ CHỐI NHẬN 1 MỤC ĐÃ ĐƯỢC CẤP PHÁT
     *
     * Hoàn tồn cho B bằng cách khoá dòng material_exports type = transfer_out tương ứng
     * (status_id = 0) - mọi công thức tồn chỉ cộng phiếu xuất status_id = 1 nên lô nguồn
     * coi như chưa từng bị trừ.
     */
    public function transferReceiveReject(Request $request)
    {
        $departmentId = $this->departmentId(); // A

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::TRANSFER_ITEM_TABLE.',id'],
            'return_note' => ['required', 'max:500'],
        ], [
            'return_note.required' => 'Vui lòng nhập lý do từ chối nhận.',
            'return_note.max' => 'Lý do từ chối nhận tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->with('activeTab', 'transfer');
        }

        $item = DB::table(self::TRANSFER_ITEM_TABLE)
            ->where('id', $request->item_id)
            ->where('active', 1)
            ->where('status', 'issued')
            ->first();

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy mục cần từ chối nhận!')->with('activeTab', 'transfer');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $item->transfer_request_id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $transferReq) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị thuộc phòng ban này!')->with('activeTab', 'transfer');
        }

        $exportRow = DB::table(self::TABLE)
            ->where('transfer_item_id', $item->id)
            ->where('type', self::TYPE_TRANSFER_OUT)
            ->first();

        if (! $exportRow) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu chuyển tương ứng!')->with('activeTab', 'transfer');
        }

        $returnedAt = now();
        $returnNote = trim((string) $request->return_note);

        DB::transaction(function () use ($item, $exportRow, $returnedAt, $returnNote) {
            DB::table(self::TABLE)->where('id', $exportRow->id)->update([
                'status_id' => 0,
                'updated_by' => $this->actor(),
                'updated_at' => $returnedAt,
            ]);

            $this->logHistory($exportRow->id, 'Khoá', 'Phòng nhận từ chối nhận: '.$returnNote.' - hoàn tồn kho phòng gửi.');

            DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $item->id)->update([
                'status' => 'returned',
                'return_note' => $returnNote,
                'returned_by' => $this->actor(),
                'returned_at' => $returnedAt,
                'updated_at' => $returnedAt,
            ]);

            $this->refreshTransferStatus((int) $item->transfer_request_id, $returnedAt);
        });

        AuditTrialController::log(
            'Từ chối nhận chuyển vật tư liên phòng ban',
            self::TRANSFER_ITEM_TABLE,
            $item->id,
            'issued',
            'Từ chối nhận, hoàn tồn phiếu '.$exportRow->code.': '.$returnNote
        );

        return redirect()->route('pages.export.materialExport.list', ['tab' => 'transfer'])
            ->with('success', 'Đã từ chối nhận, tồn kho phòng gửi đã được hoàn lại.');
    }

    /* ==========================================================
     |  HÀM DÙNG CHUNG
     ========================================================== */

    /** Thêm các dòng vật tư của một phiếu đề nghị liên phòng ban. */
    private function insertTransferItems(int $listId, Request $request, string $status): void
    {
        foreach ((array) $request->items as $item) {
            DB::table(self::TRANSFER_ITEM_TABLE)->insert([
                'transfer_request_id' => $listId,
                'category_id' => (int) $item['category_id'],
                'requested_amount' => (float) ($item['requested_amount'] ?? 0),
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'note' => $this->nullIfBlank($item['note'] ?? null),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Trạng thái tổng của phiếu đề nghị liên phòng ban, suy từ trạng thái từng mục con.
     *
     * completed chỉ tính khi TẤT CẢ mục đã received - còn mục nào issued (chờ phòng nhận
     * xác nhận) thì dù đã cấp phát hết, phiếu vẫn coi là partial.
     */
    private function refreshTransferStatus(int $transferRequestId, $at): void
    {
        $items = DB::table(self::TRANSFER_ITEM_TABLE)
            ->where('transfer_request_id', $transferRequestId)
            ->where('active', 1)
            ->get();

        $total = $items->count();
        $pending = $items->where('status', 'pending')->count();
        $issued = $items->where('status', 'issued')->count();
        $received = $items->where('status', 'received')->count();

        $status = match (true) {
            $total === 0 || $pending === $total => 'pending',
            $pending === 0 && $issued === 0 => $received > 0 ? 'completed' : 'rejected',
            default => 'partial',
        };

        DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $transferRequestId)->update([
            'status' => $status,
            'updated_by' => $this->actor(),
            'updated_at' => $at,
        ]);
    }

    /**
     * Đề nghị liên phòng ban PHÒNG MÌNH GỬI ĐI (mình là A) và GỬI ĐẾN PHÒNG MÌNH (mình là
     * B), kèm các mục con group theo transfer_request_id. Cùng hình dạng với
     * transferRequestsData() của ChemicalExportController.
     */
    private function transferRequestsData(int $departmentId, Request $request): array
    {
        $base = fn () => DB::table(self::TRANSFER_REQUEST_TABLE)
            ->select(self::TRANSFER_REQUEST_TABLE.'.*')
            ->orderBy(self::TRANSFER_REQUEST_TABLE.'.created_at', 'desc');

        $sentRange = ListRange::of($request, 'tsent_');
        $receivedRange = ListRange::of($request, 'trecv_');

        $sent = $base()
            ->leftJoin('deparments', self::TRANSFER_REQUEST_TABLE.'.to_department_id', '=', 'deparments.id')
            ->addSelect('deparments.name as partner_name', 'deparments.shortName as partner_short')
            ->where(self::TRANSFER_REQUEST_TABLE.'.department_id', $departmentId)
            ->tap(ListRange::dateFilterKeepPending(
                self::TRANSFER_REQUEST_TABLE.'.created_at',
                $sentRange,
                self::TRANSFER_REQUEST_TABLE.'.status',
                self::TRANSFER_PENDING_STATUSES
            ))
            ->paginate(ListRange::perPage($request, 'tsent_'), ['*'], ListRange::pageName('tsent_'))
            ->withQueryString();

        $received = $base()
            ->leftJoin('deparments', self::TRANSFER_REQUEST_TABLE.'.department_id', '=', 'deparments.id')
            ->addSelect('deparments.name as partner_name', 'deparments.shortName as partner_short')
            ->where(self::TRANSFER_REQUEST_TABLE.'.to_department_id', $departmentId)
            ->tap(ListRange::dateFilterKeepPending(
                self::TRANSFER_REQUEST_TABLE.'.created_at',
                $receivedRange,
                self::TRANSFER_REQUEST_TABLE.'.status',
                self::TRANSFER_PENDING_STATUSES
            ))
            ->paginate(ListRange::perPage($request, 'trecv_'), ['*'], ListRange::pageName('trecv_'))
            ->withQueryString();

        $requestIds = $sent->pluck('id')->merge($received->pluck('id'))->unique();

        $items = DB::table(self::TRANSFER_ITEM_TABLE)
            ->leftJoin('material_categories', self::TRANSFER_ITEM_TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('locations', self::TRANSFER_ITEM_TABLE.'.dest_location_id', '=', 'locations.id')
            ->where(self::TRANSFER_ITEM_TABLE.'.active', 1)
            ->whereIn(self::TRANSFER_ITEM_TABLE.'.transfer_request_id', $requestIds)
            ->select(
                self::TRANSFER_ITEM_TABLE.'.*',
                'material_categories.code as category_code',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'locations.code as dest_location_code'
            )
            ->orderBy(self::TRANSFER_ITEM_TABLE.'.id')
            ->get()
            ->groupBy('transfer_request_id');

        /*
        | Huy hiệu trên nút tab: đếm bằng truy vấn riêng chứ không đếm trên $sent /
        | $received nữa - hai danh sách đó giờ chỉ còn một trang.
        */
        $pendingIssue = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('to_department_id', $departmentId)
            ->whereIn('status', ['pending', 'partial'])
            ->count();

        $awaitingReceipt = DB::table(self::TRANSFER_ITEM_TABLE)
            ->join(
                self::TRANSFER_REQUEST_TABLE,
                self::TRANSFER_ITEM_TABLE.'.transfer_request_id',
                '=',
                self::TRANSFER_REQUEST_TABLE.'.id'
            )
            ->where(self::TRANSFER_REQUEST_TABLE.'.department_id', $departmentId)
            ->where(self::TRANSFER_ITEM_TABLE.'.active', 1)
            ->where(self::TRANSFER_ITEM_TABLE.'.status', 'issued')
            ->count();

        return [
            'sent' => $sent,
            'received' => $received,
            'items' => $items,
            'badgeCount' => $pendingIssue + $awaitingReceipt,
            'sentRange' => $sentRange,
            'sentPerPage' => ListRange::perPage($request, 'tsent_'),
            'receivedRange' => $receivedRange,
            'receivedPerPage' => ListRange::perPage($request, 'trecv_'),
        ];
    }

    /**
     * Mã đề nghị liên phòng ban: LPB-<id phòng gửi>-<id phòng nhận>-ddMMyy-<số thứ tự
     * trong ngày>. Dùng id số thay vì shortName (có thể dài như "KTBT-NM2") cho gọn.
     */
    private function nextMaterialTransferCode(int $fromDepartmentId, int $toDepartmentId): string
    {
        // Dùng chung với tab "Danh sách vật tư cần dự trù" của màn Dự Trù Vật Tư
        return \App\Support\MaterialTransferRequest::nextCode($fromDepartmentId, $toDepartmentId);
    }

    /**
     * Vật tư để chọn khi gửi đề nghị liên phòng ban: cả danh mục chung đã duyệt, không
     * giới hạn ở phần phòng mình đã khai - phòng mình đang thiếu nên mới phải đi xin.
     * Đơn vị hiện trên ô chọn là đơn vị PHÒNG MÌNH đã khai (nếu có).
     */
    private function transferCategoryOptions(int $departmentId)
    {
        return DB::table('material_categories')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_categories.id'))
            ->select(
                'material_categories.id',
                'material_categories.code',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'units.short_name as unit_short_name'
            )
            ->where('material_categories.status_id', 1)
            ->where('material_categories.app_status', 'approved')
            ->orderBy('material_names.name', 'asc')
            ->get();
    }

    /** Phòng ban nhận đề nghị chuyển: mọi phòng đang hoạt động, trừ phòng đang đứng. */
    private function departmentOptions(int $departmentId)
    {
        return DB::table('deparments')
            ->select('id', 'name', 'shortName')
            ->where('isActive', 1)
            ->where('id', '<>', $departmentId)
            ->orderBy('name', 'asc')
            ->get();
    }

    private function departmentName($id): string
    {
        return (string) (DB::table('deparments')->where('id', $id)->value('name') ?: '—');
    }

    private function transferRules(int $departmentId): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'to_department_id' => ['required', 'exists:deparments,id', Rule::notIn([$departmentId])],
            'needed_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:material_categories,id'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function transferMessages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề đề nghị.',
            'to_department_id.required' => 'Vui lòng chọn phòng ban nguồn (đang giữ vật tư).',
            'to_department_id.exists' => 'Phòng ban được chọn không tồn tại.',
            'to_department_id.not_in' => 'Không thể tạo đề nghị liên phòng ban gửi đến chính phòng mình.',
            'items.required' => 'Vui lòng thêm ít nhất một vật tư đề nghị.',
            'items.min' => 'Vui lòng thêm ít nhất một vật tư đề nghị.',
            'items.*.category_id.required' => 'Vui lòng chọn vật tư.',
            'items.*.category_id.exists' => 'Vật tư được chọn không tồn tại.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ];
    }

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
                // Tồn của danh mục tại đúng lúc dòng này được thêm - chụp một lần từ trình
                // duyệt (dòng cũ giữ nguyên giá trị cũ, dòng mới/mới đổi danh mục lấy giá trị
                // hiện tại), không tính lại ở đây để tránh đổi ngược lịch sử khi sửa đề nghị.
                'stock_at_request' => isset($item['stock_at_request']) && $item['stock_at_request'] !== ''
                    ? (float) $item['stock_at_request']
                    : null,
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
     * Phiếu sử dụng không đi qua đây: kho sinh sẵn lúc cấp phát (issueStore).
     */
    private function resolveUseTarget($validator, Request $request, int $departmentId): array
    {
        if (! $request->filled('import_id')) {
            $validator->errors()->add('import_id', 'Vui lòng chọn mã xuất nhập cần loại bỏ.');

            return [null, null];
        }

        $import = DB::table('material_imports')->where('id', $request->import_id)->where('department_id', $departmentId)->where('status_id', 1)->first();

        if (! trim((string) $request->reason)) {
            $validator->errors()->add('reason', 'Vui lòng nhập lý do loại bỏ.');
        }

        if (! $import) {
            $validator->errors()->add('import_id', 'Không tìm thấy mã xuất nhập trong kho phòng ban này.');

            return [null, null];
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

        return [$import, null];
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

    /* ==========================================================
     |  SOẠN VẬT TƯ CẤP PHÁT
     ========================================================== */

    /**
     * Gom mọi dòng đề nghị ĐÃ DUYỆT mà kho chưa cấp đủ, cộng theo TỪNG VẬT TƯ chứ không
     * theo phiếu - người giữ kho đi lấy hàng theo vật tư và vị trí, không đi theo phiếu.
     *
     * Mỗi vật tư kèm sẵn: tổng còn phải cấp, tồn còn hứa được, kế hoạch chia lô (đúng
     * thứ tự nên xuất của App\Support\MaterialPicking) và danh sách phiếu đang chờ.
     *
     * @param  \Illuminate\Support\Collection  $lotsByCategory  Lô đã nạp ở index(): category_id => lô
     * @return array{groups: \Illuminate\Support\Collection, count: int, shortageCount: int}
     */
    private function preparationData(int $departmentId, $lotsByCategory): array
    {
        $rows = DB::table(self::REQ_ITEM)
            ->join(self::REQ_LIST, self::REQ_ITEM.'.request_list_id', '=', self::REQ_LIST.'.id')
            ->leftJoin('material_categories', self::REQ_ITEM.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->select(
                self::REQ_ITEM.'.id',
                self::REQ_ITEM.'.category_id',
                self::REQ_ITEM.'.material_name',
                self::REQ_ITEM.'.technical_specification as item_specification',
                self::REQ_ITEM.'.requested_amount',
                self::REQ_ITEM.'.requested_unit',
                self::REQ_ITEM.'.issued_amount',
                self::REQ_ITEM.'.issued_unit',
                self::REQ_ITEM.'.purpose',
                self::REQ_ITEM.'.note as item_note',
                self::REQ_ITEM.'.status as item_status',
                self::REQ_ITEM.'.issued_by',
                self::REQ_ITEM.'.issued_at',
                self::REQ_LIST.'.id as request_list_id',
                self::REQ_LIST.'.code as request_code',
                self::REQ_LIST.'.name as request_name',
                self::REQ_LIST.'.note as request_note',
                self::REQ_LIST.'.type as request_type',
                self::REQ_LIST.'.needed_date',
                self::REQ_LIST.'.created_by as request_created_by',
                self::REQ_LIST.'.created_at as request_created_at',
                self::REQ_LIST.'.submitted_at as request_submitted_at',
                self::REQ_LIST.'.issue_status as request_issue_status',
                'material_names.name as category_material_name',
                'material_categories.code as category_code',
                'material_categories.technical_specification'
            )
            ->where(self::REQ_LIST.'.department_id', $departmentId)
            ->where(self::REQ_LIST.'.app_status', 'approved')
            ->whereIn(self::REQ_LIST.'.issue_status', self::REQ_PENDING_ISSUE_STATUSES)
            ->where(self::REQ_ITEM.'.active', 1)
            ->whereIn(self::REQ_ITEM.'.status', ['pending', 'partial', 'issued'])
            ->orderBy(self::REQ_LIST.'.needed_date', 'asc')
            ->orderBy(self::REQ_LIST.'.id', 'asc')
            ->get();

        $listIds = $rows->pluck('request_list_id')->unique()->values();

        // Ngày phiếu được ký xong = lần ký cuối cùng; phiếu không khai bước ký nào thì không có
        $approvedAt = DB::table(self::REQ_SIGN)
            ->select('request_list_id', DB::raw('MAX(signed_at) as signed_at'))
            ->whereIn('request_list_id', $listIds)
            ->where('active', 1)
            ->where('status', 'signed')
            ->groupBy('request_list_id')
            ->pluck('signed_at', 'request_list_id');

        // Tổng số mục của cả phiếu - để kho biết mục đang soạn nằm trong phiếu mấy mục
        $itemTotals = DB::table(self::REQ_ITEM)
            ->select('request_list_id', DB::raw('COUNT(*) as items'))
            ->whereIn('request_list_id', $listIds)
            ->where('active', 1)
            ->groupBy('request_list_id')
            ->pluck('items', 'request_list_id');

        $groups = [];

        foreach ($rows as $row) {
            // Dòng đã cấp đủ thì không còn gì để soạn
            $left = round((float) $row->requested_amount - (float) $row->issued_amount, 4);

            if ($left <= self::EPSILON) {
                continue;
            }

            // Vật tư ngoài danh mục không có category_id nên gom theo tên người lập tự khai
            $key = $row->category_id
                ? 'c'.$row->category_id
                : 'n'.mb_strtolower(trim((string) $row->material_name));

            $unit = $row->issued_unit ?: $row->requested_unit;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'category_id' => $row->category_id ? (int) $row->category_id : null,
                    'name' => $row->category_id ? $row->category_material_name : $row->material_name,
                    'category_code' => $row->category_code,
                    'specification' => $row->technical_specification ?: $row->item_specification,
                    'unit' => $unit,
                    'units' => [],
                    'needed' => 0.0,
                    'issued' => 0.0,
                    'demands' => [],
                    'earliest_needed' => null,
                ];
            }

            $groups[$key]['needed'] += $left;
            $groups[$key]['issued'] += (float) $row->issued_amount;

            if ($unit) {
                $groups[$key]['units'][$unit] = true;
            }

            if ($row->needed_date && (! $groups[$key]['earliest_needed'] || $row->needed_date < $groups[$key]['earliest_needed'])) {
                $groups[$key]['earliest_needed'] = $row->needed_date;
            }

            $groups[$key]['demands'][] = [
                'item_id' => (int) $row->id,
                'request_list_id' => (int) $row->request_list_id,
                'request_code' => $row->request_code,
                'request_name' => $row->request_name,
                'request_note' => $row->request_note,
                'request_type' => $row->request_type,
                'needed_date' => $row->needed_date,
                'created_by' => $row->request_created_by,
                'created_at' => $row->request_created_at,
                'submitted_at' => $row->request_submitted_at,
                'approved_at' => $approvedAt[$row->request_list_id] ?? null,
                'issue_status' => $row->request_issue_status,
                'item_total' => (int) ($itemTotals[$row->request_list_id] ?? 0),
                'purpose' => $row->purpose,
                'item_note' => $row->item_note,
                'status' => $row->item_status,
                'issued_by' => $row->issued_by,
                'issued_at' => $row->issued_at,
                'requested' => (float) $row->requested_amount,
                'issued' => (float) $row->issued_amount,
                'left' => $left,
                'unit' => $unit,
            ];
        }

        $shortageCount = 0;

        foreach ($groups as $key => $group) {
            $lots = $group['category_id']
                ? collect($lotsByCategory->get($group['category_id'], collect()))
                : collect();

            // Kế hoạch chia lô cho TỔNG số còn phải cấp của cả nhóm - soạn một lần cho mọi phiếu
            $plan = MaterialPicking::planFrom($lots, $group['needed']);

            $groups[$key]['plan'] = $plan['lines'];
            $groups[$key]['shortage'] = (float) $plan['shortage'];
            $groups[$key]['available'] = (float) $lots->where('suggestable', true)->sum('available');
            $groups[$key]['remaining'] = (float) $lots->sum('remaining');
            $groups[$key]['lot_count'] = count($plan['lines']);
            $groups[$key]['unit'] = $group['unit'] ?: ($lots->first()->unit_short_name ?? '');
            $groups[$key]['unit_mixed'] = count($group['units']) > 1;

            if ($plan['shortage'] > self::EPSILON) {
                $shortageCount++;
            }
        }

        // Cần trước xếp trước; phiếu không khai ngày mong muốn xuống cuối, rồi theo tên vật tư
        $groups = collect($groups)
            ->sortBy(fn ($group) => [$group['earliest_needed'] ?: '9999-12-31', mb_strtolower((string) $group['name'])])
            ->values();

        return [
            'groups' => $groups,
            'count' => $groups->count(),
            'shortageCount' => $shortageCount,
        ];
    }

    /**
     * PHIẾU SOẠN VẬT TƯ - trang A4 để in, mỗi dòng là MỘT LÔ ở MỘT vị trí.
     *
     * Thứ tự dòng do App\Support\MaterialPicking::sequence() quyết định (kho -> kệ ->
     * cột -> tầng) để nhân viên đi một vòng là lấy đủ, không phải quay lại kệ cũ.
     */
    public function prepPrint()
    {
        $departmentId = $this->departmentId();
        $prep = $this->preparationData($departmentId, $this->importOptions($departmentId)->groupBy('category_id'));

        $lines = [];

        // Nhu cầu của từng phiếu đề nghị, để tờ in nói rõ lô đang lấy là lấy cho phiếu nào
        $requests = [];

        foreach ($prep['groups'] as $group) {
            foreach ($group['plan'] as $line) {
                $lines[] = $line + [
                    'material_name' => $group['name'],
                    'category_code' => $group['category_code'],
                    'specification' => $group['specification'],
                    'requests' => collect($group['demands'])->map(fn ($demand) => [
                        'code' => $demand['request_code'],
                        'left' => $demand['left'],
                        'unit' => $demand['unit'] ?: $group['unit'],
                    ])->values()->all(),
                ];
            }

            foreach ($group['demands'] as $demand) {
                $requests[$demand['request_code']]['info'] ??= $demand;
                $requests[$demand['request_code']]['items'][] = $demand + [
                    'material_name' => $group['name'],
                    'category_code' => $group['category_code'],
                    'display_unit' => $demand['unit'] ?: $group['unit'],
                ];
            }
        }

        $lines = MaterialPicking::sequence($lines);

        // Phiếu cần trước xếp trước, phiếu không khai ngày mong muốn xuống cuối
        $requests = collect($requests)
            ->sortBy(fn ($request) => [$request['info']['needed_date'] ?: '9999-12-31', $request['info']['request_code']])
            ->values();

        return view('pages.export.MaterialExport.prepPrint', [
            'prepGroups' => $prep['groups'],
            'prepLines' => $lines,
            'prepRequests' => $requests,
            'department' => DB::table('deparments')->where('id', $departmentId)->first(),
        ]);
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
            'name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'needed_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['nullable'],
            'items.*.material_name' => ['nullable', 'string', 'max:255'],
            'items.*.technical_specification' => ['nullable', 'string', 'max:255'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.stock_at_request' => ['nullable', 'numeric'],
            'items.*.purpose' => ['nullable', 'string', 'max:500'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function requestMessages(): array
    {
        return [
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
