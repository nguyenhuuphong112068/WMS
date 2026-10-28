{{--
| Khối khai PHÂN LOẠI của Danh Mục Vật Tư Công Ty.
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
|
| THỜI GIAN ĐẶT HÀNG không khai ở đây: người có quyền category_material_lead_time khai sau bằng
| nút riêng ở cột bảng (UpdatesCategoryLeadTime); vật tư Hành Chánh không cần khai.
--}}

@php
    use App\Support\MaterialClassification;

    $selected = $selected ?? [];
    $purchasing = $purchasing ?? null;
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

