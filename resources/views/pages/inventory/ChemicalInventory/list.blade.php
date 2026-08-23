@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | TỒN - TỒN KHO HOÁ CHẤT
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable và modal cân đối cùng đọc một nguồn.
    | Màn hình chỉ đọc phần tồn, chỉ ghi dữ liệu qua nút Cân Đối.
    */

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5, 12.0000 -> 12 */
    $invNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. Khai báo ở đây để cả dataTable và các modal cùng dùng. */
    $invDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /**
     * Mã phân loại (cột JSON chemical_categories.classification) thành chuỗi "PL2,N1"
     * để đưa vào data-classification cho bộ lọc Phụ lục / Nhóm hoá chất.
     */
    $invCls = function ($value) {
        $codes = json_decode($value ?? '', true);

        return is_array($codes) ? implode(',', $codes) : '';
    };

    /*
    | Cách tính từng trạng thái tồn, đưa vào tooltip của nút lọc thay vì in thành
    | một hàng chú thích riêng trên màn hình.
    */
    $invStateHints = [
        'in' => 'Còn hàng: tồn còn trên ' . $lowStockPercent . '% lượng nhập và còn hạn dùng.',
        'low' => 'Sắp hết: tồn còn từ ' . $lowStockPercent . '% lượng nhập trở xuống.',
        'near' => 'Sắp hết hạn: còn hạn dùng dưới ' . $nearExpiryDays . ' ngày.',
        'expired' => 'Hết hạn: đã quá hạn dùng nhưng vẫn còn tồn.',
        'out' => 'Hết hàng: đã dùng hết, tồn bằng 0.',
        'over' => 'Âm kho: đã xuất vượt lượng nhập - bấm Cân Đối để chỉnh số lượng nhập về đúng thực tế.',
    ];

    // Số mã xuất nhập theo từng trạng thái tồn, dùng cho các nút lọc nhanh
    $invStateCounts = collect($states)
        ->map(fn($label, $key) => $datas->where('state', $key)->count())
        ->toArray();

    // Lịch sử cân đối cho JS: [import_id => [{số điều chỉnh, người, thời điểm}]]
    $invBalancingMap = $balancings
        ->map(
            fn($rows) => $rows
                ->map(
                    fn($row) => [
                        'balancing_amount' => (float) $row->balancing_amount,
                        'balancing_by' => $row->balancing_by,
                        'balancing_at' => \Carbon\Carbon::parse($row->balancing_at)->format('d/m/Y H:i'),
                    ],
                )
                ->values(),
        )
        ->toArray();
@endphp

@section('mainContent')
    @include('pages.inventory.ChemicalInventory.dataTable')
@endsection

@section('model')
    @include('pages.inventory.ChemicalInventory.balancing')
    @include('pages.inventory.ChemicalInventory.balancingHistory')
    @include('pages.inventory.ChemicalInventory.internalExpiry')
@endsection
