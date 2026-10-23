@include('pages.import.shared.assets')
@php $activeTab = \App\Support\PageTabs::active('import_material', $activeTab ?? 'book'); @endphp

@php
    $impToday = \Carbon\Carbon::today();
@endphp

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                <div class="imp-tabs">
                    @tab('import_material', 'book')
                    <button type="button" class="imp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}" data-pane="impPaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ nhập vật tư
                    </button>
                    @endtab
                    @tab('import_material', 'check')
                    <button type="button" class="imp-tab {{ $activeTab === 'check' ? 'is-active' : '' }}" data-pane="impPaneCheck">
                        <i class="fas fa-clipboard-check mr-1"></i> Chờ kiểm tra
                        @if ($pendingCount > 0)
                            <span class="imp-tab-count">{{ $pendingCount }}</span>
                        @endif
                    </button>
                    @endtab
                </div>

                {{-- ============ SỔ NHẬP VẬT TƯ ============ --}}
                @tab('import_material', 'book')
                <div class="imp-pane {{ $activeTab === 'book' ? 'is-active' : '' }}" id="impPaneBook">

                <div class="md-toolbar">
                    @perm('import_material_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Nhập vật tư
                        </button>
                    @endperm
                </div>
                @endtab

                @include('pages.shared.rangeFilter', [
                    'rfRoute' => $impRoute . 'list',
                    'rfPrefix' => 'book_',
                    'rfRange' => $bookRange,
                    'rfPerPage' => $bookPerPage,
                    'rfKeyword' => $bookKeyword,
                    'rfDateLabel' => 'Ngày nhập',
                    'rfPlaceholder' => 'Mã xuất nhập, tên vật tư, số lô, hoá đơn, vị trí, ghi chú...',
                ])

                @include('pages.shared.barcodeSearch', [
                    'scanTitle' => 'Quét mã QR',
                    'scanTables' => [
                        ['id' => 'mdTable', 'column' => 1, 'pane' => 'impPaneBook', 'label' => 'Sổ nhập vật tư'],
                    ],
                ])

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100" data-server-paged>
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 45px">STT</th>
                                <th style="width: 165px">Mã Xuất Nhập</th>
                                <th>Vật Tư</th>
                                <th style="width: 150px">Quy Cách / Phân Loại</th>
                                <th class="text-right" style="width: 110px">Số Lượng</th>
                                <th style="width: 110px">Số Lô</th>
                                <th style="width: 120px">Số Hoá Đơn</th>
                                <th class="text-center" style="width: 100px">Ngày Hoá Đơn</th>
                                <th style="width: 170px">Vị Trí Lưu Trữ</th>
                                <th class="text-center" style="width: 95px">Ngày Nhập</th>
                                <th class="text-center" style="width: 110px">Hạn Dùng</th>
                                <th style="width: 160px">Mục Đích Sử Dụng</th>
                                <th style="width: 150px">Tình Trạng</th>
                                <th class="text-center" style="width: 60px" title="File hồ sơ đính kèm"><i
                                        class="fas fa-paperclip"></i></th>
                                <th class="text-center" style="width: 135px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @include('pages.import.MaterialImport.bookRow', ['stt' => $datas->firstItem() + $loop->index])
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('pages.shared.paginator', ['pgItems' => $datas, 'pgUnit' => 'phiếu nhập'])

                </div>
                {{-- ============ CHỜ KIỂM TRA ============ --}}
                @tab('import_material', 'check')
                <div class="imp-pane {{ $activeTab === 'check' ? 'is-active' : '' }}" id="impPaneCheck">
                    <div class="table-responsive">
                        <table id="mdTableCheck" class="table table-bordered table-hover w-100 md-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 45px">STT</th>
                                    <th style="width: 165px">Mã Xuất Nhập</th>
                                    <th>Vật Tư</th>
                                    <th class="text-right" style="width: 110px">Số Lượng</th>
                                    <th style="width: 110px">Số Lô</th>
                                    <th style="width: 120px">Số Hoá Đơn</th>
                                    <th class="text-center" style="width: 95px">Ngày Nhập</th>
                                    <th class="text-center" style="width: 110px">Hạn Dùng</th>
                                    <th style="width: 170px">Vị Trí Biệt Trữ</th>
                                    <th class="text-center" style="width: 150px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingRows as $row)
                                    @php
                                        $pendingAmount =
                                            $impNum($row->amount) .
                                            ' ' .
                                            ($row->unit_short_name ?: $row->unit_name);
                                        $pendingLocation = $row->location_code
                                            ? $row->location_code .
                                                ($row->warehouse_name ? ' — ' . $row->warehouse_name : '')
                                            : 'Chưa định khu';
                                        $pendingCodeBadge = \App\Support\ZoneType::badge($row->location_id, $row->location_zone_type ?? null, $row->location_color ?? null);
                                    @endphp
                                    <tr data-import-id="{{ $row->id }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="imp-code font-weight-bold"
                                                style="--chip: {{ $pendingCodeBadge['bg'] }}; --chip-text: {{ $pendingCodeBadge['text'] }}">{{ $row->code }}</div>
                                            <span class="badge badge-warning mt-1">Chờ kiểm tra</span>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->material_name ?: '—' }}</div>
                                            <div class="md-sub small text-muted">
                                                NSX:
                                                {{ $row->manufacturer_short_name ?: ($row->manufacturer_name ?: '—') }}
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <span class="imp-amount">{{ $impNum($row->amount) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="md-sub">{{ $row->batch_no ?: '—' }}</td>
                                        <td class="md-sub">{{ $row->invoice_number ?: '—' }}</td>
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
                                            @perm('import_material_update')
                                                <button type="button" class="btn btn-sm btn-success btn-imp-check"
                                                    title="Xác nhận kiểm tra - bổ sung thông tin, định khu vị trí thật"
                                                    data-row="{{ json_encode([
                                                        'id' => $row->id,
                                                        'code' => $row->code,
                                                        'material_name' => $row->material_name,
                                                        'amount_label' => $pendingAmount,
                                                        'imported_date_label' => $impDate($row->imported_date),
                                                        'location_label' => $pendingLocation,
                                                        'batch_no' => $row->batch_no,
                                                        'invoice_number' => $row->invoice_number,
                                                        'invoice_date' => $row->invoice_date,
                                                        'expired_date' => $row->expired_date,
                                                        'location_id' => $row->location_id,
                                                        'purpose' => $row->purpose,
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

            </div>
        </div>
    </div>
</div>
