<?php

namespace App\Support\InitialData;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Đọc file khởi tạo dữ liệu đã đính kèm để XEM trên web (không ghi gì vào DB).
 *
 * - Sheet dữ liệu của file mẫu WMS: tiêu đề cột lấy ở dòng HEADER_ROW, dữ liệu từ FIRST_DATA_ROW,
 *   bỏ cột ẩn (cột công thức phụ) và dòng trống; số dòng điền sẵn từ hệ thống đọc ở sheet LIST_SHEET
 *   để tách dòng có sẵn với dòng phòng ban mới điền.
 * - Sheet lạ (người dùng tự thêm / file không theo mẫu): hiện nguyên các dòng có dữ liệu, cột theo chữ cái.
 * - Bỏ qua sheet ẩn, sheet Hướng dẫn và sheet tra cứu (dữ liệu hệ thống, chỉ đọc).
 * Công thức lấy giá trị Excel đã tính khi lưu file, không tính lại.
 */
class InitialDataPreview
{
    /** Số dòng tối đa hiện cho một sheet. */
    public const MAX_ROWS = 10000;

    /** Số cột tối đa hiện cho sheet lạ. */
    private const MAX_GENERIC_COLUMNS = 60;

    /**
     * Mỗi dòng = [số dòng Excel, ô 1, ô 2...]; dòng Excel < firstNew là dòng hệ thống điền sẵn.
     *
     * @return array{sheets: list<array{title: string, columns: list<string>, rows: list<list<string|int>>, firstNew: int, truncated: bool}>}|null
     *         null = không đọc được file
     */
    public static function read(string $path): ?array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $book = $reader->load($path);
        } catch (Throwable) {
            return null;
        }

        $keys = array_flip(array_map(fn ($sheet) => $sheet['title'], InitialDataSchema::sheets()));
        $skip = array_merge(
            [InitialDataSchema::LIST_SHEET, InitialDataSchema::GUIDE_SHEET],
            array_column(InitialDataSchema::referenceSheets(), 'title')
        );
        $prefilled = self::prefillCounts($book->getSheetByName(InitialDataSchema::LIST_SHEET));

        $sheets = [];
        foreach ($book->getWorksheetIterator() as $ws) {
            if ($ws->getSheetState() !== Worksheet::SHEETSTATE_VISIBLE || in_array($ws->getTitle(), $skip, true)) {
                continue;
            }

            $key = $keys[$ws->getTitle()] ?? null;
            $spec = $key !== null ? InitialDataSchema::sheets()[$key] : null;
            $sheets[] = $spec && self::matchesTemplate($ws, $spec)
                ? self::templateSheet($ws, $spec, $prefilled[$key] ?? 0)
                : self::genericSheet($ws);
        }

        $book->disconnectWorksheets();

        return ['sheets' => $sheets];
    }

    /** Số dòng điền sẵn theo sheet key, ghi ở sheet LIST_SHEET dạng "key:số dòng". */
    private static function prefillCounts(?Worksheet $lists): array
    {
        $counts = [];
        for ($r = InitialDataSchema::PREFILL_CELL_ROW; $lists && ($entry = (string) $lists->getCell('A'.$r)->getValue()) !== ''; $r++) {
            [$key, $value] = array_pad(explode(':', $entry, 2), 2, '');
            if (! str_starts_with($key, '@')) {
                $counts[$key] = (int) $value;
            }
        }

        return $counts;
    }

    /** Dòng tiêu đề còn đúng mẫu (file cũ / bị chèn cột thì hiện như sheet lạ). */
    private static function matchesTemplate(Worksheet $ws, array $spec): bool
    {
        foreach ($spec['columns'] as $i => $col) {
            $header = trim((string) self::value($ws, InitialDataSchema::letter($i).InitialDataSchema::HEADER_ROW));
            if ($header !== InitialDataSchema::headerLabel($col)) {
                return false;
            }
        }

        return true;
    }

    private static function templateSheet(Worksheet $ws, array $spec, int $prefill): array
    {
        $columns = array_filter($spec['columns'], fn ($col) => empty($col['hidden']));
        $rows = [];
        $truncated = false;

        for ($r = InitialDataSchema::FIRST_DATA_ROW, $last = $ws->getHighestDataRow(); $r <= $last; $r++) {
            $cells = [];
            $filled = false;
            foreach ($columns as $i => $col) {
                $coordinate = InitialDataSchema::letter($i).$r;
                $value = self::value($ws, $coordinate, $col['type'] === 'auto');
                $cells[] = self::format($value, $col['type']);
                // Cột công thức / mã tự sinh luôn có công thức sẵn: dòng chỉ tính là có dữ liệu khi có ô nhập
                $filled = $filled || (! in_array($col['type'], ['calc', 'auto'], true) && end($cells) !== '');
            }

            if (! $filled) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                $truncated = true;
                break;
            }
            $rows[] = array_merge([$r], $cells);
        }

        return [
            'title' => $ws->getTitle(),
            'columns' => array_values(array_map(fn ($col) => InitialDataSchema::headerLabel($col), $columns)),
            'rows' => $rows,
            'firstNew' => InitialDataSchema::FIRST_DATA_ROW + $prefill,
            'truncated' => $truncated,
        ];
    }

    private static function genericSheet(Worksheet $ws): array
    {
        $lastColumn = min(Coordinate::columnIndexFromString($ws->getHighestDataColumn()), self::MAX_GENERIC_COLUMNS);
        $letters = [];
        for ($c = 1; $c <= $lastColumn; $c++) {
            $letter = Coordinate::stringFromColumnIndex($c);
            if ($ws->getColumnDimension($letter)->getVisible()) {
                $letters[] = $letter;
            }
        }

        $rows = [];
        $truncated = false;
        for ($r = 1, $last = $ws->getHighestDataRow(); $r <= $last; $r++) {
            $cells = array_map(fn ($letter) => self::format(self::value($ws, $letter.$r), 'text'), $letters);
            if (! array_filter($cells, fn ($v) => $v !== '')) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                $truncated = true;
                break;
            }
            $rows[] = array_merge([$r], $cells);
        }

        return [
            'title' => $ws->getTitle(),
            'columns' => $letters,
            'rows' => $rows,
            'firstNew' => 1,
            'truncated' => $truncated,
        ];
    }

    /**
     * Giá trị ô, không tạo ô mới khi ô chưa có. Công thức: giá trị Excel đã tính lúc lưu file;
     * $calculate = file chưa mở bằng Excel (chưa có giá trị đã tính) thì tự tính (mã tự sinh).
     */
    private static function value(Worksheet $ws, string $coordinate, bool $calculate = false): mixed
    {
        if (! $ws->cellExists($coordinate)) {
            return null;
        }

        /** @var Cell $cell */
        $cell = $ws->getCell($coordinate);
        if (! $cell->isFormula()) {
            $value = $cell->getValue();

            return $value instanceof RichText ? $value->getPlainText() : $value;
        }

        $value = $cell->getOldCalculatedValue();
        if ($value === null && $calculate) {
            try {
                $value = $cell->getCalculatedValue();
            } catch (Throwable) {
                $value = null;
            }
        }

        return $value;
    }

    private static function format(mixed $value, string $type): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_numeric($value) && ! is_string($value)) {
            if ($type === 'date') {
                try {
                    return Date::excelToDateTimeObject((float) $value)->format('d/m/Y');
                } catch (Throwable) {
                    return (string) $value;
                }
            }

            // Bỏ sai số dấu phẩy động (0.1 + 0.2) và số 0 thừa sau dấu chấm
            return is_int($value) ? (string) $value : rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
        }

        return trim((string) $value);
    }
}
