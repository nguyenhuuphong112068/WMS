<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * PHÂN LOẠI DANH MỤC VPP CÔNG TY - 5 NHÓM DỰ TRÙ
 *
 * Nguồn sự thật duy nhất cho cột vpp_categories.classification. Khác Vật Tư (4 tiêu chí giá
 * trị / quan trọng / nguy hiểm / hiệu chuẩn - App\Support\MaterialClassification), VPP chỉ
 * chia theo CÁCH DỰ TRÙ, mỗi mã thuộc đúng một nhóm (người dùng chốt 12/11/2026):
 *
 *   1 : dự trù định kỳ hàng tháng, số lượng nhiều
 *   2 : dự trù định kỳ hàng tháng, số lượng ít
 *   3 : dự trù định kỳ hàng quý, số lượng nhiều
 *   4 : dự trù định kỳ hàng quý, số lượng ít
 *   5 : dự trù khi có nhu cầu
 *
 * Cột vẫn lưu JSON {"group":"1"} và lớp này giữ đúng bộ hàm của MaterialClassification
 * (decode / encode / chips / summary / describe / filterKeys / filterGroups / formGroups /
 * rules / messages) để mọi màn hình VPP - bảng danh mục, bộ lọc Tồn Kho, điều kiện trình ký
 * (VppSignFlow) - đọc chung một đường như trước. Phân loại cũ theo 4 tiêu chí của Vật Tư chỉ
 * còn được describe() đọc lại cho lịch sử thay đổi (RETIRED_CRITERIA).
 *
 * VPP không có tiêu chí "Cần hiệu chuẩn" nên needsQaCalibration() luôn false - luồng QA hiệu
 * chuẩn trước khi cấp phát không áp cho VPP.
 */
class VppClassification
{
    public const GROUP_KEY = 'group';

    /** Một tiêu chí duy nhất - khoá mảng là khoá lưu trong JSON, không đổi khi đã có dữ liệu. */
    public const CRITERIA = [
        self::GROUP_KEY => [
            'label' => 'Nhóm Dự Trù',
            'icon' => 'fas fa-layer-group',
            'options' => [
                '1' => 'Nhóm 1 - Dự trù hàng tháng, số lượng nhiều',
                '2' => 'Nhóm 2 - Dự trù hàng tháng, số lượng ít',
                '3' => 'Nhóm 3 - Dự trù hàng quý, số lượng nhiều',
                '4' => 'Nhóm 4 - Dự trù hàng quý, số lượng ít',
                '5' => 'Nhóm 5 - Dự trù khi có nhu cầu',
            ],
        ],
    ];

    /** Nhãn ngắn cho chip trên bảng - tên đầy đủ hiện ở tooltip. */
    public const SHORT_LABELS = [
        self::GROUP_KEY => [
            '1' => 'Nhóm 1 · Tháng, nhiều',
            '2' => 'Nhóm 2 · Tháng, ít',
            '3' => 'Nhóm 3 · Quý, nhiều',
            '4' => 'Nhóm 4 · Quý, ít',
            '5' => 'Nhóm 5 · Khi cần',
        ],
    ];

    /** VPP không có lựa chọn cần tô màu cảnh báo. */
    public const CHIP_CLASSES = [];

    /**
     * Tiêu chí ĐÃ BỎ: bộ 4 tiêu chí kiểu Vật Tư VPP dùng trước 12/11/2026. Chỉ describe() đọc
     * để lịch sử thay đổi cũ không mất chữ (migration 2026_11_12_090000).
     */
    public const RETIRED_CRITERIA = [
        'price' => ['label' => 'Theo Giá Trị', 'options' => ['low' => 'Thấp', 'high' => 'Cao']],
        'importance' => ['label' => 'Theo Mức Độ Quan Trọng', 'options' => ['low' => 'Thấp', 'high' => 'Cao']],
        'dangerous' => ['label' => 'Theo Hàng Hoá Nguy Hiểm', 'options' => ['yes' => 'Có', 'no' => 'Không']],
        'qa_calibration' => ['label' => 'Theo Cần Hiệu Chuẩn Trước Khi Dùng', 'options' => ['yes' => 'Có', 'no' => 'Không']],
    ];

    /** VPP luôn do Hành Chánh mua và cấp phát (kho VPP). */
    public const PURCHASING_ADMIN = MaterialClassification::PURCHASING_ADMIN;

    /** Đọc cột JSON thành mảng [tiêu chí => giá trị], bỏ hết khoá / giá trị lạ. */
    public static function decode(?string $json): array
    {
        $decoded = json_decode((string) $json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $result = [];

        foreach (self::CRITERIA as $key => $criterion) {
            $value = isset($decoded[$key]) ? (string) $decoded[$key] : null;

            if ($value !== null && isset($criterion['options'][$value])) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /** Gom lựa chọn từ form thành chuỗi JSON để lưu; không chọn gì thì null (chưa phân loại). */
    public static function encode($input): ?string
    {
        $input = is_array($input) ? $input : [];
        $result = [];

        foreach (self::CRITERIA as $key => $criterion) {
            $value = isset($input[$key]) ? (string) $input[$key] : null;

            if ($value !== null && isset($criterion['options'][$value])) {
                $result[$key] = $value;
            }
        }

        return $result ? json_encode($result, JSON_UNESCAPED_UNICODE) : null;
    }

    /** Số nhóm (1-5) của một mã, chưa phân loại thì null. */
    public static function groupOf(?string $json): ?int
    {
        $value = self::decode($json)[self::GROUP_KEY] ?? null;

        return $value === null ? null : (int) $value;
    }

    /** Danh sách chip để hiện trên bảng: [['short', 'label', 'class'], ...]. */
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

    /** Một dòng ngắn gọn cho ô gợi ý / bản in: "Nhóm 1 · Tháng, nhiều". */
    public static function summary(?string $json): string
    {
        $parts = [];

        foreach (self::decode($json) as $key => $value) {
            $parts[] = self::SHORT_LABELS[$key][$value] ?? self::CRITERIA[$key]['options'][$value];
        }

        return implode(', ', $parts);
    }

    /** Mô tả đầy đủ cho lịch sử thay đổi và Audit Trail - đọc cả phân loại cũ (4 tiêu chí) của bản ghi trước. */
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
                $parts[] = $criterion['label'].' (cũ): '.$criterion['options'][$value];
            }
        }

        return $parts ? implode('; ', $parts) : 'Chưa phân loại';
    }

    /**
     * Chuỗi khoá "tiêu chí:giá trị" cách nhau bởi dấu cách để gắn vào data-classification của
     * dòng bảng, bộ lọc phía client đọc mà không dò chữ. $purchasing giữ để cùng chữ ký với Vật Tư.
     */
    public static function filterKeys(?string $json, ?string $purchasing = null): string
    {
        return collect(self::decode($json))->map(fn ($value, $key) => $key.':'.$value)->values()->implode(' ');
    }

    /** Nhóm badge lọc theo phân loại: [['label', 'icon', 'chips' => [['key', 'short', 'class']]]]. */
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

    /** VPP không có tiêu chí cần hiệu chuẩn. */
    public static function needsQaCalibration(?string $json): bool
    {
        return false;
    }

    public static function isAdminPurchased(?string $purchasingDepartment): bool
    {
        return MaterialClassification::isAdminPurchased($purchasingDepartment);
    }

    public static function purchasingLabel(?string $key): string
    {
        return MaterialClassification::purchasingLabel($key);
    }

    /** Các nhóm tiêu chí theo đúng thứ tự trên form khai báo. $purchasing giữ để cùng chữ ký với Vật Tư. */
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

    public static function rules(): array
    {
        // Chỉ bắt lỗi ở từng tiêu chí - bắt cả mảng nữa thì câu báo lỗi bị lặp hai lần
        $rules = ['classification' => ['nullable', 'array']];

        foreach (self::CRITERIA as $key => $criterion) {
            $rules['classification.'.$key] = ['required', Rule::in(array_map('strval', array_keys($criterion['options'])))];
        }

        return $rules;
    }

    public static function messages(): array
    {
        $messages = ['classification.array' => 'Nhóm dự trù không hợp lệ.'];

        foreach (self::CRITERIA as $key => $criterion) {
            $messages['classification.'.$key.'.required'] = 'Vui lòng chọn "'.$criterion['label'].'".';
            $messages['classification.'.$key.'.in'] = 'Lựa chọn "'.$criterion['label'].'" không hợp lệ.';
        }

        return $messages;
    }
}
