{{--
| Một dòng của modal "Thêm Danh Mục VPP Công Ty Mới" (bảng nhiều dòng - xem create.blade.php).
|
| Biến vào:
| - $index  : số thứ tự dòng (0, 1...) khi dựng lại sau lỗi validate, '' khi dựng <template>
| - $row    : giá trị cũ của dòng (old('items')[i]), mảng rỗng cho dòng mới
| - $bag    : bag lỗi createErrors
| - $groups : VppClassification::formGroups() - một cột Nhóm Dự Trù
| - $units  : đơn vị tính cho ô chọn (dùng chung toàn công ty)
| - $nameLabels / $mfrLabels : [id => nhãn] để dựng option của giá trị cũ
--}}
@php
    $row = (array) ($row ?? []);
    $err = fn($field) => $index !== '' && $bag->has('items.' . $index . '.' . $field) ? 'is-invalid' : '';
    $rowHasError = $index !== '' && collect($bag->keys())->contains(fn($key) => str_starts_with($key, 'items.' . $index . '.'));
    $nameId = (string) ($row['material_names_id'] ?? '');
    $mfrId = (string) ($row['manufacturers_id'] ?? '');
@endphp
<tbody class="cbt-item {{ $rowHasError ? 'has-error' : '' }}">
    <tr>
        <td class="cbt-no text-center">{{ $index === '' ? '' : $index + 1 }}</td>
        <td><span class="md-tag cbt-code"></span></td>
        <td>
            <select name="items[{{ $index }}][material_names_id]"
                data-lookup-url="{{ route('pages.category.lookup.names', ['source' => 'material']) }}"
                class="form-control cbt-select cbt-focus {{ $err('material_names_id') }}" required>
                <option value="">-- Chọn tên VPP --</option>
                @if ($nameId !== '' && isset($nameLabels[$nameId]))
                    <option value="{{ $nameId }}" selected>{{ $nameLabels[$nameId] }}</option>
                @endif
            </select>
        </td>
        <td>
            <select name="items[{{ $index }}][manufacturers_id]" data-fill="manufacturers_id"
                class="form-control cbt-select mcb-mfr {{ $err('manufacturers_id') }}" required>
                <option value="">-- Chọn nhà sản xuất --</option>
                @if ($mfrId !== '' && isset($mfrLabels[$mfrId]))
                    <option value="{{ $mfrId }}" selected>{{ $mfrLabels[$mfrId] }}</option>
                @endif
            </select>
        </td>
        <td>
            <select name="items[{{ $index }}][unit_id]" data-fill="unit_id"
                class="form-control cbt-select {{ $err('unit_id') }}" required>
                <option value="">-- Đơn vị --</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" {{ (string) ($row['unit_id'] ?? '') === (string) $unit->id ? 'selected' : '' }}>
                        {{ $unit->short_name }} - {{ $unit->name }}
                    </option>
                @endforeach
            </select>
        </td>
        @foreach ($groups as $group)
            @php
                $path = explode('.', $group['errorKey']);
                $value = (string) data_get($row, $group['errorKey'], '');
            @endphp
            <td>
                <select name="items[{{ $index }}][{{ implode('][', $path) }}]" data-fill="{{ $group['errorKey'] }}"
                    class="form-control {{ $err($group['errorKey']) }}" title="{{ $group['label'] }}" required>
                    <option value="">--</option>
                    @foreach ($group['options'] as $optionValue => $optionName)
                        <option value="{{ $optionValue }}" {{ $value === (string) $optionValue ? 'selected' : '' }}>
                            {{ $optionName }}
                        </option>
                    @endforeach
                </select>
            </td>
        @endforeach
        <td class="cbt-actions">
            <button type="button" class="btn btn-sm btn-outline-primary btn-cbt-copy"
                title="Nhân bản dòng (giữ nhà sản xuất, đơn vị, nhóm dự trù)">
                <i class="fas fa-clone"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-cbt-remove" title="Bỏ dòng">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
</tbody>
