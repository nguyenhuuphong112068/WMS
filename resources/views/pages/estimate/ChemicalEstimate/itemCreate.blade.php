{{--
| Modal "Thêm Mặt Hàng Dự Trù" hoá chất dạng bảng: chọn nhiều hoá chất từ danh mục phòng
| hoặc thêm dòng ngoài danh mục, lưu một lần - xem pages/estimate/shared/batchItemCreate.blade.php
| và ChemicalEstimateController::storeItem().
--}}
@include('pages.estimate.shared.batchItemCreate', [
    'emi' => [
        'kind' => 'chemical',
        'title' => 'Thêm Mặt Hàng Dự Trù',
        'icon' => 'fas fa-flask',
        'saveLabel' => 'Lưu mặt hàng',
        'listField' => 'estimate_list_id',
        'itemLabel' => 'hoá chất',
        'itemHead' => 'Hoá Chất',
        'nameField' => 'chem_name',
        'techPlaceholder' => 'Độ tinh khiết, quy cách đóng gói',
        'purposePlaceholder' => 'Ví dụ: Pha động HPLC',
        'groups' => null,
        'thresholdUrl' => route('pages.estimate.chemicalEstimate.checkThreshold'),
        // Tồn hiện tại + đang dự trù so với ngưỡng PL IV - mã đã vượt thì khoá ở khung chọn
        'status' => $thresholdStatus ?? [],
        // Hoá chất nhóm 9 / 10 khai khác đơn vị danh mục phải có hệ số quy đổi
        'factorUnits' => $factorUnits ?? [],
    ],
])
