<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Người thực hiện thao tác + xác thực chữ ký điện tử.
 *
 * - actor()          : chuỗi ghi vào created_by / updated_by / *_signed_by ...
 *                      Định dạng "Họ Tên (userName)" để vừa dễ đọc vừa truy vết
 *                      được về đúng một tài khoản duy nhất (userName là unique).
 * - verifyPassword() : kiểm tra mật khẩu người đang đăng nhập nhập lại khi ký
 *                      duyệt (thành phần thứ 2 của chữ ký điện tử - 21 CFR 11.200).
 *                      Luôn đọc hash mới nhất từ DB, KHÔNG dùng mật khẩu trong session.
 */
class Signer
{
    /** Chuỗi định danh người thực hiện: "Họ Tên (userName)". */
    public static function actor(): string
    {
        $user = session('user');

        if (! is_array($user)) {
            return 'NA';
        }

        $fullName = trim((string) ($user['fullName'] ?? ''));
        $userName = trim((string) ($user['userName'] ?? ''));

        if ($userName === '') {
            return $fullName !== '' ? $fullName : 'NA';
        }

        return $fullName !== '' ? $fullName.' ('.$userName.')' : $userName;
    }

    /** Chỉ userName - dùng khi cần khoá đúng một tài khoản (audit, lọc "của tôi"). */
    public static function userName(): string
    {
        return (string) (session('user')['userName'] ?? 'NA');
    }

    /**
     * Xác thực mật khẩu người đang đăng nhập để ký duyệt.
     * Đọc hash trực tiếp từ user_management theo userId trong session.
     */
    public static function verifyPassword(?string $plainPassword): bool
    {
        $plainPassword = (string) $plainPassword;

        if ($plainPassword === '') {
            return false;
        }

        $userId = session('user')['userId'] ?? null;

        if (! $userId) {
            return false;
        }

        $hash = DB::table('user_management')->where('id', $userId)->value('passWord');

        return $hash ? Hash::check($plainPassword, $hash) : false;
    }
}
