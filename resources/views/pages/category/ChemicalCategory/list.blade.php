@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DANH MỤC - HOÁ CHẤT (1 TRANG, 2 TAB)
    |--------------------------------------------------------------------------
    | Tab 1 "Danh Mục Hoá Chất Công Ty" : bản chất của chất, dùng chung toàn công ty,
    |                                     có bước duyệt (ChemicalCategoryController).
    | Tab 2 "Hoá Chất Của Phòng"        : cách dùng riêng của phòng ban đang chọn,
    |                                     không duyệt (DepartmentChemicalController).
    |
    | Hai tab dùng chung một trang nên phải tách nhau ở 3 chỗ:
    | - Biến dữ liệu : tab 2 đặt tiền tố dc (dcDatas, dcCategories...)
    | - Bảng         : #mdTable và #dcTable (bảng thêm cần class md-table)
    | - Modal        : #createModal / #updateModal và #dcCreateModal / #dcUpdateModal,
    |                  nút bấm của tab 2 chỉ ra modal của mình bằng data-modal.
    */

    // ----- Tab 1: Danh Mục Hoá Chất Công Ty -----
    $mdRoute = 'pages.category.chemicalCategory.';
    $mdLabel = 'danh mục hoá chất công ty';
    $mdTitle = 'Danh Mục Hoá Chất Công Ty';
    $mdIcon = 'fas fa-flask';

    // Nhóm phân loại tô đỏ để cảnh báo (hoá chất cấm)
    $mdDangerCodes = ['CAM', 'N4', 'N6'];

    // ----- Tab 2: Hoá Chất Của Phòng -----
    $dcRoute = 'pages.category.departmentChemical.';
    $dcLabel = 'hoá chất của phòng';
    $dcTitle = 'Hoá Chất Của Phòng';
    $dcIcon = 'fas fa-building-user';

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 */
    $dcNum = fn($value) => $value === null ? null : rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Đường dẫn định khu đầy đủ của một dòng, trống thì trả về null */
    $dcPath = fn($row) => $row->location_code
        ? ($row->warehouse_name ?: '—') . ' / ' . ($row->room_name ?: '—') . ' / ' . ($row->shelf_name ?: '—')
        : null;

    /*
    | Tab đang mở khi vào trang. Lưu ở tab 2 xong mà nhảy về tab 1 thì rất khó dùng,
    | nên DepartmentChemicalController luôn kèm activeTab = 'department' lúc quay lại.
    */
    $dcHasErrors = $errors->getBag('dcCreateErrors')->any() || $errors->getBag('dcUpdateErrors')->any();
    $activeTab = session('activeTab') === 'department' || $dcHasErrors ? 'department' : 'company';
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
                        <i class="{{ $dcIcon }}"></i>
                        <span>{{ $dcTitle }}</span>
                        <span class="cat-tab-count">{{ $dcDatas->count() }}</span>
                    </a>
                </li>
            </ul>

            <p class="cat-tabs-note">
                <i class="fas fa-info-circle mr-1"></i>
                <b>{{ $mdTitle }}</b> khai bản chất của chất và dùng chung toàn công ty.
                <b>{{ $dcTitle }}</b> chỉ khai phần riêng của
                <b>{{ session('user')['selected_department'] ?? 'phòng ban đang chọn' }}</b>:
                đơn vị tính, hạn dùng nội bộ, ngưỡng tồn, vị trí quy hoạch.
            </p>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'company' ? 'show active' : '' }}" id="tabCompany"
                    role="tabpanel">
                    @include('pages.category.ChemicalCategory.dataTable')
                </div>

                <div class="tab-pane fade {{ $activeTab === 'department' ? 'show active' : '' }}" id="tabDepartment"
                    role="tabpanel">
                    @include('pages.category.DepartmentChemical.dataTable', [
                        'datas' => $dcDatas,
                        'mdRoute' => $dcRoute,
                        'mdLabel' => $dcLabel,
                        'mdTitle' => $dcTitle,
                        'mdIcon' => $dcIcon,
                        'classifications' => $classifications,
                        'mdDangerCodes' => $mdDangerCodes,
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

        /* ==========================================================================
        |  Yêu cầu hiển thị riêng trang Danh Mục - Hoá Chất:
        |  1. Toàn bộ chữ trong 2 bảng (#mdTable, #dcTable) hiển thị màu ĐEN.
        |  2. Cỡ chữ trong bảng = cỡ gốc * 1.5 rồi giảm 20% (tức * 1.2 so với gốc).
        |  Giữ nguyên màu nền + màu chữ của badge/chip trạng thái (đỏ cảnh báo an
        |  toàn, xanh "Hoạt động"...) vì đó là mã màu nghiệp vụ, không phải chữ đọc.
        ========================================================================== */
        .md-page #mdTable tbody td,
        .md-page #dcTable tbody td {
            color: #000;
            font-size: 1.06rem;   /* ~1.2x cỡ ô bảng gốc (~0.88rem) */
            line-height: 1.5;
        }

        .md-page #mdTable thead th,
        .md-page #dcTable thead th {
            color: #000;
            font-size: 0.98rem;   /* 0.82rem * 1.2 */
        }

        /* Chữ phụ màu xám -> ép về đen */
        .md-page #mdTable tbody td .md-sub,
        .md-page #dcTable tbody td .md-sub,
        .md-page #mdTable tbody td .md-empty,
        .md-page #dcTable tbody td .md-empty,
        .md-page #mdTable tbody td .md-note,
        .md-page #dcTable tbody td .md-note,
        .md-page #mdTable tbody td .dc-value.is-none,
        .md-page #dcTable tbody td .dc-value.is-none {
            color: #000;
        }

        /* Cỡ chữ nhỏ trong bảng: cỡ gốc * 1.5 rồi giảm 20% */
        .md-page #mdTable tbody td .md-sub,
        .md-page #dcTable tbody td .md-sub {
            font-size: 1.03rem;   /* 0.86rem * 1.2 */
        }

        .md-page #mdTable tbody td small,
        .md-page #dcTable tbody td small,
        .md-page #mdTable tbody td .md-tag,
        .md-page #dcTable tbody td .md-tag,
        .md-page #mdTable tbody td .thr-chip,
        .md-page #dcTable tbody td .thr-chip {
            font-size: 0.96rem;   /* 0.8rem * 1.2 */
        }

        .md-page #mdTable tbody td .badge,
        .md-page #dcTable tbody td .badge,
        .md-page #mdTable tbody td .md-badge,
        .md-page #dcTable tbody td .md-badge {
            font-size: 0.9rem;    /* 0.75rem * 1.2 */
        }

        .md-page #mdTable tbody td .cat-chip,
        .md-page #dcTable tbody td .cat-chip,
        .md-page #mdTable tbody td .safety-chip,
        .md-page #dcTable tbody td .safety-chip {
            font-size: 0.89rem;   /* 0.74rem * 1.2 */
        }

        .md-page #mdTable tbody td .dc-source,
        .md-page #dcTable tbody td .dc-source {
            font-size: 0.82rem;   /* 0.68rem * 1.2 */
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
    @include('pages.category.ChemicalCategory.create')
    @include('pages.category.ChemicalCategory.update')
    @include('pages.category.ChemicalCategory.convert')
    @include('pages.category.shared.historyModal')

    @include('pages.category.DepartmentChemical.create', [
        'mdRoute' => $dcRoute,
        'mdTitle' => $dcTitle,
        'mdIcon' => $dcIcon,
        'categories' => $dcCategories,
        'locations' => $dcLocations,
        'storageConditions' => $dcStorageConditions,
        'units' => $dcUnits,
        'unitsInUse' => $dcUnitsInUse,
        'conversions' => $dcConversions,
    ])
    @include('pages.category.DepartmentChemical.update', [
        'mdRoute' => $dcRoute,
        'mdTitle' => $dcTitle,
        'mdIcon' => $dcIcon,
        'locations' => $dcLocations,
        'storageConditions' => $dcStorageConditions,
        'units' => $dcUnits,
        'unitsInUse' => $dcUnitsInUse,
        'conversions' => $dcConversions,
    ])
@endsection
