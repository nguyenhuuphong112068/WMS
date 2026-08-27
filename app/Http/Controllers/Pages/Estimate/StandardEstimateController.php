<?php

namespace App\Http\Controllers\Pages\Estimate;

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
    private const TABLE = 'standard_estimates';

    private const ITEM_TABLE = 'standard_estimate_items';

    private const AMOUNT_TABLE = 'standard_estimate_item_amounts';

    private const HISTORY_TABLE = 'standard_estimate_histories';

    private const LABEL = 'phiếu dự trù chất chuẩn';

    private const ITEM_LABEL = 'chất chuẩn dự trù';

    /** Mã phiếu: DTC + department_id + năm + tháng(2) + số thứ tự 3 chữ số. */
    private const CODE_PREFIX = 'DTC';

    private const SEQ_LENGTH = 3;

    /** Chỉ hai trạng thái này mới được sửa đầu phiếu và chi tiết mặt hàng. */
    private const EDITABLE_STATUSES = ['draft', 'rejected'];

    /* ==========================================================
     |  DANH SÁCH PHIẾU DỰ TRÙ CỦA PHÒNG BAN
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

        session()->put(['title' => 'DỰ TRÙ - DỰ TRÙ CHẤT CHUẨN']);

        return view('pages.estimate.StandardEstimate.list', [
            'datas' => $datas,
            'itemCounts' => $this->itemCounts($datas->pluck('id')->all()),
            'appStatuses' => config('estimate.app_statuses'),
            'signSteps' => config('estimate.sign_steps'),
            'receptionStatuses' => config('estimate.reception_statuses'),
            'nextCodes' => $this->codePreviews($departmentId),
            'canSignManager' => $this->canSign('manager'),
            'canSignDirector' => $this->canSign('director'),
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

        return view('pages.estimate.StandardEstimate.detail', [
            'list' => $list,
            'items' => self::itemsOf($list->id),
            'histories' => self::historiesOf($list->id),
            'categories' => $this->categoryOptions(),
            'units' => $this->unitOptions(),
            'groups' => config('standard.groups'),
            'appStatuses' => config('estimate.app_statuses'),
            'signSteps' => config('estimate.sign_steps'),
            'receptionStatuses' => config('estimate.reception_statuses'),
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

        $this->checkDuplicatePeriod($validator, $request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $departmentId = $this->departmentId();

        // Sinh mã và ghi phiếu trong cùng một transaction để hai người lập cùng lúc không trùng mã
        $result = DB::transaction(function () use ($request, $departmentId) {
            $code = $this->nextCode($departmentId, (int) $request->month, (int) $request->year);

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

        self::writeHistory($result['id'], 'Tạo phiếu', null, null, 'draft', 'Lập '.self::LABEL.' mã '.$result['code'].'.');

        AuditTrialController::log('Thêm mới', self::TABLE, $result['id'], 'NA', 'Lập '.self::LABEL.': '.$result['code']);

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

        $this->checkDuplicatePeriod($validator, $request, $current->id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);

        // Đổi tháng/năm thì mã phiếu phải sinh lại vì mã gắn với kỳ dự trù
        if ((int) $payload['month'] !== (int) $current->month || (int) $payload['year'] !== (int) $current->year) {
            $payload['code'] = $this->nextCode((int) $current->department_id, (int) $payload['month'], (int) $payload['year']);
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, $payload['code'] ?? $current->code);

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
    }

    public function deActive(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            self::TABLE,
            $current->id,
            'status_id: '.$current->status_id,
            'status_id: '.$newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' '.$current->code.'!'
        );
    }

    /* ==========================================================
     |  MẶT HÀNG DỰ TRÙ + SỐ LƯỢNG THEO THÁNG
     ========================================================== */

    public function storeItem(Request $request)
    {
        $list = $this->findOwn($request->standard_estimate_id);

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
                'standard_estimate_id' => $list->id,
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->saveAmounts($itemId, $request);

            return $itemId;
        });

        AuditTrialController::log('Thêm mới', self::ITEM_TABLE, $itemId, 'NA', 'Thêm '.self::ITEM_LABEL.' vào phiếu '.$list->code);

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

            // Số lượng theo tháng luôn ghi lại toàn bộ: xoá dòng cũ rồi ghi dòng mới
            DB::table(self::AMOUNT_TABLE)->where('standard_estimate_item_id', $item->id)->delete();

            $this->saveAmounts($item->id, $request);
        });

        AuditTrialController::log('Cập nhật', self::ITEM_TABLE, $item->id, 'Phiếu '.$list->code, 'Sửa '.self::ITEM_LABEL);

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
            DB::table(self::AMOUNT_TABLE)->where('standard_estimate_item_id', $item->id)->delete();
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->delete();
        });

        AuditTrialController::log('Xoá', self::ITEM_TABLE, $item->id, 'Phiếu '.$list->code, 'Xoá '.self::ITEM_LABEL);

        return redirect()->back()->with('success', 'Đã xoá '.self::ITEM_LABEL.' khỏi phiếu '.$list->code.'!');
    }

    /* ==========================================================
     |  TRÌNH KÝ
     ========================================================== */

    /** Nháp / Bị từ chối -> Chờ Phó/Trưởng Phòng ký. */
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

        if (! DB::table(self::ITEM_TABLE)->where('standard_estimate_id', $current->id)->exists()) {
            return redirect()->back()->with('error', 'Phiếu '.$current->code.' chưa có chất chuẩn nào, chưa trình ký được!');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => 'pending_manager',
            'submitted_by' => $this->actor(),
            'submitted_at' => now(),
            // Trình ký lại sau khi bị từ chối thì xoá dấu vết ký cũ để bắt đầu lại từ bước 1
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

    /** Bước 1: Phó/Trưởng Phòng ký. */
    public function signManager(Request $request)
    {
        return $this->sign($request, 'manager');
    }

    /** Bước 2: Ban Giám Đốc ký, ký xong phiếu chuyển sang chờ Cung Ứng tiếp nhận. */
    public function signDirector(Request $request)
    {
        return $this->sign($request, 'director');
    }

    /** Từ chối ở bất kỳ bước nào đang chờ ký, phiếu quay về "Bị từ chối" để sửa lại. */
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

    /** Ghi nhận một bước ký: kiểm tra đúng bước, đúng quyền rồi chuyển trạng thái. */
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

        $payload = [
            'app_status' => $config['to'],
            $config['signed_by'] => $this->actor(),
            $config['signed_at'] => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ];

        // Ký xong bước cuối thì phiếu chuyển sang hàng chờ của bộ phận Cung Ứng
        if ($config['to'] === 'approved') {
            $payload['reception_status'] = 'waiting';
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload);

        self::writeHistory($current->id, 'Ký duyệt', $step, $current->app_status, $config['to'], $this->nullIfBlank($request->note));

        AuditTrialController::log('Ký duyệt', self::TABLE, $current->id, 'app_status: '.$current->app_status, 'app_status: '.$config['to']);

        return redirect()->back()->with(
            'success',
            $config['to'] === 'approved'
                ? 'Ban Giám Đốc đã phê duyệt phiếu '.$current->code.'! Phiếu đã chuyển sang bộ phận Cung Ứng tiếp nhận.'
                : 'Đã ký duyệt bước '.$config['label'].' cho phiếu '.$current->code.'! Phiếu chuyển lên Ban Giám Đốc.'
        );
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
    public static function itemsOf(int $listId)
    {
        // Đơn vị tính nằm ở danh mục chất chuẩn CỦA PHÒNG, nên phải biết phiếu này của phòng nào
        $departmentId = (int) DB::table(self::TABLE)->where('id', $listId)->value('department_id');

        $items = DB::table(self::ITEM_TABLE)
            ->leftJoin('standard_categories', self::ITEM_TABLE.'.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, self::ITEM_TABLE.'.category_id'))
            ->select(
                self::ITEM_TABLE.'.*',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_categories.cas_no as category_cas_no',
                'standard_names.name as category_standard_name',
                'units.short_name as category_unit_short_name'
            )
            ->where(self::ITEM_TABLE.'.standard_estimate_id', $listId)
            ->orderBy(self::ITEM_TABLE.'.id', 'asc')
            ->get();

        $amounts = DB::table(self::AMOUNT_TABLE)
            ->leftJoin('units', self::AMOUNT_TABLE.'.unit_id', '=', 'units.id')
            ->select(
                self::AMOUNT_TABLE.'.*',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->whereIn(self::AMOUNT_TABLE.'.standard_estimate_item_id', $items->pluck('id')->all())
            ->orderBy(self::AMOUNT_TABLE.'.for_month_year', 'asc')
            ->get()
            ->groupBy('standard_estimate_item_id');

        return $items->map(function ($item) use ($amounts) {
            $item->amounts = ($amounts[$item->id] ?? collect())->values();
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

        return DB::table(self::HISTORY_TABLE)
            ->where('standard_estimate_id', $listId)
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
            'created_by' => session('user')['fullName'] ?? 'NA',
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
    private function nextCode(int $departmentId, int $month, int $year): string
    {
        $prefix = self::CODE_PREFIX.$departmentId.$year.str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        $next = DB::table(self::TABLE)
            ->where('department_id', $departmentId)
            ->where('month', $month)
            ->where('year', $year)
            ->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix)))
            ->max();

        return $prefix.str_pad((string) (($next ?? 0) + 1), self::SEQ_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Mã dự kiến của vài kỳ gần nhất để hiện trước trên form: ["<năm>-<tháng>" => mã].
     *
     * Chỉ để xem, mã thật vẫn sinh lúc lưu trong transaction.
     */
    private function codePreviews(int $departmentId): array
    {
        $used = DB::table(self::TABLE)
            ->select('month', 'year', 'code')
            ->where('department_id', $departmentId)
            ->get()
            ->groupBy(fn ($row) => $row->year.'-'.(int) $row->month);

        $previews = [];
        $cursor = now()->startOfMonth()->subMonths(2);

        // Lấy dư vài kỳ quanh hiện tại để ô chọn tháng/năm nào cũng có mã xem trước
        for ($i = 0; $i < 27; $i++) {
            $key = $cursor->year.'-'.$cursor->month;
            $prefix = self::CODE_PREFIX.$departmentId.$cursor->year.$cursor->format('m');

            $next = ($used[$key] ?? collect())
                ->map(fn ($row) => (int) substr((string) $row->code, strlen($prefix)))
                ->max();

            $previews[$key] = $prefix.str_pad((string) (($next ?? 0) + 1), self::SEQ_LENGTH, '0', STR_PAD_LEFT);

            $cursor->addMonth();
        }

        return $previews;
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
        $item = DB::table(self::ITEM_TABLE)->where('id', $id)->first();

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

    /** Trạng thái chờ ký hiện tại ứng với bước nào: manager | director | null. */
    private function pendingStep(?string $appStatus): ?string
    {
        foreach (config('estimate.sign_steps') as $step => $config) {
            if ($config['from'] === $appStatus) {
                return $step;
            }
        }

        return null;
    }

    /** Người đang đăng nhập có quyền ký bước này không (Admin luôn ký được). */
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
        return session('user')['fullName'] ?? 'NA';
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

    /** Một phòng ban chỉ lập một phiếu dự trù chất chuẩn cho mỗi tháng/năm. */
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
                $validator->errors()->add('month', 'Phòng ban đã có phiếu dự trù chất chuẩn cho tháng '.$request->month.'/'.$request->year.'.');
            }
        });
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
        $fromCategory = $request->source === 'category';

        return [
            'category_id' => $fromCategory ? (int) $request->category_id : null,
            'standard_name' => $fromCategory ? null : $this->nullIfBlank($request->standard_name),
            'group_key' => $this->nullIfBlank($request->group_key),
            'technical_information' => $this->nullIfBlank($request->technical_information),
            'purpose' => $this->nullIfBlank($request->purpose),
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
