<?php

namespace App\Http\Controllers\Pages\Estimate;

use App\Http\Controllers\Concerns\EstimateItemCancel;
use App\Http\Controllers\Concerns\EstimateSignFlow;
use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\AttachmentBackup;
use App\Support\DepartmentMaterial;
use App\Support\MaterialClassification;
use App\Support\MaterialTransferRequest;
use App\Support\MaterialWatchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DỰ TRÙ - DỰ TRÙ VẬT TƯ
 *
 * Phòng ban lập phiếu dự trù vật tư cho một tháng/năm (material_estimates), khai từng vật
 * tư cần dùng (material_estimate_items) và số lượng cần cho từng tháng
 * (material_estimate_item_amounts).
 *
 * Mặt hàng chọn từ Danh Mục Vật Tư, hoặc tự gõ tên khi vật tư chưa có trong danh mục
 * (material_estimate_items.category_id = NULL). Vật tư không có "nhóm chuẩn" nên bỏ hẳn
 * khái niệm đó so với dự trù chất chuẩn.
 *
 * TRÌNH KÝ 2 BƯỚC dùng chung khai báo config/estimate.php:
 *   Nháp -> [Trình ký] -> Chờ Phó/Trưởng Phòng ký -> [Ký bước 1] -> Chờ Ban Giám Đốc ký
 *        -> [Ký bước 2] -> Đã phê duyệt. Duyệt xong phiếu TỰ đánh dấu đã tiếp nhận
 *        (reception_status = received) - không đi qua màn hình tiếp nhận nào.
 *   Bị từ chối ở bước nào cũng quay về "Bị từ chối", sửa lại rồi trình ký lại từ đầu.
 */
class MaterialEstimateController extends Controller
{
    use EstimateItemCancel;
    use EstimateSignFlow;
    use VerifiesSignature;

    private const TABLE = 'material_estimates';

    private const ITEM_TABLE = 'material_estimate_items';

    private const AMOUNT_TABLE = 'material_estimate_item_amounts';

    private const HISTORY_TABLE = 'material_estimate_histories';

    /** File đính kèm của phiếu - xoá mềm bằng cột active. */
    private const ATTACHMENT_TABLE = 'material_estimate_attachments';

    private const ATTACHMENT_FOLDER = 'material_estimates';

    private const ATTACHMENT_MIMES = 'pdf,doc,docx,xls,xlsx,csv,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,webp,zip,rar,7z,msg,eml';

    /** Quy trình ký duyệt động - xem App\Http\Controllers\Concerns\EstimateSignFlow. */
    private const SIGN_TABLE = 'material_estimate_signs';

    private const ESTIMATE_FK = 'material_estimate_id';

    private const EST_ROUTE = 'pages.estimate.materialEstimate.';

    private const SIGN_PERMISSION = 'estimate_material_sign';

    private const CHAT_TYPE = 'material';

    private const LABEL = 'phiếu dự trù vật tư';

    private const ITEM_LABEL = 'vật tư dự trù';

    private const EDITABLE_STATUSES = ['draft', 'rejected'];

    /** Huỷ mục cần xác nhận 2 bên - xem App\Http\Controllers\Concerns\EstimateItemCancel. */
    protected function cancelConfig(): array
    {
        return [
            'item_fk' => 'material_estimate_id',
            'chat_type' => self::CHAT_TYPE,
            'category_table' => 'material_categories',
            'name_table' => 'material_names',
            'name_fk' => 'material_names_id',
            'manual_name' => 'material_name',
            'purchasing_col' => 'purchasing_department',
        ];
    }

    /* ==========================================================
     |  DANH SÁCH PHIẾU
     ========================================================== */

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->select(self::TABLE.'.*', 'deparments.shortName as department_short_name')
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy(self::TABLE.'.year', 'desc')
            ->orderBy(self::TABLE.'.month', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        // Bước ký của các phiếu đang hiện: list_id => danh sách bước theo step_no
        $signRows = $this->signRows($datas->pluck('id'));

        $datas->each(function ($row) use ($signRows) {
            $signs = $signRows->get($row->id, collect());
            $row->pending_sign = $this->pendingSign($row, $signs);
            $row->can_sign = $row->pending_sign ? $this->canSignRow($row->pending_sign) : false;
        });

        $trackedItems = self::trackedItems($departmentId);

        // Vật tư tồn dưới ngưỡng tối thiểu + vật tư người dùng bấm "Ghi nhớ" bên Sử Dụng Vật Tư
        $watchlistItems = MaterialWatchlist::forTab($departmentId);

        // Hộp ký duyệt liên phòng ban (chỉ bật cho phòng ban chung + có quyền is_BOD)
        $inbox = $this->approvalInboxData();

        // Tab "Bộ phận mua hàng" (xác nhận huỷ 2 bên) - chỉ hiện với Cung Ứng / Hành Chánh / IT
        $purchasingItems = $this->purchasingItems($departmentId);

        $tabs = ['list', 'tracking', 'watchlist', 'inbox', 'purchasing'];
        $activeTab = in_array($request->query('tab'), $tabs, true)
            ? $request->query('tab')
            : (in_array(session('activeTab'), $tabs, true) ? session('activeTab') : 'list');

        session()->put(['title' => 'DỰ TRÙ - DỰ TRÙ VẬT TƯ']);

        return view('pages.estimate.MaterialEstimate.list', [
            'datas' => $datas,
            'itemCounts' => $this->itemCounts($datas->pluck('id')->all()),
            'appStatuses' => config('estimate.app_statuses'),
            'signStatuses' => config('estimate.sign_statuses'),
            'receptionStatuses' => config('estimate.reception_statuses'),
            'signRows' => $signRows,
            'signerOptions' => $this->signerOptions(),
            'signPermission' => self::SIGN_PERMISSION,
            'nextCode' => $this->nextCode($departmentId),
            'trackedItems' => $trackedItems,
            'watchlistItems' => $watchlistItems,
            // Tab "Danh sách vật tư cần dự trù": cột + bộ lọc Bộ Phận Mua Hàng, và hai lối đi
            // tiếp theo của các vật tư được chọn (lập phiếu dự trù / gửi đề nghị liên phòng ban)
            'purchasingDepartments' => MaterialClassification::PURCHASING_DEPARTMENTS,
            'units' => $this->unitOptions(),
            'transferDepartments' => $this->transferDepartmentOptions($departmentId),
            'adminDepartmentId' => $this->adminDepartmentId($departmentId),
            'activeTab' => $activeTab,
            'purchasingItems' => $purchasingItems,
            'showApprovalInbox' => $inbox['show'],
            'inboxRequests' => $inbox['requests'],
            'inboxItems' => $inbox['items'],
            'inboxSigns' => $inbox['signs'],
            'inboxBadgeCount' => $inbox['badge'],
        ]);
    }

    public function detail(Request $request)
    {
        $list = DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->select(self::TABLE.'.*', 'deparments.name as department_name', 'deparments.shortName as department_short_name')
            ->where(self::TABLE.'.id', $request->id)
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->first();

        if (! $list) {
            return redirect()->route('pages.estimate.materialEstimate.list')
                ->with('error', 'Không tìm thấy '.self::LABEL.' của phòng ban đang chọn!');
        }

        session()->put(['title' => 'DỰ TRÙ - CHI TIẾT PHIẾU '.$list->code]);

        $signs = $this->signRows([$list->id])->get($list->id, collect());
        $pendingSign = $this->pendingSign($list, $signs);

        return view('pages.estimate.MaterialEstimate.detail', [
            // Ngưỡng tồn tối đa + tồn hiện tại của phòng, để khối số lượng cảnh báo dự trù quá nhiều
            'maxStock' => \App\Support\MaxStockWarning::forMaterial($this->departmentId()),
            'list' => $list,
            'items' => self::itemsOf($list->id),
            'histories' => self::historiesOf($list->id),
            'categories' => $this->categoryOptions(),
            // Danh mục vật tư của phòng - khung "Chọn từ danh mục phòng" ở modal thêm vật tư
            'deptCategories' => DepartmentMaterial::importCategoryOptions($this->departmentId()),
            'units' => $this->unitOptions(),
            'appStatuses' => config('estimate.app_statuses'),
            'signStatuses' => config('estimate.sign_statuses'),
            'receptionStatuses' => config('estimate.reception_statuses'),
            'signs' => $signs,
            'pendingSign' => $pendingSign,
            // Mốc tính cảnh báo thời gian đặt hàng: ngày BGĐ duyệt (bước ký cuối), chưa duyệt thì ngày tạo phiếu
            'approvedAt' => $list->app_status === 'approved' ? $signs->max('signed_at') : null,
            'canSignCurrent' => $pendingSign ? $this->canSignRow($pendingSign) : false,
            'signPermission' => self::SIGN_PERMISSION,
            'canEditItems' => $this->editable($list),
            'backRoute' => route('pages.estimate.materialEstimate.list'),
            'estRoute' => 'pages.estimate.materialEstimate.',
        ]);
    }

    public function history(Request $request)
    {
        return response()->json(['rows' => self::historiesOf((int) $request->id)]);
    }

    /**
     * Bỏ ghi nhớ một vật tư khỏi tab "Danh sách vật tư cần dự trù". Vật tư vẫn hiện lại ở
     * đây nếu tồn hiện tại đang dưới ngưỡng tối thiểu - xem App\Support\MaterialWatchlist.
     */
    public function watchlistDismiss(Request $request)
    {
        $ok = MaterialWatchlist::dismiss((int) $request->id, $this->departmentId(), $this->actor());

        if (! $ok) {
            return redirect()->back()
                ->with('error', 'Không tìm thấy vật tư ghi nhớ cần bỏ, hoặc đã được bỏ trước đó!')
                ->with('activeTab', 'watchlist');
        }

        return redirect()->back()
            ->with('success', 'Đã bỏ ghi nhớ vật tư cần dự trù!')
            ->with('activeTab', 'watchlist');
    }

    /**
     * LẬP PHIẾU DỰ TRÙ TỪ TAB "DANH SÁCH VẬT TƯ CẦN DỰ TRÙ"
     *
     * Người dùng tick chọn các vật tư ngay trên tab rồi khai số lượng / tháng cần dùng cho
     * từng dòng. Phiếu tạo ra giống hệt phiếu lập tay: một material_estimates trạng thái
     * Nháp, mỗi vật tư một material_estimate_items kèm ĐÚNG MỘT dòng số lượng. Lưu xong mở
     * thẳng trang chi tiết để người lập bổ sung thêm tháng / mặt hàng rồi trình ký.
     *
     * Vật tư do bộ phận nào mua cũng lập được phiếu dự trù - riêng vật tư của Hành Chánh
     * còn có lối đi thứ hai là watchlistTransferStore() bên dưới.
     */
    public function watchlistEstimateStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make(
            $request->all(),
            $this->rules() + $this->watchlistItemRules(),
            $this->messages() + $this->watchlistItemMessages()
        );

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('items', []) as $index => $item) {
                if (empty($item['category_id']) && trim((string) ($item['material_name'] ?? '')) === '') {
                    $validator->errors()->add('items.'.$index.'.material_name', 'Vật tư dòng '.($index + 1).' thiếu cả mã danh mục lẫn tên, không lập phiếu được.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'watchlistEstimateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'watchlist');
        }

        $items = (array) $request->input('items', []);

        $result = DB::transaction(function () use ($request, $departmentId, $items) {
            $code = $this->nextCode($departmentId);

            $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
                'code' => $code,
                'department_id' => $departmentId,
                'app_status' => 'draft',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                $categoryId = ! empty($item['category_id']) ? (int) $item['category_id'] : null;

                $itemId = DB::table(self::ITEM_TABLE)->insertGetId([
                    'material_estimate_id' => $id,
                    'category_id' => $categoryId,
                    'material_name' => $categoryId ? null : $this->nullIfBlank($item['material_name'] ?? null),
                    'technical_information' => $this->nullIfBlank($item['technical_information'] ?? null),
                    'purpose' => $this->nullIfBlank($item['purpose'] ?? null),
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table(self::AMOUNT_TABLE)->insert([
                    'material_estimate_item_id' => $itemId,
                    'amount' => (float) $item['amount'],
                    'unit_id' => (int) $item['unit_id'],
                    'for_month_year' => $item['for_month_year'].'-01',
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return ['id' => $id, 'code' => $code];
        });

        AuditTrialController::log(
            'Thêm mới',
            self::TABLE,
            $result['id'],
            'NA',
            'Lập '.self::LABEL.' '.$result['code'].' từ danh sách vật tư cần dự trù ('.count($items).' mục)'
        );

        return redirect()->route(self::EST_ROUTE.'detail', ['id' => $result['id']])
            ->with('success', 'Đã lập '.self::LABEL.' mã '.$result['code'].' với '.count($items).' vật tư đã chọn!');
    }

    /**
     * GỬI ĐỀ NGHỊ LIÊN PHÒNG BAN TỪ TAB "DANH SÁCH VẬT TƯ CẦN DỰ TRÙ"
     *
     * Chỉ dành cho vật tư mà danh mục công ty khai BỘ PHẬN MUA HÀNG là Hành Chánh: thay vì
     * đi hết quy trình dự trù, phòng gửi thẳng đề nghị xin vật tư cho phòng Hành Chánh
     * (phòng này đang giữ hàng). Phiếu ghi vào cùng bộ bảng material_transfer_requests của
     * tab "Đề nghị chuyển liên phòng ban" bên SỬ DỤNG VẬT TƯ, các bước cấp phát / nhận hàng
     * tiếp theo xử lý ở đó.
     *
     * Vật tư ngoài danh mục không gửi được (không có category_id để phòng kia tra tồn).
     */
    public function watchlistTransferStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'to_department_id' => ['required', 'exists:deparments,id', Rule::notIn([$departmentId])],
            'needed_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:material_categories,id'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề đề nghị.',
            'to_department_id.required' => 'Vui lòng chọn phòng ban nhận đề nghị.',
            'to_department_id.exists' => 'Phòng ban được chọn không tồn tại.',
            'to_department_id.not_in' => 'Không thể gửi đề nghị liên phòng ban đến chính phòng mình.',
            'needed_date.date' => 'Ngày cần dùng không hợp lệ.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
            'items.required' => 'Vui lòng chọn ít nhất một vật tư để đề nghị.',
            'items.min' => 'Vui lòng chọn ít nhất một vật tư để đề nghị.',
            'items.*.category_id.required' => 'Vật tư ngoài danh mục không gửi đề nghị liên phòng ban được.',
            'items.*.category_id.exists' => 'Vật tư được chọn không tồn tại trong danh mục.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.numeric' => 'Số lượng đề nghị phải là số.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'watchlistTransferErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'watchlist');
        }

        $toDepartmentId = (int) $request->to_department_id;
        $items = [];

        foreach ((array) $request->input('items', []) as $item) {
            $items[] = [
                'category_id' => (int) $item['category_id'],
                'requested_amount' => (float) $item['requested_amount'],
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'note' => $this->nullIfBlank($item['note'] ?? null),
            ];
        }

        $result = MaterialTransferRequest::create(
            $departmentId,
            $toDepartmentId,
            [
                'title' => $this->nullIfBlank($request->title),
                'note' => $this->nullIfBlank($request->note),
                'needed_date' => $this->nullIfBlank($request->needed_date),
            ],
            $items,
            'pending',
            $this->actor()
        );

        $toDeptName = DB::table('deparments')->where('id', $toDepartmentId)->value('name') ?: 'phòng ban được chọn';

        AuditTrialController::log(
            'Tạo đề nghị chuyển vật tư liên phòng ban',
            MaterialTransferRequest::TABLE,
            $result['id'],
            'NA',
            'Tạo đề nghị '.$result['code'].' gửi đến '.$toDeptName.' ('.count($items).' mục) từ danh sách vật tư cần dự trù'
        );

        return redirect()->back()
            ->with('success', 'Đã gửi đề nghị liên phòng ban '.$result['code'].' đến '.$toDeptName.'! Theo dõi tiếp ở Sử Dụng Vật Tư - tab "Đề nghị chuyển liên phòng ban".')
            ->with('activeTab', 'watchlist');
    }

    /* ==========================================================
     |  ĐẦU PHIẾU
     ========================================================== */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $departmentId = $this->departmentId();

        $result = DB::transaction(function () use ($request, $departmentId) {
            $code = $this->nextCode($departmentId);

            $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
                'code' => $code,
                'department_id' => $departmentId,
                'app_status' => 'draft',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['id' => $id, 'code' => $code];
        });

        return redirect()->route('pages.estimate.materialEstimate.detail', ['id' => $result['id']])
            ->with('success', 'Đã tạo '.self::LABEL.' mã '.$result['code'].'! Hãy khai các vật tư cần dự trù.');
    }

    public function update(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        if (! $this->editable($current)) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' đã trình ký nên không sửa được nữa!');
        }

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($this->payload($request) + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        if ($current->app_status !== 'draft') {
            AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, $current->code);
            self::writeHistory($current->id, 'Sửa phiếu', null, $current->app_status, $current->app_status, 'Cập nhật thông tin phiếu dự trù.');
        }

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
    }

    public function destroy(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần huỷ!');
        }

        if (! in_array($current->app_status, ['draft', 'rejected'])) {
            return redirect()->back()->with('error', 'Chỉ có thể huỷ phiếu chưa trình ký!');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => 'cancelled',
            'cancel_reason' => $request->cancel_reason,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Huỷ phiếu', self::TABLE, $current->id, 'Phiếu: '.$current->code, 'Lý do: '.$request->cancel_reason);

        return redirect()->back()->with('success', 'Đã huỷ '.self::LABEL.' thành công!');
    }

    /* ==========================================================
     |  FILE ĐÍNH KÈM - mỗi file gắn với MỘT mục dự trù.
     |  Chỉ đính kèm / xoá khi phiếu còn Nháp / Bị từ chối. Xoá là xoá mềm.
     ========================================================== */

    public function uploadAttachment(Request $request)
    {
        $list = $this->findOwn($request->material_estimate_id);

        if (! $list) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần đính kèm file!');
        }

        $validator = Validator::make($request->all(), [
            'attachments' => ['required', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:20480', 'mimes:'.self::ATTACHMENT_MIMES],
            'note' => ['nullable', 'max:255'],
            'material_estimate_item_id' => ['required', 'integer'],
        ], [
            'attachments.required' => 'Vui lòng chọn file cần đính kèm.',
            'material_estimate_item_id.required' => 'File đính kèm phải gắn với một mục dự trù.',
            'attachments.max' => 'Mỗi lần tải tối đa 20 file.',
            'attachments.*.file' => 'File tải lên không hợp lệ.',
            'attachments.*.max' => 'Mỗi file tối đa 20MB.',
            'attachments.*.mimes' => 'Định dạng file không được hỗ trợ.',
            'note.max' => 'Ghi chú tối đa 255 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã trình ký nên không đính kèm file được nữa!');
        }

        // Mỗi file phải gắn với một mục dự trù thuộc đúng phiếu này
        $itemId = DB::table(self::ITEM_TABLE)
            ->where('id', $request->material_estimate_item_id)
            ->where('material_estimate_id', $list->id)
            ->where('active', 1)
            ->value('id');

        if (! $itemId) {
            return redirect()->back()->with('error', 'Không tìm thấy vật tư cần đính kèm file!');
        }

        $names = DB::transaction(fn () => $this->storeFiles(
            $list->id, $itemId, $request->file('attachments', []), $this->nullIfBlank($request->note)
        ));

        if (! $names) {
            return redirect()->back()->with('error', 'Không có file hợp lệ nào được tải lên!');
        }

        AuditTrialController::log('Thêm mới', self::ATTACHMENT_TABLE, $list->id, $list->code, 'Đính kèm file: '.implode(', ', $names));

        if ($list->app_status !== 'draft') {
            self::writeHistory($list->id, 'Đính kèm file', null, $list->app_status, $list->app_status, 'Đính kèm '.count($names).' file: '.implode(', ', $names));
        }

        return redirect()->back()->with('success', 'Đã đính kèm '.count($names).' file vào phiếu '.$list->code.'!');
    }

    /**
     * Xem / tải file. Người trong phòng lập phiếu, hoặc người được chỉ định ký phiếu
     * (xem từ tab "Ký duyệt (mọi phòng ban)") đều mở được.
     */
    public function downloadAttachment($id)
    {
        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->join(self::TABLE, self::ATTACHMENT_TABLE.'.material_estimate_id', '=', self::TABLE.'.id')
            ->select(self::ATTACHMENT_TABLE.'.*', self::TABLE.'.department_id')
            ->where(self::ATTACHMENT_TABLE.'.id', $id)
            ->where(self::ATTACHMENT_TABLE.'.active', 1)
            ->first();

        if (! $attachment) {
            abort(404, 'Không tìm thấy file đính kèm.');
        }

        $isSigner = DB::table(self::SIGN_TABLE)
            ->where(self::ESTIMATE_FK, $attachment->material_estimate_id)
            ->where('user_id', session('user')['userId'] ?? 0)
            ->where('active', 1)
            ->exists();

        if ((int) $attachment->department_id !== $this->departmentId() && ! $isSigner) {
            abort(403, 'Bạn không có quyền xem file này.');
        }

        if (! Storage::exists($attachment->file_path)) {
            abort(404, 'File không tồn tại trên hệ thống lưu trữ.');
        }

        return Storage::response($attachment->file_path, $attachment->file_name, [
            'Content-Disposition' => "inline; filename*=UTF-8''".rawurlencode($attachment->file_name),
        ]);
    }

    public function deleteAttachment(Request $request)
    {
        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->where('id', $request->id)
            ->where('active', 1)
            ->first();

        $list = $attachment ? $this->findOwn($attachment->material_estimate_id) : null;

        if (! $attachment || ! $list) {
            return redirect()->back()->with('error', 'Không tìm thấy file đính kèm cần xoá!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã trình ký nên không xoá file đính kèm được nữa!');
        }

        DB::table(self::ATTACHMENT_TABLE)->where('id', $attachment->id)->update([
            'active' => 0,
            'deleted_by' => $this->actor(),
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Xoá', self::ATTACHMENT_TABLE, $list->id, $list->code, 'Xoá file đính kèm: '.$attachment->file_name);

        if ($list->app_status !== 'draft') {
            self::writeHistory($list->id, 'Xoá file đính kèm', null, $list->app_status, $list->app_status, 'Xoá file: '.$attachment->file_name);
        }

        return redirect()->back()->with('success', 'Đã xoá file '.$attachment->file_name.'!');
    }

    /* ==========================================================
     |  MẶT HÀNG DỰ TRÙ + SỐ LƯỢNG THEO THÁNG
     ========================================================== */

    /**
     * Thêm NHIỀU vật tư một lần từ modal dạng bảng: mỗi dòng items[i] là một vật tư, các
     * tháng cần dùng là cột chung periods[k], số lượng của dòng ở items[i][amounts][k] và
     * dùng chung đơn vị items[i][unit_id]. Dòng bỏ trống hoàn toàn thì bỏ qua.
     */
    public function storeItem(Request $request)
    {
        $list = $this->findOwn($request->material_estimate_id);

        if (! $list) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần khai mặt hàng!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã trình ký nên không thêm mặt hàng được nữa!');
        }

        $itemFiles = $this->pruneEmptyItemRows($request);

        // File của từng dòng đi theo chỉ số MỚI sau khi bỏ dòng trống (không dùng $request->all()
        // vì phần file vẫn giữ chỉ số cũ)
        $data = $request->input();
        foreach ($itemFiles as $index => $files) {
            $data['items'][$index]['files'] = $files;
        }

        $validator = Validator::make($data, $this->batchItemRules(), $this->batchItemMessages());

        // Mỗi dòng phải có ít nhất một tháng có số lượng; cột nào có số thì phải chọn tháng
        $validator->after(function ($validator) use ($request) {
            $periods = (array) $request->input('periods', []);

            foreach ((array) $request->input('items', []) as $index => $line) {
                $hasAmount = false;

                foreach ((array) ($line['amounts'] ?? []) as $k => $amount) {
                    if ($amount === '') {
                        continue;
                    }

                    $hasAmount = true;

                    if (trim((string) ($periods[$k] ?? '')) === '') {
                        $validator->errors()->add('periods.'.$k, 'Vui lòng chọn tháng cho cột số lượng đang có dữ liệu.');
                    }
                }

                if (! $hasAmount) {
                    $validator->errors()->add('items.'.$index.'.amounts', 'Vui lòng nhập số lượng ít nhất một tháng.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'itemCreateErrors')->withInput();
        }

        $periods = (array) $request->input('periods', []);
        $lines = (array) $request->input('items', []);

        $itemIds = DB::transaction(function () use ($lines, $periods, $list, $itemFiles) {
            $ids = [];

            foreach ($lines as $index => $line) {
                $itemId = DB::table(self::ITEM_TABLE)->insertGetId($this->itemPayloadFrom($line) + [
                    'material_estimate_id' => $list->id,
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Một dòng dùng chung một đơn vị cho mọi cột tháng
                $amountRows = [];

                foreach ((array) ($line['amounts'] ?? []) as $k => $amount) {
                    $amountRows[] = [
                        'amount' => $amount,
                        'unit_id' => $line['unit_id'] ?? null,
                        'for_month_year' => $periods[$k] ?? '',
                    ];
                }

                $this->saveAmountRows($itemId, $amountRows);
                $this->storeFiles($list->id, $itemId, $itemFiles[$index] ?? [], null);
                $ids[] = $itemId;
            }

            return $ids;
        });

        if ($list->app_status !== 'draft') {
            foreach ($itemIds as $itemId) {
                AuditTrialController::log('Thêm mới', self::ITEM_TABLE, $itemId, 'NA', 'Thêm '.self::ITEM_LABEL.' vào phiếu '.$list->code);
            }

            self::writeHistory($list->id, 'Thêm mặt hàng', null, $list->app_status, $list->app_status, 'Thêm '.count($itemIds).' mặt hàng vào phiếu.');
        }

        return redirect()->back()->with('success', 'Đã thêm '.count($itemIds).' '.self::ITEM_LABEL.' vào phiếu '.$list->code.'!');
    }

    public function updateItem(Request $request)
    {
        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' cần cập nhật!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã trình ký nên không sửa mặt hàng được nữa!');
        }

        $this->pruneEmptyAmounts($request);

        $validator = Validator::make($request->all(), $this->itemRules(), $this->itemMessages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'itemUpdateErrors')->withInput();
        }

        DB::transaction(function () use ($request, $item) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update($this->itemPayload($request) + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            DB::table(self::AMOUNT_TABLE)->where('material_estimate_item_id', $item->id)->update(['active' => 0]);
            $this->saveAmounts($item->id, $request);
        });

        if ($list->app_status !== 'draft') {
            AuditTrialController::log('Cập nhật', self::ITEM_TABLE, $item->id, 'Phiếu '.$list->code, 'Sửa '.self::ITEM_LABEL);
            self::writeHistory($list->id, 'Sửa mặt hàng', null, $list->app_status, $list->app_status, 'Chỉnh sửa mặt hàng trong phiếu.');
        }

        return redirect()->back()->with('success', 'Cập nhật '.self::ITEM_LABEL.' thành công!');
    }

    public function deleteItem(Request $request)
    {
        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' cần xoá!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã trình ký nên không xoá mặt hàng được nữa!');
        }

        DB::transaction(function () use ($item) {
            DB::table(self::AMOUNT_TABLE)->where('material_estimate_item_id', $item->id)->update(['active' => 0]);
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update([
                'active' => 0,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);
        });

        if ($list->app_status !== 'draft') {
            AuditTrialController::log('Xoá', self::ITEM_TABLE, $item->id, 'Phiếu '.$list->code, 'Xoá '.self::ITEM_LABEL);
            self::writeHistory($list->id, 'Xoá mặt hàng', null, $list->app_status, $list->app_status, 'Xoá mặt hàng khỏi phiếu.');
        }

        return redirect()->back()->with('success', 'Đã xoá '.self::ITEM_LABEL.' khỏi phiếu dự trù!');
    }

    /**
     * Phòng đề nghị cập nhật trạng thái mục đã duyệt: hoàn thành / hoàn tác, và phần của
     * PHÒNG ĐỀ NGHỊ trong luồng huỷ 2 bên (cancel / cancel_reject / cancel_withdraw) -
     * xem EstimateItemCancel. Bộ phận mua hàng thao tác phần của mình qua purchaseCancel().
     */
    public function updateItemStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'action' => 'required|in:complete,cancel,cancel_reject,cancel_withdraw,undo',
        ]);

        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' cần cập nhật!');
        }

        if ($list->app_status !== 'approved') {
            return redirect()->back()->with('error', 'Phiếu chưa được duyệt nên không thể cập nhật trạng thái mục!');
        }

        if (str_starts_with($request->action, 'cancel')) {
            return $this->applyCancel($item, 'requester', $request->action, $request->cancel_reason);
        }

        if ($request->action === 'complete') {
            if ($item->cancel_requester_at || $item->cancel_purchasing_at) {
                return redirect()->back()->with('error', 'Mục đang chờ xác nhận huỷ, xử lý đề nghị huỷ trước khi xác nhận hoàn thành!');
            }

            $updateData = ['fulfilled_date' => now(), 'status_id' => 1];
            $logMessage = 'Đã xác nhận hoàn thành (giao hàng).';
        } else {
            $updateData = ['fulfilled_date' => null, 'status_id' => 1] + $this->clearCancel();
            $logMessage = 'Đã khôi phục lại trạng thái mặt hàng.';
        }

        DB::transaction(function () use ($item, $updateData, $logMessage) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update($updateData);
            $this->itemSystemChat($item->id, $logMessage);
        });

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái '.self::ITEM_LABEL.'!');
    }

    public function updatePromisedDate(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'promised_date' => 'nullable|date',
            'reason' => 'nullable|string|max:500',
        ]);

        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy '.self::ITEM_LABEL.' cần cập nhật!']);
        }

        if ($list->app_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Phiếu chưa được duyệt nên không thể hẹn ngày!']);
        }

        $oldDate = $item->promised_date ? \Carbon\Carbon::parse($item->promised_date)->format('d/m/Y') : 'Chưa có';
        $newDate = $request->promised_date ? \Carbon\Carbon::parse($request->promised_date)->format('d/m/Y') : 'Chưa có';

        if ($oldDate !== $newDate && trim((string) $request->reason) === '') {
            return response()->json(['success' => false, 'message' => 'Vui lòng nhập lý do thay đổi ngày hẹn đáp ứng!']);
        }

        $actor = $this->actor();
        $reason = trim((string) $request->reason);
        $historyAdded = false;

        DB::transaction(function () use ($item, $request, $oldDate, $newDate, $actor, $reason, &$historyAdded) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update([
                'promised_date' => $request->promised_date,
            ]);

            if ($oldDate !== $newDate) {
                DB::table('estimate_item_chats')->insert([
                    'item_id' => $item->id,
                    'item_type' => self::CHAT_TYPE,
                    'user_name' => $actor,
                    'content' => "Cập nhật ngày hẹn đáp ứng từ [{$oldDate}] thành [{$newDate}]. Lý do: {$reason}",
                    'type' => 'history_promised_date',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $historyAdded = true;
            }
        });

        return response()->json(['success' => true, 'message' => 'Đã cập nhật ngày hẹn đáp ứng!', 'historyAdded' => $historyAdded]);
    }

    public function getPromisedDateHistory($itemId)
    {
        $histories = DB::table('estimate_item_chats')
            ->where('item_id', $itemId)
            ->where('item_type', self::CHAT_TYPE)
            ->where('type', 'history_promised_date')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($chat) => [
                'content' => $chat->content,
                'user_name' => $chat->user_name,
                'created_at_formatted' => \Carbon\Carbon::parse($chat->created_at)->format('H:i d/m/Y'),
            ]);

        return response()->json(['success' => true, 'histories' => $histories]);
    }

    public function storeItemChat(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'content' => 'required|string|max:1000',
        ]);

        [$item, $list] = $this->findItem($request->item_id);

        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy '.self::ITEM_LABEL]);
        }

        $chatId = DB::table('estimate_item_chats')->insertGetId([
            'item_id' => $item->id,
            'item_type' => self::CHAT_TYPE,
            'user_name' => $this->actor(),
            'content' => $request->content,
            'type' => 'chat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $chat = DB::table('estimate_item_chats')->where('id', $chatId)->first();
        $chat->created_at_formatted = \Carbon\Carbon::parse($chat->created_at)->format('H:i d/m/Y');

        return response()->json(['success' => true, 'chat' => $chat]);
    }

    /* ==========================================================
     |  TRÌNH KÝ
     ========================================================== */

    /**
     * TRÌNH KÝ: người lập khai quy trình ký duyệt (số bước + người ký từng bước) ngay trên
     * modal Trình ký, tối thiểu 2 bước và bước cuối là Ban Giám Đốc. Quy trình được ghi lại
     * mỗi lần trình ký (kể cả trình ký lại sau khi bị từ chối).
     */
    public function submit(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần trình ký!');
        }

        if (! $this->editable($current)) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' đang ở bước "'.$this->statusLabel($current->app_status).'", không trình ký lại được!');
        }

        if ($current->status_id != 1) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' đang bị khoá, hãy mở khoá trước khi trình ký!');
        }

        if (! DB::table(self::ITEM_TABLE)->where('material_estimate_id', $current->id)->where('active', 1)->exists()) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' chưa có vật tư nào, chưa trình ký được!');
        }

        $validator = Validator::make($request->all(), [
            'signers' => ['required', 'array', 'min:2'],
            'signers.*' => ['required', 'integer', 'exists:user_management,id'],
        ], [
            'signers.required' => 'Vui lòng khai quy trình ký duyệt (tối thiểu 2 bước).',
            'signers.min' => 'Quy trình ký duyệt phải có tối thiểu 2 bước.',
            'signers.*.required' => 'Vui lòng chọn người ký cho mỗi bước.',
            'signers.*.exists' => 'Người ký được chọn không hợp lệ.',
        ]);

        $signerIds = $this->signerIds($request);

        if ($flowError = $this->validateSignerFlow($signerIds)) {
            $validator->after(fn ($v) => $v->errors()->add('signers', $flowError));
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'submitErrors')
                ->with('error', $validator->errors()->first())
                ->withInput();
        }

        if ($stop = $this->guardSignature($request, self::TABLE, $current->id, 'Trình ký')) {
            return $stop;
        }

        $now = now();
        $stepCount = count($signerIds);

        DB::transaction(function () use ($current, $request, $stepCount, $now) {
            DB::table(self::TABLE)->where('id', $current->id)->update([
                'app_status' => 'pending_sign',
                'current_step' => 1,
                'sign_step_count' => $stepCount,
                'submitted_by' => $this->actor(),
                'submitted_at' => $now,
                'rejected_by' => null,
                'rejected_at' => null,
                'reject_step' => null,
                'reject_reason' => null,
                'updated_by' => $this->actor(),
                'updated_at' => $now,
            ]);

            // Ghi lại quy trình ký: bỏ hiệu lực bước cũ (nếu trình ký lại) rồi ghi bước mới
            $this->deactivateSigns($current->id);
            $this->insertSigns($current->id, $request);
        });

        self::writeHistory($current->id, 'Trình ký', '1', $current->app_status, 'pending_sign', $this->nullIfBlank($request->note));

        AuditTrialController::log('Trình ký', self::TABLE, $current->id, 'app_status: '.$current->app_status, 'app_status: pending_sign, '.$stepCount.' bước ký');

        // Bắt đầu quy trình: báo cho người ký bước 1 (các bước sau được báo lần lượt khi ký xong bước trước).
        $this->notifyPendingSigner($current->id, $current->code, 1);

        return redirect()->back()->with('success', 'Đã trình ký phiếu '.$current->code.' qua '.$stepCount.' bước duyệt!');
    }

    /**
     * KÝ MỘT BƯỚC của phiếu dự trù. Chỉ đúng người được chỉ định ở bước đang chờ mới ký được,
     * và phải nhập lại mật khẩu (21 CFR Part 11). Ký xong bước cuối thì phiếu được duyệt và
     * tự đánh dấu đã tiếp nhận.
     */
    public function signStep(Request $request)
    {
        $tab = $this->approvalActiveTab($request);
        $current = $this->resolveEstimateForApproval($request);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần ký duyệt!')->with('activeTab', $tab);
        }

        $sign = $this->currentSign($current);

        if (! $sign) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' không ở bước chờ ký nên không ký được!')->with('activeTab', $tab);
        }

        if (! $this->canSignRow($sign)) {
            return redirect()->back()
                ->with('error', 'Bước '.$sign->step_no.' của phiếu '.$current->code.' do '.($sign->user_name ?: 'người khác').' ký, bạn không ký thay được!')
                ->with('activeTab', $tab);
        }

        if ($stop = $this->guardSignature($request, self::TABLE, $current->id, 'Ký duyệt '.self::LABEL)) {
            return $stop->with('activeTab', $tab);
        }

        $stepCount = (int) $current->sign_step_count;
        $stepNo = (int) $sign->step_no;
        $isLast = $stepNo >= $stepCount;
        $now = now();

        DB::transaction(function () use ($current, $sign, $stepNo, $isLast, $now) {
            DB::table(self::SIGN_TABLE)->where('id', $sign->id)->update([
                'status' => 'signed',
                'signed_by' => $this->actor(),
                'signed_at' => $now,
                'updated_by' => $this->actor(),
                'updated_at' => $now,
            ]);

            $payload = [
                'app_status' => $isLast ? 'approved' : 'pending_sign',
                'current_step' => $isLast ? null : $stepNo + 1,
                'updated_by' => $this->actor(),
                'updated_at' => $now,
            ];

            if ($isLast) {
                $payload['reception_status'] = 'received';
                $payload['received_by'] = 'Hệ thống';
                $payload['received_at'] = $now;
            }

            DB::table(self::TABLE)->where('id', $current->id)->update($payload);
        });

        self::writeHistory($current->id, 'Ký duyệt', (string) $stepNo, $current->app_status, $isLast ? 'approved' : 'pending_sign', $this->nullIfBlank($request->note));

        AuditTrialController::log('Ký duyệt', self::TABLE, $current->id, 'Bước '.$stepNo.'/'.$stepCount, 'app_status: '.($isLast ? 'approved' : 'pending_sign'));

        // Ký xong: chuyển lượt cho người ký bước kế tiếp (bước cuối thì không còn ai để báo).
        if (! $isLast) {
            $this->notifyPendingSigner($current->id, $current->code, $stepNo + 1);
        }

        return redirect()->back()->with(
            'success',
            $isLast
                ? 'Đã ký bước '.$stepNo.'/'.$stepCount.' - phiếu '.$current->code.' được phê duyệt và ghi nhận tiếp nhận.'
                : 'Đã ký bước '.$stepNo.'/'.$stepCount.' cho phiếu '.$current->code.'! Đã chuyển tới người ký bước '.($stepNo + 1).'.'
        )->with('activeTab', $tab);
    }

    public function reject(Request $request)
    {
        $tab = $this->approvalActiveTab($request);
        $current = $this->resolveEstimateForApproval($request);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần từ chối!')->with('activeTab', $tab);
        }

        $sign = $this->currentSign($current);

        if (! $sign) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' không ở bước chờ ký nên không từ chối được!')->with('activeTab', $tab);
        }

        if (! $this->canSignRow($sign)) {
            return redirect()->back()
                ->with('error', 'Bước '.$sign->step_no.' của phiếu '.$current->code.' do '.($sign->user_name ?: 'người khác').' ký, bạn không từ chối thay được!')
                ->with('activeTab', $tab);
        }

        $validator = Validator::make($request->all(), [
            'reject_reason' => ['required', 'max:500'],
        ], [
            'reject_reason.required' => 'Vui lòng nhập lý do từ chối.',
            'reject_reason.max' => 'Lý do từ chối tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'rejectErrors')->withInput()->with('activeTab', $tab);
        }

        if ($stop = $this->guardSignature($request, self::TABLE, $current->id, 'Từ chối duyệt')) {
            return $stop->with('activeTab', $tab);
        }

        $now = now();

        DB::transaction(function () use ($current, $sign, $request, $now) {
            DB::table(self::SIGN_TABLE)->where('id', $sign->id)->update([
                'status' => 'rejected',
                'reject_reason' => $request->reject_reason,
                'updated_by' => $this->actor(),
                'updated_at' => $now,
            ]);

            DB::table(self::TABLE)->where('id', $current->id)->update([
                'app_status' => 'rejected',
                'current_step' => null,
                'rejected_by' => $this->actor(),
                'rejected_at' => $now,
                'reject_step' => (string) $sign->step_no,
                'reject_reason' => $request->reject_reason,
                'updated_by' => $this->actor(),
                'updated_at' => $now,
            ]);
        });

        self::writeHistory($current->id, 'Từ chối', (string) $sign->step_no, $current->app_status, 'rejected', $request->reject_reason);

        AuditTrialController::log('Từ chối duyệt', self::TABLE, $current->id, 'Bước '.$sign->step_no, 'app_status: rejected');

        return redirect()->back()->with('success', 'Đã từ chối phiếu '.$current->code.'. Phòng ban cần sửa lại rồi trình ký lại.')->with('activeTab', $tab);
    }

    /* ==========================================================
     |  HÀM DÙNG CHUNG
     ========================================================== */

    /**
     * Vật tư đã được Cung Ứng hẹn ngày đáp ứng nhưng CHƯA giao xong, gom theo phòng ban
     * đang chọn để đổ vào tab "Theo dõi dự trù". Kèm số lượng theo tháng và trao đổi.
     *
     * Gom một truy vấn cho vật tư, một cho số lượng, một cho trao đổi rồi ghép trong PHP
     * để không phải hỏi DB theo từng dòng.
     */
    public static function trackedItems(int $departmentId)
    {
        $items = DB::table(self::ITEM_TABLE)
            ->join(self::TABLE, self::ITEM_TABLE.'.material_estimate_id', '=', self::TABLE.'.id')
            ->leftJoin('material_categories', self::ITEM_TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, self::ITEM_TABLE.'.category_id'))
            ->select(
                self::ITEM_TABLE.'.*',
                self::TABLE.'.id as list_id',
                self::TABLE.'.code as list_code',
                'material_categories.technical_specification as category_technical_specification',
                'material_categories.lead_time_days as category_lead_time_days',
                'material_names.name as category_material_name',
                'units.short_name as category_unit_short_name',
                'manufacturers.name as category_manufacturer_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->whereNotNull(self::ITEM_TABLE.'.promised_date')
            ->whereNull(self::ITEM_TABLE.'.fulfilled_date')
            ->where(self::ITEM_TABLE.'.status_id', 1)
            ->where(self::ITEM_TABLE.'.active', 1)
            ->orderBy(self::ITEM_TABLE.'.promised_date', 'asc')
            ->get();

        if ($items->isEmpty()) {
            return $items;
        }

        $amounts = DB::table(self::AMOUNT_TABLE)
            ->leftJoin('units', self::AMOUNT_TABLE.'.unit_id', '=', 'units.id')
            ->select(
                self::AMOUNT_TABLE.'.*',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where(self::AMOUNT_TABLE.'.active', 1)
            ->whereIn(self::AMOUNT_TABLE.'.material_estimate_item_id', $items->pluck('id')->all())
            ->orderBy(self::AMOUNT_TABLE.'.for_month_year', 'asc')
            ->get()
            ->groupBy('material_estimate_item_id');

        $allChats = DB::table('estimate_item_chats')
            ->where('item_type', self::CHAT_TYPE)
            ->whereIn('item_id', $items->pluck('id')->all())
            ->orderBy('created_at', 'desc')
            ->get();

        $chats = $allChats->where('type', '!=', 'history_promised_date')
            ->map(function ($chat) {
                $chat->created_at_formatted = \Carbon\Carbon::parse($chat->created_at)->format('H:i d/m/Y');

                return $chat;
            })
            ->groupBy('item_id');

        $historyCounts = $allChats->where('type', 'history_promised_date')
            ->groupBy('item_id')
            ->map->count();

        return $items->map(function ($item) use ($amounts, $chats, $historyCounts) {
            $item->amounts = ($amounts[$item->id] ?? collect())->values();
            $item->chats = ($chats[$item->id] ?? collect())->values();
            $item->history_count = $historyCounts[$item->id] ?? 0;
            $item->display_name = $item->category_id ? $item->category_material_name : $item->material_name;
            // Bảng theo dõi dùng chung hiện category_code + category_type sau tên mặt hàng.
            // Vật tư không có mã/loại danh mục nên mượn ô loại cho quy cách kỹ thuật.
            $item->category_code = null;
            $item->category_type = $item->category_id ? ($item->category_technical_specification ?: null) : null;

            return $item;
        });
    }

    public static function itemsOf(int $listId)
    {
        $departmentId = (int) DB::table(self::TABLE)->where('id', $listId)->value('department_id');

        $items = DB::table(self::ITEM_TABLE)
            ->leftJoin('material_categories', self::ITEM_TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, self::ITEM_TABLE.'.category_id'))
            ->select(
                self::ITEM_TABLE.'.*',
                'material_categories.technical_specification as category_technical_specification',
                'material_categories.lead_time_days as category_lead_time_days',
                'material_names.name as category_material_name',
                'units.short_name as category_unit_short_name',
                'manufacturers.name as category_manufacturer_name',
                'manufacturers.short_name as category_manufacturer_short_name'
            )
            ->where(self::ITEM_TABLE.'.material_estimate_id', $listId)
            ->where(self::ITEM_TABLE.'.active', 1)
            ->orderBy(self::ITEM_TABLE.'.id', 'asc')
            ->get();

        $amounts = DB::table(self::AMOUNT_TABLE)
            ->leftJoin('units', self::AMOUNT_TABLE.'.unit_id', '=', 'units.id')
            ->select(
                self::AMOUNT_TABLE.'.*',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where(self::AMOUNT_TABLE.'.active', 1)
            ->whereIn(self::AMOUNT_TABLE.'.material_estimate_item_id', $items->pluck('id')->all())
            ->orderBy(self::AMOUNT_TABLE.'.for_month_year', 'asc')
            ->get()
            ->groupBy('material_estimate_item_id');

        $allChats = DB::table('estimate_item_chats')
            ->where('item_type', self::CHAT_TYPE)
            ->whereIn('item_id', $items->pluck('id')->all())
            ->orderBy('created_at', 'desc')
            ->get();

        $chats = $allChats->where('type', '!=', 'history_promised_date')
            ->map(function ($chat) {
                $chat->created_at_formatted = \Carbon\Carbon::parse($chat->created_at)->format('H:i d/m/Y');

                return $chat;
            })
            ->groupBy('item_id');

        $historyCounts = $allChats->where('type', 'history_promised_date')
            ->groupBy('item_id')
            ->map->count();

        // File đính kèm của từng vật tư
        $files = DB::table(self::ATTACHMENT_TABLE)
            ->whereIn('material_estimate_item_id', $items->pluck('id')->all())
            ->where('active', 1)
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('material_estimate_item_id');

        return $items->map(function ($item) use ($amounts, $chats, $historyCounts, $files) {
            $item->files = ($files[$item->id] ?? collect())->values();
            $item->amounts = ($amounts[$item->id] ?? collect())->values();
            $item->chats = ($chats[$item->id] ?? collect())->values();
            $item->history_count = $historyCounts[$item->id] ?? 0;
            $item->display_name = $item->category_id ? $item->category_material_name : $item->material_name;

            return $item;
        });
    }

    public static function historiesOf(int $listId): array
    {
        $labels = config('estimate.app_statuses') + config('estimate.reception_statuses');
        $steps = config('estimate.sign_steps');
        $stepLabel = function ($step) use ($steps) {
            if (! $step) {
                return '';
            }
            // Luồng động: step là số thứ tự bước ký
            if (is_numeric($step)) {
                return 'Bước '.$step;
            }
            // Nhật ký ghi trước khi đổi luồng: 'manager' / 'director' / 'reception'
            return $steps[$step]['label'] ?? ($step === 'reception' ? 'Cung Ứng' : $step);
        };

        return DB::table(self::HISTORY_TABLE)
            ->where('material_estimate_id', $listId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($row) => [
                'action' => $row->action,
                'step' => $stepLabel($row->step),
                'from_status' => $labels[$row->from_status] ?? ($row->from_status ?: ''),
                'to_status' => $labels[$row->to_status] ?? ($row->to_status ?: ''),
                'note' => $row->note ?: '',
                'created_by' => $row->created_by ?: 'NA',
                'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
            ])
            ->values()
            ->all();
    }

    public static function writeHistory(int $listId, string $action, ?string $step, ?string $from, ?string $to, ?string $note): void
    {
        DB::table(self::HISTORY_TABLE)->insert([
            'material_estimate_id' => $listId,
            'action' => $action,
            'step' => $step,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'created_by' => \App\Support\Signer::actor(),
            'created_at' => now(),
        ]);
    }

    private function itemCounts(array $listIds): array
    {
        if (! $listIds) {
            return [];
        }

        return DB::table(self::ITEM_TABLE)
            ->select('material_estimate_id', DB::raw('COUNT(*) as total'))
            ->whereIn('material_estimate_id', $listIds)
            ->where('active', 1)
            ->groupBy('material_estimate_id')
            ->pluck('total', 'material_estimate_id')
            ->all();
    }

    private function pruneEmptyAmounts(Request $request): void
    {
        $rows = array_values(array_filter(
            (array) $request->input('amounts', []),
            fn ($line) => trim((string) ($line['amount'] ?? '')) !== ''
        ));

        $request->merge(['amounts' => $rows]);
    }

    /**
     * Modal thêm nhiều vật tư: bỏ các dòng trống hoàn toàn (chưa chọn/gõ vật tư, không số
     * lượng, không thông tin) rồi đánh số lại, để lỗi validate trỏ đúng dòng đang hiện.
     */
    /** @return array<int, \Illuminate\Http\UploadedFile[]> file đính kèm theo chỉ số dòng MỚI */
    private function pruneEmptyItemRows(Request $request): array
    {
        $rows = [];
        $files = [];

        foreach ((array) $request->input('items', []) as $origIndex => $line) {
            $lineFiles = array_values(array_filter((array) $request->file('items.'.$origIndex.'.files', [])));
            $line = (array) $line;
            $line['amounts'] = array_map(fn ($value) => trim((string) $value), (array) ($line['amounts'] ?? []));

            $isBlank = ! array_filter($line['amounts'], fn ($value) => $value !== '')
                && trim((string) ($line['category_id'] ?? '')) === ''
                && trim((string) ($line['material_name'] ?? '')) === ''
                && trim((string) ($line['technical_information'] ?? '')) === ''
                && trim((string) ($line['purpose'] ?? '')) === ''
                && trim((string) ($line['expected_delivery_date'] ?? '')) === ''
                && ! $lineFiles;

            if (! $isBlank) {
                if ($lineFiles) {
                    $files[count($rows)] = $lineFiles;
                }
                $rows[] = $line;
            }
        }

        $request->merge(['items' => $rows]);

        return $files;
    }

    /**
     * Lưu file đính kèm (của cả phiếu khi $itemId = null, hoặc của một vật tư).
     *
     * @return string[] tên các file đã lưu
     */
    private function storeFiles(int $listId, ?int $itemId, array $files, ?string $note): array
    {
        $names = [];

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('public/'.self::ATTACHMENT_FOLDER);
            AttachmentBackup::copy($path, self::ATTACHMENT_FOLDER);

            DB::table(self::ATTACHMENT_TABLE)->insert([
                'material_estimate_id' => $listId,
                'material_estimate_item_id' => $itemId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientMimeType() ?: $file->getClientOriginalExtension(),
                'note' => $note,
                'active' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $names[] = $file->getClientOriginalName();
        }

        return $names;
    }

    private function saveAmounts(int $itemId, Request $request): void
    {
        $this->saveAmountRows($itemId, (array) $request->input('amounts', []));
    }

    /** Ghi các dòng số lượng [amount, unit_id, for_month_year 'Y-m']; dòng thiếu số hoặc tháng thì bỏ qua. */
    private function saveAmountRows(int $itemId, array $lines): void
    {
        $rows = [];

        foreach ($lines as $line) {
            $amount = trim((string) ($line['amount'] ?? ''));
            $period = trim((string) ($line['for_month_year'] ?? ''));

            if ($amount === '' || $period === '') {
                continue;
            }

            $rows[] = [
                'material_estimate_item_id' => $itemId,
                'amount' => (float) $amount,
                'unit_id' => ! empty($line['unit_id']) ? (int) $line['unit_id'] : null,
                'for_month_year' => $period.'-01',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            DB::table(self::AMOUNT_TABLE)->insert($rows);
        }
    }

    /**
     * Mã phiếu kế tiếp: <DeptShortName><yymmdd>.<NN>, số thứ tự đếm riêng cho từng bộ
     * (phòng ban, tháng, năm). Phiếu chỉ khoá chứ không xoá nên mã không bị dùng lại.
     */
    private function nextCode(int $departmentId): string
    {
        $shortName = DB::table('deparments')->where('id', $departmentId)->value('shortName') ?? 'UNK';
        $prefix = $shortName.date('ymd').'.';

        $next = DB::table(self::TABLE)
            ->where('department_id', $departmentId)
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->pluck('code')
            ->map(function ($code) {
                $parts = explode('.', $code);

                return isset($parts[1]) ? (int) $parts[1] : 0;
            })
            ->max();

        return $prefix.str_pad((string) (($next ?? 0) + 1), 2, '0', STR_PAD_LEFT);
    }

    /** Danh mục vật tư đã duyệt và đang hoạt động mới được chọn để dự trù. */
    private function categoryOptions()
    {
        $departmentId = $this->departmentId();

        return DB::table('material_categories')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_categories.id'))
            ->select(
                'material_categories.id',
                'material_categories.technical_specification',
                'material_categories.lead_time_days',
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name',
                'units.id as unit_id'
            )
            ->where('material_categories.status_id', 1)
            ->where('material_categories.app_status', 'approved')
            ->orderBy('material_names.name', 'asc')
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

    private function findOwn($id)
    {
        return DB::table(self::TABLE)
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    private function findItem($id): array
    {
        $item = DB::table(self::ITEM_TABLE)->where('id', $id)->where('active', 1)->first();

        if (! $item) {
            return [null, null];
        }

        $list = $this->findOwn($item->material_estimate_id);

        return $list ? [$item, $list] : [null, null];
    }

    private function editable($list): bool
    {
        return in_array($list->app_status, self::EDITABLE_STATUSES, true) && $list->status_id == 1;
    }

    private function statusLabel(?string $appStatus): string
    {
        return config('estimate.app_statuses')[$appStatus] ?? ($appStatus ?: '—');
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    /* ==========================================================
     |  KIỂM TRA DỮ LIỆU NHẬP
     ========================================================== */

    /**
     * Phòng ban có thể nhận đề nghị liên phòng ban (mọi phòng còn hoạt động, trừ phòng mình).
     */
    private function transferDepartmentOptions(int $departmentId)
    {
        return DB::table('deparments')
            ->select('id', 'name', 'shortName')
            ->where('isActive', 1)
            ->where('id', '!=', $departmentId)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Phòng Hành Chánh để điền sẵn vào ô "Phòng ban nhận đề nghị" - dò theo tên vì bộ phận
     * mua hàng khai ở danh mục là một mã (admin), không trỏ tới deparments.id. Không tìm
     * thấy thì để người dùng tự chọn.
     */
    private function adminDepartmentId(int $departmentId): ?int
    {
        $id = DB::table('deparments')
            ->where('isActive', 1)
            ->where('id', '!=', $departmentId)
            ->where(function ($q) {
                $q->where('name', 'LIKE', '%Hành Ch%')
                    ->orWhere('shortName', 'LIKE', 'HC%');
            })
            ->orderBy('id', 'asc')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /** Quy tắc cho các dòng vật tư chọn từ tab "Danh sách vật tư cần dự trù". */
    private function watchlistItemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['nullable', 'exists:material_categories,id'],
            'items.*.material_name' => ['nullable', 'max:255'],
            'items.*.technical_information' => ['nullable', 'max:1000'],
            'items.*.purpose' => ['nullable', 'max:1000'],
            'items.*.amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.for_month_year' => ['required', 'date_format:Y-m'],
        ];
    }

    private function watchlistItemMessages(): array
    {
        return [
            'items.required' => 'Vui lòng chọn ít nhất một vật tư để lập phiếu dự trù.',
            'items.min' => 'Vui lòng chọn ít nhất một vật tư để lập phiếu dự trù.',
            'items.*.category_id.exists' => 'Vật tư được chọn không tồn tại trong danh mục.',
            'items.*.material_name.max' => 'Tên vật tư tối đa 255 ký tự.',
            'items.*.purpose.max' => 'Mục đích sử dụng tối đa 1000 ký tự.',
            'items.*.amount.required' => 'Vui lòng nhập số lượng dự trù cho mọi vật tư đã chọn.',
            'items.*.amount.numeric' => 'Số lượng dự trù phải là số.',
            'items.*.amount.min' => 'Số lượng dự trù phải lớn hơn 0.',
            'items.*.unit_id.required' => 'Vui lòng chọn đơn vị tính cho mọi vật tư đã chọn.',
            'items.*.unit_id.exists' => 'Đơn vị tính không hợp lệ.',
            'items.*.for_month_year.required' => 'Vui lòng chọn tháng cần dùng cho mọi vật tư đã chọn.',
            'items.*.for_month_year.date_format' => 'Tháng cần dùng không hợp lệ.',
        ];
    }

    private function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'note' => ['nullable', 'max:500'],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'month' => (int) $request->month,
            'year' => (int) $request->year,
            'note' => $this->nullIfBlank($request->note),
        ];
    }

    private function messages(): array
    {
        return [
            'month.required' => 'Vui lòng chọn tháng dự trù.',
            'month.between' => 'Tháng dự trù phải từ 1 đến 12.',
            'year.required' => 'Vui lòng nhập năm dự trù.',
            'year.between' => 'Năm dự trù không hợp lệ.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }

    private function itemRules(): array
    {
        return [
            'source' => ['required', 'in:category,manual'],
            'category_id' => ['required_if:source,category', 'nullable', 'exists:material_categories,id'],
            'material_name' => ['required_if:source,manual', 'nullable', 'max:255'],
            'technical_information' => ['nullable', 'max:1000'],
            'purpose' => ['nullable', 'max:1000'],
            'part_number' => ['nullable', 'max:100'],
            'amounts' => ['required', 'array', 'min:1'],
            'amounts.*.amount' => ['required', 'numeric', 'min:0.0001'],
            'amounts.*.unit_id' => ['required', 'exists:units,id'],
            'amounts.*.for_month_year' => ['required', 'date_format:Y-m'],
        ];
    }

    private function itemPayload(Request $request): array
    {
        return $this->itemPayloadFrom($request->all());
    }

    /** Payload một mặt hàng từ mảng dữ liệu (form sửa 1 dòng hoặc 1 dòng items[i] của modal thêm nhiều). */
    private function itemPayloadFrom(array $line): array
    {
        $fromCategory = ($line['source'] ?? '') === 'category';

        return [
            'category_id' => $fromCategory ? (int) $line['category_id'] : null,
            'material_name' => $fromCategory ? null : $this->nullIfBlank($line['material_name'] ?? null),
            'part_number' => $this->nullIfBlank($line['part_number'] ?? null),
            'technical_information' => $this->nullIfBlank($line['technical_information'] ?? null),
            'purpose' => $this->nullIfBlank($line['purpose'] ?? null),
            'expected_delivery_date' => $this->nullIfBlank($line['expected_delivery_date'] ?? null),
        ];
    }

    /** Modal thêm nhiều vật tư: items[i] là một vật tư, periods[k] là cột tháng chung. */
    private function batchItemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.source' => ['required', 'in:category,manual'],
            'items.*.category_id' => ['required_if:items.*.source,category', 'nullable', 'exists:material_categories,id'],
            'items.*.material_name' => ['required_if:items.*.source,manual', 'nullable', 'max:255'],
            'items.*.part_number' => ['nullable', 'max:100'],
            'items.*.technical_information' => ['nullable', 'max:1000'],
            'items.*.purpose' => ['nullable', 'max:1000'],
            'items.*.expected_delivery_date' => ['nullable', 'date'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.amounts' => ['array'],
            'items.*.amounts.*' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.files' => ['nullable', 'array', 'max:10'],
            'items.*.files.*' => ['file', 'max:20480', 'mimes:'.self::ATTACHMENT_MIMES],
            'periods' => ['required', 'array', 'min:1'],
            'periods.*' => ['nullable', 'date_format:Y-m', 'distinct'],
        ];
    }

    private function batchItemMessages(): array
    {
        return [
            'items.required' => 'Vui lòng khai ít nhất một vật tư.',
            'items.min' => 'Vui lòng khai ít nhất một vật tư.',
            'items.max' => 'Mỗi lần thêm tối đa 200 vật tư.',
            'items.*.source.required' => 'Vui lòng chọn nguồn vật tư.',
            'items.*.source.in' => 'Nguồn vật tư không hợp lệ.',
            'items.*.category_id.required_if' => 'Vui lòng chọn vật tư trong danh mục.',
            'items.*.category_id.exists' => 'Vật tư được chọn không tồn tại trong danh mục.',
            'items.*.material_name.required_if' => 'Vui lòng nhập tên vật tư ngoài danh mục.',
            'items.*.material_name.max' => 'Tên vật tư tối đa 255 ký tự.',
            'items.*.technical_information.max' => 'Thông tin kỹ thuật tối đa 1000 ký tự.',
            'items.*.purpose.max' => 'Mục đích sử dụng tối đa 1000 ký tự.',
            'items.*.part_number.max' => 'Part Number tối đa 100 ký tự.',
            'items.*.expected_delivery_date.date' => 'Ngày mong muốn giao không hợp lệ.',
            'items.*.unit_id.required' => 'Vui lòng chọn đơn vị tính.',
            'items.*.unit_id.exists' => 'Đơn vị tính không hợp lệ.',
            'items.*.amounts.*.numeric' => 'Số lượng dự trù phải là số.',
            'items.*.amounts.*.min' => 'Số lượng dự trù phải lớn hơn 0.',
            'items.*.files.max' => 'Mỗi vật tư đính kèm tối đa 10 file.',
            'items.*.files.*.file' => 'File đính kèm không hợp lệ.',
            'items.*.files.*.max' => 'Mỗi file đính kèm tối đa 20MB.',
            'items.*.files.*.mimes' => 'Định dạng file đính kèm không được hỗ trợ.',
            'periods.required' => 'Vui lòng khai ít nhất một tháng cần dùng.',
            'periods.*.date_format' => 'Tháng cần dùng không hợp lệ.',
            'periods.*.distinct' => 'Các cột tháng cần dùng bị trùng nhau.',
        ];
    }

    private function itemMessages(): array
    {
        return [
            'source.required' => 'Vui lòng chọn nguồn vật tư.',
            'source.in' => 'Nguồn vật tư không hợp lệ.',
            'category_id.required_if' => 'Vui lòng chọn vật tư trong danh mục.',
            'category_id.exists' => 'Vật tư được chọn không tồn tại trong danh mục.',
            'material_name.required_if' => 'Vui lòng nhập tên vật tư ngoài danh mục.',
            'material_name.max' => 'Tên vật tư tối đa 255 ký tự.',
            'technical_information.max' => 'Thông tin kỹ thuật tối đa 1000 ký tự.',
            'purpose.max' => 'Mục đích sử dụng tối đa 1000 ký tự.',
            'part_number.max' => 'Part Number tối đa 100 ký tự.',
            'amounts.required' => 'Vui lòng khai ít nhất một dòng số lượng theo tháng.',
            'amounts.min' => 'Vui lòng khai ít nhất một dòng số lượng theo tháng.',
            'amounts.*.amount.required' => 'Vui lòng nhập số lượng dự trù.',
            'amounts.*.amount.numeric' => 'Số lượng dự trù phải là số.',
            'amounts.*.amount.min' => 'Số lượng dự trù phải lớn hơn 0.',
            'amounts.*.unit_id.required' => 'Vui lòng chọn đơn vị tính.',
            'amounts.*.unit_id.exists' => 'Đơn vị tính không hợp lệ.',
            'amounts.*.for_month_year.required' => 'Vui lòng chọn tháng cần dùng.',
            'amounts.*.for_month_year.date_format' => 'Tháng cần dùng không hợp lệ.',
        ];
    }

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
