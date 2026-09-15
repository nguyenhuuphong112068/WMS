<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    .obj-freq {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: .8rem;
        font-weight: 600;
        color: var(--primary);
        background: var(--primary-soft);
        white-space: nowrap;
    }

    .obj-type,
    .obj-source {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 10px;
        font-size: .8rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .obj-type {
        color: #fff;
        background: var(--primary);
    }

    .obj-type-utility_equipment {
        background: var(--primary-dark);
    }

    .obj-type-testing_equipment {
        background: var(--accent);
    }

    .obj-source-cal {
        color: var(--primary-dark);
        background: var(--primary-soft);
        border: 1px solid var(--primary-lighter);
        cursor: help;
    }

    .obj-source-manual {
        color: var(--text-main);
        background: var(--bg-neutral);
        border: 1px solid var(--primary-lighter);
    }

    .obj-toolbar {
        gap: 8px;
    }

    /* Hai bộ lọc luôn nằm cùng một hàng; màn hình hẹp mới xuống dòng */
    .obj-filters {
        display: flex;
        flex-wrap: nowrap;
        gap: 8px;
    }

    .obj-filters .obj-filter {
        width: auto;
        min-width: 210px;
        border-radius: var(--border-radius-md, 8px);
        transition: border-color .2s, box-shadow .2s;
    }

    @media (max-width: 767.98px) {
        .obj-filters {
            flex-wrap: wrap;
            width: 100%;
        }

        .obj-filters .obj-filter {
            flex: 1 1 200px;
        }
    }
</style>
<div class="content-wrapper">
    <div class="card">

        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-2 obj-toolbar">
                @perm('materData_common_create')
                    <button class="btn btn-success btn-create" data-toggle="modal" data-target="#createModal"
                        style="width: 155px">
                        <i class="fas fa-plus"></i> Thêm mới
                    </button>
                @endperm

                @perm('materData_common_update')
                    <form id="form-sync" action="{{ route('pages.materData.consumptionObject.sync') }}" method="POST"
                        class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Đồng bộ từ phần mềm CAL
                        </button>
                    </form>
                @endperm

                <div class="ml-md-auto obj-filters">
                    <select id="filter_type" class="form-control obj-filter" title="Lọc theo loại đối tượng">
                        <option value="">Tất cả loại đối tượng</option>
                        @foreach ($types as $typeLabel)
                            <option value="{{ $typeLabel }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                    <select id="filter_source" class="form-control obj-filter" title="Lọc theo nguồn dữ liệu">
                        <option value="">Tất cả nguồn dữ liệu</option>
                        @foreach ($sources as $sourceLabel)
                            <option value="{{ $sourceLabel }}">{{ $sourceLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @php($frequencyOrder = array_flip(array_keys($frequencies)))

            <table id="data_table_consumption_object" class="table table-bordered table-striped">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Loại Đối Tượng</th>
                        <th>Nguồn</th>
                        <th>Mã Đối Tượng</th>
                        <th>Tên Đối Tượng</th>
                        <th>Vị Trí</th>
                        <th>Tần Suất</th>
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
                            <td data-search="{{ $types[$data->type] ?? $data->type }}">
                                <span class="obj-type obj-type-{{ $data->type }}">{{ $types[$data->type] ?? $data->type }}</span>
                            </td>
                            <td data-search="{{ $sources[$data->source] ?? $data->source }}">
                                @if ($data->source === 'cal')
                                    <span class="obj-source obj-source-cal"
                                        title="{{ $data->cal_connection }} · Inst_Master_{{ $data->cal_table_suffix }} · ID {{ $data->cal_record_id }} · Inst_id {{ $data->cal_inst_id }}{{ $data->cal_synced_at ? ' · đồng bộ ' . \Carbon\Carbon::parse($data->cal_synced_at)->format('d/m/Y H:i') : '' }}">
                                        <i class="fas fa-link"></i> CAL {{ $calBlocks[$data->cal_connection] ?? '' }}
                                    </span>
                                @else
                                    <span class="obj-source obj-source-manual">
                                        <i class="fas fa-user-edit"></i> Người dùng
                                    </span>
                                @endif
                            </td>
                            <td>{{ $data->code }}</td>
                            <td>{{ $data->name }}</td>
                            <td>{{ $data->location ?? '-' }}</td>
                            <td data-order="{{ $frequencyOrder[$data->frequency] ?? 99 }}">
                                <span class="obj-freq" title="{{ $data->frequency }}">{{ $frequencies[$data->frequency] ?? $data->frequency }}</span>
                            </td>
                            <td class="text-center">
                                @if ($data->status_id == 1)
                                    <span class="badge badge-success">Hoạt động</span>
                                @else
                                    <span class="badge badge-danger">Tạm ngưng</span>
                                @endif
                            </td>
                            <td>{{ $data->created_by ?? '-' }}</td>
                            <td>{{ $data->created_at ? \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="text-center align-middle">
                                <span class="md-btn-wrap">
                                    @perm('materData_common_update')
                                        <button type="button" class="btn btn-warning btn-edit mb-1"
                                            data-id="{{ $data->id }}" data-source="{{ $data->source }}"
                                            data-type="{{ $data->type }}" data-code="{{ $data->code }}"
                                            data-name="{{ $data->name }}" data-location="{{ $data->location }}"
                                            data-frequency="{{ $data->frequency }}" data-toggle="modal"
                                            data-target="#updateModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endperm

                                    {{-- Badge số lần thay đổi, bấm vào để xem lịch sử --}}
                                    @include('pages.materData.shared.historyBadge', [
                                        'count' => $historyCounts[$data->id] ?? 0,
                                        'url' => route('pages.materData.consumptionObject.history', [
                                            'id' => $data->id,
                                        ]),
                                        'title' => $data->code . ' - ' . $data->name . ' (' . ($frequencies[$data->frequency] ?? $data->frequency) . ')',
                                    ])
                                </span>

                                @perm('materData_common_deActive')
                                    <form class="form-deActive d-inline"
                                        action="{{ route('pages.materData.consumptionObject.deActive') }}"
                                        method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $data->id }}">
                                        <button type="submit"
                                            class="btn btn-{{ $data->status_id == 1 ? 'danger' : 'success' }} btn-deactive-confirm"
                                            data-name="{{ $data->code }} - {{ $data->name }} ({{ $frequencies[$data->frequency] ?? $data->frequency }})"
                                            data-active="{{ $data->status_id }}">
                                            <i class="fas fa-{{ $data->status_id == 1 ? 'lock' : 'unlock' }}"></i>
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
            text: @json(session('success')),
            icon: 'success',
            timer: 2500,
            showConfirmButton: false
        });
    </script>
@endif

@if (session('error'))
    <script>
        Swal.fire({
            title: 'Không thực hiện được',
            text: @json(session('error')),
            icon: 'error'
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#updateModal');
            // Đối tượng đồng bộ từ CAL: loại + mã + tần suất là khoá nhận diện, không cho sửa
            const fromCal = button.data('source') === 'cal';

            modal.find('#update_id').val(button.data('id'));
            modal.find('#update_type').val(button.data('type')).prop('disabled', fromCal);
            modal.find('#update_code').val(button.data('code')).prop('readonly', fromCal);
            modal.find('#update_frequency').val(button.data('frequency')).prop('disabled', fromCal);
            modal.find('#update_cal_lock').toggleClass('d-none', !fromCal);
            modal.find('#update_name').val(button.data('name'));
            modal.find('#update_location').val(button.data('location'));
            modal.find('[name="change_reason"]').val('');
        });

        $('#form-sync').on('submit', function(e) {
            e.preventDefault();
            const form = this;

            Swal.fire({
                title: 'Đồng bộ từ phần mềm CAL?',
                text: 'Lấy thiết bị sản xuất và thiết bị kiểm nghiệm từ cal1, cal2: mỗi tần suất một dòng, thêm dòng mới, cập nhật dòng đã có nếu thông tin khác.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng bộ',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Đang đồng bộ...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    form.submit();
                }
            });
        });

        $('.form-deActive').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const name = $(form).find('button').data('name');
            const active = $(form).find('button').data('active') == 1;
            const actionText = active ? 'vô hiệu hóa' : 'kích hoạt';

            Swal.fire({
                title: `Xác nhận ${actionText}?`,
                text: `Bạn có chắc chắn muốn ${actionText} đối tượng: ${name}?`,
                icon: 'warning',
                input: 'textarea',
                inputLabel: 'Lý do điều chỉnh',
                inputPlaceholder: 'Nêu rõ lý do khoá / mở khoá bản ghi này',
                inputAttributes: {
                    maxlength: '500'
                },
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy',
                preConfirm: (reason) => {
                    if (!reason || !reason.trim()) {
                        Swal.showValidationMessage('Vui lòng nhập lý do điều chỉnh');
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $(form).find('input[name="change_reason"]').remove();
                    const rs = document.createElement('input');
                    rs.type = 'hidden';
                    rs.name = 'change_reason';
                    rs.value = result.value || '';
                    form.appendChild(rs);
                    form.submit();
                }
            });
        });

        const table = $('#data_table_consumption_object').DataTable({
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
                infoFiltered: "(lọc từ _MAX_ dòng)",
                zeroRecords: "Không tìm thấy dữ liệu phù hợp",
                emptyTable: "Chưa có đối tượng nào",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            }
        });

        // Lọc theo loại (cột 1) / nguồn dữ liệu (cột 2) - so khớp đúng nguyên nhãn
        const bindFilter = (selector, column) => {
            $(selector).on('change', function() {
                const value = this.value;
                table.column(column)
                    .search(value ? '^' + $.fn.dataTable.util.escapeRegex(value) + '$' : '', true, false)
                    .draw();
            });
        };

        bindFilter('#filter_type', 1);
        bindFilter('#filter_source', 2);
    });
</script>
