<style>
    .routing-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .routing-stat {
        background: #fff;
        border-radius: 10px;
        padding: 15px 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 15px;
        border-left: 4px solid #94a3b8;
    }

    .routing-stat.in-progress { border-left-color: #3b82f6; }
    .routing-stat.waiting     { border-left-color: #f59e0b; }
    .routing-stat.completed   { border-left-color: #10b981; }

    .routing-stat i {
        font-size: 1.8rem;
        opacity: 0.8;
    }

    .routing-stat.in-progress i { color: #3b82f6; }
    .routing-stat.waiting i     { color: #f59e0b; }
    .routing-stat.completed i   { color: #10b981; }

    .routing-stat .value {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1;
        color: #1e293b;
    }

    .routing-stat .label {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Timeline đường đi */
    .routing-timeline {
        list-style: none;
        margin: 0;
        padding: 10px 0 0 8px;
        border-left: 2px dashed #cbd5e1;
    }

    .routing-timeline li {
        position: relative;
        padding: 0 0 14px 22px;
        font-size: 0.85rem;
        color: #475569;
    }

    .routing-timeline li:last-child { padding-bottom: 0; }

    .routing-timeline li::before {
        content: '';
        position: absolute;
        left: -7px;
        top: 4px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #10b981;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #10b981;
    }

    .routing-timeline li.pending::before {
        background: #f59e0b;
        box-shadow: 0 0 0 2px #f59e0b;
    }

    .routing-timeline .who {
        font-weight: 600;
        color: #1e293b;
    }

    .routing-timeline .meta {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .routing-timeline .note {
        font-size: 0.78rem;
        font-style: italic;
        color: #64748b;
    }

    .step-badge {
        display: inline-block;
        min-width: 22px;
        height: 22px;
        line-height: 22px;
        text-align: center;
        border-radius: 50%;
        background: #e2e8f0;
        color: #334155;
        font-size: 0.72rem;
        font-weight: 700;
        margin-right: 6px;
    }

    .holder-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .holder-chip.done {
        background: #ecfdf5;
        color: #047857;
    }

    .table td { vertical-align: middle; }
</style>

<div class="content-wrapper">
    <div class="card mt-5">
        <div class="card-body">

            <div class="routing-stats">
                <div class="routing-stat in-progress">
                    <i class="fas fa-route"></i>
                    <div>
                        <div class="value">{{ $inProgressCount }}</div>
                        <div class="label">Đang luân chuyển</div>
                    </div>
                </div>
                <div class="routing-stat waiting">
                    <i class="fas fa-hourglass-half"></i>
                    <div>
                        <div class="value">{{ $waitingCount }}</div>
                        <div class="label">Chờ xác nhận nhận</div>
                    </div>
                </div>
                <div class="routing-stat completed">
                    <i class="fas fa-archive"></i>
                    <div>
                        <div class="value">{{ $completedCount }}</div>
                        <div class="label">Đã lưu trữ</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-success" data-toggle="modal" data-target="#createRoutingModal">
                    <i class="fas fa-plus"></i> Khởi tạo hồ sơ (Văn thư)
                </button>

                <div class="btn-group btn-group-sm" role="group" id="routing-filter">
                    <button type="button" class="btn btn-outline-secondary active" data-filter="all">Tất cả</button>
                    <button type="button" class="btn btn-outline-primary" data-filter="in_progress">Đang luân chuyển</button>
                    <button type="button" class="btn btn-outline-success" data-filter="completed">Đã lưu trữ</button>
                </div>
            </div>

            <table id="data_table_routing" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th style="width: 30px"></th>
                        <th>Mã hồ sơ</th>
                        <th>Tên tài liệu</th>
                        <th>Người tiếp nhận</th>
                        <th>Ngày nhận</th>
                        <th>Đang ở</th>
                        <th>Chặng</th>
                        <th>Trạng thái</th>
                        <th>Vị trí lưu</th>
                        <th style="width: 170px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr data-status="{{ $data->status }}">
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-outline-info btn-toggle-timeline"
                                    data-target-row="timeline-{{ $data->id }}" title="Xem đường đi">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </td>
                            <td><span class="font-weight-bold text-primary">{{ $data->code }}</span></td>
                            <td>{{ $data->name }}</td>
                            <td>{{ $data->receiver_name }}</td>
                            <td>{{ $data->received_date ? \Carbon\Carbon::parse($data->received_date)->format('d/m/Y') : '-' }}</td>
                            <td>
                                @if ($data->status === 'completed')
                                    <span class="holder-chip done"><i class="fas fa-archive"></i> Đã lưu kho</span>
                                @elseif ($data->status === 'cancelled')
                                    <span class="text-muted">-</span>
                                @else
                                    <span class="holder-chip"><i class="fas fa-user"></i> {{ $data->current_holder_name }}</span>
                                    @if ($data->waiting_confirm)
                                        <br><small class="text-warning font-weight-bold">
                                            <i class="fas fa-hourglass-half"></i> chờ xác nhận
                                        </small>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">{{ $data->steps->count() }}</td>
                            <td class="text-center">
                                @if ($data->status === 'completed')
                                    <span class="badge badge-success">Đã kết thúc</span>
                                @elseif ($data->status === 'cancelled')
                                    <span class="badge badge-secondary">Đã huỷ</span>
                                @else
                                    <span class="badge badge-primary">Đang luân chuyển</span>
                                @endif
                            </td>
                            <td>
                                @if ($data->final_location_id)
                                    <small>
                                        {{ $data->warehouse_name }} / {{ $data->room_name }} /
                                        {{ $data->shelf_name }} / <b>{{ $data->location_name }}</b>
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($data->status === 'in_progress')
                                    @if ($data->waiting_confirm)
                                        <form class="d-inline"
                                            action="{{ route('pages.documentStorage.routing.confirmReceive') }}"
                                            method="POST">
                                            @csrf
                                            <input type="hidden" name="step_id" value="{{ $data->last_step_id }}">
                                            <button type="submit" class="btn btn-sm btn-warning" title="Xác nhận đã nhận">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-info btn-forward"
                                        data-id="{{ $data->id }}" data-code="{{ $data->code }}"
                                        data-name="{{ $data->name }}"
                                        data-holder="{{ $data->current_holder_name }}"
                                        data-holder-id="{{ $data->current_holder_user_id }}"
                                        data-toggle="modal" data-target="#forwardRoutingModal"
                                        title="Chuyển tiếp (Bước 2)">
                                        <i class="fas fa-share"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success btn-finish"
                                        data-id="{{ $data->id }}" data-code="{{ $data->code }}"
                                        data-name="{{ $data->name }}"
                                        data-toggle="modal" data-target="#finishRoutingModal"
                                        title="Kết thúc & Lưu trữ (Bước 3)">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                    <form class="d-inline form-cancel"
                                        action="{{ route('pages.documentStorage.routing.cancel') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $data->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Huỷ phiếu">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">
                                        {{ $data->completed_at ? \Carbon\Carbon::parse($data->completed_at)->format('d/m/Y H:i') : '' }}
                                    </span>
                                @endif
                            </td>
                        </tr>

                        <tr class="d-none timeline-row" id="timeline-{{ $data->id }}"
                            data-status="{{ $data->status }}">
                            <td colspan="10" class="bg-light">
                                <b class="text-uppercase small text-muted">Đường đi của hồ sơ {{ $data->code }}</b>
                                <ul class="routing-timeline">
                                    @foreach ($data->steps as $step)
                                        <li class="{{ $step->status === 'pending' ? 'pending' : '' }}">
                                            <span class="step-badge">{{ $step->step_no }}</span>
                                            <span class="who">
                                                {{ $step->from_user_name ?: 'Tiếp nhận từ bộ phận khác' }}
                                            </span>
                                            <i class="fas fa-long-arrow-alt-right mx-1 text-muted"></i>
                                            <span class="who">{{ $step->to_user_name }}</span>
                                            @if ($step->status === 'pending')
                                                <span class="badge badge-warning ml-1">Chờ xác nhận</span>
                                            @else
                                                <span class="badge badge-success ml-1">Đã nhận</span>
                                            @endif
                                            <div class="meta">
                                                Ngày giao:
                                                {{ $step->handover_date ? \Carbon\Carbon::parse($step->handover_date)->format('d/m/Y') : '-' }}
                                                &nbsp;|&nbsp; Ngày nhận:
                                                {{ $step->received_date ? \Carbon\Carbon::parse($step->received_date)->format('d/m/Y') : 'chưa nhận' }}
                                            </div>
                                            @if ($step->note)
                                                <div class="note"><i class="fas fa-comment-dots"></i> {{ $step->note }}</div>
                                            @endif
                                        </li>
                                    @endforeach

                                    @if ($data->status === 'completed')
                                        <li>
                                            <span class="step-badge"><i class="fas fa-flag-checkered"></i></span>
                                            <span class="who">Kết thúc - Lưu trữ</span>
                                            <span class="badge badge-success ml-1">Hoàn tất</span>
                                            <div class="meta">
                                                Vị trí: {{ $data->warehouse_name }} / {{ $data->room_name }} /
                                                {{ $data->shelf_name }} / <b>{{ $data->location_name }}</b>
                                                &nbsp;|&nbsp; Bởi: {{ $data->completed_by }}
                                                lúc
                                                {{ $data->completed_at ? \Carbon\Carbon::parse($data->completed_at)->format('d/m/Y H:i') : '' }}
                                            </div>
                                        </li>
                                    @endif
                                </ul>
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
            timer: 1800,
            showConfirmButton: false
        });
    </script>
@endif

@if (session('error'))
    <script>
        Swal.fire({
            title: 'Không thực hiện được',
            text: '{{ session('error') }}',
            icon: 'error'
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        // Không dùng DataTable ở đây vì mỗi hồ sơ chiếm 2 <tr> (dòng dữ liệu + dòng timeline)
        // nên phân trang/sắp xếp của DataTable sẽ tách rời chúng. Lọc bằng tay bên dưới.

        // Select2 trong modal cần dropdownParent là chính modal đó, nếu không ô tìm kiếm
        // sẽ không focus được (Bootstrap giữ focus trong modal).
        $('#forwardRoutingModal .select2').select2({
            theme: 'bootstrap4',
            dropdownParent: $('#forwardRoutingModal')
        });

        // Bung / thu timeline
        $(document).on('click', '.btn-toggle-timeline', function() {
            const row = $('#' + $(this).data('target-row'));
            row.toggleClass('d-none');
            $(this).find('i').toggleClass('fa-plus fa-minus');
        });

        // Lọc theo trạng thái
        $('#routing-filter button').on('click', function() {
            const filter = $(this).data('filter');
            $('#routing-filter button').removeClass('active');
            $(this).addClass('active');

            $('#data_table_routing tbody tr').each(function() {
                const $tr = $(this);
                const match = (filter === 'all' || $tr.data('status') === filter);

                if ($tr.hasClass('timeline-row')) {
                    // Timeline luôn thu lại khi đổi bộ lọc
                    $tr.addClass('d-none');
                } else {
                    $tr.toggleClass('d-none', !match);
                }
            });
            $('.btn-toggle-timeline i').removeClass('fa-minus').addClass('fa-plus');
        });

        // Modal Bước 2 - Chuyển tiếp
        $(document).on('click', '.btn-forward', function() {
            const b = $(this);
            const modal = $('#forwardRoutingModal');
            modal.find('#forward_routing_id').val(b.data('id'));
            modal.find('#forward_doc_label').text(b.data('code') + ' - ' + b.data('name'));
            modal.find('#forward_current_holder').text(b.data('holder'));

            // Không cho chọn lại chính người đang giữ tài liệu.
            // select2 đọc thuộc tính disabled của <option> khi dựng dropdown nên chỉ cần prop().
            const holderId = String(b.data('holder-id'));
            const select = modal.find('#forward_to_user_id');
            select.find('option').prop('disabled', false);
            select.find('option[value="' + holderId + '"]').prop('disabled', true);
            select.val('').trigger('change');
        });

        // Modal Bước 3 - Kết thúc
        $(document).on('click', '.btn-finish', function() {
            const b = $(this);
            const modal = $('#finishRoutingModal');
            modal.find('#finish_routing_id').val(b.data('id'));
            modal.find('#finish_doc_label').text(b.data('code') + ' - ' + b.data('name'));
        });

        // Xác nhận trước khi huỷ
        $(document).on('submit', '.form-cancel', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Huỷ phiếu luân chuyển?',
                text: 'Phiếu sẽ được đánh dấu đã huỷ và không thể chuyển tiếp nữa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Huỷ phiếu',
                cancelButtonText: 'Đóng',
                confirmButtonColor: '#dc3545'
            }).then((r) => {
                if (r.isConfirmed) form.submit();
            });
        });
    });
</script>
