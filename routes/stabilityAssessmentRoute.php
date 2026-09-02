<?php

/*
|--------------------------------------------------------------------------
| NHÓM MENU: ĐÁNH GIÁ HẠN DÙNG
|--------------------------------------------------------------------------
| Controller : app/Http/Controllers/Pages/StabilityAssessment/...
| View       : resources/views/pages/stabilityAssessment/...
| Tên route  : pages.stabilityAssessment.<chứcNăng>.<action>
|
| standardStability : Đánh Giá Hạn Dùng Chất Chuẩn. Mỗi ống chuẩn đang tồn được lập
|                     một phiếu theo dõi độ ổn định (đầu phiếu: ngày bắt đầu + chu kỳ),
|                     phiếu gồm nhiều mốc đánh giá T0 / T3 / T6...
|
| assessmentPlan    : Kế Hoạch Đánh Giá. Xem NGƯỢC LẠI theo thời gian - gom mọi mốc
|                     đánh giá của mọi phiếu còn hiệu lực, lọc theo khoảng "từ ngày -
|                     đến ngày". Trang chỉ đọc nên chỉ có một action index.
|
| Phiếu chạy hết các mốc mới "Hoàn Thành"; giữa chừng có thể NGƯNG (trạng thái "Dừng
| Đánh Giá") khi một mốc Không Đạt hoặc khi người dùng chủ động ngưng kèm lý do -
| "resume" là mở lại phiếu đã ngưng để đánh giá tiếp. "issueTestings" ghi việc đã cấp
| phát chuẩn cho từng chỉ tiêu kiểm của một mốc.
|
| Phiếu không xoá cứng, chỉ chuyển trạng thái "Huỷ" nên hành động là "cancel" thay cho
| "deActive" của các nhóm khác (bảng này không có cột status_id, trạng thái nằm ở cột
| status dạng chữ). Riêng mốc đánh giá chưa có kết quả thì xoá được vì nó mới chỉ là
| nội dung đang soạn của phiếu.
|
| Mọi lần thêm / sửa / xoá mốc và mọi thay đổi đầu phiếu đều ghi một dòng nhật ký
| standard_stability_assessment_histories gắn với id của phiếu.
*/

use App\Http\Controllers\Pages\StabilityAssessment\AssessmentPlanController;
use App\Http\Controllers\Pages\StabilityAssessment\StandardStabilityController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/stabilityAssessment')
    ->name('pages.stabilityAssessment.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/standardStability')->name('standardStability.')->controller(StandardStabilityController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('detail', 'detail')->name('detail');

            // Đầu phiếu
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('cancel', 'cancel')->name('cancel');
            Route::post('resume', 'resume')->name('resume');

            // Mốc đánh giá
            Route::post('storeItem', 'storeItem')->name('storeItem');
            Route::post('updateItem', 'updateItem')->name('updateItem');
            Route::post('deleteItem', 'deleteItem')->name('deleteItem');
            Route::post('assess', 'assess')->name('assess');
            Route::post('issueTestings', 'issueTestings')->name('issueTestings');
        });

        // Kế hoạch đánh giá - chỉ đọc, khoảng thời gian truyền qua query from_date / to_date
        Route::prefix('/assessmentPlan')->name('assessmentPlan.')->controller(AssessmentPlanController::class)->group(function () {
            Route::get('', 'index')->name('list');
        });
    });
