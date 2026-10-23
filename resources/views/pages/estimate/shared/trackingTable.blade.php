@php
    // Định dạng số lượng: bỏ số 0 thừa ở phần thập phân (giống shared/detail.blade.php).
    // Bảng này được @include từ dataTable nên phải tự khai, không kế thừa từ trang chi tiết.
    $estNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    // Quyền cập nhật theo dõi giao hàng suy ra từ $estRoute (vật tư / hoá chất / chất chuẩn).
    $estTrackPerm = str_contains($estRoute, 'materialEstimate')
        ? 'estimate_material_tracking'
        : (str_contains($estRoute, 'standardEstimate') ? 'estimate_standard_tracking' : 'estimate_chemical_tracking');
    // Quyền gửi tin ở cột Trao đổi (quyền con của quyền xem tab Theo dõi dự trù)
    $estChatPerm = str_replace('_tracking', '_chat', $estTrackPerm);

    // Bộ lọc: phiếu dự trù + bộ phận (bộ phận mua hàng thấy mục của nhiều phòng)
    $trackLists = $items->pluck('list_code', 'list_id')->sortDesc();
    $trackDepts = $items->mapWithKeys(fn($i) => [$i->list_department_id => $i->department_short_name ?: $i->department_name])
        ->filter()->sort();
@endphp

<div class="d-flex flex-wrap align-items-center mb-2" style="gap: 12px;">
    <div class="d-flex align-items-center">
        <label for="mdTrackFilterList" class="mb-0 mr-2 text-nowrap">Phiếu dự trù</label>
        <select id="mdTrackFilterList" class="form-control form-control-sm md-track-filter" style="min-width: 180px;">
            <option value="">Tất cả</option>
            @foreach ($trackLists as $listId => $listCode)
                <option value="{{ $listId }}">{{ $listCode }}</option>
            @endforeach
        </select>
    </div>
    <div class="d-flex align-items-center">
        <label for="mdTrackFilterDept" class="mb-0 mr-2 text-nowrap">Bộ phận</label>
        <select id="mdTrackFilterDept" class="form-control form-control-sm md-track-filter" style="min-width: 180px;">
            <option value="">Tất cả</option>
            @foreach ($trackDepts as $deptId => $deptName)
                <option value="{{ $deptId }}">{{ $deptName }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="table-responsive">
    <table id="mdTrackingTable" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th class="text-center" style="width: 55px">STT</th>
                <th style="width: 120px">Mã Phiếu</th>
                <th style="width: 200px">Mặt Hàng</th>
                <th>Thông Tin Kỹ Thuật</th>
                <th>Mục Đích Sử Dụng</th>
                <th style="width: 200px">Số Lượng</th>
                <th style="width: 120px">Ngày Mong Muốn</th>
                <th style="width: 150px">Ngày Hẹn Đáp Ứng</th>
                <th style="width: 280px">Trao Đổi</th>
                <th style="width: 110px">Người Tạo</th>
                <th class="text-center" style="width: 120px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr data-list-id="{{ $item->list_id }}" data-dept-id="{{ $item->list_department_id }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <a href="{{ route($estRoute . 'detail', ['id' => $item->list_id] + ((int) $item->list_department_id !== (int) (session('user')['selected_department_id'] ?? 0) ? ['from' => 'approved'] : [])) }}" class="font-weight-bold" title="Xem chi tiết phiếu">{{ $item->list_code }}</a>
                        <div class="md-sub">{{ $item->department_short_name ?: $item->department_name }}</div>
                    </td>
                    <td>
                        <div class="font-weight-bold">{{ $item->display_name ?: '-' }}</div>
                        <div class="md-sub">
                            @if ($item->category_id)
                                @if (!empty($item->category_code))
                                    <span class="md-tag">{{ $item->category_code }}</span>
                                @endif
                                @if (!empty($item->category_type))
                                    <span class="ml-1">{{ $item->category_type }}</span>
                                @endif
                            @else
                                <span class="est-outside">Ngoài danh mục</span>
                            @endif
                        </div>
                        @foreach ($item->threshold_warnings ?? [] as $warning)
                            <div class="est-threshold-alert is-compact level-{{ $warning['level'] }}">
                                <i class="fas fa-triangle-exclamation mr-1"></i>{{ $warning['message'] }}
                            </div>
                        @endforeach
                    </td>
                    <td class="md-sub">
                        @if ($item->category_manufacturer_name)
                            <div class="mb-1">NSX: <b>{{ $item->category_manufacturer_name }}</b></div>
                        @endif
                        <div>{{ $item->technical_information ?: '-' }}</div>
                    </td>
                    <td class="md-sub">
                        <div class="mb-1">{{ $item->purpose ?: '-' }}</div>
                    </td>
                    <td>
                        @forelse ($item->amounts as $amount)
                            <span class="est-chip">
                                <b>{{ $estNum($amount->amount) }}
                                    {{ $amount->unit_short_name ?: $amount->unit_name }}</b>
                                <span>&middot;
                                    {{ \Carbon\Carbon::parse($amount->for_month_year)->format('m/Y') }}</span>
                            </span>
                        @empty
                            <span class="md-empty">-</span>
                        @endforelse
                    </td>
                    <td data-order="{{ $item->expected_delivery_date }}">
                        @if ($item->expected_delivery_date)
                            <div class="text-primary font-weight-bold">{{ \Carbon\Carbon::parse($item->expected_delivery_date)->format('d/m/Y') }}</div>
                        @else
                            <span class="md-empty">-</span>
                        @endif
                    </td>
                    <td>
                        {{-- Chỉ xem: ngày hẹn do bộ phận mua hàng đặt ở tab "Đơn dự trù đã được phê duyệt" --}}
                        @include('pages.estimate.shared.promisedDates', ['editable' => false])
                    </td>
                    <td>
                        @include('pages.estimate.shared.itemChat', ['canChat' => user_can($estChatPerm)])
                    </td>
                    <td class="md-sub">
                        {{ $item->updated_by ?: $item->created_by ?: '-' }}
                        <br><small>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : '' }}</small>
                    </td>
                    <td class="text-center">
                        @if (user_can($estTrackPerm))
                        <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-block mb-1 form-md-confirm" data-title="Xác nhận hoàn thành?" data-text="Mặt hàng này đã được giao đến khoa/phòng?">
                            @csrf
                            <input type="hidden" name="id" value="{{ $item->id }}">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="btn btn-sm btn-success" title="Xác nhận đã được giao"><i class="fas fa-check"></i> Hoàn thành</button>
                        </form>
                        <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-block form-md-confirm-cancel" data-title="Huỷ dự trù mặt hàng này?" data-text="Xác nhận khoa/phòng không cần dự trù mặt hàng này nữa?" data-danger="1">
                            @csrf
                            <input type="hidden" name="id" value="{{ $item->id }}">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn-sm btn-danger" title="Không cần dự trù nữa"><i class="fas fa-times"></i> Huỷ</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if ($.fn.DataTable.isDataTable('#mdTrackingTable')) {
            $('#mdTrackingTable').DataTable().destroy();
        }
        // Lọc theo phiếu / bộ phận: đọc data-* của dòng, chỉ áp cho bảng theo dõi
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'mdTrackingTable') return true;
            var row = settings.aoData[dataIndex].nTr;
            var listId = $('#mdTrackFilterList').val();
            var deptId = $('#mdTrackFilterDept').val();
            return (!listId || String($(row).data('list-id')) === listId)
                && (!deptId || String($(row).data('dept-id')) === deptId);
        });

        var mdTrackingTable = $('#mdTrackingTable').DataTable({
            "order": [], // Let the server handle ordering by promised_date asc
            "language": {
                "url": "{{ asset('dataTable/plugins/datatables/i18n/vi.json') }}",
                "emptyTable": "Không có mặt hàng nào đang được theo dõi"
            }
        });

        $('.md-track-filter').on('change', function() {
            mdTrackingTable.draw();
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
</script>
