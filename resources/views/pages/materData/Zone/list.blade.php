@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | ĐỊNH KHU - cấu hình dùng chung cho bảng dữ liệu và các modal
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn,
    | tránh lệch nhau khi thêm cột hoặc đổi nhãn.
    |
    | Năm cấp: Kho/Phòng -> Kệ/Tủ -> Cột -> Tầng -> Vị Trí.
    */

    $zoneRoute = 'pages.materData.zone.';

    $zoneMeta = [
        'warehouse' => [
            'label' => 'Kho/Phòng',
            'table' => 'warehouses',
            'lower' => 'kho/phòng',
            'icon' => 'fas fa-warehouse',
            'rows' => $warehouses,
            'parents' => [],
            'cols' => [],
            'canCreate' => true,
            'blockMsg' => '',
        ],
        'shelf' => [
            'label' => 'Kệ/Tủ',
            'table' => 'shelves',
            'lower' => 'kệ/tủ',
            'icon' => 'fas fa-layer-group',
            'rows' => $shelves,
            'parents' => ['warehouse'],
            'cols' => ['warehouse_name' => 'Kho/Phòng'],
            'canCreate' => true,
            'blockMsg' => '',
        ],
        'column' => [
            'label' => 'Cột',
            'table' => 'columns',
            'lower' => 'cột',
            'icon' => 'fas fa-grip-lines-vertical',
            'rows' => $columns,
            'parents' => ['warehouse', 'shelf'],
            'cols' => ['warehouse_name' => 'Kho/Phòng', 'shelf_name' => 'Kệ/Tủ'],
            'canCreate' => true,
            'blockMsg' => '',
        ],
        'tier' => [
            'label' => 'Tầng',
            'table' => 'tiers',
            'lower' => 'tầng',
            'icon' => 'fas fa-bars',
            'rows' => $tiers,
            'parents' => ['warehouse', 'shelf', 'column'],
            'cols' => ['warehouse_name' => 'Kho/Phòng', 'shelf_name' => 'Kệ/Tủ', 'column_name' => 'Cột'],
            'canCreate' => true,
            'blockMsg' => '',
        ],
        'location' => [
            'label' => 'Vị Trí',
            'table' => 'locations',
            'lower' => 'vị trí',
            'icon' => 'fas fa-map-pin',
            'rows' => $locations,
            'parents' => ['warehouse', 'shelf', 'column', 'tier'],
            'cols' => [
                'warehouse_name' => 'Kho/Phòng',
                'shelf_name' => 'Kệ/Tủ',
                'column_name' => 'Cột',
                'tier_name' => 'Tầng',
            ],
            'canCreate' => true,
            'blockMsg' => '',
            // Chỉ cấp vị trí mới khai loại lưu trữ - đây mới là chỗ thực sự đựng hàng.
            'hasType' => true,
            // Vị trí chỉ định danh bằng mã (A01, B02...) nên không có cột tên.
            'hasName' => false,
        ],
    ];

    // Chuỗi nhận biết một dòng: cấp có tên thì lấy tên, cấp vị trí chỉ có mã.
    $zoneCaption = fn($meta, $row) => ($meta['hasName'] ?? true) ? $row->name : $row->code;

    // Vị trí không chọn loại nghĩa là dùng chung, hiện ở cả ba màn hình Tồn Kho.
    $zoneTypeLabel = fn($value) => $locationTypes[$value] ?? 'Dùng chung';

    // Mô tả các ô chọn cấp cha dùng trong modal (đổ dữ liệu động bằng JS theo cấp trên).
    $zoneParents = [
        'warehouse' => ['field' => 'warehouse_id', 'label' => 'Kho/Phòng', 'class' => 'sel-warehouse', 'placeholder' => '-- Chọn kho/phòng (tuỳ chọn) --'],
        'shelf' => ['field' => 'shelf_id', 'label' => 'Kệ/Tủ', 'class' => 'sel-shelf', 'placeholder' => '-- Chọn kệ/tủ (tuỳ chọn) --'],
        'column' => ['field' => 'column_id', 'label' => 'Cột', 'class' => 'sel-column', 'placeholder' => '-- Chọn cột (tuỳ chọn) --'],
        'tier' => ['field' => 'tier_id', 'label' => 'Tầng', 'class' => 'sel-tier', 'placeholder' => '-- Chọn tầng (tuỳ chọn) --'],
    ];

    // Dữ liệu 4 cấp cha đẩy xuống JS để đổ ô chọn dây chuyền Kho/Phòng -> Kệ/Tủ -> Cột -> Tầng.
    $zoneOption = fn($row, $parent) => [
        'id' => $row->id,
        'code' => $row->code,
        'name' => $row->name,
        'status_id' => (int) $row->status_id,
        'parent' => $parent,
    ];

    $zoneCascade = [
        'warehouse' => $warehouses->map(fn($r) => $zoneOption($r, null))->values(),
        'shelf' => $shelves->map(fn($r) => $zoneOption($r, $r->warehouse_id))->values(),
        'column' => $columns->map(fn($r) => $zoneOption($r, $r->shelf_id))->values(),
        'tier' => $tiers->map(fn($r) => $zoneOption($r, $r->column_id))->values(),
    ];

    // Giá trị vừa nhập, chỉ dùng lại khi có form bị lỗi validate.
    $zoneOld = session('formTab') ? old() : [];
@endphp

@section('mainContent')
    @include('pages.materData.Zone.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.Zone.create')
    @include('pages.materData.Zone.update')
@endsection
