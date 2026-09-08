<?php

namespace App\Http\Controllers\Pages\Estimate;

use App\Http\Controllers\Concerns\EstimateSignFlow;
use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\ActiveIngredientThreshold;
use App\Support\CompanyContext;
use App\Support\DepartmentChemical;
use App\Support\MixtureHazardThreshold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * DỰ TRÙ - DỰ TRÙ HOÁ CHẤT
 *
 * Phòng ban lập phiếu dự trù hoá chất cho một tháng/năm (estimate_lists), khai từng
 * mặt hàng cần dùng (estimate_items) và số lượng cần cho từng tháng (estimate_item_amounts).
 *
 * Mặt hàng có thể chọn từ Danh Mục Hoá Chất, hoặc tự gõ tên khi hoá chất chưa có
 * trong danh mục (estimate_items.category_id = NULL).
 *
 * TRÌNH KÝ 2 BƯỚC - khai báo tại config/estimate.php:
 *   Nháp -> [Trình ký] -> Chờ Phó/Trưởng Phòng ký -> [Ký bước 1] -> Chờ Ban Giám Đốc ký
 *        -> [Ký bước 2] -> Đã phê duyệt -> chuyển bộ phận Cung Ứng tiếp nhận.
 *   Bị từ chối ở bước nào cũng quay về "Bị từ chối", sửa lại rồi trình ký lại từ đầu.
 *   Mọi lần đổi trạng thái ghi vào estimate_list_histories để theo dõi ngay trên danh sách.
 *
 * Phiếu chỉ khoá (deActive) chứ không xoá cứng để mã phiếu không bị cấp lại.
 */
class ChemicalEstimateController extends Controller
{
    use EstimateSignFlow;
    use VerifiesSignature;

    private const TABLE = 'chemical_estimates';

    private const ITEM_TABLE = 'chemical_estimate_items';

    private const AMOUNT_TABLE = 'chemical_estimate_item_amounts';

    private const HISTORY_TABLE = 'chemical_estimate_histories';

    /** Quy trình ký duyệt động - xem App\Http\Controllers\Concerns\EstimateSignFlow. */
    private const SIGN_TABLE = 'chemical_estimate_signs';

    private const ESTIMATE_FK = 'chemical_estimate_id';

    private const EST_ROUTE = 'pages.estimate.chemicalEstimate.';

    private const SIGN_PERMISSION = 'estimate_chemical_sign';

    private const LABEL = 'phiếu dự trù hoá chất';

    private const ITEM_LABEL = 'mặt hàng dự trù';

    /** Mã phiếu: DT + department_id + năm + tháng(2) + số thứ tự 3 chữ số. */
    private const CODE_PREFIX = 'DT';

    private const SEQ_LENGTH = 3;

    /** Chỉ hai trạng thái này mới được sửa đầu phiếu và chi tiết mặt hàng. */
    private const EDITABLE_STATUSES = ['draft', 'rejected'];

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

        $tabs = ['list', 'tracking', 'inbox'];
        $activeTab = in_array($request->query('tab'), $tabs, true)
            ? $request->query('tab')
            : (in_array(session('activeTab'), $tabs, true) ? session('activeTab') : 'list');

        session()->put(['title' => 'DỰ TRÙ - DỰ TRÙ HOÁ CHẤT']);

        return view('pages.estimate.ChemicalEstimate.list', [
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
            'showApprovalInbox' => $inbox['show'],
            'inboxRequests' => $inbox['requests'],
            'inboxItems' => $inbox['items'],
            'inboxSigns' => $inbox['signs'],
            'inboxBadgeCount' => $inbox['badge'],
        ]);
    }

    /**
     * Trang chi tiết một phiếu: danh sách mặt hàng + số lượng theo tháng.
     * Dùng chung view với màn Tiếp Nhận Dự Trù, khác nhau ở quyền sửa.
     */
    public function detail(Request $request)
    {
        $list = DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->select(self::TABLE.'.*', 'deparments.name as department_name', 'deparments.shortName as department_short_name')
            ->where(self::TABLE.'.id', $request->id)
            ->where(self::TABLE.'.department_id', $this->departmentId())
            ->first();

        if (! $list) {
            return redirect()->route('pages.estimate.chemicalEstimate.list')
                ->with('error', 'Không tìm thấy '.self::LABEL.' của phòng ban đang chọn!');
        }

        session()->put(['title' => 'DỰ TRÙ - CHI TIẾT PHIẾU '.$list->code]);

        $signs = $this->signRows([$list->id])->get($list->id, collect());
        $pendingSign = $this->pendingSign($list, $signs);

        return view('pages.estimate.shared.detail', [
            'list' => $list,
            'items' => self::itemsOf($list->id),
            'histories' => self::historiesOf($list->id),
            'categories' => $this->categoryOptions($list->department_id),
            'categoryLevels' => $this->categoryThresholdLevels(CompanyContext::resolveForDepartment($list->department_id)),
            // Nhóm NĐ 24/2026 suy tự động theo mã danh mục, hiển thị ở cột "Nhóm Hoá Chất"
            'classificationCodes' => \App\Support\ChemicalClassification::codesByCategory(),
            'classificationLabels' => \App\Support\ChemicalClassification::labels(),
            'units' => $this->unitOptions(),
            'appStatuses' => config('estimate.app_statuses'),
            'signStatuses' => config('estimate.sign_statuses'),
            'receptionStatuses' => config('estimate.reception_statuses'),
            'signs' => $signs,
            'pendingSign' => $pendingSign,
            'canSignCurrent' => $pendingSign ? $this->canSignRow($pendingSign) : false,
            'signPermission' => self::SIGN_PERMISSION,
            'canEditItems' => $this->editable($list),
            'backRoute' => route('pages.estimate.chemicalEstimate.list'),
            'estRoute' => 'pages.estimate.chemicalEstimate.',
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

        return redirect()->route('pages.estimate.chemicalEstimate.detail', ['id' => $result['id']])
            ->with('success', 'Đã tạo '.self::LABEL.' mã '.$result['code'].'! Hãy khai các mặt hàng cần dự trù.');
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

    public function storeItem(Request $request)
    {
        $list = $this->findOwn($request->estimate_list_id);

        if (! $list) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần khai mặt hàng!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã trình ký nên không thêm mặt hàng được nữa!');
        }

        $this->pruneEmptyAmounts($request);

        $validator = Validator::make($request->all(), $this->itemRules(), $this->itemMessages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'itemCreateErrors')->withInput();
        }

        $itemId = DB::transaction(function () use ($request, $list) {
            $itemId = DB::table(self::ITEM_TABLE)->insertGetId($this->itemPayload($request) + [
                'estimate_list_id' => $list->id,
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->saveAmounts($itemId, $request);

            return $itemId;
        });

        if ($list->app_status !== 'draft') {
            AuditTrialController::log('Thêm mới', self::ITEM_TABLE, $itemId, 'NA', 'Thêm '.self::ITEM_LABEL.' vào phiếu '.$list->code);
            self::writeHistory($list->id, 'Thêm mặt hàng', null, $list->app_status, $list->app_status, 'Thêm mặt hàng vào phiếu.');
        }

        $redirect = redirect()->back()->with('success', 'Đã thêm '.self::ITEM_LABEL.' vào phiếu '.$list->code.'!');

        if ($warnings = $this->thresholdWarnings($itemId)) {
            $redirect->with('warning', implode(' — ', array_column($warnings, 'message')));
        }

        return $redirect;
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
            DB::table(self::AMOUNT_TABLE)->where('estimate_item_id', $item->id)->update(['active' => 0]);

            $this->saveAmounts($item->id, $request);
        });

        if ($list->app_status !== 'draft') {
            AuditTrialController::log('Cập nhật', self::ITEM_TABLE, $item->id, 'Phiếu '.$list->code, 'Sửa '.self::ITEM_LABEL);
            self::writeHistory($list->id, 'Sửa mặt hàng', null, $list->app_status, $list->app_status, 'Chỉnh sửa mặt hàng trong phiếu.');
        }

        $redirect = redirect()->back()->with('success', 'Cập nhật '.self::ITEM_LABEL.' thành công!');

        if ($warnings = $this->thresholdWarnings($item->id)) {
            $redirect->with('warning', implode(' — ', array_column($warnings, 'message')));
        }

        return $redirect;
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
            DB::table(self::AMOUNT_TABLE)->where('estimate_item_id', $item->id)->update(['active' => 0]);
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

    public function updateItemStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'action' => 'required|in:complete,cancel,undo'
        ]);

        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::ITEM_LABEL . ' cần cập nhật!');
        }

        if ($list->app_status !== 'approved') {
            return redirect()->back()->with('error', 'Phiếu chưa được duyệt nên không thể cập nhật trạng thái mục!');
        }

        $updateData = [];
        $logMessage = '';
        if ($request->action === 'complete') {
            $updateData = ['fulfilled_date' => now(), 'fulfilled_by' => $this->actor(), 'status_id' => 1];
            $logMessage = 'Đã xác nhận hoàn thành (giao hàng).';
        } elseif ($request->action === 'cancel') {
            $updateData = ['fulfilled_date' => null, 'fulfilled_by' => null, 'status_id' => 0, 'cancel_reason' => $request->cancel_reason];
            $logMessage = 'Đã huỷ dự trù mặt hàng. Lý do: ' . $request->cancel_reason;
        } else {
            $updateData = ['fulfilled_date' => null, 'fulfilled_by' => null, 'status_id' => 1, 'cancel_reason' => null];
            $logMessage = 'Đã khôi phục lại trạng thái mặt hàng.';
        }

        DB::transaction(function () use ($item, $list, $updateData, $logMessage) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update($updateData);
            
            DB::table('estimate_item_chats')->insert([
                'item_id' => $item->id,
                'item_type' => 'chemical',
                'user_name' => $this->actor(),
                'content' => $logMessage,
                'type' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $allItems = DB::table(self::ITEM_TABLE)->where('estimate_list_id', $list->id)->where('active', 1)->get();
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
        });

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái ' . self::ITEM_LABEL . '!');
    }

    public function updatePromisedDate(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'promised_date' => 'nullable|date'
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
        $actor = $this->actor();
        $historyAdded = false;

        DB::transaction(function () use ($item, $request, $oldDate, $newDate, $actor, &$historyAdded) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update([
                'promised_date' => $request->promised_date,
            ]);

            if ($oldDate !== $newDate) {
                DB::table('estimate_item_chats')->insert([
                    'item_id' => $item->id,
                    'item_type' => 'chemical',
                    'user_name' => $actor,
                    'content' => "Cập nhật ngày hẹn đáp ứng từ [{$oldDate}] thành [{$newDate}]",
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
            ->where('item_type', 'chemical')
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
            'item_type' => 'chemical',
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

        if (! DB::table(self::ITEM_TABLE)->where('estimate_list_id', $current->id)->where('active', 1)->exists()) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' chưa có mặt hàng nào, chưa trình ký được!');
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
        $companyId = CompanyContext::resolveForDepartment($departmentId);

        $items = DB::table(self::ITEM_TABLE)
            ->join(self::TABLE, self::ITEM_TABLE.'.estimate_list_id', '=', self::TABLE.'.id')
            ->leftJoin('chemical_categories', self::ITEM_TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', 'chemical_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => \App\Support\DepartmentChemical::joinUnit($query, $departmentId, self::ITEM_TABLE.'.category_id'))
            ->select(
                self::ITEM_TABLE.'.*',
                self::TABLE.'.id as list_id',
                self::TABLE.'.code as list_code',
                'chemical_categories.code as category_code',
                'chemical_categories.type as category_type',
                'chem_names.name as category_chem_name',
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
            ->whereIn(self::AMOUNT_TABLE.'.estimate_item_id', $items->pluck('id')->all())
            ->orderBy(self::AMOUNT_TABLE.'.for_month_year', 'asc')
            ->get()
            ->groupBy('estimate_item_id');

        $allChats = DB::table('estimate_item_chats')
            ->where('item_type', 'chemical')
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

        return $items->map(function ($item) use ($amounts, $chats, $historyCounts, $companyId) {
            $item->amounts = ($amounts[$item->id] ?? collect())->values();
            $item->chats = ($chats[$item->id] ?? collect())->values();
            $item->history_count = $historyCounts[$item->id] ?? 0;
            $item->display_name = $item->category_id ? $item->category_chem_name : $item->chem_name;
            $item->threshold_warnings = $item->category_id
                ? self::computeThresholdWarnings((int) $item->category_id, $item->amounts, $companyId)
                : [];

            return $item;
        });
    }

    public static function itemsOf(int $listId)
    {
        // Đơn vị tính nằm ở danh mục hoá chất CỦA PHÒNG, nên phải biết phiếu này của phòng nào
        $departmentId = (int) DB::table(self::TABLE)->where('id', $listId)->value('department_id');
        $companyId = CompanyContext::resolveForDepartment($departmentId);

        $items = DB::table(self::ITEM_TABLE)
            ->leftJoin('chemical_categories', self::ITEM_TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', 'chemical_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, self::ITEM_TABLE.'.category_id'))
            ->select(
                self::ITEM_TABLE.'.*',
                'chemical_categories.code as category_code',
                'chemical_categories.type as category_type',
                'chem_names.name as category_chem_name',
                'units.short_name as category_unit_short_name',
                'manufacturers.name as category_manufacturer_name'
            )
            ->where(self::ITEM_TABLE.'.estimate_list_id', $listId)
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
            ->whereIn(self::AMOUNT_TABLE.'.estimate_item_id', $items->pluck('id')->all())
            ->orderBy(self::AMOUNT_TABLE.'.for_month_year', 'asc')
            ->get()
            ->groupBy('estimate_item_id');

        $allChats = DB::table('estimate_item_chats')
            ->where('item_type', 'chemical')
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

        return $items->map(function ($item) use ($amounts, $chats, $historyCounts, $companyId) {
            $item->amounts = ($amounts[$item->id] ?? collect())->values();
            $item->chats = ($chats[$item->id] ?? collect())->values();
            $item->history_count = $historyCounts[$item->id] ?? 0;
            // Tên hiển thị: lấy theo danh mục, hoá chất ngoài danh mục thì lấy tên tự nhập
            $item->display_name = $item->category_id ? $item->category_chem_name : $item->chem_name;
            // Cảnh báo ngưỡng PL IV hiển thị thường trực cho người ký duyệt / bộ phận tiếp nhận
            $item->threshold_warnings = $item->category_id
                ? self::computeThresholdWarnings((int) $item->category_id, $item->amounts, $companyId)
                : [];

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
            ->where('estimate_list_id', $listId)
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
            'estimate_list_id' => $listId,
            'action' => $action,
            'step' => $step,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'created_by' => \App\Support\Signer::actor(),
            'created_at' => now(),
        ]);
    }

    /**
     * Kiểm tra cảnh báo ngưỡng PL IV theo hoá chất + số lượng đang gõ trong modal Thêm/Sửa
     * mặt hàng, TRƯỚC KHI lưu - JS gọi mỗi khi đổi hoá chất hoặc đổi số lượng/đơn vị
     * (xem pages/estimate/shared/assets.blade.php).
     */
    public function checkThreshold(Request $request)
    {
        $categoryId = (int) $request->category_id;

        if (! $categoryId) {
            return response()->json(['warnings' => []]);
        }

        $amountRows = collect((array) $request->input('amounts', []))
            ->map(fn ($line) => (object) [
                'amount' => (float) ($line['amount'] ?? 0),
                'unit_id' => (int) ($line['unit_id'] ?? 0),
            ])
            ->filter(fn ($row) => $row->amount > 0 && $row->unit_id > 0)
            ->values();

        return response()->json([
            'warnings' => self::computeThresholdWarnings($categoryId, $amountRows, CompanyContext::currentId()),
        ]);
    }

    /**
     * Cảnh báo (không chặn) khi dự trù một mặt hàng là hoá chất thuộc nhóm phải xây dựng
     * Kế hoạch phòng ngừa (N9 - PL IV Bảng A, N10 - PL IV Bảng B, hoá chất cấm) và lượng
     * dự trù đủ để đẩy tổng tồn trữ toàn công ty chạm/vượt "Ngưỡng khối lượng tồn trữ lớn
     * nhất tại một thời điểm" - Phụ lục IV NĐ 24/2026/NĐ-CP.
     *
     * Lượng dự trù = tổng số lượng các tháng của mặt hàng, quy ra kg (theo hướng thận trọng).
     * Chỉ xét mặt hàng chọn từ Danh Mục Hoá Chất (có category_id); hoá chất tự gõ tay bỏ qua.
     *
     * @return array<int, array{level: string, message: string}>
     */
    private function thresholdWarnings(int $itemId): array
    {
        $item = DB::table(self::ITEM_TABLE)->where('id', $itemId)->first();

        if (! $item || ! $item->category_id) {
            return [];
        }

        $amountRows = DB::table(self::AMOUNT_TABLE)
            ->where('estimate_item_id', $itemId)
            ->where('active', 1)
            ->select('amount', 'unit_id')
            ->get();

        return self::computeThresholdWarnings((int) $item->category_id, $amountRows, CompanyContext::currentId());
    }

    /**
     * Lõi tính cảnh báo ngưỡng PL IV dùng chung cho: cảnh báo sau khi lưu mặt hàng
     * (thresholdWarnings), kiểm tra tức thời trước khi lưu (checkThreshold) và cảnh báo
     * hiển thị thường trực trên phiếu cho người ký duyệt / bộ phận tiếp nhận xem
     * (itemsOf, trackedItems).
     *
     * @param  iterable  $amountRows  các dòng {amount, unit_id}
     * @return array<int, array{level: string, message: string}>
     */
    private static function computeThresholdWarnings(int $categoryId, $amountRows, ?int $companyId): array
    {
        $amountRows = collect($amountRows);

        if ($amountRows->isEmpty()) {
            return [];
        }

        $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');
        $warnings = [];

        // ----- BẢNG A: theo hoạt chất (× % hàm lượng) -----
        $sumA = ActiveIngredientThreshold::sumEstimateKg($categoryId, $amountRows);
        $projA = ActiveIngredientThreshold::projectedForCategory($categoryId, $sumA['kg'], $companyId);

        if ($projA && ($projA->add_ratio >= 1.0 || $projA->projected_ratio >= ActiveIngredientThreshold::warnRatio())) {
            $warnings[] = [
                'level' => $projA->level,
                'message' => 'Hoá chất "'.$projA->ai_name.'" phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố hoá chất '
                    .'(Phụ lục IV NĐ 24/2026/NĐ-CP - Bảng A). Lượng dự trù của mặt hàng ≈ '.$num($projA->add_kg).' kg hoạt chất'
                    .($sumA['unconvertible'] ? ' (chưa gồm dòng dùng đơn vị đếm / thiếu tỉ trọng)' : '')
                    .'; cộng tồn hiện tại toàn công ty '.$num($projA->current_kg).' kg thì tổng ≈ '.$num($projA->projected_kg)
                    .' kg / ngưỡng '.$num($projA->threshold_kg).' kg ('.(int) round($projA->projected_ratio * 100).'%). '
                    .($projA->add_ratio >= 1.0 ? 'Riêng lượng dự trù đã vượt ngưỡng "tồn trữ lớn nhất tại một thời điểm". ' : '')
                    .($projA->level === ActiveIngredientThreshold::LEVEL_EXCEEDED ? 'DỰ KIẾN VƯỢT NGƯỠNG.' : 'Dự kiến chạm ngưỡng cảnh báo.'),
            ];
        }

        // ----- BẢNG B: theo hỗn hợp (tồn thô, không × %) -----
        $sumB = MixtureHazardThreshold::sumEstimateKg($categoryId, $amountRows);
        $projB = MixtureHazardThreshold::projectedForCategory($categoryId, $sumB['kg'], $companyId);

        if ($projB && ($projB->add_ratio >= 1.0 || $projB->projected_ratio >= MixtureHazardThreshold::warnRatio())) {
            $warnings[] = [
                'level' => $projB->level,
                'message' => 'Hỗn hợp "'.$projB->chem_name.'" thuộc nhóm nguy hại Bảng B (Phụ lục IV NĐ 24/2026/NĐ-CP). '
                    .'Lượng dự trù ≈ '.$num($projB->add_kg).' kg thô'
                    .($sumB['unconvertible'] ? ' (chưa gồm dòng chưa quy đổi được)' : '')
                    .'; cộng tồn hiện tại '.$num($projB->current_kg).' kg thì tổng ≈ '.$num($projB->projected_kg)
                    .' kg / ngưỡng thấp nhất '.$num($projB->threshold_kg).' kg (nhóm '.$projB->strictest_group.', '
                    .(int) round($projB->projected_ratio * 100).'%). '
                    .($projB->add_ratio >= 1.0 ? 'Riêng lượng dự trù đã vượt ngưỡng. ' : '')
                    .($projB->level === MixtureHazardThreshold::LEVEL_EXCEEDED ? 'DỰ KIẾN VƯỢT NGƯỠNG.' : 'Dự kiến chạm ngưỡng cảnh báo.'),
            ];
        }

        return $warnings;
    }

    /** Số mặt hàng của từng phiếu: [estimate_list_id => số dòng]. */
    private function itemCounts(array $listIds): array
    {
        if (! $listIds) {
            return [];
        }

        return DB::table(self::ITEM_TABLE)
            ->select('estimate_list_id', DB::raw('COUNT(*) as total'))
            ->whereIn('estimate_list_id', $listIds)
            ->where('active', 1)
            ->groupBy('estimate_list_id')
            ->pluck('total', 'estimate_list_id')
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

    /** Ghi lại các dòng số lượng theo tháng của một mặt hàng. */
    private function saveAmounts(int $itemId, Request $request): void
    {
        $rows = [];

        foreach ((array) $request->input('amounts', []) as $line) {
            $amount = trim((string) ($line['amount'] ?? ''));
            $period = trim((string) ($line['for_month_year'] ?? ''));

            if ($amount === '' || $period === '') {
                continue;
            }

            $rows[] = [
                'estimate_item_id' => $itemId,
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



    /** Danh mục hoá chất đã duyệt và đang hoạt động mới được chọn để dự trù. */
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

    /**
     * Mức cảnh báo ngưỡng PL IV hiện tại (không tính thêm số lượng dự trù) của từng mã
     * danh mục, để bảng "Chọn Từ Danh Mục Hoá Chất" tô badge ngay khi duyệt danh sách -
     * gộp mức nặng nhất giữa Bảng A (theo hoạt chất) và Bảng B (theo hỗn hợp).
     *
     * @return array<int, string>  category_id => 'ok' | 'warn' | 'exceeded'
     */
    private function categoryThresholdLevels(?int $companyId): array
    {
        $rank = ['ok' => 0, 'warn' => 1, 'exceeded' => 2];
        $levels = [];

        foreach (ActiveIngredientThreshold::forCategories($companyId) as $categoryId => $row) {
            $levels[$categoryId] = $row->level;
        }

        foreach (MixtureHazardThreshold::forCategories($companyId) as $categoryId => $row) {
            if (! isset($levels[$categoryId]) || $rank[$row->level] > $rank[$levels[$categoryId]]) {
                $levels[$categoryId] = $row->level;
            }
        }

        return $levels;
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

        $list = $this->findOwn($item->estimate_list_id);

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

    /** Một phòng ban chỉ lập một phiếu cho mỗi tháng/năm. */
    private function checkDuplicatePeriod($validator, Request $request, $ignoreId = null): void
    {
        $validator->after(function ($validator) use ($request, $ignoreId) {
            $exists = DB::table(self::TABLE)
                ->where('department_id', $this->departmentId())
                ->where('month', (int) $request->month)
                ->where('year', (int) $request->year)
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists();

            if ($exists) {
                $validator->errors()->add('month', 'Phòng ban đã có phiếu dự trù cho tháng '.$request->month.'/'.$request->year.'.');
            }
        });
    }

    private function itemRules(): array
    {
        return [
            'source' => ['required', 'in:category,manual'],
            'category_id' => ['required_if:source,category', 'nullable', 'exists:chemical_categories,id'],
            'chem_name' => ['required_if:source,manual', 'nullable', 'max:255'],
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
        $fromCategory = $request->source === 'category';

        return [
            'category_id' => $fromCategory ? (int) $request->category_id : null,
            'chem_name' => $fromCategory ? null : $this->nullIfBlank($request->chem_name),
            'technical_information' => $this->nullIfBlank($request->technical_information),
            'purpose' => $this->nullIfBlank($request->purpose),
            'expected_delivery_date' => $this->nullIfBlank($request->expected_delivery_date),
        ];
    }

    private function itemMessages(): array
    {
        return [
            'source.required' => 'Vui lòng chọn nguồn hoá chất.',
            'source.in' => 'Nguồn hoá chất không hợp lệ.',
            'category_id.required_if' => 'Vui lòng chọn hoá chất trong danh mục.',
            'category_id.exists' => 'Hoá chất được chọn không tồn tại trong danh mục.',
            'chem_name.required_if' => 'Vui lòng nhập tên hoá chất ngoài danh mục.',
            'chem_name.max' => 'Tên hoá chất tối đa 255 ký tự.',
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
