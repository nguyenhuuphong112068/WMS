{{--
|--------------------------------------------------------------------------
| BỘ LỌC PHỤ LỤC / NHÓM HOÁ CHẤT - dùng chung cho các màn hình hoá chất
|--------------------------------------------------------------------------
| Dùng ở: Danh Mục Hoá Chất, Nhập Hoá Chất, Sử Dụng Hoá Chất, Tồn Kho Hoá Chất.
| Mã phân loại lấy từ config/chemical.php, cùng nguồn với ô tick lúc khai báo
| danh mục, không khai báo lặp ở đây.
|
| Cách dùng:
|   @include('pages.shared.classificationFilter', ['clsTarget' => 'mdTable'])
|
| Yêu cầu để lọc chạy được:
| - Bảng đã được khởi tạo DataTables với đúng id truyền vào $clsTarget.
| - Mỗi <tr> của bảng có thuộc tính data-classification="PL2,N1" (rỗng nếu chưa
|   phân loại). Dòng không có thuộc tính này được coi là chưa phân loại.
|
| Lọc chạy ở phía trình duyệt trên toàn bộ dòng của bảng (kể cả dòng ở trang sau),
| nên không phải tải lại trang. Số lượng trong ngoặc của mỗi mục do JS tự đếm từ
| chính bảng đó, không cần Controller truyền thêm.
--}}

@php
    $clsTarget = $clsTarget ?? 'mdTable';
    $clsLabel = $clsLabel ?? 'Phụ lục / Nhóm hoá chất';
    $clsList = config('chemical.classifications', []);
@endphp

<div class="cls-filter">
    <label for="clsSelect-{{ $clsTarget }}">
        <i class="fas fa-filter"></i> {{ $clsLabel }}
    </label>

    <select id="clsSelect-{{ $clsTarget }}" class="form-control cls-select" data-target="{{ $clsTarget }}">
        <option value="all" data-label="Tất cả">Tất cả</option>
        <option value="none" data-label="Chưa phân loại">Chưa phân loại</option>
        @foreach ($clsList as $code => $name)
            <option value="{{ $code }}" data-label="{{ $code }} - {{ $name }}" title="{{ $name }}">
                {{ $code }} - {{ $name }}
            </option>
        @endforeach
    </select>

    <span class="cls-hint">Lọc theo nhóm phân loại khai trong Danh Mục Hoá Chất.</span>
</div>

@once
    <style>
        .cls-filter {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            margin-bottom: 16px;
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-lg);
            background: var(--primary-soft);
        }

        .cls-filter label {
            margin: 0;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .cls-filter .cls-select {
            width: auto;
            min-width: 280px;
            max-width: 100%;
            flex: 1 1 320px;
            border-radius: var(--border-radius-md);
            border: 1px solid #dbe6f2;
            background: #fff;
            font-size: 0.85rem;
        }

        .cls-filter .cls-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
        }

        /* Mục không có dòng nào trong bảng - vẫn hiện để biết là có nhóm đó */
        .cls-filter .cls-select option:disabled {
            color: #cbd5e1;
        }

        .cls-filter .cls-hint {
            color: #94a3b8;
            font-size: 0.78rem;
        }

        /* Đang lọc thì nhấn viền để không quên là bảng đang bị thu hẹp */
        .cls-filter.is-filtering {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Mã đang lọc của từng bảng: {"<id bảng>": "PL2" | "none" | "all"}
            var clsState = {};

            /** Mã phân loại của một dòng, lấy từ data-classification. */
            function clsCodesOf(tr) {
                var raw = ($(tr).attr('data-classification') || '').trim();

                return raw ? raw.split(',') : [];
            }

            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                var want = clsState[settings.nTable.id];

                if (!want || want === 'all') return true;

                var codes = clsCodesOf(settings.aoData[index].nTr);

                return want === 'none' ? codes.length === 0 : codes.indexOf(want) !== -1;
            });

            /* ---------- Đếm số dòng của từng nhóm ngay trên bảng ---------- */
            $('.cls-select').each(function() {
                var id = $(this).data('target');

                if (!$.fn.dataTable.isDataTable('#' + id)) return;

                var counts = {};
                var none = 0;
                var total = 0;

                // Đếm trên TOÀN BỘ dòng của bảng, không chỉ trang đang xem
                $('#' + id).DataTable().rows().nodes().to$().each(function() {
                    var codes = clsCodesOf(this);

                    total++;

                    if (!codes.length) {
                        none++;

                        return;
                    }

                    codes.forEach(function(code) {
                        counts[code] = (counts[code] || 0) + 1;
                    });
                });

                $(this).find('option').each(function() {
                    var value = $(this).val();
                    var count = value === 'all' ? total : (value === 'none' ? none : (counts[value] || 0));

                    $(this).text($(this).data('label') + ' (' + count + ')');

                    // Nhóm không có dòng nào thì khoá lại, chọn vào cũng chỉ ra bảng rỗng
                    if (count === 0 && value !== 'all') $(this).prop('disabled', true);
                });
            });

            /* ---------- Đổi nhóm thì vẽ lại đúng bảng của ô chọn đó ---------- */
            $(document).on('change', '.cls-select', function() {
                var id = $(this).data('target');

                clsState[id] = $(this).val();

                $(this).closest('.cls-filter').toggleClass('is-filtering', $(this).val() !== 'all');

                if ($.fn.dataTable.isDataTable('#' + id)) $('#' + id).DataTable().draw();
            });
        });
    </script>
@endonce
