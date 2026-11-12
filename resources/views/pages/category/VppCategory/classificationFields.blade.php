{{--
| Khối khai NHÓM DỰ TRÙ của Danh Mục VPP Công Ty (modal Cập nhật).
|
| Một tiêu chí duy nhất, chọn đúng một trong 5 nhóm (radio, bắt buộc) - xem
| App\Support\VppClassification. Bộ phận mua hàng cố định Hành Chánh, không chọn trên form.
|
| Biến vào:
| - $bag        : bag lỗi của form đang dựng
| - $selected   : [tiêu chí => giá trị] đang chọn (modal Cập nhật đổ lại bằng JS)
| - $purchasing : giữ để cùng chữ ký với Vật Tư (không dùng)
--}}

@php
    use App\Support\VppClassification;

    $selected = $selected ?? [];
    $purchasing = $purchasing ?? null;
    $groups = VppClassification::formGroups($selected, $purchasing);
@endphp

<div class="form-group">
    <label>Phân Loại VPP <span class="text-danger">*</span></label>
    <div class="cat-check-group">
        @foreach ($groups as $group)
            <div class="cat-subgroup">
                <span class="cat-subgroup-title">
                    <i class="{{ $group['icon'] }} mr-1"></i> {{ $group['label'] }}
                </span>

                @foreach ($group['options'] as $value => $name)
                    <label class="cat-check-item {{ $group['value'] === (string) $value ? 'is-checked' : '' }}">
                        <input type="radio" class="cat-check-input" name="{{ $group['name'] }}" value="{{ $value }}"
                            {{ $group['value'] === (string) $value ? 'checked' : '' }} required>
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

