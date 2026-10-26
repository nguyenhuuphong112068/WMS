{{--
| Nút [Cách ly] của một ống chuẩn còn tồn - mở #invQuarantineModal (StandardInventory/quarantine).
| Lấy tồn HIỆN TẠI ($quarantineRemaining, không cắt theo kỳ đang xem) vì cách ly trừ vào tồn hôm nay.
| Vào: $row (dòng tồn theo mã ống chuẩn), $compact = true chỉ hiện icon,
| $act = true nút icon vuông .inv-act-btn như cột Thao Tác của Tồn Kho Hoá Chất.
--}}
@perm('export_standard_quarantine')
    @php $iqLimit = round((float) ($quarantineRemaining[$row->id] ?? 0), 4); @endphp
    @if ($iqLimit > 0.00005)
        <button type="button"
            class="{{ !empty($act) ? 'inv-act-btn is-danger' : 'btn btn-sm btn-outline-danger' . (empty($compact) ? ' mb-1' : '') }} btn-inv-quarantine"
            title="Cách ly chất chuẩn hỏng / hết hạn"
            data-row="{{ json_encode([
                'import_id' => $row->id,
                'code' => $row->code,
                'chem_name' => $row->standard_name,
                'batch_no' => $row->batch_no,
                'unit' => $row->unit_short_name ?: $row->unit_name,
                'limit' => $iqLimit,
                'location_code' => $row->location_code,
                'expired_date' => $row->effective_expired_date,
                'days' => $row->days_to_effective_expiry,
            ]) }}">
            @if (!empty($act) || !empty($compact))
                <i class="fas fa-biohazard"></i>
            @else
                <i class="fas fa-biohazard mr-1"></i>Cách ly
            @endif
        </button>
    @endif
@endperm
