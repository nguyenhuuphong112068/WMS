{{--
|--------------------------------------------------------------------------
| TỒN - Ô "HẠN DÙNG" CỦA MỘT ỐNG CHUẨN
|--------------------------------------------------------------------------
| Dùng chung cho tab "Theo mã ống chuẩn", "Hạn dùng dưới N tháng" và "Chưa có
| hạn nội bộ". Nhận: ['row' => $row, 'countdown' => true|false].
|
| - check online -> badge cam "Check online"
| - retest       -> ngày retest + badge xanh "Retest"
| - còn lại      -> ngày hạn dùng
|
| Với ống retest / check online, người có quyền inventory_standard_expiryUpdate
| bấm được vào badge (class .inv-expiry-badge + data-row) để mở modal Cập Nhật
| Hạn Dùng; badge số nhỏ .inv-expiry-count là số lần đã cập nhật.
--}}
@php
    $showCountdown = $countdown ?? true;
    $expCount = $invExpiryUpdateCount[$row->id] ?? 0;
    $expRow = [
        'import_id' => $row->id,
        'code' => $row->code,
        'chem_name' => $row->standard_name,
        'expiry_type' => $row->expiry_type,
        'expired_date' => $row->expired_date,
        'retest_interval_months' => $row->retest_interval_months,
        'potency' => $row->potency,
        'moisture' => $row->moisture,
        'coa_no' => $row->coa_no,
    ];
@endphp

@if ($invIsCheckOnline($row))
    @perm('inventory_standard_expiryUpdate')
        <span class="badge badge-warning inv-expiry-badge" role="button"
            title="Bấm để cập nhật hạn dùng / ghi nhận lần tra cứu trực tuyến"
            data-row="{{ json_encode($expRow) }}">
            <i class="fas fa-globe"></i> Check online
            @if ($expCount > 0)<span class="inv-expiry-count">{{ $expCount }}</span>@endif
        </span>
    @else
        <span class="badge badge-warning"
            title="Hạn dùng chưa xác định từ NSX. Tra cứu trực tuyến khi sử dụng.">
            <i class="fas fa-globe"></i> Check online
        </span>
    @endperm
@elseif ($invIsRetest($row))
    <div class="font-weight-bold">{{ $invDate($row->expired_date) }}</div>
    @perm('inventory_standard_expiryUpdate')
        <span class="badge badge-info inv-expiry-badge" role="button"
            title="Hạn retest do NSX công bố. Bấm để cập nhật sau khi kiểm nghiệm lại."
            data-row="{{ json_encode($expRow) }}">
            Retest
            @if ($expCount > 0)<span class="inv-expiry-count">{{ $expCount }}</span>@endif
        </span>
    @else
        <span class="badge badge-info" title="Hạn retest do NSX công bố">Retest</span>
    @endperm
    @if ($showCountdown && $row->days_to_expiry !== null && $row->remaining > 0)
        <div>
            @if ($row->days_to_expiry < 0)
                <span class="text-danger font-weight-bold">Quá {{ abs($row->days_to_expiry) }} ngày</span>
            @else
                Còn {{ $row->days_to_expiry }} ngày
            @endif
        </div>
    @endif
@else
    {{ $invDate($row->expired_date) }}
    @if ($showCountdown && $row->days_to_expiry !== null && $row->remaining > 0)
        <div>
            @if ($row->days_to_expiry < 0)
                <span class="text-danger font-weight-bold">Quá {{ abs($row->days_to_expiry) }} ngày</span>
            @else
                Còn {{ $row->days_to_expiry }} ngày
            @endif
        </div>
    @endif
@endif
