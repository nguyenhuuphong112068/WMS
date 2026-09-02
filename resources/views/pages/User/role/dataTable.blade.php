<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<style>
    .role-permission-card {
        border-radius: var(--border-radius-lg, 12px);
        box-shadow: var(--shadow-sm, 0 2px 6px rgba(0, 0, 0, .06));
        border: none;
    }

    .role-permission-title {
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 1px;
        color: var(--primary, #2E7BC4);
        margin: 0;
    }

    #data_table_permission thead th {
        background-color: var(--primary-soft, #EAF3FC);
        color: var(--primary, #2E7BC4);
        font-weight: 700;
        vertical-align: middle;
        border-bottom: 2px solid var(--primary-lighter, #9CC7EE);
    }

    #data_table_permission tbody tr:hover {
        background-color: var(--primary-soft, #EAF3FC);
    }

    #data_table_permission .permission-group-row td {
        background-color: var(--primary, #2E7BC4);
        color: #fff;
        font-weight: 700;
        letter-spacing: .5px;
    }

    #data_table_permission .permission-name {
        color: var(--text-main, #2D3748);
    }

    #data_table_permission .permission-code {
        font-size: 12px;
        color: #94A3B8;
    }

    .step-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: var(--primary, #2E7BC4);
        transition: box-shadow .2s ease;
    }

    .step-checkbox:checked {
        box-shadow: 0 0 5px var(--primary, #2E7BC4);
    }

    .role-search {
        max-width: 320px;
        border-radius: var(--border-radius-md, 8px);
    }
</style>

<div class="content-wrapper">
    <section class="content">
        <div class="card role-permission-card mt-3">

            <div class="card-header d-flex flex-wrap align-items-center justify-content-between"
                style="background-color: #fff; border-bottom: 1px solid var(--primary-soft, #EAF3FC); padding: 20px">
                <h3 class="role-permission-title">
                    <i class="fas fa-user-shield mr-2"></i> Phân Quyền Theo Nhóm
                </h3>
                <input type="text" class="form-control role-search" id="rolePermissionSearch"
                    placeholder="Tìm quyền theo tên...">
            </div>

            <div class="card-body" style="padding: 20px">

                @if ($permissions->isEmpty())
                    <div class="alert alert-warning mb-0">
                        Chưa có quyền nào trong hệ thống. Chạy <code>php artisan migrate</code> để nạp danh sách quyền.
                    </div>
                @else
                    <div style="overflow-x: auto">
                        <table id="data_table_permission" class="table table-bordered" style="font-size: 15px">
                            <thead style="position: sticky; top: 60px; z-index: 1020">
                                <tr>
                                    <th style="min-width: 320px">Quyền</th>
                                    @foreach ($roles as $role)
                                        <th class="text-center" style="min-width: 110px">{{ $role->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissions as $groupName => $groupPermissions)
                                    <tr class="permission-group-row">
                                        <td colspan="{{ count($roles) + 1 }}">
                                            {{ $groupName ?: 'Khác' }}
                                        </td>
                                    </tr>

                                    @foreach ($groupPermissions as $permission)
                                        <tr class="permission-row">
                                            <td>
                                                <div class="permission-name">
                                                    {{ $permission->display_name ?: $permission->name }}
                                                </div>
                                                <div class="permission-code">{{ $permission->name }}</div>
                                            </td>

                                            @foreach ($roles as $role)
                                                <td class="align-middle">
                                                    <div class="form-check form-switch text-center">
                                                        <input class="form-check-input step-checkbox" type="checkbox"
                                                            role="switch" data-role="{{ $role->id }}"
                                                            data-permission="{{ $permission->id }}"
                                                            id="checkbox-{{ $permission->id }}-{{ $role->id }}"
                                                            name="permission" {{ user_can('role_update', 'disabled') }}
                                                            {{ isset($assigned[$role->id . '-' . $permission->id]) ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </section>
    <!-- /.content -->
</div>

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
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
    });

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

                // Trả checkbox về trạng thái trước đó
                input.prop('checked', !checked);
            }
        });
    });

    // Lọc theo tên quyền, ẩn luôn tiêu đề nhóm không còn dòng nào
    $(document).on('keyup', '#rolePermissionSearch', function() {
        var keyword = $(this).val().toLowerCase();

        $('#data_table_permission tbody tr.permission-row').each(function() {
            var name = $(this).find('td:first').text().toLowerCase();
            $(this).toggle(name.indexOf(keyword) !== -1);
        });

        $('#data_table_permission tbody tr.permission-group-row').each(function() {
            $(this).toggle($(this).nextUntil('.permission-group-row', '.permission-row:visible').length > 0);
        });
    });
</script>
