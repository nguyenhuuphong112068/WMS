{{--
| Modal "Thêm Vật Tư Dự Trù" dạng bảng: mỗi dòng là một vật tư, các tháng cần dùng là cột
| chung ở tiêu đề bảng (mặc định 3 tháng liên tiếp từ tháng dự trù của phiếu). Lưu một lần
| được nhiều vật tư - xem MaterialEstimateController::storeItem().
|
| Vật tư TRONG danh mục chỉ vào bảng qua khung "Chọn từ danh mục phòng" (tên hiện cố định);
| nút "Thêm dòng" tạo dòng vật tư NGOÀI danh mục (gõ tên tự do). Không cần chọn nguồn riêng.
--}}
@php
    $bag = $errors->getBag('itemCreateErrors');

    $estStart = \Carbon\Carbon::createFromDate($list->year, $list->month, 1);
    $emiDefaultPeriods = collect(range(0, 2))
        ->map(fn($step) => $estStart->copy()->addMonths($step)->format('Y-m'))
        ->all();

    $emiPeriods = array_values((array) old('periods', $emiDefaultPeriods)) ?: $emiDefaultPeriods;
    $emiRows = array_values((array) old('items', []));

    // Tên hiển thị của dòng danh mục: ưu tiên danh mục phòng, thiếu thì lấy danh mục chung
    $emiLabel = fn($c) => [
        'name' => $c->material_name,
        'meta' => implode(
            ' · ',
            array_filter([
                $c->code ?? null,
                $c->manufacturer_short_name ?? ($c->manufacturer_name ?? null),
                $c->technical_specification,
            ]),
        ),
        'lead' => $c->lead_time_days ?? null,
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
@endphp

@include('pages.estimate.shared.batchItemStyle')

<div class="modal fade md-modal" id="itemCreateModal" tabindex="-1" role="dialog"
    data-default-periods="{{ json_encode($emiDefaultPeriods) }}"
    data-base-date="{{ \Carbon\Carbon::parse($list->created_at)->format('Y-m-d') }}"
    data-max-stock="{{ json_encode($maxStock ?? [], JSON_UNESCAPED_UNICODE) }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-box-open"></i> Thêm Vật Tư Dự Trù</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($estRoute . 'storeItem') }}" method="POST" id="emiForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="material_estimate_id" value="{{ $list->id }}">

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
                                title="Thêm dòng cho vật tư chưa có trong danh mục">
                                <i class="fas fa-plus mr-1"></i> Thêm dòng ngoài danh mục
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-emi-period-add">
                                <i class="fas fa-calendar-plus mr-1"></i> Thêm tháng
                            </button>
                        </div>
                        <span class="emi-count"></span>
                    </div>

                    {{-- Khung chọn nhiều vật tư từ Danh Mục Vật Tư của phòng --}}
                    <div class="emi-picker" style="display:none">
                        <div class="emi-picker-head">
                            <div class="emi-picker-title"><i class="fas fa-th-list mr-1"></i> Danh Mục Vật Tư Của Phòng
                            </div>
                            <input type="text" class="form-control form-control-sm emi-picker-search"
                                placeholder="Tìm theo mã, tên, hãng, quy cách...">
                            <label class="emi-picker-low mb-0">
                                <input type="checkbox" class="emi-picker-low-only"> Chỉ vật tư dưới tồn tối thiểu
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
                                        <th style="width:120px">Mã</th>
                                        <th>Tên Vật Tư</th>
                                        <th style="width:140px">Hãng</th>
                                        <th>Quy Cách</th>
                                        <th style="width:80px">Đơn Vị</th>
                                        <th class="text-right" style="width:100px">Tồn Hiện Tại</th>
                                        <th class="text-right" style="width:100px">Tồn Tối Thiểu</th>
                                        <th class="text-right" style="width:100px">Tồn Tối Đa</th>
                                        <th class="text-right" style="width:110px">TG Đặt Hàng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($deptCategories ?? [] as $deptCategory)
                                        @php
                                            $pickOnHand = (float) ($maxStock[$deptCategory->id]['on_hand'] ?? 0);
                                            $pickLow =
                                                $deptCategory->min_stock !== null &&
                                                $pickOnHand < (float) $deptCategory->min_stock;
                                            $pickSearch = \Illuminate\Support\Str::lower(
                                                \Illuminate\Support\Str::ascii(
                                                    implode(' ', [
                                                        $deptCategory->code,
                                                        $deptCategory->material_name,
                                                        $deptCategory->manufacturer_name,
                                                        $deptCategory->manufacturer_short_name,
                                                        $deptCategory->technical_specification,
                                                    ]),
                                                ),
                                            );
                                            $pickNum = fn($v) => $v === null
                                                ? '—'
                                                : rtrim(rtrim(number_format((float) $v, 4, ',', '.'), '0'), ',');
                                        @endphp
                                        <tr class="emi-pick-row {{ $pickLow ? 'is-low' : '' }}"
                                            data-id="{{ $deptCategory->id }}"
                                            data-unit-id="{{ $deptCategory->unit_id }}"
                                            data-low="{{ $pickLow ? 1 : 0 }}" data-search="{{ $pickSearch }}"
                                            data-name="{{ $catLabels[(string) $deptCategory->id]['name'] ?? '' }}"
                                            data-meta="{{ $catLabels[(string) $deptCategory->id]['meta'] ?? '' }}"
                                            data-lead-days="{{ $deptCategory->lead_time_days }}"
                                            data-spec="{{ $deptCategory->technical_specification }}">
                                            <td class="text-center"><input type="checkbox" class="emi-pick"></td>
                                            <td><span class="md-tag">{{ $deptCategory->code }}</span></td>
                                            <td class="font-weight-bold">
                                                {{ $deptCategory->material_name }}
                                                <span class="badge badge-secondary emi-pick-added"
                                                    style="display:none">Đã có trong bảng</span>
                                            </td>
                                            <td>{{ $deptCategory->manufacturer_short_name ?: $deptCategory->manufacturer_name }}
                                            </td>
                                            <td>{{ $deptCategory->technical_specification }}</td>
                                            <td>{{ $deptCategory->unit_short_name ?: $deptCategory->unit_name }}</td>
                                            <td
                                                class="text-right {{ $pickLow ? 'text-danger font-weight-bold' : '' }}">
                                                {{ $pickNum($pickOnHand) }}</td>
                                            <td class="text-right">{{ $pickNum($deptCategory->min_stock) }}</td>
                                            <td class="text-right">{{ $pickNum($deptCategory->max_stock) }}</td>
                                            <td class="text-right">
                                                {{ $deptCategory->lead_time_days !== null ? $deptCategory->lead_time_days . ' ngày' : '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-3">Phòng chưa khai vật
                                                tư nào trong danh mục.</td>
                                        </tr>
                                    @endforelse
                                    <tr class="emi-pick-none" style="display:none">
                                        <td colspan="10" class="text-center text-muted py-3">Không tìm thấy vật tư phù
                                            hợp.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="emi-picker-foot">
                            <span class="emi-pick-count">Đã chọn 0 vật tư</span>
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
                                    <th class="emi-col-material">Vật Tư <span class="text-danger">*</span></th>
                                    <th class="emi-col-pn">Part Number</th>
                                    <th class="emi-col-text">Thông Tin Kỹ Thuật</th>
                                    <th class="emi-col-text">Mục Đích Sử Dụng</th>
                                    <th class="emi-col-date">Ngày Mong Muốn Giao</th>
                                    <th class="emi-col-unit">Đơn Vị <span class="text-danger">*</span></th>
                                    <th class="emi-col-file">File</th>
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
                                    @include('pages.estimate.MaterialEstimate.itemCreateRow', [
                                        'index' => $index,
                                        'row' => (array) $row,
                                        'periodCount' => count($emiPeriods),
                                    ])
                                @endforeach
                                <tr class="emi-empty" @if (count($emiRows)) style="display:none" @endif>
                                    <td colspan="{{ 9 + count($emiPeriods) }}">
                                        <i class="fas fa-box-open"></i>
                                        Chưa có vật tư nào. Bấm <b>Chọn từ danh mục phòng</b> hoặc
                                        <b>Thêm dòng ngoài danh mục</b>.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <template id="emiRowTplCategory">
                        @include('pages.estimate.MaterialEstimate.itemCreateRow', [
                            'index' => '',
                            'row' => ['source' => 'category'],
                            'periodCount' => 0,
                        ])
                    </template>
                    <template id="emiRowTplManual">
                        @include('pages.estimate.MaterialEstimate.itemCreateRow', [
                            'index' => '',
                            'row' => ['source' => 'manual'],
                            'periodCount' => 0,
                        ])
                    </template>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu vật
                        tư</button>
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
                $(this).find('.emi-files').attr('name', 'items[' + i + '][files][]');
            });

            var rows = $tbody.find('.emi-row').length;
            $tbody.find('.emi-empty').toggle(rows === 0).find('td').attr('colspan', 9 + periodCount());
            $thead.find('.btn-emi-period-remove').prop('disabled', periodCount() <= 1);
            $table.css('min-width', (1360 + periodCount() * 150) + 'px');
            $form.find('.emi-count').text(rows + ' dòng vật tư · ' + periodCount() + ' tháng');
        }

        /**
         * Thêm một dòng vào bảng.
         * data.fields: source ('category' | 'manual'), category_id, material_name, unit_id...
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

            if (data.fields.source === 'category') $row.attr('data-lead-days', data.leadDays || '');

            $after ? $after.after($row) : $tbody.find('.emi-empty').before($row);
            checkMaxStock($row);
            checkLeadTime($row);
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
                leadDays: $row.attr('data-lead-days') || '',
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

        /* Cảnh báo không kịp thời gian đặt hàng: từ ngày tạo phiếu tới ngày mong muốn giao
           ít hơn "Thời gian đặt hàng" của danh mục - cùng công thức App\Support\MaterialLeadTime */
        function toUtcDay(value) {
            var p = (value || '').split('-');
            return p.length === 3 ? Date.UTC(+p[0], +p[1] - 1, +p[2]) : null;
        }

        function checkLeadTime($row) {
            var $warn = $row.find('.emi-lead-warn');
            var lead = parseInt($row.attr('data-lead-days'), 10);
            var base = toUtcDay($modal.data('base-date'));
            var expected = toUtcDay($row.find('[data-f="expected_delivery_date"]').val());

            if (!lead || lead <= 0 || base === null || expected === null) {
                $warn.hide().removeAttr('title');
                return;
            }

            var available = Math.round((expected - base) / 86400000);

            if (available >= lead) {
                $warn.hide().removeAttr('title');
                return;
            }

            $warn.html('<i class="fas fa-exclamation-triangle mr-1"></i>Không kịp đặt hàng')
                .attr('title', 'Thời gian đặt hàng ' + lead +
                    ' ngày, từ ngày tạo phiếu đến ngày mong muốn giao chỉ còn ' +
                    available + ' ngày.')
                .append('<br>cần ' + lead + ' ngày') //, còn ' + available + ' ngày'
                .show();
        }

        $form.on('change input', '[data-f="expected_delivery_date"]', function() {
            checkLeadTime($(this).closest('.emi-row'));
        });

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
                var show = (!term || String($r.data('search')).indexOf(term) !== -1) &&
                    (!lowOnly || $r.data('low') == 1);

                $r.toggleClass('is-added', isAdded).find('.emi-pick-added').toggle(isAdded);
                if (isAdded) $r.find('.emi-pick').prop('checked', false);
                $r.find('.emi-pick').prop('disabled', isAdded);
                $r.toggleClass('is-checked', $r.find('.emi-pick').is(':checked'));
                $r.toggle(show);
                if (show) visible++;
            });

            $picker.find('.emi-pick-none').toggle(visible === 0 && $picker.find('.emi-pick-row').length > 0);

            var checked = $picker.find('.emi-pick:checked').length;
            $picker.find('.emi-pick-count').text('Đã chọn ' + checked + ' vật tư');
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
            if ($(e.target).is('input')) return;

            var $check = $(this).find('.emi-pick');
            if ($check.prop('disabled')) return;

            $check.prop('checked', !$check.prop('checked'));
            refreshPicker();
        });

        $picker.on('change', '.emi-pick-all', function() {
            $picker.find('.emi-pick-row:visible .emi-pick:not(:disabled)').prop('checked', this
                .checked);
            refreshPicker();
        });

        $picker.on('click', '.btn-emi-pick-apply', function() {
            $picker.find('.emi-pick:checked').closest('.emi-pick-row').each(function() {
                addRow({
                    fields: {
                        source: 'category',
                        category_id: String($(this).data('id')),
                        unit_id: String($(this).data('unit-id') || ''),
                        // Thông tin kỹ thuật điền sẵn từ quy cách của Danh Mục công ty, sửa được
                        technical_information: String($(this).attr('data-spec') || '')
                    },
                    label: {
                        name: $(this).data('name'),
                        meta: $(this).data('meta')
                    },
                    leadDays: String($(this).data('lead-days') || ''),
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

        // "Thêm dòng" = vật tư NGOÀI danh mục
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
            });
            reindex();
        });

        $form.on('input change', '.emi-amount, .emi-unit', function() {
            checkMaxStock($(this).closest('.emi-row'));
        });

        $form.on('input change', '[data-f], .emi-amount, .emi-period input', function() {
            $(this).removeClass('is-invalid');
        });

        // File đính kèm của từng vật tư: hiện số file + tên file đã chọn
        $form.on('change', '.emi-files', function() {
            var names = Array.prototype.map.call(this.files || [], function(f) {
                return f.name;
            });
            var $cell = $(this).closest('.emi-file-cell');

            $cell.find('.emi-file-btn').toggleClass('has-files', names.length > 0)
                .removeClass('border-danger text-danger');
            $cell.find('.emi-file-count').text(names.length ? names.length + ' file' : 'Chọn');
            $cell.find('.emi-file-names').text(names.join(', ')).attr('title', names.join(', '));
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
                    .html('<li>Vui lòng thêm ít nhất một vật tư vào bảng.</li>');
            }
        });

        // Dòng in sẵn từ server (khi validate lỗi)
        $tbody.find('.emi-row').each(function() {
            checkMaxStock($(this));
            checkLeadTime($(this));
        });
        reindex();

        @if ($bag->any())
            $modal.modal('show');
        @endif
    });
</script>
