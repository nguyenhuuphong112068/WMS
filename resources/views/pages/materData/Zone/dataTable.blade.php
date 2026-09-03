<style>
    .zone-page {
        padding: 24px 24px 40px;
    }

    /* ---------- Đầu trang ---------- */
    .zone-hero {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        border-radius: var(--border-radius-lg);
        color: #fff;
        padding: 22px 26px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-md);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .zone-hero h1 {
        color: #fff;
        font-size: 1.4rem;
        margin: 0 0 6px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .zone-hero p {
        margin: 0;
        font-size: 0.88rem;
        opacity: 0.9;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Sơ đồ phân cấp Kho -> Phòng -> Kệ -> Vị trí */
    .zone-flow {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .zone-flow .step {
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .zone-flow .sep {
        opacity: 0.7;
        font-size: 0.75rem;
    }

    /* ---------- Thanh tab ---------- */
    .zone-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 16px 20px 0;
        border-bottom: 1px solid var(--primary-soft);
        list-style: none;
        margin: 0;
    }

    .zone-tabs .nav-link {
        border: 1px solid transparent;
        border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
        padding: 10px 18px;
        color: #64748b;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all var(--transition-fast);
    }

    .zone-tabs .nav-link:hover {
        background: var(--primary-soft);
        color: var(--primary-dark);
    }

    .zone-tabs .nav-link.active {
        background: linear-gradient(135deg, var(--primary-light), var(--primary));
        color: #fff;
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.28);
    }

    .zone-tabs .nav-link .count {
        background: rgba(var(--primary-rgb), 0.12);
        color: var(--primary-dark);
        border-radius: 999px;
        padding: 1px 9px;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .zone-tabs .nav-link.active .count {
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
    }

    /* ---------- Thanh công cụ trong từng tab ---------- */
    .zone-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .zone-toolbar .hint {
        color: #94a3b8;
        font-size: 0.83rem;
        margin: 0;
    }

    /* ---------- Bảng ---------- */
    .zone-code {
        display: inline-block;
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
        border-radius: 6px;
        padding: 2px 9px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .zone-parent {
        color: #475569;
        font-size: 0.87rem;
    }

    .zone-parent i {
        color: var(--primary-lighter);
        margin-right: 5px;
    }

    .zone-empty {
        color: #cbd5e1;
    }

    .zone-actions {
        display: flex;
        justify-content: center;
        gap: 6px;
    }

    .zone-actions .btn {
        border-radius: var(--border-radius-md);
        padding: 4px 10px;
        transition: all var(--transition-fast);
    }

    .zone-actions .btn:hover {
        transform: translateY(-1px);
    }

    table.dataTable tbody td {
        vertical-align: middle;
    }

    /* ---------- Modal ---------- */
    .zone-modal .modal-content {
        border: none;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .zone-modal .modal-header {
        background: var(--primary-soft);
        border-bottom: 1px solid var(--primary-lighter);
        padding: 16px 22px;
    }

    .zone-modal .modal-title {
        color: var(--primary-dark);
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .zone-modal .modal-body {
        padding: 22px;
    }

    .zone-modal .modal-footer {
        border-top: 1px solid var(--primary-soft);
        padding: 14px 22px;
    }

    .zone-modal label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #475569;
        margin-bottom: 5px;
    }

    .zone-modal .form-control {
        border-radius: var(--border-radius-md);
        border: 1px solid #dbe6f2;
        padding: 9px 12px;
    }

    .zone-modal .form-control.is-invalid {
        border-color: #DC2626;
    }

    .zone-modal .zone-error {
        color: #DC2626;
        font-size: 0.8rem;
        margin-top: 5px;
        display: block;
    }

    .zone-modal .zone-dept {
        background: var(--primary-soft);
        border-radius: var(--border-radius-md);
        padding: 9px 12px;
        font-size: 0.87rem;
        color: var(--primary-dark);
        border: 1px dashed var(--primary-lighter);
    }
</style>

<div class="content-wrapper">
    <div class="zone-page">

        <div class="zone-hero">
            <div>
                <h1><i class="fas fa-sitemap"></i> Định Khu</h1>
                <p>Khai báo và quản lý toàn bộ cấu trúc lưu trữ của phòng ban
                    <b>{{ session('user')['selected_department'] }}</b>
                </p>
            </div>
            <div class="zone-flow">
                @foreach ($zoneMeta as $key => $meta)
                    @if (!$loop->first)
                        <span class="sep"><i class="fas fa-chevron-right"></i></span>
                    @endif
                    <span class="step"><i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}</span>
                @endforeach
            </div>
        </div>

        <div class="card">
            <ul class="nav zone-tabs" id="zoneTabs" role="tablist">
                @foreach ($zoneMeta as $key => $meta)
                    <li class="nav-item">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="pill"
                            data-tab="{{ $key }}" href="#zonePane-{{ $key }}" role="tab">
                            <i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                            <span class="count">{{ $meta['rows']->count() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="card-body">
                <div class="tab-content">
                    @foreach ($zoneMeta as $key => $meta)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                            id="zonePane-{{ $key }}" role="tabpanel">

                            <div class="zone-toolbar">
                                @if ($meta['canCreate'] && user_can('materData_create'))
                                    <button type="button" class="btn btn-primary btn-zone-create"
                                        data-type="{{ $key }}">
                                        <i class="fas fa-plus mr-1"></i> Thêm {{ $meta['label'] }}
                                    </button>
                                @else
                                    <button type="button" class="btn btn-primary" disabled>
                                        <i class="fas fa-plus mr-1"></i> Thêm {{ $meta['label'] }}
                                    </button>
                                @endif
                                <p class="hint">
                                    @if ($meta['canCreate'])
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Tổng {{ $meta['rows']->count() }} {{ $meta['lower'] }},
                                        đang hoạt động {{ $meta['rows']->where('status_id', 1)->count() }}.
                                    @else
                                        <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                                        {{ $meta['blockMsg'] }}
                                    @endif
                                </p>
                            </div>

                            <div class="table-responsive">
                                <table id="zoneTable-{{ $key }}" class="table table-bordered table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 60px">STT</th>
                                            <th style="width: 130px">Mã</th>
                                            @if ($meta['hasName'] ?? true)
                                                <th>Tên {{ $meta['label'] }}</th>
                                            @endif
                                            @foreach ($meta['cols'] as $title)
                                                <th>{{ $title }}</th>
                                            @endforeach
                                            <th style="width: 140px">Người Tạo</th>
                                            <th class="text-center" style="width: 110px">Ngày Tạo</th>
                                            <th class="text-center" style="width: 110px">Trạng Thái</th>
                                            <th class="text-center" style="width: 150px">Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($meta['rows'] as $row)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td><span class="zone-code">{{ $row->code }}</span></td>
                                                @if ($meta['hasName'] ?? true)
                                                    <td class="font-weight-bold">{{ $row->name }}</td>
                                                @endif
                                                @foreach ($meta['cols'] as $field => $title)
                                                    <td class="zone-parent">
                                                        @if (!empty($row->$field))
                                                            <i class="fas fa-angle-right"></i>{{ $row->$field }}
                                                        @else
                                                            <span class="zone-empty">Chưa gán</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td>{{ $row->created_by ?: '—' }}</td>
                                                <td class="text-center">
                                                    {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '—' }}
                                                </td>
                                                <td class="text-center">
                                                    @if ($row->status_id == 1)
                                                        <span class="badge badge-success">Hoạt động</span>
                                                    @else
                                                        <span class="badge badge-danger">Đã khoá</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="zone-actions">
                                                        <span class="md-btn-wrap">
                                                            @perm('materData_update')
                                                                <button type="button" class="btn btn-sm btn-warning btn-zone-edit"
                                                                    title="Sửa"
                                                                    data-type="{{ $key }}"
                                                                    data-id="{{ $row->id }}"
                                                                    data-code="{{ $row->code }}"
                                                                    data-name="{{ ($meta['hasName'] ?? true) ? $row->name : '' }}"
                                                                    data-warehouse="{{ $row->warehouse_id ?? '' }}"
                                                                    data-room="{{ $row->room_id ?? '' }}"
                                                                    data-shelf="{{ $row->shelf_id ?? '' }}"
                                                                    data-item-type="{{ $row->item_type ?? '' }}">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                            @endperm

                                                            {{-- Badge số lần thay đổi, bấm vào để xem lịch sử --}}
                                                            @include('pages.materData.shared.historyBadge', [
                                                                'count' => $historyCounts[$meta['table'] . '-' . $row->id] ?? 0,
                                                                'url' => route($zoneRoute . 'history', ['type' => $key, 'id' => $row->id]),
                                                                'title' => trim($meta['label'] . ' ' . $row->code . (($meta['hasName'] ?? true) ? ' - ' . $row->name : '')),
                                                            ])
                                                        </span>

                                                        @perm('materData_deActive')
                                                            <form class="form-zone-confirm d-inline"
                                                                action="{{ route($zoneRoute . 'deActive', $key) }}"
                                                                method="POST"
                                                                data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $meta['lower'] }}?"
                                                                data-text="{{ $row->status_id == 1 ? 'Sau khi khoá' : 'Sau khi mở khoá' }}, {{ $meta['lower'] }} &quot;{{ $zoneCaption($meta, $row) }}&quot; {{ $row->status_id == 1 ? 'sẽ không còn được chọn ở các cấp bên dưới.' : 'sẽ được dùng lại bình thường.' }}"
                                                                data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                                                @csrf
                                                                <input type="hidden" name="id" value="{{ $row->id }}">
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'success' }}"
                                                                    title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                                    <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                                                                </button>
                                                            </form>
                                                        @endperm

                                                        @perm('materData_deActive')
                                                            <form class="form-zone-confirm d-inline"
                                                                action="{{ route($zoneRoute . 'destroy', $key) }}"
                                                                method="POST"
                                                                data-title="Xoá {{ $meta['lower'] }}?"
                                                                data-text="{{ $meta['label'] }} &quot;{{ $zoneCaption($meta, $row) }}&quot; sẽ bị xoá vĩnh viễn khỏi hệ thống."
                                                                data-danger="1">
                                                                @csrf
                                                                <input type="hidden" name="id" value="{{ $row->id }}">
                                                                <button type="submit" class="btn btn-sm btn-danger" title="Xoá">
                                                                    <i class="fas fa-trash-alt"></i>
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
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    // Dữ liệu cấp cha để đổ động cho các ô chọn trong modal (Kho -> Phòng -> Kệ).
    var zoneData = @json($zoneCascade);

    var zoneTypes = @json(array_keys($zoneMeta));
    var zoneStorageKey = 'wms.zone.activeTab.material';
    var zoneFlash = {
        activeTab: @json(session('activeTab')),
        formTab: @json(session('formTab')),
        old: @json($zoneOld),
        success: @json(session('success')),
        error: @json(session('error')),
    };

    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Đổ dữ liệu cho ô chọn cấp cha ---------- */
        function optionsFor(level, parentId) {
            return (zoneData[level] || []).filter(function(item) {
                if (item.status_id !== 1) return false;
                if (level === 'warehouse') return true;
                if (!parentId) return true;
                return String(item.parent) === String(parentId);
            });
        }

        function fillSelect($select, level, parentId, selected) {
            if (!$select.length) return;

            $select.empty().append(new Option($select.data('placeholder') || '-- Chọn --', ''));

            optionsFor(level, parentId).forEach(function(item) {
                $select.append(new Option(item.code + ' — ' + item.name, item.id));
            });

            $select.val(selected ? String(selected) : '');
            if ($select.val() === null) $select.val('');
        }

        // Đồng bộ cả chuỗi Kho -> Phòng -> Kệ trong một form.
        function syncForm($form, selected) {
            selected = selected || {};

            var $warehouse = $form.find('.sel-warehouse');
            var $room = $form.find('.sel-room');
            var $shelf = $form.find('.sel-shelf');

            fillSelect($warehouse, 'warehouse', null, selected.warehouse_id);
            fillSelect($room, 'room', $warehouse.val(), selected.room_id);
            fillSelect($shelf, 'shelf', $room.val(), selected.shelf_id);
        }

        $(document).on('change', '.sel-warehouse', function() {
            var $form = $(this).closest('form');
            fillSelect($form.find('.sel-room'), 'room', $(this).val(), null);
            fillSelect($form.find('.sel-shelf'), 'shelf', $form.find('.sel-room').val(), null);
        });

        $(document).on('change', '.sel-room', function() {
            fillSelect($(this).closest('form').find('.sel-shelf'), 'shelf', $(this).val(), null);
        });

        /* ---------- Mở modal Thêm mới / Cập nhật ---------- */
        $(document).on('click', '.btn-zone-create', function() {
            var type = $(this).data('type');
            var $modal = $('#createModal-' + type);
            var $form = $modal.find('form');

            $form[0].reset();
            $form.find('.zone-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');
            syncForm($form, {});
            $modal.modal('show');
        });

        $(document).on('click', '.btn-zone-edit', function() {
            var row = $(this).data();
            var $modal = $('#updateModal-' + row.type);
            var $form = $modal.find('form');

            $form.find('.zone-error').remove();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.inp-id').val(row.id);
            $form.find('.inp-code').val(row.code);
            $form.find('.inp-name').val(row.name);
            syncForm($form, {
                warehouse_id: row.warehouse,
                room_id: row.room,
                shelf_id: row.shelf
            });
            $modal.modal('show');
        });

        /* ---------- Bảng dữ liệu ---------- */
        var dtOptions = {
            autoWidth: false,
            responsive: true,
            pageLength: 25,
            order: [
                [1, 'asc']
            ],
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'Tất cả']
            ],
            columnDefs: [{
                orderable: false,
                targets: -1
            }],
            language: {
                search: 'Tìm kiếm:',
                lengthMenu: 'Hiển thị _MENU_ dòng',
                info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
                infoEmpty: 'Không có dữ liệu',
                zeroRecords: 'Không tìm thấy dòng nào phù hợp',
                emptyTable: 'Chưa có dữ liệu, hãy bấm "Thêm" để khai báo.',
                paginate: {
                    previous: 'Trước',
                    next: 'Sau'
                }
            }
        };

        zoneTypes.forEach(function(type) {
            $('#zoneTable-' + type).DataTable(dtOptions);
        });

        /* ---------- Ghi nhớ tab đang xem ---------- */
        function showTab(type) {
            var $link = $('#zoneTabs a[data-tab="' + type + '"]');
            if ($link.length) $link.tab('show');
        }

        $('#zoneTabs a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
            var type = $(e.target).data('tab');
            try {
                localStorage.setItem(zoneStorageKey, type);
            } catch (err) {
                /* trình duyệt chặn localStorage thì bỏ qua */
            }
            $('#zoneTable-' + type).DataTable().columns.adjust().responsive.recalc();
        });

        var savedTab = null;
        try {
            savedTab = localStorage.getItem(zoneStorageKey);
        } catch (err) {
            savedTab = null;
        }
        showTab(zoneFlash.activeTab || savedTab || zoneTypes[0]);

        /* ---------- Mở lại modal khi validate lỗi ---------- */
        if (zoneFlash.formTab) {
            var parts = zoneFlash.formTab.split('-'); // vd: "warehouse-create"
            var $reopen = $('#' + parts[1] + 'Modal-' + parts[0]);
            var $reopenForm = $reopen.find('form');
            var old = zoneFlash.old || {};

            $reopenForm.find('.inp-id').val(old.id || '');
            $reopenForm.find('.inp-code').val(old.code || '');
            $reopenForm.find('.inp-name').val(old.name || '');
            syncForm($reopenForm, {
                warehouse_id: old.warehouse_id,
                room_id: old.room_id,
                shelf_id: old.shelf_id
            });

            showTab(parts[0]);
            $reopen.modal('show');
        }

        /* ---------- Xác nhận Khoá / Mở khoá / Xoá ---------- */
        $(document).on('submit', '.form-zone-confirm', function(e) {
            e.preventDefault();
            var form = this;

            Swal.fire({
                title: $(form).data('title'),
                text: $(form).data('text'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: $(form).data('danger') ? '#DC2626' : '#2E7BC4',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Huỷ'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });

        /* ---------- Thông báo kết quả ---------- */
        if (zoneFlash.success) {
            Swal.fire({
                title: 'Thành công!',
                text: zoneFlash.success,
                icon: 'success',
                timer: 1800,
                showConfirmButton: false
            });
        }

        if (zoneFlash.error) {
            Swal.fire({
                title: 'Không thực hiện được',
                text: zoneFlash.error,
                icon: 'error',
                confirmButtonColor: '#2E7BC4',
                confirmButtonText: 'Đã hiểu'
            });
        }
    });
</script>
