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

    /* ===== Mỗi model = 1 card ===== */
    .perm-model-card {
        border: none;
        border-radius: var(--border-radius-lg, 14px);
        box-shadow: 0 4px 16px rgba(46, 123, 196, .10);
        overflow: hidden;
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
    /* Mỗi card có vùng cuộn riêng (ngang + dọc); tiêu đề role dính khi cuộn dọc */
    .perm-table-scroll {
        overflow: auto;
        max-height: 68vh;
    }

    .perm-table {
        width: max-content;
        min-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: 14.5px;
    }

    .perm-table col.perm-col-name {
        width: 340px;
    }

    .perm-table col.perm-col-role {
        width: 128px;
    }

    .perm-table th,
    .perm-table td {
        padding: 11px 16px;
        border-bottom: 1px solid #EEF3F8;
    }

    .perm-table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #fff;
        color: var(--primary, #2E7BC4);
        font-weight: 700;
        box-shadow: inset 0 -2px 0 var(--primary-lighter, #9CC7EE);
    }

    .perm-table thead th + th {
        text-align: center;
        font-size: 12px;
        line-height: 1.25;
        padding: 10px 6px;
        vertical-align: middle;
    }

    /* Ghim cột "Quyền" khi cuộn ngang; ô góc trên-trái ghim cả hai chiều */
    .perm-table th:first-child,
    .perm-table td:first-child {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 2;
    }

    .perm-table thead th:first-child {
        z-index: 4;
    }

    .perm-table tbody tr.permission-row:hover td {
        background: var(--primary-soft, #EAF3FC);
    }

    .perm-group-row td {
        position: sticky;
        left: 0;
        z-index: 1;
        background: var(--primary-soft, #EAF3FC);
        color: var(--primary, #2E7BC4);
        font-weight: 700;
        font-size: 12.5px;
        letter-spacing: .8px;
        text-transform: uppercase;
        padding: 8px 16px;
    }

    .perm-name-cell .permission-name {
        color: var(--text-main, #2D3748);
    }

    .perm-name-cell .permission-code {
        font-size: 11.5px;
        color: #9AA7B4;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }

    .perm-name-cell .permission-scope {
        margin-top: 3px;
        font-size: 11.5px;
        line-height: 1.35;
        color: #64748B;
    }

    .perm-cell {
        text-align: center;
        vertical-align: middle;
    }

    .step-checkbox {
        width: 19px;
        height: 19px;
        cursor: pointer;
        accent-color: var(--primary, #2E7BC4);
        transition: box-shadow .2s ease, transform .1s ease;
    }

    .step-checkbox:checked {
        box-shadow: 0 0 5px var(--primary, #2E7BC4);
    }

    .step-checkbox:active {
        transform: scale(.9);
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
                <button type="button" class="btn btn-outline-primary ml-auto" id="btnToggleAllCards"
                    style="border-radius: var(--border-radius-md, 8px); font-weight: 600">
                    <i class="fas fa-compress-alt mr-1"></i> Thu gọn tất cả
                </button>
            </div>
        </div>

        @if (empty($tree))
            <div class="card perm-model-card">
                <div class="card-body">
                    <div class="alert alert-warning mb-0">
                        Chưa có quyền nào trong hệ thống. Chạy <code>php artisan migrate</code> để nạp danh sách quyền.
                    </div>
                </div>
            </div>
        @else
            {{-- Mỗi model một card riêng --}}
            @foreach ($tree as $modelKey => $model)
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
                                        <col class="perm-col-role">
                                    @endforeach
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Quyền</th>
                                        @foreach ($roles as $role)
                                            <th>{{ $role->name }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($model['groups'] as $groupName => $groupPermissions)
                                        <tr class="perm-group-row">
                                            <td colspan="{{ count($roles) + 1 }}">{{ $groupName }}</td>
                                        </tr>

                                        @foreach ($groupPermissions as $permission)
                                            <tr class="permission-row">
                                                <td class="perm-name-cell">
                                                    <div class="permission-name">
                                                        {{ $permission->display_name ?: $permission->name }}
                                                    </div>
                                                    <div class="permission-code">{{ $permission->name }}</div>
                                                    @if (!empty($permission->description) && str_starts_with($permission->name, 'materData_'))
                                                        <div class="permission-scope">{{ $permission->description }}</div>
                                                    @endif
                                                </td>

                                                @foreach ($roles as $role)
                                                    <td class="perm-cell">
                                                        <input class="step-checkbox" type="checkbox"
                                                            data-role="{{ $role->id }}"
                                                            data-permission="{{ $permission->id }}"
                                                            id="checkbox-{{ $permission->id }}-{{ $role->id }}"
                                                            name="permission" {{ user_can('role_update', 'disabled') }}
                                                            {{ isset($assigned[$role->id . '-' . $permission->id]) ? 'checked' : '' }}>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="perm-empty" hidden>Không có quyền nào khớp từ khoá tìm kiếm.</div>
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
                                <th style="width: 130px" class="text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $role->name }}</td>
                                    <td>{{ $role->description ?? '—' }}</td>
                                    <td class="text-center">
                                        @if ($role->id == 1)
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-lock"></i> Khoá
                                            </span>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-role"
                                                data-id="{{ $role->id }}" data-name="{{ $role->name }}"
                                                data-description="{{ $role->description }}">
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
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.error) ?
                    xhr.responseJSON.error : 'Không lưu được phân quyền';

                Swal.fire({
                    title: 'Lỗi!',
                    text: message,
                    icon: 'error'
                });

                input.prop('checked', !checked);
            }
        });
    });

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

    // ----- Lọc theo tên quyền: ẩn dòng / băng nhóm / cả card không còn kết quả -----
    $(document).on('keyup', '#rolePermissionSearch', function() {
        var keyword = $(this).val().toLowerCase();
        var searching = keyword !== '';

        if (!searching) {
            applyStoredCollapse();
        }

        $('.perm-model-card').each(function() {
            var card = $(this);
            var hasMatch = false;

            card.find('tr.permission-row').each(function() {
                var name = $(this).find('td:first').text().toLowerCase();
                var show = name.indexOf(keyword) !== -1;
                $(this).toggle(show);
                if (show) hasMatch = true;
            });

            card.find('tr.perm-group-row').each(function() {
                $(this).toggle($(this).nextUntil('.perm-group-row', 'tr.permission-row:visible').length > 0);
            });

            // Khi đang tìm: mở tạm card có kết quả (không ghi nhớ), ẩn card không khớp
            if (searching) {
                card.removeClass('is-collapsed');
                card.children('.card-body').toggle(hasMatch);
            }

            card.find('.perm-table-scroll').toggle(hasMatch);
            card.find('.perm-empty').prop('hidden', hasMatch || !searching);
            card.toggle(hasMatch || !searching);
        });
    });

    // ----- Quản lý nhóm quyền: nạp dữ liệu 1 role vào form để sửa -----
    $(document).on('click', '.btn-edit-role', function() {
        var btn = $(this);
        $('#roleFormId').val(btn.data('id'));
        $('#roleFormName').val(btn.data('name'));
        $('#roleFormDescription').val(btn.data('description'));
        $('#roleFormTitle').html('<i class="fas fa-pen mr-1"></i> Đang Sửa: ' + btn.data('name'));
        $('#btnResetRoleForm').removeClass('d-none');
        $('#roleFormName').focus();
    });

    $(document).on('click', '#btnResetRoleForm', function() {
        $('#roleForm')[0].reset();
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
