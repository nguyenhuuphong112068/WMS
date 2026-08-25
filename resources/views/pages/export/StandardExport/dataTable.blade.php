@include('pages.export.shared.assets')

<style>
    /* Mã nhóm chuẩn của ống - phần nằm giữa mã ống chuẩn */
    .sd-group-tag {
        display: inline-block;
        background: #EDE9FE;
        color: #5B21B6;
        border: 1px solid #C4B5FD;
        border-radius: 999px;
        padding: 1px 9px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }
</style>

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="exp-tabs">
                    <button type="button" class="exp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}"
                        data-pane="expPaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ sử dụng chất chuẩn
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'request' ? 'is-active' : '' }}"
                        data-pane="expPaneRequest">
                        <i class="fas fa-hand-holding-medical mr-1"></i> Đề nghị cấp phát chuẩn
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'report' ? 'is-active' : '' }}"
                        data-pane="expPaneReport">
                        <i class="fas fa-chart-column mr-1"></i> Báo cáo sử dụng chất chuẩn
                    </button>
                </div>

                {{-- ============ SỔ SỬ DỤNG CHẤT CHUẨN ============ --}}
                <div class="exp-pane {{ $activeTab === 'book' ? 'is-active' : '' }}" id="expPaneBook">

                    <div class="md-toolbar">
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Sử dụng chất chuẩn
                        </button>
                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            Đang hiệu lực {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} phiếu.
                            Được xuất vượt tồn tối đa <b>{{ $overIssuePercent }}%</b> để bù sai số cân đong.
                        </p>
                    </div>

                    @include('pages.shared.barcodeSearch', [
                        'scanTitle' => 'Quét mã vạch',
                        'scanTables' => [
                            ['id' => 'mdTable', 'column' => 1, 'pane' => 'expPaneBook', 'label' => 'Sổ sử dụng chất chuẩn'],
                        ],
                    ])

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'mdTable'])

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px">STT</th>
                                    <th style="width: 155px">Mã Ống Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th style="width: 110px">Tổ</th>
                                    <th class="text-right" style="width: 100px">Số Lượng</th>
                                    <th class="text-center" style="width: 95px">Loại Phiếu</th>
                                    <th class="text-center" style="width: 100px">Ngày Dùng</th>
                                    <th style="width: 140px">Sản Phẩm & KNV</th>
                                    <th style="width: 140px">Số PKN/OOS</th>
                                    <th style="width: 150px">Mục Đích</th>
                                    <th style="width: 120px">Người Dùng</th>
                                    <th style="width: 120px">Người KT</th>
                                    <th class="text-center" style="width: 110px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($datas as $row)
                                    <tr data-groups="{{ $expGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="exp-code">{{ $row->code }}</span>
                                            <div class="mt-1">
                                                <span class="sd-group-tag">{{ $expGroupName($row->group_code) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                                <span class="sgr-version ml-1">v{{ $row->category_version }}</span>
                                                @if ($row->batch_no)
                                                    <span class="ml-1">Lô {{ $row->batch_no }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($row->group_name)
                                                <span class="font-weight-bold text-primary">{{ $row->group_name }}</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="text-right" data-order="{{ $row->amount }}">
                                            <span class="exp-amount">{{ $expNum($row->amount) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="exp-badge exp-badge-{{ $row->type }}">
                                                {{ $types[$row->type] ?? $row->type }}
                                            </span>
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $row->exported_date }}">
                                            {{ $expDate($row->exported_date) }}
                                        </td>
                                        <td class="md-sub">
                                            @if ($row->product_name)
                                                <div class="font-weight-bold text-dark">{{ $row->product_name }}</div>
                                            @endif
                                            @if ($row->analyst_name)
                                                <small class="text-muted"><i class="fas fa-user-check mr-1"></i>{{ $row->analyst_name }}</small>
                                            @endif
                                            @if (!$row->product_name && !$row->analyst_name)
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="md-sub">{{ $row->test_report_no ?: '—' }}</td>
                                        <td class="md-sub">
                                            @if ($row->purpose)
                                                <span class="md-note" title="{{ $row->purpose }}">{{ $row->purpose }}</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="md-sub">{{ $row->exported_by ?: '—' }}</td>
                                        <td class="md-sub">{{ $row->checked_by ?: '—' }}</td>
                                        <td>
                                            <div class="md-actions">
                                                @php $expAdjust = (int) ($adjustCounts[$row->id] ?? 0); @endphp
                                                <span class="exp-btn-wrap">
                                                    <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                        title="Cập nhật phiếu"
                                                        data-row="{{ json_encode([
                                                            'id' => $row->id,
                                                            'import_id' => $row->import_id,
                                                            'group_id' => $row->group_id,
                                                            'amount' => $row->amount,
                                                            'type' => $row->type,
                                                            'exported_date' => $row->exported_date,
                                                            'product_name' => $row->product_name,
                                                            'analyst_id' => $row->analyst_id,
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
                                                            data-title="{{ $row->code }} - {{ $row->standard_name }}">{{ $expAdjust }}</button>
                                                    @endif
                                                </span>

                                                <form class="form-md-confirm d-inline"
                                                    action="{{ route($expRoute . 'deActive') }}" method="POST"
                                                    data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $expLabel }}?"
                                                    data-text="{{ $row->status_id == 1 ? 'Sau khi khoá, phiếu này sẽ không còn trừ tồn của ống chuẩn' : 'Sau khi mở khoá, phiếu này sẽ trừ tồn của ống chuẩn trở lại' }} &quot;{{ $row->code }}&quot;."
                                                    data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                                    <button type="submit"
                                                        class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                        title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                        <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
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

                {{-- ============ ĐỀ NGHỊ CẤP PHÁT CHUẨN ============ --}}
                @include('pages.export.StandardExport.requestPane')

                {{-- ============ BÁO CÁO SỬ DỤNG CHẤT CHUẨN ============ --}}
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
                            <i class="fas fa-info-circle mr-1"></i>
                            Cộng dồn các phiếu sử dụng còn hiệu lực từ <b>{{ $expDate($reportFrom) }}</b> đến
                            <b>{{ $expDate($reportTo) }}</b>, gom theo mã danh mục chất chuẩn
                            ({{ $report->count() }} mã, {{ $expNum($expReportTimes) }} lượt xuất). Cột
                            <b>Huỷ Bỏ</b> tách riêng phần hao hụt do ống hỏng hoặc quá hạn.
                        </p>
                    </div>

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'expReportTable'])

                    <div class="table-responsive">
                        <table id="expReportTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 110px">Mã Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th class="text-center" style="width: 85px">Số Ống</th>
                                    <th class="text-center" style="width: 150px">Lần Dùng Gần Nhất</th>
                                    <th class="text-right" style="width: 130px">Đã Dùng</th>
                                    <th class="text-right" style="width: 130px">Huỷ Bỏ</th>
                                    <th class="text-right" style="width: 130px">Tổng Xuất</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report as $row)
                                    <tr data-groups="{{ $expGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td><span class="exp-code">{{ $row->category_code }}</span></td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                v{{ $row->version }} · đơn vị {{ $row->unit }} ·
                                                {{ $row->times }} lượt xuất
                                            </div>
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $row->code_count }}">
                                            {{ $row->code_count }}
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->last_exported_date ?: '0000-00-00' }}">
                                            {{ $expDate($row->last_exported_date) }}
                                        </td>
                                        <td class="text-right" data-order="{{ $row->used }}">
                                            <span class="exp-amount">{{ $expNum($row->used) }}</span>
                                            <span class="md-sub">{{ $row->unit }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->cancelled }}">
                                            <span class="exp-amount">{{ $expNum($row->cancelled) }}</span>
                                            <span class="md-sub">{{ $row->unit }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->total }}">
                                            <span class="exp-amount">{{ $expNum($row->total) }}</span>
                                            <span class="md-sub">{{ $row->unit }}</span>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Bảng dùng chung sắp theo cột 1, riêng sổ sử dụng cần xem lần dùng gần nhất trước
        // (cột 6 = Ngày Sử Dụng)
        $('#mdTable').DataTable().order([6, 'desc']).draw();
    });
</script>
