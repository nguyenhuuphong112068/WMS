<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\Signer;
use Illuminate\Http\RedirectResponse;

/**
 * 21 CFR Part 11 §11.200(a)(1) - chữ ký điện tử phi sinh trắc phải gồm ít nhất
 * hai thành phần định danh. Thành phần 1 là phiên đăng nhập (userId trong session),
 * thành phần 2 là mật khẩu người dùng nhập lại ngay tại thời điểm ký.
 *
 * Controller nào có bước "Trình ký / Ký duyệt / Từ chối / Phê duyệt" thì use trait
 * này rồi gọi guardSignature() trước khi ghi thay đổi.
 */
trait VerifiesSignature
{
    /**
     * @param  mixed   $password   Chuỗi mật khẩu, hoặc Request để tự lấy field 'sign_password'.
     * @param  string  $table      Tên bảng nghiệp vụ (cho Audit Trail).
     * @param  mixed   $recordId   Khoá bản ghi đang ký.
     * @param  string  $action     Nhãn thao tác, hiện trong thông báo lỗi + Audit Trail.
     * @return RedirectResponse|null  null = mật khẩu đúng, đi tiếp; RedirectResponse = sai, return luôn.
     */
    protected function guardSignature($password, string $table, $recordId, string $action): ?RedirectResponse
    {
        if (! is_string($password) && $password !== null) {
            $password = $password->input('sign_password');
        }

        if (Signer::verifyPassword($password)) {
            return null;
        }

        AuditTrialController::log(
            'Xác thực chữ ký thất bại',
            $table,
            $recordId ?: 0,
            'Thao tác: '.$action,
            'Nhập sai mật khẩu xác nhận khi ký/duyệt'
        );

        return redirect()->back()
            ->with('error', 'Mật khẩu xác nhận không đúng. Thao tác "'.$action.'" đã bị huỷ để bảo đảm chữ ký điện tử.')
            ->with('signatureError', $action)
            ->withInput();
    }
}
