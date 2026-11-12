<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * VPP CẦN DỰ TRÙ - dùng cho tab "Danh sách VPP cần dự trù" (Dự Trù VPP).
 *
 * Gộp ba nguồn (SOURCE_* - cũng là các lựa chọn của bộ lọc "Nguồn" trên tab):
 *   1. TỰ ĐỘNG: VPP của phòng đang tồn dưới ngưỡng tối thiểu đã khai
 *      (vpp_department_categories.min_stock) - tồn tính qua
 *      App\Support\MaxStockWarning::onHandVpp(), đúng công thức dùng ở màn Tồn Kho.
 *   2. THỦ CÔNG: người dùng bấm nút "Đề nghị dự trù VPP" (phải nhập lý do) - hoặc ngay
 *      trên dòng đề nghị của modal Đề Nghị Thường Quy / Theo ĐG Rủi Ro (SỬ DỤNG VPP,
 *      không phụ thuộc đề nghị đó có được lưu hay không), hoặc ngay trên danh mục
 *      "VPP Của Phòng" (DANH MỤC) - lưu ở bảng vpp_watchlist_items.
 *   3. TỰ ĐỘNG: VPP THIẾU TỒN KHẢ DỤNG cho nhu cầu theo chu kỳ của tháng tới - đúng các
 *      dòng "Không đáp ứng" của tab Khả dụng tháng tới ở màn Tồn Kho
 *      (App\Support\VppAvailabilityForecast); SL đề nghị gợi ý = lượng nên dự trù.
 *
 * Một VPP trong danh mục thuộc nhiều nguồn cùng lúc chỉ hiện MỘT dòng - xem forTab().
 *
 * Mọi VPP trong danh mục do Hành Chánh mua và cấp phát (kho VPP), nên danh sách chia theo PHÒNG:
 *   - forVpp()     : phòng thường -> tab "Danh sách VPP cần đề nghị Hành Chính" ở SỬ DỤNG VPP
 *     (Đề nghị VPP), gửi đề nghị tới kho VPP của phòng Hành Chính (App\Support\VppStore).
 *   - forEstimate(): tab "Danh sách VPP cần dự trù" ở DỰ TRÙ VPP. Phòng Hành Chính (không tự đề
 *     nghị kho của mình) thấy mọi dòng để dự trù mua; phòng thường chỉ còn VPP ngoài danh mục -
 *     không đề nghị kho VPP được nên lập dự trù, Hành Chánh vẫn là bộ phận mua hàng.
 */
class VppWatchlist
{
    public const TABLE = 'vpp_watchlist_items';

    public const SOURCE_THRESHOLD = 'threshold';

    public const SOURCE_USER = 'user';

    public const SOURCE_FORECAST = 'forecast';

    /** Đề nghị liên phòng ban còn dở (phòng nhận chưa cấp xong) và nhãn hiển thị - xem inProgress(). */
    private const TRANSFER_STATUS_LABELS = [
        'draft' => 'Lưu tạm',
        'pending_sign' => 'Chờ ký',
        'pending' => 'Chờ cấp phát',
        'partial' => 'Cấp phát một phần',
    ];

    /** Tháng đang xét của nguồn "Thiếu tồn khả dụng": luôn là tháng tiếp theo (Y-m). */
    public static function forecastMonth(): string
    {
        return now()->startOfMonth()->addMonthNoOverflow()->format('Y-m');
    }

    /** Nhãn các nguồn cho bộ lọc / cột Nguồn. */
    public static function sourceLabels(): array
    {
        return [
            self::SOURCE_THRESHOLD => 'Dưới ngưỡng tối thiểu',
            self::SOURCE_USER => 'Người dùng đề nghị',
            self::SOURCE_FORECAST => 'Thiếu tồn khả dụng cho tháng '.Carbon::createFromFormat('Y-m-d', self::forecastMonth().'-01')->format('m/Y'),
        ];
    }

    /** VPP trong danh mục của phòng đang tồn dưới ngưỡng tối thiểu đã khai. */
    public static function belowThreshold(int $departmentId)
    {
        $rows = DB::table(DepartmentVpp::TABLE)
            ->join('vpp_categories', DepartmentVpp::TABLE.'.category_id', '=', 'vpp_categories.id')
            ->leftJoin('material_names', 'vpp_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'vpp_categories.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', 'vpp_categories.unit_id', '=', 'units.id')
            ->select(
                'vpp_categories.id as category_id',
                'vpp_categories.purchasing_department',
                'material_names.name as material_name',
                'manufacturers.short_name as manufacturer_short_name',
                DepartmentVpp::TABLE.'.min_stock',
                'vpp_categories.unit_id',
                'units.short_name as unit_short_name'
            )
            ->where(DepartmentVpp::TABLE.'.department_id', $departmentId)
            ->where(DepartmentVpp::TABLE.'.status_id', 1)
            ->where('vpp_categories.status_id', 1)
            ->where('vpp_categories.app_status', 'approved')
            ->whereNotNull(DepartmentVpp::TABLE.'.min_stock')
            ->where(DepartmentVpp::TABLE.'.min_stock', '>', 0)
            ->orderBy('material_names.name', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            return $rows;
        }

        $onHand = MaxStockWarning::onHandVpp($departmentId);

        return $rows->map(function ($row) use ($onHand) {
            $row->on_hand = (float) ($onHand[(int) $row->category_id] ?? 0);

            return $row;
        })->filter(fn ($row) => $row->on_hand < (float) $row->min_stock)->values();
    }

    /** Các dòng được ghi nhớ thủ công còn hoạt động của phòng, kèm mô tả lấy từ danh mục. */
    public static function rememberedRows(int $departmentId)
    {
        return DB::table(self::TABLE)
            ->leftJoin('vpp_categories', self::TABLE.'.category_id', '=', 'vpp_categories.id')
            ->leftJoin('material_names', 'vpp_categories.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', 'vpp_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentVpp::joinUnit($query, $departmentId, self::TABLE.'.category_id'))
            ->leftJoin(DepartmentVpp::TABLE.' as dm_status', function ($join) use ($departmentId) {
                $join->on('dm_status.category_id', '=', self::TABLE.'.category_id')
                    ->where('dm_status.department_id', '=', $departmentId);
            })
            ->select(
                self::TABLE.'.*',
                'vpp_categories.purchasing_department',
                'material_names.name as category_material_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.id as unit_id',
                'units.short_name as unit_short_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->where(function ($q) {
                $q->whereNull('dm_status.status_id')
                    ->orWhere('dm_status.status_id', '!=', 0);
            })
            ->orderBy(self::TABLE.'.created_at', 'desc')
            ->get();
    }

    /**
     * Danh sách gộp cho tab "VPP cần dự trù": dưới ngưỡng + ghi nhớ thủ công + thiếu tồn
     * khả dụng tháng tới, không trùng dòng. Mỗi dòng mang mảng sources (SOURCE_*) để lọc.
     */
    public static function forTab(int $departmentId)
    {
        $rows = [];
        $blank = [
            'watchlist_id' => null,
            'manufacturer_name' => null,
            'unit' => null,
            'unit_id' => null,
            'purchasing_department' => null,
            'on_hand' => null,
            'min_stock' => null,
            'below_threshold' => false,
            'forecast' => null,
            'quantity' => null,
            'source_type' => null,
            'note' => null,
            'remembered_by' => null,
            'remembered_at' => null,
            'sources' => [],
        ];

        foreach (self::belowThreshold($departmentId) as $t) {
            $rows['cat_'.$t->category_id] = (object) array_merge($blank, [
                'category_id' => $t->category_id,
                'material_name' => $t->material_name,
                'technical_specification' => null,
                'manufacturer_name' => $t->manufacturer_short_name,
                'unit' => $t->unit_short_name,
                'unit_id' => $t->unit_id,
                'purchasing_department' => $t->purchasing_department,
                'on_hand' => $t->on_hand,
                'min_stock' => $t->min_stock,
                'below_threshold' => true,
                'sources' => [self::SOURCE_THRESHOLD],
            ]);
        }

        foreach (self::rememberedRows($departmentId) as $r) {
            $key = $r->category_id ? 'cat_'.$r->category_id : 'name_'.mb_strtolower(trim((string) $r->material_name));

            $rows[$key] ??= (object) array_merge($blank, [
                'category_id' => $r->category_id,
                'material_name' => $r->category_id ? $r->category_material_name : $r->material_name,
                // Quy cách chỉ có ở dòng tự nhập ngoài danh mục (danh mục VPP không có Thông tin kỹ thuật)
                'technical_specification' => $r->category_id ? null : $r->technical_specification,
                'manufacturer_name' => $r->manufacturer_short_name,
                'unit' => $r->unit_short_name,
                'unit_id' => $r->unit_id,
                'purchasing_department' => $r->purchasing_department,
            ]);

            $rows[$key]->watchlist_id = $r->id;
            $rows[$key]->quantity = $r->quantity;
            $rows[$key]->source_type = $r->source_type;
            $rows[$key]->note = $r->note;
            // Bấm đề nghị lại chỉ cập nhật dòng cũ (remember) - hiện người / ngày đề nghị lần gần nhất
            $rows[$key]->remembered_by = $r->updated_by ?: $r->created_by;
            $rows[$key]->remembered_at = $r->updated_at ?: $r->created_at;
            $rows[$key]->sources[] = self::SOURCE_USER;
        }

        // Dòng "Không đáp ứng" + dòng "Vừa đủ" còn lượng cần dự trù (sau tháng tới hụt ngưỡng tối thiểu)
        // - khớp cột "Cần Dự Trù" của tab Khả dụng ở Tồn Kho VPP
        $forecast = VppAvailabilityForecast::build($departmentId, self::forecastMonth());
        $locked = self::lockedCategoryIds($departmentId);

        foreach ($forecast['rows'] as $f) {
            $needed = $f->state === 'short' || ($f->state === 'tight' && $f->suggest > 0);

            if (! $needed || isset($locked[(int) $f->category_id])) {
                continue;
            }

            $key = 'cat_'.$f->category_id;

            $rows[$key] ??= (object) array_merge($blank, [
                'category_id' => $f->category_id,
                'material_name' => $f->material_name,
                'technical_specification' => null,
                'manufacturer_name' => $f->manufacturer_short_name,
                'unit' => $f->unit_short_name,
                'unit_id' => $f->unit_id,
                'purchasing_department' => $f->purchasing_department,
                'on_hand' => $f->stock,
                'min_stock' => $f->min_stock,
            ]);

            $rows[$key]->forecast = (object) [
                'month' => $forecast['month']['value'],
                'month_label' => $forecast['month']['label'],
                'available' => $f->available,
                'demand' => $f->demand,
                'gap' => $f->gap,
                'suggest' => $f->suggest,
            ];
            $rows[$key]->sources[] = self::SOURCE_FORECAST;

            // Người dùng chưa khai số lượng thì gợi ý bằng lượng nên dự trù của màn Tồn Kho
            if ($rows[$key]->quantity === null && $f->suggest > 0) {
                $rows[$key]->quantity = $f->suggest;
            }
        }

        // Đã lập phiếu dự trù / đề nghị liên phòng ban mà chưa xong: gắn mã phiếu, khoá chọn lại
        $progress = self::inProgress($departmentId);

        foreach ($rows as $row) {
            $refs = $row->category_id
                ? ($progress['cat'][(int) $row->category_id] ?? [])
                : ($progress['name'][mb_strtolower(trim((string) $row->material_name))] ?? []);

            $row->in_progress = array_values($refs);
        }

        return collect(array_values($rows))->sortBy('material_name')->values();
    }

    /** Danh sách VPP cần dự trù (Dự Trù VPP) - xem phần đầu lớp. */
    public static function forEstimate(int $departmentId)
    {
        $rows = self::forTab($departmentId);

        if (VppStore::isVppDepartment($departmentId)) {
            return $rows;
        }

        return $rows->filter(fn ($row) => MaterialPurchasing::keyOf($row->purchasing_department) !== VppStore::PURCHASING_KEY)->values();
    }

    /**
     * Danh sách VPP cần đề nghị Hành Chính (Sử Dụng VPP - Đề nghị VPP): VPP trong danh
     * mục do Hành Chính mua. Phòng Hành Chính / chưa có phòng Hành Chính để gửi thì rỗng.
     */
    public static function forVpp(int $departmentId)
    {
        if (! VppStore::targetDepartmentId($departmentId)) {
            return collect();
        }

        return self::forTab($departmentId)
            ->filter(fn ($row) => $row->category_id && $row->purchasing_department === VppStore::PURCHASING_KEY)
            ->values();
    }

    /**
     * Dòng nào trong $items (dòng gửi từ form lập phiếu / đề nghị) đã có phiếu chưa xong thì báo
     * lỗi - chặn ở server phòng khi trang đang mở từ trước (JS đã khoá ô chọn), xem inProgress().
     *
     * @return array<string, string> [khoá lỗi items.i.category_id => thông báo]
     */
    public static function inProgressErrors(int $departmentId, array $items): array
    {
        $progress = self::inProgress($departmentId);
        $errors = [];

        foreach ($items as $index => $item) {
            $categoryId = (int) ($item['category_id'] ?? 0);
            $refs = $categoryId
                ? ($progress['cat'][$categoryId] ?? [])
                : ($progress['name'][mb_strtolower(trim((string) ($item['material_name'] ?? '')))] ?? []);

            if ($refs) {
                $ref = reset($refs);
                // Chỉ hạ chữ đầu: "Đề nghị VPP" -> "đề nghị VPP", không làm hỏng chữ viết tắt
                $label = mb_strtolower(mb_substr($ref['label'], 0, 1)).mb_substr($ref['label'], 1);
                $errors['items.'.$index.'.category_id'] = 'VPP dòng '.($index + 1).' đã có trong '.$label.' '
                    .$ref['code'].' ('.$ref['status'].'), không lập lại được.';
            }
        }

        return $errors;
    }

    /** VPP phòng đã khoá ở danh mục - không đưa vào danh sách cần dự trù. */
    private static function lockedCategoryIds(int $departmentId): array
    {
        return DB::table(DepartmentVpp::TABLE)
            ->where('department_id', $departmentId)
            ->where('status_id', 0)
            ->pluck('category_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /**
     * Tạo một "Đề nghị dự trù VPP" (nút trên dòng đề nghị của modal Thường Quy / Theo ĐG
     * Rủi Ro, hoặc trên tab "VPP Của Phòng") - bắt buộc nêu số lượng (theo đơn vị tính
     * của phòng) và lý do dự trù. VPP trong
     * danh mục khoá theo (department_id, category_id) - bấm lại chỉ cập nhật lại số lượng /
     * lý do / nguồn, không tạo dòng trùng. VPP ngoài danh mục khoá theo tên (không phân biệt hoa
     * thường) vì không có category_id để ràng buộc duy nhất.
     */
    public static function remember(int $departmentId, ?int $categoryId, ?string $materialName, ?string $technicalSpecification, ?float $quantity, ?string $note, ?string $sourceType, string $actor): array
    {
        $materialName = trim((string) $materialName) ?: null;
        $technicalSpecification = trim((string) $technicalSpecification) ?: null;
        $note = trim((string) $note) ?: null;

        if (! $categoryId && ! $materialName) {
            return ['success' => false, 'message' => 'Vui lòng chọn VPP trong danh mục hoặc nhập tên VPP trước khi đề nghị dự trù!'];
        }

        if (! $quantity || $quantity <= 0) {
            return ['success' => false, 'message' => 'Vui lòng nhập số lượng cần đề nghị lớn hơn 0.'];
        }

        if (! $note) {
            return ['success' => false, 'message' => 'Vui lòng nhập lý do dự trù.'];
        }

        if ($categoryId) {
            $isLocked = DB::table(DepartmentVpp::TABLE)
                ->where('department_id', $departmentId)
                ->where('category_id', $categoryId)
                ->where('status_id', 0)
                ->exists();

            if ($isLocked) {
                return ['success' => false, 'message' => 'VPP của phòng đã bị khoá, không thể đề nghị dự trù!'];
            }
        }

        $query = DB::table(self::TABLE)->where('department_id', $departmentId);
        $query = $categoryId
            ? $query->where('category_id', $categoryId)
            : $query->whereNull('category_id')->whereRaw('LOWER(material_name) = ?', [mb_strtolower($materialName)]);

        $existing = $query->first();

        $payload = [
            'technical_specification' => $technicalSpecification,
            'quantity' => $quantity,
            'note' => $note,
            'source_type' => $sourceType,
            'status_id' => 1,
            'updated_by' => $actor,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table(self::TABLE)->where('id', $existing->id)->update($payload);
        } else {
            DB::table(self::TABLE)->insert($payload + [
                'department_id' => $departmentId,
                'category_id' => $categoryId,
                'material_name' => $categoryId ? null : $materialName,
                'created_by' => $actor,
                'created_at' => now(),
            ]);
        }

        return self::rememberResult($departmentId, $categoryId, $materialName, (bool) $existing);
    }

    /**
     * Thông báo sau khi ghi nhớ: nói rõ VPP hiện ở danh sách nào (VPP Hành Chính mua sang
     * "Danh sách VPP cần đề nghị Hành Chính", còn lại "Danh sách VPP cần dự trù" - cùng
     * quy tắc forEstimate / forVpp) và nếu đang nằm trong phiếu chưa xong thì kèm mã phiếu, vì
     * khi đó dòng chỉ hiện ở trạng thái "Đã lập phiếu / Đã đề nghị" chứ không ở trạng thái mặc định.
     * in_progress = true để JS hiện hộp thoại chờ người dùng đọc thay vì toast tự tắt.
     */
    private static function rememberResult(int $departmentId, ?int $categoryId, ?string $materialName, bool $updated): array
    {
        $purchasing = $categoryId
            ? DB::table('vpp_categories')->where('id', $categoryId)->value('purchasing_department')
            : null;

        $toVpp = $categoryId
            && MaterialPurchasing::keyOf($purchasing) === VppStore::PURCHASING_KEY
            && VppStore::targetDepartmentId($departmentId);

        $listName = $toVpp ? '"Danh sách VPP cần đề nghị Hành Chính"' : '"Danh sách VPP cần dự trù"';
        $doneState = $toVpp ? '"Đã đề nghị"' : '"Đã lập phiếu"';

        $progress = self::inProgress($departmentId);
        $refs = $categoryId
            ? ($progress['cat'][$categoryId] ?? [])
            : ($progress['name'][mb_strtolower(trim((string) $materialName))] ?? []);

        $head = $updated
            ? 'VPP đã có trong '.$listName.', đã cập nhật lại số lượng và lý do.'
            : 'Đã gửi đề nghị, VPP được đưa vào '.$listName.'.';

        if (! $refs) {
            return ['success' => true, 'in_progress' => false, 'message' => $head];
        }

        $codes = implode(', ', array_map(
            fn ($ref) => $ref['label'].' '.$ref['code'].' ('.$ref['status'].')',
            array_values($refs)
        ));

        return [
            'success' => true,
            'in_progress' => true,
            'message' => $head.' VPP đang nằm trong '.$codes.' chưa xử lý xong nên hiện ở trạng thái '
                .$doneState.' của danh sách, chọn trạng thái đó (hoặc Tất cả) để xem.',
        ];
    }

    /** Bỏ ghi nhớ một dòng thủ công - VPP vẫn hiện lại nếu đang tồn dưới ngưỡng tối thiểu. */
    public static function dismiss(int $id, int $departmentId, string $actor): bool
    {
        return (bool) DB::table(self::TABLE)
            ->where('id', $id)
            ->where('department_id', $departmentId)
            ->update(['status_id' => 0, 'updated_by' => $actor, 'updated_at' => now()]);
    }

    /**
     * Đóng các "Đề nghị dự trù VPP" đã được đưa vào phiếu dự trù / đề nghị liên phòng ban
     * - lời đề nghị đã có người xử lý, giao hàng xong không được hiện lại như đề nghị mới.
     * VPP trong danh mục khoá theo category_id, ngoài danh mục theo tên (không phân biệt hoa thường).
     */
    public static function closeFor(int $departmentId, array $categoryIds, array $materialNames, string $actor): void
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        $materialNames = array_values(array_unique(array_filter(array_map(fn ($name) => mb_strtolower(trim((string) $name)), $materialNames))));

        if (! $categoryIds && ! $materialNames) {
            return;
        }

        DB::table(self::TABLE)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->where(function ($query) use ($categoryIds, $materialNames) {
                if ($categoryIds) {
                    $query->orWhereIn('category_id', $categoryIds);
                }

                if ($materialNames) {
                    $query->orWhere(function ($sub) use ($materialNames) {
                        $sub->whereNull('category_id')->whereIn(DB::raw('LOWER(TRIM(material_name))'), $materialNames);
                    });
                }
            })
            ->update(['status_id' => 0, 'updated_by' => $actor, 'updated_at' => now()]);
    }

    /**
     * VPP ĐANG ĐƯỢC XỬ LÝ của phòng - đã lập phiếu, chưa xong, không được dự trù lại:
     *
     *   - Dòng phiếu dự trù còn hiệu lực, chưa huỷ (status_id = 1), chưa xác nhận giao
     *     (fulfilled_date NULL), thuộc phiếu chưa huỷ - kể cả Nháp / Chờ ký / Bị từ chối.
     *   - Dòng đề nghị liên phòng ban phòng gửi đi, phòng nhận chưa cấp.
     *
     * @return array{cat: array<int, array>, name: array<string, array>} danh sách phiếu theo category_id / tên thường
     */
    public static function inProgress(int $departmentId): array
    {
        $appStatuses = config('estimate.app_statuses');
        $out = ['cat' => [], 'name' => []];

        $push = function ($categoryId, $name, array $ref) use (&$out) {
            $bucket = $categoryId ? 'cat' : 'name';
            $key = $categoryId ? (int) $categoryId : mb_strtolower(trim((string) $name));

            if ($key === '' || isset($out[$bucket][$key][$ref['type'].$ref['id']])) {
                return;
            }

            $out[$bucket][$key][$ref['type'].$ref['id']] = $ref;
        };

        $estimates = DB::table('vpp_estimate_items as i')
            ->join('vpp_estimates as e', 'e.id', '=', 'i.vpp_estimate_id')
            ->where('e.department_id', $departmentId)
            ->where('e.status_id', 1)
            ->where('e.app_status', '!=', 'cancelled')
            ->where('i.active', 1)
            ->where('i.status_id', 1)
            ->whereNull('i.fulfilled_date')
            ->orderBy('e.id', 'desc')
            ->get(['i.category_id', 'i.material_name', 'e.id', 'e.code', 'e.app_status']);

        foreach ($estimates as $row) {
            $push($row->category_id, $row->material_name, [
                'type' => 'estimate',
                'id' => (int) $row->id,
                'code' => $row->code,
                'label' => 'Phiếu dự trù',
                'status' => $appStatuses[$row->app_status] ?? $row->app_status,
            ]);
        }

        $transfers = DB::table(VppTransferRequest::ITEM_TABLE.' as t')
            ->join(VppTransferRequest::TABLE.' as r', 'r.id', '=', 't.transfer_request_id')
            ->where('r.department_id', $departmentId)
            ->whereIn('r.status', array_keys(self::TRANSFER_STATUS_LABELS))
            ->where('t.active', 1)
            // Dòng của phiếu đang chờ ký còn ở 'draft' - xem VppTransferSignFlow
            ->whereIn('t.status', ['draft', 'pending'])
            ->orderBy('r.id', 'desc')
            ->get(['t.category_id', 'r.id', 'r.code', 'r.status', 'r.to_department_id']);

        foreach ($transfers as $row) {
            $isVpp = VppStore::isVppDepartment((int) $row->to_department_id);

            $push($row->category_id, null, [
                'type' => 'transfer',
                'tab' => $isVpp ? VppStore::TAB : 'transfer',
                'id' => (int) $row->id,
                'code' => $row->code,
                'label' => $isVpp ? 'Đề nghị cấp phát VPP đến HC' : 'Đề nghị liên phòng ban',
                'status' => self::TRANSFER_STATUS_LABELS[$row->status] ?? $row->status,
            ]);
        }

        return $out;
    }
}
