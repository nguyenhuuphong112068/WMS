<?php

namespace App\Support;

/**
 * TÌNH TRẠNG KIỂM TRA CỦA MỘT LÔ NHẬP (vật tư / hoá chất / chất chuẩn).
 *
 * Nguồn sự thật duy nhất cho cột `check_result` của material_imports,
 * chemical_imports, standard_imports. Controller, modal Xác nhận kiểm tra và cột
 * "Tình Trạng" trên sổ nhập đều đọc từ đây để nhãn/màu không lệch nhau.
 *
 *   pending : Chờ kiểm tra - vừa nhập, đang ở khu Biệt Trữ, CHƯA cộng tồn.
 *   passed  : Kiểm tra Đạt - đã nhập kho, cộng tồn, được đề nghị / sử dụng.
 *   failed  : Không đạt    - TRẢ HÀNG, không bao giờ nhập kho, không hồi lại được.
 *
 * Cờ chặn tồn kho vẫn là is_checked (1 <=> passed), xem migration
 * 2026_09_12_120000_add_check_result_to_import_tables.
 */
class CheckStatus
{
    public const PENDING = 'pending';

    public const PASSED = 'passed';

    public const FAILED = 'failed';

    /** Nhãn tiếng Việt, khoá chính là giá trị lưu ở cột `check_result`. */
    public const LABELS = [
        self::PENDING => 'Chờ kiểm tra',
        self::PASSED => 'Kiểm tra Đạt',
        self::FAILED => 'Không đạt',
    ];

    /** Class badge của Bootstrap cho từng tình trạng. */
    public const BADGES = [
        self::PENDING => 'badge-warning',
        self::PASSED => 'badge-success',
        self::FAILED => 'badge-danger',
    ];

    /** Icon FontAwesome đi kèm badge. */
    public const ICONS = [
        self::PENDING => 'fas fa-hourglass-half',
        self::PASSED => 'fas fa-circle-check',
        self::FAILED => 'fas fa-ban',
    ];

    /** Chỉ hai kết quả này được chọn ở bước Xác nhận kiểm tra. */
    public const RESULTS = [self::PASSED, self::FAILED];

    public static function label(?string $result): string
    {
        return self::LABELS[$result] ?? self::LABELS[self::PENDING];
    }

    public static function badge(?string $result): string
    {
        return self::BADGES[$result] ?? self::BADGES[self::PENDING];
    }

    public static function icon(?string $result): string
    {
        return self::ICONS[$result] ?? self::ICONS[self::PENDING];
    }

    /** Lô đã kiểm tra xong (đạt hoặc không đạt) thì không kiểm tra lại được nữa. */
    public static function isFinal(?string $result): bool
    {
        return in_array($result, [self::PASSED, self::FAILED], true);
    }

    /** Bảng tra nhãn cho lịch sử thay đổi: hiện "Kiểm tra Đạt" thay vì "passed". */
    public static function historyMap(): array
    {
        return self::LABELS;
    }
}
