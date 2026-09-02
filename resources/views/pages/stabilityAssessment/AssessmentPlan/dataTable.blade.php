@include('pages.stabilityAssessment.AssessmentPlan.assets')

<div class="content-wrapper">
    <div class="md-page plan-page">

        {{--
        | ============ THANH ĐIỀU KHIỂN KỲ KẾ HOẠCH ============
        |
        | Gộp làm một khối: chọn khoảng thời gian + mốc chọn nhanh + đường sang danh
        | sách phiếu, bên dưới là dải số liệu của kỳ. Trước đây thanh chọn khoảng và
        | 4 ô tổng quan nằm hai hàng riêng, đẩy bảng dữ liệu xuống quá sâu.
        --}}
        <div class="plan-head">
            <form method="GET" action="{{ route($planRoute . 'list') }}" class="plan-filter" id="planPeriodForm">
                <div class="plan-field">
                    <label for="planFromDate"><i class="fas fa-calendar-days"></i> Từ ngày</label>
                    <input type="date" id="planFromDate" name="from_date" class="form-control"
                        value="{{ $period['from'] }}">
                </div>

                <div class="plan-field">
                    <label for="planToDate">Đến ngày</label>
                    <input type="date" id="planToDate" name="to_date" class="form-control"
                        value="{{ $period['to'] }}">
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

                <a href="{{ route($ssaRoute . 'list') }}" class="btn btn-secondary btn-sm plan-link">
                    <i class="fas fa-clipboard-list mr-1"></i> Danh sách phiếu
                </a>
            </form>

            {{-- Số liệu của kỳ: gọn lại thành dải nhãn thay cho 4 ô tổng quan cũ --}}
            <div class="plan-summary">
                <span class="plan-stat" title="Khoảng thời gian đang xem">
                    <i class="fas fa-calendar-week"></i> {{ $planLabel }}
                </span>

                <span class="plan-stat" title="Độ dài của khoảng đang xem">
                    <b>{{ count($months) }}</b> tháng · <b>{{ $period['days'] }}</b> ngày
                </span>

                <span class="plan-stat" title="Tổng mốc đánh giá đến hạn trong khoảng đang xem">
                    <b>{{ $datas->count() }}</b> mốc · <b>{{ $planImportCount }}</b> ống chuẩn
                </span>

                <span class="plan-stat {{ $planTodo > 0 ? 'is-todo' : '' }}"
                    title="Quá hạn {{ $stateCounts['overdue'] }} mốc · Sắp đến hạn {{ $stateCounts['due'] }} mốc">
                    <i class="fas fa-list-check"></i> Cần làm <b>{{ $planTodo }}</b>
                </span>

                <span class="plan-stat" title="Chưa tới hạn {{ $stateCounts['waiting'] }} mốc">
                    <i class="fas fa-circle-check"></i> Đã đánh giá <b>{{ $stateCounts['done'] }}</b>
                </span>

                <span class="plan-note">
                    @if ($period['clamped'])
                        <i class="fas fa-scissors"></i> Khoảng chọn dài hơn {{ $period['max_months'] }} tháng nên đã cắt
                        lại đến {{ $planDate($period['to']) }}
                    @elseif ($period['has_today'])
                        <i class="fas fa-circle-check"></i> Khoảng đang xem có bao hôm nay - mốc "Quá hạn" và "Sắp đến
                        hạn" là việc phải làm ngay
                    @else
                        <i class="fas fa-clock-rotate-left"></i> Khoảng đang xem không bao hôm nay - kế hoạch của một kỳ
                        khác
                    @endif
                </span>
            </div>
        </div>

        {{-- Ống chuẩn chưa lập phiếu thì không có mốc nào, chọn khoảng nào cũng không thấy --}}
        @if ($unplanned->count() > 0)
            <div class="plan-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span>
                    <b>{{ $unplanned->count() }} ống {{ $assessGroupName }} ({{ $assessGroupCode }})</b> còn hiệu lực
                    nhưng chưa lập phiếu đánh giá hạn dùng nên chưa có mốc nào trong kế hoạch.
                </span>
                <span class="codes" title="{{ $unplanned->pluck('code')->implode(', ') }}">
                    {{ $unplanned->take(8)->pluck('code')->implode(', ') }}@if ($unplanned->count() > 8)
                        … và {{ $unplanned->count() - 8 }} ống khác
                    @endif
                </span>
                <a href="{{ route($ssaRoute . 'list') }}">Lập phiếu</a>
            </div>
        @endif

        <div class="card md-card">
            <div class="card-body">

                {{--
                | Bộ lọc tình trạng, dải tháng và ghi chú đều mang class .md-tablebar-item
                | nên được layout kéo về CHUNG MỘT HÀNG với "Hiển thị N dòng" / "Tìm kiếm"
                | của DataTables ngay trên bảng, thay vì mỗi thứ chiếm một hàng riêng.
                --}}
                <div class="ssa-filters md-tablebar-item" data-table="#mdTable">
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
                    <div class="plan-months md-tablebar-item" data-table="#mdTable">
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
                    <table id="mdTable" class="table table-bordered table-hover w-100">
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
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Kế hoạch phải đọc theo trình tự thời gian: sắp theo cột 1 = Ngày Đến Hạn
        $('#mdTable').DataTable().order([1, 'asc']).draw();

        var table = $('#mdTable').DataTable();

        table.settings()[0].oLanguage.sEmptyTable =
            'Không có mốc đánh giá nào đến hạn trong khoảng đang chọn. Chọn khoảng thời gian khác hoặc lập thêm phiếu đánh giá.';
        table.draw(false);
    });
</script>
