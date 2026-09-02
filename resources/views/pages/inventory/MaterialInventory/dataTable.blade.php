@include('pages.inventory.shared.assets')

<style>
    .mi-tabs { display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap; }
    .mi-tab {
        border: 1px solid var(--primary-soft); background: #fff; color: var(--text-main);
        border-radius: var(--border-radius-md); padding: 8px 16px; font-weight: 600; cursor: pointer;
        transition: all .2s;
    }
    .mi-tab.is-active { background: linear-gradient(135deg, var(--primary-light), var(--primary)); color: #fff; }
    .mi-pane { display: none; }
    .mi-pane.is-active { display: block; }
    .mi-chip {
        display: inline-block; border: 1px solid var(--primary-soft); background: #fff; border-radius: 999px;
        padding: 4px 12px; margin: 0 4px 6px 0; font-size: .8rem; font-weight: 600; cursor: pointer;
    }
    .mi-chip.is-active { background: var(--primary); color: #fff; border-color: var(--primary); }
    .mi-badge { display: inline-block; border-radius: 999px; padding: 2px 10px; font-size: .74rem; font-weight: 700; }
    .mi-badge.in { background: #DCFCE7; color: #166534; }
    .mi-badge.low { background: #FEF9C3; color: #854D0E; }
    .mi-badge.near { background: #FFEDD5; color: #9A3412; }
    .mi-badge.expired { background: #FEE2E2; color: #991B1B; }
    .mi-badge.out { background: #E2E8F0; color: #475569; }
    .mi-badge.over { background: #FCE7F3; color: #9D174D; }

    /* ---------- Cột THAO TÁC ---------- */
    .mi-actions { display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .mi-act-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; padding: 0; line-height: 1;
        border-radius: var(--border-radius-md); font-size: .85rem;
        border: 1px solid var(--primary); background: #fff; color: var(--primary);
        transition: all .2s;
    }
    .mi-act-btn:hover, .mi-act-btn:focus {
        background: var(--primary); color: #fff; border-color: var(--primary-dark);
        transform: translateY(-1px); box-shadow: var(--shadow-sm); outline: none;
    }
    .mi-act-count {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 32px; height: 32px; padding: 0 8px; line-height: 1;
        border-radius: var(--border-radius-md); font-size: .78rem; font-weight: 700;
        border: 1px solid var(--primary-lighter); background: var(--primary-soft); color: var(--primary);
        transition: all .2s;
    }
    .mi-act-count:hover, .mi-act-count:focus {
        background: var(--primary-lighter); color: var(--primary-dark);
        border-color: var(--primary); transform: translateY(-1px); outline: none;
    }
</style>

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                {{-- ---------- KỲ BÁO CÁO ---------- --}}
                <form method="GET" class="form-inline mb-3" style="gap: 8px;">
                    <label class="mr-1 font-weight-bold">Kỳ:</label>
                    <input type="date" name="from_date" value="{{ $period['from'] }}" class="form-control form-control-sm">
                    <span class="mx-1">→</span>
                    <input type="date" name="to_date" value="{{ $period['to'] }}" class="form-control form-control-sm">
                    <button class="btn btn-sm btn-primary ml-2"><i class="fas fa-search mr-1"></i>Xem kỳ</button>
                    <span class="ml-3 md-sub">
                        {{ $invPeriodLabel }} ({{ $period['days'] }} ngày){{ $period['is_current'] ? ' — kỳ còn hôm nay, tồn cuối kỳ = tồn hiện tại' : '' }}
                        — chỉ hiện mã còn tồn cuối kỳ, có sử dụng hoặc có loại bỏ trong kỳ
                    </span>
                </form>

                <div class="mi-tabs">
                    <button type="button" class="mi-tab is-active" data-pane="miPaneCode"><i class="fas fa-boxes mr-1"></i>Theo mã xuất nhập</button>
                    <button type="button" class="mi-tab" data-pane="miPaneMat"><i class="fas fa-layer-group mr-1"></i>Tồn kho theo tên</button>
                    <button type="button" class="mi-tab" data-pane="miPaneZone"><i class="fas fa-map-marked-alt mr-1"></i>Tồn kho theo vị trí</button>
                    <button type="button" class="mi-tab" data-pane="miPaneCheck">
                        <i class="fas fa-clipboard-check mr-1"></i>Kiểm kê định kỳ
                        @if ($stocktake['canOpen'])
                            <span class="badge badge-warning ml-1">Chưa kiểm kê {{ $stocktake['periodLabel'] }}</span>
                        @elseif ($stocktake['current']->state === 'counting')
                            <span class="badge badge-light ml-1">{{ $stocktake['progress']['counted'] }}/{{ $stocktake['progress']['total'] }}</span>
                        @endif
                    </button>
                </div>

                {{-- ============ THEO MÃ XUẤT NHẬP ============ --}}
                <div class="mi-pane is-active" id="miPaneCode">
                    <div class="mb-2">
                        <span class="mi-chip is-active" data-state="">Tất cả ({{ $datas->count() }})</span>
                        @foreach ($states as $key => $lbl)
                            @php $c = $datas->where('state', $key)->count(); @endphp
                            @if ($c > 0)
                                <span class="mi-chip" data-state="{{ $key }}">{{ $lbl }} ({{ $c }})</span>
                            @endif
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:45px">STT</th>
                                    <th style="width:150px">Mã Xuất Nhập</th>
                                    <th>Vật Tư</th>
                                    <th style="width:150px">Vị Trí</th>
                                    <th class="text-right" style="width:90px">Tồn Đầu Kỳ</th>
                                    <th class="text-right" style="width:90px">Nhập Trong Kỳ</th>
                                    <th class="text-right" style="width:90px">Sử Dụng</th>
                                    <th class="text-right" style="width:80px">Loại Bỏ</th>
                                    <th class="text-right" style="width:95px">Tồn Cuối Kỳ</th>
                                    <th class="text-right" style="width:100px">Tổng Tồn Vật Tư</th>
                                    <th class="text-center" style="width:95px">Hạn Dùng</th>
                                    <th class="text-center" style="width:95px">Trạng Thái</th>
                                    <th class="text-center" style="width:110px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($datas as $row)
                                    <tr data-state="{{ $row->state }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td><span class="inv-code font-weight-bold">{{ $row->code }}</span></td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->material_name ?: '—' }}</div>
                                            <div class="md-sub small text-muted">
                                                {{ $row->manufacturer_short_name ?: '' }}
                                                @if ($row->technical_specification) · {{ $row->technical_specification }} @endif
                                                @if ($row->classification_name) · {{ $row->classification_name }} @endif
                                            </div>
                                        </td>
                                        <td class="md-sub">
                                            @if ($row->location_code)
                                                <span class="md-tag">{{ $row->location_code }}</span>
                                                <div>{{ $row->warehouse_name }} / {{ $row->room_name }} / {{ $row->shelf_name }}</div>
                                            @else <span class="text-muted">—</span> @endif
                                        </td>
                                        <td class="text-right">{{ $invNum($row->opening) }}</td>
                                        <td class="text-right">{{ $invNum($row->period_in) }}</td>
                                        <td class="text-right">{{ $invNum($row->period_used) }}</td>
                                        <td class="text-right">{{ $invNum($row->period_cancelled) }}</td>
                                        <td class="text-right font-weight-bold {{ $row->gap < 0 ? 'text-danger' : '' }}">
                                            {{ $invNum($row->gap) }} <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="text-right">{{ $invNum($row->category_remaining) }}</td>
                                        <td class="text-center md-sub" data-order="{{ $row->expired_date ?: '9999-12-31' }}">{{ $invDate($row->expired_date) }}</td>
                                        <td class="text-center"><span class="mi-badge {{ $row->state }}">{{ $row->state_label }}</span></td>
                                        <td class="text-center">
                                            <div class="mi-actions">
                                            @perm('inventory_material_balancing')
                                                <button type="button" class="mi-act-btn btn-mi-balancing" title="Cân đối"
                                                    data-row="{{ json_encode([
                                                        'id' => $row->id, 'code' => $row->code, 'material_name' => $row->material_name,
                                                        'unit' => $row->unit_short_name ?: $row->unit_name,
                                                        'imported' => $row->imported, 'gap' => $row->gap,
                                                        'balancing_limit' => $row->balancing_limit,
                                                        'balancing_min_input' => $row->balancing_min_input,
                                                        'balancing_max_input' => $row->balancing_max_input,
                                                    ]) }}">
                                                    <i class="fas fa-balance-scale"></i>
                                                </button>
                                            @endperm
                                            @if (($row->balancing_times ?? 0) > 0)
                                                <button type="button" class="mi-act-count btn-mi-history" title="Xem lịch sử cân đối"
                                                    data-id="{{ $row->id }}" data-code="{{ $row->code }}" data-name="{{ $row->material_name }}"
                                                    data-imported="{{ $invNum($row->imported) }}" data-balanced="{{ $invNum($row->balanced) }}"
                                                    data-gap="{{ $invNum($row->gap) }}">{{ $row->balancing_times }}</button>
                                            @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ THEO VẬT TƯ ============ --}}
                <div class="mi-pane" id="miPaneMat">
                    <div class="table-responsive">
                        <table id="miSummaryTable" class="table table-bordered table-hover w-100 md-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:45px">STT</th>
                                    <th>Vật Tư</th>
                                    <th class="text-center" style="width:90px">Số Lô</th>
                                    <th class="text-right" style="width:100px">Tồn Đầu Kỳ</th>
                                    <th class="text-right" style="width:100px">Nhập Trong Kỳ</th>
                                    <th class="text-right" style="width:100px">Sử Dụng</th>
                                    <th class="text-right" style="width:90px">Loại Bỏ</th>
                                    <th class="text-right" style="width:110px">Tồn Cuối Kỳ</th>
                                    <th class="text-center" style="width:100px">Hạn Gần Nhất</th>
                                    <th class="text-center" style="width:90px">Cần Chú Ý</th>
                                    <th class="text-center inv-chart-th" style="width:115px">Biểu Đồ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summaries as $s)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $s->material_name ?: '—' }}</div>
                                            <div class="md-sub small text-muted">
                                                {{ $s->manufacturer_short_name ?: '' }}
                                                @if ($s->technical_specification) · {{ $s->technical_specification }} @endif
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $s->in_stock_count }}/{{ $s->code_count }}</td>
                                        <td class="text-right">{{ $invNum($s->opening) }}</td>
                                        <td class="text-right">{{ $invNum($s->period_in) }}</td>
                                        <td class="text-right">{{ $invNum($s->period_used) }}</td>
                                        <td class="text-right">{{ $invNum($s->period_cancelled) }}</td>
                                        <td class="text-right font-weight-bold">{{ $invNum($s->closing) }} <span class="md-sub">{{ $s->unit }}</span></td>
                                        <td class="text-center md-sub">{{ $invDate($s->nearest_expiry) }}</td>
                                        <td class="text-center">
                                            @if ($s->alert_count > 0)
                                                <span class="badge badge-warning">{{ $s->alert_count }}</span>
                                            @else <span class="text-muted">—</span> @endif
                                        </td>
                                        {{-- Mở biểu đồ nhập - xuất - tồn của đúng vật tư này, theo kỳ đang xem --}}
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-inv-chart"
                                                data-category="{{ $s->category_id }}"
                                                data-chem="{{ $s->material_name }}"
                                                title="Xem biểu đồ nhập - xuất - tồn của vật tư này">
                                                <i class="fas fa-chart-bar mr-1"></i> Biểu đồ
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ THEO VỊ TRÍ (SƠ ĐỒ THẺ) ============ --}}
                <div class="mi-pane" id="miPaneZone">
                    @include('pages.inventory.MaterialInventory.zoneMap')
                </div>

                {{-- ============ KIỂM KÊ ĐỊNH KỲ (1 THÁNG 1 LẦN) ============ --}}
                <div class="mi-pane" id="miPaneCheck">
                    @include('pages.inventory.MaterialInventory.stocktake')
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tabs
        $('.mi-tab').on('click', function () {
            $('.mi-tab').removeClass('is-active');
            $(this).addClass('is-active');
            $('.mi-pane').removeClass('is-active');
            $('#' + $(this).data('pane')).addClass('is-active');
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });

        // Lọc theo trạng thái
        var miState = '';
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'mdTable' || miState === '') return true;
            var tr = settings.aoData[dataIndex].nTr;
            return tr && tr.getAttribute('data-state') === miState;
        });
        $('.mi-chip').on('click', function () {
            $('.mi-chip').removeClass('is-active');
            $(this).addClass('is-active');
            miState = $(this).data('state') || '';
            if ($.fn.DataTable.isDataTable('#mdTable')) $('#mdTable').DataTable().draw();
        });

        // Modal cân đối
        $(document).on('click', '.btn-mi-balancing', function () {
            var r = $(this).data('row') || {};
            var $f = $('#balancingModal form');
            $f.find('[name="import_id"]').val(r.id);
            $f.find('[name="current_gap"]').val(r.gap);
            $f.find('.mi-code-view').val(r.code);
            $f.find('.mi-mat-view').val(r.material_name);
            $f.find('.mi-imported-view').val(r.imported);
            $f.find('.mi-gap-view').val(r.gap + ' ' + (r.unit || ''));
            $f.find('.mi-limit-view').val('±' + r.balancing_limit + ' ' + (r.unit || ''));
            $f.find('.mi-limit-hint').text('Lần này nhập từ ' + r.balancing_min_input + ' đến ' + r.balancing_max_input + '.');
            $f.find('[name="balancing_amount"]').attr('min', r.balancing_min_input).attr('max', r.balancing_max_input).val('');
            $('#balancingModal').modal('show');
        });

        // Modal lịch sử cân đối
        var miBalMap = @json($invBalancingMap);
        $(document).on('click', '.btn-mi-history', function () {
            var id = $(this).data('id');
            var rows = miBalMap[id] || [];
            $('#balancingHistoryModal .mi-hist-code').text($(this).data('code'));
            $('#balancingHistoryModal .mi-hist-name').text($(this).data('name'));
            $('#balancingHistoryModal .mi-hist-imported').text($(this).data('imported'));
            $('#balancingHistoryModal .mi-hist-balanced').text($(this).data('balanced'));
            $('#balancingHistoryModal .mi-hist-gap').text($(this).data('gap'));
            var $tb = $('#balancingHistoryModal tbody').empty();
            if (!rows.length) {
                $tb.append('<tr><td colspan="4" class="text-center text-muted">Chưa có lần cân đối nào.</td></tr>');
            } else {
                rows.forEach(function (x, i) {
                    var $tr = $('<tr></tr>');
                    $tr.append($('<td class="text-center"></td>').text(i + 1));
                    $tr.append($('<td class="text-right font-weight-bold"></td>').text((x.balancing_amount > 0 ? '+' : '') + x.balancing_amount));
                    $tr.append($('<td></td>').text(x.balancing_by || 'NA'));
                    $tr.append($('<td></td>').text(x.balancing_at));
                    $tb.append($tr);
                });
            }
            $('#balancingHistoryModal').modal('show');
        });

        /* ---------- Sơ đồ tồn theo vị trí ---------- */
        var mzIndex = @json($zoneMap['index']);
        var mzState = '';

        // Lọc ô vị trí theo tình trạng + từ khoá, rồi ẩn luôn kệ/phòng/kho không còn ô nào
        function mzApply() {
            var q = ($('#mzSearch').val() || '').toString().trim().toLowerCase();

            $('#miPaneZone .mz-cell[data-state]').each(function () {
                var state = $(this).data('state');
                var okState = mzState === ''
                    || (mzState === 'empty' && state === 'empty')
                    || (mzState === 'stocked' && state !== 'empty')
                    || (mzState === 'alert' && (state === 'warn' || state === 'danger'));
                var okText = q === '' || ($(this).data('search') || '').toString().indexOf(q) >= 0;
                $(this).css('display', okState && okText ? '' : 'none');
            });

            $('#miPaneZone .mz-shelf').each(function () {
                $(this).css('display', $(this).find('.mz-cell:visible').length ? '' : 'none');
            });
            $('#miPaneZone .mz-room').each(function () {
                $(this).css('display', $(this).find('.mz-shelf:visible').length ? '' : 'none');
            });
            $('#miPaneZone .mz-wh').each(function () {
                $(this).css('display', $(this).find('.mz-room:visible').length ? '' : 'none');
            });

            var total = $('#miPaneZone .mz-wh').length;
            var shown = $('#miPaneZone .mz-wh:visible').length;
            $('#miPaneZone .mz-noresult').css('display', total > 0 && shown === 0 ? 'block' : 'none');
        }

        $('#miPaneZone .mz-filter').on('click', function () {
            $('#miPaneZone .mz-filter').removeClass('is-active');
            $(this).addClass('is-active');
            mzState = $(this).data('state') || '';
            mzApply();
        });
        $('#mzSearch').on('input', mzApply);

        // Thu gọn / mở từng kho
        $(document).on('click', '#miPaneZone .mz-wh-head', function () {
            $(this).closest('.mz-wh').toggleClass('is-closed');
        });
        $('#mzToggleAll').on('click', function () {
            var closeAll = $('#miPaneZone .mz-wh').not('.is-closed').length > 0;
            $('#miPaneZone .mz-wh').toggleClass('is-closed', closeAll);
            $(this).html(closeAll
                ? '<i class="fas fa-expand mr-1"></i> Mở tất cả'
                : '<i class="fas fa-compress mr-1"></i> Thu gọn tất cả');
        });

        // Bấm một ô vị trí -> xem đủ các mã đang nằm ở đó
        $(document).on('click', '#miPaneZone .mz-cell[data-key]', function () {
            var d = mzIndex[$(this).data('key')];
            if (!d) return;

            $('#zoneDetailModal .mz-d-name').text(d.code || '—');
            $('#zoneDetailModal .mz-d-path').text(d.path || '—');
            $('#zoneDetailModal .mz-d-code').text(d.code || '—');
            $('#zoneDetailModal .mz-d-lots').text(d.lots);
            $('#zoneDetailModal .mz-d-materials').text(d.materials);

            var $tb = $('#zoneDetailModal tbody').empty();
            if (!d.items || !d.items.length) {
                $tb.append('<tr><td colspan="6" class="text-center text-muted">Vị trí này đang trống.</td></tr>');
            } else {
                d.items.forEach(function (x, i) {
                    var $tr = $('<tr></tr>');
                    $tr.append($('<td class="text-center"></td>').text(i + 1));
                    $tr.append($('<td class="font-weight-bold"></td>').text(x.code));
                    var $mat = $('<td></td>').append($('<div class="font-weight-bold"></div>').text(x.material_name));
                    if (x.sub) $mat.append($('<div class="md-sub small text-muted"></div>').text(x.sub));
                    $tr.append($mat);
                    $tr.append($('<td class="text-right font-weight-bold"></td>').text(x.remaining + ' ' + (x.unit || '')));
                    $tr.append($('<td class="text-center md-sub"></td>').text(x.expired_date));
                    $tr.append($('<td class="text-center"></td>').append(
                        $('<span></span>').addClass('mi-badge ' + x.state).text(x.state_label)
                    ));
                    $tb.append($tr);
                });
            }
            $('#zoneDetailModal').modal('show');
        });

        if ($.fn.DataTable.isDataTable('#mdTable')) $('#mdTable').DataTable().order([1, 'asc']).draw();
    });
</script>
