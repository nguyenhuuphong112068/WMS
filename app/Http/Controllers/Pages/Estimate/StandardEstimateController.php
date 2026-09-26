<?php

namespace App\Http\Controllers\Pages\Estimate;

use App\Http\Controllers\Concerns\EstimateItemCancel;
use App\Http\Controllers\Concerns\EstimateSignFlow;
use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentStandard;
use App\Support\StandardCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DỰ TRÙ - DỰ TRÙ CHẤT CHUẨN
 *
 * Phòng ban lập phiếu dự trù chất chuẩn cho một tháng/năm (standard_estimates), khai
 * từng chất chuẩn cần dùng (standard_estimate_items) và số lượng cần cho từng tháng
 * (standard_estimate_item_amounts).
 *
 * Mặt hàng có thể chọn từ Danh Mục Chất Chuẩn, hoặc tự gõ tên khi chất chuẩn chưa có
 * trong danh mục (standard_estimate_items.category_id = NULL) - lúc đó khai thêm nhóm
 * chuẩn mong muốn để Cung Ứng biết cần mua chuẩn chính hay chuẩn tạp.
 *
 * TRÌNH KÝ 2 BƯỚC dùng chung khai báo tại config/estimate.php với dự trù hoá chất:
 *   Nháp -> [Trình ký] -> Chờ Phó/Trưởng Phòng ký -> [Ký bước 1] -> Chờ Ban Giám Đốc ký
 *        -> [Ký bước 2] -> Đã phê duyệt -> chuyển bộ phận Cung Ứng tiếp nhận.
 *   Bị từ chối ở bước nào cũng quay về "Bị từ chối", sửa lại rồi trình ký lại từ đầu.
 *
 * Phiếu chỉ khoá (deActive) chứ không xoá cứng để mã phiếu không bị cấp lại.
 */
class StandardEstimateController extends Controller
{
    use EstimateItemCancel;
    use EstimateSignFlow;
    use VerifiesSignature;

    private const TABLE = 'standard_estimates';

    private const ITEM_TABLE = 'standard_estimate_items';

    private const AMOUNT_TABLE = 'standard_estimate_item_amounts';

    private const HISTORY_TABLE = 'standard_estimate_histories';

    /** Quy trình ký duyệt động - xem App\Http\Controllers\Concerns\EstimateSignFlow. */
    private const SIGN_TABLE = 'standard_estimate_signs';

    private const ESTIMATE_FK = 'standard_estimate_id';

    private const EST_ROUTE = 'pages.estimate.standardEstimate.';

    private const SIGN_PERMISSION = 'estimate_standard_sign';

    private const LABEL = 'phiếu dự trù chất chuẩn';

    private const ITEM_LABEL = 'chất chuẩn dự trù';

    /** Mã phiếu: DTC + department_id + năm + tháng(2) + số thứ tự 3 chữ số. */
    private const CODE_PREFIX = 'DTC';

    private const SEQ_LENGTH = 3;

    /** Chỉ hai trạng thái này mới được sửa đầu phiếu và chi tiết mặt hàng. */
    private const EDITABLE_STATUSES = ['draft', 'rejected'];

    /**
     * Huỷ mục cần xác nhận 2 bên - xem App\Http\Controllers\Concerns\EstimateItemCancel.
     * Danh mục không khai bộ phận mua hàng nên bộ phận mua hàng luôn là Cung Ứng.
     */
    protected function cancelConfig(): array
    {
        return [
            'item_fk' => 'standard_estimate_id',
            'chat_type' => 'standard',
            'category_table' => 'standard_categories',
            'name_table' => 'standard_names',
            'name_fk' => 'chem_names_id',
            'manual_name' => 'standard_name',
            'purchasing_col' => null,
        ];
    }

    /* ==========================================================
     |  DANH SÁCH PHIẾU DỰ TRÙ CỦA PHÒNG BAN
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

        $signRows = $this->signRows($datas->pluck('id'));

        $datas->each(function ($row) use ($signRows) {
            $signs = $signRows->get($row->id, collect());
            $row->pending_sign = $this->pendingSign($row, $signs);
            $row->can_sign = $row->pending_sign ? $this->canSignRow($row->pending_sign) : false;
        });

        $trackedItems = self::trackedItems($departmentId);

        $inbox = $this->approvalInboxData();

        // Tab "Bộ phận mua hàng" (xác nhận huỷ 2 bên) - chỉ hiện với Cung Ứng
        $purchasingItems = $this->purchasingItems($departmentId);

        $tabs = ['list', 'tracking', 'inbox', 'purchasing'];
        $activeTab = in_array($request->query('tab'), $tabs, true)
            ? $request->query('tab')
            : (in_array(session('activeTab'), $tabs, true) ? session('activeTab') : 'list');

        session()->put(['title' => 'DỰ TRÙ - DỰ TRÙ CHẤT CHUẨN']);

        return view('pages.estimate.StandardEstimate.list', [
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
            'activeTab' => $activeTab,
            'purchasingItems' => $purchasingItems,
            'showApprovalInbox' => $inbox['show'],
            'inboxRequests' => $inbox['requests'],
            'inboxItems' => $inbox['items'],
            'inboxSigns' => $inbox['signs'],
            'inboxBadgeCount' => $inbox['badge'],
        ]);
    }

    /** Trang chi tiết một phiếu: danh sách chất chuẩn + số lượng theo tháng. */
    public function detail(Request $request)
    {
        $list = DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->select(self::TABLE.'.*', 'deparments.name as department_name', 'deparments.shortName as department_short_name')
            ->where(self::TABLE.'.id', $request->id)
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->first();

        if (! $list) {
            return redirect()->route('pages.estimate.standardEstimate.list')
                ->with('error', 'Không tìm thấy '.self::LABEL.' của phòng ban đang chọn!');
        }

        session()->put(['title' => 'DỰ TRÙ - CHI TIẾT PHIẾU '.$list->code]);

        $signs = $this->signRows([$list->id])->get($list->id, collect());
        $pendingSign = $this->pendingSign($list, $signs);

        return view('pages.estimate.StandardEstimate.detail', [
            // Ngưỡng tồn tối đa + tồn hiện tại của phòng, để khối số lượng cảnh báo dự trù quá nhiều
            'maxStock' => \App\Support\MaxStockWarning::forStandard((int) $list->department_id),
            'list' => $list,
            'items' => self::itemsOf($list->id),
            'histories' => self::historiesOf($list->id),
            'categories' => $this->categoryOptions(),
            // Danh mục chất chuẩn của phòng - khung "Chọn từ danh mục phòng" ở modal thêm chất chuẩn
            'deptCategories' => $this->deptCategoryOptions((int) $list->department_id),
            'units' => $this->unitOptions(),
            'groups' => config('standard.groups'),
            'appStatuses' => config('estimate.app_statuses'),
            'signStatuses' => config('estimate.sign_statuses'),
            'receptionStatuses' => config('estimate.reception_statuses'),
            'signs' => $signs,
            'pendingSign' => $pendingSign,
            'canSignCurrent' => $pendingSign ? $this->canSignRow($pendingSign) : false,
            'signPermission' => self::SIGN_PERMISSION,
            'canEditItems' => $this->editable($list),
            'backRoute' => route('pages.estimate.standardEstimate.list'),
            'estRoute' => 'pages.estimate.standardEstimate.',
        ]);
    }

    /** Nhật ký trình ký của một phiếu, đổ vào modal "Theo dõi trình ký". */
    public function history(Request $request)
    {
        return response()->json(['rows' => self::historiesOf((int) $request->id)]);
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

        // Sinh mã và ghi phiếu trong cùng một transaction để hai người lập cùng lúc không trùng mã
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

        // Không ghi lịch sử "Tạo phiếu" khi đang nháp
        // self::writeHistory($result['id'], 'Tạo phiếu', null, null, 'draft', 'Lập '.self::LABEL.' mã '.$result['code'].'.');
        // AuditTrialController::log('Thêm mới', self::TABLE, $result['id'], 'NA', 'Lập '.self::LABEL.': '.$result['code']);

        return redirect()->route('pages.estimate.standardEstimate.detail', ['id' => $result['id']])
            ->with('success', 'Đã tạo '.self::LABEL.' mã '.$result['code'].'! Hãy khai các chất chuẩn cần dự trù.');
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

        $payload = $this->payload($request);

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        if ($current->app_status !== 'draft') {
            AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, $payload['code'] ?? $current->code);
            self::writeHistory($current->id, 'Sửa phiếu', null, $current->app_status, $current->app_status, 'Cập nhật thông tin phiếu dự trù.');
        }

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
    }

    public function destroy(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần huỷ!');
        }

        if (!in_array($current->app_status, ['draft', 'rejected'])) {
            return redirect()->back()->with('error', 'Chỉ có thể huỷ phiếu chưa trình ký!');
        }


        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => 'cancelled',
            'cancel_reason' => $request->cancel_reason,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Huỷ phiếu', self::TABLE, $current->id, 'Phiếu: ' . $current->code, 'Lý do: ' . $request->cancel_reason);

        return redirect()->back()->with('success', 'Đã huỷ ' . self::LABEL . ' thành công!');
    }

    /* ==========================================================
     |  MẶT HÀNG DỰ TRÙ + SỐ LƯỢNG THEO THÁNG
     ========================================================== */

    /**
     * Thêm NHIỀU chất chuẩn một lần từ modal dạng bảng: mỗi dòng items[i] là một chất chuẩn,
     * các tháng cần dùng là cột chung periods[k], số lượng của dòng ở items[i][amounts][k] và
     * dùng chung đơn vị items[i][unit_id]. Dòng bỏ trống hoàn toàn thì bỏ qua.
     */
    public function storeItem(Request $request)
    {
        $list = $this->findOwn($request->standard_estimate_id);

        if (! $list) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần khai mặt hàng!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã trình ký nên không thêm mặt hàng được nữa!');
        }

        $this->pruneEmptyItemRows($request);

        $validator = Validator::make($request->all(), $this->batchItemRules(), $this->batchItemMessages());

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

        $itemIds = DB::transaction(function () use ($lines, $periods, $list) {
            $ids = [];

            foreach ($lines as $line) {
                $itemId = DB::table(self::ITEM_TABLE)->insertGetId($this->itemPayloadFrom($line) + [
                    'standard_estimate_id' => $list->id,
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

            // Số lượng theo tháng luôn ghi lại toàn bộ: xoá dòng cũ rồi ghi dòng mới
            DB::table(self::AMOUNT_TABLE)->where('standard_estimate_item_id', $item->id)->update(['active' => 0]);

            $this->saveAmounts($item->id, $request);
        });

        if ($list->app_status !== 'draft') {
            AuditTrialController::log('Cập nhật', self::ITEM_TABLE, $item->id, 'Phiếu '.$list->code, 'Sửa '.self::ITEM_LABEL);
            self::writeHistory($list->id, 'Sửa mặt hàng', null, $list->app_status, $list->app_status, 'Chỉnh sửa mặt hàng trong phiếu.');
        }

        return redirect()->back()->with('success', 'Cập nhật '.self::ITEM_LABEL.' thành công!');
    }

    /**
     * Xoá hẳn một mặt hàng khỏi phiếu.
     *
     * Đây là ngoại lệ duy nhất của quy tắc "chỉ khoá, không xoá": dòng mặt hàng chỉ là
     * nội dung đang soạn của phiếu nháp, chưa phải chứng từ. Chỉ xoá được khi phiếu còn
     * Nháp / Bị từ chối, và mọi lần xoá đều ghi Audit Trail.
     */
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
            DB::table(self::AMOUNT_TABLE)->where('standard_estimate_item_id', $item->id)->update(['active' => 0]);
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
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::ITEM_LABEL . ' cần cập nhật!');
        }

        if ($list->app_status !== 'approved') {
            return redirect()->back()->with('error', 'Phiếu chưa được duyệt nên không thể cập nhật trạng thái mục!');
        }

        if (str_starts_with($request->action, 'cancel')) {
            return $this->applyCancel($item, 'requester', $request->action, $request->cancel_reason);
        }

        if ($request->action === 'complete') {
            if ($this->cancelPending($item)) {
                return redirect()->back()->with('error', 'Mục đang chờ xác nhận huỷ, xử lý đề nghị huỷ trước khi xác nhận hoàn thành!');
            }

            $updateData = ['fulfilled_date' => now(), 'fulfilled_by' => $this->actor(), 'status_id' => 1];
            $logMessage = 'Đã xác nhận hoàn thành (giao hàng).';
        } else {
            $updateData = ['fulfilled_date' => null, 'fulfilled_by' => null, 'status_id' => 1] + $this->clearCancel();
            $logMessage = 'Đã khôi phục lại trạng thái mặt hàng.';
        }

        DB::transaction(function () use ($item, $list, $updateData, $logMessage) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update($updateData);
            $this->itemSystemChat($item->id, $logMessage);
            $this->afterItemStatusChange($list);
        });

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái ' . self::ITEM_LABEL . '!');
    }

    /** Mọi mục còn hiệu lực đã giao hết thì đánh dấu phiếu hoàn tất; ngược lại mở lại. */
    protected function afterItemStatusChange($list): void
    {
        $allItems = DB::table(self::ITEM_TABLE)->where('standard_estimate_id', $list->id)->where('active', 1)->get();
        $allCompleted = true;
        $hasActive = false;
        foreach ($allItems as $i) {
            if ($i->status_id != 0) {
                $hasActive = true;
                if (empty($i->fulfilled_date)) {
                    $allCompleted = false;
                    break;
                }
            }
        }

        if ($allCompleted && $hasActive) {
            DB::table(self::TABLE)->where('id', $list->id)->update([
                'reception_status' => 'completed',
                'completed_at' => now(),
                'completed_by' => $this->actor()
            ]);
        } elseif ($list->reception_status === 'completed') {
            DB::table(self::TABLE)->where('id', $list->id)->update([
                'reception_status' => 'received',
                'completed_at' => null,
                'completed_by' => null
            ]);
        }
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
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ' . self::ITEM_LABEL . ' cần cập nhật!']);
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
                    'item_type' => 'standard',
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
            ->where('item_type', 'standard')
            ->where('type', 'history_promised_date')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($chat) {
                return [
                    'content' => $chat->content,
                    'user_name' => $chat->user_name,
                    'created_at_formatted' => \Carbon\Carbon::parse($chat->created_at)->format('H:i d/m/Y')
                ];
            });

        return response()->json(['success' => true, 'histories' => $histories]);
    }

    public function storeItemChat(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'content' => 'required|string|max:1000'
        ]);

        [$item, $list] = $this->findItem($request->item_id);

        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ' . self::ITEM_LABEL]);
        }

        $chatId = DB::table('estimate_item_chats')->insertGetId([
            'item_id' => $item->id,
            'item_type' => 'standard',
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

        if (! DB::table(self::ITEM_TABLE)->where('standard_estimate_id', $current->id)->where('active', 1)->exists()) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' chưa có chất chuẩn nào, chưa trình ký được!');
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

    /** Từ chối ở bước đang chờ ký, phiếu quay về "Bị từ chối" để sửa lại rồi trình ký lại. */
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
     * Mặt hàng của một phiếu, kèm số lượng theo từng tháng.
     *
     * Gom một truy vấn cho mặt hàng và một truy vấn cho số lượng rồi ghép trong PHP
     * để không phải hỏi DB theo từng dòng.
     */
    public static function trackedItems(int $departmentId)
    {
        $items = DB::table(self::ITEM_TABLE)
            ->join(self::TABLE, self::ITEM_TABLE.'.standard_estimate_id', '=', self::TABLE.'.id')
            ->leftJoin('standard_categories', self::ITEM_TABLE.'.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => \App\Support\DepartmentStandard::joinUnit($query, $departmentId, self::ITEM_TABLE.'.category_id'))
            ->select(
                self::ITEM_TABLE.'.*',
                self::TABLE.'.id as list_id',
                self::TABLE.'.code as list_code',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_names.name as category_standard_name',
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

        if ($items->isEmpty()) return $items;

        $amounts = DB::table(self::AMOUNT_TABLE)
            ->leftJoin('units', self::AMOUNT_TABLE.'.unit_id', '=', 'units.id')
            ->select(
                self::AMOUNT_TABLE.'.*',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where(self::AMOUNT_TABLE.'.active', 1)
            ->whereIn(self::AMOUNT_TABLE.'.standard_estimate_item_id', $items->pluck('id')->all())
            ->orderBy(self::AMOUNT_TABLE.'.for_month_year', 'asc')
            ->get()
            ->groupBy('standard_estimate_item_id');

        $allChats = DB::table('estimate_item_chats')
            ->where('item_type', 'standard')
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
            $item->display_name = $item->category_id ? $item->category_standard_name : $item->standard_name;
            // Bảng theo dõi dùng chung hiện category_type ngay sau mã danh mục; chất chuẩn
            // không có "loại" như hoá chất nên mượn ô này cho version danh mục.
            $item->category_type = $item->category_id ? 'v'.$item->category_version : null;

            return $item;
        });
    }

    public static function itemsOf(int $listId)
    {
        // Đơn vị tính nằm ở danh mục chất chuẩn CỦA PHÒNG, nên phải biết phiếu này của phòng nào
        $departmentId = (int) DB::table(self::TABLE)->where('id', $listId)->value('department_id');

        $items = DB::table(self::ITEM_TABLE)
            ->leftJoin('standard_categories', self::ITEM_TABLE.'.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, self::ITEM_TABLE.'.category_id'))
            ->select(
                self::ITEM_TABLE.'.*',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_categories.cas_no as category_cas_no',
                'standard_names.name as category_standard_name',
                'units.short_name as category_unit_short_name',
                'manufacturers.name as category_manufacturer_name'
            )
            ->where(self::ITEM_TABLE.'.standard_estimate_id', $listId)
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
            ->whereIn(self::AMOUNT_TABLE.'.standard_estimate_item_id', $items->pluck('id')->all())
            ->orderBy(self::AMOUNT_TABLE.'.for_month_year', 'asc')
            ->get()
            ->groupBy('standard_estimate_item_id');

        $allChats = DB::table('estimate_item_chats')
            ->where('item_type', 'standard')
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
            // Tên hiển thị: lấy theo danh mục, chất chuẩn ngoài danh mục thì lấy tên tự nhập
            $item->display_name = $item->category_id ? $item->category_standard_name : $item->standard_name;
            $item->group_label = $item->group_key ? StandardCode::groupLabel($item->group_key) : null;

            return $item;
        });
    }

    /** Nhật ký trình ký của một phiếu, mới nhất nằm trên cùng. */
    public static function historiesOf(int $listId): array
    {
        // Nhật ký ghi cả bước trình ký (app_status) lẫn bước tiếp nhận (reception_status)
        $labels = config('estimate.app_statuses') + config('estimate.reception_statuses');
        $steps = config('estimate.sign_steps');
        $stepLabel = function ($step) use ($steps) {
            if (! $step) {
                return '';
            }
            if (is_numeric($step)) {
                return 'Bước '.$step;
            }

            return $steps[$step]['label'] ?? ($step === 'reception' ? 'Cung Ứng' : $step);
        };

        return DB::table(self::HISTORY_TABLE)
            ->where('standard_estimate_id', $listId)
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

    /** Ghi một dòng nhật ký trình ký. */
    public static function writeHistory(int $listId, string $action, ?string $step, ?string $from, ?string $to, ?string $note): void
    {
        DB::table(self::HISTORY_TABLE)->insert([
            'standard_estimate_id' => $listId,
            'action' => $action,
            'step' => $step,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'created_by' => \App\Support\Signer::actor(),
            'created_at' => now(),
        ]);
    }

    /** Số mặt hàng của từng phiếu: [standard_estimate_id => số dòng]. */
    private function itemCounts(array $listIds): array
    {
        if (! $listIds) {
            return [];
        }

        return DB::table(self::ITEM_TABLE)
            ->select('standard_estimate_id', DB::raw('COUNT(*) as total'))
            ->whereIn('standard_estimate_id', $listIds)
            ->where('active', 1)
            ->groupBy('standard_estimate_id')
            ->pluck('total', 'standard_estimate_id')
            ->all();
    }

    /**
     * Bỏ những dòng số lượng để trống trước khi kiểm tra dữ liệu.
     *
     * Modal thêm mặt hàng mở sẵn 3 tháng liên tiếp tính từ tháng dự trù, tháng nào không
     * cần thì người dùng để trống ô số lượng - những dòng đó bị loại ở đây thay vì báo lỗi.
     * Trống hết thì rơi vào luật "amounts required|min:1" và báo lỗi bình thường.
     */
    private function pruneEmptyAmounts(Request $request): void
    {
        $rows = array_values(array_filter(
            (array) $request->input('amounts', []),
            fn ($line) => trim((string) ($line['amount'] ?? '')) !== ''
        ));

        $request->merge(['amounts' => $rows]);
    }

    /**
     * Modal thêm nhiều chất chuẩn: bỏ các dòng trống hoàn toàn (chưa chọn/gõ chất chuẩn, không
     * số lượng, không thông tin) rồi đánh số lại, để lỗi validate trỏ đúng dòng đang hiện.
     */
    private function pruneEmptyItemRows(Request $request): void
    {
        $rows = [];

        foreach ((array) $request->input('items', []) as $line) {
            $line = (array) $line;
            $line['amounts'] = array_map(fn ($value) => trim((string) $value), (array) ($line['amounts'] ?? []));

            $isBlank = ! array_filter($line['amounts'], fn ($value) => $value !== '')
                && trim((string) ($line['category_id'] ?? '')) === ''
                && trim((string) ($line['standard_name'] ?? '')) === ''
                && trim((string) ($line['technical_information'] ?? '')) === ''
                && trim((string) ($line['purpose'] ?? '')) === ''
                && trim((string) ($line['expected_delivery_date'] ?? '')) === '';

            if (! $isBlank) {
                $rows[] = $line;
            }
        }

        $request->merge(['items' => $rows]);
    }

    /** Ghi lại các dòng số lượng theo tháng của một mặt hàng (form sửa 1 mặt hàng). */
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
                'standard_estimate_item_id' => $itemId,
                'amount' => (float) $amount,
                'unit_id' => ! empty($line['unit_id']) ? (int) $line['unit_id'] : null,
                // Ô nhập dạng "2026-09" -> lưu ngày đầu tháng
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
     * Mã phiếu kế tiếp: DTC + department_id + năm + tháng(2) + số thứ tự 3 chữ số.
     *
     * Số thứ tự đếm riêng cho từng bộ (phòng ban, tháng, năm). Phiếu chỉ khoá chứ không
     * xoá nên mã không bị dùng lại.
     */
    private function nextCode(int $departmentId): string
    {
        $shortName = DB::table('deparments')->where('id', $departmentId)->value('shortName') ?? 'UNK';
        $datePart = date('ymd'); // yymmdd
        $prefix = $shortName . $datePart . '.';

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

        return $prefix . str_pad((string) (($next ?? 0) + 1), 2, '0', STR_PAD_LEFT);
    }

    /** Danh mục chất chuẩn đã duyệt và đang hoạt động mới được chọn để dự trù. */
    private function categoryOptions()
    {
        $departmentId = $this->departmentId();

        return DB::table('standard_categories')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            // Đơn vị hiện trên ô chọn là đơn vị PHÒNG ĐANG CHỌN đã khai cho chất chuẩn đó
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_categories.id'))
            ->select(
                'standard_categories.id',
                'standard_categories.code',
                'standard_categories.version',
                'standard_names.name as standard_name',
                'units.short_name as unit_short_name'
            )
            ->where('standard_categories.status_id', 1)
            ->where('standard_categories.app_status', 'approved')
            ->orderBy('standard_categories.code', 'asc')
            ->get();
    }

    /**
     * Chất chuẩn phòng đã khai ở tab "Chất Chuẩn Của Phòng" (còn hoạt động, danh mục chung đã
     * duyệt) - khung "Chọn từ danh mục phòng" của modal thêm chất chuẩn, kèm nhóm chuẩn, đơn vị
     * và ngưỡng tồn tối thiểu / tối đa của phòng.
     */
    private function deptCategoryOptions(int $departmentId)
    {
        $table = DepartmentStandard::TABLE;

        return DB::table($table)
            ->join('standard_categories', $table.'.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', $table.'.unit_id', '=', 'units.id')
            ->select(
                'standard_categories.id',
                'standard_categories.code',
                'standard_categories.version',
                'standard_categories.groups',
                'standard_categories.cas_no',
                'standard_names.name as standard_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                $table.'.unit_id',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                $table.'.min_stock',
                $table.'.max_stock'
            )
            ->where($table.'.department_id', $departmentId)
            ->where($table.'.status_id', 1)
            ->where('standard_categories.status_id', 1)
            ->where('standard_categories.app_status', 'approved')
            ->orderBy('standard_names.name', 'asc')
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

    /** Phiếu của đúng phòng ban đang chọn, tránh sửa nhầm phiếu phòng ban khác. */
    private function findOwn($id)
    {
        return DB::table(self::TABLE)
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    /** Một mặt hàng kèm phiếu chứa nó: [item, list]. */
    private function findItem($id): array
    {
        $item = DB::table(self::ITEM_TABLE)->where('id', $id)->where('active', 1)->first();

        if (! $item) {
            return [null, null];
        }

        $list = $this->findOwn($item->standard_estimate_id);

        return $list ? [$item, $list] : [null, null];
    }

    /** Chỉ phiếu Nháp / Bị từ chối và chưa bị khoá mới sửa được. */
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
            'category_id' => ['required_if:source,category', 'nullable', 'exists:standard_categories,id'],
            'standard_name' => ['required_if:source,manual', 'nullable', 'max:255'],
            // Nhóm chuẩn mong muốn: bắt buộc khi khai chất chuẩn ngoài danh mục, vì
            // Cung Ứng cần biết mua chuẩn chính hay chuẩn tạp.
            'group_key' => [
                'required_if:source,manual',
                'nullable',
                Rule::in(array_keys(config('standard.groups'))),
            ],
            'technical_information' => ['nullable', 'max:1000'],
            'purpose' => ['nullable', 'max:1000'],
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
            'standard_name' => $fromCategory ? null : $this->nullIfBlank($line['standard_name'] ?? null),
            'group_key' => $this->nullIfBlank($line['group_key'] ?? null),
            'technical_information' => $this->nullIfBlank($line['technical_information'] ?? null),
            'purpose' => $this->nullIfBlank($line['purpose'] ?? null),
            'expected_delivery_date' => $this->nullIfBlank($line['expected_delivery_date'] ?? null),
        ];
    }

    /** Modal thêm nhiều chất chuẩn: items[i] là một chất chuẩn, periods[k] là cột tháng chung. */
    private function batchItemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.source' => ['required', 'in:category,manual'],
            'items.*.category_id' => ['required_if:items.*.source,category', 'nullable', 'exists:standard_categories,id'],
            'items.*.standard_name' => ['required_if:items.*.source,manual', 'nullable', 'max:255'],
            // Nhóm chuẩn mong muốn: bắt buộc với chất chuẩn ngoài danh mục (Cung Ứng cần biết
            // mua chuẩn chính hay chuẩn tạp)
            'items.*.group_key' => [
                'required_if:items.*.source,manual',
                'nullable',
                Rule::in(array_keys(config('standard.groups'))),
            ],
            'items.*.technical_information' => ['nullable', 'max:1000'],
            'items.*.purpose' => ['nullable', 'max:1000'],
            'items.*.expected_delivery_date' => ['nullable', 'date'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.amounts' => ['array'],
            'items.*.amounts.*' => ['nullable', 'numeric', 'min:0.0001'],
            'periods' => ['required', 'array', 'min:1'],
            'periods.*' => ['nullable', 'date_format:Y-m', 'distinct'],
        ];
    }

    private function batchItemMessages(): array
    {
        return [
            'items.required' => 'Vui lòng khai ít nhất một chất chuẩn.',
            'items.min' => 'Vui lòng khai ít nhất một chất chuẩn.',
            'items.max' => 'Mỗi lần thêm tối đa 200 chất chuẩn.',
            'items.*.source.required' => 'Vui lòng chọn nguồn chất chuẩn.',
            'items.*.source.in' => 'Nguồn chất chuẩn không hợp lệ.',
            'items.*.category_id.required_if' => 'Vui lòng chọn chất chuẩn trong danh mục.',
            'items.*.category_id.exists' => 'Chất chuẩn được chọn không tồn tại trong danh mục.',
            'items.*.standard_name.required_if' => 'Vui lòng nhập tên chất chuẩn ngoài danh mục.',
            'items.*.standard_name.max' => 'Tên chất chuẩn tối đa 255 ký tự.',
            'items.*.group_key.required_if' => 'Vui lòng chọn nhóm chuẩn mong muốn cho chất chuẩn ngoài danh mục.',
            'items.*.group_key.in' => 'Nhóm chuẩn không hợp lệ.',
            'items.*.technical_information.max' => 'Thông tin kỹ thuật tối đa 1000 ký tự.',
            'items.*.purpose.max' => 'Mục đích sử dụng tối đa 1000 ký tự.',
            'items.*.expected_delivery_date.date' => 'Ngày mong muốn giao không hợp lệ.',
            'items.*.unit_id.required' => 'Vui lòng chọn đơn vị tính.',
            'items.*.unit_id.exists' => 'Đơn vị tính không hợp lệ.',
            'items.*.amounts.*.numeric' => 'Số lượng dự trù phải là số.',
            'items.*.amounts.*.min' => 'Số lượng dự trù phải lớn hơn 0.',
            'periods.required' => 'Vui lòng khai ít nhất một tháng cần dùng.',
            'periods.*.date_format' => 'Tháng cần dùng không hợp lệ.',
            'periods.*.distinct' => 'Các cột tháng cần dùng bị trùng nhau.',
        ];
    }

    private function itemMessages(): array
    {
        return [
            'source.required' => 'Vui lòng chọn nguồn chất chuẩn.',
            'source.in' => 'Nguồn chất chuẩn không hợp lệ.',
            'category_id.required_if' => 'Vui lòng chọn chất chuẩn trong danh mục.',
            'category_id.exists' => 'Chất chuẩn được chọn không tồn tại trong danh mục.',
            'standard_name.required_if' => 'Vui lòng nhập tên chất chuẩn ngoài danh mục.',
            'standard_name.max' => 'Tên chất chuẩn tối đa 255 ký tự.',
            'group_key.required_if' => 'Vui lòng chọn nhóm chuẩn mong muốn cho chất chuẩn ngoài danh mục.',
            'group_key.in' => 'Nhóm chuẩn không hợp lệ.',
            'technical_information.max' => 'Thông tin kỹ thuật tối đa 1000 ký tự.',
            'purpose.max' => 'Mục đích sử dụng tối đa 1000 ký tự.',
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
