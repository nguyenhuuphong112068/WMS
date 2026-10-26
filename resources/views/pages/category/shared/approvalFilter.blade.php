{{--
| Bộ lọc Trạng thái duyệt cho bảng danh mục công ty (HC / Chuẩn / Vật tư).
| Biến vào: $apfTarget (id bảng, mặc định mdTable).
| Dòng cần data-approval = pending | approved | rejected | locked (khoá thắng duyệt).
| Trang chủ mở sẵn bộ lọc qua ?app_status=pending (thẻ "Danh mục chờ duyệt").
--}}
@php
    $apfTarget = $apfTarget ?? 'mdTable';
    $apfOptions = [
        'all' => 'Tất cả',
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        'locked' => 'Đã khoá',
    ];
    $apfSelected = request('app_status');
    $apfSelected = array_key_exists($apfSelected, $apfOptions) ? $apfSelected : 'all';
@endphp

<div class="apf-filter md-tablebar-item {{ $apfSelected !== 'all' ? 'is-filtering' : '' }}">
    <label for="apfSelect-{{ $apfTarget }}">
        <i class="fas fa-check-circle mr-1 text-primary"></i> <span>Trạng thái duyệt</span>:
    </label>

    <select id="apfSelect-{{ $apfTarget }}" class="form-control apf-select" data-target="{{ $apfTarget }}">
        @foreach ($apfOptions as $value => $label)
            <option value="{{ $value }}" data-label="{{ $label }}" @selected($apfSelected === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

@once
    <style>
        .apf-filter {
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

        .apf-filter label {
            margin: 0;
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }

        .apf-filter .apf-select {
            width: auto;
            flex: 1 1 auto;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font-size: 0.84rem;
            height: 31px;
            padding: 2px 8px;
        }

        .apf-filter .apf-select:focus {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }

        .apf-filter .apf-select option:disabled {
            color: #cbd5e1;
        }

        .apf-filter.is-filtering {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
            background: #eff6ff;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var apfState = {};

            $('.apf-select').each(function() {
                apfState[$(this).data('target')] = $(this).val();
            });

            // Bảng data-deferred: dòng chưa hiện chưa có <tr> nên đọc từ JSON qua mdDeferred.rowAttr
            function apfStateOf(rowData, tr) {
                var raw = window.mdDeferred ? mdDeferred.rowAttr(rowData, tr, 'approval') : $(tr).attr('data-approval');
                return String(raw || '').trim();
            }

            $.fn.dataTable.ext.search.push(function(settings, data, index, rowData) {
                var want = apfState[settings.nTable.id];
                if (!want || want === 'all') return true;

                return apfStateOf(rowData, settings.aoData[index].nTr) === want;
            });

            function apfRefreshCounts(id) {
                if (!$.fn.dataTable.isDataTable('#' + id)) return;

                var counts = {};
                var total = 0;

                $('#' + id).DataTable().rows().every(function() {
                    var state = apfStateOf(this.data(), this.node());
                    total++;
                    counts[state] = (counts[state] || 0) + 1;
                });

                $('.apf-select[data-target="' + id + '"]').find('option').each(function() {
                    var value = $(this).val();
                    var count = value === 'all' ? total : (counts[value] || 0);
                    $(this).text($(this).data('label') + ' (' + count + ')');
                    $(this).prop('disabled', count === 0 && value !== 'all' && !$(this).is(':selected'));
                });
            }

            function apfRedraw(id) {
                if ($.fn.dataTable.isDataTable('#' + id)) $('#' + id).DataTable().draw();
            }

            // Bảng dựng sau DOMContentLoaded một nhịp -> lọc theo URL rồi đếm khi bảng sẵn sàng
            setTimeout(function() {
                $('.apf-select').each(function() {
                    var id = $(this).data('target');
                    apfRefreshCounts(id);
                    if (apfState[id] !== 'all') apfRedraw(id);
                });
            }, 60);

            $(document).on('md:rows-loaded', 'table', function() {
                if ($('.apf-select[data-target="' + this.id + '"]').length) apfRefreshCounts(this.id);
            });

            $(document).on('change', '.apf-select', function() {
                var id = $(this).data('target');
                apfState[id] = $(this).val();
                $(this).closest('.apf-filter').toggleClass('is-filtering', $(this).val() !== 'all');
                apfRedraw(id);
            });
        });
    </script>
@endonce
