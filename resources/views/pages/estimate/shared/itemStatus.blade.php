{{--
| Trạng thái + nút xử lý của MỘT mục dự trù đã duyệt (trang chi tiết phiếu), dùng chung cho
| dự trù vật tư / hoá chất / chất chuẩn.
|
| Huỷ mục cần XÁC NHẬN CỦA HAI BÊN: phòng đề nghị (ở đây) và bộ phận mua hàng (tab
| "Bộ phận mua hàng") - xem App\Http\Controllers\Concerns\EstimateItemCancel.
|
| Biến vào: $item, $list, $estRoute, $trackPermission (vd. 'estimate_material_tracking')
--}}
@php
    $isCancelPending = (int) $item->status_id === 1 && (($item->cancel_requester_at ?? null) || ($item->cancel_purchasing_at ?? null));
    $fulfilledBy = $item->fulfilled_by ?? null;
@endphp

@if ($list->app_status === 'approved' && user_can($trackPermission))
    <div class="mt-2 pt-2 border-top">
        @if ($item->status_id == 0)
            <span class="badge badge-danger mb-1">Đã huỷ không dự trù</span>
            @if ($item->cancel_reason)
                <div class="text-danger small mb-1">Lý do: {{ $item->cancel_reason }}</div>
            @endif
            <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline ml-2 form-md-confirm" data-title="Khôi phục lại mặt hàng?" data-text="Bạn muốn tiếp tục dự trù mặt hàng này?">
                @csrf
                <input type="hidden" name="id" value="{{ $item->id }}">
                <input type="hidden" name="action" value="undo">
                <button type="submit" class="btn btn-xs btn-outline-secondary" title="Hoàn tác"><i class="fas fa-undo"></i> Hoàn tác</button>
            </form>
        @elseif ($item->fulfilled_date)
            <div class="text-success mb-1"><i class="fas fa-check-circle mr-1"></i> Đã giao: <b>{{ \Carbon\Carbon::parse($item->fulfilled_date)->format('d/m/Y') }}</b></div>
            @if ($fulfilledBy)
                <div class="small text-muted mb-1"><i class="fas fa-user-check mr-1"></i> {{ $fulfilledBy }}</div>
            @endif
            <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline mt-1 form-md-confirm" data-title="Hoàn tác trạng thái?" data-text="Mặt hàng này chưa được giao?">
                @csrf
                <input type="hidden" name="id" value="{{ $item->id }}">
                <input type="hidden" name="action" value="undo">
                <button type="submit" class="btn btn-xs btn-outline-secondary" title="Hoàn tác"><i class="fas fa-undo"></i> Hoàn tác</button>
            </form>
        @elseif ($isCancelPending)
            @include('pages.estimate.shared.cancelState')

            @if ($item->cancel_requester_at)
                <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline form-md-confirm" data-title="Rút lại đề nghị huỷ?" data-text="Mặt hàng sẽ tiếp tục được dự trù.">
                    @csrf
                    <input type="hidden" name="id" value="{{ $item->id }}">
                    <input type="hidden" name="action" value="cancel_withdraw">
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-undo mr-1"></i>Rút đề nghị huỷ</button>
                </form>
            @else
                <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline mr-1 form-md-confirm" data-title="Đồng ý huỷ mặt hàng?" data-text="Bộ phận mua hàng đã đề nghị huỷ. Xác nhận thì mặt hàng bị huỷ." data-danger="1">
                    @csrf
                    <input type="hidden" name="id" value="{{ $item->id }}">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-check mr-1"></i>Đồng ý huỷ</button>
                </form>
                <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline form-md-confirm" data-title="Không đồng ý huỷ?" data-text="Mặt hàng sẽ tiếp tục được dự trù.">
                    @csrf
                    <input type="hidden" name="id" value="{{ $item->id }}">
                    <input type="hidden" name="action" value="cancel_reject">
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times mr-1"></i>Không đồng ý</button>
                </form>
            @endif
        @else
            <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline mr-1 form-md-confirm" data-title="Xác nhận hoàn thành?" data-text="Mặt hàng này đã được giao đến khoa/phòng?">
                @csrf
                <input type="hidden" name="id" value="{{ $item->id }}">
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="btn btn-sm btn-success" title="Xác nhận đã được giao"><i class="fas fa-check"></i> Hoàn thành</button>
            </form>
            <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline form-md-confirm-cancel" data-title="Đề nghị huỷ mặt hàng này?" data-text="Mặt hàng chỉ bị huỷ khi bộ phận mua hàng cũng xác nhận." data-danger="1">
                @csrf
                <input type="hidden" name="id" value="{{ $item->id }}">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="btn btn-sm btn-danger" title="Đề nghị huỷ - cần bộ phận mua hàng xác nhận"><i class="fas fa-times"></i> Huỷ</button>
            </form>
        @endif
    </div>
@elseif ($list->app_status === 'approved' && $isCancelPending)
    <div class="mt-2 pt-2 border-top">
        @include('pages.estimate.shared.cancelState')
    </div>
@elseif ($item->status_id == 0)
    <div class="mt-2 pt-2 border-top">
        <span class="badge badge-danger">Đã huỷ không dự trù</span>
        @if ($item->cancel_reason)
            <div class="text-danger small mt-1">Lý do: {{ $item->cancel_reason }}</div>
        @endif
    </div>
@elseif ($item->fulfilled_date)
    <div class="text-success mt-1 border-top pt-2"><i class="fas fa-check-circle mr-1"></i> Đã giao: <b>{{ \Carbon\Carbon::parse($item->fulfilled_date)->format('d/m/Y') }}</b></div>
    @if ($fulfilledBy)
        <div class="small text-muted"><i class="fas fa-user-check mr-1"></i> {{ $fulfilledBy }}</div>
    @endif
@endif
