@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | ĐỊNH KHU - cấu hình dùng chung cho bảng dữ liệu và các modal
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn,
    | tránh lệch nhau khi thêm cột hoặc đổi nhãn.
    */

    $zoneRoute = 'pages.materData.zone.';

    $activeWarehouses = $warehouses->where('status_id', 1);
    $activeRooms = $rooms->where('status_id', 1);
    $activeShelves = $shelves->where('status_id', 1);

    $zoneMeta = [
        'warehouse' => [
            'label' => 'Kho',
            'lower' => 'kho',
            'icon' => 'fas fa-warehouse',
            'rows' => $warehouses,
            'parents' => [],
            'cols' => [],
            'canCreate' => true,
            'blockMsg' => '',
        ],
        'room' => [
            'label' => 'Phòng',
            'lower' => 'phòng',
            'icon' => 'fas fa-door-open',
            'rows' => $rooms,
            'parents' => ['warehouse'],
            'cols' => ['warehouse_name' => 'Kho'],
            'canCreate' => true,
            'blockMsg' => '',
        ],
        'shelf' => [
            'label' => 'Kệ/Tủ',
            'lower' => 'kệ/tủ',
            'icon' => 'fas fa-layer-group',
            'rows' => $shelves,
            'parents' => ['warehouse', 'room'],
            'cols' => ['warehouse_name' => 'Kho', 'room_name' => 'Phòng'],
            'canCreate' => true,
            'blockMsg' => '',
        ],
        'location' => [
            'label' => 'Vị Trí',
            'lower' => 'vị trí',
            'icon' => 'fas fa-map-pin',
            'rows' => $locations,
            'parents' => ['warehouse', 'room', 'shelf'],
            'cols' => ['warehouse_name' => 'Kho', 'room_name' => 'Phòng', 'shelf_name' => 'Kệ/Tủ'],
            'canCreate' => true,
            'blockMsg' => '',
        ],
    ];

    // Mô tả các ô chọn cấp cha dùng trong modal (đổ dữ liệu động bằng JS theo cấp trên).
    $zoneParents = [
        'warehouse' => ['field' => 'warehouse_id', 'label' => 'Kho', 'class' => 'sel-warehouse', 'placeholder' => '-- Chọn kho (tuỳ chọn) --'],
        'room' => ['field' => 'room_id', 'label' => 'Phòng', 'class' => 'sel-room', 'placeholder' => '-- Chọn phòng (tuỳ chọn) --'],
        'shelf' => ['field' => 'shelf_id', 'label' => 'Kệ/Tủ', 'class' => 'sel-shelf', 'placeholder' => '-- Chọn kệ/tủ (tuỳ chọn) --'],
    ];

    // Dữ liệu 3 cấp cha đẩy xuống JS để đổ ô chọn dây chuyền Kho -> Phòng -> Kệ.
    $zoneOption = fn($row, $parent) => [
        'id' => $row->id,
        'code' => $row->code,
        'name' => $row->name,
        'status_id' => (int) $row->status_id,
        'parent' => $parent,
    ];

    $zoneCascade = [
        'warehouse' => $warehouses->map(fn($r) => $zoneOption($r, null))->values(),
        'room' => $rooms->map(fn($r) => $zoneOption($r, $r->warehouse_id))->values(),
        'shelf' => $shelves->map(fn($r) => $zoneOption($r, $r->room_id))->values(),
    ];

    // Giá trị vừa nhập, chỉ dùng lại khi có form bị lỗi validate.
    $zoneOld = session('formTab') ? old() : [];
@endphp

@section('mainContent')
    @include('pages.materData.Zone.dataTable')
@endsection

@section('model')
    @include('pages.materData.Zone.create')
    @include('pages.materData.Zone.update')
@endsection
