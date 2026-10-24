{{--
| SỬ DỤNG VẬT TƯ - TAB "XÁC NHẬN HIỆU CHUẨN"
|
| 2 tab con: "Chờ xác nhận" (mọi phiếu chờ QA, không lọc ngày) và "Đã xác nhận" (đạt / không đạt /
| huỷ, lọc theo ngày đề nghị - cal_sub=done giữ tab con khi lọc). Cả hai mới nhất lên trước.
|
| Phiếu đề nghị hiệu chuẩn vật tư "Cần Hiệu Chuẩn Trước Khi Dùng" (App\Support\MaterialCalibration):
| phiếu của phòng đang chọn + phiếu các phòng gửi tới khi phòng đang chọn là phòng QA và người dùng có
| quyền xác nhận. Mỗi hàng là một phiếu (các mã xuất nhập cùng mã phiếu).
|
| Dữ liệu vào: $calibrationPending, $calibrationDone, $calibrationSub, $calibrationRange, $calibrationCanConfirm - xem
| MaterialCalibrationController::paneData().
--}}

@php
    $calStatusMeta = [
        'pending' => ['label' => 'Chờ QA hiệu chuẩn', 'class' => 'cal-st-pending', 'icon' => 'fas fa-hourglass-half'],
        'passed' => ['label' => 'Đạt - chờ cấp phát', 'class' => 'cal-st-passed', 'icon' => 'fas fa-check-circle'],
        'issued' => ['label' => 'Đạt - đã cấp phát', 'class' => 'cal-st-issued', 'icon' => 'fas fa-dolly'],
        'failed' => ['label' => 'Không đạt', 'class' => 'cal-st-failed', 'icon' => 'fas fa-times-circle'],
        'canceled' => ['label' => 'Đã huỷ', 'class' => 'cal-st-canceled', 'icon' => 'fas fa-ban'],
    ];
    $calTypeLabel = ['periodic' => 'Định kỳ', 'risk_assessment' => 'ĐG rủi ro', 'regular' => 'Thường quy'];
@endphp

<style>
    .cal-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cal-st-pending { background: #FFFBEB; color: #B45309; }
    .cal-st-passed { background: #F0FDF4; color: #16A34A; }
    .cal-st-issued { background: var(--primary-soft); color: var(--primary-dark); }
    .cal-st-failed { background: #FEF2F2; color: #DC2626; }
    .cal-st-canceled { background: #F1F5F9; color: #475569; }

    .cal-lot {
        font-size: 0.8rem;
        line-height: 1.6;
    }

    .cal-lot + .cal-lot {
        border-top: 1px dashed var(--primary-lighter);
    }

    .cal-dept {
        display: inline-block;
        padding: 1px 8px;
        border-radius: 999px;
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary-dark);
        font-size: 0.72rem;
        font-weight: 700;
    }

    .cal-subtabs {
        margin-bottom: 12px;
    }

    .cal-subtabs .nav-link {
        font-weight: 700;
        font-size: 0.88rem;
        color: #64748b;
        transition: color 0.2s ease;
    }

    .cal-subtabs .nav-link.active {
        color: var(--primary);
    }
</style>

<ul class="nav nav-tabs cal-subtabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link {{ $calibrationSub === 'pending' ? 'active' : '' }}" data-toggle="tab" href="#calSubPending" role="tab">
            <i class="fas fa-hourglass-half mr-1"></i> Chờ xác nhận
            <span class="badge badge-warning">{{ $calibrationPending->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $calibrationSub === 'done' ? 'active' : '' }}" data-toggle="tab" href="#calSubDone" role="tab">
            <i class="fas fa-clipboard-check mr-1"></i> Đã xác nhận
        </a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade {{ $calibrationSub === 'pending' ? 'show active' : '' }}" id="calSubPending" role="tabpanel">
        @include('pages.export.MaterialExport.calibrationTable', [
            'calRows' => $calibrationPending,
            'calTableId' => 'meCalibrationPendingTable',
            'calEmpty' => 'Không có phiếu nào chờ xác nhận hiệu chuẩn.',
        ])
    </div>

    <div class="tab-pane fade {{ $calibrationSub === 'done' ? 'show active' : '' }}" id="calSubDone" role="tabpanel">
        @include('pages.shared.rangeFilter', [
            'rfRoute' => $expRoute . 'list',
            'rfTab' => 'calibration',
            'rfPrefix' => 'cal_',
            'rfInBar' => true,
            'rfRange' => $calibrationRange,
            'rfSearch' => false,
            'rfShowPerPage' => false,
            'rfDateLabel' => 'Ngày đề nghị',
            'rfHidden' => ['cal_sub' => 'done'],
        ])

        @include('pages.export.MaterialExport.calibrationTable', [
            'calRows' => $calibrationDone,
            'calTableId' => 'meCalibrationDoneTable',
            'calEmpty' => 'Không có phiếu hiệu chuẩn nào trong khoảng ngày đã chọn.',
        ])
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mới nhất lên trước: cột Phiếu Hiệu Chuẩn có data-order = thời gian đề nghị
        ['#meCalibrationPendingTable', '#meCalibrationDoneTable'].forEach(function (id) {
            if ($.fn.DataTable.isDataTable(id)) $(id).DataTable().order([1, 'desc']).draw();
        });

        // Bảng DataTables trong tab con đang ẩn: tính lại độ rộng cột khi mở tab con
        $('.cal-subtabs a[data-toggle="tab"]').on('shown.bs.tab', function () {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
</script>
