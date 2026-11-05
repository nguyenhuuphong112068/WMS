<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\MaterialClassification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * ĐIỀU CHỈNH THỜI GIAN ĐẶT HÀNG ở danh mục công ty (vật tư / hoá chất / chất chuẩn).
 *
 * Quyền riêng {permPrefix}lead_time (category_material_lead_time...), tách khỏi quyền Sửa danh
 * mục: modal Cập nhật không còn ô Thời gian đặt hàng, update() giữ nguyên giá trị cũ. Đổi số
 * ngày đặt hàng không đụng tới bản chất của mã nên KHÔNG đưa bản ghi về Chờ duyệt, nhưng vẫn
 * bắt lý do điều chỉnh, ghi lịch sử và Audit Trail như một lần sửa.
 *
 * Modal Thêm mới cũng không có ô này (cả vật tư / hoá chất / chất chuẩn): mã mới luôn bật cờ
 * lead_time_pending (trừ vật tư Hành Chánh) - trang chủ báo người có quyền vào khai
 * (HomeController::leadTimeReviews). Lưu ở đây thì tắt cờ.
 *
 * Lớp dùng trait phải có: hằng TABLE, LABEL, LEAD_TIME_PERMISSION; hàm writeHistory(), actor();
 * và dùng kèm trait RequiresChangeReason.
 */
trait UpdatesCategoryLeadTime
{
    public function updateLeadTime(Request $request)
    {
        if (! user_can(self::LEAD_TIME_PERMISSION)) {
            return redirect()->back()->with('error', 'Bạn không có quyền điều chỉnh thời gian đặt hàng!');
        }

        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        if (! $this->leadTimeApplies($current->purchasing_department ?? null)) {
            return redirect()->back()->with('error', 'Mã ' . $current->code . ' do Hành Chánh mua, không cần thời gian đặt hàng.');
        }

        $validator = Validator::make(
            $request->all(),
            MaterialClassification::leadTimeRules() + $this->changeReasonRules(),
            MaterialClassification::leadTimeMessages() + $this->changeReasonMessages()
        );

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'leadTimeErrors')->withInput();
        }

        $value = MaterialClassification::leadTimeValue($request->lead_time_days);
        $unchanged = $current->lead_time_days === null ? $value === null : (int) $current->lead_time_days === $value;

        if ($unchanged && ! $current->lead_time_pending) {
            return redirect()->back()->with('error', 'Thời gian đặt hàng không thay đổi nên không lưu.')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'lead_time_days' => $value,
            'lead_time_pending' => 0,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $format = fn ($days) => $days === null ? '—' : $days . ' ngày';
        $note = $unchanged
            ? 'Xác nhận thời gian đặt hàng: ' . $format($value)
            : 'Thời gian đặt hàng: ' . $format($current->lead_time_days) . ' -> ' . $format($value);

        $this->writeHistory($current->id, 'Điều chỉnh thời gian đặt hàng', $note, $this->changeReason($request));

        AuditTrialController::log('Điều chỉnh thời gian đặt hàng', self::TABLE, $current->id,
            'lead_time_days: ' . ($current->lead_time_days ?? 'NA'), 'lead_time_days: ' . ($value ?? 'NA'));

        return redirect()->back()->with('success', 'Đã cập nhật thời gian đặt hàng của mã ' . $current->code . '!');
    }

    /**
     * Mã có cần thời gian đặt hàng không. Mặc định luôn cần; danh mục vật tư ghi đè - vật tư
     * Hành Chánh mua thì không (MaterialCategoryController::leadTimeApplies).
     */
    private function leadTimeApplies(?string $purchasing): bool
    {
        return true;
    }

    /**
     * Mã cần mở sẵn hộp thoại điều chỉnh khi vào từ thẻ trang chủ (?lead_time=<id>).
     */
    private function leadTimeOpen(): ?object
    {
        $id = (int) request('lead_time');

        if ($id <= 0 || ! user_can(self::LEAD_TIME_PERMISSION)) {
            return null;
        }

        $row = DB::table(self::TABLE)->where('id', $id)->first();

        return $row && $this->leadTimeApplies($row->purchasing_department ?? null)
            ? (object) ['id' => $row->id, 'code' => $row->code, 'lead_time_days' => $row->lead_time_days]
            : null;
    }
}
