<?php

namespace App\Support;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Http\Controllers\Pages\MaterData\ConsumptionObjectController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DANH SÁCH VẬT TƯ ĐỀ NGHỊ THEO CHU KỲ
 *
 * Mỗi danh sách (periodic_request_list + periodic_request_item) là một đề nghị cấp phát
 * lập sẵn. Đến ngày next_run_date, hệ thống tạo MỘT đề nghị ở trạng thái Lưu tạm:
 *
 *   type = internal : material_request_lists (app_status = draft) + material_request_items
 *   type = external : material_transfer_requests (status = draft) + material_transfer_items
 *
 * Người đề nghị mở đề nghị đó ở màn Sử Dụng Vật Tư, điều chỉnh số lượng / thêm bớt dòng,
 * khai người ký rồi Trình ký (nội bộ) hoặc Gửi đề nghị (liên phòng ban) như phiếu lập tay.
 *
 * LỊCH: periodic + cycle_day (+ cycle_length khi periodic = days | year), bắt đầu từ start_date.
 *   week      : cycle_day 1-7 = Thứ 2 ... Chủ nhật
 *   month     : cycle_day 1-31 = ngày trong tháng
 *   bi_month  : kỳ 2 tháng theo lịch (T1-2, T3-4...), cycle_day 1-62
 *   quarter   : cycle_day 1-92 = ngày thứ mấy của quý
 *   half_year : nửa năm theo lịch (T1-6, T7-12), cycle_day 1-184
 *   year      : cycle_length năm tính từ năm của start_date, cycle_day 1-366 = ngày thứ mấy của năm đầu kỳ
 *   days      : chu kỳ cycle_length ngày tính liên tục từ start_date, cycle_day 1-cycle_length
 *
 * Danh sách NỘI BỘ không chọn lịch tay: chọn một tần suất của Đối tượng (cột frequency, mã
 * của CAL), lịch suy ra theo FREQUENCY_SCHEDULES.
 * Ngày chọn vượt độ dài kỳ (ngày 31 ở tháng 30 ngày, ngày 92 của quý 90 ngày) thì lấy
 * ngày cuối kỳ. Không bao giờ tạo đề nghị trước start_date.
 *
 * Có hai đường kích hoạt, cùng đi qua generateDue():
 * - Lệnh `php artisan material:periodic-requests` được Laravel Scheduler gọi hằng ngày.
 * - Mở trang Danh Mục Vật Tư / Sử Dụng Vật Tư: bù ngay các danh sách đã tới hạn của phòng
 *   đang chọn, vì máy chủ tại nhà máy không phải lúc nào cũng chạy scheduler.
 * Hai đường chạy chồng nhau cũng không sinh trùng: mỗi danh sách được khoá dòng
 * (lockForUpdate) và kiểm tra lại hạn trước khi tạo. Hệ thống đứng tắt nhiều chu kỳ thì
 * lần chạy lại chỉ tạo MỘT đề nghị rồi dời next_run_date sang lần kế tiếp sau hôm nay.
 *
 * Mọi lần Thêm / Sửa / Khoá / Mở khoá / Tạo đề nghị đều chụp lại danh sách vào
 * periodic_request_list_histories; generated_count đếm số lần đã tạo đề nghị.
 */
class MaterialPeriodicRequest
{
    public const LIST_TABLE = 'material_periodic_request_list';

    public const ITEM_TABLE = 'material_periodic_request_item';

    public const HISTORY_TABLE = 'material_periodic_request_list_histories';

    public const TYPE_INTERNAL = 'internal';

    public const TYPE_EXTERNAL = 'external';

    public const SYSTEM_ACTOR = 'Hệ thống';

    /** Chu kỳ tạo đề nghị - cột periodic_request_list.periodic. */
    public const CYCLES = [
        'week' => 'Hằng tuần',
        'month' => 'Hằng tháng',
        'bi_month' => '2 tháng/lần',
        'quarter' => 'Hằng quý',
        'half_year' => '6 tháng/lần',
        'year' => 'Theo số năm',
        'days' => 'Theo số ngày',
    ];

    /** Ngày lớn nhất chọn được trong chu kỳ lịch; chu kỳ days lấy theo cycle_length. */
    public const CYCLE_MAX_DAY = [
        'week' => 7,
        'month' => 31,
        'bi_month' => 62,
        'quarter' => 92,
        'half_year' => 184,
        'year' => 366,
    ];

    /** Chu kỳ phải khai độ dài (cycle_length) => độ dài tối đa: số ngày / số năm. */
    public const CYCLE_LENGTH_LIMITS = [
        'days' => 366,
        'year' => 10,
    ];

    /** Số ngày tối đa của một chu kỳ "Theo số ngày". */
    public const CYCLE_MAX_LENGTH = 366;

    /**
     * Tần suất của Đối tượng (mã CAL, xem ConsumptionObjectController::FREQUENCIES) => lịch
     * [periodic, cycle_length] của danh sách nội bộ.
     */
    public const FREQUENCY_SCHEDULES = [
        'Daily' => ['days', 1],
        'Alternate Day' => ['days', 2],
        'Weekly' => ['week', null],
        'Fortnightly' => ['days', 14],
        'Monthly' => ['month', null],
        'Bi Monthly' => ['bi_month', null],
        'Quaterly' => ['quarter', null],
        'Half Yearly' => ['half_year', null],
        'Yearly' => ['year', 1],
        'Two Yearly' => ['year', 2],
        'Three Yearly' => ['year', 3],
        'Five Yearly' => ['year', 5],
        'Seven Yearly' => ['year', 7],
    ];

    /** cycle_day của chu kỳ tuần: 1 = Thứ 2 ... 7 = Chủ nhật (ISO-8601). */
    public const WEEKDAYS = [
        1 => 'Thứ 2',
        2 => 'Thứ 3',
        3 => 'Thứ 4',
        4 => 'Thứ 5',
        5 => 'Thứ 6',
        6 => 'Thứ 7',
        7 => 'Chủ nhật',
    ];

    /** Chặn vòng lặp khi đếm số lần tạo đề nghị của một khoảng ngày - xem runsBetween(). */
    private const MAX_RUNS_PER_WINDOW = 400;

    /** Các hành động được tính là "thay đổi" cho badge trên nút lịch sử. */
    private const CHANGE_ACTIONS = ['Cập nhật', 'Khoá', 'Mở khoá'];

    public static function typeOf($value): string
    {
        return $value === self::TYPE_EXTERNAL ? self::TYPE_EXTERNAL : self::TYPE_INTERNAL;
    }

    /** Tên error bag của modal: periodicInternalCreateErrors, periodicExternalUpdateErrors... */
    public static function errorBag(string $type, string $mode): string
    {
        return 'periodic'.ucfirst(self::typeOf($type)).$mode.'Errors';
    }

    /* ==========================================================
     |  LỊCH CHU KỲ
     ========================================================== */

    public static function maxCycleDay(?string $periodic, $cycleLength = null): int
    {
        if ($periodic === 'days') {
            return max(1, min(self::CYCLE_MAX_LENGTH, (int) $cycleLength));
        }

        return self::CYCLE_MAX_DAY[$periodic] ?? 1;
    }

    public static function hasCycleLength(?string $periodic): bool
    {
        return isset(self::CYCLE_LENGTH_LIMITS[(string) $periodic]);
    }

    /** "Hằng tuần", "Hằng tháng", "Hằng quý", "Mỗi 10 ngày", "Hằng năm", "2 năm/lần". */
    public static function cycleLabel(?string $periodic, $cycleLength = null): string
    {
        return match ($periodic) {
            'days' => 'Mỗi '.(int) $cycleLength.' ngày',
            'year' => (int) $cycleLength <= 1 ? 'Hằng năm' : (int) $cycleLength.' năm/lần',
            default => self::CYCLES[$periodic] ?? '—',
        };
    }

    /** Nhãn chu kỳ hiển thị: danh sách nội bộ dùng đúng tên tần suất của đối tượng ("2 tuần/lần"...). */
    public static function scheduleLabel(?string $periodic, $cycleLength = null, ?string $frequency = null): string
    {
        return ConsumptionObjectController::FREQUENCIES[(string) $frequency] ?? self::cycleLabel($periodic, $cycleLength);
    }

    /** Tần suất + lịch suy ra, cho JS dựng ô chọn tần suất: [mã => {label, periodic, length}]. */
    public static function frequencyScheduleMap(): array
    {
        $map = [];

        foreach (self::FREQUENCY_SCHEDULES as $code => [$periodic, $length]) {
            $map[$code] = [
                'label' => ConsumptionObjectController::FREQUENCIES[$code] ?? $code,
                'periodic' => $periodic,
                'length' => $length,
            ];
        }

        return $map;
    }

    /** Mã tần suất của một đối tượng dùng được làm lịch; đối tượng chưa khai tần suất thì cho mọi tần suất. */
    public static function objectFrequencies($object): array
    {
        $codes = array_values(array_intersect(
            array_keys(self::FREQUENCY_SCHEDULES),
            array_map('trim', explode(',', (string) ($object->frequency ?? '')))
        ));

        return $codes ?: array_keys(self::FREQUENCY_SCHEDULES);
    }

    /** "Thứ 2", "Ngày 5 hằng tháng", "Ngày thứ 15 của quý", "Ngày thứ 3 / 10". */
    public static function cycleDayLabel(?string $periodic, $cycleDay, $cycleLength = null): string
    {
        $cycleDay = (int) $cycleDay;

        return match ($periodic) {
            'week' => self::WEEKDAYS[$cycleDay] ?? '—',
            'month' => 'Ngày '.$cycleDay.' hằng tháng',
            'bi_month' => 'Ngày thứ '.$cycleDay.' của kỳ 2 tháng',
            'quarter' => 'Ngày thứ '.$cycleDay.' của quý',
            'half_year' => 'Ngày thứ '.$cycleDay.' của nửa năm',
            'year' => 'Ngày thứ '.$cycleDay.' của năm',
            'days' => 'Ngày thứ '.$cycleDay.' / '.(int) $cycleLength,
            default => '—',
        };
    }

    /**
     * Ngày tạo đề nghị kế tiếp tính từ $from (mặc định hôm nay).
     *
     * $inclusive = false: ngày tìm được phải SAU $from (dùng sau khi vừa tạo đề nghị).
     * $inclusive = true : được tính cả chính $from (dùng lúc vừa khai / đổi lịch).
     * $from trước start_date thì bắt đầu tìm từ start_date, tính cả start_date.
     */
    public static function nextRunDate(string $periodic, int $cycleDay, ?int $cycleLength, string $startDate, $from = null, bool $inclusive = false): string
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $from = Carbon::parse($from ?? now())->startOfDay();

        if ($from->lt($start)) {
            $from = $start->copy();
            $inclusive = true;
        }

        $threshold = $inclusive ? $from->copy()->subDay() : $from->copy();
        $cycleDay = max(1, $cycleDay);
        $length = max(1, (int) $cycleLength);
        $periodStart = self::periodStart($periodic, $from, $start, $length);

        while (true) {
            $end = self::periodEnd($periodic, $periodStart, $length);
            // Chu kỳ nhiều năm: ngày thứ mấy tính trong năm đầu kỳ, vượt thì lấy 31/12 của năm đó
            $dayEnd = $periodic === 'year' ? $periodStart->copy()->endOfYear()->startOfDay() : $end;
            $candidate = $periodStart->copy()->addDays($cycleDay - 1);

            if ($candidate->gt($dayEnd)) {
                $candidate = $dayEnd->copy();
            }

            if ($candidate->gt($threshold)) {
                return $candidate->toDateString();
            }

            $periodStart = $end->copy()->addDay();
        }
    }

    /**
     * SỐ LẦN DANH SÁCH SẼ TẠO ĐỀ NGHỊ TRONG KHOẢNG [$from, $to]
     *
     * Dùng cho màn "Khả dụng tháng tới" (App\Support\MaterialAvailabilityForecast): nhân số
     * lần này với số lượng từng dòng của danh sách ra lượng vật tư chu kỳ sẽ lấy khỏi kho.
     *
     * Lịch cố định: chạy lại đúng nextRunDate() đến khi vượt $to nên số đếm khớp tuyệt đối
     * với những gì generateDue() sẽ tạo.
     *
     * Lịch "Theo ngày đến hạn lịch CAL" chỉ biết trước hạn của chu kỳ ĐANG chờ (next_run_date);
     * hạn các chu kỳ sau do CAL sinh dần nên phần đó là ƯỚC TÍNH: mỗi chu kỳ sau chu kỳ đang
     * chờ mà giao với khoảng đang xét được tính thêm một đề nghị.
     */
    public static function runsBetween($list, string $from, string $to): int
    {
        if (! $list || (int) ($list->status_id ?? 0) !== 1 || ! $list->periodic || ! $list->start_date) {
            return 0;
        }

        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();

        if ($from->gt($to)) {
            return 0;
        }

        if (self::dayModeOf($list->cycle_day_mode ?? null) === self::DAY_MODE_CAL_DUE) {
            return self::calRunsBetween($list, $from, $to);
        }

        $count = 0;
        $cursor = $from->copy();
        $inclusive = true;

        while ($count < self::MAX_RUNS_PER_WINDOW) {
            $run = Carbon::parse(self::nextRunDate(
                (string) $list->periodic,
                (int) $list->cycle_day,
                $list->cycle_length ? (int) $list->cycle_length : null,
                (string) $list->start_date,
                $cursor,
                $inclusive
            ));

            if ($run->gt($to)) {
                break;
            }

            $count++;
            $cursor = $run;
            $inclusive = false;
        }

        return $count;
    }

    /**
     * Ước tính số lần tạo đề nghị của danh sách "Theo ngày đến hạn lịch CAL" - xem runsBetween().
     * Chu kỳ đang chờ tính theo next_run_date đã đọc được từ CAL; các chu kỳ sau mỗi chu kỳ một lần.
     */
    private static function calRunsBetween($list, Carbon $from, Carbon $to): int
    {
        $periodic = (string) $list->periodic;
        $length = max(1, (int) $list->cycle_length);
        $start = Carbon::parse($list->start_date)->startOfDay();
        $next = $list->next_run_date ? Carbon::parse($list->next_run_date)->startOfDay() : null;

        $count = $next && $next->gte($from) && $next->lte($to) ? 1 : 0;

        // Mốc để bỏ qua chu kỳ đang chờ: hạn kế tiếp đã biết, chưa có thì lấy hôm nay
        $anchor = $next ?: now()->startOfDay();

        if ($anchor->lt($start)) {
            $anchor = $start->copy();
        }

        $cursor = self::periodEnd($periodic, self::periodStart($periodic, $anchor, $start, $length), $length)->addDay();
        $guard = 0;

        while ($cursor->lte($to) && $guard++ < self::MAX_RUNS_PER_WINDOW) {
            $end = self::periodEnd($periodic, $cursor, $length);

            if ($end->gte($from)) {
                $count++;
            }

            $cursor = $end->copy()->addDay();
        }

        return $count;
    }

    /**
     * Ngày tạo kế tiếp lúc vừa khai / đổi lịch: tính cả hôm nay, trừ khi hôm nay danh sách
     * đã tạo đề nghị rồi (tránh sửa lịch xong lại sinh thêm một phiếu trong cùng ngày).
     */
    public static function scheduleRunDate(array $schedule, $lastGeneratedAt = null): string
    {
        $generatedToday = $lastGeneratedAt && Carbon::parse($lastGeneratedAt)->isToday();

        return self::nextRunDate(
            (string) $schedule['periodic'],
            (int) $schedule['cycle_day'],
            $schedule['cycle_length'] ? (int) $schedule['cycle_length'] : null,
            (string) $schedule['start_date'],
            null,
            ! $generatedToday
        );
    }

    /** Ngày đầu của kỳ chứa $date ($date không trước $start). */
    private static function periodStart(string $periodic, Carbon $date, Carbon $start, int $length): Carbon
    {
        $date = $date->copy()->startOfDay();

        return match ($periodic) {
            'week' => $date->startOfWeek(Carbon::MONDAY),
            'bi_month' => $date->setDate($date->year, intdiv($date->month - 1, 2) * 2 + 1, 1),
            'quarter' => $date->startOfQuarter(),
            'half_year' => $date->setDate($date->year, $date->month <= 6 ? 1 : 7, 1),
            'year' => $date->setDate($start->year + intdiv($date->year - $start->year, $length) * $length, 1, 1),
            'days' => $start->copy()->addDays(intdiv((int) round($start->diffInDays($date, true)), $length) * $length),
            default => $date->startOfMonth(),
        };
    }

    /** Ngày cuối của kỳ bắt đầu từ $periodStart. */
    private static function periodEnd(string $periodic, Carbon $periodStart, int $length): Carbon
    {
        return match ($periodic) {
            'week' => $periodStart->copy()->addDays(6),
            'bi_month' => $periodStart->copy()->addMonthsNoOverflow(2)->subDay(),
            'quarter' => $periodStart->copy()->endOfQuarter()->startOfDay(),
            'half_year' => $periodStart->copy()->addMonthsNoOverflow(6)->subDay(),
            'year' => $periodStart->copy()->addYears($length)->subDay(),
            'days' => $periodStart->copy()->addDays($length - 1),
            default => $periodStart->copy()->endOfMonth()->startOfDay(),
        };
    }

    /* ==========================================================
     |  DỮ LIỆU HIỂN THỊ
     ========================================================== */

    /**
     * Danh sách theo chu kỳ của một phòng, tách theo loại, mỗi danh sách kèm ->items và
     * ->history_count (số lần Sửa / Khoá / Mở khoá).
     *
     * @return array{internal: \Illuminate\Support\Collection, external: \Illuminate\Support\Collection}
     */
    public static function listsOfDepartment(int $departmentId): array
    {
        $lists = DB::table(self::LIST_TABLE)
            ->leftJoin('deparments as pr_to_dept', self::LIST_TABLE.'.to_department_id', '=', 'pr_to_dept.id')
            ->leftJoin('consumption_objects as pr_object', self::LIST_TABLE.'.consumption_object_id', '=', 'pr_object.id')
            ->select(
                self::LIST_TABLE.'.*',
                'pr_to_dept.name as to_department_name',
                'pr_to_dept.shortName as to_department_short',
                'pr_object.type as object_type',
                'pr_object.code as object_code',
                'pr_object.name as object_name',
                'pr_object.location as object_location'
            )
            ->where(self::LIST_TABLE.'.department_id', $departmentId)
            ->orderBy(self::LIST_TABLE.'.status_id', 'desc')
            ->orderBy(self::LIST_TABLE.'.title', 'asc')
            ->get();

        $ids = $lists->pluck('id')->all();
        $items = self::activeItems($ids)->groupBy('periodic_request_list_id');

        $historyCounts = DB::table(self::HISTORY_TABLE)
            ->select('periodic_request_list_id', DB::raw('COUNT(*) as times'))
            ->whereIn('periodic_request_list_id', $ids)
            ->whereIn('action', self::CHANGE_ACTIONS)
            ->groupBy('periodic_request_list_id')
            ->pluck('times', 'periodic_request_list_id');

        $lists->each(function ($list) use ($items, $historyCounts) {
            $list->items = $items->get($list->id, collect())->values();
            $list->history_count = (int) ($historyCounts[$list->id] ?? 0);
        });

        return [
            self::TYPE_INTERNAL => $lists->where('type', self::TYPE_INTERNAL)->values(),
            self::TYPE_EXTERNAL => $lists->where('type', self::TYPE_EXTERNAL)->values(),
        ];
    }

    /**
     * Vật tư chọn được cho dòng của danh sách.
     *
     * - Nội bộ: vật tư phòng đã khai ở tab "Vật Tư Của Phòng" - khớp ô chọn của đề nghị
     *   cấp phát nội bộ, kho phòng mới cấp được.
     * - Liên phòng ban: mọi vật tư đã duyệt của danh mục công ty - phòng mình đang thiếu nên
     *   mới xin phòng khác, giống ô chọn của đề nghị liên phòng ban.
     * Đơn vị (unit_short_name) là đơn vị PHÒNG MÌNH đã khai, nếu có.
     */
    public static function categoryOptions(string $type, int $departmentId)
    {
        if (self::typeOf($type) === self::TYPE_INTERNAL) {
            return DepartmentMaterial::importCategoryOptions($departmentId);
        }

        return DB::table('material_categories')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'material_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_categories.id'))
            ->select(
                'material_categories.id',
                'material_categories.code',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.short_name as unit_short_name'
            )
            ->where('material_categories.status_id', 1)
            ->where('material_categories.app_status', 'approved')
            ->orderBy('material_names.name', 'asc')
            ->get();
    }

    /** Phòng cấp phát chọn được cho danh sách liên phòng ban: mọi phòng đang hoạt động, trừ phòng mình. */
    public static function departmentOptions(int $departmentId)
    {
        return DB::table('deparments')
            ->select('id', 'name', 'shortName')
            ->where('isActive', 1)
            ->where('id', '<>', $departmentId)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Đối tượng theo đúng các id truyền vào (giữ nguyên dù đã khoá) - dựng lại ô chọn "Đối tượng"
     * của danh sách đang có trên trang mà KHÔNG nhúng cả danh mục (2000+ dòng) vào HTML. Toàn bộ
     * danh mục tìm qua AJAX ở objectSearch() - xem PeriodicRequestController::objects().
     * Mỗi dòng kèm frequency_label (tần suất tiếng Việt) để hiện ngay khi chọn.
     */
    public static function objectsByIds(array $ids)
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if (! $ids) {
            return collect();
        }

        return DB::table('consumption_objects')
            ->select('id', 'type', 'code', 'name', 'location', 'frequency', 'status_id')
            ->whereIn('id', $ids)
            ->orderBy('type', 'asc')
            ->orderBy('code', 'asc')
            ->get()
            ->each(function ($object) {
                $object->frequency_label = ConsumptionObjectController::frequencyLabel($object->frequency);
            });
    }

    /**
     * Đối tượng khớp bộ lọc cho modal tìm kiếm AJAX "Dữ Liệu Gốc - Đối Tượng" - trả từng trang
     * $limit dòng (từ vị trí $offset) thay vì cả danh mục; modal cuộn tới cuối thì gọi trang kế.
     * $keepIds (đối tượng đang chọn trong ô, kể cả đã khoá) luôn đứng đầu trang đầu dù không khớp
     * bộ lọc, để không mất lựa chọn hiện tại khi gõ tìm tiếp.
     *
     * @return array{items: \Illuminate\Support\Collection, total: int, more: bool, next_offset: int}
     */
    public static function objectSearch(string $q, ?string $type, ?string $frequency, array $keepIds = [], int $limit = 50, int $offset = 0): array
    {
        $keepIds = array_values(array_unique(array_filter($keepIds)));
        $q = trim($q);
        $offset = max(0, $offset);

        $columns = ['id', 'type', 'code', 'name', 'location', 'frequency', 'status_id'];

        $kept = $keepIds
            ? DB::table('consumption_objects')->select($columns)->whereIn('id', $keepIds)->get()
            : collect();

        $query = DB::table('consumption_objects')
            ->where('status_id', 1)
            ->when($keepIds, fn ($query) => $query->whereNotIn('id', $keepIds))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($frequency, fn ($query) => $query->where('frequency', $frequency))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', '%'.$q.'%')
                    ->orWhere('name', 'like', '%'.$q.'%')
                    ->orWhere('location', 'like', '%'.$q.'%');
            }));

        $total = (clone $query)->count();

        $matches = $query
            ->select($columns)
            ->orderBy('type', 'asc')
            ->orderBy('code', 'asc')
            ->orderBy('frequency', 'asc')
            ->orderBy('id', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Dòng đang chọn chỉ chèn ở trang đầu; tổng số vẫn tính cả dòng đó
        $items = ($offset === 0 ? $kept : collect())->concat($matches)->each(function ($object) {
            $object->type_label = ConsumptionObjectController::typeLabels()[$object->type] ?? $object->type;
            $object->frequency_label = ConsumptionObjectController::frequencyLabel($object->frequency);
        })->values();

        $nextOffset = $offset + $matches->count();

        return [
            'items' => $items,
            'total' => $total + $kept->count(),
            'more' => $nextOffset < $total,
            'next_offset' => $nextOffset,
        ];
    }

    /** "PDP-108 - MÁY ĐÓNG HỘP UHLMANN", không có đối tượng thì null. */
    public static function objectLabel($objectId): ?string
    {
        $object = $objectId ? DB::table('consumption_objects')->where('id', $objectId)->first() : null;

        return $object ? $object->code.' - '.$object->name : null;
    }

    /* ==========================================================
     |  NGÀY TẠO THEO LỊCH CAL (Sch_DueDate)
     ========================================================== */

    /** Cách chọn ngày tạo đề nghị trong chu kỳ: ngày cố định (cycle_day) / theo hạn lịch CAL. */
    public const DAY_MODE_FIXED = 'fixed';

    public const DAY_MODE_CAL_DUE = 'cal_due';

    /** Trạng thái lịch đang chờ thực hiện bên CAL (Schedule_Master_x.Sch_Result_Status). */
    private const CAL_PENDING = 'Pending';

    /** Trang Danh Mục Vật Tư mở liên tục: cách tối thiểu giữa hai lần đọc CAL cho một danh sách (phút). */
    private const CAL_REFRESH_MINUTES = 15;

    /**
     * "Theo ngày đến hạn lịch CAL": tạo đề nghị trước hạn CAL mấy ngày để có thời gian chuẩn bị
     * vật tư, thay vì đúng ngày hạn. Không lùi trước Ngày bắt đầu chu kỳ đầu tiên.
     */
    public const CAL_LEAD_DAYS_DEFAULT = 3;

    public const CAL_LEAD_DAYS_MAX = 30;

    public static function dayModeOf($value): string
    {
        return $value === self::DAY_MODE_CAL_DUE ? self::DAY_MODE_CAL_DUE : self::DAY_MODE_FIXED;
    }

    /** "Theo ngày đến hạn lịch CAL" / "Ngày 5 hằng tháng" - cột Chu Kỳ và lịch sử. */
    public static function dayLabel($list): string
    {
        if (self::dayModeOf($list->cycle_day_mode ?? null) !== self::DAY_MODE_CAL_DUE) {
            return self::cycleDayLabel($list->periodic, $list->cycle_day, $list->cycle_length);
        }

        $lead = self::calLeadDays($list);

        return 'Theo ngày đến hạn lịch CAL'.($lead > 0 ? ' (tạo trước '.$lead.' ngày)' : '');
    }

    /** Số ngày tạo đề nghị trước hạn CAL của một danh sách - chưa khai thì lấy mặc định. */
    public static function calLeadDays($list): int
    {
        return $list && $list->cal_lead_days !== null ? (int) $list->cal_lead_days : self::CAL_LEAD_DAYS_DEFAULT;
    }

    /**
     * Lịch Pending bên CAL của một Đối tượng theo một tần suất, hạn sớm nhất trước.
     *
     * Liên kết qua Inst_ID: thiết bị lớn (consumption_objects.cal_inst_id) và các thiết bị con có
     * Parent_Equip_id = cal_inst_id trong Inst_Master_{cal_table_suffix}; lịch lấy ở
     * Schedule_Master_{cal_table_suffix} với Sch_Type = tần suất, Sch_Result_Status = Pending.
     *
     * Trả null khi đối tượng không đồng bộ từ CAL. Lỗi kết nối CAL ném exception cho nơi gọi xử lý.
     *
     * @return array<int, array{sch_id: int, inst_id: string, due_date: string}>|null
     */
    public static function calPendingSchedules($object, ?string $frequency): ?array
    {
        if (! $object
            || ($object->source ?? null) !== ConsumptionObjectController::SOURCE_CAL
            || ! $object->cal_connection
            || ! $object->cal_table_suffix
            || ! $object->cal_inst_id
            || ! $frequency) {
            return null;
        }

        $db = DB::connection($object->cal_connection);
        $suffix = (int) $object->cal_table_suffix;

        $instIds = $db->table('Inst_Master_'.$suffix)
            ->whereRaw('LTRIM(RTRIM(Parent_Equip_id)) = ?', [$object->cal_inst_id])
            ->pluck('Inst_id')
            ->map(fn ($id) => trim((string) $id))
            ->push((string) $object->cal_inst_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $db->table('Schedule_Master_'.$suffix)
            ->select('SCH_ID as sch_id', 'Inst_ID as inst_id', 'Sch_DueDate as due_date')
            ->whereIn(DB::raw('LTRIM(RTRIM(Inst_ID))'), $instIds)
            ->whereRaw('LTRIM(RTRIM(Sch_Type)) = ?', [$frequency])
            ->where('Sch_Result_Status', self::CAL_PENDING)
            ->whereNotNull('Sch_DueDate')
            ->orderBy('Sch_DueDate', 'asc')
            ->orderBy('SCH_ID', 'asc')
            ->get()
            ->map(fn ($row) => [
                'sch_id' => (int) $row->sch_id,
                'inst_id' => trim((string) $row->inst_id),
                'due_date' => Carbon::parse($row->due_date)->toDateString(),
            ])
            ->all();
    }

    /**
     * Chọn lịch CAL mà danh sách sẽ theo, trong các lịch Pending đã xếp hạn sớm nhất trước:
     * - Chỉ nhận lịch có hạn thuộc chu kỳ đang xét trở về sau - bỏ lịch Pending treo từ các
     *   chu kỳ trước mà CAL chưa đóng.
     * - Mỗi chu kỳ chỉ tự tạo một đề nghị: đã tạo theo lịch có hạn $lastDueDate thì chỉ nhận lịch
     *   thuộc các chu kỳ SAU chu kỳ đó (thiết bị lớn + con cùng chu kỳ không sinh thêm phiếu).
     * - Ngày tạo = hạn CAL trừ $leadDays ngày (chuẩn bị vật tư trước), nhưng không sớm hơn ngày
     *   bắt đầu chu kỳ đầu tiên. $leadDays không ảnh hưởng việc chọn lịch nào (vẫn theo hạn gốc),
     *   chỉ dời lùi ngày thật sự tạo đề nghị.
     *
     * @return array{sch_id: int, inst_id: string, due_date: string, run_date: string}|null
     */
    public static function calNextSchedule(array $schedules, string $periodic, ?int $cycleLength, string $startDate, ?string $lastDueDate = null, $today = null, int $leadDays = 0): ?array
    {
        if ($periodic === '' || ! $schedules) {
            return null;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $today = Carbon::parse($today ?? now())->startOfDay();
        $length = max(1, (int) $cycleLength);
        $windowStart = self::periodStart($periodic, $today->lt($start) ? $start->copy() : $today, $start, $length);

        if ($lastDueDate) {
            $last = Carbon::parse($lastDueDate)->startOfDay();
            $afterLast = self::periodEnd($periodic, self::periodStart($periodic, $last->lt($start) ? $start->copy() : $last, $start, $length), $length)
                ->addDay();

            if ($afterLast->gt($windowStart)) {
                $windowStart = $afterLast;
            }
        }

        foreach ($schedules as $schedule) {
            $due = Carbon::parse($schedule['due_date'])->startOfDay();

            if ($due->lt($windowStart)) {
                continue;
            }

            $run = $due->copy()->subDays(max(0, $leadDays));

            return $schedule + ['run_date' => ($run->lt($start) ? $start : $run)->toDateString()];
        }

        return null;
    }

    /**
     * Xem trước cho modal thêm / sửa: các lịch Pending CAL của đối tượng + lịch danh sách sẽ theo.
     * available = đối tượng thuộc CAL và có ít nhất một lịch Pending của tần suất đang chọn - chỉ khi
     * đó mới được chọn "Theo ngày đến hạn lịch CAL".
     *
     * @return array{available: bool, message: ?string, schedules: array, next: ?array, lead_days: int}
     */
    public static function calSchedulePreview($object, ?string $frequency, ?string $startDate, $list = null, ?int $leadDays = null): array
    {
        $leadDays = $leadDays !== null ? max(0, min(self::CAL_LEAD_DAYS_MAX, $leadDays)) : self::CAL_LEAD_DAYS_DEFAULT;
        $fail = fn (string $message) => ['available' => false, 'message' => $message, 'schedules' => [], 'next' => null, 'lead_days' => $leadDays];

        if (! $object) {
            return $fail('Chọn đối tượng thuộc phần mềm CAL để dùng hạn lịch CAL.');
        }

        if (($object->source ?? null) !== ConsumptionObjectController::SOURCE_CAL || ! $object->cal_inst_id) {
            return $fail('Đối tượng không đồng bộ từ phần mềm CAL nên không có hạn lịch CAL.');
        }

        if (! $frequency || ! isset(self::FREQUENCY_SCHEDULES[$frequency])) {
            return $fail('Chọn tần suất đề nghị trước.');
        }

        try {
            $schedules = self::calPendingSchedules($object, $frequency) ?? [];
        } catch (\Throwable $e) {
            return $fail('Không đọc được lịch từ phần mềm CAL, vui lòng thử lại sau.');
        }

        if (! $schedules) {
            return $fail('Đối tượng chưa có lịch Pending tần suất này trên CAL.');
        }

        [$periodic, $length] = self::FREQUENCY_SCHEDULES[$frequency];

        // Sửa danh sách giữ nguyên đối tượng + tần suất: tính tiếp từ mốc chu kỳ đã tự tạo đề nghị
        $lastDueDate = $list
            && (int) $list->consumption_object_id === (int) $object->id
            && (string) $list->frequency === (string) $frequency
                ? $list->last_cal_due_date
                : null;

        $next = self::calNextSchedule($schedules, $periodic, $length, $startDate ?: now()->toDateString(), $lastDueDate, null, $leadDays);

        return [
            'available' => true,
            'message' => $next ? null : 'Chưa có lịch Pending thuộc chu kỳ đang xét - hệ thống chờ CAL sinh lịch mới.',
            'schedules' => $schedules,
            'next' => $next,
            'lead_days' => $leadDays,
        ];
    }

    /**
     * Đọc lại lịch CAL cho một danh sách "theo hạn lịch CAL", ghi cal_sch_id / cal_due_date /
     * next_run_date. Không có lịch hợp lệ thì next_run_date = null (chờ CAL sinh lịch Pending mới).
     * Lỗi kết nối CAL: giữ nguyên lịch đang có, trả false.
     */
    public static function refreshCalSchedule($list, $today = null): bool
    {
        if (! $list || self::dayModeOf($list->cycle_day_mode ?? null) !== self::DAY_MODE_CAL_DUE) {
            return false;
        }

        $object = $list->consumption_object_id
            ? DB::table('consumption_objects')->where('id', $list->consumption_object_id)->first()
            : null;

        try {
            $schedules = self::calPendingSchedules($object, $list->frequency) ?? [];
        } catch (\Throwable $e) {
            DB::table(self::LIST_TABLE)->where('id', $list->id)->update(['cal_checked_at' => now()]);

            return false;
        }

        $next = self::calNextSchedule(
            $schedules,
            (string) $list->periodic,
            $list->cycle_length ? (int) $list->cycle_length : null,
            (string) $list->start_date,
            $list->last_cal_due_date,
            $today,
            self::calLeadDays($list)
        );

        DB::table(self::LIST_TABLE)->where('id', $list->id)->update([
            'cal_sch_id' => $next['sch_id'] ?? null,
            'cal_due_date' => $next['due_date'] ?? null,
            'next_run_date' => $next['run_date'] ?? null,
            'cal_checked_at' => now(),
        ]);

        return true;
    }

    /**
     * Đọc lại lịch CAL cho các danh sách "theo hạn lịch CAL" đang dùng. Mở trang thì giãn cách
     * CAL_REFRESH_MINUTES phút mỗi danh sách; $force (lệnh chạy hằng ngày) đọc lại tất cả.
     */
    public static function refreshCalSchedules(?int $departmentId = null, bool $force = false): void
    {
        DB::table(self::LIST_TABLE)
            ->where('status_id', 1)
            ->where('cycle_day_mode', self::DAY_MODE_CAL_DUE)
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->when(! $force, fn ($query) => $query->where(function ($sub) {
                $sub->whereNull('cal_checked_at')
                    ->orWhere('cal_checked_at', '<', now()->subMinutes(self::CAL_REFRESH_MINUTES));
            }))
            ->orderBy('id')
            ->get()
            ->each(fn ($list) => self::refreshCalSchedule($list));
    }

    /* ==========================================================
     |  LỊCH SỬ THAY ĐỔI
     ========================================================== */

    /** Toàn bộ thông tin của danh sách dạng {nhãn: giá trị}, dùng cho lịch sử và Audit Trail. */
    public static function snapshot(int $id): array
    {
        $list = DB::table(self::LIST_TABLE)
            ->leftJoin('deparments as pr_to_dept', self::LIST_TABLE.'.to_department_id', '=', 'pr_to_dept.id')
            ->select(self::LIST_TABLE.'.*', 'pr_to_dept.name as to_department_name')
            ->where(self::LIST_TABLE.'.id', $id)
            ->first();

        if (! $list) {
            return [];
        }

        $isExternal = $list->type === self::TYPE_EXTERNAL;
        $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');

        $items = self::activeItems([$id])->map(fn ($item) => ($item->category_code ?: '—').' '.($item->material_name ?: '')
            .' x '.$number($item->requested_amount).($item->requested_unit ? ' '.$item->requested_unit : '')
            .($item->purpose ? ' ('.$item->purpose.')' : ''));

        return array_filter([
            'Tiêu đề' => $list->title,
            'Loại đề nghị' => $isExternal ? 'Liên phòng ban' : 'Nội bộ',
            'Đối tượng' => $isExternal ? null : (self::objectLabel($list->consumption_object_id) ?? '—'),
            'Phòng cấp phát' => $isExternal ? ($list->to_department_name ?: '—') : null,
            'Chu kỳ' => self::scheduleLabel($list->periodic, $list->cycle_length, $list->frequency),
            'Ngày tạo trong chu kỳ' => self::dayLabel($list),
            'Lịch CAL đang theo' => self::dayModeOf($list->cycle_day_mode ?? null) === self::DAY_MODE_CAL_DUE
                ? ($list->cal_due_date
                    ? 'Hạn '.Carbon::parse($list->cal_due_date)->format('d/m/Y').' (SCH_ID '.$list->cal_sch_id.')'
                    : 'Chưa có lịch Pending thuộc chu kỳ đang xét')
                : null,
            'Ngày bắt đầu chu kỳ đầu tiên' => $list->start_date ? Carbon::parse($list->start_date)->format('d/m/Y') : '—',
            'Tạo đề nghị kế tiếp' => $list->next_run_date ? Carbon::parse($list->next_run_date)->format('d/m/Y') : '—',
            'Số lần đã tạo đề nghị' => (string) (int) $list->generated_count,
            'Trạng thái' => (int) $list->status_id === 1 ? 'Đang dùng' : 'Đã khoá',
            'Vật tư' => $items->implode('; ') ?: '—',
        ], fn ($value) => $value !== null);
    }

    /** "Nhãn: cũ -> mới | ..." giữa hai ảnh chụp; chuỗi rỗng = không có gì thay đổi. */
    public static function diffNote(array $before, array $after): string
    {
        $parts = [];

        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $label) {
            $old = $before[$label] ?? '—';
            $new = $after[$label] ?? '—';

            if ((string) $old !== (string) $new) {
                $parts[] = $label.': '.$old.' -> '.$new;
            }
        }

        return implode(' | ', $parts);
    }

    /** Chụp lại danh sách ngay sau lần thay đổi vào bảng lịch sử. */
    public static function writeHistory(int $listId, string $action, ?string $note = null, ?string $reason = null, ?string $actor = null): void
    {
        DB::table(self::HISTORY_TABLE)->insert([
            'periodic_request_list_id' => $listId,
            'action' => $action,
            'change_note' => $note,
            'change_reason' => $reason === '' ? null : $reason,
            'snapshot' => json_encode(self::snapshot($listId), JSON_UNESCAPED_UNICODE),
            'created_by' => $actor ?? Signer::actor(),
            'created_at' => now(),
        ]);
    }

    /** Dòng lịch sử theo đúng định dạng modal lịch sử dùng chung của nhóm Danh Mục. */
    public static function historyRows(int $listId): array
    {
        return DB::table(self::HISTORY_TABLE)
            ->where('periodic_request_list_id', $listId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($row) => [
                'action' => $row->action,
                'change_note' => $row->change_note,
                'change_reason' => $row->change_reason,
                'created_by' => $row->created_by ?: 'NA',
                'created_at' => $row->created_at ? Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                'snapshot' => (object) (json_decode((string) $row->snapshot, true) ?: []),
            ])
            ->values()
            ->all();
    }

    /** Chuỗi nhận diện một danh sách cho Audit Trail. */
    public static function describe(int $id): string
    {
        $snapshot = self::snapshot($id);

        if (! $snapshot) {
            return 'NA';
        }

        return implode(' | ', array_map(fn ($label, $value) => $label.': '.$value, array_keys($snapshot), $snapshot));
    }

    /* ==========================================================
     |  TẠO ĐỀ NGHỊ
     ========================================================== */

    /**
     * Tạo đề nghị cho mọi danh sách đã tới hạn. $departmentId = null: toàn hệ thống.
     *
     * $refreshCal = true : đọc lại lịch Pending từ phần mềm CAL trước khi xét hạn - dùng khi
     *   người dùng mở trang Sử Dụng Vật Tư hoặc lệnh scheduler chạy hằng ngày.
     * $refreshCal = false: bỏ qua bước đọc CAL để trang tải nhanh - dùng ở trang Danh Mục Vật
     *   Tư (chỉ cần tạo đề nghị cho các danh sách đã có next_run_date sẵn).
     *
     * @return array<int, array{id: int, code: string, type: string, list_id: int}>
     */
    public static function generateDue(?int $departmentId = null, bool $refreshCal = true): array
    {
        // Danh sách theo hạn lịch CAL: đọc lại lịch Pending trước khi xét hạn (lệnh hằng ngày đọc lại tất cả)
        if ($refreshCal) {
            self::refreshCalSchedules($departmentId, $departmentId === null);
        }

        $today = now()->toDateString();

        $ids = DB::table(self::LIST_TABLE)
            ->where('status_id', 1)
            ->whereNotNull('next_run_date')
            ->where('next_run_date', '<=', $today)
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('id')
            ->pluck('id');

        $created = [];

        foreach ($ids as $id) {
            try {
                [$list, $result] = DB::transaction(function () use ($id, $today) {
                    $list = DB::table(self::LIST_TABLE)->where('id', $id)->lockForUpdate()->first();

                    // Kiểm tra lại trong khoá: đường kia có thể vừa tạo xong và dời ngày
                    if (! $list || (int) $list->status_id !== 1 || ! $list->next_run_date || $list->next_run_date > $today) {
                        return [null, null];
                    }

                    $result = self::createRequest($list, self::SYSTEM_ACTOR, $list->created_user_id ? (int) $list->created_user_id : null);
                    $isCal = self::dayModeOf($list->cycle_day_mode ?? null) === self::DAY_MODE_CAL_DUE;

                    DB::table(self::LIST_TABLE)->where('id', $list->id)->update(($isCal
                        // Theo hạn lịch CAL: đánh dấu lịch đã dùng, chờ lịch Pending của chu kỳ sau ở lần đọc CAL kế tiếp
                        ? [
                            'last_cal_sch_id' => $list->cal_sch_id,
                            'last_cal_due_date' => $list->cal_due_date,
                            'cal_sch_id' => null,
                            'cal_due_date' => null,
                            'next_run_date' => null,
                            'cal_checked_at' => null,
                        ]
                        : [
                            'next_run_date' => self::nextRunDate(
                                (string) $list->periodic,
                                (int) $list->cycle_day,
                                $list->cycle_length ? (int) $list->cycle_length : null,
                                (string) $list->start_date,
                                $today
                            ),
                        ]) + self::generatedMark($result));

                    if ($result) {
                        self::writeHistory(
                            (int) $list->id,
                            'Tạo đề nghị',
                            ($isCal && $list->cal_due_date
                                ? 'Tự động theo lịch CAL (hạn '.Carbon::parse($list->cal_due_date)->format('d/m/Y').', SCH_ID '.$list->cal_sch_id.')'
                                : 'Tự động theo chu kỳ')
                                .': tạo đề nghị '.$result['code'].' (Lưu tạm).',
                            null,
                            self::SYSTEM_ACTOR
                        );
                    }

                    return [$list, $result];
                });
            } catch (\Throwable $e) {
                // Một danh sách lỗi (ví dụ phòng cấp phát đã bị xoá) không được chặn các danh sách còn lại
                report($e);

                continue;
            }

            if (! $result) {
                continue;
            }

            AuditTrialController::log(
                'Tự tạo đề nghị theo chu kỳ',
                self::LIST_TABLE,
                $list->id,
                'NA',
                'Tạo đề nghị '.$result['code'].' (Lưu tạm) từ danh sách "'.$list->title.'"',
                self::SYSTEM_ACTOR
            );

            self::notifyOwner($list, $result);

            $created[] = $result + ['list_id' => (int) $list->id];
        }

        return $created;
    }

    /**
     * Tạo ngay một đề nghị từ danh sách (nút "Tạo đề nghị ngay"). Lịch tự động giữ nguyên.
     * Trả null khi danh sách bị khoá hoặc không còn dòng vật tư nào.
     */
    public static function generateNow(int $listId, string $actor, ?int $userId): ?array
    {
        return DB::transaction(function () use ($listId, $actor, $userId) {
            $list = DB::table(self::LIST_TABLE)->where('id', $listId)->lockForUpdate()->first();

            if (! $list || (int) $list->status_id !== 1) {
                return null;
            }

            $result = self::createRequest($list, $actor, $userId);

            if (! $result) {
                return null;
            }

            DB::table(self::LIST_TABLE)->where('id', $list->id)->update(self::generatedMark($result));

            self::writeHistory((int) $list->id, 'Tạo đề nghị', 'Tạo ngay: đề nghị '.$result['code'].' (Lưu tạm).', null, $actor);

            return $result;
        });
    }

    /** Tạo đề nghị Lưu tạm đúng loại của danh sách. Không có dòng vật tư thì không tạo. */
    private static function createRequest($list, string $actor, ?int $userId): ?array
    {
        $items = self::activeItems([(int) $list->id]);

        if ($items->isEmpty()) {
            return null;
        }

        return $list->type === self::TYPE_EXTERNAL
            ? self::createExternal($list, $items, $actor)
            : self::createInternal($list, $items, $actor, $userId);
    }

    /** Đề nghị cấp phát NỘI BỘ - cùng cách sinh mã với MaterialExportController::requestStore(). */
    private static function createInternal($list, $items, string $actor, ?int $userId): array
    {
        $now = now();

        // Đối tượng của danh sách: ghi vào ghi chú phiếu
        $objectLabel = self::objectLabel($list->consumption_object_id ?? null);
        $prefix = str_pad((string) $list->department_id, 2, '0', STR_PAD_LEFT).$now->format('dmy').'_';

        $latest = DB::table('material_request_lists')->where('code', 'LIKE', $prefix.'%')->orderBy('id', 'desc')->value('code');
        $seq = $latest ? (int) Str::afterLast($latest, '_') + 1 : 1;
        $code = $prefix.str_pad((string) $seq, 2, '0', STR_PAD_LEFT);

        $requestId = DB::table('material_request_lists')->insertGetId([
            'code' => $code,
            'department_id' => $list->department_id,
            'group_id' => null,
            'name' => Str::limit($list->title.' - kỳ '.$now->format('d/m/Y'), 255, ''),
            'type' => 'periodic',
            'needed_date' => null, // Chưa biết ngày mong muốn cụ thể - người lập tự điền khi mở phiếu
            'note' => Str::limit('Tạo tự động từ danh sách đề nghị theo chu kỳ "'.$list->title.'"'
                .($objectLabel ? ' - Đối tượng: '.$objectLabel : '')
                .(self::dayModeOf($list->cycle_day_mode ?? null) === self::DAY_MODE_CAL_DUE && $list->cal_due_date
                    ? ' - Lịch CAL hạn '.Carbon::parse($list->cal_due_date)->format('d/m/Y').' (SCH_ID '.$list->cal_sch_id.')'
                    : '').'.', 500, ''),
            'app_status' => 'draft',
            'sign_step_count' => 0,
            'current_step' => null,
            'issue_status' => null,
            'status_id' => 1,
            'created_by' => $actor,
            'created_user_id' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_request_items')->insert($items->map(fn ($item) => [
            'request_list_id' => $requestId,
            'category_id' => $item->category_id,
            'material_name' => null,
            'technical_specification' => $item->technical_specification,
            'requested_amount' => $item->requested_amount,
            'requested_unit' => $item->requested_unit,
            'purpose' => $item->purpose,
            'status' => 'pending',
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        return ['id' => (int) $requestId, 'code' => $code, 'type' => self::TYPE_INTERNAL];
    }

    /** Đề nghị LIÊN PHÒNG BAN - cùng cách sinh mã với MaterialExportController::nextMaterialTransferCode(). */
    private static function createExternal($list, $items, string $actor): array
    {
        $now = now();
        $prefix = 'LPB-'.$list->department_id.'-'.$list->to_department_id.'-'.$now->format('dmy').'-';

        $latest = DB::table('material_transfer_requests')->where('code', 'LIKE', $prefix.'%')->orderBy('id', 'desc')->value('code');
        $seq = $latest ? (int) Str::afterLast($latest, '-') + 1 : 1;
        $code = $prefix.str_pad((string) $seq, 2, '0', STR_PAD_LEFT);

        $requestId = DB::table('material_transfer_requests')->insertGetId([
            'code' => $code,
            'department_id' => $list->department_id,
            'to_department_id' => $list->to_department_id,
            'title' => Str::limit($list->title.' - kỳ '.$now->format('d/m/Y'), 255, ''),
            'status' => 'draft',
            'note' => 'Tạo tự động từ danh sách đề nghị theo chu kỳ "'.$list->title.'".',
            'created_by' => $actor,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Dòng liên phòng ban không có cột mục đích riêng: ghi vào ghi chú của dòng
        DB::table('material_transfer_items')->insert($items->map(fn ($item) => [
            'transfer_request_id' => $requestId,
            'category_id' => $item->category_id,
            'requested_amount' => $item->requested_amount,
            'requested_unit' => $item->requested_unit,
            'note' => $item->purpose ?: null,
            'status' => 'draft',
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        return ['id' => (int) $requestId, 'code' => $code, 'type' => self::TYPE_EXTERNAL];
    }

    private static function generatedMark(?array $result): array
    {
        return $result
            ? [
                'last_generated_at' => now(),
                'last_request_code' => $result['code'],
                'generated_count' => DB::raw('generated_count + 1'),
            ]
            : [];
    }

    /** Các dòng còn hiệu lực của danh sách, kèm mã / tên / thông tin kỹ thuật của vật tư. */
    private static function activeItems(array $listIds)
    {
        return DB::table(self::ITEM_TABLE)
            ->leftJoin('material_categories', self::ITEM_TABLE.'.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->select(
                self::ITEM_TABLE.'.*',
                'material_categories.code as category_code',
                'material_categories.technical_specification',
                'material_names.name as material_name'
            )
            ->whereIn(self::ITEM_TABLE.'.periodic_request_list_id', $listIds)
            ->where(self::ITEM_TABLE.'.active', 1)
            ->orderBy(self::ITEM_TABLE.'.id', 'asc')
            ->get();
    }

    /**
     * Báo người lập danh sách vào chuông thông báo. Ghi thẳng vào bảng vì
     * NotificationController::sendNotification() đọc session - lệnh chạy từ scheduler không có.
     */
    private static function notifyOwner($list, array $result): void
    {
        $userId = (int) ($list->created_user_id ?? 0);

        if (! $userId) {
            return;
        }

        $isExternal = $result['type'] === self::TYPE_EXTERNAL;

        try {
            $notificationId = DB::table('notifications')->insertGetId([
                'sender_id' => null,
                'activity_type' => 'Đề nghị theo chu kỳ',
                'message' => 'Đến chu kỳ: đã tạo đề nghị '.($isExternal ? 'liên phòng ban ' : 'cấp phát vật tư ')
                    .$result['code'].' (Lưu tạm) từ danh sách "'.$list->title.'". Vui lòng kiểm tra, điều chỉnh rồi '
                    .($isExternal ? 'gửi đề nghị.' : 'trình ký.'),
                'reference_id' => $result['id'],
                'url' => route('pages.export.materialExport.list', ['tab' => $isExternal ? 'transfer' : 'periodic'], false),
                'created_at' => now(),
            ]);

            DB::table('notification_recipients')->insert([
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'is_read' => 0,
            ]);
        } catch (\Throwable $e) {
            // Thông báo lỗi không được làm mất đề nghị đã tạo
            report($e);
        }
    }
}
