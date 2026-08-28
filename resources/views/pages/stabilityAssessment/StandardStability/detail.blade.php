@extends ('layout.master')

{{--
|--------------------------------------------------------------------------
| ĐÁNH GIÁ HẠN DÙNG - CHI TIẾT MỘT PHIẾU CHẤT CHUẨN
|--------------------------------------------------------------------------
| Đầu trang là thông tin ống chuẩn đang theo dõi, bên dưới là bảng các MỐC ĐÁNH GIÁ
| của phiếu (T0, T3, T6...) kèm ngày đến hạn, chỉ tiêu kiểm và kết quả.
|
| Biến vào: $list, $items, $itemStates, $itemResults, $groups, $dueSoonDays,
|           $maxTestings, $criterias, $histories, $editable
--}}

@php
    $ssaRoute = 'pages.stabilityAssessment.standardStability.';
    $ssaLabel = 'phiếu đánh giá hạn dùng';
    $ssaIcon = 'fas fa-clipboard-list';

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $ssaDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /** Trạng thái phiếu -> lớp CSS của thẻ .ssa-badge. */
    $ssaStatusClass = fn($status) => match ($status) {
        'Đang Đánh Giá' => 'running',
        'Hoàn Thành' => 'done',
        'Huỷ' => 'cancelled',
        default => 'initial',
    };

    /** Mã nhóm trong mã ống chuẩn (VKN, IMP...) -> tên viết tắt. */
    $ssaShortByCode = collect($groups)->mapWithKeys(fn($group) => [$group['code'] => $group['short']])->all();
    $ssaGroupName = fn($code) => $ssaShortByCode[$code] ?? ($code ?: '—');

    // Tiến độ: số mốc đã có kết quả trên tổng số mốc
    $ssaDone = $items->where('state', 'done')->count();
    $ssaProgress = $items->count() > 0 ? (int) round(($ssaDone / $items->count()) * 100) : 0;

    // Số mốc theo từng tình trạng, dùng cho các nút lọc nhanh
    $ssaStateCounts = collect($itemStates)
        ->map(fn($label, $key) => $items->where('state', $key)->count())
        ->all();
@endphp

@section('mainContent')
    @include('pages.stabilityAssessment.StandardStability.assets')

    <div class="content-wrapper">
        <div class="md-page">

            {{-- ============ THÔNG TIN ỐNG CHUẨN ĐANG THEO DÕI ============ --}}
            <div class="ssa-info">
                <div class="box">
                    <label>Trạng Thái Phiếu</label>
                    <div class="val">
                        <span class="ssa-badge {{ $ssaStatusClass($list->status) }}">{{ $list->status }}</span>
                    </div>
                </div>

                <div class="box">
                    <label>Mã Ống Chuẩn</label>
                    <div class="val"><span class="ssa-code">{{ $list->import_code }}</span></div>
                    <div class="md-sub">
                        <span class="ssa-group-tag">{{ $ssaGroupName($list->group_code) }}</span>
                        @if ($list->batch_no)
                            <span class="ml-1">Lô {{ $list->batch_no }}</span>
                        @endif
                    </div>
                </div>

                <div class="box">
                    <label>Chất Chuẩn</label>
                    <div class="val">{{ $list->standard_name ?: '—' }}</div>
                    <div class="md-sub">
                        <span class="md-tag">{{ $list->category_code ?: '—' }}</span>
                        @if ($list->manufacturer_short_name)
                            <span class="ml-1">· {{ $list->manufacturer_short_name }}</span>
                        @endif
                    </div>
                </div>

                <div class="box">
                    <label>Ngày Bắt Đầu / Chu Kỳ</label>
                    <div class="val">{{ $ssaDate($list->start_date) }}</div>
                    <div class="md-sub">Mỗi {{ $list->assessment_period }} tháng một mốc</div>
                </div>

                <div class="box">
                    <label>Tiến Độ Các Mốc</label>
                    <div class="val">{{ $ssaDone }}/{{ $items->count() }} mốc đã đánh giá</div>
                    <div class="ssa-progress {{ $ssaProgress >= 100 && $items->count() > 0 ? 'is-done' : '' }}">
                        <span style="width: {{ $ssaProgress }}%"></span>
                    </div>
                </div>

                <div class="box">
                    <label>Hạn Dùng Ống Chuẩn</label>
                    <div class="val">{{ $ssaDate($list->expired_date) }}</div>
                    <div class="md-sub">
                        @if ($list->internal_expired_date)
                            Hạn nội bộ {{ $ssaDate($list->internal_expired_date) }}
                        @else
                            Chưa xác định hạn nội bộ
                        @endif
                    </div>
                </div>

                <div class="box">
                    <label>Người Lập Phiếu</label>
                    <div class="val">{{ $list->created_by ?: '—' }}</div>
                    <div class="md-sub">
                        {{ $list->created_at ? \Carbon\Carbon::parse($list->created_at)->format('d/m/Y H:i') : '' }}
                    </div>
                </div>
            </div>

            @if ($list->note)
                <div class="md-hint mb-3">
                    <i class="fas fa-sticky-note mr-1"></i> {{ $list->note }}
                </div>
            @endif

            <div class="card md-card">
                <div class="card-body">

                    <div class="md-toolbar">
                        <div>
                            <a href="{{ route($ssaRoute . 'list') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Danh sách phiếu
                            </a>

                            @if ($editable)
                                <button type="button" class="btn btn-primary btn-md-create"
                                    data-modal="#itemCreateModal">
                                    <i class="fas fa-plus mr-1"></i> Thêm mốc đánh giá
                                </button>
                            @endif

                            <button type="button" class="btn btn-info" data-toggle="modal"
                                data-target="#historyModal">
                                <i class="fas fa-clock-rotate-left mr-1"></i> Lịch sử thay đổi
                                <span class="badge badge-light ml-1">{{ $histories->count() }}</span>
                            </button>
                        </div>

                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            @if ($editable)
                                Ngày đến hạn = <b>ngày bắt đầu + số tháng của mốc</b>. Mốc đến hạn trong
                                <b>{{ $dueSoonDays }} ngày</b> được đánh dấu "Sắp đến hạn".
                            @else
                                Phiếu đã huỷ nên chỉ xem lại, không ghi thêm dữ liệu được.
                            @endif
                        </p>
                    </div>

                    {{-- Lọc nhanh theo tình trạng mốc: mỗi dòng mang sẵn data-state --}}
                    <div class="ssa-filters" data-table="#mdTable">
                        <button type="button" class="ssa-chip is-active" data-state="all">
                            <i class="fas fa-layer-group"></i> Tất cả
                            <span class="count">{{ $items->count() }}</span>
                        </button>
                        @foreach ($itemStates as $key => $label)
                            <button type="button" class="ssa-chip" data-state="{{ $key }}">
                                <span class="ssa-state ssa-state-{{ $key }}">{{ $label }}</span>
                                <span class="count">{{ $ssaStateCounts[$key] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 55px">STT</th>
                                    <th class="text-center" style="width: 80px"
                                        title="Số tháng tính từ ngày bắt đầu đánh giá">Mốc</th>
                                    <th style="width: 160px">Tên Mốc Đánh Giá</th>
                                    <th class="text-center" style="width: 130px">Ngày Đến Hạn</th>
                                    <th style="width: 230px">Chỉ Tiêu Kiểm</th>
                                    <th class="text-center" style="width: 115px">Ngày Thực Hiện</th>
                                    <th style="width: 220px">Kết Quả</th>
                                    <th class="text-center" style="width: 120px">Tình Trạng</th>
                                    <th class="text-center" style="width: 160px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr data-state="{{ $item->state }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center" data-order="{{ $item->timepoint }}">
                                            <span class="ssa-timepoint">T{{ $item->timepoint }}</span>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $item->name }}</div>
                                            @if ($item->note)
                                                <div class="md-sub">
                                                    <span class="md-note" title="{{ $item->note }}">{{ $item->note }}</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center" data-order="{{ $item->due_date ?: '9999-12-31' }}">
                                            {{ $ssaDate($item->due_date) }}
                                            @if ($item->state !== 'done' && $item->days_to_due !== null)
                                                <div class="md-sub">
                                                    @if ($item->days_to_due < 0)
                                                        <span class="text-danger font-weight-bold">Quá
                                                            {{ abs($item->days_to_due) }} ngày</span>
                                                    @else
                                                        Còn {{ $item->days_to_due }} ngày
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse ($item->testing_list as $testing)
                                                <span class="ssa-testing">{{ $testing }}</span>
                                            @empty
                                                <span class="md-empty">Chưa chọn chỉ tiêu</span>
                                            @endforelse
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $item->done_at ?: '9999-12-31' }}">
                                            {{ $ssaDate($item->done_at) }}
                                        </td>
                                        <td>
                                            @if ($item->result)
                                                <div>{{ $item->result }}</div>
                                                <div class="md-sub">
                                                    <span
                                                        class="{{ $item->status === 'Đạt' ? 'ssa-result-pass' : 'ssa-result-fail' }}">
                                                        {{ $item->status }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="md-empty">Chưa có kết quả</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="ssa-state ssa-state-{{ $item->state }}">{{ $item->state_label }}</span>
                                        </td>
                                        <td>
                                            <div class="md-actions">
                                                @if ($editable)
                                                    <button type="button" class="btn btn-sm btn-success btn-ssa-assess"
                                                        title="{{ $item->state === 'done' ? 'Sửa kết quả đánh giá' : 'Ghi kết quả đánh giá' }}"
                                                        data-row="{{ json_encode([
                                                            'id' => $item->id,
                                                            'name' => $item->name,
                                                            'timepoint' => $item->timepoint,
                                                            'due_date' => $ssaDate($item->due_date),
                                                            'done_at' => $item->done_at ? substr((string) $item->done_at, 0, 10) : now()->format('Y-m-d'),
                                                            'result' => $item->result,
                                                            'status' => $item->status,
                                                            'note' => $item->note,
                                                            'testings' => implode(', ', $item->testing_list),
                                                        ]) }}">
                                                        <i class="fas fa-clipboard-check"></i>
                                                    </button>

                                                    @if ($item->state !== 'done')
                                                        <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                            title="Sửa mốc đánh giá" data-modal="#itemUpdateModal"
                                                            data-row="{{ json_encode([
                                                                'id' => $item->id,
                                                                'name' => $item->name,
                                                                'timepoint' => $item->timepoint,
                                                                'due_date' => $item->due_date ? substr((string) $item->due_date, 0, 10) : '',
                                                                'testings' => $item->testing_list,
                                                                'note' => $item->note,
                                                            ]) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    @endif

                                                    @if ($item->state !== 'done')
                                                        <form class="form-md-confirm d-inline"
                                                            action="{{ route($ssaRoute . 'deleteItem') }}" method="POST"
                                                            data-title="Xoá mốc {{ $item->name }}?"
                                                            data-text="Mốc T{{ $item->timepoint }} sẽ bị xoá khỏi phiếu. Thao tác này không khôi phục được."
                                                            data-danger="1">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                title="Xoá mốc đánh giá">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <span class="md-empty">—</span>
                                                @endif
                                            </div>
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
            // Bảng dùng chung sắp theo cột 1, mốc đánh giá phải đi theo thứ tự thời gian (cột 1 = Mốc)
            $('#mdTable').DataTable().order([1, 'asc']).draw();

            var table = $('#mdTable').DataTable();

            table.settings()[0].oLanguage.sEmptyTable =
                'Phiếu chưa có mốc đánh giá nào. Bấm "Thêm mốc đánh giá" để khai thời điểm kiểm đầu tiên.';
            table.draw(false);
        });
    </script>
@endsection

@section('model')
    @include('pages.stabilityAssessment.StandardStability.itemCreate')
    @include('pages.stabilityAssessment.StandardStability.itemUpdate')
    @include('pages.stabilityAssessment.StandardStability.assess')
    @include('pages.stabilityAssessment.StandardStability.history')
@endsection
