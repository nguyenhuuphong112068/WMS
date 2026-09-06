{{--
| MỘT DÒNG VẬT TƯ của phiếu đề nghị liên phòng ban.
|
| Biến vào:
| - $idx     : chỉ số của dòng trong items[...]; template JS truyền chuỗi '__i__'
| - $item    : dòng đã lưu (modal điều chỉnh) hoặc null (dòng trống)
| - $onlyRow : true khi đây là dòng duy nhất đang có -> khoá nút xoá
|
| Mã vật tư nằm ở cột đầu, đổ theo data-code của mã đang chọn (xem JS trong
| transferRequestModal). Dòng đã lưu thì in sẵn mã từ server.
--}}
@php
    $rowCategoryId = $item->category_id ?? null;
    $rowCode = $rowCategoryId ? optional($transferCategories->firstWhere('id', $rowCategoryId))->code : null;
@endphp

<tr class="mat-transfer-row">
    <td class="text-center">
        <span class="badge badge-secondary px-2 py-1 mat-transfer-code" style="font-size: 0.82rem;">{{ $rowCode }}</span>
    </td>
    <td>
        <select name="items[{{ $idx }}][category_id]" class="form-control select-mat-transfer-category" required>
            <option value="">-- Chọn vật tư --</option>
            @foreach ($transferCategories as $cat)
                <option value="{{ $cat->id }}" data-code="{{ $cat->code }}" data-unit="{{ $cat->unit_short_name ?: '' }}"
                    {{ $rowCategoryId == $cat->id ? 'selected' : '' }}>
                    {{ $cat->code }} - {{ $cat->material_name }}@if ($cat->technical_specification) ({{ $cat->technical_specification }})@endif
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="text" inputmode="decimal" min="0.0001" name="items[{{ $idx }}][requested_amount]"
            class="form-control text-right js-decimal" placeholder="0.0000"
            value="{{ $item ? (float) $item->requested_amount : '' }}" required>
    </td>
    <td>
        <select name="items[{{ $idx }}][requested_unit]" class="form-control select-mat-transfer-unit">
            <option value="">-- ĐVT --</option>
            @foreach ($units as $u)
                @php $uVal = $u->short_name ?: $u->name; @endphp
                <option value="{{ $uVal }}" {{ $item && $item->requested_unit === $uVal ? 'selected' : '' }}>{{ $uVal }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <textarea name="items[{{ $idx }}][note]" class="form-control auto-resize" rows="1" placeholder="Ghi chú...">{{ $item->note ?? '' }}</textarea>
    </td>
    <td class="text-center align-middle">
        <button type="button" class="btn btn-xs btn-danger btn-remove-mat-transfer-row" {{ $onlyRow ? 'disabled' : '' }}>
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
