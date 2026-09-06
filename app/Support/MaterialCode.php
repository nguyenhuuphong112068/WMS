<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * MÃ LÔ VẬT TƯ - một chỗ duy nhất định nghĩa cách sinh mã.
 *
 *      "M" + "-" + deparments.id (đệm 0 cho đủ 2 chữ số) + "-" + <đuôi ngẫu nhiên 10 ký tự>
 *      M-07-7KPMR9J4WD
 *
 * Dùng id phòng ban thay cho shortName để mọi mã dài BẰNG NHAU (luôn 15 ký tự):
 * shortName do người dùng tự nhập, dài ngắn tuỳ ý và có thể chứa dấu "-" trùng với
 * dấu ngăn. id là số, cố định, không bao giờ đổi sau khi tạo phòng ban.
 *
 * KHÁC mã cũ (shortName + "VT" + yy + mm + số thứ tự 4 chữ số): mã mới KHÔNG chứa
 * số thứ tự. Khoá / xoá một phiếu nhập không để lại "khoảng trống" nhìn thấy được
 * qua giao diện - không còn dãy số liền mạch nào để phát hiện thiếu.
 *
 * - Đuôi ngẫu nhiên lấy từ bảng chữ Crockford Base32 đã bỏ nguyên âm (A, E, I, O, U)
 *   để không vô tình ghép thành từ có nghĩa, và bỏ ký tự dễ đọc nhầm (I, L, O, U).
 * - Mã sinh trong transaction lúc lưu, có vòng lặp sinh lại nếu trùng vì
 *   material_imports.code là UNIQUE.
 * - KHÔNG còn xem trước mã trên form: mã chỉ tồn tại sau khi đã lưu.
 *
 * Song song với App\Support\ChemicalCode (KIND = "C"). Mã ống chuẩn
 * (App\Support\StandardCode) giữ nguyên cách sinh cũ.
 */
class MaterialCode
{
    public const TABLE = 'material_imports';

    /** Phần cố định đầu mã, nhìn là biết đây là vật tư. */
    public const KIND = 'M';

    /** Dấu ngăn giữa các phần của mã. */
    public const SEP = '-';

    /** Số ký tự ngẫu nhiên ở đuôi mã. */
    public const RANDOM_LENGTH = 10;

    /** Số chữ số của phần id phòng ban trong mã (đệm 0 về đúng độ dài này). */
    public const DEPT_LENGTH = 2;

    /** Crockford Base32 bỏ nguyên âm A, E (đã sẵn không có I, L, O, U). */
    private const ALPHABET = '0123456789BCDFGHJKMNPQRSTVWXYZ';

    /** Một đuôi ngẫu nhiên độ dài RANDOM_LENGTH. */
    public static function randomTail(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $tail = '';

        for ($i = 0; $i < self::RANDOM_LENGTH; $i++) {
            $tail .= self::ALPHABET[random_int(0, $max)];
        }

        return $tail;
    }

    /** Phần phòng ban trong mã: id đệm 0 về đúng DEPT_LENGTH chữ số. */
    public static function deptCode(int $departmentId): string
    {
        return str_pad((string) $departmentId, self::DEPT_LENGTH, '0', STR_PAD_LEFT);
    }

    /** Ghép mã từ id phòng ban và đuôi ngẫu nhiên. */
    public static function build(int $departmentId, string $tail): string
    {
        return self::KIND.self::SEP.self::deptCode($departmentId).self::SEP.$tail;
    }

    /**
     * Mã lô vật tư kế tiếp, đã kiểm tra không trùng mã đang có.
     *
     * Gọi trong transaction của lúc lưu: sinh đuôi ngẫu nhiên, trùng thì sinh lại.
     */
    public static function next(int $departmentId): string
    {
        do {
            $code = self::build($departmentId, self::randomTail());
        } while (DB::table(self::TABLE)->where('code', $code)->exists());

        return $code;
    }
}
