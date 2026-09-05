@include('pages.materData.shared.assets')

@php
    /*
    | Đưa công thức về dạng chữ thường (H₂SO₄ -> H2SO4) để nạp vào data-search.
    | Nhờ vậy người dùng gõ "H2SO4" ở ô tìm kiếm vẫn tìm ra dòng dữ liệu.
    */
    $fxPlain = fn($value) => strtr((string) $value, [
        '₀' => '0', '₁' => '1', '₂' => '2', '₃' => '3', '₄' => '4',
        '₅' => '5', '₆' => '6', '₇' => '7', '₈' => '8', '₉' => '9',
        '₊' => '+', '₋' => '-', '₌' => '=', '₍' => '(', '₎' => ')',
        '⁰' => '0', '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4',
        '⁵' => '5', '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9',
        '⁺' => '+', '⁻' => '-', '⁼' => '=', '⁽' => '(', '⁾' => ')',
        '·' => '.',
    ]);

    $kg = fn($value) => $value === null || $value === ''
        ? null
        : rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');

    $pct = fn($value) => $value === null || $value === ''
        ? null
        : rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');

@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    <div class="d-flex flex-wrap align-items-center" style="gap: 10px">
                        @perm('materData_create')
                            <button type="button" class="btn btn-primary btn-md-create">
                                <i class="fas fa-plus mr-1"></i> Thêm mới
                            </button>
                        @endperm
                        <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                            data-target="#classifyGuideModal">
                            <i class="fas fa-info-circle mr-1"></i> Chú thích Phụ lục &amp; Nhóm
                        </button>
                    </div>
                    <p class="hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đang hoạt động {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} bản ghi.
                    </p>
                </div>

                @include('pages.materData.shared.classifyFilterBar')

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th>Tên Hoá Chất</th>
                                <th>Hoạt Chất Thành Phần</th>
                                <th class="text-right" style="width: 150px">Ngưỡng Khối Lượng Tồn Trữ Lớn Nhất Tại Một Thời Điểm (kg)</th>
                                <th style="width: 130px">Số CAS</th>
                                <th style="width: 90px">Phụ Lục</th>
                                <th style="width: 70px">Nhóm</th>
                                <th style="width: 70px">Bảng</th>
                                <th style="width: 230px">Phân Loại Nghị định số 24/2026/NĐ-CP</th>
                                <th style="width: 130px">Người Tạo</th>
                                <th class="text-center" style="width: 100px">Ngày Tạo</th>
                                <th class="text-center" style="width: 125px">Duyệt</th>
                                <th class="text-center" style="width: 100px">Sử Dụng</th>
                                <th class="text-center" style="width: 170px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @php
                                    $cas = collect($row->active_ingredients)->pluck('cas_no')->filter()->unique()->implode(', ');
                                    $formulaSearch = collect($row->active_ingredients)->pluck('chemical_formula')->filter()
                                        ->flatMap(fn ($f) => [$f, $fxPlain($f)])->implode(' ');
                                    $tableAis = collect($row->active_ingredients)->filter(fn ($ai) => in_array(9, $ai->groups, true));
                                    $minTableAThr = $tableAis->pluck('threshold_kg')->filter(fn ($v) => $v !== null)->min();
                                    // Ngưỡng của tên hoá chất: hỗn hợp thuộc nhóm 10 thì lấy ngưỡng nhóm nguy hại thấp nhất
                                    $governThr = $row->is_table_b ? $row->min_hazard_threshold_kg : $minTableAThr;
                                    $groups = $row->derived_groups ?? [];
                                    $groupSearch = collect($groups)->map(fn ($g) => 'Nhóm ' . $g)->implode(' ');

                                    $cls = collect($row->classifications ?? []);
                                    $apxOrder = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4];
                                    $apxList = $cls->pluck('appendix')->filter()->unique()
                                        ->sortBy(fn ($a) => $apxOrder[$a] ?? 9)->values();
                                    $grpList = $cls->pluck('group_no')
                                        ->filter(fn ($v) => $v !== null && $v !== '')->unique()->sort()->values();
                                    $tblList = $cls->pluck('table_ref')->filter()->unique()->sort()->values();
                                    $clsCodes = collect($groups)->map(fn ($g) => 'N' . $g);
                                @endphp
                                <tr data-apx="{{ $apxList->implode(',') }}" data-grp="{{ $grpList->implode(',') }}"
                                    data-tbl="{{ $tblList->implode(',') }}" data-classification="{{ $clsCodes->implode(',') }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="font-weight-bold">{{ $row->name }}</td>
                                    <td data-search="{{ $formulaSearch }}">
                                        @forelse ($row->active_ingredients as $ai)
                                            <div class="chem-ai-line">
                                                <span class="md-formula">{{ $ai->name }}</span>
                                                @foreach ($ai->groups as $g)
                                                    <span class="badge {{ \App\Support\ChemicalClassification::badgeClass($g) }} ml-1"
                                                        title="{{ $groupLabels[$g] ?? ('Nhóm ' . $g) }}">Nhóm {{ $g }}</span>
                                                @endforeach
                                                @if ($ai->content_percent !== null)
                                                    <span class="md-sub ml-1">{{ $pct($ai->content_percent) }}%</span>
                                                @endif
                                                @if ($ai->chemical_formula)
                                                    <span class="md-sub ml-1">{{ $ai->chemical_formula }}</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="md-empty">— chưa gắn —</span>
                                        @endforelse
                                    </td>
                                    <td class="text-right" data-order="{{ $governThr !== null ? $governThr : -1 }}">
                                        @if ($row->is_table_b)
                                            <span class="chem-thr chem-thr-lg">{{ $kg($row->min_hazard_threshold_kg) }}</span>
                                            <span class="md-sub d-block">Nhóm 10 · nhóm nguy hại {{ $row->strictest_hazard_code }} (thấp nhất trong {{ count($row->hazard_categories) }} nhóm đã tick)</span>
                                            @foreach ($tableAis as $ai)
                                                <div class="md-sub" style="opacity:.7">
                                                    Nhóm 9 — {{ $ai->name }}: {{ $ai->threshold_kg !== null ? $kg($ai->threshold_kg) . ' kg' : 'chưa có ngưỡng' }}
                                                </div>
                                            @endforeach
                                        @elseif ($tableAis->isEmpty())
                                            <span class="md-empty" title="Không có thành phần thuộc nhóm 9 - không áp ngưỡng theo tên hoạt chất">—</span>
                                        @else
                                            @foreach ($tableAis as $ai)
                                                <div class="chem-thr-line">
                                                    @if ($ai->threshold_kg !== null)
                                                        <span class="chem-thr">{{ $kg($ai->threshold_kg) }}</span>
                                                    @else
                                                        <span class="md-empty" title="Hoạt chất chưa khai ngưỡng - chưa dùng để cảnh báo">chưa có</span>
                                                    @endif
                                                    <span class="md-sub d-block">{{ $ai->name }}</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if ($cas)
                                            <span class="md-tag">{{ $cas }}</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td data-search="{{ $apxList->map(fn ($a) => 'Phụ lục ' . $a)->implode(' ') }}">
                                        @forelse ($apxList as $a)
                                            <span class="ai-cls-badge">{{ $a }}</span>
                                        @empty
                                            <span class="md-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td class="text-center" data-search="{{ $grpList->map(fn ($g) => 'Nhóm ' . $g)->implode(' ') }}">
                                        @forelse ($grpList as $g)
                                            <span class="ai-cls-badge">{{ $g }}</span>
                                        @empty
                                            <span class="md-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td class="text-center" data-search="{{ $tblList->map(fn ($t) => 'Bảng ' . $t)->implode(' ') }}">
                                        @forelse ($tblList as $t)
                                            <span class="ai-cls-badge">{{ $t }}</span>
                                        @empty
                                            <span class="md-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td data-search="{{ $groupSearch }}"
                                        data-order="{{ $groups ? min($groups) : 99 }}">
                                        @forelse ($groups as $g)
                                            <span class="badge {{ \App\Support\ChemicalClassification::badgeClass($g) }} mr-1 mb-1"
                                                title="{{ $groupLabels[$g] ?? ('Nhóm ' . $g) }}">Nhóm {{ $g }}</span>
                                        @empty
                                            <span class="md-empty">Không thuộc</span>
                                        @endforelse
                                    </td>
                                    <td class="md-sub">{{ $row->updated_by ?: $row->created_by ?: '—' }}</td>
                                    <td class="text-center md-sub">
                                        {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="text-center">
                                        @include('pages.materData.shared.appStatus', ['row' => $row])
                                    </td>
                                    <td class="text-center">
                                        @if ($row->status_id == 1)
                                            <span class="badge badge-success">Hoạt động</span>
                                        @else
                                            <span class="badge badge-danger">Đã khoá</span>
                                        @endif
                                    </td>
                                    <td>
                                        @include('pages.materData.shared.rowActions', [
                                            'prefix' => $mdRoute,
                                            'row' => $row,
                                            'historyCount' => $historyCounts[$row->id] ?? 0,
                                            'label' => $mdLabel,
                                            'title' => $row->name,
                                            'editData' => [
                                                'id' => $row->id,
                                                'name' => $row->name,
                                                'active_ingredient_ids' => $row->active_ingredient_ids,
                                                'hazard_category_ids' => $row->hazard_category_ids,
                                                'content_percents' => (object) $row->content_percents,
                                            ],
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .chem-ai-line + .chem-ai-line {
            margin-top: 3px;
        }

        .chem-thr-line + .chem-thr-line {
            margin-top: 4px;
            border-top: 1px dashed var(--primary-soft);
            padding-top: 4px;
        }

        .chem-thr {
            font-weight: 700;
            color: var(--primary-dark);
        }

        .chem-thr-lg {
            font-size: 1.05rem;
        }

        .md-modal .select2-container--bootstrap4 .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
        }

        .md-modal .select2-container {
            z-index: 1060;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ---------- Ô chọn nhiều hoạt chất: bật tìm kiếm ---------- */
            $('.md-modal').each(function() {
                var $modal = $(this);
                var $sel = $modal.find('.chem-ai-select');
                if (!$sel.length) return;

                $sel.select2({
                    theme: 'bootstrap4',
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: $sel.data('placeholder') || '-- Chọn --',
                    closeOnSelect: false,
                    language: {
                        noResults: function() {
                            return 'Không tìm thấy dữ liệu phù hợp';
                        }
                    }
                });
            });

            /* ---------- Bảng % khối lượng từng thành phần ---------- */
            function chemRebuildPercents($modal) {
                var $box = $modal.find('[data-percent-rows]');
                if (!$box.length) return;

                // Giữ lại giá trị đang gõ + hạt giống ban đầu (old() hoặc data-row khi Sửa)
                var values = {};
                try {
                    values = JSON.parse($box.attr('data-seed') || '{}') || {};
                } catch (e) {
                    values = {};
                }
                $box.find('input[data-percent-input]').each(function() {
                    values[$(this).data('ai')] = this.value;
                });

                var $opts = $modal.find('.chem-ai-select option:selected');
                $box.empty();

                if (!$opts.length) {
                    $box.append($('<div class="chem-percent-empty">Chọn hoạt chất ở ô trên để khai tỉ lệ %.</div>'));
                    $modal.find('[data-percent-section]').hide();
                    return;
                }

                $modal.find('[data-percent-section]').show();

                $opts.each(function() {
                    var id = this.value;
                    var name = $(this).data('name') || $(this).text();
                    var val = values[id] !== undefined && values[id] !== null ? values[id] : '';

                    var $row = $('<div class="chem-percent-row"></div>');
                    $row.append($('<span class="chem-percent-name"></span>').text(name));
                    $row.append(
                        $('<input type="number" step="0.0001" min="0" max="100" class="form-control form-control-sm">')
                        .attr('name', 'content_percent[' + id + ']')
                        .attr('data-percent-input', '1')
                        .attr('data-ai', id)
                        .attr('placeholder', '% kl')
                        .val(val)
                    );
                    $box.append($row);
                });
            }

            /* ---------- Nhóm 10 (Bảng B) chỉ bật cho HỖN HỢP: >= 2 hoạt chất + >= 1 thuộc nhóm 9 ---------- */
            function chemHazardCount($modal) {
                var n = $modal.find('.chem-hazard-input:checked').length;
                $modal.find('[data-hazard-count]').text(n ? (n + ' nhóm') : '').toggle(n > 0);
            }

            function chemHazardLock($modal) {
                var selected = $modal.find('.chem-ai-select').val() || [];
                var hasGroup9 = false;
                $modal.find('.chem-ai-select option:selected').each(function() {
                    if (String($(this).data('g9')) === '1') hasGroup9 = true;
                });

                var ok = selected.length >= 2 && hasGroup9;
                var $section = $modal.find('[data-hazard-section]');
                $section.toggleClass('is-locked', !ok);

                if (!ok) {
                    $section.find('.chem-hazard-input:checked').prop('checked', false)
                        .closest('.chem-hazard-item').removeClass('is-checked');
                }
                chemHazardCount($modal);
            }

            $(document).on('change', '.chem-hazard-input', function() {
                $(this).closest('.chem-hazard-item').toggleClass('is-checked', this.checked);
                chemHazardCount($(this).closest('.md-modal'));
            });

            $(document).on('change', '.chem-ai-select', function() {
                var $modal = $(this).closest('.md-modal');
                chemRebuildPercents($modal);
                chemHazardLock($modal);
            });

            /* ---------- Nút Sửa: điền ô chọn nhiều + % + checkbox (sau handler của shared.assets) ---------- */
            $(document).on('click', '.btn-md-edit', function() {
                var row = $(this).data('row') || {};
                var $modal = $($(this).data('modal') || '#updateModal');
                var aiIds = (row.active_ingredient_ids || []).map(String);
                var hazIds = (row.hazard_category_ids || []).map(String);

                $modal.find('[data-percent-rows]').attr('data-seed', JSON.stringify(row.content_percents || {}));

                $modal.find('.chem-ai-select').val(aiIds).trigger('change');
                $modal.find('.chem-hazard-input').each(function() {
                    var on = hazIds.indexOf(String(this.value)) !== -1;
                    $(this).prop('checked', on).closest('.chem-hazard-item').toggleClass('is-checked', on);
                });
                chemHazardLock($modal);
            });

            /* ---------- Nút Thêm mới: xoá trắng ---------- */
            $(document).on('click', '.btn-md-create', function() {
                var $modal = $($(this).data('modal') || '#createModal');
                $modal.find('[data-percent-rows]').attr('data-seed', '{}');
                $modal.find('.chem-ai-select').val(null).trigger('change');
                $modal.find('.chem-hazard-input').prop('checked', false)
                    .closest('.chem-hazard-item').removeClass('is-checked');
                chemHazardLock($modal);
            });

            /* ---------- Mở modal xong thì rà lại ---------- */
            $(document).on('shown.bs.modal', '.md-modal', function() {
                chemRebuildPercents($(this));
                chemHazardLock($(this));
            });
        });
    </script>
@endonce
