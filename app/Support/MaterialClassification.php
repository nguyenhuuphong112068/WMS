<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * PHÂN LOẠI VẬT TƯ CỦA DANH MỤC VẬT TƯ CÔNG TY
 *
 * Nguồn sự thật duy nhất cho cột material_categories.classification. Cột này lưu JSON
 * dạng {"price":"high","importance":"low",...} để MỘT vật tư mang nhiều cách phân loại
 * cùng lúc, mỗi tiêu chí chọn đúng một giá trị:
 *
 *   - importance     : theo mức độ quan trọng
 *   - dangerous      : có thuộc danh mục hàng hoá nguy hiểm hay không
 *   - qa_calibration : cần hiệu chuẩn trước khi cấp phát / sau khi cấp phát / không
 *   - contact        : có tiếp xúc hay không
 *
 * Bộ tiêu chí do Phòng Tổng Hợp chốt (13/11/2026): 4 tiêu chí ở danh mục công ty, mỗi tiêu chí
 * BẮT BUỘC chọn. "Theo Giá Trị" chuyển xuống danh mục vật tư của phòng (DEPARTMENT_CRITERIA,
 * cột material_department_categories.price_level) - mọi phòng đều khai.
 *
 * Tiêu chí "Theo Cấp Phát Bởi Hành Chánh" (khoá supply) ĐÃ BỎ từ 16/10/2026: vật tư do
 * Hành Chánh cấp phát chính là vật tư có Bộ Phận Mua Hàng = Hành Chánh - xem isAdminPurchased().
 * Khoá cũ chỉ còn ở RETIRED_CRITERIA để lịch sử thay đổi trước đó vẫn đọc ra chữ.
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
        'importance' => [
            'label' => 'Theo Mức Độ Quan Trọng',
            'icon' => 'fas fa-star',
            'options' => [
                'low' => 'Thấp',
                'high' => 'Cao',
            ],
        ],
        'dangerous' => [
            'label' => 'Theo Hàng Hoá Nguy Hiểm',
            'icon' => 'fas fa-triangle-exclamation',
            'options' => [
                'yes' => 'Có',
                'no' => 'Không',
            ],
        ],
        // Khoá 'yes' giữ nghĩa cũ "QA hiệu chuẩn TRƯỚC khi cấp phát" (MaterialCalibration đọc
        // needsQaCalibration); 'after' thêm 13/11/2026 - hiệu chuẩn sau khi cấp phát.
        'qa_calibration' => [
            'label' => 'Theo Cần Hiệu Chuẩn Trước Khi Dùng',
            'icon' => 'fas fa-ruler-combined',
            'options' => [
                'yes' => 'Cần HC trước khi cấp phát (HC bởi NSX / Stella)',
                'after' => 'Cần HC sau khi cấp phát',
                'no' => 'Không',
            ],
        ],
        'contact' => [
            'label' => 'Theo Tiếp Xúc',
            'icon' => 'fas fa-hand-paper',
            'options' => [
                'yes' => 'Có tiếp xúc',
                'no' => 'Không tiếp xúc',
            ],
        ],
    ];

    /**
     * Tiêu chí phân loại khai ở DANH MỤC VẬT TƯ CỦA PHÒNG (mọi phòng, bắt buộc), mỗi tiêu chí
     * một cột riêng trên material_department_categories. "Theo Giá Trị" chuyển từ danh mục công
     * ty xuống phòng ngày 13/11/2026. Khoá giữ 'price' để điều kiện trình ký đã khai
     * ({"price":"high"}) vẫn chạy - MaterialSignFlow đọc giá trị từ dòng của phòng.
     */
    public const DEPARTMENT_CRITERIA = [
        'price' => [
            'label' => 'Theo Giá Trị',
            'icon' => 'fas fa-tags',
            'column' => 'price_level',
            'options' => [
                'low' => 'Thấp',
                'high' => 'Cao',
            ],
        ],
    ];

    /**
     * Nhãn ngắn dùng cho chip trên bảng dữ liệu - tên đầy đủ vẫn hiện ở tooltip.
     * Cột trên bảng hẹp, để nguyên tên dài sẽ vỡ bố cục.
     */
    public const SHORT_LABELS = [
        'price' => ['low' => 'Giá thấp', 'high' => 'Giá cao'],
        'importance' => ['low' => 'Ít quan trọng', 'high' => 'Rất quan trọng'],
        'dangerous' => ['yes' => 'Hàng nguy hiểm', 'no' => 'Không nguy hiểm'],
        'qa_calibration' => ['yes' => 'HC trước cấp phát', 'after' => 'HC sau cấp phát', 'no' => 'Không hiệu chuẩn'],
        'contact' => ['yes' => 'Có tiếp xúc', 'no' => 'Không tiếp xúc'],
    ];

    /**
     * Những lựa chọn cần tô màu cảnh báo trên chip: hàng nguy hiểm (đỏ) và vật tư phải
     * hiệu chuẩn trước khi dùng (vàng). Còn lại dùng chip xanh như bình thường.
     */
    public const CHIP_CLASSES = [
        'dangerous' => ['yes' => 'critical'],
        'qa_calibration' => ['yes' => 'banned', 'after' => 'banned'],
    ];

    /**
     * Tiêu chí ĐÃ BỎ - không khai được nữa, decode()/encode() bỏ qua; chỉ describe() đọc để lịch
     * sử thay đổi cũ không mất chữ (migration 2026_10_16_100000_drop_supply_material_classification).
     */
    public const RETIRED_CRITERIA = [
        // Chuyển xuống danh mục vật tư của phòng 13/11/2026 (DEPARTMENT_CRITERIA)
        'price' => [
            'label' => 'Theo Giá Trị (đã chuyển về phòng)',
            'options' => ['low' => 'Thấp', 'high' => 'Cao'],
        ],
        'supply' => [
            'label' => 'Theo Cấp Phát Bởi Hành Chánh (đã bỏ)',
            'options' => ['yes' => 'Có', 'no' => 'Không', 'admin' => 'Hành Chánh', 'other' => 'Khác'],
        ],
    ];

    /** Bộ phận mua hàng Hành Chánh - vật tư này do Hành Chánh mua VÀ cấp phát (kho VPP). */
    public const PURCHASING_ADMIN = 'admin';

    /** Bộ phận chịu trách nhiệm mua vật tư này. */
    public const PURCHASING_DEPARTMENTS = [
        'supply' => 'Cung Ứng',
        'admin' => 'Hành Chánh',
    ];

    /**
     * Khoá bộ phận mua hàng ĐÃ BỎ - không chọn được nữa, chỉ giữ nhãn để lịch sử / Audit Trail
     * cũ vẫn đọc ra chữ. IT bỏ ngày 16/10/2026, dữ liệu đang dùng đã chuyển về Cung Ứng
     * (migration 2026_10_16_090000_remove_it_purchasing_department).
     */
    public const RETIRED_PURCHASING_DEPARTMENTS = [
        'it' => 'IT (đã bỏ)',
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

    /** Mô tả đầy đủ dùng cho lịch sử thay đổi và Audit Trail - đọc cả tiêu chí đã bỏ của bản ghi cũ. */
    public static function describe(?string $json): string
    {
        $parts = [];

        foreach (self::decode($json) as $key => $value) {
            $parts[] = self::CRITERIA[$key]['label'].': '.self::CRITERIA[$key]['options'][$value];
        }

        $raw = json_decode((string) $json, true);

        foreach (self::RETIRED_CRITERIA as $key => $criterion) {
            $value = is_array($raw) ? ($raw[$key] ?? null) : null;

            if (is_string($value) && isset($criterion['options'][$value])) {
                $parts[] = $criterion['label'].': '.$criterion['options'][$value];
            }
        }

        return $parts ? implode('; ', $parts) : 'Chưa phân loại';
    }

    /**
     * Chuỗi khoá "tiêu chí:giá trị" cách nhau bởi dấu cách (kể cả purchasing_department) để
     * gắn vào data-classification của dòng bảng, bộ lọc badge phía client đọc mà không dò chữ.
     */
    public static function filterKeys(?string $json, ?string $purchasing = null): string
    {
        $keys = collect(self::decode($json))->map(fn ($value, $key) => $key.':'.$value)->values();

        if ($purchasing && isset(self::PURCHASING_DEPARTMENTS[$purchasing])) {
            $keys->push('purchasing_department:'.$purchasing);
        }

        return $keys->implode(' ');
    }

    /**
     * Nhóm badge lọc theo phân loại: [['label', 'icon', 'chips' => [['key', 'short', 'class']]]],
     * cùng thứ tự với form khai báo. Nhãn chip dùng SHORT_LABELS để đứng riêng vẫn hiểu được.
     */
    public static function filterGroups(): array
    {
        return collect(self::formGroups())
            ->map(function ($group) {
                $criterion = str_replace('classification.', '', $group['errorKey']);

                return [
                    'label' => $group['label'],
                    'icon' => $group['icon'],
                    'chips' => collect($group['options'])->map(fn ($name, $value) => [
                        'key' => $criterion.':'.$value,
                        'short' => self::SHORT_LABELS[$criterion][$value] ?? $name,
                        'class' => self::CHIP_CLASSES[$criterion][$value] ?? '',
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    /** Vật tư phải được QA hiệu chuẩn ĐẠT trước khi cấp phát (luồng giữ chỗ - MaterialCalibration). */
    public static function needsQaCalibration(?string $json): bool
    {
        return (self::decode($json)['qa_calibration'] ?? null) === 'yes';
    }

    /** Vật tư "HC sau cấp phát": cấp phát bình thường, cấp xong tự gửi QA xác nhận hiệu chuẩn. */
    public static function needsQaCalibrationAfter(?string $json): bool
    {
        return (self::decode($json)['qa_calibration'] ?? null) === 'after';
    }

    /**
     * Vật tư do Hành Chánh mua - cũng là vật tư Hành Chánh cấp phát từ kho VPP (thay cho tiêu chí
     * "Theo Cấp Phát Bởi Hành Chánh" đã bỏ). Dùng chung cho mọi chỗ lọc / chặn vật tư gửi tới
     * phòng Hành Chính: Đề nghị VPP, danh sách liên phòng ban theo chu kỳ gửi Hành Chính.
     */
    public static function isAdminPurchased(?string $purchasingDepartment): bool
    {
        return $purchasingDepartment === self::PURCHASING_ADMIN;
    }

    /** Nhãn bộ phận mua hàng (kể cả khoá đã bỏ, cho lịch sử cũ), chưa khai thì trả về chuỗi rỗng. */
    public static function purchasingLabel(?string $key): string
    {
        return self::PURCHASING_DEPARTMENTS[(string) $key] ?? self::RETIRED_PURCHASING_DEPARTMENTS[(string) $key] ?? '';
    }

    /**
     * Các nhóm tiêu chí theo đúng thứ tự trên form khai báo.
     *
     * Bộ Phận Mua Hàng KHÔNG còn chọn trên form: từ khi VPP tách thành module riêng (11/2026) nó
     * cố định theo module - danh mục Vật Tư luôn Cung Ứng mua (MaterialPurchasing::DEFAULT_KEY),
     * danh mục VPP luôn Hành Chánh mua (PURCHASING_ADMIN). Cột purchasing_department vẫn giữ để
     * luồng dự trù (bộ phận mua hàng tiếp nhận) đọc chung. $purchasing giữ để không đổi chữ ký hàm.
     */
    public static function formGroups(array $selected = [], ?string $purchasing = null): array
    {
        $groups = [];

        foreach (self::CRITERIA as $key => $criterion) {
            $groups[] = [
                'label' => $criterion['label'],
                'icon' => $criterion['icon'],
                'name' => 'classification['.$key.']',
                'errorKey' => 'classification.'.$key,
                'options' => $criterion['options'],
                'value' => (string) ($selected[$key] ?? ''),
            ];
        }

        return $groups;
    }

    /** Giá trị tiêu chí cấp phòng của một dòng material_department_categories: [khoá => giá trị]. */
    public static function departmentValues($row): array
    {
        $values = [];

        foreach (self::DEPARTMENT_CRITERIA as $key => $criterion) {
            $value = is_object($row) ? ($row->{$criterion['column']} ?? null) : ($row[$criterion['column']] ?? null);

            if (is_string($value) && isset($criterion['options'][$value])) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /** Chip tiêu chí cấp phòng - cùng dạng chips(). */
    public static function departmentChips($row): array
    {
        $chips = [];

        foreach (self::departmentValues($row) as $key => $value) {
            $criterion = self::DEPARTMENT_CRITERIA[$key];
            $chips[] = [
                'short' => self::SHORT_LABELS[$key][$value] ?? $criterion['options'][$value],
                'label' => $criterion['label'].': '.$criterion['options'][$value],
                'class' => self::CHIP_CLASSES[$key][$value] ?? '',
            ];
        }

        return $chips;
    }

    /** Mô tả tiêu chí cấp phòng cho lịch sử: "Theo Giá Trị: Cao" hoặc "chưa khai". */
    public static function describeDepartment($row): string
    {
        $parts = [];

        foreach (self::departmentValues($row) as $key => $value) {
            $parts[] = self::DEPARTMENT_CRITERIA[$key]['label'].': '.self::DEPARTMENT_CRITERIA[$key]['options'][$value];
        }

        return $parts ? implode('; ', $parts) : 'chưa khai';
    }

    /** Khoá lọc "tiêu chí:giá trị" của tiêu chí cấp phòng, nối vào data-class của dòng bảng. */
    public static function departmentFilterKeys($row): string
    {
        return collect(self::departmentValues($row))->map(fn ($value, $key) => $key.':'.$value)->implode(' ');
    }

    /** Ô chọn + badge lọc của tiêu chí cấp phòng (giống formGroups() / filterGroups()). */
    public static function departmentFormGroups($row = null): array
    {
        $values = $row ? self::departmentValues($row) : [];
        $groups = [];

        foreach (self::DEPARTMENT_CRITERIA as $key => $criterion) {
            $groups[] = [
                'key' => $key,
                'label' => $criterion['label'],
                'icon' => $criterion['icon'],
                'name' => $criterion['column'],
                'errorKey' => $criterion['column'],
                'options' => $criterion['options'],
                'value' => (string) ($values[$key] ?? ''),
                'chips' => collect($criterion['options'])->map(fn ($name, $value) => [
                    'key' => $key.':'.$value,
                    'short' => self::SHORT_LABELS[$key][$value] ?? $name,
                    'class' => self::CHIP_CLASSES[$key][$value] ?? '',
                ])->values()->all(),
            ];
        }

        return $groups;
    }

    /** Validate tiêu chí cấp phòng (bắt buộc). $prefix cho form nhiều dòng, vd 'items.*.'. */
    public static function departmentRules(string $prefix = ''): array
    {
        $rules = [];

        foreach (self::DEPARTMENT_CRITERIA as $criterion) {
            $rules[$prefix.$criterion['column']] = ['required', Rule::in(array_keys($criterion['options']))];
        }

        return $rules;
    }

    public static function departmentMessages(string $prefix = ''): array
    {
        $messages = [];

        foreach (self::DEPARTMENT_CRITERIA as $criterion) {
            $messages[$prefix.$criterion['column'].'.required'] = 'Vui lòng chọn tiêu chí "'.$criterion['label'].'".';
            $messages[$prefix.$criterion['column'].'.in'] = 'Lựa chọn của tiêu chí "'.$criterion['label'].'" không hợp lệ.';
        }

        return $messages;
    }

    /** Quy tắc validate cho phần phân loại + thời gian đặt hàng. */
    public static function rules(): array
    {
        $rules = [
            'classification' => ['required', 'array'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_LEAD_TIME_DAYS],
        ];

        foreach (self::CRITERIA as $key => $criterion) {
            $rules['classification.'.$key] = ['required', Rule::in(array_keys($criterion['options']))];
        }

        return $rules;
    }

    public static function messages(): array
    {
        $messages = [
            'classification.required' => 'Vui lòng chọn đủ các tiêu chí phân loại.',
            'lead_time_days.integer' => 'Thời gian đặt hàng phải là số ngày nguyên.',
            'lead_time_days.min' => 'Thời gian đặt hàng không được âm.',
            'lead_time_days.max' => 'Thời gian đặt hàng tối đa '.self::MAX_LEAD_TIME_DAYS.' ngày.',
        ];

        foreach (self::CRITERIA as $key => $criterion) {
            $messages['classification.'.$key.'.required'] = 'Vui lòng chọn tiêu chí "'.$criterion['label'].'".';
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
