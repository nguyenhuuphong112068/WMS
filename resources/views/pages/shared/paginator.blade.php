{{--
|--------------------------------------------------------------------------
| THANH PHÂN TRANG PHÍA SERVER (LengthAwarePaginator)
|--------------------------------------------------------------------------
| Cùng giao diện với phân trang của bảng DataTables (bộ render 'wms' ở
| layout/js.blade.php, kiểu dáng .md-pager ở layout/css.blade.php):
|   « ‹ Trước  1 … 4 5 6 … 20  Sau › »   Đến trang [ 5 ] / 20
| Ô "Đến trang" chỉ hiện khi có trên 7 trang; nhảy trang do layout/js xử lý
| qua data-url (chuỗi __PAGE__ được thay bằng số trang).
|
| Tham số:
| - pgItems : đối tượng LengthAwarePaginator do Controller trả về
| - pgTab   : tab cần mở lại khi bấm sang trang (bỏ trống nếu màn hình không có tab)
| - pgUnit  : tên đơn vị dòng, ví dụ 'phiếu' - mặc định 'dòng'
--}}

@php
    $pgTab = $pgTab ?? null;
    $pgUnit = $pgUnit ?? 'dòng';

    if ($pgTab) {
        $pgItems->appends(['tab' => $pgTab]);
    }

    $pgCurrent = $pgItems->currentPage();
    $pgLast = $pgItems->lastPage();
    $pgFmt = fn ($n) => number_format($n, 0, ',', '.');

    // Cửa sổ số trang: luôn có trang đầu / trang cuối, quanh trang hiện tại 2 trang
    $pgNumbers = collect(range(max(1, $pgCurrent - 2), min($pgLast, $pgCurrent + 2)))
        ->prepend(1)
        ->push($pgLast)
        ->unique()
        ->sort()
        ->values();

    $pgNav = [
        ['first', 'fa-angle-double-left', 'Trang đầu', null, 1, $pgCurrent > 1],
        ['previous', 'fa-angle-left', 'Trang trước', 'Trước', $pgCurrent - 1, $pgCurrent > 1],
        ['next', 'fa-angle-right', 'Trang sau', 'Sau', $pgCurrent + 1, $pgCurrent < $pgLast],
        ['last', 'fa-angle-double-right', 'Trang cuối', null, $pgLast, $pgCurrent < $pgLast],
    ];
@endphp

<div class="md-tablefoot">
    <div class="md-pager-info">
        @if ($pgItems->total() > 0)
            Hiển thị {{ $pgFmt($pgItems->firstItem()) }}–{{ $pgFmt($pgItems->lastItem()) }}
            / {{ $pgFmt($pgItems->total()) }} {{ $pgUnit }}
        @else
            Không có {{ $pgUnit }} nào trong khoảng đã lọc
        @endif
    </div>

    @if ($pgLast > 1)
        <nav class="md-pager" aria-label="Phân trang">
            <div class="md-pager-list">
                @foreach (array_slice($pgNav, 0, 2) as [$pgKey, $pgIcon, $pgLabel, $pgText, $pgTarget, $pgOn])
                    @include('pages.shared.paginatorNav')
                @endforeach

                @php $pgPrevious = 0; @endphp
                @foreach ($pgNumbers as $pgNumber)
                    @if ($pgNumber - $pgPrevious > 1)
                        <span class="md-pager-gap" aria-hidden="true">…</span>
                    @endif

                    @if ($pgNumber === $pgCurrent)
                        <span class="md-pager-btn is-active" aria-current="page">{{ $pgNumber }}</span>
                    @else
                        <a class="md-pager-btn" href="{{ $pgItems->url($pgNumber) }}"
                            aria-label="Trang {{ $pgNumber }}">{{ $pgNumber }}</a>
                    @endif

                    @php $pgPrevious = $pgNumber; @endphp
                @endforeach

                @foreach (array_slice($pgNav, 2) as [$pgKey, $pgIcon, $pgLabel, $pgText, $pgTarget, $pgOn])
                    @include('pages.shared.paginatorNav')
                @endforeach
            </div>

            @if ($pgLast > 7)
                <label class="md-pager-jump">
                    <span>Đến trang</span>
                    <input type="number" class="md-pager-input" inputmode="numeric" aria-label="Đến trang"
                        min="1" max="{{ $pgLast }}" value="{{ $pgCurrent }}"
                        data-url="{{ str_replace('987654321', '__PAGE__', $pgItems->url(987654321)) }}">
                    <span>/ {{ $pgFmt($pgLast) }}</span>
                </label>
            @endif
        </nav>
    @endif
</div>
