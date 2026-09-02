@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DANH MỤC - CHẤT CHUẨN (1 TRANG, 2 TAB)
    |--------------------------------------------------------------------------
    | Tab 1 "Danh Mục Chất Chuẩn Công Ty" : bản chất của chất chuẩn, dùng chung toàn
    |                                        công ty, có bước duyệt (StandardCategoryController).
    | Tab 2 "Chất Chuẩn Của Phòng"        : cách dùng riêng của phòng ban đang chọn,
    |                                        không duyệt (DepartmentStandardController).
    |
    | Hai tab dùng chung một trang nên phải tách nhau ở 3 chỗ:
    | - Biến dữ liệu : tab 2 đặt tiền tố ds (dsDatas, dsCategories...)
    | - Bảng         : #mdTable và #dsTable (bảng thêm cần class md-table)
    | - Modal        : #createModal / #updateModal và #dsCreateModal / #dsUpdateModal,
    |                  nút bấm của tab 2 chỉ ra modal của mình bằng data-modal.
    */

    // ----- Tab 1: Danh Mục Chất Chuẩn Công Ty -----
    $mdRoute = 'pages.category.standardCategory.';
    $mdLabel = 'danh mục chất chuẩn công ty';
    $mdTitle = 'Danh Mục Chất Chuẩn Công Ty';
    $mdIcon = 'fas fa-vial-circle-check';

    // ----- Tab 2: Chất Chuẩn Của Phòng -----
    $dsRoute = 'pages.category.departmentStandard.';
    $dsLabel = 'chất chuẩn của phòng';
    $dsTitle = 'Chất Chuẩn Của Phòng';
    $dsIcon = 'fas fa-building-user';

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 */
    $dsNum = fn($value) => $value === null ? null : rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Đường dẫn định khu đầy đủ của một dòng, trống thì trả về null */
    $dsPath = fn($row) => $row->location_code
        ? ($row->warehouse_name ?: '—') . ' / ' . ($row->room_name ?: '—') . ' / ' . ($row->shelf_name ?: '—')
        : null;

    /** Chuỗi JSON mã nhóm chuẩn -> mảng mã, dùng chung cho cả hai tab. */
    $sdGroupsOf = function ($value) {
        $codes = json_decode($value ?? '', true);

        return is_array($codes) ? $codes : [];
    };

    /*
    | Tab đang mở khi vào trang. Lưu ở tab 2 xong mà nhảy về tab 1 thì rất khó dùng,
    | nên DepartmentStandardController luôn kèm activeTab = 'department' lúc quay lại.
    */
    $dsHasErrors = $errors->getBag('dsCreateErrors')->any() || $errors->getBag('dsUpdateErrors')->any();
    $activeTab = session('activeTab') === 'department' || $dsHasErrors ? 'department' : 'company';
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
                        <i class="{{ $dsIcon }}"></i>
                        <span>{{ $dsTitle }}</span>
                        <span class="cat-tab-count">{{ $dsDatas->count() }}</span>
                    </a>
                </li>
            </ul>

            <p class="cat-tabs-note">
                <i class="fas fa-info-circle mr-1"></i>
                <b>{{ $mdTitle }}</b> khai bản chất của chất chuẩn (tên, số CAS, nguồn gốc, version,
                phân nhóm chuẩn) và dùng chung toàn công ty.
                <b>{{ $dsTitle }}</b> chỉ khai phần riêng của
                <b>{{ session('user')['selected_department'] ?? 'phòng ban đang chọn' }}</b>:
                đơn vị tính, hạn dùng nội bộ sau khi mở ống, ngưỡng tồn, vị trí quy hoạch.
            </p>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'company' ? 'show active' : '' }}" id="tabCompany"
                    role="tabpanel">
                    @include('pages.category.StandardCategory.dataTable')
                </div>

                <div class="tab-pane fade {{ $activeTab === 'department' ? 'show active' : '' }}" id="tabDepartment"
                    role="tabpanel">
                    @include('pages.category.DepartmentStandard.dataTable', [
                        'datas' => $dsDatas,
                        'mdRoute' => $dsRoute,
                        'mdLabel' => $dsLabel,
                        'mdTitle' => $dsTitle,
                        'mdIcon' => $dsIcon,
                        'groups' => $groups,
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

            if (!@json($activeTab === 'department') && (wanted === '#tabCompany' || wanted === '#tabDepartment')) {
                $('.cat-tabs a[href="' + wanted + '"]').tab('show');
            }
        });
    </script>
@endsection

@section('model')
    @include('pages.category.StandardCategory.create')
    @include('pages.category.StandardCategory.update')
    @include('pages.category.shared.historyModal')

    @include('pages.category.DepartmentStandard.create', [
        'mdRoute' => $dsRoute,
        'mdTitle' => $dsTitle,
        'mdIcon' => $dsIcon,
        'categories' => $dsCategories,
        'locations' => $dsLocations,
        'storageConditions' => $dsStorageConditions,
        'units' => $dsUnits,
        'unitsInUse' => $dsUnitsInUse,
        'conversions' => $dsConversions,
    ])
    @include('pages.category.DepartmentStandard.update', [
        'mdRoute' => $dsRoute,
        'mdTitle' => $dsTitle,
        'mdIcon' => $dsIcon,
        'locations' => $dsLocations,
        'storageConditions' => $dsStorageConditions,
        'units' => $dsUnits,
        'unitsInUse' => $dsUnitsInUse,
        'conversions' => $dsConversions,
    ])
@endsection
