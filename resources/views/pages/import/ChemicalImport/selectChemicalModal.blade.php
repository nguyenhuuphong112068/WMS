{{--
|--------------------------------------------------------------------------
| NHẬP HOÁ CHẤT - MODAL CHỌN HOÁ CHẤT (danh mục hoá chất phòng đang dùng)
|--------------------------------------------------------------------------
| Mở từ nút danh sách cạnh ô "Hoá Chất" của modal Nhập hoá chất (#createModal). Bấm "Chọn"
| (hoặc nhấp đúp vào dòng) thì điền hoá chất vào ô category_id rồi đóng.
| Cùng giao diện modal "Chọn Vật Tư" (lớp imp-pick ở pages/import/shared/assets).
|
| Biến vào: $categories, $categoryDefaults (tồn + ngưỡng tối đa của phòng), $classificationCodes,
|           $classificationLabels, $impNum
|
| Dòng nạp sau (data-deferred="lazy") ở lần mở modal đầu tiên - pages/shared/deferredRows.
| Thuộc tính <tr> giữ nguyên khi nạp nên BỘ LỌC chạy hết ở trình duyệt: mỗi dòng mang sẵn
| giá trị lọc ở data-f-<khoá>. Ô "Tình trạng tồn" và "Nhóm hoá chất" so theo TỪ KHOÁ (một
| dòng có thể mang nhiều giá trị) nên gắn data-match="token"; các ô còn lại so bằng.
--}}
@php
    $pickCodes = $classificationCodes ?? [];
    $pickLabels = $classificationLabels ?? [];

    $pickManufacturers = $categories
        ->map(fn($c) => $c->manufacturer_name ?: $c->manufacturer_short_name)
        ->filter()
        ->unique()
        ->sort(fn($a, $b) => strcasecmp($a, $b))
        ->values();

    $pickStorages = $categories->pluck('storage_condition_name')->filter()->unique()
        ->sort(fn($a, $b) => strcasecmp($a, $b))->values();

    $pickUnits = $categories->map(fn($c) => $c->unit_short_name ?: $c->unit_name)->filter()->unique()
        ->sort(fn($a, $b) => strcasecmp($a, $b))->values();

    // Ô lọc nhóm chỉ liệt kê những nhóm có hoá chất trong danh mục của phòng
    $pickGroups = $categories
        ->flatMap(fn($c) => $pickCodes[$c->id] ?? [])
        ->unique()
        ->sortBy(fn($code) => (int) ltrim($code, 'N'))
        ->values();

    $pickStockOptions = [
        'has' => 'Còn hàng',
        'low' => 'Dưới ngưỡng tối thiểu',
        'out' => 'Hết hàng',
        'over' => 'Vượt ngưỡng tối đa',
    ];

    // Màu chip nhóm theo quy ước chung: đỏ nhóm 9-10, cam nhóm hoá chất cấm
    $pickChipClass = function ($code) {
        $group = (int) ltrim($code, 'N');

        if (in_array($group, \App\Support\ChemicalClassification::CRITICAL_GROUPS, true)) {
            return 'critical';
        }

        return in_array($group, \App\Support\ChemicalClassification::BANNED_GROUPS, true) ? 'banned' : '';
    };
@endphp

<div class="modal fade md-modal imp-pick" id="selectChemicalModal" tabindex="-1" role="dialog" aria-hidden="true"
    data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered imp-pick-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-flask"></i> Chọn Hoá Chất
                    <span class="md-tag imp-pick-total">{{ $categories->count() }} hoá chất</span>
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
                            placeholder="Tìm theo mã, tên hoá chất, số CAS, nhà sản xuất...">
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
                        data-target="#impPickChemicalFilters" aria-expanded="false" title="Lọc theo phân loại, bảo quản, đơn vị">
                        <i class="fas fa-sliders-h"></i> Phân loại
                        <span class="imp-pick-more-count">0</span>
                    </button>

                    <button type="button" class="btn btn-outline-secondary imp-pick-btn imp-pick-reset" title="Bỏ mọi bộ lọc" disabled>
                        <i class="fas fa-eraser"></i> Bỏ lọc
                    </button>
                </div>

                <div class="collapse" id="impPickChemicalFilters">
                    <div class="imp-pick-filters">
                        <div class="imp-pick-filter">
                            <label title="Nhóm hoá chất theo NĐ 24/2026"><i class="fas fa-layer-group mr-1"></i>Nhóm Hoá Chất</label>
                            <select class="form-control imp-pick-filter-select" data-filter="cls" data-match="token" data-group="more">
                                <option value="">Tất cả</option>
                                <option value="none">Không thuộc nhóm nào</option>
                                @foreach ($pickGroups as $groupCode)
                                    @php
                                        // Nhãn gọn = phần "Phụ lục ..." trong ngoặc; nhóm ngoài NĐ 24 dùng nhãn ngắn
                                        $groupLabel = $pickLabels[$groupCode] ?? $groupCode;
                                        $groupShort = preg_match('/\((.*)\)\s*$/u', $groupLabel, $groupMatch)
                                            ? $groupMatch[1]
                                            : \App\Support\ChemicalClassification::shortLabel((int) ltrim($groupCode, 'N'));
                                    @endphp
                                    <option value="{{ $groupCode }}" title="{{ $groupLabel }}">{{ $groupCode }} - {{ $groupShort }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="imp-pick-filter">
                            <label title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)"><i class="fas fa-shield-alt mr-1"></i>Kiểm Soát Đặc Biệt</label>
                            <select class="form-control imp-pick-filter-select" data-filter="special" data-group="more">
                                <option value="">Tất cả</option>
                                <option value="yes">Có</option>
                                <option value="no">Không</option>
                            </select>
                        </div>

                        <div class="imp-pick-filter">
                            <label title="Điều kiện bảo quản"><i class="fas fa-temperature-low mr-1"></i>Bảo Quản</label>
                            <select class="form-control imp-pick-filter-select" data-filter="storage" data-group="more">
                                <option value="">Tất cả</option>
                                @foreach ($pickStorages as $storageIndex => $storageName)
                                    <option value="s{{ $storageIndex }}">{{ $storageName }}</option>
                                @endforeach
                                <option value="none">Chưa khai</option>
                            </select>
                        </div>

                        <div class="imp-pick-filter">
                            <label title="Đơn vị tính của phòng"><i class="fas fa-balance-scale mr-1"></i>Đơn Vị</label>
                            <select class="form-control imp-pick-filter-select" data-filter="unit" data-group="more">
                                <option value="">Tất cả</option>
                                @foreach ($pickUnits as $unitIndex => $unitName)
                                    <option value="u{{ $unitIndex }}">{{ $unitName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="imp-pick-wrap">
                    <table class="table table-hover table-sm imp-pick-table" id="tableSelectChemical" data-no-datatable data-deferred="lazy">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 52px">STT</th>
                                <th data-sort="code" style="width: 110px">Mã <i class="fas fa-sort imp-pick-sort-icon"></i></th>
                                <th data-sort="name" style="min-width: 210px">Hoá Chất <i class="fas fa-sort imp-pick-sort-icon"></i></th>
                                <th style="width: 110px">Số CAS</th>
                                <th data-sort="mfr" style="min-width: 160px">Nhà Sản Xuất <i class="fas fa-sort imp-pick-sort-icon"></i></th>
                                <th class="text-center" style="width: 80px">Tỉ Trọng</th>
                                <th style="min-width: 120px">Bảo Quản</th>
                                <th style="min-width: 150px">Phân Loại</th>
                                <th class="text-center" style="width: 70px">Đơn Vị</th>
                                <th data-sort="stock" data-sort-type="num" class="text-right" style="width: 120px">
                                    Tồn <i class="fas fa-sort imp-pick-sort-icon"></i>
                                </th>
                                <th style="width: 82px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @deferredRows('tableSelectChemical')
                            @foreach ($categories as $c)
                                @php
                                    $pickCls = $pickCodes[$c->id] ?? [];
                                    $pickSpecial = \App\Support\ChemicalClassification::isSpecialControl($pickCls);
                                    $pickMfr = $c->manufacturer_name ?: $c->manufacturer_short_name;
                                    $pickMfrKey = $pickMfr ? 'm' . $pickManufacturers->search($pickMfr) : '';
                                    $pickStorageKey = $c->storage_condition_name ? 's' . $pickStorages->search($c->storage_condition_name) : 'none';
                                    $pickUnit = $c->unit_short_name ?: $c->unit_name;
                                    $pickUnitKey = $pickUnit ? 'u' . $pickUnits->search($pickUnit) : '';

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
                                    data-text="{{ \App\Support\CategoryLookup::chemicalCategoryLabel($c, true) }}"
                                    data-f-mfr="{{ $pickMfrKey }}"
                                    data-f-stock="{{ implode(' ', $pickTokens) }}"
                                    data-f-cls="{{ $pickCls ? implode(' ', $pickCls) : 'none' }}"
                                    data-f-special="{{ $pickSpecial ? 'yes' : 'no' }}"
                                    data-f-storage="{{ $pickStorageKey }}"
                                    data-f-unit="{{ $pickUnitKey }}"
                                    data-s-code="{{ mb_strtolower((string) $c->code) }}"
                                    data-s-name="{{ mb_strtolower((string) $c->chem_name) }}"
                                    data-s-mfr="{{ mb_strtolower((string) $pickMfr) }}"
                                    data-s-stock="{{ $pickOnHand }}"
                                    data-search="{{ mb_strtolower(implode(' ', array_filter([
                                        $c->code,
                                        $c->chem_name,
                                        $c->cas_no,
                                        $c->manufacturer_name,
                                        $c->manufacturer_short_name,
                                    ]))) }}">
                                    <td class="text-center md-sub imp-pick-stt">{{ $loop->iteration }}</td>
                                    <td><span class="md-tag">{{ $c->code ?: '—' }}</span></td>
                                    <td>
                                        <span class="imp-pick-name">{{ $c->chem_name ?: '—' }}</span>
                                        <span class="imp-pick-current"><i class="fas fa-check mr-1"></i>Đang chọn</span>
                                    </td>
                                    <td class="md-sub">{{ $c->cas_no ?: '—' }}</td>
                                    <td>
                                        <div class="md-sub">{{ $c->manufacturer_name ?: '—' }}</div>
                                        @if ($c->manufacturer_short_name)
                                            <span class="imp-pick-mfr-short">{{ $c->manufacturer_short_name }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $c->density !== null ? $impNum($c->density) : '—' }}</td>
                                    <td class="md-sub">{{ $c->storage_condition_name ?: '—' }}</td>
                                    <td>
                                        @if ($pickCls)
                                            <div class="imp-pick-chips">
                                                @foreach ($pickCls as $code)
                                                    <span class="imp-pick-chip {{ $pickChipClass($code) }}"
                                                        title="{{ $pickLabels[$code] ?? $code }}">{{ $code }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="md-sub">—</span>
                                        @endif
                                        @if ($pickSpecial)
                                            <div class="mt-1">
                                                <span class="badge-special-control" title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">
                                                    <i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt
                                                </span>
                                            </div>
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
                                        <button type="button" class="btn btn-sm btn-primary imp-pick-select btn-select-chemical">Chọn</button>
                                    </td>
                                </tr>
                            @endforeach
                            @enddeferredRows
                        </tbody>
                        {{-- Nằm ở tfoot vì fillLazy() thay toàn bộ tbody khi dòng về --}}
                        <tfoot>
                            <tr class="imp-pick-none" style="display: none">
                                <td colspan="11">
                                    @if ($categories->isEmpty())
                                        <i class="fas fa-box-open"></i>
                                        Phòng chưa khai hoá chất nào ở tab "Hoá Chất Của Phòng" nên chưa có gì để nhập.
                                    @else
                                        <i class="fas fa-search"></i>
                                        Không tìm thấy hoá chất phù hợp với bộ lọc.
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="modal-footer justify-content-between">
                <span class="imp-pick-visible">Hiển thị <b>{{ $categories->count() }}</b> / {{ $categories->count() }} hoá chất</span>
                <button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $pick = $('#selectChemicalModal');
        var pickSort = { key: 'code', dir: 1 };

        /** Chữ thường, bỏ dấu tiếng Việt - gõ "dicloetan" vẫn ra "Dicloetan" */
        function fold(value) {
            return String(value || '').toLowerCase().normalize('NFD')
                .replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').trim();
        }

        function rows() {
            return $pick.find('.imp-pick-row');
        }

        /* Ẩn / hiện dòng theo ô tìm kiếm + các ô lọc, đánh lại STT cho những dòng đang thấy */
        function impPickRefresh() {
            var search = fold($pick.find('.imp-pick-search').val());
            var filters = [];
            var moreActive = 0;
            var visible = 0;
            var $rows = rows();

            $pick.find('.imp-pick-filter-select').each(function() {
                var active = this.value !== '';

                $(this).toggleClass('is-active', active);
                if (!active) return;

                filters.push({
                    attr: 'data-f-' + $(this).data('filter'),
                    value: this.value,
                    token: $(this).data('match') === 'token'
                });
                if ($(this).data('group') === 'more') moreActive++;
            });

            $rows.each(function() {
                var $row = $(this);
                var haystack = $row.data('fold');

                if (haystack === undefined) {
                    haystack = fold($row.attr('data-search'));
                    $row.data('fold', haystack);
                }

                var match = !search || haystack.indexOf(search) !== -1;

                for (var i = 0; match && i < filters.length; i++) {
                    var current = String($row.attr(filters[i].attr) || '');

                    match = filters[i].token
                        ? current.split(' ').indexOf(filters[i].value) !== -1
                        : current === filters[i].value;
                }

                $row.toggle(match);
                if (match) $row.find('.imp-pick-stt').text(++visible);
            });

            $pick.find('.imp-pick-none').toggle(mdDeferred.loaded($pick.find('.imp-pick-table')) && visible === 0);
            $pick.find('.imp-pick-visible').html('Hiển thị <b>' + visible + '</b> / ' + $rows.length + ' hoá chất');
            $pick.find('.imp-pick-more').toggleClass('has-active', moreActive > 0)
                .find('.imp-pick-more-count').text(moreActive);
            $pick.find('.imp-pick-reset').prop('disabled', !search && !filters.length);
        }

        /* Sắp xếp lại các dòng theo cột đang chọn */
        function impPickSort() {
            var $tbody = $pick.find('.imp-pick-table tbody');
            var $th = $pick.find('th[data-sort="' + pickSort.key + '"]');
            var numeric = $th.data('sort-type') === 'num';
            var attr = 'data-s-' + pickSort.key;

            var sorted = rows().get().sort(function(a, b) {
                var x = a.getAttribute(attr) || '';
                var y = b.getAttribute(attr) || '';
                var diff = numeric ? (parseFloat(x) || 0) - (parseFloat(y) || 0) : x.localeCompare(y, 'vi');

                return diff * pickSort.dir;
            });

            $tbody.append(sorted);

            $pick.find('th[data-sort]').removeClass('is-sorted').find('.imp-pick-sort-icon')
                .attr('class', 'fas fa-sort imp-pick-sort-icon');
            $th.addClass('is-sorted').find('.imp-pick-sort-icon')
                .attr('class', 'fas imp-pick-sort-icon ' + (pickSort.dir === 1 ? 'fa-sort-up' : 'fa-sort-down'));

            impPickRefresh();
        }

        /* Đánh dấu dòng đang chọn ở ô Hoá chất của modal Nhập rồi cuộn tới dòng đó */
        function impPickMarkCurrent(scroll) {
            var current = String($('#createModal select[name="category_id"]').val() || '');

            rows().each(function() {
                $(this).toggleClass('is-current', current !== '' && String($(this).data('id')) === current);
            });

            var $current = rows().filter('.is-current:visible');
            if (scroll && $current.length) {
                var $wrap = $pick.find('.imp-pick-wrap');
                $wrap.scrollTop($current.position().top + $wrap.scrollTop() - $wrap.height() / 2);
            }
        }

        function impPickChoose($row) {
            var id = $row.data('id');
            var $category = $('#createModal select[name="category_id"]');

            // Ô hoá chất tìm qua AJAX: dựng option từ nhãn của dòng trước khi chọn
            wmsEnsureOption($category, id, $row.attr('data-text'));
            $category.val(String(id)).trigger('change');
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

        // Gõ xong mà chỉ còn đúng một hoá chất thì Enter là chọn luôn
        $pick.on('keydown', '.imp-pick-search', function(e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            var $visible = rows().filter(':visible');
            if ($visible.length === 1) impPickChoose($visible);
        });

        $pick.on('click', '.btn-select-chemical', function() {
            impPickChoose($(this).closest('.imp-pick-row'));
        });

        $pick.on('dblclick', '.imp-pick-row', function() {
            impPickChoose($(this));
        });

        $pick.on('show.bs.modal', function(e) {
            if (e.target !== this) return;

            impPickMarkCurrent(false);
        });

        $pick.on('shown.bs.modal', function(e) {
            if (e.target !== this) return;

            // Lớp phủ của bảng chọn phải che luôn modal Nhập hoá chất phía dưới
            $('.modal-backdrop').last().css('z-index', 1055);
            $pick.find('.imp-pick-search').trigger('focus');

            // Danh mục hoá chất của phòng chỉ lấy ở lần mở đầu - pages/shared/deferredRows
            mdDeferred.fillLazy($pick.find('.imp-pick-table'), function() {
                impPickSort();
                impPickMarkCurrent(true);
            }, 'Đang tải danh mục hoá chất của phòng...');
        });

        $pick.on('hidden.bs.modal', function(e) {
            if (e.target !== this) return;

            // Bootstrap gỡ modal-open khỏi body khi đóng modal trên cùng, modal bên dưới sẽ mất cuộn
            if ($('.modal.show').length) $('body').addClass('modal-open');
        });
    });
</script>
