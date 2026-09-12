@include('pages.import.shared.assets')

@php
    $impToday = \Carbon\Carbon::today();
@endphp

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                <div class="imp-tabs">
                    <button type="button" class="imp-tab is-active" data-pane="impPaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ nhập vật tư
                    </button>
                    <button type="button" class="imp-tab" data-pane="impPaneCheck">
                        <i class="fas fa-clipboard-check mr-1"></i> Chờ kiểm tra
                        @if ($pendingCount > 0)
                            <span class="imp-tab-count">{{ $pendingCount }}</span>
                        @endif
                    </button>
                </div>

                {{-- ============ SỔ NHẬP VẬT TƯ ============ --}}
                <div class="imp-pane is-active" id="impPaneBook">

                <div class="md-toolbar">
                    @perm('import_material_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Nhập vật tư
                        </button>
                    @endperm
                </div>

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
                                    $lowStock =
                                        $row->min_stock !== null && (float) $row->amount <= (float) $row->min_stock;
                                    $rowCodeBadge = \App\Support\ZoneType::badge($row->location_id, $row->location_zone_type ?? null, $row->location_color ?? null);
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $datas->firstItem() + $loop->index }}</td>
                                    <td>
                                        <div class="imp-code font-weight-bold"
                                            style="--chip: {{ $rowCodeBadge['bg'] }}; --chip-text: {{ $rowCodeBadge['text'] }}">{{ $row->code }}</div>
                                        @if ($row->category_code)
                                            <div class="md-sub mt-1">
                                                <span class="md-tag">{{ $row->category_code }}</span>
                                            </div>
                                        @endif
                                        @unless ($row->status_id)
                                            <span class="badge badge-secondary mt-1">Đã khoá</span>
                                        @endunless
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $row->material_name ?: '—' }}</div>
                                        <div class="md-sub small text-muted">
                                            NSX: {{ $row->manufacturer_short_name ?: ($row->manufacturer_name ?: '—') }}
                                        </div>
                                    </td>
                                    <td class="md-sub">
                                        <div>{{ $row->technical_specification ?: '—' }}</div>
                                        @php $rowClassification = \App\Support\MaterialClassification::summary($row->classification); @endphp
                                        @if ($rowClassification !== '')
                                            <span class="md-tag">{{ $rowClassification }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right" data-order="{{ $row->amount }}">
                                        <span class="imp-amount">{{ $impNum($row->amount) }}</span>
                                        <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        @if ($lowStock)
                                            <div><span class="badge badge-warning" title="Dưới ngưỡng tồn tối thiểu">Sắp
                                                    hết</span></div>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $row->batch_no ?: '—' }}</td>
                                    <td class="md-sub">{{ $row->invoice_number ?: '—' }}</td>
                                    <td class="text-center md-sub" data-order="{{ $row->invoice_date ?: '' }}">
                                        {{ $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="md-sub">
                                        @if ($row->location_code)
                                            <div class="font-weight-bold">
                                                <span class="md-tag">{{ $row->location_code }}</span>
                                            </div>
                                            <div>{{ $row->warehouse_name ?: '—' }} / {{ $row->shelf_name ?: '—' }} /
                                                {{ $row->column_name ?: '—' }} / {{ $row->tier_name ?: '—' }}</div>
                                        @else
                                            <span class="imp-no-location">Chưa xếp vị trí</span>
                                        @endif
                                    </td>
                                    <td class="text-center md-sub" data-order="{{ $row->imported_date }}">
                                        {{ $impDate($row->imported_date) }}</td>
                                    <td class="text-center md-sub {{ $impExpiredClass }}"
                                        data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                        {{ $impDate($row->expired_date) }}
                                    </td>
                                    <td class="md-sub">{{ $row->purpose ?: '—' }}</td>
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
                                            'uploadPerm' => 'import_material_attachment_upload',
                                            'parentId' => $row->id,
                                            'statusPerm' => 'import_material_attachment_status',
                                            'code' => $row->code,
                                            'name' => $row->material_name,
                                            'typeLabel' => 'Vật tư',
                                        ])
                                    </td>
                                    <td>
                                        <div class="md-actions">
                                            @php $impAdjust = (int) ($historyCounts[$row->id] ?? 0); @endphp
                                            <span class="imp-btn-wrap">
                                                @perm('import_material_update')
                                                    <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                        title="Điều chỉnh"
                                                        data-row="{{ json_encode([
                                                            'id' => $row->id,
                                                            'code' => $row->code,
                                                            'category_id' => $row->category_id,
                                                            'amount' => $row->amount,
                                                            'batch_no' => $row->batch_no,
                                                            'invoice_number' => $row->invoice_number,
                                                            'invoice_date' => $row->invoice_date,
                                                            'imported_date' => $row->imported_date,
                                                            'expired_date' => $row->expired_date,
                                                            'location_id' => $row->location_id,
                                                            'purpose' => $row->purpose,
                                                            'note' => $row->note,
                                                            'attachments' => $rowAttachments->map(fn($a) => ['id' => $a->id, 'file_name' => $a->file_name])->toArray(),
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endperm
                                                @if ($impAdjust > 0)
                                                    <button type="button" class="imp-count-badge btn-imp-history"
                                                        title="Xem {{ $impAdjust }} lần điều chỉnh"
                                                        data-url="{{ route($impRoute . 'history', ['id' => $row->id]) }}"
                                                        data-title="{{ $row->code }} - {{ $row->material_name }}">{{ $impAdjust }}</button>
                                                @endif
                                            </span>

                                            <a class="btn btn-sm btn-outline-secondary" target="_blank"
                                                title="In nhãn dán lô vật tư (mã QR) - chọn được số lượng nhãn cần in"
                                                @perm('import_material_label')
                                                    href="{{ route($impRoute . 'label', ['id' => $row->id]) }}">
                                                    <i class="fas fa-qrcode"></i>
                                                </a>
                                                @endperm

                                            @perm('import_material_delete')
                                                <form class="form-md-confirm d-inline"
                                                    action="{{ route($impRoute . 'deActive') }}" method="POST"
                                                    data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $impLabel }}?"
                                                    data-text="Mã xuất nhập &quot;{{ $row->code }}&quot; {{ $row->status_id == 1 ? 'sẽ không còn được tính vào tồn kho.' : 'sẽ được tính vào tồn kho trở lại.' }}"
                                                    data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                                    <button type="submit"
                                                        class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                        title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                        <i
                                                            class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
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

                @include('pages.shared.paginator', ['pgItems' => $datas, 'pgUnit' => 'phiếu nhập'])

                </div>
                {{-- ============ CHỜ KIỂM TRA ============ --}}
                <div class="imp-pane" id="impPaneCheck">
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
                                    <tr>
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
                                                <span class="md-tag">{{ $row->location_code }}</span>
                                                <div>{{ $row->warehouse_name ?: '—' }}</div>
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

            </div>
        </div>
    </div>
</div>
