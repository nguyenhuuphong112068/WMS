<?php

namespace App\Support;

/**
 * BIỂU ĐỒ NHẬP - XUẤT - TỒN THEO KỲ BÁO CÁO
 *
 * Dùng chung cho các màn hình Tồn (hoá chất, vật tư...). Mỗi màn hình tự viết 3 câu
 * truy vấn của mình - tên bảng và tên cột mốc thời gian mỗi nơi một khác:
 *
 *      Hoá chất : chemical_imports.imported_date | chemical_exports.exported_date
 *                 | chemical_balancings.balancing_at
 *      Vật tư   : material_imports.imported_date | material_exports.created_at
 *                 | material_balancings.balancing_at
 *
 * rồi đưa vào đây dưới dạng danh sách bản ghi đã đổi tên cột về chuẩn chung:
 *
 *      nhập     : {at, amount}
 *      xuất     : {at, amount, type}   type = 'export' (sử dụng) | 'cancel' (huỷ / loại bỏ)
 *      cân đối  : {at, amount}         amount là SỐ ĐIỀU CHỈNH (+/-)
 *
 * Danh sách phải lấy tới HẾT ngày cuối kỳ, kể cả các phát sinh trước kỳ: phần trước kỳ
 * chính là tồn đầu kỳ.
 *
 * Đặt ở đây thay vì trong Controller để hai màn hình không mỗi nơi một cách chia mốc rồi
 * lệch nhau. Lớp này chỉ tính toán trên dữ liệu đã lấy sẵn, không tự hỏi DB.
 */
class InventoryChart
{
    /** Kỳ dài tới ngần này ngày thì vẫn xem được từng ngày. */
    private const DAY_LIMIT = 31;

    /** Dài hơn thì gộp theo tuần, tới ngần này ngày (khoảng 6 tháng) thì chuyển sang tháng. */
    private const WEEK_LIMIT = 186;

    /**
     * Số liệu của cả biểu đồ: tồn đầu kỳ, tổng phát sinh trong kỳ và từng mốc thời gian.
     *
     * Mỗi mốc có nhập / cân đối / sử dụng / huỷ của riêng mốc đó, kèm TỒN CUỐI MỐC cộng
     * dồn từ tồn đầu kỳ:
     *
     *      tồn cuối mốc = tồn cuối mốc trước + nhập + cân đối - sử dụng - huỷ
     *
     * @param  array  $period  Kỳ báo cáo của màn hình: ['from' => 'Y-m-d', 'to' => 'Y-m-d']
     * @param  iterable  $imports  Các lần nhập {at, amount}
     * @param  iterable  $exports  Các lần xuất {at, amount, type}
     * @param  iterable  $balancings  Các lần cân đối {at, amount}
     */
    public static function series(array $period, $imports, $exports, $balancings): array
    {
        $buckets = self::buckets($period['from'], $period['to']);

        // [ngày => số thứ tự mốc chứa ngày đó], để xếp phát sinh vào mốc mà không phải dò
        $indexOf = [];
        $points = [];

        foreach ($buckets['items'] as $index => $bucket) {
            $cursor = \Carbon\Carbon::parse($bucket['from']);
            $stop = \Carbon\Carbon::parse($bucket['to']);

            while ($cursor->lte($stop)) {
                $indexOf[$cursor->format('Y-m-d')] = $index;
                $cursor->addDay();
            }

            $points[] = [
                'label' => $bucket['label'],
                'range' => $bucket['range'],
                'imported' => 0.0,
                'balanced' => 0.0,
                'used' => 0.0,
                'cancelled' => 0.0,
                'closing' => 0.0,
            ];
        }

        // Tồn đầu kỳ = mọi phát sinh TRƯỚC ngày bắt đầu kỳ, cộng dần ở ba vòng lặp dưới
        $opening = 0.0;

        foreach ($imports as $row) {
            $date = substr((string) $row->at, 0, 10);
            $amount = (float) $row->amount;

            if ($date < $period['from']) {
                $opening += $amount;
            } elseif (isset($indexOf[$date])) {
                $points[$indexOf[$date]]['imported'] += $amount;
            }
        }

        foreach ($balancings as $row) {
            $date = substr((string) $row->at, 0, 10);
            $amount = (float) $row->amount;

            if ($date < $period['from']) {
                $opening += $amount;
            } elseif (isset($indexOf[$date])) {
                $points[$indexOf[$date]]['balanced'] += $amount;
            }
        }

        foreach ($exports as $row) {
            $date = substr((string) $row->at, 0, 10);
            $amount = (float) $row->amount;
            $key = $row->type === 'cancel' ? 'cancelled' : 'used';

            if ($date < $period['from']) {
                $opening -= $amount;
            } elseif (isset($indexOf[$date])) {
                $points[$indexOf[$date]][$key] += $amount;
            }
        }

        $totals = ['imported' => 0.0, 'balanced' => 0.0, 'used' => 0.0, 'cancelled' => 0.0];
        $running = $opening;

        foreach ($points as $index => $point) {
            $running += $point['imported'] + $point['balanced'] - $point['used'] - $point['cancelled'];

            $points[$index]['closing'] = round($running, 4);

            foreach ($totals as $key => $total) {
                $totals[$key] = round($total + $point[$key], 4);
                $points[$index][$key] = round($point[$key], 4);
            }
        }

        return [
            'bucket' => $buckets['unit'],
            'bucket_label' => $buckets['label'],
            'opening' => round($opening, 4),
            'closing' => round($running, 4),
            'totals' => $totals,
            'points' => $points,
        ];
    }

    /**
     * Chia kỳ báo cáo thành các mốc của biểu đồ.
     *
     * Kỳ ngắn xem từng ngày cho chi tiết, kỳ dài gộp lại để trục ngang không dày đặc:
     * <= 31 ngày -> ngày, <= 6 tháng -> tuần, dài hơn -> tháng. Mốc đầu và mốc cuối bị
     * cắt đúng theo ngày bắt đầu / kết thúc kỳ nên tổng các mốc luôn bằng số của cả kỳ.
     *
     * @return array{unit: string, label: string, items: array<int, array{label: string, range: string, from: string, to: string}>}
     */
    public static function buckets(string $from, string $to): array
    {
        $start = \Carbon\Carbon::parse($from)->startOfDay();
        $end = \Carbon\Carbon::parse($to)->startOfDay();
        $days = (int) $start->diffInDays($end) + 1;

        [$unit, $label] = $days <= self::DAY_LIMIT
            ? ['day', 'Theo ngày']
            : ($days <= self::WEEK_LIMIT ? ['week', 'Theo tuần'] : ['month', 'Theo tháng']);

        $items = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if ($unit === 'day') {
                $stop = $cursor->copy();
                $text = $cursor->format('d/m');
            } elseif ($unit === 'week') {
                $stop = $cursor->copy()->endOfWeek()->startOfDay();
                $text = $cursor->format('d/m');
            } else {
                $stop = $cursor->copy()->endOfMonth()->startOfDay();
                $text = $cursor->format('m/Y');
            }

            if ($stop->gt($end)) {
                $stop = $end->copy();
            }

            $items[] = [
                'label' => $text,
                'range' => $cursor->format('d/m/Y')
                    .($stop->ne($cursor) ? ' - '.$stop->format('d/m/Y') : ''),
                'from' => $cursor->format('Y-m-d'),
                'to' => $stop->format('Y-m-d'),
            ];

            $cursor = $stop->copy()->addDay();
        }

        return ['unit' => $unit, 'label' => $label, 'items' => $items];
    }
}
