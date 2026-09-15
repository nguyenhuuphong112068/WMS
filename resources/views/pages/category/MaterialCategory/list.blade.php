@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DANH MỤC - VẬT TƯ (1 TRANG, 2 TAB)
    |--------------------------------------------------------------------------
    | Tab 1 "Danh Mục Vật Tư Công Ty" : bản chất của vật tư (tên, nhà sản xuất, thông tin
    |                                   kỹ thuật, phân loại, bộ phận mua hàng, thời gian đặt
    |                                   hàng), dùng chung toàn công ty, có bước duyệt
    |                                   (MaterialCategoryController).
    | Tab 2 "Vật Tư Của Phòng"        : cách dùng riêng của phòng ban đang chọn (đơn vị tính,
    |                                   ngưỡng tồn, định khu), không duyệt
    |                                   (DepartmentMaterialController).
    |
    | Hai tab dùng chung một trang nên phải tách nhau ở 3 chỗ:
    | - Biến dữ liệu : tab 2 đặt tiền tố dm (dmDatas, dmCategories...)
    | - Bảng         : #mdTable và #dmTable (bảng thêm cần class md-table)
    | - Modal        : #createModal / #updateModal và #dmCreateModal / #dmUpdateModal,
    |                  nút bấm của tab 2 chỉ ra modal của mình bằng data-modal.
    */

    // ----- Tab 1: Danh Mục Vật Tư Công Ty -----
    $mdRoute = 'pages.category.materialCategory.';
    $mdLabel = 'danh mục vật tư công ty';
    $mdTitle = 'Danh Mục Vật Tư Công Ty';
    $mdIcon = 'fas fa-cubes';

    // ----- Tab 2: Vật Tư Của Phòng -----
    $dmRoute = 'pages.category.departmentMaterial.';
    $dmLabel = 'vật tư của phòng';
    $dmTitle = 'Vật Tư Của Phòng';
    $dmIcon = 'fas fa-building-user';

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 */
    $dmNum = fn($value) => $value === null ? null : rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Đường dẫn định khu đầy đủ của một dòng (Kho / Kệ-Tủ / Cột / Tầng), trống thì trả về null */
    $dmPath = fn($row) => $row->location_code
        ? ($row->warehouse_name ?: '—') .
            ' / ' .
            ($row->shelf_name ?: '—') .
            ' / ' .
            ($row->column_name ?: '—') .
            ' / ' .
            ($row->tier_name ?: '—')
        : null;

    /*
    | Tab đang mở khi vào trang. Lưu ở tab 2 xong mà nhảy về tab 1 thì rất khó dùng, nên
    | DepartmentMaterialController luôn kèm activeTab = 'department' lúc quay lại.
    */
    $dmHasErrors = $errors->getBag('dmCreateErrors')->any() || $errors->getBag('dmUpdateErrors')->any();

    // ----- Tab 3, 4: Danh sách vật tư đề nghị theo chu kỳ (PeriodicRequestController) -----
    $prHasErrors = fn ($type) => $errors->getBag(\App\Support\MaterialPeriodicRequest::errorBag($type, 'Create'))->any()
        || $errors->getBag(\App\Support\MaterialPeriodicRequest::errorBag($type, 'Update'))->any();

    $activeTab = match (true) {
        $dmHasErrors => 'department',
        $prHasErrors('internal') => 'internal',
        $prHasErrors('external') => 'external',
        in_array(session('activeTab'), ['department', 'internal', 'external'], true) => session('activeTab'),
        default => 'company',
    };

    $tabHashes = [
        'company' => '#tabCompany',
        'department' => '#tabDepartment',
        'internal' => '#tabPeriodicInternal',
        'external' => '#tabPeriodicExternal',
    ];
@endphp

@section('mainContent')
    <div class="content-wrapper">
        <div class="md-page">

            <ul class="nav cat-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'company' ? 'active' : '' }}" id="tabCompanyLink"
                        data-toggle="pill" href="#tabCompany" role="tab">
                        <i class="{{ $mdIcon }}"></i>
                        <span>{{ $mdTitle }}</span>
                        <span class="cat-tab-count">{{ $datas->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'department' ? 'active' : '' }}" id="tabDepartmentLink"
                        data-toggle="pill" href="#tabDepartment" role="tab">
                        <i class="{{ $dmIcon }}"></i>
                        <span>{{ $dmTitle }}</span>
                        <span class="cat-tab-count">{{ $dmDatas->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'internal' ? 'active' : '' }}" id="tabPeriodicInternalLink"
                        data-toggle="pill" href="#tabPeriodicInternal" role="tab">
                        <i class="fas fa-sync-alt"></i>
                        <span>Danh sách vật tư đề nghị nội bộ theo chu kỳ</span>
                        <span class="cat-tab-count">{{ $periodicInternalLists->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'external' ? 'active' : '' }}" id="tabPeriodicExternalLink"
                        data-toggle="pill" href="#tabPeriodicExternal" role="tab">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Danh sách vật tư đề nghị liên phòng ban theo chu kỳ</span>
                        <span class="cat-tab-count">{{ $periodicExternalLists->count() }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'company' ? 'show active' : '' }}" id="tabCompany"
                    role="tabpanel">
                    @include('pages.category.MaterialCategory.dataTable')
                </div>

                <div class="tab-pane fade {{ $activeTab === 'department' ? 'show active' : '' }}" id="tabDepartment"
                    role="tabpanel">
                    @include('pages.category.DepartmentMaterial.dataTable', [
                        'datas' => $dmDatas,
                        'mdRoute' => $dmRoute,
                        'mdLabel' => $dmLabel,
                        'mdTitle' => $dmTitle,
                        'mdIcon' => $dmIcon,
                    ])
                </div>

                <div class="tab-pane fade {{ $activeTab === 'internal' ? 'show active' : '' }}" id="tabPeriodicInternal"
                    role="tabpanel">
                    @include('pages.category.PeriodicRequest.dataTable', [
                        'type' => 'internal',
                        'lists' => $periodicInternalLists,
                    ])
                </div>

                <div class="tab-pane fade {{ $activeTab === 'external' ? 'show active' : '' }}" id="tabPeriodicExternal"
                    role="tabpanel">
                    @include('pages.category.PeriodicRequest.dataTable', [
                        'type' => 'external',
                        'lists' => $periodicExternalLists,
                    ])
                </div>
            </div>
        </div>
    </div>

    <style>
        /* ---------- Thanh Tab ---------- */
        .cat-tabs {
            gap: 10px;
            border-bottom: 2px solid var(--primary-soft);
            padding-bottom: 0;
            margin-bottom: 12px;
        }

        .cat-tabs .nav-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 11px 20px;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
            color: #64748b;
            font-weight: 700;
            font-size: 0.88rem;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            background: transparent;
            transition: all var(--transition-fast, 0.2s ease);
        }

        .cat-tabs .nav-link:hover {
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .cat-tabs .nav-link.active {
            background: #fff;
            color: var(--primary);
            border-color: var(--primary-soft);
            box-shadow: 0 -3px 0 var(--primary) inset;
        }

        .cat-tabs .nav-link i {
            font-size: 0.95rem;
            color: var(--primary-lighter);
        }

        .cat-tabs .nav-link.active i {
            color: var(--primary);
        }

        .cat-tab-count {
            min-width: 26px;
            padding: 1px 8px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 0.76rem;
            text-align: center;
        }

        .cat-tabs .nav-link.active .cat-tab-count {
            background: var(--primary);
            color: #fff;
        }

        .cat-tabs-note {
            color: #94a3b8;
            font-size: 0.83rem;
            margin: 0 0 16px;
        }

        .cat-tabs-note b {
            color: var(--primary-dark);
        }

        @media (max-width: 575.98px) {
            .cat-tabs .nav-link {
                padding: 9px 12px;
                font-size: 0.78rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ---------- Bảng ở tab đang ẩn bị tính sai bề rộng cột, hiện ra thì tính lại ---------- */
            $('.cat-tabs a[data-toggle="pill"]').on('shown.bs.tab', function() {
                $.fn.dataTable.tables({
                    visible: true,
                    api: true
                }).columns.adjust();

                // Nhớ tab đang xem để F5 hoặc mở lại link vẫn đúng chỗ
                history.replaceState(null, '', $(this).attr('href'));
            });

            /* ---------- Mở đúng tab theo địa chỉ #tab... nếu server không chỉ định ---------- */
            var wanted = window.location.hash;

            if (@json($activeTab === 'company') && @json(array_values($tabHashes)).indexOf(wanted) !== -1) {
                $('.cat-tabs a[href="' + wanted + '"]').tab('show');
            } else if (@json($activeTab !== 'company')) {
                history.replaceState(null, '', @json($tabHashes[$activeTab]));
            }
        });
    </script>
@endsection

@section('model')
    @include('pages.category.MaterialCategory.create')
    @include('pages.category.MaterialCategory.update')
    @include('pages.category.shared.historyModal')

    @include('pages.category.DepartmentMaterial.create', [
        'mdRoute' => $dmRoute,
        'mdTitle' => $dmTitle,
        'mdIcon' => $dmIcon,
        'categories' => $dmCategories,
        'units' => $dmUnits,
        'locations' => $dmLocations,
        'unitsInUse' => $dmUnitsInUse,
        'conversions' => $dmConversions,
    ])
    @include('pages.category.DepartmentMaterial.categoryPicker', [
        'categories' => $dmCategories,
    ])
    @include('pages.category.DepartmentMaterial.update', [
        'mdRoute' => $dmRoute,
        'mdTitle' => $dmTitle,
        'mdIcon' => $dmIcon,
        'units' => $dmUnits,
        'locations' => $dmLocations,
        'unitsInUse' => $dmUnitsInUse,
        'conversions' => $dmConversions,
    ])

    @foreach (['internal' => $periodicInternalCategories, 'external' => $periodicExternalCategories] as $prType => $prCategories)
        @include('pages.category.PeriodicRequest.create', [
            'type' => $prType,
            'categories' => $prCategories,
            'units' => $periodicUnits,
            'departments' => $periodicDepartments,
            'objects' => $periodicObjects,
        ])
        @include('pages.category.PeriodicRequest.update', [
            'type' => $prType,
            'categories' => $prCategories,
            'units' => $periodicUnits,
            'departments' => $periodicDepartments,
            'objects' => $periodicObjects,
        ])
        @include('pages.category.PeriodicRequest.picker', [
            'type' => $prType,
            'categories' => $prCategories,
        ])
        @include('pages.category.PeriodicRequest.objectPicker', [
            'type' => $prType,
            'objects' => $periodicObjects,
        ])
    @endforeach
@endsection
