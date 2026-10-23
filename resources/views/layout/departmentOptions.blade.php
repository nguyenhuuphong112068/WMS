{{--
    Các <option> phòng ban gom theo công ty (optgroup) cho mọi ô chọn bộ phận.
    Ô chọn chứa partial này tự bật select2 có ô tìm kiếm (layout/js.blade.php, dò optgroup[data-dept-group]).
    Tham số:
      $departments  danh sách phòng (có id, name, shortName)
      $selected     id đang chọn (1 giá trị hoặc mảng)
--}}
@php
    $doSelected = collect(is_array($selected ?? null) ? $selected : [$selected ?? null])
        ->filter(fn ($v) => $v !== null && $v !== '')
        ->map(fn ($v) => (string) $v)
        ->all();
@endphp
@foreach (\App\Support\DepartmentOptions::groups($departments ?? []) as $doGroup)
    <optgroup label="{{ $doGroup['company'] }}" data-dept-group>
        @foreach ($doGroup['departments'] as $doDept)
            <option value="{{ $doDept->id }}" data-id="{{ $doDept->id }}" data-company="{{ $doGroup['company_id'] }}"
                {{ in_array((string) $doDept->id, $doSelected, true) ? 'selected' : '' }}>
                {{ \App\Support\DepartmentOptions::label($doDept) }}
            </option>
        @endforeach
    </optgroup>
@endforeach
