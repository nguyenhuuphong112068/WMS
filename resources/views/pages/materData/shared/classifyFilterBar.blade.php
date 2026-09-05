@php
    /*
    |--------------------------------------------------------------------------
    | Bộ lọc NĐ 24/2026: Phụ lục / Nhóm / Bảng / Phân loại  (dùng chung)
    |--------------------------------------------------------------------------
    | Dùng cho màn Tên Hoạt Chất và Tên Hoá Chất (bảng id="mdTable").
    | Lọc phía trình duyệt qua $.fn.dataTable.ext.search, đọc data-* trên từng <tr>:
    |   data-apx  = danh sách phụ lục   (vd "I,II,IV")
    |   data-grp  = danh sách "nhóm"    (vd "1,2")
    |   data-tbl  = danh sách "bảng"    (vd "A,C")
    |   data-classification = mã nhóm hình 1 (vd "N1,N9")
    | 4 ô lọc kết hợp theo kiểu VÀ.
    */
    $apxOptions = ['I' => 'I', 'II' => 'II', 'III' => 'III', 'IV' => 'IV'];
    $grpOptions = ['1' => 'Nhóm 1', '2' => 'Nhóm 2'];
    $tblOptions = ['A' => 'Bảng A', 'B' => 'Bảng B', 'C' => 'Bảng C'];
    $clsOptions = \App\Support\ChemicalClassification::labels(); // ['N1' => nhãn, ...]
@endphp

<div class="ai-filters" id="aiFilters">
    <span class="ai-filters-title"><i class="fas fa-filter mr-1 text-primary"></i>Lọc theo NĐ 24/2026:</span>

    <div class="ai-filter">
        <label for="aiF-apx">Phụ lục</label>
        <select id="aiF-apx" class="ai-f-select" data-dim="apx">
            <option value="" data-label="Tất cả">Tất cả</option>
            <option value="__none" data-label="Không thuộc phụ lục">Không thuộc phụ lục</option>
            @foreach ($apxOptions as $val => $lbl)
                <option value="{{ $val }}" data-label="Phụ lục {{ $lbl }}">Phụ lục {{ $lbl }}</option>
            @endforeach
        </select>
    </div>

    <div class="ai-filter">
        <label for="aiF-grp">Nhóm</label>
        <select id="aiF-grp" class="ai-f-select" data-dim="grp">
            <option value="" data-label="Tất cả">Tất cả</option>
            @foreach ($grpOptions as $val => $lbl)
                <option value="{{ $val }}" data-label="{{ $lbl }}">{{ $lbl }}</option>
            @endforeach
        </select>
    </div>

    <div class="ai-filter">
        <label for="aiF-tbl">Bảng</label>
        <select id="aiF-tbl" class="ai-f-select" data-dim="tbl">
            <option value="" data-label="Tất cả">Tất cả</option>
            @foreach ($tblOptions as $val => $lbl)
                <option value="{{ $val }}" data-label="{{ $lbl }}">{{ $lbl }}</option>
            @endforeach
        </select>
    </div>

    <div class="ai-filter ai-filter-wide">
        <label for="aiF-cls">Phân loại</label>
        <select id="aiF-cls" class="ai-f-select" data-dim="cls">
            <option value="" data-label="Tất cả">Tất cả</option>
            <option value="__none" data-label="Chưa phân loại">Chưa phân loại</option>
            @foreach ($clsOptions as $code => $name)
                <option value="{{ $code }}" data-label="{{ $code }} – {{ $name }}" title="{{ $name }}">
                    {{ $code }} – {{ \Illuminate\Support\Str::limit($name, 60) }}
                </option>
            @endforeach
        </select>
    </div>

    <button type="button" class="btn btn-sm ai-f-reset" id="aiFReset" hidden>
        <i class="fas fa-times mr-1"></i>Bỏ lọc
    </button>
</div>

@once
    <style>
        .ai-filters {
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 10px 14px;
            padding: 10px 12px;
            margin-bottom: 14px;
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            background: #f8fafc;
        }

        .ai-filters-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--primary-dark);
            align-self: center;
        }

        .ai-filter {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .ai-filter label {
            margin: 0;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
        }

        .ai-filter .ai-f-select {
            height: 32px;
            min-width: 128px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            font-size: 0.84rem;
            padding: 2px 8px;
        }

        .ai-filter.ai-filter-wide .ai-f-select {
            min-width: 300px;
            max-width: 420px;
        }

        .ai-f-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
            outline: none;
        }

        .ai-f-select.is-active {
            border-color: var(--primary);
            background: var(--primary-soft);
            font-weight: 600;
        }

        .ai-f-select option:disabled {
            color: #cbd5e1;
        }

        .ai-f-reset {
            height: 32px;
            border: 1px solid #fca5a5;
            background: #fff;
            color: #b91c1c;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .ai-f-reset:hover {
            background: #fee2e2;
        }

        /* 3 cột phụ lục / nhóm / bảng */
        .ai-cls-badge {
            display: inline-block;
            background: var(--primary-soft);
            color: var(--primary-dark);
            border: 1px solid var(--primary-lighter);
            border-radius: 5px;
            padding: 1px 7px;
            margin: 1px;
            font-size: 0.78rem;
            font-weight: 700;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var TABLE_ID = 'mdTable';
            if (!$.fn.dataTable.isDataTable('#' + TABLE_ID)) return;

            var dt = $('#' + TABLE_ID).DataTable();
            var want = {
                apx: '',
                grp: '',
                tbl: '',
                cls: ''
            };

            function listOf(tr, attr) {
                var raw = ($(tr).attr(attr) || '').trim();
                return raw ? raw.split(',') : [];
            }

            function rowMatches(tr) {
                for (var dim in want) {
                    var v = want[dim];
                    if (!v) continue;
                    var attr = dim === 'cls' ? 'data-classification' : 'data-' + dim;
                    var list = listOf(tr, attr);
                    if (v === '__none') {
                        if (list.length) return false;
                    } else if (list.indexOf(v) === -1) {
                        return false;
                    }
                }
                return true;
            }

            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                if (settings.nTable.id !== TABLE_ID) return true;
                if (!want.apx && !want.grp && !want.tbl && !want.cls) return true;
                return rowMatches(settings.aoData[index].nTr);
            });

            // Đếm số dòng cho từng lựa chọn (dựa trên toàn bộ dữ liệu, không theo lọc hiện tại)
            var rows = dt.rows().nodes().to$();

            function refreshCounts() {
                $('.ai-f-select').each(function() {
                    var dim = $(this).data('dim');
                    var attr = dim === 'cls' ? 'data-classification' : 'data-' + dim;
                    var $sel = $(this);
                    $sel.find('option').each(function() {
                        var val = $(this).val();
                        var n;
                        if (val === '') {
                            n = rows.length;
                        } else if (val === '__none') {
                            n = rows.filter(function() {
                                return listOf(this, attr).length === 0;
                            }).length;
                        } else {
                            n = rows.filter(function() {
                                return listOf(this, attr).indexOf(val) !== -1;
                            }).length;
                        }
                        $(this).text($(this).data('label') + ' (' + n + ')');
                        if (val !== '' && n === 0) $(this).prop('disabled', true);
                    });
                });
            }
            refreshCounts();

            $('.ai-f-select').on('change', function() {
                want[$(this).data('dim')] = $(this).val();
                $(this).toggleClass('is-active', $(this).val() !== '');
                var any = want.apx || want.grp || want.tbl || want.cls;
                $('#aiFReset').prop('hidden', !any);
                dt.draw();
            });

            $('#aiFReset').on('click', function() {
                $('.ai-f-select').val('').removeClass('is-active');
                want = {
                    apx: '',
                    grp: '',
                    tbl: '',
                    cls: ''
                };
                $(this).prop('hidden', true);
                dt.draw();
            });
        });
    </script>
@endonce
