{{--
| Khối khai PHÂN LOẠI + THỜI GIAN ĐẶT HÀNG của Danh Mục Vật Tư Công Ty.
| Dùng chung cho modal Thêm mới và modal Cập nhật.
|
| 6 tiêu chí, mỗi tiêu chí chọn đúng một giá trị (radio) và đều bắt buộc. Bộ Phận Mua Hàng
| là tiêu chí thứ 4 nhưng lưu ở cột riêng purchasing_department - xem
| App\Support\MaterialClassification::formGroups(), không khai rải rác trong view.
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
    $groups = MaterialClassification::formGroups($selected, $purchasing);
@endphp

<div class="form-group">
    <label>Phân Loại Vật Tư <span class="text-danger">*</span></label>
    <div class="cat-check-group">
        @foreach ($groups as $group)
            <div class="cat-subgroup">
                <span class="cat-subgroup-title">
                    <i class="{{ $group['icon'] }} mr-1"></i> {{ $group['label'] }}
                </span>

                @foreach ($group['options'] as $value => $name)
                    <label class="cat-check-item {{ $group['value'] === $value ? 'is-checked' : '' }}">
                        <input type="radio" class="cat-check-input" name="{{ $group['name'] }}" value="{{ $value }}"
                            {{ $group['value'] === $value ? 'checked' : '' }} required>
                        <span class="cat-check-name">{{ $name }}</span>
                    </label>
                @endforeach
            </div>

            @if ($bag->has($group['errorKey']))
                <span class="md-error">{{ $bag->first($group['errorKey']) }}</span>
            @endif
        @endforeach
    </div>
</div>

<div class="row">
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
