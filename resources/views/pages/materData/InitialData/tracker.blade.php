{{--
| Theo dõi tiến độ khởi tạo dữ liệu dạng danh sách việc: tab theo giai đoạn, nhóm theo phòng ban,
| mỗi việc một dòng (trạng thái lần nộp mới nhất + nút Đính kèm / Tải / Xử lý / Không áp dụng).
| Dữ liệu dựng ở InitialDataController::tracker().
--}}
@php
    $taskStates = [
        'todo' => ['label' => 'Chưa nộp', 'icon' => 'far fa-circle', 'class' => 'is-todo'],
        'pending' => ['label' => 'Chờ xử lý', 'icon' => 'fas fa-hourglass-half', 'class' => 'is-pending'],
        'returned' => ['label' => 'Trả lại', 'icon' => 'fas fa-undo', 'class' => 'is-returned'],
        'done' => ['label' => 'Hoàn thành', 'icon' => 'fas fa-check-circle', 'class' => 'is-done'],
        'skipped' => ['label' => 'Không áp dụng', 'icon' => 'fas fa-minus-circle', 'class' => 'is-skipped'],
    ];
    $taskTitles = [1 => 'Tổ chức: công ty, phòng ban, vai trò', 2 => 'Người dùng & Tổ - Nhóm'];
    $fileState = function ($f) use ($reviewStatuses) {
        if ((int) $f->status_id === 1) {
            return $reviewStatuses[$f->review_status] ?? ['label' => $f->review_status, 'class' => 'secondary'];
        }

        return $f->replaced_by_id ? ['label' => 'Đã thay thế', 'class' => 'light'] : ['label' => 'Đã gỡ', 'class' => 'light'];
    };
    $visibleStages = array_filter($tracker, fn ($s) => $s['companies']);
    // Nút lọc công ty: gom từ mọi giai đoạn (bỏ khối "Toàn hệ thống" của GĐ1)
    $companyFilters = collect($tracker)->pluck('companies')->flatMap(fn ($c) => $c)
        ->filter(fn ($c, $key) => $key !== 'all')->unique('label')->values();
    $activeStage = collect($visibleStages)->search(fn ($s) => $s['done'] < $s['total']) ?: array_key_first($visibleStages);
@endphp

<style>
    .idt-stage-tabs .nav-link {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .idt-stage-tabs .idt-count {
        font-size: 0.75rem;
        border-radius: 20px;
        padding: 2px 8px;
        background: var(--primary-soft);
        color: var(--primary-dark);
    }

    .idt-stage-tabs .idt-count.is-complete {
        background: rgba(22, 163, 74, 0.12);
        color: #16A34A;
    }

    .idt-progress-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin: 16px 0 6px;
    }

    .idt-progress-head .idt-legend span {
        margin-right: 12px;
        font-size: 0.85rem;
        color: var(--text-main);
    }

    .idt-progress {
        height: 10px;
        border-radius: 10px;
        background: var(--primary-soft);
        margin-bottom: 16px;
    }

    .idt-progress .progress-bar {
        background: #16A34A;
        transition: width 0.3s;
    }

    .idt-group {
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-md);
        margin-bottom: 12px;
        overflow: hidden;
    }

    .idt-group-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 14px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-weight: 700;
    }

    .idt-group-head .md-sub {
        font-weight: 600;
    }

    .idt-task {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-top: 1px solid var(--primary-soft);
        transition: background 0.2s;
    }

    .idt-task:first-of-type {
        border-top: 0;
    }

    .idt-task:hover {
        background: var(--bg-neutral);
    }

    .idt-task {
        flex-wrap: wrap;
    }

    .idt-history {
        flex-basis: 100%;
        margin-left: 40px;
        border-left: 3px solid var(--primary-soft);
        padding-left: 12px;
    }

    .idt-history-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        font-size: 0.88rem;
    }

    .idt-history-row.is-inactive .idt-history-file {
        color: var(--primary-lighter);
        text-decoration: line-through;
    }

    .idt-history-no {
        font-weight: 700;
        min-width: 52px;
    }

    .idt-history-file {
        font-weight: 600;
        word-break: break-all;
    }

    .idt-deadline {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 14px;
        margin-bottom: 12px;
        border-radius: var(--border-radius-md);
        background: var(--bg-neutral);
    }

    .idt-deadline.is-closed {
        background: rgba(220, 38, 38, 0.08);
    }

    .idt-deadline-form {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .idt-deadline-form input {
        width: 160px;
    }

    .idt-task-icon {
        font-size: 1.35rem;
        width: 28px;
        text-align: center;
        flex-shrink: 0;
    }

    .idt-task-body {
        flex: 1;
        min-width: 0;
    }

    .idt-task-title {
        font-weight: 700;
        color: var(--text-main);
    }

    .idt-task-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 6px;
    }

    .idt-task.is-todo .idt-task-icon { color: var(--primary-lighter); }
    .idt-task.is-pending .idt-task-icon { color: #F59E0B; }
    .idt-task.is-returned .idt-task-icon { color: #DC2626; }
    .idt-task.is-done .idt-task-icon { color: #16A34A; }
    .idt-task.is-skipped .idt-task-icon { color: var(--primary-lighter); }

    .idt-task.is-done .idt-task-title,
    .idt-task.is-skipped .idt-task-title {
        text-decoration: line-through;
        color: var(--primary-lighter);
    }

    .idt-state {
        font-size: 0.78rem;
        font-weight: 700;
        border-radius: 20px;
        padding: 2px 8px;
        margin-left: 6px;
    }

    .idt-task.is-todo .idt-state { background: var(--bg-neutral); color: var(--text-main); }
    .idt-task.is-pending .idt-state { background: rgba(245, 158, 11, 0.15); color: #B45309; }
    .idt-task.is-returned .idt-state { background: rgba(220, 38, 38, 0.12); color: #DC2626; }
    .idt-task.is-done .idt-state { background: rgba(22, 163, 74, 0.12); color: #16A34A; }
    .idt-task.is-skipped .idt-state { background: var(--bg-neutral); color: var(--primary-lighter); }

    .idt-company {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin: 18px 0 10px;
        padding-bottom: 6px;
        border-bottom: 2px solid var(--primary-light);
        color: var(--primary);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .idt-company .md-sub {
        text-transform: none;
        letter-spacing: 0;
    }

    .idt-company-progress {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
    }

    .idt-company-progress .idt-progress {
        width: 140px;
        height: 8px;
    }

    .idt-company-filter .btn {
        transition: all 0.2s;
    }

    .idt-tracker .idt-filtered {
        display: none !important;
    }

    .idt-tracker.hide-finished .idt-task.is-done,
    .idt-tracker.hide-finished .idt-task.is-skipped,
    .idt-tracker.hide-finished .idt-group.is-finished {
        display: none;
    }
</style>

<div class="card md-card idt-tracker" id="idtTracker">
    <div class="card-body">
        <div class="md-toolbar d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px">
            <div class="idt-card-title mb-0"><i class="fas fa-tasks"></i> Theo dõi tiến độ</div>
            <div class="d-flex flex-wrap align-items-center" style="gap: 14px">
                @if ($companyFilters->count() > 1)
                    <div class="btn-group btn-group-sm idt-company-filter" role="group">
                        @foreach ($companyFilters as $company)
                            <button type="button" class="btn btn-outline-primary {{ $loop->first ? 'active' : '' }}" data-company="{{ $company['label'] }}"
                                title="{{ $company['title'] ?? '' }}">{{ $company['label'] }}</button>
                        @endforeach
                    </div>
                @endif
                <input type="search" class="form-control form-control-sm idt-search" id="idtSearch" style="width: 200px"
                    placeholder="Tìm phòng ban...">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="idtHideFinished">
                    <label class="custom-control-label" for="idtHideFinished">Chỉ hiện việc chưa xong</label>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs idt-stage-tabs mt-3" role="tablist">
            @foreach ($visibleStages as $no => $stage)
                <li class="nav-item">
                    <a class="nav-link {{ $no === $activeStage ? 'active' : '' }}" data-toggle="tab" href="#idtStage{{ $no }}" role="tab">
                        {{ $no }}. {{ $stage['label'] }}
                        <span class="idt-count {{ $stage['total'] && $stage['done'] === $stage['total'] ? 'is-complete' : '' }}">
                            {{ $stage['done'] }}/{{ $stage['total'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach ($visibleStages as $no => $stage)
                <div class="tab-pane fade {{ $no === $activeStage ? 'show active' : '' }}" id="idtStage{{ $no }}" role="tabpanel">
                    <div class="idt-progress-head">
                        <div class="font-weight-bold">Hoàn thành {{ $stage['done'] }} / {{ $stage['total'] }} việc
                            ({{ $stage['total'] ? round($stage['done'] * 100 / $stage['total']) : 100 }}%)</div>
                        <div class="idt-legend">
                            <span><i class="fas fa-hourglass-half text-warning mr-1"></i>Chờ xử lý: {{ $stage['waiting'] }}</span>
                            <span><i class="fas fa-undo text-danger mr-1"></i>Trả lại: {{ $stage['returned'] }}</span>
                            <span><i class="far fa-circle mr-1"></i>Chưa nộp: {{ $stage['total'] - $stage['done'] - $stage['waiting'] - $stage['returned'] }}</span>
                        </div>
                    </div>
                    <div class="progress idt-progress">
                        <div class="progress-bar" style="width: {{ $stage['total'] ? $stage['done'] * 100 / $stage['total'] : 100 }}%"></div>
                    </div>

                    @if ($no > 1)
                        @php
                            $daysLeft = $stage['deadline'] ? now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($stage['deadline']), false) : null;
                        @endphp
                        <div class="idt-deadline {{ $stage['closed'] ? 'is-closed' : '' }}">
                            <span>
                                <i class="far fa-calendar-alt mr-1"></i>Hạn nộp:
                                @if ($stage['deadline'])
                                    <b>{{ \Carbon\Carbon::parse($stage['deadline'])->format('d/m/Y') }}</b>
                                    @if ($stage['closed'])
                                        <span class="badge badge-danger ml-1">Đã hết hạn - không đính kèm được nữa</span>
                                    @elseif ((int) $daysLeft === 0)
                                        <span class="badge badge-warning ml-1">Hết hạn cuối ngày hôm nay</span>
                                    @else
                                        <span class="badge badge-info ml-1">Còn {{ (int) $daysLeft }} ngày</span>
                                    @endif
                                @else
                                    <b>Chưa đặt</b>
                                @endif
                            </span>
                            @if ($isAdmin)
                                <form action="{{ route('pages.materData.initialData.deadline') }}" method="POST" class="idt-deadline-form">
                                    @csrf
                                    <input type="hidden" name="stage" value="{{ $no }}">
                                    <input type="date" name="deadline" class="form-control form-control-sm" value="{{ $stage['deadline'] }}"
                                        title="Hạn nộp giai đoạn {{ $no }} - để trống rồi Lưu = không giới hạn">
                                    <button type="submit" class="btn btn-sm btn-primary" title="Lưu hạn nộp">
                                        <i class="fas fa-save mr-1"></i>Lưu
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif

                    @foreach ($stage['companies'] as $companyKey => $company)
                        @if ($companyKey !== 'all')
                            <div class="idt-company" data-company="{{ $company['label'] }}">
                                <span><i class="fas fa-industry mr-1"></i>{{ $company['label'] }}
                                    <span class="md-sub font-weight-normal">{{ $company['title'] ?? '' }}</span></span>
                                <span class="idt-company-progress">
                                    <span class="progress idt-progress mb-0"><span class="progress-bar"
                                        style="width: {{ $company['total'] ? $company['done'] * 100 / $company['total'] : 100 }}%"></span></span>
                                    {{ $company['done'] }}/{{ $company['total'] }}
                                </span>
                            </div>
                        @endif
                    @foreach ($company['groups'] as $groupName => $tasks)
                        @php
                            $finished = collect($tasks)->whereIn('state', ['done', 'skipped'])->count();
                        @endphp
                        <div class="idt-group {{ $finished === count($tasks) ? 'is-finished' : '' }}"
                            data-company="{{ $companyKey === 'all' ? '' : $company['label'] }}" data-name="{{ mb_strtolower($groupName) }}">
                            <div class="idt-group-head">
                                <span><i class="fas fa-building mr-1"></i>{{ $groupName }}</span>
                                <span class="md-sub">{{ $finished }}/{{ count($tasks) }}</span>
                            </div>
                            @foreach ($tasks as $task)
                                @php
                                    $state = $taskStates[$task['state']];
                                    $latest = $task['latest'];
                                @endphp
                                <div class="idt-task {{ $state['class'] }}">
                                    <i class="idt-task-icon {{ $state['icon'] }}" title="{{ $state['label'] }}"></i>
                                    <div class="idt-task-body">
                                        <div>
                                            <span class="idt-task-title">{{ $task['module'] ? $modules[$task['module']] : $taskTitles[$task['stage']] }}</span>
                                            <span class="idt-state">{{ $state['label'] }}</span>
                                        </div>
                                        <div class="md-sub">
                                            @if ($latest)
                                                Lần nộp {{ $latest->submission_no }} · {{ $latest->created_by }} ·
                                                {{ \Carbon\Carbon::parse($latest->created_at)->format('d/m/Y H:i') }}
                                                · {{ $latest->original_name }}
                                            @elseif ($task['skip'])
                                                Đánh dấu bởi {{ $task['skip']->created_by }}
                                            @else
                                                Chưa có file đính kèm
                                            @endif
                                        </div>
                                        @if ($latest && $latest->review_note && $task['state'] !== 'skipped')
                                            <div class="md-sub {{ $latest->review_status === 'returned' ? 'text-danger' : '' }}">
                                                <i class="far fa-comment mr-1"></i>{{ $latest->review_note }}</div>
                                        @endif
                                    </div>
                                    <div class="idt-task-actions">
                                        @if ($task['state'] !== 'done' && $task['state'] !== 'skipped')
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-idt-upload"
                                                data-stage="{{ $task['stage'] }}" data-module="{{ $task['module'] }}"
                                                data-department="{{ $task['department']?->id }}" @disabled($stage['closed'])
                                                title="{{ $stage['closed'] ? 'Đã hết hạn nộp' : ($latest ? 'Nộp file mới thay thế file hiện tại' : 'Đính kèm file cho việc này') }}">
                                                <i class="fas fa-upload mr-1"></i>{{ $latest ? 'Nộp lại' : 'Đính kèm' }}
                                            </button>
                                        @endif
                                        @if ($task['count'])
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-idt-history"
                                                title="Xem các lần nộp của việc này">
                                                <i class="fas fa-history mr-1"></i>{{ $task['count'] }}
                                            </button>
                                        @endif
                                        @if ($latest)
                                            <a href="{{ route('pages.materData.initialData.download', $latest->id) }}" class="btn btn-sm btn-primary"
                                                title="Tải file nộp mới nhất">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endif
                                        @if ($isAdmin && $latest)
                                            <button type="button" class="btn btn-sm btn-warning btn-md-edit" data-modal="#reviewModal"
                                                title="Cập nhật trạng thái xử lý"
                                                data-row="{{ json_encode(['id' => $latest->id, 'review_status' => $latest->review_status, 'review_note' => $latest->review_note]) }}">
                                                <i class="fas fa-clipboard-check"></i>
                                            </button>
                                        @endif
                                        @if (($isAdmin || $task['department']) && $task['state'] !== 'done')
                                            <form class="form-md-confirm d-inline" action="{{ route('pages.materData.initialData.skip') }}" method="POST"
                                                data-title="{{ $task['skip'] ? 'Áp dụng lại việc này?' : 'Đánh dấu việc này không áp dụng?' }}">
                                                @csrf
                                                <input type="hidden" name="stage" value="{{ $task['stage'] }}">
                                                <input type="hidden" name="module" value="{{ $task['module'] }}">
                                                <input type="hidden" name="department_id" value="{{ $task['department']?->id }}">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                    title="{{ $task['skip'] ? 'Áp dụng lại việc này' : 'Phòng không dùng module / không cần làm việc này' }}">
                                                    <i class="fas fa-{{ $task['skip'] ? 'redo' : 'ban' }} mr-1"></i>{{ $task['skip'] ? 'Áp dụng lại' : 'Không áp dụng' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    @if ($task['count'])
                                        {{-- Lịch sử nộp của việc: mọi lần nộp, kể cả file đã bị thay thế / gỡ --}}
                                        <div class="idt-history" style="display: none">
                                            @foreach ($task['history'] as $f)
                                                @php
                                                    $fs = $fileState($f);
                                                @endphp
                                                <div class="idt-history-row {{ (int) $f->status_id === 1 ? '' : 'is-inactive' }}">
                                                    <span class="idt-history-no">Lần {{ $f->submission_no }}</span>
                                                    <span class="badge badge-{{ $fs['class'] }}">{{ $fs['label'] }}</span>
                                                    <span class="idt-history-file">{{ $f->original_name }}</span>
                                                    <span class="md-sub">{{ $f->created_by }} · {{ \Carbon\Carbon::parse($f->created_at)->format('d/m/Y H:i') }}
                                                        @if ($f->note)
                                                            · {{ $f->note }}
                                                        @endif
                                                        @if ($f->review_note)
                                                            · <span class="{{ $f->review_status === 'returned' ? 'text-danger' : '' }}">Admin: {{ $f->review_note }}</span>
                                                        @endif
                                                    </span>
                                                    <a href="{{ route('pages.materData.initialData.download', $f->id) }}" class="ml-auto"
                                                        title="Tải file lần nộp {{ $f->submission_no }}"><i class="fas fa-download"></i></a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var key = 'wms.initialData.hideFinished';
        var $tracker = $('#idtTracker');

        try {
            $('#idtHideFinished').prop('checked', localStorage.getItem(key) === '1');
        } catch (e) {}
        $tracker.toggleClass('hide-finished', $('#idtHideFinished').prop('checked'));

        // Lọc theo công ty + tìm phòng ban: ẩn khối công ty / nhóm phòng không khớp ở mọi tab
        function applyFilter() {
            var company = $('.idt-company-filter .active').data('company') || '';
            var term = ($('#idtSearch').val() || '').toLowerCase().trim();

            $tracker.find('.idt-group').each(function() {
                var $g = $(this);
                var okCompany = !company || !$g.data('company') || $g.data('company') === company;
                var okName = !term || String($g.data('name')).indexOf(term) !== -1;
                $g.toggleClass('idt-filtered', !(okCompany && okName));
            });
            $tracker.find('.idt-company').each(function() {
                var $c = $(this);
                var hasGroup = $c.nextUntil('.idt-company', '.idt-group').not('.idt-filtered').length > 0;
                $c.toggleClass('idt-filtered', (company && $c.data('company') !== company) || !hasGroup);
            });
        }

        // Luôn xem một công ty; nhớ công ty đã chọn
        var companyKey = 'wms.initialData.company';
        $('.idt-company-filter').on('click', '.btn', function() {
            $(this).addClass('active').siblings().removeClass('active');
            try {
                localStorage.setItem(companyKey, $(this).data('company'));
            } catch (e) {}
            applyFilter();
        });
        try {
            var saved = localStorage.getItem(companyKey);
            $('.idt-company-filter .btn').filter(function() {
                return $(this).data('company') === saved;
            }).addClass('active').siblings().removeClass('active');
        } catch (e) {}
        $('#idtSearch').on('input', applyFilter);
        applyFilter();

        $tracker.on('click', '.btn-idt-history', function() {
            $(this).closest('.idt-task').find('.idt-history').slideToggle(150);
            $(this).toggleClass('active');
        });

        $('#idtHideFinished').on('change', function() {
            $tracker.toggleClass('hide-finished', this.checked);
            try {
                localStorage.setItem(key, this.checked ? '1' : '0');
            } catch (e) {}
        });
    });
</script>
