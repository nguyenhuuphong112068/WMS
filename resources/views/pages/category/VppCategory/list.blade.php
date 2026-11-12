@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DANH MỤC - VPP (1 TRANG, 2 TAB)
    |--------------------------------------------------------------------------
    | Tab 1 "Danh Mục VPP Công Ty" : bản chất của VPP (tên, nhà sản xuất, thông tin
    |                                   kỹ thuật, phân loại, bộ phận mua hàng, thời gian đặt
    |                                   hàng), dùng chung toàn công ty, có bước duyệt
    |                                   (VppCategoryController).
    | Tab 2 "VPP Của Phòng"        : cách dùng riêng của phòng ban đang chọn (phân loại phòng,
    |                                   ngưỡng tồn, định khu), không duyệt; dòng do hệ thống tạo
    |                                   theo danh mục chung, phòng chỉ sửa (DepartmentVppController).
    |
    | Hai tab dùng chung một trang nên phải tách nhau ở 3 chỗ:
    | - Biến dữ liệu : tab 2 đặt tiền tố dm (dmDatas, dmClassifications...)
    | - Bảng         : #mdTable và #dmTable (bảng thêm cần class md-table)
    | - Modal        : #createModal / #updateModal và #dmUpdateModal,
    |                  nút bấm của tab 2 chỉ ra modal của mình bằng data-modal.
    */

    // ----- Tab 1: Danh Mục VPP Công Ty -----
    $mdRoute = 'pages.category.vppCategory.';
    $mdLabel = 'danh mục VPP công ty';
    $mdTitle = 'Danh Mục VPP Công Ty';
    $mdIcon = 'fas fa-cubes';

    // ----- Tab 2: VPP Của Phòng -----
    $dmRoute = 'pages.category.departmentVpp.';
    $dmLabel = 'VPP của phòng';
    $dmTitle = 'VPP Của Phòng';
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
    | DepartmentVppController luôn kèm activeTab = 'department' lúc quay lại.
    */
    $dmHasErrors = $errors->getBag('dmCreateErrors')->any() || $errors->getBag('dmUpdateErrors')->any();

    // ----- Tab 3, 4: Danh sách đề nghị liên phòng ban / dự trù theo chu kỳ (DS đề nghị nội bộ đã bỏ 12/11/2026) (VppPeriodicRequestController) -----
    $prHasErrors = fn($type) => $errors
        ->getBag(\App\Support\VppPeriodicRequest::errorBag($type, 'Create'))
        ->any() || $errors->getBag(\App\Support\VppPeriodicRequest::errorBag($type, 'Update'))->any();

    $activeTab = match (true) {
        $dmHasErrors => 'department',
        $prHasErrors('external') => 'external',
        $prHasErrors('estimate') => 'estimate',
        in_array(session('activeTab'), ['department', 'external', 'estimate'], true) => session('activeTab'),
        default => 'company',
    };
    $activeTab = \App\Support\PageTabs::active('category_vpp', $activeTab);

    $tabHashes = [
        'company' => '#tabCompany',
        'department' => '#tabDepartment',
        'external' => '#tabPeriodicExternal',
        'estimate' => '#tabPeriodicEstimate',
    ];
@endphp

@section('mainContent')
    <div class="content-wrapper">
        <div class="md-page">

            <ul class="nav cat-tabs" role="tablist">
                @tab('category_vpp', 'company')
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'company' ? 'active' : '' }}" id="tabCompanyLink" data-toggle="pill"
                        href="#tabCompany" role="tab">
                        <i class="{{ $mdIcon }}"></i>
                        <span>Danh mục VPP công ty</span>
                        <span class="cat-tab-count">{{ $datas->count() }}</span>
                    </a>
                </li>
                @endtab
                @tab('category_vpp', 'department')
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'department' ? 'active' : '' }}" id="tabDepartmentLink"
                        data-toggle="pill" href="#tabDepartment" role="tab">
                        <i class="{{ $dmIcon }}"></i>
                        <span>VPP của phòng</span>
                        <span class="cat-tab-count">{{ $dmDatas->count() }}</span>
                    </a>
                </li>
                @endtab
                @tab('category_vpp', 'external')
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'external' ? 'active' : '' }}" id="tabPeriodicExternalLink"
                        data-toggle="pill" href="#tabPeriodicExternal" role="tab">
                        <i class="fas fa-exchange-alt"></i>
                        <span>DS đề nghị liên phòng ban theo chu kỳ</span>
                        <span class="cat-tab-count">{{ $periodicExternalLists->count() }}</span>
                    </a>
                </li>
                @endtab
                @tab('category_vpp', 'estimate')
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'estimate' ? 'active' : '' }}" id="tabPeriodicEstimateLink"
                        data-toggle="pill" href="#tabPeriodicEstimate" role="tab">
                        <i class="fas fa-calendar-check"></i>
                        <span>DS dự trù theo chu kỳ</span>
                        <span class="cat-tab-count">{{ $periodicEstimateLists->count() }}</span>
                    </a>
                </li>
                @endtab
            </ul>

            <div class="tab-content">
                @tab('category_vpp', 'company')
                <div class="tab-pane fade {{ $activeTab === 'company' ? 'show active' : '' }}" id="tabCompany"
                    role="tabpanel">
                    @include('pages.category.VppCategory.dataTable')
                </div>
                @endtab

                @tab('category_vpp', 'department')
                <div class="tab-pane fade {{ $activeTab === 'department' ? 'show active' : '' }}" id="tabDepartment"
                    role="tabpanel">
                    @include('pages.category.DepartmentVpp.dataTable', [
                        'datas' => $dmDatas,
                        'mdRoute' => $dmRoute,
                        'mdLabel' => $dmLabel,
                        'mdTitle' => $dmTitle,
                        'mdIcon' => $dmIcon,
                    ])
                </div>
                @endtab

                @tab('category_vpp', 'external')
                <div class="tab-pane fade {{ $activeTab === 'external' ? 'show active' : '' }}" id="tabPeriodicExternal"
                    role="tabpanel">
                    @include('pages.category.VppPeriodicRequest.dataTable', [
                        'type' => 'external',
                        'lists' => $periodicExternalLists,
                    ])
                </div>
                @endtab

                @tab('category_vpp', 'estimate')
                <div class="tab-pane fade {{ $activeTab === 'estimate' ? 'show active' : '' }}" id="tabPeriodicEstimate"
                    role="tabpanel">
                    @include('pages.category.VppPeriodicRequest.dataTable', [
                        'type' => 'estimate',
                        'lists' => $periodicEstimateLists,
                    ])
                </div>
                @endtab
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
    @include('pages.category.VppCategory.create')
    @include('pages.category.VppCategory.update')
    @include('pages.category.shared.historyModal')

    @include('pages.category.DepartmentVpp.update', [
        'mdRoute' => $dmRoute,
        'mdTitle' => $dmTitle,
        'mdIcon' => $dmIcon,
        'classifications' => $dmClassifications,
        'locations' => $dmLocations,
    ])

    @foreach (['external' => $periodicExternalCategories, 'estimate' => $periodicExternalCategories] as $prType => $prCategories)
        @include('pages.shared.titleDatalist', ['id' => 'prTitleOptions_' . $prType, 'titles' => $periodicTitles[$prType] ?? []])
        @include('pages.category.VppPeriodicRequest.create', [
            'type' => $prType,
            'categories' => $prCategories,
            'units' => $periodicUnits,
            'departments' => $periodicDepartments,
            'groups' => $periodicGroups,
            'objects' => $periodicObjects,
        ])
        @include('pages.category.VppPeriodicRequest.update', [
            'type' => $prType,
            'categories' => $prCategories,
            'units' => $periodicUnits,
            'departments' => $periodicDepartments,
            'groups' => $periodicGroups,
            'objects' => $periodicObjects,
        ])
        @include('pages.category.VppPeriodicRequest.picker', [
            'type' => $prType,
            'categories' => $prCategories,
            'classifications' => $dmClassifications,
        ])
        @include('pages.category.VppPeriodicRequest.objectPicker', [
            'type' => $prType,
            'objects' => $periodicObjects,
        ])
    @endforeach
@endsection
