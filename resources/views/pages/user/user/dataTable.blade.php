<div class="content-wrapper">
    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}

        </div>

        <!-- /.card-Body -->
        <div class="card-body">

            @perm('user_create')
                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#createModal"
                    style="width: 155px">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            @endperm

            <table id="example1" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th>STT</th>
                        <th>Tên Đăng Nhập</th>
                        <th>Nhóm Người Dùng</th>
                        <th>Tên Người Dùng</th>
                        <th>Phòng Ban</th>
                        <th>Công Ty</th>
                        <th>Mail</th>
                        <th>Người Tạo</th>
                        <th>Ngày Tạo</th>
                        <th>Quyền Riêng</th>
                        <th>Edit</th>
                        <th>DeActive</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->userName }}</td>
                            <td>{{ $data->role_names }}</td>
                            <td>{{ $data->fullName }}</td>
                            <td>{{ $data->deparment }}</td>
                            <td>{{ $data->company_short ?? $data->company_name }}</td>
                            <td>{{ $data->mail }}</td>
                            <td>{{ $data->prepareBy }}</td>
                            <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>

                            <td class="text-center align-middle">
                                @perm('userPermission_manage')
                                    <button type="button" class="btn btn-primary btn-user-permission"
                                        title="Cấp quyền riêng ngoài nhóm quyền" data-id="{{ $data->id }}"
                                        data-fullname="{{ $data->fullName }}" data-toggle="modal"
                                        data-target="#UserPermissionModal">
                                        <i class="fas fa-user-shield"></i>
                                    </button>
                                @endperm
                            </td>

                            <td class="text-center align-middle">
                                @perm('user_update')
                                    <button type="button" class="btn btn-warning btn-edit" data-id="{{ $data->id }}"
                                        data-username="{{ $data->userName }}" data-usergroup='@json($data->role_ids)'
                                        data-fullname="{{ $data->fullName }}" data-deparment="{{ $data->deparment }}"
                                        data-group_ids='@json($data->group_ids)'
                                        data-mail="{{ $data->mail }}"
                                        data-toggle="modal" data-target="#UpdateModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endperm
                            </td>


                            <td class="text-center align-middle">

                                @perm('user_deActive')
                                    <form class="form-deActive"
                                        action="{{ route('pages.user.user.deActive', ['id' => $data->id]) }}"
                                        method="post">
                                        @csrf
                                        <button type="submit" class="btn btn-danger" data-name="{{ $data->userName }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endperm

                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
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
            timer: 2000, // tự đóng sau 2 giây
            showConfirmButton: false
        });
    </script>
@endif

<script>
    // Danh sách tổ kèm phòng ban - để lọc tổ theo phòng ban đã chọn
    window.ALL_GROUPS = @json($groups);

    // Dựng lại danh sách tổ theo phòng ban đang chọn trong 1 modal.
    // Không chọn phòng ban -> khoá ô chọn tổ.
    function buildGroupOptions(modal, presetIds) {
        var $dept = modal.find('select[name="deparment"]');
        var $grp = modal.find('select[name="group_id[]"]');
        var deptId = $dept.find('option:selected').data('id');

        // Giá trị cần giữ lại: ưu tiên preset truyền vào, sau đó là lựa chọn hiện tại,
        // cuối cùng là data-preset (dùng khi form bị lỗi validate và mở lại).
        var keep = presetIds;
        if (!keep) {
            keep = $grp.val();
        }
        if ((!keep || !keep.length) && $grp.data('preset') && $grp.data('preset').length) {
            keep = $grp.data('preset');
            $grp.removeData('preset').removeAttr('data-preset');
        }
        keep = (keep || []).map(String);

        $grp.empty();

        if (!deptId) {
            $grp.prop('disabled', true).trigger('change');
            return;
        }

        $grp.prop('disabled', false);
        (window.ALL_GROUPS || []).forEach(function(g) {
            if (String(g.department_id) !== String(deptId)) return;
            var selected = keep.indexOf(String(g.id)) !== -1;
            $grp.append(new Option(g.name, g.id, selected, selected));
        });
        $grp.trigger('change');
    }

    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        // Khởi tạo Select2 cho các modal
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
        $('.select2-group').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // Lọc lại danh sách tổ mỗi khi đổi phòng ban
        $(document).on('change', '#createModal select[name="deparment"], #UpdateModal select[name="deparment"]',
            function() {
                buildGroupOptions($(this).closest('.modal'));
            });

        // Mở modal: dựng lại danh sách tổ theo phòng ban (giữ old input khi lỗi validate)
        $('#createModal, #UpdateModal').on('shown.bs.modal', function() {
            buildGroupOptions($(this));
        });

        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#UpdateModal');
            // Gán dữ liệu vào input
            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="userName"]').val(button.data('username'));

            // Gán mảng role IDs và trigger change cho Select2
            var roleIds = button.data('usergroup');
            modal.find('select[name="userGroup[]"]').val(roleIds).trigger('change');

            modal.find('input[name="fullName"]').val(button.data('fullname'));
            modal.find('select[name="deparment"]').val(button.data('deparment'));
            modal.find('input[name="mail"]').val(button.data('mail'));

            // Tổ: dựng theo phòng ban của user rồi chọn sẵn các tổ hiện có
            var groupIds = (button.data('group_ids') || []).map(String);
            buildGroupOptions(modal, groupIds);
        });

        $('.form-deActive').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const name = $(form).find('button[type="submit"]').data('name');


            Swal.fire({
                title: 'Bạn chắc chắn muốn vô hiệu?',
                text: `User: ${name}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Toggle Password Visibility
        $(document).on('click', '.toggle-password', function() {
            var target = $(this).data('target');
            var input = $(target);
            var icon = $(this).find('i');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

    });
</script>
