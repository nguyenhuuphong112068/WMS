{{--
| Modal "Khai Hoá Chất Cho Phòng" dạng bảng nhiều dòng - khuôn chung pages.category.shared.deptBatch
| (xem chú thích ở đó). Riêng hoá chất: Hạn Dùng Nội Bộ, Khối lượng quy đổi (đơn vị đếm / bao bì) và
| cảnh báo tương kỵ ở định khu theo từng dòng (DepartmentChemical/compatibility.blade.php).
|
| Đơn vị gợi ý = đơn vị phòng khác đang dùng cho mã đó ($unitsInUse).
--}}
@include('pages.category.shared.deptBatch', [
    'cfg' => [
        'modalId' => 'dcCreateModal',
        'prefix' => 'dcb',
        'title' => 'Khai Hoá Chất Cho Phòng',
        'icon' => $mdIcon,
        'action' => route($mdRoute . 'store'),
        'bag' => 'dcCreateErrors',
        'word' => 'hoá chất',
        'items' => collect($categories)
            ->map(function ($c) use ($unitsInUse) {
                $maker = $c->manufacturer_short_name ?: $c->manufacturer_name;

                return [
                    'id' => (string) $c->id,
                    'text' => $c->code . ' - ' . $c->chem_name . ($maker ? ' (' . $maker . ')' : ''),
                    'search' => implode(' ', array_filter([$c->code, $c->chem_name, $c->manufacturer_name, $c->manufacturer_short_name])),
                    'name' => (string) $c->chem_name,
                    'meta' => implode(' · ', array_filter([$c->code, $maker])),
                    'unit' => (string) (collect($unitsInUse[$c->id] ?? [])->first()->unit_id ?? ''),
                    'shelf' => $c->shelf_life_months ? (string) $c->shelf_life_months : '',
                ];
            })
            ->values(),
        'units' => $units,
        'classifications' => null,
        'shelf' => true,
        'packWeight' => true,
        'compat' => true,
        'locationType' => 'chemical',
        'locationLabel' => 'code',
        'batchLocations' => $batchLocations ?? [],
        'unitsInUse' => $unitsInUse,
        'conversions' => $conversions,
        'picker' => false,
        'emptyIcon' => 'fas fa-flask',
        'hint' => 'Chỉ chọn được hoá chất đã duyệt trong danh mục công ty và <b>phòng chưa khai</b>. Bản chất hoá chất '
            . '(tên, nhà sản xuất, tỉ trọng, phân loại) khai ở <b>Danh Mục Hoá Chất Công Ty</b>; ở đây chỉ khai phần '
            . '<b>riêng của phòng</b>, ô để trống tự chạy theo danh mục. Đơn vị đếm / bao bì thì khai thêm khối lượng '
            . 'quy đổi (kg). Định khu cùng kệ/tủ với hoá chất tương kỵ - kể cả giữa các dòng trong bảng - không lưu được.',
    ],
])
