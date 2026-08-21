{{--
| Hai ô khai báo quy đổi của Đơn Vị Tính, dùng chung cho modal thêm mới và cập nhật.
| Biến vào:
| - $bag         : error bag của modal đang dùng (createErrors / updateErrors)
| - $groups      : config('unit.groups')
| - $suggestions : config('unit.suggestions')
--}}

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Nhóm Đơn Vị <span class="text-danger">*</span></label>
            <select name="unit_group" class="form-control unit-group {{ $bag->has('unit_group') ? 'is-invalid' : '' }}"
                required>
                @foreach ($groups as $key => $group)
                    <option value="{{ $key }}" data-base="{{ $group['base'] }}"
                        {{ old('unit_group', 'count') === $key ? 'selected' : '' }}>
                        {{ $group['label'] }}
                    </option>
                @endforeach
            </select>
            @if ($bag->has('unit_group'))
                <span class="md-error">{{ $bag->first('unit_group') }}</span>
            @endif
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>Hệ Số Quy Đổi <span class="text-danger">*</span></label>
            <input type="number" name="factor_to_base" step="0.000001" min="0.000001"
                class="form-control {{ $bag->has('factor_to_base') ? 'is-invalid' : '' }}"
                value="{{ old('factor_to_base', 1) }}" required>
            @if ($bag->has('factor_to_base'))
                <span class="md-error">{{ $bag->first('factor_to_base') }}</span>
            @endif
        </div>
    </div>
</div>

<div class="md-hint unit-hint" data-suggestions="{{ json_encode($suggestions) }}">
    <i class="fas fa-balance-scale mr-1"></i>
    <span class="unit-hint-text"></span>
</div>
