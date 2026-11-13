{{--
| Một dòng của modal "Khai ... Cho Phòng" dạng bảng (shared/deptBatch.blade.php) - dùng chung cho
| Vật Tư / Hoá Chất / Chất Chuẩn.
|
| Mã của dòng là cố định (chọn từ ô tìm nhanh / bảng chọn), nên chỉ có ô ẩn category_id + nhãn hiển thị.
| category_text / category_meta / category_shelf gửi kèm chỉ để dựng lại dòng khi báo lỗi validate,
| server không lưu.
|
| Dòng phụ bên dưới: khối QUY ĐỔI ĐƠN VỊ của riêng dòng (JS ở shared/unitConversion - tbody mang class
| cuc-scope để khối chỉ đọc ô đơn vị .<prefix>-unit của chính dòng, tên ô hệ số theo data-name-prefix)
| và, với hoá chất, khung cảnh báo TƯƠNG KỴ của định khu (DepartmentChemical/compatibility).
|
| Biến vào: $index ('' ở <template>), $row (old('items')[i]), $bag, $cfg (cấu hình của modal - xem deptBatch)
--}}
@php
    $row = (array) ($row ?? []);
    $err = fn($field) => $index !== '' && $bag->has('items.' . $index . '.' . $field) ? 'is-invalid' : '';
    $rowErrors = $index === ''
        ? collect()
        : collect($bag->keys())->filter(fn($key) => str_starts_with($key, 'items.' . $index . '.'));
    $convErrors = $rowErrors->filter(fn($key) => str_starts_with($key, 'items.' . $index . '.conversions'));
    $locationId = (string) ($row['default_location_id'] ?? '');
    $shelfDefault = (string) ($row['category_shelf'] ?? '');
@endphp
<tbody class="cbt-item cuc-scope {{ $rowErrors->isNotEmpty() ? 'has-error' : '' }}">
    <tr>
        <td class="cbt-no text-center">{{ $index === '' ? '' : $index + 1 }}</td>
        <td>
            <input type="hidden" name="items[{{ $index }}][category_id]" class="cbt-category"
                value="{{ $row['category_id'] ?? '' }}">
            <input type="hidden" name="items[{{ $index }}][category_text]" class="cbt-text"
                value="{{ $row['category_text'] ?? '' }}">
            <input type="hidden" name="items[{{ $index }}][category_meta]" class="cbt-meta-input"
                value="{{ $row['category_meta'] ?? '' }}">
            <input type="hidden" name="items[{{ $index }}][category_shelf]" class="cbt-shelf-default"
                value="{{ $shelfDefault }}">
            <div class="cbt-mat-name">{{ $row['category_text'] ?? '' }}</div>
            <div class="cbt-mat-meta">{{ $row['category_meta'] ?? '' }}</div>
        </td>
        @if ($cfg['classifications'] !== null)
            <td>
                <select name="items[{{ $index }}][classification_id]" data-fill="classification_id"
                    class="form-control {{ $err('classification_id') }}">
                    <option value="">-- Chưa phân loại --</option>
                    @foreach ($cfg['classifications'] as $classification)
                        <option value="{{ $classification->id }}"
                            {{ (string) ($row['classification_id'] ?? '') === (string) $classification->id ? 'selected' : '' }}>
                            {{ $classification->name }}
                        </option>
                    @endforeach
                </select>
            </td>
        @endif
        @foreach ($cfg['deptCriteria'] ?? [] as $criterion)
            <td>
                <select name="items[{{ $index }}][{{ $criterion['name'] }}]" data-fill="{{ $criterion['name'] }}"
                    class="form-control {{ $err($criterion['name']) }}" required>
                    <option value="">-- Chọn --</option>
                    @foreach ($criterion['options'] as $value => $label)
                        <option value="{{ $value }}"
                            {{ (string) ($row[$criterion['name']] ?? '') === (string) $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </td>
        @endforeach
        <td>
            <select name="items[{{ $index }}][unit_id]" data-fill="unit_id"
                class="form-control cbt-select {{ $cfg['prefix'] }}-unit {{ $err('unit_id') }}" required>
                <option value="">-- Chọn --</option>
                @foreach ($cfg['units'] as $unit)
                    <option value="{{ $unit->id }}" data-short="{{ $unit->short_name }}"
                        data-group="{{ $unit->unit_group ?? '' }}"
                        {{ (string) ($row['unit_id'] ?? '') === (string) $unit->id ? 'selected' : '' }}>
                        {{ $unit->short_name }} - {{ $unit->name }}
                    </option>
                @endforeach
            </select>
            @if ($cfg['packWeight'])
                {{-- Chỉ hiện khi đơn vị thuộc nhóm đếm / bao bì: cần để quy tồn ra kg (Ngưỡng Tồn Trữ PL IV) --}}
                <div class="input-group input-group-sm cbt-pack mt-1" style="display: none"
                    title="Khối lượng hoá chất (kg) trong 1 đơn vị đếm - để trống nếu chưa cần đối chiếu Ngưỡng Tồn Trữ PL IV">
                    <div class="input-group-prepend"><span class="input-group-text">1 <b class="cbt-unit-short mx-1">đv</b> =</span></div>
                    <input type="text" inputmode="decimal" name="items[{{ $index }}][pack_weight_kg]"
                        class="form-control js-decimal {{ $err('pack_weight_kg') }}" value="{{ $row['pack_weight_kg'] ?? '' }}">
                    <div class="input-group-append"><span class="input-group-text">kg</span></div>
                </div>
            @endif
        </td>
        @if ($cfg['shelf'])
            <td>
                <input type="number" min="1" max="1200" step="1" name="items[{{ $index }}][shelf_life_months]"
                    class="form-control text-right cbt-shelf {{ $err('shelf_life_months') }}"
                    value="{{ $row['shelf_life_months'] ?? '' }}"
                    placeholder="{{ $shelfDefault !== '' ? 'DM: ' . $shelfDefault : 'Theo DM' }}"
                    title="Để trống thì lấy hạn dùng mặc định của danh mục">
            </td>
        @endif
        <td>
            <input type="text" inputmode="decimal" name="items[{{ $index }}][min_stock]"
                class="form-control js-decimal text-right {{ $err('min_stock') }}"
                value="{{ $row['min_stock'] ?? '' }}" placeholder="—">
        </td>
        <td>
            <input type="text" inputmode="decimal" name="items[{{ $index }}][max_stock]"
                class="form-control js-decimal text-right {{ $err('max_stock') }}"
                value="{{ $row['max_stock'] ?? '' }}" placeholder="—">
        </td>
        <td>
            <select name="items[{{ $index }}][default_location_id]" data-fill="default_location_id" data-zone-filter
                data-lookup-url="{{ $cfg['locationLookupUrl'] }}"
                class="form-control cbt-select cbt-location {{ $err('default_location_id') }}">
                <option value="">-- Chưa định khu --</option>
                @if ($locationId !== '' && isset($cfg['locationLabels'][$locationId]))
                    <option value="{{ $locationId }}" data-zone="{{ $cfg['locationLabels'][$locationId]['zone'] }}" selected>
                        {{ $cfg['locationLabels'][$locationId]['text'] }}
                    </option>
                @endif
            </select>
        </td>
        <td>
            <input type="text" name="items[{{ $index }}][note]" maxlength="500"
                class="form-control {{ $err('note') }}" value="{{ $row['note'] ?? '' }}" placeholder="Ghi chú">
        </td>
        <td class="cbt-actions">
            <button type="button" class="btn btn-sm btn-outline-danger btn-cbt-remove" title="Bỏ dòng">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
    <tr class="cbt-sub">
        <td></td>
        <td colspan="{{ $cfg['columns'] - 1 }}">
            <div class="cuc-box cbt-cuc" data-prefix="{{ $cfg['prefix'] }}" data-name-prefix="items[{{ $index }}]"
                data-old="{{ json_encode($index !== '' ? (array) ($row['conversions'] ?? []) : []) }}"
                data-old-dirs="{{ json_encode($index !== '' ? (array) ($row['conversion_dirs'] ?? []) : []) }}"
                style="display: none">
                <input type="hidden" class="cuc-category" value="{{ $row['category_id'] ?? '' }}">
                <div class="cuc-head">
                    <i class="fas fa-exchange-alt mr-1"></i> Quy Đổi Đơn Vị <span class="text-danger">*</span>
                </div>
                <div class="cuc-list"></div>
                @foreach ($convErrors as $key)
                    <span class="md-error d-block">{{ $bag->first($key) }}</span>
                @endforeach
            </div>
            @if ($cfg['compat'])
                <div class="dc-compat cbt-compat" hidden></div>
            @endif
        </td>
    </tr>
</tbody>
