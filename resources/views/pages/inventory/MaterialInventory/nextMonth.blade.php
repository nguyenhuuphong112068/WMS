{{--
|--------------------------------------------------------------------------
| TỒN - VẬT TƯ | TAB "KHẢ DỤNG THÁNG TỚI"
|--------------------------------------------------------------------------
| Dữ liệu do App\Support\MaterialAvailabilityForecast::build() dựng, đưa vào
| view bằng biến $forecast (xem MaterialInventoryController::index).
|
|   Khả dụng = Tồn hiện hành - Đã đề nghị chưa cấp phát - Chu kỳ chưa tạo đề nghị
|   Thiếu/Dư = Khả dụng - Nhu cầu theo chu kỳ của tháng đang xét
|
| Chỉ hiện mã CÓ nhu cầu theo chu kỳ trong tháng đang xét.
--}}

<style>
    .mf-bar { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
    .mf-bar label { display: block; margin-bottom: 4px; font-size: .78rem; font-weight: 700;
        color: var(--primary); letter-spacing: .4px; text-transform: uppercase; }
    .mf-bar select { min-width: 190px; }
    .mf-num { font-weight: 700; }
    .mf-num.is-out { color: #DC2626; }
    .mf-num.is-in { color: #16A34A; }
    .mf-num.is-muted { color: #94A3B8; font-weight: 600; }
    .mf-badge { display: inline-block; border-radius: 999px; padding: 2px 10px; font-size: .74rem; font-weight: 700; }
    .mf-badge.short { background: #FEE2E2; color: #991B1B; }
    .mf-badge.tight { background: #FEF9C3; color: #854D0E; }
    .mf-badge.ok { background: #DCFCE7; color: #166534; }
    .mf-unit { font-size: .74rem; color: #64748B; font-weight: 600; margin-left: 3px; }
    .mf-src-type { display: inline-block; border-radius: var(--border-radius-md); padding: 1px 8px;
        font-size: .72rem; font-weight: 700; background: var(--primary-soft); color: var(--primary); }
    .mf-src-type.external { background: #FFEDD5; color: #9A3412; }
</style>

@php
    $mfMonth = $forecast['month'];
    $mfRows = $forecast['rows'];

    // [category_id => {nhu cầu tháng đang xét, phần chu kỳ chưa tạo đề nghị của tháng này}]
    $mfDetail = $mfRows->mapWithKeys(fn($r) => [
        $r->category_id => [
            'code' => $r->category_code,
            'name' => $r->material_name,
            'unit' => $r->unit_short_name,
            'stock' => $invNum($r->stock),
            'reserved' => $invNum($r->reserved),
            'pending' => $invNum($r->pending),
            'available' => $invNum($r->available),
            'demand' => $invNum($r->demand),
            'gap' => $invNum($r->gap),
            'sources' => collect($r->sources)->map(fn($s) => $s + ['amount_text' => $invNum($s['amount']), 'per_run_text' => $invNum($s['per_run'])])->values(),
            'pending_sources' => collect($r->pending_sources)->map(fn($s) => $s + ['amount_text' => $invNum($s['amount']), 'per_run_text' => $invNum($s['per_run'])])->values(),
        ],
    ]);
@endphp

<form method="GET" action="{{ route('pages.inventory.materialInventory.list') }}" class="mf-bar" id="mfMonthForm">
    <input type="hidden" name="from_date" value="{{ $period['from'] }}">
    <input type="hidden" name="to_date" value="{{ $period['to'] }}">
    <input type="hidden" name="tab" value="forecast">

    <div>
        <label for="mfMonth">Tháng cần kiểm tra</label>
        <select name="forecast_month" id="mfMonth" class="form-control form-control-sm">
            @foreach ($forecast['options'] as $option)
                <option value="{{ $option['value'] }}" @selected($option['active'])>{{ $option['label'] }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary btn-sm mb-0">
        <i class="fas fa-sync-alt mr-1"></i> Kiểm tra
    </button>
</form>

<div class="mb-2">
    <span class="mi-chip is-active" data-fstate="">Tất cả ({{ $forecast['summary']['total'] }})</span>
    @foreach ($forecast['states'] as $key => $label)
        @if ($forecast['summary'][$key] > 0)
            <span class="mi-chip" data-fstate="{{ $key }}">{{ $label }} ({{ $forecast['summary'][$key] }})</span>
        @endif
    @endforeach
</div>

<div class="table-responsive">
    <table id="mfTable" class="table table-bordered table-hover w-100 md-table">
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th style="width:130px">Mã Vật Tư</th>
                <th>Vật Tư</th>
                <th class="text-right" style="width:100px"
                    title="Tồn của phòng theo công thức màn Tồn Kho, không tính lô đã hết hạn">Tồn Hiện Hành</th>
                <th class="text-right" style="width:110px"
                    title="Đã có đề nghị nhưng kho chưa cấp phát: phiếu nội bộ Lưu tạm / chờ ký / đã duyệt chưa cấp đủ, và đề nghị liên phòng ban gửi tới phòng">
                    Đã Đề Nghị Chưa Cấp</th>
                <th class="text-right" style="width:120px"
                    title="Phần theo chu kỳ còn phát sinh từ hôm nay tới {{ \Carbon\Carbon::parse($forecast['pre']['to'])->format('d/m/Y') }} mà hệ thống chưa tạo đề nghị">
                    Chu Kỳ Chưa Tạo ĐN</th>
                <th class="text-right" style="width:100px"
                    title="Tồn hiện hành - Đã đề nghị chưa cấp - Chu kỳ chưa tạo đề nghị">Khả Dụng</th>
                <th class="text-right" style="width:110px"
                    title="Tổng số lượng các danh sách đề nghị theo chu kỳ sẽ đề nghị trong {{ $mfMonth['label'] }}">
                    Nhu Cầu {{ $mfMonth['label'] }}</th>
                <th class="text-right" style="width:100px" title="Khả dụng - Nhu cầu; số âm là thiếu">Thiếu / Dư</th>
                <th class="text-right" style="width:110px"
                    title="Lượng nên dự trù để đủ nhu cầu tháng đang xét và giữ được ngưỡng tồn tối thiểu">
                    Cần Dự Trù</th>
                <th class="text-center" style="width:110px">Trạng Thái</th>
                <th class="text-center" style="width:70px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mfRows as $row)
                <tr data-fstate="{{ $row->state }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td><span class="md-tag">{{ $row->category_code ?: '—' }}</span></td>
                    <td>
                        <div class="font-weight-bold">{{ $row->material_name ?: '—' }}</div>
                        <div class="md-sub small text-muted">
                            {{ $row->manufacturer_short_name ?: '' }}
                            @if ($row->technical_specification) · {{ $row->technical_specification }} @endif
                        </div>
                    </td>
                    <td class="text-right" data-order="{{ $row->stock }}">
                        <span class="mf-num {{ $row->stock > 0 ? '' : 'is-muted' }}">{{ $invNum($row->stock) }}</span>
                    </td>
                    <td class="text-right" data-order="{{ $row->reserved }}">
                        <span class="mf-num {{ $row->reserved > 0 ? 'is-out' : 'is-muted' }}">{{ $invNum($row->reserved) }}</span>
                    </td>
                    <td class="text-right" data-order="{{ $row->pending }}">
                        <span class="mf-num {{ $row->pending > 0 ? 'is-out' : 'is-muted' }}">{{ $invNum($row->pending) }}</span>
                    </td>
                    <td class="text-right" data-order="{{ $row->available }}">
                        <span class="mf-num {{ $row->available > 0 ? '' : 'is-out' }}">{{ $invNum($row->available) }}</span>
                        <span class="mf-unit">{{ $row->unit_short_name }}</span>
                    </td>
                    <td class="text-right" data-order="{{ $row->demand }}">
                        <span class="mf-num">{{ $invNum($row->demand) }}</span>
                        @if ($row->unit_mixed)
                            <i class="fas fa-exclamation-triangle text-warning ml-1"
                                title="Có dòng chu kỳ khai đơn vị khác đơn vị của phòng - số cộng lại chỉ để tham khảo"></i>
                        @endif
                    </td>
                    <td class="text-right" data-order="{{ $row->gap }}">
                        <span class="mf-num {{ $row->gap < 0 ? 'is-out' : 'is-in' }}">{{ $invNum($row->gap) }}</span>
                    </td>
                    <td class="text-right" data-order="{{ $row->suggest }}">
                        @if ($row->suggest > 0)
                            <span class="mf-num is-out">{{ $invNum($row->suggest) }}</span>
                            <span class="mf-unit">{{ $row->unit_short_name }}</span>
                        @else
                            <span class="mf-num is-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="mf-badge {{ $row->state }}">{{ $row->state_label }}</span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="mi-act-btn btn-mf-detail" data-id="{{ $row->category_id }}"
                            title="Xem các danh sách chu kỳ tạo ra nhu cầu này">
                            <i class="fas fa-tasks"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center text-muted">
                        {{ $mfMonth['label'] }} chưa có danh sách vật tư đề nghị theo chu kỳ nào phát sinh.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var mfDetail = @json($mfDetail);
        var mfMonthLabel = @json($mfMonth['label']);
        var mfPreLabel = @json(\Carbon\Carbon::parse($forecast['pre']['from'])->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($forecast['pre']['to'])->format('d/m/Y'));

        // Giữ nguyên thứ tự Controller đã xếp (mã không đáp ứng lên trước)
        if ($.fn.DataTable.isDataTable('#mfTable')) $('#mfTable').DataTable().order([]).draw();

        /* ---------- Lọc theo trạng thái đáp ứng ---------- */
        var mfState = '';
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'mfTable' || mfState === '') return true;
            var tr = settings.aoData[dataIndex].nTr;
            return tr && tr.getAttribute('data-fstate') === mfState;
        });
        $('#miPaneNext').on('click', '.mi-chip', function () {
            $('#miPaneNext .mi-chip').removeClass('is-active');
            $(this).addClass('is-active');
            mfState = $(this).data('fstate') || '';
            if ($.fn.DataTable.isDataTable('#mfTable')) $('#mfTable').DataTable().draw();
        });

        /* ---------- Chi tiết nguồn nhu cầu của một mã ---------- */
        function mfSourceRows($tb, sources, empty) {
            if (!sources || !sources.length) {
                $tb.append($('<tr></tr>').append($('<td colspan="6" class="text-center text-muted"></td>').text(empty)));
                return;
            }

            sources.forEach(function (s, i) {
                var $tr = $('<tr></tr>');
                $tr.append($('<td class="text-center"></td>').text(i + 1));

                var $name = $('<td></td>').append($('<div class="font-weight-bold"></div>').text(s.title || '—'));
                var sub = [];
                if (s.object) sub.push(s.object);
                if (s.type === 'external' && s.department) sub.push('Phòng đề nghị: ' + s.department);
                if (sub.length) $name.append($('<div class="md-sub small text-muted"></div>').text(sub.join(' · ')));
                $tr.append($name);

                $tr.append($('<td class="text-center"></td>').append(
                    $('<span></span>').addClass('mf-src-type ' + s.type).text(s.type === 'external' ? 'Liên phòng ban' : 'Nội bộ')
                ));
                $tr.append($('<td class="text-center"></td>').text(s.cycle + (s.cal ? ' (lịch CAL)' : '')));
                $tr.append($('<td class="text-center"></td>').text(s.runs + ' × ' + s.per_run_text + ' ' + (s.unit || '')));
                $tr.append($('<td class="text-right font-weight-bold"></td>').text(s.amount_text));
                $tb.append($tr);
            });
        }

        $(document).on('click', '.btn-mf-detail', function () {
            var d = mfDetail[$(this).data('id')];
            if (!d) return;

            var unit = d.unit || '';
            $('#mfDetailModal .mf-d-code').text(d.code || '—');
            $('#mfDetailModal .mf-d-name').text(d.name || '—');
            $('#mfDetailModal .mf-d-stock').text(d.stock + ' ' + unit);
            $('#mfDetailModal .mf-d-reserved').text(d.reserved + ' ' + unit);
            $('#mfDetailModal .mf-d-pending').text(d.pending + ' ' + unit);
            $('#mfDetailModal .mf-d-available').text(d.available + ' ' + unit);
            $('#mfDetailModal .mf-d-demand').text(d.demand + ' ' + unit);
            $('#mfDetailModal .mf-d-gap').text(d.gap + ' ' + unit);
            $('#mfDetailModal .mf-d-month').text(mfMonthLabel);
            $('#mfDetailModal .mf-d-pre-range').text(mfPreLabel);

            mfSourceRows($('#mfDetailModal .mf-d-demand-body').empty(), d.sources, 'Không có danh sách nào.');
            mfSourceRows($('#mfDetailModal .mf-d-pending-body').empty(), d.pending_sources,
                'Từ nay tới hết tháng này không còn lần tạo đề nghị nào.');

            $('#mfDetailModal').modal('show');
        });
    });
</script>
