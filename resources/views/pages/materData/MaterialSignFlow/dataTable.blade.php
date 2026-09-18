@include('pages.materData.shared.assets')

@php
    use App\Support\MaterialSignFlow;
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    @perm('materData_material_create')
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
                                <th>Tên Quy Trình</th>
                                <th>Phân Loại Danh Mục Chung</th>
                                <th style="width: 160px">Phân Loại Của Phòng</th>
                                <th>Các Bước Trình Ký</th>
                                <th style="width: 140px">Người Tạo</th>
                                <th class="text-center" style="width: 105px">Ngày Tạo</th>
                                <th class="text-center" style="width: 110px">Sử Dụng</th>
                                <th class="text-center" style="width: 120px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @php
                                    $chips = MaterialSignFlow::chips($row->criteria);
                                    $rowSteps = $steps[$row->id] ?? collect();
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="font-weight-bold">{{ $row->name }}</td>
                                    <td>
                                        @if ($chips)
                                            <div class="cat-chips">
                                                @foreach ($chips as $chip)
                                                    <span class="cat-chip {{ $chip['class'] }}"
                                                        title="{{ $chip['label'] }}">{{ $chip['short'] }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="md-tag">{{ MaterialSignFlow::ANY_LABEL }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row->classification_name)
                                            <span class="cat-chip dept">{{ $row->classification_name }}</span>
                                        @else
                                            <span class="md-tag">{{ MaterialSignFlow::ANY_LABEL }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($rowSteps->count())
                                            <div class="sf-flow">
                                                @foreach ($rowSteps as $step)
                                                    @if (!$loop->first)
                                                        <i class="fas fa-angle-right sf-flow-arrow"></i>
                                                    @endif
                                                    <span class="sf-flow-step"
                                                        title="Bước {{ $step->step_no }} - {{ $step->role_name ?: 'NA' }}: {{ MaterialSignFlow::stepSignerNames($step) }}{{ $step->signers->count() > 1 ? ' (chỉ cần 1 người ký)' : '' }}">
                                                        <b>{{ $step->step_no }}</b>
                                                        <span class="sf-flow-person">
                                                            <span class="sf-flow-names">
                                                                @foreach ($step->signers as $signer)
                                                                    @if (!$loop->first)
                                                                        <span class="sf-flow-or">hoặc</span>
                                                                    @endif
                                                                    {{ MaterialSignFlow::signerLabel($signer) }}
                                                                @endforeach
                                                            </span>
                                                            <small>{{ $step->role_name ?: 'NA' }}</small>
                                                        </span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $row->updated_by ?: $row->created_by ?: '—' }}</td>
                                    <td class="text-center md-sub">
                                        {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="text-center">
                                        @if ($row->status_id == 1)
                                            <span class="badge badge-success">Hoạt động</span>
                                        @else
                                            <span class="badge badge-danger">Đã khoá</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="md-actions">
                                            <span class="md-btn-wrap">
                                                @perm('materData_material_update')
                                                    <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                        title="Sửa {{ $row->name }}"
                                                        data-row="{{ json_encode([
                                                            'id' => $row->id,
                                                            'name' => $row->name,
                                                            'classification_id' => $row->classification_id,
                                                            'criteria' => MaterialSignFlow::decode($row->criteria),
                                                            'steps' => $rowSteps->map(fn($s) => [
                                                                'role_id' => $s->role_id,
                                                                'user_ids' => $s->signers->pluck('user_id')->values(),
                                                            ])->values(),
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endperm

                                                {{-- Badge số lần thay đổi, bấm vào để xem lịch sử --}}
                                                @include('pages.materData.shared.historyBadge', [
                                                    'count' => $historyCounts[$row->id] ?? 0,
                                                    'url' => route($mdRoute . 'history', ['id' => $row->id]),
                                                    'title' => $row->name,
                                                ])
                                            </span>

                                            @perm('materData_material_deActive')
                                                <form class="form-md-confirm d-inline" data-require-reason="1"
                                                    action="{{ route($mdRoute . 'deActive') }}" method="POST"
                                                    data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $mdLabel }} {{ $row->name }}?"
                                                    data-text="{{ $row->status_id == 1 ? 'Sau khi khoá' : 'Sau khi mở khoá' }}, quy trình &quot;{{ $row->name }}&quot; {{ $row->status_id == 1 ? 'sẽ không còn được áp dụng cho phiếu đề nghị cấp phát vật tư.' : 'sẽ được áp dụng trở lại.' }}"
                                                    data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                                    <button type="submit"
                                                        class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                        title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                        <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                                                    </button>
                                                </form>
                                            @endperm
                                        </div>
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
        /* ---------- Chip điều kiện (dùng lại cách trình bày của Danh Mục Vật Tư) ---------- */
        .cat-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .cat-chip {
            display: inline-block;
            background: var(--primary-soft);
            color: var(--primary-dark);
            border: 1px solid var(--primary-lighter);
            border-radius: 999px;
            padding: 1px 9px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            cursor: help;
        }

        .cat-chip.critical {
            background: #FEE2E2;
            color: #B91C1C;
            border-color: #FCA5A5;
        }

        .cat-chip.banned {
            background: #FEF3C7;
            color: #B45309;
            border-color: #FCD34D;
        }

        .cat-chip.dept {
            background: #DCFCE7;
            color: #15803D;
            border-color: #86EFAC;
        }

        /* ---------- Dãy bước ký trên bảng ---------- */
        .sf-flow {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 5px;
        }

        .sf-flow-step {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fff;
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-md);
            padding: 2px 9px;
            font-size: 0.78rem;
            color: var(--text-main);
            white-space: nowrap;
        }

        .sf-flow-step b {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-size: 0.68rem;
        }

        .sf-flow-person {
            display: inline-flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .sf-flow-names {
            display: block;
        }

        .sf-flow-or {
            color: #94a3b8;
            font-size: 0.68rem;
            font-style: italic;
            margin: 0 2px;
        }

        .sf-flow-person small {
            color: #64748b;
            font-size: 0.68rem;
        }

        .sf-flow-arrow {
            color: var(--primary-lighter);
            font-size: 0.85rem;
        }

        /* ---------- Khối khai điều kiện trong modal ---------- */
        .sf-check-group {
            display: flex;
            flex-direction: column;
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            padding: 10px 12px;
            background: #fff;
        }

        .sf-subgroup+.sf-subgroup {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #e6eef8;
        }

        .sf-subgroup-title {
            display: block;
            margin-bottom: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: var(--primary-dark);
        }

        .sf-options {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .sf-check-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 0;
            padding: 5px 11px;
            border: 1px solid #dbe6f2;
            border-radius: 999px;
            font-weight: 400;
            font-size: 0.83rem;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .sf-check-item:hover {
            border-color: var(--primary-light);
            background: var(--primary-soft);
        }

        .sf-check-item.is-checked {
            border-color: var(--primary);
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 600;
        }

        .sf-check-input {
            width: 15px;
            height: 15px;
            margin: 0;
            accent-color: var(--primary);
            cursor: pointer;
        }

        /* ---------- Khối khai các bước ký trong modal ---------- */
        .sf-steps {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 10px;
        }

        .sf-step {
            display: flex;
            flex-direction: column;
            gap: 6px;
            border: 1px solid #e6eef8;
            border-radius: var(--border-radius-md);
            padding: 10px;
            background: #fbfdff;
        }

        .sf-step-head {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .sf-step-users {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding-left: 37px;
        }

        .sf-step-user-row {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .sf-step-user-row select {
            flex: 1 1 auto;
        }

        .sf-step-no {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .sf-step-head select {
            flex: 1 1 auto;
        }

        .sf-step-del,
        .sf-user-add,
        .sf-user-del {
            flex-shrink: 0;
        }

        .sf-step-add {
            border-radius: var(--border-radius-md);
        }
    </style>

    <script>
        /*
        | KHỐI KHAI BÁO TRONG MODAL - dùng chung cho modal Thêm mới và Cập nhật.
        |
        | - Ô tick điều kiện: name="criteria[<tiêu chí>]", ô "Tất cả" có value rỗng nên
        |   tiêu chí đó không tham gia lọc (xem App\Support\MaterialSignFlow::encode).
        | - Bước ký: mỗi dòng một ô select name="steps[]", thứ tự dòng chính là thứ tự ký
        |   nên phải đánh số lại mỗi khi thêm / bớt dòng.
        */
        document.addEventListener('DOMContentLoaded', function() {

            /* ---------- Tô đậm ô đang chọn ---------- */
            $(document).on('change', '.sf-check-input', function() {
                var name = $(this).attr('name');

                $(this).closest('form').find('.sf-check-input[name="' + name + '"]').each(function() {
                    $(this).closest('.sf-check-item').toggleClass('is-checked', this.checked);
                });
            });

            /* ---------- Lọc danh sách người ký theo vai trò của bước ----------
               Mỗi <option> người ký mang data-roles = mọi vai trò của người đó. Chọn vai
               trò nào thì chỉ còn người thuộc vai trò đó; người đang chọn mà không thuộc
               vai trò mới thì bỏ chọn để không lưu được cặp vai trò / người lệch nhau. */
            function sfFilterUsers($step) {
                var roleId = String($step.find('.sf-step-role').val() || '');

                $step.find('.sf-step-user').each(function() {
                    var $user = $(this);

                    $user.find('option').each(function() {
                        if (!this.value) return;

                        var roles = String($(this).data('roles') || '').split(',');
                        var ok = roleId !== '' && roles.indexOf(roleId) !== -1;

                        this.hidden = !ok;
                        this.disabled = !ok;

                        if (!ok && this.selected) $user.val('');
                    });
                });
            }

            $(document).on('change', '.sf-step-role', function() {
                sfFilterUsers($(this).closest('.sf-step'));
            });

            /* ---------- Thêm một ô người ký vào khối bước ký ---------- */
            function sfAddUserRow($form, $step) {
                var $row = $($form.find('.sf-user-template').html());

                $step.find('.sf-step-users').append($row);

                return $row;
            }

            /* ---------- Đánh số lại các bước và đặt lại tên ô người ký ----------
               Tên ô người ký là signers[<chỉ số bước>][] nên mỗi lần thêm / bớt bước phải
               gán lại theo vị trí mới, nếu không hai bước sẽ ghi đè dữ liệu của nhau. */
            function sfRenumber($box) {
                $box.find('.sf-step').each(function(index) {
                    var $step = $(this);

                    $step.find('.sf-step-no').text(index + 1);
                    $step.find('.sf-step-user').attr('name', 'signers[' + index + '][]');

                    // Bước phải còn ít nhất một người ký: còn đúng một thì khoá nút bớt
                    var $rows = $step.find('.sf-step-user-row');
                    $rows.find('.sf-user-del').prop('disabled', $rows.length <= 1);

                    sfFilterUsers($step);
                });

                // Quy trình phải còn ít nhất một bước: còn đúng một thì khoá nút bớt bước
                $box.find('.sf-step-del').prop('disabled', $box.find('.sf-step').length <= 1);
            }

            /* ---------- Thêm / bớt người ký của một bước ---------- */
            $(document).on('click', '.sf-user-add', function() {
                var $form = $(this).closest('form');

                sfAddUserRow($form, $(this).closest('.sf-step'));
                sfRenumber($form.find('.sf-steps'));
            });

            $(document).on('click', '.sf-user-del', function() {
                var $box = $(this).closest('.sf-steps');

                $(this).closest('.sf-step-user-row').remove();
                sfRenumber($box);
            });

            /* ---------- Thêm một bước ký ---------- */
            $(document).on('click', '.sf-step-add', function() {
                var $form = $(this).closest('form');
                var $box = $form.find('.sf-steps');

                if ($box.find('.sf-step').length >= parseInt($box.data('max'), 10)) {
                    Swal.fire({
                        title: 'Không thêm được',
                        text: 'Quy trình tối đa ' + $box.data('max') + ' bước trình ký.',
                        icon: 'warning',
                        confirmButtonColor: '#2E7BC4',
                        confirmButtonText: 'Đã hiểu'
                    });
                    return;
                }

                var $step = $($form.find('.sf-step-template').html());

                $box.append($step);
                sfAddUserRow($form, $step);
                sfRenumber($box);
            });

            /* ---------- Bớt một bước ký ---------- */
            $(document).on('click', '.sf-step-del', function() {
                var $box = $(this).closest('.sf-steps');

                $(this).closest('.sf-step').remove();
                sfRenumber($box);
            });

            /* ---------- Mở modal Thêm mới: trả khối khai báo về mặc định ----------
               form.reset() của phần JS dùng chung chỉ trả lại giá trị các ô, không gỡ được
               những dòng bước ký do JS thêm vào ở lần khai trước. */
            $(document).on('click', '.btn-md-create', function() {
                var $form = $('#createModal').find('form');
                var $box = $form.find('.sf-steps');

                $form.find('.sf-check-input').each(function() {
                    var isAny = this.value === '';

                    $(this).prop('checked', isAny).closest('.sf-check-item').toggleClass('is-checked', isAny);
                });

                var $step = $($form.find('.sf-step-template').html());

                $box.empty().append($step);
                sfAddUserRow($form, $step);
                sfRenumber($box);
            });

            /* ---------- Đổ dữ liệu dòng đang sửa vào modal Cập nhật ----------
               Phần JS dùng chung chỉ đổ được ô có name đơn (Tên quy trình, Phân loại của
               phòng); điều kiện là nhóm radio name="criteria[...]" và các bước ký là dãy
               dòng do JS dựng nên phải tự đổ ở đây. Handler này gắn sau nên chạy sau
               phần dùng chung. */
            $(document).on('click', '.btn-md-edit', function() {
                var row = $(this).data('row') || {};
                var criteria = row.criteria || {};
                var $form = $('#updateModal').find('form');
                var $box = $form.find('.sf-steps');

                $form.find('.sf-check-input').each(function() {
                    var key = ($(this).attr('name').match(/^criteria\[(.+)\]$/) || [])[1];
                    var checked = key ? (criteria[key] || '') === this.value : false;

                    $(this).prop('checked', checked).closest('.sf-check-item').toggleClass('is-checked', checked);
                });

                $box.empty();

                (row.steps && row.steps.length ? row.steps : [{}]).forEach(function(step) {
                    var $step = $($form.find('.sf-step-template').html());
                    var users = (step.user_ids && step.user_ids.length) ? step.user_ids : [''];

                    $step.find('.sf-step-role').val(step.role_id ? String(step.role_id) : '');

                    users.forEach(function(userId) {
                        var $row = sfAddUserRow($form, $step);

                        // Lọc trước rồi mới chọn: option chưa mở khoá thì val() không ăn
                        sfFilterUsers($step);
                        $row.find('.sf-step-user').val(userId ? String(userId) : '');
                    });

                    $box.append($step);
                });

                sfRenumber($box);
            });

            /* ---------- Dựng sẵn số thứ tự cho các khối đang có trên trang ---------- */
            $('.sf-steps').each(function() {
                sfRenumber($(this));
            });
        });
    </script>
@endonce
