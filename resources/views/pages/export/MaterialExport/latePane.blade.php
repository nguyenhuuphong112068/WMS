{{--
| SỬ DỤNG VẬT TƯ - TAB "CẤP PHÁT TRỄ HẠN"
|
| Thống kê các dòng đề nghị được cấp đủ SAU ngày mong muốn (hoặc tới nay vẫn chưa cấp đủ
| mà đã quá ngày), theo THÁNG LẬP PHIẾU đề nghị. Nguyên nhân do Controller suy ra:
| duyệt trễ / thiếu tồn / cấp phát trễ - xem MaterialExportController::lateIssueData().
|
| Dữ liệu vào: $lateMonth, $lateRows, $lateTotal, $lateCauseCounts.
--}}

@php
    $lateTypeLabel = ['periodic' => 'Định kỳ', 'risk_assessment' => 'ĐG rủi ro', 'regular' => 'Thường quy'];

    $lateCauseMeta = [
        'approve' => ['label' => 'Duyệt trễ', 'icon' => 'fas fa-file-signature', 'class' => 'late-cause-approve'],
        'stock' => ['label' => 'Thiếu tồn', 'icon' => 'fas fa-box-open', 'class' => 'late-cause-stock'],
        'issue' => ['label' => 'Cấp phát trễ', 'icon' => 'fas fa-dolly', 'class' => 'late-cause-issue'],
    ];

    // Giữ tham số lọc của các tab khác khi đổi tháng
    $lateKeep = collect(request()->query())
        ->reject(fn($value, $key) => is_array($value) || in_array($key, ['tab', 'late_month'], true))
        ->all();
@endphp

<style>
    /* ---------- Cấp phát trễ hạn ---------- */
    .late-month-form {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .late-month-form label {
        margin: 0;
        font-weight: 600;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .late-month-form input[type="month"] {
        width: 170px;
    }

    .late-stat {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md, 8px);
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-weight: 600;
    }

    .late-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border: 1px solid transparent;
        border-radius: var(--border-radius-md, 8px);
        background: #fff;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .late-filter:hover {
        transform: translateY(-1px);
    }

    .late-filter .late-filter-count {
        min-width: 22px;
        padding: 0 6px;
        border-radius: 999px;
        font-size: 0.78rem;
        text-align: center;
        color: #fff;
    }

    .late-cause-approve { --late-color: var(--primary); --late-bg: var(--primary-soft); }
    .late-cause-stock { --late-color: #B45309; --late-bg: #FFFBEB; }
    .late-cause-issue { --late-color: #DC2626; --late-bg: #FEF2F2; }

    .late-filter.late-cause-approve,
    .late-filter.late-cause-stock,
    .late-filter.late-cause-issue {
        border-color: var(--late-color);
        color: var(--late-color);
    }

    .late-filter .late-filter-count {
        background: var(--late-color);
    }

    .late-filter.is-on {
        background: var(--late-color);
        color: #fff;
    }

    .late-filter.is-on .late-filter-count {
        background: #fff;
        color: var(--late-color);
    }

    .late-cause {
        padding: 3px 0;
        font-size: 0.78rem;
        line-height: 1.5;
    }

    .late-cause+.late-cause {
        border-top: 1px dashed var(--primary-lighter);
    }

    .late-cause .late-cause-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 1px 8px;
        border-radius: 999px;
        background: var(--late-bg);
        color: var(--late-color);
        font-weight: 700;
        font-size: 0.72rem;
    }

    .late-cause .late-cause-note {
        color: #64748B;
    }

    .late-days {
        font-size: 1rem;
        font-weight: 700;
        color: #B91C1C;
    }
</style>

<div class="md-toolbar">
    <form method="GET" action="{{ route($expRoute . 'list') }}" class="late-month-form">
        <input type="hidden" name="tab" value="late">
        @foreach ($lateKeep as $lateKeepName => $lateKeepValue)
            <input type="hidden" name="{{ $lateKeepName }}" value="{{ $lateKeepValue }}">
        @endforeach
        <label for="lateMonthInput">Tháng lập đề nghị</label>
        <input type="month" id="lateMonthInput" name="late_month" class="form-control" value="{{ $lateMonth }}"
            max="{{ now()->format('Y-m') }}" onchange="this.form.submit()">
    </form>

    <span class="late-stat" title="Số mục trễ / tổng số mục có ngày mong muốn của các đề nghị lập trong tháng">
        <i class="fas fa-hourglass-end"></i>
        Trễ {{ $lateRows->count() }}/{{ $lateTotal }} mục
        @if ($lateTotal)
            ({{ round($lateRows->count() * 100 / $lateTotal, 1) }}%)
        @endif
    </span>

    @foreach ($lateCauseMeta as $causeKey => $meta)
        <button type="button" class="late-filter {{ $meta['class'] }}" data-late-cause="{{ $causeKey }}"
            title="Chỉ hiện mục có nguyên nhân {{ mb_strtolower($meta['label']) }}">
            <i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}
            <span class="late-filter-count">{{ $lateCauseCounts[$causeKey] ?? 0 }}</span>
        </button>
    @endforeach
</div>

<div class="table-responsive">
    <table id="meLateTable" class="table table-bordered table-hover w-100 md-table">
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th style="width:170px">Phiếu Đề Nghị</th>
                <th>Vật Tư</th>
                <th class="text-right" style="width:110px">Đề Nghị / Đã Cấp</th>
                <th class="text-center" style="width:100px">Ngày Mong Muốn</th>
                <th class="text-center" style="width:100px">Ngày Duyệt</th>
                <th class="text-center" style="width:110px">Ngày Cấp Phát</th>
                <th class="text-center" style="width:70px">Trễ (Ngày)</th>
                <th style="width:300px">Nguyên Nhân</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lateRows as $row)
                <tr data-late-causes="{{ implode(' ', array_keys($row->causes)) }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td data-order="{{ $row->request_code }}">
                        <span class="exp-code font-weight-bold">{{ $row->request_code }}</span>
                        <div class="md-sub small">{{ $lateTypeLabel[$row->request_type] ?? $row->request_type }}</div>
                        <div class="md-sub small text-muted">{{ $row->request_created_by }} · {{ $expDate($row->request_created_at) }}</div>
                    </td>
                    <td>
                        <div class="font-weight-bold">{{ $row->name ?: '—' }}</div>
                        @if ($row->category_code)
                            <span class="md-tag">{{ $row->category_code }}</span>
                        @endif
                        @if ($row->specification)
                            <div class="md-sub small text-muted">{{ $row->specification }}</div>
                        @endif
                    </td>
                    <td class="text-right">
                        {{ $expNum($row->requested) }} / {{ $expNum($row->issued) }}
                        <span class="md-sub">{{ $row->unit }}</span>
                    </td>
                    <td class="text-center" data-order="{{ $row->needed_date }}">{{ $expDate($row->needed_date) }}</td>
                    <td class="text-center" data-order="{{ $row->approved_at }}">
                        @if ($row->approved_at)
                            {{ $expDate($row->approved_at) }}
                        @else
                            <span class="badge badge-warning">Chờ ký duyệt</span>
                        @endif
                    </td>
                    <td class="text-center" data-order="{{ $row->last_issue?->format('Y-m-d H:i:s') }}">
                        @if ($row->done)
                            {{ $row->last_issue->format('d/m/Y') }}
                            @if ($row->first_issue && $row->first_issue->toDateString() !== $row->last_issue->toDateString())
                                <div class="md-sub small text-muted">Đợt đầu {{ $row->first_issue->format('d/m/Y') }}</div>
                            @endif
                        @elseif ($row->first_issue)
                            <span class="badge badge-info">Cấp một phần</span>
                            <div class="md-sub small text-muted">Đợt đầu {{ $row->first_issue->format('d/m/Y') }}</div>
                        @else
                            <span class="badge badge-danger">Chưa cấp</span>
                        @endif
                    </td>
                    <td class="text-center" data-order="{{ $row->late_days }}">
                        <span class="late-days">{{ $row->late_days }}</span>
                    </td>
                    <td>
                        @foreach ($row->causes as $causeKey => $note)
                            <div class="late-cause {{ $lateCauseMeta[$causeKey]['class'] }}">
                                <span class="late-cause-tag">
                                    <i class="{{ $lateCauseMeta[$causeKey]['icon'] }}"></i> {{ $lateCauseMeta[$causeKey]['label'] }}
                                </span>
                                <span class="late-cause-note">{{ $note }}</span>
                            </div>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">Không có vật tư nào cấp phát trễ trong tháng này.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var table = $.fn.DataTable.isDataTable('#meLateTable') ? $('#meLateTable').DataTable() : null;

        if (!table) return;

        // Trễ nhiều nhất lên đầu
        table.order([7, 'desc']).draw();

        // Lọc theo nguyên nhân - bật nhiều nút thì hiện mục dính BẤT KỲ nguyên nhân nào đang bật
        var picked = {};

        $.fn.dataTable.ext.search.push(function (settings, data, index) {
            if (settings.nTable.id !== 'meLateTable') return true;

            var keys = Object.keys(picked).filter(function (k) { return picked[k]; });
            if (!keys.length) return true;

            var causes = ($(settings.aoData[index].nTr).attr('data-late-causes') || '').split(' ');

            return keys.some(function (k) { return causes.indexOf(k) !== -1; });
        });

        $(document).on('click', '.late-filter', function () {
            var key = $(this).data('late-cause');

            picked[key] = !picked[key];
            $(this).toggleClass('is-on', picked[key]);
            table.draw();
        });
    });
</script>
