<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">

        <div class="card-body">
            @perm('materData_create')
                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#createModal"
                    style="width: 155px">
                    <i class="fas fa-plus"></i> Thêm mới
                </button>
            @endperm

            <table id="data_table_department" class="table table-bordered table-striped">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Tên Viết Tắt</th>
                        <th>Tên Phòng Ban</th>
                        <th>Trạng Thái</th>
                        <th>Người Tạo</th>
                        <th>Ngày Tạo</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->shortName }}</td>
                            <td>{{ $data->name }}</td>
                            <td class="text-center">
                                @if ($data->isActive)
                                    <span class="badge badge-success">Hoạt động</span>
                                @else
                                    <span class="badge badge-danger">Tạm ngưng</span>
                                @endif
                            </td>
                            <td>{{ $data->prepareBy ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                            <td class="text-center align-middle">
                                <span class="md-btn-wrap">
                                    @perm('materData_update')
                                        <button type="button" class="btn btn-warning btn-edit mb-1"
                                            data-id="{{ $data->id }}" data-shortname="{{ $data->shortName }}"
                                            data-name="{{ $data->name }}" data-toggle="modal" data-target="#updateModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endperm

                                    {{-- Badge số lần thay đổi, bấm vào để xem lịch sử --}}
                                    @include('pages.materData.shared.historyBadge', [
                                        'count' => $historyCounts[$data->id] ?? 0,
                                        'url' => route('pages.materData.department.history', ['id' => $data->id]),
                                        'title' => $data->name,
                                    ])
                                </span>

                                @perm('materData_deActive')
                                    <form class="form-deActive d-inline"
                                        action="{{ route('pages.materData.department.deActive') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $data->id }}">
                                        <input type="hidden" name="active" value="{{ $data->isActive }}">
                                        <button type="submit"
                                            class="btn btn-{{ $data->isActive ? 'danger' : 'success' }} btn-deactive-confirm"
                                            data-name="{{ $data->name }}" data-active="{{ $data->isActive }}">
                                            <i class="fas fa-{{ $data->isActive ? 'lock' : 'unlock' }}"></i>
                                        </button>
                                    </form>
                                @endperm
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
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
            timer: 1500,
            showConfirmButton: false
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#updateModal');

            modal.find('#update_id').val(button.data('id'));
            modal.find('#update_shortName').val(button.data('shortname'));
            modal.find('#update_name').val(button.data('name'));
        });

        $('.form-deActive').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const name = $(form).find('button').data('name');
            const active = $(form).find('button').data('active');
            const actionText = active ? 'vô hiệu hóa' : 'kích hoạt';

            Swal.fire({
                title: `Xác nhận ${actionText}?`,
                text: `Bạn có chắc chắn muốn ${actionText} phòng ban: ${name}?`,
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

        $('#data_table_department').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            }
        });
    });
</script>
