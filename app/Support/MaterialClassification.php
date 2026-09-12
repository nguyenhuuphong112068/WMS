<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * PHÂN LOẠI VẬT TƯ CỦA DANH MỤC VẬT TƯ CÔNG TY
 *
 * Nguồn sự thật duy nhất cho cột material_categories.classification. Cột này lưu JSON
 * dạng {"price":"high","importance":"a",...} để MỘT vật tư mang nhiều cách phân loại
 * cùng lúc, mỗi tiêu chí chọn đúng một giá trị:
 *
 *   - price          : theo giá
 *   - importance     : theo mức độ quan trọng
 *   - supply         : Hành Chánh cấp phát hay Khác
 *   - dangerous      : có thuộc danh mục hàng hoá nguy hiểm hay không
 *   - qa_calibration : có cần QA hiệu chuẩn trước khi sử dụng hay không
 *
 * Trước đây phân loại là dữ liệu gốc của từng phòng (bảng material_classifications, cột
 * material_department_categories.classification_id). Nhưng "vật tư đắt hay rẻ, có nguy
 * hiểm không, có phải hiệu chuẩn không" là BẢN CHẤT của vật tư - hai phòng khai lệch nhau
 * thì một phòng sai - nên phân loại chuyển hẳn về danh mục chung của công ty.
 *
 * BỘ PHẬN MUA HÀNG (material_categories.purchasing_department) và THỜI GIAN ĐẶT HÀNG
 * (lead_time_days) cũng khai ở danh mục công ty vì cùng lý do đó. Riêng lead_time_days
 * còn được dùng chung cho danh mục hoá chất / chất chuẩn.
 */
class MaterialClassification
{
    /**
     * Các tiêu chí phân loại, theo đúng thứ tự hiển thị trên modal khai báo và trên bảng.
     * Khoá mảng chính là khoá lưu trong JSON, không đổi khi đã có dữ liệu.
     */
    public const CRITERIA = [
        'price' => [
            'label' => 'Theo Giá',
            'icon' => 'fas fa-tags',
            'options' => [
                'high' => 'Giá trị cao',
                'medium' => 'Giá trị trung bình',
                'low' => 'Giá trị thấp',
            ],
        ],
        'importance' => [
            'label' => 'Theo Mức Độ Quan Trọng',
            'icon' => 'fas fa-star',
            'options' => [
                'a' => 'A - Rất quan trọng',
                'b' => 'B - Quan trọng',
                'c' => 'C - Bình thường',
            ],
        ],
        'supply' => [
            'label' => 'Nguồn Cấp Phát',
            'icon' => 'fas fa-hand-holding',
            'options' => [
                'admin' => 'Hành Chánh Cấp Phát',
                'other' => 'Khác',
            ],
        ],
        'dangerous' => [
            'label' => 'Hàng Hoá Nguy Hiểm',
            'icon' => 'fas fa-triangle-exclamation',
            'options' => [
                'yes' => 'Thuộc danh mục hàng hoá nguy hiểm',
                'no' => 'Không',
            ],
        ],
        'qa_calibration' => [
            'label' => 'Hiệu Chuẩn Trước Khi Sử Dụng',
            'icon' => 'fas fa-ruler-combined',
            'options' => [
                'yes' => 'Cần QA hiệu chuẩn trước khi sử dụng',
                'no' => 'Không',
            ],
        ],
    ];

    /**
     * Nhãn ngắn dùng cho chip trên bảng dữ liệu - tên đầy đủ vẫn hiện ở tooltip.
     * Cột trên bảng hẹp, để nguyên tên dài sẽ vỡ bố cục.
     */
    public const SHORT_LABELS = [
        'price' => ['high' => 'Giá cao', 'medium' => 'Giá TB', 'low' => 'Giá thấp'],
        'importance' => ['a' => 'Quan trọng A', 'b' => 'Quan trọng B', 'c' => 'Quan trọng C'],
        'supply' => ['admin' => 'HC cấp phát', 'other' => 'Nguồn khác'],
        'dangerous' => ['yes' => 'Hàng nguy hiểm', 'no' => 'Không nguy hiểm'],
        'qa_calibration' => ['yes' => 'Cần hiệu chuẩn', 'no' => 'Không hiệu chuẩn'],
    ];

    /**
     * Những lựa chọn cần tô màu cảnh báo trên chip: hàng nguy hiểm (đỏ) và vật tư phải
     * hiệu chuẩn trước khi dùng (vàng). Còn lại dùng chip xanh như bình thường.
     */
    public const CHIP_CLASSES = [
        'dangerous' => ['yes' => 'critical'],
        'qa_calibration' => ['yes' => 'banned'],
    ];

    /** Bộ phận chịu trách nhiệm mua vật tư này. */
    public const PURCHASING_DEPARTMENTS = [
        'admin' => 'Hành Chánh',
        'supply' => 'Cung Ứng',
        'it' => 'IT',
    ];

    /** Số ngày đặt hàng tối đa cho phép khai - chặn gõ nhầm 3650 thành 36500. */
    public const MAX_LEAD_TIME_DAYS = 3650;

    /** Đọc cột JSON thành mảng [tiêu chí => giá trị], bỏ hết khoá / giá trị lạ. */
    public static function decode(?string $json): array
    {
        $decoded = json_decode((string) $json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $result = [];

        foreach (self::CRITERIA as $key => $criterion) {
            $value = $decoded[$key] ?? null;

            if (is_string($value) && isset($criterion['options'][$value])) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Gom lựa chọn từ form thành chuỗi JSON để lưu.
     *
     * Luôn đi theo thứ tự khai báo của CRITERIA để chuỗi JSON của cùng một nội dung không
     * đổi - nếu không, so sánh cũ / mới lúc ghi lịch sử sẽ báo thay đổi giả.
     * Không chọn tiêu chí nào thì trả về null (chưa phân loại).
     */
    public static function encode($input): ?string
    {
        $input = is_array($input) ? $input : [];
        $result = [];

        foreach (self::CRITERIA as $key => $criterion) {
            $value = $input[$key] ?? null;

            if (is_string($value) && isset($criterion['options'][$value])) {
                $result[$key] = $value;
            }
        }

        return $result ? json_encode($result, JSON_UNESCAPED_UNICODE) : null;
    }

    /**
     * Danh sách chip để hiện trên bảng: [['short', 'label', 'class'], ...].
     */
    public static function chips(?string $json): array
    {
        $chips = [];

        foreach (self::decode($json) as $key => $value) {
            $chips[] = [
                'short' => self::SHORT_LABELS[$key][$value] ?? self::CRITERIA[$key]['options'][$value],
                'label' => self::CRITERIA[$key]['label'].': '.self::CRITERIA[$key]['options'][$value],
                'class' => self::CHIP_CLASSES[$key][$value] ?? '',
            ];
        }

        return $chips;
    }

    /** Một dòng ngắn gọn cho ô gợi ý / bản in: "Giá cao, Quan trọng A, Hàng nguy hiểm". */
    public static function summary(?string $json): string
    {
        $parts = [];

        foreach (self::decode($json) as $key => $value) {
            $parts[] = self::SHORT_LABELS[$key][$value] ?? self::CRITERIA[$key]['options'][$value];
        }

        return implode(', ', $parts);
    }

    /** Mô tả đầy đủ dùng cho lịch sử thay đổi và Audit Trail. */
    public static function describe(?string $json): string
    {
        $parts = [];

        foreach (self::decode($json) as $key => $value) {
            $parts[] = self::CRITERIA[$key]['label'].': '.self::CRITERIA[$key]['options'][$value];
        }

        return $parts ? implode('; ', $parts) : 'Chưa phân loại';
    }

    /** Nhãn bộ phận mua hàng, chưa khai thì trả về chuỗi rỗng. */
    public static function purchasingLabel(?string $key): string
    {
        return self::PURCHASING_DEPARTMENTS[(string) $key] ?? '';
    }

    /** Quy tắc validate cho phần phân loại + bộ phận mua hàng + thời gian đặt hàng. */
    public static function rules(): array
    {
        $rules = [
            'classification' => ['nullable', 'array'],
            'purchasing_department' => ['nullable', Rule::in(array_keys(self::PURCHASING_DEPARTMENTS))],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_LEAD_TIME_DAYS],
        ];

        foreach (self::CRITERIA as $key => $criterion) {
            $rules['classification.'.$key] = ['nullable', Rule::in(array_keys($criterion['options']))];
        }

        return $rules;
    }

    public static function messages(): array
    {
        $messages = [
            'purchasing_department.in' => 'Bộ phận mua hàng không hợp lệ.',
            'lead_time_days.integer' => 'Thời gian đặt hàng phải là số ngày nguyên.',
            'lead_time_days.min' => 'Thời gian đặt hàng không được âm.',
            'lead_time_days.max' => 'Thời gian đặt hàng tối đa '.self::MAX_LEAD_TIME_DAYS.' ngày.',
        ];

        foreach (self::CRITERIA as $key => $criterion) {
            $messages['classification.'.$key.'.in'] = 'Lựa chọn của tiêu chí "'.$criterion['label'].'" không hợp lệ.';
        }

        return $messages;
    }

    /** Quy tắc riêng cho danh mục hoá chất / chất chuẩn: chỉ có thời gian đặt hàng. */
    public static function leadTimeRules(): array
    {
        return [
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_LEAD_TIME_DAYS],
        ];
    }

    public static function leadTimeMessages(): array
    {
        return [
            'lead_time_days.integer' => 'Thời gian đặt hàng phải là số ngày nguyên.',
            'lead_time_days.min' => 'Thời gian đặt hàng không được âm.',
            'lead_time_days.max' => 'Thời gian đặt hàng tối đa '.self::MAX_LEAD_TIME_DAYS.' ngày.',
        ];
    }

    /** Giá trị lead_time_days sạch để ghi DB: rỗng -> null, còn lại ép về số nguyên. */
    public static function leadTimeValue($value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }
}
