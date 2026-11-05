{{--
| Một dòng của modal "Thêm Danh Mục Chất Chuẩn Công Ty Mới" (bảng nhiều dòng - xem create.blade.php).
|
| Biến vào: $index ('' ở <template>), $row (old('items')[i]), $bag, $nameLabels / $mfrLabels [id => nhãn]
| để dựng option của giá trị cũ; $storageConditions, $groups (config standard.groups).
| Phân Nhóm Chuẩn chọn đúng 1 nhóm, vẫn gửi dạng mảng groups[] như modal Cập nhật.
--}}
@php
    $row = (array) ($row ?? []);
    $err = fn($field) => $index !== '' && $bag->has('items.' . $index . '.' . $field) ? 'is-invalid' : '';
    $rowHasError = $index !== '' && collect($bag->keys())->contains(fn($key) => str_starts_with($key, 'items.' . $index . '.'));
    $nameId = (string) ($row['chem_names_id'] ?? '');
    $mfrId = (string) ($row['manufacturers_id'] ?? '');
    $group = (string) (((array) ($row['groups'] ?? []))[0] ?? '');
@endphp
<tbody class="cbt-item {{ $rowHasError ? 'has-error' : '' }}">
    <tr>
        <td class="cbt-no text-center">{{ $index === '' ? '' : $index + 1 }}</td>
        <td><span class="md-tag cbt-code"></span></td>
        <td>
            <select name="items[{{ $index }}][chem_names_id]"
                data-lookup-url="{{ route('pages.category.lookup.names', ['source' => 'standard']) }}"
                class="form-control cbt-select cbt-focus scb-name {{ $err('chem_names_id') }}" required>
                <option value="">-- Chọn tên chuẩn --</option>
                @if ($nameId !== '' && isset($nameLabels[$nameId]))
                    <option value="{{ $nameId }}" selected>{{ $nameLabels[$nameId] }}</option>
                @endif
            </select>
        </td>
        <td>
            <input type="text" name="items[{{ $index }}][cas_no]" maxlength="50"
                class="form-control scb-cas {{ $err('cas_no') }}" value="{{ $row['cas_no'] ?? '' }}"
                placeholder="Theo tên" title="Điền sẵn theo tên đã chọn, sửa lại được">
        </td>
        <td>
            <select name="items[{{ $index }}][manufacturers_id]" data-fill="manufacturers_id"
                class="form-control cbt-select scb-mfr {{ $err('manufacturers_id') }}" required>
                <option value="">-- Chọn nguồn gốc / NSX --</option>
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
            <select name="items[{{ $index }}][groups][]" data-fill="groups"
                class="form-control {{ $err('groups') }}" required>
                <option value="">--</option>
                @foreach ($groups as $code => $info)
                    <option value="{{ $code }}" {{ $group === (string) $code ? 'selected' : '' }}
                        title="Mã trong mã ống chuẩn: {{ $info['code'] }}">
                        {{ $info['no'] }}. {{ $info['name'] }} ({{ $info['short'] }})
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" name="items[{{ $index }}][note]" maxlength="500" data-copy="note"
                class="form-control {{ $err('note') }}" value="{{ $row['note'] ?? '' }}" placeholder="Ghi chú">
        </td>
        <td class="cbt-actions">
            <button type="button" class="btn btn-sm btn-outline-primary btn-cbt-copy"
                title="Nhân bản dòng (giữ nguồn gốc, tỷ trọng, bảo quản, phân nhóm, ghi chú)">
                <i class="fas fa-clone"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-cbt-remove" title="Bỏ dòng">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
</tbody>
