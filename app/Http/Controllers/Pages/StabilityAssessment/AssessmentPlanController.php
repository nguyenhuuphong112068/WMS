<?php

namespace App\Http\Controllers\Pages\StabilityAssessment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ĐÁNH GIÁ HẠN DÙNG - KẾ HOẠCH ĐÁNH GIÁ
 *
 * Màn hình Chuẩn Thứ Cấp xem theo TỪNG PHIẾU: mở một phiếu ra mới thấy các mốc của
 * ống chuẩn đó. Trang này xem ngược lại - theo THỜI GIAN: gom MỌI MỐC ĐÁNH GIÁ của
 * mọi phiếu còn hiệu lực trong phòng ban, xếp theo ngày đến hạn nằm trong khoảng
 * "từ ngày - đến ngày" người dùng chọn, để biết kỳ tới phải kiểm những ống chuẩn nào.
 *
 * Trang CHỈ ĐỌC, không ghi dữ liệu. Muốn ghi kết quả một mốc thì bấm vào dòng đó để
 * sang trang chi tiết phiếu bên Chuẩn Thứ Cấp.
 *
 * Mốc lấy vào kế hoạch:
 *      - Thuộc phiếu của phòng ban đang chọn và phiếu chưa bị Huỷ.
 *      - Có ngày đến hạn (due_date) rơi vào đúng khoảng đang xem.
 *
 * TÌNH TRẠNG từng mốc tính ra từ due_date so với hôm nay chứ không lưu DB, dùng đúng
 * bộ trạng thái của StandardStabilityController (Đã đánh giá / Quá hạn / Sắp đến hạn /
 * Chưa tới hạn) để hai màn hình không lệch cách hiểu.
 *
 * Ống chuẩn thứ cấp còn tồn mà chưa lập phiếu thì không có mốc nào để đưa vào kế
 * hoạch - đếm riêng và nhắc trên đầu trang để người dùng biết mà lập phiếu.
 */
class AssessmentPlanController extends Controller
{
    private const LIST_TABLE = 'standard_stability_assessment_list';

    private const ITEM_TABLE = 'standard_stability_assessment_item';

    /** Khoá ngoại của bảng mốc - viết ra hằng vì tên cột rất dài. */
    private const ITEM_FK = 'standard_stability_assessment_list_id';

    /** Chỉ chuẩn thứ cấp mới phải đánh giá hạn dùng - khớp với màn hình Chuẩn Thứ Cấp. */
    private const GROUP_KEY = 'CTC';

    /** Khoảng xem tối đa, chặn người dùng gõ tay khoảng vài chục năm làm bảng phình ra. */
    private const MAX_MONTHS = 60;

    /* ==========================================================
     |  KẾ HOẠCH ĐÁNH GIÁ THEO KHOẢNG THỜI GIAN
     ========================================================== */

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();
        $period = $this->period($request);

        $datas = $this->planQuery($departmentId)
            ->whereNotNull(self::ITEM_TABLE.'.due_date')
            ->whereBetween(self::ITEM_TABLE.'.due_date', [$period['from'], $period['to']])
            ->orderBy(self::ITEM_TABLE.'.due_date', 'asc')
            ->orderBy('standard_imports.code', 'asc')
            ->orderBy(self::ITEM_TABLE.'.timepoint', 'asc')
            ->get()
            ->map(fn ($row) => $this->decorate($row));

        session()->put(['title' => 'ĐÁNH GIÁ HẠN DÙNG - KẾ HOẠCH ĐÁNH GIÁ']);

        return view('pages.stabilityAssessment.AssessmentPlan.list', [
            'datas' => $datas,
            'period' => $period,
            // Số mốc của từng tháng trong khoảng, vẽ thành dải thời gian trên đầu bảng
            'months' => $this->months($period, $datas),
            'stateCounts' => $this->stateCounts($datas),
            // Bỏ 'stopped': phiếu đã ngưng không vào kế hoạch nên chip đó luôn bằng 0
            'itemStates' => array_diff_key(StandardStabilityController::ITEM_STATES, ['stopped' => null]),
            'dueSoonDays' => StandardStabilityController::DUE_SOON_DAYS,
            'groups' => config('standard.groups'),
            // Ống chuẩn thứ cấp còn tồn nhưng chưa có phiếu - nhắc để không sót ống nào
            'unplanned' => $this->unplanned($departmentId),
            'assessGroupName' => $this->groupName(),
            'assessGroupCode' => $this->groupCode(),
        ]);
    }

    /* ==========================================================
     |  TRUY VẤN
     ========================================================== */

    /**
     * Mọi mốc đánh giá kèm thông tin phiếu và ống chuẩn của nó.
     *
     * Chỉ lấy mốc của phòng ban đang chọn; phiếu đã Huỷ thì không còn theo dõi nữa nên
     * các mốc của nó cũng không nằm trong kế hoạch.
     */
    private function planQuery(int $departmentId)
    {
        return DB::table(self::ITEM_TABLE)
            ->join(self::LIST_TABLE, self::ITEM_TABLE.'.'.self::ITEM_FK, '=', self::LIST_TABLE.'.id')
            ->join('standard_imports', self::LIST_TABLE.'.import_id', '=', 'standard_imports.id')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->select(
                self::ITEM_TABLE.'.*',
                self::LIST_TABLE.'.id as list_id',
                self::LIST_TABLE.'.start_date',
                self::LIST_TABLE.'.assessment_period',
                self::LIST_TABLE.'.status as list_status',
                'standard_imports.code as import_code',
                'standard_imports.group_code',
                'standard_imports.batch_no',
                'standard_imports.expired_date',
                'standard_imports.internal_expired_date',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_names.name as standard_name',
                'manufacturers.short_name as manufacturer_short_name'
            )
            ->where('standard_imports.department_id', $departmentId)
            // Phiếu đã huỷ hoặc đã ngưng thì các mốc còn lại không thực hiện nữa
            ->whereNotIn(self::LIST_TABLE.'.status', [
                StandardStabilityController::STATUS_CANCELLED,
                StandardStabilityController::STATUS_STOPPED,
            ]);
    }

    /**
     * Ống CHUẨN THỨ CẤP còn hiệu lực mà chưa có phiếu đánh giá nào chưa huỷ.
     *
     * Không có phiếu nghĩa là chưa có mốc nào, ống đó sẽ không xuất hiện trên kế hoạch
     * dù chọn khoảng thời gian nào - nên đếm riêng và nhắc ngay trên đầu trang.
     */
    private function unplanned(int $departmentId)
    {
        $taken = DB::table(self::LIST_TABLE)
            ->where('status', '!=', StandardStabilityController::STATUS_CANCELLED)
            ->pluck('import_id')
            ->all();

        return DB::table('standard_imports')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->select(
                'standard_imports.id',
                'standard_imports.code',
                'standard_imports.batch_no',
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
     |  TÍNH TOÁN CHO MÀN HÌNH
     ========================================================== */

    /**
     * Bổ sung cho mỗi mốc phần view cần mà DB không lưu: chỉ tiêu dạng mảng, số ngày
     * còn lại tới hạn, tình trạng và tháng của mốc (để lọc theo dải thời gian).
     */
    private function decorate($row)
    {
        $today = now()->startOfDay();
        $due = $row->due_date ? \Carbon\Carbon::parse($row->due_date)->startOfDay() : null;

        $row->testing_list = $this->testingList($row->testings);
        $row->days_to_due = $due ? (int) $today->diffInDays($due, false) : null;
        $row->state = $this->itemState($row);
        $row->state_label = StandardStabilityController::ITEM_STATES[$row->state];
        $row->month_key = $due ? $due->format('Y-m') : '';

        return $row;
    }

    /**
     * Tình trạng của một mốc - tính từ due_date, giống hệt màn hình Chuẩn Thứ Cấp.
     *
     * Có kết quả rồi thì thôi không xét đến hạn nữa, dù ngày đến hạn đã qua.
     */
    private function itemState($row): string
    {
        if ($row->status !== StandardStabilityController::ITEM_INITIAL) {
            return 'done';
        }

        if ($row->days_to_due === null) {
            return 'waiting';
        }

        if ($row->days_to_due < 0) {
            return 'overdue';
        }

        return $row->days_to_due <= StandardStabilityController::DUE_SOON_DAYS ? 'due' : 'waiting';
    }

    /** Số mốc theo từng tình trạng, đổ vào các nút lọc nhanh. */
    private function stateCounts($datas): array
    {
        $counts = [];

        foreach (array_keys(StandardStabilityController::ITEM_STATES) as $state) {
            if ($state === 'stopped') {
                continue;
            }

            $counts[$state] = $datas->where('state', $state)->count();
        }

        return $counts;
    }

    /**
     * DẢI THỜI GIAN - mỗi tháng trong khoảng đang xem là một mốc kèm số việc phải làm.
     *
     * Liệt kê đủ mọi tháng của khoảng kể cả tháng không có mốc nào, để nhìn ra quãng
     * trống trong kế hoạch chứ không chỉ thấy các tháng bận.
     */
    private function months(array $period, $datas): array
    {
        $cursor = \Carbon\Carbon::parse($period['from'])->startOfMonth();
        $last = \Carbon\Carbon::parse($period['to'])->startOfMonth();
        $thisMonth = now()->format('Y-m');

        $months = [];

        while ($cursor->lte($last)) {
            $key = $cursor->format('Y-m');
            $rows = $datas->where('month_key', $key);

            $months[] = [
                'key' => $key,
                'label' => 'T'.$cursor->format('n').'/'.$cursor->format('Y'),
                'total' => $rows->count(),
                'overdue' => $rows->where('state', 'overdue')->count(),
                'due' => $rows->where('state', 'due')->count(),
                'done' => $rows->where('state', 'done')->count(),
                'is_current' => $key === $thisMonth,
            ];

            $cursor->addMonthNoOverflow();
        }

        return $months;
    }

    /**
     * KHOẢNG THỜI GIAN đang xem - "từ ngày - đến ngày" của kế hoạch.
     *
     * Mặc định lấy từ đầu tháng này đến hết 3 tháng nữa: kế hoạch là để nhìn về phía
     * trước, chỉ xem đúng tháng hiện tại thì không kịp chuẩn bị mẫu và chỉ tiêu kiểm.
     *
     * Ngày gửi lên không đọc được thì bỏ qua và lấy mặc định; chọn ngược ngày
     * (từ > đến) thì đảo lại cho đúng thứ tự thay vì báo lỗi.
     */
    private function period(Request $request): array
    {
        $parse = function ($value) {
            if (! is_string($value) || trim($value) === '') {
                return null;
            }

            try {
                return \Carbon\Carbon::parse($value)->startOfDay();
            } catch (\Throwable $e) {
                return null;
            }
        };

        $today = now()->startOfDay();

        $from = $parse($request->query('from_date')) ?: $today->copy()->startOfMonth();
        $to = $parse($request->query('to_date')) ?: $today->copy()->addMonthsNoOverflow(3)->endOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        // Khoảng quá dài thì dải thời gian và bảng đều không đọc nổi, cắt lại cho vừa
        $limit = $from->copy()->addMonthsNoOverflow(self::MAX_MONTHS)->endOfMonth();
        $clamped = $to->gt($limit);

        if ($clamped) {
            $to = $limit;
        }

        return [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'days' => (int) $from->diffInDays($to) + 1,
            // Khoảng có bao hôm nay: mốc "Quá hạn" và "Sắp đến hạn" mới thật sự là việc đang cần làm
            'has_today' => $from->lte($today) && $to->gte($today),
            // Người dùng chọn khoảng dài hơn mức xem được nên đã bị cắt bớt - phải nói ra
            'clamped' => $clamped,
            'max_months' => self::MAX_MONTHS,
        ];
    }

    /* ==========================================================
     |  TIỆN ÍCH
     ========================================================== */

    /**
     * Chuỗi JSON trong cột testings -> danh sách chỉ tiêu để hiển thị.
     *
     * Mỗi phần tử là ['name' => ..., 'issued' => bool]: kế hoạch cần thấy chỉ tiêu nào
     * đã cấp phát chuẩn rồi để biết mốc đó chuẩn bị tới đâu. Phiếu ghi trước khi có
     * phần cấp phát lưu mảng TÊN trần nên vẫn phải đọc được, coi như chưa cấp phát.
     */
    private function testingList($value): array
    {
        $items = json_decode((string) $value, true);

        if (! is_array($items)) {
            return [];
        }

        $list = [];

        foreach ($items as $item) {
            $name = is_array($item) ? trim((string) ($item['name'] ?? '')) : trim((string) $item);

            if ($name === '') {
                continue;
            }

            $list[] = [
                'name' => $name,
                'issued' => is_array($item) ? (bool) ($item['issued'] ?? false) : false,
            ];
        }

        return $list;
    }

    /** Mã nhóm chuẩn phải đánh giá hạn dùng, đúng phần mã nhóm nằm trong mã ống chuẩn. */
    private function groupCode(): string
    {
        return config('standard.groups.'.self::GROUP_KEY.'.code', self::GROUP_KEY);
    }

    /** Tên nhóm chuẩn phải đánh giá hạn dùng, để viết câu hướng dẫn cho người dùng. */
    private function groupName(): string
    {
        return config('standard.groups.'.self::GROUP_KEY.'.name', self::GROUP_KEY);
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }
}
