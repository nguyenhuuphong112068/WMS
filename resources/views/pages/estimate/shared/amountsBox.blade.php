{{--
| Khối khai số lượng dự trù theo từng tháng của một mặt hàng.
|
| Biến vào:
| - $units          : danh sách đơn vị tính
| - $oldRows        : các dòng đã có (mảng ['amount', 'unit_id', 'for_month_year']),
|                     truyền vào khi form bị lỗi validate để không mất dữ liệu vừa gõ.
| - $defaultPeriods : các tháng mở sẵn khi form còn trống (mảng chuỗi 'YYYY-MM').
|                     Màn thêm mặt hàng truyền 3 tháng liên tiếp tính từ tháng dự trù.
|
| Dòng mẫu nằm trong <template>, JS nhân bản ra khi bấm "Thêm tháng" và đánh số lại
| tên ô thành amounts[0][...], amounts[1][...] - xem pages/estimate/shared/assets.blade.php.
--}}
@php
    $oldRows = $oldRows ?? [];
    $defaultPeriods = $defaultPeriods ?? [];
@endphp

<div class="est-amounts" data-default-periods="{{ json_encode($defaultPeriods) }}">
    <div class="est-amount-head">
        <div class="col-amount">Số Lượng <span class="text-danger">*</span></div>
        <div class="col-unit">Đơn Vị <span class="text-danger">*</span></div>
        <div class="col-period">Tháng Cần Dùng <span class="text-danger">*</span></div>
        <div class="col-remove"></div>
    </div>

    <div class="est-amount-list">
        @foreach ($oldRows as $index => $oldRow)
            @include('pages.estimate.shared.amountRow', [
                'units' => $units,
                'index' => $index,
                'amount' => $oldRow['amount'] ?? '',
                'unitId' => $oldRow['unit_id'] ?? '',
                'period' => $oldRow['for_month_year'] ?? '',
            ])
        @endforeach
    </div>

    <template class="est-amount-template">
        @include('pages.estimate.shared.amountRow', [
            'units' => $units,
            'index' => '',
            'amount' => '',
            'unitId' => '',
            'period' => '',
        ])
    </template>

    <button type="button" class="btn btn-sm btn-outline-primary btn-est-amount-add mb-2">
        <i class="fas fa-plus mr-1"></i> Thêm tháng
    </button>
</div>
