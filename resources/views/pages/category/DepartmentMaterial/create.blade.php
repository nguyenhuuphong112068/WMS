{{--
| Modal "Khai Vật Tư Cho Phòng" dạng bảng nhiều dòng - khuôn chung pages.category.shared.deptBatch
| (xem chú thích ở đó). Riêng vật tư: có cột Phân Loại Của Phòng và bảng chọn nhiều
| "Chọn nhiều từ danh mục công ty" (categoryPicker.blade.php).
--}}
@include('pages.category.shared.deptBatch', [
    'cfg' => [
        'modalId' => 'dmCreateModal',
        'prefix' => 'dmb',
        'title' => 'Khai Vật Tư Cho Phòng',
        'icon' => $mdIcon,
        'action' => route($mdRoute . 'store'),
        'bag' => 'dmCreateErrors',
        'word' => 'vật tư',
        'items' => collect($categories)
            ->map(function ($c) {
                $maker = $c->manufacturer_short_name ?: $c->manufacturer_name;

                return [
                    'id' => (string) $c->id,
                    'text' => ($c->code ? '[' . $c->code . '] ' : '') . $c->material_name . ($maker ? ' (' . $maker . ')' : ''),
                    'search' => implode(' ', array_filter([$c->code, $c->material_name, $c->manufacturer_name,
                        $c->manufacturer_short_name, $c->part_number, $c->technical_specification])),
                    'name' => (string) $c->material_name,
                    'meta' => implode(' · ', array_filter([$c->code, $maker, $c->part_number, $c->technical_specification])),
                    'unit' => $c->suggested_unit_id ? (string) $c->suggested_unit_id : '',
                ];
            })
            ->values(),
        'units' => $units,
        'classifications' => $classifications,
        'shelf' => false,
        'packWeight' => false,
        'compat' => false,
        'locationType' => 'material',
        'batchLocations' => $batchLocations ?? [],
        'unitsInUse' => $unitsInUse,
        'conversions' => $conversions,
        'picker' => true,
        'emptyIcon' => 'fas fa-boxes',
        'hint' => 'Chỉ chọn được vật tư đã duyệt trong danh mục công ty và <b>phòng chưa khai</b>. Bản chất vật tư (tên, '
            . 'nhà sản xuất, phân loại, bộ phận mua hàng) khai ở <b>Danh Mục Vật Tư Công Ty</b>; ở đây chỉ khai phần '
            . '<b>riêng của phòng</b>. Ngưỡng tồn tính theo đơn vị tính của dòng; định khu là chỗ <b>dự kiến</b>, '
            . 'định khu thật của từng lô khai lúc nhập.',
    ],
])
