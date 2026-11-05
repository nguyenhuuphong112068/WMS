{{--
| Modal "Khai Chất Chuẩn Cho Phòng" dạng bảng nhiều dòng - khuôn chung pages.category.shared.deptBatch
| (xem chú thích ở đó). Riêng chất chuẩn: Hạn Dùng Nội Bộ (tính từ ngày mở ống).
|
| Đơn vị gợi ý = đơn vị phòng khác đang dùng cho mã đó ($unitsInUse).
--}}
@include('pages.category.shared.deptBatch', [
    'cfg' => [
        'modalId' => 'dsCreateModal',
        'prefix' => 'dsb',
        'title' => 'Khai Chất Chuẩn Cho Phòng',
        'icon' => $mdIcon,
        'action' => route($mdRoute . 'store'),
        'bag' => 'dsCreateErrors',
        'word' => 'chất chuẩn',
        'items' => collect($categories)
            ->map(function ($c) use ($unitsInUse) {
                $maker = $c->manufacturer_short_name ?: $c->manufacturer_name;

                return [
                    'id' => (string) $c->id,
                    'text' => $c->code . ' - ' . $c->standard_name . ($maker ? ' (' . $maker . ')' : '')
                        . ($c->version > 1 ? ' - Ver ' . $c->version : ''),
                    'search' => implode(' ', array_filter([$c->code, $c->standard_name, $c->manufacturer_name, $c->manufacturer_short_name])),
                    'name' => (string) $c->standard_name,
                    'meta' => implode(' · ', array_filter([$c->code, $maker, $c->version > 1 ? 'Ver ' . $c->version : null])),
                    'unit' => (string) (collect($unitsInUse[$c->id] ?? [])->first()->unit_id ?? ''),
                    'shelf' => $c->shelf_life_months ? (string) $c->shelf_life_months : '',
                ];
            })
            ->values(),
        'units' => $units,
        'classifications' => null,
        'shelf' => true,
        'packWeight' => false,
        'compat' => false,
        'locationType' => 'standard',
        'batchLocations' => $batchLocations ?? [],
        'unitsInUse' => $unitsInUse,
        'conversions' => $conversions,
        'picker' => false,
        'emptyIcon' => 'fas fa-vial',
        'hint' => 'Chỉ chọn được chất chuẩn đã duyệt trong danh mục công ty và <b>phòng chưa khai</b>. Bản chất chất chuẩn '
            . '(tên, số CAS, nguồn gốc, phân nhóm chuẩn) khai ở <b>Danh Mục Chất Chuẩn Công Ty</b>; ở đây chỉ khai phần '
            . '<b>riêng của phòng</b>, ô để trống tự chạy theo danh mục (riêng Đơn Vị Tính bắt buộc). Hạn dùng nội bộ '
            . 'tính từ ngày mở ống.',
    ],
])
