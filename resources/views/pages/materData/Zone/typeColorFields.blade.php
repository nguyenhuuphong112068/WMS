{{--
    Hai thuộc tính dùng chung cho cả 5 cấp định khu: PHÂN LOẠI và MÀU HIỂN THỊ.
    Được @include vào cả modal Thêm mới lẫn modal Cập nhật để hai form không lệch nhau.

    Biến truyền vào: $bag (error bag của form đang vẽ).
    Ô màu thật nằm ở input hidden .inp-zone-color-value vì <input type="color"> không
    nhận được giá trị rỗng - cần rỗng để mục bám theo màu mặc định của phân loại.
--}}

<div class="form-group">
    <label>Phân Loại Định Khu</label>
    <select name="zone_type" class="form-control sel-zone-type {{ $bag->has('zone_type') ? 'is-invalid' : '' }}">
        <option value="">-- Chưa phân loại --</option>
        @foreach ($zoneClassifications as $value => $label)
            <option value="{{ $value }}" data-color="{{ $zoneTypeColors[$value] }}">{{ $label }}</option>
        @endforeach
    </select>
    @if ($bag->has('zone_type'))
        <span class="zone-error">{{ $bag->first('zone_type') }}</span>
    @endif
</div>

<div class="form-group">
    <label>Màu Hiển Thị</label>

    <input type="hidden" name="color" class="inp-zone-color-value">

    <div class="zone-color-row">
        <input type="color" class="zone-color-input" value="{{ \App\Support\ZoneType::FALLBACK_COLOR }}"
            title="Chọn màu tự do">
        <span class="zone-color-chip"><i class="zone-color-chip-icon"></i><span class="zone-color-chip-text"></span></span>
        <button type="button" class="btn btn-sm btn-light btn-zone-color-clear">
            <i class="fas fa-rotate-left mr-1"></i> Màu mặc định
        </button>
    </div>

    <div class="zone-swatches">
        @foreach ($zonePalette as $hex)
            <button type="button" class="zone-swatch" data-color="{{ $hex }}"
                style="background: {{ $hex }}" title="{{ $hex }}"></button>
        @endforeach
    </div>

    @if ($bag->has('color'))
        <span class="zone-error">{{ $bag->first('color') }}</span>
    @endif
</div>
