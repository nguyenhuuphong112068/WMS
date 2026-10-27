{{--
| Một dòng của bảng SỔ NHẬP VẬT TƯ (#mdTable).
|
| Tách riêng để confirmCheck() render lại đúng dòng vừa kiểm tra rồi trả về cho modal Xác
| nhận kiểm tra (gửi ngầm) thay vào bảng, không phải tải lại cả trang.
|
| Biến vào: $row, $stt, $attachments (gom theo material_import_id), $historyCounts,
|           $impNum, $impDate, $impToday, $impRoute
--}}
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
<tr data-import-id="{{ $row->id }}">
    <td class="text-center">{{ $stt }}</td>
    <td>
        <div class="imp-code font-weight-bold"
            style="--chip: {{ $rowCodeBadge['bg'] }}; --chip-text: {{ $rowCodeBadge['text'] }}">{{ $row->code }}</div>
        @if ($row->category_code)
            <div class="md-sub mt-1">
                <span class="md-tag">{{ $row->category_code }}</span>
            </div>
        @endif
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
                {{ \App\Support\ZoneType::tag($row->location_code, $row->location_zone_type ?? null) }}
            </div>
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
        </div>
    </td>
</tr>
