@include('pages.import.shared.assets')
@php $activeTab = \App\Support\PageTabs::active('import_chemical', $activeTab ?? 'check'); @endphp

@php
    $impToday = \Carbon\Carbon::today();
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="imp-tabs">
                    @tab('import_chemical', 'check')
                    <button type="button" class="imp-tab {{ $activeTab === 'check' ? 'is-active' : '' }}" data-pane="impPaneCheck">
                        <i class="fas fa-clipboard-check mr-1"></i> Nhập hoá chất
                        @if ($pendingCount > 0)
                            <span class="imp-tab-count">{{ $pendingCount }}</span>
                        @endif
                    </button>
                    @endtab
                    @tab('import_chemical', 'book')
                    <button type="button" class="imp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}"
                        data-pane="impPaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ nhập hoá chất
                    </button>
                    @endtab
                    @tab('import_chemical', 'report')
                    <button type="button" class="imp-tab {{ $activeTab === 'report' ? 'is-active' : '' }}"
                        data-pane="impPaneReport">
                        <i class="fas fa-chart-column mr-1"></i> Báo cáo nhập hoá chất
                    </button>
                    @endtab
                </div>

                {{-- ============ SỔ NHẬP HOÁ CHẤT ============ --}}
                @tab('import_chemical', 'book')
                <div class="imp-pane {{ $activeTab === 'book' ? 'is-active' : '' }}" id="impPaneBook">

                @include('pages.shared.rangeFilter', [
                    'rfRoute' => $impRoute . 'list',
                    'rfTab' => 'book',
                    'rfPrefix' => 'book_',
                    'rfRange' => $bookRange,
                    'rfPerPage' => $bookPerPage,
                    'rfKeyword' => $bookKeyword,
                    'rfDateLabel' => 'Ngày nhập',
                    'rfPlaceholder' => 'Mã xuất nhập, tên hoá chất, số lô, hoá đơn, NCC...',
                ])

                @include('pages.shared.barcodeSearch', [
                    'scanTitle' => 'Quét mã vạch',
                    'scanTables' => [
                        ['id' => 'mdTable', 'column' => 1, 'pane' => 'impPaneBook', 'label' => 'Sổ nhập hoá chất'],
                    ],
                ])

                @include('pages.shared.classificationFilter', ['clsTarget' => 'mdTable'])

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100" data-server-paged>
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
                                <th style="width: 150px">Tình Trạng</th>
                                <th class="text-center" style="width: 60px" title="File hồ sơ đính kèm"><i
                                        class="fas fa-paperclip"></i></th>
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
                                    $rowAttachments = $attachments->get($row->id) ?? collect();
                                    $rowCodeBadge = \App\Support\ZoneType::badge($row->location_id, $row->location_zone_type ?? null, $row->location_color ?? null);
                                @endphp
                                {{-- data-classification để bộ lọc Phụ lục / Nhóm hoá chất nhận ra dòng này --}}
                                <tr data-classification="{{ $impCls($row->category_id) }}">
                                    <td class="text-center">{{ $datas->firstItem() + $loop->index }}</td>
                                    <td>
                                        <span class="imp-code"
                                            style="--chip: {{ $rowCodeBadge['bg'] }}; --chip-text: {{ $rowCodeBadge['text'] }}">{{ $row->code }}</span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">
                                            {{ $row->chem_name ?: '—' }}
                                            @if ($impIsSpecial($row->category_id))
                                                <span class="badge-special-control ml-1" title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">
                                                    <i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt
                                                </span>
                                            @endif
                                        </div>
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
                                                {{ \App\Support\ZoneType::tag($row->location_code, $row->location_zone_type ?? null) }}
                                            </div>
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
                                    <td class="md-sub">
                                        @php $rowCheck = $row->check_result; @endphp
                                        <span class="badge {{ \App\Support\CheckStatus::badge($rowCheck) }}">
                                            <i class="{{ \App\Support\CheckStatus::icon($rowCheck) }} mr-1"></i>
                                            {{ \App\Support\CheckStatus::label($rowCheck) }}
                                        </span>
                                        @if ($row->checked_at)
                                            <div class="mt-1" title="Ngày kiểm tra">{{ $impDate($row->checked_at) }}</div>
                                            <div title="Người kiểm tra">{{ $row->checked_by ?: '—' }}</div>
                                        @endif
                                        @if ($row->check_note)
                                            <div class="text-danger" title="{{ $row->check_note }}">
                                                {{ \Illuminate\Support\Str::limit($row->check_note, 40) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @include('pages.shared.attachmentList', [
                                            'attachments' => $rowAttachments,
                                            'routePrefix' => $impRoute,
                                            'uploadPerm' => 'import_chemical_attachment_upload',
                                            'parentId' => $row->id,
                                            'statusPerm' => 'import_chemical_attachment_status',
                                            'code' => $row->code,
                                            'name' => $row->chem_name,
                                            'typeLabel' => 'Hoá chất',
                                        ])
                                    </td>
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
                                                            'category_id_text' => \App\Support\CategoryLookup::chemicalCategoryLabel((object) ['code' => $row->category_code, 'chem_name' => $row->chem_name, 'unit_short_name' => $row->unit_short_name], true),
                                                            'amount' => $row->amount,
                                                            'imported_date' => $row->imported_date,
                                                            'imported_by' => $row->imported_by,
                                                            'invoice_number' => $row->invoice_number,
                                                            'invoice_date' => $row->invoice_date,
                                                            'expired_date' => $row->expired_date,
                                                            'is_microbiological_chemicals' => $row->is_microbiological_chemicals,
                                                            'batch_no' => $row->batch_no,
                                                            'supplier_id' => $row->supplier_id,
                                                            'location_id' => $row->location_id,
                                                            'location_id_text' => \App\Support\CategoryLookup::locationLabel($row, 'location_code'),
                                                            'location_id_zone' => $row->location_zone_type,
                                                            'note' => $row->note,
                                                            'attachments' => $rowAttachments->map(fn($a) => ['id' => $a->id, 'file_name' => $a->file_name])->toArray(),
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

                @include('pages.shared.paginator', [
                    'pgItems' => $datas,
                    'pgTab' => 'book',
                    'pgUnit' => 'phiếu nhập',
                ])
                </div>
                @endtab

                {{-- ============ NHẬP HOÁ CHẤT (lô chờ kiểm tra) ============ --}}
                @tab('import_chemical', 'check')
                <div class="imp-pane {{ $activeTab === 'check' ? 'is-active' : '' }}" id="impPaneCheck">
                    <div class="md-toolbar">
                        @perm('import_chemical_create')
                            <button type="button" class="btn btn-primary btn-md-create">
                                <i class="fas fa-plus mr-1"></i> Nhập hoá chất
                            </button>
                        @endperm
                    </div>

                    <div class="table-responsive">
                        <table id="mdTableCheck" class="table table-bordered table-hover w-100 md-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 45px">STT</th>
                                    <th style="width: 150px">Mã Xuất Nhập</th>
                                    <th>Hoá Chất</th>
                                    <th class="text-right" style="width: 110px">Số Lượng</th>
                                    <th style="width: 110px">Số Lô</th>
                                    <th style="width: 150px">Nhà Cung Cấp</th>
                                    <th class="text-center" style="width: 95px">Ngày Nhập</th>
                                    <th class="text-center" style="width: 105px">Hạn Dùng</th>
                                    <th style="width: 160px">Vị Trí Biệt Trữ</th>
                                    <th class="text-center" style="width: 150px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingRows as $row)
                                    @php
                                        $pendingAmount =
                                            $impNum($row->amount) . ' ' . ($row->unit_short_name ?: $row->unit_name);
                                        $pendingLocation = $row->location_code
                                            ? $row->location_code .
                                                ($row->warehouse_name ? ' — ' . $row->warehouse_name : '')
                                            : 'Chưa định khu';
                                        $pendingCodeBadge = \App\Support\ZoneType::badge($row->location_id, $row->location_zone_type ?? null, $row->location_color ?? null);
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="imp-code"
                                                style="--chip: {{ $pendingCodeBadge['bg'] }}; --chip-text: {{ $pendingCodeBadge['text'] }}">{{ $row->code }}</span>
                                            <div><span class="badge badge-warning mt-1">Chờ kiểm tra</span></div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                                            <div class="md-sub"><span
                                                    class="md-tag">{{ $row->category_code ?: '—' }}</span></div>
                                        </td>
                                        <td class="text-right">
                                            <span class="imp-amount">{{ $impNum($row->amount) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="md-sub">{{ $row->batch_no ?: '—' }}</td>
                                        <td class="md-sub">{{ $row->supplier_name ?: '—' }}</td>
                                        <td class="text-center md-sub" data-order="{{ $row->imported_date }}">
                                            {{ $impDate($row->imported_date) }}</td>
                                        <td class="text-center md-sub"
                                            data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                            {{ $impDate($row->expired_date) }}</td>
                                        <td class="md-sub">
                                            @if ($row->location_code)
                                                {{ \App\Support\ZoneType::tag($row->location_code, $row->location_zone_type ?? null) }}
                                            @else
                                                <span class="imp-no-location">Chưa định khu</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @perm('import_chemical_update')
                                                <button type="button" class="btn btn-sm btn-success btn-imp-check"
                                                    title="Xác nhận kiểm tra - bổ sung thông tin, định khu vị trí thật"
                                                    data-row="{{ json_encode([
                                                        'id' => $row->id,
                                                        'code' => $row->code,
                                                        'chem_name' => $row->chem_name,
                                                        'amount_label' => $pendingAmount,
                                                        'imported_date_label' => $impDate($row->imported_date),
                                                        'location_label' => $pendingLocation,
                                                        'batch_no' => $row->batch_no,
                                                        'invoice_number' => $row->invoice_number,
                                                        'invoice_date' => $row->invoice_date,
                                                        'expired_date' => $row->expired_date,
                                                        'supplier_id' => $row->supplier_id,
                                                        'location_id' => $row->location_id,
                                                        'location_id_text' => \App\Support\CategoryLookup::locationLabel($row, 'location_code'),
                                                        'location_id_zone' => $row->location_zone_type,
                                                    ]) }}">
                                                    <i class="fas fa-clipboard-check mr-1"></i> Kiểm tra
                                                </button>
                                            @endperm
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endtab

                {{-- ============ BÁO CÁO NHẬP HOÁ CHẤT ============ --}}
                @tab('import_chemical', 'report')
                <div class="imp-pane {{ $activeTab === 'report' ? 'is-active' : '' }}" id="impPaneReport">

                    <form method="GET" action="{{ route($impRoute . 'list') }}" class="imp-range">
                        {{-- Lọc xong trang tải lại, cờ này đưa người dùng về đúng tab báo cáo --}}
                        <input type="hidden" name="tab" value="report">
                        <input type="hidden" name="rsub" value="{{ request('rsub') === 'other' ? 'other' : '' }}" class="imp-rsub">

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

                    @php
                        $impTransferSame = $reports['transferSame'];
                        $impTransferOther = $reports['transferOther'];
                        $impTransferSub = request('rsub') === 'other' ? 'other' : 'same';
                    @endphp

                    {{-- Hoá chất MUA (nhập từ nhà cung cấp) - không gồm hàng nhận chuyển liên phòng ban --}}
                    <div class="imp-report-card">
                        <div class="imp-report-card-header">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="imp-report-card-title">Hoá chất mua</span>
                            <span class="imp-report-card-count">{{ $reports['purchase']->count() }}</span>
                        </div>
                        <div class="imp-report-card-body">
                            @include('pages.import.ChemicalImport.reportTable', [
                                'rpId' => 'impReportTable',
                                'rpRows' => $reports['purchase'],
                                'rpTransfer' => false,
                                'rpEmpty' => 'Không có hoá chất mua nào trong khoảng thời gian đã chọn.',
                            ])
                        </div>
                    </div>

                    {{-- Nhận chuyển liên phòng ban: tab con cùng công ty / khác công ty --}}
                    <div class="imp-report-card is-transfer">
                        <div class="imp-report-card-header">
                            <i class="fas fa-people-arrows"></i>
                            <span class="imp-report-card-title">Nhận chuyển liên phòng ban</span>
                            <span class="imp-report-card-count">{{ $impTransferSame->count() + $impTransferOther->count() }}</span>
                        </div>
                        <div class="imp-report-card-body">
                            <ul class="nav nav-tabs imp-subtabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link {{ $impTransferSub === 'same' ? 'active' : '' }}" data-toggle="tab" role="tab"
                                        href="#impReportSamePane" data-sub-value="">
                                        <i class="fas fa-building mr-1"></i> Cùng công ty
                                        <span class="badge badge-primary">{{ $impTransferSame->count() }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $impTransferSub === 'other' ? 'active' : '' }}" data-toggle="tab" role="tab"
                                        href="#impReportOtherPane" data-sub-value="other">
                                        <i class="fas fa-city mr-1"></i> Khác công ty
                                        <span class="badge badge-secondary">{{ $impTransferOther->count() }}</span>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade {{ $impTransferSub === 'same' ? 'show active' : '' }}" id="impReportSamePane" role="tabpanel">
                                    @include('pages.import.ChemicalImport.reportTable', [
                                        'rpId' => 'impReportSameTable',
                                        'rpRows' => $impTransferSame,
                                        'rpTransfer' => true,
                                        'rpEmpty' => 'Không nhận chuyển hoá chất nào từ phòng cùng công ty trong khoảng thời gian đã chọn.',
                                    ])
                                </div>
                                <div class="tab-pane fade {{ $impTransferSub === 'other' ? 'show active' : '' }}" id="impReportOtherPane" role="tabpanel">
                                    @include('pages.import.ChemicalImport.reportTable', [
                                        'rpId' => 'impReportOtherTable',
                                        'rpRows' => $impTransferOther,
                                        'rpTransfer' => true,
                                        'rpEmpty' => 'Không nhận chuyển hoá chất nào từ phòng công ty khác trong khoảng thời gian đã chọn.',
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endtab

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
