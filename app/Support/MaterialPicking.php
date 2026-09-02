<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * XUẤT KHO THÔNG MINH - CHỌN LÔ NÊN XUẤT
 *
 * Một chỗ duy nhất trả lời câu "nên lấy lô nào trước". Màn Sử Dụng Vật Tư (ô chọn mã
 * xuất nhập khi cấp phát) và màn Đợt Lấy Hàng đọc chung lớp này để không lệch nhau.
 *
 * QUY TẮC SẮP LÔ - chỉ một, không có ô cho người dùng chọn FIFO hay FEFO:
 *
 *      1. Lô CÓ hạn dùng đứng trước lô KHÔNG có hạn dùng.
 *      2. Trong nhóm có hạn: hạn gần nhất trước  (FEFO - hết hạn trước, xuất trước).
 *      3. Còn lại: ngày nhập sớm nhất trước      (FIFO - nhập trước, xuất trước).
 *
 * Nhờ bước 1, vật tư không khai hạn dùng (găng tay, giấy lọc, bao bì) TỰ chạy FIFO mà
 * không phải khai gì thêm. Mệnh đề `expired_date IS NULL` phải đặt TRƯỚC, nếu không
 * MySQL xếp NULL lên đầu và FEFO sẽ ưu tiên đúng những lô cần ưu tiên ít nhất.
 *
 * TỒN KHẢ DỤNG khác tồn sổ sách ở phần ĐANG GIỮ - hàng đã hứa cho một đợt lấy hàng còn
 * treo, chưa rời kho nên vẫn nằm trong material_imports.amount, nhưng không được hứa
 * thêm lần nữa:
 *
 *      tồn      = amount + Σ material_balancings - Σ material_exports
 *      đang giữ = Σ material_pick_lines.suggested_amount   (đợt + dòng còn treo)
 *      khả dụng = tồn - đang giữ
 *
 * Query Builder thuần, không Eloquent - song song với App\Support\DepartmentMaterial.
 */
class MaterialPicking
{
    public const WAVE_TABLE = 'material_pick_waves';

    public const LINE_TABLE = 'material_pick_lines';

    /** Sai số cho phép khi so sánh số thập phân - giống MaterialExportController. */
    public const EPSILON = 0.00005;

    /** Trạng thái đợt còn giữ chỗ tồn. Đã xuất thì tồn đã trừ thật, huỷ thì nhả. */
    public const HOLDING_WAVE_STATUSES = ['new', 'picking', 'picked', 'packed'];

    /** Trạng thái dòng còn giữ chỗ tồn. Dòng bị bỏ (canceled) nhả ngay. */
    public const HOLDING_LINE_STATUSES = ['pending', 'picked', 'short'];

    /*
     | Số lượng một dòng đang giữ: chưa ra kệ thì giữ đúng phần engine hứa, lấy rồi thì
     | giữ đúng phần thực cầm về - dòng lấy thiếu tự nhả lại phần không lấy được.
     */
    private const HELD_AMOUNT = 'COALESCE('.self::LINE_TABLE.'.picked_amount, '.self::LINE_TABLE.'.suggested_amount)';

    /**
     * Các lô của một danh mục vật tư, ĐÃ SẮP theo thứ tự nên xuất, kèm tồn khả dụng.
     *
     * Lô hết hạn vẫn trả về (để màn hình còn hiện ra và chặn), nhưng gắn cờ `expired` và
     * `suggestable = false` - engine không bao giờ đề xuất lô hết hạn.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function lots(int $departmentId, ?int $categoryId = null)
    {
        $held = self::heldByImport($departmentId);
        $exported = self::sumByImport('material_exports', 'amount', $departmentId);
        $balanced = self::sumByImport('material_balancings', 'balancing_amount', $departmentId);

        $today = now()->startOfDay();
        $warningDays = (int) config('material.near_expiry_days.warning', 60);
        $criticalDays = (int) config('material.near_expiry_days.critical', 30);

        return DB::table('material_imports')
            ->leftJoin('material_categories', 'material_imports.category_id', '=', 'material_categories.id')
            ->leftJoin('material_names', 'material_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('locations', 'material_imports.location_id', '=', 'locations.id')
            ->tap(fn ($query) => DepartmentMaterial::joinUnit($query, $departmentId, 'material_imports.category_id'))
            ->select(
                'material_imports.id',
                'material_imports.code',
                'material_imports.category_id',
                'material_imports.amount',
                'material_imports.imported_date',
                'material_imports.expired_date',
                'material_imports.location_id',
                'material_categories.technical_specification',
                'material_names.name as material_name',
                'units.short_name as unit_short_name',
                'locations.code as location_code',
                'locations.warehouse_id',
                'locations.room_id',
                'locations.shelf_id'
            )
            ->where('material_imports.department_id', $departmentId)
            ->where('material_imports.status_id', 1)
            ->when($categoryId, fn ($query) => $query->where('material_imports.category_id', $categoryId))
            // 1. Lô có hạn dùng trước lô không hạn - phải đứng trước hai mệnh đề dưới
            ->orderByRaw('material_imports.expired_date IS NULL')
            ->orderBy('material_imports.expired_date', 'asc')   // 2. FEFO
            ->orderBy('material_imports.imported_date', 'asc')  // 3. FIFO
            ->orderBy('material_imports.id', 'asc')
            ->get()
            ->map(function ($lot) use ($held, $exported, $balanced, $today, $warningDays, $criticalDays) {
                $lot->exported = (float) ($exported[$lot->id] ?? 0);
                $lot->balanced = (float) ($balanced[$lot->id] ?? 0);
                $lot->held = (float) ($held[$lot->id] ?? 0);

                // Tồn sổ sách - vẫn là con số màn Tồn Kho hiển thị
                $lot->remaining = max((float) $lot->amount + $lot->balanced - $lot->exported, 0);
                // Tồn còn hứa được - trừ thêm phần đang giữ cho đợt khác
                $lot->available = max($lot->remaining - $lot->held, 0);

                $lot->expired = false;
                $lot->days_to_expiry = null;
                $lot->expiry_level = null;                      // null | 'warning' | 'critical' | 'expired'

                if ($lot->expired_date) {
                    $expiry = \Carbon\Carbon::parse($lot->expired_date)->startOfDay();
                    $lot->days_to_expiry = (int) $today->diffInDays($expiry, false);
                    $lot->expired = $lot->days_to_expiry < 0;

                    $lot->expiry_level = match (true) {
                        $lot->expired => 'expired',
                        $lot->days_to_expiry <= $criticalDays => 'critical',
                        $lot->days_to_expiry <= $warningDays => 'warning',
                        default => null,
                    };
                }

                // Engine chỉ đề xuất lô còn hạn và còn hứa được
                $lot->suggestable = ! $lot->expired && $lot->available > self::EPSILON;

                return $lot;
            });
    }

    /**
     * Kế hoạch lấy hàng cho MỘT nhu cầu: cần $needed đơn vị của danh mục $categoryId.
     *
     * Duyệt lô theo đúng thứ tự nên xuất, mỗi lô lấy tối đa phần khả dụng cho tới khi đủ.
     * Trả về:
     *      lines    - mảng dòng lấy hàng, mỗi dòng một lô
     *      shortage - phần kho KHÔNG đủ hàng (0 là đủ)
     *
     * $splitLots = false: chỉ nhận lô nào MỘT MÌNH đủ (chế độ một dòng một lô của màn
     * cấp phát lẻ hiện có). Không lô nào đủ thì trả lô tốt nhất còn hàng, phần thiếu ghi
     * vào shortage để màn hình cảnh báo.
     */
    public static function plan(int $departmentId, int $categoryId, float $needed, bool $splitLots = true): array
    {
        return self::planFrom(self::lots($departmentId, $categoryId), $needed, $splitLots);
    }

    /**
     * Đúng phép chia lô của plan() nhưng ăn thẳng bộ lô ĐÃ NẠP SẴN.
     *
     * Màn cấp phát dựng kế hoạch cho hàng chục dòng đề nghị một lúc; gọi plan() từng dòng
     * là bấy nhiêu lần đọc lại toàn bộ tồn kho. Chỗ đó nạp lots() một lần, lọc theo danh
     * mục rồi đưa vào đây - thứ tự nên xuất của lots() được giữ nguyên nên kết quả không
     * lệch so với plan().
     *
     * @param  iterable  $lots  Lô của MỘT danh mục, giữ nguyên thứ tự lots() trả về
     */
    public static function planFrom($lots, float $needed, bool $splitLots = true): array
    {
        $lots = collect($lots)->filter(fn ($lot) => $lot->suggestable)->values();

        if (! $splitLots) {
            $whole = $lots->first(fn ($lot) => $lot->available + self::EPSILON >= $needed);
            $lot = $whole ?: $lots->first();

            if (! $lot) {
                return ['lines' => [], 'shortage' => $needed];
            }

            $take = min($needed, $lot->available);

            return [
                'lines' => [self::line($lot, $take)],
                'shortage' => max($needed - $take, 0),
            ];
        }

        $lines = [];
        $need = $needed;

        foreach ($lots as $lot) {
            if ($need <= self::EPSILON) {
                break;
            }

            $take = min($need, $lot->available);
            $lines[] = self::line($lot, $take);
            $need -= $take;
        }

        return ['lines' => $lines, 'shortage' => max($need, 0)];
    }

    /** Một dòng kế hoạch lấy hàng, đúng bộ cột material_pick_lines cần. */
    private static function line($lot, float $amount): array
    {
        return [
            'import_id' => (int) $lot->id,
            'import_code' => $lot->code,
            'location_id' => $lot->location_id ? (int) $lot->location_id : null,
            'location_code' => $lot->location_code,
            'expired_date' => $lot->expired_date,
            'expiry_level' => $lot->expiry_level,
            'suggested_amount' => round($amount, 4),
            'unit' => $lot->unit_short_name,
            'available' => $lot->available,
        ];
    }

    /**
     * Sắp lại thứ tự đi lấy cho cả một đợt: gom theo đường đi trong kho chứ không theo
     * đề nghị, để nhân viên đi một vòng. Cùng vị trí thì gom tiếp theo mã lô.
     *
     * @param  array  $lines  Mảng dòng đã có location_id
     * @return array Chính mảng đó, thêm khoá 'sequence' bắt đầu từ 1
     */
    public static function sequence(array $lines): array
    {
        $paths = self::locationPaths(array_filter(array_column($lines, 'location_id')));

        usort($lines, function ($a, $b) use ($paths) {
            $pa = $paths[$a['location_id'] ?? 0] ?? [PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX, ''];
            $pb = $paths[$b['location_id'] ?? 0] ?? [PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX, ''];

            return [$pa[0], $pa[1], $pa[2], $pa[3], $a['import_code'] ?? '']
                <=> [$pb[0], $pb[1], $pb[2], $pb[3], $b['import_code'] ?? ''];
        });

        foreach ($lines as $i => $line) {
            $lines[$i]['sequence'] = $i + 1;
        }

        return $lines;
    }

    /** Đường đi tới từng vị trí: [kho, phòng, kệ, mã vị trí] để sắp thứ tự lấy hàng. */
    private static function locationPaths(array $locationIds): array
    {
        if (! $locationIds) {
            return [];
        }

        return DB::table('locations')
            ->select('id', 'code', 'warehouse_id', 'room_id', 'shelf_id')
            ->whereIn('id', array_unique($locationIds))
            ->get()
            ->mapWithKeys(fn ($loc) => [$loc->id => [
                (int) ($loc->warehouse_id ?? PHP_INT_MAX),
                (int) ($loc->room_id ?? PHP_INT_MAX),
                (int) ($loc->shelf_id ?? PHP_INT_MAX),
                (string) $loc->code,
            ]])
            ->all();
    }

    /**
     * Phần ĐANG GIỮ của từng lô: tổng số lượng các dòng lấy hàng còn treo.
     *
     * @return \Illuminate\Support\Collection import_id => số lượng đang giữ
     */
    public static function heldByImport(int $departmentId, ?int $ignoreWaveId = null)
    {
        return DB::table(self::LINE_TABLE)
            ->join(self::WAVE_TABLE, self::LINE_TABLE.'.wave_id', '=', self::WAVE_TABLE.'.id')
            ->select(self::LINE_TABLE.'.import_id', DB::raw('SUM('.self::HELD_AMOUNT.') as total'))
            ->where(self::WAVE_TABLE.'.department_id', $departmentId)
            ->where(self::WAVE_TABLE.'.status_id', 1)
            ->whereIn(self::WAVE_TABLE.'.status', self::HOLDING_WAVE_STATUSES)
            ->whereIn(self::LINE_TABLE.'.status', self::HOLDING_LINE_STATUSES)
            ->when($ignoreWaveId, fn ($query) => $query->where(self::WAVE_TABLE.'.id', '<>', $ignoreWaveId))
            ->groupBy(self::LINE_TABLE.'.import_id')
            ->pluck('total', 'import_id');
    }

    /** Phần đang giữ của MỘT lô - dùng khi kiểm tra ngay trước lúc ghi. */
    public static function heldOf(int $importId, ?int $ignoreWaveId = null): float
    {
        return (float) DB::table(self::LINE_TABLE)
            ->join(self::WAVE_TABLE, self::LINE_TABLE.'.wave_id', '=', self::WAVE_TABLE.'.id')
            ->where(self::LINE_TABLE.'.import_id', $importId)
            ->where(self::WAVE_TABLE.'.status_id', 1)
            ->whereIn(self::WAVE_TABLE.'.status', self::HOLDING_WAVE_STATUSES)
            ->whereIn(self::LINE_TABLE.'.status', self::HOLDING_LINE_STATUSES)
            ->when($ignoreWaveId, fn ($query) => $query->where(self::WAVE_TABLE.'.id', '<>', $ignoreWaveId))
            ->selectRaw('COALESCE(SUM('.self::HELD_AMOUNT.'), 0) as total')
            ->value('total');
    }

    /** Tổng một cột theo từng mã xuất nhập của phòng - giống MaterialExportController::sumByImport. */
    private static function sumByImport(string $table, string $column, int $departmentId)
    {
        return DB::table($table)
            ->select('import_id', DB::raw('SUM(`'.$column.'`) as total'))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');
    }
}
