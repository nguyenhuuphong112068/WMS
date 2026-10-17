{{--
| Một dòng "số lượng dự trù cho một tháng" trong modal khai mặt hàng.
|
| Biến vào:
| - $units  : danh sách đơn vị tính
| - $index  : số thứ tự dòng, để rỗng khi dùng làm mẫu cho JS (JS đánh số lại khi thêm/bớt)
| - $amount / $unitId / $period : giá trị sẵn có, để trống khi là dòng mới
| - $withFactor / $factor : (tuỳ chọn, chỉ Dự Trù Hoá Chất) ô hệ số quy đổi về đơn vị danh mục
|   của phòng, JS chỉ hiện khi hoá chất nhóm 9 / 10 khai khác đơn vị - xem wmsEstFactor()
--}}
@php
    $index = $index ?? '';
    $amount = $amount ?? '';
    $unitId = $unitId ?? '';
    $period = $period ?? '';
    $withFactor = $withFactor ?? false;
    $factor = $factor ?? '';
@endphp

<div class="est-amount-row">
    <div class="col-amount">
        <input type="text" inputmode="decimal" min="0.0001" name="amounts[{{ $index }}][amount]"
            data-field="amount" class="form-control js-decimal" value="{{ $amount }}" placeholder="Ví dụ: 5">
    </div>

    <div class="col-unit">
        <select name="amounts[{{ $index }}][unit_id]" data-field="unit_id" class="form-control">
            <option value="">-- Đơn vị --</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" {{ (string) $unitId === (string) $unit->id ? 'selected' : '' }}
                    data-short="{{ $unit->short_name ?: $unit->name }}" data-group="{{ $unit->unit_group ?? '' }}"
                    data-base="{{ $unit->factor_to_base ?? '' }}">
                    {{ $unit->short_name ?: $unit->name }} - {{ $unit->name }}
                </option>
            @endforeach
        </select>
        @if ($withFactor)
            <div class="est-factor" style="display:none" title="Hệ số quy đổi về đơn vị danh mục của phòng">
                1 <b class="js-factor-from"></b> =
                <input type="text" inputmode="decimal" name="amounts[{{ $index }}][conversion_factor]"
                    data-field="conversion_factor" class="form-control js-decimal js-factor-input"
                    value="{{ $factor }}" placeholder="?">
                <b class="js-factor-to"></b>
            </div>
        @endif
    </div>

    <div class="col-period">
        <input type="month" name="amounts[{{ $index }}][for_month_year]" data-field="for_month_year"
            class="form-control" value="{{ $period }}">
    </div>

    <div class="col-remove">
        <button type="button" class="btn btn-sm btn-outline-danger btn-est-amount-remove" title="Bớt dòng">
            <i class="fas fa-minus"></i>
        </button>
    </div>
</div>
