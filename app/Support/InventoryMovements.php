<?php

namespace App\Support;

/**
 * CHI TIẾT PHÁT SINH CỦA MỘT CON SỐ TRÊN BẢNG TỒN KHO
 *
 * Mỗi cột theo kỳ trên màn hình Tồn (Tồn Đầu Kỳ / Nhập / Cân Đối / Sử Dụng / Huỷ -
 * Loại Bỏ / Tồn Cuối Kỳ) và cột Tổng Tồn chỉ hiện MỘT con số tổng. Lớp này chia các
 * phát sinh thô (nhập / cân đối / sử dụng / huỷ / chuyển) thành từng "metric" đúng
 * bằng các con số đó, phục vụ modal "chi tiết con số" ở *InventoryController::movements().
 *
 * Chỉ xử lý mảng PHP đã chuẩn hoá sẵn từ Query Builder ở controller - KHÔNG tự truy vấn.
 * Tổng của mỗi metric được cộng lại TỪ CHÍNH danh sách dòng trả ra nên luôn khớp badge.
 *
 * Mỗi metric trả kèm `columns` (mô tả cột riêng) để modal dựng bảng động: cột nhập
 * khác cột xuất, sổ cái tách Nhập (+) / Xuất (−). Xem InventoryMovements::columns().
 *
 * Cách cắt kỳ bám đúng stockByCode() của các controller:
 * - phát sinh theo ngày (date)     : so chuỗi 'Y-m-d' với from / to.
 * - phát sinh theo giờ (datetime)  : so với '<from> 00:00:00' … '<to> 23:59:59'.
 */
class InventoryMovements
{
    /** Nhãn mặc định của từng metric, controller ghi đè qua $opts['labels']. */
    private const LABELS = [
        'opening' => 'Tồn đầu kỳ',
        'period_in' => 'Nhập trong kỳ',
        'period_balanced' => 'Cân đối trong kỳ',
        'period_used' => 'Sử dụng trong kỳ',
        'period_cancelled' => 'Huỷ trong kỳ',
        'period_transferred' => 'Cấp phát liên phòng ban',
        'closing' => 'Tồn cuối kỳ',
        'stock_category' => 'Tổng tồn các lô',
        'stock_batch' => 'Tổng tồn theo lô',
    ];

    /**
     * Bộ cột hiển thị của từng metric cho một loại hàng (material | chemical | standard).
     *
     * Cột cuối luôn là con số mà dòng "Tổng" ở chân bảng cộng lại (số lượng của metric
     * dạng danh sách, số dư của sổ cái, tồn cuối kỳ của bảng Tổng Tồn).
     *
     * type: 'amount' (có màu +/-) | 'number' (số trần) | mặc định là chữ.
     * tone: 'in' (xanh) | 'out' (đỏ) | 'auto' (theo dấu, có tiền tố +/-).
     */
    public static function columns(string $profile): array
    {
        $specLabel = match ($profile) {
            'chemical' => 'Số Lô / Nhà Cung Cấp',
            'standard' => 'Số Lô / Hàm Lượng',
            default => 'Thông Tin Kỹ Thuật',
        };

        $ledger = [
            ['key' => 'at_label', 'label' => 'Thời Điểm', 'align' => 'left'],
            ['key' => 'code', 'label' => 'Chứng Từ', 'align' => 'left'],
            ['key' => 'kind', 'label' => 'Loại', 'align' => 'left'],
            ['key' => 'by', 'label' => 'Người Thực Hiện', 'align' => 'left'],
            ['key' => 'detail', 'label' => 'Diễn Giải', 'align' => 'left'],
            ['key' => 'in', 'label' => 'Nhập (+)', 'align' => 'right', 'type' => 'number', 'tone' => 'in'],
            ['key' => 'out', 'label' => 'Xuất (−)', 'align' => 'right', 'type' => 'number', 'tone' => 'out'],
            ['key' => 'balance', 'label' => 'Số Dư Sau', 'align' => 'right', 'type' => 'number'],
        ];

        $stock = [
            ['key' => 'code', 'label' => 'Mã Lô / Ống', 'align' => 'left'],
            ['key' => 'spec', 'label' => $specLabel, 'align' => 'left'],
            ['key' => 'imported_at', 'label' => 'Ngày Nhập', 'align' => 'left'],
            ['key' => 'expired_at', 'label' => 'Hạn Dùng', 'align' => 'left'],
            ['key' => 'amount', 'label' => 'Tồn Cuối Kỳ', 'align' => 'right', 'type' => 'number', 'tone' => 'in'],
        ];

        $periodIn = [
            ['key' => 'at_label', 'label' => 'Thời Điểm', 'align' => 'left'],
            ['key' => 'kind', 'label' => 'Loại', 'align' => 'left'],
            ['key' => 'code', 'label' => 'Mã Phiếu', 'align' => 'left'],
            ['key' => 'spec', 'label' => $specLabel, 'align' => 'left'],
            ['key' => 'by', 'label' => 'Người Nhập', 'align' => 'left'],
            ['key' => 'amount', 'label' => 'Số Lượng', 'align' => 'right', 'type' => 'amount', 'tone' => 'auto'],
        ];

        $balanced = [
            ['key' => 'at_label', 'label' => 'Thời Điểm', 'align' => 'left'],
            ['key' => 'code', 'label' => 'Mã Phiếu', 'align' => 'left'],
            ['key' => 'by', 'label' => 'Người Cân Đối', 'align' => 'left'],
            ['key' => 'detail', 'label' => 'Ghi Chú', 'align' => 'left'],
            ['key' => 'amount', 'label' => 'Điều Chỉnh', 'align' => 'right', 'type' => 'amount', 'tone' => 'auto'],
        ];

        $used = match ($profile) {
            'chemical' => [
                ['key' => 'at_label', 'label' => 'Ngày Sử Dụng', 'align' => 'left'],
                ['key' => 'code', 'label' => 'Mã Phiếu', 'align' => 'left'],
                ['key' => 'by', 'label' => 'Người Sử Dụng', 'align' => 'left'],
                ['key' => 'purpose', 'label' => 'Mục Đích Sử Dụng', 'align' => 'left'],
                ['key' => 'coa', 'label' => 'Số Phiếu KN', 'align' => 'left'],
                ['key' => 'amount', 'label' => 'Số Lượng', 'align' => 'right', 'type' => 'amount', 'tone' => 'out'],
            ],
            default => [ // material, standard: đều đi qua phiếu đề nghị
                ['key' => 'at_label', 'label' => 'Ngày Sử Dụng', 'align' => 'left'],
                ['key' => 'code', 'label' => 'Mã Phiếu', 'align' => 'left'],
                ['key' => 'by', 'label' => 'Người Sử Dụng', 'align' => 'left'],
                ['key' => 'request', 'label' => 'Phiếu Đề Nghị', 'align' => 'left'],
                ['key' => 'purpose', 'label' => $profile === 'standard' ? 'Mục Đích / Phép Thử' : 'Mục Đích Sử Dụng', 'align' => 'left'],
                ['key' => 'coa', 'label' => 'Số Phiếu KN', 'align' => 'left'],
                ['key' => 'amount', 'label' => 'Số Lượng', 'align' => 'right', 'type' => 'amount', 'tone' => 'out'],
            ],
        };

        $cancelled = [
            ['key' => 'at_label', 'label' => 'Ngày', 'align' => 'left'],
            ['key' => 'code', 'label' => 'Mã Phiếu', 'align' => 'left'],
            ['key' => 'by', 'label' => 'Người Thực Hiện', 'align' => 'left'],
            ['key' => 'reason', 'label' => 'Lý Do Loại Bỏ', 'align' => 'left'],
            ['key' => 'coa', 'label' => 'Số Phiếu KN', 'align' => 'left'],
            ['key' => 'amount', 'label' => 'Số Lượng', 'align' => 'right', 'type' => 'amount', 'tone' => 'out'],
        ];

        $transferred = [
            ['key' => 'at_label', 'label' => 'Ngày', 'align' => 'left'],
            ['key' => 'code', 'label' => 'Mã Phiếu', 'align' => 'left'],
            ['key' => 'to_dept', 'label' => 'Phòng Nhận', 'align' => 'left'],
            ['key' => 'reason', 'label' => 'Lý Do', 'align' => 'left'],
            ['key' => 'amount', 'label' => 'Số Lượng', 'align' => 'right', 'type' => 'amount', 'tone' => 'out'],
        ];

        return [
            'opening' => $ledger,
            'closing' => $ledger,
            'period_in' => $periodIn,
            'period_balanced' => $balanced,
            'period_used' => $used,
            'period_cancelled' => $cancelled,
            'period_transferred' => $transferred,
            'stock_category' => $stock,
            'stock_batch' => $stock,
        ];
    }

    /**
     * @param  array  $events  mỗi phần tử:
     *      [ 'lot_id' => int,
     *        'metric' => 'in'|'balanced'|'used'|'cancelled'|'transferred',
     *        'datetime' => bool,       // true = so kỳ theo giờ, false = theo ngày
     *        'at'     => 'Y-m-d H:i:s',
     *        'signed' => float,        // nhập +, cân đối +/-, xuất - huỷ - chuyển đều âm
     *        'fields' => array ]       // các ô hiển thị: kind, at_label, code, spec, by,
     *                                  //   request, purpose, reason, coa, to_dept, detail…
     * @param  array  $opts
     *      [ 'unit' => string,
     *        'want' => string[],
     *        'columns' => [metric => cột[]],     // thường lấy từ self::columns($profile)
     *        'lots' => [lot_id => ['fields'=>[...], 'batch_no'=>?string]],
     *        'batch_no' => ?string,
     *        'period_in_groups' => string[],     // metric nào tính vào "Nhập trong kỳ"
     *        'labels' => [metric => nhãn] ]
     */
    public static function metrics(array $events, string $from, string $to, array $opts): array
    {
        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';

        foreach ($events as &$event) {
            if (empty($event['datetime'])) {
                $day = substr((string) $event['at'], 0, 10);
                $event['phase'] = $day < $from ? 'before' : ($day <= $to ? 'period' : 'after');
            } else {
                $stamp = (string) $event['at'];
                $event['phase'] = $stamp < $start ? 'before' : ($stamp <= $end ? 'period' : 'after');
            }
        }
        unset($event);

        usort($events, fn ($a, $b) => strcmp((string) $a['at'], (string) $b['at']));

        $want = $opts['want'] ?? [];
        $unit = $opts['unit'] ?? '';
        $labels = ($opts['labels'] ?? []) + self::LABELS;
        $columns = $opts['columns'] ?? [];
        $inGroups = $opts['period_in_groups'] ?? ['in', 'balanced'];

        $col = fn (string $key) => $columns[$key] ?? [];
        $out = [];

        $before = array_values(array_filter($events, fn ($e) => $e['phase'] === 'before'));
        $inPeriod = array_values(array_filter($events, fn ($e) => $e['phase'] === 'period'));
        $openingTotal = round(array_sum(array_map(fn ($e) => $e['signed'], $before)), 4);

        // ---- Tồn đầu kỳ: sổ cái mọi phát sinh trước kỳ ----
        if (in_array('opening', $want, true)) {
            $balance = 0.0;
            $rows = [];
            foreach ($before as $event) {
                $balance += $event['signed'];
                $rows[] = self::ledgerRow($event, $balance);
            }
            $out['opening'] = [
                'label' => $labels['opening'], 'kind' => 'ledger', 'unit' => $unit,
                'total' => $openingTotal, 'columns' => $col('opening'), 'rows' => $rows,
            ];
        }

        // ---- Các cột phát sinh trong kỳ: danh sách phẳng ----
        $bucket = function (string $key, array $groups, bool $magnitude) use ($inPeriod, $unit, $labels, $col, &$out) {
            $rows = [];
            foreach ($inPeriod as $event) {
                if (! in_array($event['metric'], $groups, true)) {
                    continue;
                }
                $row = $event['fields'];
                $amount = round($event['signed'], 4);
                $row['amount'] = $magnitude ? abs($amount) : $amount;
                $rows[] = $row;
            }
            $out[$key] = [
                'label' => $labels[$key], 'kind' => 'list', 'unit' => $unit,
                'total' => round(array_sum(array_map(fn ($r) => $r['amount'], $rows)), 4),
                'columns' => $col($key), 'rows' => $rows,
            ];
        };

        if (in_array('period_in', $want, true)) {
            $bucket('period_in', $inGroups, false);
        }
        if (in_array('period_balanced', $want, true)) {
            $bucket('period_balanced', ['balanced'], false);
        }
        if (in_array('period_used', $want, true)) {
            $bucket('period_used', ['used'], true);
        }
        if (in_array('period_cancelled', $want, true)) {
            $bucket('period_cancelled', ['cancelled'], true);
        }
        if (in_array('period_transferred', $want, true)) {
            $bucket('period_transferred', ['transferred'], true);
        }

        // ---- Tồn cuối kỳ: sổ cái đầy đủ (đầu kỳ + mọi phát sinh trong kỳ) ----
        if (in_array('closing', $want, true)) {
            $balance = $openingTotal;
            $rows = [[
                'at_label' => '', 'code' => '', 'kind' => 'Tồn đầu kỳ', 'by' => '', 'detail' => '',
                'in' => $openingTotal > 0 ? $openingTotal : null,
                'out' => $openingTotal < 0 ? round(-$openingTotal, 4) : null,
                'amount' => $openingTotal, 'balance' => $openingTotal,
            ]];
            foreach ($inPeriod as $event) {
                $balance += $event['signed'];
                $rows[] = self::ledgerRow($event, $balance);
            }
            $out['closing'] = [
                'label' => $labels['closing'], 'kind' => 'ledger', 'unit' => $unit,
                'total' => round($balance, 4), 'columns' => $col('closing'), 'rows' => $rows,
            ];
        }

        // ---- Tổng tồn: tồn cuối kỳ của TỪNG lô (mọi phát sinh tính đến hết kỳ) ----
        $lots = $opts['lots'] ?? [];
        $stock = function (?callable $keep) use ($events, $lots): array {
            $closingByLot = [];
            foreach ($events as $event) {
                if ($event['phase'] === 'after') {
                    continue;
                }
                $closingByLot[$event['lot_id']] = ($closingByLot[$event['lot_id']] ?? 0) + $event['signed'];
            }

            $rows = [];
            $total = 0.0;
            foreach ($closingByLot as $lotId => $closing) {
                $lot = $lots[$lotId] ?? ['fields' => ['code' => (string) $lotId], 'batch_no' => null];
                if ($keep && ! $keep($lot)) {
                    continue;
                }
                $remaining = max(round($closing, 4), 0);
                $total += $remaining;
                $row = $lot['fields'] ?? [];
                $row['amount'] = $remaining;
                $row['balance'] = round($closing, 4);
                $rows[] = $row;
            }
            usort($rows, fn ($a, $b) => strcmp((string) ($a['code'] ?? ''), (string) ($b['code'] ?? '')));

            return [$rows, round($total, 4)];
        };

        if (in_array('stock_category', $want, true)) {
            [$rows, $total] = $stock(null);
            $out['stock_category'] = [
                'label' => $labels['stock_category'], 'kind' => 'stock', 'unit' => $unit,
                'total' => $total, 'columns' => $col('stock_category'), 'rows' => $rows,
            ];
        }
        if (in_array('stock_batch', $want, true)) {
            $batchNo = (string) ($opts['batch_no'] ?? '');
            [$rows, $total] = $stock(fn ($lot) => (string) ($lot['batch_no'] ?? '') === $batchNo);
            $out['stock_batch'] = [
                'label' => $labels['stock_batch'], 'kind' => 'stock', 'unit' => $unit,
                'total' => $total, 'columns' => $col('stock_batch'), 'rows' => $rows,
            ];
        }

        return $out;
    }

    /** Một dòng sổ cái: tách số phát sinh thành cột Nhập (+) / Xuất (−) và kèm số dư. */
    private static function ledgerRow(array $event, float $balance): array
    {
        $signed = round($event['signed'], 4);
        $row = $event['fields'];
        $row['in'] = $signed > 0 ? $signed : null;
        $row['out'] = $signed < 0 ? round(-$signed, 4) : null;
        $row['amount'] = $signed;
        $row['balance'] = round($balance, 4);

        return $row;
    }
}
