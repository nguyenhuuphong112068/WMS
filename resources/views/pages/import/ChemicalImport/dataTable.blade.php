@include('pages.import.shared.assets')

@php
    $impToday = \Carbon\Carbon::today();
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="imp-tabs">
                    <button type="button" class="imp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}"
                        data-pane="impPaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ nhập hoá chất
                    </button>
                    <button type="button" class="imp-tab {{ $activeTab === 'transfer' ? 'is-active' : '' }}"
                        data-pane="impPaneTransfer">
                        <i class="fas fa-truck-arrow-right mr-1"></i> Hàng chờ nhận
                        @if ($pendingTransfers->count())
                            <span class="imp-tab-count">{{ $pendingTransfers->count() }}</span>
                        @endif
                    </button>
                    <button type="button" class="imp-tab {{ $activeTab === 'report' ? 'is-active' : '' }}"
                        data-pane="impPaneReport">
                        <i class="fas fa-chart-column mr-1"></i> Báo cáo nhập hoá chất
                    </button>
                </div>

                {{-- ============ SỔ NHẬP HOÁ CHẤT ============ --}}
                <div class="imp-pane {{ $activeTab === 'book' ? 'is-active' : '' }}" id="impPaneBook">

                <div class="md-toolbar">
                    @perm('import_chemical_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Nhập hoá chất
                        </button>
                    @endperm
                    <p class="hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đang hiệu lực {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} phiếu.
                    </p>
                </div>

                @include('pages.shared.barcodeSearch', [
                    'scanTitle' => 'Quét mã vạch',
                    'scanTables' => [
                        ['id' => 'mdTable', 'column' => 1, 'pane' => 'impPaneBook', 'label' => 'Sổ nhập hoá chất'],
                        [
                            'id' => 'impTransferTable',
                            'column' => 1,
                            'pane' => 'impPaneTransfer',
                            'label' => 'Hàng chờ nhận',
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
                                <th style="width: 110px">Số Lô</th>
                                <th style="width: 180px"
                                    title="Vị trí lưu trữ thực tế của lô hàng này (Kho / Phòng / Kệ/Tủ / Vị trí)">
                                    Vị Trí Lưu Trữ</th>
                                <th class="text-center" style="width: 100px">Ngày Nhập</th>
                                <th class="text-center" style="width: 100px">Hạn Dùng</th>
                                <th style="width: 150px">Nhà Cung Cấp</th>
                                <th style="width: 140px">Hoá Đơn</th>
                                <th style="width: 130px">Người Nhập</th>
                                <th class="text-center" style="width: 150px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @php
                                    $impExpired = $row->expired_date ? \Carbon\Carbon::parse($row->expired_date) : null;
                                    $impExpiredClass = '';
                                    if ($impExpired) {
                                        $impExpiredClass = $impExpired->lt($impToday)
                                            ? 'imp-expired'
                                            : ($impExpired->lte($impToday->copy()->addDays(30))
                                                ? 'imp-expiring'
                                                : '');
                                    }
                                @endphp
                                {{-- data-classification để bộ lọc Phụ lục / Nhóm hoá chất nhận ra dòng này --}}
                                <tr data-classification="{{ $impCls($row->category_id) }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><span class="imp-code">{{ $row->code }}</span></td>
                                    <td>
                                        <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                                        <div class="md-sub">
                                            <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                            @if ($row->is_microbiological_chemicals)
                                                <span class="imp-flag ml-1">Vi sinh</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <span class="imp-amount">{{ rtrim(rtrim(number_format($row->amount, 4, '.', ','), '0'), '.') }}</span>
                                        <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                    </td>
                                    <td class="md-sub">{{ $row->batch_no ?: '—' }}</td>
                                    <td class="md-sub">
                                        @if ($row->location_code)
                                            <div class="font-weight-bold">
                                                <span class="md-tag">{{ $row->location_code }}</span>
                                            </div>
                                            <div>{{ $row->warehouse_name ?: '—' }} / {{ $row->room_name ?: '—' }} /
                                                {{ $row->shelf_name ?: '—' }}</div>
                                        @else
                                            <span class="imp-no-location">Chưa xếp vị trí</span>
                                        @endif
                                    </td>
                                    <td class="text-center md-sub">
                                        {{ $row->imported_date ? \Carbon\Carbon::parse($row->imported_date)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="text-center md-sub {{ $impExpiredClass }}">
                                        {{ $impExpired ? $impExpired->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="md-sub">
                                        @if ($row->supplier_name)
                                            <div class="imp-supplier" title="{{ $row->supplier_name }}">
                                                {{ $row->supplier_name }}</div>
                                            @if ($row->supplier_address)
                                                <div class="imp-supplier-address" title="{{ $row->supplier_address }}">
                                                    <i class="fas fa-location-dot"></i> {{ $row->supplier_address }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">
                                        @if ($row->invoice_number)
                                            {{ $row->invoice_number }}
                                            @if ($row->invoice_date)
                                                <br><small>{{ \Carbon\Carbon::parse($row->invoice_date)->format('d/m/Y') }}</small>
                                            @endif
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $row->imported_by ?: '—' }}</td>
                                    <td>
                                        <div class="md-actions">
                                            @php $impAdjust = (int) ($historyCounts[$row->id] ?? 0); @endphp
                                            {{-- Badge số lần điều chỉnh nằm ở góc trên bên phải nút Sửa --}}
                                            <span class="imp-btn-wrap">
                                                @perm('import_chemical_update')
                                                    <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                        title="Điều chỉnh thông tin nhập"
                                                        data-row="{{ json_encode([
                                                            'id' => $row->id,
                                                            'category_id' => $row->category_id,
                                                            'amount' => $row->amount,
                                                            'imported_date' => $row->imported_date,
                                                            'imported_by' => $row->imported_by,
                                                            'invoice_number' => $row->invoice_number,
                                                            'invoice_date' => $row->invoice_date,
                                                            'expired_date' => $row->expired_date,
                                                            'is_microbiological_chemicals' => $row->is_microbiological_chemicals,
                                                            'batch_no' => $row->batch_no,
                                                            'supplier_id' => $row->supplier_id,
                                                            'note' => $row->note,
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endperm

                                                @if ($impAdjust > 0)
                                                    <button type="button" class="imp-count-badge btn-imp-history"
                                                        title="Xem {{ $impAdjust }} lần điều chỉnh của phiếu này"
                                                        data-url="{{ route($impRoute . 'history', ['id' => $row->id]) }}"
                                                        data-title="{{ $row->code }} - {{ $row->chem_name }}">{{ $impAdjust }}</button>
                                                @endif
                                            </span>

                                            <a class="btn btn-sm btn-outline-secondary" target="_blank"
                                                title="In nhãn dán lô hàng (mã vạch Code 128) - chọn được số lượng nhãn cần in"
                                                @perm('import_chemical_label')
                                                    href="{{ route($impRoute . 'label', ['id' => $row->id]) }}">
                                                    <i class="fas fa-tag"></i>
                                                </a>
                                                @endperm

                                            @perm('import_chemical_delete')
                                                <form class="form-md-confirm d-inline" action="{{ route($impRoute . 'deActive') }}"
                                                    method="POST"
                                                    data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $impLabel }}?"
                                                    data-text="{{ $row->status_id == 1 ? 'Sau khi khoá' : 'Sau khi mở khoá' }}, phiếu &quot;{{ $row->code }}&quot; {{ $row->status_id == 1 ? 'sẽ không còn được tính vào tồn kho.' : 'sẽ được tính vào tồn kho trở lại.' }}"
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

                {{-- ============ HÀNG CHỜ NHẬN TỪ PHÒNG BAN KHÁC ============ --}}
                <div class="imp-pane {{ $activeTab === 'transfer' ? 'is-active' : '' }}" id="impPaneTransfer">

                    <div class="md-toolbar">
                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            Hoá chất do phòng ban khác chuyển sang, <b>chưa nằm trong tồn kho của phòng mình</b>.
                            Bấm <b>Nhận</b> để khai định khu và các thông tin riêng của kho phòng mình - lúc đó lô
                            mới được cấp mã (giữ nguyên mã gốc kèm hậu tố <b>-CK</b>) và cộng vào tồn.
                        </p>
                    </div>

                    <div class="table-responsive">
                        <table id="impTransferTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 150px">Mã Gốc</th>
                                    <th>Hoá Chất</th>
                                    <th class="text-right" style="width: 110px">Số Lượng</th>
                                    <th style="width: 150px">Phòng Ban Gửi</th>
                                    <th class="text-center" style="width: 105px">Ngày Chuyển</th>
                                    <th class="text-center" style="width: 105px">Hạn Dùng</th>
                                    <th style="width: 130px">Người Chuyển</th>
                                    <th class="text-center" style="width: 110px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingTransfers as $transfer)
                                    @php $impTransferUnit = $transfer->unit_short_name ?: $transfer->unit_name; @endphp
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td><span class="imp-code">{{ $transfer->source_code }}</span></td>
                                        <td>
                                            <div class="font-weight-bold">{{ $transfer->chem_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $transfer->category_code ?: '—' }}</span>
                                                @if ($transfer->batch_no)
                                                    <span class="ml-1">Lô {{ $transfer->batch_no }}</span>
                                                @endif
                                                @if ($transfer->is_microbiological_chemicals)
                                                    <span class="imp-flag ml-1">Vi sinh</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-right" data-order="{{ $transfer->amount }}">
                                            <span class="imp-amount">{{ $impNum($transfer->amount) }}</span>
                                            <div class="md-sub">{{ $impTransferUnit }}</div>
                                            @if ($transfer->is_partial)
                                                <span class="imp-lot-kind partial"
                                                    title="Nhận lẻ: lô nguồn nhập {{ $impNum($transfer->full_lot_amount) }} {{ $impTransferUnit }} và đã bị đụng vào (cân chia, cân đối hoặc dùng bớt). Số lượng chốt cứng, không xuất vượt và không cân đối được, nhưng kế thừa luôn hạn dùng nội bộ.">Nhận
                                                    lẻ</span>
                                            @else
                                                <span class="imp-lot-kind full"
                                                    title="Nhận nguyên: lô còn y nguyên như lúc phòng gửi nhập ({{ $impNum($transfer->full_lot_amount) }} {{ $impTransferUnit }}), chưa cân đối, chưa xuất lần nào. Vẫn được xuất vượt 5% và cân đối, nhưng phải tự xác định hạn dùng nội bộ.">Nhận
                                                    nguyên</span>
                                            @endif
                                        </td>
                                        <td class="md-sub">{{ $transfer->from_department_name ?: '—' }}</td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $transfer->exported_date }}">
                                            {{ $impDate($transfer->exported_date) }}
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $transfer->expired_date ?: '9999-12-31' }}">
                                            {{ $impDate($transfer->expired_date) }}
                                        </td>
                                        <td class="md-sub">{{ $transfer->exported_by ?: '—' }}</td>
                                        <td class="text-center">
                                            @perm('import_chemical_receive')
                                                <button type="button" class="btn btn-sm btn-primary btn-imp-receive"
                                                    data-row="{{ json_encode([
                                                        'export_id' => $transfer->id,
                                                        'source_code' => $transfer->source_code,
                                                        'chem_name' => $transfer->chem_name,
                                                        'category_code' => $transfer->category_code,
                                                        'amount' => $impNum($transfer->amount) . ' ' . $impTransferUnit,
                                                        'batch_no' => $transfer->batch_no,
                                                        'expired_date' => $impDate($transfer->expired_date),
                                                        'from_department' => $transfer->from_department_name,
                                                        'exported_date' => $impDate($transfer->exported_date),
                                                        'exported_by' => $transfer->exported_by,
                                                        'purpose' => $transfer->purpose,
                                                    ]) }}">
                                                    <i class="fas fa-inbox mr-1"></i> Nhận
                                                </button>
                                            @endperm

                                            @perm('import_chemical_rejectTransfer')
                                                <button type="button" class="btn btn-sm btn-secondary btn-imp-reject"
                                                    title="Từ chối nhận, trả số lượng lại tồn của phòng gửi"
                                                    data-id="{{ $transfer->id }}"
                                                    data-title="{{ $transfer->source_code }} - {{ $transfer->chem_name }} ({{ $impNum($transfer->amount) }} {{ $impTransferUnit }}) từ {{ $transfer->from_department_name }}">
                                                    <i class="fas fa-xmark"></i>
                                                </button>
                                            @endperm
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ BÁO CÁO NHẬP HOÁ CHẤT ============ --}}
                <div class="imp-pane {{ $activeTab === 'report' ? 'is-active' : '' }}" id="impPaneReport">

                    <form method="GET" action="{{ route($impRoute . 'list') }}" class="imp-range">
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

                        <div class="imp-quick">
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
                            Cộng dồn các phiếu nhập còn hiệu lực từ <b>{{ $impDate($reportFrom) }}</b> đến
                            <b>{{ $impDate($reportTo) }}</b>, gom theo mã danh mục hoá chất
                            ({{ $report->count() }} mã, {{ $impNum($impReportTimes) }} lượt nhập). Cột <b>Quy Đổi
                                (Kg)</b> dùng tỉ trọng d (g/ml) khai báo trong Danh Mục Hoá Chất.
                        </p>
                    </div>

                    @include('pages.shared.classificationFilter', ['clsTarget' => 'impReportTable'])

                    <div class="table-responsive">
                        <table id="impReportTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px">STT</th>
                                    <th style="width: 120px">Mã Danh Mục</th>
                                    <th>Hoá Chất</th>
                                    <th class="text-center" style="width: 90px">Lượt Nhập</th>
                                    <th class="text-center" style="width: 150px">Lần Nhập Gần Nhất</th>
                                    <th class="text-right" style="width: 140px">Tổng Nhập</th>
                                    <th class="text-right" style="width: 140px">Quy Đổi (Kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report as $row)
                                    <tr data-classification="{{ $impCls($row->category_id) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td><span class="imp-code">{{ $row->category_code }}</span></td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                {{ $row->supplier_count }} nhà cung cấp · đơn vị {{ $row->unit }}
                                                @if ($row->density !== null)
                                                    · d = {{ $impNum($row->density) }} g/ml
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $row->times }}">
                                            {{ $row->times }}
                                        </td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->last_imported_date ?: '0000-00-00' }}">
                                            {{ $impDate($row->last_imported_date) }}
                                        </td>
                                        <td class="text-right" data-order="{{ $row->total }}">
                                            <span class="imp-amount">{{ $impNum($row->total) }}</span>
                                            <span class="md-sub">{{ $row->unit }}</span>
                                        </td>
                                        <td class="text-right" data-order="{{ $row->total_kg ?? -1 }}">
                                            @if ($row->total_kg !== null)
                                                <span class="imp-kg">{{ $impNum($row->total_kg) }}</span>
                                                <span class="md-sub">kg</span>
                                            @else
                                                <span class="imp-kg-na" title="{{ $row->convert_note }}">Không quy
                                                    đổi</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @if ($report->count())
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-right">Tổng cộng quy đổi</th>
                                        <th class="text-right md-sub">
                                            {{ $impReportNotConvertible }} mã không quy đổi được
                                        </th>
                                        <th class="text-right">
                                            <span class="imp-kg">{{ $impNum($impReportTotalKg) }}</span>
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
        // Bảng dùng chung sắp theo cột 1, riêng phiếu nhập cần xem lần nhập gần nhất trước
        // (cột 6 = Ngày Nhập, sau khi thêm cột Vị Trí Lưu Trữ)
        $('#mdTable').DataTable().order([6, 'desc']).draw();
    });
</script>
