{{--
|--------------------------------------------------------------------------
| NHẬP VẬT TƯ - MODAL CHỌN VẬT TƯ (danh mục vật tư phòng đang dùng)
|--------------------------------------------------------------------------
| Mở từ nút danh sách cạnh ô "Vật tư" của modal Nhập vật tư (#createModal). Bấm "Chọn"
| (hoặc nhấp đúp vào dòng) thì điền vật tư vào ô category_id rồi đóng.
|
| Biến vào: $categories, $categoryDefaults (tồn + ngưỡng của phòng, cùng nguồn với cảnh
|           báo vượt ngưỡng tối đa), $deptClassifications, $impNum
|
| BỘ LỌC chạy hết ở trình duyệt: mỗi dòng mang sẵn giá trị lọc ở data-f-<khoá>, JS
| impPickRefresh() ẩn dòng không khớp. Ô "Tình trạng tồn" so theo TỪ KHOÁ (một dòng có thể
| vừa "has" vừa "low") nên gắn data-match="token"; các ô còn lại so bằng.
|
| Không dùng DataTables: bảng cuộn trong khung + tiêu đề dính, sắp xếp bằng JS riêng, để
| bộ lọc và số đếm luôn khớp với đúng những dòng đang thấy.
--}}
@php
    $pickCriteria = \App\Support\MaterialClassification::CRITERIA;

    // FontAwesome trong dự án là bản 5 - icon khai ở CRITERIA dùng tên bản 6 nên không hiện
    $pickIcons = [
        'price' => 'fas fa-tags',
        'importance' => 'fas fa-star',
        'dangerous' => 'fas fa-exclamation-triangle',
        'purchasing' => 'fas fa-shopping-cart',
        'supply' => 'fas fa-hand-holding',
        'qa_calibration' => 'fas fa-ruler-combined',
        'dept_class' => 'fas fa-layer-group',
    ];

    // Ô lọc phân loại theo đúng thứ tự trên form danh mục công ty: [khoá => [nhãn, lựa chọn]]
    $pickFilters = collect(\App\Support\MaterialClassification::formGroups())
        ->mapWithKeys(function ($group) {
            $key = $group['name'] === 'purchasing_department'
                ? 'purchasing'
                : str_replace(['classification[', ']'], '', $group['name']);

            return [$key => [$group['label'], $group['options']]];
        })
        ->put('dept_class', [
            'Phân Loại Phòng',
            ($deptClassifications ?? collect())->pluck('name', 'id')->all() + ['none' => 'Chưa phân loại'],
        ]);

    $pickManufacturers = $categories
        ->map(fn($c) => $c->manufacturer_name ?: $c->manufacturer_short_name)
        ->filter()
        ->unique()
        ->sort(fn($a, $b) => strcasecmp($a, $b))
        ->values();

    $pickStockOptions = [
        'has' => 'Còn hàng',
        'low' => 'Dưới ngưỡng tối thiểu',
        'out' => 'Hết hàng',
        'over' => 'Vượt ngưỡng tối đa',
    ];
@endphp

<div class="modal fade md-modal imp-pick" id="selectMaterialModal" tabindex="-1" role="dialog" aria-hidden="true"
    data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered imp-pick-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-th-list"></i> Chọn Vật Tư
                    <span class="md-tag imp-pick-total">{{ $categories->count() }} vật tư</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="imp-pick-toolbar">
                    <div class="input-group imp-pick-search-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="search" class="form-control imp-pick-search" autocomplete="off"
                            placeholder="Tìm theo mã, tên vật tư, nhà sản xuất, quy cách...">
                        <div class="input-group-append">
                            <button type="button" class="btn imp-pick-search-clear" title="Xoá nội dung tìm kiếm">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <select class="form-control imp-pick-filter-select imp-pick-quick" data-filter="mfr"
                        title="Lọc theo nhà sản xuất">
                        <option value="">Nhà sản xuất: Tất cả</option>
                        @foreach ($pickManufacturers as $mfrIndex => $mfrName)
                            <option value="m{{ $mfrIndex }}">{{ $mfrName }}</option>
                        @endforeach
                    </select>

                    <select class="form-control imp-pick-filter-select imp-pick-quick" data-filter="stock"
                        data-match="token" title="Lọc theo tình trạng tồn của phòng">
                        <option value="">Tình trạng tồn: Tất cả</option>
                        @foreach ($pickStockOptions as $stockKey => $stockLabel)
                            <option value="{{ $stockKey }}">{{ $stockLabel }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="btn btn-outline-primary imp-pick-btn imp-pick-more" data-toggle="collapse"
                        data-target="#impPickMaterialFilters" aria-expanded="false" title="Lọc theo phân loại vật tư">
                        <i class="fas fa-sliders-h"></i> Phân loại
                        <span class="imp-pick-more-count">0</span>
                    </button>

                    <button type="button" class="btn btn-outline-secondary imp-pick-btn imp-pick-reset" title="Bỏ mọi bộ lọc" disabled>
                        <i class="fas fa-eraser"></i> Bỏ lọc
                    </button>
                </div>

                <div class="collapse" id="impPickMaterialFilters">
                    <div class="imp-pick-filters">
                        @foreach ($pickFilters as $filterKey => [$filterLabel, $filterOptions])
                            <div class="imp-pick-filter">
                                <label title="{{ $filterLabel }}">
                                    <i class="{{ $pickIcons[$filterKey] ?? 'fas fa-tag' }} mr-1"></i>{{ $filterLabel }}
                                </label>
                                <select class="form-control imp-pick-filter-select" data-filter="{{ $filterKey }}" data-group="more">
                                    <option value="">Tất cả</option>
                                    @foreach ($filterOptions as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="imp-pick-wrap">
                    <table class="table table-hover table-sm imp-pick-table" data-no-datatable>
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 52px">STT</th>
                                <th data-sort="code" style="width: 120px">Mã <i class="fas fa-sort imp-pick-sort-icon"></i></th>
                                <th data-sort="name" style="min-width: 190px">Vật Tư <i class="fas fa-sort imp-pick-sort-icon"></i></th>
                                <th data-sort="mfr" style="min-width: 150px">Nhà Sản Xuất <i class="fas fa-sort imp-pick-sort-icon"></i></th>
                                <th style="min-width: 130px">Quy Cách</th>
                                <th style="min-width: 230px">Phân Loại</th>
                                <th class="text-center" style="width: 76px">Đơn Vị</th>
                                <th data-sort="stock" data-sort-type="num" class="text-right" style="width: 130px">
                                    Tồn <i class="fas fa-sort imp-pick-sort-icon"></i>
                                </th>
                                <th style="width: 82px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $c)
                                @php
                                    $pickClass = \App\Support\MaterialClassification::decode($c->classification);
                                    $pickChips = \App\Support\MaterialClassification::chips($c->classification);
                                    $pickMfr = $c->manufacturer_name ?: $c->manufacturer_short_name;
                                    $pickMfrKey = $pickMfr ? 'm' . $pickManufacturers->search($pickMfr) : '';
                                    $pickUnit = $c->unit_short_name ?: $c->unit_name;

                                    $pickDefault = $categoryDefaults[$c->id] ?? [];
                                    $pickOnHand = (float) ($pickDefault['on_hand'] ?? 0);
                                    $pickMin = $c->min_stock !== null ? (float) $c->min_stock : null;
                                    $pickMax = isset($pickDefault['max_stock']) ? (float) $pickDefault['max_stock'] : null;

                                    // Từ khoá cho ô lọc tồn + màu hiển thị (ưu tiên: hết > dưới tối thiểu > vượt tối đa)
                                    $pickTokens = [$pickOnHand > 0 ? 'has' : 'out'];
                                    if ($pickMin !== null && $pickOnHand < $pickMin) {
                                        $pickTokens[] = 'low';
                                    }
                                    if ($pickMax !== null && $pickOnHand > $pickMax) {
                                        $pickTokens[] = 'over';
                                    }
                                    $pickLevel = $pickOnHand <= 0
                                        ? 'out'
                                        : (in_array('low', $pickTokens) ? 'low' : (in_array('over', $pickTokens) ? 'over' : 'ok'));

                                    $pickStockTitle = 'Tồn hiện tại: ' . $impNum($pickOnHand) . ' ' . $pickUnit
                                        . ($pickMin !== null ? ' · Tối thiểu: ' . $impNum($pickMin) : '')
                                        . ($pickMax !== null ? ' · Tối đa: ' . $impNum($pickMax) : '');
                                @endphp
                                <tr class="imp-pick-row" data-id="{{ $c->id }}"
                                    data-f-mfr="{{ $pickMfrKey }}"
                                    data-f-stock="{{ implode(' ', $pickTokens) }}"
                                    @foreach (array_keys($pickCriteria) as $criterionKey)
                                        data-f-{{ str_replace('_', '-', $criterionKey) }}="{{ $pickClass[$criterionKey] ?? '' }}"
                                    @endforeach
                                    data-f-purchasing="{{ $c->purchasing_department ?? '' }}"
                                    data-f-dept-class="{{ $c->classification_id ?: 'none' }}"
                                    data-s-code="{{ mb_strtolower((string) $c->code) }}"
                                    data-s-name="{{ mb_strtolower((string) $c->material_name) }}"
                                    data-s-mfr="{{ mb_strtolower((string) $pickMfr) }}"
                                    data-s-stock="{{ $pickOnHand }}"
                                    data-search="{{ mb_strtolower(implode(' ', array_filter([
                                        $c->code,
                                        $c->material_name,
                                        $c->manufacturer_name,
                                        $c->manufacturer_short_name,
                                        $c->technical_specification,
                                    ]))) }}">
                                    <td class="text-center md-sub imp-pick-stt">{{ $loop->iteration }}</td>
                                    <td><span class="md-tag">{{ $c->code ?: '—' }}</span></td>
                                    <td>
                                        <span class="imp-pick-name">{{ $c->material_name ?: '—' }}</span>
                                        <span class="imp-pick-current"><i class="fas fa-check mr-1"></i>Đang chọn</span>
                                    </td>
                                    <td>
                                        <div class="md-sub">{{ $c->manufacturer_name ?: '—' }}</div>
                                        @if ($c->manufacturer_short_name)
                                            <span class="imp-pick-mfr-short">{{ $c->manufacturer_short_name }}</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $c->technical_specification ?: '—' }}</td>
                                    <td>
                                        @if ($pickChips)
                                            <div class="imp-pick-chips">
                                                @foreach ($pickChips as $chip)
                                                    <span class="imp-pick-chip {{ $chip['class'] }}"
                                                        title="{{ $chip['label'] }}">{{ $chip['short'] }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="md-sub">Chưa phân loại</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $pickUnit ?: '—' }}</td>
                                    <td class="text-right">
                                        <span class="imp-pick-stock is-{{ $pickLevel }}" title="{{ $pickStockTitle }}">
                                            {{ $impNum($pickOnHand) }}
                                        </span>
                                        @if ($pickMin !== null)
                                            <div class="imp-pick-stock-sub">Tối thiểu {{ $impNum($pickMin) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary imp-pick-select btn-select-material"
                                            data-id="{{ $c->id }}">Chọn</button>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="imp-pick-none" @if ($categories->isNotEmpty()) style="display: none" @endif>
                                <td colspan="9">
                                    @if ($categories->isEmpty())
                                        <i class="fas fa-box-open"></i>
                                        Phòng chưa khai vật tư nào ở tab "Vật Tư Của Phòng" nên chưa có gì để nhập.
                                    @else
                                        <i class="fas fa-search"></i>
                                        Không tìm thấy vật tư phù hợp với bộ lọc.
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer justify-content-between">
                <span class="imp-pick-visible">Hiển thị <b>{{ $categories->count() }}</b> / {{ $categories->count() }} vật tư</span>
                <button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $pick = $('#selectMaterialModal');
        var pickTotal = $pick.find('.imp-pick-row').length;
        var pickSort = { key: 'name', dir: 1 };

        /* Ẩn / hiện dòng theo ô tìm kiếm + các ô lọc, đánh lại STT cho những dòng đang thấy */
        function impPickRefresh() {
            var search = ($pick.find('.imp-pick-search').val() || '').toLowerCase().trim();
            var filters = [];
            var moreActive = 0;
            var visible = 0;

            $pick.find('.imp-pick-filter-select').each(function() {
                var active = this.value !== '';

                $(this).toggleClass('is-active', active);
                if (!active) return;

                filters.push({
                    attr: 'data-f-' + String($(this).data('filter')).replace(/_/g, '-'),
                    value: this.value,
                    token: $(this).data('match') === 'token'
                });
                if ($(this).data('group') === 'more') moreActive++;
            });

            $pick.find('.imp-pick-row').each(function() {
                var $row = $(this);
                var match = !search || String($row.data('search')).indexOf(search) !== -1;

                for (var i = 0; match && i < filters.length; i++) {
                    var current = String($row.attr(filters[i].attr) || '');

                    match = filters[i].token
                        ? current.split(' ').indexOf(filters[i].value) !== -1
                        : current === filters[i].value;
                }

                $row.toggle(match);
                if (match) $row.find('.imp-pick-stt').text(++visible);
            });

            $pick.find('.imp-pick-none').toggle(visible === 0);
            $pick.find('.imp-pick-visible').html('Hiển thị <b>' + visible + '</b> / ' + pickTotal + ' vật tư');
            $pick.find('.imp-pick-more').toggleClass('has-active', moreActive > 0)
                .find('.imp-pick-more-count').text(moreActive);
            $pick.find('.imp-pick-reset').prop('disabled', !search && !filters.length);
        }

        /* Sắp xếp lại các dòng theo cột đang chọn; dòng "không tìm thấy" luôn nằm cuối */
        function impPickSort() {
            var $tbody = $pick.find('.imp-pick-table tbody');
            var $th = $pick.find('th[data-sort="' + pickSort.key + '"]');
            var numeric = $th.data('sort-type') === 'num';
            var attr = 'data-s-' + pickSort.key;

            var rows = $tbody.children('.imp-pick-row').get().sort(function(a, b) {
                var x = a.getAttribute(attr) || '';
                var y = b.getAttribute(attr) || '';
                var diff = numeric ? (parseFloat(x) || 0) - (parseFloat(y) || 0) : x.localeCompare(y, 'vi');

                return diff * pickSort.dir;
            });

            $tbody.prepend(rows);

            $pick.find('th[data-sort]').removeClass('is-sorted').find('.imp-pick-sort-icon')
                .attr('class', 'fas fa-sort imp-pick-sort-icon');
            $th.addClass('is-sorted').find('.imp-pick-sort-icon')
                .attr('class', 'fas imp-pick-sort-icon ' + (pickSort.dir === 1 ? 'fa-sort-up' : 'fa-sort-down'));

            impPickRefresh();
        }

        function impPickChoose(id) {
            $('#createModal select[name="category_id"]').val(id).trigger('change');
            $pick.modal('hide');
        }

        $pick.on('click', 'th[data-sort]', function() {
            var key = $(this).data('sort');

            pickSort = { key: key, dir: pickSort.key === key ? -pickSort.dir : 1 };
            impPickSort();
        });

        $pick.on('input search', '.imp-pick-search', impPickRefresh);
        $pick.on('change', '.imp-pick-filter-select', impPickRefresh);

        $pick.on('click', '.imp-pick-search-clear', function() {
            $pick.find('.imp-pick-search').val('').trigger('focus');
            impPickRefresh();
        });

        $pick.on('click', '.imp-pick-reset', function() {
            $pick.find('.imp-pick-search').val('');
            $pick.find('.imp-pick-filter-select').val('');
            impPickRefresh();
        });

        // Gõ xong mà chỉ còn đúng một vật tư thì Enter là chọn luôn
        $pick.on('keydown', '.imp-pick-search', function(e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            var $visible = $pick.find('.imp-pick-row:visible');
            if ($visible.length === 1) impPickChoose($visible.data('id'));
        });

        $pick.on('click', '.btn-select-material', function() {
            impPickChoose($(this).data('id'));
        });

        $pick.on('dblclick', '.imp-pick-row', function() {
            impPickChoose($(this).data('id'));
        });

        $pick.on('show.bs.modal', function() {
            var current = String($('#createModal select[name="category_id"]').val() || '');

            $pick.find('.imp-pick-row').each(function() {
                $(this).toggleClass('is-current', current !== '' && String($(this).data('id')) === current);
            });
        });

        $pick.on('shown.bs.modal', function() {
            // Lớp phủ của bảng chọn phải che luôn modal Nhập vật tư phía dưới
            $('.modal-backdrop').last().css('z-index', 1055);
            $pick.find('.imp-pick-search').trigger('focus');

            var $current = $pick.find('.imp-pick-row.is-current:visible');
            if ($current.length) {
                var $wrap = $pick.find('.imp-pick-wrap');
                $wrap.scrollTop($current.position().top + $wrap.scrollTop() - $wrap.height() / 2);
            }
        });

        $pick.on('hidden.bs.modal', function() {
            // Bootstrap gỡ modal-open khỏi body khi đóng modal trên cùng, modal bên dưới sẽ mất cuộn
            if ($('.modal.show').length) $('body').addClass('modal-open');
        });

        impPickSort();
    });
</script>
