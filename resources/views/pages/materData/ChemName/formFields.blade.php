{{--
| Phần dùng chung cho modal Thêm mới / Cập nhật Tên Hoá Chất:
|   - chọn NHIỀU hoạt chất (Phụ lục IV NĐ 24/2026/NĐ-CP)
|   - tick nhóm nguy hại Bảng B (chỉ có hiệu lực khi có hoạt chất Bảng A)
| Biến vào: $bag, $activeIngredients, $hazardCategories, $hazardGroups, $oldAiIds, $oldHazardIds
--}}
<div class="form-group">
    <label>Hoạt Chất (Phụ lục IV NĐ 24/2026/NĐ-CP)</label>
    <select name="active_ingredients_ids[]" class="form-control chem-ai-select" multiple
        data-placeholder="Chọn một hay nhiều hoạt chất có trong hỗn hợp">
        @foreach ($activeIngredients as $ai)
            @php
                $aiLabel = $ai->name;
                if ($ai->cas_no) {
                    $aiLabel .= ' — CAS ' . $ai->cas_no;
                }
                if ($ai->is_table_a) {
                    $aiLabel .= ' — PL IV Bảng A';
                    if ($ai->threshold_kg !== null) {
                        $aiLabel .= ' (ngưỡng ' . rtrim(rtrim(number_format((float) $ai->threshold_kg, 3, '.', ','), '0'), '.') . ' kg)';
                    }
                }
            @endphp
            <option value="{{ $ai->id }}" data-table-a="{{ $ai->is_table_a ? 1 : 0 }}"
                {{ in_array((int) $ai->id, $oldAiIds, true) ? 'selected' : '' }}>{{ $aiLabel }}</option>
        @endforeach
    </select>
    @if ($bag->has('active_ingredients_ids') || $bag->has('active_ingredients_ids.*'))
        <span class="md-error">{{ $bag->first('active_ingredients_ids') ?: $bag->first('active_ingredients_ids.*') }}</span>
    @endif
    <div class="md-hint mt-2">
        <i class="fas fa-atom mr-1"></i>
        Một hỗn hợp thường gồm nhiều chất. Tên hoạt chất, số CAS, công thức hoá học lấy theo các hoạt chất được chọn.
    </div>
</div>

<div class="form-group" data-hazard-section>
    <label class="d-flex align-items-center" style="gap: 8px">
        Phân Loại Bảng B (Phụ lục IV NĐ 24/2026/NĐ-CP)
        <span class="badge badge-danger" data-hazard-count style="display: none"></span>
    </label>

    <div class="chem-hazard-lock" data-hazard-lock>
        <i class="fas fa-lock mr-1"></i>
        Chỉ tick được khi hỗn hợp có <b>từ 2 hoạt chất trở lên</b> ở ô trên, trong đó có ít nhất một hoạt chất <b>thuộc Bảng A</b>.
    </div>

    <div class="chem-hazard-box" data-hazard-box>
        @foreach ($hazardGroups as $groupCode => $groupName)
            @php $rows = collect($hazardCategories)->where('hazard_group', $groupCode); @endphp
            @if ($rows->isNotEmpty())
                <div class="chem-hazard-group">
                    <div class="chem-hazard-group-title">{{ $groupCode }} — {{ $groupName }}</div>
                    @foreach ($rows as $h)
                        @php $checked = in_array((int) $h->id, $oldHazardIds, true); @endphp
                        <label class="chem-hazard-item {{ $checked ? 'is-checked' : '' }}">
                            <input type="checkbox" class="chem-hazard-input" name="hazard_category_ids[]"
                                value="{{ $h->id }}" {{ $checked ? 'checked' : '' }}>
                            <span class="chem-hazard-code">{{ $h->hazard_group }}.{{ $h->ordinal }}</span>
                            <span class="chem-hazard-text">
                                {{ \Illuminate\Support\Str::of($h->name)->replace("\n", ' ')->limit(140) }}
                                <span class="chem-hazard-thr">— ngưỡng {{ rtrim(rtrim(number_format((float) $h->threshold_kg, 3, '.', ','), '0'), '.') }} kg{{ $h->threshold_basis === 'net' ? ' (net)' : '' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>

    @if ($bag->has('hazard_category_ids') || $bag->has('hazard_category_ids.*'))
        <span class="md-error">{{ $bag->first('hazard_category_ids') ?: $bag->first('hazard_category_ids.*') }}</span>
    @endif
    <small class="text-muted d-block mt-1">
        Ngưỡng của tên hoá chất này = <b>ngưỡng thấp nhất</b> trong các nhóm đã tick. Đối chiếu với tổng tồn trữ
        thô toàn công ty (không nhân % hàm lượng).
    </small>
</div>

@once
    <style>
        .chem-hazard-lock {
            background: #FEF3C7;
            border: 1px dashed #FCD34D;
            color: #B45309;
            border-radius: var(--border-radius-md);
            padding: 8px 12px;
            font-size: 0.83rem;
            margin-bottom: 8px;
        }

        .chem-hazard-box {
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            max-height: 260px;
            overflow-y: auto;
            padding: 6px;
        }

        [data-hazard-section].is-locked .chem-hazard-box {
            opacity: 0.5;
            pointer-events: none;
        }

        [data-hazard-section]:not(.is-locked) .chem-hazard-lock {
            display: none;
        }

        .chem-hazard-group + .chem-hazard-group {
            margin-top: 6px;
            border-top: 1px solid var(--primary-soft);
            padding-top: 6px;
        }

        .chem-hazard-group-title {
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 4px 6px;
        }

        .chem-hazard-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 0;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 400;
        }

        .chem-hazard-item:hover {
            background: var(--primary-soft);
        }

        .chem-hazard-item.is-checked {
            background: var(--primary-soft);
        }

        .chem-hazard-input {
            margin-top: 3px;
        }

        .chem-hazard-code {
            font-weight: 700;
            color: var(--primary-dark);
            white-space: nowrap;
        }

        .chem-hazard-text {
            font-size: 0.85rem;
            color: var(--text-main);
            line-height: 1.4;
        }

        .chem-hazard-thr {
            color: #64748b;
            font-size: 0.8rem;
        }
    </style>
@endonce
