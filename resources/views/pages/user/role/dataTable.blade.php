<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<style>
    /* ===== Thanh công cụ ===== */
    .perm-toolbar-card {
        border: none;
        border-radius: var(--border-radius-lg, 12px);
        box-shadow: var(--shadow-sm, 0 2px 6px rgba(0, 0, 0, .06));
        margin-top: 14px;
        margin-bottom: 20px;
    }

    .perm-toolbar-card .card-body {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
    }

    .btn-manage-roles {
        border-radius: var(--border-radius-md, 8px);
        font-weight: 600;
    }

    .role-search {
        max-width: 320px;
        border-radius: var(--border-radius-md, 8px);
    }

    .perm-kind-filter .btn {
        font-weight: 600;
        font-size: 13.5px;
    }

    .perm-kind-filter .btn:first-child {
        border-radius: var(--border-radius-md, 8px) 0 0 var(--border-radius-md, 8px);
    }

    .perm-kind-filter .btn:last-child {
        border-radius: 0 var(--border-radius-md, 8px) var(--border-radius-md, 8px) 0;
    }

    .perm-role-filter {
        position: relative;
    }

    .perm-role-filter > .btn {
        border-radius: var(--border-radius-md, 8px);
        font-weight: 600;
        font-size: 13.5px;
    }

    .perm-role-filter.is-filtered > .btn {
        background: var(--primary, #2E7BC4);
        color: #fff;
    }

    .perm-role-menu {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        z-index: 20;
        min-width: 260px;
        max-height: 360px;
        overflow-y: auto;
        padding: 8px 0;
        background: #fff;
        border-radius: var(--border-radius-md, 8px);
        box-shadow: var(--shadow-md, 0 6px 20px rgba(0, 0, 0, .12));
    }

    .perm-role-menu-actions {
        display: flex;
        justify-content: space-between;
        padding: 2px 14px 8px;
        margin-bottom: 4px;
        border-bottom: 1px solid #EEF3F8;
        font-size: 13px;
    }

    .perm-role-option {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        padding: 6px 14px;
        font-weight: 400;
        font-size: 14px;
        cursor: pointer;
        transition: background .2s ease;
    }

    .perm-role-option:hover {
        background: var(--primary-soft, #EAF3FC);
    }

    .perm-role-option input,
    .perm-only-granted input {
        accent-color: var(--primary, #2E7BC4);
    }

    .perm-only-granted {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        font-size: 13.5px;
        color: var(--primary, #2E7BC4);
        cursor: pointer;
    }

    .perm-only-granted.is-disabled {
        color: #A0AEC0;
        cursor: default;
    }

    .perm-table .is-role-hidden {
        display: none;
    }

    /* Hàng tiêu đề loại nhóm quyền (Chung / Vật Tư / Hoá Chất / Chất Chuẩn) phía trên tên nhóm quyền */
    .perm-table thead tr.perm-kind-head-row th.perm-kind-head {
        height: 28px;
        padding: 4px 6px;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        font-size: 11.5px;
        letter-spacing: .6px;
        text-transform: uppercase;
        background: var(--primary-soft, #EAF3FC);
        border-left: 2px solid #fff;
        box-shadow: none;
    }


    .perm-table thead th.is-material { box-shadow: inset 0 -3px 0 #16A34A; }
    .perm-table thead th.is-vpp { box-shadow: inset 0 -3px 0 var(--db-group-vpp, #C2417E); }
    .perm-table thead th.is-chemical { box-shadow: inset 0 -3px 0 #F59E0B; }
    .perm-table thead th.is-standard { box-shadow: inset 0 -3px 0 var(--accent, #17B8D4); }
    .perm-table thead th.is-common { box-shadow: inset 0 -3px 0 var(--primary, #2E7BC4); }
    .perm-table thead th.perm-kind-head.is-material { color: #15803D; }
    .perm-table thead th.perm-kind-head.is-vpp { color: var(--db-group-vpp, #C2417E); }
    .perm-table thead th.perm-kind-head.is-chemical { color: #B45309; }
    .perm-table thead th.perm-kind-head.is-standard { color: #0E8FA6; }

    /* ===== Mỗi model = 1 card ===== */
    .perm-model-card {
        border: none;
        border-radius: var(--border-radius-lg, 14px);
        box-shadow: 0 4px 16px rgba(46, 123, 196, .10);
        overflow: clip;
        margin-bottom: 22px;
    }

    .perm-model-header {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 9px 18px;
        border: none;
        background: linear-gradient(135deg, var(--primary-dark, #1F5E9E), var(--primary, #2E7BC4));
        color: #fff;
        cursor: pointer;
        user-select: none;
        transition: filter .15s ease;
    }

    .perm-model-header:hover {
        filter: brightness(1.06);
    }

    .perm-model-icon {
        width: 28px;
        height: 28px;
        flex: 0 0 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .18);
    }

    .perm-model-name {
        font-size: 13.5px;
        font-weight: 700;
        letter-spacing: .8px;
        text-transform: uppercase;
    }

    .perm-model-toggle {
        margin-left: auto;
        font-size: 13px;
        transition: transform .2s ease;
    }

    .perm-model-card.is-collapsed .perm-model-toggle {
        transform: rotate(-90deg);
    }

    .perm-model-card .card-body {
        padding: 0;
    }

    /* ===== Bảng quyền trong card ===== */
    /* Card hiện đủ mọi quyền (không cuộn dọc), chỉ cuộn ngang khi nhiều cột nhóm quyền */
    .perm-table-scroll {
        overflow-x: auto;
    }

    /* Tiêu đề nhóm quyền: JS (syncStickyHeads) dịch xuống ngay dưới thanh trên cùng khi cuộn trang.
       Không dùng position: sticky vì vùng cuộn ngang chặn dính theo trang */
    .perm-table thead th {
        position: relative;
        z-index: 3;
    }

    /* Bảng gọn kiểu ma trận của Hướng Dẫn Sử Dụng: tên nhóm quyền xoay dọc, cột hẹp, kẻ ô */
    .perm-table {
        width: max-content;
        min-width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
    }

    .perm-table col.perm-col-name {
        width: 300px;
    }

    .perm-table col.perm-col-role {
        width: 42px;
    }

    .perm-table th,
    .perm-table td {
        padding: 5px 8px;
        border-right: 1px solid #E3ECF5;
        border-bottom: 1px solid #E3ECF5;
    }

    .perm-table thead th {
        background: var(--primary-soft, #EAF3FC);
        color: var(--primary, #2E7BC4);
        font-weight: 700;
    }

    .perm-table thead th.perm-name-head {
        vertical-align: bottom;
        font-size: 13px;
    }

    .perm-table thead th[data-role-col] {
        padding: 8px 2px;
        text-align: center;
        vertical-align: bottom;
        font-size: 12px;
    }

    .perm-table thead th[data-role-col] > span {
        display: inline-block;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        max-height: 170px;
        line-height: 1.15;
        text-align: left;
    }

    /* Ghim cột "Quyền" khi cuộn ngang */
    .perm-table thead th.perm-name-head,
    .perm-table td:first-child {
        position: sticky;
        left: 0;
        z-index: 2;
    }

    .perm-table td:first-child {
        background: #fff;
    }

    .perm-table thead th.perm-name-head {
        z-index: 4;
    }

    .perm-table tbody tr.permission-row:hover td {
        background: var(--primary-soft, #EAF3FC);
    }

    /* Hàng tiêu đề vùng Vật Tư / Hoá Chất / Chất Chuẩn / Dùng Chung trong một khối: màu theo loại để ngăn vùng */
    .perm-group-row td {
        background: var(--bg-neutral, #F5F9FD) !important;
        color: var(--primary-dark, #1F5E9E);
        font-weight: 700;
        font-size: 13px;
        letter-spacing: .6px;
        text-transform: uppercase;
        padding: 8px 10px;
        border-top: 2px solid var(--primary-lighter, #9CC7EE);
        box-shadow: inset 4px 0 0 var(--primary, #2E7BC4);
    }

    .perm-group-row[data-kind="material"] td {
        background: rgba(22, 163, 74, .08) !important;
        color: #15803D;
        border-top-color: #16A34A;
        box-shadow: inset 4px 0 0 #16A34A;
    }

    .perm-group-row[data-kind="vpp"] td {
        background: rgba(var(--db-group-vpp-rgb, 194, 65, 126), .08) !important;
        color: var(--db-group-vpp, #C2417E);
        border-top-color: var(--db-group-vpp, #C2417E);
        box-shadow: inset 4px 0 0 var(--db-group-vpp, #C2417E);
    }

    .perm-group-row[data-kind="chemical"] td {
        background: rgba(245, 158, 11, .10) !important;
        color: #B45309;
        border-top-color: #F59E0B;
        box-shadow: inset 4px 0 0 #F59E0B;
    }

    .perm-group-row[data-kind="standard"] td {
        background: rgba(23, 184, 212, .10) !important;
        color: #0E8FA6;
        border-top-color: var(--accent, #17B8D4);
        box-shadow: inset 4px 0 0 var(--accent, #17B8D4);
    }

    .perm-group-row td:first-child {
        z-index: 1;
    }

    /* Hàng số quyền đã cấp của từng nhóm quyền trong khối */
    .perm-count-row td {
        background: var(--primary-soft, #EAF3FC) !important;
        color: var(--primary-dark, #1F5E9E);
        font-weight: 700;
        text-align: center;
        border-bottom: 2px solid var(--primary-lighter, #9CC7EE);
    }

    .perm-count-row td:first-child {
        text-align: left;
    }

    .perm-name-cell .permission-name {
        color: var(--text-main, #2D3748);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Mã quyền + phạm vi dữ liệu gốc: chỉ để ô tìm kiếm đọc được, xem bằng tooltip */
    .perm-name-cell .permission-code,
    .perm-name-cell .permission-scope {
        display: none;
    }

    /* Nút tick / bỏ tick một quyền cho mọi nhóm quyền đang hiện, hiện khi rê chuột vào dòng */
    .perm-name-cell {
        padding-right: 34px !important;
    }

    .perm-row-all {
        position: absolute;
        top: 50%;
        right: 6px;
        width: 24px;
        height: 22px;
        padding: 0;
        transform: translateY(-50%);
        border: 1px solid var(--primary-lighter, #9CC7EE);
        border-radius: 6px;
        background: #fff;
        color: var(--primary, #2E7BC4);
        font-size: 11px;
        opacity: 0;
        cursor: pointer;
        transition: opacity .2s ease, background .2s ease, color .2s ease;
    }

    .permission-row:hover .perm-row-all,
    .perm-row-all:focus {
        opacity: 1;
    }

    .perm-row-all:hover {
        background: var(--primary, #2E7BC4);
        color: #fff;
    }

    .perm-child-icon {
        margin-right: 4px;
        font-size: 10px;
        color: var(--primary-lighter, #9CC7EE);
    }

    .permission-row.is-child .permission-name {
        color: #64748B;
    }

    /* Đang kéo chọn nhiều ô: không bôi đen chữ, ô dưới chuột sáng lên */
    body.perm-dragging,
    body.perm-dragging * {
        user-select: none;
        cursor: cell !important;
    }

    body.perm-dragging td.perm-cell:hover {
        background: var(--primary-lighter, #9CC7EE) !important;
    }

    .perm-cell {
        padding: 4px 2px !important;
        text-align: center;
        vertical-align: middle;
    }

    .step-checkbox {
        width: 16px;
        height: 16px;
        margin: 0;
        vertical-align: middle;
        cursor: pointer;
        accent-color: var(--primary, #2E7BC4);
        transition: box-shadow .2s ease, transform .1s ease;
    }


    .step-checkbox:active {
        transform: scale(.9);
    }

    .step-checkbox[aria-readonly="true"] {
        cursor: default;
    }

    .step-checkbox[aria-readonly="true"]:active {
        transform: none;
    }

    .perm-empty {
        padding: 40px 20px;
        text-align: center;
        color: #94A3B8;
    }

    /* ===== Modal quản lý nhóm quyền ===== */
    #manageRoleModal .role-list-table th {
        background-color: var(--primary-soft, #EAF3FC);
        color: var(--primary, #2E7BC4);
        font-weight: 700;
    }

    #manageRoleModal .role-form-box {
        background-color: var(--bg-neutral, #F5F9FD);
        border: 1px solid var(--primary-soft, #EAF3FC);
        border-radius: var(--border-radius-md, 8px);
        padding: 16px;
        margin-top: 14px;
    }

    .role-kind-chip {
        display: inline-block;
        margin: 1px 2px 1px 0;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        background: var(--primary-soft, #EAF3FC);
        color: var(--primary, #2E7BC4);
    }

    .role-kind-chip.is-material { background: rgba(22, 163, 74, .12); color: #15803D; }
    .role-kind-chip.is-vpp { background: rgba(var(--db-group-vpp-rgb, 194, 65, 126), .12); color: var(--db-group-vpp, #C2417E); }
    .role-kind-chip.is-chemical { background: rgba(245, 158, 11, .15); color: #B45309; }
    .role-kind-chip.is-standard { background: rgba(23, 184, 212, .14); color: #0E8FA6; }

    .role-kind-option {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0 18px 0 0;
        font-weight: 400;
        cursor: pointer;
    }

    .role-kind-option input {
        accent-color: var(--primary, #2E7BC4);
    }

    #manageRoleModal .role-form-box .form-title {
        font-weight: 700;
        color: var(--primary, #2E7BC4);
        margin-bottom: 10px;
    }
</style>

<div class="content-wrapper">
    <section class="content">

        {{-- Thanh công cụ --}}
        <div class="card perm-toolbar-card">
            <div class="card-body">
                @perm('role_manage')
                    <button type="button" class="btn btn-success btn-manage-roles" data-toggle="modal"
                        data-target="#manageRoleModal">
                        <i class="fas fa-users-cog mr-1"></i> Quản Lý Nhóm Quyền
                    </button>
                @endperm
                <input type="text" class="form-control role-search" id="rolePermissionSearch"
                    placeholder="Tìm quyền theo tên...">

                <div class="btn-group perm-kind-filter" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-kind="">Tất cả</button>
                    @foreach ($kinds as $kindKey => $kind)
                        <button type="button" class="btn btn-outline-primary" data-kind="{{ $kindKey }}">
                            <i class="fas {{ $kind['icon'] }} mr-1"></i>{{ $kind['label'] }}
                        </button>
                    @endforeach
                </div>

                <div class="perm-role-filter">
                    <button type="button" class="btn btn-outline-primary" id="btnRoleFilter">
                        <i class="fas fa-users mr-1"></i> <span id="roleFilterLabel">Tất cả nhóm quyền</span>
                        <i class="fas fa-caret-down ml-1"></i>
                    </button>
                    <div class="perm-role-menu" id="roleFilterMenu" hidden>
                        <div class="perm-role-menu-actions">
                            <a href="#" id="roleFilterAll">Chọn tất cả</a>
                            <a href="#" id="roleFilterNone">Bỏ chọn</a>
                        </div>
                        @foreach ($roles as $role)
                            <label class="perm-role-option">
                                <input type="checkbox" class="role-filter-item" value="{{ $role->id }}">
                                {{ $role->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="perm-only-granted mb-0" title="Chỉ hiện quyền mà ít nhất một nhóm quyền đang chọn đã được cấp">
                    <input type="checkbox" id="onlyGranted" disabled> Chỉ quyền đã cấp
                </label>

                <button type="button" class="btn btn-outline-primary ml-auto" id="btnToggleAllCards"
                    style="border-radius: var(--border-radius-md, 8px); font-weight: 600">
                    <i class="fas fa-compress-alt mr-1"></i> Thu gọn tất cả
                </button>
            </div>
        </div>

        {{-- Không có quyền Phân Quyền Cho Nhóm: ô vẫn hiện tick xanh nhưng chỉ đọc (không dùng disabled vì bị xám) --}}
        @php $canUpdateRole = user_can('role_update'); @endphp

        @if (empty($modules))
            <div class="card perm-model-card">
                <div class="card-body">
                    <div class="alert alert-warning mb-0">
                        Chưa có quyền nào trong hệ thống. Chạy <code>php artisan migrate</code> để nạp danh sách quyền.
                    </div>
                </div>
            </div>
        @else
            {{-- Mỗi module leftNAV một card riêng --}}
            @foreach ($modules as $modelKey => $model)
                <div class="card perm-model-card" data-model="{{ $modelKey }}">
                    <div class="card-header perm-model-header">
                        <span class="perm-model-icon"><i class="fas {{ $model['icon'] }}"></i></span>
                        <span class="perm-model-name">{{ $model['label'] }}</span>
                        <i class="fas fa-chevron-down perm-model-toggle"></i>
                    </div>

                    <div class="card-body">
                        <div class="perm-table-scroll">
                            <table class="perm-table">
                                <colgroup>
                                    <col class="perm-col-name">
                                    @foreach ($roles as $role)
                                        <col class="perm-col-role" data-role-col="{{ $role->id }}">
                                    @endforeach
                                </colgroup>
                                <thead>
                                    <tr class="perm-kind-head-row">
                                        <th rowspan="2" class="perm-name-head">Quyền</th>
                                        @foreach ($roles->groupBy('kind') as $roleKind => $kindRoles)
                                            <th class="perm-kind-head is-{{ $roleKind }}" data-head-kind="{{ $roleKind }}"
                                                colspan="{{ $kindRoles->count() }}">
                                                <i class="fas {{ $kinds[$roleKind]['icon'] }} mr-1"></i>{{ $roleKind === 'common' ? 'Chung' : $kinds[$roleKind]['label'] }}
                                            </th>
                                        @endforeach
                                    </tr>
                                    <tr class="perm-role-head-row">
                                        @foreach ($roles as $role)
                                            <th class="is-{{ $role->kind }}" data-role-col="{{ $role->id }}" data-role-kind="{{ $role->kind }}"
                                                title="{{ $role->name }}{{ $role->description ? ' - ' . $role->description : '' }}">
                                                <span>{{ $role->name }}</span></th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="perm-count-row">
                                        <td title="Số quyền đã tick của từng nhóm quyền trong khối này">Số quyền đã cấp</td>
                                        @foreach ($roles as $role)
                                            <td data-role-col="{{ $role->id }}" data-count-role="{{ $role->id }}" title="{{ $role->name }}"></td>
                                        @endforeach
                                    </tr>
                                    @foreach ($model['groups'] as $groupName => $groupPermissions)
                                        @php $groupKind = $kindOfGroup[$groupName] ?? 'common'; @endphp
                                        <tr class="perm-group-row" data-kind="{{ $groupKind }}">
                                            <td colspan="{{ count($roles) + 1 }}">
                                                <i class="fas {{ $kinds[$groupKind]['icon'] }} mr-1"></i>{{ $groupName }}
                                            </td>
                                        </tr>

                                        @foreach ($groupPermissions as $permission)
                                            <tr class="permission-row {{ $permission->parent_id ? 'is-child' : '' }}" data-kind="{{ $groupKind }}">
                                                <td class="perm-name-cell" style="padding-left: {{ 8 + 18 * ($permission->depth ?? 0) }}px"
                                                    title="{{ $permission->name }}{{ !empty($permission->description) ? "\n" . $permission->description : '' }}">
                                                    <div class="permission-name">
                                                        @if ($permission->parent_id)
                                                            <i class="fas fa-level-up-alt fa-rotate-90 perm-child-icon"></i>
                                                        @endif
                                                        {{ $permission->display_name ?: $permission->name }}
                                                    </div>
                                                    <div class="permission-code">{{ $permission->name }}</div>
                                                    @if (!empty($permission->description) && str_starts_with($permission->name, 'materData_'))
                                                        <div class="permission-scope">{{ $permission->description }}</div>
                                                    @endif
                                                    @if ($canUpdateRole)
                                                        <button type="button" class="perm-row-all"
                                                            title="Tick quyền này cho tất cả nhóm quyền đang hiện (đã tick hết thì bỏ tick hết)">
                                                            <i class="fas fa-check-double"></i>
                                                        </button>
                                                    @endif
                                                </td>

                                                @foreach ($roles as $role)
                                                    <td class="perm-cell" data-role-col="{{ $role->id }}">
                                                        <input class="step-checkbox" type="checkbox"
                                                            data-role="{{ $role->id }}"
                                                            data-permission="{{ $permission->id }}"
                                                            data-parent="{{ $permission->parent_id }}"
                                                            id="checkbox-{{ $permission->id }}-{{ $role->id }}"
                                                            name="permission" @unless ($canUpdateRole) aria-readonly="true" tabindex="-1" @endunless
                                                            {{ isset($assigned[$role->id . '-' . $permission->id]) ? 'checked' : '' }}>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="perm-empty" hidden>Không có quyền nào khớp bộ lọc.</div>
                    </div>
                </div>
            @endforeach
        @endif

    </section>
    <!-- /.content -->
</div>

@perm('role_manage')
    <div class="modal fade" id="manageRoleModal" tabindex="-1" role="dialog" aria-labelledby="manageRoleLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title w-100" id="manageRoleLabel"
                        style="color: var(--primary, #2E7BC4); font-weight: 700">
                        <i class="fas fa-users-cog mr-2"></i> Quản Lý Nhóm Quyền
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    @if ($errors->roleErrors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->roleErrors->all() as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        </div>
                    @endif

                    <table class="table table-bordered role-list-table" style="font-size: 14px">
                        <thead>
                            <tr>
                                <th style="width: 50px">STT</th>
                                <th style="width: 200px">Tên Nhóm Quyền</th>
                                <th>Diễn Giải</th>
                                <th style="width: 150px">Loại Phụ Trách</th>
                                <th style="width: 130px" class="text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $role->name }}</td>
                                    <td>{{ $role->description ?? '—' }}</td>
                                    <td>
                                        @if ($role->kind === 'common')
                                            <span class="role-kind-chip is-common">Chung</span>
                                        @endif
                                        @foreach ($role->kinds as $roleKind)
                                            <span class="role-kind-chip is-{{ $roleKind }}">{{ $roleKinds[$roleKind] }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        @if ($role->id == 1)
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-lock"></i> Khoá
                                            </span>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-role"
                                                data-id="{{ $role->id }}" data-name="{{ $role->name }}"
                                                data-description="{{ $role->description }}"
                                                data-kinds="{{ implode(',', $role->kinds) }}">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <form action="{{ route('pages.user.role.deleteRole', $role->id) }}"
                                                method="POST" class="d-inline form-delete-role"
                                                data-name="{{ $role->name }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <form action="{{ route('pages.user.role.saveRole') }}" method="POST" class="role-form-box"
                        id="roleForm">
                        @csrf
                        <input type="hidden" name="id" id="roleFormId" value="{{ old('id') }}">

                        <div class="form-title" id="roleFormTitle">
                            <i class="fas fa-plus-circle mr-1"></i> Thêm Nhóm Quyền Mới
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label for="roleFormName">Tên Nhóm Quyền</label>
                                    <input type="text" class="form-control" name="name" id="roleFormName"
                                        value="{{ old('name') }}" placeholder="VD: Thủ Kho Vật Tư">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group mb-2">
                                    <label for="roleFormDescription">Diễn Giải</label>
                                    <input type="text" class="form-control" name="description"
                                        id="roleFormDescription" value="{{ old('description') }}"
                                        placeholder="Mô tả ngắn về nhóm quyền">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label class="d-block">Loại Phụ Trách</label>
                                    @php $oldKinds = old('kinds', []); @endphp
                                    @foreach ($roleKinds as $kindKey => $kindLabel)
                                        <label class="role-kind-option" title="Tick 2-3 loại thì nhóm quyền xếp vào cột Chung">
                                            <input type="checkbox" name="kinds[]" value="{{ $kindKey }}" class="role-form-kind"
                                                {{ in_array($kindKey, $oldKinds, true) ? 'checked' : '' }}>
                                            <i class="fas {{ $kinds[$kindKey]['icon'] }}"></i> {{ $kindLabel }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-secondary btn-reset-role-form d-none"
                                id="btnResetRoleForm">
                                Huỷ sửa
                            </button>
                            <button type="submit" class="btn btn-primary" id="btnSubmitRoleForm">
                                <i class="fas fa-save mr-1"></i> Lưu
                            </button>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endperm

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    </script>
@endif

<script>
    // ----- Thu gọn / mở rộng card model -----
    function setCardCollapsed($card, collapsed, save) {
        $card.toggleClass('is-collapsed', collapsed);
        $card.children('.card-body').stop(true, true)[collapsed ? 'slideUp' : 'slideDown'](150);

        if (save) {
            try {
                localStorage.setItem('permCard:' + $card.data('model'), collapsed ? '1' : '0');
            } catch (e) {}
        }
    }

    function applyStoredCollapse() {
        $('.perm-model-card').each(function() {
            var $c = $(this);
            var stored;
            try {
                stored = localStorage.getItem('permCard:' + $c.data('model'));
            } catch (e) {}

            var collapsed = stored === '1';
            $c.toggleClass('is-collapsed', collapsed);
            $c.children('.card-body').toggle(!collapsed);
        });
        refreshToggleAllLabel();
    }

    function refreshToggleAllLabel() {
        var anyOpen = $('.perm-model-card').not('.is-collapsed').length > 0;
        $('#btnToggleAllCards').html(anyOpen ?
            '<i class="fas fa-compress-alt mr-1"></i> Thu gọn tất cả' :
            '<i class="fas fa-expand-alt mr-1"></i> Mở tất cả');
    }

    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        applyStoredCollapse();

        @if ($errors->roleErrors->any())
            $('#manageRoleModal').modal('show');
        @endif
    });

    $(document).on('click', '.perm-model-header', function() {
        var $card = $(this).closest('.perm-model-card');
        setCardCollapsed($card, !$card.hasClass('is-collapsed'), true);
        refreshToggleAllLabel();
    });

    $(document).on('click', '#btnToggleAllCards', function() {
        var collapseAll = $('.perm-model-card').not('.is-collapsed').length > 0;
        $('.perm-model-card').each(function() {
            setCardCollapsed($(this), collapseAll, true);
        });
        refreshToggleAllLabel();
    });

    // ----- Ô chỉ đọc (không có quyền Phân Quyền Cho Nhóm): chặn đổi trạng thái -----
    $(document).on('click', '.step-checkbox[aria-readonly="true"]', function(e) {
        e.preventDefault();
    });

    // Trang nạp 2 bản jQuery (1.12 ở cuối trang này, bản mới ở layout nạp sau): handler 'change' bên dưới gắn bằng bản
    // cũ, còn code chạy lúc bấm dùng $ của bản mới nên .trigger('change') không tới handler -> phát sự kiện gốc
    function fireChange($boxes) {
        $boxes.each(function() {
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    // ----- Bấm giữ rồi kéo qua nhiều ô: mọi ô đi qua nhận cùng trạng thái mới của ô bắt đầu -----
    // mousedown tự đổi ô bắt đầu nên click ngay sau đó của chính checkbox bị chặn (không đổi 2 lần);
    // bấm phím cách trên ô vẫn đổi bình thường vì không có mousedown
    var permDrag = null;
    var permSuppressClick = false;

    function paintCell(td) {
        var box = $(td).find('.step-checkbox');
        if (!box.length || box.is('[aria-readonly]') || $(td).hasClass('is-role-hidden')) return;
        if (box.prop('checked') === permDrag.checked) return;
        // Nhóm Admin luôn giữ toàn quyền nên không bỏ tick
        if (!permDrag.checked && String(box.data('role')) === '1') return;
        fireChange(box.prop('checked', permDrag.checked));
    }

    $(document).on('mousedown', 'td.perm-cell', function(e) {
        var box = $(this).find('.step-checkbox');
        if (e.which !== 1 || !box.length || box.is('[aria-readonly]')) return;
        e.preventDefault();
        permDrag = { checked: !box.prop('checked') };
        permSuppressClick = $(e.target).is('.step-checkbox');
        $('body').addClass('perm-dragging');
        paintCell(this);
    });

    $(document).on('mouseenter', 'td.perm-cell', function() {
        if (permDrag) paintCell(this);
    });

    $(document).on('mouseup', function() {
        if (!permDrag) return;
        permDrag = null;
        $('body').removeClass('perm-dragging');
    });

    $(document).on('click', '.step-checkbox', function(e) {
        if (permSuppressClick) {
            e.preventDefault();
            permSuppressClick = false;
        }
    });

    // ----- Một quyền cho mọi nhóm quyền đang hiện (theo bộ lọc): chưa tick hết thì tick hết, đã tick hết thì bỏ hết -----
    $(document).on('click', '.perm-row-all', function() {
        var row = $(this).closest('tr');
        var boxes = row.find('td.perm-cell:not(.is-role-hidden) .step-checkbox').not('[aria-readonly]');
        var allOn = boxes.not(':checked').length === 0;
        // Nhóm Admin luôn giữ toàn quyền nên không bỏ tick
        var targets = allOn
            ? boxes.filter(':checked').filter(function() { return String($(this).data('role')) !== '1'; })
            : boxes.not(':checked');

        if (!targets.length) return;

        var apply = function() {
            fireChange(targets.prop('checked', !allOn));
        };

        if (!allOn) {
            apply();
            return;
        }

        Swal.fire({
            title: 'Bỏ quyền cho tất cả?',
            text: 'Bỏ quyền "' + $.trim(row.find('.permission-name').text()) + '" khỏi ' + targets.length + ' nhóm quyền đang hiện?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Bỏ tick',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: '#DC2626'
        }).then(function(result) {
            if (result.isConfirmed) apply();
        });
    });

    // ----- Báo cho trang Hướng Dẫn Sử Dụng › Phân Quyền đang mở ở tab khác tải lại bảng phân quyền -----
    function markPermissionChanged() {
        try {
            localStorage.setItem('wmsPermissionChanged', String(Date.now()));
        } catch (e) {}
    }

    @if (session('success'))
        // Vừa thêm / sửa / xoá nhóm quyền (gửi form rồi tải lại trang)
        markPermissionChanged();
    @endif

    // ----- Hàng "Số quyền đã cấp" của từng khối: đếm ô đã tick theo nhóm quyền -----
    function refreshCounts($cards) {
        ($cards || $('.perm-model-card')).each(function() {
            var card = $(this);
            card.find('[data-count-role]').each(function() {
                var count = card.find('.step-checkbox[data-role="' + $(this).data('count-role') + '"]:checked').length;
                $(this).text(count || '—');
            });
        });
    }

    $(document).ready(function() {
        refreshCounts();
    });

    $(document).on('change', '.step-checkbox', function() {
        refreshCounts($(this).closest('.perm-model-card'));
    });

    // ----- Ma trận phân quyền: bật / tắt 1 quyền cho 1 nhóm -----
    $(document).on('change', '.step-checkbox', function() {
        var input = $(this);
        var checked = input.is(':checked');

        $.ajax({
            url: "{{ route('pages.user.role.store_or_update') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                role_id: input.data('role'),
                permission_id: input.data('permission'),
                checked: checked
            },
            success: function() {
                markPermissionChanged();
                // Quyền con chỉ có hiệu lực khi có quyền cha: tick con thì tick cha, bỏ cha thì bỏ con
                var role = input.data('role');
                if (checked && input.data('parent')) {
                    var parent = $('.step-checkbox[data-role="' + role + '"][data-permission="' + input.data('parent') + '"]');
                    if (!parent.is(':checked')) fireChange(parent.prop('checked', true));
                }
                if (!checked) {
                    $('.step-checkbox[data-role="' + role + '"][data-parent="' + input.data('permission') + '"]:checked')
                        .prop('checked', false).each(function() { fireChange($(this)); });
                }
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.error) ?
                    xhr.responseJSON.error : 'Không lưu được phân quyền';

                Swal.fire({
                    title: 'Lỗi!',
                    text: message,
                    icon: 'error'
                });

                input.prop('checked', !checked);
                refreshCounts(input.closest('.perm-model-card'));
            }
        });
    });

    // ----- Tiêu đề dính: khi cuộn trang, dịch các ô tiêu đề của bảng đang cắt ngang thanh trên cùng xuống ngay dưới nó -----
    (function() {
        var pending = false;

        function syncStickyHeads() {
            pending = false;
            var nav = document.querySelector('.main-header');
            var top = nav ? nav.getBoundingClientRect().bottom : 0;

            document.querySelectorAll('.perm-table').forEach(function(table) {
                var head = table.tHead;
                if (!head || !table.offsetParent) return;
                var rect = table.getBoundingClientRect();
                var offset = Math.max(0, Math.min(top - rect.top, rect.height - head.offsetHeight));
                var value = offset > 0 ? 'translateY(' + offset + 'px)' : '';
                head.querySelectorAll('th').forEach(function(th) {
                    if (th.style.transform !== value) th.style.transform = value;
                });
            });
        }

        function schedule() {
            if (pending) return;
            pending = true;
            requestAnimationFrame(syncStickyHeads);
        }

        $(window).on('scroll resize load', schedule);
        $(document).ready(function() {
            schedule();
            // Ghim menu trái sau khi tải trang, mở / thu card, lọc cột làm đổi kích thước bảng
            if (window.ResizeObserver) {
                var observer = new ResizeObserver(schedule);
                document.querySelectorAll('.perm-table').forEach(function(el) {
                    observer.observe(el);
                });
            }
        });
    })();

    // ----- Đồng bộ cuộn ngang giữa các card -----
    (function() {
        var panes = document.querySelectorAll('.perm-table-scroll');
        var lock = false;

        panes.forEach(function(pane) {
            pane.addEventListener('scroll', function() {
                if (lock) return;
                lock = true;
                panes.forEach(function(other) {
                    if (other !== pane) other.scrollLeft = pane.scrollLeft;
                });
                lock = false;
            });
        });
    })();

    // ----- Bộ lọc: tên quyền + loại (Vật Tư / Hoá Chất / Chất Chuẩn / Dùng Chung) + nhóm quyền -----
    var permFilter = { kind: '', roles: [], onlyGranted: false };
    var roleKindOf = @json($roles->pluck('kind', 'id'));
    var roleKindsOf = @json($roles->pluck('kinds', 'id'));

    try {
        var storedFilter = JSON.parse(localStorage.getItem('permRoleFilter.v2') || 'null');
        if (storedFilter) $.extend(permFilter, storedFilter);
    } catch (e) {}

    function saveFilter() {
        try {
            localStorage.setItem('permRoleFilter.v2', JSON.stringify(permFilter));
        } catch (e) {}
    }

    function applyFilters() {
        var keyword = $('#rolePermissionSearch').val().toLowerCase();
        var searching = keyword !== '';
        var roles = permFilter.roles;
        var onlyGranted = permFilter.onlyGranted && roles.length > 0;
        var narrowing = searching || permFilter.kind !== '' || onlyGranted;

        // Cột nhóm quyền: chỉ giữ các nhóm đang chọn (không chọn = tất cả)
        $('.perm-table [data-role-col]').each(function() {
            var cell = $(this);
            var hidden = roles.length > 0 && roles.indexOf(String(cell.data('role-col'))) === -1;
            // Lọc theo loại: giữ cột nhóm quyền có phụ trách loại đó (nhóm Chung phụ trách cả 3 nên luôn giữ);
            // nút Dùng Chung chỉ giữ cột Chung
            if (!hidden && permFilter.kind !== '') {
                var roleId = cell.data('role-col');
                hidden = permFilter.kind === 'common'
                    ? roleKindOf[roleId] !== 'common'
                    : (roleKindsOf[roleId] || []).indexOf(permFilter.kind) === -1;
            }
            cell.toggleClass('is-role-hidden', hidden);
        });

        // Ô loại trên tiêu đề co theo số cột nhóm quyền còn hiện
        $('.perm-table thead').each(function() {
            var head = $(this);
            head.find('th[data-head-kind]').each(function() {
                var visible = head.find('th[data-role-kind="' + $(this).data('head-kind') + '"]').not('.is-role-hidden').length;
                $(this).attr('colspan', Math.max(visible, 1)).toggleClass('is-role-hidden', visible === 0);
            });
        });

        $('.perm-model-card').each(function() {
            var card = $(this);
            var hasMatch = false;

            card.find('tr.permission-row').each(function() {
                var row = $(this);
                var show = (permFilter.kind === '' || row.data('kind') === permFilter.kind)
                    && row.find('td:first').text().toLowerCase().indexOf(keyword) !== -1;

                if (show && onlyGranted) {
                    show = row.find('td.perm-cell:not(.is-role-hidden) .step-checkbox:checked').length > 0;
                }

                row.toggle(show);
                if (show) hasMatch = true;
            });

            card.find('tr.perm-group-row').each(function() {
                // Xét display của chính dòng (không dùng :visible): card đang thu gọn thì mọi dòng đều "không hiện"
                $(this).toggle($(this).nextUntil('.perm-group-row', 'tr.permission-row').filter(function() {
                    return this.style.display !== 'none';
                }).length > 0);
            });

            // Khi đang tìm theo tên: mở tạm card có kết quả (không ghi nhớ)
            if (searching) {
                card.removeClass('is-collapsed');
                card.children('.card-body').toggle(hasMatch);
            }

            card.find('.perm-table-scroll').toggle(hasMatch);
            card.find('.perm-empty').prop('hidden', hasMatch || !narrowing);
            card.toggle(hasMatch || !narrowing);
        });
    }

    // permFilter.roles rỗng = tất cả nhóm quyền (kể cả nhóm tạo sau), ô tick hiện đủ;
    // syncChecks = false khi người dùng đang tick dở trong menu (vd. vừa Bỏ chọn) để không tick lại hết
    function refreshFilterControls(syncChecks) {
        $('.perm-kind-filter .btn').each(function() {
            $(this).toggleClass('active', String($(this).data('kind')) === permFilter.kind);
        });

        if (syncChecks !== false) {
            $('.role-filter-item').each(function() {
                this.checked = permFilter.roles.length === 0 || permFilter.roles.indexOf(this.value) !== -1;
            });
        }

        var names = $('.role-filter-item').filter(function() {
            return permFilter.roles.indexOf(this.value) !== -1;
        }).map(function() {
            return $.trim($(this).parent().text());
        }).get();
        $('#roleFilterLabel').text(names.length === 0 ? 'Tất cả nhóm quyền' :
            (names.length <= 2 ? names.join(', ') : names.length + ' nhóm quyền'));
        $('.perm-role-filter').toggleClass('is-filtered', names.length > 0);

        $('#onlyGranted').prop('disabled', names.length === 0).prop('checked', permFilter.onlyGranted && names.length > 0);
        $('.perm-only-granted').toggleClass('is-disabled', names.length === 0);
    }

    function onFilterChanged(syncChecks) {
        saveFilter();
        refreshFilterControls(syncChecks);
        if (!$('#rolePermissionSearch').val()) applyStoredCollapse();
        applyFilters();
    }

    $(document).ready(function() {
        // Bỏ nhóm quyền đã nhớ nhưng nay không còn
        var roleIds = $('.role-filter-item').map(function() { return this.value; }).get();
        permFilter.roles = $.grep(permFilter.roles, function(id) { return roleIds.indexOf(id) !== -1; });
        refreshFilterControls();
        applyFilters();
    });

    $(document).on('keyup', '#rolePermissionSearch', function() {
        if (!$(this).val()) applyStoredCollapse();
        applyFilters();
    });

    $(document).on('click', '.perm-kind-filter .btn', function() {
        permFilter.kind = String($(this).data('kind'));
        onFilterChanged();
    });

    $(document).on('click', '#btnRoleFilter', function(e) {
        e.stopPropagation();
        $('#roleFilterMenu').prop('hidden', !$('#roleFilterMenu').prop('hidden'));
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.perm-role-filter').length) $('#roleFilterMenu').prop('hidden', true);
    });

    $(document).on('change', '.role-filter-item', function() {
        var checked = $('.role-filter-item:checked').map(function() { return this.value; }).get();
        // Tick đủ hoặc bỏ hết = không lọc, nhóm quyền tạo sau vẫn hiện
        permFilter.roles = checked.length === $('.role-filter-item').length ? [] : checked;
        onFilterChanged(checked.length > 0);
    });

    $(document).on('click', '#roleFilterAll, #roleFilterNone', function(e) {
        e.preventDefault();
        permFilter.roles = [];
        if (this.id === 'roleFilterNone') {
            $('.role-filter-item').prop('checked', false);
        }
        onFilterChanged(this.id === 'roleFilterAll');
    });

    $(document).on('change', '#onlyGranted', function() {
        permFilter.onlyGranted = this.checked;
        onFilterChanged();
    });

    // ----- Quản lý nhóm quyền: nạp dữ liệu 1 role vào form để sửa -----
    $(document).on('click', '.btn-edit-role', function() {
        var btn = $(this);
        $('#roleFormId').val(btn.data('id'));
        $('#roleFormName').val(btn.data('name'));
        $('#roleFormDescription').val(btn.data('description'));
        var roleKinds = String(btn.data('kinds') || '').split(',');
        $('.role-form-kind').each(function() {
            this.checked = roleKinds.indexOf(this.value) !== -1;
        });
        $('#roleFormTitle').html('<i class="fas fa-pen mr-1"></i> Đang Sửa: ' + btn.data('name'));
        $('#btnResetRoleForm').removeClass('d-none');
        $('#roleFormName').focus();
    });

    $(document).on('click', '#btnResetRoleForm', function() {
        $('#roleForm')[0].reset();
        $('.role-form-kind').prop('checked', false);
        $('#roleFormId').val('');
        $('#roleFormTitle').html('<i class="fas fa-plus-circle mr-1"></i> Thêm Nhóm Quyền Mới');
        $(this).addClass('d-none');
    });

    // ----- Xoá nhóm quyền: xác nhận trước khi gửi -----
    $(document).on('submit', '.form-delete-role', function(e) {
        e.preventDefault();
        var form = this;

        Swal.fire({
            title: 'Xoá nhóm quyền?',
            text: 'Bạn chắc chắn muốn xoá nhóm quyền "' + $(form).data('name') + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xoá',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: '#DC2626'
        }).then(function(result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
