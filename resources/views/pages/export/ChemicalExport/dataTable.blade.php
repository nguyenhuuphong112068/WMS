@include('pages.export.shared.assets')

@php
    $expTypeLabel = fn($type) => $types[$type] ?? $type;
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="exp-tabs">
                    <button type="button" class="exp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}"
                        data-pane="expPaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ sử dụng hoá chất
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'request' ? 'is-active' : '' }}"
                        data-pane="expPaneRequest">
                        <i class="fas fa-paper-plane mr-1"></i> Đề nghị chuyển
                        @php $expReqPending = $requestsReceived->where('app_status', 'pending')->count(); @endphp
                        @if ($expReqPending)
                            <span class="exp-tab-count">{{ $expReqPending }}</span>
                        @endif
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'disposal' ? 'is-active' : '' }}"
                        data-pane="expPaneDisposal">
                        <i class="fas fa-dumpster-fire mr-1"></i> Hoá chất chờ huỷ
                        @if ($waitingDisposal->count())
                            <span class="exp-tab-count">{{ $waitingDisposal->count() }}</span>
                        @endif
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'report' ? 'is-active' : '' }}"
                        data-pane="expPaneReport">
                        <i class="fas fa-chart-column mr-1"></i> Báo cáo theo khoảng thời gian
                    </button>
                </div>

                {{-- ============ SỔ SỬ DỤNG HOÁ CHẤT ============ --}}
                <div class="exp-pane {{ $activeTab === 'book' ? 'is-active' : '' }}" id="expPaneBook">

                    <div class="md-toolbar">
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Sử dụng hoá chất
                        </button>
                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            Đang hiệu lực {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} phiếu.
                            Chỉ phiếu hiệu lực mới trừ tồn của phiếu nhập.
                        </p>
                    </div>

                    @include('pages.shared.barcodeSearch', [
                        'scanTitle' => 'Quét mã vạch',
                        'scanTables' => [
                            [
                                'id' => 'mdTable',
                                'column' => 1,
                                'pane' => 'expPaneBook',
                                'label' => 'Sổ sử dụng hoá chất',
                            ],
                        ],
                    ])

                    @include('pages.shared.classificationFilter', ['clsTarget' => 'mdTable'])

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 130px">Mã Xuất Nhập</th>
                                    <th>Hoá Chất</th>
                                    <th class="text-right" style="width: 110px">Số Lượng</th>
                                    <th class="text-center" style="width: 100px">Loại</th>
                                    <th class="text-center" style="width: 105px">Ngày Sử Dụng</th>
                                    <th style="width: 200px">Mục Đích Sử Dụng</th>
                                    <th style="width: 130px">Người Sử Dụng</th>
                                    <th style="width: 130px">Người Kiểm Tra</th>
                                    <th class="text-center" style="width: 110px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($datas as $row)
                                    @php $expAdjust = (int) ($adjustCounts[$row->id] ?? 0); @endphp
                                    {{-- data-classification để bộ lọc Phụ lục / Nhóm hoá chất nhận ra dòng này --}}
                                    <tr data-classification="{{ $expCls($row->classification) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td><span class="exp-code">{{ $row->code }}</span></td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                                @if ($row->batch_no)
                                                    <span class="md-sub ml-1">Lô {{ $row->batch_no }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <span
                                                class="exp-amount">{{ rtrim(rtrim(number_format($row->amount, 4, '.', ','), '0'), '.') }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="exp-badge exp-badge-{{ $row->type }}">
                                                {{ $expTypeLabel($row->type) }}
                                            </span>
                                            @if ($row->type === 'transfer')
                                                <div class="md-sub mt-1">
                                                    <i
                                                        class="fas fa-arrow-right-long mr-1"></i>{{ $row->to_department_short ?: $row->to_department_name ?: '—' }}
                                                </div>
                                                @if ($row->received_import_id)
                                                    <span class="exp-received"
                                                        title="Phòng nhận đã lấy hàng, mã lô bên đó: {{ $row->received_code }}">
                                                        Đã nhận
                                                    </span>
                                                @else
                                                    <span class="exp-pending"
                                                        title="Phòng nhận chưa vào màn hình Nhập Hoá Chất để nhận">
                                                        Chờ nhận
                                                    </span>
                                                @endif
                                            @endif

                                            {{-- Phiếu loại bỏ: cho biết đang chờ gom hay đã nằm trong đợt xin huỷ nào --}}
                                            @if ($row->type === 'cancel')
                                                @if ($row->disposal_code)
                                                    <div class="md-sub mt-1">
                                                        <span class="exp-received"
                                                            title="Đã gom vào đợt xin quyết định huỷ {{ $row->disposal_code }} ({{ $disposalStatuses[$row->disposal_status] ?? $row->disposal_status }})">
                                                            {{ $row->disposal_code }}
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="md-sub mt-1">
                                                        <span class="exp-pending"
                                                            title="Chưa gom vào đợt nào, xem tab Hoá chất chờ huỷ">Chờ
                                                            huỷ</span>
                                                    </div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center md-sub">
                                            {{ $row->exported_date ? \Carbon\Carbon::parse($row->exported_date)->format('d/m/Y') : '—' }}
                                        </td>
                                        <td class="md-sub">
                                            @if ($row->purpose)
                                                <span class="md-note"
                                                    title="{{ $row->purpose }}">{{ $row->purpose }}</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="md-sub">{{ $row->exported_by ?: '—' }}</td>
                                        <td class="md-sub">{{ $row->checked_by ?: '—' }}</td>
                                        <td>
                                            <div class="md-actions">
                                                {{-- Badge số lần điều chỉnh nằm ở góc trên bên phải nút Sửa --}}
                                                <div class="exp-btn-wrap">
                                                    {{-- Đã gom vào đợt xin huỷ thì khoá: số liệu trên phiếu đã vào hồ sơ --}}
                                                    <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                        {{ $row->disposal_id ? 'disabled' : '' }}
                                                        title="{{ $row->disposal_id ? 'Đã gom vào đợt xin huỷ ' . $row->disposal_code . ', gỡ khỏi đợt ở tab Hoá chất chờ huỷ mới sửa được' : 'Sửa' }}"
                                                        data-row="{{ json_encode([
                                                            'id' => $row->id,
                                                            'import_id' => $row->import_id,
                                                            'amount' => $row->amount,
                                                            'type' => $row->type,
                                                            'exported_date' => $row->exported_date,
                                                            'exported_by' => $row->exported_by,
                                                            'purpose' => $row->purpose,
                                                            'test_report_no' => $row->test_report_no,
                                                            'checked_by' => $row->checked_by,
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    @if ($expAdjust > 0)
                                                        <button type="button" class="exp-count-badge btn-exp-history"
                                                            title="Xem {{ $expAdjust }} lần điều chỉnh của phiếu này"
                                                            data-url="{{ route($expRoute . 'history', ['id' => $row->id]) }}"
                                                            data-title="{{ $row->code }} - {{ $row->chem_name }}">{{ $expAdjust }}</button>
                                                    @endif
                                                </div>

                                                <form class="form-md-confirm d-inline"
                                                    action="{{ route($expRoute . 'deActive') }}" method="POST"
                                                    data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $expLabel }}?"
                                                    data-text="{{ $row->status_id == 1 ? 'Sau khi khoá' : 'Sau khi mở khoá' }}, số lượng của phiếu &quot;{{ $row->code }}&quot; {{ $row->status_id == 1 ? 'sẽ được cộng trả lại tồn kho.' : 'sẽ bị trừ khỏi tồn kho trở lại.' }}"
                                                    data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                                    <button type="submit"
                                                        class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                        {{ $row->disposal_id ? 'disabled' : '' }}
                                                        title="{{ $row->disposal_id ? 'Đã gom vào đợt xin huỷ ' . $row->disposal_code . ', gỡ khỏi đợt trước khi khoá' : ($row->status_id == 1 ? 'Khoá' : 'Mở khoá') }}">
                                                        <i
                                                            class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ ĐỀ NGHỊ CHUYỂN HOÁ CHẤT ============ --}}
                @include('pages.export.ChemicalExport.requestPane')

                {{-- ============ HOÁ CHẤT CHỜ HUỶ (BƯỚC 2 CỦA HUỶ BỎ) ============ --}}
                @include('pages.export.ChemicalExport.disposalPane')

                <div class="exp-pane {{ $activeTab === 'report' ? 'is-active' : '' }}" id="expPaneReport">

                    <form method="GET" action="{{ route($expRoute . 'list') }}" class="exp-range">
                        {{-- Lọc xong trang tải lại, cờ này đưa người dùng về đúng tab báo cáo --}}
                        <input type="hidden" name="tab" value="report">

                        <div class="form-group">
                            <label>Từ ngày</label>
                            <input type="date" name="from" class="form-control" value="{{ $reportFrom }}">
                        </div>

                        <div class="form-group">
                            <label>Đến ngày</label>
                            <input type="date" name="to" class="form-control" value="{{ $reportTo }}">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter mr-1"></i> Xem báo cáo
                            </button>
                        </div>

                        <div class="exp-quick">
                            <button type="button" data-from="{{ now()->startOfMonth()->format('Y-m-d') }}"
                                data-to="{{ now()->format('Y-m-d') }}">Tháng này</button>
                            <button type="button"
                                data-from="{{ now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d') }}"
                                data-to="{{ now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d') }}">Tháng
                                trước</button>
                            <button type="button" data-from="{{ now()->startOfQuarter()->format('Y-m-d') }}"
                                data-to="{{ now()->format('Y-m-d') }}">Quý này</button>
                            <button type="button" data-from="{{ now()->startOfYear()->format('Y-m-d') }}"
                                data-to="{{ now()->format('Y-m-d') }}">Năm nay</button>
                        </div>
                    </form>

                    <div class="md-toolbar">
                        <p class="hint">

                        </p>
                    </div>

                    @include('pages.shared.classificationFilter', ['clsTarget' => 'expReportTable'])

                    <div class="table-responsive">
                        <table id="expReportTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 120px">Mã Danh Mục</th>
                                    <th>Hoá Chất</th>
                                    <th class="text-center" style="width: 90px">Lượt Xuất</th>
                                    <th class="text-right" style="width: 120px">Đã Sử Dụng</th>
                                    <th class="text-right" style="width: 110px">Đã Huỷ Bỏ</th>
                                    <th class="text-right" style="width: 130px">Tổng Sử Dụng</th>
                                    <th class="text-right" style="width: 140px">Quy Đổi (Kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report as $row)
                                    <tr data-classification="{{ $expCls($row->classification) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td><span class="exp-code">{{ $row->category_code }}</span></td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                {{ $row->code_count }} mã xuất nhập · đơn vị {{ $row->unit }}
                                                @if ($row->density !== null)
                                                    · d = {{ $expNum($row->density) }} g/ml
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $row->times }}">
                                            {{ $row->times }}
                                        </td>
                                        <td class="text-right" data-order="{{ $row->used }}">
                                            <span
                                                class="exp-amount {{ $row->used > 0 ? '' : 'md-empty' }}">{{ $expNum($row->used) }}</span>
                                            <div class="md-sub">{{ $row->unit }}</div>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->cancelled }}">
                                            <span
                                                class="exp-amount {{ $row->cancelled > 0 ? '' : 'md-empty' }}">{{ $expNum($row->cancelled) }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->total }}">
                                            <span class="exp-amount">{{ $expNum($row->total) }}</span>
                                            <span class="md-sub">{{ $row->unit }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->total_kg ?? -1 }}">
                                            @if ($row->total_kg !== null)
                                                <span class="exp-kg">{{ $expNum($row->total_kg) }}</span>
                                                <span class="md-sub">kg</span>
                                            @else
                                                <span class="exp-kg-na" title="{{ $row->convert_note }}">Không quy
                                                    đổi</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @if ($report->count())
                                <tfoot>
                                    <tr>
                                        <th colspan="6" class="text-right">Tổng cộng quy đổi</th>
                                        <th class="text-right md-sub">
                                            {{ $expReportNotConvertible }} mã không quy đổi được
                                        </th>
                                        <th class="text-right">
                                            <span class="exp-kg">{{ $expNum($expReportTotalKg) }}</span>
                                            <span class="md-sub">kg</span>
                                        </th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bảng dùng chung sắp theo cột 1, riêng phiếu sử dụng cần xem lần gần nhất trước
        $('#mdTable').DataTable().order([5, 'desc']).draw();
    });
</script>
