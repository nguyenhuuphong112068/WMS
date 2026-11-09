<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * XUẤT EXCEL ĐƠN DỰ TRÙ ĐÃ PHÊ DUYỆT - tab "Đơn dự trù đã được phê duyệt" (bộ phận mua hàng)
 * ở Dự Trù Vật Tư / Hoá Chất / Chất Chuẩn.
 *
 * - Xuất 1 phiếu (nút Excel trên dòng) hoặc nhiều phiếu đã tick chọn vào CÙNG một file.
 * - Sheet "Chi tiết": mỗi dòng = một mục x một tháng cần. Mục đã huỷ (status_id = 0) không xuất.
 * - Nhiều phiếu: dòng xếp theo mã hàng, thêm cột "Tổng SL Theo Mã" (gộp ô) cộng số lượng các mục
 *   cùng mã hàng + cùng ĐVT; kèm sheet "Tổng hợp theo mã". Mục ngoài danh mục gộp theo tên.
 *
 * Lớp dùng trait cần có EstimateItemCancel (cancelConfig, isPurchasingViewer), hằng TABLE /
 * AMOUNT_TABLE và khai exportConfig():
 *   [
 *     'amount_fk'  => cột trỏ về mục trên bảng số lượng (vd. 'material_estimate_item_id'),
 *     'title'      => 'DỰ TRÙ VẬT TƯ', 'file_prefix' => 'DuTruVatTu',
 *     'code_label' => 'Mã Vật Tư', 'name_label' => 'Vật Tư',
 *     'extras'     => [tiêu đề cột => biểu thức select] cột riêng của từng loại hàng,
 *   ]
 */
trait EstimateApprovedExport
{
    abstract protected function exportConfig(): array;

    public function exportApproved(Request $request)
    {
        $ids = collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()->back()->with('error', 'Vui lòng chọn ít nhất một đơn dự trù để xuất Excel!')->with('activeTab', 'approved');
        }

        if (! $this->isPurchasingViewer($this->departmentId())) {
            return redirect()->back()->with('error', 'Bạn không có quyền xuất Excel đơn dự trù đã được phê duyệt!');
        }

        $lists = DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->leftJoin('companies', 'deparments.company_id', '=', 'companies.id')
            ->select(
                self::TABLE.'.*',
                'deparments.shortName as department_short_name',
                'companies.short_name as company_short_name'
            )
            ->whereIn(self::TABLE.'.id', $ids->all())
            ->where(self::TABLE.'.app_status', 'approved')
            ->orderBy(self::TABLE.'.year', 'desc')
            ->orderBy(self::TABLE.'.month', 'desc')
            ->orderBy(self::TABLE.'.code', 'asc')
            ->get()
            ->keyBy('id');

        if ($lists->isEmpty()) {
            return redirect()->back()->with('error', 'Không tìm thấy đơn dự trù đã phê duyệt cần xuất!')->with('activeTab', 'approved');
        }

        $rows = $this->exportRows($lists);
        $multi = $lists->count() > 1;
        $cfg = $this->exportConfig();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arimo')->setSize(11);
        $this->fillDetailSheet($spreadsheet->getActiveSheet(), $lists, $rows, $multi);

        if ($multi) {
            $this->fillSummarySheet($spreadsheet->createSheet(), $lists, $rows);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $fileName = $cfg['file_prefix'].'_'.($multi ? $lists->count().'phieu' : $lists->first()->code).'_'.now()->format('Ymd_His').'.xlsx';

        AuditTrialController::log('Xuất Excel', self::TABLE, $multi ? 0 : $lists->first()->id, null,
            'Xuất Excel đơn dự trù đã phê duyệt: '.$lists->pluck('code')->implode(', '));

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Mỗi mục còn hiệu lực x mỗi dòng số lượng theo tháng. Mục chưa khai số lượng vẫn xuất 1 dòng
     * trống số lượng. Gắn group_key (mã hàng, ngoài danh mục thì theo tên) để cộng tổng.
     */
    private function exportRows($lists)
    {
        $cfg = $this->cancelConfig();
        $exp = $this->exportConfig();
        $item = self::ITEM_TABLE;
        $cat = $cfg['category_table'];

        $select = [
            $item.'.*',
            $cat.'.code as category_code',
            $cfg['name_table'].'.name as category_name',
            'manufacturers.name as manufacturer_name',
            'manufacturers.short_name as manufacturer_short_name',
            DB::raw($item.'.'.$cfg['manual_name'].' as manual_name'),
        ];

        foreach (array_values($exp['extras']) as $i => $expr) {
            $select[] = DB::raw($expr.' as extra_'.$i);
        }

        $items = DB::table($item)
            ->leftJoin($cat, $item.'.category_id', '=', $cat.'.id')
            ->leftJoin($cfg['name_table'], $cat.'.'.$cfg['name_fk'], '=', $cfg['name_table'].'.id')
            ->leftJoin('manufacturers', $cat.'.manufacturers_id', '=', 'manufacturers.id')
            ->select($select)
            ->whereIn($item.'.'.$cfg['item_fk'], $lists->keys()->all())
            ->where($item.'.active', 1)
            ->where($item.'.status_id', 1)
            ->orderBy($item.'.id', 'asc')
            ->get();

        $amounts = DB::table(self::AMOUNT_TABLE)
            ->leftJoin('units', self::AMOUNT_TABLE.'.unit_id', '=', 'units.id')
            ->select(self::AMOUNT_TABLE.'.*', 'units.short_name as unit_short_name', 'units.name as unit_name')
            ->where(self::AMOUNT_TABLE.'.active', 1)
            ->whereIn(self::AMOUNT_TABLE.'.'.$exp['amount_fk'], $items->pluck('id')->all())
            ->orderBy(self::AMOUNT_TABLE.'.for_month_year', 'asc')
            ->get()
            ->groupBy($exp['amount_fk']);

        $rows = collect();

        foreach ($items as $it) {
            $list = $lists->get($it->{$cfg['item_fk']});
            $name = $it->category_id ? $it->category_name : $it->manual_name;
            $groupKey = $it->category_id && $it->category_code
                ? 'code:'.$it->category_code
                : 'name:'.mb_strtolower(trim((string) $name));

            $base = [
                'list' => $list,
                'group_key' => $groupKey,
                'code' => $it->category_id ? ($it->category_code ?: '') : '',
                'name' => (string) $name,
                'outside' => ! $it->category_id,
                'manufacturer' => $it->manufacturer_short_name ?: ($it->manufacturer_name ?: ''),
                'extras' => array_map(fn ($i) => (string) ($it->{'extra_'.$i} ?? ''), array_keys(array_values($exp['extras']))),
                'technical' => (string) $it->technical_information,
                'purpose' => (string) $it->purpose,
                'expected' => $it->expected_delivery_date,
                'status' => $this->exportItemStatus($it),
            ];

            $itemAmounts = $amounts->get($it->id, collect());

            if ($itemAmounts->isEmpty()) {
                $rows->push($base + ['month' => null, 'amount' => null, 'unit' => '', 'unit_key' => '', 'promised' => $it->promised_date]);

                continue;
            }

            foreach ($itemAmounts as $amount) {
                $rows->push($base + [
                    'month' => $amount->for_month_year,
                    'amount' => (float) $amount->amount,
                    'unit' => (string) ($amount->unit_short_name ?: $amount->unit_name),
                    'unit_key' => (string) $amount->unit_id,
                    'promised' => $amount->promised_date ?: $it->promised_date,
                ]);
            }
        }

        return $rows;
    }

    private function exportItemStatus($item): string
    {
        if ($item->fulfilled_date) {
            return 'Đã giao '.\Carbon\Carbon::parse($item->fulfilled_date)->format('d/m/Y');
        }

        if ($item->cancel_requester_at && ! $item->cancel_purchasing_at) {
            return 'Chờ xác nhận huỷ';
        }

        return 'Chưa giao';
    }

    private function fillDetailSheet(Worksheet $sheet, $lists, $rows, bool $multi): void
    {
        $exp = $this->exportConfig();
        $sheet->setTitle('Chi tiết');

        $headers = array_merge(
            ['STT', 'Bộ Phận', 'Mã Phiếu', 'Kỳ Dự Trù', $exp['code_label'], $exp['name_label'], 'NSX'],
            array_keys($exp['extras']),
            ['Thông Tin Kỹ Thuật', 'Mục Đích Sử Dụng', 'Tháng Cần', 'Số Lượng', 'ĐVT', 'Ngày Mong Muốn', 'Ngày Hẹn Đáp Ứng', 'Tình Trạng']
        );

        if ($multi) {
            $headers[] = 'Tổng SL Theo Mã';
            // Cùng mã hàng nằm liền nhau để cộng tổng và gộp ô
            $rows = $rows->sortBy([
                fn ($a, $b) => strnatcasecmp($a['code'] ?: "\u{FFFF}".$a['name'], $b['code'] ?: "\u{FFFF}".$b['name']),
                fn ($a, $b) => strcmp($a['unit'], $b['unit']),
                fn ($a, $b) => strcmp($a['list']->code, $b['list']->code),
                fn ($a, $b) => strcmp((string) $a['month'], (string) $b['month']),
            ])->values();
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $first = $lists->first();

        $sheet->setCellValue('A1', 'DANH SÁCH '.$exp['title'].' ĐÃ PHÊ DUYỆT');
        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', $lists->count() > 1
            ? 'Gồm '.$lists->count().' phiếu: '.$lists->pluck('code')->implode(', ')
            : 'Phiếu '.$first->code.' - '.$this->exportDeptLabel($first).' - Kỳ '.$this->exportPeriod($first));
        $sheet->mergeCells('A2:'.$lastCol.'2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);

        $sheet->setCellValue('A3', 'Ngày xuất: '.now()->format('d/m/Y H:i').' - Người xuất: '.$this->actor());
        $sheet->mergeCells('A3:'.$lastCol.'3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headRow = 5;
        $sheet->fromArray($headers, null, 'A'.$headRow);

        $r = $headRow + 1;
        $amountCol = array_search('Số Lượng', $headers, true) + 1;
        $sumCol = count($headers);

        foreach ($rows as $i => $row) {
            $values = array_merge(
                [
                    $i + 1,
                    $this->exportDeptLabel($row['list']),
                    $row['list']->code,
                    $this->exportPeriod($row['list']),
                    $row['code'] ?: 'Ngoài danh mục',
                    $row['name'],
                    $row['manufacturer'],
                ],
                $row['extras'],
                [
                    $row['technical'],
                    $row['purpose'],
                    $row['month'] ? \Carbon\Carbon::parse($row['month'])->format('m/Y') : '',
                    $row['amount'],
                    $row['unit'],
                    $row['expected'] ? \Carbon\Carbon::parse($row['expected'])->format('d/m/Y') : '',
                    $row['promised'] ? \Carbon\Carbon::parse($row['promised'])->format('d/m/Y') : '',
                    $row['status'],
                ]
            );

            foreach ($values as $c => $value) {
                $sheet->setCellValueExplicit(
                    [$c + 1, $r],
                    $value ?? '',
                    is_int($value) || is_float($value)
                        ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                        : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }

            $r++;
        }

        $lastRow = max($r - 1, $headRow);
        $this->exportStyleTable($sheet, $headRow, $lastRow, $lastCol);

        // Cột tổng: cộng số lượng các dòng liền nhau cùng mã hàng + cùng ĐVT, gộp ô theo nhóm
        if ($multi && $rows->isNotEmpty()) {
            $start = 0;
            $count = $rows->count();

            for ($i = 1; $i <= $count; $i++) {
                $prev = $rows[$i - 1];
                $same = $i < $count && $rows[$i]['group_key'] === $prev['group_key'] && $rows[$i]['unit_key'] === $prev['unit_key'];

                if ($same) {
                    continue;
                }

                $fromRow = $headRow + 1 + $start;
                $toRow = $headRow + $i;
                $amountLetter = Coordinate::stringFromColumnIndex($amountCol);
                $sumLetter = Coordinate::stringFromColumnIndex($sumCol);

                $sheet->setCellValue($sumLetter.$fromRow, '=SUM('.$amountLetter.$fromRow.':'.$amountLetter.$toRow.')');

                if ($toRow > $fromRow) {
                    $sheet->mergeCells($sumLetter.$fromRow.':'.$sumLetter.$toRow);
                }

                // Viền đậm phía dưới mỗi nhóm mã hàng cho dễ phân biệt
                $sheet->getStyle('A'.$toRow.':'.$lastCol.$toRow)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

                $start = $i;
            }

            $sumLetter = Coordinate::stringFromColumnIndex($sumCol);
            $sheet->getStyle($sumLetter.($headRow + 1).':'.$sumLetter.$lastRow)->getFont()->setBold(true);
            $sheet->getStyle($sumLetter.($headRow + 1).':'.$sumLetter.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($sumLetter.($headRow + 1).':'.$sumLetter.$lastRow)->getNumberFormat()->setFormatCode('#,##0.###');
        }

        $sheet->getStyle(Coordinate::stringFromColumnIndex($amountCol).($headRow + 1).':'.Coordinate::stringFromColumnIndex($amountCol).$lastRow)
            ->getNumberFormat()->setFormatCode('#,##0.###');

        $widths = array_merge(
            [6, 14, 16, 10, 13, 40, 14],
            array_fill(0, count($exp['extras']), 16),
            [30, 30, 10, 11, 9, 14, 14, 16]
        );
        if ($multi) {
            $widths[] = 14;
        }
        foreach ($widths as $c => $w) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c + 1))->setWidth($w);
        }

        $sheet->freezePane('A'.($headRow + 1));
        $sheet->setAutoFilter('A'.$headRow.':'.$lastCol.$lastRow);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(0);
    }

    /** Sheet "Tổng hợp theo mã": mỗi dòng = một mã hàng + ĐVT, cộng số lượng của mọi phiếu đã chọn. */
    private function fillSummarySheet(Worksheet $sheet, $lists, $rows): void
    {
        $exp = $this->exportConfig();
        $sheet->setTitle('Tổng hợp theo mã');

        $groups = $rows->groupBy(fn ($row) => $row['group_key'].'|'.$row['unit_key'])
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'code' => $first['code'] ?: 'Ngoài danh mục',
                    'sort' => $first['code'] ?: "\u{FFFF}".$first['name'],
                    'name' => $first['name'],
                    'manufacturer' => $first['manufacturer'],
                    'unit' => $first['unit'],
                    'total' => $group->sum(fn ($row) => (float) $row['amount']),
                    'lists' => $group->pluck('list.code')->unique()->values(),
                    'depts' => $group->map(fn ($row) => $this->exportDeptLabel($row['list']))->unique()->values(),
                ];
            })
            ->sortBy('sort', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $headers = ['STT', $exp['code_label'], $exp['name_label'], 'NSX', 'ĐVT', 'Tổng Số Lượng', 'Số Phiếu', 'Bộ Phận', 'Mã Phiếu'];
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));

        $sheet->setCellValue('A1', 'TỔNG HỢP '.$exp['title'].' THEO MÃ HÀNG');
        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Gồm '.$lists->count().' phiếu: '.$lists->pluck('code')->implode(', '));
        $sheet->mergeCells('A2:'.$lastCol.'2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);

        $headRow = 4;
        $sheet->fromArray($headers, null, 'A'.$headRow);

        $r = $headRow + 1;
        foreach ($groups as $i => $g) {
            $sheet->setCellValue('A'.$r, $i + 1);
            $sheet->setCellValueExplicit('B'.$r, $g['code'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C'.$r, $g['name'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D'.$r, $g['manufacturer'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E'.$r, $g['unit'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('F'.$r, $g['total']);
            $sheet->setCellValue('G'.$r, $g['lists']->count());
            $sheet->setCellValueExplicit('H'.$r, $g['depts']->implode(', '), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('I'.$r, $g['lists']->implode(', '), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }

        $lastRow = max($r - 1, $headRow);
        $sheet->getStyle('F'.($headRow + 1).':F'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.###');
        $sheet->getStyle('F'.($headRow + 1).':F'.$lastRow)->getFont()->setBold(true);
        $this->exportStyleTable($sheet, $headRow, $lastRow, $lastCol);

        foreach ([6, 13, 40, 14, 9, 14, 9, 24, 30] as $c => $w) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c + 1))->setWidth($w);
        }

        $sheet->freezePane('A'.($headRow + 1));
        $sheet->setAutoFilter('A'.$headRow.':'.$lastCol.$lastRow);
    }

    private function exportStyleTable(Worksheet $sheet, int $headRow, int $lastRow, string $lastCol): void
    {
        $head = $sheet->getStyle('A'.$headRow.':'.$lastCol.$headRow);
        $head->getFont()->setBold(true)->getColor()->setARGB('FF1F5E9E');
        $head->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEAF3FC');
        $head->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($headRow)->setRowHeight(32);

        $body = $sheet->getStyle('A'.$headRow.':'.$lastCol.$lastRow);
        $body->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF9CC7EE');

        if ($lastRow > $headRow) {
            $sheet->getStyle('A'.($headRow + 1).':'.$lastCol.$lastRow)->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $sheet->getStyle('A'.($headRow + 1).':A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function exportDeptLabel($list): string
    {
        return trim($list->department_short_name.($list->company_short_name ? ' - '.$list->company_short_name : ''));
    }

    private function exportPeriod($list): string
    {
        return str_pad((string) $list->month, 2, '0', STR_PAD_LEFT).'/'.$list->year;
    }
}
