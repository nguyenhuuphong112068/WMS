<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentVpp;
use App\Support\ListRange;
use App\Support\VppClassification;
use App\Support\VppPicking;
use App\Support\VppTransferRequest;
use App\Support\VppTransferSignFlow;
use App\Support\VppStore;
use App\Support\VppWatchlist;
use App\Support\TitleSuggestions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * SỬ DỤNG - SỬ DỤNG VPP
 *
 * Từ 12/11/2026 VPP dùng cơ chế "SỬ DỤNG - GHI SỔ" như Hoá Chất: người dùng bấm Sử dụng VPP,
 * quét mã / chọn lô từ tồn kho của phòng, nhập số lượng + mục đích rồi Lưu là ghi thẳng một dòng
 * vpp_exports (type = export) và TRỪ TỒN NGAY - không qua đề nghị cấp phát nội bộ, không trình
 * ký, không có Phiếu Tạm (khác Hoá Chất). Phiếu cũ cấp phát theo đề nghị (request_item_id) vẫn
 * hiện trên sổ, mục đích đọc từ dòng đề nghị khi phiếu chưa có purpose.
 *
 *   LOẠI BỎ (type = cancel) hàng hỏng / hết hạn đi quy trình 3 bước riêng ở
 *   VppQuarantineController: Cách ly chờ quyết định -> Loại bỏ -> Huỷ. Phiếu loại bỏ
 *   sinh ra ngay lúc cách ly (quarantine_id khác null) và không sửa / khoá được ở đây.
 *
 *   CẤP PHÁT LIÊN PHÒNG BAN (type = transfer_out) là hàng chuyển sang phòng khác chứ không
 *   phải hàng đã dùng - xem khối "ĐỀ NGHỊ CHUYỂN VPP LIÊN PHÒNG BAN" bên dưới. Đề nghị gửi
 *   kho VPP Hành Chính (tab "VPP đến HC") dùng chung bộ bảng đó.
 *
 * Trạng thái tồn dùng công thức: nhập + cân đối - đã xuất (kể cả loại bỏ và chuyển đi).
 */
class VppExportController extends Controller
{
    use VerifiesSignature;

    private const TABLE = 'vpp_exports';

    private const HISTORY_TABLE = 'vpp_export_histories';

    /** Đề nghị cấp phát nội bộ CŨ (đã bỏ 12/11/2026) - chỉ còn đọc để hiện phiếu cũ trên sổ. */
    private const REQ_LIST = 'vpp_request_lists';

    private const REQ_ITEM = 'vpp_request_items';

    private const TRANSFER_REQUEST_TABLE = 'vpp_transfer_requests';

    private const TRANSFER_ITEM_TABLE = 'vpp_transfer_items';

    /**
     * Trạng thái đề nghị chuyển liên phòng ban còn DỞ DANG - bộ lọc khoảng ngày luôn
     * giữ lại các đề nghị này dù đã ngoài khoảng lọc.
     */
    private const TRANSFER_PENDING_STATUSES = ['draft', 'pending_sign', 'pending', 'partial'];

    /** Trạng thái phiếu liên phòng ban CHƯA tới phòng nhận - ẩn khỏi danh sách "gửi đến". */
    private const TRANSFER_UNSENT_STATUSES = ['draft', 'pending_sign', 'canceled'];

    /** Phiếu liên phòng ban phòng nhận còn phải cấp phát: chưa cấp / cấp một phần. */
    private const TRANSFER_ISSUE_STATUSES = ['pending', 'partial'];

    /** Ghi phiếu Sử dụng VPP ở tab Sử Dụng VPP (trừ tồn ngay). */
    public const ISSUE_PERMISSION = 'export_vpp_issue';

    /** Được đổi / thêm mã xuất nhập (lô) khác với đề xuất FEFO/FIFO khi cấp phát liên phòng ban. */
    public const LOT_ADJUST_PERMISSION = 'export_vpp_issue_lot_adjust';

    /** Được sửa SỐ LƯỢNG cấp phát khác số hệ thống đề xuất (cấp một phần / cấp vượt). */
    public const AMOUNT_ADJUST_PERMISSION = 'export_vpp_issue_amount_adjust';

    /** Được điều chỉnh / khoá phiếu sử dụng đã ghi ở tab Sổ sử dụng VPP. */
    public const UPDATE_PERMISSION = 'export_vpp_update';

    private const LABEL = 'phiếu sử dụng VPP';

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
        'purpose' => 'Mục đích',
    ];

    /* ==========================================================
     |  MÀN HÌNH CHÍNH
     ========================================================== */

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        // Sổ sử dụng chỉ lấy đúng một trang trong khoảng ngày đang lọc (mặc định 30 ngày gần nhất)
        $bookRange = ListRange::of($request, 'book_');
        $bookKeyword = ListRange::keyword($request, 'book_');
        $bookPerPage = ListRange::perPage($request, 'book_');

        $exports = DB::table(self::TABLE)
            ->leftJoin('vpp_imports', self::TABLE.'.import_id', '=', 'vpp_imports.id')
            ->leftJoin('vpp_categories', 'vpp_imports.category_id', '=', 'vpp_categories.id')
            ->leftJoin('material_names', 'vpp_categories.material_names_id', '=', 'material_names.id')
            // Phiếu cũ (trước 12/11/2026) cấp phát theo đề nghị nội bộ: mục đích + mã đề nghị nằm ở dòng đề nghị
            ->leftJoin(self::REQ_ITEM, self::TABLE.'.request_item_id', '=', self::REQ_ITEM.'.id')
            ->leftJoin(self::REQ_LIST, self::REQ_ITEM.'.request_list_id', '=', self::REQ_LIST.'.id')
            ->leftJoin(self::TRANSFER_ITEM_TABLE, self::TABLE.'.transfer_item_id', '=', self::TRANSFER_ITEM_TABLE.'.id')
            ->leftJoin(self::TRANSFER_REQUEST_TABLE, self::TRANSFER_ITEM_TABLE.'.transfer_request_id', '=', self::TRANSFER_REQUEST_TABLE.'.id')
            ->tap(fn ($query) => DepartmentVpp::joinUnit($query, $departmentId, 'vpp_imports.category_id'))
            // Phòng ban nhận, chỉ có ở phiếu cấp phát liên phòng ban (type = transfer_out)
            ->leftJoin('deparments', self::TABLE.'.to_department_id', '=', 'deparments.id')
            // Dòng xuất sinh từ phiếu cách ly: trạng thái phiếu quyết định nhãn Cách ly / Loại bỏ / Trả về kho
            ->leftJoin(VppPicking::QUARANTINE_TABLE.' as quarantines', self::TABLE.'.quarantine_id', '=', 'quarantines.id')
            ->select(
                self::TABLE.'.*',
                'quarantines.app_status as quarantine_status',
                'material_names.name as material_name',
                'vpp_categories.code as category_code',
                'units.short_name as unit_short_name',
                'deparments.name as to_department_name',
                'deparments.shortName as to_department_short',
                DB::raw('COALESCE('.self::TABLE.'.purpose, '.self::REQ_ITEM.'.purpose) as purpose'),
                // Tình trạng nhận hàng của phòng đề nghị (chỉ phiếu transfer_out): issued = chưa nhận
                self::TRANSFER_ITEM_TABLE.'.status as transfer_item_status',
                self::TRANSFER_ITEM_TABLE.'.received_by as transfer_received_by',
                self::TRANSFER_ITEM_TABLE.'.received_at as transfer_received_at',
                self::TRANSFER_ITEM_TABLE.'.return_note as transfer_return_note',
                DB::raw('COALESCE('.self::REQ_LIST.'.code, '.self::TRANSFER_REQUEST_TABLE.'.code) as request_code')
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->tap(ListRange::dateFilter(self::TABLE.'.created_at', $bookRange))
            ->tap(ListRange::search([
                self::TABLE.'.code',
                self::TRANSFER_REQUEST_TABLE.'.code',
                'vpp_imports.code',
                'vpp_categories.code',
                'material_names.name',
                self::TABLE.'.used_by',
                self::TABLE.'.purpose',
            ], $bookKeyword))
            ->orderBy(self::TABLE.'.created_at', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->paginate($bookPerPage, ['*'], ListRange::pageName('book_'))
            ->withQueryString();

        // Tab "Sử Dụng VPP": các lô của phòng kèm tồn còn - đổ vào bảng chọn lô của modal Sử dụng VPP
        $availableImports = $this->importOptions($departmentId);
        $useLots = $availableImports
            ->filter(fn ($lot) => $lot->remaining > self::EPSILON)
            ->values();

        // Phiếu sử dụng hôm nay của phòng - bảng ngay dưới nút Sử dụng VPP
        $todayUses = DB::table(self::TABLE)
            ->leftJoin('vpp_imports', self::TABLE.'.import_id', '=', 'vpp_imports.id')
            ->leftJoin('vpp_categories', 'vpp_imports.category_id', '=', 'vpp_categories.id')
            ->leftJoin('material_names', 'vpp_categories.material_names_id', '=', 'material_names.id')
            ->tap(fn ($query) => DepartmentVpp::joinUnit($query, $departmentId, 'vpp_imports.category_id'))
            ->select(
                self::TABLE.'.id',
                self::TABLE.'.code',
                self::TABLE.'.amount',
                self::TABLE.'.purpose',
                self::TABLE.'.used_by',
                self::TABLE.'.status_id',
                self::TABLE.'.created_at',
                'material_names.name as material_name',
                'vpp_categories.code as category_code',
                'units.short_name as unit_short_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.type', 'export')
            ->whereNull(self::TABLE.'.quarantine_id')
            ->whereDate(self::TABLE.'.created_at', now()->toDateString())
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        // Tab "Cách ly - Loại Bỏ - Huỷ": VPP sắp hết hạn -> cách ly -> loại bỏ -> huỷ
        $quarantine = VppQuarantineController::paneData($departmentId, $request);

        session()->put(['title' => 'SỬ DỤNG - SỬ DỤNG VPP']);

        // Đề nghị chuyển VPP LIÊN PHÒNG BAN: đã gửi đi (mình là A) / cần cấp phát (mình là B).
        // Đề nghị gửi tới phòng Hành Chính tách riêng thành tab "VPP đến HC" (App\Support\VppStore)
        $transfer = $this->transferRequestsData($departmentId, $request, 'transfer');
        $vpp = $this->transferRequestsData($departmentId, $request, VppStore::TAB);

        // VPP phòng mình đang dùng - cảnh báo ngay trên phiếu khi có mục "chờ nhận" mà phòng chưa có
        $declaredCategoryIds = DB::table(DepartmentVpp::TABLE)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->pluck('category_id')
            ->all();

        /*
        | Tab nào đang mở: ?tab= trên URL là chính; các action dùng redirect()->back() (không đổi
        | URL) nên tự flash activeTab qua session. Link cũ trỏ các tab đề nghị nội bộ / chờ cấp
        | phát (đã bỏ 12/11/2026) rơi về tab Sử Dụng VPP.
        */
        $tabs = ['use', 'book', 'quarantine', 'transfer', VppStore::TAB];
        $flashTab = session('activeTab');
        $activeTab = in_array($request->query('tab'), $tabs, true)
            ? $request->query('tab')
            : (in_array($flashTab, $tabs, true) ? $flashTab : 'use');

        return view('pages.export.VppExport.list', [
            'exports' => $exports,
            'useLots' => $useLots,
            // Bảng chọn lô khi cấp phát đề nghị liên phòng ban (transferImportPickerModal)
            'availableImports' => $availableImports,
            // Ô đơn vị ở dòng đề nghị liên phòng ban / VPP đến HC
            'units' => $this->unitOptions(),
            'todayUses' => $todayUses,
            'overIssuePercent' => (int) round(self::OVER_ISSUE_RATIO * 100),
            // ---- Tab "Cách ly - Loại Bỏ - Huỷ" ----
            'quarantineRows' => $quarantine['rows'],
            'quarantineRange' => $quarantine['range'],
            'quarantineKeyword' => $quarantine['keyword'],
            'quarantinePerPage' => $quarantine['perPage'],
            'quarantineStatus' => $quarantine['status'],
            'quarantineCounts' => $quarantine['counts'],
            'quarantineBadge' => $quarantine['badge'],
            'quarantineLots' => $quarantine['lots'],
            'quarantineExpiring' => $quarantine['expiring'],
            'quarantineExpiringDays' => $quarantine['expiringDays'],
            'quarantinePendingLocations' => $quarantine['pendingLocations'],
            'quarantineRejectedLocations' => $quarantine['rejectedLocations'],
            'quarantineReturnLocations' => $quarantine['returnLocations'],
            'adjustCounts' => $this->adjustCounts($departmentId),
            // ---- Tab "Đề nghị chuyển liên phòng ban" ----
            'transferSent' => $transfer['sent'],
            'transferReceived' => $transfer['received'],
            // Dòng VPP của cả đề nghị liên phòng ban lẫn đề nghị VPP - modal chi tiết / điều chỉnh dùng chung
            'transferItems' => $transfer['items']->union($vpp['items']),
            // Cột "Tồn hiện hành" / "Tồn khả dụng" của phòng cấp phát ở modal chi tiết - xem transferSourceStock()
            'transferSourceStock' => $this->transferSourceStock($transfer, $vpp),
            'transferBadgeCount' => $transfer['badgeCount'],
            'transferToSign' => $transfer['toSign'],
            // Bước ký của cả đề nghị liên phòng ban lẫn VPP
            'transferSigns' => $transfer['signs']->union($vpp['signs']),
            'transferSentPerPage' => $transfer['sentPerPage'],
            'transferSentDone' => $transfer['sentDone'],
            'transferSentDoneRange' => $transfer['sentDoneRange'],
            'transferSentDonePerPage' => $transfer['sentDonePerPage'],
            'transferReceivedPerPage' => $transfer['receivedPerPage'],
            'transferDone' => $transfer['done'],
            'transferDoneRange' => $transfer['doneRange'],
            'transferDonePerPage' => $transfer['donePerPage'],
            // ---- Tab "VPP đến HC" (đề nghị gửi tới kho VPP của phòng Hành Chính) ----
            'vppSent' => $vpp['sent'],
            'vppReceived' => $vpp['received'],
            'vppBadgeCount' => $vpp['badgeCount'],
            'vppToSign' => $vpp['toSign'],
            'vppSentPerPage' => $vpp['sentPerPage'],
            'vppSentDone' => $vpp['sentDone'],
            'vppSentDoneRange' => $vpp['sentDoneRange'],
            'vppSentDonePerPage' => $vpp['sentDonePerPage'],
            'vppReceivedPerPage' => $vpp['receivedPerPage'],
            'vppDone' => $vpp['done'],
            'vppDoneRange' => $vpp['doneRange'],
            'vppDonePerPage' => $vpp['donePerPage'],
            'vppTarget' => VppStore::targetDepartment($departmentId),
            'vppIsAdminDepartment' => VppStore::isVppDepartment($departmentId),
            'vppDepartmentIds' => VppStore::departmentIds(),
            'vppWatchlistItems' => VppWatchlist::forVpp($departmentId),
            'vppSubTab' => in_array($request->query('sub', session('vppSubTab')), ['requests', 'watchlist'], true)
                ? $request->query('sub', session('vppSubTab'))
                : 'requests',
            'bookRange' => $bookRange,
            'bookKeyword' => $bookKeyword,
            'bookPerPage' => $bookPerPage,
            'transferCategories' => $transferCategories = $this->transferCategoryOptions($departmentId),
            'transferTitles' => $this->transferTitleOptions($departmentId),
            // Đề nghị VPP chỉ chọn VPP Hành Chánh mua (VppStore)
            'vppCategories' => $transferCategories->filter(fn ($c) => VppClassification::isAdminPurchased($c->purchasing_department))->values(),
            'transferDepartments' => $this->departmentOptions($departmentId),
            'transferOwnLocations' => DepartmentVpp::locationOptions($departmentId),
            'declaredCategoryIds' => $declaredCategoryIds,
            'currentDepartmentId' => $departmentId,
            'activeTab' => $activeTab,
        ]);
    }

    /* ==========================================================
     |  SỬ DỤNG VPP - GHI SỔ TRỰC TIẾP
     |
     |  Giống Sử Dụng Hoá Chất nhưng KHÔNG có Phiếu Tạm: chọn nhiều lô (quét mã / chọn từ tồn
     |  kho của phòng), mỗi dòng nhập Số lượng + Mục đích rồi Lưu là ghi vào vpp_exports
     |  (type = export) và trừ tồn ngay. Có dòng nào lỗi thì không ghi dòng nào.
     ========================================================== */

    public function store(Request $request)
    {
        if (! user_can(self::ISSUE_PERMISSION)) {
            return redirect()->back()->with('error', 'Bạn không có quyền sử dụng VPP!')->with('activeTab', 'use');
        }

        $departmentId = $this->departmentId();

        $picked = [];
        foreach ((array) $request->input('items', []) as $importId => $row) {
            $picked[(int) $importId] = (array) $row;
        }

        if (! $picked) {
            return redirect()->back()->with('error', 'Vui lòng chọn ít nhất một VPP từ tồn kho của phòng.')->with('activeTab', 'use');
        }

        try {
            $count = DB::transaction(function () use ($picked, $departmentId) {
                $validator = Validator::make([], []);
                $rows = [];

                foreach ($picked as $importId => $row) {
                    // Khoá dòng phiếu nhập suốt lượt ghi: hai người cùng dùng một lô phải lần
                    // lượt qua kiểm tra tồn, không cùng lúc ghi vượt tồn.
                    $import = DB::table('vpp_imports')
                        ->where('id', $importId)
                        ->where('department_id', $departmentId)
                        ->where('status_id', 1)
                        ->where('is_checked', 1)
                        ->lockForUpdate()
                        ->first();

                    $amount = $row['amount'] ?? null;
                    $purpose = $this->nullIfBlank($row['purpose'] ?? null);
                    $key = "items.$importId";

                    if (! $import) {
                        $validator->errors()->add($key.'.import_id', 'Mã xuất nhập không còn trong kho của phòng (đã khoá hoặc chưa kiểm tra nhập).');

                        continue;
                    }

                    if ($import->expired_date && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt(now()->startOfDay())) {
                        $validator->errors()->add($key.'.import_id', 'Mã xuất nhập '.$import->code.' đã hết hạn, không sử dụng được.');

                        continue;
                    }

                    if (! is_numeric($amount) || (float) $amount <= 0) {
                        $validator->errors()->add($key.'.amount', 'Số lượng dùng của mã '.$import->code.' phải lớn hơn 0.');

                        continue;
                    }

                    if ($purpose !== null && mb_strlen($purpose) > 500) {
                        $validator->errors()->add($key.'.purpose', 'Mục đích của mã '.$import->code.' tối đa 500 ký tự.');

                        continue;
                    }

                    $limit = $this->available($import) * (1 + self::OVER_ISSUE_RATIO);
                    if ((float) $amount > $limit + self::EPSILON) {
                        $validator->errors()->add($key.'.amount', 'Mã xuất nhập '.$import->code.' chỉ còn cho phép dùng tối đa '.$this->number($limit).'.');

                        continue;
                    }

                    $rows[] = [$import, (float) $amount, $purpose];
                }

                if ($validator->errors()->any()) {
                    throw new \Illuminate\Validation\ValidationException($validator);
                }

                foreach ($rows as [$import, $amount, $purpose]) {
                    $id = DB::table(self::TABLE)->insertGetId([
                        'code' => $import->code,
                        'import_id' => (int) $import->id,
                        'department_id' => $departmentId,
                        'amount' => $amount,
                        'purpose' => $purpose,
                        'type' => 'export',
                        // Người sử dụng luôn là người đang đăng nhập, thời điểm là lúc bấm Lưu
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
                        'Sử dụng VPP, mã xuất nhập: '.$import->code.', số lượng: '.$this->number($amount)
                    );
                }

                return count($rows);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator, 'createErrors')->withInput()->with('activeTab', 'use');
        }

        return redirect()->back()->with('success', 'Đã ghi nhận '.$count.' dòng sử dụng VPP, tồn kho đã trừ!')->with('activeTab', 'use');
    }

    /* ==========================================================
     |  ĐIỀU CHỈNH PHIẾU TRÊN SỔ SỬ DỤNG
     ========================================================== */

    public function update(Request $request)
    {
        // Phiếu đã ghi là đã trừ tồn: chặn ở server, không chỉ ẩn nút ở giao diện
        if (! user_can(self::UPDATE_PERMISSION)) {
            return redirect()->back()->with('error', 'Bạn không có quyền điều chỉnh sổ sử dụng VPP!');
        }

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

        if ($guard = $this->quarantineGuard($current, 'sửa')) {
            return $guard;
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'product_name' => ['nullable', 'max:255'],
            'purpose' => ['nullable', 'max:500'],
            'reason' => ['nullable', 'max:500'],
            'adjust_reason' => ['nullable', 'max:500'],
        ], $this->messages());

        $import = DB::table('vpp_imports')->where('id', $current->import_id)->first();

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

        // Phiếu cũ cấp phát theo đề nghị chưa có purpose: so với mục đích đọc từ dòng đề nghị
        $oldPurpose = $current->purpose ?? ($current->request_item_id
            ? DB::table(self::REQ_ITEM)->where('id', $current->request_item_id)->value('purpose')
            : null);
        $payload['purpose'] = $current->type === 'export' ? $this->nullIfBlank($request->purpose) : $oldPurpose;

        $note = $this->changeNote((object) array_merge((array) $current, ['purpose' => $oldPurpose]), $payload, $request->adjust_reason);

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
        if (! user_can(self::UPDATE_PERMISSION)) {
            return redirect()->back()->with('error', 'Bạn không có quyền điều chỉnh sổ sử dụng VPP!');
        }

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

        if ($guard = $this->quarantineGuard($current, 'khoá / mở khoá')) {
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
            ->join(self::TABLE, self::HISTORY_TABLE.'.vpp_export_id', '=', self::TABLE.'.id')
            ->leftJoin('vpp_imports', self::HISTORY_TABLE.'.import_id', '=', 'vpp_imports.id')
            ->leftJoin('vpp_categories', 'vpp_imports.category_id', '=', 'vpp_categories.id')
            ->leftJoin('material_names', 'vpp_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin(self::REQ_ITEM, self::TABLE.'.request_item_id', '=', self::REQ_ITEM.'.id')
            ->leftJoin(self::REQ_LIST, self::REQ_ITEM.'.request_list_id', '=', self::REQ_LIST.'.id')
            ->leftJoin(self::TRANSFER_ITEM_TABLE, self::TABLE.'.transfer_item_id', '=', self::TRANSFER_ITEM_TABLE.'.id')
            ->leftJoin(self::TRANSFER_REQUEST_TABLE, self::TRANSFER_ITEM_TABLE.'.transfer_request_id', '=', self::TRANSFER_REQUEST_TABLE.'.id')
            ->leftJoin('deparments', self::TABLE.'.to_department_id', '=', 'deparments.id')
            ->tap(fn ($query) => DepartmentVpp::joinUnit($query, $this->departmentId(), 'vpp_imports.category_id'))
            ->select(
                self::HISTORY_TABLE.'.*',
                self::TABLE.'.created_at as export_created_at',
                'material_names.name as material_name',
                'vpp_categories.code as category_code',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'deparments.name as to_department_name',
                DB::raw('COALESCE('.self::REQ_LIST.'.code, '.self::TRANSFER_REQUEST_TABLE.'.code) as request_code')
            )
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->where(self::HISTORY_TABLE.'.vpp_export_id', $request->id)
            ->orderBy(self::HISTORY_TABLE.'.id', 'desc')
            ->get();

        // Đúng các cột của bảng Sổ Cấp Phát (bỏ STT / Thao Tác), mỗi lần thay đổi là một dòng
        return response()->json([
            'columns' => [
                ['title' => 'Mã Xuất Nhập'],
                ['title' => 'Mã Phiếu Đề Nghị'],
                ['title' => 'VPP'],
                ['title' => 'Số Lượng', 'align' => 'right'],
                ['title' => 'Loại', 'align' => 'center'],
                ['title' => 'Thời Gian', 'align' => 'center'],
                ['title' => 'Mục Đích'],
                ['title' => 'Người Thực Hiện'],
            ],
            'rows' => $rows->map(function ($row) {
                $unit = $row->unit_short_name ?: $row->unit_name;

                if ($row->type === self::TYPE_TRANSFER_OUT) {
                    $purpose = 'Cấp phát liên phòng ban đến '.($row->to_department_name ?: '—');
                } elseif ($row->type === 'cancel') {
                    $purpose = $row->reason;
                } else {
                    $purpose = $row->purpose;
                }

                return [
                    'action' => $row->action,
                    'change_note' => $row->change_note,
                    'created_by' => $row->created_by ?: 'NA',
                    'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                    'cells' => [
                        ['text' => $row->code ?: '—', 'sub' => $row->category_code, 'strong' => true, 'nowrap' => true],
                        ['text' => $row->request_code ?: '—', 'strong' => (bool) $row->request_code, 'nowrap' => true],
                        ['text' => $row->material_name ?: '—', 'strong' => true],
                        ['text' => $row->amount !== null ? $this->number((float) $row->amount) : '—', 'sub' => $unit, 'inline' => true],
                        [
                            'text' => $row->type === self::TYPE_TRANSFER_OUT ? 'Chuyển đi' : (self::TYPES[$row->type] ?? ($row->type ?: '—')),
                            'badge' => $row->type === 'cancel' ? 'danger' : ($row->type === self::TYPE_TRANSFER_OUT ? 'primary' : 'success'),
                            'sub' => $row->status_id ? null : 'Đã khoá',
                        ],
                        ['text' => $row->export_created_at ? \Carbon\Carbon::parse($row->export_created_at)->format('d/m/Y') : '—'],
                        ['text' => $purpose ?: '—', 'tone' => $row->type === 'cancel' ? 'danger' : null],
                        ['text' => $row->used_by ?: '—'],
                    ],
                ];
            })->values(),
        ]);
    }

    /* ==========================================================
     |  VPP ĐẾN HC - DANH SÁCH VPP CẦN ĐỀ NGHỊ HÀNH CHÍNH
     ========================================================== */

    /**
     * LẬP ĐỀ NGHỊ VPP TỪ TAB "DANH SÁCH VPP CẦN ĐỀ NGHỊ HÀNH CHÍNH" (tab Đề nghị VPP)
     *
     * VPP Hành Chính mua (VppWatchlist::forVpp) không lập phiếu dự trù: phòng tick chọn
     * rồi gửi thẳng đề nghị cho phòng Hành Chính (kho VPP). Phòng nhận do server xác định
     * (VppStore::targetDepartmentId), không lấy từ form. Phiếu ghi vào vpp_transfer_requests
     * nên Hành Chính cấp phát / phòng mình nhận hàng theo đúng luồng liên phòng ban.
     */
    public function vppWatchlistStore(Request $request)
    {
        $departmentId = $this->departmentId();
        $toDepartmentId = VppStore::targetDepartmentId($departmentId);
        $back = fn () => redirect()->route('pages.export.vppExport.list', ['tab' => VppStore::TAB, 'sub' => 'watchlist']);

        if (! $toDepartmentId) {
            return $back()->with('error', 'Không tìm thấy phòng Hành Chính để gửi đề nghị cấp phát VPP đến HC!');
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'needed_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:vpp_categories,id'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề đề nghị.',
            'needed_date.date' => 'Ngày cần dùng không hợp lệ.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
            'items.required' => 'Vui lòng chọn ít nhất một VPP để đề nghị.',
            'items.min' => 'Vui lòng chọn ít nhất một VPP để đề nghị.',
            'items.*.category_id.required' => 'VPP ngoài danh mục không đề nghị kho VPP được.',
            'items.*.category_id.exists' => 'VPP được chọn không tồn tại trong danh mục.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.numeric' => 'Số lượng đề nghị phải là số.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ]);

        $validator->after(function ($validator) use ($request, $departmentId) {
            $items = (array) $request->input('items', []);

            foreach (VppWatchlist::inProgressErrors($departmentId, $items) + VppStore::itemErrors($items) as $key => $message) {
                $validator->errors()->add($key, $message);
            }
        });

        if ($validator->fails()) {
            return $back()
                ->withErrors($validator, 'vppWatchlistErrors')
                ->with('error', $validator->errors()->first())
                ->withInput();
        }

        $isDraft = $request->input('action_type', 'send') === 'draft';
        $items = [];

        foreach ((array) $request->input('items', []) as $item) {
            $items[] = [
                'category_id' => (int) $item['category_id'],
                'requested_amount' => (float) $item['requested_amount'],
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'note' => $this->nullIfBlank($item['note'] ?? null),
            ];
        }

        $result = VppTransferRequest::create(
            $departmentId,
            $toDepartmentId,
            [
                'title' => $this->nullIfBlank($request->title),
                'note' => $this->nullIfBlank($request->note),
                'needed_date' => $this->nullIfBlank($request->needed_date),
            ],
            $items,
            $isDraft ? 'draft' : 'pending',
            $this->actor()
        );

        VppWatchlist::closeFor($departmentId, array_column($items, 'category_id'), [], $this->actor());

        $toDeptName = $this->departmentName($toDepartmentId);

        AuditTrialController::log(
            $isDraft ? 'Lưu tạm đề nghị cấp phát VPP đến HC' : 'Tạo đề nghị cấp phát VPP đến HC',
            VppTransferRequest::TABLE,
            $result['id'],
            'NA',
            ($isDraft ? 'Lưu tạm đề nghị cấp phát VPP đến HC ' : 'Tạo đề nghị cấp phát VPP đến HC ').$result['code'].' gửi đến '.$toDeptName
                .' ('.count($items).' mục) từ danh sách VPP cần đề nghị Hành Chính'
        );

        return redirect()->route('pages.export.vppExport.list', ['tab' => VppStore::TAB])->with(
            'success',
            $isDraft
                ? 'Đã lưu tạm đề nghị cấp phát VPP đến HC '.$result['code'].'! Bạn có thể gửi đề nghị khi sẵn sàng.'
                : VppTransferSignFlow::sendMessage($result['send'], $result['code'], 'đề nghị cấp phát VPP đến HC', $toDeptName)
        );
    }

    /** Bỏ ghi nhớ một VPP ở tab "Danh sách VPP cần đề nghị Hành Chính". */
    public function watchlistDismiss(Request $request)
    {
        $ok = VppWatchlist::dismiss((int) $request->id, $this->departmentId(), $this->actor());

        return redirect()->route('pages.export.vppExport.list', ['tab' => VppStore::TAB, 'sub' => 'watchlist'])->with(
            $ok ? 'success' : 'error',
            $ok ? 'Đã bỏ ghi nhớ VPP cần đề nghị!' : 'Không tìm thấy VPP ghi nhớ cần bỏ, hoặc đã được bỏ trước đó!'
        );
    }

    /**
     * Tồn hiện tại và "tồn khả dụng" của từng danh mục VPP trong phòng (lô còn hạn, còn hứa được).
     * Không còn đề nghị cấp phát nội bộ giữ chỗ nên hai số trùng nhau.
     */
    private function categoryStockMap(int $departmentId): \Illuminate\Support\Collection
    {
        return $this->importOptions($departmentId)->where('suggestable', true)
            ->groupBy('category_id')
            ->map(function ($group) {
                $rem = (float) $group->sum('remaining');

                return ['remaining' => $rem, 'available' => $rem];
            });
    }


    /* ==========================================================
     |  ĐỀ NGHỊ CHUYỂN VPP LIÊN PHÒNG BAN
     |
     |  Mô hình 3 bước, giống hệt "Đề nghị chuyển hoá chất liên phòng ban"
     |  (ChemicalExportController): A đề nghị -> B cấp phát (trừ tồn B ngay bằng một phiếu
     |  vpp_exports type = transfer_out, mục sang "Chờ nhận") -> A bấm Nhận thì mới
     |  tạo dòng vpp_imports cho A. A từ chối nhận thì khoá phiếu transfer_out là tồn
     |  của B tự hoàn lại.
     |
     |  vpp_transfer_requests.department_id    = phòng ĐỀ NGHỊ (A, cần VPP).
     |  vpp_transfer_requests.to_department_id = phòng ĐƯỢC ĐỀ NGHỊ (B, đang giữ VPP).
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
            .'Đây là phiếu do tính năng "Đề nghị chuyển VPP liên phòng ban" tạo ra, '
            .'chỉ thay đổi được qua thao tác Nhận / Từ chối nhận của phòng nhận.'
        );
    }

    /**
     * Phiếu loại bỏ sinh từ quy trình VPP hỏng (bước 2) là quyết định KHÔNG quay lại
     * kho: sửa số lượng hay khoá phiếu đều làm hàng "sống lại" trong tồn, nên chặn hẳn.
     */
    private function quarantineGuard($current, string $action)
    {
        if (empty($current->quarantine_id)) {
            return null;
        }

        return redirect()->back()->with('error', 'Phiếu loại bỏ '.$current->code.' sinh từ quyết định loại bỏ VPP hỏng, không được '.$action.'.');
    }

    /**
     * PHÒNG A TẠO ĐỀ NGHỊ CHUYỂN VPP LIÊN PHÒNG BAN (bước 1/3)
     */
    public function transferRequestStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), $this->transferRules($departmentId), $this->transferMessages());
        $this->checkVppItems($validator, $request);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'transferCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', $this->transferTabOf($request));
        }

        $isDraft = $request->input('action_type', 'send') === 'draft';
        // Gửi ngay cũng ghi Lưu tạm trước rồi đi qua quy trình ký (VppTransferSignFlow::start)
        $status = 'draft';
        $toDepartmentId = (int) $request->to_department_id;
        $code = $this->nextVppTransferCode($departmentId, $toDepartmentId);

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
        $sendResult = $isDraft ? null : VppTransferSignFlow::start((int) $listId, $this->actor());

        AuditTrialController::log(
            $isDraft ? 'Lưu tạm đề nghị chuyển VPP liên phòng ban' : 'Tạo đề nghị chuyển VPP liên phòng ban',
            self::TRANSFER_REQUEST_TABLE,
            $listId,
            'NA',
            ($isDraft ? 'Lưu tạm đề nghị ' : 'Tạo đề nghị chuyển liên phòng ban ').$code
                .' gửi đến '.$toDeptName.' ('.count((array) $request->items).' mục)'
        );

        $kindLabel = VppStore::isVppDepartment($toDepartmentId) ? 'đề nghị cấp phát VPP đến HC' : 'đề nghị chuyển VPP liên phòng ban';

        return redirect()->route('pages.export.vppExport.list', ['tab' => $this->transferTabOf($request)])->with(
            'success',
            $isDraft
                ? 'Đã lưu tạm '.$kindLabel.' '.$code.'! Bạn có thể gửi đề nghị khi sẵn sàng.'
                : VppTransferSignFlow::sendMessage($sendResult, $code, $kindLabel, $toDeptName)
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
                ->with('activeTab', $this->transferTabOf($request));
        }

        $validator = Validator::make($request->all(), $this->transferRules($departmentId), $this->transferMessages());
        $this->checkVppItems($validator, $request);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'transferCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', $this->transferTabOf($request));
        }

        $isDraft = $request->input('action_type', 'draft') === 'draft';
        // Cập nhật rồi gửi cũng ghi Lưu tạm trước rồi đi qua quy trình ký (VppTransferSignFlow::start)
        $status = 'draft';
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
        $sendResult = $isDraft ? null : VppTransferSignFlow::start((int) $req->id, $this->actor());

        AuditTrialController::log(
            $isDraft ? 'Cập nhật đề nghị chuyển VPP liên phòng ban' : 'Gửi đề nghị chuyển VPP liên phòng ban sau cập nhật',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            'draft',
            ($isDraft ? 'Cập nhật đề nghị ' : 'Gửi đề nghị ').$req->code.' gửi đến '.$toDeptName
                .' ('.count((array) $request->items).' mục)'.($sendResult ? ' -> '.$sendResult['status'] : '')
        );

        $kindLabel = VppStore::isVppDepartment($toDepartmentId) ? 'đề nghị cấp phát VPP đến HC' : 'đề nghị chuyển VPP liên phòng ban';

        return redirect()->route('pages.export.vppExport.list', ['tab' => $this->transferTabOf($request)])->with(
            $sendResult && $sendResult['error'] ? 'error' : 'success',
            $isDraft
                ? 'Đã cập nhật lưu tạm đề nghị '.$req->code.' thành công!'
                : VppTransferSignFlow::sendMessage($sendResult, $req->code, $kindLabel, $toDeptName)
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
                ->with('activeTab', $this->transferTabOf($request));
        }

        // Có quy trình ký (Dữ Liệu Gốc > Trình Ký Đề Nghị Liên Phòng Ban) thì chuyển Chờ ký, chưa tới phòng nhận
        $result = VppTransferSignFlow::start((int) $req->id, $this->actor());

        AuditTrialController::log(
            'Gửi đề nghị chuyển VPP liên phòng ban',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            'draft',
            'Gửi đề nghị chuyển liên phòng ban: '.$req->code.' -> '.$result['status']
        );

        $kindLabel = VppStore::isVppDepartment((int) $req->to_department_id) ? 'đề nghị cấp phát VPP đến HC' : 'đề nghị chuyển liên phòng ban';

        return redirect()->route('pages.export.vppExport.list', ['tab' => $this->transferTabOf($request)])
            ->with(
                $result['error'] ? 'error' : 'success',
                VppTransferSignFlow::sendMessage($result, $req->code, $kindLabel, $this->departmentName((int) $req->to_department_id))
            );
    }

    /**
     * KÝ DUYỆT ĐỀ NGHỊ LIÊN PHÒNG BAN / VPP - bước đang chờ của phiếu 'pending_sign'.
     *
     * Không lọc theo phòng đang chọn: người ký có thể thuộc phòng đề nghị, phòng được đề nghị hoặc
     * một phòng cố định (VD Hành Chính - NM1); quyền ký xét theo vai trò + phạm vi phòng ban của
     * bước (VppTransferSignFlow::canSign) và phải nhập lại mật khẩu (21 CFR Part 11).
     */
    public function transferSign(Request $request)
    {
        [$req, $sign, $stop] = $this->transferSignTarget($request, 'Ký duyệt đề nghị liên phòng ban');

        if ($stop) {
            return $stop;
        }

        /*
        | Duyệt MỘT PHẦN: người ký bỏ tick các mục muốn từ chối (reject_items[]) kèm lý do từng
        | mục (reject_reasons[id]). Mục bị từ chối dừng luôn, các mục còn lại đi tiếp luồng ký.
        | Từ chối hết thì phải dùng nút "Từ chối" (phiếu về Lưu tạm cho phòng đề nghị sửa).
        */
        $openItemIds = DB::table(self::TRANSFER_ITEM_TABLE)
            ->where('transfer_request_id', $req->id)
            ->where('active', 1)
            ->where('status', 'draft')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $rejectItems = [];

        foreach (array_unique(array_map('intval', (array) $request->input('reject_items', []))) as $itemId) {
            if (! in_array($itemId, $openItemIds, true)) {
                return redirect()->back()->with('error', 'Mục từ chối không thuộc phiếu '.$req->code.' hoặc đã được xử lý!');
            }

            $reason = trim((string) data_get($request->input('reject_reasons', []), $itemId, ''));

            if ($reason === '') {
                return redirect()->back()->with('error', 'Vui lòng nhập lý do cho từng VPP bị từ chối.');
            }

            $rejectItems[$itemId] = mb_substr($reason, 0, 450);
        }

        if ($rejectItems && count($rejectItems) >= count($openItemIds)) {
            return redirect()->back()->with('error', 'Không thể từ chối toàn bộ VPP khi ký. Hãy dùng nút "Từ chối" để trả phiếu về cho phòng đề nghị.');
        }

        $done = VppTransferSignFlow::sign($req, $sign, $this->actor(), $this->nullIfBlank($request->note), $rejectItems);

        AuditTrialController::log(
            $rejectItems ? 'Ký duyệt một phần' : 'Ký duyệt',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            'Bước '.$sign->step_no,
            ($done ? 'status: pending' : 'Chuyển bước '.($sign->step_no + 1))
                .($rejectItems ? ' | Từ chối '.count($rejectItems).' mục: '.implode('; ', array_map(fn ($id, $reason) => '#'.$id.' '.$reason, array_keys($rejectItems), $rejectItems)) : '')
        );

        $partial = $rejectItems ? ' (từ chối '.count($rejectItems).'/'.count($openItemIds).' VPP)' : '';

        return redirect()->back()->with('success', $done
            ? 'Đã ký duyệt '.$req->code.$partial.' - phiếu đã tới '.$this->departmentName((int) $req->to_department_id).' để cấp phát.'
            : 'Đã ký bước '.$sign->step_no.' của '.$req->code.$partial.'! Đã chuyển tới người ký bước '.($sign->step_no + 1).'.');
    }

    /** Từ chối ở bước đang chờ: phiếu về Lưu tạm kèm lý do (bắt buộc), phòng đề nghị sửa rồi gửi lại. */
    public function transferSignReject(Request $request)
    {
        if (trim((string) $request->reason) === '') {
            return redirect()->back()->with('error', 'Vui lòng nhập lý do từ chối.');
        }

        [$req, $sign, $stop] = $this->transferSignTarget($request, 'Từ chối ký đề nghị liên phòng ban');

        if ($stop) {
            return $stop;
        }

        VppTransferSignFlow::reject($req, $sign, $this->actor(), mb_substr(trim((string) $request->reason), 0, 500));

        AuditTrialController::log('Từ chối ký', self::TRANSFER_REQUEST_TABLE, $req->id, 'Bước '.$sign->step_no, 'Lý do: '.$request->reason);

        return redirect()->back()->with('success', 'Đã từ chối '.$req->code.' - phiếu trả về Lưu tạm cho phòng đề nghị.');
    }

    /** Phiếu + bước đang chờ mà người đăng nhập ký được; sai thì trả kèm redirect báo lỗi. */
    private function transferSignTarget(Request $request, string $action): array
    {
        $req = DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $request->transfer_request_id)->first();
        $sign = VppTransferSignFlow::currentSign($req);

        if (! $req || ! $sign) {
            return [null, null, redirect()->back()->with('error', 'Phiếu không ở bước chờ ký nên không thao tác được!')];
        }

        if (! VppTransferSignFlow::canSign((int) (session('user')['userId'] ?? 0), $sign)) {
            return [null, null, redirect()->back()->with('error', 'Bạn không có quyền ký bước '.$sign->step_no.' của phiếu '.$req->code.'!')];
        }

        if ($stop = $this->guardSignature($request, self::TRANSFER_REQUEST_TABLE, $req->id, $action)) {
            return [null, null, $stop];
        }

        return [$req, $sign, null];
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
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị này.')->with('activeTab', $this->transferTabOf($request));
        }

        if ($req->status !== 'draft') {
            return redirect()->back()->with('error', 'Chỉ có thể huỷ phiếu đang ở trạng thái Lưu tạm.')->with('activeTab', $this->transferTabOf($request));
        }

        DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $req->id)->update([
            'status' => 'canceled',
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Huỷ đề nghị chuyển VPP liên phòng ban',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            $req->code,
            'Đã huỷ đề nghị chuyển liên phòng ban đang lưu tạm'
        );

        return redirect()->back()->with('success', 'Đã huỷ phiếu đề nghị '.$req->code.' thành công!')->with('activeTab', $this->transferTabOf($request));
    }

    /**
     * DANH MỤC VPP + TỒN KHO CỦA PHÒNG ĐƯỢC ĐỀ NGHỊ, trả JSON cho picker chọn nhiều.
     *
     * Phòng A lập đề nghị cần nhìn thấy phòng B (phòng sẽ cấp phát) đang có những VPP
     * gì và còn bao nhiêu, nên bảng chọn phải đọc kho của B chứ không phải kho của mình.
     * Vì phòng B chỉ được chọn ngay trên form nên dữ liệu nạp bằng AJAX, không dựng sẵn
     * tồn của mọi phòng vào trang.
     */
    public function transferDepartmentStock(Request $request)
    {
        $departmentId = (int) $request->query('department_id');

        if ($departmentId <= 0 || $departmentId === $this->departmentId()) {
            return response()->json(['ok' => false, 'message' => 'Vui lòng chọn phòng ban đang giữ VPP trước khi mở danh mục.']);
        }

        $department = DB::table('deparments')->where('id', $departmentId)->where('isActive', 1)->first();

        if (! $department) {
            return response()->json(['ok' => false, 'message' => 'Phòng ban được chọn không tồn tại hoặc đã ngừng hoạt động.']);
        }

        // Tồn của từng lô trong kho phòng B, gom theo danh mục VPP
        $lots = VppPicking::lots($departmentId)->groupBy('category_id');

        $rows = DepartmentVpp::importCategoryOptions($departmentId)->map(function ($category) use ($lots) {
            $group = $lots->get($category->id, collect());

            return [
                'id' => (int) $category->id,
                'code' => $category->code,
                'material_name' => $category->material_name,
                'manufacturer_name' => $category->manufacturer_name ?: $category->manufacturer_short_name,
                'classification_name' => \App\Support\VppClassification::summary($category->classification),
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
     * Chỉ trừ tồn mã xuất nhập nguồn tại B bằng một dòng vpp_exports
     * type = transfer_out - CHƯA tạo tồn cho A. Mục chuyển sang status 'issued' (chờ
     * nhận); dòng vpp_imports thật cho A chỉ sinh ra ở bước A bấm Nhận, lúc đó mới
     * chắc A đã khai VPP này và có đơn vị tính để quy đổi.
     */
    public function transferIssueStore(Request $request)
    {
        $departmentId = $this->departmentId(); // B

        $error = function (string $message) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }

            return redirect()->back()->with('error', $message)->with('activeTab', $this->transferTabOf($request));
        };

        /*
        | Form cột Cấp Phát ở tab "Chờ Cấp Phát" gửi theo khuôn cấp phát nội bộ: lots[][import_id,
        | amount]. Liên phòng ban cấp một mục từ ĐÚNG MỘT mã xuất nhập (bước Nhận của phòng kia
        | tạo lô mới từ mã nguồn này) nên quy về import_id + issued_amount.
        */
        if ($request->has('lots')) {
            $wanted = [];

            foreach ((array) $request->input('lots', []) as $lot) {
                $importId = (int) ($lot['import_id'] ?? 0);
                $amount = (float) ($lot['amount'] ?? 0);

                if ($importId > 0 && $amount > self::EPSILON) {
                    $wanted[$importId] = ($wanted[$importId] ?? 0) + $amount;
                }
            }

            if (count($wanted) > 1) {
                return $error('Đề nghị liên phòng ban chỉ cấp phát từ một mã xuất nhập cho mỗi mục. Vui lòng chọn lại một mã.');
            }

            $request->merge([
                'import_id' => array_key_first($wanted),
                'issued_amount' => $wanted ? reset($wanted) : null,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::TRANSFER_ITEM_TABLE.',id'],
            'import_id' => ['required', 'exists:vpp_imports,id'],
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

        $sourceImport = DB::table('vpp_imports')
            ->where('id', $request->import_id)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->first();

        if (! $sourceImport) {
            return $error('Không tìm thấy mã xuất nhập trong kho phòng ban này!');
        }

        if ((int) $sourceImport->category_id !== (int) $item->category_id) {
            return $error('Mã xuất nhập được chọn không đúng VPP của mục đề nghị!');
        }

        /*
        | Cùng phân quyền với cấp phát nội bộ (issueStore): đối chiếu kế hoạch FEFO/FIFO một lô
        | (VppPicking::plan, $splitLots = false) tính lại ngay lúc cấp.
        |   - không có quyền điều chỉnh MÃ XUẤT NHẬP: chỉ được cấp từ đúng lô đề xuất;
        |   - không có quyền điều chỉnh SỐ LƯỢNG: phải cấp đúng số đề xuất.
        */
        $canAdjustLot = user_can(self::LOT_ADJUST_PERMISSION);
        $canAdjustAmount = user_can(self::AMOUNT_ADJUST_PERMISSION);

        if (! $canAdjustLot || ! $canAdjustAmount) {
            $plannedLine = VppPicking::plan($departmentId, (int) $item->category_id, (float) $item->requested_amount, false)['lines'][0] ?? null;

            if (! $canAdjustLot && (! $plannedLine || (int) $plannedLine['import_id'] !== (int) $sourceImport->id)) {
                return $error('Bạn không có quyền điều chỉnh mã xuất nhập - chỉ được cấp từ mã xuất nhập hệ thống đề xuất'
                    .($plannedLine ? ' ('.$plannedLine['import_code'].')' : '').'. Tải lại trang để lấy đề xuất mới nhất.');
            }

            if (! $canAdjustAmount && $plannedLine && abs(round((float) $request->issued_amount, 4) - (float) $plannedLine['suggested_amount']) > self::EPSILON) {
                return $error('Bạn không có quyền điều chỉnh số lượng cấp phát - phải cấp đúng '
                    .$this->number($plannedLine['suggested_amount']).' '.($item->requested_unit ?: '')
                    .' theo đề xuất. Tải lại trang để lấy đề xuất mới nhất.');
            }
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
                .($held > self::EPSILON ? ' (đang giữ '.$this->number($held).' cho đợt lấy hàng / đang cách ly)' : '')
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
            'Cấp phát VPP liên phòng ban',
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
                    // Cùng khuôn issueStore để tab "Chờ Cấp Phát" trừ tồn lô và bỏ hàng đã cấp
                    'lots' => [['import_id' => (int) $sourceImport->id, 'amount' => $issuedAmount]],
                    'short_amount' => 0,
                    'request_status' => $this->transferRequestStatus((int) $item->transfer_request_id),
                ],
            ]);
        }

        return redirect()->route('pages.export.vppExport.list', ['tab' => $this->transferTabOf($request)])->with('success', $message);
    }

    /**
     * PHÒNG B TỪ CHỐI CẤP PHÁT 1 MỤC ĐỀ NGHỊ LIÊN PHÒNG BAN
     */
    public function transferRequestReject(Request $request)
    {
        $departmentId = $this->departmentId(); // B

        $error = function (string $message) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }

            return redirect()->back()->with('error', $message)->with('activeTab', $this->transferTabOf($request));
        };

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::TRANSFER_ITEM_TABLE.',id'],
            'reject_note' => ['required', 'max:500'],
        ], [
            'reject_note.required' => 'Vui lòng nhập lý do từ chối.',
            'reject_note.max' => 'Lý do từ chối tối đa 500 ký tự.',
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
            return $error('Không tìm thấy mục đề nghị!');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $item->transfer_request_id)
            ->where('to_department_id', $departmentId)
            ->first();

        if (! $transferReq) {
            return $error('Không tìm thấy phiếu đề nghị thuộc phòng ban này!');
        }

        DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $item->id)->update([
            'status' => 'rejected',
            'reject_note' => trim((string) $request->reject_note),
            'updated_at' => now(),
        ]);

        $this->refreshTransferStatus((int) $item->transfer_request_id, now());

        AuditTrialController::log(
            'Từ chối cấp phát VPP liên phòng ban',
            self::TRANSFER_ITEM_TABLE,
            $item->id,
            'pending',
            'Từ chối cấp phát: '.$request->reject_note
        );

        $message = 'Đã từ chối mục đề nghị cấp phát liên phòng ban.';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'reject_note' => trim((string) $request->reject_note),
                    'request_status' => $this->transferRequestStatus((int) $item->transfer_request_id),
                ],
            ]);
        }

        return redirect()->route('pages.export.vppExport.list', ['tab' => $this->transferTabOf($request)])
            ->with('success', $message);
    }

    /**
     * PHÒNG A NHẬN VPP ĐÃ ĐƯỢC CẤP PHÁT (bước 3/3)
     *
     * Đến đây mới thật sự tạo dòng vpp_imports cho A: bắt buộc A đã khai VPP này
     * ở tab "VPP Của Phòng" (đơn vị tính dùng chung toàn công ty nên không quy đổi số lượng),
     * rồi tự chọn định khu của phòng mình. Mã lô mới giữ mã gốc kèm hậu tố -CK<số thứ tự>
     * (nextVppImportTransferCode) để truy vết, cùng cách của hoá chất.
     *
     * BƯỚC NHẬN = BƯỚC KIỂM TRA: lô nhận chuyển KHÔNG qua tab "Chờ kiểm tra" của màn Nhập. Modal
     * "Xác nhận kiểm tra lô VPP" ở bước Nhận chỉ thu kết luận kiểm tra + định khu thật (bắt
     * buộc); thông tin lô (số lô, hoá đơn, hạn dùng, mục đích) lấy nguyên từ lô nguồn. Lô mới ghi
     * check_result = passed, is_checked = 1 nên vào tồn ngay. Không đạt = Từ chối nhận (transferReceiveReject).
     */
    public function transferReceiveStore(Request $request)
    {
        $departmentId = $this->departmentId(); // A

        $error = function (string $message) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }

            return redirect()->back()->with('error', $message)->with('activeTab', $this->transferTabOf($request));
        };

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::TRANSFER_ITEM_TABLE.',id'],
            'dest_location_id' => ['required'],
            'check_note' => ['nullable', 'max:500'],
        ], [
            'item_id.required' => 'Không tìm thấy mục cần nhận.',
            'dest_location_id.required' => 'Vui lòng chọn định khu vị trí lưu trữ thật cho lô nhận.',
            'check_note.max' => 'Kết luận kiểm tra tối đa 500 ký tự.',
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

        $sourceImport = DB::table('vpp_imports')->where('id', $item->import_id)->first();

        if (! $sourceImport) {
            return $error('Không tìm thấy mã xuất nhập nguồn của mục này!');
        }

        $bDepartmentId = (int) $transferReq->to_department_id;
        $bDeptName = $this->departmentName($bDepartmentId);

        // Phòng A phải đã khai VPP này thì mới nhận vào kho phòng được
        $aKnown = DB::table(DepartmentVpp::TABLE)
            ->where('department_id', $departmentId)
            ->where('category_id', $item->category_id)
            ->where('status_id', 1)
            ->exists();

        if (! $aKnown) {
            return $error('Phòng bạn chưa khai VPP này ở tab "VPP Của Phòng" nên chưa nhận được. Vui lòng khai trước rồi quay lại nhận.');
        }

        // Đơn vị tính VPP dùng chung toàn công ty (Danh Mục VPP Công Ty) - hai phòng cùng đơn vị,
        // không cần quy đổi số lượng
        $convertedAmount = (float) $item->issued_amount;

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

        // Thông tin lô lấy nguyên từ lô nguồn; modal Xác nhận kiểm tra chỉ thu kết luận + định khu
        $checkNote = trim((string) $request->check_note) === '' ? null : trim((string) $request->check_note);
        $lotInfo = [
            'batch_no' => $sourceImport->batch_no,
            'invoice_number' => $sourceImport->invoice_number,
            'invoice_date' => $sourceImport->invoice_date,
            'expired_date' => $sourceImport->expired_date,
            'purpose' => $sourceImport->purpose,
        ];
        $checkLog = 'Kiểm tra ĐẠT ở bước nhận, lô được nhập kho và cộng vào tồn.'
            .($checkNote !== null ? ' Kết luận: '.$checkNote : '');

        $result = DB::transaction(function () use (
            $item, $sourceImport, $exportRow, $departmentId, $destLocationId, $convertedAmount, $receivedAt, $note,
            $lotInfo, $checkNote, $checkLog
        ) {
            // Giữ nguyên mã gốc kèm hậu tố -CK<số thứ tự> để truy vết
            $newCode = $this->nextVppImportTransferCode($sourceImport->code);

            $newImportId = DB::table('vpp_imports')->insertGetId([
                'code' => $newCode,
                'department_id' => $departmentId,
                'category_id' => (int) $item->category_id,
                'source_export_id' => $exportRow->id,
                'transfer_item_id' => $item->id,
                'batch_no' => $lotInfo['batch_no'],
                'invoice_number' => $lotInfo['invoice_number'],
                'invoice_date' => $lotInfo['invoice_date'],
                'expired_date' => $lotInfo['expired_date'],
                'purpose' => $lotInfo['purpose'],
                'amount' => $convertedAmount,
                // Ngày nhập là thời điểm bấm Nhận
                'imported_date' => $receivedAt->format('Y-m-d'),
                'imported_by' => $this->actor(),
                'location_id' => $destLocationId,
                'note' => $note,
                'status_id' => 1,
                // Bước Nhận chính là bước Kiểm tra: lô vào tồn ngay, không qua tab Chờ kiểm tra
                'is_checked' => 1,
                'check_result' => \App\Support\CheckStatus::PASSED,
                'check_note' => $checkNote,
                'checked_by' => $this->actor(),
                'checked_at' => $receivedAt,
                'created_by' => $this->actor(),
                'created_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]);

            DB::table('vpp_import_histories')->insert([
                'vpp_import_id' => $newImportId,
                'action' => 'Thêm mới',
                'code' => $newCode,
                'category_id' => (int) $item->category_id,
                'amount' => $convertedAmount,
                'imported_date' => $receivedAt->format('Y-m-d'),
                'imported_by' => $this->actor(),
                'expired_date' => $lotInfo['expired_date'],
                'location_id' => $destLocationId,
                'note' => $note,
                'status_id' => 1,
                'change_note' => $note.' Mã mới của phòng: '.$newCode.'. '.$checkLog,
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
            'Nhận chuyển VPP liên phòng ban',
            'vpp_imports',
            $result['new_import_id'],
            'NA',
            'Nhận từ phòng '.$bDeptName.', mã nguồn '.$sourceImport->code.' -> mã mới '.$result['new_code']
        );

        $message = 'Đã nhận hàng, mã xuất nhập mới: '.$result['new_code'].'!';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'received_by' => $this->actor(),
                    'received_at' => now()->format('d/m/Y H:i'),
                    'request_status' => $this->transferRequestStatus((int) $item->transfer_request_id),
                ],
            ]);
        }

        return redirect()->route('pages.export.vppExport.list', ['tab' => $this->transferTabOf($request)])->with('success', $message);
    }

    /**
     * PHÒNG A TỪ CHỐI NHẬN 1 MỤC ĐÃ ĐƯỢC CẤP PHÁT
     *
     * Hoàn tồn cho B bằng cách khoá dòng vpp_exports type = transfer_out tương ứng
     * (status_id = 0) - mọi công thức tồn chỉ cộng phiếu xuất status_id = 1 nên lô nguồn
     * coi như chưa từng bị trừ.
     */
    public function transferReceiveReject(Request $request)
    {
        $departmentId = $this->departmentId(); // A

        $error = function (string $message) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message]);
            }

            return redirect()->back()->with('error', $message)->with('activeTab', $this->transferTabOf($request));
        };

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:'.self::TRANSFER_ITEM_TABLE.',id'],
            'return_note' => ['required', 'max:500'],
        ], [
            'return_note.required' => 'Vui lòng nhập lý do từ chối nhận.',
            'return_note.max' => 'Lý do từ chối nhận tối đa 500 ký tự.',
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
            return $error('Không tìm thấy mục cần từ chối nhận!');
        }

        $transferReq = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->where('id', $item->transfer_request_id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $transferReq) {
            return $error('Không tìm thấy phiếu đề nghị thuộc phòng ban này!');
        }

        $exportRow = DB::table(self::TABLE)
            ->where('transfer_item_id', $item->id)
            ->where('type', self::TYPE_TRANSFER_OUT)
            ->first();

        if (! $exportRow) {
            return $error('Không tìm thấy phiếu chuyển tương ứng!');
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
            'Từ chối nhận chuyển VPP liên phòng ban',
            self::TRANSFER_ITEM_TABLE,
            $item->id,
            'issued',
            'Từ chối nhận, hoàn tồn phiếu '.$exportRow->code.': '.$returnNote
        );

        $message = 'Đã từ chối nhận, tồn kho phòng gửi đã được hoàn lại.';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'return_note' => $returnNote,
                    'request_status' => $this->transferRequestStatus((int) $item->transfer_request_id),
                ],
            ]);
        }

        return redirect()->route('pages.export.vppExport.list', ['tab' => $this->transferTabOf($request)])
            ->with('success', $message);
    }

    /* ==========================================================
     |  HÀM DÙNG CHUNG
     ========================================================== */

    /** Thêm các dòng VPP của một phiếu đề nghị liên phòng ban. */
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
    /** Trạng thái hiện tại của phiếu - modal chi tiết dùng để đổi badge tại chỗ, không tải lại trang. */
    private function transferRequestStatus(int $transferRequestId): ?string
    {
        return DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $transferRequestId)->value('status');
    }

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
     *
     * $kind tách hai tab theo phòng NHẬN đề nghị: 'transfer' = gửi tới phòng thường,
     * VppStore::TAB = gửi tới phòng Hành Chính (Đề nghị VPP). Mỗi tab một bộ tiền tố bộ lọc.
     */
    /**
     * Phiếu 'pending_sign' (của loại đang xét) mà người đăng nhập ký được ở bước đang chờ.
     */
    private function transferToSign(callable $ofKind)
    {
        $userId = (int) (session('user')['userId'] ?? 0);
        $table = self::TRANSFER_REQUEST_TABLE;
        $signs = VppTransferSignFlow::SIGN_TABLE;

        return DB::table($table)
            ->join($signs, function ($join) use ($table, $signs) {
                $join->on($signs.'.transfer_request_id', '=', $table.'.id')
                    ->on($signs.'.step_no', '=', $table.'.current_step')
                    ->where($signs.'.active', 1)
                    ->where($signs.'.status', 'pending');
            })
            ->leftJoin('deparments as rq', 'rq.id', '=', $table.'.department_id')
            ->leftJoin('deparments as rc', 'rc.id', '=', $table.'.to_department_id')
            ->tap($ofKind)
            ->where($table.'.status', VppTransferSignFlow::STATUS_PENDING_SIGN)
            ->select(
                $table.'.*',
                $signs.'.step_no as sign_step_no',
                $signs.'.role_names as sign_role_names',
                $signs.'.department_id as sign_department_id',
                'rq.name as requester_name',
                'rc.name as receiver_name'
            )
            ->orderBy($table.'.updated_at')
            ->get()
            ->filter(fn ($row) => VppTransferSignFlow::canSign($userId, (object) [
                'role_names' => $row->sign_role_names,
                'department_id' => $row->sign_department_id,
            ]))
            ->map(function ($row) {
                // Modal chi tiết dùng chung với 2 bảng gửi đi / gửi đến: "phòng bên kia" của phòng đang chọn
                $row->partner_name = (int) $row->department_id === $this->departmentId() ? $row->receiver_name : $row->requester_name;

                return $row;
            })
            ->values();
    }

    private function transferRequestsData(int $departmentId, Request $request, string $kind = 'transfer'): array
    {
        $isVpp = $kind === VppStore::TAB;
        [$sentPrefix, $sentDonePrefix, $receivedPrefix, $donePrefix] = $isVpp
            ? ['vsent_', 'vsdone_', 'vrecv_', 'vdone_']
            : ['tsent_', 'tsdone_', 'trecv_', 'tdone_'];
        $vppIds = VppStore::departmentIds() ?: [0];
        $toColumn = self::TRANSFER_REQUEST_TABLE.'.to_department_id';
        $ofKind = fn ($query) => $isVpp ? $query->whereIn($toColumn, $vppIds) : $query->whereNotIn($toColumn, $vppIds);

        $base = fn () => DB::table(self::TRANSFER_REQUEST_TABLE)
            ->select(self::TRANSFER_REQUEST_TABLE.'.*')
            ->tap($ofKind)
            ->orderBy(self::TRANSFER_REQUEST_TABLE.'.created_at', 'desc');

        $sentDoneRange = ListRange::of($request, $sentDonePrefix);
        $doneRange = ListRange::of($request, $donePrefix);

        $sentBase = fn () => $base()
            ->leftJoin('deparments', self::TRANSFER_REQUEST_TABLE.'.to_department_id', '=', 'deparments.id')
            ->addSelect('deparments.name as partner_name', 'deparments.shortName as partner_short')
            ->where(self::TRANSFER_REQUEST_TABLE.'.department_id', $departmentId);

        // Phiếu gửi đi còn dở dang: chưa cấp xong HOẶC còn mục đã cấp đang chờ phòng mình nhận
        $sentPendingWhere = fn ($query) => $query
            ->whereIn(self::TRANSFER_REQUEST_TABLE.'.status', self::TRANSFER_PENDING_STATUSES)
            ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                ->from(self::TRANSFER_ITEM_TABLE)
                ->whereColumn(self::TRANSFER_ITEM_TABLE.'.transfer_request_id', self::TRANSFER_REQUEST_TABLE.'.id')
                ->where(self::TRANSFER_ITEM_TABLE.'.active', 1)
                ->where(self::TRANSFER_ITEM_TABLE.'.status', 'issued'));

        // Tab "Chờ xử lý" của card gửi đi - luôn hiện đủ, không lọc ngày
        $sent = $sentBase()
            ->where($sentPendingWhere)
            ->paginate(ListRange::perPage($request, $sentPrefix), ['*'], ListRange::pageName($sentPrefix))
            ->withQueryString();

        // Tab "Đã hoàn thành" của card gửi đi: đã cấp đủ + nhận xong / từ chối / huỷ - lọc theo ngày lập
        $sentDone = $sentBase()
            ->whereNot($sentPendingWhere)
            ->tap(ListRange::dateFilter(self::TRANSFER_REQUEST_TABLE.'.created_at', $sentDoneRange))
            ->paginate(ListRange::perPage($request, $sentDonePrefix), ['*'], ListRange::pageName($sentDonePrefix))
            ->withQueryString();

        $receivedBase = fn () => $base()
            ->leftJoin('deparments', self::TRANSFER_REQUEST_TABLE.'.department_id', '=', 'deparments.id')
            ->addSelect('deparments.name as partner_name', 'deparments.shortName as partner_short')
            ->where(self::TRANSFER_REQUEST_TABLE.'.to_department_id', $departmentId)
            // Phòng nhận chỉ thấy phiếu đã ký duyệt xong và gửi tới: Lưu tạm / đang trình ký /
            // huỷ (chỉ huỷ được khi còn Lưu tạm) là việc riêng của phòng đề nghị
            ->whereNotIn(self::TRANSFER_REQUEST_TABLE.'.status', self::TRANSFER_UNSENT_STATUSES);

        // Card "cần cấp phát": chỉ phiếu chưa cấp / cấp một phần - luôn hiện đủ, không lọc ngày
        $received = $receivedBase()
            ->whereIn(self::TRANSFER_REQUEST_TABLE.'.status', self::TRANSFER_ISSUE_STATUSES)
            ->paginate(ListRange::perPage($request, $receivedPrefix), ['*'], ListRange::pageName($receivedPrefix))
            ->withQueryString();

        // Card "đã xử lý xong": đã cấp đủ / từ chối - lọc theo ngày lập
        $done = $receivedBase()
            ->whereNotIn(self::TRANSFER_REQUEST_TABLE.'.status', self::TRANSFER_ISSUE_STATUSES)
            ->tap(ListRange::dateFilter(self::TRANSFER_REQUEST_TABLE.'.created_at', $doneRange))
            ->paginate(ListRange::perPage($request, $donePrefix), ['*'], ListRange::pageName($donePrefix))
            ->withQueryString();

        // Phiếu đang chờ CHÍNH người đăng nhập ký, không phụ thuộc phòng đang chọn
        $toSign = $this->transferToSign($ofKind);

        $requestIds = $sent->pluck('id')->merge($sentDone->pluck('id'))->merge($received->pluck('id'))->merge($done->pluck('id'))->merge($toSign->pluck('id'))->unique();

        $items = DB::table(self::TRANSFER_ITEM_TABLE)
            ->leftJoin('vpp_categories', self::TRANSFER_ITEM_TABLE.'.category_id', '=', 'vpp_categories.id')
            ->leftJoin('material_names', 'vpp_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('locations', self::TRANSFER_ITEM_TABLE.'.dest_location_id', '=', 'locations.id')
            // Lô nguồn bên phòng gửi: modal Xác nhận kiểm tra hiện thông tin lô lấy từ nguồn
            ->leftJoin('vpp_imports as source_imports', self::TRANSFER_ITEM_TABLE.'.import_id', '=', 'source_imports.id')
            ->where(self::TRANSFER_ITEM_TABLE.'.active', 1)
            ->whereIn(self::TRANSFER_ITEM_TABLE.'.transfer_request_id', $requestIds)
            ->select(
                self::TRANSFER_ITEM_TABLE.'.*',
                'source_imports.batch_no as source_batch_no',
                'source_imports.expired_date as source_expired_date',
                'source_imports.invoice_number as source_invoice_number',
                'source_imports.invoice_date as source_invoice_date',
                'source_imports.purpose as source_purpose',
                'vpp_categories.code as category_code',
                'material_names.name as material_name',
                'locations.code as dest_location_code',
                'locations.zone_type as dest_location_zone_type'
            )
            ->orderBy(self::TRANSFER_ITEM_TABLE.'.id')
            ->get()
            ->groupBy('transfer_request_id');

        /*
        | Huy hiệu trên nút tab: đếm bằng truy vấn riêng chứ không đếm trên $sent /
        | $received nữa - hai danh sách đó giờ chỉ còn một trang.
        */
        $pendingIssue = DB::table(self::TRANSFER_REQUEST_TABLE)
            ->tap($ofKind)
            ->where('to_department_id', $departmentId)
            ->whereIn('status', self::TRANSFER_ISSUE_STATUSES)
            ->count();

        $awaitingReceipt = DB::table(self::TRANSFER_ITEM_TABLE)
            ->join(
                self::TRANSFER_REQUEST_TABLE,
                self::TRANSFER_ITEM_TABLE.'.transfer_request_id',
                '=',
                self::TRANSFER_REQUEST_TABLE.'.id'
            )
            ->tap($ofKind)
            ->where(self::TRANSFER_REQUEST_TABLE.'.department_id', $departmentId)
            ->where(self::TRANSFER_ITEM_TABLE.'.active', 1)
            ->where(self::TRANSFER_ITEM_TABLE.'.status', 'issued')
            ->count();

        return [
            'sent' => $sent,
            'sentDone' => $sentDone,
            'received' => $received,
            'done' => $done,
            'items' => $items,
            'badgeCount' => $pendingIssue + $awaitingReceipt + $toSign->count(),
            'toSign' => $toSign,
            // Bước ký của mọi phiếu đang hiện - chip "Trình ký" trên bảng
            'signs' => VppTransferSignFlow::signsOf($requestIds->values()->all()),
            'sentPerPage' => ListRange::perPage($request, $sentPrefix),
            'sentDoneRange' => $sentDoneRange,
            'sentDonePerPage' => ListRange::perPage($request, $sentDonePrefix),
            'receivedPerPage' => ListRange::perPage($request, $receivedPrefix),
            'doneRange' => $doneRange,
            'donePerPage' => ListRange::perPage($request, $donePrefix),
        ];
    }

    /** Gửi tới phòng Hành Chính (đề nghị VPP) thì mọi dòng phải là VPP Hành Chánh mua. */
    private function checkVppItems($validator, Request $request): void
    {
        if (! VppStore::isVppDepartment((int) $request->to_department_id)) {
            return;
        }

        $validator->after(function ($validator) use ($request) {
            foreach (VppStore::itemErrors((array) $request->input('items', [])) as $key => $message) {
                $validator->errors()->add($key, $message);
            }
        });
    }

    /**
     * Tab chứa đề nghị liên phòng ban đang thao tác - "Đề nghị VPP" nếu phòng nhận là Hành
     * Chính, ngược lại "Chuyển liên phòng ban" - để lưu / cấp phát / nhận xong quay về đúng tab.
     * Phòng nhận đọc từ phiếu (transfer_request_id / item_id), chưa có phiếu thì từ form.
     */
    private function transferTabOf(Request $request): string
    {
        $requestId = $request->transfer_request_id;

        if (! $requestId && $request->item_id) {
            $requestId = DB::table(self::TRANSFER_ITEM_TABLE)->where('id', $request->item_id)->value('transfer_request_id');
        }

        $toDepartmentId = $requestId
            ? DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $requestId)->value('to_department_id')
            : $request->to_department_id;

        return VppStore::tabFor($toDepartmentId ? (int) $toDepartmentId : null);
    }

    /**
     * Mã đề nghị liên phòng ban: VPP-<id phòng gửi>-<id phòng nhận>-ddMMyy-<số thứ tự
     * trong ngày>. Dùng id số thay vì shortName (có thể dài như "KTBT-NM2") cho gọn.
     */
    private function nextVppTransferCode(int $fromDepartmentId, int $toDepartmentId): string
    {
        // Dùng chung với tab "Danh sách VPP cần dự trù" của màn Dự Trù VPP
        return \App\Support\VppTransferRequest::nextCode($fromDepartmentId, $toDepartmentId);
    }

    /**
     * Mã kế tiếp cho lô nhận từ phòng khác: <mã gốc>-CK<số thứ tự>.
     *
     * Mã gốc là mã của phòng nhập ĐẦU TIÊN, tức phần đứng trước "-CK" nếu lô đang chuyển
     * vốn cũng là hàng chuyển kho - chuyển qua bao nhiêu phòng thì mã vẫn quy về đúng một
     * gốc, không nối chồng "-CK01-CK02". Cùng cách với nextChemImportTransferCode của hoá chất.
     */
    private function nextVppImportTransferCode(string $sourceCode): string
    {
        $root = explode('-CK', $sourceCode, 2)[0];
        $prefix = $root.'-CK';

        $next = DB::table('vpp_imports')
            ->where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix)))
            ->max();

        return $prefix.str_pad((string) (($next ?? 0) + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * VPP để chọn khi gửi đề nghị liên phòng ban: cả danh mục chung đã duyệt, không
     * giới hạn ở phần phòng mình đã khai - phòng mình đang thiếu nên mới phải đi xin.
     * Đơn vị hiện trên ô chọn là đơn vị PHÒNG MÌNH đã khai (nếu có).
     */
    private function transferCategoryOptions(int $departmentId)
    {
        return DB::table('vpp_categories')
            ->leftJoin('material_names', 'vpp_categories.material_names_id', '=', 'material_names.id')
            ->tap(fn ($query) => DepartmentVpp::joinUnit($query, $departmentId, 'vpp_categories.id'))
            ->select(
                'vpp_categories.id',
                'vpp_categories.code',
                'vpp_categories.purchasing_department',
                'material_names.name as material_name',
                'units.short_name as unit_short_name'
            )
            ->where('vpp_categories.status_id', 1)
            ->where('vpp_categories.app_status', 'approved')
            ->orderBy('material_names.name', 'asc')
            ->get();
    }

    /**
     * Phòng ban nhận đề nghị chuyển: mọi phòng đang hoạt động (không giới hạn công ty), trừ
     * chính phòng đó và phòng Hành Chính - xin VPP của Hành Chính đi qua tab "Đề nghị VPP".
     * Kèm company_name để ô chọn gom nhóm theo công ty (hai công ty có phòng trùng tên).
     */
    private function departmentOptions(int $departmentId)
    {
        return DB::table('deparments')
            ->leftJoin('companies', 'deparments.company_id', '=', 'companies.id')
            ->select('deparments.id', 'deparments.name', 'deparments.shortName', 'companies.name as company_name')
            ->where('deparments.isActive', 1)
            ->where('deparments.id', '<>', $departmentId)
            ->whereNotIn('deparments.id', VppStore::departmentIds() ?: [0])
            ->orderBy('deparments.company_id')
            ->orderBy('deparments.name', 'asc')
            ->get();
    }

    /**
     * Tồn của PHÒNG CẤP PHÁT (to_department_id) cho từng VPP trên các đề nghị liên phòng ban /
     * VPP đang hiện, để người ký duyệt và phòng cấp phát thấy kho nguồn còn đủ hay không.
     * Tồn theo danh mục của phòng cấp phát (categoryStockMap):
     * khả dụng = tồn - phần các đề nghị nội bộ của phòng đó đang chờ ký / chờ cấp đã giữ chỗ.
     * Số lượng tính theo đơn vị phòng cấp phát đã khai (unit).
     *
     * @return array<int, array<int, array{remaining: float, available: float, unit: ?string}>>
     *         to_department_id => category_id => tồn
     */
    private function transferSourceStock(array ...$sets): array
    {
        $requests = collect();
        $items = collect();

        foreach ($sets as $set) {
            $requests = $requests->merge($set['sent']->getCollection())
                ->merge($set['sentDone']->getCollection())
                ->merge($set['received']->getCollection())
                ->merge($set['done']->getCollection())
                ->merge($set['toSign']);
            $items = $items->union($set['items']);
        }

        $result = [];

        foreach ($requests->unique('id')->groupBy('to_department_id') as $sourceId => $reqs) {
            $categoryIds = $reqs->flatMap(fn ($req) => collect($items[$req->id] ?? [])->pluck('category_id'))
                ->filter()->unique()->values()->all();

            if (! $categoryIds) {
                continue;
            }

            $sourceId = (int) $sourceId;
            $stock = $this->categoryStockMap($sourceId);
            $units = DB::table('vpp_categories')
                ->tap(fn ($query) => DepartmentVpp::joinUnit($query, $sourceId, 'vpp_categories.id'))
                ->whereIn('vpp_categories.id', $categoryIds)
                ->pluck('units.short_name', 'vpp_categories.id');

            foreach ($categoryIds as $categoryId) {
                $result[$sourceId][(int) $categoryId] = [
                    'remaining' => (float) ($stock[$categoryId]['remaining'] ?? 0),
                    'available' => (float) ($stock[$categoryId]['available'] ?? 0),
                    'unit' => $units[$categoryId] ?? null,
                ];
            }
        }

        return $result;
    }

    /**
     * Tiêu đề phòng mình đã từng đặt cho đề nghị liên phòng ban / đề nghị VPP, mới nhất trước,
     * để ô Tiêu Đề gợi ý chọn lại (datalist). Tách theo loại vì hai loại đặt tên khác nhau.
     *
     * @return array{transfer: array<string>, vpp: array<string>}
     */
    private function transferTitleOptions(int $departmentId): array
    {
        $vppIds = VppStore::departmentIds() ?: [0];
        $toColumn = self::TRANSFER_REQUEST_TABLE.'.to_department_id';

        $titles = fn (bool $isVpp) => TitleSuggestions::recent(self::TRANSFER_REQUEST_TABLE, $departmentId, 'title',
            fn ($query) => $isVpp ? $query->whereIn($toColumn, $vppIds) : $query->whereNotIn($toColumn, $vppIds));

        return ['transfer' => $titles(false), 'vpp' => $titles(true)];
    }

    private function departmentName($id): string
    {
        return (string) (DB::table('deparments')->where('id', $id)->value('name') ?: '—');
    }

    private function transferRules(int $departmentId): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'to_department_id' => [
                'required',
                'exists:deparments,id',
                Rule::notIn([$departmentId]),
            ],
            'needed_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:vpp_categories,id'],
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
            'to_department_id.required' => 'Vui lòng chọn phòng ban nguồn (đang giữ VPP).',
            'to_department_id.exists' => 'Phòng ban được chọn không tồn tại.',
            'to_department_id.not_in' => 'Không thể tạo đề nghị liên phòng ban gửi đến chính phòng mình.',
            'items.required' => 'Vui lòng thêm ít nhất một VPP đề nghị.',
            'items.min' => 'Vui lòng thêm ít nhất một VPP đề nghị.',
            'items.*.category_id.required' => 'Vui lòng chọn VPP.',
            'items.*.category_id.exists' => 'VPP được chọn không tồn tại.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ];
    }

    private function logHistory($id, string $action, ?string $note = null): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'vpp_export_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'import_id' => $row->import_id,
            'amount' => $row->amount,
            'type' => $row->type,
            'product_name' => $row->product_name,
            'test_report_no' => $row->test_report_no,
            'reason' => $row->reason,
            // Phiếu cũ cấp phát theo đề nghị: mục đích nằm ở dòng đề nghị
            'purpose' => $row->purpose ?? ($row->request_item_id
                ? DB::table(self::REQ_ITEM)->where('id', $row->request_item_id)->value('purpose')
                : null),
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

    /* ==========================================================
     |  LÔ VPP CỦA PHÒNG + TỒN
     ========================================================== */

    /**
     * Mã xuất nhập của phòng ban đang chọn, còn hiệu lực, kèm tồn còn lại.
     *
     * Thứ tự do App\Support\VppPicking quyết định: lô NÊN XUẤT TRƯỚC đứng đầu danh
     * sách (hạn gần nhất trước; VPP không hạn dùng tự sắp theo ngày nhập). Trước đây
     * ô chọn sắp theo ngày nhập giảm dần - lô MỚI NHẤT nằm trên cùng, ngược nguyên tắc
     * xuất kho.
     *
     * `remaining` vẫn là tồn sổ sách để hiện cho người dùng; `available` mới là phần còn
     * hứa được, đã trừ hàng đang giữ cho các đợt lấy hàng còn treo.
     */
    private function importOptions(int $departmentId)
    {
        return VppPicking::lots($departmentId)->map(function ($import) {
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
        return max($this->remaining($import, $ignoreExportId) - VppPicking::heldOf((int) $import->id), 0);
    }

    private function remaining($import, ?int $ignoreExportId = null): float
    {
        $query = DB::table(self::TABLE)->where('import_id', $import->id)->where('status_id', 1);

        if ($ignoreExportId) {
            $query->where('id', '<>', $ignoreExportId);
        }

        $balanced = (float) DB::table('vpp_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('balancing_amount');

        return max((float) $import->amount + $balanced - (float) $query->sum('amount'), 0);
    }

    private function adjustCounts(int $departmentId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('vpp_export_id', DB::raw('COUNT(*) as times'))
            ->whereIn('vpp_export_id', function ($query) use ($departmentId) {
                $query->select('id')->from(self::TABLE)->where('department_id', $departmentId);
            })
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('vpp_export_id')
            ->pluck('times', 'vpp_export_id');
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
