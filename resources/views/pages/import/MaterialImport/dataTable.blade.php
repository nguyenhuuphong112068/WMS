@include('pages.import.shared.assets')

@php
    $impToday = \Carbon\Carbon::today();
@endphp

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    @perm('import_material_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Nhập vật tư
                        </button>
                    @endperm
                    <p class="hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đang hiệu lực {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} lô vật tư.
                        Mã xuất nhập được cấp tự động khi lưu: VT + mã phòng ban + chuỗi ngẫu nhiên.
                    </p>
                </div>

                @include('pages.shared.barcodeSearch', [
                    'scanTitle' => 'Quét mã QR',
                    'scanTables' => [
                        ['id' => 'mdTable', 'column' => 1, 'pane' => 'impPaneBook', 'label' => 'Sổ nhập vật tư'],
                    ],
                ])

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 45px">STT</th>
                                <th style="width: 165px">Mã Xuất Nhập</th>
                                <th>Vật Tư</th>
                                <th style="width: 150px">Quy Cách / Phân Loại</th>
                                <th class="text-right" style="width: 110px">Số Lượng</th>
                                <th style="width: 170px">Vị Trí Lưu Trữ</th>
                                <th class="text-center" style="width: 95px">Ngày Nhập</th>
                                <th class="text-center" style="width: 110px">Hạn Dùng</th>
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
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="imp-code font-weight-bold">{{ $row->code }}</div>
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
                                        @if ($row->classification_name)
                                            <span class="md-tag">{{ $row->classification_name }}</span>
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
                                    <td class="text-center md-sub" data-order="{{ $row->imported_date }}">
                                        {{ $impDate($row->imported_date) }}</td>
                                    <td class="text-center md-sub {{ $impExpiredClass }}"
                                        data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                        {{ $impDate($row->expired_date) }}
                                    </td>
                                    <td class="text-center">
                                        @if ($rowAttachments->isNotEmpty())
                                            <div class="dropdown">
                                                <button class="btn btn-xs btn-outline-primary dropdown-toggle"
                                                    type="button" data-toggle="dropdown">
                                                    <i class="fas fa-paperclip"></i> ({{ $rowAttachments->count() }})
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right shadow-sm">
                                                    @foreach ($rowAttachments as $att)
                                                        <a class="dropdown-item small py-2" target="_blank"
                                                            href="{{ route($impRoute . 'downloadAttachment', ['id' => $att->id]) }}">
                                                            <i class="fas fa-external-link-alt mr-2 text-primary"></i>
                                                            {{ $att->file_name }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
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
                                                            'imported_date' => $row->imported_date,
                                                            'expired_date' => $row->expired_date,
                                                            'location_id' => $row->location_id,
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

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if ($.fn.DataTable.isDataTable('#mdTable')) {
            $('#mdTable').DataTable().order([6, 'desc']).draw();
        }
    });
</script>
