{{--
    PHÂN LOẠI ĐỊNH KHU - chỉ cấp vị trí. Mỗi phân loại gắn cố định một màu (App\Support\ZoneType),
    chip bên dưới chỉ xem trước màu đó, người dùng không chọn màu riêng.
    Được @include vào cả modal Thêm mới lẫn modal Cập nhật để hai form không lệch nhau.

    Biến truyền vào: $bag (error bag của form đang vẽ), $withZoneType (có ô Phân loại không).
--}}

@if ($withZoneType ?? false)
<div class="form-group">
    <label>Phân Loại Định Khu</label>
    <select name="zone_type" class="form-control sel-zone-type {{ $bag->has('zone_type') ? 'is-invalid' : '' }}">
        <option value="">-- Chưa phân loại --</option>
        @foreach ($zoneClassifications as $value => $label)
            <option value="{{ $value }}" data-color="{{ $zoneTypeColors[$value] }}">{{ $label }}</option>
        @endforeach
    </select>
    <div class="mt-2">
        <span class="zone-color-chip"><i class="zone-color-chip-icon"></i><span class="zone-color-chip-text"></span></span>
    </div>
    @if ($bag->has('zone_type'))
        <span class="zone-error">{{ $bag->first('zone_type') }}</span>
    @endif
</div>
@endif
