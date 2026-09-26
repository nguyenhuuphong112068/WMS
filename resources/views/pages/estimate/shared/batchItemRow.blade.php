{{--
| Một dòng mặt hàng trong modal "Thêm ... Dự Trù" dạng bảng của Hoá Chất / Chất Chuẩn
| (pages/estimate/shared/batchItemCreate.blade.php).
|
| Hai loại dòng:
| - source = category : đổ vào từ khung "Chọn từ danh mục phòng", tên hiện cố định
| - source = manual   : dòng tạo bằng nút "Thêm dòng ngoài danh mục" - gõ tên tự do
|
| Biến vào:
| - $index       : số thứ tự dòng; để '' khi dùng làm mẫu <template> (JS tự đặt tên ô)
| - $row         : giá trị sẵn có của dòng (old input khi validate lỗi), mảng rỗng nếu dòng mới
| - $periodCount : số cột tháng cần in sẵn ô số lượng (mẫu JS truyền 0, JS tự chèn)
| - $catLabels   : [category_id => ['name' => ..., 'meta' => ...]] để hiện tên dòng danh mục
| - $emi         : cấu hình màn hình (nameField, itemLabel, groups, factorUnits...)
| - $bag / $units
|
| Tên ô do JS đặt lại theo data-f / thứ tự: items[i][<data-f>] và items[i][amounts][<k>].
--}}
@php
    $row = $row ?? [];
    $periodCount = $periodCount ?? 0;
    $source = ($row['source'] ?? 'manual') === 'category' ? 'category' : 'manual';
    $categoryId = (string) ($row['category_id'] ?? '');
    $label = $catLabels[$categoryId] ?? ['name' => '', 'meta' => ''];
    $nameField = $emi['nameField'];
    $has = fn($field) => $index !== '' && $bag->has("items.$index.$field");
    $err = fn($field) => $has($field) ? 'is-invalid' : '';
    $name = fn($field) => $index !== '' ? "items[$index][$field]" : '';
    // Chỉ Dự Trù Hoá Chất: ô hệ số quy đổi, JS hiện khi hoá chất nhóm 9 / 10 khai khác đơn vị danh mục
    $withFactor = is_array($emi['factorUnits'] ?? null);
@endphp

<tr class="emi-row emi-row-{{ $source }}">
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
                <input type="text" data-f="{{ $nameField }}" name="{{ $name($nameField) }}" maxlength="255"
                    class="form-control form-control-sm emi-h-name {{ $err($nameField) }}"
                    value="{{ $row[$nameField] ?? '' }}"
                    placeholder="Nhập tên {{ $emi['itemLabel'] }} chưa có trong danh mục">
            </div>
        @endif
        <div class="js-max-stock-warn"></div>
        @if ($emi['thresholdUrl'])
            <div class="emi-threshold"></div>
        @endif
    </td>

    @if ($emi['groups'])
        <td>
            <select data-f="group_key" name="{{ $name('group_key') }}"
                class="form-control form-control-sm {{ $err('group_key') }}"
                title="Bắt buộc với chất chuẩn ngoài danh mục">
                <option value="">{{ $source === 'manual' ? '-- Chọn nhóm --' : '-- Không chỉ định --' }}</option>
                @foreach ($emi['groups'] as $groupKey => $group)
                    <option value="{{ $groupKey }}" {{ (string) ($row['group_key'] ?? '') === (string) $groupKey ? 'selected' : '' }}>
                        {{ $group['short'] }} - {{ $group['name'] }}
                    </option>
                @endforeach
            </select>
        </td>
    @endif

    <td>
        <textarea data-f="technical_information" name="{{ $name('technical_information') }}" rows="1" maxlength="1000"
            class="form-control form-control-sm emi-auto {{ $err('technical_information') }}"
            placeholder="{{ $emi['techPlaceholder'] }}">{{ $row['technical_information'] ?? '' }}</textarea>
    </td>

    <td>
        <textarea data-f="purpose" name="{{ $name('purpose') }}" rows="1" maxlength="1000"
            class="form-control form-control-sm emi-auto {{ $err('purpose') }}"
            placeholder="{{ $emi['purposePlaceholder'] }}">{{ $row['purpose'] ?? '' }}</textarea>
    </td>

    <td>
        <input type="date" data-f="expected_delivery_date" name="{{ $name('expected_delivery_date') }}"
            class="form-control form-control-sm {{ $err('expected_delivery_date') }}"
            value="{{ $row['expected_delivery_date'] ?? '' }}">
    </td>

    <td>
        <select data-f="unit_id" name="{{ $name('unit_id') }}" class="form-control form-control-sm emi-unit {{ $err('unit_id') }}">
            <option value="">-- Chọn --</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" {{ (string) ($row['unit_id'] ?? '') === (string) $unit->id ? 'selected' : '' }}
                    data-short="{{ $unit->short_name ?: $unit->name }}" data-group="{{ $unit->unit_group ?? '' }}"
                    data-base="{{ $unit->factor_to_base ?? '' }}">
                    {{ $unit->short_name ?: $unit->name }}
                </option>
            @endforeach
        </select>
        @if ($withFactor)
            <div class="est-factor" style="display:none" title="Hệ số quy đổi về đơn vị danh mục của phòng">
                1 <b class="js-factor-from"></b> =
                <input type="text" inputmode="decimal" data-f="conversion_factor" name="{{ $name('conversion_factor') }}"
                    class="form-control js-decimal js-factor-input {{ $err('conversion_factor') }}"
                    value="{{ $row['conversion_factor'] ?? '' }}" placeholder="?">
                <b class="js-factor-to"></b>
            </div>
        @endif
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
