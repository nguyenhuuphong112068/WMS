{{--
| Tab "Đơn dự trù đã được phê duyệt" ở màn Dự Trù Vật Tư / Hoá Chất / Chất Chuẩn - chỉ hiện với
| bộ phận mua hàng (quyền estimate_<loại>_purchasing + phòng đang chọn là phòng mua hàng).
|
| Mọi phiếu đã duyệt xong của tất cả phòng ban, mọi công ty. Cột Tiếp Nhận: nút Tiếp nhận phiếu
| vừa duyệt (lưu người + ngày tiếp nhận), phiếu chờ tiếp nhận lên đầu. Nút chi tiết mở trang phiếu ở chế
| độ chỉ đọc khi phiếu thuộc phòng khác (xem EstimateItemCancel::canViewAsPurchaser()).
|
| Biến vào: $lists (EstimateItemCancel::approvedLists()), $signRows, $itemCounts, $itemLabel, $estRoute
--}}
@php
    $apStatusLabels = [
        'waiting' => 'Chưa tiếp nhận',
        'progress' => 'Đang thực hiện',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Huỷ',
    ];
    $apDepartments = $lists
        ->mapWithKeys(fn ($list) => [$list->department_id => trim($list->department_short_name.($list->company_short_name ? ' - '.$list->company_short_name : ''))])
        ->sort();
@endphp

<div class="ap-filter-bar">
    <div class="ap-filter">
        <label for="apFilterDept"><i class="fas fa-building mr-1"></i>Bộ phận</label>
        <select id="apFilterDept" class="form-control form-control-sm">
            <option value="">Tất cả bộ phận</option>
            @foreach ($apDepartments as $deptId => $deptLabel)
                <option value="{{ $deptId }}">{{ $deptLabel }}</option>
            @endforeach
        </select>
    </div>
    <div class="ap-filter">
        <label for="apFilterStatus"><i class="fas fa-filter mr-1"></i>Tình trạng</label>
        <select id="apFilterStatus" class="form-control form-control-sm">
            <option value="">Tất cả tình trạng</option>
            @foreach ($apStatusLabels as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="table-responsive">
    <table id="approvedListTable" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th class="text-center" style="width: 55px">STT</th>
                <th style="width: 150px">Bộ Phận</th>
                <th style="width: 140px">Mã Phiếu</th>
                <th class="text-center" style="width: 100px">Kỳ Dự Trù</th>
                <th class="text-center" style="width: 95px">{{ $itemLabel }}</th>
                <th class="text-center" style="width: 110px">Ngày Duyệt</th>
                <th class="text-center" style="width: 150px">Tình Trạng</th>
                <th style="width: 330px">Theo Dõi Trình Ký</th>
                <th style="width: 130px">Người Lập</th>
                <th class="text-center" style="width: 160px">Tiếp Nhận</th>
                <th class="text-center" style="width: 110px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $row)
                @php
                    $apSigns = $signRows->get($row->id, collect());
                    $apApprovedAt = $apSigns->max('signed_at');
                @endphp
                <tr data-dept="{{ $row->department_id }}" data-status="{{ $row->approved_status['key'] }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <span class="est-code">{{ $row->department_short_name }}</span>
                        @if ($row->company_short_name)
                            <div class="md-sub">{{ $row->company_short_name }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="est-code">{{ $row->code }}</span>
                        @if ($row->pending_cancel_count)
                            <div class="mt-1">
                                <span class="badge badge-danger" title="Phòng đề nghị xin huỷ - mở chi tiết phiếu để xác nhận">
                                    <i class="fas fa-ban mr-1"></i>{{ $row->pending_cancel_count }} mục chờ xác nhận huỷ
                                </span>
                            </div>
                        @endif
                        @if ($row->note)
                            <div class="md-sub">
                                <span class="md-note" title="{{ $row->note }}">{{ $row->note }}</span>
                            </div>
                        @endif
                    </td>
                    <td class="text-center" data-order="{{ $row->year }}{{ str_pad($row->month, 2, '0', STR_PAD_LEFT) }}">
                        <span class="est-period">{{ str_pad($row->month, 2, '0', STR_PAD_LEFT) }}/{{ $row->year }}</span>
                    </td>
                    <td class="text-center">
                        <span class="md-tag">{{ $itemCounts[$row->id] ?? 0 }}</span>
                    </td>
                    <td class="text-center" data-order="{{ $apApprovedAt }}">
                        {{ $apApprovedAt ? \Carbon\Carbon::parse($apApprovedAt)->format('d/m/Y') : '—' }}
                    </td>
                    @php
                        $apStatus = $row->approved_status;
                        $apStatusOrder = ['waiting' => 0, 'progress' => 1, 'completed' => 2, 'cancelled' => 3][$apStatus['key']];
                    @endphp
                    <td class="text-center" data-order="{{ $apStatusOrder }}">
                        @include('pages.estimate.shared.approvedStatusBadge', ['apStatus' => $apStatus])
                    </td>
                    <td>
                        @include('pages.estimate.shared.signFlow', ['row' => $row, 'signs' => $apSigns])
                    </td>
                    <td class="md-sub">
                        {{ $row->created_by ?: '—' }}
                        <br><small>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '' }}</small>
                    </td>
                    <td class="text-center" data-order="{{ $row->reception_status === 'waiting' ? 0 : 1 }}">
                        @include('pages.estimate.shared.receptionAction', ['list' => $row, 'raCompact' => true])
                    </td>
                    <td>
                        <div class="md-actions">
                            <a href="{{ route($estRoute . 'detail', ['id' => $row->id, 'from' => 'approved']) }}" class="btn btn-sm btn-primary"
                                title="Xem chi tiết phiếu">
                                <i class="fas fa-list-ul"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-info btn-est-history"
                                title="Theo dõi trình ký"
                                data-url="{{ route($estRoute . 'history', ['id' => $row->id]) }}"
                                data-title="Phiếu {{ $row->code }}">
                                <i class="fas fa-route"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!$.fn.DataTable || $.fn.DataTable.isDataTable('#approvedListTable')) return;

        // Bộ lọc Bộ phận / Tình trạng - chỉ áp cho bảng này
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'approvedListTable') return true;

            var row = settings.aoData[dataIndex].nTr;
            var dept = $('#apFilterDept').val();
            var status = $('#apFilterStatus').val();

            return (!dept || row.getAttribute('data-dept') === dept) &&
                (!status || row.getAttribute('data-status') === status);
        });

        var apTable = $('#approvedListTable').DataTable({
            order: [], // giữ thứ tự server: phiếu có mục chờ xác nhận huỷ lên đầu
            pageLength: 25,
            columnDefs: [{ orderable: false, targets: [0, 7, 10] }],
            language: {
                search: 'Tìm kiếm:',
                lengthMenu: 'Hiển thị _MENU_ dòng',
                info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
                infoEmpty: 'Không có dữ liệu',
                zeroRecords: 'Không tìm thấy dữ liệu phù hợp',
                emptyTable: 'Chưa có đơn dự trù nào được phê duyệt',
                paginate: { previous: 'Trước', next: 'Sau' }
            }
        });

        $('#apFilterDept, #apFilterStatus').on('change', function() {
            $(this).toggleClass('is-active', !!$(this).val());
            apTable.draw();
        });
    });
</script>

<style>
    /* ----- Bộ lọc ----- */
    .ap-filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 12px;
    }

    .ap-filter {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ap-filter label {
        margin: 0;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--primary);
        white-space: nowrap;
    }

    .ap-filter select {
        min-width: 200px;
        border-radius: var(--border-radius-md, 8px);
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .ap-filter select.is-active {
        border-color: var(--primary);
        background-color: var(--primary-soft);
        font-weight: 600;
    }

</style>
