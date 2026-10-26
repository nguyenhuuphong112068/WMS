{{--
|--------------------------------------------------------------------------
| TỒN KHO CHẤT CHUẨN - TAB "KẾ HOẠCH ĐÁNH GIÁ"
|--------------------------------------------------------------------------
| Xem ngược theo THỜI GIAN: mọi mốc đánh giá của các phiếu còn hiệu lực có ngày đến hạn
| trong khoảng plan_from - plan_to (StandardStabilityController::planData()). Chỉ đọc -
| ghi kết quả ở trang chi tiết phiếu. @include từ inventory/StandardInventory/dataTable với
| mảng $stabilityPlan; khoảng xem riêng, không dùng Kỳ báo cáo của tồn kho.
--}}
@include('pages.inventory.StandardStability.planAssets')

@php
    $ssaRoute = 'pages.inventory.standardStability.';

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $planDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /** Trạng thái phiếu -> lớp CSS của thẻ .ssa-badge (dùng chung với tab Đánh giá hạn dùng CTC). */
    $planStatusClass = fn($status) => match ($status) {
        'Đang Đánh Giá' => 'running',
        'Hoàn Thành' => 'done',
        'Dừng Đánh Giá' => 'stopped',
        'Huỷ' => 'cancelled',
        default => 'initial',
    };

    $planShortByCode = collect($groups)->mapWithKeys(fn($group) => [$group['code'] => $group['short']])->all();
    $planGroupName = fn($code) => $planShortByCode[$code] ?? ($code ?: '—');

    // Số ống chuẩn có mặt trong kế hoạch - một ống có thể có nhiều mốc trong cùng khoảng
    $planImportCount = $datas->pluck('import_code')->unique()->count();

    // Việc thật sự phải làm: mốc chưa có kết quả mà đã quá hạn hoặc sắp đến hạn
    $planTodo = $stateCounts['overdue'] + $stateCounts['due'];

    /*
    | Mốc chọn nhanh - kế hoạch nhìn về phía trước nên lấy TRỌN tháng / quý / năm.
    | "Đã quá hạn" nhìn ngược lại 24 tháng để gom hết mốc còn nợ chưa đánh giá.
    */
    $planToday = \Carbon\Carbon::today();

    $planPresets = collect([
        ['label' => 'Tháng này', 'from' => $planToday->copy()->startOfMonth(), 'to' => $planToday->copy()->endOfMonth()],
        ['label' => 'Quý này', 'from' => $planToday->copy()->startOfQuarter(), 'to' => $planToday->copy()->endOfQuarter()],
        ['label' => '3 tháng tới', 'from' => $planToday->copy()->startOfMonth(), 'to' => $planToday->copy()->addMonthsNoOverflow(3)->endOfMonth()],
        ['label' => '6 tháng tới', 'from' => $planToday->copy()->startOfMonth(), 'to' => $planToday->copy()->addMonthsNoOverflow(6)->endOfMonth()],
        ['label' => 'Năm nay', 'from' => $planToday->copy()->startOfYear(), 'to' => $planToday->copy()->endOfYear()],
        ['label' => 'Đã quá hạn', 'from' => $planToday->copy()->subMonthsNoOverflow(24)->startOfMonth(), 'to' => $planToday->copy()->subDay()],
    ])
        ->map(fn($preset) => ['label' => $preset['label'], 'from' => $preset['from']->format('Y-m-d'), 'to' => $preset['to']->format('Y-m-d')])
        ->map(fn($preset) => $preset + ['active' => $preset['from'] === $period['from'] && $preset['to'] === $period['to']])
        ->all();
@endphp

{{-- Khoảng xem của kế hoạch: gửi kèm tab + Kỳ báo cáo đang xem để quay lại đúng chỗ --}}
<form method="GET" action="{{ route('pages.inventory.standardInventory.list') }}" class="plan-filter" id="planPeriodForm">
    <input type="hidden" name="tab" value="stabilityPlan">
    <input type="hidden" name="from_date" value="{{ request('from_date') }}">
    <input type="hidden" name="to_date" value="{{ request('to_date') }}">

    <div class="plan-field">
        <label for="planFromDate"><i class="fas fa-calendar-days"></i> Từ ngày</label>
        <input type="date" id="planFromDate" name="plan_from" class="form-control" value="{{ $period['from'] }}">
    </div>

    <div class="plan-field">
        <label for="planToDate">Đến ngày</label>
        <input type="date" id="planToDate" name="plan_to" class="form-control" value="{{ $period['to'] }}">
    </div>

    <button type="submit" class="btn btn-primary btn-sm plan-apply">
        <i class="fas fa-search mr-1"></i> Xem
    </button>

    <div class="plan-presets">
        @foreach ($planPresets as $preset)
            <button type="button" class="plan-period-chip {{ $preset['active'] ? 'is-active' : '' }}"
                data-from="{{ $preset['from'] }}" data-to="{{ $preset['to'] }}">
                {{ $preset['label'] }}
            </button>
        @endforeach
    </div>

    <div class="plan-summary">
        <span class="plan-stat" title="{{ $period['clamped'] ? 'Khoảng chọn dài hơn ' . $period['max_months'] . ' tháng nên đã cắt lại' : 'Độ dài của khoảng đang xem' }}">
            <b>{{ count($months) }}</b> tháng · <b>{{ $period['days'] }}</b> ngày
        </span>
        <span class="plan-stat" title="Tổng mốc đánh giá đến hạn trong khoảng đang xem">
            <b>{{ $datas->count() }}</b> mốc · <b>{{ $planImportCount }}</b> ống chuẩn
        </span>
        <span class="plan-stat {{ $planTodo > 0 ? 'is-todo' : '' }}"
            title="Quá hạn {{ $stateCounts['overdue'] }} mốc · Sắp đến hạn {{ $stateCounts['due'] }} mốc">
            <i class="fas fa-list-check"></i> Cần làm <b>{{ $planTodo }}</b>
        </span>
    </div>
</form>

{{--
| Bộ lọc tình trạng, dải tháng và ghi chú đều mang class .md-tablebar-item
| nên được layout kéo về CHUNG MỘT HÀNG với "Hiển thị N dòng" / "Tìm kiếm"
| của DataTables ngay trên bảng, thay vì mỗi thứ chiếm một hàng riêng.
--}}
<div class="ssa-filters md-tablebar-item" data-table="#planTable">
    <button type="button" class="ssa-chip is-active" data-state="all">
        <i class="fas fa-layer-group"></i> Tất cả
        <span class="count">{{ $datas->count() }}</span>
    </button>
    @foreach ($itemStates as $key => $label)
        <button type="button" class="ssa-chip" data-state="{{ $key }}">
            <span class="ssa-state ssa-state-{{ $key }}">{{ $label }}</span>
            <span class="count">{{ $stateCounts[$key] }}</span>
        </button>
    @endforeach
</div>

{{-- Dải thời gian: bấm một tháng để chỉ xem việc của tháng đó, bấm lại để bỏ lọc --}}
@if (count($months) > 1)
    <div class="plan-months md-tablebar-item" data-table="#planTable">
        @foreach ($months as $month)
            <div class="plan-month {{ $month['is_current'] ? 'is-current' : '' }} {{ $month['total'] === 0 ? 'is-empty' : '' }}"
                data-month="{{ $month['key'] }}"
                title="{{ $month['total'] }} mốc đánh giá trong {{ $month['label'] }}">
                <span class="plan-month-label">{{ $month['label'] }}</span>
                <span class="plan-month-total">{{ $month['total'] }}</span>
                @if ($month['overdue'] > 0 || $month['due'] > 0 || $month['done'] > 0)
                    <span class="plan-month-dots">
                        @if ($month['overdue'] > 0)
                            <span class="plan-month-dot overdue"
                                title="Quá hạn">{{ $month['overdue'] }}</span>
                        @endif
                        @if ($month['due'] > 0)
                            <span class="plan-month-dot due"
                                title="Sắp đến hạn">{{ $month['due'] }}</span>
                        @endif
                        @if ($month['done'] > 0)
                            <span class="plan-month-dot done"
                                title="Đã đánh giá">{{ $month['done'] }}</span>
                        @endif
                    </span>
                @endif
            </div>
        @endforeach
    </div>
@endif

<span class="plan-help md-tablebar-item"
    title="Bảng gom mọi mốc đánh giá của các phiếu còn hiệu lực có ngày đến hạn nằm trong khoảng đang chọn. Mốc đến hạn trong {{ $dueSoonDays }} ngày được đánh dấu &quot;Sắp đến hạn&quot;. Ghi kết quả tại trang chi tiết phiếu.">
    <i class="fas fa-info"></i>
</span>

<div class="table-responsive">
    <table id="planTable" class="table table-bordered table-hover w-100 md-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 45px">STT</th>
                <th class="text-center" style="width: 120px"
                    title="Ngày phải đánh giá = ngày bắt đầu của phiếu + số tháng của mốc">
                    Ngày Đến Hạn</th>
                <th style="width: 155px">Mã Ống Chuẩn</th>
                <th>Chất Chuẩn</th>
                <th class="text-center" style="width: 60px"
                    title="Số tháng tính từ ngày bắt đầu đánh giá của phiếu">Mốc</th>
                <th style="width: 130px">Tên Mốc</th>
                <th style="width: 195px">Chỉ Tiêu Kiểm</th>
                <th class="text-center" style="width: 100px">Ngày Thực Hiện</th>
                <th style="width: 175px">Kết Quả</th>
                <th class="text-center" style="width: 110px">Tình Trạng</th>
                <th class="text-center" style="width: 105px"
                    title="Trạng thái của phiếu đánh giá chứa mốc này">Phiếu</th>
                <th class="text-center" style="width: 60px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($datas as $row)
                <tr data-state="{{ $row->state }}" data-month="{{ $row->month_key }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center" data-order="{{ $row->due_date }}">
                        <div class="font-weight-bold">{{ $planDate($row->due_date) }}</div>
                        @if ($row->state !== 'done' && $row->days_to_due !== null)
                            <div class="md-sub">
                                @if ($row->days_to_due < 0)
                                    <span class="text-danger font-weight-bold">Quá
                                        {{ abs($row->days_to_due) }} ngày</span>
                                @else
                                    Còn {{ $row->days_to_due }} ngày
                                @endif
                            </div>
                        @endif
                        {{-- Ống hết hạn trước ngày phải đánh giá thì mốc này gần như không còn ý nghĩa --}}
                        @if ($row->expired_date && substr((string) $row->expired_date, 0, 10) < substr((string) $row->due_date, 0, 10))
                            <span class="plan-expired-warn"
                                title="Ống chuẩn hết hạn ngày {{ $planDate($row->expired_date) }}, trước ngày phải đánh giá">
                                Hết hạn trước
                            </span>
                        @endif
                    </td>
                    <td>
                        <span class="ssa-code">{{ $row->import_code }}</span>
                        <div class="md-sub">
                            <span class="ssa-group-tag">{{ $planGroupName($row->group_code) }}</span>
                            @if ($row->batch_no)
                                <span class="ml-1">Lô {{ $row->batch_no }}</span>
                            @endif
                        </div>
                        <div class="md-sub">HD {{ $planDate($row->expired_date) }}</div>
                    </td>
                    <td>
                        <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                        <div class="md-sub">
                            <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                            @if ($row->category_version)
                                <span class="ml-1">v{{ $row->category_version }}</span>
                            @endif
                            @if ($row->manufacturer_short_name)
                                <span class="ml-1">· {{ $row->manufacturer_short_name }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-center" data-order="{{ $row->timepoint }}">
                        <span class="ssa-timepoint">T{{ $row->timepoint }}</span>
                    </td>
                    <td>
                        <div class="font-weight-bold">{{ $row->name }}</div>
                        <div class="md-sub">Bắt đầu {{ $planDate($row->start_date) }}</div>
                        @if ($row->note)
                            <div class="md-sub">
                                <span class="md-note" title="{{ $row->note }}">{{ $row->note }}</span>
                            </div>
                        @endif
                    </td>
                    <td>
                        @forelse ($row->testing_list as $testing)
                            {{-- Có tick là chuẩn đã cấp phát cho chỉ tiêu đó, mốc đã chuẩn bị được một phần --}}
                            <span class="ssa-testing {{ $testing['issued'] ? 'is-issued' : '' }}"
                                title="{{ $testing['issued'] ? 'Đã cấp phát chuẩn' : 'Chưa cấp phát chuẩn' }}">
                                {{ $testing['name'] }}
                            </span>
                        @empty
                            <span class="md-empty">Chưa chọn chỉ tiêu</span>
                        @endforelse
                    </td>
                    <td class="text-center md-sub" data-order="{{ $row->done_at ?: '9999-12-31' }}">
                        {{ $planDate($row->done_at) }}
                    </td>
                    <td>
                        @if ($row->result)
                            <div>{{ $row->result }}</div>
                            <div class="md-sub">
                                <span
                                    class="{{ $row->status === 'Đạt' ? 'ssa-result-pass' : 'ssa-result-fail' }}">
                                    {{ $row->status }}
                                </span>
                            </div>
                        @else
                            <span class="md-empty">Chưa có kết quả</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="ssa-state ssa-state-{{ $row->state }}">{{ $row->state_label }}</span>
                    </td>
                    <td class="text-center">
                        <span
                            class="ssa-badge {{ $planStatusClass($row->list_status) }}">{{ $row->list_status }}</span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route($ssaRoute . 'detail', ['id' => $row->list_id]) }}"
                            class="btn btn-sm btn-primary"
                            title="Mở phiếu đánh giá của ống chuẩn {{ $row->import_code }}">
                            <i class="fas fa-up-right-from-square"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Kế hoạch phải đọc theo trình tự thời gian: sắp theo cột 1 = Ngày Đến Hạn
        var table = $('#planTable').DataTable();

        table.order([1, 'asc']);

        table.settings()[0].oLanguage.sEmptyTable =
            'Không có mốc đánh giá nào đến hạn trong khoảng đang chọn. Chọn khoảng thời gian khác hoặc lập thêm phiếu đánh giá.';
        table.draw(false);
    });
</script>
