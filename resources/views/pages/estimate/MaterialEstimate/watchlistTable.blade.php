@php
    // Bảng này chỉ dùng riêng cho Dự Trù Vật Tư nên $estRoute cố định trỏ đúng module này
    $wlEstRoute = 'pages.estimate.materialEstimate.';
    $wlNum = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $wlSourceLabel = fn ($type) => match ($type) {
        'risk_assessment' => 'Đề Nghị Theo ĐG Rủi Ro',
        'regular' => 'Đề Nghị Thường Quy',
        'category' => 'Danh Mục Vật Tư Của Phòng',
        default => null,
    };
@endphp

<div class="table-responsive">
    <table id="mdWatchlistTable" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th class="text-center" style="width: 55px">STT</th>
                <th style="width: 260px">Vật Tư</th>
                <th>Quy Cách</th>
                <th class="text-center" style="width: 90px">ĐVT</th>
                <th class="text-right" style="width: 110px">Tồn Hiện Tại</th>
                <th class="text-right" style="width: 130px">Ngưỡng Tối Thiểu</th>
                <th style="width: 200px">Nguồn</th>
                <th style="width: 220px">Lý Do Dự Trù</th>
                <th class="text-center" style="width: 90px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="font-weight-bold">{{ $item->material_name ?: '-' }}</div>
                        @if ($item->manufacturer_name)
                            <div class="md-sub">NSX: {{ $item->manufacturer_name }}</div>
                        @endif
                        @unless ($item->category_id)
                            <span class="est-outside">Ngoài danh mục</span>
                        @endunless
                    </td>
                    <td class="md-sub">{{ $item->technical_specification ?: '-' }}</td>
                    <td class="text-center">{{ $item->unit ?: '-' }}</td>
                    <td class="text-right">{{ $item->on_hand !== null ? $wlNum($item->on_hand) : '—' }}</td>
                    <td class="text-right">{{ $item->min_stock !== null ? $wlNum($item->min_stock) : '—' }}</td>
                    <td>
                        @if ($item->below_threshold)
                            <span class="md-badge rejected">Dưới ngưỡng tồn tối thiểu</span>
                        @endif
                        @if ($item->watchlist_id)
                            <div class="md-sub mt-1">
                                <span class="md-badge pending">Đề nghị dự trù</span>
                                @if ($wlSourceLabel($item->source_type))
                                    <span class="ml-1">({{ $wlSourceLabel($item->source_type) }})</span>
                                @endif
                            </div>
                            <div class="md-sub">
                                {{ $item->remembered_by ?: '—' }}
                                <br><small>{{ $item->remembered_at ? \Carbon\Carbon::parse($item->remembered_at)->format('d/m/Y') : '' }}</small>
                            </div>
                        @endif
                    </td>
                    <td class="md-sub">
                        @if ($item->note)
                            <span class="md-note" title="{{ $item->note }}">{{ $item->note }}</span>
                        @else
                            <span class="md-empty">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($item->watchlist_id)
                            <form action="{{ route($wlEstRoute . 'watchlistDismiss') }}" method="POST" class="form-md-confirm-cancel d-inline"
                                data-title="Bỏ ghi nhớ vật tư này?" data-text="Vật tư sẽ chỉ còn hiện lại ở đây nếu tồn xuống dưới ngưỡng tối thiểu.">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item->watchlist_id }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Bỏ ghi nhớ"><i class="fas fa-bookmark"></i></button>
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
        if ($.fn.DataTable.isDataTable('#mdWatchlistTable')) {
            $('#mdWatchlistTable').DataTable().destroy();
        }
        $('#mdWatchlistTable').DataTable({
            "order": [],
            "language": {
                "url": "{{ asset('dataTable/plugins/datatables/i18n/vi.json') }}",
                "emptyTable": "Không có vật tư nào cần dự trù"
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
</script>
