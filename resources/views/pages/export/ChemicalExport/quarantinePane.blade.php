{{--
| SỬ DỤNG HOÁ CHẤT - TAB "CÁCH LY - LOẠI BỎ - HUỶ"
|
| Quy trình 3 bước - xem ChemicalQuarantineController:
|   1. Cách ly chờ quyết định : bấm [Cách ly] ở Tồn Kho Hoá Chất; hàng dời sang khu Chờ Quyết Định, trừ
|                               tồn ngay; trả về kho thì hoàn tồn.
|   2. Loại bỏ - Chờ huỷ      : hàng dời sang khu Loại Bỏ/Chờ Huỷ, không quay lại kho.
|   3. Huỷ                    : ghi nhận đã huỷ thực tế.
| Không có nhóm "Tất cả": mở tab không chỉ rõ nhóm thì vào "Cách ly - Chờ quyết định".
|
| Dữ liệu vào: $quarantineRows (paginator), $quarantineRange, $quarantineKeyword, $quarantinePerPage,
| $quarantineStatus, $quarantineCounts.
--}}

@php
    $qrCtrl = \App\Http\Controllers\Pages\Export\ChemicalQuarantineController::class;

    $qrStatusMeta = [
        'quarantined' => ['label' => $qrCtrl::STATUSES['quarantined'], 'class' => 'qr-st-quarantined', 'icon' => 'fas fa-lock'],
        'removed' => ['label' => $qrCtrl::STATUSES['removed'], 'class' => 'qr-st-removed', 'icon' => 'fas fa-trash-alt'],
        'destroyed' => ['label' => $qrCtrl::STATUSES['destroyed'], 'class' => 'qr-st-destroyed', 'icon' => 'fas fa-fire'],
        'returned' => ['label' => $qrCtrl::STATUSES['returned'], 'class' => 'qr-st-returned', 'icon' => 'fas fa-undo'],
    ];

    // Link lọc theo trạng thái: giữ tham số của các tab khác, về trang 1 của tab này
    $qrKeep = collect(request()->query())
        ->reject(fn($value, $key) => is_array($value) || in_array($key, ['tab', 'qstatus', 'qr_page'], true))
        ->all();
    $qrStatusUrl = fn($status) => route($expRoute . 'list', array_merge($qrKeep, ['tab' => 'quarantine', 'qstatus' => $status]));

    $qrRemovedTab = $quarantineStatus === 'removed';

    // Bước hiện tại của tiến trình 1-2-3 theo trạng thái
    $qrStep = fn($status) => match ($status) {
        'quarantined' => 1,
        'removed' => 2,
        'destroyed' => 3,
        default => 0,
    };
@endphp

<style>
    /* ---------- Hoá chất hỏng ---------- */
    .qr-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }

    .qr-toolbar .qr-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .qr-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .qr-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md, 8px);
        background: #fff;
        color: var(--primary-dark);
        font-weight: 600;
        transition: all 0.2s;
    }

    .qr-filter:hover {
        text-decoration: none;
        color: var(--primary-dark);
        background: var(--primary-soft);
        transform: translateY(-1px);
    }

    .qr-filter.is-on {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .qr-filter .qr-filter-count {
        min-width: 22px;
        padding: 0 6px;
        border-radius: 999px;
        font-size: 0.78rem;
        text-align: center;
        background: var(--primary);
        color: #fff;
    }

    .qr-filter.is-on .qr-filter-count {
        background: #fff;
        color: var(--primary);
    }

    .qr-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .qr-st-quarantined { background: #FFFBEB; color: #B45309; }
    .qr-st-removed { background: #FEF2F2; color: #DC2626; }
    .qr-st-destroyed { background: #F1F5F9; color: #475569; }
    .qr-st-returned { background: #F0FDF4; color: #16A34A; }

    /* Tiến trình 3 bước */
    .qr-steps {
        display: flex;
        align-items: center;
        margin-top: 6px;
    }

    .qr-steps .qr-dot {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        border: 2px solid var(--primary-lighter);
        color: var(--primary-lighter);
        background: #fff;
    }

    .qr-steps .qr-line {
        flex: 1;
        min-width: 14px;
        height: 2px;
        background: var(--primary-lighter);
    }

    .qr-steps .qr-dot.is-done {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .qr-steps .qr-line.is-done {
        background: var(--primary);
    }

    .qr-steps .qr-dot.is-current {
        border-color: var(--primary);
        color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15);
    }

    .qr-steps.is-returned .qr-dot,
    .qr-steps.is-returned .qr-line {
        border-color: #16A34A;
        background: #fff;
        color: #16A34A;
    }

    .qr-steps.is-returned .qr-line {
        background: #BBF7D0;
    }

    .qr-meta {
        font-size: 0.78rem;
        line-height: 1.45;
    }
</style>

<div class="qr-toolbar">
    <div class="qr-filters">
        @foreach ($qrStatusMeta as $stKey => $meta)
            <a href="{{ $qrStatusUrl($stKey) }}" class="qr-filter {{ $quarantineStatus === $stKey ? 'is-on' : '' }}">
                <i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                @if (($quarantineCounts[$stKey] ?? 0) > 0)
                    <span class="qr-filter-count">{{ $quarantineCounts[$stKey] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="qr-actions">
        @perm('inventory_chemical_view')
            <a href="{{ route('pages.inventory.chemicalInventory.list') }}" class="btn btn-outline-danger"
                title="Bấm [Cách ly] trên từng mã xuất nhập ở Tồn Kho Hoá Chất">
                <i class="fas fa-biohazard mr-1"></i> Cách ly từ Tồn Kho
            </a>
        @endperm
        @if ($qrRemovedTab)
            @perm('export_chemical_quarantine')
                <button type="button" class="btn btn-outline-danger" id="qrDisposeSelected" disabled>
                    <i class="fas fa-fire mr-1"></i> Huỷ mục đã chọn <span class="qr-selected-count"></span>
                </button>
            @endperm
        @endif
    </div>
</div>

@include('pages.shared.rangeFilter', [
    'rfRoute' => $expRoute . 'list',
    'rfTab' => 'quarantine',
    'rfPrefix' => 'qr_',
    'rfInBar' => true,
    'rfRange' => $quarantineRange,
    'rfPerPage' => $quarantinePerPage,
    'rfKeyword' => $quarantineKeyword,
    'rfDateLabel' => 'Ngày cách ly',
    'rfPlaceholder' => 'Mã phiếu, mã xuất nhập, số lô, tên hoá chất, lý do...',
])

<div class="table-responsive">
    <table id="expQuarantineTable" class="table table-bordered table-hover w-100 md-table" data-server-paged>
        <thead>
            <tr>
                @if ($qrRemovedTab)
                    <th class="text-center" style="width:36px" data-orderable="false">
                        <input type="checkbox" id="qrCheckAll" title="Chọn tất cả phiếu chờ huỷ trên trang">
                    </th>
                @endif
                <th class="text-center" style="width:45px">STT</th>
                <th style="width:190px">Phiếu Cách Ly</th>
                <th>Hoá Chất</th>
                <th class="text-right" style="width:100px">Số Lượng</th>
                <th style="width:190px">Định Khu</th>
                <th style="width:220px">Lý Do Cách Ly</th>
                <th style="width:220px">Quyết Định</th>
                <th style="width:200px">Huỷ</th>
                <th class="text-center" style="width:110px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($quarantineRows as $row)
                @php
                    $meta = $qrStatusMeta[$row->app_status] ?? ['label' => $row->app_status, 'class' => '', 'icon' => 'fas fa-circle'];
                    $step = $qrStep($row->app_status);
                    $qrData = [
                        'id' => $row->id,
                        'code' => $row->code,
                        'import_code' => $row->import_code,
                        'chem_name' => $row->chem_name,
                        'amount' => (float) $row->amount,
                        'amount_text' => $expNum($row->amount) . ' ' . $row->unit_short_name,
                        'unit' => $row->unit_short_name,
                        'reason' => $row->reason,
                        'pending_location_id' => $row->pending_location_id,
                        'pending_location_code' => $row->pending_location_code,
                        'location_id' => $row->lot_location_id,
                        'location_code' => $row->location_code,
                        'location_zone_type' => $row->location_zone_type,
                    ];
                @endphp
                <tr data-classification="{{ $expCls($row->category_id) }}">
                    @if ($qrRemovedTab)
                        <td class="text-center">
                            <input type="checkbox" class="qr-check" value="{{ $row->id }}" data-row="{{ json_encode($qrData) }}">
                        </td>
                    @endif
                    <td class="text-center">{{ $quarantineRows->firstItem() + $loop->index }}</td>
                    <td>
                        <span class="exp-code font-weight-bold">{{ $row->code }}</span>
                        <div class="mt-1"><span class="qr-status {{ $meta['class'] }}"><i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}</span></div>
                        <div class="qr-steps {{ $row->app_status === 'returned' ? 'is-returned' : '' }}"
                            title="{{ $row->app_status === 'returned' ? 'Đã trả về kho ở bước 1' : '1. Cách ly → 2. Loại bỏ → 3. Huỷ' }}">
                            @if ($row->app_status === 'returned')
                                <span class="qr-dot"><i class="fas fa-undo"></i></span>
                                <span class="qr-line"></span>
                                <span class="qr-dot">2</span>
                                <span class="qr-line"></span>
                                <span class="qr-dot">3</span>
                            @else
                                @foreach ([1, 2, 3] as $n)
                                    @if ($n > 1)
                                        <span class="qr-line {{ $step >= $n ? 'is-done' : '' }}"></span>
                                    @endif
                                    <span class="qr-dot {{ $step > $n || $step === 3 ? 'is-done' : ($step === $n ? 'is-current' : '') }}">{{ $n }}</span>
                                @endforeach
                            @endif
                        </div>
                        <div class="qr-meta md-sub text-muted mt-1">{{ $row->created_by }} · {{ $expDateTime($row->created_at) }}</div>
                    </td>
                    <td>
                        <div class="font-weight-bold">
                            {{ $row->chem_name ?: '—' }}
                            @if ($expIsSpecial($row->category_id))
                                <span class="badge-special-control ml-1" title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">
                                    <i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt
                                </span>
                            @endif
                        </div>
                        @if ($row->category_code)
                            <span class="md-tag">{{ $row->category_code }}</span>
                        @endif
                        <div class="md-sub small">Mã XN: <span class="exp-code">{{ $row->import_code }}</span>{{ $row->batch_no ? ' · Lô ' . $row->batch_no : '' }}</div>
                        @if ($row->internal_expired_date || $row->expired_date)
                            <div class="md-sub small text-muted">HSD: {{ $expDate($row->internal_expired_date ?: $row->expired_date) }}{{ $row->internal_expired_date ? ' (nội bộ)' : '' }}</div>
                        @endif
                    </td>
                    <td class="text-right">{{ $expNum($row->amount) }} <span class="md-sub">{{ $row->unit_short_name }}</span></td>
                    {{-- Chỉ một định khu - nơi hàng của phiếu đang thực sự nằm theo trạng thái --}}
                    <td>
                        @if ($row->app_status === 'quarantined')
                            {{ \App\Support\ZoneType::tag($row->pending_location_code, $row->pending_location_zone_type) }}
                        @elseif (in_array($row->app_status, ['removed', 'destroyed'], true))
                            {{ \App\Support\ZoneType::tag($row->rejected_location_code, $row->rejected_location_zone_type) }}
                        @else
                            {{ \App\Support\ZoneType::tag($row->returned_location_code ?: $row->location_code, $row->returned_location_code ? $row->returned_location_zone_type : ($row->location_zone_type ?? null)) }}
                        @endif
                    </td>
                    <td class="md-sub"><span class="text-danger">{{ $row->reason }}</span></td>
                    <td class="qr-meta">
                        @if ($row->decided_at)
                            <div class="font-weight-bold {{ $row->app_status === 'returned' ? 'text-success' : 'text-danger' }}">
                                {{ $row->app_status === 'returned' ? 'Trả về kho' : 'Loại bỏ' }}
                            </div>
                            <div>{{ $row->decision_note }}</div>
                            <div class="text-muted">{{ $row->decided_by }} · {{ $expDateTime($row->decided_at) }}</div>
                        @else
                            <span class="text-muted">Chờ quyết định</span>
                        @endif
                    </td>
                    <td class="qr-meta">
                        @if ($row->destroyed_at)
                            <div class="font-weight-bold">{{ $row->destroy_method }}</div>
                            @if ($row->destroy_note) <div>{{ $row->destroy_note }}</div> @endif
                            <div class="text-muted">{{ $row->destroyed_by }} · {{ $expDateTime($row->destroyed_at) }}</div>
                        @elseif ($row->app_status === 'removed')
                            <span class="text-muted">Chờ huỷ</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="md-actions">
                            @if ($row->app_status === 'quarantined')
                                @perm('export_chemical_quarantine_decide')
                                    <button type="button" class="btn btn-sm btn-primary btn-qr-decide" title="Quyết định loại bỏ / trả về kho"
                                        data-row="{{ json_encode($qrData) }}">
                                        <i class="fas fa-gavel"></i>
                                    </button>
                                @endperm
                                @perm('export_chemical_quarantine')
                                    <button type="button" class="btn btn-sm btn-warning btn-qr-edit" title="Sửa phiếu cách ly"
                                        data-row="{{ json_encode($qrData) }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endperm
                            @elseif ($row->app_status === 'removed')
                                @perm('export_chemical_quarantine')
                                    <button type="button" class="btn btn-sm btn-danger btn-qr-dispose" title="Ghi nhận huỷ"
                                        data-row="{{ json_encode($qrData) }}">
                                        <i class="fas fa-fire"></i>
                                    </button>
                                @endperm
                            @else
                                <span class="md-sub text-muted">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $qrRemovedTab ? 10 : 9 }}" class="text-center text-muted">Không có phiếu nào ở nhóm này.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('pages.shared.paginator', [
    'pgItems' => $quarantineRows,
    'pgTab' => 'quarantine',
    'pgUnit' => 'phiếu cách ly',
])
