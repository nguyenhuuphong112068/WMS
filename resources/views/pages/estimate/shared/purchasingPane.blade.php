{{--
| Tab "Bộ phận mua hàng" ở màn Dự Trù Vật Tư / Hoá Chất / Chất Chuẩn - chỉ hiện với
| phòng là bộ phận mua hàng (Cung Ứng / Hành Chánh / IT, xem App\Support\MaterialPurchasing).
|
| Các mục dự trù đã duyệt (cùng công ty) do phòng đang chọn phụ trách mua, chưa giao,
| chưa huỷ. Bộ phận mua hàng xác nhận / từ chối đề nghị huỷ của phòng đề nghị, hoặc tự
| đề nghị huỷ để phòng đề nghị xác nhận - huỷ chỉ có hiệu lực khi đủ cả hai bên.
|
| Biến vào: $items (EstimateItemCancel::purchasingItems()), $estRoute
--}}
@php
    $pRoute = $estRoute;
@endphp

<div class="table-responsive">
    <table id="purchasingTable" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th class="text-center" style="width: 50px">STT</th>
                <th style="width: 150px">Phiếu</th>
                <th>Vật Tư</th>
                <th style="width: 140px">Mong Muốn Giao</th>
                <th style="width: 320px">Đề Nghị Huỷ</th>
                <th class="text-center" style="width: 230px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php
                    $pReqDone = (bool) $item->cancel_requester_at;
                    $pPurDone = (bool) $item->cancel_purchasing_at;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <span class="md-tag">{{ $item->list_code }}</span>
                        <div class="md-sub">{{ $item->department_short_name }}</div>
                    </td>
                    <td>
                        <div class="font-weight-bold">{{ $item->display_name ?: '—' }}</div>
                        <div class="md-sub">
                            @if ($item->category_id)
                                <small>{{ $item->category_code }}</small>
                            @else
                                <span class="est-outside">Ngoài danh mục</span>
                            @endif
                            @if ($item->part_number ?? null)
                                <small> · P/N: <b>{{ $item->part_number }}</b></small>
                            @endif
                        </div>
                    </td>
                    <td>
                        {{ $item->expected_delivery_date ? \Carbon\Carbon::parse($item->expected_delivery_date)->format('d/m/Y') : '—' }}
                    </td>
                    <td>
                        @if ($pReqDone || $pPurDone)
                            @include('pages.estimate.shared.cancelState')
                        @else
                            <span class="md-empty">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if (!$pReqDone && !$pPurDone)
                            <form action="{{ route($pRoute . 'purchaseCancel') }}" method="POST" class="d-inline form-md-confirm-cancel"
                                data-title="Đề nghị huỷ mặt hàng này?" data-text="Mặt hàng chỉ bị huỷ khi phòng đề nghị cũng xác nhận." data-danger="1">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item->id }}">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-times mr-1"></i>Huỷ</button>
                            </form>
                        @elseif ($pReqDone && !$pPurDone)
                            <form action="{{ route($pRoute . 'purchaseCancel') }}" method="POST" class="d-inline mr-1 form-md-confirm"
                                data-title="Đồng ý huỷ mặt hàng?" data-text="Phòng đề nghị đã đề nghị huỷ. Xác nhận thì mặt hàng bị huỷ." data-danger="1">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item->id }}">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-check mr-1"></i>Đồng ý huỷ</button>
                            </form>
                            <form action="{{ route($pRoute . 'purchaseCancel') }}" method="POST" class="d-inline form-md-confirm"
                                data-title="Không đồng ý huỷ?" data-text="Mặt hàng sẽ tiếp tục được dự trù.">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item->id }}">
                                <input type="hidden" name="action" value="cancel_reject">
                                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times mr-1"></i>Không đồng ý</button>
                            </form>
                        @else
                            <form action="{{ route($pRoute . 'purchaseCancel') }}" method="POST" class="d-inline form-md-confirm"
                                data-title="Rút lại đề nghị huỷ?" data-text="Mặt hàng sẽ tiếp tục được dự trù.">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item->id }}">
                                <input type="hidden" name="action" value="cancel_withdraw">
                                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-undo mr-1"></i>Rút đề nghị huỷ</button>
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
        if (!$.fn.DataTable || $.fn.DataTable.isDataTable('#purchasingTable')) return;

        $('#purchasingTable').DataTable({
            ordering: false,
            pageLength: 25,
            language: {
                search: 'Tìm kiếm:',
                lengthMenu: 'Hiển thị _MENU_ dòng',
                info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
                infoEmpty: 'Không có dữ liệu',
                zeroRecords: 'Không tìm thấy dữ liệu phù hợp',
                emptyTable: 'Không có mục dự trù nào cần mua',
                paginate: { previous: 'Trước', next: 'Sau' }
            }
        });
    });
</script>
