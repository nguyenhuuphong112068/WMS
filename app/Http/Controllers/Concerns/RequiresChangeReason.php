<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * LÝ DO ĐIỀU CHỈNH DỮ LIỆU GỐC / DANH MỤC
 *
 * Mọi thao tác Sửa / Khoá / Mở khoá một bản ghi dữ liệu gốc hoặc danh mục bắt buộc phải
 * nhập lý do (không áp dụng cho Thêm mới và Duyệt / Từ chối duyệt).
 *
 * Cách dùng trong Controller:
 *
 *   use App\Http\Controllers\Concerns\RequiresChangeReason;
 *
 *   // Trong update(): gộp rule vào Validator đang có
 *   Validator::make($request->all(),
 *       $this->rules($id) + $this->changeReasonRules(),
 *       $this->messages() + $this->changeReasonMessages());
 *
 *   // Lấy giá trị đã trim để lưu vào lịch sử
 *   $reason = $this->changeReason($request);
 *
 *   // Trong deActive() - chỗ không có Validator sẵn - chặn nhanh:
 *   if ($stop = $this->guardChangeReason($request)) { return $stop; }
 */
trait RequiresChangeReason
{
    protected function changeReasonRules(): array
    {
        return ['change_reason' => ['required', 'string', 'max:500']];
    }

    protected function changeReasonMessages(): array
    {
        return [
            'change_reason.required' => 'Vui lòng nhập lý do điều chỉnh.',
            'change_reason.max' => 'Lý do điều chỉnh tối đa 500 ký tự.',
        ];
    }

    protected function changeReason(Request $request): string
    {
        return trim((string) $request->input('change_reason'));
    }

    /**
     * Chặn nhanh khi thiếu lý do (dùng cho deActive - không có Validator sẵn).
     *
     * @return \Illuminate\Http\RedirectResponse|null  null = hợp lệ, đi tiếp.
     */
    protected function guardChangeReason(Request $request)
    {
        $reason = $this->changeReason($request);

        if ($reason === '') {
            return redirect()->back()->with('error', 'Vui lòng nhập lý do điều chỉnh.');
        }

        if (mb_strlen($reason) > 500) {
            return redirect()->back()->with('error', 'Lý do điều chỉnh tối đa 500 ký tự.');
        }

        return null;
    }
}
