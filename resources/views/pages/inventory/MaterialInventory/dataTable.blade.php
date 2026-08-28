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
                    </span>
                </form>

                <div class="mi-tabs">
                    <button type="button" class="mi-tab is-active" data-pane="miPaneCode"><i class="fas fa-boxes mr-1"></i>Theo mã lô</button>
                    <button type="button" class="mi-tab" data-pane="miPaneMat"><i class="fas fa-layer-group mr-1"></i>Theo vật tư</button>
                </div>

                {{-- ============ THEO MÃ LÔ ============ --}}
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
                                    <th style="width:150px">Mã Lô</th>
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
                                            @if ($row->location_name)
                                                {{ $row->location_name }} <span class="md-tag">{{ $row->location_code }}</span>
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
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-mi-balancing" title="Cân đối"
                                                data-row="{{ json_encode([
                                                    'id' => $row->id, 'code' => $row->code, 'material_name' => $row->material_name,
                                                    'unit' => $row->unit_short_name ?: $row->unit_name,
                                                    'imported' => $row->imported, 'gap' => $row->gap,
                                                    'balancing_limit' => $row->balancing_limit,
                                                    'balancing_min_input' => $row->balancing_min_input,
                                                    'balancing_max_input' => $row->balancing_max_input,
                                                ]) }}">
                                                <i class="fas fa-scale-balanced"></i>
                                            </button>
                                            @if (($row->balancing_times ?? 0) > 0)
                                                <button type="button" class="btn btn-xs btn-light border btn-mi-history" title="Xem lịch sử cân đối"
                                                    data-id="{{ $row->id }}" data-code="{{ $row->code }}" data-name="{{ $row->material_name }}"
                                                    data-imported="{{ $invNum($row->imported) }}" data-balanced="{{ $invNum($row->balanced) }}"
                                                    data-gap="{{ $invNum($row->gap) }}">{{ $row->balancing_times }}</button>
                                            @endif
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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

        if ($.fn.DataTable.isDataTable('#mdTable')) $('#mdTable').DataTable().order([1, 'asc']).draw();
    });
</script>
