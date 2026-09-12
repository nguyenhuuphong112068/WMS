{{--
| Khối khai PHÂN LOẠI + BỘ PHẬN MUA HÀNG + THỜI GIAN ĐẶT HÀNG của Danh Mục Vật Tư Công Ty.
| Dùng chung cho modal Thêm mới và modal Cập nhật.
|
| Mỗi tiêu chí chọn đúng một giá trị (radio), một vật tư mang nhiều tiêu chí cùng lúc.
| Bộ tiêu chí khai ở App\Support\MaterialClassification, không khai rải rác trong view.
|
| Biến vào:
| - $bag        : bag lỗi của form đang dựng
| - $selected   : [tiêu chí => giá trị] đang chọn (modal Cập nhật đổ lại bằng JS)
| - $purchasing : mã bộ phận mua hàng đang chọn
| - $leadTime   : số ngày đặt hàng đang khai
--}}

@php
    use App\Support\MaterialClassification;

    $selected = $selected ?? [];
    $purchasing = $purchasing ?? null;
    $leadTime = $leadTime ?? null;
@endphp

<div class="form-group">
    <label>Phân Loại Vật Tư</label>
    <div class="cat-check-group">
        @foreach (MaterialClassification::CRITERIA as $key => $criterion)
            <div class="cat-subgroup">
                <span class="cat-subgroup-title">
                    <i class="{{ $criterion['icon'] }} mr-1"></i> {{ $criterion['label'] }}
                </span>

                <label class="cat-check-item {{ ($selected[$key] ?? '') === '' ? 'is-checked' : '' }}">
                    <input type="radio" class="cat-check-input" name="classification[{{ $key }}]" value=""
                        {{ ($selected[$key] ?? '') === '' ? 'checked' : '' }}>
                    <span class="cat-check-name md-empty">Chưa xác định</span>
                </label>

                @foreach ($criterion['options'] as $value => $name)
                    <label class="cat-check-item {{ ($selected[$key] ?? '') === $value ? 'is-checked' : '' }}">
                        <input type="radio" class="cat-check-input" name="classification[{{ $key }}]"
                            value="{{ $value }}" {{ ($selected[$key] ?? '') === $value ? 'checked' : '' }}>
                        <span class="cat-check-name">{{ $name }}</span>
                    </label>
                @endforeach
            </div>

            @if ($bag->has('classification.' . $key))
                <span class="md-error">{{ $bag->first('classification.' . $key) }}</span>
            @endif
        @endforeach
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Bộ Phận Mua Hàng</label>
            <select name="purchasing_department"
                class="form-control cat-select {{ $bag->has('purchasing_department') ? 'is-invalid' : '' }}">
                <option value="">-- Chưa khai --</option>
                @foreach (MaterialClassification::PURCHASING_DEPARTMENTS as $value => $name)
                    <option value="{{ $value }}" {{ $purchasing === $value ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            @if ($bag->has('purchasing_department'))
                <span class="md-error">{{ $bag->first('purchasing_department') }}</span>
            @endif
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>Thời Gian Đặt Hàng</label>
            <div class="input-group">
                <input type="text" inputmode="numeric" name="lead_time_days"
                    class="form-control {{ $bag->has('lead_time_days') ? 'is-invalid' : '' }}"
                    value="{{ $leadTime }}" placeholder="Ví dụ: 30">
                <div class="input-group-append">
                    <span class="input-group-text">ngày</span>
                </div>
            </div>
            @if ($bag->has('lead_time_days'))
                <span class="md-error">{{ $bag->first('lead_time_days') }}</span>
            @endif
            <small class="md-sub">Số ngày từ lúc đặt hàng tới lúc hàng về công ty.</small>
        </div>
    </div>
</div>
