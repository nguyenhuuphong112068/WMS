{{--
| Modal "Thêm ... Dự Trù" dạng bảng của Dự Trù Hoá Chất và Dự Trù Chất Chuẩn: mỗi dòng là
| một mặt hàng, các tháng cần dùng là cột chung ở tiêu đề bảng (mặc định 3 tháng liên tiếp
| từ tháng dự trù của phiếu). Lưu một lần được nhiều mặt hàng - xem storeItem() của
| ChemicalEstimateController / StandardEstimateController. Cùng bố cục với modal Vật Tư
| (pages/estimate/MaterialEstimate/itemCreate.blade.php), dùng chung CSS batchItemStyle.
|
| Mặt hàng TRONG danh mục chỉ vào bảng qua khung "Chọn từ danh mục phòng" (tên hiện cố định);
| nút "Thêm dòng ngoài danh mục" tạo dòng gõ tên tự do.
|
| Biến vào: $list, $units, $categories (danh mục công ty - lấy tên khi render lại old input),
|           $deptCategories (danh mục của phòng - khung chọn), $maxStock, $estRoute và
|           $emi = [
|             'kind'               => 'chemical' | 'standard',
|             'title', 'icon', 'saveLabel',
|             'listField'          => tên ô id phiếu gửi lên controller,
|             'itemLabel'          => 'hoá chất' | 'chất chuẩn' (chữ thường, dùng trong câu),
|             'itemHead'           => 'Hoá Chất' | 'Chất Chuẩn' (tiêu đề cột),
|             'nameField'          => cột tên của mặt hàng ngoài danh mục + thuộc tính tên của dòng danh mục,
|             'techPlaceholder', 'purposePlaceholder',
|             'groups'             => config('standard.groups') hoặc null (có thì hiện cột Nhóm Chuẩn),
|             'thresholdUrl'       => route kiểm tra ngưỡng PL IV hoặc null (chỉ hoá chất),
|             'status'             => [category_id => trạng thái ngưỡng PL IV (tồn hiện tại + đang dự
|                                     trù chưa hoàn thành) - App\Support\ChemicalEstimateThreshold::
|                                     categoryStatus()]; mã có blocked = true bị khoá ở khung chọn,
|             'factorUnits'        => [category_id => {unit_id, unit, group, base}] đơn vị danh mục của
|                                     phòng cho hoá chất nhóm 9 / 10 (null = màn không có hệ số quy đổi),
|           ]
--}}
@php
    $bag = $errors->getBag('itemCreateErrors');
    $nameField = $emi['nameField'];
    $emiGroups = $emi['groups'] ?? null;
    $emiStatus = $emi['status'] ?? [];
    $emiFactorUnits = $emi['factorUnits'] ?? null;
    $isChemical = $emi['kind'] === 'chemical';

    $estStart = \Carbon\Carbon::createFromDate($list->year, $list->month, 1);
    $emiDefaultPeriods = collect(range(0, 2))
        ->map(fn($step) => $estStart->copy()->addMonths($step)->format('Y-m'))
        ->all();

    $emiPeriods = array_values((array) old('periods', $emiDefaultPeriods)) ?: $emiDefaultPeriods;
    $emiRows = array_values((array) old('items', []));

    // Số cột cố định (ngoài các cột tháng): #, tên, [nhóm chuẩn], TTKT, mục đích, ngày giao, đơn vị, thao tác
    $emiFixedCols = $emiGroups ? 8 : 7;
    $emiBaseWidth = ($emiGroups ? 1250 : 1100) + (is_array($emiFactorUnits) ? 50 : 0);

    // Tên hiển thị của dòng danh mục: ưu tiên danh mục phòng, thiếu thì lấy danh mục chung
    $emiLabel = fn($c) => [
        'name' => $c->{$nameField} ?? '',
        'meta' => implode(
            ' · ',
            array_filter([
                $c->code ?? null,
                isset($c->version) ? 'v' . $c->version : null,
                $c->manufacturer_short_name ?? ($c->manufacturer_name ?? null),
                !empty($c->cas_no) ? 'CAS ' . $c->cas_no : null,
            ]),
        ),
    ];
    $catLabels = collect($categories ?? [])
        ->keyBy('id')
        ->map($emiLabel)
        ->replace(
            collect($deptCategories ?? [])
                ->keyBy('id')
                ->map($emiLabel),
        )
        ->mapWithKeys(fn($v, $k) => [(string) $k => $v])
        ->all();

    // Gom lỗi thành danh sách "Dòng N: ..." để người dùng biết dòng nào sai
    $emiErrors = collect($bag->messages())
        ->map(function ($messages, $key) {
            if (preg_match('/^items\.(\d+)\./', $key, $m)) {
                return 'Dòng ' . ($m[1] + 1) . ': ' . $messages[0];
            }
            if (preg_match('/^periods\.(\d+)/', $key, $m)) {
                return 'Cột tháng ' . ($m[1] + 1) . ': ' . $messages[0];
            }
            return $messages[0];
        })
        ->unique()
        ->values();

    $pickNum = fn($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 4, ',', '.'), '0'), ',');
    $levelLabel = ['exceeded' => 'Vượt ngưỡng', 'warn' => 'Sắp chạm ngưỡng', 'ok' => 'Trong ngưỡng'];
    $levelBadge = ['exceeded' => 'badge-danger', 'warn' => 'badge-warning', 'ok' => 'badge-light border'];
    $rowVars = ['catLabels' => $catLabels, 'emi' => $emi, 'bag' => $bag, 'units' => $units];
@endphp

@include('pages.estimate.shared.batchItemStyle')

<div class="modal fade md-modal" id="itemCreateModal" tabindex="-1" role="dialog"
    data-default-periods="{{ json_encode($emiDefaultPeriods) }}"
    data-max-stock="{{ json_encode($maxStock ?? [], JSON_UNESCAPED_UNICODE) }}"
    data-threshold-url="{{ $emi['thresholdUrl'] ?? '' }}"
    data-factor-units="{{ json_encode((object) ($emiFactorUnits ?? []), JSON_UNESCAPED_UNICODE) }}"
    data-fixed-cols="{{ $emiFixedCols }}" data-base-width="{{ $emiBaseWidth }}"
    data-item-label="{{ $emi['itemLabel'] }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $emi['icon'] }}"></i> {{ $emi['title'] }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($estRoute . 'storeItem') }}" method="POST" id="emiForm">
                @csrf
                <input type="hidden" name="{{ $emi['listField'] }}" value="{{ $list->id }}">

                <div class="modal-body">
                    <div class="alert alert-danger emi-errors"
                        @if ($emiErrors->isEmpty()) style="display:none" @endif>
                        <ul>
                            @foreach ($emiErrors as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="emi-toolbar">
                        <div>
                            <button type="button" class="btn btn-sm btn-primary btn-emi-picker-toggle">
                                <i class="fas fa-th-list mr-1"></i> Chọn từ danh mục phòng
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning btn-emi-add"
                                title="Thêm dòng cho {{ $emi['itemLabel'] }} chưa có trong danh mục">
                                <i class="fas fa-plus mr-1"></i> Thêm dòng ngoài danh mục
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-emi-period-add">
                                <i class="fas fa-calendar-plus mr-1"></i> Thêm tháng
                            </button>
                        </div>
                        <span class="emi-count"></span>
                    </div>

                    {{-- Khung chọn nhiều mặt hàng từ danh mục của phòng --}}
                    <div class="emi-picker" style="display:none">
                        <div class="emi-picker-head">
                            <div class="emi-picker-title"><i class="fas fa-th-list mr-1"></i> Danh Mục
                                {{ $emi['itemHead'] }} Của Phòng</div>
                            <input type="text" class="form-control form-control-sm emi-picker-search"
                                placeholder="Tìm theo mã, tên, hãng{{ $isChemical ? ', số CAS' : ', nhóm' }}...">
                            <label class="emi-picker-low mb-0">
                                <input type="checkbox" class="emi-picker-low-only"> Chỉ {{ $emi['itemLabel'] }} dưới
                                tồn tối thiểu
                            </label>
                            <button type="button" class="close btn-emi-picker-toggle" title="Đóng">&times;</button>
                        </div>

                        <div class="emi-picker-scroll">
                            <table class="table table-sm table-hover emi-picker-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:40px">
                                            <input type="checkbox" class="emi-pick-all"
                                                title="Chọn tất cả dòng đang hiện">
                                        </th>
                                        <th style="width:110px">Mã</th>
                                        <th>Tên {{ $emi['itemHead'] }}</th>
                                        @if ($emiGroups)
                                            <th style="width:70px">Version</th>
                                            <th style="width:150px">Nhóm Chuẩn</th>
                                        @endif
                                        <th style="width:140px">Hãng</th>
                                        @if ($isChemical)
                                            <th style="width:150px">Số CAS</th>
                                        @endif
                                        <th style="width:80px">Đơn Vị</th>
                                        <th class="text-right" style="width:100px">Tồn Hiện Tại</th>
                                        <th class="text-right" style="width:100px">Tồn Tối Thiểu</th>
                                        <th class="text-right" style="width:100px">Tồn Tối Đa</th>
                                        @if ($isChemical)
                                            <th style="width:200px">Ngưỡng PL IV</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($deptCategories ?? [] as $deptCategory)
                                        @php
                                            $pickOnHand = (float) ($maxStock[$deptCategory->id]['on_hand'] ?? 0);
                                            $pickLow =
                                                $deptCategory->min_stock !== null &&
                                                $pickOnHand < (float) $deptCategory->min_stock;
                                            $pickGroups = $emiGroups
                                                ? array_values(
                                                    array_intersect(
                                                        (array) json_decode($deptCategory->groups ?? '[]', true),
                                                        array_keys($emiGroups),
                                                    ),
                                                )
                                                : [];
                                            $pickSearch = \Illuminate\Support\Str::lower(
                                                \Illuminate\Support\Str::ascii(
                                                    implode(' ', [
                                                        $deptCategory->code,
                                                        $deptCategory->{$nameField},
                                                        $deptCategory->manufacturer_name,
                                                        $deptCategory->manufacturer_short_name,
                                                        $deptCategory->cas_no ?? '',
                                                        implode(
                                                            ' ',
                                                            array_map(fn($g) => $emiGroups[$g]['short'], $pickGroups),
                                                        ),
                                                    ]),
                                                ),
                                            );
                                            // Tồn hiện tại + đang dự trù chưa hoàn thành đã chạm ngưỡng PL IV -> không cho dự trù thêm
                                            $pickStatus = $emiStatus[$deptCategory->id] ?? null;
                                            $pickBlocked = $pickStatus && $pickStatus->blocked;
                                        @endphp
                                        <tr class="emi-pick-row {{ $pickLow ? 'is-low' : '' }} {{ $pickBlocked ? 'is-blocked' : '' }}"
                                            data-blocked="{{ $pickBlocked ? 1 : 0 }}"
                                            data-id="{{ $deptCategory->id }}"
                                            data-unit-id="{{ $deptCategory->unit_id }}"
                                            data-low="{{ $pickLow ? 1 : 0 }}" data-search="{{ $pickSearch }}"
                                            data-name="{{ $catLabels[(string) $deptCategory->id]['name'] ?? '' }}"
                                            data-meta="{{ $catLabels[(string) $deptCategory->id]['meta'] ?? '' }}"
                                            data-groups="{{ json_encode($pickGroups) }}">
                                            <td class="text-center"><input type="checkbox" class="emi-pick"
                                                    {{ $pickBlocked ? 'disabled' : '' }}></td>
                                            <td><span class="md-tag">{{ $deptCategory->code }}</span></td>
                                            <td class="font-weight-bold">
                                                {{ $deptCategory->{$nameField} }}
                                                <span class="badge badge-secondary emi-pick-added"
                                                    style="display:none">Đã có trong bảng</span>
                                            </td>
                                            @if ($emiGroups)
                                                <td>v{{ $deptCategory->version }}</td>
                                                <td class="emi-group-chips">
                                                    @forelse ($pickGroups as $pickGroup)
                                                        <span class="badge badge-light border"
                                                            title="{{ $emiGroups[$pickGroup]['name'] }}">{{ $emiGroups[$pickGroup]['short'] }}</span>
                                                    @empty
                                                        <span class="md-empty">—</span>
                                                    @endforelse
                                                </td>
                                            @endif
                                            <td>{{ $deptCategory->manufacturer_short_name ?: $deptCategory->manufacturer_name }}
                                            </td>
                                            @if ($isChemical)
                                                <td>{{ $deptCategory->cas_no ?: '—' }}</td>
                                            @endif
                                            <td>{{ $deptCategory->unit_short_name ?: $deptCategory->unit_name }}</td>
                                            <td
                                                class="text-right {{ $pickLow ? 'text-danger font-weight-bold' : '' }}">
                                                {{ $pickNum($pickOnHand) }}</td>
                                            <td class="text-right">{{ $pickNum($deptCategory->min_stock) }}</td>
                                            <td class="text-right">{{ $pickNum($deptCategory->max_stock) }}</td>
                                            @if ($isChemical)
                                                <td>
                                                    @if ($pickBlocked)
                                                        <span class="badge badge-danger emi-blocked-badge"
                                                            title="{{ $pickStatus->message }}">Đã vượt ngưỡng, không được dự trù thêm</span>
                                                    @elseif ($pickStatus)
                                                        <span class="badge {{ $levelBadge[$pickStatus->level] ?? 'badge-light border' }}"
                                                            title="{{ $pickStatus->message }}">{{ $levelLabel[$pickStatus->level] ?? '' }}
                                                            {{ (int) round($pickStatus->ratio * 100) }}%</span>
                                                    @else
                                                        <span class="md-empty">—</span>
                                                    @endif
                                                    @if ($pickStatus)
                                                        <button type="button" class="btn-est-thr-detail text-primary"
                                                            data-params="{{ json_encode(['category_id' => $deptCategory->id]) }}"
                                                            title="Xem các lượng đóng góp: tồn theo lô, dự trù chưa hoàn thành">
                                                            <i class="fas fa-search-plus"></i> Chi tiết
                                                        </button>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-3">Phòng chưa khai
                                                {{ $emi['itemLabel'] }} nào trong danh mục.</td>
                                        </tr>
                                    @endforelse
                                    <tr class="emi-pick-none" style="display:none">
                                        <td colspan="10" class="text-center text-muted py-3">Không tìm thấy
                                            {{ $emi['itemLabel'] }} phù hợp.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="emi-picker-foot">
                            <span class="emi-pick-count">Đã chọn 0 {{ $emi['itemLabel'] }}</span>
                            <button type="button" class="btn btn-sm btn-primary btn-emi-pick-apply" disabled>
                                <i class="fas fa-arrow-down mr-1"></i> Thêm vào bảng dự trù
                            </button>
                        </div>
                    </div>

                    <div class="emi-scroll">
                        <table class="table emi-table">
                            <thead>
                                <tr>
                                    <th class="emi-col-no text-center">#</th>
                                    <th class="emi-col-material">{{ $emi['itemHead'] }} <span
                                            class="text-danger">*</span></th>
                                    @if ($emiGroups)
                                        <th class="emi-col-group" title="Bắt buộc với chất chuẩn ngoài danh mục">
                                            Nhóm Chuẩn Mong Muốn</th>
                                    @endif
                                    <th class="emi-col-text">Thông Tin Kỹ Thuật</th>
                                    <th class="emi-col-text">Mục Đích Sử Dụng</th>
                                    <th class="emi-col-date">Ngày Mong Muốn Giao</th>
                                    <th class="emi-col-unit" @if (is_array($emiFactorUnits)) style="width:170px" @endif>Đơn Vị
                                        <span class="text-danger">*</span></th>
                                    @foreach ($emiPeriods as $k => $period)
                                        <th class="emi-col-period">
                                            <div class="emi-period">
                                                <input type="month" name="periods[{{ $k }}]"
                                                    value="{{ $period }}"
                                                    class="form-control {{ $bag->has("periods.$k") ? 'is-invalid' : '' }}"
                                                    title="Tháng cần dùng">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger btn-emi-period-remove"
                                                    title="Bỏ cột tháng">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </th>
                                    @endforeach
                                    <th class="emi-col-act text-center">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($emiRows as $index => $row)
                                    @include('pages.estimate.shared.batchItemRow', $rowVars + [
                                        'index' => $index,
                                        'row' => (array) $row,
                                        'periodCount' => count($emiPeriods),
                                    ])
                                @endforeach
                                <tr class="emi-empty" @if (count($emiRows)) style="display:none" @endif>
                                    <td colspan="{{ $emiFixedCols + count($emiPeriods) }}">
                                        <i class="{{ $emi['icon'] }}"></i>
                                        Chưa có {{ $emi['itemLabel'] }} nào. Bấm <b>Chọn từ danh mục phòng</b> hoặc
                                        <b>Thêm dòng ngoài danh mục</b>.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <template id="emiRowTplCategory">
                        @include('pages.estimate.shared.batchItemRow', $rowVars + [
                            'index' => '',
                            'row' => ['source' => 'category'],
                            'periodCount' => 0,
                        ])
                    </template>
                    <template id="emiRowTplManual">
                        @include('pages.estimate.shared.batchItemRow', $rowVars + [
                            'index' => '',
                            'row' => ['source' => 'manual'],
                            'periodCount' => 0,
                        ])
                    </template>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>
                        {{ $emi['saveLabel'] }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $modal = $('#itemCreateModal');
        var $form = $('#emiForm');
        var $table = $form.find('.emi-table');
        var $thead = $table.find('thead tr');
        var $tbody = $table.find('tbody');
        var $picker = $form.find('.emi-picker');
        var maxStock = $modal.data('max-stock') || {};
        var thresholdUrl = $modal.data('threshold-url') || '';
        var fixedCols = parseInt($modal.data('fixed-cols'), 10) || 7;
        var baseWidth = parseInt($modal.data('base-width'), 10) || 1100;
        var itemLabel = $modal.data('item-label') || 'mặt hàng';
        var factorUnits = $modal.data('factor-units') || {};

        function periodHeader(value) {
            return $(
                '<th class="emi-col-period"><div class="emi-period">' +
                '<input type="month" class="form-control" title="Tháng cần dùng">' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-emi-period-remove" title="Bỏ cột tháng">' +
                '<i class="fas fa-times"></i></button></div></th>'
            ).find('input').val(value || '').end();
        }

        function amountCell() {
            return $('<td class="emi-amount-cell"><input type="text" inputmode="decimal" ' +
                'class="form-control form-control-sm js-decimal text-right emi-amount" placeholder="0"></td>'
            );
        }

        function periodCount() {
            return $thead.find('.emi-col-period').length;
        }

        /* Đặt lại tên ô theo thứ tự: items[i][field], items[i][amounts][k], periods[k] */
        function reindex() {
            $thead.find('.emi-col-period input').each(function(k) {
                $(this).attr('name', 'periods[' + k + ']');
            });

            $tbody.find('.emi-row').each(function(i) {
                $(this).find('.emi-no').text(i + 1);
                $(this).find('[data-f]').each(function() {
                    $(this).attr('name', 'items[' + i + '][' + $(this).data('f') + ']');
                });
                $(this).find('.emi-amount').each(function(k) {
                    $(this).attr('name', 'items[' + i + '][amounts][' + k + ']');
                });
            });

            var rows = $tbody.find('.emi-row').length;
            $tbody.find('.emi-empty').toggle(rows === 0).find('td').attr('colspan', fixedCols + periodCount());
            $thead.find('.btn-emi-period-remove').prop('disabled', periodCount() <= 1);
            $table.css('min-width', (baseWidth + periodCount() * 150) + 'px');
            $form.find('.emi-count').text(rows + ' dòng ' + itemLabel + ' · ' + periodCount() + ' tháng');
        }

        /**
         * Thêm một dòng vào bảng.
         * data.fields: source ('category' | 'manual'), category_id, unit_id, group_key...
         * data.label : { name, meta } - tên hiển thị của dòng danh mục
         */
        function addRow(data, $after) {
            data = data || {
                fields: {
                    source: 'manual'
                },
                amounts: []
            };

            var tpl = data.fields.source === 'category' ? '#emiRowTplCategory' : '#emiRowTplManual';
            var $row = $($(tpl).html().trim());

            for (var k = 0; k < periodCount(); k++) {
                $row.find('.emi-act').before(amountCell());
            }

            Object.keys(data.fields).forEach(function(f) {
                if (f !== 'source') $row.find('[data-f="' + f + '"]').val(data.fields[f]);
            });
            $row.find('.emi-amount').each(function(k) {
                $(this).val((data.amounts || [])[k] || '');
            });

            if (data.label) {
                $row.find('.emi-mat-name').text(data.label.name || '');
                $row.find('.emi-mat-meta').text(data.label.meta || '');
            }

            $after ? $after.after($row) : $tbody.find('.emi-empty').before($row);
            applyFactor($row);
            checkMaxStock($row);
            checkThreshold($row);
            reindex();

            // Ô chữ đã có nội dung thì giãn cho vừa
            $row.find('.emi-auto').each(function() {
                if (this.value) {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 2 + 'px';
                }
            });

            return $row;
        }

        function rowData($row) {
            var fields = {};
            $row.find('[data-f]').each(function() {
                fields[$(this).data('f')] = $(this).val();
            });

            return {
                fields: fields,
                label: {
                    name: $row.find('.emi-mat-name').text(),
                    meta: $row.find('.emi-mat-meta').text()
                },
                amounts: $row.find('.emi-amount').map(function() {
                    return $(this).val();
                }).get()
            };
        }

        /* Cảnh báo vượt ngưỡng tồn tối đa của phòng, tính riêng từng dòng danh mục */
        function checkMaxStock($row) {
            if (typeof wmsMaxStockWarn !== 'function') return;

            var $warn = $row.find('.js-max-stock-warn');
            var info = maxStock[$row.find('.emi-h-category').val()] || null;
            var unitId = $row.find('.emi-unit').val();

            if (!info || (info.unit_id && String(unitId) !== String(info.unit_id))) {
                wmsMaxStockWarn($warn, null, 0);
                return;
            }

            var total = 0;
            $row.find('.emi-amount').each(function() {
                var amount = parseFloat(($(this).val() || '').replace(/,/g, ''));
                if (amount > 0) total += amount;
            });

            wmsMaxStockWarn($warn, info, total);
        }

        /* Hoá chất nhóm 9 / 10 khai khác đơn vị danh mục của phòng: bắt khai hệ số quy đổi
           "1 <đơn vị dự trù> = ? <đơn vị danh mục>" - xem wmsEstFactor() ở shared/assets */
        function applyFactor($row) {
            if (typeof wmsEstFactor !== 'function') return;

            wmsEstFactor($row.find('.est-factor'), factorUnits[$row.find('.emi-h-category').val()] || null,
                $row.find('.emi-unit'));
        }

        /* Cảnh báo ngưỡng tồn trữ PL IV NĐ 24/2026 của từng dòng hoá chất trong danh mục -
           cùng endpoint checkThreshold với modal Sửa, chỉ màn Hoá Chất có data-threshold-url */
        var thresholdLabels = {
            exceeded: 'Dự kiến vượt ngưỡng PL IV',
            warn: 'Sắp chạm ngưỡng PL IV'
        };

        /* params = {category_id, amounts} đang khai - nút "Chi tiết" gửi lại cho thresholdDetail()
           để modal liệt kê các lượng đóng góp (xem pages/estimate/shared/thresholdDetailModal) */
        function renderThreshold($box, warnings, params) {
            $box.empty();

            (warnings || []).forEach(function(w) {
                var level = w.level === 'exceeded' ? 'exceeded' : 'warn';
                var label = thresholdLabels[level] + (w.percent ? ' · ' + w.percent + '%' : '');

                $('<div class="est-threshold-alert is-compact emi-threshold-line level-' + level + '">' +
                        '<i class="fas fa-exclamation-triangle mr-1"></i><span></span></div>')
                    .attr('title', w.message || '')
                    .find('span').text(label).end()
                    .append($('<button type="button" class="btn-est-thr-detail">' +
                        '<i class="fas fa-search-plus"></i> Chi tiết</button>').data('params', params))
                    .appendTo($box);
            });
        }

        function checkThreshold($row) {
            var $box = $row.find('.emi-threshold');

            if (!thresholdUrl || !$box.length) return;

            clearTimeout($row.data('threshold-timer'));

            var categoryId = $row.find('.emi-h-category').val();
            var unitId = $row.find('.emi-unit').val();
            var factor = $row.find('.js-factor-input').val() || '';
            var amounts = [];

            $row.find('.emi-amount').each(function() {
                var amount = parseFloat(($(this).val() || '').replace(/,/g, ''));
                if (amount > 0) amounts.push({
                    amount: amount,
                    unit_id: unitId,
                    conversion_factor: factor
                });
            });

            if (!categoryId || !unitId || !amounts.length) {
                $row.data('threshold-seq', ($row.data('threshold-seq') || 0) + 1);
                renderThreshold($box, []);
                return;
            }

            $row.data('threshold-timer', setTimeout(function() {
                // Chỉ nhận kết quả của lần gọi mới nhất, tránh câu trả lời cũ về sau đè lên
                var seq = ($row.data('threshold-seq') || 0) + 1;
                $row.data('threshold-seq', seq);

                var params = {
                    category_id: categoryId,
                    amounts: amounts
                };

                $.get(thresholdUrl, params).done(function(res) {
                    if ($row.data('threshold-seq') === seq) renderThreshold($box, res && res.warnings, params);
                });
            }, 400));
        }

        /* ---------- Khung chọn từ danh mục phòng ---------- */
        function normalize(text) {
            return (text || '').toLowerCase().normalize('NFD')
                .replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').trim();
        }

        function tableCategoryIds() {
            return $tbody.find('.emi-row-category .emi-h-category').map(function() {
                return $(this).val();
            }).get().filter(Boolean);
        }

        function refreshPicker() {
            var term = normalize($picker.find('.emi-picker-search').val());
            var lowOnly = $picker.find('.emi-picker-low-only').is(':checked');
            var added = tableCategoryIds();
            var visible = 0;

            $picker.find('.emi-pick-row').each(function() {
                var $r = $(this);
                var isAdded = added.indexOf(String($r.data('id'))) !== -1;
                var isBlocked = $r.data('blocked') == 1;
                var show = (!term || String($r.data('search')).indexOf(term) !== -1) &&
                    (!lowOnly || $r.data('low') == 1);

                $r.toggleClass('is-added', isAdded).find('.emi-pick-added').toggle(isAdded);
                if (isAdded) $r.find('.emi-pick').prop('checked', false);
                // Mã đã vượt ngưỡng PL IV (tồn + đang dự trù) không bao giờ chọn được
                $r.find('.emi-pick').prop('disabled', isAdded || isBlocked);
                $r.toggleClass('is-checked', $r.find('.emi-pick').is(':checked'));
                $r.toggle(show);
                if (show) visible++;
            });

            $picker.find('.emi-pick-none').toggle(visible === 0 && $picker.find('.emi-pick-row').length > 0);

            var checked = $picker.find('.emi-pick:checked').length;
            $picker.find('.emi-pick-count').text('Đã chọn ' + checked + ' ' + itemLabel);
            $picker.find('.btn-emi-pick-apply').prop('disabled', checked === 0);
            $picker.find('.emi-pick-all').prop('checked',
                checked > 0 && $picker.find('.emi-pick-row:visible .emi-pick:not(:disabled):not(:checked)')
                .length === 0);
        }

        function openPicker() {
            refreshPicker();
            $picker.slideDown(150, function() {
                $picker.find('.emi-picker-search').trigger('focus');
            });
        }

        function closePicker() {
            $picker.slideUp(150);
            $picker.find('.emi-pick').prop('checked', false);
        }

        $form.on('click', '.btn-emi-picker-toggle', function() {
            $picker.is(':visible') ? closePicker() : openPicker();
        });

        $picker.on('input', '.emi-picker-search', refreshPicker);
        $picker.on('change', '.emi-picker-low-only, .emi-pick', refreshPicker);

        // Bấm cả dòng cũng chọn / bỏ chọn
        $picker.on('click', '.emi-pick-row', function(e) {
            if ($(e.target).closest('input, button, a').length) return;

            var $check = $(this).find('.emi-pick');
            if ($check.prop('disabled')) return;

            $check.prop('checked', !$check.prop('checked'));
            refreshPicker();
        });

        $picker.on('change', '.emi-pick-all', function() {
            $picker.find('.emi-pick-row:visible .emi-pick:not(:disabled)').prop('checked', this.checked);
            refreshPicker();
        });

        $picker.on('click', '.btn-emi-pick-apply', function() {
            $picker.find('.emi-pick:checked').closest('.emi-pick-row').each(function() {
                var fields = {
                    source: 'category',
                    category_id: String($(this).data('id')),
                    unit_id: String($(this).data('unit-id') || '')
                };
                // Chất chuẩn chỉ thuộc đúng một nhóm thì điền sẵn nhóm chuẩn mong muốn
                var groups = $(this).data('groups') || [];
                if (groups.length === 1) fields.group_key = groups[0];

                addRow({
                    fields: fields,
                    label: {
                        name: $(this).data('name'),
                        meta: $(this).data('meta')
                    },
                    amounts: []
                });
            });

            closePicker();
            $form.find('.emi-scroll').scrollTop($form.find('.emi-scroll')[0].scrollHeight);
        });

        /* ---------- Bảng dự trù ---------- */
        function resetTable() {
            var periods = $modal.data('default-periods') || [];

            $form.find('.emi-errors').hide().find('ul').empty();
            $picker.hide().find('.emi-pick, .emi-picker-low-only').prop('checked', false);
            $picker.find('.emi-picker-search').val('');
            $tbody.find('.emi-row').remove();
            $thead.find('.emi-col-period').remove();

            periods.forEach(function(period) {
                $thead.find('.emi-col-act').before(periodHeader(period));
            });

            reindex();
        }

        $(document).on('click', '.btn-est-item-create', function() {
            resetTable();
            // Đường chính là chọn trong danh mục phòng nên mở sẵn khung chọn
            if ($picker.find('.emi-pick-row').length) openPicker();
        });

        // "Thêm dòng" = mặt hàng NGOÀI danh mục
        $form.on('click', '.btn-emi-add', function() {
            var $row = addRow();

            $form.find('.emi-scroll').scrollTop($form.find('.emi-scroll')[0].scrollHeight);
            $row.find('.emi-h-name').trigger('focus');
        });

        $form.on('click', '.btn-emi-copy', function() {
            var $row = $(this).closest('.emi-row');
            addRow(rowData($row), $row);
        });

        $form.on('click', '.btn-emi-remove', function() {
            $(this).closest('.emi-row').remove();
            reindex();
            if ($picker.is(':visible')) refreshPicker();
        });

        $form.on('click', '.btn-emi-period-add', function() {
            // Tháng kế tiếp của cột cuối cùng
            var last = $thead.find('.emi-col-period input').last().val();
            var next = '';

            if (last) {
                var parts = last.split('-');
                var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10), 1);
                next = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            }

            $thead.find('.emi-col-act').before(periodHeader(next));
            $tbody.find('.emi-row').each(function() {
                $(this).find('.emi-act').before(amountCell());
            });
            reindex();
        });

        $form.on('click', '.btn-emi-period-remove', function() {
            if (periodCount() <= 1) return;

            var k = $thead.find('.emi-col-period').index($(this).closest('.emi-col-period'));

            $(this).closest('.emi-col-period').remove();
            $tbody.find('.emi-row').each(function() {
                $(this).find('.emi-amount-cell').eq(k).remove();
                checkMaxStock($(this));
                checkThreshold($(this));
            });
            reindex();
        });

        $form.on('change', '.emi-unit', function() {
            applyFactor($(this).closest('.emi-row'));
        });

        $form.on('input change', '.emi-amount, .emi-unit, .js-factor-input', function() {
            var $row = $(this).closest('.emi-row');
            checkMaxStock($row);
            checkThreshold($row);
        });

        $form.on('input change', '[data-f], .emi-amount, .emi-period input', function() {
            $(this).removeClass('is-invalid');
        });

        // Ô chữ tự giãn theo nội dung
        $form.on('input', '.emi-auto', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 2 + 'px';
        });

        // Chưa có dòng nào thì không cho lưu
        $form.on('submit', function(e) {
            if (!$tbody.find('.emi-row').length) {
                e.preventDefault();
                $form.find('.emi-errors').show().find('ul')
                    .html('<li>Vui lòng thêm ít nhất một ' + itemLabel + ' vào bảng.</li>');
            }
        });

        // Dòng in sẵn từ server (khi validate lỗi)
        $tbody.find('.emi-row').each(function() {
            applyFactor($(this));
            checkMaxStock($(this));
            checkThreshold($(this));
        });
        reindex();

        @if ($bag->any())
            $modal.modal('show');
        @endif
    });
</script>
