{{--
| Phần dùng chung cho modal Thêm mới / Cập nhật Tên Hoá Chất:
|   - chọn NHIỀU hoạt chất + khai % khối lượng từng thành phần (dùng cho quy tắc nhóm 8)
|   - tick "Hỗn hợp SX-KD có điều kiện (Phụ lục II nhóm 2)"  => nhóm 2
|   - tick nhóm nguy hại Bảng B (chỉ có hiệu lực khi hỗn hợp có thành phần nhóm 9) => nhóm 10
| Biến vào: $bag, $activeIngredients, $hazardCategories, $hazardGroups, $groupLabels,
|           $oldAiIds, $oldHazardIds, $oldPercents, $oldConditional
--}}
<div class="form-group">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-1" style="gap: 8px">
        <label class="mb-0">Hoạt Chất Thành Phần (Nghị định 24/2026/NĐ-CP)</label>
        <button type="button" class="btn btn-sm btn-outline-primary chem-ai-picker-open">
            <i class="fas fa-table mr-1"></i> Chọn từ dữ liệu gốc
        </button>
    </div>
    <select name="active_ingredients_ids[]" class="form-control chem-ai-select" multiple
        data-placeholder="Chọn một hay nhiều hoạt chất có trong hỗn hợp">
        @foreach ($activeIngredients as $ai)
            @php
                $aiLabel = $ai->name;
                if ($ai->cas_no) {
                    $aiLabel .= ' — CAS ' . $ai->cas_no;
                }
                if (! empty($ai->groups)) {
                    $aiLabel .= ' — Nhóm ' . implode(', ', $ai->groups);
                }
            @endphp
            <option value="{{ $ai->id }}" data-name="{{ $ai->name }}"
                data-g9="{{ in_array(9, $ai->groups ?? [], true) ? 1 : 0 }}"
                data-g1="{{ in_array(1, $ai->groups ?? [], true) ? 1 : 0 }}"
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

<div class="form-group" data-percent-section style="{{ $oldAiIds ? '' : 'display: none;' }}">
    <label>Tỉ Lệ % Khối Lượng Từng Thành Phần</label>
    <div class="chem-percent-box" data-percent-rows
        data-seed='@json((object) $oldPercents)'></div>
    @if ($bag->has('content_percent.*'))
        <span class="md-error">{{ $bag->first('content_percent.*') }}</span>
    @endif
    <small class="text-muted d-block mt-1">
        Dùng để tự xét <b>nhóm 8</b> (Hỗn hợp chất cần kiểm soát đặc biệt): có thành phần nhóm 3/4/6/7
        tỉ lệ &gt; 1%, hoặc thành phần nhóm 5 tỉ lệ &gt; 5%. Bỏ trống = coi như 0% (không tự đánh nhóm 8).
    </small>
</div>

<div class="form-group">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="{{ $chemCondId ?? 'chemConditional' }}"
            name="is_conditional_mixture" value="1" data-conditional-toggle
            {{ $oldConditional ? 'checked' : '' }}>
        <label class="custom-control-label" for="{{ $chemCondId ?? 'chemConditional' }}">
            <b>Hỗn hợp chất sản xuất, kinh doanh có điều kiện</b> (Phụ lục II nhóm 2) — nhóm 2
        </label>
    </div>
    <small class="text-muted d-block mt-1" data-conditional-hint style="display: none">
        <i class="fas fa-lightbulb mr-1"></i>
        Hỗn hợp này có thành phần thuộc nhóm 1 (Phụ lục II) — cân nhắc đánh dấu nhóm 2.
    </small>
</div>

<div class="form-group" data-hazard-section>
    <label class="d-flex align-items-center" style="gap: 8px">
        Phân Loại Nhóm Nguy Hại Bảng B (Phụ lục IV NĐ 24/2026/NĐ-CP) — nhóm 10
        <span class="badge badge-danger" data-hazard-count style="display: none"></span>
    </label>

    <div class="chem-hazard-lock" data-hazard-lock>
        <i class="fas fa-lock mr-1"></i>
        Chỉ tick được khi hỗn hợp có <b>từ 2 hoạt chất trở lên</b> ở ô trên, trong đó có ít nhất một thành phần <b>thuộc nhóm 9</b> (Phụ lục IV Bảng A).
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

        .chem-percent-box {
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            padding: 6px;
        }

        .chem-percent-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 6px;
        }

        .chem-percent-row + .chem-percent-row {
            border-top: 1px solid var(--primary-soft);
        }

        .chem-percent-name {
            flex: 1 1 auto;
            font-size: 0.86rem;
            color: var(--text-main);
        }

        .chem-percent-row input {
            width: 110px;
            flex: 0 0 auto;
        }

        .chem-percent-empty {
            color: #94a3b8;
            font-size: 0.83rem;
            padding: 6px;
        }
    </style>
@endonce

{{--
| Modal chọn nhanh hoạt chất từ dữ liệu gốc "Tên Hoạt Chất".
| Cùng nguồn dữ liệu với ô select ở trên ($activeIngredients) nên không cần gọi API.
| Dùng chung cho cả modal Thêm mới và Cập nhật - nút .chem-ai-picker-open cho biết
| ô .chem-ai-select nào là đích. Bọc @once để include hai lần chỉ in ra một bản.
--}}
@once
    @php
        // H₂SO₄ -> H2SO4 để gõ không dấu chỉ số vẫn tìm ra
        $fxPlain = fn ($v) => strtr((string) $v, [
            '₀' => '0', '₁' => '1', '₂' => '2', '₃' => '3', '₄' => '4',
            '₅' => '5', '₆' => '6', '₇' => '7', '₈' => '8', '₉' => '9',
            '⁰' => '0', '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4',
            '⁵' => '5', '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9', '·' => '.',
        ]);

        $pickerGroups = collect($activeIngredients)
            ->flatMap(fn ($ai) => $ai->groups ?? [])
            ->unique()->sort()->values();
    @endphp

    <div class="modal fade md-modal" id="chemAiPickerModal" tabindex="-1" role="dialog" aria-hidden="true"
        style="z-index: 1075;">
        <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 92vw" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-atom"></i> Dữ Liệu Gốc — Tên Hoạt Chất
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="chem-aip-filters">
                        <div class="input-group input-group-sm chem-aip-search">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" id="chemAiPickerSearch" class="form-control"
                                placeholder="Tìm theo tên hoạt chất, số CAS, công thức hoá học...">
                        </div>

                        <select id="chemAiPickerGroup" class="custom-select custom-select-sm chem-aip-group">
                            <option value="">Tất cả phân loại NĐ 24/2026</option>
                            @foreach ($pickerGroups as $g)
                                <option value="{{ $g }}" title="{{ $groupLabels[$g] ?? ('Nhóm ' . $g) }}">
                                    Nhóm {{ $g }}
                                </option>
                            @endforeach
                        </select>

                        <div class="custom-control custom-checkbox chem-aip-selonly">
                            <input type="checkbox" class="custom-control-input" id="chemAiPickerSelectedOnly">
                            <label class="custom-control-label" for="chemAiPickerSelectedOnly">Chỉ hiện đã chọn</label>
                        </div>

                        <span class="chem-aip-visible ml-auto">
                            <i class="fas fa-list-ul mr-1"></i><b data-pick-visible>0</b> hoạt chất
                        </span>
                    </div>

                    <div class="chem-aip-tablebox">
                        <table class="table table-sm table-hover mb-0" id="chemAiPickerTable">
                            <thead>
                                <tr>
                                    <th style="width: 42px" class="text-center">
                                        <input type="checkbox" id="chemAiPickerCheckAll" title="Chọn tất cả dòng đang hiện">
                                    </th>
                                    <th style="width: 46px" class="text-center">STT</th>
                                    <th>Tên Hoạt Chất</th>
                                    <th style="width: 140px">Số CAS</th>
                                    <th style="width: 140px">Công Thức</th>
                                    <th style="width: 200px">Phân Loại NĐ 24/2026</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activeIngredients as $ai)
                                    @php
                                        $aiGroups = $ai->groups ?? [];
                                        $search = trim($ai->name . ' ' . $ai->cas_no . ' ' . $ai->chemical_formula
                                            . ' ' . $fxPlain($ai->chemical_formula)
                                            . ' ' . collect($aiGroups)->map(fn ($g) => 'Nhóm ' . $g)->implode(' '));
                                    @endphp
                                    <tr class="chem-ai-pick-row" data-id="{{ $ai->id }}" data-name="{{ $ai->name }}"
                                        data-groups="{{ implode(',', $aiGroups) }}" data-search="{{ $search }}">
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="chem-ai-pick-check" value="{{ $ai->id }}">
                                        </td>
                                        <td class="text-center align-middle md-sub">{{ $loop->iteration }}</td>
                                        <td class="align-middle">
                                            <span class="font-weight-bold">{{ $ai->name }}</span>
                                            @if ($ai->code)
                                                <span class="md-sub ml-1">{{ $ai->code }}</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if ($ai->cas_no)
                                                <span class="md-tag">{{ $ai->cas_no }}</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if ($ai->chemical_formula)
                                                <span class="md-formula">{{ $ai->chemical_formula }}</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="align-middle" data-groups="{{ implode(',', $aiGroups) }}">
                                            @forelse ($aiGroups as $g)
                                                <span class="badge {{ \App\Support\ChemicalClassification::badgeClass($g) }} mr-1 mb-1"
                                                    title="{{ $groupLabels[$g] ?? ('Nhóm ' . $g) }}">Nhóm {{ $g }}</span>
                                            @empty
                                                <span class="md-empty">Không thuộc</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Chưa có hoạt chất nào được duyệt trong <b>Dữ Liệu Gốc → Tên Hoạt Chất</b>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <span class="chem-aip-picked">
                        <i class="fas fa-check-circle mr-1"></i> Đã chọn: <b data-pick-selected>0</b> hoạt chất
                    </span>
                    <div>
                        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" id="chemAiPickerConfirm">
                            <i class="fas fa-check mr-1"></i> Xác nhận lựa chọn
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .chem-aip-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 12px;
            margin-bottom: 12px;
        }

        .chem-aip-filters .chem-aip-search {
            flex: 1 1 320px;
            max-width: 420px;
        }

        .chem-aip-filters .chem-aip-group {
            flex: 0 1 260px;
        }

        .chem-aip-filters .chem-aip-selonly {
            margin: 0;
        }

        .chem-aip-visible {
            font-size: 0.83rem;
            color: #64748b;
        }

        .chem-aip-tablebox {
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            max-height: 56vh;
            overflow-y: auto;
        }

        #chemAiPickerTable thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--primary-soft);
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-lighter);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        #chemAiPickerTable tbody tr {
            cursor: pointer;
        }

        #chemAiPickerTable tbody tr.is-picked {
            background: var(--primary-soft);
        }

        .chem-aip-picked {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--primary-dark);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var $modal = $('#chemAiPickerModal');
            if (!$modal.length) return;

            // Đưa modal ra thẳng <body> để không nằm lồng trong <form> của modal cha
            $modal.appendTo(document.body);

            var $rows = $modal.find('tbody tr.chem-ai-pick-row');
            var $targetSelect = null;

            function refreshPicked() {
                $modal.find('[data-pick-selected]').text($modal.find('.chem-ai-pick-check:checked').length);
            }

            function applyFilter() {
                var q = ($modal.find('#chemAiPickerSearch').val() || '').toLowerCase().trim();
                var g = $modal.find('#chemAiPickerGroup').val();
                var selOnly = $modal.find('#chemAiPickerSelectedOnly').is(':checked');
                var visible = 0;

                $rows.each(function() {
                    var $r = $(this);
                    var hay = ($r.attr('data-search') || '').toLowerCase();
                    var groups = ($r.attr('data-groups') || '').split(',').filter(Boolean);
                    var ok = (!q || hay.indexOf(q) !== -1) &&
                        (!g || groups.indexOf(g) !== -1) &&
                        (!selOnly || $r.find('.chem-ai-pick-check').is(':checked'));
                    $r.toggle(ok);
                    if (ok) visible++;
                });

                $modal.find('[data-pick-visible]').text(visible);
                $modal.find('#chemAiPickerCheckAll').prop('checked', false);
            }

            /* ---------- Mở modal từ nút cạnh ô chọn hoạt chất ---------- */
            $(document).on('click', '.chem-ai-picker-open', function() {
                $targetSelect = $(this).closest('.md-modal').find('.chem-ai-select');
                var current = ($targetSelect.val() || []).map(String);

                $modal.find('.chem-ai-pick-check').each(function() {
                    var on = current.indexOf(String(this.value)) !== -1;
                    $(this).prop('checked', on).closest('tr').toggleClass('is-picked', on);
                });

                $modal.find('#chemAiPickerSearch').val('');
                $modal.find('#chemAiPickerGroup').val('');
                $modal.find('#chemAiPickerSelectedOnly').prop('checked', false);
                refreshPicked();
                applyFilter();
                $modal.modal('show');
            });

            $modal.on('input', '#chemAiPickerSearch', applyFilter);
            $modal.on('change', '#chemAiPickerGroup, #chemAiPickerSelectedOnly', applyFilter);

            $modal.on('change', '.chem-ai-pick-check', function() {
                $(this).closest('tr').toggleClass('is-picked', this.checked);
                refreshPicked();
                if ($modal.find('#chemAiPickerSelectedOnly').is(':checked')) applyFilter();
            });

            /* Bấm vào bất kỳ đâu trên dòng cũng tick / bỏ tick */
            $modal.on('click', 'tbody td', function(e) {
                if ($(e.target).is('input')) return;
                var $c = $(this).closest('tr').find('.chem-ai-pick-check');
                $c.prop('checked', !$c.prop('checked')).trigger('change');
            });

            /* Chọn / bỏ chọn tất cả dòng đang hiện */
            $modal.on('change', '#chemAiPickerCheckAll', function() {
                var on = this.checked;
                $rows.filter(':visible').find('.chem-ai-pick-check').prop('checked', on)
                    .closest('tr').toggleClass('is-picked', on);
                refreshPicked();
                if ($modal.find('#chemAiPickerSelectedOnly').is(':checked')) applyFilter();
            });

            /* ---------- Xác nhận: ghi lựa chọn về ô select2 của modal cha ---------- */
            $modal.on('click', '#chemAiPickerConfirm', function() {
                if (!$targetSelect || !$targetSelect.length) {
                    $modal.modal('hide');
                    return;
                }
                var ids = $modal.find('.chem-ai-pick-check:checked').map(function() {
                    return this.value;
                }).get();
                $targetSelect.val(ids).trigger('change');
                $modal.modal('hide');
            });

            /* ---------- Xếp chồng lên trên modal cha ---------- */
            $modal.on('show.bs.modal', function() {
                $(this).css('z-index', 1075);
                window.setTimeout(function() {
                    $('.modal-backdrop').not('.chem-aip-backdrop').last()
                        .addClass('chem-aip-backdrop').css('z-index', 1070);
                }, 0);
            });

            $modal.on('hidden.bs.modal', function() {
                $('.chem-aip-backdrop').removeClass('chem-aip-backdrop');
                // Bootstrap gỡ .modal-open khi đóng modal con -> trả lại cho modal cha
                if ($('.modal.show').length) $(document.body).addClass('modal-open');
            });
        });
    </script>
@endonce
