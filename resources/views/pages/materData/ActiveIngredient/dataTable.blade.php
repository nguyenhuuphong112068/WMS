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
                    @perm('materData_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Thêm mới
                        </button>
                    @endperm

                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">STT</th>
                                <th>Tên khoa học (danh pháp IUPAC)</th>
                                <th>Tên chất</th>
                                <th style="width: 120px">Mã số CAS</th>
                                <th style="width: 130px">Công Thức</th>
                                <th class="text-center" style="width: 95px">Bảng A</th>
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
                                <tr>
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
                                    <td class="text-center" data-order="{{ $row->is_table_a ? 1 : 0 }}">
                                        @if ($row->is_table_a)
                                            <span class="badge badge-danger"
                                                title="Thuộc Bảng A Phụ lục IV NĐ 24/2026/NĐ-CP">Bảng A</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right" data-order="{{ $row->threshold_kg ?? -1 }}">
                                        @if (!$row->is_table_a)
                                            <span class="md-empty">—</span>
                                        @elseif ($kg($row->threshold_kg) !== null)
                                            <span class="ai-threshold">{{ $kg($row->threshold_kg) }}</span>
                                        @else
                                            <span class="md-empty"
                                                title="Chưa nhập ngưỡng theo Bảng A NĐ 24/2026 - chưa dùng để cảnh báo">Chưa
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
                                                'is_table_a' => (int) $row->is_table_a,
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
            /* Ô "Ngưỡng tồn trữ" chỉ hiện và bắt buộc khi tick "Thuộc Bảng A". */
            function aiToggleThreshold(cb) {
                var $block = $(cb).closest('form').find('[data-table-a-only]');
                var $input = $block.find('input[name="threshold_kg"]');
                $block.toggle(cb.checked);
                $input.prop('required', cb.checked);
                if (!cb.checked) {
                    $input.val('');
                    $input.removeClass('is-invalid');
                    $block.find('.md-error').remove();
                }
            }

            $(document).on('change', 'input[data-table-a-toggle]', function() {
                aiToggleThreshold(this);
            });

            /* Nút Thêm mới: đồng bộ trạng thái required cho ngưỡng */
            $(document).on('click', '.btn-md-create', function() {
                var modal = $(this).data('modal') || '#createModal';
                setTimeout(function() {
                    $(modal).find('input[data-table-a-toggle]').each(function() {
                        aiToggleThreshold(this);
                    });
                }, 50);
            });

            /* Nút Sửa: shared.assets điền các ô text bằng .val(), nhưng checkbox phải set
               .prop('checked') riêng theo data-row. Chạy sau handler của shared.assets. */
            $(document).on('click', '.btn-md-edit', function() {
                var row = $(this).data('row') || {};
                var modal = $(this).data('modal') || '#updateModal';
                var $cb = $(modal).find('input[data-table-a-toggle]');
                $cb.prop('checked', !!Number(row.is_table_a));
                $cb.each(function() {
                    aiToggleThreshold(this);
                });
            });

            /* Sau khi modal hiện xong thì co giãn ô ngưỡng theo trạng thái checkbox. */
            $(document).on('shown.bs.modal', '.md-modal', function() {
                $(this).find('input[data-table-a-toggle]').each(function() {
                    aiToggleThreshold(this);
                });
            });

            /* Kiểm tra trước khi submit: nếu thuộc Bảng A thì ngưỡng phải > 0 */
            $(document).on('submit', '#createModal form, #updateModal form', function(e) {
                var $form = $(this);
                var isTableA = $form.find('input[data-table-a-toggle]').is(':checked');
                var $threshold = $form.find('input[name="threshold_kg"]');
                var val = $.trim($threshold.val());
                if (isTableA && (val === '' || isNaN(val) || Number(val) <= 0)) {
                    e.preventDefault();
                    $threshold.addClass('is-invalid');
                    $form.find('[data-table-a-only] .md-error').remove();
                    $threshold.after(
                        '<span class="md-error">Hoạt chất thuộc Bảng A bắt buộc phải có ngưỡng tồn trữ lớn hơn 0.</span>'
                    );
                    $threshold.focus();
                    return false;
                }
            });
        });
    </script>
@endonce
