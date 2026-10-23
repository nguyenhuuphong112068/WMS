{{--
| BẢNG "HOÁ CHẤT / CHẤT CHUẨN CẦN DỰ TRÙ" - tab của Dự Trù Hoá Chất / Dự Trù Chất Chuẩn, cùng
| cách dùng với tab "Danh sách vật tư cần dự trù" (MaterialEstimate/watchlistTable): mã trong
| danh mục phòng đang tồn dưới ngưỡng tối thiểu (App\Support\EstimateWatchlist), chọn nhiều
| dòng rồi Lập phiếu dự trù (modal #watchlistEstimateModal - shared/watchlistEstimate).
|
| Biến vào: $items, $wlKind ('chemical' | 'standard'), $estRoute,
|           $groups (chất chuẩn - config('standard.groups')),
|           $watchlistStatus (hoá chất - trạng thái ngưỡng PL IV theo category_id).
--}}
@php
    $wlIsChemical = $wlKind === 'chemical';
    $wlLabel = $wlIsChemical ? 'hoá chất' : 'chất chuẩn';
    $wlHead = $wlIsChemical ? 'Hoá Chất' : 'Chất Chuẩn';
    $wlGroups = $wlIsChemical ? [] : ($groups ?? config('standard.groups'));
    $wlStatus = $watchlistStatus ?? [];
    $wlNum = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
@endphp

<div class="md-toolbar">
    <div class="d-flex align-items-center flex-wrap" style="gap: 10px">
        <button type="button" class="btn btn-outline-primary btn-sm btn-wl-select-all" data-mode="all">
            <i class="far fa-check-square mr-1"></i> Chọn tất cả
        </button>

        @if (user_can('estimate_' . $wlKind . '_create'))
            <button type="button" class="btn btn-primary btn-sm btn-wl-estimate" disabled>
                <i class="fas fa-clipboard-check mr-1"></i> Lập phiếu dự trù <span class="wl-count"></span>
            </button>
        @endif
    </div>

    <div class="md-filter">
        <label for="wlProgressFilter"><i class="fas fa-filter mr-1"></i> Trạng thái</label>
        <select id="wlProgressFilter" class="form-control form-control-sm">
            <option value="open">Chưa lập phiếu</option>
            <option value="done">Đã lập phiếu</option>
            <option value="all">Tất cả</option>
        </select>
    </div>
</div>

<div class="table-responsive">
    <table id="mdWatchlistTable" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th class="text-center wl-check-col" style="width: 42px"></th>
                <th class="text-center" style="width: 55px">STT</th>
                <th style="width: 120px">Mã {{ $wlHead }}</th>
                <th style="width: 260px">{{ $wlHead }}</th>
                @unless ($wlIsChemical)
                    <th style="width: 150px">Nhóm Chuẩn</th>
                @endunless
                <th class="text-center" style="width: 80px">ĐVT</th>
                <th class="text-right" style="width: 100px">Tồn Hiện Tại</th>
                <th class="text-right" style="width: 115px">Ngưỡng Tối Thiểu</th>
                <th class="text-right" style="width: 105px">Ngưỡng Tối Đa</th>
                <th class="text-right" style="width: 105px">SL Gợi Ý</th>
                <th style="width: 220px">Tình Trạng</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php
                    // Gợi ý dự trù đủ lên ngưỡng tối đa (chưa khai tối đa thì lên ngưỡng tối thiểu)
                    $wlTarget = (float) ($item->max_stock ?: $item->min_stock);
                    $wlSuggest = max($wlTarget - (float) $item->on_hand, 0);
                    $wlItemGroups = $wlIsChemical
                        ? []
                        : array_values(array_intersect((array) json_decode($item->groups ?? '[]', true), array_keys($wlGroups)));
                    $wlThreshold = $wlIsChemical ? ($wlStatus[$item->category_id] ?? null) : null;
                    $wlBlocked = $wlThreshold && $wlThreshold->blocked;
                    $wlLocked = $item->in_progress || $wlBlocked;
                @endphp
                <tr data-category-id="{{ $item->category_id }}"
                    data-name="{{ $item->name }}"
                    data-meta="{{ implode(' · ', array_filter([$item->code, $wlIsChemical ? null : ($item->version ? 'v' . $item->version : null), $item->manufacturer_short_name ?: $item->manufacturer_name])) }}"
                    data-unit-id="{{ $item->unit_id }}"
                    data-quantity="{{ $wlSuggest > 0 ? $wlNum($wlSuggest) : '' }}"
                    data-groups="{{ json_encode($wlItemGroups) }}"
                    data-progress="{{ $item->in_progress ? 'done' : 'open' }}"
                    class="{{ $wlLocked ? 'is-wl-progress' : '' }}">
                    <td class="text-center">
                        @if ($wlBlocked)
                            <i class="fas fa-ban wl-lock text-danger" title="Đã vượt ngưỡng PL IV, không được dự trù thêm"></i>
                        @elseif ($item->in_progress)
                            {{-- Đã có phiếu chưa xong - không chọn lại được --}}
                            <i class="fas fa-lock wl-lock" title="Đã lập phiếu, chờ xử lý xong mới dự trù lại được"></i>
                        @else
                            <input type="checkbox" class="js-wl-check" value="{{ $loop->index }}">
                        @endif
                    </td>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-nowrap">
                        <div class="font-weight-bold">{{ $item->code ?: '—' }}</div>
                        @if ($wlIsChemical && $item->type)
                            <div class="md-sub">{{ $item->type }}</div>
                        @elseif (! $wlIsChemical && $item->version)
                            <div class="md-sub">v{{ $item->version }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="font-weight-bold">{{ $item->name ?: '—' }}</div>
                        @if ($item->manufacturer_short_name || $item->manufacturer_name)
                            <div class="md-sub">NSX: {{ $item->manufacturer_short_name ?: $item->manufacturer_name }}</div>
                        @endif
                        @if ($item->cas_no)
                            <div class="md-sub">CAS: {{ $item->cas_no }}</div>
                        @endif
                    </td>
                    @unless ($wlIsChemical)
                        <td>
                            @forelse ($wlItemGroups as $wlGroup)
                                <span class="badge badge-light border" title="{{ $wlGroups[$wlGroup]['name'] }}">{{ $wlGroups[$wlGroup]['short'] }}</span>
                            @empty
                                <span class="md-empty">—</span>
                            @endforelse
                        </td>
                    @endunless
                    <td class="text-center">{{ $item->unit ?: '-' }}</td>
                    <td class="text-right text-danger font-weight-bold">{{ $wlNum($item->on_hand) }}</td>
                    <td class="text-right">{{ $wlNum($item->min_stock) }}</td>
                    <td class="text-right">{{ $item->max_stock !== null ? $wlNum($item->max_stock) : '—' }}</td>
                    <td class="text-right font-weight-bold">{{ $wlSuggest > 0 ? $wlNum($wlSuggest) : '—' }}</td>
                    <td>
                        <span class="md-badge rejected">Dưới ngưỡng tồn tối thiểu</span>
                        @if ($wlBlocked)
                            <div class="md-sub mt-1">
                                <span class="badge badge-danger" title="{{ $wlThreshold->message }}">Đã vượt ngưỡng PL IV</span>
                            </div>
                        @endif
                        @foreach ($item->in_progress as $ref)
                            <div class="md-sub mt-1">
                                <a href="{{ route($estRoute . 'detail', ['id' => $ref['id']]) }}" class="md-badge wl-progress" title="Mở phiếu dự trù">
                                    <i class="fas fa-file-signature mr-1"></i>Đã lập {{ $ref['code'] }} · {{ $ref['status'] }}
                                </a>
                            </div>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<style>
    .md-filter {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .md-filter label {
        margin: 0;
        font-size: 0.83rem;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .md-filter .form-control {
        width: auto;
        min-width: 180px;
    }

    .md-badge.wl-progress {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
        text-decoration: none;
        transition: background 0.2s;
    }

    a.md-badge.wl-progress:hover {
        background: #BBF7D0;
    }

    #mdWatchlistTable tr.is-wl-progress > td {
        background: #F8FAFC;
    }

    #mdWatchlistTable .wl-lock {
        color: var(--primary-lighter);
        cursor: help;
    }

    /* Cột ô chọn: cố định hẹp, không để DataTables / trình duyệt chia thêm chỗ trống */
    #mdWatchlistTable thead th.wl-check-col,
    #mdWatchlistTable tbody td:first-child:not(.dataTables_empty) {
        width: 42px !important;
        min-width: 42px;
        max-width: 42px;
        padding-left: 4px;
        padding-right: 4px;
    }

    #mdWatchlistTable .js-wl-check {
        width: 17px;
        height: 17px;
        accent-color: var(--primary);
        cursor: pointer;
    }

    #mdWatchlistTable tr.is-wl-picked > td {
        background: var(--primary-soft);
    }

    .md-toolbar .wl-count:not(:empty) {
        display: inline-block;
        background: rgba(255, 255, 255, 0.28);
        border-radius: 999px;
        padding: 0 8px;
        margin-left: 4px;
        font-weight: 700;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if ($.fn.DataTable.isDataTable('#mdWatchlistTable')) {
            $('#mdWatchlistTable').DataTable().destroy();
        }

        var wlTable = $('#mdWatchlistTable').DataTable({
            // Giữ bề rộng khai ở <th> - autoWidth chia lại cột và làm cột ô chọn phình to
            "autoWidth": false,
            "order": [],
            "columnDefs": [{ "orderable": false, "targets": [0] }],
            "language": {
                "url": "{{ asset('dataTable/plugins/datatables/i18n/vi.json') }}",
                "emptyTable": @json('Không có ' . $wlLabel . ' nào cần dự trù')
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        /* ---------- Lọc theo trạng thái ---------- */
        var wlProgressWant = 'open';

        $.fn.dataTable.ext.search.push(function(settings, data, index) {
            if (settings.nTable.id !== 'mdWatchlistTable') return true;

            return wlProgressWant === 'all' || $(settings.aoData[index].nTr).attr('data-progress') === wlProgressWant;
        });

        $(document).on('change', '#wlProgressFilter', function() {
            wlProgressWant = this.value;
            wlTable.draw();
            wlClearPicked();
        });

        // Trình duyệt có thể giữ lại giá trị ô lọc khi tải lại trang mà không phát 'change'
        wlProgressWant = $('#wlProgressFilter').val() || 'open';
        wlTable.draw();

        /* ---------- Chọn các mục ---------- */
        // Dòng ở trang khác của DataTables vẫn nằm trong bộ nhớ nên phải quét qua API
        function wlPicked() {
            return $(wlTable.rows({ search: 'applied' }).nodes()).filter(function() {
                return $(this).find('.js-wl-check').prop('checked');
            });
        }

        function wlClearPicked() {
            $(wlTable.rows().nodes()).each(function() {
                $(this).removeClass('is-wl-picked').find('.js-wl-check').prop('checked', false);
            });

            wlSyncButtons();
        }

        function wlSyncButtons() {
            var count = wlPicked().length;
            var total = $(wlTable.rows({ search: 'applied' }).nodes()).filter(function() {
                return $(this).find('.js-wl-check').length > 0;
            }).length;
            var all = count > 0 && count === total;

            $('.wl-count').text(count ? count : '');
            $('.btn-wl-estimate').prop('disabled', count === 0);
            $('.btn-wl-select-all')
                .attr('data-mode', all ? 'none' : 'all')
                .html(all
                    ? '<i class="far fa-square mr-1"></i> Bỏ chọn tất cả'
                    : '<i class="far fa-check-square mr-1"></i> Chọn tất cả');
        }

        $(document).on('change', '.js-wl-check', function() {
            $(this).closest('tr').toggleClass('is-wl-picked', this.checked);
            wlSyncButtons();
        });

        $(document).on('click', '.btn-wl-select-all', function() {
            if ($(this).attr('data-mode') !== 'all') {
                wlClearPicked();

                return;
            }

            $(wlTable.rows({ search: 'applied' }).nodes()).each(function() {
                $(this).find('.js-wl-check').prop('checked', true);
                $(this).toggleClass('is-wl-picked', $(this).find('.js-wl-check').length > 0);
            });

            wlSyncButtons();
        });

        /* ---------- Đổ các dòng đã chọn vào modal lập phiếu ---------- */
        $(document).on('click', '.btn-wl-estimate', function() {
            var $modal = $('#watchlistEstimateModal');
            var $body = $modal.find('.wl-items-body').empty();
            var tpl = $modal.find('.wl-row-template').html();

            wlPicked().each(function(index) {
                var $tr = $(this);
                var groups = JSON.parse($tr.attr('data-groups') || '[]');

                $body.append($(tpl.replace(/__INDEX__/g, index))
                    .find('[data-field="category_id"]').val($tr.attr('data-category-id')).end()
                    .find('[data-field="display_name"]').text($tr.attr('data-name') || '—').end()
                    .find('[data-field="display_meta"]').text($tr.attr('data-meta') || '').end()
                    .find('[data-field="unit_id"]').val($tr.attr('data-unit-id') || '').end()
                    // ',' ngăn nghìn của số gợi ý phải bỏ trước khi điền vào ô số
                    .find('[data-field="amount"]').val(($tr.attr('data-quantity') || '').replace(/,/g, '')).end()
                    // Chất chuẩn chỉ thuộc đúng một nhóm thì điền sẵn nhóm chuẩn mong muốn
                    .find('[data-field="group_key"]').val(groups.length === 1 ? groups[0] : '').end());
            });

            $modal.modal('show');
        });

        wlSyncButtons();
    });
</script>
