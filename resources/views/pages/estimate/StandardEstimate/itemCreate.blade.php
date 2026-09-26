{{--
| Modal "Thêm Chất Chuẩn Dự Trù" dạng bảng: chọn nhiều chất chuẩn từ danh mục phòng hoặc
| thêm dòng ngoài danh mục, lưu một lần - xem pages/estimate/shared/batchItemCreate.blade.php
| và StandardEstimateController::storeItem().
|
| Cột "Nhóm Chuẩn Mong Muốn" để Cung Ứng biết cần mua chuẩn chính hay chuẩn tạp; bắt buộc
| với chất chuẩn ngoài danh mục, chất chuẩn trong danh mục chỉ có một nhóm thì điền sẵn.
--}}
@include('pages.estimate.shared.batchItemCreate', [
    'emi' => [
        'kind' => 'standard',
        'title' => 'Thêm Chất Chuẩn Dự Trù',
        'icon' => 'fas fa-vial-circle-check',
        'saveLabel' => 'Lưu chất chuẩn',
        'listField' => 'standard_estimate_id',
        'itemLabel' => 'chất chuẩn',
        'itemHead' => 'Chất Chuẩn',
        'nameField' => 'standard_name',
        'techPlaceholder' => 'Độ tinh khiết, quy cách 50mg/ống',
        'purposePlaceholder' => 'Ví dụ: Đánh giá phương pháp HPLC',
        'groups' => $groups ?? config('standard.groups'),
        'thresholdUrl' => null,
        'status' => [],
        'factorUnits' => null,
    ],
])
