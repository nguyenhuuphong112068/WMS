<?php

namespace App\Support\InitialData;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * SINH FILE EXCEL MẪU KHỞI TẠO DỮ LIỆU
 *
 * Dựng toàn bộ file từ InitialDataSchema: sheet Hướng dẫn, các sheet dữ liệu (tiêu đề, mô tả,
 * tên cột, gợi ý, ô chọn liên kết giữa các sheet), sheet tra cứu hoạt chất / nhóm nguy hại lấy
 * từ DB và sheet ẩn chứa danh sách chọn cố định.
 *
 * Ô chọn tham chiếu dùng Name dạng OFFSET + COUNTA nên danh sách co giãn theo số dòng khách
 * hàng đã điền, không hiện hàng nghìn dòng trống.
 */
class InitialDataTemplate
{
    // Bảng màu xanh dương nhạt của dự án (xem layout/css.blade.php)
    private const PRIMARY = '2E7BC4';

    private const PRIMARY_DARK = '1F5E9E';

    private const PRIMARY_LIGHT = '5AA0DE';

    private const PRIMARY_SOFT = 'EAF3FC';

    private const TEXT_MAIN = '2D3748';

    private const MUTED = '64748B';

    private const DANGER = 'DC2626';

    /** Màu tab theo nhóm sheet, để khách hàng nhìn thanh tab là biết đang ở phần nào. */
    private const GROUP_TAB_COLORS = [
        'Tổ chức' => '1F5E9E',
        'Dữ liệu gốc dùng chung' => '2E7BC4',
        'Tên vật tư / hoá chất / chất chuẩn' => '17B8D4',
        'Danh mục công ty' => '0F766E',
        'Định khu kho' => '7C3AED',
        'Khai báo hàng của phòng' => 'F59E0B',
        'Tồn đầu kỳ' => '16A34A',
    ];

    public static function save(string $path): void
    {
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $writer = new Xlsx(self::build());
        // Giữ nguyên công thức để Excel tự tính lúc mở, không tính trước bằng PHP
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
    }

    public static function build(): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('WMS')
            ->setTitle('Khởi tạo dữ liệu ban đầu WMS')
            ->setSubject(InitialDataSchema::VERSION)
            ->setDescription('File mẫu tập hợp dữ liệu gốc, danh mục và tồn đầu kỳ để nạp vào hệ thống WMS.');

        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $guide = $book->getActiveSheet();
        $guide->setTitle(InitialDataSchema::GUIDE_SHEET);

        $lists = $book->createSheet();
        $lists->setTitle(InitialDataSchema::LIST_SHEET);
        self::buildListSheet($book, $lists);

        foreach (InitialDataSchema::sheets() as $key => $sheet) {
            $ws = $book->createSheet();
            $ws->setTitle($sheet['title']);
            self::buildDataSheet($ws, $key, $sheet);
        }

        foreach (InitialDataSchema::referenceSheets() as $key => $sheet) {
            $ws = $book->createSheet();
            $ws->setTitle($sheet['title']);
            self::buildReferenceSheet($ws, $key, $sheet);
        }

        self::defineRefNames($book);

        foreach (InitialDataSchema::sheets() as $key => $sheet) {
            self::addValidations($book->getSheetByName($sheet['title']), $sheet);
        }

        self::buildGuide($guide);

        // Sheet danh sách để cuối và ẩn hẳn, khách hàng không cần thấy
        $book->setIndexByName(InitialDataSchema::LIST_SHEET, $book->getSheetCount() - 1);
        $lists->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
        $book->setActiveSheetIndex(0);

        return $book;
    }

    // ------------------------------------------------------------------ SHEET HƯỚNG DẪN

    private static function buildGuide(Worksheet $ws): void
    {
        $ws->getTabColor()->setRGB(self::DANGER);
        $ws->setShowGridlines(false);
        $ws->getColumnDimension('A')->setWidth(6);
        $ws->getColumnDimension('B')->setWidth(30);
        $ws->getColumnDimension('C')->setWidth(30);
        $ws->getColumnDimension('D')->setWidth(95);

        $ws->setCellValue('A1', 'FILE KHỞI TẠO DỮ LIỆU BAN ĐẦU - HỆ THỐNG QUẢN LÝ KHO (WMS)');
        $ws->mergeCells('A1:D1');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::PRIMARY);
        $ws->getRowDimension(1)->setRowHeight(28);

        $ws->setCellValue('A2', 'Phiên bản cấu trúc: '.InitialDataSchema::VERSION.'  ·  Ngày tạo file: '.now()->format('d/m/Y'));
        $ws->mergeCells('A2:D2');
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED);

        $row = 4;
        $row = self::guideHeading($ws, $row, 'A. CÁCH ĐIỀN FILE');

        $rules = [
            'Điền lần lượt theo thứ tự sheet 01 → 33. Sheet sau chỉ chọn lại được dữ liệu đã khai ở sheet trước (ô có mũi tên chọn).',
            'Chỉ điền dữ liệu từ DÒNG 5 trở xuống. Dòng 3 là tên cột, dòng 4 là gợi ý / ví dụ. KHÔNG đổi tên sheet, KHÔNG chèn / xoá / đổi thứ tự cột.',
            'Cột có dấu * (nền xanh đậm) là bắt buộc. Cột còn lại để trống nếu không có thông tin.',
            'Điền liền mạch từ trên xuống, không bỏ dòng trống xen giữa (danh sách chọn ở sheet sau sẽ bị cắt tại dòng trống).',
            'Ô có danh sách chọn: nên chọn từ danh sách thay vì gõ tay để tránh sai chính tả. Có thể dán (paste) giá trị nhưng phải trùng khớp chính xác.',
            'Ngày ghi theo dạng dd/mm/yyyy (VD: 31/12/2027). Số thập phân dùng dấu chấm hoặc theo định dạng số của Excel.',
            'Cột nhiều giá trị (Cảnh báo an toàn, Phân nhóm chuẩn, Mục đích sử dụng): ghi các mã / tên cách nhau bằng dấu phẩy hoặc chấm phẩy.',
            'Mã danh mục (sheet 19, 20, 21) do người điền tự đánh số theo mẫu M00001 / C00001 / S00001, không trùng. Các sheet 27-33 chọn lại theo mã này.',
            'Sheet nào không có dữ liệu thì để trống, hệ thống bỏ qua. Dữ liệu đã có sẵn trong hệ thống (trùng tên / mã) được giữ nguyên, không ghi đè.',
            'Sheet "Vai trò" và "Đơn vị tính" đã điền sẵn dữ liệu hệ thống đang có - giữ lại để chọn, có thể bổ sung thêm dòng.',
        ];
        foreach ($rules as $i => $text) {
            $row = self::guideLine($ws, $row, ($i + 1).'.', $text);
        }

        $row++;
        $row = self::guideHeading($ws, $row, 'B. TỒN ĐẦU KỲ (SHEET 31 - 33)');
        $stockRules = [
            'Ghi tồn kho THỰC TẾ tại thời điểm bắt đầu dùng hệ thống, theo đơn vị tính của phòng đã khai ở sheet 27 / 28 / 29.',
            'Mỗi dòng = 1 nhóm kiện / chai / ống giống hệt nhau. "Số kiện" = bao nhiêu kiện như vậy; mỗi kiện được cấp 1 mã xuất nhập riêng để in nhãn. VD: 3 chai Methanol 4L còn nguyên → Lượng mỗi chai = 4, Số kiện = 3.',
            'Chai / ống đã mở dở thì ghi riêng 1 dòng với lượng còn lại thực tế.',
            'Ngày nhập kho trên hệ thống = ngày nạp file (không điền tay). Lô tồn đầu được ghi nhận ĐẠT kiểm tra và cộng ngay vào tồn kho.',
            'Vị trí lưu bắt buộc, phải là vị trí (sheet 26) cùng phòng ban, loại lưu trữ trống hoặc đúng loại hàng.',
            'Mỗi phòng ban chỉ nạp tồn đầu kỳ MỘT lần. Nạp lại sẽ bị chặn để tránh cộng trùng tồn.',
        ];
        foreach ($stockRules as $i => $text) {
            $row = self::guideLine($ws, $row, ($i + 1).'.', $text);
        }

        $row++;
        $row = self::guideHeading($ws, $row, 'C. DANH SÁCH SHEET');
        foreach (['STT', 'Sheet', 'Nhóm', 'Nội dung'] as $i => $label) {
            $ws->setCellValue(InitialDataSchema::letter($i).$row, $label);
        }
        self::styleHeader($ws, 'A'.$row.':D'.$row, self::PRIMARY);
        $row++;

        $no = 1;
        foreach (InitialDataSchema::sheets() as $sheet) {
            $ws->setCellValue('A'.$row, $no++);
            $ws->setCellValue('B'.$row, $sheet['title']);
            $ws->getCell('B'.$row)->getHyperlink()->setUrl("sheet://'".$sheet['title']."'!A5");
            $ws->getStyle('B'.$row)->getFont()->setUnderline(true)->getColor()->setRGB(self::PRIMARY);
            $ws->setCellValue('C'.$row, $sheet['group']);
            $ws->setCellValue('D'.$row, $sheet['desc']);
            $ws->getStyle('A'.$row.':D'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $ws->getStyle('C'.$row)->getFont()->getColor()->setRGB(self::GROUP_TAB_COLORS[$sheet['group']] ?? self::TEXT_MAIN);
            $row++;
        }
        foreach (InitialDataSchema::referenceSheets() as $sheet) {
            $ws->setCellValue('A'.$row, '—');
            $ws->setCellValue('B'.$row, $sheet['title']);
            $ws->setCellValue('C'.$row, 'Tra cứu (chỉ đọc)');
            $ws->setCellValue('D'.$row, $sheet['desc']);
            $ws->getStyle('A'.$row.':D'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $row++;
        }
        $ws->getStyle('A'.($row - count(InitialDataSchema::sheets()) - 2).':D'.($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D6E4F2');

        $row++;
        $row = self::guideHeading($ws, $row, 'D. MÃ DÙNG CHO CỘT NHIỀU GIÁ TRỊ');
        $lists = InitialDataSchema::lists();
        $row = self::guideLine($ws, $row, '', 'Cảnh báo an toàn (sheet 20): '.implode('; ', array_keys($lists['safety_warning'])));
        $row = self::guideLine($ws, $row, '', 'Phân nhóm chuẩn (sheet 21, 33): '.implode('; ', array_keys($lists['standard_group'])));
        $row = self::guideLine($ws, $row, '', 'Chỉ cần ghi phần mã đứng trước dấu " - ", VD: "TOXIC, CORROSIVE" hoặc "PRS, IMPRS".');

        $ws->getStyle('A4:D'.$row)->getFont()->getColor()->setRGB(self::TEXT_MAIN);
        $ws->freezePane('A4');
    }

    private static function guideHeading(Worksheet $ws, int $row, string $text): int
    {
        $ws->setCellValue('A'.$row, $text);
        $ws->mergeCells('A'.$row.':D'.$row);
        $ws->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB(self::PRIMARY_DARK);
        $ws->getStyle('A'.$row.':D'.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::PRIMARY_LIGHT);

        return $row + 1;
    }

    private static function guideLine(Worksheet $ws, int $row, string $no, string $text): int
    {
        $ws->setCellValue('A'.$row, $no);
        $ws->setCellValue('B'.$row, $text);
        $ws->mergeCells('B'.$row.':D'.$row);
        $ws->getStyle('A'.$row.':D'.$row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $ws->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        // Ô gộp không tự giãn chiều cao: ước lượng theo độ dài chữ trên bề rộng ~150 ký tự
        $ws->getRowDimension($row)->setRowHeight(15 * max(1, (int) ceil(mb_strlen($text) / 150)));

        return $row + 1;
    }

    // ------------------------------------------------------------------ SHEET DỮ LIỆU

    private static function buildDataSheet(Worksheet $ws, string $key, array $sheet): void
    {
        $columns = $sheet['columns'];
        $last = InitialDataSchema::letter(count($columns) - 1);
        $maxRow = InitialDataSchema::MAX_ROWS;
        $first = InitialDataSchema::FIRST_DATA_ROW;

        $ws->getTabColor()->setRGB(self::GROUP_TAB_COLORS[$sheet['group']] ?? self::PRIMARY);

        $ws->setCellValue('A1', mb_strtoupper(preg_replace('/^\d+\./', '', $sheet['title'])));
        $ws->mergeCells('A1:'.$last.'1');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::PRIMARY);
        $ws->getRowDimension(1)->setRowHeight(22);

        $ws->setCellValue('A2', $sheet['desc']);
        $ws->mergeCells('A2:'.$last.'2');
        $ws->getStyle('A2')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED);
        $ws->getRowDimension(2)->setRowHeight(32);

        foreach ($columns as $i => $col) {
            $letter = InitialDataSchema::letter($i);

            // Định dạng gắn cho CẢ CỘT (một style của cột) thay vì từng ô 5.000 dòng - áp
            // trước khi ghi ô để ô tiêu đề / gợi ý vẫn giữ style riêng ghi đè lên sau đó.
            $columnFormat = match ($col['type']) {
                'int' => '0',
                'num' => '#,##0.####',
                'date' => 'dd/mm/yyyy',
                'calc' => null,
                // Mã như "001" phải giữ nguyên số 0 đầu: ép định dạng chữ
                default => NumberFormat::FORMAT_TEXT,
            };
            if ($columnFormat) {
                $ws->getStyle($letter.':'.$letter)->getNumberFormat()->setFormatCode($columnFormat);
            }

            $ws->setCellValue($letter.InitialDataSchema::HEADER_ROW, InitialDataSchema::headerLabel($col));
            $ws->setCellValue($letter.InitialDataSchema::HINT_ROW, $col['hint'] ?? '');
            $ws->getColumnDimension($letter)->setWidth($col['width'] ?? 16);

            self::styleHeader(
                $ws,
                $letter.InitialDataSchema::HEADER_ROW,
                ! empty($col['req']) ? self::PRIMARY_DARK : ($col['type'] === 'calc' ? self::MUTED : self::PRIMARY_LIGHT)
            );

            if ($col['type'] === 'calc') {
                for ($r = $first; $r <= $maxRow; $r++) {
                    $ws->setCellValue($letter.$r, str_replace('{r}', (string) $r, $col['formula']));
                }
            }

            if (! empty($col['hidden'])) {
                $ws->getColumnDimension($letter)->setVisible(false);
            }
        }

        $hint = 'A'.InitialDataSchema::HINT_ROW.':'.$last.InitialDataSchema::HINT_ROW;
        $ws->getStyle($hint)->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB(self::MUTED);
        $ws->getStyle($hint)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::PRIMARY_SOFT);
        $ws->getStyle($hint)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $ws->getRowDimension(InitialDataSchema::HEADER_ROW)->setRowHeight(32);
        $ws->getRowDimension(InitialDataSchema::HINT_ROW)->setRowHeight(26);

        $ws->freezePane('B'.$first);
        $ws->setAutoFilter('A'.InitialDataSchema::HEADER_ROW.':'.$last.InitialDataSchema::HEADER_ROW);

        foreach (self::prefill($key) as $offset => $values) {
            foreach ($values as $i => $value) {
                $ws->setCellValueExplicit(
                    InitialDataSchema::letter($i).($first + $offset),
                    $value,
                    is_numeric($value) && $columns[$i]['type'] === 'num'
                        ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                        : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
        }
    }

    private static function styleHeader(Worksheet $ws, string $range, string $fill): void
    {
        $style = $ws->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fill);
        $style->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('FFFFFF');
    }

    /**
     * Dữ liệu điền sẵn: các dòng hệ thống đang có mà khách hàng cần chọn lại.
     *
     * @return array<int, array<int, string>> dòng => [giá trị theo thứ tự cột]
     */
    private static function prefill(string $key): array
    {
        if ($key === 'roles' && Schema::hasTable('roles')) {
            return DB::table('roles')->orderBy('id')->get()
                ->map(fn ($r) => [(string) $r->name, (string) ($r->description ?? '')])
                ->all();
        }

        if ($key === 'units') {
            $groupLabels = array_flip(InitialDataSchema::lists()['unit_group']);
            $rows = Schema::hasTable('units')
                ? DB::table('units')->where('status_id', 1)->orderBy('unit_group')->orderBy('factor_to_base')->get()
                    ->map(fn ($u) => [(string) $u->short_name, (string) $u->name, $groupLabels[$u->unit_group] ?? '', self::number($u->factor_to_base)])
                    ->all()
                : [];

            return $rows ?: array_map(
                fn ($u) => [$u[0], $u[1], $groupLabels[$u[2]], self::number($u[3])],
                self::defaultUnits()
            );
        }

        return [];
    }

    /** Bộ đơn vị chuẩn khi DB chưa có đơn vị nào - trùng với database/seeders/UnitSeeder. */
    private static function defaultUnits(): array
    {
        return [
            ['µg', 'Vi-crô-gam', 'mass', 0.000001], ['mg', 'Mi-li-gam', 'mass', 0.001], ['g', 'Gam', 'mass', 1],
            ['kg', 'Ki-lô-gam', 'mass', 1000], ['tấn', 'Tấn', 'mass', 1000000],
            ['µl', 'Vi-crô-lít', 'volume', 0.001], ['ml', 'Mi-li-lít', 'volume', 1], ['L', 'Lít', 'volume', 1000],
            ['cái', 'Cái', 'count', 1], ['bộ', 'Bộ', 'count', 1], ['đôi', 'Đôi', 'count', 1], ['chai', 'Chai', 'count', 1],
            ['lọ', 'Lọ', 'count', 1], ['ống', 'Ống', 'count', 1], ['can', 'Can', 'count', 1], ['hộp', 'Hộp', 'count', 1],
            ['thùng', 'Thùng', 'count', 1], ['bao', 'Bao', 'count', 1], ['túi', 'Túi', 'count', 1], ['gói', 'Gói', 'count', 1],
            ['cuộn', 'Cuộn', 'count', 1], ['tờ', 'Tờ', 'count', 1], ['m', 'Mét', 'count', 1],
        ];
    }

    private static function number($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
    }

    // ------------------------------------------------------------------ SHEET TRA CỨU

    private static function buildReferenceSheet(Worksheet $ws, string $key, array $sheet): void
    {
        $columns = $sheet['columns'];
        $last = InitialDataSchema::letter(count($columns) - 1);
        $first = InitialDataSchema::FIRST_DATA_ROW;

        $ws->getTabColor()->setRGB(self::MUTED);
        $ws->setCellValue('A1', mb_strtoupper(preg_replace('/^TC\./', 'Tra cứu: ', $sheet['title'])));
        $ws->mergeCells('A1:'.$last.'1');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::PRIMARY);
        $ws->setCellValue('A2', $sheet['desc']);
        $ws->mergeCells('A2:'.$last.'2');
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED);

        foreach ($columns as $i => $col) {
            $letter = InitialDataSchema::letter($i);
            $ws->setCellValue($letter.InitialDataSchema::HEADER_ROW, $col['label']);
            $ws->getColumnDimension($letter)->setWidth($col['width']);
        }
        self::styleHeader($ws, 'A'.InitialDataSchema::HEADER_ROW.':'.$last.InitialDataSchema::HEADER_ROW, self::MUTED);

        $rows = match ($key) {
            'ref_active_ingredients' => Schema::hasTable('active_ingredients')
                ? DB::table('active_ingredients')->where('status_id', 1)->where('app_status', 'approved')->orderBy('code')
                    ->get()->map(fn ($r) => [$r->code, $r->name, $r->name_en, $r->cas_no, $r->threshold_kg !== null ? self::number($r->threshold_kg) : ''])->all()
                : [],
            'ref_hazard_categories' => Schema::hasTable('mixture_hazard_categories')
                ? DB::table('mixture_hazard_categories')->where('status_id', 1)->where('app_status', 'approved')->orderBy('code')
                    ->get()->map(fn ($r) => [$r->code, $r->hazard_group, str_replace("\n", ' ', $r->name), self::number($r->threshold_kg)])->all()
                : [],
            default => [],
        };

        foreach ($rows as $offset => $values) {
            foreach ($values as $i => $value) {
                $ws->setCellValueExplicit(InitialDataSchema::letter($i).($first + $offset), (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $ws->freezePane('A'.$first);
        $ws->setAutoFilter('A'.InitialDataSchema::HEADER_ROW.':'.$last.max(InitialDataSchema::HEADER_ROW, $first + count($rows) - 1));
        $ws->getProtection()->setSheet(true)->setAutoFilter(false)->setSort(false);
    }

    // ------------------------------------------------------------------ DANH SÁCH CHỌN

    private static function buildListSheet(Spreadsheet $book, Worksheet $ws): void
    {
        $ws->setCellValue('A1', 'VERSION');
        $ws->setCellValue('A2', InitialDataSchema::VERSION);

        $index = 1;
        foreach (InitialDataSchema::lists() as $name => $items) {
            $letter = InitialDataSchema::letter($index++);
            $ws->setCellValue($letter.'1', $name);

            $row = 2;
            foreach (array_keys($items) as $label) {
                $ws->setCellValueExplicit($letter.$row++, (string) $label, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }

            $book->addNamedRange(new NamedRange(
                self::listName($name),
                $ws,
                '$'.$letter.'$2:$'.$letter.'$'.max(2, $row - 1)
            ));
        }
    }

    private static function listName(string $list): string
    {
        return 'L_'.strtoupper($list);
    }

    /**
     * Name cho các vùng chọn tham chiếu. Dùng OFFSET để danh sách chỉ dài đúng số dòng đã
     * điền; cột công thức (hiển thị "mã | tên") trả "" ở dòng trống nên đếm bằng COUNTIF "?*".
     */
    private static function defineRefNames(Spreadsheet $book): void
    {
        $sheets = InitialDataSchema::sheets();
        $first = InitialDataSchema::FIRST_DATA_ROW;
        $max = InitialDataSchema::REF_MAX_ROWS;

        foreach (InitialDataSchema::refs() as $name => [$sheetKey, $column]) {
            $title = InitialDataSchema::sheetTitle($sheetKey);
            $letter = InitialDataSchema::columnLetter($sheetKey, $column);
            $isCalc = is_string($column) && collect($sheets[$sheetKey]['columns'])->firstWhere('key', $column)['type'] === 'calc';

            $quoted = "'".str_replace("'", "''", $title)."'";
            $span = $quoted.'!$'.$letter.'$'.$first.':$'.$letter.'$'.$max;
            $count = $isCalc ? 'COUNTIF('.$span.',"?*")' : 'COUNTA('.$span.')';

            $book->addDefinedName(\PhpOffice\PhpSpreadsheet\DefinedName::createInstance(
                $name,
                null,
                '=OFFSET('.$quoted.'!$'.$letter.'$'.$first.',0,0,MAX(1,'.$count.'),1)'
            ));
        }
    }

    private static function addValidations(Worksheet $ws, array $sheet): void
    {
        $first = InitialDataSchema::FIRST_DATA_ROW;
        $max = InitialDataSchema::MAX_ROWS;

        foreach ($sheet['columns'] as $i => $col) {
            $letter = InitialDataSchema::letter($i);
            $range = $letter.$first.':'.$letter.$max;
            $label = $col['label'];

            $validation = match ($col['type']) {
                'list' => self::validation(DataValidation::TYPE_LIST, '='.self::listName($col['list']), $label, ! empty($col['free'])),
                'ref' => self::validation(DataValidation::TYPE_LIST, '='.$col['ref'], $label),
                'int' => self::numberValidation(DataValidation::TYPE_WHOLE, $col, $label),
                'num' => self::numberValidation(DataValidation::TYPE_DECIMAL, $col, $label),
                'date' => self::dateValidation($label),
                'text' => isset($col['max']) ? self::lengthValidation($col, $label) : null,
                default => null,
            };

            if ($validation) {
                $ws->setDataValidation($range, $validation);
            }
        }
    }

    private static function validation(string $type, string $formula, string $label, bool $free = false): DataValidation
    {
        $v = new DataValidation();
        $v->setType($type);
        $v->setAllowBlank(true);
        $v->setShowDropDown(true);
        $v->setShowErrorMessage(true);
        // Cột "free" (Loại hoá chất) chỉ gợi ý, vẫn cho gõ giá trị khác
        $v->setErrorStyle($free ? DataValidation::STYLE_INFORMATION : DataValidation::STYLE_STOP);
        $v->setErrorTitle($label);
        $v->setError('Vui lòng chọn giá trị trong danh sách.');
        $v->setFormula1($formula);

        return $v;
    }

    private static function numberValidation(string $type, array $col, string $label): ?DataValidation
    {
        $min = $col['gt'] ?? $col['min'] ?? null;
        $maxValue = $col['maxValue'] ?? null;

        if ($min === null && $maxValue === null) {
            return null;
        }

        $v = new DataValidation();
        $v->setType($type);
        $v->setAllowBlank(true);
        $v->setShowErrorMessage(true);
        $v->setErrorStyle(DataValidation::STYLE_STOP);
        $v->setErrorTitle($label);

        if (isset($col['gt'])) {
            $v->setOperator(DataValidation::OPERATOR_GREATERTHAN);
            $v->setFormula1((string) $col['gt']);
            $v->setError('Giá trị phải lớn hơn '.$col['gt'].($maxValue !== null ? ' (tối đa '.$maxValue.')' : '').'.');
        } elseif ($maxValue !== null) {
            $v->setOperator(DataValidation::OPERATOR_BETWEEN);
            $v->setFormula1((string) ($min ?? 0));
            $v->setFormula2((string) $maxValue);
            $v->setError('Giá trị phải từ '.($min ?? 0).' đến '.$maxValue.'.');
        } else {
            $v->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
            $v->setFormula1((string) $min);
            $v->setError('Giá trị phải từ '.$min.' trở lên.');
        }

        return $v;
    }

    private static function dateValidation(string $label): DataValidation
    {
        $v = new DataValidation();
        $v->setType(DataValidation::TYPE_DATE);
        $v->setOperator(DataValidation::OPERATOR_BETWEEN);
        // 01/01/2000 - 31/12/2100 theo số ngày của Excel
        $v->setFormula1('36526');
        $v->setFormula2('73415');
        $v->setAllowBlank(true);
        $v->setShowErrorMessage(true);
        $v->setErrorStyle(DataValidation::STYLE_STOP);
        $v->setErrorTitle($label);
        $v->setError('Vui lòng nhập ngày hợp lệ dạng dd/mm/yyyy.');

        return $v;
    }

    private static function lengthValidation(array $col, string $label): DataValidation
    {
        $v = new DataValidation();
        $v->setType(DataValidation::TYPE_TEXTLENGTH);
        $v->setOperator(DataValidation::OPERATOR_LESSTHANOREQUAL);
        $v->setFormula1((string) $col['max']);
        $v->setAllowBlank(true);
        $v->setShowErrorMessage(true);
        $v->setErrorStyle(DataValidation::STYLE_STOP);
        $v->setErrorTitle($label);
        $v->setError('Tối đa '.$col['max'].' ký tự.');

        return $v;
    }
}
