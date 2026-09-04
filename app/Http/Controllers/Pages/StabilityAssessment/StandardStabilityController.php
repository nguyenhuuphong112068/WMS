<?php

namespace App\Http\Controllers\Pages\StabilityAssessment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * ĐÁNH GIÁ HẠN DÙNG - CHẤT CHUẨN
 *
 * Chất chuẩn để lâu thì hàm lượng đổi theo thời gian, nên ống chuẩn đang tồn được lập
 * một PHIẾU ĐÁNH GIÁ HẠN DÙNG (standard_stability_assessment_list) để theo dõi độ ổn
 * định: chọn ống chuẩn, ngày bắt đầu và chu kỳ đánh giá (số tháng).
 *
 * Phiếu gồm nhiều MỐC ĐÁNH GIÁ (standard_stability_assessment_item) - T0, T3, T6...
 * Mỗi mốc có:
 *      timepoint : số tháng tính từ ngày bắt đầu.
 *      due_date  : ngày đến hạn = start_date + timepoint tháng, tính sẵn khi ghi.
 *      testings  : các chỉ tiêu cần thử nghiệm tại mốc đó (mảng JSON). Mỗi chỉ tiêu còn
 *                  đánh dấu ĐÃ CẤP PHÁT CHUẨN riêng kèm ghi chú riêng, vì chuẩn thường
 *                  cấp làm nhiều lần chứ không cấp một thể cho cả mốc.
 *      done_at / result / status : ngày làm, kết quả và kết luận Đạt / Không Đạt.
 *
 * Mọi thay đổi trên phiếu (lập phiếu, sửa đầu phiếu, thêm / sửa / xoá mốc, ghi kết quả,
 * huỷ, mở lại) đều ghi một dòng standard_stability_assessment_histories gắn với id của
 * phiếu, xem lại ngay trên màn hình chi tiết.
 *
 * TRẠNG THÁI PHIẾU tự chạy theo các mốc, không phải bấm tay:
 *      Ban Đầu       : chưa mốc nào có kết quả.
 *      Đang Đánh Giá : đã có kết quả nhưng chưa xong hết các mốc.
 *      Hoàn Thành    : mọi mốc đều đã có kết quả.
 *      Dừng Đánh Giá : ngưng giữa chừng, các mốc còn lại không thực hiện nữa.
 *      Huỷ           : người dùng bỏ phiếu. Phiếu KHÔNG xoá cứng, chỉ chuyển "Huỷ".
 *
 * NGƯNG GIỮA CHỪNG - một phiếu không phải lúc nào cũng chạy hết các mốc:
 *
 *      Mốc Không Đạt : chất chuẩn đã hỏng, các mốc sau không còn ý nghĩa nên phiếu dừng
 *                      ngay tại đó, lý do hệ thống tự ghi.
 *      Mốc Đạt       : người dùng chọn đánh giá tiếp, hoặc ngưng vì một lý do khác
 *                      (dùng hết ống, đổi chuẩn...) - chọn ngưng thì phải nhập lý do.
 *
 * Ngưng khác Huỷ ở chỗ số liệu các mốc đã làm vẫn còn giá trị. Phiếu đã ngưng khoá mọi
 * thao tác ghi cho tới khi bấm "Đánh giá tiếp" (resume) để chạy lại.
 *
 * Mỗi ống chuẩn chỉ có một phiếu còn hiệu lực (chưa Huỷ) để số liệu đánh giá không
 * bị tách làm hai nơi; muốn lập lại thì huỷ phiếu cũ trước.
 */
class StandardStabilityController extends Controller
{
    private const TABLE = 'standard_stability_assessment_list';

    private const ITEM_TABLE = 'standard_stability_assessment_item';

    private const HISTORY_TABLE = 'standard_stability_assessment_histories';

    /** Khoá ngoại của bảng mốc - viết ra hằng vì tên cột rất dài. */
    private const ITEM_FK = 'standard_stability_assessment_list_id';

    private const LABEL = 'phiếu đánh giá hạn dùng';

    private const ITEM_LABEL = 'mốc đánh giá';

    /* ---------- Trạng thái phiếu ---------- */
    public const STATUS_INITIAL = 'Ban Đầu';

    public const STATUS_RUNNING = 'Đang Đánh Giá';

    public const STATUS_DONE = 'Hoàn Thành';

    public const STATUS_CANCELLED = 'Huỷ';

    /**
     * Ngưng đánh giá giữa chừng - khác hẳn "Huỷ".
     *
     * Huỷ là bỏ cả phiếu, coi như chưa từng theo dõi. Dừng là ĐÃ đánh giá được một phần
     * rồi mới ngưng: hoặc vì một mốc Không Đạt, hoặc vì người dùng chủ động ngưng và
     * nêu lý do. Số liệu các mốc đã làm vẫn giữ nguyên giá trị.
     */
    public const STATUS_STOPPED = 'Dừng Đánh Giá';

    /* ---------- Trạng thái một mốc đánh giá ---------- */
    public const ITEM_INITIAL = 'Ban Đầu';

    public const ITEM_PASSED = 'Đạt';

    public const ITEM_FAILED = 'Không Đạt';

    /** Kết luận được chọn khi ghi kết quả cho một mốc. */
    public const ITEM_RESULTS = [self::ITEM_PASSED, self::ITEM_FAILED];

    /**
     * CHỈ CHUẨN THỨ CẤP MỚI ĐÁNH GIÁ HẠN DÙNG.
     *
     * Chuẩn chính / chuẩn viện / chuẩn nhập ngoại... đã có hạn dùng của nhà sản xuất nên
     * không phải theo dõi độ ổn định; chỉ chuẩn thứ cấp do phòng tự thiết lập mới cần.
     * Khoá nhóm trỏ vào config('standard.groups'), mã nhóm nằm trong mã ống chuẩn.
     */
    private const GROUP_KEY = 'CTC';

    /** Còn dưới ngần này ngày là mốc "Sắp đến hạn". Trang Kế Hoạch Đánh Giá dùng lại hằng này. */
    public const DUE_SOON_DAYS = 30;

    /** Số chỉ tiêu thử nghiệm tối đa của một mốc, và độ dài cột testings (kiểu TEXT). */
    private const MAX_TESTINGS = 20;

    private const TESTINGS_LENGTH = 4000;

    /** Ghi chú cấp phát của một chỉ tiêu tối đa ngần này ký tự. */
    private const TESTING_NOTE_LENGTH = 255;

    /**
     * Tên hiển thị của tình trạng từng mốc, tính ra từ due_date chứ không lưu DB.
     *
     * 'stopped' chỉ xuất hiện khi PHIẾU đã dừng: mốc chưa làm của phiếu dừng không còn
     * là việc phải làm nữa nên không xét đến hạn. Trang Kế Hoạch Đánh Giá bỏ phiếu dừng
     * ra ngoài nên ở đó không bao giờ gặp trạng thái này.
     */
    public const ITEM_STATES = [
        'done' => 'Đã đánh giá',
        'overdue' => 'Quá hạn',
        'due' => 'Sắp đến hạn',
        'waiting' => 'Chưa tới hạn',
        'stopped' => 'Đã ngưng',
    ];

    /* ---------- Lựa chọn sau khi một mốc kết luận "Đạt" ---------- */
    public const AFTER_CONTINUE = 'continue';

    public const AFTER_STOP = 'stop';

    /* ==========================================================
     |  DANH SÁCH PHIẾU ĐÁNH GIÁ CỦA PHÒNG BAN
     ========================================================== */

    public function index()
    {
        $departmentId = $this->departmentId();

        $datas = $this->listQuery($departmentId)
            ->orderByDesc(self::TABLE.'.start_date')
            ->orderByDesc(self::TABLE.'.id')
            ->get();

        $stats = $this->itemStats($datas->pluck('id')->all());

        $datas = $datas->map(function ($row) use ($stats) {
            $stat = $stats[$row->id] ?? null;

            $row->item_total = (int) ($stat['total'] ?? 0);
            $row->item_done = (int) ($stat['done'] ?? 0);
            // Phiếu đã ngưng thì các mốc còn lại không thực hiện nữa, đừng báo đến hạn
            $waiting = $row->status !== self::STATUS_STOPPED;

            $row->item_overdue = $waiting ? (int) ($stat['overdue'] ?? 0) : 0;
            $row->item_due = $waiting ? (int) ($stat['due'] ?? 0) : 0;
            $row->next_due_date = $waiting ? ($stat['next_due_date'] ?? null) : null;
            $row->progress = $row->item_total > 0
                ? (int) round($row->item_done / $row->item_total * 100)
                : 0;

            return $row;
        });

        session()->put(['title' => 'ĐÁNH GIÁ HẠN DÙNG - CHẤT CHUẨN']);

        return view('pages.stabilityAssessment.StandardStability.list', [
            'datas' => $datas,
            'imports' => $this->importOptions($departmentId),
            'statuses' => [self::STATUS_INITIAL, self::STATUS_RUNNING, self::STATUS_DONE, self::STATUS_STOPPED, self::STATUS_CANCELLED],
            'itemStates' => self::ITEM_STATES,
            'groups' => config('standard.groups'),
            'dueSoonDays' => self::DUE_SOON_DAYS,
            'maxTestings' => self::MAX_TESTINGS,
            // Chỉ tiêu kiểm lấy từ Dữ Liệu Gốc, đổ vào ô chọn nhiều của từng mốc
            'criterias' => $this->criteriaOptions(),
            // Nhóm chuẩn duy nhất phải đánh giá hạn dùng, để view viết đúng câu hướng dẫn
            'assessGroupName' => $this->groupName(),
            'assessGroupCode' => $this->groupCode(),
        ]);
    }

    /** Trang chi tiết một phiếu: thông tin ống chuẩn + các mốc đánh giá. */
    public function detail(Request $request)
    {
        $list = $this->listQuery($this->departmentId())
            ->where(self::TABLE.'.id', $request->id)
            ->first();

        if (! $list) {
            return redirect()->route('pages.stabilityAssessment.standardStability.list')
                ->with('error', 'Không tìm thấy '.self::LABEL.' của phòng ban đang chọn!');
        }

        session()->put(['title' => 'ĐÁNH GIÁ HẠN DÙNG - ỐNG CHUẨN '.$list->import_code]);

        $stopped = $list->status === self::STATUS_STOPPED;

        return view('pages.stabilityAssessment.StandardStability.detail', [
            'list' => $list,
            'items' => $this->itemsOf($list->id, $stopped),
            'itemStates' => self::ITEM_STATES,
            'itemResults' => self::ITEM_RESULTS,
            'groups' => config('standard.groups'),
            'dueSoonDays' => self::DUE_SOON_DAYS,
            'maxTestings' => self::MAX_TESTINGS,
            'testingNoteLength' => self::TESTING_NOTE_LENGTH,
            'criterias' => $this->criteriaOptions(),
            'histories' => $this->historiesOf($list->id),
            'editable' => $this->editable($list),
            // Phiếu đã ngưng thì chỉ xem: mốc còn lại không thực hiện nữa
            'running' => $this->running($list),
            'stopped' => $stopped,
            'afterContinue' => self::AFTER_CONTINUE,
            'afterStop' => self::AFTER_STOP,
            'itemPassed' => self::ITEM_PASSED,
            'itemFailed' => self::ITEM_FAILED,
        ]);
    }

    /* ==========================================================
     |  ĐẦU PHIẾU
     ========================================================== */

    /**
     * Lập phiếu, khai luôn các MỐC ĐÁNH GIÁ ngay trên form.
     *
     * Người lập gõ thời điểm kiểm (số tháng) và chọn chỉ tiêu kiểm cho từng mốc; ngày
     * kiểm dự kiến do hệ thống tính (ngày bắt đầu + số tháng) nên không nhận từ form.
     * Không khai mốc nào cũng lưu được, sau đó vào trang chi tiết khai tiếp.
     */
    public function store(Request $request)
    {
        $this->pruneEmptyItems($request);

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        $this->checkItemRows($validator, $request);

        $import = $this->findImport($request->import_id);

        $validator->after(function ($validator) use ($request, $import) {
            if (! $import) {
                $validator->errors()->add('import_id', 'Không tìm thấy mã ống chuẩn còn hiệu lực của phòng ban đang chọn!');

                return;
            }

            // Ô chọn đã lọc sẵn nhưng vẫn chặn lại ở đây, tránh gửi thẳng import_id lên
            if ($import->group_code !== $this->groupCode()) {
                $validator->errors()->add('import_id', 'Chỉ '.$this->groupName().' mới phải đánh giá hạn dùng, ống '.$import->code.' không thuộc nhóm này!');

                return;
            }

            // Mỗi ống chuẩn chỉ theo dõi trên một phiếu, tránh số liệu đánh giá tách làm hai nơi
            $exists = DB::table(self::TABLE)
                ->where('import_id', $import->id)
                ->where('status', '!=', self::STATUS_CANCELLED)
                ->exists();

            if ($exists) {
                $validator->errors()->add('import_id', 'Mã ống chuẩn '.$import->code.' đã có phiếu đánh giá hạn dùng còn hiệu lực!');
            }

            if ($import->imported_date && $request->start_date
                && substr((string) $request->start_date, 0, 10) < substr((string) $import->imported_date, 0, 10)) {
                $validator->errors()->add('start_date', 'Ngày bắt đầu đánh giá không được trước ngày nhập kho của ống chuẩn!');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $startDate = substr((string) $request->start_date, 0, 10);
        $rows = (array) $request->input('items', []);

        // Đầu phiếu và các mốc phải cùng vào hoặc cùng không, tránh phiếu rỗng nửa vời
        $id = DB::transaction(function () use ($request, $import, $startDate, $rows) {
            $id = DB::table(self::TABLE)->insertGetId([
                'import_id' => $import->id,
                'start_date' => $startDate,
                'assessment_period' => (int) $request->assessment_period,
                'status' => self::STATUS_INITIAL,
                'note' => $this->nullIfBlank($request->note),
                'created_by' => $this->actor(),
                'created_at' => now(),
            ]);

            $this->writeHistory(
                $id,
                'Lập phiếu',
                null,
                null,
                null,
                'Bắt đầu '.$this->day($startDate).', chu kỳ '.(int) $request->assessment_period.' tháng',
                'Ống chuẩn '.$import->code.($rows ? ', khai kèm '.count($rows).' mốc' : '')
            );

            foreach ($rows as $row) {
                $timepoint = (int) $row['timepoint'];

                $payload = [
                    'name' => $this->itemName($timepoint),
                    'timepoint' => $timepoint,
                    'due_date' => $this->dueDate($startDate, $timepoint),
                    'testings' => $this->testingsJson($row['testings'] ?? null),
                    'note' => $this->nullIfBlank($row['note'] ?? null),
                ];

                // insertGetId từng dòng thay vì insert cả mảng: cần id để gắn vào nhật ký
                $itemId = DB::table(self::ITEM_TABLE)->insertGetId($payload + [
                    self::ITEM_FK => $id,
                    'status' => self::ITEM_INITIAL,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->writeHistory($id, 'Thêm mốc', $itemId, $this->itemTarget($payload), null, $this->itemDigest($payload));
            }

            return $id;
        });

        AuditTrialController::log(
            'Thêm mới',
            self::TABLE,
            $id,
            'NA',
            'Lập '.self::LABEL.' cho ống chuẩn '.$import->code.' với '.count($rows).' '.self::ITEM_LABEL
        );

        return redirect()->route('pages.stabilityAssessment.standardStability.detail', ['id' => $id])
            ->with('success', $rows
                ? 'Đã lập '.self::LABEL.' cho ống chuẩn '.$import->code.' kèm '.count($rows).' '.self::ITEM_LABEL.'!'
                : 'Đã lập '.self::LABEL.' cho ống chuẩn '.$import->code.'! Hãy khai các mốc đánh giá.');
    }

    /**
     * Sửa đầu phiếu.
     *
     * Đổi ngày bắt đầu hoặc chu kỳ thì các mốc CHƯA CÓ KẾT QUẢ được dời ngày đến hạn
     * theo mốc mới; mốc đã đánh giá giữ nguyên ngày cũ vì đó là dữ liệu đã xảy ra.
     * Không đổi được ống chuẩn: muốn đánh giá ống khác thì lập phiếu khác.
     */
    public function update(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        if (! $this->editable($current)) {
            return redirect()->back()->with('error', 'Phiếu đã huỷ nên không sửa được nữa!');
        }

        $validator = Validator::make($request->all(), $this->rules(false), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $startDate = substr((string) $request->start_date, 0, 10);

        DB::transaction(function () use ($current, $request, $startDate) {
            DB::table(self::TABLE)->where('id', $current->id)->update([
                'start_date' => $startDate,
                'assessment_period' => (int) $request->assessment_period,
                'note' => $this->nullIfBlank($request->note),
            ]);

            $moved = 0;

            // Đổi ngày bắt đầu thì các mốc chưa có kết quả phải dời ngày đến hạn theo
            if ($startDate !== substr((string) $current->start_date, 0, 10)) {
                $waiting = DB::table(self::ITEM_TABLE)
                    ->where(self::ITEM_FK, $current->id)
                    ->where('status', self::ITEM_INITIAL)
                    ->get();

                foreach ($waiting as $item) {
                    DB::table(self::ITEM_TABLE)->where('id', $item->id)->update([
                        'due_date' => $this->dueDate($startDate, (int) $item->timepoint),
                        'updated_by' => $this->actor(),
                        'updated_at' => now(),
                    ]);
                }

                $moved = $waiting->count();
            }

            $this->writeHistory(
                $current->id,
                'Sửa phiếu',
                null,
                null,
                $this->listDigest($current->start_date, $current->assessment_period, $current->note),
                $this->listDigest($startDate, $request->assessment_period, $request->note),
                $moved > 0 ? 'Dời ngày đến hạn của '.$moved.' mốc chưa có kết quả.' : null
            );
        });

        AuditTrialController::log(
            'Cập nhật',
            self::TABLE,
            $current->id,
            'Bắt đầu '.$current->start_date.', chu kỳ '.$current->assessment_period.' tháng',
            'Bắt đầu '.$startDate.', chu kỳ '.$request->assessment_period.' tháng'
        );

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
    }

    /**
     * Huỷ phiếu / mở lại phiếu đã huỷ.
     *
     * Thay cho deActive của các màn hình khác: bảng này không có cột status_id, trạng
     * thái nằm ở chính cột status. Mở lại thì trạng thái được tính lại theo các mốc.
     */
    public function cancel(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
        }

        if ($current->status !== self::STATUS_CANCELLED) {
            $newStatus = self::STATUS_CANCELLED;
        } elseif ($current->stop_reason) {
            // Phiếu đang ngưng đánh giá rồi mới bị huỷ: mở lại thì trả về đúng trạng thái ngưng,
            // muốn chạy tiếp thì bấm "Đánh giá tiếp" - đó là một quyết định riêng
            $newStatus = self::STATUS_STOPPED;
        } else {
            // Mở lại: phiếu đang ở đâu trong tiến độ thì trả về đúng chỗ đó
            $newStatus = $this->statusFromItems($current->id);
        }

        DB::table(self::TABLE)->where('id', $current->id)->update(['status' => $newStatus]);

        $this->writeHistory(
            $current->id,
            $newStatus === self::STATUS_CANCELLED ? 'Huỷ phiếu' : 'Mở lại phiếu',
            null,
            null,
            'Trạng thái: '.$current->status,
            'Trạng thái: '.$newStatus
        );

        AuditTrialController::log(
            $newStatus === self::STATUS_CANCELLED ? 'Huỷ' : 'Mở lại',
            self::TABLE,
            $current->id,
            'status: '.$current->status,
            'status: '.$newStatus
        );

        return redirect()->back()->with(
            'success',
            $newStatus === self::STATUS_CANCELLED
                ? 'Đã huỷ '.self::LABEL.'!'
                : 'Đã mở lại '.self::LABEL.', trạng thái hiện tại: '.$newStatus.'.'
        );
    }

    /* ==========================================================
     |  MỐC ĐÁNH GIÁ
     ========================================================== */

    public function storeItem(Request $request)
    {
        $list = $this->findOwn($request->{self::ITEM_FK});

        if (! $list) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần khai mốc đánh giá!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu đã huỷ nên không thêm mốc đánh giá được nữa!');
        }

        $validator = Validator::make($request->all(), $this->itemRules(), $this->itemMessages());

        $this->checkTestings($validator, $request);
        $this->checkDuplicateTimepoint($validator, $list->id, (int) $request->timepoint);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'itemCreateErrors')->withInput();
        }

        $payload = $this->itemPayload($request, $list);

        $id = DB::transaction(function () use ($payload, $list) {
            $id = DB::table(self::ITEM_TABLE)->insertGetId($payload + [
                self::ITEM_FK => $list->id,
                'status' => self::ITEM_INITIAL,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->writeHistory($list->id, 'Thêm mốc', $id, $this->itemTarget($payload), null, $this->itemDigest($payload));

            return $id;
        });

        AuditTrialController::log('Thêm mới', self::ITEM_TABLE, $id, 'NA', 'Thêm '.self::ITEM_LABEL.' T'.(int) $request->timepoint.' vào phiếu #'.$list->id);

        return redirect()->back()->with('success', 'Đã thêm '.self::ITEM_LABEL.' vào phiếu!');
    }

    public function updateItem(Request $request)
    {
        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' cần cập nhật!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu đã huỷ nên không sửa mốc đánh giá được nữa!');
        }

        if ($item->status !== self::ITEM_INITIAL) {
            return redirect()->back()->with('error', 'Mốc đánh giá đã có kết quả nên không sửa được nữa!');
        }

        $validator = Validator::make($request->all(), $this->itemRules(), $this->itemMessages());

        $this->checkTestings($validator, $request);
        $this->checkDuplicateTimepoint($validator, $list->id, (int) $request->timepoint, $item->id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'itemUpdateErrors')->withInput();
        }

        $payload = $this->itemPayload($request, $list, $item);

        DB::transaction(function () use ($payload, $item, $list) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update($payload + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->writeHistory(
                $list->id,
                'Sửa mốc',
                $item->id,
                $this->itemTarget($payload),
                $this->itemDigestOf($item),
                $this->itemDigest($payload)
            );
        });

        AuditTrialController::log('Cập nhật', self::ITEM_TABLE, $item->id, $item->name.' (T'.$item->timepoint.')', $request->name.' (T'.(int) $request->timepoint.')');

        return redirect()->back()->with('success', 'Cập nhật '.self::ITEM_LABEL.' thành công!');
    }

    /**
     * Xoá hẳn một mốc khỏi phiếu.
     *
     * Ngoại lệ của quy tắc "chỉ khoá, không xoá": mốc chưa có kết quả mới chỉ là nội dung
     * đang soạn của phiếu. Mốc đã đánh giá là dữ liệu đã xảy ra nên giữ lại. Mọi lần xoá
     * đều ghi vào nhật ký thay đổi của phiếu và Audit Trail.
     */
    public function deleteItem(Request $request)
    {
        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' cần xoá!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu đã huỷ nên không xoá mốc đánh giá được nữa!');
        }

        if ($item->status !== self::ITEM_INITIAL) {
            return redirect()->back()->with('error', 'Mốc đánh giá đã có kết quả nên không xoá được!');
        }

        DB::transaction(function () use ($item, $list) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->delete();

            // Ghi nhật ký sau khi xoá: dòng nhật ký giữ lại nội dung mốc vừa mất
            $this->writeHistory(
                $list->id,
                'Xoá mốc',
                $item->id,
                $this->itemTargetOf($item),
                $this->itemDigestOf($item),
                null
            );
        });

        AuditTrialController::log('Xoá', self::ITEM_TABLE, $item->id, $item->name.' (T'.$item->timepoint.')', 'Xoá khỏi phiếu #'.$list->id);

        return redirect()->back()->with('success', 'Đã xoá '.self::ITEM_LABEL.' khỏi phiếu!');
    }

    /**
     * Ghi kết quả đánh giá của một mốc, và quyết định phiếu có chạy tiếp hay không.
     *
     * Bình thường trạng thái phiếu tính theo tiến độ: còn mốc chưa làm là "Đang Đánh
     * Giá", xong hết là "Hoàn Thành". Nhưng một phiếu không phải lúc nào cũng chạy hết
     * các mốc:
     *
     *      KHÔNG ĐẠT : chất chuẩn đã hỏng, các mốc sau không còn ý nghĩa -> phiếu DỪNG
     *                  ngay, lý do do hệ thống tự ghi, người dùng không phải chọn gì.
     *      ĐẠT       : người dùng chọn đánh giá tiếp, hoặc ngưng vì một lý do khác
     *                  (dùng hết ống, đổi chuẩn...) - chọn ngưng thì BẮT BUỘC nhập lý do.
     *
     * Mốc cuối cùng đã Đạt thì phiếu "Hoàn Thành", không hỏi tiếp nữa - không còn mốc
     * nào ở sau để mà ngưng.
     */
    public function assess(Request $request)
    {
        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' cần ghi kết quả!');
        }

        if (! $this->running($list)) {
            return redirect()->back()->with('error', $list->status === self::STATUS_STOPPED
                ? 'Phiếu đã ngưng đánh giá, hãy mở lại phiếu trước khi ghi thêm kết quả!'
                : 'Phiếu đã huỷ nên không ghi kết quả được nữa!');
        }

        $validator = Validator::make($request->all(), [
            'done_at' => ['required', 'date'],
            'result' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:'.implode(',', self::ITEM_RESULTS)],
            'note' => ['nullable', 'string', 'max:255'],
            'after_pass' => ['nullable', 'string', 'in:'.self::AFTER_CONTINUE.','.self::AFTER_STOP],
            'stop_reason' => ['nullable', 'string', 'max:255'],
        ], [
            'done_at.required' => 'Chưa chọn ngày thực hiện đánh giá!',
            'done_at.date' => 'Ngày thực hiện đánh giá không hợp lệ!',
            'result.required' => 'Chưa nhập kết quả đánh giá!',
            'result.max' => 'Kết quả đánh giá tối đa 255 ký tự!',
            'status.required' => 'Chưa chọn kết luận Đạt / Không Đạt!',
            'status.in' => 'Kết luận chỉ nhận Đạt hoặc Không Đạt!',
            'note.max' => 'Ghi chú tối đa 255 ký tự!',
            'after_pass.in' => 'Lựa chọn sau khi đạt không hợp lệ!',
            'stop_reason.max' => 'Lý do ngưng đánh giá tối đa 255 ký tự!',
        ]);

        // Còn mốc nào chưa làm ở sau thì mới có chuyện chạy tiếp hay ngưng
        $remaining = DB::table(self::ITEM_TABLE)
            ->where(self::ITEM_FK, $list->id)
            ->where('id', '!=', $item->id)
            ->where('status', self::ITEM_INITIAL)
            ->count();

        $wantStop = $request->after_pass === self::AFTER_STOP;

        $validator->after(function ($validator) use ($request, $remaining, $wantStop) {
            if ($request->status === self::ITEM_PASSED && $remaining > 0 && ! $request->after_pass) {
                $validator->errors()->add('after_pass', 'Chưa chọn đánh giá tiếp hay ngưng đánh giá!');
            }

            // Ngưng là một quyết định, phải nói rõ vì sao thì lần sau đọc lại mới hiểu
            if ($wantStop && trim((string) $request->stop_reason) === '') {
                $validator->errors()->add('stop_reason', 'Chọn ngưng đánh giá thì phải nhập lý do!');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'assessErrors')->withInput();
        }

        $failed = $request->status === self::ITEM_FAILED;

        // Không Đạt thì dừng dù người dùng có chọn gì; Đạt thì dừng khi người dùng chọn dừng
        $stop = ($failed || $wantStop) && $remaining > 0;

        $stopReason = $failed
            ? 'Mốc '.$this->itemTargetOf($item).' kết luận Không Đạt.'
            : trim((string) $request->stop_reason);

        DB::transaction(function () use ($request, $item, $list, $stop, $stopReason, $remaining) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update([
                'done_at' => $request->done_at,
                'result' => $request->result,
                'status' => $request->status,
                'note' => $this->nullIfBlank($request->note),
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $newStatus = $stop ? self::STATUS_STOPPED : $this->statusFromItems($list->id);

            DB::table(self::TABLE)->where('id', $list->id)->update([
                'status' => $newStatus,
                'stop_reason' => $stop ? $this->cut($stopReason, 255) : null,
                'stopped_at' => $stop ? now() : null,
                'stopped_by' => $stop ? $this->actor() : null,
            ]);

            $note = 'Thực hiện '.$this->day($request->done_at);

            if ($list->status !== $newStatus) {
                $note .= '. Phiếu chuyển sang "'.$newStatus.'"';
            }

            $this->writeHistory(
                $list->id,
                'Ghi kết quả',
                $item->id,
                $this->itemTargetOf($item),
                $item->result ? $item->status.' - '.$item->result : 'Chưa có kết quả',
                $request->status.' - '.$request->result,
                $note
            );

            // Ngưng là một quyết định riêng, ghi thành dòng nhật ký riêng để đọc lại rõ
            if ($stop) {
                $this->writeHistory(
                    $list->id,
                    'Ngưng đánh giá',
                    $item->id,
                    $this->itemTargetOf($item),
                    'Còn '.$remaining.' mốc chưa thực hiện',
                    'Ngưng tại mốc '.$this->itemTargetOf($item),
                    $stopReason
                );
            }
        });

        AuditTrialController::log(
            'Đánh giá',
            self::ITEM_TABLE,
            $item->id,
            'status: '.$item->status,
            'status: '.$request->status.' - '.$request->result.($stop ? ' (ngưng phiếu)' : '')
        );

        if ($stop) {
            return redirect()->back()->with('success', $failed
                ? 'Mốc '.$item->name.' Không Đạt - phiếu đã ngưng đánh giá, '.$remaining.' mốc còn lại sẽ không thực hiện!'
                : 'Đã ghi kết quả mốc '.$item->name.' và ngưng đánh giá, '.$remaining.' mốc còn lại sẽ không thực hiện!');
        }

        return redirect()->back()->with('success', 'Đã ghi kết quả đánh giá cho mốc '.$item->name.'!');
    }

    /**
     * Mở lại phiếu đã NGƯNG để đánh giá tiếp các mốc còn lại.
     *
     * Khác với mở lại phiếu Huỷ ở chỗ phần lý do ngưng được xoá trắng; các dòng nhật ký
     * của lần ngưng trước vẫn còn nguyên nên vẫn truy được là đã từng ngưng vì lý do gì.
     */
    public function resume(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần mở lại!');
        }

        if ($current->status !== self::STATUS_STOPPED) {
            return redirect()->back()->with('error', 'Phiếu này không ở trạng thái ngưng đánh giá!');
        }

        $newStatus = $this->statusFromItems($current->id);

        DB::transaction(function () use ($current, $newStatus) {
            DB::table(self::TABLE)->where('id', $current->id)->update([
                'status' => $newStatus,
                'stop_reason' => null,
                'stopped_at' => null,
                'stopped_by' => null,
            ]);

            $this->writeHistory(
                $current->id,
                'Đánh giá tiếp',
                null,
                null,
                'Trạng thái: '.$current->status.($current->stop_reason ? ' - '.$current->stop_reason : ''),
                'Trạng thái: '.$newStatus
            );
        });

        AuditTrialController::log('Mở lại', self::TABLE, $current->id, 'status: '.$current->status, 'status: '.$newStatus);

        return redirect()->back()->with('success', 'Đã mở lại phiếu, trạng thái hiện tại: '.$newStatus.'.');
    }

    /**
     * CẤP PHÁT CHUẨN theo từng chỉ tiêu kiểm của một mốc.
     *
     * Mỗi chỉ tiêu của mốc được tick "đã cấp phát chuẩn" riêng kèm ghi chú riêng, vì
     * chuẩn thường cấp làm nhiều lần chứ không cấp một thể cho cả mốc.
     *
     * Form gửi lên theo TÊN chỉ tiêu chứ không theo vị trí: danh sách chỉ tiêu của mốc
     * có thể đã bị sửa ở tab khác trong lúc modal đang mở, ghép theo tên thì tick không
     * bị lệch sang chỉ tiêu khác. Tên không còn trong mốc thì bỏ qua.
     */
    public function issueTestings(Request $request)
    {
        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' cần cấp phát chuẩn!');
        }

        if (! $this->running($list)) {
            return redirect()->back()->with('error', $list->status === self::STATUS_STOPPED
                ? 'Phiếu đã ngưng đánh giá nên không ghi cấp phát chuẩn được nữa!'
                : 'Phiếu đã huỷ nên không ghi cấp phát chuẩn được nữa!');
        }

        $current = $this->testingList($item->testings);

        if (! $current) {
            return redirect()->back()->with('error', 'Mốc này chưa chọn chỉ tiêu kiểm nào để cấp phát chuẩn!');
        }

        $issued = array_map('strval', (array) $request->input('issued', []));
        $notes = (array) $request->input('notes', []);

        $validator = Validator::make([], []);

        foreach ($notes as $name => $note) {
            if (mb_strlen(trim((string) $note)) > self::TESTING_NOTE_LENGTH) {
                $validator->errors()->add('notes.'.$name, 'Ghi chú của chỉ tiêu "'.$name.'" tối đa '.self::TESTING_NOTE_LENGTH.' ký tự!');
            }
        }

        if ($validator->errors()->isNotEmpty()) {
            return redirect()->back()->withErrors($validator, 'issueErrors')->withInput();
        }

        $updated = [];

        foreach ($current as $testing) {
            $updated[] = [
                'name' => $testing['name'],
                'issued' => in_array($testing['name'], $issued, true),
                'note' => $this->nullIfBlank($notes[$testing['name']] ?? null),
            ];
        }

        $json = json_encode($updated, JSON_UNESCAPED_UNICODE);

        if (strlen($json) > self::TESTINGS_LENGTH) {
            return redirect()->back()->with('error', 'Ghi chú cấp phát quá dài, hãy viết ngắn lại!');
        }

        DB::transaction(function () use ($item, $list, $current, $updated, $json) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update([
                'testings' => $json,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $this->writeHistory(
                $list->id,
                'Cấp phát chuẩn',
                $item->id,
                $this->itemTargetOf($item),
                $this->issueDigest($current),
                $this->issueDigest($updated)
            );
        });

        $count = count(array_filter($updated, fn ($testing) => $testing['issued']));

        AuditTrialController::log(
            'Cấp phát',
            self::ITEM_TABLE,
            $item->id,
            $this->issueDigest($current),
            $this->issueDigest($updated)
        );

        return redirect()->back()->with('success', 'Đã ghi cấp phát chuẩn cho mốc '.$item->name.': '
            .$count.'/'.count($updated).' chỉ tiêu đã cấp phát.');
    }

    /* ==========================================================
     |  TRUY VẤN DÙNG CHUNG
     ========================================================== */

    /** Phiếu kèm thông tin ống chuẩn, chỉ lấy ống của phòng ban đang chọn. */
    private function listQuery(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->join('standard_imports', self::TABLE.'.import_id', '=', 'standard_imports.id')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->select(
                self::TABLE.'.*',
                'standard_imports.code as import_code',
                'standard_imports.group_code',
                'standard_imports.batch_no',
                'standard_imports.imported_date',
                'standard_imports.expired_date',
                'standard_imports.internal_expired_date',
                'standard_imports.potency',
                'standard_imports.standard_form',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_names.name as standard_name',
                'manufacturers.short_name as manufacturer_short_name'
            )
            ->where('standard_imports.department_id', $departmentId);
    }

    /**
     * Các mốc của một phiếu, đã tính sẵn tình trạng để view không phải tính lại.
     *
     * $stopped: phiếu đã ngưng đánh giá thì mốc chưa làm không còn là việc phải làm nữa.
     */
    private function itemsOf(int $listId, bool $stopped = false)
    {
        $today = now()->startOfDay();

        return DB::table(self::ITEM_TABLE)
            ->where(self::ITEM_FK, $listId)
            ->orderBy('timepoint', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($row) use ($today, $stopped) {
                $row->testing_list = $this->testingList($row->testings);
                $row->testing_names = array_column($row->testing_list, 'name');
                $row->issued_count = count(array_filter($row->testing_list, fn ($t) => $t['issued']));
                $row->days_to_due = $row->due_date
                    ? (int) $today->diffInDays(\Carbon\Carbon::parse($row->due_date)->startOfDay(), false)
                    : null;
                $row->state = $this->itemState($row, $stopped);
                $row->state_label = self::ITEM_STATES[$row->state];

                return $row;
            });
    }

    /**
     * Tình trạng của một mốc - tính ra từ due_date, không lưu DB.
     *
     * Có kết quả rồi thì không còn xét đến hạn nữa, dù ngày đến hạn đã qua. Phiếu đã
     * ngưng thì mốc chưa làm cũng thôi không xét: nó sẽ không được thực hiện nữa.
     */
    private function itemState($row, bool $stopped = false): string
    {
        if ($row->status !== self::ITEM_INITIAL) {
            return 'done';
        }

        if ($stopped) {
            return 'stopped';
        }

        if ($row->days_to_due === null) {
            return 'waiting';
        }

        if ($row->days_to_due < 0) {
            return 'overdue';
        }

        return $row->days_to_due <= self::DUE_SOON_DAYS ? 'due' : 'waiting';
    }

    /** Thống kê mốc của nhiều phiếu cùng lúc, đổ vào bảng danh sách. */
    private function itemStats(array $listIds): array
    {
        if (! $listIds) {
            return [];
        }

        $today = now()->startOfDay()->format('Y-m-d');
        $dueLimit = now()->startOfDay()->addDays(self::DUE_SOON_DAYS)->format('Y-m-d');

        $rows = DB::table(self::ITEM_TABLE)
            ->whereIn(self::ITEM_FK, $listIds)
            ->orderBy('due_date', 'asc')
            ->get();

        $stats = [];

        foreach ($rows as $row) {
            $key = $row->{self::ITEM_FK};

            $stats[$key] ??= [
                'total' => 0,
                'done' => 0,
                'overdue' => 0,
                'due' => 0,
                'next_due_date' => null,
            ];

            $stats[$key]['total']++;

            if ($row->status !== self::ITEM_INITIAL) {
                $stats[$key]['done']++;

                continue;
            }

            $due = $row->due_date ? substr((string) $row->due_date, 0, 10) : null;

            if ($due === null) {
                continue;
            }

            if ($due < $today) {
                $stats[$key]['overdue']++;
            } elseif ($due <= $dueLimit) {
                $stats[$key]['due']++;
            }

            // Mốc chưa làm gần nhất - đã orderBy due_date nên dòng đầu tiên gặp là sớm nhất
            $stats[$key]['next_due_date'] ??= $due;
        }

        return $stats;
    }

    /**
     * Ống chuẩn được chọn khi lập phiếu.
     *
     * Chỉ lấy ống CHUẨN THỨ CẤP (CTC) và còn hiệu lực của phòng ban đang chọn; ống đã
     * có phiếu chưa huỷ bị loại hẳn khỏi danh sách vì mỗi ống chỉ theo dõi trên một phiếu.
     */
    private function importOptions(int $departmentId)
    {
        $taken = DB::table(self::TABLE)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->pluck('import_id')
            ->all();

        return DB::table('standard_imports')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->select(
                'standard_imports.id',
                'standard_imports.code',
                'standard_imports.batch_no',
                'standard_imports.imported_date',
                'standard_imports.expired_date',
                'standard_categories.code as category_code',
                'standard_names.name as standard_name'
            )
            ->where('standard_imports.department_id', $departmentId)
            ->where('standard_imports.status_id', 1)
            ->where('standard_imports.group_code', $this->groupCode())
            ->when($taken, fn ($query) => $query->whereNotIn('standard_imports.id', $taken))
            ->orderBy('standard_imports.code', 'asc')
            ->get();
    }

    /* ==========================================================
     |  TIỆN ÍCH
     ========================================================== */

    /** Phiếu của đúng phòng ban đang chọn, tránh sửa nhầm phiếu phòng ban khác. */
    private function findOwn($id)
    {
        return $this->listQuery($this->departmentId())
            ->where(self::TABLE.'.id', $id)
            ->first();
    }

    /** Một mốc kèm phiếu chứa nó: [item, list]. */
    private function findItem($id): array
    {
        $item = DB::table(self::ITEM_TABLE)->where('id', $id)->first();

        if (! $item) {
            return [null, null];
        }

        $list = $this->findOwn($item->{self::ITEM_FK});

        return $list ? [$item, $list] : [null, null];
    }

    /** Ống chuẩn còn hiệu lực của phòng ban đang chọn. */
    private function findImport($id)
    {
        return DB::table('standard_imports')
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->where('status_id', 1)
            ->first();
    }

    /** Mã nhóm chuẩn được đánh giá, đúng phần mã nhóm nằm trong mã ống chuẩn. */
    private function groupCode(): string
    {
        return config('standard.groups.'.self::GROUP_KEY.'.code', self::GROUP_KEY);
    }

    /** Tên nhóm chuẩn được đánh giá, để viết câu thông báo cho người dùng. */
    private function groupName(): string
    {
        return config('standard.groups.'.self::GROUP_KEY.'.name', self::GROUP_KEY);
    }

    /** Phiếu đã huỷ thì khoá mọi thao tác ghi. */
    private function editable($list): bool
    {
        return $list && $list->status !== self::STATUS_CANCELLED;
    }

    /**
     * Phiếu còn ĐANG CHẠY, tức còn ghi kết quả và cấp phát chuẩn được.
     *
     * Chặt hơn editable(): phiếu đã ngưng thì các mốc còn lại không thực hiện nữa nên
     * không ghi thêm gì vào chúng được, muốn làm tiếp phải mở lại phiếu trước.
     */
    private function running($list): bool
    {
        return $this->editable($list) && $list->status !== self::STATUS_STOPPED;
    }

    /** Trạng thái phiếu suy ra từ tiến độ các mốc. */
    private function statusFromItems(int $listId): string
    {
        $total = DB::table(self::ITEM_TABLE)->where(self::ITEM_FK, $listId)->count();

        if ($total === 0) {
            return self::STATUS_INITIAL;
        }

        $done = DB::table(self::ITEM_TABLE)
            ->where(self::ITEM_FK, $listId)
            ->where('status', '!=', self::ITEM_INITIAL)
            ->count();

        if ($done === 0) {
            return self::STATUS_INITIAL;
        }

        return $done >= $total ? self::STATUS_DONE : self::STATUS_RUNNING;
    }

    /** Ngày đến hạn của một mốc = ngày bắt đầu + timepoint tháng. */
    private function dueDate(string $startDate, int $timepoint): string
    {
        return \Carbon\Carbon::parse($startDate)->addMonthsNoOverflow($timepoint)->format('Y-m-d');
    }

    /** Tên mốc suy ra từ số tháng, dùng chung cho form lập phiếu và nút sinh nhanh. */
    private function itemName(int $timepoint): string
    {
        return $timepoint === 0 ? 'Ban đầu' : $timepoint.' Tháng';
    }

    /**
     * Chuỗi JSON trong cột testings -> mảng chỉ tiêu ĐẦY ĐỦ để hiển thị.
     *
     * Mỗi phần tử trả về luôn có đủ ba khoá: name / issued / note, dù trong DB đang lưu
     * kiểu nào. Cột này từng lưu mảng TÊN trần ["Định tính", ...]; phiếu cũ ghi trước
     * khi có phần cấp phát chuẩn vẫn nằm nguyên như vậy nên vẫn phải đọc được, và được
     * hiểu là chỉ tiêu chưa cấp phát, chưa có ghi chú.
     */
    private function testingList($value): array
    {
        $items = json_decode((string) $value, true);

        if (! is_array($items)) {
            return [];
        }

        $list = [];

        foreach ($items as $item) {
            // Kiểu cũ: phần tử là chính tên chỉ tiêu. Kiểu mới: object có name/issued/note.
            $name = is_array($item) ? trim((string) ($item['name'] ?? '')) : trim((string) $item);

            if ($name === '') {
                continue;
            }

            $list[] = [
                'name' => $name,
                'issued' => is_array($item) ? (bool) ($item['issued'] ?? false) : false,
                'note' => is_array($item) ? $this->nullIfBlank($item['note'] ?? null) : null,
            ];
        }

        return $list;
    }

    /** Chỉ lấy TÊN các chỉ tiêu - dùng cho ô chọn, nhật ký và phần kiểm tra dữ liệu. */
    private function testingNames($value): array
    {
        return array_column($this->testingList($value), 'name');
    }

    /**
     * Danh sách chỉ tiêu đã chọn -> mảng JSON để ghi vào cột testings.
     *
     * $current là nội dung đang lưu của mốc: chỉ tiêu nào vẫn còn được chọn thì GIỮ
     * NGUYÊN phần đã cấp phát và ghi chú của nó, sửa mốc không được xoá mất dữ liệu
     * cấp phát đã ghi. Chỉ tiêu bỏ chọn thì mất theo, chỉ tiêu mới bắt đầu từ chưa cấp.
     */
    private function testingsJson($value, $current = null): ?string
    {
        $keep = [];

        foreach ($this->testingList($current) as $item) {
            $keep[$item['name']] = $item;
        }

        $items = [];

        foreach ($this->splitTestings($value) as $name) {
            $items[] = $keep[$name] ?? ['name' => $name, 'issued' => false, 'note' => null];
        }

        return $items ? json_encode($items, JSON_UNESCAPED_UNICODE) : null;
    }

    /**
     * Chuẩn hoá phần chỉ tiêu người dùng gửi lên: bỏ giá trị trống và giá trị trùng.
     *
     * Ô chọn nhiều gửi lên dạng mảng; vẫn nhận cả chuỗi nhiều dòng để dữ liệu nhập tay
     * từ trước (nếu có) không bị mất khi lưu lại.
     */
    private function splitTestings($value): array
    {
        $items = is_array($value)
            ? $value
            : (preg_split('/\r\n|\r|\n/', (string) $value) ?: []);

        $items = array_map(fn ($item) => trim((string) $item), $items);

        return array_values(array_unique(array_filter($items, fn ($item) => $item !== '')));
    }

    /**
     * CHỈ TIÊU KIỂM lấy từ Dữ Liệu Gốc (bảng purposes), chỉ lấy chỉ tiêu còn hiệu lực.
     *
     * Cột testings lưu TÊN chỉ tiêu chứ không lưu id: đây là bản ghi việc đã kiểm những
     * gì tại một thời điểm, đổi tên chỉ tiêu ở danh mục về sau không được sửa ngược lại
     * lịch sử đánh giá.
     */
    private function criteriaOptions(): array
    {
        return DB::table('purposes')
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->all();
    }

    /* ==========================================================
     |  NHẬT KÝ THAY ĐỔI CỦA PHIẾU
     ========================================================== */

    /**
     * Ghi một dòng nhật ký, luôn gắn với id của PHIẾU.
     *
     * Bảng chỉ ghi thêm, không sửa không xoá. Gọi bên trong transaction của hành động
     * để nhật ký và dữ liệu cùng vào hoặc cùng không.
     */
    private function writeHistory(
        int $listId,
        string $action,
        ?int $itemId = null,
        ?string $target = null,
        ?string $old = null,
        ?string $new = null,
        ?string $note = null
    ): void {
        DB::table(self::HISTORY_TABLE)->insert([
            self::ITEM_FK => $listId,
            'item_id' => $itemId,
            'action' => $action,
            'target' => $this->cut($target, 100),
            'old_values' => $this->cut($old, 1000),
            'new_values' => $this->cut($new, 1000),
            'note' => $this->cut($note, 500),
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    /** Nhật ký của một phiếu, mới nhất nằm trên cùng. */
    private function historiesOf(int $listId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->where(self::ITEM_FK, $listId)
            ->orderByDesc('id')
            ->get();
    }

    /** Đầu phiếu tóm tắt thành một dòng chữ để so sánh trước / sau. */
    private function listDigest($startDate, $period, $note): string
    {
        $parts = [
            'Bắt đầu '.$this->day($startDate),
            'chu kỳ '.(int) $period.' tháng',
        ];

        if (trim((string) $note) !== '') {
            $parts[] = 'ghi chú: '.trim((string) $note);
        }

        return implode(', ', $parts);
    }

    /** Tên gọi ngắn của một mốc trong nhật ký: "T6 - 6 Tháng". */
    private function itemTarget(array $data): string
    {
        return 'T'.(int) $data['timepoint'].' - '.$data['name'];
    }

    private function itemTargetOf($row): string
    {
        return $this->itemTarget(['timepoint' => $row->timepoint, 'name' => $row->name]);
    }

    /** Nội dung một mốc tóm tắt thành một dòng chữ để so sánh trước / sau. */
    private function itemDigest(array $data): string
    {
        $testings = $this->testingNames($data['testings'] ?? null);

        $parts = [
            'T'.(int) $data['timepoint'],
            $data['name'],
            'đến hạn '.$this->day($data['due_date'] ?? null),
            'chỉ tiêu: '.($testings ? implode(', ', $testings) : '—'),
        ];

        if (trim((string) ($data['note'] ?? '')) !== '') {
            $parts[] = 'ghi chú: '.trim((string) $data['note']);
        }

        return implode(' · ', $parts);
    }

    private function itemDigestOf($row): string
    {
        return $this->itemDigest([
            'timepoint' => $row->timepoint,
            'name' => $row->name,
            'due_date' => $row->due_date,
            'testings' => $row->testings,
            'note' => $row->note,
        ]);
    }

    /**
     * Tình hình cấp phát chuẩn của một mốc tóm tắt thành một dòng chữ cho nhật ký.
     *
     * Ví dụ: "Định tính [đã cấp: cấp 2 ống] · Định lượng [chưa cấp]".
     */
    private function issueDigest(array $testings): string
    {
        $parts = [];

        foreach ($testings as $testing) {
            $state = $testing['issued'] ? 'đã cấp' : 'chưa cấp';

            if ($testing['issued'] && $testing['note']) {
                $state .= ': '.$testing['note'];
            }

            $parts[] = $testing['name'].' ['.$state.']';
        }

        return $parts ? implode(' · ', $parts) : '—';
    }

    /** Ngày dạng d/m/Y cho nhật ký, trống thì gạch ngang. */
    private function day($value): string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    }

    /** Cắt cho vừa độ dài cột, tính theo ký tự chứ không theo byte. */
    private function cut(?string $value, int $length): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return mb_strlen($value) > $length ? mb_substr($value, 0, $length - 1).'…' : $value;
    }

    /* ==========================================================
     |  KIỂM TRA DỮ LIỆU NHẬP
     ========================================================== */

    /** @param  bool  $withImport  Chỉ lúc lập phiếu mới chọn ống chuẩn, sửa thì không đổi được. */
    private function rules(bool $withImport = true): array
    {
        $rules = [
            'start_date' => ['required', 'date'],
            'assessment_period' => ['required', 'integer', 'min:1', 'max:60'],
            'note' => ['nullable', 'string', 'max:255'],
        ];

        if ($withImport) {
            $rules['import_id'] = ['required', 'integer', 'exists:standard_imports,id'];
        }

        return $rules;
    }

    private function messages(): array
    {
        return [
            'import_id.required' => 'Chưa chọn mã ống chuẩn cần đánh giá!',
            'import_id.exists' => 'Mã ống chuẩn không tồn tại!',
            'start_date.required' => 'Chưa chọn ngày bắt đầu đánh giá!',
            'start_date.date' => 'Ngày bắt đầu đánh giá không hợp lệ!',
            'assessment_period.required' => 'Chưa nhập chu kỳ đánh giá!',
            'assessment_period.integer' => 'Chu kỳ đánh giá phải là số nguyên!',
            'assessment_period.min' => 'Chu kỳ đánh giá ít nhất là 1 tháng!',
            'assessment_period.max' => 'Chu kỳ đánh giá tối đa 60 tháng!',
            'note.max' => 'Ghi chú tối đa 255 ký tự!',
        ];
    }

    private function itemRules(): array
    {
        return [
            self::ITEM_FK => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:100'],
            // tinyInteger: mốc lớn nhất lưu được là 127 tháng
            'timepoint' => ['required', 'integer', 'min:0', 'max:127'],
            'due_date' => ['nullable', 'date'],
            // Ô chọn nhiều chỉ tiêu kiểm gửi lên dạng mảng tên chỉ tiêu
            'testings' => ['nullable', 'array'],
            'testings.*' => ['string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function itemMessages(): array
    {
        return [
            'name.required' => 'Chưa nhập tên mốc đánh giá!',
            'name.max' => 'Tên mốc đánh giá tối đa 100 ký tự!',
            'timepoint.required' => 'Chưa nhập mốc thời gian!',
            'timepoint.integer' => 'Mốc thời gian phải là số nguyên (số tháng)!',
            'timepoint.min' => 'Mốc thời gian không được nhỏ hơn 0!',
            'timepoint.max' => 'Mốc thời gian tối đa 127 tháng!',
            'due_date.date' => 'Ngày đến hạn không hợp lệ!',
            'note.max' => 'Ghi chú tối đa 255 ký tự!',
        ];
    }

    /** Một phiếu không có hai mốc cùng số tháng. */
    private function checkDuplicateTimepoint($validator, int $listId, int $timepoint, $ignoreId = null): void
    {
        $validator->after(function ($validator) use ($listId, $timepoint, $ignoreId) {
            $exists = DB::table(self::ITEM_TABLE)
                ->where(self::ITEM_FK, $listId)
                ->where('timepoint', $timepoint)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists();

            if ($exists) {
                $validator->errors()->add('timepoint', 'Phiếu đã có mốc T'.$timepoint.' rồi!');
            }
        });
    }

    /** Số chỉ tiêu và độ dài chuỗi JSON phải nằm gọn trong cột testings. */
    private function checkTestings($validator, Request $request): void
    {
        $validator->after(function ($validator) use ($request) {
            $error = $this->testingsError($request->testings);

            if ($error) {
                $validator->errors()->add('testings', $error);
            }
        });
    }

    /**
     * Kiểm tra một danh sách chỉ tiêu, trả về câu lỗi hoặc null nếu hợp lệ.
     *
     * Chỉ tiêu phải nằm trong Dữ Liệu Gốc còn hiệu lực - ô chọn đã lọc sẵn nhưng vẫn
     * chặn lại ở đây để không nhận được chữ tự gõ gửi thẳng lên.
     */
    private function testingsError($value): ?string
    {
        $items = $this->splitTestings($value);

        if (! $items) {
            return null;
        }

        if (count($items) > self::MAX_TESTINGS) {
            return 'Mỗi mốc chỉ chọn tối đa '.self::MAX_TESTINGS.' chỉ tiêu kiểm!';
        }

        $unknown = array_diff($items, $this->criteriaOptions());

        if ($unknown) {
            return 'Chỉ tiêu kiểm không có trong Dữ Liệu Gốc: '.implode(', ', $unknown).'!';
        }

        // Đo trên đúng format sẽ ghi xuống DB (object có name/issued/note), không đo mảng tên trần
        $stored = array_map(fn ($name) => ['name' => $name, 'issued' => false, 'note' => null], $items);

        if (strlen(json_encode($stored, JSON_UNESCAPED_UNICODE)) > self::TESTINGS_LENGTH) {
            return 'Danh sách chỉ tiêu kiểm quá dài, hãy chọn bớt lại!';
        }

        return null;
    }

    /**
     * Bỏ những dòng mốc để trống trước khi kiểm tra dữ liệu.
     *
     * Form lập phiếu mở sẵn vài dòng trống, dòng nào không khai thời điểm kiểm thì bị
     * loại ở đây thay vì báo lỗi. Không khai dòng nào cũng lưu được.
     */
    private function pruneEmptyItems(Request $request): void
    {
        $rows = array_values(array_filter(
            (array) $request->input('items', []),
            fn ($row) => is_array($row) && trim((string) ($row['timepoint'] ?? '')) !== ''
        ));

        $request->merge(['items' => $rows]);
    }

    /** Các dòng mốc khai ngay trên form lập phiếu. */
    private function checkItemRows($validator, Request $request): void
    {
        $validator->after(function ($validator) use ($request) {
            $seen = [];

            foreach ((array) $request->input('items', []) as $index => $row) {
                $raw = trim((string) ($row['timepoint'] ?? ''));
                $key = 'items.'.$index.'.timepoint';

                if (! preg_match('/^\d+$/', $raw)) {
                    $validator->errors()->add($key, 'Thời điểm kiểm dòng '.($index + 1).' phải là số tháng nguyên, không âm!');

                    continue;
                }

                $timepoint = (int) $raw;

                // tinyInteger chỉ chứa tới 127 tháng
                if ($timepoint > 127) {
                    $validator->errors()->add($key, 'Thời điểm kiểm dòng '.($index + 1).' tối đa 127 tháng!');

                    continue;
                }

                if (isset($seen[$timepoint])) {
                    $validator->errors()->add($key, 'Thời điểm kiểm '.$timepoint.' tháng bị khai trùng ở dòng '.($index + 1).'!');

                    continue;
                }

                $seen[$timepoint] = true;

                $error = $this->testingsError($row['testings'] ?? null);

                if ($error) {
                    $validator->errors()->add('items.'.$index.'.testings', 'Dòng '.($index + 1).': '.$error);
                }

                if (mb_strlen(trim((string) ($row['note'] ?? ''))) > 255) {
                    $validator->errors()->add('items.'.$index.'.note', 'Ghi chú dòng '.($index + 1).' tối đa 255 ký tự!');
                }
            }
        });
    }

    /**
     * Phần dữ liệu chung của thêm mới / cập nhật một mốc.
     *
     * $current là mốc đang sửa (thêm mới thì null) - truyền xuống để phần đã cấp phát
     * chuẩn của các chỉ tiêu vẫn còn được chọn không bị xoá mất khi lưu lại.
     */
    private function itemPayload(Request $request, $list, $current = null): array
    {
        $timepoint = (int) $request->timepoint;

        return [
            'name' => trim((string) $request->name),
            'timepoint' => $timepoint,
            // Để trống thì tính theo chu kỳ: ngày bắt đầu + số tháng của mốc
            'due_date' => $this->nullIfBlank($request->due_date)
                ?? $this->dueDate(substr((string) $list->start_date, 0, 10), $timepoint),
            'testings' => $this->testingsJson($request->testings, $current->testings ?? null),
            'note' => $this->nullIfBlank($request->note),
        ];
    }

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }
}
