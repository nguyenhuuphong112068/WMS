@include('pages.materData.shared.assets')

@php
    /*
    | Đưa công thức về dạng chữ thường (H₂SO₄ -> H2SO4) để nạp vào data-search.
    | Nhờ vậy người dùng gõ "H2SO4" ở ô tìm kiếm vẫn tìm ra dòng dữ liệu.
    */
    $fxPlain = fn($value) => strtr((string) $value, [
        '₀' => '0',
        '₁' => '1',
        '₂' => '2',
        '₃' => '3',
        '₄' => '4',
        '₅' => '5',
        '₆' => '6',
        '₇' => '7',
        '₈' => '8',
        '₉' => '9',
        '₊' => '+',
        '₋' => '-',
        '₌' => '=',
        '₍' => '(',
        '₎' => ')',
        '⁰' => '0',
        '¹' => '1',
        '²' => '2',
        '³' => '3',
        '⁴' => '4',
        '⁵' => '5',
        '⁶' => '6',
        '⁷' => '7',
        '⁸' => '8',
        '⁹' => '9',
        '⁺' => '+',
        '⁻' => '-',
        '⁼' => '=',
        '⁽' => '(',
        '⁾' => ')',
        '·' => '.',
    ]);

    /** Ngưỡng kg: bỏ số 0 thừa ở phần thập phân (50000.000 -> 50000). */
    $kg = fn($value) => $value === null || $value === ''
        ? null
        : rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    <div class="d-flex flex-wrap" style="gap: 10px">
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
                </div>

                @include('pages.materData.shared.classifyFilterBar')

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">STT</th>
                                <th>Tên khoa học (danh pháp IUPAC)</th>
                                <th>Tên chất</th>
                                <th style="width: 120px">Mã số CAS</th>
                                <th style="width: 130px">Công Thức</th>
                                <th style="width: 90px">Phụ Lục</th>
                                <th style="width: 70px">Nhóm</th>
                                <th style="width: 70px">Bảng</th>
                                <th style="width: 190px">Phân Loại NĐ 24/2026</th>
                                <th class="text-right" style="width: 140px">Ngưỡng Tồn Trữ (kg)</th>
                                <th style="width: 140px">Người Tạo</th>
                                <th class="text-center" style="width: 105px">Ngày Tạo</th>
                                <th class="text-center" style="width: 130px">Duyệt</th>
                                <th class="text-center" style="width: 105px">Sử Dụng</th>
                                <th class="text-center" style="width: 175px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @php
                                    $groups = $row->groups ?? [];
                                    $isGroup9 = in_array(9, $groups, true);
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
                                    <td class="font-weight-bold">
                                        {{ $row->name }}
                                        @if ($row->code)
                                            <div class="md-sub">{{ $row->code }}</div>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $row->name_en ?: '—' }}</td>
                                    <td>
                                        @if ($row->cas_no)
                                            <span class="md-tag">{{ $row->cas_no }}</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td
                                        data-search="{{ $row->chemical_formula ? $row->chemical_formula . ' ' . $fxPlain($row->chemical_formula) : '' }}">
                                        @if ($row->chemical_formula)
                                            <span class="md-formula">{{ $row->chemical_formula }}</span>
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
                                    <td data-search="{{ $groupSearch }}" data-order="{{ $groups ? min($groups) : 99 }}">
                                        @forelse ($groups as $g)
                                            <span class="badge {{ \App\Support\ChemicalClassification::badgeClass($g) }} mr-1 mb-1"
                                                title="{{ $groupLabels[$g] ?? ('Nhóm ' . $g) }}">Nhóm {{ $g }}</span>
                                        @empty
                                            <span class="md-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td class="text-right" data-order="{{ $row->threshold_kg ?? -1 }}">
                                        @if (!$isGroup9)
                                            <span class="md-empty">—</span>
                                        @elseif ($kg($row->threshold_kg) !== null)
                                            <span class="ai-threshold">{{ $kg($row->threshold_kg) }}</span>
                                        @else
                                            <span class="md-empty"
                                                title="Chưa nhập ngưỡng theo nhóm 9 NĐ 24/2026 - chưa dùng để cảnh báo">Chưa
                                                có</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $row->created_by ?: '—' }}</td>
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
                                                'name_en' => $row->name_en,
                                                'cas_no' => $row->cas_no,
                                                'chemical_formula' => $row->chemical_formula,
                                                'groups' => array_values($groups),
                                                'threshold_kg' => $row->threshold_kg,
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
        .ai-threshold {
            font-weight: 700;
            color: var(--primary-dark);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /* Ô "Ngưỡng tồn trữ" chỉ hiện và bắt buộc khi tick "Nhóm 9". */
            function aiToggleThreshold($form) {
                var on = $form.find('input[data-g9-toggle]').is(':checked');
                var $block = $form.find('[data-g9-only]');
                var $input = $block.find('input[name="threshold_kg"]');
                $block.toggle(on);
                $input.prop('required', on);
                if (!on) {
                    $input.val('');
                    $input.removeClass('is-invalid');
                    $block.find('.md-error').remove();
                }
            }

            function aiGroupCount($modal) {
                var n = $modal.find('.ai-group-input:checked').length;
                $modal.find('[data-group-count]').text(n ? (n + ' nhóm') : '').toggle(n > 0);
            }

            $(document).on('change', '.ai-group-input', function() {
                var $modal = $(this).closest('.md-modal');
                $(this).closest('.ai-group-item').toggleClass('is-checked', this.checked);
                aiToggleThreshold($modal.find('form'));
                aiGroupCount($modal);
            });

            /* Nút Thêm mới: bỏ chọn hết, đồng bộ ô ngưỡng */
            $(document).on('click', '.btn-md-create', function() {
                var $modal = $($(this).data('modal') || '#createModal');
                $modal.find('.ai-group-input').prop('checked', false)
                    .closest('.ai-group-item').removeClass('is-checked');
                setTimeout(function() {
                    aiToggleThreshold($modal.find('form'));
                    aiGroupCount($modal);
                }, 50);
            });

            /* Nút Sửa: shared.assets điền các ô text; checkbox nhóm set theo data-row.groups */
            $(document).on('click', '.btn-md-edit', function() {
                var row = $(this).data('row') || {};
                var $modal = $($(this).data('modal') || '#updateModal');
                var groups = (row.groups || []).map(String);

                $modal.find('.ai-group-input').each(function() {
                    var on = groups.indexOf(String(this.value)) !== -1;
                    $(this).prop('checked', on).closest('.ai-group-item').toggleClass('is-checked', on);
                });
                aiToggleThreshold($modal.find('form'));
                aiGroupCount($modal);
            });

            /* Sau khi modal hiện xong thì co giãn ô ngưỡng theo trạng thái checkbox. */
            $(document).on('shown.bs.modal', '.md-modal', function() {
                aiToggleThreshold($(this).find('form'));
                aiGroupCount($(this));
            });

            /* Kiểm tra trước khi submit: nếu thuộc nhóm 9 thì ngưỡng phải > 0 */
            $(document).on('submit', '#createModal form, #updateModal form', function(e) {
                var $form = $(this);
                var isGroup9 = $form.find('input[data-g9-toggle]').is(':checked');
                var $threshold = $form.find('input[name="threshold_kg"]');
                var val = $.trim($threshold.val());
                if (isGroup9 && (val === '' || isNaN(val) || Number(val) <= 0)) {
                    e.preventDefault();
                    $threshold.addClass('is-invalid');
                    $form.find('[data-g9-only] .md-error').remove();
                    $threshold.after(
                        '<span class="md-error">Hoạt chất thuộc nhóm 9 bắt buộc phải có ngưỡng tồn trữ lớn hơn 0.</span>'
                    );
                    $threshold.focus();
                    return false;
                }
            });
        });
    </script>
@endonce
