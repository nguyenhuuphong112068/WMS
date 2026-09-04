<?php

namespace App\Support;

/**
 * CÔNG THỨC HOÁ HỌC - chuyển đổi giữa dạng ASCII phẳng và dạng có chỉ số Unicode.
 *
 * Toàn dự án lưu công thức ở CSDL dưới dạng ký tự Unicode (H₂SO₄, CuSO₄·5H₂O) - xem
 * resources/views/pages/materData/shared/formulaInput.blade.php. Nhờ vậy bảng dữ liệu,
 * ô tìm kiếm, xuất Excel, in ấn đều hiển thị đúng mà không cần nhúng thẻ HTML.
 *
 *   toSubscript('C3H4O')  => 'C₃H₄O'      (số đứng ngay sau ký hiệu nguyên tố / ) ] )
 *   toPlain('C₃H₄O')      => 'C3H4O'      (đưa mọi chỉ số Unicode về ASCII cho tìm kiếm)
 *
 * Số đứng đầu chuỗi hoặc sau khoảng trắng / dấu * (hệ số, ví dụ "nSO3", "2H2O") KHÔNG
 * bị hạ chỉ số - giữ đúng quy tắc của nút "Tự động" trong ô nhập công thức.
 */
class ChemicalFormula
{
    /** Chỉ số dưới Unicode -> ký tự thường. */
    private const SUBSCRIPT_TO_PLAIN = [
        '₀' => '0', '₁' => '1', '₂' => '2', '₃' => '3', '₄' => '4',
        '₅' => '5', '₆' => '6', '₇' => '7', '₈' => '8', '₉' => '9',
        '₊' => '+', '₋' => '-', '₌' => '=', '₍' => '(', '₎' => ')',
    ];

    /** Chỉ số trên Unicode -> ký tự thường. */
    private const SUPERSCRIPT_TO_PLAIN = [
        '⁰' => '0', '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4',
        '⁵' => '5', '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9',
        '⁺' => '+', '⁻' => '-', '⁼' => '=', '⁽' => '(', '⁾' => ')',
    ];

    /** Ký tự thường -> chỉ số dưới Unicode. */
    private const PLAIN_TO_SUBSCRIPT = [
        '0' => '₀', '1' => '₁', '2' => '₂', '3' => '₃', '4' => '₄',
        '5' => '₅', '6' => '₆', '7' => '₇', '8' => '₈', '9' => '₉',
        '+' => '₊', '-' => '₋', '=' => '₌', '(' => '₍', ')' => '₎',
    ];

    /**
     * Đưa mọi chỉ số trên / dưới về ký tự thường (dùng cho ô tìm kiếm DataTables).
     * Đổi luôn dấu chấm giữa "·" -> "." để gõ "CuSO4.5H2O" vẫn tìm ra.
     */
    public static function toPlain(?string $value): string
    {
        return strtr(self::stripSubSup($value), ['·' => '.']);
    }

    /**
     * Hạ chỉ số các con số đứng ngay sau ký hiệu nguyên tố hoặc dấu ) ].
     * Chuẩn hoá đầu vào (bỏ chỉ số cũ, GIỮ dấu "·" của muối ngậm nước) trước khi hạ
     * lại nên gọi nhiều lần không hỏng dữ liệu.
     */
    public static function toSubscript(?string $value): string
    {
        $plain = self::stripSubSup($value);

        if ($plain === '') {
            return '';
        }

        return preg_replace_callback(
            '/(?<=[A-Za-z\)\]])(\d+)/',
            fn ($m) => strtr($m[1], self::PLAIN_TO_SUBSCRIPT),
            $plain
        );
    }

    /** Bỏ mọi chỉ số trên / dưới Unicode, giữ nguyên các ký tự khác. */
    private static function stripSubSup(?string $value): string
    {
        return strtr((string) $value, self::SUBSCRIPT_TO_PLAIN + self::SUPERSCRIPT_TO_PLAIN);
    }
}
