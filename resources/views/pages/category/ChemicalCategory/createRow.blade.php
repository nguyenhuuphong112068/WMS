{{--
| Một dòng của modal "Thêm Danh Mục Hoá Chất Công Ty Mới" (bảng nhiều dòng - xem create.blade.php).
|
| Biến vào: $index ('' ở <template>), $row (old('items')[i]), $bag, $nameLabels / $mfrLabels [id => nhãn]
| để dựng option của giá trị cũ; $storageConditions, $safetyWarnings như modal cũ.
| Ô .ccb-cls dưới tên hiện nhóm NĐ 24/2026 suy được của tên đã chọn (chemNamePicker - cnpClsChips).
--}}
@php
    $row = (array) ($row ?? []);
    $err = fn($field) => $index !== '' && $bag->has('items.' . $index . '.' . $field) ? 'is-invalid' : '';
    $rowHasError = $index !== '' && collect($bag->keys())->contains(fn($key) => str_starts_with($key, 'items.' . $index . '.'));
    $nameId = (string) ($row['chem_names_id'] ?? '');
    $mfrId = (string) ($row['manufacturers_id'] ?? '');
    $warnings = (array) ($row['safety_warning'] ?? []);
@endphp
<tbody class="cbt-item {{ $rowHasError ? 'has-error' : '' }}">
    <tr>
        <td class="cbt-no text-center">{{ $index === '' ? '' : $index + 1 }}</td>
        <td><span class="md-tag cbt-code"></span></td>
        <td>
            <select name="items[{{ $index }}][chem_names_id]"
                data-lookup-url="{{ route('pages.category.lookup.names', ['source' => 'chemical']) }}"
                class="form-control cbt-select cbt-focus ccb-name {{ $err('chem_names_id') }}" required>
                <option value="">-- Chọn tên hoá chất --</option>
                @if ($nameId !== '' && isset($nameLabels[$nameId]))
                    <option value="{{ $nameId }}" selected>{{ $nameLabels[$nameId] }}</option>
                @endif
            </select>
            <div class="ccb-cls"></div>
        </td>
        <td>
            <input type="text" name="items[{{ $index }}][type]" maxlength="30" list="ccbTypeList" data-fill="type"
                class="form-control {{ $err('type') }}" value="{{ $row['type'] ?? '' }}" placeholder="Loại">
        </td>
        <td>
            <select name="items[{{ $index }}][manufacturers_id]" data-fill="manufacturers_id"
                class="form-control cbt-select ccb-mfr {{ $err('manufacturers_id') }}" required>
                <option value="">-- Chọn nhà sản xuất --</option>
                @if ($mfrId !== '' && isset($mfrLabels[$mfrId]))
                    <option value="{{ $mfrId }}" selected>{{ $mfrLabels[$mfrId] }}</option>
                @endif
            </select>
        </td>
        <td>
            <input type="text" inputmode="decimal" name="items[{{ $index }}][density]" data-copy="density"
                class="form-control js-decimal text-right {{ $err('density') }}" value="{{ $row['density'] ?? '' }}"
                placeholder="g/ml">
        </td>
        <td>
            <select name="items[{{ $index }}][storage_condition_id]" data-fill="storage_condition_id"
                class="form-control {{ $err('storage_condition_id') }}">
                <option value="">--</option>
                @foreach ($storageConditions as $option)
                    <option value="{{ $option->id }}"
                        {{ (string) ($row['storage_condition_id'] ?? '') === (string) $option->id ? 'selected' : '' }}>
                        {{ $option->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="items[{{ $index }}][safety_warning][]" multiple data-fill="safety_warning"
                class="form-control ccb-safety {{ $err('safety_warning') }}">
                @foreach ($safetyWarnings as $code => $name)
                    <option value="{{ $code }}" {{ in_array($code, $warnings, true) ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </td>
        <td class="cbt-actions">
            <button type="button" class="btn btn-sm btn-outline-primary btn-cbt-copy"
                title="Nhân bản dòng (giữ loại, nhà sản xuất, tỉ trọng, bảo quản, cảnh báo)">
                <i class="fas fa-clone"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-cbt-remove" title="Bỏ dòng">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
</tbody>
