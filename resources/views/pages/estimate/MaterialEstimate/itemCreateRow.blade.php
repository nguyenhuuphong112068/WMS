{{--
| Một dòng vật tư trong modal "Thêm Vật Tư Dự Trù" (dạng bảng, thêm nhiều vật tư một lần).
|
| Hai loại dòng:
| - source = category : vật tư đổ vào từ khung "Chọn từ danh mục phòng", tên hiện cố định
| - source = manual   : dòng tạo bằng nút "Thêm dòng" - vật tư NGOÀI danh mục, gõ tên tự do
|
| Biến vào:
| - $index       : số thứ tự dòng; để '' khi dùng làm mẫu <template> (JS tự đặt tên ô)
| - $row         : giá trị sẵn có của dòng (old input khi validate lỗi), mảng rỗng nếu dòng mới
| - $periodCount : số cột tháng cần in sẵn ô số lượng (mẫu JS truyền 0, JS tự chèn)
| - $catLabels   : [category_id => ['name' => ..., 'meta' => ...]] để hiện tên dòng danh mục
| - $bag / $units
|
| Tên ô do JS đặt lại theo data-f / thứ tự: items[i][<data-f>] và items[i][amounts][<k>].
--}}
@php
    $row = $row ?? [];
    $periodCount = $periodCount ?? 0;
    $source = ($row['source'] ?? 'manual') === 'category' ? 'category' : 'manual';
    $categoryId = (string) ($row['category_id'] ?? '');
    $label = $catLabels[$categoryId] ?? ['name' => '', 'meta' => '', 'lead' => ''];
    $has = fn($field) => $index !== '' && $bag->has("items.$index.$field");
    $err = fn($field) => $has($field) ? 'is-invalid' : '';
    $name = fn($field) => $index !== '' ? "items[$index][$field]" : '';
@endphp

<tr class="emi-row emi-row-{{ $source }}" data-lead-days="{{ $source === 'category' ? $label['lead'] ?? '' : '' }}">
    <td class="emi-no text-center"></td>

    <td class="emi-material">
        <input type="hidden" data-f="source" class="emi-h-source" name="{{ $name('source') }}" value="{{ $source }}">

        @if ($source === 'category')
            <input type="hidden" data-f="category_id" class="emi-h-category" name="{{ $name('category_id') }}" value="{{ $categoryId }}">
            <div class="emi-mat-label {{ $has('category_id') ? 'is-invalid' : '' }}">
                <span class="emi-badge emi-badge-cat">Danh mục</span>
                <span class="emi-mat-name">{{ $label['name'] }}</span>
                <small class="emi-mat-meta">{{ $label['meta'] }}</small>
            </div>
        @else
            <input type="hidden" data-f="category_id" class="emi-h-category" name="{{ $name('category_id') }}" value="">
            <div class="emi-manual-wrap">
                <span class="emi-badge emi-badge-manual">Ngoài danh mục</span>
                <input type="text" data-f="material_name" name="{{ $name('material_name') }}" maxlength="255"
                    class="form-control form-control-sm emi-h-name {{ $has('material_name') ? 'is-invalid' : '' }}"
                    value="{{ $row['material_name'] ?? '' }}" placeholder="Nhập tên vật tư chưa có trong danh mục">
            </div>
        @endif
        <div class="js-max-stock-warn"></div>
    </td>

    <td>
        <input type="text" data-f="part_number" name="{{ $name('part_number') }}" maxlength="100"
            class="form-control form-control-sm {{ $err('part_number') }}"
            value="{{ $row['part_number'] ?? '' }}" placeholder="Mã P/N">
    </td>

    <td>
        <textarea data-f="technical_information" name="{{ $name('technical_information') }}" rows="1" maxlength="1000"
            class="form-control form-control-sm emi-auto {{ $err('technical_information') }}"
            placeholder="Quy cách, kích thước, tiêu chuẩn">{{ $row['technical_information'] ?? '' }}</textarea>
    </td>

    <td>
        <textarea data-f="purpose" name="{{ $name('purpose') }}" rows="1" maxlength="1000"
            class="form-control form-control-sm emi-auto {{ $err('purpose') }}"
            placeholder="Ví dụ: Lọc mẫu HPLC">{{ $row['purpose'] ?? '' }}</textarea>
    </td>

    <td>
        <input type="date" data-f="expected_delivery_date" name="{{ $name('expected_delivery_date') }}"
            class="form-control form-control-sm {{ $err('expected_delivery_date') }}"
            value="{{ $row['expected_delivery_date'] ?? '' }}">
        <span class="emi-lead-warn" style="display:none"></span>
    </td>

    <td>
        <select data-f="unit_id" name="{{ $name('unit_id') }}" class="form-control form-control-sm emi-unit {{ $err('unit_id') }}">
            <option value="">-- Chọn --</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" {{ (string) ($row['unit_id'] ?? '') === (string) $unit->id ? 'selected' : '' }}>
                    {{ $unit->short_name ?: $unit->name }}
                </option>
            @endforeach
        </select>
    </td>

    <td class="emi-file-cell">
        <label class="btn btn-sm btn-outline-secondary emi-file-btn mb-0 {{ $has('files') ? 'border-danger text-danger' : '' }}"
            title="Đính kèm file cho vật tư này (báo giá, ảnh, catalogue...)">
            <i class="fas fa-paperclip"></i> <span class="emi-file-count">Chọn</span>
            <input type="file" class="emi-files d-none" multiple
                accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.bmp,.webp,.zip,.rar,.7z,.msg,.eml">
        </label>
        <div class="emi-file-names"></div>
    </td>

    @for ($k = 0; $k < $periodCount; $k++)
        <td class="emi-amount-cell">
            <input type="text" inputmode="decimal" name="items[{{ $index }}][amounts][{{ $k }}]"
                class="form-control form-control-sm js-decimal text-right emi-amount {{ $has('amounts') || $has("amounts.$k") ? 'is-invalid' : '' }}"
                value="{{ $row['amounts'][$k] ?? '' }}" placeholder="0">
        </td>
    @endfor

    <td class="emi-act text-center">
        <button type="button" class="btn btn-sm btn-outline-primary btn-emi-copy" title="Nhân bản dòng">
            <i class="fas fa-clone"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-emi-remove" title="Xoá dòng">
            <i class="fas fa-trash-alt"></i>
        </button>
    </td>
</tr>
