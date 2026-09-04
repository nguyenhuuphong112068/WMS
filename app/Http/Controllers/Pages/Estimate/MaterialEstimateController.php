<?php

namespace App\Http\Controllers\Pages\Estimate;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
    use VerifiesSignature;

    private const TABLE = 'material_estimates';

    private const ITEM_TABLE = 'material_estimate_items';

    private const AMOUNT_TABLE = 'material_estimate_item_amounts';

    private const HISTORY_TABLE = 'material_estimate_histories';

    private const CHAT_TYPE = 'material';

    private const LABEL = 'phiếu dự trù vật tư';

    private const ITEM_LABEL = 'vật tư dự trù';

    private const EDITABLE_STATUSES = ['draft', 'rejected'];

    /* ==========================================================
     |  DANH SÁCH PHIẾU
     ========================================================== */

    public function index()
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

        $trackedItems = self::trackedItems($departmentId);

        session()->put(['title' => 'DỰ TRÙ - DỰ TRÙ VẬT TƯ']);

        return view('pages.estimate.MaterialEstimate.list', [
            'datas' => $datas,
            'itemCounts' => $this->itemCounts($datas->pluck('id')->all()),
            'appStatuses' => config('estimate.app_statuses'),
            'signSteps' => config('estimate.sign_steps'),
            'receptionStatuses' => config('estimate.reception_statuses'),
            'canSignManager' => $this->canSign('manager'),
            'canSignDirector' => $this->canSign('director'),
            'nextCode' => $this->nextCode($departmentId),
            'trackedItems' => $trackedItems,
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

        return view('pages.estimate.MaterialEstimate.detail', [
            'list' => $list,
            'items' => self::itemsOf($list->id),
            'histories' => self::historiesOf($list->id),
            'categories' => $this->categoryOptions(),
            'units' => $this->unitOptions(),
            'appStatuses' => config('estimate.app_statuses'),
            'signSteps' => config('estimate.sign_steps'),
            'receptionStatuses' => config('estimate.reception_statuses'),
            'canEditItems' => $this->editable($list),
            'backRoute' => route('pages.estimate.materialEstimate.list'),
            'estRoute' => 'pages.estimate.materialEstimate.',
        ]);
    }

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
     |  MẶT HÀNG DỰ TRÙ + SỐ LƯỢNG THEO THÁNG
     ========================================================== */

    public function storeItem(Request $request)
    {
        $list = $this->findOwn($request->material_estimate_id);

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
                'material_estimate_id' => $list->id,
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

        return redirect()->back()->with('success', 'Đã thêm '.self::ITEM_LABEL.' vào phiếu '.$list->code.'!');
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

    public function updateItemStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'action' => 'required|in:complete,cancel,undo',
        ]);

        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' cần cập nhật!');
        }

        if ($list->app_status !== 'approved') {
            return redirect()->back()->with('error', 'Phiếu chưa được duyệt nên không thể cập nhật trạng thái mục!');
        }

        if ($request->action === 'complete') {
            $updateData = ['fulfilled_date' => now(), 'status_id' => 1];
            $logMessage = 'Đã xác nhận hoàn thành (giao hàng).';
        } elseif ($request->action === 'cancel') {
            $updateData = ['fulfilled_date' => null, 'status_id' => 0, 'cancel_reason' => $request->cancel_reason];
            $logMessage = 'Đã huỷ dự trù mặt hàng. Lý do: '.$request->cancel_reason;
        } else {
            $updateData = ['fulfilled_date' => null, 'status_id' => 1, 'cancel_reason' => null];
            $logMessage = 'Đã khôi phục lại trạng thái mặt hàng.';
        }

        DB::transaction(function () use ($item, $updateData, $logMessage) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update($updateData);

            DB::table('estimate_item_chats')->insert([
                'item_id' => $item->id,
                'item_type' => self::CHAT_TYPE,
                'user_name' => $this->actor(),
                'content' => $logMessage,
                'type' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái '.self::ITEM_LABEL.'!');
    }

    public function updatePromisedDate(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'promised_date' => 'nullable|date',
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
        $actor = $this->actor();
        $historyAdded = false;

        DB::transaction(function () use ($item, $request, $oldDate, $newDate, $actor, &$historyAdded) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update([
                'promised_date' => $request->promised_date,
            ]);

            if ($oldDate !== $newDate) {
                DB::table('estimate_item_chats')->insert([
                    'item_id' => $item->id,
                    'item_type' => self::CHAT_TYPE,
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

        if ($stop = $this->guardSignature($request, self::TABLE, $current->id, 'Trình ký')) {
            return $stop;
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => 'pending_manager',
            'submitted_by' => $this->actor(),
            'submitted_at' => now(),
            'manager_signed_by' => null,
            'manager_signed_at' => null,
            'director_signed_by' => null,
            'director_signed_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'reject_step' => null,
            'reject_reason' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        self::writeHistory($current->id, 'Trình ký', 'manager', $current->app_status, 'pending_manager', $this->nullIfBlank($request->note));

        AuditTrialController::log('Trình ký', self::TABLE, $current->id, 'app_status: '.$current->app_status, 'app_status: pending_manager');

        return redirect()->back()->with('success', 'Đã trình ký phiếu '.$current->code.' lên Phó/Trưởng Phòng!');
    }

    public function signManager(Request $request)
    {
        return $this->sign($request, 'manager');
    }

    public function signDirector(Request $request)
    {
        return $this->sign($request, 'director');
    }

    public function reject(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần từ chối!');
        }

        $step = $this->pendingStep($current->app_status);

        if (! $step) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' không ở bước chờ ký nên không từ chối được!');
        }

        if (! $this->canSign($step)) {
            return redirect()->back()->with('error', 'Bạn không có quyền ký duyệt bước "'.config('estimate.sign_steps')[$step]['label'].'"!');
        }

        $validator = Validator::make($request->all(), [
            'reject_reason' => ['required', 'max:500'],
        ], [
            'reject_reason.required' => 'Vui lòng nhập lý do từ chối.',
            'reject_reason.max' => 'Lý do từ chối tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'rejectErrors')->withInput();
        }

        if ($stop = $this->guardSignature($request, self::TABLE, $current->id, 'Từ chối duyệt')) {
            return $stop;
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => 'rejected',
            'rejected_by' => $this->actor(),
            'rejected_at' => now(),
            'reject_step' => $step,
            'reject_reason' => $request->reject_reason,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        self::writeHistory($current->id, 'Từ chối', $step, $current->app_status, 'rejected', $request->reject_reason);

        AuditTrialController::log('Từ chối duyệt', self::TABLE, $current->id, 'app_status: '.$current->app_status, 'app_status: rejected');

        return redirect()->back()->with('success', 'Đã từ chối phiếu '.$current->code.'. Phòng ban cần sửa lại rồi trình ký lại.');
    }

    private function sign(Request $request, string $step)
    {
        $config = config('estimate.sign_steps')[$step];

        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần ký duyệt!');
        }

        if ($current->app_status !== $config['from']) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' đang ở bước "'.$this->statusLabel($current->app_status).'", không ký bước "'.$config['label'].'" được!');
        }

        if (! $this->canSign($step)) {
            return redirect()->back()->with('error', 'Bạn không có quyền ký duyệt bước "'.$config['label'].'"!');
        }

        if ($stop = $this->guardSignature($request, self::TABLE, $current->id, 'Ký duyệt '.$config['label'])) {
            return $stop;
        }

        $payload = [
            'app_status' => $config['to'],
            $config['signed_by'] => $this->actor(),
            $config['signed_at'] => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ];

        if ($config['to'] === 'approved') {
            $payload['reception_status'] = 'received';
            $payload['received_by'] = 'Hệ thống';
            $payload['received_at'] = now();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload);

        self::writeHistory($current->id, 'Ký duyệt', $step, $current->app_status, $config['to'], $this->nullIfBlank($request->note));

        AuditTrialController::log('Ký duyệt', self::TABLE, $current->id, 'app_status: '.$current->app_status, 'app_status: '.$config['to']);

        return redirect()->back()->with(
            'success',
            $config['to'] === 'approved'
                ? 'Ban Giám Đốc đã phê duyệt phiếu '.$current->code.'! Phiếu đã được ghi nhận tiếp nhận.'
                : 'Đã ký duyệt bước '.$config['label'].' cho phiếu '.$current->code.'! Phiếu chuyển lên Ban Giám Đốc.'
        );
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

        return $items->map(function ($item) use ($amounts, $chats, $historyCounts) {
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

        return DB::table(self::HISTORY_TABLE)
            ->where('material_estimate_id', $listId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($row) => [
                'action' => $row->action,
                'step' => $row->step ? ($steps[$row->step]['label'] ?? ($row->step === 'reception' ? 'Cung Ứng' : $row->step)) : '',
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
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name'
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

    private function pendingStep(?string $appStatus): ?string
    {
        foreach (config('estimate.sign_steps') as $step => $config) {
            if ($config['from'] === $appStatus) {
                return $step;
            }
        }

        return null;
    }

    private function canSign(string $step): bool
    {
        return user_has_any_role(session('user')['userId'] ?? 0, config('estimate.sign_steps')[$step]['roles']);
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
            'category_id' => ['required_if:source,category', 'nullable', 'exists:material_categories,id'],
            'material_name' => ['required_if:source,manual', 'nullable', 'max:255'],
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
            'material_name' => $fromCategory ? null : $this->nullIfBlank($request->material_name),
            'technical_information' => $this->nullIfBlank($request->technical_information),
            'purpose' => $this->nullIfBlank($request->purpose),
            'expected_delivery_date' => $this->nullIfBlank($request->expected_delivery_date),
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
