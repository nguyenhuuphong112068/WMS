{{--
| Phân loại hoạt chất - các nhóm ĐƠN CHẤT (1, 3, 4, 5, 6, 7, 9 theo Nghị định 24/2026/NĐ-CP,
| 11 theo Luật Đầu tư 2025 số 143/2025/QH15). Nhóm 9 (Phụ lục IV Bảng A) bắt buộc kèm ngưỡng tồn trữ.
| Kèm ô "Thuộc mục gộp": chất cụ thể (HgCl₂...) trỏ về dòng khai theo nhóm chất của nghị định
| ("Thủy ngân và các hợp chất của thủy ngân") để tồn của nó cộng vào ngưỡng của mục gộp đó.
| Biến vào: $bag, $groupLabels, $singleSubstanceGroups, $oldGroups, $collectives
--}}
@php
    $oldParentId = (int) old('parent_id');
    $oldEquiv = old('equiv_factor');
@endphp

<div class="form-group">
    <label>Thuộc Mục Gộp Của Nghị Định</label>
    <select name="parent_id" class="form-control {{ $bag->has('parent_id') ? 'is-invalid' : '' }}" data-parent-select>
        <option value="">— Không thuộc mục gộp nào (chất đứng riêng) —</option>
        @foreach ($collectives as $c)
            <option value="{{ $c->id }}" {{ $oldParentId === (int) $c->id ? 'selected' : '' }}>
                {{ $c->name }}{{ $c->threshold_kg ? ' — ngưỡng ' . rtrim(rtrim(number_format((float) $c->threshold_kg, 3, '.', ','), '0'), '.') . ' kg' : '' }}
            </option>
        @endforeach
    </select>
    @if ($bag->has('parent_id'))
        <span class="md-error">{{ $bag->first('parent_id') }}</span>
    @endif
    <small class="text-muted d-block mt-1">
        Nghị định có dòng khai theo <b>nhóm chất</b> ("Thủy ngân và các hợp chất của thủy ngân",
        "Các hợp chất xyanua"...). Chất cụ thể có số CAS riêng phải trỏ về mục gộp thì tồn kho mới
        được <b>cộng chung</b> khi đối chiếu ngưỡng, và chất đó cũng <b>thừa hưởng</b> phân loại
        của mục gộp - không cần tick lại nhóm bên dưới.
    </small>
</div>

<div class="form-group" data-parent-only style="{{ $oldParentId ? '' : 'display: none;' }}">
    <label>Hệ Số Quy Đổi Về Mục Gộp</label>
    <input type="text" inputmode="decimal" name="equiv_factor"
        class="form-control js-decimal {{ $bag->has('equiv_factor') ? 'is-invalid' : '' }}"
        value="{{ $oldEquiv === null || $oldEquiv === '' ? '1' : $oldEquiv }}" placeholder="1">
    @if ($bag->has('equiv_factor'))
        <span class="md-error">{{ $bag->first('equiv_factor') }}</span>
    @endif
    <small class="text-muted d-block mt-1">
        Để <b>1</b> (mặc định): tính trọn khối lượng hợp chất như nhập kho vào ngưỡng của mục gộp -
        1 kg HgCl₂ tính là 1 kg. Chỉ đổi hệ số khi cần quy về phần nguyên tố (ví dụ 0,738 nếu chỉ
        tính khối lượng Hg trong HgCl₂).
    </small>
</div>

<div class="form-group">
    <label class="d-flex align-items-center" style="gap: 8px">
        Phân Loại Nghị định số 24/2026/NĐ-CP
        <span class="badge badge-danger" data-group-count style="display: none"></span>
    </label>

    <div class="ai-group-box {{ $bag->has('groups') || $bag->has('groups.0') ? 'is-invalid' : '' }}">
        @foreach ($singleSubstanceGroups as $g)
            @php $checked = in_array((int) $g, $oldGroups, true); @endphp
            <label class="ai-group-item {{ $checked ? 'is-checked' : '' }}">
                <input type="checkbox" class="ai-group-input" name="groups[]" value="{{ $g }}"
                    {{ $g == 9 ? 'data-g9-toggle' : '' }} {{ $checked ? 'checked' : '' }}>
                <span class="ai-group-code">{{ \App\Support\ChemicalClassification::shortLabel($g) }}</span>
                <span class="ai-group-text">{{ $groupLabels[$g] ?? \App\Support\ChemicalClassification::shortLabel($g) }}</span>
            </label>
        @endforeach
    </div>

    @if ($bag->has('groups') || $bag->has('groups.0'))
        <span class="md-error">{{ $bag->first('groups') ?: $bag->first('groups.0') }}</span>
    @endif
    <small class="text-muted d-block mt-1">
        Một hoạt chất có thể thuộc nhiều nhóm cùng lúc. Các nhóm hỗn hợp (2, 8, 10) khai ở màn
        <b>Tên Hoá Chất</b>.
    </small>
</div>

<div class="form-group" data-g9-only style="{{ in_array(9, $oldGroups, true) ? '' : 'display: none;' }}">
    <label>Ngưỡng Khối Lượng Tồn Trữ Lớn Nhất Tại Một Thời Điểm (kg) <span class="text-danger">*</span></label>
    <input type="text" inputmode="decimal" name="threshold_kg" min="0.001"
        class="form-control js-decimal {{ $bag->has('threshold_kg') ? 'is-invalid' : '' }}"
        value="{{ old('threshold_kg') }}" placeholder="Ví dụ: 50000" {{ in_array(9, $oldGroups, true) ? 'required' : '' }}>
    @if ($bag->has('threshold_kg'))
        <span class="md-error">{{ $bag->first('threshold_kg') }}</span>
    @endif
    <small class="text-muted d-block mt-1">
        Bắt buộc nhập ngưỡng tồn trữ đối với hoạt chất thuộc <b>nhóm 9</b> (Phụ lục IV Bảng A).
    </small>
</div>

@once
    <style>
        .ai-group-box {
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            max-height: 240px;
            overflow-y: auto;
            padding: 6px;
        }

        .ai-group-box.is-invalid {
            border-color: #DC2626;
        }

        .ai-group-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 0;
            padding: 7px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 400;
        }

        .ai-group-item + .ai-group-item {
            border-top: 1px solid var(--primary-soft);
        }

        .ai-group-item:hover,
        .ai-group-item.is-checked {
            background: var(--primary-soft);
        }

        .ai-group-input {
            margin-top: 3px;
        }

        .ai-group-code {
            font-weight: 700;
            color: var(--primary-dark);
            white-space: nowrap;
        }

        .ai-group-text {
            font-size: 0.85rem;
            color: var(--text-main);
            line-height: 1.4;
        }
    </style>
@endonce
