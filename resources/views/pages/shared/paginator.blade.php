{{--
|--------------------------------------------------------------------------
| THANH PHÂN TRANG (dùng chung Nhập / Sử Dụng)
|--------------------------------------------------------------------------
| Dựng tay thay vì dùng view phân trang mặc định của Laravel: view mặc định là
| Tailwind, còn dự án chạy Bootstrap 4 + bảng màu xanh dương nhạt của layout.
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

    // Cửa sổ số trang: luôn có trang đầu / trang cuối, quanh trang hiện tại 2 trang
    $pgNumbers = collect(range(max(1, $pgCurrent - 2), min($pgLast, $pgCurrent + 2)))
        ->prepend(1)
        ->push($pgLast)
        ->unique()
        ->sort()
        ->values();
@endphp

<div class="pgn-bar">
    <div class="pgn-info">
        @if ($pgItems->total() > 0)
            Hiển thị <b>{{ number_format($pgItems->firstItem(), 0, ',', '.') }}</b>–<b>{{ number_format($pgItems->lastItem(), 0, ',', '.') }}</b>
            trên tổng <b>{{ number_format($pgItems->total(), 0, ',', '.') }}</b> {{ $pgUnit }}
        @else
            Không có {{ $pgUnit }} nào trong khoảng đã lọc
        @endif
    </div>

    @if ($pgLast > 1)
        <ul class="pagination pagination-sm pgn-list mb-0">
            <li class="page-item {{ $pgItems->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $pgItems->onFirstPage() ? '#' : $pgItems->previousPageUrl() }}">
                    <i class="fas fa-angle-left"></i>
                </a>
            </li>

            @php $pgPrevious = 0; @endphp
            @foreach ($pgNumbers as $pgNumber)
                @if ($pgNumber - $pgPrevious > 1)
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                @endif

                <li class="page-item {{ $pgNumber === $pgCurrent ? 'active' : '' }}">
                    <a class="page-link" href="{{ $pgItems->url($pgNumber) }}">{{ $pgNumber }}</a>
                </li>

                @php $pgPrevious = $pgNumber; @endphp
            @endforeach

            <li class="page-item {{ $pgItems->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $pgItems->hasMorePages() ? $pgItems->nextPageUrl() : '#' }}">
                    <i class="fas fa-angle-right"></i>
                </a>
            </li>
        </ul>
    @endif
</div>

@once
    <style>
        .pgn-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .pgn-info {
            font-size: 0.82rem;
            color: var(--text-main, #2d3748);
        }

        .pgn-info b {
            color: var(--primary-dark, #1f5e9e);
        }

        .pgn-list .page-link {
            color: var(--primary, #2e7bc4);
            border-color: var(--primary-lighter, #9cc7ee);
            min-width: 32px;
            text-align: center;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .pgn-list .page-item.active .page-link {
            background: var(--primary, #2e7bc4);
            border-color: var(--primary, #2e7bc4);
            color: #ffffff;
        }

        .pgn-list .page-item.disabled .page-link {
            color: #94a3b8;
            border-color: #e2e8f0;
        }

        .pgn-list .page-link:hover {
            background: var(--primary-soft, #eaf3fc);
        }
    </style>
@endonce
