@php
    $sgrTarget = $sgrTarget ?? 'mdTable';
    $sgrLabel = $sgrLabel ?? 'Phân nhóm chuẩn';
    $sgrList = config('standard.groups', []);
@endphp

<div class="sgr-filter">
    <label for="sgrSelect-{{ $sgrTarget }}">
        <i class="fas fa-filter mr-1 text-primary"></i> <span>{{ $sgrLabel }}</span>:
    </label>

    <select id="sgrSelect-{{ $sgrTarget }}" class="form-control sgr-select" data-target="{{ $sgrTarget }}">
        <option value="all" data-label="Tất cả">Tất cả</option>
        <option value="none" data-label="Chưa phân nhóm">Chưa phân nhóm</option>
        @foreach ($sgrList as $code => $group)
            <option value="{{ $code }}"
                data-label="{{ $group['no'] }} - {{ $group['short'] }} ({{ $group['name'] }})"
                title="{{ $group['name'] }}">
                {{ $group['no'] }} - {{ $group['short'] }} ({{ $group['name'] }})
            </option>
        @endforeach
    </select>
</div>

@once
    <style>
        .sgr-filter {
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

        .sgr-filter label {
            margin: 0;
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }

        .sgr-filter .sgr-select {
            width: auto;
            flex: 1 1 auto;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font-size: 0.84rem;
            height: 31px;
            padding: 2px 8px;
        }

        .sgr-filter .sgr-select:focus {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }

        .sgr-filter .sgr-select option:disabled {
            color: #cbd5e1;
        }

        .sgr-filter.is-filtering {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
            background: #eff6ff;
        }

        .sgr-version {
            display: inline-block;
            background: #EDE9FE;
            color: #5B21B6;
            border: 1px solid #C4B5FD;
            border-radius: 999px;
            padding: 1px 9px;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sgrState = {};

            function sgrCodesOf(tr) {
                var raw = ($(tr).attr('data-groups') || '').trim();
                return raw ? raw.split(',') : [];
            }

            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                var want = sgrState[settings.nTable.id];
                if (!want || want === 'all') return true;

                var codes = sgrCodesOf(settings.aoData[index].nTr);
                return want === 'none' ? codes.length === 0 : codes.indexOf(want) !== -1;
            });

            $('.sgr-select').each(function() {
                var id = $(this).data('target');
                if (!$.fn.dataTable.isDataTable('#' + id)) return;

                var counts = {};
                var none = 0;
                var total = 0;

                $('#' + id).DataTable().rows().nodes().to$().each(function() {
                    var codes = sgrCodesOf(this);
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

            $(document).on('change', '.sgr-select', function() {
                var id = $(this).data('target');
                sgrState[id] = $(this).val();
                $(this).closest('.sgr-filter').toggleClass('is-filtering', $(this).val() !== 'all');
                if ($.fn.dataTable.isDataTable('#' + id)) $('#' + id).DataTable().draw();
            });
        });
    </script>
@endonce
