<?php

namespace App\Support;

/**
 * PHÂN LOẠI ĐỊNH KHU & MÀU HIỂN THỊ
 *
 * Nguồn sự thật duy nhất cho cột `zone_type` và `color` của 5 cấp định khu
 * (warehouses, shelves, columns, tiers, locations). Controller, modal khai báo,
 * bảng dữ liệu và sơ đồ ô kho đều đọc từ đây để nhãn/màu không lệch nhau.
 *
 * Năm phân loại theo trạng thái hàng đang để trong khu vực:
 *   - Dự Phòng        : hàng dự trữ, chưa đưa vào sử dụng.
 *   - Sử Dụng         : hàng đã thông qua, đang dùng cho sản xuất/kiểm nghiệm.
 *   - Biệt Trữ        : hàng mới nhập, đang chờ kiểm tra chất lượng.
 *   - Chờ Quyết Định  : hàng có kết quả bất thường, chờ kết luận xử lý.
 *   - Loại Bỏ/Chờ Huỷ : hàng không đạt, chờ tiêu huỷ.
 *
 * Màu mặc định đi theo quy ước cảnh báo quen thuộc trong kho: xanh lá = dùng được,
 * vàng = đang giữ chờ, cam = cần quyết định, đỏ = loại bỏ, xanh dương = dự phòng.
 * Người dùng vẫn được chọn màu riêng cho từng mục, màu riêng luôn thắng màu mặc định.
 */
class ZoneType
{
    /** Nhãn tiếng Việt của từng phân loại, khoá chính là giá trị lưu ở cột `zone_type`. */
    public const TYPES = [
        'reserve' => 'Dự Phòng',
        'in_use' => 'Sử Dụng',
        'quarantine' => 'Biệt Trữ',
        'pending' => 'Chờ Quyết Định',
        'rejected' => 'Loại Bỏ/Chờ Huỷ',
    ];

    /** Màu mặc định của từng phân loại, dùng khi mục định khu chưa chọn màu riêng. */
    public const DEFAULT_COLORS = [
        'reserve' => '#3B82F6',
        'in_use' => '#16A34A',
        'quarantine' => '#F59E0B',
        'pending' => '#EA580C',
        'rejected' => '#DC2626',
    ];

    /** Icon riêng của từng phân loại, dùng nhất quán ở chip trên bảng và trong modal. */
    public const ICONS = [
        'reserve' => 'fas fa-box-open',
        'quarantine' => 'fas fa-shield-halved',
        'in_use' => 'fas fa-circle-check',
        'pending' => 'fas fa-clock-rotate-left',
        'rejected' => 'fas fa-ban',
    ];

    /** Màu dùng khi mục chưa phân loại và cũng chưa chọn màu riêng. */
    public const FALLBACK_COLOR = '#2E7BC4';

    /** Bảng màu gợi ý cho ô chọn màu, để người dùng bấm nhanh thay vì mò mã hex. */
    public const PALETTE = [
        '#3B82F6', '#16A34A', '#F59E0B', '#EA580C', '#DC2626',
        '#2E7BC4', '#17B8D4', '#0F766E', '#7C3AED', '#DB2777',
        '#64748B', '#1F2937',
    ];

    /** Nhãn của một phân loại; chưa phân loại thì trả về "Chưa phân loại". */
    public static function label(?string $type): string
    {
        return self::TYPES[$type] ?? 'Chưa phân loại';
    }

    public static function icon(?string $type): string
    {
        return self::ICONS[$type] ?? 'fas fa-circle-dot';
    }

    /**
     * Màu hiển thị thật sự của một mục định khu: ưu tiên màu người dùng chọn,
     * thiếu thì lấy màu mặc định của phân loại, thiếu nốt thì dùng màu chủ đạo.
     */
    public static function color(?string $type, ?string $color = null): string
    {
        $color = trim((string) $color);

        if (self::valid($color)) {
            return strtoupper($color);
        }

        return self::DEFAULT_COLORS[$type] ?? self::FALLBACK_COLOR;
    }

    /** Mã màu có đúng dạng '#RRGGBB' hay không. */
    public static function valid(?string $color): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $color);
    }

    /**
     * Chuẩn hoá màu trước khi lưu: rỗng / sai định dạng thì trả null để bản ghi
     * bám theo màu mặc định của phân loại thay vì giữ một chuỗi rác.
     */
    public static function normalize(?string $color): ?string
    {
        $color = trim((string) $color);

        return self::valid($color) ? strtoupper($color) : null;
    }

    /**
     * Chữ đen hay chữ trắng thì đọc rõ trên nền màu đã chọn.
     * Dùng độ sáng cảm nhận (luminance) chứ không so từng kênh, để màu vàng
     * (nền sáng) ra chữ đen còn màu đỏ/tím (nền tối) ra chữ trắng.
     */
    public static function textOn(string $color): string
    {
        if (! self::valid($color)) {
            return '#FFFFFF';
        }

        $r = hexdec(substr($color, 1, 2));
        $g = hexdec(substr($color, 3, 2));
        $b = hexdec(substr($color, 5, 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#1F2937' : '#FFFFFF';
    }

    /** Bảng tra nhãn cho lịch sử thay đổi: hiện "Dự Phòng" thay vì "reserve". */
    public static function historyMap(): array
    {
        return ['' => 'Chưa phân loại'] + self::TYPES;
    }

    /**
     * Màu badge cho các mã tham chiếu tới một định khu ở màn hình KHÁC màn Định Khu (mã
     * xuất nhập, mã ống chuẩn, thẻ vị trí trên sơ đồ tồn kho...): chỉ tô màu khi định khu
     * đó đã được gắn (có $locationId) VÀ đã phân loại hoặc chọn màu riêng; còn lại giữ nền
     * trắng để không ngộ nhận là đã định khu - khác quy ước trên chính màn Định Khu (luôn
     * tô, kể cả bằng màu mặc định của phân loại) vì ở đây phần lớn bản ghi chưa từng gán
     * định khu nên tô đại một màu mặc định sẽ gây hiểu lầm.
     */
    public static function badge($locationId, ?string $type, ?string $color): array
    {
        if (! $locationId || (! $type && ! self::valid($color))) {
            return ['bg' => '#FFFFFF', 'text' => 'var(--primary-dark)'];
        }

        $bg = self::color($type, $color);

        return ['bg' => $bg, 'text' => self::textOn($bg)];
    }
}
