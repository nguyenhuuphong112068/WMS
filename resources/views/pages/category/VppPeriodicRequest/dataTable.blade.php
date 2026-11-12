{{--
| Bảng "Danh sách VPP đề nghị / dự trù ... theo chu kỳ". Biến vào: $type (internal|external|estimate), $lists
--}}
@include('pages.category.shared.assets')
@include('pages.category.VppPeriodicRequest.assets')

@php
    $prRoute = 'pages.category.vppPeriodicRequest.';
    $prKey = \App\Support\VppPeriodicRequest::typeKey($type);
    $isExternal = $type === 'external';
    $isEstimate = $type === 'estimate';
    $isInternal = ! $isExternal && ! $isEstimate;
    // Danh sách dự trù tạo "phiếu dự trù", hai loại kia tạo "đề nghị"
    $prDoc = $isEstimate ? 'phiếu dự trù' : 'đề nghị';
    $prSupport = \App\Support\VppPeriodicRequest::class;
    $prObjectTypes = \App\Http\Controllers\Pages\MaterData\ConsumptionObjectController::typeLabels();
    $prNum = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
@endphp

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            @perm('category_vpp_periodic_manage')
                <button type="button" class="btn btn-primary btn-pr-create" data-modal="#periodic{{ $prKey }}CreateModal">
                    <i class="fas fa-plus mr-1"></i> Thêm danh sách {{ $isEstimate ? 'dự trù' : ($isExternal ? 'liên phòng ban' : 'nội bộ') }}
                </button>
            @endperm

            @if ($isInternal)
                <div class="pr-group-filter">
                    <label for="prGroupFilter"><i class="fas fa-filter mr-1"></i> Tổ quản lý</label>
                    <select id="prGroupFilter" class="form-control form-control-sm">
                        <option value="">Tất cả</option>
                        @foreach ($lists->pluck('group_name')->filter()->unique()->sort()->values() as $prGroupName)
                            <option value="{{ $prGroupName }}">{{ $prGroupName }}</option>
                        @endforeach
                        @if ($lists->contains(fn ($row) => ! $row->group_name))
                            <option value="__none">Chưa có tổ</option>
                        @endif
                    </select>
                </div>
            @endif
        </div>

        <div class="table-responsive">
            <table id="periodic{{ $prKey }}Table" class="table table-bordered table-hover w-100 md-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 46px">STT</th>
                        <th style="width: 190px">Tiêu Đề</th>
                        @if ($isInternal)
                            <th style="width: 130px">Tổ Quản Lý</th>
                        @endif
                        @if ($isEstimate)
                            <th style="width: 150px">Dự Trù Cho</th>
                        @elseif ($isExternal)
                            <th style="width: 150px">Phòng Cấp Phát</th>
                        @else
                            <th style="width: 210px">Đối Tượng</th>
                        @endif
                        <th style="width: 160px">Chu Kỳ</th>
                        <th class="text-center" style="width: 110px">{{ $isEstimate ? 'Tạo Phiếu Kế Tiếp' : 'Tạo Đề Nghị Kế Tiếp' }}</th>
                        <th style="width: 160px">{{ $isEstimate ? 'Phiếu Đã Tạo' : 'Đề Nghị Đã Tạo' }}</th>
                        <th class="text-center" style="width: 90px">Sử Dụng</th>
                        <th class="text-center" style="width: 160px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $row)
                        <tr data-group="{{ $row->group_name ?: '__none' }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="font-weight-bold">{{ $row->title }}</td>
                            @if ($isInternal)
                                <td>{{ $row->group_name ?: '—' }}</td>
                            @endif
                            @if ($isEstimate)
                                @php $prPeriod = $prSupport::estimatePeriod($row, $row->next_run_date ?: now()); @endphp
                                <td data-order="{{ $prSupport::estimateMonthOffset($row) }}">
                                    <div class="font-weight-bold">{{ $prSupport::ESTIMATE_MONTH_OFFSETS[$prSupport::estimateMonthOffset($row)] }}</div>
                                    @if ($row->status_id == 1 && $row->next_run_date)
                                        <div class="md-sub">Phiếu kế tiếp: tháng {{ $prPeriod->format('m/Y') }}</div>
                                    @endif
                                </td>
                            @elseif ($isExternal)
                                <td>
                                    {{ $row->to_department_name ?: '—' }}
                                    @if ($row->to_department_short)
                                        <br><span class="md-tag">{{ $row->to_department_short }}</span>
                                    @endif
                                </td>
                            @else
                                <td data-order="{{ $row->object_code }}">
                                    @if ($row->object_code)
                                        <span class="md-tag">{{ $row->object_code }}</span>
                                        <div class="font-weight-bold">{{ $row->object_name }}</div>
                                        <div class="md-sub">
                                            {{ $prObjectTypes[$row->object_type] ?? $row->object_type }}{{ $row->object_location ? ' · ' . $row->object_location : '' }}
                                        </div>
                                    @else
                                        <span class="md-empty">—</span>
                                    @endif
                                </td>
                            @endif
                            <td data-order="{{ (int) array_search($row->periodic, array_keys($prSupport::CYCLES)) }}-{{ str_pad((string) $row->cycle_length, 3, '0', STR_PAD_LEFT) }}-{{ str_pad((string) $row->cycle_day, 3, '0', STR_PAD_LEFT) }}">
                                @php $prCalMode = $prSupport::dayModeOf($row->cycle_day_mode ?? null) === $prSupport::DAY_MODE_CAL_DUE; @endphp
                                <div class="font-weight-bold">{{ $prSupport::scheduleLabel($row->periodic, $row->cycle_length, $row->frequency) }}</div>
                                <div>{{ $prSupport::dayLabel($row) }}</div>
                                @if ($prCalMode)
                                    <div class="md-sub" title="{{ $row->cal_sch_id ? 'SCH_ID ' . $row->cal_sch_id : '' }}">
                                        <i class="fas fa-link mr-1"></i>{{ $row->cal_due_date
                                            ? 'Hạn CAL ' . \Carbon\Carbon::parse($row->cal_due_date)->format('d/m/Y')
                                            : 'Chờ lịch Pending mới từ CAL' }}
                                    </div>
                                @endif
                                <div class="md-sub">Bắt đầu {{ \Carbon\Carbon::parse($row->start_date)->format('d/m/Y') }}</div>
                            </td>
                            <td class="text-center" data-order="{{ $row->status_id == 1 && $row->next_run_date ? $row->next_run_date : '9999-12-31' }}">
                                @if ($row->status_id == 1 && $row->next_run_date)
                                    @php $prNext = \Carbon\Carbon::parse($row->next_run_date); @endphp
                                    <div class="font-weight-bold">{{ $prNext->format('d/m/Y') }}</div>
                                    <div class="md-sub">{{ $prSupport::WEEKDAYS[$prNext->dayOfWeekIso] }}</div>
                                @elseif ($row->status_id == 1 && $prCalMode)
                                    <span class="md-sub">Chờ lịch CAL</span>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td data-order="{{ (int) $row->generated_count }}">
                                <div class="pr-count"><b>{{ (int) $row->generated_count }}</b> lần</div>
                                @if ($row->last_generated_at)
                                    <div class="md-sub">Gần nhất {{ \Carbon\Carbon::parse($row->last_generated_at)->format('d/m/Y H:i') }}</div>
                                    <span class="md-tag">{{ $row->last_request_code }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->status_id == 1)
                                    <span class="badge badge-success">Đang dùng</span>
                                @else
                                    <span class="badge badge-danger">Đã khoá</span>
                                @endif
                            </td>
                            <td>
                                <div class="md-actions">
                                    <button type="button" class="btn btn-sm btn-outline-info btn-pr-items" title="Xem VPP {{ $isEstimate ? 'dự trù' : 'đề nghị' }}"
                                        data-title="{{ $row->title }}"
                                        data-items="{{ json_encode($row->items->map(fn ($item) => [
                                            'category_code' => $item->category_code,
                                            'material_name' => $item->material_name,
                                            'requested_amount' => $prNum($item->requested_amount),
                                            'requested_unit' => $item->requested_unit,
                                            'purpose' => $item->purpose,
                                        ])->values()) }}">
                                        <i class="fas fa-list-ul"></i>
                                    </button>

                                    {{-- Nút Sửa + badge số lần thay đổi (nếu có) ở góc trên bên phải - giống cụm Danh Mục --}}
                                    <span class="cat-btn-wrap">
                                        @perm('category_vpp_periodic_manage')
                                            <button type="button" class="btn btn-sm btn-warning btn-pr-edit" title="Sửa"
                                                data-modal="#periodic{{ $prKey }}UpdateModal"
                                                data-row="{{ json_encode([
                                                'id' => $row->id,
                                                'title' => $row->title,
                                                'periodic' => $row->periodic,
                                                'cycle_length' => $row->cycle_length === null ? null : (int) $row->cycle_length,
                                                'cycle_day' => (int) $row->cycle_day,
                                                'cycle_day_mode' => $prSupport::dayModeOf($row->cycle_day_mode ?? null),
                                                'cal_lead_days' => $prSupport::calLeadDays($row),
                                                'cal_due_date' => $row->cal_due_date ?? null,
                                                'start_date' => $row->start_date,
                                                'next_run_date' => $row->next_run_date,
                                                'to_department_id' => $row->to_department_id,
                                                'estimate_month_offset' => $prSupport::estimateMonthOffset($row),
                                                'consumption_object_id' => $row->consumption_object_id,
                                                'frequency' => $row->frequency,
                                'group_id' => $row->group_id,
                                'group_name' => $row->group_name,
                                                'items' => $row->items->map(fn ($item) => [
                                                    'category_id' => $item->category_id,
                                                    'requested_amount' => $prNum($item->requested_amount),
                                                    'requested_unit' => $item->requested_unit,
                                                    'purpose' => $item->purpose,
                                                ])->values(),
                                            ]) }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endperm

                                        @if ($row->history_count > 0)
                                            <button type="button" class="cat-count-badge btn-cat-history"
                                                title="Xem {{ $row->history_count }} lần thay đổi"
                                                data-url="{{ route($prRoute . 'history', ['id' => $row->id]) }}"
                                                data-title="{{ $row->title }}">{{ $row->history_count }}</button>
                                        @endif
                                    </span>

                                    @perm('category_vpp_periodic_manage')
                                        @if ($row->status_id == 1)
                                            <form class="form-md-confirm d-inline" action="{{ route($prRoute . 'generateNow') }}" method="POST"
                                                data-title="Tạo {{ $prDoc }} ngay?"
                                                data-text="Hệ thống tạo {{ $isEstimate ? 'một phiếu dự trù VPP ở trạng thái Nháp' : 'một đề nghị ' . ($isExternal ? 'liên phòng ban' : 'nội bộ') . ' ở trạng thái Lưu tạm' }} từ danh sách &quot;{{ $row->title }}&quot;. Lịch tự động theo chu kỳ giữ nguyên.">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $row->id }}">
                                                <button type="submit" class="btn btn-sm btn-info" title="Tạo {{ $prDoc }} ngay">
                                                    <i class="fas fa-file-medical"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <form class="form-md-confirm d-inline" data-require-reason="1" action="{{ route($prRoute . 'deActive') }}" method="POST"
                                            data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} danh sách theo chu kỳ?"
                                            data-text="{{ $row->status_id == 1 ? 'Sau khi khoá, hệ thống sẽ không tự tạo ' . $prDoc . ' từ' : 'Sau khi mở khoá, hệ thống tiếp tục tự tạo ' . $prDoc . ' từ' }} danh sách &quot;{{ $row->title }}&quot;."
                                            data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $row->id }}">
                                            <button type="submit"
                                                class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                                            </button>
                                        </form>
                                    @endperm
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($isInternal)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                if (settings.nTable.id !== 'periodic{{ $prKey }}Table') return true;

                var want = $('#prGroupFilter').val();
                if (!want) return true;

                return $(settings.aoData[index].nTr).attr('data-group') === want;
            });

            $(document).on('change', '#prGroupFilter', function() {
                $('#periodic{{ $prKey }}Table').DataTable().draw();
            });
        });
    </script>
@endif

<style>
    .pr-group-filter { display: flex; align-items: center; gap: 6px; }
    .pr-group-filter label { margin: 0; font-size: 0.83rem; font-weight: 700; color: var(--primary-dark); white-space: nowrap; }
    .pr-group-filter .form-control { width: auto; min-width: 150px; }
</style>
