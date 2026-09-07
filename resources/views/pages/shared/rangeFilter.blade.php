{{--
|--------------------------------------------------------------------------
| BỘ LỌC TỪ NGÀY - ĐẾN NGÀY + SỐ DÒNG MỘT TRANG (dùng chung Nhập / Sử Dụng)
|--------------------------------------------------------------------------
| Lọc chạy Ở SERVER: bấm Lọc là tải lại trang, Controller chỉ lấy đúng một trang
| dữ liệu trong khoảng ngày. Nhờ vậy dữ liệu tích luỹ nhiều năm cũng không treo trang.
|
| Mỗi bảng trên trang dùng MỘT TIỀN TỐ riêng ($rfPrefix) nên các tab không giẫm chân
| nhau: book_from, book_to, book_q, book_per, book_page.
|
| Tham số:
| - rfRoute       : tên route của màn hình (form GET trỏ về chính nó)
| - rfPrefix      : tiền tố tham số của bảng này, ví dụ 'book_'
| - rfRange       : mảng ['from' => ..., 'to' => ...] do App\Support\ListRange::of() trả về
| - rfPerPage     : số dòng một trang đang chọn
| - rfTab         : tab cần mở lại sau khi lọc (bỏ trống nếu màn hình không có tab)
| - rfKeyword     : từ khoá đang tìm (bỏ trống nếu bảng không cần ô tìm kiếm)
| - rfSearch      : true/false - có hiện ô tìm kiếm hay không (mặc định theo rfKeyword)
| - rfPlaceholder : gợi ý trong ô tìm kiếm
| - rfDateLabel   : tên cột ngày đang lọc, ví dụ 'Ngày nhập'
--}}

@php
    $rfPrefix = $rfPrefix ?? '';
    $rfTab = $rfTab ?? null;
    $rfRange = $rfRange ?? ['from' => null, 'to' => null];
    $rfPerPage = $rfPerPage ?? \App\Support\ListRange::DEFAULT_PER_PAGE;
    $rfKeyword = $rfKeyword ?? null;
    $rfSearch = $rfSearch ?? ($rfKeyword !== null);
    $rfPlaceholder = $rfPlaceholder ?? 'Tìm theo mã, tên...';
    $rfDateLabel = $rfDateLabel ?? 'Khoảng ngày';

    // Giữ nguyên tham số của các bảng khác trên cùng trang: form GET chỉ gửi ô của
    // chính nó, không kèm lại thì lọc bảng này sẽ xoá mất lọc của bảng kia.
    $rfKeep = collect(request()->query())
        ->reject(fn($value, $key) => is_array($value) || $key === 'tab' || str_starts_with($key, $rfPrefix))
        ->all();
@endphp

<form method="GET" action="{{ route($rfRoute) }}" class="rgf-box" data-prefix="{{ $rfPrefix }}">
    @if ($rfTab)
        <input type="hidden" name="tab" value="{{ $rfTab }}">
    @endif

    @foreach ($rfKeep as $rfKeepName => $rfKeepValue)
        <input type="hidden" name="{{ $rfKeepName }}" value="{{ $rfKeepValue }}">
    @endforeach

    <div class="rgf-row">
        <div class="rgf-field">
            <label>{{ $rfDateLabel }} từ</label>
            <input type="date" name="{{ $rfPrefix }}from" class="form-control" value="{{ $rfRange['from'] }}">
        </div>

        <div class="rgf-field">
            <label>đến</label>
            <input type="date" name="{{ $rfPrefix }}to" class="form-control" value="{{ $rfRange['to'] }}">
        </div>

        @if ($rfSearch)
            <div class="rgf-field rgf-field-wide">
                <label>Tìm kiếm</label>
                <input type="text" name="{{ $rfPrefix }}q" class="form-control" autocomplete="off"
                    value="{{ $rfKeyword }}" placeholder="{{ $rfPlaceholder }}">
            </div>
        @endif

        <div class="rgf-field">
            <label>Số dòng</label>
            <select name="{{ $rfPrefix }}per" class="form-control">
                @foreach (\App\Support\ListRange::PER_PAGE_OPTIONS as $rfOption)
                    <option value="{{ $rfOption }}" {{ (int) $rfPerPage === $rfOption ? 'selected' : '' }}>
                        {{ $rfOption }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="rgf-field">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter mr-1"></i> Lọc
            </button>
        </div>

        <div class="rgf-quick">
            <button type="button" data-from="{{ now()->subDays(29)->format('Y-m-d') }}"
                data-to="{{ now()->format('Y-m-d') }}">30 ngày</button>
            <button type="button" data-from="{{ now()->startOfMonth()->format('Y-m-d') }}"
                data-to="{{ now()->format('Y-m-d') }}">Tháng này</button>
            <button type="button" data-from="{{ now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d') }}"
                data-to="{{ now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d') }}">Tháng trước</button>
            <button type="button" data-from="{{ now()->startOfQuarter()->format('Y-m-d') }}"
                data-to="{{ now()->format('Y-m-d') }}">Quý này</button>
            <button type="button" data-from="{{ now()->startOfYear()->format('Y-m-d') }}"
                data-to="{{ now()->format('Y-m-d') }}">Năm nay</button>
            <button type="button" data-from="" data-to="">Tất cả</button>
        </div>
    </div>
</form>

@once
    <style>
        .rgf-box {
            padding: 8px 10px;
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
            border-radius: var(--border-radius-md, 8px);
            background: var(--primary-soft, #eaf3fc);
        }

        .rgf-row {
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .rgf-field {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .rgf-field-wide {
            flex: 1 1 200px;
            min-width: 160px;
        }

        .rgf-field label {
            margin: 0;
            font-size: 0.74rem;
            font-weight: 700;
            color: var(--primary-dark, #1f5e9e);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .rgf-field .form-control {
            height: 31px;
            padding: 2px 8px;
            font-size: 0.84rem;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }

        .rgf-field input[type="date"] {
            width: 145px;
        }

        .rgf-field select.form-control {
            width: 80px;
        }

        .rgf-field .btn {
            height: 31px;
            padding: 2px 12px;
            font-size: 0.84rem;
            line-height: 1.5;
            display: inline-flex;
            align-items: center;
            border-radius: 4px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .rgf-field .btn:hover {
            transform: translateY(-1px);
        }

        .rgf-quick {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
            padding-bottom: 2px;
        }

        .rgf-quick button {
            border: 1px solid var(--primary-lighter, #9cc7ee);
            background: #ffffff;
            color: var(--primary, #2e7bc4);
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 0.78rem;
            font-weight: 600;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .rgf-quick button:hover {
            background: var(--primary, #2e7bc4);
            color: #ffffff;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /* Nút chọn nhanh khoảng thời gian: điền 2 ô ngày rồi gửi form ngay.
               Nút "Tất cả" để trống cả hai ô - Controller hiểu là không chặn ngày. */
            $(document).on('click', '.rgf-quick button', function() {
                var $form = $(this).closest('form');
                var prefix = $form.data('prefix') || '';

                $form.find('[name="' + prefix + 'from"]').val($(this).data('from') || '');
                $form.find('[name="' + prefix + 'to"]').val($(this).data('to') || '');
                $form.trigger('submit');
            });
        });
    </script>
@endonce
