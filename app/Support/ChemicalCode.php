<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * MÃ XUẤT NHẬP HOÁ CHẤT - một chỗ duy nhất định nghĩa cách sinh mã.
 *
 *      "C" + "-" + deparments.shortName + "-" + <đuôi ngẫu nhiên 10 ký tự>
 *      C-QC1-7KPMR9J4WD
 *
 * KHÁC mã cũ (department_id + category_id + số thứ tự 8 chữ số): mã mới KHÔNG chứa
 * số thứ tự và không gắn với danh mục hoá chất. Nhờ vậy khoá / xoá một phiếu nhập
 * không để lại "khoảng trống" nhìn thấy được qua giao diện - không có dãy số liền
 * mạch nào để mà phát hiện thiếu.
 *
 * - Đuôi ngẫu nhiên lấy từ bảng chữ Crockford Base32 đã bỏ nguyên âm (A, E, I, O, U)
 *   để không vô tình ghép thành từ có nghĩa, và bỏ các ký tự dễ đọc nhầm (I, L, O, U).
 * - 10 ký tự trên 29 ký tự => ~4,2 x 10^14 tổ hợp, xác suất trùng gần như bằng 0;
 *   vẫn còn vòng lặp sinh lại nếu trùng vì chemical_imports.code là UNIQUE.
 * - Mã sinh trong transaction lúc lưu. KHÔNG có bước xem trước trên form vì mã chỉ
 *   tồn tại sau khi đã lưu.
 *
 * Song song với App\Support\MaterialCode (mã vật tư cũng theo cách này, KIND = "M").
 * Mã ống chuẩn (App\Support\StandardCode) giữ nguyên cách sinh cũ.
 */
class ChemicalCode
{
    public const TABLE = 'chemical_imports';

    /** Phần cố định đầu mã, nhìn là biết đây là hoá chất. */
    public const KIND = 'C';

    /** Dấu ngăn giữa các phần của mã. */
    public const SEP = '-';

    /** Số ký tự ngẫu nhiên ở đuôi mã. */
    public const RANDOM_LENGTH = 10;

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

    /** Ghép mã từ mã phòng ban và đuôi ngẫu nhiên. Tách riêng để chỗ nào cũng ghép giống nhau. */
    public static function build(string $shortName, string $tail): string
    {
        return self::KIND.self::SEP.$shortName.self::SEP.$tail;
    }

    /**
     * Mã xuất nhập hoá chất kế tiếp, đã kiểm tra không trùng mã đang có.
     *
     * Gọi trong transaction của lúc lưu: sinh đuôi ngẫu nhiên, nếu vô tình trùng thì
     * sinh lại. Không đọc MAX() nên hai người nhập cùng lúc cũng không lệ thuộc thứ tự.
     */
    public static function next(string $shortName): string
    {
        do {
            $code = self::build($shortName, self::randomTail());
        } while (DB::table(self::TABLE)->where('code', $code)->exists());

        return $code;
    }
}
