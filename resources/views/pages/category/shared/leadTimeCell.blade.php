{{--
| Ô cột "Thời Gian Đặt Hàng" của danh mục công ty + nút điều chỉnh (mở #leadTimeModal).
| Mã mới tạo còn cờ lead_time_pending thì gắn nhãn "Chờ khai" (trang chủ đang báo người có quyền).
| Biến vào: $row, $permPrefix ('category_material_'...),
|           $applies (tuỳ chọn, mặc định true) - false: vật tư Hành Chánh, không cần thời gian đặt hàng.
--}}
@php
    $applies = $applies ?? true;
@endphp
<td class="text-center text-nowrap" data-order="{{ $applies ? ($row->lead_time_days ?? -1) : -2 }}">
    @if (! $applies)
        <span class="md-empty" title="Vật tư do Hành Chánh mua - không cần thời gian đặt hàng">Không cần</span>
    @else
        @if ($row->lead_time_days !== null)
            <span class="font-weight-bold">{{ $row->lead_time_days }}</span>
            <span class="md-sub">ngày</span>
        @else
            <span class="md-empty">—</span>
        @endif
        @if (user_can($permPrefix . 'lead_time'))
            <button type="button" class="btn btn-link btn-sm p-0 ml-1 btn-cat-lead-time"
                title="Điều chỉnh thời gian đặt hàng"
                data-id="{{ $row->id }}" data-code="{{ $row->code }}" data-days="{{ $row->lead_time_days ?? '' }}">
                <i class="fas fa-pen"></i>
            </button>
        @endif
        @if (! empty($row->lead_time_pending))
            <div><span class="badge badge-warning" title="Mã mới - chờ người có quyền khai / xác nhận thời gian đặt hàng">Chờ khai</span></div>
        @endif
    @endif
</td>
