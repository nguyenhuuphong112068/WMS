<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * BỘ LỌC KHOẢNG NGÀY + PHÂN TRANG PHÍA SERVER cho các màn hình danh sách.
 *
 * Vì sao cần: các tab Sổ nhập / Sổ sử dụng / Đề nghị trước đây nạp TOÀN BỘ bản ghi
 * của phòng ban rồi để DataTables phân trang ở trình duyệt. Dữ liệu tích luỹ vài năm
 * là trang treo. Từ nay mỗi tab chỉ lấy đúng một trang trong một khoảng ngày.
 *
 * MỖI TAB MỘT TIỀN TỐ riêng (book_, rep_, req_, tsent_...) để nhiều bảng trên cùng
 * một trang không giẫm chân nhau: book_from, book_to, book_q, book_per, book_page.
 *
 * Quy ước tham số trên URL:
 * - Chưa có <prefix>from lẫn <prefix>to  -> lần đầu vào màn hình, mặc định 30 ngày gần nhất.
 * - Có nhưng để trống                    -> người dùng chọn "Tất cả", không giới hạn ngày.
 */
class ListRange
{
    /** Khoảng ngày mặc định khi mới mở màn hình (tính cả hôm nay). */
    public const DEFAULT_DAYS = 30;

    /** Các mức số dòng một trang cho người dùng chọn. */
    public const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    public const DEFAULT_PER_PAGE = 50;

    /** Độ dài tối đa của từ khoá tìm kiếm, chặn chuỗi rác quá dài. */
    private const KEYWORD_MAX = 100;

    /**
     * Khoảng ngày của một tab: ['from' => 'Y-m-d'|null, 'to' => 'Y-m-d'|null].
     * null nghĩa là không chặn đầu đó (người dùng chọn "Tất cả").
     */
    public static function of(Request $request, string $prefix = '', int $days = self::DEFAULT_DAYS): array
    {
        $fromKey = $prefix.'from';
        $toKey = $prefix.'to';

        if (! $request->has($fromKey) && ! $request->has($toKey)) {
            return [
                'from' => now()->subDays(max($days, 1) - 1)->format('Y-m-d'),
                'to' => now()->format('Y-m-d'),
            ];
        }

        $from = self::date($request->input($fromKey));
        $to = self::date($request->input($toKey));

        // Nhập ngược (từ > đến) thì tự đảo lại cho đúng thứ tự
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return ['from' => $from, 'to' => $to];
    }

    /** Chuẩn hoá một giá trị ngày về Y-m-d; trống hoặc sai định dạng thì trả null. */
    private static function date($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /** Số dòng một trang, chỉ nhận các mức đã khai ở PER_PAGE_OPTIONS. */
    public static function perPage(Request $request, string $prefix = ''): int
    {
        $value = (int) $request->input($prefix.'per');

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : self::DEFAULT_PER_PAGE;
    }

    /** Từ khoá tìm kiếm đã cắt gọn, trống thì trả về chuỗi rỗng. */
    public static function keyword(Request $request, string $prefix = ''): string
    {
        return mb_substr(trim((string) $request->input($prefix.'q')), 0, self::KEYWORD_MAX);
    }

    /** Tên tham số trang của tab, truyền vào paginate() để nhiều bảng không đụng nhau. */
    public static function pageName(string $prefix = ''): string
    {
        return $prefix.'page';
    }

    /**
     * Chặn theo khoảng ngày, dùng với ->tap():
     *   ->tap(ListRange::dateFilter('chemical_imports.imported_date', $range))
     */
    public static function dateFilter(string $column, array $range): callable
    {
        return function ($query) use ($column, $range) {
            if (! empty($range['from'])) {
                $query->whereDate($column, '>=', $range['from']);
            }

            if (! empty($range['to'])) {
                $query->whereDate($column, '<=', $range['to']);
            }
        };
    }

    /**
     * Như dateFilter nhưng GIỮ LẠI các bản ghi còn dở dang dù ngoài khoảng ngày.
     *
     * Dùng cho các tab quy trình (Đề nghị cấp phát, Chuyển liên phòng ban, Hàng chờ huỷ):
     * lọc 30 ngày mà giấu mất một đề nghị 2 tháng trước còn chờ duyệt thì hỏng nghiệp vụ.
     *
     * @param  string  $statusColumn  cột trạng thái duyệt / cấp phát
     * @param  array  $pendingValues  các giá trị coi là "còn dở dang"
     */
    public static function dateFilterKeepPending(string $column, array $range, string $statusColumn, array $pendingValues): callable
    {
        return self::dateFilterKeep(
            $column,
            $range,
            fn ($query) => $query->orWhereIn($statusColumn, $pendingValues)
        );
    }

    /**
     * Như dateFilterKeepPending nhưng điều kiện "còn dở dang" do nơi gọi tự viết, dùng
     * khi phải xét nhiều cột (ví dụ đề nghị vật tư: chưa duyệt xong HOẶC chưa cấp đủ).
     *
     * @param  callable  $keep  nhận query của mệnh đề OR, thêm các orWhere... vào đó
     */
    public static function dateFilterKeep(string $column, array $range, callable $keep): callable
    {
        return function ($query) use ($column, $range, $keep) {
            if (empty($range['from']) && empty($range['to'])) {
                return;
            }

            $query->where(function ($outer) use ($column, $range, $keep) {
                $outer->where(function ($inner) use ($column, $range) {
                    if (! empty($range['from'])) {
                        $inner->whereDate($column, '>=', $range['from']);
                    }

                    if (! empty($range['to'])) {
                        $inner->whereDate($column, '<=', $range['to']);
                    }
                });

                $keep($outer);
            });
        };
    }

    /**
     * Cắt trang cho danh sách ĐÃ DỰNG SẴN TRONG PHP (không cắt được ở SQL).
     *
     * Dùng cho các bảng phải cộng dồn theo thứ tự trước khi hiển thị - ví dụ Sổ hoá chất
     * cấm phải cộng số dư từng lô qua từng dòng. Khối lượng dữ liệu đã được khoảng ngày
     * chặn từ trước nên đây chỉ là bước cắt trang, không phải "nạp toàn bộ rồi cắt".
     */
    public static function paginateCollection($items, int $perPage, string $prefix = ''): LengthAwarePaginator
    {
        $items = $items instanceof Collection ? $items->values() : Collection::make($items)->values();

        $pageName = self::pageName($prefix);
        $page = max(1, (int) Paginator::resolveCurrentPage($pageName));

        return new LengthAwarePaginator(
            $items->slice(($page - 1) * $perPage, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    /**
     * Tìm kiếm theo từ khoá trên nhiều cột, dùng với ->tap():
     *   ->tap(ListRange::search(['chemical_categories.code', 'chem_names.name'], $keyword))
     *
     * Từ khoá đi qua binding của Query Builder nên không ghép chuỗi vào SQL.
     */
    public static function search(array $columns, string $keyword): callable
    {
        return function ($query) use ($columns, $keyword) {
            if ($keyword === '' || $columns === []) {
                return;
            }

            $needle = '%'.$keyword.'%';

            $query->where(function ($inner) use ($columns, $needle) {
                foreach ($columns as $column) {
                    $inner->orWhere($column, 'like', $needle);
                }
            });
        };
    }
}
