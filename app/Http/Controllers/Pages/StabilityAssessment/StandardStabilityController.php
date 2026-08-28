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
 *      testings  : các chỉ tiêu cần thử nghiệm tại mốc đó (mảng JSON).
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
 *      Huỷ           : người dùng dừng phiếu. Phiếu KHÔNG xoá cứng, chỉ chuyển "Huỷ".
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

    /** Còn dưới ngần này ngày là mốc "Sắp đến hạn". */
    private const DUE_SOON_DAYS = 30;

    /** Số chỉ tiêu thử nghiệm tối đa của một mốc, và độ dài cột testings. */
    private const MAX_TESTINGS = 20;

    private const TESTINGS_LENGTH = 500;

    /** Tên hiển thị của tình trạng từng mốc, tính ra từ due_date chứ không lưu DB. */
    public const ITEM_STATES = [
        'done' => 'Đã đánh giá',
        'overdue' => 'Quá hạn',
        'due' => 'Sắp đến hạn',
        'waiting' => 'Chưa tới hạn',
    ];

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
            $row->item_overdue = (int) ($stat['overdue'] ?? 0);
            $row->item_due = (int) ($stat['due'] ?? 0);
            $row->next_due_date = $stat['next_due_date'] ?? null;
            $row->progress = $row->item_total > 0
                ? (int) round($row->item_done / $row->item_total * 100)
                : 0;

            return $row;
        });

        session()->put(['title' => 'ĐÁNH GIÁ HẠN DÙNG - CHẤT CHUẨN']);

        return view('pages.stabilityAssessment.StandardStability.list', [
            'datas' => $datas,
            'imports' => $this->importOptions($departmentId),
            'statuses' => [self::STATUS_INITIAL, self::STATUS_RUNNING, self::STATUS_DONE, self::STATUS_CANCELLED],
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

        return view('pages.stabilityAssessment.StandardStability.detail', [
            'list' => $list,
            'items' => $this->itemsOf($list->id),
            'itemStates' => self::ITEM_STATES,
            'itemResults' => self::ITEM_RESULTS,
            'groups' => config('standard.groups'),
            'dueSoonDays' => self::DUE_SOON_DAYS,
            'maxTestings' => self::MAX_TESTINGS,
            'criterias' => $this->criteriaOptions(),
            'histories' => $this->historiesOf($list->id),
            'editable' => $this->editable($list),
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

        $payload = $this->itemPayload($request, $list);

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
     * Ghi kết quả đánh giá của một mốc.
     *
     * Ghi xong thì trạng thái phiếu được tính lại: còn mốc chưa làm là "Đang Đánh Giá",
     * xong hết là "Hoàn Thành".
     */
    public function assess(Request $request)
    {
        [$item, $list] = $this->findItem($request->id);

        if (! $item) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' cần ghi kết quả!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu đã huỷ nên không ghi kết quả được nữa!');
        }

        $validator = Validator::make($request->all(), [
            'done_at' => ['required', 'date'],
            'result' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:'.implode(',', self::ITEM_RESULTS)],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'done_at.required' => 'Chưa chọn ngày thực hiện đánh giá!',
            'done_at.date' => 'Ngày thực hiện đánh giá không hợp lệ!',
            'result.required' => 'Chưa nhập kết quả đánh giá!',
            'result.max' => 'Kết quả đánh giá tối đa 255 ký tự!',
            'status.required' => 'Chưa chọn kết luận Đạt / Không Đạt!',
            'status.in' => 'Kết luận chỉ nhận Đạt hoặc Không Đạt!',
            'note.max' => 'Ghi chú tối đa 255 ký tự!',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'assessErrors')->withInput();
        }

        DB::transaction(function () use ($request, $item, $list) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update([
                'done_at' => $request->done_at,
                'result' => $request->result,
                'status' => $request->status,
                'note' => $this->nullIfBlank($request->note),
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $newStatus = $this->statusFromItems($list->id);

            DB::table(self::TABLE)->where('id', $list->id)->update(['status' => $newStatus]);

            $this->writeHistory(
                $list->id,
                'Ghi kết quả',
                $item->id,
                $this->itemTargetOf($item),
                $item->result ? $item->status.' - '.$item->result : 'Chưa có kết quả',
                $request->status.' - '.$request->result,
                'Thực hiện '.$this->day($request->done_at)
                    .($list->status !== $newStatus ? '. Phiếu chuyển sang "'.$newStatus.'"' : '')
            );
        });

        AuditTrialController::log(
            'Đánh giá',
            self::ITEM_TABLE,
            $item->id,
            'status: '.$item->status,
            'status: '.$request->status.' - '.$request->result
        );

        return redirect()->back()->with('success', 'Đã ghi kết quả đánh giá cho mốc '.$item->name.'!');
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

    /** Các mốc của một phiếu, đã tính sẵn tình trạng để view không phải tính lại. */
    private function itemsOf(int $listId)
    {
        $today = now()->startOfDay();

        return DB::table(self::ITEM_TABLE)
            ->where(self::ITEM_FK, $listId)
            ->orderBy('timepoint', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($row) use ($today) {
                $row->testing_list = $this->testingList($row->testings);
                $row->days_to_due = $row->due_date
                    ? (int) $today->diffInDays(\Carbon\Carbon::parse($row->due_date)->startOfDay(), false)
                    : null;
                $row->state = $this->itemState($row);
                $row->state_label = self::ITEM_STATES[$row->state];

                return $row;
            });
    }

    /**
     * Tình trạng của một mốc - tính ra từ due_date, không lưu DB.
     *
     * Có kết quả rồi thì không còn xét đến hạn nữa, dù ngày đến hạn đã qua.
     */
    private function itemState($row): string
    {
        if ($row->status !== self::ITEM_INITIAL) {
            return 'done';
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

    /** Chuỗi JSON trong cột testings -> mảng chỉ tiêu để hiển thị. */
    private function testingList($value): array
    {
        $items = json_decode((string) $value, true);

        return is_array($items) ? array_values(array_filter(array_map('strval', $items), fn ($item) => $item !== '')) : [];
    }

    /** Danh sách chỉ tiêu đã chọn -> mảng JSON để ghi vào cột testings. */
    private function testingsJson($value): ?string
    {
        $items = $this->splitTestings($value);

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
        $testings = $this->testingList($data['testings'] ?? null);

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

    /** Số chỉ tiêu và độ dài chuỗi JSON phải nằm gọn trong cột testings varchar(500). */
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

        if (strlen(json_encode($items, JSON_UNESCAPED_UNICODE)) > self::TESTINGS_LENGTH) {
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

    /** Phần dữ liệu chung của thêm mới / cập nhật một mốc. */
    private function itemPayload(Request $request, $list): array
    {
        $timepoint = (int) $request->timepoint;

        return [
            'name' => trim((string) $request->name),
            'timepoint' => $timepoint,
            // Để trống thì tính theo chu kỳ: ngày bắt đầu + số tháng của mốc
            'due_date' => $this->nullIfBlank($request->due_date)
                ?? $this->dueDate(substr((string) $list->start_date, 0, 10), $timepoint),
            'testings' => $this->testingsJson($request->testings),
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
        return session('user')['fullName'] ?? 'NA';
    }
}
