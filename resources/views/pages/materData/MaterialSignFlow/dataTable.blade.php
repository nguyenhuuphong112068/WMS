@include('pages.materData.shared.assets')

@php
    use App\Support\MaterialSignFlow;

    // Tiêu chí điều kiện cho JS, dạng danh sách để giữ đúng thứ tự hiển thị
    $sfCriteria = collect($criteriaOptions)->map(fn($c, $key) => [
        'key' => $key,
        'label' => $c['label'],
        'options' => collect($c['options'])->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])->values(),
    ])->values();

    // Nhóm điều kiện đã lưu -> dạng dòng {key, value} mà khối khai báo trên modal dùng
    $sfRows = fn($groups) => array_map(
        fn($group) => array_map(fn($key) => ['key' => $key, 'value' => $group[$key]], array_keys($group)),
        $groups
    );
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    @perm('materData_material_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Thêm vai trò
                        </button>
                    @endperm
                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">STT</th>
                                <th class="text-center" style="width: 80px">Cấp Ký</th>
                                <th style="width: 200px">Vai Trò Nhận Trình Ký</th>
                                <th style="width: 240px">Người Ký</th>
                                <th>Điều Kiện Nhận Trình Ký</th>
                                <th style="width: 140px">Người Tạo</th>
                                <th class="text-center" style="width: 105px">Ngày Tạo</th>
                                <th class="text-center" style="width: 110px">Sử Dụng</th>
                                <th class="text-center" style="width: 120px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @php $chipGroups = MaterialSignFlow::chipGroups($row->groups, $criteriaOptions); @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="text-center" data-order="{{ $row->level }}">
                                        <span class="sf-level">{{ $row->level }}</span>
                                    </td>
                                    <td class="font-weight-bold">{{ $row->role_name ?: 'NA' }}</td>
                                    <td>
                                        @forelse ($row->signers as $signer)
                                            <div class="sf-signer {{ $signer->signer_active ? '' : 'is-inactive' }}"
                                                title="{{ $signer->signer_active ? '' : 'Tài khoản đã bị khoá' }}">
                                                {{ MaterialSignFlow::signerLabel($signer) }}
                                                @if ($signer->signer_department_short)
                                                    <small>· {{ $signer->signer_department_short }}</small>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="md-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($chipGroups)
                                            <div class="sf-cgroups">
                                                @foreach ($chipGroups as $chips)
                                                    @if (!$loop->first)
                                                        <span class="sf-or">HOẶC</span>
                                                    @endif
                                                    <span class="sf-cgroup">
                                                        @foreach ($chips as $chip)
                                                            @if (!$loop->first)
                                                                <span class="sf-and">VÀ</span>
                                                            @endif
                                                            <span class="cat-chip {{ $chip['class'] }}" title="{{ $chip['label'] }}">{{ $chip['short'] }}</span>
                                                        @endforeach
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="md-tag"><i class="fas fa-infinity mr-1"></i>{{ MaterialSignFlow::ALWAYS_LABEL }}</span>
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
                                                        title="Sửa {{ $row->role_name }}"
                                                        data-row="{{ json_encode([
                                                            'id' => $row->id,
                                                            'role_id' => $row->role_id,
                                                            'level' => $row->level,
                                                            'signers' => $row->signers->pluck('user_id')->values(),
                                                            'conditions' => $sfRows($row->groups),
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endperm

                                                {{-- Badge số lần thay đổi, bấm vào để xem lịch sử --}}
                                                @include('pages.materData.shared.historyBadge', [
                                                    'count' => $historyCounts[$row->id] ?? 0,
                                                    'url' => route($mdRoute . 'history', ['id' => $row->id]),
                                                    'title' => $row->role_name,
                                                ])
                                            </span>

                                            @perm('materData_material_deActive')
                                                <form class="form-md-confirm d-inline" data-require-reason="1"
                                                    action="{{ route($mdRoute . 'deActive') }}" method="POST"
                                                    data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $mdLabel }} {{ $row->role_name }}?"
                                                    data-text="{{ $row->status_id == 1 ? 'Sau khi khoá, vai trò này sẽ không còn nhận trình ký đề nghị cấp phát vật tư.' : 'Sau khi mở khoá, vai trò này nhận trình ký trở lại.' }}"
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
        /* ---------- Bảng ---------- */
        .sf-level {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            font-size: 0.82rem;
        }

        .sf-signer {
            font-size: 0.85rem;
            line-height: 1.35;
        }

        .sf-signer small {
            color: #64748b;
        }

        .sf-signer.is-inactive {
            color: #B45309;
            text-decoration: line-through;
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

        .cat-chip.any {
            background: #F1F5F9;
            color: #475569;
            border-color: #CBD5E1;
        }

        .cat-chip.dept {
            background: #DCFCE7;
            color: #15803D;
            border-color: #86EFAC;
        }

        .sf-cgroups {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .sf-cgroup {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px;
            padding: 3px 6px;
            border: 1px dashed var(--primary-lighter);
            border-radius: var(--border-radius-md);
            background: #fff;
        }

        .sf-and,
        .sf-or {
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .sf-and {
            color: var(--primary);
        }

        .sf-or {
            color: #B45309;
        }

        /* ---------- Modal: người ký ---------- */
        .sf-users {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 8px;
        }

        .sf-user-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sf-user-row select {
            flex: 1 1 auto;
        }

        /* ---------- Modal: nhóm điều kiện ---------- */
        .sf-conds {
            display: flex;
            flex-direction: column;
        }

        .sf-group {
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            background: #fbfdff;
            padding: 10px 12px;
        }

        /* Giữa hai nhóm là toán tử HOẶC */
        .sf-group+.sf-group::before {
            content: 'HOẶC';
            display: block;
            width: max-content;
            margin: -24px auto 10px;
            padding: 1px 10px;
            border-radius: 999px;
            background: #FEF3C7;
            border: 1px solid #FCD34D;
            color: #B45309;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .sf-group+.sf-group {
            margin-top: 22px;
        }

        .sf-group-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .sf-group-title {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: var(--primary-dark);
        }

        .sf-cond-rows {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 8px;
        }

        .sf-cond-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sf-cond-row .sf-and {
            width: 28px;
            flex-shrink: 0;
            text-align: center;
        }

        .sf-cond-row:first-child .sf-and {
            visibility: hidden;
        }

        .sf-cond-row select {
            flex: 1 1 0;
            min-width: 0;
        }

        .sf-cond-always {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1.5px dashed #cbd5e1;
            border-radius: var(--border-radius-md);
            padding: 10px 14px;
            color: #64748b;
            font-size: 0.84rem;
            margin-bottom: 8px;
        }

        .sf-cond-always i {
            color: var(--primary);
        }

        .sf-group-add {
            margin-top: 10px;
        }
    </style>

    <script>
        /*
        | KHỐI KHAI BÁO TRONG MODAL - dùng chung cho modal Thêm mới và Cập nhật.
        |
        | - Người ký: các ô name="signers[]", chỉ hiện người thuộc vai trò đang chọn.
        | - Điều kiện: các nhóm, mỗi nhóm nhiều dòng tiêu chí = giá trị. Trong nhóm là VÀ,
        |   giữa các nhóm là HOẶC. Tên ô conditions[nhóm][dòng][key|value] được đánh lại
        |   mỗi lần thêm / bớt. Không có nhóm nào = vai trò nhận mọi đề nghị.
        */
        window.sfCriteria = @json($sfCriteria);
        window.sfAny = { value: @json(MaterialSignFlow::ANY_VALUE), label: @json(MaterialSignFlow::ANY_LABEL) };

        document.addEventListener('DOMContentLoaded', function() {

            /* ---------- Người ký ---------- */
            function sfFilterUsers($form) {
                var roleId = String($form.find('.sf-role').val() || '');

                $form.find('.sf-user').each(function() {
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

            function sfAddUser($form, userId) {
                var $row = $($form.find('.sf-user-template').html());

                $form.find('.sf-users').append($row);
                sfFilterUsers($form);
                if (userId) $row.find('.sf-user').val(String(userId));
                sfUsersChanged($form);
            }

            function sfUsersChanged($form) {
                var $rows = $form.find('.sf-user-row');
                $rows.find('.sf-user-del').prop('disabled', $rows.length <= 1);
            }

            $(document).on('change', '.sf-role', function() {
                sfFilterUsers($(this).closest('form'));
            });

            $(document).on('click', '.sf-user-add', function() {
                sfAddUser($(this).closest('form'));
            });

            $(document).on('click', '.sf-user-del', function() {
                var $form = $(this).closest('form');

                $(this).closest('.sf-user-row').remove();
                sfUsersChanged($form);
            });

            /* ---------- Điều kiện ---------- */
            function sfCriterion(key) {
                return window.sfCriteria.find(function(c) { return c.key === key; });
            }

            function sfFillValues($row, value) {
                var criterion = sfCriterion($row.find('.sf-cond-key').val());
                var $value = $row.find('.sf-cond-value').empty();

                $value.append($('<option value="">-- Giá trị --</option>'));
                // "Tất cả" = tiêu chí không giới hạn giá trị
                if (criterion) $value.append($('<option></option>').val(window.sfAny.value).text(window.sfAny.label));
                (criterion ? criterion.options : []).forEach(function(o) {
                    $value.append($('<option></option>').val(o.value).text(o.label));
                });
                $value.prop('disabled', !criterion);
                if (value !== undefined) $value.val(String(value));
            }

            function sfAddCond($group, key, value) {
                var $key = $('<select class="form-control sf-cond-key" required></select>')
                    .append('<option value="">-- Tiêu chí --</option>');

                window.sfCriteria.forEach(function(c) {
                    $key.append($('<option></option>').val(c.key).text(c.label));
                });

                var $row = $('<div class="sf-cond-row"></div>')
                    .append('<span class="sf-and">VÀ</span>')
                    .append($key)
                    .append('<select class="form-control sf-cond-value" required></select>')
                    .append('<button type="button" class="btn btn-sm btn-outline-danger sf-cond-del" title="Bớt tiêu chí này"><i class="fas fa-minus"></i></button>');

                $group.find('.sf-cond-rows').append($row);
                if (key) $key.val(key);
                sfFillValues($row, value);
            }

            function sfAddGroup($form, rows) {
                var $group = $(
                    '<div class="sf-group">' +
                    '<div class="sf-group-head"><span class="sf-group-title"></span>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger sf-group-del" title="Bỏ nhóm này"><i class="fas fa-times"></i></button></div>' +
                    '<div class="sf-cond-rows"></div>' +
                    '<button type="button" class="btn btn-sm btn-outline-primary sf-cond-add"><i class="fas fa-plus mr-1"></i> VÀ tiêu chí</button>' +
                    '</div>'
                );

                $form.find('.sf-conds').append($group);
                (rows && rows.length ? rows : [{}]).forEach(function(r) {
                    sfAddCond($group, r.key, r.value);
                });
            }

            /* Đánh lại tên ô, tiêu đề nhóm, khoá tiêu chí đã dùng trong cùng nhóm */
            function sfCondsChanged($form) {
                var $groups = $form.find('.sf-group');

                $groups.each(function(gi) {
                    var $group = $(this);
                    var used = $group.find('.sf-cond-key').map(function() { return this.value; }).get();

                    $group.find('.sf-group-title').text('Nhóm ' + (gi + 1) + ' — thoả tất cả tiêu chí');
                    $group.find('.sf-cond-row').each(function(ri) {
                        var $key = $(this).find('.sf-cond-key');

                        $key.attr('name', 'conditions[' + gi + '][' + ri + '][key]');
                        $(this).find('.sf-cond-value').attr('name', 'conditions[' + gi + '][' + ri + '][value]');
                        $key.find('option').each(function() {
                            this.disabled = !!this.value && this.value !== $key.val() && used.indexOf(this.value) !== -1;
                        });
                    });

                    $group.find('.sf-cond-add').prop('disabled', $group.find('.sf-cond-row').length >= window.sfCriteria.length);
                });

                var $add = $form.find('.sf-group-add');
                $form.find('.sf-cond-always').toggle($groups.length === 0);
                $add.find('.sf-group-add-text').text($groups.length ? 'HOẶC nhóm điều kiện khác' : 'Thêm nhóm điều kiện');
                $add.prop('disabled', $groups.length >= parseInt($add.data('max'), 10));
            }

            $(document).on('click', '.sf-group-add', function() {
                var $form = $(this).closest('form');

                sfAddGroup($form);
                sfCondsChanged($form);
            });

            $(document).on('click', '.sf-group-del', function() {
                var $form = $(this).closest('form');

                $(this).closest('.sf-group').remove();
                sfCondsChanged($form);
            });

            $(document).on('click', '.sf-cond-add', function() {
                var $form = $(this).closest('form');

                sfAddCond($(this).closest('.sf-group'));
                sfCondsChanged($form);
            });

            $(document).on('click', '.sf-cond-del', function() {
                var $form = $(this).closest('form');
                var $group = $(this).closest('.sf-group');

                $(this).closest('.sf-cond-row').remove();
                // Nhóm không còn tiêu chí nào thì bỏ luôn nhóm
                if (!$group.find('.sf-cond-row').length) $group.remove();
                sfCondsChanged($form);
            });

            $(document).on('change', '.sf-cond-key', function() {
                sfFillValues($(this).closest('.sf-cond-row'));
                sfCondsChanged($(this).closest('form'));
            });

            /* ---------- Dựng toàn bộ khối từ dữ liệu ---------- */
            function sfFill($form, data) {
                data = data || {};

                $form.find('.sf-users').empty();
                (data.signers && data.signers.length ? data.signers : ['']).forEach(function(id) {
                    sfAddUser($form, id);
                });

                $form.find('.sf-conds').empty();
                (data.conditions || []).forEach(function(rows) {
                    // Dữ liệu old() là object {0: {...}} chứ không phải mảng
                    sfAddGroup($form, $.map(rows || {}, function(r) { return r; }));
                });
                sfCondsChanged($form);
            }

            $(document).on('click', '.btn-md-create', function() {
                sfFill($('#createModal').find('form'), {});
            });

            /* Handler dùng chung đã đổ id / role_id / level theo name; ở đây dựng người ký + điều kiện */
            $(document).on('click', '.btn-md-edit', function() {
                sfFill($('#updateModal').find('form'), $(this).data('row') || {});
            });

            $('.sf-form').each(function() {
                sfFill($(this).closest('form'), $(this).data('initial') || {});
            });
        });
    </script>
@endonce
