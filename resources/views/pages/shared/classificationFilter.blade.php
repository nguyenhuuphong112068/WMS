@php
    $clsTarget = $clsTarget ?? 'mdTable';
    $clsLabel = $clsLabel ?? 'Phụ lục / Nhóm hoá chất';
    $clsList = config('chemical.classifications', []);
@endphp

<div class="cls-filter">
    <label for="clsSelect-{{ $clsTarget }}">
        <i class="fas fa-filter mr-1 text-primary"></i> <span>{{ $clsLabel }}</span>:
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
</div>

@once
    <style>
        .cls-filter {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 8px;
            padding: 5px 10px;
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            transition: box-shadow 0.2s, border-color 0.2s;
        }

        .cls-filter label {
            margin: 0;
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }

        .cls-filter .cls-select {
            width: auto;
            flex: 1 1 auto;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font-size: 0.84rem;
            height: 31px;
            padding: 2px 8px;
        }

        .cls-filter .cls-select:focus {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }

        .cls-filter .cls-select option:disabled {
            color: #cbd5e1;
        }

        .cls-filter.is-filtering {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
            background: #eff6ff;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var clsState = {};

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

            $('.cls-select').each(function() {
                var id = $(this).data('target');
                if (!$.fn.dataTable.isDataTable('#' + id)) return;

                var counts = {};
                var none = 0;
                var total = 0;

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
                    if (count === 0 && value !== 'all') $(this).prop('disabled', true);
                });
            });

            $(document).on('change', '.cls-select', function() {
                var id = $(this).data('target');
                clsState[id] = $(this).val();
                $(this).closest('.cls-filter').toggleClass('is-filtering', $(this).val() !== 'all');
                if ($.fn.dataTable.isDataTable('#' + id)) $('#' + id).DataTable().draw();
            });
        });
    </script>
@endonce
